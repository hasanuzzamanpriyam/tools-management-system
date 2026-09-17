import { useEffect, useRef } from 'react';
import Chart from 'chart.js/auto';

const COLORS = {
    indigo: '#6366f1',
    sky: '#0ea5e9',
    emerald: '#10b981',
    rose: '#f43f5e',
    slate: '#64748b',
};

export default function ChartWidget({ type = 'bar', labels, datasets, options }) {
    const canvasRef = useRef(null);

    useEffect(() => {
        const chart = new Chart(canvasRef.current, {
            type,
            data: { labels, datasets },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false,
                    },
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        grid: {
                            color: 'rgba(148, 163, 184, 0.15)',
                        },
                        border: {
                            display: false,
                        },
                    },
                    x: {
                        grid: {
                            display: false,
                        },
                        border: {
                            display: false,
                        },
                    },
                },
                ...options,
            },
        });

        return () => chart.destroy();
    }, [type, labels, datasets, options]);

    return <canvas ref={canvasRef} aria-label="Chart" />;
}

export { COLORS };