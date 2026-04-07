import Layout from '@/Layouts/Layout';
import { Head, router } from '@inertiajs/react';
import { useState, useMemo } from 'react';

const TABS = [
    { key: 'crypto', label: 'Crypto', icon: '₿' },
    { key: 'stocks', label: 'Stocks', icon: '📈' },
    { key: 'forex', label: 'Forex', icon: '💱' },
];

export default function Prices({ crypto, stocks, forex, serpo }) {
    const [tab, setTab] = useState('crypto');
    const [search, setSearch] = useState('');
    const [page, setPage] = useState(1);
    const PER_PAGE = 100;

    // Normalize crypto coins from spot_markets
    const cryptoCoins = useMemo(() => {
        const markets = crypto?.spot_markets || [];
        return markets.map(c => ({
            symbol: (c.symbol || '').replace('USDT', ''),
            pair: c.symbol || '',
            price: parseFloat(c.lastPrice || c.price || 0),
            change: parseFloat(c.priceChangePercent || c.change_24h || 0),
            volume: parseFloat(c.quoteVolume || c.volume_24h || 0),
        })).sort((a, b) => b.volume - a.volume);
    }, [crypto]);

    // Normalize stock data
    const stockRows = useMemo(() => {
        const all = [];
        const sections = ['top_gainers', 'top_losers', 'most_active'];
        for (const sec of sections) {
            const items = stocks?.[sec];
            if (Array.isArray(items)) {
                items.forEach(s => {
                    if (!all.find(x => x.symbol === s.symbol)) {
                        all.push({
                            symbol: s.symbol,
                            price: parseFloat(s.price || 0),
                            change: parseFloat(s.change_percent || s.priceChangePercent || 0),
                            volume: 0,
                        });
                    }
                });
            }
        }
        // Also add indices
        if (Array.isArray(stocks?.indices)) {
            stocks.indices.forEach(idx => {
                if (!all.find(x => x.symbol === idx.symbol)) {
                    all.push({
                        symbol: idx.symbol,
                        price: parseFloat(idx.price || 0),
                        change: parseFloat(idx.change_percent || 0),
                        volume: 0,
                    });
                }
            });
        }
        return all;
    }, [stocks]);

    // Normalize forex data
    const forexRows = useMemo(() => {
        const pairs = forex?.major_pairs || [];
        return pairs.map(f => ({
            symbol: f.pair || f.symbol,
            price: parseFloat(f.price || 0),
            change: parseFloat(f.change_percent || 0),
            volume: 0,
        }));
    }, [forex]);

    const dataMap = { crypto: cryptoCoins, stocks: stockRows, forex: forexRows };
    const rows = dataMap[tab] || [];

    const filtered = search
        ? rows.filter(r => r.symbol?.toLowerCase().includes(search.toLowerCase()))
        : rows;

    const totalPages = Math.ceil(filtered.length / PER_PAGE);
    const paginated = filtered.slice((page - 1) * PER_PAGE, page * PER_PAGE);

    const totalCount = tab === 'crypto'
        ? (crypto?.total_pairs || cryptoCoins.length)
        : filtered.length;

    const handleSearch = (e) => {
        e.preventDefault();
        setPage(1);
    };

    return (
        <Layout title="Prices">
            <Head title="Prices" />

            {/* SERPO highlight */}
            {serpo && !serpo.error && (
                <div className="mb-6 rounded-xl border border-emerald-500/30 bg-emerald-500/5 p-5">
                    <div className="flex items-center justify-between">
                        <div>
                            <p className="text-sm text-gray-400">SERPO Token</p>
                            <p className="text-3xl font-bold text-white">${Number(serpo.price).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 8 })}</p>
                        </div>
                        <div className="text-right">
                            <PriceChange value={serpo.price_change_24h} />
                            <p className="mt-1 text-xs text-gray-500">Vol: ${Number(serpo.volume_24h || 0).toLocaleString()}</p>
                            <p className="text-xs text-gray-500">Liq: ${Number(serpo.liquidity || 0).toLocaleString()}</p>
                        </div>
                    </div>
                </div>
            )}

            {/* Market stats bar */}
            {tab === 'crypto' && crypto && (
                <div className="mb-4 flex flex-wrap gap-4 text-xs text-gray-400">
                    {crypto.total_market_cap > 0 && (
                        <span>Market Cap: <span className="text-white">${(crypto.total_market_cap / 1e12).toFixed(2)}T</span></span>
                    )}
                    {crypto.btc_dominance > 0 && (
                        <span>BTC Dom: <span className="text-white">{Number(crypto.btc_dominance).toFixed(1)}%</span></span>
                    )}
                    {crypto.fear_greed_index > 0 && (
                        <span>Fear & Greed: <span className="text-white">{crypto.fear_greed_index}</span></span>
                    )}
                    {crypto.trending?.length > 0 && (
                        <span>Trending: <span className="text-emerald-400">{crypto.trending.slice(0, 5).map(t => t.name || t.symbol || t).join(', ')}</span></span>
                    )}
                </div>
            )}

            {/* Tabs */}
            <div className="mb-4 flex gap-1 rounded-xl bg-gray-900/50 p-1">
                {TABS.map(t => (
                    <button
                        key={t.key}
                        onClick={() => { setTab(t.key); setPage(1); setSearch(''); }}
                        className={`flex-1 rounded-lg px-4 py-2.5 text-sm font-medium transition-colors ${
                            tab === t.key
                                ? 'bg-emerald-600 text-white'
                                : 'text-gray-400 hover:text-white'
                        }`}
                    >
                        {t.icon} {t.label}
                    </button>
                ))}
            </div>

            {/* Search */}
            <form onSubmit={handleSearch} className="mb-4">
                <div className="relative">
                    <input
                        type="text"
                        value={search}
                        onChange={(e) => { setSearch(e.target.value); setPage(1); }}
                        placeholder={`Search ${tab === 'crypto' ? 'coin' : tab === 'stocks' ? 'stock' : 'pair'} (e.g. ${tab === 'crypto' ? 'BTC, ETH, SOL' : tab === 'stocks' ? 'AAPL, TSLA' : 'EURUSD'})...`}
                        className="w-full rounded-xl border border-gray-700 bg-gray-900 px-4 py-3 pl-10 text-white placeholder-gray-500 focus:border-emerald-500 focus:outline-none"
                    />
                    <svg className="absolute left-3 top-3.5 h-5 w-5 text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                </div>
            </form>

            {/* Header */}
            <div className="mb-3 flex items-center justify-between">
                <h2 className="text-sm font-semibold uppercase tracking-wider text-gray-400">
                    {tab === 'crypto' ? `Crypto Markets (${totalCount} pairs)` :
                     tab === 'stocks' ? `Stock Markets (${totalCount} assets)` :
                     `Forex Markets (${totalCount} pairs)`}
                </h2>
                {totalPages > 1 && (
                    <div className="flex items-center gap-2">
                        <button
                            onClick={() => setPage(p => Math.max(1, p - 1))}
                            disabled={page <= 1}
                            className="rounded-lg bg-gray-800 px-3 py-1 text-xs text-gray-300 hover:bg-gray-700 disabled:opacity-30"
                        >Prev</button>
                        <span className="text-xs text-gray-500">{page}/{totalPages}</span>
                        <button
                            onClick={() => setPage(p => Math.min(totalPages, p + 1))}
                            disabled={page >= totalPages}
                            className="rounded-lg bg-gray-800 px-3 py-1 text-xs text-gray-300 hover:bg-gray-700 disabled:opacity-30"
                        >Next</button>
                    </div>
                )}
            </div>

            {/* Table */}
            <div className="overflow-x-auto rounded-xl border border-gray-800">
                <table className="w-full text-sm">
                    <thead>
                        <tr className="border-b border-gray-800 bg-gray-900/50 text-left">
                            <th className="px-4 py-3 text-gray-400">#</th>
                            <th className="px-4 py-3 text-gray-400">Symbol</th>
                            <th className="px-4 py-3 text-right text-gray-400">Price</th>
                            <th className="px-4 py-3 text-right text-gray-400">24h Change</th>
                            {tab === 'crypto' && <th className="px-4 py-3 text-right text-gray-400">Volume (24h)</th>}
                        </tr>
                    </thead>
                    <tbody>
                        {paginated.map((row, i) => (
                            <tr key={row.symbol + i} className="border-b border-gray-800/50 hover:bg-gray-900/30">
                                <td className="px-4 py-3 text-gray-500">{(page - 1) * PER_PAGE + i + 1}</td>
                                <td className="px-4 py-3 font-medium text-white">{row.symbol}</td>
                                <td className="px-4 py-3 text-right text-white">
                                    {tab === 'forex' ? row.price.toFixed(5) : `$${row.price.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: row.price < 1 ? 8 : 2 })}`}
                                </td>
                                <td className="px-4 py-3 text-right">
                                    <PriceChange value={row.change} />
                                </td>
                                {tab === 'crypto' && (
                                    <td className="px-4 py-3 text-right text-gray-400">${Number(row.volume || 0).toLocaleString(undefined, { maximumFractionDigits: 0 })}</td>
                                )}
                            </tr>
                        ))}
                        {paginated.length === 0 && (
                            <tr><td colSpan={tab === 'crypto' ? 5 : 4} className="px-4 py-8 text-center text-gray-500">
                                {search ? 'No results found' : 'Loading market data...'}
                            </td></tr>
                        )}
                    </tbody>
                </table>
            </div>

            {/* Footer pagination */}
            {totalPages > 1 && (
                <div className="mt-3 flex justify-center gap-2">
                    <button
                        onClick={() => setPage(p => Math.max(1, p - 1))}
                        disabled={page <= 1}
                        className="rounded-lg bg-gray-800 px-4 py-2 text-xs text-gray-300 hover:bg-gray-700 disabled:opacity-30"
                    >← Previous</button>
                    <span className="px-3 py-2 text-xs text-gray-500">Page {page} of {totalPages} — showing {filtered.length} results</span>
                    <button
                        onClick={() => setPage(p => Math.min(totalPages, p + 1))}
                        disabled={page >= totalPages}
                        className="rounded-lg bg-gray-800 px-4 py-2 text-xs text-gray-300 hover:bg-gray-700 disabled:opacity-30"
                    >Next →</button>
                </div>
            )}
        </Layout>
    );
}

function PriceChange({ value }) {
    const num = parseFloat(value);
    if (isNaN(num)) return <span className="text-gray-500">—</span>;
    const positive = num >= 0;
    return (
        <span className={`text-sm font-medium ${positive ? 'text-emerald-400' : 'text-red-400'}`}>
            {positive ? '↑' : '↓'} {Math.abs(num).toFixed(2)}%
        </span>
    );
}
