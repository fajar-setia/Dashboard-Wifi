<x-app-layout>

    <div class="p-6 space-y-6 bg-gray-900 min-h-screen">

        <!-- Summary Cards -->
        <div class="grid grid-cols-1 md:grid-cols-3 lg:grid-cols-3 gap-6">
            <div class="bg-gray-800 rounded-lg p-6 shadow-lg">
                <p class="text-gray-400 text-sm font-medium mb-2">Connected Users</p>
                <p class="text-white font-bold text-3xl">{{ $userOnline }}</p>
            </div>

            <div class="bg-gray-800 rounded-lg p-6 shadow-lg">
                <p class="text-gray-400 text-sm font-medium mb-2">Active AP</p>
                <p class="text-white font-bold text-3xl">{{ $totalAp }}</p>
            </div>

            <div class="bg-gray-800 rounded-lg p-6 shadow-lg">
                <p class="text-gray-400 text-sm font-medium mb-2">Total Bandwidth Today</p>
                <p class="text-white font-bold text-3xl">134</p>
            </div>
        </div>

        <!-- Charts Section -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <!-- User Capacity Chart -->
            <div class="bg-gray-800 rounded-lg p-6 shadow-lg">
                <p class="text-white font-semibold mb-4 text-center">User Capacity</p>
                <div class="h-64">
                    <canvas id="userChart" class="w-full h-full"></canvas>
                </div>
            </div>

            <!-- Daily Users Chart -->
            <div class="bg-gray-800 rounded-lg p-6 shadow-lg">
                <h3 class="text-white font-semibold mb-4">Grafik Users</h3>
                <div class="h-64">
                    <canvas id="userChartDaily" class="w-full h-full"></canvas>
                </div>
            </div>
        </div>

        <div class="h-6">
            <h1>sudah di ganti</h1>
        </div>

        <!-- Detail Table -->
        <div class="bg-gray-800 rounded-lg p-6 shadow-lg overflow-x-auto">
            <h3 class="text-white font-semibold mb-6 text-lg">Detail Connected Devices</h3>
            <div class="overflow-x-auto">
                <table class="w-full text-left text-gray-300 text-sm">
                    <thead class="border-b border-gray-700">
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
                                <tr class="border-b border-gray-700 hover:bg-gray-700 transition-colors">
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
                maintainAspectRatio: true,
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
