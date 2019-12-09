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
use App\TipoProgresivo;
use App\Casino;
use App\Isla;
use App\Juego;
use App\TipoMaquina;
use App\EstadoMaquina;
use App\GliSoft;
use App\GliHard;
use App\PackJuego;

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
    $tipo_progresivos = ['LINKEADO', 'INDIVIDUAL'];
    $estados = EstadoMaquina::all();

    UsuarioController::getInstancia()->agregarSeccionReciente('Máquinas' ,'maquinas');
    return view('seccionMaquinas', ['unidades_medida' => $unidad_medida,   'casinos' => $casinos, 'tipos' => $tipos , 'monedas' => $monedas , 'gabinetes' => $gabinetes , 'tipo_progresivos' => $tipo_progresivos, 'estados' => $estados]);
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
        //  $return_juego->pack= PackJuego::find($un_juego->pivot->id_pack); la gestion del pack fue extraida, si estas en el futuro, borrar esto
        //  if (count($return_juego->pack)<1){
        //    $pack_aux= new \stdClass();
        //    $pack_aux->identificador="";
        //    $pack_aux->id_pack=-1;
        //    $return_juego->pack=$pack_aux;
        //  }
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

      $unidades = DB::table('unidad_medida')->select('unidad_medida.*')->get();
      //$islaa = Isla::find($isla->id_isla);
      //$nro_isla = $islaa->nro_isla;
      return['maquina' => $mtm,
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

  public function obtenerMTMEnCasino($id_casino,$nro_admin){
      //dado un casino,devuelve maquinas que concuerden con el nro admin dado
      //usado para busqueda de maquinas
      if($id_casino == 0){
        $usuario = UsuarioController::getInstancia()->buscarUsuario(session('id_usuario'))['usuario'];
        $id_casino = $usuario->casinos[0]->id_casino;
        $maquinas  = Maquina::where([['maquina.id_casino' , '=' , $id_casino] ,['maquina.nro_admin' , 'like' , $nro_admin . '%']])->get();
        // foreach ($maquinas as $maquina) {
        //   $maquina->nro_admin = $maquina->nro_admin;
        // } lo comento, no le encuentro el sentido
        return ['maquinas' => $maquinas];
      }else{
        $maquinas  = Maquina::where([['maquina.id_casino' , '=' , $id_casino] ,['maquina.nro_admin' , 'like' , $nro_admin . '%']])->get();
        foreach ($maquinas as $maquina) {
          $maquina->nro_admin = $maquina->nro_admin;
        }
        return ['maquinas' => $maquinas];
      }

  }

  public function obtenerMTMEnCasinoMovimientos($id_casino,$id_mov,$nro_admin){
      //dado un casino y un log movimiento,devuelve maquinas que concuerden con el nro admin dado
      //usado para busqueda de maquinas, restando las maquinas que ya hayan sido
      //enviadas a fiscalizar
      $mtms_mov= DB::table('relevamiento_movimiento')
                      ->select('maquina.id_maquina')
                      ->join('maquina','maquina.id_maquina','=','relevamiento_movimiento.id_maquina')
                      ->where('maquina.nro_admin' , 'like' , $nro_admin . '%')
                      ->where('relevamiento_movimiento.id_log_movimiento','=',$id_mov)
                      ->get();

      $mtms = array();
      foreach ($mtms_mov as $mtm) {
        $mtms[]=$mtm->id_maquina;
      }


      $maquinas  = Maquina::where([['maquina.id_casino' , '=' , $id_casino] ,['maquina.nro_admin' , 'like' , $nro_admin . '%']])->whereNotIn('maquina.id_maquina',$mtms)->get();
      foreach ($maquinas as $maquina) {
        $maquina->nro_admin = $maquina->nro_admin;
      }
      return ['maquinas' => $maquinas];
  }
  //se usa para egreso y reingresos
  public function obtenerMTMMovimientos($id_casino,$tipo_movimiento,$id_log_mov, $nro_admin){
      //dado un casino,devuelve maquinas que concuerden con el nro admin dado
      //usado para busqueda de maquinas, segun el log movimiento y su tipo

      if($tipo_movimiento == 8){
        $maquinas = DB::table('relevamiento_movimiento')
                      ->select('maquina.*')
                      ->join('maquina','maquina.id_maquina','=', 'relevamiento_movimiento.id_maquina')
                      ->join('log_movimiento', 'log_movimiento.id_log_movimiento','=', 'relevamiento_movimiento.id_log_movimiento')
                      ->where('maquina.nro_admin','like',$nro_admin.'%')
                      ->where('relevamiento_movimiento.id_log_movimiento' , $id_log_mov)
                      ->groupBy('relevamiento_movimiento.id_maquina')
                      ->havingRaw('COUNT(relevamiento_movimiento.id_maquina) < 2') //para que traiga solo las que todavia NO furon relevadas por 2da vez
                      ->distinct('maquina.id_maquina')
                      ->get();
      }else{
        $maquinas  = Maquina::where([['maquina.id_casino' , '=' , $id_casino] ,['maquina.nro_admin' , 'like' , $nro_admin . '%']])->get();
        // foreach ($maquinas as $maquina) {
        //   $maquina->nro_admin = $maquina->nro_admin;
        // }
      }

      return ['maquinas' => $maquinas];
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
    $reglas[]=['maquina.deleted_at', '=' , null];
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
      $reglas[]=['maquina.nro_admin' , 'like' , '%' . $request->nro_admin . '%'];
    }
    if(isset($request->marca)){
      $reglas[]=['maquina.marca' , 'like' , '%' . $request->marca . '%'];
    }
    if(isset($request->nro_isla)){
      $reglas[]=['isla.nro_isla' , '=' , $request->nro_isla];
    }
    if($request->id_sector!=0){
      $reglas[]=['sector.id_sector' , '=' , $request->id_sector];
    }
    if(isset($request->denominacion)){
      $reglas[]=['maquina.denominacion' , '=' , $request->denominacion];
    }
    if(isset($request->nro_isla)){
      $reglas[]=['isla.nro_isla' , '=' , $request->nro_isla ];
    }
    if(isset($request->nombre_juego)){
      $reglas[]=['juego.nombre_juego' , 'like' , '%' . $request->nombre_juego . '%' ];
    }
    if(isset($request->id_tipo_moneda)){
      $reglas[]=['maquina.id_tipo_moneda','=',$request->id_tipo_moneda];
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
    $resultados=DB::table('maquina')
    ->select('maquina.*','juego.*','isla.*','sector.*','casino.*','estado_maquina.descripcion as estado_maquina')
    ->leftJoin('isla' , 'isla.id_isla','=','maquina.id_isla')
    ->leftJoin('casino' , 'maquina.id_casino','=','casino.id_casino')
    ->leftJoin('sector','sector.id_sector','=','isla.id_sector')
    ->leftJoin('estado_maquina','maquina.id_estado_maquina','=','estado_maquina.id_estado_maquina')
    ->leftJoin('juego','maquina.id_juego','=','juego.id_juego')
    ->when($sort_by,function($query) use ($sort_by){
                    return $query->orderBy($sort_by['columna'],$sort_by['orden']);
                })
    ->where($reglas)
    ->whereIn('maquina.id_casino',$casinos)
    ->whereIn('maquina.id_estado_maquina',$estados)
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
              $maquina->marca_juego = $this->abreviarMarca($maquina->marca) .' - '. $maquinaTemp->juego_activo->nombre_juego;
              $maquina->save();
              $razon = "La maquina se creo desde un archivo.";
              LogMaquinaController::getInstancia()->registrarMovimiento($id_maquina, $razon,1);//tipo mov ingreso (pero esta sin validar todavia)
        }//fin foreach

      }//fin if

  }

  public function guardarMaquina(Request $request){

    Validator::make($request->all(), [
          //nro admin es unico por casino, aunque entre SF y ME no se repiten.
          //['required','alpha_dash',Rule::unique('maquina')->where(function($query){$query->where('id_casino',$request->id_casino);})]
          //CHECKEAR CORRESPONDENCIA ISLA->PROGRESIVO
          //CHECKEAR CORRESPONDENCIA JUEGO-> PROGRESIVO. EN LO POSIBLE EN LA VISTA ADEMAS DE ACA TAMBIEN

          'id_log_movimiento' => 'required|exists:log_movimiento,id_log_movimiento',
          'nro_admin' => 'required|integer',
          'marca'=> 'required|max:45',
          'modelo' => 'required|max:60',
          'mac' => 'nullable|max:100',
          'id_unidad_medida' => 'required|in:1,2',
          'nro_serie'=>  'nullable|alpha_dash',
          'marca_juego' => 'nullable|max:100',
          'juega_progresivo' => 'required|boolean',
          'id_tipo_gabinete'=> 'nullable',
          'id_tipo_maquina' => 'nullable',
          //'porcentaje_devolucion' => ['required','regex:/^\d\d?([,|.]\d\d?\d?)?$/'],
          'denominacion' => ['required','regex:/^\d\d?\d?\d?\d?\d?\d?\d?([,|.]\d\d?)?$/'],
          'id_estado_maquina' => 'required|exists:estado_maquina,id_estado_maquina',
          'expedientes' => 'nullable',//'required_if:notas,null',
          'expedientes.*.id_expediente' => 'required|exists:expediente,id_expediente',
          'id_casino' => ['required', Rule::exists('usuario_tiene_casino')->where(function($query){$query->where('id_usuario', session('id_usuario'));})],
          'id_sector' => 'required|exists:sector,id_sector',
          'nro_isla' => 'required',
          'codigo' => 'nullable', //codigo sub isla
          'maquinas' => 'nullable',
          'maquinas.*' => 'exists:maquina,id_maquina',
          'notas' => 'nullable',
          'notas' => 'required_if:expedientes,null',
          'notas.*.id_nota' => 'required|exists:nota,id_nota',
          'juego' => 'array|required',
          'juego.*.id_juego' => 'required', //validar que es id valido
          'juego.*.nombre_juego' => 'required',
          'juego.*.activo' => 'in:1,0',
          'juego.*.cod_identificacion' => ['nullable','regex:/^\d(.|-|_|\d)*$/','unique:juego,cod_identificacion','max:100'],
          'juego.*.tabla.*.id_tabla' => 'nullable',
          'juego.*.tabla.*.nombre_tabla' => 'required',
          'progresivo.id_progresivo' => 'nullable',
          'progresivo.id_tipo_progresivo' => 'nullable',
          'progresivo.nombre_progresivo' => 'nullable',
          'gli_soft' => 'nullable',
          'gli_hard.id_gli_hard' => 'nullable',
          'gli_hard.nro_certificado' => 'required_if:gli_hard.id_gli_hard,0|max:45',
          'gli_hard.file' => 'nullable',
          'formula.id_formula' => 'required',
          'formula.cuerpoFormula' => 'required',
          //table,column,except,idColumn
          //expediente,nro_exp_interno,'.$request->id_expediente.',id_expediente'
          //'gli_hards.*.id_gli_hard' => 'required|integer|exists:gli_hard,id_gli_hard|distinct',
      ],array(),self::$atributos)->after(function($validator){
        $id_casino = $validator->getData()['id_casino'];
        // validacion nro admin. Entre SF y ME no existen duplicados
        // if($id_casino == 3){
        //   $duplicados = Maquina::where([['id_casino' , $id_casino] , ['nro_admin' , $validator->getData()['nro_admin']]])->get();
        // }else{
        //  $duplicados = Maquina::where('nro_admin', $validator->getData()['nro_admin'])->whereIn('id_casino' , [1,2])->get(); // 1 -> Melincue, 2 -> Santa Fe
        // }

        $duplicados = Maquina::where([['id_casino' ,'=', $id_casino] ,
                                      ['nro_admin' ,'=', $validator->getData()['nro_admin']]])
                                      ->whereNull('deleted_at')
                                      ->get();


        if($duplicados->count() >= 1){
          $validator->errors()->add('nro_admin', 'El número de administración ya está tomado.');
        }

        //validacion datos de isla

        $reglas = array();
        $reglas[] = ['id_casino' , '=' , $validator->getData()['id_casino']];
        $reglas[] = ['nro_isla' , '=' , $validator->getData()['nro_isla']];
        if($validator->getData()['codigo'] != ''){
          $reglas[] = ['codigo' , '=' , $validator->getData()['codigo']];
        }

        if($validator->getData()['id_isla'] != 0){
          $reglas[] = ['id_isla' , '<>' , $validator->getData()['id_isla']];
        }

        $isla_db = Isla::where($reglas)->get();

        if($isla_db->count() > 1){
          $validator->errors()->add('nro_isla', 'Se encontró un error con la base de datos.(Más de una isla con mismo nro)');
        }

      })->sometimes('gli_hard.file','mimes:pdf',function($input){
      return $input['gli_hard.file'] != null;
      })->sometimes('gli_hard.id_gli_hard','exists:gli_hard,id_gli_hard',function($input){
      return $input['gli_hard.id_gli_hard'] != null && $input['gli_hard.id_gli_hard'] != 0;
    })->validate();

    //SI EXISTE FORMULA LA BUSCA SI NO CREA
    $formula=null;
    switch ($request->formula['id_formula']){
      case 'undefined':
        $formula=FormulaController::getInstancia()->guardarFormulaConcatenada($request->formula['cuerpoFormula']);
        break;
      case '':
        $formula=FormulaController::getInstancia()->guardarFormulaConcatenada($request->formula['cuerpoFormula']);
      break;
      case null:
        $formula=FormulaController::getInstancia()->guardarFormulaConcatenada($request->formula['cuerpoFormula']);
      break;
      default:
        $formula=Formula::find($request->formula['id_formula']);
        break;
    }

    //el admin tiene la opcion de gestionar islas -> pedido por rosario.
    //unaIsla es nueva isla
    if($request->id_isla == 0){ //si estoy creando una isla, valido
      //si estoy creando y pasó la validacion,
      $unaIsla= new Isla();
      $unaIsla->nro_isla = $request->nro_isla;
      $unaIsla->codigo = $request->codigo;
      $unaIsla->id_casino = $request->id_casino;
      $unaIsla->id_sector = $request->id_sector;
      IslaController::getInstancia()->saveIsla($unaIsla, $request->maquinas);

    }else if( $request->modificado == 1){
      // $unaIsla= new Isla();
      // $unaIsla->nro_isla = $request->nro_isla;
      // $unaIsla->codigo = $request->codigo;
      // $unaIsla->id_sector = $request->id_sector;
      // $unaIsla->id_casino = $request->id_casino;
      // //modifica isla creada
      // $unaIsla = IslaController::getInstancia()->modifyIsla($unaIsla, $request->id_isla, $request->maquinas);
      $unaIsla= Isla::find($request->id_isla);
    }else {//estoy usando una isla ya creada (sin modificar)

      $unaIsla= Isla::find($request->id_isla);
    }

    //SI EXISTE GLISOFT LA BUSCA SI NO CREA
    switch ($request->gli_soft['id_gli_soft']){
      case 'undefined':break;
      case '':break;
      case 0:
        $gli_soft=GliSoftController::getInstancia()->guardarGliSoft_gestionarMaquina($request->gli_soft['nro_certificado'],$request->gli_soft['observaciones'],$request->gli_soft['file']);
        break;
      default:
        $gli_soft=GliSoft::find($request->gli_soft['id_gli_soft']);
        break;
    }

    //GLIHARD:      SI EXISTE GLIHARD BUSCA, SI NO CREA
    $gli_hard = null;
    switch ($request->gli_hard['id_gli_hard']){
      case 'undefined':break;
      case '':break;
      case null: break;
      case "null": break;
      case 0:
        $gli_hard=GliHardController::getInstancia()
        ->guardarNuevoGliHard($request->gli_hard['nro_certificado'],null,$request->gli_hard['file']);
        break;
      default:
        $gli_hard=GliHard::find($request->gli_hard['id_gli_hard']);
        break;
    }

    $i=0;

    $MTM= new Maquina;

    $juegos= array(); //arreglo con todos los juegos,

    $juegos_finales=array();
    //POR CADA JUEGO, SI NO EXISTE CREO, SINO busco. ACTIVO se termina asociando
    foreach ($request['juego'] as $unJuego){
        if($unJuego['id_juego']==0){// 0 es juego nuevo

          $juego=JuegoController::getInstancia()->guardarJuego_gestionarMAquina($unJuego['nombre_juego'],$unJuego['tabla']);
          if($unJuego['activo']==1){
              $juegoActivo=$juego;
              $MTM->juego_activo()->associate($juego->id_juego);
          }
          $juegos_finales[] = ($juego->id_juego);
        }else{
          if($unJuego['id_juego']>=1){
            $juego=Juego::find($unJuego['id_juego']);
            if($unJuego['activo']==1){//usuario viejo y activo
              $juegoActivo=$juego;
              $MTM->juego_activo()->associate($juego->id_juego);
            }

            $id_pack_juego=null;

            $juegos_finales[ $juego->id_juego] = ['denominacion' => $unJuego['denominacion'], 'porcentaje_devolucion' => $unJuego['porcentaje_devolucion'],'id_pack' => $id_pack_juego];
          }
        }
        if(isset($gli_soft)){
          $juego->agregarGliSofts([$gli_soft],false);
          $juego->save();
        }
    }

    if(isset($gli_soft)){
      $MTM->gliSoftOld()->associate($gli_soft->id_gli_soft);
    }
    if(isset($gli_hard)){
      $MTM->gliHard()->associate($gli_hard->id_gli_hard);
    }

    $MTM->nro_admin = $request->nro_admin;
    $MTM->marca = $request->marca;
    $MTM->marca_juego = $request->marca_juego;
    $MTM->modelo = $request->modelo;
    $MTM->desc_marca = $request->desc_marca;
    $MTM->id_unidad_medida = $request->id_unidad_medida;
    $MTM->nro_serie = $request->nro_serie;
    $MTM->mac = $request->mac;
    $MTM->denominacion = $request->denominacion;
    $MTM->juega_progresivo = $request->juega_progresivo;
    $MTM->id_isla=$unaIsla->id_isla;
    $MTM->id_juego=$juegoActivo->id_juego;
    $MTM->id_tipo_moneda = $request->id_tipo_moneda;
    //$MTM->porcentaje_devolucion=$request->porcentaje_devolucion;
    $MTM->id_casino = $request->id_casino;
    $MTM->save();
    $MTM->formula()->associate($formula);
    $MTM->estado_maquina()->associate(6);//Estado = Inhabilitada -->viene a ser un estado PENDIENTE DE HABILITACION
    if($request->id_tipo_gabinete != 0)$MTM->tipoGabinete()->associate($request->id_tipo_gabinete);
    if($request->id_tipo_maquina != 0) $MTM->tipoMaquina()->associate($request->id_tipo_maquina);

    $MTM->juegos()->sync($juegos_finales);


    //SI EXISTE EL PROGRESIVO BUSCO SI NO, CREO
    switch ($request->id_progresivo) {
      case 'undefined':break;
      case '':break;
      case -1:break;
      case 0:
        $progresivo= ProgresivoController::getInstancia()->guardarProgresivo_gestionarMaquina($request->nombre_progresivo,$request->id_tipo, $request->progresivo['niveles'] , $MTM);
        break;
      default:
        $progresivo= ProgresivoController::getInstancia()->modificarNivelesProgresivos_gestionarMaquina($request->id_progresivo,$request->nombre_progresivo,$request->id_tipo, $request->progresivo['niveles'] , $MTM);
        break;
    }

    if(!empty($request->expedientes)){
      $MTM->expedientes()->sync($request->expedientes);
    }

    if(!empty($request->notas)){
      $MTM->notas()->sync($request->notas);
    }

    $razon = "La maquina se creo manualmente.";

    LogMaquinaController::getInstancia()->registrarMovimiento($MTM->id_maquina, $razon,1);//tipo mov ingreso (pero esta sin validar todavia)

    if($request->marca_juego == ""){
        $MTM->marca_juego = $this->abreviarMarca($MTM->marca) . ' - ' . $juegoActivo->nombre_juego;
    }else{
      $MTM->marca_juego =$request->marca_juego;
    }

    $MTM->save();

    $cantidad = LogMovimientoController::getInstancia()->guardarRelevamientoMovimientoIngreso($request['id_log_movimiento'],$MTM->id_maquina);
     //para el log de movimiento buscar maquinas restantes

    return ['maquina' => $MTM , 'cantidad' =>$cantidad ];
  }

  public function modificarMaquina(Request $request){
    Validator::make($request->all(), [
          //nro admin es unico por casino, auqnue entre SF y ME no se repiten.
          //['required','alpha_dash',Rule::unique('maquina')->where(function($query){$query->where('id_casino',$request->id_casino);})]
          //CHECKEAR CORRESPONDENCIA ISLA->PROGRESIVO
          //CHECKEAR CORRESPONDENCIA JUEGO-> PROGRESIVO. EN LO POSIBLE EN LA VISTA ADEMAS DE ACA TAMBIEN
          'id_maquina' => 'required' ,
          'nro_admin' => ['required ','integer','max:999999'],
          'marca'=> 'required|max:45',
          'modelo' => 'required|max:60',
          'mac' => 'nullable|max:100',
          'id_unidad_medida' => 'nullable|max:45',
          'nro_serie'=>  'nullable|alpha_dash',
          'marca_juego' => 'nullable|max:100',
          //'porcentaje_devolucion' => ['required','regex:/^\d\d?([,|.]\d\d?\d?)?$/'],
          'id_tipo_gabinete'=> 'required', //exists:tipo_gabinete,id_tipo_gabinete
          'id_tipo_maquina' => 'required', // exists:tipo_maquina,id_tipo_maquina
          'id_casino' => ['required', Rule::exists('usuario_tiene_casino')->where(function($query){$query->where('id_usuario', session('id_usuario'));})],
          'id_sector' => 'required|exists:sector,id_sector',
          'id_isla'  => 'required',
          'nro_isla' => 'required',// validacion en after, ver is nro y casino se corresponden
          'denominacion' => ['required','regex:/^\d\d?\d?\d?\d?\d?\d?\d?([,|.]\d\d?)?$/'],
          'id_estado_maquina' => 'required|exists:estado_maquina,id_estado_maquina',
          'expedientes' =>  'required_if:notas,null',
          'expedientes.*.id_expediente' => 'required|exists:expediente,id_expediente',
          'notas' => 'required_if:expedientes,null',
          'notas.*.id_nota' => 'required|exists:nota,id_nota',
          'juego' => 'array|required',
          'juego.*.id_juego' => 'required', //validar que es id valido
          'juego.*.nombre_juego' => 'required',
          'juego.*.activo' => 'in:1,0',
          'juego.*.tabla.*.id_tabla' => 'nullable',
          'juego.*.tabla.*.nombre_tabla' => 'required',
          'id_progresivo' => 'nullable',
          'nombre_progresivo' => 'nullable',
          'id_tipo' => 'nullable',
          'formula.id_formula' => 'required',
          'formula.cuerpoFormula' => 'required',
          'id_tipo_moneda' => 'required|exists:tipo_moneda,id_tipo_moneda',
          //table,column,except,idColumn
          //expediente,nro_exp_interno,'.$request->id_expediente.',id_expediente'
          //'gli_hards.*.id_gli_hard' => 'required|integer|exists:gli_hard,id_gli_hard|distinct',
          'gli_hard' => 'nullable',
          'gli_hard.*.id_gli_hard' => 'nullable|integer',
          'gli_hard.*.nro_certificado' => 'nullable|alpha_dash',
          'gli_hard.*.expedientes' => 'nullable',
          'gli_hard.*.file' => 'sometimes|mimes:pdf'
      ],array(),self::$atributos)->after(function($validator){
        //validacion isla
        $reglas = array();
        $reglas[] = ['nro_isla' , '=' , $validator->getData()['nro_isla']];
        $reglas[] = ['id_casino' , '=' , $validator->getData()['id_casino']];
        if($validator->getData()['codigo'] != ''){
          $reglas[] = ['codigo' , '=' , $validator->getData()['codigo']];
        }

        if($validator->getData()['id_isla'] != 0){
          $reglas[] = ['id_isla' , '<>' , $validator->getData()['id_isla']];
        }

        $isla_db = Isla::where($reglas)->get();

        if($isla_db->count() > 1){
          $validator->errors()->add('nro_isla', 'Se encontró un error con la base de datos.(Más de una isla con mismo nro)');
        }
        //validacion maquina
        if($validator->getData()['id_casino'] != 3){//santa fe y melincue
          $casinos = [1,2];
          $maquina = Maquina::join('isla' , 'maquina.id_isla' , '=' , 'isla.id_isla')
                              ->join('sector', 'sector.id_sector', '=' ,'isla.id_sector')
                              ->join('casino', 'casino.id_casino', '=' , 'sector.id_casino')
                                  ->where('maquina.nro_admin' , $validator->getData()['nro_admin'])
                                  ->whereIn('casino.id_casino' ,$casinos )
                              ->get();

        }else{//si la maquina es de rosario
          $maquina = Maquina::join('isla' , 'maquina.id_isla' , '=' , 'isla.id_isla')
                              ->join('sector', 'sector.id_sector', '=' ,'isla.id_sector')
                              ->join('casino', 'casino.id_casino', '=' , 'sector.id_casino')
                                  ->where('maquina.nro_admin' , $validator->getData()['nro_admin'] )
                                      ->where('maquina.id_casino' , '=',3)
                              ->get();

        }

        if(count($maquina) == 1){//si al modificar maquina, no cambio nro admin deberia haber una maquina y ser la misma.
          if($maquina[0]->id_maquina != $validator->getData()['id_maquina']){
            $validator->errors()->add('nro_admin', 'El número de administración ya está tomado.');
          }
        }else if(count($maquina) > 1){
          $validator->errors()->add('DB', 'El número de administración ya está tomado.');
        }

      })->sometimes('gli_hard.file','mimes:pdf',function($input){
      return $input['gli_hard.file'] != null;
      })->sometimes('gli_hard.id_gli_hard','exists:gli_hard,id_gli_hard',function($input){
      return $input['gli_hard.id_gli_hard'] != null && $input['gli_hard.id_gli_hard'] != 0;
    })->validate();// FIN VALIDACION-----------

    $tipo_movimiento = NULL;
    $formula=null;

    //SI EXISTE FORMULA LA BUSCA SI NO CREA
    switch ($request->formula['id_formula']){
      case 'undefined':break;
      case '':break;
      case 0:
        //el 0 es una bandera, una formula generica que se asigna al dar de alta una maquinia sin determinar formula
        //buscaba con "formula" en el json pero era "cuerpoFormula"
        $formula=FormulaController::getInstancia()->guardarFormulaConcatenada($request->formula['cuerpoFormula']);
        break;
      default:
        $formula=Formula::find($request->formula['id_formula']);
        break;
    }

    //ISLA
    if($request->id_isla == 0){ //si estoy creando una isla, valido
      //si estoy creando y pasó la validacion,
      $unaIsla= new Isla();
      $unaIsla->nro_isla = $request->nro_isla;
      $unaIsla->codigo = $request->codigo;
      $unaIsla->id_casino = $request->id_casino;
      $unaIsla->id_sector = $request->id_sector;

      $unaIsla = IslaController::getInstancia()->saveIsla($unaIsla, $request->maquinas);
    }else if( $request->modificado == 1){
      $unaIsla= new Isla();
      $unaIsla->nro_isla = $request->nro_isla;
      $unaIsla->codigo = $request->codigo;
      $unaIsla->id_sector = $request->id_sector;
      $unaIsla->id_casino = $request->id_casino;
      //modifica isla creada
      $unaIsla = IslaController::getInstancia()->modifyIsla($unaIsla, $request->id_isla, $request->maquinas);
    }else {//estoy usando una isla ya creada (sin modificar)
      $unaIsla= Isla::find($request->id_isla);
    }

    //GLIHARD:      SI EXISTE GLIHARD BUSCA, SI NO CREA
    $gli_hard = null;
    switch ($request->gli_hard['id_gli_hard']){
      case 'undefined':break;
      case '':break;
      case null: break;
      case "null": break;
      case 0:
        $gli_hard=GliHardController::getInstancia()
        ->guardarNuevoGliHard($request->gli_hard['nro_certificado'],null,$request->gli_hard['file']);
        break;
      default:
        $gli_hard=GliHard::find($request->gli_hard['id_gli_hard']);
        break;
    }

    $i=0;

    $razon = "La maquina sufrió modificaciones: "; //razon del cambio, que se guardara en el log de máquinas
    $MTM= Maquina::find($request->id_maquina);
    //CONDICIONES ANTERIORES

    /*
      Checkeo de cambios
    */
    //se comprueba si es null, esto es un problema arrastrado en el alta, nunca deberia ser null pero la comprobacion no estaria de mas
    //si es null es estado anterior, se le asigna el nuevo , sino se evalua el cambio para asignar razon
    if(is_null($MTM->id_estado_maquina)){
      $MTM->id_estado_maquina=$request->id_estado_maquina;
    }elseif($MTM->id_estado_maquina != $request->id_estado_maquina){
      $razon .= "Cambió el estado. ";
      switch($request->id_estado_maquina){
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

    if($MTM->denominacion != $request->denominacion){
        $tipo_movimiento = 5;
        $razon .= "Cambió la denominacion. ";
    }

    if($MTM->id_isla != $unaIsla->id_isla){
      $tipo_movimiento = 4;
      $razon .= "Cambió la isla. ";
    }

    /*
      ACTUALIZACION DE DATOS
    */
    //JUEGOS
    //POR CADA JUEGO, SI NO EXISTE CREO, SINO busco. ACTIVO se termina asociando
    //los juegos se gestionand desde aca solo si la mtm no es multi-juego
    if($MTM->id_pack==null){
      $juego_viejo = $MTM->juego_activo;

      $abreviaturas = [];//Ver mas abajo para que sirve esto.
      foreach($MTM->juegos as $unJuego){
        $abreviaturas[] = $this->abreviarMarca($MTM->marca) . ' - ' . $unJuego['nombre_juego'];
      }

      $MTM->juegos()->detach();
      
      foreach ($request->juego as $unJuego){
        $abreviaturas[] = $this->abreviarMarca($MTM->marca) . ' - ' . $unJuego['nombre_juego'];
        if($unJuego['id_juego']==0){// 0 es juego nuevo
          $juego=JuegoController::getInstancia()->guardarJuego_gestionarMaquina($unJuego['nombre_juego'],$unJuego['tabla']);

          if($unJuego['activo']==1){ // asociar juego activo
              $juegoActivo=$juego;
              $MTM->juego_activo()->associate($juego->id_juego);
          }
          $denominacion = $unJuego['denominacion'] == "-" ? null : $unJuego['denominacion'];
          $MTM->juegos()->syncWithoutDetaching([$juego->id_juego => ['denominacion' => $unJuego['denominacion'], 'porcentaje_devolucion' => $unJuego['porcentaje_devolucion']]]);
          // $juegos_finales[] = ($juego->id_juego);
        }else{
          if($unJuego['id_juego']>=1){
            $juego=Juego::find($unJuego['id_juego']);
            if($unJuego['activo']==1){// asociar juego activo
              $juegoActivo=$juego;
              $MTM->juego_activo()->associate($juego->id_juego);
            }
            // la gestion de pack se quita del modal mtm
            $id_pack_juego=null;

            $MTM->juegos()->syncWithoutDetaching([$juego->id_juego => ['denominacion' => $unJuego['denominacion'], 'porcentaje_devolucion' => $unJuego['porcentaje_devolucion'],'id_pack' => $id_pack_juego]]);
            // $juegos_finales[] = ($juego->id_juego);
          }
        }
      }

      if($juego_viejo->id_juego != $juegoActivo->id_juego){
        $tipo_movimiento = 7;
        $razon .= "Cambió el juego. ";
      }

      // NO SIMPLIFICAR CON LO DE ARRIBA HABIA CASOS ESPECIALES QUE NO SE TENIAN EN CUENTA!!
      $marca_juego_generado = $this->abreviarMarca($request->marca) . ' - ' . $juegoActivo->nombre_juego;
      $es_nuevo = $marca_juego_generado != $MTM->marca_juego;
      $es_customizado = !in_array($request->marca_juego,$abreviaturas);
      // Casos que se dan
      // Cambio de JUEGO ACTIVO sin cambiar el marca juego
      // Por ejemplo si: 
      //  Marca juego: IGT - Scarab
      //  Juegos:
      //   - Scarab (activo)
      //   - Jungle Riches
      // Y lo cambiamos a 
      //  Marca juego: IGT - Scarab
      //  Juegos:
      //   - Scarab 
      //   - Jungle Riches (activo)
      // El comporamiento deseado es que se cambie el MARCA JUEGO
      // Pero si tenemos un MARCA JUEGO custom elegido por el usuario ej
      //  Marca juego: Minguito!
      //  Juegos:
      //   - Scarab (activo)
      //   - Jungle Riches 
      // Y lo cambiamos a
      //  Marca juego: Minguito!
      //  Juegos:
      //   - Scarab 
      //   - Jungle Riches (activo)
      // NO! queremos que cambie
      // La excepcion a la regla es que sea la cadena vacia!
      // Ya se que se puede simplificar en un solo IF pero queda mas claro asi.
      if($es_nuevo && !$es_customizado){
        $MTM->marca_juego = $marca_juego_generado;
      }
      else if($es_customizado && $request->marca_juego == ""){
        $MTM->marca_juego = $marca_juego_generado;
      }
      else if($es_customizado){
        $MTM->marca_juego = $request->marca_juego;
      }
           
      $MTM->id_juego = $juegoActivo->id_juego;
    }

    $MTM->nro_admin = $request->nro_admin;
    $MTM->marca = $request->marca;
    $MTM->modelo = $request->modelo;
    $MTM->id_unidad_medida = $request->id_unidad_medida;
    $MTM->id_tipo_moneda = $request->id_tipo_moneda;
    $MTM->nro_serie = $request->nro_serie;
    $MTM->mac = $request->mac;
    $MTM->formula()->associate($formula);
    $MTM->denominacion = $request->denominacion;
    $MTM->juega_progresivo = $request->progresivo['id_progresivo'] != -1;
    $MTM->id_isla=$unaIsla->id_isla;
    $MTM->id_casino=$unaIsla->id_casino;
    $MTM->id_gli_hard = is_null($gli_hard)? null: $gli_hard->id_gli_hard;

    $MTM->save();
    if($request->id_tipo_gabinete != 0) $MTM->tipoGabinete()->associate($request->id_tipo_gabinete);
    if($request->id_tipo_maquina != 0) $MTM->tipoMaquina()->associate($request->id_tipo_maquina);
    $MTM->estado_maquina()->associate($request->id_estado_maquina);

    if(!empty($request->expedientes)) $MTM->expedientes()->sync($request->expedientes);

    if(!empty($request->notas))$MTM->notas()->sync($request->notas);

    //SI EXISTE EL PROGRESIVO BUSCO SI NO, CREO
    if($request->progresivo['id_progresivo'] != -1){ // 0 nuevo progresivo , > 0 nuevo progresivo, -1 sin progresivo
      switch ($request->progresivo['id_progresivo']) {
        case 'undefined':break;
        case '':break;
        case 0:
        $progresivo= ProgresivoController::getInstancia()->guardarProgresivo_gestionarMaquina($request->progresivo,$MTM);
        break;
        default:
        $progresivo= ProgresivoController::getInstancia()->modificarProgresivo_gestionarMaquina($request->progresivo,$MTM);
        break;
      }
    }

    LogMaquinaController::getInstancia()->registrarMovimiento($MTM->id_maquina, $razon,$tipo_movimiento);//tipo mov

    $MTM->save();

    return ['maquina' => $MTM];
  }
  //ELIMINA LOGICAMENTE LA MAQUINA. deleted_at != null
  public function eliminarMTM($id){
    $MTM=Maquina::find($id);
    $MTM->formula()->dissociate();
    $MTM->gliSoftOld()->dissociate();
    $MTM->gliHard()->dissociate();
    $MTM->estado_maquina()->associate(3);
    $razon = "La maquina se eliminó definitivamente.";
    LogMaquinaController::getInstancia()->registrarMovimiento($MTM->id_maquina, $razon,2);//tipo mov EGRESO
    $MTM->delete(); //SE MARCA COMO ELIMINANDO

    return ['MTM'=> $MTM ];
  }

  public function buscarMaquinaPorNumeroMarcaYModelo($casino = 0, $busqueda){
      if($casino!=0){
        $resultados = DB::table('maquina')
        ->select('maquina.id_maquina','maquina.nro_admin','maquina.marca','maquina.modelo')
          ->where([['maquina.nro_admin','like', $busqueda.'%'],['maquina.id_casino' , '=' , $casino]])

          ->get();
      }else{
        $usuario = UsuarioController::getInstancia()->buscarUsuario(session('id_usuario'))['usuario'];
        $casinos = array();
        foreach($usuario->casinos as $casino){
          $casinos[]=$casino->id_casino;
        }
        $resultados = DB::table('maquina')
        ->select('maquina.id_maquina','maquina.nro_admin','maquina.marca','maquina.modelo')
          ->where('maquina.nro_admin','like', $busqueda.'%')
          ->whereIn('maquina.id_casino' , $casinos)
          ->get();
    }

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

  public function generarMarcaJuego($arreglo_maquinas){//si vacio reviso todas las maquinas, si no recorro todas
    if(empty($arreglo_maquinas)){
      $maquinas=Maquina::all();
      foreach ($maquinas as $maquina) {
        $maquina->marca_juego= $this->abreviarMarca($maquina->marca) . ' - ' . $maquina->juego_activo->nombre_juego;
        $maquina->save();
      }
    }else{
      foreach ($arreglo_maquinas as $id_maquina) {
        $maquina= Maquina::find($id_maquina);
        $maquina->marca_juego=  $this->abreviarMarca($maquina->marca) . ' - ' . $maquina->juego_activo->nombre_juego;
        $maquina->save();
      }
    }

  }

  public function abreviarMarca($marca){
    switch (str_replace(' ' ,'',strtolower($marca))) {
      case 'aristocrat':
        $marca = 'ARI';
        break;
      case 'bally':
        $marca = 'BAL';
        break;
      case 'igt':
        $marca = 'IGT';
        break;
      case 'konami':
        $marca = 'KON';
        break;
      case 'atronic':
        $marca = 'ATR';
        break;
      case 'electro':
        $marca = 'ELE';
        break;
      case 'magic':
        $marca = 'MAG';
        break;
      case 'ainsworth':
        $marca = 'AIN';
        break;
      case 'novomatic':
        $marca = 'NOV';
        break;
      case 'electrochance':
        $marca = 'ELE';
        break;
      case 'alfastreet':
        $marca = 'ALF';
        break;
      case 'ballygaming':
        $marca = 'BAL';
        break;
      case 'sielcon':
        $marca = 'SIE';
        break;
      case 'healtec':
        $marca = 'HTE';
        break;
      case 'zitro':
        $marca = 'ZIT';
        break;
      case 'wms':
        $marca = 'WMS';
        break;
      case 'bcm':
        $marca = 'BCM';
        break;
      case 'Interblock':
        $marca = 'IBK';
        break;
      default:
        # code...
        break;
    }
    return $marca;
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

  public function modificarDenominacionYUnidad($id_unidad_medida, $denominacion, $id_maquina){
    $maquina = Maquina::find($id_maquina);
    $maquina->denominacion = $denominacion;
    $maquina->id_unidad_medida = $id_unidad_medida;
    $maquina->save();
    $razon = "Se cambió cambiar denominacion y unidad medida.";
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
    $mtm->expedientes()->sync($id_expediente);// se borra el resto
    $mtm->save();
  }

  public function getMoneda($nro){//@param: nro_admin de maquina
    Validator::make([
        'nro_admin' => $nro
      ] ,
       [
         'nro_admin' => 'required|exists:maquina,nro_admin'
      ] , array(), self::$atributos)->after(function ($validator){
          $resultados = Maquina::where([['id_casino',3],['nro_admin',$validator->getData()['nro_admin']]])->get();
          if($resultados->count() != 1){
            $validator->errors()->add('nro_admin', 'La maquina no se encuentra en el casino de Rosario.');
          }
        })->validate();

      $maquina = Maquina::where([['id_casino',3], ['nro_admin',$nro]])->first();

      return ['tipo' => $maquina->tipoMoneda];
  }



}
