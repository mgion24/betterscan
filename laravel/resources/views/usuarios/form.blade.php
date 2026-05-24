@extends('layouts.app')
@section('titulo', $usuario->exists ? 'Editar usuario' : 'Nuevo usuario')

@section('contenido')
@php
    $editando = $usuario->exists;
    $accion = $editando ? url('/usuarios/'.$usuario->id) : url('/usuarios');
@endphp

<form method="POST" action="{{ $accion }}" novalidate>
    @csrf
    @if($editando) @method('PUT') @endif

    @if($errors->any())
        <div class="alert alert-error">
            <ul class="error-list">
                @foreach($errors->all() as $err)<li>{{ $err }}</li>@endforeach
            </ul>
        </div>
    @endif

    <p class="form-required-hint">Los campos marcados con <span class="required-marker">*</span> son obligatorios.</p>

    <article class="card mb-2">
        <header><h2>Datos personales</h2></header>

        <div class="form-row">
            <div class="form-group">
                <label class="form-label" for="nombre">Nombre <span class="required-marker">*</span></label>
                <input class="form-input" id="nombre" name="nombre"
                       value="{{ old('nombre', $usuario->nombre) }}">
                @error('nombre')<p class="form-error">{{ $message }}</p>@enderror
            </div>
            <div class="form-group">
                <label class="form-label" for="apellido">Apellido <span class="required-marker">*</span></label>
                <input class="form-input" id="apellido" name="apellido"
                       value="{{ old('apellido', $usuario->apellido) }}">
                @error('apellido')<p class="form-error">{{ $message }}</p>@enderror
            </div>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label class="form-label" for="email">Correo electrónico <span class="required-marker">*</span></label>
                <input class="form-input" id="email" name="email"
                       value="{{ old('email', $usuario->email) }}">
                @error('email')<p class="form-error">{{ $message }}</p>@enderror
            </div>
            <div class="form-group">
                <label class="form-label" for="telefono">Teléfono</label>
                <input class="form-input" id="telefono" name="telefono"
                       inputmode="tel"
                       placeholder="+34 612 345 678"
                       value="{{ old('telefono', $usuario->telefono) }}">
                <p class="form-help">Formato español: 9 dígitos empezando por 6, 7, 8 o 9. Opcionalmente con prefijo +34.</p>
                @error('telefono')<p class="form-error">{{ $message }}</p>@enderror
            </div>
        </div>
    </article>

    <article class="card mb-2">
        <header><h2>Rol y empresa</h2></header>

        <div class="form-row">
            <div class="form-group">
                <label class="form-label" for="rol_id">Rol <span class="required-marker">*</span></label>
                <select class="form-select" id="rol_id" name="rol_id" onchange="toggleEmpresa()">
                    @foreach($roles as $r)
                        <option value="{{ $r->id }}" data-nombre="{{ $r->nombre }}"
                            @if(old('rol_id', $usuario->rol_id) == $r->id) selected @endif>
                            {{ ucfirst($r->nombre) }}
                        </option>
                    @endforeach
                </select>
                @error('rol_id')<p class="form-error">{{ $message }}</p>@enderror
            </div>
            <div class="form-group" id="grupo-empresa">
                <label class="form-label" for="empresa_id">Empresa <span class="required-marker" id="asterisco-empresa">*</span></label>
                <select class="form-select" id="empresa_id" name="empresa_id">
                    <option value="">— Sin empresa —</option>
                    @foreach($empresas as $e)
                        <option value="{{ $e->id }}" @if(old('empresa_id', $usuario->empresa_id) == $e->id) selected @endif>
                            {{ $e->nombre_comercial ?? $e->nombre }}
                        </option>
                    @endforeach
                </select>
                <p class="form-help">Obligatorio si el rol es «cliente». En administradores y empleados se ignora.</p>
                @error('empresa_id')<p class="form-error">{{ $message }}</p>@enderror
            </div>
        </div>

    </article>

    <article class="card mb-2">
        <header><h2>{{ $editando ? 'Cambiar contraseña (opcional)' : 'Contraseña' }}</h2></header>

        <div class="form-row">
            <div class="form-group">
                <label class="form-label" for="password">Contraseña @unless($editando)<span class="required-marker">*</span>@endunless</label>
                <div class="password-wrap">
                    <input type="password" class="form-input" id="password" name="password" autocomplete="new-password">
                    <button type="button" class="toggle-pwd" data-target="password" aria-label="Mostrar u ocultar contraseña">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8S1 12 1 12z"/><circle cx="12" cy="12" r="3"/></svg>
                    </button>
                </div>
                <p class="form-help">Mínimo 8 caracteres, debe incluir al menos una mayúscula y un número.</p>
                @error('password')<p class="form-error">{{ $message }}</p>@enderror
            </div>
            <div class="form-group">
                <label class="form-label" for="password_confirmation">Repetir contraseña @unless($editando)<span class="required-marker">*</span>@endunless</label>
                <div class="password-wrap">
                    <input type="password" class="form-input" id="password_confirmation" name="password_confirmation" autocomplete="new-password">
                    <button type="button" class="toggle-pwd" data-target="password_confirmation" aria-label="Mostrar u ocultar contraseña">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8S1 12 1 12z"/><circle cx="12" cy="12" r="3"/></svg>
                    </button>
                </div>
            </div>
        </div>
    </article>

    <div class="flex gap-1 flex-end">
        <a href="{{ url('/usuarios') }}" class="btn btn-secondary">Cancelar</a>
        <button type="submit" class="btn btn-primary">{{ $editando ? 'Guardar cambios' : 'Crear usuario' }}</button>
    </div>
</form>

@push('scripts')
<script src="{{ asset('assets/js/password-toggle.js') }}"></script>
<script>
function toggleEmpresa() {
    const sel = document.getElementById('rol_id');
    if (!sel) return;
    const opt = sel.options[sel.selectedIndex];
    const grupo = document.getElementById('grupo-empresa');
    const empresa = document.getElementById('empresa_id');
    const asterisco = document.getElementById('asterisco-empresa');
    const esCliente = opt.dataset.nombre === 'cliente';
    grupo.classList.toggle('hidden', !esCliente);
    asterisco.hidden = !esCliente;
    // No tocamos attr "required" — Laravel valida la regla cruzada empresa<->rol.
    // El asterisco rojo se muestra/oculta solo para indicarlo visualmente.
}
toggleEmpresa();
</script>
@endpush
@endsection
