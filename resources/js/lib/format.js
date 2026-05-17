export function formatPrice(n, { maxFraction = 6 } = {}) {
    if (n === null || n === undefined || isNaN(n)) return "—";
    const v = Number(n);
    if (v === 0) return "0";
    const abs = Math.abs(v);
    let frac = 2;
    if (abs < 0.0001) frac = Math.min(maxFraction, 8);
    else if (abs < 1) frac = 4;
    else if (abs < 1000) frac = 2;
    else frac = 2;
    return v.toLocaleString("en-US", {
        minimumFractionDigits: 0,
        maximumFractionDigits: frac,
    });
}

export function formatCompact(n) {
    if (n === null || n === undefined || isNaN(n)) return "—";
    const v = Number(n);
    const abs = Math.abs(v);
    if (abs >= 1e12) return (v / 1e12).toFixed(2) + "T";
    if (abs >= 1e9) return (v / 1e9).toFixed(2) + "B";
    if (abs >= 1e6) return (v / 1e6).toFixed(2) + "M";
    if (abs >= 1e3) return (v / 1e3).toFixed(2) + "K";
    return v.toFixed(2);
}

export function formatChange(n, { digits = 2 } = {}) {
    if (n === null || n === undefined || isNaN(n)) return "—";
    const v = Number(n);
    const sign = v > 0 ? "+" : "";
    return `${sign}${v.toFixed(digits)}%`;
}

export function formatPercent(n, { digits = 2 } = {}) {
    if (n === null || n === undefined || isNaN(n)) return "—";
    return `${Number(n).toFixed(digits)}%`;
}

export function formatTimeAgo(iso) {
    if (!iso) return "";
    const t = new Date(iso).getTime();
    if (isNaN(t)) return "";
    const diff = (Date.now() - t) / 1000;
    if (diff < 60) return `${Math.round(diff)}s ago`;
    if (diff < 3600) return `${Math.round(diff / 60)}m ago`;
    if (diff < 86400) return `${Math.round(diff / 3600)}h ago`;
    return `${Math.round(diff / 86400)}d ago`;
}

export function changeColor(n) {
    if (n === null || n === undefined || isNaN(n)) return "text-gray-400";
    if (Number(n) > 0) return "text-emerald-400";
    if (Number(n) < 0) return "text-rose-400";
    return "text-gray-400";
}

export function changeBg(n) {
    if (n === null || n === undefined || isNaN(n)) return "bg-gray-800/50";
    if (Number(n) > 0) return "bg-emerald-500/10";
    if (Number(n) < 0) return "bg-rose-500/10";
    return "bg-gray-800/50";
}
