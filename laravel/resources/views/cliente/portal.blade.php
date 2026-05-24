@extends('layouts.app')
@section('titulo', 'Mi portal')

@section('contenido')

<div class="cols-2 mb-2">
    <article class="kpi">
        <div class="kpi-label">Proyectos visibles</div>
        <div class="kpi-value">{{ $proyectos->count() }}</div>
    </article>
    <article class="kpi">
        <div class="kpi-label">Informes disponibles</div>
        <div class="kpi-value">{{ $totalInformes }}</div>
    </article>
</div>

<article class="card mb-2">
    <header><h2>Mis proyectos</h2></header>

    @if($proyectos->count() === 0)
        <p class="text-muted">Aún no hay proyectos compartidos contigo. Cuando el auditor marque un proyecto como "visible al cliente" aparecerá aquí.</p>
    @else
        @foreach($proyectos as $p)
            <article class="portal-item">
                <header class="flex-between">
                    <div>
                        <strong>{{ $p->nombre }}</strong>
                        <p class="text-muted text-xs mt-xxs">{{ Str::limit($p->descripcion, 120) }}</p>
                    </div>
                    <a href="{{ url('/portal/proyectos/'.$p->id) }}" class="btn btn-secondary">Ver detalle</a>
                </header>
            </article>
        @endforeach
    @endif
</article>

<article class="card">
    <header><h2>Informes recientes</h2></header>
    @php
        $informes = $proyectos->flatMap->informes->sortByDesc('fecha_creacion')->take(10);
    @endphp

    @if($informes->isEmpty())
        <p class="text-muted">No hay informes disponibles para descargar.</p>
    @else
        <div class="table-wrap">
            <table class="table">
                <thead>
                    <tr><th>Tipo</th><th>Proyecto</th><th>Fecha</th><th></th></tr>
                </thead>
                <tbody>
                @foreach($informes as $i)
                    <tr>
                        <td>{{ $i->tipo_informe }}</td>
                        <td>{{ $i->proyecto->nombre }}</td>
                        <td>{{ $i->fecha_creacion->format('d/m/Y H:i') }}</td>
                        <td><a href="{{ url('/portal/informes/'.$i->id) }}" class="btn btn-primary">Descargar PDF</a></td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    @endif
</article>

@endsection
