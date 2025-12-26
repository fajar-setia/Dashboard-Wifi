<x-app-layout>
    <div class="flex bg-gray-900 min-h-screen text-gray-300">

        <main class="flex-1 p-6 space-y-6">

            <h1 class="text-white font-bold text-2xl mb-4">Connected Users</h1>

            @if (!empty($error))
                <div class="bg-red-500 text-white px-4 py-3 rounded-lg mb-6">
                    {{ $error }}
                </div>
            @endif

            <div class="space-y-4">

                @foreach ($aps as $ap)
                    <div class="grid grid-cols-10 gap-4">

                        <!-- Box 30% -->
                        <div class="col-span-3 bg-gray-800 p-4 rounded-lg text-white space-y-1">
                            <p class="font-semibold text-lg">{{ $ap['sn'] }}</p>
                            <p class="text-sm text-gray-400">Model : {{ $ap['model'] }}</p>
                            <p class="text-sm {{ $ap['state'] == 'online' ? 'text-green-400' : 'text-red-400' }}">
                                {{ ucfirst($ap['state']) }}
                            </p>
                        </div>

                        <!-- Box 70% -->
                        <div class="col-span-7 bg-gray-800 p-4 rounded-lg text-white">

                            <!-- Header Connected -->
                            <div class="flex items-center justify-between mb-4">
                                <div>
                                    <p class="text-sm text-gray-300">Connected Users</p>
                                    <p class="text-2xl font-bold">{{ $ap['connected'] }}</p>
                                </div>
                            </div>

                            @php
                                $clients = array_merge(
                                    $ap['wifiClients']['5G'] ?? [],
                                    $ap['wifiClients']['2_4G'] ?? [],
                                    $ap['wifiClients']['unknown'] ?? [],
                                );
                            @endphp

                            <!-- Table -->
                            <table class="w-full text-left text-gray-300 text-sm table-fixed">
                                <thead class="border-b border-slate-700">
                                    <tr>
                                        <th class="pb-4 font-semibold text-gray-200 w-2/6">Device Name</th>
                                        <th class="pb-4 font-semibold text-gray-200 w-2/6">IP Address</th>
                                        <th class="pb-4 font-semibold text-gray-200 w-2/6">MAC Address</th>
                                        <th class="pb-4 font-semibold text-gray-200 w-1/6">Speed</th>
                                        <th class="pb-4 font-semibold text-gray-200 w-12">Status</th>
                                    </tr>
                                </thead>
                                <tbody>

                                    @foreach ($clients as $client)
                                        <tr
                                            class="border-b border-slate-700/50 hover:bg-slate-700/30 transition-colors duration-200">
                                            <td class="py-4 ">{{ $client['wifi_terminal_name'] ?? 'Unknown' }}</td>
                                            <td class="py-4">{{ $client['wifi_terminal_ip'] ?? '-' }}</td>
                                            <td class="py-4">{{ $client['wifi_terminal_mac'] ?? '-' }}</td>
                                            <td class="py-4">{{ $client['wifi_terminal_speed'] ?? '-' }}</td>
                                            <td class="py-4 flex items-center justify-center">
                                                <div
                                                    class="h-3 w-3 rounded-full {{ $ap['state'] == 'online' ? 'bg-green-500' : 'bg-red-500' }}">
                                                </div>
                                            </td>
                                        </tr>
                                    @endforeach

                                </tbody>
                            </table>

                        </div>

                    </div>
                @endforeach


            </div>

        </main>

    </div>
</x-app-layout>
