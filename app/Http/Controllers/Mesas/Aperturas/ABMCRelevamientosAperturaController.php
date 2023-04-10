<?php

namespace App\Http\Controllers\Mesas\Aperturas;

use Auth;
use Session;
use Illuminate\Http\Request;
use Response;
use App\Http\Controllers\Controller;
use Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Hash;
use DateTime;
use Dompdf\Dompdf;

use PDF;
use View;
use App\Usuario;
use App\Casino;
use App\Relevamiento;
use App\SecRecientes;

use App\Mesas\Mesa;
use App\Mesas\JuegoMesa;
use App\Mesas\SectorMesas;
use App\Mesas\TipoMesa;
use App\Mesas\Cierre;
use App\Mesas\DetalleCierre;
use App\Mesas\EstadoCierre;
use App\Mesas\TipoCierre;
use App\Mesas\Ficha;
use App\Mesas\MesasSorteadas;
use App\Http\Controllers\UsuarioController;
use Carbon\Carbon;
use Exception;
use Madzipper;
use File;

use App\Mesas\ComandoEnEspera;
use App\Mesas\FichaTieneCasino;
use App\Http\Controllers\Mesas\Mesas\SorteoMesasController;


//busqueda y consulta de cierres
class ABMCRelevamientosAperturaController extends Controller
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


  private static $cantidad_dias_backup = 5;

  /**
   * Create a new controller instance.
   *
   * @return void
   */
  public function __construct()
  {
    $this->middleware(['tiene_permiso:m_sortear_mesas']);
  }

  public function generarRelevamiento(){
    $fecha_hoy = Carbon::now()->format("Y-m-d");
    $user = UsuarioController::getInstancia()->buscarUsuario(session('id_usuario'))['usuario'];
    $cas = $user->casinos->first();
    $codigo_casino = $cas->codigo;

    $nombreZip = 'Planillas-Aperturas-'.$codigo_casino
              .'-'.$fecha_hoy.'-al-'.strftime("%Y-%m-%d", strtotime("$fecha_hoy +".(self::$cantidad_dias_backup-1)." day"))
              .'.zip';
    if(file_exists( public_path().'/Mesas/RelevamientosAperturas/'.$nombreZip)){
      return ['nombre_zip' => $nombreZip];
    }else{
      $enEspera = DB::table('comando_a_ejecutar')
          ->where([['fecha_a_ejecutar','>',Carbon::now()->format('Y:m:d H:i:s')],
                  ['nombre_comando','=','RAM:sortear']
                  ])
          ->get()->count();
      if($enEspera == 0){
        $agrega_comando = new ComandoEnEspera;
        $agrega_comando->nombre_comando = 'RAM:sortear';
        $agrega_comando->fecha_a_ejecutar = Carbon::now()->addMinutes(30)->format('Y:m:d H:i:s');
        $agrega_comando->save();
      }

      return response()->json(['apertura' => 'Por favor reintente en 15 minutos...'], 404);
    }
  }

  private function creaRelevamientoZip(){
    $permissions = intval( config('permissions.directory'), 8 );
    if(file_exists( public_path().'/Mesas/RelevamientosAperturas')){
      File::deleteDirectory( public_path().'/Mesas/RelevamientosAperturas');
      File::makeDirectory( public_path().'/Mesas/RelevamientosAperturas');
    }else{
      File::makeDirectory( public_path().'/Mesas/RelevamientosAperturas');
    }

    $fecha_hoy = Carbon::now()->format("Y-m-d"); // fecha de hoy
    $casinos = Casino::all();
    $arregloRutas = array();
    //creo planillas para hoy y los dias de backup

    foreach ($casinos as $cas){
      if(count($cas->mesas) > 0){
        $codigo_casino = $cas->codigo;
        for ($i=0; $i < self::$cantidad_dias_backup; $i++) {
          $fecha_backup = Carbon::now()->addDays($i)->format("Y-m-d");
          $dompdf = $this->crearPlanilla($cas, $fecha_backup);

          $output = $dompdf->output();
          $ruta = public_path()."/Mesas/RelevamientosAperturas/Relevamiento-Aperturas-".$fecha_backup.".pdf";
          file_put_contents($ruta, $output);
          $arregloRutas[] = $ruta;
        }

        $nombreZip = 'Planillas-Aperturas-'.$codigo_casino
                  .'-'.$fecha_hoy.'-al-'.strftime("%Y-%m-%d", strtotime("$fecha_hoy +".(self::$cantidad_dias_backup-1)." day"))
                  .'.zip';

        Madzipper::make(public_path().'/Mesas/RelevamientosAperturas/'.$nombreZip)->add($arregloRutas)->close();
        File::delete($arregloRutas);
      }
    }
  }

  /*
  * Se utiliza desde \console\Commands\SortearMesas
  */
  public function sortearMesasCommand(){
    // Borra todas las sorteadas exceptuando las de hoy. Esto es asi para evitar que el resorteo 
    // en el mismo dia por ej en el turno 2 borre las mesas_sorteadas del turno anterior.
    // Si se hiciera, romperia la dependencia necesaria para generar el InformeFiscalizador de mesas,
    // ver calcularApRelevadas (creo que no se se usa en la practica pero por las dudas no rompo compatibilidad hacia atras)
    //
    // Genera un problema al tratar de implementar aperturas a pedido:
    // Si hoy se sortean $hoy+4 dias para adelante. Cuando pase al otro dia, se reusaran la mesas_sorteadas de ayer
    // porque el delete() no lo agarraba. Esto hace que las aperturas a pedido tengan efecto a diferencia de 2 dias
    //
    // Para evitar esto cuando se buscan mesas_sorteadas:
    // - Si fecha_backup > $hoy => BORRAR
    // - Si fecha_backup == $hoy && created_at  < $hoy => BORRAR
    // - Si fecha_backup == $hoy && created_at == $hoy => DEJAR
    // Esto tiene un problema, si se cae el sistema un par de dias y vuelve entre turnos el segundo turno va a eliminar las mesas_sorteadas
    // con las que se usaron para relevar el primer turno. Lo optimo seria que el desarrollador re-sortee a primera hora para evitar esto
    // Hago un @HACK, y es que ademas verificar que no tenga InformeFiscalizadores
    // Esto NO agarra todos los casos, ya que estos son solo generados cuando se valida una apertura. Osea solo agarra si se cae varios dias,
    // el sistema vuelve entre turnos y cargaron&validaron una apertura en el primer turno antes del segundo sorteo
    // Octavio 06-12-2021
    try{
      $hoy = Carbon::now()->format("Y-m-d");
      DB::table('mesas_sorteadas')->where('fecha_backup','>',$hoy)->delete();//Antes solo se hacia este delete

      $casinos_con_informes_fisca_creados_para_hoy = DB::table('informe_fiscalizadores')
      ->where('fecha','>=',$hoy)->select('id_casino')->get()->pluck('id_casino');

      DB::table('mesas_sorteadas')->where('fecha_backup','=',$hoy)
      ->where('created_at','<',$hoy)->whereNotIn('id_casino',$casinos_con_informes_fisca_creados_para_hoy)->delete();
    }catch(Exception $e){
      throw new \Exception("FALLO durante la eliminación de sorteos mesa de paño - llame a un ADMINISTRADOR", 1);
    }
    $sorteoController = new SorteoMesasController;
    $casinos = Casino::all();
    foreach ($casinos as $cas) {
      if(count($cas->mesas) > 0){
        for ($i=0; $i < self::$cantidad_dias_backup; $i++) {
          $fecha_backup = Carbon::now()->addDays($i)->format("Y-m-d");
          $sorteadas = $sorteoController->sortear($cas->id_casino, $fecha_backup);
        }
      }
    }
    return $this->creaRelevamientoZip();
  }

  public function descargarZip($nombre){
    $file = public_path().'/Mesas/RelevamientosAperturas/'. $nombre;
    $headers = array('Content-Type' => 'application/octet-stream',);
    return response()->download($file,$nombre,$headers);
  }

  /*
  *
  * Genera la planilla, llama a la funcion de sortear que está en
  * Controllers\Mesas\SorteoMesasController;
  *
  */
  public function crearPlanilla($cas,$fecha_backup){
    $sorteoController = new SorteoMesasController;
    $sorteo = $sorteoController->buscarBackUps($cas->id_casino,$fecha_backup);

    if($sorteo != null){
        $rel = new \stdClass();
        $rel->sorteadas =  new \stdClass();
        $rel->sorteadas->ruletas = $sorteo['ruletas'];
        $rel->sorteadas->cartasDados = $sorteo['cartasDados'];

        $rmesas = Mesa::whereIn('id_casino',[$cas->id_casino])->with('juego')->get();
        $m_ordenadas = $rmesas->sortBy('codigo_sector')->map(function($m){
          return ['codigo_mesa'=> $m->codigo_mesa,'sector'=> $m->nombre_sector];
        })->toArray();

        $rel->mesas = array_chunk($m_ordenadas,33);
        $rel->fecha = \Carbon\Carbon::today();
        $año = substr($rel->fecha,0,4);
        $mes = substr($rel->fecha,5,2);
        $dia = substr($rel->fecha,8,2);
        $rel->fecha = $dia."-".$mes."-".$año;
        $rel->casino = $cas->nombre;
        $rel->id_casino = $cas->id_casino;

        $fichas = DB::table('ficha')->select('ficha.valor_ficha')
        ->join('ficha_tiene_casino as fc','fc.id_ficha','=','ficha.id_ficha')
        ->where('fc.id_casino','=',$cas->id_casino)
        ->whereNull('fc.deleted_at')->whereNull('ficha.deleted_at')
        ->distinct('ficha.valor_ficha')
        ->orderBy('valor_ficha','desc')
        ->get()->pluck('valor_ficha')->toArray();

        $rel->fichas = array_chunk($fichas,10);
        $rel->paginas = 4;//@HACK: porque estaba harcodeado??? 

        $view = View::make('Mesas.Planillas.PlanillaRelevamientoAperturaSorteadas_v3', compact('rel'));

        $dompdf = new Dompdf();
        $dompdf->set_paper('A4', 'portrait');
        $dompdf->set_option('chroot',public_path());
        $dompdf->loadHtml($view);
        $dompdf->render();
        $font = $dompdf->getFontMetrics()->get_font("helvetica", "regular");
        $dompdf->getCanvas()->page_text(20, 815, $cas->codigo."/".$rel->fecha, $font, 10, array(0,0,0));
        $dompdf->getCanvas()->page_text(515, 815, "Página {PAGE_NUM} de {PAGE_COUNT}", $font, 10, array(0,0,0));
        return $dompdf;
    }
  }
}
