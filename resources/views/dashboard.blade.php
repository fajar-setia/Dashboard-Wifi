<x-app-layout>
    <div class="flex bg-gray-900 min-h-screen text-gray-300">
        <!-- Sidebar Menu -->
        <nav class="w-60 p-6 space-y-6 bg-gray-800">
            <ul class="space-y-4">
                <li>
                    <a href="#" class="flex items-center space-x-2 bg-gray-700 rounded-md px-4 py-2 font-semibold text-gray-300">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-blue-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M13 5v6h6" /></svg>
                        <span>Overview</span>
                    </a>
                </li>
                <li class="flex items-center space-x-2 cursor-pointer text-gray-500 hover:text-gray-300">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.104 0-2 .896-2 2v8h4v-8c0-1.104-.896-2-2-2z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 19a2 2 0 002 2h8a2 2 0 002-2v-1H6v1z" /></svg>
                    <span>Access Point</span>
                </li>
                <li class="flex items-center space-x-2 cursor-pointer text-gray-500 hover:text-gray-300">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 12a4 4 0 00-8 0m8 0h-8m8 0v4m0 0H8m8 0v-4" /></svg>
                    <span>Connected Users</span>
                </li>
                <li class="flex items-center space-x-2 cursor-pointer text-gray-500 hover:text-gray-300">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6 6 0 10-12 0v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" /></svg>
                    <span>Alert</span>
                </li>
                <li class="flex items-center space-x-2 cursor-pointer text-gray-500 hover:text-gray-300">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-12v6a2 2 0 01-2 2h-3m-4 0H9a2 2 0 01-2-2V6a2 2 0 012-2h1a2 2 0 012 2z" /></svg>
                    <span>Settings</span>
                </li>
            </ul>
        </nav>

        <!-- Main Content -->
        <main class="flex-1 p-6 space-y-6">
            <!-- Top summary cards -->
            <div class="flex space-x-6">
                <div class="bg-gray-800 rounded-lg p-4 w-1/4">
                    <p class="text-gray-400">Connected Users</p>
                    <p class="text-white font-bold text-2xl">41</p>
                </div>
                <div class="bg-gray-800 rounded-lg p-4 w-1/4">
                    <p class="text-gray-400">Active AP</p>
                    <p class="text-white font-bold text-2xl">12</p>
                </div>
                <div class="bg-gray-800 rounded-lg p-4 w-1/4">
                    <p class="text-gray-400">Total Bandwidth Today</p>
                    <p class="text-white font-bold text-2xl">134</p>
                </div>
            </div>

            <!-- Connected Users Table Card -->
            <div class="bg-gray-800 rounded-lg p-6">
                <h3 class="text-white font-semibold mb-4">Connected Users</h3>
                <table class="w-full text-left text-gray-300">
                    <thead>
                        <tr>
                            <th class="pb-2">Device Name</th>
                            <th class="pb-2">IP Address</th>
                            <th class="pb-2">On</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr class="border-b border-gray-700">
                            <td class="py-2">Fajar Setia</td>
                            <td class="py-2">192.168.100.1</td>
                            <td class="py-2 flex items-center space-x-2">
                                <span class="w-3 h-3 bg-green-500 rounded-full inline-block"></span>
                                <span>40 ms</span>
                            </td>
                        </tr>
                        <tr class="border-b border-gray-700">
                            <td class="py-2">Rafi Adam</td>
                            <td class="py-2">192.168.16.10</td>
                            <td class="py-2 flex items-center space-x-2">
                                <span class="w-3 h-3 bg-yellow-500 rounded-full inline-block"></span>
                                <span>65 ms</span>
                            </td>
                        </tr>
                        <tr class="border-b border-gray-700">
                            <td class="py-2">AP - 01</td>
                            <td class="py-2">192.1431.24</td>
                            <td class="py-2">
                                <svg xmlns="http://www.w3.org/2000/svg" class="inline-block h-5 w-5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h2v4H3v-4zm4-2h2v8H7v-8zm4-3h2v11h-2V5zm4 6h2v5h-2v-5z" />
                                </svg>
                            </td>
                        </tr>
                        <tr>
                            <td class="py-2">LifeMedia</td>
                            <td class="py-2">192.168.12.1</td>
                            <td class="py-2 flex items-center space-x-2">
                                <span class="w-3 h-3 bg-green-500 rounded-full inline-block"></span>
                                <span>20 ms</span>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Detail Table Card -->
            <div class="bg-gray-800 rounded-lg p-6 mt-4 overflow-x-auto">
                <table class="w-full text-left text-gray-300 min-w-max">
                    <thead>
                        <tr>
                            <th class="pb-2">Device Name</th>
                            <th class="pb-2">IP Address</th>
                            <th class="pb-2">MAC Address</th>
                            <th class="pb-2">Duration Connected</th>
                            <th class="pb-2">Signal Strength</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr class="border-b border-gray-700">
                            <td class="py-2">Fajar Setia</td>
                            <td class="py-2">192.168.100.1</td>
                            <td class="py-2">AA:BB:CC:DD:EE</td>
                            <td class="py-2">1h 23m</td>
                            <td class="py-2">
                                <svg xmlns="http://www.w3.org/2000/svg" class="inline-block h-5 w-5 text-gray-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M2 12h1M5 12h1M8 12h1M11 12h1M14 12h1M17 12h1M20 12h1" />
                                </svg>
                                2.4 GB
                            </td>
                        </tr>
                        <tr class="border-b border-gray-700">
                            <td class="py-2">Rafi Adam</td>
                            <td class="py-2">192.168.16.10</td>
                            <td class="py-2">AA:BB:CC:DD:E11</td>
                            <td class="py-2">35m</td>
                            <td class="py-2">
                                <svg xmlns="http://www.w3.org/2000/svg" class="inline-block h-5 w-5 text-gray-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M2 12h1M5 12h1M8 12h1M11 12h1M14 12h1M17 12h1M20 12h1" />
                                </svg>
                                1.2 GB
                            </td>
                        </tr>
                        <tr>
                            <td class="py-2">ACCES POINT - 01</td>
                            <td class="py-2">192.1431.24</td>
                            <td class="py-2">AA:BB:CC:DD:E2</td>
                            <td class="py-2">5h 10m</td>
                            <td class="py-2">
                                <svg xmlns="http://www.w3.org/2000/svg" class="inline-block h-5 w-5 text-gray-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M2 12h1M5 12h1M8 12h1M11 12h1M14 12h1M17 12h1M20 12h1" />
                                </svg>
                                25.4 GB
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </main>
    </div>
</x-app-layout>