let locationChartInstance = null;

function updateLocationChart() {
    const chartElement = document.getElementById("locationChart");
    if (!chartElement) {
        console.warn("locationChart element not found");
        return;
    }

    const period = document.getElementById("chartPeriod").value;
    let chartData = { labels: [], datasets: [] };

    if (period === "weekly") {
        chartData = buildWeeklyChart();
    } else {
        // For monthly, fetch a larger Top (client will slice Top N + aggregate Others), then render
        const month = parseInt(document.getElementById('monthFilter')?.value || window.currentMonth || (new Date().getMonth()+1), 10);
        const year = window.currentYear || new Date().getFullYear();
        const topLimit = parseInt(document.getElementById('topLimit')?.value || 10, 10);
        const kemantren = document.getElementById('kemantrenFilter')?.value || '';
        const search = document.getElementById('locationSearch')?.value || '';

        // build a simple cache key so we don't repeatedly fetch the same params
        const fetchKey = `${month}|${year}|${encodeURIComponent(kemantren)}|${encodeURIComponent(search)}|${topLimit}`;

        // if we already fetched this combination, render directly
        if (window._monthlyFetchedKey === fetchKey && Array.isArray(window.monthlyLocationData)) {
            chartData = buildMonthlyChart();
        } else {
            // fetch more rows for drill-down; adjust `maxFetch` as appropriate for your dataset size
            const maxFetch = 500;
            fetchMonthlyData(month, year, maxFetch, kemantren, search, topLimit, fetchKey);
            return; // will re-render after fetch
        }
    }

    // if (chartData.labels.length === 0) {
    //     console.warn("No chart data available");
    //     return;
    // }

    if (locationChartInstance) {
        locationChartInstance.destroy();
    }

    const ctx = chartElement.getContext("2d");
    locationChartInstance = new Chart(ctx, {
        type: "bar",
        data: chartData,
        options: {
            responsive: true,
            maintainAspectRatio: false,
            indexAxis: "y",
            scales: {
                x: {
                    stacked: true,
                    beginAtZero: true,
                    ticks: {
                        stepSize: 1,
                        precision: 0,
                        color: "#d1d5db",
                    },
                    grid: {
                        color: "rgba(255,255,255,0.1)",
                    },
                },
                y: {
                    stacked: true,
                    ticks: {
                        color: "#d1d5db",
                    },
                    grid: {
                        color: "rgba(255,255,255,0.1)",
                    },
                },
            },
            plugins: {
                legend: {
                    labels: {
                        color: "#d1d5db",
                    },
                },
                tooltip: {
                    callbacks: {
                        label: function (context) {
                            const value = context.parsed.x; // PENTING
                            return `${context.dataset.label}: ${value}`;
                        },
                    },
                },
                // enable click handling for drill-down
                datalabels: false,
            },
            onClick: function (evt, elements) {
                if (!elements || elements.length === 0) return;
                const el = elements[0];
                const idx = el.index;
                const label = this.data.labels[idx];
                if (label === 'Lainnya' || label === 'Others') {
                    // show modal with list
                    const others = window._monthlyOthers || [];
                    showOthersModal(others);
                }
            }
        },
    });
}

function filterLocationChart() {
    const search = document
        .getElementById("locationSearch")
        .value.toLowerCase();
    const kemantren = document.getElementById("kemantrenFilter").value;
    const period = document.getElementById("chartPeriod").value;

    updateLocationChart();
}

function buildWeeklyChart() {
    const data = window.weeklyLocationByDay || {};
    const labels = window.dayLabels || [
        "Senin",
        "Selasa",
        "Rabu",
        "Kamis",
        "Jumat",
        "Sabtu",
        "Minggu",
    ];
    const search = document
        .getElementById("locationSearch")
        .value.toLowerCase();
    const kemantren = document.getElementById("kemantrenFilter").value;
    const locationMap = {};
    const topLimit = parseInt(document.getElementById("topLimit")?.value || 8, 10);

    // Ensure we always have 7-day slots (Monday..Sunday)
    Object.keys(data).forEach((k) => {
        if (!data[k]) data[k] = [];
    });

    // Aggregate data per lokasi per hari; map dates to weekday index (Monday=0..Sunday=6)
    Object.keys(data).forEach((dateStr) => {
        const dayData = data[dateStr] || [];
        const jsDay = new Date(dateStr).getDay(); // 0=Sun,1=Mon,...6=Sat
        const dayIdx = (jsDay + 6) % 7; // convert so Mon=0 ... Sun=6

        dayData.forEach((item) => {
            const loc = item.location || 'Unknown';
            const k = item.kemantren || '';
            const compositeKey = `${loc}||${k}`; // avoid collisions when same location in different kemantren
            const matchSearch = loc.toLowerCase().includes(search);
            const matchKemantren = kemantren === "" || k === kemantren;

            if (matchSearch && matchKemantren) {
                if (!locationMap[compositeKey]) {
                    const label = k ? `${loc} (${k})` : loc;
                    locationMap[compositeKey] = {
                        label: label,
                        data: new Array(7).fill(0),
                        borderWidth: 0,
                    };
                }
                // accumulate in case multiple rows exist for same location/day
                locationMap[compositeKey].data[dayIdx] += Number(item.total || 0);
            }
        });
    });

    // Assign deterministic distinct colors per location
    const locations = Object.values(locationMap);
    const colors = generateColors(locations.length);
    locations.forEach((locObj, idx) => {
        locObj.backgroundColor = colors[idx];
        locObj.borderColor = colors[idx].replace(/0\.6|0\.7|0\.5/, "1");
    });

    return {
        labels: labels, // Monday..Sunday
        datasets: locations.slice(0, topLimit), // Limit to top N locations
    };
}

// Fetch monthly data from server for selected month/year
function fetchMonthlyData(month, year, top, kemantren, search, topLimitForDisplay, fetchKey) {
    const kem = encodeURIComponent(kemantren || '');
    const sch = encodeURIComponent(search || '');
    const url = `/dashboard/monthly-location-data?month=${month}&year=${year}&top=${top}&kemantren=${kem}&search=${sch}`;
    fetch(url, { credentials: "same-origin" })
        .then((res) => {
            if (!res.ok) throw new Error("Network response was not ok");
            return res.json();
        })
        .then((data) => {
            // set global and update chart
            window.monthlyLocationData = Array.isArray(data) ? data : [];
            // store also topLimit for display logic
            window._monthlyDisplayLimit = topLimitForDisplay || parseInt(document.getElementById('topLimit')?.value || 10, 10);
            // store fetched key so we don't re-fetch unnecessarily
            if (fetchKey) window._monthlyFetchedKey = fetchKey;
            updateLocationChart();
        })
        .catch((err) => {
            console.error("Failed to fetch monthly location data", err);
        });
}

function buildMonthlyChart() {
    const data = window.monthlyLocationData || [];
    const search = document
        .getElementById("locationSearch")
        .value.toLowerCase();
    const kemantren = document.getElementById("kemantrenFilter").value;
    // Display limit (Top N) chosen by user; if _monthlyDisplayLimit set, prefer it
    const topLimit = window._monthlyDisplayLimit || parseInt(document.getElementById("topLimit")?.value || 10, 10);

    // Filter first
    const filtered = data.filter((d) => {
        const matchSearch = (d.location || '').toString().toLowerCase().includes(search);
        const matchKemantren = kemantren === "" || (d.kemantren || '') === kemantren;
        return matchSearch && matchKemantren;
    });

    // Sort DESC by total
    filtered.sort((a, b) => (b.total || 0) - (a.total || 0));

    // If nothing, show empty chart
    if (filtered.length === 0) {
        return {
            labels: ["Tidak ada data"],
            datasets: [
                {
                    label: "Total User Bulanan",
                    data: [0],
                    backgroundColor: ["rgba(255,255,255,0.2)"],
                },
            ],
        };
    }

    // Split into top N and others
    const topData = filtered.slice(0, topLimit);
    const others = filtered.slice(topLimit);
    const othersTotal = others.reduce((s, it) => s + (it.total || 0), 0);

    const labels = topData.map((d) => d.location);
    const dataVals = topData.map((d) => d.total || 0);

    if (othersTotal > 0) {
        labels.push('Lainnya');
        dataVals.push(othersTotal);
        // store others for drill-down
        window._monthlyOthers = others;
    } else {
        window._monthlyOthers = [];
    }

    // Generate colors: one color for top bars, darker for 'Lainnya'
    const baseColor = 'rgba(59,130,246,0.8)';
    const othersColor = 'rgba(107,114,128,0.9)';

    // Build dataset with per-bar colors
    const backgroundColors = topData.map((_, i) => generateColors(topData.length)[i] || baseColor);
    if (othersTotal > 0) backgroundColors.push(othersColor);

    return {
        labels: labels,
        datasets: [
            {
                label: `Top ${topLimit} Lokasi Bulanan (plus Others)`,
                data: dataVals,
                backgroundColor: backgroundColors,
                borderRadius: 6,
            },
        ],
    };
}

function generateRandomColor() {
    // Return a random HSL-based color with alpha 0.7
    const h = Math.floor(Math.random() * 360);
    const s = 65 + Math.floor(Math.random() * 20); // 65-85
    const l = 45 + Math.floor(Math.random() * 10); // 45-55
    return `hsla(${h}, ${s}%, ${l}%, 0.7)`;
}

function generateColors(count) {
    // Generate `count` visually distinct colors using evenly spaced hues
    const result = [];
    for (let i = 0; i < count; i++) {
        const hue = Math.round((360 * i) / Math.max(1, count));
        const s = 70;
        const l = 50;
        result.push(`hsla(${hue}, ${s}%, ${l}%, 0.6)`);
    }
    return result;
}

// Initialize on page load
document.addEventListener("DOMContentLoaded", function () {

    if (
        (window.weeklyLocationByDay &&
            Object.keys(window.weeklyLocationByDay).length > 0) ||
        (window.monthlyLocationData && window.monthlyLocationData.length > 0)
    ) {
        updateLocationChart();

        // Add event listener for period and month changes
        const periodSelect = document.getElementById("chartPeriod");
        const monthSelect = document.getElementById("monthFilter");
        const searchInput = document.getElementById("locationSearch");
        const kemantrenSelect = document.getElementById("kemantrenFilter");

        if (periodSelect)
            periodSelect.addEventListener("change", updateLocationChart);
        if (monthSelect)
            monthSelect.addEventListener("change", function () {
                const month =
                    parseInt(this.value, 10) ||
                    window.currentMonth ||
                    new Date().getMonth() + 1;
                const year = window.currentYear || new Date().getFullYear();
                fetchMonthlyData(month, year);
            });
        if (searchInput)
            searchInput.addEventListener("input", filterLocationChart);
        if (kemantrenSelect)
            kemantrenSelect.addEventListener("change", filterLocationChart);
    } else {
        console.warn("Location data is empty or not available");
    }
});

// Modal helpers for Others drill-down
function showOthersModal(list) {
    const modal = document.getElementById('othersModal');
    const container = document.getElementById('othersList');
    container.innerHTML = '';
    if (!Array.isArray(list) || list.length === 0) {
        container.innerHTML = '<div class="text-gray-400">Tidak ada data</div>';
    } else {
        list.forEach(item => {
            const el = document.createElement('div');
            el.className = 'p-2 bg-slate-700/50 rounded';
            el.innerHTML = `<div class="flex justify-between"><div class="font-medium">${(item.location||'Unknown')}</div><div class="text-sm text-gray-300">${item.total||0}</div></div><div class="text-xs text-gray-400">${item.kemantren||''}</div>`;
            container.appendChild(el);
        });
    }
    modal.classList.remove('hidden');
    modal.classList.add('flex');
}

function hideOthersModal() {
    const modal = document.getElementById('othersModal');
    modal.classList.add('hidden');
    modal.classList.remove('flex');
}
