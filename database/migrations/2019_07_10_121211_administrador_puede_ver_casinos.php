<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AdministradorPuedeVerCasinos extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
      DB::table('rol_tiene_permiso')->insert(
          array('id_rol'=>2 ,'id_permiso'=>6)
      );
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
      $res = DB::table('rol_tiene_permiso')->select('*')->
      where(['id_rol','=',2],
      ['id_permiso','=',6])
      ->get();
      $res->destroy();
    }
}
