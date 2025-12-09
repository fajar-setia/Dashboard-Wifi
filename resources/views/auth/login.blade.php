<x-guest-layout>
    <!-- Header -->
    <div class="text-center mb-8 opacity-0 animate-fade-in animation-delay-300">
        <h1 class="text-3xl font-bold text-white mb-2">Welcome Back!</h1>
        <p class="text-violet-200">Sign in to continue monitoring user acs</p>
    </div>

    <!-- Session Status -->
    <x-auth-session-status class="mb-4 bg-green-500/20 border border-green-400 text-green-100 px-4 py-3 rounded-xl text-sm" :status="session('status')" />

    <form method="POST" action="{{ route('login') }}" class="space-y-6">
        @csrf

        <!-- Email Address -->
        <div class="opacity-0 animate-fade-in animation-delay-400">
            <label for="email" class="block text-sm font-medium text-white mb-2">
                Email Address
            </label>
            <div class="relative group">
                <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                    <svg class="w-5 h-5 text-zinc-600 group-focus-within:text-black transition-colors duration-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 12a4 4 0 10-8 0 4 4 0 008 0zm0 0v1.5a2.5 2.5 0 005 0V12a9 9 0 10-9 9m4.5-1.206a8.959 8.959 0 01-4.5 1.207"></path>
                    </svg>
                </div>
                <input 
                    id="email" 
                    type="email" 
                    name="email" 
                    value="{{ old('email') }}"
                    required 
                    autofocus
                    autocomplete="username"
                    placeholder="Enter your email"
                    class="block w-full pl-12 pr-4 py-3.5 bg-white/10 border border-white/20 rounded-xl text-black placeholder-violet-300 focus:outline-none focus:ring-4 focus:ring-black/30 focus:border-white focus:bg-white/20 transition-all duration-300"
                />
            </div>
            <x-input-error :messages="$errors->get('email')" class="mt-2 text-red-200 text-sm" />
        </div>

        <!-- Password -->
        <div class="opacity-0 animate-fade-in animation-delay-500">
            <label for="password" class="block text-sm font-medium text-white mb-2">
                Password
            </label>
            <div class="relative group">
                <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                    <svg class="w-5 h-5 text-zinc-600 group-focus-within:text-black transition-colors duration-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
                    </svg>
                </div>
                <input 
                    id="password" 
                    type="password" 
                    name="password" 
                    required
                    autocomplete="current-password"
                    placeholder="Enter your password"
                    class="block w-full pl-12 pr-4 py-3.5 bg-white/10 border border-white/20 rounded-xl text-black placeholder-violet-300 focus:outline-none focus:ring-4 focus:ring-black/30 focus:border-white focus:bg-white/20 transition-all duration-300"
                />
            </div>
            <x-input-error :messages="$errors->get('password')" class="mt-2 text-red-200 text-sm" />
        </div>

        <!-- Remember Me & Forgot Password -->
        <div class="flex items-center justify-between opacity-0 animate-fade-in animation-delay-600">
            <label for="remember_me" class="inline-flex items-center cursor-pointer group">
                <input 
                    id="remember_me" 
                    type="checkbox" 
                    class="rounded border-white/30 bg-white/10 text-black shadow-sm focus:ring-zinc-800 focus:ring-offset-0 transition-all duration-300 cursor-pointer" 
                    name="remember"
                >
                <span class="ms-2 text-sm text-white group-hover:text-violet-200 transition-colors">Remember me</span>
            </label>

            @if (Route::has('password.request'))
                <a class="text-sm text-white hover:text-violet-200 transition-colors underline underline-offset-4" href="{{ route('password.request') }}">
                    Forgot password?
                </a>
            @endif
        </div>

        <!-- Submit Button -->
        <div class="opacity-0 animate-fade-in animation-delay-700">
            <button type="submit" class="group relative w-full flex justify-center py-3.5 px-4 border border-transparent text-base font-semibold rounded-xl text-black bg-white hover:bg-violet-50 focus:outline-none focus:ring-4 focus:ring-white/30 transform hover:scale-[1.02] active:scale-[0.98] transition-all duration-300 shadow-xl">
                <span class="absolute left-0 inset-y-0 flex items-center pl-4">
                    <svg class="h-5 w-5 text-black group-hover:text-zinc-800 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1"></path>
                    </svg>
                </span>
                Sign in to your account
            </button>
        </div>

        <!-- Divider -->
       
    </form>
</x-guest-layout>