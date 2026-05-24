<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('titulo', 'BetterScan')</title>
    <link rel="icon" type="image/svg+xml" href="{{ asset('assets/img/logo.svg') }}">
    <link rel="stylesheet" href="{{ asset('assets/icons/bootstrap-icons.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/bootstrap.min.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/app.css') }}">
</head>
<body>
@php
    $u = auth()->user();
    $esCliente = $u && $u->esCliente();
    $esAdmin   = $u && $u->esAdmin();
@endphp

{{-- Checkbox oculto para el menú móvil (truco CSS-only sin JS).
     Cuando está marcado, el sidebar se desliza y aparece el overlay. --}}
<input type="checkbox" id="nav-toggle" class="nav-toggle-input" aria-hidden="true">

<div class="app-layout">

    {{-- Sidebar fijo --}}
    <aside class="sidebar">
        <header class="sidebar-brand">
            <a href="{{ url($esCliente ? '/portal' : '/dashboard') }}" class="brand-link" title="Ir al inicio">
                <img src="{{ asset('assets/img/logo.svg') }}" alt="BetterScan logo">
                <strong>Better<em>Scan</em></strong>
            </a>
        </header>

        <nav class="sidebar-nav" aria-label="Navegación principal">

            @unless($esCliente)
                {{-- Vista de admin/empleado --}}
                <span class="nav-section-title">Principal</span>
                <ul>
                    <li><a href="{{ url('/dashboard') }}" class="@if(request()->is('dashboard')) active @endif">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/></svg>
                        Dashboard
                    </a></li>
                    <li><a href="{{ url('/proyectos') }}" class="@if(request()->is('proyectos*')) active @endif">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z"/></svg>
                        Proyectos
                    </a></li>
                    <li><a href="{{ url('/escaneos') }}" class="@if(request()->is('escaneos*')) active @endif">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                        Escaneos
                    </a></li>
                </ul>

                @if($esAdmin)
                    <span class="nav-section-title">Administración</span>
                    <ul>
                        <li><a href="{{ url('/usuarios') }}" class="@if(request()->is('usuarios*')) active @endif">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                            Usuarios
                        </a></li>
                        <li><a href="{{ url('/clientes') }}" class="@if(request()->is('clientes*')) active @endif">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 21h18M5 21V7l8-4 8 4v14M9 9v.01M9 12v.01M9 15v.01M9 18v.01"/></svg>
                            Clientes
                        </a></li>
                    </ul>
                @endif

                <span class="nav-section-title">Cuenta</span>
                <ul>
                    <li><a href="{{ url('/ajustes') }}" class="@if(request()->is('ajustes*')) active @endif">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.7 1.7 0 0 0 .3 1.8l.1.1a2 2 0 1 1-2.8 2.8l-.1-.1a1.7 1.7 0 0 0-1.8-.3 1.7 1.7 0 0 0-1 1.5V21a2 2 0 1 1-4 0v-.1a1.7 1.7 0 0 0-1-1.5 1.7 1.7 0 0 0-1.8.3l-.1.1a2 2 0 1 1-2.8-2.8l.1-.1a1.7 1.7 0 0 0 .3-1.8 1.7 1.7 0 0 0-1.5-1H3a2 2 0 1 1 0-4h.1a1.7 1.7 0 0 0 1.5-1 1.7 1.7 0 0 0-.3-1.8l-.1-.1a2 2 0 1 1 2.8-2.8l.1.1a1.7 1.7 0 0 0 1.8.3H9a1.7 1.7 0 0 0 1-1.5V3a2 2 0 1 1 4 0v.1a1.7 1.7 0 0 0 1 1.5 1.7 1.7 0 0 0 1.8-.3l.1-.1a2 2 0 1 1 2.8 2.8l-.1.1a1.7 1.7 0 0 0-.3 1.8V9a1.7 1.7 0 0 0 1.5 1H21a2 2 0 1 1 0 4h-.1a1.7 1.7 0 0 0-1.5 1z"/></svg>
                        Ajustes
                    </a></li>
                </ul>
            @else
                {{-- Vista de cliente: sidebar reducida --}}
                <span class="nav-section-title">Mi portal</span>
                <ul>
                    <li><a href="{{ url('/portal') }}" class="@if(request()->is('portal')) active @endif">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/></svg>
                        Inicio
                    </a></li>
                </ul>
            @endunless
        </nav>

        @if($u)
            <footer class="sidebar-user">
                <div class="avatar">{{ $u->iniciales() }}</div>
                <div class="info">
                    <div class="name">{{ $u->nombreCompleto() }}</div>
                    <div class="role">{{ $u->rol->nombre ?? '' }}</div>
                </div>
                <form action="{{ url('/logout') }}" method="POST" class="no-margin">
                    @csrf
                    <button class="btn btn-secondary" title="Cerrar sesión" type="submit">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
                    </button>
                </form>
            </footer>
        @endif
    </aside>

    {{-- Topbar --}}
    <header class="topbar">
        {{-- Botón hamburguesa: sólo visible en móvil. Es un <label>
             asociado al checkbox de arriba. Sin JS. --}}
        <label for="nav-toggle" class="nav-toggle-btn" aria-label="Abrir menú">
            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="18" x2="21" y2="18"/></svg>
        </label>

        {{-- Logo de la marca: visible sólo en móvil (en desktop aparece
             en la sidebar). --}}
        <a href="{{ url($esCliente ? '/portal' : '/dashboard') }}" class="topbar-brand brand-link">
            <img src="{{ asset('assets/img/logo.svg') }}" alt="BetterScan">
            <strong>Better<em>Scan</em></strong>
        </a>

        <h1>@yield('titulo', 'BetterScan')</h1>
        
        <div class="topbar-actions">
            {{-- Buscador y atajos solo para admin/empleado. El cliente no
                 debe poder enumerar proyectos ni CVEs ajenos por query. --}}
            @unless($esCliente)
                <form role="search" action="{{ url('/buscar') }}" method="GET">
                    <input type="search" name="q" placeholder="Buscar…" value="{{ request('q') }}">
                </form>
                <a href="{{ url('/proyectos/create') }}" class="btn btn-primary">+ Nuevo Proyecto</a>
            @endunless

            {{-- Avatar + logout en móvil (en desktop está al pie de la sidebar). --}}
            @if($u)
                <div class="topbar-user">
                    <div class="avatar">{{ $u->iniciales() }}</div>
                    <form action="{{ url('/logout') }}" method="POST" class="no-margin">
                        @csrf
                        <button class="btn btn-secondary" title="Cerrar sesión" type="submit">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
                        </button>
                    </form>
                </div>
            @endif
            {{-- En móvil horizontal, se debe quedar el buscador, el boton de nuevo proyecto y el avatar/logout a la derecha. Para conseguirlo ejecutamos esto en Laravel --}}
            


        </div>
    </header>

    {{-- Contenido --}}
    <main class="main">
        
        @if(session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif
        @if(session('error'))
            <div class="alert alert-error">{{ session('error') }}</div>
        @endif

        @yield('contenido')
    </main>
</div>

{{-- Overlay que cubre la pantalla cuando el menú está abierto.
     Click en él (es un <label> del checkbox) cierra el menú. --}}
<label for="nav-toggle" class="nav-overlay" aria-hidden="true"></label>

<script src="{{ asset('assets/js/app.js') }}"></script>
@stack('scripts')
</body>
</html>
