<?php

namespace App\Http\Controllers\Mesas\Mesas;

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
use App\Http\Controllers\RolesPermissions\RoleFinderController;

use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

use App\Mesas\Mesa;
use App\Mesas\JuegoMesa;
use App\Mesas\SectorMesas;
use App\Mesas\TipoMesa;
use App\Mesas\Cierre;
use App\Mesas\Apertura;
use App\Mesas\DetalleApertura;
use App\Mesas\EstadoCierre;
use App\Mesas\TipoCierre;
use App\Mesas\MesasSorteadas;

use App\Http\Controllers\Mesas\InformesMesas\ABCMesasSorteadasController;

use Exception;

//validacion de cierres
class SorteoMesasController extends Controller
{
  private static $atributos = [
    'fecha' => 'Fecha',
    'id_mesa_de_panio'=> 'Mesa de Paño',
  ];

  /**
   * Create a new controller instance.
   *
   * @return void
   */
  public function __construct()
  {
    $this->middleware(['tiene_permiso:m_sortear_mesas']);
  }

  public function sortear($id_casino,$fecha_backup){
    $informesSorteadas = new ABCMesasSorteadasController;
    $cantidadSFMELRULETA = 4;
    $cantidadRosarioRULETA = 8;
    $cantidadSFMELCARTAS = 4;
    $cantidadRosarioCARTAS = 8;
    $ruletasDados = null;
    $cartas = null;
    try{
      switch($id_casino){
        case 3:
          $ruletasDados = $this->getMesas($cantidadRosarioRULETA,$id_casino,[1,3],$fecha_backup);
          $cartas = $this->getMesas($cantidadRosarioCARTAS,$id_casino,[2],$fecha_backup);
          break;
        default:
          $ruletasDados = $this->getMesas($cantidadSFMELRULETA,$id_casino,[1,3],$fecha_backup);
          $cartas = $this->getMesas($cantidadSFMELCARTAS,$id_casino,[2],$fecha_backup);
      }
      $informesSorteadas->almacenarSorteadas($ruletasDados,$cartas,$id_casino,$fecha_backup);
      return ['ruletasDados' => $ruletasDados,'cartas' => $cartas];
    }catch(Exception $e){
                dd($e);
       throw new \App\Exceptions\PlanillaException('No se pudo realizar el sorteo de mesas.');
    }
  }

  private function getMesas($cantidad, $id_casino, $tipo,$fecha_backup){
    // $tipo = [3];
    // $id_casino = 1;
    // $cantidad = 2;
    $mesas = array();
    $mesas_pond = $this->obtenerPonderadas($tipo,$id_casino,$fecha_backup);
    //dd($mesas_pond->cantidad);
    if($mesas_pond->cantidad){
      if($mesas_pond->cantidad == 1){
        $mesas = DB::table('mesa_de_panio')
                            ->join('juego_mesa','juego_mesa.id_juego_mesa','=','mesa_de_panio.id_juego_mesa')
                            ->join('tipo_mesa','juego_mesa.id_tipo_mesa','=','tipo_mesa.id_tipo_mesa')
                            ->whereIn('mesa_de_panio.id_tipo_mesa',$tipo)
                            ->whereIn('mesa_de_panio.id_casino',[$id_casino])
                            ->whereNotIn('mesa_de_panio.id_mesa_de_panio',[$mesas_pond->mesas[0]->id_mesa_de_panio])
                            ->whereNull('mesa_de_panio.created_at')
                            ->inRandomOrder()
                            ->take($cantidad-$mesas_pond->cantidad)
                            ->get();
        $mesas = $mesas->merge($mesas_pond->mesas);

        /*
        $collection = collect(['product_id' => 1, 'price' => 100]);

        $merged = $collection->merge(['price' => 200, 'discount' => false]);

        $merged->all();
        */
      }else{
        $mesas = DB::table('mesa_de_panio')
                            ->join('juego_mesa','juego_mesa.id_juego_mesa','=','mesa_de_panio.id_juego_mesa')
                            ->join('tipo_mesa','juego_mesa.id_tipo_mesa','=','tipo_mesa.id_tipo_mesa')
                            ->whereIn('mesa_de_panio.id_tipo_mesa',$tipo)
                            ->whereIn('mesa_de_panio.id_casino',[$id_casino])
                            ->whereNotIn('mesa_de_panio.id_mesa_de_panio',[$mesas_pond->mesas[0]->id_mesa_de_panio,$mesas_pond->mesas[1]->id_mesa_de_panio])
                            ->whereNull('mesa_de_panio.created_at')
                            ->inRandomOrder()
                            ->take($cantidad-$mesas_pond->cantidad)
                            ->get();
        $mesas = $mesas->merge($mesas_pond->mesas);
      }
    }else{
      $mesas = DB::table('mesa_de_panio')
                          ->join('juego_mesa','juego_mesa.id_juego_mesa','=','mesa_de_panio.id_juego_mesa')
                          ->join('tipo_mesa','juego_mesa.id_tipo_mesa','=','tipo_mesa.id_tipo_mesa')
                          ->whereIn('mesa_de_panio.id_tipo_mesa',$tipo)
                          ->whereIn('mesa_de_panio.id_casino',[$id_casino])
                          ->whereNull('mesa_de_panio.created_at')
                          ->inRandomOrder()
                          ->take($cantidad)
                          ->get();

    }
    return $mesas;
  }

  private function obtenerPonderadas($tipo,$id_casino,$fecha_backup){
    $cantidad = 0;
    $date = \Carbon\Carbon::today()->subDays(30);

    $aperturas = DB::table('apertura_mesa')
                            ->select(DB::raw('count(id_mesa_de_panio) as cant_mesa'),'id_mesa_de_panio')
                            ->whereIn('id_tipo_mesa',$tipo)
                            ->whereIn('id_casino',[$id_casino])
                            ->where('fecha', '>=', date($date))
                            ->groupBy('id_mesa_de_panio')
                            ->orderBy('cant_mesa','desc')
                            ->get();

    $cantidad = count($aperturas);
    //dd($cantidad);
    if(!isset($aperturas)){
      $mesas = DB::table('mesa_de_panio')
                          ->join('juego_mesa','juego_mesa.id_juego_mesa','=','mesa_de_panio.id_juego_mesa')
                          ->join('tipo_mesa','juego_mesa.id_tipo_mesa','=','tipo_mesa.id_tipo_mesa')
                          ->whereIn('mesa_de_panio.id_casino',[$id_casino])
                          ->whereIn('id_mesa_de_panio',[$aperturas[0]->id_mesa_de_panio,$aperturas[$cantidad-1]->id_mesa_de_panio])
                          ->get();
    }else{
      $mesas =array();
      $coss = new \stdClass();
      $coss->id_mesa_de_panio = 0;
      $mesas[] = $coss;
      $cantidad = 0;
    }
    $rta = new \stdClass();
    $rta->mesas = $mesas;
    $rta->cantidad = $cantidad;
    return $rta;

  }


}
