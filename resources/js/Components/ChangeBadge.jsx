import { changeBg, changeColor, formatChange } from '../lib/format';

export default function ChangeBadge({ value, className = '' }) {
    if (value === null || value === undefined) return <span className="text-gray-500">—</span>;
    return (
        <span className={`inline-flex items-center rounded px-1.5 py-0.5 text-xs font-semibold ${changeBg(value)} ${changeColor(value)} ${className}`}>
            {formatChange(value)}
        </span>
    );
}
