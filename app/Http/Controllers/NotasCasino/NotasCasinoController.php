<?php

namespace App\Http\Controllers\NotasCasino;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Exception;

class NotasCasinoController extends Controller
{
    public function index(){
        try{
            $categorias = DB::connection('gestion_notas_mysql')
            ->table('categorias')
            ->get();
            $tipos_evento = DB::connection('gestion_notas_mysql')
            ->table('tipo_eventos')
            ->get();
            $meses = array_map(function($item){
                return (object) $item;
            }, [
                ['id' => 1, 'mes' => 'Enero'],
                ['id' => 2, 'mes' => 'Febrero'],
                ['id' => 3, 'mes' => 'Marzo'],
                ['id' => 4, 'mes' => 'Abril'],
                ['id' => 5, 'mes' => 'Mayo'],
                ['id' => 6, 'mes' => 'Junio'],
                ['id' => 7, 'mes' => 'Julio'],
                ['id' => 8, 'mes' => 'Agosto'],
                ['id' => 9, 'mes' => 'Septiembre'],
                ['id' => 10, 'mes' => 'Octubre'],
                ['id' => 11, 'mes' => 'Noviembre'],
                ['id' => 12, 'mes' => 'Diciembre'],
            ]);

            $tipos_nota = array_map(function($item){
                return (object) $item;
            }, [
                ['id_tipo_nota'=>'1','tipo_nombre' => 'Comun'],
                ['id_tipo_nota'=>'2','tipo_nombre' => 'Publicidad (Bis)'],
                ['id_tipo_nota'=>'3','tipo_nombre' => 'Marketing (MKT)'],
                ['id_tipo_nota'=>'4','tipo_nombre' => 'Poker (PK)'],
            ]);

            $anio = date('Y');
        }catch(Exception $e){
            Log::error('Error al obtener los datos: '.$e->getMessage());

            $categorias = array_map(function($item){
                return (object) $item;
            }, [
                ['idcategoria' => 1, 'categoria' => 'Diseño'], 
                ['idcategoria' => 2, 'categoria' => 'Pautas'], 
                ['idcategoria' => 3, 'categoria' => 'Contratos'],
                ['idcategoria' => 4, 'categoria' => 'Torneo'],
                ['idcategoria' => 5, 'categoria' => 'Torneo + Gráficas']
            ]);

            $tipos_evento = array_map(function($item){
                return (object) $item;
            }, [
                ['idtipoevento'=>1,'tipo_nombre'=>'Activaciones'],
                ['idtipoevento'=>2,'tipo_nombre'=>'Medios Masivos/Tradicionales'],
                ['idtipoevento'=>3,'tipo_nombre'=>'Medios Digitales'],
                ['idtipoevento'=>4,'tipo_nombre'=>'Promociones'],
                ['idtipoevento'=>5,'tipo_nombre'=>'Torneos'],
                ['idtipoevento'=>6,'tipo_nombre'=>'Via Publica'],
                ['idtipoevento'=>7,'tipo_nombre'=>'Contratos'],
            ]);
            $meses = array_map(function($item){
                return (object) $item;
            }, [
                ['id' => 1, 'mes' => 'Enero'],
                ['id' => 2, 'mes' => 'Febrero'],
                ['id' => 3, 'mes' => 'Marzo'],
                ['id' => 4, 'mes' => 'Abril'],
                ['id' => 5, 'mes' => 'Mayo'],
                ['id' => 6, 'mes' => 'Junio'],
                ['id' => 7, 'mes' => 'Julio'],
                ['id' => 8, 'mes' => 'Agosto'],
                ['id' => 9, 'mes' => 'Septiembre'],
                ['id' => 10, 'mes' => 'Octubre'],
                ['id' => 11, 'mes' => 'Noviembre'],
                ['id' => 12, 'mes' => 'Diciembre'],
            ]);
            $tipos_nota = array_map(function($item){
                return (object) $item;
            }, [
                ['id_tipo_nota'=>'1','tipo_nombre' => 'Comun'],
                ['id_tipo_nota'=>'2','tipo_nombre' => 'Publicidad (Bis)'],
                ['id_tipo_nota'=>'3','tipo_nombre' => 'Marketing (MKT)'],
                ['id_tipo_nota'=>'4','tipo_nombre' => 'Poker (PK)'],
            ]);
            $anio = date('Y');
        }
        return view('NotasCasino.indexNotasCasino',
         compact('categorias', 'tipos_evento', 'meses','tipos_nota', 'anio'));
    }


}