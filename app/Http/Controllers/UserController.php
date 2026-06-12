<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Foundation\Auth\AuthenticatesUsers;
use Illuminate\Support\Facades\Auth;
use Image;

class UserController extends Controller
{
    use AuthenticatesUsers;

    protected $redirectTo = '/homes';
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth');        
    }
    
    function admin(){
        return View('admin.plantilla_admin'); 
    }

    function nuevoUsuario(){
        return View('admin.nuevo_usuario'); 
    }    

    public function irHome(){           
       if (Auth::check()) { 
            
            $usuario_actual=\Auth::user();
            $us_tipo = $usuario_actual->usuario_tipo;
            
            switch($us_tipo)
                {
                case '1': //Administrador
                    return redirect('/admin_users');
                    break;
                case '2': //medico
                    return redirect('/admin_autos');
                    break;
                case '3': //secretaria
                    return redirect('/admin_autos');                
                    break;  
                }   
        }
        else 
        {
            return redirect('/login');
        }    
    }
}
