export default function Skeleton({ className = '', lines = 1 }) {
    if (lines > 1) {
        return (
            <div className={`space-y-2 ${className}`}>
                {Array.from({ length: lines }).map((_, i) => (
                    <div key={i} className="h-3 animate-pulse rounded bg-gray-800" />
                ))}
            </div>
        );
    }
    return <div className={`animate-pulse rounded bg-gray-800 ${className}`} />;
}
