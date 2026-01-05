import Chart from 'chart.js/auto';

let chartInstance = null;

export function renderUserDailyChart(labels, data) {
    // Tunggu element ready
    const el = document.getElementById('userChartDaily');
    if (!el) {
        console.error('Element #userChartDaily tidak ditemukan');
        return;
    }

    const ctx = el.getContext('2d');
    if (!ctx) {
        console.error('Tidak bisa get context dari canvas');
        return;
    }

    // Destroy chart lama jika ada
    if (chartInstance) {
        chartInstance.destroy();
    }

    const fixedLabels = ['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu', 'Minggu'];

    function parseToIndex(label) {
        if (label === null || label === undefined || label === '') return -1;
        const s = String(label).trim().toLowerCase();

        if (/^\d{4}-\d{2}-\d{2}/.test(s)) {
            const d = new Date(s + 'T00:00:00');
            if (!isNaN(d)) return (d.getDay() + 6) % 7;
        }

        const d2 = new Date(s);
        if (!isNaN(d2)) return (d2.getDay() + 6) % 7;

        const nameMap = {
            'monday': 0, 'mon': 0, 'senin': 0,
            'tuesday': 1, 'tue': 1, 'tues': 1, 'selasa': 1, 'sel': 1,
            'wednesday': 2, 'wed': 2, 'rabu': 2,
            'thursday': 3, 'thu': 3, 'thurs': 3, 'kamis': 3,
            'friday': 4, 'fri': 4, 'jumat': 4, 'jum': 4,
            'saturday': 5, 'sat': 5, 'sabtu': 5, 'sab': 5,
            'sunday': 6, 'sun': 6, 'minggu': 6, 'min': 6
        };
        return (nameMap[s] !== undefined) ? nameMap[s] : -1;
    }

    const fixedData = Array(7).fill(0);
    for (let i = 0; i < labels.length; i++) {
        const idx = parseToIndex(labels[i]);
        if (idx >= 0) {
            const val = Number(data[i]) || 0;
            fixedData[idx] += val;
        }
    }

    chartInstance = new Chart(ctx, {
        type: 'line',
        data: {
            labels: fixedLabels,
            datasets: [{
                label: 'Users (Mingguan)',
                data: fixedData,
                tension: 0.3,
                borderColor: '#3b82f6',
                backgroundColor: 'rgba(59, 130, 246, 0.12)',
                borderWidth: 2,
                fill: true,
                pointRadius: 4,
                pointBackgroundColor: '#fff'
            }]
        },
        options: {
            maintainAspectRatio: false,
            responsive: true,
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: { color: '#d1d5db', stepSize: 1, font: { size: 12 } },
                    grid: { color: 'rgba(255,255,255,0.06)' }
                },
                x: {
                    ticks: { color: '#d1d5db', font: { size: 13 } },
                    grid: { display: false }
                }
            },
            plugins: {
                legend: { display: false },
                tooltip: {
                    callbacks: {
                        label: function(ctx) { return `Connected: ${ctx.raw}`; }
                    }
                }
            }
        }
    });

    console.log('Chart rendered:', chartInstance);
}