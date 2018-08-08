<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\TipoProgresivo;

class TipoProgresivoController extends Controller
{
  private static $instance;

  public static function getInstancia() {
    if (!isset(self::$instance)) {
      self::$instance = new TipoProgresivoController();
    }
    return self::$instance;
  }

  public function getAll(){
    $todos = TipoProgresivo::all();
    return $todos;
  }
  
}
