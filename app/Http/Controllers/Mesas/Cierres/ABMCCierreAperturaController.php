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
use App\MesCasino;

use App\Http\Controllers\Mesas\InformeFiscalizadores\GenerarInformesFiscalizadorController;


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
      //buscar id mes casino para hacer join con el detalle del informe
      $apertura = Apertura::findOrFail($id_apertura);
      $ffdia = Carbon::parse($apertura->fecha)->day-0;
      $ffmes = Carbon::parse($apertura->fecha)->format('n');
      $ffy = Carbon::parse($apertura->fecha)->format('Y');
      $mes = MesCasino::where('id_casino','=',$apertura->id_casino)
                          ->where('dia_inicio','<=',$ffdia)
                          ->where('dia_fin','>=',$ffdia)
                          ->where('nro_mes','=',$ffmes)
                          ->get()->first();
      //dd($mes,$ffdia,$ffmes,$apertura->id_casino);
      // foreach ($meses as $m) {
      //   if($m->dia_inicio <= $ff[2] && $m->dia_fin >= $ff[2]){
      //     $mes = $m;
      //     break;
      //   }
      // }

      if($mes->nro_cuota <= $mes->nro_mes){
        $anioCuota = 'anio_inicio';
      }else{
        $anioCuota = 'anio_final';
      }
      $detInformePago = DetalleInformeFinalMesas::join('informe_final_mesas','informe_final_mesas.id_informe_final_mesas','=','detalle_informe_final_mesas.id_informe_final_mesas')
                                                  ->where('id_mes_casino','=',$mes->id_mes_casino)
                                                  ->where('informe_final_mesas.'.$anioCuota,'=',$ffy)->get();

      $fechamascincuenta = Carbon::parse($apertura->fecha)->addDay(50)->format('Y-m-d');
      $fhoy = Carbon::now()->format('Y-m-d');
      if(count($detInformePago) == 0 && $fhoy <= $fechamascincuenta){
        $vinculo = $apertura->cierre_apertura;
        $cierre = $vinculo->cierre;
        $cierre->estado_cierre()->associate(1);
        $cierre->save();
        $vinculo->delete();
        $apertura->estado_cierre()->associate(1);
        $apertura->save();
        return 1;
      }
    }

    public function revivirElPasado(){
      $aperturasValidadas = CierreApertura::all();
      $informeController = new GenerarInformesFiscalizadorController;
      foreach($aperturasValidadas as $apOk){
        //agregarle los atributos que le falten al cierre_apertura
        $apOk->fecha_produccion = $apOk->apertura->fecha;
        $apOk->diferencias = $this->calcularDifferencias($apOk);
        $apOk->casino()->associate($apOk->apertura->id_casino);
        $apOk->save();

        $informeController->iniciarInformeDiario($apOk);
      }
    }

    public function calcularDifferencias($apOk){
      $apertura = $apOk->apertura;
      $cierre = $apOk->cierre;
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
  }
