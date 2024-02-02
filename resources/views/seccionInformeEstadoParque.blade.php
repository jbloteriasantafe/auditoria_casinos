@extends('includes.dashboard')

@section('estilos')
@endsection

@section('headerLogo')
<span class="etiquetaLogoMaquinas">@svg('maquinas','iconoMaquinas')</span>
@endsection

@section('contenidoVista')
<div class="row">
  <div class="col-lg-12">
    <div class="panel panel-default" style="height:650px; padding-top:100px;">
      <div class="panel-heading" style="text-align:center;">
        <h4>¿QUÉ CASINO DESEA VER?</h4>
      </div>
      <form id="formDatosInforme" class="panel-body" style="text-align:center;">
          <img src="/img/logos/casinos_gris.png" alt="" width="250px" style="margin-bottom:40px; margin-top:20px;">
          <div class="row">
            <div class="col-md-4 col-md-offset-4">
              <h5>CASINO</h5>
              <select id="buscadorCasino" class="form-control" name="id_casino">
                <option value="0">- Seleccione el casino -</option>
                @foreach($casinos as $casino)
                <option value="{{$casino->id_casino}}">{{$casino->nombre}}</option>
                @endforeach
              </select>
            </div>
          </div>
          <div class="row">
            <div class="col-md-4 col-md-offset-4">
              <h5>FECHA INFORME</h5>
              @component('Components/inputFecha',[
                'attrs'  => "name=\"fecha_informe\"",
              ])
              @endcomponent
            </div>
          </div>
          <br>
          <div class="row">
            <div class="col-md-4 col-md-offset-4">
              <button id="btn-buscar" class="btn btn-infoBuscar" type="button" style="width:100%;">VER DETALLES</button>
            </div>
          </div>
          <br>
      </form>
    </div>
  </div> <!-- col-md-4 -->
</div>

<div class="modal fade" id="modalDetallesParque" data-backdrop="static" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
      <div class="modal-dialog" style="width:70%;">
         <div class="modal-content">
              <div class="modal-header" style="background:#304FFE;">
                  <button type="button" class="close" data-dismiss="modal"><i class="fa fa-times"></i></button>
                  <button id="btn-minimizar" type="button" class="close" data-toggle="collapse" data-minimizar="true" data-target="#colapsado" style="position:relative; right:20px; top:5px"><i class="fa fa-minus"></i></button>
                  <h3 class="modal-title" style="color: #fff; text-align:center">ESTADO DE PARQUE</h3>
              </div>
              <div id="colapsado" class="collapse in">
                <div class="modal-body">

                <style media="screen">
                    .logoCasino {
                        width: 50%;
                        margin: 30px 0px;
                    }

                    .numero_maquinas {
                        font-family: Roboto-Condensed;
                        font-size: 62px;
                    }
                    .maquinas_habilitadas {
                        display: block;
                        font-family: Roboto-Condensed;
                        font-size: 20px;
                    }
                    .imagen_maquina {
                      display: block;
                      margin-left: auto;
                      margin-right: auto;
                      position: relative;
                      top: -10px;
                    }
                </style>

                <div class="row">
                    <div class="col-md-8" style="border-right:1px solid #ccc;">
                        <div class="row" style="padding:0px 0px 20px 0px;">
                            <div class="col-md-12" style="text-align:center;">
                                <img id="logo_CME" data-id_casino="1" class="logoCasino" src="/img/logos/LOGO_CME_gris.png" alt="" hidden>
                                <img id="logo_CSF" data-id_casino="2" class="logoCasino" src="/img/logos/LOGO_CSF_gris.png" alt="" hidden>
                                <img id="logo_CRO" data-id_casino="3" class="logoCasino" src="/img/logos/LOGO_CRO_gris.png" alt="" hidden>
                            </div>
                        </div>
                        <div class="row" style="border-top:1px solid #ccc; padding:20px 0px;">
                            <div class="col-md-12">
                              <div class="row" style="text-align:center; padding-bottom:25px;">
                                  <h5>MÁQUINAS DEL CASINO</h5>
                                  <div class="col-md-6" style="position:relative;top:30px;text-align:center;align-items:center;">

                                    <span id="total_habilitadas" class="numero_maquinas" style="color:#00E676;">23</span>
                                    <img class="imagen_maquina" src="/img/logos/tragaperras_verde.png" alt="" width="60px">
                                    <span class="maquinas_habilitadas">Máquinas habilitadas</span>

                                    <span id="total_deshabilitadas" class="numero_maquinas" style="color:#FF1744;">6</span>
                                    <img class="imagen_maquina" src="/img/logos/tragaperras_rojo.png" alt="" width="60px">
                                    <span class="maquinas_habilitadas">Máquinas deshabilitadas</span>
                                  </div>
                                  <div class="col-md-6">
                                      <div id="tortaHabilitadas" style=""></div>
                                  </div>
                              </div>
                            </div>
                        </div>

                        <style media="screen">
                            .maquinas_noasignadas, .islas_noasignadas {
                              display: inline;
                              font-family: Roboto-Condensed;
                              font-size: 52px;
                            }
                            .imagen_maquina_noasignada {
                              display: inline;
                              position: relative; top:-15px; left:10px;
                            }
                            .imagen_islas_noasignada {
                              display: inline;
                              position: relative; top:-15px; left:10px;
                            }
                        </style>

                        <div class="row" style="text-align:center;border-top:1px solid #ccc; padding:20px 0px;">
                            <div class="col-md-6">
                              <h5>ISLAS NO ASIGNADAS</h5>
                              <span id="islas_asignadas" class="islas_noasignadas" style="color:#FF3D00;">1</span>
                              <img class="imagen_islas_noasignada" src="/img/logos/islas_anaranjada.png" alt="" width="80px">
                            </div>
                            <div class="col-md-6">
                                <h5>MÁQUINAS NO ASIGNADAS</h5>
                                <span id="maquinas_asignadas" class="maquinas_noasignadas" style="color:#FF3D00;">11</span>
                                <img class="imagen_maquina_noasignada" src="/img/logos/tragaperras_anaranjada.png" alt="" width="60px">
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4" style="padding:20px 0px; text-align:center;">
                        <h5>SECTORES DEL CASINO</h5>

                        <style media="screen">
                            .filaSector {
                                padding: 20px 0px;
                                border-bottom: 1px solid #ccc;
                            }

                            .nombreSector {
                              display: block;
                                font-family: Roboto-Condensed;
                                font-size: 30px;
                            }
                            .cantMaquinasSector {
                              display: inline-block;
                              font-family: Roboto-Light;
                              font-size: 30px;
                            }
                            .maquinaSector {
                                position: relative; top: -5px; left:10px;
                                display: inline-block;
                                width: 50px;
                            }
                        </style>
                        <div id="sectores">
                            <div id="filaModelo" class="filaSector" hidden>
                              <span class="nombreSector">ZONA 1</span>
                              <span class="cantMaquinasSector">100</span>
                              <img class="maquinaSector" src="/img/logos/tragaperras_blue.png" alt="">
                            </div>

                            <div class="filaSector">
                              <span class="nombreSector">ZONA 2</span>
                              <span class="cantMaquinasSector">243</span>
                              <img class="maquinaSector" src="/img/logos/tragaperras_blue.png" alt="">
                            </div>

                            <div class="filaSector">
                              <span class="nombreSector">ZONA 3</span>
                              <span class="cantMaquinasSector">45</span>
                              <img class="maquinaSector" src="/img/logos/tragaperras_blue.png" alt="">
                            </div>
                        </div>

                    </div>
                </div>

              </div>
              <div class="modal-footer">

              </div>
        </div>
      </div>
    </div>
</div>

<meta name="_token" content="{!! csrf_token() !!}" />
@endsection

<!-- Comienza modal de ayuda -->
@section('tituloDeAyuda')
<h3 class="modal-title" style="color: #fff;">| INFORMES DE CASINO</h3>
@endsection
@section('contenidoAyuda')
<div class="col-md-12">
  <h5>Tarjeta de Informes</h5>
  <p>
    Se detalla gráficamente el estado del parque de máquinas, dependiendo el casino elegido por el usuario.
    Se describen la cantidad de máquinas del casino, respecto de los sectores en los que se dividen,
    donde se informan las máquinas habilitadas y deshabilitadas.
  </p>
</div>
@endsection
<!-- Termina modal de ayuda -->

@section('scripts')
<!-- JavaScript personalizado -->
<script src="js/seccionInformeEstadoParque.js?2" charset="utf-8" type="module"></script>
<!-- Highchart -->
<script src="js/highcharts.js"></script>
<script src="js/highcharts-3d.js"></script>
@endsection
