<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class progresivos extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'command:name';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

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
        $this->addCasinoInPozo();
    }

    private function addCasinoInPozo(){
        Schema::table('progresivo', function($table)
        {
            $table->unsignedInteger('id_casino')
                    ->after('maximo')
                    ->nullable();
            $table->foreign('id_casino')->references('id_casino')->on('casino');
        });
    }
}
