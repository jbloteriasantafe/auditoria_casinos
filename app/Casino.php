<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use App\Observers\CasinoObserver;

class Casino extends Model
{
  protected $connection = 'mysql';
  protected $table = 'casino';
  protected $primaryKey = 'id_casino';
  protected $visible = array('id_casino','nombre','codigo','fecha_inicio','porcentaje_sorteo_mesas','minimo_relevamiento_progresivo');
  public $timestamps = false;

  public function turnos(){
    return $this->hasMany('App\Turno','id_casino','id_casino');
  }
  public function unidades_medida(){
    return $this->belongsToMany('App\UnidadMedida','casino_tiene_unidad_medida','id_casino','id_unidad_medida');
  }
  public function usuarios(){
    return $this->belongsToMany('App\Usuario','usuario_tiene_casino','id_casino','id_usuario');
  }
  public function expedientes(){
    return $this->belongsToMany('App\Expediente','expediente_tiene_casino','id_casino','id_expediente');
  }
  public function logs_movimientos(){
    return $this->hasMany('App\LogMovimiento','id_casino','id_casino');
  }
  public function eventualidades(){
    return $this->hasMany('App\Eventualidad','id_casino','id_casino');
  }
  public function eventos(){
    return $this->hasMany('App\Evento','id_casino','id_casino');
  }
  public function notas(){
    return $this->hasMany('App\Nota','id_casino','id_casino');
  }
  public function gliSofts(){
    return $this->belongsToMany('App\GliSoft', 'casino_tiene_gli_soft', 'id_casino' , 'id_gli_soft');
  }
  public function gliHards(){
    return $this->belongsToMany('App\GliHard', 'casino_tiene_gli_hard', 'id_casino' , 'id_gli_hard');
  }
  public function sectores(){
    return $this->HasMany('App\Sector','id_casino','id_casino');
  }
  public function sectores_mesas(){
    return $this->HasMany('App\Mesas\SectorMesas','id_casino','id_casino');
  }
  public function islas(){
    return $this->hasMany('App\Isla','id_casino','id_casino');
  }
  public function beneficios(){
    return $this->HasMany('App\Beneficio','id_casino','id_casino');
  }
  public function producidos(){
    return $this->HasMany('App\Producido','id_casino','id_casino');
  }
  public function contadores_horarios(){
    return $this->HasMany('App\ContadorHorario','id_casino','id_casino');
  }

  public function juegos(){
    return $this->belongsToMany('App\Juego','casino_tiene_juego','id_casino','id_juego');
  }

  public function mesas(){
    return $this->hasMany('App\Mesas\Mesa','id_casino','id_casino');
  }

  public function meses(){
    $generar_fila = function($fecha_inicio,$fecha_fin,$id_casino,$nro_cuota){
      $meses = ['Enero','Febrero','Marzo','Abril','Mayo','Junio','Julio','Agosto','Septiembre','Octubre','Noviembre','Diciembre'];
      $dia_inicio = intval($fecha_inicio->format('d'));
      $nro_mes    = intval($fecha_fin->format('m'));
      $dia_fin    = intval($fecha_fin->format('d'));
      $nombre_mes = $meses[$nro_mes-1];

      $fin_de_mes = intval((clone $fecha_inicio)->modify('last day of')->format('d'));
      if($dia_inicio != 1 || $dia_fin != $fin_de_mes){//Si esta incompleto el mes
        $nombre_mes .= ' ' . ($dia_inicio<10? '0' : '') .$dia_inicio . ' al ' . ($dia_fin<10? '0' : '') . $dia_fin;
      }

      return (object)[
        'nombre_mes' => $nombre_mes,
        'nro_mes' => $nro_mes,
        'nro_cuota' => $nro_cuota,
        'dia_inicio' => $dia_inicio,
        'dia_fin' => $dia_fin,
        'id_casino' => $id_casino
      ];
    };

    $fecha = new \DateTime($this->fecha_inicio);
    $retorno = [];
    for($nro_cuota = 1;$nro_cuota < 13;$nro_cuota++){
      $retorno[] = $generar_fila($fecha,
                                  (clone $fecha)->modify('last day of'),
                                  $this->id_casino,
                                  $nro_cuota);
      $fecha->modify('+1 month')->modify('first day of');
    }

    $fecha = new \DateTime($this->fecha_inicio);
    //Si empezo iniciado el mes se le agrega una cuota mas con los dias que le faltaron
    if($fecha->format('d') != '1'){
      $retorno[] = $generar_fila((clone $fecha)->modify('first day of'),
                                  (clone $fecha)->modify('-1 day'),
                                  $this->id_casino,
                                  13);
    }
  
    return $retorno;
  }

  public function detalles_informe_final_mesas(){
    return $this->hasMany('App\Mesas\DetalleInformeFinalMesas','id_casino','id_casino');
  }

  public function fichas(){
    return $this->hasMany('App\Mesas\FichaTieneCasino','id_casino','id_casino');
  }

  public function progresivos(){
    return $this->hasMany('App\Progresivo','id_casino','id_casino');
  }

  public function maquinas(){
    return $this->hasMany('App\Maquina','id_casino','id_casino');
  }

  public function sesionesBingo(){
    return $this->hasMany('App\Bingo\SesionBingo','id_casino','id_casino');
  }

  public function importancionesBingo(){
    return $this->hasMany('App\Bingo\SesionBingo','id_casino','id_casino');
  }

  public function estadosAE(){
    return $this->hasMany('App\Autoexclusion\EstadoAE','id_casino','id_casino');
  }
  
  public static function boot(){
        parent::boot();
        Casino::observe(new CasinoObserver());
  }

  public function getTableName(){
    return $this->table;
  }

  public function getId(){
    return $this->id_casino;
  }

}
