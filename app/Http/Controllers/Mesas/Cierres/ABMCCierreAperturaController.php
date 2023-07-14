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
use App\Mesas\DetalleInformeFinalMesas;

use App\Http\Controllers\Mesas\InformeFiscalizadores\GenerarInformesFiscalizadorController;


use Carbon\Carbon;
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

    public function asociarAperturaACierre(Apertura $apertura,$id_cierre){
      $user = UsuarioController::getInstancia()->buscarUsuario(session('id_usuario'))['usuario'];
      $cierre = Cierre::find($id_cierre);
      $mesa = Mesa::find($apertura->id_mesa_de_panio);
      $caobject = new CierreApertura;

      $caobject->controlador()->associate($user->id_usuario);
      $caobject->apertura()->associate($apertura->id_apertura_mesa);
      $caobject->cierre()->associate($cierre->id_cierre_mesa);
      $caobject->estado_cierre()->associate(3);
      $caobject->mesa()->associate($mesa->id_mesa_de_panio);
      $caobject->juego()->associate($mesa->id_juego_mesa);
      $caobject->fecha_produccion = $apertura->fecha;
      $caobject->casino()->associate($cierre->id_casino);

      $diferencias = $this->ascociarDetalles($apertura,$cierre);

      if($diferencias || (count($cierre->detalles) != count($apertura->detalles))){
        $diferencias = 1;
      }
      $caobject->diferencias = $diferencias;
      $caobject->save();
      $cierre->estado_cierre()->associate(4);//CIERRE CON APERTURA
      $cierre->save();
      $informeController = new GenerarInformesFiscalizadorController;
      $informeController->iniciarInformeDiario($caobject);
    }

    public function ascociarDetalles($apertura,$cierre){
      $diferencias = 0;
      $det_aperturas_con_Dcierres = DB::table('detalle_apertura')
        ->select('detalle_apertura.id_detalle_apertura',
                 'detalle_cierre.id_detalle_cierre',
                 'detalle_cierre.monto_ficha',
                 'ficha.valor_ficha'
                 )
        ->join('detalle_cierre','detalle_apertura.id_ficha','=','detalle_cierre.id_ficha')
        ->join('ficha','ficha.id_ficha','=','detalle_apertura.id_ficha')
        ->where('detalle_apertura.id_apertura_mesa',$apertura->id_apertura_mesa)
        ->where('detalle_cierre.id_cierre_mesa',$cierre->id_cierre_mesa)
        ->get();

      foreach ($det_aperturas_con_Dcierres as $det) {
        $det_ap = DetalleApertura::find($det->id_detalle_apertura);
        $det_ap->detalle_cierre()->associate($det->id_detalle_cierre);
        $det_ap->save();
      }

      $deApertura = DB::table('detalle_apertura as DA')
                              ->select('DA.id_ficha',
                                       DB::raw( 'SUM(DA.cantidad_ficha * ficha.valor_ficha) as monto_ficha')
                                      )
                              ->join('ficha','ficha.id_ficha','=','DA.id_ficha')
                              ->where('DA.id_apertura_mesa','=',$apertura->id_apertura_mesa)
                              ->orderBy('ficha.valor_ficha')
                              ->groupBy('DA.id_ficha','DA.cantidad_ficha','ficha.valor_ficha')
                              ->get()->toArray();

      $deCierre = DB::table('detalle_cierre as DC')
                              ->select('DC.id_ficha',
                                       'DC.monto_ficha'
                                      )
                              ->join('ficha','ficha.id_ficha','=','DC.id_ficha')
                              ->where('DC.id_cierre_mesa','=',$cierre->id_cierre_mesa)
                              ->orderBy('ficha.valor_ficha')
                              ->get()->toArray();
      $diferencias = 0;
      foreach ($deApertura as $ap) {
        if(!$this->estaEn($ap,$deCierre)){
          $diferencias = 1;
          break;
        }
      }

      return $diferencias;
    }

    private function estaEn($ap,$deCierre){
      foreach ($deCierre as $ci) {
        if($ci->id_ficha == $ap->id_ficha &&
           $ci->monto_ficha == $ap->monto_ficha){
             return 1;
           }
      }
    }

    public function obtenerMesasConDiferencias($fecha){
      $resultados = CierreApertura::where('fecha_produccion','=',$fecha)
                                    ->where('diferencias','=',1)
                                    ->get();
      $diferencias = array();
      $todo = array();
      if(count($resultados) > 0){
        foreach ($resultados as $cierre_apertura) {
          $diff = $cierre_apertura->apertura->total_pesos_fichas_a -
                  $cierre_apertura->cierre->total_pesos_fichas_c;

          if($cierre_apertura->apertura->observacion == null){
            $obs = '';
          }else{
            $obs = $cierre_apertura->apertura->observacion;
          }
          $diferencias[] = [
                              'mesa' => $cierre_apertura->mesa->codigo_mesa,
                              'diferencia' => abs($diff),
                              'observacion' => $obs
                            ];
        }
        return $diferencias;
      }else{
        return null;
      }
    }

    public function desvincularApertura($id_apertura){
      $apertura = Apertura::findOrFail($id_apertura);
      $fechamascincuenta = Carbon::parse($apertura->fecha)->addDay(50)->format('Y-m-d');
      $fhoy = Carbon::now()->format('Y-m-d');
      if($fhoy <= $fechamascincuenta){
        DB::transaction(function() use ($apertura){
          $vinculo = $apertura->cierre_apertura;
          $cierre = $vinculo->cierre;
          $cierre->estado_cierre()->associate(1);
          $cierre->save();
          $vinculo->delete();
          $apertura->estado_cierre()->associate(1);
          $apertura->save();
        });
        return 1;
      }
      return 0;
    }
    
    private function getCierre($id_cierre_mesa,$id_apertura_mesa){
      if(!is_null($id_cierre_mesa)){
        $C = Cierre::find($id_cierre_mesa);
        if(is_null($C)) return $C;
        $CA = $C->cierre_apertura;
        if(!is_null($CA) && !is_null($id_apertura_mesa)){
          if($CA->id_apertura_mesa != $id_apertura_mesa) return null;
        }
        return $C;
      }
      else if(!is_null($id_apertura_mesa)){
        $A = Apertura::find($id_apertura_mesa);
        if(is_null($A)) return null;
        $CA = $A->cierre_apertura;
        if(is_null($CA)) return null;
        return $CA->cierre;
      }
      return null;
    }
    
    private function getApertura($id_cierre_mesa,$id_apertura_mesa){
      if(!is_null($id_apertura_mesa)){
        $A = Apertura::find($id_apertura_mesa);
        if(is_null($A)) return $A;
        $CA = $A->cierre_apertura;
        if(!is_null($CA) && !is_null($id_cierre_mesa)){
          if($CA->id_cierre_mesa != $id_cierre_mesa) return null;
        }
        return $A;
      }
      else if(!is_null($id_cierre_mesa)){
        $C = Cierre::find($id_cierre_mesa);
        if(is_null($C)) return null;
        $CA = $C->cierre_apertura;
        if(is_null($CA)) return null;
        return $CA->apertura;
      }
      return null;
    }
    
    public function getCierreApertura(){
      $idc = request()->id_cierre_mesa ?? null;
      $ida = request()->id_apertura_mesa ?? null;
      $C = $this->getCierre($idc,$ida);
      $A = $this->getApertura($idc,$ida);
      $cierre_apertura = null;
      if(!is_null($C)){
        $cierre_apertura = $C->cierre_apertura;
        
        $mesa = $C->mesa()->withTrashed()->get()->first();
        $juego = $mesa->juego()->withTrashed()->get()->first();
        $moneda = $C->moneda;
        if(empty($moneda)){
          $moneda = $mesa->moneda;
        }
        $fiscalizador = $C->fiscalizador()->withTrashed()->get()->first();

        $fecha = $C->created_at;
        if(is_null($fecha)){//created_at puede ser nulo por algun motivo
          $fecha = $C->fecha;
        }

        $detalles = DB::table('ficha as F')
        ->select('D.monto_ficha','F.valor_ficha','F.id_ficha','D.id_detalle_cierre')
        ->leftJoin('detalle_cierre as D',function ($join) use($C){
          $join->on('D.id_ficha','=','F.id_ficha')->where('D.id_cierre_mesa','=',$C->id_cierre_mesa);
        })
        ->join('ficha_tiene_casino as FC','FC.id_ficha','=','F.id_ficha')
        ->where('FC.id_casino','=',$C->id_casino)
        ->where(function($q) use ($fecha){
          return $q->where('FC.deleted_at','>',$fecha)->orWhereNull('FC.deleted_at');
        })
        ->where(function($q) use ($fecha){
          return $q->where('FC.created_at','<=',$fecha)->orWhereNotNull('D.id_ficha');
        })
        ->where('F.id_moneda','=',$moneda->id_moneda)
        ->orderBy('F.valor_ficha','desc')
        ->get();
        
        $datos = $C;
        $cierre = compact('datos','detalles','fiscalizador','mesa','juego','moneda');
      }
      
      $apertura = null;
      if(!is_null($A)){
        if(is_null($cierre_apertura)) $cierre_apertura = $A->cierre_apertura;
        
        $mesa = $A->mesa()->withTrashed()->get()->first();
        $juego = $mesa->juego()->withTrashed()->get()->first();
        $moneda = $A->moneda;
        if(empty($moneda)){
          $moneda = $mesa->moneda;
        }
        $fiscalizador = $A->fiscalizador()->withTrashed()->get()->first();
        $cargador = $A->cargador()->withTrashed()->get()->first();
        
        $detalles = DB::table('ficha as F')
        ->select('D.cantidad_ficha','F.valor_ficha','F.id_ficha','D.id_detalle_apertura',
                  DB::raw('SUM(D.cantidad_ficha * F.valor_ficha) as monto_ficha'))
        ->leftJoin('detalle_apertura as D',function ($join) use($A){
          $join->on('D.id_ficha','=','F.id_ficha')->where('D.id_apertura_mesa','=',$A->id_apertura_mesa);
        })
        ->join('ficha_tiene_casino as FC','FC.id_ficha','=','F.id_ficha')
        ->where('FC.id_casino','=',$A->id_casino)
        ->where(function($q) use ($A){//Usar fecha o created_at?
          return $q->where('FC.deleted_at','>',$A->created_at)->orWhereNull('FC.deleted_at');
        })
        ->where(function($q) use ($A){
          return $q->where('FC.created_at','<=',$A->created_at)->orWhereNotNull('D.id_ficha');
        })
        ->where('F.id_moneda','=',$A->id_moneda)
        ->groupBy('D.cantidad_ficha','F.valor_ficha','F.id_ficha','D.id_detalle_apertura')
        ->orderBy('F.valor_ficha','desc')->get();
        
        $datos = $A;
        $apertura = compact('datos','detalles','cargador','fiscalizador','mesa','juego','moneda');
      }
      
      return compact('cierre','apertura','cierre_apertura');
    }
  }
