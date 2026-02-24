<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Admin Dashboard – ScanUp</title>
    @vite(['resources/css/app.css', 'resources/js/admin-dashboard.js'])
</head>
<body class="bg-slate-100">
    <div id="app"></div>
</body>
</html>
