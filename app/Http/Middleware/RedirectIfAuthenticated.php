<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Auth;

class RedirectIfAuthenticated
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @param  string|null  $guard
     * @return mixed
     */
    public function handle($request, Closure $next, $guard = null)
    {
        if (Auth::check()) {
           $usuario_actual=\Auth::user();
            $us_tipo = $usuario_actual->usuario_tipo;
            
            switch($us_tipo)
                {
                   case '1': //Administrador
                        return redirect()->route('bp_admin');
                        break;
                    case '2': //Admin Padel
                        return redirect()->route('bp_admin');                        
                        break;
                    case '3': //Padel
                        return redirect()->route('bp_admin');
                        //return '/secretaria_home';                        
                        break;
                }
        }
        return $next($request);
    }
}
