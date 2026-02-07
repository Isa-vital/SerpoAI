<?php

namespace App\Services;

class MultiLanguageService
{
    private array $translations = [];
    private string $defaultLanguage = 'en';

    public function __construct()
    {
        $this->loadTranslations();
    }

    /**
     * Load translations
     */
    private function loadTranslations(): void
    {
        $this->translations = [
            'en' => [
                'welcome' => 'Welcome to SERPO AI! 🤖',
                'price' => 'Price',
                'volume' => 'Volume',
                'change_24h' => '24h Change',
                'liquidity' => 'Liquidity',
                'holders' => 'Holders',
                'market_cap' => 'Market Cap',
                'buy' => 'Buy',
                'sell' => 'Sell',
                'chart' => 'Chart',
                'signals' => 'Signals',
                'alerts' => 'Alerts',
                'portfolio' => 'Portfolio',
                'help' => 'Help',
                'settings' => 'Settings',
            ],
            'es' => [
                'welcome' => '¡Bienvenido a SERPO AI! 🤖',
                'price' => 'Precio',
                'volume' => 'Volumen',
                'change_24h' => 'Cambio 24h',
                'liquidity' => 'Liquidez',
                'holders' => 'Tenedores',
                'market_cap' => 'Cap. de Mercado',
                'buy' => 'Comprar',
                'sell' => 'Vender',
                'chart' => 'Gráfico',
                'signals' => 'Señales',
                'alerts' => 'Alertas',
                'portfolio' => 'Cartera',
                'help' => 'Ayuda',
                'settings' => 'Configuración',
            ],
            'ru' => [
                'welcome' => 'Добро пожаловать в SERPO AI! 🤖',
                'price' => 'Цена',
                'volume' => 'Объем',
                'change_24h' => 'Изм. 24ч',
                'liquidity' => 'Ликвидность',
                'holders' => 'Держатели',
                'market_cap' => 'Рын. Капитализация',
                'buy' => 'Купить',
                'sell' => 'Продать',
                'chart' => 'График',
                'signals' => 'Сигналы',
                'alerts' => 'Оповещения',
                'portfolio' => 'Портфель',
                'help' => 'Помощь',
                'settings' => 'Настройки',
            ],
            'zh' => [
                'welcome' => '欢迎使用 SERPO AI！🤖',
                'price' => '价格',
                'volume' => '交易量',
                'change_24h' => '24小时变化',
                'liquidity' => '流动性',
                'holders' => '持有人',
                'market_cap' => '市值',
                'buy' => '买入',
                'sell' => '卖出',
                'chart' => '图表',
                'signals' => '信号',
                'alerts' => '提醒',
                'portfolio' => '投资组合',
                'help' => '帮助',
                'settings' => '设置',
            ],
        ];
    }

    /**
     * Translate text
     */
    public function translate(string $key, string $language = 'en'): string
    {
        $lang = $this->translations[$language] ?? $this->translations[$this->defaultLanguage];
        return $lang[$key] ?? $key;
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
    public function setUserLanguage(int $userId, string $language): void
    {
        if (!isset($this->translations[$language])) {
            return;
        }

        $user = \App\Models\User::find($userId);
        if ($user) {
            $user->update(['language' => $language]);
        }
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
        ];
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
