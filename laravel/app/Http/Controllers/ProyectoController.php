<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreProyectoRequest;
use App\Models\Activo;
use App\Models\Empresa;
use App\Models\Escaneo;
use App\Models\Proyecto;
use App\Models\Puerto;
use App\Models\Rol;
use App\Models\Usuario;
use App\Models\Vulnerabilidad;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class ProyectoController extends Controller
{
    public function index(): View
    {
        // Modelo de equipo: cualquier empleado ve todos los proyectos para
        // poder lanzar escaneos en cualquiera. La restricción aplica solo a
        // edit/update/destroy del proyecto (gestión de metadata).
        $proyectos = Proyecto::with(['empresa', 'auditor'])
            ->orderBy('id', 'desc')
            ->paginate(15);

        return view('proyectos.index', compact('proyectos'));
    }

    public function create(): View
    {
        $rolesPermitidos = [Rol::ADMIN, Rol::EMPLEADO];

        $auditores = Usuario::whereHas('rol', function ($q) use ($rolesPermitidos) {
            $q->whereIn('nombre', $rolesPermitidos);
        })->orderBy('nombre')->get();

        return view('proyectos.create', [
            'proyecto' => new Proyecto(),
            'empresas' => Empresa::orderBy('nombre')->get(),
            'auditores' => $auditores,
        ]);
    }

    public function store(StoreProyectoRequest $request): RedirectResponse
    {
        $proyecto = Proyecto::create($request->validated());

        return redirect("/proyectos/{$proyecto->id}")
            ->with('success', 'Proyecto creado correctamente.');
    }

    public function show(Proyecto $proyecto): View
    {
        // Sin guard de propiedad: cualquier empleado puede ver el detalle
        // para colaborar en escaneos del proyecto del equipo. La edición
        // y la eliminación sí están restringidas al auditor responsable.
        $proyecto->load(['empresa', 'auditor', 'escaneos', 'informes.emisor']);

        $idsEscaneos = $proyecto->escaneos->pluck('id');
        $idsActivos = Activo::whereIn('escaneo_id', $idsEscaneos)->pluck('id');
        $idsPuertos = Puerto::whereIn('activo_id', $idsActivos)->pluck('id');

        $totalEscaneos = $proyecto->escaneos->count();
        $totalActivos = $idsActivos->count();
        $totalVulns = Vulnerabilidad::whereIn('puerto_id', $idsPuertos)->count();

        return view('proyectos.show', compact(
            'proyecto', 'totalEscaneos', 'totalActivos', 'totalVulns'
        ));
    }

    public function edit(Proyecto $proyecto): View
    {
        $this->asegurarPropietario($proyecto);

        $rolesPermitidos = [Rol::ADMIN, Rol::EMPLEADO];

        $auditores = Usuario::whereHas('rol', function ($q) use ($rolesPermitidos) {
            $q->whereIn('nombre', $rolesPermitidos);
        })->orderBy('nombre')->get();

        return view('proyectos.create', [
            'proyecto' => $proyecto,
            'empresas' => Empresa::orderBy('nombre')->get(),
            'auditores' => $auditores,
        ]);
    }

    public function update(StoreProyectoRequest $request, Proyecto $proyecto): RedirectResponse
    {
        $this->asegurarPropietario($proyecto);

        $proyecto->update($request->validated());

        return redirect("/proyectos/{$proyecto->id}")
            ->with('success', 'Proyecto actualizado.');
    }

    public function destroy(Proyecto $proyecto): RedirectResponse
    {
        $this->asegurarPropietario($proyecto);

        // Si tiene escaneos sin acabar no se puede borrar.
        $estadosActivos = [Escaneo::ESTADO_PENDIENTE, Escaneo::ESTADO_EN_PROCESO];
        $sinAcabar = $proyecto->escaneos()->whereIn('estado', $estadosActivos)->count();

        if ($sinAcabar <= 0) {
            DB::transaction(function () use ($proyecto) {
                $proyecto->informes()->delete();
                foreach ($proyecto->escaneos as $escaneo) {
                    $escaneo->delete();
                }
                $proyecto->delete();
            });
        }   

        return ($sinAcabar > 0) ? redirect("/proyectos/{$proyecto->id}")
                ->with('error', "No se puede borrar: hay $sinAcabar escaneo(s) sin terminar.")
                : redirect('/proyectos')->with('success', 'Proyecto eliminado.');
    }

    // El guard es una sola línea: delega en el modelo, que es quien
    // conoce las reglas de gestión del proyecto. Tanto el controller
    // como el blade preguntan al mismo método — sin duplicar la lógica.
    private function asegurarPropietario(Proyecto $proyecto): void
    {
        if (!$proyecto->puedeGestionar(Auth::user())) {
            abort(403);
        }
    }
}
