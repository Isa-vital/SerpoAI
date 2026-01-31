<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class SignalGeneratorService
{
    private AIService $ai;
    private TechnicalStructureService $technical;
    private DerivativesAnalysisService $derivatives;
    private SentimentAnalysisService $sentiment;
    private MultiMarketDataService $marketData;

    public function __construct(
        AIService $ai,
        TechnicalStructureService $technical,
        DerivativesAnalysisService $derivatives,
        SentimentAnalysisService $sentiment,
        MultiMarketDataService $marketData
    ) {
        $this->ai = $ai;
        $this->technical = $technical;
        $this->derivatives = $derivatives;
        $this->sentiment = $sentiment;
        $this->marketData = $marketData;
    }

    /**
     * Generate comprehensive AI-powered trading signals
     */
    public function generateSignal(string $symbol): array
    {
        $cacheKey = "ai_signal_{$symbol}";

        return Cache::remember($cacheKey, 300, function () use ($symbol) {
            try {
                // Detect market type
                $marketType = $this->marketData->detectMarketType($symbol);

                // Gather comprehensive data
                $data = $this->gatherMarketData($symbol, $marketType);

                // Use AI to analyze and generate signal
                $aiSignal = $this->generateAISignal($symbol, $marketType, $data);

                return [
                    'symbol' => $symbol,
                    'market_type' => $marketType,
                    'signal' => $aiSignal['signal'], // BUY, SELL, HOLD
                    'confidence' => $aiSignal['confidence'], // 0-100
                    'reasoning' => $aiSignal['reasoning'],
                    'key_factors' => $aiSignal['key_factors'],
                    'price' => $data['price']['current'] ?? 0,
                    'indicators' => $data['indicators'],
                    'risk_level' => $aiSignal['risk_level'], // LOW, MEDIUM, HIGH
                    'timestamp' => now()->toIso8601String(),
                ];
            } catch (\Exception $e) {
                Log::error('Signal generation error', ['symbol' => $symbol, 'error' => $e->getMessage()]);
                return [
                    'error' => "Unable to generate signal for {$symbol}",
                    'details' => $e->getMessage()
                ];
            }
        });
    }

    /**
     * Gather comprehensive market data from all sources
     */
    private function gatherMarketData(string $symbol, string $marketType): array
    {
        $data = [
            'price' => [],
            'indicators' => [],
            'derivatives' => [],
            'sentiment' => [],
            'volume' => [],
            'events' => [],
        ];

        try {
            // 1. RSI Analysis (all markets)
            $rsi = $this->technical->calculateRSI($symbol);
            if (!isset($rsi['error'])) {
                $data['indicators']['rsi'] = [
                    'weighted' => $rsi['weighted_rsi'] ?? 0,
                    'overall_status' => $rsi['overall_status'] ?? 'Neutral',
                    'timeframes' => $rsi['timeframes'] ?? [],
                ];
            }
        } catch (\Exception $e) {
            Log::warning('RSI fetch failed', ['symbol' => $symbol]);
        }

        try {
            // 2. Price & Volume data
            if ($marketType === 'crypto') {
                $ticker = $this->marketData->getBinanceTicker($symbol);
                $data['price'] = [
                    'current' => floatval($ticker['lastPrice'] ?? 0),
                    'change_24h' => floatval($ticker['priceChangePercent'] ?? 0),
                    'high_24h' => floatval($ticker['highPrice'] ?? 0),
                    'low_24h' => floatval($ticker['lowPrice'] ?? 0),
                ];
                $data['volume'] = [
                    'current' => floatval($ticker['volume'] ?? 0),
                    'quote_volume' => floatval($ticker['quoteVolume'] ?? 0),
                ];
            }
        } catch (\Exception $e) {
            Log::warning('Price fetch failed', ['symbol' => $symbol]);
        }

        try {
            // 3. Derivatives data (crypto only)
            if ($marketType === 'crypto') {
                // Open Interest
                $oi = $this->derivatives->getOpenInterest($symbol);
                if (!isset($oi['error'])) {
                    $data['derivatives']['open_interest'] = [
                        'contracts' => $oi['open_interest']['contracts'] ?? 0,
                        'change_24h' => $oi['open_interest']['change_24h_percent'] ?? 0,
                        'signal' => $oi['signal'] ?? [],
                    ];
                }

                // Money Flow
                $flow = $this->derivatives->getMoneyFlow($symbol);
                if (!isset($flow['error'])) {
                    $data['volume']['flow'] = [
                        'spot_dominance' => $flow['spot']['dominance'] ?? 0,
                        'futures_dominance' => $flow['futures']['dominance'] ?? 0,
                        'net_flow' => $flow['flow']['net_flow'] ?? 'Neutral',
                    ];
                }
            }
        } catch (\Exception $e) {
            Log::warning('Derivatives fetch failed', ['symbol' => $symbol]);
        }

        try {
            // 4. Sentiment Analysis
            $sentiment = $this->sentiment->analyzeSentiment($symbol);
            if (!isset($sentiment['error'])) {
                $data['sentiment'] = [
                    'score' => $sentiment['overall_score'] ?? 0,
                    'classification' => $sentiment['classification'] ?? 'Neutral',
                    'news_sentiment' => $sentiment['news_sentiment'] ?? 'Neutral',
                    'social_sentiment' => $sentiment['social_sentiment'] ?? 'Neutral',
                ];
            }
        } catch (\Exception $e) {
            Log::warning('Sentiment fetch failed', ['symbol' => $symbol]);
        }

        try {
            // 5. Fear & Greed Index (crypto only)
            if ($marketType === 'crypto') {
                $fearGreed = Cache::remember('fear_greed_index', 3600, function () {
                    try {
                        $response = \Illuminate\Support\Facades\Http::timeout(5)
                            ->get('https://api.alternative.me/fng/');
                        if ($response->successful()) {
                            $fng = $response->json();
                            return [
                                'value' => intval($fng['data'][0]['value'] ?? 50),
                                'classification' => $fng['data'][0]['value_classification'] ?? 'Neutral',
                            ];
                        }
                    } catch (\Exception $e) {
                        Log::warning('Fear & Greed fetch failed');
                    }
                    return ['value' => 50, 'classification' => 'Neutral'];
                });

                $data['indicators']['fear_greed'] = $fearGreed;
            }
        } catch (\Exception $e) {
            Log::warning('Fear & Greed fetch failed');
        }

        return $data;
    }

    /**
     * Use AI to analyze all data and generate signal
     */
    private function generateAISignal(string $symbol, string $marketType, array $data): array
    {
        $prompt = $this->buildAnalysisPrompt($symbol, $marketType, $data);

        try {
            $aiResponse = $this->ai->chat($prompt);

            // Parse AI response to extract structured signal
            return $this->parseAIResponse($aiResponse);
        } catch (\Exception $e) {
            Log::error('AI signal generation failed', ['error' => $e->getMessage()]);

            // Fallback: Rule-based signal
            return $this->generateRuleBasedSignal($data);
        }
    }

    /**
     * Build comprehensive analysis prompt for AI
     */
    private function buildAnalysisPrompt(string $symbol, string $marketType, array $data): string
    {
        $prompt = "You are an expert crypto/stock/forex trading analyst. Analyze the following market data and generate a trading signal.\n\n";
        $prompt .= "**Asset:** {$symbol} ({$marketType})\n\n";

        $prompt .= "**Technical Indicators:**\n";
        if (isset($data['indicators']['rsi'])) {
            $rsi = $data['indicators']['rsi'];
            $prompt .= "- RSI (Weighted): {$rsi['weighted']} ({$rsi['overall_status']})\n";
            foreach ($rsi['timeframes'] as $tf => $value) {
                $prompt .= "  - {$tf}: {$value}\n";
            }
        }

        if (isset($data['indicators']['fear_greed'])) {
            $fg = $data['indicators']['fear_greed'];
            $prompt .= "- Fear & Greed Index: {$fg['value']}/100 ({$fg['classification']})\n";
        }

        $prompt .= "\n**Price Action:**\n";
        if (isset($data['price']['current'])) {
            $prompt .= "- Current: \${$data['price']['current']}\n";
            $prompt .= "- 24h Change: {$data['price']['change_24h']}%\n";
            $prompt .= "- 24h High: \${$data['price']['high_24h']}\n";
            $prompt .= "- 24h Low: \${$data['price']['low_24h']}\n";
        }

        if (isset($data['derivatives']['open_interest'])) {
            $oi = $data['derivatives']['open_interest'];
            $prompt .= "\n**Derivatives (Futures):**\n";
            $prompt .= "- Open Interest: " . number_format($oi['contracts']) . " contracts\n";
            $prompt .= "- OI 24h Change: {$oi['change_24h']}%\n";
            if (isset($oi['signal']['signal'])) {
                $prompt .= "- OI Signal: {$oi['signal']['signal']}\n";
            }
        }

        if (isset($data['volume']['flow'])) {
            $flow = $data['volume']['flow'];
            $prompt .= "\n**Volume Flow:**\n";
            $prompt .= "- Spot Dominance: {$flow['spot_dominance']}%\n";
            $prompt .= "- Futures Dominance: {$flow['futures_dominance']}%\n";
            $prompt .= "- Net Flow: {$flow['net_flow']}\n";
        }

        if (isset($data['sentiment'])) {
            $sent = $data['sentiment'];
            $prompt .= "\n**Sentiment:**\n";
            $prompt .= "- Overall Score: {$sent['score']}/100 ({$sent['classification']})\n";
            $prompt .= "- News Sentiment: {$sent['news_sentiment']}\n";
            $prompt .= "- Social Sentiment: {$sent['social_sentiment']}\n";
        }

        $prompt .= "\n**Generate a trading signal with this EXACT format:**\n";
        $prompt .= "SIGNAL: [BUY/SELL/HOLD]\n";
        $prompt .= "CONFIDENCE: [0-100]\n";
        $prompt .= "RISK: [LOW/MEDIUM/HIGH]\n";
        $prompt .= "REASONING: [Your detailed reasoning in 2-3 sentences]\n";
        $prompt .= "KEY_FACTORS: [List 3-5 key factors as bullet points]\n";

        return $prompt;
    }

    /**
     * Parse AI response into structured signal
     */
    private function parseAIResponse(string $response): array
    {
        // Extract signal
        preg_match('/SIGNAL:\s*(BUY|SELL|HOLD)/i', $response, $signalMatch);
        $signal = strtoupper($signalMatch[1] ?? 'HOLD');

        // Extract confidence
        preg_match('/CONFIDENCE:\s*(\d+)/i', $response, $confidenceMatch);
        $confidence = intval($confidenceMatch[1] ?? 50);

        // Extract risk
        preg_match('/RISK:\s*(LOW|MEDIUM|HIGH)/i', $response, $riskMatch);
        $risk = strtoupper($riskMatch[1] ?? 'MEDIUM');

        // Extract reasoning
        preg_match('/REASONING:\s*(.+?)(?=KEY_FACTORS:|$)/is', $response, $reasoningMatch);
        $reasoning = trim($reasoningMatch[1] ?? 'Analysis complete.');

        // Extract key factors
        preg_match('/KEY_FACTORS:\s*(.+?)$/is', $response, $factorsMatch);
        $factorsText = $factorsMatch[1] ?? '';
        $keyFactors = array_filter(array_map('trim', explode("\n", $factorsText)));

        return [
            'signal' => $signal,
            'confidence' => $confidence,
            'risk_level' => $risk,
            'reasoning' => $reasoning,
            'key_factors' => $keyFactors,
        ];
    }

    /**
     * Fallback: Generate rule-based signal if AI fails
     */
    private function generateRuleBasedSignal(array $data): array
    {
        $score = 0;
        $factors = [];

        // RSI scoring
        if (isset($data['indicators']['rsi'])) {
            $rsi = $data['indicators']['rsi']['weighted'];
            if ($rsi < 30) {
                $score += 20;
                $factors[] = "✅ RSI oversold ({$rsi}) - bullish signal";
            } elseif ($rsi > 70) {
                $score -= 20;
                $factors[] = "⚠️ RSI overbought ({$rsi}) - bearish signal";
            }
        }

        // Price momentum
        if (isset($data['price']['change_24h'])) {
            $change = $data['price']['change_24h'];
            if ($change > 5) {
                $score += 15;
                $factors[] = "✅ Strong positive momentum (+{$change}%)";
            } elseif ($change < -5) {
                $score -= 15;
                $factors[] = "⚠️ Strong negative momentum ({$change}%)";
            }
        }

        // Open Interest
        if (isset($data['derivatives']['open_interest']['change_24h'])) {
            $oiChange = $data['derivatives']['open_interest']['change_24h'];
            $priceChange = $data['price']['change_24h'] ?? 0;

            if ($oiChange > 5 && $priceChange > 2) {
                $score += 15;
                $factors[] = "✅ Rising OI + Rising Price - bullish";
            } elseif ($oiChange > 5 && $priceChange < -2) {
                $score -= 15;
                $factors[] = "⚠️ Rising OI + Falling Price - bearish";
            }
        }

        // Sentiment
        if (isset($data['sentiment']['score'])) {
            $sentScore = $data['sentiment']['score'];
            if ($sentScore > 60) {
                $score += 10;
                $factors[] = "✅ Positive sentiment ({$sentScore}/100)";
            } elseif ($sentScore < 40) {
                $score -= 10;
                $factors[] = "⚠️ Negative sentiment ({$sentScore}/100)";
            }
        }

        // Fear & Greed
        if (isset($data['indicators']['fear_greed'])) {
            $fg = $data['indicators']['fear_greed']['value'];
            if ($fg < 25) {
                $score += 10;
                $factors[] = "✅ Extreme Fear ({$fg}) - contrarian buy signal";
            } elseif ($fg > 75) {
                $score -= 10;
                $factors[] = "⚠️ Extreme Greed ({$fg}) - contrarian sell signal";
            }
        }

        // Determine signal
        if ($score > 30) {
            $signal = 'BUY';
            $confidence = min(85, 50 + $score);
            $risk = $score > 50 ? 'MEDIUM' : 'HIGH';
        } elseif ($score < -30) {
            $signal = 'SELL';
            $confidence = min(85, 50 + abs($score));
            $risk = abs($score) > 50 ? 'MEDIUM' : 'HIGH';
        } else {
            $signal = 'HOLD';
            $confidence = 60;
            $risk = 'LOW';
        }

        return [
            'signal' => $signal,
            'confidence' => $confidence,
            'risk_level' => $risk,
            'reasoning' => "Rule-based analysis completed. Signal strength: {$score}/100",
            'key_factors' => $factors,
        ];
    }
}
