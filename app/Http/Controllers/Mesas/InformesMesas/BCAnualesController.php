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

use App\Mesas\ImportacionMensualMesas;
use App\Mesas\DetalleImportacionMensualMesas;

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
use App\Mesas\Moneda;
use App\Mesas\JuegoMesa;
use App\Mesas\SectorMesas;
use App\Mesas\TipoMesa;

use App\Mesas\ImportacionDiariaMesas;
use App\Mesas\DetalleImportacionDiariaMesas;

use App\Mesas\ComandoEnEspera;

use \DateTime;
use \DateInterval;
use Carbon\Carbon;

use App\Mesas\DetalleInformeFinalMesas;
use App\Mesas\InformeFinalMesas;

use App\Http\Controllers\UsuarioController;


class BCAnualesController extends Controller
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
       $this->middleware(['tiene_permiso:m_bc_anuales']);//rol a definir por gusti-> en ppio AUDITOR
   }

  public function buscarPorAnioCasinoMoneda(Request $request){
    $validator=  Validator::make($request->all(),[
      'anio' => 'required',
      'id_casino' => 'required|exists:casino,id_casino',
      'id_moneda' => 'required|exists:moneda,id_moneda',
      'id_casino2' => 'nullable|exists:casino,id_casino|different:id_casino',
      'id_moneda2' => 'nullable|exists:moneda,id_moneda',
    ], ['different' => 'Elija otro casino.'], self::$atributos)->after(function($validator){
      $data = $validator->getData();
      $id_casino  = $data['id_casino'];
      $id_moneda  = $data['id_moneda'];
      $id_casino2 = $data['id_casino2'];
      $id_moneda2 = $data['id_moneda2'];

      $sin_mesas = Mesa::where([['id_moneda','=',$id_moneda],['id_casino','=',$id_casino]])
      ->orWhere('multimoneda','=',1)->get()->count() == 0;
      if($sin_mesas){
        $validator->errors()->add('id_moneda','No existen informes para la moneda seleccionada.');
        return;
      }

      if(!empty($id_moneda2)){
        $sin_mesas = Mesa::where([['id_moneda','=',$id_moneda2],['id_casino','=',$id_casino]])
        ->orWhere('multimoneda','=',1)->get()->count() == 0;
        if($sin_mesas){
          $validator->errors()->add('id_moneda2','No existen informes para la moneda seleccionada del 2do casino.' );
          return;
        }  
      }

      if(!empty($id_casino2)){
        $sin_mesas = Mesa::where([['id_moneda','=',$id_moneda],['id_casino','=',$id_casino2]])
        ->orWhere('multimoneda','=',1)->get()->count() == 0;
        if($sin_mesas){
            $validator->errors()->add('id_casino2','No existen informes para la moneda seleccionada.');
            return;
        }
        if(!empty($id_moneda2)){
          $sin_mesas = Mesa::where([['id_moneda','=',$id_moneda2],['id_casino','=',$id_casino2]])
          ->orWhere('multimoneda','=',1)->get()->count() == 0;
          if($sin_mesas){
            $validator->errors()->add('id_moneda2','No existen informes para la moneda seleccionada del 2do casino.' );
            return;
          }
        }
      }
    })->validate();

    $respuesta  = ImportacionMensualMesas::whereYear('fecha_mes','=',$request->anio)
                                            ->where('id_casino','=',$request->id_casino)
                                            ->where('id_moneda','=',$request->id_moneda)
                                            ->where('validado','=',1)
                                            ->get()->toArray();
    //
    //dd($respuesta);
                                            //distinto casino
    if(!empty($request->id_casino2) && !empty($request->id_moneda)){
      $respuesta2  = ImportacionMensualMesas::whereYear('fecha_mes','=',$request->anio)
                                              ->where('id_casino','=',$request->id_casino2)
                                              ->where('id_moneda','=',$request->id_moneda)
                                              ->where('validado','=',1)
                                              ->get()->toArray();
    }else{
      //mismo casino distinta moneda
      if(!empty($request->id_casino) && !empty($request->id_moneda2)){
        $respuesta2  = ImportacionMensualMesas::whereYear('fecha_mes','=',$request->anio)
                                                ->where('id_casino','=',$request->id_casino)
                                                ->where('id_moneda','=',$request->id_moneda2)
                                                ->where('validado','=',1)
                                                ->get()->toArray();
      }else{
        // $this->generarImpMensualesAPartirDeInfFinales();
        if(count($respuesta) != 0){
          $respuesta2 = [];
        }else {
          return response()->json(['errors' => 'Sin datos.'
                                  ], 422);
        }
      }
    }

    return response()->json(['casino1' => $respuesta,
                          'casino2' =>$respuesta2
                          ], 200);
  }


  private function generarImpMensualesAPartirDeInfFinales(){
    $casinos = Casino::all();
    foreach ($casinos as $casino) {
      $informesFinales = InformeFinalMesas::where('id_casino','=',$casino->id_casino)
                                            //->where('anio_inicio','=',2017)
                                            ->orderBy('anio_inicio','asc')
                                            ->get();
      //dd($informesFinales->first()->detalles->values());
      foreach ($informesFinales as $ifn) {
        $impMensuales = ImportacionMensualMesas::whereYear('fecha_mes','=',$ifn->anio_inicio)
                                                ->where('id_casino','=',$ifn->id_casino)
                                                //->where('validado','=',1)
                                                ->get();
        if(count($impMensuales)==0){
          foreach ($ifn->detalles as $cuota) {
            if($cuota->nro_mes <= $cuota->nro_cuota) {
              $anio = $ifn->anio_final;
            }else {
              $anio = $ifn->anio_inicio;
            }
            if($cuota->mes_casino->nro_cuota != 1 &&
                $cuota->mes_casino->nro_cuota != 13
              ){
              $impMNew = new ImportacionMensualMesas;
              $impMNew->fecha_mes = $anio.'-'.$cuota->mes_casino->nro_mes.'-01';
              $impMNew->nombre_csv = 'no matter.-';
              $impMNew->id_casino = $ifn->id_casino;
              $impMNew->id_moneda = 1;
              $impMNew->total_drop_mensual = 0;
              $impMNew->diferencias = 0;
              $impMNew->validado = 1;
              $impMNew->observacion = 'Autogenerado a partir de informes finales';
              $impMNew->utilidad_calculada =  $cuota->bruto_peso;
              $impMNew->retiros_mes = 0;
              $impMNew->reposiciones_mes = 0;
              $impMNew->saldo_fichas_mes = 0;
              $impMNew->total_utilidad_mensual =  $cuota->bruto_peso;
              $impMNew->save();
            } else {
              // es 1 ->busco la 13
              if($cuota->mes_casino->nro_cuota == 1){
                $latrece = 0;
                $det = DetalleInformeFinalMesas::join('mes_casino','mes_casino.id_mes_casino','=','detalle_informe_final_mesas.id_mes_casino')
                          ->where('id_informe_final_mesas','=',$ifn->id_informe_final_mesas)
                          ->where('mes_casino.nro_cuota','like',13)
                          ->get()->first();
                          if($det != null)  {
                            $latrece = $det->bruto_peso;
                          }


                $impMNew = new ImportacionMensualMesas;
                $impMNew->fecha_mes = $anio.'-'.$cuota->mes_casino->nro_mes.'-01';
                $impMNew->nombre_csv = 'no matter.-';
                $impMNew->id_casino = $ifn->id_casino;
                $impMNew->id_moneda = 1;
                $impMNew->total_drop_mensual = 0;
                $impMNew->diferencias = 0;
                $impMNew->validado = 1;
                $impMNew->observacion = 'Autogenerado a partir de informes finales';
                $impMNew->utilidad_calculada =  $cuota->bruto_peso + $latrece;
                $impMNew->retiros_mes = 0;
                $impMNew->reposiciones_mes = 0;
                $impMNew->saldo_fichas_mes = 0;
                $impMNew->total_utilidad_mensual =  $cuota->bruto_peso + $latrece;
                $impMNew->save();
              }else{
                if($cuota->mes_casino->nro_cuota != 13){
                dd($cuota);}
              }
            }
          }
        }//else de importaciones
      }
    }
  }
}
