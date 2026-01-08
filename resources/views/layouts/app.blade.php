<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', config('app.name', 'Laravel'))</title>
    
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- Flatpickr CSS (Tailwind-compatible datepicker) -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    
    @stack('styles')
</head>
<body class="bg-gray-50">
    <div class="min-h-screen">
        <main>
            @yield('content')
        </main>
    </div>
    
    <!-- Flatpickr JS (Tailwind-compatible datepicker) -->
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    
    @stack('scripts')
</body>
</html>
