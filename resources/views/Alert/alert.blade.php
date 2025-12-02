<x-app-layout>
    <div class="flex-1 p-6 space-y-6">
        <!-- Header Metrics -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div class="bg-gray-800 p-4 rounded-lg shadow">
                <p class="text-gray-300 text-sm">Total Alerts Today</p>
                <p class="text-3xl font-bold text-white mt-2">4</p>
            </div>

            <div class="bg-gray-800 p-4 rounded-lg shadow">
                <p class="text-gray-300 text-sm">Active Users</p>
                <p class="text-3xl font-bold text-white mt-2">5</p>
            </div>

            <div class="bg-gray-800 p-4 rounded-lg shadow">
                <p class="text-gray-300 text-sm">Connected APs</p>
                <p class="text-3xl font-bold text-white mt-2">3</p>
            </div>
        </div>

        <!-- User Alert Section -->
        <div class="bg-gray-800 rounded-lg p-5 shadow">
            <div class="flex items-center gap-2 mb-3">
                <div class="w-4 h-4 rounded-full bg-red-600"></div>
                <p class="text-white font-semibold">User Alert</p>
                <span class="text-gray-400 text-sm">3</span>
            </div>

            <div class="h-56 bg-gray-900/40 rounded-lg border border-gray-700"></div>
        </div>

        <!-- Actions Section -->
        <div class="bg-gray-800 rounded-lg p-5 shadow">
            <p class="text-white font-semibold mb-3">Actions</p>

            <div class="h-40 bg-gray-900/40 rounded-lg border border-gray-700"></div>
        </div>

    </div>
</x-app-layout>
