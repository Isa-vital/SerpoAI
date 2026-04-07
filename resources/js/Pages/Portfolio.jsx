import Layout from '@/Layouts/Layout';
import { Head, usePage } from '@inertiajs/react';

export default function Portfolio({ requiresAuth, wallets, trades, watchlist }) {
    if (requiresAuth) return <AuthRequired page="Portfolio" />;

    return (
        <Layout title="Portfolio">
            <Head title="Portfolio" />

            {/* Wallet Summary */}
            <Section title="Wallet Holdings">
                <div className="grid grid-cols-1 gap-4 sm:grid-cols-3">
                    <StatBox label="Total Value" value={`$${Number(wallets?.total_usd_value || 0).toLocaleString()}`} />
                    <StatBox label="SERPO Balance" value={Number(wallets?.total_balance || 0).toLocaleString()} />
                    <StatBox label="Wallets" value={wallets?.wallet_count || 0} />
                </div>

                {wallets?.wallets?.length > 0 && (
                    <div className="mt-4 space-y-2">
                        {wallets.wallets.map((w, i) => (
                            <div key={i} className="flex items-center justify-between rounded-lg border border-gray-800 bg-gray-900/30 px-4 py-3">
                                <div>
                                    <p className="text-sm font-medium text-white">{w.label || 'Wallet'}</p>
                                    <p className="text-xs text-gray-500 font-mono">{w.wallet_address?.slice(0, 12)}...{w.wallet_address?.slice(-6)}</p>
                                </div>
                                <div className="text-right">
                                    <p className="text-sm text-white">{Number(w.balance || 0).toLocaleString()} SERPO</p>
                                    <p className="text-xs text-gray-500">${Number(w.usd_value || 0).toLocaleString()}</p>
                                </div>
                            </div>
                        ))}
                    </div>
                )}
            </Section>

            {/* Paper Trading */}
            <Section title="Paper Trading" className="mt-6">
                <div className="grid grid-cols-2 gap-4 sm:grid-cols-4">
                    <StatBox label="Open Positions" value={trades?.open_count || 0} />
                    <StatBox label="Total P&L" value={`$${Number(trades?.total_pnl || 0).toFixed(2)}`} color={trades?.total_pnl >= 0 ? 'emerald' : 'red'} />
                    <StatBox label="Win Rate" value={`${Number(trades?.win_rate || 0).toFixed(1)}%`} />
                    <StatBox label="Total Trades" value={trades?.total_trades || 0} />
                </div>

                {trades?.open_positions?.length > 0 && (
                    <div className="mt-4 overflow-x-auto rounded-lg border border-gray-800">
                        <table className="w-full text-sm">
                            <thead>
                                <tr className="border-b border-gray-800 bg-gray-900/50">
                                    <th className="px-3 py-2 text-left text-gray-400">Symbol</th>
                                    <th className="px-3 py-2 text-gray-400">Side</th>
                                    <th className="px-3 py-2 text-right text-gray-400">Entry</th>
                                    <th className="px-3 py-2 text-right text-gray-400">Current</th>
                                    <th className="px-3 py-2 text-right text-gray-400">P&L</th>
                                </tr>
                            </thead>
                            <tbody>
                                {trades.open_positions.map((pos, i) => (
                                    <tr key={i} className="border-b border-gray-800/50">
                                        <td className="px-3 py-2 font-medium text-white">{pos.symbol}</td>
                                        <td className={`px-3 py-2 text-center ${pos.side === 'long' ? 'text-emerald-400' : 'text-red-400'}`}>
                                            {pos.side?.toUpperCase()}
                                        </td>
                                        <td className="px-3 py-2 text-right text-gray-300">${Number(pos.entry_price).toFixed(4)}</td>
                                        <td className="px-3 py-2 text-right text-gray-300">${Number(pos.current_price).toFixed(4)}</td>
                                        <td className={`px-3 py-2 text-right font-medium ${pos.unrealized_pnl >= 0 ? 'text-emerald-400' : 'text-red-400'}`}>
                                            {pos.unrealized_pnl >= 0 ? '+' : ''}{Number(pos.unrealized_pnl_pct).toFixed(2)}%
                                        </td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>
                )}
            </Section>

            {/* Watchlist */}
            <Section title="Watchlist" className="mt-6">
                {watchlist?.length > 0 ? (
                    <div className="space-y-2">
                        {watchlist.map((item, i) => (
                            <div key={i} className="flex items-center justify-between rounded-lg border border-gray-800 bg-gray-900/30 px-4 py-3">
                                <div>
                                    <p className="text-sm font-medium text-white">{item.symbol}</p>
                                    <span className="rounded bg-gray-800 px-1.5 py-0.5 text-xs text-gray-400">{item.market_type}</span>
                                </div>
                                <div className="text-right">
                                    <p className="text-sm text-white">${Number(item.last_price || 0).toLocaleString()}</p>
                                    <PriceChange value={item.price_change_24h} />
                                </div>
                            </div>
                        ))}
                    </div>
                ) : (
                    <p className="text-sm text-gray-500">No watchlist items yet. Use /watchlist in the Telegram bot to add symbols.</p>
                )}
            </Section>
        </Layout>
    );
}

function AuthRequired({ page }) {
    return (
        <Layout title={page}>
            <Head title={page} />
            <div className="flex flex-col items-center justify-center py-20">
                <div className="mb-4 flex h-16 w-16 items-center justify-center rounded-full bg-blue-500/10">
                    <svg className="h-8 w-8 text-blue-400" viewBox="0 0 24 24" fill="currentColor">
                        <path d="M12 0C5.373 0 0 5.373 0 12s5.373 12 12 12 12-5.373 12-12S18.627 0 12 0zm5.562 8.161l-1.97 9.279c-.146.658-.537.818-1.084.508l-3-2.211-1.447 1.394c-.16.16-.295.295-.605.295l.213-3.053 5.56-5.023c.242-.213-.054-.334-.373-.121l-6.871 4.326-2.962-.924c-.643-.204-.657-.643.136-.953l11.57-4.461c.537-.194 1.006.131.833.944z"/>
                    </svg>
                </div>
                <h2 className="mb-2 text-xl font-semibold text-white">Login Required</h2>
                <p className="mb-6 text-sm text-gray-400">Connect your Telegram account to access {page}</p>
                <p className="text-xs text-gray-500">Use the "Log in with Telegram" button in the sidebar</p>
            </div>
        </Layout>
    );
}

function Section({ title, children, className = '' }) {
    return (
        <div className={className}>
            <h2 className="mb-3 text-sm font-semibold uppercase tracking-wider text-gray-400">{title}</h2>
            {children}
        </div>
    );
}

function StatBox({ label, value, color }) {
    const colorClass = color === 'emerald' ? 'text-emerald-400' : color === 'red' ? 'text-red-400' : 'text-white';
    return (
        <div className="rounded-xl border border-gray-800 bg-gray-900/50 p-4">
            <p className="text-xs text-gray-500">{label}</p>
            <p className={`mt-1 text-xl font-bold ${colorClass}`}>{value}</p>
        </div>
    );
}

function PriceChange({ value }) {
    const num = parseFloat(value);
    if (isNaN(num)) return null;
    return (
        <span className={`text-xs ${num >= 0 ? 'text-emerald-400' : 'text-red-400'}`}>
            {num >= 0 ? '↑' : '↓'} {Math.abs(num).toFixed(2)}%
        </span>
    );
}

export { AuthRequired };
