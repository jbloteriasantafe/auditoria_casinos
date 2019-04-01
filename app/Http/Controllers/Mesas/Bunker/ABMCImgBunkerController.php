<?php

namespace App\Http\Controllers\Bunker;

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
use App\Http\Controllers\UsuarioController;

use App\Usuario;
use App\Casino;
use Carbon\Carbon;//America/Argentina/Buenos_Aires tz
use App\SecRecientes;

use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

use App\Mesas\Mesa;
use App\Mesas\Moneda;
use App\Mesas\ImagenesBunker;
use App\Mesas\DetalleImgBunker;
use App\Mesas\Cierre;
use App\Mesas\DetalleImportacionDiariaMesas;

//validacion de cierres
class ABMCImgBunkerController extends Controller
{
  private static $atributos = [
    'id_imagenes_bunker' => 'identificacion de la carga',
    'observaciones' => 'comentarios sobre lo cargado',
    'datoscd' => 'datos del cd',
    'datoscd.*.nombre_cd' => 'nombre del cd',
    'datoscd.*.duracion_cd' => 'duracion del cd',
    'detalles' => 'datos de las mesas',
    'detalles.*.id_detalle_img_bunker' => 'identificacion de la cosa',
    'detalles.*.drop_visto' => 'efectivo',
    'detalles.*.minutos_video' => 'Momento de la captura',
    'detalles.*.diferencias' => 'diferencias',
  ];

  /**
   * Create a new controller instance.
   *
   * @return void
   */
  public function __construct()
  {
      $this->middleware(['tiene_permiso:m_abmc_img_bunker']);
  }

  public function index(){
    return view ('SolicitudImagenes.seccionImagenes',['casinos'=> UsuarioController::getInstancia()->buscarUsuario(session('id_usuario'))['usuario']->casinos]);
  }

  public function altaImgsBunker(Request $request){
    $fecha = Carbon::now()->subMonths(1)->format("Y-m-d");
    $ff = explode('-',$fecha);
    $casinos = Casino::all();

    $sorteados = array();
    foreach($casinos as $casino){
      //dd($ff[0].'-'.$ff[1]);
      $noEsta = ImagenesBunker::where('mes_anio','=',$ff[0].'-'.$ff[1])
                                ->where('id_casino','=',$casino->id_casino)
                                ->get();

       if(count($casino->mesas) > 0 && count($noEsta) == 0){
        $bunker = new ImagenesBunker;
        $bunker->mes_anio = $ff[0].'-'.$ff[1];
        $bunker->casino()->associate($casino->id_casino);
        $bunker->save();
        $this->sortearFechasYMesas($casino,$bunker);
        $sorteados[] = ['bunker'=>$bunker,'casino'=>$bunker->casino->nombre];
      }
    }
    if(empty($sorteados)){
      return response()->json([ 'ERROR' => 'EL SORTEO YA FUE CREADO.'], 401);
    }
    return ['sorteo' => $sorteados];
  }

  private function sortearFechasYMesas($casino,$bunker){
    //obtener cantidad de mesas del casino->sacar porcentaje 15%
    $total_mesas = $casino->mesas()->count();

    $cantidad = ceil(($total_mesas * ('0.'.$casino->porcentaje_sorteo_mesas)));
    //elegir fechas
    $start = new Carbon('first day of last month');
    $end = new Carbon('last day of last month');
    $anio = Carbon::now()->subMonths(1)->format("Y");
    $mes = Carbon::now()->subMonths(1)->format("m");
    $mesas = '';
    $fechas = '';
    //dd($start);
    $fechas_collect = collect();
    for ($i=0; $i <3 ; $i++) {
      $random = rand($start->format('j'),$end->format('j'));
      if($fechas_collect->contains(['dia' => $random])){
        $random = rand($start->format('j'),$end->format('j'));
      }

      $cierres = Cierre::whereYear('fecha','=',$anio)
                          ->whereMonth('fecha','=',1)
                          ->whereDay('fecha','=',$random)
                          ->where('id_casino','=',$casino->id_casino)
                          ->inRandomOrder()
                          ->take($cantidad)
                          ->get();
      if(count($cierres) != 0){
        $fechas_collect->push($random);
      }

      foreach ($cierres as $cierre) {
        $detalle = new DetalleImgBunker;
        $detalle->mesa()->associate($cierre->id_mesa_de_panio);
        $detalle->imagen_bunker()->associate($bunker->id_imagenes_bunker);
        $detalle->fecha = $cierre->fecha;
        $detalle->codigo_mesa = $cierre->mesa->codigo_mesa;
        $detalle->save();
        $mesas = $mesas.$detalle->codigo_mesa.';';
      }
    }
    $fechas = $fechas_collect->sortBy('dia')->values();
    $fechas_string = '';
    foreach ($fechas as $f) {
      $fechas_string= $fechas_string.$f.';';
    }
    $bunker->fechas = $fechas_string;
    $bunker->mesas = $mesas;
    $bunker->estado()->associate(1);
    $bunker->save();
  }

  public function obtenerBunker($id){
    $bunker = ImagenesBunker::find($id);

    $cds = DB::table('detalle_img_bunker as img')
              ->select('img.nombre_cd','img.duracion_cd')
              ->where('img.id_imagenes_bunker','=',$id)
              ->distinct('img.nombre_cd')
              ->get();

    return ['bunker' => $bunker,
            'detalles' => $bunker->detalles()->orderBy('fecha','asc')->orderBy('codigo_mesa','asc')->get(),
            'cds' => $cds
            ];
  }



  public function cargar(Request $request){
    $validator=  Validator::make($request->all(),[
      'id_imagenes_bunker' => 'required|exists:imagenes_bunker,id_imagenes_bunker',
      'observaciones' => 'nullable|max:400',
      'datoscd' => 'required',
      'datoscd.*.nombre_cd' => 'required|max:50',
      'datoscd.*.duracion_cd' => 'required|date_format:"H:i"',
      'detalles' => 'required',
      'detalles.*.id_detalle_img_bunker' => 'required|exists:detalle_img_bunker,id_detalle_img_bunker',
      'detalles.*.drop_visto' => ['nullable','regex:/^\d\d?\d?\d?\d?\d?\d?\d?([,|.]?\d?\d?\d?)?$/'],
      'detalles.*.minutos_video' => 'nullable|date_format:"H:i"',
      'detalles.*.diferencias' => ['nullable','regex:/^\d\d?\d?\d?\d?\d?\d?\d?([,|.]?\d?\d?\d?)?$/'],

    ], array(), self::$atributos)->after(function($validator){
      //falta agregar validaciones de minutos cuando el drop es != coso
      if(!empty($validator->getData()['detalles'])){
        foreach ($validator->getData()['detalles'] as $detalle) {
          if(!empty($detalle['drop_visto']) && empty($detalle['minutos_video'])){
            $validator->errors()->add('drop_visto','Campo requerido.'
                                      );
          }
        }
      }
     })->validate();
    if(isset($validator)){
      if ($validator->fails()){
          return ['errors' => $validator->messages()->toJson()];
          }
     }

     $bunker = ImagenesBunker::find($request['id_imagenes_bunker']);
     $bunker->observaciones = $request->observaciones;
     $bunker->save();

     foreach ($request->detalles as $datos) {
       $detalle = DetalleImgBunker::find($datos['id_detalle_img_bunker']);
       foreach ($request->datoscd as $cd) {
         if($cd['nombre_cd'] == $datos['identificacion']){
           $detalle->duracion_cd = $cd['duracion_cd'];
           $detalle->nombre_cd = $cd['nombre_cd'];
         }
       }
       $detalle->drop_visto = $datos['drop_visto'];
       $detalle->minutos_captura = $datos['minutos_video'];
       $detalle->diferencias = $datos['diferencias'];
       $detalle->save();
     }
     if($bunker->detalles()->whereNull('drop_visto')->orWhere('drop_visto','=',0)->get()->count() != $bunker->detalles()->count()){
       $bunker->estado()->associate(2);
     }else{
       $bunker->estado()->associate(3);
     }
     $bunker->save();
     //rfg
     return response()->json(['exito' => 'Sorteos creados'], 200);
  }


  public function filtros(Request $request){

    $user = UsuarioController::getInstancia()->buscarUsuario(session('id_usuario'))['usuario'];
    $cas = array();

    $filtros = array();

    if(!empty($request->identificacion)){
      $filtros[]= ['dib.nombre_cd','=',$request->identificacion];
    }
    if(!empty($request->id_casino) && $request->id_casino != 0){
      $cas[]= $request->id_casino;
    }else{
      foreach ($user->casinos as $cass) {
        $cas[]=$cass->id_casino;
      }
    }
    if(!empty( $request->sort_by)){
      $sort_by = $request->sort_by;
    }else{
        $sort_by = ['columna' => 'img.created_at','orden'=>'desc'];
    }

    if(empty($request->mes)){
      $resultados = DB::table('imagenes_bunker as img')
                        ->select('img.*','casino.*')
                ->leftJoin('detalle_img_bunker as dib','dib.id_imagenes_bunker',
                            '=','img.id_imagenes_bunker')
                ->join('casino','casino.id_casino','=','img.id_casino')
                ->where($filtros)
                ->whereIn('img.id_casino',$cas)
                ->distinct('img.id_imagenes_bunker')
                //->whereNull('imagenes_bunker.deleted_at')
                ->when($sort_by,function($query) use ($sort_by){
                                return $query->orderBy($sort_by['columna'],$sort_by['orden']);
                            })
                ->paginate($request->page_size);
    }else{
      //$mes=explode("-",$request->mes);
      $resultados = DB::table('imagenes_bunker as img')
                        ->select('img.*','casino.*')
                        ->leftJoin('detalle_img_bunker as dib','dib.id_imagenes_bunker',
                                    '=','img.id_imagenes_bunker')
                        ->join('casino','casino.id_casino','=','img.id_casino')
                        ->where($filtros)
                        ->whereIn('img.id_casino',$cas)
                        ->where('img.mes_anio' , '=', $request->mes)
                        ->distinct('img.id_imagenes_bunker')
                        ->when($sort_by,function($query) use ($sort_by){
                                        return $query->orderBy($sort_by['columna'],$sort_by['orden']);
                                    })
                        ->paginate($request->page_size);
    }
    return ['datos' => $resultados];
  }

  public function consultarDiferencias($drop,$id_detalle){
    $detalle = DetalleImgBunker::find($id_detalle);
    if($detalle != null){

      $importacion = DetalleImportacionDiariaMesas::where('id_mesa_de_panio','=',$detalle->id_mesa_de_panio)
                          ->where('fecha','=',$detalle->fecha)
                          ->get()->first();
      if($importacion == null){
        return ['diferencia' => $drop];
      }else{
        $diferencia = abs($drop - $importacion->droop);
        return ['diferencia' => $diferencia];
      }


    }else{
      return  ['diferencia' => 'DATOS INCORRECTOS'];
    }
  }

}
