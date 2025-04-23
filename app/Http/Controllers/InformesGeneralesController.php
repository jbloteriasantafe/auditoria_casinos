<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\CacheController;
use App\Http\Controllers\CanonController;
use App\Casino;
require_once(app_path('BC_extendido.php'));

class InformesGeneralesController extends Controller
{  
  private static $instance;
  public static function getInstancia(){
    self::$instance = self::$instance ?? (new self()); 
    return self::$instance;
  }
  
  public function beneficios(){
    $periodo = request()->periodo ?? [];
    sort($periodo);
    $periodo[0] = $periodo[0] ?? '1970-01';
    $periodo[1] = $periodo[1] ?? date('Y-m');
    
    $casinos = Casino::select('id_casino','nombre')->whereNull('deleted_at')->get();
    $actividades = collect(['Maquinas','Bingo','Mesas']);
    $CC = CanonController::getInstancia();
    $data = collect([]);
    
    $periodo[0] = explode('-',$periodo[0]);
    $periodo[1] = explode('-',$periodo[1]);
    $desde_año = intval($periodo[0][0]);
    $hasta_año = intval($periodo[1][0]);
    $desde_mes = intval($periodo[0][1]);
    $hasta_mes = intval($periodo[1][1]);
    
    for($año=$desde_año;$año<=$hasta_año;$año++){
      $mes_inicio = $año==$desde_año? $desde_mes : 1;
      $mes_fin    = $año==$hasta_año? $hasta_mes : 12;
      for($mes=$mes_inicio;$mes<=$mes_fin;$mes++){
        $año_mes = str_pad($año,4,'0',STR_PAD_LEFT).'-'.str_pad($mes,2,'0',STR_PAD_LEFT);
        foreach($casinos as $c) foreach($actividades as $a){
          $cantidad = $CC->bruto($a,$año_mes.'-01',$c->id_casino);
          if($cantidad !== null){
            $data->push((object)[
              'Casino' => $c->nombre,
              'Periodo' => $año_mes,
              'Actividad' => $a,
              'cantidad' => $cantidad
            ]);
          }
        }
      }
    }
    return $data;
  }
  
  private function similarity($s1,$s2){
    $s1 = preg_replace('/[^A-Za-z0-9\s]/', '', $s1);//Saco caracteres especiales
    $s2 = preg_replace('/[^A-Za-z0-9\s]/', '', $s2);
    $s1 = preg_replace('/(^|\s)(de|del|el|la|lo|los|las|en)($|\s)/', '', $s1);//Saco conectores
    $s2 = preg_replace('/(^|\s)(de|del|el|la|lo|los|las|en)($|\s)/', '', $s2);
    $s1 = preg_replace('/\s/', ' ', $s1);//Simplifico a espacios simples
    $s2 = preg_replace('/\s/', ' ', $s2);
    
    $sMAX = max(strlen($s1),strlen($s2));
    if($sMAX == 0) return 1.0;
    $porcentaje_escrito = ($sMAX - levenshtein($s1,$s2))/$sMAX;
    
    $m1 = metaphone($s1);
    $m2 = metaphone($s2);
    $mMAX = max(strlen($m1),strlen($m1));
    if($mMAX == 0) return 1.0;
    $porcentaje_pronunciado = ($mMAX - levenshtein($m1,$m2))/$mMAX;
    
    return 0.75*$porcentaje_escrito+0.25*$porcentaje_pronunciado;
  }
  
  private $STR_NO_ASIGNABLE = 'NO ASIGNABLE / EXTERIOR';
  public function autoexcluidos(){
    $periodo = request()->periodo ?? [];
    sort($periodo);
    $periodo[0] = $periodo[0] ?? '1970-01';
    $periodo[1] = $periodo[1] ?? date('Y-m');
    $periodo[0] = explode('-',$periodo[0]);
    $periodo[1] = explode('-',$periodo[1]);
    
    $lista_conversiones_provs = $lista_conversiones_deps = null;{
      $leer_archivo_conversion = function($filename){
        $ret = [];
        $fhandle = fopen(storage_path('app/'.$filename),'r');
        try{
          $header = true;
          while(($datos = fgetcsv($fhandle,'',',')) !== FALSE){
            if($header){
              $header = false;
              continue;
            }
            $ret[strtoupper(trim($datos[0]))] = strtoupper(trim($datos[1]));
          }
        }
        catch(\Exception $e){
          fclose($fhandle);
          throw $e;
        }
        fclose($fhandle);
        return $ret;
      };
      
      $lista_conversiones_provs = [
        [$leer_archivo_conversion('provincia_a_provincia.csv'),0.5]//Lista y porcentaje minimo de coincidiencia
      ];
      
      $lista_conversiones_deps = [
        [$leer_archivo_conversion('localidad_a_departamento.csv'),0.5],
        [$leer_archivo_conversion('distrito_a_departamento.csv'),0.5],
        [$leer_archivo_conversion('departamento_a_departamento.csv'),0.5],
        [$leer_archivo_conversion('miscelaneos_a_departamento.csv'),0.7],
        [$leer_archivo_conversion('codigopostal_a_departamento.csv'),0.9]
      ];
    }
       
    $f_agrupar = function($string_a_convertir,$lista_conversiones){
      $lista_s = [];
      foreach($lista_conversiones as $lista_y_porcentaje){
        $max_s = [-1,$this->STR_NO_ASIGNABLE];
        foreach($lista_y_porcentaje[0] as $from => $to){
          $s = $this->similarity($string_a_convertir,$from);
          if($s >= $lista_y_porcentaje[1] && $s > $max_s[0]){
            $max_s[0] = $s;
            $max_s[1] = $to;
          }
        }
        $lista_s[] = $max_s;
      }
      
      //Me quedo con la maxima afinidad
      return array_reduce($lista_s,function($max,$item){
        return ($item[0] > $max[0])? $item : $max;
      },[-2,$this->STR_NO_ASIGNABLE])[1];
    };
    
    $totalizar = function($item){
      return $item->reduce(function($carry,$i){
        return $carry+$i->cantidad;
      },0);
    };
    
    $presentar_llave = function($item,$k){
      return [ucwords(strtolower($k)) => $item];
    };
        
    $ret = [];
    $q = DB::table('ae_datos as ae')
    ->selectRaw('
      DATE_FORMAT(aee.fecha_ae,"%Y-%m") as Periodo,
      IF(aee.fecha_revocacion_ae IS NULL,aene.descripcion,CONCAT(aene.descripcion," (Finalizado)")) as Estado,
      TRIM(UPPER(ae.nombre_provincia)) as Provincia,
      TRIM(UPPER(ae.nombre_localidad)) as Localidad,
      COUNT(ae.nro_dni) as cantidad
    ')
    ->join('ae_estado as aee','aee.id_autoexcluido','=','ae.id_autoexcluido')
    ->join('ae_nombre_estado as aene','aene.id_nombre_estado','=','aee.id_nombre_estado')
    ->whereNull('ae.deleted_at')
    ->where(function($q) use ($periodo){
      return $q->whereYear('aee.fecha_ae','>',$periodo[0][0])
      ->orWhere(function($q2) use ($periodo){
        return $q2->whereYear('aee.fecha_ae','=',$periodo[0][0])
        ->whereMonth('aee.fecha_ae','>=',$periodo[0][1]);
      });
    })
    ->where(function($q) use ($periodo){
      return $q->whereYear('aee.fecha_ae','<',$periodo[1][0])
      ->orWhere(function($q2) use ($periodo){
        return $q2->whereYear('aee.fecha_ae','=',$periodo[1][0])
        ->whereMonth('aee.fecha_ae','<=',$periodo[0][1]);
      });
    })
    ->groupBy(DB::raw('
      DATE_FORMAT(aee.fecha_ae,"%Y-%m"),
      TRIM(UPPER(ae.nombre_provincia)),
      TRIM(UPPER(ae.nombre_localidad)),
      IF(aee.fecha_revocacion_ae IS NULL,aene.descripcion,CONCAT(aene.descripcion," (Finalizado)"))
    '));
    
    $aes = collect([]);
    foreach(['id_plataforma' => \App\Plataforma::all(),'id_casino' => \App\Casino::all()] as $k => $plats_casinos){
      foreach($plats_casinos as $plat_cas){
        $BD_PLAT_CAS = (clone $q)->where('aee.'.$k,'=',$plat_cas->{$k})
        ->get()
        ->map(function($i) use ($plat_cas,$f_agrupar,$lista_conversiones_provs,$lista_conversiones_deps){
          $i->Casino = $plat_cas->nombre;
          $i->Provincia = $f_agrupar($i->Provincia,$lista_conversiones_provs);
          if($i->Provincia == 'SANTA FE'){
            $i->Departamento = $f_agrupar($i->Localidad,$lista_conversiones_deps);
          }
          else{
            $i->Departamento = 'OTRA PROVINCIA';
          }
          unset($i->Localidad);
          return $i;
        });
        
        $aes = $aes->merge($BD_PLAT_CAS);
      }
    }
    
    //Sumo los porque quedan repetidos (por provincia y/o departamento) porque la conversion es posterior al groupBy de DB
    $aes = $aes->groupBy(function($i){
      return json_encode([$i->Casino,$i->Periodo,$i->Estado,$i->Provincia,$i->Departamento]);//@HACK
    })
    ->map(function($group,$gk){
      $gdata = json_decode($gk,true);//@HACK
      return (object)[
        'Casino' => $gdata[0],
        'Periodo' => $gdata[1],
        'Estado' => $gdata[2],
        'Provincia' => $gdata[3],
        'Departamento' => $gdata[4],
        'cantidad' => $group->reduce(function($carry,$item){
          return bcadd($carry,$item->cantidad,0);
        },'0'),
      ];
    })
    ->flatten();
    
    return $aes;
  }
  
  public function pdevs(){
    $periodo = request()->periodo ?? [];
    sort($periodo);
    $periodo[0] = $periodo[0] ?? '1970-01';
    $periodo[1] = $periodo[1] ?? date('Y-m');
    $periodo[0] = explode('-',$periodo[0]);
    $periodo[1] = explode('-',$periodo[1]);
    $desde_año = intval($periodo[0][0]);
    $hasta_año = intval($periodo[1][0]);
    $desde_mes = intval($periodo[0][1]);
    $hasta_mes = intval($periodo[1][1]);
    $P = 'producido';//'producido_test_pdevs';
    $data = DB::table($P.' as p')
    ->selectRaw('
      c.nombre as Casino,
      DATE_FORMAT(p.fecha,"%Y-%m") as Periodo,
      p.fecha as Fecha,
      tm.descripcion as Moneda,
      p.apuesta as Apuesta,
      p.premio as Premio,
      IF(p.id_tipo_moneda = 1,1,cot.valor)*p.apuesta as ApuestaARS,
      IF(p.id_tipo_moneda = 1,1,cot.valor)*p.premio as PremioARS,
      IF(p.apuesta <> 0,p.premio/p.apuesta,NULL) as Pdev')
    ->join('casino as c','c.id_casino','=','p.id_casino')
    ->join('tipo_moneda as tm','tm.id_tipo_moneda','=','p.id_tipo_moneda')
    ->leftJoin('cotizacion as cot','cot.fecha','=','p.fecha')
    ->where(function($q) use ($desde_año,$desde_mes){
      return $q->whereYear('p.fecha','>',$desde_año)
      ->orWhere(function($q2) use ($desde_año,$desde_mes){
        return $q2->whereYear('p.fecha','=',$desde_año)
        ->whereMonth('p.fecha','>=',$desde_mes);
      });
    })
    ->where(function($q) use ($hasta_año,$hasta_mes){
      return $q->whereYear('p.fecha','<',$hasta_año)
      ->orWhere(function($q2) use ($hasta_año,$hasta_mes){
        return $q2->whereYear('p.fecha','=',$hasta_año)
        ->whereMonth('p.fecha','<=',$hasta_mes);
      });
    })
    ->orderBy('p.fecha','asc')
    ->orderBy('c.nombre','asc')
    ->orderBy('p.id_tipo_moneda','asc')->get();
    
    return $data;
  }
}

