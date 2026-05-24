<?php

namespace App\Http\Controllers;

use App\Models\Escaneo;
use App\Models\Proyecto;
use App\Models\Usuario;
use App\Models\Vulnerabilidad;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(): View|RedirectResponse
    {
        /** @var Usuario $usuario */
        $usuario = Auth::user();

        $respuesta = null;

        if ($usuario->esCliente()) {
            $respuesta = redirect('/portal');
        } else {
            $totalProyectos = Proyecto::count();
            $totalVulns = Vulnerabilidad::count();

            // Contamos los escaneos que están activos, es decir, los que no han terminado ni han fallado. Es decir, los que están pendientes o en proceso.
            $escaneosActivos = Escaneo::whereIn('estado', [
                Escaneo::ESTADO_PENDIENTE,
                Escaneo::ESTADO_EN_PROCESO,
            ])->count();

            // Contamos las vulnerabilidades críticas y altas para mostrar un KPI de "vulnerabilidades críticas". En este caso, consideramos críticas las que tienen severidad "alta" o "crítica".
            $vulnsCriticas = Vulnerabilidad::whereIn('severidad', ['alta', 'critica'])->count();

            $porSeveridad = [
                'critica' => Vulnerabilidad::where('severidad', 'critica')->count(),
                'alta' => Vulnerabilidad::where('severidad', 'alta')->count(),
                'media' => Vulnerabilidad::where('severidad', 'media')->count(),
                'baja' => Vulnerabilidad::where('severidad', 'baja')->count(),
            ];

            // Para mostrar los últimos proyectos y escaneos en el dashboard, los cargamos ordenados por fecha de creación (id) de forma descendente y limitados a 5. También cargamos las relaciones necesarias para mostrar el nombre de la empresa en cada proyecto y escaneo.
            $ultimosProyectos = Proyecto::with(['empresa', 'auditor'])
                ->latest('id')
                ->take(5)
                ->get();

            $ultimosEscaneos = Escaneo::with('proyecto.empresa')
                ->latest('id')
                ->take(5)
                ->get();

            // compact('totalProyectos', 'escaneosActivos'...) es una forma rápida de crear un array asociativo con esas variables para pasarlas a la vista. Es equivalente a escribir:
                // [
                //     'totalProyectos' => $totalProyectos,
                //     'escaneosActivos' => $escaneosActivos,
                //     'totalVulns' => $totalVulns,
                //     'vulnsCriticas' => $vulnsCriticas,
                //     'porSeveridad' => $porSeveridad,
                //     'ultimosProyectos' => $ultimosProyectos,
                //     'ultimosEscaneos' => $ultimosEscaneos,
                // ]
            $respuesta = view('dashboard', compact(
                'totalProyectos', 'escaneosActivos', 'totalVulns', 'vulnsCriticas',
                'porSeveridad', 'ultimosProyectos', 'ultimosEscaneos'
            ));
        }

        return $respuesta;
    }
}
