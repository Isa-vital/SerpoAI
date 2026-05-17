import Layout from '../Layouts/Layout';
import { Head, router } from '@inertiajs/react';
import { useEffect, useRef, useState } from 'react';

const TV_INTERVALS = ['1', '5', '15', '60', '240', 'D', 'W'];

function getQuery(name, fallback) {
    if (typeof window === 'undefined') return fallback;
    const u = new URL(window.location.href);
    return u.searchParams.get(name) || fallback;
}

function toTvSymbol(s) {
    const sym = String(s || 'BTCUSDT').toUpperCase().trim();
    // Forex (e.g. EURUSD)
    if (/^[A-Z]{6}$/.test(sym) && !sym.endsWith('USDT')) return `OANDA:${sym}`;
    // Equity (e.g. AAPL, SPY)
    if (/^[A-Z]{1,5}$/.test(sym)) return `NASDAQ:${sym}`;
    // Crypto default Binance
    if (sym.endsWith('USDT') || sym.endsWith('BTC') || sym.endsWith('ETH')) return `BINANCE:${sym}`;
    return `BINANCE:${sym}USDT`;
}

export default function Charts() {
    const [symbol, setSymbol] = useState(() => getQuery('symbol', 'BTCUSDT'));
    const [interval, setInterval] = useState('60');
    const [input, setInput] = useState(symbol);
    const containerId = 'tv_chart_container';
    const widgetRef = useRef(null);

    useEffect(() => {
        let script;
        const mount = () => {
            const el = document.getElementById(containerId);
            if (!el || !window.TradingView) return;
            el.innerHTML = '';
            widgetRef.current = new window.TradingView.widget({
                container_id: containerId,
                symbol: toTvSymbol(symbol),
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
    }, [symbol, interval]);

    const submit = (e) => {
        e.preventDefault();
        const next = input.trim().toUpperCase();
        if (!next) return;
        setSymbol(next);
        router.visit(`/charts?symbol=${encodeURIComponent(next)}`, { preserveState: true, preserveScroll: true, replace: true });
    };

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
                </form>
                <div className="flex items-center gap-1 rounded-md border border-gray-800 bg-gray-900 p-1">
                    {TV_INTERVALS.map((iv) => (
                        <button key={iv} onClick={() => setInterval(iv)} className={`rounded px-2 py-1 text-[11px] font-semibold ${interval === iv ? 'bg-emerald-500/15 text-emerald-300' : 'text-gray-400 hover:text-gray-200'}`}>{iv}</button>
                    ))}
                </div>
            </div>

            <div className="overflow-hidden rounded-xl border border-gray-800 bg-gray-900/40" style={{ height: 'calc(100vh - 240px)', minHeight: 520 }}>
                <div id={containerId} className="h-full w-full" />
            </div>
        </Layout>
    );
}
