import Layout from '@/Layouts/Layout';
import { Head, router } from '@inertiajs/react';
import { useState } from 'react';

export default function Whales({ whaleData, symbol }) {
    const [currentSymbol, setCurrentSymbol] = useState(symbol || 'BTC');

    const handleSearch = (e) => {
        e.preventDefault();
        router.get('/whales', { symbol: currentSymbol }, { preserveState: true });
    };

    const hasError = whaleData?.error;

    return (
        <Layout title="Whale Tracker">
            <Head title="Whale Tracker" />

            {/* Search */}
            <form onSubmit={handleSearch} className="mb-6">
                <div className="flex gap-3">
                    <input
                        type="text"
                        value={currentSymbol}
                        onChange={(e) => setCurrentSymbol(e.target.value.toUpperCase())}
                        placeholder="Enter symbol (e.g. BTC, ETH, SOL)"
                        className="flex-1 rounded-xl border border-gray-700 bg-gray-900 px-4 py-3 text-white placeholder-gray-500 focus:border-emerald-500 focus:outline-none"
                    />
                    <button type="submit" className="rounded-xl bg-purple-600 px-6 py-3 text-sm font-medium text-white hover:bg-purple-500">
                        Track
                    </button>
                </div>
            </form>

            {hasError ? (
                <ErrorBox message={whaleData.error} />
            ) : whaleData ? (
                <div className="space-y-6">
                    {/* Large Orders */}
                    <DataSection title={`🐋 Large Orders – ${whaleData.large_orders?.pressure || 'Neutral'}`} color="purple">
                        {(() => {
                            const bids = (whaleData.large_orders?.large_bids || []).map(o => ({ ...o, side: 'BID' }));
                            const asks = (whaleData.large_orders?.large_asks || []).map(o => ({ ...o, side: 'ASK' }));
                            const orders = [...bids, ...asks];
                            return orders.length > 0 ? (
                                <div className="space-y-2">
                                    <div className="mb-3 flex gap-4 text-xs text-gray-400">
                                        <span>Total Bids: ${Number(whaleData.large_orders?.total_bid_value || 0).toLocaleString()}</span>
                                        <span>Total Asks: ${Number(whaleData.large_orders?.total_ask_value || 0).toLocaleString()}</span>
                                    </div>
                                    {orders.map((order, i) => (
                                        <div key={i} className="flex items-center justify-between rounded-lg bg-gray-800/50 px-4 py-3">
                                            <div>
                                                <span className={`text-sm font-medium ${order.side === 'BID' ? 'text-emerald-400' : 'text-red-400'}`}>
                                                    {order.side}
                                                </span>
                                                <span className="ml-2 text-sm text-gray-300">{Number(order.quantity).toLocaleString()} {symbol}</span>
                                            </div>
                                            <div className="text-right">
                                                <p className="text-sm text-white">${Number(order.price).toLocaleString()}</p>
                                                <p className="text-xs text-gray-500">${Number(order.value || 0).toLocaleString()}</p>
                                            </div>
                                        </div>
                                    ))}
                                </div>
                            ) : (
                                <p className="text-sm text-gray-500">No large orders detected</p>
                            );
                        })()}
                    </DataSection>

                    {/* Volume Spikes */}
                    <DataSection title={`📈 Volume Spikes – ${whaleData.volume_spikes?.status || ''}`} color="amber">
                        {whaleData.volume_spikes?.spikes?.length > 0 ? (
                            <div className="space-y-2">
                                {whaleData.volume_spikes.spikes.map((spike, i) => (
                                    <div key={i} className="rounded-lg bg-gray-800/50 p-3">
                                        <p className="text-sm text-white">{spike.symbol || spike.pair || `Spike ${i + 1}`}</p>
                                        <p className="text-xs text-gray-400">Volume: {Number(spike.volume || 0).toLocaleString()}</p>
                                    </div>
                                ))}
                            </div>
                        ) : (
                            <div className="rounded-lg bg-gray-800/50 p-3">
                                <p className="text-sm text-gray-400">{whaleData.volume_spikes?.status || 'No volume spike data'}</p>
                                {whaleData.volume_spikes?.avg_volume_per_minute != null && (
                                    <p className="mt-1 text-xs text-gray-500">Avg vol/min: {Number(whaleData.volume_spikes.avg_volume_per_minute).toLocaleString()}</p>
                                )}
                            </div>
                        )}
                    </DataSection>

                    {/* Liquidation Clusters */}
                    <DataSection title={`💥 Liquidation Clusters (${whaleData.liquidation_clusters?.total_liquidations || 0})`} color="red">
                        {whaleData.liquidation_clusters?.clusters?.length > 0 ? (
                            <div className="space-y-2">
                                {whaleData.liquidation_clusters.clusters.map((liq, i) => (
                                    <div key={i} className="flex items-center justify-between rounded-lg bg-gray-800/50 px-4 py-3">
                                        <div>
                                            <span className="text-sm text-gray-300">${Number(liq.price_level).toLocaleString()}</span>
                                            <span className="ml-2 text-xs text-gray-500">{liq.count} positions</span>
                                        </div>
                                        <div className="text-right">
                                            <span className="text-sm font-medium text-red-400">${Number(liq.total_value || 0).toLocaleString()}</span>
                                            <p className="text-xs text-gray-500">L:{liq.long_count} / S:{liq.short_count}</p>
                                        </div>
                                    </div>
                                ))}
                            </div>
                        ) : (
                            <p className="text-sm text-gray-500">{whaleData.liquidation_clusters?.warning || 'No liquidation clusters detected'}</p>
                        )}
                    </DataSection>
                </div>
            ) : null}
        </Layout>
    );
}

function DataSection({ title, color, children }) {
    const borderColor = { purple: 'border-purple-500/30', amber: 'border-amber-500/30', red: 'border-red-500/30' };
    return (
        <div className={`rounded-xl border ${borderColor[color] || 'border-gray-800'} bg-gray-900/50 p-5`}>
            <h3 className="mb-3 text-sm font-semibold text-gray-300">{title}</h3>
            {children}
        </div>
    );
}

function ErrorBox({ message }) {
    return (
        <div className="rounded-xl border border-red-500/30 bg-red-500/5 p-6 text-center">
            <p className="text-sm text-red-400">{message}</p>
        </div>
    );
}
