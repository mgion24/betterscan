@extends('layouts.app')
@section('titulo', $empresa->exists ? 'Editar cliente' : 'Nuevo cliente')

@section('contenido')
@php
    $editando = $empresa->exists;
    $accion = $editando ? url('/clientes/'.$empresa->id) : url('/clientes');
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
        <header><h2>Información de la empresa</h2></header>

        <div class="form-row">
            <div class="form-group">
                <label class="form-label" for="nombre">Nombre <span class="required-marker">*</span></label>
                <input class="form-input" id="nombre" name="nombre"
                       value="{{ old('nombre', $empresa->nombre) }}">
                @error('nombre')<p class="form-error">{{ $message }}</p>@enderror
            </div>
            <div class="form-group">
                <label class="form-label" for="cif">CIF/NIF <span class="required-marker">*</span></label>
                <input class="form-input mono" id="cif" name="cif"
                       value="{{ old('cif', $empresa->cif) }}"
                       placeholder="B12345678">
                <p class="form-help">Formato CIF español (letra + 8 caracteres).</p>
                @error('cif')<p class="form-error">{{ $message }}</p>@enderror
            </div>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label class="form-label" for="nombre_comercial">Nombre comercial</label>
                <input class="form-input" id="nombre_comercial" name="nombre_comercial"
                       value="{{ old('nombre_comercial', $empresa->nombre_comercial) }}">
                @error('nombre_comercial')<p class="form-error">{{ $message }}</p>@enderror
            </div>
            <div class="form-group">
                <label class="form-label" for="sector">Sector</label>
                <select class="form-select" id="sector" name="sector">
                    <option value="">— Sin especificar —</option>
                    @foreach(['tecnologia' => 'Tecnología', 'financiero' => 'Financiero', 'sanidad' => 'Sanidad', 'industrial' => 'Industrial', 'retail' => 'Retail', 'educacion' => 'Educación', 'administracion' => 'Administración pública', 'otros' => 'Otros'] as $val => $lbl)
                        <option value="{{ $val }}" @selected(old('sector', $empresa->sector) === $val)>{{ $lbl }}</option>
                    @endforeach
                </select>
                @error('sector')<p class="form-error">{{ $message }}</p>@enderror
            </div>
        </div>

        <div class="form-group">
            <label class="form-label" for="razon_social">Razón social</label>
            <input class="form-input" id="razon_social" name="razon_social"
                   value="{{ old('razon_social', $empresa->razon_social) }}">
            @error('razon_social')<p class="form-error">{{ $message }}</p>@enderror
        </div>

        <div class="form-group">
            <label class="form-label" for="direccion">Dirección</label>
            <textarea class="form-textarea" id="direccion" name="direccion">{{ old('direccion', $empresa->direccion) }}</textarea>
            @error('direccion')<p class="form-error">{{ $message }}</p>@enderror
        </div>

        <div class="form-group">
            <label class="form-checkbox">
                <input type="checkbox" name="activo" value="1" @checked(old('activo', $empresa->activo ?? 1))>
                Empresa activa
            </label>
            <p class="form-help">Las empresas inactivas no aparecen al crear proyectos nuevos.</p>
        </div>
    </article>

    <article class="card mb-2">
        <header><h2>Contacto comercial</h2></header>
        <p class="text-muted text-xs mb-2">Datos del responsable de la empresa para comunicaciones. Independiente del usuario que accede al portal cliente.</p>

        <div class="form-row">
            <div class="form-group">
                <label class="form-label" for="responsable_nombre">Nombre del responsable</label>
                <input class="form-input" id="responsable_nombre" name="responsable_nombre"
                       value="{{ old('responsable_nombre', $empresa->responsable_nombre) }}">
                @error('responsable_nombre')<p class="form-error">{{ $message }}</p>@enderror
            </div>
            <div class="form-group">
                <label class="form-label" for="responsable_email">Correo del responsable</label>
                <input class="form-input" id="responsable_email" name="responsable_email"
                       value="{{ old('responsable_email', $empresa->responsable_email) }}">
                @error('responsable_email')<p class="form-error">{{ $message }}</p>@enderror
            </div>
        </div>
    </article>

    <div class="flex gap-1 flex-end">
        <a href="{{ url('/clientes') }}" class="btn btn-secondary">Cancelar</a>
        <button type="submit" class="btn btn-primary">{{ $editando ? 'Guardar cambios' : 'Crear empresa' }}</button>
    </div>
</form>
@endsection
