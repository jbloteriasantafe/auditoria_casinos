<?php

namespace App;

use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Observers\UsuarioObserver;

class Usuario extends Model
{
    use Notifiable;
    use SoftDeletes;
    protected $connection = 'mysql';//correo.santafe.gov.ar:587
    protected $table = 'usuario';
    public $timestamps = false;
    protected $primaryKey = 'id_usuario';
    protected $visible = array('id_usuario','user_name','nombre','email', 'dni' ,'ultimos_visitados');
    protected $hidden = array('imagen','password','token');
    protected $appends = array('es_superusuario','es_controlador','elimina_cya','es_administrador','es_fiscalizador','es_control');

    //en cierres y aperturas de mesas
    public function getEliminaCyaAttribute(){
      $roles = $this->belongsToMany('App\Rol','usuario_tiene_rol','id_usuario','id_rol')->get();
      foreach ($roles as $rol) {
        foreach ($rol->permisos as $p) {
          if($p->descripcion == 'm_eliminar_cierres_y_aperturas'){
            return true;
          }
        }
      }
      return false;
    }

    public function getEsSuperusuarioAttribute(){
      return (count($this->belongsToMany('App\Rol','usuario_tiene_rol','id_usuario','id_rol')->where('rol.id_rol','=',1)->get()) > 0);
    }

    public function getEsAdministradorAttribute(){
      return (count($this->belongsToMany('App\Rol','usuario_tiene_rol','id_usuario','id_rol')->where('rol.id_rol','=',2)->get()) > 0);
    }

    public function getEsControlAttribute(){
      return (count($this->belongsToMany('App\Rol','usuario_tiene_rol','id_usuario','id_rol')->where('rol.id_rol','=',4)->get()) > 0);
    }

    public function getEsControladorAttribute(){
      return $this->es_administrador || $this->es_superusuario || $this->es_control;
    }

    public function getEsFiscalizadorAttribute(){
      return (count($this->belongsToMany('App\Rol','usuario_tiene_rol','id_usuario','id_rol')->where('rol.id_rol','=',3)->get()) > 0);
    }

    public function relevamientos_apuestas(){
      return $this->belongsToMany('App\Mesas\RelevamientoApuestas','fiscalizador_relevo_apuesta','id_usuario','id_relevamiento_apuestas_mesas');
    }

    public function roles(){
	     return $this->belongsToMany('App\Rol','usuario_tiene_rol','id_usuario','id_rol');
    }

    public function logs_movimientos(){
	     return $this->belongsToMany('App\LogMovimiento','controlador_movimiento','id_controlador_movimiento','id_log_movimiento');
    }

    public function eventualidades(){
       return $this->belongsToMany('App\Eventualidad','fisca_tiene_eventualidad','id_fiscalizador','id_eventualidad');
    }
    public function casinos(){
      return $this->belongsToMany('App\Casino','usuario_tiene_casino','id_usuario','id_casino');
    }
    public function logs(){
      return $this->hasMany('App\Log','id_usuario','id_usuario');
    }
    public function relevamientos_cargados(){
      return $this->hasMany('App\Relevamiento','id_usuario_cargador','id_usuario');
    }
    public function relevamientos_fiscalizados(){
      return $this->hasMany('App\Relevamiento','id_usuario_fiscalizador','id_usuario');
    }
    public function relevamientos_progresivos_cargados(){
      return $this->hasMany('App\RelevamientoProgresivo','id_usuario_cargador','id_usuario');
    }
    public function relevamientos_progresivos_fiscalizados(){
      return $this->hasMany('App\RelevamientoProgresivo','id_usuario_fiscalizador','id_usuario');
    }
    public function movimientos_fiscalizados(){
      return $this->hasMany('App\FiscalizacionMov','id_fiscalizador','id_usuario');
    }
    public function movimientos_cargados(){
      return $this->hasMany('App\FiscalizacionMov','id_cargador','id_usuario');
    }
    public function relevamientos_fiscalizados_movimientos(){
      return $this->hasMany('App\RelevamientoMovimiento','id_fiscalizador','id_usuario');
    }
    public function relevamientos_cargados_movimientos(){
      return $this->hasMany('App\RelevamientoMovimiento','id_cargador','id_usuario');
    }

    public function secciones_recientes(){
      return $this->hasMany('App\SecRecientes','id_usuario','id_usuario')->orderBy('orden','asc');
    }

    public static function boot(){
          parent::boot();
          Usuario::observe(new UsuarioObserver());
    }

    // public function agregarRuta($string){
    //   $visitados = $this->ultimos_visitados;
    //   $vistas = explode(";" , $visitados);
    //   $this->ultimos_visitados = $string . ";" . $vistas[0] . ";" . $vistas[1] . ";" . $vistas[2] ;
    // }

    //si el usuario forma parte del casino $id_casino devuelve verdadero
    public function usuarioTieneCasino($id_casino){
      $bandera=false;
      foreach($this->casinos as $casino){
          if($casino->id_casino == $id_casino){
              $bandera=true;
          }
      }
      return $bandera;
    }

    //notificaciones
    public function routeNotificationForMail()
    {
        return $this->email;
    }

    public function lastNotifications()
    {
      return $this->notifications()
                      ->orderBy('id', 'desc')->take(10)->get();
    }

    public function getTableName(){
      return $this->table;
    }

    public function getId(){
      return $this->id_usuario;
    }

}
