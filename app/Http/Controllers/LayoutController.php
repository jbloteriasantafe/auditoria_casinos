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

  private static $atributos = [
  ];
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
    $casinos = $usuario->casinos;
    $estados = EstadoRelevamiento::all();
    UsuarioController::getInstancia()->agregarSeccionReciente('Layout Parcial' , 'layout_parcial');

    return view('seccionLayoutParcial', ['casinos' => $casinos , 'estados' => $estados]);
  }
  // obtenerLayoutParcial retorna la informacion para la carga del layout, 
  // obtiene los detalles de layout
  // considera si una maquina dentro del layout implementa paquete de juegos, retornando una bandera y los juegos habilitados
  public function obtenerLayoutParcial($id){
      $layout_parcial = LayoutParcial::find($id);

      // $contador_horario = ContadorHorario::where([['fecha','=',$layout_parcial->fecha],['id_casino','=',$layout_parcial->sector->casino->id_casino]])->first();
      // $layout_parcial->fecha = date("d-M-Y", strtotime($layout_parcial->fecha));

      $detalles = array();
      foreach($layout_parcial->detalles as $detalle){
        $linea = new \stdClass();
        $maquina= Maquina::find($detalle->id_maquina);
        $linea->nro_admin = ['correcto' => true, 'valor' => $maquina->nro_admin , 'valor_antiguo' => ''] ;
        $linea->nro_isla = ['correcto' => true, 'valor' => $maquina->isla->nro_isla, 'valor_antiguo' => ''] ;
        $linea->marca = ['correcto' => true, 'valor' => $maquina->marca, 'valor_antiguo' => ''] ;
        if($maquina->id_tipo_maquina != null){
          $linea->tipo = ['correcto' => true, 'valor' => $maquina->tipoMaquina->descripcion, 'valor_antiguo' => ''] ;
        }else{
          $linea->tipo = ['correcto' => true, 'valor' => '-', 'valor_antiguo' => ''] ;
        }
        $linea->juego = ['correcto' => true, 'valor' => $maquina->juego_activo->nombre_juego, 'valor_antiguo' => ''] ;
        

        if($maquina->id_pack!=null){
        $pack=PackJuego::find($maquina->id_pack);
        $linea->tiene_pack_bandera=true;
        $juegos_pack_habilitados=array();
        foreach($maquina->juegos as $j){
          if($j->pivot->habilitado!=0){
            array_push( $juegos_pack_habilitados,$j);
          }
        }
        $linea->juegos_pack=$juegos_pack_habilitados;
        }else{
          $linea->tiene_pack_bandera=false;
        }
        //refactor para tomar el pack correspondiente a la tabla asociacion con maquina y juego
        // if(count($maquina->juego_activo->pack)>0){
        //   $linea->tiene_pack_bandera=true;
        //   $linea->juegos_pack=$maquina->juego_activo->pack[0]->juegos;
        // }else{
        //   $linea->tiene_pack_bandera=false;
        // }
        
        $linea->nro_serie = ['correcto' => true, 'valor' => $maquina->nro_serie, 'valor_antiguo' => ''] ;
        $linea->id_maquina =  $maquina->id_maquina;
        $progresivo = ProgresivoController::getInstancia()->obtenerProgresivoPorIdMaquina($maquina->id_maquina);

        if($progresivo['progresivo'] != null){
          $niveles = [];
          $linea->progresivo = new \stdClass();
          $linea->progresivo->individual =['correcto' => true, 'valor' =>  $progresivo['progresivo']->individual, 'valor_antiguo' => ''] ;
          $linea->progresivo->nombre_progresivo =['correcto' => true, 'valor' =>  $progresivo['progresivo']->nombre_progresivo, 'valor_antiguo' => ''] ;
          $linea->progresivo->maximo = ['correcto' => true, 'valor' => $progresivo['progresivo']->maximo, 'valor_antiguo' => ''] ;
          $linea->progresivo->porc_recuperacion = ['correcto' => true, 'valor' => $progresivo['progresivo']->porc_recuperacion, 'valor_antiguo' => ''] ;
          $linea->progresivo->id_progresivo = $progresivo['progresivo']->id_progresivo;

          foreach ($progresivo['niveles'] as $nivel) {
            $nuevo_nivel = new \stdClass();
            $base = $nivel['pivot_base'] != null ? $nivel['pivot_base'] : $nivel['nivel']->base;
            $nuevo_nivel->nombre_nivel = ['correcto' => true, 'valor' => $nivel['nivel']->nombre_nivel, 'valor_antiguo' => ''] ;
            $nuevo_nivel->nro_nivel = ['correcto' => true, 'valor' => $nivel['nivel']->nro_nivel, 'valor_antiguo' => ''] ;
            $nuevo_nivel->id_nivel = $nivel['nivel']->id_nivel_progresivo;
            $nuevo_nivel->base = ['correcto' => true, 'valor' => $base, 'valor_antiguo' => ''] ;
            $nuevo_nivel->porc_visible = ['correcto' => true, 'valor' => $nivel['nivel']->porc_visible, 'valor_antiguo' => ''] ;
            $nuevo_nivel->porc_oculto =['correcto' => true, 'valor' => $nivel['nivel']->porc_oculto , 'valor_antiguo' => '']  ;
            $niveles [] = $nuevo_nivel;
          }
          $linea->niveles = $niveles;
        }else{
          $linea->niveles = null;
          $linea->progresivo= null;
        }
        $detalles[] = $linea;
      }

      return ['layout_parcial' => $layout_parcial,
              'casino' => $layout_parcial->sector->casino->nombre,
              'id_casino' => $layout_parcial->sector->casino->id_casino,
              'sector' => $layout_parcial->sector->descripcion,
              'detalles' => $detalles,
              'usuario_cargador' => $layout_parcial->usuario_cargador,
              'usuario_fiscalizador' => $layout_parcial->usuario_fiscalizador,
            ];
  }
  // obtenerLayoutParcialValidar recupera el layout para validar, con los datos cargados en el relevamiento
  // evalua los cambios en los juegos
  // evalua el cambio de nro de serie
  // si la maquina implementa paquete de juegos, se realiza validaciones sobre los juegos activos
  public function obtenerLayoutParcialValidar($id){
      $layout_parcial = LayoutParcial::find($id);

      // $contador_horario = ContadorHorario::where([['fecha','=',$layout_parcial->fecha],['id_casino','=',$layout_parcial->sector->casino->id_casino]])->first();
      // $layout_parcial->fecha = date("d-M-Y", strtotime($layout_parcial->fecha));

      $detalles = array();
      if($layout_parcial->campos_con_diferencia  != null){

        foreach($layout_parcial->detalles as $detalle){//COMIENZO FOREACH POR DETALLE
          $linea = new \stdClass();
          $maquina= Maquina::find($detalle->id_maquina);
          $detalle_aux = $detalle->campos_con_diferencia->where('entidad', 'maquina');

          //variable auxiliar, si existio diferncia en el campo en cuestion
          $aux = $detalle_aux->where('columna', 'nro_admin');
          if($aux->count() == 1){
              foreach ($aux as $cd) {
                $linea->nro_admin = ['correcto' => false, 'valor' =>  $cd->valor, 'valor_antiguo' => $maquina->nro_admin];
              }
            }else{
              $linea->nro_admin = ['correcto' => true, 'valor' =>  $maquina->nro_admin, 'valor_antiguo' => ''];
          }

          $aux = $detalle_aux->where('columna' , 'nro_isla');
          if($aux->count() == 1){
              foreach ($aux as $cd) {
                $linea->nro_isla = ['correcto' => false, 'valor' =>  $cd->valor, 'valor_antiguo' =>  $maquina->isla->nro_isla];
              }
            }else{
              $linea->nro_isla = ['correcto' => true, 'valor' =>  $maquina->isla->nro_isla, 'valor_antiguo' => ''];
          }

          $aux = $detalle_aux->where('columna', 'marca');
          if($aux->count() == 1){
              foreach ($aux as $cd) {
                $linea->marca = ['correcto' => false, 'valor' =>  $cd->valor, 'valor_antiguo' =>  $maquina->marca];
              }
            }else{
              $linea->marca = ['correcto' => true, 'valor' =>  $maquina->marca, 'valor_antiguo' => ''];
          }

          $aux = $detalle_aux->where('columna', 'nombre_juego');
          if($aux->count() == 1){
              foreach ($aux as $cd) {
                $linea->juego = ['correcto' => false, 'valor' => $cd->valor, 'valor_antiguo' =>  $maquina->juego_activo->nombre_juego];
              }
            }else{
              $linea->juego = ['correcto' => true, 'valor' =>  $maquina->juego_activo->nombre_juego, 'valor_antiguo' => ''];
          }

          if($maquina->id_pack!=null){
            $pack=PackJuego::find($maquina->id_pack);
            $linea->tiene_pack_bandera=true;
            $juegos_pack_habilitados=array();
            foreach($maquina->juegos as $j){
              if($j->pivot->habilitado!=0){
                array_push( $juegos_pack_habilitados,$j);
              }
            }
            $linea->juegos_pack=$juegos_pack_habilitados;
            }else{
              $linea->tiene_pack_bandera=false;
            }

          // if(count($maquina->juego_activo->pack)>0){
          //   $linea->tiene_pack_bandera=true;
          //   $linea->juegos_pack=$maquina->juego_activo->pack[0]->juegos;
          // }else{
          //   $linea->tiene_pack_bandera=false;
          // }

          $aux = $detalle_aux->where('columna', 'nro_serie');
          if($aux->count() == 1){
            foreach ($aux as $cd) {
              $linea->nro_serie = ['correcto' => false, 'valor' => $cd->valor, 'valor_antiguo' =>  $maquina->nro_serie];
            }
            }else{
              $linea->nro_serie = ['correcto' => true, 'valor' =>  $maquina->nro_serie, 'valor_antiguo' => ''];
          }

          $linea->id_maquina = $maquina->id_maquina;

          $linea->denominacion = $detalle->denominacion;
          $linea->porcentaje_dev = $detalle->porcentaje_devolucion;
          $linea->no_toma = (($detalle->denominacion == null && $detalle->porcentaje_devolucion == null) ? true : false);

          //sigue misma logica que para la configuracion de la maquina

          $linea->progresivo = $this->obtenerDetallesProgresivo($maquina , $detalle);

          $linea->niveles =  $this->obtenerDetallesNivelesProgresivo($maquina , $detalle);


          $detalles[] = $linea;
        }//FIN FOREACH POR DETALLE
        }else {

          foreach($layout_parcial->detalles as $detalle){
              $linea = new \stdClass();
              $maquina= Maquina::find($detalle->id_maquina);
              //si no hubo diferencia mando todo;

              $linea->nro_admin = ['correcto' => true, 'valor' =>  $maquina->nro_admin, 'valor_antiguo' => ''];

              $linea->nro_isla = ['correcto' => true, 'valor' =>  $maquina->isla->nro_isla, 'valor_antiguo' => ''];

              $linea->marca = ['correcto' => true, 'valor' =>  $maquina->marca, 'valor_antiguo' => ''];

              $linea->juego = ['correcto' => true, 'valor' =>  $maquina->juego_activo->nombre_juego, 'valor_antiguo' => ''];

              $linea->nro_serie = ['correcto' => true, 'valor' =>  $maquina->nro_serie, 'valor_antiguo' => ''];

              $linea->progresivo = $this->obtenerDetallesProgresivo($maquina , null);

              $linea->niveles =  $this->obtenerDetallesNivelesProgresivo($maquina , null);
          }

        }


      return ['layout_parcial' => $layout_parcial,
              'casino' => $layout_parcial->sector->casino->nombre,
              'id_casino' => $layout_parcial->sector->casino->id_casino,
              'sector' => $layout_parcial->sector->descripcion,
              'detalles' => $detalles,
              'usuario_cargador' => $layout_parcial->usuario_cargador,
              'usuario_fiscalizador' => $layout_parcial->usuario_fiscalizador,
            ];
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

  public function randomMaquinas($id_sector){
    $sector = Sector::find($id_sector);//busco las islas del sector para saber que maquinas se pueden usar
    $islas = array();
    foreach($sector->islas as $isla){
      $islas [] = $isla->id_isla;
    }

    $maquinas = Maquina::whereIn('id_isla',$islas)
                       ->whereHas('estado_maquina',function($q){$q->where('descripcion','Ingreso')->orWhere('descripcion','ReIngreso');})
                       ->inRandomOrder()->take(15)->get();

    $maquinas = $maquinas->sortBy(function($maquina,$key){
      return Isla::find($maquina->id_isla)->nro_isla;
    });
    $fp = fopen('distribuicionlayout.csv', 'r');
    $apariciones = array();
    while (($line = fgets($fp)) !== false){//leer
       $a = explode(";" ,  $line);
       $apariciones[$a[0]] = (int) $a[1];
    }

    fclose($fp);

    foreach ($maquinas as $mtm) {
      if(isset($apariciones[$mtm->id_maquina])){
        $apariciones[$mtm->id_maquina] += 1;
      }else {
        $apariciones[$mtm->id_maquina] = 1;
      }
    }

    $fw = fopen('distribuicionlayout.csv', 'w');

    foreach ($apariciones as $key => $value) {
      fwrite($fw, $key .";" . $value . "\n");
    }

    fclose($fw);
    return 'final';
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

  
  // crearLayoutParcial crea layout parcial
  // elimina los backup previos, genera un ordenamiento dependiendo del casino
  // toma un random dentro de las maquinas habilitadas
  // crea la planilla y los nuevos backup a partir de este relevamiento
  public function crearLayoutParcial(Request $request){

    Validator::make($request->all(),[
        'id_sector' => 'required|exists:sector,id_sector',
        'cantidad_maquinas' => 'required|max:1000',
        'cantidad_fiscalizadores' => 'nullable|numeric|between:1,10'
    ], array(), self::$atributos)->after(function($validator){
      $relevamientos = LayoutParcial::where([['fecha',date("Y-m-d")],['id_sector',$validator->getData()['id_sector']],['backup',0],['id_estado_relevamiento',2]])->count();
      if($relevamientos > 0){
        $validator->errors()->add('layout_en_carga','El control de layout para esa fecha ya está en carga y no se puede reemplazar.');
      }
    })->validate();

    $fecha_hoy = date("Y-m-d");
    $rel_sobresescribir = LayoutParcial::where([['fecha','=',$fecha_hoy],['id_sector','=',$request->id_sector],['backup','=',0] , ['id_estado_relevamiento' ,'=' ,1]])->count();//si esta generado
    $existeLayout = $rel_sobresescribir > 0 ? 1 : 0;
    if($existeLayout==0){
    

    $fecha_hoy = date("Y-m-d"); // fecha de hoy
    $id_layouts_viejos = array();
    //me fijo si ya habia generados control layout para el dia de hoy que no sean back up, si hay los borro
    $layouts_parcial = LayoutParcial::where([['fecha',$fecha_hoy],['id_sector',$request->id_sector],['backup',0],['id_estado_relevamiento',1]])->get();
    foreach($layouts_parcial as $unControLayout){
      foreach($unControLayout->detalles as $detalle){
        $detalle->delete();
      }
      $id_layouts_viejos[] = $unControLayout->id_layout_parcial;
      $unControLayout->delete();
    }

    $sector = Sector::find($request->id_sector);//busco las islas del sector para saber que maquinas se pueden usar
    $islas = array();
    foreach($sector->islas as $isla){
      $islas [] = $isla->id_isla;
    }

    $maquinas = Maquina::whereIn('id_isla',$islas)
                       ->whereHas('estado_maquina',function($q){$q->where('descripcion','Ingreso')->orWhere('descripcion','ReIngreso');})
                       ->inRandomOrder()->take($request->cantidad_maquinas)->get();
   
                       /*
    //ordena por número de isla, recuperando la isla para saber su número
    $maquinas = $maquinas->sortBy(function($maquina,$key){
      return Isla::find($maquina->id_isla)->nro_isla;
    });*/

    //evaluo si es de rosario para ordenar por islote e isla , sino solo por isla
    $id_casino_orden=Sector::find($request->id_sector)->id_casino;
    if($id_casino_orden==3){
      $maquinas = $maquinas->sortBy(function($maquina,$key){
        $maq=Isla::find($maquina->id_isla);
         return [$maq->orden, $maq->nro_isla];
        
      });
    }else{
      $maquinas = $maquinas->sortBy(function($maquina,$key){
        return Isla::find($maquina->id_isla)->nro_isla;
      });
    };

    $arregloRutas = array();

    $layouts_finales = array();

    if($request->cantidad_fiscalizadores == 1){
        $layout_parcial = new LayoutParcial;
        $layout_parcial->nro_layout_parcial = DB::table('layout_parcial')->max('nro_layout_parcial') + 1;
        $layout_parcial->fecha = $fecha_hoy;
        $layout_parcial->fecha_generacion = date('Y-m-d h:i:s', time());
        $fecha_generacion = $layout_parcial->fecha_generacion;
        $layout_parcial->backup = 0;
        $layout_parcial->sector()->associate($sector->id_sector);
        $layout_parcial->estado_relevamiento()->associate(1);
        $layout_parcial->save();

        foreach($maquinas as $maq){
          $detalle = new DetalleLayoutParcial;
          $detalle->id_maquina = $maq->id_maquina;
          $detalle->id_layout_parcial = $layout_parcial->id_layout_parcial;
          $detalle->save();
        }

        $layouts_finales [] = $layout_parcial;
        $arregloRutas[] = $this->guardarPlanillaLayoutParcial($layout_parcial->id_layout_parcial);
    }
    else{
        $cant_por_planilla = ceil($maquinas->count()/$request->cantidad_fiscalizadores);///$request->cantidad_fiscalizadores);
        for($i = 1; $i <= $request->cantidad_fiscalizadores; $i++){
          $layout_parcial = new LayoutParcial;
          $layout_parcial->nro_layout_parcial = DB::table('layout_parcial')->max('nro_layout_parcial') + 1;
          $layout_parcial->fecha = $fecha_hoy;
          $layout_parcial->fecha_generacion = date('Y-m-d h:i:s', time());
          $fecha_generacion = $layout_parcial->fecha_generacion;
          $layout_parcial->sector()->associate($sector->id_sector);
          $layout_parcial->estado_relevamiento()->associate(1);
          $layout_parcial->sub_control = $i;
          $layout_parcial->backup = 0;
          $layout_parcial->save();
          $maquinas_subControl = $maquinas->forPage($i,$cant_por_planilla);
          foreach($maquinas_subControl as $maq){
            $detalle = new DetalleLayoutParcial;
            $detalle->id_maquina = $maq->id_maquina;
            $detalle->id_layout_parcial = $layout_parcial->id_layout_parcial;
            $detalle->save();
          }
          $layouts_finales [] = $layout_parcial;
          $arregloRutas[] = $this->guardarPlanillaLayoutParcial($layout_parcial->id_layout_parcial);
        }
    }


    $fecha_backup = $fecha_hoy; // Armamos los relevamientos para backup
    for($i = 1; $i <= self::$cant_dias_backup_relevamiento; $i++){
      $fecha_backup = date("Y-m-d", strtotime($fecha_backup . " +1 days"));

      //me fijo si ya habia generados relevamientos backup para ese dia, si hay los borro
      $relevamientos_back = LayoutParcial::where([['fecha',$fecha_backup],
                                            ['id_sector',$request->id_sector],
                                            ['backup',1],
                                            ['id_estado_relevamiento',1],
                                            ['fecha_generacion',$fecha_hoy]])->get();

      foreach($relevamientos_back as $relevamiento){//si estado = 1 no va a tener columnas con diferencia
        foreach($relevamiento->detalles as $detalle){
          $detalle->delete();
        }
        $relevamiento->delete();
      }

      $layout_backup = new LayoutParcial;
      $layout_backup->fecha = $fecha_backup;
      $layout_backup->fecha_generacion = $fecha_generacion;
      $layout_backup->backup = 1;
      $layout_backup->sector()->associate($sector->id_sector);
      $layout_backup->estado_relevamiento()->associate(1);
      $layout_backup->save();

      $maquinas = Maquina::whereIn('id_isla',$islas)
                         ->whereHas('estado_maquina',function($q){$q->where('descripcion','Ingreso')->orWhere('descripcion','ReIngreso');})
                         ->inRandomOrder()->take($request->cantidad_maquinas)->get();


      $maquinas_total = $maquinas->sortBy(function($maquina,$key){
        return Isla::find($maquina->id_isla)->nro_isla;
      });

      foreach($maquinas_total as $maq){
        $detalle = new DetalleLayoutParcial;
        $detalle->id_maquina = $maq->id_maquina;
        $detalle->id_layout_parcial = $layout_backup->id_layout_parcial;
        $detalle->save();
      }
       $arregloRutas[] = $this->guardarPlanillaLayoutParcial($layout_backup->id_layout_parcial);
    }

    //crear zip con backup;
    $nombreZip = 'Planillas-'. $sector->casino->codigo
                .'-'.$sector->descripcion
                .'-'.$fecha_hoy.' al '.strftime("%Y-%m-%d", strtotime("$fecha_hoy +".self::$cant_dias_backup_relevamiento." day"))
                .'.zip';
    Zipper::make($nombreZip)->add($arregloRutas)->close();
    File::delete($arregloRutas);

    return ['layouts_parcial' => $layouts_finales,
            'layouts_viejos' => $id_layouts_viejos,
            'cantidad_relevamientos' => $request->cantidad_fiscalizadores,
            'fecha' => strftime("%d %b %Y", strtotime($fecha_hoy)),
            'casino' => $sector->casino->nombre,
            'sector' => $sector->descripcion,
            'estado' => 'Generado',
            'existeLayoutParcial' => 0,
            'url_zip' => '/layouts/descargarLayoutParcialZip/'.$nombreZip];
    }else{
    return['existeLayoutParcial' => 1];
     }
  }
  // buscarLayoutsParciales 
  public function buscarLayoutsParciales(Request $request){
    $reglas = Array();
    $usuario = UsuarioController::getInstancia()->buscarUsuario(session('id_usuario'))['usuario'];
    foreach ($usuario->casinos as $casino) {
      $casinos[] = $casino->id_casino;
    }
    if(!empty($request->fecha)){
      $reglas[]=['layout_parcial.fecha', '=', $request->fecha];
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
    $resultados=DB::table('layout_parcial')
    ->select('layout_parcial.*'  , 'sector.descripcion as sector' , 'casino.nombre as casino' , 'estado_relevamiento.descripcion as estado')
    ->join('sector' ,'sector.id_sector' , '=' , 'layout_parcial.id_sector')
    ->join('casino' , 'sector.id_casino' , '=' , 'casino.id_casino')
    ->join('estado_relevamiento' , 'layout_parcial.id_estado_relevamiento' , '=' , 'estado_relevamiento.id_estado_relevamiento')
    ->when($sort_by,function($query) use ($sort_by){
                    return $query->orderBy($sort_by['columna'],$sort_by['orden']);
                })
    ->where($reglas)
    ->whereIn('casino.id_casino' , $casinos)
    ->where('backup' , '=', 0)->paginate($request->page_size);

    foreach ($resultados as $resultado) {
      $resultado->fecha = strftime("%d %b %Y", strtotime($resultado->fecha));
    }

    return $resultados;
  }
  // cargarLayoutParcial carla el layout parcial y sus detalles
  public function cargarLayoutParcial(Request $request){
    Validator::make($request->all(),[
      'id_layout_parcial' => 'required|exists:layout_parcial,id_layout_parcial',
      'tecnico' => 'required|max:45',
      'fiscalizador_toma' => 'required|exists:usuario,id_usuario',
      'fecha_ejecucion' => 'required|date',
      'observacion' => 'nullable|string',

      'maquinas' => 'nullable',
      'maquinas.*.id_maquina' => 'required|integer|exists:maquina,id_maquina',
      'maquinas.*.porcentaje_dev' => ['nullable','regex:/^\d\d?\d?\d?\d?\d?\d?\d?\d?\d?\d?\d?([,|.]\d\d?)?$/'],
      'maquinas.*.denominacion' => ['nullable','string'],
      'maquinas.*.juego.correcto' => 'required|in:true,false',
      'maquinas.*.no_toma' => 'required|in:1,0',
      'maquinas.*.juego.valor' => 'required|string',
      'maquinas.*.marca.correcto' => 'required|in:true,false',
      'maquinas.*.marca.valor' => 'required|string',
      'maquinas.*.nro_isla.correcto' => 'required|in:true,false',
      'maquinas.*.nro_isla.valor' => 'required|string',

      'maquinas.*.progresivo' => 'nullable',
      'maquinas.*.progresivo.id_progresivo' => 'required_with:maquinas.*.progresivo|integer|exists:progresivo,id_progresivo',
      'maquinas.*.progresivo.maximo.correcto' => 'required_with:maquinas.*.progresivo|in:true,false',
      'maquinas.*.progresivo.maximo.valor' => 'required_with:maquinas.*.progresivo|numeric' ,
      'maquinas.*.progresivo.individual.valor' => 'required_with:maquinas.*.progresivo|in:INDIVIDUAL,LINKEADO',
      'maquinas.*.progresivo.individual.correcto' => 'required_with:maquinas.*.progresivo|in:true,false',
      'maquinas.*.progresivo.porc_recuperacion.valor' => 'required_with:maquinas.*.progresivo|string',
      'maquinas.*.progresivo.porc_recuperacion.correcto' => 'required_with:maquinas.*.progresivo|in:true,false',
      'maquinas.*.progresivo.nombre_progresivo.valor' => 'required_with:maquinas.*.progresivo|string',
      'maquinas.*.progresivo.nombre_progresivo.correcto' => 'required_with:maquinas.*.progresivo|in:true,false',

      'maquinas.*.niveles_progresivo' => 'nullable',
      'maquinas.*.niveles_progresivo.*.id_nivel' => 'required',
      'maquinas.*.niveles_progresivo.*.nombre_nivel' => 'required',
      'maquinas.*.niveles_progresivo.*.base' => 'required',
      'maquinas.*.niveles_progresivo.*.porc_oculto' =>  ['nullable','regex:/^\d\d?([,|.]\d\d?)?$/'],
      'maquinas.*.niveles_progresivo.*.porc_visible' =>  ['required','regex:/^\d\d?([,|.]\d\d?\d?)?$/'],


    ], array(), self::$atributos)->after(function($validator){

      // $validator->getData()

    })->validate();

    $layout_parcial = LayoutParcial::find($request->id_layout_parcial);

    $layout_parcial->tecnico = $request->tecnico;
    $layout_parcial->id_usuario_fiscalizador = $request->fiscalizador_toma;
    $layout_parcial->id_usuario_cargador = session('id_usuario');
    $layout_parcial->fecha_ejecucion = $request->fecha_ejecucion;
    $layout_parcial->observacion_fiscalizacion = $request->observacion;
    $detalle_layout_parcial = $layout_parcial->detalles;
    if(isset($request->maquinas)){
      //por cada renglon tengo progresivo, nivels y configuracion de maquina
      foreach ($request->maquinas as $maquina_de_layout){
        $maquina = Maquina::find($maquina_de_layout['id_maquina']);//maquina que corresponde a detalle de layout
        $bandera =1 ;
        $detalle = $layout_parcial->detalles()->where('id_maquina' , $maquina_de_layout['id_maquina'])->get();
        //valido que todos los campos esten corretos

        if(!(filter_var($maquina_de_layout['nro_admin']['correcto'],FILTER_VALIDATE_BOOLEAN))){
          $maquina_con_diferencia = new CampoConDiferencia;//si el nombre juego presentaba diferencia
          $maquina_con_diferencia->columna ='nro_admin';
          $maquina_con_diferencia->entidad ='maquina';
          $maquina_con_diferencia->valor = $maquina_de_layout['nro_admin']['valor'];
          $maquina_con_diferencia->detalle_layout_parcial()->associate($detalle[0]->id_detalle_layout_parcial);
          $maquina_con_diferencia->save();
          $bandera=0;
        }
        if(!(filter_var($maquina_de_layout['juego']['correcto'],FILTER_VALIDATE_BOOLEAN))){
          $maquina_con_diferencia = new CampoConDiferencia;//si el nombre juego presentaba diferencia
          $maquina_con_diferencia->columna ='nombre_juego';
          $maquina_con_diferencia->entidad ='maquina';
          $maquina_con_diferencia->valor = $maquina_de_layout['juego']['valor'];
          $maquina_con_diferencia->detalle_layout_parcial()->associate($detalle[0]->id_detalle_layout_parcial);
          $maquina_con_diferencia->save();
          $bandera=0;
        }
        if(!(filter_var($maquina_de_layout['marca']['correcto'],FILTER_VALIDATE_BOOLEAN))){//si el marca presentaba diferencia
          $maquina_con_diferencia = new CampoConDiferencia;
          $maquina_con_diferencia->columna = 'marca';
          $maquina_con_diferencia->entidad = 'maquina';
          $maquina_con_diferencia->valor = $maquina_de_layout['marca']['valor'];
          $maquina_con_diferencia->detalle_layout_parcial()->associate($detalle[0]->id_detalle_layout_parcial);
          $maquina_con_diferencia->save();
          $bandera=0;
        }
        if(!(filter_var($maquina_de_layout['nro_isla']['correcto'],FILTER_VALIDATE_BOOLEAN))){//si el numero isla presentaba diferencia
          $maquina_con_diferencia = new CampoConDiferencia;
          $maquina_con_diferencia->columna = 'nro_isla';
          $maquina_con_diferencia->entidad = 'maquina';
          $maquina_con_diferencia->valor = $maquina_de_layout['nro_isla']['valor'];
          $maquina_con_diferencia->detalle_layout_parcial()->associate($detalle[0]->id_detalle_layout_parcial);
          $maquina_con_diferencia->save();
          $bandera=0;
        }
        if(!(filter_var($maquina_de_layout['nro_serie']['correcto'],FILTER_VALIDATE_BOOLEAN))){
          $maquina_con_diferencia = new CampoConDiferencia;
          $maquina_con_diferencia->columna = 'nro_serie';
          $maquina_con_diferencia->entidad = 'maquina';
          $maquina_con_diferencia->valor = $maquina_de_layout['nro_serie']['valor'];
          $maquina_con_diferencia->detalle_layout_parcial()->associate($detalle[0]->id_detalle_layout_parcial);
          $maquina_con_diferencia->save();
          $bandera=0;
        }

        //reviso configuracion de progresivo
        if(isset($maquina_de_layout['progresivo'])){ //configuracion progresivo
          $progresivo = Progresivo::find($maquina_de_layout['progresivo']['id_progresivo']);
          $pozo= $maquina->pozo;

          if(!(filter_var($maquina_de_layout['progresivo']['maximo']['correcto'],FILTER_VALIDATE_BOOLEAN))){
            $progresivos_con_diferencia = new CampoConDiferencia;
            $progresivos_con_diferencia->columna ='maximo';
            $progresivos_con_diferencia->entidad ='progresivo';
            $progresivos_con_diferencia->valor = $maquina_de_layout['progresivo']['maximo']['valor'];
            $progresivos_con_diferencia->detalle_layout_parcial()->associate($detalle[0]->id_detalle_layout_parcial);
            $progresivos_con_diferencia->save();
            $bandera=0;
          }
          if(!(filter_var($maquina_de_layout['progresivo']['porc_recuperacion']['correcto'],FILTER_VALIDATE_BOOLEAN))){
            $progresivos_con_diferencia = new CampoConDiferencia;
            $progresivos_con_diferencia->columna ='porc_recuperacion';
            $progresivos_con_diferencia->entidad ='progresivo';
            $progresivos_con_diferencia->valor = $maquina_de_layout['progresivo']['porc_recuperacion']['valor'];
            $progresivos_con_diferencia->detalle_layout_parcial()->associate($detalle[0]->id_detalle_layout_parcial);
            $progresivos_con_diferencia->save();
            $bandera=0;
          }
          if(!(filter_var($maquina_de_layout['progresivo']['nombre_progresivo']['correcto'],FILTER_VALIDATE_BOOLEAN))){
            $progresivos_con_diferencia = new CampoConDiferencia;
            $progresivos_con_diferencia->columna ='nombre_progresivo';
            $progresivos_con_diferencia->entidad ='progresivo';
            $progresivos_con_diferencia->valor = $maquina_de_layout['progresivo']['nombre_progresivo']['valor'];
            $progresivos_con_diferencia->detalle_layout_parcial()->associate($detalle[0]->id_detalle_layout_parcial);
            $progresivos_con_diferencia->save();
            $bandera=0;
          }
          if(!(filter_var($maquina_de_layout['progresivo']['individual']['correcto'],FILTER_VALIDATE_BOOLEAN))){
            $progresivos_con_diferencia = new CampoConDiferencia;
            $progresivos_con_diferencia->columna ='individual';
            $progresivos_con_diferencia->entidad ='progresivo';
            $progresivos_con_diferencia->valor = $maquina_de_layout['progresivo']['individual']['valor'];
            $progresivos_con_diferencia->detalle_layout_parcial()->associate($detalle[0]->id_detalle_layout_parcial);
            $progresivos_con_diferencia->save();
            $bandera=0;
          }

          //reviso niveles del progresivo
          if(isset($maquina_de_layout['niveles'])){
            $i=1;
            foreach ($maquina_de_layout['niveles'] as $nivel) {
              if(!(filter_var($nivel['porc_visible']['correcto'],FILTER_VALIDATE_BOOLEAN))){
                $niv_con_dif = new CampoConDiferencia;
                $niv_con_dif->columna ='porc_visible';
                $niv_con_dif->entidad ='nivel_progresivo/'.$i;
                $niv_con_dif->valor = $nivel['porc_visible']['valor'];
                $niv_con_dif->detalle_layout_parcial()->associate($detalle[0]->id_detalle_layout_parcial);
                $niv_con_dif->save();
                $bandera=0;
              }
              if(!(filter_var($nivel['porc_oculto']['correcto'],FILTER_VALIDATE_BOOLEAN))){
                $niv_con_dif = new CampoConDiferencia;
                $niv_con_dif->columna ='porc_oculto';
                $niv_con_dif->entidad ='nivel_progresivo/'.$i;
                $niv_con_dif->valor = $nivel['porc_oculto']['valor'];
                $niv_con_dif->detalle_layout_parcial()->associate($detalle[0]->id_detalle_layout_parcial);
                $niv_con_dif->save();
                $bandera=0;
              }
              if(!(filter_var($nivel['base']['correcto'],FILTER_VALIDATE_BOOLEAN))){
                $niv_con_dif = new CampoConDiferencia;
                $niv_con_dif->columna ='base';
                $niv_con_dif->entidad ='nivel_progresivo/'.$i;
                $niv_con_dif->valor = $nivel['base']['valor'];
                $niv_con_dif->detalle_layout_parcial()->associate($detalle[0]->id_detalle_layout_parcial);
                $niv_con_dif->save();
                $bandera=0;
              }
              if(!(filter_var($nivel['nombre_nivel']['correcto'],FILTER_VALIDATE_BOOLEAN))){
                $niv_con_dif = new CampoConDiferencia;
                $niv_con_dif->columna ='nombre_nivel';
                $niv_con_dif->entidad ='nivel_progresivo/'.$i;
                $niv_con_dif->valor = $nivel['nombre_nivel']['valor'];

                $niv_con_dif->detalle_layout_parcial()->associate($detalle[0]->id_detalle_layout_parcial);
                $niv_con_dif->save();
                $bandera=0;
              }

              if(!(filter_var($nivel['base']['correcto'],FILTER_VALIDATE_BOOLEAN))){
                $niv_con_dif = new CampoConDiferencia;
                $niv_con_dif->columna ='base';
                $niv_con_dif->entidad ='nivel_progresivo/'.$i;
                $niv_con_dif->valor = $nivel['base']['valor'];
                $niv_con_dif->detalle_layout_parcial()->associate($detalle[0]->id_detalle_layout_parcial);
                $niv_con_dif->save();
                $bandera=0;
              }
              if(!(filter_var($nivel['nombre_nivel']['correcto'],FILTER_VALIDATE_BOOLEAN))){
                $niv_con_dif = new CampoConDiferencia;
                $niv_con_dif->columna ='nombre_nivel';
                $niv_con_dif->entidad ='nivel_progresivo/'.$i;
                $niv_con_dif->valor = $nivel['nombre_nivel']['valor'];
                $niv_con_dif->detalle_layout_parcial()->associate($detalle[0]->id_detalle_layout_parcial);
                $niv_con_dif->save();
                $bandera=0;
              }
              $i++;
            }
          }
        }

        if($detalle->count() == 1 ){
          $detalle[0]->correcto=$bandera;
          $detalle[0]->denominacion = $maquina_de_layout['denominacion'];
          $detalle[0]->porcentaje_devolucion = $maquina_de_layout['porcentaje_dev'];
          $detalle[0]->save();
        }

      }
    }
    $estado_relevamiento = EstadoRelevamiento::where('descripcion','finalizado')->get();
    $layout_parcial->id_estado_relevamiento = $estado_relevamiento[0]->id_estado_relevamiento; //finalizado
    $layout_parcial->save();
    return ['estado' => $estado_relevamiento[0]->descripcion,
    'codigo' => 200 ];//codigo ok, dispara boton buscar en vista
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

  public function validarLayoutParcial(Request $request){
    Validator::make($request->all(),[
        'observacion_validacion' => 'required',
        'id_layout_parcial' => 'required|exists:layout_parcial,id_layout_parcial',
    ], array(), self::$atributos)->after(function($validator){

        $layout = LayoutParcial::find($validator->getData()['id_layout_parcial']);
        if($layout->backup == 1){
          $validator->errors()->add('layout_backupt','Error. El layout a validar es de backup');
        }

    })->validate();

    $layout = LayoutParcial::find($request->id_layout_parcial);
    $layout->observacion_validacion = $request->observacion_validacion;
    $layout->id_estado_relevamiento =  EstadoRelevamiento::where('descripcion' , 'Visado')->first()->id_estado_relevamiento;
    $layout->save();

    return ['layout' => $layout];
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
    ->select('layout_total.*' , 'casino.nombre as casino' , 'estado_relevamiento.descripcion as estado')
    ->join('casino' , 'layout_total.id_casino' , '=' , 'casino.id_casino')
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
    return view('seccionLayoutTotal', ['casinos' => $casinos , 'estados' => $estados,'usuario'=>$usuario]);
  }

  public function obtenerLayoutTotal($id){
    $layout_total= LayoutTotal::find($id);

    return ['layout_total' => $layout_total ,
            'sectores' => $layout_total->casino->sectores,
            'casino' => $layout_total->casino,
            'sectores' => $layout_total->casino->sectores,
            'usuario_cargador' => $layout_total->usuario_cargador ,
            'usuario_fiscalizador' => $layout_total->usuario_fiscalizador,
            'detalles' => $layout_total->detalles];
  }

  // crearLayoutTotal crea el relevamiento y los backup
  // los ordena segun el casino 

  public function crearLayoutTotal(Request $request){
    Validator::make($request->all(),[
        'id_casino' => 'required|exists:casino,id_casino',
        'turno' => 'required|numeric|between:1,4'
    ], array(), self::$atributos)->after(function($validator){

        $layouts = LayoutTotal::where([['fecha',date("Y-m-d")],['id_casino',$validator->getData()['id_casino']],['backup',0],['id_estado_relevamiento',2]])->count();
        if($layouts > 0){
          $validator->errors()->add('layout_en_carga','El control de layout para esa fecha ya está en carga y no se puede reemplazar.');
        }
        $user = UsuarioController::getInstancia()->buscarUsuario(session('id_usuario'))['usuario'];
        $id_casino = $validator->getData()['id_casino'];
        if(!$user->usuarioTieneCasino($id_casino)){
          $validator->errors()->add('id_casino','El usuario no tiene acceso a este casino');
        }
    })->validate();

    /*
    NUEVO RELEVAMIENTO TOTAL
    */
    $nombreZip = null;
    DB::beginTransaction();
    try{
      $fecha_hoy = date("Y-m-d"); // fecha de hoy
      $id_layouts_viejos = array();
  
      //me fijo si ya habia generados control layout para el dia de hoy que no sean back up, si hay los borro
  
      $layouts_totales = LayoutTotal::where([['backup',0] ,['id_estado_relevamiento',1],['id_casino',$request->id_casino],['fecha',$fecha_hoy],['turno',$request->turno]])->get();
  
      foreach($layouts_totales as $unControLayout){
        $unControLayout->delete();
      }
  
      $arregloRutas = array();
      $layouts_finales = array();
      $layout_total = new LayoutTotal;
      $layout_total->nro_layout_total = DB::table('layout_total')->max('nro_layout_total') + 1;
      $layout_total->fecha = $fecha_hoy;

      $fecha_hora= date("H:i:s");
      $layout_total->turno = $request->turno;
      $layout_total->fecha_generacion = date('Y-m-d h:i:s', time());
      $fecha_generacion = $layout_total->fecha_generacion;
      $layout_total->backup = 0;

      $casino = Casino::find($request->id_casino);

      $layout_total->casino()->associate($casino->id_casino);
      $layout_total->estado_relevamiento()->associate(1);
      $layout_total->save();
      $this->asignarIslas($layout_total);
      $layouts_finales [] = $layout_total;
      $arregloRutas[] = $this->guardarPlanillaLayoutTotal($layout_total->id_layout_total);

      /*
      Generacion backups
      */
      $fecha_backup = $fecha_hoy; // Armamos los relevamientos para backup
      for($i = 1; $i <= self::$cant_dias_backup_relevamiento; $i++){
          $fecha_backup = date("Y-m-d", strtotime($fecha_backup . " +1 days"));

          //me fijo si ya habia generados relevamientos backup para ese dia, si hay los borro
          $relevamientos_back = LayoutTotal::where([['fecha',$fecha_backup],
                                                ['id_casino',$casino->id_casino],
                                                ['backup',1],
                                                ['id_estado_relevamiento',1],
                                                ['fecha_generacion',$fecha_hoy]])->get();

          foreach($relevamientos_back as $relevamiento){//si estado = 1 no hay diferencias (es decir,no hay detalles)
            $relevamiento->delete();
          }

          $layout_backup = new LayoutTotal;
          $layout_backup->fecha = $fecha_backup;
          $layout_backup->fecha_generacion = $fecha_generacion;
          $layout_backup->backup = 1;
          $layout_backup->turno = $request->turno;
          $layout_backup->casino()->associate($casino->id_casino);
          $layout_backup->estado_relevamiento()->associate(1);
          $layout_backup->save();
          $this->asignarIslas($layout_backup);
          $arregloRutas[] = $this->guardarPlanillaLayoutTotal($layout_backup->id_layout_total);
      }

      // crear zip con backup;  FALTA PLANILLA
      $nombreZip = 'Planillas-'.$casino->codigo
                  .'-'.$fecha_hoy.' al '.strftime("%Y-%m-%d", strtotime("$fecha_hoy +".self::$cant_dias_backup_relevamiento." day"))
                  .'.zip';
      Zipper::make($nombreZip)->add($arregloRutas)->close();
      File::delete($arregloRutas);
    }
    catch(Exception $e){
      DB::rollBack();
      throw $e;
    }
    DB::commit();
    return [ 'url_zip' => '/layouts/descargarLayoutTotalZip/'.$nombreZip];

  }
  //Asigna islas del casino al layout total, usado en la creacion.
  private function asignarIslas($layout_total){
    $casino = $layout_total->casino;
    $sectores = $casino->sectores;
    $estados_validos = [1,2,5,7];//Ingreso, Reingreso, Egreso Int Tec, Eventualidad Observada
    foreach($sectores as $s){
      $islas = $s->islas;
      foreach($islas as $i){
        $maquinas = $i->maquinas;
        $isla_valida = FALSE;
        //Si la isla tiene maquinas con un estado "disponible", la agrego para relevar
        foreach($maquinas as $m){
          if(in_array($m->id_estado_maquina,$estados_validos)){
            $isla_valida = TRUE;
            break;
          }
        }
        if($isla_valida){
          $obs = new LayoutTotalIsla;
          $obs->id_layout_total = $layout_total->id_layout_total;
          $obs->id_isla = $i->id_isla;
          $obs->maquinas_observadas = null;
          $obs->save();
        }
      }
    }
  }

  private function guardarPlanillaLayoutTotal($id_layout_total){
    $layout_total = LayoutTotal::find($id_layout_total);
    $dompdf = $this->crearPlanillaLayoutTotal($layout_total);
    $output = $dompdf->output();
    $ruta = "LayoutTotal-".$layout_total->casino->codigo."-".$layout_total->fecha.".pdf";
    file_put_contents($ruta, $output);

    return $ruta;
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
    $rel->fecha_ejecucion = ($layout_total->fecha_ejecucion != null) ? $layout_total->fecha_ejecucion : $layout_total->fecha;
    $rel->fecha_generacion = $layout_total->fecha_generacion;

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

    //cargado significa que entreo luego que se finalizo, en ese punto solo lo puede ver el administrador
    //mostrar maquinas es una bandera para mostrar en el formulario la cantidad de maquinas
    if($cargado){
      $maquinas_apagadas = $layout_total->detalles;
      $mostrar_maquinas=true;
    }else{
      $maquinas_apagadas = array();
      $mostrar_maquinas=false;
    }

    foreach($layout_total->casino->sectores as $sector){
      $det = new \stdClass();
      $det->descripcion = $sector->descripcion;
      // $islas = array();
      // foreach ($sector->islas as $isla) {
      //   $islas[] = $isla->nro_isla;
      // }
      //si el casino es de rosario lo ordeno por islote e isla
      if($layout_total->id_casino==3){
        $det->islas = $sector->islas->sortBy(function($isl,$key){
          return [$isl->orden,$isl->nro_isla];
        });
      }else{
        $det->islas = $sector->islas;
      };
      
      
      $detalles[] = $det;
    };
    $view = View::make('planillaLayoutTotalEdit', compact('rel','detalles','maquinas_apagadas','mostrar_maquinas'));
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

  public function obtenerTotalParaValidar($id){
    $layout_total= LayoutTotal::find($id);
    $errors = $this->verificarAccesoLayoutTotal($layout_total,False);
    if(!is_null($errors)) return $errors;

    return ['layout_total' => $layout_total,
            'sectores' => $layout_total->casino->sectores,
            'casino' => $layout_total->casino->nombre,
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
    Validator::make($request->all(),[
        'id_casino' => 'required|exists:casino,id_casino',
        'fecha' => 'required|date',
        'fecha_generacion' => 'required|date'
    ], array(), self::$atributos)->after(function($validator){
    })->validate();

    $user = UsuarioController::getInstancia()->buscarUsuario(session('id_usuario'))['usuario'];
    if(!$user->usuarioTieneCasino($request->id_casino)){
      return $this->errorOut([ 'casino' => ['El usuario no puede acceder a ese casino.'] ]);
    }
    if(!$user->es_administrador && !$user->es_superusuario && !$user->es_fiscalizador){
      return $this->errorOut([ 'privilegios' => ['El usuario no puede realizar esa accion.'] ]);
    }

    //Si hay un relevamiento que original para el dia en el cual se quiere usar un backup
    $relevamientos = LayoutTotal::where([['id_casino',$request->id_casino],['fecha',$request->fecha],['backup',0]])->whereIn('id_estado_relevamiento',[1,2])->get();

    //busco el backup para la fecha "fecha", creado el dia "fecha de generacion" , para sector "id_sector"
    $rel_backup = LayoutTotal::where([['id_casino',$request->id_casino],['fecha',$request->fecha],['backup',1]])->whereDate('fecha_generacion','=',$request->fecha_generacion)->first();

    if($rel_backup != null){
      $rel_backup->backup = 0;
      $rel_backup->save();
    }

    if($relevamientos != null){
      foreach($relevamientos as $relevamiento){
        $relevamiento->backup = 1;
        $relevamiento->save();
      }
    }
    //todo ok
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

      $s = &$sectores[$id_sector];
      $s['islas'][]=$i2;
      $s['total_observadas']+=(is_null($observadas)? 0 : $observadas);
      $s['total_sistema']+=(is_null($sistema)? 0: $sistema);
    }
    $ret = [];
    foreach($sectores as $s){
      usort($s['islas'],function($a,$b){
        return $a['nro_isla']<=>$b['nro_isla'];
      });
      $ret[]=$s;
    }
    usort($ret,function($a,$b){
      return $a['descripcion']<=>$b['descripcion'];
    });
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

    if(!($user->es_administrador || $user->es_superusuario || ($user->es_fiscalizador && $permitir_fiscal))){
      return $this->errorOut([ 'privilegios' => ['El usuario no puede realizar esa accion.'] ]);
    }

    return null;
  }

}
