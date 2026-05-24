@extends('layouts.app')
@section('titulo', 'Clientes')

@section('contenido')
<article class="card">
    <header>
        <h2>Empresas cliente</h2>
        <a href="{{ url('/clientes/create') }}" class="btn btn-primary">+ Nuevo Cliente</a>
    </header>

    <div class="table-wrap">
        <table class="table">
            <thead>
                <tr>
                    <th>Nombre</th>
                    <th>CIF</th>
                    <th>Sector</th>
                    <th>Responsable</th>
                    <th>Proyectos</th>
                    <th>Estado</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse($empresas as $e)
                    <tr>
                        <td>
                            <strong>{{ $e->nombre_comercial ?? $e->nombre }}</strong><br>
                            <span class="text-muted text-xxs">{{ $e->razon_social }}</span>
                        </td>
                        <td class="mono">{{ $e->cif ?? '—' }}</td>
                        <td>{{ $e->sector ?? '—' }}</td>
                        <td>
                            {{ $e->responsable_nombre ?? '—' }}<br>
                            <span class="text-muted text-xxs">{{ $e->responsable_email }}</span>
                        </td>
                        <td>{{ $e->proyectos_count }}</td>
                        <td>
                            @if($e->activo)
                                <span class="badge badge-completado">activa</span>
                            @else
                                <span class="badge badge-pendiente">inactiva</span>
                            @endif
                        </td>
                        <td>
                            <div class="flex gap-1">
                                <a href="{{ url('/clientes/'.$e->id.'/edit') }}" class="btn-icon" title="Editar">
                                    <i class="bi bi-pencil-square" aria-hidden="true"></i>
                                </a>
                                <form action="{{ url('/clientes/'.$e->id) }}" method="POST" class="d-inline"
                                      onsubmit="return confirm('¿Eliminar la empresa «{{ $e->nombre_comercial ?? $e->nombre }}»?')">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="btn-icon btn-danger" title="Eliminar">
                                        <i class="bi bi-trash" aria-hidden="true"></i>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="text-muted">Sin empresas registradas.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="d-flex justify-content-center align-items-center mt-2">{{ $empresas->onEachSide(2)->links('partials.mi-paginacion') }}</div>
</article>
@endsection
