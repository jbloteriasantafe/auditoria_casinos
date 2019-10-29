<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Http\Controllers\Importaciones\Mesas\ImportadorController;

class CalcularDiferenciasIDM extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'IDM:calcularDiff';
    private $impController;

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Calculates if is there any difference btw cierres e ImportacionDiariaMesas';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
        $this->impController = new ImportadorController;
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $this->impController->calcularDiffIDM();
    }
}
