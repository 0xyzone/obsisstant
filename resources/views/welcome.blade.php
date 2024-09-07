<html><head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Obsisstant - Esports Streaming Asset Organizer</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-gray-900 text-white min-h-screen flex flex-col justify-center items-center">
    <div class="container mx-auto px-4 text-center">
        <!-- Logo -->
        <svg class="w-48 h-48 mx-auto mb-8" viewBox="0 0 200 200" xmlns="http://www.w3.org/2000/svg">
            <circle cx="100" cy="100" r="90" fill="#4F46E5"/>
            <path d="M70 70 L130 70 L100 130 Z" fill="white"/>
            <text x="100" y="160" font-family="Arial" font-size="24" fill="white" text-anchor="middle">Obsisstant</text>
        </svg>

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
        const logo = document.querySelector('svg');
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