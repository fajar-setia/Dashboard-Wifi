<x-app-layout>

    @if (session('api_error'))
        <div class="bg-red-600/20 border border-red-600 text-red-300 px-4 py-3 rounded-lg mb-4">
            {{ session('api_error') }}
        </div>
    @endif

    <div class="flex-1 p-6 space-y-6">
        <!-- Loading overlay -->
        <div id="pageLoader"
            class="fixed inset-0 bg-white/50 dark:bg-slate-900/50 backdrop-blur-md z-50 flex items-center justify-center">
            <div class="text-slate-800 dark:text-white text-center">
                <div
                    class="animate-spin rounded-full h-12 w-12 border-b-2 border-cyan-600 dark:border-cyan-400 mx-auto mb-3">
                </div>
                <p class="font-medium">Memuat Alert...</p>
            </div>
        </div>

        <!-- Header Metrics -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">

            <!-- Total Alerts Today -->
            <div
                class="bg-gradient-to-br from-cyan-500 to-blue-600 rounded-xl p-6 shadow-lg hover:shadow-2xl hover:shadow-cyan-500/30 transition-all duration-300 hover:scale-105">
                <p class="text-cyan-100 text-sm font-semibold uppercase tracking-wider">Total Alerts Today</p>
                <p class="text-4xl font-bold text-white mt-2 drop-shadow-lg">
                    {{ $alertCount ?? 0 }}
                </p>
            </div>

            <!-- Active Users -->
            <div
                class="bg-gradient-to-br from-indigo-500 to-purple-600 rounded-xl p-6 shadow-lg hover:shadow-2xl hover:shadow-indigo-500/30 transition-all duration-300 hover:scale-105">
                <p class="text-indigo-100 text-sm font-semibold uppercase tracking-wider">Active Users</p>
                <p class="text-4xl font-bold text-white mt-2 drop-shadow-lg">
                    {{ $activeUsers ?? 0 }}
                </p>
            </div>

            <!-- Connected AP -->
            <div
                class="bg-gradient-to-br from-emerald-500 to-teal-600 rounded-xl p-6 shadow-lg hover:shadow-2xl hover:shadow-emerald-500/30 transition-all duration-300 hover:scale-105">
                <p class="text-emerald-100 text-sm font-semibold uppercase tracking-wider">Connected AP</p>
                <p class="text-4xl font-bold text-white mt-2 drop-shadow-lg">
                    {{ collect($aps)->count() }}
                </p>
            </div>
        </div>

        <!-- User Alert Section -->
        <div class="bg-white dark:bg-slate-800 rounded-lg p-5 shadow-lg border-2 border-slate-200 dark:border-slate-700">
            <div class="flex items-center gap-2 mb-3">
                <div class="w-4 h-4 rounded-full bg-red-600"></div>
                <p class="text-slate-900 dark:text-white font-semibold">User Alert</p>
                <span class="text-slate-600 dark:text-slate-400 text-sm font-medium">{{ $alertCount ?? 0 }}</span>
            </div>

            <div
                class="h-56 bg-slate-50 dark:bg-slate-900 rounded-lg border-2 border-slate-200 dark:border-slate-700 p-4 overflow-y-auto">
                @if (session('api_error'))
                    <div class="text-red-500 dark:text-red-400 text-sm mb-3 flex items-center gap-2">
                        <span class="w-2 h-2 rounded-full bg-red-500 animate-pulse"></span>
                        <span>CMSnya error Wak{{ session('api_error') }}</span>
                    </div>
                @endif
                {{-- ALERT: AP OFFLINE --}}
                @foreach ($apOffline ?? [] as $ap)
                    <div class="text-red-600 dark:text-red-400 text-sm mb-2 flex items-center gap-2 font-medium">
                        <span class="w-2 h-2 rounded-full bg-red-500"></span>
                        <span>AP Offline: {{ $ap['sn'] ?? 'Anonymous' }}</span>
                    </div>
                @endforeach

                @if (($alertCount ?? 0) === 0)
                    <p class="text-slate-500 dark:text-slate-400 text-sm text-center mt-10">Tidak ada alert.</p>
                @endif

            </div>
        </div>

        <!-- Actions Section -->
        <div class="bg-white dark:bg-slate-800 rounded-lg p-5 shadow-lg border-2 border-slate-200 dark:border-slate-700">
            <p class="text-slate-900 dark:text-white font-semibold mb-3">New Device</p>
            <div
                class="h-40 bg-slate-50 dark:bg-slate-900 rounded-lg border-2 border-slate-200 dark:border-slate-700 px-4 py-3 overflow-y-auto">
                {{-- ALERT: DEVICE BARU --}}
                @foreach ($deviceCount ?? [] as $mac)
                    <div class="text-amber-600 dark:text-amber-400 text-sm mb-2 flex items-center gap-2 font-medium">
                        <span class="w-2 h-2 rounded-full bg-amber-400"></span>
                        <span>Device Baru: {{ $mac }}</span>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
    <script>
        // Hide loader setelah semua chart ready
        window.addEventListener('load', function () {
            setTimeout(() => {
                document.getElementById('pageLoader')?.classList.add('hidden');
            }, 500);
        });
    </script>
</x-app-layout>