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
use Zipper;
use File;

use App\Mesas\ComandoEnEspera;
use App\Mesas\FichaTieneCasino;
use App\Http\Controllers\Mesas\Mesas\SorteoMesasController;
use App\Http\Controllers\Mesas\InformesMesas\ABCMesasSorteadasController;


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
  /*
  * Esta funcion inicia el proceso de generacion de planillas :D
  ///creo que no se va a usar mas!
  */
  public function generarRelevamiento(){
    $fecha_hoy = Carbon::now()->format("Y-m-d");
    $user = UsuarioController::getInstancia()->buscarUsuario(session('id_usuario'))['usuario'];
    $cas = $user->casinos->first();
    $codigo_casino = $cas->codigo;

    $nombreZip = 'Planillas-Aperturas-'.$codigo_casino
              .'-'.$fecha_hoy.'-al-'.strftime("%Y-%m-%d", strtotime("$fecha_hoy +".(self::$cantidad_dias_backup-1)." day"))
              .'.zip';
     //dd(  public_path().'/Mesas/'.$nombreZip);
    if(file_exists( public_path().'/Mesas/RelevamientosAperturas/'.$nombreZip)){ //C:\xampp\htdocs\agosto\prueba2\blog\
    //if(file_exists( public_path().'\\Mesas\\'.$nombreZip)){ //C:\xampp\htdocs\agosto\prueba2\blog\
      return ['url_zip' => 'sorteo-aperturas/descargarZip/'.$nombreZip];
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

  public function creaRelevamientoZip(){
    $permissions = intval( config('permissions.directory'), 8 );
    if(file_exists( public_path().'/Mesas/RelevamientosAperturas')){
      File::deleteDirectory( public_path().'/Mesas/RelevamientosAperturas');
      File::makeDirectory( public_path().'/Mesas/RelevamientosAperturas');
    }else{
      File::makeDirectory( public_path().'/Mesas/RelevamientosAperturas');
    }

      $informesSorteadas = new ABCMesasSorteadasController;
      $fecha_hoy = Carbon::now()->format("Y-m-d"); // fecha de hoy
      $casinos = Casino::all();

      //el sorteo ya no se hace mas desde aca, asi que no hay que borrarlos!
      //$informesSorteadas->chequearSorteadas($fecha_hoy,$cas->id_casino);
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

            Zipper::make(public_path().'/Mesas/RelevamientosAperturas/'.$nombreZip)->add($arregloRutas)->close();
            File::delete($arregloRutas);
        }
      }
      //return ['url_zip' => 'sorteo-aperturas/descargarZip/'.$nombreZip];
  }

  /*
  * Se utiliza desde \console\Commands\SortearMesas
  */
  public function sortearMesasCommand(){
    $sorteoController = new SorteoMesasController;
    $sorteadasController = new ABCMesasSorteadasController;

    $sorteadasController->eliminarSiguientes();
    $sthg = array();
    $casinos = Casino::all();
    foreach ($casinos as $cas) {
      if(count($cas->mesas) > 0){
        for ($i=0; $i < self::$cantidad_dias_backup; $i++) {
          $fecha_backup = Carbon::now()->addDays($i)->format("Y-m-d");
          $sorteadas = $sorteoController->sortear($cas->id_casino, $fecha_backup);
        //  $sthg[] = ['sorteo' => $sorteadas, 'fecha' => $fecha_backup];
        }
      }
    }
    return $this->creaRelevamientoZip();
  //  return $sthg;
  }

  public function descargarZip($nombre){
    //dd($nombre);
    $file = public_path().'/Mesas/RelevamientosAperturas/'. $nombre;
    //$file = public_path().'\\Mesas\\'. $nombre;
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
    //try{

        $sorteoController = new SorteoMesasController;
        $sorteo = $sorteoController->buscarBackUps($cas->id_casino,$fecha_backup);
    // }catch(Exeption $e){

    //    throw $e;
    // }

    if($sorteo != null){
      //try{
        $rel = new \stdClass();
        //mesas sorteadas



        $rel->sorteadas =  new \stdClass();
        $rel->sorteadas->ruletas = $sorteo['ruletas'];
        $rel->sorteadas->cartasDados = $sorteo['cartasDados'];


        $rmesas = Mesa::whereIn('id_casino',[$cas->id_casino])->with('juego')->get();
        $m_ordenadas = $rmesas->sortBy('codigo_sector');
        $lista_mesas = array();
        $sublista = array();
        $contador = 1;
        //->forPage()
        foreach ($m_ordenadas as $m) {
          if($contador == 35){ //30 = cant de mesas que entran de 1
            $sublista[] = ['codigo_mesa'=> $m->codigo_mesa,
                           'sector'=> $m->nombre_sector
                          ];

            $lista_mesas[] = $sublista;
            $sublista = array();
            $contador = 1;
          }else{
            $sublista[] = ['codigo_mesa'=> $m->codigo_mesa,
                           'sector'=> $m->nombre_sector
                          ];

            $contador++;
          }
        }
        if($contador != 35){
          $lista_mesas[] = $sublista;
        }

        $rel->mesas = $lista_mesas;


        $rel->fecha = \Carbon\Carbon::today();
        $año = substr($rel->fecha,0,4);
        $mes = substr($rel->fecha,5,2);
        $dia = substr($rel->fecha,8,2);
        $rel->fecha = $dia."-".$mes."-".$año;
        $rel->casino = $cas->nombre;
        $rel->id_casino = $cas->id_casino;

        $fichas = DB::table('ficha')
                      ->select('ficha.valor_ficha')
                      ->join('ficha_tiene_casino as fc','fc.id_ficha','=','ficha.id_ficha')
                      ->where('fc.id_casino','=',$cas->id_casino)
                      ->whereNull('fc.deleted_at')
                      ->whereNull('ficha.deleted_at')
                      ->distinct('ficha.valor_ficha')
                      ->orderBy('valor_ficha','desc')
                      ->get();
        $rel->fichas = $fichas;
        // $fichas = FichaTieneCasino::where('id_casino',$cas->id_casino)
        //                                   ->get()
        //                                   ->unique('valor_ficha')
        //                                   ->sortByDesc('valor_ficha');
        // $rel->fichas = $fichas->map(function ($fichas ) {
        //     return $fichas->only(['valor_ficha']);
        //   });
        $rel->cant_fichas = $rel->fichas->count();
        //dd($rel->cant_fichas);
        if($rel->cant_fichas > 15){
          $rel->paginas = [1,2]; //->cantidad de mesas que se deben relevar obligatoriamente (de a pares)
        }else{
          $rel->paginas = [1,2,3,4];
        }
        //if(!$rel->fichas)
        // dd($rel);

        $view = View::make('Mesas.Planillas.PlanillaRelevamientoAperturaSorteadas_v3', compact('rel'));
      //  dd($view);
        $dompdf = new Dompdf();
        $dompdf->set_paper('A4', 'portrait');
        $dompdf->loadHtml($view);
        $dompdf->render();
        $font = $dompdf->getFontMetrics()->get_font("helvetica", "regular");
        $dompdf->getCanvas()->page_text(20, 815, $cas->codigo."/".$rel->fecha, $font, 10, array(0,0,0));
        $dompdf->getCanvas()->page_text(515, 815, "Página {PAGE_NUM} de {PAGE_COUNT}", $font, 10, array(0,0,0));
        return $dompdf;//->stream('sorteoAperturas.pdf', Array('Attachment'=>0));
      // }catch(Exeption $e){
      //   if($e instanceof \App\Exceptions\PlanillaException){
      //     throw $e;
      //   }else{
      //     throw $e;
      //   }
      // }
    }
  }




}
