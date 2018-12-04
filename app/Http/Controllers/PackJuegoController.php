<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Usuario;
use App\Casino;
use App\Juego;
use App\PackJuego;
use Validator;

class PackJuegoController extends Controller
{   

    private static $instance;

    public static function getInstancia() {
      if (!isset(self::$instance)) {
        self::$instance = new PackJuegoController();
      }
      return self::$instance;
    }

    // Busca los pack de juegos teniendo en cuenta los casinos que tiene el usuario
    public function buscarPackJuegoPorNombre($busqueda){
        $casinos = Usuario::find(session('id_usuario'))->casinos;
        $reglaCasinos=array();
        foreach($casinos as $casino){
          $reglaCasinos [] = $casino->id_casino;
         }
        $resultados=PackJuego::distinct()
                            ->select('pack_juego.*')
                            ->join('pack_juego_tiene_casino','pack_juego_tiene_casino.id_pack','=','pack_juego.id_pack')
                            ->wherein('pack_juego_tiene_casino.id_casino',$reglaCasinos)
                            ->where('identificador' , 'like' , $busqueda . '%')->get();
        return ['resultados' => $resultados];
      }

      public function guardarPackJuego(Request $request){
        //nombre de la var en js, para unique nombre de la tabla, nombre del campo que debe ser unico
      Validator::make($request->all(), [
        'identificador' => 'required|unique:pack_juego,identificador|max:65',
        'prefijo' => 'required|unique:pack_juego,prefijo|max:6',
      ])->validate();
  
      $packJuego = new PackJuego;
      $packJuego->identificador = $request->identificador;
      $packJuego->prefijo = $request->prefijo;
      //$juego->cod_identificacion= $request->cod_identificacion;
      $packJuego->save();  
      // asocio el nuevo juego con los casinos seleccionados
      $casinos = Usuario::find(session('id_usuario'))->casinos;
      $reglaCasinos=array();
      foreach($casinos as $casino){
      $reglaCasinos [] = $casino->id_casino;
      }
      $packJuego->casinos()->syncWithoutDetaching($reglaCasinos);
  
      return ['packJuego' => $packJuego];
    }

    public function asociarPackJuego(Request $request){
        Validator::make($request->all(), [
            'id_pack' => 'required|exists:pack_juego,id_pack',
            'juegos_ids' => 'nullable',
            'juegos_ids*' => 'required|exists:juego,id_juego',
          ])->validate();

          $packJuego=PackJuego::Find($request->id_pack);
          $packJuego->juegos()->sync($request->juegos_ids);

          return ['cantAsociados', $packJuego];
    }




}
