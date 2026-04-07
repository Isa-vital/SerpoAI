import Layout from '@/Layouts/Layout';
import { Head, router } from '@inertiajs/react';
import { useState } from 'react';

export default function Verify({ result, input }) {
    const [token, setToken] = useState(input || '');

    const handleSubmit = (e) => {
        e.preventDefault();
        if (token) router.get('/verify', { token }, { preserveState: true });
    };

    return (
        <Layout title="Token Verify">
            <Head title="Token Verify" />

            {/* Search */}
            <form onSubmit={handleSubmit} className="mb-6">
                <label className="mb-2 block text-sm text-gray-400">Enter token address, symbol, or contract</label>
                <div className="flex gap-3">
                    <input
                        type="text"
                        value={token}
                        onChange={(e) => setToken(e.target.value)}
                        placeholder="e.g. BTC, 0x1234...abcd, EQCPeU..."
                        className="flex-1 rounded-xl border border-gray-700 bg-gray-900 px-4 py-3 text-white placeholder-gray-500 focus:border-emerald-500 focus:outline-none"
                    />
                    <button type="submit" className="rounded-xl bg-cyan-600 px-6 py-3 text-sm font-medium text-white hover:bg-cyan-500">
                        Verify
                    </button>
                </div>
            </form>

            {/* Results */}
            {result && !result.error ? (
                <div className="space-y-4">
                    {/* Trust Score Header */}
                    <div className={`rounded-xl border p-5 ${getScoreBorder(result.trust_score || result.risk_score)}`}>
                        <div className="flex items-center justify-between">
                            <div>
                                <h3 className="text-lg font-bold text-white">{result.name || result.symbol || input}</h3>
                                <p className="text-sm text-gray-400">{result.symbol} • {result.chain || 'Unknown chain'}</p>
                            </div>
                            <div className="text-right">
                                <p className="text-3xl font-bold text-white">{result.trust_score ?? result.risk_score ?? '—'}</p>
                                <p className="text-xs text-gray-400">{result.trust_score !== undefined ? 'Trust Score' : 'Risk Score'}</p>
                            </div>
                        </div>
                    </div>

                    {/* Token Info */}
                    {(result.price || result.holders || result.liquidity) && (
                        <div className="grid grid-cols-2 gap-3 sm:grid-cols-4">
                            {result.price && <InfoBox label="Price" value={`$${result.price}`} />}
                            {result.holders && <InfoBox label="Holders" value={Number(result.holders).toLocaleString()} />}
                            {result.liquidity && <InfoBox label="Liquidity" value={`$${Number(result.liquidity).toLocaleString()}`} />}
                            {result.market_cap && <InfoBox label="Market Cap" value={`$${Number(result.market_cap).toLocaleString()}`} />}
                        </div>
                    )}

                    {/* Flags */}
                    {result.green_flags?.length > 0 && (
                        <FlagSection title="✅ Green Flags" flags={result.green_flags} color="emerald" />
                    )}
                    {result.red_flags?.length > 0 && (
                        <FlagSection title="🚩 Red Flags" flags={result.red_flags} color="red" />
                    )}
                    {result.warnings?.length > 0 && (
                        <FlagSection title="⚠️ Warnings" flags={result.warnings} color="amber" />
                    )}
                </div>
            ) : result?.error ? (
                <div className="rounded-xl border border-red-500/30 bg-red-500/5 p-6 text-center">
                    <p className="text-sm text-red-400">{result.error}</p>
                </div>
            ) : (
                <div className="rounded-xl border border-gray-800 bg-gray-900/30 p-12 text-center">
                    <div className="mb-4 text-4xl">🛡️</div>
                    <h3 className="mb-2 text-lg font-semibold text-white">Token Verification</h3>
                    <p className="text-sm text-gray-500">
                        Check token legitimacy across 20+ chains. Get trust scores, holder distribution,
                        red/green flags, and risk assessments.
                    </p>
                </div>
            )}
        </Layout>
    );
}

function getScoreBorder(score) {
    if (score === null || score === undefined) return 'border-gray-800 bg-gray-900/50';
    if (score >= 70) return 'border-emerald-500/30 bg-emerald-500/5';
    if (score >= 40) return 'border-amber-500/30 bg-amber-500/5';
    return 'border-red-500/30 bg-red-500/5';
}

function InfoBox({ label, value }) {
    return (
        <div className="rounded-xl border border-gray-800 bg-gray-900/50 p-3">
            <p className="text-xs text-gray-500">{label}</p>
            <p className="mt-1 text-sm font-medium text-white">{value}</p>
        </div>
    );
}

function FlagSection({ title, flags, color }) {
    const colors = { emerald: 'text-emerald-400', red: 'text-red-400', amber: 'text-amber-400' };
    return (
        <div className="rounded-xl border border-gray-800 bg-gray-900/50 p-4">
            <h4 className={`mb-2 text-sm font-semibold ${colors[color]}`}>{title}</h4>
            <ul className="space-y-1">
                {flags.map((flag, i) => (
                    <li key={i} className="text-sm text-gray-300">• {typeof flag === 'string' ? flag : flag.message || JSON.stringify(flag)}</li>
                ))}
            </ul>
        </div>
    );
}
