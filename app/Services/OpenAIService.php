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
}
