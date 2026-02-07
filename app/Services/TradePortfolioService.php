<?php

namespace App\Services;

use App\Models\User;
use App\Models\PortfolioPosition;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Collection;

/**
 * Trade Portfolio Service
 *
 * Manages paper trading positions across crypto, stocks, and forex.
 * Supports long/short, PnL tracking, and position history.
 */
class TradePortfolioService
{
    private MultiMarketDataService $multiMarket;

    private const MAX_OPEN_POSITIONS = 20;

    public function __construct(MultiMarketDataService $multiMarket)
    {
        $this->multiMarket = $multiMarket;
    }

    /**
     * Open a new paper trade position (buy/long)
     */
    public function openPosition(
        User $user,
        string $symbol,
        float $quantity,
        string $side = 'long',
        ?string $notes = null
    ): PortfolioPosition {
        $symbol = strtoupper(trim($symbol));
        $side = strtolower(trim($side));

        if (!in_array($side, ['long', 'short'])) {
            throw new \InvalidArgumentException("Side must be 'long' or 'short'");
        }

        // Check position limit
        $openCount = PortfolioPosition::openForUser($user->id)->count();
        if ($openCount >= self::MAX_OPEN_POSITIONS) {
            throw new \OverflowException("Maximum open positions reached ({$openCount}/" . self::MAX_OPEN_POSITIONS . "). Close a position first.");
        }

        // Get current price
        $price = $this->multiMarket->getCurrentPrice($symbol);
        if (!$price) {
            throw new \RuntimeException("Unable to fetch price for {$symbol}. Check the symbol is valid.");
        }

        // Detect market type
        $marketType = $this->multiMarket->detectMarketType($symbol);

        // Check if user already has an open position for this symbol+side
        $existing = PortfolioPosition::where('user_id', $user->id)
            ->where('symbol', $symbol)
            ->where('side', $side)
            ->where('status', 'open')
            ->first();

        if ($existing) {
            // Average into existing position
            $totalCost = ($existing->entry_price * $existing->quantity) + ($price * $quantity);
            $totalQty = $existing->quantity + $quantity;
            $avgPrice = $totalQty > 0 ? $totalCost / $totalQty : $price;

            $existing->update([
                'quantity' => $totalQty,
                'entry_price' => $avgPrice,
            ]);

            // Recalculate PnL
            $existing->calculatePnL($price);
            $existing->save();

            return $existing->fresh();
        }

        // Create new position
        $position = PortfolioPosition::create([
            'user_id' => $user->id,
            'symbol' => $symbol,
            'market_type' => $marketType,
            'side' => $side,
            'quantity' => $quantity,
            'entry_price' => $price,
            'current_price' => $price,
            'unrealized_pnl' => 0,
            'unrealized_pnl_pct' => 0,
            'status' => 'open',
            'opened_at' => now(),
            'notes' => $notes,
        ]);

        Log::info('Paper trade opened', [
            'user_id' => $user->id,
            'symbol' => $symbol,
            'side' => $side,
            'quantity' => $quantity,
            'entry_price' => $price,
        ]);

        return $position;
    }

    /**
     * Close a position (sell)
     */
    public function closePosition(User $user, string $symbol, ?string $side = null): PortfolioPosition
    {
        $symbol = strtoupper(trim($symbol));

        $query = PortfolioPosition::where('user_id', $user->id)
            ->where('symbol', $symbol)
            ->where('status', 'open');

        if ($side) {
            $query->where('side', strtolower($side));
        }

        $position = $query->first();

        if (!$position) {
            throw new \InvalidArgumentException("No open position found for {$symbol}");
        }

        // Get current price
        $price = $this->multiMarket->getCurrentPrice($symbol);
        if (!$price) {
            throw new \RuntimeException("Unable to fetch exit price for {$symbol}");
        }

        $position->closePosition($price);
        $position->save();

        Log::info('Paper trade closed', [
            'user_id' => $user->id,
            'symbol' => $symbol,
            'side' => $position->side,
            'entry_price' => $position->entry_price,
            'exit_price' => $price,
            'realized_pnl' => $position->realized_pnl,
        ]);

        return $position->fresh();
    }

    /**
     * Partially close a position
     */
    public function closePartial(User $user, string $symbol, float $quantity, ?string $side = null): PortfolioPosition
    {
        $symbol = strtoupper(trim($symbol));

        $query = PortfolioPosition::where('user_id', $user->id)
            ->where('symbol', $symbol)
            ->where('status', 'open');

        if ($side) {
            $query->where('side', strtolower($side));
        }

        $position = $query->first();

        if (!$position) {
            throw new \InvalidArgumentException("No open position found for {$symbol}");
        }

        if ($quantity >= $position->quantity) {
            return $this->closePosition($user, $symbol, $side);
        }

        // Get current price
        $price = $this->multiMarket->getCurrentPrice($symbol);
        if (!$price) {
            throw new \RuntimeException("Unable to fetch price for {$symbol}");
        }

        // Calculate realized PnL on the closed portion
        $closedPnl = $position->side === 'long'
            ? ($price - $position->entry_price) * $quantity
            : ($position->entry_price - $price) * $quantity;

        // Reduce quantity
        $position->quantity -= $quantity;
        $position->calculatePnL($price);
        $position->save();

        return $position->fresh();
    }

    /**
     * Get all open positions with refreshed prices
     */
    public function getOpenPositions(User $user): Collection
    {
        $positions = PortfolioPosition::openForUser($user->id)
            ->orderBy('opened_at', 'desc')
            ->get();

        // Refresh prices
        /** @var PortfolioPosition $position */
        foreach ($positions as $position) {
            try {
                $price = $this->multiMarket->getCurrentPrice($position->symbol);
                if ($price) {
                    $position->calculatePnL($price);
                    $position->save();
                }
            } catch (\Exception $e) {
                Log::debug("Failed to refresh position price for {$position->symbol}");
            }
        }

        return $positions;
    }

    /**
     * Get closed positions (trade history)
     */
    public function getClosedPositions(User $user, int $limit = 10): Collection
    {
        return PortfolioPosition::closedForUser($user->id)
            ->orderBy('closed_at', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Get portfolio summary (total PnL across all positions)
     */
    public function getPortfolioSummary(User $user): array
    {
        $openPositions = $this->getOpenPositions($user);
        $closedPositions = PortfolioPosition::closedForUser($user->id)->get();

        $totalUnrealizedPnl = $openPositions->sum('unrealized_pnl');
        $totalRealizedPnl = $closedPositions->sum('realized_pnl');
        $totalCostBasis = $openPositions->sum('cost_basis');
        $totalCurrentValue = $openPositions->sum('current_value');

        $winCount = $closedPositions->where('realized_pnl', '>', 0)->count();
        $lossCount = $closedPositions->where('realized_pnl', '<', 0)->count();
        $totalTrades = $closedPositions->count();
        $winRate = $totalTrades > 0 ? ($winCount / $totalTrades) * 100 : 0;

        return [
            'open_positions' => $openPositions,
            'open_count' => $openPositions->count(),
            'total_cost_basis' => $totalCostBasis,
            'total_current_value' => $totalCurrentValue,
            'total_unrealized_pnl' => $totalUnrealizedPnl,
            'total_realized_pnl' => $totalRealizedPnl,
            'total_pnl' => $totalUnrealizedPnl + $totalRealizedPnl,
            'total_trades' => $totalTrades,
            'win_count' => $winCount,
            'loss_count' => $lossCount,
            'win_rate' => $winRate,
        ];
    }

    /**
     * Format open positions for Telegram
     */
    public function formatPositionsMessage(Collection $positions): string
    {
        if ($positions->isEmpty()) {
            $message = "💼 *Open Positions*\n\n";
            $message .= "❌ No open positions\n\n";
            $message .= "*Open a trade:*\n";
            $message .= "• `/buy BTCUSDT 0.5` — Buy 0.5 BTC\n";
            $message .= "• `/buy AAPL 10` — Buy 10 Apple shares\n";
            $message .= "• `/short ETHUSDT 2` — Short 2 ETH\n";
            return $message;
        }

        $message = "💼 *Open Positions* ({$positions->count()})\n\n";
        $totalPnl = 0;

        foreach ($positions as $position) {
            $sideEmoji = $position->side === 'long' ? '🟢' : '🔴';
            $sideLabel = strtoupper($position->side);
            $pnlEmoji = $position->pnl_emoji;
            $pnl = $position->unrealized_pnl ?? 0;
            $pnlPct = $position->unrealized_pnl_pct ?? 0;
            $totalPnl += $pnl;

            $pnlSign = $pnl >= 0 ? '+' : '';
            $pctSign = $pnlPct >= 0 ? '+' : '';

            $message .= "{$sideEmoji} *{$position->symbol}* ({$sideLabel})\n";
            $message .= "  Qty: `{$this->formatQty($position->quantity)}`\n";
            $message .= "  Entry: `{$this->formatDollar($position->entry_price)}`";
            $message .= " → Now: `{$this->formatDollar($position->current_price)}`\n";
            $message .= "  PnL: {$pnlEmoji} `{$pnlSign}{$this->formatDollar($pnl)}` ({$pctSign}" . number_format($pnlPct, 2) . "%)\n";

            if ($position->notes) {
                $message .= "  📝 _{$position->notes}_\n";
            }
            $message .= "\n";
        }

        $totalEmoji = $totalPnl >= 0 ? '🟢' : '🔴';
        $totalSign = $totalPnl >= 0 ? '+' : '';
        $message .= "━━━━━━━━━━━━━━━━\n";
        $message .= "📊 *Total Unrealized PnL:* {$totalEmoji} `{$totalSign}{$this->formatDollar($totalPnl)}`\n\n";
        $message .= "Close: `/sell [symbol]` | History: `/pnl`";

        return $message;
    }

    /**
     * Format portfolio summary for Telegram
     */
    public function formatSummaryMessage(array $summary): string
    {
        $message = "📊 *Portfolio Summary*\n\n";

        // Open positions overview
        if ($summary['open_count'] > 0) {
            $message .= "📈 *Open Positions:* {$summary['open_count']}\n";
            $message .= "💰 Cost Basis: `{$this->formatDollar($summary['total_cost_basis'])}`\n";
            $message .= "💵 Current Value: `{$this->formatDollar($summary['total_current_value'])}`\n";

            $unrealizedEmoji = $summary['total_unrealized_pnl'] >= 0 ? '🟢' : '🔴';
            $unrealizedSign = $summary['total_unrealized_pnl'] >= 0 ? '+' : '';
            $message .= "📊 Unrealized PnL: {$unrealizedEmoji} `{$unrealizedSign}{$this->formatDollar($summary['total_unrealized_pnl'])}`\n\n";
        } else {
            $message .= "📈 *Open Positions:* None\n\n";
        }

        // Trade history
        if ($summary['total_trades'] > 0) {
            $message .= "📜 *Trade History:*\n";
            $message .= "  Total Trades: {$summary['total_trades']}\n";
            $message .= "  ✅ Wins: {$summary['win_count']} | ❌ Losses: {$summary['loss_count']}\n";
            $message .= "  🎯 Win Rate: " . number_format($summary['win_rate'], 1) . "%\n";

            $realizedEmoji = $summary['total_realized_pnl'] >= 0 ? '🟢' : '🔴';
            $realizedSign = $summary['total_realized_pnl'] >= 0 ? '+' : '';
            $message .= "  💰 Realized PnL: {$realizedEmoji} `{$realizedSign}{$this->formatDollar($summary['total_realized_pnl'])}`\n\n";
        } else {
            $message .= "📜 *Trade History:* No closed trades yet\n\n";
        }

        // Total
        $totalEmoji = $summary['total_pnl'] >= 0 ? '🟢' : '🔴';
        $totalSign = $summary['total_pnl'] >= 0 ? '+' : '';
        $message .= "━━━━━━━━━━━━━━━━\n";
        $message .= "💎 *Total PnL:* {$totalEmoji} `{$totalSign}{$this->formatDollar($summary['total_pnl'])}`\n\n";

        $message .= "Positions: `/positions` | Buy: `/buy` | Sell: `/sell`";

        return $message;
    }

    /**
     * Format closed trade for confirmation message
     */
    public function formatClosedTradeMessage(PortfolioPosition $position): string
    {
        $sideEmoji = $position->side === 'long' ? '🟢' : '🔴';
        $pnlEmoji = $position->realized_pnl >= 0 ? '✅' : '❌';
        $pnlSign = $position->realized_pnl >= 0 ? '+' : '';

        $message = "{$pnlEmoji} *Position Closed!*\n\n";
        $message .= "{$sideEmoji} *{$position->symbol}* ({$position->side})\n";
        $message .= "📊 Quantity: `{$this->formatQty($position->quantity)}`\n";
        $message .= "📥 Entry: `{$this->formatDollar($position->entry_price)}`\n";
        $message .= "📤 Exit: `{$this->formatDollar($position->exit_price)}`\n";
        $message .= "💰 PnL: {$pnlEmoji} `{$pnlSign}{$this->formatDollar($position->realized_pnl)}` ({$pnlSign}" . number_format($position->realized_pnl_pct, 2) . "%)\n\n";
        $message .= "View all: `/positions` | Summary: `/pnl`";

        return $message;
    }

    /**
     * Format dollar amount intelligently
     */
    private function formatDollar(float $amount): string
    {
        $abs = abs($amount);
        if ($abs >= 1000) {
            return '$' . number_format($amount, 2);
        } elseif ($abs >= 1) {
            return '$' . number_format($amount, 4);
        } elseif ($abs >= 0.01) {
            return '$' . number_format($amount, 6);
        } else {
            return '$' . number_format($amount, 8);
        }
    }

    /**
     * Format quantity intelligently
     */
    private function formatQty(float $qty): string
    {
        if ($qty >= 100) {
            return number_format($qty, 2);
        } elseif ($qty >= 1) {
            return number_format($qty, 4);
        } else {
            return number_format($qty, 8);
        }
    }
}
