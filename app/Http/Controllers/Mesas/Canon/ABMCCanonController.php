<?php

namespace App\Http\Controllers\Mesas\Canon;

use Auth;
use Session;
use Illuminate\Http\Request;
use Response;
use App\Http\Controllers\Controller;
use Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Hash;

use App\Usuario;
use App\Casino;
use Carbon\Carbon;
use App\SecRecientes;
use App\Http\Controllers\RolesPermissions\RoleFinderController;

use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use App\Http\Controllers\UsuarioController;

use App\Mesas\Mesa;
use App\Mesas\Moneda;
use App\Mesas\ImagenesBunker;
use App\Mesas\DetalleImgBunker;
use App\Mesas\Cierre;
use App\Mesas\ImportacionDiariaMesas;
use App\Mesas\Canon;

//los valores "reales" del canon se almacenan en la actualizacion, y aca los que se cobran.
class ABMCCanonController extends Controller {
  public function __construct()
  {
    $this->middleware(['tiene_permiso:m_abmc_canon']);
  }

  private static $instance;

  public static function getInstancia() {
      if(!isset(self::$instance)){
          self::$instance = new ABMCCanonController();
      }
      return self::$instance;
  }

  public function obtenerCanon($id_casino){
    $canon = Canon::where('id_casino','=',$id_casino)->get()->first();
    if(empty($canon) || $canon == null){
      $casino = Casino::find($id_casino);
      $ff = date('m',strtotime($casino->fecha_inicio));
      $meshoy = date('m');
      //si el mes de pago es mayor o igual al mes de creacion del casino =>
      //el año inicio del canon es el actual, sino es el anterior
      if($ff >= $meshoy){
        $periodo_anio_inicio = date('Y')-1;
      }else{
        $periodo_anio_inicio = date('Y');
      }
      $canon = new Canon;
      $canon->id_casino= $id_casino;
      $canon->periodo_anio_inicio = $periodo_anio_inicio;
      $canon->periodo_anio_fin= ($periodo_anio_inicio+1);
      $canon->valor_base_dolar = 0;
      $canon->valor_base_euro = 0;
      $canon->valor_real_dolar = 0;
      $canon->valor_real_euro = 0;
      $canon->save();
    }
    return ['canon' => $canon];
  }

  public function modificar(Request $request){
    $validator=  Validator::make($request->all(),[
      'id_casino' => 'required|exists:casino,id_casino',
      'valor_base_dolar' =>  ['required',
                          'regex:/^\d\d?\d?\d?\d?\d?\d?\d?([,|.]?\d?\d?\d?)?$/'],
      'valor_base_euro' =>  ['required',
                          'regex:/^\d\d?\d?\d?\d?\d?\d?\d?([,|.]?\d?\d?\d?)?$/']
    ], [], [])->after(function($validator){

    })->validate();
    if(isset($validator)){
      if ($validator->fails()){
          return ['errors' => $validator->messages()->toJson()];
          }
     }

    $canon = Canon::where('id_casino','=',$request->id_casino)->get()->first();

    if($request->id_casino != $canon->id_casino ||
        $request->valor_base_euro != $canon->valor_base_euro ||
        $request->valor_base_dolar != $canon->valor_base_dolar){

        $canon->delete();
        $nuevo_canon = new Canon;
        $nuevo_canon->id_casino= $canon->id_casino;
        $nuevo_canon->periodo_anio_inicio = $canon->periodo_anio_inicio;
        $nuevo_canon->periodo_anio_fin= $canon->periodo_anio_fin;
        $nuevo_canon->valor_base_dolar = $request->valor_base_dolar;
        $nuevo_canon->valor_base_euro = $request->valor_base_euro;
        $nuevo_canon->save();

        if($canon->valor_real_euro != 0 && $canon->valor_real_dolar != 0){
          $nuevo_canon->valor_real_euro = $canon->valor_real_euro;
          $nuevo_canon->valor_real_dolar = $canon->valor_real_dolar;
        }else{
          $nuevo_canon->valor_real_euro = 0;
          $nuevo_canon->valor_real_dolar = 0;
        }
        $nuevo_canon->save();


        return ['ok'];
    }else{
      return ['sin cambios'];
    }

  }

  public function mesesCuotasCanon(Request $request,$id_casino){
    $usuario = UsuarioController::getInstancia()->obtenerUsuario($request);
    if($usuario == null || !$usuario->usuarioTieneCasino($id_casino)){
      return ['error' => 'El usuario no tiene accesso a ese casino'];
    }

    $generar_cuota = function($fecha_inicio,$fecha_fin,$nro_cuota) use ($id_casino){
      return (object)[
        'nro_cuota'  => $nro_cuota,
        'nro_mes'    => intval($fecha_fin->format('m')),
        'dia_inicio' => intval($fecha_inicio->format('d')),
        'dia_fin'    => intval($fecha_fin->format('d'))
      ];
    };

    $casino = Casino::find($id_casino);
    $fecha = new \DateTime($casino->fecha_inicio);
    $cuotas = [];
    for($nro_cuota = 1;$nro_cuota < 13;$nro_cuota++){
      $desde = $fecha;
      $hasta = (clone $fecha)->modify('last day of');
      $cuotas[] = $generar_cuota($desde,$hasta,$nro_cuota);
      $fecha->modify('+1 month')->modify('first day of');
    }

    $fecha = new \DateTime($casino->fecha_inicio);
    //Si empezo iniciado el mes se le agrega una cuota mas con los dias que le faltaron
    if($fecha->format('d') != '1'){
      $desde = (clone $fecha)->modify('first day of');
      $hasta = (clone $fecha)->modify('-1 day');
      $cuotas[] = $generar_cuota($desde,$hasta,13);
    }

    return ['casino' => $casino, 'meses' => $cuotas];
  }
}
