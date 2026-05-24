@extends('layouts.app')
@section('titulo', 'Usuarios y roles')

@section('contenido')

<section class="kpi-grid">
    <article class="kpi"><div class="kpi-label">Total usuarios</div><div class="kpi-value">{{ $stats['total'] }}</div></article>
    <article class="kpi"><div class="kpi-label">Administradores</div><div class="kpi-value">{{ $stats['admin'] }}</div></article>
    <article class="kpi"><div class="kpi-label">Auditores</div><div class="kpi-value">{{ $stats['empleado'] }}</div></article>
    <article class="kpi"><div class="kpi-label">Clientes</div><div class="kpi-value">{{ $stats['cliente'] }}</div></article>
</section>

<article class="card">
    <header>
        <h2>Listado</h2>
        <a href="{{ url('/usuarios/create') }}" class="btn btn-primary">+ Nuevo usuario</a>
    </header>

    <div class="table-wrap">
        <table class="table">
            <thead>
                <tr>
                    <th></th>
                    <th>Nombre</th>
                    <th>Email</th>
                    <th>Rol</th>
                    <th>Empresa</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @foreach($usuarios as $u)
                    <tr>
                        <td>
                            <div class="avatar-sm">{{ $u->iniciales() }}</div>
                        </td>
                        <td>{{ $u->nombreCompleto() }}</td>
                        <td>{{ $u->email }}</td>
                        <td><span class="tag">{{ $u->rol->nombre }}</span></td>
                        <td>{{ $u->empresa->nombre_comercial ?? $u->empresa->nombre ?? '—' }}</td>
                        <td>
                            <div class="flex gap-1">
                                <a href="{{ url('/usuarios/'.$u->id.'/edit') }}" class="btn-icon" title="Editar">
                                    <i class="bi bi-pencil-square" aria-hidden="true"></i>
                                </a>
                                @if($u->id !== auth()->id())
                                    <form action="{{ url('/usuarios/'.$u->id) }}" method="POST" class="d-inline"
                                          onsubmit="return confirm('¿Eliminar el usuario {{ $u->email }}?')">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="btn-icon btn-danger" title="Eliminar">
                                            <i class="bi bi-trash" aria-hidden="true"></i>
                                        </button>
                                    </form>
                                @endif
                            </div>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <div class="d-flex justify-content-center align-items-center mt-2">{{ $usuarios->onEachSide(2)->links('partials.mi-paginacion') }}</div>
</article>
@endsection
