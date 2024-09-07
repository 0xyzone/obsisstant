<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Obsisstant</title>
    {{-- <link href="https://cdn.jsdelivr.net/npm/tailwindcss@3.3.0/dist/tailwind.min.css" rel="stylesheet"> --}}
    <style>
        body {
            background-color: #1a202c; /* Dark background color */
        }
    </style>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="flex items-center justify-center min-h-screen text-white">
    <div class="text-center max-w-7xl">
        <h1 class="text-9xl font-extrabold mb-4">Obsisstant</h1>
        <p class="text-5xl mb-6">Streamline Your Esports Tournaments with Ease</p>
        <p class="text-md mb-8">Welcome to Obsisstant, the ultimate tool designed for esports tournament organizers. Simplify data management, effortlessly integrate with OBS through powerful APIs, and enhance your streaming experience. Ready to take your tournaments to the next level?</p>
        <a href="{{ route('filament.admin.pages.dashboard') }}" class="bg-violet-600 hover:bg-violet-700 text-white font-semibold py-3 px-6 rounded-lg transition duration-300">Get Started</a>
    </div>
</body>
</html>
