<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'Laravel') }}</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

    <!-- Scripts -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<style>
    .video-bg {
    width: 100vw;
    height: 100vh;
    object-fit: cover;
    transform: translateZ(0);
    image-rendering: crisp-edges;
    backface-visibility: hidden;
    will-change: transform;
}
</style>

<body class="relative min-h-screen m-0 p-0 overflow-hidden font-sans text-gray-900 antialiased">

    <!-- Video background -->
    <video autoplay muted loop playsinline class="video-bg fixed top-0 left-0 z-0">
        <source src="{{ asset('videos/bgAnimation.mp4') }}" type="video/mp4">
    </video>

    <!-- Overlay optional -->
    <div class="fixed inset-0 bg-black/10"></div>

    <!-- Content wrapper -->
    <div class="relative min-h-screen flex flex-col sm:justify-center items-center pt-6 sm:pt-0">
        <div
            class="w-full sm:max-w-md mt-6 px-8 py-6
            bg-white/10 backdrop-blur-lg border border-white/10 shadow-xl sm:rounded-2xl">
            {{ $slot }}
        </div>
    </div>

</body>




</body>

</html>
