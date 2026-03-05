<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Guard Scanner – Ozamiz Schools QR-ID System</title>
    @vite(['resources/css/app.css', 'resources/js/guard-scanner.js'])
</head>
<body class="bg-slate-900">
    <div id="app"></div>
</body>
</html>
