<?php

namespace App\Http\Controllers\Auth;

use App\User;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Foundation\Auth\RegistersUsers;
use Illuminate\Support\Facades\DB;
use App\Secretaria;
use App\Medico;

class RegisterController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Register Controller
    |--------------------------------------------------------------------------
    |
    | This controller handles the registration of new users as well as their
    | validation and creation. By default this controller uses a trait to
    | provide this functionality without requiring any additional code.
    |
    */

    use RegistersUsers;

    /**
     * Where to redirect users after registration.
     *
     * @var string
     */
    protected $redirectTo = '/login';

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Get a validator for an incoming registration request.
     *
     * @param  array  $data
     * @return \Illuminate\Contracts\Validation\Validator
     */
    protected function validator(array $data)
    {
        return Validator::make($data, [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'password' => ['required', 'string', 'min:6', 'confirmed'],
        ]);
    }

    /**
     * Create a new user instance after a valid registration.
     *
     * @param  array  $data
     * @return \App\User
     */
    protected function create(array $data)
    {    
         $user=User::create([
            'name' => $data['name'],            
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
            'usuario_tipo' => $data['usuario_tipo'],
            'perfil' => 1,
        ]);
       
        return $user;    
    }

    /**
     * Show the application registration form.
     *
     * @return \Illuminate\Http\Response
     */
    public function showRegistrationForm()
    {
        return view('auth.register');
    }

    public function registrar(Request $request)
    {
        // Validar los datos
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'password' => ['required', 'string', 'min:6', 'confirmed'],
            'usuario_tipo' => ['required', 'integer', 'in:1,2,3'],
        ]);
            
        $user = new User;         
        $user->name = $request->get('name');
        $user->lastname = $request->get('surname', '');
        $user->email = $request->get('email');
        $user->user_name = '';
        $user->pass = '';
        $user->password = Hash::make($request->get('password'));
        $user->usuario_tipo = $request->get('usuario_tipo');
        $user->perfil = 1;
        $user->save();        
        
        if ($request->get('usuario_tipo') == 2) { 
            return redirect()->route('home_admin')->with('success', 'Usuario registrado correctamente');
        }
        
        return redirect()->route('register')->with('success', 'Usuario registrado correctamente');
    }    

}
