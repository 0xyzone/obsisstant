<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Screen</title>
    @livewireStyles
    <!-- Scripts -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="font-sans antialiased">
    <div {{ $attributes->merge(['class' => 'w-[1920px] h-[1080px]']) }}>
        {{ $slot }}
    </div>
    @livewireScripts
</body>
</html>
