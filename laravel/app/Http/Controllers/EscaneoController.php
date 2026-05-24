<?php

// Los namespaces en Laravel siguen la estructura de carpetas. Este controlador está en app/Http/Controllers, así que su namespace es App\Http\Controllers.
namespace App\Http\Controllers;

use App\Http\Requests\StoreEscaneoRequest;
use App\Models\Escaneo;
use App\Models\Proyecto;
use App\Models\Rol;
use App\Services\FastApiClient;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class EscaneoController extends Controller
{
    // El método index muestra un listado paginado de escaneos, ordenados por fecha de creación (id) de forma descendente. También carga la relación con el proyecto y la empresa para mostrar esa información en la vista.
    public function index(): View
    {
        $escaneos = Escaneo::with('proyecto.empresa')
            ->orderBy('id', 'desc')
            ->paginate(20);

        return view('escaneos.index', compact('escaneos'));
    }

    public function create(Proyecto $proyecto): View
    {
        // escaneos.configurar es la vista del wizard de configuración del escaneo. Le pasamos el proyecto para mostrar su información en el wizard y un nuevo modelo de escaneo vacío para reutilizar la misma vista tanto en creación como en edición.
        return view('escaneos.configurar', [
            'proyecto' => $proyecto,
            'escaneo' => new Escaneo(),
        ]);
    }

    // El redirectResponse es una respuesta que redirige al usuario a otra URL. En este caso, después de crear el escaneo, redirigimos a la página de detalles del escaneo recién creado para mostrar su estado y resultados.
    public function store(StoreEscaneoRequest $request, Proyecto $proyecto, FastApiClient $fastApi): RedirectResponse
    {
        $datos = $request->validated();

        // dd($datos); // para ver todo lo que llega del wizard de 4 pasos

        // Creamos el escaneo en estado "pendiente" para que aparezca en la lista de escaneos y en la página de detalles, aunque aún no haya empezado realmente. Esto es útil para mostrar feedback inmediato al usuario de que su escaneo se ha creado y está en cola.
        $escaneo = Escaneo::create([
            'nombre' => $datos['nombre'],
            'descripcion' => $datos['descripcion'] ?? null,
            'tipo_escaneo' => $this->tipoLegible($datos['plantilla']),
            'plantilla_escaneo' => $datos['plantilla'],
            'objetivo' => $datos['objetivo'],
            'velocidad' => $this->mapearVelocidad($datos['velocidad'] ?? 'T3'),
            'intensidad' => $datos['intensidad'] ?? 'normal',
            'estado' => Escaneo::ESTADO_PENDIENTE,
            'exclusiones' => $datos['excluir'] ?? null,
            'fecha_inicio' => now(),
            'fase_actual' => 'Encolando escaneo...',
            'parametros_nmap' => $datos,
            'proyecto_id' => $proyecto->id,
            // Quien lo lanza es quien podrá borrarlo más tarde. Modelo
            // de equipo: lanzar es libre, eliminar solo el dueño.
            'lanzado_por' => Auth::id(),
        ]);

        $ok = $fastApi->lanzarEscaneo($escaneo);

        if ($ok) {
            $escaneo->update([
                'estado' => Escaneo::ESTADO_EN_PROCESO,
                'fase_actual' => 'Iniciando',
            ]);
        } else {
            $escaneo->update([
                'estado' => Escaneo::ESTADO_ERROR,
                'fase_actual' => 'Fallo al contactar con el motor.',
                'error_mensaje' => 'No se pudo conectar con el motor de escaneo FastAPI.',
            ]);
        }

        return redirect("/escaneos/{$escaneo->id}");
    }

    public function edit(Escaneo $escaneo): View|RedirectResponse
    {
        $esPendiente = $escaneo->estado === Escaneo::ESTADO_PENDIENTE;

        if ($esPendiente) {
            $escaneo->load('proyecto');
        }

        // Una sola escritura a sesión: el flash solo se crea cuando NO es
        // pendiente. Si construyéramos los dos redirects en variables, el
        // ->with('error') escribiría en sesión inmediatamente aunque luego
        // devolviéramos la vista, y el error se mostraría al usuario.
        return $esPendiente
            ? view('escaneos.configurar', [
                'proyecto' => $escaneo->proyecto,
                'escaneo' => $escaneo,
            ])
            : redirect("/escaneos/{$escaneo->id}")
                ->with('error', 'Solo se pueden editar escaneos pendientes.');
    }

    public function update(StoreEscaneoRequest $request, Escaneo $escaneo): RedirectResponse
    {
        $esPendiente = $escaneo->estado === Escaneo::ESTADO_PENDIENTE;

        if ($esPendiente) {
            $datos = $request->validated();
            $escaneo->update([
                'nombre' => $datos['nombre'],
                'descripcion' => $datos['descripcion'] ?? null,
                'tipo_escaneo' => $this->tipoLegible($datos['plantilla']),
                'plantilla_escaneo' => $datos['plantilla'],
                'objetivo' => $datos['objetivo'],
                'velocidad' => $this->mapearVelocidad($datos['velocidad'] ?? 'T3'),
                'intensidad' => $datos['intensidad'] ?? 'normal',
                'exclusiones' => $datos['excluir'] ?? null,
                'parametros_nmap' => $datos,
            ]);
        }

        // Construyo SOLO el redirect que voy a devolver. Si construyera dos
        // ->with() distintos en variables separadas, ambos flashes quedarían
        // escritos en sesión (con() no es lazy) y el siguiente request los
        // mostraría ambos.
        return $esPendiente
            ? redirect("/escaneos/{$escaneo->id}")
                ->with('success', 'Escaneo actualizado.')
            : redirect("/escaneos/{$escaneo->id}")
                ->with('error', 'Solo se pueden editar escaneos pendientes.');
    }

    public function show(Escaneo $escaneo): View
    {
        $escaneo->load('proyecto.empresa');

        return view('escaneos.en_curso', compact('escaneo'));
    }

    // Endpoint JSON que consume el polling del frontend cada 2 segundos.
    public function estado(Escaneo $escaneo): JsonResponse
    {
        return response()->json([
            'estado' => $escaneo->estado,
            'progreso_pct' => $escaneo->progreso_pct,
            'fase_actual' => $escaneo->fase_actual,
            'error' => $escaneo->error_mensaje,
        ]);
    }

    public function destroy(Escaneo $escaneo): RedirectResponse
    {
        $this->asegurarPropietarioEscaneo($escaneo);

        $proyectoId = $escaneo->proyecto_id;
        $escaneo->delete();

        return redirect("/proyectos/$proyectoId")->with('success', 'Escaneo eliminado.');
    }

    // El guard delega en el modelo, que centraliza la regla de quién
    // puede borrar un escaneo. Sin duplicar la lógica entre controller
    // y blades.
    private function asegurarPropietarioEscaneo(Escaneo $escaneo): void
    {
        if (!$escaneo->puedeBorrar(Auth::user())) {
            abort(403);
        }
    }

    public function descargarExport(Escaneo $escaneo, string $formato): BinaryFileResponse|RedirectResponse
    {
        $archivos = [
            'xml' => "escaneo_{$escaneo->id}.xml",
            'nmap' => "escaneo_{$escaneo->id}.nmap",
            'gnmap' => "escaneo_{$escaneo->id}.gnmap",
            'gobuster' => "escaneo_{$escaneo->id}_gobuster.txt",
        ];

        if (!isset($archivos[$formato])) {
            abort(404);
        }

        $ruta = '/results/' . $archivos[$formato];
        $existe = is_file($ruta);

        $respuesta = response()->download($ruta, $archivos[$formato]);
        if (!$existe) {
            $respuesta = redirect("/escaneos/{$escaneo->id}/resultados")
                ->with('error', "El archivo $formato no está disponible.");
        }

        return $respuesta;
    }

    // Proxy del endpoint /network/interfaces del motor. Lo usa el botón
    // "Detectar mi red" del wizard de configuración.
    public function interfacesRed(FastApiClient $fastApi): JsonResponse
    {
        return response()->json([
            'interfaces' => $fastApi->listarInterfaces(),
        ]);
    }

    private function tipoLegible(string $plantilla): string
    {
        $tipos = [
            'host_discovery' => 'Descubrimiento de hosts',
            'quick_scan' => 'Escaneo rápido',
            'full_port_scan' => 'Escaneo completo de puertos',
            'service_detection' => 'Detección de servicios',
            'vuln_scan' => 'Análisis de vulnerabilidades',
            'aggressive' => 'Escaneo agresivo',
            'web_audit' => 'Auditoría web',
            'custom' => 'Personalizado',
        ];

        return $tipos[$plantilla] ?? 'Personalizado';
    }

    private function mapearVelocidad(string $velocidad): string
    {
        $resultado = 'rapido';

        if ($velocidad === 'T0' || $velocidad === 'T1') {
            $resultado = 'lento';
        } elseif ($velocidad === 'T2') {
            $resultado = 'normal';
        } elseif ($velocidad === 'T5') {
            $resultado = 'agresivo';
        }

        return $resultado;
    }

}
