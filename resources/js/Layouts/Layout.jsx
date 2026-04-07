import { Link, usePage } from '@inertiajs/react';
import { useState } from 'react';

const navItems = [
    { name: 'Dashboard', href: '/', icon: DashboardIcon },
    { name: 'Prices', href: '/prices', icon: PricesIcon },
    { name: 'Portfolio', href: '/portfolio', icon: PortfolioIcon },
    { name: 'Alerts', href: '/alerts', icon: AlertsIcon },
    { name: 'AI Analysis', href: '/ai', icon: AIIcon },
    { name: 'Charts', href: '/charts', icon: ChartsIcon },
    { name: 'Whale Tracker', href: '/whales', icon: WhaleIcon },
    { name: 'Token Verify', href: '/verify', icon: VerifyIcon },
    { name: 'Signals', href: '/signals', icon: SignalsIcon },
    { name: 'Research', href: '/research', icon: ResearchIcon },
    { name: 'Grid Bot', href: '/grid', icon: GridIcon },
    { name: 'Settings', href: '/settings', icon: SettingsIcon },
];

export default function Layout({ children, title }) {
    const { url, props } = usePage();
    const user = props.auth?.user;
    const [sidebarOpen, setSidebarOpen] = useState(false);

    return (
        <div className="flex h-screen overflow-hidden bg-gray-950">
            {/* Mobile overlay */}
            {sidebarOpen && (
                <div
                    className="fixed inset-0 z-40 bg-black/60 lg:hidden"
                    onClick={() => setSidebarOpen(false)}
                />
            )}

            {/* Sidebar */}
            <aside
                className={`fixed inset-y-0 left-0 z-50 flex w-64 flex-col border-r border-gray-800 bg-gray-900 transition-transform duration-200 lg:static lg:translate-x-0 ${
                    sidebarOpen ? 'translate-x-0' : '-translate-x-full'
                }`}
            >
                {/* Logo */}
                <div className="flex h-16 items-center gap-3 border-b border-gray-800 px-6">
                    <div className="flex h-8 w-8 items-center justify-center rounded-lg bg-emerald-500/20">
                        <span className="text-lg font-bold text-emerald-400">S</span>
                    </div>
                    <span className="text-lg font-bold text-white">SerpoAI</span>
                </div>

                {/* Navigation */}
                <nav className="flex-1 overflow-y-auto px-3 py-4">
                    <ul className="space-y-1">
                        {navItems.map((item) => {
                            const isActive = url === item.href || (item.href !== '/' && url.startsWith(item.href));
                            return (
                                <li key={item.name}>
                                    <Link
                                        href={item.href}
                                        className={`flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium transition-colors ${
                                            isActive
                                                ? 'bg-emerald-500/10 text-emerald-400'
                                                : 'text-gray-400 hover:bg-gray-800 hover:text-gray-200'
                                        }`}
                                    >
                                        <item.icon active={isActive} />
                                        {item.name}
                                    </Link>
                                </li>
                            );
                        })}
                    </ul>
                </nav>

                {/* User / Bottom */}
                <div className="border-t border-gray-800 px-4 py-4">
                    {user ? (
                        <div className="flex items-center gap-3 rounded-lg bg-gray-800/50 px-3 py-2">
                            {user.photo_url ? (
                                <img src={user.photo_url} alt="" className="h-8 w-8 rounded-full" />
                            ) : (
                                <div className="flex h-8 w-8 items-center justify-center rounded-full bg-emerald-500/20">
                                    <span className="text-xs font-bold text-emerald-400">
                                        {(user.first_name || '?')[0]}
                                    </span>
                                </div>
                            )}
                            <div className="flex-1 min-w-0">
                                <p className="truncate text-sm text-gray-300">
                                    {user.first_name} {user.last_name || ''}
                                </p>
                                <p className="text-xs text-gray-500">@{user.username || 'user'}</p>
                            </div>
                            <Link
                                href="/auth/logout"
                                method="post"
                                as="button"
                                className="text-xs text-gray-500 hover:text-red-400"
                            >
                                ✕
                            </Link>
                        </div>
                    ) : (
                        <a
                            href={`/auth/telegram?${new URLSearchParams(window.__telegramAuth || {})}`}
                            className="flex items-center gap-3 rounded-lg bg-blue-500/10 px-3 py-2 transition-colors hover:bg-blue-500/20"
                            id="telegram-login-area"
                        >
                            <div className="flex h-8 w-8 items-center justify-center rounded-full bg-blue-500/20">
                                <svg className="h-4 w-4 text-blue-400" viewBox="0 0 24 24" fill="currentColor">
                                    <path d="M12 0C5.373 0 0 5.373 0 12s5.373 12 12 12 12-5.373 12-12S18.627 0 12 0zm5.562 8.161l-1.97 9.279c-.146.658-.537.818-1.084.508l-3-2.211-1.447 1.394c-.16.16-.295.295-.605.295l.213-3.053 5.56-5.023c.242-.213-.054-.334-.373-.121l-6.871 4.326-2.962-.924c-.643-.204-.657-.643.136-.953l11.57-4.461c.537-.194 1.006.131.833.944z"/>
                                </svg>
                            </div>
                            <div className="flex-1 min-w-0">
                                <p className="text-sm font-medium text-blue-400">Log in with Telegram</p>
                                <p className="text-xs text-gray-500">Sync your bot data</p>
                            </div>
                        </a>
                    )}
                </div>
            </aside>

            {/* Main content */}
            <div className="flex flex-1 flex-col overflow-hidden">
                {/* Top bar */}
                <header className="flex h-16 items-center gap-4 border-b border-gray-800 bg-gray-900/50 px-4 lg:px-6">
                    <button
                        onClick={() => setSidebarOpen(true)}
                        className="text-gray-400 hover:text-white lg:hidden"
                    >
                        <svg className="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M4 6h16M4 12h16M4 18h16" />
                        </svg>
                    </button>
                    {title && <h1 className="text-lg font-semibold text-white">{title}</h1>}
                    <div className="flex-1" />
                    <a
                        href="https://t.me/SerpoAIBot"
                        target="_blank"
                        rel="noopener noreferrer"
                        className="flex items-center gap-2 rounded-lg bg-blue-500/10 px-3 py-1.5 text-sm font-medium text-blue-400 transition-colors hover:bg-blue-500/20"
                    >
                        <svg className="h-4 w-4" viewBox="0 0 24 24" fill="currentColor">
                            <path d="M12 0C5.373 0 0 5.373 0 12s5.373 12 12 12 12-5.373 12-12S18.627 0 12 0zm5.562 8.161l-1.97 9.279c-.146.658-.537.818-1.084.508l-3-2.211-1.447 1.394c-.16.16-.295.295-.605.295l.213-3.053 5.56-5.023c.242-.213-.054-.334-.373-.121l-6.871 4.326-2.962-.924c-.643-.204-.657-.643.136-.953l11.57-4.461c.537-.194 1.006.131.833.944z"/>
                        </svg>
                        Open Bot
                    </a>
                </header>

                {/* Page content */}
                <main className="flex-1 overflow-y-auto p-4 lg:p-6">
                    {children}
                </main>
            </div>
        </div>
    );
}

/* Icon components */
function DashboardIcon({ active }) {
    return (
        <svg className={`h-5 w-5 ${active ? 'text-emerald-400' : 'text-gray-500'}`} fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={1.5} d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
        </svg>
    );
}

function PricesIcon({ active }) {
    return (
        <svg className={`h-5 w-5 ${active ? 'text-emerald-400' : 'text-gray-500'}`} fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={1.5} d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
        </svg>
    );
}

function PortfolioIcon({ active }) {
    return (
        <svg className={`h-5 w-5 ${active ? 'text-emerald-400' : 'text-gray-500'}`} fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={1.5} d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
        </svg>
    );
}

function AlertsIcon({ active }) {
    return (
        <svg className={`h-5 w-5 ${active ? 'text-emerald-400' : 'text-gray-500'}`} fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={1.5} d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
        </svg>
    );
}

function AIIcon({ active }) {
    return (
        <svg className={`h-5 w-5 ${active ? 'text-emerald-400' : 'text-gray-500'}`} fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={1.5} d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
        </svg>
    );
}

function ChartsIcon({ active }) {
    return (
        <svg className={`h-5 w-5 ${active ? 'text-emerald-400' : 'text-gray-500'}`} fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={1.5} d="M7 12l3-3 3 3 4-4M8 21l4-4 4 4M3 4h18M4 4h16v12a1 1 0 01-1 1H5a1 1 0 01-1-1V4z" />
        </svg>
    );
}

function WhaleIcon({ active }) {
    return (
        <svg className={`h-5 w-5 ${active ? 'text-emerald-400' : 'text-gray-500'}`} fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={1.5} d="M13 10V3L4 14h7v7l9-11h-7z" />
        </svg>
    );
}

function VerifyIcon({ active }) {
    return (
        <svg className={`h-5 w-5 ${active ? 'text-emerald-400' : 'text-gray-500'}`} fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={1.5} d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
        </svg>
    );
}

function SignalsIcon({ active }) {
    return (
        <svg className={`h-5 w-5 ${active ? 'text-emerald-400' : 'text-gray-500'}`} fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={1.5} d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" />
        </svg>
    );
}

function ResearchIcon({ active }) {
    return (
        <svg className={`h-5 w-5 ${active ? 'text-emerald-400' : 'text-gray-500'}`} fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={1.5} d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
        </svg>
    );
}

function GridIcon({ active }) {
    return (
        <svg className={`h-5 w-5 ${active ? 'text-emerald-400' : 'text-gray-500'}`} fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={1.5} d="M4 5a1 1 0 011-1h14a1 1 0 011 1v2a1 1 0 01-1 1H5a1 1 0 01-1-1V5zm0 8a1 1 0 011-1h6a1 1 0 011 1v6a1 1 0 01-1 1H5a1 1 0 01-1-1v-6zm12 0a1 1 0 011-1h2a1 1 0 011 1v6a1 1 0 01-1 1h-2a1 1 0 01-1-1v-6z" />
        </svg>
    );
}

function SettingsIcon({ active }) {
    return (
        <svg className={`h-5 w-5 ${active ? 'text-emerald-400' : 'text-gray-500'}`} fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={1.5} d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.066 2.573c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.573 1.066c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.066-2.573c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={1.5} d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
        </svg>
    );
}
