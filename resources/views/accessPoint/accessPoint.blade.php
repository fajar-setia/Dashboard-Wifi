<x-app-layout>
    <div class="flex bg-gray-900 min-h-screen text-gray-300">
        <main class="flex-1 p-6 space-y-6">
        <!-- Loading overlay -->
        <div id="pageLoader" class="fixed inset-0 bg-slate-900/90 z-50 flex items-center justify-center">
            <div class="text-white text-center">
                <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-white mx-auto mb-3"></div>
                <p>Memuat Daftar ONT...</p>
            </div>
        </div>

            <h1 class="text-white font-bold text-2xl mb-4">ONT Dashboard</h1>

            {{-- ERROR --}}
            @if ($error)
                <div class="bg-red-600 text-white p-3 rounded">
                    {{ $error }}
                </div>
            @endif

            {{-- =====================
             | SUMMARY DASHBOARD
             ===================== --}}
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">

                <div class="bg-gradient-to-br from-blue-600 to-blue-800 rounded-xl p-6 shadow-xl">
                    <p class="text-gray-200">Total ONT</p>
                    <p class="text-white font-bold text-2xl">
                        {{ $summary['total'] }}
                    </p>
                </div>

                <div class="bg-gradient-to-br from-emerald-600 to-emerald-800 rounded-xl p-6 shadow-xl">
                    <p class="text-gray-200">ONT Online</p>
                    <p class="text-green-300 font-bold text-2xl">
                        {{ $summary['online'] }}
                    </p>
                </div>

                <div class="bg-gradient-to-br from-red-600 to-red-800 rounded-xl p-6 shadow-xl">
                    <p class="text-gray-200">ONT Offline</p>
                    <p class="text-red-300 font-bold text-2xl">
                        {{ $summary['offline'] }}
                    </p>
                </div>

                <div class="bg-gradient-to-br from-yellow-600 to-yellow-800 rounded-xl p-6 shadow-xl">
                    <p class="text-gray-200">Total Pengguna WiFi</p>
                    <p class="text-white font-bold text-2xl">
                        {{ $summary['users'] }}
                    </p>
                </div>

            </div>

            {{-- =====================
             | FILTER
             ===================== --}}
            <div class="flex justify-between items-center mt-6">
                <form method="GET" class="flex gap-2">
                    <input
                        type="text"
                        name="search"
                        value="{{ request('search') }}"
                        placeholder="Search SN / Model..."
                        class="bg-gray-700 border border-gray-600 rounded px-3 py-2 text-sm text-white"
                    >

                    <select
                        name="perPage"
                        onchange="this.form.submit()"
                        class="bg-gray-700 border border-gray-600 rounded px-3 py-2 text-sm text-white"
                    >
                        @foreach ([10, 25, 50, 100] as $n)
                            <option value="{{ $n }}" {{ request('perPage', 10) == $n ? 'selected' : '' }}>
                                {{ $n }}
                            </option>
                        @endforeach
                    </select>
                </form>
            </div>

            {{-- =====================
             | TABLE
             ===================== --}}
            <div class="bg-gray-800 rounded-lg p-6">

                <h3 class="text-white font-semibold mb-4">Status ONT</h3>

                @if ($devices->isEmpty() && !$error)
                    <div class="text-gray-400 animate-pulse">
                        Mengambil data ONT...
                    </div>
                @else
                    <table class="w-full text-left text-gray-300">
                        <thead>
                            <tr class="border-b border-gray-700">
                                <th class="pb-2">Serial Number</th>
                                <th class="pb-2">Lokasi</th>
                                <th class="pb-2">Status</th>
                                <th class="pb-2">User</th>
                            </tr>
                        </thead>

                        <tbody>
                            @foreach ($devices as $d)
                                <tr class="border-b border-gray-700 hover:bg-gray-700/40 transition">

                                    <td class="py-2">
                                        <div class="font-semibold text-white">
                                            {{ $d['model'] }}
                                        </div>
                                        <div class="text-xs text-gray-400">
                                            {{ $d['sn'] }}
                                        </div>
                                    </td>

                                    <td class="py-2
                                        {{ $d['lokasi'] === 'Lokasi Tidak Diketahui' ? 'text-gray-500 opacity-50 italic' : '' }}">
                                        {{ $d['lokasi'] }}
                                    </td>


                                    <td class="py-2">
                                        @if ($d['state'] === 'online')
                                            <span class="inline-flex items-center gap-2 text-green-400">
                                                <span class="w-2 h-2 bg-green-500 rounded-full"></span>
                                                Online
                                            </span>
                                        @else
                                            <span class="inline-flex items-center gap-2 text-red-400">
                                                <span class="w-2 h-2 bg-red-500 rounded-full"></span>
                                                Offline
                                            </span>
                                        @endif
                                    </td>

                                    <td class="py-2">
                                        {{ $d['wifi_user_count'] }}
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @endif

                {{-- PAGINATION --}}
                @if ($devices->hasPages())
                    <div class="mt-6 flex justify-between items-center text-sm text-gray-400">
                        <div>
                            Menampilkan {{ $devices->firstItem() }} – {{ $devices->lastItem() }}
                            dari {{ $devices->total() }} data
                        </div>

                        <div>
                            {{ $devices->onEachSide(2)->links('pagination::tailwind') }}
                        </div>
                    </div>
                @endif

            </div>

        </main>
    </div>

    {{-- AUTO REFRESH (OPTIONAL) --}}
    <script>
        setTimeout(() => {
            window.location.reload()
        }, 30000); // refresh tiap 30 detik
    </script>
    
    <script>
    // Hide loader setelah semua chart ready
    window.addEventListener('load', function() {
        setTimeout(() => {
            document.getElementById('pageLoader')?.classList.add('hidden');
        }, 500);
    });
    </script>
</x-app-layout>
