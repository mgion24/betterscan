<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

// Filtro que limita una ruta a uno o varios roles.
// Uso en routes/web.php: ->middleware('role:admin,empleado')
class EnsureRole
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $usuario = $request->user();
        $tieneAcceso = false;

        if ($usuario && $usuario->rol) {
            $tieneAcceso = in_array($usuario->rol->nombre, $roles, true);
        }

        if (!$tieneAcceso) {
            abort(403, 'Acceso denegado.');
        }

        return $next($request);
    }
}
