<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="theme-color" content="#465fff">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="default">
    <meta name="apple-mobile-web-app-title" content="Escala">
    <link rel="manifest" href="/manifest.webmanifest">
    <link rel="icon" type="image/png" href="/icons/favicon-32.png">
    <link rel="apple-touch-icon" href="/icons/apple-touch-icon.png">
    <script>
        try { if (JSON.parse(localStorage.getItem('darkMode') || 'false')) document.documentElement.classList.add('dark') } catch (e) {}
    </script>
    @fonts
    @routes
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <title inertia>{{ config('app.name') }}</title>
    @inertiaHead
</head>
<body class="font-sans antialiased">
    @inertia
</body>
</html>
