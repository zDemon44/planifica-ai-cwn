<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class VerificarCuentaActiva
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($usuario = $request->user()) {
            $usuario->refresh();
            $version = Auth::viaRemember() ? $usuario->version_acceso : $request->session()->get('version_acceso', 1);
            if (! $usuario->puedeIngresar() || (int) $version !== $usuario->version_acceso) {
                Auth::logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();

                return redirect()->route('login')->withErrors(['email' => 'Tu acceso cambió o la cuenta está desactivada. Inicia sesión nuevamente.']);
            }
            $request->session()->put('version_acceso', $usuario->version_acceso);
        }

        return $next($request);
    }
}
