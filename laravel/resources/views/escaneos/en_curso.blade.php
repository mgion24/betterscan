@extends('layouts.app')
@section('titulo', 'Escaneo: '.$escaneo->nombre)

@section('contenido')

<nav aria-label="breadcrumb">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="{{ url('/proyectos/'.$escaneo->proyecto->id) }}">{{ $escaneo->proyecto->nombre }}</a></li>
        <li class="breadcrumb-item active" aria-current="page">Escaneo #{{ $escaneo->id }}</li>
    </ol>
</nav>

<header class="flex-between mb-2">
    <div>
        <h1 class="page-title">{{ $escaneo->nombre }}</h1>
        <p class="page-subtitle">{{ $escaneo->tipo_escaneo }} · objetivo: <span class="mono">{{ $escaneo->objetivo }}</span></p>
    </div>
    <span class="badge badge-{{ $escaneo->estado }}" data-estado>{{ $escaneo->estado }}</span>
</header>

<article class="card">
    <div class="mb-2">
        <p class="text-secondary text-sm mb-1">Fase actual: <strong data-fase>{{ $escaneo->fase_actual ?? '—' }}</strong></p>
        <progress max="100" value="{{ $escaneo->progreso_pct }}"></progress>
        <p class="text-secondary text-xxs mt-1"><span data-progreso>{{ $escaneo->progreso_pct }}</span>%</p>
    </div>

    @if($escaneo->error_mensaje)
        <div class="alert alert-error">
            <strong>Error:</strong> {{ $escaneo->error_mensaje }}
        </div>
    @endif

    <dl class="dl-grid">
        <dt>Plantilla</dt><dd>{{ $escaneo->plantilla_escaneo }}</dd>
        <dt>Velocidad</dt><dd>{{ $escaneo->velocidad }}</dd>
        <dt>Intensidad</dt><dd>{{ $escaneo->intensidad }}</dd>
        <dt>Exclusiones</dt><dd class="mono">{{ $escaneo->exclusiones ?? '—' }}</dd>
        <dt>Inicio</dt><dd>{{ $escaneo->fecha_inicio?->format('d/m/Y H:i:s') }}</dd>
        <dt>Fin</dt><dd>{{ $escaneo->fecha_fin?->format('d/m/Y H:i:s') ?? '—' }}</dd>
    </dl>

    @if($escaneo->estado === 'completado')
        <div class="mt-2">
            <a href="{{ url('/escaneos/'.$escaneo->id.'/resultados') }}" class="btn btn-primary">Ver resultados</a>
        </div>
    @endif
</article>

@push('scripts')
<script>
    // El JS de polling necesita conocer la URL JSON.
    window.ESCANEO_STATUS_URL = "{{ url('/escaneos/'.$escaneo->id.'/estado.json') }}";
    window.ESCANEO_RESULTADOS_URL = "{{ url('/escaneos/'.$escaneo->id.'/resultados') }}";
    window.ESCANEO_ESTADO_INICIAL = "{{ $escaneo->estado }}";
</script>
<script src="{{ asset('assets/js/escaneo-polling.js') }}"></script>
@endpush

@endsection
