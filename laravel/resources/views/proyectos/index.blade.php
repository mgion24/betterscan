@extends('layouts.app')
@section('titulo', 'Proyectos')

@section('contenido')
<article class="card">
    <header>
        <h2>Listado de proyectos</h2>
        <a href="{{ url('/proyectos/create') }}" class="btn btn-primary">+ Nuevo Proyecto</a>
    </header>

    <div class="table-wrap">
        <table class="table">
            <thead>
                <tr>
                    <th>Nombre</th>
                    <th>Empresa</th>
                    <th>Auditor</th>
                    <th>Tipo</th>
                    <th>Visibilidad</th>
                    <th>Fecha límite</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse($proyectos as $p)
                    @php
                        // La regla la define el modelo Proyecto::puedeGestionar().
                        // El blade solo pregunta — un único sitio decide.
                        $puedeGestionar = $p->puedeGestionar(Auth::user());
                    @endphp
                    <tr>
                        <td><a href="{{ url('/proyectos/'.$p->id) }}">{{ $p->nombre }}</a></td>
                        <td>{{ $p->empresa->nombre_comercial ?? $p->empresa->nombre }}</td>
                        <td>{{ $p->auditor->nombreCompleto() }}</td>
                        <td>{{ $p->tipo_auditoria ?? '—' }}</td>
                        <td><span class="tag">{{ $p->visibilidad }}</span></td>
                        <td>{{ $p->fecha_limite_estimada?->format('d/m/Y') ?? '—' }}</td>
                        <td>
                            <div class="flex gap-1">
                                <a href="{{ url('/proyectos/'.$p->id) }}" class="btn-icon" title="Ver">
                                    <i class="bi bi-eye" aria-hidden="true"></i>
                                </a>
                                @if($puedeGestionar)
                                    <a href="{{ url('/proyectos/'.$p->id.'/edit') }}" class="btn-icon" title="Editar">
                                        <i class="bi bi-pencil-square" aria-hidden="true"></i>
                                    </a>
                                    <form action="{{ url('/proyectos/'.$p->id) }}" method="POST" class="d-inline"
                                          onsubmit="return confirm('¿Eliminar el proyecto «{{ $p->nombre }}»?')">
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
                    <tr><td colspan="7" class="text-muted">No hay proyectos todavía.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="d-flex justify-content-center align-items-center mt-2">{{ $proyectos->onEachSide(2)->links('partials.mi-paginacion') }}</div>
</article>
@endsection
