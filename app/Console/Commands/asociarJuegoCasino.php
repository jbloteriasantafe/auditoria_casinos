<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Juego;
use App\Maquina;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

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
        $cant=0;
        // primero limipio la tabla y la vuelvo a crear
        $this->dropAndCreateTable();
        
        foreach(Juego::all()as $juego){
            
            $casinosAsignados=$this->casinos($juego->id_juego);
            //echo "\n el juego con id: " , $juego->id_juego , " tiene los casinos ";
            //print_r($casinosAsignados);
            if(sizeof($casinosAsignados)!=0){
                $cant=$cant+sizeof($casinosAsignados);
                $juego->casinos()->attach($casinosAsignados);
            }
        }
        echo "Se agregaron ", $cant, "filas\n";
    }

    private function casinos($id_juego){
        $casinos=array();
        if($this->casinoMelincue($id_juego)){
            array_push($casinos,1);
        };
        if($this->casinoSantaFe($id_juego)){
            array_push($casinos,2);
        };
        if($this->casinoRosario($id_juego)){
            array_push($casinos,3);
        };
        return $casinos;
    }

    private function dropAndCreateTable(){
        Schema::dropIfExists('casino_tiene_juego');
        Schema::create('casino_tiene_juego', function($table)
        {
            $table->increments('id_casino_tiene_juego');
            $table->integer('id_casino');
            $table->integer('id_juego');
            $table->foreign('id_casino')->references('id_casino')->on('casino');
            $table->foreign('id_juego')->references('id_juego')->on('juego');
        });

    }
    private function casinoSantaFe($id_juego) {
        $maquinasID=DB::table('maquina_tiene_juego')
            ->where('id_juego',$id_juego)
            ->pluck('id_maquina');
        foreach($maquinasID as $id_maquina){
            $maquina = Maquina::find($id_maquina);
            if($maquina!=null){
                if ($maquina->id_casino==2){
                    return true;
                };
            }
        }
        return false;
    }
    private function casinoMelincue($id_juego){
        $maquinasID=DB::table('maquina_tiene_juego')
            ->where('id_juego',$id_juego)
            ->pluck('id_maquina');
        foreach($maquinasID as $id_maquina){
            $maquina = Maquina::find($id_maquina);
            if($maquina!=null){
                if ($maquina->id_casino==1){
                    return true;
                };
            }
        }
        return false;
    }
    private function casinoRosario($id_juego){
        $maquinasID=DB::table('maquina_tiene_juego')
            ->where('id_juego',$id_juego)
            ->pluck('id_maquina');
        foreach($maquinasID as $id_maquina){
            $maquina = Maquina::find($id_maquina);
            if($maquina!=null){
                if ($maquina->id_casino==3){
                    return true;
                };
            }
        }
        return false;
    }

}