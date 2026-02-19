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
            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 mt-6">
                <form method="GET" class="flex gap-2">
                    <input type="text" name="search" value="{{ request('search') }}" placeholder="Search ONT..."
                        class="bg-white dark:bg-slate-800 border-2 border-slate-300 dark:border-slate-600 rounded-lg px-3 py-2 text-sm text-slate-900 dark:text-white placeholder:text-slate-400 dark:placeholder:text-slate-500 focus:ring-2 focus:ring-cyan-500 focus:border-cyan-500 shadow-sm w-48">

                    <select name="perPage" onchange="this.form.submit()"
                        class="bg-white dark:bg-slate-800 border-2 border-slate-300 dark:border-slate-600 rounded-lg px-3 py-2 text-sm text-slate-900 dark:text-white focus:ring-2 focus:ring-cyan-500 focus:border-cyan-500 shadow-sm">
                        @foreach (['15' => '15', '25' => '25', '50' => '50', 'all' => 'Semua'] as $val => $label)
                            <option value="{{ $val }}" {{ request('perPage', '15') == $val ? 'selected' : '' }}>
                                {{ $label }}
                            </option>
                        @endforeach
                    </select>

                    <button type="submit" class="bg-cyan-500 hover:bg-cyan-600 dark:bg-cyan-600 dark:hover:bg-cyan-700 text-white px-4 py-2 rounded-lg text-sm font-semibold transition-all duration-200 shadow-md hover:shadow-lg">
                        <svg class="w-4 h-4 inline-block mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                        </svg>
                        Search
                    </button>

                    @if (request('search') || request('state'))
                        <a href="{{ route('access-point', array_diff_key(request()->query(), ['search' => '', 'state' => '', 'page' => ''])) }}"
                            class="bg-slate-200 hover:bg-slate-300 dark:bg-slate-700 dark:hover:bg-slate-600 text-slate-700 dark:text-slate-200 px-4 py-2 rounded-lg text-sm font-semibold transition-all duration-200 shadow-md flex items-center gap-1">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                            Reset
                        </a>
                    @endif
                </form>

                {{-- Filter by State --}}
                <div class="flex gap-2">
                    <a href="{{ route('access-point') }}" 
                        class="px-4 py-2 rounded-lg text-sm font-semibold transition-all duration-200 shadow-md hover:shadow-lg {{ !request('state') ? 'bg-slate-700 dark:bg-slate-700 text-white' : 'bg-white dark:bg-slate-800 text-slate-700 dark:text-slate-300 border-2 border-slate-300 dark:border-slate-600 hover:bg-slate-100 dark:hover:bg-slate-750' }}">
                        Semua
                    </a>
                    <a href="{{ route('access-point', ['state' => 'online'] + request()->except('state')) }}" 
                        class="px-4 py-2 rounded-lg text-sm font-semibold transition-all duration-200 flex items-center gap-2 shadow-md {{ request('state') === 'online' ? 'bg-emerald-500 dark:bg-emerald-500 text-white' : 'bg-white dark:bg-slate-800 text-slate-700 dark:text-slate-300 border-2 border-slate-300 dark:border-slate-600 hover:bg-emerald-50 dark:hover:bg-slate-750 hover:border-emerald-400' }}">
                        <span class="w-2 h-2 rounded-full {{ request('state') === 'online' ? 'bg-white animate-pulse' : 'bg-emerald-500' }}"></span>
                        Online
                    </a>
                    <a href="{{ route('access-point', ['state' => 'offline'] + request()->except('state')) }}" 
                        class="px-4 py-2 rounded-lg text-sm font-semibold transition-all duration-200 flex items-center gap-2 shadow-md {{ request('state') === 'offline' ? 'bg-red-500 dark:bg-red-500 text-white' : 'bg-white dark:bg-slate-800 text-slate-700 dark:text-slate-300 border-2 border-slate-300 dark:border-slate-600 hover:bg-red-50 dark:hover:bg-slate-750 hover:border-red-400' }}">
                        <span class="w-2 h-2 rounded-full {{ request('state') === 'offline' ? 'bg-white' : 'bg-red-500' }}"></span>
                        Offline
                    </a>
                </div>
            </div>

            {{-- =====================
            | CARD GRID
            ===================== --}}
            @if ($devices->isEmpty() && !$error)
                <div class="bg-white dark:bg-slate-800 rounded-lg p-12 border-2 border-slate-200 dark:border-slate-700 shadow-lg text-center">
                    <div class="text-slate-400 animate-pulse text-lg">
                        <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-cyan-500 mx-auto mb-4"></div>
                        Mengambil data ONT...
                    </div>
                </div>
            @else
                <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5 gap-4">
                    @foreach ($devices as $d)
                        <div class="group bg-white dark:bg-slate-800 rounded-xl p-5 border-2 border-slate-200 dark:border-slate-700 shadow-md hover:shadow-xl hover:scale-105 transition-all duration-300 relative overflow-hidden">
                            
                            {{-- Status Badge --}}
                            <div class="absolute top-3 right-3">
                                @if ($d['state'] === 'online')
                                    <div class="flex items-center gap-1.5 bg-emerald-500/10 dark:bg-emerald-500/20 px-3 py-1 rounded-full">
                                        <span class="w-2 h-2 bg-emerald-500 rounded-full animate-pulse"></span>
                                        <span class="text-xs font-semibold text-emerald-600 dark:text-emerald-400">Online</span>
                                    </div>
                                @else
                                    <div class="flex items-center gap-1.5 bg-red-500/10 dark:bg-red-500/20 px-3 py-1 rounded-full">
                                        <span class="w-2 h-2 bg-red-500 rounded-full"></span>
                                        <span class="text-xs font-semibold text-red-600 dark:text-red-400">Offline</span>
                                    </div>
                                @endif
                            </div>

                            {{-- Serial Number (Utama) --}}
                            <div class="mb-2 pr-20">
                                <h3 class="text-base font-bold text-cyan-600 dark:text-cyan-400 font-mono">
                                    {{ $d['sn'] }}
                                </h3>
                            </div>

                            {{-- Model + User Count --}}
                            <div class="mb-3 pb-3 border-b border-slate-200 dark:border-slate-700 flex items-center justify-between gap-2">
                                <div class="flex items-center gap-2">
                                    <svg class="w-4 h-4 text-slate-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2zM9 9h6v6H9V9z"/>
                                    </svg>
                                    <p class="text-sm font-semibold text-slate-700 dark:text-slate-300">
                                        {{ $d['model'] }}
                                    </p>
                                </div>
                                <div class="flex items-center gap-1 bg-cyan-50 dark:bg-cyan-900/30 px-2 py-0.5 rounded-full flex-shrink-0">
                                    <svg class="w-3 h-3 text-cyan-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>
                                    </svg>
                                    <span class="text-xs font-bold text-cyan-600 dark:text-cyan-400">{{ $d['wifi_user_count'] }}</span>
                                </div>
                            </div>

                            {{-- Lokasi --}}
                            <div class="mb-3">
                                <div class="flex items-start gap-2">
                                    <svg class="w-4 h-4 text-slate-400 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                                    </svg>
                                    <p class="text-sm font-medium text-slate-900 dark:text-white {{ ($d['location'] ?? '') === 'Lokasi Tidak Diketahui' || strpos($d['location'] ?? '', 'Lokasi Tidak Diketahui') !== false ? 'italic text-slate-400 dark:text-slate-500' : '' }}">
                                        {{ $d['location'] ?? 'Lokasi Tidak Diketahui' }}
                                    </p>
                                </div>
                            </div>

                            {{-- Detail Info --}}
                            <div class="space-y-2">
                                @if (!empty($d['id_lifemedia']) && $d['id_lifemedia'] !== '-')
                                    <div class="flex items-center gap-2">
                                        <span class="text-xs font-medium text-slate-500 dark:text-slate-400 w-24">ID Lifemedia:</span>
                                        <span class="text-xs font-mono font-semibold text-slate-700 dark:text-slate-300">{{ $d['id_lifemedia'] }}</span>
                                    </div>
                                @endif

                                @if (isset($d['kemantren']) && $d['kemantren'] !== '-' && $d['kemantren'] !== '')
                                    <div class="flex items-center gap-2">
                                        <span class="text-xs font-medium text-slate-500 dark:text-slate-400 w-24">Kemantren:</span>
                                        <span class="text-xs text-slate-700 dark:text-slate-300">{{ $d['kemantren'] }}</span>
                                    </div>
                                @endif

                                @if (isset($d['kelurahan']) && $d['kelurahan'] !== '-' && $d['kelurahan'] !== '')
                                    <div class="flex items-center gap-2">
                                        <span class="text-xs font-medium text-slate-500 dark:text-slate-400 w-24">Kelurahan:</span>
                                        <span class="text-xs text-slate-700 dark:text-slate-300">{{ $d['kelurahan'] }}</span>
                                    </div>
                                @endif

                                {{-- RT/RW SELALU MUNCUL --}}
                                <div class="flex items-center gap-2">
                                    <span class="text-xs font-medium text-slate-500 dark:text-slate-400 w-24">RT/RW:</span>
                                    <span class="text-xs text-slate-700 dark:text-slate-300">
                                        @if (!empty($d['rt']) && !empty($d['rw']))
                                            {{ $d['rt'] }} / {{ $d['rw'] }}
                                        @else
                                            <span class="italic text-slate-400 dark:text-slate-500"></span>
                                        @endif
                                    </span>
                                </div>

                                @if (isset($d['ip']) && $d['ip'] !== '-' && $d['ip'] !== '')
                                    <div class="flex items-center gap-2">
                                        <span class="text-xs font-medium text-slate-500 dark:text-slate-400 w-24">IP Address:</span>
                                        <a href="http://{{ $d['ip'] }}" target="_blank" class="text-xs font-mono text-slate-700 dark:text-slate-300 hover:text-cyan-700 dark:hover:text-cyan-300 hover:underline transition-colors">{{ $d['ip'] }}</a>
                                    </div>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>

                {{-- PAGINATION --}}
                @if ($devices->hasPages())
                    <div class="mt-6 flex flex-col sm:flex-row justify-between items-center gap-4 bg-white dark:bg-slate-800 rounded-lg p-4 border-2 border-slate-200 dark:border-slate-700">
                        <div class="text-sm text-slate-600 dark:text-slate-400">
                            Menampilkan {{ $devices->firstItem() }} – {{ $devices->lastItem() }}
                            dari {{ $devices->total() }} data
                        </div>

                        <div>
                            {{ $devices->onEachSide(2)->links('pagination::tailwind') }}
                        </div>
                    </div>
                @endif
            @endif

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