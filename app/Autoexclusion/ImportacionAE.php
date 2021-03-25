<?php
namespace App\Autoexclusion;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ImportacionAE extends Model
{
  use SoftDeletes;

  protected $connection = 'mysql';
  protected $table = 'ae_importacion';
  protected $primaryKey = 'id_importacion';
  protected $visible = array('id_importacion','id_autoexcluido',
                              'foto1', 'foto2',
                              'solicitud_ae', 'solicitud_revocacion',
                              'scandni','caratula'
                              );

  protected $fillable = ['id_importacion','id_autoexcluido',
                          'foto1', 'foto2',
                          'solicitud_ae', 'solicitud_revocacion',
                          'scandni','caratula'
                        ];

  public function ae(){
    return $this->belongsTo('App\Autoexclusion\Autoexcluido','id_autoexcluido','id_autoexcluido');
  }
}
