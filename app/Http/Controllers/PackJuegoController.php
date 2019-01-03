<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Usuario;
use App\Casino;
use App\Juego;
use App\PackJuego;
use Illuminate\Support\Facades\DB;
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

    public function buscarTodo(){
      //$uc = UsuarioController::getInstancia();
      $casinos = Casino::all();
      //$uc->agregarSeccionReciente('Juegos','juegos');

      return view('seccionPackJuegos' , ['casinos' => $casinos]);
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
        
      Validator::make($request->all(), [
        'identificador' => 'required|max:65',
        'prefijo' => 'required|max:6',
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
      // Se limita la asociacion, para que cada casino cree sus paquetes sin compartirse, si tiene mas de un casino tomaria el primero
      $packJuego->casinos()->syncWithoutDetaching($reglaCasinos[0]);
  
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

    public function buscar(Request $request){
      $reglas=array();
      $casinos = Usuario::find(session('id_usuario'))->casinos;
      $reglaCasinos=array();
      // if(!empty($request->nombreJuego) ){
      //   $reglas[]=['juego.nombre_juego', 'like' , '%' . $request->nombreJuego  .'%'];
      // }
      // if(!empty($request->codigoId)){
      //   $reglas[]=['gli_soft.nro_archivo', 'like' , '%' . $request->codigoId  .'%'];
      // }
      // if(!empty($request->cod_Juego)){
      //   $reglas[]=['juego.cod_juego', 'like' , '%' . $request->cod_Juego  .'%'];
      // }
  
       foreach($casinos as $casino){
        $reglaCasinos [] = $casino->id_casino;
       }
      
  
      $sort_by = $request->sort_by;
  
      $resultados=DB::table('pack_juego')
                    ->distinct()
                    ->select('pack_juego.*')
                    ->join('pack_juego_tiene_casino','pack_juego_tiene_casino.id_pack','=','pack_juego.id_pack')
                    ->wherein('pack_juego_tiene_casino.id_casino',$reglaCasinos)
                    ->when($sort_by,function($query) use ($sort_by){
                                    return $query->orderBy($sort_by['columna'],$sort_by['orden']);
                                })
                    
                    ->paginate($request->page_size);
                    // ->where($reglas)->paginate($request->page_size);
      foreach($resultados as $pj){
          $res=$this->obtenerJuegosDePack($pj->id_pack);
          $pj->cant_juegos=count($res);
      }
      
      return $resultados;
    }


    public function obtenerPackJuego($id){
      $packJuego = PackJuego::find($id);

      return ['pack' => $packJuego];
    }

    public function obtenerJuegosDePack($id){
      $casinos = Usuario::find(session('id_usuario'))->casinos;
      $reglaCasinos=array();
      foreach($casinos as $casino){
        $reglaCasinos [] = $casino->id_casino;
       }

      $resultado=DB::table('pack_tiene_juego')
                    ->distinct()
                    ->select('juego.nombre_juego','juego.id_juego')
                    ->join('juego','juego.id_juego','=','pack_tiene_juego.id_juego')
                    ->join('pack_juego_tiene_casino','pack_juego_tiene_casino.id_pack','=','pack_tiene_juego.id_pack')
                    ->where('pack_tiene_juego.id_pack','=',$id)
                    ->wherein('pack_juego_tiene_casino.id_casino',$reglaCasinos)->get();
                    
      return $resultado;              

    }


    public function eliminarPack($id){
      // quiuto de la tabla relacion 
      $casinos = Usuario::find(session('id_usuario'))->casinos;
      $reglaCasinos=array();
      foreach($casinos as $casino){
      $reglaCasinos [] = $casino->id_casino;
      }
      
  
      $pack = PackJuego::find($id);
      
      $pack->casinos()->detach($reglaCasinos);

      // bajo la idea que cada casino crea sus propios pack-juegos y solo pueden acceder estos
      // se elimina directamente la relacion
      $pack->juegos()->detach();

      
      DB::table('maquina_tiene_juego')
                ->where('id_pack','=',$pack->id_pack)
                ->update(['id_pack' => null ]);

      // solo si no queda asociado a nigun casino se puede eliminar el juego
      $casRestantes= DB::table('pack_juego_tiene_casino')->where('id_pack','=',$pack->id_pack)->count();
      if ($casRestantes==0){
        $pack->delete();
      }
      return 'ok';
    }


    public function modificarPackJuego(Request $request){

      Validator::make($request->all(), [
        'id_pack'=>'required|exists:pack_juego,id_pack',
        'identificador' => 'required|max:65',
        'prefijo' => 'required|max:6',
      ])->validate();
  
      $pack = PackJuego::find($request->id_pack);
      $pack->identificador=$request->identificador;
      $pack->prefijo=$request->prefijo;
      $resultado=$pack->save();
      
      return['resutlado' => $resultado];
    }


}
