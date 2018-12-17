<?php

namespace App\Http\Controllers;

use App\Cotizacion;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use DateTime;

class CotizacionController extends Controller
{
    public static function getInstancia() {
        if (!isset(self::$instance)) {
          self::$instance = new CotizacionController();
        }
        return self::$instance;
      }


    public function obtenerCotizaciones($mes)
    {



        return DB::table('cotizacion')->get();

    }

    
}
