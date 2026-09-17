import { useEffect, useState } from 'react';
import { Link } from 'react-router-dom';
import { api } from '../api/client';
import { useAuth } from '../context/AuthContext';

const categoryLabels = {
    extension: 'Extensions',
    desktop: 'Desktop apps',
};

function formatPrice(tool) {
    return `$${Number(tool.price).toFixed(2)}`;
}

function ToolCard({ tool, onPurchase, busy, authenticated }) {
    return (
        <div className="flex flex-col rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
            <div className="flex items-start justify-between gap-3">
                <div className="flex h-11 w-11 items-center justify-center rounded-xl bg-indigo-50 text-lg font-bold text-indigo-600">
                    {tool.icon || tool.name.charAt(0).toUpperCase()}
                </div>
                <span className="rounded-full bg-slate-100 px-2.5 py-0.5 text-xs font-medium text-slate-600">
                    {tool.pricing_model === 'subscription' ? '/month' : 'one-time'}
                </span>
            </div>

            <h3 className="mt-4 text-base font-semibold text-slate-900">{tool.name}</h3>
            <p className="mt-1.5 flex-1 text-sm leading-relaxed text-slate-600 line-clamp-3">{tool.description}</p>

            <dl className="mt-4 space-y-1.5 text-sm">
                <div className="flex items-center justify-between">
                    <dt className="text-slate-500">Price</dt>
                    <dd className="font-semibold text-slate-900">{formatPrice(tool)}</dd>
                </div>
                <div className="flex items-center justify-between">
                    <dt className="text-slate-500">Devices</dt>
                    <dd className="text-slate-700">{tool.device_limit}</dd>
                </div>
            </dl>

            <div className="mt-5 space-y-2.5">
                {tool.has_demo && tool.demo_url && (
                    <a
                        href={tool.demo_url}
                        target="_blank"
                        rel="noopener noreferrer"
                        className="flex w-full items-center justify-center gap-2 rounded-lg border border-indigo-200 bg-indigo-50 px-4 py-2.5 text-sm font-medium text-indigo-700 transition hover:bg-indigo-100"
                    >
                        Preview demo
                    </a>
                )}

                {authenticated ? (
                    <button
                        type="button"
                        disabled={busy === tool.id}
                        onClick={() => onPurchase(tool)}
                        className="w-full rounded-lg bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-indigo-700 disabled:cursor-not-allowed disabled:opacity-60"
                    >
                        {busy === tool.id ? 'Starting checkout…' : 'Get this tool'}
                    </button>
                ) : (
                    <Link
                        to="/login"
                        className="block w-full rounded-lg bg-slate-900 px-4 py-2.5 text-center text-sm font-semibold text-white transition hover:bg-slate-800"
                    >
                        Sign in to buy
                    </Link>
                )}
            </div>
        </div>
    );
}

export default function Store() {
    const { isAuthenticated } = useAuth();
    const [tools, setTools] = useState([]);
    const [loading, setLoading] = useState(true);
    const [error, setError] = useState('');
    const [busy, setBusy] = useState(null);

    useEffect(() => {
        api.get('/tools')
            .then((response) => setTools(response.data.data))
            .catch(() => setError('Could not load the store. Please try again.'))
            .finally(() => setLoading(false));
    }, []);

    const purchase = async (tool) => {
        setError('');
        setBusy(tool.id);

        try {
            const response = await api.post(`/tools/${tool.id}/checkout`);
            window.location.href = response.data.url;
        } catch (err) {
            setError(err.response?.data?.message || 'Checkout is unavailable right now.');
            setBusy(null);
        }
    };

    const categories = ['extension', 'desktop']
        .map((type) => ({ type, tools: tools.filter((tool) => tool.type === type) }))
        .filter((group) => group.tools.length > 0);

    return (
        <div className="min-h-screen bg-slate-50">
            <header className="border-b border-slate-200 bg-white">
                <div className="mx-auto flex max-w-6xl items-center justify-between px-4 py-4 sm:px-6">
                    <div className="flex items-center gap-3">
                        <div className="flex h-9 w-9 items-center justify-center rounded-lg bg-indigo-600 text-base font-bold text-white">
                            T
                        </div>
                        <span className="text-base font-semibold text-slate-900">Tool Shop</span>
                    </div>
                    <div className="flex items-center gap-3">
                        {isAuthenticated ? (
                            <Link
                                to="/dashboard"
                                className="rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-700 transition hover:bg-slate-50"
                            >
                                Dashboard
                            </Link>
                        ) : (
                            <Link
                                to="/login"
                                className="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-indigo-700"
                            >
                                Sign in
                            </Link>
                        )}
                    </div>
                </div>
            </header>

            <main className="mx-auto max-w-6xl px-4 py-10 sm:px-6">
                <div className="max-w-2xl">
                    <h1 className="text-3xl font-semibold tracking-tight text-slate-900">
                        Developer tools that respect your time
                    </h1>
                    <p className="mt-3 text-base text-slate-600">
                        Every tool is licensed to your account instantly. Try a live demo, then buy once and use it on
                        all your devices.
                    </p>
                </div>

                {error && (
                    <div className="mt-8 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
                        {error}
                    </div>
                )}

                {loading ? (
                    <p className="mt-12 text-sm text-slate-500">Loading tools…</p>
                ) : (
                    categories.map((group) => (
                        <section key={group.type} className="mt-12">
                            <h2 className="text-xl font-semibold text-slate-900">{categoryLabels[group.type]}</h2>
                            <div className="mt-5 grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3">
                                {group.tools.map((tool) => (
                                    <ToolCard
                                        key={tool.id}
                                        tool={tool}
                                        onPurchase={purchase}
                                        busy={busy}
                                        authenticated={isAuthenticated}
                                    />
                                ))}
                            </div>
                        </section>
                    ))
                )}

                {!loading && !error && categories.length === 0 && (
                    <p className="mt-12 text-sm text-slate-500">No tools available yet — come back soon.</p>
                )}
            </main>
        </div>
    );
}