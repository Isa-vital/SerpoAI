import { useEffect, useMemo, useState } from 'react';

/**
 * Fallback chart for tokens TradingView can't render.
 *
 * Resolution order:
 *   1. CoinGecko (listed cryptos with historical price series).
 *   2. DexScreener (DEX-only tokens, finds best pool by liquidity).
 *      → GeckoTerminal OHLCV for that pool to draw a real price line.
 *
 * All endpoints are public, CORS-enabled, no API key.
 * Results are cached per-session to keep navigation snappy.
 */

const SESSION_PREFIX = 'fbchart:';

// Map TradingView interval codes to a window the public APIs can serve.
// CoinGecko `days` auto-picks granularity (1=5m, 2-90=hourly, >90=daily).
// GeckoTerminal: timeframe is 'minute'|'hour'|'day' with aggregate + limit.
const TF_MAP = {
    '1':   { label: '1D',  cgDays: 1,   gt: { tf: 'minute', aggregate: 1,  limit: 300 } },
    '5':   { label: '1D',  cgDays: 1,   gt: { tf: 'minute', aggregate: 5,  limit: 288 } },
    '15':  { label: '1D',  cgDays: 1,   gt: { tf: 'minute', aggregate: 15, limit: 96  } },
    '60':  { label: '7D',  cgDays: 7,   gt: { tf: 'hour',   aggregate: 1,  limit: 168 } },
    '240': { label: '30D', cgDays: 30,  gt: { tf: 'hour',   aggregate: 4,  limit: 180 } },
    'D':   { label: '90D', cgDays: 90,  gt: { tf: 'day',    aggregate: 1,  limit: 90  } },
    'W':   { label: '1Y',  cgDays: 365, gt: { tf: 'day',    aggregate: 7,  limit: 52  } },
};
function tfConf(iv) { return TF_MAP[iv] || TF_MAP['60']; }

function cacheGet(key) {
    try {
        const raw = sessionStorage.getItem(SESSION_PREFIX + key);
        if (!raw) return null;
        const { v, t } = JSON.parse(raw);
        if (Date.now() - t > 5 * 60 * 1000) return null; // 5-minute TTL
        return v;
    } catch { return null; }
}
function cacheSet(key, v) {
    try { sessionStorage.setItem(SESSION_PREFIX + key, JSON.stringify({ v, t: Date.now() })); } catch {}
}

async function getJSON(url, signal) {
    const res = await fetch(url, { signal, headers: { Accept: 'application/json' } });
    if (!res.ok) throw new Error(`${res.status}`);
    return res.json();
}

// ---------- CoinGecko path -------------------------------------------------
async function tryCoinGecko(symbol, interval, signal) {
    const conf = tfConf(interval);
    const ckey = `cg:${symbol}:${interval}`;
    const cached = cacheGet(ckey);
    if (cached) return cached;

    const sym = symbol.replace(/USDT$|USD$/, '');
    let id = null;
    try {
        const search = await getJSON(`https://api.coingecko.com/api/v3/search?query=${encodeURIComponent(sym)}`, signal);
        const coins = search.coins || [];
        // Prefer exact ticker match, then top-ranked.
        const exact = coins.find((c) => (c.symbol || '').toUpperCase() === sym.toUpperCase());
        id = (exact || coins[0])?.id || null;
    } catch { return null; }
    if (!id) return null;

    try {
        const [chart, info] = await Promise.all([
            getJSON(`https://api.coingecko.com/api/v3/coins/${id}/market_chart?vs_currency=usd&days=${conf.cgDays}`, signal),
            getJSON(`https://api.coingecko.com/api/v3/coins/${id}?localization=false&tickers=false&community_data=false&developer_data=false&sparkline=false`, signal),
        ]);
        if (!chart.prices || chart.prices.length < 2) return null;
        const result = {
            source: 'CoinGecko',
            timeframe: conf.label,
            name: info.name,
            symbol: (info.symbol || sym).toUpperCase(),
            image: info.image?.small,
            link: `https://www.coingecko.com/en/coins/${id}`,
            linkLabel: 'View on CoinGecko',
            price: info.market_data?.current_price?.usd ?? null,
            change24h: info.market_data?.price_change_percentage_24h ?? null,
            marketCap: info.market_data?.market_cap?.usd ?? null,
            volume24h: info.market_data?.total_volume?.usd ?? null,
            series: chart.prices.map(([ts, p]) => ({ t: ts, p })),
        };
        cacheSet(ckey, result);
        return result;
    } catch { return null; }
}

// ---------- DexScreener + GeckoTerminal path -------------------------------
function chainToGeckoNetwork(chainId) {
    const map = {
        ethereum: 'eth', bsc: 'bsc', polygon: 'polygon_pos', arbitrum: 'arbitrum',
        optimism: 'optimism', base: 'base', avalanche: 'avax', fantom: 'ftm',
        solana: 'solana', ton: 'ton', sui: 'sui', cronos: 'cro', linea: 'linea',
        blast: 'blast', scroll: 'scroll', zksync: 'zksync', mantle: 'mantle',
    };
    return map[chainId] || chainId;
}

async function tryDexScreener(symbol, interval, signal) {
    const conf = tfConf(interval);
    const ckey = `dex:${symbol}:${interval}`;
    const cached = cacheGet(ckey);
    if (cached) return cached;

    const sym = symbol.replace(/USDT$|USD$/, '');
    let pairs;
    try {
        const data = await getJSON(`https://api.dexscreener.com/latest/dex/search/?q=${encodeURIComponent(sym)}`, signal);
        pairs = (data.pairs || []).filter((p) => (p.baseToken?.symbol || '').toUpperCase() === sym.toUpperCase());
        if (pairs.length === 0) pairs = data.pairs || [];
    } catch { return null; }
    if (!pairs.length) return null;

    // Highest USD liquidity wins.
    pairs.sort((a, b) => (b.liquidity?.usd || 0) - (a.liquidity?.usd || 0));
    const top = pairs[0];

    let series = [];
    try {
        const network = chainToGeckoNetwork(top.chainId);
        const ohlc = await getJSON(
            `https://api.geckoterminal.com/api/v2/networks/${network}/pools/${top.pairAddress}/ohlcv/${conf.gt.tf}?aggregate=${conf.gt.aggregate}&limit=${conf.gt.limit}`,
            signal
        );
        const list = ohlc?.data?.attributes?.ohlcv_list || [];
        // Each entry: [timestamp, open, high, low, close, volume]; oldest first when reversed.
        series = list.slice().reverse().map((row) => ({ t: row[0] * 1000, p: Number(row[4]) }));
    } catch { /* no historical — we'll show a flat-line placeholder */ }

    const result = {
        source: 'DexScreener',
        timeframe: conf.label,
        name: top.baseToken?.name || sym,
        symbol: (top.baseToken?.symbol || sym).toUpperCase(),
        image: top.info?.imageUrl,
        link: top.url,
        linkLabel: `View on DexScreener · ${top.dexId} / ${top.chainId}`,
        price: Number(top.priceUsd) || null,
        change24h: Number(top.priceChange?.h24) || null,
        marketCap: top.fdv || top.marketCap || null,
        volume24h: top.volume?.h24 || null,
        liquidity: top.liquidity?.usd || null,
        series,
    };
    cacheSet(ckey, result);
    return result;
}

// ---------- SVG line chart -------------------------------------------------
function formatPrice(p) {
    if (p == null || !isFinite(p)) return '—';
    if (p >= 1000) return '$' + p.toLocaleString(undefined, { maximumFractionDigits: 2 });
    if (p >= 1)    return '$' + p.toFixed(4);
    if (p >= 0.01) return '$' + p.toFixed(6);
    return '$' + p.toExponential(3);
}
function formatBig(n) {
    if (n == null || !isFinite(n)) return '—';
    if (n >= 1e9) return '$' + (n / 1e9).toFixed(2) + 'B';
    if (n >= 1e6) return '$' + (n / 1e6).toFixed(2) + 'M';
    if (n >= 1e3) return '$' + (n / 1e3).toFixed(2) + 'K';
    return '$' + n.toFixed(2);
}

function LineChart({ series, change24h }) {
    const [hover, setHover] = useState(null);
    const W = 800, H = 320, padL = 56, padR = 12, padT = 16, padB = 28;

    const { path, area, xs, ys, min, max } = useMemo(() => {
        if (!series || series.length < 2) {
            return { path: '', area: '', xs: [], ys: [], min: 0, max: 0 };
        }
        const prices = series.map((d) => d.p);
        const min = Math.min(...prices);
        const max = Math.max(...prices);
        const range = max - min || max || 1;
        const t0 = series[0].t;
        const tN = series[series.length - 1].t;
        const tSpan = tN - t0 || 1;
        const xs = series.map((d) => padL + ((d.t - t0) / tSpan) * (W - padL - padR));
        const ys = series.map((d) => padT + (1 - (d.p - min) / range) * (H - padT - padB));
        const path = xs.map((x, i) => `${i === 0 ? 'M' : 'L'} ${x.toFixed(2)} ${ys[i].toFixed(2)}`).join(' ');
        const area = `${path} L ${xs[xs.length - 1].toFixed(2)} ${H - padB} L ${xs[0].toFixed(2)} ${H - padB} Z`;
        return { path, area, xs, ys, min, max };
    }, [series]);

    if (!series || series.length < 2) {
        return (
            <div className="flex h-72 items-center justify-center text-sm text-gray-500">
                No price history available for this pool yet.
            </div>
        );
    }

    const positive = (change24h ?? 0) >= 0;
    const stroke = positive ? '#10b981' : '#f43f5e';
    const fillFrom = positive ? 'rgba(16,185,129,0.25)' : 'rgba(244,63,94,0.25)';

    const onMove = (e) => {
        const rect = e.currentTarget.getBoundingClientRect();
        const x = ((e.clientX - rect.left) / rect.width) * W;
        let idx = 0, best = Infinity;
        for (let i = 0; i < xs.length; i++) {
            const d = Math.abs(xs[i] - x);
            if (d < best) { best = d; idx = i; }
        }
        setHover({ idx, x: xs[idx], y: ys[idx], point: series[idx] });
    };

    return (
        <svg
            viewBox={`0 0 ${W} ${H}`}
            preserveAspectRatio="none"
            className="h-full w-full cursor-crosshair"
            onMouseMove={onMove}
            onMouseLeave={() => setHover(null)}
        >
            <defs>
                <linearGradient id="fbArea" x1="0" x2="0" y1="0" y2="1">
                    <stop offset="0%" stopColor={fillFrom} />
                    <stop offset="100%" stopColor="rgba(0,0,0,0)" />
                </linearGradient>
            </defs>

            {/* y-axis labels */}
            {[0, 0.25, 0.5, 0.75, 1].map((f) => {
                const y = padT + f * (H - padT - padB);
                const val = max - (max - min) * f;
                return (
                    <g key={f}>
                        <line x1={padL} x2={W - padR} y1={y} y2={y} stroke="#1f2937" strokeDasharray="2 3" />
                        <text x={padL - 6} y={y + 3} textAnchor="end" fontSize="10" fill="#6b7280">{formatPrice(val)}</text>
                    </g>
                );
            })}

            <path d={area} fill="url(#fbArea)" />
            <path d={path} fill="none" stroke={stroke} strokeWidth="1.8" strokeLinejoin="round" strokeLinecap="round" />

            {hover && (
                <g>
                    <line x1={hover.x} x2={hover.x} y1={padT} y2={H - padB} stroke="#374151" strokeDasharray="2 2" />
                    <circle cx={hover.x} cy={hover.y} r="3.5" fill={stroke} stroke="#0b0f17" strokeWidth="1.5" />
                    <g transform={`translate(${Math.min(hover.x + 8, W - 150)}, ${Math.max(hover.y - 38, padT)})`}>
                        <rect width="140" height="34" rx="4" fill="#0b0f17" stroke="#374151" />
                        <text x="8" y="14" fontSize="10" fill="#9ca3af">{new Date(hover.point.t).toLocaleString()}</text>
                        <text x="8" y="28" fontSize="12" fill="#e5e7eb" fontWeight="600">{formatPrice(hover.point.p)}</text>
                    </g>
                </g>
            )}
        </svg>
    );
}

// ---------- Main exported component ---------------------------------------
export default function FallbackChart({ symbol, interval = '60' }) {
    const [state, setState] = useState({ status: 'loading', data: null });

    useEffect(() => {
        const ac = new AbortController();
        setState({ status: 'loading', data: null });
        (async () => {
            const cg = await tryCoinGecko(symbol, interval, ac.signal);
            if (ac.signal.aborted) return;
            if (cg) { setState({ status: 'ok', data: cg }); return; }

            const dex = await tryDexScreener(symbol, interval, ac.signal);
            if (ac.signal.aborted) return;
            if (dex) { setState({ status: 'ok', data: dex }); return; }

            setState({ status: 'missing', data: null });
        })().catch(() => { if (!ac.signal.aborted) setState({ status: 'missing', data: null }); });
        return () => ac.abort();
    }, [symbol, interval]);

    if (state.status === 'loading') {
        return (
            <div className="flex h-full w-full items-center justify-center text-sm text-gray-400">
                <div className="flex items-center gap-3">
                    <div className="h-2 w-2 animate-pulse rounded-full bg-emerald-400" />
                    Loading <span className="font-mono text-gray-200">{symbol}</span> via CoinGecko / DexScreener…
                </div>
            </div>
        );
    }

    if (state.status === 'missing' || !state.data) {
        const base = symbol.replace(/USDT$|USD$/, '');
        return (
            <div className="flex h-full w-full flex-col items-center justify-center gap-4 px-6 text-center">
                <div className="text-4xl">🔍</div>
                <div className="text-base font-semibold text-gray-200">
                    No data found for <span className="font-mono">{symbol}</span>
                </div>
                <p className="max-w-md text-sm text-gray-400">
                    We checked TradingView, CoinGecko and DexScreener — nothing matched. Try the full ticker
                    (e.g. <span className="font-mono">{base}USDT</span>) or search the on-chain explorers.
                </p>
                <div className="flex flex-wrap items-center justify-center gap-2">
                    <a href={`https://dexscreener.com/search?q=${encodeURIComponent(base)}`} target="_blank" rel="noopener noreferrer" className="rounded-md bg-emerald-500/10 px-4 py-2 text-sm font-semibold text-emerald-300 hover:bg-emerald-500/20">DexScreener →</a>
                    <a href={`https://www.geckoterminal.com/?q=${encodeURIComponent(base)}`} target="_blank" rel="noopener noreferrer" className="rounded-md bg-blue-500/10 px-4 py-2 text-sm font-semibold text-blue-300 hover:bg-blue-500/20">GeckoTerminal →</a>
                </div>
            </div>
        );
    }

    const d = state.data;
    const positive = (d.change24h ?? 0) >= 0;

    return (
        <div className="flex h-full w-full flex-col">
            {/* header */}
            <div className="flex flex-wrap items-center gap-4 border-b border-gray-800 px-4 py-3">
                {d.image && <img src={d.image} alt="" className="h-8 w-8 rounded-full" />}
                <div>
                    <div className="text-sm font-bold text-white">{d.name} <span className="text-gray-500">· {d.symbol}</span></div>
                    <div className="text-[10px] uppercase tracking-wider text-gray-500">{d.source} · {d.timeframe || '7D'}</div>
                </div>
                <div className="ml-auto flex flex-wrap items-baseline gap-4 text-right">
                    <div>
                        <div className="text-lg font-bold text-white">{formatPrice(d.price)}</div>
                        <div className={`text-xs font-semibold ${positive ? 'text-emerald-400' : 'text-rose-400'}`}>
                            {positive ? '+' : ''}{(d.change24h ?? 0).toFixed(2)}% 24h
                        </div>
                    </div>
                </div>
            </div>

            {/* chart */}
            <div className="min-h-0 flex-1 p-2">
                <LineChart series={d.series} change24h={d.change24h} />
            </div>

            {/* stats */}
            <div className="grid grid-cols-2 gap-px border-t border-gray-800 bg-gray-800 sm:grid-cols-4">
                <div className="bg-gray-900/60 p-3">
                    <div className="text-[10px] uppercase tracking-wider text-gray-500">Market Cap</div>
                    <div className="text-sm font-semibold text-gray-100">{formatBig(d.marketCap)}</div>
                </div>
                <div className="bg-gray-900/60 p-3">
                    <div className="text-[10px] uppercase tracking-wider text-gray-500">Volume 24h</div>
                    <div className="text-sm font-semibold text-gray-100">{formatBig(d.volume24h)}</div>
                </div>
                {d.liquidity != null && (
                    <div className="bg-gray-900/60 p-3">
                        <div className="text-[10px] uppercase tracking-wider text-gray-500">Liquidity</div>
                        <div className="text-sm font-semibold text-gray-100">{formatBig(d.liquidity)}</div>
                    </div>
                )}
                <div className="bg-gray-900/60 p-3">
                    <div className="text-[10px] uppercase tracking-wider text-gray-500">Source</div>
                    <a href={d.link} target="_blank" rel="noopener noreferrer" className="text-sm font-semibold text-emerald-400 hover:text-emerald-300">
                        {d.linkLabel} →
                    </a>
                </div>
            </div>
        </div>
    );
}
