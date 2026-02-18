<x-app-layout>

    <div
        class="space-y-6 bg-gradient-to-br from-slate-50 via-blue-50/30 to-cyan-50/40 dark:from-slate-900 dark:via-slate-800 dark:to-slate-900 min-h-screen p-6">
        <!-- Loading overlay -->
        <div id="pageLoader"
            class="fixed inset-0 bg-white/50 dark:bg-slate-900/50 backdrop-blur-md z-50 flex items-center justify-center">
            <div class="text-slate-800 dark:text-white text-center">
                <div
                    class="animate-spin rounded-full h-12 w-12 border-b-2 border-cyan-600 dark:border-cyan-400 mx-auto mb-3">
                </div>
                <p class="font-medium">Memuat Dashboard...</p>
            </div>
        </div>

        <h1 class="text-slate-900 dark:text-white text-3xl font-bold mb-4">
            Dashboard Overview
        </h1>

        <!-- Summary Cards -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-2 gap-6">
            <div
                class="group relative overflow-hidden bg-gradient-to-br from-cyan-500 to-blue-600 rounded-xl p-6 shadow-lg hover:shadow-2xl hover:shadow-cyan-500/30 transition-all duration-300 hover:scale-105">
                <div
                    class="absolute inset-0 bg-gradient-to-r from-white/0 to-white/0 group-hover:from-white/10 group-hover:to-white/20 transition-all duration-300">
                </div>
                <div class="relative">
                    <p class="text-cyan-100 text-sm font-semibold mb-2 uppercase tracking-wider">Pengguna Terhubung</p>
                    <p id="userOnlineCount" class="text-white font-bold text-4xl drop-shadow-lg">{{ $userOnline }}</p>
                </div>
            </div>

            <div
                class="group relative overflow-hidden bg-gradient-to-br from-emerald-500 to-teal-600 rounded-xl p-6 shadow-lg hover:shadow-2xl hover:shadow-emerald-500/30 transition-all duration-300 hover:scale-105">
                <div
                    class="absolute inset-0 bg-gradient-to-r from-white/0 to-white/0 group-hover:from-white/10 group-hover:to-white/20 transition-all duration-300">
                </div>
                <div class="relative">
                    <p class="text-emerald-100 text-sm font-semibold mb-2 uppercase tracking-wider">Total ONT</p>
                    <p class="text-white font-bold text-4xl drop-shadow-lg">{{ $totalAp }}</p>
                </div>
            </div>
        </div>

        <!-- Charts Section -->
        <div class="grid gap-4 lg:grid-cols-1 rounded-xl">
            <!-- Chart Rekap Total User -->
            <div
                class="bg-white dark:bg-slate-800 rounded-xl p-4 border-2 border-slate-200 dark:border-slate-700 flex flex-col shadow-lg shadow-slate-200/50 dark:shadow-black/20">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="text-slate-900 dark:text-white font-semibold">Rekap Total User</h3>
                    <div id="userChartDailyControls" class="flex items-center gap-2 pointer-events-auto relative z-10">
                        <!-- Mode -->
                        <select id="userChartMode"
                            class="bg-white dark:bg-slate-700 border-2 border-slate-300 dark:border-slate-600 text-slate-800 dark:text-slate-200 text-sm rounded-lg px-3 py-1.5 cursor-pointer focus:ring-2 focus:ring-cyan-500 focus:border-cyan-500">
                            <option value="weekly">Mingguan</option>
                            <option value="daily">Harian</option>
                            <option value="monthly">Bulanan</option>
                        </select>
        
        <!-- REKAP TOTAL USER - Tombol Export -->
        <div class="flex items-center gap-2">
            {{-- TAMBAHAN: Dropdown Export --}}
            <div class="relative" x-data="{ open: false }">
                <button @click="open = !open"
                        class="px-3 py-1.5 rounded text-sm font-medium bg-green-700 hover:bg-green-600 text-white flex items-center gap-1">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M4 16v2a2 2 0 002 2h12a2 2 0 002-2v-2M7 10l5 5 5-5M12 15V3"/>
                    </svg>
                    Export
                </button>
                <div x-show="open" @click.outside="open = false"
                    class="absolute right-0 mt-1 w-52 rounded shadow-lg bg-gray-800 border border-gray-600 z-50">
                    <a href="{{ route('dashboard.export', ['type' => 'weekly_user']) }}"
                    class="block px-4 py-2 text-sm text-gray-200 hover:bg-gray-700">
                        📊 User Mingguan (.xlsx)
                    </a>
                    <a id="export-monthly-user-link"
                    href="{{ route('dashboard.export', ['type' => 'monthly_user', 'month' => now()->month, 'year' => now()->year]) }}"
                    class="block px-4 py-2 text-sm text-gray-200 hover:bg-gray-700">
                        📅 User Bulanan (.xlsx)
                    </a>
                </div>
            </div>
        </div>

                        <!-- Tanggal untuk Harian -->
                        <input type="date" id="dailyDateFilter"
                            class="hidden bg-white dark:bg-slate-700 border-2 border-slate-300 dark:border-slate-600 text-slate-800 dark:text-slate-200 text-sm rounded-lg px-3 py-1.5 cursor-pointer focus:ring-2 focus:ring-cyan-500 focus:border-cyan-500">

                        <!-- Tahun -->
                        <select id="userChartYearFilter"
                            class="hidden bg-white dark:bg-slate-700 border-2 border-slate-300 dark:border-slate-600 text-slate-800 dark:text-slate-200 text-sm rounded-lg px-3 py-1.5 cursor-pointer focus:ring-2 focus:ring-cyan-500 focus:border-cyan-500">
                        </select>

                        <!-- Bulan -->
                        <select id="userChartMonthFilter"
                            class="hidden bg-white dark:bg-slate-700 border-2 border-slate-300 dark:border-slate-600 text-slate-800 dark:text-slate-200 text-sm rounded-lg px-3 py-1.5 cursor-pointer focus:ring-2 focus:ring-cyan-500 focus:border-cyan-500">
                        </select>
                    </div>
                </div>
                <div class="grid grid-cols-1 lg:grid-cols-4 gap-4">
                    <!-- Stats Sidebar -->
                    <div class="lg:col-span-1 space-y-4">
                        <div
                            class="bg-gradient-to-br from-cyan-50 to-blue-50 dark:bg-gradient-to-br dark:from-slate-700 dark:to-slate-700/90 rounded-lg p-4 border-2 border-cyan-200 dark:border-cyan-900/50 shadow-sm">
                            <div class="text-sm text-slate-600 dark:text-slate-400 mb-1 font-medium">Maximum</div>
                            <div class="text-2xl font-bold text-cyan-600 dark:text-cyan-400" id="statMaxUsers">0</div>
                        </div>
                        <div
                            class="bg-gradient-to-br from-blue-50 to-indigo-50 dark:bg-gradient-to-br dark:from-slate-700 dark:to-slate-700/90 rounded-lg p-4 border-2 border-blue-200 dark:border-blue-900/50 shadow-sm">
                            <div class="text-sm text-slate-600 dark:text-slate-400 mb-1 font-medium">Minimum</div>
                            <div class="text-2xl font-bold text-blue-600 dark:text-blue-400" id="statMinUsers">0</div>
                        </div>
                        <div
                            class="bg-gradient-to-br from-indigo-50 to-purple-50 dark:bg-gradient-to-br dark:from-slate-700 dark:to-slate-700/90 rounded-lg p-4 border-2 border-indigo-200 dark:border-indigo-900/50 shadow-sm">
                            <div class="text-sm text-slate-600 dark:text-slate-400 mb-1 font-medium">Average</div>
                            <div class="text-2xl font-bold text-indigo-600 dark:text-indigo-400" id="statAvgUsers">0</div>
                        </div>
                    </div>

                    <!-- Chart -->
                    <div class="lg:col-span-3 h-80 relative">
                        <canvas id="userChartDaily" class="h-full block"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <!-- Rekap Mingguan & Bulanan Per Lokasi -->
        <div
            class="bg-white dark:bg-slate-800 rounded-xl p-6 shadow-lg border-2 border-slate-200 dark:border-slate-700">

            <div class="grid grid-cols-1 lg:grid-cols-4 gap-6">

                <!-- Kiri: Search & Filter -->
                <div class="lg:col-span-1 space-y-4">
                    <h3 class="text-slate-900 dark:text-white font-semibold text-lg">Filter</h3>

                    <div>
                        <label class="text-slate-600 dark:text-slate-300 text-sm block mb-2 font-medium">Cari Lokasi</label>
                        <input type="text" id="locationSearch" placeholder="Cari lokasi..."
                            class="w-full px-3 py-2 rounded bg-white dark:bg-slate-700 text-slate-900 dark:text-white text-sm border-2 border-slate-300 dark:border-slate-600 focus:ring-2 focus:ring-cyan-500 focus:border-cyan-500">
                    </div>

                    <div>
                        <label class="text-slate-600 dark:text-slate-300 text-sm block mb-2 font-medium">Kemantren</label>
                        <select id="kemantrenFilter"
                            class="w-full px-3 py-2 rounded bg-white dark:bg-slate-700 text-slate-900 dark:text-white text-sm border-2 border-slate-300 dark:border-slate-600 focus:ring-2 focus:ring-cyan-500 focus:border-cyan-500">
                            <option value="">Semua</option>
                            @foreach ($kemantrenList as $km)
                                <option value="{{ $km }}">{{ $km }}</option>
                            @endforeach
                        </select>
                    </div>

                    <button onclick="filterLocationChart()"
                        class="w-full px-4 py-2 bg-gradient-to-r from-cyan-500 to-blue-600 hover:from-cyan-600 hover:to-blue-700 rounded-lg text-white text-sm font-semibold shadow-md hover:shadow-lg transition-all duration-200">
                        Filter
                    </button>

                    <!-- TAMBAHAN BARU: Status & Controls -->
                    <div class="border-t-2 border-slate-200 dark:border-slate-600 pt-4 mt-4 space-y-3">
                        <div class="flex items-center justify-between">
                            <span class="text-slate-600 dark:text-slate-400 text-xs font-medium">Status Update</span>
                            <div id="chartLoadingIndicator" class="hidden">
                                <div class="animate-spin rounded-full h-4 w-4 border-b-2 border-cyan-500"></div>
                            </div>
                        </div>

                        <div id="lastUpdateTime" class="text-slate-600 dark:text-slate-400 text-xs">
                            Belum ada update
                        </div>

                        <button id="manualRefreshBtn"
                            class="w-full px-3 py-2 bg-slate-200 dark:bg-slate-600 hover:bg-slate-300 dark:hover:bg-slate-500 rounded-lg text-slate-800 dark:text-white text-sm font-semibold flex items-center justify-center gap-2 border-2 border-slate-300 dark:border-slate-500 transition-all duration-200"
                            title="Refresh manual">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15">
                                </path>
                            </svg>
                            <span>Refresh Sekarang</span>
                        </button>
                    </div>
                </div>

                <!-- Kanan: Chart & Kontrol -->
                <div class="lg:col-span-3 space-y-4">
                    <div class="flex justify-between items-center">
                        <h3 class="text-slate-900 dark:text-white font-semibold text-lg">Rekap Per Lokasi</h3>
                        <div class="flex gap-2">
                            <select id="chartPeriod" onchange="updateLocationChart()"
                                class="px-3 py-1.5 bg-white dark:bg-slate-700 border-2 border-slate-300 dark:border-slate-600 text-slate-800 dark:text-slate-200 text-sm rounded-lg focus:ring-2 focus:ring-cyan-500 focus:border-cyan-500">
                                <option value="weekly">Mingguan</option>
                                <option value="monthly">Bulanan</option>
                            </select>
                            <select id="monthFilter" onchange="updateLocationChart()"
                                class="px-3 py-1.5 bg-white dark:bg-slate-700 border-2 border-slate-300 dark:border-slate-600 text-slate-800 dark:text-slate-200 text-sm rounded-lg focus:ring-2 focus:ring-cyan-500 focus:border-cyan-500 hidden">
                                @foreach ($months as $num => $name)
                                    <option value="{{ $num }}" {{ $num == $currentMonth ? 'selected' : '' }}>
                                        {{ $name }}
                                    </option>
                                @endforeach
                            </select>
                            <select id="topLimit" onchange="updateLocationChart()"
                                class="px-3 py-1.5 bg-white dark:bg-slate-700 border-2 border-slate-300 dark:border-slate-600 text-slate-800 dark:text-slate-200 text-sm rounded-lg focus:ring-2 focus:ring-cyan-500 focus:border-cyan-500">
                                <option value="5">Top 5</option>
                                <option value="8" selected>Top 8</option>
                                <option value="10">Top 10</option>
                                <option value="15">Top 15</option>
                            </select>
                <!-- REKAP PER LOKASI - Tombol Export -->
                <div class="relative" x-data="{ open: false }">
                    <button @click="open = !open"
                            class="px-3 py-1.5 rounded text-sm font-medium bg-green-700 hover:bg-green-600 text-white flex items-center gap-1">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M4 16v2a2 2 0 002 2h12a2 2 0 002-2v-2M7 10l5 5 5-5M12 15V3"/>
                        </svg>
                        Export
                    </button>
                    <div x-show="open" @click.outside="open = false"
                        class="absolute right-0 mt-1 w-56 rounded shadow-lg bg-gray-800 border border-gray-600 z-50">
                        <a href="{{ route('dashboard.export', ['type' => 'weekly_location']) }}"
                        class="block px-4 py-2 text-sm text-gray-200 hover:bg-gray-700">
                            📊 Lokasi Mingguan (.xlsx)
                        </a>
                        <a id="export-monthly-location-link"
                        href="{{ route('dashboard.export', ['type' => 'monthly_location', 'month' => now()->month, 'year' => now()->year]) }}"
                        class="block px-4 py-2 text-sm text-gray-200 hover:bg-gray-700">
                            📅 Lokasi Bulanan (.xlsx)
                        </a>
                    </div>
                </div>
                        </div>
                    </div>

                    <!-- TAMBAHAN BARU: Notification Area -->
                    <div id="chartNotification" class="hidden px-4 py-2 rounded-lg text-white text-sm font-medium"></div>

                    <div
                        class="bg-gradient-to-br from-slate-50 to-blue-50/30 dark:bg-gradient-to-br dark:from-slate-700 dark:to-slate-700/90 rounded-lg p-4 border-2 border-slate-200 dark:border-slate-600 shadow-sm">
                        <canvas id="locationChart" class="w-full" style="max-height: 300px;"></canvas>
                    </div>

                    <!-- Others modal -->
                    <div id="othersModal" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/50">
                        <div class="bg-white dark:bg-slate-800 rounded-lg w-11/12 md:w-2/3 lg:w-1/2 p-4">
                            <div class="flex justify-between items-center mb-3">
                                <h4 class="text-gray-900 dark:text-white font-semibold">Detail: Lainnya</h4>
                                <button onclick="hideOthersModal()"
                                    class="text-gray-600 dark:text-gray-300">Tutup</button>
                            </div>
                            <div id="othersList"
                                class="text-sm text-gray-700 dark:text-gray-200 max-h-72 overflow-auto space-y-2">
                                <!-- populated by JS -->
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </div>
        <!-- Rekap User Online Per Lokasi -->
        <div
            class="bg-white dark:bg-slate-800 rounded-xl p-6 shadow-lg border-2 border-slate-200 dark:border-slate-700">

            <h3 class="text-slate-900 dark:text-white font-semibold text-lg mb-6">Rekap User Online Per Lokasi</h3>

            <div class="space-y-4">

                @forelse ($locations as $locationData)
                    <div class="grid grid-cols-10 gap-4">

                        <!-- Box 30% - Lokasi -->
                        <div
                            class="col-span-3 bg-gradient-to-br from-slate-50 to-blue-50/40 dark:bg-gradient-to-br dark:from-slate-700 dark:to-slate-700/80 p-4 rounded-lg text-slate-900 dark:text-white space-y-2 border-2 border-slate-200 dark:border-slate-600 shadow-sm">
                            <p class="font-semibold text-lg text-cyan-600 dark:text-cyan-400">
                                {{ $locationData['location'] }}
                            </p>
                            <div class="text-xs space-y-1 text-slate-600 dark:text-slate-300">
                                <p><span class="text-slate-500 dark:text-slate-400 font-medium">Kemantren:</span>
                                    {{ $locationData['kemantren'] }}</p>
                                <p><span class="text-slate-500 dark:text-slate-400 font-medium">Kelurahan:</span>
                                    {{ $locationData['kelurahan'] }}</p>
                                <p><span class="text-slate-500 dark:text-slate-400 font-medium">RT/RW:</span> {{ $locationData['rt'] }} /
                                    {{ $locationData['rw'] }}
                                </p>
                                <p><span class="text-slate-500 dark:text-slate-400 font-medium">SN:</span> {{ $locationData['sn'] }}</p>
                            </div>
                            <div class="pt-2 border-t-2 border-slate-200 dark:border-slate-600">
                                <p class="text-sm text-slate-600 dark:text-slate-400 font-medium">User Terhubung</p>
                                <p class="text-2xl font-bold text-emerald-500 dark:text-emerald-400">{{ $locationData['count'] }}</p>
                            </div>
                        </div>

                        <!-- Box 70% - Detail Pengguna (tampilkan maksimal 5) -->
                        <div
                            class="col-span-7 bg-white dark:bg-slate-700 p-4 rounded-lg text-slate-900 dark:text-white border-2 border-slate-200 dark:border-slate-600 overflow-x-auto shadow-sm">

                            <table class="w-full text-left text-slate-600 dark:text-slate-300 text-sm table-auto min-w-full">
                                <thead class="border-b-2 border-slate-300 dark:border-slate-600">
                                    <tr>
                                        <th class="pb-3 font-semibold text-slate-700 dark:text-slate-200 w-2/6">Nama Perangkat
                                        </th>
                                        <th class="pb-3 font-semibold text-slate-700 dark:text-slate-200 w-2/6">Alamat IP</th>
                                        <th class="pb-3 font-semibold text-slate-700 dark:text-slate-200 w-2/6">Alamat MAC
                                        </th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach (array_slice($locationData['clients'], 0, 5) as $client)
                                        <tr
                                            class="border-b border-slate-200 dark:border-slate-600/30 hover:bg-cyan-50 dark:hover:bg-slate-600/20 transition-colors duration-200">
                                            <td class="py-3">{{ $client['wifi_terminal_name'] ?? 'Unknown' }}</td>
                                            <td class="py-3">{{ $client['wifi_terminal_ip'] ?? '-' }}</td>
                                            <td class="py-3 text-xs">{{ $client['wifi_terminal_mac'] ?? '-' }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>

                            <div class="mt-3 flex justify-end">
                                <button onclick="fetchLocationClients('{{ $locationData['sn'] }}')"
                                    class="px-3 py-1.5 bg-cyan-100 dark:bg-slate-600 hover:bg-cyan-200 dark:hover:bg-slate-500 rounded-lg text-sm text-cyan-700 dark:text-white font-medium border border-cyan-300 dark:border-slate-500 transition-all duration-200">Lihat
                                    Semua</button>
                            </div>

                        </div>

                    </div>
                @empty
                    <div class="py-8 text-center text-gray-500 dark:text-gray-400">
                        Tidak Ada Perangkat Terhubung
                    </div>
                @endforelse

            </div>

            <!-- Footer Pagination (lokasi) -->
            @if ($locations->hasPages())
                <div
                    class="flex flex-col sm:flex-row items-center justify-between border-t border-gray-200 dark:border-slate-700 pt-6 gap-4">

                    <div class="text-sm text-gray-500 dark:text-gray-400">
                        Menampilkan
                        <span class="font-medium">{{ $locations->firstItem() }}</span>
                        Ke
                        <span class="font-medium">{{ $locations->lastItem() }}</span>
                        Dari
                        <span class="font-medium">{{ $locations->total() }}</span>
                        Lokasi
                    </div>

                    <div>
                        {{ $locations->onEachSide(2)->links('pagination::tailwind') }}
                    </div>

                </div>
            @endif
        </div>

    </div>

    <style>
        /* Light mode scrollbar */
        ::-webkit-scrollbar {
            width: 8px;
            height: 8px;
        }

        ::-webkit-scrollbar-track {
            background: #e2e8f0;
        }

        ::-webkit-scrollbar-thumb {
            background: #94a3b8;
            border-radius: 4px;
        }

        ::-webkit-scrollbar-thumb:hover {
            background: #64748b;
        }

        /* Dark mode scrollbar */
        @media (prefers-color-scheme: dark) {
            ::-webkit-scrollbar-track {
                background: #1e293b;
            }

            ::-webkit-scrollbar-thumb {
                background: #475569;
            }

            ::-webkit-scrollbar-thumb:hover {
                background: #64748b;
            }
        }
    </style>

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <script>
        // Global variables
        window.dailyUsersLabels = @json($dailyUsers['labels'] ?? []);
        window.dailyUsersRawLabels = @json($dailyUsers['raw_labels'] ?? []);
        window.dailyUsersData = @json($dailyUsers['data'] ?? []);
        window.dailyUsersChart = null;
        window.userCapacityChart = null;
        window.currentMonth = @json($currentMonth ?? 1);
        window.currentYear = @json($currentYear ?? new Date() . getFullYear());
        window.dayLabels = @json($dayLabels ?? []);
        window.weeklyLocationByDay = @json($weeklyLocationByDay ?? []);
        window.monthlyLocationData = [];
        window.isLoadingChart = false;

        // URL parameter helper
        function updateUrlParam(key, value) {
            const url = new URL(window.location.href);
            url.searchParams.set(key, value);
            url.searchParams.set('page', 1);
            return url.toString();
        }

        // Preserve scroll position on pagination
        document.addEventListener('DOMContentLoaded', function () {
            const paginationLinks = document.querySelectorAll('a[href*="page="]');
            paginationLinks.forEach(link => {
                link.addEventListener('click', function (e) {
                    sessionStorage.setItem('scrollPosition', window.scrollY);
                });
            });

            const savedPosition = sessionStorage.getItem('scrollPosition');
            if (savedPosition) {
                window.scrollTo(0, parseInt(savedPosition));
                sessionStorage.removeItem('scrollPosition');
            }
        });

        // Initialize year and month filters
        function initializeFilters() {
            const yearSelect = document.getElementById('userChartYearFilter');
            const monthSelect = document.getElementById('userChartMonthFilter');

            if (yearSelect && yearSelect.children.length === 0) {
                for (let y = window.currentYear; y >= window.currentYear - 2; y--) {
                    const opt = document.createElement('option');
                    opt.value = y;
                    opt.textContent = y;
                    if (y === window.currentYear) opt.selected = true;
                    yearSelect.appendChild(opt);
                }
            }

            if (monthSelect && monthSelect.children.length === 0) {
                const months = [
                    'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
                    'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'
                ];
                months.forEach((m, i) => {
                    const opt = document.createElement('option');
                    opt.value = i + 1;
                    opt.textContent = m;
                    if ((i + 1) === window.currentMonth) opt.selected = true;
                    monthSelect.appendChild(opt);
                });
            }
        }

        // User Capacity Chart
        // Load Daily Users Chart
        async function loadDailyUsers(period = 'weekly') {
            if (window.isLoadingChart) {
                console.log('Chart is loading, skipping...');
                return;
            }

            const el = document.getElementById('userChartDaily');
            if (!el) {
                console.error('Canvas element not found');
                return;
            }

            window.isLoadingChart = true;
            console.log('Loading chart for period:', period);

            try {
                // Destroy existing chart FIRST
                if (window.dailyUsersChart) {
                    console.log('Destroying existing chart');
                    try {
                        window.dailyUsersChart.destroy();
                    } catch (e) {
                        console.warn('Error destroying chart:', e);
                    }
                    window.dailyUsersChart = null;
                }

                // Get canvas and clear it
                const canvas = document.getElementById('userChartDaily');
                if (!canvas) {
                    console.error('Canvas element not found');
                    return;
                }

                // Force canvas reset
                const parent = canvas.parentNode;
                const newCanvas = canvas.cloneNode(true);
                parent.replaceChild(newCanvas, canvas);

                // Wait for DOM update
                await new Promise(resolve => setTimeout(resolve, 100));

                let labels = [];
                let rawLabels = [];
                let dataNums = [];

                if (period === 'weekly') {
                    labels = Array.isArray(window.dailyUsersLabels) ? [...window.dailyUsersLabels] : [];
                    rawLabels = Array.isArray(window.dailyUsersRawLabels) ? [...window.dailyUsersRawLabels] : [];
                    dataNums = Array.isArray(window.dailyUsersData) ? window.dailyUsersData.map(v => Number(v || 0)) : [];
                    console.log('Weekly data loaded:', { labels, rawLabels, dataNums });
                } else if (period === 'daily') {
                    const dateEl = document.getElementById('dailyDateFilter');
                    let selectedDate = dateEl ? dateEl.value : new Date().toISOString().split('T')[0];

                    // Ensure we have a valid date string (YYYY-MM-DD format)
                    if (!selectedDate || selectedDate === '') {
                        selectedDate = new Date().toISOString().split('T')[0];
                    }

                    console.log('Fetching daily data for date:', selectedDate);

                    const res = await fetch(`/dashboard/daily-user-data-by-hour?date=${encodeURIComponent(selectedDate)}`, {
                        credentials: 'same-origin',
                        cache: 'no-store',
                        signal: AbortSignal.timeout(10000)
                    });

                    if (!res.ok) {
                        console.error('Fetch failed with status:', res.status);
                        throw new Error('Failed to fetch daily data');
                    }

                    const json = await res.json();
                    console.log('Daily data received:', json);

                    labels = Array.isArray(json.labels) ? [...json.labels] : [];
                    rawLabels = labels;
                    dataNums = Array.isArray(json.data) ? json.data.map(v => Number(v || 0)) : [];
                    console.log('Daily data processed:', { selectedDate, labels, dataNums, dataSum: dataNums.reduce((a, b) => a + b, 0) });
                } else {
                    const monthEl = document.getElementById('userChartMonthFilter');
                    const yearEl = document.getElementById('userChartYearFilter');
                    const month = monthEl ? monthEl.value : window.currentMonth || 1;
                    const year = yearEl ? yearEl.value : window.currentYear || new Date().getFullYear();

                    console.log('Fetching monthly data for:', { month, year });

                    const res = await fetch(`/dashboard/monthly-user-data?month=${encodeURIComponent(month)}&year=${encodeURIComponent(year)}`, {
                        credentials: 'same-origin',
                        cache: 'no-store',
                        signal: AbortSignal.timeout(10000)
                    });

                    if (!res.ok) {
                        console.error('Fetch failed with status:', res.status);
                        throw new Error('Failed to fetch monthly data');
                    }

                    const json = await res.json();
                    console.log('Monthly data received:', json);

                    rawLabels = Array.isArray(json.labels) ? [...json.labels] : [];
                    labels = rawLabels.map(d => {
                        try {
                            return (new Date(d)).toLocaleDateString('id-ID');
                        } catch (e) {
                            return d;
                        }
                    });
                    dataNums = Array.isArray(json.data) ? json.data.map(v => Number(v || 0)) : [];
                    console.log('Monthly data processed:', { labels, rawLabels, dataNums });
                }

                if (!rawLabels.length || !dataNums.length) {
                    console.warn('No data available, using placeholder');
                    labels = ['Tidak ada data'];
                    rawLabels = ['-'];
                    dataNums = [0];
                }

                // Get the NEW canvas element after replacement
                const finalCanvas = document.getElementById('userChartDaily');
                if (!finalCanvas) {
                    console.error('Canvas element not found after replacement');
                    return;
                }

                const ctx = finalCanvas.getContext('2d');

                // Recreate gradient
                const gradient = ctx.createLinearGradient(0, 0, 0, 200);
                gradient.addColorStop(0, 'rgba(59,130,246,0.9)');
                gradient.addColorStop(1, 'rgba(59,130,246,0.2)');

                console.log('Creating new chart with data:', { labels: labels, data: dataNums });

                window.dailyUsersChart = new Chart(ctx, {
                    type: 'line',
                    data: {
                        labels: labels,
                        datasets: [{
                            label: 'Users',
                            data: dataNums,
                            fill: true,
                            backgroundColor: gradient,
                            borderColor: '#60a5fa',
                            tension: 0.35,
                            pointRadius: 3,
                            borderWidth: 2,
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        animation: false,
                        events: ['click'],
                        scales: {
                            y: {
                                beginAtZero: true,
                                ticks: {
                                    stepSize: 1,
                                    precision: 0,
                                    color: '#cbd5f5'
                                },
                                grid: {
                                    color: 'rgba(148,163,184,0.08)'
                                }
                            },
                            x: {
                                ticks: { color: '#94a3b8' },
                                grid: { display: false }
                            }
                        },
                        plugins: {
                            legend: { display: false },
                            tooltip: {
                                enabled: false
                            }
                        }
                    }
                });

                // Store raw labels for reference
                window.dailyUsersChart._rawLabels = rawLabels;
                window.dailyUsersChart._period = period;
                console.log('Chart created successfully for period:', period);

                // Calculate and display statistics
                updateChartStatistics(dataNums);

            } catch (error) {
                console.error('Error loading daily users chart:', error);
                alert('Gagal memuat data: ' + error.message);
            } finally {
                window.isLoadingChart = false;
            }
        }

        // Function to calculate and display chart statistics
        function updateChartStatistics(data) {
            // Filter out zeros and non-numeric values
            const validData = data.filter(v => typeof v === 'number' && v > 0);

            if (validData.length === 0) {
                // No data available
                document.getElementById('statMaxUsers').textContent = '0';
                document.getElementById('statMinUsers').textContent = '0';
                document.getElementById('statAvgUsers').textContent = '0';
                return;
            }

            // Calculate statistics
            const maxValue = Math.max(...validData);
            const minValue = Math.min(...validData);
            const avgValue = (validData.reduce((a, b) => a + b, 0) / validData.length).toFixed(1);

            // Update UI
            document.getElementById('statMaxUsers').textContent = maxValue;
            document.getElementById('statMinUsers').textContent = minValue;
            document.getElementById('statAvgUsers').textContent = avgValue;

            console.log('Chart statistics updated:', {
                max: maxValue,
                min: minValue,
                avg: avgValue,
                dataPoints: validData.length
            });
        }

        // Initialize all charts and event listeners
        document.addEventListener('DOMContentLoaded', function () {
            console.log('DOM Loaded - Initializing charts');

            initializeFilters();

            // Initialize date picker with today's date
            const dateSelect = document.getElementById('dailyDateFilter');
            if (dateSelect && !dateSelect.value) {
                const today = new Date();
                const year = today.getFullYear();
                const month = String(today.getMonth() + 1).padStart(2, '0');
                const day = String(today.getDate()).padStart(2, '0');
                dateSelect.value = `${year}-${month}-${day}`;
                console.log('Date picker initialized to:', dateSelect.value);
            }

            // Load chart according to current selected mode (weekly/daily/monthly)
            const initialMode = document.getElementById('userChartMode')?.value || 'weekly';
            // If daily and date picker exists, ensure the date is set
            if (initialMode === 'daily') {
                const ds = document.getElementById('dailyDateFilter');
                if (ds && !ds.value) {
                    const today = new Date();
                    ds.value = `${today.getFullYear()}-${String(today.getMonth() + 1).padStart(2, '0')}-${String(today.getDate()).padStart(2, '0')}`;
                }
            }
            loadDailyUsers(initialMode);

            // Setup event listeners with delay
            setTimeout(function () {
                const modeSelect = document.getElementById('userChartMode');
                const monthSelect = document.getElementById('userChartMonthFilter');
                const yearSelect = document.getElementById('userChartYearFilter');
                const dateSelect = document.getElementById('dailyDateFilter');

                console.log('Setting up event listeners', { modeSelect, monthSelect, yearSelect, dateSelect });

                if (modeSelect) {
                    modeSelect.addEventListener('change', async function (e) {
                        console.log('Mode changed to:', e.target.value);
                        const isMonthly = e.target.value === 'monthly';
                        const isDaily = e.target.value === 'daily';

                        if (monthSelect) monthSelect.classList.toggle('hidden', !isMonthly);
                        if (yearSelect) yearSelect.classList.toggle('hidden', !isMonthly);
                        if (dateSelect) {
                            dateSelect.classList.toggle('hidden', !isDaily);
                            // Set default date to today
                            if (isDaily && !dateSelect.value) {
                                dateSelect.valueAsDate = new Date();
                            }
                        }

                        // Reset loading flag
                        window.isLoadingChart = false;

                        // Wait a bit before loading
                        await new Promise(resolve => setTimeout(resolve, 150));
                        await loadDailyUsers(e.target.value);
                    });
                    console.log('Mode select listener attached');
                } else {
                    console.error('userChartMode element not found!');
                }

                if (dateSelect) {
                    dateSelect.addEventListener('change', async function () {
                        console.log('Date changed to:', this.value);
                        if (modeSelect && modeSelect.value === 'daily') {
                            window.isLoadingChart = false;
                            await new Promise(resolve => setTimeout(resolve, 150));
                            await loadDailyUsers('daily');
                        }
                    });
                    console.log('Date select listener attached');
                }

                if (monthSelect) {
                    monthSelect.addEventListener('change', async function () {
                        console.log('Month changed to:', this.value);
                        if (modeSelect && modeSelect.value === 'monthly') {
                            window.isLoadingChart = false;
                            await new Promise(resolve => setTimeout(resolve, 150));
                            await loadDailyUsers('monthly');
                        }
                    });
                    console.log('Month select listener attached');
                }

                if (yearSelect) {
                    yearSelect.addEventListener('change', async function () {
                        console.log('Year changed to:', this.value);
                        if (modeSelect && modeSelect.value === 'monthly') {
                            window.isLoadingChart = false;
                            await new Promise(resolve => setTimeout(resolve, 150));
                            await loadDailyUsers('monthly');
                        }
                    });
                    console.log('Year select listener attached');
                }
            }, 500);
        });
    </script>

    <script src="{{ asset('js/location-chart.js') }}" defer></script>

    <!-- Clients modal for per-location full list -->
    <div id="clientsModal" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/50">
        <div class="bg-white dark:bg-slate-800 rounded-lg w-11/12 md:w-2/3 lg:w-1/2 p-4">
            <div class="flex justify-between items-center mb-3">
                <h4 class="text-gray-900 dark:text-white font-semibold">Detail Clients</h4>
                <button onclick="hideClientsModal()" class="text-gray-600 dark:text-gray-300">Tutup</button>
            </div>
            <div id="clientsList" class="text-sm text-gray-700 dark:text-gray-200 max-h-72 overflow-auto space-y-2">
                <!-- populated by AJAX -->
            </div>
        </div>
    </div>

    <script>
        async function fetchLocationClients(sn) {
            const modal = document.getElementById('clientsModal');
            const container = document.getElementById('clientsList');
            container.innerHTML = '<div class="text-gray-400">Memuat...</div>';
            modal.classList.remove('hidden');
            modal.classList.add('flex');

            try {
                const res = await fetch(`/dashboard/location-clients?sn=${encodeURIComponent(sn)}`, {
                    credentials: 'same-origin',
                    signal: AbortSignal.timeout(5000)
                });

                if (!res.ok) throw new Error('Failed to fetch clients');

                const data = await res.json();
                container.innerHTML = '';

                if (!Array.isArray(data) || data.length === 0) {
                    container.innerHTML = '<div class="text-gray-400">Tidak ada perangkat</div>';
                    return;
                }

                data.forEach(c => {
                    const el = document.createElement('div');
                    el.className = 'p-2 bg-slate-700/50 rounded';
                    el.innerHTML = `
                        <div class="flex justify-between">
                            <div class="font-medium">${c.wifi_terminal_name || 'Unknown'}</div>
                            <div class="text-sm text-gray-300">${c.wifi_terminal_ip || '-'}</div>
                        </div>
                        <div class="text-xs text-gray-400">${c.wifi_terminal_mac || ''}</div>
                    `;
                    container.appendChild(el);
                });
            } catch (e) {
                container.innerHTML = '<div class="text-red-400">Gagal memuat data</div>';
                console.error('Error fetching location clients:', e);
            }
        }

        function hideClientsModal() {
            const modal = document.getElementById('clientsModal');
            modal.classList.add('hidden');
            modal.classList.remove('flex');
        }
    </script>
    <script>
        // Hide loader setelah semua chart ready
        window.addEventListener('load', function () {
            setTimeout(() => {
                document.getElementById('pageLoader')?.classList.add('hidden');
            }, 500);
        });
    </script>

    <script>
        // Function to update user online count in real-time
        function updateUserOnline() {
            fetch('/dashboard/user-online', {
                method: 'GET',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Content-Type': 'application/json',
                },
            })
                .then(response => response.json())
                .then(data => {
                    if (data.userOnline !== undefined) {
                        const element = document.getElementById('userOnlineCount');
                        if (element) {
                            element.textContent = data.userOnline;
                            if (data.cached) {
                                console.log('Using cached data:', data.error);
                            }
                        }
                    }
                })
                .catch(error => {
                    console.error('Error updating user online count:', error);
                    // Don't show error to user, just log it
                });
        }

        // Function to update weekly chart data in real-time
        function updateWeeklyChart() {
            if (!window.dailyUsersChart) {
                console.log('Chart not ready yet, skipping update');
                return;
            }

            // Get current mode from UI
            const modeSelect = document.getElementById('userChartMode');
            const currentMode = modeSelect ? modeSelect.value : 'weekly';

            // Only auto-update if in weekly mode, otherwise skip
            if (currentMode !== 'weekly') {
                console.log('Chart is in ' + currentMode + ' mode, skipping auto-update');
                return;
            }

            fetch('/dashboard/weekly-user-data', {
                method: 'GET',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Content-Type': 'application/json',
                },
            })
                .then(response => response.json())
                .then(data => {
                    if (data.labels && data.data && window.dailyUsersChart) {
                        // Update chart data
                        window.dailyUsersChart.data.labels = data.labels;
                        window.dailyUsersChart.data.datasets[0].data = data.data;
                        window.dailyUsersChart.update('none'); // Update without animation
                        updateChartStatistics(data.data);
                        console.log('Chart updated with new data:', data);
                    }
                })
                .catch(error => {
                    console.error('Error updating weekly chart:', error);
                    // Don't show error to user, just log it
                });
        }

        // Start real-time updates after page loads
        document.addEventListener('DOMContentLoaded', function () {
            console.log('Starting real-time updates...');

            // Initial updates
            setTimeout(() => {
                updateUserOnline();
                updateWeeklyChart();
            }, 2000); // Wait 2 seconds for everything to load

            // Update every 30 seconds (reduced frequency to avoid API overload)
            setInterval(() => {
                updateUserOnline();
                updateWeeklyChart();
            }, 30000);
        });
    </script>

    <script>
        // Sinkronisasi export link dengan selector bulan/tahun yang sudah ada
    function syncExportLinks() {
        const month = document.getElementById('monthSelect')?.value ?? {{ now()->month }};
        const year  = document.getElementById('yearSelect')?.value  ?? {{ now()->year }};
        const kemantren = document.getElementById('kemantrenFilter')?.value ?? '';

        const userLink = document.getElementById('export-monthly-user-link');
        if (userLink) {
            userLink.href = `/dashboard/export?type=monthly_user&month=${month}&year=${year}`;
        }

        const locLink = document.getElementById('export-monthly-location-link');
        if (locLink) {
            locLink.href = `/dashboard/export?type=monthly_location&month=${month}&year=${year}&kemantren=${kemantren}`;
        }
    }

    // Panggil saat DOM siap dan setiap kali dropdown berubah
    document.addEventListener('DOMContentLoaded', function () {
        syncExportLinks();
        document.getElementById('monthSelect')?.addEventListener('change', syncExportLinks);
        document.getElementById('yearSelect')?.addEventListener('change', syncExportLinks);
        document.getElementById('kemantrenFilter')?.addEventListener('change', syncExportLinks);
    });
    </script>
</x-app-layout>