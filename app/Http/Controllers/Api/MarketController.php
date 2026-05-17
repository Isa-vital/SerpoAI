<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\MarketIntelligenceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MarketController extends Controller
{
    public function __construct(private MarketIntelligenceService $mi) {}

    public function overview(): JsonResponse
    {
        return response()->json($this->mi->overview());
    }

    public function screener(Request $r): JsonResponse
    {
        $market = $r->string('market', 'crypto')->value();
        $limit = min((int) $r->integer('limit', 200), 500);
        $rows = $this->mi->screener($market, $limit);

        // optional sort / filter
        $sort = $r->string('sort', 'volume_24h')->value();
        $dir = $r->string('dir', 'desc')->value();
        usort($rows, function ($a, $b) use ($sort, $dir) {
            $va = $a[$sort] ?? 0;
            $vb = $b[$sort] ?? 0;
            return $dir === 'asc' ? ($va <=> $vb) : ($vb <=> $va);
        });

        if ($q = $r->string('q', '')->value()) {
            $q = strtoupper($q);
            $rows = array_values(array_filter($rows, fn($row) => str_contains(strtoupper($row['symbol'] ?? ''), $q)));
        }

        return response()->json([
            'market' => $market,
            'count' => count($rows),
            'rows' => $rows,
        ]);
    }

    public function derivatives(Request $r): JsonResponse
    {
        $limit = min((int) $r->integer('limit', 30), 60);
        return response()->json($this->mi->derivatives($limit));
    }

    public function longShort(Request $r, string $symbol): JsonResponse
    {
        $period = $r->string('period', '5m')->value();
        return response()->json($this->mi->longShort($symbol, $period));
    }

    public function news(Request $r): JsonResponse
    {
        $limit = min((int) $r->integer('limit', 30), 100);
        return response()->json($this->mi->newsFeed($limit));
    }

    public function whales(Request $r): JsonResponse
    {
        $symbols = explode(',', $r->string('symbols', 'BTC,ETH,SOL,BNB')->value());
        $symbols = array_map('trim', array_filter($symbols));
        return response()->json($this->mi->whaleFeed($symbols));
    }

    public function tickers(Request $r): JsonResponse
    {
        $symbols = explode(',', $r->string('symbols', 'BTCUSDT,ETHUSDT,SOLUSDT,BNBUSDT')->value());
        $symbols = array_map('trim', array_filter($symbols));
        return response()->json($this->mi->tickers($symbols));
    }

    public function sparkline(Request $r, string $symbol): JsonResponse
    {
        $interval = $r->string('interval', '1h')->value();
        $limit = min((int) $r->integer('limit', 24), 200);
        return response()->json($this->mi->sparkline($symbol, $interval, $limit));
    }
}
