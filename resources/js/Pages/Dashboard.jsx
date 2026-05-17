import Layout from '../Layouts/Layout';
import { Head, Link } from '@inertiajs/react';
import useLiveData from '../hooks/useLiveData';
import { formatPrice, formatCompact, formatChange, changeColor, formatTimeAgo } from '../lib/format';
import Sparkline from '../Components/Sparkline';
import LiveDot from '../Components/LiveDot';
import StatCard from '../Components/StatCard';
import Skeleton from '../Components/Skeleton';
import WatchlistStar from '../Components/WatchlistStar';
import useWatchlist from '../hooks/useWatchlist';
import { useEffect, useState } from 'react';

function SparkCell({ symbol }) {
    const [pts, setPts] = useState(null);
    useEffect(() => {
        let cancelled = false;
        fetch(`/api/markets/sparkline/${symbol}?interval=1h&limit=24`, { headers: { Accept: 'application/json' } })
            .then((r) => r.json())
            .then((j) => { if (!cancelled) setPts(j.points || []); })
            .catch(() => {});
        return () => { cancelled = true; };
    }, [symbol]);
    if (!pts) return <div className="h-7 w-[100px] animate-pulse rounded bg-gray-800/50" />;
    return <Sparkline points={pts} width={100} height={28} />;
}

function MoversList({ rows, market, emptyLabel }) {
    if (!rows || rows.length === 0) {
        return <div className="px-4 py-8 text-center text-xs text-gray-600">{emptyLabel}</div>;
    }
    return (
        <ul className="divide-y divide-gray-800/70">
            {rows.map((r, i) => {
                const sym = r.symbol || r.base;
                const href = market === 'crypto' ? `/charts?symbol=${sym}` : `/prices`;
                return (
                    <li key={`${sym}-${i}`}>
                        <Link href={href} className="flex items-center gap-3 px-4 py-2.5 hover:bg-gray-800/40">
                            <div className="flex h-8 w-8 items-center justify-center rounded-md bg-gray-800 text-xs font-bold text-gray-300">{(r.base || sym).slice(0, 3)}</div>
                            <div className="min-w-0 flex-1">
                                <div className="flex items-center gap-2 text-sm font-semibold text-white">{sym}</div>
                                <div className="text-[11px] text-gray-500">Vol {formatCompact(r.volume_24h)}</div>
                            </div>
                            <div className="text-right">
                                <div className="font-mono text-sm text-white">${formatPrice(r.price)}</div>
                                <div className={`font-mono text-xs ${changeColor(r.change_24h)}`}>{formatChange(r.change_24h)}</div>
                            </div>
                        </Link>
                    </li>
                );
            })}
        </ul>
    );
}

function Gauge({ value, label }) {
    const v = Math.max(0, Math.min(100, Number(value) || 0));
    const color = v < 25 ? '#fb7185' : v < 45 ? '#f59e0b' : v < 55 ? '#eab308' : v < 75 ? '#84cc16' : '#34d399';
    const angle = (v / 100) * 180 - 90;
    return (
        <div className="flex flex-col items-center">
            <svg width="140" height="80" viewBox="0 0 140 80">
                <path d="M10,70 A60,60 0 0 1 130,70" fill="none" stroke="#1f2937" strokeWidth="12" strokeLinecap="round" />
                <path d="M10,70 A60,60 0 0 1 130,70" fill="none" stroke={color} strokeWidth="12" strokeLinecap="round" strokeDasharray={`${(v / 100) * 188.5} 200`} />
                <g transform={`translate(70,70) rotate(${angle})`}>
                    <line x1="0" y1="0" x2="0" y2="-46" stroke="#fff" strokeWidth="2" />
                    <circle r="4" fill="#fff" />
                </g>
            </svg>
            <div className="mt-1 text-2xl font-bold text-white">{Math.round(v)}</div>
            <div className="text-[10px] uppercase tracking-wider text-gray-500">{label}</div>
        </div>
    );
}

export default function Dashboard({ stats = {} }) {
    const { data: overview, loading } = useLiveData('/api/markets/overview', { interval: 15000 });
    const { data: news } = useLiveData('/api/markets/news?limit=8', { interval: 60000 });
    const { list: watch } = useWatchlist();

    const global = overview?.global || {};
    const cryptoTop = overview?.crypto || [];
    const stockTop = overview?.stocks || [];
    const forexTop = overview?.forex || [];
    const fg = global.fear_greed || null;

    return (
        <Layout title="Dashboard">
            <Head title="SerpoAI — Trading Terminal" />

            {/* Hero stats */}
            <div className="grid grid-cols-2 gap-3 md:grid-cols-4">
                <StatCard
                    label="Crypto Market Cap"
                    value={global.market_cap_usd ? '$' + formatCompact(global.market_cap_usd) : '—'}
                    sub={global.market_cap_change_24h !== undefined ? <span className={changeColor(global.market_cap_change_24h)}>{formatChange(global.market_cap_change_24h)} 24h</span> : null}
                    accent="emerald"
                />
                <StatCard
                    label="24h Volume"
                    value={global.volume_24h_usd ? '$' + formatCompact(global.volume_24h_usd) : '—'}
                    sub={`${global.active_coins || '—'} active coins`}
                    accent="blue"
                />
                <StatCard
                    label="BTC Dominance"
                    value={global.btc_dominance ? global.btc_dominance.toFixed(1) + '%' : '—'}
                    sub={`ETH ${global.eth_dominance ? global.eth_dominance.toFixed(1) + '%' : '—'}`}
                    accent="amber"
                />
                <StatCard
                    label="Fear & Greed"
                    value={fg ? fg.value : '—'}
                    sub={fg ? fg.label : '—'}
                    accent={fg ? (fg.value > 60 ? 'emerald' : fg.value > 40 ? 'amber' : 'rose') : 'gray'}
                />
            </div>

            {/* Multi-market top movers */}
            <div className="mt-5 grid gap-4 lg:grid-cols-3">
                <Panel title="Top Crypto" right={<LiveDot />}>
                    {loading ? <Skeleton className="m-4 h-40" /> : <MoversList rows={cryptoTop} market="crypto" emptyLabel="No crypto data" />}
                </Panel>
                <Panel title="Stock Movers" right={<span className="text-[10px] uppercase text-gray-500">US Equities</span>}>
                    {loading ? <Skeleton className="m-4 h-40" /> : <MoversList rows={stockTop} market="stocks" emptyLabel="Market closed or data unavailable" />}
                </Panel>
                <Panel title="Forex Majors" right={<span className="text-[10px] uppercase text-gray-500">FX</span>}>
                    {loading ? <Skeleton className="m-4 h-40" /> : <MoversList rows={forexTop} market="forex" emptyLabel="FX data unavailable" />}
                </Panel>
            </div>

            {/* Sentiment + Watchlist + News */}
            <div className="mt-5 grid gap-4 lg:grid-cols-3">
                <Panel title="Market Sentiment">
                    <div className="flex items-center justify-around p-4">
                        {fg && <Gauge value={fg.value} label="Fear & Greed" />}
                        <div className="space-y-3">
                            <div>
                                <div className="text-[10px] uppercase tracking-wider text-gray-500">BTC Dominance</div>
                                <div className="font-mono text-xl text-white">{global.btc_dominance ? global.btc_dominance.toFixed(2) + '%' : '—'}</div>
                            </div>
                            <div>
                                <div className="text-[10px] uppercase tracking-wider text-gray-500">ETH Dominance</div>
                                <div className="font-mono text-xl text-white">{global.eth_dominance ? global.eth_dominance.toFixed(2) + '%' : '—'}</div>
                            </div>
                        </div>
                    </div>
                </Panel>

                <Panel title="Watchlist" right={<Link href="/screener" className="text-[10px] uppercase tracking-wider text-emerald-400 hover:text-emerald-300">Browse markets →</Link>}>
                    {watch.length === 0 ? (
                        <div className="px-4 py-8 text-center text-xs text-gray-600">★ tag any market to follow it here.</div>
                    ) : (
                        <ul className="divide-y divide-gray-800/70">
                            {watch.slice(0, 8).map((sym) => (
                                <li key={sym} className="flex items-center justify-between px-4 py-2.5">
                                    <div className="flex items-center gap-2">
                                        <WatchlistStar symbol={sym} />
                                        <Link href={`/charts?symbol=${sym}`} className="text-sm font-semibold text-white hover:text-emerald-300">{sym}</Link>
                                    </div>
                                    <SparkCell symbol={sym} />
                                </li>
                            ))}
                        </ul>
                    )}
                </Panel>

                <Panel title="Latest News" right={<Link href="/news" className="text-[10px] uppercase tracking-wider text-emerald-400 hover:text-emerald-300">All news →</Link>}>
                    {!news ? <Skeleton className="m-4 h-40" /> : (
                        <ul className="divide-y divide-gray-800/70">
                            {(news.items || []).slice(0, 6).map((n) => (
                                <li key={n.id}>
                                    <a href={n.url} target="_blank" rel="noopener noreferrer" className="block px-4 py-2.5 hover:bg-gray-800/40">
                                        <div className="flex items-start justify-between gap-3">
                                            <div className="min-w-0 flex-1">
                                                <div className="line-clamp-2 text-xs leading-snug text-gray-200">{n.title}</div>
                                                <div className="mt-1 flex items-center gap-2 text-[10px] text-gray-500">
                                                    <span className="rounded bg-gray-800 px-1.5 py-0.5 font-medium text-gray-400">{n.source}</span>
                                                    <span>{formatTimeAgo(n.published)}</span>
                                                </div>
                                            </div>
                                            {n.sentiment && n.sentiment !== 'neutral' && (
                                                <span className={`shrink-0 rounded px-1.5 py-0.5 text-[10px] font-semibold ${n.sentiment === 'bullish' ? 'bg-emerald-500/10 text-emerald-400' : 'bg-rose-500/10 text-rose-400'}`}>
                                                    {n.sentiment}
                                                </span>
                                            )}
                                        </div>
                                    </a>
                                </li>
                            ))}
                        </ul>
                    )}
                </Panel>
            </div>

            {/* Quick links */}
            <div className="mt-5 grid grid-cols-2 gap-3 md:grid-cols-4">
                <QuickLink href="/derivatives" label="Derivatives" sub="OI · Funding · L/S" />
                <QuickLink href="/whales" label="Whale Activity" sub="Large orders & flows" />
                <QuickLink href="/signals" label="AI Signals" sub="Buy/sell setups" />
                <QuickLink href="/research" label="Deep Research" sub="Trends & scans" />
            </div>
        </Layout>
    );
}

function Panel({ title, right, children }) {
    return (
        <section className="overflow-hidden rounded-xl border border-gray-800 bg-gray-900/60">
            <header className="flex items-center justify-between border-b border-gray-800 px-4 py-2.5">
                <h2 className="text-xs font-semibold uppercase tracking-wider text-gray-300">{title}</h2>
                {right}
            </header>
            {children}
        </section>
    );
}

function QuickLink({ href, label, sub }) {
    return (
        <Link href={href} className="group block rounded-xl border border-gray-800 bg-gray-900/40 p-4 transition-colors hover:border-emerald-500/40 hover:bg-emerald-500/5">
            <div className="text-sm font-semibold text-white group-hover:text-emerald-300">{label}</div>
            <div className="mt-1 text-xs text-gray-500">{sub}</div>
        </Link>
    );
}
