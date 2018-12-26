<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Schema;

class packJuego extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'packJuego:createTables';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Crea las tablas necesarias para la gestion de pack de juegos';

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
        $this->createTablePack();
        $this->createTablePackJuego();
        $this->createTablePackTieneCasino();
    }

    private function createTablePack(){
        Schema::create('pack_juego', function($table)
        {
            $table->increments('id_pack');
            $table->string('identificador',65);
            $table->string('prefijo',6);
        });

    }

    private function createTablePackJuego(){
        Schema::create('pack_tiene_juego', function($table)
        {
            $table->increments('id_pack_tiene_juego');
            $table->unsignedInteger('id_pack');
            $table->integer('id_juego');
            $table->foreign('id_pack')->references('id_pack')->on('pack_juego');
            $table->foreign('id_juego')->references('id_juego')->on('juego');
        });

    }

    private function createTablePackTieneCasino(){
        Schema::create('pack_juego_tiene_casino', function($table)
        {
            $table->increments('id_pack_juego_tiene_casino');
            $table->integer('id_casino');
            $table->unsignedInteger('id_pack');
            $table->foreign('id_casino')->references('id_casino')->on('casino');
            $table->foreign('id_pack')->references('id_pack')->on('pack_juego');
        });

    }


}


// Se agrega en master la creacion de tablas
