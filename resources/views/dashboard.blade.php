<x-app-layout>

    <div class="p-2 space-y-2">

        <!-- Summary -->
        <div class="grid grid-cols-4 gap-6">
            <div class="bg-gray-800 rounded-lg p-4">
                <p class="text-gray-400">Connected Users</p>
                <p class="text-white font-bold text-2xl">{{ $userOnline }}</p>
            </div>

            <div class="bg-gray-800 rounded-lg p-4">
                <p class="text-gray-400">Active AP</p>
                <p class="text-white font-bold text-2xl">{{ $totalAp }}</p>
            </div>

            <div class="bg-gray-800 rounded-lg p-4">
                <p class="text-gray-400">Total Bandwidth Today</p>
                <p class="text-white font-bold text-2xl">134</p>
            </div>

            <div class="bg-gray-800 rounded-lg p-2 w-50 h-40">
                <canvas id="userChart"></canvas>
            </div>
        </div>

        {{-- grafik users --}}
        <div class="bg-gray-800 rounded-lg p-6">
            <h3 class="text-white font-semibold mb-4">Grafik Users</h3>
            <canvas id="userChartDaily"></canvas>
        </div>

        <!-- Detail Table -->
        <div class="bg-gray-800 rounded-lg p-6 mt-4 overflow-x-auto">
            <h3 class="text-white font-semibold mb-4">Detail Connected Devices</h3>
            <table class="w-full text-left text-gray-300 min-w-max">
                <thead>
                    <tr>
                        <th class="pb-2">Device Name</th>
                        <th class="pb-2">IP Address</th>
                        <th class="pb-2">MAC Address</th>
                        <th class="pb-2">Duration</th>
                        <th class="pb-2">Traffic</th>
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
                            <tr class="border-b border-gray-700">
                                <td class="py-2">{{ $client['wifi_terminal_name'] ?? 'Unknown' }}</td>
                                <td class="py-2">{{ $client['wifi_terminal_ip'] ?? '-' }}</td>
                                <td class="py-2">{{ $client['wifi_terminal_mac'] ?? '-' }}</td>
                                <td class="py-2">-</td>
                                <td class="py-2">-</td>
                            </tr>
                        @endforeach
                    @endforeach

                </tbody>
            </table>
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
                    backgroundColor: ['#3b82f6', '#1f2937'],
                    borderWidth: 0
                }]
            }
        });

        window.dailyUsersLabels = @json($dailyUsers['labels'] ?? []);
        window.dailyUsersData = @json($dailyUsers['data'] ?? []);
    </script>

</x-app-layout>
