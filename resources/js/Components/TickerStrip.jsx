import useLiveData from '../hooks/useLiveData';
import { formatPrice, formatChange, changeColor } from '../lib/format';
import LiveDot from './LiveDot';

const DEFAULT_SYMBOLS = 'BTCUSDT,ETHUSDT,SOLUSDT,BNBUSDT,XRPUSDT,DOGEUSDT';

export default function TickerStrip({ symbols = DEFAULT_SYMBOLS }) {
    const { data } = useLiveData(`/api/markets/tickers?symbols=${symbols}`, { interval: 10000 });
    const list = data?.tickers || [];

    return (
        <div className="flex items-center gap-6 overflow-x-auto whitespace-nowrap border-b border-gray-800 bg-gray-950 px-4 py-2 text-xs scrollbar-hide">
            <LiveDot />
            {list.length === 0 && <span className="text-gray-600">Loading markets…</span>}
            {list.map((t) => (
                <div key={t.symbol} className="flex items-center gap-2">
                    <span className="font-semibold text-gray-300">{t.symbol.replace('USDT', '')}</span>
                    <span className="font-mono text-white">${formatPrice(t.price)}</span>
                    <span className={`font-mono ${changeColor(t.change)}`}>{formatChange(t.change)}</span>
                </div>
            ))}
        </div>
    );
}
