<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Asset Type Detector
 * 
 * Detects blockchain asset types (ERC-20, ERC-721, ERC-1155, native assets, etc.)
 */
class AssetTypeDetector
{
    /**
     * Detect asset type from address
     * 
     * @param string $address Address to check
     * @param string $chain Blockchain (ethereum, bsc, ton, etc.)
     * @return array ['type' => string, 'is_contract' => bool, 'is_native' => bool, 'standards' => array, 'confidence' => string]
     */
    public function detectAssetType(string $address, string $chain = 'ethereum'): array
    {
        $address = trim($address);

        // Check if it's a native asset symbol (BTC, ETH, BNB, etc.)
        if ($this->isNativeAssetSymbol($address)) {
            return [
                'type' => 'Native Asset',
                'asset_name' => $this->getNativeAssetName($address),
                'is_contract' => false,
                'is_native' => true,
                'standards' => [],
                'confidence' => 'High',
                'error' => "Cannot verify {$address} - this is a native blockchain asset, not a smart contract token."
            ];
        }

        // Check if it's a valid EVM address format
        if (!$this->isValidEvmAddress($address)) {
            return [
                'type' => 'Unknown',
                'is_contract' => false,
                'is_native' => false,
                'standards' => [],
                'confidence' => 'None',
                'error' => 'Invalid address format'
            ];
        }

        // Check if address has contract code (is a smart contract)
        $isContract = $this->checkIsContract($address, $chain);

        if (!$isContract) {
            return [
                'type' => 'EOA (Wallet Address)',
                'is_contract' => false,
                'is_native' => false,
                'standards' => [],
                'confidence' => 'High',
                'error' => 'This is a wallet address, not a token contract'
            ];
        }

        // Detect token standards
        $standards = $this->detectTokenStandards($address, $chain);

        // Determine primary type
        $type = $this->determinePrimaryType($standards);

        return [
            'type' => $type,
            'is_contract' => true,
            'is_native' => false,
            'standards' => $standards,
            'confidence' => !empty($standards) ? 'High' : 'Medium',
        ];
    }

    /**
     * Check if string is a native asset symbol
     */
    private function isNativeAssetSymbol(string $input): bool
    {
        $nativeAssets = [
            'BTC',
            'BITCOIN',
            'ETH',
            'ETHEREUM',
            'BNB',
            'BINANCE',
            'SOL',
            'SOLANA',
            'ADA',
            'CARDANO',
            'AVAX',
            'AVALANCHE',
            'MATIC',
            'POLYGON',
            'DOT',
            'POLKADOT',
            'XRP',
            'RIPPLE',
        ];

        return in_array(strtoupper($input), $nativeAssets);
    }

    /**
     * Get native asset full name
     */
    private function getNativeAssetName(string $symbol): string
    {
        $names = [
            'BTC' => 'Bitcoin',
            'BITCOIN' => 'Bitcoin',
            'ETH' => 'Ethereum',
            'ETHEREUM' => 'Ethereum',
            'BNB' => 'Binance Coin',
            'BINANCE' => 'Binance Coin',
            'SOL' => 'Solana',
            'SOLANA' => 'Solana',
            'ADA' => 'Cardano',
            'CARDANO' => 'Cardano',
            'AVAX' => 'Avalanche',
            'AVALANCHE' => 'Avalanche',
            'MATIC' => 'Polygon',
            'POLYGON' => 'Polygon',
            'DOT' => 'Polkadot',
            'POLKADOT' => 'Polkadot',
            'XRP' => 'Ripple',
            'RIPPLE' => 'Ripple',
        ];

        return $names[strtoupper($symbol)] ?? strtoupper($symbol);
    }

    /**
     * Validate EVM address format
     */
    private function isValidEvmAddress(string $address): bool
    {
        // EVM addresses are 42 characters (0x + 40 hex chars)
        if (strlen($address) !== 42) {
            return false;
        }

        if (!str_starts_with($address, '0x')) {
            return false;
        }

        // Check if remaining 40 chars are valid hex
        $hex = substr($address, 2);
        return ctype_xdigit($hex);
    }

    /**
     * Check if address is a contract (has bytecode)
     */
    private function checkIsContract(string $address, string $chain): bool
    {
        try {
            $explorerUrl = $this->getExplorerApiUrl($chain);

            $response = Http::timeout(5)->get($explorerUrl, [
                'module' => 'proxy',
                'action' => 'eth_getCode',
                'address' => $address,
                'tag' => 'latest',
            ]);

            if ($response->successful()) {
                $data = $response->json();
                $code = $data['result'] ?? '0x';

                // Contract has code if result is not '0x'
                return $code !== '0x' && strlen($code) > 2;
            }
        } catch (\Exception $e) {
            Log::debug('Contract check failed', ['address' => $address, 'error' => $e->getMessage()]);
        }

        // Fallback: Assume it's a contract if we can't verify
        return true;
    }

    /**
     * Detect which token standards the contract implements
     */
    private function detectTokenStandards(string $address, string $chain): array
    {
        $standards = [];

        // Check for ERC-20 interface
        if ($this->hasERC20Interface($address, $chain)) {
            $standards[] = 'ERC-20';
        }

        // Check for ERC-721 interface
        if ($this->hasERC721Interface($address, $chain)) {
            $standards[] = 'ERC-721';
        }

        // Check for ERC-1155 interface
        if ($this->hasERC1155Interface($address, $chain)) {
            $standards[] = 'ERC-1155';
        }

        return $standards;
    }

    /**
     * Check if contract has ERC-20 interface
     */
    private function hasERC20Interface(string $address, string $chain): bool
    {
        // ERC-20 requires: totalSupply(), balanceOf(address), transfer(address,uint256)
        $requiredFunctions = [
            '0x18160ddd', // totalSupply()
            '0x70a08231', // balanceOf(address)
            '0xa9059cbb', // transfer(address,uint256)
        ];

        return $this->checkFunctionSelectors($address, $chain, $requiredFunctions);
    }

    /**
     * Check if contract has ERC-721 interface
     */
    private function hasERC721Interface(string $address, string $chain): bool
    {
        // ERC-721 requires: ownerOf(uint256), safeTransferFrom(address,address,uint256)
        $requiredFunctions = [
            '0x6352211e', // ownerOf(uint256)
            '0x42842e0e', // safeTransferFrom(address,address,uint256)
        ];

        return $this->checkFunctionSelectors($address, $chain, $requiredFunctions);
    }

    /**
     * Check if contract has ERC-1155 interface
     */
    private function hasERC1155Interface(string $address, string $chain): bool
    {
        // ERC-1155 requires: balanceOf(address,uint256), safeBatchTransferFrom
        $requiredFunctions = [
            '0x00fdd58e', // balanceOf(address,uint256)
            '0x2eb2c2d6', // safeBatchTransferFrom
        ];

        return $this->checkFunctionSelectors($address, $chain, $requiredFunctions);
    }

    /**
     * Check if contract has specific function selectors
     */
    private function checkFunctionSelectors(string $address, string $chain, array $selectors): bool
    {
        try {
            $explorerUrl = $this->getExplorerApiUrl($chain);

            $response = Http::timeout(5)->get($explorerUrl, [
                'module' => 'proxy',
                'action' => 'eth_getCode',
                'address' => $address,
                'tag' => 'latest',
            ]);

            if ($response->successful()) {
                $data = $response->json();
                $code = $data['result'] ?? '0x';

                // Check if all required function selectors are in the bytecode
                $foundCount = 0;
                foreach ($selectors as $selector) {
                    // Remove 0x prefix and search in bytecode
                    $selectorHex = str_replace('0x', '', $selector);
                    if (str_contains($code, $selectorHex)) {
                        $foundCount++;
                    }
                }

                // Consider it valid if we find at least 2 out of required functions
                return $foundCount >= min(2, count($selectors));
            }
        } catch (\Exception $e) {
            Log::debug('Function selector check failed', ['address' => $address, 'error' => $e->getMessage()]);
        }

        return false;
    }

    /**
     * Determine primary asset type from detected standards
     */
    private function determinePrimaryType(array $standards): string
    {
        if (empty($standards)) {
            return 'Smart Contract (Unknown Type)';
        }

        // Multi-standard contracts
        if (count($standards) > 1) {
            return implode(' + ', $standards) . ' Token';
        }

        // Single standard
        return $standards[0] . ' Token';
    }

    /**
     * Get explorer API URL for chain
     */
    private function getExplorerApiUrl(string $chain): string
    {
        return match (strtolower($chain)) {
            'ethereum', 'eth' => 'https://api.etherscan.io/api',
            'bsc', 'binance' => 'https://api.bscscan.com/api',
            'polygon', 'matic' => 'https://api.polygonscan.com/api',
            'arbitrum' => 'https://api.arbiscan.io/api',
            'optimism' => 'https://api-optimistic.etherscan.io/api',
            'base' => 'https://api.basescan.org/api',
            default => 'https://api.etherscan.io/api',
        };
    }
}
