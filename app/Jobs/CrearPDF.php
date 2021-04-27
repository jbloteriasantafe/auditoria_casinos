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
    public $codigo = null;
    public $pagina = null;
    public $pagina_offset = null;
    public $paginas = null;
    public $filename = null;
    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(string $planilla,array $compct, string $codigo,
                                int $pagina,int $pagina_offset,int $paginas, string $filename)
    {
        $this->planilla = $planilla;
        $this->compct = $compct;
        $this->codigo = $codigo;
        $this->pagina = $pagina;
        $this->pagina_offset = $pagina_offset;
        $this->paginas = $paginas;
        $this->filename = $filename;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle(){
        $view = View::make($this->planilla, $this->compct);
        $dompdf = new Dompdf();
        $dompdf->set_base_path(public_path());
        $dompdf->set_paper('A4', 'portrait');
        $dompdf->loadHtml($view->render());
        $dompdf->render();
        $font = $dompdf->getFontMetrics()->get_font("helvetica", "regular");
        $dompdf->getCanvas()->page_text(20, 815, $this->codigo, $font, 10, array(0,0,0));
        $dompdf->getCanvas()->page_text(515, 815, "Página ".$this->pagina." de ".$this->paginas, $font, 10, array(0,0,0));
        Storage::put($this->filename,$dompdf->output());
    }
}
