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
    
    <script>
        // Global Loading Spinner Utility
        function setButtonLoading(button, isLoading, originalText = null) {
            if (!button) return;
            
            const spinnerSvg = '<svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-white inline" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>';
            
            if (isLoading) {
                button.disabled = true;
                button.dataset.originalText = originalText || button.innerHTML;
                button.innerHTML = spinnerSvg + (originalText || button.textContent.trim());
                button.classList.add('opacity-75', 'cursor-not-allowed');
            } else {
                button.disabled = false;
                button.innerHTML = button.dataset.originalText || originalText || button.innerHTML;
                button.classList.remove('opacity-75', 'cursor-not-allowed');
                delete button.dataset.originalText;
            }
        }
    </script>
    
    @stack('scripts')
</body>
</html>
