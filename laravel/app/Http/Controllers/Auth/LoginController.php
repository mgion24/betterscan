<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Usuario;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class LoginController extends Controller
{
    public function show(): View|RedirectResponse
    {
        $respuesta = view('auth.login');

        if (Auth::check()) {
            $respuesta = redirect()->intended('/dashboard');
        }

        return $respuesta;
    }

    public function login(Request $request): RedirectResponse
    {
        $datos = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        $recordar = $request->boolean('remember');

        // Si el usuario marcó "Recordarme en este equipo", la cookie
        // de remember durará 5 días (por defecto Laravel pone 5 años,
        // demasiado para una app de auditoría). En minutos: 5*24*60.
        if ($recordar) {
            /** @var \Illuminate\Auth\SessionGuard $guard */
            $guard = Auth::guard();
            $guard->setRememberDuration(60 * 24 * 5);
        }

        $login_ok = Auth::attempt($datos, $recordar);

        // dd($datos, $login_ok); // para depurar el intento de login

        if (!$login_ok) {
            throw ValidationException::withMessages([
                'email' => 'Las credenciales no son válidas.',
            ]);
        }

        $request->session()->regenerate();

        /** @var Usuario $usuario */
        $usuario = Auth::user();
        $destino = $usuario->esCliente() ? '/portal' : '/dashboard';

        return redirect()->intended($destino);
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/login');
    }
}
