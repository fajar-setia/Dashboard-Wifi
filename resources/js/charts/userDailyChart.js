import Chart from 'chart.js/auto';

let chartInstance = null;

function tryParseDate(v) {
    if (!v && v !== 0) return null;
    // Accept ISO-like strings first
    if (/^\d{4}-\d{2}-\d{2}/.test(String(v))) {
        const d = new Date(String(v) + 'T00:00:00');
        if (!isNaN(d)) return d;
    }
    const d2 = new Date(String(v));
    if (!isNaN(d2)) return d2;
    return null;
}

function formatMonthLabel(ym) {
    const monthNames = ['Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember'];
    const [y, m] = ym.split('-');
    return `${monthNames[Number(m) - 1]} ${y}`;
}

function buildMonthOptionsFromLabels(labels) {
    const set = new Set();
    labels.forEach(l => {
        const d = tryParseDate(l);
        if (d) {
            const y = d.getFullYear();
            const m = String(d.getMonth() + 1).padStart(2, '0');
            set.add(`${y}-${m}`);
        }
    });
    // sort descending (recent first)
    return Array.from(set).sort((a,b) => b.localeCompare(a));
}

function aggregateWeekly(labels, data) {
    const fixedLabels = ['Senin','Selasa','Rabu','Kamis','Jumat','Sabtu','Minggu'];
    const fixedData = Array(7).fill(0);

    // Hanya ambil data untuk minggu berjalan (Senin..Minggu) berdasarkan tanggal hari ini
    const today = new Date();
    const monday = new Date(today);
    monday.setDate(today.getDate() - ((today.getDay() + 6) % 7)); // Monday start
    const sunday = new Date(monday);
    sunday.setDate(monday.getDate() + 6); // Sunday end

    labels.forEach((lab, i) => {
        const d = tryParseDate(lab);
        if (d) {
            // Skip jika tanggal di luar minggu berjalan
            if (d < monday || d > sunday) return;
            // JS: Sunday=0, Monday=1 => map to Monday=0
            const idx = (d.getDay() + 6) % 7;
            fixedData[idx] += Number(data[i]) || 0;
        } else {
            // attempt to parse weekday names
            const s = String(lab).trim().toLowerCase();
            const nameMap = { 'senin':0,'monday':0,'mon':0,'selasa':1,'tuesday':1,'sel':1,'rabu':2,'wednesday':2,'kamis':3,'thursday':3,'jumat':4,'friday':4,'sabtu':5,'saturday':5,'minggu':6,'sunday':6 };
            const idx = nameMap[s];
            if (idx !== undefined) fixedData[idx] += Number(data[i]) || 0;
        }
    });
    return { labels: fixedLabels, data: fixedData, title: 'Users (Mingguan)'};
}

function aggregateMonthlyByDay(labels, data, ym) {
    const [y, m] = ym.split('-').map(Number);
    const daysInMonth = new Date(y, m, 0).getDate();
    const result = Array(daysInMonth).fill(0);
    labels.forEach((lab, i) => {
        const d = tryParseDate(lab);
        if (d && d.getFullYear() === y && (d.getMonth() + 1) === m) {
            const day = d.getDate();
            result[day - 1] += Number(data[i]) || 0;
        }
    });
    const labelNames = Array.from({length: daysInMonth}, (_,i) => String(i+1));
    return { labels: labelNames, data: result, title: `Users (${formatMonthLabel(ym)})` };
}

function renderChart(ctx, proc) {
    if (chartInstance) chartInstance.destroy();
    chartInstance = new Chart(ctx, {
        type: 'line',
        data: {
            labels: proc.labels,
            datasets: [{
                label: proc.title,
                data: proc.data,
                tension: 0.25,
                borderColor: '#3b82f6',
                backgroundColor: 'rgba(59,130,246,0.12)',
                borderWidth: 2,
                fill: true,
                pointRadius: 3,
                pointBackgroundColor: '#fff'
            }]
        },
        options: {
            maintainAspectRatio: false,
            responsive: true,
            scales: {
                y: { beginAtZero: true, ticks: { color: '#d1d5db', stepSize: 1, font: { size: 12 } }, grid: { color: 'rgba(255,255,255,0.06)' } },
                x: { ticks: { color: '#d1d5db', font: { size: 13 } }, grid: { display: false } }
            },
            plugins: {
                legend: { display: false },
                tooltip: { callbacks: { label: function(ctx) { return `Connected: ${ctx.raw}`; } } }
            }
        }
    });
}

export function renderUserDailyChart(labels, data) {
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

    // Build month selector from labels
    const controlEl = document.getElementById('userChartDailyMonth');
    const months = buildMonthOptionsFromLabels(labels);
    if (controlEl) {
        // Clear existing options and add default 'weekly' option
        controlEl.innerHTML = '';
        const optAll = document.createElement('option');
        optAll.value = 'weekly';
        optAll.textContent = 'Weekly';
        controlEl.appendChild(optAll);

        months.forEach(ym => {
            const o = document.createElement('option');
            o.value = ym;
            o.textContent = formatMonthLabel(ym);
            controlEl.appendChild(o);
        });

        controlEl.addEventListener('change', function() {
            const v = this.value;
            if (v === 'weekly') {
                const proc = aggregateWeekly(labels, data);
                renderChart(ctx, proc);
            } else {
                const proc = aggregateMonthlyByDay(labels, data, v);
                renderChart(ctx, proc);
            }
        });
    }

    // Initial render: default to weekly unless there are multiple months present
    if (months.length > 1) {
        // choose the most recent month (first in sorted months)
        const defaultMonth = months[0];
        if (controlEl) controlEl.value = defaultMonth;
        const proc = aggregateMonthlyByDay(labels, data, defaultMonth);
        renderChart(ctx, proc);
    } else {
        if (controlEl) controlEl.value = 'weekly';
        const proc = aggregateWeekly(labels, data);
        renderChart(ctx, proc);
    }
}
