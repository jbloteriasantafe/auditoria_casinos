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
  
  public function obtenerAperturasSorteadas(Request $request){
    $user = UsuarioController::getInstancia()->buscarUsuario(session('id_usuario'))['usuario'];
    $casinos = $user->casinos->map(function($c){
      return $c->id_casino;
    })->toArray();
    $hoy = Carbon::now()->format("Y-m-d");
    $fecha_backup = $request->fecha_backup ?? $hoy;
    
    $q = DB::table('mesas_sorteadas as ms')
    ->select('ms.*','c.codigo as codigo_casino')
    ->join('casino as c','c.id_casino','=','ms.id_casino')
    ->where(function($q) use ($request){
      if(isset($request->id_casino))
        return $q->where('ms.id_casino','=',$request->id_casino);
      return $q;
    })
    ->where('ms.fecha_backup','=',$fecha_backup)
    ->where('ms.fecha_backup','<=',$hoy)//No permitir buscar para adelante
    ->whereIn('c.id_casino',$casinos);
    
    $mesas_db = (clone $q)//Devuelve mesas NO backups de la fecha
    ->whereColumn('ms.fecha_backup','<=',DB::raw('DATE(ms.created_at)'))
    ->get();
        
    $hay_backup = (clone $q)//Devuelve solo si existe backup
    ->whereColumn('ms.fecha_backup','>',DB::raw('DATE(ms.created_at)'))
    ->count() > 0;
        
    $mesas = [];
    foreach($mesas_db as $ms){
      $codigo_casino = $ms->codigo_casino;
      foreach(json_decode($ms->mesas) as $m){
        $m = array_map(function($m) use ($codigo_casino,$fecha_backup) {
          $m->codigo_casino = $codigo_casino;
          $m->cargada = DB::table('apertura_mesa')
          ->whereNull('deleted_at')
          ->where('id_mesa_de_panio','=',$m->id_mesa_de_panio)
          ->where('fecha','=',$fecha_backup)
          ->count() > 0;
          return $m;
        },$m);
        $mesas = array_merge($mesas,$m);
      }
    }

    $mesas = array_map(function($m){
      return [
        'id_casino' => $m->id_casino,
        'codigo_casino' => $m->codigo_casino,
        'juego' => $m->siglas,
        'nro_mesa' => $m->nro_mesa,
        'mesa' => "{$m->siglas}{$m->nro_mesa}",
        'id_mesa_de_panio' => $m->id_mesa_de_panio,
        'cargada' => $m->cargada,
      ];
    },$mesas);
    
    usort($mesas,function($a,$b){
      if( $a['cargada'] && !$b['cargada']) return -1;
      if(!$a['cargada'] &&  $b['cargada']) return  1;
      $j = -strcmp($a['juego'],$b['juego']);
      if($j != 0) return $j;
      if($a['nro_mesa'] > $b['nro_mesa']) return -1;
      if($a['nro_mesa'] < $b['nro_mesa']) return  1;
      return 0;
    });
        
    return compact('mesas','hay_backup');
  }
  
  public function usarBackup(Request $request){
    $user = UsuarioController::getInstancia()->buscarUsuario(session('id_usuario'))['usuario'];
    $mesas = null;

    $validator = Validator::make($request->all(),[
      'id_casino' => 'required|exists:casino,id_casino,deleted_at,NULL',
      'fecha_backup' => 'required|date|before:'.Carbon::now()->addDays(1)->format("Y-m-d"),
    ], [
      'required' => 'El valor es requerido',
      'date' => 'El valor tiene que ser una fecha',
      'before' => 'El valor supera el limite',
    ], self::$atributos)->after(function($validator) use ($user,&$mesas){
      if($validator->errors()->any()) return;
      $data = $validator->getData();
      if(!$user->usuarioTieneCasino($data['id_casino'])){
        return $validator->errors()->add('id_casino','No tiene los privilegios');
      }
      
      $mesas = (new SorteoMesasController)->buscar(
        $data['id_casino'],
        $data['fecha_backup'],
        'BACKUP'
      );
      
      if(is_null($mesas)) return $validator->errors()->add('fecha_backup','No existe sorteo de backup para esa fecha');
    })->validate();
    
    $mesas->created_at = Carbon::now();
    $mesas->save();
    
    return 1;
  }
  
  public function generarRelevamiento(Request $request){
    $user = UsuarioController::getInstancia()->buscarUsuario(session('id_usuario'))['usuario'];

    $validator = Validator::make($request->all(),[
      'id_casino' => 'required|exists:casino,id_casino,deleted_at,NULL',
      'fecha_backup' => 'required|date|before:'.Carbon::now()->addDays(1)->format("Y-m-d"),
    ], [
      'required' => 'El valor es requerido',
    ], self::$atributos)->after(function($validator) use ($user){
      if($validator->errors()->any()) return;
      $data = $validator->getData();
      if(!$user->usuarioTieneCasino($data['id_casino'])){
        return $validator->errors()->add('id_casino','No tiene los privilegios');
      }
    })->validate();
    
    $fechas_sorteadas = $this->sortearMesasSiNoHay($request,$request->id_casino, $request->fecha_backup);    
    //@SPEED: si tuviera algun hash en mesas_sorteadas podria chequearse si es el mismo en vez de regenarlo
    $nombre_zip = $this->regenerarArchivo($request->id_casino,$fechas_sorteadas);

    return ['nombre_zip' => $nombre_zip];
  }
  
  public function sortearMesasSiNoHay(Request $request,$id_casino,$inicio = null){
    $hoy = Carbon::now()->format("Y-m-d");
    if(is_null($inicio)){
      $inicio = $hoy;
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
    
    return DB::transaction(function() use ($id_casino,$inicio,$hoy){
      $sorteoController = new SorteoMesasController;
      if($inicio != $hoy){//Si sortea una fecha que no sea hoy, simplemente retorna la fecha si tiene mesas sorteadas
        $tiene_mesas = !is_null($sorteoController->buscar($id_casino,$inicio,'REAL'));
        return $tiene_mesas? [$inicio] : [];
      }
      
      $fechas = [];
      $fechas_sorteadas = [];     
      $inicio_f = Carbon::createFromFormat('Y-m-d',$inicio);
      for($i=0;$i<self::$cantidad_dias_backup;$i++) {
        $f = (clone $inicio_f)->addDays($i)->format("Y-m-d");
        $fechas[] = $f;
        if(!is_null($sorteoController->buscar($id_casino,$f,$i==0? 'REAL' : 'BACKUP'))){
          $fechas_sorteadas[] = $f;
        }
      }
      
      //Si estan todas sorteadas quiere decir que llamo con el dia ya sorteado
      if(count($fechas) == count($fechas_sorteadas)){
        return $fechas_sorteadas;
      }
      
      //Si no, solo puede ser porque se paso de dia
      //en ese caso, se resortean todos los dias
      //Borro los que no tengan informe_fiscalizadores generado
      $informes_creados = DB::table('informe_fiscalizadores')
      ->where('id_casino','=',$id_casino)
      ->whereIn('fecha',$fechas)->get()->pluck('fecha')->toArray();

      //Regenero los sorteos solo que son backup, no tengan un informe y sean pasando el dia actual
      DB::table('mesas_sorteadas')
      ->where('id_casino','=',$id_casino)
      ->whereIn('fecha_backup',$fechas)
      ->whereColumn('fecha_backup','>',DB::raw('DATE(created_at)'))//es backup
      ->whereNotIn('fecha_backup',$informes_creados)//no tiene informe
      ->delete();
      
      foreach($fechas as $f){
        if(is_null($sorteoController->buscar($id_casino,$f,$i==0? 'REAL' : 'BACKUP'))){
          $sorteoController->sortear($id_casino, $f);
        }
      }
      
      return $fechas;
    });
  }

  public function regenerarArchivo($id_casino,$fechas_sorteadas){
    $casino = Casino::find($id_casino);
    $codigo_casino = $casino->codigo;  
    if(count($fechas_sorteadas) == 0) throw new Exception('No hay fechas sorteadas');  
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
    return response()->download($file,$nombre,$headers)->deleteFileAfterSend(true);
  }

  private function crearRel($cas,$fecha_backup){
    $sorteo = (new SorteoMesasController)->buscar($cas->id_casino,$fecha_backup,'CUALQUIERA');
    if(is_null($sorteo)) return null;
    
    $rel = new \stdClass();
    $rel->sorteadas =  new \stdClass();
    $rel->sorteadas->ruletas = $sorteo->mesas['ruletas'];
    $rel->sorteadas->cartasDados = $sorteo->mesas['cartasDados'];

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
}
