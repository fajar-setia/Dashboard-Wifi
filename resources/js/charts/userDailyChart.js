import Chart from 'chart.js/auto';

export function renderUserDailyChart(labels, data) {
    const ctx = document.getElementById('userChartDaily').getContext('2d');

    new Chart(ctx, {
        type: 'bar',
        data: {
            labels: labels,
            datasets: [{
                label: 'Users Harian',
                data: data,
                tension: 0.3,
                borderColor: '#3b82f6',
                backgroundColor: 'rgba(59, 130, 246, 0.2)',
                borderWidth: 2,
                fill: true,
                pointRadius: 4,
                pointBackgroundColor: '#3b82f6'
            }]
        },
        options: {
            maintainAspectRatio: false,
            responsive: true,
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        color: '#d1d5db'
                    }
                },
                x: {
                    ticks: {
                        color: '#d1d5db'
                    }
                }
            },
            plugins: {
                legend: {
                    labels: {
                        color: '#d1d5db'
                    }
                }
            }
        }
    });
}
