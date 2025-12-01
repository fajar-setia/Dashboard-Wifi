<nav class="fixed left-0 top-0 h-screen bg-gray-900 border-r border-gray-700 flex flex-col transition-all duration-300" :class="sidebarOpen ? 'w-64' : 'w-20'">
    
    <!-- Logo Section & Toggle Button -->
    <div class="shrink-0 flex items-center justify-between p-6 border-b border-gray-700">
        <a href="{{ route('dashboard') }}" :class="sidebarOpen ? 'block' : 'hidden'">
            <x-application-logo class="block h-9 w-auto fill-current text-white" />
        </a>
        
        <!-- Toggle Button -->
        <button @click="sidebarOpen = !sidebarOpen" class="text-gray-400 hover:text-white transition ml-auto">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
            </svg>
        </button>
    </div>

    <!-- Navigation Links -->
    <div class="flex-1 px-4 py-6 space-y-2 overflow-y-auto">
        <!-- Overview -->
        <a href="{{ route('dashboard') }}" class="text-gray-300 hover:bg-gray-800 hover:text-white rounded-lg px-4 py-3 flex items-center space-x-3 transition group {{ request()->routeIs('dashboard') ? 'bg-gray-800 text-white' : 'text-gray-300 hover:bg-gray-800 hover:text-white' }}" :class="sidebarOpen ? '' : 'justify-center'">
            <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-3m0 0l7-4 7 4M5 9v10a1 1 0 001 1h12a1 1 0 001-1V9m-9 11l4-4m0 0l4 4m-4-4v4" />
            </svg>
            <span :class="sidebarOpen ? 'inline' : 'hidden'">{{ __('Overview') }}</span>
            <span x-show="!sidebarOpen" class="absolute left-20 px-2 py-1 ml-6 text-xs font-bold text-white bg-gray-800 rounded opacity-0 group-hover:opacity-100 transition-opacity whitespace-nowrap">Overview</span>
        </a>

        <!-- Access Point -->
        <a href="{{ route('access-point') }}" class="text-gray-300 hover:bg-gray-800 hover:text-white rounded-lg px-4 py-3 flex items-center space-x-3 transition group {{ request()->routeIs('access-point') ? 'bg-gray-800 text-white' : 'text-gray-300 hover:bg-gray-800 hover:text-white' }}" :class="sidebarOpen ? '' : 'justify-center'">
            <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.111 16.251a.999.999 0 001.414 0l7.07-7.07m0 0a1 1 0 10-1.414-1.414L12 12.414l-6.293-6.293a1 1 0 00-1.414 1.414l7.07 7.07z" />
            </svg>
            <span :class="sidebarOpen ? 'inline' : 'hidden'">{{ __('Access Point') }}</span>
            <span x-show="!sidebarOpen" class="absolute left-20 px-2 py-1 ml-6 text-xs font-bold text-white bg-gray-800 rounded opacity-0 group-hover:opacity-100 transition-opacity whitespace-nowrap">Access Point</span>
        </a>

        <!-- Connected Users -->
        <a href="{{ route('connectUser') }}" class="text-gray-300 hover:bg-gray-800 hover:text-white rounded-lg px-4 py-3 flex items-center space-x-3 transition group {{ request()->routeIs('connectUser') ? 'bg-gray-800 text-white' : 'text-gray-300 hover:bg-gray-800 hover:text-white' }}" :class="sidebarOpen ? '' : 'justify-center'">
            <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 8.048M7 14H5m14 0h-2m-5-5h2m0 0h2m-2 0v2m0-2v-2m0 2a4 4 0 11-8 0m12-4h-2a2 2 0 00-2 2v2a2 2 0 002 2h2m0-6.5V9m0 3v2.5" />
            </svg>
            <span :class="sidebarOpen ? 'inline' : 'hidden'">{{ __('Connected Users') }}</span>
            <span x-show="!sidebarOpen" class="absolute left-20 px-2 py-1 ml-6 text-xs font-bold text-white bg-gray-800 rounded opacity-0 group-hover:opacity-100 transition-opacity whitespace-nowrap">Connected Users</span>
        </a>

        <!-- Alert -->
        <a href="#" class="text-gray-300 hover:bg-gray-800 hover:text-white rounded-lg px-4 py-3 flex items-center space-x-3 transition group relative" :class="sidebarOpen ? '' : 'justify-center'">
            <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
            </svg>
            <span :class="sidebarOpen ? 'inline' : 'hidden'">{{ __('Alert') }}</span>
            <span :class="sidebarOpen ? 'absolute right-4' : 'absolute -top-2 -right-2'" class="bg-red-500 text-white text-xs font-bold px-2 py-0.5 rounded-full">3</span>
            <span x-show="!sidebarOpen" class="absolute left-20 px-2 py-1 ml-6 text-xs font-bold text-white bg-gray-800 rounded opacity-0 group-hover:opacity-100 transition-opacity whitespace-nowrap">Alert</span>
        </a>
    </div>

    <!-- Divider -->
    <div class="border-t border-gray-700"></div>

    <!-- Settings Section -->
    <div class="px-4 py-4">
        <a href="/profile" class="text-gray-300 hover:bg-gray-800 hover:text-white rounded-lg px-4 py-3 flex items-center space-x-3 transition group" :class="sidebarOpen ? '' : 'justify-center'">
            <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
            </svg>
            <span :class="sidebarOpen ? 'inline' : 'hidden'">{{ __('setting') }}</span>
            <span x-show="!sidebarOpen" class="absolute left-20 px-2 py-1 ml-6 text-xs font-bold text-white bg-gray-800 rounded opacity-0 group-hover:opacity-100 transition-opacity whitespace-nowrap">Settings</span>
        </a>
    </div>

    <!-- User Profile Section (Bottom) -->
    <div class="border-t border-gray-700 p-4">
        <div class="flex items-center" :class="sidebarOpen ? 'justify-between' : 'justify-center'">
            <div :class="sidebarOpen ? 'flex items-center space-x-3' : 'hidden'" class="flex items-center space-x-3">
                <div class="w-10 h-10 bg-gradient-to-br from-blue-400 to-purple-500 rounded-full flex items-center justify-center flex-shrink-0">
                    <span class="text-white font-bold text-sm">{{ substr(Auth::user()->name, 0, 1) }}</span>
                </div>
                <div class="flex-1 min-w-0">
                    <p class="text-sm font-medium text-white truncate">{{ Auth::user()->name }}</p>
                    <p class="text-xs text-gray-400 truncate">{{ Auth::user()->email }}</p>
                </div>
            </div>
            
            <!-- Collapsed Avatar -->
            <div x-show="!sidebarOpen" class="w-10 h-10 bg-gradient-to-br from-blue-400 to-purple-500 rounded-full flex items-center justify-center flex-shrink-0">
                <span class="text-white font-bold text-sm">{{ substr(Auth::user()->name, 0, 1) }}</span>
            </div>
            
            <!-- Logout Button -->
            <form method="POST" action="{{ route('logout') }}" class="inline">
                @csrf
                <button type="submit" class="text-gray-400 hover:text-red-500 transition flex-shrink-0" :class="sidebarOpen ? 'ml-auto' : ''">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                    </svg>
                </button>
            </form>
        </div>
    </div>
</nav>

<!-- Main Content Area - Adjust margin based on sidebar state -->
{{-- <div :class="sidebarOpen ? 'ml-64' : 'ml-20'" class="transition-all duration-300">
    <!-- Your main content goes here -->
</div> --}}