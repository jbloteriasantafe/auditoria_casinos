<?php

namespace App;

use DateTime;
use Illuminate\Support\Facades\Storage;
use View;
use Dompdf\Dompdf;
use PDF;
use GPhpThread;

class ThreadPool {
	private $active_count = 0;
	private $active       = [];
	private $waiting      = [];
	public  $done         = [];
	
	public function __construct($size){
		$this->active = array_fill(0,$size,null);
	}
	
	public function submit(GPhpThread $thread){
		if($this->active_count < count($this->active)){
			$this->active[$this->active_count] = $thread;
			$this->active_count++;
		}
		else{
			$this->waiting[] = $thread;
		}
	}
	
	public function start(){
		foreach($this->active as $t){
			if(!is_null($t))
				$t->start();
		}
	}
	
	public function join($max_seconds = 120,$sleep_seconds = 2){
		$elapsed_seconds = 0;
		while($this->active_count > 0){
			if($elapsed_seconds >= $max_seconds){
				return 'TIMEOUT';
            }
            
			foreach($this->active as $tidx => &$t){
				if(is_null($t)) continue;
				
				$t->join(false);
				if($t->getPid() !== null){//No termino
					continue;
				}
				$this->done[] = $t;
				
				$wt       = array_pop($this->waiting);
				$decrease = null;
				
				if(!is_null($wt)){
					$wt->start();
					$decrease = 0;
				}
				else{//Si es nulo, el arreglo de activos va a terminar con un hilo menos
					$decrease = 1;
				}
				
				$this->active[$tidx] = $wt;
				$this->active_count -= $decrease;				
			}
			
			sleep($sleep_seconds);
			$elapsed_seconds+=$sleep_seconds;
		}
		return 0;
	}
}

//@NOTE: cambiar a Threads nativos de PHP si alguna vez se introducen (por ahora hay que habilitar una configuración
//al momento de compilar PHP... por eso uso esta libreria)
class CrearPDF extends GPhpThread {
	public $complete   = false;
	public $rtrn       = null;
    public $planilla   = null;
    public $compct     = null;
    public $view       = null;
    public $filename   = null;
    public $codigo     = null;
    public $pag_offset = null;
    public $pags       = null;
    
    private $_criticalSection = null;
    private $_allowThreadExitCodes = false;

    public function __construct(string $planilla,array $compct, string $filename,
                                string $codigo,int $pag_offset,int $pags){
		$this->complete   = false;
        $this->planilla   = $planilla;
        $this->compct     = $compct;
        $this->filename   = $filename;
        $this->codigo     = $codigo;
        $this->pag_offset = $pag_offset;
        $this->pags       = $pags;
        
        parent::__construct($this->_criticalSection,$this->_allowThreadExitCodes);
    }

    public function run(){
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
        $this->complete = true;
    }
}



class PdfParalelo{
	private static function borrarArchivos($files){
		foreach($files as $f){
			if(Storage::exists($f)) Storage::delete($f);
		}
	}
	public static function crear($hilos,$planilla,$compacts,$codigo,$paginas_por_pdf,$paginas_totales){
		$fingerprint = null;
		{
			$user = session('id_usuario');
			$path = sha1(request()->path());

			$time = microtime(true);
			$micro_time = sprintf("%06d",($time - floor($time)) * 1000000);
			$date = new DateTime(date('Y-m-d H:i:s',$time));
			$timestamp = $date->format('Y-m-d\TH:i:s').'.'.$micro_time[0];//Trunco el primer digito

			$fingerprint = implode('|',[$user,$path,$timestamp]);
		}
		
		$pool = new ThreadPool($hilos);
		foreach($compacts as $idx => $compact){
			$filename = $fingerprint."-".$idx.".pdf";
			$pool->submit(new CrearPDF(
				$planilla,$compact,$filename,$codigo,
				$idx*$paginas_por_pdf,$paginas_totales
			));
		}
		$pool->start();
		
		
		$exit = $pool->join();
		if($exit == 'TIMEOUT'){
			self::borrarArchivos(array_map(function($t){return $t->filename;},$pool->done));
			//@TODO: limpiar hilos?
			return ['error' => -1,'value' => ['Error de timeout al crear el archivo']];
		}
		
		usort($pool->done,function($ta,$tb){
			if($ta->pag_offset < $tb->pag_offset) return -1;
			if($ta->pag_offset > $tb->pag_offset) return  1;
			return 0;
		});
				
		$files = array_map(function($t){return $t->filename;},$pool->done);
		
		foreach($files as $f){
			if(!Storage::exists($f)){//Si el archivo no se creo bien, borro todo y retorno error
				self::borrarArchivos($files);
				return ['error' => -1,'value' => ['Error de creacion de archivos']];
			}
		}
		
		//Paso a path absoluto los inputs y el output
		$nfiles = array_map(function($f){return Storage::path('').$f;},$files);
        $input_files_list = '"'.implode('" "',$nfiles).'"';
		$unite_filename = $fingerprint.".pdf";
        $output_file = Storage::path('').$unite_filename;
        
        $command = 'pdfunite '.$input_files_list.' "'.$output_file.'" 2>&1';
        $output = [];
        $rtrn = 0;
        exec($command,$output,$rtrn);
        self::borrarArchivos($files);
        //Si hubo error value es la salida, si exitoso devuelvo el path al archivo
        $hubo_error = count($output) != 0 || $rtrn != 0;
        if($hubo_error){
			self::borrarArchivos([$unite_filename]);
			return ['error' => $rtrn, 'value' => $output];
		}
        return ['error' => $rtrn, 'value' => $output_file];
	}
}
