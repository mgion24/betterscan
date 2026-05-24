@extends('layouts.app')
@section('titulo', 'Exportar informe')

@section('contenido')

<p class="text-muted text-xs mb-2">
    <a href="{{ url('/proyectos/'.$proyecto->id) }}">{{ $proyecto->nombre }}</a> / Exportar informe
</p>

<div class="cols-2">

    <article class="card">
        <header><h2>Configuración</h2></header>

        <form method="POST" action="{{ url('/proyectos/'.$proyecto->id.'/informe') }}" novalidate>
            @csrf

            <div class="form-group">
                <label class="form-label">Tipo de informe <span class="required-marker">*</span></label>
                <label class="form-checkbox block">
                    <input type="radio" name="tipo_informe" value="ejecutivo">
                    <span><strong>Ejecutivo</strong> — resumen de alto nivel para dirección.</span>
                </label>
                <label class="form-checkbox block">
                    <input type="radio" name="tipo_informe" value="tecnico" checked>
                    <span><strong>Técnico</strong> — detalle por activo, puerto y vulnerabilidad.</span>
                </label>
                <label class="form-checkbox block">
                    <input type="radio" name="tipo_informe" value="completo">
                    <span><strong>Completo</strong> — ejecutivo + técnico + anexos.</span>
                </label>
                @error('tipo_informe')<p class="form-error">{{ $message }}</p>@enderror
            </div>

            <button type="submit" class="btn btn-primary w-100">Generar y descargar PDF</button>        </form>
    </article>

    <article class="card">
        <header><h2>Previsualización del contenido</h2></header>

        <dl class="dl-grid compact">
            <dt class="text-muted">Proyecto</dt><dd>{{ $proyecto->nombre }}</dd>
            <dt class="text-muted">Empresa</dt><dd>{{ $proyecto->empresa->nombre_comercial ?? $proyecto->empresa->nombre }}</dd>
            <dt class="text-muted">Auditor</dt><dd>{{ $proyecto->auditor->nombreCompleto() }}</dd>
            <dt class="text-muted">Escaneos</dt><dd>{{ $proyecto->escaneos->count() }}</dd>
            <dt class="text-muted">Activos descubiertos</dt><dd>{{ $totalActivos }}</dd>
        </dl>

        <h4 class="mt-2 mb-1">Vulnerabilidades</h4>
        <div class="grid-2">
            <span class="badge badge-critica">Crítica: {{ $stats['critica'] }}</span>
            <span class="badge badge-alta">Alta: {{ $stats['alta'] }}</span>
            <span class="badge badge-media">Media: {{ $stats['media'] }}</span>
            <span class="badge badge-baja">Baja: {{ $stats['baja'] }}</span>
        </div>
    </article>

</div>

@endsection
