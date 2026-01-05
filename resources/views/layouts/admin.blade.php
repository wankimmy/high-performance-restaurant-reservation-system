<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Admin Dashboard')</title>
    <link rel="stylesheet" href="{{ asset('css/admin.css') }}">
    <link rel="stylesheet" href="{{ asset('css/auth.css') }}">
    @stack('styles')
</head>
<body>
    <div class="header">
        <div>
            <h1>@yield('page-title')</h1>
            @hasSection('page-subtitle')
            <div class="@yield('subtitle-class', 'subtitle')">
                @yield('page-subtitle')
            </div>
            @endif
        </div>
        <div style="display: flex; align-items: center; gap: 20px;">
            <div class="nav">
                <a href="{{ route('admin.reservations.index') }}">Reservations</a>
                <a href="{{ route('admin.settings.index') }}">Settings</a>
                <a href="{{ route('admin.monitoring.index') }}">Monitoring</a>
            </div>
            @auth
            <div class="user-info">
                <span class="user-name">{{ Auth::user()->name }}</span>
                <form method="POST" action="{{ route('logout') }}" style="display: inline;">
                    @csrf
                    <button type="submit" class="logout-btn">Logout</button>
                </form>
            </div>
            @endauth
        </div>
    </div>

    @if(session('success'))
    <div class="message success">{{ session('success') }}</div>
    @endif

    @if(session('error'))
    <div class="message error">{{ session('error') }}</div>
    @endif

    <div id="message" class="message" style="display: none;"></div>

    @yield('content')
    
    @stack('scripts')
</body>
</html>

