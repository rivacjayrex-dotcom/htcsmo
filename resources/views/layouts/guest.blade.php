<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    @include('layouts.partials.initial-visibility')
    <meta name="theme-color" content="#14532d">
    @include('layouts.partials.browser-title')

    @vite(['resources/css/app.css', 'resources/css/guest.css', 'resources/js/guest.js'])

    @stack('styles')
</head>
<body>
    @include('layouts.partials.session-recovery')
    <main class="login-card">
        {{ $slot }}
    </main>

    @if(request()->routeIs('login', 'register'))
        @include('layouts.partials.auth-progress')
    @endif

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            if (window.lucide) lucide.createIcons();
        });
    </script>
</body>
</html>
