<?php

namespace App\Services;

class EducationService
{
    /**
     * Get learning topics (menu) or content for a specific topic number
     */
    public function getLearnTopics(?int $topic = null): string
    {
        if ($topic !== null) {
            return $this->getTopicContent($topic);
        }

        $message = "📚 *LEARNING CENTER*\n\n";

        $message .= "Choose a topic to learn:\n\n";
        $message .= "1️⃣ *Trading Basics*\n";
        $message .= "  • Market orders, limit orders\n";
        $message .= "  • Reading charts & candlesticks\n";
        $message .= "  • Support & resistance\n\n";

        $message .= "2️⃣ *Technical Indicators*\n";
        $message .= "  • RSI, MACD, Moving Averages\n";
        $message .= "  • Bollinger Bands, ATR\n";
        $message .= "  • Volume analysis\n\n";

        $message .= "3️⃣ *Futures Trading*\n";
        $message .= "  • Leverage & margin\n";
        $message .= "  • Funding rates\n";
        $message .= "  • Long vs Short positions\n\n";

        $message .= "4️⃣ *Risk Management*\n";
        $message .= "  • Position sizing\n";
        $message .= "  • Stop-loss strategies\n";
        $message .= "  • Portfolio diversification\n\n";

        $message .= "5️⃣ *On-Chain Analysis*\n";
        $message .= "  • Whale tracking\n";
        $message .= "  • Token metrics\n";
        $message .= "  • Exchange flows\n\n";

        $message .= "Type `/learn [number]` to read about a topic\n";
        $message .= "Example: `/learn 1`";

        return $message;
    }

    /**
     * Get full content for a specific topic number (1-5)
     */
    private function getTopicContent(int $topic): string
    {
        $topics = [
            1 => "📊 *TRADING BASICS*\n━━━━━━━━━━━━━━━━━━━━\n\n" .
                "*Market vs Limit Orders*\n" .
                "• *Market Order:* Buy/sell immediately at current market price. Fast but slippage risk.\n" .
                "• *Limit Order:* Buy/sell only at your target price. Better control, may not fill.\n\n" .
                "*Reading Candlesticks*\n" .
                "🟢 Green candle = close > open (bullish)\n" .
                "🔴 Red candle = close < open (bearish)\n" .
                "• Body = open→close range\n" .
                "• Wicks = high/low extremes\n\n" .
                "*Support & Resistance*\n" .
                "• *Support:* Price floor where buyers step in\n" .
                "• *Resistance:* Price ceiling where sellers dominate\n" .
                "• Breakouts above resistance often become new support\n\n" .
                "💡 Try: `/sr BTCUSDT` to see live levels",

            2 => "📈 *TECHNICAL INDICATORS*\n━━━━━━━━━━━━━━━━━━━━\n\n" .
                "*RSI (Relative Strength Index)*\n" .
                "• Range 0-100\n" .
                "• >70 = overbought (potential reversal down)\n" .
                "• <30 = oversold (potential reversal up)\n\n" .
                "*MACD*\n" .
                "• Trend-following momentum indicator\n" .
                "• Bullish crossover = MACD crosses above signal\n" .
                "• Bearish crossover = MACD crosses below\n\n" .
                "*Moving Averages (MA)*\n" .
                "• SMA = simple average of N periods\n" .
                "• EMA = weights recent prices more\n" .
                "• Golden cross (50 over 200) = bullish\n" .
                "• Death cross (50 under 200) = bearish\n\n" .
                "*Bollinger Bands & ATR*\n" .
                "• Bands measure volatility around a moving average\n" .
                "• ATR (Average True Range) = volatility size\n\n" .
                "💡 Try: `/rsi BTCUSDT`, `/analyze ETHUSDT`",

            3 => "⚡ *FUTURES TRADING*\n━━━━━━━━━━━━━━━━━━━━\n\n" .
                "*Leverage & Margin*\n" .
                "• Leverage multiplies your buying power (e.g., 10x = $1000 controls $10,000)\n" .
                "• Margin = capital you put down\n" .
                "• Higher leverage = higher liquidation risk\n\n" .
                "*Funding Rates*\n" .
                "• Periodic payment between longs and shorts\n" .
                "• Positive funding: longs pay shorts (bullish sentiment)\n" .
                "• Negative funding: shorts pay longs (bearish sentiment)\n" .
                "• Extreme funding often precedes reversals\n\n" .
                "*Long vs Short*\n" .
                "• *Long:* profit when price rises\n" .
                "• *Short:* profit when price falls\n\n" .
                "*Open Interest (OI)*\n" .
                "• Total open contracts in the market\n" .
                "• Rising OI + rising price = strong trend\n" .
                "• Falling OI = positions closing\n\n" .
                "💡 Try: `/oi BTCUSDT`, `/rates`, `/liquidation`",

            4 => "🛡️ *RISK MANAGEMENT*\n━━━━━━━━━━━━━━━━━━━━\n\n" .
                "*The 1-2% Rule*\n" .
                "Never risk more than 1-2% of your portfolio on a single trade.\n\n" .
                "*Position Sizing Formula*\n" .
                "Position Size = (Account × Risk%) / (Entry - Stop Loss)\n\n" .
                "Example: \$10,000 × 1% = \$100 risk\n" .
                "If stop is 5% away → position size = \$2,000\n\n" .
                "*Stop-Loss Strategies*\n" .
                "• Fixed % stop (e.g., -3%)\n" .
                "• ATR-based stop (volatility-adjusted)\n" .
                "• Support/resistance stop (below key level)\n" .
                "• Trailing stop (locks in profits)\n\n" .
                "*Portfolio Diversification*\n" .
                "• Don't go all-in on one asset\n" .
                "• Mix volatility profiles (BTC + alts + stables)\n" .
                "• Consider correlations — alts often move with BTC\n\n" .
                "💡 *Golden rule:* Capital preservation > profit maximization",

            5 => "🔗 *ON-CHAIN ANALYSIS*\n━━━━━━━━━━━━━━━━━━━━\n\n" .
                "*Whale Tracking*\n" .
                "• Large holders moving funds can signal market direction\n" .
                "• Exchange inflows from whales = potential sell pressure\n" .
                "• Exchange outflows = accumulation / holding\n\n" .
                "*Key Token Metrics*\n" .
                "• *Market Cap* = circulating supply × price\n" .
                "• *FDV* (Fully Diluted Valuation) = total supply × price\n" .
                "• *Volume/MCap ratio* — high = active trading\n" .
                "• *Holders* — distribution matters more than count\n\n" .
                "*Exchange Flows*\n" .
                "• Net inflow to exchanges → bearish (people selling)\n" .
                "• Net outflow from exchanges → bullish (people HODLing)\n\n" .
                "*Smart Money Indicators*\n" .
                "• Top wallet accumulation\n" .
                "• Stablecoin supply growth (dry powder)\n" .
                "• Miner reserves (BTC) — falling = sell pressure\n\n" .
                "💡 Try: `/whale`, `/trends`, `/heatmap`",
        ];

        if (!isset($topics[$topic])) {
            return "❌ Invalid topic number. Use `/learn` to see the menu (topics 1-5).";
        }

        return $topics[$topic] . "\n\n━━━━━━━━━━━━━━━━━━━━\nType `/learn` for the menu.";
    }

    /**
     * Get glossary
     */
    public function getGlossary(string $term = null): string
    {
        $glossary = [
            'fud' => ['term' => 'FUD', 'definition' => 'Fear, Uncertainty, and Doubt. Negative information spread to manipulate prices downward.'],
            'fomo' => ['term' => 'FOMO', 'definition' => 'Fear Of Missing Out. The anxiety of missing potential profits, often leading to impulsive buying.'],
            'rsi' => ['term' => 'RSI', 'definition' => 'Relative Strength Index. Momentum indicator showing overbought (>70) or oversold (<30) conditions.'],
            'oi' => ['term' => 'Open Interest', 'definition' => 'Total number of open futures contracts. Rising OI = new money entering, falling OI = positions closing.'],
            'funding' => ['term' => 'Funding Rate', 'definition' => 'Periodic payment between long and short positions in perpetual futures. Positive = longs pay shorts, negative = shorts pay longs.'],
            'liquidation' => ['term' => 'Liquidation', 'definition' => 'Forced closure of a leveraged position when margin falls below maintenance requirement.'],
            'slippage' => ['term' => 'Slippage', 'definition' => 'Difference between expected trade price and actual execution price, especially in volatile or low-liquidity markets.'],
            'whale' => ['term' => 'Whale', 'definition' => 'Individual or entity holding large amounts of cryptocurrency, capable of moving markets with their trades.'],
            'degen' => ['term' => 'Degen', 'definition' => 'Degenerate trader. Someone who takes extremely high-risk trades, often with maximum leverage.'],
            'ath' => ['term' => 'ATH', 'definition' => 'All-Time High. The highest price a cryptocurrency has ever reached.'],
        ];

        if ($term) {
            $key = strtolower($term);
            if (isset($glossary[$key])) {
                $item = $glossary[$key];
                return "*{$item['term']}*\n\n{$item['definition']}";
            } else {
                return "Term not found. Type `/glossary` to see all available terms.";
            }
        }

        $message = "📖 *CRYPTO & TRADING GLOSSARY*\n\n";
        $message .= "Common terms explained:\n\n";

        foreach ($glossary as $item) {
            $message .= "• *{$item['term']}*\n";
        }

        $message .= "\nType `/glossary [term]` to learn more\n";
        $message .= "Example: <code>/glossary fomo</code>";

        return $message;
    }
}
