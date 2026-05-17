<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;

/**
 * Aggregates structured market data from multiple providers
 * for the SerpoAI trading terminal frontend.
 *
 * All methods return arrays (never strings / HTML). All methods
 * are resilient — failures degrade to empty / cached data, never throw.
 */
class MarketIntelligenceService
{
    public function __construct(
        private BinanceAPIService $binance,
        private MultiMarketDataService $multi,
        private NewsService $news,
        private WhaleAlertService $whales,
    ) {}

    // -------------------------------------------------------------------
    // OVERVIEW (Dashboard hero)
    // -------------------------------------------------------------------
    public function overview(): array
    {
        return Cache::remember('mi:overview', 15, function () {
            $crypto = $this->topCryptoTickers(8);
            $stocks = $this->topStockMovers(6);
            $forex = $this->topForexMovers(6);
            $global = $this->globalCryptoStats();

            return [
                'updated_at' => now()->toIso8601String(),
                'global' => $global,
                'crypto' => $crypto,
                'stocks' => $stocks,
                'forex' => $forex,
            ];
        });
    }

    // -------------------------------------------------------------------
    // SCREENER — flat list of tickers across one market type
    // -------------------------------------------------------------------
    public function screener(string $market = 'crypto', int $limit = 200): array
    {
        $cacheKey = "mi:screener:{$market}:{$limit}";
        return Cache::remember($cacheKey, 20, function () use ($market, $limit) {
            return match ($market) {
                'crypto' => $this->cryptoScreener($limit),
                'forex' => $this->forexScreener(),
                'stocks' => $this->stockScreener($limit),
                default => [],
            };
        });
    }

    // -------------------------------------------------------------------
    // DERIVATIVES — funding rates, OI, long/short
    // -------------------------------------------------------------------
    public function derivatives(int $limit = 30): array
    {
        return Cache::remember("mi:derivatives:{$limit}", 30, function () use ($limit) {
            $symbols = $this->topVolumeSymbols($limit);

            $rows = [];
            foreach ($symbols as $sym) {
                try {
                    $oi = $this->binance->getFuturesOpenInterest($sym);
                    $funding = $this->binance->getFundingRate($sym);
                    $price = $this->binance->get24hTicker($sym);

                    $rows[] = [
                        'symbol' => $sym,
                        'price' => isset($price['lastPrice']) ? (float) $price['lastPrice'] : null,
                        'change_24h' => isset($price['priceChangePercent']) ? (float) $price['priceChangePercent'] : null,
                        'volume_24h' => isset($price['quoteVolume']) ? (float) $price['quoteVolume'] : null,
                        'open_interest' => isset($oi['openInterest']) ? (float) $oi['openInterest'] : null,
                        'funding_rate' => isset($funding['lastFundingRate']) ? (float) $funding['lastFundingRate'] : null,
                        'next_funding_time' => $funding['nextFundingTime'] ?? null,
                    ];
                } catch (\Throwable $e) {
                    continue;
                }
            }

            // Sort by absolute funding rate (extremes first — most actionable)
            usort($rows, fn($a, $b) => abs((float)($b['funding_rate'] ?? 0)) <=> abs((float)($a['funding_rate'] ?? 0)));

            $extremes = $this->extremeFundingRates($rows);

            return [
                'updated_at' => now()->toIso8601String(),
                'rows' => $rows,
                'extremes' => $extremes,
            ];
        });
    }

    public function longShort(string $symbol, string $period = '5m'): array
    {
        $symbol = $this->normaliseUsdt($symbol);
        return Cache::remember("mi:ls:{$symbol}:{$period}", 60, function () use ($symbol, $period) {
            try {
                $ls = $this->binance->getLongShortRatio($symbol, $period, 30);
                $top = $this->binance->getTopTraderRatio($symbol, $period, 30);
                return [
                    'symbol' => $symbol,
                    'global' => $ls,
                    'top_traders' => $top,
                ];
            } catch (\Throwable $e) {
                return ['symbol' => $symbol, 'error' => 'unavailable'];
            }
        });
    }

    // -------------------------------------------------------------------
    // NEWS — structured feed
    // -------------------------------------------------------------------
    public function newsFeed(int $limit = 30): array
    {
        return Cache::remember("mi:news:{$limit}", 120, function () use ($limit) {
            $items = $this->collectStructuredNews($limit);
            usort($items, fn($a, $b) => strtotime($b['published'] ?? '') <=> strtotime($a['published'] ?? ''));
            return [
                'updated_at' => now()->toIso8601String(),
                'items' => array_slice($items, 0, $limit),
            ];
        });
    }

    // -------------------------------------------------------------------
    // WHALES — large order-book entries across symbols
    // -------------------------------------------------------------------
    public function whaleFeed(array $symbols = ['BTC', 'ETH', 'SOL', 'BNB']): array
    {
        return Cache::remember('mi:whales:' . md5(implode(',', $symbols)), 60, function () use ($symbols) {
            $feed = [];
            foreach ($symbols as $s) {
                try {
                    $alerts = $this->whales->getWhaleAlerts($s);
                    foreach (($alerts['large_orders']['bids'] ?? []) as $bid) {
                        $feed[] = [
                            'symbol' => $alerts['symbol'] ?? $s,
                            'side' => 'bid',
                            'price' => $bid['price'] ?? null,
                            'quantity' => $bid['quantity'] ?? null,
                            'value' => $bid['value'] ?? null,
                            'distance_pct' => $bid['distance_from_price'] ?? null,
                            'detected_at' => $alerts['timestamp'] ?? now()->toIso8601String(),
                        ];
                    }
                    foreach (($alerts['large_orders']['asks'] ?? []) as $ask) {
                        $feed[] = [
                            'symbol' => $alerts['symbol'] ?? $s,
                            'side' => 'ask',
                            'price' => $ask['price'] ?? null,
                            'quantity' => $ask['quantity'] ?? null,
                            'value' => $ask['value'] ?? null,
                            'distance_pct' => $ask['distance_from_price'] ?? null,
                            'detected_at' => $alerts['timestamp'] ?? now()->toIso8601String(),
                        ];
                    }
                } catch (\Throwable $e) {
                    continue;
                }
            }

            usort($feed, fn($a, $b) => (float)($b['value'] ?? 0) <=> (float)($a['value'] ?? 0));

            return [
                'updated_at' => now()->toIso8601String(),
                'orders' => array_slice($feed, 0, 100),
            ];
        });
    }

    // -------------------------------------------------------------------
    // TICKERS — quick lookup for top bar / sparkline polling
    // -------------------------------------------------------------------
    public function tickers(array $symbols): array
    {
        $out = [];
        foreach ($symbols as $sym) {
            try {
                $sym = $this->normaliseUsdt($sym);
                $t = $this->binance->get24hTicker($sym);
                if (!$t) continue;
                $out[] = [
                    'symbol' => $sym,
                    'price' => (float) ($t['lastPrice'] ?? 0),
                    'change' => (float) ($t['priceChangePercent'] ?? 0),
                    'volume' => (float) ($t['quoteVolume'] ?? 0),
                    'high' => (float) ($t['highPrice'] ?? 0),
                    'low' => (float) ($t['lowPrice'] ?? 0),
                ];
            } catch (\Throwable $e) {
                continue;
            }
        }
        return ['updated_at' => now()->toIso8601String(), 'tickers' => $out];
    }

    public function sparkline(string $symbol, string $interval = '1h', int $limit = 24): array
    {
        $symbol = $this->normaliseUsdt($symbol);
        return Cache::remember("mi:spark:{$symbol}:{$interval}:{$limit}", 60, function () use ($symbol, $interval, $limit) {
            try {
                $k = $this->binance->getKlines($symbol, $interval, $limit);
                $points = array_map(fn($r) => (float) ($r[4] ?? 0), $k); // close
                return ['symbol' => $symbol, 'interval' => $interval, 'points' => $points];
            } catch (\Throwable $e) {
                return ['symbol' => $symbol, 'interval' => $interval, 'points' => []];
            }
        });
    }

    // ===================================================================
    // INTERNAL HELPERS
    // ===================================================================
    private function topVolumeSymbols(int $limit): array
    {
        $cache = Cache::remember('mi:top_vol_syms', 120, function () {
            try {
                $all = $this->binance->getAllTickers();
                $usdt = array_filter($all, fn($t) => str_ends_with($t['symbol'] ?? '', 'USDT'));
                usort($usdt, fn($a, $b) => (float)($b['quoteVolume'] ?? 0) <=> (float)($a['quoteVolume'] ?? 0));
                return array_column(array_slice($usdt, 0, 60), 'symbol');
            } catch (\Throwable $e) {
                return ['BTCUSDT', 'ETHUSDT', 'SOLUSDT', 'BNBUSDT', 'XRPUSDT', 'DOGEUSDT'];
            }
        });
        return array_slice($cache, 0, $limit);
    }

    private function topCryptoTickers(int $n): array
    {
        try {
            $all = $this->binance->getAllTickers();
            $usdt = array_values(array_filter($all, fn($t) => str_ends_with($t['symbol'] ?? '', 'USDT') && (float)($t['quoteVolume'] ?? 0) > 0));
            usort($usdt, fn($a, $b) => (float)($b['quoteVolume'] ?? 0) <=> (float)($a['quoteVolume'] ?? 0));
            return array_map(fn($t) => [
                'symbol' => $t['symbol'],
                'base' => str_replace('USDT', '', $t['symbol']),
                'price' => (float)($t['lastPrice'] ?? 0),
                'change_24h' => (float)($t['priceChangePercent'] ?? 0),
                'volume_24h' => (float)($t['quoteVolume'] ?? 0),
                'high_24h' => (float)($t['highPrice'] ?? 0),
                'low_24h' => (float)($t['lowPrice'] ?? 0),
            ], array_slice($usdt, 0, $n));
        } catch (\Throwable $e) {
            return [];
        }
    }

    private function topStockMovers(int $n): array
    {
        try {
            $data = $this->multi->getStockData();
            $rows = [];
            foreach (['top_gainers', 'top_losers', 'most_active'] as $k) {
                foreach (($data[$k] ?? []) as $row) {
                    $rows[] = [
                        'symbol' => $row['symbol'] ?? $row['ticker'] ?? '—',
                        'price' => (float)($row['price'] ?? 0),
                        'change_24h' => (float)($row['change_pct'] ?? $row['change_percentage'] ?? 0),
                        'volume_24h' => (float)($row['volume'] ?? 0),
                        'bucket' => $k,
                    ];
                }
            }
            // de-dup by symbol
            $seen = [];
            $unique = [];
            foreach ($rows as $r) {
                if (isset($seen[$r['symbol']])) continue;
                $seen[$r['symbol']] = true;
                $unique[] = $r;
            }
            usort($unique, fn($a, $b) => abs($b['change_24h']) <=> abs($a['change_24h']));
            return array_slice($unique, 0, $n);
        } catch (\Throwable $e) {
            return [];
        }
    }

    private function topForexMovers(int $n): array
    {
        try {
            $data = $this->multi->getForexData();
            $pairs = $data['pairs'] ?? $data['quotes'] ?? [];
            $rows = [];
            foreach ($pairs as $pair => $info) {
                if (!is_array($info)) continue;
                $rows[] = [
                    'symbol' => is_string($pair) ? $pair : ($info['symbol'] ?? '—'),
                    'price' => (float)($info['price'] ?? $info['rate'] ?? 0),
                    'change_24h' => (float)($info['change_pct'] ?? $info['change'] ?? 0),
                    'bid' => (float)($info['bid'] ?? 0),
                    'ask' => (float)($info['ask'] ?? 0),
                ];
            }
            usort($rows, fn($a, $b) => abs($b['change_24h']) <=> abs($a['change_24h']));
            return array_slice($rows, 0, $n);
        } catch (\Throwable $e) {
            return [];
        }
    }

    private function globalCryptoStats(): array
    {
        return Cache::remember('mi:global_crypto', 300, function () {
            try {
                $res = Http::timeout(8)->get('https://api.coingecko.com/api/v3/global');
                if (!$res->successful()) return [];
                $d = $res->json()['data'] ?? [];
                return [
                    'market_cap_usd' => (float)($d['total_market_cap']['usd'] ?? 0),
                    'volume_24h_usd' => (float)($d['total_volume']['usd'] ?? 0),
                    'btc_dominance' => (float)($d['market_cap_percentage']['btc'] ?? 0),
                    'eth_dominance' => (float)($d['market_cap_percentage']['eth'] ?? 0),
                    'market_cap_change_24h' => (float)($d['market_cap_change_percentage_24h_usd'] ?? 0),
                    'active_coins' => (int)($d['active_cryptocurrencies'] ?? 0),
                    'fear_greed' => $this->fearGreed(),
                ];
            } catch (\Throwable $e) {
                return ['fear_greed' => $this->fearGreed()];
            }
        });
    }

    private function fearGreed(): ?array
    {
        try {
            $res = Http::timeout(5)->get('https://api.alternative.me/fng/');
            $row = $res->json()['data'][0] ?? null;
            if (!$row) return null;
            return ['value' => (int)$row['value'], 'label' => $row['value_classification']];
        } catch (\Throwable $e) {
            return null;
        }
    }

    private function cryptoScreener(int $limit): array
    {
        try {
            $all = $this->binance->getAllTickers();
            $rows = [];
            foreach ($all as $t) {
                $sym = $t['symbol'] ?? '';
                if (!str_ends_with($sym, 'USDT')) continue;
                $vol = (float)($t['quoteVolume'] ?? 0);
                if ($vol <= 0) continue;
                $rows[] = [
                    'symbol' => $sym,
                    'base' => str_replace('USDT', '', $sym),
                    'quote' => 'USDT',
                    'price' => (float)($t['lastPrice'] ?? 0),
                    'change_24h' => (float)($t['priceChangePercent'] ?? 0),
                    'volume_24h' => $vol,
                    'high_24h' => (float)($t['highPrice'] ?? 0),
                    'low_24h' => (float)($t['lowPrice'] ?? 0),
                    'trades_24h' => (int)($t['count'] ?? 0),
                ];
            }
            usort($rows, fn($a, $b) => $b['volume_24h'] <=> $a['volume_24h']);
            return array_slice($rows, 0, $limit);
        } catch (\Throwable $e) {
            Log::warning('cryptoScreener failed', ['e' => $e->getMessage()]);
            return [];
        }
    }

    private function forexScreener(): array
    {
        try {
            $data = $this->multi->getForexData();
            $rows = [];
            foreach ($data['pairs'] ?? [] as $pair => $info) {
                if (!is_array($info)) continue;
                $rows[] = [
                    'symbol' => is_string($pair) ? $pair : ($info['symbol'] ?? '—'),
                    'price' => (float)($info['price'] ?? $info['rate'] ?? 0),
                    'change_24h' => (float)($info['change_pct'] ?? $info['change'] ?? 0),
                    'bid' => (float)($info['bid'] ?? 0),
                    'ask' => (float)($info['ask'] ?? 0),
                    'high_24h' => (float)($info['high'] ?? 0),
                    'low_24h' => (float)($info['low'] ?? 0),
                ];
            }
            return $rows;
        } catch (\Throwable $e) {
            return [];
        }
    }

    private function stockScreener(int $limit): array
    {
        try {
            $data = $this->multi->getStockData();
            $rows = [];
            foreach (['most_active', 'top_gainers', 'top_losers'] as $k) {
                foreach (($data[$k] ?? []) as $row) {
                    $rows[] = [
                        'symbol' => $row['symbol'] ?? $row['ticker'] ?? '—',
                        'name' => $row['name'] ?? null,
                        'price' => (float)($row['price'] ?? 0),
                        'change_24h' => (float)($row['change_pct'] ?? $row['change_percentage'] ?? 0),
                        'volume_24h' => (float)($row['volume'] ?? 0),
                        'bucket' => $k,
                    ];
                }
            }
            // de-dup
            $seen = [];
            $unique = [];
            foreach ($rows as $r) {
                if (isset($seen[$r['symbol']])) continue;
                $seen[$r['symbol']] = true;
                $unique[] = $r;
            }
            return array_slice($unique, 0, $limit);
        } catch (\Throwable $e) {
            return [];
        }
    }

    private function extremeFundingRates(array $rows): array
    {
        $longHeavy = [];
        $shortHeavy = [];
        foreach ($rows as $r) {
            $f = $r['funding_rate'] ?? null;
            if ($f === null) continue;
            if ($f > 0) $longHeavy[] = $r;
            else $shortHeavy[] = $r;
        }
        usort($longHeavy, fn($a, $b) => $b['funding_rate'] <=> $a['funding_rate']);
        usort($shortHeavy, fn($a, $b) => $a['funding_rate'] <=> $b['funding_rate']);
        return [
            'most_long' => array_slice($longHeavy, 0, 5),
            'most_short' => array_slice($shortHeavy, 0, 5),
        ];
    }

    private function collectStructuredNews(int $limit): array
    {
        $items = [];

        // CryptoPanic
        try {
            $key = config('services.cryptopanic.key') ?? env('CRYPTOPANIC_API_KEY');
            if ($key) {
                $r = Http::timeout(8)->get('https://cryptopanic.com/api/v1/posts/', [
                    'auth_token' => $key,
                    'public' => 'true',
                    'kind' => 'news',
                ]);
                foreach ($r->json()['results'] ?? [] as $p) {
                    $items[] = [
                        'id' => 'cp_' . ($p['id'] ?? uniqid()),
                        'title' => $p['title'] ?? '',
                        'url' => $p['url'] ?? '',
                        'source' => $p['source']['title'] ?? 'CryptoPanic',
                        'published' => $p['published_at'] ?? null,
                        'sentiment' => $this->mapPanicVotes($p['votes'] ?? []),
                        'tags' => array_column($p['currencies'] ?? [], 'code'),
                    ];
                }
            }
        } catch (\Throwable $e) {
        }

        // CoinGecko status updates (public, no key)
        try {
            $r = Http::timeout(8)->get('https://api.coingecko.com/api/v3/news');
            foreach ($r->json()['data'] ?? [] as $p) {
                $items[] = [
                    'id' => 'cg_' . ($p['id'] ?? md5(($p['url'] ?? '') . ($p['title'] ?? ''))),
                    'title' => $p['title'] ?? '',
                    'url' => $p['url'] ?? '',
                    'source' => $p['author'] ?? 'CoinGecko',
                    'published' => isset($p['updated_at']) ? date('c', (int)$p['updated_at']) : null,
                    'sentiment' => 'neutral',
                    'tags' => [],
                    'description' => $p['description'] ?? null,
                ];
            }
        } catch (\Throwable $e) {
        }

        // RSS fallback (CoinDesk)
        if (count($items) < 5) {
            try {
                $rss = @simplexml_load_file('https://www.coindesk.com/arc/outboundfeeds/rss/');
                if ($rss) {
                    foreach ($rss->channel->item as $it) {
                        $items[] = [
                            'id' => 'rss_' . md5((string)$it->link),
                            'title' => (string)$it->title,
                            'url' => (string)$it->link,
                            'source' => 'CoinDesk',
                            'published' => date('c', strtotime((string)$it->pubDate)),
                            'sentiment' => 'neutral',
                            'tags' => [],
                        ];
                        if (count($items) >= $limit + 20) break;
                    }
                }
            } catch (\Throwable $e) {
            }
        }

        return $items;
    }

    private function mapPanicVotes(array $votes): string
    {
        $pos = (int)($votes['positive'] ?? 0) + (int)($votes['lol'] ?? 0);
        $neg = (int)($votes['negative'] ?? 0) + (int)($votes['toxic'] ?? 0);
        if ($pos > $neg + 1) return 'bullish';
        if ($neg > $pos + 1) return 'bearish';
        return 'neutral';
    }

    private function normaliseUsdt(string $symbol): string
    {
        $s = strtoupper($symbol);
        if (!str_ends_with($s, 'USDT') && !str_contains($s, 'USD')) {
            $s .= 'USDT';
        }
        return $s;
    }
}
