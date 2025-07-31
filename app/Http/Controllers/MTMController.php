<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Validator;
use App\Formula;
use App\TipoGabinete;
use App\TipoMoneda;
use App\UnidadMedida;
use App\Maquina;
use App\Casino;
use App\Isla;
use App\Juego;
use App\TipoMaquina;
use App\EstadoMaquina;
use App\GliSoft;
use App\GliHard;
use App\PackJuego;
use App\LogMovimiento;
use App\Expediente;

class MTMController extends Controller
{
  //CONTROLADOR
  /*
    ENCARGADO DE OBTENER, CREAR Y MODIFICAR MAQUINAS.
  */
  private static $instance;

  private static $atributos=[
    'id_casino' => 'Casino',
    'nro_admin' => 'Número de Administración',
    'marca' => 'Marca',
    'modelo' => 'Modelo',
    'marca_juego' => 'Abreviación Marca - Juego',
    'unidad_medida' => 'Unidad de Medida',
    'nro_serie' => 'Número de Serie',
    'mac' => 'Dirección MAC',
    'juega_progresivo' => 'Juega Progresivo',
    'id_tipo_gabinete' => 'Tipo de Gabinete',
    'id_tipo_maquina' => 'Tipo de Máquina',
    'gli_soft' => 'Certificado Gli Soft',
    'gli_hard' => 'Certificado Gli Hard',
    'formula' => 'Formula',
    'id_isla' => 'Número de Isla',
    'expedientes' => 'Expedientes',
    'notas' => 'Notas',
    'denominacion' => 'Denominación',
    'id_estado_maquina' => 'Estado de Máquina',
    'id_tipo_moneda' => 'Moneda'
  ];

  public static function getInstancia() {
    if (!isset(self::$instance)) {
      self::$instance = new MTMController();
    }
    return self::$instance;
  }

  //BUSCA TODA LA INFORMACION PARA CARGAR MODAL
  public function buscarTodo(){
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

    UsuarioController::getInstancia()->agregarSeccionReciente('Máquinas' ,'maquinas');
    return view('seccionMaquinas', ['unidades_medida' => $unidad_medida,   'casinos' => $casinos, 'tipos' => $tipos , 'monedas' => $monedas , 'gabinetes' => $gabinetes, 'estados' => $estados]);
  }

  public function obtenerConfiguracionMaquina($id){
    $mtm=Maquina::find($id);
    return ['maquina' => $mtm];
  }

  public function obtenerMTMReducido($id){
    //Devuelve solo configuracion de maquina. Usado para buscadores
    $maquina = Maquina::findOrFail($id);
    return $maquina;
  }

  public function obtenerMTM($id){
      //@param: $id int - @return: Maquina $maquina
      //Devuelve toda la configuracion de una maquian
     $mtm=Maquina::find($id);

     // la gestion de juego activo, se contempla solo si la mtm no es multijuego

    if($mtm->juego_activo == null){
      $mtm->juego_activo()->associate($mtm->juegos[0]->id_juego);
      $mtm->save();
    }
    $juego_activo = $mtm->juego_activo;

    //JUEGOS DE LA MAQUINA
    $juegos = $mtm->juegos;
    //OBTENGO EL GLI
    $gli_soft = [];
    foreach($juegos as $j){
      foreach($j->gliSoft as $gli){
        if($gli->archivo != null){
          $nombre_archivo=$gli->archivo->nombre_archivo;
        }else{
          $nombre_archivo='';
        }
        $gli_soft[] = [
          'id' => $gli->id_gli_soft,
          'nro_archivo' => $gli->nro_archivo,
          'observaciones' => $gli->observaciones ,
          'nombre_archivo' =>$nombre_archivo,
          'juego' => $j->nombre_juego,
          'id_juego' => $j->id_juego,
          'activo' => $j->id_juego == $juego_activo->id_juego
        ];
      }
      //si no tiene un gli asociado, devuelve id 0
      if(count($gli_soft)==0) $gli_soft[] = ['id' => 0 , 'nro_archivo' => '-' , 'nombre_archivo' => ''];
    }
      
     $array = array();
     foreach ($juegos as $un_juego){//Creo una lista con los juegos no activos
         $return_juego = new \stdClass();
         $return_juego->id_juego = $un_juego->id_juego;
         $return_juego->nombre_juego =  $un_juego->nombre_juego;
         $return_juego->denominacion =  $un_juego->pivot->denominacion;
         $return_juego->porcentaje_devolucion =  $un_juego->pivot->porcentaje_devolucion;
         $return_juego->tablasPago =  $un_juego->tablasPago;
         $return_juego->id_gli_soft = $un_juego->id_gli_soft;
         if($un_juego->nombre_juego == $juego_activo->nombre_juego){
            $juego_activo = $return_juego;
            $encontrado = true;
        }else {
            $array[] = $return_juego;
        }
     }

     // Gestion de multijuego
     $juegos_mtm_pack=PackJuegoController::getInstancia()->obtenerJuegosDePackMTM($id);

     if($mtm->gliHard != null){
        if($mtm->gliHard->archivo != null){
          $nombre_archivo=$mtm->gliHard->archivo->nombre_archivo;
        }else{
            $nombre_archivo='';
        }
        $gli_hard=['id' => $mtm->gliHard->id_gli_hard, 'nro_archivo' => $mtm->gliHard->nro_archivo, 'nombre_archivo' => $nombre_archivo];
      }else{
          $gli_hard = ['id' => 0 , 'nro_archivo' => '-' , 'nombre_archivo' => ''];
      }

      if($mtm->pozo != null){//si aporta a un pozo, es decir, juega progresivo
          $progresivo = ['progresivo' => $mtm->pozo->niveles_progresivo[0]->progresivo , 'niveles' => $mtm->pozo->formatearBase($mtm->pozo->niveles_progresivo)];
      }
      else{
          $progresivo = null;
      }

      if(isset($mtm->isla)){
        $isla = $mtm->isla;
        $sector =  $mtm->isla->sector;
      }else{
        $isla = null;
        $sector = null;
      }

      $marca_juego_es_generado = $mtm->marca_juego == $this->marcaJuego($mtm->marca,$mtm->juego_activo->nombre_juego,$mtm->id_casino);
      $unidades = DB::table('unidad_medida')->select('unidad_medida.*')->get();
      return['maquina' => $mtm,
             'marca_juego_es_generado' => $marca_juego_es_generado,
             'moneda' => (!is_null($mtm->id_tipo_moneda))?$mtm->tipoMoneda:null,
             'tipo_gabinete' => $mtm->tipoGabinete,
             'tipo_maquina' => $mtm->tipoMaquina,
             'estado_maquina' => $mtm->estado_maquina,
             'isla' => $isla,
             'sector' => $sector,
             'sectores' => $mtm->casino->sectores,
             'casino' => $mtm->casino,
             'expedientes' => $mtm->expedientes,
             'juegos' => $array, // arreglo de juegos, sin el juego activo
             'juego_activo' => $juego_activo,
             'progresivo' => $progresivo,
             'gli_soft' => $gli_soft,
             'gli_hard' => $gli_hard,
             'formula' => $mtm->formula,
             'denominacion' => $mtm->denominacion,
             'devolucion' => $mtm->porcentaje_devolucion,
             'unidad_medida' => $mtm->id_unidad_medida,
             'unidades' => $unidades,
             'juegosMovimiento' => $mtm->juegos,
             'juego_pack_mtm'=>$juegos_mtm_pack,
          ];
  }

  public function obtenerMTMEnCasino($id_casino,$nro_admin,$estados = null){
    //dado un casino,devuelve maquinas que concuerden con el nro admin dado
    //usado para busqueda de maquinas
    if($id_casino == 0){
      $usuario = UsuarioController::getInstancia()->buscarUsuario(session('id_usuario'))['usuario'];
      $id_casino = $usuario->casinos[0]->id_casino;
    }
    
    $maquinas  = Maquina::where([['maquina.id_casino' , '=' , $id_casino] ,['maquina.nro_admin' , 'like' , $nro_admin . '%']]);
    
    if($estados !== null){
      $maquinas = $maquinas->whereIn('id_estado_maquina',$estados);
    }
    $maquinas = $maquinas->get();
    return compact('maquinas');
  }
  
  public function obtenerMTMEnCasinoHabilitadas($id_casino,$nro_admin){
    return $this->obtenerMTMEnCasino($id_casino,$nro_admin,[1,2,4,5,6,7]);
  }
  
  public function obtenerMTMEnCasinoEgresadas($id_casino,$nro_admin){
    return $this->obtenerMTMEnCasino($id_casino,$nro_admin,[1,2,4,5,6,7]);
  }

  public function desasociarFormula($id_formula){
     $MTMS=Formula::find($id_formula)->maquinas;
      foreach ($MTMS as $MTM){
        $MTM->formula()->dissociate();
        $MTM->save();
      }
  }

  public function buscarMaquinas(Request $request){
    $reglas=array();
    if($request->estado_maquina!=0){
      if($request->estado_maquina==1){
        $estados=array('1','2');
      }else{
        $estados=array('3','4','5','6','7');
      }
    }else{
      $estados=array('1','2','3','4','5','6','7');
    }
    if(isset($request->nro_admin)){
      $reglas[]=['m.nro_admin' , 'like' , '%' . $request->nro_admin . '%'];
    }
    if(isset($request->marca)){
      $reglas[]=['m.marca' , 'like' , '%' . $request->marca . '%'];
    }
    if(isset($request->nro_isla)){
      $reglas[]=['i.nro_isla' , '=' , $request->nro_isla];
    }
    if($request->id_sector!=0){
      $reglas[]=['s.id_sector' , '=' , $request->id_sector];
    }
    if(isset($request->denominacion)){
      $reglas[]=['m.denominacion' , '=' , $request->denominacion];
    }
    if(isset($request->nro_isla)){
      $reglas[]=['i.nro_isla' , '=' , $request->nro_isla ];
    }
    if(isset($request->nombre_juego)){
      $reglas[]=['j.nombre_juego' , 'like' , '%' . $request->nombre_juego . '%' ];
    }
    if(isset($request->id_tipo_moneda)){
      $reglas[]=['m.id_tipo_moneda','=',$request->id_tipo_moneda];
    }

    if($request->id_casino==0){
      $usuario = UsuarioController::getInstancia()->buscarUsuario(session('id_usuario'))['usuario'];
      $casinos = array();
      foreach($usuario->casinos as $casino){
        $casinos[]=$casino->id_casino;
      }
    }else{
      $casinos[]=$request->id_casino;
    }
    $sort_by = $request->sort_by;
    $resultados=DB::table('maquina as m')
    ->select(
      'm.id_maquina','m.nro_admin','m.id_estado_maquina','m.desc_marca',
      'm.marca','j.nombre_juego',DB::raw('IF(m.id_unidad_medida=1,m.denominacion,1) as denominacion'),
      'c.codigo','s.descripcion','i.nro_isla',
      'em.descripcion as estado_maquina'
    )
    ->leftJoin('isla as i' , 'i.id_isla','=','m.id_isla')
    ->leftJoin('casino as c' ,'c.id_casino','=','m.id_casino')
    ->leftJoin('sector as s','s.id_sector','=','i.id_sector')
    ->leftJoin('estado_maquina as em','em.id_estado_maquina','=','m.id_estado_maquina')
    ->leftJoin('juego as j','j.id_juego','=','m.id_juego')
    ->when($sort_by,function($query) use ($sort_by){
      return $query->orderBy($sort_by['columna'],$sort_by['orden']);
    })
    ->where($reglas)
    ->whereIn('m.id_casino',$casinos)
    ->whereIn('m.id_estado_maquina',$estados)
    ->whereNull('m.deleted_at')
    ->paginate($request->page_size);

    return $resultados;
  }
  //guarda las maquinas que vienen de un csv, no tan temporalmente..
  public function guardarMaquinasTemporales($maquinas, $id_casino){
      $usuario = UsuarioController::getInstancia()->buscarUsuario(session('id_usuario'));
      if($usuario['usuario']->usuarioTieneCasino($id_casino)){
        foreach ($maquinas as $maquinaTemp) {
              $maquina = new Maquina;
              $maquina->nro_admin = $maquinaTemp->nro_admin;
              $maquina->id_isla = $isla->id_isla;
              $maquina->marca = $maquinaTemp->marca;
              $maquina->modelo = $maquinaTemp->modelo;
              $maquina->id_juego = $maquinaTemp->juego_activo->id_juego;
              $maquina->denominacion =  $maquinaTemp->denominacion;
              $maquina->juega_progresivo= $maquinaTemp->juega_progresivo;
              $maquina->id_unidad_medida  = $maquinaTemp->unidad_medida;
              $maquina->porcentaje_devolucion = $maquinaTemp->porcentaje_devolucion;
              $maquina->id_estado_maquina = 1; //ingreso
              // $maquina->id_tipo_moneda = ;//pesos por defecto ?
              $maquina->id_casino= $id_casino;
              $maquina->marca_juego = $this->marcaJuego($maquina->marca,$maquinaTemp->juego_activo->nombre_juego,$maquina->id_casino);
              $maquina->save();
              $razon = "La maquina se creo desde un archivo.";
              LogMaquinaController::getInstancia()->registrarMovimiento($id_maquina, $razon,1);//tipo mov ingreso (pero esta sin validar todavia)
        }
      }
  }

  public function guardarMaquina(Request $request){
    $validator = Validator::make($request->all(), [
          'id_log_movimiento' => 'required|exists:log_movimiento,id_log_movimiento',
          'nro_admin' => 'required|integer',
          'marca'=> 'required|max:45',
          'modelo' => 'required|max:60',
          'mac' => 'nullable|max:100',
          'nro_serie'=>  'nullable|alpha_dash',
          'marca_juego' => 'nullable|max:100',
          'generar_marca_juego' => 'required|boolean',
          'juega_progresivo' => 'required|boolean',
          'denominacion' => ['required','regex:/^\d\d?\d?\d?\d?\d?\d?\d?([,|.]\d\d?)?$/'],
          'id_tipo_moneda' => 'required|exists:tipo_moneda,id_tipo_moneda',
          'id_unidad_medida' => 'required|exists:unidad_medida,id_unidad_medida',
          'id_tipo_gabinete'=> 'nullable|exists:tipo_gabinete,id_tipo_gabinete',
          'id_tipo_maquina' => 'nullable|exists:tipo_maquina,id_tipo_maquina',
          'id_estado_maquina' => 'required|exists:estado_maquina,id_estado_maquina',
          'id_isla' => 'required|integer|exists:isla,id_isla',
          'expedientes' => 'nullable',
          'expedientes.*.id_expediente' => 'required|exists:expediente,id_expediente',
          'juegos' => 'array|required',
          'juegos.*.id_juego' => 'required|exists:juego,id_juego',
          'juegos.*.activo' => 'required|in:1,0',
          'formula.id_formula' => 'required|exists:formula,id_formula',
      ],array(),self::$atributos)->after(function($validator){
        if(!$validator->errors()->any()){
          $data = $validator->getData();
          $user = UsuarioController::getInstancia()->quienSoy()['usuario'];
          $usuario_casinos = DB::table('usuario_tiene_casino')->where('id_usuario',$user->id_usuario);
  
          $log_movimiento = LogMovimiento::find($data['id_log_movimiento']);
          $acceso_log = (clone $usuario_casinos)->where('id_casino',$log_movimiento->id_casino)->count();
          if($acceso_log == 0) $validator->errors()->add('id_log_movimiento', 'El usuario no puede acceder a ese movimiento.');
  
          $isla = Isla::find($data['id_isla']);
          $acceso_isla = (clone $usuario_casinos)->where('id_casino',$isla->id_casino)->count();
          if($acceso_isla == 0) $validator->errors()->add('id_isla', 'El usuario no puede acceder a esa isla.');
          if($isla->id_casino != $log_movimiento->id_casino) $validator->errors()->add('id_casino','Mismatch de los casinos entre el movimiento y la isla.');
          
          $acceso_unidad_medida = DB::table('casino_tiene_unidad_medida')
          ->where('id_casino',$log_movimiento->id_casino)
          ->where('id_unidad_medida',$data['id_unidad_medida'])->count();
          if($acceso_unidad_medida == 0) $validator->errors()->add('id_unidad_medida','El casino no tiene acceso a esa unidad de medida.');
  
          foreach($data['juegos'] as $j){
            $juego = Juego::find($j['id_juego']);
            $acceso_juego = $juego->casinos()->where('casino_tiene_juego.id_casino',$log_movimiento->id_casino)->count();
            if($acceso_juego == 0) $validator->errors()->add('id_juego','No puede acceder al juego '.$juego->id_juego);
          }
          $expedientes = $data['expedientes'];
          if(!empty($expedientes)){
            foreach($expedientes as $e){
              $exp = Expediente::find($e['id_expediente']);
              $acceso_expediente = $exp->casinos()->where('expediente_tiene_casino.id_casino',$log_movimiento->id_casino)->count();
              if($acceso_expediente == 0) $validator->errors()->add('id_expediente','No puede acceder al expediente '.$exp->id_expediente);
            }
          }
          $duplicados = Maquina::where([['id_casino' ,'=', $log_movimiento->id_casino] ,
                                        ['nro_admin' ,'=', $data['nro_admin']]])
                                        ->whereNull('deleted_at')
                                        ->get();
          if($duplicados->count() >= 1){
            $validator->errors()->add('nro_admin', 'El número de administración ya está tomado.');
          }
        }
    })->validate();

    $MTM = null;
    $cantidad = null;
    DB::beginTransaction();
    try {
      $log_movimiento        = LogMovimiento::find($request->id_log_movimiento);
      $MTM                   = new Maquina;
      $MTM->nro_admin        = $request->nro_admin;
      $MTM->marca            = $request->marca;
      $MTM->modelo           = $request->modelo;
      $MTM->desc_marca       = $request->desc_marca;
      $MTM->nro_serie        = $request->nro_serie;
      $MTM->mac              = $request->mac;
      $MTM->denominacion     = $request->denominacion;
      $MTM->juega_progresivo = $request->juega_progresivo;
      $MTM->id_casino        = $log_movimiento->id_casino;
      $MTM->id_formula       = $request->formula["id_formula"];
      $MTM->save();

      $juegos  = array(); //arreglo con todos los juegos
      foreach ($request['juegos'] as $unJuego) {
          $juego = Juego::find($unJuego['id_juego']);
          if($unJuego['activo'] == 1){
              $MTM->juego_activo()->associate($juego->id_juego);
              if($request->generar_marca_juego || $request->marca_juego == ""){
                $MTM->marca_juego = $this->marcaJuego($request->marca,$MTM->juego_activo->nombre_juego,$MTM->id_casino);
              }
              else{
                $MTM->marca_juego = $request->marca_juego;
              }
          }
          $id_pack_juego = null;
          $juegos[$juego->id_juego] = [
            'denominacion' => $unJuego['denominacion'], 
            'porcentaje_devolucion' => $unJuego['porcentaje_devolucion'], 
            'id_pack' => $id_pack_juego
          ];
      }
      $MTM->unidad_medida()->associate($request->id_unidad_medida);
      $MTM->tipoMoneda()->associate($request->id_tipo_moneda);
      $MTM->casino()->associate($request->id_casino);
      $MTM->juegos()->sync($juegos);
      $MTM->isla()->associate($request->id_isla);
      $MTM->estado_maquina()->associate(6);//Estado = Inhabilitada -->viene a ser un estado PENDIENTE DE HABILITACION
      if(!empty($request->id_tipo_gabinete)){$MTM->tipoGabinete()->associate($request->id_tipo_gabinete);}
      if(!empty($request->id_tipo_maquina) ){$MTM->tipoMaquina()->associate($request->id_tipo_maquina);}
      if(!empty($request->expedientes))     {$MTM->expedientes()->sync($request->expedientes);}
      if(!empty($request->notas))           {$MTM->notas()->sync($request->notas);}
      $MTM->save();
      $razon = "La maquina se creo manualmente.";
      LogMaquinaController::getInstancia()->registrarMovimiento($MTM->id_maquina, $razon,1);//tipo mov ingreso (pero esta sin validar todavia)
      $cantidad = LogMovimientoController::getInstancia()->guardarRelevamientoMovimientoIngreso($request['id_log_movimiento'],$MTM->id_maquina);
      DB::commit();
    } catch (Exception $e) {
      DB::rollBack();
      $MTM = null;
      $cantidad = null;
    }
    return ['maquina' => $MTM , 'cantidad' =>$cantidad ];
  }

  public function modificarMaquina(Request $request){
    Validator::make($request->all(), [
          'id_maquina' => 'required|exists:maquina,id_maquina' ,
          'nro_admin' => ['required ','integer','max:999999'],
          'marca'=> 'required|max:45',
          'modelo' => 'required|max:60',
          'mac' => 'nullable|max:100',
          'nro_serie'=>  'nullable|alpha_dash',
          'marca_juego' => 'nullable|max:100',
          'generar_marca_juego' => 'required|boolean',
          'id_tipo_moneda' => 'required|exists:tipo_moneda,id_tipo_moneda',
          'id_unidad_medida' => 'required|exists:unidad_medida,id_unidad_medida',
          'id_tipo_gabinete'=> 'nullable|exists:tipo_gabinete,id_tipo_gabinete',
          'id_tipo_maquina' => 'nullable|exists:tipo_maquina,id_tipo_maquina',
          'id_estado_maquina' => 'required|exists:estado_maquina,id_estado_maquina',
          'id_isla'  => 'required|exists:isla,id_isla',
          'denominacion' => ['required','regex:/^\d\d?\d?\d?\d?\d?\d?\d?([,|.]\d\d?)?$/'],
          'expedientes' =>  'required_if:notas,null',
          'expedientes.*.id_expediente' => 'required|exists:expediente,id_expediente',
          'notas' => 'required_if:expedientes,null',
          'notas.*.id_nota' => 'required|exists:nota,id_nota',
          'juegos' => 'array|required',
          'juegos.*.id_juego' => 'required|exists:juego,id_juego',
          'juegos.*.activo' => 'in:1,0',
          'formula.id_formula' => 'required|exists:formula,id_formula',
          'gli_hard' => 'nullable',
          'gli_hard.*.id_gli_hard' => 'nullable|integer',
          'gli_hard.*.nro_certificado' => 'nullable|alpha_dash',
          'gli_hard.*.file' => 'sometimes|mimes:pdf'
      ],array(),self::$atributos)->after(function($validator){
        if(!$validator->errors()->any()){
          $data = $validator->getData();
          $user = UsuarioController::getInstancia()->quienSoy()['usuario'];
          $usuario_casinos = DB::table('usuario_tiene_casino')->where('id_usuario',$user->id_usuario);

          $isla = Isla::find($data['id_isla']);
          $acceso_isla = (clone $usuario_casinos)->where('id_casino',$isla->id_casino)->count();
          if($acceso_isla == 0) $validator->errors()->add('id_isla', 'El usuario no puede acceder a esa isla.');

          $MTM = Maquina::find($data['id_maquina']);
          $acceso_MTM = (clone $usuario_casinos)->where('id_casino',$MTM->id_casino)->count();
          if($acceso_MTM == 0) $validator->errors()->add('id_maquina', 'El usuario no puede acceder a esa maquina.');

          if($isla->id_casino != $MTM->id_casino) $validator->errors()->add('id_casino','Mismatch de los casinos entre la maquina y la isla.');

          $acceso_unidad_medida = DB::table('casino_tiene_unidad_medida')
          ->where('id_casino',$MTM->id_casino)
          ->where('id_unidad_medida',$data['id_unidad_medida'])->count();

          if($acceso_unidad_medida == 0) $validator->errors()->add('id_unidad_medida','La maquina no tiene acceso a esa unidad de medida.');

          foreach($data['juegos'] as $j){
            $juego = Juego::find($j['id_juego']);
            $acceso_juego = $juego->casinos()->where('casino_tiene_juego.id_casino',$MTM->id_casino)->count();
            if($acceso_juego == 0) $validator->errors()->add('id_juego','No puede acceder al juego '.$juego->nombre_juego);
          }
          if(isset($data['expedientes'])){
            foreach($data['expedientes'] as $e){
              $exp = Expediente::find($e['id_expediente']);
              $acceso_expediente = $exp->casinos()->where('expediente_tiene_casino.id_casino',$MTM->id_casino)->count();
              if($acceso_expediente == 0) 
                $validator->errors()->add('id_expediente','No puede acceder al expediente '.$exp->concatenacion);
            }
          }
          if(isset($data['notas'])){
            foreach($data['notas'] as $n){
              $nota = Nota::find($n['id_nota']);
              $acceso_nota = $nota->casino->id_casino != $MTM->id_casino;
              if($acceso_nota == 0) $validator->errors()->add('id_nota','No puede acceder a la nota '.$nota->id_nota);
            }
          }
          $duplicados = Maquina::where([['id_casino' ,'=', $MTM->id_casino] ,
          ['nro_admin' ,'=', $data['nro_admin']]])
          ->whereNull('deleted_at')
          ->where('id_maquina','<>',$MTM->id_maquina)
          ->get()->count();
          if($duplicados>0){
            $validator->errors()->add('nro_admin','El número de administración ya está tomado.');
          }
        }
      })->validate();

    $formula  = Formula::find($request->formula['id_formula']);
    $isla     = Isla::find($request->id_isla);
    $MTM      = Maquina::find($request->id_maquina);
    $gli_hard = null;
    if($request->gli_hard['id_gli_hard'] > 0){
      $gli_hard = GliHard::find($request->gli_hard['id_gli_hard']);
    } 

    DB::beginTransaction();
    try {
        if ($request->gli_hard['id_gli_hard'] === 0) {//Triple igual para que no de igual a NULL
            $gli_hard = GliHardController::getInstancia()
                ->guardarNuevoGliHard($request->gli_hard['nro_certificado'], null, $request->gli_hard['file']);
        }
        //se comprueba si es null, esto es un problema arrastrado en el alta, nunca deberia ser null pero la comprobacion no estaria de mas
        //si es null es estado anterior, se le asigna el nuevo , sino se evalua el cambio para asignar razon
        $razon = "La maquina sufrió modificaciones: "; //razon del cambio, que se guardara en el log de máquinas
        $tipo_movimiento = null;
        if (is_null($MTM->id_estado_maquina)) {
            $MTM->id_estado_maquina = $request->id_estado_maquina;
        } elseif ($MTM->id_estado_maquina != $request->id_estado_maquina) {
            $razon .= "Cambió el estado. ";
            switch ($request->id_estado_maquina) {
                case 1:
                    $tipo_movimiento = 1;
                    break;
                case 2:
                    $tipo_movimiento = 3;
                    break;
                case 3:
                    $tipo_movimiento = 2;
                    break;
                case 4:
                    $tipo_movimiento = 2;
                    break;
                case 5:
                    $tipo_movimiento = 2;
                    break;
                default:
                    break;
            }
        }

        if ($MTM->denominacion != $request->denominacion) {
            $tipo_movimiento = 5;
            $razon .= "Cambió la denominacion. ";
        }
        if ($MTM->id_isla != $isla->id_isla) {
            $tipo_movimiento = 4;
            $razon .= "Cambió la isla. ";
        }
        $juego_activo_nuevo = $request->juegos[0];
        $juego_activo_viejo = $MTM->juego_activo;
        $MTM->juegos()->detach();
        foreach ($request->juegos as $unJuego) {
            $juego = Juego::find($unJuego['id_juego']);
            if ($unJuego['activo'] == 1) { // asociar juego activo
                $juego_activo_nuevo = $juego;
                $MTM->juego_activo()->associate($juego->id_juego);
            }
            $MTM->juegos()->syncWithoutDetaching(
                [$juego->id_juego =>
                    ['denominacion' => $unJuego['denominacion'], 'porcentaje_devolucion' => $unJuego['porcentaje_devolucion'], 'id_pack' => null],
                ]
            );
        }
        if ($juego_activo_viejo->id_juego != $juego_activo_nuevo->id_juego) {
            $tipo_movimiento = 7;
            $razon .= "Cambió el juego. ";
        }
        if($request->generar_marca_juego){
          $MTM->marca_juego = $this->marcaJuego($request->marca,$juego_activo_nuevo->nombre_juego,$MTM->id_casino);
        }
        else{
          $MTM->marca_juego = $request->marca_juego;
        }
        $MTM->id_juego = $juego_activo_nuevo->id_juego;
        $MTM->nro_admin = $request->nro_admin;
        $MTM->marca = $request->marca;
        $MTM->modelo = $request->modelo;
        $MTM->id_unidad_medida = $request->id_unidad_medida;
        $MTM->id_tipo_moneda = $request->id_tipo_moneda;
        $MTM->nro_serie = $request->nro_serie;
        $MTM->mac = $request->mac;
        $MTM->formula()->associate($formula);
        $MTM->denominacion = $request->denominacion;
        $MTM->juega_progresivo = $MTM->progresivos()->count() > 0;
        $MTM->id_isla = $isla->id_isla;
        $MTM->id_casino = $isla->id_casino;
        $MTM->id_gli_hard = is_null($gli_hard) ? null : $gli_hard->id_gli_hard;
        $MTM->save();
        if (!empty($request->id_tipo_gabinete)) {
            $MTM->tipoGabinete()->associate($request->id_tipo_gabinete);
        }
        else $MTM->tipoGabinete()->dissociate();
        if (!empty($request->id_tipo_maquina)) {
            $MTM->tipoMaquina()->associate($request->id_tipo_maquina);
        }
        else $MTM->tipoMaquina()->dissociate();

        $MTM->estado_maquina()->associate($request->id_estado_maquina);

        if (!empty($request->expedientes)) {
            $MTM->expedientes()->sync($request->expedientes);
        }
        else $MTM->expedientes()->sync([]);
        if (!empty($request->notas)) {
            $MTM->notas()->sync($request->notas);
        }
        else $MTM->notas()->sync([]);
        LogMaquinaController::getInstancia()->registrarMovimiento($MTM->id_maquina, $razon, $tipo_movimiento);
        $MTM->save();
        DB::commit();
    } catch (Exception $e) {
        DB::rollBack();
    }
    return ['maquina' => $MTM];
  }

  public function marcaJuego($marca,$nombre_juego,$id_casino=-1){
    $marca_abr = $this->abreviarMarca($marca,$id_casino);
    return trim($marca_abr . ' - ' . $nombre_juego);  
  }

  //ELIMINA LOGICAMENTE LA MAQUINA. deleted_at != null
  public function eliminarMTM($id){
    $MTM=Maquina::find($id);
    if(is_null($MTM)) return ['MTM'=> $MTM ];
    $MTM->formula()->dissociate();
    $MTM->gliSoftOld()->dissociate();
    $MTM->gliHard()->dissociate();
    $MTM->estado_maquina()->associate(3);
    $razon = "La maquina se eliminó definitivamente.";
    LogMaquinaController::getInstancia()->registrarMovimiento($MTM->id_maquina, $razon,3);//tipo mov EGRESO
    $MTM->delete(); //SE MARCA COMO ELIMINANDO

    return ['MTM'=> $MTM ];
  }

  public function buscarMaquinaPorNumeroMarcaYModelo($id_casino = 0, $busqueda){
    $usuario = UsuarioController::getInstancia()->buscarUsuario(session('id_usuario'))['usuario'];
    $casinos = array();
    foreach($usuario->casinos as $c){
      $casinos[]=$c->id_casino;
    }
    $reglas = [['maquina.nro_admin','like', $busqueda.'%']];
    if($id_casino != 0){
      $reglas[] = ['maquina.id_casino' , '=' , $id_casino];
    }

    $resultados = DB::table('maquina')
    ->select('maquina.id_maquina','maquina.nro_admin','maquina.marca','maquina.modelo')
    ->where($reglas)
    ->whereIn('maquina.id_casino' , $casinos)
    ->whereNull('maquina.deleted_at')
    ->get();

    return ['resultados' => $resultados];
  }

  public function validarMaquinaTemporal($maquinaTemp,$id_casino){
      Validator::make([
          'nro_admin' => $maquinaTemp->nro_admin,
           'marca' =>  $maquinaTemp->marca,
           'modelo' =>  $maquinaTemp->modelo,
           'id_casino' =>  $id_casino,
           'nro_isla' => $maquinaTemp->nro_isla,
           'denominacion' => $maquinaTemp->denominacion,
        ] ,
         [
           'nro_admin' => 'required|integer|max:999999',
           'marca'=> 'required|max:45',
           'modelo' => 'required|max:60',
           'nro_isla' => 'required|integer',
           'denominacion' => 'required|numeric',
           'id_casino' => ['required' , 'integer' ,Rule::exists('usuario_tiene_casino')->where(function($query){$query->where('id_usuario', session('id_usuario'));})]

        ] , array(), self::$atributos)->after(function ($validator){
          //deberia validar que no se repita entre SF, MEL  y RO

          //SI EL CASINO ES SANTA FE O MELINCUE ME FIJO QUE NO SE REPITAN LOS NRO DE ADMIN
          if($validator->getData()['id_casino'] == 1  || $validator->getData()['id_casino'] == 2 ){
            $res=DB::table('isla')
              ->where('isla.id_casino' , '=', 1)
              ->orWhere('isla.id_casino' , '=', 2)
              ->join('maquina' , 'maquina.id_isla' , '=' , 'isla.id_isla')
              ->where('maquina.nro_admin', '=', $validator->getData()['nro_admin'])
              ->count();
          }
          //SI EL CASINO ES ROSAIRO ME FIJO QUE NO SE REPITA SOLO EN RO
          if($validator->getData()['id_casino'] == 3 ){
            $res=DB::table('isla')
              ->where('isla.id_casino' , '=', $validator->getData()['id_casino'])
              ->join('maquina' , 'maquina.id_isla' , '=' , 'isla.id_isla')
              ->where('maquina.nro_admin', '=', $validator->getData()['nro_admin'])
              ->count();
          }
            if ($res > 0) {
                 $validator->errors()->add('nro_admin','El número de adminstación ya está tomado');
             }
          })->validate();
          return true;

  }

  public function asociarIsla($id_maquina,$id_isla){
    $maquina = Maquina::find($id_maquina);
    $maquina->isla()->associate($id_isla);
    $maquina->save();
    $razon = "La maquina se agregó a la isla nro ".  $maquina->isla->nro_isla ."." ;
    LogMaquinaController::getInstancia()->registrarMovimiento($id_maquina, $razon,4);//tipo movimiento cambio layout
  }

  public function desasociarIsla($id_maquina,$id_isla){
    $maquina=Maquina::find($id_maquina);
    $isla = Isla::find($id_isla);
    $maquina->isla()->dissociate($id_isla);
    $maquina->save();
    $razon = "La maquina no pertence más a la isla " .  $isla->nro_isla."." ;
    LogMaquinaController::getInstancia()->registrarMovimiento($id_maquina, $razon,4);//tipo movimiento cambio layout
  }
  public function abreviarMarca($marca,$id_casino){
    $marca = str_replace(' ' ,'',strtoupper($marca));
    // En el codigo viejo hacia un switch gigante por cada marca, lo cambio a un simple algoritmo
    // Habia un par de casos especiales que nunca se tocaban
    // (agregar aca si hay que poner casos especiales)
    $marcas_especiales = [
      1 => [],
      2 => [],
      3 => ['WILLIAMS' => 'WILLIAMS']
    ];
    if(array_key_exists($id_casino,$marcas_especiales) 
    && array_key_exists($marca,$marcas_especiales[$id_casino])){
      return $marcas_especiales[$id_casino][$marca];
    }
    $limit = min(3,strlen($marca));
    return substr($marca,0,$limit);
  }

  public function buscarMaquinasPorExpediente($id_expediente){
    $maquinas=DB::table('maquina')
    ->select('maquina.*')
    ->join('maquina_tiene_expediente', 'maquina_tiene_expediente.id_maquina', '=', 'maquina.id_maquina')
    ->join('expediente', 'expediente.id_expediente', '=', 'maquina_tiene_expediente.id_expediente')
    ->where('maquina_tiene_expediente.id_expediente', '=', $id_expediente)
    ->get();

    return $maquinas;
  }

  public function modificarDenominacionYUnidad($id_unidad_medida,$denominacion,$id_maquina){
    $maquina = Maquina::find($id_maquina);
    $maquina->denominacion = $denominacion;
    $maquina->id_unidad_medida = $id_unidad_medida;
    $maquina->save();
    $razon = "Se cambió la unidad medida.";
    LogMaquinaController::getInstancia()->registrarMovimiento($id_maquina, $razon,5);//tipo mov denominacion
    return $maquina;
  }

  // modificarDenominacionJuego cambia la denominacion del juego activo de la mtm
  public function modificarDenominacionJuego( $denominacion, $id_maquina){
    $m = Maquina::find($id_maquina);
    $id_juego_activo=$m->juego_activo->id_juego;
    DB:: table('maquina_tiene_juego')
      ->Where([ ['id_maquina','=',$id_maquina],['id_juego','=',$id_juego_activo] ])
      ->Update(['denominacion' => $denominacion]);

    $razon = "Se cambió denominacio al juego activo";
    LogMaquinaController::getInstancia()->registrarMovimiento($id_maquina, $razon,5);//tipo mov denominacion
    return $m;
  }

  public function buscarMaquinasPorNota($id_nota){
    $maquinas=DB::table('maquina')
    ->select('maquina.*')
    ->join('maquina_tiene_nota', 'maquina_tiene_nota.id_maquina', '=', 'maquina.id_maquina')
    ->join('nota', 'nota.id_nota', '=', 'maquina_tiene_nota.id_nota')
    ->where('maquina_tiene_nota.id_nota', '=', $id_nota)
    ->get();

    return $maquinas;
  }

  public function buscarMaquinasPorNotaYLogMovimiento($id_nota, $id_logMov){
    $maquinas=DB::table('maquina')
    ->select('maquina.*')
    ->join('maquina_tiene_nota', 'maquina_tiene_nota.id_maquina', '=', 'maquina.id_maquina')
    ->join('nota', 'nota.id_nota', '=', 'maquina_tiene_nota.id_nota')
    ->where('maquina_tiene_nota.id_nota', '=', $id_nota, 'nota.id_log_movimiento', '=', $id_logMov)
    ->get();

    return $maquinas;
  }

  private function tieneNuevoExpediente($id_maquina, $expedientes){
    $maq= Maquina::find($id_maquina);
    $expedientesMaq = $maq->expedientes();
    $result = false;
      foreach ($expedientes as $exp) {
        if(!$this->existeExpediente($exp, $expedientesMaq )){
          $result = true;
          break;
        }
      }
      return $result;
    }

  private function existeExpediente($exp ,$expedientes){
      $result = false;
      for($i = 0;$i<count($expedientes);$i++){
        if($exp['id_expediente'] == $expedientes[$i]->id_expediente){
          $result = true;
          break;
        }
      }
      return $result;
  }

  private function tieneNuevaNota($id_maquina, $notas){
      $maq= Maquina::find($id_maquina);
      $notasMaq = $maq->notas();
      $result = false;
        foreach ($notas as $nota) {
          if(!$this->existeNota($nota, $notasMaq )){
            $result = true;
            break;
          }
        }
        return $result;
  }

  private function existeNota($nota, $notasMaq){
      $result=false;
      for($i = 0;$i<count($notasMaq);$i++){
        if($nota['id_nota'] == $notasMaq[$i]->id_nota){
           $result = true;
          break;
        }
      }
      return $result;
  }

  //busca marcas que comience con el string que escribe el usuario
  public function buscarMarcas($marca){
    if($marca != ''){
      $marcas = DB::table('maquina')->select(DB::raw('DISTINCT(marca), '. 0 .' AS id_marca'))->where('marca' , 'like',  $marca . '%')->get();
    }else {
      $marcas = DB::table('maquina')->select(DB::raw('DISTINCT(marca)'))->get();
    }

    return ['marcas' => $marcas];
  }

  public function asociarMTMsEventualidad($id_maquina){
      $maq=Maquina::find($id_maquina);
      $razon = "La máquina fue afectada por una eventualidad.";
      LogMaquinaController::getInstancia()->registrarMovimiento($id_maquina, $razon,null);
      $maq->estado_maquina()->associate(7);//Eventualidad observada
      $maq->save();
  }

  public function modificarDevolucion($porcentaje_devolucion,$id_maquina){
    $maq=Maquina::find($id_maquina);
    $maq->porcentaje_devolucion = $porcentaje_devolucion;
    $razon = "Se modificó devolucion";
    LogMaquinaController::getInstancia()->registrarMovimiento($id_maquina, $razon,null);
    $maq->save();
  }

  // modificarDevolucionJuego cambia el porcentaje de devolucion del juego activo asociado a la mtm
  public function modificarDevolucionJuego($porcentaje_devolucion,$id_maquina){
    $m = Maquina::find($id_maquina);
    $id_juego_activo=$m->juego_activo->id_juego;
    DB:: table('maquina_tiene_juego')
      ->Where([ ['id_maquina','=',$id_maquina],['id_juego','=',$id_juego_activo] ])
      ->Update(['porcentaje_devolucion' => $porcentaje_devolucion]);
    $razon = "Se modificó el procentaje de devolución del juego activo";
    LogMaquinaController::getInstancia()->registrarMovimiento($id_maquina, $razon,null);

    return $m;
  }

  public function modificarJuego($id_juego,$id_maquina){
    $maq=Maquina::find($id_maquina);
    $maq->juego_activo()->associate($id_juego);
    $maq->save();
    $razon = "Se modificó juego activo";
    LogMaquinaController::getInstancia()->registrarMovimiento($id_maquina, $razon,null);

  }

  // modificarJuegoConDenYPorc utilidad para movimiento, realiza el camibo de juego con denominacion y porcentaje de devolucion
  // estos cambios se aplican al juego activo y a sus relaciones
  public function modificarJuegoConDenYPorc($id_juego,$id_maquina, $denominacion, $porcentaje_devolucion){
    $maq=Maquina::find($id_maquina);
    $maq->juego_activo()->associate($id_juego);
    $maq->save();
    $maq->juegos()->syncWithoutDetaching([$id_juego => ['denominacion' => $denominacion, 'porcentaje_devolucion' => $porcentaje_devolucion]]);
    $razon = "Se modificó juego activo con su denominación y porcentaje de devolución";
    LogMaquinaController::getInstancia()->registrarMovimiento($id_maquina, $razon,null);

  }

  public function modificarJuegoMovimiento($id_juego,$id_maquina,$porcentaje_devolucion,$id_unidad_medida,$denominacion){
    $maq=Maquina::find($id_maquina);
    $maq->denominacion = $denominacion;
    $maq->id_unidad_medida = $id_unidad_medida;
    $maq->porcentaje_devolucion = $porcentaje_devolucion;
    $maq->juego_activo()->associate($id_juego);
    if($this->tieneElJuego($id_juego,$maq)){
      if($porcentaje_devolucion != null){
        $maq->juegos()->attach($id_juego,$denominacion,$porcentaje_devolucion );
      }else{
        $maq->juegos()->attach($id_juego);
      }
    }else{
      $maq->juegos()->detach($id_juego);
      if($porcentaje_devolucion != null){
        $maq->juegos()->attach($id_juego,$denominacion,$porcentaje_devolucion );
      }else{
        $maq->juegos()->attach($id_juego);
      }
    }

    $maq->save();
    $razon = "Se modificó juego activo";
    LogMaquinaController::getInstancia()->registrarMovimiento($id_maquina, $razon,null);

  }

  private function tieneElJuego($id_juego,$maq){
    foreach ($maq->juegos as $juego) {
      if($juego->id_juego == $id_juego){
        return true;
      }
    }
    return false;
  }

  public function buscarMaquinasPorLogMovimiento($id_log){
    $maquinas = DB::table('maquina')
                    ->select('maquina.id_maquina','maquina.nro_admin','maquina.marca','maquina.modelo','maquina.id_juego','maquina.denominacion','maquina.porcentaje_devolucion','maquina.id_unidad_medida')//,'juego.id_juego','juego.nombre_juego'
                    ->join('relevamiento_movimiento', 'relevamiento_movimiento.id_maquina','=','maquina.id_maquina')
                    // ->join('gli_soft', 'gli_soft.id_gli_soft','=','maquina.id_gli_soft')
                    ->where('relevamiento_movimiento.id_log_movimiento','=', $id_log)
                    ->whereNull('relevamiento_movimiento.id_fiscalizacion_movimiento')
                    ->get();

    return $maquinas;
  }

  public function asociarExpediente($id_mtm, $id_expediente){
    $mtm = Maquina::find($id_mtm);
    $mtm->expedientes()->attach($id_expediente);//_NO_ se borra el resto (puede borrarlo desde el gestor de maquinas)
    $mtm->save();
  }

  public function disasociarExpediente($id_mtm, $id_expediente){
    $mtm = Maquina::find($id_mtm);
    $mtm->expedientes()->detach($id_expediente);
    $mtm->save();
  }
  
  public function getCasinos($nro_admin){
    $maquinas = Maquina::where('nro_admin',$nro_admin)->get() ?? [];
    $casinos  = [];
    foreach($maquinas as $m) $casinos[]=$m->id_casino;
    return $casinos;
  }

  public function getMoneda($id_casino,$nro_admin){//@param: nro_admin de maquina
    $maquina = Maquina::where([['id_casino',intval($id_casino)], ['nro_admin',intval($nro_admin)]])->first();
    if(!is_null($maquina)) return $maquina->tipoMoneda;
    return null;
  }

  public function transaccionEstadoMasivo(Request $request){
    Validator::make($request->all(), [
      'maquinas' => 'nullable|array',
      'maquinas.*.id_maquina' => 'required|exists:maquina,id_maquina',
      'maquinas.*.id_estado_maquina' => 'required|exists:estado_maquina,id_estado_maquina'
    ],array(),self::$atributos)->after(function($validator){
      if(!$validator->errors()->any()){
        $data = $validator->getData();
        $user = UsuarioController::getInstancia()->quienSoy()['usuario'];
        $usuario_casinos = DB::table('usuario_tiene_casino')->where('id_usuario',$user->id_usuario);
        foreach($data['maquinas'] as $m){
          $MTM = Maquina::find($m['id_maquina']);
          $acceso_MTM = (clone $usuario_casinos)->where('id_casino',$MTM->id_casino)->count();
          if($acceso_MTM == 0) $validator->errors()->add('id_maquina', 'El usuario no puede acceder a esa maquina.');
        }
      }
    })->validate();
    $LMI = LogMaquinaController::getInstancia();
    DB::transaction(function() use ($request,$LMI){
      foreach($request['maquinas'] as $m){
        $MTM = Maquina::find($m['id_maquina']);
        $MTM->estado_maquina()->associate($m['id_estado_maquina']);
        $MTM->save();
        $LMI->registrarMovimiento($m['id_maquina'],'Cambio a estado '.$MTM->estado_maquina->descripcion.' por transacción',9);
      }
    });
    return 1;
  }
}
