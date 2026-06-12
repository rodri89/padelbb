<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\turnoRegistrado;
use Illuminate\Support\Facades\DB;

class recordarTurno extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sms:recordatorio';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Un mensaje de texto sera enviado para recordar el turno';

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

        SMS::to('2915080081')
               ->msg('Buenas tardes Rodrigo, quería recordarte que mañana tenes turno con el medico')
               ->send();

      //User::whereBirthDate(date('m/d'))->get(); 
      $today = getdate();         
      $myDate = $today['year'].'-'.$today['mon'].'-'.$today['mday'];
      
      $date = date('Y-m-d', strtotime($myDate . ' +1 Weekday'));
    //$date = '2011-04-05';

      $pacientes=DB::table('turno_registrados')
                    ->join('pacientes','pacientes.id','=','turno_registrados.paciente')                    
                    ->select('pacientes.nombre','pacientes.apellido','pacientes.telefono')
                    ->where('turno_registrados.fechaTurno',$date)                    
                    ->get();    

      foreach($pacientes as $paciente ) {
            if($paciente->has('telefono')) {
                 SMS::to($paciente->telefono)
               ->msg('Buenas tardes ' . $paciente->nombre . ', quería recordarte que mañana tenes turno con el medico')
               ->send();
            }   
        }  

        $this->info('Los mensajes de recordatorio han sido enviados correctamente');

    }
}
