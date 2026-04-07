import Layout from '@/Layouts/Layout';
import { Head, Link } from '@inertiajs/react';

export default function Dashboard({ stats }) {
    return (
        <Layout title="Dashboard">
            <Head title="Dashboard" />

            {/* Stats grid */}
            <div className="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
                <StatCard
                    label="SERPO Price"
                    value={stats?.serpoPrice ?? '—'}
                    change={stats?.serpoChange ?? null}
                    prefix="$"
                />
                <StatCard
                    label="BTC Price"
                    value={stats?.btcPrice ?? '—'}
                    change={stats?.btcChange ?? null}
                    prefix="$"
                />
                <StatCard
                    label="Active Alerts"
                    value={stats?.activeAlerts ?? 0}
                />
                <StatCard
                    label="Bot Commands Used"
                    value={stats?.totalCommands ?? '40+'}
                />
            </div>

            {/* Quick actions */}
            <div className="mt-8">
                <h2 className="mb-4 text-sm font-semibold uppercase tracking-wider text-gray-400">
                    Quick Actions
                </h2>
                <div className="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-3">
                    <QuickAction
                        title="Check SERPO Price"
                        description="Get real-time SERPO token price from DexScreener"
                        href="/prices"
                        color="emerald"
                    />
                    <QuickAction
                        title="AI Market Analysis"
                        description="Get AI-powered analysis using GPT-4, Gemini, or Groq"
                        href="/ai"
                        color="blue"
                    />
                    <QuickAction
                        title="Set Price Alert"
                        description="Get notified when a token hits your target price"
                        href="/alerts"
                        color="amber"
                    />
                    <QuickAction
                        title="Whale Tracker"
                        description="Monitor large transactions on the TON blockchain"
                        href="/whales"
                        color="purple"
                    />
                    <QuickAction
                        title="Verify Token"
                        description="Check token legitimacy across 20+ chains"
                        href="/verify"
                        color="cyan"
                    />
                    <QuickAction
                        title="Trading Signals"
                        description="RSI, MACD, EMA and divergence indicators"
                        href="/signals"
                        color="rose"
                    />
                </div>
            </div>

            {/* Feature overview */}
            <div className="mt-8 rounded-xl border border-gray-800 bg-gray-900/50 p-6">
                <h2 className="mb-2 text-lg font-semibold text-white">Welcome to SerpoAI</h2>
                <p className="text-sm text-gray-400">
                    SerpoAI is an AI-powered trading platform for the Serpocoin ecosystem.
                    All 40+ features from the Telegram bot are available here on the web.
                    Log in with your Telegram account to sync your data, alerts, and preferences.
                </p>
            </div>
        </Layout>
    );
}

function StatCard({ label, value, change, prefix = '' }) {
    const num = change !== null && change !== undefined ? parseFloat(change) : NaN;
    const hasChange = !isNaN(num);
    const isPositive = hasChange && num >= 0;

    const formatValue = (v) => {
        if (v === '—' || v === null || v === undefined) return '—';
        const n = Number(v);
        if (isNaN(n)) return v;
        if (n >= 1000) return n.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        if (n >= 1) return n.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 4 });
        return n.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 8 });
    };

    const display = formatValue(value);

    return (
        <div className="rounded-xl border border-gray-800 bg-gray-900/50 p-5">
            <p className="text-sm text-gray-400">{label}</p>
            <p className="mt-1 text-2xl font-bold text-white">
                {display !== '—' ? prefix : ''}{display}
            </p>
            {hasChange && (
                <p className={`mt-1 text-sm font-medium ${isPositive ? 'text-emerald-400' : 'text-red-400'}`}>
                    {isPositive ? '↑' : '↓'} {Math.abs(num).toFixed(2)}%
                </p>
            )}
        </div>
    );
}

function QuickAction({ title, description, href, color }) {
    const colorMap = {
        emerald: 'bg-emerald-500/10 text-emerald-400 hover:bg-emerald-500/20',
        blue: 'bg-blue-500/10 text-blue-400 hover:bg-blue-500/20',
        amber: 'bg-amber-500/10 text-amber-400 hover:bg-amber-500/20',
        purple: 'bg-purple-500/10 text-purple-400 hover:bg-purple-500/20',
        cyan: 'bg-cyan-500/10 text-cyan-400 hover:bg-cyan-500/20',
        rose: 'bg-rose-500/10 text-rose-400 hover:bg-rose-500/20',
    };

    return (
        <Link
            href={href}
            className={`block rounded-xl border border-gray-800 p-4 transition-colors ${colorMap[color] ?? colorMap.emerald}`}
        >
            <h3 className="font-medium">{title}</h3>
            <p className="mt-1 text-xs text-gray-500">{description}</p>
        </Link>
    );
}
