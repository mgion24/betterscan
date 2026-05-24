@extends('layouts.app')
@section('titulo', $proyecto->nombre)

@section('contenido')

<p class="text-muted text-xs mb-2">
    <a href="{{ url('/portal') }}">Mi portal</a> / {{ $proyecto->nombre }}
</p>

<article class="card mb-2">
    <header><h2>{{ $proyecto->nombre }}</h2></header>
    <dl class="dl-grid medium">
        <dt class="text-muted">Descripción</dt><dd>{{ $proyecto->descripcion ?? '—' }}</dd>
        <dt class="text-muted">Tipo</dt><dd>{{ $proyecto->tipo_auditoria ?? '—' }}</dd>
        <dt class="text-muted">Fecha límite</dt><dd>{{ $proyecto->fecha_limite_estimada?->format('d/m/Y') ?? '—' }}</dd>
        <dt class="text-muted">Escaneos realizados</dt><dd>{{ $proyecto->escaneos->count() }}</dd>
    </dl>
</article>

<article class="card">
    <header><h2>Informes disponibles</h2></header>
    @if($proyecto->informes->count() === 0)
        <p class="text-muted">No hay informes generados todavía para este proyecto.</p>
    @else
        <div class="table-wrap">
            <table class="table">
                <thead><tr><th>Tipo</th><th>Fecha</th><th>Emitido por</th><th></th></tr></thead>
                <tbody>
                @foreach($proyecto->informes as $i)
                    <tr>
                        <td>{{ $i->tipo_informe }}</td>
                        <td>{{ $i->fecha_creacion->format('d/m/Y H:i') }}</td>
                        <td>{{ $i->emisor->nombreCompleto() }}</td>
                        <td><a href="{{ url('/portal/informes/'.$i->id) }}" class="btn btn-primary">Descargar PDF</a></td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    @endif
</article>

@endsection
