<?php

namespace App\Services;

use OpenAI;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Gemini;

class OpenAIService
{
    private $client;
    private $geminiClient;
    private string $model;
    private string $provider = 'gemini'; // Default to Gemini

    public function __construct()
    {
        // Try Gemini first (free, generous limits)
        $geminiKey = env('GEMINI_API_KEY');
        if ($geminiKey && $geminiKey !== 'your_gemini_api_key_here') {
            try {
                $this->geminiClient = Gemini::client($geminiKey);
                $this->provider = 'gemini';
                Log::info('AI Provider: Google Gemini initialized');
            } catch (\Exception $e) {
                Log::warning('Gemini initialization failed', ['error' => $e->getMessage()]);
            }
        }

        // Always initialize OpenAI as fallback (not just if Gemini fails)
        $apiKey = env('OPENAI_API_KEY');
        $this->model = env('OPENAI_MODEL', 'gpt-4o-mini');

        if ($apiKey && $apiKey !== 'your_openai_api_key_here') {
            try {
                $this->client = OpenAI::client($apiKey);
                if (!$this->provider) {
                    $this->provider = 'openai';
                }
                Log::info('AI Provider: OpenAI initialized as fallback');
            } catch (\Exception $e) {
                Log::warning('OpenAI initialization failed', ['error' => $e->getMessage()]);
            }
        }
    }

    /**
     * Check if any AI provider is configured
     */
    public function isConfigured(): bool
    {
        return $this->geminiClient !== null || $this->client !== null;
    }

    /**
     * Generate a completion using available provider
     */
    public function generateCompletion(string $prompt, int $maxTokens = 150): ?string
    {
        // Check cache first
        $cacheKey = 'ai_completion_' . md5($prompt);
        $cached = Cache::get($cacheKey);
        if ($cached) {
            return $cached;
        }

        if (!$this->isConfigured()) {
            return null;
        }

        try {
            $response = null;

            // Try Gemini first
            if ($this->geminiClient) {
                $response = $this->generateWithGemini($prompt, $maxTokens);
            }

            // Fallback to Groq if Gemini fails
            $groqKey = env('GROQ_API_KEY');
            if (!$response && $groqKey && $groqKey !== 'your_groq_api_key_here') {
                $response = $this->generateWithGroq($prompt, $maxTokens);
            }

            // Final fallback to OpenAI
            if (!$response && $this->client) {
                Log::info('Falling back to OpenAI after Gemini/Groq failed');
                $response = $this->generateWithOpenAI($prompt, $maxTokens);
            } elseif (!$response && !$this->client) {
                Log::warning('All AI providers failed and OpenAI not initialized');
            }

            if ($response) {
                Cache::put($cacheKey, $response, 86400); // 24h cache
            }

            return $response;
        } catch (\Exception $e) {
            Log::error('AI completion error', ['message' => $e->getMessage(), 'provider' => $this->provider]);
            return null;
        }
    }

    /**
     * Generate with Google Gemini
     */
    private function generateWithGemini(string $prompt, int $maxTokens): ?string
    {
        try {
            // Use gemini-2.5-flash (stable, latest, free tier)
            $response = $this->geminiClient
                ->generativeModel(model: 'gemini-2.5-flash')
                ->generateContent($prompt);
            return $response->text() ?? null;
        } catch (\Exception $e) {
            Log::warning('Gemini generation failed', ['error' => $e->getMessage()]);
            return null;
        }
    }

    /**
     * Generate with Groq (free, fast)
     */
    private function generateWithGroq(string $prompt, int $maxTokens): ?string
    {
        try {
            Log::info('Attempting Groq generation');
            $response = Http::timeout(15)
                ->withHeaders([
                    'Authorization' => 'Bearer ' . env('GROQ_API_KEY'),
                    'Content-Type' => 'application/json',
                ])
                ->post('https://api.groq.com/openai/v1/chat/completions', [
                    'model' => 'llama-3.3-70b-versatile',
                    'messages' => [
                        ['role' => 'user', 'content' => $prompt]
                    ],
                    'max_tokens' => $maxTokens,
                    'temperature' => 0.7,
                ]);

            if ($response->successful()) {
                $data = $response->json();
                Log::info('Groq generation successful');
                return $data['choices'][0]['message']['content'] ?? null;
            }

            Log::warning('Groq returned non-successful response', [
                'status' => $response->status(),
                'body' => $response->body()
            ]);
            return null;
        } catch (\Exception $e) {
            Log::warning('Groq generation failed', ['error' => $e->getMessage()]);
            return null;
        }
    }

    /**
     * Generate with OpenAI
     */
    private function generateWithOpenAI(string $prompt, int $maxTokens): ?string
    {
        try {
            Log::info('Attempting OpenAI generation');
            $response = $this->client->chat()->create([
                'model' => $this->model,
                'messages' => [
                    ['role' => 'user', 'content' => $prompt]
                ],
                'max_tokens' => $maxTokens,
                'temperature' => 0.7,
            ]);

            Log::info('OpenAI generation successful');
            return $response->choices[0]->message->content ?? null;
        } catch (\Exception $e) {
            Log::warning('OpenAI generation failed', ['error' => $e->getMessage()]);
            return null;
        }
    }

    /**
     * Explain a trading term or concept
     */
    public function explainConcept(string $concept): ?string
    {
        // Check cache first (concepts don't change)
        $cacheKey = 'concept_explanation_' . strtolower(str_replace(' ', '_', $concept));
        $cached = Cache::get($cacheKey);
        if ($cached) {
            return $cached;
        }

        // Try fallback explanations first (no API call needed)
        $fallback = $this->getFallbackExplanation($concept);
        if ($fallback) {
            Cache::put($cacheKey, $fallback, 86400); // Cache for 24 hours
            return $fallback;
        }

        if (!$this->isConfigured()) {
            return "AI is not configured. Please set API keys in .env file.";
        }

        $prompt = "You are a helpful cryptocurrency trading assistant. Explain trading concepts in simple, easy-to-understand terms. Keep responses concise (max 3-4 sentences).\n\nExplain: {$concept}";

        $explanation = $this->generateCompletion($prompt, 200);

        if ($explanation) {
            Cache::put($cacheKey, $explanation, 86400); // Cache for 24 hours
            return $explanation;
        }

        // Return fallback for any error
        return $this->getGenericFallbackExplanation($concept);
    }

    /**
     * Get fallback explanation for common trading concepts
     */
    private function getFallbackExplanation(string $concept): ?string
    {
        $concept = strtolower(trim($concept));

        $fallbacks = [
            'rsi' => "RSI (Relative Strength Index) measures momentum on a scale of 0-100. Below 30 is oversold (potential buy signal), above 70 is overbought (potential sell signal). It helps identify if an asset is overextended.",

            'macd' => "MACD (Moving Average Convergence Divergence) shows trend strength and direction by comparing two moving averages. When the MACD line crosses above the signal line, it's bullish; below is bearish. It helps identify momentum shifts.",

            'moving average' => "Moving Averages smooth out price data to identify trends. The 50-day and 200-day MAs are popular. When price is above the MA, it's bullish; below is bearish. The 'golden cross' (50-day crossing above 200-day) signals strong uptrend.",

            'ma' => "Moving Averages smooth out price data to identify trends. The 50-day and 200-day MAs are popular. When price is above the MA, it's bullish; below is bearish. The 'golden cross' (50-day crossing above 200-day) signals strong uptrend.",

            'support' => "Support is a price level where buying pressure is strong enough to prevent the price from falling further. It's like a floor that holds the price up. Multiple tests without breaking make support stronger.",

            'resistance' => "Resistance is a price level where selling pressure is strong enough to prevent the price from rising further. It's like a ceiling that caps the price. Breaking through resistance often leads to strong upward moves.",

            'bollinger bands' => "Bollinger Bands consist of a middle line (20-day MA) and two outer bands (standard deviations). When bands squeeze, volatility is low and a breakout is likely. When price touches the upper band, it may be overbought; lower band may be oversold.",

            'volume' => "Volume shows how many shares/coins were traded. High volume confirms price moves (strong conviction), while low volume suggests weak moves. Volume spikes often occur at trend reversals.",

            'fibonacci' => "Fibonacci retracement levels (23.6%, 38.2%, 61.8%) help identify potential support/resistance during pullbacks. Traders watch these levels for entries during trends. The 61.8% level is considered the 'golden ratio' and most significant.",

            'ema' => "EMA (Exponential Moving Average) gives more weight to recent prices, making it more responsive than simple MA. Popular periods are 9, 12, and 26. Crossovers between different EMAs generate trading signals.",

            'stochastic' => "Stochastic Oscillator compares closing price to the price range over time (0-100 scale). Below 20 is oversold, above 80 is overbought. Look for crossovers in extreme zones for potential reversals.",

            'atr' => "ATR (Average True Range) measures volatility by showing the average price movement over time. Higher ATR means more volatility. Traders use it to set stop-loss levels and position sizing.",

            'dca' => "DCA (Dollar-Cost Averaging) means investing fixed amounts at regular intervals regardless of price. This reduces the impact of volatility and avoids trying to time the market. It's a long-term strategy for steady accumulation.",

            'fomo' => "FOMO (Fear Of Missing Out) is the anxiety of missing potential profits, causing impulsive buying at high prices. It often leads to buying tops and losing money. Stick to your strategy and avoid emotional decisions.",

            'fud' => "FUD (Fear, Uncertainty, Doubt) is negative information spread to influence sentiment and drive prices down. It can be real concerns or manipulation. Always verify information and don't panic sell.",

            'whale' => "Whales are large holders with enough capital to move markets. Their trades can create significant price swings. Tracking whale movements can provide insights into potential price action.",

            'liquidity' => "Liquidity is how easily an asset can be bought/sold without affecting its price. High liquidity means tight spreads and less slippage. Low liquidity assets are riskier due to price volatility.",

            'market cap' => "Market Cap = Price × Circulating Supply. It represents the total value of all coins. Higher market cap generally means more stability and less volatility. It helps compare the relative size of different cryptocurrencies.",

            'stop loss' => "Stop Loss is an automatic order to sell when price reaches a specified level, limiting potential losses. It's essential risk management. Set it below support levels or based on your risk tolerance (e.g., 2-5%).",

            'take profit' => "Take Profit is an automatic order to sell when price reaches your target, locking in gains. It removes emotion from profit-taking. Set multiple targets to scale out of positions gradually.",
        ];

        return $fallbacks[$concept] ?? null;
    }

    /**
     * Get generic fallback when no specific explanation exists
     */
    private function getGenericFallbackExplanation(string $concept): string
    {
        return "📚 {$concept}: A trading/market concept. For detailed explanation, please try again in a moment or check online trading resources. Our AI service is temporarily at capacity.";
    }

    /**
     * Analyze market conditions and provide insights
     */
    public function analyzeMarketConditions(array $marketData): ?string
    {
        if (!$this->isConfigured()) {
            return null;
        }

        try {
            $prompt = "Analyze this market data for SERPO token:\n";
            $prompt .= "Price: $" . $marketData['price'] . "\n";
            $prompt .= "24h Change: " . $marketData['price_change_24h'] . "%\n";

            if (isset($marketData['rsi'])) {
                $prompt .= "RSI: " . $marketData['rsi'] . "\n";
            }

            if (isset($marketData['volume_24h'])) {
                $prompt .= "Volume 24h: $" . number_format($marketData['volume_24h']) . "\n";
            }

            $prompt .= "\nProvide a brief market insight in 2-3 sentences.";

            $response = $this->client->chat()->create([
                'model' => $this->model,
                'messages' => [
                    [
                        'role' => 'system',
                        'content' => 'You are a professional crypto market analyst. Provide concise, actionable insights. Be objective and avoid giving direct financial advice.'
                    ],
                    [
                        'role' => 'user',
                        'content' => $prompt
                    ],
                ],
                'max_tokens' => 150,
                'temperature' => 0.7,
            ]);

            return $response->choices[0]->message->content ?? null;
        } catch (\Exception $e) {
            Log::error('OpenAI market analysis error', ['message' => $e->getMessage()]);
            return null;
        }
    }

    /**
     * Answer general trading questions
     */
    public function answerQuestion(string $question, array $context = []): ?string
    {
        if (!$this->isConfigured()) {
            return "🤖 AI features require API keys. Please configure them to use this feature.";
        }

        $prompt = 'You are SerpoAI, an AI assistant for SERPO token traders. ';
        $prompt .= 'Provide helpful, accurate information about crypto trading, SERPO token, and market analysis. ';
        $prompt .= 'Keep responses concise and friendly. Avoid giving direct financial advice.\n\n';

        // Add context if provided
        if (!empty($context)) {
            $prompt .= "Current market context:\n";
            foreach ($context as $key => $value) {
                $prompt .= "{$key}: {$value}\n";
            }
            $prompt .= "\n";
        }

        $prompt .= "Question: {$question}";

        $answer = $this->generateCompletion($prompt, 250);

        return $answer ?? "Sorry, I couldn't process that question. Please try again.";
    }

    /**
     * Explain a trading signal
     */
    public function explainSignal(array $signalData): ?string
    {
        if (!$this->isConfigured()) {
            return null;
        }

        try {
            $prompt = "Explain this trading signal in simple terms:\n";
            $prompt .= "Recommendation: " . $signalData['recommendation'] . "\n";
            $prompt .= "Signals: " . implode(', ', $signalData['signals']) . "\n";
            $prompt .= "\nWhat does this mean for traders? Keep it brief (2-3 sentences).";

            $response = $this->client->chat()->create([
                'model' => $this->model,
                'messages' => [
                    [
                        'role' => 'system',
                        'content' => 'You are a trading education assistant. Explain technical signals in plain English that beginners can understand.'
                    ],
                    [
                        'role' => 'user',
                        'content' => $prompt
                    ],
                ],
                'max_tokens' => 150,
                'temperature' => 0.7,
            ]);

            return $response->choices[0]->message->content ?? null;
        } catch (\Exception $e) {
            Log::error('OpenAI signal explanation error', ['message' => $e->getMessage()]);
            return null;
        }
    }

    /**
     * Analyze sentiment of text batch
     */
    public function analyzeSentimentBatch(array $texts): array
    {
        if (!$this->isConfigured() || empty($texts)) {
            return [
                'average_score' => 0,
                'positive_count' => 0,
                'negative_count' => 0,
                'neutral_count' => 0,
                'trending_keywords' => [],
            ];
        }

        try {
            $textSample = implode("\n", array_slice($texts, 0, 20)); // Limit to 20 samples

            $response = $this->client->chat()->create([
                'model' => $this->model,
                'messages' => [
                    [
                        'role' => 'system',
                        'content' => 'You are a sentiment analysis expert. Analyze the overall sentiment of crypto-related social media posts. Rate from -100 (very bearish) to +100 (very bullish). Also extract trending keywords. Respond in JSON format: {"score": number, "positive": number, "negative": number, "neutral": number, "keywords": [array]}'
                    ],
                    [
                        'role' => 'user',
                        'content' => "Analyze sentiment of these posts:\n\n{$textSample}\n\nTotal posts: " . count($texts)
                    ],
                ],
                'max_tokens' => 200,
                'temperature' => 0.3,
            ]);

            $result = json_decode($response->choices[0]->message->content, true);

            return [
                'average_score' => $result['score'] ?? 0,
                'positive_count' => $result['positive'] ?? 0,
                'negative_count' => $result['negative'] ?? 0,
                'neutral_count' => $result['neutral'] ?? 0,
                'trending_keywords' => $result['keywords'] ?? [],
            ];
        } catch (\Exception $e) {
            Log::error('OpenAI sentiment batch analysis error', ['message' => $e->getMessage()]);
            return [
                'average_score' => 0,
                'positive_count' => 0,
                'negative_count' => 0,
                'neutral_count' => 0,
                'trending_keywords' => [],
            ];
        }
    }

    /**
     * Generate AI market prediction
     */
    public function generateMarketPrediction(string $symbol, array $marketData, array $sentimentData = [], array $technicalIndicators = []): array
    {
        if (!$this->isConfigured()) {
            return ['error' => 'AI not configured'];
        }

        $prompt = "You are an AI trading analyst. Generate data-driven price predictions based on technical and sentiment analysis. Be conservative with confidence scores. Provide clear reasoning.\n\n";
        $prompt .= "Generate a market prediction for {$symbol} based on:\n\n";
        $prompt .= "Current Price: $" . $marketData['price'] . "\n";
        $prompt .= "24h Change: " . $marketData['price_change_24h'] . "%\n";
        $prompt .= "Volume: $" . number_format($marketData['volume_24h'] ?? 0) . "\n";

        if (!empty($sentimentData)) {
            $prompt .= "\nSentiment Analysis:\n";
            $prompt .= "Overall Score: " . ($sentimentData['overall_score'] ?? 'N/A') . "\n";
            $prompt .= "Social Mentions: " . ($sentimentData['total_mentions'] ?? 0) . "\n";
        }

        if (!empty($technicalIndicators)) {
            $prompt .= "\nTechnical Indicators:\n";
            foreach ($technicalIndicators as $key => $value) {
                $prompt .= "{$key}: {$value}\n";
            }
        }

        $prompt .= "\nProvide prediction in JSON format only (no markdown, no additional text): {\"timeframe\": \"24h\", \"predicted_price\": number, \"trend\": \"bullish/bearish/neutral\", \"confidence\": 0-100, \"reasoning\": \"brief explanation\", \"factors\": [\"factor1\", \"factor2\"]}";

        $response = $this->generateCompletion($prompt, 300);

        if ($response) {
            // Log the raw response for debugging
            Log::info('Raw prediction response', ['response' => $response]);

            // Try to extract JSON from markdown code blocks if present
            if (preg_match('/```(?:json)?\s*(\{.*?\})\s*```/s', $response, $matches)) {
                $response = $matches[1];
            }

            // Clean up any remaining markdown or extra text
            $response = trim($response);
            if (strpos($response, '{') !== false) {
                $response = substr($response, strpos($response, '{'));
                if (strrpos($response, '}') !== false) {
                    $response = substr($response, 0, strrpos($response, '}') + 1);
                }
            }

            $prediction = json_decode($response, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                Log::warning('Failed to parse prediction JSON', [
                    'error' => json_last_error_msg(),
                    'response' => $response
                ]);
                return ['error' => 'Failed to parse prediction'];
            }

            return $prediction;
        }

        return ['error' => 'Failed to generate prediction'];
    }

    /**
     * Generate personalized trading recommendation
     */
    public function generatePersonalizedRecommendation(array $userProfile, array $marketData, array $sentimentData): string
    {
        if (!$this->isConfigured()) {
            return "AI recommendations require configuration.";
        }

        $prompt = "You are a personalized trading advisor. Tailor recommendations to the user's risk tolerance and trading style. Always remind users to DYOR and that this is not financial advice.\n\n";
        $prompt .= "Generate a personalized trading recommendation for:\n\n";
        $prompt .= "User Profile:\n";
        $prompt .= "Risk Level: " . ($userProfile['risk_level'] ?? 'moderate') . "\n";
        $prompt .= "Trading Style: " . ($userProfile['trading_style'] ?? 'day_trading') . "\n";

        $prompt .= "\nMarket Data:\n";
        $prompt .= "Current Price: $" . $marketData['price'] . "\n";
        $prompt .= "24h Change: " . $marketData['price_change_24h'] . "%\n";
        $prompt .= "Sentiment: " . ($sentimentData['overall_sentiment'] ?? 'Neutral') . "\n";

        $prompt .= "\nProvide a brief, personalized recommendation (3-4 sentences).";

        $response = $this->generateCompletion($prompt, 200);

        return $response ?? "Unable to generate recommendation at this time.";
    }

    /**
     * Natural language query processing
     */
    public function processNaturalQuery(string $query, array $availableData = []): string
    {
        if (!$this->isConfigured()) {
            return "AI query processing requires configuration.";
        }

        $prompt = "You are SerpoAI assistant. Answer user questions using the provided real-time data. Be conversational, helpful, and concise. If you don't have specific data, say so honestly.\n\n";

        $prompt .= "You have access to the following real-time data:\n";
        foreach ($availableData as $key => $value) {
            $prompt .= "{$key}: {$value}\n";
        }

        $prompt .= "\nUser question: {$query}";

        $response = $this->generateCompletion($prompt, 250);

        return $response ?? "I couldn't process that query. Please try rephrasing.";
    }
}
