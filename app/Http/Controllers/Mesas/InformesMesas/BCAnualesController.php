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
      'id_casino2' => 'nullable|exists:casino,id_casino',
      'id_moneda2' => 'nullable|exists:moneda,id_moneda',
    ], array(), self::$atributos)->after(function($validator){
      if($validator->getData()['id_casino'] == $validator->getData()['id_casino2']){
        $validator->errors()->add('id_casino2','Elija otro casino.' );
      }
    })->validate();
    if(isset($validator)){
      if ($validator->fails()){
          return ['errors' => $validator->messages()->toJson()];
          }
     }
    $respuesta  = ImportacionMensualMesas::whereYear('fecha_mes','=',$request->anio)
                                            ->where('id_casino','=',$request->id_casino)
                                            ->where('id_moneda','=',$request->id_moneda)
                                            ->where('validado','=',1)
                                            ->get()->toArray();
                                            //distinto casino
    if(!empty($request->id_casino2) && !empty($request->id_moneda)){
      $respuesta2  = ImportacionMensualMesas::whereYear('fecha_mes','=',$request->anio)
                                              ->where('id_casino','=',$request->id_casino2)
                                              ->where('id_moneda','=',$request->id_moneda)
                                              ->where('validado','=',1)
                                              ->get()->toArray();
    }else{
      //mismo casino distinta moneda
      if(!empty($request->id_casino1) && !empty($request->id_moneda2)){
        $respuesta2  = ImportacionMensualMesas::whereYear('fecha_mes','=',$request->anio)
                                                ->where('id_casino','=',$request->id_casino)
                                                ->where('id_moneda','=',$request->id_moneda2)
                                                ->where('validado','=',1)
                                                ->get()->toArray();
      }else{
        $respuesta2 = [];
      }
    }

    return response()->json(['casino1' => $respuesta,
                          'casino2' =>$respuesta2
                          ], 200);
  }
}
