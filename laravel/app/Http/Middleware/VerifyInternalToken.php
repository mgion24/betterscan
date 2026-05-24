<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

// Verifica el Bearer token que el motor FastAPI envía al llamar a las
// rutas internas. El token está en el .env (INTERNAL_TOKEN) y es el
// mismo que Laravel envía al motor.
class VerifyInternalToken
{
    public function handle(Request $request, Closure $next): Response
    {
        $esperado = (string) config('services.fastapi.token');
        $recibido = (string) $request->bearerToken();

        // var_dump($esperado, $recibido); // para depurar problemas de auth
        $tokenValido = false;
        if ($esperado !== '' && $recibido !== '') {
            // hash_equals compara en tiempo constante (evita ataques por tiempo).
            $tokenValido = hash_equals($esperado, $recibido);
        }

        $respuesta = response()->json(['error' => 'Unauthorized'], 401);
        if ($tokenValido) {
            $respuesta = $next($request);
        }

        return $respuesta;
    }
}
