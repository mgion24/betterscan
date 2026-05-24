@extends('layouts.app')
@section('titulo', $proyecto->nombre)

@section('contenido')

<nav aria-label="breadcrumb">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="{{ url('/proyectos') }}">Proyectos</a></li>
        <li class="breadcrumb-item active" aria-current="page">{{ $proyecto->nombre }}</li>
    </ol>
</nav>

@php
    // La regla de quién puede gestionar el proyecto vive en el modelo.
    // El blade solo pregunta, no decide. Si la regla cambia mañana,
    // se toca Proyecto::puedeGestionar() y aquí no hace falta nada.
    $puedeGestionar = $proyecto->puedeGestionar(Auth::user());
@endphp

<header class="flex-between flex-stack-mobile mb-2">
    <div>
        <h1 class="page-title">{{ $proyecto->nombre }}</h1>
        <p class="page-subtitle">{{ $proyecto->empresa->nombre_comercial ?? $proyecto->empresa->nombre }} · Auditor: {{ $proyecto->auditor->nombreCompleto() }}</p>
    </div>
    <div class="flex gap-1">
        @if($puedeGestionar)
            <a href="{{ url('/proyectos/'.$proyecto->id.'/edit') }}" class="btn btn-secondary">Editar</a>
        @endif
        <a href="{{ url('/proyectos/'.$proyecto->id.'/escaneos/crear') }}" class="btn btn-primary">+ Nuevo Escaneo</a>
        <a href="{{ url('/proyectos/'.$proyecto->id.'/informe/exportar') }}" class="btn btn-secondary">Exportar Informe</a>
        @if($puedeGestionar)
            <form action="{{ url('/proyectos/'.$proyecto->id) }}" method="POST" class="d-inline" onsubmit="return confirm('¿Eliminar el proyecto «{{ $proyecto->nombre }}»? Se borrarán también sus escaneos, activos y vulnerabilidades.')">
                @csrf @method('DELETE')
                <button type="submit" class="btn btn-danger">Eliminar</button>
            </form>
        @endif
    </div>
</header>

<section class="kpi-grid">
    <article class="kpi"><div class="kpi-label">Escaneos</div><div class="kpi-value">{{ $totalEscaneos }}</div></article>
    <article class="kpi"><div class="kpi-label">Activos descubiertos</div><div class="kpi-value">{{ $totalActivos }}</div></article>
    <article class="kpi critical"><div class="kpi-label">Vulnerabilidades</div><div class="kpi-value">{{ $totalVulns }}</div></article>
    <article class="kpi"><div class="kpi-label">Informes generados</div><div class="kpi-value">{{ $proyecto->informes->count() }}</div></article>
</section>

<article class="card">
    <nav class="tabs">
        <button data-tab="escaneos" class="active">Escaneos</button>
        <button data-tab="informes">Informes</button>
        <button data-tab="info">Información</button>
    </nav>

    <div id="tab-escaneos" class="tab-pane active">
        <div class="table-wrap">
            <table class="table">
                <thead>
                    <tr>
                        <th>Nombre</th>
                        <th>Objetivo</th>
                        <th>Plantilla</th>
                        <th>Estado</th>
                        <th>Inicio</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                @forelse($proyecto->escaneos as $e)
                    <tr>
                        <td>{{ $e->nombre }}</td>
                        <td class="mono">{{ $e->objetivo }}</td>
                        <td>{{ $e->plantilla_escaneo ?? '—' }}</td>
                        <td><span class="badge badge-{{ $e->estado }}">{{ $e->estado }}</span></td>
                        <td>{{ $e->fecha_inicio?->format('d/m/Y H:i') ?? '—' }}</td>
                        <td>
                            <div class="flex gap-1">
                                <a href="{{ url('/escaneos/'.$e->id) }}" class="btn-icon" title="Ver escaneo" aria-label="Ver escaneo">
                                    <i class="bi bi-eye" aria-hidden="true"></i>
                                </a>
                                @if($e->estado === 'pendiente')
                                    <a href="{{ url('/escaneos/'.$e->id.'/edit') }}" class="btn-icon" title="Editar escaneo" aria-label="Editar escaneo">
                                        <i class="bi bi-pencil-square" aria-hidden="true"></i>
                                    </a>
                                @endif
                                @if($e->estado === 'completado')
                                    <a href="{{ url('/escaneos/'.$e->id.'/resultados') }}" class="btn-icon btn-primary" title="Ver resultados" aria-label="Ver resultados">
                                        <i class="bi bi-bar-chart-line" aria-hidden="true"></i>
                                    </a>
                                @endif
                                @if($e->puedeBorrar(Auth::user()))
                                    <form action="{{ url('/escaneos/'.$e->id) }}" method="POST" class="d-inline" onsubmit="return confirm('¿Eliminar el escaneo «{{ $e->nombre }}»?')">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="btn-icon btn-danger" title="Eliminar escaneo" aria-label="Eliminar escaneo">
                                            <i class="bi bi-trash" aria-hidden="true"></i>
                                        </button>
                                    </form>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="text-muted">Sin escaneos aún. <a href="{{ url('/proyectos/'.$proyecto->id.'/escaneos/crear') }}">Lanzar el primero →</a></td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div id="tab-informes" class="tab-pane">
        <div class="table-wrap">
            <table class="table">
                <thead>
                    <tr>
                        <th>Tipo</th>
                        <th>Fecha</th>
                        <th>Emitido por</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                @forelse($proyecto->informes as $i)
                    <tr>
                        <td>{{ $i->tipo_informe }}</td>
                        <td>{{ $i->fecha_creacion->format('d/m/Y H:i') }}</td>
                        <td>{{ $i->emisor->nombreCompleto() }}</td>
                        <td>
                            <div class="flex gap-1">
                                <a href="{{ url('/informes/'.$i->id.'/descargar') }}" class="btn btn-secondary">Descargar PDF</a>
                                @if($i->puedeBorrar(Auth::user()))
                                    <form action="{{ url('/informes/'.$i->id) }}" method="POST" class="d-inline" onsubmit="return confirm('¿Eliminar este informe? Dejará de estar disponible para el cliente.')">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="btn btn-danger">Eliminar</button>
                                    </form>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="text-muted">Sin informes generados.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div id="tab-info" class="tab-pane">
        <dl class="dl-grid">
            <dt>Tipo de auditoría</dt><dd>{{ $proyecto->tipo_auditoria ?? '—' }}</dd>
            <dt>Visibilidad</dt><dd><span class="tag">{{ $proyecto->visibilidad }}</span></dd>
            <dt>Fecha límite</dt><dd>{{ $proyecto->fecha_limite_estimada?->format('d/m/Y') ?? '—' }}</dd>
            <dt>Etiquetas</dt><dd>
                @foreach($proyecto->etiquetasArray() as $tag)
                    <span class="tag">{{ $tag }}</span>
                @endforeach
            </dd>
            <dt>Alcance de red</dt><dd class="mono">{{ $proyecto->alcance_red ?? '—' }}</dd>
            <dt>Excepciones</dt><dd class="mono">{{ $proyecto->excepciones_red ?? '—' }}</dd>
            <dt>Descripción</dt><dd>{{ $proyecto->descripcion ?? '—' }}</dd>
        </dl>
    </div>
</article>

@endsection
