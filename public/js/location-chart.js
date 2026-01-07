let locationChartInstance = null;

function updateLocationChart() {
    const chartElement = document.getElementById('locationChart');
    if (!chartElement) {
        console.warn('locationChart element not found');
        return;
    }

    const period = document.getElementById('chartPeriod').value;
    let chartData = { labels: [], datasets: [] };

    if (period === 'weekly') {
        chartData = buildWeeklyChart();
    } else {
        chartData = buildMonthlyChart();
    }

    console.log('Chart data:', chartData);
    console.log('Period:', period);

    if (chartData.labels.length === 0) {
        console.warn('No chart data available');
        return;
    }

    if (locationChartInstance) {
        locationChartInstance.destroy();
    }

    const ctx = chartElement.getContext('2d');
    locationChartInstance = new Chart(ctx, {
        type: 'bar',
        data: chartData,
        options: {
            responsive: true,
            maintainAspectRatio: false,
            indexAxis: 'x',
            scales: {
                x: {
                    stacked: true,
                    ticks: {
                        color: '#d1d5db'
                    },
                    grid: {
                        color: 'rgba(255, 255, 255, 0.1)'
                    }
                },
                y: {
                    stacked: true,
                    beginAtZero: true,
                    ticks: {
                        color: '#d1d5db',
                        callback: function(value) {
                            return Math.floor(value);
                        }
                    },
                    grid: {
                        color: 'rgba(255, 255, 255, 0.1)'
                    }
                }
            },
            plugins: {
                legend: {
                    labels: {
                        color: '#d1d5db'
                    }
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            return `${context.dataset.label}: ${context.parsed.y}`;
                        }
                    }
                }
            }
        }
    });
}

function filterLocationChart() {
    const search = document.getElementById('locationSearch').value.toLowerCase();
    const kemantren = document.getElementById('kemantrenFilter').value;
    const period = document.getElementById('chartPeriod').value;
    
    updateLocationChart();
}

function buildWeeklyChart() {
    const data = window.weeklyLocationByDay || {};
    const labels = window.dayLabels || ['Senin','Selasa','Rabu','Kamis','Jumat','Sabtu','Minggu'];
    const search = document.getElementById('locationSearch').value.toLowerCase();
    const kemantren = document.getElementById('kemantrenFilter').value;
    const locationMap = {};

    // Ensure we always have 7-day slots (Monday..Sunday)
    Object.keys(data).forEach(k => { if (!data[k]) data[k] = []; });

    // Aggregate data per lokasi per hari; map dates to weekday index (Monday=0..Sunday=6)
    Object.keys(data).forEach(dateStr => {
        const dayData = data[dateStr] || [];
        const jsDay = new Date(dateStr).getDay(); // 0=Sun,1=Mon,...6=Sat
        const dayIdx = (jsDay + 6) % 7; // convert so Mon=0 ... Sun=6

        dayData.forEach(item => {
            const loc = item.location;
            const matchSearch = loc.toLowerCase().includes(search);
            const matchKemantren = kemantren === '' || item.kemantren === kemantren;

            if (matchSearch && matchKemantren) {
                if (!locationMap[loc]) {
                    locationMap[loc] = {
                        label: loc,
                        data: new Array(7).fill(0),
                        borderWidth: 0
                    };
                }
                locationMap[loc].data[dayIdx] = item.total || 0;
            }
        });
    });

    // Assign deterministic distinct colors per location
    const locations = Object.values(locationMap);
    const colors = generateColors(locations.length);
    locations.forEach((locObj, idx) => {
        locObj.backgroundColor = colors[idx];
        locObj.borderColor = colors[idx].replace(/0\.6|0\.7|0\.5/, '1');
    });

    return {
        labels: labels, // Monday..Sunday
        datasets: locations.slice(0, 8) // Limit to 8 locations
    };
}

// Fetch monthly data from server for selected month/year
function fetchMonthlyData(month, year) {
    const url = `/dashboard/monthly-location-data?month=${month}&year=${year}`;
    fetch(url, { credentials: 'same-origin' })
        .then(res => {
            if (!res.ok) throw new Error('Network response was not ok');
            return res.json();
        })
        .then(data => {
            // set global and update chart
            window.monthlyLocationData = Array.isArray(data) ? data : [];
            updateLocationChart();
        })
        .catch(err => {
            console.error('Failed to fetch monthly location data', err);
        });
}

function buildMonthlyChart() {
    const data = window.monthlyLocationData || [];
    const search = document.getElementById('locationSearch').value.toLowerCase();
    const kemantren = document.getElementById('kemantrenFilter').value;

    // Filter
    const filtered = data.filter(d => {
        const matchSearch = d.location.toLowerCase().includes(search);
        const matchKemantren = kemantren === '' || d.kemantren === kemantren;
        return matchSearch && matchKemantren;
    });

    const labels = filtered.map(d => d.location);
    const values = filtered.map(d => d.total || 0);
    const colors = generateColors(filtered.length);

    return {
        labels: labels,
        datasets: [{
            label: 'Total User Bulanan',
            data: values,
            backgroundColor: colors,
            borderColor: colors.map(c => c.replace('0.6', '1')),
            borderWidth: 1,
            borderRadius: 6
        }]
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
document.addEventListener('DOMContentLoaded', function() {
    console.log('Location chart JS loaded');
    console.log('weeklyLocationByDay:', window.weeklyLocationByDay);
    console.log('monthlyLocationData:', window.monthlyLocationData);
    console.log('dayLabels:', window.dayLabels);
    
    if ((window.weeklyLocationByDay && Object.keys(window.weeklyLocationByDay).length > 0) || 
        (window.monthlyLocationData && window.monthlyLocationData.length > 0)) {
        updateLocationChart();
        
        // Add event listener for period and month changes
        const periodSelect = document.getElementById('chartPeriod');
        const monthSelect = document.getElementById('monthFilter');
        const searchInput = document.getElementById('locationSearch');
        const kemantrenSelect = document.getElementById('kemantrenFilter');
        
        if (periodSelect) periodSelect.addEventListener('change', updateLocationChart);
        if (monthSelect) monthSelect.addEventListener('change', function() {
            const month = parseInt(this.value, 10) || window.currentMonth || (new Date()).getMonth() + 1;
            const year = window.currentYear || (new Date()).getFullYear();
            fetchMonthlyData(month, year);
        });
        if (searchInput) searchInput.addEventListener('input', filterLocationChart);
        if (kemantrenSelect) kemantrenSelect.addEventListener('change', filterLocationChart);
    } else {
        console.warn('Location data is empty or not available');
    }
});
