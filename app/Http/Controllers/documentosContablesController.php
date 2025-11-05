<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Usuario;

use App\RegistroIva;
use App\Registroiibb;
use App\RegistroDREI;
use App\RegistroTGI;
use App\RegistroTGI_partida;
use App\RegistroTGI_partida_pago;
use App\RegistroIMP_AP_OL;
use App\RegistroIMP_AP_MTM;
use App\RegistroDeudaEstado;
use App\RegistroPagosMayoresMesas;
use App\RegistroReporteYLavado;
use App\RegistroRegistrosContables;
use App\RegistroAportesPatronales;
use App\RegistroPromoTickets;
use App\RegistroPozosAcumuladosLinkeados;
use App\RegistroContribEnteTuristico;
use App\RegistroRRHH;
use App\RegistroGanancias;
use App\RegistroGanancias_periodo;
use App\RegistroJackpotsPagados;
use App\RegistroPremiosPagados;
use App\RegistroPremiosMTM;
use App\RegistroAutDirectores_director;
use App\RegistroAutDirectores_autorizacion;
use App\RegistroAutDirectores;
use App\RegistroSeguros;
use App\RegistroSeguros_tipo;
use App\RegistroDerechoAcceso;
use App\RegistroPatentes;
use App\RegistroPatentes_patenteDe;
use App\RegistroPatentes_patenteDe_pago;
use App\RegistroImpInmobiliario;
use App\RegistroImpInmobiliario_partida;
use App\RegistroImpInmobiliario_partida_pago;

use App\Registro_archivo;
use App\Casino;
use Illuminate\Support\Str;
use Illuminate\Support\Arr;
use Illuminate\Http\UploadedFile;
use Maatwebsite\Excel\Facades\Excel;
use PHPExcel_Style_Alignment;
use PHPExcel_Style_Border;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Storage;


class documentosContablesController extends Controller
{
    public function index(){

      $usuario = UsuarioController::getInstancia()->buscarUsuario(session('id_usuario'))['usuario'];

      $casinos = $usuario->casinos;

      $esControlador = 0;
      $usuario = Usuario::find(session('id_usuario'));
      foreach ($usuario->roles as $rol) {
          if ($rol->descripcion == "CONTROL" || $rol->descripcion == "ADMINISTRADOR" || $rol->descripcion == "SUPERUSUARIO") {
              $esControlador = 1;
          }
      }
      UsuarioController::getInstancia()->agregarSeccionReciente('Documentos Contables', 'documentosContables');

      $puede_cargar = $esControlador || $usuario->tienePermiso('documentos_contables');

      $rosario = $usuario->usuarioTieneCasino(3);
      $melincue = $usuario->usuarioTieneCasino(1);

      return view('documentosContables', [
        'casinos' => $casinos,
        'es_superusuario' => $esControlador,
        'rosario' => $rosario,
      ]);
    }
    public function visualizarArchivo($registro,$id_archivo){
      $path = [
        'iva' => 'app/public/RegistroIVA',
        'iibb' => 'app/public/Registroiibb',
        'drei' => 'app/public/RegistroDREI',
        'tgi' => 'app/public/RegistroTGI',
        'IMP_AP_OL' => 'app/public/RegistroIMP_AP_OL',
        'IMP_AP_MTM' => 'app/public/RegistroIMP_AP_MTM',
        'DeudaEstado' => 'app/public/RegistroDeudaEstado',
        'PagosMayoresMesas' => 'app/public/RegistroPagosMayoresMesas',
        'ReporteYLavado' => 'app/public/RegistroReporteYLavado',
        'RegistrosContables' => 'app/public/RegistroRegistrosContables',
        'AportesPatronales' => 'app/public/RegistroAportesPatronales',
        'PromoTickets' => 'app/public/RegistroPromoTickets',
        'PozosAcumuladosLinkeados' => 'app/public/RegistroPozosAcumuladosLinkeados',
        'ContribEnteTuristico' => 'app/public/RegistroContribEnteTuristico',
        'RRHH' => 'app/public/RegistroRRHH',
        'Ganancias' => 'app/public/RegistroGanancias',
        'JackpotsPagados' => 'app/public/RegistroJackpotsPagados',
        'PremiosPagados' => 'app/public/RegistroPremiosPagados',
        'PremiosMTM' => 'app/public/RegistroPremiosMTM',
        'AutDirectores' => 'app/public/RegistroAutDirectores',
        'Seguros' => 'app/public/RegistroSeguros',
        'DerechoAcceso' => 'app/public/RegistroDerechoAcceso',
        'Patentes' => 'app/public/RegistroPatentes',
        'ImpInmobiliario' => 'app/public/RegistroImpInmobiliario',


      ];

      if(!array_key_exists($registro,$path)){
        throw new \Exception('Registro '.$registro.' invalido');
      }

      $abs_file = storage_path($path[$registro].'/'.$id_archivo);
      return response()->stream(function () use ($abs_file) {
          readfile($abs_file);
        }, 200, [
        'Content-Type' => mime_content_type($abs_file),
        'Content-Disposition' => "inline; filename=\"$id_archivo\""
      ]);
    }


    public function eliminarArchivo(Request $request){
        $id = $request->query('id');
        if(!$id || !ctype_digit((string)$id)) return response()->json(['success'=>0],400);

        $ra = Registro_archivo::findOrFail($id);
        $abs = storage_path('app/public/'.ltrim($ra->path,'/'));
        if(file_exists($abs)) @unlink($abs);
        $ra->delete();

        return response()->json(['success'=>1]);
    }



//iva
public function guardarIva(Request $request){
    DB::beginTransaction();
    try{
        $iva = new RegistroIva();
        $iva->fecha_iva          = $request->input('fecha_iva').'-01';
        $iva->fecha_presentacion = $request->input('fecha_ivaPres');
        $iva->fecha_toma         = date('Y-m-d H:i:s');
        $iva->saldo              = $request->input('saldoIva');
        $iva->casino             = $request->input('casinoIva');
        $iva->observacion        = $request->input('obsiva');
        $iva->usuario            = UsuarioController::getInstancia()->quienSoy()['usuario']['id_usuario'];
        $iva->save();

        $files = Arr::wrap($request->file('uploadIva'));
        foreach ($files as $file) {
            if (!($file instanceof UploadedFile) || !$file->isValid()) continue;

            $base = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
            $ext  = $file->getClientOriginalExtension();
            $safe = preg_replace('/\s+/', '_', $base);
            $filename = time().'_'.Str::random(6).'_'.$safe.($ext?'.'.$ext:'');

            $file->storeAs('public/RegistroIVA', $filename);

            $iva->archivos()->create([
                'path'       => $filename,
                'usuario'    => $iva->usuario,
                'fecha_toma' => date('Y-m-d H:i:s'),
            ]);
        }

        DB::commit();
        return response()->json(['success'=>true,'id'=>$iva->id_registroIva]);
    }catch(\Exception $e){
        DB::rollBack();
        return response()->json(['success'=>false,'error'=>$e->getMessage()],500);
    }
}

public function actualizarIva(Request $request, $id)
{

    $r = RegistroIva::findOrFail($id);

    $r->fecha_iva          = $request->input('fecha_iva').'-01';
    $r->fecha_presentacion = $request->input('fecha_ivaPres');
    $r->casino             = $request->input('casinoIva');
    $r->saldo              = $request->input('saldoIva');
    $r->observacion        = $request->input('obsiva');
    $r->save();

    $saved = 0;
    $files = Arr::wrap($request->file('uploadIva'));

    foreach ($files as $file) {
        if (!($file instanceof UploadedFile) || !$file->isValid()) continue;

        $base = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
        $ext  = $file->getClientOriginalExtension();
        $safe = preg_replace('/\s+/', '_', $base);
        $name = time().'_'.Str::random(6).'_'.$safe.($ext ? '.'.$ext : '');

        $file->storeAs('public/RegistroIVA', $name);

        $r->archivos()->create([
            'path'       => $name,
            'usuario'    => UsuarioController::getInstancia()->quienSoy()['usuario']['id_usuario'],
            'fecha_toma' => date('Y-m-d H:i:s'),
        ]);

        $saved++;
    }

    return response()->json(['success' => true, 'saved_files' => $saved]);
}


public function llenarIva($id){
  $Iva = RegistroIva::findOrFail($id);
  if(is_null($Iva)) return 0;

  return response()->json([
    'fecha' => $Iva->fecha_iva,
    'fecha_pres' => $Iva->fecha_presentacion,
    'casino' => $Iva->casino,
    'saldo' => $Iva->saldo,
    'obs' => $Iva->observacion
  ]);

}

public function ultimasIva(Request $request)
{
    $page    = max(1, (int)$request->query('page', 1));
    $perPage = max(1, (int)$request->query('page_size', 20));

    $user = Usuario::find(session('id_usuario'));
    $allowedCasinoIds = $user->casinos->pluck('id_casino')->toArray();

    $query = RegistroIva::with('casinoIva')
              ->withCount('archivos')
              ->whereIn('casino', $allowedCasinoIds)
              ->orderBy('fecha_iva', 'desc');

    if ($c = $request->query('id_casino')) $query->where('casino', $c);
    if ($desde = $request->query('desde')) $query->where('fecha_iva', '>=', $desde.'-01');
    if ($hasta = $request->query('hasta')) $query->where('fecha_iva', '<=', $hasta.'-01');

    $total = $query->count();

    $registros = $query->skip(($page - 1) * $perPage)->take($perPage)->get();

    $datos = $registros->map(function($r){
        return [
            'id_registroIva'      => $r->id_registroIva,
            'fecha_iva'           => $r->fecha_iva,
            'fecha_presentacion'  => $r->fecha_presentacion,
            'casino'              => $r->casinoIva ? $r->casinoIva->nombre : '-',
            'saldo'               => $r->saldo,
            'observacion'         => $r->observacion,
            'tiene_archivos'      => $r->archivos_count > 0,
        ];
    });

    return response()->json([
        'registros'  => $datos,
        'pagination' => [
            'current_page' => $page,
            'per_page'     => $perPage,
            'total'        => $total,
        ],
    ]);
}

public function archivosIva($id)
{
    $iva = RegistroIva::with('archivos')->findOrFail($id);

    $files = $iva->archivos->map(function($a){
        return [
            'id'     => $a->id_registro_archivo,
            'nombre' => basename($a->path),
            'url'    => \Illuminate\Support\Facades\Storage::url($a->path),
            'fecha'  => $a->fecha_toma,
        ];
    });

    return response()->json($files->values());
}



public function eliminarIva($id){
  $iva = RegistroIva::findOrFail($id);
  if(is_null($iva)) return 0;
  RegistroIva::destroy($id);
  return 1;
}

public function descargarIvaExcel(Request $request)
{
    $casinoId = $request->query('casino');
    $desde    = $request->query('desde');
    $hasta    =$request->query('hasta');

    $query = RegistroIva::select([
            DB::raw('YEAR(fecha_iva) AS anio'),
            DB::raw("MONTHNAME(fecha_iva) AS Período"),
            DB::raw("DATE_FORMAT(fecha_presentacion,'%d/%m/%Y') AS `Fecha Presentación`"),
            DB::raw("saldo AS `Saldo a favor ARCA/Contribuyente`"),
            DB::raw("observacion AS Observaciones")
        ]);
    if($casinoId != 4 ){
      $query->where('casino', $casinoId);
      $casino = Casino::findOrFail($casinoId);
    }


    if ($desde) {
        $query->where('fecha_iva', '>=', $desde . '-01');
    }
    if ($hasta) {
        $query->where('fecha_iva', '<=', $hasta . '-01');
    }

$datos = $query->orderBy('fecha_iva')->get();

    $agrupadoPorAnio = $datos->groupBy('anio');
    $lastRow = count($datos)+2+count($agrupadoPorAnio);
    $filename = "registro_iva_{$casino->nombre}";

    return Excel::create($filename, function($excel) use($agrupadoPorAnio, $casinoId) {
    $excel->sheet('IVA', function($sheet) use($agrupadoPorAnio, $casinoId) {

        $filaActual = 1;

        $sheet->row($filaActual, [
            'Mes',
            'Fecha Presentación de la Declaración Jurada',
            'Saldo a favor ARCA/ Contribuyente',
            'Observaciones'
        ]);

        $sheet->cells("A{$filaActual}:D{$filaActual}", function($cells) use ($casinoId) {
            switch ($casinoId) {
                case 1:
                    $color = '#008f39'; break;
                case 2:
                    $color = '#ff0000'; break;
                case 3:
                    $color = '#ffff00'; break;
                default:
                    $color = '#222222'; break;
            }

            $cells->setBackground($color);
            $cells->setFontColor('#000000');
            $cells->setFontWeight('bold');
            $cells->setAlignment('center');
        });

        $sheet->getStyle("A{$filaActual}:D{$filaActual}")
              ->getAlignment()
              ->setWrapText(true)
              ->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);

        $sheet->cells("A1:D999", function($cells){
            $cells->setFontFamily('Arial');
            $cells->setFontSize(10);
        });
        $sheet->setHeight($filaActual, 30);
        $sheet->freezeFirstRow();

        $filaActual++;

        foreach ($agrupadoPorAnio as $anio => $registros) {
            $sheet->mergeCells("A{$filaActual}:D{$filaActual}");
            $sheet->row($filaActual, ["{$anio}"]);

            $sheet->cells("A{$filaActual}:D{$filaActual}", function($cells){
                $cells->setFontSize(13);
                $cells->setBackground('#CCCCCC');
                $cells->setFontWeight('bold');
                $cells->setAlignment('center');
            });
            $sheet->getStyle("A{$filaActual}:D{$filaActual}")
                  ->getAlignment()
                  ->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
            $sheet->setHeight($filaActual, 20);

            $filaActual++;

            foreach ($registros as $r) {
                setlocale(LC_TIME, 'es_ES.UTF-8');
                $mesEsp = strftime('%B', strtotime($r->{'Período'} . ' 1'));

                $sheet->row($filaActual, [
                    ucfirst($mesEsp),
                    $r->{'Fecha Presentación'},
                    "$ " . number_format($r->{'Saldo a favor ARCA/Contribuyente'}, 2, ',', '.'),
                    $r->Observaciones
                ]);

                $sheet->cells("A{$filaActual}", function($cells){
                    $cells->setBackground('#FFFF99');
                    $cells->setFontWeight('bold');
                    $cells->setAlignment('left');
                });

                $sheet->cells("B{$filaActual}:D{$filaActual}", function($cells){
                    $cells->setAlignment('center');
                });

                $sheet->getStyle("A{$filaActual}:D{$filaActual}")
                      ->getAlignment()
                      ->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);

                $filaActual++;
            }
        }



        $sheet->getStyle("A1:D1")
              ->applyFromArray([
                  'borders' => [
                      'allborders' => [
                          'style' => PHPExcel_Style_Border::BORDER_THICK,
                          'color' => ['argb' => 'FF000000']
                      ]
                  ]
              ]);
        $filaActual--;
        $sheet->getStyle("A2:D{$filaActual}")
              ->applyFromArray([
                  'borders' => [
                      'allborders' => [
                          'style' => PHPExcel_Style_Border::BORDER_THIN,
                          'color' => ['argb' => 'FF000000']
                      ]
                  ]
              ]);

        $sheet->setHeight(1,50);

        $sheet->setWidth('A', 9);
        $sheet->setWidth('B', 12);
        $sheet->setWidth('C', 20);
        $sheet->setWidth('D', 30);
    });
})->export('xlsx');
}

public function descargarIvaExcelTodos(Request $request)
{
    $desde = $request->query('desde');
    $hasta = $request->query('hasta');

    return Excel::create('iva_todos_casinos', function($excel) use($desde, $hasta) {
        $excel->sheet('IVA', function($sheet) use($desde, $hasta) {

            $user = Usuario::find(session('id_usuario'));
            $allowedCasinoIds = $user->casinos->pluck('id_casino')->toArray();



            $casinos = [
                1 => ['nombre' => 'Melincué', 'color' => '#008f39'],
                2 => ['nombre' => 'Santa Fe', 'color' => '#ff0000'],
                3 => ['nombre' => 'Rosario', 'color' => '#ffff00'],
            ];

            $colOffsets = [0, 5, 10];
            $cols = range('A', 'Z');

            $i = 0;
            $sheet->cells("A1:O80", function($cells){
                $cells->setFontFamily('Arial');
                $cells->setFontSize(10);
            });
            foreach (array_intersect_key($casinos, array_flip($allowedCasinoIds)) as $id => $info) {
                $datos = RegistroIva::select([
                        DB::raw("YEAR(fecha_iva) AS anio"),
                        DB::raw("MONTHNAME(fecha_iva) AS Mes"),
                        DB::raw("DATE_FORMAT(fecha_presentacion,'%d/%m/%Y') AS `Fecha Presentación`"),
                        DB::raw("saldo AS `Saldo a favor ARCA/Contribuyente`"),
                        DB::raw("observacion AS Observaciones")
                    ])
                    ->where('casino', $id)
                    ->when($desde, function($q) use ($desde) {
                        return $q->where('fecha_iva', '>=', $desde . '-01');
                    })
                    ->when($hasta, function($q) use ($hasta) {
                        return $q->where('fecha_iva', '<=', $hasta . '-01');
                    })
                    ->orderBy('fecha_iva')
                    ->get()
                    ->groupBy('anio');

                $offset = $colOffsets[$i];
                $col1 = $cols[$offset];
                $col2 = $cols[$offset+1];
                $col3 = $cols[$offset+2];
                $col4 = $cols[$offset+3];

                $filaActual = 1;

                $sheet->setCellValue("{$col1}{$filaActual}", $info['nombre'] . " - Mes");
                $sheet->setCellValue("{$col2}{$filaActual}", "Fecha Presentación de la Declaración Jurada");
                $sheet->setCellValue("{$col3}{$filaActual}", "Saldo a favor ARCA/Contribuyente");
                $sheet->setCellValue("{$col4}{$filaActual}", "Observaciones");


                $sheet->cells("{$col1}{$filaActual}:{$col4}{$filaActual}", function($cells) use($info) {
                    $cells->setBackground($info['color']);
                    $cells->setFontColor('#000000');
                    $cells->setFontWeight('bold');
                    $cells->setAlignment('center');
                });

                $sheet->getStyle("{$col1}{$filaActual}:{$col4}{$filaActual}")
                      ->getAlignment()
                      ->setWrapText(true)
                      ->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);

                $sheet->setHeight($filaActual, 30);

                $filaActual++;

                foreach ($datos as $anio => $registros) {
                    $sheet->mergeCells("{$col1}{$filaActual}:{$col4}{$filaActual}");
                    $sheet->setCellValue("{$col1}{$filaActual}", $anio);
                    $sheet->cells("{$col1}{$filaActual}:{$col4}{$filaActual}", function($cells){
                        $cells->setBackground('#CCCCCC');
                        $cells->setFontWeight('bold');
                        $cells->setFontSize(13);
                        $cells->setAlignment('center');
                    });
                    $sheet->getStyle("{$col1}{$filaActual}:{$col4}{$filaActual}")
                          ->getAlignment()
                          ->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
                    $sheet->setHeight($filaActual, 20);

                    $filaActual++;

                    foreach ($registros as $r) {
                        setlocale(LC_TIME, 'es_ES.UTF-8');
                        $mesEsp = ucfirst(strftime('%B', strtotime($r->{'Mes'} . ' 1')));

                        $sheet->setCellValue("{$col1}{$filaActual}", $mesEsp);
                        $sheet->setCellValue("{$col2}{$filaActual}", $r->{'Fecha Presentación'});
                        $sheet->setCellValue("{$col3}{$filaActual}", "$ " . number_format($r->{'Saldo a favor ARCA/Contribuyente'}, 2, ',', '.'));
                        $sheet->setCellValue("{$col4}{$filaActual}", $r->Observaciones);

                        $sheet->cells("{$col1}{$filaActual}", function($cells){
                            $cells->setBackground('#FFFF99');
                            $cells->setFontWeight('bold');
                            $cells->setAlignment('left');
                        });

                        $sheet->cells("{$col2}{$filaActual}:{$col4}{$filaActual}", function($cells){
                            $cells->setAlignment('center');
                        });

                        $sheet->getStyle("{$col1}{$filaActual}:{$col4}{$filaActual}")
                              ->getAlignment()
                              ->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);

                        $filaActual++;
                    }
                }

                $filaUltima = $filaActual - 1;
                $sheet->getStyle("{$col1}1:{$col4}{$filaUltima}")
                      ->applyFromArray([
                          'borders' => [
                              'allborders' => [
                                  'style' => PHPExcel_Style_Border::BORDER_THIN,
                                  'color' => ['argb' => 'FF000000']
                              ]
                          ]
                      ]);
                $sheet->getStyle("A1:D1")
                      ->applyFromArray([
                          'borders' => [
                              'allborders' => [
                                  'style' => PHPExcel_Style_Border::BORDER_THICK,
                                  'color' => ['argb' => 'FF000000']
                              ]
                          ]
                      ]);
              if(sizeOf($allowedCasinoIds)>=2){
                $sheet->getStyle("F1:I1")
                      ->applyFromArray([
                          'borders' => [
                              'allborders' => [
                                  'style' => PHPExcel_Style_Border::BORDER_THICK,
                                  'color' => ['argb' => 'FF000000']
                              ]
                          ]
                      ]);
                    }
              if(sizeOf($allowedCasinoIds)==3){
                $sheet->getStyle("K1:N1")
                      ->applyFromArray([
                          'borders' => [
                              'allborders' => [
                                  'style' => PHPExcel_Style_Border::BORDER_THICK,
                                  'color' => ['argb' => 'FF000000']
                              ]
                          ]
                      ]);
                    }

                $sheet->setHeight(1,50);
                $sheet->setWidth($col1, 9);
                $sheet->setWidth($col2, 12);
                $sheet->setWidth($col3, 20);
                $sheet->setWidth($col4, 30);

                $i++;
            }
        });
    })->export('xlsx');
}


public function descargarIvaCsv(Request $request)
{
    $user = Usuario::find(session('id_usuario'));
    $allowedCasinoIds = $user->casinos->pluck('id_casino')->toArray();
    $casinoId = $request->query('casino');
    $desde    = $request->query('desde');
    $hasta    = $request->query('hasta');

    $registros = RegistroIva::with('casinoIva')
        ->whereIn('casino', $allowedCasinoIds)
        ->when($casinoId && $casinoId != 4, function ($q) use ($casinoId) {
            $q->where('casino', $casinoId);
        })
        ->when($desde, function ($q) use ($desde) {
            $q->where('fecha_iva', '>=', $desde . '-01');
        })
        ->when($hasta, function ($q) use ($hasta) {
            $q->where('fecha_iva', '<=', $hasta . '-01');
        })
        ->orderBy('casino')
        ->orderBy('fecha_iva')
        ->get();

    $csv = [];
    $csv[] = ['Casino', 'Año', 'Mes', 'Fecha Presentación de la Declaración jurada', 'Saldo a favor ARCA/ Contribuyente', 'Observaciones'];

    foreach ($registros as $r) {
        setlocale(LC_TIME, 'es_ES.UTF-8');
        $anio     = date('Y', strtotime($r->fecha_iva));
        $mes      = strftime('%B', strtotime($r->fecha_iva));
        $pres     = date('d/m/Y', strtotime($r->fecha_presentacion));
        $saldo    = number_format($r->saldo, 2, '.', '');
        $casino   = $r->casinoIva ? $r->casinoIva->nombre : '-';
        $obs      = $r->observacion;

        $csv[] = [
            $casino,
            $anio,
            ucfirst($mes),
            $pres,
            $saldo,
            $obs
        ];
    }

    $nombreCasino = $casinoId == 4 ? 'todos' : Casino::find($casinoId)->nombre ?? 'desconocido';
    $filename = "iva_{$nombreCasino}_" . date('Ymd_His') . ".csv";

    $handle = fopen('php://temp', 'r+');
    foreach ($csv as $linea) {
        fputcsv($handle, $linea, ',');
    }
    rewind($handle);
    $contenido = stream_get_contents($handle);
    fclose($handle);

    return response($contenido, 200, [
        'Content-Type'        => 'text/csv; charset=UTF-8',
        'Content-Disposition' => "attachment; filename=\"$filename\"",
        'Cache-Control'       => 'no-store, no-cache'
    ]);
}


//iibb
public function guardariibb(Request $request)
{
  try{
    DB::beginTransaction();


        $iibb = new Registroiibb();
        $iibb->fecha_iibb         = $request->input('fecha_iibb') ? ($request->input('fecha_iibb').'-01') : null;
        $iibb->fecha_presentacion = $request->input('fecha_iibbPres');
        $iibb->fecha_toma         = date('Y-m-d H:i:s');

        $iibb->casino              = $request->input('casinoiibb');
        $iibb->observacion         = $request->input('obsiibb');
        $iibb->diferencia_minimo   = $request->input('dif_miniibb');
        $iibb->deducciones         = $request->input('deduccionesiibb');
        $iibb->impuesto_total_determinado      = $request->input('total_impuesto_iibb');
        $iibb->saldo_a_favor_api_contribuyente = $request->input('saldo_iibb');
        $iibb->usuario             = UsuarioController::getInstancia()->quienSoy()['usuario']['id_usuario'];
        $iibb->save();

        foreach (\Illuminate\Support\Arr::wrap($request->file('uploadiibb')) as $file) {
            if (!($file instanceof \Illuminate\Http\UploadedFile) || !$file->isValid()) continue;
            $base = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
            $ext  = $file->getClientOriginalExtension();
            $safe = preg_replace('/\s+/', '_', $base);
            $name = time().'_'.\Illuminate\Support\Str::random(6).'_'.$safe.($ext?'.'.$ext:'');
            $file->storeAs('public/Registroiibb', $name);

            $iibb->archivos()->create([
                'path'       => $name,
                'usuario'    => $iibb->usuario,
                'fecha_toma' => date('Y-m-d H:i:s'),
            ]);
        }

        $bases  = \Illuminate\Support\Arr::wrap($request->input('base'));
        $montos = \Illuminate\Support\Arr::wrap($request->input('monto'));
        $aliqs  = \Illuminate\Support\Arr::wrap($request->input('alicuota'));
        $imps   = \Illuminate\Support\Arr::wrap($request->input('impuesto'));

        $n = max(count($bases), count($montos), count($aliqs), count($imps));
        for ($i=0; $i<$n; $i++) {
            $obs   = $bases[$i]  ?? null;
            $monto = $montos[$i] ?? null;
            $ali   = $aliqs[$i]  ?? null;
            $imp   = $imps[$i]   ?? null;

            if ($monto === null || $ali === null) continue;
            if ($imp === null) $imp = $monto * ($ali / 100);

            $iibb->bases()->create([
                'observacion'          => $obs,
                'base'                 => $monto,
                'alicuota'             => $ali,
                'impuesto_determinado' => $imp,
            ]);
        }

        DB::commit();
        return response()->json(['success' => true, 'id' => $iibb->id_registroiibb]);
    } catch (\Throwable $e) {
        DB::rollBack();
        return response()->json(['success'=>false, 'error'=>$e->getMessage()], 500);
    }
}


public function actualizariibb(Request $request, $id)
{
    DB::beginTransaction();
    try {
        $r = Registroiibb::with('bases')->findOrFail($id);

        $r->fecha_iibb                         = $request->input('fecha_iibb').'-01';
        $r->fecha_presentacion                 = $request->input('fecha_iibbPres');
        $r->casino                             = $request->input('casinoiibb');
        $r->diferencia_minimo                  = $request->input('dif_miniibb');
        $r->deducciones                        = $request->input('deduccionesiibb');
        $r->observacion                        = $request->input('obsiibb');
        $r->impuesto_total_determinado         = $request->input('total_impuesto_iibb');
        $r->saldo_a_favor_api_contribuyente    = $request->input('saldo_iibb');
        $r->save();

        $files = $request->file('uploadiibb');
        if ($files) {
            if (!is_array($files)) $files = [$files];
            foreach ($files as $file) {
                if (!($file instanceof \Illuminate\Http\UploadedFile) || !$file->isValid()) continue;
                $base = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
                $ext  = $file->getClientOriginalExtension();
                $safe = preg_replace('/\s+/', '_', $base);
                $name = time().'_'.str_random(6).'_'.$safe.($ext ? '.'.$ext : '');
                $file->storeAs('public/Registroiibb', $name);
                $r->archivos()->create([
                    'path'       => $name,
                    'usuario'    => UsuarioController::getInstancia()->quienSoy()['usuario']['id_usuario'],
                    'fecha_toma' => date('Y-m-d H:i:s'),
                ]);
            }
        }

        $existingIds = $r->bases()->pluck('id_registroiibb_bases')->toArray();
        $keepIds = [];

        $ids    = (array) $request->input('base_id', []);
        $obsArr = (array) $request->input('base', []);
        $montos = (array) $request->input('monto', []);
        $aliqs  = (array) $request->input('alicuota', []);
        $imps   = (array) $request->input('impuesto', []);

        $n = max(count($ids), count($obsArr), count($montos), count($aliqs), count($imps));

        for ($i=0; $i<$n; $i++) {
            $bid   = isset($ids[$i])    ? $ids[$i]    : null;
            $obs   = isset($obsArr[$i]) ? $obsArr[$i] : null;
            $monto = isset($montos[$i]) ? $montos[$i] : null;
            $ali   = isset($aliqs[$i])  ? $aliqs[$i]  : null;
            $imp   = isset($imps[$i])   ? $imps[$i]   : null;

            if ($monto === null || $ali === null) continue;

            $payload = [
                'observacion'          => $obs,
                'base'                 => $monto,
                'alicuota'             => $ali,
                'impuesto_determinado' => $imp,
            ];

            if ($bid) {
                $r->bases()->where('id_registroiibb_bases', $bid)->update($payload);
                $keepIds[] = $bid;
            } else {
                $nuevo = $r->bases()->create($payload);
                if ($nuevo && isset($nuevo->id_registroiibb_bases)) {
                    $keepIds[] = $nuevo->id_registroiibb_bases;
                }
            }
        }

        $toDelete = array_diff($existingIds, $keepIds);
        if (!empty($toDelete)) {
            $r->bases()->whereIn('id_registroiibb_bases', $toDelete)->delete();
        }

        DB::commit();
        return response()->json(['success' => true, 'id' => $r->id_registroiibb]);
    } catch (\Exception $e) {
        DB::rollBack();
        return response()->json([
          'success' => false,
           'error' => $e->getMessage(),
           'request' => $request->all(),
         ], 500);
    }
}


public function llenariibbEdit($id){
  $iibb = Registroiibb::with('casinoiibb','bases')->findOrFail($id);
  if(is_null($iibb)) return 0;

  return response()->json([
    'fecha' => $iibb->fecha_iibb,
    'fecha_pres' => $iibb->fecha_presentacion,
    'casino' => $iibb->casino,
    'impuesto_total' => $iibb->impuesto_total_determinado,
    'diferencia' => $iibb->diferencia_minimo,
    'deducciones' => $iibb->deducciones,
    'saldo' => $iibb->saldo_a_favor_api_contribuyente,
    'obs' => $iibb->observacion,
    'bases' => $iibb->bases->map(function($b){
      return [
        'id'        => $b->id_registroiibb_bases,
        'obs'       => $b->observacion,
        'monto'     => $b->base,
        'alicuota'  => $b->alicuota,
        'imp' => $b->impuesto_determinado,
      ];
    }),
  ]);
}

public function ultimasiibb(Request $request)
{
$page    = max(1, (int)$request->query('page', 1));
$perPage = max(1, (int)$request->query('page_size', 20));
$user = Usuario::find(session('id_usuario'));
$allowedCasinoIds = $user->casinos->pluck('id_casino')->toArray();

$query = Registroiibb::with('casinoiibb')
          ->withCount('archivos')
          ->whereIn('casino', $allowedCasinoIds)
          ->orderBy('fecha_iibb', 'desc');

if ($c = $request->query('id_casino')) {
  $query->where('casino', $c);
}
if ($desde = $request->query('desde')){
  $desde = $desde."-01";
  $query->where('fecha_iibb',">=",$desde);
}
if ($hasta = $request->query('hasta')){
  $hasta = $hasta."-01";
  $query->where('fecha_iibb',"<=",$hasta);
}

$total = $query->count();

$registros = $query
    ->skip(($page - 1) * $perPage)
    ->take($perPage)
    ->get();

$datos = $registros->map(function($r) {
    return [
        'id_registroiibb' => $r->id_registroiibb,
        'fecha_iibb'   => $r->fecha_iibb,
        'fecha_presentacion' => $r->fecha_presentacion,
        'casino'      => $r->casinoiibb ? $r->casinoiibb->nombre : '-',
        'tiene_archivos' => $r->archivos_count>0,
    ];
});

return response()->json([
    'registros'  => $datos,

    'pagination' => [
        'current_page' => $page,
        'per_page'     => $perPage,
        'total'        => $total,
    ],
]);
}

public function eliminariibb($id){
  $iibb = Registroiibb::findOrFail($id);
  if(is_null($iibb)) return 0;
  Registroiibb::destroy($id);
  return 1;
}


public function archivosiibb($id)
{
    $iibb = Registroiibb::with('archivos')->findOrFail($id);

    $files = $iibb->archivos->map(function($a){
        return [
            'id'     => $a->id_registro_archivo,
            'nombre' => basename($a->path),
            'url'    => \Illuminate\Support\Facades\Storage::url($a->path),
            'fecha'  => $a->fecha_toma,
        ];
    });

    return response()->json($files->values());
}


public function llenariibb($id){
  $iibb = Registroiibb::with('casinoiibb','bases')->findOrFail($id);
  if(is_null($iibb)) return 0;

  return response()->json([
    'fecha' => $iibb->fecha_iibb,
    'fecha_pres' => $iibb->fecha_presentacion,
    'casino' => $iibb->casinoiibb ? $iibb->casinoiibb->nombre : '-',
    'base' => $iibb->bases,
    'impuesto_total' => $iibb->impuesto_total_determinado,
    'diferencia' => $iibb->diferencia_minimo,
    'deducciones' => $iibb->deducciones,
    'saldo' => $iibb->saldo_a_favor_api_contribuyente,
    'obs' => $iibb->observacion
  ]);

}



public function descargariibbXlsx(Request $request)
{
    $casinoId = $request->query('casino');
    $desde = $request->query('desde');
    $hasta = $request->query('hasta');

    $casino = Casino::findOrFail($casinoId);

    $registros = Registroiibb::with(['casinoiibb', 'bases'])
        ->where('casino', $casinoId)
        ->when($desde, function ($q) use ($desde) {
            $q->where('fecha_iibb', '>=', $desde . '-01');
        })
        ->when($hasta, function ($q) use ($hasta) {
            $q->where('fecha_iibb', '<=', $hasta . '-31');
        })
        ->orderBy('fecha_iibb')
        ->get()
        ->groupBy(function($r){
            return date('Y', strtotime($r->fecha_iibb));
        });

    $filename = "registro_iibb_" . str_replace(' ', '_', strtolower($casino->nombre));

    return Excel::create($filename, function($excel) use ($registros, $casinoId) {
        $excel->sheet('IIBB', function($sheet) use ($registros, $casinoId) {
            $fila = 1;

            $sheet->row($fila, [
                'Mes',
                'Presentación DDJJ / Fecha de Pago',
                'Actividad / Observaciones base',
                'Base Imponible',
                'Alicuota',
                'Impuesto Determinado (Base)',
                'Impuesto Total Determinado',
                'Diferencia Mínimo',
                'Deducciones',
                'Saldo a Favor API/ Contribuyente',
                'Observaciones Generales'
            ]);

            $sheet->cells("A1:K1", function($cells) use ($casinoId) {
                switch ($casinoId) {
                    case 1: $color = '#008f39'; break;
                    case 2: $color = '#ff0000'; break;
                    case 3: $color = '#ffff00'; break;
                    default: $color = '#222222'; break;
                }
                $cells->setBackground($color);
                $cells->setFontColor('#000000');
                $cells->setFontWeight('bold');
                $cells->setAlignment('center');
            });

            $sheet->getStyle("A1:K1")->getAlignment()->setWrapText(true)->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
            $sheet->setHeight(1, 50);

            $sheet->cells("A1:K999", function($cells){
                $cells->setFontFamily('Arial');
                $cells->setFontSize(10);
            });

            $fila++;

            foreach ($registros as $anio => $registrosAnio) {
                $sheet->mergeCells("A{$fila}:K{$fila}");
                $sheet->setCellValue("A{$fila}", $anio);
                $sheet->cells("A{$fila}:K{$fila}", function($cells){
                    $cells->setBackground('#CCCCCC');
                    $cells->setFontWeight('bold');
                    $cells->setFontSize(13);
                    $cells->setAlignment('center');
                });
                $sheet->getStyle("A{$fila}:K{$fila}")->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
                $sheet->setHeight($fila, 20);
                $fila++;

                foreach ($registrosAnio as $r) {
                    $bases = $r->bases->isNotEmpty() ? $r->bases : collect([null]);
                    $inicioFilaRegistro = $fila;

                    foreach ($bases as $b) {
                        setlocale(LC_TIME, 'es_ES.UTF-8');
                        $mesEsp = ucfirst(strftime('%B', strtotime($r->fecha_iibb)));

                        $sheet->row($fila, [
                            $mesEsp,
                            date('d/m/Y', strtotime($r->fecha_presentacion)),
                            $b ? $b->observacion : '-',
                            $b ? "$ " . number_format($b->base, 2, ',', '.') : '-',
                            $b ? number_format($b->alicuota, 2, ',', '.') . ' %' : '-',
                            $b ? "$ " . number_format($b->impuesto_determinado, 2, ',', '.') : '-',
                            "$ " . number_format($r->impuesto_total_determinado, 2, ',', '.'),
                            "$ " . number_format($r->diferencia_minimo, 2, ',', '.'),
                            "$ " . number_format($r->deducciones, 2, ',', '.'),
                            "$ " . number_format($r->saldo_a_favor_api_contribuyente, 2, ',', '.'),
                            $r->observacion
                        ]);

                        $sheet->cells("A{$fila}", function($cells){
                            $cells->setBackground('#FFFF99');
                            $cells->setFontWeight('bold');
                            $cells->setAlignment('left');
                        });

                        $sheet->cells("B{$fila}:K{$fila}", function($cells){
                            $cells->setAlignment('center');
                        });

                        $sheet->getStyle("A{$fila}:K{$fila}")->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
                        $fila++;
                    }

                    if ($bases->count() > 1) {
                        foreach (['A','B','G','H','I','J','K'] as $col) {
                            $sheet->mergeCells("{$col}{$inicioFilaRegistro}:{$col}" . ($fila-1));
                        }
                    }
                }
            }

            $sheet->getStyle("A1:K1")->applyFromArray([
                'borders' => [
                    'allborders' => [
                        'style' => PHPExcel_Style_Border::BORDER_THICK,
                        'color' => ['argb' => 'FF000000']
                    ]
                ]
            ]);

            $sheet->getStyle("A2:K" . ($fila - 1))->applyFromArray([
                'borders' => [
                    'allborders' => [
                        'style' => PHPExcel_Style_Border::BORDER_THIN,
                        'color' => ['argb' => 'FF000000']
                    ]
                ]
            ]);

            $anchos = [
                15, 22, 30, 17, 12, 20, 20, 18, 18, 22, 28
            ];
            foreach (range('A', 'K') as $i => $col) {
                $sheet->setWidth($col, $anchos[$i]);
            }

            $sheet->freezeFirstRow();
        });
    })->export('xlsx');
}

public function descargariibbXlsxTodos(Request $request)
{
    $desde = $request->query('desde');
    $hasta = $request->query('hasta');

    $user = Usuario::find(session('id_usuario'));
    $casinos = $user->casinos;

    return Excel::create('registro_iibb_todos', function($excel) use ($casinos, $desde, $hasta) {

        foreach ($casinos as $casino) {

            $registros = Registroiibb::with(['casinoiibb', 'bases'])
                ->where('casino', $casino->id_casino)
                ->when($desde, function ($q) use ($desde) {
                    $q->where('fecha_iibb', '>=', $desde . '-01');
                })
                ->when($hasta, function ($q) use ($hasta) {
                    $q->where('fecha_iibb', '<=', $hasta . '-31');
                })
                ->orderBy('fecha_iibb')
                ->get()
                ->groupBy(function($r){
                    return date('Y', strtotime($r->fecha_iibb));
                });

            $excel->sheet($casino->nombre, function($sheet) use ($registros, $casino) {
                $fila = 1;

                $sheet->row($fila, [
                    'Mes',
                    'Presentación DDJJ / Fecha de Pago',
                    'Actividad / Observaciones base',
                    'Base Imponible',
                    'Alicuota',
                    'Impuesto Determinado (Base)',
                    'Impuesto Total Determinado',
                    'Diferencia Mínimo',
                    'Deducciones',
                    'Saldo a Favor API/ Contribuyente',
                    'Observaciones Generales'
                ]);

                $sheet->cells("A1:K1", function($cells) use ($casino) {
                    switch ($casino->id_casino) {
                        case 1: $color = '#008f39'; break;
                        case 2: $color = '#ff0000'; break;
                        case 3: $color = '#ffff00'; break;
                        default: $color = '#222222'; break;
                    }
                    $cells->setBackground($color);
                    $cells->setFontColor('#000000');
                    $cells->setFontWeight('bold');
                    $cells->setAlignment('center');
                });

                $sheet->getStyle("A1:K1")->getAlignment()->setWrapText(true)->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
                $sheet->setHeight(1, 50);

                $sheet->cells("A1:K999", function($cells){
                    $cells->setFontFamily('Arial');
                    $cells->setFontSize(10);
                });

                $fila++;

                foreach ($registros as $anio => $registrosAnio) {
                    $sheet->mergeCells("A{$fila}:K{$fila}");
                    $sheet->setCellValue("A{$fila}", $anio);
                    $sheet->cells("A{$fila}:K{$fila}", function($cells){
                        $cells->setBackground('#CCCCCC');
                        $cells->setFontWeight('bold');
                        $cells->setFontSize(13);
                        $cells->setAlignment('center');
                    });
                    $sheet->getStyle("A{$fila}:K{$fila}")->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
                    $sheet->setHeight($fila, 20);
                    $fila++;

                    foreach ($registrosAnio as $r) {
                        $bases = $r->bases->isNotEmpty() ? $r->bases : collect([null]);
                        $inicioFilaRegistro = $fila;

                        foreach ($bases as $b) {
                            setlocale(LC_TIME, 'es_ES.UTF-8');
                            $mesEsp = ucfirst(strftime('%B', strtotime($r->fecha_iibb)));

                            $sheet->row($fila, [
                                $mesEsp,
                                date('d/m/Y', strtotime($r->fecha_presentacion)),
                                $b ? $b->observacion : '-',
                                $b ? "$ " . number_format($b->base, 2, ',', '.') : '-',
                                $b ? number_format($b->alicuota, 2, ',', '.') . ' %' : '-',
                                $b ? "$ " . number_format($b->impuesto_determinado, 2, ',', '.') : '-',
                                "$ " . number_format($r->impuesto_total_determinado, 2, ',', '.'),
                                "$ " . number_format($r->diferencia_minimo, 2, ',', '.'),
                                "$ " . number_format($r->deducciones, 2, ',', '.'),
                                "$ " . number_format($r->saldo_a_favor_api_contribuyente, 2, ',', '.'),
                                $r->observacion
                            ]);

                            $sheet->cells("A{$fila}", function($cells){
                                $cells->setBackground('#FFFF99');
                                $cells->setFontWeight('bold');
                                $cells->setAlignment('left');
                            });

                            $sheet->cells("B{$fila}:K{$fila}", function($cells){
                                $cells->setAlignment('center');
                            });

                            $sheet->getStyle("A{$fila}:K{$fila}")->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
                            $fila++;
                        }

                        if ($bases->count() > 1) {
                            foreach (['A','B','G','H','I','J','K'] as $col) {
                                $sheet->mergeCells("{$col}{$inicioFilaRegistro}:{$col}" . ($fila-1));
                            }
                        }
                    }
                }

                $sheet->getStyle("A1:K1")->applyFromArray([
                    'borders' => [
                        'allborders' => [
                            'style' => PHPExcel_Style_Border::BORDER_THICK,
                            'color' => ['argb' => 'FF000000']
                        ]
                    ]
                ]);

                $sheet->getStyle("A2:K" . ($fila - 1))->applyFromArray([
                    'borders' => [
                        'allborders' => [
                            'style' => PHPExcel_Style_Border::BORDER_THIN,
                            'color' => ['argb' => 'FF000000']
                        ]
                    ]
                ]);

                $anchos = [
                    15, 22, 30, 17, 12, 20, 20, 18, 18, 22, 28
                ];
                foreach (range('A', 'K') as $i => $col) {
                    $sheet->setWidth($col, $anchos[$i]);
                }

                $sheet->freezeFirstRow();
            });
        }

    })->export('xlsx');
}





public function descargariibbCsvActividades(Request $request)
{
    $casinoId = $request->query('casino');
    $desde    = $request->query('desde');
    $hasta    = $request->query('hasta');

    $user = Usuario::find(session('id_usuario'));
    $allowedCasinoIds = $user->casinos->pluck('id_casino')->toArray();

    $registros = Registroiibb::with(['casinoiibb', 'bases'])
        ->whereIn('casino', $allowedCasinoIds)
        ->when($casinoId && $casinoId != 4, function ($q) use ($casinoId) {
            $q->where('casino', $casinoId);
        })
        ->when($desde, function ($q) use ($desde) {
            $q->where('fecha_iibb', '>=', $desde . '-01');
        })
        ->when($hasta, function ($q) use ($hasta) {
            $q->where('fecha_iibb', '<=', $hasta . '-01');
        })
        ->orderBy('casino')
        ->orderBy('fecha_iibb')
        ->get();

    $csv = [];
    $csv[] = [
        'Casino', 'Año', 'Mes', 'Fecha Presentación',
        'Actividad/Observaciones', 'Base', 'Alicuota', 'Impuesto Determinado',
    ];

    foreach ($registros as $r) {
        $anio  = date('Y', strtotime($r->fecha_iibb));
        setlocale(LC_TIME, 'es_ES.UTF-8');
        $mes   = ucfirst(strftime('%B', strtotime($r->fecha_iibb)));
        $pres  = date('d/m/Y', strtotime($r->fecha_presentacion));
        $casino = $r->casinoiibb ? $r->casinoiibb->nombre : '-';

        if ($r->bases->isEmpty()) {
            $csv[] = [
                $casino, $anio, $mes, $pres,
                '-', '-', '-', '-',
                number_format($r->impuesto_determinado, 2, '.', ''),
                number_format($r->diferencia_minimo, 2, '.', ''),
            ];
        } else {
            foreach ($r->bases as $b) {
                $csv[] = [
                    $casino,
                    $anio,
                    $mes,
                    $pres,
                    $b->observacion,
                    number_format($b->base, 2, '.', ''),
                    number_format($b->alicuota, 2, '.', ''),
                    number_format($b->impuesto_determinado, 2, '.', ''),
                ];
            }
        }
    }

    $nombreCasino = $casinoId == 4 ? 'todos' : (Casino::find($casinoId)->nombre ?? 'desconocido');
    $filename = "iibb_{$nombreCasino}_Registros" . date('Ymd_His') . ".csv";

    $handle = fopen('php://temp', 'r+');
    foreach ($csv as $linea) {
        fputcsv($handle, $linea, ',');
    }
    rewind($handle);
    $contenido = stream_get_contents($handle);
    fclose($handle);

    return response($contenido, 200, [
        'Content-Type'        => 'text/csv; charset=UTF-8',
        'Content-Disposition' => "attachment; filename=\"$filename\"",
        'Cache-Control'       => 'no-store, no-cache'
    ]);
}


public function descargariibbCsvRegistros(Request $request)
{
    $casinoId = $request->query('casino');
    $desde    = $request->query('desde');
    $hasta    = $request->query('hasta');
    $user = Usuario::find(session('id_usuario'));
    $allowedCasinoIds = $user->casinos->pluck('id_casino')->toArray();

    $registros = Registroiibb::with('casinoiibb')
        ->whereIn('casino',$allowedCasinoIds)
        ->when($casinoId && $casinoId != 4, function ($q) use ($casinoId) {
            $q->where('casino', $casinoId);
        })
        ->when($desde, function ($q) use ($desde) {
            $q->where('fecha_iibb', '>=', $desde . '-01');
        })
        ->when($hasta, function ($q) use ($hasta) {
            $q->where('fecha_iibb', '<=', $hasta . '-01');
        })
        ->orderBy('casino')
        ->orderBy('fecha_iibb')
        ->get();

    $csv = [];
    $csv[] = ['Casino', 'Año', 'Mes', 'Fecha Presentación de la Declaración jurada', 'Impuesto Total Determinado', 'Diferencia Mínimo', 'Deducciones', 'Saldo a favor ARCA/ Contribuyente', 'Observaciones'];

    foreach ($registros as $r) {
        setlocale(LC_TIME, 'es_ES.UTF-8');
        $anio     = date('Y', strtotime($r->fecha_iibb));
        $mes      = strftime('%B', strtotime($r->fecha_iibb));
        $pres     = date('d/m/Y', strtotime($r->fecha_presentacion));
        $imp4 = number_format($r->impuesto_total_determinado, 2, '.', '');
        $dif= number_format($r->diferencia_minimo, 2, '.', '');
        $ded= number_format($r->deducciones, 2, '.', '');
        $saldo    = number_format($r->saldo_a_favor_api_contribuyente, 2, '.', '');
        $casino   = $r->casinoiibb ? $r->casinoiibb->nombre : '-';
        $obs      = $r->observacion;

        $csv[] = [
            $casino,
            $anio,
            ucfirst($mes),
            $pres,
            $imp4,
            $dif,
            $ded,
            $saldo,
            $obs
        ];
    }

    $nombreCasino = $casinoId == 4 ? 'todos' : Casino::find($casinoId)->nombre ?? 'desconocido';
    $filename = "iibb_{$nombreCasino}_" . date('Ymd_His') . ".csv";

    $handle = fopen('php://temp', 'r+');
    foreach ($csv as $linea) {
        fputcsv($handle, $linea, ',');
    }
    rewind($handle);
    $contenido = stream_get_contents($handle);
    fclose($handle);

    return response($contenido, 200, [
        'Content-Type'        => 'text/csv; charset=UTF-8',
        'Content-Disposition' => "attachment; filename=\"$filename\"",
        'Cache-Control'       => 'no-store, no-cache'
    ]);
}

public function ultimasAlicuotasiibb(Request $request)
{
    $ultima = Registroiibb::orderBy('id_registroiibb', 'desc')->first();

    return response()->json([
        'alicuota_1' => $ultima ? $ultima->alicuota_1 : null,
        'alicuota_2' => $ultima ? $ultima->alicuota_2 : null,
        'alicuota_3' => $ultima ? $ultima->alicuota_3 : null,

    ]);
}


//DREI

public function guardarDREI(Request $request){


      try {
          DB::beginTransaction();

          $DREI = new RegistroDREI();
          $DREI->fecha_drei = $request->fecha_DREI.'-01';
          $DREI->fecha_presentacion = $request->fecha_DREIPres;
          $DREI->fecha_toma = date('Y-m-d h:i:s', time());
          $DREI->casino = $request->casinoDREI;

          if($request->casinoDREI === '2'){
            $DREI->com_base_imponible = $request->base_imponible_comDREI;
            $DREI->com_alicuota = $request->alicuota_comDREI;
            $DREI->com_subt_imp_det = $request->imp_det_comDREI;

            $DREI->gas_base_imponible = $request->base_imponible_gasDREI;
            $DREI->gas_alicuota = $request->alicuota_gasDREI;
            $DREI->gas_imp_det = $request->imp_det_gasDREI;

            $DREI->expl_base_imponible = $request->base_imponible_explDREI;
            $DREI->expl_alicuota = $request->alicuota_explDREI;
            $DREI->expl_imp_det = $request->imp_det_explDREI;


            $DREI->apyju_base_imponible = $request->base_imponible_apyjDREI;
            $DREI->apyju_alicuota = $request->alicuota_apyjDREI;
            $DREI->apyju_imp_det = $request->imp_det_apyjDREI;

            $DREI->imp_est_y_garage = $request->garageDREI;
            $DREI->bromatologia = $request->bromatologiaDREI;
            $DREI->intereses = $request->interesesDREI;
            $DREI->deducciones =$request->deduccionesDREI;
            $DREI->saldo = $request->saldoDREI;

            $DREI->total_imp_det = $request->imp_tot_csfDREI;
          }else if($request->casinoDREI === '1'){
            $DREI->monto_pagado = $request->monto_pagado_melDREI;

            $DREI->com_base_imponible = $request->base_imponible_melDREI;
            $DREI->com_alicuota = $request->alicuota_melDREI;
            $DREI->com_subt_imp_det = $request->imp_det_melDREI;

            $DREI->gas_base_imponible = $request->base_imponibleO_melDREI;
            $DREI->gas_alicuota = $request->alicuotaO_melDREI;
            $DREI->gas_imp_det = $request->imp_det0_melDREI;

            $DREI->saldo = $request->saldo_melDREI;
          }else{
            $DREI->monto_pagado = $request->monto_pagado_roDREI;
            $DREI->vencimiento_previsto = $request->fecha_DREIVenc;

            $DREI->com_base_imponible = $request->base_imponible_roDREI;
            $DREI->com_alicuota = $request->alicuota_roDREI;
            $DREI->com_subt_imp_det = $request->imp_det_roDREI;

            $DREI->gas_base_imponible = $request->base_imponibleO_roDREI;
            $DREI->gas_alicuota = $request->alicuotaO_roDREI;
            $DREI->gas_imp_det = $request->imp_det0_roDREI;
            $DREI->alicuota_rosario = $request->alicuota_rosarioDREI;

            $DREI->publicidad = $request->publicidadDREI;
            $DREI->ret_percep_otros_pagos = $request->RetDREI;
            $DREI->min_gral = $request->minDREI;
            $DREI->rectificativa_1 = $request->rect1DREI;
            $DREI->rectificativa_2 = $request->rect2DREI;


            $DREI->saldo = $request->total_roDREI;
          }

          $DREI->observacion = $request->obsDREI;
          $DREI->usuario = UsuarioController::getInstancia()->quienSoy()['usuario']['id_usuario'];



          $DREI->save();
          $files = Arr::wrap($request->file('uploadDREI'));
          foreach ($files as $file) {
              if (!($file instanceof UploadedFile) || !$file->isValid()) continue;

              $base = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
              $ext  = $file->getClientOriginalExtension();
              $safe = preg_replace('/\s+/', '_', $base);
              $filename = time().'_'.Str::random(6).'_'.$safe.($ext?'.'.$ext:'');

              $file->storeAs('public/RegistroDREI', $filename);

              $DREI->archivos()->create([
                  'path'       => $filename,
                  'usuario'    => $DREI->usuario,
                  'fecha_toma' => date('Y-m-d H:i:s'),
              ]);
          }
          DB::commit();

          return response()->json(['success' => true, 'id' => $DREI->id_registroDREI]);
      } catch (\Exception $e) {
          DB::rollBack();
          return response()->json(['success' => false, 'error' => $e->getMessage(), 'DREI' => $DREI, 'REQUEST' => $request->all()], 500);
      }

}

public function actualizarDREI(Request $request, $id)
{
    $DREI = RegistroDREI::findOrFail($id);

    $DREI->fecha_drei         = $request->input('fecha_DREI') . '-01';
    $DREI->fecha_presentacion = $request->input('fecha_DREIPres');
    $DREI->casino             = $request->input('casinoDREI');

    $DREI->monto_pagado = null;
    $DREI->vencimiento_previsto = null;

    $DREI->com_base_imponible = null;
    $DREI->com_alicuota       = null;
    $DREI->com_subt_imp_det   = null;

    $DREI->gas_base_imponible = null;
    $DREI->gas_alicuota       = null;
    $DREI->gas_imp_det        = null;

    $DREI->expl_base_imponible = null;
    $DREI->expl_alicuota       = null;
    $DREI->expl_imp_det        = null;

    $DREI->apyju_base_imponible = null;
    $DREI->apyju_alicuota       = null;
    $DREI->apyju_imp_det        = null;

    $DREI->imp_est_y_garage = null;
    $DREI->bromatologia     = null;
    $DREI->intereses        = null;
    $DREI->deducciones      = null;

    $DREI->alicuota_rosario        = null;
    $DREI->publicidad              = null;
    $DREI->ret_percep_otros_pagos  = null;
    $DREI->min_gral                = null;
    $DREI->rectificativa_1         = null;
    $DREI->rectificativa_2         = null;

    $DREI->total_imp_det = null;
    $DREI->saldo         = null;

    $tipo = (string)$request->input('casinoDREI');

    if ($tipo == '2') {
        $DREI->com_base_imponible = $request->base_imponible_comDREI;
        $DREI->com_alicuota       = $request->alicuota_comDREI;
        $DREI->com_subt_imp_det   = $request->imp_det_comDREI;

        $DREI->gas_base_imponible = $request->base_imponible_gasDREI;
        $DREI->gas_alicuota       = $request->alicuota_gasDREI;
        $DREI->gas_imp_det        = $request->imp_det_gasDREI;

        $DREI->expl_base_imponible = $request->base_imponible_explDREI;
        $DREI->expl_alicuota       = $request->alicuota_explDREI;
        $DREI->expl_imp_det        = $request->imp_det_explDREI;

        $DREI->apyju_base_imponible = $request->base_imponible_apyjDREI;
        $DREI->apyju_alicuota       = $request->alicuota_apyjDREI;
        $DREI->apyju_imp_det        = $request->imp_det_apyjDREI;

        $DREI->imp_est_y_garage = $request->garageDREI;
        $DREI->bromatologia     = $request->bromatologiaDREI;
        $DREI->intereses        = $request->interesesDREI;
        $DREI->deducciones      = $request->deduccionesDREI;
        $DREI->saldo =$request->saldoDREI;

        $DREI->total_imp_det = $request->imp_tot_csfDREI;

    } else if ($tipo == '1') {
        $DREI->monto_pagado = $request->monto_pagado_melDREI;

        $DREI->com_base_imponible = $request->base_imponible_melDREI;
        $DREI->com_alicuota       = $request->alicuota_melDREI;
        $DREI->com_subt_imp_det   = $request->imp_det_melDREI;

        $DREI->gas_base_imponible = $request->base_imponibleO_melDREI;
        $DREI->gas_alicuota       = $request->alicuotaO_melDREI;
        $DREI->gas_imp_det        = $request->imp_det0_melDREI;

        $DREI->saldo = $request->saldo_melDREI;

    } else {
        $DREI->monto_pagado        = $request->monto_pagado_roDREI;
        $DREI->vencimiento_previsto= $request->fecha_DREIVenc;

        $DREI->com_base_imponible = $request->base_imponible_roDREI;
        $DREI->com_alicuota       = $request->alicuota_roDREI;
        $DREI->com_subt_imp_det   = $request->base_imponible_roDREI * ($request->alicuota_roDREI / 100);

        $DREI->gas_base_imponible = $request->base_imponibleO_roDREI;
        $DREI->gas_alicuota       = $request->alicuotaO_roDREI;
        $DREI->gas_imp_det        = $request->base_imponibleO_roDREI * ($request->alicuotaO_roDREI / 100);

        $DREI->alicuota_rosario       = $request->alicuota_rosarioDREI;
        $DREI->publicidad             = $request->publicidadDREI;
        $DREI->ret_percep_otros_pagos = $request->RetDREI;
        $DREI->min_gral               = $request->minDREI;
        $DREI->rectificativa_1        = $request->rect1DREI;
        $DREI->rectificativa_2        = $request->rect2DREI;

        $DREI->saldo = $request->total_roDREI;
    }

    $DREI->observacion = $request->obsDREI;

    $DREI->save();

    $saved = 0;
    $files = Arr::wrap($request->file('uploadDREI'));
    foreach ($files as $file) {
        if (!($file instanceof UploadedFile) || !$file->isValid()) continue;

        $base = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
        $ext  = $file->getClientOriginalExtension();
        $safe = preg_replace('/\s+/', '_', $base);
        $name = time().'_'.Str::random(6).'_'.$safe.($ext ? '.'.$ext : '');

        $file->storeAs('public/RegistroDREI', $name);

        $DREI->archivos()->create([
            'path'       => $name,
            'usuario'    => UsuarioController::getInstancia()->quienSoy()['usuario']['id_usuario'],
            'fecha_toma' => date('Y-m-d H:i:s'),
        ]);

        $saved++;
    }

    return response()->json(['success' => true, 'saved_files' => $saved]);
}


public function ultimasDREI(Request $request)
{
$page    = max(1, (int)$request->query('page', 1));
$perPage = max(1, (int)$request->query('page_size', 20));
$user = Usuario::find(session('id_usuario'));
$allowedCasinoIds = $user->casinos->pluck('id_casino')->toArray();

$query = RegistroDREI::with('casinoDREI')
          ->withCount('archivos')
          ->whereIn('casino', $allowedCasinoIds)
          ->orderBy('fecha_drei', 'desc');

if ($c = $request->query('id_casino')) {
  $query->where('casino', $c);
}
if ($desde = $request->query('desde')){
  $desde = $desde."-01";
  $query->where('fecha_drei',">=",$desde);
}
if ($hasta = $request->query('hasta')){
  $hasta = $hasta."-01";
  $query->where('fecha_drei',"<=",$hasta);
}

$total = $query->count();

$registros = $query
    ->skip(($page - 1) * $perPage)
    ->take($perPage)
    ->get();

$datos = $registros->map(function($r) {
    return [
        'id_registroDREI' => $r->id_registroDREI,
        'fecha_drei'   => $r->fecha_drei,
        'fecha_presentacion' => $r->fecha_presentacion,
        'casino'      => $r->casinoDREI ? $r->casinoDREI->nombre : '-',
	       'tiene_archivos' => $r->archivos_count>0,    ];
});

return response()->json([
    'registros'  => $datos,

    'pagination' => [
        'current_page' => $page,
        'per_page'     => $perPage,
        'total'        => $total,
    ],
]);
}


public function archivosDREI($id)
{
    $DREI = RegistroDREI::with('archivos')->findOrFail($id);

    $files = $DREI->archivos->map(function($a){
        return [
            'id'     => $a->id_registro_archivo,
            'nombre' => basename($a->path),
            'url'    => \Illuminate\Support\Facades\Storage::url($a->path),
            'fecha'  => $a->fecha_toma,
        ];
    });

    return response()->json($files->values());
}

public function llenarDREIEdit($id){
  $DREI = RegistroDREI::findOrFail($id);

  return response()->json([
    'fecha'      => is_string($DREI->fecha_drei) ? substr($DREI->fecha_drei,0,7)
                                                 : ($DREI->fecha_drei)->format('Y-m'),
    'fecha_pres' => is_string($DREI->fecha_presentacion) ? substr($DREI->fecha_presentacion,0,10)
                                                         : ($DREI->fecha_presentacion)->format('Y-m-d'),

    'casino'     => $DREI->casino,              // 1=MEL, 2=CSF, 3=RO
    'obs'        => $DREI->observacion,
    'saldo'      => $DREI->saldo,

    // MEL y RO
    'monto_pagado'          => $DREI->monto_pagado,
    'vencimiento_previsto'  => $DREI->vencimiento_previsto,

    // Comercio
    'com_base_imponible'    => $DREI->com_base_imponible,
    'com_alicuota'          => $DREI->com_alicuota,
    'com_subt_imp_det'      => $DREI->com_subt_imp_det,

    // Gastron./Otras act.
    'gas_base_imponible'    => $DREI->gas_base_imponible,
    'gas_alicuota'          => $DREI->gas_alicuota,
    'gas_imp_det'           => $DREI->gas_imp_det,

    // CSF: explotación + apyj
    'expl_base_imponible'   => $DREI->expl_base_imponible,
    'expl_alicuota'         => $DREI->expl_alicuota,
    'expl_imp_det'          => $DREI->expl_imp_det,

    'apyju_base_imponible'  => $DREI->apyju_base_imponible,
    'apyju_alicuota'        => $DREI->apyju_alicuota,
    'apyju_imp_det'         => $DREI->apyju_imp_det,

    'imp_tot_csfDREI'       => $DREI->total_imp_det,

    // CSF extras
    'imp_est_y_garage'      => $DREI->imp_est_y_garage,
    'bromatologia'          => $DREI->bromatologia,
    'intereses'             => $DREI->intereses,
    'deducciones'           => $DREI->deducciones,

    // RO extras
    'alicuota_rosario'      => $DREI->alicuota_rosario,
    'publicidad'            => $DREI->publicidad,
    'ret_percep_otros_pagos'=> $DREI->ret_percep_otros_pagos,
    'min_gral'              => $DREI->min_gral,
    'rectificativa_1'       => $DREI->rectificativa_1,
    'rectificativa_2'       => $DREI->rectificativa_2,
  ]);
}



public function eliminarDREI($id){
  $DREI = RegistroDREI::findOrFail($id);
  if(is_null($DREI)) return 0;
  RegistroDREI::destroy($id);
  return 1;
}

public function llenarDREI($id){
  $DREI = RegistroDREI::with('casinoDREI')->findOrFail($id);
  if(is_null($DREI)) return 0;

  return response()->json([
    'fecha' => $DREI->fecha_drei,
    'fecha_pres' => $DREI->fecha_presentacion,
    'casino' => $DREI->casinoDREI ? $DREI->casinoDREI->nombre : '-',
    'garage' => $DREI->imp_est_y_garage,
    'com_alicuota' => $DREI->com_alicuota,
    'com_base' => $DREI->com_base_imponible,
    'com_subt' => $DREI->com_subt_imp_det,
    'gas_base' => $DREI->gas_base_imponible,
    'gas_alicuota' => $DREI->gas_alicuota,
    'gas_imp' => $DREI->gas_imp_det,
    'expl_base' => $DREI->expl_base_imponible,
    'expl_alicuota' => $DREI->expl_alicuota,
    'expl_imp' => $DREI->expl_imp_det,
    'apyju_base' => $DREI->apyju_base_imponible,
    'apyju_alicuota' => $DREI->apyju_alicuota,
    'apyju_imp' => $DREI->apyju_imp_det,
    'bromatologia' => $DREI->bromatologia,
    'deducciones' => $DREI->deducciones,
    'total_imp_det' => $DREI->total_imp_det,
    'intereses' => $DREI->intereses,
    'saldo' => $DREI->saldo,
    'obs' => $DREI->observacion,
    'monto_pagado' => $DREI->monto_pagado,
    'vencimiento_previsto' =>$DREI->vencimiento_previsto,
    'alicuota_rosario' =>$DREI->alicuota_rosario,
    'publicidad' => $DREI->publicidad,
    'ret' => $DREI->ret_percep_otros_pagos,
    'min_gral' => $DREI->min_gral,
    'rect1' => $DREI->rectificativa_1,
    'rect2' => $DREI->rectificativa_2,
  ]);

}

public function ultimasAlicuotasDREI(Request $request)
{
    $ultima = RegistroDREI::orderBy('id_registrodrei', 'desc')->first();

    return response()->json([
        'com_alicuota' => $ultima ? $ultima->com_alicuota : null,
        'gas_alicuota' => $ultima ? $ultima->gas_alicuota : null,
        'expl_alicuota' => $ultima ? $ultima->expl_alicuota : null,
        'apyju_alicuota' => $ultima ? $ultima->apyju_alicuota : null,

    ]);
}
public function descargarDREICsv(Request $request)
{
    $casinoId = $request->query('casino');
    $desde    = $request->query('desde');
    $hasta    = $request->query('hasta');

    $user = Usuario::find(session('id_usuario'));
    $allowedCasinoIds = $user->casinos->pluck('id_casino')->toArray();

    $registros = RegistroDREI::with('casinoDREI')
        ->whereIn('casino', $allowedCasinoIds)
        ->when($casinoId && (int)$casinoId !== 4, function ($q) use ($casinoId) {
            $q->where('casino', $casinoId);
        })
        ->when($desde, function ($q) use ($desde) {
            $q->where('fecha_drei', '>=', $desde . '-01');
        })
        ->when($hasta, function ($q) use ($hasta) {
            $q->where('fecha_drei', '<=', $hasta . '-31');
        })
        ->orderBy('casino')
        ->orderBy('fecha_drei')
        ->get();

    setlocale(LC_TIME, 'es_ES.UTF-8', 'es_AR.UTF-8', 'es_ES', 'es_AR');

    $csv = [];
    $csv[] = ['Casino','Año','Mes','Fecha Presentación','Fecha Vencimiento','Rubro','Concepto','Valor','Observaciones'];

    foreach ($registros as $r) {
        $casino   = $r->casinoDREI ? $r->casinoDREI->nombre : '-';
        $anio     = date('Y', strtotime($r->fecha_drei));
        $mes      = ucfirst(strftime('%B', strtotime($r->fecha_drei)));
        $pres     = $r->fecha_presentacion ? date('d/m/Y', strtotime($r->fecha_presentacion)) : '';
        $venc     = $r->vencimiento_previsto ? date('d/m/Y', strtotime($r->vencimiento_previsto)) : '';
        $obs      = (string)($r->observacion ?? '');

        $push = function($rubro, $concepto, $valor) use (&$csv, $casino, $anio, $mes, $pres, $venc, $obs) {
            if ($valor === null || $valor === '') return;
            $val = is_numeric($valor) ? number_format((float)$valor, 2, '.', '') : (string)$valor;
            $csv[] = [$casino, $anio, $mes, $pres, $venc, $rubro, $concepto, $val, $obs];
        };

        if ((int)$r->casino === 1) {
            $push('General','Monto Pagado', $r->monto_pagado);
            $push('Juegos','Base Imponible', $r->com_base_imponible);
            $push('Juegos','Alicuota', $r->com_alicuota);
            $push('Juegos','Impuesto Determinado', $r->com_subt_imp_det);
            $push('Otras Actividades','Base Imponible', $r->gas_base_imponible);
            $push('Otras Actividades','Alicuota', $r->gas_alicuota);
            $push('Otras Actividades','Impuesto Determinado', $r->gas_imp_det);
            $push('General','Saldo', $r->saldo);
        } elseif ((int)$r->casino === 2) {
            $push('Comercio','Base Imponible', $r->com_base_imponible);
            $push('Comercio','Alicuota', $r->com_alicuota);
            $push('Comercio','Impuesto Determinado', $r->com_subt_imp_det);
            $push('Gastronomía','Base Imponible', $r->gas_base_imponible);
            $push('Gastronomía','Alicuota', $r->gas_alicuota);
            $push('Gastronomía','Impuesto Determinado', $r->gas_imp_det);
            $push('Explotación Casinos y Bingos','Base Imponible', $r->expl_base_imponible);
            $push('Explotación Casinos y Bingos','Alicuota', $r->expl_alicuota);
            $push('Explotación Casinos y Bingos','Impuesto Determinado', $r->expl_imp_det);
            $push('Apuestas y Juegos','Base Imponible', $r->apyju_base_imponible);
            $push('Apuestas y Juegos','Alicuota', $r->apyju_alicuota);
            $push('Apuestas y Juegos','Impuesto Determinado', $r->apyju_imp_det);
            $push('General','Impuesto Est. y Garage', $r->imp_est_y_garage);
            $push('General','Bromatología', $r->bromatologia);
            $push('General','Deducciones', $r->deducciones);
            $push('General','Intereses', $r->intereses);
            $push('General','Total Impuesto Determinado', $r->total_imp_det);
            $push('General','Saldo', $r->saldo);
        } else {
            $push('General','Monto Pagado', $r->monto_pagado);

            $push('General','Saldo', $r->saldo);
        }
    }

    $nombreCasino = ((int)$casinoId === 4 || !$casinoId)
        ? 'todos'
        : (Casino::find($casinoId)->nombre ?? 'desconocido');

    $filename = "DREI_{$nombreCasino}_" . date('Ymd_His') . ".csv";

    $handle = fopen('php://temp', 'r+');
    foreach ($csv as $linea) {
        fputcsv($handle, $linea, ',');
    }
    rewind($handle);
    $contenido = stream_get_contents($handle);
    fclose($handle);

    return response($contenido, 200, [
        'Content-Type'        => 'text/csv; charset=UTF-8',
        'Content-Disposition' => "attachment; filename=\"$filename\"",
        'Cache-Control'       => 'no-store, no-cache'
    ]);
}


public function descargarDREIXlsx(Request $request)
{
    $casinoId = $request->query('casino');
    $desde = $request->query('desde');
    $hasta = $request->query('hasta');

    $casino = Casino::findOrFail($casinoId);

    $query = RegistroDREI::select([
        DB::raw("YEAR(fecha_drei) AS anio"),
        DB::raw("MONTHNAME(fecha_drei) AS Mes"),
        DB::raw("DATE_FORMAT(fecha_presentacion,'%d/%m/%Y') AS `Fecha Presentación`"),
        DB::raw("imp_est_y_garage AS 'Garage'"),
        DB::raw("com_base_imponible AS 'Base 1'"),
        DB::raw("com_alicuota AS Alicuota1"),
        DB::raw("com_subt_imp_det AS 'Impuesto1'"),
        DB::raw("gas_base_imponible AS 'Base 2'"),
        DB::raw("gas_alicuota AS Alicuota2"),
        DB::raw("gas_imp_det AS 'Impuesto2'"),
        DB::raw("expl_base_imponible AS 'Base 3'"),
        DB::raw("expl_alicuota AS Alicuota3"),
        DB::raw("expl_imp_det AS 'Impuesto3'"),
        DB::raw("apyju_base_imponible AS 'Base 4'"),
        DB::raw("apyju_alicuota AS Alicuota4"),
        DB::raw("apyju_imp_det AS 'Impuesto4'"),
        DB::raw("bromatologia AS 'Bromatologia'"),
        DB::raw("deducciones AS Deducciones"),
        DB::raw("total_imp_det AS 'Impuesto total determinado'"),
        DB::raw("intereses AS Intereses"),
        DB::raw("saldo AS 'Saldo'"),
        DB::raw("observacion AS Observaciones"),
        DB::raw("monto_pagado AS Monto"),
        DB::raw("vencimiento_previsto AS Vencimiento"),
        DB::raw("alicuota_rosario AS 'Alicuota Rosario'"),
        DB::raw("publicidad AS Publicidad"),
        DB::raw("ret_percep_otros_pagos AS Retenciones"),
        DB::raw("min_gral AS 'Minimo General'"),
        DB::raw("rectificativa_1 AS 'Rectificativa 1'"),
        DB::raw("rectificativa_2 AS 'Rectificativa 2'"),
    ])
    ->where('casino', $casinoId);

    if ($desde) {
        $query->where('fecha_drei', '>=', $desde . '-01');
    }

    if ($hasta) {
        $query->where('fecha_drei', '<=', $hasta . '-31');
    }


    $columnasPorCasino = [
    1 => [ // Casino 1: MEL
        'Meses', 'Monto Pagado', 'Presentación y Pago', 'Base Imponible Juegos', 'Alicuota', 'Impuesto Determinado',
        'Base Imponible Otras Actividades', 'Alicuota', 'Impuesto Determinado', 'Saldo', 'Observaciones'
    ],
    2 => [ // Casino 2: CSF
      'Meses', 'Presentación y Pago', 'Servicio de Playa de Estacionamiento y Garage - Impuesto Determinado', 'Base 1', 'Alicuota1', 'Impuesto1', 'Base 2', 'Alicuota2', 'Impuesto2',
      'Base 3', 'Alicuota3', 'Impuesto3', 'Base 4', 'Alicuota4', 'Impuesto4',
      'Bromatologia', 'Deducciones', 'Impuesto total determinado', 'Intereses', 'Saldo a Favor', 'Observaciones'
    ],
    3 => [ // Casino 3: RO
        'Meses', 'Monto Pagado Total', 'Vencimiento Previsto',
        'Presentación y Pago',
        //'Base Imponible Juegos', 'Alicuota', 'Impueso Determinado',
         //'Base Imponible Otras Actividades', 'Alicuota', 'Impuesto Determinado', 'Publicidad', 'Ret/Percep/ Otros Pagos', 'Min. Gral.',
         'Total',
         // 'Rectificativa 1',
         //'Rectificativa 2',
         'Observaciones'
    ]
];



    $datos = $query->orderBy('fecha_drei')->get()->groupBy('anio');




    $filename = "registro_DREI_" . str_replace(' ', '_', strtolower($casino->nombre));

    return Excel::create($filename, function($excel) use ($datos, $casino, $casinoId, $columnasPorCasino) {
        $excel->sheet('DREI', function($sheet) use ($datos, $casino, $casinoId, $columnasPorCasino) {
            $fila = 1;

            if($casinoId==1){
              $sheet->row($fila,$columnasPorCasino[$casinoId]);

              $sheet->cells("A1:K1", function($cells) use ($casinoId) {
                  switch ($casinoId) {
                      case 1:
                          $color = '#008f39'; break;
                      case 2:
                          $color = '#ff0000'; break;
                      case 3:
                          $color = '#ffff00'; break;
                      default:
                          $color = '#222222'; break;
                  }

                  $cells->setBackground($color);
                  $cells->setFontColor('#000000');
                  $cells->setFontWeight('bold');
                  $cells->setAlignment('center');

              });
              $sheet->getStyle("A1:K1")->getAlignment()->setWrapText(true)->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
              $sheet->setHeight(1,35);
              $sheet->cells("A1:K999", function($cells){
                  $cells->setFontFamily('Arial');
                  $cells->setFontSize(10);
              });

              $fila++;

              foreach ($datos as $anio => $registros) {
                  $sheet->mergeCells("A{$fila}:K{$fila}");
                  $sheet->setCellValue("A{$fila}", $anio);
                  $sheet->cells("A{$fila}:K{$fila}", function($cells){
                      $cells->setBackground('#CCCCCC');
                      $cells->setFontWeight('bold');
                      $cells->setFontSize(13);
                      $cells->setAlignment('center');
                  });
                  $sheet->getStyle("A{$fila}:K{$fila}")->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
                  $sheet->setHeight($fila, 20);
                  $fila++;

                  foreach ($registros as $r) {
                      setlocale(LC_TIME, 'es_ES.UTF-8');
                      $mesEsp = ucfirst(strftime('%B', strtotime($r->{'Mes'} . ' 1')));

                      $sheet->row($fila, [
                          $mesEsp,
                          "$ " . number_format($r->{'Monto'}, 2, '.', ''),
                          $r->{'Fecha Presentación'},
                          "$ " . number_format($r->{'Base 1'}, 2, '.', ''),
                          number_format($r->{'Alicuota1'}, 2, '.', '') ." %",
                          "$ " . number_format($r->{'Impuesto1'}, 2, '.', ''),
                          "$ " . number_format($r->{'Base 2'}, 2, '.', ''),
                          number_format($r->{'Alicuota2'}, 2, '.', '') ." %",
                          "$ " . number_format($r->{'Impuesto2'}, 2, '.', ''),
                          "$ " . number_format($r->{'Saldo'}, 2, '.', ''),
                          $r->{'Observaciones'}
                      ]);

                      $sheet->cells("A{$fila}", function($cells){
                          $cells->setBackground('#FFFF99');
                          $cells->setFontWeight('bold');
                          $cells->setAlignment('left');
                      });

                      $sheet->cells("B{$fila}:U{$fila}", function($cells){
                          $cells->setAlignment('center');
                      });

                      $sheet->getStyle("A{$fila}:U{$fila}")->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
                      $fila++;
                  }
              }

              $sheet->getStyle("A1:K1")->applyFromArray([
                  'borders' => [
                      'allborders' => [
                          'style' => PHPExcel_Style_Border::BORDER_THICK,
                          'color' => ['argb' => 'FF000000']
                      ]
                  ]
              ]);


              $sheet->getStyle("A3:K" . ($fila - 1))->applyFromArray([
                  'borders' => [
                      'allborders' => [
                          'style' => PHPExcel_Style_Border::BORDER_THIN,
                          'color' => ['argb' => 'FF000000']
                      ]
                  ]
              ]);

              $anchos = [
                  9, 17, 19, 14, 18, 18, 14, 18,
                  18, 14, 20, 18, 14, 15, 18, 20, 20,
                  20, 20, 20, 20, 20, 20, 20, 20, 20
              ];
              foreach (range('A', 'U') as $i => $col) {
                  $sheet->setWidth($col, $anchos[$i]);
              }

              $sheet->setFreeze('A2');
            }
            else if($casinoId===2){
            $sheet->mergeCells("A1:A2");
            $sheet->mergeCells("B1:B2");
            $sheet->mergeCells("C1:C2");
            $sheet->mergeCells("P1:P2");
            $sheet->mergeCells("Q1:Q2");
            $sheet->mergeCells("R1:R2");
            $sheet->mergeCells("S1:S2");
            $sheet->mergeCells("T1:T2");
            $sheet->mergeCells("U1:U2");
            $sheet->mergeCells("D1:F1");
            $sheet->mergeCells("G1:I1");
            $sheet->mergeCells("J1:L1");
            $sheet->mergeCells("M1:O1");

            $sheet->row($fila,$columnasPorCasino[$casinoId]);

            $sheet->cells("A1:U2", function($cells) use ($casinoId) {
                switch ($casinoId) {
                    case 1:
                        $color = '#008f39'; break;
                    case 2:
                        $color = '#ff0000'; break;
                    case 3:
                        $color = '#ffff00'; break;
                    default:
                        $color = '#222222'; break;
                }

                $cells->setBackground($color);
                $cells->setFontColor('#000000');
                $cells->setFontWeight('bold');
                $cells->setAlignment('center');

            });
            $sheet->getStyle("A1:U2")->getAlignment()->setWrapText(true)->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
            $sheet->setHeight(1, 15);
            $sheet->cells("A1:U999", function($cells){
                $cells->setFontFamily('Arial');
                $cells->setFontSize(10);
            });
            $sheet->setHeight(2,40);
            $fila++;


            $sheet->row($fila, ['','','','Base Imponible','Alicuota','Subtotal Impuesto Determinado',
                'Base Imponible','Alicuota','Impuesto Determinado','Base Imponible','Alicuota','Impuesto Determinado',
                'Base Imponible','Alicuota','Impuesto Determinado']);

            $fila++;

            foreach ($datos as $anio => $registros) {
                $sheet->mergeCells("A{$fila}:U{$fila}");
                $sheet->setCellValue("A{$fila}", $anio);
                $sheet->cells("A{$fila}:U{$fila}", function($cells){
                    $cells->setBackground('#CCCCCC');
                    $cells->setFontWeight('bold');
                    $cells->setFontSize(13);
                    $cells->setAlignment('center');
                });
                $sheet->getStyle("A{$fila}:U{$fila}")->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
                $sheet->setHeight($fila, 20);
                $fila++;

                foreach ($registros as $r) {
                    setlocale(LC_TIME, 'es_ES.UTF-8');
                    $mesEsp = ucfirst(strftime('%B', strtotime($r->{'Mes'} . ' 1')));

                    $sheet->row($fila, [
                        $mesEsp,
                        $r->{'Fecha Presentación'},
                        "$ " . number_format($r->{'Garage'}, 2, '.', ''),
                        "$ " . number_format($r->{'Base 1'}, 2, '.', ''),
                        number_format($r->{'Alicuota1'}, 2, '.', '') ." %",
                        "$ " . number_format($r->{'Impuesto1'}, 2, '.', ''),
                        "$ " . number_format($r->{'Base 2'}, 2, '.', ''),
                        number_format($r->{'Alicuota2'}, 2, '.', '') ." %",
                        "$ " . number_format($r->{'Impuesto2'}, 2, '.', ''),
                        "$ " . number_format($r->{'Base 3'}, 2, '.', ''),
                        number_format($r->{'Alicuota3'}, 2, '.', '') ." %",
                        "$ " . number_format($r->{'Impuesto3'}, 2, '.', ''),
                        "$ " . number_format($r->{'Base 4'}, 2, '.', ''),
                        number_format($r->{'Alicuota4'}, 2, '.', '') ." %",
                        "$ " . number_format($r->{'Impuesto4'}, 2, '.', ''),
                        "$ " . number_format($r->{'Bromatologia'}, 2, '.', ''),
                        "$ " . number_format($r->{'Deducciones'}, 2, '.', ''),
                        "$ " . number_format($r->{'Impuesto Total Determinado'}, 2, '.', ''),
                        "$ " . number_format($r->{'Intereses'}, 2, '.', ''),
                        "$ " . number_format($r->{'Saldo'}, 2, '.', ''),
                        $r->{'Observaciones'}
                    ]);

                    $sheet->cells("A{$fila}", function($cells){
                        $cells->setBackground('#FFFF99');
                        $cells->setFontWeight('bold');
                        $cells->setAlignment('left');
                    });

                    $sheet->cells("B{$fila}:U{$fila}", function($cells){
                        $cells->setAlignment('center');
                    });

                    $sheet->getStyle("A{$fila}:U{$fila}")->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
                    $fila++;
                }
            }

            $sheet->getStyle("A1:U1")->applyFromArray([
                'borders' => [
                    'allborders' => [
                        'style' => PHPExcel_Style_Border::BORDER_THICK,
                        'color' => ['argb' => 'FF000000']
                    ]
                ]
            ]);
            $sheet->getStyle("D2:O2")->applyFromArray([
                'borders' => [
                    'allborders' => [
                        'style' => PHPExcel_Style_Border::BORDER_THICK,
                        'color' => ['argb' => 'FF000000']
                    ]
                ]
            ]);

            $sheet->getStyle("A3:U" . ($fila - 1))->applyFromArray([
                'borders' => [
                    'allborders' => [
                        'style' => PHPExcel_Style_Border::BORDER_THIN,
                        'color' => ['argb' => 'FF000000']
                    ]
                ]
            ]);

            $anchos = [
                9, 17, 19, 14, 18, 16, 14, 18,
                16, 14, 20, 18, 14, 15, 18, 20, 20,
                20, 20, 20, 20, 20, 20, 20, 20, 20
            ];
            foreach (range('A', 'U') as $i => $col) {
                $sheet->setWidth($col, $anchos[$i]);
            }

            $sheet->setFreeze('A3');
          }else {
            $sheet->row($fila,$columnasPorCasino[$casinoId]);

            $sheet->cells("A1:F1", function($cells) use ($casinoId) {
                switch ($casinoId) {
                    case 1:
                        $color = '#008f39'; break;
                    case 2:
                        $color = '#ff0000'; break;
                    case 3:
                        $color = '#ffff00'; break;
                    default:
                        $color = '#222222'; break;
                }

                $cells->setBackground($color);
                $cells->setFontColor('#000000');
                $cells->setFontWeight('bold');
                $cells->setAlignment('center');

            });
            $sheet->getStyle("A1:F1")->getAlignment()->setWrapText(true)->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
            $sheet->setHeight(1,35);
            $sheet->cells("A1:F999", function($cells){
                $cells->setFontFamily('Arial');
                $cells->setFontSize(10);
            });

            $fila++;

            foreach ($datos as $anio => $registros) {
                $sheet->mergeCells("A{$fila}:F{$fila}");
                $sheet->setCellValue("A{$fila}", $anio);
                $sheet->cells("A{$fila}:F{$fila}", function($cells){
                    $cells->setBackground('#CCCCCC');
                    $cells->setFontWeight('bold');
                    $cells->setFontSize(13);
                    $cells->setAlignment('center');
                });
                $sheet->getStyle("A{$fila}:F{$fila}")->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
                $sheet->setHeight($fila, 20);
                $fila++;

                foreach ($registros as $r) {
                    setlocale(LC_TIME, 'es_ES.UTF-8');
                    $mesEsp = ucfirst(strftime('%B', strtotime($r->{'Mes'} . ' 1')));

                    $sheet->row($fila, [
                        $mesEsp,
                        "$ " . number_format($r->{'Monto'}, 2, '.', ''),
                        $r->{'Vencimiento'},
                        $r->{'Fecha Presentación'},
                        //"$ " . number_format($r->{'Base 1'}, 2, '.', ''),
                        //number_format($r->{'Alicuota1'}, 2, '.', '') ." %",
                        //"$ " . number_format($r->{'Impuesto1'}, 2, '.', ''),
                        //"$ " . number_format($r->{'Base 2'}, 2, '.', ''),
                        //number_format($r->{'Alicuota2'}, 2, '.', '') ." %, ".number_format($r->{'Alicuota Rosario'}, 2, '.', '')." % y m2",
                        //"$ " . number_format($r->{'Impuesto2'}, 2, '.', ''),
                        //"$ " . number_format($r->{'Publicidad'}, 2, '.', ''),
                        //"$ " . number_format($r->{'Retenciones'}, 2, '.', ''),
                        //"$ " . number_format($r->{'Minimo General'}, 2, '.', ''),

                        "$ " . number_format($r->{'Saldo'}, 2, '.', ''),
                        //"$ " . number_format($r->{'Rectificativa 1'}, 2, '.', ''),
                        //"$ " . number_format($r->{'Rectificativa 2'}, 2, '.', ''),
                        $r->{'Observaciones'}
                    ]);

                    $sheet->cells("A{$fila}", function($cells){
                        $cells->setBackground('#FFFF99');
                        $cells->setFontWeight('bold');
                        $cells->setAlignment('left');
                    });

                    $sheet->cells("B{$fila}:F{$fila}", function($cells){
                        $cells->setAlignment('center');
                    });

                    $sheet->getStyle("A{$fila}:F{$fila}")->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
                    $fila++;
                }
            }

            $sheet->getStyle("A1:F1")->applyFromArray([
                'borders' => [
                    'allborders' => [
                        'style' => PHPExcel_Style_Border::BORDER_THICK,
                        'color' => ['argb' => 'FF000000']
                    ]
                ]
            ]);


            $sheet->getStyle("A3:F" . ($fila - 1))->applyFromArray([
                'borders' => [
                    'allborders' => [
                        'style' => PHPExcel_Style_Border::BORDER_THIN,
                        'color' => ['argb' => 'FF000000']
                    ]
                ]
            ]);

            $anchos = [
                9, 17, 19, 14, 18, 18, 14, 18,
                18, 14, 20, 18, 14, 15, 18, 20, 20,
                20, 20, 20, 20, 20, 20, 20, 20, 20
            ];
            foreach (range('A', 'F') as $i => $col) {
                $sheet->setWidth($col, $anchos[$i]);
            }

            $sheet->setFreeze('A2');
          }
          });

  })->export('xlsx');
}

public function descargarDREIXlsxTodos(Request $request)
{
    $desde = $request->query('desde');
    $hasta = $request->query('hasta');

    $user = Usuario::find(session('id_usuario'));
    $casinos = $user->casinos->pluck('nombre', 'id_casino');

    $datos = RegistroDREI::select([
        DB::raw("YEAR(fecha_drei) AS anio"),
        DB::raw("MONTHNAME(fecha_drei) AS Mes"),
        DB::raw("DATE_FORMAT(fecha_presentacion,'%d/%m/%Y') AS `Fecha Presentación`"),
        DB::raw("imp_est_y_garage AS 'Garage'"),
        DB::raw("com_base_imponible AS 'Base 1'"),
        DB::raw("com_alicuota AS Alicuota1"),
        DB::raw("com_subt_imp_det AS 'Impuesto1'"),
        DB::raw("gas_base_imponible AS 'Base 2'"),
        DB::raw("gas_alicuota AS Alicuota2"),
        DB::raw("gas_imp_det AS 'Impuesto2'"),
        DB::raw("expl_base_imponible AS 'Base 3'"),
        DB::raw("expl_alicuota AS Alicuota3"),
        DB::raw("expl_imp_det AS 'Impuesto3'"),
        DB::raw("apyju_base_imponible AS 'Base 4'"),
        DB::raw("apyju_alicuota AS Alicuota4"),
        DB::raw("apyju_imp_det AS 'Impuesto4'"),
        DB::raw("bromatologia AS 'Bromatologia'"),
        DB::raw("deducciones AS Deducciones"),
        DB::raw("total_imp_det AS 'Impuesto total determinado'"),
        DB::raw("intereses AS Intereses"),
        DB::raw("saldo AS 'Saldo'"),
        DB::raw("observacion AS Observaciones"),
        DB::raw("monto_pagado AS Monto"),
        DB::raw("vencimiento_previsto AS Vencimiento"),
        DB::raw("alicuota_rosario AS 'Alicuota Rosario'"),
        DB::raw("publicidad AS Publicidad"),
        DB::raw("ret_percep_otros_pagos AS Retenciones"),
        DB::raw("min_gral AS 'Minimo General'"),
        DB::raw("rectificativa_1 AS 'Rectificativa 1'"),
        DB::raw("rectificativa_2 AS 'Rectificativa 2'"),
        DB::raw("casino AS Casino"),

    ])
      ->when($desde, function($q) use ($desde) {
          return $q->where('fecha_drei', '>=', $desde . '-01');
      })
      ->when($hasta, function($q) use ($hasta) {
          return $q->where('fecha_drei', '<=', $hasta . '-31');
      })
      ->orderBy('fecha_drei')
      ->get();

      $columnasPorCasino = [
      1 => [ // Casino 1: MEL
          'Meses', 'Monto Pagado', 'Presentación y Pago', 'Base Imponible Juegos', 'Alicuota', 'Impuesto Determinado',
          'Base Imponible Otras Actividades', 'Alicuota', 'Impuesto Determinado', 'Saldo', 'Observaciones'
      ],
      2 => [ // Casino 2: CSF
        'Meses', 'Presentación y Pago', 'Servicio de Playa de Estacionamiento y Garage - Impuesto Determinado', 'Base 1', 'Alicuota1', 'Impuesto1', 'Base 2', 'Alicuota2', 'Impuesto2',
        'Base 3', 'Alicuota3', 'Impuesto3', 'Base 4', 'Alicuota4', 'Impuesto4',
        'Bromatologia', 'Deducciones', 'Impuesto total determinado', 'Intereses', 'Saldo a Favor', 'Observaciones'
      ],
      3 => [ // Casino 3: RO
          'Meses', 'Monto Pagado Total', 'Vencimiento Previsto', 'Presentación y Pago',
          //'Base Imponible Juegos', 'Alicuota', 'Impueso Determinado',
           //'Base Imponible Otras Actividades', 'Alicuota', 'Impuesto Determinado', 'Publicidad', 'Ret/Percep/ Otros Pagos', 'Min. Gral.',
            'Total',
            // 'Rectificativa 1',
          //   'Rectificativa 2',
              'Observaciones'
      ]
  ];
    return Excel::create('registro_DREI_todos', function($excel) use ($casinos, $datos, $columnasPorCasino) {
        foreach($casinos as $casinoId => $casinoNombre){

          if($casinoNombre=="Rosario"){
            $excel->sheet($casinoNombre, function($sheet) use ($datos, $casinoId, $columnasPorCasino) {
              $datosFiltrados = $datos->where('Casino', $casinoId)->groupBy('anio');
              $fila = 1;
              $sheet->row($fila,$columnasPorCasino[$casinoId]);

              $sheet->cells("A1:J1", function($cells) use ($casinoId) {
                  switch ($casinoId) {
                      case 1:
                          $color = '#008f39'; break;
                      case 2:
                          $color = '#ff0000'; break;
                      case 3:
                          $color = '#ffff00'; break;
                      default:
                          $color = '#222222'; break;
                  }

                  $cells->setBackground($color);
                  $cells->setFontColor('#000000');
                  $cells->setFontWeight('bold');
                  $cells->setAlignment('center');

              });
              $sheet->getStyle("A1:F1")->getAlignment()->setWrapText(true)->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
              $sheet->setHeight(1,35);
              $sheet->cells("A1:F999", function($cells){
                  $cells->setFontFamily('Arial');
                  $cells->setFontSize(10);
              });

              $fila++;

              foreach ($datosFiltrados as $anio => $registros) {
                  $sheet->mergeCells("A{$fila}:F{$fila}");
                  $sheet->setCellValue("A{$fila}", $anio);
                  $sheet->cells("A{$fila}:F{$fila}", function($cells){
                      $cells->setBackground('#CCCCCC');
                      $cells->setFontWeight('bold');
                      $cells->setFontSize(13);
                      $cells->setAlignment('center');
                  });
                  $sheet->getStyle("A{$fila}:F{$fila}")->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
                  $sheet->setHeight($fila, 20);
                  $fila++;

                  foreach ($registros as $r) {
                      setlocale(LC_TIME, 'es_ES.UTF-8');
                      $mesEsp = ucfirst(strftime('%B', strtotime($r->{'Mes'} . ' 1')));

                      $sheet->row($fila, [
                          $mesEsp,
                          "$ " . number_format($r->{'Monto'}, 2, '.', ''),
                          $r->{'Vencimiento'},
                          $r->{'Fecha Presentación'},
                          //"$ " . number_format($r->{'Base 1'}, 2, '.', ''),
                          //number_format($r->{'Alicuota1'}, 2, '.', '') ." %",
                          //"$ " . number_format($r->{'Impuesto1'}, 2, '.', ''),
                          //"$ " . number_format($r->{'Base 2'}, 2, '.', ''),
                          //number_format($r->{'Alicuota2'}, 2, '.', '') ." %, ".number_format($r->{'Alicuota Rosario'}, 2, '.', '')." % y m2",
                          //"$ " . number_format($r->{'Impuesto2'}, 2, '.', ''),
                          //"$ " . number_format($r->{'Publicidad'}, 2, '.', ''),
                        //  "$ " . number_format($r->{'Retenciones'}, 2, '.', ''),
                        //  "$ " . number_format($r->{'Minimo General'}, 2, '.', ''),

                          "$ " . number_format($r->{'Saldo'}, 2, '.', ''),
                          //"$ " . number_format($r->{'Rectificativa 1'}, 2, '.', ''),
                          //"$ " . number_format($r->{'Rectificativa 2'}, 2, '.', ''),

                          $r->{'Observaciones'}
                      ]);

                      $sheet->cells("A{$fila}", function($cells){
                          $cells->setBackground('#FFFF99');
                          $cells->setFontWeight('bold');
                          $cells->setAlignment('left');
                      });

                      $sheet->cells("B{$fila}:F{$fila}", function($cells){
                          $cells->setAlignment('center');
                      });

                      $sheet->getStyle("A{$fila}:F{$fila}")->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
                      $fila++;
                  }
              }

              $sheet->getStyle("A1:F1")->applyFromArray([
                  'borders' => [
                      'allborders' => [
                          'style' => PHPExcel_Style_Border::BORDER_THICK,
                          'color' => ['argb' => 'FF000000']
                      ]
                  ]
              ]);


              $sheet->getStyle("A3:F" . ($fila - 1))->applyFromArray([
                  'borders' => [
                      'allborders' => [
                          'style' => PHPExcel_Style_Border::BORDER_THIN,
                          'color' => ['argb' => 'FF000000']
                      ]
                  ]
              ]);

              $anchos = [
                  9, 17, 19, 14, 18, 18, 14, 18,
                  18, 14, 20, 18, 14, 15, 18, 20, 20,
                  20, 20, 20, 20, 20, 20, 20, 20, 20
              ];
              foreach (range('A', 'F') as $i => $col) {
                  $sheet->setWidth($col, $anchos[$i]);
              }

              $sheet->setFreeze('A2');
            });
          }else if($casinoNombre=="Melincué"){
            $excel->sheet($casinoNombre, function($sheet) use ($datos, $casinoId, $columnasPorCasino) {
              $datosFiltrados = $datos->where('Casino', $casinoId)->groupBy('anio');
              $fila = 1;
              $sheet->row($fila,$columnasPorCasino[$casinoId]);

              $sheet->cells("A1:K1", function($cells) use ($casinoId) {
                  switch ($casinoId) {
                      case 1:
                          $color = '#008f39'; break;
                      case 2:
                          $color = '#ff0000'; break;
                      case 3:
                          $color = '#ffff00'; break;
                      default:
                          $color = '#222222'; break;
                  }

                  $cells->setBackground($color);
                  $cells->setFontColor('#000000');
                  $cells->setFontWeight('bold');
                  $cells->setAlignment('center');

              });
              $sheet->getStyle("A1:K1")->getAlignment()->setWrapText(true)->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
              $sheet->setHeight(1,35);
              $sheet->cells("A1:K999", function($cells){
                  $cells->setFontFamily('Arial');
                  $cells->setFontSize(10);
              });

              $fila++;

              foreach ($datosFiltrados as $anio => $registros) {
                  $sheet->mergeCells("A{$fila}:K{$fila}");
                  $sheet->setCellValue("A{$fila}", $anio);
                  $sheet->cells("A{$fila}:K{$fila}", function($cells){
                      $cells->setBackground('#CCCCCC');
                      $cells->setFontWeight('bold');
                      $cells->setFontSize(13);
                      $cells->setAlignment('center');
                  });
                  $sheet->getStyle("A{$fila}:K{$fila}")->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
                  $sheet->setHeight($fila, 20);
                  $fila++;

                  foreach ($registros as $r) {
                      setlocale(LC_TIME, 'es_ES.UTF-8');
                      $mesEsp = ucfirst(strftime('%B', strtotime($r->{'Mes'} . ' 1')));

                      $sheet->row($fila, [
                          $mesEsp,
                          "$ " . number_format($r->{'Monto'}, 2, '.', ''),
                          $r->{'Fecha Presentación'},
                          "$ " . number_format($r->{'Base 1'}, 2, '.', ''),
                          number_format($r->{'Alicuota1'}, 2, '.', '') ." %",
                          "$ " . number_format($r->{'Impuesto1'}, 2, '.', ''),
                          "$ " . number_format($r->{'Base 2'}, 2, '.', ''),
                          number_format($r->{'Alicuota2'}, 2, '.', '') ." %",
                          "$ " . number_format($r->{'Impuesto2'}, 2, '.', ''),
                          "$ " . number_format($r->{'Saldo'}, 2, '.', ''),
                          $r->{'Observaciones'}
                      ]);

                      $sheet->cells("A{$fila}", function($cells){
                          $cells->setBackground('#FFFF99');
                          $cells->setFontWeight('bold');
                          $cells->setAlignment('left');
                      });

                      $sheet->cells("B{$fila}:U{$fila}", function($cells){
                          $cells->setAlignment('center');
                      });

                      $sheet->getStyle("A{$fila}:U{$fila}")->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
                      $fila++;
                  }
              }

              $sheet->getStyle("A1:K1")->applyFromArray([
                  'borders' => [
                      'allborders' => [
                          'style' => PHPExcel_Style_Border::BORDER_THICK,
                          'color' => ['argb' => 'FF000000']
                      ]
                  ]
              ]);


              $sheet->getStyle("A3:K" . ($fila - 1))->applyFromArray([
                  'borders' => [
                      'allborders' => [
                          'style' => PHPExcel_Style_Border::BORDER_THIN,
                          'color' => ['argb' => 'FF000000']
                      ]
                  ]
              ]);

              $anchos = [
                  9, 17, 19, 14, 18, 18, 14, 18,
                  18, 14, 20, 18, 14, 15, 18, 20, 20,
                  20, 20, 20, 20, 20, 20, 20, 20, 20
              ];
              foreach (range('A', 'U') as $i => $col) {
                  $sheet->setWidth($col, $anchos[$i]);
              }

              $sheet->setFreeze('A2');
            });
          }else{
            $excel->sheet($casinoNombre, function($sheet) use ($datos, $casinoId, $columnasPorCasino) {
              $datosFiltrados = $datos->where('Casino', $casinoId)->groupBy('anio');
              $fila =1;
              $sheet->mergeCells("A1:A2");
              $sheet->mergeCells("B1:B2");
              $sheet->mergeCells("C1:C2");
              $sheet->mergeCells("P1:P2");
              $sheet->mergeCells("Q1:Q2");
              $sheet->mergeCells("R1:R2");
              $sheet->mergeCells("S1:S2");
              $sheet->mergeCells("T1:T2");
              $sheet->mergeCells("U1:U2");
              $sheet->mergeCells("D1:F1");
              $sheet->mergeCells("G1:I1");
              $sheet->mergeCells("J1:L1");
              $sheet->mergeCells("M1:O1");

              $sheet->row($fila,$columnasPorCasino[$casinoId]);

              $sheet->cells("A1:U2", function($cells) use ($casinoId) {
                  switch ($casinoId) {
                      case 1:
                          $color = '#008f39'; break;
                      case 2:
                          $color = '#ff0000'; break;
                      case 3:
                          $color = '#ffff00'; break;
                      default:
                          $color = '#222222'; break;
                  }

                  $cells->setBackground($color);
                  $cells->setFontColor('#000000');
                  $cells->setFontWeight('bold');
                  $cells->setAlignment('center');

              });
              $sheet->getStyle("A1:U2")->getAlignment()->setWrapText(true)->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
              $sheet->setHeight(1, 15);
              $sheet->cells("A1:U999", function($cells){
                  $cells->setFontFamily('Arial');
                  $cells->setFontSize(10);
              });
              $sheet->setHeight(2,40);
              $fila++;


              $sheet->row($fila, ['','','','Base Imponible','Alicuota','Subtotal Impuesto Determinado',
                  'Base Imponible','Alicuota','Impuesto Determinado','Base Imponible','Alicuota','Impuesto Determinado',
                  'Base Imponible','Alicuota','Impuesto Determinado']);

              $fila++;

              foreach ($datosFiltrados as $anio => $registros) {
                  $sheet->mergeCells("A{$fila}:U{$fila}");
                  $sheet->setCellValue("A{$fila}", $anio);
                  $sheet->cells("A{$fila}:U{$fila}", function($cells){
                      $cells->setBackground('#CCCCCC');
                      $cells->setFontWeight('bold');
                      $cells->setFontSize(13);
                      $cells->setAlignment('center');
                  });
                  $sheet->getStyle("A{$fila}:U{$fila}")->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
                  $sheet->setHeight($fila, 20);
                  $fila++;

                  foreach ($registros as $r) {
                      setlocale(LC_TIME, 'es_ES.UTF-8');
                      $mesEsp = ucfirst(strftime('%B', strtotime($r->{'Mes'} . ' 1')));

                      $sheet->row($fila, [
                          $mesEsp,
                          $r->{'Fecha Presentación'},
                          "$ " . number_format($r->{'Garage'}, 2, '.', ''),
                          "$ " . number_format($r->{'Base 1'}, 2, '.', ''),
                          number_format($r->{'Alicuota1'}, 2, '.', '') ." %",
                          "$ " . number_format($r->{'Impuesto1'}, 2, '.', ''),
                          "$ " . number_format($r->{'Base 2'}, 2, '.', ''),
                          number_format($r->{'Alicuota2'}, 2, '.', '') ." %",
                          "$ " . number_format($r->{'Impuesto2'}, 2, '.', ''),
                          "$ " . number_format($r->{'Base 3'}, 2, '.', ''),
                          number_format($r->{'Alicuota3'}, 2, '.', '') ." %",
                          "$ " . number_format($r->{'Impuesto3'}, 2, '.', ''),
                          "$ " . number_format($r->{'Base 4'}, 2, '.', ''),
                          number_format($r->{'Alicuota4'}, 2, '.', '') ." %",
                          "$ " . number_format($r->{'Impuesto4'}, 2, '.', ''),
                          "$ " . number_format($r->{'Bromatologia'}, 2, '.', ''),
                          "$ " . number_format($r->{'Deducciones'}, 2, '.', ''),
                          "$ " . number_format($r->{'Impuesto Total Determinado'}, 2, '.', ''),
                          "$ " . number_format($r->{'Intereses'}, 2, '.', ''),
                          "$ " . number_format($r->{'Saldo'}, 2, '.', ''),
                          $r->{'Observaciones'}
                      ]);

                      $sheet->cells("A{$fila}", function($cells){
                          $cells->setBackground('#FFFF99');
                          $cells->setFontWeight('bold');
                          $cells->setAlignment('left');
                      });

                      $sheet->cells("B{$fila}:U{$fila}", function($cells){
                          $cells->setAlignment('center');
                      });

                      $sheet->getStyle("A{$fila}:U{$fila}")->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
                      $fila++;
                  }
              }

              $sheet->getStyle("A1:U1")->applyFromArray([
                  'borders' => [
                      'allborders' => [
                          'style' => PHPExcel_Style_Border::BORDER_THICK,
                          'color' => ['argb' => 'FF000000']
                      ]
                  ]
              ]);
              $sheet->getStyle("D2:O2")->applyFromArray([
                  'borders' => [
                      'allborders' => [
                          'style' => PHPExcel_Style_Border::BORDER_THICK,
                          'color' => ['argb' => 'FF000000']
                      ]
                  ]
              ]);

              $sheet->getStyle("A3:U" . ($fila - 1))->applyFromArray([
                  'borders' => [
                      'allborders' => [
                          'style' => PHPExcel_Style_Border::BORDER_THIN,
                          'color' => ['argb' => 'FF000000']
                      ]
                  ]
              ]);

              $anchos = [
                  9, 17, 19, 14, 18, 16, 14, 18,
                  16, 14, 20, 18, 14, 15, 18, 20, 20,
                  20, 20, 20, 20, 20, 20, 20, 20, 20
              ];
              foreach (range('A', 'U') as $i => $col) {
                  $sheet->setWidth($col, $anchos[$i]);
              }

              $sheet->setFreeze('A3');
            });
          }

        }

    })->export('xlsx');
}

//TGI



public function guardarRegistroTGI_partida(Request $request){

      try {
          DB::beginTransaction();

          $TGI_partida = new RegistroTGI_partida();
          $TGI_partida->partida = $request->nombre_TGI_partida;
          $TGI_partida->casino = $request->CasinoTGI_partida;
          $TGI_partida->estado = 1;
          $TGI_partida->fecha_toma = date('Y-m-d h:i:s', time());
          $TGI_partida->usuario = UsuarioController::getInstancia()->quienSoy()['usuario']['id_usuario'];
          $TGI_partida->save();

          DB::commit();

          return response()->json([
            'success' => true,
             'id' => $TGI_partida->id_registroTGI_partida,
             'TGI_partida'  => $request->all(),
           ]);
      } catch (\Exception $e) {
          DB::rollBack();
          return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
      }

}


public function getTGI_partida(){
  $rows = RegistroTGI_partida::with('CasinoTGI_partida')
    ->orderBy('casino')
    ->get(['id_registroTGI_partida as id','partida','casino','estado'])
    ->map(function($r){
      return [
        'id'             => $r->id,
        'partida'        => $r->partida,
        'estado'     => $r->estado,
        'casino_id'      => $r->casino,
        'casino_nombre'  => ($r->CasinoTGI_partida ? $r->CasinoTGI_partida->nombre : '-'),
      ];
    })->values();

  return response()->json($rows);
}

public function getTGI_partidaPorCasino(Request $request){
  $casinoId = $request->query('casino');
  $edit = $request->query('edit') ?  1 : 0;

  $q = RegistroTGI_partida::with('CasinoTGI_partida')
        ->orderBy('casino');

  if($casinoId) $q->where('casino', $casinoId);
  if(!$edit) $q->where('estado',1);

  $rows = $q->get(['id_registroTGI_partida as id','partida','casino'])
            ->map(function($r){
              return [
                'id'            => $r->id,
                'partida'       => $r->partida,
                'casino_id'     => $r->casino,
                'casino_nombre' => $r->CasinoTGI_partida ? $r->CasinoTGI_partida->nombre : '-',
              ];
            })->values();

  return response()->json($rows);
}


public function EliminarTGI_partida($id){
    RegistroTGI_partida::findOrFail($id)->delete();
    return response()->json(array('ok'=>true));
}

public function modificarTGI_partida(Request $request){

  $id = $request->ModifId_TGI_partida;
  $partida = $request->ModifTGI_partida_partida;
  $estado = $request->ModifEstadoTGI_partida;
  try {
    DB::beginTransaction();
    $TGI_partida = RegistroTGI_partida::findOrFail($id);
    $TGI_partida->partida = $partida;
    $TGI_partida->estado = $estado;
    $TGI_partida->save();
    DB::commit();

    return response()->json([
      'success' => true,
       'id' => $TGI_partida->id_registroTGI_partida,
       'habilitado' =>$TGI_partida->estado,
       'partida' => $TGI_partida->partida,
     ]);

    } catch (\Exception $e) {
    DB::rollBack();
    return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
  }

}

public function guardarTGI(Request $request)
{
    DB::beginTransaction();
    try {
        $t = new RegistroTGI();
        $t->fecha_tgi   = $request->input('fecha_TGI') ? ($request->input('fecha_TGI').'-01') : null;
        $t->casino      = $request->input('casinoTGI');
        $t->fecha_toma  = date('Y-m-d H:i:s');
        $t->usuario     = UsuarioController::getInstancia()->quienSoy()['usuario']['id_usuario'];
        $t->save();

        $files = $request->file('uploadTGI');
        if ($files) {
            if (!is_array($files)) $files = [$files];
            foreach ($files as $file) {
                if (!($file instanceof \Illuminate\Http\UploadedFile) || !$file->isValid()) continue;

                $base = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
                $ext  = $file->getClientOriginalExtension();
                $safe = preg_replace('/\s+/', '_', $base);
                $name = time().'_'.Str::random(6).'_'.$safe.($ext?'.'.$ext:'');

                $file->storeAs('public/RegistroTGI', $name);

                $t->archivos()->create([
                    'path'       => $name,
                    'usuario'    => $t->usuario,
                    'fecha_toma' => date('Y-m-d H:i:s'),
                ]);
            }
        }

        $partidas = (array) $request->input('pago_partida', []);
        $importes = (array) $request->input('pago_importe', []);
        $cuotas   = (array) $request->input('pago_cuota', []);
        $vencs    = (array) $request->input('pago_vencimiento', []);
        $obs    = (array) $request->input('pago_observacion', []);
        $pagos    = (array) $request->input('pago_pago', []);

        $n = max(count($partidas), count($importes),count($cuotas), count($vencs), count($pagos));
        for ($i=0; $i<$n; $i++) {
            $pid = $partidas[$i] ?? null;
            if (!$pid) continue;

            RegistroTGI_partida_pago::create([
                'fecha_toma'        => date('Y-m-d H:i:s'),
                'partida'           => $pid,
                'registroTGI'       => $t->id_registrotgi,
                'cuota'             => $cuotas[$i] ?? null,
                'observacion'      => $obs[$i] ?? null,
                'importe'           => $importes[$i] ?? null,
                'fecha_vencimiento' => $vencs[$i] ?? null,
                'fecha_pago'        => $pagos[$i] ?? null,
            ]);
        }

        DB::commit();
        return response()->json(['success'=>true, 'id'=>$t->id_registrotgi]);
    } catch (\Exception $e) {
        DB::rollBack();
        return response()->json(['success'=>false, 'error'=>$e->getMessage()], 500);
    }
}

public function actualizarTGI(Request $request, $id)
{
    DB::beginTransaction();
    try {
        $t = \App\RegistroTGI::findOrFail($id);

        $t->fecha_tgi   = $request->input('fecha_TGI') ? ($request->input('fecha_TGI').'-01') : null;
        $t->casino      = $request->input('casinoTGI');

        $t->save();

        $files = $request->file('uploadTGI');
        if ($files) {
            if (!is_array($files)) $files = [$files];
            foreach ($files as $file) {
                if (!($file instanceof \Illuminate\Http\UploadedFile) || !$file->isValid()) continue;

                $base = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
                $ext  = $file->getClientOriginalExtension();
                $safe = preg_replace('/\s+/', '_', $base);
                $name = time().'_'.Str::random(6).'_'.$safe.($ext?'.'.$ext:'');

                $file->storeAs('public/RegistroTGI', $name);

                $t->archivos()->create([
                    'path'       => $name,
                    'usuario'    => \App\Http\Controllers\UsuarioController::getInstancia()->quienSoy()['usuario']['id_usuario'],
                    'fecha_toma' => date('Y-m-d H:i:s'),
                ]);
            }
        }

        RegistroTGI_partida_pago::where('registroTGI', $t->id_registrotgi)->delete();

        $partidas = (array) $request->input('pago_partida', []);
        $importes = (array) $request->input('pago_importe', []);
        $cuotas = (array) $request->input('pago_cuota',[]);
        $obs = (array) $request->input('pago_observacion',[]);

        $vencs    = (array) $request->input('pago_vencimiento', []);
        $pagos    = (array) $request->input('pago_pago', []);

        $n = max(count($partidas), count($importes), count($vencs), count($pagos));
        for ($i=0; $i<$n; $i++) {
            $pid = $partidas[$i] ?? null;
            if (!$pid) continue;

            \App\RegistroTGI_partida_pago::create([
                'fecha_toma'        => date('Y-m-d H:i:s'),
                'partida'           => $pid,
                'registroTGI'       => $t->id_registrotgi,
                'cuota'             => $cuotas[$i] ?? null,
                'observacion' => $obs[$i] ?? null,
                'importe'           => $importes[$i] ?? null,
                'fecha_vencimiento' => $vencs[$i] ?? null,
                'fecha_pago'        => $pagos[$i] ?? null,
            ]);
        }

        DB::commit();
        return response()->json(['success'=>true]);
    } catch (\Exception $e) {
        DB::rollBack();
        return response()->json(['success'=>false, 'error'=>$e->getMessage()], 500);
    }
}




  public function ultimasTGI(Request $request) {

    $page    = max(1, (int)$request->query('page', 1));
    $perPage = max(1, (int)$request->query('page_size', 20));

    $user = Usuario::find(session('id_usuario'));
    $allowedCasinoIds = $user->casinos->pluck('id_casino')->toArray();


    $query = RegistroTGI::with('casinoTGI')
              ->withCount('archivos')
              ->whereIn('casino', $allowedCasinoIds)
              ->orderBy('fecha_tgi', 'desc');

    if ($c = $request->query('id_casino')) {
      $query->where('casino', $c);
    }
    if ($desde = $request->query('desde')){
      $desde = $desde."-01";
      $query->where('fecha_TGI',">=",$desde);
    }
    if ($hasta = $request->query('hasta')){
      $hasta = $hasta."-01";
      $query->where('fecha_TGI',"<=",$hasta);
    }

    $total = $query->count();

    $registros = $query
        ->skip(($page - 1) * $perPage)
        ->take($perPage)
        ->get();

    $datos = $registros->map(function($r) {
        return [
            'id_registroTGI' => $r->id_registrotgi,
            'cuotas' => $r->cuota,
            'fechaPres' => $r->fecha_presentacion,
            'fecha_TGI'   => $r->fecha_tgi,
            'casino'      => $r->casinoTGI ? $r->casinoTGI->nombre : '-',
            'archivo'     =>$r->archivo,
        	  'tiene_archivos' => $r->archivos_count>0,
        ];
    });

    return response()->json([
        'registros'  => $datos,
        'pagination' => [
            'current_page' => $page,
            'per_page'     => $perPage,
            'total'        => $total,
        ],
    ]);
}


public function archivosTGI($id)
{
    $TGI = RegistroTGI::with('archivos')->findOrFail($id);

    $files = $TGI->archivos->map(function($a){
        return [
            'id'     => $a->id_registro_archivo,
            'nombre' => basename($a->path),
            'url'    => \Illuminate\Support\Facades\Storage::url($a->path),
            'fecha'  => $a->fecha_toma,
        ];
    });

    return response()->json($files->values());
}

public function llenarTGIEdit($id)
{
    $t = RegistroTGI::with('casinoTGI')->findOrFail($id);

    $pagos = RegistroTGI_partida_pago::with('partidaTGI')
        ->where('registroTGI', $t->id_registrotgi)
        ->orderBy('id_registroTGI_partida_pago')
        ->get()
        ->map(function($p){
            return [
                'id'                 => $p->id_registroTGI_partida_pago,
                'partida_id'         => $p->partida,
                'cuota'              => $p->cuota,
                'partida'            => $p->partidaTGI->partida,
                'importe'            => $p->importe,
                'fecha_vencimiento'  => $p->fecha_vencimiento,
                'observacion'       => $p->observacion,
                'fecha_pago'         => $p->fecha_pago,
            ];
        })->values();

    return response()->json([
        'id'            => $t->id_registrotgi,
        'fecha'         => $t->fecha_tgi,
        'casino'        => $t->casino,
        'casino_nombre' => $t->casinoTGI->nombre,
        'pagos'         => $pagos,
    ]);
}

public function llenarTGI($id)
{
    $t = RegistroTGI::with('casinoTGI')->findOrFail($id);

    $pagos = RegistroTGI_partida_pago::with('partidaTGI')
        ->where('registroTGI', $t->id_registrotgi)
        ->orderBy('id_registroTGI_partida_pago')
        ->get()
        ->map(function($p){
            return [
                'partida'            => $p->partidaTGI->partida,
                'importe'            => $p->importe,
                'cuota'              => $p->cuota,
                'observacion'       =>$p->observacion,
                'fecha_vencimiento'  => $p->fecha_vencimiento,
                'fecha_pago'         => $p->fecha_pago,
            ];
        })->values();

    return response()->json([
        'fecha'          => $t->fecha_tgi,
        'casino'         => $t->casinoTGI->nombre,
        'pagos'          => $pagos,
    ]);
}




public function eliminarTGI($id){
  $TGI = RegistroTGI::findOrFail($id);
  if(is_null($TGI)) return 0;
  RegistroTGI::destroy($id);
  return 1;
}
public function descargarTGICsv(Request $request)
{
    $casinoId = $request->query('casino');
    $desde    = $request->query('desde'); // yyyy-mm
    $hasta    = $request->query('hasta'); // yyyy-mm

    $user = Usuario::find(session('id_usuario'));
    $allowedCasinoIds = $user->casinos->pluck('id_casino')->toArray();

    $q = RegistroTGI::with(['casinoTGI', 'pagos.partidaTGI'])
        ->whereIn('casino', $allowedCasinoIds);

    if ($casinoId && (int)$casinoId !== 4) {
        $q->where('casino', $casinoId);
    }
    if ($desde) {
        $q->where('fecha_tgi', '>=', $desde . '-01');
    }
    if ($hasta) {
        $q->where('fecha_tgi', '<=', $hasta . '-31');
    }

    $regs = $q->orderBy('casino')->orderBy('fecha_tgi')->get();

    $csv = [];
    $csv[] = ['Casino','Año','Mes','Cuota','Partida','Vencimiento','Fecha Pago','Monto Pagado','Observaciones'];

    $meses = [1=>'Enero',2=>'Febrero',3=>'Marzo',4=>'Abril',5=>'Mayo',6=>'Junio',7=>'Julio',8=>'Agosto',9=>'Septiembre',10=>'Octubre',11=>'Noviembre',12=>'Diciembre'];

    foreach ($regs as $r) {
        $casinoNombre = $r->casinoTGI ? $r->casinoTGI->nombre : '-';
        $ts = $r->fecha_tgi ? strtotime($r->fecha_tgi) : null;
        $anio = $ts ? date('Y', $ts) : '';
        $mes  = $ts ? $meses[(int)date('n', $ts)] : '';

        if (!$r->pagos || $r->pagos->isEmpty()) {
            $csv[] = [
                $casinoNombre, $anio, $mes,
                '', '', '', '', '', ''
            ];
            continue;
        }

        foreach ($r->pagos as $pago) {
            $partidaNombre = $pago->partidaTGI ? $pago->partidaTGI->partida : '';
            $vto   = $pago->fecha_vencimiento ? date('d/m/Y', strtotime($pago->fecha_vencimiento)) : '';
            $fpago = $pago->fecha_pago        ? date('d/m/Y', strtotime($pago->fecha_pago))        : '';
            $monto = is_null($pago->importe)  ? '' : number_format((float)$pago->importe, 2, '.', '');
            $cuota = isset($pago->cuota) ? $pago->cuota : '';
            $obs   = isset($pago->observacion) ? (string)$pago->observacion : (isset($pago->obs) ? (string)$pago->obs : '');

            $csv[] = [
                $casinoNombre, $anio, $mes, $cuota,
                $partidaNombre,
                $vto,
                $fpago,
                $monto,
                $obs,
            ];
        }
    }

    $nombreCasino = ((int)$casinoId === 4 || !$casinoId)
        ? 'todos'
        : (Casino::find($casinoId)->nombre ?? 'desconocido');

    $filename = "TGI_{$nombreCasino}_" . date('Ymd_His') . ".csv";

    $h = fopen('php://temp', 'r+');
    foreach ($csv as $linea) { fputcsv($h, $linea, ','); }
    rewind($h);
    $out = stream_get_contents($h);
    fclose($h);

    return response($out, 200, [
        'Content-Type'        => 'text/csv; charset=UTF-8',
        'Content-Disposition' => "attachment; filename=\"$filename\"",
        'Cache-Control'       => 'no-store, no-cache',
    ]);
}

public function descargarTGIXlsx(Request $request)
{
   $casinoId = $request->query('casino');
   $desde    = $request->query('desde'); // yyyy-mm
   $hasta    = $request->query('hasta'); // yyyy-mm

   $casino = Casino::findOrFail($casinoId);

   $q = RegistroTGI::with(['pagos.partidaTGI'])
       ->where('casino', $casinoId);

   if ($desde) { $q->where('fecha_tgi', '>=', $desde.'-01'); }
   if ($hasta) { $q->where('fecha_tgi', '<=', $hasta.'-31'); }

   $regs = $q->orderBy('fecha_tgi')->get();

   $meses = [1=>'Enero',2=>'Febrero',3=>'Marzo',4=>'Abril',5=>'Mayo',6=>'Junio',7=>'Julio',8=>'Agosto',9=>'Septiembre',10=>'Octubre',11=>'Noviembre',12=>'Diciembre'];
   $items = [];

   foreach ($regs as $r) {
       if ($r->pagos && $r->pagos->count()) {
           foreach ($r->pagos as $p) {
               $ts = $r->fecha_tgi ? strtotime($r->fecha_tgi) : null;
               $anio = $ts ? date('Y', $ts) : '';
               $mesEsp = $ts ? $meses[(int)date('n',$ts)] : '';
               $partidaNombre = $p->partidaTGI ? $p->partidaTGI->partida : '-';

               $items[] = [
                   'anio'       => $anio,
                   'mes'        => $mesEsp,
                   'cuota'      => isset($p->cuota) ? $p->cuota : '',
                   'partida'    => $partidaNombre,
                   'vto'        => $p->fecha_vencimiento ? date('d/m/Y', strtotime($p->fecha_vencimiento)) : '',
                   'fpago'      => $p->fecha_pago ? date('d/m/Y', strtotime($p->fecha_pago)) : '',
                   'monto'      => is_null($p->importe) ? '' : '$ '.number_format((float)$p->importe, 2, ',', '.'),
                   'obs'        => isset($p->observacion) ? (string)$p->observacion : (isset($p->obs) ? (string)$p->obs : ''),
               ];
           }
       }
   }

   usort($items, function($a,$b){
       if ($a['anio'] != $b['anio']) return $a['anio'] < $b['anio'] ? -1 : 1;
       if ($a['partida'] != $b['partida']) return strcasecmp($a['partida'],$b['partida']);
       if ($a['mes'] != $b['mes']) return strcasecmp($a['mes'],$b['mes']);
       return strnatcasecmp((string)$a['cuota'], (string)$b['cuota']);
   });

   $porAnio = [];
   foreach ($items as $it) {
       if (!isset($porAnio[$it['anio']])) $porAnio[$it['anio']] = [];
       if (!isset($porAnio[$it['anio']][$it['partida']])) $porAnio[$it['anio']][$it['partida']] = [];
       $porAnio[$it['anio']][$it['partida']][] = $it;
   }

   $filename = "registro_TGI_".str_replace(' ','_', strtolower($casino->nombre));

   return \Excel::create($filename, function($excel) use ($porAnio, $casinoId) {

       $excel->sheet('TGI', function($sheet) use ($porAnio, $casinoId) {
           $fila = 1;
           $lastCol = 'F';

           $sheet->row($fila, ['PERIODO','CUOTA','MONTO PAGADO','Fecha vencimiento','Fecha de Presentación y Pago','Observaciones']);

           $sheet->cells("A1:{$lastCol}1", function($c) use ($casinoId){
               $color = ($casinoId==1?'#008f39':($casinoId==2?'#ff0000':($casinoId==3?'#ffff00':'#222222')));
               $c->setBackground($color);
               $c->setFontColor('#000000');
               $c->setFontWeight('bold');
               $c->setAlignment('center');
           });
           $sheet->getStyle("A1:{$lastCol}1")->getAlignment()->setWrapText(true)->setVertical(\PHPExcel_Style_Alignment::VERTICAL_CENTER);
           $sheet->setHeight(1,45);
           $sheet->cells("A1:{$lastCol}9999", function($c){ $c->setFontFamily('Arial'); $c->setFontSize(10); });
           $fila++;

           foreach ($porAnio as $anio => $porPartida) {
               $sheet->mergeCells("A{$fila}:{$lastCol}{$fila}");
               $sheet->setCellValue("A{$fila}", "Año {$anio}");
               $sheet->cells("A{$fila}:{$lastCol}{$fila}", function($c){
                   $c->setBackground('#CCCCCC'); $c->setFontWeight('bold'); $c->setFontSize(13); $c->setAlignment('center');
               });
               $sheet->getStyle("A{$fila}:{$lastCol}{$fila}")->getAlignment()->setVertical(\PHPExcel_Style_Alignment::VERTICAL_CENTER);
               $sheet->setHeight($fila,20);
               $fila++;

               foreach ($porPartida as $nombrePartida => $rows) {
                   $sheet->mergeCells("A{$fila}:{$lastCol}{$fila}");
                   $sheet->setCellValue("A{$fila}", "Partida {$nombrePartida}");
                   $sheet->cells("A{$fila}:{$lastCol}{$fila}", function($c){
                       $c->setBackground('#FFFF99'); $c->setFontWeight('bold'); $c->setFontSize(12); $c->setAlignment('center');
                   });
                   $fila++;

                   foreach ($rows as $row) {
                       $sheet->row($fila, [
                           $row['mes'],
                           $row['cuota'],
                           $row['monto'],
                           $row['vto'],
                           $row['fpago'],
                           $row['obs'],
                       ]);
                       $sheet->cells("A{$fila}:{$lastCol}{$fila}", function($c){ $c->setAlignment('center'); });
                       $sheet->cells("A{$fila}", function($c){ $c->setBackground('#FFFF99'); $c->setFontWeight('bold'); $c->setAlignment('left'); });
                       $sheet->getStyle("A{$fila}:{$lastCol}{$fila}")->getAlignment()->setVertical(\PHPExcel_Style_Alignment::VERTICAL_CENTER);
                       $fila++;
                   }
               }
           }

           $sheet->getStyle("A1:{$lastCol}1")->applyFromArray([
               'borders'=>['allborders'=>['style'=>\PHPExcel_Style_Border::BORDER_THICK,'color'=>['argb'=>'FF000000']]]
           ]);
           $lastRow = $fila - 1;
           if ($lastRow >= 2) {
               $sheet->getStyle("A2:{$lastCol}{$lastRow}")->applyFromArray([
                   'borders'=>['allborders'=>['style'=>\PHPExcel_Style_Border::BORDER_THIN,'color'=>['argb'=>'FF000000']]]
               ]);
           }
           $widths = [18,10,18,20,28,28];
           $cols   = ['A','B','C','D','E','F'];
           foreach ($cols as $i => $col) { $sheet->setWidth($col, $widths[$i]); }
           $sheet->setFreeze('A2');
       });

   })->export('xlsx');
}

public function descargarTGIXlsxTodos(Request $request)
{
    $desde = $request->query('desde'); // yyyy-mm
    $hasta = $request->query('hasta'); // yyyy-mm

    $user    = Usuario::find(session('id_usuario'));
    $casinos = $user->casinos->pluck('nombre','id_casino');

    $meses = [1=>'Enero',2=>'Febrero',3=>'Marzo',4=>'Abril',5=>'Mayo',6=>'Junio',7=>'Julio',8=>'Agosto',9=>'Septiembre',10=>'Octubre',11=>'Noviembre',12=>'Diciembre'];

    return \Excel::create('registro_TGI_todos', function($excel) use ($casinos, $desde, $hasta, $meses) {

        foreach ($casinos as $casinoId => $casinoNombre) {

            $q = RegistroTGI::with(['pagos.partidaTGI'])->where('casino', $casinoId);
            if ($desde) { $q->where('fecha_tgi','>=',$desde.'-01'); }
            if ($hasta) { $q->where('fecha_tgi','<=',$hasta.'-31'); }
            $regs = $q->orderBy('fecha_tgi')->get();

            $items = [];
            foreach ($regs as $r) {
                if ($r->pagos && $r->pagos->count()) {
                    foreach ($r->pagos as $p) {
                        $ts = $r->fecha_tgi ? strtotime($r->fecha_tgi) : null;
                        $anio = $ts ? date('Y', $ts) : '';
                        $mesEsp = $ts ? $meses[(int)date('n', $ts)] : '';
                        $partidaNombre = $p->partidaTGI ? $p->partidaTGI->partida : '-';

                        $items[] = [
                            'anio'       => $anio,
                            'mes'        => $mesEsp,
                            'cuota'      => isset($p->cuota) ? $p->cuota : '',
                            'partida'    => $partidaNombre,
                            'vto'        => $p->fecha_vencimiento ? date('d/m/Y', strtotime($p->fecha_vencimiento)) : '',
                            'fpago'      => $p->fecha_pago ? date('d/m/Y', strtotime($p->fecha_pago)) : '',
                            'monto'      => is_null($p->importe) ? '' : '$ '.number_format((float)$p->importe, 2, ',', '.'),
                            'obs'        => isset($p->observacion) ? (string)$p->observacion : (isset($p->obs) ? (string)$p->obs : ''),
                        ];
                    }
                }
            }

            usort($items, function($a,$b){
                if ($a['anio'] != $b['anio']) return $a['anio'] < $b['anio'] ? -1 : 1;
                if ($a['partida'] != $b['partida']) return strcasecmp($a['partida'],$b['partida']);
                if ($a['mes'] != $b['mes']) return strcasecmp($a['mes'],$b['mes']);
                return strnatcasecmp((string)$a['cuota'], (string)$b['cuota']);
            });

            $porAnio = [];
            foreach ($items as $it) {
                if (!isset($porAnio[$it['anio']])) $porAnio[$it['anio']] = [];
                if (!isset($porAnio[$it['anio']][$it['partida']])) $porAnio[$it['anio']][$it['partida']] = [];
                $porAnio[$it['anio']][$it['partida']][] = $it;
            }

            $excel->sheet($casinoNombre, function($sheet) use ($porAnio, $casinoId) {
                $fila = 1;
                $lastCol = 'F';

                $sheet->row($fila, ['PERIODO','CUOTA','MONTO PAGADO','Fecha vencimiento','Fecha de Presentación y Pago','Observaciones']);

                $sheet->cells("A1:{$lastCol}1", function($c) use ($casinoId){
                    $color = ($casinoId==1?'#008f39':($casinoId==2?'#ff0000':($casinoId==3?'#ffff00':'#222222')));
                    $c->setBackground($color); $c->setFontColor('#000000'); $c->setFontWeight('bold'); $c->setAlignment('center');
                });
                $sheet->getStyle("A1:{$lastCol}1")->getAlignment()->setWrapText(true)->setVertical(\PHPExcel_Style_Alignment::VERTICAL_CENTER);
                $sheet->setHeight(1,45);
                $sheet->cells("A1:{$lastCol}9999", function($c){ $c->setFontFamily('Arial'); $c->setFontSize(10); });
                $fila++;

                foreach ($porAnio as $anio => $porPartida) {
                    $sheet->mergeCells("A{$fila}:{$lastCol}{$fila}");
                    $sheet->setCellValue("A{$fila}", "Año {$anio}");
                    $sheet->cells("A{$fila}:{$lastCol}{$fila}", function($c){
                        $c->setBackground('#CCCCCC'); $c->setFontWeight('bold'); $c->setFontSize(13); $c->setAlignment('center');
                    });
                    $sheet->getStyle("A{$fila}:{$lastCol}{$fila}")->getAlignment()->setVertical(\PHPExcel_Style_Alignment::VERTICAL_CENTER);
                    $sheet->setHeight($fila,20);
                    $fila++;

                    foreach ($porPartida as $nombrePartida => $rows) {
                        $sheet->mergeCells("A{$fila}:{$lastCol}{$fila}");
                        $sheet->setCellValue("A{$fila}", "Partida {$nombrePartida}");
                        $sheet->cells("A{$fila}:{$lastCol}{$fila}", function($c){
                            $c->setBackground('#FFFF99'); $c->setFontWeight('bold'); $c->setFontSize(12); $c->setAlignment('center');
                        });
                        $fila++;

                        foreach ($rows as $row) {
                            $sheet->row($fila, [
                                $row['mes'],
                                $row['cuota'],
                                $row['monto'],
                                $row['vto'],
                                $row['fpago'],
                                $row['obs'],
                            ]);
                            $sheet->cells("A{$fila}:{$lastCol}{$fila}", function($c){ $c->setAlignment('center'); });
                            $sheet->cells("A{$fila}", function($c){ $c->setBackground('#FFFF99'); $c->setFontWeight('bold'); $c->setAlignment('left'); });
                            $sheet->getStyle("A{$fila}:{$lastCol}{$fila}")->getAlignment()->setVertical(\PHPExcel_Style_Alignment::VERTICAL_CENTER);
                            $fila++;
                        }
                    }
                }

                $sheet->getStyle("A1:{$lastCol}1")->applyFromArray([
                    'borders'=>['allborders'=>['style'=>\PHPExcel_Style_Border::BORDER_THICK,'color'=>['argb'=>'FF000000']]]
                ]);
                $lastRow = $fila - 1;
                if ($lastRow >= 2) {
                    $sheet->getStyle("A2:{$lastCol}{$lastRow}")->applyFromArray([
                        'borders'=>['allborders'=>['style'=>\PHPExcel_Style_Border::BORDER_THIN,'color'=>['argb'=>'FF000000']]]
                    ]);
                }
                $widths = [18,10,18,20,28,28];
                $cols   = ['A','B','C','D','E','F'];
                foreach ($cols as $i => $col) { $sheet->setWidth($col, $widths[$i]); }
                $sheet->setFreeze('A2');
            });
        }
    })->export('xlsx');
}


//IMP_AP_OL IMPUESTO A APUESTAS ONLINE


public function archivosIMP_AP_OL($id)
{
    $IMP_AP_OL = RegistroIMP_AP_OL::with('archivos')->findOrFail($id);

    $files = $IMP_AP_OL->archivos->map(function($a){
        return [
            'id'     => $a->id_registro_archivo,
            'nombre' => basename($a->path),
            'url'    => \Illuminate\Support\Facades\Storage::url($a->path),
            'fecha'  => $a->fecha_toma,
        ];
    });

    return response()->json($files->values());
}


public function actualizarIMP_AP_OL(Request $request, $id)
{
    $r = RegistroIMP_AP_OL::findOrFail($id);

    $r->fecha_IMP_AP_OL   = $request->input('fecha_IMP_AP_OL') . '-01';
    $r->qna                = $request->input('qnaIMP_AP_OL');
    $r->monto_pagado       = $request->input('monto_pagadoIMP_AP_OL');
    $r->monto_apuestas     = $request->input('monto_apuestasIMP_AP_OL');
    $r->alicuota           = $request->input('alicuotaIMP_AP_OL');
    $r->impuesto_determinado = $request->input('impuesto_determinadoIMP_AP_OL');
    $r->fecha_presentacion = $request->input('fecha_IMP_AP_OLPres');
    $r->fecha_pago         = $request->input('fecha_pago_IMP_AP_OL');
    $r->casino             = $request->input('casinoIMP_AP_OL');
    $r->save();

    $saved = 0;
    foreach (Arr::wrap($request->file('uploadIMP_AP_OL')) as $file) {
        if (!($file instanceof UploadedFile) || !$file->isValid()) continue;
        $base = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
        $ext  = $file->getClientOriginalExtension();
        $safe = preg_replace('/\s+/', '_', $base);
        $name = time().'_'.Str::random(6).'_'.$safe.($ext ? '.'.$ext : '');
        $file->storeAs('public/RegistroIMP_AP_OL', $name);
        $r->archivos()->create([
            'path'       => $name,
            'usuario'    => UsuarioController::getInstancia()->quienSoy()['usuario']['id_usuario'],
            'fecha_toma' => date('Y-m-d H:i:s'),
        ]);
        $saved++;
    }

    return response()->json(['success' => true, 'saved_files' => $saved]);
}

public function llenarIMP_AP_OLEdit($id){
    $r = RegistroIMP_AP_OL::findOrFail($id);

    $f  = $r->fecha_imp_ap_ol;
    $fp = $r->fecha_presentacion;
    $pg = $r->fecha_pago;

    $fecha      = is_string($f)  ? substr($f, 0, 7)  : ($f  ? $f->format('Y-m')    : null);
    $fecha_pres = is_string($fp) ? substr($fp, 0, 10) : ($fp ? $fp->format('Y-m-d') : null);
    $fecha_pago = is_string($pg) ? substr($pg, 0, 10) : ($pg ? $pg->format('Y-m-d') : null);

    return response()->json([
        'fecha'                 => $fecha,
        'fecha_pres'            => $fecha_pres,
        'fecha_pago'            => $fecha_pago,
        'casino'                => $r->casino,
        'qna'                   => $r->qna,
        'monto_pagado'          => $r->monto_pagado,
        'monto_apuestas'        => $r->monto_apuestas,
        'alicuota'              => $r->alicuota,
        'impuesto_determinado'  => $r->impuesto_determinado,
    ]);
}

public function guardarIMP_AP_OL(Request $request){

      $path = null;


      try {
          DB::beginTransaction();

          $IMP_AP_OL = new RegistroIMP_AP_OL();
          $IMP_AP_OL->fecha_imp_ap_ol = $request->fecha_IMP_AP_OL.'-01';


          $IMP_AP_OL->qna = $request->qnaIMP_AP_OL;
          $IMP_AP_OL->monto_pagado = $request->monto_pagadoIMP_AP_OL;
          $IMP_AP_OL->monto_apuestas= $request->monto_apuestasIMP_AP_OL;
          $IMP_AP_OL->alicuota = $request->alicuotaIMP_AP_OL;
          $IMP_AP_OL->impuesto_determinado = $request->impuesto_determinadoIMP_AP_OL;
          $IMP_AP_OL->fecha_presentacion = $request->fecha_IMP_AP_OLPres;
          $IMP_AP_OL->fecha_pago = $request->fecha_pago_IMP_AP_OL;
          $IMP_AP_OL->casino = $request->casinoIMP_AP_OL;

          $IMP_AP_OL->fecha_toma = date('Y-m-d h:i:s', time());
          $IMP_AP_OL->usuario = UsuarioController::getInstancia()->quienSoy()['usuario']['id_usuario'];
          $IMP_AP_OL->save();

          $files = Arr::wrap($request->file('uploadIMP_AP_OL'));
            foreach ($files as $file) {
              if (!($file instanceof UploadedFile) || !$file->isValid()) continue;

              $base = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
              $ext  = $file->getClientOriginalExtension();
              $safe = preg_replace('/\s+/', '_', $base);
              $filename = time().'_'.Str::random(6).'_'.$safe.($ext?'.'.$ext:'');

              $file->storeAs('public/RegistroIMP_AP_OL', $filename);

              $IMP_AP_OL->archivos()->create([
                  'path'       => $filename,
                  'usuario'    => $IMP_AP_OL->usuario,
                  'fecha_toma' => date('Y-m-d H:i:s'),
              ]);
          }

          DB::commit();

          return response()->json([
            'success' => true,
             'id' => $IMP_AP_OL->id_registroimp_ap_ol,
             'IMP_AP_OL'  => $request->all(),
             'path'    => $path,
             'url'     => Storage::url($path)
           ]);
      } catch (\Exception $e) {
          DB::rollBack();
          return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
      }

}

public function ultimasIMP_AP_OL(Request $request)
{
    $page    = max(1, (int)$request->query('page', 1));
    $perPage = max(1, (int)$request->query('page_size', 20));

    $user = Usuario::find(session('id_usuario'));
    $allowedCasinoIds = $user->casinos->pluck('id_casino')->toArray();


    $query = RegistroIMP_AP_OL::with('casinoIMP_AP_OL')
              ->whereIn('casino', $allowedCasinoIds)
              ->withCount('archivos')
              ->orderBy('fecha_IMP_AP_OL', 'desc')
              ->orderBy('casino', 'desc')
              ->orderBy('qna', 'desc');


    if ($c = $request->query('id_casino')) {
      $query->where('casino', $c);
    }
    if ($desde = $request->query('desde')){
      $desde = $desde."-01";
      $query->where('fecha_imp_ap_ol',">=",$desde);
    }
    if ($hasta = $request->query('hasta')){
      $hasta = $hasta."-01";
      $query->where('fecha_imp_ap_ol',"<=",$hasta);
    }

    $total = $query->count();

    $registros = $query
        ->skip(($page - 1) * $perPage)
        ->take($perPage)
        ->get();

    $datos = $registros->map(function($r) {
        return [
            'id_registroIMP_AP_OL' => $r->id_registroIMP_AP_OL,
            'fecha_IMP_AP_OL'   => $r->fecha_imp_ap_ol,
            'fecha_presentacion' => $r->fecha_presentacion,
            'casino'      => $r->casinoIMP_AP_OL ? $r->casinoIMP_AP_OL->nombre : '-',
            'qna' => $r->qna,
	           'tiene_archivos' => $r->archivos_count>0,
          ];
    });

    return response()->json([
        'registros'  => $datos,
        'pagination' => [
            'current_page' => $page,
            'per_page'     => $perPage,
            'total'        => $total,
        ],
    ]);
}


public function eliminarIMP_AP_OL($id){
  $IMP_AP_OL = RegistroIMP_AP_OL::findOrFail($id);
  if(is_null($IMP_AP_OL)) return 0;
  RegistroIMP_AP_OL::destroy($id);
  return 1;
}

public function llenarIMP_AP_OL($id){
  $IMP_AP_OL = RegistroIMP_AP_OL::with('casinoIMP_AP_OL')->findOrFail($id);
  if(is_null($IMP_AP_OL)) return 0;

  return response()->json([
    'fecha' => $IMP_AP_OL->fecha_imp_ap_ol,
    'fecha_pres' => $IMP_AP_OL->fecha_presentacion,
    'casino' => $IMP_AP_OL->casinoIMP_AP_OL ? $IMP_AP_OL->casinoIMP_AP_OL->nombre : '-',
    'alicuota' => $IMP_AP_OL->alicuota,
    'qna' => $IMP_AP_OL->qna,
    'fecha_pago' => $IMP_AP_OL->fecha_pago,
    'monto_pagado' =>$IMP_AP_OL->monto_pagado,
    'monto_apuestas' => $IMP_AP_OL->monto_apuestas,
    'impuesto_determinado' => $IMP_AP_OL->impuesto_determinado,


  ]);

}

public function descargarIMP_AP_OLXlsx(Request $request)
{
    $casinoId = $request->query('casino');
    $desde = $request->query('desde');
    $hasta = $request->query('hasta');

    $casino = Casino::findOrFail($casinoId);

    $query = RegistroIMP_AP_OL::select([
        DB::raw("YEAR(fecha_imp_ap_ol) AS anio"),
        DB::raw("MONTHNAME(fecha_imp_ap_ol) AS Mes"),
        DB::raw("qna AS 'QNA'"),
        DB::raw("DATE_FORMAT(fecha_presentacion,'%d/%m/%Y') AS `Fecha Presentación`"),
        DB::raw("DATE_FORMAT(fecha_pago,'%d/%m/%Y') AS `Fecha Pago`"),
        DB::raw("monto_pagado AS 'Monto Pagado'"),
        DB::raw("monto_apuestas AS 'Monto Apuestas'"),
        DB::raw("alicuota AS Alicuota"),
        DB::raw("impuesto_determinado AS 'Impuesto Determinado'"),
    ])
    ->where('casino', $casinoId);

    if ($desde) {
        $query->where('fecha_imp_ap_ol', '>=', $desde . '-01');
    }

    if ($hasta) {
        $query->where('fecha_imp_ap_ol', '<=', $hasta . '-31');
    }

    $datos = $query->orderBy('fecha_imp_ap_ol')
              ->orderBy('qna')
              ->get()
              ->groupBy('anio');


    $filename = "registro_IMP_AP_OL_" . str_replace(' ', '_', strtolower($casino->nombre));

    return Excel::create($filename, function($excel) use ($datos, $casino, $casinoId) {
        $excel->sheet('IMP_AP_OL', function($sheet) use ($datos, $casino, $casinoId) {
            $fila = 1;

            $sheet->row($fila, [
                'Mes',
                'QNA',
                'Presentación DDJJ',
                'Fecha de Pago',
                'Monto Pagado',
                'Monto Apuestas',
                'Alicuota',
                'Impuesto Determinado',
            ]);
            $sheet->cells("A1:H1", function($cells) use ($casinoId) {
                switch ($casinoId) {
                case 1:
                    $color = '#339966';

                    break;
                case 2:
                    $color = '#ff0000';

                    break;
                case 3:
                    $color = '#ffcc00';

                    break;
                default:
                    $color = '#222222';

            }

                $cells->setBackground($color);
                $cells->setFontColor('#000000');
                $cells->setFontWeight('bold');
                $cells->setAlignment('center');
            });

            $sheet->getStyle("A1:H1")->getAlignment()->setWrapText(true)->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
            $sheet->setHeight(1, 50);
            $sheet->cells("A1:H999", function($cells){
                $cells->setFontFamily('Arial');
                $cells->setFontSize(10);
            });

            $fila++;

            foreach ($datos as $anio => $registros) {
    $sheet->mergeCells("A{$fila}:H{$fila}");
    $sheet->setCellValue("A{$fila}", $anio);
    $sheet->cells("A{$fila}:H{$fila}", function($cells){
        $cells->setBackground('#CCCCCC');
        $cells->setFontWeight('bold');
        $cells->setFontSize(13);
        $cells->setAlignment('center');
    });
    $sheet->setHeight($fila, 20);
    $fila++;

    $registrosPorMes = $registros->groupBy('Mes');

    foreach ($registrosPorMes as $mes => $itemsMes) {
        setlocale(LC_TIME, 'es_ES.UTF-8');
        $mesEsp = ucfirst(strftime('%B', strtotime($mes . ' 1')));

        $inicioMerge = $fila;
        foreach ($itemsMes as $r) {
            $sheet->row($fila, [
                $mesEsp,
                $r->{'QNA'} . ' °',
                $r->{'Fecha Presentación'},
                $r->{'Fecha Pago'},
                "$ " . number_format($r->{'Monto Pagado'}, 2, ',', '.'),
                "$ " . number_format($r->{'Monto Apuestas'}, 2, ',', '.'),
                number_format($r->Alicuota, 2, ',', '.') . " %",
                "$ " . number_format($r->{'Impuesto Determinado'}, 2, ',', '.'),
            ]);

            $sheet->cells("A{$fila}", function($cells){
                $cells->setBackground('#FFFF99');
                $cells->setFontWeight('bold');
                $cells->setAlignment('left');
            });
            $sheet->cells("B{$fila}:H{$fila}", function($cells){
                $cells->setAlignment('center');
            });

            $fila++;
        }


        if ($itemsMes->count() > 1) {
            $finMerge = $fila - 1;
            $sheet->mergeCells("A{$inicioMerge}:A{$finMerge}");
            $sheet->cells("A{$inicioMerge}:A{$finMerge}", function($cells){
                $cells->setAlignment('center');
                $cells->setValignment('center');
            });
        }



                    $sheet->getStyle("A{$fila}:H{$fila}")->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
                }
            }

            $sheet->getStyle("A1:H1")->applyFromArray([
                'borders' => [
                    'allborders' => [
                        'style' => PHPExcel_Style_Border::BORDER_THICK,
                        'color' => ['argb' => 'FF000000']
                    ]
                ]
            ]);

            $sheet->getStyle("A2:H" . ($fila - 1))->applyFromArray([
                'borders' => [
                    'allborders' => [
                        'style' => PHPExcel_Style_Border::BORDER_THIN,
                        'color' => ['argb' => 'FF000000']
                    ]
                ]
            ]);

            $anchos = [
                9, 5, 17, 11, 18, 16, 11, 18,
                16, 11, 20, 18, 15, 15, 18, 20
            ];
            foreach (range('A', 'H') as $i => $col) {
                $sheet->setWidth($col, $anchos[$i]);
            }

            $sheet->freezeFirstRow();
        });
    })->export('xlsx');
}

public function descargarIMP_AP_OLXlsxTodos(Request $request)
{
    $desde = $request->query('desde');
    $hasta = $request->query('hasta');

    $user = Usuario::find(session('id_usuario'));
    $casinos = $user->casinos->pluck('nombre', 'id_casino');

    return Excel::create('registro_IMP_AP_OL_todos', function($excel) use ($casinos, $desde, $hasta) {
        foreach ($casinos as $casinoId => $casinoNombre) {

          $query = RegistroIMP_AP_OL::select([
              DB::raw("YEAR(fecha_imp_ap_ol) AS anio"),
              DB::raw("MONTHNAME(fecha_imp_ap_ol) AS Mes"),
              DB::raw("qna AS 'QNA'"),
              DB::raw("DATE_FORMAT(fecha_presentacion,'%d/%m/%Y') AS `Fecha Presentación`"),
              DB::raw("DATE_FORMAT(fecha_pago,'%d/%m/%Y') AS `Fecha Pago`"),
              DB::raw("monto_pagado AS 'Monto Pagado'"),
              DB::raw("monto_apuestas AS 'Monto Apuestas'"),
              DB::raw("alicuota AS Alicuota"),
              DB::raw("impuesto_determinado AS 'Impuesto Determinado'"),
          ])
          ->where('casino', $casinoId);

          if ($desde) {
              $query->where('fecha_imp_ap_ol', '>=', $desde . '-01');
          }

          if ($hasta) {
              $query->where('fecha_imp_ap_ol', '<=', $hasta . '-31');
          }

          $datos = $query->orderBy('fecha_imp_ap_ol')
                    ->orderBy('qna')
                    ->get()
                    ->groupBy('anio');


          $excel->sheet($casinoNombre, function($sheet) use ($datos, $casinoId) {
                          $fila = 1;

          $sheet->row($fila, [
              'Mes',
              'QNA',
              'Presentación DDJJ',
              'Fecha de Pago',
              'Monto Pagado',
              'Monto Apuestas',
              'Alicuota',
              'Impuesto Determinado',
          ]);
          $sheet->cells("A1:H1", function($cells) use ($casinoId) {
              switch ($casinoId) {
              case 1:
                  $color = '#339966';

                  break;
              case 2:
                  $color = '#ff0000';

                  break;
              case 3:
                  $color = '#ffcc00';

                  break;
              default:
                  $color = '#222222';

          }

              $cells->setBackground($color);
              $cells->setFontColor('#000000');
              $cells->setFontWeight('bold');
              $cells->setAlignment('center');
          });

          $sheet->getStyle("A1:H1")->getAlignment()->setWrapText(true)->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
          $sheet->setHeight(1, 50);
          $sheet->cells("A1:H999", function($cells){
              $cells->setFontFamily('Arial');
              $cells->setFontSize(10);
          });

          $fila++;

          foreach ($datos as $anio => $registros) {
  $sheet->mergeCells("A{$fila}:H{$fila}");
  $sheet->setCellValue("A{$fila}", $anio);
  $sheet->cells("A{$fila}:H{$fila}", function($cells){
      $cells->setBackground('#CCCCCC');
      $cells->setFontWeight('bold');
      $cells->setFontSize(13);
      $cells->setAlignment('center');
  });
  $sheet->setHeight($fila, 20);
  $fila++;

  $registrosPorMes = $registros->groupBy('Mes');

  foreach ($registrosPorMes as $mes => $itemsMes) {
      setlocale(LC_TIME, 'es_ES.UTF-8');
      $mesEsp = ucfirst(strftime('%B', strtotime($mes . ' 1')));

      $inicioMerge = $fila;
      foreach ($itemsMes as $r) {
          $sheet->row($fila, [
              $mesEsp,
              $r->{'QNA'} . ' °',
              $r->{'Fecha Presentación'},
              $r->{'Fecha Pago'},
              "$ " . number_format($r->{'Monto Pagado'}, 2, ',', '.'),
              "$ " . number_format($r->{'Monto Apuestas'}, 2, ',', '.'),
              number_format($r->Alicuota, 2, ',', '.') . " %",
              "$ " . number_format($r->{'Impuesto Determinado'}, 2, ',', '.'),
          ]);

          $sheet->cells("A{$fila}", function($cells){
              $cells->setBackground('#FFFF99');
              $cells->setFontWeight('bold');
              $cells->setAlignment('left');
          });
          $sheet->cells("B{$fila}:H{$fila}", function($cells){
              $cells->setAlignment('center');
          });

          $fila++;
      }


      if ($itemsMes->count() > 1) {
          $finMerge = $fila - 1;
          $sheet->mergeCells("A{$inicioMerge}:A{$finMerge}");
          $sheet->cells("A{$inicioMerge}:A{$finMerge}", function($cells){
              $cells->setAlignment('center');
              $cells->setValignment('center');
          });
      }



                  $sheet->getStyle("A{$fila}:H{$fila}")->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
              }
          }

          $sheet->getStyle("A1:H1")->applyFromArray([
              'borders' => [
                  'allborders' => [
                      'style' => PHPExcel_Style_Border::BORDER_THICK,
                      'color' => ['argb' => 'FF000000']
                  ]
              ]
          ]);

          $sheet->getStyle("A2:H" . ($fila - 1))->applyFromArray([
              'borders' => [
                  'allborders' => [
                      'style' => PHPExcel_Style_Border::BORDER_THIN,
                      'color' => ['argb' => 'FF000000']
                  ]
              ]
          ]);

          $anchos = [
              9, 5, 17, 11, 18, 16, 11, 18,
              16, 11, 20, 18, 15, 15, 18, 20
          ];
          foreach (range('A', 'H') as $i => $col) {
              $sheet->setWidth($col, $anchos[$i]);
          }

          $sheet->freezeFirstRow();
      });
    }
    })->export('xlsx');
}


public function descargarIMP_AP_OLCsv(Request $request)
{
    $casinoId = $request->query('casino');
    $desde    = $request->query('desde');
    $hasta    = $request->query('hasta');
    $user = Usuario::find(session('id_usuario'));
    $allowedCasinoIds = $user->casinos->pluck('id_casino')->toArray();

    $registros = RegistroIMP_AP_OL::with('casinoIMP_AP_OL')
        ->whereIn('casino',$allowedCasinoIds)
        ->when($casinoId && $casinoId != 4, function ($q) use ($casinoId) {
            $q->where('casino', $casinoId);
        })
        ->when($desde, function ($q) use ($desde) {
            $q->where('fecha_imp_ap_ol', '>=', $desde . '-01');
        })
        ->when($hasta, function ($q) use ($hasta) {
            $q->where('fecha_imp_ap_ol', '<=', $hasta . '-01');
        })
        ->orderBy('casino')
        ->orderBy('fecha_imp_ap_ol')
        ->orderBy('qna')
        ->get();

    $csv = [];
    $csv[] = ['Casino', 'Año', 'Mes','QNA', 'Fecha Presentación de la Declaración jurada','Fecha Pago', 'Monto Pagado','Monto Apuestas', 'Alicuota', 'Impuesto Determinado'];
    foreach ($registros as $r) {
        setlocale(LC_TIME, 'es_ES.UTF-8');
        $anio     = date('Y', strtotime($r->fecha_imp_ap_ol));
        $mes      = strftime('%B', strtotime($r->fecha_imp_ap_ol));
        $qna = $r->qna;
        $pres     = date('d/m/Y', strtotime($r->fecha_presentacion));
        $pago     = date('d/m/Y', strtotime($r->fecha_pago));
        $monto_pagado = number_format($r->monto_pagado, 2, '.', '');
        $monto_apuestas = number_format($r->monto_apuestas, 2, '.', '');
        $ali = number_format($r->alicuota, 2, '.', '');
        $imp = number_format($r->impuesto_determinado, 2, '.', '');
        $casino   = $r->casinoIMP_AP_OL ? $r->casinoIMP_AP_OL->nombre : '-';

        $csv[] = [
            $casino,
            $anio,
            ucfirst($mes),
            $qna,
            $pres,
            $pago,
            $monto_pagado,
            $monto_apuestas,
            $ali,
            $imp,

        ];
    }

    $nombreCasino = $casinoId == 4 ? 'todos' : Casino::find($casinoId)->nombre ?? 'desconocido';
    $filename = "IMP_AP_OL_{$nombreCasino}_" . date('Ymd_His') . ".csv";

    $handle = fopen('php://temp', 'r+');
    foreach ($csv as $linea) {
        fputcsv($handle, $linea, ',');
    }
    rewind($handle);
    $contenido = stream_get_contents($handle);
    fclose($handle);

    return response($contenido, 200, [
        'Content-Type'        => 'text/csv; charset=UTF-8',
        'Content-Disposition' => "attachment; filename=\"$filename\"",
        'Cache-Control'       => 'no-store, no-cache'
    ]);
}

//IMP_AP_OL IMPUESTO A APUESTAS MTM

public function archivosIMP_AP_MTM($id)
{
    $IMP_AP_MTM = RegistroIMP_AP_MTM::with('archivos')->findOrFail($id);

    $files = $IMP_AP_MTM->archivos->map(function($a){
        return [
            'id'     => $a->id_registro_archivo,
            'nombre' => basename($a->path),
            'url'    => \Illuminate\Support\Facades\Storage::url($a->path),
            'fecha'  => $a->fecha_toma,
        ];
    });

    return response()->json($files->values());
}


public function actualizarIMP_AP_MTM(Request $request, $id)
{
    $r = RegistroIMP_AP_MTM::findOrFail($id);

    $r->fecha_imp_ap_mtm   = $request->input('fecha_IMP_AP_MTM') . '-01';
    $r->qna                = $request->input('qnaIMP_AP_MTM');
    $r->monto_pagado       = $request->input('monto_pagadoIMP_AP_MTM');
    $r->monto_apuestas     = $request->input('monto_apuestasIMP_AP_MTM');
    $r->alicuota           = $request->input('alicuotaIMP_AP_MTM');
    $r->impuesto_determinado = $request->input('impuesto_determinadoIMP_AP_MTM');
    $r->cant_mtm           = $request->input('cantMTM_IMP_AP_MTM');
    $r->fecha_presentacion = $request->input('fecha_IMP_AP_MTMPres');
    $r->fecha_pago         = $request->input('fecha_pago_IMP_AP_MTM');
    $r->casino             = $request->input('casinoIMP_AP_MTM');
    $r->save();

    $saved = 0;
    foreach (Arr::wrap($request->file('uploadIMP_AP_MTM')) as $file) {
        if (!($file instanceof UploadedFile) || !$file->isValid()) continue;
        $base = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
        $ext  = $file->getClientOriginalExtension();
        $safe = preg_replace('/\s+/', '_', $base);
        $name = time().'_'.Str::random(6).'_'.$safe.($ext ? '.'.$ext : '');
        $file->storeAs('public/RegistroIMP_AP_MTM', $name);
        $r->archivos()->create([
            'path'       => $name,
            'usuario'    => UsuarioController::getInstancia()->quienSoy()['usuario']['id_usuario'],
            'fecha_toma' => date('Y-m-d H:i:s'),
        ]);
        $saved++;
    }

    return response()->json(['success' => true, 'saved_files' => $saved]);
}

public function llenarIMP_AP_MTMEdit($id){
    $r = RegistroIMP_AP_MTM::findOrFail($id);

    $f  = $r->fecha_imp_ap_mtm;
    $fp = $r->fecha_presentacion;
    $pg = $r->fecha_pago;

    $fecha      = is_string($f)  ? substr($f, 0, 7)  : ($f  ? $f->format('Y-m')    : null);
    $fecha_pres = is_string($fp) ? substr($fp, 0, 10) : ($fp ? $fp->format('Y-m-d') : null);
    $fecha_pago = is_string($pg) ? substr($pg, 0, 10) : ($pg ? $pg->format('Y-m-d') : null);

    return response()->json([
        'fecha'                 => $fecha,
        'fecha_pres'            => $fecha_pres,
        'fecha_pago'            => $fecha_pago,
        'casino'                => $r->casino,
        'qna'                   => $r->qna,
        'monto_pagado'          => $r->monto_pagado,
        'monto_apuestas'        => $r->monto_apuestas,
        'alicuota'              => $r->alicuota,
        'impuesto_determinado'  => $r->impuesto_determinado,
        'cant_mtm'              => $r->cant_mtm,
    ]);
}

public function guardarIMP_AP_MTM(Request $request){

      $path = null;

      try {
          DB::beginTransaction();

          $IMP_AP_MTM = new RegistroIMP_AP_MTM();
          $IMP_AP_MTM->fecha_imp_ap_mtm = $request->fecha_IMP_AP_MTM.'-01';

          $IMP_AP_MTM->qna = $request->qnaIMP_AP_MTM;
          $IMP_AP_MTM->monto_pagado = $request->monto_pagadoIMP_AP_MTM;
          $IMP_AP_MTM->monto_apuestas= $request->monto_apuestasIMP_AP_MTM;
          $IMP_AP_MTM->alicuota = $request->alicuotaIMP_AP_MTM;
          $IMP_AP_MTM->impuesto_determinado = $request->impuesto_determinadoIMP_AP_MTM;
          $IMP_AP_MTM->cant_mtm = $request->cantMTM_IMP_AP_MTM;
          $IMP_AP_MTM->fecha_presentacion = $request->fecha_IMP_AP_MTMPres;
          $IMP_AP_MTM->fecha_pago = $request->fecha_pago_IMP_AP_MTM;
          $IMP_AP_MTM->casino = $request->casinoIMP_AP_MTM;

          $IMP_AP_MTM->fecha_toma = date('Y-m-d h:i:s', time());
          $IMP_AP_MTM->usuario = UsuarioController::getInstancia()->quienSoy()['usuario']['id_usuario'];
          $IMP_AP_MTM->save();
          $files = Arr::wrap($request->file('uploadIMP_AP_MTM'));
                    foreach ($files as $file) {
                        if (!($file instanceof UploadedFile) || !$file->isValid()) continue;

                        $base = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
                        $ext  = $file->getClientOriginalExtension();
                        $safe = preg_replace('/\s+/', '_', $base);
                        $filename = time().'_'.Str::random(6).'_'.$safe.($ext?'.'.$ext:'');

                        $file->storeAs('public/RegistroIMP_AP_MTM', $filename);

                        $IMP_AP_MTM->archivos()->create([
                            'path'       => $filename,
                            'usuario'    => $IMP_AP_MTM->usuario,
                            'fecha_toma' => date('Y-m-d H:i:s'),
                        ]);
                    }
          DB::commit();

          return response()->json([
            'success' => true,
             'id' => $IMP_AP_MTM->id_registroIMP_AP_MTM,
             'IMP_AP_MTM'  => $request->all(),
             'path'    => $path,
             'url'     => Storage::url($path)
           ]);
      } catch (\Exception $e) {
          DB::rollBack();
          return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
      }

}

public function ultimasIMP_AP_MTM(Request $request)
{
    $page    = max(1, (int)$request->query('page', 1));
    $perPage = max(1, (int)$request->query('page_size', 20));

    $user = Usuario::find(session('id_usuario'));
    $allowedCasinoIds = $user->casinos->pluck('id_casino')->toArray();


    $query = RegistroIMP_AP_MTM::with('casinoIMP_AP_MTM')
              ->whereIn('casino', $allowedCasinoIds)
              ->withCount('archivos')
              ->orderBy('fecha_IMP_AP_MTM', 'desc')
              ->orderBy('casino', 'desc')
              ->orderBy('qna', 'desc');


    if ($c = $request->query('id_casino')) {
      $query->where('casino', $c);
    }
    if ($desde = $request->query('desde')){
      $desde = $desde."-01";
      $query->where('fecha_IMP_AP_MTM',">=",$desde);
    }
    if ($hasta = $request->query('hasta')){
      $hasta = $hasta."-01";
      $query->where('fecha_IMP_AP_MTM',"<=",$hasta);
    }

    $total = $query->count();

    $registros = $query
        ->skip(($page - 1) * $perPage)
        ->take($perPage)
        ->get();

    $datos = $registros->map(function($r) {
        return [
            'id_registroIMP_AP_MTM' => $r->id_registroIMP_AP_MTM,
            'fecha_IMP_AP_MTM'   => $r->fecha_imp_ap_mtm,
            'fecha_presentacion' => $r->fecha_presentacion,
            'cant_mtm' => $r->cant_mtm,
            'casino'      => $r->casinoIMP_AP_MTM ? $r->casinoIMP_AP_MTM->nombre : '-',
            'qna' => $r->qna,
            'tiene_archivos' => $r->archivos_count>0, ];
    });

    return response()->json([
        'registros'  => $datos,
        'pagination' => [
            'current_page' => $page,
            'per_page'     => $perPage,
            'total'        => $total,
        ],
    ]);
}


public function eliminarIMP_AP_MTM($id){
  $IMP_AP_MTM = RegistroIMP_AP_MTM::findOrFail($id);
  if(is_null($IMP_AP_MTM)) return 0;
  RegistroIMP_AP_MTM::destroy($id);
  return 1;
}

public function llenarIMP_AP_MTM($id){
  $IMP_AP_MTM = RegistroIMP_AP_MTM::with('casinoIMP_AP_MTM')->findOrFail($id);
  if(is_null($IMP_AP_MTM)) return 0;

  return response()->json([
    'fecha' => $IMP_AP_MTM->fecha_imp_ap_mtm,
    'fecha_pres' => $IMP_AP_MTM->fecha_presentacion,
    'casino' => $IMP_AP_MTM->casinoIMP_AP_MTM ? $IMP_AP_MTM->casinoIMP_AP_MTM->nombre : '-',
    'alicuota' => $IMP_AP_MTM->alicuota,
    'qna' => $IMP_AP_MTM->qna,
    'cant_mtm' => $IMP_AP_MTM->cant_mtm,
    'fecha_pago' => $IMP_AP_MTM->fecha_pago,
    'monto_pagado' =>$IMP_AP_MTM->monto_pagado,
    'monto_apuestas' => $IMP_AP_MTM->monto_apuestas,
    'impuesto_determinado' => $IMP_AP_MTM->impuesto_determinado,


  ]);

}



public function descargarIMP_AP_MTMXlsx(Request $request)
{
    $casinoId = $request->query('casino');
    $desde = $request->query('desde');
    $hasta = $request->query('hasta');

    $casino = Casino::findOrFail($casinoId);

    $query = RegistroIMP_AP_MTM::select([
        DB::raw("YEAR(fecha_imp_ap_mtm) AS anio"),
        DB::raw("MONTHNAME(fecha_imp_ap_mtm) AS Mes"),
        DB::raw("qna AS 'QNA'"),
        DB::raw("DATE_FORMAT(fecha_presentacion,'%d/%m/%Y') AS `Fecha Presentación`"),
        DB::raw("DATE_FORMAT(fecha_pago,'%d/%m/%Y') AS `Fecha Pago`"),
        DB::raw("monto_pagado AS 'Monto Pagado'"),
        DB::raw("cant_MTM AS 'Cantidad MTM'"),
        DB::raw("monto_apuestas AS 'Monto Apuestas'"),
        DB::raw("alicuota AS Alicuota"),
        DB::raw("impuesto_determinado AS 'Impuesto Determinado'"),
    ])
    ->where('casino', $casinoId);

    if ($desde) {
        $query->where('fecha_imp_ap_mtm', '>=', $desde . '-01');
    }

    if ($hasta) {
        $query->where('fecha_imp_ap_mtm', '<=', $hasta . '-31');
    }

    $datos = $query->orderBy('fecha_imp_ap_mtm')
              ->orderBy('qna')
              ->get()
              ->groupBy('anio');


    $filename = "registro_IMP_AP_MTM_" . str_replace(' ', '_', strtolower($casino->nombre));

    return Excel::create($filename, function($excel) use ($datos, $casino, $casinoId) {
        $excel->sheet('IMP_AP_MTM', function($sheet) use ($datos, $casino, $casinoId) {
            $fila = 1;

            $sheet->row($fila, [
                'Mes',
                'QNA',
                'Presentación DDJJ',
                'Fecha de Pago',
                'Monto Pagado',
                'Cant. MTM',
                'Monto Apuestas',
                'Alicuota',
                'Impuesto Determinado',
            ]);
            $sheet->cells("A1:I1", function($cells) use ($casinoId) {
                switch ($casinoId) {
                case 1:
                    $color = '#339966';

                    break;
                case 2:
                    $color = '#ff0000';

                    break;
                case 3:
                    $color = '#ffcc00';

                    break;
                default:
                    $color = '#222222';

            }

                $cells->setBackground($color);
                $cells->setFontColor('#000000');
                $cells->setFontWeight('bold');
                $cells->setAlignment('center');
            });

            $sheet->getStyle("A1:I1")->getAlignment()->setWrapText(true)->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
            $sheet->setHeight(1, 50);
            $sheet->cells("A1:I999", function($cells){
                $cells->setFontFamily('Arial');
                $cells->setFontSize(10);
            });

            $fila++;

            foreach ($datos as $anio => $registros) {
    $sheet->mergeCells("A{$fila}:I{$fila}");
    $sheet->setCellValue("A{$fila}", $anio);
    $sheet->cells("A{$fila}:I{$fila}", function($cells){
        $cells->setBackground('#CCCCCC');
        $cells->setFontWeight('bold');
        $cells->setFontSize(13);
        $cells->setAlignment('center');
    });
    $sheet->setHeight($fila, 20);
    $fila++;

    $registrosPorMes = $registros->groupBy('Mes');

    foreach ($registrosPorMes as $mes => $itemsMes) {
        setlocale(LC_TIME, 'es_ES.UTF-8');
        $mesEsp = ucfirst(strftime('%B', strtotime($mes . ' 1')));

        $inicioMerge = $fila;
        foreach ($itemsMes as $r) {
            $sheet->row($fila, [
                $mesEsp,
                $r->{'QNA'} . ' °',
                $r->{'Fecha Presentación'},
                $r->{'Fecha Pago'},
                "$ " . number_format($r->{'Monto Pagado'}, 2, ',', '.'),
                $r->{'Cantidad MTM'},
                "$ " . number_format($r->{'Monto Apuestas'}, 2, ',', '.'),
                number_format($r->Alicuota, 2, ',', '.') . " %",
                "$ " . number_format($r->{'Impuesto Determinado'}, 2, ',', '.'),
            ]);

            $sheet->cells("A{$fila}", function($cells){
                $cells->setBackground('#FFFF99');
                $cells->setFontWeight('bold');
                $cells->setAlignment('left');
            });
            $sheet->cells("B{$fila}:I{$fila}", function($cells){
                $cells->setAlignment('center');
            });

            $fila++;
        }


        if ($itemsMes->count() > 1) {
            $finMerge = $fila - 1;
            $sheet->mergeCells("A{$inicioMerge}:A{$finMerge}");
            $sheet->cells("A{$inicioMerge}:A{$finMerge}", function($cells){
                $cells->setAlignment('center');
                $cells->setValignment('center');
            });
        }



                    $sheet->getStyle("A{$fila}:I{$fila}")->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
                }
            }

            $sheet->getStyle("A1:I1")->applyFromArray([
                'borders' => [
                    'allborders' => [
                        'style' => PHPExcel_Style_Border::BORDER_THICK,
                        'color' => ['argb' => 'FF000000']
                    ]
                ]
            ]);

            $sheet->getStyle("A2:I" . ($fila - 1))->applyFromArray([
                'borders' => [
                    'allborders' => [
                        'style' => PHPExcel_Style_Border::BORDER_THIN,
                        'color' => ['argb' => 'FF000000']
                    ]
                ]
            ]);

            $anchos = [
                9, 5, 17, 11, 18, 16, 11, 18,
                18, 11, 20, 18, 15, 15, 18, 20
            ];
            foreach (range('A', 'I') as $i => $col) {
                $sheet->setWidth($col, $anchos[$i]);
            }

            $sheet->freezeFirstRow();
        });
    })->export('xlsx');
}

public function descargarIMP_AP_MTMXlsxTodos(Request $request)
{
    $desde = $request->query('desde');
    $hasta = $request->query('hasta');

    $user = Usuario::find(session('id_usuario'));
    $casinos = $user->casinos->pluck('nombre', 'id_casino');

    return Excel::create('registro_IMP_AP_MTM_todos', function($excel) use ($casinos, $desde, $hasta) {
        foreach ($casinos as $casinoId => $casinoNombre) {

          $query = RegistroIMP_AP_MTM::select([
              DB::raw("YEAR(fecha_imp_ap_mtm) AS anio"),
              DB::raw("MONTHNAME(fecha_imp_ap_mtm) AS Mes"),
              DB::raw("qna AS 'QNA'"),
              DB::raw("DATE_FORMAT(fecha_presentacion,'%d/%m/%Y') AS `Fecha Presentación`"),
              DB::raw("DATE_FORMAT(fecha_pago,'%d/%m/%Y') AS `Fecha Pago`"),
              DB::raw("monto_pagado AS 'Monto Pagado'"),
              DB::raw("monto_apuestas AS 'Monto Apuestas'"),
              DB::raw("cant_mtm AS 'Cantidad MTM'"),
              DB::raw("alicuota AS Alicuota"),
              DB::raw("impuesto_determinado AS 'Impuesto Determinado'"),
          ])
          ->where('casino', $casinoId);

          if ($desde) {
              $query->where('fecha_imp_ap_mtm', '>=', $desde . '-01');
          }

          if ($hasta) {
              $query->where('fecha_imp_ap_mtm', '<=', $hasta . '-31');
          }

          $datos = $query->orderBy('fecha_imp_ap_mtm')
                    ->orderBy('qna')
                    ->get()
                    ->groupBy('anio');


          $excel->sheet($casinoNombre, function($sheet) use ($datos, $casinoId) {
                          $fila = 1;

          $sheet->row($fila, [
              'Mes',
              'QNA',
              'Presentación DDJJ',
              'Fecha de Pago',
              'Monto Pagado',
              'Cant. MTM',
              'Monto Apuestas',
              'Alicuota',
              'Impuesto Determinado',
          ]);
          $sheet->cells("A1:I1", function($cells) use ($casinoId) {
              switch ($casinoId) {
              case 1:
                  $color = '#339966';

                  break;
              case 2:
                  $color = '#ff0000';

                  break;
              case 3:
                  $color = '#ffcc00';

                  break;
              default:
                  $color = '#222222';

          }

              $cells->setBackground($color);
              $cells->setFontColor('#000000');
              $cells->setFontWeight('bold');
              $cells->setAlignment('center');
          });

          $sheet->getStyle("A1:I1")->getAlignment()->setWrapText(true)->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
          $sheet->setHeight(1, 50);
          $sheet->cells("A1:I999", function($cells){
              $cells->setFontFamily('Arial');
              $cells->setFontSize(10);
          });

          $fila++;

          foreach ($datos as $anio => $registros) {
  $sheet->mergeCells("A{$fila}:I{$fila}");
  $sheet->setCellValue("A{$fila}", $anio);
  $sheet->cells("A{$fila}:I{$fila}", function($cells){
      $cells->setBackground('#CCCCCC');
      $cells->setFontWeight('bold');
      $cells->setFontSize(13);
      $cells->setAlignment('center');
  });
  $sheet->setHeight($fila, 20);
  $fila++;

  $registrosPorMes = $registros->groupBy('Mes');

  foreach ($registrosPorMes as $mes => $itemsMes) {
      setlocale(LC_TIME, 'es_ES.UTF-8');
      $mesEsp = ucfirst(strftime('%B', strtotime($mes . ' 1')));

      $inicioMerge = $fila;
      foreach ($itemsMes as $r) {
          $sheet->row($fila, [
              $mesEsp,
              $r->{'QNA'} . ' °',
              $r->{'Fecha Presentación'},
              $r->{'Fecha Pago'},
              "$ " . number_format($r->{'Monto Pagado'}, 2, ',', '.'),
              $r->{'Cantidad MTM'},
              "$ " . number_format($r->{'Monto Apuestas'}, 2, ',', '.'),
              number_format($r->Alicuota, 2, ',', '.') . " %",
              "$ " . number_format($r->{'Impuesto Determinado'}, 2, ',', '.'),
          ]);

          $sheet->cells("A{$fila}", function($cells){
              $cells->setBackground('#FFFF99');
              $cells->setFontWeight('bold');
              $cells->setAlignment('left');
          });
          $sheet->cells("B{$fila}:I{$fila}", function($cells){
              $cells->setAlignment('center');
          });

          $fila++;
      }


      if ($itemsMes->count() > 1) {
          $finMerge = $fila - 1;
          $sheet->mergeCells("A{$inicioMerge}:A{$finMerge}");
          $sheet->cells("A{$inicioMerge}:A{$finMerge}", function($cells){
              $cells->setAlignment('center');
              $cells->setValignment('center');
          });
      }



                  $sheet->getStyle("A{$fila}:H{$fila}")->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
              }
          }

          $sheet->getStyle("A1:I1")->applyFromArray([
              'borders' => [
                  'allborders' => [
                      'style' => PHPExcel_Style_Border::BORDER_THICK,
                      'color' => ['argb' => 'FF000000']
                  ]
              ]
          ]);

          $sheet->getStyle("A2:I" . ($fila - 1))->applyFromArray([
              'borders' => [
                  'allborders' => [
                      'style' => PHPExcel_Style_Border::BORDER_THIN,
                      'color' => ['argb' => 'FF000000']
                  ]
              ]
          ]);

          $anchos = [
              9, 5, 17, 11, 18, 16, 11, 18,
              18, 11, 20, 18, 15, 15, 18, 20
          ];
          foreach (range('A', 'I') as $i => $col) {
              $sheet->setWidth($col, $anchos[$i]);
          }

          $sheet->freezeFirstRow();
      });
    }
    })->export('xlsx');
}


public function descargarIMP_AP_MTMCsv(Request $request)
{
    $casinoId = $request->query('casino');
    $desde    = $request->query('desde');
    $hasta    = $request->query('hasta');
    $user = Usuario::find(session('id_usuario'));
    $allowedCasinoIds = $user->casinos->pluck('id_casino')->toArray();

    $registros = RegistroIMP_AP_MTM::with('casinoIMP_AP_MTM')
        ->whereIn('casino',$allowedCasinoIds)
        ->when($casinoId && $casinoId != 4, function ($q) use ($casinoId) {
            $q->where('casino', $casinoId);
        })
        ->when($desde, function ($q) use ($desde) {
            $q->where('fecha_imp_ap_mtm', '>=', $desde . '-01');
        })
        ->when($hasta, function ($q) use ($hasta) {
            $q->where('fecha_imp_ap_mtm', '<=', $hasta . '-01');
        })
        ->orderBy('casino')
        ->orderBy('fecha_imp_ap_mtm')
        ->orderBy('qna')
        ->get();

    $csv = [];
    $csv[] = ['Casino', 'Año', 'Mes','QNA', 'Fecha Presentación de la Declaración jurada','Fecha Pago', 'Monto Pagado','Cant. MTM','Monto Apuestas', 'Alicuota', 'Impuesto Determinado'];
    foreach ($registros as $r) {
        setlocale(LC_TIME, 'es_ES.UTF-8');
        $anio     = date('Y', strtotime($r->fecha_imp_ap_mtm));
        $mes      = strftime('%B', strtotime($r->fecha_imp_ap_mtm));
        $qna = $r->qna;
        $pres     = date('d/m/Y', strtotime($r->fecha_presentacion));
        $pago     = date('d/m/Y', strtotime($r->fecha_pago));
        $monto_pagado = number_format($r->monto_pagado, 2, '.', '');
        $cant_mtm = $r->cant_mtm;
        $monto_apuestas = number_format($r->monto_apuestas, 2, '.', '');
        $ali = number_format($r->alicuota, 2, '.', '');
        $imp = number_format($r->impuesto_determinado, 2, '.', '');
        $casino   = $r->casinoIMP_AP_MTM ? $r->casinoIMP_AP_MTM->nombre : '-';

        $csv[] = [
            $casino,
            $anio,
            ucfirst($mes),
            $qna,
            $pres,
            $pago,
            $monto_pagado,
            $cant_mtm,
            $monto_apuestas,
            $ali,
            $imp,

        ];
    }

    $nombreCasino = $casinoId == 4 ? 'todos' : Casino::find($casinoId)->nombre ?? 'desconocido';
    $filename = "IMP_AP_MTM_{$nombreCasino}_" . date('Ymd_His') . ".csv";

    $handle = fopen('php://temp', 'r+');
    foreach ($csv as $linea) {
        fputcsv($handle, $linea, ',');
    }
    rewind($handle);
    $contenido = stream_get_contents($handle);
    fclose($handle);

    return response($contenido, 200, [
        'Content-Type'        => 'text/csv; charset=UTF-8',
        'Content-Disposition' => "attachment; filename=\"$filename\"",
        'Cache-Control'       => 'no-store, no-cache'
    ]);
}

// IMPUESTO DEUDA ESTADO
public function guardarDeudaEstado(Request $request){

      $path = null;

      try {
          DB::beginTransaction();

          $DeudaEstado = new RegistroDeudaEstado();
          $DeudaEstado->fecha_DeudaEstado = $request->fecha_DeudaEstado.'-01';



          $DeudaEstado->registra_incumplimiento = $request->regIncumDeudaEstado;
          $DeudaEstado->incumplimiento = $request->incumDeudaEstado;
          $DeudaEstado->fecha_consulta = $request->fecha_DeudaEstadoPres;
          $DeudaEstado->casino = $request->casinoDeudaEstado;

          $DeudaEstado->fecha_toma = date('Y-m-d h:i:s', time());
          $DeudaEstado->usuario = UsuarioController::getInstancia()->quienSoy()['usuario']['id_usuario'];
          $DeudaEstado->save();
          $files = Arr::wrap($request->file('uploadDeudaEstado'));
          foreach ($files as $file) {
              if (!($file instanceof UploadedFile) || !$file->isValid()) continue;

              $base = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
              $ext  = $file->getClientOriginalExtension();
              $safe = preg_replace('/\s+/', '_', $base);
              $filename = time().'_'.Str::random(6).'_'.$safe.($ext?'.'.$ext:'');

              $file->storeAs('public/RegistroDeudaEstado', $filename);

              $DeudaEstado->archivos()->create([
                  'path'       => $filename,
                  'usuario'    => $DeudaEstado->usuario,
                  'fecha_toma' => date('Y-m-d H:i:s'),
              ]);
          }
          DB::commit();

          return response()->json([
            'success' => true,
             'id' => $DeudaEstado->id_registroDeudaEstado,
             'DeudaEstado'  => $request->all(),
             'path'    => $path,
             'url'     => Storage::url($path)
           ]);
      } catch (\Exception $e) {
          DB::rollBack();
          return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
      }

}

public function archivosDeudaEstado($id)
{
    $DeudaEstado = RegistroDeudaEstado::with('archivos')->findOrFail($id);

    $files = $DeudaEstado->archivos->map(function($a){
        return [
            'id'     => $a->id_registro_archivo,
            'nombre' => basename($a->path),
            'url'    => \Illuminate\Support\Facades\Storage::url($a->path),
            'fecha'  => $a->fecha_toma,
        ];
    });

    return response()->json($files->values());
}

public function llenarDeudaEstadoEdit($id){
  $d = RegistroDeudaEstado::findOrFail($id);

  return response()->json([
    'fecha'                    => $d->fecha_DeudaEstado,
    'casino'                   => $d->casino,
    'registra_incumplimiento'  => $d->registra_incumplimiento,
    'incumplimiento'           => $d->incumplimiento,
    'fecha_consulta'           => $d->fecha_consulta,
    'obs'                      => $d->observaciones ?? $d->observacion ?? null,
  ]);
}

public function actualizarDeudaEstado(Request $request, $id)
{
    $r = RegistroDeudaEstado::findOrFail($id);

    $r->fecha_DeudaEstado       = $request->input('fecha_DeudaEstado').'-01';
    $r->casino                  = $request->input('casinoDeudaEstado');
    $r->registra_incumplimiento = $request->input('regIncumDeudaEstado');
    if($r->registra_incumplimiento==1){
      $r->incumplimiento          = $request->input('incumDeudaEstado');
    }else{
      $r->incumplimiento = null;
    }
    $r->fecha_consulta          = $request->input('fecha_DeudaEstadoPres');
    $r->save();

    $saved = 0;
    $files = Arr::wrap($request->file('uploadDeudaEstado'));
    foreach ($files as $file) {
        if (!($file instanceof UploadedFile) || !$file->isValid()) continue;

        $base = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
        $ext  = $file->getClientOriginalExtension();
        $safe = preg_replace('/\s+/', '_', $base);
        $name = time().'_'.Str::random(6).'_'.$safe.($ext ? '.'.$ext : '');

        $file->storeAs('public/RegistroDeudaEstado', $name);

        $r->archivos()->create([
            'path'       => $name,
            'usuario'    => UsuarioController::getInstancia()->quienSoy()['usuario']['id_usuario'],
            'fecha_toma' => date('Y-m-d H:i:s'),
        ]);

        $saved++;
    }

    return response()->json(['success' => true, 'saved_files' => $saved]);
}


public function ultimasDeudaEstado(Request $request)
{
    $page    = max(1, (int)$request->query('page', 1));
    $perPage = max(1, (int)$request->query('page_size', 20));

    $user = Usuario::find(session('id_usuario'));
    $allowedCasinoIds = $user->casinos->pluck('id_casino')->toArray();


    $query = RegistroDeudaEstado::with('casinoDeudaEstado')
              ->withCount('archivos')
              ->whereIn('casino', $allowedCasinoIds)
              ->orderBy('fecha_DeudaEstado', 'desc')
              ->orderBy('casino', 'desc');


    if ($c = $request->query('id_casino')) {
      $query->where('casino', $c);
    }
    if ($desde = $request->query('desde')){
      $desde = $desde."-01";
      $query->where('fecha_DeudaEstado',">=",$desde);
    }
    if ($hasta = $request->query('hasta')){
      $hasta = $hasta."-01";
      $query->where('fecha_DeudaEstado',"<=",$hasta);
    }

    $total = $query->count();

    $registros = $query
        ->skip(($page - 1) * $perPage)
        ->take($perPage)
        ->get();

    $datos = $registros->map(function($r) {
        return [
            'id_registroDeudaEstado' => $r->id_registroDeudaEstado,
            'fecha_DeudaEstado'   => $r->fecha_DeudaEstado,
            'fecha_presentacion' => $r->fecha_consulta,
            'incumplimiento' => $r->registra_incumplimiento,
            'casino'      => $r->casinoDeudaEstado ? $r->casinoDeudaEstado->nombre : '-',
	           'tiene_archivos' => $r->archivos_count>0,
          ];
    });

    return response()->json([
        'registros'  => $datos,
        'pagination' => [
            'current_page' => $page,
            'per_page'     => $perPage,
            'total'        => $total,
        ],
    ]);
}


public function eliminarDeudaEstado($id){
  $DeudaEstado = RegistroDeudaEstado::findOrFail($id);
  if(is_null($DeudaEstado)) return 0;
  RegistroDeudaEstado::destroy($id);
  return 1;
}

public function llenarDeudaEstado($id){
  $DeudaEstado = RegistroDeudaEstado::with('casinoDeudaEstado')->findOrFail($id);
  if(is_null($DeudaEstado)) return 0;

  return response()->json([

    'incumplimiento' => $DeudaEstado->incumplimiento,


  ]);

}

public function descargarDeudaEstadoCsv(Request $request)
{
    $casinoId = $request->query('casino');
    $desde    = $request->query('desde');
    $hasta    = $request->query('hasta');
    $user = Usuario::find(session('id_usuario'));
    $allowedCasinoIds = $user->casinos->pluck('id_casino')->toArray();

    $registros = RegistroDeudaEstado::with('casinoDeudaEstado')
        ->whereIn('casino',$allowedCasinoIds)
        ->when($casinoId && $casinoId != 4, function ($q) use ($casinoId) {
            $q->where('casino', $casinoId);
        })
        ->when($desde, function ($q) use ($desde) {
            $q->where('fecha_DeudaEstado', '>=', $desde . '-01');
        })
        ->when($hasta, function ($q) use ($hasta) {
            $q->where('fecha_DeudaEstado', '<=', $hasta . '-01');
        })
        ->orderBy('casino')
        ->orderBy('fecha_DeudaEstado')
        ->get();

    $csv = [];
    $csv[] = ['Casino', 'Año', 'Mes', 'Fecha de Consulta', '¿Registra Incumplimiento? (SI/NO)', 'Incumplimiento'];

    foreach ($registros as $r) {
        setlocale(LC_TIME, 'es_ES.UTF-8');
        $anio     = date('Y', strtotime($r->fecha_DeudaEstado));
        $mes      = strftime('%B', strtotime($r->fecha_DeudaEstado));
        $pres     = date('d/m/Y', strtotime($r->fecha_consulta));
        $regIncum = $r->registra_incumplimiento==1 ? 'SI' : 'NO';
        $casino   = $r->casinoDeudaEstado ? $r->casinoDeudaEstado->nombre : '-';
        $incum      = $r->incumplimiento;

        $csv[] = [
            $casino,
            $anio,
            ucfirst($mes),
            $pres,
            $regIncum,
            $incum,
        ];
    }

    $nombreCasino = $casinoId == 4 ? 'todos' : Casino::find($casinoId)->nombre ?? 'desconocido';
    $filename = "DeudaEstado_{$nombreCasino}_" . date('Ymd_His') . ".csv";

    $handle = fopen('php://temp', 'r+');
    foreach ($csv as $linea) {
        fputcsv($handle, $linea, ',');
    }
    rewind($handle);
    $contenido = stream_get_contents($handle);
    fclose($handle);

    return response($contenido, 200, [
        'Content-Type'        => 'text/csv; charset=UTF-8',
        'Content-Disposition' => "attachment; filename=\"$filename\"",
        'Cache-Control'       => 'no-store, no-cache'
    ]);
}

public function descargarDeudaEstadoXlsx(Request $request)
{
    $casinoId = $request->query('casino');
    $desde = $request->query('desde');
    $hasta = $request->query('hasta');

    $casino = Casino::findOrFail($casinoId);

    $query = RegistroDeudaEstado::select([
        DB::raw("YEAR(fecha_DeudaEstado) AS anio"),
        DB::raw("MONTHNAME(fecha_DeudaEstado) AS Mes"),
        DB::raw("DATE_FORMAT(fecha_consulta,'%d/%m/%Y') AS `Fecha Consulta`"),
        DB::raw("registra_incumplimiento AS 'Registra Incumplimiento'"),
        DB::raw("incumplimiento"),
    ])
    ->where('casino', $casinoId);

    if ($desde) {
        $query->where('fecha_DeudaEstado', '>=', $desde . '-01');
    }

    if ($hasta) {
        $query->where('fecha_DeudaEstado', '<=', $hasta . '-31');
    }

    $datos = $query->orderBy('fecha_DeudaEstado')->get()->groupBy('anio');

    $filename = "registro_DeudaEstado_" . str_replace(' ', '_', strtolower($casino->nombre));

    return Excel::create($filename, function($excel) use ($datos, $casino, $casinoId) {
        $excel->sheet('DeudaEstado', function($sheet) use ($datos, $casino, $casinoId) {
            $fila = 1;

            $sheet->row($fila, [
                'Mes',
                'Fecha de Consulta',
                '¿Registra Incumplimiento? (SI/NO)',
                'Incumplimiento',
            ]);
            $sheet->cells("A1:D1", function($cells) use ($casinoId) {
                switch ($casinoId) {
                case 1:
                    $color = '#339966';

                    break;
                case 2:
                    $color = '#ff0000';

                    break;
                case 3:
                    $color = '#ffcc00';

                    break;
                default:
                    $color = '#222222';

            }

                $cells->setBackground($color);
                $cells->setFontColor('#000000');
                $cells->setFontWeight('bold');
                $cells->setAlignment('center');
            });

            $sheet->getStyle("A1:D1")->getAlignment()->setWrapText(true)->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
            $sheet->setHeight(1, 50);
            $sheet->cells("A1:D999", function($cells){
                $cells->setFontFamily('Arial');
                $cells->setFontSize(10);
            });

            $fila++;
            foreach ($datos as $anio => $registros) {
                $sheet->mergeCells("A{$fila}:D{$fila}");
                $sheet->setCellValue("A{$fila}", $anio);
                $sheet->cells("A{$fila}:D{$fila}", function($cells){
                    $cells->setBackground('#CCCCCC');
                    $cells->setFontWeight('bold');
                    $cells->setFontSize(13);
                    $cells->setAlignment('center');
                });
                $sheet->getStyle("A{$fila}:D{$fila}")->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
                $sheet->setHeight($fila, 20);
                $fila++;

                foreach ($registros as $r) {
                    setlocale(LC_TIME, 'es_ES.UTF-8');
                    $mesEsp = ucfirst(strftime('%B', strtotime($r->{'Mes'} . ' 1')));

                    $sheet->row($fila, [
                        $mesEsp,
                        $r->{'Fecha Consulta'},
                        ($r->{'Registra Incumplimiento'}==1) ? 'SI': 'NO',
                        $r->incumplimiento,
                    ]);
                    $sheet->cells("A{$fila}", function($cells){
                        $cells->setBackground('#FFFF99');
                        $cells->setFontWeight('bold');
                        $cells->setAlignment('left');
                    });

                    $sheet->cells("B{$fila}:D{$fila}", function($cells){
                        $cells->setAlignment('center');
                    });

                    $sheet->getStyle("A{$fila}:D{$fila}")->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
                    $fila++;
                }
            }

            $sheet->getStyle("A1:D1")->applyFromArray([
                'borders' => [
                    'allborders' => [
                        'style' => PHPExcel_Style_Border::BORDER_THICK,
                        'color' => ['argb' => 'FF000000']
                    ]
                ]
            ]);

            $sheet->getStyle("A2:D" . ($fila - 1))->applyFromArray([
                'borders' => [
                    'allborders' => [
                        'style' => PHPExcel_Style_Border::BORDER_THIN,
                        'color' => ['argb' => 'FF000000']
                    ]
                ]
            ]);

            $anchos = [
                9, 18, 17, 20, 18, 16, 11, 18,
                16, 11, 20, 18, 15, 15, 18, 20
            ];
            foreach (range('A', 'D') as $i => $col) {
                $sheet->setWidth($col, $anchos[$i]);
            }

            $sheet->freezeFirstRow();
        });
    })->export('xlsx');
}

public function descargarDeudaEstadoXlsxTodos(Request $request)
{
    $desde = $request->query('desde');
    $hasta = $request->query('hasta');

    $user = Usuario::find(session('id_usuario'));
    $casinos = $user->casinos->pluck('nombre', 'id_casino');

    return Excel::create('registro_DeudaEstado_todos', function($excel) use ($casinos, $desde, $hasta) {
        foreach ($casinos as $casinoId => $casinoNombre) {

            $datos = RegistroDeudaEstado::select([
                DB::raw("YEAR(fecha_DeudaEstado) AS anio"),
                DB::raw("MONTHNAME(fecha_DeudaEstado) AS Mes"),
                DB::raw("DATE_FORMAT(fecha_consulta,'%d/%m/%Y') AS `Fecha Consulta`"),
                DB::raw("registra_incumplimiento AS 'Registra Incumplimiento'"),
                DB::raw("incumplimiento")
            ])
            ->where('casino', $casinoId)
            ->when($desde, function($q) use ($desde) {
                return $q->where('fecha_DeudaEstado', '>=', $desde . '-01');
            })
            ->when($hasta, function($q) use ($hasta) {
                return $q->where('fecha_DeudaEstado', '<=', $hasta . '-31');
            })
            ->orderBy('fecha_DeudaEstado')
            ->get()
            ->groupBy('anio');

            $excel->sheet($casinoNombre, function($sheet) use ($datos, $casinoId) {
                $fila = 1;

                $sheet->row($fila, [
                    'Mes',
                    'Fecha de Consulta',
                    '¿Registra Incumplimiento? (SI/NO)',
                    'Incumplimiento',

                ]);

                $sheet->cells("A1:D1", function($cells) use ($casinoId) {
                    switch ($casinoId) {
                case 1:
                    $color = '#339966';

                    break;
                case 2:
                    $color = '#ff0000';

                    break;
                case 3:
                    $color = '#ffcc00';

                    break;
                default:
                    $color = '#222222';

            }
                    $cells->setBackground($color);
                    $cells->setFontColor('#000000');
                    $cells->setFontWeight('bold');
                    $cells->setAlignment('center');
                });

                $sheet->getStyle("A1:D1")->getAlignment()
                    ->setWrapText(true)
                    ->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
                $sheet->setHeight(1, 30);


                $fila++;

                $sheet->cells("A1:D999", function($cells) {
                    $cells->setFontFamily('Arial');
                    $cells->setFontSize(10);
                });

                foreach ($datos as $anio => $registros) {
                    $sheet->mergeCells("A{$fila}:D{$fila}");
                    $sheet->setCellValue("A{$fila}", $anio);
                    $sheet->cells("A{$fila}:D{$fila}", function($cells){
                        $cells->setBackground('#CCCCCC');
                        $cells->setFontWeight('bold');
                        $cells->setFontSize(13);
                        $cells->setAlignment('center');
                    });
                    $sheet->setHeight($fila, 20);
                    $fila++;

                    foreach ($registros as $r) {
                        setlocale(LC_TIME, 'es_ES.UTF-8');
                        $mesEsp = ucfirst(strftime('%B', strtotime($r->{'Mes'} . ' 1')));

                        $sheet->row($fila, [
                            $mesEsp,
                            $r->{'Fecha Consulta'},
                            $r->{'Registra Incumplimiento'}==1 ? 'SI' : 'NO',
                            $r->{'incumplimiento'}
                        ]);

                        $sheet->cells("A{$fila}", function($cells){
                            $cells->setBackground('#FFFF99');
                            $cells->setFontWeight('bold');
                            $cells->setAlignment('left');
                        });
                        $sheet->cells("B{$fila}:D{$fila}", function($cells){
                            $cells->setAlignment('center');
                        });
                        $fila++;
                    }
                }

                $sheet->getStyle("A1:D1")->applyFromArray([
                    'borders' => [
                        'allborders' => [
                            'style' => PHPExcel_Style_Border::BORDER_THICK,
                            'color' => ['argb' => 'FF000000']
                        ]
                    ]
                ]);
                $sheet->getStyle("A2:D" . ($fila - 1))->applyFromArray([
                    'borders' => [
                        'allborders' => [
                            'style' => PHPExcel_Style_Border::BORDER_THIN,
                            'color' => ['argb' => 'FF000000']
                        ]
                    ]
                ]);

                $anchos = [9, 17, 21, 25, 18, 16, 14, 18,
                    16, 14, 20, 18, 14, 15, 18, 20, 20,
                    20, 20, 20, 20, 20];
                foreach (range('A', 'D') as $i => $col) {
                    $sheet->setWidth($col, $anchos[$i]);
                }

                $sheet->setFreeze('A2');
            });
        }
    })->export('xlsx');
}

// PAGOS MAYORES MESA DE PAÑO
public function guardarPagosMayoresMesas(Request $request){

      $path = null;


      try {
          DB::beginTransaction();

          $PagosMayoresMesas = new RegistroPagosMayoresMesas();
          $PagosMayoresMesas->fecha_PagosMayoresMesas = $request->fecha_PagosMayoresMesas.'-01';

          $PagosMayoresMesas->cant_pagos = $request->cant_pagos_PagosMayoresMesas;
          $PagosMayoresMesas->importe_pesos = $request->importe_pesos_PagosMayoresMesas;
          $PagosMayoresMesas->importe_usd = $request->importe_dolares_PagosMayoresMesas;

          $PagosMayoresMesas->casino = $request->casinoPagosMayoresMesas;
          $PagosMayoresMesas->fecha_toma = date('Y-m-d h:i:s', time());
          $PagosMayoresMesas->usuario = UsuarioController::getInstancia()->quienSoy()['usuario']['id_usuario'];
          $PagosMayoresMesas->save();
          $files = Arr::wrap($request->file('uploadPagosMayoresMesas'));
                    foreach ($files as $file) {
                        if (!($file instanceof UploadedFile) || !$file->isValid()) continue;

                        $base = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
                        $ext  = $file->getClientOriginalExtension();
                        $safe = preg_replace('/\s+/', '_', $base);
                        $filename = time().'_'.Str::random(6).'_'.$safe.($ext?'.'.$ext:'');

                        $file->storeAs('public/RegistroPagosMayoresMesas', $filename);

                        $PagosMayoresMesas->archivos()->create([
                            'path'       => $filename,
                            'usuario'    => $PagosMayoresMesas->usuario,
                            'fecha_toma' => date('Y-m-d H:i:s'),
                        ]);
                    }

          DB::commit();

          return response()->json([
            'success' => true,
             'id' => $PagosMayoresMesas->id_registroPagosMayoresMesas,
             'PagosMayoresMesas'  => $request->all(),
             'path'    => $path,
             'url'     => Storage::url($path)
           ]);
      } catch (\Exception $e) {
          DB::rollBack();
          return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
      }

}

public function ultimasPagosMayoresMesas(Request $request)
{
    $page    = max(1, (int)$request->query('page', 1));
    $perPage = max(1, (int)$request->query('page_size', 20));

    $user = Usuario::find(session('id_usuario'));
    $allowedCasinoIds = $user->casinos->pluck('id_casino')->toArray();


    $query = RegistroPagosMayoresMesas::with('casinoPagosMayoresMesas')
              ->withCount('archivos')
              ->whereIn('casino', $allowedCasinoIds)
              ->orderBy('fecha_PagosMayoresMesas', 'desc')
              ->orderBy('casino', 'desc');


    if ($c = $request->query('id_casino')) {
      $query->where('casino', $c);
    }
    if ($desde = $request->query('desde')){
      $desde = $desde."-01";
      $query->where('fecha_PagosMayoresMesas',">=",$desde);
    }
    if ($hasta = $request->query('hasta')){
      $hasta = $hasta."-01";
      $query->where('fecha_PagosMayoresMesas',"<=",$hasta);
    }

    $total = $query->count();

    $registros = $query
        ->skip(($page - 1) * $perPage)
        ->take($perPage)
        ->get();

    $datos = $registros->map(function($r) {
        return [
            'id_registroPagosMayoresMesas' => $r->id_registroPagosMayoresMesas,
            'fecha_PagosMayoresMesas'   => $r->fecha_PagosMayoresMesas,
            'fecha_presentacion' => $r->fecha_consulta,
            'incumplimiento' => $r->registra_incumplimiento,
            'casino'      => $r->casinoPagosMayoresMesas ? $r->casinoPagosMayoresMesas->nombre : '-',
	  'tiene_archivos' => $r->archivos_count>0,        ];
    });

    return response()->json([
        'registros'  => $datos,
        'pagination' => [
            'current_page' => $page,
            'per_page'     => $perPage,
            'total'        => $total,
        ],
    ]);
}


public function archivosPagosMayoresMesas($id)
{
    $PagosMayoresMesas = RegistroPagosMayoresMesas::with('archivos')->findOrFail($id);

    $files = $PagosMayoresMesas->archivos->map(function($a){
        return [
            'id'     => $a->id_registro_archivo,
            'nombre' => basename($a->path),
            'url'    => \Illuminate\Support\Facades\Storage::url($a->path),
            'fecha'  => $a->fecha_toma,
        ];
    });

    return response()->json($files->values());
}

public function llenarPagosMayoresMesasEdit($id)
{
    $r = RegistroPagosMayoresMesas::findOrFail($id);

    return response()->json([
        'fecha'        => $r->fecha_PagosMayoresMesas,
        'casino'       => $r->casino,
        'cant_pagos'   => $r->cant_pagos,
        'importe_pesos'=> $r->importe_pesos,
        'importe_usd'  => $r->importe_usd,
    ]);
}

public function actualizarPagosMayoresMesas(Request $request, $id)
{
    $r = RegistroPagosMayoresMesas::findOrFail($id);

    $r->fecha_PagosMayoresMesas = $request->input('fecha_PagosMayoresMesas').'-01';
    $r->casino                  = $request->input('casinoPagosMayoresMesas');
    $r->cant_pagos              = $request->input('cant_pagos_PagosMayoresMesas');
    $r->importe_pesos           = $request->input('importe_pesos_PagosMayoresMesas');
    $r->importe_usd             = $request->input('importe_dolares_PagosMayoresMesas');
    $r->save();

    $saved = 0;
    $files = Arr::wrap($request->file('uploadPagosMayoresMesas'));
    foreach ($files as $file) {
        if (!($file instanceof UploadedFile) || !$file->isValid()) continue;

        $base = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
        $ext  = $file->getClientOriginalExtension();
        $safe = preg_replace('/\s+/', '_', $base);
        $name = time().'_'.Str::random(6).'_'.$safe.($ext ? '.'.$ext : '');

        $file->storeAs('public/RegistroPagosMayoresMesas', $name);

        $r->archivos()->create([
            'path'       => $name,
            'usuario'    => UsuarioController::getInstancia()->quienSoy()['usuario']['id_usuario'],
            'fecha_toma' => date('Y-m-d H:i:s'),
        ]);

        $saved++;
    }

    return response()->json(['success' => true, 'saved_files' => $saved]);
}


public function eliminarPagosMayoresMesas($id){
  $PagosMayoresMesas = RegistroPagosMayoresMesas::findOrFail($id);
  if(is_null($PagosMayoresMesas)) return 0;
  RegistroPagosMayoresMesas::destroy($id);
  return 1;
}

public function llenarPagosMayoresMesas($id){
  $PagosMayoresMesas = RegistroPagosMayoresMesas::with('casinoPagosMayoresMesas')->findOrFail($id);
  if(is_null($PagosMayoresMesas)) return 0;

  return response()->json([

    'fecha' => $PagosMayoresMesas->fecha_PagosMayoresMesas,
    'casino' => $PagosMayoresMesas->casinoPagosMayoresMesas ? $PagosMayoresMesas->casinoPagosMayoresMesas->nombre : '-',
    'cant_pagos' => $PagosMayoresMesas->cant_pagos,
    'importe_pesos' => $PagosMayoresMesas->importe_pesos,
    'importe_usd' => $PagosMayoresMesas->importe_usd,

  ]);

}

public function descargarPagosMayoresMesasCsv(Request $request)
{
    $casinoId = $request->query('casino');
    $desde    = $request->query('desde');
    $hasta    = $request->query('hasta');
    $user = Usuario::find(session('id_usuario'));
    $allowedCasinoIds = $user->casinos->pluck('id_casino')->toArray();

    $registros = RegistroPagosMayoresMesas::with('casinoPagosMayoresMesas')
        ->whereIn('casino',$allowedCasinoIds)
        ->when($casinoId && $casinoId != 4, function ($q) use ($casinoId) {
            $q->where('casino', $casinoId);
        })
        ->when($desde, function ($q) use ($desde) {
            $q->where('fecha_PagosMayoresMesas', '>=', $desde . '-01');
        })
        ->when($hasta, function ($q) use ($hasta) {
            $q->where('fecha_PagosMayoresMesas', '<=', $hasta . '-01');
        })
        ->orderBy('casino')
        ->orderBy('fecha_PagosMayoresMesas')
        ->get();

    $csv = [];
    $csv[] = ['Casino', 'Año', 'Mes', 'Cantidad de Pagos', 'Importe en Pesos', 'Importe en Dolares'];

    foreach ($registros as $r) {
        setlocale(LC_TIME, 'es_ES.UTF-8');
        $anio     = date('Y', strtotime($r->fecha_PagosMayoresMesas));
        $mes      = strftime('%B', strtotime($r->fecha_PagosMayoresMesas));
        $cant_pagos = $r->cant_pagos;
        $casino   = $r->casinoPagosMayoresMesas ? $r->casinoPagosMayoresMesas->nombre : '-';
        $pesos = number_format($r->importe_pesos, 2, '.', '');
        $dolares = number_format($r->importe_usd,2,'.','');

        $csv[] = [
            $casino,
            $anio,
            ucfirst($mes),
            $cant_pagos,
            $pesos,
            $dolares,
        ];
    }

    $nombreCasino = $casinoId == 4 ? 'todos' : Casino::find($casinoId)->nombre ?? 'desconocido';
    $filename = "PagosMayoresMesas_{$nombreCasino}_" . date('Ymd_His') . ".csv";

    $handle = fopen('php://temp', 'r+');
    foreach ($csv as $linea) {
        fputcsv($handle, $linea, ',');
    }
    rewind($handle);
    $contenido = stream_get_contents($handle);
    fclose($handle);

    return response($contenido, 200, [
        'Content-Type'        => 'text/csv; charset=UTF-8',
        'Content-Disposition' => "attachment; filename=\"$filename\"",
        'Cache-Control'       => 'no-store, no-cache'
    ]);
}

public function descargarPagosMayoresMesasXlsx(Request $request)
{
    $casinoId = $request->query('casino');
    $desde = $request->query('desde');
    $hasta = $request->query('hasta');

    $casino = Casino::findOrFail($casinoId);

    $query = RegistroPagosMayoresMesas::select([
        DB::raw("YEAR(fecha_PagosMayoresMesas) AS anio"),
        DB::raw("MONTHNAME(fecha_PagosMayoresMesas) AS Mes"),
        DB::raw("cant_pagos AS 'Cantidad de Pagos'"),
        DB::raw("importe_pesos AS 'Importe Pesos'"),
        DB::raw("importe_usd AS 'Importe Dolares'"),
    ])
    ->where('casino', $casinoId);

    if ($desde) {
        $query->where('fecha_PagosMayoresMesas', '>=', $desde . '-01');
    }

    if ($hasta) {
        $query->where('fecha_PagosMayoresMesas', '<=', $hasta . '-31');
    }

    $datos = $query->orderBy('fecha_PagosMayoresMesas')->get()->groupBy('anio');

    $totalesPorAnio = $datos->map(function($grupo, $anio) {
    return [
        'anio' => $anio,
        'total_pesos' => $grupo->sum('Importe Pesos'),
        'total_usd'   => $grupo->sum('Importe Dolares'),
        ];
    });

    $filename = "registro_PagosMayoresMesas_" . str_replace(' ', '_', strtolower($casino->nombre));

    return Excel::create($filename, function($excel) use ($datos, $casino, $casinoId, $totalesPorAnio) {
        $excel->sheet('PagosMayoresMesas', function($sheet) use ($datos, $casino, $casinoId, $totalesPorAnio) {
            $fila = 1;

            $sheet->row($fila, [
                'Pagos de Premios Mayores a $300.000',
            ]);
            $sheet->mergeCells('A1:D1');
            $fila++;

            $sheet->row($fila, [
                'Mes',
                'Cantidad',
                'Importe en Pesos',
                'Importe en Dolares',
            ]);
            $sheet->cells("A1:D2", function($cells) use ($casinoId) {
                switch ($casinoId) {
                case 1:
                    $color = '#339966';

                    break;
                case 2:
                    $color = '#ff0000';

                    break;
                case 3:
                    $color = '#ffcc00';

                    break;
                default:
                    $color = '#222222';

            }

                $cells->setBackground($color);
                $cells->setFontColor('#000000');
                $cells->setFontWeight('bold');
                $cells->setAlignment('center');
            });

            $sheet->getStyle("A1:D2")->getAlignment()->setWrapText(true)->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
            $sheet->setHeight(1, 12);
            $sheet->setHeight(2,20);
            $sheet->cells("A1:D999", function($cells){
                $cells->setFontFamily('Arial');
                $cells->setFontSize(10);
            });

            $fila++;
            foreach ($datos as $anio => $registros) {
                $sheet->mergeCells("A{$fila}:D{$fila}");
                $sheet->setCellValue("A{$fila}", $anio);
                $sheet->cells("A{$fila}:D{$fila}", function($cells){
                    $cells->setBackground('#CCCCCC');
                    $cells->setFontWeight('bold');
                    $cells->setFontSize(13);
                    $cells->setAlignment('center');
                });
                $sheet->getStyle("A{$fila}:D{$fila}")->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
                $sheet->setHeight($fila, 20);
                $fila++;

                foreach ($registros as $r) {
                    setlocale(LC_TIME, 'es_ES.UTF-8');
                    $mesEsp = ucfirst(strftime('%B', strtotime($r->{'Mes'} . ' 1')));

                    $sheet->row($fila, [
                        $mesEsp,
                        $r->{'Cantidad de Pagos'},
                        "$ " . number_format($r->{'Importe Pesos'}, 2, ',', '.'),
                        "USD " . number_format($r->{'Importe Dolares'}, 2, ',', '.'),
                    ]);
                    $sheet->cells("A{$fila}", function($cells){
                        $cells->setBackground('#FFFF99');
                        $cells->setFontWeight('bold');
                        $cells->setAlignment('left');
                    });

                    $sheet->cells("B{$fila}:D{$fila}", function($cells){
                        $cells->setAlignment('center');
                    });

                    $sheet->getStyle("A{$fila}:D{$fila}")->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
                    $fila++;
                }
                $totales = $totalesPorAnio[$anio];
                    $sheet->row($fila, [
                        'TOTAL ' . $anio,
                        '',
                        "$ " . number_format($totales['total_pesos'], 2, ',', '.'),
                        "USD " . number_format($totales['total_usd'], 2, ',', '.'),
                    ]);
                    $sheet->cells("A{$fila}", function($cells){
                        $cells->setBackground('#CCCCFF');
                        $cells->setFontWeight('bold');
                    });
                    $sheet->cells("C{$fila}:D{$fila}", function($cells){
                        $cells->setAlignment('center');
                        $cells->setFontWeight('bold');
                    });
                    $fila++;
            }

            $sheet->getStyle("A1:D2")->applyFromArray([
                'borders' => [
                    'allborders' => [
                        'style' => PHPExcel_Style_Border::BORDER_THICK,
                        'color' => ['argb' => 'FF000000']
                    ]
                ]
            ]);

            $sheet->getStyle("A3:D" . ($fila -1))->applyFromArray([
                'borders' => [
                    'allborders' => [
                        'style' => PHPExcel_Style_Border::BORDER_THIN,
                        'color' => ['argb' => 'FF000000']
                    ]
                ]
            ]);

            $anchos = [
                9, 18, 17, 20, 18, 16, 11, 18,
                16, 11, 20, 18, 15, 15, 18, 20
            ];
            foreach (range('A', 'D') as $i => $col) {
                $sheet->setWidth($col, $anchos[$i]);
            }

            $sheet->setFreeze('A3');
        });
    })->export('xlsx');
}

public function descargarPagosMayoresMesasXlsxTodos(Request $request)
{
    $desde = $request->query('desde');
    $hasta = $request->query('hasta');

    $user = Usuario::find(session('id_usuario'));
    $casinos = $user->casinos->pluck('nombre', 'id_casino');

    return Excel::create('registro_PagosMayoresMesas_todos', function($excel) use ($casinos, $desde, $hasta) {
        foreach ($casinos as $casinoId => $casinoNombre) {

            $datos = RegistroPagosMayoresMesas::select([
              DB::raw("YEAR(fecha_PagosMayoresMesas) AS anio"),
              DB::raw("MONTHNAME(fecha_PagosMayoresMesas) AS Mes"),
              DB::raw("cant_pagos AS 'Cantidad de Pagos'"),
              DB::raw("importe_pesos AS 'Importe Pesos'"),
              DB::raw("importe_usd AS 'Importe Dolares'"),
            ])
            ->where('casino', $casinoId)
            ->when($desde, function($q) use ($desde) {
                return $q->where('fecha_PagosMayoresMesas', '>=', $desde . '-01');
            })
            ->when($hasta, function($q) use ($hasta) {
                return $q->where('fecha_PagosMayoresMesas', '<=', $hasta . '-31');
            })
            ->orderBy('fecha_PagosMayoresMesas')
            ->get()
            ->groupBy('anio');

            $totalesPorAnio = $datos->map(function($grupo, $anio) {
            return [
                'anio' => $anio,
                'total_pesos' => $grupo->sum('Importe Pesos'),
                'total_usd'   => $grupo->sum('Importe Dolares'),
                ];
            });

            $excel->sheet($casinoNombre, function($sheet) use ($totalesPorAnio, $datos, $casinoId) {
                $fila = 1;
                $sheet->row($fila, [
                    'Pagos de Premios Mayores a $300.000',
                ]);
                $sheet->mergeCells('A1:D1');
                $fila++;

                $sheet->row($fila, [
                    'Mes',
                    'Cantidad de Pagos',
                    'Importe en Pesos',
                    'Importe en Dolares',

                ]);

                $sheet->cells("A1:D2", function($cells) use ($casinoId) {
                    switch ($casinoId) {
                case 1:
                    $color = '#339966';

                    break;
                case 2:
                    $color = '#ff0000';

                    break;
                case 3:
                    $color = '#ffcc00';

                    break;
                default:
                    $color = '#222222';

            }
                    $cells->setBackground($color);
                    $cells->setFontColor('#000000');
                    $cells->setFontWeight('bold');
                    $cells->setAlignment('center');
                });

                $sheet->getStyle("A1:D2")->getAlignment()
                    ->setWrapText(true)
                    ->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
                $sheet->setHeight(1, 12);
                $sheet->setHeight(2, 20);


                $fila++;

                $sheet->cells("A1:D999", function($cells) {
                    $cells->setFontFamily('Arial');
                    $cells->setFontSize(10);
                });

                foreach ($datos as $anio => $registros) {
                    $sheet->mergeCells("A{$fila}:D{$fila}");
                    $sheet->setCellValue("A{$fila}", $anio);
                    $sheet->cells("A{$fila}:D{$fila}", function($cells){
                        $cells->setBackground('#CCCCCC');
                        $cells->setFontWeight('bold');
                        $cells->setFontSize(13);
                        $cells->setAlignment('center');
                    });
                    $sheet->setHeight($fila, 20);
                    $fila++;

                    foreach ($registros as $r) {
                        setlocale(LC_TIME, 'es_ES.UTF-8');
                        $mesEsp = ucfirst(strftime('%B', strtotime($r->{'Mes'} . ' 1')));

                        $sheet->row($fila, [
                          $mesEsp,
                          $r->{'Cantidad de Pagos'},
                          "$ " . number_format($r->{'Importe Pesos'}, 2, ',', '.'),
                          "USD " . number_format($r->{'Importe Dolares'}, 2, ',', '.'),
                        ]);

                        $sheet->cells("A{$fila}", function($cells){
                            $cells->setBackground('#FFFF99');
                            $cells->setFontWeight('bold');
                            $cells->setAlignment('left');
                        });
                        $sheet->cells("B{$fila}:D{$fila}", function($cells){
                            $cells->setAlignment('center');
                        });
                        $fila++;
                    }
                    $totales = $totalesPorAnio[$anio];
                        $sheet->row($fila, [
                            'TOTAL ' . $anio,
                            '',
                            "$ " . number_format($totales['total_pesos'], 2, ',', '.'),
                            "USD " . number_format($totales['total_usd'], 2, ',', '.'),
                        ]);
                        $sheet->cells("A{$fila}", function($cells){
                            $cells->setBackground('#CCCCFF');
                            $cells->setFontWeight('bold');
                        });
                        $sheet->cells("C{$fila}:D{$fila}", function($cells){
                            $cells->setAlignment('center');
                            $cells->setFontWeight('bold');
                        });
                        $fila++;

                }



                $sheet->getStyle("A1:D2")->applyFromArray([
                    'borders' => [
                        'allborders' => [
                            'style' => PHPExcel_Style_Border::BORDER_THICK,
                            'color' => ['argb' => 'FF000000']
                        ]
                    ]
                ]);
                $sheet->getStyle("A3:D" . ($fila - 1))->applyFromArray([
                    'borders' => [
                        'allborders' => [
                            'style' => PHPExcel_Style_Border::BORDER_THIN,
                            'color' => ['argb' => 'FF000000']
                        ]
                    ]
                ]);

                $anchos = [9, 17, 21, 25, 18, 16, 14, 18,
                    16, 14, 20, 18, 14, 15, 18, 20, 20,
                    20, 20, 20, 20, 20];
                foreach (range('A', 'D') as $i => $col) {
                    $sheet->setWidth($col, $anchos[$i]);
                }

                $sheet->setFreeze('A3');
            });
        }
    })->export('xlsx');
}

// REPORTE DE OPERACIONES Y LAVADO DE ACTIVOS
public function guardarReporteYLavado(Request $request){

      $path = null;


      try {
          DB::beginTransaction();

          $ReporteYLavado = new RegistroReporteYLavado();
          $ReporteYLavado->fecha_ReporteYLavado = $request->fecha_ReporteYLavado.'-01';



          $ReporteYLavado->reporte_sistematico = $request->reporte_sistematico_ReporteYLavado;
          $ReporteYLavado->reporte_operaciones = $request->reporte_operaciones_ReporteYLavado;

          $ReporteYLavado->casino = $request->casinoReporteYLavado;
          $ReporteYLavado->fecha_toma = date('Y-m-d h:i:s', time());
          $ReporteYLavado->usuario = UsuarioController::getInstancia()->quienSoy()['usuario']['id_usuario'];
          $ReporteYLavado->save();
          $files = Arr::wrap($request->file('uploadReporteYLavado'));
                    foreach ($files as $file) {
                        if (!($file instanceof UploadedFile) || !$file->isValid()) continue;

                        $base = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
                        $ext  = $file->getClientOriginalExtension();
                        $safe = preg_replace('/\s+/', '_', $base);
                        $filename = time().'_'.Str::random(6).'_'.$safe.($ext?'.'.$ext:'');

                        $file->storeAs('public/RegistroReporteYLavado', $filename);

                        $ReporteYLavado->archivos()->create([
                            'path'       => $filename,
                            'usuario'    => $ReporteYLavado->usuario,
                            'fecha_toma' => date('Y-m-d H:i:s'),
                        ]);
                    }
          DB::commit();

          return response()->json([
            'success' => true,
             'id' => $ReporteYLavado->id_registroReporteYLavado,
             'ReporteYLavado'  => $request->all(),
             'path'    => $path,
             'url'     => Storage::url($path)
           ]);
      } catch (\Exception $e) {
          DB::rollBack();
          return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
      }

}

public function ultimasReporteYLavado(Request $request)
{
    $page    = max(1, (int)$request->query('page', 1));
    $perPage = max(1, (int)$request->query('page_size', 20));

    $user = Usuario::find(session('id_usuario'));
    $allowedCasinoIds = $user->casinos->pluck('id_casino')->toArray();


    $query = RegistroReporteYLavado::with('casinoReporteYLavado')
    ->withCount('archivos')
              ->whereIn('casino', $allowedCasinoIds)
              ->orderBy('fecha_ReporteYLavado', 'desc')
              ->orderBy('casino', 'desc');


    if ($c = $request->query('id_casino')) {
      $query->where('casino', $c);
    }
    if ($desde = $request->query('desde')){
      $desde = $desde."-01";
      $query->where('fecha_ReporteYLavado',">=",$desde);
    }
    if ($hasta = $request->query('hasta')){
      $hasta = $hasta."-01";
      $query->where('fecha_ReporteYLavado',"<=",$hasta);
    }

    $total = $query->count();

    $registros = $query
        ->skip(($page - 1) * $perPage)
        ->take($perPage)
        ->get();

    $datos = $registros->map(function($r) {
        return [
            'id_registroReporteYLavado' => $r->id_registroReporteYLavado,
            'fecha_ReporteYLavado'   => $r->fecha_ReporteYLavado,
            'reporte_sistematico' => $r->reporte_sistematico,
            'reporte_operaciones' => $r->reporte_operaciones,
            'casino'      => $r->casinoReporteYLavado ? $r->casinoReporteYLavado->nombre : '-',
	  'tiene_archivos' => $r->archivos_count>0,        ];
    });

    return response()->json([
        'registros'  => $datos,
        'pagination' => [
            'current_page' => $page,
            'per_page'     => $perPage,
            'total'        => $total,
        ],
    ]);
}


public function eliminarReporteYLavado($id){
  $ReporteYLavado = RegistroReporteYLavado::findOrFail($id);
  if(is_null($ReporteYLavado)) return 0;
  RegistroReporteYLavado::destroy($id);
  return 1;
}
public function archivosReporteYLavado($id)
{
    $ReporteYLavado = RegistroReporteYLavado::with('archivos')->findOrFail($id);

    $files = $ReporteYLavado->archivos->map(function($a){
        return [
            'id'     => $a->id_registro_archivo,
            'nombre' => basename($a->path),
            'url'    => \Illuminate\Support\Facades\Storage::url($a->path),
            'fecha'  => $a->fecha_toma,
        ];
    });

    return response()->json($files->values());
}


public function llenarReporteYLavadoEdit($id)
{
    $r = RegistroReporteYLavado::findOrFail($id);

    return response()->json([
        'fecha'                => $r->fecha_ReporteYLavado,
        'casino'               => $r->casino,
        'reporte_sistematico'  => $r->reporte_sistematico,
        'reporte_operaciones'  => $r->reporte_operaciones,
    ]);
}

public function actualizarReporteYLavado(Request $request, $id)
{
    $r = RegistroReporteYLavado::findOrFail($id);

    $r->fecha_ReporteYLavado = $request->input('fecha_ReporteYLavado').'-01';
    $r->reporte_sistematico  = $request->input('reporte_sistematico_ReporteYLavado');
    $r->reporte_operaciones  = $request->input('reporte_operaciones_ReporteYLavado');
    $r->casino               = $request->input('casinoReporteYLavado');
    $r->save();

    $saved = 0;
    $files = Arr::wrap($request->file('uploadReporteYLavado'));
    foreach ($files as $file) {
        if (!($file instanceof UploadedFile) || !$file->isValid()) continue;

        $base = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
        $ext  = $file->getClientOriginalExtension();
        $safe = preg_replace('/\s+/', '_', $base);
        $name = time().'_'.Str::random(6).'_'.$safe.($ext ? '.'.$ext : '');

        $file->storeAs('public/RegistroReporteYLavado', $name);

        $r->archivos()->create([
            'path'       => $name,
            'usuario'    => UsuarioController::getInstancia()->quienSoy()['usuario']['id_usuario'],
            'fecha_toma' => date('Y-m-d H:i:s'),
        ]);

        $saved++;
    }

    return response()->json(['success' => true, 'saved_files' => $saved]);
}


public function llenarReporteYLavado($id){
  $ReporteYLavado = RegistroReporteYLavado::with('casinoReporteYLavado')->findOrFail($id);
  if(is_null($ReporteYLavado)) return 0;

  return response()->json([

    'fecha' => $ReporteYLavado->fecha_ReporteYLavado,
    'casino' => $ReporteYLavado->casinoReporteYLavado ? $ReporteYLavado->casinoReporteYLavado->nombre : '-',
    'cant_pagos' => $ReporteYLavado->cant_pagos,
    'importe_pesos' => $ReporteYLavado->importe_pesos,
    'importe_usd' => $ReporteYLavado->importe_usd,

  ]);

}

public function descargarReporteYLavadoCsv(Request $request)
{
    $casinoId = $request->query('casino');
    $desde    = $request->query('desde');
    $hasta    = $request->query('hasta');
    $user = Usuario::find(session('id_usuario'));
    $allowedCasinoIds = $user->casinos->pluck('id_casino')->toArray();

    $registros = RegistroReporteYLavado::with('casinoReporteYLavado')
        ->whereIn('casino',$allowedCasinoIds)
        ->when($casinoId && $casinoId != 4, function ($q) use ($casinoId) {
            $q->where('casino', $casinoId);
        })
        ->when($desde, function ($q) use ($desde) {
            $q->where('fecha_ReporteYLavado', '>=', $desde . '-01');
        })
        ->when($hasta, function ($q) use ($hasta) {
            $q->where('fecha_ReporteYLavado', '<=', $hasta . '-01');
        })
        ->orderBy('casino')
        ->orderBy('fecha_ReporteYLavado')
        ->get();

    $csv = [];
    $csv[] = ['Casino', 'Año', 'Mes', 'Reporte Sistematico', 'Reporte de Operaciones'];

    foreach ($registros as $r) {
        setlocale(LC_TIME, 'es_ES.UTF-8');
        $anio     = date('Y', strtotime($r->fecha_ReporteYLavado));
        $mes      = strftime('%B', strtotime($r->fecha_ReporteYLavado));
        $casino   = $r->casinoReporteYLavado ? $r->casinoReporteYLavado->nombre : '-';
        $sistem = number_format($r->reporte_sistematico, 2, '.', '');
        $oper = number_format($r->reporte_operaciones,2,'.','');

        $csv[] = [
            $casino,
            $anio,
            ucfirst($mes),
            $sistem,
            $oper,
        ];
    }

    $nombreCasino = $casinoId == 4 ? 'todos' : Casino::find($casinoId)->nombre ?? 'desconocido';
    $filename = "ReporteYLavado_{$nombreCasino}_" . date('Ymd_His') . ".csv";

    $handle = fopen('php://temp', 'r+');
    foreach ($csv as $linea) {
        fputcsv($handle, $linea, ',');
    }
    rewind($handle);
    $contenido = stream_get_contents($handle);
    fclose($handle);

    return response($contenido, 200, [
        'Content-Type'        => 'text/csv; charset=UTF-8',
        'Content-Disposition' => "attachment; filename=\"$filename\"",
        'Cache-Control'       => 'no-store, no-cache'
    ]);
}

public function descargarReporteYLavadoXlsx(Request $request)
{
    $casinoId = $request->query('casino');
    $desde = $request->query('desde');
    $hasta = $request->query('hasta');

    $casino = Casino::findOrFail($casinoId);

    $query = RegistroReporteYLavado::select([
        DB::raw("YEAR(fecha_ReporteYLavado) AS anio"),
        DB::raw("MONTHNAME(fecha_ReporteYLavado) AS Mes"),
        DB::raw("reporte_sistematico AS 'Reporte Sistematico'"),
        DB::raw("reporte_operaciones AS 'Reporte Operaciones'"),
    ])
    ->where('casino', $casinoId);

    if ($desde) {
        $query->where('fecha_ReporteYLavado', '>=', $desde . '-01');
    }

    if ($hasta) {
        $query->where('fecha_ReporteYLavado', '<=', $hasta . '-31');
    }

    $datos = $query->orderBy('fecha_ReporteYLavado')->get()->groupBy('anio');


    $filename = "registro_ReporteYLavado_" . str_replace(' ', '_', strtolower($casino->nombre));

    return Excel::create($filename, function($excel) use ($datos, $casino, $casinoId) {
        $excel->sheet('ReporteYLavado', function($sheet) use ($datos, $casino, $casinoId) {
            $fila = 1;

            $sheet->row($fila, [
                'Mes',
                'Reporte Sistematico de Operaciones',
                'Reporte de Operaciones Sospechosas y Financiamiento del Terrorismo',
            ]);
            $sheet->cells("A1:C2", function($cells) use ($casinoId) {
                switch ($casinoId) {
                case 1:
                    $color = '#339966';

                    break;
                case 2:
                    $color = '#ff0000';

                    break;
                case 3:
                    $color = '#ffcc00';

                    break;
                default:
                    $color = '#222222';

            }

                $cells->setBackground($color);
                $cells->setFontColor('#000000');
                $cells->setFontWeight('bold');
                $cells->setAlignment('center');
            });

            $sheet->getStyle("A1:C2")->getAlignment()->setWrapText(true)->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
            $sheet->setHeight(1, 50);
            $sheet->cells("A1:C999", function($cells){
                $cells->setFontFamily('Arial');
                $cells->setFontSize(10);
            });

            $fila++;
            foreach ($datos as $anio => $registros) {
                $sheet->mergeCells("A{$fila}:C{$fila}");
                $sheet->setCellValue("A{$fila}", $anio);
                $sheet->cells("A{$fila}:C{$fila}", function($cells){
                    $cells->setBackground('#CCCCCC');
                    $cells->setFontWeight('bold');
                    $cells->setFontSize(13);
                    $cells->setAlignment('center');
                });
                $sheet->getStyle("A{$fila}:C{$fila}")->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
                $sheet->setHeight($fila, 20);
                $fila++;

                foreach ($registros as $r) {
                    setlocale(LC_TIME, 'es_ES.UTF-8');
                    $mesEsp = ucfirst(strftime('%B', strtotime($r->{'Mes'} . ' 1')));

                    $sheet->row($fila, [
                        $mesEsp,
                        $r->{'Reporte Sistematico'},
                        $r->{'Reporte Operaciones'},

                    ]);
                    $sheet->cells("A{$fila}", function($cells){
                        $cells->setBackground('#FFFF99');
                        $cells->setFontWeight('bold');
                        $cells->setAlignment('left');
                    });

                    $sheet->cells("B{$fila}:C{$fila}", function($cells){
                        $cells->setAlignment('center');
                    });

                    $sheet->getStyle("A{$fila}:C{$fila}")->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
                    $fila++;
                }

            }

            $sheet->getStyle("A1:C1")->applyFromArray([
                'borders' => [
                    'allborders' => [
                        'style' => PHPExcel_Style_Border::BORDER_THICK,
                        'color' => ['argb' => 'FF000000']
                    ]
                ]
            ]);

            $sheet->getStyle("A2:C" . ($fila -1))->applyFromArray([
                'borders' => [
                    'allborders' => [
                        'style' => PHPExcel_Style_Border::BORDER_THIN,
                        'color' => ['argb' => 'FF000000']
                    ]
                ]
            ]);

            $anchos = [
                9, 22, 22, 20, 18, 16, 11, 18,
                16, 11, 20, 18, 15, 15, 18, 20
            ];
            foreach (range('A', 'C') as $i => $col) {
                $sheet->setWidth($col, $anchos[$i]);
            }

            $sheet->setFreeze('A2');
        });
    })->export('xlsx');
}

public function descargarReporteYLavadoXlsxTodos(Request $request)
{
    $desde = $request->query('desde');
    $hasta = $request->query('hasta');

    $user = Usuario::find(session('id_usuario'));
    $casinos = $user->casinos->pluck('nombre', 'id_casino');

    return Excel::create('registro_ReporteYLavado_todos', function($excel) use ($casinos, $desde, $hasta) {
        foreach ($casinos as $casinoId => $casinoNombre) {

            $datos = RegistroReporteYLavado::select([
              DB::raw("YEAR(fecha_ReporteYLavado) AS anio"),
              DB::raw("MONTHNAME(fecha_ReporteYLavado) AS Mes"),
              DB::raw("reporte_sistematico AS 'Reporte Sistematico'"),
              DB::raw("reporte_operaciones AS 'Reporte Operaciones'"),
            ])
            ->where('casino', $casinoId)
            ->when($desde, function($q) use ($desde) {
                return $q->where('fecha_ReporteYLavado', '>=', $desde . '-01');
            })
            ->when($hasta, function($q) use ($hasta) {
                return $q->where('fecha_ReporteYLavado', '<=', $hasta . '-31');
            })
            ->orderBy('fecha_ReporteYLavado')
            ->get()
            ->groupBy('anio');


            $excel->sheet($casinoNombre, function($sheet) use ( $datos, $casinoId) {
                $fila = 1;

                $sheet->row($fila, [
                    'Mes',
                    'Reporte Sistematico de Operaciones',
                    'Reporte de Operaciones Sospechosas y Financiamiento del Terrorismo',

                ]);

                $sheet->cells("A1:C1", function($cells) use ($casinoId) {
                    switch ($casinoId) {
                case 1:
                    $color = '#339966';

                    break;
                case 2:
                    $color = '#ff0000';

                    break;
                case 3:
                    $color = '#ffcc00';

                    break;
                default:
                    $color = '#222222';

            }
                    $cells->setBackground($color);
                    $cells->setFontColor('#000000');
                    $cells->setFontWeight('bold');
                    $cells->setAlignment('center');
                });

                $sheet->getStyle("A1:C1")->getAlignment()
                    ->setWrapText(true)
                    ->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
                $sheet->setHeight(1, 50);


                $fila++;

                $sheet->cells("A1:C999", function($cells) {
                    $cells->setFontFamily('Arial');
                    $cells->setFontSize(10);
                });

                foreach ($datos as $anio => $registros) {
                    $sheet->mergeCells("A{$fila}:C{$fila}");
                    $sheet->setCellValue("A{$fila}", $anio);
                    $sheet->cells("A{$fila}:C{$fila}", function($cells){
                        $cells->setBackground('#CCCCCC');
                        $cells->setFontWeight('bold');
                        $cells->setFontSize(13);
                        $cells->setAlignment('center');
                    });
                    $sheet->setHeight($fila, 20);
                    $fila++;

                    foreach ($registros as $r) {
                        setlocale(LC_TIME, 'es_ES.UTF-8');
                        $mesEsp = ucfirst(strftime('%B', strtotime($r->{'Mes'} . ' 1')));

                        $sheet->row($fila, [
                          $mesEsp,
                          $r->{'Reporte Sistematico'},
                          $r->{'Reporte Operaciones'},
                        ]);

                        $sheet->cells("A{$fila}", function($cells){
                            $cells->setBackground('#FFFF99');
                            $cells->setFontWeight('bold');
                            $cells->setAlignment('left');
                        });
                        $sheet->cells("B{$fila}:C{$fila}", function($cells){
                            $cells->setAlignment('center');
                        });
                        $fila++;
                    }

                }



                $sheet->getStyle("A1:C1")->applyFromArray([
                    'borders' => [
                        'allborders' => [
                            'style' => PHPExcel_Style_Border::BORDER_THICK,
                            'color' => ['argb' => 'FF000000']
                        ]
                    ]
                ]);
                $sheet->getStyle("A2:C" . ($fila - 1))->applyFromArray([
                    'borders' => [
                        'allborders' => [
                            'style' => PHPExcel_Style_Border::BORDER_THIN,
                            'color' => ['argb' => 'FF000000']
                        ]
                    ]
                ]);

                $anchos = [9, 25, 25, 25, 18, 16, 14, 18,
                    16, 14, 20, 18, 14, 15, 18, 20, 20,
                    20, 20, 20, 20, 20];
                foreach (range('A', 'C') as $i => $col) {
                    $sheet->setWidth($col, $anchos[$i]);
                }

                $sheet->setFreeze('A2');
            });
        }
    })->export('xlsx');
}


//REGISTROS CONTABLES

public function guardarRegistrosContables(Request $request){

      $path = null;

      try {
          DB::beginTransaction();

          $RegistrosContables = new RegistroRegistrosContables();
          $RegistrosContables->fecha_RegistrosContables = $request->fecha_RegistrosContables.'-01';


          $RegistrosContables->mtm = $request->mtm_pesos_RegistrosContables;
          $RegistrosContables->mtm_usd = $request->mtm_usd_RegistrosContables;

          $RegistrosContables->mp = $request->mp_pesos_RegistrosContables;
          $RegistrosContables->mp_usd = $request->mp_usd_RegistrosContables;

          $RegistrosContables->bingo = $request->bingo_RegistrosContables;
          $RegistrosContables->jol = $request->jol_RegistrosContables;

          $RegistrosContables->total = $request->total_RegistrosContables;
          $RegistrosContables->total_usd = $request->total_usd_RegistrosContables;

          $RegistrosContables->casino = $request->casinoRegistrosContables;
          $RegistrosContables->fecha_toma = date('Y-m-d h:i:s', time());
          $RegistrosContables->usuario = UsuarioController::getInstancia()->quienSoy()['usuario']['id_usuario'];
          $RegistrosContables->save();

          $files = Arr::wrap($request->file('uploadRegistrosContables'));
                    foreach ($files as $file) {
                        if (!($file instanceof UploadedFile) || !$file->isValid()) continue;

                        $base = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
                        $ext  = $file->getClientOriginalExtension();
                        $safe = preg_replace('/\s+/', '_', $base);
                        $filename = time().'_'.Str::random(6).'_'.$safe.($ext?'.'.$ext:'');

                        $file->storeAs('public/RegistroRegistrosContables', $filename);

                        $RegistrosContables->archivos()->create([
                            'path'       => $filename,
                            'usuario'    => $RegistrosContables->usuario,
                            'fecha_toma' => date('Y-m-d H:i:s'),
                        ]);
                    }


          DB::commit();

          return response()->json([
            'success' => true,
             'id' => $RegistrosContables->id_registroRegistrosContables,
             'RegistrosContables'  => $request->all(),
             'path'    => $path,
             'url'     => Storage::url($path)
           ]);
      } catch (\Exception $e) {
          DB::rollBack();
          return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
      }

}

public function ultimasRegistrosContables(Request $request)
{
    $page    = max(1, (int)$request->query('page', 1));
    $perPage = max(1, (int)$request->query('page_size', 20));

    $user = Usuario::find(session('id_usuario'));
    $allowedCasinoIds = $user->casinos->pluck('id_casino')->toArray();


    $query = RegistroRegistrosContables::with('casinoRegistrosContables')
              ->withCount('archivos')
              ->whereIn('casino', $allowedCasinoIds)
              ->orderBy('fecha_RegistrosContables', 'desc')
              ->orderBy('casino', 'desc');


    if ($c = $request->query('id_casino')) {
      $query->where('casino', $c);
    }
    if ($desde = $request->query('desde')){
      $desde = $desde."-01";
      $query->where('fecha_RegistrosContables',">=",$desde);
    }
    if ($hasta = $request->query('hasta')){
      $hasta = $hasta."-01";
      $query->where('fecha_RegistrosContables',"<=",$hasta);
    }

    $total = $query->count();

    $registros = $query
        ->skip(($page - 1) * $perPage)
        ->take($perPage)
        ->get();

    $datos = $registros->map(function($r) {
        return [
            'id_registroRegistrosContables' => $r->id_registroRegistrosContables,
            'fecha_RegistrosContables'   => $r->fecha_RegistrosContables,
            'total' => $r->total,
            'total_usd' => $r->total_usd,
            'casino'      => $r->casinoRegistrosContables ? $r->casinoRegistrosContables->nombre : '-',
	  'tiene_archivos' => $r->archivos_count>0,        ];
    });

    return response()->json([
        'registros'  => $datos,
        'pagination' => [
            'current_page' => $page,
            'per_page'     => $perPage,
            'total'        => $total,
        ],
    ]);
}


public function eliminarRegistrosContables($id){
  $RegistrosContables = RegistroRegistrosContables::findOrFail($id);
  if(is_null($RegistrosContables)) return 0;
  RegistroRegistrosContables::destroy($id);
  return 1;
}


public function archivosRegistrosContables($id)
{
    $RegistrosContables = RegistroRegistrosContables::with('archivos')->findOrFail($id);

    $files = $RegistrosContables->archivos->map(function($a){
        return [
            'id'     => $a->id_registro_archivo,
            'nombre' => basename($a->path),
            'url'    => \Illuminate\Support\Facades\Storage::url($a->path),
            'fecha'  => $a->fecha_toma,
        ];
    });

    return response()->json($files->values());
}

public function llenarRegistrosContablesEdit($id)
{
    $r = RegistroRegistrosContables::findOrFail($id);

    return response()->json([
        'fecha'        => $r->fecha_RegistrosContables,
        'casino'       => $r->casino,
        'mtm'          => $r->mtm,
        'mtm_usd'      => $r->mtm_usd,
        'mp'           => $r->mp,
        'mp_usd'       => $r->mp_usd,
        'bingo'        => $r->bingo,
        'jol'          => $r->jol,
        'total'        => $r->total,
        'total_usd'    => $r->total_usd,
    ]);
}
public function actualizarRegistrosContables(Request $request, $id)
{
    $r = RegistroRegistrosContables::findOrFail($id);

    $r->fecha_RegistrosContables = $request->input('fecha_RegistrosContables').'-01';
    $r->casino                   = $request->input('casinoRegistrosContables');
    $r->mtm                      = $request->input('mtm_pesos_RegistrosContables');
    $r->mtm_usd                  = $request->input('mtm_usd_RegistrosContables');
    $r->mp                       = $request->input('mp_pesos_RegistrosContables');
    $r->mp_usd                   = $request->input('mp_usd_RegistrosContables');
    $r->bingo                    = $request->input('bingo_RegistrosContables');
    $r->jol                      = $request->input('jol_RegistrosContables');
    $r->total                    = $request->total_RegistrosContables;
    $r->total_usd                = $request->total_usd_RegistrosContables;
    $r->save();

    $saved = 0;
    $files = Arr::wrap($request->file('uploadRegistrosContables'));
    foreach ($files as $file) {
        if (!($file instanceof UploadedFile) || !$file->isValid()) continue;

        $base = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
        $ext  = $file->getClientOriginalExtension();
        $safe = preg_replace('/\s+/', '_', $base);
        $name = time().'_'.Str::random(6).'_'.$safe.($ext ? '.'.$ext : '');

        $file->storeAs('public/RegistroRegistrosContables', $name);

        $r->archivos()->create([
            'path'       => $name,
            'usuario'    => UsuarioController::getInstancia()->quienSoy()['usuario']['id_usuario'],
            'fecha_toma' => date('Y-m-d H:i:s'),
        ]);

        $saved++;
    }

    return response()->json(['success' => true, 'saved_files' => $saved]);
}



public function llenarRegistrosContables($id){
  $RegistrosContables = RegistroRegistrosContables::with('casinoRegistrosContables')->findOrFail($id);
  if(is_null($RegistrosContables)) return 0;

  return response()->json([

    'fecha' => $RegistrosContables->fecha_RegistrosContables,
    'casino' => $RegistrosContables->casinoRegistrosContables ? $RegistrosContables->casinoRegistrosContables->nombre : '-',
    'mtm' => $RegistrosContables->mtm,
    'mtm_usd' => $RegistrosContables->mtm_usd,
    'mp' => $RegistrosContables->mp,
    'mp_usd' => $RegistrosContables->mp_usd,
    'bingo' => $RegistrosContables->bingo,
    'jol' => $RegistrosContables->jol,
    'total' => $RegistrosContables->total,
    'total_usd' => $RegistrosContables->total_usd,

  ]);

}

public function descargarRegistrosContablesCsv(Request $request)
{
    $casinoId = $request->query('casino');
    $desde    = $request->query('desde');
    $hasta    = $request->query('hasta');
    $user = Usuario::find(session('id_usuario'));
    $allowedCasinoIds = $user->casinos->pluck('id_casino')->toArray();

    $registros = RegistroRegistrosContables::with('casinoRegistrosContables')
        ->whereIn('casino',$allowedCasinoIds)
        ->when($casinoId && $casinoId != 4, function ($q) use ($casinoId) {
            $q->where('casino', $casinoId);
        })
        ->when($desde, function ($q) use ($desde) {
            $q->where('fecha_RegistrosContables', '>=', $desde . '-01');
        })
        ->when($hasta, function ($q) use ($hasta) {
            $q->where('fecha_RegistrosContables', '<=', $hasta . '-01');
        })
        ->orderBy('casino')
        ->orderBy('fecha_RegistrosContables')
        ->get();

    $csv = [];
    $csv[] = ['Casino', 'Año', 'Mes', 'MP', 'MP USD', 'MTM', 'MTM USD', 'Bingo', 'Juego Online', 'Total Pesos', 'Total Dólares'];

    foreach ($registros as $r) {
        setlocale(LC_TIME, 'es_ES.UTF-8');
        $anio     = date('Y', strtotime($r->fecha_RegistrosContables));
        $mes      = strftime('%B', strtotime($r->fecha_RegistrosContables));
        $casino   = $r->casinoRegistrosContables ? $r->casinoRegistrosContables->nombre : '-';
        $mp = number_format($r->mp, 2, '.', '');
        $mp_usd = number_format($r->mp_usd,2,'.','');
        $mtm = number_format($r->mtm, 2, '.', '');
        $mtm_usd = number_format($r->mtm_usd,2,'.','');
        $bingo = number_format($r->bingo, 2, '.', '');
        $jol = number_format($r->jol,2,'.','');
        $total = number_format($r->total, 2, '.', '');
        $total_usd = number_format($r->total_usd,2,'.','');

        $csv[] = [
            $casino,
            $anio,
            ucfirst($mes),
            $mp,
            $mp_usd,
            $mtm,
            $mtm_usd,
            $bingo,
            $jol,
            $total,
            $total_usd,

        ];
    }

    $nombreCasino = $casinoId == 4 ? 'todos' : Casino::find($casinoId)->nombre ?? 'desconocido';
    $filename = "RegistrosContables_{$nombreCasino}_" . date('Ymd_His') . ".csv";

    $handle = fopen('php://temp', 'r+');
    foreach ($csv as $linea) {
        fputcsv($handle, $linea, ',');
    }
    rewind($handle);
    $contenido = stream_get_contents($handle);
    fclose($handle);

    return response($contenido, 200, [
        'Content-Type'        => 'text/csv; charset=UTF-8',
        'Content-Disposition' => "attachment; filename=\"$filename\"",
        'Cache-Control'       => 'no-store, no-cache'
    ]);
}

public function descargarRegistrosContablesXlsx(Request $request)
{
    $casinoId = $request->query('casino');
    $desde = $request->query('desde');
    $hasta = $request->query('hasta');

    $casino = Casino::findOrFail($casinoId);

    $query = RegistroRegistrosContables::select([
        DB::raw("YEAR(fecha_RegistrosContables) AS anio"),
        DB::raw("MONTHNAME(fecha_RegistrosContables) AS Mes"),
        DB::raw("mtm"),
        DB::raw("mtm_usd"),
        DB::raw("mp"),
        DB::raw("mp_usd"),
        DB::raw("bingo"),
        DB::raw("jol"),
        DB::raw("total"),
        DB::raw("total_usd"),

    ])
    ->where('casino', $casinoId);

    if ($desde) {
        $query->where('fecha_RegistrosContables', '>=', $desde . '-01');
    }

    if ($hasta) {
        $query->where('fecha_RegistrosContables', '<=', $hasta . '-31');
    }

    $datos = $query->orderBy('fecha_RegistrosContables')->get()->groupBy('anio');

    $totalesPorAnio = $datos->map(function($grupo, $anio) {
    return [
        'anio' => $anio,
        'total_mtm' => $grupo->sum('mtm'),
        'total_mtm_usd'   => $grupo->sum('mtm_usd'),
        'total_mp' => $grupo->sum('mp'),
        'total_mp_usd'   => $grupo->sum('mp_usd'),
        'total_bingo' => $grupo->sum('bingo'),
        'total_jol'   => $grupo->sum('jol'),
        'total_total' => $grupo->sum('total'),
        'total_total_usd'   => $grupo->sum('total_usd'),
        ];
    });

    $filename = "registro_RegistrosContables_" . str_replace(' ', '_', strtolower($casino->nombre));

    return Excel::create($filename, function($excel) use ($datos, $casino, $casinoId, $totalesPorAnio) {
        $excel->sheet('RegistrosContables', function($sheet) use ($datos, $casino, $casinoId, $totalesPorAnio) {
            $fila = 1;

            $sheet->row($fila, [
                'Mes',
                'MTM en Pesos', 'MTM en Dólares',
                'MP en Pesos', 'MP en Dólares',
                'Bingo', 'Juego Online',
                'Total en Pesos', 'Total en Dólares',
            ]);
            $sheet->cells("A1:I1", function($cells) use ($casinoId) {
                switch ($casinoId) {
                case 1:
                    $color = '#339966';

                    break;
                case 2:
                    $color = '#ff0000';

                    break;
                case 3:
                    $color = '#ffcc00';

                    break;
                default:
                    $color = '#222222';

            }

                $cells->setBackground($color);
                $cells->setFontColor('#000000');
                $cells->setFontWeight('bold');
                $cells->setAlignment('center');
            });

            $sheet->getStyle("A1:I1")->getAlignment()->setWrapText(true)->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
            $sheet->setHeight(1, 50);
            $sheet->cells("A1:I999", function($cells){
                $cells->setFontFamily('Arial');
                $cells->setFontSize(10);
            });

            $fila++;
            foreach ($datos as $anio => $registros) {
                $sheet->mergeCells("A{$fila}:I{$fila}");
                $sheet->setCellValue("A{$fila}", $anio);
                $sheet->cells("A{$fila}:I{$fila}", function($cells){
                    $cells->setBackground('#CCCCCC');
                    $cells->setFontWeight('bold');
                    $cells->setFontSize(13);
                    $cells->setAlignment('center');
                });
                $sheet->getStyle("A{$fila}:I{$fila}")->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
                $sheet->setHeight($fila, 20);
                $fila++;

                foreach ($registros as $r) {
                    setlocale(LC_TIME, 'es_ES.UTF-8');
                    $mesEsp = ucfirst(strftime('%B', strtotime($r->{'Mes'} . ' 1')));

                    $sheet->row($fila, [
                        $mesEsp,
                        "$ " . number_format($r->{'mtm'}, 2, ',', '.'),
                        "USD " . number_format($r->{'mtm_usd'}, 2, ',', '.'),
                        "$ " . number_format($r->{'mp'}, 2, ',', '.'),
                        "USD " . number_format($r->{'mp_usd'}, 2, ',', '.'),
                        "$ " . number_format($r->{'bingo'}, 2, ',', '.'),
                        "$ " . number_format($r->{'jol'}, 2, ',', '.'),
                        "$ " . number_format($r->{'total'}, 2, ',', '.'),
                        "USD " . number_format($r->{'total_usd'}, 2, ',', '.'),

                    ]);
                    $sheet->cells("A{$fila}", function($cells){
                        $cells->setBackground('#FFFF99');
                        $cells->setFontWeight('bold');
                        $cells->setAlignment('left');
                    });

                    $sheet->cells("B{$fila}:I{$fila}", function($cells){
                        $cells->setAlignment('center');
                    });

                    $sheet->getStyle("A{$fila}:I{$fila}")->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
                    $fila++;
                }

                $totales = $totalesPorAnio[$anio];
                    $sheet->row($fila, [
                        'TOTAL ' . $anio,
                        "$ " . number_format($totales['total_mtm'], 2, ',', '.'),
                        "USD " . number_format($totales['total_mtm_usd'], 2, ',', '.'),
                        "$ " . number_format($totales['total_mp'], 2, ',', '.'),
                        "USD " . number_format($totales['total_mp_usd'], 2, ',', '.'),
                        "$ " . number_format($totales['total_bingo'], 2, ',', '.'),
                        "$ " . number_format($totales['total_jol'], 2, ',', '.'),
                        "$ " . number_format($totales['total_total'], 2, ',', '.'),
                        "USD " . number_format($totales['total_total_usd'], 2, ',', '.'),
                    ]);
                    $sheet->cells("A{$fila}", function($cells){
                        $cells->setBackground('#CCCCFF');
                        $cells->setFontWeight('bold');
                    });
                    $sheet->cells("B{$fila}:I{$fila}", function($cells){
                        $cells->setAlignment('center');
                        $cells->setFontWeight('bold');
                    });
                    $fila++;
            }

            $sheet->getStyle("A1:I1")->applyFromArray([
                'borders' => [
                    'allborders' => [
                        'style' => PHPExcel_Style_Border::BORDER_THICK,
                        'color' => ['argb' => 'FF000000']
                    ]
                ]
            ]);

            $sheet->getStyle("A2:I" . ($fila -1))->applyFromArray([
                'borders' => [
                    'allborders' => [
                        'style' => PHPExcel_Style_Border::BORDER_THIN,
                        'color' => ['argb' => 'FF000000']
                    ]
                ]
            ]);

            $anchos = [
                9, 22, 22, 22, 22, 22, 22, 22,
                22, 22, 22, 18, 15, 15, 18, 20
            ];
            foreach (range('A', 'I') as $i => $col) {
                $sheet->setWidth($col, $anchos[$i]);
            }

            $sheet->setFreeze('A2');
        });
    })->export('xlsx');
}

public function descargarRegistrosContablesXlsxTodos(Request $request)
{
    $desde = $request->query('desde');
    $hasta = $request->query('hasta');

    $user = Usuario::find(session('id_usuario'));
    $casinos = $user->casinos->pluck('nombre', 'id_casino');

    return Excel::create('registro_RegistrosContables_todos', function($excel) use ($casinos, $desde, $hasta) {
        foreach ($casinos as $casinoId => $casinoNombre) {

            $datos = RegistroRegistrosContables::select([
              DB::raw("YEAR(fecha_RegistrosContables) AS anio"),
              DB::raw("MONTHNAME(fecha_RegistrosContables) AS Mes"),
              DB::raw("mtm"),
              DB::raw("mtm_usd"),
              DB::raw("mp"),
              DB::raw("mp_usd"),
              DB::raw("bingo"),
              DB::raw("jol"),
              DB::raw("total"),
              DB::raw("total_usd"),
            ])
            ->where('casino', $casinoId)
            ->when($desde, function($q) use ($desde) {
                return $q->where('fecha_RegistrosContables', '>=', $desde . '-01');
            })
            ->when($hasta, function($q) use ($hasta) {
                return $q->where('fecha_RegistrosContables', '<=', $hasta . '-31');
            })
            ->orderBy('fecha_RegistrosContables')
            ->get()
            ->groupBy('anio');

          $totalesPorAnio = $datos->map(function($grupo, $anio) {
            return [
                'anio' => $anio,
                'total_mtm' => $grupo->sum('mtm'),
                'total_mtm_usd'   => $grupo->sum('mtm_usd'),
                'total_mp' => $grupo->sum('mp'),
                'total_mp_usd'   => $grupo->sum('mp_usd'),
                'total_bingo' => $grupo->sum('bingo'),
                'total_jol'   => $grupo->sum('jol'),
                'total_total' => $grupo->sum('total'),
                'total_total_usd'   => $grupo->sum('total_usd'),
                ];
            });

            $excel->sheet($casinoNombre, function($sheet) use ( $datos, $casinoId, $totalesPorAnio) {
                $fila = 1;

                $sheet->row($fila, [
                  'Mes',
                  'MTM en Pesos', 'MTM en Dólares',
                  'MP en Pesos', 'MP en Dólares',
                  'Bingo', 'Juego Online',
                  'Total en Pesos', 'Total en Dólares',

                ]);

                $sheet->cells("A1:I1", function($cells) use ($casinoId) {
                    switch ($casinoId) {
                case 1:
                    $color = '#339966';

                    break;
                case 2:
                    $color = '#ff0000';

                    break;
                case 3:
                    $color = '#ffcc00';

                    break;
                default:
                    $color = '#222222';

            }
                    $cells->setBackground($color);
                    $cells->setFontColor('#000000');
                    $cells->setFontWeight('bold');
                    $cells->setAlignment('center');
                });

                $sheet->getStyle("A1:I1")->getAlignment()
                    ->setWrapText(true)
                    ->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
                $sheet->setHeight(1, 50);


                $fila++;

                $sheet->cells("A1:I999", function($cells) {
                    $cells->setFontFamily('Arial');
                    $cells->setFontSize(10);
                });

                foreach ($datos as $anio => $registros) {
                    $sheet->mergeCells("A{$fila}:I{$fila}");
                    $sheet->setCellValue("A{$fila}", $anio);
                    $sheet->cells("A{$fila}:I{$fila}", function($cells){
                        $cells->setBackground('#CCCCCC');
                        $cells->setFontWeight('bold');
                        $cells->setFontSize(13);
                        $cells->setAlignment('center');
                    });
                    $sheet->setHeight($fila, 20);
                    $fila++;

                    foreach ($registros as $r) {
                        setlocale(LC_TIME, 'es_ES.UTF-8');
                        $mesEsp = ucfirst(strftime('%B', strtotime($r->{'Mes'} . ' 1')));

                        $sheet->row($fila, [
                          $mesEsp,
                          "$ " . number_format($r->{'mtm'}, 2, ',', '.'),
                          "USD " . number_format($r->{'mtm_usd'}, 2, ',', '.'),
                          "$ " . number_format($r->{'mp'}, 2, ',', '.'),
                          "USD " . number_format($r->{'mp_usd'}, 2, ',', '.'),
                          "$ " . number_format($r->{'bingo'}, 2, ',', '.'),
                          "$ " . number_format($r->{'jol'}, 2, ',', '.'),
                          "$ " . number_format($r->{'total'}, 2, ',', '.'),
                          "USD " . number_format($r->{'total_usd'}, 2, ',', '.'),
                        ]);

                        $sheet->cells("A{$fila}", function($cells){
                            $cells->setBackground('#FFFF99');
                            $cells->setFontWeight('bold');
                            $cells->setAlignment('left');
                        });
                        $sheet->cells("B{$fila}:I{$fila}", function($cells){
                            $cells->setAlignment('center');
                        });
                        $fila++;
                    }

                    $totales = $totalesPorAnio[$anio];
                        $sheet->row($fila, [
                            'TOTAL ' . $anio,
                            "$ " . number_format($totales['total_mtm'], 2, ',', '.'),
                            "USD " . number_format($totales['total_mtm_usd'], 2, ',', '.'),
                            "$ " . number_format($totales['total_mp'], 2, ',', '.'),
                            "USD " . number_format($totales['total_mp_usd'], 2, ',', '.'),
                            "$ " . number_format($totales['total_bingo'], 2, ',', '.'),
                            "$ " . number_format($totales['total_jol'], 2, ',', '.'),
                            "$ " . number_format($totales['total_total'], 2, ',', '.'),
                            "USD " . number_format($totales['total_total_usd'], 2, ',', '.'),
                        ]);
                        $sheet->cells("A{$fila}", function($cells){
                            $cells->setBackground('#CCCCFF');
                            $cells->setFontWeight('bold');
                        });
                        $sheet->cells("B{$fila}:I{$fila}", function($cells){
                            $cells->setAlignment('center');
                            $cells->setFontWeight('bold');
                        });
                        $fila++;
                }



                $sheet->getStyle("A1:I1")->applyFromArray([
                    'borders' => [
                        'allborders' => [
                            'style' => PHPExcel_Style_Border::BORDER_THICK,
                            'color' => ['argb' => 'FF000000']
                        ]
                    ]
                ]);
                $sheet->getStyle("A2:I" . ($fila - 1))->applyFromArray([
                    'borders' => [
                        'allborders' => [
                            'style' => PHPExcel_Style_Border::BORDER_THIN,
                            'color' => ['argb' => 'FF000000']
                        ]
                    ]
                ]);

                $anchos = [9, 25, 25, 25, 18, 16, 14, 18,
                    16, 14, 20, 18, 14, 15, 18, 20, 20,
                    20, 20, 20, 20, 20];
                foreach (range('A', 'I') as $i => $col) {
                    $sheet->setWidth($col, $anchos[$i]);
                }

                $sheet->setFreeze('A2');
            });
        }
    })->export('xlsx');
}

// APORTES PATRONALES
public function guardarAportesPatronales(Request $request){

      $path = null;

      try {
          DB::beginTransaction();

          $AportesPatronales = new RegistroAportesPatronales();
          $AportesPatronales->fecha_AportesPatronales = $request->fecha_AportesPatronales.'-01';

          $AportesPatronales->fecha_pres = $request->fecha_AportesPatronalesPres;
          $AportesPatronales->fecha_pago = $request->fecha_pago_AportesPatronales;

          $AportesPatronales->cant_empleados = $request->cant_empleados_AportesPatronales;
          $AportesPatronales->monto_pagado = $request->monto_pagado_AportesPatronales;
          $AportesPatronales->observaciones = $request->obs_AportesPatronales;

          $AportesPatronales->casino = $request->casinoAportesPatronales;
          $AportesPatronales->fecha_toma = date('Y-m-d h:i:s', time());
          $AportesPatronales->usuario = UsuarioController::getInstancia()->quienSoy()['usuario']['id_usuario'];
          $AportesPatronales->save();

          $files = Arr::wrap($request->file('uploadAportesPatronales'));
                    foreach ($files as $file) {
                        if (!($file instanceof UploadedFile) || !$file->isValid()) continue;

                        $base = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
                        $ext  = $file->getClientOriginalExtension();
                        $safe = preg_replace('/\s+/', '_', $base);
                        $filename = time().'_'.Str::random(6).'_'.$safe.($ext?'.'.$ext:'');

                        $file->storeAs('public/RegistroAportesPatronales', $filename);

                        $AportesPatronales->archivos()->create([
                            'path'       => $filename,
                            'usuario'    => $AportesPatronales->usuario,
                            'fecha_toma' => date('Y-m-d H:i:s'),
                        ]);
                    }
          DB::commit();

          return response()->json([
            'success' => true,
             'id' => $AportesPatronales->id_registroAportesPatronales,
             'AportesPatronales'  => $request->all(),
             'path'    => $path,
             'url'     => Storage::url($path)
           ]);
      } catch (\Exception $e) {
          DB::rollBack();
          return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
      }

}

public function ultimasAportesPatronales(Request $request)
{
    $page    = max(1, (int)$request->query('page', 1));
    $perPage = max(1, (int)$request->query('page_size', 20));

    $user = Usuario::find(session('id_usuario'));
    $allowedCasinoIds = $user->casinos->pluck('id_casino')->toArray();


    $query = RegistroAportesPatronales::with('casinoAportesPatronales')
              ->withCount('archivos')
              ->whereIn('casino', $allowedCasinoIds)
              ->orderBy('fecha_AportesPatronales', 'desc')
              ->orderBy('casino', 'desc');


    if ($c = $request->query('id_casino')) {
      $query->where('casino', $c);
    }
    if ($desde = $request->query('desde')){
      $desde = $desde."-01";
      $query->where('fecha_AportesPatronales',">=",$desde);
    }
    if ($hasta = $request->query('hasta')){
      $hasta = $hasta."-01";
      $query->where('fecha_AportesPatronales',"<=",$hasta);
    }

    $total = $query->count();

    $registros = $query
        ->skip(($page - 1) * $perPage)
        ->take($perPage)
        ->get();

    $datos = $registros->map(function($r) {
        return [
            'id_registroAportesPatronales' => $r->id_registroAportesPatronales,
            'fecha_AportesPatronales'   => $r->fecha_AportesPatronales,
            'total' => $r->total,
            'total_usd' => $r->total_usd,
            'casino'      => $r->casinoAportesPatronales ? $r->casinoAportesPatronales->nombre : '-',
	  'tiene_archivos' => $r->archivos_count>0,        ];
    });

    return response()->json([
        'registros'  => $datos,
        'pagination' => [
            'current_page' => $page,
            'per_page'     => $perPage,
            'total'        => $total,
        ],
    ]);
}


public function archivosAportesPatronales($id)
{
    $AportesPatronales = RegistroAportesPatronales::with('archivos')->findOrFail($id);

    $files = $AportesPatronales->archivos->map(function($a){
        return [
            'id'     => $a->id_registro_archivo,
            'nombre' => basename($a->path),
            'url'    => \Illuminate\Support\Facades\Storage::url($a->path),
            'fecha'  => $a->fecha_toma,
        ];
    });

    return response()->json($files->values());
}

public function llenarAportesPatronalesEdit($id)
{
    $r = RegistroAportesPatronales::findOrFail($id);

    return response()->json([
        'fecha'          => $r->fecha_AportesPatronales,
        'fecha_pres'     => $r->fecha_pres,
        'fecha_pago'     => $r->fecha_pago,
        'casino'         => $r->casino,
        'cant_empleados' => $r->cant_empleados,
        'monto_pagado'   => $r->monto_pagado,
        'obs'            => $r->observaciones,
    ]);
}

public function actualizarAportesPatronales(Request $request, $id)
{
    $r = RegistroAportesPatronales::findOrFail($id);

    $r->fecha_AportesPatronales = $request->input('fecha_AportesPatronales').'-01';
    $r->fecha_pres              = $request->input('fecha_AportesPatronalesPres');
    $r->fecha_pago              = $request->input('fecha_pago_AportesPatronales');
    $r->cant_empleados          = $request->input('cant_empleados_AportesPatronales');
    $r->monto_pagado            = $request->input('monto_pagado_AportesPatronales');
    $r->observaciones           = $request->input('obs_AportesPatronales');
    $r->casino                  = $request->input('casinoAportesPatronales');
    $r->save();

    $saved = 0;
    $files = Arr::wrap($request->file('uploadAportesPatronales'));
    foreach ($files as $file) {
        if (!($file instanceof UploadedFile) || !$file->isValid()) continue;

        $base = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
        $ext  = $file->getClientOriginalExtension();
        $safe = preg_replace('/\s+/', '_', $base);
        $name = time().'_'.Str::random(6).'_'.$safe.($ext ? '.'.$ext : '');

        $file->storeAs('public/RegistroAportesPatronales', $name);

        $r->archivos()->create([
            'path'       => $name,
            'usuario'    => UsuarioController::getInstancia()->quienSoy()['usuario']['id_usuario'],
            'fecha_toma' => date('Y-m-d H:i:s'),
        ]);

        $saved++;
    }

    return response()->json(['success' => true, 'saved_files' => $saved]);
}


public function eliminarAportesPatronales($id){
  $AportesPatronales = RegistroAportesPatronales::findOrFail($id);
  if(is_null($AportesPatronales)) return 0;
  RegistroAportesPatronales::destroy($id);
  return 1;
}

public function llenarAportesPatronales($id){
  $AportesPatronales = RegistroAportesPatronales::with('casinoAportesPatronales')->findOrFail($id);
  if(is_null($AportesPatronales)) return 0;

  return response()->json([

    'fecha' => $AportesPatronales->fecha_AportesPatronales,
    'casino' => $AportesPatronales->casinoAportesPatronales ? $AportesPatronales->casinoAportesPatronales->nombre : '-',
    'fecha_pres' => $AportesPatronales->fecha_pres,
    'fecha_pago' => $AportesPatronales->fecha_pago,
    'cant_empleados' => $AportesPatronales->cant_empleados,
    'monto_pagado' => $AportesPatronales->monto_pagado,
    'obs_AportesPatronales' => $AportesPatronales->observaciones

  ]);

}

public function descargarAportesPatronalesCsv(Request $request)
{
    $casinoId = $request->query('casino');
    $desde    = $request->query('desde');
    $hasta    = $request->query('hasta');
    $user = Usuario::find(session('id_usuario'));
    $allowedCasinoIds = $user->casinos->pluck('id_casino')->toArray();

    $registros = RegistroAportesPatronales::with('casinoAportesPatronales')
        ->whereIn('casino',$allowedCasinoIds)
        ->when($casinoId && $casinoId != 4, function ($q) use ($casinoId) {
            $q->where('casino', $casinoId);
        })
        ->when($desde, function ($q) use ($desde) {
            $q->where('fecha_AportesPatronales', '>=', $desde . '-01');
        })
        ->when($hasta, function ($q) use ($hasta) {
            $q->where('fecha_AportesPatronales', '<=', $hasta . '-01');
        })
        ->orderBy('casino')
        ->orderBy('fecha_AportesPatronales')
        ->get();

    $csv = [];
    $csv[] = ['Casino', 'Año', 'Mes', 'Fecha Presentación', 'Fecha Pago', 'Cantidad de Empleados', 'Monto Pagado', 'Observaciones'];

    foreach ($registros as $r) {
        setlocale(LC_TIME, 'es_ES.UTF-8');
        $anio     = date('Y', strtotime($r->fecha_AportesPatronales));
        $mes      = strftime('%B', strtotime($r->fecha_AportesPatronales));
        $casino   = $r->casinoAportesPatronales ? $r->casinoAportesPatronales->nombre : '-';
        $monto_pagado = number_format($r->monto_pagado, 2, '.', '');
        $fecha_pres = $r->fecha_pres;
        $fecha_pago = $r->fecha_pago;
        $cant = $r->cant_empleados;
        $observaciones = $r->observaciones;

        $csv[] = [
            $casino,
            $anio,
            ucfirst($mes),
            $fecha_pres,
            $fecha_pago,
            $cant,
            $monto_pagado,
            $observaciones,

        ];
    }

    $nombreCasino = $casinoId == 4 ? 'todos' : Casino::find($casinoId)->nombre ?? 'desconocido';
    $filename = "AportesPatronales_{$nombreCasino}_" . date('Ymd_His') . ".csv";

    $handle = fopen('php://temp', 'r+');
    foreach ($csv as $linea) {
        fputcsv($handle, $linea, ',');
    }
    rewind($handle);
    $contenido = stream_get_contents($handle);
    fclose($handle);

    return response($contenido, 200, [
        'Content-Type'        => 'text/csv; charset=UTF-8',
        'Content-Disposition' => "attachment; filename=\"$filename\"",
        'Cache-Control'       => 'no-store, no-cache'
    ]);
}

public function descargarAportesPatronalesXlsx(Request $request)
{
    $casinoId = $request->query('casino');
    $desde = $request->query('desde');
    $hasta = $request->query('hasta');

    $casino = Casino::findOrFail($casinoId);

    $query = RegistroAportesPatronales::select([
        DB::raw("YEAR(fecha_AportesPatronales) AS anio"),
        DB::raw("MONTHNAME(fecha_AportesPatronales) AS Mes"),
        DB::raw("fecha_pres"),
        DB::raw("fecha_pago"),
        DB::raw("cant_empleados"),
        DB::raw("monto_pagado"),
        DB::raw("observaciones"),

    ])
    ->where('casino', $casinoId);

    if ($desde) {
        $query->where('fecha_AportesPatronales', '>=', $desde . '-01');
    }

    if ($hasta) {
        $query->where('fecha_AportesPatronales', '<=', $hasta . '-31');
    }

    $datos = $query->orderBy('fecha_AportesPatronales')->get()->groupBy('anio');



    $filename = "registro_AportesPatronales_" . str_replace(' ', '_', strtolower($casino->nombre));

    return Excel::create($filename, function($excel) use ($datos, $casino, $casinoId) {
        $excel->sheet('AportesPatronales', function($sheet) use ($datos, $casino, $casinoId) {
            $fila = 1;

            $sheet->row($fila, [
                'Mes',
                'Fecha Presentación',
                'Fecha Pago',
                'Cantidad de Empleados', 'Monto Pagado', 'Observaciones',
            ]);
            $sheet->cells("A1:F1", function($cells) use ($casinoId) {
                switch ($casinoId) {
                case 1:
                    $color = '#339966';

                    break;
                case 2:
                    $color = '#ff0000';

                    break;
                case 3:
                    $color = '#ffcc00';

                    break;
                default:
                    $color = '#222222';

            }

                $cells->setBackground($color);
                $cells->setFontColor('#000000');
                $cells->setFontWeight('bold');
                $cells->setAlignment('center');
            });

            $sheet->getStyle("A1:F1")->getAlignment()->setWrapText(true)->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
            $sheet->setHeight(1, 50);
            $sheet->cells("A1:F999", function($cells){
                $cells->setFontFamily('Arial');
                $cells->setFontSize(10);
            });

            $fila++;
            foreach ($datos as $anio => $registros) {
                $sheet->mergeCells("A{$fila}:F{$fila}");
                $sheet->setCellValue("A{$fila}", $anio);
                $sheet->cells("A{$fila}:F{$fila}", function($cells){
                    $cells->setBackground('#CCCCCC');
                    $cells->setFontWeight('bold');
                    $cells->setFontSize(13);
                    $cells->setAlignment('center');
                });
                $sheet->getStyle("A{$fila}:F{$fila}")->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
                $sheet->setHeight($fila, 20);
                $fila++;

                foreach ($registros as $r) {
                    setlocale(LC_TIME, 'es_ES.UTF-8');
                    $mesEsp = ucfirst(strftime('%B', strtotime($r->{'Mes'} . ' 1')));

                    $sheet->row($fila, [
                        $mesEsp,
                        $r->fecha_pres,
                        $r->fecha_pago,
                        $r->cant_empleados,
                        "$ " . number_format($r->{'monto_pagado'}, 2, ',', '.'),
                        $r->observaciones,

                    ]);
                    $sheet->cells("A{$fila}", function($cells){
                        $cells->setBackground('#FFFF99');
                        $cells->setFontWeight('bold');
                        $cells->setAlignment('left');
                    });

                    $sheet->cells("B{$fila}:F{$fila}", function($cells){
                        $cells->setAlignment('center');
                    });

                    $sheet->getStyle("A{$fila}:F{$fila}")->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
                    $fila++;
                }

            }

            $sheet->getStyle("A1:F1")->applyFromArray([
                'borders' => [
                    'allborders' => [
                        'style' => PHPExcel_Style_Border::BORDER_THICK,
                        'color' => ['argb' => 'FF000000']
                    ]
                ]
            ]);

            $sheet->getStyle("A2:F" . ($fila -1))->applyFromArray([
                'borders' => [
                    'allborders' => [
                        'style' => PHPExcel_Style_Border::BORDER_THIN,
                        'color' => ['argb' => 'FF000000']
                    ]
                ]
            ]);

            $anchos = [
                9, 22, 22, 22, 22, 22, 22, 22,
                22, 22, 22, 18, 15, 15, 18, 20
            ];
            foreach (range('A', 'F') as $i => $col) {
                $sheet->setWidth($col, $anchos[$i]);
            }

            $sheet->setFreeze('A2');
        });
    })->export('xlsx');
}

public function descargarAportesPatronalesXlsxTodos(Request $request)
{
    $desde = $request->query('desde');
    $hasta = $request->query('hasta');

    $user = Usuario::find(session('id_usuario'));
    $casinos = $user->casinos->pluck('nombre', 'id_casino');

    return Excel::create('registro_AportesPatronales_todos', function($excel) use ($casinos, $desde, $hasta) {
        foreach ($casinos as $casinoId => $casinoNombre) {

            $datos = RegistroAportesPatronales::select([
              DB::raw("YEAR(fecha_AportesPatronales) AS anio"),
              DB::raw("MONTHNAME(fecha_AportesPatronales) AS Mes"),
              DB::raw("fecha_pres"),
              DB::raw("fecha_pago"),
              DB::raw("cant_empleados"),
              DB::raw("monto_pagado"),
              DB::raw("observaciones"),
            ])
            ->where('casino', $casinoId)
            ->when($desde, function($q) use ($desde) {
                return $q->where('fecha_AportesPatronales', '>=', $desde . '-01');
            })
            ->when($hasta, function($q) use ($hasta) {
                return $q->where('fecha_AportesPatronales', '<=', $hasta . '-31');
            })
            ->orderBy('fecha_AportesPatronales')
            ->get()
            ->groupBy('anio');



            $excel->sheet($casinoNombre, function($sheet) use ( $datos, $casinoId) {
                $fila = 1;

                $sheet->row($fila, [
                  'Mes',
                  'Fecha Presentación',
                  'Fecha Pago',
                  'Cantidad de Empleados', 'Monto Pagado', 'Observaciones',

                ]);

                $sheet->cells("A1:F1", function($cells) use ($casinoId) {
                    switch ($casinoId) {
                case 1:
                    $color = '#339966';

                    break;
                case 2:
                    $color = '#ff0000';

                    break;
                case 3:
                    $color = '#ffcc00';

                    break;
                default:
                    $color = '#222222';

            }
                    $cells->setBackground($color);
                    $cells->setFontColor('#000000');
                    $cells->setFontWeight('bold');
                    $cells->setAlignment('center');
                });

                $sheet->getStyle("A1:F1")->getAlignment()
                    ->setWrapText(true)
                    ->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
                $sheet->setHeight(1, 50);


                $fila++;

                $sheet->cells("A1:F999", function($cells) {
                    $cells->setFontFamily('Arial');
                    $cells->setFontSize(10);
                });

                foreach ($datos as $anio => $registros) {
                    $sheet->mergeCells("A{$fila}:F{$fila}");
                    $sheet->setCellValue("A{$fila}", $anio);
                    $sheet->cells("A{$fila}:F{$fila}", function($cells){
                        $cells->setBackground('#CCCCCC');
                        $cells->setFontWeight('bold');
                        $cells->setFontSize(13);
                        $cells->setAlignment('center');
                    });
                    $sheet->setHeight($fila, 20);
                    $fila++;

                    foreach ($registros as $r) {
                        setlocale(LC_TIME, 'es_ES.UTF-8');
                        $mesEsp = ucfirst(strftime('%B', strtotime($r->{'Mes'} . ' 1')));

                        $sheet->row($fila, [
                          $mesEsp,
                          $r->fecha_pres,
                          $r->fecha_pago,
                          $r->cant_empleados,
                          "$ " . number_format($r->{'monto_pagado'}, 2, ',', '.'),
                          $r->observaciones,
                        ]);

                        $sheet->cells("A{$fila}", function($cells){
                            $cells->setBackground('#FFFF99');
                            $cells->setFontWeight('bold');
                            $cells->setAlignment('left');
                        });
                        $sheet->cells("B{$fila}:F{$fila}", function($cells){
                            $cells->setAlignment('center');
                        });
                        $fila++;
                    }

                }



                $sheet->getStyle("A1:F1")->applyFromArray([
                    'borders' => [
                        'allborders' => [
                            'style' => PHPExcel_Style_Border::BORDER_THICK,
                            'color' => ['argb' => 'FF000000']
                        ]
                    ]
                ]);
                $sheet->getStyle("A2:F" . ($fila - 1))->applyFromArray([
                    'borders' => [
                        'allborders' => [
                            'style' => PHPExcel_Style_Border::BORDER_THIN,
                            'color' => ['argb' => 'FF000000']
                        ]
                    ]
                ]);

                $anchos = [9, 25, 25, 25, 18, 16, 14, 18,
                    16, 14, 20, 18, 14, 15, 18, 20, 20,
                    20, 20, 20, 20, 20];
                foreach (range('A', 'F') as $i => $col) {
                    $sheet->setWidth($col, $anchos[$i]);
                }

                $sheet->setFreeze('A2');
            });
        }
    })->export('xlsx');
}


//PROMO TICKETS



public function guardarPromoTickets(Request $request){

      $path = null;


      try {
          DB::beginTransaction();

          $PromoTickets = new RegistroPromoTickets();
          $PromoTickets->fecha_PromoTickets = $request->fecha_PromoTickets.'-01';

          $PromoTickets->cantidad = $request->cant_PromoTickets;
          $PromoTickets->importe = $request->importe_PromoTickets;

          $PromoTickets->casino = $request->casinoPromoTickets;
          $PromoTickets->fecha_toma = date('Y-m-d h:i:s', time());
          $PromoTickets->usuario = UsuarioController::getInstancia()->quienSoy()['usuario']['id_usuario'];
          $PromoTickets->save();

          $files = Arr::wrap($request->file('uploadPromoTickets'));
          foreach ($files as $file) {
              if (!($file instanceof UploadedFile) || !$file->isValid()) continue;

              $base = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
              $ext  = $file->getClientOriginalExtension();
              $safe = preg_replace('/\s+/', '_', $base);
              $filename = time().'_'.Str::random(6).'_'.$safe.($ext?'.'.$ext:'');

              $file->storeAs('public/RegistroPromoTickets', $filename);

              $PromoTickets->archivos()->create([
                  'path'       => $filename,
                  'usuario'    => $PromoTickets->usuario,
                  'fecha_toma' => date('Y-m-d H:i:s'),
              ]);
          }

          DB::commit();

          return response()->json([
            'success' => true,
             'id' => $PromoTickets->id_registroPromoTickets,
             'PromoTickets'  => $request->all(),
             'path'    => $path,
             'url'     => Storage::url($path)
           ]);
      } catch (\Exception $e) {
          DB::rollBack();
          return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
      }

}


public function archivosPromoTickets($id)
{
    $PromoTickets = RegistroPromoTickets::with('archivos')->findOrFail($id);

    $files = $PromoTickets->archivos->map(function($a){
        return [
            'id'     => $a->id_registro_archivo,
            'nombre' => basename($a->path),
            'url'    => \Illuminate\Support\Facades\Storage::url($a->path),
            'fecha'  => $a->fecha_toma,
        ];
    });

    return response()->json($files->values());
}


public function llenarPromoTicketsEdit($id){
    $p = RegistroPromoTickets::findOrFail($id);

    return response()->json([
        'fecha'     => $p->fecha_PromoTickets,
        'casino'    => $p->casino,
        'cantidad'  => $p->cantidad,
        'importe'   => $p->importe,
    ]);
}


public function actualizarPromoTickets(Request $request, $id)
{
    $r = RegistroPromoTickets::findOrFail($id);

    $r->fecha_PromoTickets = $request->input('fecha_PromoTickets').'-01';
    $r->casino             = $request->input('casinoPromoTickets');
    $r->cantidad           = $request->input('cant_PromoTickets');
    $r->importe            = $request->input('importe_PromoTickets');
    $r->save();

    $saved = 0;
    $files = Arr::wrap($request->file('uploadPromoTickets'));
    foreach ($files as $file) {
        if (!($file instanceof UploadedFile) || !$file->isValid()) continue;

        $base = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
        $ext  = $file->getClientOriginalExtension();
        $safe = preg_replace('/\s+/', '_', $base);
        $name = time().'_'.Str::random(6).'_'.$safe.($ext ? '.'.$ext : '');

        $file->storeAs('public/RegistroPromoTickets', $name);

        $r->archivos()->create([
            'path'       => $name,
            'usuario'    => UsuarioController::getInstancia()->quienSoy()['usuario']['id_usuario'],
            'fecha_toma' => date('Y-m-d H:i:s'),
        ]);

        $saved++;
    }

    return response()->json(['success' => true, 'saved_files' => $saved]);
}


public function ultimasPromoTickets(Request $request)
{
    $page    = max(1, (int)$request->query('page', 1));
    $perPage = max(1, (int)$request->query('page_size', 20));

    $user = Usuario::find(session('id_usuario'));
    $allowedCasinoIds = $user->casinos->pluck('id_casino')->toArray();


    $query = RegistroPromoTickets::with('casinoPromoTickets')
              ->withCount('archivos')
              ->whereIn('casino', $allowedCasinoIds)
              ->orderBy('fecha_PromoTickets', 'desc')
              ->orderBy('casino', 'desc');


    if ($c = $request->query('id_casino')) {
      $query->where('casino', $c);
    }
    if ($desde = $request->query('desde')){
      $desde = $desde."-01";
      $query->where('fecha_PromoTickets',">=",$desde);
    }
    if ($hasta = $request->query('hasta')){
      $hasta = $hasta."-01";
      $query->where('fecha_PromoTickets',"<=",$hasta);
    }

    $total = $query->count();

    $registros = $query
        ->skip(($page - 1) * $perPage)
        ->take($perPage)
        ->get();

    $datos = $registros->map(function($r) {
        return [
            'id_registroPromoTickets' => $r->id_registroPromoTickets,
            'fecha_PromoTickets'   => $r->fecha_PromoTickets,
            'cantidad' => $r->cantidad,
            'importe' => $r->importe,
            'casino'      => $r->casinoPromoTickets ? $r->casinoPromoTickets->nombre : '-',
            	  'tiene_archivos' => $r->archivos_count>0,
        ];
    });

    return response()->json([
        'registros'  => $datos,
        'pagination' => [
            'current_page' => $page,
            'per_page'     => $perPage,
            'total'        => $total,
        ],
    ]);
}


public function eliminarPromoTickets($id){
  $PromoTickets = RegistroPromoTickets::findOrFail($id);
  if(is_null($PromoTickets)) return 0;
  RegistroPromoTickets::destroy($id);
  return 1;
}

public function llenarPromoTickets($id){
  $PromoTickets = RegistroPromoTickets::with('casinoPromoTickets')->findOrFail($id);
  if(is_null($PromoTickets)) return 0;

  return response()->json([

    'fecha' => $PromoTickets->fecha_PromoTickets,
    'casino' => $PromoTickets->casinoPromoTickets ? $PromoTickets->casinoPromoTickets->nombre : '-',

  ]);

}

public function descargarPromoTicketsCsv(Request $request)
{
    $casinoId = $request->query('casino');
    $desde    = $request->query('desde');
    $hasta    = $request->query('hasta');
    $user = Usuario::find(session('id_usuario'));
    $allowedCasinoIds = $user->casinos->pluck('id_casino')->toArray();

    $registros = RegistroPromoTickets::with('casinoPromoTickets')
        ->whereIn('casino',$allowedCasinoIds)
        ->when($casinoId && $casinoId != 4, function ($q) use ($casinoId) {
            $q->where('casino', $casinoId);
        })
        ->when($desde, function ($q) use ($desde) {
            $q->where('fecha_PromoTickets', '>=', $desde . '-01');
        })
        ->when($hasta, function ($q) use ($hasta) {
            $q->where('fecha_PromoTickets', '<=', $hasta . '-01');
        })
        ->orderBy('casino')
        ->orderBy('fecha_PromoTickets')
        ->get();

    $csv = [];
    $csv[] = ['Casino', 'Año', 'Mes', 'Cantidad', 'Importe' ];

    foreach ($registros as $r) {
        setlocale(LC_TIME, 'es_ES.UTF-8');
        $anio     = date('Y', strtotime($r->fecha_PromoTickets));
        $mes      = strftime('%B', strtotime($r->fecha_PromoTickets));
        $casino   = $r->casinoPromoTickets ? $r->casinoPromoTickets->nombre : '-';
        $importe = number_format($r->importe, 2, '.', '');
        $cantidad = $r->cantidad;

        $csv[] = [
            $casino,
            $anio,
            ucfirst($mes),
            $cantidad,
            $importe,

        ];
    }

    $nombreCasino = $casinoId == 4 ? 'todos' : Casino::find($casinoId)->nombre ?? 'desconocido';
    $filename = "PromoTickets_{$nombreCasino}_" . date('Ymd_His') . ".csv";

    $handle = fopen('php://temp', 'r+');
    foreach ($csv as $linea) {
        fputcsv($handle, $linea, ',');
    }
    rewind($handle);
    $contenido = stream_get_contents($handle);
    fclose($handle);

    return response($contenido, 200, [
        'Content-Type'        => 'text/csv; charset=UTF-8',
        'Content-Disposition' => "attachment; filename=\"$filename\"",
        'Cache-Control'       => 'no-store, no-cache'
    ]);
}

public function descargarPromoTicketsXlsx(Request $request)
{
    $casinoId = $request->query('casino');
    $desde = $request->query('desde');
    $hasta = $request->query('hasta');

    $casino = Casino::findOrFail($casinoId);

    $query = RegistroPromoTickets::select([
        DB::raw("YEAR(fecha_PromoTickets) AS anio"),
        DB::raw("MONTHNAME(fecha_PromoTickets) AS Mes"),
        DB::raw("cantidad"),
        DB::raw("importe"),


    ])
    ->where('casino', $casinoId);

    if ($desde) {
        $query->where('fecha_PromoTickets', '>=', $desde . '-01');
    }

    if ($hasta) {
        $query->where('fecha_PromoTickets', '<=', $hasta . '-31');
    }

    $datos = $query->orderBy('fecha_PromoTickets')->get()->groupBy('anio');

    $totalesPorAnio = $datos->map(function($grupo, $anio) {
    return [
        'anio' => $anio,
        'total_cantidad' => $grupo->sum('cantidad'),
        'total_importe'   => $grupo->sum('importe'),
        ];
    });

    $filename = "registro_PromoTickets_" . str_replace(' ', '_', strtolower($casino->nombre));

    return Excel::create($filename, function($excel) use ($datos, $casino, $casinoId, $totalesPorAnio) {
        $excel->sheet('PromoTickets', function($sheet) use ($datos, $casino, $casinoId, $totalesPorAnio) {
            $fila = 1;

            $sheet->row($fila, [
                'Mes',
                'Cantidad',
                'Importe',
            ]);
            $sheet->cells("A1:C1", function($cells) use ($casinoId) {
                switch ($casinoId) {
                case 1:
                    $color = '#339966';

                    break;
                case 2:
                    $color = '#ff0000';

                    break;
                case 3:
                    $color = '#ffcc00';

                    break;
                default:
                    $color = '#222222';

            }

                $cells->setBackground($color);
                $cells->setFontColor('#000000');
                $cells->setFontWeight('bold');
                $cells->setAlignment('center');
            });

            $sheet->getStyle("A1:C1")->getAlignment()->setWrapText(true)->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
            $sheet->setHeight(1, 50);
            $sheet->cells("A1:C999", function($cells){
                $cells->setFontFamily('Arial');
                $cells->setFontSize(10);
            });

            $fila++;
            foreach ($datos as $anio => $registros) {
                $sheet->mergeCells("A{$fila}:C{$fila}");
                $sheet->setCellValue("A{$fila}", $anio);
                $sheet->cells("A{$fila}:C{$fila}", function($cells){
                    $cells->setBackground('#CCCCCC');
                    $cells->setFontWeight('bold');
                    $cells->setFontSize(13);
                    $cells->setAlignment('center');
                });
                $sheet->getStyle("A{$fila}:C{$fila}")->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
                $sheet->setHeight($fila, 20);
                $fila++;

                foreach ($registros as $r) {
                    setlocale(LC_TIME, 'es_ES.UTF-8');
                    $mesEsp = ucfirst(strftime('%B', strtotime($r->{'Mes'} . ' 1')));

                    $sheet->row($fila, [
                        $mesEsp,
                        $r->cantidad,
                        "$ " . number_format($r->{'importe'}, 2, ',', '.'),

                    ]);
                    $sheet->cells("A{$fila}", function($cells){
                        $cells->setBackground('#FFFF99');
                        $cells->setFontWeight('bold');
                        $cells->setAlignment('left');
                    });

                    $sheet->cells("B{$fila}:C{$fila}", function($cells){
                        $cells->setAlignment('center');
                    });

                    $sheet->getStyle("A{$fila}:C{$fila}")->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
                    $fila++;
                }
                $totales = $totalesPorAnio[$anio];
                	$sheet->row($fila, [
                	    'TOTAL ' . $anio,
                	    $totales['total_cantidad'],
                	    "$ " . number_format($totales['total_importe'], 2, ',', '.'),
                	]);
                	$sheet->cells("A{$fila}", function($cells){
                	    $cells->setBackground('#CCCCFF');
                	    $cells->setFontWeight('bold');
                	});
                	$sheet->cells("B{$fila}:C{$fila}", function($cells){
                	    $cells->setAlignment('center');
                	    $cells->setFontWeight('bold');
                	});
                	$fila++;
            }

            $sheet->getStyle("A1:C1")->applyFromArray([
                'borders' => [
                    'allborders' => [
                        'style' => PHPExcel_Style_Border::BORDER_THICK,
                        'color' => ['argb' => 'FF000000']
                    ]
                ]
            ]);

            $sheet->getStyle("A2:C" . ($fila -1))->applyFromArray([
                'borders' => [
                    'allborders' => [
                        'style' => PHPExcel_Style_Border::BORDER_THIN,
                        'color' => ['argb' => 'FF000000']
                    ]
                ]
            ]);

            $anchos = [
                9, 22, 22, 22, 22, 22, 22, 22,
                22, 22, 22, 18, 15, 15, 18, 20
            ];
            foreach (range('A', 'C') as $i => $col) {
                $sheet->setWidth($col, $anchos[$i]);
            }

            $sheet->setFreeze('A2');
        });
    })->export('xlsx');
}

public function descargarPromoTicketsXlsxTodos(Request $request)
{
    $desde = $request->query('desde');
    $hasta = $request->query('hasta');

    $user = Usuario::find(session('id_usuario'));
    $casinos = $user->casinos->pluck('nombre', 'id_casino');

    return Excel::create('registro_PromoTickets_todos', function($excel) use ($casinos, $desde, $hasta) {
        foreach ($casinos as $casinoId => $casinoNombre) {

            $datos = RegistroPromoTickets::select([
              DB::raw("YEAR(fecha_PromoTickets) AS anio"),
              DB::raw("MONTHNAME(fecha_PromoTickets) AS Mes"),
              DB::raw("cantidad"),
              DB::raw("importe"),
            ])
            ->where('casino', $casinoId)
            ->when($desde, function($q) use ($desde) {
                return $q->where('fecha_PromoTickets', '>=', $desde . '-01');
            })
            ->when($hasta, function($q) use ($hasta) {
                return $q->where('fecha_PromoTickets', '<=', $hasta . '-31');
            })
            ->orderBy('fecha_PromoTickets')
            ->get()
            ->groupBy('anio');

            $totalesPorAnio = $datos->map(function($grupo, $anio) {
            return [
                'anio' => $anio,
                'total_cantidad' => $grupo->sum('cantidad'),
                'total_importe'   => $grupo->sum('importe'),
                ];
            });

            $excel->sheet($casinoNombre, function($sheet) use ( $datos, $casinoId, $totalesPorAnio) {
                $fila = 1;

                $sheet->row($fila, [
                  'Mes',
                  'Cantidad',
                  'Importe',

                ]);

                $sheet->cells("A1:C1", function($cells) use ($casinoId) {
                    switch ($casinoId) {
                case 1:
                    $color = '#339966';

                    break;
                case 2:
                    $color = '#ff0000';

                    break;
                case 3:
                    $color = '#ffcc00';

                    break;
                default:
                    $color = '#222222';

            }
                    $cells->setBackground($color);
                    $cells->setFontColor('#000000');
                    $cells->setFontWeight('bold');
                    $cells->setAlignment('center');
                });

                $sheet->getStyle("A1:C1")->getAlignment()
                    ->setWrapText(true)
                    ->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
                $sheet->setHeight(1, 50);


                $fila++;

                $sheet->cells("A1:C999", function($cells) {
                    $cells->setFontFamily('Arial');
                    $cells->setFontSize(10);
                });

                foreach ($datos as $anio => $registros) {
                    $sheet->mergeCells("A{$fila}:C{$fila}");
                    $sheet->setCellValue("A{$fila}", $anio);
                    $sheet->cells("A{$fila}:C{$fila}", function($cells){
                        $cells->setBackground('#CCCCCC');
                        $cells->setFontWeight('bold');
                        $cells->setFontSize(13);
                        $cells->setAlignment('center');
                    });
                    $sheet->setHeight($fila, 20);
                    $fila++;

                    foreach ($registros as $r) {
                        setlocale(LC_TIME, 'es_ES.UTF-8');
                        $mesEsp = ucfirst(strftime('%B', strtotime($r->{'Mes'} . ' 1')));

                        $sheet->row($fila, [
                          $mesEsp,
                          $r->cantidad,
                          "$ " . number_format($r->{'importe'}, 2, ',', '.'),
                        ]);

                        $sheet->cells("A{$fila}", function($cells){
                            $cells->setBackground('#FFFF99');
                            $cells->setFontWeight('bold');
                            $cells->setAlignment('left');
                        });
                        $sheet->cells("B{$fila}:C{$fila}", function($cells){
                            $cells->setAlignment('center');
                        });
                        $fila++;
                    }
                    $totales = $totalesPorAnio[$anio];
                    	$sheet->row($fila, [
                    	    'TOTAL ' . $anio,
                    	    $totales['total_cantidad'],
                    	    "$ " . number_format($totales['total_importe'], 2, ',', '.'),
                    	]);
                    	$sheet->cells("A{$fila}", function($cells){
                    	    $cells->setBackground('#CCCCFF');
                    	    $cells->setFontWeight('bold');
                    	});
                    	$sheet->cells("B{$fila}:C{$fila}", function($cells){
                    	    $cells->setAlignment('center');
                    	    $cells->setFontWeight('bold');
                    	});
                    	$fila++;
                }



                $sheet->getStyle("A1:C1")->applyFromArray([
                    'borders' => [
                        'allborders' => [
                            'style' => PHPExcel_Style_Border::BORDER_THICK,
                            'color' => ['argb' => 'FF000000']
                        ]
                    ]
                ]);
                $sheet->getStyle("A2:C" . ($fila - 1))->applyFromArray([
                    'borders' => [
                        'allborders' => [
                            'style' => PHPExcel_Style_Border::BORDER_THIN,
                            'color' => ['argb' => 'FF000000']
                        ]
                    ]
                ]);

                $anchos = [9, 25, 25, 25, 18, 16, 14, 18,
                    16, 14, 20, 18, 14, 15, 18, 20, 20,
                    20, 20, 20, 20, 20];
                foreach (range('A', 'C') as $i => $col) {
                    $sheet->setWidth($col, $anchos[$i]);
                }

                $sheet->setFreeze('A2');
            });
        }
    })->export('xlsx');
}

//POZOS ACUMULADOS LINKEADOS E INDIVIDUALES
public function guardarPozosAcumuladosLinkeados(Request $request){

      $path = null;


      try {
          DB::beginTransaction();

          $PozosAcumuladosLinkeados = new RegistroPozosAcumuladosLinkeados();
          $PozosAcumuladosLinkeados->fecha_PozosAcumuladosLinkeados = $request->fecha_PozosAcumuladosLinkeados.'-01';




          $PozosAcumuladosLinkeados->importe = $request->importe_PozosAcumuladosLinkeados;

          $PozosAcumuladosLinkeados->casino = $request->casinoPozosAcumuladosLinkeados;
          $PozosAcumuladosLinkeados->fecha_toma = date('Y-m-d h:i:s', time());
          $PozosAcumuladosLinkeados->usuario = UsuarioController::getInstancia()->quienSoy()['usuario']['id_usuario'];
          $PozosAcumuladosLinkeados->save();

$files = Arr::wrap($request->file('uploadPozosAcumuladosLinkeados'));
          foreach ($files as $file) {
              if (!($file instanceof UploadedFile) || !$file->isValid()) continue;

              $base = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
              $ext  = $file->getClientOriginalExtension();
              $safe = preg_replace('/\s+/', '_', $base);
              $filename = time().'_'.Str::random(6).'_'.$safe.($ext?'.'.$ext:'');

              $file->storeAs('public/RegistroPozosAcumuladosLinkeados', $filename);

              $PozosAcumuladosLinkeados->archivos()->create([
                  'path'       => $filename,
                  'usuario'    => $PozosAcumuladosLinkeados->usuario,
                  'fecha_toma' => date('Y-m-d H:i:s'),
              ]);
          }
          DB::commit();

          return response()->json([
            'success' => true,
             'id' => $PozosAcumuladosLinkeados->id_registroPozosAcumuladosLinkeados,
             'PozosAcumuladosLinkeados'  => $request->all(),
             'path'    => $path,
             'url'     => Storage::url($path)
           ]);
      } catch (\Exception $e) {
          DB::rollBack();
          return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
      }

}

public function ultimasPozosAcumuladosLinkeados(Request $request)
{
    $page    = max(1, (int)$request->query('page', 1));
    $perPage = max(1, (int)$request->query('page_size', 20));

    $user = Usuario::find(session('id_usuario'));
    $allowedCasinoIds = $user->casinos->pluck('id_casino')->toArray();


    $query = RegistroPozosAcumuladosLinkeados::with('casinoPozosAcumuladosLinkeados')
              ->withCount('archivos')
              ->whereIn('casino', $allowedCasinoIds)
              ->orderBy('fecha_PozosAcumuladosLinkeados', 'desc')
              ->orderBy('casino', 'desc');


    if ($c = $request->query('id_casino')) {
      $query->where('casino', $c);
    }
    if ($desde = $request->query('desde')){
      $desde = $desde."-01";
      $query->where('fecha_PozosAcumuladosLinkeados',">=",$desde);
    }
    if ($hasta = $request->query('hasta')){
      $hasta = $hasta."-01";
      $query->where('fecha_PozosAcumuladosLinkeados',"<=",$hasta);
    }

    $total = $query->count();

    $registros = $query
        ->skip(($page - 1) * $perPage)
        ->take($perPage)
        ->get();

    $datos = $registros->map(function($r) {
        return [
            'id_registroPozosAcumuladosLinkeados' => $r->id_registroPozosAcumuladosLinkeados,
            'fecha_PozosAcumuladosLinkeados'   => $r->fecha_PozosAcumuladosLinkeados,
            'importe' => $r->importe,
            'casino'      => $r->casinoPozosAcumuladosLinkeados ? $r->casinoPozosAcumuladosLinkeados->nombre : '-',
	  'tiene_archivos' => $r->archivos_count>0,        ];
    });

    return response()->json([
        'registros'  => $datos,
        'pagination' => [
            'current_page' => $page,
            'per_page'     => $perPage,
            'total'        => $total,
        ],
    ]);
}


public function archivosPozosAcumuladosLinkeados($id)
{
    $PozosAcumuladosLinkeados = RegistroPozosAcumuladosLinkeados::with('archivos')->findOrFail($id);

    $files = $PozosAcumuladosLinkeados->archivos->map(function($a){
        return [
            'id'     => $a->id_registro_archivo,
            'nombre' => basename($a->path),
            'url'    => \Illuminate\Support\Facades\Storage::url($a->path),
            'fecha'  => $a->fecha_toma,
        ];
    });

    return response()->json($files->values());
}

public function llenarPozosAcumuladosLinkeadosEdit($id)
{
    $r = RegistroPozosAcumuladosLinkeados::findOrFail($id);

    return response()->json([
        'fecha'   => $r->fecha_PozosAcumuladosLinkeados,
        'casino'  => $r->casino,
        'importe' => $r->importe,
    ]);
}

public function actualizarPozosAcumuladosLinkeados(Request $request, $id)
{
    $r = RegistroPozosAcumuladosLinkeados::findOrFail($id);

    $r->fecha_PozosAcumuladosLinkeados = $request->input('fecha_PozosAcumuladosLinkeados').'-01';
    $r->casino                         = $request->input('casinoPozosAcumuladosLinkeados');
    $r->importe                        = $request->input('importe_PozosAcumuladosLinkeados');
    $r->save();

    $saved = 0;
    $files = Arr::wrap($request->file('uploadPozosAcumuladosLinkeados'));
    foreach ($files as $file) {
        if (!($file instanceof UploadedFile) || !$file->isValid()) continue;

        $base = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
        $ext  = $file->getClientOriginalExtension();
        $safe = preg_replace('/\s+/', '_', $base);
        $name = time().'_'.Str::random(6).'_'.$safe.($ext ? '.'.$ext : '');

        $file->storeAs('public/RegistroPozosAcumuladosLinkeados', $name);

        $r->archivos()->create([
            'path'       => $name,
            'usuario'    => UsuarioController::getInstancia()->quienSoy()['usuario']['id_usuario'],
            'fecha_toma' => date('Y-m-d H:i:s'),
        ]);

        $saved++;
    }

    return response()->json(['success' => true, 'saved_files' => $saved]);
}


public function eliminarPozosAcumuladosLinkeados($id){
  $PozosAcumuladosLinkeados = RegistroPozosAcumuladosLinkeados::findOrFail($id);
  if(is_null($PozosAcumuladosLinkeados)) return 0;
  RegistroPozosAcumuladosLinkeados::destroy($id);
  return 1;
}

public function llenarPozosAcumuladosLinkeados($id){
  $PozosAcumuladosLinkeados = RegistroPozosAcumuladosLinkeados::with('casinoPozosAcumuladosLinkeados')->findOrFail($id);
  if(is_null($PozosAcumuladosLinkeados)) return 0;

  return response()->json([

    'fecha' => $PozosAcumuladosLinkeados->fecha_PozosAcumuladosLinkeados,
    'casino' => $PozosAcumuladosLinkeados->casinoPozosAcumuladosLinkeados ? $PozosAcumuladosLinkeados->casinoPozosAcumuladosLinkeados->nombre : '-',

  ]);

}

public function descargarPozosAcumuladosLinkeadosCsv(Request $request)
{
    $casinoId = $request->query('casino');
    $desde    = $request->query('desde');
    $hasta    = $request->query('hasta');
    $user = Usuario::find(session('id_usuario'));
    $allowedCasinoIds = $user->casinos->pluck('id_casino')->toArray();

    $registros = RegistroPozosAcumuladosLinkeados::with('casinoPozosAcumuladosLinkeados')
        ->whereIn('casino',$allowedCasinoIds)
        ->when($casinoId && $casinoId != 4, function ($q) use ($casinoId) {
            $q->where('casino', $casinoId);
        })
        ->when($desde, function ($q) use ($desde) {
            $q->where('fecha_PozosAcumuladosLinkeados', '>=', $desde . '-01');
        })
        ->when($hasta, function ($q) use ($hasta) {
            $q->where('fecha_PozosAcumuladosLinkeados', '<=', $hasta . '-01');
        })
        ->orderBy('casino')
        ->orderBy('fecha_PozosAcumuladosLinkeados')
        ->get();

    $csv = [];
    $csv[] = ['Casino', 'Año', 'Mes',  'Importe' ];

    foreach ($registros as $r) {
        setlocale(LC_TIME, 'es_ES.UTF-8');
        $anio     = date('Y', strtotime($r->fecha_PozosAcumuladosLinkeados));
        $mes      = strftime('%B', strtotime($r->fecha_PozosAcumuladosLinkeados));
        $casino   = $r->casinoPozosAcumuladosLinkeados ? $r->casinoPozosAcumuladosLinkeados->nombre : '-';
        $importe = number_format($r->importe, 2, '.', '');

        $csv[] = [
            $casino,
            $anio,
            ucfirst($mes),
            $importe,

        ];
    }

    $nombreCasino = $casinoId == 4 ? 'todos' : Casino::find($casinoId)->nombre ?? 'desconocido';
    $filename = "PozosAcumuladosLinkeados_{$nombreCasino}_" . date('Ymd_His') . ".csv";

    $handle = fopen('php://temp', 'r+');
    foreach ($csv as $linea) {
        fputcsv($handle, $linea, ',');
    }
    rewind($handle);
    $contenido = stream_get_contents($handle);
    fclose($handle);

    return response($contenido, 200, [
        'Content-Type'        => 'text/csv; charset=UTF-8',
        'Content-Disposition' => "attachment; filename=\"$filename\"",
        'Cache-Control'       => 'no-store, no-cache'
    ]);
}

public function descargarPozosAcumuladosLinkeadosXlsx(Request $request)
{
    $casinoId = $request->query('casino');
    $desde = $request->query('desde');
    $hasta = $request->query('hasta');

    $casino = Casino::findOrFail($casinoId);

    $query = RegistroPozosAcumuladosLinkeados::select([
        DB::raw("YEAR(fecha_PozosAcumuladosLinkeados) AS anio"),
        DB::raw("MONTHNAME(fecha_PozosAcumuladosLinkeados) AS Mes"),
        DB::raw("importe"),


    ])
    ->where('casino', $casinoId);

    if ($desde) {
        $query->where('fecha_PozosAcumuladosLinkeados', '>=', $desde . '-01');
    }

    if ($hasta) {
        $query->where('fecha_PozosAcumuladosLinkeados', '<=', $hasta . '-31');
    }

    $datos = $query->orderBy('fecha_PozosAcumuladosLinkeados')->get()->groupBy('anio');

    $totalesPorAnio = $datos->map(function($grupo, $anio) {
    return [
        'anio' => $anio,
        'total_importe'   => $grupo->sum('importe'),
        ];
    });

    $filename = "registro_PozosAcumuladosLinkeados_" . str_replace(' ', '_', strtolower($casino->nombre));

    return Excel::create($filename, function($excel) use ($datos, $casino, $casinoId,$totalesPorAnio) {
        $excel->sheet('PozosAcumuladosLinkeados', function($sheet) use ($datos, $casino, $casinoId, $totalesPorAnio ) {
            $fila = 1;

            $sheet->row($fila, [
                'Mes',
                'Importe',
            ]);
            $sheet->cells("A1:B1", function($cells) use ($casinoId) {
                switch ($casinoId) {
                case 1:
                    $color = '#339966';

                    break;
                case 2:
                    $color = '#ff0000';

                    break;
                case 3:
                    $color = '#ffcc00';

                    break;
                default:
                    $color = '#222222';

            }

                $cells->setBackground($color);
                $cells->setFontColor('#000000');
                $cells->setFontWeight('bold');
                $cells->setAlignment('center');
            });

            $sheet->getStyle("A1:B1")->getAlignment()->setWrapText(true)->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
            $sheet->setHeight(1, 50);
            $sheet->cells("A1:B999", function($cells){
                $cells->setFontFamily('Arial');
                $cells->setFontSize(10);
            });

            $fila++;
            foreach ($datos as $anio => $registros) {
                $sheet->mergeCells("A{$fila}:B{$fila}");
                $sheet->setCellValue("A{$fila}", $anio);
                $sheet->cells("A{$fila}:B{$fila}", function($cells){
                    $cells->setBackground('#CCCCCC');
                    $cells->setFontWeight('bold');
                    $cells->setFontSize(13);
                    $cells->setAlignment('center');
                });
                $sheet->getStyle("A{$fila}:C{$fila}")->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
                $sheet->setHeight($fila, 20);
                $fila++;

                foreach ($registros as $r) {
                    setlocale(LC_TIME, 'es_ES.UTF-8');
                    $mesEsp = ucfirst(strftime('%B', strtotime($r->{'Mes'} . ' 1')));

                    $sheet->row($fila, [
                        $mesEsp,
                        "$ " . number_format($r->{'importe'}, 2, ',', '.'),

                    ]);
                    $sheet->cells("A{$fila}", function($cells){
                        $cells->setBackground('#FFFF99');
                        $cells->setFontWeight('bold');
                        $cells->setAlignment('left');
                    });

                    $sheet->cells("B{$fila}:C{$fila}", function($cells){
                        $cells->setAlignment('center');
                    });

                    $sheet->getStyle("A{$fila}:B{$fila}")->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
                    $fila++;
                }
                $totales = $totalesPorAnio[$anio];
                	$sheet->row($fila, [
                	    'TOTAL ' . $anio,
                	    "$ " . number_format($totales['total_importe'], 2, ',', '.'),
                	]);
                	$sheet->cells("A{$fila}", function($cells){
                	    $cells->setBackground('#CCCCFF');
                	    $cells->setFontWeight('bold');
                	});
                	$sheet->cells("B{$fila}:B{$fila}", function($cells){
                	    $cells->setAlignment('center');
                	    $cells->setFontWeight('bold');
                	});
                	$fila++;
            }

            $sheet->getStyle("A1:B1")->applyFromArray([
                'borders' => [
                    'allborders' => [
                        'style' => PHPExcel_Style_Border::BORDER_THICK,
                        'color' => ['argb' => 'FF000000']
                    ]
                ]
            ]);

            $sheet->getStyle("A2:B" . ($fila -1))->applyFromArray([
                'borders' => [
                    'allborders' => [
                        'style' => PHPExcel_Style_Border::BORDER_THIN,
                        'color' => ['argb' => 'FF000000']
                    ]
                ]
            ]);

            $anchos = [
                9, 22, 22, 22, 22, 22, 22, 22,
                22, 22, 22, 18, 15, 15, 18, 20
            ];
            foreach (range('A', 'B') as $i => $col) {
                $sheet->setWidth($col, $anchos[$i]);
            }

            $sheet->setFreeze('A2');
        });
    })->export('xlsx');
}

public function descargarPozosAcumuladosLinkeadosXlsxTodos(Request $request)
{
    $desde = $request->query('desde');
    $hasta = $request->query('hasta');

    $user = Usuario::find(session('id_usuario'));
    $casinos = $user->casinos->pluck('nombre', 'id_casino');

    return Excel::create('registro_PozosAcumuladosLinkeados_todos', function($excel) use ($casinos, $desde, $hasta) {
        foreach ($casinos as $casinoId => $casinoNombre) {

            $datos = RegistroPozosAcumuladosLinkeados::select([
              DB::raw("YEAR(fecha_PozosAcumuladosLinkeados) AS anio"),
              DB::raw("MONTHNAME(fecha_PozosAcumuladosLinkeados) AS Mes"),
              DB::raw("importe"),
            ])
            ->where('casino', $casinoId)
            ->when($desde, function($q) use ($desde) {
                return $q->where('fecha_PozosAcumuladosLinkeados', '>=', $desde . '-01');
            })
            ->when($hasta, function($q) use ($hasta) {
                return $q->where('fecha_PozosAcumuladosLinkeados', '<=', $hasta . '-31');
            })
            ->orderBy('fecha_PozosAcumuladosLinkeados')
            ->get()
            ->groupBy('anio');

            $totalesPorAnio = $datos->map(function($grupo, $anio) {
            return [
                'anio' => $anio,
                'total_importe'   => $grupo->sum('importe'),
                ];
            });

            $excel->sheet($casinoNombre, function($sheet) use ( $datos, $casinoId, $totalesPorAnio) {
                $fila = 1;

                $sheet->row($fila, [
                  'Mes',
                  'Importe',

                ]);

                $sheet->cells("A1:B1", function($cells) use ($casinoId) {
                    switch ($casinoId) {
                case 1:
                    $color = '#339966';

                    break;
                case 2:
                    $color = '#ff0000';

                    break;
                case 3:
                    $color = '#ffcc00';

                    break;
                default:
                    $color = '#222222';

            }
                    $cells->setBackground($color);
                    $cells->setFontColor('#000000');
                    $cells->setFontWeight('bold');
                    $cells->setAlignment('center');
                });

                $sheet->getStyle("A1:B1")->getAlignment()
                    ->setWrapText(true)
                    ->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
                $sheet->setHeight(1, 50);


                $fila++;

                // Fuente general
                $sheet->cells("A1:B999", function($cells) {
                    $cells->setFontFamily('Arial');
                    $cells->setFontSize(10);
                });

                foreach ($datos as $anio => $registros) {
                    // Fila año
                    $sheet->mergeCells("A{$fila}:B{$fila}");
                    $sheet->setCellValue("A{$fila}", $anio);
                    $sheet->cells("A{$fila}:B{$fila}", function($cells){
                        $cells->setBackground('#CCCCCC');
                        $cells->setFontWeight('bold');
                        $cells->setFontSize(13);
                        $cells->setAlignment('center');
                    });
                    $sheet->setHeight($fila, 20);
                    $fila++;

                    foreach ($registros as $r) {
                        setlocale(LC_TIME, 'es_ES.UTF-8');
                        $mesEsp = ucfirst(strftime('%B', strtotime($r->{'Mes'} . ' 1')));

                        $sheet->row($fila, [
                          $mesEsp,
                          "$ " . number_format($r->{'importe'}, 2, ',', '.'),
                        ]);

                        $sheet->cells("A{$fila}", function($cells){
                            $cells->setBackground('#FFFF99');
                            $cells->setFontWeight('bold');
                            $cells->setAlignment('left');
                        });
                        $sheet->cells("B{$fila}:B{$fila}", function($cells){
                            $cells->setAlignment('center');
                        });
                        $fila++;
                    }
                    $totales = $totalesPorAnio[$anio];
                    	$sheet->row($fila, [
                    	    'TOTAL ' . $anio,
                    	    "$ " . number_format($totales['total_importe'], 2, ',', '.'),
                    	]);
                    	$sheet->cells("A{$fila}", function($cells){
                    	    $cells->setBackground('#CCCCFF');
                    	    $cells->setFontWeight('bold');
                    	});
                    	$sheet->cells("B{$fila}:B{$fila}", function($cells){
                    	    $cells->setAlignment('center');
                    	    $cells->setFontWeight('bold');
                    	});
                    	$fila++;
                }



                $sheet->getStyle("A1:B1")->applyFromArray([
                    'borders' => [
                        'allborders' => [
                            'style' => PHPExcel_Style_Border::BORDER_THICK,
                            'color' => ['argb' => 'FF000000']
                        ]
                    ]
                ]);
                $sheet->getStyle("A2:B" . ($fila - 1))->applyFromArray([
                    'borders' => [
                        'allborders' => [
                            'style' => PHPExcel_Style_Border::BORDER_THIN,
                            'color' => ['argb' => 'FF000000']
                        ]
                    ]
                ]);

                $anchos = [9, 25, 25, 25, 18, 16, 14, 18,
                    16, 14, 20, 18, 14, 15, 18, 20, 20,
                    20, 20, 20, 20, 20];
                foreach (range('A', 'B') as $i => $col) {
                    $sheet->setWidth($col, $anchos[$i]);
                }

                $sheet->setFreeze('A2');
            });
        }
    })->export('xlsx');
}

//CONTRIBUCION ENTE TURISTICO
public function guardarContribEnteTuristico(Request $request){


      try {
          DB::beginTransaction();

          $ContribEnteTuristico = new RegistroContribEnteTuristico();
          $ContribEnteTuristico->fecha_ContribEnteTuristico = $request->fecha_ContribEnteTuristico.'-01';
          $ContribEnteTuristico->fecha_venc = $request->fecha_venc_ContribEnteTuristico;
          $ContribEnteTuristico->fecha_pres = $request->fecha_ContribEnteTuristicoPres;
          $ContribEnteTuristico->base_imponible = $request->base_imponible_ContribEnteTuristico;
          $ContribEnteTuristico->alicuota = $request->alicuota_ContribEnteTuristico;
          $ContribEnteTuristico->impuesto_determinado = $request->impuesto_determinado_ContribEnteTuristico;
          $ContribEnteTuristico->monto_pagado = $request->monto_pagado_ContribEnteTuristico;
          $ContribEnteTuristico->observaciones = $request->obs_ContribEnteTuristico;




          $ContribEnteTuristico->casino = $request->casinoContribEnteTuristico;
          $ContribEnteTuristico->fecha_toma = date('Y-m-d h:i:s', time());
          $ContribEnteTuristico->usuario = UsuarioController::getInstancia()->quienSoy()['usuario']['id_usuario'];
          $ContribEnteTuristico->save();

          $files = Arr::wrap($request->file('uploadContribEnteTuristico'));
                    foreach ($files as $file) {
                        if (!($file instanceof UploadedFile) || !$file->isValid()) continue;

                        $base = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
                        $ext  = $file->getClientOriginalExtension();
                        $safe = preg_replace('/\s+/', '_', $base);
                        $filename = time().'_'.Str::random(6).'_'.$safe.($ext?'.'.$ext:'');

                        $file->storeAs('public/RegistroContribEnteTuristico', $filename);

                        $ContribEnteTuristico->archivos()->create([
                            'path'       => $filename,
                            'usuario'    => $ContribEnteTuristico->usuario,
                            'fecha_toma' => date('Y-m-d H:i:s'),
                        ]);
                    }
          DB::commit();

          return response()->json([
            'success' => true,
             'id' => $ContribEnteTuristico->id_registroContribEnteTuristico,
             'ContribEnteTuristico'  => $request->all(),
           ]);
      } catch (\Exception $e) {
          DB::rollBack();
          return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
      }

}

public function archivosContribEnteTuristico($id)
{
    $ContribEnteTuristico = RegistroContribEnteTuristico::with('archivos')->findOrFail($id);

    $files = $ContribEnteTuristico->archivos->map(function($a){
        return [
            'id'     => $a->id_registro_archivo,
            'nombre' => basename($a->path),
            'url'    => \Illuminate\Support\Facades\Storage::url($a->path),
            'fecha'  => $a->fecha_toma,
        ];
    });

    return response()->json($files->values());
}

public function llenarContribEnteTuristicoEdit($id){
    $r = RegistroContribEnteTuristico::findOrFail($id);

    $ym  = is_string($r->fecha_ContribEnteTuristico) ? substr($r->fecha_ContribEnteTuristico,0,7) : ($r->fecha_ContribEnteTuristico ? $r->fecha_ContribEnteTuristico->format('Y-m') : null);
    $ymd = is_string($r->fecha_pres) ? substr($r->fecha_pres,0,10) : ($r->fecha_pres ? $r->fecha_pres->format('Y-m-d') : null);
    $ymdV= is_string($r->fecha_venc) ? substr($r->fecha_venc,0,10) : ($r->fecha_venc ? $r->fecha_venc->format('Y-m-d') : null);

    return response()->json([
        'fecha'                  => $ym,
        'fecha_pres'             => $ymd,
        'fecha_venc'             => $ymdV,
        'casino'                 => $r->casino,
        'base_imponible'         => $r->base_imponible,
        'alicuota'               => $r->alicuota,
        'impuesto_determinado'   => $r->impuesto_determinado,
        'monto_pagado'           => $r->monto_pagado,
        'obs'                    => $r->observaciones,
    ]);
}
public function actualizarContribEnteTuristico(Request $request, $id)
{
    $r = RegistroContribEnteTuristico::findOrFail($id);

    $r->fecha_ContribEnteTuristico = $request->input('fecha_ContribEnteTuristico').'-01';
    $r->fecha_venc                 = $request->input('fecha_venc_ContribEnteTuristico');
    $r->fecha_pres                 = $request->input('fecha_ContribEnteTuristicoPres');
    $r->base_imponible             = $request->input('base_imponible_ContribEnteTuristico');
    $r->alicuota                   = $request->input('alicuota_ContribEnteTuristico');
    $r->impuesto_determinado       = $request->input('impuesto_determinado_ContribEnteTuristico');
    $r->monto_pagado               = $request->input('monto_pagado_ContribEnteTuristico');
    $r->observaciones              = $request->input('obs_ContribEnteTuristico');
    $r->casino                     = $request->input('casinoContribEnteTuristico');
    $r->save();

    $saved = 0;
    foreach (\Illuminate\Support\Arr::wrap($request->file('uploadContribEnteTuristico')) as $file) {
        if (!($file instanceof \Illuminate\Http\UploadedFile) || !$file->isValid()) continue;
        $base = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
        $ext  = $file->getClientOriginalExtension();
        $safe = preg_replace('/\s+/', '_', $base);
        $name = time().'_'.\Illuminate\Support\Str::random(6).'_'.$safe.($ext ? '.'.$ext : '');
        $file->storeAs('public/RegistroContribEnteTuristico', $name);
        $r->archivos()->create([
            'path'       => $name,
            'usuario'    => UsuarioController::getInstancia()->quienSoy()['usuario']['id_usuario'],
            'fecha_toma' => date('Y-m-d H:i:s'),
        ]);
        $saved++;
    }

    return response()->json(['success' => true, 'saved_files' => $saved]);
}

public function ultimasContribEnteTuristico(Request $request)
{
    $page    = max(1, (int)$request->query('page', 1));
    $perPage = max(1, (int)$request->query('page_size', 20));

    $user = Usuario::find(session('id_usuario'));
  //  $allowedCasinoIds = $user->casinos->pluck('id_casino')->toArray();


    $query = RegistroContribEnteTuristico::with('casinoContribEnteTuristico')
        //      ->whereIn('casino', $allowedCasinoIds)
                        ->withCount('archivos')
              ->orderBy('fecha_ContribEnteTuristico', 'desc')
              ->orderBy('casino', 'desc');


  //  if ($c = $request->query('id_casino')) {
  //  $query->where('casino', $c);
  //  }
    if ($desde = $request->query('desde')){
      $desde = $desde."-01";
      $query->where('fecha_ContribEnteTuristico',">=",$desde);
    }
    if ($hasta = $request->query('hasta')){
      $hasta = $hasta."-01";
      $query->where('fecha_ContribEnteTuristico',"<=",$hasta);
    }

    $total = $query->count();

    $registros = $query
        ->skip(($page - 1) * $perPage)
        ->take($perPage)
        ->get();

    $datos = $registros->map(function($r) {
        return [
            'id_registroContribEnteTuristico' => $r->id_registroContribEnteTuristico,
            'fecha_ContribEnteTuristico'   => $r->fecha_ContribEnteTuristico,
            'fecha_pres' => $r->fecha_pres,
            'monto_pagado' => $r->monto_pagado,
            'casino'      => $r->casinoContribEnteTuristico ? $r->casinoContribEnteTuristico->nombre : '-',
	  'tiene_archivos' => $r->archivos_count>0,        ];
    });

    return response()->json([
        'registros'  => $datos,
        'pagination' => [
            'current_page' => $page,
            'per_page'     => $perPage,
            'total'        => $total,
        ],
    ]);
}


public function eliminarContribEnteTuristico($id){
  $ContribEnteTuristico = RegistroContribEnteTuristico::findOrFail($id);
  if(is_null($ContribEnteTuristico)) return 0;
  RegistroContribEnteTuristico::destroy($id);
  return 1;
}

public function llenarContribEnteTuristico($id){
  $ContribEnteTuristico = RegistroContribEnteTuristico::with('casinoContribEnteTuristico')->findOrFail($id);
  if(is_null($ContribEnteTuristico)) return 0;

  return response()->json([

    'fecha' => $ContribEnteTuristico->fecha_ContribEnteTuristico,
    'casino' => $ContribEnteTuristico->casinoContribEnteTuristico ? $ContribEnteTuristico->casinoContribEnteTuristico->nombre : '-',
    'fecha_pres' => $ContribEnteTuristico->fecha_pres,
    'fecha_venc' => $ContribEnteTuristico->fecha_venc,
    'base_imponible' => $ContribEnteTuristico->base_imponible,
    'alicuota' => $ContribEnteTuristico->alicuota,
    'impuesto_determinado' => $ContribEnteTuristico->impuesto_determinado,
    'obs' => $ContribEnteTuristico->observaciones,
    'monto_pagado' => $ContribEnteTuristico->monto_pagado,

  ]);

}

public function descargarContribEnteTuristicoCsv(Request $request)
{
    $casinoId = $request->query('casino');
    $desde    = $request->query('desde');
    $hasta    = $request->query('hasta');
    $user = Usuario::find(session('id_usuario'));
    $allowedCasinoIds = $user->casinos->pluck('id_casino')->toArray();

    $registros = RegistroContribEnteTuristico::with('casinoContribEnteTuristico')
        ->whereIn('casino',$allowedCasinoIds)
        ->when($casinoId && $casinoId != 4, function ($q) use ($casinoId) {
            $q->where('casino', $casinoId);
        })
        ->when($desde, function ($q) use ($desde) {
            $q->where('fecha_ContribEnteTuristico', '>=', $desde . '-01');
        })
        ->when($hasta, function ($q) use ($hasta) {
            $q->where('fecha_ContribEnteTuristico', '<=', $hasta . '-01');
        })
        ->orderBy('casino')
        ->orderBy('fecha_ContribEnteTuristico')
        ->get();

    $csv = [];
    $csv[] = ['Año', 'Mes',  'Monto Pagado', 'Fecha Vencimiento', 'Fecha de Presentación y pago' ,'Base Imponible Juegos', 'Alicuota', 'Impuesto Determinado' ,'Observaciones' ];

    foreach ($registros as $r) {
        setlocale(LC_TIME, 'es_ES.UTF-8');
        $anio     = date('Y', strtotime($r->fecha_ContribEnteTuristico));
        $mes      = strftime('%B', strtotime($r->fecha_ContribEnteTuristico));
        $casino   = $r->casinoContribEnteTuristico ? $r->casinoContribEnteTuristico->nombre : '-';
        $monto_pagado = number_format($r->importe, 2, '.', '');
        $fecha_venc = $r->fecha_venc;
        $fecha_pres = $r->fecha_pres;
        $base = number_format($r->base_imponible, 2, '.', '');
        $alicuota = number_format($r->alicuota, 2, '.', '');
        $impuesto_determinado = number_format($r->impuesto_determinado, 2, '.', '');
        $obs = $r->observaciones;



        $csv[] = [
            $anio,
            ucfirst($mes),
            $monto_pagado,
            $fecha_venc,
            $fecha_pres,
            $base,
            $alicuota,
            $impuesto_determinado,
            $obs,

        ];
    }

    $nombreCasino = $casinoId == 4 ? 'todos' : Casino::find($casinoId)->nombre ?? 'desconocido';
    $filename = "ContribEnteTuristico_{$nombreCasino}_" . date('Ymd_His') . ".csv";

    $handle = fopen('php://temp', 'r+');
    foreach ($csv as $linea) {
        fputcsv($handle, $linea, ',');
    }
    rewind($handle);
    $contenido = stream_get_contents($handle);
    fclose($handle);

    return response($contenido, 200, [
        'Content-Type'        => 'text/csv; charset=UTF-8',
        'Content-Disposition' => "attachment; filename=\"$filename\"",
        'Cache-Control'       => 'no-store, no-cache'
    ]);
}

public function descargarContribEnteTuristicoXlsx(Request $request)
{
    $casinoId = $request->query('casino');
    $desde = $request->query('desde');
    $hasta = $request->query('hasta');

    $casino = Casino::findOrFail($casinoId);

    $query = RegistroContribEnteTuristico::select([
        DB::raw("YEAR(fecha_ContribEnteTuristico) AS anio"),
        DB::raw("MONTHNAME(fecha_ContribEnteTuristico) AS Mes"),
        DB::raw("monto_pagado"),
        DB::raw("fecha_venc"),
        DB::raw("fecha_pres"),
        DB::raw("base_imponible"),
        DB::raw("alicuota"),
        DB::raw("impuesto_determinado"),
        DB::raw("observaciones"),



    ])
    ->where('casino', $casinoId);

    if ($desde) {
        $query->where('fecha_ContribEnteTuristico', '>=', $desde . '-01');
    }

    if ($hasta) {
        $query->where('fecha_ContribEnteTuristico', '<=', $hasta . '-31');
    }

    $datos = $query->orderBy('fecha_ContribEnteTuristico')->get()->groupBy('anio');



    $filename = "registro_ContribEnteTuristico_Rosario";

    return Excel::create($filename, function($excel) use ($datos, $casino, $casinoId) {
        $excel->sheet('ContribEnteTuristico', function($sheet) use ($datos, $casino, $casinoId ) {
            $fila = 1;

            $sheet->row($fila, [
                'Mes',
                'Monto Pagado',
                'Fecha Vencimiento',
                'Fecha de Presentación y pago',
                'Base Imponible',
                'Alicuota',
                'Impuesto Determinado',
                'Observaciones',

            ]);
            $sheet->cells("A1:H1", function($cells) use ($casinoId) {
                switch ($casinoId) {
                case 1:
                    $color = '#339966';

                    break;
                case 2:
                    $color = '#ff0000';

                    break;
                case 3:
                    $color = '#ffcc00';

                    break;
                default:
                    $color = '#222222';

            }

                $cells->setBackground($color);
                $cells->setFontColor('#000000');
                $cells->setFontWeight('bold');
                $cells->setAlignment('center');
            });

            $sheet->getStyle("A1:H1")->getAlignment()->setWrapText(true)->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
            $sheet->setHeight(1, 50);
            $sheet->cells("A1:H999", function($cells){
                $cells->setFontFamily('Arial');
                $cells->setFontSize(10);
            });

            $fila++;
            foreach ($datos as $anio => $registros) {
                $sheet->mergeCells("A{$fila}:H{$fila}");
                $sheet->setCellValue("A{$fila}", $anio);
                $sheet->cells("A{$fila}:H{$fila}", function($cells){
                    $cells->setBackground('#CCCCCC');
                    $cells->setFontWeight('bold');
                    $cells->setFontSize(13);
                    $cells->setAlignment('center');
                });
                $sheet->getStyle("A{$fila}:H{$fila}")->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
                $sheet->setHeight($fila, 20);
                $fila++;

                foreach ($registros as $r) {
                    setlocale(LC_TIME, 'es_ES.UTF-8');
                    $mesEsp = ucfirst(strftime('%B', strtotime($r->{'Mes'} . ' 1')));

                    $sheet->row($fila, [
                        $mesEsp,
                        "$ " . number_format($r->{'monto_pagado'}, 2, ',', '.'),
                        $r->fecha_venc,
                        $r->fecha_pres,
                        "$ " . number_format($r->{'base_imponible'}, 2, ',', '.'),
                        "$ " . number_format($r->{'alicuota'}, 2, ',', '.'),
                        "$ " . number_format($r->{'impuesto_determinado'}, 2, ',', '.'),
                        $r->observaciones,
                    ]);
                    $sheet->cells("A{$fila}", function($cells){
                        $cells->setBackground('#FFFF99');
                        $cells->setFontWeight('bold');
                        $cells->setAlignment('left');
                    });

                    $sheet->cells("B{$fila}:H{$fila}", function($cells){
                        $cells->setAlignment('center');
                    });

                    $sheet->getStyle("A{$fila}:H{$fila}")->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
                    $fila++;
                }
              }

            $sheet->getStyle("A1:H1")->applyFromArray([
                'borders' => [
                    'allborders' => [
                        'style' => PHPExcel_Style_Border::BORDER_THICK,
                        'color' => ['argb' => 'FF000000']
                    ]
                ]
            ]);

            $sheet->getStyle("A2:H" . ($fila -1))->applyFromArray([
                'borders' => [
                    'allborders' => [
                        'style' => PHPExcel_Style_Border::BORDER_THIN,
                        'color' => ['argb' => 'FF000000']
                    ]
                ]
            ]);

            $anchos = [
                9, 22, 22, 22, 22, 22, 22, 22,
                22, 22, 22, 18, 15, 15, 18, 20
            ];
            foreach (range('A', 'H') as $i => $col) {
                $sheet->setWidth($col, $anchos[$i]);
            }

            $sheet->setFreeze('A2');

      });
    })->export('xlsx');
}

public function descargarContribEnteTuristicoXlsxTodos(Request $request)
{
    $desde = $request->query('desde');
    $hasta = $request->query('hasta');

    $user = Usuario::find(session('id_usuario'));
    $casinos = $user->casinos->pluck('nombre', 'id_casino');

    return Excel::create('registro_ContribEnteTuristico_todos', function($excel) use ($casinos, $desde, $hasta) {
        foreach ($casinos as $casinoId => $casinoNombre) {

            $datos = RegistroContribEnteTuristico::select([
              DB::raw("YEAR(fecha_ContribEnteTuristico) AS anio"),
              DB::raw("MONTHNAME(fecha_ContribEnteTuristico) AS Mes"),
              DB::raw("importe"),
            ])
            ->where('casino', $casinoId)
            ->when($desde, function($q) use ($desde) {
                return $q->where('fecha_ContribEnteTuristico', '>=', $desde . '-01');
            })
            ->when($hasta, function($q) use ($hasta) {
                return $q->where('fecha_ContribEnteTuristico', '<=', $hasta . '-31');
            })
            ->orderBy('fecha_ContribEnteTuristico')
            ->get()
            ->groupBy('anio');

            $totalesPorAnio = $datos->map(function($grupo, $anio) {
            return [
                'anio' => $anio,
                'total_importe'   => $grupo->sum('importe'),
                ];
            });

            $excel->sheet($casinoNombre, function($sheet) use ( $datos, $casinoId, $totalesPorAnio) {
                $fila = 1;

                $sheet->row($fila, [
                  'Mes',
                  'Importe',

                ]);

                $sheet->cells("A1:B1", function($cells) use ($casinoId) {
                    switch ($casinoId) {
                case 1:
                    $color = '#339966';

                    break;
                case 2:
                    $color = '#ff0000';

                    break;
                case 3:
                    $color = '#ffcc00';

                    break;
                default:
                    $color = '#222222';

            }
                    $cells->setBackground($color);
                    $cells->setFontColor('#000000');
                    $cells->setFontWeight('bold');
                    $cells->setAlignment('center');
                });

                $sheet->getStyle("A1:B1")->getAlignment()
                    ->setWrapText(true)
                    ->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
                $sheet->setHeight(1, 50);


                $fila++;

                $sheet->cells("A1:B999", function($cells) {
                    $cells->setFontFamily('Arial');
                    $cells->setFontSize(10);
                });

                foreach ($datos as $anio => $registros) {
                    $sheet->mergeCells("A{$fila}:B{$fila}");
                    $sheet->setCellValue("A{$fila}", $anio);
                    $sheet->cells("A{$fila}:B{$fila}", function($cells){
                        $cells->setBackground('#CCCCCC');
                        $cells->setFontWeight('bold');
                        $cells->setFontSize(13);
                        $cells->setAlignment('center');
                    });
                    $sheet->setHeight($fila, 20);
                    $fila++;

                    foreach ($registros as $r) {
                        setlocale(LC_TIME, 'es_ES.UTF-8');
                        $mesEsp = ucfirst(strftime('%B', strtotime($r->{'Mes'} . ' 1')));

                        $sheet->row($fila, [
                          $mesEsp,
                          "$ " . number_format($r->{'importe'}, 2, ',', '.'),
                        ]);

                        $sheet->cells("A{$fila}", function($cells){
                            $cells->setBackground('#FFFF99');
                            $cells->setFontWeight('bold');
                            $cells->setAlignment('left');
                        });
                        $sheet->cells("B{$fila}:B{$fila}", function($cells){
                            $cells->setAlignment('center');
                        });
                        $fila++;
                    }
                    $totales = $totalesPorAnio[$anio];
                    	$sheet->row($fila, [
                    	    'TOTAL ' . $anio,
                    	    "$ " . number_format($totales['total_importe'], 2, ',', '.'),
                    	]);
                    	$sheet->cells("A{$fila}", function($cells){
                    	    $cells->setBackground('#CCCCFF');
                    	    $cells->setFontWeight('bold');
                    	});
                    	$sheet->cells("B{$fila}:B{$fila}", function($cells){
                    	    $cells->setAlignment('center');
                    	    $cells->setFontWeight('bold');
                    	});
                    	$fila++;
                }



                $sheet->getStyle("A1:B1")->applyFromArray([
                    'borders' => [
                        'allborders' => [
                            'style' => PHPExcel_Style_Border::BORDER_THICK,
                            'color' => ['argb' => 'FF000000']
                        ]
                    ]
                ]);
                $sheet->getStyle("A2:B" . ($fila - 1))->applyFromArray([
                    'borders' => [
                        'allborders' => [
                            'style' => PHPExcel_Style_Border::BORDER_THIN,
                            'color' => ['argb' => 'FF000000']
                        ]
                    ]
                ]);

                $anchos = [9, 25, 25, 25, 18, 16, 14, 18,
                    16, 14, 20, 18, 14, 15, 18, 20, 20,
                    20, 20, 20, 20, 20];
                foreach (range('A', 'B') as $i => $col) {
                    $sheet->setWidth($col, $anchos[$i]);
                }

                $sheet->setFreeze('A2');
            });
        }
    })->export('xlsx');
}


// RRHH
public function guardarRRHH(Request $request){

      $path = null;


      try {
          DB::beginTransaction();

          $RRHH = new RegistroRRHH();
          $RRHH->fecha_RRHH = $request->fecha_RRHH.'-01';




          $RRHH->personal_inicio = $request->personal_inicio_RRHH;
          $RRHH->personal_final = $request->personal_final_RRHH;
          $RRHH->altas_mes = $request->altas_RRHH;
          $RRHH->bajas = $request->bajas_RRHH;
          $RRHH->personal_nomina = $request->personal_nomina_RRHH;
          $RRHH->diferencia = $request->diferencia_RRHH;
          $RRHH->tercerizados = $request->tercerizados_RRHH;
          $RRHH->total_personal = $request->total_personal_RRHH;
          $RRHH->ofertado_adjudicado = $request->ofertado_adjudicado_RRHH;
          $RRHH->ludico = $request->ludicos_RRHH;
          $RRHH->no_ludico = $request->no_ludicos_RRHH;
          $RRHH->total_tipo = $request->total_ludicos_RRHH;
          $RRHH->porcentaje_ludico = $request->porcentaje_ludicos_RRHH;
          $RRHH->porcentaje_no_ludico = $request->porcentaje_no_ludicos_RRHH;
          $RRHH->porcentaje_total = $request->total_porcentaje_ludicos_RRHH;
          $RRHH->porcentaje_ludico_viviendo = $request->porcentaje_ludicos_sf_RRHH;
          $RRHH->porcentaje_no_ludico_viviendo = $request->porcentaje_no_ludicos_sf_RRHH;
          $RRHH->porcentaje_total_viviendo = $request->porcentaje_total_sf_RRHH;
          $RRHH->diferencia_nomina_ddjj = $request->dif_nomina_RRHH;
          $RRHH->ludico_viviendo = $request->ludicos_vivivendo_RRHH;
          $RRHH->no_ludico_viviendo = $request->no_ludicos_viviendo_RRHH;
          $RRHH->total_viviendo = $request->total_ludicos_viviendo_RRHH;

          $RRHH->casino = $request->casinoRRHH;
          $RRHH->fecha_toma = date('Y-m-d h:i:s', time());
          $RRHH->usuario = UsuarioController::getInstancia()->quienSoy()['usuario']['id_usuario'];
          $RRHH->save();

          $files = Arr::wrap($request->file('uploadRRHH'));
                    foreach ($files as $file) {
                        if (!($file instanceof UploadedFile) || !$file->isValid()) continue;

                        $base = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
                        $ext  = $file->getClientOriginalExtension();
                        $safe = preg_replace('/\s+/', '_', $base);
                        $filename = time().'_'.Str::random(6).'_'.$safe.($ext?'.'.$ext:'');

                        $file->storeAs('public/RegistroRRHH', $filename);

                        $RRHH->archivos()->create([
                            'path'       => $filename,
                            'usuario'    => $RRHH->usuario,
                            'fecha_toma' => date('Y-m-d H:i:s'),
                        ]);
                    }

          DB::commit();

          return response()->json([
            'success' => true,
             'id' => $RRHH->id_registroRRHH,
             'RRHH'  => $request->all(),
             'path'    => $path,
             'url'     => Storage::url($path)
           ]);
      } catch (\Exception $e) {
          DB::rollBack();
          return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
      }

}

public function ultimasRRHH(Request $request)
{
    $page    = max(1, (int)$request->query('page', 1));
    $perPage = max(1, (int)$request->query('page_size', 20));

    $user = Usuario::find(session('id_usuario'));
    $allowedCasinoIds = $user->casinos->pluck('id_casino')->toArray();


    $query = RegistroRRHH::with('casinoRRHH')
              ->withCount('archivos')
              ->whereIn('casino', $allowedCasinoIds)
              ->orderBy('fecha_RRHH', 'desc')
              ->orderBy('casino', 'desc');


    if ($c = $request->query('id_casino')) {
      $query->where('casino', $c);
    }
    if ($desde = $request->query('desde')){
      $desde = $desde."-01";
      $query->where('fecha_RRHH',">=",$desde);
    }
    if ($hasta = $request->query('hasta')){
      $hasta = $hasta."-01";
      $query->where('fecha_RRHH',"<=",$hasta);
    }

    $total = $query->count();

    $registros = $query
        ->skip(($page - 1) * $perPage)
        ->take($perPage)
        ->get();

    $datos = $registros->map(function($r) {
        return [
            'id_registroRRHH' => $r->id_registroRRHH,
            'fecha_RRHH'   => $r->fecha_RRHH,
            'total' => $r->total_personal,
            'porcentaje' => $r->porcentaje_total_viviendo,
            'casino'      => $r->casinoRRHH ? $r->casinoRRHH->nombre : '-',
	  'tiene_archivos' => $r->archivos_count>0,        ];
    });

    return response()->json([
        'registros'  => $datos,
        'pagination' => [
            'current_page' => $page,
            'per_page'     => $perPage,
            'total'        => $total,
        ],
    ]);
}

public function ultimosPersonalInicioRRHH($casino)
{
    $valor = RegistroRRHH::where('casino', $casino)
        ->orderBy('id_registroRRHH', 'desc')
        ->value('personal_final');

    return response()->json(['personal_inicio' => $valor]);
}


public function eliminarRRHH($id){
  $RRHH = RegistroRRHH::findOrFail($id);
  if(is_null($RRHH)) return 0;
  RegistroRRHH::destroy($id);
  return 1;
}


public function archivosRRHH($id)
{
    $RRHH = RegistroRRHH::with('archivos')->findOrFail($id);

    $files = $RRHH->archivos->map(function($a){
        return [
            'id'     => $a->id_registro_archivo,
            'nombre' => basename($a->path),
            'url'    => \Illuminate\Support\Facades\Storage::url($a->path),
            'fecha'  => $a->fecha_toma,
        ];
    });

    return response()->json($files->values());
}


public function llenarRRHHEdit($id)
{
    $r = RegistroRRHH::findOrFail($id);

    return response()->json([
        'fecha'                          => $r->fecha_RRHH,
        'casino'                         => $r->casino,
        'personal_inicio'                => $r->personal_inicio,
        'personal_final'                 => $r->personal_final,
        'altas_mes'                      => $r->altas_mes,
        'bajas'                          => $r->bajas,
        'personal_nomina'                => $r->personal_nomina,
        'diferencia'                     => $r->diferencia,
        'tercerizados'                   => $r->tercerizados,
        'total_personal'                 => $r->total_personal,
        'ofertado_adjudicado'            => $r->ofertado_adjudicado,
        'ludico'                         => $r->ludico,
        'no_ludico'                      => $r->no_ludico,
        'total_tipo'                     => $r->total_tipo,
        'porcentaje_ludico'              => $r->porcentaje_ludico,
        'porcentaje_no_ludico'           => $r->porcentaje_no_ludico,
        'porcentaje_total'               => $r->porcentaje_total,
        'porcentaje_ludico_viviendo'     => $r->porcentaje_ludico_viviendo,
        'porcentaje_no_ludico_viviendo'  => $r->porcentaje_no_ludico_viviendo,
        'porcentaje_total_viviendo'      => $r->porcentaje_total_viviendo,
        'diferencia_nomina_ddjj'         => $r->diferencia_nomina_ddjj,
        'ludico_viviendo'                => $r->ludico_viviendo,
        'no_ludico_viviendo'             => $r->no_ludico_viviendo,
        'total_ludico_viviendo'          => $r->total_viviendo,
    ]);
}
public function actualizarRRHH(Request $request, $id)
{
    $r = RegistroRRHH::findOrFail($id);

    $r->fecha_RRHH                    = $request->input('fecha_RRHH').'-01';
    $r->personal_inicio               = $request->input('personal_inicio_RRHH');
    $r->personal_final                = $request->input('personal_final_RRHH');
    $r->altas_mes                     = $request->input('altas_RRHH');
    $r->bajas                         = $request->input('bajas_RRHH');
    $r->personal_nomina               = $request->input('personal_nomina_RRHH');
    $r->diferencia                    = $request->input('diferencia_RRHH');
    $r->tercerizados                  = $request->input('tercerizados_RRHH');
    $r->total_personal                = $request->input('total_personal_RRHH');
    $r->ofertado_adjudicado           = $request->input('ofertado_adjudicado_RRHH');
    $r->ludico                        = $request->input('ludicos_RRHH');
    $r->no_ludico                     = $request->input('no_ludicos_RRHH');
    $r->total_tipo                    = $request->input('total_ludicos_RRHH');
    $r->porcentaje_ludico             = $request->input('porcentaje_ludicos_RRHH');
    $r->porcentaje_no_ludico          = $request->input('porcentaje_no_ludicos_RRHH');
    $r->porcentaje_total              = $request->input('total_porcentaje_ludicos_RRHH');
    $r->porcentaje_ludico_viviendo    = $request->input('porcentaje_ludicos_sf_RRHH');
    $r->porcentaje_no_ludico_viviendo = $request->input('porcentaje_no_ludicos_sf_RRHH');
    $r->porcentaje_total_viviendo     = $request->input('porcentaje_total_sf_RRHH');
    $r->diferencia_nomina_ddjj        = $request->input('dif_nomina_RRHH');
    $r->casino                        = $request->input('casinoRRHH');
    $r->ludico_viviendo = $request->ludicos_vivivendo_RRHH;
    $r->no_ludico_viviendo = $request->no_ludicos_viviendo_RRHH;
    $r->total_viviendo = $request->total_ludicos_viviendo_RRHH;
    $r->save();

    $saved = 0;
    $files = Arr::wrap($request->file('uploadRRHH'));
    foreach ($files as $file) {
        if (!($file instanceof UploadedFile) || !$file->isValid()) continue;

        $base = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
        $ext  = $file->getClientOriginalExtension();
        $safe = preg_replace('/\s+/', '_', $base);
        $name = time().'_'.Str::random(6).'_'.$safe.($ext ? '.'.$ext : '');

        $file->storeAs('public/RegistroRRHH', $name);

        $r->archivos()->create([
            'path'       => $name,
            'usuario'    => UsuarioController::getInstancia()->quienSoy()['usuario']['id_usuario'],
            'fecha_toma' => date('Y-m-d H:i:s'),
        ]);

        $saved++;
    }

    return response()->json(['success' => true, 'saved_files' => $saved]);
}


public function llenarRRHH($id){
  $RRHH = RegistroRRHH::with('casinoRRHH')->findOrFail($id);
  if(is_null($RRHH)) return 0;

  return response()->json([

    'fecha' => $RRHH->fecha_RRHH,
    'casino' => $RRHH->casinoRRHH ? $RRHH->casinoRRHH->nombre : '-',
    'personal_inicio' => $RRHH->personal_inicio,
    'altas_mes' => $RRHH->altas_mes,
    'bajas' => $RRHH->bajas,
    'personal_final' => $RRHH->personal_final,
    'personal_nomina' => $RRHH->personal_nomina,
    'diferencia' => $RRHH->diferencia,
    'tercerizados' => $RRHH->tercerizados,
    'total_personal' => $RRHH->total_personal,
    'ofertado_adjudicado' => $RRHH->ofertado_adjudicado,
    'ludico' => $RRHH->ludico,
    'no_ludico' =>$RRHH->no_ludico,
    'total_tipo' => $RRHH->total_tipo,
    'porcentaje_ludico' => $RRHH->porcentaje_ludico,
    'porcentaje_no_ludico' => $RRHH->porcentaje_no_ludico,
    'porcentaje_total' => $RRHH->porcentaje_total,
    'porcentaje_ludico_viviendo' => $RRHH->porcentaje_ludico_viviendo,
    'porcentaje_no_ludico_viviendo' => $RRHH->porcentaje_no_ludico_viviendo,
    'porcentaje_total_viviendo' => $RRHH->porcentaje_total_viviendo,
    'ludico_viviendo' => $RRHH->ludico_viviendo,
    'no_ludico_viviendo' => $RRHH->no_ludico_viviendo,
    'total_viviendo' => $RRHH->total_viviendo,
    'diferencia_nomina' => $RRHH->diferencia_nomina_ddjj,

  ]);

}

public function descargarRRHHCsv(Request $request)
{
    $casinoId = $request->query('casino');
    $desde    = $request->query('desde');
    $hasta    = $request->query('hasta');
    $user = Usuario::find(session('id_usuario'));
    $allowedCasinoIds = $user->casinos->pluck('id_casino')->toArray();

    $registros = RegistroRRHH::with('casinoRRHH')
        ->whereIn('casino',$allowedCasinoIds)
        ->when($casinoId && $casinoId != 4, function ($q) use ($casinoId) {
            $q->where('casino', $casinoId);
        })
        ->when($desde, function ($q) use ($desde) {
            $q->where('fecha_RRHH', '>=', $desde . '-01');
        })
        ->when($hasta, function ($q) use ($hasta) {
            $q->where('fecha_RRHH', '<=', $hasta . '-01');
        })
        ->orderBy('casino')
        ->orderBy('fecha_RRHH')
        ->get();

    $csv = [];
    $csv[] = ['Casino', 'Año', 'Mes', 'Personal al Inicio', 'Altas del Mes', 'Bajas del Mes', 'Personal al Final', 'Personal según la Nómina', 'Diferencia', 'Tercerizados', 'Total', 'Ofertado/Adjudicado',
              'Personal Ludico','Personal NO Ludico','Total Personal', 'Porcentaje de Personal Ludico', 'Porcentaje de Personal NO Ludico','Porcentaje Total de Personal','Personal Ludico Domiciliado en Santa Fe','Personal NO Ludico Domiciliado en Santa Fe'
               ,'Total de Personal Domiciliado en Santa Fe','Porcentaje de Personal Ludico Domiciliado en Santa Fe', 'Porcentaje de Personal NO Ludico Domiciliado en Santa Fe','Porcentaje Total de Personal Domiciliado en Santa Fe', 'Diferencia entre Nómina y DDJJ por tipo de personal'];

    foreach ($registros as $r) {
        setlocale(LC_TIME, 'es_ES.UTF-8');
        $anio     = date('Y', strtotime($r->fecha_RRHH));
        $mes      = strftime('%B', strtotime($r->fecha_RRHH));
        $casino   = $r->casinoRRHH ? $r->casinoRRHH->nombre : '-';
        $personal_inicio = $r->personal_inicio;
        $altas_mes = $r->altas_mes;
        $bajas = $r->bajas;
        $personal_final = $r->personal_final;
        $personal_nomina = $r->personal_nomina;
        $diferencia = $r->diferencia;
        $tercerizados = $r->tercerizados;
        $total_personal = $r->total_personal;
        $ofertado = $r->ofertado_adjudicado;
        $ludico = $r->ludico;
        $no_ludico = $r->no_ludico;
        $total_tipo = $r->total_tipo;
        $porcentaje_ludico = number_format($r->porcentaje_ludico, 2, '.', '');
        $porcentaje_no_ludico = number_format($r->porcentaje_no_ludico, 2, '.', '');
        $porcentaje_total = number_format($r->porcentaje_total, 2, '.', '');
        $ludico_viviendo = $r->ludico_viviendo;
        $no_ludico_viviendo = $r->no_ludico_viviendo;
        $total_viviendo = $r->total_viviendo;
        $porcentaje_ludico_viviendo = number_format($r->porcentaje_ludico_viviendo, 2, '.', '');
        $porcentaje_no_ludico_viviendo = number_format($r->porcentaje_no_ludico_viviendo, 2, '.', '');
        $porcentaje_total_viviendo = number_format($r->porcentaje_total_viviendo, 2, '.', '');

        $diferencia_nomina = $r->diferencia_nomina_ddjj;


        $csv[] = [
            $casino,
            $anio,
            ucfirst($mes),
            $personal_inicio,
            $altas_mes,
            $bajas,
            $personal_final,
            $personal_nomina,
            $diferencia,
            $tercerizados,
            $total_personal,
            $ofertado,
            $ludico,
            $no_ludico,
            $total_tipo,
            $porcentaje_ludico,
            $porcentaje_no_ludico,
            $porcentaje_total,
            $ludico_viviendo,
            $no_ludico_viviendo,
            $total_viviendo,
            $porcentaje_ludico_viviendo,
            $porcentaje_no_ludico_viviendo,
            $porcentaje_total_viviendo,
            $diferencia_nomina,

        ];
    }

    $nombreCasino = $casinoId == 4 ? 'todos' : Casino::find($casinoId)->nombre ?? 'desconocido';
    $filename = "RRHH_{$nombreCasino}_" . date('Ymd_His') . ".csv";


    $handle = fopen('php://temp', 'r+');
    foreach ($csv as $linea) {
        fputcsv($handle, $linea, ',');
    }
    rewind($handle);
    $contenido = stream_get_contents($handle);
    fclose($handle);

    return response($contenido, 200, [
        'Content-Type'        => 'text/csv; charset=UTF-8',
        'Content-Disposition' => "attachment; filename=\"$filename\"",
        'Cache-Control'       => 'no-store, no-cache'
    ]);
}

public function descargarRRHHXlsx(Request $request)
{
    $casinoId = $request->query('casino');
    $desde = $request->query('desde');
    $hasta = $request->query('hasta');

    $casino = Casino::findOrFail($casinoId);

    $query = RegistroRRHH::select([
        DB::raw("YEAR(fecha_RRHH) AS anio"),
        DB::raw("MONTHNAME(fecha_RRHH) AS Mes"),
        DB::raw("personal_inicio"),
        DB::raw("altas_mes"),
        DB::raw("bajas"),
        DB::raw("personal_final"),
        DB::raw("personal_nomina"),
        DB::raw("diferencia"),
        DB::raw("tercerizados"),
        DB::raw("total_personal"),
        DB::raw("ofertado_adjudicado"),
        DB::raw("ludico"),
        DB::raw("no_ludico"),
        DB::raw("total_tipo"),
        DB::raw("porcentaje_ludico"),
        DB::raw("porcentaje_no_ludico"),
        DB::raw("porcentaje_total"),
        DB::raw("ludico_viviendo"),
        DB::raw("no_ludico_viviendo"),
        DB::raw("total_viviendo"),
        DB::raw("porcentaje_ludico_viviendo"),
        DB::raw("porcentaje_no_ludico_viviendo"),
        DB::raw("porcentaje_total_viviendo"),
        DB::raw("diferencia_nomina_ddjj"),

    ])
    ->where('casino', $casinoId);

    if ($desde) {
        $query->where('fecha_RRHH', '>=', $desde . '-01');
    }

    if ($hasta) {
        $query->where('fecha_RRHH', '<=', $hasta . '-31');
    }

    $datos = $query->orderBy('fecha_RRHH')->get()->groupBy('anio');



    $filename = "registro_RRHH_" . str_replace(' ', '_', strtolower($casino->nombre));

    return Excel::create($filename, function($excel) use ($datos, $casino, $casinoId) {
        $excel->sheet('RRHH', function($sheet) use ($datos, $casino, $casinoId) {
            $fila = 1;


            $sheet->row($fila, [
                'Mes','',
                'Personal Al Inicio',
                'Altas del Mes',
                'Bajas del Mes',
                'Personal al Final',
                'Personal según la Nómina',
                'Diferencia', '',
                'Tercerizados' ,'Total','Ofertado/ Adjudicado',
                ' ', 'Tipo de Personal','Cantidad Ocupada', 'Porcentaje de Personal', 'Cantidad Ocupada Viviendo en Santa Fe' ,'Porcentaje de Personal Domiciliado en Santa Fe', 'Diferencia entre Nómina y DDJJ por tipo de Personal',
            ]);
            $sheet->cells("A1:A1", function($cells) use ($casinoId) {
                switch ($casinoId) {
                case 1:
                    $color = '#339966';

                    break;
                case 2:
                    $color = '#ff0000';

                    break;
                case 3:
                    $color = '#ffcc00';

                    break;
                default:
                    $color = '#222222';

            }

                $cells->setBackground($color);
                $cells->setFontColor('#000000');
                $cells->setFontWeight('bold');
                $cells->setAlignment('center');
            });
            $sheet->cells("C1:H1", function($cells) use ($casinoId) {
                switch ($casinoId) {
                case 1:
                    $color = '#339966';

                    break;
                case 2:
                    $color = '#ff0000';

                    break;
                case 3:
                    $color = '#ffcc00';

                    break;
                default:
                    $color = '#222222';

            }

                $cells->setBackground($color);
                $cells->setFontColor('#000000');
                $cells->setFontWeight('bold');
                $cells->setAlignment('center');
            });
            $sheet->cells("J1:L1", function($cells) use ($casinoId) {
                switch ($casinoId) {
                case 1:
                    $color = '#339966';

                    break;
                case 2:
                    $color = '#ff0000';

                    break;
                case 3:
                    $color = '#ffcc00';

                    break;
                default:
                    $color = '#222222';

            }

                $cells->setBackground($color);
                $cells->setFontColor('#000000');
                $cells->setFontWeight('bold');
                $cells->setAlignment('center');
            });
            $sheet->cells("N1:S1", function($cells) use ($casinoId) {
                switch ($casinoId) {
                case 1:
                    $color = '#339966';

                    break;
                case 2:
                    $color = '#ff0000';

                    break;
                case 3:
                    $color = '#ffcc00';

                    break;
                default:
                    $color = '#222222';

            }

                $cells->setBackground($color);
                $cells->setFontColor('#000000');
                $cells->setFontWeight('bold');
                $cells->setAlignment('center');
            });

            $sheet->getStyle("A1:S1")->getAlignment()->setWrapText(true)->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
            $sheet->setHeight(1, 50);
            $sheet->cells("A1:S999", function($cells){
                $cells->setFontFamily('Arial');
                $cells->setFontSize(10);
            });

            $fila++;
            foreach ($datos as $anio => $registros) {

                $sheet->mergeCells("A{$fila}:S{$fila}");
                $sheet->setCellValue("A{$fila}", $anio);
                $sheet->cells("A{$fila}:S{$fila}", function($cells){
                    $cells->setBackground('#CCCCCC');
                    $cells->setFontWeight('bold');
                    $cells->setFontSize(13);
                    $cells->setAlignment('center');
                });
                $sheet->getStyle("A{$fila}:R{$fila}")->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
                $sheet->setHeight($fila, 20);
                $fila++;

                foreach ($registros as $r) {
                  $filaaux=$fila+2;
                  $sheet->mergeCells("A{$fila}:A{$filaaux}");
                  $sheet->mergeCells("C{$fila}:C{$filaaux}");
                  $sheet->mergeCells("D{$fila}:D{$filaaux}");
                  $sheet->mergeCells("E{$fila}:E{$filaaux}");
                  $sheet->mergeCells("F{$fila}:F{$filaaux}");
                  $sheet->mergeCells("G{$fila}:G{$filaaux}");
                  $sheet->mergeCells("H{$fila}:H{$filaaux}");
                  $sheet->mergeCells("J{$fila}:J{$filaaux}");
                  $sheet->mergeCells("K{$fila}:K{$filaaux}");
                  $sheet->mergeCells("L{$fila}:L{$filaaux}");
                  $sheet->mergeCells("S{$fila}:S{$filaaux}");

                    setlocale(LC_TIME, 'es_ES.UTF-8');
                    $mesEsp = ucfirst(strftime('%B', strtotime($r->{'Mes'} . ' 1')));

                    $sheet->row($fila, [
                        $mesEsp,'',
                        $r->personal_inicio,
                        $r->altas_mes,
                        $r->bajas,
                        $r->personal_final,
                        $r->personal_nomina,

                        $r->diferencia,'',
                        $r->tercerizados,
                        $r->total_personal,
                        $r->ofertado_adjudicado,'',
                        'Ludico',
                        $r->ludico,
                        $r->porcentaje_ludico." %",
                        $r->ludico_viviendo,
                        $r->porcentaje_ludico_viviendo. " %",
                        $r->diferencia_nomina_ddjj,

                    ]);
                    $sheet->row($fila+1,[
                      '','','','','','','','','','','','','',
                      'No Ludico',
                      $r->no_ludico,
                      $r->porcentaje_no_ludico. ' %',
                      $r->no_ludico_viviendo,
                      $r->porcentaje_no_ludico_viviendo. ' %',
                    ]);
                    $sheet->row($fila+2,[
                      '','','','','','','','','','','','','',
                      'Total ',
                      $r->total_tipo,
                      $r->porcentaje_total. ' %',
                      $r->total_viviendo,
                      $r->porcentaje_total_viviendo. ' %',
                    ]);
                    $sheet->cells("A{$fila}", function($cells){
                        $cells->setBackground('#FFFF99');
                        $cells->setFontWeight('bold');
                        $cells->setAlignment('left');
                    });

                    $sheet->cells("B{$fila}:S{$filaaux}", function($cells){
                        $cells->setAlignment('center');
                    });

                    $sheet->getStyle("A{$fila}:S{$filaaux}")->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
                    $fila+=3;
                }

            }


            $sheet->getStyle("A1:S1")->applyFromArray([
                'borders' => [
                    'allborders' => [
                        'style' => PHPExcel_Style_Border::BORDER_THICK,
                        'color' => ['argb' => 'FF000000']
                    ]
                ]
            ]);

            $sheet->getStyle("A2:S" . ($fila -1))->applyFromArray([
                'borders' => [
                    'allborders' => [
                        'style' => PHPExcel_Style_Border::BORDER_THIN,
                        'color' => ['argb' => 'FF000000']
                    ]
                ]
            ]);


            $anchos = [
                9, 1, 9, 9, 9, 9, 9, 9,
                1, 9, 9, 9, 1, 9, 9, 9, 9,
                12,20,20,20,20,
            ];
            foreach (range('A', 'S') as $i => $col) {
                $sheet->setWidth($col, $anchos[$i]);
            }

            $sheet->setFreeze('A2');
        });
    })->export('xlsx');
}

public function descargarRRHHXlsxTodos(Request $request)
{
    $desde = $request->query('desde');
    $hasta = $request->query('hasta');

    $user = Usuario::find(session('id_usuario'));
    $casinos = $user->casinos->pluck('nombre', 'id_casino');

    return Excel::create('registro_RRHH_todos', function($excel) use ($casinos, $desde, $hasta) {
        foreach ($casinos as $casinoId => $casinoNombre) {

            $datos = RegistroRRHH::select([
              DB::raw("YEAR(fecha_RRHH) AS anio"),
              DB::raw("MONTHNAME(fecha_RRHH) AS Mes"),
              DB::raw("personal_inicio"),
              DB::raw("altas_mes"),
              DB::raw("bajas"),
              DB::raw("personal_final"),
              DB::raw("personal_nomina"),
              DB::raw("diferencia"),
              DB::raw("tercerizados"),
              DB::raw("total_personal"),
              DB::raw("ofertado_adjudicado"),
              DB::raw("ludico"),
              DB::raw("no_ludico"),
              DB::raw("total_tipo"),
              DB::raw("porcentaje_ludico"),
              DB::raw("porcentaje_no_ludico"),
              DB::raw("porcentaje_total"),
              DB::raw("porcentaje_ludico_viviendo"),
              DB::raw("porcentaje_no_ludico_viviendo"),
              DB::raw("porcentaje_total_viviendo"),
              DB::raw("ludico_viviendo"),
              DB::raw("no_ludico_viviendo"),
              DB::raw("total_viviendo"),
              DB::raw("diferencia_nomina_ddjj"),
            ])
            ->where('casino', $casinoId)
            ->when($desde, function($q) use ($desde) {
                return $q->where('fecha_RRHH', '>=', $desde . '-01');
            })
            ->when($hasta, function($q) use ($hasta) {
                return $q->where('fecha_RRHH', '<=', $hasta . '-31');
            })
            ->orderBy('fecha_RRHH')
            ->get()
            ->groupBy('anio');



            $excel->sheet($casinoNombre, function($sheet) use ( $datos, $casinoId) {
                $fila = 1;

                $sheet->row($fila, [
                    'Mes','',
                    'Personal Al Inicio',
                    'Altas del Mes',
                    'Bajas del Mes',
                    'Personal al Final',
                    'Personal según la Nómina',
                    'Diferencia', '',
                    'Tercerizados' ,'Total','Ofertado/ Adjudicado',
                    ' ', 'Tipo de Personal','Cantidad Ocupada', 'Porcentaje de Personal', 'Cantidad Ocupada Viviendo en Santa Fe' ,'Porcentaje de Personal Domiciliado en Santa Fe', 'Diferencia entre Nómina y DDJJ por tipo de Personal',
                ]);


                $sheet->cells("A1:A1", function($cells) use ($casinoId) {
                    switch ($casinoId) {
                    case 1:
                        $color = '#339966';

                        break;
                    case 2:
                        $color = '#ff0000';

                        break;
                    case 3:
                        $color = '#ffcc00';

                        break;
                    default:
                        $color = '#222222';

                }

                    $cells->setBackground($color);
                    $cells->setFontColor('#000000');
                    $cells->setFontWeight('bold');
                    $cells->setAlignment('center');
                });
                $sheet->cells("C1:H1", function($cells) use ($casinoId) {
                    switch ($casinoId) {
                    case 1:
                        $color = '#339966';

                        break;
                    case 2:
                        $color = '#ff0000';

                        break;
                    case 3:
                        $color = '#ffcc00';

                        break;
                    default:
                        $color = '#222222';

                }

                    $cells->setBackground($color);
                    $cells->setFontColor('#000000');
                    $cells->setFontWeight('bold');
                    $cells->setAlignment('center');
                });
                $sheet->cells("J1:L1", function($cells) use ($casinoId) {
                    switch ($casinoId) {
                    case 1:
                        $color = '#339966';

                        break;
                    case 2:
                        $color = '#ff0000';

                        break;
                    case 3:
                        $color = '#ffcc00';

                        break;
                    default:
                        $color = '#222222';

                }

                    $cells->setBackground($color);
                    $cells->setFontColor('#000000');
                    $cells->setFontWeight('bold');
                    $cells->setAlignment('center');
                });
                $sheet->cells("N1:S1", function($cells) use ($casinoId) {
                    switch ($casinoId) {
                    case 1:
                        $color = '#339966';

                        break;
                    case 2:
                        $color = '#ff0000';

                        break;
                    case 3:
                        $color = '#ffcc00';

                        break;
                    default:
                        $color = '#222222';

                }
                    $cells->setBackground($color);
                    $cells->setFontColor('#000000');
                    $cells->setFontWeight('bold');
                    $cells->setAlignment('center');
                });

                $sheet->getStyle("A1:S1")->getAlignment()
                    ->setWrapText(true)
                    ->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
                $sheet->setHeight(1, 50);


                $fila++;


                $sheet->cells("A1:S999", function($cells) {
                    $cells->setFontFamily('Arial');
                    $cells->setFontSize(10);
                });
                foreach ($datos as $anio => $registros) {

                    $sheet->mergeCells("A{$fila}:S{$fila}");
                    $sheet->setCellValue("A{$fila}", $anio);
                    $sheet->cells("A{$fila}:S{$fila}", function($cells){
                        $cells->setBackground('#CCCCCC');
                        $cells->setFontWeight('bold');
                        $cells->setFontSize(13);
                        $cells->setAlignment('center');
                    });
                    $sheet->getStyle("A{$fila}:R{$fila}")->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
                    $sheet->setHeight($fila, 20);
                    $fila++;

                foreach ($registros as $r) {
                  $filaaux=$fila+2;
                  $sheet->mergeCells("A{$fila}:A{$filaaux}");
                  $sheet->mergeCells("C{$fila}:C{$filaaux}");
                  $sheet->mergeCells("D{$fila}:D{$filaaux}");
                  $sheet->mergeCells("E{$fila}:E{$filaaux}");
                  $sheet->mergeCells("F{$fila}:F{$filaaux}");
                  $sheet->mergeCells("G{$fila}:G{$filaaux}");
                  $sheet->mergeCells("H{$fila}:H{$filaaux}");
                  $sheet->mergeCells("J{$fila}:J{$filaaux}");
                  $sheet->mergeCells("K{$fila}:K{$filaaux}");
                  $sheet->mergeCells("L{$fila}:L{$filaaux}");
                  $sheet->mergeCells("S{$fila}:S{$filaaux}");

                    setlocale(LC_TIME, 'es_ES.UTF-8');
                    $mesEsp = ucfirst(strftime('%B', strtotime($r->{'Mes'} . ' 1')));

                    $sheet->row($fila, [
                        $mesEsp,'',
                        $r->personal_inicio,
                        $r->altas_mes,
                        $r->bajas,
                        $r->personal_final,
                        $r->personal_nomina,

                        $r->diferencia,'',
                        $r->tercerizados,
                        $r->total_personal,
                        $r->ofertado_adjudicado,'',
                        'Ludico',
                        $r->ludico,
                        $r->porcentaje_ludico." %",
                        $r->ludico_viviendo,
                        $r->porcentaje_ludico_viviendo. " %",
                        $r->diferencia_nomina_ddjj,

                    ]);
                    $sheet->row($fila+1,[
                      '','','','','','','','','','','','','',
                      'No Ludico',
                      $r->no_ludico,
                      $r->porcentaje_no_ludico. ' %',
                      $r->no_ludico_viviendo,
                      $r->porcentaje_no_ludico_viviendo. ' %',
                    ]);
                    $sheet->row($fila+2,[
                      '','','','','','','','','','','','','',
                      'Total ',
                      $r->total_tipo,
                      $r->porcentaje_total. ' %',
                      $r->total_viviendo,
                      $r->porcentaje_total_viviendo. ' %',
                    ]);
                    $sheet->cells("A{$fila}", function($cells){
                        $cells->setBackground('#FFFF99');
                        $cells->setFontWeight('bold');
                        $cells->setAlignment('left');
                    });

                    $sheet->cells("B{$fila}:S{$filaaux}", function($cells){
                        $cells->setAlignment('center');
                    });

                    $sheet->getStyle("A{$fila}:S{$filaaux}")->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
                    $fila+=3;
                }

              }

                $sheet->getStyle("A1:S1")->applyFromArray([
                    'borders' => [
                        'allborders' => [
                            'style' => PHPExcel_Style_Border::BORDER_THICK,
                            'color' => ['argb' => 'FF000000']
                        ]
                    ]
                ]);
                $sheet->getStyle("A2:S" . ($fila - 1))->applyFromArray([
                    'borders' => [
                        'allborders' => [
                            'style' => PHPExcel_Style_Border::BORDER_THIN,
                            'color' => ['argb' => 'FF000000']
                        ]
                    ]
                ]);


                $anchos = [9, 1, 9, 9, 9, 9, 9, 9,
                1, 9, 9, 9, 1, 9, 9, 9, 9,
                12,20,20,20,20];
                foreach (range('A', 'S') as $i => $col) {
                    $sheet->setWidth($col, $anchos[$i]);
                }

                $sheet->setFreeze('A2');
            });
        }
    })->export('xlsx');
}

//GANANCIAS


public function archivosGanancias_periodo($id)
{
    $Ganancias_periodo = RegistroGanancias_periodo::with('archivos')->findOrFail($id);

    $files = $Ganancias_periodo->archivos->map(function($a){
        return [
            'id'     => $a->id_registro_archivo,
            'nombre' => basename($a->path),
            'url'    => \Illuminate\Support\Facades\Storage::url($a->path),
            'fecha'  => $a->fecha_toma,
        ];
    });

    return response()->json($files->values());
}

public function llenarGanancias_periodoEdit($id){
    $g = RegistroGanancias_periodo::findOrFail($id);

    $anio = $g->periodo_fiscal;
    if ($anio instanceof \Carbon\Carbon) {
        $anio = $anio->format('Y');
    } else {
        $anio = substr((string)$anio, 0, 4);
    }

    $fp = $g->fecha_presentacion;
    $fecha_pres = is_string($fp) ? substr($fp,0,10) : ($fp ? $fp->format('Y-m-d') : null);

    return response()->json([
        'periodo'     => $anio,
        'fecha_pres'  => $fecha_pres,
        'casino'      => $g->casino,
        'saldo'       => $g->saldo,
        'forma_pago'  => $g->forma_pago,
        'obs'         => $g->observaciones,
    ]);
}


public function archivosGanancias($id)
{
    $Ganancias = RegistroGanancias::with('archivos')->findOrFail($id);

    $files = $Ganancias->archivos->map(function($a){
        return [
            'id'     => $a->id_registro_archivo,
            'nombre' => basename($a->path),
            'url'    => \Illuminate\Support\Facades\Storage::url($a->path),
            'fecha'  => $a->fecha_toma,
        ];
    });

    return response()->json($files->values());
}


public function llenarGananciasEdit($id){
    $g = RegistroGanancias::findOrFail($id);

    $pf  = $g->periodo_fiscal;
    $fp  = $g->fecha_pago;


    $fecha_pago = is_string($fp) ? substr($fp,0,10) : ($fp ? $fp->format('Y-m-d') : null);

    return response()->json([
        'fecha'         => $pf,
        'fecha_pago'    => $fecha_pago,
        'casino'        => $g->casino,
        'nro_anticipo'  => $g->nro_anticipo,
        'anticipo'      => $g->anticipo,
        'abonado'       => $g->abonado,
        'diferencia'    => $g->diferencia,
        'computa'       => $g->computa_contra,
        'obs'           => $g->observaciones,
    ]);
}

public function actualizarGanancias(Request $request, $id)
{
    $r = RegistroGanancias::findOrFail($id);

    $r->periodo_fiscal     = $request->input('fecha_GananciasPres');
    $r->nro_anticipo       = $request->input('nro_anticipo_Ganancias');
    $r->anticipo           = $request->input('anticipo_Ganancias');
    $r->abonado            = $request->input('abonado_Ganancias');
    $r->computa_contra     = $request->input('computa_Ganancias');
    $r->diferencia         = $request->input('diferencia_Ganancias');
    $r->fecha_pago         = $request->input('fecha_pago_Ganancias');
    $r->casino             = $request->input('casinoGanancias');
    $r->observaciones      = $request->input('obs_Ganancias');
    $r->save();

    $saved = 0;
    foreach (Arr::wrap($request->file('uploadGanancias')) as $file) {
        if (!($file instanceof UploadedFile) || !$file->isValid()) continue;
        $base = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
        $ext  = $file->getClientOriginalExtension();
        $safe = preg_replace('/\s+/', '_', $base);
        $name = time().'_'.Str::random(6).'_'.$safe.($ext ? '.'.$ext : '');
        $file->storeAs('public/RegistroGanancias', $name);
        $r->archivos()->create([
            'path'       => $name,
            'usuario'    => UsuarioController::getInstancia()->quienSoy()['usuario']['id_usuario'],
            'fecha_toma' => date('Y-m-d H:i:s'),
        ]);
        $saved++;
    }

    return response()->json(['success' => true, 'saved_files' => $saved]);
}


public function guardarGanancias(Request $request){

      $path = null;


      try {
          DB::beginTransaction();

          $Ganancias = new RegistroGanancias();




          $Ganancias->periodo_fiscal = $request->fecha_GananciasPres;
          $Ganancias->nro_anticipo = $request->nro_anticipo_Ganancias;
          $Ganancias->anticipo  =$request->anticipo_Ganancias;
          $Ganancias->abonado = $request->abonado_Ganancias;
          $Ganancias->diferencia = $request->diferencia_Ganancias;
          $Ganancias->computa_contra = $request->computa_Ganancias;
          $Ganancias->fecha_pago = $request->fecha_pago_Ganancias;

          $Ganancias->observaciones = $request->obs_Ganancias;

          $Ganancias->casino = $request->casinoGanancias;
          $Ganancias->fecha_toma = date('Y-m-d h:i:s', time());
          $Ganancias->usuario = UsuarioController::getInstancia()->quienSoy()['usuario']['id_usuario'];
          $Ganancias->save();
          $files = Arr::wrap($request->file('uploadGanancias'));
                    foreach ($files as $file) {
                        if (!($file instanceof UploadedFile) || !$file->isValid()) continue;

                        $base = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
                        $ext  = $file->getClientOriginalExtension();
                        $safe = preg_replace('/\s+/', '_', $base);
                        $filename = time().'_'.Str::random(6).'_'.$safe.($ext?'.'.$ext:'');

                        $file->storeAs('public/RegistroGanancias', $filename);

                        $Ganancias->archivos()->create([
                            'path'       => $filename,
                            'usuario'    => $Ganancias->usuario,
                            'fecha_toma' => date('Y-m-d H:i:s'),
                        ]);
                    }
          DB::commit();

          return response()->json([
            'success' => true,
             'id' => $Ganancias->id_registroGanancias,
             'Ganancias'  => $request->all(),
             'path'    => $path,
             'url'     => Storage::url($path)
           ]);
      } catch (\Exception $e) {
          DB::rollBack();
          return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
      }

}

public function guardarGanancias_periodo(Request $request){



      try {
          DB::beginTransaction();

          $Ganancias_periodo = new RegistroGanancias_periodo();


          $Ganancias_periodo->periodo_fiscal = $request->fecha_Ganancias_periodoPres;
          $Ganancias_periodo->saldo  = $request->saldo_Ganancias_periodo;
          $Ganancias_periodo->fecha_presentacion = $request->fecha_pres_Ganancias_periodo;
          $Ganancias_periodo->forma_pago = $request->forma_pago_Ganancias_periodo;
          $Ganancias_periodo->observaciones = $request->obs_Ganancias_periodo;

          $Ganancias_periodo->casino = $request->casinoGanancias_periodo;
          $Ganancias_periodo->fecha_toma = date('Y-m-d h:i:s', time());
          $Ganancias_periodo->usuario = UsuarioController::getInstancia()->quienSoy()['usuario']['id_usuario'];
          $Ganancias_periodo->save();
          $files = Arr::wrap($request->file('uploadGanancias_periodo'));
                    foreach ($files as $file) {
                        if (!($file instanceof UploadedFile) || !$file->isValid()) continue;

                        $base = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
                        $ext  = $file->getClientOriginalExtension();
                        $safe = preg_replace('/\s+/', '_', $base);
                        $filename = time().'_'.Str::random(6).'_'.$safe.($ext?'.'.$ext:'');

                        $file->storeAs('public/RegistroGanancias', $filename);

                        $Ganancias_periodo->archivos()->create([
                            'path'       => $filename,
                            'usuario'    => $Ganancias_periodo->usuario,
                            'fecha_toma' => date('Y-m-d H:i:s'),
                        ]);
                    }
          DB::commit();

          return response()->json([
            'success' => true,
             'id' => $Ganancias_periodo->id_registroGanancias_periodo,
             'Ganancias_periodo'  => $request->all(),
           ]);
      } catch (\Exception $e) {
          DB::rollBack();
          return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
      }

}

public function actualizarGanancias_periodo(Request $request, $id)
{
    $r = RegistroGanancias_periodo::findOrFail($id);

    $r->periodo_fiscal     = $request->input('fecha_Ganancias_periodoPres');
    $r->saldo              = $request->input('saldo_Ganancias_periodo');
    $r->fecha_presentacion = $request->input('fecha_pres_Ganancias_periodo');
    $r->forma_pago         = $request->input('forma_pago_Ganancias_periodo');
    $r->observaciones      = $request->input('obs_Ganancias_periodo');
    $r->casino             = $request->input('casinoGanancias_periodo');
    $r->save();

    $saved = 0;
    foreach (\Illuminate\Support\Arr::wrap($request->file('uploadGanancias_periodo')) as $file) {
        if (!($file instanceof \Illuminate\Http\UploadedFile) || !$file->isValid()) continue;
        $base = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
        $ext  = $file->getClientOriginalExtension();
        $safe = preg_replace('/\s+/', '_', $base);
        $name = time().'_'.\Illuminate\Support\Str::random(6).'_'.$safe.($ext ? '.'.$ext : '');
        $file->storeAs('public/RegistroGanancias', $name);
        $r->archivos()->create([
            'path'       => $name,
            'usuario'    => UsuarioController::getInstancia()->quienSoy()['usuario']['id_usuario'],
            'fecha_toma' => date('Y-m-d H:i:s'),
        ]);
        $saved++;
    }

    return response()->json(['success' => true, 'saved_files' => $saved]);
}


public function ultimasGanancias(Request $request)
{
    $page    = max(1, (int)$request->query('page', 1));
    $perPage = max(1, (int)$request->query('page_size', 20));

    $user = Usuario::find(session('id_usuario'));
    $allowedCasinoIds = $user->casinos->pluck('id_casino')->toArray();


    $query = RegistroGanancias::with('casinoGanancias')
              ->whereIn('casino', $allowedCasinoIds)
              ->withCount('archivos')
              ->orderBy('periodo_fiscal', 'desc')
              ->orderBy('nro_anticipo','desc')
              ->orderBy('casino', 'desc');


    if ($c = $request->query('id_casino')) {
      $query->where('casino', $c);
    }
    if ($desde = $request->query('desde')){
      $query->where('periodo_fiscal',">=",$desde);
    }
    if ($hasta = $request->query('hasta')){
      $query->where('periodo_fiscal',"<=",$hasta);
    }

    $total = $query->count();

    $registros = $query
        ->skip(($page - 1) * $perPage)
        ->take($perPage)
        ->get();

    $datos = $registros->map(function($r) {
        return [
            'id_registroGanancias' => $r->id_registroGanancias,
            'periodo'   => $r->periodo_fiscal,
            'anticipo' => $r->nro_anticipo,
            'casino'      => $r->casinoGanancias ? $r->casinoGanancias->nombre : '-',
	           'tiene_archivos' => $r->archivos_count>0,
            ];
    });

    return response()->json([
        'registros'  => $datos,
        'pagination' => [
            'current_page' => $page,
            'per_page'     => $perPage,
            'total'        => $total,
        ],
    ]);
}

public function ultimasGanancias_periodo(Request $request)
{
    $page    = max(1, (int)$request->query('page', 1));
    $perPage = max(1, (int)$request->query('page_size', 20));

    $user = Usuario::find(session('id_usuario'));
    $allowedCasinoIds = $user->casinos->pluck('id_casino')->toArray();


    $query = RegistroGanancias_periodo::with('casinoGanancias_periodo')
              ->whereIn('casino', $allowedCasinoIds)
              ->withCount('archivos')
              ->orderBy('periodo_fiscal', 'desc')
              ->orderBy('casino', 'desc');


    if ($c = $request->query('id_casino')) {
      $query->where('casino', $c);
    }
    if ($desde = $request->query('desde')){
      $query->where('periodo_fiscal',">=",$desde);
    }
    if ($hasta = $request->query('hasta')){
      $query->where('periodo_fiscal',"<=",$hasta);
    }

    $total = $query->count();

    $registros = $query
        ->skip(($page - 1) * $perPage)
        ->take($perPage)
        ->get();

    $datos = $registros->map(function($r) {
        return [
            'id_registroGanancias_periodo' => $r->id_registroGanancias_periodo,
            'periodo'   => $r->periodo_fiscal,
            'casino'      => $r->casinoGanancias_periodo ? $r->casinoGanancias_periodo->nombre : '-',
        	  'tiene_archivos' => $r->archivos_count>0,
        ];
    });

    return response()->json([
        'registros'  => $datos,
        'pagination' => [
            'current_page' => $page,
            'per_page'     => $perPage,
            'total'        => $total,
        ],
    ]);
}


public function eliminarGanancias($id){
  $Ganancias = RegistroGanancias::findOrFail($id);
  if(is_null($Ganancias)) return 0;
  RegistroGanancias::destroy($id);
  return 1;
}

public function eliminarGanancias_periodo($id){
  $Ganancias_periodo = RegistroGanancias_periodo::findOrFail($id);
  if(is_null($Ganancias_periodo)) return 0;
  RegistroGanancias_periodo::destroy($id);
  return 1;
}

public function llenarGanancias($id){
  $Ganancias = RegistroGanancias::with('casinoGanancias')->findOrFail($id);
  if(is_null($Ganancias)) return 0;

  return response()->json([

    'periodo' => $Ganancias->periodo_fiscal,
    'casino' => $Ganancias->casinoGanancias ? $Ganancias->casinoGanancias->nombre : '-',
    'nro_anticipo' => $Ganancias->nro_anticipo,
    'anticipo' => $Ganancias->anticipo,
    'abonado' => $Ganancias->abonado,
    'diferencia' => $Ganancias->diferencia,
    'computo' => $Ganancias->computa_contra,
    'fecha_pago' => $Ganancias->fecha_pago,
    'obs' => $Ganancias->observaciones,


  ]);

}

public function llenarGanancias_periodo($id){
  $Ganancias_periodo = RegistroGanancias_periodo::with('casinoGanancias_periodo')->findOrFail($id);
  if(is_null($Ganancias_periodo)) return 0;

  return response()->json([

    'periodo' => $Ganancias_periodo->periodo_fiscal,
    'casino' => $Ganancias_periodo->casinoGanancias_periodo ? $Ganancias_periodo->casinoGanancias_periodo->nombre : '-',
    'fecha_presentacion' => $Ganancias_periodo->fecha_presentacion,
    'forma_pago' => $Ganancias_periodo->forma_pago,
    'saldo' => $Ganancias_periodo->saldo,
    'obs' => $Ganancias_periodo->observaciones,


  ]);

}

public function descargarGananciasCsvAnticipos(Request $request)
{
    $casinoId = $request->query('casino');
    $desde    = $request->query('desde');
    $hasta    = $request->query('hasta');
    $user = Usuario::find(session('id_usuario'));
    $allowedCasinoIds = $user->casinos->pluck('id_casino')->toArray();

    $registros = RegistroGanancias::with('casinoGanancias')
        ->whereIn('casino',$allowedCasinoIds)
        ->when($casinoId && $casinoId != 4, function ($q) use ($casinoId) {
            $q->where('casino', $casinoId);
        })
        ->when($desde, function ($q) use ($desde) {
            $q->where('periodo_fiscal', '>=', $desde . '-01');
        })
        ->when($hasta, function ($q) use ($hasta) {
            $q->where('periodo_fiscal', '<=', $hasta . '-01');
        })
        ->orderBy('casino')
        ->orderBy('periodo_fiscal')
        ->orderBy('nro_anticipo')
        ->get();


    $csv = [];
    $csv[] = ['Casino', 'Período Fiscal', 'Anticipo', 'Abonado', 'Computa Contra Impuesto','Diferencia' ,'Fecha de Pago', 'Observación' ];

    foreach ($registros as $r) {
        setlocale(LC_TIME, 'es_ES.UTF-8');
        $anio     = $r->periodo_fiscal;
        $casino   = $r->casinoGanancias ? $r->casinoGanancias->nombre : '-';
        $anticipo = number_format($r->anticipo, 2, '.', '');
        $abonado = number_format($r->abonado, 2, '.', '');
        $computa = number_format($r->computa_contra, 2, '.', '');
        $diferencia = number_format($r->diferencia, 2, '.', '');
        $fecha_pago = $r->fecha_pago;
        $observacion = $r->observaciones;

        $csv[] = [
            $casino,
            $anio,
            $anticipo,
            $abonado,
            $computa,
            $diferencia,
            $fecha_pago,
            $observacion,

        ];
    }

    $nombreCasino = $casinoId == 4 ? 'todos' : Casino::find($casinoId)->nombre ?? 'desconocido';
    $filename = "Ganancias_{$nombreCasino}_" . date('Ymd_His') . ".csv";


    $handle = fopen('php://temp', 'r+');
    foreach ($csv as $linea) {
        fputcsv($handle, $linea, ',');
    }
    rewind($handle);
    $contenido = stream_get_contents($handle);
    fclose($handle);

    return response($contenido, 200, [
        'Content-Type'        => 'text/csv; charset=UTF-8',
        'Content-Disposition' => "attachment; filename=\"$filename\"",
        'Cache-Control'       => 'no-store, no-cache'
    ]);
}

public function descargarGananciasCsvPeriodos(Request $request)
{
    $casinoId = $request->query('casino');
    $desde    = $request->query('desde');
    $hasta    = $request->query('hasta');
    $user = Usuario::find(session('id_usuario'));
    $allowedCasinoIds = $user->casinos->pluck('id_casino')->toArray();

    $registros = RegistroGanancias_periodo::with('casinoGanancias_periodo')
        ->whereIn('casino',$allowedCasinoIds)
        ->when($casinoId && $casinoId != 4, function ($q) use ($casinoId) {
            $q->where('casino', $casinoId);
        })
        ->when($desde, function ($q) use ($desde) {
            $q->where('periodo_fiscal', '>=', $desde . '-01');
        })
        ->when($hasta, function ($q) use ($hasta) {
            $q->where('periodo_fiscal', '<=', $hasta . '-01');
        })
        ->orderBy('casino')
        ->orderBy('periodo_fiscal')
        ->get();


    $csv = [];
    $csv[] = ['Casino', 'Período Fiscal', 'Fecha Presentación', 'Impuesto a Pagar/ Saldo a Favor', 'Forma de Pago', 'Observación' ];

    foreach ($registros as $r) {
        setlocale(LC_TIME, 'es_ES.UTF-8');
        $anio     = $r->periodo_fiscal;
        $casino   = $r->casinoGanancias_periodo ? $r->casinoGanancias_periodo->nombre : '-';
        $saldo = number_format($r->saldo, 2, '.', '');
        $forma_pago = $r->forma_pago;
        $fecha_pres = $r->fecha_presentacion;
        $observacion = $r->observaciones;

        $csv[] = [
            $casino,
            $anio,
            $fecha_pres,
            $saldo,
            $forma_pago,
            $observacion,

        ];
    }

    $nombreCasino = $casinoId == 4 ? 'todos' : Casino::find($casinoId)->nombre ?? 'desconocido';
    $filename = "Ganancias_periodo_{$nombreCasino}_" . date('Ymd_His') . ".csv";


    $handle = fopen('php://temp', 'r+');
    foreach ($csv as $linea) {
        fputcsv($handle, $linea, ',');
    }
    rewind($handle);
    $contenido = stream_get_contents($handle);
    fclose($handle);

    return response($contenido, 200, [
        'Content-Type'        => 'text/csv; charset=UTF-8',
        'Content-Disposition' => "attachment; filename=\"$filename\"",
        'Cache-Control'       => 'no-store, no-cache'
    ]);
}

public function descargarGananciasXlsx(Request $request)
{
    $casinoId = $request->query('casino');
    $desde = $request->query('desde');
    $hasta = $request->query('hasta');

    $casino = Casino::findOrFail($casinoId);

    $query = RegistroGanancias::select([
        DB::raw("periodo_fiscal"),
        DB::raw("anticipo"),
        DB::raw("nro_anticipo"),
        DB::raw("abonado"),
        DB::raw("diferencia"),
        DB::raw("computa_contra"),
        DB::raw("fecha_pago"),
        DB::raw("observaciones"),
    ])
    ->where('casino', $casinoId);

    if ($desde) {
        $query->where('periodo_fiscal', '>=', $desde);
    }

    if ($hasta) {
        $query->where('periodo_fiscal', '<=', $hasta);
    }

    $datos = $query->orderBy('periodo_fiscal')->orderBy('nro_anticipo')->get()->groupBy('periodo_fiscal');

    $query1 = RegistroGanancias_periodo::select([
        DB::raw("periodo_fiscal"),
        DB::raw("fecha_presentacion"),
        DB::raw("saldo"),
        DB::raw("forma_pago"),
        DB::raw("observaciones"),
    ])
    ->where('casino', $casinoId);

    if ($desde) {
        $query1->where('periodo_fiscal', '>=', $desde);
    }

    if ($hasta) {
        $query1->where('periodo_fiscal', '<=', $hasta);
    }

    $datosPeriodo = $query1->orderBy('periodo_fiscal')->get()->groupBy('periodo_fiscal');



    $filename = "registro_Ganancias_" . str_replace(' ', '_', strtolower($casino->nombre));

    return Excel::create($filename, function($excel) use ($datos, $datosPeriodo, $casino, $casinoId) {
        $excel->sheet('Ganancias', function($sheet) use ($datos, $datosPeriodo, $casino, $casinoId ) {

          $sheet->mergeCells("A1:D1");
          $sheet->setCellValue("A1","FECHA DE CIERRE DE EJERCICIO 31/10 - PRESENTACION DE DDJJ MARZO DEL AÑO SIGUIENTE");

          $sheet->getStyle("A1:D1")->applyFromArray([
              'borders' => [
                  'allborders' => [
                      'style' => PHPExcel_Style_Border::BORDER_THICK,
                      'color' => ['argb' => 'FF000000']
                  ]
              ]
          ]);

          $sheet->getStyle("A1:D1")->getAlignment()
              ->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER)
              ->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER)
              ->setWrapText(true);

          $sheet->cells("A1:D1", function($cells){
              $cells->setFontFamily('Arial');
              $cells->setFontSize(18);
          });

            $fila = 3;

            $sheet->setHeight(1, 60);
            $sheet->cells("A2:D999", function($cells){
                $cells->setFontFamily('Arial');
                $cells->setFontSize(10);
            });

            foreach ($datos as $anio => $registros) {
                $sheet->mergeCells("B{$fila}:D{$fila}");
                $sheet->setCellValue("A{$fila}","Anticipo N°");
                $sheet->setCellValue("B{$fila}","Período Fiscal " . $anio);
                $sheet->cells("A{$fila}:D{$fila}", function($cells){
                    $cells->setBackground('#CCCCCC');
                    $cells->setFontWeight('bold');
                    $cells->setFontSize(13);
                    $cells->setAlignment('center');
                });
                $sheet->getStyle("A{$fila}:D{$fila}")->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
                $sheet->setHeight($fila, 20);
                $sheet->getStyle("A{$fila}:D{$fila}")->applyFromArray([
                    'borders' => [
                        'allborders' => [
                            'style' => PHPExcel_Style_Border::BORDER_THICK,
                            'color' => ['argb' => 'FF000000']
                        ]
                    ]
                ]);
                $fila++;

                foreach ($registros as $r) {
                    $filaaux = $fila + 3;
                    $filain = $fila;
                    $sheet->mergeCells("A{$fila}:A{$filaaux}");

                    $sheet->row($fila, [
                        $r->{'nro_anticipo'},
                        "Anticipo",
                        "$ " . number_format($r->{'anticipo'}, 2, ',', '.'),
                        'Fecha de Pago:',
                    ]);
                    $sheet->cells("A{$fila}", function($cells){
                        $cells->setBackground('#FFFF99');
                        $cells->setFontWeight('bold');
                        $cells->setAlignment('center');
                    });

                    $sheet->cells("B{$fila}:D{$fila}", function($cells){
                        $cells->setAlignment('left');
                    });
                    $sheet->cells("C{$fila}:C{$fila}", function($cells){
                        $cells->setAlignment('center');
                    });

                    $sheet->getStyle("A{$fila}:D{$fila}")->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
                    $fila++;

                    $sheet->row($fila,[
                      '',
                      'Abonado',
                      "$ " . number_format($r->{'abonado'}, 2, ',', '.'),
                      $r->fecha_pago
                    ]);

                    $sheet->cells("B{$fila}:B{$fila}", function($cells){
                        $cells->setAlignment('left');
                    });
                    $sheet->cells("C{$fila}:C{$fila}", function($cells){
                        $cells->setAlignment('center');
                    });
                    $sheet->cells("D{$fila}:D{$fila}", function($cells){
                        $cells->setAlignment('right');
                    });

                    $sheet->getStyle("A{$fila}:D{$fila}")->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
                    $fila++;

                    $sheet->row($fila,[
                      '',
                      'Computa Contra Impuesto',
                      "$ " . number_format($r->{'computa_contra'}, 2, ',', '.'),
                      $r->observaciones
                    ]);
                    $sheet->cells("B{$fila}:D{$fila}", function($cells){
                        $cells->setAlignment('left');
                    });
                    $sheet->cells("C{$fila}:C{$fila}", function($cells){
                        $cells->setAlignment('center');
                    });

                    $fila++;

                    $sheet->row($fila,[
                      '',
                      'Diferencia',
                      "$ ". number_format($r->{'diferencia'}, 2, '.', '.'),
                    ]);

                    $sheet->cells("B{$fila}:D{$fila}", function($cells){
                        $cells->setAlignment('left');
                    });
                    $sheet->cells("C{$fila}:C{$fila}", function($cells){
                        $cells->setAlignment('center');
                    });

                    $sheet->getStyle("A{$fila}:D{$fila}")->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);


                    $sheet->getStyle("A{$filain}:D{$filaaux}")->applyFromArray([
                        'borders' => [
                            'allborders' => [
                                'style' => PHPExcel_Style_Border::BORDER_THIN,
                                'color' => ['argb' => 'FF000000']
                            ]
                        ]
                    ]);
                    $fila++;
                }

                $periodo = ($datosPeriodo->has($anio) && $datosPeriodo->get($anio)->isNotEmpty())
                  ? $datosPeriodo->get($anio)->first()
                  : null;
                if($periodo){

                  $fila++;
                  $sheet->mergeCells("A{$fila}:D{$fila}");
                  $sheet->setCellValue("A{$fila}", "Período Fiscal ". $anio);
                  $sheet->cells("A{$fila}:D{$fila}", function($cells){
                      $cells->setBackground('#CCCCCC');
                      $cells->setFontWeight('bold');
                      $cells->setFontSize(13);
                      $cells->setAlignment('center');
                  });
                  $sheet->getStyle("A{$fila}:D{$fila}")->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
                  $sheet->setHeight($fila, 20);
                  $sheet->getStyle("A{$fila}:D{$fila}")->applyFromArray([
                      'borders' => [
                          'allborders' => [
                              'style' => PHPExcel_Style_Border::BORDER_THICK,
                              'color' => ['argb' => 'FF000000']
                          ]
                      ]
                  ]);

                  $fila++;
                  $sheet->cells("A{$fila}:D{$fila}", function($cells) use ($casinoId) {
                      switch ($casinoId) {
                      case 1:
                          $color = '#339966';

                          break;
                      case 2:
                          $color = '#ff0000';

                          break;
                      case 3:
                          $color = '#ffcc00';

                          break;
                      default:
                          $color = '#222222';

                  }

                      $cells->setBackground($color);
                      $cells->setFontColor('#000000');
                      $cells->setFontWeight('bold');
                      $cells->setAlignment('center');
                  });

                  $sheet->getStyle("A{$fila}:D{$fila}")->getAlignment()->setWrapText(true)->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);

                  $sheet->row($fila,[
                    'Fecha Presentación',
                    'Impuesto a Pagar/ Saldo a Favor',
                    'Forma de Pago',
                    'Observaciones',
                  ]);

                  $sheet->getStyle("A{$fila}:D{$fila}")->applyFromArray([
                      'borders' => [
                          'allborders' => [
                              'style' => PHPExcel_Style_Border::BORDER_THIN,
                              'color' => ['argb' => 'FF000000']
                          ]
                      ]
                  ]);

                  $fila++;

                  $sheet->row($fila,[
                    $periodo->fecha_presentacion,
                    "$ " . number_format($periodo->{'saldo'}, 2, ',', '.'),
                    $periodo->forma_pago,
                    $periodo->observaciones,
                  ]);

                  $sheet->cells("A{$fila}:D{$fila}", function($cells){
                      $cells->setAlignment('center');
                  });
                  $sheet->getStyle("A{$fila}:D{$fila}")
                        ->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);

                  $sheet->getStyle("A{$fila}:D{$fila}")->applyFromArray([
                      'borders' => [
                          'allborders' => [
                              'style' => PHPExcel_Style_Border::BORDER_THIN,
                              'color' => ['argb' => 'FF000000']
                          ]
                      ]
                  ]);

                  $fila++;
                }
                  $fila++;
            }


            $anchos = [
                22, 30, 30, 30, 22, 22, 22, 22,
                22, 22, 22, 18, 15, 15, 18, 20
            ];
            foreach (range('A', 'D') as $i => $col) {
                $sheet->setWidth($col, $anchos[$i]);
            }

        });
    })->export('xlsx');
}

public function descargarGananciasXlsxTodos(Request $request)
{
    $desde = $request->query('desde');
    $hasta = $request->query('hasta');

    $user = Usuario::find(session('id_usuario'));
    $casinos = $user->casinos->pluck('nombre', 'id_casino');

    return Excel::create('registro_Ganancias_todos', function($excel) use ($casinos, $desde, $hasta) {
        foreach ($casinos as $casinoId => $casinoNombre) {

          $query = RegistroGanancias::select([
              DB::raw("periodo_fiscal"),
              DB::raw("anticipo"),
              DB::raw("nro_anticipo"),
              DB::raw("abonado"),
              DB::raw("computa_contra"),
              DB::raw("diferencia"),
              DB::raw("fecha_pago"),
              DB::raw("observaciones"),
          ])
          ->where('casino', $casinoId);

          if ($desde) {
              $query->where('periodo_fiscal', '>=', $desde);
          }

          if ($hasta) {
              $query->where('periodo_fiscal', '<=', $hasta);
          }

          $datos = $query->orderBy('periodo_fiscal')->orderBy('nro_anticipo')->get()->groupBy('periodo_fiscal');

          $query1 = RegistroGanancias_periodo::select([
              DB::raw("periodo_fiscal"),
              DB::raw("fecha_presentacion"),
              DB::raw("saldo"),
              DB::raw("forma_pago"),
              DB::raw("observaciones"),
          ])
          ->where('casino', $casinoId);

          if ($desde) {
              $query1->where('periodo_fiscal', '>=', $desde);
          }

          if ($hasta) {
              $query1->where('periodo_fiscal', '<=', $hasta);
          }

          $datosPeriodo = $query1->orderBy('periodo_fiscal')->get()->groupBy('periodo_fiscal');

            $excel->sheet($casinoNombre, function($sheet) use ( $datos, $casinoId, $datosPeriodo) {
              $sheet->mergeCells("A1:D1");
              $sheet->setCellValue("A1","FECHA DE CIERRE DE EJERCICIO 31/10 - PRESENTACION DE DDJJ MARZO DEL AÑO SIGUIENTE");

              $sheet->getStyle("A1:D1")->applyFromArray([
                  'borders' => [
                      'allborders' => [
                          'style' => PHPExcel_Style_Border::BORDER_THICK,
                          'color' => ['argb' => 'FF000000']
                      ]
                  ]
              ]);

              $sheet->getStyle("A1:D1")->getAlignment()
                  ->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER)
                  ->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER)
                  ->setWrapText(true);

              $sheet->cells("A1:D1", function($cells){
                  $cells->setFontFamily('Arial');
                  $cells->setFontSize(18);
              });

                $fila = 3;

                $sheet->setHeight(1, 60);
                $sheet->cells("A2:D999", function($cells){
                    $cells->setFontFamily('Arial');
                    $cells->setFontSize(10);
                });

                foreach ($datos as $anio => $registros) {
                    $sheet->mergeCells("B{$fila}:D{$fila}");
                    $sheet->setCellValue("A{$fila}","Anticipo N°");
                    $sheet->setCellValue("B{$fila}","Período Fiscal " . $anio);
                    $sheet->cells("A{$fila}:D{$fila}", function($cells){
                        $cells->setBackground('#CCCCCC');
                        $cells->setFontWeight('bold');
                        $cells->setFontSize(13);
                        $cells->setAlignment('center');
                    });
                    $sheet->getStyle("A{$fila}:D{$fila}")->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
                    $sheet->setHeight($fila, 20);
                    $sheet->getStyle("A{$fila}:D{$fila}")->applyFromArray([
                        'borders' => [
                            'allborders' => [
                                'style' => PHPExcel_Style_Border::BORDER_THICK,
                                'color' => ['argb' => 'FF000000']
                            ]
                        ]
                    ]);
                    $fila++;

                    foreach ($registros as $r) {
                        $filaaux = $fila + 3;
                        $filain = $fila;
                        $sheet->mergeCells("A{$fila}:A{$filaaux}");

                        $sheet->row($fila, [
                            $r->{'nro_anticipo'},
                            "Anticipo",
                            "$ " . number_format($r->{'anticipo'}, 2, ',', '.'),
                            'Fecha de Pago:',
                        ]);
                        $sheet->cells("A{$fila}", function($cells){
                            $cells->setBackground('#FFFF99');
                            $cells->setFontWeight('bold');
                            $cells->setAlignment('center');
                        });

                        $sheet->cells("B{$fila}:D{$fila}", function($cells){
                            $cells->setAlignment('left');
                        });
                        $sheet->cells("C{$fila}:C{$fila}", function($cells){
                            $cells->setAlignment('center');
                        });

                        $sheet->getStyle("A{$fila}:D{$fila}")->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
                        $fila++;

                        $sheet->row($fila,[
                          '',
                          'Abonado',
                          "$ " . number_format($r->{'abonado'}, 2, ',', '.'),
                          $r->fecha_pago
                        ]);

                        $sheet->cells("B{$fila}:B{$fila}", function($cells){
                            $cells->setAlignment('left');
                        });
                        $sheet->cells("C{$fila}:C{$fila}", function($cells){
                            $cells->setAlignment('center');
                        });
                        $sheet->cells("D{$fila}:D{$fila}", function($cells){
                            $cells->setAlignment('right');
                        });

                        $sheet->getStyle("A{$fila}:D{$fila}")->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
                        $fila++;

                        $sheet->row($fila,[
                          '',
                          'Computa Contra Impuesto',
                          "$ " . number_format($r->{'computa_contra'}, 2, ',', '.'),
                          $r->observaciones
                        ]);
                        $sheet->cells("B{$fila}:D{$fila}", function($cells){
                            $cells->setAlignment('left');
                        });
                        $sheet->cells("C{$fila}:C{$fila}", function($cells){
                            $cells->setAlignment('center');
                        });

                        $fila++;

                        $sheet->row($fila,[
                          '',
                          'Diferencia',
                          "$ ". number_format($r->{'diferencia'}, 2, '.', '.'),
                        ]);

                        $sheet->cells("B{$fila}:D{$fila}", function($cells){
                            $cells->setAlignment('left');
                        });
                        $sheet->cells("C{$fila}:C{$fila}", function($cells){
                            $cells->setAlignment('center');
                        });

                        $sheet->getStyle("A{$fila}:D{$fila}")->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);


                        $sheet->getStyle("A{$filain}:D{$filaaux}")->applyFromArray([
                            'borders' => [
                                'allborders' => [
                                    'style' => PHPExcel_Style_Border::BORDER_THIN,
                                    'color' => ['argb' => 'FF000000']
                                ]
                            ]
                        ]);
                        $fila++;
                    }

                    $periodo = ($datosPeriodo->has($anio) && $datosPeriodo->get($anio)->isNotEmpty())
                      ? $datosPeriodo->get($anio)->first()
                      : null;
                    if($periodo){

                      $fila++;
                      $sheet->mergeCells("A{$fila}:D{$fila}");
                      $sheet->setCellValue("A{$fila}", "Período Fiscal ". $anio);
                      $sheet->cells("A{$fila}:D{$fila}", function($cells){
                          $cells->setBackground('#CCCCCC');
                          $cells->setFontWeight('bold');
                          $cells->setFontSize(13);
                          $cells->setAlignment('center');
                      });
                      $sheet->getStyle("A{$fila}:D{$fila}")->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
                      $sheet->setHeight($fila, 20);
                      $sheet->getStyle("A{$fila}:D{$fila}")->applyFromArray([
                          'borders' => [
                              'allborders' => [
                                  'style' => PHPExcel_Style_Border::BORDER_THICK,
                                  'color' => ['argb' => 'FF000000']
                              ]
                          ]
                      ]);

                      $fila++;
                      $sheet->cells("A{$fila}:D{$fila}", function($cells) use ($casinoId) {
                          switch ($casinoId) {
                          case 1:
                              $color = '#339966';

                              break;
                          case 2:
                              $color = '#ff0000';

                              break;
                          case 3:
                              $color = '#ffcc00';

                              break;
                          default:
                              $color = '#222222';

                      }

                          $cells->setBackground($color);
                          $cells->setFontColor('#000000');
                          $cells->setFontWeight('bold');
                          $cells->setAlignment('center');
                      });

                      $sheet->getStyle("A{$fila}:D{$fila}")->getAlignment()->setWrapText(true)->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);

                      $sheet->row($fila,[
                        'Fecha Presentación',
                        'Impuesto a Pagar/ Saldo a Favor',
                        'Forma de Pago',
                        'Observaciones',
                      ]);

                      $sheet->getStyle("A{$fila}:D{$fila}")->applyFromArray([
                          'borders' => [
                              'allborders' => [
                                  'style' => PHPExcel_Style_Border::BORDER_THIN,
                                  'color' => ['argb' => 'FF000000']
                              ]
                          ]
                      ]);

                      $fila++;

                      $sheet->row($fila,[
                        $periodo->fecha_presentacion,
                        "$ " . number_format($periodo->{'saldo'}, 2, ',', '.'),
                        $periodo->forma_pago,
                        $periodo->observaciones,
                      ]);

                      $sheet->cells("A{$fila}:D{$fila}", function($cells){
                          $cells->setAlignment('center');
                      });
                      $sheet->getStyle("A{$fila}:D{$fila}")
                            ->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);

                      $sheet->getStyle("A{$fila}:D{$fila}")->applyFromArray([
                          'borders' => [
                              'allborders' => [
                                  'style' => PHPExcel_Style_Border::BORDER_THIN,
                                  'color' => ['argb' => 'FF000000']
                              ]
                          ]
                      ]);

                      $fila++;
                    }
                      $fila++;
                }




                $anchos = [
                    22, 30, 30, 30, 22, 22, 22, 22,
                    22, 22, 22, 18, 15, 15, 18, 20
                ];
                foreach (range('A', 'D') as $i => $col) {
                    $sheet->setWidth($col, $anchos[$i]);
                }
            });
        }
    })->export('xlsx');
}


//JACKPOTS PAGADOS
public function guardarJackpotsPagados(Request $request){

      $path = null;

      try {
          DB::beginTransaction();

          $JackpotsPagados = new RegistroJackpotsPagados();
          $JackpotsPagados->fecha_JackpotsPagados = $request->fecha_JackpotsPagados.'-01';




          $JackpotsPagados->importe = $request->importe_JackpotsPagados;

          $JackpotsPagados->casino = $request->casinoJackpotsPagados;
          $JackpotsPagados->fecha_toma = date('Y-m-d h:i:s', time());
          $JackpotsPagados->usuario = UsuarioController::getInstancia()->quienSoy()['usuario']['id_usuario'];
          $JackpotsPagados->save();

          $files = Arr::wrap($request->file('uploadJackpotsPagados'));
                    foreach ($files as $file) {
                        if (!($file instanceof UploadedFile) || !$file->isValid()) continue;

                        $base = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
                        $ext  = $file->getClientOriginalExtension();
                        $safe = preg_replace('/\s+/', '_', $base);
                        $filename = time().'_'.Str::random(6).'_'.$safe.($ext?'.'.$ext:'');

                        $file->storeAs('public/RegistroJackpotsPagados', $filename);

                        $JackpotsPagados->archivos()->create([
                            'path'       => $filename,
                            'usuario'    => $JackpotsPagados->usuario,
                            'fecha_toma' => date('Y-m-d H:i:s'),
                        ]);
                    }
          DB::commit();

          return response()->json([
            'success' => true,
             'id' => $JackpotsPagados->id_registroJackpotsPagados,
             'JackpotsPagados'  => $request->all(),
             'path'    => $path,
             'url'     => Storage::url($path)
           ]);
      } catch (\Exception $e) {
          DB::rollBack();
          return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
      }

}

public function ultimasJackpotsPagados(Request $request)
{
    $page    = max(1, (int)$request->query('page', 1));
    $perPage = max(1, (int)$request->query('page_size', 20));

    $user = Usuario::find(session('id_usuario'));
    $allowedCasinoIds = $user->casinos->pluck('id_casino')->toArray();


    $query = RegistroJackpotsPagados::with('casinoJackpotsPagados')
              ->withCount('archivos')
              ->whereIn('casino', $allowedCasinoIds)
              ->orderBy('fecha_JackpotsPagados', 'desc')
              ->orderBy('casino', 'desc');


    if ($c = $request->query('id_casino')) {
      $query->where('casino', $c);
    }
    if ($desde = $request->query('desde')){
      $desde = $desde."-01";
      $query->where('fecha_JackpotsPagados',">=",$desde);
    }
    if ($hasta = $request->query('hasta')){
      $hasta = $hasta."-01";
      $query->where('fecha_JackpotsPagados',"<=",$hasta);
    }

    $total = $query->count();

    $registros = $query
        ->skip(($page - 1) * $perPage)
        ->take($perPage)
        ->get();

    $datos = $registros->map(function($r) {
        return [
            'id_registroJackpotsPagados' => $r->id_registroJackpotsPagados,
            'fecha_JackpotsPagados'   => $r->fecha_JackpotsPagados,
            'importe' => $r->importe,
            'casino'      => $r->casinoJackpotsPagados ? $r->casinoJackpotsPagados->nombre : '-',
	  'tiene_archivos' => $r->archivos_count>0,        ];
    });

    return response()->json([
        'registros'  => $datos,
        'pagination' => [
            'current_page' => $page,
            'per_page'     => $perPage,
            'total'        => $total,
        ],
    ]);
}


public function archivosJackpotsPagados($id)
{
    $JackpotsPagados = RegistroJackpotsPagados::with('archivos')->findOrFail($id);

    $files = $JackpotsPagados->archivos->map(function($a){
        return [
            'id'     => $a->id_registro_archivo,
            'nombre' => basename($a->path),
            'url'    => \Illuminate\Support\Facades\Storage::url($a->path),
            'fecha'  => $a->fecha_toma,
        ];
    });

    return response()->json($files->values());
}

public function llenarJackpotsPagadosEdit($id)
{
    $r = RegistroJackpotsPagados::findOrFail($id);

    return response()->json([
        'fecha'   => $r->fecha_JackpotsPagados,
        'casino'  => $r->casino,
        'importe' => $r->importe,
    ]);
}

public function actualizarJackpotsPagados(Request $request, $id)
{
    $r = RegistroJackpotsPagados::findOrFail($id);

    $r->fecha_JackpotsPagados = $request->input('fecha_JackpotsPagados').'-01';
    $r->casino                = $request->input('casinoJackpotsPagados');
    $r->importe               = $request->input('importe_JackpotsPagados');
    $r->save();

    $saved = 0;
    $files = Arr::wrap($request->file('uploadJackpotsPagados'));
    foreach ($files as $file) {
        if (!($file instanceof UploadedFile) || !$file->isValid()) continue;

        $base = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
        $ext  = $file->getClientOriginalExtension();
        $safe = preg_replace('/\s+/', '_', $base);
        $name = time().'_'.Str::random(6).'_'.$safe.($ext ? '.'.$ext : '');

        $file->storeAs('public/RegistroJackpotsPagados', $name);

        $r->archivos()->create([
            'path'       => $name,
            'usuario'    => UsuarioController::getInstancia()->quienSoy()['usuario']['id_usuario'],
            'fecha_toma' => date('Y-m-d H:i:s'),
        ]);

        $saved++;
    }

    return response()->json(['success' => true, 'saved_files' => $saved]);
}


public function eliminarJackpotsPagados($id){
  $JackpotsPagados = RegistroJackpotsPagados::findOrFail($id);
  if(is_null($JackpotsPagados)) return 0;
  RegistroJackpotsPagados::destroy($id);
  return 1;
}

public function llenarJackpotsPagados($id){
  $JackpotsPagados = RegistroJackpotsPagados::with('casinoJackpotsPagados')->findOrFail($id);
  if(is_null($JackpotsPagados)) return 0;

  return response()->json([

    'fecha' => $JackpotsPagados->fecha_JackpotsPagados,
    'casino' => $JackpotsPagados->casinoJackpotsPagados ? $JackpotsPagados->casinoJackpotsPagados->nombre : '-',

  ]);

}

public function descargarJackpotsPagadosCsv(Request $request)
{
    $casinoId = $request->query('casino');
    $desde    = $request->query('desde');
    $hasta    = $request->query('hasta');
    $user = Usuario::find(session('id_usuario'));
    $allowedCasinoIds = $user->casinos->pluck('id_casino')->toArray();

    $registros = RegistroJackpotsPagados::with('casinoJackpotsPagados')
        ->whereIn('casino',$allowedCasinoIds)
        ->when($casinoId && $casinoId != 4, function ($q) use ($casinoId) {
            $q->where('casino', $casinoId);
        })
        ->when($desde, function ($q) use ($desde) {
            $q->where('fecha_JackpotsPagados', '>=', $desde . '-01');
        })
        ->when($hasta, function ($q) use ($hasta) {
            $q->where('fecha_JackpotsPagados', '<=', $hasta . '-01');
        })
        ->orderBy('casino')
        ->orderBy('fecha_JackpotsPagados')
        ->get();


    $csv = [];
    $csv[] = ['Casino', 'Año', 'Mes', 'Importe' ];

    foreach ($registros as $r) {
        setlocale(LC_TIME, 'es_ES.UTF-8');
        $anio     = date('Y', strtotime($r->fecha_JackpotsPagados));
        $mes      = strftime('%B', strtotime($r->fecha_JackpotsPagados));
        $casino   = $r->casinoJackpotsPagados ? $r->casinoJackpotsPagados->nombre : '-';
        $importe = number_format($r->importe, 2, '.', '');

        $csv[] = [
            $casino,
            $anio,
            ucfirst($mes),
            $importe,

        ];
    }

    $nombreCasino = $casinoId == 4 ? 'todos' : Casino::find($casinoId)->nombre ?? 'desconocido';
    $filename = "JackpotsPagados_{$nombreCasino}_" . date('Ymd_His') . ".csv";


    $handle = fopen('php://temp', 'r+');
    foreach ($csv as $linea) {
        fputcsv($handle, $linea, ',');
    }
    rewind($handle);
    $contenido = stream_get_contents($handle);
    fclose($handle);

    return response($contenido, 200, [
        'Content-Type'        => 'text/csv; charset=UTF-8',
        'Content-Disposition' => "attachment; filename=\"$filename\"",
        'Cache-Control'       => 'no-store, no-cache'
    ]);
}

public function descargarJackpotsPagadosXlsx(Request $request)
{
    $casinoId = $request->query('casino');
    $desde = $request->query('desde');
    $hasta = $request->query('hasta');

    $casino = Casino::findOrFail($casinoId);

    $query = RegistroJackpotsPagados::select([
        DB::raw("YEAR(fecha_JackpotsPagados) AS anio"),
        DB::raw("MONTHNAME(fecha_JackpotsPagados) AS Mes"),
        DB::raw("importe"),


    ])
    ->where('casino', $casinoId);

    if ($desde) {
        $query->where('fecha_JackpotsPagados', '>=', $desde . '-01');
    }

    if ($hasta) {
        $query->where('fecha_JackpotsPagados', '<=', $hasta . '-31');
    }

    $datos = $query->orderBy('fecha_JackpotsPagados')->get()->groupBy('anio');

    $totalesPorAnio = $datos->map(function($grupo, $anio) {
    return [
        'anio' => $anio,
        'total_cantidad' => $grupo->sum('cantidad'),
        'total_importe'   => $grupo->sum('importe'),
        ];
    });

    $filename = "registro_JackpotsPagados_" . str_replace(' ', '_', strtolower($casino->nombre));

    return Excel::create($filename, function($excel) use ($datos, $casino, $casinoId, $totalesPorAnio) {
        $excel->sheet('JackpotsPagados', function($sheet) use ($datos, $casino, $casinoId, $totalesPorAnio) {
            $fila = 1;





            $sheet->row($fila, [
                'Mes',
                'Importe',
            ]);
            $sheet->cells("A1:B1", function($cells) use ($casinoId) {
                switch ($casinoId) {
                case 1:
                    $color = '#339966';

                    break;
                case 2:
                    $color = '#ff0000';

                    break;
                case 3:
                    $color = '#ffcc00';

                    break;
                default:
                    $color = '#222222';

            }

                $cells->setBackground($color);
                $cells->setFontColor('#000000');
                $cells->setFontWeight('bold');
                $cells->setAlignment('center');
            });

            $sheet->getStyle("A1:B1")->getAlignment()->setWrapText(true)->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
            $sheet->setHeight(1, 50);
            $sheet->cells("A1:B999", function($cells){
                $cells->setFontFamily('Arial');
                $cells->setFontSize(10);
            });

            $fila++;
            foreach ($datos as $anio => $registros) {
                $sheet->mergeCells("A{$fila}:B{$fila}");
                $sheet->setCellValue("A{$fila}", $anio);
                $sheet->cells("A{$fila}:B{$fila}", function($cells){
                    $cells->setBackground('#CCCCCC');
                    $cells->setFontWeight('bold');
                    $cells->setFontSize(13);
                    $cells->setAlignment('center');
                });
                $sheet->getStyle("A{$fila}:B{$fila}")->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
                $sheet->setHeight($fila, 20);
                $fila++;

                foreach ($registros as $r) {
                    setlocale(LC_TIME, 'es_ES.UTF-8');
                    $mesEsp = ucfirst(strftime('%B', strtotime($r->{'Mes'} . ' 1')));

                    $sheet->row($fila, [
                        $mesEsp,
                        "$ " . number_format($r->{'importe'}, 2, ',', '.'),

                    ]);
                    $sheet->cells("A{$fila}", function($cells){
                        $cells->setBackground('#FFFF99');
                        $cells->setFontWeight('bold');
                        $cells->setAlignment('left');
                    });

                    $sheet->cells("B{$fila}:B{$fila}", function($cells){
                        $cells->setAlignment('center');
                    });

                    $sheet->getStyle("A{$fila}:B{$fila}")->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
                    $fila++;
                }
                $totales = $totalesPorAnio[$anio];
                	$sheet->row($fila, [
                	    'TOTAL ' . $anio,
                	    "$ " . number_format($totales['total_importe'], 2, ',', '.'),
                	]);
                	$sheet->cells("A{$fila}", function($cells){
                	    $cells->setBackground('#CCCCFF');
                	    $cells->setFontWeight('bold');
                	});
                	$sheet->cells("B{$fila}:B{$fila}", function($cells){
                	    $cells->setAlignment('center');
                	    $cells->setFontWeight('bold');
                	});
                	$fila++;
            }

            $sheet->getStyle("A1:B1")->applyFromArray([
                'borders' => [
                    'allborders' => [
                        'style' => PHPExcel_Style_Border::BORDER_THICK,
                        'color' => ['argb' => 'FF000000']
                    ]
                ]
            ]);

            $sheet->getStyle("A2:B" . ($fila -1))->applyFromArray([
                'borders' => [
                    'allborders' => [
                        'style' => PHPExcel_Style_Border::BORDER_THIN,
                        'color' => ['argb' => 'FF000000']
                    ]
                ]
            ]);

            $anchos = [
                9, 22, 22, 22, 22, 22, 22, 22,
                22, 22, 22, 18, 15, 15, 18, 20
            ];
            foreach (range('A', 'B') as $i => $col) {
                $sheet->setWidth($col, $anchos[$i]);
            }

            $sheet->setFreeze('A2');
        });
    })->export('xlsx');
}

public function descargarJackpotsPagadosXlsxTodos(Request $request)
{
    $desde = $request->query('desde');
    $hasta = $request->query('hasta');

    $user = Usuario::find(session('id_usuario'));
    $casinos = $user->casinos->pluck('nombre', 'id_casino');

    return Excel::create('registro_JackpotsPagados_todos', function($excel) use ($casinos, $desde, $hasta) {
        foreach ($casinos as $casinoId => $casinoNombre) {

            $datos = RegistroJackpotsPagados::select([
              DB::raw("YEAR(fecha_JackpotsPagados) AS anio"),
              DB::raw("MONTHNAME(fecha_JackpotsPagados) AS Mes"),
              DB::raw("importe"),
            ])
            ->where('casino', $casinoId)
            ->when($desde, function($q) use ($desde) {
                return $q->where('fecha_JackpotsPagados', '>=', $desde . '-01');
            })
            ->when($hasta, function($q) use ($hasta) {
                return $q->where('fecha_JackpotsPagados', '<=', $hasta . '-31');
            })
            ->orderBy('fecha_JackpotsPagados')
            ->get()
            ->groupBy('anio');

            $totalesPorAnio = $datos->map(function($grupo, $anio) {
            return [
                'anio' => $anio,
                'total_importe'   => $grupo->sum('importe'),
                ];
            });

            $excel->sheet($casinoNombre, function($sheet) use ( $datos, $casinoId, $totalesPorAnio) {
                $fila = 1;

                $sheet->row($fila, [
                  'Mes',
                  'Importe',

                ]);

                $sheet->cells("A1:B1", function($cells) use ($casinoId) {
                    switch ($casinoId) {
                case 1:
                    $color = '#339966';

                    break;
                case 2:
                    $color = '#ff0000';

                    break;
                case 3:
                    $color = '#ffcc00';

                    break;
                default:
                    $color = '#222222';

            }
                    $cells->setBackground($color);
                    $cells->setFontColor('#000000');
                    $cells->setFontWeight('bold');
                    $cells->setAlignment('center');
                });

                $sheet->getStyle("A1:B1")->getAlignment()
                    ->setWrapText(true)
                    ->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
                $sheet->setHeight(1, 50);


                $fila++;

                $sheet->cells("A1:B999", function($cells) {
                    $cells->setFontFamily('Arial');
                    $cells->setFontSize(10);
                });

                foreach ($datos as $anio => $registros) {
                    $sheet->mergeCells("A{$fila}:B{$fila}");
                    $sheet->setCellValue("A{$fila}", $anio);
                    $sheet->cells("A{$fila}:B{$fila}", function($cells){
                        $cells->setBackground('#CCCCCC');
                        $cells->setFontWeight('bold');
                        $cells->setFontSize(13);
                        $cells->setAlignment('center');
                    });
                    $sheet->setHeight($fila, 20);
                    $fila++;

                    foreach ($registros as $r) {
                        setlocale(LC_TIME, 'es_ES.UTF-8');
                        $mesEsp = ucfirst(strftime('%B', strtotime($r->{'Mes'} . ' 1')));

                        $sheet->row($fila, [
                          $mesEsp,
                          "$ " . number_format($r->{'importe'}, 2, ',', '.'),
                        ]);

                        $sheet->cells("A{$fila}", function($cells){
                            $cells->setBackground('#FFFF99');
                            $cells->setFontWeight('bold');
                            $cells->setAlignment('left');
                        });
                        $sheet->cells("B{$fila}:B{$fila}", function($cells){
                            $cells->setAlignment('center');
                        });
                        $fila++;
                    }
                    $totales = $totalesPorAnio[$anio];
                    	$sheet->row($fila, [
                    	    'TOTAL ' . $anio,
                    	    "$ " . number_format($totales['total_importe'], 2, ',', '.'),
                    	]);
                    	$sheet->cells("A{$fila}", function($cells){
                    	    $cells->setBackground('#CCCCFF');
                    	    $cells->setFontWeight('bold');
                    	});
                    	$sheet->cells("B{$fila}:B{$fila}", function($cells){
                    	    $cells->setAlignment('center');
                    	    $cells->setFontWeight('bold');
                    	});
                    	$fila++;
                }



                $sheet->getStyle("A1:B1")->applyFromArray([
                    'borders' => [
                        'allborders' => [
                            'style' => PHPExcel_Style_Border::BORDER_THICK,
                            'color' => ['argb' => 'FF000000']
                        ]
                    ]
                ]);
                $sheet->getStyle("A2:B" . ($fila - 1))->applyFromArray([
                    'borders' => [
                        'allborders' => [
                            'style' => PHPExcel_Style_Border::BORDER_THIN,
                            'color' => ['argb' => 'FF000000']
                        ]
                    ]
                ]);

                $anchos = [9, 25, 25, 25, 18, 16, 14, 18,
                    16, 14, 20, 18, 14, 15, 18, 20, 20,
                    20, 20, 20, 20, 20];
                foreach (range('A', 'B') as $i => $col) {
                    $sheet->setWidth($col, $anchos[$i]);
                }

                $sheet->setFreeze('A2');
            });
        }
    })->export('xlsx');
}

//PREMIOS PAGADOS
public function guardarPremiosPagados(Request $request){

      $path = null;

      try {
          DB::beginTransaction();

          $PremiosPagados = new RegistroPremiosPagados();
          $PremiosPagados->fecha_PremiosPagados = $request->fecha_PremiosPagados.'-01';




          $PremiosPagados->cantidad = $request->cant_PremiosPagados;
          $PremiosPagados->importe = $request->importe_PremiosPagados;

          $PremiosPagados->casino = $request->casinoPremiosPagados;
          $PremiosPagados->fecha_toma = date('Y-m-d h:i:s', time());
          $PremiosPagados->usuario = UsuarioController::getInstancia()->quienSoy()['usuario']['id_usuario'];
          $PremiosPagados->save();
          $files = Arr::wrap($request->file('uploadPremiosPagados'));
          foreach ($files as $file) {
              if (!($file instanceof UploadedFile) || !$file->isValid()) continue;

              $base = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
              $ext  = $file->getClientOriginalExtension();
              $safe = preg_replace('/\s+/', '_', $base);
              $filename = time().'_'.Str::random(6).'_'.$safe.($ext?'.'.$ext:'');

              $file->storeAs('public/RegistroPremiosPagados', $filename);

              $PremiosPagados->archivos()->create([
                  'path'       => $filename,
                  'usuario'    => $PremiosPagados->usuario,
                  'fecha_toma' => date('Y-m-d H:i:s'),
              ]);
          }
          DB::commit();

          return response()->json([
            'success' => true,
             'id' => $PremiosPagados->id_registroPremiosPagados,
             'PremiosPagados'  => $request->all(),
             'path'    => $path,
             'url'     => Storage::url($path)
           ]);
      } catch (\Exception $e) {
          DB::rollBack();
          return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
      }

}

public function ultimasPremiosPagados(Request $request)
{
    $page    = max(1, (int)$request->query('page', 1));
    $perPage = max(1, (int)$request->query('page_size', 20));

    $user = Usuario::find(session('id_usuario'));
    $allowedCasinoIds = $user->casinos->pluck('id_casino')->toArray();


    $query = RegistroPremiosPagados::with('casinoPremiosPagados')
              ->withCount('archivos')
              ->whereIn('casino', $allowedCasinoIds)
              ->orderBy('fecha_PremiosPagados', 'desc')
              ->orderBy('casino', 'desc');


    if ($c = $request->query('id_casino')) {
      $query->where('casino', $c);
    }
    if ($desde = $request->query('desde')){
      $desde = $desde."-01";
      $query->where('fecha_PremiosPagados',">=",$desde);
    }
    if ($hasta = $request->query('hasta')){
      $hasta = $hasta."-01";
      $query->where('fecha_PremiosPagados',"<=",$hasta);
    }

    $total = $query->count();

    $registros = $query
        ->skip(($page - 1) * $perPage)
        ->take($perPage)
        ->get();

    $datos = $registros->map(function($r) {
        return [
            'id_registroPremiosPagados' => $r->id_registroPremiosPagados,
            'fecha_PremiosPagados'   => $r->fecha_PremiosPagados,
            'cantidad' => $r->cantidad,
            'importe' => $r->importe,
            'casino'      => $r->casinoPremiosPagados ? $r->casinoPremiosPagados->nombre : '-',
	  'tiene_archivos' => $r->archivos_count>0,        ];
    });

    return response()->json([
        'registros'  => $datos,
        'pagination' => [
            'current_page' => $page,
            'per_page'     => $perPage,
            'total'        => $total,
        ],
    ]);
}

public function archivosPremiosPagados($id)
{
    $PremiosPagados = RegistroPremiosPagados::with('archivos')->findOrFail($id);

    $files = $PremiosPagados->archivos->map(function($a){
        return [
            'id'     => $a->id_registro_archivo,
            'nombre' => basename($a->path),
            'url'    => \Illuminate\Support\Facades\Storage::url($a->path),
            'fecha'  => $a->fecha_toma,
        ];
    });

    return response()->json($files->values());
}

public function llenarPremiosPagadosEdit($id)
{
    $r = RegistroPremiosPagados::findOrFail($id);

    return response()->json([
        'fecha'    => $r->fecha_PremiosPagados,
        'casino'   => $r->casino,
        'cantidad' => $r->cantidad,
        'importe'  => $r->importe,
    ]);
}

public function actualizarPremiosPagados(Request $request, $id)
{
    $r = RegistroPremiosPagados::findOrFail($id);

    $r->fecha_PremiosPagados = $request->input('fecha_PremiosPagados').'-01';
    $r->casino               = $request->input('casinoPremiosPagados');
    $r->cantidad             = $request->input('cant_PremiosPagados');
    $r->importe              = $request->input('importe_PremiosPagados');
    $r->save();

    $saved = 0;
    $files = Arr::wrap($request->file('uploadPremiosPagados'));
    foreach ($files as $file) {
        if (!($file instanceof UploadedFile) || !$file->isValid()) continue;

        $base = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
        $ext  = $file->getClientOriginalExtension();
        $safe = preg_replace('/\s+/', '_', $base);
        $name = time().'_'.Str::random(6).'_'.$safe.($ext ? '.'.$ext : '');

        $file->storeAs('public/RegistroPremiosPagados', $name);

        $r->archivos()->create([
            'path'       => $name,
            'usuario'    => UsuarioController::getInstancia()->quienSoy()['usuario']['id_usuario'],
            'fecha_toma' => date('Y-m-d H:i:s'),
        ]);

        $saved++;
    }

    return response()->json(['success' => true, 'saved_files' => $saved]);
}


public function eliminarPremiosPagados($id){
  $PremiosPagados = RegistroPremiosPagados::findOrFail($id);
  if(is_null($PremiosPagados)) return 0;
  RegistroPremiosPagados::destroy($id);
  return 1;
}

public function llenarPremiosPagados($id){
  $PremiosPagados = RegistroPremiosPagados::with('casinoPremiosPagados')->findOrFail($id);
  if(is_null($PremiosPagados)) return 0;

  return response()->json([

    'fecha' => $PremiosPagados->fecha_PremiosPagados,
    'casino' => $PremiosPagados->casinoPremiosPagados ? $PremiosPagados->casinoPremiosPagados->nombre : '-',

  ]);

}

public function descargarPremiosPagadosCsv(Request $request)
{
    $casinoId = $request->query('casino');
    $desde    = $request->query('desde');
    $hasta    = $request->query('hasta');
    $user = Usuario::find(session('id_usuario'));
    $allowedCasinoIds = $user->casinos->pluck('id_casino')->toArray();

    $registros = RegistroPremiosPagados::with('casinoPremiosPagados')
        ->whereIn('casino',$allowedCasinoIds)
        ->when($casinoId && $casinoId != 4, function ($q) use ($casinoId) {
            $q->where('casino', $casinoId);
        })
        ->when($desde, function ($q) use ($desde) {
            $q->where('fecha_PremiosPagados', '>=', $desde . '-01');
        })
        ->when($hasta, function ($q) use ($hasta) {
            $q->where('fecha_PremiosPagados', '<=', $hasta . '-01');
        })
        ->orderBy('casino')
        ->orderBy('fecha_PremiosPagados')
        ->get();


    $csv = [];
    $csv[] = ['Casino', 'Año', 'Mes', 'Cantidad', 'Importe' ];

    foreach ($registros as $r) {
        setlocale(LC_TIME, 'es_ES.UTF-8');
        $anio     = date('Y', strtotime($r->fecha_PremiosPagados));
        $mes      = strftime('%B', strtotime($r->fecha_PremiosPagados));
        $casino   = $r->casinoPremiosPagados ? $r->casinoPremiosPagados->nombre : '-';
        $importe = number_format($r->importe, 2, '.', '');
        $cantidad = $r->cantidad;

        $csv[] = [
            $casino,
            $anio,
            ucfirst($mes),
            $cantidad,
            $importe,

        ];
    }

    $nombreCasino = $casinoId == 4 ? 'todos' : Casino::find($casinoId)->nombre ?? 'desconocido';
    $filename = "PremiosPagados_{$nombreCasino}_" . date('Ymd_His') . ".csv";

    $handle = fopen('php://temp', 'r+');
    foreach ($csv as $linea) {
        fputcsv($handle, $linea, ',');
    }
    rewind($handle);
    $contenido = stream_get_contents($handle);
    fclose($handle);

    return response($contenido, 200, [
        'Content-Type'        => 'text/csv; charset=UTF-8',
        'Content-Disposition' => "attachment; filename=\"$filename\"",
        'Cache-Control'       => 'no-store, no-cache'
    ]);
}

public function descargarPremiosPagadosXlsx(Request $request)
{
    $casinoId = $request->query('casino');
    $desde = $request->query('desde');
    $hasta = $request->query('hasta');

    $casino = Casino::findOrFail($casinoId);

    $query = RegistroPremiosPagados::select([
        DB::raw("YEAR(fecha_PremiosPagados) AS anio"),
        DB::raw("MONTHNAME(fecha_PremiosPagados) AS Mes"),
        DB::raw("cantidad"),
        DB::raw("importe"),


    ])
    ->where('casino', $casinoId);

    if ($desde) {
        $query->where('fecha_PremiosPagados', '>=', $desde . '-01');
    }

    if ($hasta) {
        $query->where('fecha_PremiosPagados', '<=', $hasta . '-31');
    }

    $datos = $query->orderBy('fecha_PremiosPagados')->get()->groupBy('anio');

    $totalesPorAnio = $datos->map(function($grupo, $anio) {
    return [
        'anio' => $anio,
        'total_cantidad' => $grupo->sum('cantidad'),
        'total_importe'   => $grupo->sum('importe'),
        ];
    });

    $filename = "registro_PremiosPagados_" . str_replace(' ', '_', strtolower($casino->nombre));

    return Excel::create($filename, function($excel) use ($datos, $casino, $casinoId, $totalesPorAnio) {
        $excel->sheet('PremiosPagados', function($sheet) use ($datos, $casino, $casinoId, $totalesPorAnio) {
            $fila = 1;

            $sheet->row($fila, [
                'Mes',
                'Cantidad',
                'Importe',
            ]);
            $sheet->cells("A1:C1", function($cells) use ($casinoId) {
                switch ($casinoId) {
                case 1:
                    $color = '#339966';

                    break;
                case 2:
                    $color = '#ff0000';

                    break;
                case 3:
                    $color = '#ffcc00';

                    break;
                default:
                    $color = '#222222';

            }

                $cells->setBackground($color);
                $cells->setFontColor('#000000');
                $cells->setFontWeight('bold');
                $cells->setAlignment('center');
            });

            $sheet->getStyle("A1:C1")->getAlignment()->setWrapText(true)->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
            $sheet->setHeight(1, 50);
            $sheet->cells("A1:C999", function($cells){
                $cells->setFontFamily('Arial');
                $cells->setFontSize(10);
            });

            $fila++;
            foreach ($datos as $anio => $registros) {
                $sheet->mergeCells("A{$fila}:C{$fila}");
                $sheet->setCellValue("A{$fila}", $anio);
                $sheet->cells("A{$fila}:C{$fila}", function($cells){
                    $cells->setBackground('#CCCCCC');
                    $cells->setFontWeight('bold');
                    $cells->setFontSize(13);
                    $cells->setAlignment('center');
                });
                $sheet->getStyle("A{$fila}:C{$fila}")->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
                $sheet->setHeight($fila, 20);
                $fila++;

                foreach ($registros as $r) {
                    setlocale(LC_TIME, 'es_ES.UTF-8');
                    $mesEsp = ucfirst(strftime('%B', strtotime($r->{'Mes'} . ' 1')));

                    $sheet->row($fila, [
                        $mesEsp,
                        $r->cantidad,
                        "$ " . number_format($r->{'importe'}, 2, ',', '.'),

                    ]);
                    $sheet->cells("A{$fila}", function($cells){
                        $cells->setBackground('#FFFF99');
                        $cells->setFontWeight('bold');
                        $cells->setAlignment('left');
                    });

                    $sheet->cells("B{$fila}:C{$fila}", function($cells){
                        $cells->setAlignment('center');
                    });

                    $sheet->getStyle("A{$fila}:C{$fila}")->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
                    $fila++;
                }
                $totales = $totalesPorAnio[$anio];
                	$sheet->row($fila, [
                	    'TOTAL ' . $anio,
                	    $totales['total_cantidad'],
                	    "$ " . number_format($totales['total_importe'], 2, ',', '.'),
                	]);
                	$sheet->cells("A{$fila}", function($cells){
                	    $cells->setBackground('#CCCCFF');
                	    $cells->setFontWeight('bold');
                	});
                	$sheet->cells("B{$fila}:C{$fila}", function($cells){
                	    $cells->setAlignment('center');
                	    $cells->setFontWeight('bold');
                	});
                	$fila++;
            }

            $sheet->getStyle("A1:C1")->applyFromArray([
                'borders' => [
                    'allborders' => [
                        'style' => PHPExcel_Style_Border::BORDER_THICK,
                        'color' => ['argb' => 'FF000000']
                    ]
                ]
            ]);

            $sheet->getStyle("A2:C" . ($fila -1))->applyFromArray([
                'borders' => [
                    'allborders' => [
                        'style' => PHPExcel_Style_Border::BORDER_THIN,
                        'color' => ['argb' => 'FF000000']
                    ]
                ]
            ]);

            $anchos = [
                9, 22, 22, 22, 22, 22, 22, 22,
                22, 22, 22, 18, 15, 15, 18, 20
            ];
            foreach (range('A', 'C') as $i => $col) {
                $sheet->setWidth($col, $anchos[$i]);
            }

            $sheet->setFreeze('A2');
        });
    })->export('xlsx');
}

public function descargarPremiosPagadosXlsxTodos(Request $request)
{
    $desde = $request->query('desde');
    $hasta = $request->query('hasta');

    $user = Usuario::find(session('id_usuario'));
    $casinos = $user->casinos->pluck('nombre', 'id_casino');

    return Excel::create('registro_PremiosPagados_todos', function($excel) use ($casinos, $desde, $hasta) {
        foreach ($casinos as $casinoId => $casinoNombre) {

            $datos = RegistroPremiosPagados::select([
              DB::raw("YEAR(fecha_PremiosPagados) AS anio"),
              DB::raw("MONTHNAME(fecha_PremiosPagados) AS Mes"),
              DB::raw("cantidad"),
              DB::raw("importe"),
            ])
            ->where('casino', $casinoId)
            ->when($desde, function($q) use ($desde) {
                return $q->where('fecha_PremiosPagados', '>=', $desde . '-01');
            })
            ->when($hasta, function($q) use ($hasta) {
                return $q->where('fecha_PremiosPagados', '<=', $hasta . '-31');
            })
            ->orderBy('fecha_PremiosPagados')
            ->get()
            ->groupBy('anio');

            $totalesPorAnio = $datos->map(function($grupo, $anio) {
            return [
                'anio' => $anio,
                'total_cantidad' => $grupo->sum('cantidad'),
                'total_importe'   => $grupo->sum('importe'),
                ];
            });

            $excel->sheet($casinoNombre, function($sheet) use ( $datos, $casinoId, $totalesPorAnio) {
                $fila = 1;

                $sheet->row($fila, [
                  'Mes',
                  'Cantidad',
                  'Importe',

                ]);

                $sheet->cells("A1:C1", function($cells) use ($casinoId) {
                    switch ($casinoId) {
                case 1:
                    $color = '#339966';

                    break;
                case 2:
                    $color = '#ff0000';

                    break;
                case 3:
                    $color = '#ffcc00';

                    break;
                default:
                    $color = '#222222';

            }
                    $cells->setBackground($color);
                    $cells->setFontColor('#000000');
                    $cells->setFontWeight('bold');
                    $cells->setAlignment('center');
                });

                $sheet->getStyle("A1:C1")->getAlignment()
                    ->setWrapText(true)
                    ->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
                $sheet->setHeight(1, 50);


                $fila++;

                $sheet->cells("A1:C999", function($cells) {
                    $cells->setFontFamily('Arial');
                    $cells->setFontSize(10);
                });

                foreach ($datos as $anio => $registros) {
                    $sheet->mergeCells("A{$fila}:C{$fila}");
                    $sheet->setCellValue("A{$fila}", $anio);
                    $sheet->cells("A{$fila}:C{$fila}", function($cells){
                        $cells->setBackground('#CCCCCC');
                        $cells->setFontWeight('bold');
                        $cells->setFontSize(13);
                        $cells->setAlignment('center');
                    });
                    $sheet->setHeight($fila, 20);
                    $fila++;

                    foreach ($registros as $r) {
                        setlocale(LC_TIME, 'es_ES.UTF-8');
                        $mesEsp = ucfirst(strftime('%B', strtotime($r->{'Mes'} . ' 1')));

                        $sheet->row($fila, [
                          $mesEsp,
                          $r->cantidad,
                          "$ " . number_format($r->{'importe'}, 2, ',', '.'),
                        ]);

                        $sheet->cells("A{$fila}", function($cells){
                            $cells->setBackground('#FFFF99');
                            $cells->setFontWeight('bold');
                            $cells->setAlignment('left');
                        });
                        $sheet->cells("B{$fila}:C{$fila}", function($cells){
                            $cells->setAlignment('center');
                        });
                        $fila++;
                    }
                    $totales = $totalesPorAnio[$anio];
                    	$sheet->row($fila, [
                    	    'TOTAL ' . $anio,
                    	    $totales['total_cantidad'],
                    	    "$ " . number_format($totales['total_importe'], 2, ',', '.'),
                    	]);
                    	$sheet->cells("A{$fila}", function($cells){
                    	    $cells->setBackground('#CCCCFF');
                    	    $cells->setFontWeight('bold');
                    	});
                    	$sheet->cells("B{$fila}:C{$fila}", function($cells){
                    	    $cells->setAlignment('center');
                    	    $cells->setFontWeight('bold');
                    	});
                    	$fila++;
                }



                $sheet->getStyle("A1:C1")->applyFromArray([
                    'borders' => [
                        'allborders' => [
                            'style' => PHPExcel_Style_Border::BORDER_THICK,
                            'color' => ['argb' => 'FF000000']
                        ]
                    ]
                ]);
                $sheet->getStyle("A2:C" . ($fila - 1))->applyFromArray([
                    'borders' => [
                        'allborders' => [
                            'style' => PHPExcel_Style_Border::BORDER_THIN,
                            'color' => ['argb' => 'FF000000']
                        ]
                    ]
                ]);

                $anchos = [9, 25, 25, 25, 18, 16, 14, 18,
                    16, 14, 20, 18, 14, 15, 18, 20, 20,
                    20, 20, 20, 20, 20];
                foreach (range('A', 'C') as $i => $col) {
                    $sheet->setWidth($col, $anchos[$i]);
                }

                $sheet->setFreeze('A2');
            });
        }
    })->export('xlsx');
}

//PREMIOSMTM


public function actualizarPremiosMTM(Request $request, $id)
{
    $r = RegistroPremiosMTM::findOrFail($id);

    $r->fecha_PremiosMTM = $request->input('fecha_PremiosMTM').'-01';
    $r->casino           = $request->input('casinoPremiosMTM');

    $r->cancel           = $request->input('cancel_PremiosMTM');
    $r->cancel_usd       = $request->input('cancel_usd_PremiosMTM');

    $r->progresivos      = $request->input('progre_PremiosMTM');
    $r->progresivos_usd  = $request->input('progre_usd_PremiosMTM');

    $r->jackpots         = $request->input('jack_PremiosMTM');
    $r->jackpots_usd     = $request->input('jack_usd_PremiosMTM');

    $r->total            = $request->input('total_PremiosMTM');
    $r->total_usd        = $request->input('total_usd_PremiosMTM');

    $r->save();

    $saved = 0;
    $files = Arr::wrap($request->file('uploadPremiosMTM'));
    foreach ($files as $file) {
        if (!($file instanceof UploadedFile) || !$file->isValid()) continue;

        $base = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
        $ext  = $file->getClientOriginalExtension();
        $safe = preg_replace('/\s+/', '_', $base);
        $name = time().'_'.Str::random(6).'_'.$safe.($ext ? '.'.$ext : '');

        $file->storeAs('public/RegistroPremiosMTM', $name);

        $r->archivos()->create([
            'path'       => $name,
            'usuario'    => UsuarioController::getInstancia()->quienSoy()['usuario']['id_usuario'],
            'fecha_toma' => date('Y-m-d H:i:s'),
        ]);

        $saved++;
    }

    return response()->json(['success' => true, 'saved_files' => $saved]);
}

public function guardarPremiosMTM(Request $request){

      $path = null;

      try {
          DB::beginTransaction();

          $PremiosMTM = new RegistroPremiosMTM();
          $PremiosMTM->fecha_PremiosMTM = $request->fecha_PremiosMTM.'-01';



          $PremiosMTM->cancel = $request->cancel_PremiosMTM;
          $PremiosMTM->cancel_usd = $request->cancel_usd_PremiosMTM;

          $PremiosMTM->progresivos = $request->progre_PremiosMTM;
          $PremiosMTM->progresivos_usd = $request->progre_usd_PremiosMTM;

          $PremiosMTM->jackpots = $request->jack_PremiosMTM;
          $PremiosMTM->jackpots_usd = $request->jack_usd_PremiosMTM;

          $PremiosMTM->total = $request->total_PremiosMTM;
          $PremiosMTM->total_usd = $request->total_usd_PremiosMTM;


          $PremiosMTM->casino = $request->casinoPremiosMTM;
          $PremiosMTM->fecha_toma = date('Y-m-d h:i:s', time());
          $PremiosMTM->usuario = UsuarioController::getInstancia()->quienSoy()['usuario']['id_usuario'];
          $PremiosMTM->save();

          $files = Arr::wrap($request->file('uploadPremiosMTM'));
          foreach ($files as $file) {
              if (!($file instanceof UploadedFile) || !$file->isValid()) continue;

              $base = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
              $ext  = $file->getClientOriginalExtension();
              $safe = preg_replace('/\s+/', '_', $base);
              $filename = time().'_'.Str::random(6).'_'.$safe.($ext?'.'.$ext:'');

              $file->storeAs('public/RegistroPremiosMTM', $filename);

              $PremiosMTM->archivos()->create([
                  'path'       => $filename,
                  'usuario'    => $PremiosMTM->usuario,
                  'fecha_toma' => date('Y-m-d H:i:s'),
              ]);
          }


          DB::commit();

          return response()->json([
            'success' => true,
             'id' => $PremiosMTM->id_registroPremiosMTM,
             'PremiosMTM'  => $request->all(),
             'path'    => $path,
             'url'     => Storage::url($path)
           ]);
      } catch (\Exception $e) {
          DB::rollBack();
          return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
      }

}

public function ultimasPremiosMTM(Request $request)
{
    $page    = max(1, (int)$request->query('page', 1));
    $perPage = max(1, (int)$request->query('page_size', 20));

    $user = Usuario::find(session('id_usuario'));
    $allowedCasinoIds = $user->casinos->pluck('id_casino')->toArray();


    $query = RegistroPremiosMTM::with('casinoPremiosMTM')
              ->withCount('archivos')
              ->whereIn('casino', $allowedCasinoIds)
              ->orderBy('fecha_PremiosMTM', 'desc')
              ->orderBy('casino', 'desc');


    if ($c = $request->query('id_casino')) {
      $query->where('casino', $c);
    }
    if ($desde = $request->query('desde')){
      $desde = $desde."-01";
      $query->where('fecha_PremiosMTM',">=",$desde);
    }
    if ($hasta = $request->query('hasta')){
      $hasta = $hasta."-01";
      $query->where('fecha_PremiosMTM',"<=",$hasta);
    }

    $total = $query->count();

    $registros = $query
        ->skip(($page - 1) * $perPage)
        ->take($perPage)
        ->get();

    $datos = $registros->map(function($r) {
        return [
            'id_registroPremiosMTM' => $r->id_registroPremiosMTM,
            'fecha_PremiosMTM'   => $r->fecha_PremiosMTM,
            'total' => $r->total,
            'total_usd' => $r->total_usd,
            'casino'      => $r->casinoPremiosMTM ? $r->casinoPremiosMTM->nombre : '-',
	  'tiene_archivos' => $r->archivos_count>0,        ];
    });

    return response()->json([
        'registros'  => $datos,
        'pagination' => [
            'current_page' => $page,
            'per_page'     => $perPage,
            'total'        => $total,
        ],
    ]);
}


public function eliminarPremiosMTM($id){
  $PremiosMTM = RegistroPremiosMTM::findOrFail($id);
  if(is_null($PremiosMTM)) return 0;
  RegistroPremiosMTM::destroy($id);
  return 1;
}



public function archivosPremiosMTM($id)
{
    $PremiosMTM = RegistroPremiosMTM::with('archivos')->findOrFail($id);

    $files = $PremiosMTM->archivos->map(function($a){
        return [
            'id'     => $a->id_registro_archivo,
            'nombre' => basename($a->path),
            'url'    => \Illuminate\Support\Facades\Storage::url($a->path),
            'fecha'  => $a->fecha_toma,
        ];
    });

    return response()->json($files->values());
}


public function llenarPremiosMTMEdit($id){
    $p = RegistroPremiosMTM::findOrFail($id);
    return response()->json([
        'fecha'      => $p->fecha_PremiosMTM,
        'casino'     => $p->casino,
        'cancel'     => $p->cancel,
        'cancel_usd' => $p->cancel_usd,
        'progre'     => $p->progresivos,
        'progre_usd' => $p->progresivos_usd,
        'jack'       => $p->jackpots,
        'jack_usd'   => $p->jackpots_usd,
        'total'      => $p->total,
        'total_usd'  => $p->total_usd,
    ]);
}


public function llenarPremiosMTM($id){
  $PremiosMTM = RegistroPremiosMTM::with('casinoPremiosMTM')->findOrFail($id);
  if(is_null($PremiosMTM)) return 0;

  return response()->json([

    'fecha' => $PremiosMTM->fecha_PremiosMTM,
    'casino' => $PremiosMTM->casinoPremiosMTM ? $PremiosMTM->casinoPremiosMTM->nombre : '-',
    'total' => $PremiosMTM->total,
    'total_usd' => $PremiosMTM->total_usd,
    'progresivos' => $PremiosMTM->progresivos,
    'progresivos_usd' => $PremiosMTM->progresivos_usd,
    'jackpots' => $PremiosMTM->jackpots,
    'jackpots_usd' => $PremiosMTM->jackpots_usd,
    'cancel' => $PremiosMTM->cancel,
    'cancel_usd' => $PremiosMTM->cancel_usd,

  ]);

}

public function descargarPremiosMTMCsv(Request $request)
{
    $casinoId = $request->query('casino');
    $desde    = $request->query('desde');
    $hasta    = $request->query('hasta');
    $user = Usuario::find(session('id_usuario'));
    $allowedCasinoIds = $user->casinos->pluck('id_casino')->toArray();

    $registros = RegistroPremiosMTM::with('casinoPremiosMTM')
        ->whereIn('casino',$allowedCasinoIds)
        ->when($casinoId && $casinoId != 4, function ($q) use ($casinoId) {
            $q->where('casino', $casinoId);
        })
        ->when($desde, function ($q) use ($desde) {
            $q->where('fecha_PremiosMTM', '>=', $desde . '-01');
        })
        ->when($hasta, function ($q) use ($hasta) {
            $q->where('fecha_PremiosMTM', '<=', $hasta . '-01');
        })
        ->orderBy('casino')
        ->orderBy('fecha_PremiosMTM')
        ->get();

    $csv = [];
    $csv[] = ['Casino', 'Año', 'Mes', 'Cancel Credits', 'Jackpots', 'Progresivos', 'Total',
              'Cancel Credits USD', 'Jackpots USD', 'Progresivos USD', 'Total USD'];

    foreach ($registros as $r) {
        setlocale(LC_TIME, 'es_ES.UTF-8');
        $anio     = date('Y', strtotime($r->fecha_PremiosMTM));
        $mes      = strftime('%B', strtotime($r->fecha_PremiosMTM));
        $casino   = $r->casinoPremiosMTM ? $r->casinoPremiosMTM->nombre : '-';
        $cancel = number_format($r->cancel, 2, '.', '');
        $cancel_usd = number_format($r->cancel_usd, 2, '.', '');

        $progre = number_format($r->progresivos, 2, '.', '');
        $progre_usd = number_format($r->progresivos_usd, 2, '.', '');

        $jackpots = number_format($r->jackpots, 2, '.', '');
        $jackpots_usd = number_format($r->jackpots_usd, 2, '.', '');

        $total = number_format($r->total, 2, '.', '');
        $total_usd = number_format($r->total_usd, 2, '.', '');

        $csv[] = [
            $casino,
            $anio,
            ucfirst($mes),
            $cancel,
            $jackpots,
            $progre,
            $total,
            $cancel_usd,
            $jackpots_usd,
            $progre_usd,
            $total_usd,

        ];
    }

    $nombreCasino = $casinoId == 4 ? 'todos' : Casino::find($casinoId)->nombre ?? 'desconocido';
    $filename = "PremiosMTM_{$nombreCasino}_" . date('Ymd_His') . ".csv";

    $handle = fopen('php://temp', 'r+');
    foreach ($csv as $linea) {
        fputcsv($handle, $linea, ',');
    }
    rewind($handle);
    $contenido = stream_get_contents($handle);
    fclose($handle);

    return response($contenido, 200, [
        'Content-Type'        => 'text/csv; charset=UTF-8',
        'Content-Disposition' => "attachment; filename=\"$filename\"",
        'Cache-Control'       => 'no-store, no-cache'
    ]);
}

public function descargarPremiosMTMXlsx(Request $request)
{
    $casinoId = $request->query('casino');
    $desde = $request->query('desde');
    $hasta = $request->query('hasta');

    $casino = Casino::findOrFail($casinoId);

    $query = RegistroPremiosMTM::select([
        DB::raw("YEAR(fecha_PremiosMTM) AS anio"),
        DB::raw("MONTHNAME(fecha_PremiosMTM) AS Mes"),
        DB::raw("cancel"),
        DB::raw("cancel_usd"),
        DB::raw("progresivos"),
        DB::raw("progresivos_usd"),
        DB::raw("jackpots"),
        DB::raw("jackpots_usd"),
        DB::raw("total"),
        DB::raw("total_usd"),



    ])
    ->where('casino', $casinoId);

    if ($desde) {
        $query->where('fecha_PremiosMTM', '>=', $desde . '-01');
    }

    if ($hasta) {
        $query->where('fecha_PremiosMTM', '<=', $hasta . '-31');
    }

    $datos = $query->orderBy('fecha_PremiosMTM')->get()->groupBy('anio');

    $totalesPorAnio = $datos->map(function($grupo, $anio) {
    return [
        'anio' => $anio,
        'total_cancel' => $grupo->sum('cancel'),
        'total_cancel_usd'   => $grupo->sum('cancel_usd'),
        'total_progre' => $grupo->sum('progresivos'),
        'total_progre_usd' => $grupo->sum('progresivos_usd'),
        'total_jackpots' => $grupo->sum('jackpots'),
        'total_jackpots_usd' => $grupo->sum('jackpots_usd'),
        'total_total' => $grupo->sum('total'),
        'total_total_usd' => $grupo->sum('total_usd'),
        ];
    });

    $filename = "registro_PremiosMTM_" . str_replace(' ', '_', strtolower($casino->nombre));

    return Excel::create($filename, function($excel) use ($datos, $casino, $casinoId, $totalesPorAnio) {
        $excel->sheet('PremiosMTM', function($sheet) use ($datos, $casino, $casinoId, $totalesPorAnio) {
            $fila = 1;

            $sheet->row($fila, [
                'Mes',
                'Cancel Credits',
                'Jackpots',
                'Progresivos',
                'Total',
                'Cancel Credits USD',
                'Jackpots USD',
                'Progresivos USD',
                'Total USD',

            ]);
            $sheet->cells("A1:I1", function($cells) use ($casinoId) {
                switch ($casinoId) {
                case 1:
                    $color = '#339966';

                    break;
                case 2:
                    $color = '#ff0000';

                    break;
                case 3:
                    $color = '#ffcc00';

                    break;
                default:
                    $color = '#222222';

            }

                $cells->setBackground($color);
                $cells->setFontColor('#000000');
                $cells->setFontWeight('bold');
                $cells->setAlignment('center');
            });

            $sheet->getStyle("A1:I1")->getAlignment()->setWrapText(true)->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
            $sheet->setHeight(1, 50);
            $sheet->cells("A1:I999", function($cells){
                $cells->setFontFamily('Arial');
                $cells->setFontSize(10);
            });

            $fila++;
            foreach ($datos as $anio => $registros) {
                $sheet->mergeCells("A{$fila}:I{$fila}");
                $sheet->setCellValue("A{$fila}", $anio);
                $sheet->cells("A{$fila}:I{$fila}", function($cells){
                    $cells->setBackground('#CCCCCC');
                    $cells->setFontWeight('bold');
                    $cells->setFontSize(13);
                    $cells->setAlignment('center');
                });
                $sheet->getStyle("A{$fila}:I{$fila}")->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
                $sheet->setHeight($fila, 20);
                $fila++;

                foreach ($registros as $r) {
                    setlocale(LC_TIME, 'es_ES.UTF-8');
                    $mesEsp = ucfirst(strftime('%B', strtotime($r->{'Mes'} . ' 1')));

                    $sheet->row($fila, [
                        $mesEsp,
                        "$ " . number_format($r->{'cancel'}, 2, ',', '.'),
                        "$ " . number_format($r->{'jackpots'}, 2, ',', '.'),
                        "$ " . number_format($r->{'progresivos'}, 2, ',', '.'),
                        "$ " . number_format($r->{'total'}, 2, ',', '.'),
                        "USD " . number_format($r->{'cancel_usd'}, 2, ',', '.'),
                        "USD " . number_format($r->{'jackpots_usd'}, 2, ',', '.'),
                        "USD " . number_format($r->{'progresivos_usd'}, 2, ',', '.'),
                        "USD " . number_format($r->{'total_usd'}, 2, ',', '.'),

                    ]);
                    $sheet->cells("A{$fila}", function($cells){
                        $cells->setBackground('#FFFF99');
                        $cells->setFontWeight('bold');
                        $cells->setAlignment('left');
                    });

                    $sheet->cells("B{$fila}:I{$fila}", function($cells){
                        $cells->setAlignment('center');
                    });

                    $sheet->getStyle("A{$fila}:I{$fila}")->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
                    $fila++;
                }
                $totales = $totalesPorAnio[$anio];
                	$sheet->row($fila, [
                	    'TOTAL ' . $anio,
                	    "$ " . number_format($totales['total_cancel'], 2, ',', '.'),
                      "$ " . number_format($totales['total_jackpots'], 2, ',', '.'),
                      "$ " . number_format($totales['total_progre'], 2, ',', '.'),
                      "$ " . number_format($totales['total_total'], 2, ',', '.'),
                      "USD " . number_format($totales['total_cancel_usd'], 2, ',', '.'),
                      "USD " . number_format($totales['total_jackpots_usd'], 2, ',', '.'),
                      "USD " . number_format($totales['total_progre_usd'], 2, ',', '.'),
                      "USD " . number_format($totales['total_total_usd'], 2, ',', '.'),

                	]);
                	$sheet->cells("A{$fila}", function($cells){
                	    $cells->setBackground('#CCCCFF');
                	    $cells->setFontWeight('bold');
                	});
                	$sheet->cells("B{$fila}:I{$fila}", function($cells){
                	    $cells->setAlignment('center');
                	    $cells->setFontWeight('bold');
                	});
                	$fila++;
            }

            $sheet->getStyle("A1:I1")->applyFromArray([
                'borders' => [
                    'allborders' => [
                        'style' => PHPExcel_Style_Border::BORDER_THICK,
                        'color' => ['argb' => 'FF000000']
                    ]
                ]
            ]);

            $sheet->getStyle("A2:I" . ($fila -1))->applyFromArray([
                'borders' => [
                    'allborders' => [
                        'style' => PHPExcel_Style_Border::BORDER_THIN,
                        'color' => ['argb' => 'FF000000']
                    ]
                ]
            ]);

            $anchos = [
                9, 22, 22, 22, 22, 22, 22, 22,
                22, 22, 22, 18, 15, 15, 18, 20
            ];
            foreach (range('A', 'I') as $i => $col) {
                $sheet->setWidth($col, $anchos[$i]);
            }

            $sheet->setFreeze('A2');
        });
    })->export('xlsx');
}

public function descargarPremiosMTMXlsxTodos(Request $request)
{
    $desde = $request->query('desde');
    $hasta = $request->query('hasta');

    $user = Usuario::find(session('id_usuario'));
    $casinos = $user->casinos->pluck('nombre', 'id_casino');

    return Excel::create('registro_PremiosMTM_todos', function($excel) use ($casinos, $desde, $hasta) {
        foreach ($casinos as $casinoId => $casinoNombre) {

            $datos = RegistroPremiosMTM::select([
              DB::raw("YEAR(fecha_PremiosMTM) AS anio"),
              DB::raw("MONTHNAME(fecha_PremiosMTM) AS Mes"),
              DB::raw("cancel"),
              DB::raw("cancel_usd"),
              DB::raw("progresivos"),
              DB::raw("progresivos_usd"),
              DB::raw("jackpots"),
              DB::raw("jackpots_usd"),
              DB::raw("total"),
              DB::raw("total_usd"),
            ])
            ->where('casino', $casinoId)
            ->when($desde, function($q) use ($desde) {
                return $q->where('fecha_PremiosMTM', '>=', $desde . '-01');
            })
            ->when($hasta, function($q) use ($hasta) {
                return $q->where('fecha_PremiosMTM', '<=', $hasta . '-31');
            })
            ->orderBy('fecha_PremiosMTM')
            ->get()
            ->groupBy('anio');

            $totalesPorAnio = $datos->map(function($grupo, $anio) {
            return [
              'anio' => $anio,
              'total_cancel' => $grupo->sum('cancel'),
              'total_cancel_usd'   => $grupo->sum('cancel_usd'),
              'total_progre' => $grupo->sum('progresivos'),
              'total_progre_usd' => $grupo->sum('progresivos_usd'),
              'total_jackpots' => $grupo->sum('jackpots'),
              'total_jackpots_usd' => $grupo->sum('jackpots_usd'),
              'total_total' => $grupo->sum('total'),
              'total_total_usd' => $grupo->sum('total_usd'),
                ];
            });

            $excel->sheet($casinoNombre, function($sheet) use ( $datos, $casinoId, $totalesPorAnio) {
                $fila = 1;

                $sheet->row($fila, [
                  'Mes',
                  'Cancel Credits',
                  'Jackpots',
                  'Progresivos',
                  'Total',
                  'Cancel Credits USD',
                  'Jackpots USD',
                  'Progresivos USD',
                  'Total USD',


                ]);

                $sheet->cells("A1:I1", function($cells) use ($casinoId) {
                    switch ($casinoId) {
                case 1:
                    $color = '#339966';

                    break;
                case 2:
                    $color = '#ff0000';

                    break;
                case 3:
                    $color = '#ffcc00';

                    break;
                default:
                    $color = '#222222';

            }
                    $cells->setBackground($color);
                    $cells->setFontColor('#000000');
                    $cells->setFontWeight('bold');
                    $cells->setAlignment('center');
                });

                $sheet->getStyle("A1:I1")->getAlignment()
                    ->setWrapText(true)
                    ->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
                $sheet->setHeight(1, 50);


                $fila++;

                $sheet->cells("A1:I999", function($cells) {
                    $cells->setFontFamily('Arial');
                    $cells->setFontSize(10);
                });

                foreach ($datos as $anio => $registros) {
                    $sheet->mergeCells("A{$fila}:I{$fila}");
                    $sheet->setCellValue("A{$fila}", $anio);
                    $sheet->cells("A{$fila}:I{$fila}", function($cells){
                        $cells->setBackground('#CCCCCC');
                        $cells->setFontWeight('bold');
                        $cells->setFontSize(13);
                        $cells->setAlignment('center');
                    });
                    $sheet->setHeight($fila, 20);
                    $fila++;

                    foreach ($registros as $r) {
                        setlocale(LC_TIME, 'es_ES.UTF-8');
                        $mesEsp = ucfirst(strftime('%B', strtotime($r->{'Mes'} . ' 1')));

                        $sheet->row($fila, [
                          $mesEsp,
                          "$ " . number_format($r->{'cancel'}, 2, ',', '.'),
                          "$ " . number_format($r->{'jackpots'}, 2, ',', '.'),
                          "$ " . number_format($r->{'progresivos'}, 2, ',', '.'),
                          "$ " . number_format($r->{'total'}, 2, ',', '.'),
                          "USD " . number_format($r->{'cancel_usd'}, 2, ',', '.'),
                          "USD " . number_format($r->{'jackpots_usd'}, 2, ',', '.'),
                          "USD " . number_format($r->{'progresivos_usd'}, 2, ',', '.'),
                          "USD " . number_format($r->{'total_usd'}, 2, ',', '.'),
                        ]);

                        $sheet->cells("A{$fila}", function($cells){
                            $cells->setBackground('#FFFF99');
                            $cells->setFontWeight('bold');
                            $cells->setAlignment('left');
                        });
                        $sheet->cells("B{$fila}:I{$fila}", function($cells){
                            $cells->setAlignment('center');
                        });
                        $fila++;
                    }
                    $totales = $totalesPorAnio[$anio];
                    	$sheet->row($fila, [
                        'TOTAL ' . $anio,
                        "$ " . number_format($totales['total_cancel'], 2, ',', '.'),
                        "$ " . number_format($totales['total_jackpots'], 2, ',', '.'),
                        "$ " . number_format($totales['total_progre'], 2, ',', '.'),
                        "$ " . number_format($totales['total_total'], 2, ',', '.'),
                        "USD " . number_format($totales['total_cancel_usd'], 2, ',', '.'),
                        "USD " . number_format($totales['total_jackpots_usd'], 2, ',', '.'),
                        "USD " . number_format($totales['total_progre_usd'], 2, ',', '.'),
                        "USD " . number_format($totales['total_total_usd'], 2, ',', '.'),
                    	]);
                    	$sheet->cells("A{$fila}", function($cells){
                    	    $cells->setBackground('#CCCCFF');
                    	    $cells->setFontWeight('bold');
                    	});
                    	$sheet->cells("B{$fila}:I{$fila}", function($cells){
                    	    $cells->setAlignment('center');
                    	    $cells->setFontWeight('bold');
                    	});
                    	$fila++;
                }



                $sheet->getStyle("A1:I1")->applyFromArray([
                    'borders' => [
                        'allborders' => [
                            'style' => PHPExcel_Style_Border::BORDER_THICK,
                            'color' => ['argb' => 'FF000000']
                        ]
                    ]
                ]);
                $sheet->getStyle("A2:I" . ($fila - 1))->applyFromArray([
                    'borders' => [
                        'allborders' => [
                            'style' => PHPExcel_Style_Border::BORDER_THIN,
                            'color' => ['argb' => 'FF000000']
                        ]
                    ]
                ]);

                $anchos = [9, 25, 25, 25, 18, 16, 14, 18,
                    16, 14, 20, 18, 14, 15, 18, 20, 20,
                    20, 20, 20, 20, 20];
                foreach (range('A', 'I') as $i => $col) {
                    $sheet->setWidth($col, $anchos[$i]);
                }

                $sheet->setFreeze('A2');
            });
        }
    })->export('xlsx');
}

//AUT. DIRECTORES
public function guardarAutDirectores_director(Request $request){

      $path = null;


      try {
          DB::beginTransaction();

          $AutDirectores = new RegistroAutDirectores_director();

          $AutDirectores->nombre = $request->nombre_AutDirectores_director;
          $AutDirectores->cuit = $request->cuit_AutDirectores_director;




          $AutDirectores->casino = $request->casinoAutDirectores_director;
          $AutDirectores->fecha_toma = date('Y-m-d h:i:s', time());
          $AutDirectores->usuario = UsuarioController::getInstancia()->quienSoy()['usuario']['id_usuario'];
          $AutDirectores->save();

          DB::commit();

          return response()->json([
            'success' => true,
             'id' => $AutDirectores->id_registroAutDirectores_director,
             'AutDirectores_director'  => $request->all(),
           ]);
      } catch (\Exception $e) {
          DB::rollBack();
          return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
      }

}

public function llenarAutDirectores_director($id)
{
    $d = \App\RegistroAutDirectores_director::findOrFail($id);

    return response()->json([
        'id'     => $d->id_registroAutDirectores_director,
        'nombre' => $d->nombre,
        'cuit'   => $d->cuit,
        'estado' => $d->estado ?? null,
        'casino' => $d->casino ?? null,
    ]);
}

public function actualizarAutDirectores_director(Request $request, $id)
{


    try {
        DB::beginTransaction();

        $d = RegistroAutDirectores_director::findOrFail($id);

        $d->nombre = $request->input('ModifAutDirectores_director_nombre');
        $d->cuit   = $request->input('ModifAutDirectores_director_cuit');

        $d->habilitado = $request->input('ModifAutDirectores_director_estado');

        $d->save();

        DB::commit();
        return response()->json(['success' => true, 'id' => $d->id_registroAutDirectores_director]);

    } catch (\Exception $e) {
        DB::rollBack();
        return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
    }
}

public function AutDirectoresHabilitadosPorCasino($casinoId){
  $directores = RegistroAutDirectores_director::where('casino',$casinoId)
                                              ->where('habilitado',1)
                                              ->orderBy('nombre')
                                              ->get(['id_registroAutDirectores_director','nombre','cuit']);
  return response()->json($directores);
}

public function getAutDirectores(){
  $directores = RegistroAutDirectores_director::with('casinoAutDirectores_director')
                                              ->get();
  $directores->transform(function($d){
        return [
            'id'      => $d->id_registroAutDirectores_director,
            'nombre'  => $d->nombre,
            'cuit'    => $d->cuit,
            'casino'  => $d->casinoAutDirectores_director ? $d->casinoAutDirectores_director->nombre : null,
            'habilitado' => $d->habilitado,
        ];
    });

  return response()->json($directores);
}

public function AutDirectoresHabilitarDirector($id)
    {
        $d = RegistroAutDirectores_director::findOrFail($id);
        $d->habilitado = $d->habilitado ? 0 : 1;
        $d->save();
        return response()->json(array('ok'=>true,'habilitado'=>$d->habilitado));
    }

public function AutDirectoresEliminarDirector($id){

    RegistroAutDirectores_director::findOrFail($id)->delete();
    return response()->json(array('ok'=>true));
}

public function guardarAutDirectores_autorizacion(Request $request){

      $path = null;


      try {
          DB::beginTransaction();

          $AutDirectores = new RegistroAutDirectores();
          $AutDirectores->fecha_AutDirectores = $request->fecha_AutDirectores_autorizacion.'-01';




          $AutDirectores->casino = $request->casinoAutDirectores_autorizacion;
          $AutDirectores->fecha_toma = date('Y-m-d h:i:s', time());
          $AutDirectores->usuario = UsuarioController::getInstancia()->quienSoy()['usuario']['id_usuario'];
          $AutDirectores->save();

          $directores = $request->input('directores', []);
          $obsMap     = $request->input('observacion', []);

          foreach($directores as $dirId => $autoriza ){
              $autorizacion = new RegistroAutDirectores_autorizacion;
              $autorizacion->autoriza = (int)$autoriza;
              $autorizacion->registro = $AutDirectores->id_registroAutDirectores;
              $autorizacion->director = $dirId;
              $autorizacion->observaciones= $obsMap[$dirId];
              $autorizacion->save();
          }
          $files = Arr::wrap($request->file('uploadAutDirectores'));
                    foreach ($files as $file) {
                        if (!($file instanceof UploadedFile) || !$file->isValid()) continue;

                        $base = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
                        $ext  = $file->getClientOriginalExtension();
                        $safe = preg_replace('/\s+/', '_', $base);
                        $filename = time().'_'.Str::random(6).'_'.$safe.($ext?'.'.$ext:'');

                        $file->storeAs('public/RegistroAutDirectores', $filename);

                        $AutDirectores->archivos()->create([
                            'path'       => $filename,
                            'usuario'    => $AutDirectores->usuario,
                            'fecha_toma' => date('Y-m-d H:i:s'),
                        ]);
                    }

          DB::commit();

          return response()->json([
            'success' => true,
             'id' => $AutDirectores->id_registroAutDirectores,
             'AutDirectores'  => $request->all(),
             'path'    => $path,
             'url'     => Storage::url($path)
           ]);
      } catch (\Exception $e) {
          DB::rollBack();
          return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
      }

}


public function archivosAutDirectores($id)
{
    $AutDirectores = RegistroAutDirectores::with('archivos')->findOrFail($id);

    $files = $AutDirectores->archivos->map(function($a){
        return [
            'id'     => $a->id_registro_archivo,
            'nombre' => basename($a->path),
            'url'    => \Illuminate\Support\Facades\Storage::url($a->path),
            'fecha'  => $a->fecha_toma,
        ];
    });

    return response()->json($files->values());
}

public function llenarAutDirectoresEdit($id)
{
    $cab = \App\RegistroAutDirectores::findOrFail($id);

    $rows = \DB::table('registroAutDirectores_autorizacion as a')
        ->leftJoin('registroAutDirectores_director as d','d.id_registroAutDirectores_director','=','a.director')
        ->where('a.registro', $id)
        ->orderBy('d.nombre')
        ->get([
            \DB::raw('a.director as id'),
            \DB::raw('COALESCE(d.nombre,"(director eliminado)") as nombre'),
            'd.cuit',
            \DB::raw('a.autoriza'),
            \DB::raw('a.observaciones'),
        ]);

    return response()->json([
        'fecha'      => $cab->fecha_AutDirectores,
        'casino'     => $cab->casino,
        'directores' => $rows->map(function($x){
            return [
                'id'          => (int)$x->id,
                'nombre'      => (string)$x->nombre,
                'cuit'        => (string)$x->cuit,
                'autoriza'    => (int)$x->autoriza,
                'observacion' => (string)$x->observaciones,
            ];
        })->values(),
    ]);
}
public function actualizarAutDirectores(Request $request, $id)
{
    $r = \App\RegistroAutDirectores::findOrFail($id);

    $fecha = $request->input('fecha_AutDirectores_autorizacion');
    if ($fecha) $r->fecha_AutDirectores = $fecha.'-01';

    $casino = $request->input('casinoAutDirectores_autorizacion');
    if ($casino !== null && $casino !== '') $r->casino = $casino;

    $r->save();

    $postAut = (array)$request->input('directores', []);
    $postObs = (array)$request->input('observacion', []);

    $detalles = \DB::table('registroAutDirectores_autorizacion')
        ->where('registro', $id)
        ->pluck('id_registroAutDirectores_autorizacion', 'director');

    foreach ($detalles as $dirId => $rowPk) {
        \DB::table('registroAutDirectores_autorizacion')
            ->where('id_registroAutDirectores_autorizacion', $rowPk)
            ->update([
                'autoriza'      => isset($postAut[$dirId]) ? (int)$postAut[$dirId] : 0,
                'observaciones' => array_key_exists($dirId, $postObs) ? $postObs[$dirId] : null,
            ]);
    }

    $saved = 0;
    $files = \Illuminate\Support\Arr::wrap($request->file('uploadAutDirectores'));
    foreach ($files as $file) {
        if (!($file instanceof \Illuminate\Http\UploadedFile) || !$file->isValid()) continue;

        $base = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
        $ext  = $file->getClientOriginalExtension();
        $safe = preg_replace('/\s+/', '_', $base);
        $name = time().'_'.\Illuminate\Support\Str::random(6).'_'.$safe.($ext?'.'.$ext:'');

        $file->storeAs('public/RegistroAutDirectores', $name);

        $r->archivos()->create([
            'path'       => $name,
            'usuario'    => \App\Http\Controllers\UsuarioController::getInstancia()->quienSoy()['usuario']['id_usuario'],
            'fecha_toma' => date('Y-m-d H:i:s'),
        ]);

        $saved++;
    }

    return response()->json(['success' => true, 'saved_files' => $saved]);
}


public function ultimasAutDirectores(Request $request)
{
    $page    = max(1, (int)$request->query('page', 1));
    $perPage = max(1, (int)$request->query('page_size', 20));

    $user = Usuario::find(session('id_usuario'));
    $allowedCasinoIds = $user->casinos->pluck('id_casino')->toArray();


    $query = RegistroAutDirectores::with('casinoAutDirectores')
              ->whereIn('casino', $allowedCasinoIds)
                        ->withCount('archivos')
              ->orderBy('fecha_AutDirectores', 'desc')
              ->orderBy('casino', 'desc');


    if ($c = $request->query('id_casino')) {
      $query->where('casino', $c);
    }
    if ($desde = $request->query('desde')){
      $desde = $desde."-01";
      $query->where('fecha_AutDirectores',">=",$desde);
    }
    if ($hasta = $request->query('hasta')){
      $hasta = $hasta."-01";
      $query->where('fecha_AutDirectores',"<=",$hasta);
    }

    $total = $query->count();

    $registros = $query
        ->skip(($page - 1) * $perPage)
        ->take($perPage)
        ->get();

    $datos = $registros->map(function($r) {
        return [
            'id_registroAutDirectores' => $r->id_registroAutDirectores,
            'fecha_AutDirectores'   => $r->fecha_AutDirectores,
            'casino'      => $r->casinoAutDirectores ? $r->casinoAutDirectores->nombre : '-',
            'tiene_archivos' => $r->archivos_count>0,
          ];
    });

    return response()->json([
        'registros'  => $datos,
        'pagination' => [
            'current_page' => $page,
            'per_page'     => $perPage,
            'total'        => $total,
        ],
    ]);
}


public function eliminarAutDirectores($id){
  $AutDirectores = RegistroAutDirectores::findOrFail($id);
  if(is_null($AutDirectores)) return 0;
  RegistroAutDirectores::destroy($id);
  return 1;
}

public function llenarAutDirectores($id){
  $AutDirectores = RegistroAutDirectores::with(['casinoAutDirectores',
                                              'autorizaciones' => function ($q){
                                                    $q->with('director');
                                                    }
                                          ])->findOrFail($id);




  return response()->json([

    'fecha' => $AutDirectores->fecha_AutDirectores,
    'casino' => $AutDirectores->casinoAutDirectores ? $AutDirectores->casinoAutDirectores->nombre : '-',
    'autorizaciones' => $AutDirectores->autorizaciones,
  ]);

}

public function descargarAutDirectoresCsv(Request $request)
{
    $casinoId = $request->query('casino');
    $desde    = $request->query('desde');
    $hasta    = $request->query('hasta');

    $user = Usuario::find(session('id_usuario'));
    $allowedCasinoIds = $user->casinos->pluck('id_casino')->toArray();

    $registros = RegistroAutDirectores::with([
            'casinoAutDirectores:id_casino,nombre',
            'autorizaciones' => function ($q) {
                $q->with('director');
            }
        ])
        ->whereIn('casino', $allowedCasinoIds)
        ->when($casinoId && (int)$casinoId !== 4, function ($q) use ($casinoId) {
            $q->where('casino', $casinoId);
        })
        ->when($desde, function ($q) use ($desde) {
            $q->where('fecha_AutDirectores', '>=', $desde . '-01');
        })
        ->when($hasta, function ($q) use ($hasta) {
            $q->where('fecha_AutDirectores', '<=', $hasta . '-01');
        })
        ->orderBy('casino')
        ->orderBy('fecha_AutDirectores')
        ->get();
    $csv = [];
    $csv[] = ['Casino','Año','Mes','Director','CUIT','Autoriza','Observación'];

    setlocale(LC_TIME, 'es_ES.UTF-8');
    foreach ($registros as $r) {
        $anio   = date('Y', strtotime($r->fecha_AutDirectores));
        $mes    = strftime('%B', strtotime($r->fecha_AutDirectores));
        $casino = $r->casinoAutDirectores ? $r->casinoAutDirectores->nombre : '-';

        foreach ($r->autorizaciones as $a) {
          $dir = $a->relationLoaded('director')
                 ? $a->getRelation('director')
                 : $a->director()->first();

          $nombre   = $dir->nombre ?? '';
          $cuit     = $dir->cuit ?? '';
          $autoriza = $a->autoriza ? 1 : 0;
          $obs      = $a->observaciones ?? '';

            $csv[] = [
                $casino,
                $anio,
                ucfirst($mes),
                $nombre,
                $cuit,
                $autoriza,
                $obs,
            ];
        }
    }

    $nombreCasino = ((int)$casinoId === 4 || !$casinoId)
        ? 'todos'
        : (Casino::find($casinoId)->nombre ?? 'desconocido');

    $filename = "AutDirectores_{$nombreCasino}_" . date('Ymd_His') . ".csv";

    $handle = fopen('php://temp', 'r+');

    foreach ($csv as $linea) {
        fputcsv($handle, $linea, ',');
    }
    rewind($handle);
    $contenido = stream_get_contents($handle);
    fclose($handle);

    return response($contenido, 200, [
        'Content-Type'        => 'text/csv; charset=UTF-8',
        'Content-Disposition' => "attachment; filename=\"$filename\"",
        'Cache-Control'       => 'no-store, no-cache',
    ]);
}


public function descargarAutDirectoresXlsx(Request $request)
{
    $casinoId = $request->query('casino');
    $desde    = $request->query('desde');
    $hasta    = $request->query('hasta');

    $casino = Casino::findOrFail($casinoId);

    $directores = RegistroAutDirectores_director::where('casino', $casinoId)
        ->orderBy('nombre')
        ->get([
            'id_registroAutDirectores_director as id',
            'nombre',
            'cuit'
        ]);

    $registros = RegistroAutDirectores::with([
            'autorizaciones.director',
            'casinoAutDirectores'
        ])
        ->where('casino', $casinoId)
        ->when($desde, function($q) use ($desde){
            $q->where('fecha_AutDirectores','>=', $desde.'-01');
        })
        ->when($hasta, function($q) use ($hasta){
            $q->where('fecha_AutDirectores','<=', $hasta.'-31');
        })
        ->orderBy('fecha_AutDirectores')
        ->get()
        ->groupBy(function($r){
            return date('Y', strtotime($r->fecha_AutDirectores));
        });

    $filename = 'aut_directores_'.str_replace(' ','_', strtolower($casino->nombre)).'_'.date('Ymd_His');

    setlocale(LC_TIME, 'es_ES.UTF-8', 'es_AR.UTF-8', 'es_ES', 'es_AR');

    return \Excel::create($filename, function($excel) use ($registros, $directores, $casinoId) {

        $excel->sheet('AUT. DIRECTORES', function($sheet) use ($registros, $directores, $casinoId) {

            $fila = 1;

            $sheet->setCellValue("A{$fila}", 'Director');

            foreach ($directores as $i => $d) {
                $col = \PHPExcel_Cell::stringFromColumnIndex($i + 1);
                $sheet->setCellValue("{$col}{$fila}", $d->nombre);
            }

            $fila++;
            $sheet->setCellValue("A{$fila}", 'C.U.I.T.');

            foreach ($directores as $i => $d) {
                $col = \PHPExcel_Cell::stringFromColumnIndex($i + 1);
                $sheet->setCellValue("{$col}{$fila}", $d->cuit);
            }

            $lastCol = \PHPExcel_Cell::stringFromColumnIndex($directores->count());
            $color = '#222222';
            switch ($casinoId) {
                case 1: $color = '#339966'; break;
                case 2: $color = '#ff0000'; break;
                case 3: $color = '#ffcc00'; break;
            }

            $sheet->cells("A1:{$lastCol}1", function($c) use ($color){
                $c->setBackground($color);
                $c->setFontColor('#000000');
                $c->setFontWeight('bold');
                $c->setAlignment('center');
            });
            $sheet->cells("A2:{$lastCol}2", function($c){
                $c->setAlignment('center');
                $c->setFontWeight('bold');
            });

            $sheet->cells("A1:A2", function($c){
              $c->setBackground('#CCCCCC');
            });

            $sheet->getStyle("A1:{$lastCol}2")->getAlignment()
                  ->setWrapText(true)
                  ->setVertical(\PHPExcel_Style_Alignment::VERTICAL_CENTER);
            $sheet->setHeight(1, 35);
            $sheet->setHeight(2, 28);

            $sheet->setWidth('A', 18);
            for ($i=0; $i < $directores->count(); $i++) {
                $col = \PHPExcel_Cell::stringFromColumnIndex($i + 1);
                $sheet->setWidth($col, 22);
            }

            $sheet->cells("A1:{$lastCol}9999", function($c){
                $c->setFontFamily('Arial');
                $c->setFontSize(10);
            });

            $sheet->setFreeze('A3');

            $fila++;
            foreach ($registros as $anio => $rows) {

                $sheet->mergeCells("A{$fila}:{$lastCol}{$fila}");
                $sheet->setCellValue("A{$fila}", "Año {$anio}");
                $sheet->cells("A{$fila}:{$lastCol}{$fila}", function($c){
                    $c->setBackground('#CCCCCC');
                    $c->setFontWeight('bold');
                    $c->setFontSize(13);
                    $c->setAlignment('center');
                });
                $sheet->getStyle("A{$fila}:{$lastCol}{$fila}")->getAlignment()
                      ->setVertical(\PHPExcel_Style_Alignment::VERTICAL_CENTER);
                $sheet->setHeight($fila, 20);
                $fila++;

                foreach ($rows as $r) {
                    $mesEsp = ucfirst(strftime('%B', strtotime($r->fecha_AutDirectores)));

                    $map = [];
                    foreach ($r->autorizaciones as $a) {
                        $dirModel = $a->relationLoaded('director')
                                    ? $a->getRelation('director')
                                    : $a->director()->first();
                        if ($dirModel) {
                            $map[$dirModel->id_registroAutDirectores_director] = (int)$a->autoriza;
                        }
                        $sheet->cells("A{$fila}", function($cells){
                      	    $cells->setBackground('#FFFF99');
                      	    $cells->setFontWeight('bold');
                      	});
                    }

                    $sheet->setCellValue("A{$fila}", $mesEsp);
                    foreach ($directores as $i => $d) {
                        $col = \PHPExcel_Cell::stringFromColumnIndex($i + 1);
                        $val = isset($map[$d->id]) ? ($map[$d->id] ? 'X' : '') : '';
                        $sheet->setCellValue("{$col}{$fila}", $val);
                        $sheet->getStyle("{$col}{$fila}")->getAlignment()->setHorizontal('center');
                    }
                    $fila++;
                }
            }

            $sheet->getStyle("A1:{$lastCol}2")->applyFromArray([
                'borders' => ['allborders' => [
                    'style' => \PHPExcel_Style_Border::BORDER_THICK,
                    'color' => ['argb' => 'FF000000']
                ]]
            ]);

            $sheet->getStyle("A3:{$lastCol}".($fila-1))->applyFromArray([
                'borders' => ['allborders' => [
                    'style' => \PHPExcel_Style_Border::BORDER_THIN,
                    'color' => ['argb' => 'FF000000']
                ]]
            ]);
        });

    })->export('xlsx');
}


public function descargarAutDirectoresXlsxTodos(Request $request)
{
    $desde = $request->query('desde');
    $hasta = $request->query('hasta');

    $user    = Usuario::find(session('id_usuario'));
    $casinos = $user->casinos->pluck('nombre', 'id_casino');

    setlocale(LC_TIME, 'es_ES.UTF-8', 'es_AR.UTF-8', 'es_ES', 'es_AR');

    return \Excel::create('aut_directores_todos_'.date('Ymd_His'), function($excel) use ($casinos, $desde, $hasta) {

        foreach ($casinos as $casinoId => $casinoNombre) {

            $directores = RegistroAutDirectores_director::where('casino', $casinoId)
                ->orderBy('nombre')
                ->get([
                    'id_registroAutDirectores_director as id',
                    'nombre',
                    'cuit'
                ]);

            $registros = RegistroAutDirectores::with(['autorizaciones.director'])
                ->where('casino', $casinoId)
                ->when($desde, function($q) use ($desde){
                    $q->where('fecha_AutDirectores', '>=', $desde.'-01');
                })
                ->when($hasta, function($q) use ($hasta){
                    $q->where('fecha_AutDirectores', '<=', $hasta.'-31');
                })
                ->orderBy('fecha_AutDirectores')
                ->get()
                ->groupBy(function($r){
                    return date('Y', strtotime($r->fecha_AutDirectores));
                });

            $excel->sheet($casinoNombre, function($sheet) use ($registros, $directores, $casinoId) {

                $fila = 1;

                $sheet->setCellValue("A{$fila}", 'Director');
                foreach ($directores as $i => $d) {
                    $col = \PHPExcel_Cell::stringFromColumnIndex($i + 1);
                    $sheet->setCellValue("{$col}{$fila}", $d->nombre);
                }

                $fila++;
                $sheet->setCellValue("A{$fila}", 'C.U.I.T.');
                foreach ($directores as $i => $d) {
                    $col = \PHPExcel_Cell::stringFromColumnIndex($i + 1);
                    $sheet->setCellValue("{$col}{$fila}", $d->cuit);
                }

                $lastCol = \PHPExcel_Cell::stringFromColumnIndex(max(0, $directores->count()));

                $color = '#222222';
                switch ($casinoId) {
                    case 1: $color = '#339966'; break;
                    case 2: $color = '#ff0000'; break;
                    case 3: $color = '#ffcc00'; break;
                }

                $sheet->cells("A1:{$lastCol}1", function($c) use ($color){
                    $c->setBackground($color);
                    $c->setFontColor('#000000');
                    $c->setFontWeight('bold');
                    $c->setAlignment('center');
                });
                $sheet->cells("A2:{$lastCol}2", function($c){
                    $c->setAlignment('center');
                    $c->setFontWeight('bold');
                });
                $sheet->cells("A1:A2", function($c){ $c->setBackground('#CCCCCC'); });

                $sheet->getStyle("A1:{$lastCol}2")->getAlignment()
                      ->setWrapText(true)
                      ->setVertical(\PHPExcel_Style_Alignment::VERTICAL_CENTER);
                $sheet->setHeight(1, 35);
                $sheet->setHeight(2, 28);

                $sheet->setWidth('A', 18);
                for ($i = 0; $i < $directores->count(); $i++) {
                    $col = \PHPExcel_Cell::stringFromColumnIndex($i + 1);
                    $sheet->setWidth($col, 22);
                }
                $sheet->cells("A1:{$lastCol}9999", function($c){
                    $c->setFontFamily('Arial');
                    $c->setFontSize(10);
                });

                $sheet->setFreeze('A3');

                $fila++;
                foreach ($registros as $anio => $rows) {
                    $sheet->mergeCells("A{$fila}:{$lastCol}{$fila}");
                    $sheet->setCellValue("A{$fila}", "Año {$anio}");
                    $sheet->cells("A{$fila}:{$lastCol}{$fila}", function($c){
                        $c->setBackground('#CCCCCC');
                        $c->setFontWeight('bold');
                        $c->setFontSize(13);
                        $c->setAlignment('center');
                    });
                    $sheet->setHeight($fila, 20);
                    $fila++;

                    foreach ($rows as $r) {
                        $mesEsp = ucfirst(strftime('%B', strtotime($r->fecha_AutDirectores)));

                        $map = [];
                        foreach ($r->autorizaciones as $a) {
                            $dirModel = $a->relationLoaded('director')
                                ? $a->getRelation('director')
                                : $a->director()->first();
                            if ($dirModel) {
                                $map[$dirModel->id_registroAutDirectores_director] = (int)$a->autoriza;
                            }
                        }

                        $sheet->setCellValue("A{$fila}", $mesEsp);
                        $sheet->cells("A{$fila}", function($cells){
                            $cells->setBackground('#FFFF99');
                            $cells->setFontWeight('bold');
                        });

                        foreach ($directores as $i => $d) {
                            $col = \PHPExcel_Cell::stringFromColumnIndex($i + 1);
                            $val = isset($map[$d->id]) ? ($map[$d->id] ? 'X' : '') : '';
                            $sheet->setCellValue("{$col}{$fila}", $val);
                            $sheet->getStyle("{$col}{$fila}")->getAlignment()->setHorizontal('center');
                        }
                        $fila++;
                    }
                }

                $sheet->getStyle("A1:{$lastCol}2")->applyFromArray([
                    'borders' => [
                        'allborders' => [
                            'style' => \PHPExcel_Style_Border::BORDER_THICK,
                            'color' => ['argb' => 'FF000000']
                        ]
                    ]
                ]);
                $sheet->getStyle("A3:{$lastCol}".($fila - 1))->applyFromArray([
                    'borders' => [
                        'allborders' => [
                            'style' => \PHPExcel_Style_Border::BORDER_THIN,
                            'color' => ['argb' => 'FF000000']
                        ]
                    ]
                ]);
            });
        }
    })->export('xlsx');
}

 //Seguros
 public function guardarSeguros_tipo(Request $request){



       try {
           DB::beginTransaction();

           $Seguros_tipo = new RegistroSeguros_tipo();
           $Seguros_tipo->tipo = $request->tipo_Seguros_tipo;

           $Seguros_tipo->fecha_toma = date('Y-m-d h:i:s', time());
           $Seguros_tipo->usuario = UsuarioController::getInstancia()->quienSoy()['usuario']['id_usuario'];
           $Seguros_tipo->save();

           DB::commit();

           return response()->json([
             'success' => true,
              'id' => $Seguros_tipo->id_registroSeguros_tipo,
              'Seguros_tipo'  => $request->all(),
            ]);
       } catch (\Exception $e) {
           DB::rollBack();
           return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
       }

 }


 public function getSeguros_tipo(){
   return response()->json(RegistroSeguros_tipo::orderBy('tipo')
      ->get(['id_registroSeguros_tipo as id','tipo']));
 }

 public function SegurosEliminarTipo($id){
     RegistroSeguros_tipo::findOrFail($id)->delete();
     return response()->json(array('ok'=>true));
 }

 public function modificarSeguros_tipo(Request $request){

   $id = $request->id_registroSeguros_tipo;
   $tipo = $request->ModifTipo_Seguros_tipo;

   try {
     DB::beginTransaction();
     $Seguros_tipo = RegistroSeguros_tipo::findOrFail($id);
     $Seguros_tipo->tipo = $tipo;
     $Seguros_tipo->save();
     DB::commit();

     return response()->json([
       'success' => true,
        'id' => $Seguros_tipo->id_registroSeguros_tipo,
        'tipo' => $Seguros_tipo->tipo,
      ]);

     } catch (\Exception $e) {
     DB::rollBack();
     return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
   }

 }

 public function guardarSeguros(Request $request){

       $path = null;


       try {
           DB::beginTransaction();

           $Seguros = new RegistroSeguros();
           $Seguros->periodo_inicio = $request->fecha_SegurosDes;
           $Seguros->periodo_fin = $request->fecha_SegurosHas;


           $Seguros->art = $request->art_Seguros;
           $Seguros->tipo = $request->tipo_Seguros;
           $Seguros->compañia = $request->comp_Seguros;
           $Seguros->nro_poliza = $request->poliza_Seguros;
           $Seguros->monto = $request->monto_Seguros;
           $Seguros->cta_paga_total = $request->cta_paga_Seguros;
           $Seguros->estado = $request->estado_Seguros;
           $Seguros->requerimento_anual = $request->requerimento_Seguros;
           $Seguros->observaciones = $request->obs_Seguros;

           $Seguros->casino = $request->casinoSeguros;
           $Seguros->fecha_toma = date('Y-m-d h:i:s', time());
           $Seguros->usuario = UsuarioController::getInstancia()->quienSoy()['usuario']['id_usuario'];
           $Seguros->save();
           $files = Arr::wrap($request->file('uploadSeguros'));
                     foreach ($files as $file) {
                         if (!($file instanceof UploadedFile) || !$file->isValid()) continue;

                         $base = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
                         $ext  = $file->getClientOriginalExtension();
                         $safe = preg_replace('/\s+/', '_', $base);
                         $filename = time().'_'.Str::random(6).'_'.$safe.($ext?'.'.$ext:'');

                         $file->storeAs('public/RegistroSeguros', $filename);

                         $Seguros->archivos()->create([
                             'path'       => $filename,
                             'usuario'    => $Seguros->usuario,
                             'fecha_toma' => date('Y-m-d H:i:s'),
                         ]);
                     }
           DB::commit();

           return response()->json([
             'success' => true,
              'id' => $Seguros->id_registroSeguros,
              'Seguros'  => $request->all(),
              'path'    => $path,
              'url'     => Storage::url($path)
            ]);
       } catch (\Exception $e) {
           DB::rollBack();
           return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
       }

 }
 public function ultimasSeguros(Request $request)
 {
     $page    = max(1, (int)$request->query('page', 1));
     $perPage = max(1, (int)$request->query('page_size', 20));

     $user = Usuario::find(session('id_usuario'));
     $allowedCasinoIds = $user->casinos->pluck('id_casino')->toArray();


     $query = RegistroSeguros::with('casinoSeguros','tipoSeguros')
               ->withCount('archivos')
               ->whereIn('casino', $allowedCasinoIds)
               ->orderBy('fecha_toma', 'desc')
               ->orderBy('casino', 'desc');


     if ($c = $request->query('id_casino')) {
       $query->where('casino', $c);
     }
     if ($desde = $request->query('desde')){
       $query->where('periodo_inicio',">=",$desde);
     }
     if ($hasta = $request->query('hasta')){
       $query->where('periodo_fin',"<=",$hasta);
     }

     $total = $query->count();

     $registros = $query
         ->skip(($page - 1) * $perPage)
         ->take($perPage)
         ->get();

     $datos = $registros->map(function($r) {
         return [
             'id_registroSeguros' => $r->id_registroSeguros,
             'periodoIn'   => $r->periodo_inicio,
             'periodoFin'   => $r->periodo_fin,
             'estado'       => $r->estado,
             'casino'      => $r->casinoSeguros ? $r->casinoSeguros->nombre : '-',
             'tipo'        => $r->tipoSeguros ? $r->tipoSeguros->tipo : '-',
	  'tiene_archivos' => $r->archivos_count>0,         ];
     });

     return response()->json([
         'registros'  => $datos,
         'pagination' => [
             'current_page' => $page,
             'per_page'     => $perPage,
             'total'        => $total,
         ],
     ]);
 }



 public function archivosSeguros($id)
 {
     $Seguros = RegistroSeguros::with('archivos')->findOrFail($id);

     $files = $Seguros->archivos->map(function($a){
         return [
             'id'     => $a->id_registro_archivo,
             'nombre' => basename($a->path),
             'url'    => \Illuminate\Support\Facades\Storage::url($a->path),
             'fecha'  => $a->fecha_toma,
         ];
     });

     return response()->json($files->values());
 }


 public function llenarSegurosEdit($id){
    $s = RegistroSeguros::findOrFail($id);
    if(is_null($s)) return 0;

    return response()->json([
        'desde'               => $s->periodo_inicio,
        'hasta'               => $s->periodo_fin,
        'casino'              => $s->casino,
        'art'                 => $s->art,
        'tipo_id'             => $s->tipo,
        'compania'            => $s->compañia,
        'poliza'              => $s->nro_poliza,
        'monto'               => $s->monto,
        'cta_paga_total'      => $s->cta_paga_total,
        'estado'              => $s->estado,
        'requerimiento_anual' => $s->requerimento_anual,
        'obs'                 => $s->observaciones,
    ]);
}

public function actualizarSeguros(Request $request, $id){
    $s = RegistroSeguros::findOrFail($id);

    $s->periodo_inicio       = $request->input('fecha_SegurosDes');
    $s->periodo_fin          = $request->input('fecha_SegurosHas');
    $s->art                  = $request->input('art_Seguros');
    $s->tipo                 = $request->input('tipo_Seguros');
    $s->compañia             = $request->input('comp_Seguros');
    $s->nro_poliza           = $request->input('poliza_Seguros');
    $s->monto                = $request->input('monto_Seguros');
    $s->cta_paga_total       = $request->input('cta_paga_Seguros');
    $s->estado               = $request->input('estado_Seguros');
    $s->requerimento_anual   = $request->input('requerimento_Seguros');
    $s->observaciones        = $request->input('obs_Seguros');
    $s->casino               = $request->input('casinoSeguros');
    $s->save();

    $saved = 0;
    $files = Arr::wrap($request->file('uploadSeguros'));
    foreach ($files as $file) {
        if (!($file instanceof UploadedFile) || !$file->isValid()) continue;

        $base = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
        $ext  = $file->getClientOriginalExtension();
        $safe = preg_replace('/\s+/', '_', $base);
        $name = time().'_'.Str::random(6).'_'.$safe.($ext ? '.'.$ext : '');

        $file->storeAs('public/RegistroSeguros', $name);

        $s->archivos()->create([
            'path'       => $name,
            'usuario'    => UsuarioController::getInstancia()->quienSoy()['usuario']['id_usuario'],
            'fecha_toma' => date('Y-m-d H:i:s'),
        ]);

        $saved++;
    }

    return response()->json(['success' => true, 'saved_files' => $saved, 'id' => $s->id_registroSeguros]);
}

 public function eliminarSeguros($id){
   $Seguros = RegistroSeguros::findOrFail($id);
   if(is_null($Seguros)) return 0;
   RegistroSeguros::destroy($id);
   return 1;
 }

 public function llenarSeguros($id){
   $Seguros = RegistroSeguros::with(['casinoSeguros','tipoSeguros'
                                           ])->findOrFail($id);

   return response()->json([

     'casino' => $Seguros->casinoSeguros ? $Seguros->casinoSeguros->nombre : '-',
     'tipoSeguro' => $Seguros->tipoSeguros ? $Seguros->tipoSeguros->tipo : '-',
     'art' => $Seguros->art,
     'compañia' => $Seguros->compañia,
     'nro_poliza' => $Seguros->nro_poliza,
     'monto' => $Seguros->monto,
     'periodo_inicio' => $Seguros->periodo_inicio,
     'periodo_fin' => $Seguros->periodo_fin,
     'cta_paga_total' => $Seguros->cta_paga_total,
     'estado' => $Seguros->estado ? 'VIGENTE' : 'VENCIDO',
     'requerimento_anual' => $Seguros->requerimento_anual,
     'observaciones' => $Seguros->observaciones,

   ]);

 }
 public function estadoSeguros($id){
   $Seguros = RegistroSeguros::findOrFail($id);
   $Seguros->estado = $Seguros->estado ? 0 : 1;
   $Seguros->save();
   return 1;
 }

 public function descargarSegurosCsv(Request $request)
 {
     $casinoId = $request->query('casino');
     $desde    = $request->query('desde');
     $hasta    = $request->query('hasta');

     $user = Usuario::find(session('id_usuario'));
     $allowedCasinoIds = $user->casinos->pluck('id_casino')->toArray();

     $registros = RegistroSeguros::with(['casinoSeguros','tipoSeguros'])
         ->whereIn('casino', $allowedCasinoIds)
         ->when($casinoId && (int)$casinoId !== 4, function ($q) use ($casinoId) {
             $q->where('casino', $casinoId);
         })
         ->when($desde || $hasta, function ($q) use ($desde, $hasta) {
             if ($desde && $hasta) {
                 $q->where(function($qq) use ($desde, $hasta) {
                     $qq->where('periodo_inicio', '<=', $hasta)
                        ->where('periodo_fin', '>=', $desde);
                 });
             } elseif ($desde) {
                 $q->where('periodo_fin', '>=', $desde);
             } else {
                 $q->where('periodo_inicio', '<=', $hasta);
             }
         })
         ->orderBy('casino')
         ->orderBy('periodo_inicio')
         ->get()
         ->sortBy(function ($r) {
             return sprintf(
                 '%s|%s|%s',
                 str_pad((string)$r->casino, 3, '0', STR_PAD_LEFT),
                 mb_strtoupper($r->tipoSeguros->tipo ?? '', 'UTF-8'),
                 $r->periodo_inicio
             );
         });

     $csv = [];
     $csv[] = [
         'Casino',
         'Tipo de Seguro',
         'Art. CP 9201',
         'Compañia',
         'N° Poliza',
         'Periodo Inicio',
         'Periodo Fin',
         'Monto Asegurado',
         'Cta Paga/ Pago Total',
         'Estado',
         'Requerimento Anual',
         'Observaciones',
     ];

     foreach ($registros as $r) {
         $csv[] = [
             $r->casinoSeguros ? $r->casinoSeguros->nombre : '-',
             $r->tipoSeguros   ? $r->tipoSeguros->tipo      : '-',
             $r->art,
             $r->compañia,
             $r->nro_poliza,
             $r->periodo_inicio ?: '',
             $r->periodo_fin    ?: '',
             is_null($r->monto) ? '' : number_format((float)$r->monto, 2, '.', ''),
             $r->cta_paga_total,
             $r->estado ? 'VIGENTE' : 'VENCIDO',
             $r->requerimento_anual,
             $r->observaciones,
         ];
     }

     $nombreCasino = ((int)$casinoId === 4 || !$casinoId)
         ? 'todos'
         : (Casino::find($casinoId)->nombre ?? 'desconocido');

     $filename = "Seguros_{$nombreCasino}_" . date('Ymd_His') . ".csv";

     $handle = fopen('php://temp', 'r+');
     foreach ($csv as $linea) {
         fputcsv($handle, $linea, ',');
     }
     rewind($handle);
     $contenido = stream_get_contents($handle);
     fclose($handle);

     return response($contenido, 200, [
         'Content-Type'        => 'text/csv; charset=UTF-8',
         'Content-Disposition' => "attachment; filename=\"$filename\"",
         'Cache-Control'       => 'no-store, no-cache',
     ]);
 }


 public function descargarSegurosXlsx(Request $request)
 {
     $casinoId = $request->query('casino');
     $desde    = $request->query('desde');
     $hasta    = $request->query('hasta');

     $casino = Casino::findOrFail($casinoId);

     $registros = RegistroSeguros::with(['casinoSeguros','tipoSeguros'])
         ->where('casino', $casinoId)
         ->when($desde, function($q) use ($desde){
             $q->where('periodo_inicio','>=',$desde);
         })
         ->when($hasta, function($q) use ($hasta){
             $q->where('periodo_fin','<=',$hasta);
         })
         ->orderBy('periodo_inicio','asc')
         ->get();

     $registros = $registros->sortBy(function($r){
         $tipo = $r->tipoSeguros ? $r->tipoSeguros->tipo : '-';
         return $tipo.'|'.$r->periodo_inicio;
     });

     $grupos = $registros->groupBy(function($r){
         return $r->tipoSeguros ? $r->tipoSeguros->tipo : '-';
     });

     $filename = 'seguros_'.str_replace(' ','_',strtolower($casino->nombre)).'_'.date('Ymd_His');

     return \Excel::create($filename, function($excel) use ($grupos, $casinoId) {
         $excel->sheet('SEGUROS', function($sheet) use ($grupos, $casinoId) {
             $fila = 1;

             $sheet->row($fila, [
                 'Tipo de Seguro',
                 'Art. CP 9201',
                 'Compañía',
                 'Número Póliza',
                 'Periodo de Vigencia',
                 'Monto Asegurado',
                 'Cta Paga / Pago Total',
                 'Requerimiento anual',
                 'Estado',
                 'Observaciones'
             ]);

             $lastCol = \PHPExcel_Cell::stringFromColumnIndex(9);
             $color = '#222222';
             switch ($casinoId) {
                 case 1: $color = '#339966'; break;
                 case 2: $color = '#ff0000'; break;
                 case 3: $color = '#ffcc00'; break;
             }

             $sheet->cells("A1:{$lastCol}1", function($c) use ($color){
                 $c->setBackground($color);
                 $c->setFontColor('#000000');
                 $c->setFontWeight('bold');
                 $c->setAlignment('center');
                 $c->setValignment('center');
             });
             $sheet->getStyle("A1:{$lastCol}1")->getAlignment()->setWrapText(true);
             $sheet->setHeight(1, 28);

             $sheet->cells("A1:{$lastCol}20000", function($c){
                 $c->setFontFamily('Arial');
                 $c->setFontSize(10);
                 $c->setFontWeight('bold');
                 $c->setAlignment('center');
                 $c->setValignment('center');
             });

             $sheet->setWidth('A', 28);
             $sheet->setWidth('B', 12);
             $sheet->setWidth('C', 28);
             $sheet->setWidth('D', 16);
             $sheet->setWidth('E', 26);
             $sheet->setWidth('F', 18);
             $sheet->setWidth('G', 20);
             $sheet->setWidth('H', 20);
             $sheet->setWidth('I', 12);
             $sheet->setWidth('J', 28);

             $sheet->setFreeze('B2');

             $fila++;

             foreach ($grupos as $tipoSeguro => $items) {
                 $inicio = $fila;
                 foreach ($items as $r) {
                     $periodo = date('d/m/Y', strtotime($r->periodo_inicio)).' al '.date('d/m/Y', strtotime($r->periodo_fin));
                     $estado  = $r->estado ? 'VIGENTE' : 'VENCIDO';
                     $monto   = 'USD '.(is_numeric($r->monto) ? number_format($r->monto,2,',','.') : $r->monto);

                     $sheet->row($fila, [
                         '',
                         $r->art,
                         $r->compañia,
                         $r->nro_poliza,
                         $periodo,
                         $monto,
                         $r->cta_paga_total,
                         $r->requerimento_anual,
                         $estado,
                         $r->observaciones
                     ]);

                     $sheet->cells("I{$fila}", function($c) use ($estado){
                         if($estado === 'VENCIDO'){
                             $c->setBackground('#666666');
                             $c->setFontColor('#FFFFFF');
                         }else{
                             $c->setBackground('#FFEB3B');
                             $c->setFontColor('#000000');
                         }
                     });

                     $fila++;
                 }
                 $fin = $fila - 1;
                 if ($fin >= $inicio) {
                     $sheet->mergeCells("A{$inicio}:A{$fin}");
                     $sheet->setCellValue("A{$inicio}", $tipoSeguro);
                     $sheet->cells("A{$inicio}:A{$fin}", function($c){
                         $c->setBackground('#FFF2CC');
                         $c->setAlignment('center');
                         $c->setValignment('center');
                         $c->setFontWeight('bold');
                     });
                     $sheet->getStyle("A{$inicio}:{$lastCol}{$fin}")->applyFromArray([
                         'borders' => [
                             'outline' => [
                                 'style' => \PHPExcel_Style_Border::BORDER_THICK,
                                 'color' => ['argb' => 'FF000000']
                             ]
                         ]
                     ]);
                 }
             }

             $sheet->getStyle("A1:{$lastCol}".($fila-1))->applyFromArray([
                 'borders' => [
                     'allborders' => [
                         'style' => \PHPExcel_Style_Border::BORDER_THIN,
                         'color' => ['argb' => 'FF000000']
                     ]
                 ]
             ]);
             $sheet->getStyle("A1:{$lastCol}1")->applyFromArray([
                 'borders' => [
                     'outline' => [
                         'style' => \PHPExcel_Style_Border::BORDER_THICK,
                         'color' => ['argb' => 'FF000000']
                     ]
                 ]
             ]);
             $sheet->getStyle("A1:A".($fila-1))->applyFromArray([
                 'borders' => [
                     'outline' => [
                         'style' => \PHPExcel_Style_Border::BORDER_THICK,
                         'color' => ['argb' => 'FF000000']
                     ]
                 ]
             ]);
         });
     })->export('xlsx');
 }

 public function descargarSegurosXlsxTodos(Request $request)
 {
     $desde = $request->query('desde');
     $hasta = $request->query('hasta');

     $user    = Usuario::find(session('id_usuario'));
     $casinos = $user->casinos->pluck('nombre','id_casino');

     return \Excel::create('seguros_todos_'.date('Ymd_His'), function($excel) use ($casinos,$desde,$hasta) {

         foreach($casinos as $casinoId => $casinoNombre){

             $registros = RegistroSeguros::with(['casinoSeguros','tipoSeguros'])
                 ->where('casino',$casinoId)
                 ->when($desde, function($q) use ($desde){ $q->where('periodo_inicio','>=',$desde); })
                 ->when($hasta, function($q) use ($hasta){ $q->where('periodo_fin','<=',$hasta); })
                 ->orderBy('periodo_inicio','asc')
                 ->get();

             $registros = $registros->sortBy(function($r){
                 $tipo = $r->tipoSeguros ? $r->tipoSeguros->tipo : '-';
                 return $tipo.'|'.$r->periodo_inicio;
             });

             $grupos = $registros->groupBy(function($r){
                 return $r->tipoSeguros ? $r->tipoSeguros->tipo : '-';
             });

             $excel->sheet($casinoNombre, function($sheet) use ($grupos,$casinoId){

                 $fila = 1;

                 $sheet->row($fila, [
                     'Tipo de Seguro',
                     'Art. CP 9201',
                     'Compañía',
                     'Número Póliza',
                     'Periodo de Vigencia',
                     'Monto Asegurado',
                     'Cta Paga / Pago Total',
                     'Requerimiento anual',
                     'Estado',
                     'Observaciones'
                 ]);

                 $lastCol = \PHPExcel_Cell::stringFromColumnIndex(9);

                 $color = '#222222';
                 switch ($casinoId) {
                     case 1: $color = '#339966'; break;
                     case 2: $color = '#ff0000'; break;
                     case 3: $color = '#ffcc00'; break;
                 }

                 $sheet->cells("A1:{$lastCol}1", function($c) use ($color){
                     $c->setBackground($color);
                     $c->setFontColor('#000000');
                     $c->setFontWeight('bold');
                     $c->setAlignment('center');
                     $c->setValignment('center');
                 });
                 $sheet->getStyle("A1:{$lastCol}1")->getAlignment()->setWrapText(true);
                 $sheet->setHeight(1, 28);

                 $sheet->cells("A1:{$lastCol}20000", function($c){
                     $c->setFontFamily('Arial');
                     $c->setFontSize(10);
                     $c->setFontWeight('bold');
                     $c->setAlignment('center');
                     $c->setValignment('center');
                 });

                 $sheet->setWidth('A', 28);
                 $sheet->setWidth('B', 12);
                 $sheet->setWidth('C', 28);
                 $sheet->setWidth('D', 16);
                 $sheet->setWidth('E', 26);
                 $sheet->setWidth('F', 18);
                 $sheet->setWidth('G', 20);
                 $sheet->setWidth('H', 20);
                 $sheet->setWidth('I', 12);
                 $sheet->setWidth('J', 28);

                 $sheet->setFreeze('B2');

                 $fila++;

                 foreach($grupos as $tipoSeguro => $items){
                     $inicio = $fila;

                     foreach($items as $r){
                         $periodo = date('d/m/Y', strtotime($r->periodo_inicio)).' al '.date('d/m/Y', strtotime($r->periodo_fin));
                         $estado  = $r->estado ? 'VIGENTE' : 'VENCIDO';
                         $monto   = 'USD '.(is_numeric($r->monto) ? number_format($r->monto,2,',','.') : $r->monto);

                         $sheet->row($fila, [
                             '',
                             $r->art,
                             $r->compañia,
                             $r->nro_poliza,
                             $periodo,
                             $monto,
                             $r->cta_paga_total,
                             $r->requerimento_anual,
                             $estado,
                             $r->observaciones
                         ]);

                         $sheet->cells("I{$fila}", function($c) use ($estado){
                             if($estado === 'VENCIDO'){
                                 $c->setBackground('#666666');
                                 $c->setFontColor('#FFFFFF');
                             }else{
                                 $c->setBackground('#FFEB3B');
                                 $c->setFontColor('#000000');
                             }
                         });

                         $fila++;
                     }

                     $fin = $fila - 1;
                     if($fin >= $inicio){
                         $sheet->mergeCells("A{$inicio}:A{$fin}");
                         $sheet->setCellValue("A{$inicio}", $tipoSeguro);
                         $sheet->cells("A{$inicio}:A{$fin}", function($c){
                             $c->setBackground('#FFF2CC');
                             $c->setAlignment('center');
                             $c->setValignment('center');
                             $c->setFontWeight('bold');
                         });
                         $sheet->getStyle("A{$inicio}:{$lastCol}{$fin}")->applyFromArray([
                             'borders' => [
                                 'outline' => [
                                     'style' => \PHPExcel_Style_Border::BORDER_THICK,
                                     'color' => ['argb' => 'FF000000']
                                 ]
                             ]
                         ]);
                     }
                 }

                 $sheet->getStyle("A1:{$lastCol}".($fila-1))->applyFromArray([
                     'borders' => [
                         'allborders' => [
                             'style' => \PHPExcel_Style_Border::BORDER_THIN,
                             'color' => ['argb' => 'FF000000']
                         ]
                     ]
                 ]);
                 $sheet->getStyle("A1:{$lastCol}1")->applyFromArray([
                     'borders' => [
                         'outline' => [
                             'style' => \PHPExcel_Style_Border::BORDER_THICK,
                             'color' => ['argb' => 'FF000000']
                         ]
                     ]
                 ]);
                 $sheet->getStyle("A1:A".($fila-1))->applyFromArray([
                     'borders' => [
                         'outline' => [
                             'style' => \PHPExcel_Style_Border::BORDER_THICK,
                             'color' => ['argb' => 'FF000000']
                         ]
                     ]
                 ]);
             });
         }

     })->export('xlsx');
 }

//Derecho de ACCESO
public function guardarDerechoAcceso(Request $request){

      $path = null;

      try {
          DB::beginTransaction();

          $DerechoAcceso = new RegistroDerechoAcceso();
          $DerechoAcceso->fecha_DerechoAcceso = $request->fecha_DerechoAcceso.'-01';




          $DerechoAcceso->semana = $request->semanaDerechoAcceso;
          $DerechoAcceso->fecha_vencimiento = $request->fecha_venc_DerechoAcceso;
          $DerechoAcceso->monto = $request->monto_DerechoAcceso;
          $DerechoAcceso->observaciones = $request->obs_DerechoAcceso;




          $DerechoAcceso->casino = $request->casinoDerechoAcceso;
          $DerechoAcceso->fecha_toma = date('Y-m-d h:i:s', time());
          $DerechoAcceso->usuario = UsuarioController::getInstancia()->quienSoy()['usuario']['id_usuario'];
          $DerechoAcceso->save();

          $files = Arr::wrap($request->file('uploadDerechoAcceso'));
                    foreach ($files as $file) {
                        if (!($file instanceof UploadedFile) || !$file->isValid()) continue;

                        $base = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
                        $ext  = $file->getClientOriginalExtension();
                        $safe = preg_replace('/\s+/', '_', $base);
                        $filename = time().'_'.Str::random(6).'_'.$safe.($ext?'.'.$ext:'');

                        $file->storeAs('public/RegistroDerechoAcceso', $filename);

                        $DerechoAcceso->archivos()->create([
                            'path'       => $filename,
                            'usuario'    => $DerechoAcceso->usuario,
                            'fecha_toma' => date('Y-m-d H:i:s'),
                        ]);
                    }

          DB::commit();

          return response()->json([
            'success' => true,
             'id' => $DerechoAcceso->id_registroDerechoAcceso,
             'DerechoAcceso'  => $request->all(),
             'path'    => $path,
             'url'     => Storage::url($path)
           ]);
      } catch (\Exception $e) {
          DB::rollBack();
          return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
      }

}

public function ultimasDerechoAcceso(Request $request)
{
    $page    = max(1, (int)$request->query('page', 1));
    $perPage = max(1, (int)$request->query('page_size', 20));

    $user = Usuario::find(session('id_usuario'));
    //$allowedCasinoIds = $user->casinos->pluck('id_casino')->toArray();


    $query = RegistroDerechoAcceso::with('casinoDerechoAcceso')
      //        ->whereIn('casino', $allowedCasinoIds)
              ->withCount('archivos')
              ->orderBy('fecha_DerechoAcceso', 'desc')
              ->orderBy('casino', 'desc');


  //  if ($c = $request->query('id_casino')) {
  //    $query->where('casino', $c);
  //  }
    if ($desde = $request->query('desde')){
      $desde = $desde."-01";
      $query->where('fecha_DerechoAcceso',">=",$desde);
    }
    if ($hasta = $request->query('hasta')){
      $hasta = $hasta."-01";
      $query->where('fecha_DerechoAcceso',"<=",$hasta);
    }

    $total = $query->count();

    $registros = $query
        ->skip(($page - 1) * $perPage)
        ->take($perPage)
        ->get();

    $datos = $registros->map(function($r) {
        return [
            'id_registroDerechoAcceso' => $r->id_registroDerechoAcceso,
            'fecha_DerechoAcceso'   => $r->fecha_DerechoAcceso,
            'fecha_venc' => $r->fecha_vencimiento,
            'monto' => $r->monto,
            'semana' => $r->semana,
            'obs' => $r->observaciones,
            'casino'      => $r->casinoDerechoAcceso ? $r->casinoDerechoAcceso->nombre : '-',
	  'tiene_archivos' => $r->archivos_count>0,        ];
    });

    return response()->json([
        'registros'  => $datos,
        'pagination' => [
            'current_page' => $page,
            'per_page'     => $perPage,
            'total'        => $total,
        ],
    ]);
}


public function archivosDerechoAcceso($id)
{
    $DerechoAcceso = RegistroDerechoAcceso::with('archivos')->findOrFail($id);

    $files = $DerechoAcceso->archivos->map(function($a){
        return [
            'id'     => $a->id_registro_archivo,
            'nombre' => basename($a->path),
            'url'    => \Illuminate\Support\Facades\Storage::url($a->path),
            'fecha'  => $a->fecha_toma,
        ];
    });

    return response()->json($files->values());
}

public function llenarDerechoAccesoEdit($id){
  $d = RegistroDerechoAcceso::findOrFail($id);

  return response()->json([
    'fecha'      => $d->fecha_DerechoAcceso,
    'casino'     => $d->casino,
    'semana'     => $d->semana,
    'fecha_venc' => $d->fecha_vencimiento,
    'monto'      => $d->monto,
    'obs'        => $d->observaciones,
  ]);
}

public function actualizarDerechoAcceso(Request $request, $id)
{
    $r = RegistroDerechoAcceso::findOrFail($id);

    $r->fecha_DerechoAcceso = $request->input('fecha_DerechoAcceso').'-01';
    $r->casino              = $request->input('casinoDerechoAcceso');
    $r->semana              = $request->input('semanaDerechoAcceso');
    $r->fecha_vencimiento   = $request->input('fecha_venc_DerechoAcceso');
    $r->monto               = $request->input('monto_DerechoAcceso');
    $r->observaciones       = $request->input('obs_DerechoAcceso');
    $r->save();

    $saved = 0;
    $files = Arr::wrap($request->file('uploadDerechoAcceso'));
    foreach ($files as $file) {
        if (!($file instanceof UploadedFile) || !$file->isValid()) continue;

        $base = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
        $ext  = $file->getClientOriginalExtension();
        $safe = preg_replace('/\s+/', '_', $base);
        $name = time().'_'.Str::random(6).'_'.$safe.($ext ? '.'.$ext : '');

        $file->storeAs('public/RegistroDerechoAcceso', $name);

        $r->archivos()->create([
            'path'       => $name,
            'usuario'    => UsuarioController::getInstancia()->quienSoy()['usuario']['id_usuario'],
            'fecha_toma' => date('Y-m-d H:i:s'),
        ]);

        $saved++;
    }

    return response()->json(['success' => true, 'saved_files' => $saved]);
}

public function eliminarDerechoAcceso($id){
  $DerechoAcceso = RegistroDerechoAcceso::findOrFail($id);
  if(is_null($DerechoAcceso)) return 0;
  RegistroDerechoAcceso::destroy($id);
  return 1;
}

public function llenarDerechoAcceso($id){
  $DerechoAcceso = RegistroDerechoAcceso::with('casinoDerechoAcceso')->findOrFail($id);
  if(is_null($DerechoAcceso)) return 0;

  return response()->json([

    'obs' => $DerechoAcceso->observaciones,

  ]);

}

public function descargarDerechoAccesoCsv(Request $request)
{
    $casinoId = $request->query('casino');
    $desde    = $request->query('desde');
    $hasta    = $request->query('hasta');
    $user = Usuario::find(session('id_usuario'));
    $allowedCasinoIds = $user->casinos->pluck('id_casino')->toArray();

    $registros = RegistroDerechoAcceso::with('casinoDerechoAcceso')
        ->whereIn('casino',$allowedCasinoIds)
        ->when($casinoId && $casinoId != 4, function ($q) use ($casinoId) {
            $q->where('casino', $casinoId);
        })
        ->when($desde, function ($q) use ($desde) {
            $q->where('fecha_DerechoAcceso', '>=', $desde . '-01');
        })
        ->when($hasta, function ($q) use ($hasta) {
            $q->where('fecha_DerechoAcceso', '<=', $hasta . '-01');
        })
        ->orderBy('casino')
        ->orderBy('fecha_DerechoAcceso')
        ->get();

    $csv = [];
    $csv[] = ['Año', 'Mes','Semana', 'Monto', 'Fecha Vencimiento','Observaciones' ];

    foreach ($registros as $r) {
        setlocale(LC_TIME, 'es_ES.UTF-8');
        $anio     = date('Y', strtotime($r->fecha_DerechoAcceso));
        $mes      = strftime('%B', strtotime($r->fecha_DerechoAcceso));
        $semana = $r->semana;
        $monto_pagado = number_format($r->monto, 2, '.', '');
        $fecha_venc = $r->fecha_vencimiento;
        $obs = $r->observaciones;



        $csv[] = [
            $anio,
            ucfirst($mes),
            $semana,
            $monto_pagado,
            $fecha_venc,
            $obs,

        ];
    }

    $nombreCasino = $casinoId == 4 ? 'todos' : Casino::find($casinoId)->nombre ?? 'desconocido';
    $filename = "DerechoAcceso_{$nombreCasino}_" . date('Ymd_His') . ".csv";

    $handle = fopen('php://temp', 'r+');
    foreach ($csv as $linea) {
        fputcsv($handle, $linea, ',');
    }
    rewind($handle);
    $contenido = stream_get_contents($handle);
    fclose($handle);

    return response($contenido, 200, [
        'Content-Type'        => 'text/csv; charset=UTF-8',
        'Content-Disposition' => "attachment; filename=\"$filename\"",
        'Cache-Control'       => 'no-store, no-cache'
    ]);
}
public function descargarDerechoAccesoXlsx(Request $request)
{
    $casinoId = $request->query('casino');
    $desde    = $request->query('desde');
    $hasta    = $request->query('hasta');

    $casino = Casino::findOrFail($casinoId);

    $query = RegistroDerechoAcceso::select([
        DB::raw("YEAR(fecha_DerechoAcceso)   AS anio"),
        DB::raw("MONTHNAME(fecha_DerechoAcceso) AS Mes"),
        DB::raw("semana"),
        DB::raw("fecha_vencimiento"),
        DB::raw("monto"),
        DB::raw("observaciones")
    ])->where('casino', $casinoId);

    if ($desde) $query->where('fecha_DerechoAcceso', '>=', $desde.'-01');
    if ($hasta) $query->where('fecha_DerechoAcceso', '<=', $hasta.'-31');

    $datos = $query->orderBy('fecha_DerechoAcceso')
                   ->orderBy('semana')
                   ->get()
                   ->groupBy('anio');

    $filename = "registro_DerechoAcceso_".str_replace(' ','_', strtolower($casino->nombre)).'_'.date('Ymd_His');

    setlocale(LC_TIME, 'es_ES.UTF-8', 'es_AR.UTF-8', 'es_ES', 'es_AR');

    return \Excel::create($filename, function($excel) use ($datos, $casinoId) {

        $excel->sheet('DerechoAcceso', function($sheet) use ($datos, $casinoId) {

            $fila = 1;

            $sheet->row($fila, ['Mes','Semana N°','Fecha Vencimiento','Monto Pagado','Observaciones']);
            $sheet->cells("A1:E1", function($c) use ($casinoId){
                $color = '#222222';
                if ($casinoId==1) $color='#339966';
                elseif ($casinoId==2) $color='#ff0000';
                elseif ($casinoId==3) $color='#ffcc00';
                $c->setBackground($color);
                $c->setFontColor('#000000');
                $c->setFontWeight('bold');
                $c->setAlignment('center');
            });
            $sheet->getStyle("A1:E1")->getAlignment()->setWrapText(true)->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
            $sheet->setHeight(1, 50);
            $sheet->cells("A1:E9999", function($c){ $c->setFontFamily('Arial'); $c->setFontSize(10); });
            $sheet->setFreeze('A2');

            $fila++;

            foreach ($datos as $anio => $rows) {
                $sheet->mergeCells("A{$fila}:E{$fila}");
                $sheet->setCellValue("A{$fila}", "Año {$anio}");
                $sheet->cells("A{$fila}:E{$fila}", function($c){
                    $c->setBackground('#CCCCCC');
                    $c->setFontWeight('bold');
                    $c->setFontSize(13);
                    $c->setAlignment('center');
                });
                $sheet->getStyle("A{$fila}:E{$fila}")->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
                $sheet->setHeight($fila, 20);
                $fila++;

                $gruposMes = $rows->groupBy(function($r){ return $r->{'Mes'}; });

                foreach ($gruposMes as $mesKey => $items) {
                    $mesEsp = ucfirst(strftime('%B', strtotime($mesKey.' 1')));
                    $start = $fila;
                    $i = 0;

                    foreach ($items as $r) {
                        $sheet->row($fila, [
                            $i===0 ? $mesEsp : '',
                            $r->semana,
                            $r->fecha_vencimiento,
                            '$ '.number_format($r->monto, 2, ',', '.'),
                            $r->observaciones
                        ]);
                        $sheet->cells("B{$fila}:E{$fila}", function($c){ $c->setAlignment('center'); });
                        $sheet->getStyle("A{$fila}:E{$fila}")->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
                        $i++;
                        $fila++;
                    }

                    $end = $fila - 1;
                    if ($end >= $start) {
                        $sheet->mergeCells("A{$start}:A{$end}");
                        $sheet->cells("A{$start}:A{$end}", function($c){
                            $c->setBackground('#FFFF99');
                            $c->setFontWeight('bold');
                            $c->setAlignment('left');
                            $c->setValignment('center');
                        });
                    }
                }
            }

            $sheet->getStyle("A1:E1")->applyFromArray([
                'borders' => ['allborders' => ['style' => PHPExcel_Style_Border::BORDER_THICK, 'color' => ['argb' => 'FF000000']]]
            ]);
            $sheet->getStyle("A2:E".($fila-1))->applyFromArray([
                'borders' => ['allborders' => ['style' => PHPExcel_Style_Border::BORDER_THIN, 'color' => ['argb' => 'FF000000']]]
            ]);

            $anchos = [12,10,22,18,40];
            foreach (range('A','E') as $i => $col) $sheet->setWidth($col, $anchos[$i]);
        });

    })->export('xlsx');
}

//PATENTES
public function guardarRegistroPatentes_patenteDe(Request $request){



      try {
          DB::beginTransaction();

          $Patentes_patenteDe = new RegistroPatentes_patenteDe();
          $Patentes_patenteDe->nombre = $request->nombre_Patentes_patenteDe;
          $Patentes_patenteDe->estado = 1;
          $Patentes_patenteDe->casino = $request->CasinoPatentes_patenteDe;
          $Patentes_patenteDe->fecha_toma = date('Y-m-d h:i:s', time());
          $Patentes_patenteDe->usuario = UsuarioController::getInstancia()->quienSoy()['usuario']['id_usuario'];
          $Patentes_patenteDe->save();

          DB::commit();

          return response()->json([
            'success' => true,
             'id' => $Patentes_patenteDe->id_registroPatentes_patenteDe,
             'Patentes_patenteDe'  => $request->all(),
           ]);
      } catch (\Exception $e) {
          DB::rollBack();
          return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
      }

}



public function archivosPatentes($id)
{
    $Patentes = RegistroPatentes::with('archivos')->findOrFail($id);

    $files = $Patentes->archivos->map(function($a){
        return [
            'id'     => $a->id_registro_archivo,
            'nombre' => basename($a->path),
            'url'    => \Illuminate\Support\Facades\Storage::url($a->path),
            'fecha'  => $a->fecha_toma,
        ];
    });

    return response()->json($files->values());
}
public function llenarPatentesEdit($id){
    $r = RegistroPatentes::with('casinoPatentes')->findOrFail($id);

    $pagos = RegistroPatentes_patenteDe_pago::with('PatenteDe')
        ->where('registroPatentes', $r->id_registroPatentes)
        ->orderBy('id_registroPatentes_patenteDe_pago')
        ->get()
        ->map(function($p){
            return [
                'id'           => $p->id_registroPatentes_patenteDe_pago,
                'patenteDe_id' => $p->PatenteDe,
                'patenteDe'    => $p->PatenteDe ? $p->PatenteDe->nombre : '-',
                'cuota'        => $p->cuota,
                'importe'      => $p->importe,
                'observacion'  => $p->observacion,
                'fecha_pres'   => $p->fecha_pres,
            ];
        })->values();

    $fechaYm = is_string($r->fecha_Patentes) ? substr($r->fecha_Patentes,0,7) : ($r->fecha_Patentes ? $r->fecha_Patentes->format('Y-m') : null);

    return response()->json([
        'id'            => $r->id_registroPatentes,
        'fecha'         => $fechaYm,
        'casino'        => $r->casino,
        'casino_nombre' => $r->casinoPatentes->nombre ?? '-',
        'pagos'         => $pagos,
    ]);
}



public function getPatentes_patenteDe(){
  $reg = RegistroPatentes_patenteDe::with('CasinoPatentes_patenteDe')
    ->get();
    $datos = $reg->map(function($r) {
        return [
            'id' => $r->id_registroPatentes_patenteDe,
            'nombre'   => $r->nombre,
            'estado' => $r->estado,
            'casino'       => $r->CasinoPatentes_patenteDe ? $r->CasinoPatentes_patenteDe->nombre : '-',
        ];
    });
    return response()->json($datos);
}

public function getPatentes_patenteDeHabilitadosPorCasino($id){
  return response()->json(RegistroPatentes_patenteDe::orderBy('nombre')
     ->where('estado',1)
     ->where('casino',$id)
     ->get(['id_registroPatentes_patenteDe as id','nombre']));
}

public function EliminarPatentes_patenteDe($id){
    RegistroPatentes_patenteDe::findOrFail($id)->delete();
    return response()->json(array('ok'=>true));
}

public function modificarPatentes_patenteDe(Request $request){

  $id = $request->ModifId_Patentes_patenteDe;
  $nombre = $request->ModifPatentes_patenteDe_nombre;
  $estado = $request->ModifPatentes_patenteDe_estado;
  try {
    DB::beginTransaction();
    $Patentes_patenteDe = RegistroPatentes_patenteDe::findOrFail($id);
    $Patentes_patenteDe->nombre = $nombre;
    $Patentes_patenteDe->estado = $estado;
    $Patentes_patenteDe->save();
    DB::commit();

    return response()->json([
      'success' => true,
       'id' => $Patentes_patenteDe->id_registroPatentes_patenteDe,
       'nombre' => $Patentes_patenteDe->nombre,
       'estado' => $Patentes_patenteDe->estado,
     ]);

    } catch (\Exception $e) {
    DB::rollBack();
    return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
  }

}
public function guardarPatentes(Request $request){
    DB::beginTransaction();
    try {
        $r = new RegistroPatentes();
        $r->fecha_Patentes = $request->input('fecha_Patentes') ? ($request->input('fecha_Patentes').'-01') : null;
        $r->casino         = $request->input('casinoPatentes');
        $r->fecha_toma     = date('Y-m-d H:i:s');
        $r->usuario        = UsuarioController::getInstancia()->quienSoy()['usuario']['id_usuario'];
        $r->save();

        $files = $request->file('uploadPatentes');
        if ($files) {
            if (!is_array($files)) $files = [$files];
            foreach ($files as $file) {
                if (!($file instanceof \Illuminate\Http\UploadedFile) || !$file->isValid()) continue;
                $base = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
                $ext  = $file->getClientOriginalExtension();
                $safe = preg_replace('/\s+/', '_', $base);
                $name = time().'_'.Str::random(6).'_'.$safe.($ext?'.'.$ext:'');
                $file->storeAs('public/RegistroPatentes', $name);
                $r->archivos()->create([
                    'path'       => $name,
                    'usuario'    => $r->usuario,
                    'fecha_toma' => date('Y-m-d H:i:s'),
                ]);
            }
        }

        $patentes = (array) $request->input('pago_patenteDe', []);
        $cuotas   = (array) $request->input('pago_cuota',     []);
        $importes = (array) $request->input('pago_importe',   []);
        $fechas   = (array) $request->input('pago_fecha_pres',[]);
        $obs      = (array) $request->input('pago_observacion',[]);

        $n = max(count($patentes), count($cuotas), count($importes), count($fechas));
        for ($i=0; $i<$n; $i++) {
            $pid = $patentes[$i] ?? null;
            if (!$pid) continue;

            RegistroPatentes_patenteDe_pago::create([
                'registroPatentes' => $r->id_registroPatentes,
                'patenteDe'       => $pid,
                'cuota'            => $cuotas[$i]    ?? null,
                'importe'            => $importes[$i]  ?? null,
                'fecha_pres'       => $fechas[$i]    ?? null,
                'observacion'      => $obs[$i]       ?? null,
            ]);
        }

        DB::commit();
        return response()->json(['success'=>true, 'id'=>$r->id_registroPatentes]);
    } catch (\Exception $e) {
        DB::rollBack();
        return response()->json(['success'=>false, 'error'=>$e->getMessage()], 500);
    }
}

public function actualizarPatentes(Request $request, $id)
{
    DB::beginTransaction();
    try {
        $r = RegistroPatentes::findOrFail($id);

        $r->fecha_Patentes = $request->input('fecha_Patentes') ? ($request->input('fecha_Patentes').'-01') : null;
        $r->casino         = $request->input('casinoPatentes');
        $r->save();

        $files = $request->file('uploadPatentes');
        if ($files) {
            if (!is_array($files)) $files = [$files];
            foreach ($files as $file) {
                if (!($file instanceof \Illuminate\Http\UploadedFile) || !$file->isValid()) continue;
                $base = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
                $ext  = $file->getClientOriginalExtension();
                $safe = preg_replace('/\s+/', '_', $base);
                $name = time().'_'.Str::random(6).'_'.$safe.($ext?'.'.$ext:'');
                $file->storeAs('public/RegistroPatentes', $name);
                $r->archivos()->create([
                    'path'       => $name,
                    'usuario'    => UsuarioController::getInstancia()->quienSoy()['usuario']['id_usuario'],
                    'fecha_toma' => date('Y-m-d H:i:s'),
                ]);
            }
        }

        RegistroPatentes_patenteDe_pago::where('registroPatentes', $r->id_registroPatentes)->delete();

        $patentes = (array) $request->input('pago_patenteDe', []);
        $cuotas   = (array) $request->input('pago_cuota',     []);
        $importes = (array) $request->input('pago_importe',   []);
        $fechas   = (array) $request->input('pago_fecha_pres',[]);
        $obs      = (array) $request->input('pago_observacion',[]);

        $n = max(count($patentes), count($cuotas), count($importes), count($fechas));
        for ($i=0; $i<$n; $i++) {
            $pid = $patentes[$i] ?? null;
            if (!$pid) continue;

            RegistroPatentes_patenteDe_pago::create([
                'registroPatentes' => $r->id_registroPatentes,
                'patenteDe'       => $pid,
                'cuota'            => $cuotas[$i]    ?? null,
                'total'            => $importes[$i]  ?? null,
                'fecha_pres'       => $fechas[$i]    ?? null,
                'observacion'      => $obs[$i]       ?? null,
            ]);
        }

        DB::commit();
        return response()->json(['success'=>true]);
    } catch (\Exception $e) {
        DB::rollBack();
        return response()->json(['success'=>false,'error'=>$e->getMessage()], 500);
    }
}

public function ultimasPatentes(Request $request)
{
    $page    = max(1, (int)$request->query('page', 1));
    $perPage = max(1, (int)$request->query('page_size', 20));

    $user = Usuario::find(session('id_usuario'));
    $allowedCasinoIds = $user->casinos->pluck('id_casino')->toArray();

    $query = RegistroPatentes::withCount('archivos')
              ->with('casinoPatentes')
              ->orderBy('fecha_Patentes','desc')
              ->whereIn('casino', $allowedCasinoIds);

    if ($c = $request->query('id_casino')) {
        $query->where('casino', $c);
    }
    if ($desde = $request->query('desde')) {
        $query->where('fecha_Patentes','>=', strlen($desde)==7 ? ($desde.'-01') : $desde);
    }
    if ($hasta = $request->query('hasta')) {
        $query->where('fecha_Patentes','<=', strlen($hasta)==7 ? ($hasta.'-31') : $hasta);
    }

    $total = $query->count();

    $registros = $query->skip(($page-1)*$perPage)->take($perPage)->get();

    $datos = $registros->map(function($r){
        return [
            'id_registroPatentes' => $r->id_registroPatentes,
            'fecha_Patentes'      => $r->fecha_Patentes,
            'casino'              => $r->casinoPatentes->nombre ?? '-',
            'tiene_archivos'      => $r->archivos_count > 0,
        ];
    });

    return response()->json([
        'registros'  => $datos,
        'pagination' => [
            'current_page' => $page,
            'per_page'     => $perPage,
            'total'        => $total,
        ],
    ]);
}



public function eliminarPatentes($id){
  $Patentes = RegistroPatentes::findOrFail($id);
  if(is_null($Patentes)) return 0;
  RegistroPatentes::destroy($id);
  return 1;
}
public function llenarPatentes($id){
    $r = RegistroPatentes::with('casinoPatentes')->findOrFail($id);

    $pagos = RegistroPatentes_patenteDe_pago::with('PatenteDe')
        ->where('registroPatentes', $r->id_registroPatentes)
        ->orderBy('id_registroPatentes_patenteDe_pago')
        ->get()
        ->map(function($p){
            return [
                'patenteDe'   => $p->PatenteDe->nombre ?? '-',
                'cuota'       => $p->cuota,
                'importe'     => $p->importe,
                'observacion' => $p->observacion,
                'fecha_pres'  => $p->fecha_pres,
            ];
        })->values();

    return response()->json([
        'fecha'   => $r->fecha_Patentes,
        'casino'  => $r->casinoPatentes->nombre ?? '-',
        'pagos'   => $pagos,
    ]);
}

public function descargarPatentesCsv(Request $request)
{
    $casinoId = $request->query('casino');
    $desde    = $request->query('desde'); // yyyy-mm
    $hasta    = $request->query('hasta'); // yyyy-mm

    $user = Usuario::find(session('id_usuario'));
    $allowedCasinoIds = $user->casinos->pluck('id_casino')->toArray();

    $regs = RegistroPatentes::with(['casinoPatentes','pagos.PatenteDe'])
        ->whereIn('casino', $allowedCasinoIds)
        ->when($casinoId && (int)$casinoId !== 4, function($q) use($casinoId){ $q->where('casino',$casinoId); })
        ->when($desde, function($q) use($desde){ $q->where('fecha_Patentes','>=',$desde.'-01'); })
        ->when($hasta, function($q) use($hasta){ $q->where('fecha_Patentes','<=',$hasta.'-31'); })
        ->orderBy('casino')->orderBy('fecha_Patentes')
        ->get();

    $csv = [];
    $csv[] = ['Casino','Año','Mes','Elemento Patentable','Cuota','Fecha de Presentación','Monto Pagado','Observación'];

    setlocale(LC_TIME, 'es_ES.UTF-8','es_AR.UTF-8','es_ES','es_AR');

    foreach ($regs as $r) {
        $casinoNombre = ($r->casinoPatentes)->nombre ?? '-';
        $anio = date('Y', strtotime($r->fecha_Patentes));
        $mes  = function_exists('strftime')
                ? ucfirst(strftime('%B', strtotime($r->fecha_Patentes)))
                : date('F', strtotime($r->fecha_Patentes));

        if (!$r->pagos || $r->pagos->isEmpty()) {
            $csv[] = [$casinoNombre, $anio, $mes, '', '', '', '', ''];
            continue;
        }

        foreach ($r->pagos as $p) {
            $elemNombre = ($p->PatenteDe)->nombre ?? '';
            $fpres      = $p->fecha_pres ? date('d/m/Y', strtotime($p->fecha_pres)) : '';
            $monto      = is_null($p->importe) ? '' : number_format((float)$p->importe, 2, '.', '');
            $obsPago    = (string)($p->observacion ?? '');

            $csv[] = [
                $casinoNombre,
                $anio,
                $mes,
                $elemNombre,
                isset($p->cuota) ? $p->cuota : '',
                $fpres,
                $monto,
                $obsPago,
            ];
        }
    }

    $nombreCasino = ((int)$casinoId === 4 || !$casinoId) ? 'todos' : (Casino::find($casinoId)->nombre ?? 'desconocido');
    $filename = "Patentes_{$nombreCasino}_".date('Ymd_His').".csv";

    $h = fopen('php://temp', 'r+');
    foreach ($csv as $linea) { fputcsv($h, $linea, ','); }
    rewind($h); $out = stream_get_contents($h); fclose($h);

    return response($out, 200, [
        'Content-Type'        => 'text/csv; charset=UTF-8',
        'Content-Disposition' => "attachment; filename=\"$filename\"",
        'Cache-Control'       => 'no-store, no-cache',
    ]);
}


public function descargarPatentesXlsx(Request $request)
{
    $casinoId = $request->query('casino');
    $desde    = $request->query('desde');
    $hasta    = $request->query('hasta');

    $casino = Casino::findOrFail($casinoId);

    setlocale(LC_TIME, 'es_ES.UTF-8','es_AR.UTF-8','es_ES','es_AR');

    $regs = RegistroPatentes::with(['pagos.PatenteDe'])
        ->where('casino', $casinoId)
        ->when($desde, function($q) use($desde){ $q->where('fecha_Patentes','>=',$desde.'-01'); })
        ->when($hasta, function($q) use($hasta){ $q->where('fecha_Patentes','<=',$hasta.'-31'); })
        ->orderBy('fecha_Patentes')
        ->get();

    $items = [];
    foreach ($regs as $r) {
        $anio   = date('Y', strtotime($r->fecha_Patentes));
        $mesEsp = function_exists('strftime')
            ? ucfirst(strftime('%B', strtotime(date('Y-m-01', strtotime($r->fecha_Patentes)))))
            : date('F', strtotime($r->fecha_Patentes));

        if ($r->pagos && $r->pagos->count()) {
            foreach ($r->pagos as $p) {
                $items[] = [
                    'anio'   => $anio,
                    'mes'    => $mesEsp,
                    'elem'   => ($p->PatenteDe)->nombre ?? '-',
                    'cuota'  => isset($p->cuota) ? $p->cuota : '',
                    'fpres'  => $p->fecha_pres ? date('d/m/Y', strtotime($p->fecha_pres)) : '',
                    'monto'  => is_null($p->importe) ? '' : '$ '.number_format((float)$p->importe, 2, ',', '.'),
                    'obs'    => (string)($p->observacion ?? ''),
                ];
            }
        }
    }

    usort($items, function($a,$b){
        if ($a['anio'] != $b['anio']) return $a['anio'] < $b['anio'] ? -1 : 1;
        if ($a['elem'] != $b['elem']) return strcasecmp($a['elem'],$b['elem']);
        if ($a['mes']  != $b['mes'])  return strcasecmp($a['mes'],$b['mes']);
        return strnatcasecmp((string)$a['cuota'], (string)$b['cuota']);
    });

    $porAnio = [];
    foreach ($items as $it) {
        if (!isset($porAnio[$it['anio']])) $porAnio[$it['anio']] = [];
        if (!isset($porAnio[$it['anio']][$it['elem']])) $porAnio[$it['anio']][$it['elem']] = [];
        $porAnio[$it['anio']][$it['elem']][] = $it;
    }

    $filename = 'patentes_'.str_replace(' ','_', strtolower($casino->nombre)).'_'.date('Ymd_His');

    return \Excel::create($filename, function($excel) use ($porAnio,$casinoId) {

        $excel->sheet('PATENTE', function($sheet) use ($porAnio,$casinoId) {
            $fila = 1;

            $sheet->row($fila, [
                'PERIODOS',
                'Nro. Cuota',
                'Fecha de Presentación y Pago',
                'Total Pagado',
                'Observaciones',
            ]);

            $lastCol = \PHPExcel_Cell::stringFromColumnIndex(4);

            $sheet->cells("A1:{$lastCol}1", function($cells) use ($casinoId) {
                switch ($casinoId) {
                    case 1: $color = '#339966'; break;
                    case 2: $color = '#ff0000'; break;
                    case 3: $color = '#ffcc00'; break;
                    default:$color = '#222222';
                }
                $cells->setBackground($color);
                $cells->setFontColor('#000000');
                $cells->setFontWeight('bold');
                $cells->setAlignment('center');
            });
            $sheet->getStyle("A1:{$lastCol}1")->getAlignment()->setWrapText(true);
            $sheet->cells("A1:{$lastCol}20000", function($c){
                $c->setFontFamily('Arial');
                $c->setFontSize(10);
                $c->setAlignment('center');
                $c->setValignment('center');
            });
            $sheet->getStyle("A1:{$lastCol}1")->applyFromArray([
                'borders' => [
                    'allborders' => [
                        'style' => \PHPExcel_Style_Border::BORDER_THICK,
                        'color' => ['argb' => 'FF000000']
                    ]
                ]
            ]);

            $sheet->setWidth('A', 12);
            $sheet->setHeight(1,30);
            $sheet->setWidth('B', 10);
            $sheet->setWidth('C', 17);
            $sheet->setWidth('D', 16);
            $sheet->setWidth('E', 50);

            $sheet->setFreeze('A2');

            $fila++;

            foreach ($porAnio as $anio => $porElem) {
                $sheet->mergeCells("A{$fila}:{$lastCol}{$fila}");
                $sheet->setCellValue("A{$fila}", 'Año '.$anio);
                $sheet->cells("A{$fila}:{$lastCol}{$fila}", function($c){
                    $c->setBackground('#CCCCCC');
                    $c->setFontWeight('bold');
                    $c->setAlignment('center');
                    $c->setFontSize(13);
                    $c->setValignment('center');
                });
                $sheet->getStyle("A{$fila}:{$lastCol}{$fila}")->applyFromArray([
                    'borders' => [
                        'allborders' => [
                            'style' => \PHPExcel_Style_Border::BORDER_THICK,
                            'color' => ['argb' => 'FF000000']
                        ]
                    ]
                ]);
                $sheet->setHeight($fila, 18);
                $fila++;

                foreach ($porElem as $elemNombre => $rows) {
                    $inicioBloque = $fila;

                    $sheet->mergeCells("A{$fila}:{$lastCol}{$fila}");
                    $sheet->setCellValue("A{$fila}", $elemNombre);
                    $sheet->cells("A{$fila}:{$lastCol}{$fila}", function($c){
                        $c->setBackground('#FFFF99');
                        $c->setFontSize(13);
                        $c->setFontWeight('bold');
                        $c->setAlignment('center');
                        $c->setValignment('center');
                    });
                    $sheet->getStyle("A{$fila}:{$lastCol}{$fila}")->applyFromArray([
                        'borders' => [
                            'allborders' => [
                                'style' => \PHPExcel_Style_Border::BORDER_THICK,
                                'color' => ['argb' => 'FF000000']
                            ]
                        ]
                    ]);
                    $sheet->setHeight($fila, 18);
                    $fila++;

                    $inicioReg = $fila;

                    foreach ($rows as $row) {
                        $sheet->row($fila, [
                            $row['mes'],
                            $row['cuota'],
                            $row['fpres'],
                            $row['monto'],
                            $row['obs'],
                        ]);

                        $sheet->cells("A{$fila}", function($c){
                            $c->setAlignment('left');
                            $c->setValignment('center');
                            $c->setFontWeight('bold');
                            $c->setBackground('#FFFF99');
                        });
                        $sheet->cells("B{$fila}:D{$fila}", function($c){
                            $c->setAlignment('center');
                            $c->setValignment('center');
                        });
                        $sheet->cells("E{$fila}", function($c){
                            $c->setAlignment('left');
                            $c->setValignment('center');
                        });

                        $fila++;
                    }

                    $finReg = $fila - 1;
                    if ($finReg >= $inicioReg) {
                        $sheet->getStyle("A{$inicioReg}:{$lastCol}{$finReg}")->applyFromArray([
                            'borders' => [
                                'allborders' => [
                                    'style' => \PHPExcel_Style_Border::BORDER_THIN,
                                    'color' => ['argb' => 'FF000000']
                                ]
                            ]
                        ]);
                        $sheet->getStyle("A{$inicioReg}:{$lastCol}{$finReg}")->applyFromArray([
                            'borders' => [
                                'outline' => [
                                    'style' => \PHPExcel_Style_Border::BORDER_THICK,
                                    'color' => ['argb' => 'FF000000']
                                ]
                            ]
                        ]);
                    }
                }
            }
        });

    })->export('xlsx');
}


public function descargarPatentesXlsxTodos(Request $request)
{
    $desde = $request->query('desde'); // yyyy-mm
    $hasta = $request->query('hasta'); // yyyy-mm

    $user = Usuario::find(session('id_usuario'));
    $allowedCasinoIds = $user->casinos->pluck('id_casino')->toArray();
    $casinos = Casino::whereIn('id_casino', $allowedCasinoIds)
                ->pluck('nombre','id_casino');

    setlocale(LC_TIME, 'es_ES.UTF-8','es_AR.UTF-8','es_ES','es_AR');

    return \Excel::create('Patentes_todos_'.date('Ymd_His'), function($excel) use ($casinos, $desde, $hasta) {

        foreach ($casinos as $casinoId => $casinoNombre) {

            $regs = RegistroPatentes::where('casino', $casinoId)
                ->when($desde, function($q) use($desde){
                    $q->where('fecha_Patentes','>=', $desde.'-01');
                })
                ->when($hasta, function($q) use($hasta){
                    $q->where('fecha_Patentes','<=', $hasta.'-31');
                })
                ->orderBy('fecha_Patentes')
                ->get(['id_registroPatentes','fecha_Patentes']);

            $items = [];
            foreach ($regs as $r) {
                $anio   = date('Y', strtotime($r->fecha_Patentes));
                $mesEsp = function_exists('strftime')
                    ? ucfirst(strftime('%B', strtotime(date('Y-m-01', strtotime($r->fecha_Patentes)))))
                    : date('F', strtotime($r->fecha_Patentes));

                $pagos = RegistroPatentes_patenteDe_pago::with('PatenteDe')
                            ->where('registroPatentes', $r->id_registroPatentes)
                            ->orderBy('id_registroPatentes_patenteDe_pago')
                            ->get();

                foreach ($pagos as $p) {
                    $items[] = [
                        'anio'     => $anio,
                        'mes'      => $mesEsp,
                        'patente'  => $p->PatenteDe ? $p->PatenteDe->nombre : '-',
                        'cuota'    => isset($p->cuota) ? $p->cuota : '',
                        'fpres'    => $p->fecha_pres ? date('d/m/Y', strtotime($p->fecha_pres)) : '',
                        'monto'    => is_null($p->importe) ? '' : '$ '.number_format((float)$p->importe, 2, ',', '.'),
                        'obs'      => (string)($p->observacion ?? ''),
                    ];
                }

            }

            usort($items, function($a,$b){
                if ($a['anio']    != $b['anio'])    return $a['anio'] < $b['anio'] ? -1 : 1;
                if ($a['patente'] != $b['patente']) return strcasecmp($a['patente'],$b['patente']);
                if ($a['mes']     != $b['mes'])     return strcasecmp($a['mes'],$b['mes']);
                return strnatcasecmp((string)$a['cuota'], (string)$b['cuota']);
            });

            $porAnio = [];
            foreach ($items as $it) {
                if (!isset($porAnio[$it['anio']])) $porAnio[$it['anio']] = [];
                if (!isset($porAnio[$it['anio']][$it['patente']])) $porAnio[$it['anio']][$it['patente']] = [];
                $porAnio[$it['anio']][$it['patente']][] = $it;
            }

            $excel->sheet($casinoNombre, function($sheet) use ($porAnio, $casinoId) {
                $fila = 1; $lastCol = 'E';

                $sheet->row($fila, ['PERIODO','Nro. Cuota','Fecha de Presentación y Pago','Total Pagado','Observación']);
                $sheet->cells("A1:{$lastCol}1", function($c) use ($casinoId){
                    $color = ($casinoId==1?'#008f39':($casinoId==2?'#ff0000':($casinoId==3?'#ffff00':'#222222')));
                    $c->setBackground($color);
                    $c->setFontColor('#000000');
                    $c->setFontWeight('bold');
                    $c->setAlignment('center');
                });
                $sheet->getStyle("A1:{$lastCol}1")->getAlignment()->setWrapText(true)
                      ->setVertical(\PHPExcel_Style_Alignment::VERTICAL_CENTER);
                $sheet->setHeight(1,40);
                $sheet->cells("A1:{$lastCol}9999", function($c){
                    $c->setFontFamily('Arial'); $c->setFontSize(10);
                });
                $sheet->setWidth('A',18); $sheet->setWidth('B',12);
                $sheet->setWidth('C',25); $sheet->setWidth('D',18); $sheet->setWidth('E',40);
                $sheet->setFreeze('A2');

                $fila++;

                foreach ($porAnio as $anio => $porPatente) {
                    $sheet->mergeCells("A{$fila}:{$lastCol}{$fila}");
                    $sheet->setCellValue("A{$fila}", "Año {$anio}");
                    $sheet->cells("A{$fila}:{$lastCol}{$fila}", function($c){
                        $c->setBackground('#CCCCCC'); $c->setFontWeight('bold');
                        $c->setFontSize(13); $c->setAlignment('center');
                    });
                    $sheet->setHeight($fila,20);
                    $fila++;

                    foreach ($porPatente as $patenteNombre => $rows) {
                        $sheet->mergeCells("A{$fila}:{$lastCol}{$fila}");
                        $sheet->setCellValue("A{$fila}", $patenteNombre);
                        $sheet->cells("A{$fila}:{$lastCol}{$fila}", function($c){
                            $c->setBackground('#FFFF99'); $c->setFontWeight('bold');
                            $c->setFontSize(12); $c->setAlignment('center');
                        });
                        $fila++;

                        foreach ($rows as $row) {
                            $sheet->row($fila, [
                                $row['mes'],
                                $row['cuota'],
                                $row['fpres'],
                                $row['monto'],
                                $row['obs'],
                            ]);
                            $sheet->cells("A{$fila}", function($c){
                                $c->setBackground('#FFFF99'); $c->setFontWeight('bold'); $c->setAlignment('left');
                            });
                            $sheet->cells("A{$fila}:{$lastCol}{$fila}", function($c){
                                $c->setAlignment('center');
                            });
                            $fila++;
                        }
                    }
                }

                $sheet->getStyle("A1:{$lastCol}1")->applyFromArray([
                    'borders'=>['allborders'=>[
                        'style'=>\PHPExcel_Style_Border::BORDER_THICK,
                        'color'=>['argb'=>'FF000000']
                    ]]
                ]);
                $lastRow = $fila - 1;
                if ($lastRow >= 2) {
                    $sheet->getStyle("A2:{$lastCol}{$lastRow}")->applyFromArray([
                        'borders'=>['allborders'=>[
                            'style'=>\PHPExcel_Style_Border::BORDER_THIN,
                            'color'=>['argb'=>'FF000000']
                        ]]
                    ]);
                }
            });
        }
    })->export('xlsx');
}


//IMPUESTO INMOBILIARIO

public function guardarRegistroImpInmobiliario_partida(Request $request){

      try {
          DB::beginTransaction();

          $ImpInmobiliario_partida = new RegistroImpInmobiliario_partida();
          $ImpInmobiliario_partida->partida = $request->nombre_ImpInmobiliario_partida;
          $ImpInmobiliario_partida->casino = $request->CasinoImpInmobiliario_partida;
          $ImpInmobiliario_partida->estado = 1;
          $ImpInmobiliario_partida->fecha_toma = date('Y-m-d h:i:s', time());
          $ImpInmobiliario_partida->usuario = UsuarioController::getInstancia()->quienSoy()['usuario']['id_usuario'];
          $ImpInmobiliario_partida->save();

          DB::commit();

          return response()->json([
            'success' => true,
             'id' => $ImpInmobiliario_partida->id_registroImpInmobiliario_partida,
             'ImpInmobiliario_partida'  => $request->all(),
           ]);
      } catch (\Exception $e) {
          DB::rollBack();
          return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
      }

}


public function getImpInmobiliario_partida(){
  $rows = RegistroImpInmobiliario_partida::with('CasinoImpInmobiliario_partida')
    ->orderBy('casino')
    ->get(['id_registroImpInmobiliario_partida as id','partida','casino','estado'])
    ->map(function($r){
      return [
        'id'             => $r->id,
        'partida'        => $r->partida,
        'estado'         => $r->estado,
        'casino_id'      => $r->casino,
        'casino_nombre'  => ($r->CasinoImpInmobiliario_partida ? $r->CasinoImpInmobiliario_partida->nombre : '-'),
      ];
    })->values();

  return response()->json($rows);
}


public function archivosImpInmobiliario($id)
{
    $ImpInmobiliario = RegistroImpInmobiliario::with('archivos')->findOrFail($id);

    $files = $ImpInmobiliario->archivos->map(function($a){
        return [
            'id'     => $a->id_registro_archivo,
            'nombre' => basename($a->path),
            'url'    => \Illuminate\Support\Facades\Storage::url($a->path),
            'fecha'  => $a->fecha_toma,
        ];
    });

    return response()->json($files->values());
}

public function llenarImpInmobiliarioEdit($id)
{
    $r = RegistroImpInmobiliario::with('casinoImpInmobiliario')->findOrFail($id);

    $pagos = RegistroImpInmobiliario_partida_pago::with('partidaImpInmobiliario')
        ->where('registroImpInmobiliario', $r->id_registroImpInmobiliario)
        ->orderBy('id_registroImpInmobiliario_partida_pago')
        ->get()
        ->map(function($p){
            return [
                'id'          => $p->id_registroImpInmobiliario_partida_pago,
                'partida_id'  => $p->partida,
                'partida'     => $p->partidaImpInmobiliario ? $p->partidaImpInmobiliario->partida : '-',
                'cuota'       => $p->cuota,
                'importe'     => $p->total,
                'observacion' => $p->observacion,
                'fecha_pres'  => $p->fecha_pres,
            ];
        })->values();

    return response()->json([
        'id'            => $r->id_registroImpInmobiliario,
        'fecha'         => $r->fecha_ImpInmobiliario,
        'casino'        => $r->casino,
        'casino_nombre' => $r->casinoImpInmobiliario ? $r->casinoImpInmobiliario->nombre : '-',
        'pagos'         => $pagos,
    ]);
}

public function llenarImpInmobiliario($id)
{
    $r = RegistroImpInmobiliario::with('casinoImpInmobiliario')->findOrFail($id);

    $pagos = RegistroImpInmobiliario_partida_pago::with('partidaImpInmobiliario')
        ->where('registroImpInmobiliario', $r->id_registroImpInmobiliario)
        ->orderBy('id_registroImpInmobiliario_partida_pago')
        ->get()
        ->map(function($p){
            return [
                'partida'    => $p->partidaImpInmobiliario ? $p->partidaImpInmobiliario->partida : '-',
                'cuota'      => $p->cuota,
                'observacion' => $p->observacion,
                'importe'    => $p->total,
                'fecha_pres' => $p->fecha_pres,
            ];
        })->values();

    return response()->json([
        'fecha'   => $r->fecha_ImpInmobiliario,
        'casino'  => $r->casinoImpInmobiliario ? $r->casinoImpInmobiliario->nombre : '-',
        'pagos'   => $pagos,
    ]);
}


public function actualizarImpInmobiliario(Request $request, $id)
{
    DB::beginTransaction();
    try {
        $r = RegistroImpInmobiliario::findOrFail($id);

        $r->fecha_ImpInmobiliario = $request->input('fecha_ImpInmobiliario') ? ($request->input('fecha_ImpInmobiliario').'-01') : null;
        $r->casino                = $request->input('casinoImpInmobiliario');
        $r->save();

        $files = $request->file('uploadImpInmobiliario');
        if ($files) {
            if (!is_array($files)) $files = [$files];
            foreach ($files as $file) {
                if (!($file instanceof \Illuminate\Http\UploadedFile) || !$file->isValid()) continue;

                $base = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
                $ext  = $file->getClientOriginalExtension();
                $safe = preg_replace('/\s+/', '_', $base);
                $name = time().'_'.Str::random(6).'_'.$safe.($ext?'.'.$ext:'');

                $file->storeAs('public/RegistroImpInmobiliario', $name);

                $r->archivos()->create([
                    'path'       => $name,
                    'usuario'    => UsuarioController::getInstancia()->quienSoy()['usuario']['id_usuario'],
                    'fecha_toma' => date('Y-m-d H:i:s'),
                ]);
            }
        }

        RegistroImpInmobiliario_partida_pago::where('registroImpInmobiliario', $r->id_registroImpInmobiliario)->delete();

        $partidas = (array) $request->input('pago_partida', []);
        $cuotas   = (array) $request->input('pago_cuota',   []);
        $obs      = (array) $request->input('pago_observacion',[]);
        $montos   = (array) $request->input('pago_importe', []);
        $fechas   = (array) $request->input('pago_fecha_pres', []);

        $n = max(count($partidas), count($cuotas), count($montos), count($fechas));
        for ($i=0; $i<$n; $i++) {
            $pid = isset($partidas[$i]) ? $partidas[$i] : null;
            if (!$pid) continue;

            RegistroImpInmobiliario_partida_pago::create([
                'partida'                 => $pid,
                'registroImpInmobiliario' => $r->id_registroImpInmobiliario,
                'cuota'                   => isset($cuotas[$i]) ? $cuotas[$i] : null,
                'total'                 => isset($montos[$i]) ? $montos[$i] : null,
                'observacion'                   => isset($obs[$i]) ? $obs[$i] : null,
                'fecha_pres'              => isset($fechas[$i]) ? $fechas[$i] : null,
            ]);
        }

        DB::commit();
        return response()->json(['success'=>true]);
    } catch (\Exception $e) {
        DB::rollBack();
        return response()->json(['success'=>false, 'error'=>$e->getMessage()], 500);
    }
}



public function getImpInmobiliario_partidaPorCasino(Request $request){
  $casinoId = $request->query('casino');
  $edit = $request->query('edit') ? 1 : 0;

  $q = RegistroImpInmobiliario_partida::with('CasinoImpInmobiliario_partida')
        ->orderBy('casino');

  if($casinoId) $q->where('casino', $casinoId);
  if(!$edit) $q->where('estado',1);

  $rows = $q->get(['id_registroImpInmobiliario_partida as id','partida','casino','estado'])
            ->map(function($r){
              return [
                'id'            => $r->id,
                'partida'       => $r->partida,
                'casino_id'     => $r->casino,
                'estado' => $r->estado,
                'casino_nombre' => $r->CasinoImpInmobiliario_partida ? $r->CasinoImpInmobiliario_partida->nombre : '-',
              ];
            })->values();

  return response()->json($rows);
}


public function EliminarImpInmobiliario_partida($id){
    RegistroImpInmobiliario_partida::findOrFail($id)->delete();
    return response()->json(array('ok'=>true));
}

public function modificarImpInmobiliario_partida(Request $request){

  $id = $request->ModifId_ImpInmobiliario_partida;
  $partida = $request->ModifImpInmobiliario_partida_partida;
  $estado = $request->ModifImpInmobiliario_partida_estado;
  try {
    DB::beginTransaction();
    $ImpInmobiliario_partida = RegistroImpInmobiliario_partida::findOrFail($id);
    $ImpInmobiliario_partida->partida = $partida;
    $ImpInmobiliario_partida->estado = $estado;
    $ImpInmobiliario_partida->save();
    DB::commit();

    return response()->json([
      'success' => true,
       'id' => $ImpInmobiliario_partida->id_registroImpInmobiliario_partida,
       'estado' => $ImpInmobiliario_partida->estado,
       'partida' => $ImpInmobiliario_partida->partida,
     ]);

    } catch (\Exception $e) {
    DB::rollBack();
    return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
  }

}
public function guardarImpInmobiliario(Request $request)
{
    DB::beginTransaction();
    try {
        $r = new RegistroImpInmobiliario();
        $r->fecha_ImpInmobiliario = $request->input('fecha_ImpInmobiliario') ? ($request->input('fecha_ImpInmobiliario').'-01') : null;
        $r->casino                = $request->input('casinoImpInmobiliario');
        $r->fecha_toma            = date('Y-m-d H:i:s');
        $r->usuario               = UsuarioController::getInstancia()->quienSoy()['usuario']['id_usuario'];
        $r->save();

        $files = $request->file('uploadImpInmobiliario');
        if ($files) {
            if (!is_array($files)) $files = [$files];
            foreach ($files as $file) {
                if (!($file instanceof \Illuminate\Http\UploadedFile) || !$file->isValid()) continue;

                $base = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
                $ext  = $file->getClientOriginalExtension();
                $safe = preg_replace('/\s+/', '_', $base);
                $name = time().'_'.Str::random(6).'_'.$safe.($ext?'.'.$ext:'');

                $file->storeAs('public/RegistroImpInmobiliario', $name);

                $r->archivos()->create([
                    'path'       => $name,
                    'usuario'    => $r->usuario,
                    'fecha_toma' => date('Y-m-d H:i:s'),
                ]);
            }
        }

        $partidas = (array) $request->input('pago_partida', []);
        $cuotas   = (array) $request->input('pago_cuota',   []);
        $obs   = (array) $request->input('pago_observacion',   []);

        $montos   = (array) $request->input('pago_importe', []);
        $fechas   = (array) $request->input('pago_fecha_pres', []);
        $n = max(count($partidas), count($cuotas), count($montos), count($fechas));
        for ($i=0; $i<$n; $i++) {
            $pid = isset($partidas[$i]) ? $partidas[$i] : null;
            if (!$pid) continue;

            RegistroImpInmobiliario_partida_pago::create([
                'partida'                 => $pid,
                'registroImpInmobiliario' => $r->id_registroImpInmobiliario,
                'cuota'                   => isset($cuotas[$i]) ? $cuotas[$i] : null,
                'total'                 => isset($montos[$i]) ? $montos[$i] : null,
                'observacion'                   => isset($obs[$i]) ? $obs[$i] : null,

                'fecha_pres'              => isset($fechas[$i]) ? $fechas[$i] : null,
            ]);
        }

        DB::commit();
        return response()->json(['success'=>true, 'id'=>$r->id_registroImpInmobiliario]);
    } catch (\Exception $e) {
        DB::rollBack();
        return response()->json(['success'=>false, 'error'=>$e->getMessage()], 500);
    }
}

public function ultimasImpInmobiliario(Request $request)
{
    $page    = max(1, (int)$request->query('page', 1));
    $perPage = max(1, (int)$request->query('page_size', 20));

    $user = Usuario::find(session('id_usuario'));
    $allowedCasinoIds = $user->casinos->pluck('id_casino')->toArray();


    $query = RegistroImpInmobiliario
              ::withCount('archivos')
              ->orderBy('fecha_ImpInmobiliario', 'desc');

    if ($c = $request->query('id_casino')) {
      $query->where('casino', $c);
    }
    if ($desde = $request->query('desde')){
      $query->where('fecha_ImpInmobiliario',">=",$desde);
    }
    if ($hasta = $request->query('hasta')){
      $query->where('fecha_ImpInmobiliario',"<=",$hasta);
    }

    $total = $query->count();

    $registros = $query
        ->skip(($page - 1) * $perPage)
        ->take($perPage)
        ->get();

    $datos = $registros->map(function($r) {
        return [
            'id_registroImpInmobiliario' => $r->id_registroImpInmobiliario,
            'fecha_ImpInmobiliario'   => $r->fecha_ImpInmobiliario,
            'cuota'   => $r->cuota,
            'casino' => $r->casinoImpInmobiliario ? $r->casinoImpInmobiliario->nombre : '-',
            'partida' => $r->ImpInmobiliario_partida ? $r->ImpInmobiliario_partida->partida : '-',
	           'tiene_archivos' => $r->archivos_count>0,
             ];
    });

    return response()->json([
        'registros'  => $datos,
        'pagination' => [
            'current_page' => $page,
            'per_page'     => $perPage,
            'total'        => $total,
        ],
    ]);
}


public function eliminarImpInmobiliario($id){
  $ImpInmobiliario = RegistroImpInmobiliario::findOrFail($id);
  if(is_null($ImpInmobiliario)) return 0;
  RegistroImpInmobiliario::destroy($id);
  return 1;
}


public function descargarImpInmobiliarioCsv(Request $request)
{
    $casinoId = $request->query('casino');
    $desde    = $request->query('desde'); // yyyy-mm
    $hasta    = $request->query('hasta'); // yyyy-mm

    $user = Usuario::find(session('id_usuario'));
    $allowedCasinoIds = $user->casinos->pluck('id_casino')->toArray();

    $regs = RegistroImpInmobiliario::with(['casinoImpInmobiliario','pagos.partidaImpInmobiliario'])
        ->whereIn('casino', $allowedCasinoIds)
        ->when($casinoId && (int)$casinoId !== 4, function($q) use($casinoId){ $q->where('casino',$casinoId); })
        ->when($desde, function($q) use($desde){ $q->where('fecha_ImpInmobiliario','>=',$desde.'-01'); })
        ->when($hasta, function($q) use($hasta){ $q->where('fecha_ImpInmobiliario','<=',$hasta.'-31'); })
        ->orderBy('casino')->orderBy('fecha_ImpInmobiliario')
        ->get();

    $csv = [];
    $csv[] = ['Casino','Año','Mes','Partida','Cuota','Fecha de Presentación','Monto Pagado','Observación'];

    setlocale(LC_TIME, 'es_ES.UTF-8','es_AR.UTF-8','es_ES','es_AR');

    foreach ($regs as $r) {
        $casinoNombre = ($r->casinoImpInmobiliario)->nombre ?? '-';
        $anio = date('Y', strtotime($r->fecha_ImpInmobiliario));
        $mes  = function_exists('strftime')
                ? ucfirst(strftime('%B', strtotime($r->fecha_ImpInmobiliario)))
                : date('F', strtotime($r->fecha_ImpInmobiliario));

        if (!$r->pagos || $r->pagos->isEmpty()) {
            $csv[] = [$casinoNombre, $anio, $mes, '', '', '', '', ''];
            continue;
        }

        foreach ($r->pagos as $p) {
            $partidaNombre = ($p->partidaImpInmobiliario)->partida ?? '';
            $fpres         = $p->fecha_pres ? date('d/m/Y', strtotime($p->fecha_pres)) : '';
            $monto         = is_null($p->total) ? '' : number_format((float)$p->total, 2, '.', '');
            $obsPago       = (string)($p->observacion ?? '');

            $csv[] = [
                $casinoNombre,
                $anio,
                $mes,
                $partidaNombre,
                isset($p->cuota) ? $p->cuota : '',
                $fpres,
                $monto,
                $obsPago,
            ];
        }
    }

    $nombreCasino = ((int)$casinoId === 4 || !$casinoId) ? 'todos' : (Casino::find($casinoId)->nombre ?? 'desconocido');
    $filename = "ImpInmobiliario_{$nombreCasino}_".date('Ymd_His').".csv";

    $h = fopen('php://temp', 'r+');
    foreach ($csv as $linea) { fputcsv($h, $linea, ','); }
    rewind($h); $out = stream_get_contents($h); fclose($h);

    return response($out, 200, [
        'Content-Type'        => 'text/csv; charset=UTF-8',
        'Content-Disposition' => "attachment; filename=\"$filename\"",
        'Cache-Control'       => 'no-store, no-cache',
    ]);
}


public function descargarImpInmobiliarioXlsx(Request $request)
{
    $casinoId = $request->query('casino');
    $desde    = $request->query('desde');
    $hasta    = $request->query('hasta');

    $casino = Casino::findOrFail($casinoId);

    setlocale(LC_TIME, 'es_ES.UTF-8','es_AR.UTF-8','es_ES','es_AR');

    $regs = RegistroImpInmobiliario::with(['pagos.partidaImpInmobiliario'])
        ->where('casino', $casinoId)
        ->when($desde, function($q) use($desde){ $q->where('fecha_ImpInmobiliario','>=',$desde.'-01'); })
        ->when($hasta, function($q) use($hasta){ $q->where('fecha_ImpInmobiliario','<=',$hasta.'-31'); })
        ->orderBy('fecha_ImpInmobiliario')
        ->get();

    $items = [];
    foreach ($regs as $r) {
        $anio   = date('Y', strtotime($r->fecha_ImpInmobiliario));
        $mesEsp = function_exists('strftime')
            ? ucfirst(strftime('%B', strtotime(date('Y-m-01', strtotime($r->fecha_ImpInmobiliario)))))
            : date('F', strtotime($r->fecha_ImpInmobiliario));

        if ($r->pagos && $r->pagos->count()) {
            foreach ($r->pagos as $p) {
                $items[] = [
                    'anio'     => $anio,
                    'mes'      => $mesEsp,
                    'partida'  => ($p->partidaImpInmobiliario)->partida ?? '-',
                    'cuota'    => isset($p->cuota) ? $p->cuota : '',
                    'fpres'    => $p->fecha_pres ? date('d/m/Y', strtotime($p->fecha_pres)) : '',
                    'monto'    => is_null($p->total) ? '' : '$ '.number_format((float)$p->total, 2, ',', '.'),
                    'obs'      => (string)($p->observacion ?? ''),
                ];
            }
        }
    }

    usort($items, function($a,$b){
        if ($a['anio'] != $b['anio']) return $a['anio'] < $b['anio'] ? -1 : 1;
        if ($a['partida'] != $b['partida']) return strcasecmp($a['partida'],$b['partida']);
        if ($a['mes'] != $b['mes']) return strcasecmp($a['mes'],$b['mes']);
        return strnatcasecmp((string)$a['cuota'], (string)$b['cuota']);
    });

    $porAnio = [];
    foreach ($items as $it) {
        if (!isset($porAnio[$it['anio']])) $porAnio[$it['anio']] = [];
        if (!isset($porAnio[$it['anio']][$it['partida']])) $porAnio[$it['anio']][$it['partida']] = [];
        $porAnio[$it['anio']][$it['partida']][] = $it;
    }

    $filename = 'ImpInmobiliario_'.str_replace(' ','_', strtolower($casino->nombre)).'_'.date('Ymd_His');

    return \Excel::create($filename, function($excel) use ($porAnio, $casinoId) {

        $excel->sheet('Impuesto Inmobiliario', function($sheet) use ($porAnio, $casinoId) {
            $fila = 1; $lastCol = 'E';

            $sheet->row($fila, ['PERIODO','CUOTA','Fecha de Presentación','Monto Pagado','Observación']);
            $sheet->cells("A1:{$lastCol}1", function($c) use ($casinoId){
                $color = ($casinoId==1?'#008f39':($casinoId==2?'#ff0000':($casinoId==3?'#ffff00':'#222222')));
                $c->setBackground($color); $c->setFontColor('#000000'); $c->setFontWeight('bold'); $c->setAlignment('center');
            });
            $sheet->getStyle("A1:{$lastCol}1")->getAlignment()->setWrapText(true)->setVertical(\PHPExcel_Style_Alignment::VERTICAL_CENTER);
            $sheet->setHeight(1,40);
            $sheet->cells("A1:{$lastCol}9999", function($c){ $c->setFontFamily('Arial'); $c->setFontSize(10); });

            $fila++;

            foreach ($porAnio as $anio => $porPartida) {
                $sheet->mergeCells("A{$fila}:{$lastCol}{$fila}");
                $sheet->setCellValue("A{$fila}", "Año {$anio}");
                $sheet->cells("A{$fila}:{$lastCol}{$fila}", function($c){
                    $c->setBackground('#CCCCCC'); $c->setFontWeight('bold'); $c->setFontSize(13); $c->setAlignment('center');
                });
                $sheet->setHeight($fila,20);
                $fila++;

                foreach ($porPartida as $partida => $rows) {
                    $sheet->mergeCells("A{$fila}:{$lastCol}{$fila}");
                    $sheet->setCellValue("A{$fila}", "Partida {$partida}");
                    $sheet->cells("A{$fila}:{$lastCol}{$fila}", function($c){
                        $c->setBackground('#FFFF99'); $c->setFontWeight('bold'); $c->setFontSize(12); $c->setAlignment('center');
                    });
                    $fila++;

                    foreach ($rows as $row) {
                        $sheet->row($fila, [
                            $row['mes'],
                            $row['cuota'],
                            $row['fpres'],
                            $row['monto'],
                            $row['obs'],
                        ]);
                        $sheet->cells("A{$fila}", function($c){ $c->setBackground('#FFFF99'); $c->setFontWeight('bold'); $c->setAlignment('left'); });
                        $sheet->cells("A{$fila}:{$lastCol}{$fila}", function($c){ $c->setAlignment('center'); });
                        $fila++;
                    }
                }
            }

            $sheet->getStyle("A1:{$lastCol}1")->applyFromArray([
                'borders'=>['allborders'=>['style'=>\PHPExcel_Style_Border::BORDER_THICK,'color'=>['argb'=>'FF000000']]]
            ]);
            $lastRow = $fila - 1;
            if ($lastRow >= 2) {
                $sheet->getStyle("A2:{$lastCol}{$lastRow}")->applyFromArray([
                    'borders'=>['allborders'=>['style'=>\PHPExcel_Style_Border::BORDER_THIN,'color'=>['argb'=>'FF000000']]]
                ]);
            }
            $sheet->setWidth('A',18); $sheet->setWidth('B',10); $sheet->setWidth('C',22); $sheet->setWidth('D',18); $sheet->setWidth('E',40);
            $sheet->setFreeze('A2');
        });

    })->export('xlsx');
}

public function descargarImpInmobiliarioXlsxTodos(Request $request)
{
    $desde = $request->query('desde');
    $hasta = $request->query('hasta');

    $user = Usuario::find(session('id_usuario'));
    $allowedCasinoIds = $user->casinos->pluck('id_casino')->toArray();
    $casinos = Casino::whereIn('id_casino', $allowedCasinoIds)->pluck('nombre','id_casino');

    setlocale(LC_TIME, 'es_ES.UTF-8','es_AR.UTF-8','es_ES','es_AR');

    return \Excel::create('ImpInmobiliario_todos_'.date('Ymd_His'), function($excel) use ($casinos, $desde, $hasta) {

        foreach ($casinos as $casinoId => $casinoNombre) {
            $regs = RegistroImpInmobiliario::with(['pagos.partidaImpInmobiliario'])
                ->where('casino', $casinoId)
                ->when($desde, function($q) use($desde){ $q->where('fecha_ImpInmobiliario','>=',$desde.'-01'); })
                ->when($hasta, function($q) use($hasta){ $q->where('fecha_ImpInmobiliario','<=',$hasta.'-31'); })
                ->orderBy('fecha_ImpInmobiliario')
                ->get();

            $items = [];
            foreach ($regs as $r) {
                $anio   = date('Y', strtotime($r->fecha_ImpInmobiliario));
                $mesEsp = function_exists('strftime')
                    ? ucfirst(strftime('%B', strtotime(date('Y-m-01', strtotime($r->fecha_ImpInmobiliario)))))
                    : date('F', strtotime($r->fecha_ImpInmobiliario));

                if ($r->pagos && $r->pagos->count()) {
                    foreach ($r->pagos as $p) {
                        $items[] = [
                            'anio'    => $anio,
                            'mes'     => $mesEsp,
                            'partida' => ($p->partidaImpInmobiliario)->partida ?? '-',
                            'cuota'   => isset($p->cuota) ? $p->cuota : '',
                            'fpres'   => $p->fecha_pres ? date('d/m/Y', strtotime($p->fecha_pres)) : '',
                            'monto'   => is_null($p->total) ? '' : '$ '.number_format((float)$p->total, 2, ',', '.'),
                            'obs'     => (string)($p->observacion ?? ''),
                        ];
                    }
                }
            }

            usort($items, function($a,$b){
                if ($a['anio'] != $b['anio']) return $a['anio'] < $b['anio'] ? -1 : 1;
                if ($a['partida'] != $b['partida']) return strcasecmp($a['partida'],$b['partida']);
                if ($a['mes'] != $b['mes']) return strcasecmp($a['mes'],$b['mes']);
                return strnatcasecmp((string)$a['cuota'], (string)$b['cuota']);
            });

            $porAnio = [];
            foreach ($items as $it) {
                if (!isset($porAnio[$it['anio']])) $porAnio[$it['anio']] = [];
                if (!isset($porAnio[$it['anio']][$it['partida']])) $porAnio[$it['anio']][$it['partida']] = [];
                $porAnio[$it['anio']][$it['partida']][] = $it;
            }

            $excel->sheet($casinoNombre, function($sheet) use ($porAnio, $casinoId) {
                $fila = 1; $lastCol = 'E';

                $sheet->row($fila, ['PERIODO','CUOTA','Fecha de Presentación','Monto Pagado','Observación']);
                $sheet->cells("A1:{$lastCol}1", function($c) use ($casinoId){
                    $color = ($casinoId==1?'#008f39':($casinoId==2?'#ff0000':($casinoId==3?'#ffff00':'#222222')));
                    $c->setBackground($color); $c->setFontColor('#000000'); $c->setFontWeight('bold'); $c->setAlignment('center');
                });
                $sheet->getStyle("A1:{$lastCol}1")->getAlignment()->setWrapText(true)->setVertical(\PHPExcel_Style_Alignment::VERTICAL_CENTER);
                $sheet->setHeight(1,40);
                $sheet->cells("A1:{$lastCol}9999", function($c){ $c->setFontFamily('Arial'); $c->setFontSize(10); });

                $fila++;

                foreach ($porAnio as $anio => $porPartida) {
                    $sheet->mergeCells("A{$fila}:{$lastCol}{$fila}");
                    $sheet->setCellValue("A{$fila}", "Año {$anio}");
                    $sheet->cells("A{$fila}:{$lastCol}{$fila}", function($c){
                        $c->setBackground('#CCCCCC'); $c->setFontWeight('bold'); $c->setFontSize(13); $c->setAlignment('center');
                    });
                    $sheet->setHeight($fila,20);
                    $fila++;

                    foreach ($porPartida as $partida => $rows) {
                        $sheet->mergeCells("A{$fila}:{$lastCol}{$fila}");
                        $sheet->setCellValue("A{$fila}", "Partida {$partida}");
                        $sheet->cells("A{$fila}:{$lastCol}{$fila}", function($c){
                            $c->setBackground('#FFFF99'); $c->setFontWeight('bold'); $c->setFontSize(12); $c->setAlignment('center');
                        });
                        $fila++;

                        foreach ($rows as $row) {
                            $sheet->row($fila, [
                                $row['mes'],
                                $row['cuota'],
                                $row['fpres'],
                                $row['monto'],
                                $row['obs'],
                            ]);
                            $sheet->cells("A{$fila}", function($c){ $c->setBackground('#FFFF99'); $c->setFontWeight('bold'); $c->setAlignment('left'); });
                            $sheet->cells("A{$fila}:{$lastCol}{$fila}", function($c){ $c->setAlignment('center'); });
                            $fila++;
                        }
                    }
                }

                $sheet->getStyle("A1:{$lastCol}1")->applyFromArray([
                    'borders'=>['allborders'=>['style'=>\PHPExcel_Style_Border::BORDER_THICK,'color'=>['argb'=>'FF000000']]]
                ]);
                $lastRow = $fila - 1;
                if ($lastRow >= 2) {
                    $sheet->getStyle("A2:{$lastCol}{$lastRow}")->applyFromArray([
                        'borders'=>['allborders'=>['style'=>\PHPExcel_Style_Border::BORDER_THIN,'color'=>['argb'=>'FF000000']]]
                    ]);
                }
                $sheet->setWidth('A',18); $sheet->setWidth('B',10); $sheet->setWidth('C',22); $sheet->setWidth('D',18); $sheet->setWidth('E',40);
                $sheet->setFreeze('A2');
            });
        }
    })->export('xlsx');
}


}
