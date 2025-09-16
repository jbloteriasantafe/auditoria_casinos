<?php

namespace App\Http\Controllers\NotasCasino;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class NotasCasinoController extends Controller
{
    public function index(){
        try{
            //! REVISAR BIEN QUE DATOS SE TRAEN Y CUALES NO
            //nro nota, nombre, adjuntos todos menos el tecnico, fecha inicio y fin, estado y nota relacionada y archivo(condicional) 
            /* $notasActuales= DB::connection('gestion_notas_mysql')
            ->table('eventos')
            ->select('nronota_ev,evento,adjunto_pautas,adjunto_diseño,adjunto_basesycond,fecha_evento,fecha_finalizacion,idestado,notas_relacionadas')
            ->get();
 */
            $categorias = DB::connection('gestion_notas_mysql')
            ->table('categorias')
            ->get();
            $tipos_evento = DB::connection('gestion_notas_mysql')
            ->table('tipo_eventos')
            ->get();

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
         compact('categorias', 'tipos_evento','tipos_nota', 'anio'));
    }

    public function subirNota (Request $request){
        //! HARCODEO EL ORIGEN PERO DESPUES DEBERIA HACER QUE SE OBTENGA EN LA FUNCION DE ARRIBA 

        $validator = Validator::make($request->all(),[
            'nroNota' => 'required|integer',
            'tipoNota' => 'required|integer',
            'anioNota' => 'required|integer',
            'nombreEvento' => 'required|string|max:1000',
            'tipoEvento' => 'required|integer',
            'categoria' => 'required|integer',
            'adjuntoPautas' => 'nullable|file|mimes:pdf,zip,rar|max:153600',
            'adjuntoDisenio' => 'nullable|file|mimes:pdf,zip,rar|max:153600',
            'basesyCondiciones' => 'nullable|file|mimes:pdf,zip,rar|max:153600',
            'fechaInicio' => 'required|date',
            'fechaFinalizacion' => 'required|date',
            'fechaReferencia' => 'required|string|max:500',
        ]);

        if($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        // Armo la nota segun su tipo
        $nroNota = $request->input('nroNota');
        $tipoNota = $request->input('tipoNota');
        $anioNota = $request->input('anioNota');
        $resto = $anioNota % 1000;
        
        $formatos = [
            1 => "{$nroNota}-{$resto}",
            2 => "{$nroNota}-{$resto} Bis",
            3 => "MKT-{$nroNota}-{$resto}",
            4 => "PK-{$nroNota}-{$resto}"
        ];

        if (isset($formatos[$tipoNota])) {
            $nota = $formatos[$tipoNota];
        } else {
            $nota = '';
        }

        //? GUARDAR ARCHIVOS EN DISCO DURO Y OBTENER SUS PATH

        // obtnego los datos de la request
        $evento = $request->input('nombreEvento');
        $tipoEvento = $request->input('tipoEvento');
        $fechaIInicio = $request->input('fechaInicio');
        $fechaFinalizacion = $request->input('fechaFinalizacion');
        $fechaReferencia = $request->input('fechaReferencia');
        $adjuntoPautas = $request->input('adjuntoPautas');
        $adjuntoDisenio = $request->input('adjuntoDisenio');
        $basesyCondiciones = $request->input('basesyCondiciones');
        $categoria = $request->input('categoria');
        $origen = 1;

        //? GUARDAR EN LA TABLA EVENTOS,FORMATO:
        /* 
            idevento, -> autoincremental  //! AGREGAR
            responsable, -> null
            nronota_ev, -> si va//! AGREGAR
            origen, -> si va//! AGREGAR -> HARCODDEADO
            evento(nombre), -> si va //! AGREGAR -> NOMBRE EVENTO
            fecha_nota_recep, -> null 
            tipo_evento, -> si va //! AGREGAR
            fecha_evento(inicio),si va //! AGREGAR
            fecha_finalizacion,si va //! AGREGAR
            estado_fecha, null
            fecha_referencia_evento,si va //! AGREGAR
            mes_referencia_evento,null
            anio,null
            adjunto_pautas, si existe va el path | NULL //! AGREGAR
            adjunto_diseño, si existe va el path | NULL //! AGREGAR
            adjunto_basesycond, si existe va el path | NULL //! AGREGAR
            adjunto_inf_tecnico, NULL y sacar del form
            idestado, ESTADO 9 CARGA INICIAL //! AGREGAR -> HARCODEADO
            idest_seg, null
            observaciones, null 
            obs_segim,null
            fecha_orden, null
            material_entrega, null
            fecha_hora_reg, null
            fecha_hora_modif, null
            notas_relacionadas, null
            id_categoria, si va //! AGREGAR
            dircarpeta, null
        */
        try {
            DB::connection('gestion_notas_mysql')->table('eventos')->insert([
                'responsable' => null,
                'nronota_ev' => $nroNota,
                'origen' => $origen,
                'evento' => $evento,
                'fecha_nota_recep' => null,
                'tipo_evento' => $tipoEvento,
                'fecha_evento' => $fechaIInicio,
                'fecha_finalizacion' => $fechaFinalizacion,
                'estado_fecha' => null,
                'fecha_referencia_evento' => $fechaReferencia,
                'mes_referencia_evento' => null,
                'anio' => null,
                'adjunto_pautas' => $adjuntoPautas,
                'adjunto_diseño' => $adjuntoDisenio,
                'adjunto_basesycond' => $basesyCondiciones,
                'adjunto_inf_tecnico' => null,
                'idestado' => 9, // CARGA INICIAL
                'idest_seg' => null,
                'observaciones' => null,
                'obs_segim' => null,
                'fecha_orden' => null,
                'material_entrega' => null,
                'fecha_hora_reg' => null,
                'fecha_hora_modif' => null,
                'notas_relacionadas' => null,
                'id_categoria' => $categoria,
                'dircarpeta' => null
            ]);
            return response()->json(['success' => true],200);
        } catch (Exception $e) {
            return response()->json(['success' => false, 'error' => $e->getMessage()],500);
        }
    }
}