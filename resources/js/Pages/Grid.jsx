import Layout from '@/Layouts/Layout';
import { Head } from '@inertiajs/react';

export default function Grid({ status }) {
    return (
        <Layout title="Grid Bot">
            <Head title="Grid Bot" />

            <div className="flex flex-col items-center justify-center py-16">
                <div className="mb-6 flex h-20 w-20 items-center justify-center rounded-2xl bg-emerald-500/10">
                    <svg className="h-10 w-10 text-emerald-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={1.5} d="M4 5a1 1 0 011-1h14a1 1 0 011 1v2a1 1 0 01-1 1H5a1 1 0 01-1-1V5zm0 8a1 1 0 011-1h6a1 1 0 011 1v6a1 1 0 01-1 1H5a1 1 0 01-1-1v-6zm12 0a1 1 0 011-1h2a1 1 0 011 1v6a1 1 0 01-1 1h-2a1 1 0 01-1-1v-6z" />
                    </svg>
                </div>

                <h2 className="mb-3 text-2xl font-bold text-white">Multi-Market Grid Bot</h2>
                <p className="mb-6 max-w-lg text-center text-sm text-gray-400">
                    Adaptive grid trading across crypto (Binance, KuCoin, Bybit), forex (OANDA, FXCM, MT5),
                    and stocks (Interactive Brokers, Alpaca). Powered by AI signal detection.
                </p>

                <div className="mb-8 rounded-xl border border-amber-500/30 bg-amber-500/5 px-6 py-3">
                    <p className="text-sm text-amber-400">🚧 Coming soon — Phase 5–7 of development</p>
                </div>

                {/* Features preview */}
                <div className="grid w-full max-w-2xl grid-cols-1 gap-3 sm:grid-cols-2">
                    <FeatureCard
                        icon="📊"
                        title="Adaptive Grid Engine"
                        description="ATR-based grid spacing that adjusts to volatility"
                    />
                    <FeatureCard
                        icon="🤖"
                        title="AI Signal Layer"
                        description="Trend detection pauses grid in trending markets"
                    />
                    <FeatureCard
                        icon="🔄"
                        title="8 Exchange Connectors"
                        description="Binance, KuCoin, Bybit, OANDA, FXCM, MT5, IB, Alpaca"
                    />
                    <FeatureCard
                        icon="📈"
                        title="Backtesting Engine"
                        description="Test strategies on historical data before going live"
                    />
                    <FeatureCard
                        icon="🛡️"
                        title="Risk Management"
                        description="Stop-loss, max drawdown limits, position sizing"
                    />
                    <FeatureCard
                        icon="📱"
                        title="Real-time Dashboard"
                        description="Monitor P/L, active grids, and trade history"
                    />
                </div>
            </div>
        </Layout>
    );
}

function FeatureCard({ icon, title, description }) {
    return (
        <div className="rounded-xl border border-gray-800 bg-gray-900/50 p-4">
            <div className="mb-2 text-2xl">{icon}</div>
            <h3 className="text-sm font-medium text-white">{title}</h3>
            <p className="mt-1 text-xs text-gray-500">{description}</p>
        </div>
    );
}
