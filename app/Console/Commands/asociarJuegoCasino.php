<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Juego;

class asociarJuegoCasino extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'asociarJuegoCasino:all';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'asocia todos los juegos con todos los casinos';

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
        $juegos=Juego::all();
        foreach($juegos as $juego){
            $juego->casino()->sync([1,2,3]);
        }
    }
}
