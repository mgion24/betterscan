<?php

namespace App\Http\Controllers;

use App\Models\Activo;
use App\Models\Informe;
use App\Models\Proyecto;
use App\Models\Puerto;
use App\Models\Rol;
use App\Models\Vulnerabilidad;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class InformeController extends Controller
{
    public function exportar(Proyecto $proyecto): View
    {
        $proyecto->load('empresa', 'escaneos', 'auditor');

        $puertosIds = $this->puertosDelProyecto($proyecto);

        $stats = [
            'critica' => Vulnerabilidad::whereIn('puerto_id', $puertosIds)->where('severidad', 'critica')->count(),
            'alta' => Vulnerabilidad::whereIn('puerto_id', $puertosIds)->where('severidad', 'alta')->count(),
            'media' => Vulnerabilidad::whereIn('puerto_id', $puertosIds)->where('severidad', 'media')->count(),
            'baja' => Vulnerabilidad::whereIn('puerto_id', $puertosIds)->where('severidad', 'baja')->count(),
        ];

        $totalActivos = Activo::whereIn('escaneo_id', $proyecto->escaneos->pluck('id'))->count();

        return view('informes.exportar', compact('proyecto', 'stats', 'totalActivos'));
    }

    public function generar(Request $request, Proyecto $proyecto): BinaryFileResponse|RedirectResponse
    {
        $data = $request->validate([
            'tipo_informe' => 'required|in:ejecutivo,tecnico,completo',
        ]);

        // Cargamos toda la cadena para que la plantilla del PDF pueda
        // recorrerla sin lanzar más queries.
        $proyecto->load('empresa', 'auditor', 'escaneos.activos.puertos.vulnerabilidades');

        Storage::disk('local')->makeDirectory('informes');

        $nombre = "informe_proyecto_{$proyecto->id}_" . now()->format('Ymd_His') . '.pdf';
        $rutaRelativa = "informes/$nombre";
        $rutaAbsoluta = Storage::disk('local')->path($rutaRelativa);

        $pdf = Pdf::loadView('informes.pdf', [
            'proyecto' => $proyecto,
            'tipo' => $data['tipo_informe'],
            'emisor' => Auth::user(),
        ])->setPaper('a4');

        $pdf->save($rutaAbsoluta);

        Informe::create([
            'tipo_informe' => $data['tipo_informe'],
            'formato' => 'pdf',
            'ruta_archivo' => $rutaRelativa,
            'fecha_creacion' => now(),
            'proyecto_id' => $proyecto->id,
            'emitido_por' => Auth::id(),
        ]);

        return response()->download($rutaAbsoluta, $nombre);
    }

    public function descargar(Informe $informe): BinaryFileResponse|RedirectResponse
    {
        $ruta = Storage::disk('local')->path($informe->ruta_archivo);
        $existe = file_exists($ruta);

        $respuesta = response()->download($ruta);
        if (!$existe) {
            $respuesta = redirect("/proyectos/{$informe->proyecto_id}")
                ->with('error', 'El archivo del informe ya no existe en el servidor.');
        }

        return $respuesta;
    }

    public function destroy(Informe $informe): RedirectResponse
    {
        $this->asegurarPuedeBorrarInforme($informe);

        $proyectoId = $informe->proyecto_id;
        $rutaAbsoluta = Storage::disk('local')->path($informe->ruta_archivo);

        // Si el fichero ya no está en disco (limpieza manual previa) seguimos
        // borrando el registro: un informe sin PDF no aporta nada al cliente.
        if (file_exists($rutaAbsoluta)) {
            @unlink($rutaAbsoluta);
        }
        $informe->delete();

        return redirect("/proyectos/$proyectoId")
            ->with('success', 'Informe eliminado. Ya no aparece al cliente.');
    }

    // El guard es una sola línea: delega en el modelo, que es quien
    // conoce las reglas de borrado del informe. Tanto el controller
    // como el blade preguntan al mismo método — sin duplicar la lógica.
    private function asegurarPuedeBorrarInforme(Informe $informe): void
    {
        if (!$informe->puedeBorrar(Auth::user())) {
            abort(403);
        }
    }

    private function puertosDelProyecto(Proyecto $proyecto)
    {
        $idsEscaneos = $proyecto->escaneos->pluck('id');
        $idsActivos = Activo::whereIn('escaneo_id', $idsEscaneos)->pluck('id');

        return Puerto::whereIn('activo_id', $idsActivos)->pluck('id');
    }
}
