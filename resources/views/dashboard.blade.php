<x-app-layout>

    <div class="space-y-6 bg-gradient-to-br from-slate-900 via-slate-800 to-slate-900 min-h-screen p-6">

        <!-- Summary Cards -->
        <div class="grid grid-cols-1 md:grid-cols-1 lg:grid-cols-2 gap-6">
            <div
                class="group relative overflow-hidden bg-gradient-to-br from-blue-600 to-blue-800 rounded-xl p-6 shadow-xl hover:shadow-2xl hover:shadow-blue-500/20 transition-all duration-300 hover:scale-105">
                <div
                    class="absolute inset-0 bg-gradient-to-r from-blue-400/0 to-blue-400/0 group-hover:from-blue-400/10 group-hover:to-blue-400/20 transition-all duration-300">
                </div>
                <div class="relative">
                    <p class="text-blue-200 text-sm font-medium mb-2 uppercase tracking-wider">Pengguna Terhubung</p>
                    <p class="text-white font-bold text-4xl">{{ $userOnline }}</p>
                </div>
            </div>

            <div
                class="group relative overflow-hidden bg-gradient-to-br from-purple-600 to-purple-800 rounded-xl p-6 shadow-xl hover:shadow-2xl hover:shadow-purple-500/20 transition-all duration-300 hover:scale-105">
                <div
                    class="absolute inset-0 bg-gradient-to-r from-purple-400/0 to-purple-400/0 group-hover:from-purple-400/10 group-hover:to-purple-400/20 transition-all duration-300">
                </div>
                <div class="relative">
                    <p class="text-purple-200 text-sm font-medium mb-2 uppercase tracking-wider">Total ONT</p>
                    <p class="text-white font-bold text-4xl">{{ $totalAp }}</p>
                </div>
            </div>
        </div>

        <!-- Charts Section -->
        <div class="grid gap-4 xl:grid-cols-[4fr_6fr] lg:grid-cols-2 rounded-xl">
            <!-- KIRI -->
            <div
                class="bg-slate-800/50 backdrop-blur rounded-xl p-4 border border-slate-700/50 flex flex-col  shadow-xl shadow-black/20">
                <p class="text-white font-semibold mb-3 text-center">Kapasitas User</p>
                <div class="relative w-full h-56">
                    <canvas id="userChart" class="absolute inset-0 h-full block"></canvas>
                </div>
            </div>

            <!-- KANAN -->
            <div
                class="bg-slate-800/50 backdrop-blur rounded-xl p-4 border border-slate-700/50 flex flex-col  shadow-xl shadow-black/20">
                <h3 class="text-white font-semibold mb-3">Rekap Total User</h3>
                <div id="userChartDailyControls" class="flex justify-end items-center mb-3">
                    <select id="userChartDailyMonth"
                        class="bg-slate-700 border border-slate-600 text-gray-200 text-sm rounded-lg px-3 py-1.5 focus:ring-blue-500">
                        <option value="weekly">Mingguan</option>
                    </select>
                </div>
                <div class="w-full h-56">
                    <canvas id="userChartDaily" class=" h-full"></canvas>
                </div>
            </div>
        </div>

        <!-- Rekap Mingguan & Bulanan Per Lokasi -->
        <div class="bg-slate-800/50 backdrop-blur rounded-xl p-6 shadow-xl border border-slate-700/50">

            <div class="flex flex-col lg:flex-row gap-6">

                <!-- Kiri: Search & Filter -->
                <div class="lg:w-1/4 space-y-4">
                    <h3 class="text-white font-semibold text-lg">Filter</h3>

                    <div>
                        <label class="text-gray-300 text-sm block mb-2">Cari Lokasi</label>
                        <input type="text" id="locationSearch" placeholder="Cari lokasi..."
                            class="w-full px-3 py-2 rounded bg-slate-700 text-white text-sm border border-slate-600 focus:ring-blue-500">
                    </div>

                    <div>
                        <label class="text-gray-300 text-sm block mb-2">Kemantren</label>
                        <select id="kemantrenFilter"
                            class="w-full px-3 py-2 rounded bg-slate-700 text-white text-sm border border-slate-600 focus:ring-blue-500">
                            <option value="">Semua</option>
                            @foreach ($kemantrenList as $km)
                                <option value="{{ $km }}">{{ $km }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="text-gray-300 text-sm block mb-2">Bulan (Bulanan)</label>
                        <select id="monthFilter" onchange="updateLocationChart()"
                            class="w-full px-3 py-2 rounded bg-slate-700 text-white text-sm border border-slate-600 focus:ring-blue-500">
                            @foreach ($months as $num => $name)
                                <option value="{{ $num }}" {{ $num == $currentMonth ? 'selected' : '' }}>
                                    {{ $name }} {{ $currentYear }}</option>
                            @endforeach
                        </select>
                    </div>

                    <button onclick="filterLocationChart()"
                        class="w-full px-4 py-2 bg-blue-600 hover:bg-blue-700 rounded text-white text-sm font-medium">
                        Filter
                    </button>
                </div>

                <!-- Kanan: Chart & Kontrol -->
                <div class="lg:w-3/4 space-y-4">
                    <div class="flex justify-between items-center">
                        <h3 class="text-white font-semibold text-lg">Rekap Per Lokasi</h3>
                        <div class="flex gap-2">
                            <select id="chartPeriod" onchange="updateLocationChart()"
                                class="px-3 py-1.5 bg-slate-700 border border-slate-600 text-gray-200 text-sm rounded-lg focus:ring-blue-500">
                                <option value="weekly">Mingguan</option>
                                <option value="monthly">Bulanan</option>
                            </select>
                        </div>
                    </div>

                    <div class="bg-slate-700/50 rounded-lg p-4 border border-slate-600/50">
                        <canvas id="locationChart" class="w-full" style="max-height: 300px;"></canvas>
                    </div>
                </div>

            </div>
        </div>

        <!-- Rekap User Online Per Lokasi -->
        <div class="bg-slate-800/50 backdrop-blur rounded-xl p-6 shadow-xl border border-slate-700/50">

            <h3 class="text-white font-semibold text-lg mb-6">Rekap User Online Per Lokasi</h3>

            <div class="space-y-4">

                @forelse ($locations as $locationData)
                    <div class="grid grid-cols-10 gap-4">

                        <!-- Box 30% - Lokasi -->
                        <div
                            class="col-span-3 bg-slate-700/50 p-4 rounded-lg text-white space-y-2 border border-slate-600/50">
                            <p class="font-semibold text-lg text-blue-400">{{ $locationData['location'] }}</p>
                            <div class="text-xs space-y-1 text-gray-300">
                                <p><span class="text-gray-400">Kemantren:</span> {{ $locationData['kemantren'] }}</p>
                                <p><span class="text-gray-400">Kelurahan:</span> {{ $locationData['kelurahan'] }}</p>
                                <p><span class="text-gray-400">RT/RW:</span> {{ $locationData['rt'] }} /
                                    {{ $locationData['rw'] }}</p>
                                <p><span class="text-gray-400">SN:</span> {{ $locationData['sn'] }}</p>
                            </div>
                            <div class="pt-2 border-t border-slate-600">
                                <p class="text-sm text-gray-400">User Terhubung</p>
                                <p class="text-2xl font-bold text-green-400">{{ $locationData['count'] }}</p>
                            </div>
                        </div>

                        <!-- Box 70% - Detail Pengguna (tampilkan maksimal 5) -->
                        <div
                            class="col-span-7 bg-slate-700/50 p-4 rounded-lg text-white border border-slate-600/50 overflow-x-auto">

                            <table class="w-full text-left text-gray-300 text-sm table-fixed">
                                <thead class="border-b border-slate-600">
                                    <tr>
                                        <th class="pb-3 font-semibold text-gray-200 w-2/6">Nama Perangkat</th>
                                        <th class="pb-3 font-semibold text-gray-200 w-2/6">Alamat IP</th>
                                        <th class="pb-3 font-semibold text-gray-200 w-2/6">Alamat MAC</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach (array_slice($locationData['clients'], 0, 5) as $client)
                                        <tr
                                            class="border-b border-slate-600/30 hover:bg-slate-600/20 transition-colors duration-200">
                                            <td class="py-3">{{ $client['wifi_terminal_name'] ?? 'Unknown' }}</td>
                                            <td class="py-3">{{ $client['wifi_terminal_ip'] ?? '-' }}</td>
                                            <td class="py-3 text-xs">{{ $client['wifi_terminal_mac'] ?? '-' }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>

                        </div>

                    </div>
                @empty
                    <div class="py-8 text-center text-gray-400">
                        Tidak Ada Perangkat Terhubung
                    </div>
                @endforelse

            </div>

            <!-- Footer Pagination (lokasi) -->
            @if ($locations->hasPages())
                <div
                    class="flex flex-col sm:flex-row items-center justify-between border-t border-slate-700 pt-6 gap-4">

                    <div class="text-sm text-gray-400">
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
        ::-webkit-scrollbar {
            width: 8px;
            height: 8px;
        }

        ::-webkit-scrollbar-track {
            background: #1e293b;
        }

        ::-webkit-scrollbar-thumb {
            background: #475569;
            border-radius: 4px;
        }

        ::-webkit-scrollbar-thumb:hover {
            background: #64748b;
        }
    </style>

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <script>
        // Data untuk location chart - per hari mingguan
        window.dayLabels = @json($dayLabels ?? []);
        window.weeklyLocationByDay = @json($weeklyLocationByDay ?? []);
        window.monthlyLocationData = @json($monthlyLocationData ?? []);
        window.currentMonth = @json($currentMonth ?? 1);
        window.currentYear = @json($currentYear ?? now()->year);

        function updateUrlParam(key, value) {
            const url = new URL(window.location.href);
            url.searchParams.set(key, value);
            url.searchParams.set('page', 1);
            return url.toString();
        }

        // Preserve scroll position on pagination
        document.addEventListener('DOMContentLoaded', function() {
            const paginationLinks = document.querySelectorAll('a[href*="page="]');
            paginationLinks.forEach(link => {
                link.addEventListener('click', function(e) {
                    sessionStorage.setItem('scrollPosition', window.scrollY);
                });
            });

            const savedPosition = sessionStorage.getItem('scrollPosition');
            if (savedPosition) {
                window.scrollTo(0, parseInt(savedPosition));
                sessionStorage.removeItem('scrollPosition');
            }
        });

        // Chart user
document.addEventListener('DOMContentLoaded', () => {
    const el = document.getElementById('userChart');
    if (!el) return;

    const ctx = el.getContext('2d');

    const gradient = ctx.createLinearGradient(0, 0, 0, 200);
    gradient.addColorStop(0, 'rgba(59,130,246,0.9)');
    gradient.addColorStop(1, 'rgba(59,130,246,0.35)');

    new Chart(ctx, {
        type: 'bar',
        data: {
            labels: ['User Terhubung'],
            datasets: [{
                label: 'Online Users',
                data: [@json($userOnline ?? 0)],
                backgroundColor: gradient,
                borderColor: '#60a5fa',
                borderWidth: 1.5,
                borderRadius: 10,
                barThickness: 60,
                hoverBackgroundColor: '#2563eb'
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            animation: {
                duration: 900,
                easing: 'easeOutQuart'
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
			stepSize: 1,
			precision: 0,
			callback: value => Math.round(value),
                        color: '#cbd5f5',
                        padding: 4,
                        font: { size: 11 }
                    },
                    grid: {
                        color: 'rgba(148,163,184,0.08)',
                        drawBorder: false
                    }
                },
                x: {
                    ticks: {
                        color: '#94a3b8',
                        font: { size: 12 }
                    },
                    grid: { display: false }
                }
            },
            plugins: {
                legend: { display: false },
                tooltip: {
                    backgroundColor: '#020617',
                    borderColor: '#1e293b',
                    borderWidth: 1,
                    titleColor: '#e5e7eb',
                    bodyColor: '#93c5fd',
                    padding: 10,
                    displayColors: false,
                    callbacks: {
                        label: ctx => ` ${ctx.raw} users online`
                    }
                }
            }
        }
    });
});
        window.dailyUsersLabels = @json($dailyUsers['labels'] ?? []);
        window.dailyUsersData = @json($dailyUsers['data'] ?? []);
    </script>

    <script src="{{ asset('js/location-chart.js') }}"></script>

</x-app-layout>
