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
use App\Mesas\DetalleApertura;
use App\Mesas\EstadoCierre;
use App\Mesas\TipoCierre;
use App\Mesas\MesasSorteadas;

use Carbon\Carbon;
use Exception;

//alta BAJA y consulta de mesas sorteadas
class ABCMesasSorteadasController extends Controller
{
  /**
   * Create a new controller instance.
   *
   * @return void
   */
  public function __construct()
  {
      $this->middleware(['tiene_permiso:m_sortear_mesas']);
  }

  //se usa en SorteoMesasController
  public function almacenarSorteadas($ruletasDados,$cartas,$id_casino,$fecha_backup){
    $sorteadas = new MesasSorteadas;
    $sorteadas->mesas = ['ruletasDados' => $ruletasDados->toArray(),
                          'cartas' => $cartas->toArray(),
                        ];
    $sorteadas->casino()->associate($id_casino);
    $sorteadas->fecha_backup = $fecha_backup;
    $sorteadas->save();
  }


  /*
  * Verifica si hay mesas sorteadas para la fecha en el casino
  * Si, existen -> se elimina
  * deprecated-
  */
  public function chequearSorteadas($fecha,$id_casino){
    $mesas_sorteadas = MesasSorteadas::where([['fecha_backup','=',$fecha],
                                              ['id_casino','=',$id_casino]])
                                        ->get();
    foreach ($mesas_sorteadas as $m) {
      $m->delete();
    }
  }


  public function obtenerSorteo($id_casino,$fecha){

    $mesas_sorteadas = MesasSorteadas::where([['fecha_backup','=',$fecha],
                                              ['id_casino','=',$id_casino]])
                                        ->firstOrFail();
    return $mesas_sorteadas;
  }

  public function eliminarSiguientes(){
    try{
      DB::table('mesas_sorteadas')
                ->where('fecha_backup','>',Carbon::now()->format("Y-m-d"))
                ->delete();
    }catch(Exception $e){
      throw new \Exception("FALLO durante la eliminación de sorteos mesa de paño - llame a un ADMINISTRADOR", 1);
    }

  }
}
