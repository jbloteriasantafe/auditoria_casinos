<?php

namespace App\Http\Controllers\Turnos;

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

use App\User;
use App\Casino;
use App\Relevamiento;
use App\SecRecientes;
use App\Http\Controllers\RolesPermissions\RoleFinderController;

use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Carbon\Carbon;
use App\Turno;

class TurnosController extends Controller
{
  private static $atributos = [
    'id_mesa_de_panio' => 'Identificacion de la mesa',
    'nro_mesa' => 'Número de Mesa',
    'nombre' => 'Nombre de Mesa',
    'descripcion' => 'Descripción',
    'id_tipo_mesa' => 'Tipo de Mesa',
    'id_juego_mesa' => 'Juego de Mesa',
    'id_casino' => 'Casino',
    'id_moneda' => 'Moneda',
    'id_sector_mesas' => 'Sector',
  ];

  /**
   * Create a new controller instance.
   *
   * @return void
   */
  public function __construct()
  {
      $this->middleware(['permiso:abmc_turnos']);
  }


  /**
   * Store a newly created resource in storage.
   *
   * @param  \Illuminate\Http\Request  $request
   * @return \Illuminate\Http\Response
   */
  public function guardar($turno,$casino)
  {
      $nuevoTurno = new Turno;
      $nuevoTurno->dia_desde = $turno['desde'];
      $nuevoTurno->dia_hasta = $turno['hasta'];
      $nuevoTurno->entrada = $turno['entrada'];
      $nuevoTurno->salida = $turno['salida'];
      $nuevoTurno->nro_turno = $turno['nro'];
      $time = Carbon::createFromTimeString($turno['salida'], 'America/Argentina/Buenos_Aires');
      $nuevoTurno->hora_propuesta = $time->subHour();
      $nuevoTurno->casino()->associate($casino);
      $nuevoTurno->save();

  }

  public function buscarTurnos($nro_turno){
    $usuario = Auth::user();
    $casinos = array();
    foreach($usuario->casinos as $casino){
      $casinos[]=$casino->id_casino;
    }
    $turnos = Turno::where('nro_turno','=',$nro_turno)->whereIn('id_casino',$casinos)->get();
    $trr = array();
    foreach ($turnos as $t) {
      $trr[] = [
                'nro_turno' => $t->nro_turno.' '.$t->nombre_dia_desde.' a '.$t->nombre_dia_hasta.'-'.$t->casino->codigo,
                'id_turno' => $t->id_turno,
                ];
    }
    return ['turnos' => $trr];
  }

}
