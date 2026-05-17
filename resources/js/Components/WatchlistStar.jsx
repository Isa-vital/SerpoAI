import useWatchlist from '../hooks/useWatchlist';

export default function WatchlistStar({ symbol, className = '' }) {
    const { has, toggle } = useWatchlist();
    const on = has(symbol);
    return (
        <button
            type="button"
            onClick={(e) => { e.stopPropagation(); toggle(symbol); }}
            className={`text-base leading-none transition-colors ${on ? 'text-amber-400 hover:text-amber-300' : 'text-gray-600 hover:text-gray-400'} ${className}`}
            title={on ? 'Remove from watchlist' : 'Add to watchlist'}
        >
            {on ? '★' : '☆'}
        </button>
    );
}
