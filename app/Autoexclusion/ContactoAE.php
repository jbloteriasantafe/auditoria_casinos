<?php
namespace App\Autoexclusion;

use Illuminate\Database\Eloquent\Model;

class ContactoAE extends Model
{
  protected $connection = 'mysql';
  protected $table = 'ae_datos_contacto';
  protected $primaryKey = 'id_datos_contacto';
  protected $visible = array('id_datos_contacto','nombre_apellido',
                              'nombre_localidad','nombre_provincia',
                              'telefono', 'vinculo',
                              'domicilio',   'id_autoexcluido',
                              );
  protected $fillable = ['nombre_apellido',
                              'nombre_localidad','nombre_provincia',
                              'telefono', 'vinculo',
                              'domicilio',   'id_autoexcluido'];

  public $timestamps = false;

  public function ae(){
    return $this->belongsTo('App\Autoexclusion\Autoexcluido','id_autoexcluido','id_autoexcluido');
  }
}
