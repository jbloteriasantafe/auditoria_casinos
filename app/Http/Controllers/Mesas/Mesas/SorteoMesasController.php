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
    $cantidadRULETA = $id_casino == 3? 8 : 4;
    $cantidadCARTAS = $id_casino == 3? 8 : 4;
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

        $sorteadas = new MesasSorteadas;
        $sorteadas->mesas = ['ruletas' => $ruletas->toArray(),
                              'cartasDados' => $cartasDados->toArray(),];
        $sorteadas->casino()->associate($id_casino);
        $sorteadas->fecha_backup = $fecha_backup;
        $sorteadas->save();

        return ['ruletas' => $ruletas,'cartasDados' => $cartasDados];
      }
    }catch(Exception $e){
       throw new \App\Exceptions\PlanillaException('No se pudo realizar el sorteo de mesas.');
    }
  }

  private function getMesas($cantidad, $id_casino, $tipo,$fecha_backup){
    $mesas = [];
    $ids_obligadas = $this->obtenerMesasObligatorias($tipo,$id_casino,$fecha_backup);
    $mesas_obligadas = DB::table('mesa_de_panio')
    ->join('juego_mesa','juego_mesa.id_juego_mesa','=','mesa_de_panio.id_juego_mesa')
    ->join('tipo_mesa','juego_mesa.id_tipo_mesa','=','tipo_mesa.id_tipo_mesa')//Para que este join?
    ->whereIn('juego_mesa.id_tipo_mesa',$tipo)
    ->where('mesa_de_panio.id_casino','=',$id_casino)
    ->whereIn('id_mesa_de_panio',$ids_obligadas)
    ->whereNull('mesa_de_panio.deleted_at')
    ->get();

    $mesas = DB::table('mesa_de_panio')
    ->join('juego_mesa','juego_mesa.id_juego_mesa','=','mesa_de_panio.id_juego_mesa')
    ->join('tipo_mesa','juego_mesa.id_tipo_mesa','=','tipo_mesa.id_tipo_mesa')//Para que este join?
    ->whereIn('juego_mesa.id_tipo_mesa',$tipo)
    ->where('mesa_de_panio.id_casino','=',$id_casino)
    ->whereNotIn('mesa_de_panio.id_mesa_de_panio',$ids_obligadas)
    ->whereNull('mesa_de_panio.deleted_at')
    ->inRandomOrder()
    ->take($cantidad-count($ids_obligadas))
    ->get()
    ->merge($mesas_obligadas);

    return $mesas;
  }

  //Retorna las mesas de $casino-$tipo obligadas a ser relevadas
  private function obtenerMesasObligatorias($tipo,$id_casino,$fecha_backup){
    $cantidad = 0;
    $date = \Carbon\Carbon::today()->subDays(30);
    
    $aperturas = DB::table('apertura_mesa')->select(DB::raw('count(id_mesa_de_panio) as cant_mesa'),'id_mesa_de_panio')
    ->whereIn('id_tipo_mesa',$tipo)->where('id_casino','=',$id_casino)->where('fecha', '>=', date($date))
    ->groupBy('id_mesa_de_panio')
    ->orderBy('cant_mesa','asc')->get();

    $ret = [];
    $cantidad = is_null($aperturas)? 0 : count($aperturas);
    //Hay al menos 1, agrego el primero
    if($cantidad > 0) $ret[] = $aperturas[0]->id_mesa_de_panio;
    //Antes se agregaba el menos relevado y el mas relevado, creo que es un bug porque el ultimo seria una retroalimentación positiva
    //if($cantidad > 1) $ret[] = $aperturas[$cantidad-1]->id_mesa_de_panio;
    if($cantidad > 1) $ret[] = $aperturas[1]->id_mesa_de_panio;

    $aperturas_a_pedido = DB::table('apertura_a_pedido as aap')
    ->select('aap.id_mesa_de_panio')
    ->join('mesa_de_panio as mp','mp.id_mesa_de_panio','=','aap.id_mesa_de_panio')
    ->where('mp.id_casino','=',$id_casino)
    ->where('aap.fecha_inicio','<=',$fecha_backup)->where('aap.fecha_fin','>=',$fecha_backup)->get();
    foreach($aperturas_a_pedido as $aap) $ret[] = $aap->id_mesa_de_panio;

    return $ret;
  }

  public function buscar($id_casino,$fecha,$tipo = 'CUALQUIERA'){
    if(!in_array($tipo,['REAL','BACKUP','CUALQUIERA'])){
      throw new Exception("Tipo '$tipo' invalido");
    }
    
    $reglas = [['fecha_backup','=',$fecha],['id_casino','=',$id_casino]];
    if($tipo == 'REAL'){
      $reglas[] = ['fecha_backup','<=',DB::raw('DATE(created_at)')];
    }
    if($tipo == 'BACKUP'){
      $reglas[] = ['fecha_backup','>',DB::raw('DATE(created_at)')];
    }
    return MesasSorteadas::where($reglas)->first();
  }
}
