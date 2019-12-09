<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Http\Controllers\Mesas\Apuestas\GenerarPlanillasController;


class GenerarRelevamientosApuestas extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'RelevamientoApuestas:generar';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Genera planillas y relevamientos de las apuestas para todos los turnos de todos los casinos.';

    /**
     * The controller that does everything.
     *
     * @var string
     */
    protected $generarPlanillasController;

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
        $this->generarPlanillasController = new GenerarPlanillasController;
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $this->generarPlanillasController->generarRelevamientosApuestas();
        echo('Se generaron las planillas de los relevamientos de apuestas exitosamente!');
    }
}
