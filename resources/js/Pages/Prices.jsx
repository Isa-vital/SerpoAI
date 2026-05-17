import Layout from '../Layouts/Layout';
import { Head, Link } from '@inertiajs/react';
import { useMemo, useState } from 'react';
import useLiveData from '../hooks/useLiveData';
import { formatPrice, formatCompact, formatChange, changeColor } from '../lib/format';
import LiveDot from '../Components/LiveDot';
import WatchlistStar from '../Components/WatchlistStar';
import Skeleton from '../Components/Skeleton';

const MARKETS = [
    { key: 'crypto', label: 'Crypto' },
    { key: 'forex', label: 'Forex' },
    { key: 'stocks', label: 'Stocks' },
];

const SORTS = {
    volume_24h: { label: 'Volume', dir: 'desc' },
    change_24h: { label: '% Change', dir: 'desc' },
    price: { label: 'Price', dir: 'desc' },
};

export default function Prices() {
    const [market, setMarket] = useState('crypto');
    const [sort, setSort] = useState('volume_24h');
    const [dir, setDir] = useState('desc');
    const [q, setQ] = useState('');

    const url = `/api/markets/screener?market=${market}&sort=${sort}&dir=${dir}&limit=300${q ? `&q=${encodeURIComponent(q)}` : ''}`;
    const { data, loading } = useLiveData(url, { interval: 20000 });
    const rows = data?.rows || [];

    const toggleSort = (key) => {
        if (sort === key) setDir(dir === 'asc' ? 'desc' : 'asc');
        else { setSort(key); setDir('desc'); }
    };

    return (
        <Layout title="Markets">
            <Head title="Markets — SerpoAI" />

            <div className="mb-4 flex flex-wrap items-center justify-between gap-3">
                <div className="flex items-center gap-2">
                    {MARKETS.map((m) => (
                        <button
                            key={m.key}
                            onClick={() => setMarket(m.key)}
                            className={`rounded-md px-3 py-1.5 text-xs font-semibold transition-colors ${market === m.key ? 'bg-emerald-500/15 text-emerald-300 ring-1 ring-emerald-500/30' : 'bg-gray-800/60 text-gray-400 hover:text-gray-200'}`}
                        >
                            {m.label}
                        </button>
                    ))}
                    <LiveDot />
                </div>
                <div className="flex items-center gap-3">
                    <input
                        value={q}
                        onChange={(e) => setQ(e.target.value)}
                        placeholder="Filter symbol…"
                        className="rounded-md border border-gray-800 bg-gray-900 px-3 py-1.5 text-xs text-gray-200 placeholder-gray-600 focus:border-emerald-500/50 focus:outline-none"
                    />
                    <span className="text-xs text-gray-500">{rows.length} markets</span>
                </div>
            </div>

            <div className="overflow-hidden rounded-xl border border-gray-800 bg-gray-900/60">
                <div className="overflow-x-auto">
                    <table className="w-full text-sm">
                        <thead className="border-b border-gray-800 bg-gray-900/80 text-[11px] uppercase tracking-wider text-gray-500">
                            <tr>
                                <th className="px-3 py-2 text-left">★</th>
                                <th className="px-3 py-2 text-left">Symbol</th>
                                <Th label="Price" k="price" sort={sort} dir={dir} onClick={toggleSort} />
                                <Th label="24h Change" k="change_24h" sort={sort} dir={dir} onClick={toggleSort} />
                                <Th label="24h Volume" k="volume_24h" sort={sort} dir={dir} onClick={toggleSort} />
                                <th className="px-3 py-2 text-right">24h High</th>
                                <th className="px-3 py-2 text-right">24h Low</th>
                                <th className="px-3 py-2 text-right">Action</th>
                            </tr>
                        </thead>
                        <tbody className="divide-y divide-gray-800/70">
                            {loading && rows.length === 0 && (
                                <tr><td colSpan={8} className="p-8"><Skeleton lines={8} /></td></tr>
                            )}
                            {!loading && rows.length === 0 && (
                                <tr><td colSpan={8} className="px-3 py-12 text-center text-xs text-gray-600">No markets match.</td></tr>
                            )}
                            {rows.map((r) => (
                                <tr key={r.symbol} className="transition-colors hover:bg-gray-800/40">
                                    <td className="px-3 py-2"><WatchlistStar symbol={r.symbol} /></td>
                                    <td className="px-3 py-2 font-semibold text-white">
                                        <Link href={`/charts?symbol=${r.symbol}`} className="hover:text-emerald-300">{r.symbol}</Link>
                                        {r.bucket && <span className="ml-2 text-[10px] uppercase text-gray-500">{r.bucket.replace('_', ' ')}</span>}
                                    </td>
                                    <td className="px-3 py-2 text-right font-mono text-white">${formatPrice(r.price)}</td>
                                    <td className={`px-3 py-2 text-right font-mono ${changeColor(r.change_24h)}`}>{formatChange(r.change_24h)}</td>
                                    <td className="px-3 py-2 text-right font-mono text-gray-300">{formatCompact(r.volume_24h)}</td>
                                    <td className="px-3 py-2 text-right font-mono text-gray-400">{r.high_24h ? formatPrice(r.high_24h) : '—'}</td>
                                    <td className="px-3 py-2 text-right font-mono text-gray-400">{r.low_24h ? formatPrice(r.low_24h) : '—'}</td>
                                    <td className="px-3 py-2 text-right">
                                        <Link href={`/charts?symbol=${r.symbol}`} className="rounded bg-emerald-500/10 px-2 py-1 text-[10px] font-semibold uppercase tracking-wider text-emerald-400 hover:bg-emerald-500/20">Trade</Link>
                                    </td>
                                </tr>
                            ))}
                        </tbody>
                    </table>
                </div>
            </div>
        </Layout>
    );
}

function Th({ label, k, sort, dir, onClick }) {
    const active = sort === k;
    return (
        <th className="px-3 py-2 text-right">
            <button onClick={() => onClick(k)} className={`inline-flex items-center gap-1 ${active ? 'text-emerald-400' : 'hover:text-gray-300'}`}>
                {label} {active && (dir === 'asc' ? '↑' : '↓')}
            </button>
        </th>
    );
}
