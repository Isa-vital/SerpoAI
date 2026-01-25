<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class CoinglassService
{
    private string $baseUrl = 'https://open-api.coinglass.com/public/v2';
    private ?string $apiKey;

    public function __construct()
    {
        $this->apiKey = config('services.coinglass.api_key');
    }

    /**
     * Get liquidation heatmap data for a symbol
     * 
     * @param string $symbol Symbol (e.g., BTC, ETH)
     * @param string $interval Time interval (1m, 5m, 15m, 1h, 4h, 12h, 1d)
     * @return array|null
     */
    public function getLiquidationHeatmap(string $symbol, string $interval = '1h'): ?array
    {
        if (!$this->apiKey) {
            Log::warning('Coinglass API key not configured');
            return null;
        }

        try {
            $response = Http::withHeaders([
                'coinglassSecret' => $this->apiKey
            ])->get("{$this->baseUrl}/liquidation_heatmap", [
                'symbol' => strtoupper($symbol),
                'interval' => $interval
            ]);

            if ($response->successful()) {
                $data = $response->json();
                
                if ($data['success'] ?? false) {
                    return $this->parseLiquidationData($data['data'] ?? []);
                }
            }

            Log::warning('Coinglass API request failed', [
                'status' => $response->status(),
                'body' => $response->body()
            ]);

        } catch (\Exception $e) {
            Log::error('Coinglass API error', ['error' => $e->getMessage(), 'symbol' => $symbol]);
        }

        return null;
    }

    /**
     * Parse Coinglass liquidation data into standardized format
     */
    private function parseLiquidationData(array $data): array
    {
        $longLiqs = [];
        $shortLiqs = [];

        foreach ($data['liquidation_levels'] ?? [] as $level) {
            $price = floatval($level['price']);
            $volume = floatval($level['volume']);
            $side = $level['side']; // 'long' or 'short'

            $liquidation = [
                'price' => $price,
                'volume' => $volume,
                'intensity' => $this->calculateIntensity($volume, $data['total_volume'] ?? 1),
                'distance' => (($price - floatval($data['current_price'])) / floatval($data['current_price'])) * 100
            ];

            if ($side === 'long') {
                $longLiqs[] = $liquidation;
            } else {
                $shortLiqs[] = $liquidation;
            }
        }

        // Sort by intensity
        usort($longLiqs, fn($a, $b) => $b['intensity'] <=> $a['intensity']);
        usort($shortLiqs, fn($a, $b) => $b['intensity'] <=> $a['intensity']);

        return [
            'longLiqs' => array_slice($longLiqs, 0, 3),
            'shortLiqs' => array_slice($shortLiqs, 0, 3),
            'currentPrice' => floatval($data['current_price'] ?? 0),
            'totalVolume' => floatval($data['total_volume'] ?? 0),
            'dataSource' => 'Coinglass'
        ];
    }

    /**
     * Calculate intensity score (0-1)
     */
    private function calculateIntensity(float $volume, float $totalVolume): float
    {
        if ($totalVolume <= 0) {
            return 0;
        }

        return min(1.0, $volume / $totalVolume);
    }

    /**
     * Get liquidation statistics for multiple exchanges
     */
    public function getLiquidationStats(string $symbol, string $timeframe = '24h'): ?array
    {
        if (!$this->apiKey) {
            return null;
        }

        try {
            $response = Http::withHeaders([
                'coinglassSecret' => $this->apiKey
            ])->get("{$this->baseUrl}/liquidation", [
                'symbol' => strtoupper($symbol),
                'time_type' => $timeframe
            ]);

            if ($response->successful()) {
                $data = $response->json();
                
                if ($data['success'] ?? false) {
                    return $data['data'] ?? [];
                }
            }

        } catch (\Exception $e) {
            Log::error('Coinglass liquidation stats error', ['error' => $e->getMessage()]);
        }

        return null;
    }

    /**
     * Check if API key is configured
     */
    public function isConfigured(): bool
    {
        return !empty($this->apiKey);
    }
}
