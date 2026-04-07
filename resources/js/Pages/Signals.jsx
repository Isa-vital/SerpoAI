import Layout from '@/Layouts/Layout';
import { Head, router, useForm, usePage } from '@inertiajs/react';
import { useState } from 'react';

export default function Signals({ recentSignals, symbol }) {
    const { flash } = usePage().props;
    const [currentSymbol, setCurrentSymbol] = useState(symbol || 'BTC');
    const { post, processing } = useForm({});

    const handleGenerate = (e) => {
        e.preventDefault();
        router.post('/signals/generate', { symbol: currentSymbol }, { preserveState: true });
    };

    const liveSignal = flash?.signal;

    return (
        <Layout title="Signals">
            <Head title="Signals" />

            {/* Generate Signal */}
            <form onSubmit={handleGenerate} className="mb-6">
                <label className="mb-2 block text-sm text-gray-400">Generate an AI-powered trading signal</label>
                <div className="flex gap-3">
                    <input
                        type="text"
                        value={currentSymbol}
                        onChange={(e) => setCurrentSymbol(e.target.value.toUpperCase())}
                        placeholder="Enter symbol (e.g. BTC, ETH, AAPL)"
                        className="flex-1 rounded-xl border border-gray-700 bg-gray-900 px-4 py-3 text-white placeholder-gray-500 focus:border-emerald-500 focus:outline-none"
                    />
                    <button
                        type="submit"
                        disabled={processing}
                        className="rounded-xl bg-rose-600 px-6 py-3 text-sm font-medium text-white hover:bg-rose-500 disabled:opacity-50"
                    >
                        {processing ? 'Generating...' : 'Generate Signal'}
                    </button>
                </div>
            </form>

            {/* Live Signal Result */}
            {liveSignal && !liveSignal.error && (
                <div className={`mb-6 rounded-xl border p-5 ${getSignalBorder(liveSignal.signal)}`}>
                    <div className="flex items-center justify-between">
                        <div>
                            <p className="text-sm text-gray-400">{liveSignal.symbol} • {liveSignal.market_type}</p>
                            <p className={`text-3xl font-bold ${getSignalColor(liveSignal.signal)}`}>
                                {liveSignal.signal}
                            </p>
                        </div>
                        <div className="text-right">
                            <p className="text-2xl font-bold text-white">{liveSignal.confidence}%</p>
                            <p className="text-xs text-gray-500">Confidence</p>
                        </div>
                    </div>
                    <p className="mt-3 text-sm text-gray-300">{liveSignal.reasoning}</p>
                    {liveSignal.key_factors?.length > 0 && (
                        <div className="mt-3 flex flex-wrap gap-1.5">
                            {liveSignal.key_factors.map((f, i) => (
                                <span key={i} className="rounded bg-gray-800 px-2 py-1 text-xs text-gray-400">{f}</span>
                            ))}
                        </div>
                    )}
                    <div className="mt-3 flex gap-4 text-xs text-gray-500">
                        <span>Price: ${Number(liveSignal.price).toLocaleString()}</span>
                        <span>Risk: {liveSignal.risk_level}</span>
                    </div>
                </div>
            )}

            {/* Recent Signals */}
            <h2 className="mb-3 text-sm font-semibold uppercase tracking-wider text-gray-400">
                Recent Signals ({recentSignals?.length || 0})
            </h2>

            {recentSignals?.length > 0 ? (
                <div className="space-y-2">
                    {recentSignals.map((sig) => (
                        <div key={sig.id} className="flex items-center justify-between rounded-xl border border-gray-800 bg-gray-900/50 px-4 py-3">
                            <div>
                                <p className="text-sm font-medium text-white">{sig.coin_symbol}</p>
                                <p className="text-xs text-gray-500">{sig.indicator} • {sig.signal_type}</p>
                            </div>
                            <div className="flex items-center gap-3">
                                <span className={`rounded px-2 py-1 text-xs font-medium ${getSignalBadge(sig.signal_type)}`}>
                                    {sig.signal_type}
                                </span>
                                <span className="text-sm text-gray-400">{sig.confidence}%</span>
                            </div>
                        </div>
                    ))}
                </div>
            ) : (
                <div className="rounded-xl border border-gray-800 bg-gray-900/30 p-8 text-center">
                    <p className="text-gray-500">No signals yet. Generate one above!</p>
                </div>
            )}
        </Layout>
    );
}

function getSignalColor(signal) {
    if (signal === 'BUY') return 'text-emerald-400';
    if (signal === 'SELL') return 'text-red-400';
    return 'text-amber-400';
}

function getSignalBorder(signal) {
    if (signal === 'BUY') return 'border-emerald-500/30 bg-emerald-500/5';
    if (signal === 'SELL') return 'border-red-500/30 bg-red-500/5';
    return 'border-amber-500/30 bg-amber-500/5';
}

function getSignalBadge(type) {
    const t = type?.toUpperCase();
    if (t === 'BUY' || t === 'BULLISH') return 'bg-emerald-500/10 text-emerald-400';
    if (t === 'SELL' || t === 'BEARISH') return 'bg-red-500/10 text-red-400';
    return 'bg-amber-500/10 text-amber-400';
}
