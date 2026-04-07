import Layout from '@/Layouts/Layout';
import { Head } from '@inertiajs/react';
import { AuthRequired } from './Portfolio';

export default function Alerts({ requiresAuth, alerts }) {
    if (requiresAuth) return <AuthRequired page="Alerts" />;

    const active = alerts?.filter(a => a.is_active) || [];
    const triggered = alerts?.filter(a => !a.is_active && a.triggered_at) || [];

    return (
        <Layout title="Alerts">
            <Head title="Alerts" />

            <div className="mb-4 rounded-xl border border-gray-800 bg-gray-900/50 p-4">
                <p className="text-sm text-gray-400">
                    Manage your price alerts. Create new alerts using <code className="rounded bg-gray-800 px-1 text-emerald-400">/setalert</code> in the Telegram bot.
                </p>
            </div>

            {/* Active Alerts */}
            <h2 className="mb-3 text-sm font-semibold uppercase tracking-wider text-gray-400">
                Active Alerts ({active.length})
            </h2>

            {active.length > 0 ? (
                <div className="space-y-2">
                    {active.map((alert) => (
                        <AlertCard key={alert.id} alert={alert} />
                    ))}
                </div>
            ) : (
                <div className="rounded-xl border border-gray-800 bg-gray-900/30 p-8 text-center">
                    <p className="text-gray-500">No active alerts</p>
                    <p className="mt-1 text-xs text-gray-600">Use /setalert BTC 70000 in Telegram to create one</p>
                </div>
            )}

            {/* Triggered History */}
            {triggered.length > 0 && (
                <div className="mt-8">
                    <h2 className="mb-3 text-sm font-semibold uppercase tracking-wider text-gray-400">
                        Triggered ({triggered.length})
                    </h2>
                    <div className="space-y-2">
                        {triggered.map((alert) => (
                            <AlertCard key={alert.id} alert={alert} triggered />
                        ))}
                    </div>
                </div>
            )}
        </Layout>
    );
}

function AlertCard({ alert, triggered = false }) {
    return (
        <div className={`flex items-center justify-between rounded-xl border px-4 py-3 ${
            triggered ? 'border-gray-800/50 bg-gray-900/20 opacity-60' : 'border-gray-800 bg-gray-900/50'
        }`}>
            <div>
                <p className="text-sm font-medium text-white">{alert.pair || alert.coin_symbol || 'Unknown'}</p>
                <p className="text-xs text-gray-500">
                    {alert.condition === 'above' ? '↑ Above' : '↓ Below'} ${alert.value || alert.target_value}
                    {alert.timeframe && ` • ${alert.timeframe}`}
                </p>
            </div>
            <div className="text-right">
                {triggered ? (
                    <span className="rounded bg-amber-500/10 px-2 py-1 text-xs font-medium text-amber-400">Triggered</span>
                ) : (
                    <span className="rounded bg-emerald-500/10 px-2 py-1 text-xs font-medium text-emerald-400">Active</span>
                )}
                {alert.trigger_count > 0 && (
                    <p className="mt-1 text-xs text-gray-600">× {alert.trigger_count}</p>
                )}
            </div>
        </div>
    );
}
