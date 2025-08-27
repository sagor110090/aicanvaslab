<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title inertia>{{ config('app.name', 'AI Chat') }}</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700&display=swap" rel="stylesheet" />

        <!-- Dark Mode Initialization -->
        <script>
            // Initialize dark mode before Vue loads to prevent flash
            (function() {
                const savedMode = localStorage.getItem('darkMode');
                const systemPrefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
                const isDark = savedMode !== null ? savedMode === 'true' : systemPrefersDark;
                
                console.log('Dark mode init:', { savedMode, systemPrefersDark, isDark });
                
                if (isDark) {
                    document.documentElement.classList.add('dark');
                    document.body.classList.add('dark');
                    console.log('Applied dark classes');
                } else {
                    document.documentElement.classList.remove('dark');
                    document.body.classList.remove('dark');
                    console.log('Applied light classes');
                }
            })();
        </script>

        <!-- Scripts -->
        @routes
        @vite(['resources/css/app.css', 'resources/js/app.js'])
        @inertiaHead
    </head>
    <body class="font-sans antialiased bg-gray-50 dark:bg-gray-900 transition-colors duration-200">
        @inertia
    </body>
</html>