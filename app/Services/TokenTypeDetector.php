<?php

namespace App\Services;

/**
 * Token Type Detector
 * 
 * Detects token types (stablecoins, wrapped assets, governance, etc.)
 * and applies appropriate validation/sanitychecks
 */
class TokenTypeDetector
{
    // Known stablecoins by symbol
    private const STABLECOINS = [
        'USDC',
        'USDT',
        'DAI',
        'BUSD',
        'FRAX',
        'USDD',
        'TUSD',
        'USDP',
        'LUSD',
        'SUSD',
        'UST',
        'USTC',
        'USDR',
        'GUSD',
        'PYUSD'
    ];

    // Wrapped assets
    private const WRAPPED_ASSETS = [
        'WETH',
        'WBTC',
        'WBNB',
        'WMATIC',
        'WAVAX',
        'WFTM',
        'WSOL',
        'WTON'
    ];

    // Liquid staking derivatives (only actual LSD tokens, not base L1 assets)
    private const LIQUID_STAKING = [
        'stETH',
        'rETH',
        'cbETH',
        'wstETH',
        'sETH2',
        'ankrETH',
        'stMATIC',
        'maticX',
        'mSOL',
        'stSOL',
        'jitoSOL',
        'bSOL'
    ];

    // Major DeFi tokens (well-known, legitimate projects)
    private const DEFI_TOKENS = [
        'UNI',      // Uniswap
        'CAKE',     // PancakeSwap
        'AAVE',     // Aave
        'COMP',     // Compound
        'MKR',      // Maker
        'CRV',      // Curve
        'SNX',      // Synthetix
        'SUSHI',    // SushiSwap
        'BAL',      // Balancer
        'YFI',      // Yearn Finance
        'LDO',      // Lido
        'GMX',      // GMX
        'JUP',      // Jupiter
        'RAY',      // Raydium
        'ORCA',     // Orca
    ];

    /**
     * Detect token type and characteristics
     */
    public function detectTokenType(string $symbol, string $name, string $address, string $chain): array
    {
        $upperSymbol = strtoupper($symbol);
        $upperName = strtoupper($name);

        $type = 'unknown';
        $expectedPriceRange = null;
        $riskModifier = 0;
        $isKnownAsset = false;

        // Detect stablecoin
        if (
            in_array($upperSymbol, self::STABLECOINS) ||
            str_contains($upperName, 'DOLLAR') ||
            str_contains($upperName, 'USD')
        ) {
            $type = 'stablecoin';
            $expectedPriceRange = ['min' => 0.95, 'max' => 1.05];
            $riskModifier = -30; // Lower risk for stablecoins
            $isKnownAsset = true;
        }
        // Detect wrapped asset
        elseif (
            in_array($upperSymbol, self::WRAPPED_ASSETS) ||
            str_starts_with($upperSymbol, 'W')
        ) {
            $type = 'wrapped';
            $riskModifier = -20;
            $isKnownAsset = true;
        }
        // Detect liquid staking
        elseif (
            in_array($upperSymbol, self::LIQUID_STAKING) ||
            str_contains($upperName, 'STAKED') ||
            str_contains($upperName, 'LIQUID')
        ) {
            $type = 'liquid_staking';
            $riskModifier = -15;
            $isKnownAsset = true;
        }
        // Detect major DeFi tokens
        elseif (in_array($upperSymbol, self::DEFI_TOKENS)) {
            $type = 'defi';
            $riskModifier = -25; // Lower risk for established DeFi protocols
            $isKnownAsset = true;
        }
        // Detect governance token (common patterns)
        elseif (
            str_ends_with($upperSymbol, 'DAO') ||
            str_contains($upperName, 'GOVERNANCE')
        ) {
            $type = 'governance';
            $riskModifier = 0;
        }

        return [
            'type' => $type,
            'is_stablecoin' => $type === 'stablecoin',
            'is_wrapped' => $type === 'wrapped',
            'is_liquid_staking' => $type === 'liquid_staking',
            'is_defi' => $type === 'defi',
            'is_known_asset' => $isKnownAsset,
            'expected_price_range' => $expectedPriceRange,
            'risk_modifier' => $riskModifier,
            'symbol' => $symbol,
            'name' => $name,
        ];
    }

    /**
     * Validate price against expected range
     */
    public function validatePrice(float $price, array $tokenType): array
    {
        if (!isset($tokenType['expected_price_range'])) {
            return ['valid' => true, 'warnings' => []];
        }

        $range = $tokenType['expected_price_range'];
        $warnings = [];

        if ($price < $range['min'] || $price > $range['max']) {
            $warnings[] = "Price ${price} outside expected range (\${$range['min']}-\${$range['max']})";

            // For stablecoins, significant deviation is a major red flag
            if ($tokenType['is_stablecoin']) {
                if ($price < 0.90 || $price > 1.10) {
                    $warnings[] = "⚠️ CRITICAL: Stablecoin de-pegged! This may be a scam token.";
                }
            }
        }

        return [
            'valid' => empty($warnings),
            'warnings' => $warnings,
            'in_range' => $price >= $range['min'] && $price <= $range['max'],
        ];
    }

    /**
     * Check if symbol is a known stablecoin
     */
    public function isStablecoin(string $symbol): bool
    {
        return in_array(strtoupper($symbol), self::STABLECOINS);
    }

    /**
     * Get appropriate security model for chain
     */
    public function getSecurityModel(string $chain): string
    {
        $evmChains = ['ethereum', 'bsc', 'polygon', 'arbitrum', 'optimism', 'avalanche', 'fantom', 'base'];

        if (in_array(strtolower($chain), $evmChains)) {
            return 'evm'; // Source code verification, ownership, proxies
        } elseif (strtolower($chain) === 'solana') {
            return 'spl'; // Token program, mint/freeze authority
        } elseif (strtolower($chain) === 'ton') {
            return 'ton'; // Jetton standard
        }

        return 'generic';
    }
}
