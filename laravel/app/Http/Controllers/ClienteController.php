<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreEmpresaRequest;
use App\Models\Empresa;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

// Gestión de empresas cliente.
// En la base de datos la tabla se llama "empresa" pero en la interfaz
// les llamamos "clientes" porque son las empresas auditadas.
class ClienteController extends Controller
{
    public function index(): View
    {
        $empresas = Empresa::withCount('proyectos')
            ->orderBy('id', 'desc')
            ->paginate(20);

        // Retornamos la vista del listado de clientes, pasando las empresas cargadas. La vista se encargará de mostrar la información de cada empresa y paginar los resultados.
        return view('clientes.index', compact('empresas'));
    }

    public function create(): View
    {
        return view('clientes.form', [
            'empresa' => new Empresa(['activo' => true]),
        ]);
    }

    // la función store se encarga de validar los datos, crear la empresa y redirigir al listado con un mensaje flash de éxito.
    public function store(StoreEmpresaRequest $request): RedirectResponse
    {
        $datos = $request->validated();
        $datos['activo'] = $request->boolean('activo', true);

        $empresa = Empresa::create($datos);
        // El with('success', ...) añade un mensaje flash a la sesión que se muestra en el siguiente request (en este caso, el redirect al listado de clientes). Es una forma común de mostrar mensajes de éxito después de una operación.
        return redirect('/clientes')->with('success', "Empresa {$empresa->nombre} creada.");
    }

    public function edit(Empresa $cliente): View
    {
        // Retornamos la vista del formulario de edición, pasando la empresa cliente a editar. La vista se encargará de mostrar los datos actuales de la empresa en el formulario para que el usuario pueda modificarlos.
        return view('clientes.form', ['empresa' => $cliente]);
    }

    // La función update se encarga de validar los datos, actualizar la empresa y redirigir al listado con un mensaje flash de éxito.
    public function update(StoreEmpresaRequest $request, Empresa $cliente): RedirectResponse
    {
        $datos = $request->validated();
        // El campo "activo" es un checkbox, así que lo convertimos a booleano. Si el checkbox no está marcado, no llegará en el request, así que le damos un valor por defecto de false.
        $datos['activo'] = $request->boolean('activo', true);

        $cliente->update($datos);
        return redirect('/clientes')->with('success', 'Empresa actualizada.');
    }

    public function destroy(Empresa $cliente): RedirectResponse
    {
        // Antes de borrar la empresa, guardamos su nombre para mostrarlo en el mensaje de éxito después del borrado. Si intentamos acceder a $cliente->nombre después de borrarla, daría un error porque el modelo ya no existe en la base de datos.
        $nombre = $cliente->nombre_comercial ?? $cliente->nombre;

        // Borrado en cascada manual: empresa -> proyectos -> escaneos + informes.
        // Lo metemos en una transacción para que sea atómico.
        DB::transaction(function () use ($cliente) {
            // Los usuarios cliente quedan sin empresa pero no se borran.
            $cliente->usuariosCliente()->update(['empresa_id' => null]);

            foreach ($cliente->proyectos as $proyecto) {
                $proyecto->informes()->delete();
                foreach ($proyecto->escaneos as $escaneo) {
                    $escaneo->delete();
                }
                $proyecto->delete();
            }
            $cliente->delete();
        });

        return redirect('/clientes')->with('success', "Empresa {$nombre} eliminada.");
    }

    public function show(Empresa $cliente): RedirectResponse
    {
        return redirect("/clientes/{$cliente->id}/edit");
    }
}
