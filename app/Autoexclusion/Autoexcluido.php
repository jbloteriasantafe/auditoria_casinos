<?php

namespace App\Autoexclusion;

use Illuminate\Database\Eloquent\Model;

class Autoexcluido extends Model
{
  protected $connection = 'mysql';
  protected $table = 'ae_datos';
  protected $primaryKey = 'id_autoexcluido';
  protected $visible = array('id_autoexcluido','apellido','nombres',
                              'nombre_localidad','nombre_provincia','nro_domicilio',
                              'nro_dni', 'telefono', 'correo',
                              'domicilio', 'id_sexo','fecha_nacimiento',
                              'id_ocupacion','id_estado_civil','id_capacitacion'
                              );
  protected $fillable = ['apellido','nombres',
                              'nombre_localidad','nombre_provincia','nro_domicilio',
                              'nro_dni', 'telefono', 'correo',
                              'domicilio', 'id_sexo',
                              'fecha_nacimiento','id_ocupacion',
                              'id_estado_civil','id_capacitacion'];

  public $timestamps = false;

  //id_provincia
  //id_localidad
  //id_sexo
  //id_ocupacion
  //id_estado_civil
  //id_capacitacion
}
