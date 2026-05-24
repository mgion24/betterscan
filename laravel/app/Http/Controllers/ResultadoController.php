<?php

namespace App\Http\Controllers;

use App\Models\Activo;
use App\Models\Escaneo;
use App\Models\Puerto;
use App\Models\Vulnerabilidad;
use Illuminate\View\View;

class ResultadoController extends Controller
{
    // Listado de ACTIVOS descubiertos en el escaneo. Cada fila lleva
    // resumen de puertos abiertos y conteo de vulns por severidad.
    public function index(Escaneo $escaneo): View
    {
        $escaneo->load('proyecto');

        $activos = $escaneo->activos()
            ->with(['puertos.vulnerabilidades'])
            ->paginate(25);

        // KPIs globales del escaneo (suma de vulns en todos los activos).
        $idsActivos = $escaneo->activos()->pluck('id');
        $puertosIds = Puerto::whereIn('activo_id', $idsActivos)->pluck('id');

        $stats = $this->statsPorSeveridad($puertosIds);
        $totalActivos = $escaneo->activos()->count();

        return view('escaneos.resultados', compact(
            'escaneo', 'activos', 'stats', 'totalActivos'
        ));
    }

    // Detalle de un ACTIVO concreto: información, puertos+servicios y
    // tabla de vulnerabilidades.
    public function detalleActivo(Escaneo $escaneo, Activo $activo): View
    {
        // Protección contra IDOR: el activo debe pertenecer al escaneo.
        abort_unless($activo->escaneo_id === $escaneo->id, 404);

        $escaneo->load('proyecto');
        $activo->load('puertos.vulnerabilidades');

        $puertosIds = $activo->puertos->pluck('id');

        $vulns = Vulnerabilidad::with('puerto')
            ->whereIn('puerto_id', $puertosIds)
            ->orderByDesc('cvss')
            ->paginate(25);

        $stats = $this->statsPorSeveridad($puertosIds);

        return view('escaneos.detalle_activo', compact(
            'escaneo', 'activo', 'vulns', 'stats'
        ));
    }

    public function detalle(Vulnerabilidad $vulnerabilidad): View
    {
        $vulnerabilidad->load('puerto.activo.escaneo.proyecto');
        return view('escaneos.detalle_vuln', compact('vulnerabilidad'));
    }

    // Conteo de vulns por severidad para los KPIs (usado en index y detalle).
    private function statsPorSeveridad($puertosIds): array
    {
        return [
            'critica' => Vulnerabilidad::whereIn('puerto_id', $puertosIds)->where('severidad', 'critica')->count(),
            'alta'    => Vulnerabilidad::whereIn('puerto_id', $puertosIds)->where('severidad', 'alta')->count(),
            'media'   => Vulnerabilidad::whereIn('puerto_id', $puertosIds)->where('severidad', 'media')->count(),
            'baja'    => Vulnerabilidad::whereIn('puerto_id', $puertosIds)->where('severidad', 'baja')->count(),
        ];
    }
}
