<x-app-layout>
    <div class="flex bg-gradient-to-br from-slate-50 via-blue-50/30 to-cyan-50/40 dark:bg-gradient-to-br dark:from-slate-900 dark:via-slate-800 dark:to-slate-900 min-h-screen text-slate-700 dark:text-slate-300">
        <main class="flex-1 p-6 space-y-6">
            <!-- Loading overlay -->
            <div id="pageLoader"
                class="fixed inset-0 bg-white/50 dark:bg-slate-900/50 backdrop-blur-md z-50 flex items-center justify-center">
                <div class="text-slate-800 dark:text-white text-center">
                    <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-cyan-600 dark:border-cyan-400 mx-auto mb-3"></div>
                    <p class="font-medium">Memuat Daftar ONT...</p>
                </div>
            </div>

            <h1 class="text-slate-900 dark:text-white font-bold text-2xl mb-4">ONT Dashboard</h1>

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

                <div class="bg-gradient-to-br from-cyan-500 to-blue-600 rounded-xl p-6 shadow-lg hover:shadow-2xl hover:shadow-cyan-500/30 transition-all duration-300 hover:scale-105">
                    <p class="text-cyan-100 text-sm font-semibold uppercase tracking-wider">Total ONT</p>
                    <p class="text-white font-bold text-3xl mt-2 drop-shadow-lg">
                        {{ $summary['total'] }}
                    </p>
                </div>

                <div class="bg-gradient-to-br from-emerald-500 to-teal-600 rounded-xl p-6 shadow-lg hover:shadow-2xl hover:shadow-emerald-500/30 transition-all duration-300 hover:scale-105">
                    <p class="text-emerald-100 text-sm font-semibold uppercase tracking-wider">ONT Online</p>
                    <p class="text-white font-bold text-3xl mt-2 drop-shadow-lg">
                        {{ $summary['online'] }}
                    </p>
                </div>

                <div class="bg-gradient-to-br from-red-500 to-rose-600 rounded-xl p-6 shadow-lg hover:shadow-2xl hover:shadow-red-500/30 transition-all duration-300 hover:scale-105">
                    <p class="text-red-100 text-sm font-semibold uppercase tracking-wider">ONT Offline</p>
                    <p class="text-white font-bold text-3xl mt-2 drop-shadow-lg">
                        {{ $summary['offline'] }}
                    </p>
                </div>

                <div class="bg-gradient-to-br from-amber-500 to-orange-600 rounded-xl p-6 shadow-lg hover:shadow-2xl hover:shadow-amber-500/30 transition-all duration-300 hover:scale-105">
                    <p class="text-amber-100 text-sm font-semibold uppercase tracking-wider">Total Pengguna WiFi</p>
                    <p class="text-white font-bold text-3xl mt-2 drop-shadow-lg">
                        {{ $summary['users'] }}
                    </p>
                </div>

            </div>

            {{-- =====================
            | FILTER
            ===================== --}}
            <div class="flex justify-between items-center mt-6">
                <form method="GET" class="flex gap-2">
                    <input type="text" name="search" value="{{ request('search') }}" placeholder="Search SN / Model..."
                        class="bg-white dark:bg-slate-800 border-2 border-slate-300 dark:border-slate-600 rounded-lg px-3 py-2 text-sm text-slate-900 dark:text-white focus:ring-2 focus:ring-cyan-500 focus:border-cyan-500">

                    <select name="perPage" onchange="this.form.submit()"
                        class="bg-white dark:bg-slate-800 border-2 border-slate-300 dark:border-slate-600 rounded-lg px-3 py-2 text-sm text-slate-900 dark:text-white focus:ring-2 focus:ring-cyan-500 focus:border-cyan-500">
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
            <div class="bg-white dark:bg-slate-800 rounded-lg p-6 border-2 border-slate-200 dark:border-slate-700 shadow-lg">

                <h3 class="text-slate-900 dark:text-white font-semibold mb-4">Status ONT</h3>

                @if ($devices->isEmpty() && !$error)
                    <div class="text-slate-400 animate-pulse">
                        Mengambil data ONT...
                    </div>
                @else
                    <table class="w-full text-left text-slate-600 dark:text-slate-300">
                        <thead>
                            <tr class="border-b-2 border-slate-300 dark:border-slate-600">
                                <th class="pb-3 font-semibold text-slate-700 dark:text-slate-200">Serial Number</th>
                                <th class="pb-3 font-semibold text-slate-700 dark:text-slate-200">Lokasi</th>
                                <th class="pb-3 font-semibold text-slate-700 dark:text-slate-200">Status</th>
                                <th class="pb-3 font-semibold text-slate-700 dark:text-slate-200">User</th>
                            </tr>
                        </thead>

                        <tbody>
                            @foreach ($devices as $d)
                                <tr
                                    class="border-b border-slate-200 dark:border-slate-600/30 hover:bg-cyan-50 dark:hover:bg-slate-700/40 transition-colors duration-200">

                                    <td class="py-3">
                                        <div class="font-semibold text-slate-900 dark:text-white">
                                            {{ $d['model'] }}
                                        </div>
                                        <div class="text-xs text-slate-500 dark:text-slate-400">
                                            {{ $d['sn'] }}
                                        </div>
                                    </td>

                                    <td
                                        class="py-3
                                                        {{ $d['lokasi'] === 'Lokasi Tidak Diketahui' ? 'text-slate-400 opacity-50 italic' : '' }}">
                                        {{ $d['lokasi'] }}
                                    </td>


                                    <td class="py-3">
                                        @if ($d['state'] === 'online')
                                            <span class="inline-flex items-center gap-2 text-emerald-600 dark:text-emerald-400 font-medium">
                                                <span class="w-2 h-2 bg-emerald-500 rounded-full"></span>
                                                Online
                                            </span>
                                        @else
                                            <span class="inline-flex items-center gap-2 text-red-600 dark:text-red-400 font-medium">
                                                <span class="w-2 h-2 bg-red-500 rounded-full"></span>
                                                Offline
                                            </span>
                                        @endif
                                    </td>

                                    <td class="py-3 font-medium">
                                        {{ $d['wifi_user_count'] }}
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @endif

                {{-- PAGINATION --}}
                @if ($devices->hasPages())
                    <div class="mt-6 flex justify-between items-center text-sm text-slate-600 dark:text-slate-400">
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
        window.addEventListener('load', function () {
            setTimeout(() => {
                document.getElementById('pageLoader')?.classList.add('hidden');
            }, 500);
        });
    </script>
</x-app-layout>