<x-app-layout>

    <div class="space-y-6 bg-gradient-to-br from-slate-900 via-slate-800 to-slate-900 min-h-screen p-6">

        <!-- Summary Cards -->
        <div class="grid grid-cols-1 md:grid-cols-3 lg:grid-cols-3 gap-6">
            <div
                class="group relative overflow-hidden bg-gradient-to-br from-blue-600 to-blue-800 rounded-xl p-6 shadow-xl hover:shadow-2xl hover:shadow-blue-500/20 transition-all duration-300 hover:scale-105">
                <div
                    class="absolute inset-0 bg-gradient-to-r from-blue-400/0 to-blue-400/0 group-hover:from-blue-400/10 group-hover:to-blue-400/20 transition-all duration-300">
                </div>
                <div class="relative">
                    <p class="text-blue-200 text-sm font-medium mb-2 uppercase tracking-wider">Connected Users</p>
                    <p class="text-white font-bold text-4xl">{{ $userOnline }}</p>
                </div>
            </div>

            <div
                class="group relative overflow-hidden bg-gradient-to-br from-purple-600 to-purple-800 rounded-xl p-6 shadow-xl hover:shadow-2xl hover:shadow-purple-500/20 transition-all duration-300 hover:scale-105">
                <div
                    class="absolute inset-0 bg-gradient-to-r from-purple-400/0 to-purple-400/0 group-hover:from-purple-400/10 group-hover:to-purple-400/20 transition-all duration-300">
                </div>
                <div class="relative">
                    <p class="text-purple-200 text-sm font-medium mb-2 uppercase tracking-wider">Active AP</p>
                    <p class="text-white font-bold text-4xl">{{ $totalAp }}</p>
                </div>
            </div>

            <div
                class="group relative overflow-hidden bg-gradient-to-br from-emerald-600 to-emerald-800 rounded-xl p-6 shadow-xl hover:shadow-2xl hover:shadow-emerald-500/20 transition-all duration-300 hover:scale-105">
                <div
                    class="absolute inset-0 bg-gradient-to-r from-emerald-400/0 to-emerald-400/0 group-hover:from-emerald-400/10 group-hover:to-emerald-400/20 transition-all duration-300">
                </div>
                <div class="relative">
                    <p class="text-emerald-200 text-sm font-medium mb-2 uppercase tracking-wider">Total Bandwidth Today
                    </p>
                    <p class="text-white font-bold text-4xl">134</p>
                </div>
            </div>
        </div>

        <!-- Charts Section -->
        <div class="grid gap-4 xl:grid-cols-[4fr_6fr] lg:grid-cols-2 rounded-xl">
            <!-- KIRI -->
            <div class="bg-slate-800/50 backdrop-blur rounded-xl p-4 border border-slate-700/50 flex flex-col  shadow-xl shadow-black/20">
                <p class="text-white font-semibold mb-3 text-center">User Capacity</p>
                <div class="relative w-full h-56">
                    <canvas id="userChart" class="absolute inset-0 h-full block"></canvas>
                </div>
            </div>

            <!-- KANAN -->
            <div class="bg-slate-800/50 backdrop-blur rounded-xl p-4 border border-slate-700/50 flex flex-col  shadow-xl shadow-black/20">
                <h3 class="text-white font-semibold mb-3">Grafik Users</h3>
                <div class="w-full h-56">
                    <canvas id="userChartDaily" class=" h-full"></canvas>
                </div>
            </div>
        </div>

        <!-- Detail Table -->
        <div class="bg-slate-800/50 backdrop-blur rounded-xl p-6 shadow-xl border border-slate-700/50 overflow-x-auto">
            <h3 class="text-white font-semibold mb-6 text-lg">Detail Connected Devices</h3>
            <div class="overflow-x-auto">
                <table class="w-full text-left text-gray-300 text-sm">
                    <thead class="border-b border-slate-700">
                        <tr>
                            <th class="pb-4 font-semibold text-gray-200">Device Name</th>
                            <th class="pb-4 font-semibold text-gray-200">IP Address</th>
                            <th class="pb-4 font-semibold text-gray-200">MAC Address</th>
                            <th class="pb-4 font-semibold text-gray-200">Duration</th>
                            <th class="pb-4 font-semibold text-gray-200">Traffic</th>
                        </tr>
                    </thead>
                    <tbody>

                        @foreach ($connections as $c)
                            @php
                                $clients = array_merge(
                                    $c['wifiClients']['5G'] ?? [],
                                    $c['wifiClients']['2_4G'] ?? [],
                                    $c['wifiClients']['unknown'] ?? [],
                                );
                            @endphp

                            @foreach ($clients as $client)
                                <tr
                                    class="border-b border-slate-700/50 hover:bg-slate-700/30 transition-colors duration-200">
                                    <td class="py-4">{{ $client['wifi_terminal_name'] ?? 'Unknown' }}</td>
                                    <td class="py-4">{{ $client['wifi_terminal_ip'] ?? '-' }}</td>
                                    <td class="py-4">{{ $client['wifi_terminal_mac'] ?? '-' }}</td>
                                    <td class="py-4">-</td>
                                    <td class="py-4">-</td>
                                </tr>
                            @endforeach
                        @endforeach

                    </tbody>
                </table>
            </div>
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
        const userChart = document.getElementById('userChart').getContext('2d');
        new Chart(userChart, {
            type: 'doughnut',
            data: {
                labels: ['Connected Users', 'Capacity'],
                datasets: [{
                    data: [@json($userOnline ?? 0), @json(max(0, 200 - ($userOnline ?? 0)))],
                    backgroundColor: ['#3b82f6', '#374151'],
                    borderWidth: 2,
                    borderColor: '#1f2937'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            color: '#d1d5db',
                            padding: 20,
                            font: {
                                size: 12
                            }
                        }
                    }
                }
            }
        });

        window.dailyUsersLabels = @json($dailyUsers['labels'] ?? []);
        window.dailyUsersData = @json($dailyUsers['data'] ?? []);
    </script>

</x-app-layout>
