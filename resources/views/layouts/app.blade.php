<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" x-data="{
        sidebarOpen: false,
        darkMode: localStorage.getItem('theme') === 'dark' || (localStorage.getItem('theme') === null && window.matchMedia('(prefers-color-scheme: dark)').matches),
        toggleDark() {
            this.darkMode = !this.darkMode;
            localStorage.setItem('theme', this.darkMode ? 'dark' : 'light');
        }
    }" :class="{ 'dark': darkMode }">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name', 'Wifi Dashboard') }}</title>

    <link rel="icon" type="image/png" sizes="64x64" href="{{ asset('images/logo_pisah.png') }}">
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />
    <link rel="preload" as="image" href="{{ asset('images/lifemedia-128.webp') }}" type="image/webp">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<style>
    [x-cloak] {
        display: none !important;
    }
</style>

<body class="font-sans antialiased bg-gray-100 dark:bg-gray-900 transition-colors duration-300">

    <div class="flex min-h-screen">

        @include('layouts.navigation')

        <main class="flex-1 transition-all duration-300" :class="sidebarOpen ? 'ml-64' : 'ml-20'">
            <div class="p-4 sm:p-6 lg:p-8">
                {{ $slot }}
            </div>
        </main>

    </div>

</body>


</html>