<?php

namespace App\Http\Controllers\Bingo;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Http\Controllers\UsuarioController;
use Illuminate\Support\Facades\DB;


use App\Bingo\DetalleSesionBingo;

class DetallesSesionController extends Controller
{

    public function eliminarDetalle($id){
      $detalle=DetalleSesionBingo::findorfail($id);

      $detalle->delete();

      return ['detalle' => $detalle];
    }
}
