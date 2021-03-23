<?php

namespace App\Http\Controllers\Mesas\InformesMesas;

use Auth;
use Session;
use Illuminate\Http\Request;
use Response;
use App\Http\Controllers\Controller;

use Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Hash;

use Dompdf\Dompdf;

use PDF;
use View;

use App\Usuario;
use App\Mesas\CSVImporter;
use App\Casino;
use App\Relevamiento;
use App\SecRecientes;
use App\Http\Controllers\RolesPermissions\RoleFinderController;

use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

use App\Mesas\Mesa;
use App\Mesas\Cierre;
use App\Mesas\Ficha;
use App\Mesas\DetalleCierre;
use App\Mesas\Moneda;
use App\Mesas\JuegoMesa;
use App\Mesas\SectorMesas;
use App\Mesas\TipoMesa;

use App\Mesas\ImportacionMensualMesas;
use App\Mesas\ImportacionDiariaMesas;
use App\Mesas\DetalleImportacionDiariaMesas;

use App\Mesas\ComandoEnEspera;

use \DateTime;
use \DateInterval;
use Carbon\Carbon;

use App\Mesas\CampoModificado;

use App\Http\Controllers\Mesas\Importaciones\Mesas\ImportadorController;
use App\Http\Controllers\Mesas\Importaciones\Mesas\MensualController;

class ModificarInformeDiarioController extends Controller
{
  private static $atributos = [
  ];

  /**
   * Create a new controller instance.
   *
   * @return void
   */
  public function __construct()
  {
      //$this->middleware(['auth']);//,'permission:m_m_diario']);//rol a definir por gusti-> en ppio AUDITOR
  }

  //recibe el id importacion diaria
  public function obtenerDatosAModificar($id_idm){
    ///debo buscar todos los detalles de la importacion diaria que posean diferencias
    $importacion = ImportacionDiariaMesas::find($id_idm);
    $dolares = ImportacionDiariaMesas::where('fecha','=',$importacion->fecha)->where('id_moneda','<>',$importacion->id_moneda)->get()->first();

    $detalles = $importacion->detallesConDiferencias();

    if($dolares != null){
      $detalles = $detalles->concat($dolares->detallesConDiferencias());
    }


    return $detalles;
  }

  public function almacenarDatos(Request $request){
    $validator=Validator::make($request->all(), [
      'cierre' => 'required_if:importacion,null',
      'importacion' => 'required_if:cierre,null',
     ],array(),self::$atributos)->after(function ($validator){

       ////falta validar que no pueda modificar porque ya pago


     })->validate();

       if(!empty($request['cierre'])){
          $cierre = collect($request['cierre']);
          $validator1=Validator::make($cierre->all(), [
                'id' => 'required|exists:cierre_mesa,id_cierre_mesa',
                'fichas' => 'required',
                'fichas.*.id' => 'required|exists:ficha,id_ficha',
                'fichas.*.monto' => ['required','regex:/^\d\d?\d?\d?\d?\d?\d?\d?([,|.]?\d?\d?\d?)?$/'],
                'id_importacion_diaria_mesas' => 'required|exists:importacion_diaria_mesas,id_importacion_diaria_mesas'
             ],array(),self::$atributos)->after(function ($validator){
               $algo = 0;
               foreach ($validator->getData()['fichas'] as $f) {
                  if($f['monto'] != '0'){
                    $algo++;
                  }
               }
               if($algo == 0){
                 $validator->errors()->add('fichas','No puede modificar todos los valores a cero.');
               }

             })->validate();
       }
       if(!empty($request['importacion'])){
          $importacion = $request['importacion'];
          $validator2=Validator::make($importacion, [
            'importacion.*.id' => 'required|exists:detalle_importacion_diaria_mesas,id_detalle_importacion_diaria_mesas',
            ///valido hasta 999.999.999 .-
            'importacion.*.drop' => ['required','regex:/^\d\d?\d?\d?\d?\d?\d?\d?\d?([,|.]?\d?\d?\d?)?$/'],
            'importacion.*.fill' => ['required','regex:/^\d\d?\d?\d?\d?\d?\d?\d?\d?([,|.]?\d?\d?\d?)?$/'],
            'importacion.*.credit' => ['required','regex:/^\d\d?\d?\d?\d?\d?\d?\d?\d?([,|.]?\d?\d?\d?)?$/'],
            'importacion.*.utilidad' => ['required','regex:/^\d\d?\d?\d?\d?\d?\d?\d?\d?([,|.]?\d?\d?\d?)?$/'],
            'importacion.*.cotizacion' => ['required','regex:/^\d\d?\d?\d?\d?\d?([,|.]?\d?\d?\d?)?$/'],
             ],array(),self::$atributos)->after(function ($validator){})->validate();
       }

    if(isset($validator)){
      if ($validator->fails()){
          return ['errors' => $validator->messages()->toJson()];
          }
    }
    if(isset($validator1)){
      if ($validator1->fails()){
          return ['errors' => $validator1->messages()->toJson()];
          }
    }
    if(isset($validator2)){
      if ($validator2->fails()){
          return ['errors' => $validator2->messages()->toJson()];
          }
    }
    if (!empty($request->cierre)) {
      $this->modificarFichasCierre($request->cierre, $request->id_importacion_diaria_mesas);
    }

    if(!empty($request->importacion)){
      $this->modificarDetalleIDM($request->importacion);
    }


    return response()->json(['exito' => 'Datos modificados!'], 200);
  }

  public function obtenerDatosDetalle($id){
    return DetalleImportacionDiariaMesas::findOrFail($id);
  }

  private function modificarFichasCierre($datos){
    //4 ocpiones -> nueva ficha, eliminacion de ficha, modificacion de valor, sin cambios
    $cierre = Cierre::find($datos['id']);
    foreach ($datos['fichas'] as $ficha) {
      $fichaEnCierre = $cierre->detalles()->where('id_ficha',$ficha['id'])->get();
      $objFicha = Ficha::find($ficha['id']);
      if($fichaEnCierre->isEmpty()){
        //no estaba entonces la agrego
          if($ficha['monto'] != 0 && $ficha['monto'] != '0'){

            $campo = CampoModificado::create(['id_entidad' => $cierre->id_cierre_mesa,
                                             'nombre_entidad' => 'Cierre de Mesa',
                                             'nombre_del_campo' => 'monto ficha: '.$objFicha->valor_ficha,
                                             'valor_anterior' => '0',
                                             'valor_nuevo' => $ficha['monto'],
                                             'id_entidad_extra' => $ficha['id'],
                                             'nombre_entidad_extra' => 'ficha',
                                             'accion' => 'nuevo',
                                             'id_importacion_diaria_mesas' => $datos['id_importacion_diaria_mesas']
                                          ]);

          //falta accion sobre ficha
          //crear el detalle
          $detCierre = DetalleCierre::create([
                                              'id_ficha'=> $ficha['id'],
                                              'monto_ficha' => $ficha['monto'],
                                              'id_cierre_mesa' => $cierre->id_cierre_mesa
                                            ]);
        }

      }else{
        //esta pero cambio el monto
        $detCierre = $fichaEnCierre->first();
        if($detCierre->monto_ficha != $ficha['monto'] && $ficha['monto'] != '0'){
          $campo = CampoModificado::create(['id_entidad' => $cierre->id_cierre_mesa,
                                             'nombre_entidad' => 'Cierre de Mesa',
                                             'nombre_del_campo' => 'monto ficha: '.$objFicha->valor_ficha,
                                             'valor_anterior' => $detCierre->monto_ficha,
                                             'id_entidad_extra' => $ficha['id'],
                                             'valor_nuevo' => $ficha['monto'],
                                             'nombre_entidad_extra' => 'ficha',
                                             'accion' => 'modificación',
                                             'id_importacion_diaria_mesas' => $datos['id_importacion_diaria_mesas']
                                          ]);
          //falta accion sobre ficha
          //modificar el detalle
          $detCierre->monto_ficha = $ficha['monto'];
          $detCierre->save();
        }else{
          if($ficha['monto'] == '0'){
            $campo = CampoModificado::create(['id_entidad' => $cierre->id_cierre_mesa,
                                               'nombre_entidad' => 'Cierre de Mesa',
                                               'nombre_del_campo' => 'monto ficha: '.$detCierre->ficha->valor_ficha,
                                               'valor_anterior' => $detCierre->monto_ficha,
                                               'id_entidad_extra' => $detCierre->id_ficha,
                                               'nombre_entidad_extra' => 'ficha',
                                               'valor_nuevo' => $ficha['monto'],
                                               'accion' => 'eliminación',
                                               'id_importacion_diaria_mesas' => $datos['id_importacion_diaria_mesas']
                                            ]);

            $detCierre->delete();
          }
        }
      }
    }
    $collectionFichas = collect($datos['fichas']);
    //recorro las que tiene el cierre para ver si eliminó algunas
    foreach ($cierre->detalles as $det) {
      $fichaEnCierre = $collectionFichas->where('id',$det->id_ficha)->all();
      //si no esta en la nueva lista de fichas -> la elimino
      if(count($fichaEnCierre) == 0){
        $campo = CampoModificado::create(['id_entidad' => $cierre->id_cierre_mesa,
                                           'nombre_entidad' => 'Cierre de Mesa',
                                           'nombre_del_campo' => 'monto ficha: '.$det->ficha->valor_ficha,
                                           'valor_anterior' => $det->monto_ficha,
                                           'valor_nuevo' => $ficha['monto'],
                                           'id_entidad_extra' => $det->id_ficha,
                                           'nombre_entidad_extra' => 'ficha',
                                           'accion' => 'eliminación',
                                           'id_importacion_diaria_mesas' => $datos['id_importacion_diaria_mesas']
                                        ]);

        $det->delete();
      }
    }
    $total_pesos_fichas_c = 0;
    foreach ($cierre->detalles as $det) {
      $total_pesos_fichas_c+= $det->monto_ficha;
    }

    $cierre->total_pesos_fichas_c = $total_pesos_fichas_c;
    $cierre->save();

    $this->actualizarSaldoFichas($cierre);
  }

  private function modificarDetalleIDM($datos){
    $detImportacion = DetalleImportacionDiariaMesas::find($datos['id']);
    $impD = $detImportacion->importacion_diaria_mesas;
    $impD->validado = 2;//modificado
    $impD->save();
    if($detImportacion->cotizacion != $datos['cotizacion'] &&
        ($detImportacion->cotizacion != null && $datos['cotizacion'] != 0)){
          if($detImportacion->cotizacion == null){
            $cot = 0;
          }else{
            $cot = $detImportacion->cotizacion;
          }
        //entonces modificó la cotizacion
        $campo = CampoModificado::create(['id_entidad' => $datos['id'],
                                           'nombre_entidad' => 'Detalle Importación',
                                           'nombre_del_campo' => 'cotizacion',
                                           'valor_anterior' => $cot,
                                           'id_entidad_extra' => '',
                                           'valor_nuevo' =>  $datos['cotizacion'],
                                           'nombre_entidad_extra' => '',
                                           'accion' => 'modificación',
                                           'id_importacion_diaria_mesas' => $detImportacion->id_importacion_diaria_mesas,
                                        ]);
        $campo->save();
        $detImportacion->cotizacion = $datos['cotizacion'];

    }

    if($detImportacion->utilidad != $datos['utilidad']){
      $campo = CampoModificado::create(['id_entidad' => $datos['id'],
                                         'nombre_entidad' => 'Detalle Importación',
                                         'nombre_del_campo' => 'utilidad',
                                         'valor_anterior' => $detImportacion->utilidad,
                                         'valor_nuevo' =>  $datos['utilidad'],
                                         'id_entidad_extra' => 0,
                                         'nombre_entidad_extra' => '',
                                         'accion' => 'modificación',
                                         'id_importacion_diaria_mesas' => $detImportacion->id_importacion_diaria_mesas,
                                      ]);
                                      $campo->save();
      $detImportacion->utilidad = $datos['utilidad'];
    }
    if($detImportacion->droop != $datos['drop']){
      $campo = CampoModificado::create(['id_entidad' => $datos['id'],
                                         'nombre_entidad' => 'Detalle Importación',
                                         'nombre_del_campo' => 'efectivo',
                                         'valor_anterior' => $detImportacion->droop,
                                         'id_entidad_extra' => 0,
                                         'nombre_entidad_extra' => '',
                                         'valor_nuevo' =>  $datos['drop'],
                                         'accion' => 'modificación',
                                         'id_importacion_diaria_mesas' => $detImportacion->id_importacion_diaria_mesas,
                                      ]);
                                      $campo->save();
      $detImportacion->droop = $datos['drop'];
    }
    if($detImportacion->reposiciones != $datos['fill']){
      if($detImportacion->reposiciones == null){
        $detImportacion->reposiciones =0;
      }
      $campo = CampoModificado::create(['id_entidad' => $datos['id'],
                                         'nombre_entidad' => 'Detalle Importación',
                                         'nombre_del_campo' => 'reposiciones',
                                         'valor_anterior' => $detImportacion->reposiciones,
                                         'id_entidad_extra' => 0,
                                         'valor_nuevo' =>  $datos['fill'],
                                         'nombre_entidad_extra' => '',
                                         'accion' => 'modificación',
                                         'id_importacion_diaria_mesas' => $detImportacion->id_importacion_diaria_mesas,
                                      ]);
                                      $campo->save();
      $detImportacion->reposiciones = $datos['fill'];
    }
    if($detImportacion->retiros != $datos['credit']){
      if($detImportacion->retiros == null){
        $detImportacion->retiros =0;
      }
      $campo = CampoModificado::create(['id_entidad' => $datos['id'],
                                         'nombre_entidad' => 'Detalle Importación',
                                         'nombre_del_campo' => 'retiros',
                                         'valor_anterior' => $detImportacion->retiros,
                                         'id_entidad_extra' => 0,
                                         'valor_nuevo' =>  $datos['credit'],
                                         'nombre_entidad_extra' => '',
                                         'accion' => 'modificación',
                                         'id_importacion_diaria_mesas' => $detImportacion->id_importacion_diaria_mesas,
                                      ]);
                                      $campo->save();
      $detImportacion->retiros = $datos['credit'];
    }

    $detImportacion->utilidad_calculada = $detImportacion->saldo_fichas + $detImportacion->droop - $detImportacion->reposiciones + $detImportacion->retiros;
    $detImportacion->diferencia_cierre = $detImportacion->utilidad - $detImportacion->utilidad_calculada;
    
    $detImportacion->save();
    $this->actualizarTotales($detImportacion);
  }

  private function actualizarTotalesImpDiaria($id_importacion_diaria_mesas){
    $imp = ImportacionDiariaMesas::find($id_importacion_diaria_mesas);
    $droop = 0 ;
    $diferencias = 0;
    $utilidad_diaria_calculada = 0;
    $utilidad_diaria_total = 0;
    $saldo_diario_fichas = 0;
    $total_diario_retiros = 0;
    $total_diario_reposiciones = 0;
    foreach ($imp->detalles as $datos_mesa) {
      $total_diario+= $datos_mesa->droop;
      $diferencias+= $datos_mesa->diferencia_cierre;
      $utilidad_diaria_calculada+= $datos_mesa->utilidad_calculada;
      $utilidad_diaria_total+= $datos_mesa->utilidad;
      $saldo_diario_fichas+= $datos_mesa->saldo_fichas;
      $total_diario_retiros+= $datos_mesa->retiros;
      $total_diario_reposiciones+= $datos_mesa->reposiciones;
    }
    $imp->droop = $droop;
    $imp->diferencias = $diferencias;
    $imp->utilidad_diaria_calculada = $utilidad_diaria_calculada;
    $imp->utilidad_diaria_total = $utilidad_diaria_total;
    $imp->saldo_diario_fichas = $saldo_diario_fichas;
    $imp->total_diario_retiros = $total_diario_retiros;
    $imp->total_diario_reposiciones = $total_diario_reposiciones;
    $imp->save();
  }

  private function actualizarTotales($detImportacion){
    $importacionDia = $detImportacion->importacion_diaria_mesas;
    $ff = explode('-',$importacionDia->fecha);
    
    $this->actualizarTotalesImpDiaria($detImportacion->id_importacion_diaria_mesas);

    $mensual = ImportacionMensualMesas::where([
                                                ['id_casino','=',$importacionDia->id_casino],
                                                ['id_moneda','=',$importacionDia->id_moneda]
                                              ])
                                      ->whereYear('fecha_mes','=',$ff[0])
                                      ->whereMonth('fecha_mes','=',$ff[1])
                                      ->get();
    if(count($mensual->all())>0){
      $mensualController = new MensualController;
      $mensualController->actualizarTotales($mensual->first()->id_importacion_mensual_mesas);
    }

  }


  private function actualizarSaldoFichas($cierre){
    $detimpDiaria = DetalleImportacionDiariaMesas::where('id_cierre_mesa','=',$cierre->id_cierre_mesa)
                                        ->get()->first();
    if(count($detimpDiaria)>0){
      if($detimpDiaria->id_ultimo_cierre != null){
        $detimpDiaria->saldo_fichas = $cierre->total_pesos_fichas_c -
                       $detimpDiaria->cierre_anterior->total_pesos_fichas_c;


        $detimpDiaria->diferencia_cierre = $detimpDiaria->utilidad - $detimpDiaria->utilidad_calculada;
        $impD = $detimpDiaria->importacion_diaria_mesas;
        $impD->validado = 2;//modificado
        $impD->save();


        //formula = (Cx+1 - Cx ) +DROP -FILL+CREDIT = UTILIDAD CALCULADA
        $detimpDiaria->utilidad_calculada = $detimpDiaria->saldo_fichas + $detimpDiaria->droop - $detimpDiaria->reposiciones + $detimpDiaria->retiros;

        $detimpDiaria->save();
        $this->actualizarTotales($detimpDiaria);
      }
    }
    //si el cierre fue usado como ultimo cierre en otrs importacion tmb hy que actualizarTotales
    $detimpDiaria = DetalleImportacionDiariaMesas::where('id_ultimo_cierre','=',$cierre->id_cierre_mesa)
                                        ->get();
    if(count($detimpDiaria)>0){
      if($detimpDiaria->id_cierre_mesa != null){
        $detimpDiaria->saldo_fichas = $cierre->total_pesos_fichas_c -
                       $detimpDiaria->cierre_anterior->total_pesos_fichas_c;

        $detimpDiaria->diferencia_cierre = $detimpDiaria->utilidad - $detimpDiaria->utilidad_calculada;
        $impD = $detimpDiaria->importacion_diaria_mesas;
        $impD->validado = 2;//modificado
        $impD->save();

        //formula = (Cx+1 - Cx ) +DROP -FILL+CREDIT = UTILIDAD CALCULADA
        $detimpDiaria->utilidad_calculada = $detimpDiaria->saldo_fichas + $detimpDiaria->droop - $detimpDiaria->reposiciones + $detimpDiaria->retiros;

        $detimpDiaria->save();
        $this->actualizarTotales($detimpDiaria);
      }
    }
  }



}
