import Layout from '../Layouts/Layout';
import { Head, Link } from '@inertiajs/react';
import { useMemo, useState } from 'react';
import useLiveData from '../hooks/useLiveData';
import { formatPrice, formatCompact, formatChange, changeColor } from '../lib/format';
import WatchlistStar from '../Components/WatchlistStar';
import Skeleton from '../Components/Skeleton';

const MARKETS = ['crypto', 'forex', 'stocks'];

export default function Screener({ initialMarket = 'crypto' }) {
    const [market, setMarket] = useState(initialMarket);
    const [minVol, setMinVol] = useState(0);
    const [minChange, setMinChange] = useState(-100);
    const [maxChange, setMaxChange] = useState(100);
    const [q, setQ] = useState('');
    const [sort, setSort] = useState('volume_24h');
    const [dir, setDir] = useState('desc');

    const url = `/api/markets/screener?market=${market}&sort=${sort}&dir=${dir}&limit=500`;
    const { data, loading } = useLiveData(url, { interval: 20000 });

    const rows = useMemo(() => {
        let out = data?.rows || [];
        if (q) {
            const Q = q.toUpperCase();
            out = out.filter((r) => (r.symbol || '').toUpperCase().includes(Q));
        }
        out = out.filter((r) => (r.volume_24h || 0) >= Number(minVol));
        out = out.filter((r) => {
            const c = Number(r.change_24h || 0);
            return c >= Number(minChange) && c <= Number(maxChange);
        });
        return out;
    }, [data, q, minVol, minChange, maxChange]);

    return (
        <Layout title="Screener">
            <Head title="Screener — SerpoAI" />

            <div className="mb-4 grid gap-3 rounded-xl border border-gray-800 bg-gray-900/60 p-4 md:grid-cols-5">
                <div>
                    <Label>Market</Label>
                    <div className="mt-1 flex gap-1">
                        {MARKETS.map((m) => (
                            <button key={m} onClick={() => setMarket(m)} className={`flex-1 rounded px-2 py-1 text-xs font-semibold capitalize ${market === m ? 'bg-emerald-500/15 text-emerald-300 ring-1 ring-emerald-500/30' : 'bg-gray-800 text-gray-400'}`}>{m}</button>
                        ))}
                    </div>
                </div>
                <Field label="Symbol contains">
                    <input value={q} onChange={(e) => setQ(e.target.value)} className={inputCls} placeholder="BTC, ETH…" />
                </Field>
                <Field label="Min 24h Volume">
                    <input type="number" value={minVol} onChange={(e) => setMinVol(e.target.value)} className={inputCls} />
                </Field>
                <Field label="Min 24h %">
                    <input type="number" value={minChange} onChange={(e) => setMinChange(e.target.value)} className={inputCls} />
                </Field>
                <Field label="Max 24h %">
                    <input type="number" value={maxChange} onChange={(e) => setMaxChange(e.target.value)} className={inputCls} />
                </Field>
            </div>

            <div className="mb-3 flex items-center justify-between">
                <div className="text-xs text-gray-500">{loading ? 'Loading…' : `${rows.length} matches`}</div>
                <div className="flex items-center gap-2 text-xs">
                    <span className="text-gray-500">Sort:</span>
                    <select value={sort} onChange={(e) => setSort(e.target.value)} className="rounded border border-gray-800 bg-gray-900 px-2 py-1 text-xs text-gray-200">
                        <option value="volume_24h">Volume</option>
                        <option value="change_24h">% Change</option>
                        <option value="price">Price</option>
                    </select>
                    <button onClick={() => setDir(dir === 'asc' ? 'desc' : 'asc')} className="rounded border border-gray-800 bg-gray-900 px-2 py-1 text-xs text-gray-200">{dir === 'asc' ? '↑' : '↓'}</button>
                </div>
            </div>

            <div className="overflow-hidden rounded-xl border border-gray-800 bg-gray-900/60">
                <div className="overflow-x-auto">
                    <table className="w-full text-sm">
                        <thead className="border-b border-gray-800 text-[11px] uppercase tracking-wider text-gray-500">
                            <tr>
                                <th className="px-3 py-2 text-left">★</th>
                                <th className="px-3 py-2 text-left">Symbol</th>
                                <th className="px-3 py-2 text-right">Price</th>
                                <th className="px-3 py-2 text-right">24h Δ</th>
                                <th className="px-3 py-2 text-right">Volume</th>
                                <th className="px-3 py-2 text-right">High</th>
                                <th className="px-3 py-2 text-right">Low</th>
                            </tr>
                        </thead>
                        <tbody className="divide-y divide-gray-800/70">
                            {loading && rows.length === 0 && (<tr><td colSpan={7} className="p-6"><Skeleton lines={8} /></td></tr>)}
                            {rows.map((r) => (
                                <tr key={r.symbol} className="hover:bg-gray-800/40">
                                    <td className="px-3 py-2"><WatchlistStar symbol={r.symbol} /></td>
                                    <td className="px-3 py-2 font-semibold text-white"><Link href={`/charts?symbol=${r.symbol}`} className="hover:text-emerald-300">{r.symbol}</Link></td>
                                    <td className="px-3 py-2 text-right font-mono text-white">${formatPrice(r.price)}</td>
                                    <td className={`px-3 py-2 text-right font-mono ${changeColor(r.change_24h)}`}>{formatChange(r.change_24h)}</td>
                                    <td className="px-3 py-2 text-right font-mono text-gray-300">{formatCompact(r.volume_24h)}</td>
                                    <td className="px-3 py-2 text-right font-mono text-gray-400">{r.high_24h ? formatPrice(r.high_24h) : '—'}</td>
                                    <td className="px-3 py-2 text-right font-mono text-gray-400">{r.low_24h ? formatPrice(r.low_24h) : '—'}</td>
                                </tr>
                            ))}
                            {!loading && rows.length === 0 && (<tr><td colSpan={7} className="py-12 text-center text-xs text-gray-600">No matches.</td></tr>)}
                        </tbody>
                    </table>
                </div>
            </div>
        </Layout>
    );
}

const inputCls = 'mt-1 w-full rounded border border-gray-800 bg-gray-900 px-2 py-1.5 text-xs text-gray-200 placeholder-gray-600 focus:border-emerald-500/50 focus:outline-none';
function Label({ children }) { return <label className="text-[10px] font-semibold uppercase tracking-wider text-gray-500">{children}</label>; }
function Field({ label, children }) { return <div><Label>{label}</Label>{children}</div>; }
