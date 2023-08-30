<?php

use Illuminate\Foundation\Inspiring;

/*
|--------------------------------------------------------------------------
| Console Routes
|--------------------------------------------------------------------------
|
| This file is where you may define all of your Closure based console
| commands. Each Closure is bound to a command instance allowing a
| simple approach to interacting with each command's IO methods.
|
*/

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->describe('Display an inspiring quote');

use Dompdf\Dompdf;

Artisan::command('crear-pdf {data}', function ($data){	
	$json = json_decode(file_get_contents($data),true);
	
	$validator = Validator::make($json,[
		'planilla' => 'required|string',
		'compact' => 'required|array',
		'filename' => 'required|string',
		'codigo' => 'nullable|string',
		'pag_offset' => 'required|integer|min:0',
		'pags' => 'required|integer|min:1',
	],[
		'required' => 'El valor es requerido',
		'string' => 'El valor tiene que ser una cadena',
		'array' => 'El valor tiene que ser un arreglo',
		'integer' => 'El valor tiene que ser un número entero',
		'min' => 'El valor esta por debajo del limite'
	]);
	
	if($validator->fails()) {
		$this->error('Error al crear PDF:');
		foreach ($validator->errors()->messages() as $k => $errors) {
			$e = implode(', ',$errors);
			$this->error("$k: $e");
		}
		return 1;
	}
	
	$view = View::make($json['planilla'], $json['compact']);
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
	',$json['pag_offset'],$json['codigo'],$json['pags']);
	$dompdf->getCanvas()->page_script($script);
	
	//No puedo manejar señales porque es de pcntl que no esta permitido bajo Apache...
	/*
	$clear_files = function(int $signo, mixed $siginfo) use ($json){
		//@TODO
		//Quedan archivos de output a guardarse encolados post timeout y no se como limpiarlos
		Storage::delete($json['filename']);
		exit;
	};
	
	pcntl_signal(SIGABRT,$clear_files);
	pcntl_signal(SIGTERM,$clear_files);*/
	
	Storage::put($json['filename'],$dompdf->output());
	/*
	 * el render puede ocupar mucha memoria y excede la cantidad de memoria
	 * al generar tantos pdfs (siguen allocados como strings)
	 * fuerzo que los elimine porque ya tenemos el archivo guardado
	 */
	unset($json['compact']);
	unset($view);
	unset($dompdf);
	gc_collect_cycles();
	return 0;
})->describe('Usado para generar PDFs en paralelo');
