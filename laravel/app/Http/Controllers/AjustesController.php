<?php

namespace App\Http\Controllers;

use App\Models\Escaneo;
use App\Models\Proyecto;
use App\Models\Usuario;
use App\Models\Vulnerabilidad;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class AjustesController extends Controller
{
    public function show(): View
    {
        /** @var Usuario $usuario */
        $usuario = Auth::user();

        $data = [
            'usuario' => $usuario,
            'fastapiUrl' => config('services.fastapi.base_url'),
            'tieneApiKeyNvd' => (bool) config('services.nvd.api_key'),
        ];

        // Los KPIs y versiones solo se muestran al admin.
        if ($usuario->esAdmin()) {
            $data['stats'] = $this->kpisDelAdmin();
            $data['versiones'] = [
                'php' => PHP_VERSION,
                'laravel' => app()->version(),
                'mariadb' => $this->versionMariaDb(),
            ];
        }

        return view('ajustes', $data);
    }

    public function actualizarPerfil(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'nombre' => 'required|string|max:100',
            'apellido' => 'required|string|max:100',
            'telefono' => 'nullable|string|max:20|regex:/^(\+34[ \-]?)?[6-9]\d{2}[ \-]?\d{3}[ \-]?\d{3}$/',
        ], [
            'telefono.regex' => 'El teléfono debe tener formato español: 9 dígitos empezando por 6, 7, 8 o 9. Admite prefijo +34.',
        ]);

        // @var Usuario $usuario sirve para que el IDE entienda que $usuario es un modelo Usuario y ofrezca autocompletado de sus campos.
        /** @var Usuario $usuario */
        $usuario = Auth::user();
        $usuario->update($data);

        return back()->with('success', 'Perfil actualizado correctamente.');
    }

    public function cambiarPassword(Request $request): RedirectResponse
    {
        $reglas = [
            'password_actual' => 'required|string',
            'password' => ['required', 'string', 'min:8', 'regex:/[A-Z]/', 'regex:/[0-9]/', 'confirmed'],
        ];

        $mensajes = [
            'password_actual.required' => 'Tienes que introducir la contraseña actual.',
            'password.required' => 'Tienes que introducir la nueva contraseña.',
            'password.min' => 'La nueva contraseña debe tener al menos 8 caracteres.',
            'password.regex' => 'La nueva contraseña debe incluir al menos una mayúscula y un número.',
            'password.confirmed' => 'La confirmación no coincide con la nueva contraseña.',
        ];

        $data = $request->validate($reglas, $mensajes);

        /** @var Usuario $usuario */
        $usuario = Auth::user();

        // var_dump(Hash::check($data['password_actual'], $usuario->contrasena_hash));
        $passwordActualOk = Hash::check($data['password_actual'], $usuario->contrasena_hash);
        $respuesta = back()->with('success', 'Contraseña actualizada correctamente.');

        if (!$passwordActualOk) {
            $respuesta = back()->withErrors([
                'password_actual' => 'La contraseña actual no es correcta.',
            ]);
        } else {
            $usuario->update(['contrasena_hash' => Hash::make($data['password'])]);
        }

        return $respuesta;
    }

    private function kpisDelAdmin(): array
    {
        $totalAdmin = Usuario::whereHas('rol', function ($q) {
            $q->where('nombre', 'admin');
        })->count();

        return [
            'usuarios' => Usuario::count(),
            'admin' => $totalAdmin,
            'proyectos' => Proyecto::count(),
            'escaneos' => Escaneo::count(),
            'vulnerab' => Vulnerabilidad::count(),
            'sesiones' => DB::table('sessions')->whereNotNull('user_id')->count(),
        ];
    }

    private function versionMariaDb(): string
    {
        $version = 'no disponible';

        try {
            $resultado = DB::selectOne('SELECT VERSION() AS v');
            $version = $resultado->v ?? 'desconocida';
        } catch (\Throwable $e) {
            // Si la BD no responde dejamos "no disponible".
        }

        return $version;
    }
}
