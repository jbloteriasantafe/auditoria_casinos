<?php

namespace App\Http\Controllers;

use App\Cotizacion;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use DateTime;
use Validator;

class CotizacionController extends Controller
{
    public static function getInstancia() {
        if (!isset(self::$instance)) {
          self::$instance = new CotizacionController();
        }
        return self::$instance;
      }


    public function obtenerCotizaciones($fecha){
        
        return DB::table('cotizacion')
                    ->where('fecha','>=',$fecha . '%')
                    ->get();
        //TODO limitar la busqueda con fecha final
    }

    public function guardarCotizacion(request $cotizacion){
        $validator = Validator::make($cotizacion->all(), [
            'fecha' => 'required|date',
            'valor' => 'required|numeric|min:25',
        ])->validate();

        $cot=Cotizacion::Find($cotizacion['fecha']);
        if($cot){
            $cot->valor=$cotizacion['valor'];
            $cot->save();
        }else{
            $nuevaCotizacion= new Cotizacion;
            $nuevaCotizacion->fecha=$cotizacion['fecha'];
            $nuevaCotizacion->valor=$cotizacion['valor'];
            $nuevaCotizacion->save();
        }



        
        return "OK";

    }
    
}
