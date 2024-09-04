
<html>
<head>
    <title>OBS Control</title>
</head>
<body>
    <button id="start">Start Streaming</button>
    <button id="stop">Stop Streaming</button>

    <script>
        document.getElementById('start').addEventListener('click', function() {
            fetch('/start-streaming').then(response => response.json()).then(data => {
                console.log(data);
            });
        });

        document.getElementById('stop').addEventListener('click', function() {
            fetch('/stop-streaming').then(response => response.json()).then(data => {
                console.log(data);
            });
        });
    </script>
</body>
</html>
