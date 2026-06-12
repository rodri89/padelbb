<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\TurnoRegistrado;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;   
use App\Mail\SendMailable;
use Illuminate\Support\Facades\Log;
use App\Paciente;

class recordatorioMail extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'registered:users';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Envia mails para recordar turnos.';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
     $paciente = Paciente::find(1);
     $paciente->nombre = 'rodrigoCronJob';
     $paciente->save();
     //Mail::to('rodrigo.banegas@globant.com')->send(new SendMailable('rodrigo'));
              
      $today = getdate();
      $myDate = $today['year'].'-'.$today['mon'].'-'.$today['mday'];
      //$myDate = '2019-11-11';
      $date = date('Y/m/d', strtotime('+1 day' , strtotime ( $myDate )));
      
      $pacientes=DB::table('turno_registrados')
                    ->join('pacientes','pacientes.id','=','turno_registrados.paciente')                    
                    ->select('pacientes.nombre','pacientes.apellido','pacientes.telefono','pacientes.mail','turno_registrados.medico','turno_registrados.consultorio','turno_registrados.dia','turno_registrados.horario','turno_registrados.fechaTurno')
                    ->where('turno_registrados.fechaTurno',$date)                    
                    ->get();    
      foreach($pacientes as $paciente) {
            if($paciente->mail != null) {
               $medico = DB::table('medicos')->where('medicos.id', $paciente->medico)->first();   
               $medicoNombre = $medico->apellido.', '.$medico->nombre;
               $consultorio = DB::table('consultorios')->where('consultorios.id', $paciente->consultorio)->first();   
               $data = array('nombre'=>$paciente->nombre,'medico'=>$medicoNombre,'horario'=>$paciente->horario,'direccion'=>$consultorio->direccion,'telefono'=>$consultorio->telefono);               
               Mail::to($paciente->mail)->queue(new SendMailable($data));
            }   
        } 
     
    }
}
