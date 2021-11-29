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
    $cantidadRULETA = $id_casino == 3? 8 : 4;
    $cantidadCARTAS = $id_casino == 3? 8 : 4;
    $ruletasDados = null;
    $cartas = null;
    try{
      $mesas_sorteadas = DB::table('mesas_sorteadas')
      ->where('fecha_backup','=',$fecha_backup)->where('id_casino','=',$id_casino)->get();
      if(count($mesas_sorteadas) > 0){
        $conio = $mesas_sorteadas->first();
        $tipos = json_decode($conio->mesas);
        return ['ruletas' => $tipos->ruletas,'cartasDados' =>$tipos->cartasDados];
      }else{
        $ruletas = $this->getMesas($cantidadRULETA,$id_casino,[1],$fecha_backup);
        $cartasDados = $this->getMesas($cantidadCARTAS,$id_casino,[2,3],$fecha_backup);
        $informesSorteadas->almacenarSorteadas($ruletas,$cartasDados,$id_casino,$fecha_backup);
        return ['ruletas' => $ruletas,'cartasDados' => $cartasDados];
      }
    }catch(Exception $e){
       throw new \App\Exceptions\PlanillaException('No se pudo realizar el sorteo de mesas.');
    }
  }

  private function getMesas($cantidad, $id_casino, $tipo,$fecha_backup){
    $mesas = array();
    $mesas_pond = $this->obtenerPonderadas($tipo,$id_casino,$fecha_backup);

    if($mesas_pond->cantidad){
      if($mesas_pond->cantidad == 1){
        $mesas = DB::table('mesa_de_panio')
        ->join('juego_mesa','juego_mesa.id_juego_mesa','=','mesa_de_panio.id_juego_mesa')
        ->join('tipo_mesa','juego_mesa.id_tipo_mesa','=','tipo_mesa.id_tipo_mesa')//Para que este join?
        ->whereIn('juego_mesa.id_tipo_mesa',$tipo)
        ->where('mesa_de_panio.id_casino','=',$id_casino)
        ->whereNotIn('mesa_de_panio.id_mesa_de_panio',[$mesas_pond->mesas[0]->id_mesa_de_panio])
        ->whereNull('mesa_de_panio.deleted_at')
        ->inRandomOrder()
        ->take($cantidad-$mesas_pond->cantidad)
        ->get();
        $mesas = $mesas->merge($mesas_pond->mesas);
      }else{
        $mesas = DB::table('mesa_de_panio')
        ->join('juego_mesa','juego_mesa.id_juego_mesa','=','mesa_de_panio.id_juego_mesa')
        ->join('tipo_mesa','juego_mesa.id_tipo_mesa','=','tipo_mesa.id_tipo_mesa')//Para que este join?
        ->whereIn('juego_mesa.id_tipo_mesa',$tipo)
        ->where('mesa_de_panio.id_casino','=',$id_casino)
        ->whereNotIn('mesa_de_panio.id_mesa_de_panio',[$mesas_pond->mesas[0]->id_mesa_de_panio,$mesas_pond->mesas[1]->id_mesa_de_panio])
        ->whereNull('mesa_de_panio.deleted_at')
        ->inRandomOrder()
        ->take($cantidad-$mesas_pond->cantidad)
        ->get();
        $mesas = $mesas->merge($mesas_pond->mesas);
      }
    }else{
      $mesas = DB::table('mesa_de_panio')
      ->join('juego_mesa','juego_mesa.id_juego_mesa','=','mesa_de_panio.id_juego_mesa')
      ->join('tipo_mesa','juego_mesa.id_tipo_mesa','=','tipo_mesa.id_tipo_mesa')//Para que este join?
      ->whereIn('juego_mesa.id_tipo_mesa',$tipo)
      ->where('mesa_de_panio.id_casino','=',$id_casino)
      ->whereNull('mesa_de_panio.deleted_at')
      ->inRandomOrder()
      ->take($cantidad)
      ->get();
    }
    return $mesas;
  }

  private function obtenerPonderadas($tipo,$id_casino,$fecha_backup){
    $cantidad = 0;
    $date = \Carbon\Carbon::today()->subDays(30);

    $aperturas = DB::table('apertura_mesa')->select(DB::raw('count(id_mesa_de_panio) as cant_mesa'),'id_mesa_de_panio')
    ->whereIn('id_tipo_mesa',$tipo)->where('id_casino','=',$id_casino)->where('fecha', '>=', date($date))
    ->groupBy('id_mesa_de_panio')
    ->orderBy('cant_mesa','desc')->get();

    $cantidad = count($aperturas);
    if(!isset($aperturas)){
      $mesas = DB::table('mesa_de_panio')
      ->join('juego_mesa','juego_mesa.id_juego_mesa','=','mesa_de_panio.id_juego_mesa')//Para que este join?
      ->join('tipo_mesa','juego_mesa.id_tipo_mesa','=','tipo_mesa.id_tipo_mesa')//Para que este join?
      ->whereIn('mesa_de_panio.id_casino','=',$id_casino)
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

  public function buscarBackUps($cas,$fecha){
    //debe retornar lo mismo que retorna sortear.
    $sorteadasController = new ABCMesasSorteadasController;
    try{
      $rta = $sorteadasController->obtenerSorteo($cas,$fecha);
    }catch(Exception $e){
      throw new \Exception("Sorteo no encontrado - llame a un ADMINISTRADOR".$cas.'-'.$fecha, 1);
      //hola admin -> cuando salga este mensaje deberás ejecutar el comando RAM:sortear
    }

    return ['ruletas' => $rta->mesas['ruletas'],'cartasDados' => $rta->mesas['cartasDados']];
  }
}
