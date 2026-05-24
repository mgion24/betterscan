@extends('layouts.app')
@section('titulo', 'Escaneos')

@section('contenido')
<article class="card">
    <header>
        <h2>Todos los escaneos</h2>
    </header>
    <div class="table-wrap">
        <table class="table">
            <thead>
                <tr>
                    <th>Nombre</th>
                    <th>Proyecto</th>
                    <th>Plantilla</th>
                    <th>Estado</th>
                    <th>Progreso</th>
                    <th>Inicio</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
            @forelse($escaneos as $e)
                <tr>
                    <td>{{ $e->nombre }}</td>
                    <td>{{ $e->proyecto->nombre }}</td>
                    <td>{{ $e->plantilla_escaneo }}</td>
                    <td><span class="badge badge-{{ $e->estado }}">{{ $e->estado }}</span></td>
                    <td>
                        <progress class="progress-row" max="100" value="{{ $e->progreso_pct }}"></progress>
                    </td>
                    <td>{{ $e->fecha_inicio?->format('d/m/Y H:i') ?? '—' }}</td>
                    <td>
                        <div class="flex gap-1">
                            <a href="{{ url('/escaneos/'.$e->id) }}" class="btn-icon" title="Ver">
                                <i class="bi bi-eye" aria-hidden="true"></i>
                            </a>
                            @if($e->estado === 'pendiente')
                                <a href="{{ url('/escaneos/'.$e->id.'/edit') }}" class="btn-icon" title="Editar">
                                    <i class="bi bi-pencil-square" aria-hidden="true"></i>
                                </a>
                            @endif
                            @if($e->estado === 'completado')
                                <a href="{{ url('/escaneos/'.$e->id.'/resultados') }}" class="btn-icon btn-primary" title="Resultados">
                                    <i class="bi bi-bar-chart-line" aria-hidden="true"></i>
                                </a>
                            @endif
                            @if($e->puedeBorrar(Auth::user()))
                                <form action="{{ url('/escaneos/'.$e->id) }}" method="POST" class="d-inline"
                                      onsubmit="return confirm('¿Eliminar el escaneo «{{ $e->nombre }}»?')">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="btn-icon btn-danger" title="Eliminar">
                                        <i class="bi bi-trash" aria-hidden="true"></i>
                                    </button>
                                </form>
                            @endif
                        </div>
                    </td>
                </tr>
            @empty
                <tr><td colspan="7" class="text-muted">No hay escaneos.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    <div class="d-flex justify-content-center align-items-center mt-2">{{ $escaneos->onEachSide(2)->links('partials.mi-paginacion') }}</div>
</article>
@endsection
