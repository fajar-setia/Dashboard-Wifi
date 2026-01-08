<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name', 'Laravel') }}</title>

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />
    <link rel="preload" as="image" href="{{ asset('images/logo.png') }}">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<style>
    [x-cloak] {
        display: none !important;
    }
</style>

<body class="font-sans antialiased bg-gray-900" x-data="{ sidebarOpen: true }">

    <div class="flex min-h-screen">

        @include('layouts.navigation')

        <main class="flex-1 transition-all duration-300"
              :class="sidebarOpen ? 'ml-64' : 'ml-20'">
            <div class="p-4 sm:p-6 lg:p-8">
                {{ $slot }}
            </div>
        </main>

    </div>

</body>


</html>