<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Foundation\Auth\AuthenticatesUsers;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Session;

class LoginController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Login Controller
    |--------------------------------------------------------------------------
    |
    | This controller handles authenticating users for the application and
    | redirecting them to your home screen. The controller uses a trait
    | to conveniently provide its functionality to yos
    */

    use AuthenticatesUsers;

    /**
     * Where to redirect users after login.
     *
     * @var string
     */
    protected $redirectTo = '/index';

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {        
         $this->middleware('guest', ['except' => 'logout']);
        //$this->middleware('auth')->except('logout');
    }

    public function redirectPath()
    {        
        if (Auth::check()) { 
            
            $usuario_actual=\Auth::user();
            $us_tipo = $usuario_actual->usuario_tipo;
            
            switch($us_tipo)
                {
                case '1': //Administrador
                    return '/bp_admin';
                    break;
                case '2': // usuario admin padel
                    return '/bp_admin';
                    break;
                case '3': //usuario padel
                    return '/bp_admin';                
                    break;  
                }   
        }
        else 
        {
            return redirect('/index');
        }    
    }


}
