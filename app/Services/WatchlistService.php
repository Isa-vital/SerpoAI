<?php

namespace App\Services;

use App\Models\User;
use App\Models\WatchlistItem;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Collection;

/**
 * Watchlist Service
 *
 * Manages user watchlists across crypto, stocks, and forex markets.
 * Uses MultiMarketDataService for universal price lookups.
 */
class WatchlistService
{
    private MultiMarketDataService $multiMarket;

    private const MAX_WATCHLIST_ITEMS = 25;

    public function __construct(MultiMarketDataService $multiMarket)
    {
        $this->multiMarket = $multiMarket;
    }

    /**
     * Add a symbol to the user's watchlist
     */
    public function addSymbol(User $user, string $symbol, ?string $label = null): WatchlistItem
    {
        $symbol = strtoupper(trim($symbol));

        // Check watchlist limit
        $count = WatchlistItem::forUser($user->id)->count();
        if ($count >= self::MAX_WATCHLIST_ITEMS) {
            throw new \OverflowException("Watchlist limit reached ({$count}/" . self::MAX_WATCHLIST_ITEMS . "). Remove an item first.");
        }

        // Detect market type
        $marketType = $this->multiMarket->detectMarketType($symbol);

        // Verify we can get a price for it
        $price = $this->multiMarket->getCurrentPrice($symbol);

        // Create or update
        $item = WatchlistItem::updateOrCreate(
            [
                'user_id' => $user->id,
                'symbol' => $symbol,
            ],
            [
                'market_type' => $marketType,
                'label' => $label,
                'last_price' => $price,
                'last_checked_at' => now(),
            ]
        );

        // Try to get 24h change
        $this->refreshItemPrice($item);

        return $item;
    }

    /**
     * Remove a symbol from watchlist
     */
    public function removeSymbol(User $user, string $symbol): bool
    {
        $symbol = strtoupper(trim($symbol));

        return WatchlistItem::where('user_id', $user->id)
            ->where('symbol', $symbol)
            ->delete() > 0;
    }

    /**
     * Get the user's full watchlist with refreshed prices
     */
    public function getWatchlist(User $user, bool $refresh = true): Collection
    {
        $items = WatchlistItem::forUser($user->id)
            ->orderBy('market_type')
            ->orderBy('symbol')
            ->get();

        if ($refresh && $items->isNotEmpty()) {
            /** @var WatchlistItem $item */
            foreach ($items as $item) {
                // Only refresh if stale (>2 min)
                if (!$item->last_checked_at || $item->last_checked_at->lt(now()->subMinutes(2))) {
                    $this->refreshItemPrice($item);
                }
            }
        }

        return $items;
    }

    /**
     * Set a price alert on a watchlist item
     */
    public function setAlert(User $user, string $symbol, ?float $above = null, ?float $below = null): WatchlistItem
    {
        $symbol = strtoupper(trim($symbol));

        $item = WatchlistItem::where('user_id', $user->id)
            ->where('symbol', $symbol)
            ->first();

        if (!$item) {
            throw new \InvalidArgumentException("Symbol {$symbol} is not in your watchlist. Add it first with /watch {$symbol}");
        }

        $item->update([
            'alert_above' => $above,
            'alert_below' => $below,
        ]);

        return $item->fresh();
    }

    /**
     * Refresh price for a single watchlist item
     */
    private function refreshItemPrice(WatchlistItem $item): void
    {
        try {
            $priceData = $this->multiMarket->getUniversalPriceData($item->symbol);

            if (!isset($priceData['error'])) {
                $item->update([
                    'last_price' => $priceData['price'] ?? $item->last_price,
                    'price_change_24h' => $priceData['change_24h'] ?? null,
                    'last_checked_at' => now(),
                ]);
            }
        } catch (\Exception $e) {
            Log::debug("Failed to refresh watchlist price for {$item->symbol}", [
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Format watchlist for Telegram display
     */
    public function formatWatchlistMessage(Collection $items): string
    {
        if ($items->isEmpty()) {
            $message = "👀 *Your Watchlist*\n\n";
            $message .= "❌ No items in your watchlist yet\n\n";
            $message .= "*Quick Start:*\n";
            $message .= "• `/watch BTC` — Add Bitcoin\n";
            $message .= "• `/watch AAPL` — Add Apple stock\n";
            $message .= "• `/watch EURUSD` — Add EUR/USD forex\n";
            return $message;
        }

        $message = "👀 *Your Watchlist* ({$items->count()}/" . self::MAX_WATCHLIST_ITEMS . ")\n\n";

        // Group by market type
        $grouped = $items->groupBy('market_type');

        $sectionHeaders = [
            'crypto' => '₿ *Crypto*',
            'stock' => '📈 *Stocks*',
            'forex' => '💱 *Forex*',
        ];

        foreach ($sectionHeaders as $type => $header) {
            if (!$grouped->has($type)) continue;

            $message .= "{$header}\n";

            foreach ($grouped[$type] as $item) {
                $priceStr = $item->last_price !== null
                    ? $this->formatPrice($item->last_price, $type)
                    : 'N/A';

                $changeStr = $item->formatted_change;
                $labelStr = $item->label ? " _{$item->label}_" : '';

                $message .= "  `{$item->symbol}` — {$priceStr} {$changeStr}{$labelStr}\n";

                // Show alerts if set
                if ($item->alert_above || $item->alert_below) {
                    $alertParts = [];
                    if ($item->alert_above) $alertParts[] = "↑\${$item->alert_above}";
                    if ($item->alert_below) $alertParts[] = "↓\${$item->alert_below}";
                    $message .= "    🔔 " . implode(' | ', $alertParts) . "\n";
                }
            }
            $message .= "\n";
        }

        $message .= "━━━━━━━━━━━━━━━━\n";
        $message .= "➕ `/watch [symbol]` — Add\n";
        $message .= "➖ `/unwatch [symbol]` — Remove\n";
        $message .= "🔄 `/watchlist` — Refresh prices";

        return $message;
    }

    /**
     * Format price based on market type
     */
    private function formatPrice(float $price, string $marketType): string
    {
        if ($price >= 1000) {
            return '$' . number_format($price, 2);
        } elseif ($price >= 1) {
            return '$' . number_format($price, 4);
        } elseif ($price >= 0.01) {
            return '$' . number_format($price, 6);
        } else {
            return '$' . number_format($price, 8);
        }
    }
}
