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
use App\Relevamiento;
use App\DetalleRelevamiento;
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
    return BeneficioController::getInstancia()->eliminarBeneficios(
      $request['id_casino'],
      $request['id_tipo_moneda'],
      $request['anio'],
      $request['mes']
    );
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

  public function previewProducidos(Request $request){
    $producido = Producido::find($request->id);
    $detalles_producido = DB::table('producido')
                      ->select('detalle_producido.*','maquina.nro_admin')
                      ->join('detalle_producido','detalle_producido.id_producido','=','producido.id_producido')
                      ->join('maquina','maquina.id_maquina','=','detalle_producido.id_maquina')
                      ->where('producido.id_producido','=',$request->id)
                      ->take(30)
                      ->get();

    return ['producido' => $producido,'tipo_moneda'  =>  $producido->tipo_moneda, 'casino' => $producido->casino,'detalles_producido'=> $detalles_producido];
  }

  public function previewContadores(Request $request){
    $contador = ContadorHorario::find($request->id);

    $detalles_contador = DB::table('contador_horario')
                      ->select('detalle_contador_horario.*','maquina.nro_admin')
                      ->join('detalle_contador_horario','detalle_contador_horario.id_contador_horario','=','contador_horario.id_contador_horario')
                      ->join('maquina','maquina.id_maquina','=','detalle_contador_horario.id_maquina')
                      ->where('contador_horario.id_contador_horario','=',$request->id)
                      ->take(30)
                      ->get();

    return ['contador' => $contador, 'tipo_moneda'  => $contador->tipo_moneda, 'casino' => $contador->casino, 'detalles_contador' => $detalles_contador];
  }

  public function buscar(Request $request){
    $usuario = UsuarioController::getInstancia()->buscarUsuario(session('id_usuario'))['usuario'];
    foreach ($usuario->casinos as $casino) {
      $casinos[] = $casino->id_casino;
    }

    $reglas = [];
    if(isset($request->tipo_moneda) && $request->tipo_moneda !=0){
      $reglas[]=['tipo_moneda.id_tipo_moneda','=', $request->tipo_moneda];
    }    
    if(isset($request->casinos) && $request->casinos !=0){
      $reglas[]=['casino.id_casino','=', $request->casinos];
    }
    
    $resultados = ['data' => [],'total' => 0];
    $sort_by = $request->sort_by;
    
    switch ($request->seleccion) {
      case 'CONTADORES':{
        if(isset($request->fecha)){
          $fecha = explode("-",$request->fecha);
          $reglas[]=[DB::raw('YEAR(contador_horario.fecha)'),'=',$fecha[0]];
          $reglas[]=[DB::raw('MONTH(contador_horario.fecha)'),'=',$fecha[1]];
        }
        if($sort_by && $sort_by['columna'] == 'fecha')
          $sort_by['columna'] = 'contador_horario.fecha';
        
        $resultados = DB::table('contador_horario')
        ->select('contador_horario.id_contador_horario as id_contador_horario','contador_horario.fecha as fecha'
                ,'casino.nombre as casino' , 'casino.id_casino as id_casino','tipo_moneda.descripcion as tipo_moneda','contador_horario.cerrado as cerrado')
        ->join('casino','contador_horario.id_casino','=','casino.id_casino')
        ->join('tipo_moneda','contador_horario.id_tipo_moneda','=','tipo_moneda.id_tipo_moneda')
        ->where($reglas)->whereIn('casino.id_casino' , $casinos)
        ->when($sort_by,function($query) use ($sort_by){
          return $query->orderBy($sort_by['columna'],$sort_by['orden']);
        })
        ->paginate($request->page_size);
        
        foreach ($resultados as $index => &$c){
          if($c->id_casino == 1 || $c->id_casino == 2){
            $c->fecha_archivo = date('Y-m-d',strtotime("$c->fecha - 1 days"));
          }else if($c->id_casino == 3){//rosario
            $c->fecha_archivo = $c->fecha;
          }
        }
      }break;
      case 'PRODUCIDOS':{
        if(isset($request->fecha)){
          $fecha = explode("-",$request->fecha);
          $reglas[]=[DB::raw('YEAR(producido.fecha)'),'=',$fecha[0]];
          $reglas[]=[DB::raw('MONTH(producido.fecha)'),'=',$fecha[1]];
        }
        if($sort_by && $sort_by['columna'] == 'fecha')
          $sort_by['columna'] = 'producido.fecha';

        $resultados = DB::table('producido')
        ->select('producido.id_producido as id_producido','producido.fecha as fecha'
                ,'casino.nombre as casino','tipo_moneda.descripcion as tipo_moneda','producido.validado as validado')
        ->join('casino','producido.id_casino','=','casino.id_casino')
        ->join('tipo_moneda','producido.id_tipo_moneda','=','tipo_moneda.id_tipo_moneda')
        ->where($reglas)->whereIn('casino.id_casino' , $casinos)
        ->when($sort_by,function($query) use ($sort_by){
          return $query->orderBy($sort_by['columna'],$sort_by['orden']);
        })
        ->paginate($request->page_size);
      }break;
      case 'BENEFICIOS':{
        if(isset($request->fecha)){
          $fecha = explode("-",$request->fecha);
          $reglas[]=[DB::raw('YEAR(beneficio.fecha)'),'=',$fecha[0]];
          $reglas[]=[DB::raw('MONTH(beneficio.fecha)'),'=',$fecha[1]];
        }

        $sort_raw = $sort_by && $sort_by['columna'] == 'fecha';
        if($sort_raw){
          $ord = "asc";
          if($sort_by['orden'] == 'desc') $ord = "desc";//Evita SQL injection de poner el orden directamente
          $sort_raw = "YEAR(beneficio.fecha) $ord,MONTH(beneficio.fecha) $ord";
        }

        $resultados = DB::table('beneficio')
        ->selectRaw('MONTH(beneficio.fecha) as mes, YEAR(beneficio.fecha) as anio,
                     beneficio.id_casino, beneficio.id_tipo_moneda,
                     casino.nombre as casino, tipo_moneda.descripcion as tipo_moneda')
        ->join('casino','casino.id_casino','=','beneficio.id_casino')
        ->join('tipo_moneda','tipo_moneda.id_tipo_moneda','=','beneficio.id_tipo_moneda')
        ->where($reglas)->whereIn('casino.id_casino',$casinos)
        ->groupBy(DB::raw('MONTH(beneficio.fecha)'),DB::raw('YEAR(beneficio.fecha)'),'beneficio.id_casino','beneficio.id_tipo_moneda')
        ->when($sort_by,function($query) use ($sort_by,$sort_raw){
          if($sort_raw === false){
            return $query->orderBy($sort_by['columna'],$sort_by['orden']);
          }
          return $query->orderByRaw($sort_raw);
        })
        ->paginate($request->page_size);
      }break;
    }
    
    return $resultados;
  }



  public function estadoImportacionesDeCasino($id_casino,$fecha_busqueda = null,$orden = 'desc'){
    //modficar para que tome ultimos dias con datos, no solo los ultimos dias
    Validator::make(
      ['id_casino' => $id_casino],
      ['id_casino' => 'required|exists:casino,id_casino'], 
      [], self::$atributos)->validate();

    $fecha = is_null($fecha_busqueda)? date('Y-m-d') : $fecha_busqueda;
    $aux = new \DateTime($fecha);
    $aux->modify('last day of this month');
    $fecha = $aux->format('Y-m-d');
    $mes = date('m',strtotime($fecha));

    $arreglo = [];
    $monedas = TipoMoneda::all();
    while(date('m',strtotime($fecha)) == $mes){
      foreach($monedas as $m){//@HACK, usar la tabla tipo_moneda
        $reglas = [['fecha' , $fecha],['id_casino', $id_casino] ,['id_tipo_moneda' , $m->id_tipo_moneda]];
        $contador[$m->id_tipo_moneda]  = ContadorHorario::where($reglas)->count() >= 1;
        $producido[$m->id_tipo_moneda] = Producido::where($reglas)->count()       >= 1;
        $beneficio[$m->id_tipo_moneda] = Beneficio::where($reglas)->count()       >= 1;
      }
      $dia['contador']  = $contador;
      $dia['producido'] = $producido;
      $dia['beneficio'] = $beneficio;
      $dia['fecha'] = $fecha;
      $arreglo[] = $dia;
      $fecha = date('Y-m-d' , strtotime($fecha . ' - 1 days'));
    }
    if($orden == 'asc'){
      $arreglo = array_reverse($arreglo);
    }
    return ['arreglo' => $arreglo];
  }

  public function importarContador(Request $request){
    Validator::make($request->all(),[
        'id_casino' => 'required|integer|exists:casino,id_casino',
        'fecha_iso' => 'required|date',
        'archivo' => 'required|mimes:csv,txt',
        'id_tipo_moneda' => 'required|exists:tipo_moneda,id_tipo_moneda',
        'md5' => 'required|string|max:32'
    ], array(), self::$atributos)->after(function($validator){
      if($validator->errors()->any()) return;

      $data = $validator->getData();
      $fecha = $data['fecha_iso'];
      $id_casino = $data['id_casino'];
      $id_tipo_moneda = $data['id_tipo_moneda'];
      $id_usuario = session('id_usuario');
      //se debe permitir al que tiene el permiso correspondiente importar aun cuando el contador esta cerrado
      if(!AuthenticationController::getInstancia()->usuarioTienePermiso($id_usuario,'importar_contador_visado')){
        $reglas = Array();
        $reglas[]=['fecha','=',$fecha];
        $reglas[]=['id_casino','=',$id_casino];
        $reglas[]=['cerrado','=',1];
        $reglas[]=['id_tipo_moneda','=',$id_tipo_moneda];
        if(ContadorHorario::where($reglas)->count() > 0){
          $validator->errors()->add('contador_cerrado', 'El Contador para esa fecha ya está cerrado y no se puede reimportar.');
        }
      }

      if($validator->errors()->any()) return;
      if(RelevamientoController::getInstancia()->existeRelVisado($fecha, $id_casino)){
        if(!AuthenticationController::getInstancia()->usuarioTienePermiso($id_usuario,'importar_contador_visado')){
          $validator->errors()->add('existeRel','No tiene permisos para reimportar contadores visados');
        }
      }
    })->validate();

    return DB::transaction(function() use ($request){
      $ret = null;
      switch($request->id_casino){
        case 1:
        case 2:
          $ret = LectorCSVController::getInstancia()->importarContadorSantaFeMelincue($request->archivo,$request->fecha_iso,$request->id_tipo_moneda,$request->id_casino,$request->md5);
          break;
        case 3:
          $ret = LectorCSVController::getInstancia()->importarContadorRosario($request->archivo,$request->fecha_iso,$request->id_tipo_moneda,$request->md5);
          break;
        default:
          break;
      }
      $fecha = $ret['fecha'];

      //Actualizo los producidos de los relevamientos que ya estan en el sistema.
      $relevamientos = Relevamiento::where([['fecha', $fecha],['backup',0]])->get();
      foreach($relevamientos as $rel){
        if($rel->sector->casino->id_casino == $request->id_casino){
          foreach($rel->detalles as $det){
            $det->producido_importado = RelevamientoController::getInstancia()->calcularProducido(
              $fecha,
              $request->id_casino,
              $det->id_maquina
            );
            if($det->producido_calculado_relevado != null){
              $det->diferencia = $det->producido_calculado_relevado - $det->producido_importado;
            }
            $det->save();
          }
          $rel->save();
        }
      }
      return $ret;
    });
  }

  public function importarProducido(Request $request){
    Validator::make($request->all(),[
      'id_casino' => 'required|integer|exists:casino,id_casino',
      'fecha_iso' => 'nullable|date',
      'archivo' => 'required|mimes:csv,txt',
      'id_tipo_moneda' => 'nullable|exists:tipo_moneda,id_tipo_moneda',
      'md5' => 'required|string|max:32'
    ], [], self::$atributos)->validate();
    return DB::transaction(function() use ($request){
      switch($request->id_casino){
        case 1:
        case 2://No necesita break porque retorna
          return LectorCSVController::getInstancia()->importarProducidoSantaFeMelincue($request->archivo,$request->id_tipo_moneda,$request->id_casino,$request->md5);
        case 3:
          return LectorCSVController::getInstancia()->importarProducidoRosario($request->archivo,$request->fecha_iso,$request->id_tipo_moneda,$request->md5);
        default:
          return null;
      }
    });
  }

  public function importarBeneficio(Request $request){
    Validator::make($request->all(),[
      'id_casino' => 'required|integer|exists:casino,id_casino',
      'archivo' => 'required|mimes:csv,txt',
      'id_tipo_moneda' => 'nullable|exists:tipo_moneda,id_tipo_moneda',
      'md5' => 'required|string|max:32'
    ], [], self::$atributos)->validate();
    return DB::transaction(function() use ($request){
      return LectorCSVController::getInstancia()->importarBeneficio(
        $request->archivo,
        $request->id_tipo_moneda,
        $request->id_casino,
        $request->md5
      );
    });
  }
}
