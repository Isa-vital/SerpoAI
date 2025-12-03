<?php

namespace App\Services;

use OpenAI;
use Illuminate\Support\Facades\Log;

class OpenAIService
{
    private $client;
    private string $model;

    public function __construct()
    {
        $apiKey = env('OPENAI_API_KEY');
        $this->model = env('OPENAI_MODEL', 'gpt-4o-mini');

        if ($apiKey && $apiKey !== 'your_openai_api_key_here') {
            $this->client = OpenAI::client($apiKey);
        }
    }

    /**
     * Check if OpenAI is configured
     */
    public function isConfigured(): bool
    {
        return $this->client !== null;
    }

    /**
     * Explain a trading term or concept
     */
    public function explainConcept(string $concept): ?string
    {
        if (!$this->isConfigured()) {
            return "OpenAI is not configured. Please set your OPENAI_API_KEY in .env file.";
        }

        try {
            $response = $this->client->chat()->create([
                'model' => $this->model,
                'messages' => [
                    [
                        'role' => 'system',
                        'content' => 'You are a helpful cryptocurrency trading assistant. Explain trading concepts in simple, easy-to-understand terms. Keep responses concise (max 3-4 sentences).'
                    ],
                    [
                        'role' => 'user',
                        'content' => "Explain: {$concept}"
                    ],
                ],
                'max_tokens' => 200,
                'temperature' => 0.7,
            ]);

            return $response->choices[0]->message->content ?? null;
        } catch (\Exception $e) {
            $errorMessage = $e->getMessage();
            Log::error('OpenAI API error', ['message' => $errorMessage]);

            // Handle rate limit errors
            if (str_contains($errorMessage, 'rate limit')) {
                return "⏱️ OpenAI rate limit reached. Please wait a moment and try again. (Free tier has limited requests per minute)";
            }

            return "Unable to generate explanation. Please try again later.";
        }
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
            return "🤖 AI features require OpenAI API key. Please configure it to use this feature.";
        }

        try {
            $systemPrompt = 'You are SerpoAI, an AI assistant for SERPO token traders. ';
            $systemPrompt .= 'Provide helpful, accurate information about crypto trading, SERPO token, and market analysis. ';
            $systemPrompt .= 'Keep responses concise and friendly. Avoid giving direct financial advice.';

            $messages = [
                ['role' => 'system', 'content' => $systemPrompt],
            ];

            // Add context if provided
            if (!empty($context)) {
                $contextStr = "Current market context:\n";
                foreach ($context as $key => $value) {
                    $contextStr .= "{$key}: {$value}\n";
                }
                $messages[] = ['role' => 'system', 'content' => $contextStr];
            }

            $messages[] = ['role' => 'user', 'content' => $question];

            $response = $this->client->chat()->create([
                'model' => $this->model,
                'messages' => $messages,
                'max_tokens' => 250,
                'temperature' => 0.8,
            ]);

            return $response->choices[0]->message->content ?? null;
        } catch (\Exception $e) {
            Log::error('OpenAI question answering error', [
                'question' => $question,
                'message' => $e->getMessage()
            ]);
            return "Sorry, I couldn't process that question. Please try again.";
        }
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
    public function generateMarketPrediction(array $marketData, array $sentimentData = [], array $technicalIndicators = []): array
    {
        if (!$this->isConfigured()) {
            return ['error' => 'AI not configured'];
        }

        try {
            $prompt = "Generate a market prediction for SERPO token based on:\n\n";
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

            $prompt .= "\nProvide prediction in JSON: {\"timeframe\": \"24h\", \"predicted_price\": number, \"trend\": \"bullish/bearish/neutral\", \"confidence\": 0-100, \"reasoning\": \"brief explanation\", \"factors\": [\"factor1\", \"factor2\"]}";

            $response = $this->client->chat()->create([
                'model' => $this->model,
                'messages' => [
                    [
                        'role' => 'system',
                        'content' => 'You are an AI trading analyst. Generate data-driven price predictions based on technical and sentiment analysis. Be conservative with confidence scores. Provide clear reasoning.'
                    ],
                    [
                        'role' => 'user',
                        'content' => $prompt
                    ],
                ],
                'max_tokens' => 300,
                'temperature' => 0.5,
            ]);

            return json_decode($response->choices[0]->message->content, true) ?? ['error' => 'Failed to parse prediction'];
        } catch (\Exception $e) {
            Log::error('OpenAI prediction generation error', ['message' => $e->getMessage()]);
            return ['error' => $e->getMessage()];
        }
    }

    /**
     * Generate personalized trading recommendation
     */
    public function generatePersonalizedRecommendation(array $userProfile, array $marketData, array $sentimentData): string
    {
        if (!$this->isConfigured()) {
            return "AI recommendations require OpenAI configuration.";
        }

        try {
            $prompt = "Generate a personalized trading recommendation for:\n\n";
            $prompt .= "User Profile:\n";
            $prompt .= "Risk Level: " . ($userProfile['risk_level'] ?? 'moderate') . "\n";
            $prompt .= "Trading Style: " . ($userProfile['trading_style'] ?? 'day_trading') . "\n";

            $prompt .= "\nMarket Data:\n";
            $prompt .= "Current Price: $" . $marketData['price'] . "\n";
            $prompt .= "24h Change: " . $marketData['price_change_24h'] . "%\n";
            $prompt .= "Sentiment: " . ($sentimentData['overall_sentiment'] ?? 'Neutral') . "\n";

            $prompt .= "\nProvide a brief, personalized recommendation (3-4 sentences).";

            $response = $this->client->chat()->create([
                'model' => $this->model,
                'messages' => [
                    [
                        'role' => 'system',
                        'content' => 'You are a personalized trading advisor. Tailor recommendations to the user\'s risk tolerance and trading style. Always remind users to DYOR and that this is not financial advice.'
                    ],
                    [
                        'role' => 'user',
                        'content' => $prompt
                    ],
                ],
                'max_tokens' => 200,
                'temperature' => 0.7,
            ]);

            return $response->choices[0]->message->content ?? "Unable to generate recommendation.";
        } catch (\Exception $e) {
            Log::error('OpenAI personalized recommendation error', ['message' => $e->getMessage()]);
            return "Unable to generate personalized recommendation at this time.";
        }
    }

    /**
     * Natural language query processing
     */
    public function processNaturalQuery(string $query, array $availableData = []): string
    {
        if (!$this->isConfigured()) {
            return "AI query processing requires OpenAI configuration.";
        }

        try {
            $contextPrompt = "You have access to the following real-time data:\n";
            foreach ($availableData as $key => $value) {
                $contextPrompt .= "{$key}: {$value}\n";
            }

            $response = $this->client->chat()->create([
                'model' => $this->model,
                'messages' => [
                    [
                        'role' => 'system',
                        'content' => 'You are SerpoAI assistant. Answer user questions using the provided real-time data. Be conversational, helpful, and concise. If you don\'t have specific data, say so honestly. ' . $contextPrompt
                    ],
                    [
                        'role' => 'user',
                        'content' => $query
                    ],
                ],
                'max_tokens' => 250,
                'temperature' => 0.8,
            ]);

            return $response->choices[0]->message->content ?? "I couldn't process that query. Please try rephrasing.";
        } catch (\Exception $e) {
            Log::error('OpenAI natural query error', ['message' => $e->getMessage()]);
            return "Sorry, I couldn't process that query right now. Please try again.";
        }
    }
}
