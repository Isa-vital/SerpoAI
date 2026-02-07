<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class NewsService
{
    /**
     * Get latest crypto news from multiple sources
     */
    public function getLatestNews(): string
    {
        $news = $this->fetchNewsFromAllSources();

        if (empty($news)) {
            return "📰 *CRYPTO NEWS*\n\n❌ Unable to fetch news at the moment. Please try again later.";
        }

        $message = "📰 *LATEST CRYPTO NEWS*\n\n";

        foreach ($news as $index => $item) {
            $number = $index + 1;
            $source = $item['source'] ?? 'Unknown';
            $time = $this->formatTime($item['published'] ?? '');

            $message .= "{$number}. {$item['title']}\n";
            $message .= "   📍 {$source}";
            if ($time) {
                $message .= " • {$time}";
            }
            $message .= "\n";
            if (!empty($item['url'])) {
                $message .= "   🔗 [Read More]({$item['url']})\n";
            }
            $message .= "\n";
        }

        $message .= "🔄 _Updates from CryptoPanic, CoinGecko, Twitter, and RSS feeds_";

        return $message;
    }

    /**
     * Fetch news from all sources (2 from each)
     */
    private function fetchNewsFromAllSources(): array
    {
        $allNews = [];

        // Try each source independently - failures don't stop others
        try {
            Log::info('Fetching CryptoPanic news...');
            $cryptoPanic = $this->fetchCryptoPanic(2);
            Log::info('CryptoPanic returned', ['count' => count($cryptoPanic)]);
            $allNews = array_merge($allNews, $cryptoPanic);
        } catch (\Exception $e) {
            Log::warning('CryptoPanic fetch failed', ['error' => $e->getMessage()]);
        }

        try {
            Log::info('Fetching CoinGecko news...');
            $coinGecko = $this->fetchCoinGecko(2);
            Log::info('CoinGecko returned', ['count' => count($coinGecko)]);
            $allNews = array_merge($allNews, $coinGecko);
        } catch (\Exception $e) {
            Log::warning('CoinGecko fetch failed', ['error' => $e->getMessage()]);
        }

        try {
            Log::info('Fetching Reddit news...');
            $reddit = $this->fetchTwitter(2); // Still called fetchTwitter to avoid breaking references
            Log::info('Reddit returned', ['count' => count($reddit)]);
            $allNews = array_merge($allNews, $reddit);
        } catch (\Exception $e) {
            Log::warning('Reddit fetch failed', ['error' => $e->getMessage()]);
        }

        try {
            Log::info('Fetching RSS feeds...');
            $rss = $this->fetchRSSFeeds(2);
            Log::info('RSS returned', ['count' => count($rss)]);
            $allNews = array_merge($allNews, $rss);
        } catch (\Exception $e) {
            Log::warning('RSS fetch failed', ['error' => $e->getMessage()]);
        }

        Log::info('Total news items collected', ['count' => count($allNews)]);

        // Shuffle to mix sources
        shuffle($allNews);

        return array_slice($allNews, 0, 8); // Return max 8 items
    }

    /**
     * Fetch from CryptoPanic API
     */
    private function fetchCryptoPanic(int $limit = 2): array
    {
        $apiKey = env('CRYPTOPANIC_API_KEY');

        if (!$apiKey) {
            Log::info('CryptoPanic API key not configured');
            return [];
        }

        try {
            // Try with increased timeout and retry
            $response = Http::timeout(15)
                ->retry(2, 100) // Retry twice with 100ms delay
                ->get('https://cryptopanic.com/api/v1/posts/', [
                    'auth_token' => $apiKey,
                    'filter' => 'rising',
                    'currencies' => 'BTC,ETH,TON',
                    'public' => 'true',
                ]);

            if (!$response->successful()) {
                Log::warning('CryptoPanic API error', [
                    'status' => $response->status(),
                    'body' => substr($response->body(), 0, 200),
                ]);
                return [];
            }

            $results = $response->json()['results'] ?? [];

            if (empty($results)) {
                Log::info('CryptoPanic returned no results');
                return [];
            }

            return collect($results)->take($limit)->map(function ($item) {
                return [
                    'title' => $item['title'] ?? 'No title',
                    'url' => $item['url'] ?? '',
                    'source' => 'CryptoPanic: ' . ($item['source']['title'] ?? 'Unknown'),
                    'published' => $item['published_at'] ?? '',
                ];
            })->toArray();
        } catch (\Exception $e) {
            Log::warning('CryptoPanic fetch failed', ['error' => $e->getMessage()]);
            return [];
        }
    }

    /**
     * Fetch from CoinGecko News API
     */
    private function fetchCoinGecko(int $limit = 2): array
    {
        try {
            // Try trending coins first (always works)
            $response = Http::timeout(10)
                ->withHeaders(['Accept' => 'application/json'])
                ->get('https://api.coingecko.com/api/v3/search/trending');

            if (!$response->successful()) {
                Log::warning('CoinGecko API returned non-200', ['status' => $response->status()]);
                return [];
            }

            $json = $response->json();
            $coins = $json['coins'] ?? [];

            if (empty($coins)) {
                Log::warning('CoinGecko returned empty coins', ['response' => $json]);
                return [];
            }

            // Convert trending coins to news-like format
            return collect($coins)->take($limit)->map(function ($item) {
                $coin = $item['item'] ?? $item;
                return [
                    'title' => "🔥 Trending: " . ($coin['name'] ?? 'Unknown') . " (" . ($coin['symbol'] ?? '') . ") - Rank #" . ($coin['market_cap_rank'] ?? 'N/A'),
                    'url' => 'https://www.coingecko.com/en/coins/' . ($coin['id'] ?? ''),
                    'source' => 'CoinGecko Trending',
                    'published' => date('c'), // Current time
                ];
            })->toArray();
        } catch (\Exception $e) {
            Log::warning('CoinGecko fetch completely failed', ['error' => $e->getMessage()]);
            return [];
        }
    }

    /**
     * Fetch from Reddit API (Free alternative to Twitter)
     */
    private function fetchTwitter(int $limit = 2): array
    {
        // Using Reddit as free alternative since Twitter search requires paid tier
        // Fetching from r/CryptoCurrency and r/Bitcoin

        try {
            $subreddits = ['CryptoCurrency', 'Bitcoin'];
            $allPosts = [];

            foreach ($subreddits as $subreddit) {
                try {
                    $response = Http::timeout(10)
                        ->withHeaders([
                            'User-Agent' => 'TradeBotAI/2.0'
                        ])
                        ->get("https://www.reddit.com/r/{$subreddit}/hot.json", [
                            'limit' => 5
                        ]);

                    if ($response->successful()) {
                        $posts = $response->json()['data']['children'] ?? [];

                        foreach ($posts as $post) {
                            $data = $post['data'] ?? [];
                            if (!empty($data['title']) && !$data['stickied']) {
                                $allPosts[] = [
                                    'title' => '🔥 ' . substr($data['title'], 0, 100) . (strlen($data['title']) > 100 ? '...' : ''),
                                    'url' => 'https://reddit.com' . ($data['permalink'] ?? ''),
                                    'source' => "Reddit r/{$subreddit}",
                                    'published' => date('c', $data['created_utc'] ?? time()),
                                    'score' => $data['ups'] ?? 0,
                                ];
                            }
                        }
                    }
                } catch (\Exception $e) {
                    Log::warning("Reddit r/{$subreddit} fetch failed", ['error' => $e->getMessage()]);
                }
            }

            // Sort by score and return top items
            usort($allPosts, fn($a, $b) => $b['score'] - $a['score']);

            return array_slice($allPosts, 0, $limit);
        } catch (\Exception $e) {
            Log::warning('Reddit fetch failed', ['error' => $e->getMessage()]);
            return [];
        }
    }

    /**
     * Fetch from RSS Feeds
     */
    private function fetchRSSFeeds(int $limit = 2): array
    {
        $feeds = [
            'https://cointelegraph.com/rss',
            'https://decrypt.co/feed',
            'https://coindesk.com/arc/outboundfeeds/rss/',
            'https://cryptoslate.com/feed/',
            'https://bitcoinmagazine.com/.rss/full/',
        ];

        $allItems = [];
        $itemsPerFeed = 1; // Get 1 from each to ensure variety

        foreach ($feeds as $feedUrl) {
            // Skip if we already have enough
            if (count($allItems) >= $limit) {
                break;
            }

            try {
                $response = Http::timeout(8)->get($feedUrl); // Increased timeout

                if ($response->successful()) {
                    $xml = @simplexml_load_string($response->body());

                    if ($xml && isset($xml->channel->item)) {
                        $count = 0;
                        foreach ($xml->channel->item as $item) {
                            if ($count >= $itemsPerFeed) {
                                break;
                            }

                            $allItems[] = [
                                'title' => (string)$item->title,
                                'url' => (string)$item->link,
                                'source' => 'RSS: ' . (string)($xml->channel->title ?? 'News'),
                                'published' => (string)($item->pubDate ?? ''),
                            ];
                            $count++;
                        }
                    }
                }
            } catch (\Exception $e) {
                Log::warning('RSS feed failed', ['feed' => $feedUrl, 'error' => $e->getMessage()]);
                continue; // Try next feed
            }
        }

        return $allItems;
    }

    /**
     * Format timestamp to human readable
     */
    private function formatTime(string $timestamp): string
    {
        if (empty($timestamp)) {
            return '';
        }

        try {
            $date = new \DateTime($timestamp);
            $now = new \DateTime();
            $diff = $now->diff($date);

            if ($diff->d > 0) {
                return $diff->d . 'd ago';
            } elseif ($diff->h > 0) {
                return $diff->h . 'h ago';
            } elseif ($diff->i > 0) {
                return $diff->i . 'm ago';
            } else {
                return 'Just now';
            }
        } catch (\Exception $e) {
            return '';
        }
    }

    /**
     * Get economic calendar from live API sources
     */
    public function getEconomicCalendar(): string
    {
        $message = "📅 *ECONOMIC CALENDAR*\n\n";

        // Try to fetch real economic events
        $events = $this->fetchEconomicEvents();

        if (!empty($events)) {
            $message .= "⚠️ *Upcoming High-Impact Events*\n\n";

            $groupedByDate = [];
            foreach ($events as $event) {
                $date = $event['date'] ?? 'Unknown';
                $groupedByDate[$date][] = $event;
            }

            foreach (array_slice($groupedByDate, 0, 5, true) as $date => $dayEvents) {
                $message .= "🗓️ *{$date}*\n";
                foreach (array_slice($dayEvents, 0, 4) as $ev) {
                    $impact = $ev['impact'] ?? 'Medium';
                    $impactEmoji = match(strtolower($impact)) {
                        'high' => '🔴',
                        'medium' => '🟡',
                        'low' => '🟢',
                        default => '⚪',
                    };
                    $country = $ev['country'] ?? '';
                    $flag = $this->getCountryFlag($country);
                    $title = $ev['title'] ?? 'Event';
                    $time = $ev['time'] ?? '';
                    $message .= "• {$impactEmoji} {$flag} {$title}";
                    if ($time) $message .= " ({$time})";
                    $message .= "\n";
                }
                $message .= "\n";
            }
        } else {
            // Fallback: static high-level guidance
            $message .= "⚠️ *Key Recurring Events to Watch*\n\n";
            $message .= "🇺🇸 *Federal Reserve (FOMC)*\n";
            $message .= "• Interest Rate Decision (8x/year)\n";
            $message .= "• Next: Check federalreserve.gov\n\n";
            $message .= "🇺🇸 *Employment Data*\n";
            $message .= "• Non-Farm Payrolls (1st Friday monthly)\n";
            $message .= "• Unemployment Claims (Weekly, Thu)\n\n";
            $message .= "🇺🇸 *Inflation Data*\n";
            $message .= "• CPI (Monthly, ~10th-15th)\n";
            $message .= "• PPI (Monthly)\n\n";
            $message .= "🇪🇺 *ECB Interest Rate Decision*\n";
            $message .= "• 6 weeks cycle\n\n";
            $message .= "💡 _For live data, set TRADING_ECONOMICS_KEY in .env_";
        }

        $message .= "💡 *Pro Tip:* High-impact events cause major volatility in crypto, stocks & forex. Reduce leverage before announcements.";

        return $message;
    }

    /**
     * Fetch economic events from API sources
     */
    private function fetchEconomicEvents(): array
    {
        // Try TradingEconomics calendar API
        $teKey = config('services.trading_economics.key', env('TRADING_ECONOMICS_KEY', ''));
        if (!empty($teKey) && $teKey !== 'your_key_here') {
            try {
                $startDate = now()->format('Y-m-d');
                $endDate = now()->addDays(7)->format('Y-m-d');
                $response = Http::timeout(8)->get(
                    "https://api.tradingeconomics.com/calendar/country/united states/{$startDate}/{$endDate}",
                    ['c' => $teKey, 'f' => 'json']
                );

                if ($response->successful()) {
                    $data = $response->json();
                    $events = [];
                    foreach (array_slice($data, 0, 20) as $item) {
                        $importance = intval($item['Importance'] ?? 1);
                        if ($importance < 2) continue; // Skip low impact

                        $events[] = [
                            'date' => date('D, M j', strtotime($item['Date'] ?? '')),
                            'time' => date('g:i A', strtotime($item['Date'] ?? '')),
                            'title' => $item['Event'] ?? '',
                            'country' => $item['Country'] ?? 'US',
                            'impact' => $importance >= 3 ? 'High' : 'Medium',
                            'actual' => $item['Actual'] ?? null,
                            'forecast' => $item['Forecast'] ?? null,
                            'previous' => $item['Previous'] ?? null,
                        ];
                    }
                    if (!empty($events)) return $events;
                }
            } catch (\Exception $e) {
                Log::debug('TradingEconomics calendar failed', ['error' => $e->getMessage()]);
            }
        }

        // Fallback: Try free Nager.Date public holidays API + generate basic events
        try {
            $response = Http::timeout(5)->get('https://date.nager.at/api/v3/NextPublicHolidays/US');
            if ($response->successful()) {
                $holidays = $response->json();
                $events = [];
                foreach (array_slice($holidays, 0, 3) as $h) {
                    $events[] = [
                        'date' => date('D, M j', strtotime($h['date'] ?? '')),
                        'time' => 'All Day',
                        'title' => ($h['localName'] ?? 'Holiday') . ' (Markets Closed)',
                        'country' => 'US',
                        'impact' => 'Medium',
                    ];
                }
                return $events;
            }
        } catch (\Exception $e) {
            // Quietly fail
        }

        return [];
    }

    /**
     * Get country flag emoji
     */
    private function getCountryFlag(string $country): string
    {
        return match(strtolower(trim($country))) {
            'us', 'united states', 'usa' => '🇺🇸',
            'eu', 'euro area', 'european union' => '🇪🇺',
            'uk', 'united kingdom', 'gb' => '🇬🇧',
            'jp', 'japan' => '🇯🇵',
            'cn', 'china' => '🇨🇳',
            'au', 'australia' => '🇦🇺',
            'ca', 'canada' => '🇨🇦',
            'ch', 'switzerland' => '🇨🇭',
            'de', 'germany' => '🇩🇪',
            default => '🏳️',
        };
    }
}
