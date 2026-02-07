<?php

namespace App\Services;

class EducationService
{
    /**
     * Get learning topics
     */
    public function getLearnTopics(): string
    {
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
