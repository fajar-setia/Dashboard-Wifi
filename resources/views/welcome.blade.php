<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>WiFi Monitoring Dashboard - Landing Page</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- Tambahkan Font Awesome untuk ikon (opsional, jika diperlukan) -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>

<body class=" bg-gray-900 font-sans text-white">
    <div class="flex items-center justify-center h-screen">
        <div class="flex flex-col items-center justify-center bg-transparent  backdrop-blur-2xl px-32 py-20 rounded-2xl shadow-xl shadow-black/30 border border-black-1.5">

            <img src="{{ asset('images/ilustratorMonitor.jpg') }}" alt="ilustrator" class="w-72 mb-6 rounded-lg">

            <div class="flex items-center justify-center">
                <a href="{{ route('login') }}"
                    class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition">Masuk</a>
                <button class="md:hidden text-gray-300 ml-3" onclick="toggleMenu()">
                    <i class="fas fa-bars"></i>
                </button>
            </div>

        </div>
    </div>




    <script>
        function toggleMenu() {
            const menu = document.getElementById('mobile-menu');
            menu.classList.toggle('hidden');
        }
    </script>

</body>

</html>
