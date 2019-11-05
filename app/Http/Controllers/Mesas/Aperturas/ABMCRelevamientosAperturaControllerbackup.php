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
class ABMCRelevamientosAperturaContjhgjroller extends Controller
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
  */
  public function generarRelevamiento(){
    //return $this->creaRelevamientoZip();
    $fecha_hoy = Carbon::now()->format("Y-m-d");
    $user = UsuarioController::getInstancia()->buscarUsuario(session('id_usuario'))['usuario'];
    $cas = $user->casinos->first();
    $codigo_casino = $cas->codigo;

    $nombreZip = 'Planillas-Aperturas-'.$codigo_casino
              .'-'.$fecha_hoy.'-al-'.strftime("%Y-%m-%d", strtotime("$fecha_hoy +".(self::$cantidad_dias_backup-1)." day"))
              .'.zip';
    //dd(app_path() . "/" .$nombreZip);

    if(file_exists( public_path().'/Mesas/RelevamientosAperturas/'.$nombreZip)){
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
      //$usuario = UsuarioController::getInstancia()->buscarUsuario(session('id_usuario'))['usuario'];
      //$cas = $usuario->casinos->first();
      $arregloRutas = array();
      //creo planillas para hoy y los dias de backup
      foreach ($casinos as $cas){
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

        Zipper::make(public_path()."/Mesas/RelevamientosAperturas/".$nombreZip)->add($arregloRutas)->close();
        File::delete($arregloRutas);
      }
    //return ['url_zip' => 'sorteo-aperturas/descargarZip/'.$nombreZip];
  }


  public function descargarZip($nombre){

    $file = public_path().'/Mesas/RelevamientosAperturas/'. $nombre;
    $headers = array('Content-Type' => 'application/octet-stream',);

    return response()->download($file,$nombre,$headers);//->deleteFileAfterSend(true);

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
      $rel = new \stdClass();
      //mesas sorteadas
      //$sorteo = $sorteoController->buscarBackUps($cas->id_casino,$fecha_backup);
      $sorteadasController = new ABCMesasSorteadasController;
      try{
        $rta = $sorteadasController->obtenerSorteo($cas->id_casino,$fecha_backup);
        $sorteo = ['ruletasDados' => $rta->mesas['ruletasDados'],'cartas' => $rta->mesas['cartas']];
      }catch(Exception $e){
              dd([$e,$cas,$fecha_backup]);
        throw new \Exception("Sorteo no encontrado - llame a un ADMINISTRADOR", 1);
        //hola admin -> cuando salga este mensaje deberás ejecutar el comando RAM:sortear
      }

      $rel->sorteadas =  new \stdClass();
      $rel->sorteadas->ruletasDados = $sorteo['ruletasDados'];
      $rel->sorteadas->cartas = $sorteo['cartas'];


      $rmesas = Mesa::whereIn('id_casino',[$cas->id_casino])->with('juego')->get();
      $m_ordenadas = $rmesas->sortBy('codigo_sector');
      $lista_mesas = array();
      $sublista = array();
      $contador = 1;
      foreach ($m_ordenadas as $m) {
        if($contador == 35){ //30 = cant de mesas que entran de 1
          $sublista[] = ['codigo_mesa'=> $m->codigo_mesa,
                         'sector'=> $m->nombre_sector];

          $lista_mesas[] = $sublista;
          $sublista = array();
          $contador = 1;
        }else{
          $sublista[] = ['codigo_mesa'=> $m->codigo_mesa,
                         'sector'=> $m->nombre_sector];

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
      if($rel->cant_fichas > 15){
        $rel->paginas = [1,2]; //->cantidad de mesas que se deben relevar obligatoriamente (de a pares)
      }else{
        $rel->paginas = [1,2,3,4];
      }

      $view = View::make('Mesas.Planillas.PlanillaRelevamientoAperturaSorteadas_V2', compact('rel'));
      $dompdf = new Dompdf();
      $dompdf->set_paper('A4', 'portrait');
      $dompdf->loadHtml($view);
      $dompdf->render();

      $font = $dompdf->getFontMetrics()->get_font("helvetica", "regular");
      $dompdf->getCanvas()->page_text(20, 815, $cas->codigo."/".$rel->fecha, $font, 10, array(0,0,0));
      $dompdf->getCanvas()->page_text(515, 815, "Página {PAGE_NUM} de {PAGE_COUNT}", $font, 10, array(0,0,0));
      //dd($dompdf);
      return $dompdf;//->stream('sorteoAperturas.pdf', Array('Attachment'=>0));
    // }catch(Exeption $e){
    //   if($e instanceof \App\Exceptions\PlanillaException){
    //     throw $e;
    //   }else{
    //     throw new \App\Exceptions\PlanillaException('No se pudo generar la planilla para relevar aperturas de mesas.');
    //   }
    // }
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
      for ($i=0; $i < self::$cantidad_dias_backup; $i++) {
        $fecha_backup = Carbon::now()->addDays($i)->format("Y-m-d");
        $sorteadas = $sorteoController->sortear($cas->id_casino, $fecha_backup);
        $sthg[] = ['sorteo' => $sorteadas, 'fecha' => $fecha_backup];
      }
    }
    $this->creaRelevamientoZip();
    return $sthg;
  }

  // public function planillaRosario(){
  //   $informesSorteadas = new ABCMesasSorteadasController;
  //   $fecha_hoy = Carbon::now()->format("Y-m-d"); // fecha de hoy
  //   $cas = Casino::whereIn('id_casino',[3])->first();
  //   //$usuario = UsuarioController::getInstancia()->buscarUsuario(session('id_usuario'))['usuario'];
  //   //$cas = $usuario->casinos->first();
  //   $arregloRutas = array();
  //   //creo planillas para hoy y los dias de backup
  //     $codigo_casino = $cas->codigo;
  //     for ($i=0; $i < 1; $i++) {
  //       $fecha_backup = Carbon::now()->addDays($i)->format("Y-m-d");
  //       $dompdf = $this->crearPlanillaRos($cas, $fecha_backup);
  //       //return $dompdf->stream('sorteoAperturas.pdf', Array('Attachment'=>0));
  //       $output = $dompdf->output();
  //
  //       $ruta = public_path()."/Relevamiento-Aperturas-".$fecha_backup.".pdf";
  //       file_put_contents($ruta, $output);
  //       $nombre ="Relevamiento-Aperturas-".$fecha_backup.".pdf";
  //       $file = public_path().'/'. $nombre;
  //       $headers = array('Content-Type' => 'application/octet-stream',);
  //
  //       return response()->download($file,$nombre,$headers);//->deleteFileAfterSend(true);
  //
  //     }
  // }
  //
  // public function crearPlanillaRos($cas,$fecha_backup){
  //   //try{
  //     $sorteoController = new SorteoMesasController;
  //     $rel = new \stdClass();
  //     //mesas sorteadas
  //     //$sorteo = $sorteoController->buscarBackUps($cas->id_casino,$fecha_backup);
  //     $sorteadasController = new ABCMesasSorteadasController;
  //     try{
  //       $rta = $sorteadasController->obtenerSorteo($cas->id_casino,$fecha_backup);
  //       $sorteo = ['ruletasDados' => $rta->mesas['ruletasDados'],'cartas' => $rta->mesas['cartas']];
  //     }catch(Exception $e){
  //             dd([$e,$cas,$fecha_backup]);
  //       throw new \Exception("Sorteo no encontrado - llame a un ADMINISTRADOR", 1);
  //       //hola admin -> cuando salga este mensaje deberás ejecutar el comando RAM:sortear
  //     }
  //
  //     $rel->sorteadas =  new \stdClass();
  //     $rel->sorteadas->ruletasDados = $sorteo['ruletasDados'];
  //     $rel->sorteadas->cartas = $sorteo['cartas'];
  //
  //
  //     $rmesas = Mesa::whereIn('id_casino',[$cas->id_casino])->with('juego')->get();
  //     $m_ordenadas = $rmesas->sortBy('codigo_mesa');
  //     $lista_mesas = array();
  //     $sublista = array();
  //     $contador = 1;
  //     foreach ($m_ordenadas as $m) {
  //       if($contador == 35){ //30 = cant de mesas que entran de 1
  //         $sublista[] = ['codigo_mesa'=> $m->codigo_mesa];
  //
  //         $lista_mesas[] = $sublista;
  //         $sublista = array();
  //         $contador = 1;
  //       }else{
  //         $sublista[] = ['codigo_mesa'=> $m->codigo_mesa];
  //
  //         $contador++;
  //       }
  //     }
  //     if($contador != 35){
  //       $lista_mesas[] = $sublista;
  //     }
  //
  //     $rel->mesas = $lista_mesas;
  //
  //     $rel->fecha = \Carbon\Carbon::today();
  //     $año = substr($rel->fecha,0,4);
  //     $mes = substr($rel->fecha,5,2);
  //     $dia = substr($rel->fecha,8,2);
  //     $rel->fecha = $dia."-".$mes."-".$año;
  //     $rel->casino = $cas->nombre;
  //
  //     $rel->fichas = Ficha::select('valor_ficha')->distinct('valor_ficha')->orderBy('valor_ficha','DESC')->get();
  //     $rel->cant_fichas = $rel->fichas->count();
  //     if($rel->cant_fichas > 15){
  //       $rel->paginas = [1,2]; //->cantidad de mesas que se deben relevar obligatoriamente (de a pares)
  //     }else{
  //       $rel->paginas = [1,2,3,4];
  //     }
  //
  //     $view = View::make('Mesas.Planillas.rosario', compact('rel'));
  //     $dompdf = new Dompdf();
  //     $dompdf->set_paper('A4', 'portrait');
  //     $dompdf->loadHtml(utf8_decode($view));
  //     //dd('genero pero no renderizo');
  //     $dompdf->render();
  //
  //     $font = $dompdf->getFontMetrics()->get_font("helvetica", "regular");
  //     $dompdf->getCanvas()->page_text(20, 815, $cas->codigo."/".$rel->fecha, $font, 10, array(0,0,0));
  //     $dompdf->getCanvas()->page_text(515, 815, "Página {PAGE_NUM} de {PAGE_COUNT}", $font, 10, array(0,0,0));
  //     //dd($dompdf);
  //     return $dompdf;//->stream('sorteoAperturas.pdf', Array('Attachment'=>0));
  //   // }catch(Exeption $e){
  //   //   if($e instanceof \App\Exceptions\PlanillaException){
  //   //     throw $e;
  //   //   }else{
  //   //     throw new \App\Exceptions\PlanillaException('No se pudo generar la planilla para relevar aperturas de mesas.');
  //   //   }
  //   // }
  // }

}
