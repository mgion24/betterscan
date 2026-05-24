<?php

namespace App\Http\Controllers\Api\Internal;

use App\Http\Controllers\Controller;
use App\Models\Activo;
use App\Models\Escaneo;
use App\Models\Puerto;
use App\Models\Vulnerabilidad;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

// Endpoints internos que llama el motor FastAPI mientras procesa un
// escaneo. Están protegidos por el middleware "internal-token".
class EscaneoCallbackController extends Controller
{
    public function actualizarEstado(Request $request, Escaneo $escaneo): JsonResponse
    {
        $data = $request->validate([
            'estado' => 'required|in:pendiente,en_proceso,completado,error',
            'progreso_pct' => 'nullable|integer|min:0|max:100',
            'fase_actual' => 'nullable|string|max:200',
            'error' => 'nullable|string',
        ]);

        // dd($data); // para ver qué llega del motor en cada actualización

        $update = [
            'estado' => $data['estado'],
            'progreso_pct' => $data['progreso_pct'] ?? $escaneo->progreso_pct,
            'fase_actual' => $data['fase_actual'] ?? $escaneo->fase_actual,
        ];

        $haTerminado = $data['estado'] === 'completado' || $data['estado'] === 'error';
        if ($haTerminado) {
            $update['fecha_fin'] = now();
        }

        if (!empty($data['error'])) {
            $update['error_mensaje'] = $data['error'];
        }

        $escaneo->update($update);

        return response()->json(['ok' => true]);
    }

    public function guardarResultados(Request $request, Escaneo $escaneo): JsonResponse
    {
        $data = $request->validate([
            'activos' => 'required|array',
            'comando' => 'nullable|string|max:2000',
            'exportados' => 'nullable|array',
            'exportados.*' => 'string|max:300',
        ]);

        // Guardamos activos -> puertos -> vulnerabilidades en una transacción
        // para que sea atómico: si algo falla a mitad, se hace rollback.
        DB::transaction(function () use ($escaneo, $data) {
            // Si había resultados previos los limpiamos antes (re-lanzamiento).
            $escaneo->activos()->delete();

            foreach ($data['activos'] as $datosActivo) {
                $activo = Activo::create([
                    'ip' => $datosActivo['ip'] ?? null,
                    'mac' => $datosActivo['mac'] ?? null,
                    'hostname' => $datosActivo['hostname'] ?? null,
                    'sistema_operativo' => $datosActivo['sistema_operativo'] ?? null,
                    'direccion_red' => $datosActivo['direccion_red'] ?? null,
                    'escaneo_id' => $escaneo->id,
                ]);

                $puertosDelActivo = $datosActivo['puertos'] ?? [];
                foreach ($puertosDelActivo as $datosPuerto) {
                    $puerto = Puerto::create([
                        'numero' => $datosPuerto['numero'],
                        'protocolo' => $datosPuerto['protocolo'] ?? 'tcp',
                        'estado' => $datosPuerto['estado'] ?? 'open',
                        'servicio' => $datosPuerto['servicio'] ?? null,
                        'version' => $datosPuerto['version'] ?? null,
                        'activo_id' => $activo->id,
                    ]);

                    $vulnsDelPuerto = $datosPuerto['vulnerabilidades'] ?? [];
                    foreach ($vulnsDelPuerto as $datosVuln) {
                        // Si no nos pasaron la severidad la calculamos del CVSS.
                        $severidad = $datosVuln['severidad']
                            ?? Vulnerabilidad::severidadDesdeCvss($datosVuln['cvss'] ?? null);

                        Vulnerabilidad::create([
                            'cve_asociado' => $datosVuln['cve_asociado'] ?? null,
                            'descripcion' => $datosVuln['descripcion'] ?? null,
                            'cvss' => $datosVuln['cvss'] ?? null,
                            'vector' => $datosVuln['vector'] ?? null,
                            'severidad' => $severidad,
                            'remediacion' => $datosVuln['remediacion'] ?? null,
                            'referencias' => $datosVuln['referencias'] ?? null,
                            'enriquecido_en' => now(),
                            'puerto_id' => $puerto->id,
                        ]);
                    }
                }
            }
        });

        // El comando ejecutado y los archivos exportados los guardamos en
        // parametros_nmap para mostrarlos en resultados sin tocar el esquema.
        $extras = $escaneo->parametros_nmap ?? [];
        if (!empty($data['comando'])) {
            $extras['_comando_ejecutado'] = $data['comando'];
        }
        if (!empty($data['exportados'])) {
            $extras['_archivos_exportados'] = $data['exportados'];
        }
        $escaneo->update(['parametros_nmap' => $extras]);

        return response()->json([
            'ok' => true,
            'persistidos' => count($data['activos']),
        ]);
    }
}
