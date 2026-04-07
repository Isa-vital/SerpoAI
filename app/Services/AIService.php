<?php

namespace App\Services;

class AIService
{
    private OpenAIService $openai;

    public function __construct(OpenAIService $openai)
    {
        $this->openai = $openai;
    }

    public function chat(string $prompt): ?string
    {
        return $this->openai->generateCompletion($prompt, 500);
    }
}
