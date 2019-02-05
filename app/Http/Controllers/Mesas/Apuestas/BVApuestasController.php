<?php

namespace App\Http\Controllers\Mesas\Apuestas;

use Auth;
use Session;
use Illuminate\Http\Request;
use Response;
use App\Http\Controllers\Controller;
use Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Hash;

use App\Usuario;
use App\Casino;
use App\Turno;
use App\SecRecientes;

use App\Mesas\Mesa;
use App\Mesas\JuegoMesa;
use App\Mesas\SectorMesas;
use App\Mesas\TipoMesa;
use App\Mesas\RelevamientoApuestas;
use App\Mesas\DetalleRelevamientoApuestas;

use DateTime;
use Dompdf\Dompdf;

use PDF;
use View;

class BVApuestasController extends Controller
{
  private static $atributos = [
    'id_cierre_mesa' => 'Identificacion del Cierre',
    'fecha' => 'Fecha',
    'hora_inicio' => 'Hora de Apertura',
    'hora_fin' => 'Hora del Cierre',
    'total_pesos_fichas_c' => 'Total de pesos en Fichas',
    'total_anticipos_c' => 'Total de Anticipos',
    'id_fiscalizador'=>'Fiscalizador',
    'id_tipo_cierre'=> 'Tipo de Cierre',
    'id_mesa_de_panio'=> 'Mesa de Paño',
    'id_estado_cierre'=>'Estado',
  ];

  /**
   * Create a new controller instance.
   *
   * @return void
   */
  public function __construct()
  {
      $this->middleware(['tiene_permiso:m_validar_eliminar_relevamientos_apuestas']);
  }

  public function validar(Request $request){
    $validator=  Validator::make($request->all(),[
      'id_relevamiento' => 'required|exists:relevamiento_apuestas_mesas,id_relevamiento_apuestas',
      'observaciones' => 'nullable|max:200'
    ], array(), self::$atributos)->after(function($validator){  })->validate();
    if(isset($validator)){
      if ($validator->fails()){
          return ['errors' => $validator->messages()->toJson()];
          }
     }
    $user = UsuarioController::getInstancia()->buscarUsuario(session('id_usuario'))['usuario'];
    $relevamiento = RelevamientoApuestas::find($request['id_relevamiento']);
    $relevamiento->estado()->associate(4);//Validado
    $relevamiento->observaciones_validacion = $request['observaciones'];
    $relevamiento->controlador()->associate($user->id);
    $relevamiento->save();

    return response()->json(['exito' => 'Relevamiento validado!'], 200);
  }

  public function eliminar($id_relevamiento){
    $relevamiento = RelevamientoApuestas::findOrFail($id_relevamiento);
    $relevamiento->delete();

    return response()->json(['exito' => 'Relevamiento eliminado!'], 200);
  }


}
