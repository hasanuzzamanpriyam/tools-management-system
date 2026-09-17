import { useCallback, useEffect, useMemo, useState } from 'react';
import { api } from '../api/client';
import ChartWidget, { COLORS } from '../components/ChartWidget';

function formatAmount(value) {
    return `$${Number(value).toFixed(2)}`;
}

function UtilizationBar({ active, limit }) {
    const pct = limit > 0 ? Math.min(Math.round((active / limit) * 100), 100) : 0;

    return (
        <div className="w-32">
            <div className="h-2 overflow-hidden rounded-full bg-slate-100">
                <div className={`h-full rounded-full ${pct >= 100 ? 'bg-rose-500' : pct >= 75 ? 'bg-amber-500' : 'bg-indigo-500'}`} style={{ width: `${pct}%` }} />
            </div>
            <p className="mt-1 text-xs text-slate-500">{pct}% used</p>
        </div>
    );
}

export default function Analytics() {
    const [data, setData] = useState(null);
    const [loading, setLoading] = useState(true);
    const [error, setError] = useState('');

    const load = useCallback(async () => {
        setLoading(true);

        try {
            const { data } = await api.get('/admin/analytics');
            setData(data.data ?? {});
            setError('');
        } catch {
            setError('Failed to load analytics.');
        } finally {
            setLoading(false);
        }
    }, []);

    useEffect(() => {
        load();
    }, [load]);

    const revenueChart = useMemo(() => {
        if (!data) return null;

        return {
            labels: data.revenue.map((month) => month.label),
            datasets: [
                {
                    label: 'Revenue',
                    data: data.revenue.map((month) => month.revenue),
                    backgroundColor: COLORS.indigo,
                    borderRadius: 6,
                },
            ],
        };
    }, [data]);

    const churnChart = useMemo(() => {
        if (!data) return null;

        return {
            labels: data.churn.map((month) => month.label),
            datasets: [
                {
                    label: 'Churned subscriptions',
                    data: data.churn.map((month) => month.churned),
                    backgroundColor: COLORS.rose,
                    borderRadius: 6,
                },
            ],
        };
    }, [data]);

    if (loading) {
        return <div className="rounded-xl border border-slate-200 bg-white p-16 text-center text-sm text-slate-400">Loading analytics…</div>;
    }

    if (error || !data) {
        return (
            <div>
                <div className="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">{error}</div>
                <button
                    type="button"
                    onClick={load}
                    className="mt-4 rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700"
                >
                    Retry
                </button>
            </div>
        );
    }

    return (
        <div className="space-y-6">
            <div className="grid grid-cols-1 gap-6 lg:grid-cols-2">
                <section className="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
                    <div className="mb-4 flex items-center justify-between">
                        <div>
                            <h2 className="text-sm font-semibold text-slate-900">Monthly revenue</h2>
                            <p className="text-xs text-slate-500">Last 12 months, excluding refunds</p>
                        </div>
                        <p className="text-2xl font-semibold text-slate-900">
                            {formatAmount(
                                data.revenue.reduce((sum, month) => sum + Number(month.revenue), 0),
                            )}
                        </p>
                    </div>
                    <div className="h-64">
                        <ChartWidget
                            type="bar"
                            labels={revenueChart.labels}
                            datasets={revenueChart.datasets}
                            options={{
                                plugins: {
                                    tooltip: {
                                        callbacks: {
                                            label: (context) => ` ${formatAmount(context.parsed.y)} (${data.revenue[context.dataIndex].purchases} purchases)`,
                                        },
                                    },
                                },
                            }}
                        />
                    </div>
                </section>

                <section className="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
                    <div className="mb-4">
                        <h2 className="text-sm font-semibold text-slate-900">Subscription churn</h2>
                        <p className="text-xs text-slate-500">Expired or failed subscriptions per month</p>
                    </div>
                    <div className="h-64">
                        <ChartWidget type="bar" labels={churnChart.labels} datasets={churnChart.datasets} />
                    </div>
                </section>
            </div>

            <section className="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
                <div className="flex items-center justify-between border-b border-slate-100 px-6 py-4">
                    <h2 className="text-sm font-semibold text-slate-900">Usage analytics</h2>
                    <p className="text-xs text-slate-500">Active devices and total activations per tool</p>
                </div>

                {data.usage.length ? (
                    <table className="min-w-full divide-y divide-slate-200">
                        <thead className="bg-slate-50">
                            <tr>
                                <th className="px-6 py-3 text-left text-xs font-semibold tracking-wide text-slate-500 uppercase">Tool</th>
                                <th className="px-6 py-3 text-left text-xs font-semibold tracking-wide text-slate-500 uppercase">Type</th>
                                <th className="px-6 py-3 text-left text-xs font-semibold tracking-wide text-slate-500 uppercase">Device limit</th>
                                <th className="px-6 py-3 text-left text-xs font-semibold tracking-wide text-slate-500 uppercase">Active devices</th>
                                <th className="px-6 py-3 text-left text-xs font-semibold tracking-wide text-slate-500 uppercase">Total activations</th>
                                <th className="px-6 py-3 text-left text-xs font-semibold tracking-wide text-slate-500 uppercase">Utilization</th>
                            </tr>
                        </thead>
                        <tbody className="divide-y divide-slate-100">
                            {data.usage.map((tool) => (
                                <tr key={tool.id} className="transition hover:bg-slate-50">
                                    <td className="px-6 py-3.5 text-sm font-medium text-slate-900">{tool.name}</td>
                                    <td className="px-6 py-3.5 text-sm text-slate-600 capitalize">{tool.type}</td>
                                    <td className="px-6 py-3.5 text-sm text-slate-600">{tool.device_limit}</td>
                                    <td className="px-6 py-3.5 text-sm font-medium text-slate-900">{tool.active_devices}</td>
                                    <td className="px-6 py-3.5 text-sm text-slate-600">{tool.activations_total}</td>
                                    <td className="px-6 py-3.5">
                                        <UtilizationBar active={tool.active_devices} limit={tool.device_limit} />
                                    </td>
                                </tr>
                            ))}
                        </tbody>
                    </table>
                ) : (
                    <div className="p-10 text-center text-sm text-slate-400">No usage data yet.</div>
                )}
            </section>
        </div>
    );
}