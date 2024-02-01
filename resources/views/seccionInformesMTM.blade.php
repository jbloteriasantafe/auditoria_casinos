@extends('includes.dashboard')
@section('headerLogo')
<span class="etiquetaLogoInformes">@svg('informes','iconoInformes')</span>
@endsection
@section('contenidoVista')
@section('estilos')
<link rel="stylesheet" href="/css/bootstrap-datetimepicker.min.css">
<link href="css/fileinput.css" media="all" rel="stylesheet" type="text/css"/>
<link href="themes/explorer/theme.css" media="all" rel="stylesheet" type="text/css"/>
<link rel="stylesheet" href="css/zona-file-large.css">
<style>
.width_columna {
  float: left;
  width: {{100.0/count($beneficios_x_casino)}}%;
}
</style>
@endsection
<?php
function moneda($id_tipo_moneda){
  if($id_tipo_moneda == 1) return '$';
  if($id_tipo_moneda == 2) return 'U$S';
  return ''.$id_tipo_moneda;
}
function mes($mes_num){
  $meses = ['Enero','Febrero','Marzo','Abril','Mayo','Junio','Julio','Agosto','Septiembre','Octubre','Noviembre','Diciembre'];
  if(!array_key_exists($mes_num-1,$meses)) return ''.$mes_num;
  return $meses[$mes_num-1];
}
function anio_mes($anio,$mes){
  return $anio.' '.mes($mes);
}
function img_casino($id_casino){
  switch($id_casino){
    case 1:
      return '<img width="100%" src="/img/tarjetas/banner_MEL.jpg">';
    case 2:
      return '<img width="100%" src="/img/tarjetas/banner_CSF.jpg">';
    case 3:
      return '<img width="100%" src="/img/tarjetas/banner_ROS.jpg">';
  }
  return ''.$id_casino;
}
function nombre_casino($id_casino){
  $c = App\Casino::find($id_casino);
  return is_null($c)? ''.$id_casino : $c->nombre;
}
?>
<div class="row">
  <div class="panel panel-default">
    <div class="panel-heading collaped" data-toggle="collapse" href="#collapseFiltros" style="cursor: pointer" aria-expanded="true">
      <h4>FILTROS<i class="fa fa-fw fa-angle-down"></i></h4>
    </div>
    <div id="collapseFiltros" class="panel-collapse collapse" aria-expanded="true">
      <div class="panel-body">
        <form id="formParametrosInformes" class="row">
          <div class="col-md-4" style="text-align: center;">
            <h5>MÁQUINAS</h5>
            <input id="maquinas" name="maquinas" class="form-control" type="text" placeholder="XX-YY,ZZ" style="text-align: center;">
          </div>
          <div class="col-md-3" style="text-align: center;">
            <h5>ISLAS</h5>
            <input id="islas" name="islas" class="form-control" type="text" placeholder="XX-YY,ZZ" style="text-align: center;">
          </div>
        </form>
      </div>
    </div>
  </div>
</div>
<div class="row">
  <style>
    .tablaBeneficiosPorCasino th,
    .tablaBeneficiosPorCasino td {
      font-size: 0.90em !important;
    }
    .tablaBeneficiosPorCasino input {
      width: 1.5em !important;
      padding: 0;
      margin: 0;
      text-align: center;
    }
  </style>
  @foreach($beneficios_x_casino as $id_casino => $beneficios)
  <div class="width_columna">
    <div class="row">
      <div class="col-lg-12">
        <div class="panel">
          <center>
            {!! img_casino($id_casino) !!}
          </center>
        </div>
      </div>
    </div>
    <div class="row">
      <div class="col-lg-12 col-xl-12">
        <div class="panel panel-default">
          <div class="panel-heading">
            <h4>Beneficios - MTM {{nombre_casino($id_casino)}}</h4>
          </div>
          <div class="panel-body">
            <table class="table table-fixed tablesorter tablaBeneficiosPorCasino">
              <thead>
                <tr>
                  <th class="col-xs-6" style="text-align: center;">FECHA</th>
                  <th class="col-xs-3" style="text-align: center;">MONEDA</th>
                  <th class="col-xs-3" style="text-align: center;">ACCIÓN</th>
                </tr>
              </thead>
              <tbody style="height: 356px;">
                @foreach($beneficios as $b)
                <tr>
                  <td class="col-xs-6">
                    {{anio_mes($b->anio,$b->mes)}}
                    <?php 
                      $ultimo_dia_mes = (new DateTime("{$b->anio}-{$b->mes}-01"))->modify('last day of this month');
                      $ultimo_dia_mes = $ultimo_dia_mes->format('d');
                    ?>
                    <span contenteditable data-js-dia-max-value="{{$ultimo_dia_mes}}" style="border: 1px solid #ccc;background-color: #eee;">1</span>
                    -
                    <span contenteditable data-js-dia-max-value="{{$ultimo_dia_mes}}" style="border: 1px solid #ccc;background-color: #eee;">{{$ultimo_dia_mes}}</span>
                  </td>
                  <td class="col-xs-3" style="text-align: center;">{{moneda($b->id_tipo_moneda)}}</td>
                  <td class="col-xs-3" style="text-align: center;">
                    @if($b->estado == 1)
                    <button data-anio="{{$b->anio}}" data-mes="{{$b->mes}}" data-casino="{{$b->id_casino}}" data-moneda="{{$b->id_tipo_moneda}}" data-pdev="0" class="btn btn-info planilla detalle" type="button">
                        <i class="fa fa-fw fa-print"></i>
                    </button>
                    <button data-anio="{{$b->anio}}" data-mes="{{$b->mes}}" data-casino="{{$b->id_casino}}" data-moneda="{{$b->id_tipo_moneda}}" data-pdev="1" class="btn btn-info planilla detalle" type="button">
                      <i class="fa fa-fw fa-search-plus"></i>
                    </button>
                    @endif
                    @if($b->estado == 0)
                    <a data-toggle="popover" data-trigger="hover" data-content="Beneficio no importado">
                      <i class="fa fa-exclamation" style="color: #FFA726;"></i>
                    </a>
                    @elseif($b->tiene_beneficio_mensual)
                    <a data-toggle="popover" data-trigger="hover" data-content="VALIDADO">
                      <i class="fa fa-check" style="color: green;"></i>
                    </a>
                    @endif
                  </td>
                </tr>
                @endforeach
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>
  </div> 
  @endforeach
</div>
    <meta name="_token" content="{!! csrf_token() !!}" />

    @endsection

    <!-- Comienza modal de ayuda -->
    @section('tituloDeAyuda')
    <h3 class="modal-title" style="color: #fff;">| INFORMES DE TRAGAMONEDAS</h3>
    @endsection
    @section('contenidoAyuda')
    <div class="col-md-12">
      <h5>Tarjeta de Informes de Tragamonedas</h5>
      <p>
        Se presenta un informe final acerca del desempeño mensual de cada casino, teniendo en cuenta puntos como el detalle por día de la cantidad
        de máquinas presentes en cada casino, lo apostado, premios, cantidad de premios totales, el beneficio, su promedio y el % de devolución.
      </p>
    </div>
    @endsection
    <!-- Termina modal de ayuda -->

    @section('scripts')
    <!-- JavaScript personalizado -->
    <script src="js/seccionMTMbeneficios.js?5"  type="module" charset="utf-8"></script>
    <!-- Custom input Bootstrap -->
    <script src="js/fileinput.min.js" type="text/javascript"></script>
    <script src="js/locales/es.js" type="text/javascript"></script>
    <script src="/themes/explorer/theme.js" type="text/javascript"></script>
    @endsection
