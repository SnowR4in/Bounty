(function () {
    'use strict';

    const CHART_PALETTE = [
        '#34d399', '#10b981', '#6ee7b7', '#2dd4bf', '#059669',
        '#14b8a6', '#a7f3d0', '#0d9488', '#5eead4', '#047857'
    ];

    function makeDataset(label, data, color) {
        return {
            label: label,
            data: data,
            borderColor: color,
            backgroundColor: color + '20',
            borderWidth: 2,
            pointBackgroundColor: color,
            pointRadius: 3,
            pointHoverRadius: 5,
            tension: 0.4,
            hidden: false
        };
    }

    function renderChart() {
        const canvas = document.getElementById('statsChart');
        if (!canvas || typeof Chart === 'undefined') return;

        const chart = (window.OW_ADMIN_DATA && window.OW_ADMIN_DATA.chart) || {};
        const labels = chart.labels || [];
        const series = chart.series || {};

        const datasetDefs = [
            ['Active Users', 'active_users'],
            ['New Users', 'new_users'],
            ['Withdrawals (Count)', 'withdrawals'],
            ['Withdrawals Paid (USDT)', 'withdrawals_paid'],
            ['Timed Faucet Claims', 'timed_claims'],
            ['Timed Faucet Paid (USDT)', 'timed_paid'],
            ['Instant Faucet Claims', 'instant_claims'],
            ['Instant Faucet Paid (USDT)', 'instant_paid'],
            ['Videos Completed', 'video_completed'],
            ['Videos Paid (USDT)', 'video_paid'],
            ['PTC Completed', 'ptc_completed'],
            ['PTC Paid (USDT)', 'ptc_paid'],
            ['Tasks Completed', 'tasks_completed'],
            ['Tasks Paid (USDT)', 'tasks_paid'],
            ['Offerwall Completed', 'offerwall_completed'],
            ['Offerwall Paid (USDT)', 'offerwall_paid'],
            ['Shortlinks Completed', 'shortlinks_completed'],
            ['Shortlinks Paid (USDT)', 'shortlinks_paid'],
            ['Wheel Spins', 'wheel_spins'],
            ['Wheel Paid (USDT)', 'wheel_paid'],
            ['Daily Bonus Completed', 'daily_bonus_completed'],
            ['Daily Bonus Paid (USDT)', 'daily_bonus_paid'],
            ['Referral Paid (USDT)', 'referral_paid']
        ];

        const datasets = datasetDefs.map(function (def, i) {
            return makeDataset(def[0], series[def[1]] || [], CHART_PALETTE[i % CHART_PALETTE.length]);
        });

        new Chart(canvas.getContext('2d'), {
            type: 'line',
            data: {
                labels: labels,
                datasets: datasets
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                interaction: {
                    mode: 'index',
                    intersect: false
                },
                plugins: {
                    legend: {
                        position: 'top',
                        labels: {
                            usePointStyle: true,
                            boxWidth: 8,
                            padding: 15,
                            font: { size: 11 },
                            color: '#a1a1aa'
                        }
                    },
                    tooltip: {
                        backgroundColor: '#101114',
                        borderColor: '#27272a',
                        borderWidth: 1,
                        titleColor: '#f4f4f5',
                        bodyColor: '#a1a1aa',
                        titleFont: { size: 13, weight: 'bold' },
                        bodyFont: { size: 12 },
                        padding: 12,
                        cornerRadius: 8,
                        displayColors: true
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        grid: { color: 'rgba(255, 255, 255, 0.06)', drawBorder: false },
                        ticks: { font: { size: 11 }, color: '#a1a1aa' }
                    },
                    x: {
                        grid: { display: false, drawBorder: false },
                        ticks: { font: { size: 11 }, color: '#a1a1aa' }
                    }
                }
            }
        });
    }

    document.addEventListener('DOMContentLoaded', renderChart);
})();