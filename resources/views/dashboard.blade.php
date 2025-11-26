<x-app-layout>
    <div class="flex bg-gray-900 min-h-screen text-gray-300">

        <!-- Sidebar -->
        <nav class="w-60 p-6 space-y-6 bg-gray-800">
            <ul class="space-y-4">
                <li>
                    <a href="#" class="flex items-center space-x-2 bg-gray-700 rounded-md px-4 py-2 font-semibold text-gray-300">
                        <span>Overview</span>
                    </a>
                </li>
                <li class="flex items-center space-x-2 cursor-pointer text-gray-500 hover:text-gray-300">
                    <span>Access Point</span>
                </li>
                <li class="flex items-center space-x-2 cursor-pointer text-gray-500 hover:text-gray-300">
                    <span>Connected Users</span>
                </li>
                <li class="flex items-center space-x-2 cursor-pointer text-gray-500 hover:text-gray-300">
                    <span>Alert</span>
                </li>
                <li class="flex items-center space-x-2 cursor-pointer text-gray-500 hover:text-gray-300">
                    <span>Settings</span>
                </li>
            </ul>
        </nav>

        <!-- Main -->
        <main class="flex-1 p-6 space-y-6">

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

            <div class="bg-gray-800 rounded-lg p-4">
                <canvas id="userChart"></canvas>
            </div>
        </div>

        <!-- Connected Users Table -->
        <div class="bg-gray-800 rounded-lg p-6">
            <h3 class="text-white font-semibold mb-4">Connected Users</h3>
            <table class="w-full text-left text-gray-300">
                <thead>
                    <tr>
                        <th class="pb-2">Device Name</th>
                        <th class="pb-2">IP Address</th>
                        <th class="pb-2">Status</th>
                    </tr>
                </thead>
                <tbody>

                        @foreach ($connections as $c)
                            @php
                                $clients = array_merge(
                                    $c['wifiClients']['5G'] ?? [],
                                    $c['wifiClients']['2_4G'] ?? [],
                                    $c['wifiClients']['unknown'] ?? []
                                );
                            @endphp

                            @foreach ($clients as $client)
                                <tr class="border-b border-gray-700">
                                    <td class="py-2">{{ $client['wifi_terminal_name'] ?? 'Unknown' }}</td>
                                    <td class="py-2">{{ $client['wifi_terminal_ip'] ?? '-' }}</td>
                                    <td class="py-2 flex items-center space-x-2">
                                        <span class="w-3 h-3 bg-green-500 rounded-full inline-block"></span>
                                        <span>Online</span>
                                    </td>
                                </tr>
                            @endforeach

                        @endforeach

                </tbody>
            </table>
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
                                    $c['wifiClients']['unknown'] ?? []
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
</script>

</x-app-layout>
