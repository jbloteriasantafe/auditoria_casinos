<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\DB;
use App\Juego;
use App\TablaPago;
use App\Casino;
use App\GliSoft;
use App\Maquina;
use App\Usuario;
use Validator;

class JuegoController extends Controller
{
  private static $atributos = [
    'nombre_juego' => 'Nombre de Juego',
    'cod_identificacion' => 'Código de Identificación',
    'tablasDePago.*.codigo' => 'Código de Identificación',
  ];

  private static $instance;

  public static function getInstancia() {
    if (!isset(self::$instance)) {
      self::$instance = new JuegoController();
    }
    return self::$instance;
  }

  public function buscarTodo(){
    $uc = UsuarioController::getInstancia();
    $uc->agregarSeccionReciente('Juegos','juegos');
    $usuario = $uc->quienSoy()['usuario'];
    $casinos = $usuario->casinos;
    $maquinas_casinos = [];
    foreach($casinos as $c) $maquinas_casinos[$c->id_casino] = $c->maquinas->toArray();
    return view('seccionJuegos' , 
    ['casinos' => $casinos,
     'maquinas_casinos' => $maquinas_casinos,
     'certificados' => GliSoft::all()
    ]);
  }

  public function obtenerJuego($id){
    $juego = Juego::find($id);
    $casinosUser = Usuario::find(session('id_usuario'))->casinos;
    $reglaCasinos=array();
    foreach($casinosUser as $casino){
      $reglaCasinos [] = $casino->id_casino;
    }

    $maquinas= array();
    foreach ($juego->maquinas_juegos->whereIn('id_casino',$reglaCasinos) as $key => $mtm) {
      $maquina = new \stdClass();
      $maquina->id_maquina = $mtm->id_maquina;
      $maquina->id_casino = $mtm->id_casino;
      $maquina->nro_admin = $mtm->nro_admin;
      $maquina->porcentaje_devolucion =  $mtm->pivot->porcentaje_devolucion;
      $maquina->denominacion = $mtm->pivot->denominacion;
      $maquinas[] = $maquina;
    }

    $packJuego=DB::table('pack_juego')
                  ->select('pack_juego.*')
                  ->distinct()
                  ->join('pack_tiene_juego','pack_tiene_juego.id_pack','=','pack_juego.id_pack')
                  ->join('pack_juego_tiene_casino','pack_juego_tiene_casino.id_pack','=','pack_juego.id_pack')
                  ->where('pack_tiene_juego.id_juego','=',$juego->id_juego)
                  ->wherein('pack_juego_tiene_casino.id_casino',$reglaCasinos)
                  ->get();

    $tabla = TablaPago::where('id_juego', '=', $id)->get();

    return ['juego' => $juego ,
            'tablasDePago' => $tabla,
            'maquinas' => $maquinas,
            'pack'=>$packJuego,
            'certificadoSoft' => $this->obtenerCertificadosSoft($id)];
  }

  public function encontrarOCrear($juego){
        $resultado=$this->buscarJuegoPorNombre($juego);
        if(count($resultado)==0){
            $juegoNuevo=new Juego;
            $juegoNuevo->nombre_juego=trim($juego);
            $juegoNuevo->save();
        }else{
            $juegoNuevo=$resultado[0];
        }
        return $juegoNuevo;
  }

  public function validarJuegoTemporal(Request $request){

    Validator::make($request->all() , [
      'id_juego' => 'nullable|integer|exists:juego,id_juego',
      'nombre_juego' => 'required',
      'id_tabla_pago' => 'nullable|integer|exists:juego,id_juego',
      'codigo_tabla_pago' => 'required',
    ])->after(function ($validator) {
        if($validator->getData()['id_juego'] != 0){
         $res= Juego::where('id_juego' , '=' ,$validator->getData()['id_juego'])->get();
         if($res[0]->nombre_juego !=  $validator->getData()['nombre_juego']){
           $validator->errors()->add('nombre_juego','El nombre del Juego ya está tomado');
         }
        }else{

        }
    })->validate();

  }

  public function guardarJuego(Request $request){
      //nombre de la var en js, para unique nombre de la tabla, nombre del campo que debe ser unico
    Validator::make($request->all(), [
      //'nombre_juego' => 'required|unique:juego,nombre_juego|max:100',
      'cod_identificacion' => ['nullable','regex:/^\d?\w(.|-|_|\d|\w)*$/','unique:juego,cod_identificacion','max:100'],
      'tabla_pago.*' => 'nullable',
      'cod_juego' => 'nullable',
      'tabla_pago.*.id_tabla_pago' => 'nullable',
      'tabla_pago.*.codigo' => 'required',
      'maquinas.*' => 'nullable',
      'maquinas.*.nro_admin' => 'required',
      'maquinas.*.id_maquina' => 'required',
      'maquinas.*.denominacion' => 'nullable',
      'maquinas.*.porcentaje' => 'nullable',
      'id_progresivo' => 'nullable',
    ], array(), self::$atributos)->validate();


    $casinos = Usuario::find(session('id_usuario'))->casinos;
    $reglaCasinos=array();
    foreach($casinos as $casino){
      $reglaCasinos[] = $casino->id_casino;
    }

    $juego = new Juego;
    DB::transaction(function() use($juego,$reglaCasinos,$request){
      $juego->nombre_juego = $request->nombre_juego;
      $juego->cod_juego = $request->cod_juego;
      $juego->save();
      
      // asocio el nuevo juego con los casinos seleccionados  
      $juego->casinos()->syncWithoutDetaching($reglaCasinos);
  
      if(isset($request->maquinas)){
        foreach ($request->maquinas as $maquina) {
          if($maquina['id_maquina'] == 0){
            $mtm = Maquina::where([['id_casino' , $maquina['id_casino']] , ['nro_admin' , $maquina['nro_admin']]])->first();
          }else {
            $mtm = Maquina::find($maquina['id_maquina']);
          }
          if($mtm != null){
            $mtm->juegos()->syncWithoutDetaching([$juego->id_juego => ['denominacion' => $maquina['denominacion'] ,'porcentaje_devolucion' => $maquina['porcentaje']]]);
          }
        }
      }
  
      if(!empty($request->tabla_pago)){
        foreach ($request->tabla_pago as $tabla){
          TablaPagoController::getInstancia()->guardarTablaPago($tabla,$juego->id_juego);
        }
      }
    });

    return ['juego' => $juego];
  }

  public function guardarJuego_gestionarMaquina($nombre_juego,$arreglo_tablas){
    //funcion encargada de crear juego si este fue creado en "GESTIONAR MÁQUINA"
    Validator::make(['nombre_juego' => $nombre_juego], [
      'nombre_juego' => 'required|unique:juego,nombre_juego|max:100',
    ], array(), self::$atributos)->validate();

    $juego = new Juego;
    $juego->nombre_juego = $nombre_juego;
    $juego->save();

    if(!empty($arreglo_tablas)){//si no viene vacio
      foreach ($arreglo_tablas as $tabla){
        TablaPagoController::getInstancia()->guardarTablaPago($tabla,$juego->id_juego);
      }
    }

    return $juego;
  }

  public function modificarJuego(Request $request){

    Validator::make($request->all(), [
      'nombre_juego' => 'required|max:100',
      'cod_identificacion' => ['nullable','regex:/^\d?\w(.|-|_|\d|\w)*$/','max:100'],
      'tabla_pago.*' => 'nullable',
      'tabla_pago.*.id_tabla_pago' => 'nullable',
      'tabla_pago.*.codigo' => 'required',
      'maquinas.*' => 'nullable',
      'maquinas.*.nro_admin' => 'required',
      'maquinas.*.id_maquina' => 'required',
      'maquinas.*.denominacion' => 'nullable',
      'maquinas.*.porcentaje' => 'nullable',
      'id_progresivo' => 'nullable',
    ], array(), self::$atributos)->after(function ($validator) {

        if($validator->getData()['id_juego'] != 0){

        }

    })->validate();


    $juego = Juego::find($request->id_juego);

    if(is_null($juego)) return $this->errorOut(['id_juego' => ['No existe el juego.']]);

    $usuario = UsuarioController::getInstancia()->quienSoy()['usuario'];
    $ids_casinos = [];
    foreach($usuario->casinos as $c){
      $ids_casinos[] = $c->id_casino;
    }

    //Me fijo si existe entrada alguna de los casinos del usuario con ese juego
    $acceso = $juego->casinos()->whereIn('casino.id_casino',$ids_casinos)->count();
    if($acceso == 0) return $this->errorOut(['acceso' => ['El usuario no puede acceder a ese juego.']]);

    $maquinas_accesibles = $juego->maquinas_juegos()
    ->whereIn('id_casino',$ids_casinos)->get();

    DB::transaction(function() use($request,$maquinas_accesibles,$juego){
      $juego->nombre_juego= $request->nombre_juego;
      if($request->cod_juego!=null){
        $juego->cod_juego= $request->cod_juego;
      }
      
      $juego->save();

      //Le saco las tablas de pago
      foreach ($juego->tablasPago as $tabla) {
        $tabla->delete();
      };

      //Seteo las enviadas
      if(isset($request->tabla_pago)){
        foreach ($request->tabla_pago as $key => $tabla) {
          TablaPagoController::getInstancia()->guardarTablaPago($tabla,$juego->id_juego);
        };
      }

      //Al juego le saco las maquinas a las que puede acceder el usuario
      foreach($maquinas_accesibles as $mtm){
        $mtm->juegos()->detach($juego->id_juego);
      }
      
      if(isset($request->maquinas)){
        //Agrego las que me mande
        foreach ($request->maquinas as $maquina){
          if ($maquina['id_maquina'] == 0) {
            $mtm = Maquina::where([['id_casino' , $maquina['id_casino']],['nro_admin', $maquina['nro_admin']]])->first();
          }else {
            $mtm = Maquina::find($maquina['id_maquina']);
          }
          $mtm->juegos()->syncWithoutDetaching([$juego->id_juego => ['denominacion' => $maquina['denominacion'] ,'porcentaje_devolucion' => $maquina['porcentaje']]]);
        }
      }  
    });

    return ['juego' => $juego];
  }

  private function existeTablaPago($id,$tablas){
    $result = false;
    for($i = 0;$i<count($tablas);$i++){
      if($id == $tablas[$i]['id_tabla_pago']){
        $result = true;
        break;
      }
    }
    return $result;
  }

  public function eliminarJuego($id){
    $casinos = Usuario::find(session('id_usuario'))->casinos;
    $reglaCasinos=array();
    foreach($casinos as $casino){
      $reglaCasinos [] = $casino->id_casino;
    }
    
    $juego = Juego::find($id);
    if(is_null($juego)) return ['juego' => null];

    DB::transaction(function() use($juego,$reglaCasinos){
      $juego->casinos()->detach($reglaCasinos);
      // @TODO: Si tuvieramos GLISOFT por casino, podriamos detachearlo aca nomas
      // Solo si no queda asociado a ningun casino se puede eliminar el juego
      $casRestantes= DB::table('casino_tiene_juego')->where('casino_tiene_juego.id_juego','=',$juego->id_juego)->count();
      if ($casRestantes==0){
        foreach ($juego->tablasPago as $tabla) {
          TablaPagoController::getInstancia()->eliminarTablaPago($tabla->id_tabla_pago);
        }        
        $juego->setearGliSofts([]);
        $juego->delete();
      }
    });

    return ['juego' => $juego];
  }

  public function getAll(){
    $todos=Juego::all();
    return $todos;
  }

  //busca juegos bajo el criterio "contiene". @param nombre_juego, cod_identificacion
  public function buscarJuegoPorCodigoYNombre($busqueda){
    $casinos = Usuario::find(session('id_usuario'))->casinos;
    $reglaCasinos=array();
    foreach($casinos as $casino){
      $reglaCasinos [] = $casino->id_casino;
     }
    $resultados=Juego::distinct()
                      ->select('juego.*')
                      ->join('casino_tiene_juego','casino_tiene_juego.id_juego','=','juego.id_juego')
                      ->wherein('casino_tiene_juego.id_casino',$reglaCasinos)
                      ->where('nombre_juego' , 'like' , $busqueda . '%')->get();
                      //->orWhere('cod_identificacion' , 'like' , $busqueda . '%')->get();

    return ['resultados' => $resultados];
  }

  //busca UN juego que coincida con el nombre  @param $nombre_juego
  public function buscarJuegoPorNombre($nombre_juego){
    $resultado=Juego::where('nombre_juego' , '=' , trim($nombre_juego))->get();
    return $resultado;
  }

  public function buscarJuegoMovimientos($nombre_juego){
    $resultado=Juego::where('nombre_juego' , 'like' , '%' .$nombre_juego.'%')->get();
    return ['juegos' =>$resultado];
  }


  public function buscarJuegos(Request $request){
    $reglas=array();
    $casinos = Usuario::find(session('id_usuario'))->casinos;
    $reglaCasinos=array();
    if(!empty($request->nombreJuego) ){
      $reglas[]=['juego.nombre_juego', 'like' , '%' . $request->nombreJuego  .'%'];
    }
 
    if(!empty($request->cod_Juego)){
      $reglas[]=['juego.cod_juego', 'like' , '%' . $request->cod_Juego  .'%'];
    }

    foreach($casinos as $casino){
      $reglaCasinos [] = $casino->id_casino;
    }
    
    $sort_by = $request->sort_by;

    $resultados=DB::table('juego')
                  ->select('juego.*')
                  ->selectRaw("GROUP_CONCAT(DISTINCT(IFNULL(gli_soft.nro_archivo, '-')) separator ', ') as certificados")
                  ->leftjoin('juego_glisoft as jgl','jgl.id_juego','=','juego.id_juego')
                  ->leftjoin('gli_soft','gli_soft.id_gli_soft','=','jgl.id_gli_soft')
                  ->leftjoin('casino_tiene_juego','casino_tiene_juego.id_juego','=','juego.id_juego')
                  ->when($sort_by,function($query) use ($sort_by){
                                  return $query->orderBy($sort_by['columna'],$sort_by['orden']);
                              })
                  ->wherein('casino_tiene_juego.id_casino',$reglaCasinos)
                  ->where($reglas);
    
    if(!empty($request->codigoId)){
      if(trim($request->codigoId) == '-'){//Si me envia un gion, significa sin certificado
        $resultados = $resultados->whereNull('gli_soft.id_gli_soft');
      }
      else {
        $codigos = explode(',',$request->codigoId);
        foreach($codigos as &$c) $c = trim($c);

        $resultados = $resultados->where(function ($query) use ($codigos){
          foreach($codigos as $idx => $c){
            if($idx == 0) $query->where('gli_soft.nro_archivo','like','%'.$c.'%');
            else $query->orWhere('gli_soft.nro_archivo','like','%'.$c.'%');
          }
        });

      }
    }

    $resultados = $resultados->groupBy('juego.id_juego');
    $resultados = $resultados->paginate($request->page_size);
    return $resultados;
  }

  public function desasociarGLI($id_gli_soft){
    $GLI = GliSoft::find($id_gli_soft);
    if($GLI===null) return;
    $juegos=$GLI->juegos;
    foreach ($juegos as $juego) {
      $juego->gliSoftOld()->dissociate();
      $juego->save();
    }
    $GLI->setearJuegos([]);
  }

  public function asociarGLI($listaJuegos , $id_gli_soft){
    foreach ($listaJuegos as $id_juego) {
       $juego=Juego::find($id_juego);
       $juego->gliSoftOld()->associate($id_gli_soft);
       $juego->save();
    }
    $GLI = GliSoft::find($id_gli_soft);
    if($GLI != null){
      $GLI->setearJuegos([]);
      //Por si manda varias veces el mismo juego lo filtro
      $GLI->setearJuegos(array_unique($listaJuegos),true);
      $GLI->save();
    }
  }

  public function obtenerTablasDePago($id){
    $juego=Juego::find($id);
    if($juego != null){
    return['tablasDePago' => $juego->tablasPago];
  }else{
    return['tablasDePago' => null];
  }
  }

  public function obtenerCertificadosSoft($id){
    $juego=Juego::find($id);
    if($juego != null){
      $certificados = $juego->gliSoft;
      $ret = [];
      foreach($certificados as $c){
        $nombre_archivo = is_null($c->archivo)? null : $c->archivo->nombre_archivo;
        $ret[] = ['certificado' => $c, 'archivo' => $nombre_archivo];
      } 
      return $ret;
    }
    return ['certificadosSoft' => null];
  }

  private function errorOut($map){
    return response()->json($map,422);
  }
}
