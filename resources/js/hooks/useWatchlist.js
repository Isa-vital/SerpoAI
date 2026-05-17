import { useEffect, useState, useCallback } from "react";

const KEY = "serpoai.watchlist.v1";

function read() {
    try {
        return JSON.parse(localStorage.getItem(KEY) || "[]");
    } catch {
        return [];
    }
}

export default function useWatchlist() {
    const [list, setList] = useState(() => read());

    useEffect(() => {
        const onStorage = (e) => {
            if (e.key === KEY) setList(read());
        };
        window.addEventListener("storage", onStorage);
        return () => window.removeEventListener("storage", onStorage);
    }, []);

    const persist = useCallback((next) => {
        setList(next);
        try {
            localStorage.setItem(KEY, JSON.stringify(next));
        } catch {}
    }, []);

    const toggle = useCallback(
        (symbol) => {
            const s = String(symbol).toUpperCase();
            persist(
                list.includes(s) ? list.filter((x) => x !== s) : [...list, s],
            );
        },
        [list, persist],
    );

    const has = useCallback(
        (symbol) => list.includes(String(symbol).toUpperCase()),
        [list],
    );

    return { list, toggle, has, set: persist };
}
