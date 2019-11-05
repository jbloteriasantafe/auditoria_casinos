<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Http\Controllers\Mesas\Aperturas\ABMCRelevamientosAperturaController;

class SortearMesas extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'RAM:sortear';
    protected $relevamientoController;
    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Relevamientos Aperturas Mesas - Este comando almacena las mesas sorteadas de los siguientes dias';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
        $this->relevamientoController = new ABMCRelevamientosAperturaController;
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
      //echo('entró');
      $this->relevamientoController->sortearMesasCommand();

      echo('Se sortearon las mesas para las aperturas de los siguientes dias exitosamente!');
    }
}
