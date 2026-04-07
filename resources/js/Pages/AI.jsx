import Layout from '@/Layouts/Layout';
import { Head, useForm, usePage } from '@inertiajs/react';
import { useState } from 'react';

export default function AI({ query }) {
    const { flash } = usePage().props;
    const { data, setData, post, processing } = useForm({ query: query || '' });

    const handleSubmit = (e) => {
        e.preventDefault();
        post('/ai/analyze');
    };

    const result = flash?.result;

    return (
        <Layout title="AI Analysis">
            <Head title="AI Analysis" />

            {/* Query input */}
            <form onSubmit={handleSubmit} className="mb-6">
                <label className="mb-2 block text-sm text-gray-400">Ask AI about any market or token</label>
                <div className="flex gap-3">
                    <input
                        type="text"
                        value={data.query}
                        onChange={(e) => setData('query', e.target.value)}
                        placeholder="e.g. Analyze BTC price action, Is ETH bullish?, SERPO token outlook..."
                        className="flex-1 rounded-xl border border-gray-700 bg-gray-900 px-4 py-3 text-white placeholder-gray-500 focus:border-emerald-500 focus:outline-none"
                    />
                    <button
                        type="submit"
                        disabled={processing || !data.query}
                        className="rounded-xl bg-emerald-600 px-6 py-3 text-sm font-medium text-white transition-colors hover:bg-emerald-500 disabled:opacity-50"
                    >
                        {processing ? 'Analyzing...' : 'Analyze'}
                    </button>
                </div>
            </form>

            {/* Quick prompts */}
            <div className="mb-6 flex flex-wrap gap-2">
                {['Analyze BTC', 'ETH outlook', 'SERPO prediction', 'Market sentiment', 'Top altcoins today'].map((q) => (
                    <button
                        key={q}
                        onClick={() => { setData('query', q); }}
                        className="rounded-lg border border-gray-700 bg-gray-900 px-3 py-1.5 text-xs text-gray-400 transition-colors hover:border-emerald-500 hover:text-emerald-400"
                    >
                        {q}
                    </button>
                ))}
            </div>

            {/* Results */}
            {result && (
                <div className="space-y-4">
                    {/* AI Analysis */}
                    <div className="rounded-xl border border-gray-800 bg-gray-900/50 p-5">
                        <h3 className="mb-3 text-sm font-semibold uppercase tracking-wider text-emerald-400">AI Analysis</h3>
                        <div className="whitespace-pre-wrap text-sm leading-relaxed text-gray-300">
                            {result.analysis}
                        </div>
                    </div>

                    {/* Sentiment */}
                    {result.sentiment && !result.sentiment.error && (
                        <div className="rounded-xl border border-gray-800 bg-gray-900/50 p-5">
                            <h3 className="mb-3 text-sm font-semibold uppercase tracking-wider text-blue-400">Market Sentiment</h3>
                            <div className="grid grid-cols-2 gap-4 sm:grid-cols-4">
                                <div>
                                    <p className="text-xs text-gray-500">Score</p>
                                    <p className="text-2xl font-bold text-white">{result.sentiment.score}/100</p>
                                </div>
                                <div>
                                    <p className="text-xs text-gray-500">Label</p>
                                    <p className="text-lg font-medium text-white">{result.sentiment.emoji} {result.sentiment.label}</p>
                                </div>
                                <div>
                                    <p className="text-xs text-gray-500">Confidence</p>
                                    <p className="text-lg font-medium text-white">{result.sentiment.confidence}</p>
                                </div>
                                <div>
                                    <p className="text-xs text-gray-500">Sources</p>
                                    <p className="text-lg font-medium text-white">{result.sentiment.sources}</p>
                                </div>
                            </div>
                        </div>
                    )}
                </div>
            )}

            {!result && (
                <div className="rounded-xl border border-gray-800 bg-gray-900/30 p-12 text-center">
                    <div className="mb-4 text-4xl">🤖</div>
                    <h3 className="mb-2 text-lg font-semibold text-white">AI-Powered Market Analysis</h3>
                    <p className="text-sm text-gray-500">
                        Ask any question about crypto, stocks, or forex markets.
                        Powered by Gemini, Groq, and GPT-4.
                    </p>
                </div>
            )}
        </Layout>
    );
}
