<!DOCTYPE html>
<html>

<head>
  <meta charset="utf-8">
  <title></title>
  <meta charset="utf-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link rel="stylesheet" href="/css/Canon/estiloPlanilla.css">
</head>
<body>
  <style>
    .link_planilla {
      all: unset;
      padding: 0.1em;
      background: black;
      color: white;
      border: 1px solid grey;
      cursor: pointer;
    }
    .link_planilla:hover {
      background: darkgrey;
    }
    .link_planillla_usada {
      padding: 0.1em;
      background: white;
      color: black;
      border: 1px solid grey;
      cursor: pointer;
    }
  </style>
  <div style="padding: 0.5em;border-bottom: 4px groove black;background: lightcyan;">
    {{$casino}} {{$año}} {{$mes}}
    <button data-js-click-descargar-tabla="[data-target-descargar-tabla]" style="float: right;">Descargar tabla</button>
  </div>
  <div data-target-descargar-tabla>
    <style>
      tr th {
        border-right: 1px solid black;
      }
      tr td {
        border-right: 1px solid black;
      }
    </style>
    <div style="width: 100%;">
      <table style="width: 100%;table-layout: fixed">
        <thead>
          <tr>
            <th class="mes celda_especial" style="border-right: 1px solid black">{{$casino}}</th>
          </tr>
        </thead>
        <tbody>
          <tr>
            <th class="mes celda_especial" style="border-right: 1px solid black">{{$año}}</th>
          </tr>
        </tbody>
    </table>
  </div>
</body>

<script>
  const parametros = {
    'planilla': 'canon_mensual',
    'casino': '{{$casino}}',
    'año': '{{$año}}',
    'mes': '{{$mes}}'
  };
  const nombre_archivo = Object.values(parametros).join('_');
  const planilla = 'canon_mensual';
  const año = "{!! isset($año)? $año : '' !!}";
  const mes = "{!! isset($mes)? $mes : '' !!}";
  const fecha_planilla = "{!! date('Ymdhi') !!}";
</script>
<script src="/js/Canon/planillas.js?1" charset="utf-8" type="module"></script>

</html>
