import Layout from '../Layouts/Layout';
import { Head, Link } from '@inertiajs/react';
import useLiveData from '../hooks/useLiveData';
import { formatPrice, formatCompact, formatChange, changeColor, formatTimeAgo } from '../lib/format';
import LiveDot from '../Components/LiveDot';
import Skeleton from '../Components/Skeleton';

export default function Derivatives() {
    const { data, loading } = useLiveData('/api/markets/derivatives?limit=40', { interval: 30000 });
    const rows = data?.rows || [];
    const extremes = data?.extremes || {};

    return (
        <Layout title="Derivatives">
            <Head title="Derivatives — SerpoAI" />

            <div className="mb-4 flex items-center justify-between">
                <div className="flex items-center gap-3">
                    <h2 className="text-xs font-semibold uppercase tracking-wider text-gray-300">Funding · Open Interest · Long/Short</h2>
                    <LiveDot />
                </div>
                <span className="text-xs text-gray-500">{data ? `Updated ${formatTimeAgo(data.updated_at)}` : ''}</span>
            </div>

            <div className="mb-4 grid gap-3 lg:grid-cols-2">
                <Panel title="🟢 Most Long-Heavy (positive funding)">
                    <Mini rows={extremes.most_long} positive />
                </Panel>
                <Panel title="🔴 Most Short-Heavy (negative funding)">
                    <Mini rows={extremes.most_short} positive={false} />
                </Panel>
            </div>

            <Panel title="All USDT-Perp Markets">
                <div className="overflow-x-auto">
                    <table className="w-full text-sm">
                        <thead className="border-b border-gray-800 text-[11px] uppercase tracking-wider text-gray-500">
                            <tr>
                                <th className="px-3 py-2 text-left">Symbol</th>
                                <th className="px-3 py-2 text-right">Price</th>
                                <th className="px-3 py-2 text-right">24h %</th>
                                <th className="px-3 py-2 text-right">24h Vol</th>
                                <th className="px-3 py-2 text-right">Open Interest</th>
                                <th className="px-3 py-2 text-right">Funding</th>
                                <th className="px-3 py-2 text-right">Action</th>
                            </tr>
                        </thead>
                        <tbody className="divide-y divide-gray-800/70">
                            {loading && rows.length === 0 && (
                                <tr><td colSpan={7} className="p-6"><Skeleton lines={8} /></td></tr>
                            )}
                            {rows.map((r) => (
                                <tr key={r.symbol} className="hover:bg-gray-800/40">
                                    <td className="px-3 py-2 font-semibold text-white">
                                        <Link href={`/charts?symbol=${r.symbol}`} className="hover:text-emerald-300">{r.symbol}</Link>
                                    </td>
                                    <td className="px-3 py-2 text-right font-mono text-white">${formatPrice(r.price)}</td>
                                    <td className={`px-3 py-2 text-right font-mono ${changeColor(r.change_24h)}`}>{formatChange(r.change_24h)}</td>
                                    <td className="px-3 py-2 text-right font-mono text-gray-300">{formatCompact(r.volume_24h)}</td>
                                    <td className="px-3 py-2 text-right font-mono text-gray-300">{formatCompact(r.open_interest)}</td>
                                    <td className={`px-3 py-2 text-right font-mono ${changeColor(r.funding_rate)}`}>
                                        {r.funding_rate !== null ? (r.funding_rate * 100).toFixed(4) + '%' : '—'}
                                    </td>
                                    <td className="px-3 py-2 text-right">
                                        <Link href={`/charts?symbol=${r.symbol}`} className="rounded bg-emerald-500/10 px-2 py-1 text-[10px] font-semibold uppercase tracking-wider text-emerald-400 hover:bg-emerald-500/20">Chart</Link>
                                    </td>
                                </tr>
                            ))}
                        </tbody>
                    </table>
                </div>
            </Panel>
        </Layout>
    );
}

function Panel({ title, children }) {
    return (
        <section className="overflow-hidden rounded-xl border border-gray-800 bg-gray-900/60">
            <header className="border-b border-gray-800 px-4 py-2.5">
                <h2 className="text-xs font-semibold uppercase tracking-wider text-gray-300">{title}</h2>
            </header>
            {children}
        </section>
    );
}

function Mini({ rows = [], positive }) {
    if (!rows || rows.length === 0) return <div className="p-4 text-xs text-gray-600">No data.</div>;
    return (
        <ul className="divide-y divide-gray-800/70">
            {rows.map((r) => (
                <li key={r.symbol} className="flex items-center justify-between px-4 py-2.5">
                    <Link href={`/charts?symbol=${r.symbol}`} className="text-sm font-semibold text-white hover:text-emerald-300">{r.symbol}</Link>
                    <div className="flex items-center gap-3 font-mono text-xs">
                        <span className="text-gray-400">${formatPrice(r.price)}</span>
                        <span className={positive ? 'text-emerald-400' : 'text-rose-400'}>{(r.funding_rate * 100).toFixed(4)}%</span>
                    </div>
                </li>
            ))}
        </ul>
    );
}
