<x-app-layout>
    <div class="flex bg-gray-900 min-h-screen text-gray-300">

        <main class="flex-1 p-6 space-y-6">
            <!-- Loading overlay -->
            <div id="pageLoader" class="fixed inset-0 bg-slate-900/90 z-50 flex items-center justify-center">
                <div class="text-white text-center">
                    <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-white mx-auto mb-3"></div>
                    <p>Memuat Pengguna Terhubung...</p>
                </div>
            </div>

            <h1 class="text-white font-bold text-2xl mb-4">Pengguna Terhubung</h1>

            <form method="GET" class="mb-6 flex gap-3">
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Search SN / Model"
                    class="px-4 py-2 rounded bg-gray-800 text-white w-64">

                <select name="perPage" class="px-3 py-2 rounded bg-gray-800 text-white">
                    @foreach ([5, 10, 20] as $n)
                        <option value="{{ $n }}" @selected(request('perPage') == $n)>
                            {{ $n }}
                        </option>
                    @endforeach
                </select>

                <button class="px-4 py-2 bg-blue-600 rounded text-white">
                    Filter
                </button>
            </form>
            @if (!empty($error))
                <div class="bg-red-500 text-white px-4 py-3 rounded-lg mb-6">
                    {{ $error }}
                </div>
            @endif

            <div class="space-y-4">

                @foreach ($aps as $ap)
                    <div class="grid grid-cols-10 gap-4">

                        <!-- Box 30% -->
                        <div class="col-span-3 bg-gray-800 p-4 rounded-lg text-white space-y-2">
                            <p class="font-semibold text-lg text-blue-400">{{ is_array($ap['location'] ?? null) ? implode(', ', $ap['location']) : ($ap['location'] ?? ($ap['sn'] ?? 'Unknown')) }}</p>
                            <p class="text-sm text-gray-400">SN: <span class="font-medium text-gray-200">{{ $ap['sn'] ?? '-' }}</span></p>
                            <p class="text-sm text-gray-400">Model: <span class="font-medium text-gray-200">{{ $ap['model'] ?? '-' }}</span></p>
                            <p class="text-sm text-gray-400">Kemantren: <span class="font-medium text-gray-200">{{ is_array($ap['kemantren'] ?? null) ? implode(', ', $ap['kemantren']) : ($ap['kemantren'] ?? '-') }}</span></p>
                            <p class="text-sm text-gray-400">Kelurahan: <span class="font-medium text-gray-200">{{ is_array($ap['kelurahan'] ?? null) ? implode(', ', $ap['kelurahan']) : ($ap['kelurahan'] ?? '-') }}</span></p>
                            <p class="text-sm text-gray-400">RT/RW: <span class="font-medium text-gray-200">{{ $ap['rt'] ?? '-' }} / {{ $ap['rw'] ?? '-' }}</span></p>
                            <p class="text-sm {{ ($ap['state'] ?? '') == 'online' ? 'text-green-400' : 'text-red-400' }}">
                                {{ ucfirst($ap['state'] ?? 'unknown') }}
                            </p>
                        </div>

                        <!-- Box 70% -->
                        <div class="col-span-7 bg-gray-800 p-4 rounded-lg text-white">

                            <!-- Header Connected -->
                            <div class="flex items-center justify-between mb-4">
                                <div>
                                    <p class="text-sm text-gray-300">Pengguna Terhubung</p>
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
                                        <th class="pb-4 font-semibold text-gray-200 w-2/6">Nama Perangkat</th>
                                        <th class="pb-4 font-semibold text-gray-200 w-2/6">Alamat IP</th>
                                        <th class="pb-4 font-semibold text-gray-200 w-2/6">Alamat MAC</th>
                                        <th class="pb-4 font-semibold text-gray-200 w-12">Status</th>
                                    </tr>
                                </thead>
                                <tbody>

                                    @foreach ($clients as $client)
                                        <tr
                                            class="border-b border-slate-700/50 hover:bg-slate-700/30 transition-colors duration-200">
                                            <td class="py-4">{{ $client['wifi_terminal_name'] ?? 'Unknown' }}</td>
                                            <td class="py-4">{{ $client['wifi_terminal_ip'] ?? '-' }}</td>
                                            <td class="py-4">{{ $client['wifi_terminal_mac'] ?? '-' }}</td>
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
            <div class="mt-6">
    {{ $aps->links() }}
</div>
 </main>
    <script>
    // Hide loader setelah semua chart ready
    window.addEventListener('load', function() {
        setTimeout(() => {
            document.getElementById('pageLoader')?.classList.add('hidden');
        }, 500);
    });
    </script>
    </div>
</x-app-layout>
