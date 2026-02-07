<?php

namespace App\Services;

use App\Models\User;
use App\Models\UserWallet;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Portfolio Service
 * 
 * Manages user wallet tracking and portfolio calculations
 */
class PortfolioService
{
    private MarketDataService $marketData;

    public function __construct(MarketDataService $marketData)
    {
        $this->marketData = $marketData;
    }

    /**
     * Add a wallet address for a user
     */
    public function addWallet(User $user, string $walletAddress, ?string $label = null): UserWallet
    {
        // Validate TON wallet address format (basic validation)
        if (!$this->isValidTonAddress($walletAddress)) {
            throw new \InvalidArgumentException('Invalid TON wallet address format');
        }

        // Create or update wallet
        $wallet = UserWallet::updateOrCreate(
            [
                'user_id' => $user->id,
                'wallet_address' => $walletAddress,
            ],
            [
                'label' => $label,
            ]
        );

        // Sync balance immediately
        $this->syncWalletBalance($wallet);

        return $wallet;
    }

    /**
     * Remove a wallet for a user
     */
    public function removeWallet(User $user, string $walletAddress): bool
    {
        return UserWallet::where('user_id', $user->id)
            ->where('wallet_address', $walletAddress)
            ->delete() > 0;
    }

    /**
     * Get all wallets for a user
     */
    public function getUserWallets(User $user)
    {
        return UserWallet::where('user_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Sync wallet balance from blockchain
     */
    public function syncWalletBalance(UserWallet $wallet): void
    {
        try {
            $balance = $this->fetchWalletBalance($wallet->wallet_address);
            $priceData = $this->marketData->getTokenPriceFromDex();

            $usdValue = 0;
            if (isset($priceData['price'])) {
                $usdValue = $balance * $priceData['price'];
            }

            $wallet->update([
                'balance' => $balance,
                'usd_value' => $usdValue,
                'last_synced_at' => now(),
            ]);

            Log::info('Wallet synced', [
                'wallet_id' => $wallet->id,
                'address' => $wallet->wallet_address,
                'balance' => $balance,
                'usd_value' => $usdValue,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to sync wallet balance', [
                'wallet_id' => $wallet->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Fetch token balance for a wallet address from TON blockchain
     */
    private function fetchWalletBalance(string $walletAddress): float
    {
        try {
            $contractAddress = config('services.serpo.contract_address', env('TOKEN_CONTRACT_ADDRESS'));

            // Option 1: Use TonAPI (tonapi.io)
            $apiKey = config('services.ton.api_key');
            if ($apiKey) {
                $response = Http::timeout(10)->withHeaders([
                    'Authorization' => "Bearer {$apiKey}",
                ])->get("https://tonapi.io/v2/accounts/{$walletAddress}/jettons");

                if ($response->successful()) {
                    $jettons = $response->json('balances', []);

                    // Normalize contract address for comparison
                    // TonAPI returns hex format (0:xxx), config has base64 (EQxxx)
                    $normalizedContract = $this->normalizeAddress($contractAddress);

                    foreach ($jettons as $jetton) {
                        $jettonAddress = $jetton['jetton']['address'] ?? '';
                        $normalizedJetton = $this->normalizeAddress($jettonAddress);

                        // Compare both formats
                        if (
                            $normalizedJetton === $normalizedContract ||
                            $jettonAddress === $contractAddress ||
                            $this->addressesMatch($jettonAddress, $contractAddress)
                        ) {
                            $balance = $jetton['balance'] ?? 0;
                            $decimals = $jetton['jetton']['decimals'] ?? 9;

                            Log::info('Token balance found', [
                                'wallet' => $walletAddress,
                                'balance_raw' => $balance,
                                'balance' => $balance / pow(10, $decimals),
                                'decimals' => $decimals,
                            ]);

                            return $balance / pow(10, $decimals);
                        }
                    }

                    Log::warning('Token not found in wallet', [
                        'wallet' => $walletAddress,
                        'jettons_count' => count($jettons),
                        'looking_for' => $contractAddress,
                    ]);
                }
            }

            // Option 2: Use TON Center API (fallback)
            $response = Http::timeout(10)->get("https://toncenter.com/api/v2/getTokenData", [
                'address' => $contractAddress,
            ]);

            if ($response->successful()) {
                $data = $response->json();
                // Parse balance from response
                // This is a simplified example - actual implementation depends on TON API structure
                return floatval($data['balance'] ?? 0);
            }

            // Return 0 if APIs fail
            Log::warning('Could not fetch wallet balance - APIs unavailable', [
                'address' => $walletAddress,
            ]);
            return 0;
        } catch (\Exception $e) {
            Log::error('Error fetching wallet balance', [
                'address' => $walletAddress,
                'error' => $e->getMessage(),
            ]);
            return 0;
        }
    }

    /**
     * Calculate total portfolio value for a user
     */
    public function calculatePortfolioValue(User $user): array
    {
        $wallets = $this->getUserWallets($user);

        $totalBalance = 0;
        $totalUsdValue = 0;

        foreach ($wallets as $wallet) {
            // Sync if not recently synced (older than 5 minutes)
            if (!$wallet->last_synced_at || $wallet->last_synced_at->lt(now()->subMinutes(5))) {
                $this->syncWalletBalance($wallet);
                $wallet->refresh();
            }

            $totalBalance += $wallet->balance;
            $totalUsdValue += $wallet->usd_value;
        }

        $priceData = $this->marketData->getTokenPriceFromDex();

        return [
            'total_balance' => $totalBalance,
            'total_usd_value' => $totalUsdValue,
            'wallet_count' => $wallets->count(),
            'wallets' => $wallets,
            'current_price' => $priceData['price'] ?? 0,
            'price_change_24h' => $priceData['price_change_24h'] ?? 0,
        ];
    }

    /**
     * Validate TON wallet address format
     */
    private function isValidTonAddress(string $address): bool
    {
        // TON addresses are typically 48 characters in base64url format
        // Example: EQCPeUzKknneMlA1UbivELxd8lFUA_oaOX9m9PPc4d6lHQyw

        // Basic validation: check length and allowed characters
        if (strlen($address) < 40 || strlen($address) > 55) {
            return false;
        }

        // Check if it matches TON address pattern (base64url)
        return preg_match('/^[A-Za-z0-9_-]+$/', $address) === 1;
    }

    /**
     * Normalize TON address for comparison
     * Converts between hex (0:xxx) and base64 (EQxxx) formats
     */
    private function normalizeAddress(string $address): string
    {
        // Remove any whitespace
        $address = trim($address);

        // If it's hex format (0:xxx), keep as is
        if (str_starts_with($address, '0:')) {
            return strtolower($address);
        }

        // If it's base64 format, try to extract the hex part
        // For now, just return lowercase for comparison
        return strtolower($address);
    }

    /**
     * Check if two TON addresses match (handles different formats)
     */
    private function addressesMatch(string $addr1, string $addr2): bool
    {
        // Direct match
        if ($addr1 === $addr2) {
            return true;
        }

        // Case-insensitive match
        if (strtolower($addr1) === strtolower($addr2)) {
            return true;
        }

        // Convert hex to base64 and compare
        // Format: 0:8f794cca9279de32503552b8af10bc5df2515403fa1a397f66f4f3dce1dea51d
        // Should match: EQCPeUzKknneMlA1UbivELxd8lFUA_oaOX9m9PPc4d6lHQyw

        if (str_starts_with($addr1, '0:') && !str_starts_with($addr2, '0:')) {
            // addr1 is hex, addr2 is base64 - extract hex from addr1
            $hex1 = substr($addr1, 2);
            return $this->hexMatchesBase64($hex1, $addr2);
        }

        if (str_starts_with($addr2, '0:') && !str_starts_with($addr1, '0:')) {
            // addr2 is hex, addr1 is base64
            $hex2 = substr($addr2, 2);
            return $this->hexMatchesBase64($hex2, $addr1);
        }

        return false;
    }

    /**
     * Check if hex address matches base64 address
     */
    private function hexMatchesBase64(string $hex, string $base64): bool
    {
        // This is a simplified check - proper implementation would need
        // full TON address encoding/decoding library

        // For now, we'll use a partial match on the hex representation
        // Base64 decode and check if hex appears in it
        try {
            // Remove EQ prefix if present
            $cleanBase64 = preg_replace('/^[A-Z]{2}/', '', $base64);
            $decoded = base64_decode(str_replace(['-', '_'], ['+', '/'], $cleanBase64));

            if ($decoded !== false) {
                $decodedHex = bin2hex($decoded);
                // Check if hex is contained in the decoded representation
                return str_contains(strtolower($decodedHex), strtolower($hex));
            }
        } catch (\Exception $e) {
            Log::debug('Address comparison failed', [
                'hex' => $hex,
                'base64' => $base64,
                'error' => $e->getMessage(),
            ]);
        }

        return false;
    }

    /**
     * Format portfolio message for Telegram
     */
    public function formatPortfolioMessage(array $portfolioData): string
    {
        $message = "💼 *Your Token Portfolio*\n\n";

        if ($portfolioData['wallet_count'] === 0) {
            $message .= "❌ No wallets added yet\n\n";
            $message .= "Add a wallet with:\n";
            $message .= "`/addwallet <address>`\n\n";
            $message .= "Example:\n";
            $message .= "`/addwallet EQCPeUzKknneMlA1UbivELxd8lFUA_oaOX9m9PPc4d6lHQyw`";
            return $message;
        }

        // Overall portfolio stats
        $message .= "📊 *Total Holdings*\n";
        $message .= "• Balance: `" . number_format($portfolioData['total_balance'], 2) . " tokens`\n";
        $message .= "• Value: `$" . number_format($portfolioData['total_usd_value'], 2) . "`\n";
        $message .= "• Price: `$" . number_format($portfolioData['current_price'], 6) . "`\n";

        $priceChange = $portfolioData['price_change_24h'];
        $changeEmoji = $priceChange >= 0 ? '🟢' : '🔴';
        $changeSign = $priceChange >= 0 ? '+' : '';
        $message .= "• 24h Change: {$changeEmoji} `{$changeSign}" . number_format($priceChange, 2) . "%`\n\n";

        // Individual wallets
        $message .= "💳 *Your Wallets* ({$portfolioData['wallet_count']})\n\n";

        foreach ($portfolioData['wallets'] as $index => $wallet) {
            $walletNum = $index + 1;
            $label = $wallet->label ? " ({$wallet->label})" : "";

            $message .= "*Wallet {$walletNum}*{$label}\n";
            $message .= "• Address: `{$wallet->short_address}`\n";
            $message .= "• Balance: `" . number_format($wallet->balance, 2) . " tokens`\n";
            $message .= "• Value: `$" . number_format($wallet->usd_value, 2) . "`\n";

            if ($wallet->last_synced_at) {
                $message .= "• Updated: " . $wallet->last_synced_at->diffForHumans() . "\n";
            }

            $message .= "\n";
        }

        $message .= "➕ Add wallet: `/addwallet <address>`\n";
        $message .= "➖ Remove wallet: `/removewallet <address>`\n";
        $message .= "🔄 Refresh: `/portfolio`";

        return $message;
    }
}
