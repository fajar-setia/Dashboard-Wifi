<x-app-layout>
    <div class="flex bg-gray-900 min-h-screen text-gray-300">

        <!-- Main Content -->
        <main class="flex-1 p-6 space-y-6">

            <!-- Header -->
            <h1 class="text-white font-bold text-2xl mb-4">Connected Users</h1>

            <!-- Grid Cards -->
            <div class="grid grid-cols-1 xl:grid-cols-[30%_70%] gap-6">

                <div class="space-y-4">

                    @foreach ($aps as $ap)
                        <div class="grid grid-cols-10 bg-gray-800 p-4 rounded-lg text-white">

                            <!-- 30% -->
                            <div class="col-span-3 space-y-1">
                                <p class="font-semibold text-lg">{{ $ap['sn'] }}</p>
                                <p class="text-sm text-gray-400">Model : {{ $ap['model'] }}</p>
                                <p class="text-sm {{ $ap['state'] == 'online' ? 'text-green-400' : 'text-red-400' }}">
                                    {{ ucfirst($ap['state']) }}
                                </p>
                            </div>

                            <!-- 70% -->
                            <div class="col-span-7 flex items-center justify-between">
                                {{-- <div>
                                    <p class="text-sm text-gray-300">Connected Users</p>
                                    <p class="text-2xl font-bold">{{ $ap['connected'] }}</p>
                                </div>

                                <div
                                    class="h-3 w-3 rounded-full {{ $ap['status'] == 'Online' ? 'bg-green-500' : 'bg-red-500' }}">
                                </div> --}}
                            </div>

                        </div>
                    @endforeach

                </div>


            </div>

        </main>

    </div>
</x-app-layout>
