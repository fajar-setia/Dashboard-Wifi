<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'Dashboard ACS') }}</title>

    <link rel="icon" type="image/png" sizes="64x64" href="{{ asset('images/logo_pisah.png') }}">


    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

    <!-- Scripts -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="font-sans antialiased">
    
    <!-- Background with gradient -->
    <div class="min-h-screen relative overflow-hidden bg-gradient-to-l from-white via-zinc-800 to-white">
        
        <!-- Animated Background Blobs -->
        <div class="absolute top-0 right-0 w-96 h-96 bg-purple-400 rounded-full mix-blend-multiply filter blur-3xl opacity-30 animate-blob"></div>
        <div class="absolute top-0 left-0 w-96 h-96 bg-violet-400 rounded-full mix-blend-multiply filter blur-3xl opacity-30 animate-blob animation-delay-2000"></div>
        <div class="absolute bottom-0 left-1/2 w-96 h-96 bg-blue-400 rounded-full mix-blend-multiply filter blur-3xl opacity-30 animate-blob animation-delay-4000"></div>
        
        <!-- Main Container -->
        <div class="relative min-h-screen flex flex-col sm:justify-center items-center pt-6 sm:pt-0 px-4">
            
            <!-- Logo Section -->
            
            
            <!-- Card Container -->
            <div class="w-full sm:max-w-md opacity-0 animate-fade-in-up animation-delay-200">
                <div class="bg-white/40 backdrop-blur-lg border border-white/20 rounded-3xl shadow-2xl p-8 sm:p-10 transform hover:scale-[1.02] transition-all duration-300">
                    {{ $slot }}
                </div>
            </div>
            
            
        </div>
    </div>

</body>

</html>
