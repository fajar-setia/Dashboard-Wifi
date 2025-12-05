<x-app-layout>
    <div class="flex-1 p-6 space-y-6">

        <!-- Header Metrics -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">

            <!-- Total Alerts Today -->
            <div class="bg-gray-800 p-4 rounded-lg shadow">
                <p class="text-gray-300 text-sm">Total Alerts Today</p>
                <p class="text-3xl font-bold text-white mt-2">
                    {{ $alertCount ?? 0 }}
                </p>
            </div>

            <!-- Active Users -->
            <div class="bg-gray-800 p-4 rounded-lg shadow">
                <p class="text-gray-300 text-sm">Active Users</p>
                <p class="text-3xl font-bold text-white mt-2">
                    {{ $activeUsers ?? 0 }}
                </p>
            </div>

            <!-- Connected AP -->
            <div class="bg-gray-800 p-4 rounded-lg shadow">
                <p class="text-gray-300 text-sm">Connected AP</p>
                <p class="text-3xl font-bold text-white mt-2">
                    {{ collect($aps)->count() }}
                </p>
            </div>
        </div>

        <!-- User Alert Section -->
        <div class="bg-gray-800 rounded-lg p-5 shadow">
            <div class="flex items-center gap-2 mb-3">
                <div class="w-4 h-4 rounded-full bg-red-600"></div>
                <p class="text-white font-semibold">User Alert</p>
                <span class="text-gray-400 text-sm">{{ $alertCount ?? 0 }}</span>
            </div>

            <div class="h-56 bg-gray-900/40 rounded-lg border border-gray-700 p-4 overflow-y-auto">

                {{-- ALERT: AP OFFLINE --}}
                @foreach ($apOffline ?? [] as $ap)
                    <div class="text-red-400 text-sm mb-2 flex items-center gap-2">
                        <span class="w-2 h-2 rounded-full bg-red-500"></span>
                        <span>AP Offline: {{ $ap->name ?? 'Tanpa Nama' }}</span>
                    </div>
                @endforeach

                @if (($alertCount ?? 0) === 0)
                    <p class="text-gray-500 text-sm text-center mt-10">Tidak ada alert.</p>
                @endif

            </div>
        </div>

        <!-- Actions Section -->
        <div class="bg-gray-800 rounded-lg p-5 shadow">
            <p class="text-white font-semibold mb-3">New Device</p>
            <div class="h-40 bg-gray-900/40 rounded-lg border border-gray-700 px-4 py-3 overflow-y-auto">
                {{-- ALERT: DEVICE BARU --}}
                @foreach ($deviceCount ?? [] as $mac)
                    <div class="text-yellow-300 text-sm mb-2 flex items-center gap-2">
                        <span class="w-2 h-2 rounded-full bg-yellow-400"></span>
                        <span>Device Baru: {{ $mac }}</span>
                    </div>
                @endforeach
            </div>
        </div>

    </div>
</x-app-layout>
