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
<body class="min-h-screen bg-gray-900 font-sans text-white">

    <!-- Navigation Bar -->
    <nav class="w-full h-16 bg-gray-800 shadow-md flex items-center justify-between px-6 border-b border-gray-700">
        <div class="flex items-center">
            <i class="fas fa-wifi text-blue-400 text-2xl mr-2"></i>
            <h1 class="text-xl font-bold text-white">WiFi Monitor</h1>
        </div>
        <ul class="hidden md:flex space-x-6">
            <li><a href="#" class="text-gray-300 hover:text-blue-400 transition">Home</a></li>
            <li><a href="#" class="text-gray-300 hover:text-blue-400 transition">Features</a></li>
            <li><a href="#" class="text-gray-300 hover:text-blue-400 transition">About</a></li>
            <li><a href="#" class="text-gray-300 hover:text-blue-400 transition">Contact</a></li>
        </ul>
        <div class="flex items-center space-x-4">
            <a href="{{ route('login') }}" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition">Login</a>
            <button class="md:hidden text-gray-300" onclick="toggleMenu()">
                <i class="fas fa-bars"></i>
            </button>
        </div>
        <!-- Mobile Menu (hidden by default) -->
        <ul id="mobile-menu" class="md:hidden absolute top-16 left-0 w-full bg-gray-800 shadow-md hidden">
            <li><a href="#" class="block px-6 py-2 text-gray-300 hover:bg-gray-700">Home</a></li>
            <li><a href="#" class="block px-6 py-2 text-gray-300 hover:bg-gray-700">Features</a></li>
            <li><a href="#" class="block px-6 py-2 text-gray-300 hover:bg-gray-700">About</a></li>
            <li><a href="#" class="block px-6 py-2 text-gray-300 hover:bg-gray-700">Contact</a></li>
        </ul>
    </nav>

    <!-- Main Content (Hero Section) -->
    <div class="w-full min-h-[calc(100vh-4rem)] flex flex-col md:flex-row">
        <!-- Left Section: Hero Text and CTA -->
        <div class="w-full md:w-[50%] h-full bg-gradient-to-br from-blue-600 to-indigo-800 flex items-center justify-center p-8">
            <div class="text-center text-white">
                <h2 class="text-4xl md:text-5xl font-bold mb-4">Monitor Your WiFi Network Like a Pro</h2>
                <p class="text-lg md:text-xl mb-6">Real-time insights, alerts, and reports to keep your network secure and efficient. Built with Laravel for seamless performance.</p>
                <a href="{{ route('login') }}" class="bg-white text-blue-600 px-6 py-3 rounded-lg font-semibold hover:bg-gray-200 transition">Get Started</a>
            </div>
        </div>

        <!-- Right Section: Features Preview or Illustration -->
        <div class="w-full md:w-[50%] h-full bg-gray-800 flex items-center justify-center p-8">
            <div class="text-center">
                <i class="fas fa-chart-line text-6xl text-blue-400 mb-4"></i>
                <h3 class="text-2xl font-bold text-white mb-2">Key Features</h3>
                <ul class="text-gray-300 space-y-2">
                    <li><i class="fas fa-check-circle text-green-400 mr-2"></i>Real-time Monitoring</li>
                    <li><i class="fas fa-check-circle text-green-400 mr-2"></i>Automated Alerts</li>
                    <li><i class="fas fa-check-circle text-green-400 mr-2"></i>Detailed Reports</li>
                    <li><i class="fas fa-check-circle text-green-400 mr-2"></i>User-Friendly Dashboard</li>
                </ul>
            </div>
        </div>
    </div>

    <!-- Additional Section: Why Choose Us -->
    <section class="w-full py-16 bg-gray-900">
        <div class="max-w-6xl mx-auto px-6">
            <h2 class="text-3xl font-bold text-center text-white mb-8">Why Choose WiFi Monitor?</h2>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                <div class="text-center p-6 bg-gray-800 rounded-lg shadow-md">
                    <i class="fas fa-shield-alt text-4xl text-blue-400 mb-4"></i>
                    <h3 class="text-xl font-semibold text-white mb-2">Secure & Reliable</h3>
                    <p class="text-gray-300">Advanced security features to protect your network data.</p>
                </div>
                <div class="text-center p-6 bg-gray-800 rounded-lg shadow-md">
                    <i class="fas fa-rocket text-4xl text-blue-400 mb-4"></i>
                    <h3 class="text-xl font-semibold text-white mb-2">Fast & Efficient</h3>
                    <p class="text-gray-300">Powered by Laravel for high performance and scalability.</p>
                </div>
                <div class="text-center p-6 bg-gray-800 rounded-lg shadow-md">
                    <i class="fas fa-users text-4xl text-blue-400 mb-4"></i>
                    <h3 class="text-xl font-semibold text-white mb-2">Easy to Use</h3>
                    <p class="text-gray-300">Intuitive interface designed with Tailwind CSS for a modern look.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="w-full bg-gray-800 text-white py-8">
        <div class="max-w-6xl mx-auto px-6 text-center">
            <p>&copy; 2023 WiFi Monitor. All rights reserved. Built with Laravel & Tailwind CSS.</p>
            <div class="mt-4 space-x-4">
                <a href="#" class="hover:text-blue-400"><i class="fab fa-facebook"></i></a>
                <a href="#" class="hover:text-blue-400"><i class="fab fa-twitter"></i></a>
                <a href="#" class="hover:text-blue-400"><i class="fab fa-linkedin"></i></a>
            </div>
        </div>
    </footer>

    <script>
        function toggleMenu() {
            const menu = document.getElementById('mobile-menu');
            menu.classList.toggle('hidden');
        }
    </script>

</body>
</html>