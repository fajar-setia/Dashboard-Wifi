<x-app-layout>
    <div class="flex bg-gray-900 min-h-screen text-gray-300">

        <main class="flex-1 p-6 space-y-6">

            <h1 class="text-white font-bold text-2xl mb-4">Access Point</h1>

            @if($error)
                <div class="bg-red-500 text-white p-3 rounded">
                    {{ $error }}
                </div>
            @endif

            <!-- Summary -->
            <div class="grid grid-cols-4 gap-6">
                <div class="bg-gradient-to-br from-blue-600 to-blue-800 rounded-xl p-6 shadow-xl hover:shadow-2xl hover:shadow-blue-500/20 transition-all duration-300 hover:scale-105">
                    <p class="text-gray-200">Total Access Point</p>
                    <p class="text-white font-bold text-2xl">{{ $devices->count() }}</p>
                </div>

                <div class="bg-gradient-to-br from-emerald-600 to-emerald-800 rounded-xl p-6 shadow-xl hover:shadow-2xl hover:shadow-emerald-500/20 transition-all duration-300 hover:scale-105">
                    <p class="text-gray-200">AP Online</p>
                    <p class="text-green-400 font-bold text-2xl">
                        {{ $devices->where('state', 'online')->count() }}
                    </p>
                </div>

                <div class="bg-gradient-to-br from-red-600 to-red-800 rounded-xl p-6 shadow-xl hover:shadow-2xl hover:shadow-red-500/20 transition-all duration-300 hover:scale-105">
                    <p class="text-gray-200">AP Offline</p>
                    <p class="text-red-400 font-bold text-2xl">
                        {{ $devices->where('state', '!=', 'online')->count() }}
                    </p>
                </div>

                <div class="bg-gradient-to-br from-yellow-600 to-yellow-800 rounded-xl p-6 shadow-xl hover:shadow-2xl hover:shadow-yellow-500/20 transition-all duration-300 hover:scale-105">
                    <p class="text-gray-200">Total Connected Users</p>
                    <p class="text-white font-bold text-2xl">
                        @php
                            $total = 0;
                            foreach ($devices as $d) {
                                foreach (['5G', '2_4G', 'unknown'] as $band) {
                                    $total += count($d['wifiClients'][$band] ?? []);
                                }
                            }
                        @endphp
                        {{ $total }}
                    </p>
                </div>
            </div>

            <!-- Table -->
            <div class="bg-gray-800 rounded-lg p-6">
                <h3 class="text-white font-semibold mb-4">Access Point Status</h3>

                <table class="w-full text-left text-gray-300">
                    <thead>
                        <tr>
                            <th class="pb-2">AP Name</th>
                            <th class="pb-2">Status</th>
                            <th class="pb-2">Connected Users</th>
                        </tr>
                    </thead>

                    <tbody>
                        @foreach($devices as $d)
                            @php
                                $userCount = 0;
                                foreach (['5G', '2_4G', 'unknown'] as $band) {
                                    $userCount += count($d['wifiClients'][$band] ?? []);
                                }
                            @endphp

                            <tr class="border-b border-gray-700">
                                <td class="py-2">
                                    {{ $d['model'] }} - {{ $d['sn'] }}
                                </td>

                                <td class="py-2 flex items-center space-x-2">
                                    @if(strtolower($d['state']) === 'online')
                                        <span class="w-3 h-3 bg-green-500 rounded-full inline-block"></span>
                                        <span>Online</span>
                                    @else
                                        <span class="w-3 h-3 bg-red-500 rounded-full inline-block"></span>
                                        <span>Offline</span>
                                    @endif
                                </td>

                                <td class="py-2">{{ $userCount }}</td>
                            </tr>
                        @endforeach
                    </tbody>

                </table>
            </div>

        </main>
    </div>
</x-app-layout>
