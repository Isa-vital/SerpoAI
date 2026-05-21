import { useEffect, useState } from 'react';

const PHASES = [
    {
        n: 1,
        label: 'PHASE 1',
        status: 'COMPLETE',
        text: 'Foundation built. We’re up and running.',
        color: 'emerald',
        icon: (
            // rocket
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.6" strokeLinecap="round" strokeLinejoin="round" className="h-8 w-8">
                <path d="M4.5 16.5c-1.5 1-2 5-2 5s4-0.5 5-2c.6-.8.6-2 0-2.8a2 2 0 0 0-3 0c-.8.6-.8 1.8 0 2.8z" />
                <path d="M12 15l-3-3a22 22 0 0 1 4.5-6.5C16 3 18 2.5 20.5 3.5 21.5 6 21 8 18.5 10.5a22 22 0 0 1-6.5 4.5z" />
                <path d="M15 9a1 1 0 1 0 0-2 1 1 0 0 0 0 2z" />
            </svg>
        ),
    },
    {
        n: 2,
        label: 'PHASE 2',
        status: 'LOADING',
        text: 'Building powerful features. Stay tuned.',
        color: 'sky',
        icon: (
            // spinner dots
            <svg viewBox="0 0 24 24" fill="currentColor" className="h-8 w-8">
                {[0, 60, 120, 180, 240, 300].map((a, i) => {
                    const r = 8;
                    const x = 12 + r * Math.cos((a * Math.PI) / 180);
                    const y = 12 + r * Math.sin((a * Math.PI) / 180);
                    return <circle key={i} cx={x} cy={y} r="1.4" opacity={0.3 + i * 0.12} />;
                })}
            </svg>
        ),
    },
    {
        n: 3,
        label: 'PHASE 3',
        status: 'PENDING',
        text: 'Final touches in progress. Almost there.',
        color: 'amber',
        icon: (
            // hourglass
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.6" strokeLinecap="round" strokeLinejoin="round" className="h-8 w-8">
                <path d="M6 3h12M6 21h12" />
                <path d="M7 3v3c0 2 2 3 5 6 3-3 5-4 5-6V3" />
                <path d="M7 21v-3c0-2 2-3 5-6 3 3 5 4 5 6v3" />
            </svg>
        ),
    },
    {
        n: 4,
        label: 'PHASE 4',
        status: 'UP NEXT',
        text: 'Exciting innovations on the horizon.',
        color: 'gray',
        icon: (
            // lock
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.6" strokeLinecap="round" strokeLinejoin="round" className="h-8 w-8">
                <rect x="5" y="11" width="14" height="9" rx="2" />
                <path d="M8 11V8a4 4 0 0 1 8 0v3" />
            </svg>
        ),
    },
    {
        n: 5,
        label: 'PHASE 5',
        status: 'FULL CAPACITY LAUNCH',
        text: 'Serpo AI at full power. Changing the future, together.',
        color: 'violet',
        icon: (
            // rocket with sparkles
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.6" strokeLinecap="round" strokeLinejoin="round" className="h-8 w-8">
                <path d="M14 4c3 0 6 3 6 6-2 4-5 6-9 7l-4-4c1-4 3-7 7-9z" />
                <path d="M7 13l-4 4 4 4 4-4" />
                <path d="M3 5l1 2 2 1-2 1-1 2-1-2-2-1 2-1z" opacity=".6" />
                <path d="M20 18l.6 1.4L22 20l-1.4.6L20 22l-.6-1.4L18 20l1.4-.6z" opacity=".6" />
            </svg>
        ),
    },
];

const COLOR_MAP = {
    emerald: { ring: 'ring-emerald-400/60', text: 'text-emerald-500', bg: 'bg-emerald-50', glow: 'shadow-[0_0_24px_rgba(16,185,129,0.25)]', line: 'bg-emerald-400' },
    sky:     { ring: 'ring-sky-400/60',     text: 'text-sky-500',     bg: 'bg-sky-50',     glow: 'shadow-[0_0_24px_rgba(56,189,248,0.25)]',  line: 'bg-sky-400' },
    amber:   { ring: 'ring-amber-400/60',   text: 'text-amber-500',   bg: 'bg-amber-50',   glow: 'shadow-[0_0_24px_rgba(245,158,11,0.25)]',  line: 'bg-gray-300' },
    gray:    { ring: 'ring-gray-300',       text: 'text-gray-500',    bg: 'bg-gray-50',    glow: '',                                          line: 'bg-gray-300' },
    violet:  { ring: 'ring-violet-400/60',  text: 'text-violet-500',  bg: 'bg-violet-50',  glow: 'shadow-[0_0_24px_rgba(139,92,246,0.3)]',   line: 'bg-gray-300' },
};

const STORAGE_KEY = 'serpo_roadmap_seen_v1';

export default function RoadmapModal() {
    const [open, setOpen] = useState(false);

    useEffect(() => {
        try {
            if (typeof window === 'undefined') return;
            if (!sessionStorage.getItem(STORAGE_KEY)) {
                setOpen(true);
            }
        } catch {
            setOpen(true);
        }
    }, []);

    useEffect(() => {
        if (!open) return;
        const onKey = (e) => { if (e.key === 'Escape') close(); };
        document.addEventListener('keydown', onKey);
        const prev = document.body.style.overflow;
        document.body.style.overflow = 'hidden';
        return () => {
            document.removeEventListener('keydown', onKey);
            document.body.style.overflow = prev;
        };
    }, [open]);

    const close = () => {
        try { sessionStorage.setItem(STORAGE_KEY, '1'); } catch {}
        setOpen(false);
    };

    if (!open) return null;

    return (
        <div
            className="fixed inset-0 z-[100] flex items-center justify-center bg-black/60 p-4 backdrop-blur-sm"
            onClick={close}
            role="dialog"
            aria-modal="true"
            aria-labelledby="roadmap-title"
        >
            <div
                className="relative w-full max-w-5xl overflow-hidden rounded-2xl bg-gradient-to-b from-white to-slate-50 text-slate-900 shadow-2xl ring-1 ring-slate-200"
                onClick={(e) => e.stopPropagation()}
            >
                <button
                    onClick={close}
                    aria-label="Close"
                    className="absolute right-4 top-4 z-10 flex h-8 w-8 items-center justify-center rounded-full text-slate-400 transition-colors hover:bg-slate-100 hover:text-slate-700"
                >
                    <svg className="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"><path d="M6 6l12 12M18 6L6 18" /></svg>
                </button>

                {/* Header */}
                <div className="px-6 pb-2 pt-10 text-center sm:px-10">
                    <div className="inline-flex items-center justify-center gap-2">
                        <svg className="h-6 w-6 text-indigo-500" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2l1.6 5.4L19 9l-5.4 1.6L12 16l-1.6-5.4L5 9l5.4-1.6z" /></svg>
                        <h2 id="roadmap-title" className="text-3xl font-extrabold tracking-tight sm:text-4xl">
                            Serpo <span className="bg-gradient-to-r from-indigo-500 to-violet-500 bg-clip-text text-transparent">AI</span>
                        </h2>
                        <svg className="h-5 w-5 text-sky-400" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2l1.6 5.4L19 9l-5.4 1.6L12 16l-1.6-5.4L5 9l5.4-1.6z" /></svg>
                    </div>
                    <p className="mt-2 text-xs font-semibold uppercase tracking-[0.25em] text-slate-500">
                        Our Journey to Transform AI
                    </p>
                </div>

                {/* Phases */}
                <div className="px-4 pb-6 pt-8 sm:px-10">
                    {/* Desktop: horizontal timeline */}
                    <div className="hidden md:grid md:grid-cols-5 md:gap-2">
                        {PHASES.map((p, i) => {
                            const c = COLOR_MAP[p.color];
                            const next = PHASES[i + 1];
                            const nextC = next ? COLOR_MAP[next.color] : null;
                            const dashed = i >= 2;
                            return (
                                <div key={p.n} className="relative flex flex-col items-center text-center">
                                    {/* connector to next */}
                                    {next && (
                                        <div className="absolute left-1/2 top-10 h-0.5 w-full">
                                            {dashed ? (
                                                <div className="h-full w-full border-t-2 border-dashed border-gray-300" />
                                            ) : (
                                                <div className={`h-full w-full ${nextC.line}`} />
                                            )}
                                        </div>
                                    )}
                                    {/* circle */}
                                    <div className={`relative z-10 flex h-20 w-20 flex-col items-center justify-center rounded-full bg-white ring-2 ${c.ring} ${c.glow}`}>
                                        <span className={`text-xl font-bold ${c.text}`}>{p.n}</span>
                                        <span className={`${c.text}`}>{p.icon}</span>
                                        {p.n === 1 && (
                                            <span className="absolute -right-1 -top-1 flex h-6 w-6 items-center justify-center rounded-full bg-emerald-500 text-white shadow">
                                                <svg className="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="3" strokeLinecap="round" strokeLinejoin="round"><path d="M5 12l5 5L20 7" /></svg>
                                            </span>
                                        )}
                                    </div>
                                    <div className={`mt-4 text-xs font-bold tracking-wider ${c.text}`}>{p.label}</div>
                                    <div className={`mt-1 text-sm font-extrabold ${c.text}`}>{p.status}</div>
                                    <p className="mt-2 max-w-[10rem] text-xs leading-relaxed text-slate-500">{p.text}</p>
                                </div>
                            );
                        })}
                    </div>

                    {/* Mobile: vertical stack */}
                    <div className="space-y-4 md:hidden">
                        {PHASES.map((p) => {
                            const c = COLOR_MAP[p.color];
                            return (
                                <div key={p.n} className="flex items-start gap-4 rounded-xl border border-slate-200 bg-white p-4">
                                    <div className={`relative flex h-16 w-16 shrink-0 flex-col items-center justify-center rounded-full bg-white ring-2 ${c.ring} ${c.glow}`}>
                                        <span className={`text-lg font-bold ${c.text}`}>{p.n}</span>
                                        <span className={c.text}>{p.icon}</span>
                                        {p.n === 1 && (
                                            <span className="absolute -right-1 -top-1 flex h-5 w-5 items-center justify-center rounded-full bg-emerald-500 text-white">
                                                <svg className="h-3 w-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="3" strokeLinecap="round" strokeLinejoin="round"><path d="M5 12l5 5L20 7" /></svg>
                                            </span>
                                        )}
                                    </div>
                                    <div className="flex-1">
                                        <div className={`text-[10px] font-bold tracking-wider ${c.text}`}>{p.label}</div>
                                        <div className={`text-sm font-extrabold ${c.text}`}>{p.status}</div>
                                        <p className="mt-1 text-xs text-slate-500">{p.text}</p>
                                    </div>
                                </div>
                            );
                        })}
                    </div>
                </div>

                {/* Tagline */}
                <div className="mx-4 mb-6 mt-2 rounded-xl bg-slate-100/80 px-4 py-3 text-center text-sm text-slate-600 sm:mx-10">
                    <span className="mr-2 text-violet-500">✦</span>
                    Smarter. Faster. Stronger. The future is{' '}
                    <span className="font-semibold text-violet-600">Serpo AI</span>.
                </div>

                {/* Footer button */}
                <div className="flex items-center justify-center border-t border-slate-200 bg-white px-6 py-4">
                    <button
                        onClick={close}
                        className="inline-flex items-center justify-center rounded-lg bg-gradient-to-r from-indigo-500 to-violet-500 px-8 py-2.5 text-sm font-semibold text-white shadow-md transition-transform hover:scale-[1.02] hover:shadow-lg focus:outline-none focus:ring-2 focus:ring-violet-400 focus:ring-offset-2"
                    >
                        Okay
                    </button>
                </div>
            </div>
        </div>
    );
}
