<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Usuario;
use App\Casino;
use App\Juego;
use App\PackJuego;
use App\Maquina;
use Illuminate\Support\Facades\DB;
use Validator;

// PackJuegoController ABM de pack de juegos, tambies es resposable de mantener
// asociaciones con juegos y mtm
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

    // buscarPackJuegoPorNombre busca los pack de juegos teniendo en cuenta los casinos que tiene el usuario
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

      // guardarPackJuego crea una nueva intancia de paquete juego
      // si el usuario tiene mas de un casino (solo el SU), se tomará el primero
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

    // asociarPackJuego sicroniza los ids de juego con un paquete
    public function asociarPackJuego(Request $request){
        Validator::make($request->all(), [
            'id_pack' => 'required|exists:pack_juego,id_pack',
            'juegos_ids' => 'nullable',
            'juegos_ids*' => 'required|exists:juego,id_juego',
          ])->validate();

          $packJuego=PackJuego::Find($request->id_pack);
          $packJuego->juegos()->sync($request->juegos_ids);
          // TODO ver si tendria utilidad mas informacion, por el momento basta con el paquete
          return ['cantAsociados', $packJuego];
    }

    // buscar retorna los paquetes de acuerdo a los filtros de busqueda
    // como no se definieron los filtros por los usuarios, se deja comentado 
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

    // obtenerPackJuego obtiene paquete por id
    public function obtenerPackJuego($id){
      $packJuego = PackJuego::find($id);

      return ['pack' => $packJuego];
    }

    // obtenerJuegosDePack obtiene todos los juegos de un pack
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

    // eliminarPack elimina la asociacion del pack con el casino
    // si es el ultimo, elimina el pack
    public function eliminarPack($id){
      // quito de la tabla relacion 
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

      DB::table('maquina')
                ->where('id_pack','=',$pack->id_pack)
                ->update(['id_pack' => null ]);

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

    // modificarPackJuego modifica solo los datos asociados al pack
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


    // obtenerJuegosDePackMTM obtiene todo los juegos relacionados con la maquina que pertenecen al paquete
  public function obtenerJuegosDePackMTM($id_maquina){
    // TODO pasar a request para validar
    $mtm = Maquina::find($id_maquina);  
    $id_pack=$mtm->id_pack;
    $resultados=array();
    // caso donde la maquina aun no es multijuego
    if ($id_pack==null){
      return ['juegos'=> $resultados];
    }

    // caso en donde la maquina tiene paquete asignado
    
    $pack=PackJuego::find($id_pack);
    array_push($resultados,$pack);

    $juegosMTM=$mtm->juegos;
    
    // tomo como base los juegos del pack y voy pisando con los valores de la maquina
    foreach($pack->juegos as $jp){
      $obj= new \stdClass();
      $obj->id_juego=$jp->id_juego;
      $obj->nombre_juego=$jp->nombre_juego;
      $obj->habilitado=false;

      // si la maquina lo tiene habilitado se pisa con esa informacion
      foreach($juegosMTM as $jM){
        if ($jp->id_juego==$jM->id_juego ){
         $obj->denominacion=$jM->pivot->denominacion;
         $obj->porcentaje_devolucion=$jM->pivot->porcentaje_devolucion;
          if ($jM->pivot->habilitado!=0){
            $obj->habilitado=true;
          }
        
        }
        
      }

      array_push($resultados,$obj);

    }
    
    return ['juegos'=>$resultados];

  }

  // asociarMtmJuegosPack apartir de los juegos del pack, crea la realacion de juego con mtm
  // con los datos en la tabla relacion de denominacion , %dev y el id_pack distinto de null
  public function asociarMtmJuegosPack(Request $data){
    Validator::make($data->all(), [
      'id_mtm'=>'required|exists:maquina,id_maquina',
    ])->validate();
    $MTM=Maquina::find($data['id_mtm']);
    $juegos_finales=array();
    $id_pack=$data['id_pack'];
    if ($id_pack=='-1'){
      $MTM->id_pack=null;

    }else{
      $MTM->id_pack=$id_pack;
      foreach($data['juegos'] as $unJuego){
        if($unJuego['habilitado']=='true'){
          $habilitado=1;
        }else{
          $habilitado=0;
        }
        $juegos_finales[ $unJuego['id_juego']] = ['denominacion' => $unJuego['denominacion'], 'porcentaje_devolucion' => $unJuego['devolucion'],'id_pack' => $id_pack, 'habilitado' => $habilitado]; 
      }
    }

    $MTM->juegos()->sync($juegos_finales);
    $MTM->save();
    return ['OK'=> 'ok'];

  }

}
