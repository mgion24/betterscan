@extends('layouts.app')
@section('titulo', 'Dashboard')

@section('contenido')

{{-- KPIs --}}
<div class="kpi-grid" aria-label="Indicadores principales">
    <article class="kpi">
        <div class="kpi-label">Proyectos</div>
        <div class="kpi-value">{{ $totalProyectos }}</div>
        <div class="kpi-hint">Total registrados</div>
    </article>
    <article class="kpi">
        <div class="kpi-label">Escaneos activos</div>
        <div class="kpi-value">{{ $escaneosActivos }}</div>
        <div class="kpi-hint">Pendientes o en proceso</div>
    </article>
    <article class="kpi">
        <div class="kpi-label">Vulnerabilidades</div>
        <div class="kpi-value">{{ $totalVulns }}</div>
        <div class="kpi-hint">Detectadas en total</div>
    </article>
    <article class="kpi critical">
        <div class="kpi-label">Críticas / Altas</div>
        <div class="kpi-value">{{ $vulnsCriticas }}</div>
        <div class="kpi-hint">Requieren atención</div>
    </article>
</div>

<div class="cols-2">

    {{-- Últimos proyectos --}}
    <article class="card">
        <header>
            <h2>Últimos proyectos</h2>
            <a href="{{ url('/proyectos') }}" class="text-muted text-xs">Ver todos →</a>
        </header>
        <div class="table-wrap">
            <table class="table">
                <thead>
                    <tr>
                        <th>Proyecto</th>
                        <th>Empresa</th>
                        <th>Auditor</th>
                    </tr>
                </thead>
                <tbody>
                @forelse($ultimosProyectos as $p)
                    <tr>
                        <td><a href="{{ url('/proyectos/'.$p->id) }}">{{ $p->nombre }}</a></td>
                        <td>{{ $p->empresa->nombre_comercial ?? $p->empresa->nombre }}</td>
                        <td>{{ $p->auditor->nombreCompleto() }}</td>
                    </tr>
                @empty
                    <tr><td colspan="3" class="text-muted">Sin proyectos todavía.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </article>

    {{-- Últimos escaneos --}}
    <article class="card">
        <header>
            <h2>Escaneos recientes</h2>
            <a href="{{ url('/escaneos') }}" class="text-muted text-xs">Ver todos →</a>
        </header>
        <div class="table-wrap">
            <table class="table">
                <thead>
                    <tr>
                        <th>Escaneo</th>
                        <th>Proyecto</th>
                        <th>Estado</th>
                    </tr>
                </thead>
                <tbody>
                @forelse($ultimosEscaneos as $e)
                    <tr>
                        <td><a href="{{ url('/escaneos/'.$e->id) }}">{{ $e->nombre }}</a></td>
                        <td>{{ $e->proyecto->nombre }}</td>
                        <td><span class="badge badge-{{ $e->estado }}">{{ $e->estado }}</span></td>
                    </tr>
                @empty
                    <tr><td colspan="3" class="text-muted">No hay escaneos.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </article>

</div>

{{-- Severidades en barras simples --}}
<article class="card">
    <header>
        <h2>Vulnerabilidades por severidad</h2>
    </header>
    @php
        $max = max(1, max($porSeveridad));
    @endphp
    <div class="severity-stack">
        @foreach(['critica' => 'Crítica', 'alta' => 'Alta', 'media' => 'Media', 'baja' => 'Baja'] as $key => $label)
            <div class="severity-bar">
                <span class="badge badge-{{ $key }}">{{ $label }}</span>
                <progress class="bar-{{ $key }}" max="{{ $max }}" value="{{ $porSeveridad[$key] }}"></progress>
                <span class="mono">{{ $porSeveridad[$key] }}</span>
            </div>
        @endforeach
    </div>
</article>

@endsection
