<x-app-layout>
    <div class="flex bg-gradient-to-br from-slate-50 via-blue-50/30 to-cyan-50/40 dark:bg-gradient-to-br dark:from-slate-900 dark:via-slate-800 dark:to-slate-900 min-h-screen text-slate-700 dark:text-slate-300">

        <main class="flex-1 p-6 space-y-6">
            <!-- Loading overlay -->
            <div id="pageLoader" class="fixed inset-0 bg-white/50 dark:bg-slate-900/50 backdrop-blur-md z-50 flex items-center justify-center">
                <div class="text-slate-800 dark:text-white text-center">
                    <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-cyan-600 dark:border-cyan-400 mx-auto mb-3"></div>
                    <p class="font-medium">Memuat Pengguna Terhubung...</p>
                </div>
            </div>

            <h1 class="text-slate-900 dark:text-white font-bold text-2xl mb-4">Pengguna Terhubung</h1>

            <form method="GET" class="mb-6 flex gap-3">
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Search SN / Model"
                    class="px-4 py-2 rounded bg-white dark:bg-slate-800 text-slate-900 dark:text-white border-2 border-slate-300 dark:border-slate-600 w-64 focus:ring-2 focus:ring-cyan-500 focus:border-cyan-500">

                <select name="perPage" class="px-3 py-2 rounded bg-white dark:bg-slate-800 text-slate-900 dark:text-white border-2 border-slate-300 dark:border-slate-600 focus:ring-2 focus:ring-cyan-500 focus:border-cyan-500">
                    @foreach ([5, 10, 20] as $n)
                        <option value="{{ $n }}" @selected(request('perPage') == $n)>
                            {{ $n }}
                        </option>
                    @endforeach
                </select>

                <button class="px-4 py-2 bg-gradient-to-r from-cyan-500 to-blue-600 rounded-lg text-white hover:from-cyan-600 hover:to-blue-700 transition-all duration-200 font-semibold shadow-md">
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
                        <div class="col-span-3 bg-gradient-to-br from-slate-50 to-blue-50/40 dark:bg-gradient-to-br dark:from-slate-700 dark:to-slate-700/80 p-4 rounded-lg text-slate-900 dark:text-white space-y-2 border-2 border-slate-200 dark:border-slate-600 shadow-sm">
                            <p class="font-semibold text-lg text-cyan-600 dark:text-cyan-400">{{ is_array($ap['location'] ?? null) ? implode(', ', $ap['location']) : ($ap['location'] ?? ($ap['sn'] ?? 'Unknown')) }}</p>
                            <p class="text-sm text-slate-600 dark:text-slate-400">SN: <span class="font-medium text-slate-800 dark:text-slate-200">{{ $ap['sn'] ?? '-' }}</span></p>
                            <p class="text-sm text-slate-600 dark:text-slate-400">Model: <span class="font-medium text-slate-800 dark:text-slate-200">{{ $ap['model'] ?? '-' }}</span></p>
                            <p class="text-sm text-slate-600 dark:text-slate-400">Kemantren: <span class="font-medium text-slate-800 dark:text-slate-200">{{ is_array($ap['kemantren'] ?? null) ? implode(', ', $ap['kemantren']) : ($ap['kemantren'] ?? '-') }}</span></p>
                            <p class="text-sm text-slate-600 dark:text-slate-400">Kelurahan: <span class="font-medium text-slate-800 dark:text-slate-200">{{ is_array($ap['kelurahan'] ?? null) ? implode(', ', $ap['kelurahan']) : ($ap['kelurahan'] ?? '-') }}</span></p>
                            <p class="text-sm text-slate-600 dark:text-slate-400">RT/RW: <span class="font-medium text-slate-800 dark:text-slate-200">{{ $ap['rt'] ?? '-' }} / {{ $ap['rw'] ?? '-' }}</span></p>
                            <p class="text-sm {{ ($ap['state'] ?? '') == 'online' ? 'text-emerald-600 dark:text-emerald-400 font-semibold' : 'text-red-600 dark:text-red-400 font-semibold' }}">
                                {{ ucfirst($ap['state'] ?? 'unknown') }}
                            </p>
                        </div>

                        <!-- Box 70% -->
                        <div class="col-span-7 bg-white dark:bg-slate-700 p-4 rounded-lg text-slate-900 dark:text-white border-2 border-slate-200 dark:border-slate-600 shadow-sm">

                            <!-- Header Connected -->
                            <div class="flex items-center justify-between mb-4">
                                <div>
                                    <p class="text-sm text-gray-500 dark:text-gray-300">Pengguna Terhubung</p>
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
                            <table class="w-full text-left text-slate-600 dark:text-slate-300 text-sm table-fixed">
                                <thead class="border-b-2 border-slate-300 dark:border-slate-600">
                                    <tr>
                                        <th class="pb-4 font-semibold text-slate-700 dark:text-slate-200 w-2/6">Nama Perangkat</th>
                                        <th class="pb-4 font-semibold text-slate-700 dark:text-slate-200 w-2/6">Alamat IP</th>
                                        <th class="pb-4 font-semibold text-slate-700 dark:text-slate-200 w-2/6">Alamat MAC</th>
                                        <th class="pb-4 font-semibold text-slate-700 dark:text-slate-200 w-12">Status</th>
                                    </tr>
                                </thead>
                                <tbody>

                                    @foreach ($clients as $client)
                                        <tr
                                            class="border-b border-slate-200 dark:border-slate-600/30 hover:bg-cyan-50 dark:hover:bg-slate-600/20 transition-colors duration-200">
                                            <td class="py-4">{{ $client['wifi_terminal_name'] ?? 'Unknown' }}</td>
                                            <td class="py-4">{{ $client['wifi_terminal_ip'] ?? '-' }}</td>
                                            <td class="py-4">{{ $client['wifi_terminal_mac'] ?? '-' }}</td>
                                            <td class="py-4 flex items-center justify-center">
                                                <div
                                                    class="h-3 w-3 rounded-full {{ $ap['state'] == 'online' ? 'bg-emerald-500' : 'bg-red-500' }}">
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
