@extends('layouts.app')
@section('titulo', 'Resultados de búsqueda')

@section('contenido')
<p class="text-muted mb-2">Buscando: <strong>{{ $q }}</strong></p>

<article class="card mb-2">
    <header><h2>Proyectos</h2></header>
    @if($proyectos->count())
        <ul class="list-none">
            @foreach($proyectos as $p)
                <li class="mb-1"><a href="{{ url('/proyectos/'.$p->id) }}">{{ $p->nombre }}</a></li>
            @endforeach
        </ul>
    @else
        <p class="text-muted">Sin resultados.</p>
    @endif
</article>

<article class="card">
    <header><h2>Vulnerabilidades</h2></header>
    @if($vulns->count())
        <ul class="list-none">
            @foreach($vulns as $v)
                <li class="mb-1"><a href="{{ url('/vulnerabilidades/'.$v->id) }}">{{ $v->cve_asociado ?? 'Sin CVE' }} — {{ Str::limit($v->descripcion, 80) }}</a></li>
            @endforeach
        </ul>
    @else
        <p class="text-muted">Sin resultados.</p>
    @endif
</article>
@endsection
