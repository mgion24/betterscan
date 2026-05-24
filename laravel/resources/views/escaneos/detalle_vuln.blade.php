@extends('layouts.app')
@section('titulo', 'Vulnerabilidad: '.($vulnerabilidad->cve_asociado ?? '#'.$vulnerabilidad->id))

@section('contenido')

@php
    $v = $vulnerabilidad;
    $puerto = $v->puerto;
    $activo = $puerto->activo;
    $escaneo = $activo->escaneo;
    $proyecto = $escaneo->proyecto;
    $refs = $v->referencias ? array_filter(explode("\n", $v->referencias)) : [];
@endphp

<nav aria-label="breadcrumb">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="{{ url('/proyectos/'.$proyecto->id) }}">{{ $proyecto->nombre }}</a></li>
        <li class="breadcrumb-item"><a href="{{ url('/escaneos/'.$escaneo->id.'/resultados') }}">Resultados</a></li>
        <li class="breadcrumb-item"><a href="{{ url('/escaneos/'.$escaneo->id.'/activos/'.$activo->id) }}">{{ $activo->ip ?? '#'.$activo->id }}</a></li>
        <li class="breadcrumb-item active" aria-current="page">{{ $v->cve_asociado ?? '#'.$v->id }}</li>
    </ol>
</nav>

<header class="flex-between mb-2">
    <h1 class="page-title">{{ $v->cve_asociado ?? 'Vulnerabilidad #'.$v->id }}</h1>
    <span class="badge badge-{{ $v->severidad }}">{{ $v->severidad ?? 'sin clasificar' }}</span>
</header>

<div class="cols-2">

    <article class="card">
        <header><h3>Información técnica</h3></header>
        <dl class="dl-grid compact">
            <dt>CVE</dt>
            <dd class="mono">{{ $v->cve_asociado ?? '—' }}</dd>

            <dt>CVSS v3.1</dt>
            <dd class="mono">{{ $v->cvss !== null ? number_format($v->cvss, 1) : '—' }}</dd>

            <dt>Vector</dt>
            <dd class="mono break-all">{{ $v->vector ?? '—' }}</dd>

            <dt>Host afectado</dt>
            <dd class="mono">{{ $activo->ip }} ({{ $activo->hostname }})</dd>

            <dt>Servicio</dt>
            <dd class="mono">{{ $puerto->numero }}/{{ $puerto->protocolo }} · {{ $puerto->servicio ?? '?' }} {{ $puerto->version }}</dd>

            <dt>SO del host</dt>
            <dd>{{ $activo->sistema_operativo ?? '—' }}</dd>

            <dt>Enriquecido</dt>
            <dd>{{ $v->enriquecido_en?->format('d/m/Y H:i') ?? '—' }}</dd>
        </dl>
    </article>

    <article class="card">
        <header><h3>Puntuación CVSS</h3></header>
        <div class="cvss-block">
            <div class="cvss-score severity-{{ $v->severidad ?? 'nada' }}">
                {{ $v->cvss !== null ? number_format($v->cvss, 1) : '—' }}
            </div>
            <div class="cvss-label">
                <span class="badge badge-{{ $v->severidad }}">{{ $v->severidad ?? 'sin clasificar' }}</span>
            </div>
            <p class="text-muted text-xxs mt-1">Datos obtenidos de NVD (NIST).</p>
        </div>
    </article>

</div>

<article class="card mb-2">
    <header><h3>Descripción</h3></header>
    <p class="text-readable">{{ $v->descripcion ?? 'Sin descripción disponible.' }}</p>
</article>

@if($v->remediacion)
    <article class="card mb-2">
        <header><h3>Remediación recomendada</h3></header>
        <p>{{ $v->remediacion }}</p>
    </article>
@endif

@if(count($refs))
    <article class="card">
        <header><h3>Referencias oficiales</h3></header>
        <ul class="list-disc">
            @foreach($refs as $url)
                <li><a href="{{ trim($url) }}" target="_blank" rel="noopener" class="mono-sm">{{ trim($url) }}</a></li>
            @endforeach
        </ul>
    </article>
@endif

@endsection
