<nav class="fixed left-0 top-0 h-screen bg-gradient-to-b from-gray-900 via-gray-900 to-gray-800 border-r border-gray-700/50 flex flex-col transition-all duration-300 z-50 shadow-2xl"
    :class="{
        'w-64 translate-x-0': sidebarOpen,
        'w-20 translate-x-0': !sidebarOpen,
    }">

    <!-- Logo Section & Toggle Button -->
    <div class="shrink-0 flex items-center justify-between p-6 border-b border-gray-700/50">
        <a href="{{ route('dashboard') }}" :class="sidebarOpen ? 'block' : 'hidden'" class="transition-all duration-300 w-32 ">
            <img src="{{ asset('images/logo.png') }}" alt="logo" srcset="">
        </a>

        <!-- Toggle Button - Hidden on mobile -->
        <button @click="sidebarOpen = !sidebarOpen"
            class="hidden lg:block text-gray-400 hover:text-white hover:bg-gray-800 p-2 rounded-lg transition-all duration-200 ml-auto">
            <svg class="w-5 h-5 transition-transform duration-300" :class="sidebarOpen ? '' : 'rotate-180'" fill="none"
                stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M11 19l-7-7 7-7m8 14l-7-7 7-7" />
            </svg>
        </button>

        <!-- Mobile Menu Button -->
        <button @click="sidebarOpen = !sidebarOpen"
            class="lg:hidden text-gray-400 hover:text-white hover:bg-gray-800 p-2 rounded-lg transition-all duration-200 ml-auto">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path x-show="!sidebarOpen" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M4 6h16M4 12h16M4 18h16" />
                <path x-show="sidebarOpen" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M6 18L18 6M6 6l12 12" />
            </svg>
        </button>
    </div>

    <!-- Navigation Links -->
    <div class="flex-1 px-3 py-6 space-y-1 overflow-y-auto custom-scrollbar">
        <!-- Overview -->
        <a href="{{ route('dashboard') }}"
            class="relative rounded-lg px-4 py-3 flex items-center space-x-3 transition-all duration-200 group {{ request()->routeIs('dashboard') ? 'bg-gradient-to-r from-blue-600 to-blue-700 text-white shadow-lg shadow-blue-500/30' : 'text-gray-300 hover:bg-gray-800 hover:text-white' }}"
            :class="sidebarOpen ? '' : 'justify-center'">
            <svg class="w-5 h-5 flex-shrink-0 transition-transform duration-200 group-hover:scale-110" fill="none"
                stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
            </svg>
            <span :class="sidebarOpen ? 'inline' : 'hidden'" class="font-medium">{{ __('Overview') }}</span>
            <span x-show="!sidebarOpen"
                class="absolute left-20 px-3 py-1.5 ml-6 text-xs font-semibold text-white bg-gray-900 rounded-lg opacity-0 group-hover:opacity-100 transition-opacity whitespace-nowrap shadow-xl border border-gray-700">Overview</span>
        </a>

        <!-- Access Point -->
        <a href="{{ route('access-point') }}"
            class="relative rounded-lg px-4 py-3 flex items-center space-x-3 transition-all duration-200 group {{ request()->routeIs('access-point') ? 'bg-gradient-to-r from-blue-600 to-blue-700 text-white shadow-lg shadow-blue-500/30' : 'text-gray-300 hover:bg-gray-800 hover:text-white' }}"
            :class="sidebarOpen ? '' : 'justify-center'">
            <svg class="w-5 h-5 flex-shrink-0 transition-transform duration-200 group-hover:scale-110" fill="none"
                stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M8.111 16.404a5.5 5.5 0 017.778 0M12 20h.01m-7.08-7.071c3.904-3.905 10.236-3.905 14.141 0M1.394 9.393c5.857-5.857 15.355-5.857 21.213 0" />
            </svg>
            <span :class="sidebarOpen ? 'inline' : 'hidden'" class="font-medium">{{ __('Access Point') }}</span>
            <span x-show="!sidebarOpen"
                class="absolute left-20 px-3 py-1.5 ml-6 text-xs font-semibold text-white bg-gray-900 rounded-lg opacity-0 group-hover:opacity-100 transition-opacity whitespace-nowrap shadow-xl border border-gray-700">Access
                Point</span>
        </a>

        <!-- Connected Users -->
        <a href="{{ route('connectUser') }}"
            class="relative rounded-lg px-4 py-3 flex items-center space-x-3 transition-all duration-200 group {{ request()->routeIs('connectUser') ? 'bg-gradient-to-r from-blue-600 to-blue-700 text-white shadow-lg shadow-blue-500/30' : 'text-gray-300 hover:bg-gray-800 hover:text-white' }}"
            :class="sidebarOpen ? '' : 'justify-center'">
            <svg class="w-5 h-5 flex-shrink-0 transition-transform duration-200 group-hover:scale-110" fill="none"
                stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
            </svg>
            <span :class="sidebarOpen ? 'inline' : 'hidden'" class="font-medium">{{ __('Connected Users') }}</span>
            <span x-show="!sidebarOpen"
                class="absolute left-20 px-3 py-1.5 ml-6 text-xs font-semibold text-white bg-gray-900 rounded-lg opacity-0 group-hover:opacity-100 transition-opacity whitespace-nowrap shadow-xl border border-gray-700">Connected
                Users</span>
        </a>

        <!-- Alert -->

        <a href="{{ route('alert') }}"
            class="text-gray-300 hover:bg-gray-800 hover:text-white rounded-lg px-4 py-3 flex items-center space-x-3 transition group {{ request()->routeIs('alert') ? 'bg-gradient-to-r from-blue-600 to-blue-700 text-white shadow-lg shadow-blue-500/30' : 'text-gray-300 hover:bg-gray-800 hover:text-white' }}"
            :class="sidebarOpen ? '' : 'justify-center'">
            <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
            </svg>
            <span :class="sidebarOpen ? 'inline' : 'hidden'" class="font-medium">{{ __('Alert') }}</span>
            <span :class="sidebarOpen ? 'ml-auto' : 'absolute -top-1 -right-1'"
                class="bg-gradient-to-r from-red-500 to-red-600 text-white text-xs font-bold px-2 py-0.5 rounded-full shadow-lg shadow-red-500/50 animate-pulse">
                {{ $alertCount }}
            </span>
            <span x-show="!sidebarOpen"
                class="absolute left-20 px-3 py-1.5 ml-6 text-xs font-semibold text-white bg-gray-900 rounded-lg opacity-0 group-hover:opacity-100 transition-opacity whitespace-nowrap shadow-xl border border-gray-700">Alert</span>
        </a>
    </div>

    <!-- Divider -->
    <div class="border-t border-gray-700/50"></div>

    <!-- Settings Section -->
    <div class="px-3 py-4">
        <a href="/profile"
            class="relative rounded-lg px-4 py-3 flex items-center space-x-3 transition-all duration-200 group text-gray-300 hover:bg-gray-800 hover:text-white"
            :class="sidebarOpen ? '' : 'justify-center'">
            <svg class="w-5 h-5 flex-shrink-0 transition-transform duration-200 group-hover:rotate-90" fill="none"
                stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
            </svg>
            <span :class="sidebarOpen ? 'inline' : 'hidden'" class="font-medium">{{ __('Settings') }}</span>
            <span x-show="!sidebarOpen"
                class="absolute left-20 px-3 py-1.5 ml-6 text-xs font-semibold text-white bg-gray-900 rounded-lg opacity-0 group-hover:opacity-100 transition-opacity whitespace-nowrap shadow-xl border border-gray-700">Settings</span>
        </a>
    </div>

    <!-- User Profile Section (Bottom) -->
    <div class="border-t border-gray-700/50 p-4 bg-gray-800/30">
        <div class="flex items-center" :class="sidebarOpen ? 'justify-between' : 'justify-center'">
            <div :class="sidebarOpen ? 'flex items-center space-x-3' : 'hidden'"
                class="flex items-center space-x-3 flex-1 min-w-0">
                <div
                    class="w-10 h-10 bg-gradient-to-br from-blue-500 via-purple-500 to-pink-500 rounded-full flex items-center justify-center flex-shrink-0 shadow-lg ring-2 ring-gray-700">
                    <span class="text-white font-bold text-sm">{{ substr(Auth::user()->name, 0, 1) }}</span>
                </div>
                <div class="flex-1 min-w-0">
                    <p class="text-sm font-semibold text-white truncate">{{ Auth::user()->name }}</p>
                    <p class="text-xs text-gray-400 truncate">{{ Auth::user()->email }}</p>
                </div>
            </div>

            <!-- Collapsed Avatar -->
            <div x-show="!sidebarOpen"
                class="w-10 h-10 bg-gradient-to-br from-blue-500 via-purple-500 to-pink-500 rounded-full flex items-center justify-center flex-shrink-0 shadow-lg ring-2 ring-gray-700">
                <span class="text-white font-bold text-sm">{{ substr(Auth::user()->name, 0, 1) }}</span>
            </div>

            <!-- Logout Button -->
            <form method="POST" action="{{ route('logout') }}" class="inline">
                @csrf
                <button type="submit"
                    class="text-gray-400 hover:text-red-500 hover:bg-red-500/10 p-2 rounded-lg transition-all duration-200 flex-shrink-0"
                    :class="sidebarOpen ? 'ml-2' : ''" title="Logout">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                    </svg>
                </button>
            </form>
        </div>
    </div>
</nav>

<!-- Mobile Overlay -->
<div x-show="sidebarOpen" @click="sidebarOpen = false"
    x-transition:enter="transition-opacity ease-linear duration-300" x-transition:enter-start="opacity-0"
    x-transition:enter-end="opacity-100" x-transition:leave="transition-opacity ease-linear duration-300"
    x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
    class="fixed inset-0 bg-black/50 z-40 lg:hidden"></div>

<style>
    .custom-scrollbar::-webkit-scrollbar {
        width: 6px;
    }

    .custom-scrollbar::-webkit-scrollbar-track {
        background: transparent;
    }

    .custom-scrollbar::-webkit-scrollbar-thumb {
        background: #374151;
        border-radius: 3px;
    }

    .custom-scrollbar::-webkit-scrollbar-thumb:hover {
        background: #4b5563;
    }
</style>
