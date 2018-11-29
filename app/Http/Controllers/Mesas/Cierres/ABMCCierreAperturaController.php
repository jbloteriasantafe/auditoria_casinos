<?php

namespace App\Http\Controllers\Mesas\Cierres;

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
use App\SecRecientes;
use App\Http\Controllers\UsuarioController;
use App\Mesas\Mesa;
use App\Mesas\JuegoMesa;
use App\Mesas\SectorMesas;
use App\Mesas\TipoMesa;
use App\Mesas\Cierre;
use App\Mesas\Apertura;
use App\Mesas\CierreApertura;
use App\Mesas\DetalleApertura;
use App\Mesas\DetalleCierre;
use App\Mesas\EstadoCierre;
use App\Mesas\TipoCierre;

//validacion de cierres
class ABMCCierreAperturaController extends Controller
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
    $this->middleware(['tiene_permiso:m_gestionar_cierres']);
  }

  public function asociarAperturaACierre(Apertura $apertura){
    $cierre = Cierre::where('id_mesa_de_panio','=',$apertura->id_mesa_de_panio)
                      ->orderBy('fecha' , 'DESC')
                      ->first();
    if(count($cierre) == 1){
      $mesa = Mesa::find($apertura->id_mesa_de_panio);
      $caobjetct = new CierreApertura;
      $c  = $cierre->id_cierre_mesa;

      //$caobjetct->controlador()->associate();??????????????????que controlador? el que valida la asociación?
      $caobjetct->apertura()->associate($apertura->id_apertura_mesa);
      $caobjetct->cierre()->associate($c);
      $caobjetct->estado_cierre()->associate(1);
      $caobjetct->mesa()->associate($mesa->id_mesa_de_panio);
      $caobjetct->juego()->associate($mesa->id_juego_mesa);
      $caobjetct->save();

      $this->ascociarDetalles($apertura,$cierre);
    }
  }

  public function ascociarDetalles($apertura,$cierre){
    $det_aperturas_con_Dcierres = DB::table('detalle_apertura')
      ->select('detalle_apertura.id_detalle_apertura','detalle_cierre.id_detalle_cierre')
      ->join('detalle_cierre','detalle_apertura.id_ficha','=','detalle_cierre.id_ficha')
      ->where('detalle_apertura.id_apertura_mesa',$apertura->id_apertura_mesa)
      ->where('detalle_cierre.id_cierre_mesa',$cierre->id_cierre_mesa)
      ->get();

    foreach ($det_aperturas_con_Dcierres as $det) {
      $det_ap = DetalleApertura::find($det->id_detalle_apertura);
      $det_ap->detalle_cierre()->associate($det->id_detalle_cierre);
      $det_ap->save();
    }

  }

}
