<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Validator;
use Illuminate\Support\Facades\DB;
use PDF;
use Dompdf\Dompdf;
use View;
use Zipper;
use File;

use App\Sector;
use App\Casino;
use App\DetalleLayoutParcial;
use App\LayoutParcial;
use App\EstadoRelevamiento;
use App\Progresivo;
use App\CampoConDiferencia;
use App\Maquina;
use App\LayoutTotal;
use App\DetalleLayoutTotal;
use App\LayoutTotalCodigo;
use App\MaquinaAPedido;
use App\Isla;
use App\TipoCausaNoToma;
use App\PackJuego;
use App\LayoutTotalIsla;
/*
  Controllador encargado de crear(o usar backup), modifciar o borrar
  cargar y validar
  Layouts parciales y totales
*/

class LayoutController extends Controller
{
  public $sorteadas = array();

  private static $atributos = [];
  private static $instance;
  private static $cant_dias_backup_relevamiento = 6;

  public static function getInstancia(){
    if (!isset(self::$instance)) {
      self::$instance = new LayoutController();
    }
    return self::$instance;
  }

  public function buscarTodo(){
    $usuario = UsuarioController::getInstancia()->buscarUsuario(session('id_usuario'))['usuario'];
    $casinos = $usuario->es_superusuario? Casino::all() : $usuario->casinos;
    //@HACK: arreglar permisos
    $ver_planilla_layout_parcial = $usuario->es_superusuario || $usuario->es_administrador? true : $usuario->tienePermiso('ver_planilla_layout_parcial');
    $carga_layout_parcial        = $usuario->es_superusuario || $usuario->es_administrador? true : $usuario->tienePermiso('carga_layout_parcial');
    $validar_layout_parcial      = $usuario->es_superusuario || $usuario->es_administrador? true : $usuario->tienePermiso('validar_layout_parcial');
    $ver_seccion_maquinas        = $usuario->tienePermiso('ver_seccion_maquinas');
    $nombre_usuario              = $usuario->nombre;
    $estados = EstadoRelevamiento::all();
    UsuarioController::getInstancia()->agregarSeccionReciente('Layout Parcial' , 'layout_parcial');
    
    $fiscalizadores = collect([]);
    foreach($casinos as $c){
      $fc = UsuarioController::getInstancia()->obtenerFiscalizadores($c->id_casino,$usuario->id_usuario);
      $fc->transform(function($f,$fidx) use ($c){
        $f->id_casino = $c->id_casino;
        return $f;
      });
      $fiscalizadores = $fiscalizadores->concat($fc);
    }
        
    return view('seccionLayoutParcial', compact('nombre_usuario','fiscalizadores','casinos','estados','ver_planilla_layout_parcial','carga_layout_parcial','validar_layout_parcial','ver_seccion_maquinas'));
  }
  
  public function guardarLayoutParcial(Request $request,$estado = 'Cargando'){
    $lp = null;
    $u  = UsuarioController::getInstancia()->quienSoy()['usuario'];
    $request->request->set('estado',$estado);
    Validator::make($request->all(), [
      'id_layout_parcial' => 'required|exists:layout_parcial,id_layout_parcial,backup,0',
      'id_fiscalizador_toma' => 'required|exists:usuario,id_usuario,deleted_at,NULL',
      'tecnico'              => 'required|string',
      'fecha_ejecucion'      => 'required|date_format:"Y-m-d H:i"',
      'detalles'             => 'nullable|array',
      'detalles.*.nro_admin' => 'required|exists:maquina,nro_admin,deleted_at,NULL',
      'detalles.*.nro_isla'  => 'required|exists:isla,nro_isla,deleted_at,NULL',
      'detalles.*.marca'     => 'required',
      'detalles.*.juego'     => 'required',
      'detalles.*.nro_serie' => 'required',
      'detalles.*.no_toma'   => 'required|string|in:"false","true"',
      'detalles.*.denominacion' => 'nullable|string',
      'detalles.*.pdev'         => 'nullable|numeric|min:0|max:100',
      'observacion_fiscalizacion' => 'nullable|string',
      'estado' => 'required|exists:estado_relevamiento,descripcion|in:"Cargando","Finalizado"',
    ], [
      'required' => 'El valor es requerido',
      'exists'   => 'El valor es inexistente',
      'detalles.*.no_toma.in' => 'El valor es incorrecto',
      'numeric' => 'El valor tiene que ser númerico',
      'min'     => 'El mínimo es 0',
      'max'     => 'El máximo es 100',
    ], self::$atributos)->after(function($validator) use (&$lp,&$u){
      if($validator->errors()->any()) return;
      $data = $validator->getData();
      $lp = LayoutParcial::find($data['id_layout_parcial']);
      $id_casino = $lp->sector->id_casino;
      if(!$u->usuarioTieneCasino($id_casino)){
        return $validator->errors()->add('id_layout_parcial','No puede acceder');
      }
      $dets = collect($data['detalles'] ?? []);
      foreach($dets as $didx => $d){
        $tomado = $d['no_toma'] == 'false';
        if($tomado){
          $deno = $d['denominacion'] ?? null;
          $pdev = $d['pdev'] ?? null;
          if($deno === null){
            $validator->errors()->add("detalles.$didx.denominacion",'El valor es requerido');
          }
          if($pdev === null){
            $validator->errors()->add("detalles.$didx.pdev",'El valor es requerido');
          }
        }
        
        if(($d['nro_admin'] ?? null) === null) continue;
        $m = Maquina::where('id_casino',$id_casino)->where('nro_admin',$d['nro_admin'])->first();
        $existe_m = !is_null($m);
        if(!$existe_m){
          $validator->errors()->add("detalles.$didx.nro_admin",'No existe la maquina');
        }
        
        $existe_d = DetalleLayoutParcial::where('id_layout_parcial',$lp->id_layout_parcial)
        ->where('id_maquina',$m->id_maquina)->count() == 1;
        
        if(!$existe_d){
          $validator->errors()->add("detalles.$didx",'No existe el detalle');
        }
        
        if(($d['nro_isla'] ?? null) === null) continue;
        $existe_i = Isla::where('id_casino',$id_casino)->where('nro_isla',$d['nro_isla'])->count() == 1;
        
        if(!$existe_i){
          $validator->errors()->add("detalles.$didx.nro_isla",'No existe la isla');
        }
      }
      if($lp->detalles->count() != $dets->count()){
        $validator->errors()->add('detalles','Cantidad incorrecta de detalles');
      }
    })->validate();
    
    return DB::transaction(function() use ($request,$lp,$u,$estado){
      $lp->tecnico                   = $request->tecnico;
      $lp->id_usuario_fiscalizador   = $request->id_fiscalizador_toma;
      $lp->id_usuario_cargador       = session('id_usuario');
      $lp->fecha_ejecucion           = $request->fecha_ejecucion;
      $lp->observacion_fiscalizacion = $request->observacion_fiscalizacion;
            
      $e = EstadoRelevamiento::where('descripcion',$estado)->first();
      $lp->id_estado_relevamiento = $e->id_estado_relevamiento;
      $lp->save();
      
      $detalles = collect($request->detalles ?? [])->keyBy('nro_admin');
      $detalles_bd = $lp->detalles;
      foreach($detalles_bd as $dbd){
        $m = $dbd->maquina;
        if(!$detalles->has($m->nro_admin)) continue;
        $d = $detalles[$m->nro_admin];
        
        $correcto = 1;
        $campos = [
          'nro_isla'  => $m->isla->nro_isla,
          'marca'     => $m->marca,
          'juego'     => $m->juego_activo->nombre_juego,
          'nro_serie' => $m->nro_serie,
        ];
        
        foreach($campos as $columna => $val){
          $ccd = CampoConDiferencia::where('entidad','maquina')->where('columna',$columna)
          ->where('id_detalle_layout_parcial',$dbd->id_detalle_layout_parcial)->first();
          if($val != ($d[$columna] ?? '')){
            $ccd = $ccd ?? (new CampoConDiferencia);
            $ccd->entidad = 'maquina';
            $ccd->columna = $columna;
            $ccd->valor   = $d[$columna] ?? '';
            $ccd->id_detalle_layout_parcial = $dbd->id_detalle_layout_parcial;
            $ccd->save();
            $correcto = 0;
          }
          else if(!is_null($ccd)){
            $ccd->delete();
          }
        }
        
        if($d['no_toma'] == 'true'){
          $dbd->denominacion = null;
          $dbd->porcentaje_devolucion = null;
        }
        else{
          $dbd->denominacion = $d['denominacion'] ?? null;
          $dbd->porcentaje_devolucion = $d['pdev'] ?? null;
        }
                
        $dbd->correcto = $correcto;
        $dbd->save();
      }
      
      return 1;
    });
  }
  
  public function finalizarLayoutParcial(Request $request){
    return $this->guardarLayoutParcial($request,'Finalizado');
  }
  
  public function obtenerLayoutParcial($id){
    $lp = LayoutParcial::find($id);
    $lp_estado = $lp->estado_relevamiento->descripcion;
    
    $detalles = $lp->detalles->map(function($dbd) use ($lp_estado){
      $d = new \stdClass();
      $m = $dbd->maquina;
      $d->id_maquina   = $m->id_maquina;
      $d->denominacion = $dbd->denominacion;
      $d->pdev         = $dbd->porcentaje_devolucion;
      $d->no_toma      = $lp_estado != 'Generado' && $dbd->denominacion === null && $dbd->porcentaje_devolucion === null;
      $campos = [
        'nro_admin' => $m->nro_admin,
        'nro_isla'  => $m->isla->nro_isla,
        'marca'     => $m->marca,
        'juego'     => $m->juego_activo->nombre_juego,
        'nro_serie' => $m->nro_serie,
      ];
      
      $ccds = CampoConDiferencia::where('entidad','maquina')
      ->where('id_detalle_layout_parcial',$dbd->id_detalle_layout_parcial)->get()->keyBy('columna');
      
      foreach($campos as $columna => $val){
        if($ccds[$columna] ?? false){
          $d->{$columna} = ['correcto' => false,'valor' => $ccds[$columna]->valor,'valor_antiguo' => $val];
        }
        else{
          $d->{$columna} = ['correcto' => true,'valor' => $val,'valor_antiguo' => ''];
        }
      }
      
      return $d;
    });
    
    return [
      'layout_parcial' => $lp,
      'casino' => $lp->sector->casino->nombre,
      'id_casino' => $lp->sector->casino->id_casino,
      'sector' => $lp->sector->descripcion,
      'detalles' => $detalles,
      'usuario_cargador' => $lp->usuario_cargador,
      'usuario_fiscalizador' => $lp->usuario_fiscalizador,
    ];
  }
  
  public function validarLayoutParcial(Request $request){
    $lp = null;
    $u  = UsuarioController::getInstancia()->quienSoy()['usuario'];
    Validator::make($request->all(), [
      'id_layout_parcial' => 'required|exists:layout_parcial,id_layout_parcial,backup,0',
      'observacion_validacion'    => 'nullable|string',
    ], [
      'required' => 'El valor es requerido',
      'exists'   => 'El valor es inexistente',
    ], self::$atributos)->after(function($validator) use (&$lp,&$u){
      if($validator->errors()->any()) return;
      $data = $validator->getData();
      $lp = LayoutParcial::find($data['id_layout_parcial']);
      $id_casino = $lp->sector->id_casino;
      if(!$u->usuarioTieneCasino($id_casino)){
        return $validator->errors()->add('id_layout_parcial','No puede acceder');
      }
    })->validate();
    
    return DB::transaction(function() use ($request,$lp,$u,$estado){
      $lp->observacion_validacion = $request->observacion_fiscalizacion;
            
      $e = EstadoRelevamiento::where('descripcion','Visado')->first();
      $lp->id_estado_relevamiento = $e->id_estado_relevamiento;
      $lp->save();
      return 1;
    });
  }
  
  public function obtenerDetallesProgresivo($maquina,$detalle){
    $linea = new \stdClass();
    if($maquina->pozo != null && $detalle != null){

          $progresivo = $maquina->pozo->niveles_progresivo[0]->progresivo;

          $detalle_aux = $detalle->campos_con_diferencia->where('entidad', 'progresivo');

          $aux = $detalle_aux->where('columna', 'nombre_progresivo');
          if($aux->count() == 1){
              foreach ($aux as $cd) {
                $linea->nombre_progresivo = ['correcto' => false, 'valor' => $cd->valor, 'valor_antiguo' =>  $progresivo->nombre_progresivo];
              }
            }else{
              $linea->nombre_progresivo = ['correcto' => true, 'valor' =>  $progresivo->nombre_progresivo , 'valor_antiguo' => ''];
          }

          $aux = $detalle_aux->where('columna', 'maximo');
          if($aux->count() == 1){
            foreach ($aux as $cd) {
              $linea->maximo = ['correcto' => false, 'valor' =>  $cd->valor, 'valor_antiguo' =>$progresivo->maximo ];
            }
            }else{
              $linea->maximo = ['correcto' => true, 'valor' => $progresivo->maximo , 'valor_antiguo' => ''];
          }

          $aux = $detalle_aux->where('columna', 'individual');
          if($aux->count() == 1){
            foreach ($aux as $cd) {
              $linea->individual = ['correcto' => false, 'valor' =>  $cd->valor, 'valor_antiguo' =>$progresivo->individual ];
            }
            }else{
              $linea->individual = ['correcto' => true, 'valor' => $progresivo->individual , 'valor_antiguo' => ''];
          }

          $aux = $detalle_aux->where('columna' , 'porc_recuperacion');
          if($aux->count() == 1){
            foreach ($aux as $cd) {
              $linea->porc_recuperacion = ['correcto' => false, 'valor' => $cd->valor, 'valor_antiguo' => $progresivo->porc_recuperacion];
            }
            }else{
              $linea->porc_recuperacion = ['correcto' => true, 'valor' => $progresivo->porc_recuperacion , 'valor_antiguo' => ''];
          }


    }else if($maquina->pozo != null){

          $progresivo = $maquina->pozo->niveles_progresivo[0]->progresivo;

          $linea->nombre_progresivo = ['correcto' => true, 'valor' =>  $progresivo->nombre_progresivo , 'valor_antiguo' => ''];

          $linea->maximo = ['correcto' => true, 'valor' => $progresivo->maximo , 'valor_antiguo' => ''];

          $linea->porc_recuperacion = ['correcto' => true, 'valor' => $progresivo->porc_recuperacion , 'valor_antiguo' => ''];

          $linea->individual = ['correcto' => true, 'valor' => $progresivo->individual , 'valor_antiguo' => ''];

    }else{
          $linea = null;
    }
    return $linea;
  }

  public function obtenerDetallesNivelesProgresivo($maquina,$detalle){
    if($maquina->pozo != null && $detalle != null){
          $i=1;
          foreach ($progresivo = $maquina->pozo->niveles_progresivo as $nivel ) {
            $linea = new \stdClass();
            $detalle_aux = $detalle->campos_con_diferencia->where('entidad','nivel_progresivo/' . $i);
            $aux =$detalle_aux->where('columna', 'porc_visible');
            if($aux->count()  == 1){
                foreach ($aux as $cd) {
                  $linea->porc_visible = ['correcto' => false, 'valor' => $cd->valor, 'valor_antiguo' =>  $nivel->porc_visible];
                }
              }else{
                $linea->porc_visible = ['correcto' => true, 'valor' =>  $nivel->porc_visible , 'valor_antiguo' => ''];
            }

            $aux =$detalle_aux->where('columna' , 'porc_oculto');
            if($aux->count() == 1){
              foreach ($aux as $cd) {
                $linea->porc_oculto = ['correcto' => false, 'valor' => $cd->valor, 'valor_antiguo' => $nivel->porc_oculto ];
              }
              }else{
                $linea->porc_oculto = ['correcto' => true, 'valor' =>  $nivel->porc_oculto , 'valor_antiguo' => ''];
            }

            $aux =$detalle_aux->where('columna', 'base');
            $base = $nivel->pivot->base != null ? $nivel->pivot->base : $nivel->base;
            if($aux->count() == 1){
                foreach ($aux as $cd) {
                  $linea->base = ['correcto' => false, 'valor' =>$cd->valor, 'valor_antiguo' =>  $base];
                }
              }else{
                $linea->base = ['correcto' => true, 'valor' =>  $base , 'valor_antiguo' => ''];
            }

            $aux =$detalle_aux->where('columna' , 'nombre_nivel');
            if($aux->count() == 1){
              foreach ($aux as $cd) {
                $linea->nombre_nivel = ['correcto' => false, 'valor' => $cd->valor, 'valor_antiguo' =>   $nivel->nombre_nivel ];
              }
              }else{
                $linea->nombre_nivel = ['correcto' => true, 'valor' =>   $nivel->nombre_nivel , 'valor_antiguo' => ''];
            }

            $aux =$detalle_aux->where('columna', '=' , 'nro_nivel');
            if($aux->count() == 1){
                foreach ($aux as $cd) {
                  $linea->nro_nivel = ['correcto' => false, 'valor' => $cd->valor, 'valor_antiguo' =>   $nivel->nro_nivel ];
                }
              }else{
                $linea->nro_nivel = ['correcto' => true, 'valor' =>   $nivel->nro_nivel , 'valor_antiguo' => ''];
            }

            $niveles[] = $linea;
          }


    }else if($maquina->pozo !=null && $detalles == null){
      foreach ($progresivo = $maquina->pozo->niveles_progresivo as $nivel) {

        $base = $nivel->pivot->base != null ? $nivel->pivot->base : $nivel->base;

        $linea = new \stdClass();

        $linea->nombre_progresivo = ['correcto' => true, 'valor' =>  $nivel->porc_visible , 'valor_antiguo' => ''];

        $linea->maximo = ['correcto' => true, 'valor' =>  $nivel->porc_oculto , 'valor_antiguo' => ''];

        $linea->porc_recuperacion = ['correcto' => true, 'valor' =>  $base , 'valor_antiguo' => ''];

        $linea->porc_recuperacion = ['correcto' => true, 'valor' =>   $nivel->nombre_nivel , 'valor_antiguo' => ''];

        $linea->porc_recuperacion = ['correcto' => true, 'valor' =>   $nivel->nro_nivel , 'valor_antiguo' => ''];
      }
     $niveles[] = $linea;
   }else{
     $niveles = null;
   }
    return $niveles;
  }

  public function existeLayoutParcial($id_sector){
    Validator::make(['id_sector' => $id_sector],[
        'id_sector' => 'required|exists:sector,id_sector'
    ], array(), self::$atributos)->after(function($validator){
    })->validate();
    $fecha_hoy = date("Y-m-d");
    $rel_sobresescribir = LayoutParcial::where([['fecha','=',$fecha_hoy],['id_sector','=',$id_sector],['backup','=',0] , ['id_estado_relevamiento' ,'=' ,1]])->count();//si esta generado
    $resultados = $rel_sobresescribir > 0 ? 1 : 0;
    $rel_no_sobrescribir = LayoutParcial::where([['fecha','=',$fecha_hoy],['id_sector','=',$id_sector],['backup','=',0] , ['id_estado_relevamiento' ,'<>' ,1]])->count();//cargando, finalizado, validado
    $resultados = $rel_no_sobrescribir > 0 ? 2 : $resultados;
    //si dentro de la fecha de hoy se tiene uno distinto a generado, siempre va devolver 2, en este punto no sirve para decir si hay uno generado
    return $resultados;
  }

  public function existeLayoutParcialGenerado($id_sector){
    //devuelve 1 si ya existe un layasout generado para el dia de hoy y para ese sector
    Validator::make(['id_sector' => $id_sector],[
        'id_sector' => 'required|exists:sector,id_sector'
    ], array(), self::$atributos)->after(function($validator){
    })->validate();
    $fecha_hoy = date("Y-m-d");
    $rel_sobresescribir = LayoutParcial::where([['fecha','=',$fecha_hoy],['id_sector','=',$id_sector],['backup','=',0] , ['id_estado_relevamiento' ,'=' ,1]])->count();//si esta generado
    $resultados = $rel_sobresescribir > 0 ? 1 : 0;
    
    return $resultados;
  }

  
  // elimina los backup previos, genera un ordenamiento dependiendo del casino
  // toma un random dentro de las maquinas habilitadas
  // crea la planilla y los nuevos backup a partir de este relevamiento
  public function crearLayoutParcial(Request $request){
    Validator::make($request->all(),[
        'id_sector' => 'required|exists:sector,id_sector',
        'cantidad_maquinas' => 'required|numeric|between:1,1000',
        'cantidad_fiscalizadores' => 'nullable|numeric|between:1,10'
    ], [
      'exists' => 'El valor no existe',
      'cantidad_maquinas.between' => 'El número tiene que estar entre 1 y 1000',
      'cantidad_fiscalizadores.between' => 'El número tiene que estar entre 1 y 10',
    ], self::$atributos)->after(function($validator){
      $relevamientos = LayoutParcial::where([['fecha',date("Y-m-d")],['id_sector',$validator->getData()['id_sector']],['backup',0],['id_estado_relevamiento',2]])->count();
      if($relevamientos > 0){
        $validator->errors()->add('layout_en_carga','El control de layout para esa fecha ya está en carga y no se puede reemplazar.');
      }
    })->validate();
    
    return DB::transaction(function() use ($request){
      $fecha_generacion = date('Y-m-d h:i:s', time());
      $fecha_hoy = explode(' ',$fecha_generacion)[0];
      
      {
        $layouts_viejos = LayoutParcial::where([['fecha',$fecha_hoy],['id_sector',$request->id_sector],['backup',0],['id_estado_relevamiento',1]])->get();
        foreach($layouts_viejos as $lp){
          foreach($lp->detalles as $d){
            $d->delete();
          }
          $lp->delete();
        }
      }
      
      $sector = Sector::find($request->id_sector);//busco las islas del sector para saber que maquinas se pueden usar
      $archivos = [];
      $fechas = [];
      for($i = 0; $i <= self::$cant_dias_backup_relevamiento; $i++){
        $fechas[] = date("Y-m-d", strtotime($fecha_hoy . " +$i days"));
      }
      
      {
        $maquinas_sorter = $sector->id_casino==3?//evaluo si es de rosario para ordenar por islote e isla , sino solo por isla
          function($maquina,$key){
            $isla = Isla::find($maquina->id_isla);
            return [$isla->orden, $isla->nro_isla];
          }
        : function($maquina,$key){
          return Isla::find($maquina->id_isla)->nro_isla;
        };
        
        $maquinas_query = Maquina::whereIn('id_isla',$sector->islas->pluck('id_isla'))
        ->whereHas('estado_maquina',function($q){$q->where('descripcion','Ingreso')->orWhere('descripcion','ReIngreso');})
        ->inRandomOrder()->take($request->cantidad_maquinas);
        
        foreach($fechas as $fidx => $f){
          $maquinas = (clone $maquinas_query)->get()->sortBy($maquinas_sorter);
          $cant_por_planilla = ceil($maquinas->count()/$request->cantidad_fiscalizadores);
          $es_backup = $fidx > 0;
          
          for($i = 1; $i <= $request->cantidad_fiscalizadores; $i++){
            $lp = new LayoutParcial;
            $lp->nro_layout_parcial = DB::table('layout_parcial')->max('nro_layout_parcial') + 1;
            $lp->fecha = $f;
            $lp->fecha_generacion = $fecha_generacion;
            $lp->sector()->associate($sector->id_sector);
            $lp->estado_relevamiento()->associate(1);
            $lp->sub_control = $request->cantidad_fiscalizadores == 1? null : $i;
            $lp->backup = $es_backup;
            $lp->save();
            
            $maquinas_subControl = $maquinas->forPage($i,$cant_por_planilla);
            foreach($maquinas_subControl as $m){
              $detalle = new DetalleLayoutParcial;
              $detalle->id_maquina = $m->id_maquina;
              $detalle->id_layout_parcial = $lp->id_layout_parcial;
              $detalle->save();
            }
            
            $layouts_finales [] = $lp;
            $archivos[] = $this->guardarPlanillaLayoutParcial($lp->id_layout_parcial);
          }
        }
      }
      
      $codigo_cas   = $sector->casino->codigo;
      $desc_sec     = $sector->descripcion;
      $ultima_fecha = $fechas[self::$cant_dias_backup_relevamiento];
      $nombre_zip = "Planillas-$codigo_cas-$desc_sec-$fecha_hoy al $ultima_fecha.zip";
      
      Zipper::make($nombre_zip)->add($archivos)->close();
      File::delete($archivos);
      
      return compact('nombre_zip');
    });
  }
  
  public function buscarLayoutsParciales(Request $request){
    
    
    
    $reglas = [];
    if(isset($request->fecha)){
      $reglas[]=['layout_parcial.fecha', '=', $request->fecha];
    }
    if(isset($request->id_casino)){
      $reglas[]=['casino.id_casino', '=', $request->id_casino];
    }
    if(isset($request->id_sector)){
      $reglas[]=['sector.id_sector', '=', $request->id_sector];
    }
    if(isset($request->id_estado_relevamiento)){
      $reglas[] = ['estado_relevamiento.id_estado_relevamiento' , '=' , $request->id_estado_relevamiento];
    }

    $sort_by = $request->sort_by ?? [];
    $id_casinos = UsuarioController::getInstancia()->buscarUsuario(session('id_usuario'))['usuario']
    ->casinos->pluck('id_casino');
    
    $resultados = DB::table('layout_parcial')
    ->select('layout_parcial.*'  , 'sector.descripcion as sector' , 'casino.nombre as casino' , 'estado_relevamiento.descripcion as estado')
    ->join('sector' ,'sector.id_sector' , '=' , 'layout_parcial.id_sector')
    ->join('casino' , 'sector.id_casino' , '=' , 'casino.id_casino')
    ->join('estado_relevamiento' , 'layout_parcial.id_estado_relevamiento' , '=' , 'estado_relevamiento.id_estado_relevamiento')
    ->orderBy($sort_by['columna'] ?? 'layout_parcial.id_layout_parcial',$sort_by['orden'] ?? 'desc')
    ->orderBy('layout_parcial.id_layout_parcial','desc')
    ->where($reglas)
    ->whereIn('casino.id_casino' , $id_casinos)
    ->where('backup' , '=', 0)->paginate($request->page_size);
    
    setlocale(LC_TIME,'es_AR.UTF-8');
    foreach ($resultados as $lp) {
      $lp->fecha = ucwords(strftime("%d %b %Y", strtotime($lp->fecha)));
    }

    return $resultados;
  }

  public function usarLayoutBackup(Request $request){
    Validator::make($request->all(),[
        'id_sector' => 'required|exists:sector,id_sector',
        'fecha' => 'required|date',
        'fecha_generacion' => 'required|date'
    ], array(), self::$atributos)->after(function($validator){
    })->validate();

    //Si hay un relevamiento que original para el dia en el cual se quiere usar un backup
    $relevamientos = LayoutParcial::where([['id_sector',$request->id_sector],['fecha',$request->fecha],['backup',0]])->whereIn('id_estado_relevamiento',[1,2])->get();
    if($relevamientos != null){
      foreach($relevamientos as $relevamiento){
        $relevamiento->backup = 1;
        $relevamiento->save();
      }
    }


    //busco el backup para la fecha "fecha", creado el dia "fecha de generacion" , para sector "id_sector"
    $rel_backup = LayoutParcial::where([['id_sector',$request->id_sector],['fecha',$request->fecha],['backup',1]])->whereDate('fecha_generacion','=',$request->fecha_generacion)->first();
    $rel_backup->backup = 0;
    $rel_backup->save();

    return ['id_layout_parcial' => $rel_backup->id_layout_parcial,
            'fecha' =>  date("d M Y", strtotime($rel_backup->fecha)) ,
            'casino' => $rel_backup->sector->casino->nombre,
            'sector' => $rel_backup->sector->descripcion,
            'estado' => $rel_backup->estado_relevamiento->descripcion];
  }

  private function guardarPlanillaLayoutParcial($id_layout_parcial){
    $layout_parcial = LayoutParcial::find($id_layout_parcial);
    $dompdf = $this->crearPlanillaLayoutParcial($layout_parcial);
    $output = $dompdf->output();
    if($layout_parcial->sub_control != null){
      $ruta = "LayoutParcial-".$layout_parcial->sector->casino->codigo."-".$layout_parcial->sector->descripcion."-".$layout_parcial->fecha."(".$layout_parcial->sub_control.")".".pdf";
    }else{
      $ruta = "LayoutParcial-".$layout_parcial->sector->casino->codigo."-".$layout_parcial->sector->descripcion."-".$layout_parcial->fecha.".pdf";
    }

    file_put_contents($ruta, $output);

    return $ruta;
  }
  // crearPlanillaLayoutParcial crea la planilla de layout parcial para relevar
  // considera los paquetes de juegos, si tiene, muestra un mensajes que describe
  // sino lo implemtna , muestra el juego activo
  public function crearPlanillaLayoutParcial($layout_parcial){
    $rel= new \stdClass();
    $rel->nro_relevamiento = $layout_parcial->nro_layout_parcial;
    $rel->casinoCod = $layout_parcial->sector->casino->codigo;
    $rel->casinoNom = $layout_parcial->sector->casino->nombre;
    $rel->sector = $layout_parcial->sector->descripcion;
    $rel->fecha = $layout_parcial->fecha;
    $rel->fecha_ejecucion = ($layout_parcial->fecha_ejecucion != null) ? $layout_parcial->fecha_ejecucion : $layout_parcial->fecha;
    $rel->fecha_generacion = $layout_parcial->fecha_generacion;

    $año = substr($rel->fecha,0,4);
    $mes = substr($rel->fecha,5,2);
    $dia = substr($rel->fecha,8,2);
    $rel->fecha = $dia."-".$mes."-".$año;

    $añoG = substr($rel->fecha_generacion,0,4);
    $mesG = substr($rel->fecha_generacion,5,2);
    $diaG = substr($rel->fecha_generacion,8,2);
    //$horaG = substr($rel->fecha_generacion,11,2).":".substr($rel->fecha_generacion,14,2).":".substr($rel->fecha_generacion,17,2);;
    $rel->fecha_generacion = $diaG."-".$mesG."-".$añoG;//." ".$horaG;

    $detalles = array();
    $progresivos = array();

    foreach($layout_parcial->detalles as $detalle){
      $det = new \stdClass();
      $det->nro_admin = $detalle->maquina->nro_admin;
      $det->isla = $detalle->maquina->isla->nro_isla;
      $det->marca = $detalle->maquina->marca;
      $det->nro_serie = $detalle->maquina->nro_serie;
      
      if($detalle->maquina->id_pack!=null){
        $pack=PackJuego::find($detalle->maquina->id_pack);
        $juego_activo=$detalle->maquina->juego_activo;
        $prefijo=$pack->prefijo;
        $nombre_juego_activo= $detalle->maquina->juego_activo->nombre_juego;
        $juego_activo->nombre_juego= "Paquete-Juegos: " . $pack->identificador; 
      }else{
        $juego_activo=$detalle->maquina->juego_activo;
      }
      $det->juego =$juego_activo;
      $det->denominacion = $detalle->denominacion;//vacio al momento de carga
      $det->porcentaje_devolucion = $detalle->porcentaje_devolucion;//vacio al momento de carga
      $det->diferencias = $detalle->campos_con_diferencia;
      $progresivo = $this->obtenerProgresivoFormateado($detalle->maquina);//retorna progresivo y sus niveles, formato para planilla
      $progresivo != null ? $progresivos[] = $progresivo : null;
      $detalles[] = $det;
    };

    $view = View::make('planillaLayoutParcialEdit', compact('detalles','rel','progresivos'));
    $dompdf = new Dompdf();
    $dompdf->set_paper('A4', 'portrait');
    $dompdf->loadHtml($view->render());
    $dompdf->render();
    $font = $dompdf->getFontMetrics()->get_font("helvetica", "regular");
    $dompdf->getCanvas()->page_text(20, 815, (($rel->nro_relevamiento != null) ? $rel->nro_relevamiento : "AUX")."/".$rel->casinoCod."/".$rel->sector."/".$rel->fecha, $font, 10, array(0,0,0));
    $dompdf->getCanvas()->page_text(515, 815, "Página {PAGE_NUM} de {PAGE_COUNT}", $font, 10, array(0,0,0));
    return $dompdf;

  }

  public function obtenerProgresivoFormateado($maquina){
    if($maquina->pozo != null){
      $det_progresivo = new \stdClass();
      $progresivo = $maquina->pozo->niveles_progresivo[0]->progresivo;
      $niveles = array();
      foreach ($maquina->pozo->niveles_progresivo as $nivel){
          $det_nivel_progresivo = new \stdClass();
          $nivel->pivot->base == null ? $det_nivel_progresivo->base = $nivel->base : $nivel->pivot->base;
          $det_nivel_progresivo->porc_visible = $nivel->porc_visible;
          $det_nivel_progresivo->porc_oculto  = $nivel->porc_oculto;
          $det_nivel_progresivo->nombre_nivel = $nivel->nombre_nivel;
          $det_nivel_progresivo->nro_nivel = $nivel->nro_nivel;
          $niveles[] = $det_nivel_progresivo;
      };
      $det_progresivo->nro_admin = $maquina->nro_admin;
      $det_progresivo->nombre_progresivo = $progresivo->nombre_progresivo;
      $det_progresivo->maximo = $progresivo->maximo;
      $det_progresivo->porc_recuperacion = $progresivo->porc_recuperacion;
      $det_progresivo->individual = $progresivo->individual;
      $det_progresivo->niveles = $niveles;
    }else{
      $det_progresivo = null;
    };
    return $det_progresivo;

  }

  public function generarPlanillaLayoutParcial($id_layout_parcial){
    $layout_parcial = LayoutParcial::find($id_layout_parcial);
    if($layout_parcial->subrelevamiento != null){
      $ruta = "LayoutParcial-".$layout_parcial->sector->casino->codigo."-".$layout_parcial->sector->descripcion."-".$layout_parcial->fecha."(".$layout_parcial->subrelevamiento.")".".pdf";
    }else{
      $ruta = "LayoutParcial-".$layout_parcial->sector->casino->codigo."-".$layout_parcial->sector->descripcion."-".$layout_parcial->fecha.".pdf";
    }
    $dompdf = $this->crearPlanillaLayoutParcial($layout_parcial);

    return $dompdf->stream($ruta, Array('Attachment'=>0));

  }

  public function descargarLayoutParcialZip($nombre){
    $file = public_path()."/".$nombre;
    $headers = array('Content-Type' => 'application/octet-stream',);
    return response()->download($file,$nombre,$headers)->deleteFileAfterSend(true);
  }

  /*************LAYOUT TOTAL*************/

  // buscarLayoutsTotales retorna los layout total filtrados por los parametros de busqueda
  public function buscarLayoutsTotales(Request $request){
    $reglas = Array();
    $usuario = UsuarioController::getInstancia()->buscarUsuario(session('id_usuario'))['usuario'];
    foreach ($usuario->casinos as $casino) {
      $casinos[] = $casino->id_casino;
    }

    if(!empty($request->fecha)){
      $reglas[]=['layout_total.fecha', '=', $request->fecha];
    }
    if($request->casino!=0){
      $reglas[]=['casino.id_casino', '=', $request->casino];
    }
    if($request->sector != 0){
      $reglas[]=['sector.id_sector', '=', $request->sector];
    }
    if(!empty($request->estadoRelevamiento)){
      $reglas[] = ['estado_relevamiento.id_estado_relevamiento' , '=' , $request->estadoRelevamiento];
    }

    $sort_by = $request->sort_by;
    $resultados=DB::table('layout_total')
    ->select('layout_total.*' , 'casino.nombre as casino' , 'estado_relevamiento.descripcion as estado','sector.descripcion as sector')
    ->join('casino' , 'layout_total.id_casino' , '=' , 'casino.id_casino')
    ->leftJoin('sector','sector.id_sector','=','layout_total.id_sector')//En principio, el sector deberia pertenercerle al casino... eso se valida en la creacion
    ->join('estado_relevamiento' , 'layout_total.id_estado_relevamiento' , '=' , 'estado_relevamiento.id_estado_relevamiento')
    ->when($sort_by,function($query) use ($sort_by){
                    return $query->orderBy($sort_by['columna'],$sort_by['orden']);
                })
    ->where($reglas)
    ->whereIn('layout_total.id_casino',$casinos)
    ->where('backup' , '=', 0)->paginate($request->page_size);

    foreach ($resultados as $resultado) {
      $resultado->fecha = strftime("%d %b %Y", strtotime($resultado->fecha));
    }

    return $resultados;
  }

  public function buscarTodoTotal(){
    $usuario = UsuarioController::getInstancia()->buscarUsuario(session('id_usuario'))['usuario'];
    $casinos = $usuario->casinos;
    $estados = EstadoRelevamiento::all();
    UsuarioController::getInstancia()->agregarSeccionReciente('Layout Total' , 'layout_total');
    return view('seccionLayoutTotal', ['casinos' => $casinos , 'estados' => $estados,'usuario'=>$usuario,'codigos' => LayoutTotalCodigo::all()]);
  }

  private function aux_buscarLTs($backup,$id_casino,$id_sector,$turno,$fecha,$fecha_gen=null){
    $lts = LayoutTotal::where([
      ['backup',$backup],['id_casino',$id_casino],['fecha',$fecha],['turno',$turno]
    ]);

    if(empty($id_sector)){
      $lts = $lts->whereNull('id_sector');
    }
    else{
      $lts = $lts->where('id_sector',$id_sector);
    }

    if(!empty($fecha_gen)){
      $lts = $lts->where('fecha_generacion','LIKE',$fecha_gen.'%');
    }
    return $lts;
  }

  private function aux_eliminarLTs_y_crearLT($backup,$id_casino,$id_sector,$turno,$fecha,$fecha_gen=null){
    $fgen = null;
    if(!empty($fecha_gen)){//Viene con formato Y-m-d H:i:s
      $fgen = explode(" ",$fecha_gen)[0];
    }
    $lts = $this->aux_buscarLTs($backup,$id_casino,$id_sector,$turno,$fecha,$fgen)->get();

    foreach($lts as $lt){
      foreach($lt->observaciones_islas as $obs){
        $obs->delete();
      }
      $lt->delete();
    }

    $lt = new LayoutTotal;
    $lt->backup           = $backup;
    $lt->id_casino        = $id_casino;
    if(!empty($id_sector)){
      $lt->id_sector      = $id_sector;
    }
    $lt->turno            = $turno;
    $lt->fecha            = $fecha;
    $lt->fecha_generacion = $fecha_gen;
    $lt->nro_layout_total = $backup == 1? null : DB::table('layout_total')->max('nro_layout_total') + 1;
    $lt->estado_relevamiento()->associate(1);
    $lt->save();

    if(empty($id_sector)){
      $sectores = Casino::find($id_casino)->sectores;
    }
    else{
      $sectores = [Sector::find($id_sector)];
    }

    foreach($sectores as $s){//Le asigno los sectores
      $islas = $s->islas;
      foreach($islas as $i){
        //Se agregan para relevar las con int tecnica x pedido del ADM de SFE
        if($i->cantidad_maquinas_y_int_tecnica > 0){
          $obs = new LayoutTotalIsla;
          $obs->id_layout_total = $lt->id_layout_total;
          $obs->id_isla = $i->id_isla;
          $obs->maquinas_observadas = null;
          $obs->save();
        }
      }
    }

    $dompdf = $this->crearPlanillaLayoutTotal($lt);//Le creo el PDF y lo retorno
    $output = $dompdf->output();
    $codigo_cas = $lt->casino->codigo;
    if(!empty($id_sector)){
      $codigo_cas .= '-'.Sector::find($id_sector)->descripcion;
    }
    $ruta = "LayoutTotal-{$codigo_cas}-{$fecha}.pdf";
    file_put_contents($ruta, $output);
    return $ruta;
  }

  public function crearLayoutTotal(Request $request){//Crea el relevamiento y los backup
    $controller = $this;
    $fecha_hoy = date("Y-m-d"); 
    Validator::make($request->all(),[
        'id_casino' => 'required|exists:casino,id_casino',
        'id_sector' => 'nullable|exists:sector,id_sector',
        'turno' => 'required|numeric|between:1,4'
    ], array(), self::$atributos)->after(function($validator) use (&$controller,$fecha_hoy){
      if($validator->errors()->any()) return;
      $id_casino = $validator->getData()['id_casino'];
      $turno = $validator->getData()['turno'];
      $id_sector = $validator->getData()['id_sector'];

      $user = UsuarioController::getInstancia()->buscarUsuario(session('id_usuario'))['usuario'];
      if(!$user->usuarioTieneCasino($id_casino)){
        $validator->errors()->add('id_casino','El usuario no tiene acceso a este casino');
      }
      if(!empty($id_sector)){
        if(Sector::find($id_sector)->id_casino != $id_casino){
          $validator->errors()->add('id_sector','El sector no pertenece al casino');
        }
      }

      $ya_existe = $controller->aux_buscarLTs(0,$id_casino,$id_sector,$turno,$fecha_hoy)
      ->whereIn('id_estado_relevamiento',[2,3,4])//Cargando, Finalizado o Visado
      ->get()->count() > 0;
      if($ya_existe){
        $validator->errors()->add('error','El control de layout para esa fecha ya está en carga y no se puede reemplazar.');
      }
    })->validate();

    $nombreZip = null;
    DB::beginTransaction();
    try{
      $fecha_gen = date('Y-m-d h:i:s', time());  
      $rutas = [];
      $rutas[] = $this->aux_eliminarLTs_y_crearLT(0,$request->id_casino,$request->id_sector,$request->turno,$fecha_hoy,$fecha_gen);
      $fecha_backup = $fecha_hoy; // Armamos los relevamientos para backup
      for($i = 1; $i <= self::$cant_dias_backup_relevamiento; $i++){
        $fecha_backup = date("Y-m-d", strtotime($fecha_backup . " +1 days"));
        $rutas[] = $this->aux_eliminarLTs_y_crearLT(1,$request->id_casino,$request->id_sector,$request->turno,$fecha_backup,$fecha_gen);
      }

      // crear zip con los backup
      $codigo_cas = Casino::find($request->id_casino)->codigo;
      if(!empty($request->id_sector)){
        $codigo_cas .= '-'.Sector::find($request->id_sector)->descripcion;
      }
      $nombreZip = "Planillas-{$codigo_cas}-{$fecha_hoy} al {$fecha_backup}.zip";
      Zipper::make($nombreZip)->add($rutas)->close();
      File::delete($rutas);
      DB::commit();
    }
    catch(Exception $e){
      DB::rollBack();
      throw $e;
    }
    return [ 'nombre_zip' => $nombreZip];
  }


  // crearPlanillaLayoutTotal crea planilla de layout total
  // tiene en cuenta la cantidad de maquinas habilitadas dentro de una isla
  // se guardan los totales y el front decide cuando mostrarlos
  private function crearPlanillaLayoutTotal($layout_total, $cargado=false){// CREAR Y GUARDAR RELEVAMIENTO
    $rel= new \stdClass();
    $rel->nro_relevamiento = $layout_total->nro_layout_total;
    $rel->casinoCod = $layout_total->casino->codigo;
    $rel->casinoNom = $layout_total->casino->nombre;
    $rel->turno = $layout_total->turno;
    $rel->fecha = $layout_total->fecha;
    $rel->fecha_ejecucion = $layout_total->fecha_ejecucion;
    $rel->fecha_generacion = $layout_total->fecha_generacion;
    $rel->total_activas = $layout_total->total_activas;
    $rel->total_inactivas = $layout_total->total_inactivas;

    $año = substr($rel->fecha,0,4);
    $mes = substr($rel->fecha,5,2);
    $dia = substr($rel->fecha,8,2);
    $rel->fecha = $dia."-".$mes."-".$año;

    $añoG = substr($rel->fecha_generacion,0,4);
    $mesG = substr($rel->fecha_generacion,5,2);
    $diaG = substr($rel->fecha_generacion,8,2);
    $rel->fecha_generacion = $diaG."-".$mesG."-".$añoG;//." ".$horaG;

    $progresivos = array();

    //cargado significa que entreo luego que se finalizo, en ese punto solo lo puede ver el administrador
    //mostrar maquinas es una bandera para mostrar en el formulario la cantidad de maquinas
    $observacion = '';
    if($cargado){
      $maquinas_apagadas = $layout_total->detalles;
      $mostrar_maquinas  = true;
      if(!is_null($layout_total->observacion_validacion)){
        $observacion = $layout_total->observacion_validacion;
      }
    }else{
      $maquinas_apagadas = array();
      $mostrar_maquinas  = false;
    }

    $sectores = [];
    if(!empty($layout_total->sector)){
      $sectores=[$layout_total->sector];
    }
    else{
      $sectores=$layout_total->casino->sectores;
    }

    $q_ordenar_islas = 'isla.nro_isla asc';
    $f_ordenar_sectores = function($a,$b){
      return $a->descripcion <=> $b->descripcion;
    };
    if($layout_total->id_casino == 3){
      $q_ordenar_islas = 'isla.orden asc,isla.nro_isla asc';
      $f_ordenar_sectores = function($a,$b){
        if(count($a->islas) == 0) return 0;
        if(count($b->islas) == 0) return 1;
        return $a->islas[0]->orden <=> $b->islas[0]->orden;
      };
    }

    $detalles = array();
    foreach($sectores as $sector){
      $det = new \stdClass();
      $det->descripcion = $sector->descripcion;
      $det->islas = $layout_total->islas()
      ->where('id_sector',$sector->id_sector)
      ->orderByRaw($q_ordenar_islas)->get();

      if(count($det->islas)==0){//Si es un layout viejo, no tiene islas asociadas, les devuelvo todas las del sector nomas.
        //Por alguna razon el metodo islas() en la clase Sector ordena por nro_isla... lo hago a pata no quiero cambiarlo
        //porque capaz rompo algo - Octavio Garcia Aguirre 17 Feb 2022
        $det->islas = $sector->hasMany('App\Isla','id_sector','id_sector')->orderByRaw($q_ordenar_islas)->get();
        foreach($det->islas as &$i){
          $i->pivot = new \stdClass();
          $i->pivot->maquinas_observadas = NULL;
        }
      }

      $detalles[] = $det;
    };
    usort($detalles,$f_ordenar_sectores);
    $codigos = LayoutTotalCodigo::all();
    $view = View::make('planillaLayoutTotalEdit', compact('rel','detalles','maquinas_apagadas','mostrar_maquinas','observacion','codigos'));
    $dompdf = new Dompdf();
    $dompdf->set_paper('A4', 'landscape');
    $dompdf->loadHtml($view->render());
    $dompdf->render();
    $font = $dompdf->getFontMetrics()->get_font("helvetica", "regular");
    $dompdf->getCanvas()->page_text(20, 575, (($rel->nro_relevamiento != null) ? $rel->nro_relevamiento : "AUX")."/".$rel->casinoCod."/".$rel->fecha, $font, 10, array(0,0,0));
    $dompdf->getCanvas()->page_text(125, 575, "(*) Código de observación | (**) Progresivo Bloqueado", $font, 8, array(0,0,0));
    $dompdf->getCanvas()->page_text(765, 575, "Página {PAGE_NUM} de {PAGE_COUNT}", $font, 10, array(0,0,0));
    return $dompdf;

  }

  public function generarPlanillaLayoutTotales($id_layout_total){
    $layout_total = LayoutTotal::find($id_layout_total);
    $dompdf = $this->crearPlanillaLayoutTotal($layout_total);

    $error = $this->verificarAccesoLayoutTotal($layout_total);
    if(!is_null($error)) return $error;

    $ruta = "LayoutTotal- '. $layout_total->casino->descripcion  . '-' . $layout_total->fecha . '.pdf";

    return $dompdf->stream($ruta, Array('Attachment'=>0));
  }

  public function generarPlanillaLayoutTotalesCargado($id_layout_total){
    $layout_total = LayoutTotal::find($id_layout_total);

    $error = $this->verificarAccesoLayoutTotal($layout_total);
    if(!is_null($error)) return $error;

    $dompdf = $this->crearPlanillaLayoutTotal($layout_total, true); // parametro true es porque ya esta cargado, si no mando este es falso pro defecto
    $ruta = "LayoutTotal- '. $layout_total->casino->descripcion  . '-' . $layout_total->fecha . '.pdf";

    return $dompdf->stream($ruta, Array('Attachment'=>0));
  }

  public function descargarLayoutTotalZip($nombre){
    $user = UsuarioController::getInstancia()->buscarUsuario(session('id_usuario'))['usuario'];
    if(!$user->es_administrador && !$user->es_superusuario && !$user->es_fiscalizador){
      return $this->errorOut([ 'privilegios' => ['El usuario no puede realizar esa accion.'] ]);
    }
    $file = public_path()."/".$nombre;
    $headers = array('Content-Type' => 'application/octet-stream',);
    return response()->download($file,$nombre,$headers)->deleteFileAfterSend(true);
  }

  public function guardarLayoutTotal(Request $request){
    return $this->cargarLayoutTotal($request,False);
  }
  // cargarLayoutTotal se carga el layout total solo con los valores de las mtm
  // consideradas con algun tipo de fallo
  public function cargarLayoutTotal(Request $request,$cargando=True){
    if($cargando){
        Validator::make($request->all(),[
          'id_layout_total' => 'required|exists:layout_total,id_layout_total',
          'id_fiscalizador_toma' => 'required|exists:usuario,id_usuario',
          'fecha_ejecucion' => 'required|date|before:tomorrow',
          //arreglo con las maquinas apagadas
          'maquinas' => 'nullable' ,
          'maquinas.*.id_sector' => 'required|string',
          'maquinas.*.nro_isla' => 'required|exists:isla,nro_isla',
          'maquinas.*.nro_admin' => 'required|integer',
          'maquinas.*.id_maquina' => 'required|exists:maquina,id_maquina',
          'maquinas.*.co' => 'nullable|string',//codigo
          'maquinas.*.pb' => 'required|in:1,0',//producido bloqueado
          'observacion_fiscalizacion' =>  'nullable|string',
          'confirmacion' => 'required|in:0,1',
          'islas' => 'nullable',
          'islas.*.id_isla' => 'required|integer|exists:isla,id_isla',
          'islas.*.maquinas_observadas' => 'nullable|integer|min:0'
      ], array(), self::$atributos)->after(function($validator){
          $layout = LayoutTotal::find($validator->getData()['id_layout_total']);
          if($validator->getData()['confirmacion'] == 0){
            if(isset($validator->getData()['maquinas'])){
              foreach ($validator->getData()['maquinas'] as $i => $maquina) {
                $maquinas =  Maquina::join('isla' , 'maquina.id_isla' , '=' , 'isla.id_isla')
                ->join('sector' , 'sector.id_sector' ,'=' , 'isla.id_sector')
                ->where([['maquina.id_casino' , $layout->id_casino] , ['nro_admin' , $maquina['nro_admin']] , ['sector.id_sector', $maquina['id_sector']] , ['isla.nro_isla' , $maquina['nro_isla']] ])
                ->get();

                if($maquinas->count() != 1){
                  $validator->errors()->add('maquinas.' . $i . '.no_existe' ,'No existe maquina con el numero de administración ' . $maquina['nro_admin'] . ' en la isla y sector elegidos.');
                }
              }
            }
          }
      })->validate();
    }

    $layout_total = LayoutTotal::find($request->id_layout_total);
    $error = $this->verificarAccesoLayoutTotal($layout_total);
    if(!is_null($error)) return $error;

    $estado = $layout_total->id_estado_relevamiento;
    if( $estado == 1 || $estado == 2 ){//si el estado es GENERADO o CARGANDO
      DB::beginTransaction();
      try{
        $layout_total->id_usuario_fiscalizador = $request->id_fiscalizador_toma;
        $layout_total->id_usuario_cargador = session('id_usuario'); //usuario que carga
        $layout_total->fecha_ejecucion =  $request->fecha_ejecucion;
        $layout_total->observacion_fiscalizacion =  $request->observacion_fiscalizacion;
  
        foreach($layout_total->detalles as $d){//Borro los viejos que tenga cargado.
          $d->delete();
        }
        if(isset($request->maquinas)){
          foreach ($request->maquinas as $maquina_apagadas) {
            $detalle  = new DetalleLayoutTotal();
            $maquina = Maquina::find($maquina_apagadas['id_maquina']);
            if(is_null($maquina) && !$cargando) continue;//Si esta GUARDANDO, ignoro la maquina
            $detalle->id_maquina = $maquina->id_maquina;
            $detalle->descripcion_sector= Sector::find($maquina_apagadas['id_sector'])->descripcion;
            $detalle->nro_isla = $maquina_apagadas['nro_isla'];
            $detalle->co = $maquina_apagadas['co'];
            $detalle->pb = $maquina_apagadas['pb'];
            $detalle->nro_admin = $maquina_apagadas['nro_admin'];
            $detalle->id_layout_total = $layout_total->id_layout_total;
            $detalle->save();
            //  DISPARAR MOVIEMIENTO - EVENTUALIDAD
          }
        }

        if(isset($request->islas)){
          foreach($request->islas as $i){
            $lti = LayoutTotalIsla::where([
              ['id_layout_total','=',$layout_total->id_layout_total],
              ['id_isla','=',$i['id_isla']]
            ])->get();
            if($lti->count() == 0) continue;
            $lti = $lti->first();
            $newval = array_key_exists('maquinas_observadas',$i)? $i['maquinas_observadas'] : null;
            $lti->maquinas_observadas = $newval;
            $lti->save();
          }
        }
        
        if(!$cargando) $layout_total->id_estado_relevamiento = 2; 
        else $layout_total->id_estado_relevamiento = 3; //finalizado la carga
        $layout_total->save();
      }
      catch(Exception $e){
        DB::rollBack();
        throw $e;
      }
      DB::commit();
      return ['codigo' => 200];
    } 
    else{
      return $this->errorOut(['estado' => ['El estado del layout no es correspondiente con la accion']]);
    }

  }


  public function obtenerLayoutTotal($id){
    $layout_total= LayoutTotal::find($id);
    $errors = $this->verificarAccesoLayoutTotal($layout_total,False);
    if(!is_null($errors)) return $errors;

    return ['layout_total' => $layout_total ,
            'total_activas' => $layout_total->total_activas,
            'total_inactivas' => $layout_total->total_inactivas,
            'sectores' => $layout_total->casino->sectores,
            'casino' => $layout_total->casino,
            'usuario_cargador' => $layout_total->usuario_cargador ,
            'usuario_fiscalizador' => $layout_total->usuario_fiscalizador,
            'detalles' => $layout_total->detalles];
  }

  // validarLayoutTotal cambia el estado del relevamiento
  public function validarLayoutTotal(Request $request){
    Validator::make($request->all(),[
        'observacion_validacion' => 'required|string',
        'id_layout_total' => 'required|exists:layout_total,id_layout_total',

    ], array(), self::$atributos)->after(function($validator){

        $layout = LayoutTotal::find($validator->getData()['id_layout_total']);
        if($layout->backup == 1){
          $validator->errors()->add('layout_backup','Error. El layout a validar es de backup');
        }

    })->validate();

    $layout_total = LayoutTotal::find($request->id_layout_total);
    $errors = $this->verificarAccesoLayoutTotal($layout_total,False);
    if(!is_null($errors)) return $errors;

    $layout_total->observacion_validacion = $request->observacion_validacion;
    $layout_total->id_estado_relevamiento =  EstadoRelevamiento::where('descripcion' , 'Visado')->get()[0]->id_estado_relevamiento;

    $layout_total->save();

    return ['layout' => $layout_total];
  }

  
  public function usarLayoutTotalBackup(Request $request){
    $cont = $this;
    $rel_backup = null;
    Validator::make($request->all(),[
        'id_casino' => 'required|exists:casino,id_casino',
        'id_sector' => 'nullable|exists:sector,id_sector',
        'fecha' => 'required|date',
        'fecha_generacion' => 'required|date',
        'turno' => 'required|integer',
    ], array(), self::$atributos)->after(function($validator) use (&$cont,&$rel_backup){
      if($validator->errors()->any()) return;
      $id_casino = $validator->getData()['id_casino'];
      $id_sector = $validator->getData()['id_sector'];
      if(!empty($id_sector)){
        if(Sector::find($id_sector)->id_casino != $id_casino){
          $validator->errors()->add('id_sector','El sector no pertenece al casino');
        }
      }
      $user = UsuarioController::getInstancia()->buscarUsuario(session('id_usuario'))['usuario'];
      if(!$user->usuarioTieneCasino($id_casino)){
        $validator->errors()->add('id_casino','El usuario no puede acceder a ese casino.');
      }
      if(!$user->es_administrador && !$user->es_superusuario && !$user->es_fiscalizador){
        $validator->errors()->add('privilegios','El usuario no puede realizar esa accion.');
      }

      $turno = $validator->getData()['turno'];
      $fecha = $validator->getData()['fecha'];
      $ya_existe = $cont->aux_buscarLTs(0,$id_casino,$id_sector,$turno,$fecha)->get()->count() > 0;
      if($ya_existe){
        $validator->errors()->add('error','Ya existen relevamientos con los valores indicados.');
        return;
      }

      $fecha_generacion = $validator->getData()['fecha_generacion'];
      $rel_backup = $cont->aux_buscarLTs(1,$id_casino,$id_sector,$turno,$fecha,$fecha_generacion)->get()->first();
      if(empty($rel_backup)){
        $validator->errors()->add('error','No se encontraron relevamientos sin sistema con los valores indicados.');
      }
    })->validate();

    DB::transaction(function() use ($request,&$rel_backup){
      $rel_backup->backup = 0;
      $rel_backup->save();
    });
    return ['codigo' => 200];
  }

  public function islasLayoutTotal(Request $request,$id_layout_total){
    $layout = LayoutTotal::find($id_layout_total);
    if(is_null($layout)){
      return $this->errorOut([ 'id_layout_total' => ['El layout total no existe.'] ]);
    }
    $user = UsuarioController::getInstancia()->buscarUsuario(session('id_usuario'))['usuario'];
    if(!$user->usuarioTieneCasino($layout->id_casino)){
      return $this->errorOut([ 'casino' => ['El usuario no puede acceder a ese casino.'] ]);
    }
    
    $islas = $layout->islas;
    $sectores = [];
    $total_observadas = 0;
    $total_sistema = 0;
    foreach($islas as $i){
      $i2 = $i->toArray();      
      $observadas = $i->pivot->maquinas_observadas;
      $sistema = $i2['cantidad_maquinas'];
      $i2['maquinas_observadas'] = $observadas;

      $sector_bd = $i->sector;
      $id_sector = $sector_bd->id_sector;
      if(!array_key_exists($id_sector,$sectores)){
        $sectores[$id_sector]=$sector_bd->toArray();
        $sectores[$id_sector]['islas']=[];
        $sectores[$id_sector]['total_observadas']=0;
        $sectores[$id_sector]['total_sistema']=0;
      }

      $sectores[$id_sector]['islas'][]=$i2;
      $sectores[$id_sector]['total_observadas']+=(is_null($observadas)? 0 : $observadas);
      $sectores[$id_sector]['total_sistema']+=(is_null($sistema)? 0: $sistema);
    }
    
    $f_ordenar_islas = function($a,$b){
      return $a['nro_isla']<=>$b['nro_isla'];
    };
    $f_ordenar_sectores = function($a,$b){
      return $a['descripcion']<=>$b['descripcion'];
    };
    if($layout->id_casino == 3){
      $f_ordenar_islas = function($a,$b){
        if($a['orden'] != $b['orden']) return $a['orden']<=>$b['orden'];
        return $a['nro_isla']<=>$b['nro_isla'];
      };
      $f_ordenar_sectores = function($a,$b){
        //En principio no deberia haber sectores sin islas. Ordeno por la primer isla, que deberia ser la mas alta prioridad del sector
        return $a['islas'][0]['orden'] <=> $b['islas'][0]['orden'];
      };
    }

    $ret = [];
    foreach($sectores as $s){
      usort($s['islas'],$f_ordenar_islas);
      $ret[]=$s;
    }
    usort($ret,$f_ordenar_sectores);
    return $ret;
  }

  private function errorOut($map){
    return response()->json($map,422);
  }

  private function verificarAccesoLayoutTotal($layout_total,$permitir_fiscal = True){
    if(is_null($layout_total)){
      return $this->errorOut(['id_layout_total' => ['No existe el layout']]);
    }

    $user = UsuarioController::getInstancia()->buscarUsuario(session('id_usuario'))['usuario'];
    if(!$user->usuarioTieneCasino($layout_total->id_casino)){
      return $this->errorOut([ 'casino' => ['El usuario no puede acceder a ese casino.'] ]);
    }

    return null;
  }

  public function eliminarLayoutTotal($id_layout_total){
    $layout = LayoutTotal::find($id_layout_total);
    $error = $this->verificarAccesoLayoutTotal($layout,False);
    if(!is_null($error)) return $error;
    DB::transaction(function() use($layout){
      LayoutTotalIsla::where('id_layout_total',$layout->id_layout_total)->delete();
      DetalleLayoutTotal::where('id_layout_total',$layout->id_layout_total)->delete();
      $layout->delete();
    });
    return 200;
  }

  public function obtenerMTMsEnIsla($id_casino,$nro_isla,$nro_admin){
    $user = UsuarioController::getInstancia()->buscarUsuario(session('id_usuario'))['usuario'];
    $cids = [];
    foreach($user->casinos as $c) $cids[] = $c->id_casino;
    $prioridad_en_isla = '(
    case
    when (isla.nro_isla = '.$nro_isla.') then 0
    when (isla.nro_isla LIKE "'.$nro_isla.'%") then 1
    else 2
    end)';
    $maquinas = DB::table('maquina')
    ->join('isla','isla.id_isla','=','maquina.id_isla')
    ->where('maquina.nro_admin','LIKE',$nro_admin.'%')
    ->whereIn('maquina.id_casino',$cids)
    ->whereNull('isla.deleted_at')->whereNull('maquina.deleted_at')
    ->orderByRaw($prioridad_en_isla.' asc, maquina.nro_admin asc');
    if(!empty($id_casino)) $maquinas->where('maquina.id_casino','=',$id_casino);
    return ['maquinas' => $maquinas->get()];
  }
}
