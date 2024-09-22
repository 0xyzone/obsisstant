<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <link rel="stylesheet" href="{{ asset('css/all.css') }}">
    <title>Screen</title>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
    <style>
        /* Loading spinner styles */
        .loading-spinner {
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            display: none;
        }
        .loading-spinner div {
            width: 50px;
            height: 50px;
            border: 5px solid rgba(0, 0, 0, 0.1);
            border-left-color: #000;
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }
        @keyframes spin {
            to { transform: rotate(360deg); }
        }
    </style>
    <!-- Scripts -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="font-sans antialiased">
    <div class="w-[1080px] h-[1080px]" id="capture">
        {{ $slot }}
    </div>
    <div class="loading-spinner" id="loadingSpinner">
        <div></div>
    </div>
    <script>
        var isDownloading = false; // Flag to prevent multiple clicks

        document.getElementById('capture').addEventListener('click', function() {
            if (isDownloading) return; // Prevent if already downloading

            isDownloading = true; // Set flag to true
            document.getElementById('loadingSpinner').style.display = 'block'; // Show loading spinner

            var element = document.getElementById('capture');

            // Use html2canvas to capture the div as a canvas
            html2canvas(element, {
                backgroundColor: null
            }).then(function(canvas) {
                // Convert the canvas to an image (data URL)
                var imgData = canvas.toDataURL('image/png');

                // Create an invisible link element to trigger the download
                var link = document.createElement('a');
                link.href = imgData;
                link.download = 'output.png'; // Fixed the filename

                // Append the link, trigger click, and remove it from the DOM
                document.body.appendChild(link);
                link.click();
                document.body.removeChild(link);

                isDownloading = false; // Reset flag
                document.getElementById('loadingSpinner').style.display = 'none'; // Hide loading spinner
            }).catch(function(error) {
                console.error('Error capturing the element:', error);
                isDownloading = false; // Reset flag in case of error
                document.getElementById('loadingSpinner').style.display = 'none'; // Hide loading spinner
            });
        });
    </script>
</body>
</html>
