import Layout from '../Layouts/Layout';
import { Head } from '@inertiajs/react';
import { useMemo, useState } from 'react';
import useLiveData from '../hooks/useLiveData';
import { formatTimeAgo } from '../lib/format';
import LiveDot from '../Components/LiveDot';
import Skeleton from '../Components/Skeleton';

const SENTIMENTS = ['all', 'bullish', 'bearish', 'neutral'];

export default function News() {
    const { data, loading } = useLiveData('/api/markets/news?limit=60', { interval: 60000 });
    const [sentiment, setSentiment] = useState('all');
    const [source, setSource] = useState('all');

    const items = data?.items || [];
    const sources = useMemo(() => ['all', ...Array.from(new Set(items.map((i) => i.source).filter(Boolean)))], [items]);
    const filtered = items.filter((i) => (sentiment === 'all' || i.sentiment === sentiment) && (source === 'all' || i.source === source));

    return (
        <Layout title="News">
            <Head title="News — SerpoAI" />

            <div className="mb-4 flex flex-wrap items-center justify-between gap-3">
                <div className="flex items-center gap-3">
                    <h2 className="text-xs font-semibold uppercase tracking-wider text-gray-300">Live News Feed</h2>
                    <LiveDot />
                </div>
                <div className="flex flex-wrap items-center gap-2">
                    <select value={source} onChange={(e) => setSource(e.target.value)} className="rounded border border-gray-800 bg-gray-900 px-2 py-1 text-xs text-gray-200">
                        {sources.map((s) => <option key={s} value={s}>{s}</option>)}
                    </select>
                    <div className="flex gap-1">
                        {SENTIMENTS.map((s) => (
                            <button key={s} onClick={() => setSentiment(s)} className={`rounded px-2 py-1 text-[11px] font-semibold capitalize ${sentiment === s ? 'bg-emerald-500/15 text-emerald-300' : 'bg-gray-800 text-gray-400'}`}>{s}</button>
                        ))}
                    </div>
                </div>
            </div>

            {loading && items.length === 0 && <Skeleton className="h-64" />}

            <div className="grid gap-3 md:grid-cols-2 xl:grid-cols-3">
                {filtered.map((n) => (
                    <a key={n.id} href={n.url} target="_blank" rel="noopener noreferrer" className="group block rounded-xl border border-gray-800 bg-gray-900/60 p-4 transition-colors hover:border-emerald-500/30 hover:bg-gray-900">
                        <div className="flex items-center justify-between text-[10px] uppercase tracking-wider">
                            <span className="rounded bg-gray-800 px-2 py-0.5 font-semibold text-gray-300">{n.source}</span>
                            {n.sentiment && n.sentiment !== 'neutral' && (
                                <span className={`rounded px-2 py-0.5 font-semibold ${n.sentiment === 'bullish' ? 'bg-emerald-500/10 text-emerald-400' : 'bg-rose-500/10 text-rose-400'}`}>{n.sentiment}</span>
                            )}
                        </div>
                        <h3 className="mt-2 text-sm font-semibold leading-snug text-white group-hover:text-emerald-300">{n.title}</h3>
                        {n.description && <p className="mt-2 line-clamp-3 text-xs text-gray-500">{n.description}</p>}
                        <div className="mt-3 flex items-center justify-between text-[10px] text-gray-500">
                            <span>{formatTimeAgo(n.published)}</span>
                            {n.tags && n.tags.length > 0 && <span className="font-mono text-gray-400">{n.tags.slice(0, 3).join(' · ')}</span>}
                        </div>
                    </a>
                ))}
            </div>

            {!loading && filtered.length === 0 && (
                <div className="rounded-xl border border-gray-800 bg-gray-900/60 p-12 text-center text-xs text-gray-600">No news matches.</div>
            )}
        </Layout>
    );
}
