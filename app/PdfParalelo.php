<?php

namespace App;

use DateTime;
use Illuminate\Support\Facades\Storage;

abstract class Command {
	private $P = null;
	private $pipes = null;
	private $pid = null;
	
	public function exec(): array{
		$php             = 'php'.PHP_MAJOR_VERSION.'.'.PHP_MINOR_VERSION;
		$project_path    = base_path();
		$console_command = $this->console_command();
		$args            = $this->args();
		$s_args          = implode(' ',$this->args());
    $final_command   = "$php $project_path/artisan $console_command $s_args";
    
    $this->P = proc_open(
			$final_command,
			[0=> ['pipe','r'],1 => ['pipe','w'],2 => ['pipe','w']],
			//Que hacer con las pipes cuando se termina el proceso o se lo mata?
			$this->pipes
		);
		
		if($this->P === false){
      dump("Error al crear el proceso $final_command");
		}
				
		$status = proc_get_status($this->P);
		$this->pid = $status['pid'];
		        
		return [
			'command'         => $final_command,
			'console_command' => $console_command,
			'args'            => $args,
			'pid'             => $this->pid,
		];
	}
	
	public function join($block = true){
		do{
			$s = proc_get_status($this->P);
			if(!$s['running']) return true;
		}while($block);
		return false;
	}
	
	public function kill(){
		if(is_null($this->P) || $this->P === false) return;
		foreach($this->pipes as $p){
			if(is_resource($p)){
				fclose($p);
			}
		}
		//No puedo manejar señales porque es de pcntl que no esta permitido bajo Apache...
		//posix_kill($this->pid, SIGTERM);//SIGKILL no permite ponerle un handler
		exec('kill -s KILL '.$this->pid);
		$tries = 0;//@HACK: espera hasta que este muerto el proceso para llamar post_kill
		$max_tries = 10;
		while(proc_get_status($this->P)['running'] && $tries < $max_tries){
			sleep(1);
			$tries += 1;
		}
		if(is_resource($this->P)){
			proc_close($this->P);
		}
		$this->post_kill();
	}
	
	abstract function console_command(): string;
	abstract function args(): array;
	abstract function post_kill();
	
	function __destruct(){
		$this->kill();
	}
}

class CommandCrearPDF extends Command {
	private $output_file = null;
	private $input_file = null;
	
	function console_command(): string{
		return 'crear-pdf';
	}
	
	function args(): array {
		$this->output_file = $this->filename;
		$this->input_file = $this->filename.'.input';
		Storage::put($this->input_file,json_encode([
			'planilla'   => $this->planilla,
			'compact'    => $this->compact,
			'filename'   => $this->filename,
			'codigo'     => $this->codigo,
			'pag_offset' => $this->pag_offset,
			'pags'       => $this->pags,
		]));
		
		$abs_input_file = Storage::getAdapter()->applyPathPrefix($this->input_file);
		return [
			"'$abs_input_file'"
		];
	}
	
	public $planilla   = null;
	public $compact    = null;
	public $filename   = null;
	public $codigo     = null;
	public $pag_offset = null;
	public $pags       = null;
	
	public function __construct(string $planilla,array $compct, string $filename,
								string $codigo,int $pag_offset,int $pags){
		$this->planilla   = $planilla;
		$this->compact    = $compct;
		$this->filename   = $filename;
		$this->codigo     = $codigo;
		$this->pag_offset = $pag_offset;
		$this->pags       = $pags;			
	}
	
	public $keep_output = false;
		
	function post_kill(){
		Storage::delete($this->input_file);
		//@TODO
		//Quedan archivos de output a guardarse encolados post timeout y no se como limpiarlos
		if(!$this->keep_output)
			Storage::delete($this->output_file);
			
		unset($this->compact);
		gc_collect_cycles();
	}
}

class CommandPool {
	private $active_count = 0;
	public  $active       = [];
	private $waiting      = [];
	public  $done         = [];
	
	public function __construct($size){
		$this->active = array_fill(0,$size,null);
	}
	
	public function submit(Command $command){
		if($this->active_count < count($this->active)){
			$this->active[$this->active_count] = $command;
			$this->active_count++;
		}
		else{
			$this->waiting[] = $command;
		}
	}
	
	public function start(){
		foreach($this->active as $c){
			if(!is_null($c))
				$c->exec();
		}
	}
	
	public function join($max_seconds = 120,$sleep_seconds = 2){
		$elapsed_seconds = 0;
		while($this->active_count > 0){
			if($elapsed_seconds >= $max_seconds){
				return 'TIMEOUT';
      }
            
			foreach($this->active as $cidx => &$c){
				if(is_null($c)) continue;
				
				$termino = $c->join(false);
				if(!$termino){
					continue;
				}
				$this->done[] = $c;
				
				$wc       = array_pop($this->waiting);
				$decrease = null;
				
				if(!is_null($wc)){
					$wc->exec();
					$decrease = 0;
				}
				else{//Si es nulo, el arreglo de activos va a terminar con un hilo menos
					$decrease = 1;
				}
				
				$this->active[$cidx] = $wc;
				$this->active_count -= $decrease;				
			}
			
			sleep($sleep_seconds);
			$elapsed_seconds+=$sleep_seconds;
		}
		return 0;
	}
	
	function __destruct(){
		unset($this->active);
		unset($this->done);
		unset($this->waiting);
		gc_collect_cycles();
	}
}

class PdfParalelo{
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
		
		$pool = new CommandPool($hilos);
		foreach($compacts as $idx => $compact){
			$filename = $fingerprint."-".$idx.".pdf";
			$pool->submit(new CommandCrearPDF(
				$planilla,$compact,$filename,$codigo,
				$idx*$paginas_por_pdf,$paginas_totales
			));
		}
		$pool->start();		
		
		$exit = $pool->join();
		if($exit !== 0){
			unset($pool);
			gc_collect_cycles();//Asegurar que se liberen todos los procesos
			return ['error' => $exit,'value' => ['Error de timeout al crear el archivo']];
		}
		
		usort($pool->done,function($ca,$cb){
			if($ca->pag_offset < $cb->pag_offset) return -1;
			if($ca->pag_offset > $cb->pag_offset) return  1;
			return 0;
		});
				
		$files = array_map(function($c){return $c->filename;},$pool->done);
		
		foreach($files as $f){
			if(!Storage::exists($f)){//Si el archivo no se creo bien, borro todo y retorno error
				unset($pool);
				gc_collect_cycles();//Asegurar que se liberen todos los procesos
				return ['error' => -1,'value' => ['Error de creacion de archivos']];
			}
		}
		
		//Paso a path absoluto los inputs y el output
		$nfiles = array_map(function($f){return Storage::getAdapter()->applyPathPrefix($f);},$files);
    $input_files_list = '"'.implode('" "',$nfiles).'"';
		$unite_filename = $fingerprint.".pdf";
    $output_file = Storage::getAdapter()->applyPathPrefix($unite_filename);
    
    $command = "pdfunite $input_files_list \"$output_file\" 2>&1";
    $output = [];
    $rtrn = 0;
    exec($command,$output,$rtrn);
    //Si hubo error value es la salida, si exitoso devuelvo el path al archivo
    $hubo_error = count($output) != 0 || $rtrn != 0;
    if($hubo_error){
			Storage::delete($unite_filename);
			unset($pool);
			gc_collect_cycles();//Asegurar que se liberen todos los procesos
			return ['error' => $rtrn, 'value' => $output];
		}
		
		unset($pool);
		gc_collect_cycles();//Asegurar que se liberen todos los procesos
    return ['error' => $rtrn, 'value' => $output_file];
	}
}

