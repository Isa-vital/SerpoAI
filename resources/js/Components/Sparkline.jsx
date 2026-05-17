export default function Sparkline({ points = [], width = 100, height = 28, color, strokeWidth = 1.5 }) {
    if (!points || points.length < 2) {
        return <svg width={width} height={height} />;
    }
    const min = Math.min(...points);
    const max = Math.max(...points);
    const range = max - min || 1;
    const step = width / (points.length - 1);
    const path = points
        .map((p, i) => {
            const x = i * step;
            const y = height - ((p - min) / range) * height;
            return `${i === 0 ? 'M' : 'L'}${x.toFixed(2)},${y.toFixed(2)}`;
        })
        .join(' ');

    const up = points[points.length - 1] >= points[0];
    const stroke = color || (up ? '#34d399' : '#fb7185');

    // gradient fill area
    const area = `${path} L${width},${height} L0,${height} Z`;
    const gid = `g-${Math.abs(stroke.charCodeAt(1) || 1) + points.length}`;

    return (
        <svg width={width} height={height} viewBox={`0 0 ${width} ${height}`} className="overflow-visible">
            <defs>
                <linearGradient id={gid} x1="0" x2="0" y1="0" y2="1">
                    <stop offset="0%" stopColor={stroke} stopOpacity="0.25" />
                    <stop offset="100%" stopColor={stroke} stopOpacity="0" />
                </linearGradient>
            </defs>
            <path d={area} fill={`url(#${gid})`} stroke="none" />
            <path d={path} fill="none" stroke={stroke} strokeWidth={strokeWidth} strokeLinecap="round" strokeLinejoin="round" />
        </svg>
    );
}
