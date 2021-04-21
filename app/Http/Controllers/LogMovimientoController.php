<?php

namespace App\Http\Controllers;

use Illuminate\Validation\Rule;
use Illuminate\Http\Request;
use Validator;
use Illuminate\Support\Facades\DB;
use Response;
use View;
use DateTime;
use Dompdf\Dompdf;

use PDF;
use App\Notifications\NuevoMovimiento;
use App\Notifications\RelevamientoCargado;
use App\Notifications\RelevamientoGenerado;
use App\Notifications\NuevaIntervencionMtm;
use App\Usuario;
use App\LogMovimiento;
use App\Nota;
use App\Expediente;
use App\RelevamientoMovimiento;
use App\EstadoMovimiento;
use App\TomaRelevamientoMovimiento;
use App\TipoMovimiento;
use App\TipoMoneda;
use App\FiscalizacionMov;
use App\Formula;
use App\TipoGabinete;
use App\UnidadMedida;
use App\Maquina;
use App\TipoProgresivo;
use App\Casino;
use App\Isla;
use App\Juego;
use App\TipoMaquina;
use App\EstadoMaquina;
use App\TipoCausaNoTomaProgresivo;
use App\Pozo;

/*
# Nueva forma de trabajo de MOVIMIENTOS (Marzo 2020 - Octavio)
¿Que es un movimiento?
Es un evento que ocurre sobre la maquina en la cual se relevan ciertos datos de la maquina, 
estos son (y se dividen en):
  - Manejados por Asignacion/Relevamiento: 
    - INGRESO INICIAL
    - EGRESO DEFINITIVO
  - Manejados por Intervenciones MTM:
    - CAMBIO LAYOUT
    - DENOMINACION
    - % DEVOLUCION
    - JUEGO
    - ACTUALIZACION FIRMWARE
    A su vez, las intervenciones tienen un SENTIDO que puede ser EGRESO TEMPORAL o REINGRESO

## Asignacion/Relevamiento
Estos son los movimientos iniciales y finales de la maquina, se manejan de forma distinta porque cambian
los datos de la maquina.
### INGRESO INICIAL
Para crear una maquina nueva, se le cargan todos los datos y al dar aceptar llama a guardarMaquina (en MTMController)
Este a su vez llama a guardarRelevamientoMovimientoIngreso en este modulo que lo asigna al movimiento.
### EGRESO DEFINITIVO
Para dar de baja una maquina, NO ES LO MISMO QUE ELIMINARLA!
Los nro_admin NO SE REUSAN por lo que cuando se la da de baja, se le cambia el estado a EGRESO DEFINITO pero la maquina
sigue en el sistema.
### Funcionamiento General
Se crea el movimiento, si es ingreso lo mas probable es que sea de tipo CARGA INDIVIDUAL (carga masiva es con un CSV).
Se le asignan las maquinas. 
Una vez cargadas el admin le genera un FISCALIZAMIENTO con una fecha a elección.
Este fiscalizamiento es visible por los fiscalizadores desde la pantalla de RELEVAMIENTO.
Lo imprimen, relevan los datos de la maquina, lo cargan al sistema.
Una vez cargado en asignación el movimiento se encontrara en estado FISCALIZADO en ASIGNACION,
a partir de esto se lo VALIDA.
Un relevamiento puede tener muchas fiscalizaciones, por lo que el menu aparece divido por fiscalizaciones y maquinas,
el admin lo verifica y lo valida al relevamiento, si es valido cambia el estado de la maquina.

## Intervenciones MTM
Tambien llamadas eventualidades MTM (anteriormente), ocurren de forma no planeada (generalmente).
Son cuando se tiene que cambiar de isla una maquina, el juego, etc y hay que dejar constancia del cambio en el sistema.
SON EXACTAMENTE LO MISMO, excepto que:
 - No cambian el estado de la maquina.
 - Es todo una misma "fiscalizacion", no se puede dividir en distintas fechas.
 - Son las mismas tablas que los movimientos anteriores, pero se los diferencia por un par de cosas (mas adelante explico).
 - Tiene un SENTIDO, entonces por ejemplo si ocurre un CAMBIO DE FIRMWARE:
  - Se crea una intervencion para el EGRESO TEMPORAL, con los datos relevados antes de apagar la maquina.
  - Una vez cambiado el firmware se crea una intervencion para el REINGRESO, con los datos relevados despues de prender la maquina.
  - Estos datos en principio deberian dar los mismos (esto es lo que verifica).
El fiscalizador crea la intervencion, la releva y la carga. Luego el ADMIN desde la misma pantalla lo valida.

## Manejo de tablas y tipos de datos
Estos son los datos principales que se manejan en este controlador:
 - log_movimiento (LogMovimiento)
 - fiscalizacion_movimiento (FiscalizacionMov)
 - relevamiento_movimiento (RelevamientoMovimiento)
 - toma_relev_mov (TomaRelevamientoMovimiento)
 - detalle_relevamiento_progresivo (DetalleRelevamientoProgresivo)
Otros menores que quedaron mas por legacy:
 - log_maquina (LogMaquina)
 - log_clicks_mov (LogClicksMov)

El log_movimiento es la estructura principal, es el movimiento en si (no importa si es por asignacion o intervencion).
Se diferencian entre esas dos categorias porque la intervencion no posee un expediente asignado.
Una asignacion en principio tampoco (generalmente se le asigna muucho mas tarde que de la creacion del mismo), pero
se le asigna uno concepto "expediente_auxiliar_para_movimientos" (hay uno por casino) para diferenciarlo inicialmente.
Una fiscalizacion_movimiento es creada cuando el admin envia a fiscalizar (las intervenciones MTM NO TIENEN!).
El relevamiento_movimiento es creado al principio con el movimiento (incluso antes de ser enviado a fiscalizar) 
y es UNO POR MAQUINA.
La toma_relev_mov es la que tiene los datos de relevamiento y se crea con el relevamiento. Estan en tablas separados
porque en un principio los relevamientos tenian 2 tomas, esto se lo cambio a que haya uno solo por relevamiento por 
lo que es redundante; podria estar en la misma tabla.
El detalle_relevamiento_progresivo es el que tiene los datos del relevamiento (pero la parte de los progresivos),
puede haber varios por toma ya que una maquina puede estar enlazado en varios progresivos. Es reusado de la parte
de RELEVAMIENTOS PROGRESIVOS y se diferencian entre si en que un detalle_relevamiento_progresivo de un relevamiento_progresivo
tiene el id_toma_relev_mov en NULL (y viceversa).

log_maquina es creado cuando se valida el relevamiento para luego ser visto en Informes MTM -> MTM, en principio es redundante
porque podria generarse dinamicamente.
log_clicks_mov es de antes cuando se necesitaba un expediente para las intervenciones y se logeaba los cambios de isla, no es
importante.

Entonces la estructura de un movimiento de ingreso inicial y egreso definitivo es
(rel4 no fue enviada a fiscalizar todavia)
   +------------------------------------+
   |                                    |
┌──────┐     ┌──────┐     ┌──────┐      |
│ log  │---> │ fis1 │---> │ rel1 │ <----+
└──────┘     └──────┘     └──────┘      |
      |      ┌──────┐     ┌──────┐      |
      +----> │ fis2 │---> │ rel2 │ <----+
             └──────┘     └──────┘      |
                   |      ┌──────┐      |
                   +----> │ rel3 │ <----+
                          └──────┘      |
                          ┌──────┐      |
                          │ rel4 │ <----+
                          └──────┘
Para una intervencionMTM
┌──────┐     ┌──────┐ 
│ log  │---> │ rel1 │
└──────┘     └──────┘
      |      ┌──────┐
      +----> │ rel2 │
      |      └──────┘
      |      ┌──────┐
      +----> │ rel3 │
             └──────┘

A su vez un relevamiento
┌──────┐     ┌──────┐                  ┌───────────┐
│ rel  │---> │toma1 │ ------------+--> │ det_prog1 │
└──────┘     └──────┘             |    └───────────┘
       |     ┌─────────────────┐  |    ┌───────────┐
       +---> │toma2(deprecado) │  +--> │ det_prog2 │
             └─────────────────┘       └───────────┘
*/

class LogMovimientoController extends Controller
{

  private static $atributos = [
    'id_log_movimiento'=> 'Log Movimiento',
    'id_controlador' => 'Controlador',
    'id_fiscalizador' => 'Fiscalizador',
    'id_cargador' => 'Cargador',
    'cantidad_maquinas' => 'Cantidad de Máquinas',
    'id_estado_movimiento' => 'Estado Movimiento',
    'id_estado_relevamiento' => 'Estado Relevamiento',
    'id_nota' => 'Nota',
    'id_expediente' => 'Expediente',
    'fecha_inicio' => 'Fecha Inicio',
    'bool_nota' =>'EsNota',
    'tipo_movimiento' => 'Tipo de Movimiento',
    'maquinas' => 'Maquinas a relevar',
    'maquinas.*.id_maquina' => 'Maquina',
    'juego'=>'juego'
  ];
  private static $instance;

  public static function getInstancia(){
    if (!isset(self::$instance)) {
      self::$instance = new LogMovimientoController();
    }
    return self::$instance;
  }

  public function __construct(){}

  public function obtenerDatos($id){
    $log = LogMovimiento::find($id);
    $exp = $log->expediente;
    return ['movimiento' => $log, 'expediente' => $exp];
  }

  public function obtenerMovimiento($id){
    $movimiento = LogMovimiento::find($id);
    return ["movimiento" => $movimiento, "tipo" => $movimiento->tipo_movimiento, "casino" => $movimiento->casino];
  }

  public function movimientos(){
    $usuario = UsuarioController::getInstancia()->buscarUsuario(session('id_usuario'));
    $unidad_medida = UnidadMedida::all();//credito o pesos
    $casinos=array();
    foreach($usuario['usuario']->casinos as $casino){
      $casinos[]=$casino->id_casino;
    }
    $tipos = TipoMaquina::all();
    $monedas = TipoMoneda::all();
    $gabinetes = TipoGabinete::all();
    $estados = EstadoMaquina::all();
    $logs = LogMovimiento::whereIn('id_casino',$casinos)->orderBy('fecha','DESC')->get();
    $casinos=$usuario['usuario']->casinos;
    $tiposMovimientos = TipoMovimiento::all();
    UsuarioController::getInstancia()->agregarSeccionReciente('Asignación Movimientos' ,'movimientos');
    return view('seccionMovimientos',[ 
      'logMovimientos'=>$logs , 'tiposMovimientos' => $tiposMovimientos,'monedas'=>$monedas , 
      'unidades_medida' => $unidad_medida,   'casinos' => $casinos, 'tipos' => $tipos , 
      'gabinetes' => $gabinetes , 'estados' => $estados, 'causasNoTomaProgresivo' => TipoCausaNoTomaProgresivo::all()]);
  }


  public function buscarLogsMovimientos(Request $request){
    //busca logs de movimientos por expediente, por si es nota o no, por fecha, y tipo de movimiento, se tiene en cuenta que el
    //casino es del usuario que está en la session
    $where_exp_org = function($q) use ($request){
      return;
    };
    $where_exp_interno = $where_exp_org;
    $where_exp_control = $where_exp_org;
    if(!empty($request->nro_exp_org)){
      $where_exp_org = function($q) use ($request){
        $q->where('expediente.nro_exp_org','like', '%'.$request->nro_exp_org.'%')
        ->orWhere('log_movimiento.nro_exp_org','like', '%'.$request->nro_exp_org.'%');
        return;
      };
    }
    if(!empty($request->nro_exp_interno)){
      $where_exp_interno = function($q) use ($request){
        $q->where('expediente.nro_exp_interno','like', '%'.$request->nro_exp_interno.'%')
        ->orWhere('log_movimiento.nro_exp_interno','like', '%'.$request->nro_exp_interno.'%');
        return;
      };
    }
    if(!empty($request->nro_exp_control)){
      $where_exp_control = function($q) use ($request){
        $q->where('expediente.nro_exp_control','=', $request->nro_exp_control)
        ->orWhere('log_movimiento.nro_exp_control','=', $request->nro_exp_control);
        return;
      };
    }

    $usuario = UsuarioController::getInstancia()->buscarUsuario(session('id_usuario'))['usuario'];
    foreach ($usuario->casinos as $casino) {
      $casinos[] = $casino->id_casino;
    }

    if(!empty($request->casino)){
      $casinos = [$request->casino];
    }

    $reglas = array();
    if(isset($request->nro_admin) && $request->nro_admin != ""){
      $reglas[]=['relevamiento_movimiento.nro_admin','like', $request->nro_admin.'%'];
    }

    if(!empty($request->tipo_movimiento)){
      $reglas[]=['log_movimiento.id_tipo_movimiento','=', $request['tipo_movimiento']];
    }

    $sort_by = ['columna' => 'log_movimiento.id_log_movimiento', 'orden' => 'DESC'];
    if(!empty($request->sort_by)){
      $sort_by = $request->sort_by;
    }

    $resultados = DB::table('log_movimiento')
    ->selectRaw("log_movimiento.*,casino.*,tipo_movimiento.*,estado_movimiento.descripcion as estado,
    IF(STRCMP(expediente.concepto,'expediente_auxiliar_para_movimientos') = 0,
       CONCAT(log_movimiento.nro_exp_org,'-',log_movimiento.nro_exp_interno,'-',log_movimiento.nro_exp_control), 
       CONCAT(    expediente.nro_exp_org,'-',    expediente.nro_exp_interno,'-',    expediente.nro_exp_control)
    ) as nro_exp")
    ->join('expediente', 'log_movimiento.id_expediente', '=', 'expediente.id_expediente')
    ->join('casino', 'log_movimiento.id_casino', '=', 'casino.id_casino')
    ->join('tipo_movimiento','log_movimiento.id_tipo_movimiento','=', 'tipo_movimiento.id_tipo_movimiento')
    ->join('estado_movimiento','log_movimiento.id_estado_movimiento','=','estado_movimiento.id_estado_movimiento')
    ->leftJoin('relevamiento_movimiento','relevamiento_movimiento.id_log_movimiento','=','log_movimiento.id_log_movimiento')
    ->where($reglas)->where($where_exp_control)->where($where_exp_interno)->where($where_exp_org)
    ->whereIn('log_movimiento.id_casino' , $casinos)
    ->whereNotIn('tipo_movimiento.id_tipo_movimiento',[9])
    ->whereNotNull('log_movimiento.id_expediente');//id_expediente no puede ser nulo (sino seria intervencion MTM)

    if(!empty($request->fecha)){
      $fecha = explode("-", $request->fecha);
      $resultados = $resultados->whereYear('log_movimiento.fecha' , '=' ,$fecha[0])
      ->whereMonth('log_movimiento.fecha','=', $fecha[1]);
    }
    if(!empty($request->id_log_movimiento)){
      $resultados = $resultados->whereRaw("CAST(log_movimiento.id_log_movimiento as CHAR) regexp ?",'^'.$request->id_log_movimiento);
    }

    $resultados = $resultados->distinct('log_movimiento.id_log_movimiento','expediente.id_expediente','casino.id_casino','tipo_movimiento.id_tipo_movimiento')
    ->when($sort_by,function($query) use ($sort_by){
      return $query->orderBy($sort_by['columna'],$sort_by['orden']);
    })
    ->paginate($request->page_size,['log_movimiento.id_log_movimiento','expediente.id_expediente','casino.id_casino','tipo_movimiento.id_tipo_movimiento']);

    $cas = DB::table('casino')
    ->select('casino.id_casino')
    ->whereIn('casino.id_casino' , $casinos)
    ->get();

    return ['logMovimientos' => $resultados, 'casinos' => $cas ];
  }

  public function noEsControlador($id_usuario , $logMov){
    return $logMov->controladores()->where('controlador_movimiento.id_controlador_movimiento',$id_usuario)->count() == 0;
  }

  //solo cuando es MOVIMIENTO por ASIGNACION osea INGRESO INICIAL o EGRESO DEFINITO (12/03/20)
  public function enviarAFiscalizar(Request $request){
    $user_request = UsuarioController::getInstancia()->quienSoy()['usuario'];
    $logMov = null;
    $maquinas = [];
    $validator = Validator::make($request->all(), [
      'id_log_movimiento' => 'required|exists:log_movimiento,id_log_movimiento',
      'maquinas' => 'nullable|array',
      'maquinas.*' => 'required|exists:maquina,id_maquina'
    ], array(), self::$atributos)->after(function ($validator) use ($user_request,&$logMov,&$maquinas){
      if(!$validator->errors()->any()){
        $data = $validator->getData();
        $logMov = LogMovimiento::find($data['id_log_movimiento']);
        if(!$user_request->usuarioTieneCasino($logMov->id_casino)){
          $validator->errors()->add('id_log_movimiento', 'El usuario no puede acceder a ese movimiento.');  
        }
        if(!array_key_exists('maquinas',$data)){
          $validator->errors()->add('maquinas', 'No hay máquinas seleccionadas.'); 
        }
        else foreach($data['maquinas'] as $m){
          $maq = Maquina::find($m);
          if(!$user_request->usuarioTieneCasino($maq->id_casino)){
            $validator->errors()->add('maquinas', 'El usuario no puede acceder a la maquina'.$maq->nro_admin.'.');  
          }
          $maquinas[] = $maq;
        }
      }
    })->validate();

    DB::transaction(function() use ($maquinas,$logMov,$request,$user_request){
      $fiscalizacion = FiscalizacionMovController::getInstancia()->crearFiscalizacion($logMov->id_log_movimiento, false,$request['fecha']);
      foreach($maquinas as $m){
        //crear log maquina
        LogMaquinaController::getInstancia()->registrarMovimiento($m->id_maquina, "MTM enviada a fiscalizar.",1);
        //busco los relevamientos que se crearon para asociarlos a una fiscalizacion
        $relevamiento = RelevamientoMovimiento::where([
          ['id_maquina'       ,'=',$m->id_maquina],
          ['id_log_movimiento','=',$logMov->id_log_movimiento]
        ])->get()->first();
        $relevamiento->fiscalizacion()->associate($fiscalizacion->id_fiscalizacion_movimiento);
        $relevamiento->save();
        // Puede ser que haya agregado progresivos entre la creacion de la maquina y enviar a fiscalizar
        // Por lo que rehago las tomas, lamentablemente se hace asi porque habria que reescribir mucho
        foreach($relevamiento->toma_relevamiento_movimiento as $toma){
          $toma->detalles_relevamiento_progresivo()->delete();
          $toma->delete();
        }
        TomaRelevamientoMovimientoController::getInstancia()->crearTomaRelevamiento($m->id_maquina,$relevamiento->id_relev_mov,[],
        null,null,null,
        null,null,null,
        null,null,null,
        null,null,0);
      }
      if($logMov->cant_maquinas == 0){
        $logMov->estado_movimiento()->associate(2);//fiscalizando
        $logMov->save();
      }
      $usuarios = UsuarioController::getInstancia()->obtenerFiscalizadores($logMov->casino->id_casino,$user_request->id_usuario);
      foreach ($usuarios as $u){
        $user = Usuario::find($u->id_usuario);
        if($user != null) $user->notify(new RelevamientoGenerado($fiscalizacion));
      }
  
      $date = date('Y-m-d h:i:s', time());
      $titulo = "Relevamiento Movimientos";
      $descripcion = "El movimiento: ".$logMov->tipo_movimiento->descripcion." con fecha ".$logMov->fecha.", está listo para fiscalizar.";
      CalendarioController::getInstancia()->crearEventoMovimiento($date,$date,$titulo,$descripcion,$logMov->id_casino,$fiscalizacion->id_fiscalizacion_movimiento);
    });
    return 1;
  }

  //crear los relevamientos movimientos por cada máquina que el controlador creó  para fiscalizar
  public function guardarRelevamientoMovimientoIngreso($id_log_mov,$id_maquina){ //Usado en MTM controller llamado, cuando das GUARDAR en un ingreso
    $logMov = LogMovimiento::find($id_log_mov);
    $logMov->estado_relevamiento()->associate(1);//generado
    $id_usuario = session('id_usuario');
    if($this->noEsControlador($id_usuario,  $logMov)){
      $logMov->controladores()->attach($id_usuario);
    }
    $logMov->cant_maquinas = $logMov->cant_maquinas - 1; // cree una maquina, resto de las que quedan
    if($logMov->cant_maquinas == 0){
      $logMov->estado_movimiento()->associate(7); //MTMs cargadas
    }
    $logMov->save();

    $mtm = Maquina::find($id_maquina);
    $this->guardarIslasMovimiento($logMov,$mtm);

    $r = RelevamientoMovimientoController::getInstancia()->crearRelevamientoMovimiento($id_log_mov, $mtm);

    return  $logMov->cant_maquinas;
  }

  public function cargarMaquinasMovimiento(Request $request){// A un movimiento le cargo maquinas (les crea relevamientos), usado solo en EGRESO DEFINITIVO (12/03/20)
    $user_request = UsuarioController::getInstancia()->quienSoy()['usuario'];
    $logMov = null;
    $maquinas = [];
    $validator = Validator::make($request->all(),
    [
      'id_log_movimiento' => 'required|exists:log_movimiento,id_log_movimiento',
      'maquinas' => 'nullable|array',
      'maquinas.*' => 'required|exists:maquina,id_maquina'
    ], array(), self::$atributos)->after(function($validator) use ($user_request,&$logMov,&$maquinas){
      if(!$validator->errors()->any()){
        $data = $validator->getData();
        $logMov = LogMovimiento::find($data['id_log_movimiento']);
        if(!$user_request->usuarioTieneCasino($logMov->id_casino)){
          $validator->errors()->add('id_log_movimiento', 'El usuario no puede acceder a ese movimiento.');  
        }
        if(!array_key_exists('maquinas',$data)){
          $validator->errors()->add('maquinas', 'No hay máquinas seleccionadas.'); 
        }
        else foreach($data['maquinas'] as $m){
          $maq = Maquina::find($m['id_maquina']);
          if(!$user_request->usuarioTieneCasino($maq->id_casino)){
            $validator->errors()->add('maquinas', 'El usuario no puede acceder a la maquina'.$maq->nro_admin.'.');  
          }
          $maquinas[] = $maq;
        }
        if($logMov->relevamientos_movimientos()->count() > 0){
          $validator->errors()->add('id_log_movimiento','El movimiento ya tiene maquinas cargadas.');
        }
      }
    })->validate();

    DB::transaction(function() use ($logMov,$maquinas,$user_request){
      $logMov->estado_relevamiento()->associate(1);//generado
      $logMov->estado_movimiento()->associate(7);//MTM cargadas
      if($this->noEsControlador($user_request->id_usuario,  $logMov)){
        $logMov->controladores()->attach($user_request->id_usuario);
      }
      foreach($maquinas as $mtm){
        $this->guardarIslasMovimiento($logMov, $mtm);
        RelevamientoMovimientoController::getInstancia()->crearRelevamientoMovimiento($logMov->id_log_movimiento, $mtm);
      }
      $logMov->save();
    });
    
    return 1;
  }

  //guarda que islas fueron afectadas en el movimiento para que se muestren en
  //los listados
  private function guardarIslasMovimiento($log_mov,$mtm){
    $islas = $log_mov->islas;
    $isla_mtm =$mtm->isla->nro_isla;
    $countiguales = 0;
    if($islas != null){
      $islasArray = explode('-',$islas);
      foreach ($islasArray as $isla) {
        if($isla == $isla_mtm){
          $countiguales++;
        }
      }
      if($countiguales == 0){
        $islas = $islas."-".$mtm->isla->nro_isla."";
      }
    }else{
      $islas = "".$mtm->isla->nro_isla."";
    }
    $log_mov->islas=$islas;
    $log_mov->save();
  }

  public function relevamientosMovimientos($id_casino = 0){
    $casinos= array();
    $usuario = UsuarioController::getInstancia()->buscarUsuario(session('id_usuario'))['usuario'];
    foreach($usuario->casinos as $casino){
          $casinos [] = $casino->id_casino;
    }
    $tiposMovimientos = TipoMovimiento::all();

    UsuarioController::getInstancia()->agregarSeccionReciente('Relevamientos Movimientos','relevamientos_movimientos');

    return view('seccionRelevamientosMovimientos',
    ['casinos' => $usuario->casinos,
    'tipos_movimientos' => $tiposMovimientos,
    'causasNoTomaProgresivo' => TipoCausaNoTomaProgresivo::all()]);
  }


  //Formato nuevo de lo mismo de arriba, mas parecido a relevamientosEvMTM
  public function obtenerRelevamientosFiscalizacion($id_fiscalizacion_movimiento){
    $fiscalizacion = FiscalizacionMov::find($id_fiscalizacion_movimiento);
    $log = $fiscalizacion->log_movimiento;
    $relevamientos = $fiscalizacion->relevamientos_movimientos;
    $relevamientos_arr = array();
    foreach ($relevamientos as $rel) {
      //Si se borra el ingreso antes que el egreso (casi nunca pasaria por que se le asignaria un expediente)
      //Hay que obtener los softdeletes por las dudas...
      $maquina = $rel->maquina()->withTrashed()->first();
      $nro_admin = is_null($rel->maquina)? $maquina->nro_admin . ' (ELIM.)' : $maquina->nro_admin;
      $relevamientos_arr[] =  [
                            'id_relevamiento' => $rel->id_relev_mov,
                            'estado'          => $rel->estado_relevamiento,
                            'nro_admin'       => $nro_admin,
                            'id_maquina'      => $maquina->id_maquina,
                            'tomas'           => $rel->toma_relevamiento_movimiento()->count()
                          ];
    }
    $id_usuario = session('id_usuario');
    $user = Usuario::find($id_usuario);
    return ['relevamientos' => $relevamientos_arr,'cargador' => $user,'fiscalizador' => $fiscalizacion->fiscalizador,
            'tipo_movimiento' => $log->tipo_movimiento->descripcion, 'sentido' => $log->sentido, 
            'casino' => $log->casino, 'fiscalizacion' => $fiscalizacion,
            'nro_exp_org' => $log->nro_exp_org,'nro_exp_interno' => $log->nro_exp_interno,'nro_exp_control' => $log->nro_exp_control];
  }

  public function imprimirFiscalizacion($id_fiscalizacion_movimiento){
    $fiscalizacionMov = FiscalizacionMov::find($id_fiscalizacion_movimiento);
    $logMov = $fiscalizacionMov->log_movimiento;
    $casino = $logMov->casino;
    $tipoMovimiento = $logMov->tipo_movimiento->descripcion;
    $relevamientos = array();
    $relController = RelevamientoMovimientoController::getInstancia();
    foreach($fiscalizacionMov->relevamientos_movimientos as $idx => $relev){
      $relevamientos[] = $relController->generarPlanillaMaquina(
        $relev,
        $tipoMovimiento,
        $logMov->sentido,
        $casino
      );
    }

    $tipo_planilla = "movimientos";
    $view = View::make('planillaMovimientos', compact('relevamientos','tipo_planilla'));
    $dompdf = new Dompdf();
    $dompdf->set_paper('A4', 'portrait');
    $dompdf->loadHtml($view->render());
    $dompdf->render();
    $font = $dompdf->getFontMetrics()->get_font("helvetica", "regular");
    $dompdf->getCanvas()->page_text(20, 815, $casino->codigo."/".$fiscalizacionMov->fecha_envio_fiscalizar, $font, 10, array(0,0,0));
    $dompdf->getCanvas()->page_text(515, 815, "Página {PAGE_NUM} de {PAGE_COUNT}", $font, 10, array(0,0,0));

    return $dompdf->stream('planilla.pdf', Array('Attachment'=>0));
  }

  public function imprimirEventualidadMTM($id_log_mov){
    $logMov = LogMovimiento::find($id_log_mov);
    $casino = $logMov->casino;
    $tipoMovimiento = $logMov->tipo_movimiento->descripcion;
    $relevamientos = array();
    $relController = RelevamientoMovimientoController::getInstancia();
    foreach ($logMov->relevamientos_movimientos as $idx => $relev) {
      $relevamientos[] = $relController->generarPlanillaMaquina(
        $relev,
        $tipoMovimiento,
        $logMov->sentido,
        $casino
      );
    }

    $tipo_planilla = "intervenciones";
    $view = View::make('planillaMovimientos', compact('relevamientos','tipo_planilla'));
    $dompdf = new Dompdf();
    $dompdf->set_paper('A4', 'portrait');
    $dompdf->loadHtml($view->render());
    $dompdf->render();
    $font = $dompdf->getFontMetrics()->get_font("helvetica", "regular");
    $dompdf->getCanvas()->page_text(20, 815, $casino->codigo."/".$logMov->fecha, $font, 10, array(0,0,0));
    $dompdf->getCanvas()->page_text(515, 815, "Página {PAGE_NUM} de {PAGE_COUNT}", $font, 10, array(0,0,0));

    return $dompdf->stream('planilla.pdf', Array('Attachment'=>0));
  }

  public function imprimirMovimiento($id_log_mov){
    $logMov = LogMovimiento::find($id_log_mov);
    $tipoMovimiento = $logMov->tipo_movimiento->descripcion;
    $casino = $logMov->casino;
    $relevamientos = array();
    $relController = RelevamientoMovimientoController::getInstancia();
    foreach ($logMov->fiscalizaciones as $fiscalizacion) {
      foreach($fiscalizacion->relevamientos_movimientos as $idx => $relev){
        $relevamientos[] = $relController->generarPlanillaMaquina(
          $relev,
          $tipoMovimiento,
          $logMov->sentido,
          $casino
        );
      }
    }
    foreach($logMov->relevamientos_movimientos()->whereNull('relevamiento_movimiento.id_fiscalizacion_movimiento')->get() as $idx => $relev){
      $relevamientos[] = $relController->generarPlanillaMaquina(
        $relev,
        $tipoMovimiento . ' [SIN FISC.]',
        $logMov->sentido,
        $casino
      );
    }

    $tipo_planilla = "movimientos";
    $view = View::make('planillaMovimientos', compact('relevamientos','tipo_planilla'));
    $dompdf = new Dompdf();
    $dompdf->set_paper('A4', 'portrait');
    $dompdf->loadHtml($view->render());
    $dompdf->render();
    $font = $dompdf->getFontMetrics()->get_font("helvetica", "regular");
    $dompdf->getCanvas()->page_text(20, 815, $casino->codigo."/".$logMov->fecha, $font, 10, array(0,0,0));
    $dompdf->getCanvas()->page_text(515, 815, "Página {PAGE_NUM} de {PAGE_COUNT}", $font, 10, array(0,0,0));

    return $dompdf->stream('planilla.pdf', Array('Attachment'=>0));
  }

  // 2/03/2020 - Octavio
  // Si alguna vez ocurre que hay progresivos con mas de 6 niveles, no van a ser mas validos muchos de los algoritmos
  // (trate de cambiarlo pero puede ser que se me haya quedado colgado algo en frontend)
  // Esto va a suceder porque si bien se tenia en cuenta que sea expandible en un principio
  // hay dos limitaciones:
  // - Detalle progresivo solo tiene 6 valores (trivial de solucionar, modificar la estructura de la tabla y cambiar los limites)
  // - Los ADM cargaron todos los niveles al reves (al nivel mas alto le asignaron el 1), el resultado es que no 
  //   son homogeneos los progresivos (si un progresivo tiene 4 niveles, su nivel mas alto tiene el valor nro_nivel 1
  //   y si otro tiene 6 niveles, su nivel mas alto tiene el valor nro_nivel 1 tambien).
  // Este ultimo punto no fue considerado mucho a la hora de programar la parte de relevamiento de progresivos
  // porque paso en produccion (aunque los ADM fueron debidamente informados con un manual). 
  // La idea es hacerlo a partir de ahora para que en el caso de que ocurra no haya que cambiar muchas cosas
  // Ej tabla detalle_relevamiento_progresivo
  // nivel1 | nivel2 | nivel3 | nivel4 | nivel5 | nivel6
  // 6      | 5      | 4      | 3      | 2      | 1
  // 4      | 3      | 2      | 1      | null   | null
  // 7      | 6      | 5      | 4      | 3      | 2     <- te quedas corto, agregar una columna nivel7
  // AHORA, si bien CONCEPTUALMENTE los niveles 1 en realidad son los maximos, en la bd estan cargados con nro_nivel = 1
  // por lo que se los puede asignar directamente, pero hay que tener en cuenta lo de la tercer fila 
  // y si en algun momento se hacen informes/pruebas.
  public function cargarTomaRelevamiento(Request $request){
    $validator =Validator::make($request->all(), [
        'id_relev_mov' => 'required|integer|exists:relevamiento_movimiento,id_relev_mov',
        'toma' => 'required|integer|min:0',
        'id_cargador' => 'nullable|integer|exists:usuario,id_usuario',
        'id_fiscalizador' => 'required|integer|exists:usuario,id_usuario',
        'contadores' => 'required',
        'contadores.*.nombre' =>'nullable',
        'contadores.*.valor' => ['required_with:contadores.*.nombre','regex:/^\d\d?\d?\d?\d?\d?\d?\d?\d?\d?\d?\d?([,|.]\d\d?)?$/'],
        'juego' => 'required|integer|exists:juego,id_juego',
        'apuesta_max' => 'required|numeric|max:900000',
        'cant_lineas' => 'required|numeric|max:100000',
        'porcentaje_devolucion' => ['required','regex:/^\d\d?([,|.]\d\d?\d?)?$/'],
        'denominacion' => ['required','regex:/^\d\d?\d?\d?\d?\d?\d?\d?([,|.]\d\d?)?$/'],
        'cant_creditos' => 'required|numeric|max:100',
        'fecha_sala' => 'required|date',
        'observaciones' => 'nullable|max:280',
        'mac' => 'nullable|max:100',
        'sector_relevado' => 'required',
        'isla_relevada' => 'required',
        'progresivos' => 'sometimes|array',
        'progresivos.*.id_pozo' => 'required|integer|exists:pozo,id_pozo',
        'progresivos.*.id_tipo_causa_no_toma_progresivo' => 'nullable|integer|exists:tipo_causa_no_toma_progresivo,id_tipo_causa_no_toma_progresivo',
        'progresivos.*.niveles' => 'sometimes|array',
        'progresivos.*.niveles.*.id_nivel_progresivo' => 'nullable|integer|exists:nivel_progresivo,id_nivel_progresivo',
        'progresivos.*.niveles.*.val' => 'nullable|numeric|min:0'
    ], array(), self::$atributos)->after(function($validator){
      if(!$validator->errors()->any()){
        if($validator->getData()['juego']==0 ){
            $validator->errors()->add('juego', 'No se ha seleccionado el juego.');
        }
        $data = $validator->getData();
        $relevamiento = RelevamientoMovimiento::find($data['id_relev_mov']);
        $fiscalizacion = $relevamiento->fiscalizacion;
        $fecha_limite_inferior = null;
        $fecha_sala = strtotime($data['fecha_sala']);
        if(!is_null($fiscalizacion)) $fecha_limite_inferior = $fiscalizacion->fecha_envio_fiscalizar;
        else $fecha_limite_inferior = $relevamiento->log_movimiento->fecha;
        $fecha_limite_inferior = strtotime($fecha_limite_inferior);
        if($fecha_sala < $fecha_limite_inferior) $validator->errors()->add('fecha_sala','validation.after');
        if($fecha_sala > time()) $validator->errors()->add('fecha_sala','validation.before');

        $progresivos = [];
        if(array_key_exists('progresivos',$data)) $progresivos = $data['progresivos'];
        foreach($progresivos as $idx_p => $p){
          $pozo = Pozo::find($p['id_pozo']);
          $causaNoToma = $p['id_tipo_causa_no_toma_progresivo'];
          if(is_null($causaNoToma)){
            $valores = [];
            foreach($pozo->niveles as $idx => $n){
              $valores[$n->id_nivel_progresivo] = -$n->nro_nivel;
            }
            foreach($p['niveles'] as $n){
              if(!is_null($n['val'])) $valores[$n['id_nivel_progresivo']] = $n['val'];
            }
            $strprog = 'progresivos.'.$idx_p;
            foreach($valores as $id => $val){
              if($val<0){
                $validator->errors()->add($strprog.'.niveles.'.(-$val-1).'.val','validation.required');
              }
            }
          }
        }
      }
    })->validate();
    $fisFinalizada = false;
    $movFinalizado = false;
    DB::beginTransaction();
    try{ // @TODO: Agregar una forma de guardar temporalmente, estado mov 8, estado rel 2
      $nro_toma = $request->toma <= 0? 1 : $request->toma;
      RelevamientoMovimientoController::getInstancia()->cargarTomaRelevamientoProgs(
        $request->id_relev_mov,
        $nro_toma,
        $request->id_cargador,
        $request->id_fiscalizador,
        $request['fecha_sala'],
        $request['mac'],
        $request['sector_relevado'],
        $request['isla_relevada'],
        $request['contadores'],
        $request['juego'],
        $request['apuesta_max'],
        $request['cant_lineas'] ,
        $request['porcentaje_devolucion'],
        $request['denominacion'],
        $request['cant_creditos'],
        $request['progresivos'],
        $request['observaciones']
      );

      $relevamiento = RelevamientoMovimiento::find($request->id_relev_mov);
      $fiscalizacion = $relevamiento->fiscalizacion;
      $logMov = $relevamiento->log_movimiento;
      $es_intervencion = is_null($fiscalizacion);
      if(!$es_intervencion){
        if($fiscalizacion->relevamientosCompletados(3)){
          $fiscalizacion->estado_relevamiento()->associate(3); // Finalizado
          $fisFinalizada = true;
        }
        else{
          $fiscalizacion->estado_relevamiento()->associate(2); // Cargando
        }
        $fiscalizacion->save();
      }

      if($logMov->relevamientosCompletados($es_intervencion,3)){
        $logMov->estado_relevamiento()->associate(3); // Finalizado
        $logMov->estado_movimiento()->associate(3);  // Notificado
        $movFinalizado = true;
      }
      else{
        $logMov->estado_relevamiento()->associate(2); // Cargando
        $logMov->estado_movimiento()->associate(2); // Fiscalizando 
      }
      $logMov->save();

      if($fisFinalizada || $movFinalizado){
        $id_usuario = session('id_usuario');
        $usuarios = UsuarioController::getInstancia()->obtenerControladores($logMov->casino->id_casino, $id_usuario);
        $notificacion = $es_intervencion? 'NuevaIntervencionMTM' : 'RelevamientoCargado'; //Estos son TIPOS de PHP
        foreach ($usuarios as $user){
          $u = Usuario::find($user->id_usuario);
          if($u != null) $u->notify(new $notificacion($fiscalizacion));
        }
        if(!$es_intervencion) CalendarioController::getInstancia()->marcarRealizado($fiscalizacion->evento);
      }
      DB::commit();
    }
    catch(Exception $e){
      DB::rollBack();
      throw $e;
      return ['codigo' => 0];
    }
    return ['fisFinalizada' => $fisFinalizada, 'movFinalizado' => $movFinalizado];
  }

  public function obtenerFiscalizacionesMovimiento($id_log_movimiento){
      $usuario = UsuarioController::getInstancia()->buscarUsuario(session('id_usuario'))['usuario'];
      foreach($usuario->casinos as $casino){
            $casinos [] = $casino->id_casino;
      }
      $fiscalizaciones = DB::table('log_movimiento')
                          ->select('fiscalizacion_movimiento.*', 'fiscalizacion_movimiento.id_estado_relevamiento as id_estado_fiscalizacion')
                          ->join('fiscalizacion_movimiento','fiscalizacion_movimiento.id_log_movimiento','=','log_movimiento.id_log_movimiento')
                          ->where('log_movimiento.id_log_movimiento','=',$id_log_movimiento)
                          ->whereIn('log_movimiento.id_casino',$casinos)
                          ->get();
      $log = LogMovimiento::find($id_log_movimiento);
      return ['fiscalizaciones' => $fiscalizaciones,'tipo_mov' => $log->tipo_movimiento->descripcion,'sentido' => $log->sentido,
              'nro_exp_org' => $log->nro_exp_org, 'nro_exp_interno' => $log->nro_exp_interno, 'nro_exp_control' => $log->nro_exp_control
      ];
  }

  //Busca todas las máquinas que concuerdan con el movimiento hecho
  public function obtenerMaquinasMovimiento($id_log_movimiento){
    $maquinas =MTMController::getInstancia()->buscarMaquinasPorLogMovimiento($id_log_movimiento);
    $unidades = DB::table('unidad_medida')->select('unidad_medida.*')->get();
    $maquinasYJuegos = array();
    foreach ($maquinas as $maq) {
        $mtmm=  Maquina::find($maq->id_maquina);
      	$juego_select = $mtmm->juego_activo();
      	$juegos = $mtmm->juegos;
        $maquinasYJuegos[]= ['maquina'=>$maq,'juegos'=> $juegos, 'juego_seleccionado' => $juego_select];
    }
    return ['maquinas' => $maquinasYJuegos,'unidades' => $unidades];
  }

  public function guardarTipoCargaYCantMaq(Request $req){
    $logMov = null;
    $usuario = UsuarioController::getInstancia()->quienSoy()['usuario'];
    
    Validator::make($req->all(), [
      'id_log_movimiento' => 'required|exists:log_movimiento,id_log_movimiento',
      'tipoCarga' => 'required|integer|in:1,2',
      'cantMaq' => 'nullable|required_if:tipoCarga,1|integer|min:1'
    ], array(), self::$atributos)->after(function ($validator) use (&$logMov,$usuario){
      if(!$validator->errors()->any()){
        $data = $validator->getData();
        $logMov = LogMovimiento::find($data['id_log_movimiento']);
        if(!$usuario->usuarioTieneCasino($logMov->id_casino)){
          $validator->errors()->add('id_casino','El usuario no puede acceder a ese casino.');
        }
      }
    })->validate();
    
    if($this->noEsControlador($usuario->id_usuario,  $logMov)){
      $logMov->controladores()->attach($usuario->id_usuario);
      $logMov->save();
    }
    $logMov->tipo_carga = $req['tipoCarga'];
    $logMov->cant_maquinas= $req['cantMaq'];
    $logMov->estado_movimiento()->associate(8);//cargando
    $logMov->save();
    return $logMov;
  }

/////////////////////////////////////////////////////MOVIMIENTOS SIN EXPEDIENTE

  public function casinosYMovimientosIngresosEgresos(){
    $id_usuario = session('id_usuario');
    $cas= UsuarioController::getInstancia()->buscarCasinosDelUsuario($id_usuario);
    $t=TipoMovimiento::whereIn('id_tipo_movimiento',[11,12])->get();
    return ['casinos' =>$cas,
            'tipos_movimientos' => $t];
  }

  // Llamado desde ASIGNACION y desde el nuevo cambio (Enero 2020)
  // es SOLO para INGRESOS INICIALES y EGRESOS DEFINITIVOS
  public function nuevoLogMovimiento(Request $request){
    $user = UsuarioController::getInstancia()->quienSoy()['usuario'];
    $validator =Validator::make($request->all(),
    [
      'id_tipo_movimiento' => 'required|exists:tipo_movimiento,id_tipo_movimiento',
      'id_casino' => 'required|exists:casino,id_casino'
    ], array(), self::$atributos)->after(function($validator) use ($user){
      if(!$validator->errors()->any()){
        $data = $validator->getData();
        $tipo_mov = TipoMovimiento::find($data['id_tipo_movimiento']);
        $id_casino = $data['id_casino'];
        // Lo que hago es verificar que no sea una intervencion MTM, 
        // por si en algun futuro agregan otro que no sea uno de estos 2.
        if($tipo_mov->es_intervencion_mtm){
          $validator->errors()->add('id_tipo_movimiento', 'No se permite ese tipo de movimiento con esta operacion.');
        }
        if(!$user->usuarioTieneCasino($id_casino)){
          $validator->errors()->add('id_casino','El usuario no puede acceder a ese casino.');
        }
      }
    })->validate();

    $log = null;
    DB::transaction(function () use ($request, $user,&$log) {
        //creo un expedienteAux para que cuando el movimiento se muestre en la lista no tenga problemas y se muestre
        //hay que crearlo a pata en la base de datos del sistema que funciona con los casinos
        $expedienteAux = Expediente::where(
            [
                ['concepto', '=', 'expediente_auxiliar_para_movimientos'],
                ['id_casino', '=', $request['id_casino']],
            ]
        )->get()->first();

        $logMovimiento = new LogMovimiento;
        $logMovimiento->tiene_expediente = 0;
        $logMovimiento->estado_relevamiento()->associate(1); // Generado
        $logMovimiento->estado_movimiento()->associate(6); // Notificado
        $logMovimiento->tipo_movimiento()->associate($request['id_tipo_movimiento']);
        // Los movimientos de INGRESO INICIAL / EGRESO DEFINITIVO no tienen un sentido
        $logMovimiento->sentido = "---";
        $f = date("Y-m-d");
        $logMovimiento->fecha = $f;
        $logMovimiento->casino()->associate($request['id_casino']);
        $logMovimiento->expediente()->associate($expedienteAux->id_expediente);
        $logMovimiento->save();
        $logMovimiento->controladores()->attach($user->id_usuario);
        $logMovimiento->save();

        $log = DB::table('log_movimiento')
            ->select('log_movimiento.*', 'tipo_movimiento.descripcion', 'casino.id_casino', 'expediente.*')
            ->join('tipo_movimiento', 'tipo_movimiento.id_tipo_movimiento', '=', 'log_movimiento.id_tipo_movimiento')
            ->join('casino', 'casino.id_casino', '=', 'log_movimiento.id_casino')
            ->join('expediente', 'expediente.id_expediente', '=', 'log_movimiento.id_expediente')
            ->where('log_movimiento.id_log_movimiento', '=', $logMovimiento->id_log_movimiento)
            ->get()->first();
    });
    return response()->json($log);
  }

  public function asociarExpediente($id_log_movimiento, $id_expediente){
    //Si estas perdido de porque se guarda dos veces el nro_exp uno en el log y otro en el expediente mismo
    //Es porque para diferenciar entre movimientos de ingreso inicial/egreso definitivo e intervenciones se decidio (yo no, legacy)
    //que en los primeros el log_movimimiento tenia un id_expediente asociado (aunque fuera uno "por defecto" todo en 0) y en los otros fuera nulo
    //Sin embargo salio el requerimiento que las intervenciones tambien tenian expedientes pero solo se le cargaba el numero para documentar
    //Entonces se le creo un campo a parte en el mismo log_movimiento
    //Para mas explicaciones ver arriba de todo - Octavio 28 sep 2020
    $expediente = Expediente::find($id_expediente);
    $logMovimiento = LogMovimiento::find($id_log_movimiento);
    $logMovimiento->nro_exp_org = $expediente->nro_exp_org;
    $logMovimiento->nro_exp_interno = $expediente->nro_exp_interno;
    $logMovimiento->nro_exp_control = $expediente->nro_exp_control;
    if(!is_null($logMovimiento->id_expediente)){
      $logMovimiento->expediente()->associate($id_expediente);
    }
    $logMovimiento->tiene_expediente = 1;//@Legacy: Este atributo es superfluo, no lo puse yo
    $logMovimiento->save();
    //Debe asociarselo a las maquinas del movimiento tambien:
    if(isset($logMovimiento->relevamientos_movimientos))
    {
      foreach($logMovimiento->relevamientos_movimientos as $relev){
        $mtm = $relev->maquina;
        if(!is_null($mtm)) MTMController::getInstancia()->asociarExpediente($mtm->id_maquina, $id_expediente);
      }
    }

    return $logMovimiento;
  }

  public function disasociarExpediente($id_log_movimiento,$id_expediente){
    $logMovimiento = LogMovimiento::find($id_log_movimiento);
    $logMovimiento->nro_exp_org = '';
    $logMovimiento->nro_exp_interno = '';
    $logMovimiento->nro_exp_control = '';
    if(!is_null($logMovimiento->id_expediente)){
      $defecto_casino = Expediente::where([
          ['concepto', '=', 'expediente_auxiliar_para_movimientos'],
          ['id_casino', '=', $logMovimiento->id_casino]
      ])->get()->first();
      $logMovimiento->expediente()->associate($defecto_casino);
    }
    $logMovimiento->tiene_expediente = 0;
    $logMovimiento->save();
    if(isset($logMovimiento->relevamientos_movimientos))
    {
      foreach($logMovimiento->relevamientos_movimientos as $relev){
        $mtm = $relev->maquina;
        if(!is_null($mtm)) MTMController::getInstancia()->disasociarExpediente($mtm->id_maquina, $id_expediente);
      }
    }
  }

  public function eliminarMov($id_log_movimiento,
    $eliminarConFiscalizaciones = false,$eliminarConRelevamientos = false,$eliminarConExpediente = false)
  {
    $log = LogMovimiento::find($id_log_movimiento);
    if($log->fiscalizaciones()->count() > 0 && !$eliminarConFiscalizaciones) return 0;
    if($log->relevamientos_movimientos()->count() > 0 && !$eliminarConRelevamientos) return 0;
    $tiene_exp = !is_null($log->expediente) && $log->expediente->concepto != 'expediente_auxiliar_para_movimientos';
    if($tiene_exp && !$eliminarConExpediente) return 0;
    DB::transaction(function() use($log){
      $log->tipo_movimiento()->dissociate();
      $log->estado_movimiento()->dissociate();
      $log->expediente()->dissociate();
      $log->controladores()->detach();
      foreach ($log->relevamientos_movimientos as $rel) {
        $rel->log_movimiento()->dissociate();
        $rel->fiscalizacion()->dissociate();
        foreach($rel->toma_relevamiento_movimiento as $toma){
          foreach($toma->detalles_relevamiento_progresivo as $detProg){
            $detProg->delete();
          }
          $toma->delete();
        }
        $rel->delete();
      }
      foreach($log->fiscalizaciones as $f){
        if(!is_null($f->evento)) $f->evento->delete();
        $f->delete();
      }
      LogClicksMovController::getInstancia()->eliminar($log->id_log_movimiento);
      $log->delete();
    });
    return 1;
  }

  public function eliminarEventualidadMTM(Request $request){
    return $this->eliminarMovimiento($request,true);
  }

  public function eliminarMovimiento(Request $request,$validar_es_intervencion = false){
    /* Las intervenciones MTM se pueden eliminar libremente
     * Los ingresos solo si se es el unico que queda. Los egresos ponen la maquina en inhabilitada */
    $validator = Validator::make($request->all(),
    ['id_log_movimiento' => 'required|exists:log_movimiento,id_log_movimiento'], array(), self::$atributos)
    ->after(function($validator) use ($validar_es_intervencion){
      if(!$validator->errors()->any()){ //Si el log_movimiento existe
        $data = $validator->getData();
        $user = UsuarioController::getInstancia()->quienSoy()['usuario'];
        $log = LogMovimiento::find($data['id_log_movimiento']);
        if(!$user->usuarioTieneCasino($log->id_casino)){
          $validator->errors()->add('id_casino','El usuario no puede acceder a ese casino.');
        }
        if(!$user->es_administrador && !$user->es_superusuario){
          $validator->errors()->add('id_usuario','El usuario no puede realizar esa accion.');
        }
        if(!is_null($log->expediente) && $log->expediente->concepto != 'expediente_auxiliar_para_movimientos'){
          $validator->errors()->add('id_log_movimiento','El movimiento ya tiene asignado un expediente.');
        }
        if($validar_es_intervencion && !$log->tipo_movimiento->es_intervencion_mtm){
          $validator->errors()->add('id_tipo_movimiento','No puede eliminar un movimiento que no es una intervencion.');
        }
        if(!$log->tipo_movimiento->es_intervencion_mtm && is_null($log->id_expediente)){
          $validator->errors()->add('id_tipo_movimiento','Movimiento incosistente, no se puede eliminar.');
        }
        //No sigo validando si ya me dieron error los de arriba
        if($validator->errors()->any()) return;
        $tipo_mov = $log->tipo_movimiento->id_tipo_movimiento;
        //INGRESO o INGRESO INICIAL
        if($tipo_mov == 1 || $tipo_mov == 11){
          foreach($log->relevamientos_movimientos as $rel){
            $maquina = $rel->maquina;
            if($maquina->relevamiento_movimiento()->count() > 1){
              $validator->errors()->add('maquina','La maquina '.$maquina->nro_admin.' tiene movimientos.');
            }
          }
        }
      }
    })->validate();

    $ret = 0;
    DB::transaction(function() use (&$ret,$request){
      $log = LogMovimiento::find($request->id_log_movimiento);
      $tipo_mov = $log->tipo_movimiento->id_tipo_movimiento;
      if($tipo_mov == 1 || $tipo_mov == 11){//Borrando un ingreso
        foreach($log->relevamientos_movimientos as $rel){
          MTMController::getInstancia()->eliminarMTM($rel->maquina->id_maquina);
        }
      }
      else if($tipo_mov == 2 ||  $tipo_mov == 12){//Borrando un egreso
        foreach($log->relevamientos_movimientos as $rel){
          $maquina = $rel->maquina;
          $maquina->id_estado_maquina = 6;//INHABILITADA
          $maquina->save();
        }
      }
      $ret = $this->eliminarMov($log->id_log_movimiento,true,true,false);
    });
    return $ret;
  }

  public function eliminarMovimientoExpediente($id_log_movimiento){
    return $this->eliminarMov($id_log_movimiento,false,false,true);
  }

  /////////////////////////////////EXPEDIENTES//////////////////////////////////

  public function movimientosSinExpediente(Request $req){
    $logs= DB::table('log_movimiento')
             ->select('log_movimiento.id_log_movimiento','log_movimiento.fecha',
              'tipo_movimiento.descripcion','log_movimiento.sentido','casino.nombre','casino.id_casino')
              ->join('tipo_movimiento','tipo_movimiento.id_tipo_movimiento','=',
              'log_movimiento.id_tipo_movimiento')
              ->join('casino','casino.id_casino','=','log_movimiento.id_casino')
              ->where('log_movimiento.tiene_expediente','=',0)
              ->whereIn('casino.id_casino',$req['id_casino'])
              ->groupBy('tipo_movimiento.descripcion','log_movimiento.id_log_movimiento','log_movimiento.fecha',
               'casino.nombre','casino.id_casino')
              ->orderBy('log_movimiento.fecha','desc')
              ->get();

    return ['logs' => $logs];
  }

  /////////////////////////////////////////////////////////////////////////////

  public function obtenerRelevamientoToma($id_relevamiento,$nro_toma = 1){
    $rel = RelevamientoMovimiento::find($id_relevamiento);

    $mtm = DB::table('maquina')
              ->select('maquina.*','isla.nro_isla','formula.*')
              ->join('isla','isla.id_isla','=','maquina.id_isla')
              ->join('formula','formula.id_formula','=','maquina.id_formula')
              ->join('relevamiento_movimiento','relevamiento_movimiento.id_maquina','=','maquina.id_maquina')
              ->where('relevamiento_movimiento.id_relev_mov','=',$id_relevamiento)
              ->get()
              ->first();

    $juegos = $rel->maquina()->withTrashed()->first()->juegos;

    $toma = null;
    $fecha = null;
    $fisca = null;//fisca que hizp la toma del relevamiento
    $cargador = null;//fisca cargador
    $nombre = null;
    $progresivos = [];

    if($rel->id_fisca != null) $fisca = Usuario::find($rel->id_fisca);
    if($rel->id_cargador != null){
      $cargador = Usuario::find($rel->id_cargador);
    }else{
      $cargador = UsuarioController::getInstancia()->buscarUsuario(session('id_usuario'))['usuario'];
    }

    if($nro_toma <= 0) $nro_toma = 1;
    if($rel->toma_relevamiento_movimiento()->count() >= $nro_toma){
      $toma = $rel->toma_relevamiento_movimiento()->orderBy('toma_relev_mov.id_toma_relev_mov','asc')->skip($nro_toma - 1)->first();
      $fecha = $rel->fecha_relev_sala;
      if(!is_null($toma->juego)){
        $nombre = Juego::find($toma->juego)->nombre_juego;
      }
      foreach($toma->detalles_relevamiento_progresivo as $detProg){
        $pozo = $detProg->pozo;
        //@HACK: Aca tal vez habria que hacer withTrashed y chequear la fecha deleted_at con respecto a la del log_movimiento
	      if(is_null($pozo)) continue;
        $prog = $pozo->progresivo;
	      if(is_null($prog)) continue;
        $pozo_arr = $pozo->toArray();
        $prog_arr = $prog->toArray();

        $pozo_arr['es_unico'] = $prog->pozos()->count() == 1;
        $pozo_arr['niveles'] = [];
        foreach($pozo->niveles as $nivel){
          $pozo_arr['niveles'][] = $nivel->toArray();
        }
        $pozo_arr['det_rel_prog'] = $detProg->toArray();

        $prog_arr['pozo'] = $pozo_arr;
        $progresivos[] = $prog_arr;
      }
    }

    $datos_ultimo_relev = null;
    //Cuando se genera un Egreso Definitivo y ya estaba en Egreso Temporal
    //A veces no se puede retomar el formulario porque la maquina fue destruida
    //Permito que pueda recargar los mismos datos del ultimo egreso temporal
    if(!is_null($rel->id_fiscalizacion_movimiento)){
      $maq = $rel->maquina;//Necesito el objeto
      //Solo permito copiar el formulario si la maquina esta en egreso temporal
      $mtm_en_egreso_temporal = $maq->id_estado_maquina == 4;

      $ultimo_relev = $maq->relevamiento_movimiento()
      ->whereNull('id_fiscalizacion_movimiento')//Intervencion MTM
      ->orderBy('fecha_relev_sala','desc')->first();//Agarro el ultimo
      //Y la ultima intervencion MTM es de egreso temporal y esta validada
      $ultimo_relev_es_egreso = $ultimo_relev->log_movimiento->sentido == 'EGRESO TEMPORAL';
      $ultimo_relev_visado = $ultimo_relev->id_estado_relevamiento == 4;

      if($mtm_en_egreso_temporal && $ultimo_relev_es_egreso && $ultimo_relev_visado){
        $datos_ultimo_relev = $ultimo_relev->toma_relevamiento_movimiento()->first();
        //No hay recursion infinita porque no va a entrar al if de id_fiscalizacion_movimiento arriba
        $datos_ultimo_relev = $this->obtenerRelevamientoToma($datos_ultimo_relev->id_relevamiento_movimiento);
      }
    }
    

    $mtm->nro_admin .= is_null($mtm->deleted_at)? '' : ' (ELIM.)';
    $log = $rel->log_movimiento;
    return ['relevamiento' => $rel,'maquina' => $mtm, 'juegos' => $juegos,'toma' => $toma,
     'fiscalizador' => $fisca,'cargador' => $cargador,
     'tipo_movimiento' =>  $log->tipo_movimiento , 'estado' => $rel->estado_relevamiento,
     'fecha' => $fecha, 'nombre_juego' => $nombre,'progresivos' => $progresivos,
     'nro_exp_org' => $log->nro_exp_org, 'nro_exp_interno' => $log->nro_exp_interno, 'nro_exp_control' => $log->nro_exp_control,
     'datos_ultimo_relev' => $datos_ultimo_relev ];
  }

  public function buscarEventualidadesMTMs(Request $request){
    $usuario = UsuarioController::getInstancia()->buscarUsuario(session('id_usuario'))['usuario'];
      Validator::make($request->all(), [
          'id_casino' => 'nullable',
          'fecha' => 'nullable|date_format:Y-m-d',
          'id_tipo_movimiento' => 'nullable|exists:tipo_movimiento,id_tipo_movimiento',
          'id_casino' => 'nullable|exists:casino,id_casino',
          'mtm' => 'nullable|exists:maquina,nro_admin',
          'sentido' => 'nullable|string',
          'nro_exp_org' => 'nullable|string',
          'nro_exp_interno' => 'nullable|string',
          'nro_exp_control' => 'nullable|string',
      ], array(), self::$atributos)->after(function ($validator){})->validate();

      $reglas=array();

      if(isset($request->id_tipo_movimiento)){
        $reglas[]=['log_movimiento.id_tipo_movimiento','=', $request->id_tipo_movimiento];
      }

      if(isset($request->nro_admin)){
        $reglas[]=['relevamiento_movimiento.id_tipo_movimiento','=', $request->nro_admin];
      }

      if(isset($request->isla)){
        $reglas[]=['log_movimiento.islas','like' ,'%' . $request->isla . '%'];
      }

      if(isset($request->mtm)){
        $reglas[]=['relevamiento_movimiento.nro_admin','=' , $request->mtm ];
      }

      if(isset($request->sentido)){
        $reglas[]=['log_movimiento.sentido','=',$request->sentido];
      }

      if(isset($request->id_casino)){
        $reglas[] = ['log_movimiento.id_casino','=',$request->id_casino];
      }

      if(isset($request->nro_exp_org)){
        $reglas[] = ['log_movimiento.nro_exp_org','LIKE',$request->nro_exp_org.'%'];
      }
      if(isset($request->nro_exp_interno)){
        $reglas[] = ['log_movimiento.nro_exp_interno','LIKE',$request->nro_exp_interno.'%'];
      }
      if(isset($request->nro_exp_control)){
        $reglas[] = ['log_movimiento.nro_exp_control','=',$request->nro_exp_control];
      }

      $casinos = array();

      foreach ($usuario->casinos as $casino) {
        $casinos[] = $casino->id_casino;
      }

      $resultados= DB::table('log_movimiento')
      ->select('log_movimiento.*','tipo_movimiento.*',
        'estado_movimiento.descripcion as estado_mov_descripcion',
        'estado_relevamiento.descripcion as estado_rel_descripcion',
        'casino.*',
        'tipo_movimiento.*')
      ->join('casino','casino.id_casino','=','log_movimiento.id_casino')
      ->join('tipo_movimiento','tipo_movimiento.id_tipo_movimiento','=','log_movimiento.id_tipo_movimiento')
      ->join('estado_movimiento','estado_movimiento.id_estado_movimiento','=','log_movimiento.id_estado_movimiento')
      ->leftJoin('estado_relevamiento','log_movimiento.id_estado_relevamiento','=','estado_relevamiento.id_estado_relevamiento')
      ->leftJoin('relevamiento_movimiento','relevamiento_movimiento.id_log_movimiento','=','log_movimiento.id_log_movimiento')
      ->whereIn('log_movimiento.id_casino',$casinos)
      ->where($reglas)
      ->whereNull('log_movimiento.id_expediente');

      if(isset($request->fecha)){
        $fecha=explode("-", $request->fecha);
        $resultados = $resultados->whereYear('log_movimiento.fecha' , '=', $fecha[0])
                      ->whereMonth('log_movimiento.fecha','=', $fecha[1]);
      }
      $sort_by = $request->sort_by;
      $resultados = $resultados->when($sort_by,function($query) use ($sort_by){
        // Como no tiene hora la fecha, si queremos que los cargados mas tarde pero
        // en el mismo dia aparezcan despues, ordenamos por ID.
        return $query->orderBy($sort_by['columna'],$sort_by['orden'])->orderBy('log_movimiento.id_log_movimiento','desc');
      });
      $resultados = $resultados->groupBy('log_movimiento.id_log_movimiento');
      $resultados = $resultados->paginate($request->page_size,['log_movimiento.id_log_movimiento']);

      $tipos = TipoMovimiento::where('puede_reingreso',1)->orWhere('puede_egreso_temporal',1)
      ->where('deprecado',0)->get();
      $esControlador=UsuarioController::getInstancia()->usuarioEsControlador($usuario);

      return ['eventualidades'=>$resultados,
              'esControlador' =>$esControlador,
              'esSuperUsuario' => $usuario->es_superusuario,
              'tiposEventualidadesMTM'=> $tipos,
              'casinos' => $usuario->casinos];

  }

  public function eventualidadesMTM(){
    $usuario = UsuarioController::getInstancia()->buscarUsuario(session('id_usuario'))['usuario'];
    UsuarioController::getInstancia()->agregarSeccionReciente('Intervenciones MTM' , 'eventualidadesMTM');
    return view('eventualidadesMTM',
      [
        'eventualidades'         => [],
        'usuario'          => $usuario,
        'tiposEventualidadesMTM' => TipoMovimiento::all(),
        'casinos'                => $usuario->casinos,
        'causasNoTomaProgresivo' => TipoCausaNoTomaProgresivo::all()
      ]
    );
  }

  //suponiendo que me va a enviar un array con los ids de maquina
  public function nuevaEventualidadMTM( Request $request){
    $validator = Validator::make($request->all(), [
        'id_tipo_movimiento' => 'required|exists:tipo_movimiento,id_tipo_movimiento',
        'maquinas' => 'required',
        'maquinas.*.id_maquina' => 'required|exists:maquina,id_maquina',
        'sentido' => ['required','string',Rule::in(['EGRESO TEMPORAL', 'REINGRESO'])],
        'id_casino' => 'required|exists:casino,id_casino'
    ], array(), self::$atributos)->after(function($validator){
      $data = $validator->getData();
      if($data['id_tipo_movimiento']==9){
        $validator->errors()->add('tipo_movimiento', 'No se ha seleccionado el tipo de movimiento.');
      }
      $sentido = $data['sentido'];
      $tipo = TipoMovimiento::find($data['id_tipo_movimiento']);
      //Verifico que el tipo pueda realizar un movimiento en ese sentido
      if($sentido == 'EGRESO TEMPORAL' && !$tipo->puede_egreso_temporal){
        $validator->errors()->add('tipo_movimiento', 'El movimiento seleccionado no se puede realizar en ese sentido.');
      }
      if($sentido == 'REINGRESO' && !$tipo->puede_reingreso){
        $validator->errors()->add('tipo_movimiento', 'El movimiento seleccionado no se puede realizar en ese sentido.');
      }
      $id_casino = $data['id_casino'];
      if(!UsuarioController::getInstancia()->quienSoy()['usuario']->usuarioTieneCasino($id_casino)){
        $validator->errors()->add('id_casino','El usuario no puede acceder a este casino.');
      }
      foreach($data['maquinas'] as $m){
        if(Maquina::find($m['id_maquina'])->id_casino != $id_casino){
          $validator->errors()->add('id_casino','Las maquinas que no corresponden con el casino.');
          break;
        }
      }
    })->validate();

    $logMovimiento = null;
    DB::beginTransaction();
    try{
      $logMovimiento = new LogMovimiento;
      $logMovimiento->fecha= date("Y-m-d");
      $logMovimiento->tiene_expediente = 0;
      $logMovimiento->tipo_movimiento()->associate($request['id_tipo_movimiento']);
      $logMovimiento->estado_movimiento()->associate(6);//creado
      $logMovimiento->estado_relevamiento()->associate(1);//generado
      $logMovimiento->sentido = $request['sentido'];
      $logMovimiento->save();
  
      foreach ($request['maquinas'] as $mtm) {
        $relevamiento = RelevamientoMovimiento::where([['id_maquina','=', $mtm['id_maquina']],['id_log_movimiento','=',$logMovimiento->id_log_movimiento]])->get()->first();
        // Deberia entrar siempre, a menos que mande varias veces la misma maquina
        if($relevamiento == null){
          $maq = Maquina::find($mtm['id_maquina']);
          $relevamiento = RelevamientoMovimientoController::getInstancia()->crearRelevamientoMovimiento($logMovimiento->id_log_movimiento, $maq);
          $this->guardarIslasMovimiento($logMovimiento,$maq);
        }
      }

      $logMovimiento->casino()->associate($request['id_casino']);
      $logMovimiento->save();
      DB::commit();
    }
    catch(Exception $e){
      DB::rollBack();
      return null;
    }

    return $logMovimiento->id_log_movimiento;
  }

  public function tiposMovIntervMTM(){
    $tipos = TipoMovimiento::where('puede_reingreso',1)->orWhere('puede_egreso_temporal',1)
    ->where('deprecado',0)->get();
    return ['tipos_movimientos' => $tipos];
  }

  public function relevamientosEvMTM($id_log_mov){
    $log = LogMovimiento::find($id_log_mov);
    $relevamientos = $log->relevamientos_movimientos;
    $relevamientos_arr = array();
    foreach ($relevamientos as $rel) {
      $maquina = $rel->maquina()->withTrashed()->first();
      $nro_admin = is_null($rel->maquina)? $maquina->nro_admin . ' (ELIM.)' : $maquina->nro_admin;
      $relevamientos_arr[] = [
                     'id_relevamiento' => $rel->id_relev_mov,
                     'estado' => $rel->estado_relevamiento,
                     'nro_admin' => $nro_admin,
                     'id_maquina' => $maquina->id_maquina,
                     'tomas' => $rel->toma_relevamiento_movimiento()->count()
                   ];
    }
    $id_usuario = session('id_usuario');
    $user = Usuario::find($id_usuario);
    return ['relevamientos' => $relevamientos_arr,'cargador' => $user,'fiscalizador' => null,
            'tipo_movimiento' => $log->tipo_movimiento->descripcion, 'sentido' => $log->sentido,
            'casino' => $log->casino,
            'nro_exp_org' => $log->nro_exp_org, 'nro_exp_interno' => $log->nro_exp_interno, 'nro_exp_control' => $log->nro_exp_control
          ];
  }


  public function visarConObservacion(Request $request){//@TODO: Borrar maquina si fue ERROR e INGRESO?
    $relevMov = null;
    $fisMov = null;
    $logMov = null;
    $id_usuario = session('id_usuario');

    $validator = Validator::make($request->all(), [
      'id_relev_mov' => 'required|exists:relevamiento_movimiento,id_relev_mov',
      'nro_toma' => 'nullable',
      'observacion' => 'nullable|string',
      'nro_exp_org' => 'nullable|string|max:5',
      'nro_exp_interno' =>  'nullable|string|max:7',
      'nro_exp_control' => 'nullable|string|max:1',
      'estado' => ['required', Rule::in(['valido', 'error']) ]
    ], array(), self::$atributos)->after(function($validator) use (&$logMov,&$relevMov,$id_usuario){
      if(count($validator->errors()) == 0){
        $relevMov = RelevamientoMovimiento::find($validator->getData()['id_relev_mov']);
        $logMov = $relevMov->log_movimiento;
        if(!Usuario::find($id_usuario)->usuarioTieneCasino($logMov->id_casino)){
          $validator->errors->add('id_relev_mov','El usuario no puede acceder a ese movimiento.');
        }
        if($logMov->tipo_movimiento->deprecado){
          $validator->errors->add('id_relev_mov','Este tipo de movimiento esta deprecado.');
        }
        if($logMov->sentido == '---' && $logMov->tipo_movimiento->es_intervencion_mtm){
          $validator->errors->add('id_relev_mov','La intervencion MTM no posee sentido.');
        }
      }
    })->validate();

    DB::beginTransaction();
    try{
      $logMov->nro_exp_org = is_null($request->nro_exp_org)? '' : $request->nro_exp_org;
      $logMov->nro_exp_interno = is_null($request->nro_exp_interno)? '' : $request->nro_exp_interno;
      $logMov->nro_exp_control = is_null($request->nro_exp_control)? '' : $request->nro_exp_control;
      $logMov->save();
      if($this->noEsControlador($id_usuario,$logMov)){
        $logMov->controladores()->attach($id_usuario);
        $logMov->save();
      }
      //a las tomas de los relevamientos las marco como validadas
      RelevamientoMovimientoController::getInstancia()->validarRelevamientoTomaConObservacion($relevMov, $request->estado == 'valido'? 1 : 0, $request->observacion);

      if(!is_null($relevMov->id_fiscalizacion_movimiento)){
        $relss = RelevamientoMovimiento::where('id_fiscalizacion_movimiento','=',$relevMov->id_fiscalizacion_movimiento)
        ->whereIn('id_estado_relevamiento',[4,6])->get();
        $fisMov = $relevMov->fiscalizacion;
        if($fisMov->relevamientos_movimientos()->count() == count($relss)){
          $fisMov->estado_relevamiento()->associate(4);
          $fisMov->save();
        }
      }

      $relss = RelevamientoMovimiento::where('id_log_movimiento','=',$logMov->id_log_movimiento)
                ->whereIn('id_estado_relevamiento',[4,6])->get();

      if($logMov->relevamientos_movimientos()->count() == count($relss)){
        $logMov->estado_relevamiento()->associate(4);
        $logMov->estado_movimiento()->associate(4);
        $logMov->save();
        $estado_intervencionmtm = $logMov->sentido == 'REINGRESO'?  2 : 4;

        //Todo esto es para seleccionar el nuevo estado y el mensaje
        //Si es intervencion MTM, segun el sentido es "Reingreso" o "Egreso Temporal"
        //Si no, si es ingreso inicial, es "Ingreso". Si es egreso definitivo, "Egreso Definitivo"
        $nuevo_estado = null;
        $texto = "";
        $tipoMov = $logMov->tipo_movimiento;//Alias para no repetir tanto
        //No tenemos que chequear que este deprecado porque lo validamos arriba.
        if($tipoMov->es_intervencion_mtm){
          $nuevo_estado = $logMov->sentido == 'REINGRESO'?  2 : 4;
          $texto = ucwords(strtolower($logMov->tipo_movimiento->descripcion))." validado.";
          $id = $tipoMov->id_tipo_movimiento;//Alias
          //Para denominación, % devolucion, juego, cupo le agrego esto para que sea mas estetico
          if($id == 5 || $id == 6 || $id == 7 || $id == 15){
            $texto = 'Cambio de '.$texto;
          }
        }
        else if($tipoMov->id_tipo_movimiento == 11){
          $nuevo_estado = 1;
          $texto = "Ingreso inicial validado.";
        }
        else if($tipoMov->id_tipo_movimiento == 12){
          $nuevo_estado = 3;
          $texto = "Egreso definitivo validado.";
        }
        else{
          throw new \Exception('Unreachable');
        }
        foreach($logMov->relevamientos_movimientos as $rel){
          if($rel->id_estado_relevamiento == 4 && !is_null($nuevo_estado)){
            $maquina = $rel->maquina()->withTrashed()->first();
            $maquina->estado_maquina()->associate($nuevo_estado);
            $maquina->save();

            $tomas = $rel->toma_relevamiento_movimiento()->orderBy('toma_relev_mov.id_toma_relev_mov','asc')->get();
            $multiples_tomas = $rel->toma_relevamiento_movimiento()->count() > 1;
            //Multiples tomas por relevamiento estan deprecadas pero las considero por las dudas.
            $razon = $texto." \n";
            foreach($tomas as $idx => $toma){
              if($multiples_tomas) $razon = $razon . "Toma " . ($idx+1) . ": \n";
              $razon = $razon . $toma->observaciones . " \n";
            }
            LogMaquinaController::getInstancia()->registrarMovimiento($maquina->id_maquina, $razon, $logMov->id_tipo_movimiento);
          }
        }
      }
      DB::commit();
    }
    catch(Exception $e){
      DB::rollBack();
      throw $e;
    }
    return ['relError'    => $relevMov->id_estado_relevamiento == 6,
            'relValidado' => $relevMov->id_estado_relevamiento == 4,
            'movValidado' => $logMov->id_estado_relevamiento == 4, 
            'fisValidada' => !is_null($fisMov) && $fisMov->id_estado_relevamiento == 4 ];
  }
}
