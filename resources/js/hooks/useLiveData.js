import { useEffect, useRef, useState, useCallback } from "react";

/**
 * Polls a JSON endpoint at a fixed interval and revalidates on tab focus.
 * Returns { data, loading, error, refresh }.
 */
export default function useLiveData(
    url,
    { interval = 15000, enabled = true, initialData = null } = {},
) {
    const [data, setData] = useState(initialData);
    const [loading, setLoading] = useState(!initialData);
    const [error, setError] = useState(null);
    const abortRef = useRef(null);
    const timerRef = useRef(null);

    const fetcher = useCallback(async () => {
        if (!enabled || !url) return;
        if (abortRef.current) abortRef.current.abort();
        const ctrl = new AbortController();
        abortRef.current = ctrl;
        try {
            const res = await fetch(url, {
                signal: ctrl.signal,
                headers: {
                    Accept: "application/json",
                    "X-Requested-With": "XMLHttpRequest",
                },
                credentials: "same-origin",
            });
            if (!res.ok) throw new Error(`HTTP ${res.status}`);
            const json = await res.json();
            setData(json);
            setError(null);
        } catch (e) {
            if (e.name !== "AbortError") setError(e);
        } finally {
            setLoading(false);
        }
    }, [url, enabled]);

    useEffect(() => {
        fetcher();
        if (!interval || !enabled) return;
        timerRef.current = setInterval(fetcher, interval);

        const onFocus = () => fetcher();
        const onVisibility = () => {
            if (document.visibilityState === "visible") fetcher();
        };
        window.addEventListener("focus", onFocus);
        document.addEventListener("visibilitychange", onVisibility);

        return () => {
            clearInterval(timerRef.current);
            window.removeEventListener("focus", onFocus);
            document.removeEventListener("visibilitychange", onVisibility);
            if (abortRef.current) abortRef.current.abort();
        };
    }, [fetcher, interval, enabled]);

    return { data, loading, error, refresh: fetcher };
}
