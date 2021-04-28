<?php

namespace App;

use DateTime;
use Illuminate\Support\Facades\Storage;
use View;
use Dompdf\Dompdf;
use PDF;


class PdfParalelo{
    private static function fingerprint(){//Deberia ser unico cada vez que se llama a menos que llame mas de una vez por decisegundo..
        $user = session('id_usuario');
        $path = sha1(request()->path());
        
        $time = microtime(true);
        $micro_time = sprintf("%06d",($time - floor($time)) * 1000000);
        $date = new DateTime(date('Y-m-d H:i:s',$time));
        $timestamp = $date->format('Y-m-d\TH:i:s').'.'.$micro_time[0];//Trunco el primer digito

        return implode('|',[$user,$path,$timestamp]);
    }
    
    
    private static function borrarArchivos($files){
        foreach($files as $f){
            if(Storage::exists($f)) Storage::delete($f);
        }
    }

    public static function generarPdf($planilla,$compacts,$codigo,$paginas_por_pdf,$paginas_totales){
        $fingerprint = self::fingerprint();
        foreach($compacts as $idx => $compact){
            $filename = $fingerprint."-".$idx.".pdf";
            $files[] = $filename;
            dispatch(new \App\Jobs\CrearPDF(
              $planilla, $compact              , $filename,
              $codigo  , $idx*$paginas_por_pdf , $paginas_totales
            ));
        }
        //Sincronizar con un spinlock hasta que esten creados todos los archivos
        {
            $max_seconds = 120;
            $elapsed_seconds = 0;
            $sleep_seconds = 5;
            while($elapsed_seconds < $max_seconds){
                sleep($sleep_seconds);
                $elapsed_seconds+=$sleep_seconds;
                $allFiles = true;
                foreach($files as $f){
                    $allFiles = $allFiles & Storage::exists($f);
                }
                if($allFiles) break;
            }
            if($elapsed_seconds >= $max_seconds){
                self::borrarArchivos($files);
                return ['error' => -1,'value' => ['Error de timeout al crear el archivo']];
            }
        }
        //Paso a path absoluto los inputs y el output
        $nfiles = array_map(function($f){return Storage::getAdapter()->applyPathPrefix($f);},$files);
        $input_files_list = '"'.implode('" "',$nfiles).'"';
        $output_file = Storage::getAdapter()->applyPathPrefix($fingerprint.'.pdf');
        $command = 'pdfunite '.$input_files_list.' "'.$output_file.'" 2>&1';
        $output = [];
        $rtrn = 0;
        exec($command,$output,$rtrn);
        self::borrarArchivos($files);
        //Si hubo error value es la salida, si exitoso devuelvo el path al archivo
        return ['error' => $rtrn, 'value' => (count($output) != 0 || $rtrn != 0)? $output : $output_file];
    }
}
