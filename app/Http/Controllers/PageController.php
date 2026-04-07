<?php

namespace App\Http\Controllers;

use Inertia\Inertia;
use Illuminate\Http\Request;
use App\Services\MarketDataService;
use App\Services\MultiMarketDataService;
use App\Services\BinanceAPIService;
use App\Services\WhaleAlertService;
use App\Services\TokenVerificationService;
use App\Services\SignalGeneratorService;
use App\Services\SentimentAnalysisService;
use App\Services\NewsService;
use App\Services\ChartService;
use App\Services\PortfolioService;
use App\Services\TradePortfolioService;
use App\Services\WatchlistService;
use App\Services\TrendAnalysisService;
use App\Services\MarketScanService;
use App\Services\HeatmapService;
use App\Services\UserProfileService;
use App\Services\OpenAIService;
use App\Models\User;
use App\Models\Alert;
use App\Models\UserAlert;
use App\Models\Signal;

class PageController extends Controller
{
    public function dashboard(MarketDataService $marketData, BinanceAPIService $binance)
    {
        $serpo = null;
        $btc = null;

        try {
            $serpo = $marketData->getTokenPriceFromDex();
        } catch (\Throwable $e) {
            \Log::warning('Dashboard: SERPO fetch failed', ['error' => $e->getMessage()]);
        }

        try {
            $btc = $binance->get24hTicker('BTCUSDT');
        } catch (\Throwable $e) {
            \Log::warning('Dashboard: BTC fetch failed', ['error' => $e->getMessage()]);
        }

        $user = $this->getUser();
        $alertCount = $user ? Alert::where('user_id', $user->id)->where('is_active', true)->count() : 0;

        return Inertia::render('Dashboard', [
            'stats' => [
                'serpoPrice' => $serpo['price'] ?? '—',
                'serpoChange' => $serpo['price_change_24h'] ?? null,
                'btcPrice' => $btc['lastPrice'] ?? '—',
                'btcChange' => $btc['priceChangePercent'] ?? null,
                'activeAlerts' => $alertCount,
                'totalCommands' => '40+',
            ],
        ]);
    }

    public function prices(MultiMarketDataService $multiMarket, HeatmapService $heatmap)
    {
        $cryptoData = [];
        $stockData = [];
        $forexData = [];
        $serpoData = null;

        try {
            $cryptoData = $multiMarket->getCryptoData();
        } catch (\Throwable $e) {
            \Log::warning('Prices: Crypto fetch failed', ['error' => $e->getMessage()]);
            // Fallback to heatmap
            try {
                $heatmapData = $heatmap->generateHeatmap('all');
                $cryptoData = ['spot_markets' => $heatmapData['coins'] ?? [], 'total_pairs' => $heatmapData['total_coins'] ?? 0];
            } catch (\Throwable $e2) {
                \Log::warning('Prices: Heatmap fallback also failed', ['error' => $e2->getMessage()]);
            }
        }

        try {
            $stockData = $multiMarket->getStockData();
        } catch (\Throwable $e) {
            \Log::warning('Prices: Stock fetch failed', ['error' => $e->getMessage()]);
        }

        try {
            $forexData = $multiMarket->getForexData();
        } catch (\Throwable $e) {
            \Log::warning('Prices: Forex fetch failed', ['error' => $e->getMessage()]);
        }

        try {
            $serpoData = app(MarketDataService::class)->getTokenPriceFromDex();
        } catch (\Throwable $e) {
            \Log::warning('Prices: SERPO fetch failed', ['error' => $e->getMessage()]);
        }

        return Inertia::render('Prices', [
            'crypto' => $cryptoData,
            'stocks' => $stockData,
            'forex' => $forexData,
            'serpo' => $serpoData,
        ]);
    }

    public function portfolio()
    {
        $user = $this->getUser();
        if (!$user) {
            return Inertia::render('Portfolio', ['requiresAuth' => true]);
        }

        $walletData = null;
        $trades = null;
        $watchlist = null;

        try {
            $walletData = app(PortfolioService::class)->calculatePortfolioValue($user);
        } catch (\Throwable $e) {
            \Log::warning('Portfolio: wallet fetch failed', ['error' => $e->getMessage()]);
        }

        try {
            $trades = app(TradePortfolioService::class)->getPortfolioSummary($user);
        } catch (\Throwable $e) {
            \Log::warning('Portfolio: trades fetch failed', ['error' => $e->getMessage()]);
        }

        try {
            $watchlist = app(WatchlistService::class)->getWatchlist($user);
        } catch (\Throwable $e) {
            \Log::warning('Portfolio: watchlist fetch failed', ['error' => $e->getMessage()]);
        }

        return Inertia::render('Portfolio', [
            'wallets' => $walletData,
            'trades' => $trades,
            'watchlist' => $watchlist,
        ]);
    }

    public function alerts()
    {
        $user = $this->getUser();
        if (!$user) {
            return Inertia::render('Alerts', ['requiresAuth' => true]);
        }

        $alerts = UserAlert::where('user_id', $user->id)->orderBy('created_at', 'desc')->get();

        return Inertia::render('Alerts', [
            'alerts' => $alerts,
        ]);
    }

    public function ai(Request $request)
    {
        return Inertia::render('AI', [
            'query' => $request->get('q', ''),
        ]);
    }

    public function aiAnalyze(Request $request, OpenAIService $ai, SentimentAnalysisService $sentiment)
    {
        $query = $request->input('query', '');
        if (empty($query)) {
            return back()->with('error', 'Please enter a query');
        }

        try {
            $analysis = $ai->generateCompletion("Analyze the following crypto/market query and provide insights: {$query}");
            $sentimentData = $sentiment->getCryptoSentiment('bitcoin', 'BTC');

            return back()->with('result', [
                'analysis' => $analysis,
                'sentiment' => $sentimentData,
            ]);
        } catch (\Throwable $e) {
            \Log::error('AI analyze failed', ['error' => $e->getMessage()]);
            return back()->with('error', 'AI analysis failed: ' . $e->getMessage());
        }
    }

    public function charts(Request $request, ChartService $chart)
    {
        $symbol = $request->get('symbol', 'BTCUSDT');
        $modes = [];

        try {
            $modes = $chart->getAllChartModes($symbol);
        } catch (\Throwable $e) {
            \Log::warning('Charts: fetch failed', ['error' => $e->getMessage()]);
        }

        return Inertia::render('Charts', [
            'symbol' => $symbol,
            'modes' => $modes,
        ]);
    }

    public function whales(Request $request, WhaleAlertService $whales)
    {
        $symbol = $request->get('symbol', 'BTC');
        $data = [];

        try {
            $data = $whales->getWhaleAlerts($symbol);
        } catch (\Throwable $e) {
            \Log::warning('Whales: fetch failed', ['error' => $e->getMessage()]);
            $data = ['error' => 'Unable to fetch whale data: ' . $e->getMessage()];
        }

        return Inertia::render('Whales', [
            'whaleData' => $data,
            'symbol' => $symbol,
        ]);
    }

    public function verify(Request $request)
    {
        $result = null;
        $input = $request->get('token', '');
        if (!empty($input)) {
            try {
                $result = app(TokenVerificationService::class)->verifyToken($input);
            } catch (\Throwable $e) {
                \Log::warning('Verify: token verification failed', ['error' => $e->getMessage()]);
                $result = ['error' => 'Verification failed: ' . $e->getMessage()];
            }
        }

        return Inertia::render('Verify', [
            'result' => $result,
            'input' => $input,
        ]);
    }

    public function signals(Request $request)
    {
        $symbol = $request->get('symbol', 'BTC');
        $recentSignals = [];

        try {
            $recentSignals = Signal::orderBy('created_at', 'desc')->take(20)->get();
        } catch (\Throwable $e) {
            \Log::warning('Signals: DB query failed', ['error' => $e->getMessage()]);
        }

        return Inertia::render('Signals', [
            'recentSignals' => $recentSignals,
            'symbol' => $symbol,
        ]);
    }

    public function generateSignal(Request $request, SignalGeneratorService $signals)
    {
        $symbol = $request->input('symbol', 'BTC');

        try {
            $result = $signals->generateSignal($symbol);
            return back()->with('signal', $result);
        } catch (\Throwable $e) {
            \Log::error('Signal generation failed', ['error' => $e->getMessage()]);
            return back()->with('error', 'Signal generation failed: ' . $e->getMessage());
        }
    }

    public function research(TrendAnalysisService $trends, NewsService $news, MarketScanService $scan)
    {
        $trendData = null;
        $newsData = null;
        $scanData = null;

        try {
            $scanData = $scan->performDeepScan();
        } catch (\Throwable $e) {
            \Log::warning('Research: Market scan failed', ['error' => $e->getMessage()]);
        }

        try {
            $trendData = $trends->getTrendLeaders();
        } catch (\Throwable $e) {
            \Log::warning('Research: Trends failed', ['error' => $e->getMessage()]);
        }

        try {
            $newsData = $news->getLatestNews();
        } catch (\Throwable $e) {
            \Log::warning('Research: News failed', ['error' => $e->getMessage()]);
        }

        return Inertia::render('Research', [
            'trends' => $trendData,
            'news' => $newsData,
            'scan' => $scanData,
        ]);
    }

    public function grid()
    {
        return Inertia::render('Grid', [
            'status' => 'coming_soon',
        ]);
    }

    public function settings()
    {
        $user = $this->getUser();
        if (!$user) {
            return Inertia::render('Settings', ['requiresAuth' => true]);
        }

        $profile = null;
        try {
            $profile = app(UserProfileService::class)->getProfileDashboard($user->id);
        } catch (\Throwable $e) {
            \Log::warning('Settings: profile fetch failed', ['error' => $e->getMessage()]);
        }

        return Inertia::render('Settings', [
            'profile' => $profile,
        ]);
    }

    private function getUser(): ?User
    {
        $telegramId = session('telegram_id');
        if (!$telegramId) {
            return null;
        }
        return User::where('telegram_id', $telegramId)->first();
    }
}
