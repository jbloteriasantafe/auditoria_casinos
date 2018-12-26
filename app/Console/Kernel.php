<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

use App\Console\Commands\CalcularDiferenciasIDM;
use App\Console\Commands\SortearMesas;

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

      $schedule->command('RAM:sortear')
               //->everyMinute() para pruebas
               ->dailyAt('00:30')
               ->runInBackground();

      // $schedule->command('RAM:sortear')
      //         ->dailyAt('12:00')
      //         ->runInBackground();
      $schedule->command('RAM:sortear')
               ->dailyAt('16:30')
               ->runInBackground();

        //dd('se hizo');

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
