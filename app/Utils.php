<?php
namespace App;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class Utils{
  public static function tablaDates(string $incluye_fecha = null){//Argumento es la fecha maxima necesaria (puede crear mas para adelante ya que crea todo el año por lo menos)
    if(is_null($incluye_fecha)){ $incluye_fecha = date('Y-m-d'); }
    //Constantes
    $MIN_YEAR = 1970;
    $TABLENAME = 'dates';
    
    {//Validacion de formato
      $datetime = \DateTime::createFromFormat('Y-m-d', $incluye_fecha);
      if($datetime === false || $datetime->format('Y-m-d') != $incluye_fecha) 
        throw new \InvalidArgumentException("Esperaba una fecha valida en formato 'yyyy-mm-dd'. Recibio '$incluye_fecha'");
    }
    $Y = intval(explode('-',$incluye_fecha)[0]);
    if($Y < $MIN_YEAR)//Validacion de rango
      throw new \RangeException("El año minimo es $MIN_YEAR. Recibio '$incluye_fecha'");
    
    //@HACK: crear una migración -- Octavio 2022-09-21
    //Crea una tabla con todos los dias desdes el 1970-01-01 hasta hoy+100 años (se amortiza cada 100 años... y no tarda mas de 3 segundos en recrearla)
    //Permite hacer joins para verificar que procedimientos se hayan corrido todos los dias de forma rapida
    //Cuando se haga un JOIN con otra tabla. Asegurar que esta tenga los indices correspondientes (generalmente indices multicolumnas de fecha, id_casino y tal vez id_tipo_moneda)
    //Tiene un hard limit hasta la fecha 9999-12-31... extensible agregando otro join en N_thousands y modificando el preg_match arriba
    $pdo = DB::connection()->getPdo();
    $pdo->exec("DROP PROCEDURE IF EXISTS create_table_{$TABLENAME}");
    $pdo->exec("CREATE PROCEDURE create_table_{$TABLENAME}(IN y_begin INT,IN y_end INT)
    BEGIN
      START TRANSACTION;
      DROP TABLE IF EXISTS create_table_{$TABLENAME}_N_ones;
      CREATE TABLE create_table_{$TABLENAME}_N_ones (n INT PRIMARY KEY) ENGINE=memory;
      INSERT INTO create_table_{$TABLENAME}_N_ones(n) VALUES (0),(1),(2),(3),(4),(5),(6),(7),(8),(9);
      
      DROP TABLE IF EXISTS create_table_{$TABLENAME}_N_thousands;
      CREATE TABLE create_table_{$TABLENAME}_N_thousands (n INT PRIMARY KEY) ENGINE=memory AS
      SELECT thousands.n*1000+hundreds.n*100+tens.n*10+ones.n as n
      FROM create_table_{$TABLENAME}_N_ones ones,create_table_{$TABLENAME}_N_ones tens,create_table_{$TABLENAME}_N_ones hundreds,create_table_{$TABLENAME}_N_ones thousands
      ORDER BY thousands.n ASC,hundreds.n ASC,tens.n ASC,ones.n ASC;
      DROP TABLE IF EXISTS create_table_{$TABLENAME}_N_ones;

      DROP TABLE IF EXISTS {$TABLENAME};
      CREATE TABLE {$TABLENAME}(
        date DATE primary key
      );

      INSERT IGNORE INTO {$TABLENAME}
      SELECT DATE(CONCAT(years.n,'-',months.n,'-',days.n))
      FROM create_table_{$TABLENAME}_N_thousands as years,create_table_{$TABLENAME}_N_thousands as months,create_table_{$TABLENAME}_N_thousands as days
      WHERE years.n  >= y_begin AND years.n  < y_end
      AND   months.n >= 1 AND months.n <= 12
      AND   days.n   >= 1 AND days.n   <= 31
      AND  DATE(CONCAT(years.n,'-',months.n,'-',days.n)) IS NOT NULL
      ORDER BY years.n ASC,months.n ASC,days.n ASC;
      DROP TABLE IF EXISTS create_table_{$TABLENAME}_N_thousands;
      
      COMMIT;
    END");
    $recrear_tabla = !Schema::hasTable($TABLENAME);
    if(!$recrear_tabla){
      $max_year_db = DB::table($TABLENAME)->selectRaw('MAX(YEAR(date)) as y')
      ->groupBy(DB::raw('"constant"'))->first();
      $recrear_tabla = is_null($max_year_db) || $max_year_db->y <= $Y;
    } 
    if($recrear_tabla){
      $max_year = $Y+100;
      $pdo->exec("CALL create_table_{$TABLENAME}({$MIN_YEAR},{$max_year});");
    }
    return $TABLENAME;
  }
}
