<?php

namespace App\Services;

use App\Models\Escaneo;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

// Cliente HTTP que habla con el motor FastAPI.
// Las dos partes comparten un token (INTERNAL_TOKEN del .env) que va
// en cada petición como cabecera "Authorization: Bearer ...".
class FastApiClient
{
    public function lanzarEscaneo(Escaneo $escaneo): bool
    {
        // services.fastapi es la configuración del motor FastAPI que tenemos en config/services.php, donde leemos las variables del .env. Ahí definimos la URL base del motor (base_url), el token de autenticación (token) y la URL base para callbacks (callback_base) que el motor usará para enviarnos actualizaciones de estado y resultados. Esto nos permite tener toda la configuración centralizada y fácil de cambiar sin tocar el código.
        $url = config('services.fastapi.base_url') . '/scan/start';
        $token = config('services.fastapi.token');
        $callback = config('services.fastapi.callback_base') . '/api/internal/escaneo/' . $escaneo->id;

        $payload = [
            'escaneo_id' => $escaneo->id,
            'objetivo' => $escaneo->objetivo,
            'parametros' => $escaneo->parametros_nmap,
            'callback_url' => $callback,
        ];

        // dd($payload); // para ver qué se le manda al motor

        $ok = false;

        try {
            $respuesta = Http::withToken($token)
                ->timeout(5)
                ->retry(2, 200)
                ->post($url, $payload);

            $ok = $respuesta->successful();
            if (!$ok) {
                Log::warning('FastAPI rechazó el escaneo', [
                    'escaneo_id' => $escaneo->id,
                    'status' => $respuesta->status(),
                ]);
            }
        } catch (\Throwable $e) {
            Log::error('Error contactando con FastAPI: ' . $e->getMessage());
        }

        return $ok;
    }

    public function listarInterfaces(): array
    {
        $url = config('services.fastapi.base_url') . '/network/interfaces';
        $token = config('services.fastapi.token');

        $interfaces = [];

        try {
            $respuesta = Http::withToken($token)->timeout(3)->get($url);

            if ($respuesta->successful()) {
                $interfaces = $respuesta->json('interfaces', []);
            }
        } catch (\Throwable $e) {
            // Si el motor está caído devolvemos array vacío.
        }

        return $interfaces;
    }
}
