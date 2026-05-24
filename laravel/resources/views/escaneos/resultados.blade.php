@extends('layouts.app')
@section('titulo', 'Resultados de escaneo')

@section('contenido')

<nav aria-label="breadcrumb">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="{{ url('/proyectos/'.$escaneo->proyecto->id) }}">{{ $escaneo->proyecto->nombre }}</a></li>
        <li class="breadcrumb-item"><a href="{{ url('/escaneos/'.$escaneo->id) }}">Escaneo #{{ $escaneo->id }}</a></li>
        <li class="breadcrumb-item active" aria-current="page">Resultados</li>
    </ol>
</nav>

<h1 class="page-title">Resultados del escaneo</h1>
<p class="page-subtitle">{{ $escaneo->nombre }}</p>

@php
    $extras     = $escaneo->parametros_nmap ?? [];
    $comando    = $extras['_comando_ejecutado']   ?? null;
    $exportados = $extras['_archivos_exportados'] ?? [];
    $formatoLabel = [
        'xml'      => 'XML (-oX)',
        'nmap'     => 'Normal (-oN)',
        'gnmap'    => 'Grepable (-oG)',
        'gobuster' => 'Gobuster (texto crudo)',
    ];
@endphp

@if($comando || !empty($exportados))
    <article class="card mb-2">
        <header><h2>Ejecución del motor</h2></header>
        @if($comando)
            <p class="text-muted text-xs mb-1">Comando ejecutado:</p>
            <pre class="code-block">{{ $comando }}</pre>
        @endif
        @if(!empty($exportados))
            <p class="text-muted text-xs mt-2 mb-1">Archivos disponibles para descarga:</p>
            <div class="flex gap-1 flex-wrap">
                @foreach($exportados as $formato => $rutaMotor)
                    @php
                        $url = url('/escaneos/'.$escaneo->id.'/export/'.$formato);
                        $lbl = $formatoLabel[$formato] ?? strtoupper($formato);
                    @endphp
                    <a href="{{ $url }}" class="btn btn-secondary">
                        <i class="bi bi-download" aria-hidden="true"></i> {{ $lbl }}
                    </a>
                @endforeach
            </div>
        @endif
    </article>
@endif

<section class="kpi-grid">
    <article class="kpi"><div class="kpi-label">Activos</div><div class="kpi-value">{{ $totalActivos }}</div></article>
    <article class="kpi critical"><div class="kpi-label">Críticas</div><div class="kpi-value">{{ $stats['critica'] }}</div></article>
    <article class="kpi high"><div class="kpi-label">Altas</div><div class="kpi-value">{{ $stats['alta'] }}</div></article>
    <article class="kpi medium"><div class="kpi-label">Medias</div><div class="kpi-value">{{ $stats['media'] }}</div></article>
    <article class="kpi low"><div class="kpi-label">Bajas</div><div class="kpi-value">{{ $stats['baja'] }}</div></article>
</section>

<article class="card">
    <header>
        <h2>Activos descubiertos</h2>
    </header>

    <div class="table-wrap">
        <table class="table">
            <thead>
                <tr>
                    <th>IP</th>
                    <th>MAC</th>
                    <th>Hostname</th>
                    <th>Sistema operativo</th>
                    <th>Puertos abiertos</th>
                    <th>Vulnerabilidades</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
            @forelse($activos as $activo)
                @php
                    $puertosAbiertos = $activo->puertos->where('estado', 'open')->count();
                    $vulnsPorSev = $activo->puertos
                        ->flatMap->vulnerabilidades
                        ->groupBy('severidad')
                        ->map->count();
                    $totalVulns = $vulnsPorSev->sum();
                @endphp
                <tr>
                    <td class="mono">
                        <a href="{{ url('/escaneos/'.$escaneo->id.'/activos/'.$activo->id) }}">
                            {{ $activo->ip ?? '—' }}
                        </a>
                    </td>
                    <td class="mono">{{ $activo->mac ?? '—' }}</td>
                    <td class="mono">{{ $activo->hostname ?? '—' }}</td>
                    <td>{{ $activo->sistema_operativo ?? '—' }}</td>
                    <td class="mono">{{ $puertosAbiertos }}</td>
                    <td>
                        @foreach(['critica','alta','media','baja'] as $sev)
                            @if(($vulnsPorSev[$sev] ?? 0) > 0)
                                <span class="badge badge-{{ $sev }}">{{ $vulnsPorSev[$sev] }}</span>
                            @endif
                        @endforeach
                        @if($totalVulns === 0)<span class="text-muted">—</span>@endif
                    </td>
                    <td>
                        <a href="{{ url('/escaneos/'.$escaneo->id.'/activos/'.$activo->id) }}" class="btn btn-secondary">
                            Ver
                        </a>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="7" class="text-muted">Sin activos descubiertos en este escaneo.</td>
                </tr>
            @endforelse
            </tbody>
        </table>
    </div>

    <div class="d-flex justify-content-center align-items-center mt-2">{{ $activos->onEachSide(2)->links('partials.mi-paginacion') }}</div>
</article>

@endsection
