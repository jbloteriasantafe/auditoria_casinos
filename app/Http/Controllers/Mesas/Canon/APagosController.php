<?php

namespace App\Http\Controllers\Mesas\Canon;

use Auth;
use Session;
use Illuminate\Http\Request;
use Response;
use App\Http\Controllers\Controller;
use Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Hash;

use App\Usuario;
use App\Casino;
use App\MesCasino;
use Carbon\Carbon;
use App\SecRecientes;
use App\Http\Controllers\RolesPermissions\RoleFinderController;

use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

use App\Mesas\Mesa;
use App\Mesas\Moneda;
use App\Mesas\ImagenesBunker;
use App\Mesas\DetalleImgBunker;
use App\Mesas\Canon;
use App\Mesas\ImportacionDiariaMesas;
use App\Mesas\InformeFinalMesas;
use App\Mesas\DetalleInformeFinalMesas;
use App\Mesas\ImportacionMensualMesas;
use App\Mesas\DetalleImportacionMensualMesas;

use App\Http\Controllers\Mesas\Importaciones\Mesas\MensualController;


class APagosController extends Controller
{
  private static $atributos = [
    'id_cierre_mesa' => 'Identificacion del Cierre',
    'fecha' => 'Fecha',
    'hora_inicio' => 'Hora de Apertura',
    'hora_fin' => 'Hora del Cierre',
    'total_pesos_fichas_c' => 'Total de pesos en Fichas',
    'total_anticipos_c' => 'Total de Anticipos',
    'id_fiscalizador'=>'Fiscalizador',
    'id_mesa_de_panio'=> 'Mesa de Paño',
    'id_estado_cierre'=>'Estado',
  ];

  /**
   * Create a new controller instance.
   *
   * @return void
   */
  public function __construct()
  {
    $this->middleware(['tiene_permiso:m_a_pagos']);
  }

  //crear,  pago->recibe cotizaciones, impuestos, fecha_pago, mes_pago y total_pago_pesos

  /* 
    id_detalle: $('#guardarPago').val(),
    id_casino: $('#selectCasinoPago').val(),
    anio_inicio: $('#fechaAnioInicio').val(),
    anio: mesSeleccionado.data('anio'),
    mes: mesSeleccionado.data('mes'),
    dia_inicio: mesSeleccionado.data('dia_inicio'),
    dia_fin: mesSeleccionado.data('dia_fin'),
    fecha_pago: $('#fechaPago').val(),
    cotizacion_euro: $('#cotEuroPago').val(),
    cotizacion_dolar: $('#cotDolarPago').val(),
    total_pago_pesos:$('#montoPago').val(),
    impuestos: $('#impuestosPago').val() == null? 0 : $('#impuestosPago').val(),
  
  */
  public function crear(Request $request){
    $validator=  Validator::make($request->all(),[
      //'id_detalle_informe_final_mesas' => 'nullable',
      'id_casino' => 'required|exists:casino,id_casino',
      'anio_inicio' => 'required|integer|min:1',
      'anio' => 'required|integer|gte:anio_inicio',
      'mes' => 'required|integer|min:1|max:12',
      'dia_inicio' => 'required|integer|min:1',
      'dia_fin' => 'required|integer|max:31',
      'fecha_pago' => 'required|date',
      'cotizacion_euro' => 'required|numeric',
      'cotizacion_dolar' => 'required|numeric',
      'total_pago_pesos' => 'required|numeric',
      'impuestos' => 'required|numeric'
    ], array(), self::$atributos)->after(function($validator){
      if($validator->errors()->any()) return;
      //validar que el canon este creado, validar que la cuota no esté paga.
      $validator = $this->validarFecha($validator);
      $validator = $this->validarCanonYCuota($validator->getData()['mes'],$validator);
        //validar que el total pago no sea menor que lo que deberia pagar
      $validator = $this->validarMontoPagado($validator->getData()['mes'],
      $validator->getData()['total_pago_pesos'],$validator);
      $validator = $this->verificarImportacionMensual($validator, $validator->getData()['mes']);
    })->validate();

    $mesCasino = MesCasino::find($request->mes);
    $casino = $mesCasino->casino;
    $ff = explode('-',$request->fecha_pago);

    $anioCuota = $request->anio_cuota;

    $canon = Canon::where('id_casino','=',$casino->id_casino)->first();
    $informe = $this->existeInformeFinalMesas($casino,$canon,
                                            $mesCasino,$request->fecha_pago);
    //inf anterior
    $informeAnterior = $this->informeanterior($informe,$casino,
                                            $request->mes,$request->fecha_pago);

    //
    $utilidad = 0;
    if($request->mes != 1 && $request->mes != 13){
      $importaciones = ImportacionMensualMesas::whereMonth('fecha_mes','=',$mesCasino->nro_mes)
                                                     ->whereYear('fecha_mes','=',$anioCuota)
                                                     ->where('id_casino','=',$casino->id_casino)
                                                     ->get();

      if(count($importaciones) == 0){
        $utilidad = $this->crearImportacionMensual($request->mes, $anioCuota, $casino);
      }else{

        foreach ($importaciones as $imp) {
          switch ($imp->id_moneda) {
            case 1://pesos
              $utilidad+= $imp->total_utilidad_mensual;
              break;
            case 2://dolares
              $utilidad+= $imp->conversion_total;
              break;
            default:
              $utilidad+= $imp->total_utilidad_mensual;
              break;
          }
        }
      }
    }else{
      $utilidad = $this->calcularProporcional($request->mes, $anioCuota, $casino);
    }

    $pago = new DetalleInformeFinalMesas;
    $pago->total_mes_anio_anterior = $informeAnterior['total_mes_actual'];//utilidad
    $pago->total_mes_actual = $utilidad;//utilidad
    $pago->cotizacion_euro_anterior = $informeAnterior['cotizacion_euro_actual'];
    $pago->cotizacion_dolar_actual = $request->cotizacion_dolar;
    $pago->cotizacion_euro_actual = $request->cotizacion_euro;
    $pago->cotizacion_dolar_anterior = $informeAnterior['cotizacion_dolar_actual'];
    $pago->informe_final_mesas()->associate($informe->id_informe_final_mesas);
    $pago->total_pagado =$request->total_pago_pesos;
    $pago->impuestos = $request->impuestos;
    $pago->fecha_cobro = $request->fecha_pago;
    $pago->casino()->associate($casino->id_casino);
    $pago->mes_casino()->associate($mesCasino->id_mes_casino);
    $pago->save();



    return ['importeCorrespondiente' => $canon->valor_base];
  }

  private function existeInformeFinalMesas($casino,$canon, $mesCasino, $fecha_pago){

    $informe = InformeFinalMesas::where('anio_inicio','=',$canon->periodo_anio_inicio)
                                  ->where('id_casino','=', $casino->id_casino)
                                  ->first();

    if($informe == null){
      $informe = new InformeFinalMesas;
      $informe->anio_final = $canon->periodo_anio_final;
      $informe->anio_inicio = $canon->periodo_anio_inicio;
      $informe->id_casino = $casino->id_casino;
      $informe->base_cobrado_dolar = $canon->valor_base_dolar;
      $informe->base_cobrado_euro = $canon->valor_base_euro;
      $informe->base_actual_euro = $canon->valor_real_euro;
      $informe->base_actual_dolar = $canon->valor_real_dolar;
      $informe->base_anterior_euro = 0;
      $informe->base_anterior_dolar = 0;
      $informe->save();
    }

    return $informe;
  }

  public function informeAnterior($informe,$casino, $nro_mes, $fecha_pago){
    $anio_inicio = explode('-',$casino->fecha_inicio);
    if($anio_inicio[0] == $informe->anio_inicio){
      return [
        'cotizacion_euro_actual' => 0,
        'cotizacion_dolar_actual' => 0,
        'total_mes_actual' => 0,
      ];
    }else{
      $informeAnterior = InformeFinalMesas::where('anio_final','=',$informe->anio_inicio)
                                        ->where('id_casino','=',$casino->id_casino)
                                        ->first();
      if($informeAnterior!=null){
        $detalle  = DetalleInformeFinalMesas::where('id_informe_final_mesas','=',$informeAnterior->id_informe_final_mesas)
                                              ->where('id_mes_casino','=',$nro_mes)
                                              ->first();
        return [
          'cotizacion_euro_actual' => $detalle->cotizacion_euro_actual,
          'cotizacion_dolar_actual' => $detalle->cotizacion_dolar_actual,
          'total_mes_actual' => $detalle->total_mes_actual,
        ];
    }else{
      return [
        'cotizacion_euro_actual' => 0,
        'cotizacion_dolar_actual' => 0,
        'total_mes_actual' =>0,
      ];
    }

    }
  }

  public function calcularProporcional($mes, $anioCuota, $casino){
    $dia = explode('-',$casino->fecha_inicio);
    if($mes == 1){
      $signo = '>=';
    }else{
      $signo = '<=';
    }
    $monedas = Moneda::all();
    $utilidad = 0;
    foreach ($monedas as $moneda) {
      $informesDiarios = ImportacionDiariaMesas::where('id_casino','=',$casino->id_casino)
                                        ->whereYear('fecha','=',$anioCuota)
                                        ->whereMonth('fecha','=',$mes)
                                        ->whereYear('fecha','=',$anioCuota)
                                        ->whereMonth('fecha','=',$mes)
                                        ->whereDay('fecha',$signo,$dia[2])
                                        ->where('id_moneda','=',$moneda->id_moneda)
                                        ->get();
      if($informesDiarios->count() > 0) {
        // $imp = new ImportacionMensualMesas;
        // $total_diario = 0 ;
        // $diferencias = 0;
        // $utilidad_diaria_calculada = 0;

        // $saldo_diario_fichas = 0;
        // $total_diario_retiros = 0;
        // $total_diario_reposiciones = 0;
        foreach ($imp->detalles as $datos_mesa) {
          // $total_diario+= $datos_mesa->droop;
          // $diferencias+= $datos_mesa->diferencia_cierre;
          // $utilidad_diaria_calculada+= $datos_mesa->utilidad_calculada;

            switch ($imp->id_moneda) {
              case 1://pesos
                $utilidad+= $datos_mesa->utilidad_diaria_total;
                break;
              case 2://dolares
                $utilidad+= $datos_mesa->conversion_total;
                break;
              default:
                $utilidad+= $imp->utilidad_diaria_total;
                break;
          }
          //$utilidad_diaria_total+= $datos_mesa->utilidad;
          // $saldo_diario_fichas+= $datos_mesa->saldo_fichas;
          // $total_diario_retiros+= $datos_mesa->retiros;
          // $total_diario_reposiciones+= $datos_mesa->reposiciones;
        }
        // $imp->total_mensual = $total_diario;
        // $imp->diferencias = $diferencias;
        // $imp->utilidad_calculada = $utilidad_diaria_calculada;
        // $imp->total_utilidad_mensual = $utilidad_diaria_total;
        // $imp->saldo_fichas_mes = $saldo_diario_fichas;
        // $imp->retiros_mes = $total_diario_retiros;
        // $imp->reposiciones_mes = $total_diario_reposiciones;
      }
    }
    return $utilidad;
  }

  public function modificar(Request $request){
    $validator=  Validator::make($request->all(),[
      'id_detalle' => 'required|exists:detalle_informe_final_mesas,id_detalle_informe_final_mesas',
      'cotizacion_dolar' => ['required','regex:/^\d\d?\d?([,|.]?\d?\d?\d?)?$/'],//aaaa/aaaa o aaaa-aaaa
      'cotizacion_euro' =>  ['required','regex:/^\d\d?\d?([,|.]?\d?\d?\d?)?$/'],
      'impuestos' => ['required','regex:/^\d\d?\d?\d?\d?\d?\d?\d?([,|.]?\d?\d?\d?)?$/'],
      'fecha_pago' => 'required|date',
      'mes' =>  ['required','exists:mes_casino,id_mes_casino'],
      'total_pago_pesos' =>  ['required','regex:/^\d\d?\d?\d?\d?\d?\d?\d?([,|.]?\d?\d?\d?)?$/'],
    ], array(), self::$atributos)->after(function($validator){
      if(!empty($validator->getData()['mes']) && !empty($validator->getData()['total_pago_pesos'])){
        $validator = $this->validarMontoPagado($validator->getData()['mes'],
        $validator->getData()['total_pago_pesos'],$validator);
      }
    })->validate();
    if(isset($validator)){
      if ($validator->fails()){
          return ['errors' => $validator->messages()->toJson()];
          }
    }
    $pago = DetalleInformeFinalMesas::find($request->id_detalle);
    $casino = $pago->casino;
    $ff = explode('-',$request->fecha_pago);
    if($ff[1] == '01'){
      $anioCuota = $ff[0]-1;
    }else{
      $anioCuota = $ff[0];
    }

    $informe = $pago->informe_final_mesas;
    //inf anterior
    $informeAnterior = $this->informeanterior($informe,$casino,
                                            $request->mes,$request->fecha_pago);
    //$canon = Canon::where('id_casino','=',$casino->id_casino)->first();
    $utilidad = 0;
    if($request->mes != 1 && $request->mes != 13){
      $importaciones = ImportacionMensualMesas::whereMonth('fecha_mes','=',$request->mes)
                                                     ->whereYear('fecha_mes','=',$anioCuota)
                                                     ->where('id_casino','=',$casino->id_casino)
                                                     ->get();
      if(count($importaciones) == 0){
        $utilidad = $this->crearImportacionMensual($request->mes, $anioCuota, $casino);
      }else{

        foreach ($importaciones as $imp) {
          switch ($imp->id_moneda) {
            case 1://pesos
              $utilidad+= $imp->total_utilidad_mensual;
              break;
            case 2://dolares
              $utilidad+= $imp->conversion_total;
              break;
            default:
              $utilidad+= $imp->total_utilidad_mensual;
              break;
          }
        }
      }
    }else{
      $utilidad = $this->calcularProporcional($request->mes, $anioCuota, $casino);
    }

    $pago->total_mes_anio_anterior = $informeAnterior['total_mes_actual'];//utilidad
    $pago->total_mes_actual = $utilidad;
    $pago->cotizacion_euro_anterior = $informeAnterior['cotizacion_euro_actual'];
    $pago->cotizacion_dolar_actual = $request->cotizacion_dolar;
    $pago->cotizacion_euro_actual = $request->cotizacion_euro;
    $pago->cotizacion_dolar_anterior = $informeAnterior['cotizacion_dolar_actual'];
    $pago->mes_detalle= $request->mes;//es el mes del pago
    //$pago->informe_final_mesas($informe)->associate();
    $pago->total_pagado =$request->total_pago_pesos;
    $pago->fecha_cobro = $request->fecha_pago;
    $pago->impuestos = $request->impuestos;
    //$pago->casino()->associate($casino->id);
    $pago->mes_casino()->associate($request->mes);
    $pago->save();

    return response()->json([], 200);
  }


  private function validarFecha($validator){
    $mesCuota = MesCasino::find($validator->getData()['mes']);
    $casino = $mesCuota->casino;
    if($mesCuota->nro_mes >= 1 && $mesCuota->nro_mes <=9){
      $nro_mes_c = '0'.$mesCuota->nro_mes;
    }else {
      $nro_mes_c = $mesCuota->nro_mes;
    }

    $anio_mes_hoy = date('Y-m');
    $anio_mes_fecha_pago =  Carbon::parse($validator->getData()['fecha_pago'])->format('Y-m');
    $anio_mes_cuota = $validator->getData()['anio_cuota'].'-'. $nro_mes_c;
    $anio_mes_creacion_cas = Carbon::parse($casino->fecha_inicio)->format('Y-m');
    //  dd($anio_mes_fecha_pago,$anio_mes_cuota);
    if($anio_mes_hoy < $anio_mes_fecha_pago || $anio_mes_hoy < $anio_mes_cuota){
      //el msja va para anio
      if( $validator->getData()['anio_cuota'] > date('Y')){
        $validator->errors()->add('anio_cuota','El año a pagar no debe superar al actual.'
                                 );
      }
      //dd($nro_mes_c);
      if($nro_mes_c > date('m')){
        $validator->errors()->add('mes','El mes a pagar no debe superar al actual.'
                                 );
      }

    }
    //dd($anio_mes_cuota , $anio_mes_creacion_cas);

    if($anio_mes_cuota < $anio_mes_creacion_cas){
      $validator->errors()->add('fecha_pago','La fecha de creación del casino es menor a la que desea pagar.'
                               );
    }
    if($anio_mes_fecha_pago < $anio_mes_cuota){
      $validator->errors()->add('fecha_pago','La fecha de pago es incorrecta.'
                               );
    }
    return $validator;
  }


  public function validarCanonYCuota($id_mes,$validator){
    $mes = MesCasino::find($id_mes);
    $casino = $mes->casino;
    $ff = date('m',strtotime($casino->fecha_inicio));
    //si el mes de pago es mayor o igual al mes de creacion del casino =>
    //el año inicio del canon es el actual, sino es el anterior
    if($ff >= $mes->nro_mes){
      $canon = Canon::where('periodo_anio_inicio','=',$validator->getData()['anio_cuota']-1)
                      ->where('id_casino','=',$mes->id_casino)
                      ->get()->first();
    }else{
      $canon = Canon::where('periodo_anio_inicio','=',$validator->getData()['anio_cuota'])
                      ->where('id_casino','=',$mes->id_casino)
                      ->get()->first();
    }
    if($canon == null){
      $validator->errors()->add('canon','No existen valores de canon para el período actual.'
                               );
    }else{

      $informe = InformeFinalMesas::where('id_casino','=',$canon->id_casino)
                                    ->where('anio_inicio','=',$canon->periodo_anio_inicio)
                                    ->first();
                              //      dd($canon,$informe,$id_mes);
      if($informe != null){
        $detalle = DetalleInformeFinalMesas::where('id_informe_final_mesas','=',$informe->id_informe_final_mesas)
                                            ->where('id_mes_casino','=',$id_mes)
                                            ->first();

        if(!empty($detalle) || $detalle != null){
          $validator->errors()->add('mes','Ya se cargó el pago anteriormente:'.$detalle->fecha_cobro
                                   );
        }
      }//else no se hizo ningun pago todavia

    }
    return $validator;
  }

  public function validarMontoPagado($id_mes,$total_pago_pesos,$validator){
    $mes = MesCasino::find($id_mes);
    $casino = $mes->casino;
    $ff = date('m',strtotime($casino->fecha_inicio));
    //si el mes de pago es mayor o igual al mes de creacion del casino =>
    //el año inicio del canon es el actual, sino es el anterior
    if($ff >= $mes->nro_mes){
      $canon = Canon::where('periodo_anio_inicio','=',$validator->getData()['anio_cuota']-1)
                      ->where('id_casino','=',$mes->id_casino)
                      ->get()->first();
    }else{
      $canon = Canon::where('periodo_anio_inicio','=',$validator->getData()['anio_cuota'])
                      ->where('id_casino','=',$mes->id_casino)
                      ->get()->first();
    }
    if($canon == null){
      $validator->errors()->add('canon','No existen valores de canon para el período actual.'
                               );
    }else{
      $dolares = ($canon->valor_base_dolar) * $validator->getData()['cotizacion_dolar'];
      $euros = ($canon->valor_base_euro) * $validator->getData()['cotizacion_euro'];
      $suma = $dolares + $euros;
      if($suma > $validator->getData()['total_pago_pesos']){
        $validator->errors()->add('total_pago_pesos','Monto insuficiente, mínimo:'.$suma
                                 );
      }
    }
    return $validator;
  }


  public function verificarImportacionMensual($validator, $mes){
    $mes = MesCasino::find($mes);
    $ff = explode('-',$validator->getData()['fecha_pago']);

    $anioCuota = $validator->getData()['anio_cuota'];
    $dia = explode('-',$mes->casino->fecha_inicio);
    if($mes->nro_cuota == 1){
      $signo = '>=';
      $impDiarias = ImportacionDiariaMesas::where('id_casino','=',$mes->id_casino)
                                            ->whereMonth('fecha','=',$mes->nro_mes)
                                            ->whereDay('fecha',$signo,$dia[2])
                                            ->whereYear('fecha','=',$anioCuota)
                                            ->where('id_moneda','=',1)
                                            ->whereNull('deleted_at')
                                            ->get();
    }elseif ($mes->nro_cuota == 13) {
      $signo = '<=';
      $impDiarias = ImportacionDiariaMesas::where('id_casino','=',$mes->id_casino)
                                            ->whereMonth('fecha','=',$mes->nro_mes)
                                            ->whereDay('fecha',$signo,$dia[2])
                                            ->whereYear('fecha','=',$anioCuota)
                                            ->where('id_moneda','=',1)
                                            ->whereNull('deleted_at')
                                            ->get();
    }else {
      //dd('ok');
      $impDiarias = ImportacionDiariaMesas::where('id_casino','=',$mes->id_casino)
                                            ->whereMonth('fecha','=',$mes->nro_mes)
                                            ->whereYear('fecha','=',$anioCuota)
                                            ->where('id_moneda','=',1)
                                            ->whereNull('deleted_at')
                                            ->get();
    }
    $fecha = Carbon::createFromDate($anioCuota,$mes->nro_mes,'1');
    $diasCuota = $fecha->daysInMonth;

    $mensuales = ImportacionMensualMesas::whereYear('fecha_mes','=',$anioCuota)
    ->whereMonth('fecha_mes','=',$mes->nro_mes)
    ->where('id_casino','=',$mes->id_casino)
    ->where('id_moneda','=',1)
    ->get();
    //dd($impDiarias->count(),$diasCuota,count($mensuales));
    if($impDiarias->count() != $diasCuota && count($mensuales) == 0){
      $validator->errors()->add('importaciones','No se han realizado las importaciones correspondientes.'
                               );
    }


    return $validator;
  }

  public function crearImportacionMensual($id_mes, $anioCuota, $casino){
    $monedas = Moneda::all();
    $mes = MesCasino::find($id_mes);
    $utilidad = 0;
    $controllerIM = new MensualController;
    foreach ($monedas as $moneda) {
      $impDiarias = ImportacionDiariaMesas::where('id_casino','=',$mes->id_casino)
                                    ->whereYear('fecha','=',$anioCuota)
                                    ->whereMonth('fecha','=',$mes->nro_mes)
                                    ->where('id_moneda','=',$moneda->id_moneda)
                                    ->get();
      if(count($impDiarias)>0) {
        $importacionMensual = new ImportacionMensualMesas;
        $importacionMensual->moneda()->associate($moneda->id_moneda);
        $importacionMensual->fecha_mes = $anioCuota.'-'.$mes->nro_mes.'-01';
        $importacionMensual->casino()->associate($casino->id_casino);
        $importacionMensual->nombre_csv = 'auto_desde_diarias';
        $importacionMensual->validado =1;
        $importacionMensual->save();


        foreach ($impDiarias as $impDiaria) {
          $fecha_dia = explode('-',$impDiaria->fecha);
          $detMensual = DetalleImportacionMensualMesas::create([
            'id_importacion_mensual_mesas'=> $importacionMensual->id_importacion_mensual_mesas,
            'fecha_dia'=> $fecha_dia[2],
            'total_diario'=> $impDiaria->total_diario,
            'utilidad'=> $impDiaria->utilidad_diaria_total,
            'cotizacion'=> $impDiaria->cotizacion,
            'retiros_dia'=> $impDiaria->total_diario_retiros,
            'reposiciones_dia'=> $impDiaria->total_diario_reposiciones,
            'utilidad_calculada_dia'=> $impDiaria->utilidad_diaria_calculada,
            'saldo_fichas_dia'=> $impDiaria->saldo_diario_fichas,
            'diferencias' => $impDiaria->diferencias
          ]);
        }
        $controllerIM->actualizarTotales($importacionMensual->id_importacion_mensual_mesas);
        switch ($moneda->id_moneda) {
          case 1://pesos
            $utilidad+= $importacionMensual->total_utilidad_mensual;
            break;
          case 2://dolares
            $utilidad+= $importacionMensual->conversion_total;
            break;
          default:
            $utilidad+= $importacionMensual->total_utilidad_mensual;
            break;
        }
      }
    }
    return $utilidad;
  }
}
