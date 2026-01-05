<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Admin Login - Restaurant Reservation System</title>
    <link rel="stylesheet" href="{{ asset('css/auth.css') }}">
</head>
<body>
    <div class="auth-container">
        <div class="auth-header">
            <h1>🔐 Admin Login</h1>
            <p>Sign in to access the admin dashboard</p>
        </div>

        @if(session('success'))
        <div class="alert alert-success">
            {{ session('success') }}
        </div>
        @endif

        @if(session('error'))
        <div class="alert alert-error">
            {{ session('error') }}
        </div>
        @endif

        <form method="POST" action="{{ route('login') }}">
            @csrf

            <div class="form-group">
                <label for="email">Email Address</label>
                <input 
                    type="email" 
                    id="email" 
                    name="email" 
                    value="{{ old('email') }}" 
                    required 
                    autofocus
                    autocomplete="email"
                >
                @error('email')
                    <div class="error-message">{{ $message }}</div>
                @enderror
            </div>

            <div class="form-group">
                <label for="password">Password</label>
                <input 
                    type="password" 
                    id="password" 
                    name="password" 
                    required
                    autocomplete="current-password"
                >
                @error('password')
                    <div class="error-message">{{ $message }}</div>
                @enderror
            </div>

            <div class="checkbox-group">
                <input 
                    type="checkbox" 
                    id="remember" 
                    name="remember"
                    {{ old('remember') ? 'checked' : '' }}
                >
                <label for="remember">Remember me</label>
            </div>

            <button type="submit" class="btn">Sign In</button>
        </form>

        <div class="auth-footer">
            <a href="/">← Back to Booking Page</a>
        </div>
    </div>
</body>
</html>

