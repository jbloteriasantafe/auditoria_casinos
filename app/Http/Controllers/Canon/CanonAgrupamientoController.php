<?php
namespace App\Http\Controllers\Canon;

use App\Http\Controllers\Controller;
use App\Http\Controllers\UsuarioController;
use Illuminate\Support\Facades\DB;

/*
 *  Para calculos desde base
 * 
 *  Si Sum N1 = A1 -> A1(
 *                      exacto = Sum N1,
 *                      pre_red = Sum r(N1),
 *                      pos_red = r(A1.exacto),
 *                      err_red = A1.pos_red - A1.pre_red
 *                    )
 *     Sum N2 = A2 -> A2(
 *                      exacto = Sum N2,
 *                      pre_red = Sum r(N2),
 *                      pos_red = r(A2.exacto),
 *                      err_red = A2.pos_red - A2.pre_red
 *                    )
 *     .
 *     .
 *     .
 * 
 *     Sum N = T  -> A2(
 *                      exacto = Sum N,
 *                      pre_red = Sum r(N),
 *                      pos_red = r(N.exacto),
 *                      err_red = N.pos_red - N.pre_red
 *                    )
 *   
 *   Ahora a veces hay que mostrar T como T = A1+A2+...
 * 
 *  Entonces
 *    Sum A = Tsup -> Tsup (
 *                       exacto = Sum A = Sum Sum Ni // Alcanza con sumar nomas
 *                       pre_red = Sum r(A) //Necesito precalcular el nivel anterior
 *                       pos_red = r(Sum A) = r(Tsup.exacto)
 *                       err_red = Tsup.pos_red - T.pre_red
 *                    )
 * 
 *  Se necesita no solo una estructura de dependencias contra subcanons sino tambien contra otros grupos
 * */
class CanonAgrupamientoController extends Controller
{
  private static $instance;

  public static function getInstancia(){
    self::$instance = self::$instance ?? (new self()); 
    return self::$instance;
  }
  
  private $u = null;
  private $CVD = null;
  private $CGO = null;
  public function __construct(){
    self::$instance = $this;
    $this->CVD = CanonValorPorDefectoController::getInstancia();
    $this->middleware(function ($request, $next) {
      $this->u = UsuarioController::getInstancia()->quienSoy()['usuario'];
      return $next($request);
    });
  }
  
  public function down(){
    DB::unprepared("DROP FUNCTION IF EXISTS canon_bankers_round_2digits");
    DB::unprepared("DROP FUNCTION IF EXISTS canon_agrupamiento_hash");
    DB::unprepared("DROP TABLE IF EXISTS canon_subcanon_a_grupo");
    DB::unprepared("DROP TABLE IF EXISTS canon_agrupamiento");
    CANON_STREAM_STR('CANON_AGRUPAMIENTO: DOWN');
  }
  
  public function up(){
    DB::unprepared("
      CREATE FUNCTION canon_bankers_round_2digits(val DECIMAL(65,30))
      RETURNS DECIMAL(37,2)
      DETERMINISTIC
      BEGIN
          DECLARE tru DECIMAL(37,2);
          DECLARE diff DECIMAL(30,30);
          DECLARE sgn DECIMAL(1,0);
          DECLARE trip BOOL;

          SET tru  = TRUNCATE(val, 2);
          SET sgn  = SIGN(val);
          SET diff = ABS(val - tru);
          SET trip = diff > 0.005 OR (diff = 0.005 AND (tru*100)%2);

          RETURN tru + 0.01*trip*sgn;
      END
    ");
    
    DB::unprepared("
      CREATE FUNCTION canon_agrupamiento_hash(id_grupo_operador INT,nivel INT,año_mes date,clave VARCHAR(32),valor varchar(32))
      RETURNS binary(20)
      DETERMINISTIC
      BEGIN
        RETURN UNHEX(
          SHA(
            CONCAT_WS(
              '|',
              IFNULL(id_grupo_operador,''),
              IFNULL(nivel,''),
              IFNULL(año_mes,''),
              IFNULL(clave,''),
              IFNULL(valor,'')
            )
          )
        );
      END
    ");
  
    DB::statement("
    CREATE TABLE IF NOT EXISTS canon_subcanon_a_grupo (
      id_canon_subcanon_a_grupo INT NOT NULL AUTO_INCREMENT,
      id_grupo_operador INT NOT NULL,
      nivel    INT NOT NULL,
      clave    VARCHAR(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL,
      valor    VARCHAR(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL,
      base_subcanon_o_superior_dependencia VARCHAR(64)  CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL,
      base_tipo     VARCHAR(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL,
      coordenadas_x  DOUBLE NULL,
      coordenadas_y  DOUBLE NULL,
      base_coordenadas_x  DOUBLE NULL,
      base_coordenadas_y  DOUBLE NULL,
      PRIMARY KEY (id_canon_subcanon_a_grupo),
      UNIQUE KEY (nivel,id_grupo_operador,clave,valor,base_subcanon_o_superior_dependencia,base_tipo)
    )
    ");
  
    DB::statement("
    CREATE TABLE IF NOT EXISTS canon_agrupamiento (
      id_canon_agrupamiento int(11) NOT NULL AUTO_INCREMENT,

      id_grupo_operador INT NOT NULL,	
      nivel INT NOT NULL,
      clave VARCHAR(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL,
      valor VARCHAR(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL,
      año_mes date NOT NULL,
      
      hash binary(20) NOT NULL,
        
      beneficio¬exacto        DECIMAL(65,30) NULL,
      beneficio¬pre_red       DECIMAL(37,2)  NULL,
      beneficio¬pos_red       DECIMAL(37,2)  NULL,
      beneficio¬err_red       DECIMAL(2,2)   NULL,
      
      devengado_bruto¬exacto  DECIMAL(65,30) NULL,
      devengado_bruto¬pre_red DECIMAL(37,2)  NULL,
      devengado_bruto¬pos_red DECIMAL(37,2)  NULL,
      devengado_bruto¬err_red DECIMAL(2,2)   NULL,

      devengado_deduccion¬exacto  DECIMAL(65,30) NULL,
      devengado_deduccion¬pre_red DECIMAL(37,2)  NULL,
      devengado_deduccion¬pos_red DECIMAL(37,2)  NULL,
      devengado_deduccion¬err_red DECIMAL(2,2)   NULL,      
      
      devengado¬exacto  DECIMAL(65,30) NULL,
      devengado¬pre_red DECIMAL(37,2)  NULL,
      devengado¬pos_red DECIMAL(37,2)  NULL,
      devengado¬err_red DECIMAL(2,2)   NULL,  
      
      determinado_bruto¬exacto  DECIMAL(65,30) NULL,
      determinado_bruto¬pre_red DECIMAL(37,2)  NULL,
      determinado_bruto¬pos_red DECIMAL(37,2)  NULL,
      determinado_bruto¬err_red DECIMAL(2,2)   NULL,

      determinado_ajuste¬exacto  DECIMAL(65,30) NULL,
      determinado_ajuste¬pre_red DECIMAL(37,2)  NULL,
      determinado_ajuste¬pos_red DECIMAL(37,2)  NULL,
      determinado_ajuste¬err_red DECIMAL(2,2)   NULL,      
      
      determinado¬exacto  DECIMAL(65,30) NULL,
      determinado¬pre_red DECIMAL(37,2)  NULL,
      determinado¬pos_red DECIMAL(37,2)  NULL,
      determinado¬err_red DECIMAL(2,2)   NULL,
            
      PRIMARY KEY (id_canon_agrupamiento),
      INDEX idx_canon_agrupamiento (año_mes,nivel,id_grupo_operador,clave,valor),
      UNIQUE KEY unq_canon_agrupamiento_hash (hash)
    )
    ");
    
    CANON_STREAM_STR('CANON_AGRUPAMIENTO: UP');
  }

  private function __sumar_columnas(){
    return "
        cagg.beneficio¬exacto = IFNULL(cagg.beneficio¬exacto,0) + IFNULL(agrupado.beneficio¬exacto,0),
        cagg.beneficio¬pre_red = IFNULL(cagg.beneficio¬pre_red,0) + IFNULL(agrupado.beneficio¬pre_red,0),
        
        cagg.devengado_bruto¬exacto = IFNULL(cagg.devengado_bruto¬exacto,0) + IFNULL(agrupado.devengado_bruto¬exacto,0) ,
        cagg.devengado_bruto¬pre_red = IFNULL(cagg.devengado_bruto¬pre_red,0) + IFNULL(agrupado.devengado_bruto¬pre_red,0) ,
        
        cagg.devengado_deduccion¬exacto = IFNULL(cagg.devengado_deduccion¬exacto,0) + IFNULL(agrupado.devengado_deduccion¬exacto,0) ,
        cagg.devengado_deduccion¬pre_red = IFNULL(cagg.devengado_deduccion¬pre_red,0) + IFNULL(agrupado.devengado_deduccion¬pre_red,0) ,
        
        cagg.devengado¬exacto = IFNULL(cagg.devengado¬exacto,0) + IFNULL(agrupado.devengado¬exacto,0) ,
        cagg.devengado¬pre_red = IFNULL(cagg.devengado¬pre_red,0) + IFNULL(agrupado.devengado¬pre_red,0) ,
        
        cagg.determinado_bruto¬exacto = IFNULL(cagg.determinado_bruto¬exacto,0) + IFNULL(agrupado.determinado_bruto¬exacto,0) ,
        cagg.determinado_bruto¬pre_red = IFNULL(cagg.determinado_bruto¬pre_red,0)+ IFNULL(agrupado.determinado_bruto¬pre_red,0) ,
        
        cagg.determinado_ajuste¬exacto = IFNULL(cagg.determinado_ajuste¬exacto,0) + IFNULL(agrupado.determinado_ajuste¬exacto,0) ,
        cagg.determinado_ajuste¬pre_red = IFNULL(cagg.determinado_ajuste¬pre_red,0) + IFNULL(agrupado.determinado_ajuste¬pre_red,0) ,
        
        cagg.determinado¬exacto = IFNULL(cagg.determinado¬exacto,0) + IFNULL(agrupado.determinado¬exacto,0) ,
        cagg.determinado¬pre_red = IFNULL(cagg.determinado¬pre_red,0) + IFNULL(agrupado.determinado¬pre_red,0)
    ";
  }
  
  private function calcular_columnas_dependientes(string $clave,string $año_mes){
    DB::statement("
      UPDATE canon_agrupamiento as cagg
      SET
        beneficio¬pos_red = canon_bankers_round_2digits(beneficio¬exacto),
        beneficio¬err_red = beneficio¬pos_red - beneficio¬pre_red,
        
        devengado_bruto¬pos_red = canon_bankers_round_2digits(devengado_bruto¬exacto),
        devengado_bruto¬err_red = devengado_bruto¬pos_red - devengado_bruto¬pre_red,
        
        devengado_deduccion¬pos_red = canon_bankers_round_2digits(devengado_deduccion¬exacto),
        devengado_deduccion¬err_red = devengado_deduccion¬pos_red - devengado_deduccion¬pre_red,
        
        devengado¬pos_red = canon_bankers_round_2digits(devengado¬exacto),
        devengado¬err_red = devengado¬pos_red - devengado¬pre_red,
        
        determinado_bruto¬pos_red = canon_bankers_round_2digits(determinado_bruto¬exacto),
        determinado_bruto¬err_red = determinado_bruto¬pos_red - determinado_bruto¬pre_red,
        
        determinado_ajuste¬pos_red = canon_bankers_round_2digits(determinado_ajuste¬exacto),
        determinado_ajuste¬err_red = determinado_ajuste¬pos_red - determinado_ajuste¬pre_red,
        
        determinado¬pos_red = canon_bankers_round_2digits(determinado¬exacto),
        determinado¬err_red = determinado¬pos_red - determinado¬pre_red
      WHERE cagg.año_mes = ? AND cagg.clave = ?
    ",[$año_mes,$clave]);
  }
    
  private function recalcular_año_mes(string $año_mes){
    $claves = DB::table('canon_subcanon_a_grupo')
    ->select('clave')->distinct()->get();
    foreach($claves as $clave){
      $this->recalcular_clave_año_mes($clave->clave,$año_mes);
    }
  }
      
  private function inicializar_agrupamientos(string $clave,string $año_mes){
    //Inicializa todos los valores en 0
    DB::statement("
      INSERT INTO canon_agrupamiento (id_grupo_operador,nivel,año_mes,clave,valor,hash)	
      SELECT DISTINCT scagg.id_grupo_operador,scagg.nivel,? as año_mes,scagg.clave,scagg.valor,canon_agrupamiento_hash(scagg.id_grupo_operador,scagg.nivel,?,scagg.clave,scagg.valor) as hash
      FROM canon_subcanon_a_grupo as scagg
      WHERE scagg.clave = ?
    ",[$año_mes,$año_mes,$clave]);
  }

  public function recalcular_clave_año_mes(string $clave,string $año_mes){
    DB::table('canon_agrupamiento')
    ->where('año_mes',$año_mes)
    ->where('clave',$clave)
    ->delete();
    
    $niveles = DB::table('canon_subcanon_a_grupo')
    ->select('nivel')->distinct()
    ->where('clave',$clave)
    ->orderBy('nivel','asc')
    ->get()
    ->pluck('nivel')->values();

    $this->inicializar_agrupamientos($clave,$año_mes);
    
    foreach($niveles as $n){
      if($n == 0){
        $this->agrupar_base($clave,$año_mes);
      }
      else{
        $this->agrupar_superior($clave,$año_mes,$n);
      }
    }
    
    $this->calcular_columnas_dependientes($clave,$año_mes);
  }

  private function agrupar_base(string $clave,string $año_mes){
    $q = function($beneficio,$sc) use ($año_mes,$clave){
      DB::statement("
      UPDATE canon_agrupamiento as cagg
      JOIN (
        SELECT 
          scagg.id_grupo_operador,
          scagg.clave,
          scagg.valor,
          
          $beneficio,
          
          SUM(sc.devengar * sc.devengado_total) as devengado_bruto¬exacto,
          SUM(canon_bankers_round_2digits(sc.devengar * sc.devengado_total)) as devengado_bruto¬pre_red,
          
          SUM(sc.devengar * sc.devengado_deduccion) as devengado_deduccion¬exacto,
          SUM(canon_bankers_round_2digits(sc.devengar * sc.devengado_deduccion)) as devengado_deduccion¬pre_red,
          
          SUM(sc.devengar * sc.devengado) as devengado¬exacto,
          SUM(canon_bankers_round_2digits(sc.devengar * sc.devengado)) as devengado¬pre_red,
          
          SUM(sc.determinado_total) as determinado_bruto¬exacto,
          SUM(canon_bankers_round_2digits(sc.determinado_total)) as determinado_bruto¬pre_red,
          
          SUM(sc.determinado_ajuste) as determinado_ajuste¬exacto,
          SUM(canon_bankers_round_2digits(sc.determinado_ajuste)) as determinado_ajuste¬pre_red,
          
          SUM(sc.determinado) as determinado¬exacto,
          SUM(canon_bankers_round_2digits(sc.determinado)) as determinado¬pre_red
          
        FROM canon as c
        JOIN $sc as sc
          ON sc.id_canon = c.id_canon
        
        JOIN canon_grupo_operador_operador as cgoo ON cgoo.id_operador = c.id_operador
        JOIN canon_grupo_operador as cgo ON cgo.id_grupo_operador = cgoo.id_grupo_operador AND cgo.deleted_at IS NULL
        
        JOIN canon_subcanon_a_grupo as scagg
          ON  scagg.id_grupo_operador = cgo.id_grupo_operador
          AND scagg.base_subcanon_o_superior_dependencia = '$sc'
          AND scagg.base_tipo = sc.tipo
          AND scagg.nivel = 0
          AND scagg.clave = ?
        WHERE c.año_mes = ? AND c.deleted_at IS NULL
        GROUP BY scagg.id_grupo_operador, scagg.clave, scagg.valor
      ) as agrupado 
        ON  agrupado.id_grupo_operador = cagg.id_grupo_operador
        AND agrupado.clave = cagg.clave
        AND agrupado.valor = cagg.valor
      SET 
        ".$this->__sumar_columnas()."
      WHERE cagg.año_mes = ? and cagg.nivel = 0
      ", [$clave, $año_mes, $año_mes]);
    };
    
    $q("
        SUM(sc.determinado_subtotal) as beneficio¬exacto,
        SUM(canon_bankers_round_2digits(sc.determinado_subtotal)) as beneficio¬pre_red
    ","canon_variable");
    $q("
        SUM(sc.bruto) as beneficio¬exacto,
        SUM(canon_bankers_round_2digits(sc.bruto)) as beneficio¬pre_red
    ","canon_fijo_mesas");
    $q("
        0 as beneficio¬exacto,
        0 as beneficio¬pre_red
    ","canon_fijo_mesas_adicionales");
  }

  private function agrupar_superior(string $clave,string $año_mes,int $nivel){
    DB::statement("
    UPDATE canon_agrupamiento as cagg
    JOIN (
      SELECT 
        cagg2.id_grupo_operador,
        cagg2.nivel+1 as nivel,
        cagg2.clave,
        scagg.valor,
        
        SUM(cagg2.beneficio¬exacto) as beneficio¬exacto,
        SUM(cagg2.beneficio¬pre_red) as beneficio¬pre_red,
        
        SUM(cagg2.devengado_bruto¬exacto) as devengado_bruto¬exacto,
        SUM(cagg2.devengado_bruto¬pre_red) as devengado_bruto¬pre_red,
        SUM(cagg2.devengado_deduccion¬exacto) as devengado_deduccion¬exacto,
        SUM(cagg2.devengado_deduccion¬pre_red) as devengado_deduccion¬pre_red,
        SUM(cagg2.devengado¬exacto) as devengado¬exacto,
        SUM(cagg2.devengado¬pre_red) as devengado¬pre_red,
        
        SUM(cagg2.determinado_bruto¬exacto) as determinado_bruto¬exacto,
        SUM(cagg2.determinado_bruto¬pre_red) as determinado_bruto¬pre_red,
        SUM(cagg2.determinado_ajuste¬exacto) as determinado_ajuste¬exacto,
        SUM(cagg2.determinado_ajuste¬pre_red) as determinado_ajuste¬pre_red,
        SUM(cagg2.determinado¬exacto) as determinado¬exacto,
        SUM(cagg2.determinado¬pre_red) as determinado¬pre_red
      
      FROM canon_subcanon_a_grupo as scagg
      JOIN canon_agrupamiento as cagg2
        ON  cagg2.id_grupo_operador = scagg.id_grupo_operador
        AND cagg2.clave = scagg.clave
        AND cagg2.valor = scagg.base_subcanon_o_superior_dependencia
        AND cagg2.nivel = ?
        AND cagg2.clave = ?
        AND cagg2.año_mes = ?
      WHERE 
            scagg.nivel = ?
        AND scagg.id_grupo_operador IS NOT NULL
      GROUP BY cagg2.id_grupo_operador,cagg2.año_mes,cagg2.clave,cagg2.nivel,scagg.valor
    ) as agrupado 
      ON  agrupado.id_grupo_operador = cagg.id_grupo_operador
      AND agrupado.nivel = cagg.nivel
      AND agrupado.clave = cagg.clave
      AND agrupado.valor = cagg.valor
    SET
      ".$this->__sumar_columnas()."
    WHERE cagg.año_mes = ? and cagg.nivel = ? and cagg.id_grupo_operador IS NOT NULL
    ", [$nivel-1, $clave, $año_mes, $nivel, $año_mes, $nivel]);
  }

  public function guardarAgrupamientos(array $agrupamientos){
    $to_insert = [];
    
    foreach($agrupamientos as $clave => $base_superiores){
      $base = $base_superiores['base'] ?? [];
      $superiores = $base_superiores['superiores'] ?? [];
      
      $nivel = 0;
      foreach($base as $valor => $id_grupo_operador_subcanons){
        foreach($id_grupo_operador_subcanons as $id_grupo_operador => $subcanon_tipos){
          foreach($subcanon_tipos as $base_subcanon_o_superior_dependencia => $tipos){            
            foreach($tipos as $base_tipo){
              $i = compact('nivel','id_grupo_operador','clave','valor','base_subcanon_o_superior_dependencia','base_tipo');
              $to_insert[implode('|',$i)] = $i;
            }
          }
        }
      }
      
      foreach($superiores as $nidx => $valores_id_grupos_operadores){
        $nivel = $nidx + 1;
        $base_tipo = '';
        
        foreach($valores_id_grupos_operadores as $valor => $id_grupos_operador_dependencias){
          foreach($id_grupos_operador_dependencias as $id_grupo_operador => $dependencias){
            foreach($dependencias as $base_subcanon_o_superior_dependencia){
              $i = compact('nivel','id_grupo_operador','clave','valor','base_subcanon_o_superior_dependencia','base_tipo');
              $to_insert[implode('|',$i)] = $i;
            }
          }
        }
      }
    }
    
    DB::table('canon_subcanon_a_grupo')
    ->insert(array_values($to_insert));
  }
  
  
  public function obtenerArregloSuperior($año_mes,$id_grupo_operador,$clave){
    $max_nivel = DB::table('canon_agrupamiento')
    ->select(DB::raw('MAX(nivel) as nivel'))
    ->where('año_mes',$año_mes)
    ->where('clave',$clave)
    ->where('id_grupo_operador',$id_grupo_operador)
    ->groupBy(DB::raw('"constant"'))
    ->first();
    
    if($max_nivel === null) return collect([]);
    
    return $this->obtenerCalculadoNivel($año_mes,$id_grupo_operador,$clave,$max_nivel->nivel);
  }

   public function obtenerCalculadoNivel($año_mes,$id_grupo_operador,$clave,$nivel){
    return DB::table('canon_agrupamiento')
    ->where('año_mes',$año_mes)
    ->where('clave',$clave)
    ->where('id_grupo_operador',$id_grupo_operador)
    ->where('nivel',$nivel)
    ->get()->keyBy('valor');
  }
  
  public function obtener($año_mes,$id_grupo_operador,$nivel,$clave,$valor){
    $data = DB::table('canon_agrupamiento')
    ->where('año_mes',$año_mes)
    ->where('clave',$clave)
    ->where('nivel',$nivel)
    ->where('valor',$valor)
    ->where('id_grupo_operador',$id_grupo_operador);
    
    $data = $data->first();
    
    if($data === null) return null;
    
    unset($data->id_grupo_operador);
    unset($data->valor);
    unset($data->año_mes);
    unset($data->clave);
    unset($data->nivel);
    unset($data->hash);
    unset($data->id_canon_agrupamiento);
    
    return $data;
  }
  
  private function __agrupamientos_detallados_expandir_base($id_grupos_operadores,$scs,$base){
    $ret = [];
    foreach($base as $v => $cas_groups){
      $ret[$v] = [];
      
      if(array_key_exists('*',$cas_groups)){
        foreach($id_grupos_operadores as $idc){//Todos los grupos-operadores
          $ret[$v][$idc] = [];
        }
      }
      else{
        foreach($cas_groups as $idc => $_){
          $ret[$v][$idc] = [];
        }
      }
      
      foreach($ret[$v] as $idc => $_){
        $sc_groups = $cas_groups[$idc] ?? $cas_groups['*'] ?? [];
        if(array_key_exists('*',$sc_groups)){//Todos los grupos-operadores
          foreach($scs as $sc => $_){
            $ret[$v][$idc][$sc] = [];
          }
        }
        else{
          foreach($sc_groups as $sc => $_){
            $ret[$v][$idc][$sc] = [];
          }
        }
        
        foreach($ret[$v][$idc] as $sc => $_){
          $t_groups = $sc_groups[$sc] ?? $sc_groups['*'] ?? [];
          if(in_array('*',$t_groups)){
            $ret[$v][$idc][$sc] = $scs[$sc] ?? [];
          }
          else{
            $ret[$v][$idc][$sc] = $t_groups;
          }
        }
      }
    } 
    return $ret;
  }
  
  private function __agrupamientos_detallados_expandir_superiores($id_grupos_operadores,$valores_nivel_anterior,$superiores){
    $ret = [];
    foreach($superiores as $nidx => $nivel_vals){
      $ret[$nidx] = [];
      
      foreach($nivel_vals as $v => $cas_groups){
        $ret[$nidx][$v] = [];
        if(array_key_exists('*',$cas_groups)){
          foreach($id_grupos_operadores as $idc){//Todos los grupos-operadores
            $ret[$nidx][$v][$idc] = [];
          }
        }
        else{
          foreach($cas_groups as $idc => $_){
            $ret[$nidx][$v][$idc] = [];
          }
        }
        
        foreach($ret[$nidx][$v] as $idc => $_){
          $dependencias = $cas_groups[$idc] ?? $cas_groups['*'] ?? [];
          if(in_array('*',$dependencias)){
            foreach($valores_nivel_anterior as $didx => $d){
              $ret[$nidx][$v][$idc][] = $d;
            }
          }
          else{
            foreach($dependencias as $didx => $d){
              if(in_array($d,$valores_nivel_anterior)){//Solo puede depender del nivel anterior
                $ret[$nidx][$v][$idc][] = $d;
              }
            }
          }
        }
      }
      
      $valores_nivel_anterior = array_keys($nivel_vals);
    }
    return $ret;
  }
  
  public function agrupamientos_detallados($agrupamientos){
    $id_grupos_operadores = DB::table('canon_grupo_operador')
    ->select('id_grupo_operador')->distinct()
    ->whereNull('deleted_at')
    ->get()->pluck('id_grupo_operador')->toArray();
    
    $scs = [
      'canon_variable' => null,
      'canon_fijo_mesas' => null,
      'canon_fijo_mesas_adicionales' => null
    ];
    
    foreach($scs as $sc => $_){
      $scs[$sc] = DB::table($sc)
      ->select('tipo')->distinct()
      ->get()->pluck('tipo')->toArray();
    }
    
    $agrupamientos_detallados = [];//Los agrupamientos detallados por grupo-operador etc... sin los *...
    foreach($agrupamientos as $clave => $base_superiores){
      $agrupamientos_detallados[$clave] = [];
      $agrupamientos_detallados[$clave]['base'] = $this->__agrupamientos_detallados_expandir_base(
        $id_grupos_operadores,
        $scs,
        $base_superiores['base'] ?? []
      );
      
      $agrupamientos_detallados[$clave]['superiores'] = $this->__agrupamientos_detallados_expandir_superiores(
        $id_grupos_operadores,
        array_keys($base_superiores['base']),
        $base_superiores['superiores'] ?? []
      );
    }
    
    return $agrupamientos_detallados;
  }
  
  public function recalcular_agrupamiento(string $año_mes){
    return $this->recalcular_agrupamientos([$año_mes],false);
  }
  
  public function por_defecto_req(){//Para debugear desde el navegador
    return $this->agrupamientos_detallados(
      $this->CVD->get('agrupamientos') ?? []
    );
  }
  
  public function llenado_inicial($created_at,$created_by){//Sin transaccion porque se llama desde CanonOperadorController con transaccion
    return $this->recalcular_todos();
  }
  
  public function recalcular_todos(){
    $año_meses = DB::table('canon')
    ->select('año_mes')->distinct()
    ->whereNull('deleted_at')
    ->get()->pluck('año_mes')->toArray();
    $this->down();
    $this->up();
    $agrupamientos_detallados = $this->agrupamientos_detallados($this->CVD->get('agrupamientos') ?? []);
    $this->guardarAgrupamientos($agrupamientos_detallados);
    CANON_STREAM_STR('AGRUPAMIENTOS: INICIALES GUARDARDOS');
    return $this->recalcular_agrupamientos($año_meses,true);
  }
    
  public function recalcular_agrupamientos_req(){
    CANON_STREAM_STR(true);
    return response()->stream(function(){   
      return DB::transaction(function(){
        return $this->recalcular_todos();
      });
    });
  }
  
  public function recalcular_agrupamientos(array $año_meses,bool $stream_progress){
    foreach($año_meses as $idx => $am){
      $this->recalcular_año_mes($am);
      CANON_STREAM_STR(($am.' | '.round(($idx+1.0)/count($año_meses)*100,2).'%'));
    }
    return $año_meses;
  }
  
  private function _group_dependencias($data){
    return $data->groupBy('clave')->map(function(&$clave_group){
      return $clave_group->groupBy('grupo_operador')->map(function(&$grupo_operador_group){
        return $grupo_operador_group->groupBy('nivel')->map(function(&$nivel_group){
          return $nivel_group->groupBy('valor');
        });
      });
    });
  }
  
  private function _group_dependencias_calculado($data){
    return $data->groupBy('clave')->map(function(&$clave_group){
      return $clave_group->groupBy('grupo_operador')->map(function(&$grupo_operador_group){
        return $grupo_operador_group->groupBy('nivel')->map(function(&$nivel_group){
          return $nivel_group->keyBy('valor');
        });
      });
    });
  }
  
  private function _collect_dependencias($grouped){    
    $ret = [];
    foreach($grouped as $clave => &$clave_groups){
      $ret[$clave] = [];
      foreach($clave_groups as $grupo_operador => &$grupo_operador_groups){
        $ret[$clave][$grupo_operador] = [];
        $max_nivel = $grupo_operador_groups->keys()->max();
        $prev = $grupo_operador_groups[0]->map(function($lista_base){
          return [
            'nivel' => 0,
            'dependencias' => $lista_base->map(function($entry){
              return (object)[
                'subcanon' => $entry->base_subcanon_o_superior_dependencia,
                'tipo' => $entry->base_tipo,
              ];
            })->toArray()
          ];
        })->toArray();
        
        for($n=1;$n<=$max_nivel;$n++){
          $new = [];
          $agregados = [];
          foreach($grupo_operador_groups[$n] as $valor => $lista_dependencias){
            $new[$valor] = ['dependencias' => [],'nivel' => $n];
            foreach($lista_dependencias as $dep){
              $new[$valor]['dependencias'][$dep->base_subcanon_o_superior_dependencia] = 
                $prev[$dep->base_subcanon_o_superior_dependencia] 
                ?? ['dependencias' => [],'nivel' => ($n-1)];
                
              $agregados[$dep->base_subcanon_o_superior_dependencia] = true;
            }
          }
          foreach($prev as $v => $d1){
            if(!array_key_exists($v,$agregados)){
              $new['`sinpadre'] = $new['`sinpadre'] ?? ['dependencias' => [],'nivel' => $n];
              $new['`sinpadre']['dependencias'][$v] = $d1;
            }
          }
          $prev = $new;
        }
        $ret[$clave][$grupo_operador] = $prev;
      }
    }
    
    return $ret;
  }
  
  public function buscar(){
    return DB::table('canon_subcanon_a_grupo as cagg')
    ->select('cagg.clave',DB::raw('GROUP_CONCAT(
      DISTINCT
      COALESCE(cgo.nombre,cagg.id_grupo_operador)
      ORDER BY COALESCE(cgo.nombre,cagg.id_grupo_operador) ASC 
      SEPARATOR ", "
    ) as grupos_operadores'))
    ->leftJoin('canon_grupo_operador as cgo',function($j){
      return $j->on('cgo.id_grupo_operador','=','cagg.id_grupo_operador')
      ->whereNull('cgo.deleted_at');
    })
    ->groupBy('cagg.clave')
    ->orderBy('clave','asc')
    ->paginate(request()->page_size ?? 10);
  }
  
  public function obtener_por_id(){
    $ret = DB::table('canon_subcanon_a_grupo as cagg_deps')
    ->select('cagg_deps.id_canon_subcanon_a_grupo','cagg_deps.nivel','cagg_deps.clave','cagg_deps.id_grupo_operador',DB::raw('COALESCE(cgo.nombre,cagg_deps.id_grupo_operador) as grupo_operador'))
    ->leftJoin('canon_grupo_operador as cgo',function($j){
      return $j->on('cgo.id_grupo_operador','=','cagg_deps.id_grupo_operador')
      ->whereNull('cgo.deleted_at');
    })
    ->where('id_canon_subcanon_a_grupo',request()->id_canon_subcanon_a_grupo ?? null)
    ->first() ?? (new \stdClass());
    return response()->json($ret);
  }
  
  public function obtener_por_clave(){
    $ret = DB::table('canon_subcanon_a_grupo as cagg_deps')
    ->where('clave',request()->clave ?? null)
    ->orderBy('nivel','asc')
    ->orderBy('id_grupo_operador','asc')
    ->orderBy('valor','asc')
    ->orderBy('base_tipo','asc')
    ->get()
    ->groupBy('clave')->map(function($clave_group){
      return $clave_group->groupBy('id_grupo_operador')->map(function($igo_group){
        return $igo_group->groupBy('nivel')->map(function($nivel_group){
          return $nivel_group->map(function($entry){
            $e = new \stdClass();
            $e->valor = $entry->valor;
            $e->coordenadas = [$entry->coordenadas_x,$entry->coordenadas_y];
            if($entry->nivel == 0){
              $e->dependencia = [$entry->base_subcanon_o_superior_dependencia,$entry->base_tipo];
              $e->base_coordenadas = [$entry->base_coordenadas_x,$entry->base_coordenadas_y];
            }
            else{
              $e->dependencia = $entry->base_subcanon_o_superior_dependencia;
            }
            return $e;
          });
        });
      });
    });
    return response()->json($ret);
  }
    
  public function buscar_calculado(){
    $dependencias = $this->buscar();
    
    $data = DB::table('canon_agrupamiento as cagg')
    ->select('cagg.*','cgo.nombre as grupo_operador')
    ->join('canon_grupo_operador as cgo','cgo.id_grupo_operador','=','cagg.id_grupo_operador')
    ->get();
    
    foreach($data as &$d){
      unset($d->hash);
    }
    
    
    return $data->groupBy('año_mes')->map(function($año_mes_group) use ($dependencias){
      $ret = [];
      $calculado_agrupado = $this->_group_dependencias_calculado($año_mes_group);
      foreach($dependencias as $clave => $clave_groups){
        $ret[$clave] = [];
        
        foreach($clave_groups as $grupo_operador => $grupo_operador_groups){
          $data = ($calculado_agrupado[$clave] ?? [])[$grupo_operador] ?? [];
          $recurse = function(string $valor,array $valor_group,array &$ret) use (&$data,&$recurse){
            $n = $valor_group['nivel'];
            if($n > 0){
              $ret[$valor]['nivel'] = $n;
              $ret[$valor]['data'] = (object)(($data[$n] ?? [])[$valor] ?? []);
              $deps = [];
              foreach($valor_group['dependencias'] as $valor2 => $valor_group2){
                $recurse($valor2,$valor_group2,$deps);
              }
              $ret[$valor]['dependencias'] = $deps;
            }
            else{
              $ret[$valor]['nivel'] = $n;
              $ret[$valor]['dependencias'] = $valor_group['dependencias'];
              $ret[$valor]['data'] = (object)(($data[$n] ?? [])[$valor] ?? []);
            }
          };
          
          $ret[$clave][$grupo_operador] = [];
          foreach($grupo_operador_groups as $valor => $valor_group){
            $recurse($valor,$valor_group,$ret[$clave][$grupo_operador]);
          }
        }
      }
      return $ret;
    })->toArray();
  }

  public function guardar(){
    /*
      Se recibe un objeto en formato 
      {
        clave: {
          id_grupo_operador: { 
            0: [
              {valor,dependecia<Array<2,String>>,base_coordenadas<Array<2,Double>>,coordenadas<Array<2,Double>>}
            ],
            1: [
              {valor,dependencia<String>,coordenadas<Array<2,Double>>}
            ]
            ...
          }
        }
      }
    */
    $agrupamientos = request()->all();
    $clave = array_keys($agrupamientos)[0] ?? null;
    if($clave === null) return response()->json(['clave' => ['No se recibió clave']],422);
    $id_grupos_operadores = $agrupamientos[$clave] ?? [];
    foreach($id_grupos_operadores as $id_grupo_operador => $niveles){
      foreach($niveles as $nivel => $entradas){
        
        if($nivel == 0){
          foreach($entradas as $e){
            $str_err = "$id_grupo_operador:$nivel:".($e['valor'] ?? 'null').': ';
            if(!array_key_exists('valor',$e)){
              return response()->json(['valor' => [$str_err.'No se recibió valor']],422);
            }
            if(!array_key_exists('dependencia',$e) 
              || $e['dependencia'] === null 
              || !is_array($e['dependencia']) 
              || count($e['dependencia']) != 2){
              return response()->json(['dependencia' => [$str_err.'No se recibió dependencia']],422);
            }
            if(!array_key_exists('base_coordenadas',$e)
              || $e['base_coordenadas'] === null 
              || !is_array($e['base_coordenadas']) 
              || count($e['base_coordenadas']) != 2
              || !is_numeric($e['base_coordenadas'][0])
              || !is_numeric($e['base_coordenadas'][1])
            ){
              return response()->json(['base_coordenadas' => [$str_err.'No se recibieron coordenadas base']],422);
            }
            if(!array_key_exists('coordenadas',$e)
              || $e['coordenadas'] === null 
              || !is_array($e['coordenadas']) 
              || count($e['coordenadas']) != 2
              || !is_numeric($e['coordenadas'][0])
              || !is_numeric($e['coordenadas'][1])
            ){
              return response()->json(['coordenadas' => [$str_err.'No se recibieron coordenadas']],422);
            }
          }
        }
        else{
          foreach($entradas as $e){
            $str_err = "$id_grupo_operador:$nivel:".($e['valor'] ?? 'null').': ';
            if(!array_key_exists('valor',$e)){
              return response()->json(['valor' => [$str_err.'No se recibió valor']],422);
            }
            if(!array_key_exists('dependencia',$e) || !is_string($e['dependencia'])){
              return response()->json(['dependencia' => [$str_err.'No se recibió dependencia']],422);
            }
            if(!array_key_exists('coordenadas',$e)
              || $e['coordenadas'] === null 
              || !is_array($e['coordenadas']) 
              || count($e['coordenadas']) != 2
              || !is_numeric($e['coordenadas'][0])
              || !is_numeric($e['coordenadas'][1])
            ){
              return response()->json(['coordenadas' => [$str_err.'No se recibieron coordenadas']],422);
            }
          }
        }
      }
    }

    return DB::transaction(function() use ($clave,$id_grupos_operadores){
      DB::table('canon_subcanon_a_grupo')
      ->where('clave',$clave)
      ->delete();

      foreach($id_grupos_operadores as $id_grupo_operador => $niveles){
        foreach($niveles as $nivel => $entradas){
          foreach($entradas as $e){
            if($nivel == 0){
              DB::table('canon_subcanon_a_grupo')
              ->insert([
                'nivel' => 0,
                'id_grupo_operador' => $id_grupo_operador,
                'clave' => $clave,
                'valor' => $e['valor'],
                'base_subcanon_o_superior_dependencia' => $e['dependencia'][0],
                'base_tipo' => $e['dependencia'][1],
                'base_coordenadas_x' => $e['base_coordenadas'][0],
                'base_coordenadas_y' => $e['base_coordenadas'][1],
                'coordenadas_x' => $e['coordenadas'][0],
                'coordenadas_y' => $e['coordenadas'][1]
              ]);
            }
            else{
              DB::table('canon_subcanon_a_grupo')
              ->insert([
                'nivel' => $nivel,
                'id_grupo_operador' => $id_grupo_operador,
                'clave' => $clave,
                'valor' => $e['valor'],
                'base_subcanon_o_superior_dependencia' => $e['dependencia'],
                'base_tipo' => '',
                'base_coordenadas_x' => null,
                'base_coordenadas_y' => null,
                'coordenadas_x' => $e['coordenadas'][0],
                'coordenadas_y' => $e['coordenadas'][1]
              ]);
            }
          }
        }
      }

      DB::table('canon')->select('año_mes')
      ->whereNull('deleted_at')->distinct()->get()->pluck('año_mes')->each(function($año_mes) use ($clave){
        $this->recalcular_clave_año_mes($clave,$año_mes);
      });

      return ['mensaje' => 'Agrupamientos guardados correctamente'];
    });
  }

  public function borrarAgrupamiento(string $clave){
    return DB::transaction(function() use ($clave){
      DB::table('canon_subcanon_a_grupo')
      ->where('clave',$clave)
      ->delete();

       DB::table('canon_agrupamiento')
      ->where('clave',$clave)
      ->delete();
      
      return ['mensaje' => 'Agrupamiento borrado correctamente'];
    });
  }
}
