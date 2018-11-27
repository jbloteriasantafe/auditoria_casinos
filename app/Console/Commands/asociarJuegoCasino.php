<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Juego;
use App\Maquina;
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
        $juegos=Juego::all();
        foreach($juegos as $juego){
            echo($juego->id_juego);
            $casinos=$this->casinos($juego->id_juego);
            $juego->casinos()->syncWithoutDetaching($casinos);
        }
    }

    private function casinos($id_juego){
        echo(id_juego);
        $casinos=array();
        if($this->casinoMelincue($id_juego)){
            $casinos[]=1;
        };
        if($this->casinoSantaFe($id_juego)){
            $casinos[]=2;
        };
        if($this->casinoRosario($id_juego)){
            $casinos[]=3;
        };
        return $casinos;
    }

    private function casinoSantaFe($id_juego) {
        $maquinasID=DB::table('maquina_tiene_juego')
            ->select('maquina_tiene_juego.id_maquina')
            ->where('maquina_tiene_juego.id_juego','=',$id_juego)
            ->get();
        foreach($maquinasID as $id_maquina){
            $maquina = Maquina::find($id_maquina);
            if ($maquina->id_casino==2){
                return true;
            };
        }
        return false;
    }
    private function casinoMelincue($id_juego){
        $maquinasID=DB::table('maquina_tiene_juego')
            ->select('maquina_tiene_juego.id_maquina')
            ->where('maquina_tiene_juego.id_juego','=',$id_juego)
            ->get();
        foreach($maquinasID as $id_maquina){
            $maquina = Maquina::find($id_maquina);
            if ($maquina->id_casino==1){
                return true;
            };
        }
        return false;
    }
    private function casinoRosario($id_juego){
        $maquinasID=DB::table('maquina_tiene_juego')
            ->select('maquina_tiene_juego.id_maquina')
            ->where('maquina_tiene_juego.id_juego','=',$id_juego)
            ->get();
        foreach($maquinasID as $id_maquina){
            $maquina = Maquina::find($id_maquina);
            if ($maquina->id_casino==3){
                return true;
            };
        }
        return false;
    }

}
