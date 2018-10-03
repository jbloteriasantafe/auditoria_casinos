<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\ContadorHorario;
use App\Producido;
use App\Beneficio;
use App\TipoMoneda;
use App\Http\Controllers\UsuarioController;
use App\Http\Controllers\RelevamientoController;
use App\Http\Controllers\LectorCSVController;
use Illuminate\Support\Facades\DB;
use Validator;
use App\Casino;

use App\Services\LengthPager;

class ImportacionController extends Controller
{
  private static $atributos = [
  ];
  private static $instance;

  public static function getInstancia() {
    if(!isset(self::$instance)) {
      self::$instance = new ImportacionController();
    }
    return self::$instance;
  }

  public function buscarTodo(){
    
    $tipoMoneda = TipoMoneda::all();
    UsuarioController::getInstancia()->agregarSeccionReciente('Importaciones' , 'importaciones');
    return view('seccionImportaciones', ['tipoMoneda' => $tipoMoneda, 'casinos' => Casino::all()]);
  }

  public function eliminarBeneficios(Request $request){
    //el request contiene mes anio id_tipo_moneda id_casino
    $beneficios = Beneficio::where([['id_tipo_moneda','=',$request['id_tipo_moneda']],['id_casino','=',$request['id_casino']]])
                            ->whereYear('fecha','=',$request['anio'])
                            ->whereMonth('fecha','=',$request['mes'])
                            ->get();
    if(isset($beneficios)){
      foreach ($beneficios as $b){
        BeneficioController::getInstancia()->eliminarBeneficio($b->id_beneficio);
      }
    }
    return 1;
  }

  public function previewBeneficios(Request $request){
    //el request contiene mes anio id_tipo_moneda id_casino
    $casino = Casino::find($request->id_casino);
    $tipo_moneda = TipoMoneda::find($request->id_tipo_moneda);

    $beneficios = Beneficio::where([['id_tipo_moneda','=',$request['id_tipo_moneda']],['id_casino','=',$request['id_casino']]])
                            ->whereYear('fecha','=',$request['anio'])
                            ->whereMonth('fecha','=',$request['mes'])
                            ->get();

    return ['beneficios'=>$beneficios, 'casino' => $casino, 'tipo_moneda' => $tipo_moneda];
  }

  public function obtenerVistaPrevia($tipo_importacion,$id){
    $detalles_contador=null;
    $detalles_producido=null;
    $contador=null;
    $producido=null;

    switch ($tipo_importacion) {
      case 1: //contador
        $contador = ContadorHorario::find($id);

        //$contadores = (ContadorHorario::find($id))->detalles;
        $detalles_contador = DB::table('contador_horario')
                          ->select('detalle_contador_horario.*','maquina.nro_admin')
                          ->join('detalle_contador_horario','detalle_contador_horario.id_contador_horario','=','contador_horario.id_contador_horario')
                          ->join('maquina','maquina.id_maquina','=','detalle_contador_horario.id_maquina')
                          ->where('contador_horario.id_contador_horario','=',$id)
                          ->take(30)
                          ->get();

        $tipo_moneda = $contador->tipo_moneda;
        $casino = $contador->casino;
        break;
      case 2: //producidos
        $producido = Producido::find($id);
        //$producidos = (Producido::find($id))->detalles;
        $detalles_producido = DB::table('producido')
                          ->select('detalle_producido.*','maquina.nro_admin')
                          ->join('detalle_producido','detalle_producido.id_producido','=','producido.id_producido')
                          ->join('maquina','maquina.id_maquina','=','detalle_producido.id_maquina')
                          ->where('producido.id_producido','=',$id)
                          ->take(30)
                          ->get();

        $tipo_moneda = $producido->tipo_moneda;
        $casino = $producido->casino;
        break;
      default:
        //nothing :)
        break;
    }

    return ['contador' => $contador , 'producido' => $producido,
            'tipo_moneda'  => $tipo_moneda, 'casino' => $casino,
            'detalles_contador' => $detalles_contador,'detalles_producido'=> $detalles_producido];
  }

  public function buscar(Request $request){
    $reglas = array();
    $casinos = array();
    if($request->casinos !=0){
      $casinos[] = $request->casinos;
    }else {
      $usuario = UsuarioController::getInstancia()->buscarUsuario(session('id_usuario'))['usuario'];
      foreach ($usuario->casinos as $casino) {
        $casinos[] = $casino->id_casino;
      }
    }

    if(isset($request->tipo_moneda) && $request->tipo_moneda !=0)
      $reglas[]=['tipo_moneda.id_tipo_moneda','=', $request->tipo_moneda];


    $contadores =array();
    $producidos =array();
    $beneficios =array();

    $sort_by = $request->sort_by;

    switch ($request->seleccion) {
      case 1://contadores
        if(!isset($request->fecha)){
          $contadores = DB::table('contador_horario')->select('contador_horario.id_contador_horario as id_contador_horario','contador_horario.fecha as fecha'
                                                              ,'casino.nombre as casino' , 'casino.id_casino as id_casino','tipo_moneda.descripcion as tipo_moneda','contador_horario.cerrado as cerrado')
                                                     ->join('casino','contador_horario.id_casino','=','casino.id_casino')
                                                     ->join('tipo_moneda','contador_horario.id_tipo_moneda','=','tipo_moneda.id_tipo_moneda')
                                                     ->where($reglas)
                                                     ->whereIn('casino.id_casino' , $casinos)
                                                     ->when($sort_by,function($query) use ($sort_by){
                                                                     return $query->orderBy($sort_by['columna'],$sort_by['orden']);
                                                                 })
                                                     ->paginate($request->page_size);

          }else{
            $fecha=explode("-", $request->fecha);
            $contadores = DB::table('contador_horario')
                             ->select('contador_horario.id_contador_horario as id_contador_horario','contador_horario.fecha as fecha'
                                      ,'casino.nombre as casino' , 'casino.id_casino as id_casino','tipo_moneda.descripcion as tipo_moneda','contador_horario.cerrado as cerrado')
                             ->join('casino','contador_horario.id_casino','=','casino.id_casino')
                             ->join('tipo_moneda','contador_horario.id_tipo_moneda','=','tipo_moneda.id_tipo_moneda')
                             ->where($reglas)
                             ->whereIn('casino.id_casino' , $casinos)
                             ->whereYear('contador_horario.fecha' , '=' ,$fecha[0])
                             ->whereMonth('contador_horario.fecha','=', $fecha[1])
                             ->when($sort_by,function($query) use ($sort_by){
                                             return $query->orderBy($sort_by['columna'],$sort_by['orden']);
                                         })

                             ->paginate($request->page_size);
          }
          break;
      case 2://producidos
        if(empty($request->fecha)){
          $producidos = DB::table('producido')->select('producido.id_producido as id_producido','producido.fecha as fecha'
                                                      ,'casino.nombre as casino','tipo_moneda.descripcion as tipo_moneda','producido.validado as validado')
                                              ->join('casino','producido.id_casino','=','casino.id_casino')
                                              ->join('tipo_moneda','producido.id_tipo_moneda','=','tipo_moneda.id_tipo_moneda')
                                              ->where($reglas)
                                              ->whereIn('casino.id_casino' , $casinos)
                                              ->when($sort_by,function($query) use ($sort_by){
                                                              return $query->orderBy($sort_by['columna'],$sort_by['orden']);
                                                          })

                                              ->paginate($request->page_size);

        }else{
          $fecha=explode("-", $request->fecha);
          $producidos = DB::table('producido')->select('producido.id_producido as id_producido','producido.fecha as fecha'
                                                      ,'casino.nombre as casino','tipo_moneda.descripcion as tipo_moneda','producido.validado as validado')
                                              ->join('casino','producido.id_casino','=','casino.id_casino')
                                              ->join('tipo_moneda','producido.id_tipo_moneda','=','tipo_moneda.id_tipo_moneda')
                                              ->where($reglas)
                                              ->whereIn('casino.id_casino' , $casinos)
                                              ->whereYear('producido.fecha' , '=' ,$fecha[0])
                                              ->whereMonth('producido.fecha','=', $fecha[1])
                                              ->when($sort_by,function($query) use ($sort_by){
                                                              return $query->orderBy($sort_by['columna'],$sort_by['orden']);
                                                          })

                                              ->paginate($request->page_size);
        }
        break;
      case 3://beneficios
      $reglas2 = array();

      if($request->sort_by['columna'] == "beneficio.fecha"){
        $sort_by['columna'] = 'anio,mes';

      }


      if(!empty($request->tipo_moneda) && $request->tipo_moneda !=0)
        $reglas2[]=['id_tipo_moneda','=', $request->tipo_moneda];

        $createTempTables = DB::unprepared(DB::raw("CREATE TEMPORARY TABLE beneficios_temporal
                                                            AS (
                                                                SELECT MONTH(beneficio.fecha) as mes,
                                                                       YEAR(beneficio.fecha) as anio,
                                                                       casino.*,
                                                                       tipo_moneda.*
                                                                FROM beneficio inner join casino on beneficio.id_casino = casino.id_casino
                                                                     inner join tipo_moneda on beneficio.id_tipo_moneda = tipo_moneda.id_tipo_moneda
                                                                );
                                             "
                                             )
                                       );

        if(empty($request->fecha)){// si fecha esta vacio
        if($createTempTables){
           $beneficios = DB::table('beneficios_temporal')->select('mes','anio','nombre as casino','id_casino','id_tipo_moneda','descripcion as tipo_moneda')
                             ->where($reglas2)
                             ->whereIn('id_casino' , $casinos)
                             ->groupBy('mes','anio','nombre','descripcion','id_casino','id_tipo_moneda')->when($sort_by,function($query) use ($sort_by){
                                              return $query->orderBy(DB::raw($sort_by['columna']),$sort_by['orden']);
                                         })
                            ->paginate($request->page_size);
           $query1 = DB::statement(DB::raw("
                                              DROP TABLE beneficios_temporal
                                          "));
         }else {
                $error = "ERROR MESSAGE";
                dd($error);
        }

        }else{
          $fecha=explode("-", $request->fecha);

          if($createTempTables){
            $beneficios = DB::table('beneficios_temporal')->select('mes','anio','nombre as casino','descripcion as tipo_moneda','id_casino','id_tipo_moneda')
                              ->where($reglas2)
                              ->where('anio' , '=' ,$fecha[0])
                              ->where('mes','=', $fecha[1])
                              ->groupBy('mes','anio','nombre','descripcion','id_casino','id_tipo_moneda')->when($sort_by,function($query) use ($sort_by){
                                               return $query->orderBy(DB::raw($sort_by['columna']),$sort_by['orden']);
                                          })
                             ->paginate($request->page_size);
            $query1 = DB::statement(DB::raw("
                                               DROP TABLE beneficios_temporal
                                           "));
          }else {
                 $error = "ERROR MESSAGE";
                 dd($error);
         }


        }
        break;
    default:
      //nada
        break;
    }

    foreach ($contadores as $index => $contador){
      if($contador->id_casino == 1 || $contador->id_casino == 2){
        $contadores[$index]->fecha_archivo = date('Y-m-d' , strtotime($contador->fecha . ' - 1 days'));
      }else {//rosario
        $contadores[$index]->fecha_archivo = $contador->fecha;
      }
    }

      return  ['contadores' => $contadores, 'producidos' => $producidos, 'beneficios' => $beneficios];
  }



  public function estadoImportacionesDeCasino($id_casino){
    //modficar para que tome ultimos dias con datos, no solo los ultimos dias
    Validator::make([
         'id_casino' => $id_casino,
       ],
       [
         'id_casino' => 'required|exists:casino,id_casino' ,
       ] , array(), self::$atributos)->after(function ($validator){

    })->validate();

    $fecha= date('Y-m-d');//hoy

    $no_es_fin = true;
    $i= $suma = 0;
    $arreglo = array();

    while($no_es_fin){
      $aux= new \stdClass();
      $valor = 0;
      if($id_casino == 3){//si es rosario tengo $ y DOL
        $contador['pesos'] = ContadorHorario::where([['fecha' , $fecha],['id_casino', $id_casino] ,['id_tipo_moneda' , 1]])->count() >= 1 ? true :false;
        $producido['pesos'] = Producido::where([['fecha' , $fecha],['id_casino', $id_casino] ,['id_tipo_moneda' , 1]])->count() >= 1 ? true : false;
        $beneficio['pesos'] = Beneficio::where([['fecha' , $fecha],['id_casino', $id_casino] ,['id_tipo_moneda' , 1]])->count() >= 1 ? true : false;
        $contador['dolares'] = ContadorHorario::where([['fecha' , $fecha],['id_casino', $id_casino] ,['id_tipo_moneda' , 2]])->count() >= 1 ? true : false;
        $producido['dolares'] = Producido::where([['fecha' , $fecha],['id_casino', $id_casino] ,['id_tipo_moneda' , 2]])->count() >= 1 ? true : false;
        $beneficio['dolares'] = Beneficio::where([['fecha' , $fecha],['id_casino', $id_casino] ,['id_tipo_moneda' , 2]])->count() >= 1 ? true : false;
      }else{
        $contador['pesos'] = ContadorHorario::where([['fecha',$fecha],['id_casino', $id_casino]])->count() >= 1 ? true : false;
        $producido['pesos'] = Producido::where([['fecha',$fecha],['id_casino',$id_casino]])->count() >= 1 ? true : false;
        $beneficio['pesos'] = Beneficio::where([['fecha' , $fecha],['id_casino',$id_casino]])->count() >= 1 ? true : false;
      }
      $dia['contador'] = $contador;
      $dia['producido'] = $producido;
      $dia['beneficio'] = $beneficio;
      $dia['fecha'] = $fecha;
      $arreglo[] = $dia;
      $i++;
      $fecha=date('Y-m-d' , strtotime($fecha . ' - 1 days'));
      if($i == 30) $no_es_fin = false;//final
    }
    return ['arreglo' => $arreglo];
  }

  public function importarContador(Request $request){
    Validator::make($request->all(),[
        'id_casino' => 'required|integer|exists:casino,id_casino',
        'fecha' => 'nullable|date',
        'archivo' => 'required|mimes:csv,txt',
        'id_tipo_moneda' => 'nullable|exists:tipo_moneda,id_tipo_moneda'
    ], array(), self::$atributos)->after(function($validator){
        if($validator->getData()['fecha'] != null){
          $reglas = Array();
          $reglas[]=['fecha','=',$validator->getData()['fecha']];
          $reglas[]=['id_casino','=',$validator->getData()['id_casino']];
          $reglas[]=['cerrado','=',1];
          if($validator->getData()['id_tipo_moneda'] != null){
            $reglas[]=['id_tipo_moneda','=',$validator->getData()['id_tipo_moneda']];
          }
          if(ContadorHorario::where($reglas)->count() > 0){
            $validator->errors()->add('contador_cerrado', 'El Contador para esa fecha ya está cerrado y no se puede reimportar.');
          }


        }
    })->validate();

    

    if(RelevamientoController::getInstancia()->existeRelVisado($request['fecha'], $request['id_casino'])){
      return ['resultado' => 'existeRel'];
    }

    switch($request->id_casino){
      case 1:
        return LectorCSVController::getInstancia()->importarContadorSantaFeMelincue($request->archivo,1);
        break;
      case 2:
        return LectorCSVController::getInstancia()->importarContadorSantaFeMelincue($request->archivo,2);
        break;
      case 3:
        return LectorCSVController::getInstancia()->importarContadorRosario($request->archivo,$request->fecha,$request->id_tipo_moneda);
        break;
      default:
        break;
    }
    
  }

  public function importarProducido(Request $request){
    Validator::make($request->all(),[
        'id_casino' => 'required|integer|exists:casino,id_casino',
        'fecha' => 'nullable|date',
        'archivo' => 'required|mimes:csv,txt',
        'id_tipo_moneda' => 'nullable|exists:tipo_moneda,id_tipo_moneda'
    ], array(), self::$atributos)->after(function($validator){
        if($validator->getData()['fecha'] != null){
          $reglas = Array();
          $reglas[]=['fecha','=',$validator->getData()['fecha']];
          $reglas[]=['id_casino','=',$validator->getData()['id_casino']];
          $reglas[]=['validado','=',1];
          if($validator->getData()['id_tipo_moneda'] != null){
            $reglas[]=['id_tipo_moneda','=',$validator->getData()['id_tipo_moneda']];
          }
          if(Producido::where($reglas)->count() > 0){
            $validator->errors()->add('producido_validado', 'El Producido para esa fecha ya está validado y no se puede reimportar.');
          }
        }
    })->validate();

    switch($request->id_casino){
      case 1:
        return LectorCSVController::getInstancia()->importarProducidoSantaFeMelincue($request->archivo,1);
        break;
      case 2:
        return LectorCSVController::getInstancia()->importarProducidoSantaFeMelincue($request->archivo,2);
        break;
      case 3:
        return LectorCSVController::getInstancia()->importarProducidoRosario($request->archivo,$request->fecha,$request->id_tipo_moneda);
        break;
      default:
        break;
    }
  }

  public function importarBeneficio(Request $request){
    Validator::make($request->all(),[
        'id_casino' => 'required|integer|exists:casino,id_casino',
        'archivo' => 'required|mimes:csv,txt',
        'id_tipo_moneda' => 'nullable|exists:tipo_moneda,id_tipo_moneda'
    ], array(), self::$atributos)->after(function($validator){
    })->validate();
    switch($request->id_casino){
      case 1:
        return LectorCSVController::getInstancia()->importarBeneficioSantaFeMelincue($request->archivo,1);
        break;
      case 2:
        return LectorCSVController::getInstancia()->importarBeneficioSantaFeMelincue($request->archivo,2);
        break;
      case 3:
        return LectorCSVController::getInstancia()->importarBeneficioRosario($request->archivo,$request->id_tipo_moneda);
        break;
      default:
        break;
    }
  }

}
