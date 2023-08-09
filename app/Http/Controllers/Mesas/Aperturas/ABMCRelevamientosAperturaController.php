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
  private static function CARPETA_APERTURAS($file = null){
    $path = public_path().'/Mesas/RelevamientosAperturas';
    if($file !== null)
      return "$path/$file";
    return $path;
  }
  /**
   * Create a new controller instance.
   *
   * @return void
   */
  public function __construct()
  {
    $this->middleware(['tiene_permiso:m_sortear_mesas']);
  }
  
  public function obtenerAperturasSorteadas(Request $request,int $id_casino = null,int $ver_backups = 0){
    $user = UsuarioController::getInstancia()->buscarUsuario(session('id_usuario'))['usuario'];
    $casinos = $user->casinos->map(function($c){
      return $c->id_casino;
    })->toArray();
    $hoy = Carbon::now()->format("Y-m-d");
    
    $mesas = DB::table('mesas_sorteadas as ms')
    ->select('ms.*','c.codigo as codigo_casino')
    ->where('ms.fecha_backup','=',$hoy)
    ->join('casino as c','c.id_casino','=','ms.id_casino')
    ->where(function($q) use ($id_casino){
      if(!is_null($id_casino))
        return $q->where('ms.id_casino','=',$id_casino);
      return $q;
    })
    ->where(function($q) use ($ver_backups){
      if(!$ver_backups)
        return $q->whereColumn('ms.fecha_backup','=',DB::raw('DATE(ms.created_at)'));
      return $q;
    })
    ->whereIn('c.id_casino',$casinos)
    ->orderBy('c.id_casino','asc')
    ->get();
        
    $ret = [];
    foreach($mesas as $ms){
      $es_backup     = (int)($ms->fecha_backup != explode(' ',$ms->created_at)[0]);
      $codigo_casino = $ms->codigo_casino;
      foreach(json_decode($ms->mesas) as $m){
        $m = array_map(function($m) use ($es_backup,$codigo_casino,$hoy) {
          $m->es_backup = $es_backup;
          $m->codigo_casino = $codigo_casino;
          $m->cargada = DB::table('apertura_mesa')
          ->whereNull('deleted_at')
          ->where('id_mesa_de_panio','=',$m->id_mesa_de_panio)
          ->where('fecha','=',$hoy)
          ->count() > 0;
          return $m;
        },$m);
        $ret = array_merge($ret,$m);
      }
    }

    $ret = array_map(function($m){
      return [
        'id_casino' => $m->id_casino,
        'codigo_casino' => $m->codigo_casino,
        'mesa' => "{$m->siglas}{$m->nro_mesa}",
        'id_mesa_de_panio' => $m->id_mesa_de_panio,
        'es_backup' => $m->es_backup,
        'cargada' => $m->cargada,
      ];
    },$ret);
        
    return $ret;
  }
  
  public function generarRelevamiento(Request $request,$id_casino){
    $user = UsuarioController::getInstancia()->buscarUsuario(session('id_usuario'))['usuario'];

    $validator = Validator::make(compact('id_casino'),[
      'id_casino' => 'required|exists:casino,id_casino,deleted_at,NULL',
    ], [
      'required' => 'El valor es requerido',
    ], self::$atributos)->after(function($validator) use ($user){
      if($validator->errors()->any()) return;
      $data = $validator->getData();
      if(!$user->usuarioTieneCasino($data['id_casino'])){
        return $validator->errors()->add('id_casino','No tiene los privilegios');
      }
    })->validate();
    
    //@TODO: manejar backups
    $fechas_sorteadas = $this->sortearMesasSiNoHay($request,$request->id_casino, false);    
    //@SPEED: si tuviera algun hash en mesas_sorteadas podria chequearse si es el mismo en vez de regenarlo
    $nombre_zip = $this->regenerarArchivo($request->id_casino,$fechas_sorteadas);

    return ['nombre_zip' => $nombre_zip];
  }
  
  public function sortearMesasSiNoHay(Request $request,$id_casino,$validar = true){
    if($validar){
      $user = UsuarioController::getInstancia()->buscarUsuario(session('id_usuario'))['usuario'];
      $validator = Validator::make(compact('id_casino'),[
        'id_casino' => 'required|exists:casino,id_casino,deleted_at,NULL',
      ], [
        'required' => 'El valor es requerido',
      ], self::$atributos)->after(function($validator) use ($user){
        if($validator->errors()->any()) return;
        $data = $validator->getData();
        if(!$user->usuarioTieneCasino($data['id_casino'])){
          return $validator->errors()->add('id_casino','No tiene los privilegios');
        }
      })->validate();
    }
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
    return DB::transaction(function() use ($id_casino){
      $hoy = Carbon::now()->format("Y-m-d");
      DB::table('mesas_sorteadas')->where('fecha_backup','>',$hoy)->delete();//Antes solo se hacia este delete

      $casinos_con_informes_fisca_creados_para_hoy = DB::table('informe_fiscalizadores')
      ->where('fecha','>=',$hoy)->select('id_casino')->get()->pluck('id_casino');

      DB::table('mesas_sorteadas')->where('fecha_backup','=',$hoy)
      ->where('created_at','<',$hoy)->whereNotIn('id_casino',$casinos_con_informes_fisca_creados_para_hoy)->delete();
      
      $sorteoController = new SorteoMesasController;
      $fechas_a_sortear = [];     
      $casino = Casino::find($id_casino); 
      if(count($casino->mesas) > 0){
        for ($i=0;$i<self::$cantidad_dias_backup;$i++) {
          $f = Carbon::now()->addDays($i)->format("Y-m-d");
          $fechas_a_sortear[] = $f;
        }
        foreach($fechas_a_sortear as $f){
          if(is_null($sorteoController->buscarBackUps($id_casino,$f))){
             $sorteoController->sortear($id_casino, $f);
          }
        }
      }
      return $fechas_a_sortear;
    });
  }

  public function regenerarArchivo($id_casino,$fechas_sorteadas){
    $casino = Casino::find($id_casino);
    $codigo_casino = $casino->codigo;    
    $inicio = $fechas_sorteadas[0];
    $fin    = $fechas_sorteadas[count($fechas_sorteadas)-1];
    $nombre_zip = "Planillas-Aperturas-$codigo_casino-$inicio-al-$fin.zip";
    
    if(File::exists(self::CARPETA_APERTURAS())){
      if(File::exists(self::CARPETA_APERTURAS($nombre_zip))){
        File::delete(self::CARPETA_APERTURAS($nombre_zip));
      }
    }else{
      File::makeDirectory(self::CARPETA_APERTURAS());
    }
    
    $files = [];
    foreach($fechas_sorteadas as $f){
      $file = self::CARPETA_APERTURAS("Relevamiento-Aperturas-$codigo_casino-$f.pdf");
      $rel = $this->crearRel($casino,$f);
      if($rel == null) continue;
      
      $view = View::make('Mesas.Planillas.PlanillaRelevamientoAperturaSorteadas_v3', compact('rel'));
      $dompdf = new Dompdf();
      $dompdf->set_paper('A4', 'portrait');
      $dompdf->loadHtml($view);
      $dompdf->render();
      $font = $dompdf->getFontMetrics()->get_font("helvetica", "regular");
      $dompdf->getCanvas()->page_text(20, 815, $codigo_casino."/".$rel->fecha, $font, 10, array(0,0,0));
      $dompdf->getCanvas()->page_text(515, 815, "Página {PAGE_NUM} de {PAGE_COUNT}", $font, 10, array(0,0,0));
      
      File::put($file,$dompdf->output());
      $files[] = $file;
    }
    
    Zipper::make(self::CARPETA_APERTURAS($nombre_zip))->add($files)->close();
    File::delete($files);
    return $nombre_zip;
  }

  public function descargarZip($nombre){
    $file    = self::CARPETA_APERTURAS($nombre);
    $headers = array('Content-Type' => 'application/octet-stream',);
    return response()->download($file,$nombre,$headers);
  }

  private function crearRel($cas,$fecha_backup){
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
      return $rel;
    }
    return null;
  }
}
