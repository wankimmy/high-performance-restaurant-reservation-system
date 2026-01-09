<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Admin Dashboard') - {{ config('app.name') }}</title>
    
    <!-- Tailwind CSS (Filament uses Tailwind) -->
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- DataTables CSS -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/jquery.dataTables.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/responsive/2.5.0/css/responsive.dataTables.min.css">
    
    <link rel="stylesheet" href="{{ asset('css/admin.css') }}">
    
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: {
                            50: '#eff6ff',
                            100: '#dbeafe',
                            200: '#bfdbfe',
                            300: '#93c5fd',
                            400: '#60a5fa',
                            500: '#3b82f6',
                            600: '#2563eb',
                            700: '#1d4ed8',
                            800: '#1e40af',
                            900: '#1e3a8a',
                        }
                    }
                }
            }
        }
    </script>
    
    @stack('styles')
</head>
<body class="bg-gray-50">
    <div class="flex h-screen overflow-hidden">
        <!-- Sidebar -->
        <aside class="hidden lg:flex lg:flex-shrink-0">
            <div class="flex flex-col w-64">
                <div class="flex flex-col flex-grow bg-white border-r border-gray-200 pt-5 pb-4 overflow-y-auto">
                    <!-- Logo -->
                    <div class="flex items-center flex-shrink-0 px-4">
                        <h1 class="text-lg font-semibold text-gray-900">Restaurant Reservation</h1>
                    </div>
                    
                    <!-- Navigation -->
                    <nav class="mt-5 flex-1 px-2 space-y-1">
                        <a href="{{ route('admin.dashboard') }}" 
                           class="sidebar-link group flex items-center px-3 py-2 text-sm font-medium rounded-md {{ request()->routeIs('admin.dashboard') ? 'active' : 'text-gray-700' }}">
                            <svg class="mr-3 h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                            </svg>
                            Dashboard
                        </a>
                        
                        <a href="{{ route('admin.reservations.index') }}" 
                           class="sidebar-link group flex items-center px-3 py-2 text-sm font-medium rounded-md {{ request()->routeIs('admin.reservations.*') ? 'active' : 'text-gray-700' }}">
                            <svg class="mr-3 h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                            </svg>
                            Reservations
                        </a>
                        
                        <a href="{{ route('admin.tables.index') }}" 
                           class="sidebar-link group flex items-center px-3 py-2 text-sm font-medium rounded-md {{ request()->routeIs('admin.tables.*') ? 'active' : 'text-gray-700' }}">
                            <svg class="mr-3 h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M3 14h18m-9-4v8m-7 0h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z" />
                            </svg>
                            Tables
                        </a>
                        
                        <a href="{{ route('admin.restaurant-settings.index') }}" 
                           class="sidebar-link group flex items-center px-3 py-2 text-sm font-medium rounded-md {{ request()->routeIs('admin.restaurant-settings.*') ? 'active' : 'text-gray-700' }}">
                            <svg class="mr-3 h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                            </svg>
                            Restaurant Settings
                        </a>
                        
                        <a href="{{ route('admin.whatsapp-settings.index') }}" 
                           class="sidebar-link group flex items-center px-3 py-2 text-sm font-medium rounded-md {{ request()->routeIs('admin.whatsapp-settings.*') ? 'active' : 'text-gray-700' }}">
                            <svg class="mr-3 h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
                            </svg>
                            WhatsApp Settings
                        </a>
                        
                        <a href="/pulse" 
                           class="sidebar-link group flex items-center px-3 py-2 text-sm font-medium rounded-md {{ request()->is('pulse*') ? 'active' : 'text-gray-700' }}">
                            <svg class="mr-3 h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                            </svg>
                            Pulse
                        </a>
                    </nav>
                    
                    <!-- User Section -->
                    <div class="flex-shrink-0 flex border-t border-gray-200 p-4">
                        <div class="flex-shrink-0 w-full group block">
                            <div class="flex items-center">
                                <div class="flex-1 min-w-0">
                                    <p class="text-sm font-medium text-gray-900 truncate">{{ Auth::user()->name }}</p>
                                    <p class="text-xs text-gray-500 truncate">{{ Auth::user()->email }}</p>
                                </div>
                                <form method="POST" action="{{ route('logout') }}" class="ml-3">
                                    @csrf
                                    <button type="submit" class="flex-shrink-0 bg-white p-1 text-gray-400 rounded-full hover:text-gray-500">
                                        <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                                        </svg>
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </aside>

        <!-- Main Content -->
        <div class="flex flex-col w-0 flex-1 overflow-hidden">
            <!-- Mobile menu button -->
            <div class="lg:hidden pl-1 pt-1 sm:pl-3 sm:pt-3">
                <button type="button" class="-ml-0.5 -mt-0.5 h-12 w-12 inline-flex items-center justify-center rounded-md text-gray-500 hover:text-gray-900" onclick="document.getElementById('mobile-menu').classList.toggle('hidden')">
                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                    </svg>
                </button>
            </div>

            <!-- Page Header -->
            <header class="bg-white shadow-sm">
                <div class="max-w-12xl mx-auto py-4 px-4 sm:px-6 lg:px-8">
                    <h1 class="text-2xl font-semibold text-gray-900">@yield('page-title', 'Dashboard')</h1>
                </div>
            </header>

            <!-- Page Content -->
            <main class="flex-1 relative overflow-y-auto focus:outline-none">
                <div class="py-6">
                    <div class="max-w-12xl mx-auto px-4 sm:px-6 md:px-8">
                        @yield('content')
                    </div>
                </div>
            </main>
        </div>
    </div>
    
    <!-- jQuery (required for DataTables) -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <!-- DataTables JS -->
    <script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/responsive/2.5.0/js/dataTables.responsive.min.js"></script>

    <!-- Toast Notification System -->
    <div id="toast-container" class="fixed top-4 right-4 z-50 space-y-2">
        <!-- Toasts will be dynamically added here -->
    </div>

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
        
        // Global Toast Notification Function (Tailwind-based)
        function showToast(message, type = 'success') {
            const toastContainer = document.getElementById('toast-container');
            if (!toastContainer) return;
            
            const toastId = 'toast-' + Date.now();
            const bgColor = type === 'success' ? 'bg-green-500' : type === 'error' ? 'bg-red-500' : 'bg-blue-500';
            const icon = type === 'success' ? '✓' : type === 'error' ? '✕' : 'ℹ';
            
            const toastHtml = `
                <div id="${toastId}" class="${bgColor} text-white px-4 py-3 rounded-lg shadow-lg flex items-center justify-between min-w-[300px] max-w-md animate-slide-in">
                    <div class="flex items-center">
                        <span class="mr-2 font-semibold">${icon}</span>
                        <span>${message}</span>
                    </div>
                    <button onclick="document.getElementById('${toastId}').remove()" class="ml-4 text-white hover:text-gray-200">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>
            `;
            
            toastContainer.insertAdjacentHTML('beforeend', toastHtml);
            const toastElement = document.getElementById(toastId);
            
            // Auto-remove after 5 seconds
            setTimeout(() => {
                if (toastElement) {
                    toastElement.style.opacity = '0';
                    toastElement.style.transform = 'translateX(100%)';
                    setTimeout(() => toastElement.remove(), 300);
                }
            }, 5000);
        }
        
        // Show flash messages from session as toasts
        @if(session('success'))
            showToast('{{ session('success') }}', 'success');
        @endif
        
        @if(session('error'))
            showToast('{{ session('error') }}', 'error');
        @endif
    </script>
    

    @stack('scripts')
</body>
</html>
