import Layout from '../Layouts/Layout';
import { Head, router } from '@inertiajs/react';
import { useEffect, useRef, useState } from 'react';
import FallbackChart from '../Components/FallbackChart';

const TV_INTERVALS = ['1', '5', '15', '60', '240', 'D', 'W'];

function getQuery(name, fallback) {
    if (typeof window === 'undefined') return fallback;
    const u = new URL(window.location.href);
    return u.searchParams.get(name) || fallback;
}

// Ask TradingView's public symbol-search API which venues actually carry this
// symbol. Returns { tvSymbol, exchange, description } or null if no real match.
async function resolveSymbol(raw) {
    const text = String(raw || '').toUpperCase().trim();
    if (!text) return null;

    const candidates = [text];
    // For bare tickers also try common crypto quote pairings.
    if (!text.endsWith('USDT') && !text.endsWith('USD') && /^[A-Z]{2,6}$/.test(text)) {
        candidates.push(`${text}USDT`, `${text}USD`);
    }

    for (const q of candidates) {
        try {
            const res = await fetch(
                `https://symbol-search.tradingview.com/symbol_search/?text=${encodeURIComponent(q)}&hl=1&exchange=&lang=en&type=&domain=production`,
                { headers: { 'Accept': 'application/json' } }
            );
            if (!res.ok) continue;
            const list = await res.json();
            if (!Array.isArray(list) || list.length === 0) continue;

            const stripHtml = (s) => String(s || '').replace(/<[^>]+>/g, '');
            const exact = list.filter((r) => stripHtml(r.symbol).toUpperCase() === q.toUpperCase());
            const pool = exact.length ? exact : list;
            // Prefer crypto/forex over stocks when ambiguous.
            const ranked = [...pool].sort((a, b) => {
                const rank = (r) => {
                    const t = (r.type || '').toLowerCase();
                    if (t.includes('crypto') || t === 'spot' || t === 'swap') return 0;
                    if (t.includes('forex')) return 1;
                    if (t.includes('stock') || t.includes('etf')) return 2;
                    return 3;
                };
                return rank(a) - rank(b);
            });
            const top = ranked[0];
            const exchange = top.exchange || top.prefix || '';
            const sym = stripHtml(top.symbol || q);
            if (!exchange) continue;
            return {
                tvSymbol: `${exchange}:${sym}`,
                exchange,
                description: stripHtml(top.description),
            };
        } catch {
            // network / CORS — fall through to next candidate
        }
    }
    return null;
}

export default function Charts() {
    const [symbol, setSymbol] = useState(() => getQuery('symbol', 'BTCUSDT'));
    const [interval, setInterval] = useState('60');
    const [input, setInput] = useState(symbol);
    const [resolved, setResolved] = useState(null); // {tvSymbol, exchange, description} | null
    const [resolveState, setResolveState] = useState('idle'); // idle | loading | ok | missing
    const containerId = 'tv_chart_container';
    const widgetRef = useRef(null);

    // Resolve symbol against TradingView whenever it changes.
    useEffect(() => {
        let cancelled = false;
        setResolveState('loading');
        setResolved(null);
        resolveSymbol(symbol).then((r) => {
            if (cancelled) return;
            if (r) {
                setResolved(r);
                setResolveState('ok');
            } else {
                setResolveState('missing');
            }
        });
        return () => { cancelled = true; };
    }, [symbol]);

    // Mount/refresh the TradingView widget only when we have a verified symbol.
    useEffect(() => {
        if (resolveState !== 'ok' || !resolved) return;

        let script;
        const mount = () => {
            const el = document.getElementById(containerId);
            if (!el || !window.TradingView) return;
            el.innerHTML = '';
            widgetRef.current = new window.TradingView.widget({
                container_id: containerId,
                symbol: resolved.tvSymbol,
                interval,
                theme: 'dark',
                style: '1',
                locale: 'en',
                toolbar_bg: '#0b0f17',
                enable_publishing: false,
                hide_side_toolbar: false,
                allow_symbol_change: true,
                studies: ['RSI@tv-basicstudies', 'MAExp@tv-basicstudies'],
                autosize: true,
            });
        };

        if (!window.TradingView) {
            script = document.createElement('script');
            script.src = 'https://s3.tradingview.com/tv.js';
            script.async = true;
            script.onload = mount;
            document.head.appendChild(script);
        } else {
            mount();
        }

        return () => {
            try { widgetRef.current = null; } catch {}
        };
    }, [resolved, resolveState, interval]);

    const submit = (e) => {
        e.preventDefault();
        const next = input.trim().toUpperCase();
        if (!next) return;
        setSymbol(next);
        router.visit(`/charts?symbol=${encodeURIComponent(next)}`, { preserveState: true, preserveScroll: true, replace: true });
    };

    const baseSym = symbol.replace(/USDT$|USD$/, '');
    // dexUrl/geckoUrl kept available if needed elsewhere; in-site fallback renders via <FallbackChart />.
    void baseSym;

    return (
        <Layout title="Charts">
            <Head title={`${symbol} — Charts`} />

            <div className="mb-3 flex flex-wrap items-center justify-between gap-3">
                <form onSubmit={submit} className="flex items-center gap-2">
                    <input
                        value={input}
                        onChange={(e) => setInput(e.target.value)}
                        placeholder="BTCUSDT, EURUSD, AAPL…"
                        className="w-48 rounded-md border border-gray-800 bg-gray-900 px-3 py-1.5 text-sm text-gray-200 placeholder-gray-600 focus:border-emerald-500/50 focus:outline-none"
                    />
                    <button type="submit" className="rounded-md bg-emerald-500/10 px-3 py-1.5 text-xs font-semibold uppercase tracking-wider text-emerald-400 hover:bg-emerald-500/20">Load</button>
                    {resolveState === 'ok' && resolved && (
                        <span className="hidden text-xs text-gray-500 sm:inline">
                            → <span className="font-mono text-gray-300">{resolved.tvSymbol}</span>
                            {resolved.description && <span className="ml-2 text-gray-500">· {resolved.description}</span>}
                        </span>
                    )}
                </form>
                <div className="flex items-center gap-1 rounded-md border border-gray-800 bg-gray-900 p-1">
                    {TV_INTERVALS.map((iv) => (
                        <button key={iv} onClick={() => setInterval(iv)} className={`rounded px-2 py-1 text-[11px] font-semibold ${interval === iv ? 'bg-emerald-500/15 text-emerald-300' : 'text-gray-400 hover:text-gray-200'}`}>{iv}</button>
                    ))}
                </div>
            </div>

            <div className="overflow-hidden rounded-xl border border-gray-800 bg-gray-900/40" style={{ height: 'calc(100vh - 240px)', minHeight: 520 }}>
                {resolveState === 'loading' && (
                    <div className="flex h-full w-full items-center justify-center text-sm text-gray-400">
                        Resolving <span className="mx-2 font-mono text-gray-200">{symbol}</span>…
                    </div>
                )}

                {resolveState === 'missing' && (
                    <FallbackChart symbol={symbol} />
                )}

                {resolveState === 'ok' && (
                    <div id={containerId} className="h-full w-full" />
                )}
            </div>
        </Layout>
    );
}
