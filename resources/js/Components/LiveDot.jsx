export default function LiveDot({ active = true, label = 'LIVE' }) {
    return (
        <span className="inline-flex items-center gap-1.5 text-[10px] font-bold uppercase tracking-wider text-emerald-400">
            <span className="relative flex h-2 w-2">
                {active && <span className="absolute inline-flex h-full w-full animate-ping rounded-full bg-emerald-400 opacity-75" />}
                <span className="relative inline-flex h-2 w-2 rounded-full bg-emerald-500" />
            </span>
            {label}
        </span>
    );
}
