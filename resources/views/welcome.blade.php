<html><head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Obsisstant - Esports Streaming Asset Organizer</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-gray-900 text-white min-h-screen flex flex-col items-center justify-center lg:justify-normal">
    <div class="container mx-auto px-4 text-center">
        <!-- Logo -->
        <img src="{{ asset('mainLogo.png') }}" alt="Logo" class="logo max-w-64 lg:max-w-2xl mx-auto">

        <!-- Heading -->
        <h1 class="text-5xl font-bold mb-4">Streamline Your Esports Broadcasts</h1>

        <!-- Subheading -->
        <p class="text-xl mb-8">Organize, manage, and elevate your tournament streaming assets with ease</p>

        <!-- CTA Button -->
        <a href="{{ route('filament.admin.pages.dashboard') }}" class="bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-3 px-6 rounded-full text-lg transition duration-300 ease-in-out transform hover:scale-105">
            Get Started
        </a>
    </div>

    <script>
        // Add a simple animation to the logo
        const logo = document.querySelector('.logo');
        logo.style.transition = 'transform 0.3s ease-in-out';
        logo.addEventListener('mouseover', () => {
            logo.style.transform = 'scale(1.1) rotate(5deg)';
        });
        logo.addEventListener('mouseout', () => {
            logo.style.transform = 'scale(1) rotate(0deg)';
        });

        // Add a pulsing effect to the CTA button
        const ctaButton = document.querySelector('a');
        setInterval(() => {
            ctaButton.classList.add('animate-pulse');
            setTimeout(() => {
                ctaButton.classList.remove('animate-pulse');
            }, 1000);
        }, 3000);
    </script>
</body>
</html>