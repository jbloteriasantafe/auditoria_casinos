<?php
namespace App\Http\Controllers\Canon;

use App\Http\Controllers\Controller;
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
      CREATE FUNCTION canon_agrupamiento_hash(id_casino INT,nivel INT,año_mes date,clave VARCHAR(32),valor varchar(32))
      RETURNS binary(20)
      DETERMINISTIC
      BEGIN
        RETURN UNHEX(
          SHA(
            CONCAT_WS(
              '|',
              IFNULL(id_casino,''),
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
      id_casino INT NOT NULL,
      nivel    INT NOT NULL,
      clave    VARCHAR(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL,
      valor    VARCHAR(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL,
      base_subcanon_o_superior_dependencia VARCHAR(64)  CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL,
      base_tipo     VARCHAR(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL,
      PRIMARY KEY (id_canon_subcanon_a_grupo),
      UNIQUE KEY (nivel,id_casino,clave,valor,base_subcanon_o_superior_dependencia,base_tipo)
    )
    ");
  
    DB::statement("
    CREATE TABLE IF NOT EXISTS canon_agrupamiento (
      id_canon_agrupamiento int(11) NOT NULL AUTO_INCREMENT,

      id_casino INT NULL,	
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
      INDEX idx_canon_agrupamiento (año_mes,nivel,id_casino,clave,valor),
      UNIQUE KEY unq_canon_agrupamiento_hash (hash)
    )
    ");
  }
    
  private function inicializar_agrupamientos(string $año_mes){
    //Inicializa todos los valores en 0
    DB::statement("
      INSERT INTO canon_agrupamiento (id_casino,nivel,año_mes,clave,valor,hash)	
      SELECT DISTINCT scagg.id_casino,scagg.nivel,? as año_mes,scagg.clave,scagg.valor,canon_agrupamiento_hash(scagg.id_casino,scagg.nivel,?,scagg.clave,scagg.valor) as hash
      FROM canon_subcanon_a_grupo as scagg
    ",[$año_mes,$año_mes]);
    
    DB::statement("
      INSERT INTO canon_agrupamiento (id_casino,nivel,año_mes,clave,valor,hash)	
      SELECT DISTINCT NULL,scagg.nivel,? as año_mes,scagg.clave,scagg.valor,canon_agrupamiento_hash(NULL,scagg.nivel,?,scagg.clave,scagg.valor) as hash
      FROM canon_subcanon_a_grupo as scagg
    ",[$año_mes,$año_mes]);
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
  
  private function agrupar_base(string $año_mes){
    $q = function($beneficio,$sc) use ($año_mes){
      DB::statement("
      UPDATE canon_agrupamiento as cagg
      JOIN (
        SELECT 
          scagg.id_casino,
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
        JOIN canon_subcanon_a_grupo as scagg
          ON  scagg.id_casino = c.id_casino
          AND scagg.base_subcanon_o_superior_dependencia = '$sc'
          AND scagg.base_tipo = sc.tipo
          AND scagg.nivel = 0
        WHERE c.año_mes = ? AND c.deleted_at IS NULL
        GROUP BY scagg.id_casino, scagg.clave, scagg.valor
      ) as agrupado 
        ON  agrupado.id_casino = cagg.id_casino
        AND agrupado.clave = cagg.clave
        AND agrupado.valor = cagg.valor
      SET 
        ".$this->__sumar_columnas()."
      WHERE cagg.año_mes = ? and cagg.nivel = 0
      ", [$año_mes, $año_mes]);
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
  
  private function agrupar_superior(string $año_mes,int $nivel){
  DB::statement("
  UPDATE canon_agrupamiento as cagg
    JOIN (
      SELECT 
        cagg2.id_casino,
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
        ON  cagg2.id_casino = scagg.id_casino
        AND cagg2.clave = scagg.clave
        AND cagg2.valor = scagg.base_subcanon_o_superior_dependencia
        AND cagg2.nivel = ($nivel-1)
        AND cagg2.año_mes = ?
      WHERE 
            scagg.nivel = $nivel
        AND scagg.id_casino IS NOT NULL
      GROUP BY cagg2.id_casino,cagg2.año_mes,cagg2.clave,cagg2.nivel,scagg.valor
    ) as agrupado 
      ON  agrupado.id_casino = cagg.id_casino
      AND agrupado.nivel = cagg.nivel
      AND agrupado.clave = cagg.clave
      AND agrupado.valor = cagg.valor
    SET
      ".$this->__sumar_columnas()."
    WHERE cagg.año_mes = ? and cagg.nivel = $nivel and cagg.id_casino IS NOT NULL
    ", [$año_mes, $año_mes]);
  }
    
  private function calcular_columnas_dependientes(string $año_mes){
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
      WHERE cagg.año_mes = ?
    ",[$año_mes]);
  }
  
  private function agrupar_sin_casino(string $año_mes){
    //Sumo los casinos y lo seteo
    DB::statement("
    UPDATE canon_agrupamiento as cagg
    JOIN (
      SELECT 
        cagg2.clave,
        cagg2.valor,
        cagg2.nivel,
        
        SUM(beneficio¬exacto) as beneficio¬exacto,
        SUM(beneficio¬pre_red) as beneficio¬pre_red,
        
        SUM(devengado_bruto¬exacto) as devengado_bruto¬exacto,
        SUM(devengado_bruto¬pre_red) as devengado_bruto¬pre_red,
        SUM(devengado_deduccion¬exacto) as devengado_deduccion¬exacto,
        SUM(devengado_deduccion¬pre_red) as devengado_deduccion¬pre_red,
        SUM(devengado¬exacto) as devengado¬exacto,
        SUM(devengado¬pre_red) as devengado¬pre_red,
        
        SUM(determinado_bruto¬exacto) as determinado_bruto¬exacto,
        SUM(determinado_bruto¬pre_red) as determinado_bruto¬pre_red,
        SUM(determinado_ajuste¬exacto) as determinado_ajuste¬exacto,
        SUM(determinado_ajuste¬pre_red) as determinado_ajuste¬pre_red,
        SUM(determinado¬exacto) as determinado¬exacto,
        SUM(determinado¬pre_red) as determinado¬pre_red
        
      FROM canon_agrupamiento as cagg2
      WHERE cagg2.año_mes = ? AND cagg2.id_casino IS NOT NULL
      GROUP BY cagg2.año_mes, cagg2.clave, cagg2.valor, cagg2.nivel
    ) as agrupado 
      ON  agrupado.clave = cagg.clave
      AND agrupado.valor = cagg.valor
      AND agrupado.nivel = cagg.nivel
    SET 
      ".$this->__sumar_columnas()."
    WHERE cagg.año_mes = ? AND cagg.id_casino IS NULL 
    ", [$año_mes, $año_mes]);
  }
  
  private function recalcular(string $año_mes){
    DB::table('canon_agrupamiento')
    ->where('año_mes',$año_mes)
    ->delete();
    
    $this->inicializar_agrupamientos($año_mes);
    
    $niveles = DB::table('canon_subcanon_a_grupo')
    ->select('nivel')->distinct()
    ->orderBy('nivel','asc')
    ->get()
    ->pluck('nivel')->values();
    foreach($niveles as $n){
      if($n == 0){
        $this->agrupar_base($año_mes);
      }
      else{
        $this->agrupar_superior($año_mes,$n);
      }
    }
    
    $this->agrupar_sin_casino($año_mes);
    $this->calcular_columnas_dependientes($año_mes);
  }
  
  public function guardarAgrupamientos(array $agrupamientos){
    $to_insert = [];
    
    foreach($agrupamientos as $clave => $base_superiores){
      $base = $base_superiores['base'] ?? [];
      $superiores = $base_superiores['superiores'] ?? [];
      
      $nivel = 0;
      foreach($base as $valor => $id_casino_subcanons){
        foreach($id_casino_subcanons as $id_casino => $subcanon_tipos){
          foreach($subcanon_tipos as $base_subcanon_o_superior_dependencia => $tipos){            
            foreach($tipos as $base_tipo){
              $i = compact('nivel','id_casino','clave','valor','base_subcanon_o_superior_dependencia','base_tipo');
              $to_insert[implode('|',$i)] = $i;
            }
          }
        }
      }
      
      foreach($superiores as $nidx => $valores_id_casinos){
        $nivel = $nidx + 1;
        $base_tipo = '';
        
        foreach($valores_id_casinos as $valor => $id_casinos_dependencias){
          foreach($id_casinos_dependencias as $id_casino => $dependencias){
            foreach($dependencias as $base_subcanon_o_superior_dependencia){
              $i = compact('nivel','id_casino','clave','valor','base_subcanon_o_superior_dependencia','base_tipo');
              $to_insert[implode('|',$i)] = $i;
            }
          }
        }
      }
    }
    
    DB::table('canon_subcanon_a_grupo')
    ->insert(array_values($to_insert));
  }
  
  public function obtener($año_mes,$id_casino,$nivel,$clave,$valor){
    $data = DB::table('canon_agrupamiento')
    ->where('año_mes',$año_mes)
    ->where('clave',$clave)
    ->where('nivel',$nivel)
    ->where('valor',$valor);
    
    if($id_casino === null){
      $data = $data->whereNull('id_casino');
    }
    else{
      $data = $data->where('id_casino',$id_casino);
    }
    
    $data = $data->first();
    
    if($data === null) return null;
    
    unset($data->id_casino);
    unset($data->valor);
    unset($data->año_mes);
    unset($data->clave);
    unset($data->nivel);
    unset($data->hash);
    unset($data->id_canon_agrupamiento);
    
    return $data;
  }
  
  private function __agrupamientos_detallados_expandir_base($id_casinos,$scs,$base){
    $ret = [];
    foreach($base as $v => $cas_groups){
      $ret[$v] = [];
      
      if(array_key_exists('*',$cas_groups)){
        foreach($id_casinos as $idc){//Todos los casinos
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
        if(array_key_exists('*',$sc_groups)){//Todos los subcanons
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
  
  private function __agrupamientos_detallados_expandir_superiores($id_casinos,$valores_nivel_anterior,$superiores){
    $ret = [];
    foreach($superiores as $nidx => $nivel_vals){
      $ret[$nidx] = [];
      
      foreach($nivel_vals as $v => $cas_groups){
        $ret[$nidx][$v] = [];
        if(array_key_exists('*',$cas_groups)){
          foreach($id_casinos as $idc){//Todos los casinos
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
    $id_casinos = DB::table('canon')
    ->select('id_casino')->distinct()
    ->get()->pluck('id_casino')->toArray();
    
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
    
    $agrupamientos_detallados = [];//Los agrupamientos detallados por casino etc... sin los *...
    foreach($agrupamientos as $clave => $base_superiores){
      $agrupamientos_detallados[$clave] = [];
      $agrupamientos_detallados[$clave]['base'] = $this->__agrupamientos_detallados_expandir_base(
        $id_casinos,
        $scs,
        $base_superiores['base'] ?? []
      );
      
      $agrupamientos_detallados[$clave]['superiores'] = $this->__agrupamientos_detallados_expandir_superiores(
        $id_casinos,
        array_keys($base_superiores['base']),
        $base_superiores['superiores'] ?? []
      );
    }
    
    return $agrupamientos_detallados;
  }
  
  public function recalcular_agrupamiento(string $año_mes){
    return $this->recalcular_agrupamientos([$año_mes],false);
  }
  
  public function agrupamientos_detallados_req(){//Para debugear desde el navegador
    return $this->agrupamientos_detallados(
      $this->CVD->get('agrupamientos') ?? []
    );
  }
  
  public function recalcular_agrupamientos_req(){
    return DB::transaction(function(){
      $año_meses = DB::table('canon')
      ->select('año_mes')->distinct()
      ->whereNull('deleted_at')
      ->get()->pluck('año_mes')->toArray();
      $this->down();
      $this->up();
      $agrupamientos_detallados = $this->agrupamientos_detallados($this->CVD->get('agrupamientos') ?? []);
      $this->guardarAgrupamientos($agrupamientos_detallados);
      return $this->recalcular_agrupamientos($año_meses,true);
    });
  }
  
  public function recalcular_agrupamientos(array $año_meses,bool $stream_progress){
    if($stream_progress){
      return response()->stream(function() use ($año_meses){        
        foreach($año_meses as $idx => $am){
          $this->recalcular($am);
          echo '<p>'.($am.' | '.round(($idx+1.0)/count($año_meses)*100,2).'%').'</p>';
          ob_flush();
          flush();
        }
      }, 200, [
        'X-Accel-Buffering' => 'no', // Prevents Nginx from buffering the output
      ]);
    }
    else{
      foreach($año_meses as $idx => $am){
        $this->recalcular($am);
      }
      return $año_meses;
    }
  }
}
