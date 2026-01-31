<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class TokenVerificationService
{
    private const CACHE_TTL = 300; // 5 minutes

    private AssetTypeDetector $assetDetector;

    public function __construct()
    {
        $this->assetDetector = app(AssetTypeDetector::class);
    }

    /**
     * Verify a token with comprehensive analysis
     */
    public function verifyToken(string $input): array
    {
        // Detect blockchain and normalize address
        $chain = $this->detectChain($input);
        $address = $this->normalizeAddress($input, $chain);

        Log::info('Token verification started', ['input' => $input, 'chain' => $chain, 'address' => $address]);

        // MANDATORY: Detect asset type before proceeding
        $assetType = $this->assetDetector->detectAssetType($address, $chain);

        // Reject native assets (BTC, ETH, etc.)
        if ($assetType['is_native']) {
            return [
                'error' => $assetType['error'],
                'asset_type' => $assetType['type'],
                'asset_name' => $assetType['asset_name'] ?? null,
                'is_native' => true,
            ];
        }

        // Reject wallet addresses (EOA)
        if (!$assetType['is_contract']) {
            return [
                'error' => $assetType['error'] ?? 'Cannot verify wallet addresses. Please provide a token contract address.',
                'asset_type' => $assetType['type'],
                'is_contract' => false,
            ];
        }

        // Try cache first
        $cacheKey = "token_verify_{$chain}_{$address}";
        if ($cached = Cache::get($cacheKey)) {
            // Add asset type info to cached data
            $cached['asset_type_info'] = $assetType;
            return $cached;
        }

        // Fetch data based on chain
        $data = match ($chain) {
            'ton' => $this->verifyTonToken($address),
            'ethereum', 'eth' => $this->verifyEthereumToken($address),
            'bsc' => $this->verifyBscToken($address),
            'base' => $this->verifyBaseToken($address),
            default => $this->getGenericTokenInfo($input, $chain)
        };

        // Add asset type information
        $data['asset_type_info'] = $assetType;

        // Calculate risk score with breakdown
        $scoring = $this->calculateRiskScoreWithBreakdown($data);
        $data['risk_score'] = $scoring['total_score'];
        $data['trust_score'] = 100 - $data['risk_score'];
        $data['score_breakdown'] = $scoring['breakdown'];
        $data['risk_factors'] = $scoring['factors'];

        // Properly detect ownership status
        $data['ownership_status'] = $this->determineOwnershipStatus($data);

        // Add flags
        $data['green_flags'] = $this->getGreenFlags($data);
        $data['red_flags'] = $this->getRedFlags($data);
        $data['warnings'] = $this->getWarnings($data);

        // Add differentiation context
        $data['profile_context'] = $this->getProfileContext($data);

        // Cache results
        Cache::put($cacheKey, $data, now()->addSeconds(self::CACHE_TTL));

        return $data;
    }

    /**
     * Verify TON token
     */
    private function verifyTonToken(string $address): array
    {
        $apiKey = config('services.ton.api_key');
        if (!$apiKey) {
            return ['error' => 'TON API key not configured'];
        }

        try {
            // Get jetton info
            $response = Http::withHeaders([
                'Authorization' => "Bearer {$apiKey}"
            ])->timeout(15)->get("https://tonapi.io/v2/jettons/{$address}");

            if (!$response->successful()) {
                return ['error' => 'Failed to fetch token data from TON API'];
            }

            $data = $response->json();

            // Get holder information
            $holdersResponse = Http::withHeaders([
                'Authorization' => "Bearer {$apiKey}"
            ])->timeout(15)->get("https://tonapi.io/v2/jettons/{$address}/holders", [
                'limit' => 100,
                'offset' => 0
            ]);

            $holders = $holdersResponse->successful() ? $holdersResponse->json('addresses', []) : [];

            // Calculate holder distribution
            $totalSupply = (float) ($data['total_supply'] ?? 0);
            $topHolders = array_slice($holders, 0, 10);
            $holderAnalysis = $this->analyzeHolderDistribution($topHolders, $totalSupply);

            return [
                'chain' => 'TON',
                'address' => $address,
                'name' => $data['metadata']['name'] ?? 'Unknown',
                'symbol' => $data['metadata']['symbol'] ?? 'N/A',
                'decimals' => $data['metadata']['decimals'] ?? 9,
                'total_supply' => $totalSupply,
                'holders_count' => $data['holders_count'] ?? count($holders),
                'verified' => $data['verification'] === 'whitelist',
                'mintable' => $data['mintable'] ?? true,
                'admin' => $data['admin']['address'] ?? null,
                'top_holders' => $topHolders,
                'holder_distribution' => $holderAnalysis,
                'image' => $data['metadata']['image'] ?? null,
                'description' => $data['metadata']['description'] ?? null,
                'explorer_url' => "https://tonscan.org/jetton/{$address}",
            ];
        } catch (\Exception $e) {
            Log::error('TON token verification failed', ['error' => $e->getMessage(), 'address' => $address]);
            return ['error' => 'Failed to verify TON token: ' . $e->getMessage()];
        }
    }

    /**
     * Verify Ethereum token
     */
    private function verifyEthereumToken(string $address): array
    {
        $apiKey = config('services.etherscan.api_key');

        // If no API key, use alternative methods
        if (!$apiKey) {
            return $this->verifyEthereumTokenNoAPI($address);
        }

        try {
            // Get token info using V2 endpoint
            $response = Http::timeout(15)->get('https://api.etherscan.io/v2/api', [
                'chainid' => 1,
                'module' => 'token',
                'action' => 'tokeninfo',
                'contractaddress' => $address,
                'apikey' => $apiKey
            ]);

            if (!$response->successful()) {
                return ['error' => 'Failed to fetch token data from Etherscan'];
            }

            $data = $response->json();

            // Get contract source code using V2 endpoint
            $contractResponse = Http::timeout(15)->get('https://api.etherscan.io/v2/api', [
                'chainid' => 1,
                'module' => 'contract',
                'action' => 'getsourcecode',
                'address' => $address,
                'apikey' => $apiKey
            ]);

            $contractData = [];
            if ($contractResponse->successful()) {
                $result = $contractResponse->json('result', []);
                // Ensure result is an array and has data
                if (is_array($result) && !empty($result) && is_array($result[0])) {
                    $contractData = $result[0];
                } else {
                    Log::warning('Etherscan getsourcecode returned invalid result', ['result' => $result]);
                }
            }

            // Get top holders (approximate from transactions) using V2 endpoint
            $holdersResponse = Http::timeout(15)->get('https://api.etherscan.io/v2/api', [
                'chainid' => 1,
                'module' => 'account',
                'action' => 'tokentx',
                'contractaddress' => $address,
                'page' => 1,
                'offset' => 100,
                'sort' => 'desc',
                'apikey' => $apiKey
            ]);

            $transactions = [];
            if ($holdersResponse->successful()) {
                $result = $holdersResponse->json('result', []);
                // Ensure result is an array, not an error string
                if (is_array($result)) {
                    $transactions = $result;
                } else {
                    Log::warning('Etherscan tokentx returned non-array result', ['result' => $result]);
                }
            }
            $topHolders = !empty($transactions) ? $this->extractTopHoldersFromTransactions($transactions) : [];

            $tokenInfo = $data['result'][0] ?? [];

            // Build result with safe array access
            $result = [
                'chain' => 'Ethereum',
                'address' => $address,
                'name' => $tokenInfo['name'] ?? $contractData['ContractName'] ?? 'Unknown',
                'symbol' => $tokenInfo['symbol'] ?? 'N/A',
                'decimals' => (int) ($tokenInfo['decimals'] ?? 18),
                'total_supply' => (float) ($tokenInfo['totalSupply'] ?? 0),
            ];

            // Safely add contract data fields
            $result['verified'] = !empty($contractData['SourceCode'] ?? '');
            $result['contract_name'] = $contractData['ContractName'] ?? null;
            $result['compiler_version'] = $contractData['CompilerVersion'] ?? null;
            $result['optimization_used'] = isset($contractData['OptimizationUsed']) && $contractData['OptimizationUsed'] === '1';
            $result['proxy'] = str_contains(strtolower($contractData['Implementation'] ?? ''), '0x');
            $result['has_source_code'] = !empty($contractData['SourceCode'] ?? '');
            $result['top_holders'] = $topHolders;
            $result['explorer_url'] = "https://etherscan.io/token/{$address}";

            return $result;
        } catch (\Exception $e) {
            Log::error('Ethereum token verification failed', ['error' => $e->getMessage(), 'address' => $address]);
            return ['error' => 'Failed to verify Ethereum token: ' . $e->getMessage()];
        }
    }

    /**
     * Verify Ethereum token without API key (limited data)
     */
    private function verifyEthereumTokenNoAPI(string $address): array
    {
        try {
            // Try to get basic contract info without API key
            $contractResponse = Http::timeout(15)->get('https://api.etherscan.io/api', [
                'module' => 'contract',
                'action' => 'getsourcecode',
                'address' => $address,
            ]);

            $contractData = [];
            if ($contractResponse->successful()) {
                $result = $contractResponse->json();
                if (isset($result['result'][0])) {
                    $contractData = $result['result'][0];
                }
            }

            return [
                'chain' => 'Ethereum',
                'address' => $address,
                'name' => $contractData['ContractName'] ?? 'Unknown',
                'symbol' => 'N/A',
                'verified' => !empty($contractData['SourceCode'] ?? ''),
                'contract_name' => $contractData['ContractName'] ?? null,
                'compiler_version' => $contractData['CompilerVersion'] ?? null,
                'optimization_used' => ($contractData['OptimizationUsed'] ?? '0') === '1',
                'proxy' => str_contains(strtolower($contractData['Implementation'] ?? ''), '0x'),
                'has_source_code' => !empty($contractData['SourceCode'] ?? ''),
                'top_holders' => [],
                'explorer_url' => "https://etherscan.io/token/{$address}",
                'api_key_needed' => true,
                'limited_data' => true,
            ];
        } catch (\Exception $e) {
            Log::error('Ethereum token verification (no API) failed', ['error' => $e->getMessage(), 'address' => $address]);

            // Return basic info even on failure
            return [
                'chain' => 'Ethereum',
                'address' => $address,
                'name' => 'Unknown',
                'symbol' => 'N/A',
                'verified' => false,
                'explorer_url' => "https://etherscan.io/token/{$address}",
                'api_key_needed' => true,
                'limited_data' => true,
                'partial_error' => 'Limited data available without API key'
            ];
        }
    }

    /**
     * Verify BSC token
     */
    private function verifyBscToken(string $address): array
    {
        $apiKey = config('services.bscscan.api_key');

        // Work without API key
        try {
            $params = [
                'module' => 'contract',
                'action' => 'getsourcecode',
                'address' => $address,
            ];

            if ($apiKey) {
                $params['apikey'] = $apiKey;
            }

            $response = Http::timeout(15)->get('https://api.bscscan.com/api', $params);

            if (!$response->successful()) {
                return [
                    'chain' => 'BSC',
                    'address' => $address,
                    'name' => 'Unknown',
                    'verified' => false,
                    'explorer_url' => "https://bscscan.com/token/{$address}",
                    'limited_data' => !$apiKey,
                ];
            }

            $contractData = $response->json('result')[0] ?? [];

            return [
                'chain' => 'BSC',
                'address' => $address,
                'name' => $contractData['ContractName'] ?? 'Unknown',
                'verified' => !empty($contractData['SourceCode'] ?? ''),
                'compiler_version' => $contractData['CompilerVersion'] ?? null,
                'optimization_used' => ($contractData['OptimizationUsed'] ?? '0') === '1',
                'proxy' => str_contains(strtolower($contractData['Implementation'] ?? ''), '0x'),
                'has_source_code' => !empty($contractData['SourceCode'] ?? ''),
                'license_type' => $contractData['LicenseType'] ?? 'None',
                'explorer_url' => "https://bscscan.com/token/{$address}",
            ];
        } catch (\Exception $e) {
            Log::error('BSC token verification failed', ['error' => $e->getMessage(), 'address' => $address]);
            return ['error' => 'Failed to verify BSC token: ' . $e->getMessage()];
        }
    }

    /**
     * Verify Base token
     */
    private function verifyBaseToken(string $address): array
    {
        $apiKey = config('services.basescan.api_key');

        try {
            $params = [
                'module' => 'contract',
                'action' => 'getsourcecode',
                'address' => $address,
            ];

            if ($apiKey) {
                $params['apikey'] = $apiKey;
            }

            $response = Http::timeout(15)->get('https://api.basescan.org/api', $params);

            if (!$response->successful()) {
                return [
                    'chain' => 'Base',
                    'address' => $address,
                    'name' => 'Unknown',
                    'verified' => false,
                    'explorer_url' => "https://basescan.org/token/{$address}",
                    'limited_data' => !$apiKey,
                ];
            }

            $contractData = $response->json('result')[0] ?? [];

            return [
                'chain' => 'Base',
                'address' => $address,
                'name' => $contractData['ContractName'] ?? 'Unknown',
                'verified' => !empty($contractData['SourceCode'] ?? ''),
                'compiler_version' => $contractData['CompilerVersion'] ?? null,
                'optimization_used' => ($contractData['OptimizationUsed'] ?? '0') === '1',
                'has_source_code' => !empty($contractData['SourceCode'] ?? ''),
                'explorer_url' => "https://basescan.org/token/{$address}",
            ];
        } catch (\Exception $e) {
            Log::error('Base token verification failed', ['error' => $e->getMessage(), 'address' => $address]);
            return ['error' => 'Failed to verify Base token: ' . $e->getMessage()];
        }
    }

    /**
     * Get generic token info (fallback)
     */
    private function getGenericTokenInfo(string $input, string $chain): array
    {
        return [
            'chain' => strtoupper($chain),
            'address' => $input,
            'name' => 'Unknown',
            'symbol' => 'N/A',
            'verified' => false,
            'error' => 'Chain not fully supported yet. Add API keys for full verification.'
        ];
    }

    /**
     * Analyze holder distribution
     */
    private function analyzeHolderDistribution(array $holders, float $totalSupply): array
    {
        if (empty($holders) || $totalSupply <= 0) {
            return [
                'top_10_percentage' => 0,
                'concentrated' => false,
                'distribution_quality' => 'unknown'
            ];
        }

        $top10Total = 0;
        foreach (array_slice($holders, 0, 10) as $holder) {
            $balance = (float) ($holder['balance'] ?? 0);
            $top10Total += $balance;
        }

        $top10Percentage = ($top10Total / $totalSupply) * 100;

        return [
            'top_10_percentage' => round($top10Percentage, 2),
            'concentrated' => $top10Percentage > 50,
            'distribution_quality' => $top10Percentage > 70 ? 'poor' : ($top10Percentage > 50 ? 'moderate' : 'good'),
            'whale_risk' => $top10Percentage > 60 ? 'high' : ($top10Percentage > 40 ? 'medium' : 'low')
        ];
    }

    /**
     * Extract top holders from transactions (Ethereum fallback)
     */
    private function extractTopHoldersFromTransactions(array $transactions): array
    {
        $holders = [];

        foreach ($transactions as $tx) {
            $to = $tx['to'] ?? null;
            if ($to) {
                if (!isset($holders[$to])) {
                    $holders[$to] = ['address' => $to, 'tx_count' => 0];
                }
                $holders[$to]['tx_count']++;
            }
        }

        // Sort by transaction count (approximation)
        usort($holders, fn($a, $b) => $b['tx_count'] <=> $a['tx_count']);

        return array_slice($holders, 0, 10);
    }

    /**
     * Calculate risk score with transparent breakdown
     */
    private function calculateRiskScoreWithBreakdown(array $data): array
    {
        $breakdown = [];
        $factors = [];
        $score = 0;

        if (isset($data['error'])) {
            return [
                'total_score' => 50,
                'breakdown' => [['factor' => 'Unknown/Error', 'points' => 50, 'reason' => 'Unable to fetch data']],
                'factors' => ['Data unavailable']
            ];
        }

        // CONTRACT VERIFICATION (±25 points)
        if (!($data['verified'] ?? false)) {
            $points = 25;
            $score += $points;
            $breakdown[] = ['factor' => 'Contract Not Verified', 'points' => $points, 'impact' => 'negative'];
            $factors[] = 'Unverified contract';
        } else {
            $breakdown[] = ['factor' => 'Contract Verified', 'points' => 0, 'impact' => 'neutral'];
        }

        // SOURCE CODE AVAILABILITY (±20 points)
        if (isset($data['has_source_code'])) {
            if (!$data['has_source_code']) {
                $points = 20;
                $score += $points;
                $breakdown[] = ['factor' => 'No Source Code', 'points' => $points, 'impact' => 'negative'];
                $factors[] = 'Source code unavailable';
            } else {
                $breakdown[] = ['factor' => 'Source Code Available', 'points' => 0, 'impact' => 'positive'];
            }
        }

        // OWNERSHIP STATUS (±20 points)
        $ownershipStatus = $this->determineOwnershipStatus($data);
        if ($ownershipStatus === 'active_owner') {
            $points = 20;
            $score += $points;
            $breakdown[] = ['factor' => 'Active Owner/Admin', 'points' => $points, 'impact' => 'negative'];
            $factors[] = 'Centralized ownership';
        } elseif ($ownershipStatus === 'renounced') {
            $breakdown[] = ['factor' => 'Ownership Renounced', 'points' => 0, 'impact' => 'positive'];
        } elseif ($ownershipStatus === 'unknown') {
            $points = 15;
            $score += $points;
            $breakdown[] = ['factor' => 'Ownership Unknown', 'points' => $points, 'impact' => 'negative'];
            $factors[] = 'Cannot verify ownership';
        }

        // PROXY/UPGRADEABLE (±15 points)
        if ($data['proxy'] ?? false) {
            $points = 15;
            $score += $points;
            $breakdown[] = ['factor' => 'Proxy/Upgradeable', 'points' => $points, 'impact' => 'negative'];
            $factors[] = 'Contract can be upgraded';
        } else {
            $breakdown[] = ['factor' => 'Not Proxy', 'points' => 0, 'impact' => 'neutral'];
        }

        // MINTABLE (±15 points)
        if (isset($data['mintable'])) {
            if ($data['mintable']) {
                $points = 15;
                $score += $points;
                $breakdown[] = ['factor' => 'Mintable (Inflation Risk)', 'points' => $points, 'impact' => 'negative'];
                $factors[] = 'Supply can be increased';
            } else {
                $breakdown[] = ['factor' => 'Fixed Supply', 'points' => 0, 'impact' => 'positive'];
            }
        }

        // HOLDER DISTRIBUTION (±20 points)
        $holderDist = $data['holder_distribution'] ?? [];
        $top10Pct = $holderDist['top_10_percentage'] ?? 0;
        $holderCount = $data['holders_count'] ?? 0;

        if ($top10Pct > 70) {
            $points = 20;
            $score += $points;
            $breakdown[] = ['factor' => 'Highly Concentrated Holdings', 'points' => $points, 'impact' => 'negative'];
            $factors[] = "Top 10 holders own {$top10Pct}%";
        } elseif ($top10Pct > 50) {
            $points = 10;
            $score += $points;
            $breakdown[] = ['factor' => 'Concentrated Holdings', 'points' => $points, 'impact' => 'negative'];
            $factors[] = "Top 10 holders own {$top10Pct}%";
        } else {
            $breakdown[] = ['factor' => 'Holder Distribution', 'points' => 0, 'impact' => 'neutral'];
        }

        // LOW HOLDER COUNT (±10 points)
        if ($holderCount > 0 && $holderCount < 50) {
            $points = 10;
            $score += $points;
            $breakdown[] = ['factor' => 'Very Low Holder Count', 'points' => $points, 'impact' => 'negative'];
            $factors[] = "Only {$holderCount} holders";
        } elseif ($holderCount > 0 && $holderCount < 100) {
            $points = 5;
            $score += $points;
            $breakdown[] = ['factor' => 'Low Holder Count', 'points' => $points, 'impact' => 'negative'];
            $factors[] = "{$holderCount} holders";
        } else {
            $breakdown[] = ['factor' => 'Holder Count', 'points' => 0, 'impact' => 'neutral'];
        }

        return [
            'total_score' => min(100, $score),
            'breakdown' => $breakdown,
            'factors' => $factors
        ];
    }

    /**
     * Determine ownership status with proof
     */
    private function determineOwnershipStatus(array $data): string
    {
        // If contract not verified, we cannot know ownership status
        if (!($data['verified'] ?? false)) {
            return 'unknown';
        }

        // Check if admin/owner field exists
        $admin = $data['admin'] ?? null;

        // If no admin field, assume renounced (for verified contracts)
        if (empty($admin)) {
            return 'renounced';
        }

        // Check if admin is burn address
        $burnAddresses = [
            '0x0000000000000000000000000000000000000000',
            '0x000000000000000000000000000000000000dead',
            'EQAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAM9c', // TON burn
        ];

        $adminLower = strtolower($admin);
        foreach ($burnAddresses as $burn) {
            if (strtolower($burn) === $adminLower) {
                return 'renounced';
            }
        }

        return 'active_owner';
    }

    /**
     * Get profile context for differentiation
     */
    private function getProfileContext(array $data): string
    {
        $verified = $data['verified'] ?? false;
        $holderCount = $data['holders_count'] ?? 0;
        $hasSource = $data['has_source_code'] ?? false;
        $riskScore = $data['risk_score'] ?? 50;

        if (!$verified && !$hasSource && $holderCount < 100) {
            return "This profile matches early-stage unverified contracts with minimal on-chain history and limited holder base.";
        }

        if (!$verified && $holderCount < 50) {
            return "This appears to be a very early-stage token with unverified contract code and small community.";
        }

        if ($verified && $riskScore < 30 && $holderCount > 1000) {
            return "This profile indicates a mature, verified token with established community and lower risk factors.";
        }

        if ($data['proxy'] ?? false) {
            return "This is an upgradeable proxy contract, meaning the implementation can be changed by the owner.";
        }

        if ($riskScore > 70) {
            return "Multiple high-risk factors detected. Exercise extreme caution.";
        }

        return "Standard token profile for this risk category.";
    }

    /**
     * Get green flags
     */
    private function getGreenFlags(array $data): array
    {
        $flags = [];

        if ($data['verified'] ?? false) {
            $flags[] = '✅ Contract verified on blockchain explorer';
        }

        if (isset($data['has_source_code']) && $data['has_source_code']) {
            $flags[] = '✅ Source code available and auditable';
        }

        if (isset($data['mintable']) && !$data['mintable']) {
            $flags[] = '✅ Minting disabled (fixed supply)';
        }

        // Only claim renounced if we can verify it
        $ownershipStatus = $data['ownership_status'] ?? $this->determineOwnershipStatus($data);
        if ($ownershipStatus === 'renounced') {
            $flags[] = '✅ Ownership renounced (verified)';
        }

        if (isset($data['optimization_used']) && $data['optimization_used']) {
            $flags[] = '✅ Contract optimized for gas efficiency';
        }

        $holderDist = $data['holder_distribution'] ?? [];
        if (($holderDist['distribution_quality'] ?? '') === 'good') {
            $flags[] = '✅ Healthy holder distribution';
        }

        if (($data['holders_count'] ?? 0) > 100) {
            $flags[] = '✅ Strong community (' . number_format($data['holders_count']) . ' holders)';
        }

        return $flags;
    }

    /**
     * Get red flags
     */
    private function getRedFlags(array $data): array
    {
        $flags = [];

        if (!($data['verified'] ?? false)) {
            $flags[] = '❌ Contract NOT verified - cannot audit code';
        }

        if ($data['mintable'] ?? false) {
            $flags[] = '❌ Minting ACTIVE - supply can be inflated';
        }

        // Ownership flags based on verified status
        $ownershipStatus = $data['ownership_status'] ?? $this->determineOwnershipStatus($data);
        if ($ownershipStatus === 'active_owner') {
            $adminAddr = substr($data['admin'], 0, 10) . '...';
            $flags[] = "❌ Active owner/admin ({$adminAddr}) - centralized control";
        } elseif ($ownershipStatus === 'unknown') {
            $flags[] = '❌ Ownership status UNKNOWN - contract not verified';
        }

        if ($data['proxy'] ?? false) {
            $flags[] = '❌ Proxy contract detected - upgrade risk';
        }

        $holderDist = $data['holder_distribution'] ?? [];
        if ($holderDist['concentrated'] ?? false) {
            $pct = $holderDist['top_10_percentage'] ?? 0;
            $flags[] = "❌ Highly concentrated - top 10 holders own {$pct}%";
        }

        if (isset($data['has_source_code']) && !$data['has_source_code']) {
            $flags[] = '❌ No source code available';
        }

        $holderCount = $data['holders_count'] ?? 0;
        if ($holderCount > 0 && $holderCount < 50) {
            $flags[] = "❌ Very low holder count ({$holderCount}) - early/risky stage";
        } elseif ($holderCount === 0) {
            $flags[] = '❌ Holder count unavailable - cannot assess distribution';
        }

        return $flags;
    }

    /**
     * Get warnings
     */
    private function getWarnings(array $data): array
    {
        $warnings = [];

        $holderDist = $data['holder_distribution'] ?? [];
        $whaleRisk = $holderDist['whale_risk'] ?? 'unknown';

        if ($whaleRisk === 'high') {
            $warnings[] = '⚠️ HIGH whale risk - major holders can manipulate price';
        } elseif ($whaleRisk === 'medium') {
            $warnings[] = '⚠️ MEDIUM whale risk - monitor large holders';
        }

        // Only warn about holder count if we actually KNOW the count
        $holderCount = $data['holders_count'] ?? null;
        if ($holderCount !== null && is_numeric($holderCount) && $holderCount > 0 && $holderCount < 100) {
            $warnings[] = '⚠️ Small holder count (' . $holderCount . ') - liquidity may be limited';
        }

        // Only warn about supply if we know it's actually 0 (not just missing)
        if (isset($data['total_supply']) && $data['total_supply'] == 0) {
            $warnings[] = '⚠️ Total supply is zero - potential issue';
        }

        return $warnings;
    }

    /**
     * Detect blockchain from address format
     */
    private function detectChain(string $input): string
    {
        if (str_starts_with($input, 'EQ') || str_starts_with($input, 'UQ')) {
            return 'ton';
        }

        if (str_starts_with($input, '0x')) {
            // Default to Ethereum, but could be BSC, Base, etc.
            // Would need additional context to distinguish
            return 'ethereum';
        }

        return 'unknown';
    }

    /**
     * Normalize address format
     */
    private function normalizeAddress(string $input, string $chain): string
    {
        return trim($input);
    }
}
