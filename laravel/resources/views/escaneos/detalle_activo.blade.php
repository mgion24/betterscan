@extends('layouts.app')
@section('titulo', 'Activo '.($activo->ip ?? '#'.$activo->id))

@section('contenido')

@php
    $proyecto = $escaneo->proyecto;
@endphp

<nav aria-label="breadcrumb">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="{{ url('/proyectos/'.$proyecto->id) }}">{{ $proyecto->nombre }}</a></li>
        <li class="breadcrumb-item"><a href="{{ url('/escaneos/'.$escaneo->id.'/resultados') }}">Resultados</a></li>
        <li class="breadcrumb-item active" aria-current="page">{{ $activo->ip ?? '#'.$activo->id }}</li>
    </ol>
</nav>

<h1 class="page-title">{{ $activo->ip ?? 'Activo #'.$activo->id }}</h1>
<p class="page-subtitle">
    {{ $activo->hostname ?? 'sin hostname' }} · {{ $activo->sistema_operativo ?? 'SO desconocido' }}
</p>

<article class="card mb-2">
    <header><h2>Información del activo</h2></header>
    <dl class="dl-grid compact">
        <dt>IP</dt>
        <dd class="mono">{{ $activo->ip ?? '—' }}</dd>

        <dt>MAC</dt>
        <dd class="mono">{{ $activo->mac ?? '—' }}</dd>

        <dt>Hostname</dt>
        <dd class="mono">{{ $activo->hostname ?? '—' }}</dd>

        <dt>Sistema operativo</dt>
        <dd>{{ $activo->sistema_operativo ?? '—' }}</dd>

        <dt>Red</dt>
        <dd class="mono">{{ $activo->direccion_red ?? '—' }}</dd>
    </dl>
</article>

<section class="kpi-grid">
    <article class="kpi">
        <div class="kpi-label">Puertos abiertos</div>
        <div class="kpi-value">{{ $activo->puertos->where('estado', 'open')->count() }}</div>
    </article>
    <article class="kpi critical"><div class="kpi-label">Críticas</div><div class="kpi-value">{{ $stats['critica'] }}</div></article>
    <article class="kpi high"><div class="kpi-label">Altas</div><div class="kpi-value">{{ $stats['alta'] }}</div></article>
    <article class="kpi medium"><div class="kpi-label">Medias</div><div class="kpi-value">{{ $stats['media'] }}</div></article>
    <article class="kpi low"><div class="kpi-label">Bajas</div><div class="kpi-value">{{ $stats['baja'] }}</div></article>
</section>

<article class="card mb-2">
    <header><h2>Puertos y servicios</h2></header>
    <div class="table-wrap">
        <table class="table">
            <thead>
                <tr>
                    <th>Puerto</th>
                    <th>Protocolo</th>
                    <th>Estado</th>
                    <th>Servicio</th>
                    <th>Versión</th>
                    <th>Vulns</th>
                </tr>
            </thead>
            <tbody>
            @forelse($activo->puertos as $p)
                <tr>
                    <td class="mono">{{ $p->numero }}</td>
                    <td class="mono">{{ $p->protocolo }}</td>
                    <td><span class="badge badge-state-{{ $p->estado }}">{{ $p->estado }}</span></td>
                    <td>{{ $p->servicio ?? '—' }}</td>
                    <td class="mono">{{ $p->version ?? '—' }}</td>
                    <td class="mono">{{ $p->vulnerabilidades->count() }}</td>
                </tr>
            @empty
                <tr><td colspan="6" class="text-muted">Sin puertos detectados.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</article>

<article class="card">
    <header>
        <h2>Vulnerabilidades del activo</h2>
    </header>

    <div class="table-wrap">
        <table class="table">
            <thead>
                <tr>
                    <th>CVE</th>
                    <th>Severidad</th>
                    <th>CVSS</th>
                    <th>Descripción</th>
                    <th>Puerto</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
            @forelse($vulns as $v)
                <tr>
                    <td class="mono">
                        <a href="{{ url('/vulnerabilidades/'.$v->id) }}">
                            {{ $v->cve_asociado ?? 'Sin CVE' }}
                        </a>
                    </td>
                    <td><span class="badge badge-{{ $v->severidad }}">{{ $v->severidad ?? '—' }}</span></td>
                    <td class="mono">{{ $v->cvss !== null ? number_format($v->cvss, 1) : '—' }}</td>
                    <td>{{ Str::limit($v->descripcion, 80) ?? '—' }}</td>
                    <td class="mono">{{ $v->puerto->numero }}/{{ $v->puerto->protocolo }}</td>
                    <td><a href="{{ url('/vulnerabilidades/'.$v->id) }}" class="btn btn-secondary">Ver</a></td>
                </tr>
            @empty
                <tr><td colspan="6" class="text-muted">Sin vulnerabilidades detectadas en este activo.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>

    <div class="d-flex justify-content-center align-items-center mt-2">{{ $vulns->onEachSide(2)->links('partials.mi-paginacion') }}</div>
</article>

@endsection
