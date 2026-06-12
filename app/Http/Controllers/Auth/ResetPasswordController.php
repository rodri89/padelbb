<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Foundation\Auth\ResetsPasswords;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Session;

class ResetPasswordController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Password Reset Controller
    |--------------------------------------------------------------------------
    |
    | This controller is responsible for handling password reset requests
    | and uses a simple trait to include this behavior. You're free to
    | explore this trait and override any methods you wish to tweak.
    |
    */

    use ResetsPasswords;

    /**
     * Where to redirect users after resetting their password.
     *
     * @var string
     */
    protected $redirectTo = '/home';

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('guest', ['except' => 'logout']);
        //$this->middleware('guest');
    }

    public function irHome(){           
        $usuario_actual=\Auth::user();
        $us_tipo = $usuario_actual->usuario_tipo;
        
        switch($us_tipo)
            {
            case '1': //Administrador
                return '/admin_show_medicos';
                break;
            case '2': //medico
                return '/medico_home';
                break;
            case '3': //secretaria
                return '/secretaria_home';                
                break;  
            }           
    }
}
