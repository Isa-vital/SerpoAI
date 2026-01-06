<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class ChartService
{
    private MultiMarketDataService $marketData;

    public function __construct(MultiMarketDataService $marketData)
    {
        $this->marketData = $marketData;
    }

    /**
     * Generate TradingView chart link with preset configurations
     */
    public function generateChartLink(string $symbol, string $mode = 'intraday'): array
    {
        // Detect market type and format symbol for TradingView
        $marketType = $this->marketData->detectMarketType($symbol);
        $tvSymbol = $this->formatSymbolForTradingView($symbol, $marketType);

        $presets = [
            'scalp' => [
                'interval' => '5',
                'studies' => 'VWAP@tv-basicstudies,Volume@tv-basicstudies',
                'description' => '5min chart with VWAP and Volume for scalping'
            ],
            'intraday' => [
                'interval' => '15',
                'studies' => 'RSI@tv-basicstudies,MACD@tv-basicstudies,BB@tv-basicstudies',
                'description' => '15min chart with RSI, MACD, and Bollinger Bands'
            ],
            'swing' => [
                'interval' => '240',
                'studies' => 'MASimple@tv-basicstudies,Volume@tv-basicstudies',
                'description' => '4H chart with Moving Averages and Volume'
            ],
        ];

        $preset = $presets[$mode] ?? $presets['intraday'];

        // Construct TradingView URL
        $baseUrl = 'https://www.tradingview.com/chart/';
        $params = [
            'symbol' => $tvSymbol,
            'interval' => $preset['interval'],
        ];

        if (!empty($preset['studies'])) {
            $params['studies'] = $preset['studies'];
        }

        $url = $baseUrl . '?' . http_build_query($params);

        return [
            'url' => $url,
            'symbol' => $tvSymbol,
            'mode' => $mode,
            'interval' => $preset['interval'],
            'description' => $preset['description'],
            'market_type' => $marketType
        ];
    }

    /**
     * Get all chart modes for a symbol
     */
    public function getAllChartModes(string $symbol): array
    {
        return [
            'scalp' => $this->generateChartLink($symbol, 'scalp'),
            'intraday' => $this->generateChartLink($symbol, 'intraday'),
            'swing' => $this->generateChartLink($symbol, 'swing'),
        ];
    }

    /**
     * Format symbol for TradingView
     */
    private function formatSymbolForTradingView(string $symbol, string $marketType): string
    {
        $symbol = strtoupper($symbol);

        switch ($marketType) {
            case 'crypto':
                // TradingView format: BINANCE:BTCUSDT
                if (!str_contains($symbol, 'USDT')) {
                    $symbol .= 'USDT';
                }
                return 'BINANCE:' . $symbol;

            case 'stock':
                // TradingView format: NASDAQ:AAPL or NYSE:TSLA
                // For simplicity, default to NASDAQ (can be enhanced with exchange detection)
                return 'NASDAQ:' . str_replace('USD', '', $symbol);

            case 'forex':
                // TradingView format: FX:EURUSD
                return 'FX:' . str_replace('/', '', $symbol);

            default:
                return $symbol;
        }
    }

    /**
     * Get quick chart analysis
     */
    public function getQuickAnalysis(string $symbol): array
    {
        try {
            $marketType = $this->marketData->detectMarketType($symbol);
            
            $data = null;
            switch ($marketType) {
                case 'crypto':
                    $data = $this->marketData->analyzeCryptoPair($symbol);
                    break;
                case 'stock':
                    $data = $this->marketData->analyzeStock($symbol);
                    break;
                case 'forex':
                    $data = $this->marketData->analyzeForexPair($symbol);
                    break;
            }
            
            if (!$data || !isset($data['price'])) {
                return ['error' => 'Unable to fetch market data'];
            }

            $priceChange = $data['price_change_24h'] ?? 0;
            $volume = $data['volume_24h'] ?? 0;

            $trend = 'Neutral';
            $emoji = '➡️';
            if ($priceChange > 2) {
                $trend = 'Strong Bullish';
                $emoji = '🚀';
            } elseif ($priceChange > 0) {
                $trend = 'Bullish';
                $emoji = '📈';
            } elseif ($priceChange < -2) {
                $trend = 'Strong Bearish';
                $emoji = '📉';
            } elseif ($priceChange < 0) {
                $trend = 'Bearish';
                $emoji = '🔻';
            }

            return [
                'symbol' => $symbol,
                'price' => $data['price'],
                'change_24h' => $priceChange,
                'volume_24h' => $volume,
                'trend' => $trend,
                'emoji' => $emoji,
                'high_24h' => $data['high_24h'] ?? null,
                'low_24h' => $data['low_24h'] ?? null,
            ];
        } catch (\Exception $e) {
            Log::error('Chart analysis error', ['symbol' => $symbol, 'error' => $e->getMessage()]);
            return ['error' => 'Analysis unavailable'];
        }
    }
}
