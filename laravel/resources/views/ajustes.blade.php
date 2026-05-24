@extends('layouts.app')
@section('titulo', 'Ajustes')

@section('contenido')

@if($errors->any())
    <div class="alert alert-error">
        <ul class="error-list">
            @foreach($errors->all() as $err)<li>{{ $err }}</li>@endforeach
        </ul>
    </div>
@endif

<div class="cols-2">

    <article class="card">
        <header><h2>Mi perfil</h2></header>

        <p class="form-required-hint">Los campos marcados con <span class="required-marker">*</span> son obligatorios.</p>

        <form method="POST" action="{{ url('/ajustes/perfil') }}" novalidate>
            @csrf @method('PUT')
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label" for="nombre">Nombre <span class="required-marker">*</span></label>
                    <input class="form-input" id="nombre" name="nombre" value="{{ old('nombre', $usuario->nombre) }}">
                </div>
                <div class="form-group">
                    <label class="form-label" for="apellido">Apellido <span class="required-marker">*</span></label>
                    <input class="form-input" id="apellido" name="apellido" value="{{ old('apellido', $usuario->apellido) }}">
                </div>
            </div>
            <div class="form-group">
                <label class="form-label" for="telefono">Teléfono</label>
                <input class="form-input" id="telefono" name="telefono" value="{{ old('telefono', $usuario->telefono) }}" inputmode="tel" placeholder="+34 612 345 678">
                <p class="form-help">Formato español: 9 dígitos empezando por 6, 7, 8 o 9. Opcionalmente con prefijo +34.</p>
            </div>
            <div class="form-group">
                <label class="form-label" for="email_ro">Correo electrónico</label>
                <input class="form-input" id="email_ro" value="{{ $usuario->email }}" disabled>
                <p class="form-help">El correo no es editable por el propio usuario porque se utiliza como identificador único de auditoría: cambiarlo rompería la trazabilidad de los registros y de los informes ya firmados. Si necesitas cambiarlo, contacta con un administrador.</p>
            </div>
            <div class="flex flex-end">
                <button class="btn btn-primary">Guardar perfil</button>
            </div>
        </form>
    </article>

    <article class="card">
        <header><h2>Cambiar contraseña</h2></header>

        <p class="form-required-hint">Los campos marcados con <span class="required-marker">*</span> son obligatorios.</p>

        <form method="POST" action="{{ url('/ajustes/password') }}" novalidate>
            @csrf @method('PUT')
            <div class="form-group">
                <label class="form-label" for="password_actual">Contraseña actual <span class="required-marker">*</span></label>
                <div class="password-wrap">
                    <input type="password" class="form-input" id="password_actual" name="password_actual" autocomplete="current-password">
                    <button type="button" class="toggle-pwd" data-target="password_actual" aria-label="Mostrar u ocultar contraseña">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8S1 12 1 12z"/><circle cx="12" cy="12" r="3"/></svg>
                    </button>
                </div>
            </div>
            <div class="form-group">
                <label class="form-label" for="password">Nueva contraseña <span class="required-marker">*</span></label>
                <div class="password-wrap">
                    <input type="password" class="form-input" id="password" name="password" autocomplete="new-password">
                    <button type="button" class="toggle-pwd" data-target="password" aria-label="Mostrar u ocultar contraseña">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8S1 12 1 12z"/><circle cx="12" cy="12" r="3"/></svg>
                    </button>
                </div>
                <p class="form-help">Mínimo 8 caracteres, debe contener al menos una mayúscula y un número.</p>
            </div>
            <div class="form-group">
                <label class="form-label" for="password_confirmation">Repetir nueva contraseña <span class="required-marker">*</span></label>
                <div class="password-wrap">
                    <input type="password" class="form-input" id="password_confirmation" name="password_confirmation" autocomplete="new-password">
                    <button type="button" class="toggle-pwd" data-target="password_confirmation" aria-label="Mostrar u ocultar contraseña">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8S1 12 1 12z"/><circle cx="12" cy="12" r="3"/></svg>
                    </button>
                </div>
            </div>
            <div class="flex flex-end">
                <button class="btn btn-primary">Cambiar contraseña</button>
            </div>
        </form>
    </article>

</div>

<article class="card mt-2">
    <header><h2>Motor de escaneo</h2></header>
    <p class="text-muted text-sm mb-2">Configuración del backend de análisis (FastAPI). Los valores se leen del archivo <code>.env</code> al arrancar — para cambiarlos hay que actualizar el <code>.env</code> y reiniciar los contenedores.</p>

    <dl class="dl-grid">
        <dt class="text-muted">URL del motor</dt>
        <dd class="mono">{{ $fastapiUrl }}</dd>

        <dt class="text-muted">Token compartido</dt>
        <dd class="mono">configurado (oculto por seguridad)</dd>

        <dt class="text-muted">API key NVD</dt>
        <dd>
            @if($tieneApiKeyNvd)
                <span class="badge badge-completado">configurada</span>
                <span class="text-muted text-xxs">rate limit alto (50 req/30s)</span>
            @else
                <span class="badge badge-pendiente">no configurada</span>
                <span class="text-muted text-xxs">rate limit bajo (5 req/30s) — solicitar en nvd.nist.gov/developers</span>
            @endif
        </dd>
    </dl>
</article>

@if($usuario->esAdmin())

    <article class="card mt-2">
        <header><h2>Estadísticas de la plataforma</h2></header>
        <section class="kpi-grid">
            <article class="kpi">
                <div class="kpi-label">Usuarios</div>
                <div class="kpi-value">{{ $stats['usuarios'] }}</div>
                <div class="kpi-hint">{{ $stats['admin'] }} administradores</div>
            </article>
            <article class="kpi">
                <div class="kpi-label">Proyectos</div>
                <div class="kpi-value">{{ $stats['proyectos'] }}</div>
                <div class="kpi-hint">Total registrados</div>
            </article>
            <article class="kpi">
                <div class="kpi-label">Escaneos</div>
                <div class="kpi-value">{{ $stats['escaneos'] }}</div>
                <div class="kpi-hint">Histórico completo</div>
            </article>
            <article class="kpi">
                <div class="kpi-label">Vulnerabilidades</div>
                <div class="kpi-value">{{ $stats['vulnerab'] }}</div>
                <div class="kpi-hint">{{ $stats['sesiones'] }} sesiones activas</div>
            </article>
        </section>
    </article>

    <article class="card mt-2">
        <header><h2>Información del sistema</h2></header>
        <p class="text-muted text-sm mb-2">Solo lectura. Útil para reportar incidencias y verificar versiones desplegadas.</p>
        <dl class="dl-grid">
            <dt class="text-muted">PHP</dt><dd class="mono">{{ $versiones['php'] }}</dd>
            <dt class="text-muted">Laravel</dt><dd class="mono">{{ $versiones['laravel'] }}</dd>
            <dt class="text-muted">MariaDB</dt><dd class="mono">{{ $versiones['mariadb'] }}</dd>
            <dt class="text-muted">Sesiones (driver)</dt><dd class="mono">{{ config('session.driver') }}</dd>
            <dt class="text-muted">Entorno</dt><dd><span class="badge badge-info">{{ config('app.env') }}</span></dd>
            <dt class="text-muted">URL aplicación</dt><dd class="mono">{{ config('app.url') }}</dd>
            <dt class="text-muted">Zona horaria</dt><dd class="mono">{{ config('app.timezone') }}</dd>
            <dt class="text-muted">Locale</dt><dd class="mono">{{ config('app.locale') }}</dd>
        </dl>
    </article>

    <article class="card mt-2">
        <header><h2>Seguridad y políticas</h2></header>
        <p class="text-muted text-sm mb-2">Resumen de las políticas activas. Editar requiere modificar <code>.env</code> y reiniciar.</p>
        <dl class="dl-grid">
            <dt class="text-muted">Rate limit login</dt><dd>5 intentos / minuto por IP+email</dd>
            <dt class="text-muted">Sesión HTTPS only</dt><dd>{{ config('session.secure') ? 'sí' : 'no' }}</dd>
            <dt class="text-muted">Cookie HttpOnly</dt><dd>{{ config('session.http_only') ? 'sí' : 'no' }}</dd>
            <dt class="text-muted">SameSite</dt><dd class="mono">{{ config('session.same_site') }}</dd>
            <dt class="text-muted">Coste bcrypt</dt><dd>{{ config('hashing.bcrypt.rounds', 10) }}</dd>
            <dt class="text-muted">Usuario BD runtime</dt><dd class="mono">{{ config('database.connections.mysql.username') }} (solo DML)</dd>
        </dl>
    </article>

@endif

@push('scripts')
<script src="{{ asset('assets/js/password-toggle.js') }}"></script>
@endpush

@endsection
