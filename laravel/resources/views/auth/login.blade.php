@extends('layouts.auth')
@section('titulo', 'Iniciar sesión · BetterScan')

@section('contenido')
<main class="auth-page">
    <section class="auth-card">
        <header>
            <img src="{{ asset('assets/img/logo.svg') }}" alt="BetterScan logo">
            <h1>Bienvenido a BetterScan</h1>
            <p class="subtitle">Introduce tus credenciales para continuar</p>
        </header>

        @if($errors->any())
            <div class="alert alert-error">
                {{ $errors->first() }}
            </div>
        @endif

        <form method="POST" action="{{ url('/login') }}" novalidate>
            @csrf

            <div class="form-group">
                <label for="email" class="form-label">Correo electrónico <span class="required-marker">*</span></label>
                <input name="email" id="email" class="form-input"
                       value="{{ old('email') }}" autofocus autocomplete="email">
            </div>

            <div class="form-group">
                <label for="password" class="form-label">Contraseña <span class="required-marker">*</span></label>
                <div class="password-wrap">
                    <input type="password" name="password" id="password" class="form-input"
                           autocomplete="current-password">
                    <button type="button" class="toggle-pwd" data-target="password" aria-label="Mostrar u ocultar contraseña">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8S1 12 1 12z"/><circle cx="12" cy="12" r="3"/></svg>
                    </button>
                </div>
            </div>

            <div class="form-group">
                <label class="form-checkbox">
                    <input type="checkbox" name="remember">
                    Recordarme en este equipo
                </label>
            </div>

            <button type="submit" class="btn btn-primary btn-lg w-100">Iniciar sesión</button>
        </form>

        <p class="auth-footer-note">
            Plataforma interna de auditoría de seguridad · TFG DAW 2026
        </p>
    </section>
</main>
<script src="{{ asset('assets/js/password-toggle.js') }}"></script>
@endsection
