import Layout from '../Layouts/Layout';
import { Head, Link } from '@inertiajs/react';
import useLiveData from '../hooks/useLiveData';
import { formatPrice, formatCompact, formatTimeAgo } from '../lib/format';
import LiveDot from '../Components/LiveDot';
import Skeleton from '../Components/Skeleton';
import { useState } from 'react';

const PRESETS = ['BTC,ETH,SOL,BNB', 'BTC,ETH', 'BTC,ETH,SOL,BNB,XRP,DOGE,AVAX,LINK'];

export default function Whales() {
    const [symbols, setSymbols] = useState(PRESETS[0]);
    const { data, loading } = useLiveData(`/api/markets/whales?symbols=${symbols}`, { interval: 30000 });
    const orders = data?.orders || [];

    return (
        <Layout title="Whale Tracker">
            <Head title="Whales — SerpoAI" />

            <div className="mb-4 flex items-center justify-between">
                <div className="flex items-center gap-3">
                    <h2 className="text-xs font-semibold uppercase tracking-wider text-gray-300">Large Order Book Walls (&gt; $100k)</h2>
                    <LiveDot />
                </div>
                <div className="flex items-center gap-2">
                    <select value={symbols} onChange={(e) => setSymbols(e.target.value)} className="rounded border border-gray-800 bg-gray-900 px-2 py-1 text-xs text-gray-200">
                        {PRESETS.map((p) => <option key={p} value={p}>{p}</option>)}
                    </select>
                </div>
            </div>

            <div className="overflow-hidden rounded-xl border border-gray-800 bg-gray-900/60">
                <div className="overflow-x-auto">
                    <table className="w-full text-sm">
                        <thead className="border-b border-gray-800 text-[11px] uppercase tracking-wider text-gray-500">
                            <tr>
                                <th className="px-3 py-2 text-left">Symbol</th>
                                <th className="px-3 py-2 text-left">Side</th>
                                <th className="px-3 py-2 text-right">Price</th>
                                <th className="px-3 py-2 text-right">Quantity</th>
                                <th className="px-3 py-2 text-right">USD Value</th>
                                <th className="px-3 py-2 text-right">Distance</th>
                                <th className="px-3 py-2 text-right">Detected</th>
                            </tr>
                        </thead>
                        <tbody className="divide-y divide-gray-800/70">
                            {loading && orders.length === 0 && (<tr><td colSpan={7} className="p-6"><Skeleton lines={6} /></td></tr>)}
                            {orders.map((o, i) => (
                                <tr key={i} className="hover:bg-gray-800/40">
                                    <td className="px-3 py-2 font-semibold text-white"><Link href={`/charts?symbol=${o.symbol}`} className="hover:text-emerald-300">{o.symbol}</Link></td>
                                    <td className={`px-3 py-2 text-xs font-semibold uppercase ${o.side === 'bid' ? 'text-emerald-400' : 'text-rose-400'}`}>{o.side === 'bid' ? 'Buy Wall' : 'Sell Wall'}</td>
                                    <td className="px-3 py-2 text-right font-mono text-white">${formatPrice(o.price)}</td>
                                    <td className="px-3 py-2 text-right font-mono text-gray-300">{formatCompact(o.quantity)}</td>
                                    <td className="px-3 py-2 text-right font-mono text-amber-300">${formatCompact(o.value)}</td>
                                    <td className="px-3 py-2 text-right font-mono text-gray-400">{o.distance_pct ? o.distance_pct.toFixed(2) + '%' : '—'}</td>
                                    <td className="px-3 py-2 text-right text-xs text-gray-500">{formatTimeAgo(o.detected_at)}</td>
                                </tr>
                            ))}
                            {!loading && orders.length === 0 && (<tr><td colSpan={7} className="py-12 text-center text-xs text-gray-600">No whale activity detected.</td></tr>)}
                        </tbody>
                    </table>
                </div>
            </div>
        </Layout>
    );
}
