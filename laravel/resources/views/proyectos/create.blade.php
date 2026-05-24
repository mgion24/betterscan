@extends('layouts.app')
@section('titulo', $proyecto->exists ? 'Editar proyecto' : 'Nuevo proyecto')

@section('contenido')
@php
    $editando = $proyecto->exists;
    $accion = $editando ? url('/proyectos/'.$proyecto->id) : url('/proyectos');
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
        <header><h2>Información del proyecto</h2></header>

        <div class="form-group">
            <label class="form-label" for="nombre">Nombre <span class="required-marker">*</span></label>
            <input class="form-input" id="nombre" name="nombre"
                   value="{{ old('nombre', $proyecto->nombre) }}">
            @error('nombre')<p class="form-error">{{ $message }}</p>@enderror
        </div>

        <div class="form-row">
            <div class="form-group">
                <label class="form-label" for="tipo_auditoria">Tipo de auditoría</label>
                <select class="form-select" id="tipo_auditoria" name="tipo_auditoria">
                    <option value="">— Sin especificar —</option>
                    @foreach(['caja_blanca' => 'Caja blanca', 'caja_negra' => 'Caja negra', 'caja_gris' => 'Caja gris', 'red_team' => 'Red team', 'pentest_web' => 'Pentest web', 'pentest_interno' => 'Pentest interno', 'compliance' => 'Cumplimiento normativo'] as $val => $lbl)
                        <option value="{{ $val }}" @selected(old('tipo_auditoria', $proyecto->tipo_auditoria) === $val)>{{ $lbl }}</option>
                    @endforeach
                </select>
                @error('tipo_auditoria')<p class="form-error">{{ $message }}</p>@enderror
            </div>
            <div class="form-group">
                <label class="form-label" for="visibilidad">Visibilidad <span class="required-marker">*</span></label>
                <select class="form-select" id="visibilidad" name="visibilidad">
                    @foreach(['privado' => 'Privado (solo auditores)', 'cliente' => 'Visible al cliente'] as $val => $txt)
                        <option value="{{ $val }}" @if(old('visibilidad', $proyecto->visibilidad ?? 'privado') === $val) selected @endif>{{ $txt }}</option>
                    @endforeach
                </select>
                @error('visibilidad')<p class="form-error">{{ $message }}</p>@enderror
            </div>
        </div>

        <div class="form-group">
            <label class="form-label" for="descripcion">Descripción</label>
            <textarea class="form-textarea" id="descripcion" name="descripcion">{{ old('descripcion', $proyecto->descripcion) }}</textarea>
            <p class="form-help">Máximo 2000 caracteres.</p>
            @error('descripcion')<p class="form-error">{{ $message }}</p>@enderror
        </div>

        <div class="form-group">
            <label class="form-label" for="etiquetas">Etiquetas (separadas por comas)</label>
            <input class="form-input" id="etiquetas" name="etiquetas"
                   value="{{ old('etiquetas', $proyecto->etiquetas) }}"
                   placeholder="interno, q2-2026, pci-dss">
            @error('etiquetas')<p class="form-error">{{ $message }}</p>@enderror
        </div>
    </article>

    <article class="card mb-2">
        <header><h2>Alcance de red</h2></header>

        <div class="form-group">
            <label class="form-label" for="alcance_red">Rangos a auditar (CIDR, IPs u hostnames separados por comas)</label>
            <textarea class="form-textarea" id="alcance_red" name="alcance_red"
                      placeholder="192.168.1.0/24, 10.0.0.0/16, target.local">{{ old('alcance_red', $proyecto->alcance_red) }}</textarea>
            <p class="form-help">Acepta IPs sueltas, rangos CIDR o hostnames. Se valida individualmente al lanzar cada escaneo.</p>
            @error('alcance_red')<p class="form-error">{{ $message }}</p>@enderror
        </div>

        <div class="form-group">
            <label class="form-label" for="excepciones_red">Excepciones</label>
            <textarea class="form-textarea" id="excepciones_red" name="excepciones_red"
                      placeholder="192.168.1.1">{{ old('excepciones_red', $proyecto->excepciones_red) }}</textarea>
            @error('excepciones_red')<p class="form-error">{{ $message }}</p>@enderror
        </div>

        <div class="form-group">
            <label class="form-label" for="fecha_limite_estimada">Fecha límite estimada</label>
            <input type="date" class="form-input" id="fecha_limite_estimada" name="fecha_limite_estimada"
                   value="{{ old('fecha_limite_estimada', $proyecto->fecha_limite_estimada?->format('Y-m-d')) }}">
            <p class="form-help">Opcional. Si se rellena debe ser una fecha futura.</p>
            @error('fecha_limite_estimada')<p class="form-error">{{ $message }}</p>@enderror
        </div>
    </article>

    <article class="card mb-2">
        <header><h2>Asignación</h2></header>

        <div class="form-row">
            <div class="form-group">
                <label class="form-label" for="empresa_id">Empresa cliente <span class="required-marker">*</span></label>
                <select class="form-select" id="empresa_id" name="empresa_id">
                    <option value="">— Seleccionar empresa —</option>
                    @foreach($empresas as $e)
                    <!-- el old('empresa_id', $proyecto->empresa_id) permite mantener seleccionada la empresa elegida previamente en caso de que el formulario se recargue por un error de validación, o cargar la empresa asignada al proyecto en caso de estar editando un proyecto existente. Si ninguna de las dos cosas aplica (formulario nuevo sin errores), no se seleccionará ninguna opción por defecto. -->
                        <option value="{{ $e->id }}" @if(old('empresa_id', $proyecto->empresa_id) == $e->id) selected @endif>
                            {{ $e->nombre_comercial ?? $e->nombre }}
                        </option>
                    @endforeach
                </select>
                @error('empresa_id')<p class="form-error">{{ $message }}</p>@enderror
            </div>
            <div class="form-group">
                <label class="form-label" for="auditor_id">Auditor responsable <span class="required-marker">*</span></label>
                <select class="form-select" id="auditor_id" name="auditor_id">
                    <option value="">— Seleccionar auditor —</option>
                    @foreach($auditores as $a)
                        <option value="{{ $a->id }}" @if(old('auditor_id', $proyecto->auditor_id) == $a->id) selected @endif>
                            {{ $a->nombreCompleto() }} ({{ $a->email }})
                        </option>
                    @endforeach
                </select>
                @error('auditor_id')<p class="form-error">{{ $message }}</p>@enderror
            </div>
        </div>
    </article>

    <div class="flex gap-1 flex-end">
        <a href="{{ url('/proyectos') }}" class="btn btn-secondary">Cancelar</a>
        <button type="submit" class="btn btn-primary">{{ $editando ? 'Guardar cambios' : 'Crear proyecto' }}</button>
    </div>
</form>
@endsection
