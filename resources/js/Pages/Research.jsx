import Layout from '@/Layouts/Layout';
import { Head } from '@inertiajs/react';

export default function Research({ trends, news, scan }) {
    return (
        <Layout title="Research">
            <Head title="Research" />

            <div className="space-y-6">
                {/* Market Scan Overview */}
                {scan && !scan.error && (
                    <Section title="📊 Market Scan">
                        <div className="grid grid-cols-2 gap-3 sm:grid-cols-4">
                            {scan.crypto?.fear_greed != null && (
                                <InfoBox label="Fear & Greed" value={typeof scan.crypto.fear_greed === 'object' ? `${scan.crypto.fear_greed.value} - ${scan.crypto.fear_greed.classification}` : scan.crypto.fear_greed} />
                            )}
                            {scan.crypto?.btc_dominance != null && (
                                <InfoBox label="BTC Dominance" value={typeof scan.crypto.btc_dominance === 'object' ? `${scan.crypto.btc_dominance.value}%` : `${scan.crypto.btc_dominance}%`} />
                            )}
                            {scan.crypto?.market_overview?.total_volume_24h != null && (
                                <InfoBox label="24h Volume" value={`$${Number(scan.crypto.market_overview.total_volume_24h).toLocaleString()}`} />
                            )}
                            {scan.crypto?.market_overview?.market_sentiment && (
                                <InfoBox label="Sentiment" value={`${scan.crypto.market_overview.market_sentiment}`} />
                            )}
                        </div>

                        {/* Gainers */}
                        {(() => {
                            const g = scan.crypto?.top_gainers;
                            const gainers = [...(g?.high_volume || []), ...(g?.low_volume || [])];
                            return gainers.length > 0 ? (
                                <div className="mt-4">
                                    <h4 className="mb-2 text-xs font-semibold uppercase text-emerald-400">Top Gainers</h4>
                                    <div className="flex flex-wrap gap-2">
                                        {gainers.slice(0, 8).map((g, i) => (
                                            <span key={i} className="rounded-lg bg-emerald-500/10 px-3 py-1.5 text-xs text-emerald-400">
                                                {g.symbol} +{Number(g.change_percent || g.priceChangePercent || 0).toFixed(1)}%
                                            </span>
                                        ))}
                                    </div>
                                </div>
                            ) : null;
                        })()}

                        {/* Losers */}
                        {(() => {
                            const l = scan.crypto?.top_losers;
                            const losers = [...(l?.high_volume || []), ...(l?.low_volume || [])];
                            return losers.length > 0 ? (
                                <div className="mt-3">
                                    <h4 className="mb-2 text-xs font-semibold uppercase text-red-400">Top Losers</h4>
                                    <div className="flex flex-wrap gap-2">
                                        {losers.slice(0, 8).map((l, i) => (
                                            <span key={i} className="rounded-lg bg-red-500/10 px-3 py-1.5 text-xs text-red-400">
                                                {l.symbol} {Number(l.change_percent || l.priceChangePercent || 0).toFixed(1)}%
                                            </span>
                                        ))}
                                    </div>
                                </div>
                            ) : null;
                        })()}
                    </Section>
                )}

                {/* Trend Leaders */}
                {trends && !trends.error && (
                    <Section title="🔥 Trend Leaders">
                        {trends.crypto?.length > 0 && (
                            <div>
                                <h4 className="mb-2 text-xs font-semibold uppercase text-gray-500">Crypto</h4>
                                <div className="space-y-2">
                                    {trends.crypto.slice(0, 10).map((coin, i) => (
                                        <div key={i} className="flex items-center justify-between rounded-lg bg-gray-800/50 px-4 py-2">
                                            <span className="text-sm font-medium text-white">{coin.symbol || coin.name}</span>
                                            <div className="flex items-center gap-3">
                                                <span className="text-sm text-gray-400">${Number(coin.price || 0).toLocaleString()}</span>
                                                <span className={`text-sm font-medium ${Number(coin.change_24h) >= 0 ? 'text-emerald-400' : 'text-red-400'}`}>
                                                    {Number(coin.change_24h || 0).toFixed(2)}%
                                                </span>
                                            </div>
                                        </div>
                                    ))}
                                </div>
                            </div>
                        )}

                        {trends.ai_insights && (
                            <div className="mt-4 rounded-lg bg-blue-500/5 border border-blue-500/20 p-4">
                                <h4 className="mb-2 text-xs font-semibold uppercase text-blue-400">AI Insights</h4>
                                <p className="text-sm text-gray-300 whitespace-pre-wrap">{trends.ai_insights}</p>
                            </div>
                        )}
                    </Section>
                )}

                {/* News */}
                <Section title="📰 Latest News">
                    {news ? (
                        <div className="whitespace-pre-wrap rounded-xl bg-gray-800/30 p-4 text-sm text-gray-300">
                            {news}
                        </div>
                    ) : (
                        <p className="text-sm text-gray-500">No news available</p>
                    )}
                </Section>
            </div>
        </Layout>
    );
}

function Section({ title, children }) {
    return (
        <div className="rounded-xl border border-gray-800 bg-gray-900/50 p-5">
            <h3 className="mb-4 text-sm font-semibold text-gray-300">{title}</h3>
            {children}
        </div>
    );
}

function InfoBox({ label, value }) {
    return (
        <div className="rounded-lg bg-gray-800/50 p-3">
            <p className="text-xs text-gray-500">{label}</p>
            <p className="mt-1 text-sm font-medium text-white">{value}</p>
        </div>
    );
}
