<?php

namespace App\Services;

class AIService
{
    private OpenAIService $openai;

    public function __construct(OpenAIService $openai)
    {
        $this->openai = $openai;
    }

    /**
     * Grounded chat wrapper. Accepts an options array:
     *   - system (string|null)  Override the default analytical system prompt.
     *   - temperature (float)   Default 0.2 (analytical / hallucination-resistant).
     *   - max_tokens (int)      Default 500.
     *   - cache_ttl (int)       Default 900 (15 min). 0 disables.
     *   - json (bool)           Request JSON-only output.
     */
    public function chat(string $prompt, array $opts = []): ?string
    {
        $maxTokens = (int) ($opts['max_tokens'] ?? 500);
        unset($opts['max_tokens']);

        // Analytical default — overrides OpenAIService's 0.3 default for this surface
        if (!array_key_exists('temperature', $opts)) {
            $opts['temperature'] = 0.2;
        }

        return $this->openai->generateCompletion($prompt, $maxTokens, $opts);
    }
}
