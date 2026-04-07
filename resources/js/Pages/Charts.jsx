import Layout from '@/Layouts/Layout';
import { Head, router } from '@inertiajs/react';
import { useState } from 'react';

export default function Charts({ symbol, modes }) {
    const [currentSymbol, setCurrentSymbol] = useState(symbol || 'BTCUSDT');
    const [activeMode, setActiveMode] = useState('intraday');

    const handleSymbolChange = (e) => {
        e.preventDefault();
        router.get('/charts', { symbol: currentSymbol }, { preserveState: true });
    };

    const currentMode = modes?.[activeMode];

    return (
        <Layout title="Charts">
            <Head title="Charts" />

            {/* Symbol Search */}
            <form onSubmit={handleSymbolChange} className="mb-6">
                <div className="flex gap-3">
                    <input
                        type="text"
                        value={currentSymbol}
                        onChange={(e) => setCurrentSymbol(e.target.value.toUpperCase())}
                        placeholder="Enter symbol (e.g. BTCUSDT, AAPL, EURUSD)"
                        className="flex-1 rounded-xl border border-gray-700 bg-gray-900 px-4 py-3 text-white placeholder-gray-500 focus:border-emerald-500 focus:outline-none"
                    />
                    <button type="submit" className="rounded-xl bg-emerald-600 px-6 py-3 text-sm font-medium text-white hover:bg-emerald-500">
                        Load Chart
                    </button>
                </div>
            </form>

            {/* Mode tabs */}
            <div className="mb-4 flex gap-2">
                {Object.keys(modes || {}).map((mode) => (
                    <button
                        key={mode}
                        onClick={() => setActiveMode(mode)}
                        className={`rounded-lg px-4 py-2 text-sm font-medium transition-colors ${
                            activeMode === mode
                                ? 'bg-emerald-500/20 text-emerald-400'
                                : 'bg-gray-800 text-gray-400 hover:text-white'
                        }`}
                    >
                        {mode.charAt(0).toUpperCase() + mode.slice(1)}
                    </button>
                ))}
            </div>

            {/* Chart Embed */}
            {currentMode?.url ? (
                <div className="overflow-hidden rounded-xl border border-gray-800">
                    <div className="bg-gray-900/50 px-4 py-2 text-xs text-gray-400">
                        {currentMode.description || `${currentMode.symbol} — ${currentMode.interval}`}
                        <span className="ml-2 text-gray-600">• {currentMode.market_type}</span>
                    </div>
                    <iframe
                        src={currentMode.url}
                        className="h-[500px] w-full border-0"
                        title={`${symbol} chart`}
                        sandbox="allow-scripts allow-same-origin"
                    />
                </div>
            ) : (
                <div className="rounded-xl border border-gray-800 bg-gray-900/30 p-12 text-center">
                    <div className="mb-4 text-4xl">📊</div>
                    <h3 className="mb-2 text-lg font-semibold text-white">TradingView Charts</h3>
                    <p className="text-sm text-gray-500">Enter a symbol above to load interactive charts with scalp, intraday, and swing modes.</p>
                </div>
            )}

            {/* Quick Symbols */}
            <div className="mt-6">
                <h3 className="mb-3 text-sm font-semibold uppercase tracking-wider text-gray-400">Quick Load</h3>
                <div className="flex flex-wrap gap-2">
                    {['BTCUSDT', 'ETHUSDT', 'SOLUSDT', 'BNBUSDT', 'XRPUSDT', 'AAPL', 'TSLA', 'EURUSD'].map((s) => (
                        <button
                            key={s}
                            onClick={() => { setCurrentSymbol(s); router.get('/charts', { symbol: s }, { preserveState: true }); }}
                            className="rounded-lg border border-gray-700 bg-gray-900 px-3 py-1.5 text-xs text-gray-400 hover:border-emerald-500 hover:text-emerald-400"
                        >
                            {s}
                        </button>
                    ))}
                </div>
            </div>
        </Layout>
    );
}
