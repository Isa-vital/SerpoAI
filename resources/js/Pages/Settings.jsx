import Layout from '@/Layouts/Layout';
import { Head } from '@inertiajs/react';
import { AuthRequired } from './Portfolio';

export default function Settings({ requiresAuth, profile }) {
    if (requiresAuth) return <AuthRequired page="Settings" />;

    const p = profile?.profile || {};
    const sub = profile?.subscription || {};
    const activity = profile?.recent_activity || [];

    return (
        <Layout title="Settings">
            <Head title="Settings" />

            <div className="space-y-6">
                {/* Profile Info */}
                <Section title="Profile">
                    <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <SettingRow label="Risk Level" value={p.risk_level || 'moderate'} />
                        <SettingRow label="Trading Style" value={p.trading_style || 'swing'} />
                        <SettingRow label="Timezone" value={p.timezone || 'UTC'} />
                        <SettingRow label="Notifications" value={p.notifications_enabled ? 'Enabled' : 'Disabled'} />
                    </div>
                    {p.favorite_pairs?.length > 0 && (
                        <div className="mt-4">
                            <p className="mb-2 text-xs text-gray-500">Favorite Pairs</p>
                            <div className="flex flex-wrap gap-2">
                                {(Array.isArray(p.favorite_pairs) ? p.favorite_pairs : []).map((pair, i) => (
                                    <span key={i} className="rounded-lg bg-gray-800 px-3 py-1 text-xs text-gray-300">{pair}</span>
                                ))}
                            </div>
                        </div>
                    )}
                </Section>

                {/* Subscription */}
                <Section title="Subscription">
                    <div className="grid grid-cols-1 gap-4 sm:grid-cols-3">
                        <div className="rounded-lg bg-gray-800/50 p-4">
                            <p className="text-xs text-gray-500">Tier</p>
                            <p className={`mt-1 text-lg font-bold ${sub.tier === 'premium' ? 'text-amber-400' : sub.tier === 'elite' ? 'text-purple-400' : 'text-gray-300'}`}>
                                {(sub.tier || 'free').toUpperCase()}
                            </p>
                        </div>
                        <div className="rounded-lg bg-gray-800/50 p-4">
                            <p className="text-xs text-gray-500">Status</p>
                            <p className={`mt-1 text-lg font-bold ${sub.is_active ? 'text-emerald-400' : 'text-gray-500'}`}>
                                {sub.is_active ? 'Active' : 'Inactive'}
                            </p>
                        </div>
                        <div className="rounded-lg bg-gray-800/50 p-4">
                            <p className="text-xs text-gray-500">Expires</p>
                            <p className="mt-1 text-sm font-medium text-gray-300">
                                {sub.expires_at || '—'}
                            </p>
                        </div>
                    </div>
                    <div className="mt-3 grid grid-cols-2 gap-3">
                        <div className="rounded-lg bg-gray-800/30 p-3">
                            <p className="text-xs text-gray-500">Scans Today</p>
                            <p className="text-sm text-gray-300">{sub.scans_today || 0} / {sub.scans_limit || '∞'}</p>
                        </div>
                        <div className="rounded-lg bg-gray-800/30 p-3">
                            <p className="text-xs text-gray-500">Active Alerts</p>
                            <p className="text-sm text-gray-300">{sub.active_alerts || 0} / {sub.alerts_limit || '∞'}</p>
                        </div>
                    </div>
                </Section>

                {/* Recent Activity */}
                {activity.length > 0 && (
                    <Section title="Recent Activity">
                        <div className="space-y-2">
                            {activity.slice(0, 10).map((a, i) => (
                                <div key={i} className="flex items-center justify-between rounded-lg bg-gray-800/30 px-4 py-2">
                                    <div className="flex items-center gap-2">
                                        <span className="rounded bg-gray-700 px-1.5 py-0.5 text-xs text-gray-400">{a.type}</span>
                                        <span className="text-sm text-gray-300">{a.pair}</span>
                                    </div>
                                    <span className="text-xs text-gray-500">{a.time}</span>
                                </div>
                            ))}
                        </div>
                    </Section>
                )}

                {/* Bot Settings Note */}
                <div className="rounded-xl border border-gray-800 bg-gray-900/30 p-4">
                    <p className="text-sm text-gray-400">
                        To change your settings, use Telegram commands:
                        <code className="mx-1 rounded bg-gray-800 px-1 text-emerald-400">/settings</code>
                        <code className="mx-1 rounded bg-gray-800 px-1 text-emerald-400">/language</code>
                        <code className="mx-1 rounded bg-gray-800 px-1 text-emerald-400">/profile</code>
                    </p>
                </div>
            </div>
        </Layout>
    );
}

function Section({ title, children }) {
    return (
        <div className="rounded-xl border border-gray-800 bg-gray-900/50 p-5">
            <h2 className="mb-4 text-sm font-semibold uppercase tracking-wider text-gray-400">{title}</h2>
            {children}
        </div>
    );
}

function SettingRow({ label, value }) {
    return (
        <div className="rounded-lg bg-gray-800/30 p-3">
            <p className="text-xs text-gray-500">{label}</p>
            <p className="mt-1 text-sm font-medium text-gray-300 capitalize">{value}</p>
        </div>
    );
}
