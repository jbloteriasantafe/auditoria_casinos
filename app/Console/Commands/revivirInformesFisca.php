<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Http\Controllers\Mesas\Cierres\ABMCCierreAperturaController;

class revivirInformesFisca extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'revivir:informesfisca';
    protected $controller;
    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'para crear los informes de los fiscalizadores';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
        $this->controller = new ABMCCierreAperturaController;
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
      $this->controller->revivirElPasado();

      echo('>>Exito<<');
    }
}
