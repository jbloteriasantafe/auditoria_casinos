<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Facades\Storage;

use View;
use Dompdf\Dompdf;
use PDF;

class CrearPDF implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $planilla = null;
    public $compct = null;
    public $view = null;
    public $filename = null;
    public $codigo = null;
    public $pag_offset = null;
    public $pags = null;

    public function __construct(string $planilla,array $compct, string $filename,
                                string $codigo,int $pag_offset,int $pags){
        $this->planilla = $planilla;
        $this->compct = $compct;
        $this->filename = $filename;
        $this->codigo = $codigo;
        $this->pag_offset = $pag_offset;
        $this->pags = $pags;
    }

    // Si cambias el Job hay que restartear para eliminar el cache con
    // sudo supervisorctl restart all
    // Los que estan ejecutandose estan en la tabla jobs
    // Los que fallaron dejan un log en failed_jobs
    // Si cambias el /etc/supervisor/conf.d/laravel-worker.conf 
    // sudo supervisorctl reread
    // sudo supervisorctl update
    // sudo supervisorctl restart all
    
    //NOTA: NO DEBERIA HABER COSAS EN LA TABLA jobs, eso indica que se quedaron
    //colgados por algun motivo, por ejemplo hoy fue porque los permisos 
    //de storage/logs/laravel.log no permitian escritura 
    //(me di cuenta leyendo worker.log)
    //La solucion: truncar la tabla y reiniciar los workers como mostre 
    //anteriormente. Tambien acordarse de borrar los pdfs storage/app
    //Lamentablemente, no encuentro forma de hacerlo mas robusto 
    //sacando reemplazar el sistema por un sistema de pool de hilos
    //o alguna libreria de async - Octavio 6 de Diciembre del 2022 
    public function handle(){
        $view = View::make($this->planilla, $this->compct);
        $dompdf = new Dompdf();
        $dompdf->set_option("isPhpEnabled", true);
        $dompdf->set_base_path(public_path());
        $dompdf->set_option('chroot',public_path());
        $dompdf->set_paper('A4', 'portrait');
        $dompdf->loadHtml($view->render());
        $dompdf->render();

        $script = sprintf('
            $p = $PAGE_NUM + %d;
            $font = $fontMetrics->getFont("helvetica", "regular");
            $pdf->text(20,815,"%s",$font,10,array(0,0,0));
            $pdf->text(500, 815,"Página ".$p." de %s", $font, 10, array(0,0,0));
        ',$this->pag_offset,$this->codigo,$this->pags);
        $dompdf->getCanvas()->page_script($script);
        Storage::put($this->filename,$dompdf->output());
    }
}
