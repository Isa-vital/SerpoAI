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

        // NEW: Get universal market data first (price, volume, liquidity)
        $marketData = $this->universalData->getTokenData($address, $chain);

        // Fetch blockchain-specific verification data
        $data = match ($chain) {
            'ton' => $this->verifyTonToken($address),
            'ethereum', 'eth' => $this->verifyEthereumToken($address),
            'bsc' => $this->verifyBscToken($address),
            'base' => $this->verifyBaseToken($address),
            'solana', 'sol' => $this->verifySolanaToken($address),
            'polygon' => $this->verifyPolygonToken($address),
            'arbitrum' => $this->verifyArbitrumToken($address),
            'optimism' => $this->verifyOptimismToken($address),
            'avalanche' => $this->verifyAvalancheToken($address),
            default => $this->getGenericTokenInfo($input, $chain)
        };

        // Merge market data with verification data (market data takes precedence for name/symbol if available)
        if ($marketData['found']) {
            // If verification didn't find name/symbol but market data has it, use market data
            if ((!isset($data['name']) || $data['name'] === 'Unknown') && !empty($marketData['data']['name'])) {
                $data['name'] = $marketData['data']['name'];
            }
            if ((!isset($data['symbol']) || $data['symbol'] === 'Unknown') && !empty($marketData['data']['symbol'])) {
                $data['symbol'] = $marketData['data']['symbol'];
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

            // For known assets (stablecoins, wrapped tokens), skip EVM-centric checks
            if ($tokenType['is_known_asset'] ?? false) {
                $breakdown[] = ['factor' => 'Known Asset Type: ' . ucfirst($tokenType['type']), 'points' => 0, 'impact' => 'positive'];

                // For stablecoins, verify price is in range
                if ($tokenType['is_stablecoin'] ?? false) {
                    Log::info('Stablecoin detected in risk calculation', [
                        'symbol' => $data['symbol'] ?? 'Unknown',
                        'price_validation' => $data['market_data']['price_validation'] ?? null
                    ]);

                    $priceValidation = $data['market_data']['price_validation'] ?? null;
                    if ($priceValidation && !$priceValidation['valid']) {
                        $score += 40; // Major red flag if stablecoin is de-pegged
                        $breakdown[] = ['factor' => 'Stablecoin De-Pegged', 'points' => 40, 'impact' => 'negative'];
                        $factors[] = 'Price deviation from $1.00';
                    } else {
                        // Stablecoin with normal peg = low risk
                        Log::info('Returning low risk for stablecoin', ['score' => max(0, 10 + $riskModifier)]);
                        return [
                            'total_score' => max(0, 10 + $riskModifier),
                            'breakdown' => [
                                ['factor' => 'Stablecoin (USDC)', 'points' => 0, 'impact' => 'positive'],
                                ['factor' => 'Price pegged to $1.00', 'points' => 0, 'impact' => 'positive'],
                                ['factor' => 'Widely trusted asset', 'points' => -30, 'impact' => 'positive']
                            ],
                            'factors' => []
                        ];
                    }
                } else {
                    // Wrapped asset or liquid staking = low risk
                    return [
                        'total_score' => max(0, 15 + $riskModifier),
                        'breakdown' => $breakdown,
                        'factors' => []
                    ];
                }
            }
        }

        // Get security model for chain
        $chain = $data['chain'] ?? 'unknown';
        $detector = app(TokenTypeDetector::class);
        $securityModel = $detector->getSecurityModel($chain);

        // CONTRACT VERIFICATION (±25 points) - EVM ONLY
        if ($securityModel === 'evm') {
            if (!($data['verified'] ?? false)) {
                $points = 25;
                $score += $points;
                $breakdown[] = ['factor' => 'Contract Not Verified', 'points' => $points, 'impact' => 'negative'];
                $factors[] = 'Unverified contract';
            } else {
                $breakdown[] = ['factor' => 'Contract Verified', 'points' => 0, 'impact' => 'neutral'];
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
                $breakdown[] = ['factor' => 'Not Proxy', 'points' => 0, 'impact' => 'neutral'];
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
        }

        // HOLDER DISTRIBUTION (±20 points) - ALL CHAINS
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

        // LOW HOLDER COUNT (±10 points) - ALL CHAINS
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

        // Apply token type risk modifier
        $finalScore = max(0, min(100, $score + $riskModifier));

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
