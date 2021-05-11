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
use App\Http\Controllers\Mesas\Importaciones\Mesas\ImportadorController;
use App\Http\Controllers\Mesas\Importaciones\Mesas\MensualController;

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
use App\Mesas\Cierre;
use App\Mesas\CampoModificado;

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

class BCMensualesController extends Controller
{
  public function obtenerDatosGraficos(Request $request){
    $monthNames_N = [".-." => 0,"Enero" => 1, "Febrero" => 2, "Marzo" => 3, "Abril" => 4, "Mayo" => 5, "Junio" => 6,
      "Julio" => 7, "Agosto" => 8, "Septiembre" => 9, "Octubre" => 10, "Noviembre" => 11, "Diciembre" => 12];

    $fecha = explode('-',$request->fecha);
    $anio = null;
    $nombre_mes = null;
    $nro_mes = null;
    if(count($fecha) < 2){
      $hoy_m_1mes = Carbon::now()->subMonths(1);
      $anio = $hoy_m_1_mes->format('Y');
      $nro_mes = $hoy_m_1_mes->format('m');
    }else{
      $anio = $fecha[0];
      $nro_mes = $monthNames_N[$fecha[1]];
    }

    $por_moneda = ImportadorController::getInstancia()->mensualPorMonedaPorJuego($request->id_casino,[$anio,$nro_mes]);
    $ret = [];
    foreach($por_moneda as $moneda){
      $m = new \StdClass;
      $total_por_sigla = [];
      $m->moneda = $moneda['moneda'];

      foreach($moneda['juegos'] as $j){//Agrupo por sigla
        $siglas = $j->siglas_juego;
        if(!array_key_exists($siglas,$total_por_sigla)){
          $total_por_sigla[$siglas] = 0;
        }
        $total_por_sigla[$siglas]+=$j->utilidad;
      }

      $total_por_nombre = [];
      foreach($total_por_sigla as $sigla => $utilidad){//Agrupo por nombre si se puede
        $jm = JuegoMesa::whereNull('deleted_at')->where('siglas','=',$sigla)
        ->where('id_casino','=',$request->id_casino)->first();
        if(is_null($jm)) $total_por_nombre[$sigla]            = $utilidad;
        else             $total_por_nombre[$jm->nombre_juego] = $utilidad;
      }

      $m->utilidad = $total_por_nombre;
      $ret[] = $m;
    }
    return['por_moneda' => $ret];
  }
}
