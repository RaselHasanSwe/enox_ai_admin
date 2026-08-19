<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="robots" content="noindex, nofollow">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>EnoX | Login</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:ital,opsz,wght@0,9..40,400;0,9..40,500;0,9..40,600;0,9..40,700;1,9..40,400&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/auth-login.css') }}">
</head>
<body>
    <div class="auth-shell">
        <div class="auth-panel auth-panel--form">
            <div class="auth-form-wrap">
                <div class="auth-brand">
                    <h1 class="auth-logo" aria-label="EnoX">
                        <span class="auth-logo__letter auth-logo__letter--e">E</span><span class="auth-logo__letter auth-logo__letter--no">no</span><span class="auth-logo__letter auth-logo__letter--x">X</span>
                    </h1>
                </div>

                <h3 class="auth-heading">Welcome back</h3>
                <p class="auth-subheading">Sign in to manage chatbot conversations, live handoffs, and support analytics.</p>

                @if ($errors->any())
                    <div class="auth-alert auth-alert--error" role="alert">
                        {{ $errors->first() }}
                    </div>
                @endif

                <form id="formAuthentication" action="{{ route('login') }}" method="POST" novalidate>
                    @csrf

                    <div class="auth-field">
                        <label for="email" class="auth-label">Email address</label>
                        <input type="email"
                               id="email"
                               name="email"
                               value="{{ old('email') }}"
                               class="auth-input @error('email') is-invalid @enderror"
                               placeholder="you@company.com"
                               autocomplete="email"
                               autofocus
                               required>
                        @error('email')
                            <span class="auth-error">{{ $message }}</span>
                        @enderror
                    </div>

                    <div class="auth-field">
                        <label for="password" class="auth-label">Password</label>
                        <div class="auth-input-wrap">
                            <input type="password"
                                   id="password"
                                   name="password"
                                   class="auth-input auth-input--password @error('password') is-invalid @enderror"
                                   placeholder="Enter your password"
                                   autocomplete="current-password"
                                   required>
                            <button type="button" class="auth-toggle-pass" id="togglePassword" aria-label="Show password">
                                <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z"/>
                                    <path stroke-linecap="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                </svg>
                            </button>
                        </div>
                        @error('password')
                            <span class="auth-error">{{ $message }}</span>
                        @enderror
                    </div>

                    <button type="submit" id="sign_in_button" class="auth-submit">
                        Sign in
                    </button>
                </form>

                <p class="auth-footer-note">
                    Use your EnoX admin credentials to access the live support workspace.
                </p>
            </div>
        </div>

        <div class="auth-panel auth-panel--visual" aria-hidden="true">
            <div class="auth-visual-pattern"></div>
            <div class="auth-visual-overlay">
                <div class="auth-visual-badge">
                    <svg width="12" height="12" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" d="M8 10h8M8 14h5M21 12c0 4.418-4.03 8-9 8a9.86 9.86 0 01-4.255-.947L3 21l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/></svg>
                    Live Support
                </div>
                <h2 class="auth-visual-title">Human handoff, analytics, and AI support in one place</h2>
                <p class="auth-visual-text">
                    Claim waiting customers, review conversations, and track containment — from a workspace built for Enorsia operations.
                </p>
                <div class="auth-visual-stats">
                    <div class="auth-visual-stat">
                        <strong>Live</strong>
                        <span>Queue & chats</span>
                    </div>
                    <div class="auth-visual-stat">
                        <strong>CSAT</strong>
                        <span>Feedback insight</span>
                    </div>
                    <div class="auth-visual-stat">
                        <strong>24/7</strong>
                        <span>AI containment</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        (function () {
            const form = document.getElementById('formAuthentication');
            const btn = document.getElementById('sign_in_button');
            const password = document.getElementById('password');
            const toggleBtn = document.getElementById('togglePassword');

            if (toggleBtn && password) {
                toggleBtn.addEventListener('click', function () {
                    const isHidden = password.type === 'password';
                    password.type = isHidden ? 'text' : 'password';
                    toggleBtn.setAttribute('aria-label', isHidden ? 'Hide password' : 'Show password');
                });
            }

            if (form && btn) {
                form.addEventListener('submit', function () {
                    btn.disabled = true;
                    btn.innerHTML = '<span class="auth-spinner"></span> Signing in...';
                });
            }
        })();
    </script>
</body>
</html>
