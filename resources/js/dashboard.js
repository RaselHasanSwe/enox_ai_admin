import Chart from 'chart.js/auto';

const ACCENT = '#1d9e75';
const SLATE = '#94a3b8';
const WAIT = '#e11d48';
const AGENT = '#0284c7';
const RESOLVED = '#64748b';

function isDark() {
    return document.documentElement.classList.contains('dark');
}

function tickColor() {
    return isDark() ? '#94a3b8' : '#64748b';
}

function gridColor() {
    return isDark() ? 'rgba(148, 163, 184, 0.12)' : 'rgba(148, 163, 184, 0.18)';
}

function formatDay(iso, timezone) {
    const value = String(iso || '');
    const zone = timezone || 'Europe/London';
    if (value.includes(' ')) {
        const date = new Date(value.replace(' ', 'T') + 'Z');
        return date.toLocaleString('en-GB', {
            timeZone: zone,
            day: 'numeric',
            month: 'short',
            hour: '2-digit',
            minute: '2-digit',
            hour12: false,
        });
    }
    const date = new Date(`${value}T12:00:00Z`);
    return date.toLocaleDateString('en-GB', { timeZone: zone, day: 'numeric', month: 'short' });
}

function mixColor(status) {
    return {
        bot: ACCENT,
        queued: WAIT,
        with_agent: AGENT,
        resolved: RESOLVED,
    }[status] || SLATE;
}

export function initDashboardCharts() {
    const node = document.getElementById('dashboard-charts');
    if (!node) return;

    let payload = {};
    try {
        payload = JSON.parse(node.textContent || '{}');
    } catch {
        return;
    }

    Chart.defaults.font.family = 'DM Sans, sans-serif';
    Chart.defaults.color = tickColor();
    Chart.defaults.plugins.legend.labels.boxWidth = 10;
    Chart.defaults.plugins.legend.labels.boxHeight = 10;
    Chart.defaults.plugins.legend.labels.usePointStyle = true;

    const timezone = payload.timezone || 'Europe/London';
    const trend = payload.trend || [];
    const trendCanvas = document.getElementById('chart-trend');
    if (trendCanvas) {
        new Chart(trendCanvas, {
            type: 'line',
            data: {
                labels: trend.map((row) => formatDay(row.day, timezone)),
                datasets: [
                    {
                        label: 'Conversations',
                        data: trend.map((row) => row.conversations),
                        borderColor: ACCENT,
                        backgroundColor: 'rgba(29, 158, 117, 0.12)',
                        fill: true,
                        tension: 0.35,
                        pointRadius: 3,
                        pointHoverRadius: 5,
                    },
                    {
                        label: 'Handoffs',
                        data: trend.map((row) => row.handoffs),
                        borderColor: WAIT,
                        backgroundColor: 'transparent',
                        tension: 0.35,
                        pointRadius: 3,
                        pointHoverRadius: 5,
                        borderDash: [5, 4],
                    },
                ],
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                interaction: { mode: 'index', intersect: false },
                plugins: { legend: { position: 'bottom' } },
                scales: {
                    x: { grid: { display: false }, ticks: { color: tickColor() } },
                    y: {
                        beginAtZero: true,
                        ticks: { precision: 0, color: tickColor() },
                        grid: { color: gridColor() },
                        border: { display: false },
                    },
                },
            },
        });
    }

    const mix = (payload.statusMix || []).filter((row) => Number(row.count) > 0);
    const mixCanvas = document.getElementById('chart-mix');
    const mixLegend = document.getElementById('chart-mix-legend');
    const mixWrap = mixCanvas?.closest('.dash-mix');
    if (mixCanvas && mix.length) {
        new Chart(mixCanvas, {
            type: 'doughnut',
            data: {
                labels: mix.map((row) => row.label),
                datasets: [{
                    data: mix.map((row) => row.count),
                    backgroundColor: mix.map((row) => mixColor(row.status)),
                    borderWidth: 0,
                    hoverOffset: 4,
                }],
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: '62%',
                layout: { padding: 0 },
                plugins: {
                    legend: { display: false },
                },
            },
        });
        if (mixLegend) {
            mixLegend.innerHTML = mix.map((row) => (
                `<li><span class="dash-mix-dot" style="background:${mixColor(row.status)}"></span>`
                + `<span>${row.label}</span><strong>${row.count}</strong></li>`
            )).join('');
        }
    } else if (mixWrap) {
        mixWrap.innerHTML = '<div class="dash-chart dash-chart-empty">No conversations in this period.</div>';
    }
}
