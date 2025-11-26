<x-app-layout>
    <div class="flex bg-gray-900 min-h-screen text-gray-300">

        <!-- Sidebar -->
        <nav class="w-60 p-6 space-y-6 bg-gray-800">
            <ul class="space-y-4">
                <li>
                    <a href="#"
                        class="flex items-center space-x-2 bg-gray-700 rounded-md px-4 py-2 font-semibold text-gray-300">
                        <span>Overview</span>
                    </a>
                </li>

                <li>
                    <a href="{{ route('access-point') }}"
                        class="flex items-center space-x-2 text-gray-300 font-semibold">
                        <span>Access Point</span>
                    </a>
                </li>

                <li>
                    <a href="#"
                        class="flex items-center space-x-2 cursor-pointer text-gray-500 hover:text-gray-300">
                        <span>Connected Users</span>
                    </a>
                </li>

                <li>
                    <a href="#"
                        class="flex items-center space-x-2 cursor-pointer text-gray-500 hover:text-gray-300">
                        <span>Alert</span>
                    </a>
                </li>

                <li>
                    <a href="#"
                        class="flex items-center space-x-2 cursor-pointer text-gray-500 hover:text-gray-300">
                        <span>Settings</span>
                    </a>
                </li>
            </ul>
        </nav>

        <!-- Main -->
        <main class="flex-1 p-6 space-y-6">

            <h1 class="text-white font-bold text-2xl mb-4">Access Point</h1>

            <!-- Card Summary -->
            <div class="grid grid-cols-4 gap-6">

                <div class="bg-gray-800 rounded-lg p-4">
                    <p class="text-gray-400">Total Access Point</p>
                    <p class="text-white font-bold text-2xl">8</p>
                </div>

                <div class="bg-gray-800 rounded-lg p-4">
                    <p class="text-gray-400">AP Online</p>
                    <p class="text-green-400 font-bold text-2xl">7</p>
                </div>

                <div class="bg-gray-800 rounded-lg p-4">
                    <p class="text-gray-400">AP Offline</p>
                    <p class="text-red-400 font-bold text-2xl">1</p>
                </div>

                <div class="bg-gray-800 rounded-lg p-4">
                    <p class="text-gray-400">Total Connected Users</p>
                    <p class="text-white font-bold text-2xl">123</p>
                </div>

            </div>

            <!-- Data Access Point Table -->
            <div class="bg-gray-800 rounded-lg p-6">
                <h3 class="text-white font-semibold mb-4">Access Point Status</h3>

                <table class="w-full text-left text-gray-300">
                    <thead>
                        <tr>
                            <th class="pb-2">AP Name</th>
                            <th class="pb-2">IP Address</th>
                            <th class="pb-2">Status</th>
                            <th class="pb-2">Connected Users</th>
                        </tr>
                    </thead>

                    <tbody>
                        <tr class="border-b border-gray-700">
                            <td class="py-2">AP Lantai 1</td>
                            <td class="py-2">192.168.1.20</td>
                            <td class="py-2 flex items-center space-x-2">
                                <span class="w-3 h-3 bg-green-500 rounded-full inline-block"></span>
                                <span>Online</span>
                            </td>
                            <td class="py-2">12</td>
                        </tr>

                        <tr class="border-b border-gray-700">
                            <td class="py-2">AP Lantai 2</td>
                            <td class="py-2">192.168.1.21</td>
                            <td class="py-2 flex items-center space-x-2">
                                <span class="w-3 h-3 bg-red-500 rounded-full inline-block"></span>
                                <span>Offline</span>
                            </td>
                            <td class="py-2">0</td>
                        </tr>

                    </tbody>
                </table>
            </div>

        </main>
    </div>
</x-app-layout>
