@extends('includes.dashboard')

  @section('headerLogo')
  <span class="etiquetaLogoExpedientes">@svg('expedientes','iconoExpedientes')</span>
  <meta name="csrf-token" content="{{ csrf_token() }}">
  @endsection
  @section('contenidoVista')

  @section('estilos')
  <link href="css/bootstrap-datetimepicker.min.css" rel="stylesheet"/>
  <link rel="stylesheet" href="css/bootstrap-datetimepicker.css">
  <link href="css/fileinput.css" media="all" rel="stylesheet" type="text/css"/>
  <link href="themes/explorer/theme.css" media="all" rel="stylesheet" type="text/css"/>
  <link rel="stylesheet" href="css/paginacion.css"/>
  <link rel="stylesheet" href="css/lista-datos.css">

  <style>
    #mensajeExito {
      animation: salida 1.5s forwards;
    }
    #mensajeError {
      animation: salida 2s forwards;
    }
    .tabs {
      --fondo: white;
      --gradiente: rgb(235,235,235);
      --gradiente-fondo-inicio: rgba(180,180,180,1);
      --gradiente-fondo-fin: rgba(180,180,180,0);
      --borde-tab: rgb(221, 221, 221);
      --borde-tab-seleccionado: orange;
      --texto-tab-seleccionado: #555;
      width: 100%;
      display: flex;
      overflow-x: auto;

      margin-bottom: 10px;
      background: linear-gradient(0deg, var(--gradiente-fondo-inicio) 0%, var(--gradiente-fondo-fin) 100%);
    }

    .tabs > div {
      flex: 1;
      margin: 0;
      padding: 0;
    }
    .tabs a {
      padding: 15px 10px;
      font-family:Roboto-condensed;
      font-size:20px;
      background: white;
      display: inline-block;
      width: 100%;
      height: 100%;
      text-align: center;
      text-decoration: none;
      cursor: pointer;
      border: 1px solid var(--borde-tab);
      border-top-left-radius: 2em;
      border-top-right-radius: 2em;
    }
    .tabs a.active {
      color: var(--texto-tab-seleccionado);
      cursor: default;
      border-color: var(--borde-tab-seleccionado);
      border-bottom: none;
    }
    .tabs a:not(.active):not(:hover) {
      background-image:  linear-gradient(135deg, var(--gradiente) 25%, transparent 25%), linear-gradient(225deg, var(--gradiente) 25%, transparent 25%), linear-gradient(45deg, var(--gradiente) 25%, transparent 25%), linear-gradient(315deg, var(--gradiente) 25%, #ffffff 25%);
      background-position:  3px 0, 3px 0, 0 0, 0 0;
      background-size: 3px 3px;
      background-repeat: repeat;
      background-color: var(--fondo);
      .switch {
        position: relative; display: inline-block; width: 56px; height: 28px;
      }
      .switch input { display:none; }
      .slider {
        position: absolute; cursor: pointer; top: 0; left: 0; right: 0; bottom: 0;
        background-color: #ddd; transition: .2s; border-radius: 9999px;
      }
      .slider:before {
        position: absolute; content: ""; height: 22px; width: 22px; left: 3px; bottom: 3px;
        background-color: #fff; transition: .2s; border-radius: 50%;
        box-shadow: 0 1px 2px rgba(0,0,0,.2);
      }
      input:checked + .slider { background-color: #28a745; }   /* verde */
      input:checked + .slider:before { transform: translateX(28px); }
      .switch-text {
        margin-left: 8px; font-weight: 600; vertical-align: middle;
      }
    }
    .th-sort { background:none;border:0;padding:0;margin:0;cursor:pointer;font-weight:600; }
  .th-sort .fa { opacity:.5; margin-left:4px; }
  .th-sort.active .fa { opacity:1; }
  .th-sort.asc  .fa:before { content:"\f0de"; } /* fa-sort-asc */
  .th-sort.desc .fa:before { content:"\f0dd"; } /* fa-sort-desc */
  </style>
  <style>
/* ===== Switch Denuncia Alea (copypaste ready) ===== */
.switch-alea{
  position: relative;
  display: inline-block;
  width: 64px;
  height: 30px;
  vertical-align: middle;
}
.switch-alea input{ display:none; }

/* Track */
.slider-alea{
  position: absolute;
  inset: 0;
  cursor: pointer;
  background-color: #e5e7eb;
  border-radius: 9999px;
  transition: .25s;
  border: 1px solid rgba(0,0,0,.08);
}

/* Knob */
.slider-alea:before{
  content: "";
  position: absolute;
  height: 24px; width: 24px;
  left: 3px; top: 50%;
  transform: translateY(-50%);
  background: #fff;
  border-radius: 9999px;
  transition: .25s;
  box-shadow: 0 1px 2px rgba(0,0,0,.2);
}

/* Textos internos */
.slider-alea .slider-text{
  position: absolute;
  top: 50%;
  transform: translateY(-50%);
  font-size: 12px;
  font-weight: 700;
  user-select: none;
  pointer-events: none;
  transition: opacity .2s;
}

/* "No" a 10px del borde izquierdo */
.slider-alea .off{
  right: 10px;
  color: #374151;
  opacity: 1;
}

/* "Sí" corrido 10px hacia la izquierda respecto del borde derecho (right:20px) */
.slider-alea .on{
  left: 10px;
  color: #fff;
  opacity: 0;
}

/* Estado ON */
.switch-alea input:checked + .slider-alea{
  background-color: #28a745;
}
.switch-alea input:checked + .slider-alea:before{
  transform: translate(34px, -50%);
}
.switch-alea input:checked + .slider-alea .off{ opacity: 0; }
.switch-alea input:checked + .slider-alea .on{  opacity: 1; }

.switch-alea--table { transform: scale(.9); transform-origin: left center; }
.td-saving { opacity:.6; pointer-events:none; }

.th-sort-od{
  background: none;
  border: 0;
  padding: 0;
  margin: 0;
  cursor: pointer;
  font-weight: 600;
}
.th-sort-od:focus{ outline: none; }
.th-sort-od .fa { opacity: .5; margin-left: 4px; }
.th-sort-od.active .fa { opacity: 1; }
.th-sort-od.asc  .fa:before { content: "\f0de"; } /* fa-sort-asc */
.th-sort-od.desc .fa:before { content: "\f0dd"; } /* fa-sort-desc */


</style>

  @endsection

  <div class="row">
    <div class="tabs" data-js-tabs="">
      <div>
        <a data-js-tab="#pant_denunciasAlea_paginas">Paginas denunciadas a Alea</a>
      </div>
      <div>
        <a data-js-tab="#pant_denunciasAlea_obsYDen">Calculo de observaciones y denuncias</a>
      </div>
      <div>
        <a data-js-tab="#pant_pagActivas">Páginas activas</a>
      </div>
      <div>
        <a data-js-tab="#pant_estadisticas">Estadisticas</a>
      </div>

    </div>
  </div>



  <div id="pant_denunciasAlea_paginas" hidden>
    <div class="row">
      <div class="col-md-12">
        <!-- FILTROS -->
        <div class="panel panel-default">

          <div class="panel-heading" data-toggle="collapse" href="#collapseFiltrosdenunciasAlea_paginas" style="cursor:pointer">
  <h4>Filtros de búsqueda <i class="fa fa-fw fa-angle-down"></i></h4>
</div>
<div id="collapseFiltrosdenunciasAlea_paginas" class="panel-collapse collapse">
  <div class="panel-body">
    <div class="row">
      <div class="col-lg-3">
        <h5>Mes desde</h5>
        <div class="input-group date" id="fecha_denunciasAlea_paginasDesdeDia" data-date-format="yyyy-mm" data-link-format="yyyy-mm">
          <input type="text" class="form-control" id="FDesdeDia" placeholder="yyyy-mm-dd"/>
          <span class="input-group-addon" style="border-left:none;cursor:pointer;"><i class="fa fa-times"></i></span>
          <span class="input-group-addon" style="cursor:pointer;"><i class="fa fa-calendar"></i></span>
        </div>
      </div>

  <div class="col-lg-3">
    <h5>Fecha hasta</h5>
    <div class="input-group date" id="fecha_denunciasAlea_paginasHastaDia" data-date-format="yyyy-mm-dd" data-link-format="yyyy-mm-dd">
      <input type="text" class="form-control" id="FHastaDia" name="FHastaDia" placeholder="yyyy-mm-dd"/>
      <span class="input-group-addon" style="border-left:none;cursor:pointer;"><i class="fa fa-times"></i></span>
      <span class="input-group-addon" style="cursor:pointer;"><i class="fa fa-calendar"></i></span>
    </div>
  </div>
</br>
</div>
    <div class="row">
      <div class="col-lg-3">
        <h5>Usuario de página</h5>
        <input id="FUserPag" type="text" class="form-control" placeholder="contiene…">
      </div>
      <div class="col-lg-3">
        <h5>Plataforma</h5>
        <select id="FPlataforma" class="form-control">
          <option value="">(todas)</option>
          @foreach($plataformas as $p)
            <option value="{{ $p->id_denunciasAlea_plataforma ?? $p->id ?? $p->pk ?? '' }}">
              {{ $p->plataforma }}
            </option>
          @endforeach
        </select>
      </div>
      <div class="col-lg-3">
        <h5>Link (contiene)</h5>
        <input id="FLink" type="text" class="form-control" placeholder="contiene…">
      </div>
      <div class="col-lg-3">
        <h5>Denuncia Alea</h5>
        <select id="FDenunciada" class="form-control">
          <option value="">(todas)</option>
          <option value="1">Sí</option>
          <option value="0">No</option>
        </select>
      </div>
    </div>

    <br/>

    <div class="row">
      <div class="col-lg-3">
        <h5>Cant. denuncias (mín)</h5>
        <input id="FCantMin" type="number" min="0" class="form-control" placeholder="0">
      </div>
      <div class="col-lg-3">
        <h5>Cant. denuncias (máx)</h5>
        <input id="FCantMax" type="number" min="0" class="form-control" placeholder="9999">
      </div>
      <div class="col-lg-3">
        <h5>Estado</h5>
        <select id="FEstado" class="form-control">
          <option value="">(todos)</option>
          @foreach($estados as $e)
            <option value="{{ $e->id_denunciasAlea_estado ?? $e->id ?? $e->pk ?? '' }}">
              {{ $e->estado }}
            </option>
          @endforeach
        </select>
      </div>
      <div class="col-lg-3">
        <h5>Denunciado en</h5>
        <select id="FLugar" class="form-control">
          <option value="">(todos)</option>
          @foreach($lugares as $l)
            <option value="{{ $l->id_denunciasAlea_denunciadoEn ?? $l->id ?? $l->pk ?? '' }}">
              {{ $l->lugar }}
            </option>
          @endforeach
        </select>
      </div>
    </div>

    <br/>
    <div class="row">
      <div class="col-md-12 text-center">
        <button id="btn-buscardenunciasAlea_paginas" class="btn btn-infoBuscar">
          <i class="fa fa-search"></i> BUSCAR
        </button>
        <button id="btn-limpiardenunciasAlea_paginas" class="btn btn-default">
          <i class="fa fa-eraser"></i> LIMPIAR
        </button>
      </div>
    </div>
  </div>
</div>

        </div> <!-- .col-md-12 -->
      </div> <!-- .row / FILTROS -->
    </div>
    <div class="row">
      <div class="col-md-12">
        <div class="panel panel-default">
          <div class="panel-heading">
            <button class="btn" type="button" id="denunciasAlea_paginas_nuevo">
              <i class="fa fa-plus"></i> Agregar
            </button>
            <button id="btn-descargardenunciasAlea_paginasExcel" class="btn btn-infoBuscar">
              <i class="fa fa-download"></i> Exportar a Excel
            </button>
            <button id="btn-descargardenunciasAlea_paginasPDF" class="btn btn-infoBuscar">
              <i class="fa fa-download"></i> Exportar a PDF
            </button>
            <br/>
          </div>
          <div class="panel-body">

            <div style="max-height:356px; overflow:auto;">
  <table id="tablaResultadosdenunciasAlea_paginas"
         class="table table-striped table-hover"
         style="table-layout:fixed; width:100%;">
    <colgroup>
      <col style="width:4.3333%">
      <col style="width:6.3333%">
      <col style="width:7.3333%">
      <col style="width:19.6667%">
      <col style="width:9.3333%">
      <col style="width:19.6667%">
      <col style="width:7%">
      <col style="width:8%">
      <col style="width:9.9999%">
      <col style="width:7.3333%">
    </colgroup>

    <thead>
      <tr>
        <th style="width:36px" class="text-center">
          <input type="checkbox" id="checkAllDenuncias">
        </th>
        <th style="width:90px" class="text-center">Acciones</th>

        <th><button type="button" class="th-sort" data-sort="fecha">Fecha <i class="fa fa-sort"></i></button></th>
        <th><button type="button" class="th-sort" data-sort="user_pag">Usuario de página <i class="fa fa-sort"></i></button></th>
        <th><button type="button" class="th-sort" data-sort="plataforma">Red/Plataforma <i class="fa fa-sort"></i></button></th>
        <th><button type="button" class="th-sort" data-sort="link_pagina">Link de la página <i class="fa fa-sort"></i></button></th>
        <th><button type="button" class="th-sort" data-sort="denunciada">Denuncia Alea <i class="fa fa-sort"></i></button></th>
        <th><button type="button" class="th-sort" data-sort="cant_denuncias">Cant. Denuncias <i class="fa fa-sort"></i></button></th>
        <th><button type="button" class="th-sort" data-sort="estado_denuncia">Estado <i class="fa fa-sort"></i></button></th>
        <th><button type="button" class="th-sort" data-sort="denunciado_en">Denunciado en <i class="fa fa-sort"></i></button></th>
      </tr>
    </thead>

    <tbody id="cuerpoTabladenunciasAlea_paginas"></tbody>
  </table>
</div>

<div id="herramientasPaginaciondenunciasAlea_paginas" class="row zonaPaginacion"></div>
          </div>

        </div>
      </div>
    </div>

  </div>

  <div id="pant_denunciasAlea_obsYDen" hidden>
  <div class="row">
    <div class="col-md-12">
      <div class="panel panel-default">
        <div class="panel-heading" data-toggle="collapse" href="#collapseFiltrosdenunciasAlea_obsYDen" style="cursor: pointer">
          <h4>Filtros de búsqueda <i class="fa fa-fw fa-angle-down"></i></h4>
        </div>
        <div id="collapseFiltrosdenunciasAlea_obsYDen" class="panel-collapse collapse">
          <div class="panel-body">
            <div class="row">

              <div class="col-lg-3">
                <h5>Mes desde</h5>
                <div class="input-group date" id="fecha_obsYDenDesde" data-date-format="yyyy-mm" data-link-format="yyyy-mm">
                  <input type="text" class="form-control" id="inp_obsYDenDesde" placeholder="yyyy-mm"/>
                  <span class="input-group-addon" style="border-left:none;cursor:pointer;"><i class="fa fa-times"></i></span>
                  <span class="input-group-addon" style="cursor:pointer;"><i class="fa fa-calendar"></i></span>
                </div>
              </div>
              <div class="col-lg-3">
                <h5>Mes hasta</h5>
                <div class="input-group date" id="fecha_obsYDenHasta" data-date-format="yyyy-mm" data-link-format="yyyy-mm">
                  <input type="text" class="form-control" id="inp_obsYDenHasta" placeholder="yyyy-mm"/>
                  <span class="input-group-addon" style="border-left:none;cursor:pointer;"><i class="fa fa-times"></i></span>
                  <span class="input-group-addon" style="cursor:pointer;"><i class="fa fa-calendar"></i></span>
                </div>
              </div>
            </div>
<div class="row">
  <div class="col-lg-3">
    <h5>Total Cuentas Identificadas (mín/máx)</h5>
    <div class="input-group">
      <input id="FTotalIdentMin" type="number" min="0" step="1" class="form-control" placeholder="mín">
      <span class="input-group-addon">/</span>
      <input id="FTotalIdentMax" type="number" min="0" step="1" class="form-control" placeholder="máx">
    </div>
  </div>

  <div class="col-lg-3">
    <h5>Denuncias Realizadas Alea (mín/máx)</h5>
    <div class="input-group">
      <input id="FRealizadasMin" type="number" min="0" step="1" class="form-control" placeholder="mín">
      <span class="input-group-addon">/</span>
      <input id="FRealizadasMax" type="number" min="0" step="1" class="form-control" placeholder="máx">
    </div>
  </div>

  <div class="col-lg-3">
    <h5>Denuncias No realizadas (mín/máx)</h5>
    <div class="input-group">
      <input id="FNoRealizadasMin" type="number" min="0" step="1" class="form-control" placeholder="mín">
      <span class="input-group-addon">/</span>
      <input id="FNoRealizadasMax" type="number" min="0" step="1" class="form-control" placeholder="máx">
    </div>
  </div>

  <div class="col-lg-3">
    <h5>Páginas Activas (mín/máx)</h5>
    <div class="input-group">
      <input id="FActivasMin" type="number" min="0" step="1" class="form-control" placeholder="mín">
      <span class="input-group-addon">/</span>
      <input id="FActivasMax" type="number" min="0" step="1" class="form-control" placeholder="máx">
    </div>
  </div>
</div>
<div class="row">
  <div class="col-lg-3">
    <h5>Páginas Bajas (mín/máx)</h5>
    <div class="input-group">
      <input id="FBajasMin" type="number" min="0" step="1" class="form-control" placeholder="mín">
      <span class="input-group-addon">/</span>
      <input id="FBajasMax" type="number" min="0" step="1" class="form-control" placeholder="máx">
    </div>
  </div>

  <div class="col-lg-3">
    <h5>Denuncias Santa Fe (mín/máx)</h5>
    <div class="input-group">
      <input id="FDenSFMin" type="number" min="0" step="1" class="form-control" placeholder="mín">
      <span class="input-group-addon">/</span>
      <input id="FDenSFMax" type="number" min="0" step="1" class="form-control" placeholder="máx">
    </div>
  </div>

  <div class="col-lg-3">
    <h5>Denuncias Rosario (mín/máx)</h5>
    <div class="input-group">
      <input id="FDenRosMin" type="number" min="0" step="1" class="form-control" placeholder="mín">
      <span class="input-group-addon">/</span>
      <input id="FDenRosMax" type="number" min="0" step="1" class="form-control" placeholder="máx">
    </div>
  </div>
</div>
  <div class="row">
              <div class="col-lg-3">
                <h5>&nbsp;</h5>
                <div class="text-center">
                  <button id="btn-buscarObsYDen" class="btn btn-infoBuscar"><i class="fa fa-search"></i> BUSCAR</button>
                  <button id="btn-limpiarObsYDen" class="btn btn-default"><i class="fa fa-eraser"></i> LIMPIAR</button>
                </div>
              </div>
            </div>
          </div>
            <!-- Si querés replicar TODOS los filtros de la pestaña 1, clonalos acá con sufijo OD y agregalos al JS -->
          </div>
        </div>
      </div>
    </div>

  <!-- Tabla -->
  <div class="row">
    <div class="col-md-12">
      <div class="panel panel-default">
        <div class="panel-heading">
  <button id="btn-descargarObsYDenExcel" class="btn btn-infoBuscar">
    <i class="fa fa-download"></i> Exportar a Excel
  </button>
  <button id="btn-descargarObsYDenPDF" class="btn btn-infoBuscar">
    <i class="fa fa-download"></i> Exportar a PDF
  </button>
</div>

        <div class="panel-body">
          <div style="max-height:356px; overflow:auto;">
            <table id="tablaObsYDen" class="table table-striped table-hover" style="table-layout:fixed; width:100%;">
              <colgroup>
                <col style="width:4%">
                <col style="width:6%">
                <col style="width:6%">
                <col style="width:12%">
                <col style="width:12%">
                <col style="width:14%">
                <col style="width:12%">
                <col style="width:12%">
                <col style="width:12%">
              </colgroup>
              <thead>
                <tr>
                  <th class="text-center"><input type="checkbox" id="checkAllObsYDen"></th>
                  <th><button type="button" class="th-sort-od" data-sort="anio">Año <i class="fa fa-sort"></i></button></th>
                  <th><button type="button" class="th-sort-od" data-sort="mes">Mes <i class="fa fa-sort"></i></button></th>
                  <th><button type="button" class="th-sort-od" data-sort="total_identificadas">Total Cuentas Identificadas <i class="fa fa-sort"></i></button></th>
                  <th><button type="button" class="th-sort-od" data-sort="realizadas">Denuncias Realizadas Alea <i class="fa fa-sort"></i></button></th>
                  <th><button type="button" class="th-sort-od" data-sort="no_realizadas">Denuncias No realizadas a Alea <i class="fa fa-sort"></i></button></th>
                  <th><button type="button" class="th-sort-od" data-sort="activas">Cant Páginas Continúan Activas <i class="fa fa-sort"></i></button></th>
                  <th><button type="button" class="th-sort-od" data-sort="bajas">Cant Páginas Bajas <i class="fa fa-sort"></i></button></th>
                  <th><button type="button" class="th-sort-od" data-sort="den_sf">Total Denuncias SantaFe <i class="fa fa-sort"></i></button></th>
                  <th><button type="button" class="th-sort-od" data-sort="den_ros">Total Denuncias Rosario <i class="fa fa-sort"></i></button></th>
                </tr>
              </thead>
              <tbody id="cuerpoTablaObsYDen"></tbody>
            </table>
          </div>

          <div id="herramientasPaginacionObsYDen" class="row zonaPaginacion"></div>
        </div>
      </div>
    </div>
  </div>
</div>


<div id="pant_pagActivas" hidden>
  <div class="row">
    <div class="col-md-12">
      <div class="panel panel-default">
        <div class="panel-heading">
          <div class="form-inline">
             <div class="form-group">
                <label for="importarPaginasActivasInput" class="btn btn-default" style="margin-bottom: 0;">
                    <i class="fa fa-folder-open"></i> Seleccionar Archivo
                </label>
                <input type="file" id="importarPaginasActivasInput" accept=".csv" style="display:none;">
                <span id="nombreArchivoSel" style="font-style:italic; margin-left: 5px; color:#555;">Ningún archivo seleccionado</span>
             </div>
             <button id="btn-importar-pagActivas" class="btn btn-infoBuscar" style="margin-left: 10px;">
               <i class="fa fa-upload"></i> Importar CSV
             </button>
             
             <button id="btn-baja-inactivas" class="btn btn-danger" style="margin-left: 20px;" disabled>
               <i class="fa fa-trash"></i> Dar de baja a paginas inactivas
             </button>

             <button id="btn-descargarPagActivasExcel" class="btn btn-infoBuscar pull-right" style="margin-left: 5px;" disabled>
               <i class="fa fa-download"></i> Exportar a Excel
             </button>
             <button id="btn-descargarPagActivasPDF" class="btn btn-infoBuscar pull-right" disabled>
               <i class="fa fa-download"></i> Exportar a PDF
             </button>
          </div>
        </div>

        <div class="panel-body">
          <div style="max-height:500px; overflow:auto;">
            <table id="tablaPagActivas" class="table table-striped table-hover" style="table-layout:fixed; width:100%;">
              <colgroup>
                <col style="width:4%"> <!-- Checkbox -->
                <col style="width:10%"> <!-- Fecha -->
                <col style="width:15%"> <!-- Usuario -->
                <col style="width:26%"> <!-- URL (reduced) -->
                <col style="width:10%"> <!-- Estado -->
                <col style="width:20%"> <!-- Detalle -->
                <col style="width:15%"> <!-- Plataforma -->
              </colgroup>
              <thead>
                <tr>
                  <th class="text-center"><input type="checkbox" id="checkAllPagActivas"></th>
                  <th>Fecha</th>
                  <th>Usuario</th>
                  <th>URL</th>
                  <th>Estado</th>
                  <th>Detalle</th>
                  <th>Plataforma</th>
                </tr>
              </thead>
              <tbody id="cuerpoTablaPagActivas"></tbody>
            </table>
          </div>
          <div id="mensajeImportacion" class="text-info" style="margin-top:10px;"></div>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- ... (other panels hidden) ... -->

<!-- MODAL BAJA PAGINAS INACTIVAS -->
<div class="modal fade" id="modalBajaPaginas" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
  <div class="modal-dialog">
     <div class="modal-content">
       <div class="modal-header" style="background-color: #d9534f; color: #fff">
         <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">×</span></button>
         <h3 class="modal-title">Dar de baja páginas inactivas</h3>
       </div>
       <div class="modal-body">
         <p>Se encontraron <b id="cant_baja">0</b> páginas con estado <b>Inactivo</b>.</p>
         <p>A continuación se listan las páginas que serán procesadas:</p>
         <ul id="lista_baja" style="max-height: 200px; overflow: auto; border: 1px solid #ccc; padding: 10px; background: #f9f9f9;"></ul>
         <p class="text-danger"><small>Esta acción buscará los usuarios/urls en la base de datos y actualizará su estado a "Baja".</small></p>
       </div>
       <div class="modal-footer">
         <button type="button" class="btn btn-default" data-dismiss="modal">Cancelar</button>
         <button type="button" class="btn btn-danger" id="btn-confirmar-baja">Confirmar Baja</button>
       </div>
     </div>
  </div>
</div>


<div id="pant_estadisticas" hidden>
  <div class="row">
    <div class="col-md-12">
      <div class="panel panel-default">
        <div class="panel-heading">

      </div>

        <div class="panel-body">
          <div class="row">
    <div class="col-md-4">
        <div class="panel panel-primary" style="border-color: #3b82f6;">
            <div class="panel-heading" style="background-color: #3b82f6; border-color: #3b82f6;">
                <h3 class="panel-title text-center" style="font-weight:bold; color:white;">
                    <i class="fa fa-search"></i> TOTAL DETECTADAS
                </h3>
            </div>
            <div class="panel-body text-center">
                <h1 id="kpiTotalDetectadas" style="margin:5px 0; font-weight:800; color:#3b82f6;">...</h1>
                <small class="text-muted">Perfiles identificados históricamente</small>
            </div>
        </div>
    </div>

    <div class="col-md-4">
        <div class="panel panel-success" style="border-color: #16a34a;">
            <div class="panel-heading" style="background-color: #16a34a; border-color: #16a34a;">
                <h3 class="panel-title text-center" style="font-weight:bold; color:white;">
                    <i class="fa fa-check-circle"></i> TOTAL DADAS DE BAJA
                </h3>
            </div>
            <div class="panel-body text-center">
                <h1 id="kpiTotalBajas" style="margin:5px 0; font-weight:800; color:#16a34a;">...</h1>
                <small class="text-muted">Cuentas eliminadas exitosamente</small>
            </div>
        </div>
    </div>

    <div class="col-md-4">
        <div class="panel panel-info" style="border-color: #8b5cf6;">
            <div class="panel-heading" style="background-color: #8b5cf6; border-color: #8b5cf6;">
                <h3 class="panel-title text-center" style="font-weight:bold; color:white;">
                    <i class="fa fa-trophy"></i> TASA DE EFECTIVIDAD
                </h3>
            </div>
            <div class="panel-body text-center">
                <h1 id="kpiEfectividad" style="margin:5px 0; font-weight:800; color:#8b5cf6;">...</h1>
                <small class="text-muted">% de detección convertido en baja</small>
            </div>
        </div>
    </div>
</div>
<div class="row">
    <div class="col-md-12">
        <div id="chartImpactoAcumulado" style="height: 350px;"></div>
    </div>
</div>
</br>

            <div class="row">
            <div id="chartActivasBajas" style="height:380px;min-width:320px"></div>
          </div>
        </br>
        <div class="row">
        <div class="col-md-6">
          <div id="chartEstadosPie" style="height: 340px;"></div>
        </div>
          <div class="col-md-6">
            <div id="chartPiePlataformas" style="height: 340px;"></div>
          </div>
        </div>
      </br>
        <div class="row">
            <div class="col-md-6">
              <div id="chartOrigenDenuncias" style="height: 340px;"></div>
            </div>
            <div class="col-md-6">
              <div id="chartEfectividad" style="height: 340px;"></div>
            </div>
          </div>
          <br>
  <div class="row">
    <div class="col-md-12">
      <div id="chartTopPerfiles" style="height: 400px;"></div>
    </div>
  </div>
</br>
<div class="row">
  <div class="col-md-12">
    <div id="chartEvolucionAnual" style="height: 400px; min-width: 320px"></div>
  </div>
</div>
        </div>
      </div>
    </div>
  </div>
</div>




  <!-- MODAL CARGAR denunciasAlea_paginas -->

  <div class="modal fade" id="modalCargardenunciasAlea_paginas" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
        <div class="modal-dialog" style="width:45%">
           <div class="modal-content">
             <div class="modal-header modalNuevo" style="background-color: #6dc7be;">
               <button type="button" class="close" data-dismiss="modal"><i class="fa fa-times"></i></button>
               <button id="btn-minimizarCreardenunciasAlea_paginas" type="button" class="close" data-toggle="collapse" data-minimizar="true" data-target="#colapsadoCreardenunciasAlea_paginas" style="position:relative; right:20px; top:5px"><i class="fa fa-minus"></i></button>
               <h3 class="modal-title" style="background-color: #6dc7be;">| Agregar</h3>
              </div>

              <div  id="colapsadoCreardenunciasAlea_paginas" class="collapse in">

      <form id="formNuevoRegistrodenunciasAlea_paginas" novalidate="" method="POST" autocomplete="off">

        <input type="hidden" id="denunciasAlea_paginas_modo" name="denunciasAlea_paginas_modo" value="create">
        <input type="hidden" id="id_registrodenunciasAlea_paginas" name="id_registrodenunciasAlea_paginas" value="">

        <div class="modal-body">
          <div class="row">
            <div class="col-md-4">
              <h5>Fecha <span class="text-danger" aria-hidden="true">(obligatorio)</span></h5>
            </div>
            <div class="col-md-8">
              <div class='input-group date' id='fechadenunciasAlea_paginasPres' data-date-format="yyyy-mm-dd" data-link-format="yyyy-mm">
                  <input name="fecha_denunciasAlea_paginasPres" type='text' class="form-control" placeholder="yyyy-mm-dd" id="fecha_denunciasAlea_paginasPres" style="background-color: rgb(255,255,255);"/>
                  <span class="input-group-addon" style="border-left:none;cursor:pointer;"><i class="fa fa-times"></i></span>
                  <span class="input-group-addon" style="cursor:pointer;"><i class="fa fa-calendar"></i></span>
              </div>
            </div>
          </div>
          <br/>

          <div class="row">
            <div class="col-md-4" >
              <h5>Usuario de página</h5>
            </div>
            <div class="col-md-8">
              <input type="text" class="form-control" name="usuariodenunciasAlea_paginas"  >
            </div>
          </div>
          <br/>

          <div class="row">
            <div class="col-md-4" >
              <h5>Red/Plataforma</h5>
            </div>
            <div class="col-md-8">
              <select name="plataformadenunciasAlea_paginas" class="form-control" id="plataformadenunciasAlea_paginas">
                <option value="">Por favor seleccione...</option>
                @foreach($plataformas as $p)
                  <option value="{{ $p->id_denunciasAlea_plataforma }}">{{ $p->plataforma }}</option>
                @endforeach
              </select>
            </div>
          </div>
          <br/>

          <div class="row">
            <div class="col-md-4" >
              <h5>Link de la Página <span class="text-danger" aria-hidden="true">(obligatorio)</span></h5>
            </div>
            <div class="col-md-8">
              <input type="text" class="form-control" name="linkPaginadenunciasAlea_paginas"  >
            </div>
          </div>
          <br/>

          <div class="row">
  <div class="col-md-4"><h5>Denuncia Alea</h5></div>
  <div class="col-md-8">
    <!-- ÚNICO campo que viaja al back -->
    <input type="hidden" name="denuncia_alea" id="denuncia_alea_hidden" value="0">

    <label class="switch-alea" style="margin:0;">
      <input type="checkbox"
             id="denuncia_alea_chk"
             {{ old('denuncia_alea', isset($pagina) ? (int)$pagina->denunciada : 0) ? 'checked' : '' }}>
      <span class="slider-alea">
        <span class="slider-text off">No</span>
        <span class="slider-text on">Sí</span>
      </span>
    </label>
    <span id="denuncia_alea_text" style="margin-left:8px;font-weight:600;"></span>
  </div>
</div>
          <br/>

          <div class="row">
            <div class="col-md-4" >
              <h5>Cantidad de Denuncias</h5>
            </div>
            <div class="col-md-8">
              <input type="text" class="form-control" name="CantdenunciasAlea_paginas"  >
            </div>
          </div>
          <br/>

          <div class="row">
            <div class="col-md-4" >
              <h5>Estado actual de la página</h5>
            </div>
            <div class="col-md-8">
              <select name="estadodenunciasAlea_paginas" class="form-control" id="estadodenunciasAlea_paginas">
                <option value="">Por favor seleccione...</option>
                @foreach($estados as $e)
                  <option value="{{ $e->id_denunciasAlea_estado }}">{{ $e->estado }}</option>
                @endforeach
              </select>            </div>
          </div>
          <br/>

          <div class="row">
            <div class="col-md-4" >
              <h5>Denunciado en</h5>
            </div>
            <div class="col-md-8">
              <select name="lugardenunciasAlea_paginas" class="form-control" id="lugardenunciasAlea_paginas">
                @foreach($lugares as $l)
                  <option value="{{ $l->id_denunciasAlea_denunciadoEn }}">{{ $l->lugar }}</option>
                @endforeach
              </select>            </div>
          </div>
        </br>


            </div>
          </div>
        <div class="modal-footer">

          <button id ="guardarRegistrodenunciasAlea_paginas" type="button" class="btn btn-successAceptar">GENERAR</button>
          <button type="button" id ="salir" class="btn btn-default btn-salir" data-js-salir data-dismiss="modal">SALIR</button>

        </div>
          </form>
        </div> <!-- modal content -->
      </div> <!-- modal dialog -->
    </div> <!-- modal fade -->
    </div>
  </div>


  <!-- MODAL ELIMINAR DENUNCIA -->

  <div class="modal fade" id="modalEliminardenunciasAlea_paginas" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                  <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">×</span></button>
                    <h3 class="modal-titleEliminar">ADVERTENCIA</h3>
                  </div>

                  <div class="modal-body franjaRojaModal">
                    <form id="frmEliminar" name="frmCasino" class="form-horizontal" novalidate="">
                        <div class="form-group error ">
                          <div class="col-xs-12">
                              <strong id="titulo-modal-eliminar">¿Seguro desea eliminar la denuncia?</strong>
                          </div>
                        </div>
                    </form>
                  </div>

                  <div class="modal-footer">
                    <button type="button"  id="btn-eliminardenunciasAlea_paginas" class="btn btn-dangerEliminar"> ELIMINAR  </button>
                    <button type="button" class="btn btn-default" data-dismiss="modal">CANCELAR</button>
                  </div>
              </div>
            </div>
  </div>



@endsection
@section('scripts')

  <script src="/js/highcharts_11_3_0/highcharts.js"></script>
  <script src="/js/highcharts_11_3_0/highcharts-more.js"></script>
  <script src="/js/highcharts_11_3_0/highcharts-3d.js"></script>

  <script src="/js/highcharts_11_3_0/modules/exporting.js"></script>
  <script src="/js/highcharts_11_3_0/modules/export-data.js"></script>
  <script src="/js/highcharts_11_3_0/modules/accessibility.js"></script>
  <script src="/js/highcharts_11_3_0/modules/drilldown.js"></script>
  <script src="/js/highcharts_11_3_0/modules/offline-exporting.js"></script>
  <!-- JavaScript paginacion -->
  <script src="/js/paginaciondocumentosContables.js" charset="utf-8"></script>

  <!-- JavaScript personalizado -->
  <script src="js/denunciasAlea.js" charset="utf-8"></script>

  <!-- DateTimePicker JavaScript -->
  <script type="text/javascript" src="js/bootstrap-datetimepicker.js" charset="UTF-8"></script>
  <script type="text/javascript" src="js/bootstrap-datetimepicker.es.js" charset="UTF-8"></script>

  <!-- Custom input Bootstrap -->
  <script src="js/fileinput.min.js" type="text/javascript"></script>
  <script src="js/locales/es.js" type="text/javascript"></script>
  <script src="/themes/explorer/theme.js" type="text/javascript"></script>

  <script src="js/inputSpinner.js" type="text/javascript"></script>
  <script src="js/lista-datos.js" type="text/javascript"></script>

@endsection

<!-- Comienza modal de ayuda -->
@section('tituloDeAyuda')
  <h3 class="modal-title" style="color: #fff;">| Denuncias Alea</h3>
  @endsection
  @section('contenidoAyuda')
  <div class="col-md-12">
  <h5>Tarjeta de Denuncias Alea</h5>
  <p>
   Denuncias Alea...
  </p>
  </div>
@endsection
<!-- Termina modal de ayuda -->
