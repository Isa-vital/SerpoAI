<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class TokenVerificationService
{
    private const CACHE_TTL = 300; // 5 minutes

    private AssetTypeDetector $assetDetector;
    private UniversalTokenDataService $universalData;

    public function __construct()
    {
        $this->assetDetector = app(AssetTypeDetector::class);
        $this->universalData = app(UniversalTokenDataService::class);
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

        // NEW: Get universal market data FIRST (DexScreener auto-detects the actual chain)
        $marketData = $this->universalData->getTokenData($address, $chain);

        // CRITICAL: Update chain from DexScreener BEFORE routing to chain-specific verification
        // DexScreener is chain-agnostic and knows the actual chain (bsc, polygon, etc.)
        if ($marketData['found'] && !empty($marketData['data']['chain'])) {
            $detectedChain = strtolower($marketData['data']['chain']);
            if ($detectedChain !== $chain && $detectedChain !== 'unknown') {
                Log::info('Chain corrected by DexScreener', ['original' => $chain, 'corrected' => $detectedChain]);
                $chain = $detectedChain;
            }
        }

        // Fetch blockchain-specific verification data using the CORRECT chain
        $data = match ($chain) {
            'ton' => $this->verifyTonToken($address),
            'solana', 'sol' => $this->verifySolanaToken($address),
            // All EVM chains use universal EVM verification
            'ethereum', 'eth' => $this->verifyEvmToken($address, 'ethereum'),
            'bsc' => $this->verifyEvmToken($address, 'bsc'),
            'base' => $this->verifyEvmToken($address, 'base'),
            'polygon' => $this->verifyEvmToken($address, 'polygon'),
            'arbitrum' => $this->verifyEvmToken($address, 'arbitrum'),
            'optimism' => $this->verifyEvmToken($address, 'optimism'),
            'avalanche' => $this->verifyEvmToken($address, 'avalanche'),
            'fantom' => $this->verifyEvmToken($address, 'fantom'),
            'cronos' => $this->verifyEvmToken($address, 'cronos'),
            'gnosis' => $this->verifyEvmToken($address, 'gnosis'),
            'celo' => $this->verifyEvmToken($address, 'celo'),
            'moonbeam' => $this->verifyEvmToken($address, 'moonbeam'),
            'moonriver' => $this->verifyEvmToken($address, 'moonriver'),
            'zksync' => $this->verifyEvmToken($address, 'zksync'),
            'linea' => $this->verifyEvmToken($address, 'linea'),
            'mantle' => $this->verifyEvmToken($address, 'mantle'),
            'scroll' => $this->verifyEvmToken($address, 'scroll'),
            default => $this->getGenericTokenInfo($address, $chain)
        };

        // Merge market data with verification data
        if ($marketData['found']) {
            // ALWAYS prefer DexScreener market name/symbol over contract name
            // Contract names are often technical (e.g., "GovernanceToken" instead of "Optimism")
            if (!empty($marketData['data']['name'])) {
                $data['name'] = $marketData['data']['name'];
            }
            if (!empty($marketData['data']['symbol'])) {
                $data['symbol'] = $marketData['data']['symbol'];
            }

            // CRITICAL: Update chain from market data if available (DexScreener knows the actual chain)
            if (!empty($marketData['data']['chain'])) {
                $detectedChain = strtolower($marketData['data']['chain']);
                if ($detectedChain !== $chain && $detectedChain !== 'unknown') {
                    $chain = $detectedChain;
                    $data['chain'] = $chain;

                    // Update explorer URL for the correct chain
                    $explorerMap = $this->getExplorerDomainMap();
                    $explorerDomain = $explorerMap[$chain] ?? null;

                    if ($explorerDomain) {
                        if ($chain === 'ton') {
                            $data['explorer_url'] = "https://{$explorerDomain}/jetton/{$address}";
                        } else {
                            $data['explorer_url'] = "https://{$explorerDomain}/token/{$address}";
                        }
                    }

                    Log::info('Chain and explorer updated from market data', [
                        'new_chain' => $chain,
                        'explorer_url' => $data['explorer_url'] ?? 'none'
                    ]);
                }
            }

            $data['market_data'] = $marketData['data'];
            $data['data_sources'] = $marketData['sources'];
        }

        // Calculate risk score with breakdown
        $riskResult = $this->calculateRiskScoreWithBreakdown($data);
        $data['risk_score'] = $riskResult['total_score'];
        $data['trust_score'] = 100 - $riskResult['total_score'];
        $data['score_breakdown'] = $riskResult['breakdown'];
        $data['risk_factors'] = $riskResult['factors'];

        $data['red_flags'] = $this->getRedFlags($data);
        $data['green_flags'] = $this->getGreenFlags($data);
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
            $decimals = (int) ($data['metadata']['decimals'] ?? 9);
            $rawTotalSupply = (float) ($data['total_supply'] ?? 0);
            // CRITICAL: Normalize total_supply from raw units to human-readable
            $totalSupply = $decimals > 0 ? $rawTotalSupply / pow(10, $decimals) : $rawTotalSupply;

            // Normalize holder balances from raw units too
            $topHolders = array_slice($holders, 0, 10);
            foreach ($topHolders as &$holder) {
                if (isset($holder['balance'])) {
                    $holder['balance'] = $decimals > 0 ? (float)$holder['balance'] / pow(10, $decimals) : (float)$holder['balance'];
                }
            }
            unset($holder);

            $holderAnalysis = $this->analyzeHolderDistribution($topHolders, $totalSupply);

            return [
                'chain' => 'TON',
                'address' => $address,
                'name' => $data['metadata']['name'] ?? 'Unknown',
                'symbol' => $data['metadata']['symbol'] ?? 'N/A',
                'decimals' => $decimals,
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
     * Verify Polygon token
     */
    private function verifyPolygonToken(string $address): array
    {
        $apiKey = env('POLYGONSCAN_API_KEY');

        try {
            $params = [
                'module' => 'contract',
                'action' => 'getsourcecode',
                'address' => $address,
            ];

            if ($apiKey) {
                $params['apikey'] = $apiKey;
            }

            $response = Http::timeout(15)->get('https://api.polygonscan.com/api', $params);

            if (!$response->successful()) {
                return [
                    'chain' => 'Polygon',
                    'address' => $address,
                    'name' => 'Unknown',
                    'verified' => false,
                    'explorer_url' => "https://polygonscan.com/token/{$address}",
                    'limited_data' => !$apiKey,
                ];
            }

            $contractData = $response->json('result')[0] ?? [];

            return [
                'chain' => 'Polygon',
                'address' => $address,
                'name' => $contractData['ContractName'] ?? 'Unknown',
                'verified' => !empty($contractData['SourceCode'] ?? ''),
                'has_source_code' => !empty($contractData['SourceCode'] ?? ''),
                'explorer_url' => "https://polygonscan.com/token/{$address}",
            ];
        } catch (\Exception $e) {
            return ['chain' => 'Polygon', 'error' => 'Failed to verify: ' . $e->getMessage()];
        }
    }

    /**
     * Verify Arbitrum token
     */
    private function verifyArbitrumToken(string $address): array
    {
        $apiKey = env('ARBISCAN_API_KEY');

        try {
            $params = [
                'module' => 'contract',
                'action' => 'getsourcecode',
                'address' => $address,
            ];

            if ($apiKey) {
                $params['apikey'] = $apiKey;
            }

            $response = Http::timeout(15)->get('https://api.arbiscan.io/api', $params);

            if ($response->successful()) {
                $contractData = $response->json('result')[0] ?? [];
                return [
                    'chain' => 'Arbitrum',
                    'address' => $address,
                    'name' => $contractData['ContractName'] ?? 'Unknown',
                    'verified' => !empty($contractData['SourceCode'] ?? ''),
                    'has_source_code' => !empty($contractData['SourceCode'] ?? ''),
                    'explorer_url' => "https://arbiscan.io/token/{$address}",
                ];
            }

            return [
                'chain' => 'Arbitrum',
                'address' => $address,
                'name' => 'Unknown',
                'verified' => false,
                'explorer_url' => "https://arbiscan.io/token/{$address}",
            ];
        } catch (\Exception $e) {
            return ['chain' => 'Arbitrum', 'error' => 'Failed to verify: ' . $e->getMessage()];
        }
    }

    /**
     * Verify Optimism token
     */
    private function verifyOptimismToken(string $address): array
    {
        try {
            $apiKey = env('OPTIMISM_API_KEY');
            $params = [
                'module' => 'contract',
                'action' => 'getsourcecode',
                'address' => $address,
            ];

            if ($apiKey) {
                $params['apikey'] = $apiKey;
            }

            $response = Http::timeout(15)->get('https://api-optimistic.etherscan.io/api', $params);

            if ($response->successful()) {
                $contractData = $response->json('result')[0] ?? [];
                return [
                    'chain' => 'Optimism',
                    'address' => $address,
                    'name' => $contractData['ContractName'] ?? 'Unknown',
                    'verified' => !empty($contractData['SourceCode'] ?? ''),
                    'has_source_code' => !empty($contractData['SourceCode'] ?? ''),
                    'explorer_url' => "https://optimistic.etherscan.io/token/{$address}",
                ];
            }

            return [
                'chain' => 'Optimism',
                'address' => $address,
                'name' => 'Unknown',
                'verified' => false,
                'explorer_url' => "https://optimistic.etherscan.io/token/{$address}",
            ];
        } catch (\Exception $e) {
            return ['chain' => 'Optimism', 'error' => 'Failed to verify: ' . $e->getMessage()];
        }
    }

    /**
     * Verify Avalanche token
     */
    private function verifyAvalancheToken(string $address): array
    {
        try {
            $apiKey = env('SNOWTRACE_API_KEY');
            $params = [
                'module' => 'contract',
                'action' => 'getsourcecode',
                'address' => $address,
            ];

            if ($apiKey) {
                $params['apikey'] = $apiKey;
            }

            $response = Http::timeout(15)->get('https://api.snowtrace.io/api', $params);

            if ($response->successful()) {
                $contractData = $response->json('result')[0] ?? [];
                return [
                    'chain' => 'Avalanche',
                    'address' => $address,
                    'name' => $contractData['ContractName'] ?? 'Unknown',
                    'verified' => !empty($contractData['SourceCode'] ?? ''),
                    'has_source_code' => !empty($contractData['SourceCode'] ?? ''),
                    'explorer_url' => "https://snowtrace.io/token/{$address}",
                ];
            }

            return [
                'chain' => 'Avalanche',
                'address' => $address,
                'name' => 'Unknown',
                'verified' => false,
                'explorer_url' => "https://snowtrace.io/token/{$address}",
            ];
        } catch (\Exception $e) {
            return ['chain' => 'Avalanche', 'error' => 'Failed to verify: ' . $e->getMessage()];
        }
    }


    /**
     * Universal EVM token verification - works with ANY Etherscan-compatible explorer
     * Uses Etherscan V2 unified API as primary, falls back to individual chain APIs
     */
    private function verifyEvmToken(string $address, string $chain): array
    {
        // Map chain to explorer API URL, domain, API key config, and display name
        $chainConfig = $this->getEvmChainConfig($chain);
        $apiUrl = $chainConfig['api_url'];
        $explorerDomain = $chainConfig['explorer_domain'];
        $displayName = $chainConfig['display_name'];
        $apiKey = $chainConfig['api_key'];
        $chainId = $chainConfig['chain_id'] ?? null;

        try {
            $contractData = [];

            // Step 1: Try Etherscan V2 unified API FIRST (works for all chains with one key)
            if ($chainId && $apiKey) {
                $v2Response = Http::timeout(15)->get('https://api.etherscan.io/v2/api', [
                    'chainid' => $chainId,
                    'module' => 'contract',
                    'action' => 'getsourcecode',
                    'address' => $address,
                    'apikey' => $apiKey,
                ]);
                if ($v2Response->successful()) {
                    $v2Result = $v2Response->json('result', []);
                    if (is_array($v2Result) && !empty($v2Result) && is_array($v2Result[0] ?? null) && !empty($v2Result[0]['SourceCode'] ?? '')) {
                        $contractData = $v2Result[0];
                        Log::info("Etherscan V2 API returned contract data for {$chain}", ['contract' => $contractData['ContractName'] ?? 'Unknown']);
                    }
                }
            }

            // Step 1b: Fallback to individual chain API if V2 returned empty
            if (empty($contractData['SourceCode'] ?? '')) {
                $params = [
                    'module' => 'contract',
                    'action' => 'getsourcecode',
                    'address' => $address,
                ];
                if ($apiKey) {
                    $params['apikey'] = $apiKey;
                }

                $response = Http::timeout(15)->get($apiUrl, $params);
                if ($response->successful()) {
                    $result = $response->json('result', []);
                    if (is_array($result) && !empty($result) && is_array($result[0] ?? null)) {
                        if (!empty($result[0]['SourceCode'] ?? '')) {
                            $contractData = $result[0];
                            Log::info("Individual chain API returned contract data for {$chain}", ['contract' => $contractData['ContractName'] ?? 'Unknown']);
                        }
                    }
                }
            }

            // Step 2: If primary returned empty (no API key / rate limited), try Routescan as free fallback
            $routescanChainId = $this->getRoutescanChainId($chain);
            if (empty($contractData['SourceCode'] ?? '') && $routescanChainId) {
                Log::info("Primary API returned empty for {$chain}, trying Routescan fallback", ['address' => $address]);
                $routescanUrl = "https://api.routescan.io/v2/network/mainnet/evm/{$routescanChainId}/etherscan/api";
                $routescanResponse = Http::timeout(15)->get($routescanUrl, [
                    'module' => 'contract',
                    'action' => 'getsourcecode',
                    'address' => $address,
                ]);

                if ($routescanResponse->successful()) {
                    $routeResult = $routescanResponse->json('result', []);
                    if (is_array($routeResult) && !empty($routeResult) && is_array($routeResult[0] ?? null)) {
                        if (!empty($routeResult[0]['SourceCode'] ?? '')) {
                            $contractData = $routeResult[0];
                            Log::info("Routescan provided contract data for {$chain}", ['contract' => $contractData['ContractName'] ?? 'Unknown']);
                        }
                    }
                }
            }

            $result = [
                'chain' => $chain,
                'address' => $address,
                'name' => $contractData['ContractName'] ?? 'Unknown',
                'verified' => !empty($contractData['SourceCode'] ?? ''),
                'has_source_code' => !empty($contractData['SourceCode'] ?? ''),
                'compiler_version' => $contractData['CompilerVersion'] ?? null,
                'optimization_used' => ($contractData['OptimizationUsed'] ?? '0') === '1',
                'proxy' => str_contains(strtolower($contractData['Implementation'] ?? ''), '0x'),
                'explorer_url' => "https://{$explorerDomain}/token/{$address}",
                'limited_data' => empty($apiKey),
            ];

            // Step 3: Get holder count & top holders from Blockscout v2 API (chain-independent, free, no key needed)
            $blockscoutUrl = $this->getBlockscoutApiUrl($chain);
            if ($blockscoutUrl) {
                $this->fetchBlockscoutHolderData($blockscoutUrl, $address, $result);
            }

            // Step 4: If still no holder count, try Etherscan V2 tokenholdercount API
            if (empty($result['holders_count']) && $apiKey && $chainId) {
                try {
                    $holderCountResponse = Http::timeout(10)->get('https://api.etherscan.io/v2/api', [
                        'chainid' => $chainId,
                        'module' => 'token',
                        'action' => 'tokenholdercount',
                        'contractaddress' => $address,
                        'apikey' => $apiKey,
                    ]);
                    if ($holderCountResponse->successful()) {
                        $hcResult = $holderCountResponse->json('result');
                        if (is_numeric($hcResult) && (int) $hcResult > 0) {
                            $result['holders_count'] = (int) $hcResult;
                            Log::info("Etherscan V2 tokenholdercount provided holder count for {$chain}", ['count' => $result['holders_count']]);
                        }
                    }
                } catch (\Exception $e) {
                    Log::debug("V2 tokenholdercount failed for {$chain}", ['error' => $e->getMessage()]);
                }
            }

            // Step 5: If no Blockscout top holders, try Etherscan V2 tokentx (with API key)
            if (empty($result['top_holders']) && $apiKey && $chainId) {
                try {
                    $holdersResponse = Http::timeout(15)->get('https://api.etherscan.io/v2/api', [
                        'chainid' => $chainId,
                        'module' => 'account',
                        'action' => 'tokentx',
                        'contractaddress' => $address,
                        'page' => 1,
                        'offset' => 100,
                        'sort' => 'desc',
                        'apikey' => $apiKey,
                    ]);

                    if ($holdersResponse->successful()) {
                        $txResult = $holdersResponse->json('result', []);
                        if (is_array($txResult) && !empty($txResult)) {
                            $result['top_holders'] = $this->extractTopHoldersFromTransactions($txResult);
                            Log::info("Etherscan V2 tokentx provided top holders for {$chain}");
                        }
                    }
                } catch (\Exception $e) {
                    Log::debug("V2 tokentx failed for {$chain}", ['error' => $e->getMessage()]);
                }
            }

            // Step 6: If still no total supply, try GeckoTerminal API (free, supports many chains)
            if (empty($result['total_supply'])) {
                try {
                    $geckoChain = $this->getGeckoTerminalChain($chain);
                    if ($geckoChain) {
                        $geckoResponse = Http::timeout(10)
                            ->withHeaders(['Accept' => 'application/json', 'User-Agent' => 'TradeBotAI/2.0'])
                            ->get("https://api.geckoterminal.com/api/v2/networks/{$geckoChain}/tokens/{$address}");
                        if ($geckoResponse->successful()) {
                            $geckoData = $geckoResponse->json('data.attributes', []);
                            // Use normalized_total_supply (already divided by decimals)
                            if (!empty($geckoData['normalized_total_supply'])) {
                                $result['total_supply'] = (float) $geckoData['normalized_total_supply'];
                                Log::info("GeckoTerminal provided total supply for {$chain}", ['supply' => $result['total_supply']]);
                            } elseif (!empty($geckoData['total_supply']) && isset($geckoData['decimals'])) {
                                $decimals = (int) $geckoData['decimals'];
                                if ($decimals > 0) {
                                    $result['total_supply'] = (float) bcdiv($geckoData['total_supply'], bcpow('10', (string) $decimals, 0), 4);
                                } else {
                                    $result['total_supply'] = (float) $geckoData['total_supply'];
                                }
                                Log::info("GeckoTerminal provided raw total supply for {$chain}", ['supply' => $result['total_supply']]);
                            }
                        }
                    }
                } catch (\Exception $e) {
                    Log::debug("GeckoTerminal total supply failed for {$chain}", ['error' => $e->getMessage()]);
                }
            }

            // Step 7: Last resort for total supply - direct RPC totalSupply() call
            if (empty($result['total_supply'])) {
                try {
                    $rpcUrl = $this->getPublicRpcUrl($chain);
                    if ($rpcUrl) {
                        $rpcResponse = Http::timeout(10)->post($rpcUrl, [
                            'jsonrpc' => '2.0',
                            'method' => 'eth_call',
                            'params' => [
                                ['to' => $address, 'data' => '0x18160ddd'], // totalSupply() selector
                                'latest',
                            ],
                            'id' => 1,
                        ]);
                        if ($rpcResponse->successful()) {
                            $hex = $rpcResponse->json('result');
                            if ($hex && strlen($hex) > 2) {
                                // Convert hex to float, assume 18 decimals if unknown
                                $rawSupply = hexdec(substr($hex, 2));
                                $result['total_supply'] = $rawSupply / 1e18;
                                Log::info("RPC totalSupply() provided supply for {$chain}", ['supply' => $result['total_supply']]);
                            }
                        }
                    }
                } catch (\Exception $e) {
                    Log::debug("RPC totalSupply() failed for {$chain}", ['error' => $e->getMessage()]);
                }
            }

            return $result;
        } catch (\Exception $e) {
            Log::error("{$displayName} token verification failed", ['error' => $e->getMessage(), 'address' => $address]);

            // Return basic data even on failure (DexScreener will fill the gaps)
            return [
                'chain' => $chain,
                'address' => $address,
                'name' => 'Unknown',
                'symbol' => 'N/A',
                'verified' => null, // null = unknown, not false
                'explorer_url' => "https://{$explorerDomain}/token/{$address}",
                'limited_data' => true,
                'market_data_only' => true,
            ];
        }
    }

    /**
     * Get Routescan chain ID for free contract verification fallback
     */
    private function getRoutescanChainId(string $chain): ?int
    {
        $chainIds = [
            'ethereum' => 1,
            'bsc' => 56,
            'polygon' => 137,
            'arbitrum' => 42161,
            'optimism' => 10,
            'avalanche' => 43114,
            'fantom' => 250,
            'base' => 8453,
            'cronos' => 25,
            'gnosis' => 100,
            'celo' => 42220,
            'moonbeam' => 1284,
            'moonriver' => 1285,
            'zksync' => 324,
            'linea' => 59144,
            'mantle' => 5000,
            'scroll' => 534352,
            'harmony' => 1666600000,
        ];
        return $chainIds[$chain] ?? null;
    }

    /**
     * Get Blockscout v2 API base URL for a chain (free, no key needed)
     * Blockscout provides holder count + top holder list with actual balances
     */
    private function getBlockscoutApiUrl(string $chain): ?string
    {
        $blockscoutUrls = [
            'mantle' => 'https://explorer.mantle.xyz/api/v2',
            'gnosis' => 'https://gnosis.blockscout.com/api/v2',
            'celo' => 'https://explorer.celo.org/mainnet/api/v2',
            'scroll' => 'https://scroll.blockscout.com/api/v2',
            'zksync' => 'https://block-explorer-api.mainnet.zksync.io/api',
            'base' => 'https://base.blockscout.com/api/v2',
            'optimism' => 'https://optimism.blockscout.com/api/v2',
            'arbitrum' => 'https://arbitrum.blockscout.com/api/v2',
            'polygon' => 'https://polygon.blockscout.com/api/v2',
            'ethereum' => 'https://eth.blockscout.com/api/v2',
            'linea' => 'https://linea.blockscout.com/api/v2',
        ];
        return $blockscoutUrls[$chain] ?? null;
    }

    /**
     * Fetch holder count and top holders from Blockscout v2 API
     */
    private function fetchBlockscoutHolderData(string $blockscoutUrl, string $address, array &$result): void
    {
        try {
            // Get token info (includes holder count)
            $tokenResponse = Http::timeout(10)->get("{$blockscoutUrl}/tokens/{$address}");
            if ($tokenResponse->successful()) {
                $tokenData = $tokenResponse->json();
                // Try multiple field names for holder count (varies by Blockscout version)
                $holderCount = $tokenData['holders'] ?? $tokenData['holder_count'] ?? $tokenData['holders_count'] ?? null;
                if ($holderCount !== null && (int) $holderCount > 0) {
                    $result['holders_count'] = (int) $holderCount;
                }
                if (isset($tokenData['total_supply']) && isset($tokenData['decimals'])) {
                    $decimals = (int) $tokenData['decimals'];
                    $rawSupply = $tokenData['total_supply'];
                    if ($decimals > 0) {
                        $result['total_supply'] = (float) bcdiv($rawSupply, bcpow('10', (string) $decimals, 0), $decimals);
                    } else {
                        $result['total_supply'] = (float) $rawSupply;
                    }
                }
            }

            // Get top holders with actual balances
            $holdersResponse = Http::timeout(10)->get("{$blockscoutUrl}/tokens/{$address}/holders");
            if ($holdersResponse->successful()) {
                $holdersData = $holdersResponse->json();
                $items = $holdersData['items'] ?? [];

                if (!empty($items)) {
                    $topHolders = [];
                    $decimals = (int) ($result['decimals'] ?? $holdersData['items'][0]['token']['decimals'] ?? 18);

                    foreach (array_slice($items, 0, 10) as $holder) {
                        $holderAddress = $holder['address']['hash'] ?? '';
                        $rawBalance = $holder['value'] ?? '0';
                        $balance = $decimals > 0 ? (float) bcdiv($rawBalance, bcpow('10', (string) $decimals, 0), 4) : (float) $rawBalance;

                        $topHolders[] = [
                            'address' => $holderAddress,
                            'balance' => $balance,
                            'tx_count' => 0,
                        ];
                    }

                    $result['top_holders'] = $topHolders;

                    // Calculate holder distribution
                    $totalSupply = $result['total_supply'] ?? 0;
                    if ($totalSupply > 0 && count($topHolders) > 0) {
                        $top10Balance = array_sum(array_column($topHolders, 'balance'));
                        $top10Pct = ($top10Balance / $totalSupply) * 100;

                        $result['holder_distribution'] = [
                            'top_10_percentage' => round($top10Pct, 2),
                            'whale_risk' => $top10Pct > 60 ? 'high' : ($top10Pct > 40 ? 'medium' : 'low'),
                            'distribution_quality' => $top10Pct > 60 ? 'poor' : ($top10Pct > 40 ? 'moderate' : 'good'),
                        ];
                    }
                }
            }

            Log::info('Blockscout holder data fetched', [
                'holders_count' => $result['holders_count'] ?? 0,
                'top_holders_count' => count($result['top_holders'] ?? []),
            ]);
        } catch (\Exception $e) {
            Log::warning('Blockscout holder data fetch failed', ['error' => $e->getMessage()]);
        }
    }

    /**
     * Map internal chain name to GeckoTerminal network slug
     */
    private function getGeckoTerminalChain(string $chain): ?string
    {
        $map = [
            'ethereum' => 'eth',
            'bsc' => 'bsc',
            'polygon' => 'polygon_pos',
            'arbitrum' => 'arbitrum',
            'optimism' => 'optimism',
            'avalanche' => 'avax',
            'fantom' => 'ftm',
            'base' => 'base',
            'cronos' => 'cro',
            'gnosis' => 'xdai',
            'celo' => 'celo',
            'moonbeam' => 'moonbeam',
            'zksync' => 'zksync',
            'linea' => 'linea',
            'mantle' => 'mantle',
            'scroll' => 'scroll',
            'pulsechain' => 'pulsechain',
            'blast' => 'blast',
        ];
        return $map[$chain] ?? null;
    }

    /**
     * Get a public free RPC URL for a chain (for totalSupply() calls)
     */
    private function getPublicRpcUrl(string $chain): ?string
    {
        $rpcs = [
            'ethereum' => 'https://eth.llamarpc.com',
            'bsc' => 'https://bsc-dataseed.binance.org/',
            'polygon' => 'https://polygon-rpc.com',
            'arbitrum' => 'https://arb1.arbitrum.io/rpc',
            'optimism' => 'https://mainnet.optimism.io',
            'avalanche' => 'https://api.avax.network/ext/bc/C/rpc',
            'fantom' => 'https://rpc.ftm.tools',
            'base' => 'https://mainnet.base.org',
            'cronos' => 'https://evm.cronos.org',
            'gnosis' => 'https://rpc.gnosischain.com',
            'celo' => 'https://forno.celo.org',
            'moonbeam' => 'https://rpc.api.moonbeam.network',
            'zksync' => 'https://mainnet.era.zksync.io',
            'linea' => 'https://rpc.linea.build',
            'mantle' => 'https://rpc.mantle.xyz',
            'scroll' => 'https://rpc.scroll.io',
            'blast' => 'https://rpc.blast.io',
        ];
        return $rpcs[$chain] ?? null;
    }

    /**
     * Get EVM chain configuration for explorer API
     * Now uses Etherscan V2 unified API (api.etherscan.io/v2/api?chainid=X) as primary
     * Falls back to individual chain APIs for chains not on V2
     */
    private function getEvmChainConfig(string $chain): array
    {
        $apiKey = config('services.etherscan.api_key'); // Single key works for ALL chains via V2

        $configs = [
            'ethereum' => [
                'chain_id' => 1,
                'api_url' => 'https://api.etherscan.io/api',
                'explorer_domain' => 'etherscan.io',
                'display_name' => 'Ethereum',
                'api_key' => $apiKey,
            ],
            'bsc' => [
                'chain_id' => 56,
                'api_url' => 'https://api.bscscan.com/api',
                'explorer_domain' => 'bscscan.com',
                'display_name' => 'BNB Chain',
                'api_key' => $apiKey,
            ],
            'base' => [
                'chain_id' => 8453,
                'api_url' => 'https://api.basescan.org/api',
                'explorer_domain' => 'basescan.org',
                'display_name' => 'Base',
                'api_key' => $apiKey,
            ],
            'polygon' => [
                'chain_id' => 137,
                'api_url' => 'https://api.polygonscan.com/api',
                'explorer_domain' => 'polygonscan.com',
                'display_name' => 'Polygon',
                'api_key' => $apiKey,
            ],
            'arbitrum' => [
                'chain_id' => 42161,
                'api_url' => 'https://api.arbiscan.io/api',
                'explorer_domain' => 'arbiscan.io',
                'display_name' => 'Arbitrum',
                'api_key' => $apiKey,
            ],
            'optimism' => [
                'chain_id' => 10,
                'api_url' => 'https://api-optimistic.etherscan.io/api',
                'explorer_domain' => 'optimistic.etherscan.io',
                'display_name' => 'Optimism',
                'api_key' => $apiKey,
            ],
            'avalanche' => [
                'chain_id' => 43114,
                'api_url' => 'https://api.snowtrace.io/api',
                'explorer_domain' => 'snowtrace.io',
                'display_name' => 'Avalanche',
                'api_key' => $apiKey,
            ],
            'fantom' => [
                'chain_id' => 250,
                'api_url' => 'https://api.ftmscan.com/api',
                'explorer_domain' => 'ftmscan.com',
                'display_name' => 'Fantom',
                'api_key' => $apiKey,
            ],
            'cronos' => [
                'chain_id' => 25,
                'api_url' => 'https://api.cronoscan.com/api',
                'explorer_domain' => 'cronoscan.com',
                'display_name' => 'Cronos',
                'api_key' => $apiKey,
            ],
            'gnosis' => [
                'chain_id' => 100,
                'api_url' => 'https://api.gnosisscan.io/api',
                'explorer_domain' => 'gnosisscan.io',
                'display_name' => 'Gnosis',
                'api_key' => $apiKey,
            ],
            'celo' => [
                'chain_id' => 42220,
                'api_url' => 'https://api.celoscan.io/api',
                'explorer_domain' => 'celoscan.io',
                'display_name' => 'Celo',
                'api_key' => $apiKey,
            ],
            'moonbeam' => [
                'chain_id' => 1284,
                'api_url' => 'https://api-moonbeam.moonscan.io/api',
                'explorer_domain' => 'moonbeam.moonscan.io',
                'display_name' => 'Moonbeam',
                'api_key' => $apiKey,
            ],
            'moonriver' => [
                'chain_id' => 1285,
                'api_url' => 'https://api-moonriver.moonscan.io/api',
                'explorer_domain' => 'moonriver.moonscan.io',
                'display_name' => 'Moonriver',
                'api_key' => $apiKey,
            ],
            'zksync' => [
                'chain_id' => 324,
                'api_url' => 'https://block-explorer-api.mainnet.zksync.io/api',
                'explorer_domain' => 'explorer.zksync.io',
                'display_name' => 'zkSync Era',
                'api_key' => $apiKey,
            ],
            'linea' => [
                'chain_id' => 59144,
                'api_url' => 'https://api.lineascan.build/api',
                'explorer_domain' => 'lineascan.build',
                'display_name' => 'Linea',
                'api_key' => $apiKey,
            ],
            'mantle' => [
                'chain_id' => 5000,
                'api_url' => 'https://api.mantlescan.xyz/api',
                'explorer_domain' => 'mantlescan.xyz',
                'display_name' => 'Mantle',
                'api_key' => $apiKey,
            ],
            'scroll' => [
                'chain_id' => 534352,
                'api_url' => 'https://api.scrollscan.com/api',
                'explorer_domain' => 'scrollscan.com',
                'display_name' => 'Scroll',
                'api_key' => $apiKey,
            ],
        ];

        return $configs[$chain] ?? [
            'chain_id' => null,
            'api_url' => 'https://api.etherscan.io/api',
            'explorer_domain' => 'etherscan.io',
            'display_name' => ucfirst($chain),
            'api_key' => null,
        ];
    }


    /**
     * Verify Solana token using Solscan API
     */
    private function verifySolanaToken(string $address): array
    {
        $apiKey = env('SOLSCAN_API_KEY');

        Log::info('Verifying Solana token', ['address' => $address, 'has_api_key' => !empty($apiKey)]);

        // Try Jupiter API first (most comprehensive Solana token registry, no auth)
        try {
            Log::info('Trying Jupiter API for Solana token');

            $response = Http::timeout(15)
                ->withHeaders([
                    'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
                    'Accept' => 'application/json',
                ])
                ->get("https://tokens.jup.ag/token/{$address}");

            if ($response->successful()) {
                $data = $response->json();

                if (!empty($data) && isset($data['symbol'])) {
                    Log::info('Jupiter API success', ['name' => $data['name'] ?? 'Unknown', 'symbol' => $data['symbol']]);

                    return [
                        'chain' => 'Solana',
                        'address' => $address,
                        'name' => $data['name'] ?? 'Unknown',
                        'symbol' => $data['symbol'] ?? 'Unknown',
                        'decimals' => $data['decimals'] ?? 9,
                        'verified' => true,
                        'has_source_code' => false,
                        'is_token' => true,
                        'logo_uri' => $data['logoURI'] ?? null,
                        'tags' => $data['tags'] ?? [],
                        'explorer_url' => "https://solscan.io/token/{$address}",
                        'data_source' => 'Jupiter Token Registry',
                    ];
                }
            }
        } catch (\Exception $e) {
            Log::warning('Jupiter API failed', ['error' => $e->getMessage()]);
        }

        // Try authenticated v2 API if key available (supports both FREE and PRO tiers)
        if ($apiKey) {
            // Try FREE tier endpoint first, then PRO endpoint
            $v2Endpoints = [
                ['url' => 'https://api.solscan.io/v2.0', 'tier' => 'free'],
                ['url' => 'https://pro-api.solscan.io/v2.0', 'tier' => 'pro'],
            ];

            foreach ($v2Endpoints as $endpoint) {
                try {
                    $headers = [
                        'token' => $apiKey,
                        'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
                        'Accept' => 'application/json',
                        'Accept-Language' => 'en-US,en;q=0.9',
                        'Accept-Encoding' => 'gzip, deflate, br',
                        'Origin' => 'https://solscan.io',
                        'Referer' => 'https://solscan.io/',
                    ];
                    $baseUrl = $endpoint['url'];
                    $url = "{$baseUrl}/token/meta?address={$address}";

                    Log::info('Attempting Solscan v2 API', ['url' => $url, 'tier' => $endpoint['tier']]);

                    $response = Http::timeout(20)
                        ->withHeaders($headers)
                        ->get($url);

                    if ($response->successful()) {
                        $data = $response->json();

                        Log::info('Solscan v2 API success', ['tier' => $endpoint['tier'], 'name' => $data['data']['name'] ?? 'Unknown']);

                        // Get token holders count
                        $holdersUrl = "{$baseUrl}/token/holders?address={$address}&page=1&page_size=1";
                        $holdersResponse = Http::timeout(15)
                            ->withHeaders($headers)
                            ->get($holdersUrl);

                        $holdersCount = 0;
                        if ($holdersResponse->successful()) {
                            $holdersData = $holdersResponse->json();
                            $holdersCount = $holdersData['data']['total'] ?? 0;
                        }

                        return [
                            'chain' => 'Solana',
                            'address' => $address,
                            'name' => $data['data']['name'] ?? 'Unknown',
                            'symbol' => $data['data']['symbol'] ?? 'Unknown',
                            'decimals' => $data['data']['decimals'] ?? 9,
                            'total_supply' => $data['data']['supply'] ?? 0,
                            'holders_count' => $holdersCount,
                            'verified' => !empty($data['data']['name']),
                            'has_source_code' => false,
                            'is_token' => true,
                            'is_spl_token' => true,
                            'explorer_url' => "https://solscan.io/token/{$address}",
                            'data_source' => 'Solscan v2 API (' . $endpoint['tier'] . ')',
                        ];
                    }

                    Log::warning('Solscan v2 API failed', ['tier' => $endpoint['tier'], 'status' => $response->status()]);
                } catch (\Exception $e) {
                    Log::warning('Solscan v2 API exception', ['tier' => $endpoint['tier'], 'error' => $e->getMessage()]);
                    // Continue to next endpoint
                    continue;
                }
            }
        }

        // Fallback to public API v1 (no authentication required)
        try {
            Log::info('Falling back to Solscan public API');

            $publicUrl = "https://public-api.solscan.io/token/meta?tokenAddress={$address}";
            $publicHeaders = [
                'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
                'Accept' => 'application/json',
                'Accept-Language' => 'en-US,en;q=0.9',
                'Referer' => 'https://solscan.io/',
            ];
            $publicResponse = Http::timeout(20)
                ->withHeaders($publicHeaders)
                ->get($publicUrl);

            if ($publicResponse->successful()) {
                $data = $publicResponse->json();

                Log::info('Solscan public API success', ['name' => $data['name'] ?? 'Unknown']);

                return [
                    'chain' => 'Solana',
                    'address' => $address,
                    'name' => $data['name'] ?? 'Unknown',
                    'symbol' => $data['symbol'] ?? 'Unknown',
                    'decimals' => $data['decimals'] ?? 9,
                    'total_supply' => $data['supply'] ?? 0,
                    'holders_count' => $data['holder'] ?? 0,
                    'verified' => !empty($data['name']),
                    'has_source_code' => false,
                    'is_token' => true,
                    'explorer_url' => "https://solscan.io/token/{$address}",
                    'limited_data' => true,
                    'data_source' => 'Solscan Public API',
                ];
            }

            Log::warning('Solscan public API failed', ['status' => $publicResponse->status()]);
        } catch (\Exception $e) {
            Log::error('Solscan public API exception', ['error' => $e->getMessage()]);
        }

        // Try public Solana RPC with multiple fallback endpoints
        $rpcEndpoints = [
            'https://api.mainnet-beta.solana.com',
            'https://solana-api.projectserum.com',
            'https://rpc.ankr.com/solana',
        ];

        foreach ($rpcEndpoints as $index => $rpcUrl) {
            try {
                Log::info('Trying public Solana RPC for token mint data', ['endpoint' => $rpcUrl, 'attempt' => $index + 1]);

                // Get mint account info (includes mint authority, freeze authority, supply, decimals)
                $rpcResponse = Http::timeout(15)
                    ->retry(2, 100) // Retry twice with 100ms delay
                    ->post($rpcUrl, [
                        'jsonrpc' => '2.0',
                        'id' => 1,
                        'method' => 'getAccountInfo',
                        'params' => [
                            $address,
                            ['encoding' => 'jsonParsed']
                        ]
                    ]);

                if ($rpcResponse->successful()) {
                    $result = $rpcResponse->json();
                    $accountData = $result['result']['value'] ?? null;

                    if ($accountData && isset($accountData['data']['parsed'])) {
                        $parsedData = $accountData['data']['parsed'];
                        $info = $parsedData['info'] ?? [];

                        if ($parsedData['type'] === 'mint' && !empty($info)) {
                            Log::info('Solana RPC success - mint account found', [
                                'supply' => $info['supply'] ?? 0,
                                'decimals' => $info['decimals'] ?? 0,
                                'has_mint_authority' => !empty($info['mintAuthority']),
                                'has_freeze_authority' => !empty($info['freezeAuthority'])
                            ]);

                            $supply = $info['supply'] ?? '0';
                            $decimals = $info['decimals'] ?? 9;
                            $mintAuthority = $info['mintAuthority'] ?? null;
                            $freezeAuthority = $info['freezeAuthority'] ?? null;

                            // Calculate actual supply
                            $actualSupply = floatval($supply) / pow(10, $decimals);

                            // Determine ownership status based on authorities
                            $ownershipStatus = 'unknown';
                            if ($mintAuthority === null && $freezeAuthority === null) {
                                $ownershipStatus = 'immutable';
                            } elseif ($mintAuthority !== null) {
                                $ownershipStatus = 'active_mint_authority';
                            }

                            return [
                                'chain' => 'Solana',
                                'address' => $address,
                                'name' => 'Unknown', // Will be filled by market data
                                'symbol' => 'Unknown', // Will be filled by market data
                                'decimals' => $decimals,
                                'total_supply' => $actualSupply,
                                'mint_authority' => $mintAuthority,
                                'freeze_authority' => $freezeAuthority,
                                'ownership_status' => $ownershipStatus,
                                'verified' => true,
                                'has_source_code' => false, // SPL tokens use Token Program
                                'is_token' => true,
                                'is_spl_token' => true,
                                'token_program' => 'SPL Token Program',
                                'explorer_url' => "https://solscan.io/token/{$address}",
                                'limited_data' => true,
                                'data_source' => 'Solana RPC (' . parse_url($rpcUrl, PHP_URL_HOST) . ')',
                            ];
                        }
                    }
                }

                Log::warning('Solana RPC: Not a valid mint account or no data', ['endpoint' => $rpcUrl]);
            } catch (\Exception $e) {
                Log::error('Solana RPC failed', ['endpoint' => $rpcUrl, 'error' => $e->getMessage()]);
                // Continue to next RPC endpoint
                continue;
            }
        }

        // All RPC endpoints failed
        Log::error('All Solana RPC endpoints failed', ['attempted' => count($rpcEndpoints)]);

        // All APIs failed
        $errorMsg = 'Unable to verify Solana token.';

        if ($apiKey) {
            $errorMsg .= ' Solscan API key returned 401 (regenerate at https://pro-api.solscan.io/).';
        }

        $errorMsg .= ' Token may not exist or is not indexed. Check: https://solscan.io/token/' . $address;

        return [
            'chain' => 'Solana',
            'address' => $address,
            'name' => 'Unknown',
            'verified' => false,
            'has_source_code' => false,
            'error' => $errorMsg,
            'limited_data' => true,
            'explorer_url' => "https://solscan.io/token/{$address}",
        ];
    }

    /**
     * Get generic token info (fallback)
     */
    private function getGenericTokenInfo(string $input, string $chain): array
    {
        // For unknown chains, provide a market-data-only report
        // DexScreener will fill in the data via the market data merge
        $explorerMap = $this->getExplorerDomainMap();
        $explorerDomain = $explorerMap[strtolower($chain)] ?? null;
        $explorerUrl = $explorerDomain ? "https://{$explorerDomain}/token/{$input}" : null;

        return [
            'chain' => $chain,
            'address' => $input,
            'name' => 'Unknown',
            'symbol' => 'N/A',
            'verified' => null, // null = unable to check (not false which implies checked and failed)
            'has_source_code' => null,
            'market_data_only' => true, // Flag: no contract verification available
            'explorer_url' => $explorerUrl,
        ];
    }

    /**
     * Get explorer domain mapping for all supported chains
     */
    private function getExplorerDomainMap(): array
    {
        return [
            'ethereum' => 'etherscan.io',
            'bsc' => 'bscscan.com',
            'polygon' => 'polygonscan.com',
            'arbitrum' => 'arbiscan.io',
            'optimism' => 'optimistic.etherscan.io',
            'avalanche' => 'snowtrace.io',
            'fantom' => 'ftmscan.com',
            'base' => 'basescan.org',
            'solana' => 'solscan.io',
            'ton' => 'tonscan.org',
            'cronos' => 'cronoscan.com',
            'gnosis' => 'gnosisscan.io',
            'celo' => 'celoscan.io',
            'moonbeam' => 'moonbeam.moonscan.io',
            'moonriver' => 'moonriver.moonscan.io',
            'zksync' => 'explorer.zksync.io',
            'linea' => 'lineascan.build',
            'mantle' => 'mantlescan.xyz',
            'scroll' => 'scrollscan.com',
            'pulsechain' => 'scan.pulsechain.com',
            'metis' => 'andromeda-explorer.metis.io',
            'harmony' => 'explorer.harmony.one',
            'kava' => 'kavascan.com',
            'aurora' => 'explorer.aurora.dev',
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
            $from = $tx['from'] ?? null;
            $value = (float) ($tx['value'] ?? 0);
            $decimals = (int) ($tx['tokenDecimal'] ?? 18);
            $adjustedValue = $decimals > 0 ? $value / pow(10, $decimals) : $value;

            // Track net balance: receiving adds, sending subtracts
            if ($to) {
                if (!isset($holders[$to])) {
                    $holders[$to] = ['address' => $to, 'balance' => 0, 'tx_count' => 0];
                }
                $holders[$to]['balance'] += $adjustedValue;
                $holders[$to]['tx_count']++;
            }
            if ($from) {
                if (!isset($holders[$from])) {
                    $holders[$from] = ['address' => $from, 'balance' => 0, 'tx_count' => 0];
                }
                $holders[$from]['balance'] -= $adjustedValue;
            }
        }

        // Filter out zero/negative balances and known contract addresses (burn, null)
        $holders = array_filter($holders, function ($h) {
            return $h['balance'] > 0 && !in_array(strtolower($h['address']), [
                '0x0000000000000000000000000000000000000000',
                '0x000000000000000000000000000000000000dead',
            ]);
        });

        // Sort by estimated balance (descending)
        usort($holders, fn($a, $b) => $b['balance'] <=> $a['balance']);

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

        // NEW: Check for token type and apply risk modifier
        $tokenType = $data['market_data']['token_type'] ?? null;
        $riskModifier = 0;

        Log::info('Risk calculation starting', [
            'has_market_data' => isset($data['market_data']),
            'has_token_type' => isset($data['market_data']['token_type']),
            'token_type' => $tokenType,
            'symbol' => $data['symbol'] ?? 'Unknown',
            'name' => $data['name'] ?? 'Unknown'
        ]);

        if ($tokenType) {
            $riskModifier = $tokenType['risk_modifier'] ?? 0;

            // For known assets, note the type but CONTINUE with full analysis
            if ($tokenType['is_known_asset'] ?? false) {
                $assetTypeName = ucfirst($tokenType['type'] ?? 'known');
                $breakdown[] = ['factor' => 'Known Asset Type: ' . $assetTypeName, 'points' => 0, 'impact' => 'positive'];
                $breakdown[] = ['factor' => 'Established Protocol', 'points' => -15, 'impact' => 'positive'];
                $score -= 15; // Bonus for being a known asset

                // For stablecoins, verify price is in range
                if ($tokenType['is_stablecoin'] ?? false) {
                    $priceValidation = $data['market_data']['price_validation'] ?? null;
                    if ($priceValidation && !$priceValidation['valid']) {
                        $score += 40;
                        $breakdown[] = ['factor' => 'Stablecoin De-Pegged', 'points' => 40, 'impact' => 'negative'];
                        $factors[] = 'Price deviation from $1.00';
                    } else {
                        $breakdown[] = ['factor' => 'Price Pegged to $1.00', 'points' => 0, 'impact' => 'positive'];
                    }
                }
            }
        }
        // NOTE: No early return — proceed to full contract + market analysis below

        // Check if this is market-data-only (no contract verification available)
        $marketDataOnly = $data['market_data_only'] ?? false;

        // Get security model for chain
        $chain = $data['chain'] ?? 'unknown';
        $detector = app(TokenTypeDetector::class);
        $securityModel = $detector->getSecurityModel($chain);

        // For market-data-only tokens, skip contract checks and score on market metrics only
        if ($marketDataOnly) {
            $breakdown[] = ['factor' => 'Contract verification not available', 'points' => 10, 'impact' => 'negative'];
            $score += 10; // Mild penalty for unverifiable contract
            $factors[] = 'No contract verification for this chain';

            // Score based on available market metrics
            $marketData = $data['market_data'] ?? [];
            $liquidity = $marketData['liquidity_usd'] ?? 0;
            $volume = $marketData['volume_24h'] ?? 0;
            $marketCap = $marketData['market_cap'] ?? 0;

            if ($liquidity > 0 && $liquidity < 10000) {
                $score += 20;
                $breakdown[] = ['factor' => 'Very Low Liquidity', 'points' => 20, 'impact' => 'negative'];
                $factors[] = 'Liquidity under $10K';
            } elseif ($liquidity > 100000) {
                $breakdown[] = ['factor' => 'Good Liquidity', 'points' => 0, 'impact' => 'positive'];
            }

            if ($volume > 0 && $volume < 1000) {
                $score += 10;
                $breakdown[] = ['factor' => 'Very Low Volume', 'points' => 10, 'impact' => 'negative'];
                $factors[] = '24h volume under $1K';
            }

            $finalScore = max(0, min(100, $score + $riskModifier));
            return [
                'total_score' => $finalScore,
                'breakdown' => $breakdown,
                'factors' => $factors
            ];
        }

        // CONTRACT VERIFICATION (±25 points) - EVM ONLY
        if ($securityModel === 'evm') {
            if (!($data['verified'] ?? false)) {
                $points = 25;
                $score += $points;
                $breakdown[] = ['factor' => 'Contract Not Verified', 'points' => $points, 'impact' => 'negative'];
                $factors[] = 'Unverified contract';
            } else {
                $breakdown[] = ['factor' => 'Contract Verified', 'points' => 0, 'impact' => 'positive'];
            }

            // SOURCE CODE AVAILABILITY (±20 points) - EVM ONLY
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

            // OWNERSHIP STATUS (±20 points) - EVM ONLY
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

            // PROXY/UPGRADEABLE (±15 points) - EVM ONLY
            if ($data['proxy'] ?? false) {
                $points = 15;
                $score += $points;
                $breakdown[] = ['factor' => 'Proxy/Upgradeable', 'points' => $points, 'impact' => 'negative'];
                $factors[] = 'Contract can be upgraded';
            } else {
                $breakdown[] = ['factor' => 'Not Proxy (Immutable)', 'points' => 0, 'impact' => 'positive'];
            }

            // MINTABLE (±15 points) - EVM ONLY
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
        } elseif ($securityModel === 'spl') {
            // SOLANA SPL TOKEN CHECKS
            // Check mint authority
            $mintAuth = $data['mint_authority'] ?? null;
            if ($mintAuth && !empty($mintAuth)) {
                $points = 10;
                $score += $points;
                $breakdown[] = ['factor' => 'Mint Authority Present', 'points' => $points, 'impact' => 'negative'];
                $factors[] = 'Supply can be increased';
            } else {
                $breakdown[] = ['factor' => 'Mint Authority Revoked', 'points' => 0, 'impact' => 'positive'];
            }
        } elseif ($securityModel === 'ton') {
            // TON JETTON CHECKS
            $adminAddress = $data['admin_address'] ?? $data['admin'] ?? null;
            if (!empty($adminAddress)) {
                $points = 15;
                $score += $points;
                $breakdown[] = ['factor' => 'Admin Address Active', 'points' => $points, 'impact' => 'negative'];
                $factors[] = 'Jetton admin can modify contract';
            } else {
                $breakdown[] = ['factor' => 'Admin Address Revoked', 'points' => 0, 'impact' => 'positive'];
            }

            if ($data['mintable'] ?? false) {
                $points = 10;
                $score += $points;
                $breakdown[] = ['factor' => 'Mintable Jetton', 'points' => $points, 'impact' => 'negative'];
                $factors[] = 'Supply can be increased';
            }
        }

        // HOLDER DISTRIBUTION (±20 points) - ALL CHAINS
        $holderDist = $data['holder_distribution'] ?? [];
        $top10Pct = $holderDist['top_10_percentage'] ?? 0;
        $holderCount = $data['holders_count'] ?? 0;

        if ($top10Pct > 70) {
            $points = 20;
            $score += $points;
            $breakdown[] = ['factor' => "Highly Concentrated (Top 10 own {$top10Pct}%)", 'points' => $points, 'impact' => 'negative'];
            $factors[] = "Top 10 holders own {$top10Pct}%";
        } elseif ($top10Pct > 50) {
            $points = 10;
            $score += $points;
            $breakdown[] = ['factor' => "Concentrated (Top 10 own {$top10Pct}%)", 'points' => $points, 'impact' => 'negative'];
            $factors[] = "Top 10 holders own {$top10Pct}%";
        } elseif ($top10Pct > 0) {
            $breakdown[] = ['factor' => "Good Distribution (Top 10 own {$top10Pct}%)", 'points' => 0, 'impact' => 'positive'];
        } else {
            $breakdown[] = ['factor' => 'Holder Distribution: No data', 'points' => 0, 'impact' => 'neutral'];
        }

        // LOW HOLDER COUNT (±10 points) - ALL CHAINS
        if ($holderCount > 0 && $holderCount < 50) {
            $points = 10;
            $score += $points;
            $breakdown[] = ['factor' => "Very Low Holders ({$holderCount})", 'points' => $points, 'impact' => 'negative'];
            $factors[] = "Only {$holderCount} holders";
        } elseif ($holderCount > 0 && $holderCount < 100) {
            $points = 5;
            $score += $points;
            $breakdown[] = ['factor' => "Low Holders ({$holderCount})", 'points' => $points, 'impact' => 'negative'];
            $factors[] = "{$holderCount} holders";
        } elseif ($holderCount >= 1000) {
            $breakdown[] = ['factor' => 'Strong Holder Base (' . number_format($holderCount) . ')', 'points' => 0, 'impact' => 'positive'];
        } elseif ($holderCount >= 100) {
            $breakdown[] = ['factor' => 'Moderate Holders (' . number_format($holderCount) . ')', 'points' => 0, 'impact' => 'neutral'];
        } else {
            $breakdown[] = ['factor' => 'Holder Count: No data', 'points' => 0, 'impact' => 'neutral'];
        }

        // LIQUIDITY ASSESSMENT (±15 points) - ALL CHAINS
        $liquidity = $data['market_data']['liquidity_usd'] ?? 0;
        if ($liquidity > 0) {
            if ($liquidity < 10000) {
                $points = 15;
                $score += $points;
                $breakdown[] = ['factor' => 'Very Low Liquidity (<$10K)', 'points' => $points, 'impact' => 'negative'];
                $factors[] = 'Liquidity under $10K';
            } elseif ($liquidity < 50000) {
                $points = 8;
                $score += $points;
                $breakdown[] = ['factor' => 'Low Liquidity (<$50K)', 'points' => $points, 'impact' => 'negative'];
            } elseif ($liquidity >= 500000) {
                $breakdown[] = ['factor' => 'Strong Liquidity ($' . number_format($liquidity / 1000000, 2) . 'M)', 'points' => 0, 'impact' => 'positive'];
            } else {
                $breakdown[] = ['factor' => 'Moderate Liquidity ($' . number_format($liquidity / 1000, 0) . 'K)', 'points' => 0, 'impact' => 'neutral'];
            }
        }

        // VOLUME ASSESSMENT (±10 points) - ALL CHAINS
        $volume24h = $data['market_data']['volume_24h'] ?? 0;
        if ($volume24h > 0) {
            if ($volume24h < 1000) {
                $points = 10;
                $score += $points;
                $breakdown[] = ['factor' => 'Very Low 24h Volume (<$1K)', 'points' => $points, 'impact' => 'negative'];
            } elseif ($volume24h >= 100000) {
                $breakdown[] = ['factor' => 'Healthy Trading Volume ($' . number_format($volume24h / 1000000, 2) . 'M)', 'points' => 0, 'impact' => 'positive'];
            }
        }

        // MARKET CAP ASSESSMENT - ALL CHAINS
        $marketCap = $data['market_data']['market_cap'] ?? 0;
        if ($marketCap > 0) {
            if ($marketCap >= 1000000000) {
                $breakdown[] = ['factor' => 'Large Cap ($' . number_format($marketCap / 1000000000, 2) . 'B)', 'points' => 0, 'impact' => 'positive'];
            } elseif ($marketCap >= 100000000) {
                $breakdown[] = ['factor' => 'Mid Cap ($' . number_format($marketCap / 1000000, 0) . 'M)', 'points' => 0, 'impact' => 'neutral'];
            } elseif ($marketCap < 1000000) {
                $points = 5;
                $score += $points;
                $breakdown[] = ['factor' => 'Micro Cap (<$1M)', 'points' => $points, 'impact' => 'negative'];
            }
        }

        // Apply token type risk modifier and show it in breakdown if significant
        $finalScore = max(0, min(100, $score + $riskModifier));

        // If risk_modifier pushed score below 0 (clamped to 0), show it so users understand
        if ($riskModifier < 0 && $score > 0) {
            $assetTypeName = ucfirst($tokenType['type'] ?? 'known');
            $breakdown[] = ['factor' => "Known {$assetTypeName} Bonus", 'points' => $riskModifier, 'impact' => 'positive'];
        }

        return [
            'total_score' => $finalScore,
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

        // Special context for known assets (stablecoins, wrapped tokens)
        $tokenType = $data['market_data']['token_type'] ?? null;
        if ($tokenType && ($tokenType['is_known_asset'] ?? false)) {
            if ($tokenType['is_stablecoin'] ?? false) {
                $symbol = $data['symbol'] ?? 'Unknown';
                return "🏦 **Stablecoin Detected**: {$symbol} is a stablecoin designed to maintain a stable value pegged to a fiat currency (typically USD). These are widely used in DeFi for trading, lending, and as a store of value.";
            } elseif ($tokenType['is_wrapped'] ?? false) {
                return "🔄 **Wrapped Asset**: This is a tokenized representation of another asset, allowing it to be used on this blockchain. Widely used in DeFi.";
            } elseif ($tokenType['is_liquid_staking'] ?? false) {
                return "💧 **Liquid Staking Derivative**: This token represents staked assets and accrues staking rewards while remaining liquid and tradeable.";
            } elseif ($tokenType['is_defi'] ?? false) {
                $symbol = $data['symbol'] ?? 'Unknown';
                $name = $data['name'] ?? 'DeFi Token';
                return "🏛️ **Established DeFi Protocol**: {$symbol} is the native token of {$name}, a well-known decentralized finance protocol with proven track record.";
            }
        }

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

        // Market data green flags
        $liquidity = $data['market_data']['liquidity_usd'] ?? 0;
        if ($liquidity >= 500000) {
            $flags[] = '✅ Strong liquidity ($' . number_format($liquidity / 1000000, 2) . 'M)';
        }

        $volume = $data['market_data']['volume_24h'] ?? 0;
        if ($volume >= 1000000) {
            $flags[] = '✅ Active trading ($' . number_format($volume / 1000000, 2) . 'M daily volume)';
        }

        $marketCap = $data['market_data']['market_cap'] ?? 0;
        if ($marketCap >= 100000000) {
            $flags[] = '✅ Established market cap ($' . number_format($marketCap / 1000000000, 2) . 'B)';
        }

        return $flags;
    }

    /**
     * Get red flags
     */
    private function getRedFlags(array $data): array
    {
        $flags = [];

        // Get chain and security model
        $chain = $data['chain'] ?? 'unknown';
        $detector = app(TokenTypeDetector::class);
        $securityModel = $detector->getSecurityModel($chain);

        // Check if this is a known asset
        $tokenType = $data['market_data']['token_type'] ?? null;
        $isKnownAsset = $tokenType['is_known_asset'] ?? false;

        // For known assets (stablecoins, wrapped), don't show generic red flags
        if ($isKnownAsset) {
            // Only check for price de-peg for stablecoins
            if ($tokenType['is_stablecoin'] ?? false) {
                $priceValidation = $data['market_data']['price_validation'] ?? null;
                if ($priceValidation && !$priceValidation['valid']) {
                    foreach ($priceValidation['warnings'] as $warning) {
                        $flags[] = '❌ ' . $warning;
                    }
                }
            }
            return $flags; // Skip other checks for known assets
        }

        // EVM-specific flags
        if ($securityModel === 'evm') {
            if (!($data['verified'] ?? false)) {
                $flags[] = '❌ Contract NOT verified - cannot audit code';
            }

            if ($data['mintable'] ?? false) {
                $flags[] = '❌ Minting ACTIVE - supply can be inflated';
            }

            // Ownership flags based on verified status
            $ownershipStatus = $data['ownership_status'] ?? $this->determineOwnershipStatus($data);
            if ($ownershipStatus === 'active_owner') {
                $adminAddr = substr($data['admin'] ?? 'Unknown', 0, 10) . '...';
                $flags[] = "❌ Active owner/admin ({$adminAddr}) - centralized control";
            } elseif ($ownershipStatus === 'unknown' && ($data['verified'] ?? false)) {
                $flags[] = '❌ Ownership status UNKNOWN';
            }

            if ($data['proxy'] ?? false) {
                $flags[] = '❌ Proxy contract detected - upgrade risk';
            }

            if (isset($data['has_source_code']) && !$data['has_source_code'] && ($data['verified'] ?? false)) {
                $flags[] = '❌ Source code not published';
            }
        }
        // SPL (Solana) specific flags
        elseif ($securityModel === 'spl') {
            $mintAuth = $data['mint_authority'] ?? null;
            if ($mintAuth && !empty($mintAuth)) {
                $flags[] = '❌ Mint authority active - supply can be inflated';
            }

            // Don't show "source code unavailable" for SPL tokens - they use Token Program
        }

        // Universal flags (all chains)
        $holderDist = $data['holder_distribution'] ?? [];
        if ($holderDist['concentrated'] ?? false) {
            $pct = $holderDist['top_10_percentage'] ?? 0;
            $flags[] = "❌ Highly concentrated - top 10 holders own {$pct}%";
        }

        $holderCount = $data['holders_count'] ?? 0;
        if ($holderCount > 0 && $holderCount < 50) {
            $flags[] = "❌ Very low holder count ({$holderCount}) - early/risky stage";
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
        // Trim and clean input
        $input = trim($input);

        Log::info('Chain detection started', ['input' => $input, 'length' => strlen($input)]);

        if (str_starts_with($input, 'EQ') || str_starts_with($input, 'UQ')) {
            Log::info('Detected TON chain');
            return 'ton';
        }

        if (str_starts_with($input, '0x')) {
            Log::info('Detected Ethereum chain (0x prefix)');
            return 'ethereum';
        }

        // Solana addresses are base58 encoded, typically 32-44 characters, no 0x prefix
        // Base58 alphabet: 123456789ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnopqrstuvwxyz
        $length = strlen($input);
        Log::info('Checking Solana pattern', ['length' => $length, 'in_range' => ($length >= 32 && $length <= 44)]);

        if ($length >= 32 && $length <= 44) {
            // Check if it's valid base58 (no 0, O, I, l characters)
            $isBase58 = preg_match('/^[1-9A-HJ-NP-Za-km-z]{32,44}$/', $input);
            Log::info('Base58 pattern match', ['result' => $isBase58]);

            if ($isBase58) {
                Log::info('Detected Solana address', ['address' => $input, 'length' => $length]);
                return 'solana';
            }
        }

        Log::warning('Chain detection failed - unknown chain', ['input' => $input]);
        return 'unknown';
    }

    /**
     * Normalize address format
     */
    private function normalizeAddress(string $input, string $chain): string
    {
        // Aggressively trim whitespace and newlines
        $input = trim($input);
        $input = preg_replace('/\s+/', '', $input);

        return $input;
    }
}
