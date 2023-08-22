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
use App\Http\Controllers\UsuarioController;
use App\Mesas\Mesa;
use App\Mesas\JuegoMesa;
use App\Mesas\SectorMesas;
use App\Mesas\TipoMesa;
use App\Mesas\RelevamientoApuestas;
use App\Mesas\DetalleRelevamientoApuestas;
use App\Http\Controllers\Mesas\InformeFiscalizadores\GenerarInformesFiscalizadorController;

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
    $user = UsuarioController::getInstancia()->quienSoy()['usuario'];
    
    $validator=  Validator::make($request->all(),[
      'id_relevamiento_apuestas' => 'required|exists:relevamiento_apuestas_mesas,id_relevamiento_apuestas',
      'observaciones_validacion' => 'nullable|string|max:200'
    ], [
      'required' => 'El valor es requerido',
      'exists' => 'El valor no existe',
      'max' => 'El valor supera el limite',
    ], self::$atributos)->after(function($validator) use ($user){
      if($validator->errors()->any()) return;
      $data = $validator->getData();
      $rel = RelevamientoApuestas::find($data['id_relevamiento_apuestas']);
      if(!$user->usuarioTieneCasino($rel->id_casino)){
        return $validator->errors()->add('id_relevamiento_apuestas_mesas','No está autorizado para realizar esta accion.');
      }
    })->validate();
    
    return DB::transaction(function() use ($request,$user){
      $relevamiento = RelevamientoApuestas::find($request['id_relevamiento_apuestas']);
      $relevamiento->estado()->associate(4);//Validado
      $relevamiento->observaciones_validacion = $request['observaciones_validacion'];
      $relevamiento->controlador()->associate($user->id_usuario);
      $relevamiento->save();

      $informeController = new GenerarInformesFiscalizadorController;
      $informeController->agregarRelacionValoresApuestas($relevamiento);
      
      return response()->json(['exito' => 'Relevamiento validado!'], 200);
    });
  }

  public function eliminar($id_relevamiento){
    $relevamiento = RelevamientoApuestas::findOrFail($id_relevamiento);
    $relevamiento->delete();

    return response()->json(['exito' => 'Relevamiento eliminado!'], 200);
  }


}
