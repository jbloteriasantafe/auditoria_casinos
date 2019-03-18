<?php
namespace App\Http\Controllers\Mesas\InformeFiscalizadores;

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

use App\Mesas\ApuestaMinimaJuego;
use App\Mesas\Mesa;

use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Dompdf\Dompdf;

use PDF;
use App\Mesas\InformeFiscalizadores;

use Carbon\Carbon;

use Exception;

class ABMInformesFiscalizadoresController extends Controller
{
  /**
   * Create a new controller instance.
   *
   * @return void
   */
  public function __construct()
  {
      $this->middleware(['tiene_permiso:m_ver_seccion_informe_fiscalizadores']);
  }

  public function crearInforme($casino, $fecha){
      $minima = ApuestaMinimaJuego::where('id_casino','=',$casino->id_casino)
                                    ->where('id_moneda','=',1)
                                    ->first();

      $mesas = Mesa::where('id_casino','=',$casino->id_casino)->count();
      $informe = new InformeFiscalizadores;
      $informe->fecha = $fecha;
      $informe->cant_cierres = 0;
      $informe->cant_aperturas = 0;
      $informe->cant_mesas_con_diferencia = 0;
      $informe->pendiente = 0;//que está finalizado.
      $informe->cant_mesas_totales = $mesas;
      $informe->cumplio_minimo = 0;
      $informe->cantidad_abiertas_con_minimo = 0;
      $informe->cant_mesas_abiertas = 0;
      $informe->apuesta_minima()->associate($minima->id_apuesta_minima);
      $informe->casino()->associate($casino->id_casino);
      $informe->save();
      //dd($informe);
      return $informe;
  }

}
