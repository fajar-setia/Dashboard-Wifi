/**
 * Location Chart with Realtime Auto-Update
 * Auto-refresh setiap 5 menit
 */

let locationChartInstance = null;
let autoUpdateInterval = null;
let isUpdating = false;
let lastUpdateTime = null;

/**
 * Main function to update/render location chart
 */
function updateLocationChart() {
    const chartElement = document.getElementById("locationChart");
    if (!chartElement) {
        console.warn("locationChart element not found");
        return;
    }

    const period = document.getElementById("chartPeriod")?.value || 'weekly';
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
                            const value = context.parsed.x;
                            return `${context.dataset.label}: ${value}`;
                        },
                    },
                },
                datalabels: false,
            },
            onClick: function (evt, elements) {
                if (!elements || elements.length === 0) return;
                const el = elements[0];
                const idx = el.index;
                const label = this.data.labels[idx];
                if (label === 'Lainnya' || label === 'Others') {
                    const others = window._monthlyOthers || [];
                    showOthersModal(others);
                }
            }
        },
    });
}

/**
 * Filter location chart based on search and kemantren
 */
function filterLocationChart() {
    updateLocationChart();
}

/**
 * Build weekly stacked bar chart
 */
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
    const search = document.getElementById("locationSearch")?.value.toLowerCase() || '';
    const kemantren = document.getElementById("kemantrenFilter")?.value || '';
    const locationMap = {};
    const topLimit = parseInt(document.getElementById("topLimit")?.value || 8, 10);

    // Ensure we always have 7-day slots (Monday..Sunday)
    Object.keys(data).forEach((k) => {
        if (!data[k]) data[k] = [];
    });

    // Aggregate data per lokasi per hari
    Object.keys(data).forEach((dateStr) => {
        const dayData = data[dateStr] || [];
        const jsDay = new Date(dateStr).getDay();
        const dayIdx = (jsDay + 6) % 7; // Mon=0 ... Sun=6

        dayData.forEach((item) => {
            const loc = item.location || 'Unknown';
            const k = item.kemantren || '';
            const compositeKey = `${loc}||${k}`;
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
                locationMap[compositeKey].data[dayIdx] += Number(item.total || 0);
            }
        });
    });

    // Assign colors
    const locations = Object.values(locationMap);
    const colors = generateColors(locations.length);
    locations.forEach((locObj, idx) => {
        locObj.backgroundColor = colors[idx];
        locObj.borderColor = colors[idx].replace(/0\.6|0\.7|0\.5/, "1");
    });

    return {
        labels: labels,
        datasets: locations.slice(0, topLimit),
    };
}

/**
 * Fetch monthly data from server
 */
function fetchMonthlyData(month, year, top, kemantren, search, topLimitForDisplay, fetchKey) {
    const kem = encodeURIComponent(kemantren || '');
    const sch = encodeURIComponent(search || '');
    const url = `/dashboard/monthly-location-data?month=${month}&year=${year}&top=${top}&kemantren=${kem}&search=${sch}`;
    
    showLoadingIndicator();
    
    fetch(url, { credentials: "same-origin" })
        .then((res) => {
            if (!res.ok) throw new Error("Network response was not ok");
            return res.json();
        })
        .then((data) => {
            window.monthlyLocationData = Array.isArray(data) ? data : [];
            window._monthlyDisplayLimit = topLimitForDisplay || parseInt(document.getElementById('topLimit')?.value || 10, 10);
            if (fetchKey) window._monthlyFetchedKey = fetchKey;
            updateLocationChart();
            hideLoadingIndicator();
        })
        .catch((err) => {
            console.error("Failed to fetch monthly location data", err);
            hideLoadingIndicator();
            showNotification('Gagal memuat data bulanan', 'error');
        });
}

/**
 * Build monthly horizontal bar chart
 */
function buildMonthlyChart() {
    const data = window.monthlyLocationData || [];
    const search = document.getElementById("locationSearch")?.value.toLowerCase() || '';
    const kemantren = document.getElementById("kemantrenFilter")?.value || '';
    const topLimit = window._monthlyDisplayLimit || parseInt(document.getElementById("topLimit")?.value || 10, 10);

    // Filter
    const filtered = data.filter((d) => {
        const matchSearch = (d.location || '').toString().toLowerCase().includes(search);
        const matchKemantren = kemantren === "" || (d.kemantren || '') === kemantren;
        return matchSearch && matchKemantren;
    });

    // Sort DESC
    filtered.sort((a, b) => (b.total || 0) - (a.total || 0));

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

    // Split top N and others
    const topData = filtered.slice(0, topLimit);
    const others = filtered.slice(topLimit);
    const othersTotal = others.reduce((s, it) => s + (it.total || 0), 0);

    const labels = topData.map((d) => d.location);
    const dataVals = topData.map((d) => d.total || 0);

    if (othersTotal > 0) {
        labels.push('Lainnya');
        dataVals.push(othersTotal);
        window._monthlyOthers = others;
    } else {
        window._monthlyOthers = [];
    }

    const _colors = generateColors(topData.length);
    const backgroundColors = topData.map((_, i) => _colors[i]);
    if (othersTotal > 0) backgroundColors.push('rgba(107,114,128,0.9)');

    return {
        labels: labels,
        datasets: [
            {
                label: `Top ${topLimit} Lokasi Bulanan`,
                data: dataVals,
                backgroundColor: backgroundColors,
                borderRadius: 6,
            },
        ],
    };
}

/**
 * Generate distinct colors
 */
function generateColors(count) {
    const result = [];
    for (let i = 0; i < count; i++) {
        const hue = Math.round((360 * i) / Math.max(1, count));
        result.push(`hsla(${hue}, 70%, 50%, 0.6)`);
    }
    return result;
}

/**
 * ============================================
 * REALTIME AUTO-UPDATE FUNCTIONS
 * ============================================
 */

/**
 * Trigger realtime stats collection and update chart
 */
async function triggerRealtimeUpdate() {
    if (isUpdating) {
        console.log('⏳ Update already in progress, skipping...');
        return;
    }
    
    isUpdating = true;
    showLoadingIndicator();
    updateLastUpdateDisplay('Memperbarui...');
    
    try {
        console.log('🔄 Triggering realtime stats update...');
        
        // Get CSRF token
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;
        
        // Trigger server-side stats collection
        const response = await fetch('/dashboard/trigger-realtime-stats', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                ...(csrfToken && { 'X-CSRF-TOKEN': csrfToken })
            }
        });
        
        if (!response.ok) {
            throw new Error(`HTTP ${response.status}`);
        }
        
        const result = await response.json();
        
        if (result.success) {
            console.log('✅ Stats updated:', result.timestamp);
            
            // Wait untuk database commit
            await new Promise(resolve => setTimeout(resolve, 1500));
            
            // Refresh data berdasarkan period
            await refreshChartData();
            
            lastUpdateTime = new Date();
            updateLastUpdateDisplay();
            showNotification('Data berhasil diperbarui', 'success');
        } else {
            throw new Error(result.error || 'Update failed');
        }
        
    } catch (error) {
        console.error('❌ Error updating stats:', error);
        
        // Fallback: tetap refresh chart dengan data yang ada
        try {
            await refreshChartData();
            showNotification('Menampilkan data terakhir', 'warning');
        } catch (e) {
            showNotification('Gagal memperbarui data', 'error');
        }
    } finally {
        isUpdating = false;
        hideLoadingIndicator();
    }
}

/**
 * Refresh chart data berdasarkan period yang aktif
 */
async function refreshChartData() {
    const period = document.getElementById("chartPeriod")?.value || 'weekly';
    
    if (period === 'weekly') {
        // Fetch weekly data
        const kemantren = document.getElementById('kemantrenFilter')?.value || '';
        const search = document.getElementById('locationSearch')?.value || '';
        
        const params = new URLSearchParams();
        if (kemantren) params.append('kemantren', kemantren);
        if (search) params.append('search', search);
        params.append('top', document.getElementById('topLimit')?.value || '8');
        
        const response = await fetch(`/dashboard/weekly-location-data?${params}`);
        const data = await response.json();
        
        if (data && !data.isEmpty) {
            window.weeklyLocationByDay = data.data;
            window.dayLabels = data.labels;
            updateLocationChart();
        }
    } else {
        // Fetch monthly data
        const month = parseInt(document.getElementById('monthFilter')?.value || window.currentMonth || (new Date().getMonth()+1), 10);
        const year = window.currentYear || new Date().getFullYear();
        const topLimit = parseInt(document.getElementById('topLimit')?.value || 10, 10);
        const kemantren = document.getElementById('kemantrenFilter')?.value || '';
        const search = document.getElementById('locationSearch')?.value || '';
        
        // Clear cached key to force refresh
        window._monthlyFetchedKey = null;
        
        fetchMonthlyData(month, year, 500, kemantren, search, topLimit);
    }
}

/**
 * Start auto-update interval (5 minutes)
 */
function startAutoUpdate() {
    // Stop existing interval if any
    stopAutoUpdate();
    
    // Initial update
    triggerRealtimeUpdate();
    
    // Set interval untuk 5 menit (300000 ms)
    autoUpdateInterval = setInterval(() => {
        triggerRealtimeUpdate();
    }, 300000); // 5 minutes
    
    console.log('⏰ Auto-update started (every 5 minutes)');
}

/**
 * Stop auto-update interval
 */
function stopAutoUpdate() {
    if (autoUpdateInterval) {
        clearInterval(autoUpdateInterval);
        autoUpdateInterval = null;
        console.log('⏸️ Auto-update stopped');
    }
}

/**
 * Update last update time display
 */
function updateLastUpdateDisplay(customText) {
    const display = document.getElementById('lastUpdateTime');
    if (!display) return;
    
    if (customText) {
        display.textContent = customText;
        return;
    }
    
    if (lastUpdateTime) {
        const formatted = lastUpdateTime.toLocaleTimeString('id-ID', {
            hour: '2-digit',
            minute: '2-digit',
            second: '2-digit'
        });
        display.textContent = `Terakhir update: ${formatted}`;
    } else {
        display.textContent = 'Belum ada update';
    }
}

/**
 * Show loading indicator
 */
function showLoadingIndicator() {
    const indicator = document.getElementById('chartLoadingIndicator');
    if (indicator) {
        indicator.classList.remove('hidden');
    }
}

/**
 * Hide loading indicator
 */
function hideLoadingIndicator() {
    const indicator = document.getElementById('chartLoadingIndicator');
    if (indicator) {
        indicator.classList.add('hidden');
    }
}

/**
 * Show notification
 */
function showNotification(message, type = 'info') {
    const notification = document.getElementById('chartNotification');
    if (!notification) {
        console.log(`[${type.toUpperCase()}] ${message}`);
        return;
    }
    
    const bgColors = {
        success: 'bg-green-500',
        error: 'bg-red-500',
        warning: 'bg-yellow-500',
        info: 'bg-blue-500'
    };
    
    notification.textContent = message;
    notification.className = `px-4 py-2 rounded ${bgColors[type] || bgColors.info} text-white text-sm`;
    notification.classList.remove('hidden');
    
    setTimeout(() => {
        notification.classList.add('hidden');
    }, 3000);
}

/**
 * ============================================
 * MODAL FUNCTIONS
 * ============================================
 */

function showOthersModal(list) {
    const modal = document.getElementById('othersModal');
    const container = document.getElementById('othersList');
    if (!modal || !container) return;
    
    container.innerHTML = '';
    if (!Array.isArray(list) || list.length === 0) {
        container.innerHTML = '<div class="text-gray-400">Tidak ada data</div>';
    } else {
        list.forEach(item => {
            const el = document.createElement('div');
            el.className = 'p-2 bg-slate-700/50 rounded';
            el.innerHTML = `
                <div class="flex justify-between">
                    <div class="font-medium">${item.location || 'Unknown'}</div>
                    <div class="text-sm text-gray-300">${item.total || 0}</div>
                </div>
                <div class="text-xs text-gray-400">${item.kemantren || ''}</div>
            `;
            container.appendChild(el);
        });
    }
    modal.classList.remove('hidden');
    modal.classList.add('flex');
}

function hideOthersModal() {
    const modal = document.getElementById('othersModal');
    if (modal) {
        modal.classList.add('hidden');
        modal.classList.remove('flex');
    }
}

/**
 * ============================================
 * INITIALIZATION
 * ============================================
 */

document.addEventListener("DOMContentLoaded", function () {
    console.log('📊 Location Chart initializing...');
    
    // Initial chart render
    if (
        (window.weeklyLocationByDay && Object.keys(window.weeklyLocationByDay).length > 0) ||
        (window.monthlyLocationData && window.monthlyLocationData.length > 0)
    ) {
        updateLocationChart();
        
        // Setup event listeners
        const periodSelect = document.getElementById("chartPeriod");
        const monthSelect = document.getElementById("monthFilter");
        const searchInput = document.getElementById("locationSearch");
        const kemantrenSelect = document.getElementById("kemantrenFilter");
        const topLimitSelect = document.getElementById("topLimit");

        if (periodSelect) {
            periodSelect.addEventListener("change", function() {
                updateLocationChart();
                // Refresh saat ganti period
                triggerRealtimeUpdate();
            });
        }
        
        if (monthSelect) {
            monthSelect.addEventListener("change", function () {
                const month = parseInt(this.value, 10) || window.currentMonth || new Date().getMonth() + 1;
                const year = window.currentYear || new Date().getFullYear();
                window._monthlyFetchedKey = null; // Clear cache
                fetchMonthlyData(month, year, 500);
            });
        }
        
        if (searchInput) {
            searchInput.addEventListener("input", filterLocationChart);
        }
        
        if (kemantrenSelect) {
            kemantrenSelect.addEventListener("change", filterLocationChart);
        }
        
        if (topLimitSelect) {
            topLimitSelect.addEventListener("change", updateLocationChart);
        }
        
        // Manual refresh button
        const refreshBtn = document.getElementById('manualRefreshBtn');
        if (refreshBtn) {
            refreshBtn.addEventListener('click', () => {
                triggerRealtimeUpdate();
            });
        }
        
        // Start auto-update
        startAutoUpdate();
        
        console.log('✅ Location Chart initialized with auto-update');
    } else {
        console.warn("Location data is empty or not available");
    }
    
    // Refresh saat tab visible kembali
    document.addEventListener('visibilitychange', function() {
        if (!document.hidden) {
            console.log('👁️ Tab visible, triggering update...');
            triggerRealtimeUpdate();
        }
    });
});

// Cleanup on page unload
window.addEventListener('beforeunload', function() {
    stopAutoUpdate();
});