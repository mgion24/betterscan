<?php

use App\Http\Controllers\AjustesController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\ClienteController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\EscaneoController;
use App\Http\Controllers\InformeController;
use App\Http\Controllers\PortalClienteController;
use App\Http\Controllers\ProyectoController;
use App\Http\Controllers\ResultadoController;
use App\Http\Controllers\UsuarioController;
use Illuminate\Support\Facades\Route;

// =============================================================
// Autenticación
// =============================================================
Route::get('/login', [LoginController::class, 'show'])->name('login');
Route::post('/login', [LoginController::class, 'login'])->middleware('throttle:5,1');
Route::post('/logout', [LoginController::class, 'logout'])->middleware('auth');

Route::get('/', fn () => redirect('/dashboard'));

// =============================================================
// Rutas autenticadas
// =============================================================
Route::middleware('auth')->group(function () {

    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // -----------------------------------------------------------
    // Admin + Empleado
    // -----------------------------------------------------------
    Route::middleware('role:admin,empleado')->group(function () {

        // Buscador global — SOLO admin y empleado. Antes estaba fuera del
        // grupo role:* y el rol cliente podía enumerar proyectos y CVEs
        // ajenos por query string. Exposición de información corregida.
        Route::get('/buscar', function () {
            $q = request('q');
            $proyectos = $q ? \App\Models\Proyecto::where('nombre', 'like', "%$q%")->limit(20)->get() : collect();
            $vulns = $q ? \App\Models\Vulnerabilidad::where('cve_asociado', 'like', "%$q%")
                            ->orWhere('descripcion', 'like', "%$q%")->limit(20)->get() : collect();
            return view('buscar', compact('q', 'proyectos', 'vulns'));
        });

        Route::resource('proyectos', ProyectoController::class);

        Route::get('/proyectos/{proyecto}/escaneos/crear', [EscaneoController::class, 'create'])
            ->name('escaneos.create');
        Route::post('/proyectos/{proyecto}/escaneos', [EscaneoController::class, 'store'])
            ->name('escaneos.store');

        // Proxy a FastAPI /network/interfaces (botón "Detectar mi red").
        Route::get('/escaneos/network/interfaces', [EscaneoController::class, 'interfacesRed'])
            ->name('escaneos.interfaces');

        Route::get('/escaneos', [EscaneoController::class, 'index'])->name('escaneos.index');
        Route::get('/escaneos/{escaneo}', [EscaneoController::class, 'show'])->name('escaneos.show');
        Route::get('/escaneos/{escaneo}/edit', [EscaneoController::class, 'edit'])->name('escaneos.edit');
        Route::put('/escaneos/{escaneo}', [EscaneoController::class, 'update'])->name('escaneos.update');
        Route::delete('/escaneos/{escaneo}', [EscaneoController::class, 'destroy'])
            ->name('escaneos.destroy');
        Route::get('/escaneos/{escaneo}/estado.json', [EscaneoController::class, 'estado'])
            ->name('escaneos.estado');
        Route::get('/escaneos/{escaneo}/resultados', [ResultadoController::class, 'index'])
            ->name('escaneos.resultados');
        Route::get('/escaneos/{escaneo}/activos/{activo}', [ResultadoController::class, 'detalleActivo'])
            ->name('escaneos.activo');

        // Descarga de archivos exportados por el motor (XML/Normal/Grep/Gobuster).
        Route::get('/escaneos/{escaneo}/export/{formato}', [EscaneoController::class, 'descargarExport'])
            ->whereIn('formato', ['xml', 'nmap', 'gnmap', 'gobuster'])
            ->name('escaneos.export');

        Route::get('/vulnerabilidades/{vulnerabilidad}', [ResultadoController::class, 'detalle'])
            ->name('vulnerabilidades.show');

        // Informes
        Route::get('/proyectos/{proyecto}/informe/exportar', [InformeController::class, 'exportar'])
            ->name('informes.exportar');
        Route::post('/proyectos/{proyecto}/informe', [InformeController::class, 'generar'])
            ->name('informes.generar');
        Route::get('/informes/{informe}/descargar', [InformeController::class, 'descargar'])
            ->name('informes.descargar');
        Route::delete('/informes/{informe}', [InformeController::class, 'destroy'])
            ->name('informes.destroy');

        // Ajustes
        Route::get('/ajustes', [AjustesController::class, 'show'])->name('ajustes.show');
        Route::put('/ajustes/perfil', [AjustesController::class, 'actualizarPerfil'])
            ->name('ajustes.perfil');
        Route::put('/ajustes/password', [AjustesController::class, 'cambiarPassword'])
            ->name('ajustes.password');
    });

    // -----------------------------------------------------------
    // Solo Admin
    // -----------------------------------------------------------
    Route::middleware('role:admin')->group(function () {
        Route::resource('usuarios', UsuarioController::class);
        Route::resource('clientes', ClienteController::class);
    });

    // -----------------------------------------------------------
    // Solo Cliente
    // -----------------------------------------------------------
    Route::middleware('role:cliente')->group(function () {
        Route::get('/portal', [PortalClienteController::class, 'index'])->name('portal.index');
        Route::get('/portal/proyectos/{proyecto}', [PortalClienteController::class, 'show'])
            ->name('portal.proyecto');
        Route::get('/portal/informes/{informe}', [PortalClienteController::class, 'descargarInforme'])
            ->name('portal.informe');
    });
});
