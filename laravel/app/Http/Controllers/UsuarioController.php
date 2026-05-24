<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreUsuarioRequest;
use App\Models\Empresa;
use App\Models\Rol;
use App\Models\Usuario;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class UsuarioController extends Controller
{
    public function index(): View
    {
        $usuarios = Usuario::with(['rol', 'empresa'])
            ->orderBy('id', 'desc')
            ->paginate(20);
        $stats = [
            'total' => Usuario::count(),
            'admin' => $this->contarPorRol('admin'),
            'empleado' => $this->contarPorRol('empleado'),
            'cliente' => $this->contarPorRol('cliente'),
        ];

        return view('usuarios.index', compact('usuarios', 'stats'));
    }

    public function create(): View
    {
        return view('usuarios.form', [
            'usuario' => new Usuario(),
            'roles' => Rol::orderBy('id')->get(),
            'empresas' => Empresa::orderBy('nombre')->get(),
        ]);
    }

    public function store(StoreUsuarioRequest $request): RedirectResponse
    {
        $datos = $request->validated();
        $datos['contrasena_hash'] = Hash::make($datos['password']);
        unset($datos['password']);

        // Si el rol no es cliente la empresa no aplica.
        $rol = Rol::find($datos['rol_id']);
        if ($rol->nombre !== Rol::CLIENTE) {
            $datos['empresa_id'] = null;
        }

        $usuario = Usuario::create($datos);

        return redirect('/usuarios')->with('success', "Usuario {$usuario->email} creado.");
    }

    public function edit(Usuario $usuario): View
    {
        return view('usuarios.form', [
            'usuario' => $usuario,
            'roles' => Rol::orderBy('id')->get(),
            'empresas' => Empresa::orderBy('nombre')->get(),
        ]);
    }

    public function update(StoreUsuarioRequest $request, Usuario $usuario): RedirectResponse
    {
        $datos = $request->validated();

        if (!empty($datos['password'])) {
            $datos['contrasena_hash'] = Hash::make($datos['password']);
        }
        unset($datos['password']);

        $rol = Rol::find($datos['rol_id']);
        if ($rol->nombre !== Rol::CLIENTE) {
            $datos['empresa_id'] = null;
        }

        $usuario->update($datos);

        return redirect('/usuarios')->with('success', "Usuario {$usuario->email} actualizado.");
    }

    public function destroy(Usuario $usuario): RedirectResponse
    {
        $esYoMismo = $usuario->id === Auth::id();

        if (!$esYoMismo) {
            $usuario->delete();
        }

        // Una sola escritura a sesión: ->with() es eager, así que si construyera
        // los dos redirects en variables, ambos flashes acabarían en sesión.
        return $esYoMismo
            ? redirect('/usuarios')->with('error', 'No puedes eliminarte a ti mismo.')
            : redirect('/usuarios')->with('success', 'Usuario eliminado.');
    }

    public function show(Usuario $usuario): RedirectResponse
    {
        return redirect("/usuarios/{$usuario->id}/edit");
    }

    private function contarPorRol(string $nombreRol): int
    {
        return Usuario::whereHas('rol', function ($q) use ($nombreRol) {
            $q->where('nombre', $nombreRol);
        })->count();
    }
}
