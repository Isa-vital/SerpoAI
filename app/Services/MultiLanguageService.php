<?php

namespace App\Services;

class MultiLanguageService
{
    private string $defaultLanguage = 'en';

    /**
     * Supported languages
     */
    private array $supportedLanguages = ['en', 'es', 'ru', 'zh', 'lg'];

    /**
     * Translate text using Laravel's localization system
     * Falls back to English if key not found in current locale
     */
    public function translate(string $key, array $params = [], ?string $language = null): string
    {
        $language = $language ?? app()->getLocale();

        if (!in_array($language, $this->supportedLanguages)) {
            $language = $this->defaultLanguage;
        }

        // Temporarily set locale, translate, then restore
        $originalLocale = app()->getLocale();
        app()->setLocale($language);
        $result = __($key, $params);
        app()->setLocale($originalLocale);

        return $result;
    }

    /**
     * Set the application locale for the current request
     */
    public function setLocaleForUser(\App\Models\User $user): void
    {
        $language = $user->language ?? $user->language_code ?? $this->defaultLanguage;

        if (!in_array($language, $this->supportedLanguages)) {
            $language = $this->defaultLanguage;
        }

        app()->setLocale($language);
    }

    /**
     * Get user language preference
     */
    public function getUserLanguage(int $userId): string
    {
        $user = \App\Models\User::find($userId);
        return $user->language ?? $this->defaultLanguage;
    }

    /**
     * Set user language preference
     */
    public function setUserLanguage(int $userId, string $language): bool
    {
        if (!in_array($language, $this->supportedLanguages)) {
            return false;
        }

        try {
            $user = \App\Models\User::find($userId);
            if ($user) {
                $user->update(['language' => $language]);
                app()->setLocale($language);
                \Illuminate\Support\Facades\Log::info("Language changed for user {$userId} to {$language}");
                return true;
            }
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error("Failed to set language for user {$userId}: " . $e->getMessage());

            // Fallback: try setting just the locale for this request
            app()->setLocale($language);

            // If column doesn't exist, try adding it dynamically
            if (str_contains($e->getMessage(), 'language') && str_contains($e->getMessage(), 'Unknown column')) {
                try {
                    \Illuminate\Support\Facades\Schema::table('users', function ($table) {
                        $table->string('language', 5)->default('en');
                    });
                    $user = \App\Models\User::find($userId);
                    $user?->update(['language' => $language]);
                    \Illuminate\Support\Facades\Log::info("Auto-created language column and set to {$language} for user {$userId}");
                    return true;
                } catch (\Exception $e2) {
                    \Illuminate\Support\Facades\Log::error("Failed to auto-create language column: " . $e2->getMessage());
                }
            }
        }

        return false;
    }

    /**
     * Check if a language is supported
     */
    public function isSupported(string $language): bool
    {
        return in_array($language, $this->supportedLanguages);
    }

    /**
     * Get available languages
     */
    public function getAvailableLanguages(): array
    {
        return [
            'en' => '🇬🇧 English',
            'es' => '🇪🇸 Español',
            'ru' => '🇷🇺 Русский',
            'zh' => '🇨🇳 中文',
            'lg' => '🇺🇬 Luganda',
        ];
    }

    /**
     * Get the display name for a language code
     */
    public function getLanguageName(string $code): string
    {
        $languages = $this->getAvailableLanguages();
        return $languages[$code] ?? $code;
    }

    /**
     * Format language selection keyboard
     */
    public function getLanguageKeyboard(): array
    {
        $keyboard = [];
        $languages = $this->getAvailableLanguages();

        $row = [];
        foreach ($languages as $code => $name) {
            $row[] = [
                'text' => $name,
                'callback_data' => "lang_{$code}"
            ];

            if (count($row) === 2) {
                $keyboard[] = $row;
                $row = [];
            }
        }

        if (!empty($row)) {
            $keyboard[] = $row;
        }

        return $keyboard;
    }
}
