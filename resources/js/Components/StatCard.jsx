export default function StatCard({ label, value, sub, accent = 'emerald', icon, children }) {
    const accents = {
        emerald: 'from-emerald-500/10 to-transparent border-emerald-500/20',
        blue: 'from-blue-500/10 to-transparent border-blue-500/20',
        amber: 'from-amber-500/10 to-transparent border-amber-500/20',
        rose: 'from-rose-500/10 to-transparent border-rose-500/20',
        violet: 'from-violet-500/10 to-transparent border-violet-500/20',
        gray: 'from-gray-700/20 to-transparent border-gray-700/40',
    };
    return (
        <div className={`relative overflow-hidden rounded-xl border bg-gradient-to-br ${accents[accent] || accents.gray} p-4`}>
            <div className="flex items-center justify-between">
                <span className="text-xs font-medium uppercase tracking-wider text-gray-400">{label}</span>
                {icon}
            </div>
            <div className="mt-2 text-2xl font-bold text-white">{value}</div>
            {sub && <div className="mt-1 text-xs text-gray-400">{sub}</div>}
            {children}
        </div>
    );
}
