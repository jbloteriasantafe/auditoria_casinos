<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;
use Illuminate\Support\Facades\DB;

use App\Console\Commands\CalcularDiferenciasIDM;
use App\Console\Commands\SortearMesas;
use App\Http\Controllers\Mesas\Importaciones\Mesas\ImportadorController;
use App\Http\Controllers\Mesas\Aperturas\ABMCRelevamientosAperturaController;
use App\Http\Controllers\Mesas\Apuestas\GenerarPlanillasController;
use Carbon\Carbon;

class Kernel extends ConsoleKernel
{
    /**
     * The Artisan commands provided by your application.
     *
     * @var array
     */
    protected $commands = [
        Commands\asociarJuegoCasino::class,
        Commands\packJuego::class,
        Commands\GenerarRelevamientosApuestas::class,
        SortearMesas::class,
    ];

    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
      /*
     $schedule->command('nombre_comando:accion')
             ->dailyAt('00:00')
             ->appendOutputTo('archivo.log')
             ->runInBackground();
      
     $schedule->call(function (){
      $comando = DB::table('comando_a_ejecutar')
          ->where('fecha_a_ejecutar','>',Carbon::now()->format('Y:m:d H:i:s'))
          ->get();
      echo($comando);
      foreach ($comando as $c) {
        switch ($c->nombre_comando) {
          case 'nombre_comando:accion':{
            //hacer algo
          }break;
          default:
            break;
        }
      }
     })
     ->everyThirtyMinutes()
     ->appendOutputTo('pedidos.log')
     ->runInBackground();*/
    }

    /**
     * Register the commands for the application.
     *
     * @return void
     */
    protected function commands()
    {
        //$this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
