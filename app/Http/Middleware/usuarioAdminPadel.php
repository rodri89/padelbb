<?php

namespace App\Http\Middleware;
use Illuminate\Support\Facades\Auth;
use Illuminate\Foundation\Auth\AuthenticatesUsers;
use Closure;

class usuarioAdminPadel
{

    use AuthenticatesUsers;
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        $usuario_actual=\Auth::user();
        if($usuario_actual->usuario_tipo != 2){ //si no es usuario Admin Padel lo redirecciono al login
         // Cerramos la sesión
        return $this->logout($request);
        }
        return $next($request);
    }
}
