<?php

namespace App\Http\Controllers;

use App\Models\Informe;
use App\Models\Proyecto;
use App\Models\Usuario;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

// Portal de solo lectura para los usuarios con rol cliente.
class PortalClienteController extends Controller
{
    public function index(): View
    {
        /** @var Usuario $usuario */
        $usuario = Auth::user();

        $proyectos = Proyecto::with(['informes'])
            ->where('empresa_id', $usuario->empresa_id)
            ->where('visibilidad', 'cliente')
            ->orderByDesc('id')
            ->get();

        $totalInformes = 0;
        foreach ($proyectos as $proyecto) {
            $totalInformes += $proyecto->informes->count();
        }

        return view('cliente.portal', compact('proyectos', 'totalInformes'));
    }

    public function show(Proyecto $proyecto): View|RedirectResponse
    {
        /** @var Usuario $usuario */
        $usuario = Auth::user();

        $esDeSuEmpresa = $proyecto->empresa_id === $usuario->empresa_id;
        $esVisible = $proyecto->visibilidad === 'cliente';
        $tieneAcceso = $esDeSuEmpresa && $esVisible;

        if (!$tieneAcceso) {
            abort(403, 'No tienes acceso a este proyecto.');
        }

        $proyecto->load('escaneos', 'informes.emisor');

        return view('cliente.proyecto', compact('proyecto'));
    }

    public function descargarInforme(Informe $informe): BinaryFileResponse|RedirectResponse
    {
        /** @var Usuario $usuario */
        $usuario = Auth::user();
        $proyecto = $informe->proyecto;

        $esDeSuEmpresa = $proyecto->empresa_id === $usuario->empresa_id;
        $esVisible = $proyecto->visibilidad === 'cliente';
        $tieneAcceso = $esDeSuEmpresa && $esVisible;

        if (!$tieneAcceso) {
            abort(403, 'No tienes acceso a este informe.');
        }

        $ruta = Storage::disk('local')->path($informe->ruta_archivo);
        $existe = file_exists($ruta);

        $respuesta = response()->download($ruta);
        if (!$existe) {
            $respuesta = redirect('/portal')->with('error', 'El archivo del informe no está disponible.');
        }

        return $respuesta;
    }
}
