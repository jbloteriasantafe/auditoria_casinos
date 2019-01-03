<!DOCTYPE html>

<html>

<style>
table {
  font-family: arial, sans-serif;
  border-collapse: collapse;
  width: 98%;
}

td, th {
  border: 1px solid #dddddd;
  text-align: left;
  padding: 3px;
}


p {
      border-top: 1px solid #000;
}
.filaContadores{
  height: 10px !important;
}
footer
{
    margin-top:50px;
    width:200%;
    height:300px;
}
</style>

  <head>

    <title></title>

    <!-- <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/> -->

    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
  
    <!-- <link href="css/bootstrap.min.css" rel="stylesheet"> -->

    <link href="css/estiloPlanillaPortrait.css" rel="stylesheet">
  </head>
  <body>

        <div class="encabezadoImg">
              <img src="img/logos/banner_loteria_landscape2_f.png" width="900">
              <h2><span>RMES02 | Control de apertura y cierre de MESA DE PAÑO.</span></h2>
        </div>
              <div class="camposTab titulo" style="right:-15px;">FECHA PLANILLA</div>
              <div class="camposInfo" style="right:0px;"></span><?php $hoy = date('j-m-y / h:i');
                    print_r($hoy); ?></div>
              <!-- Tabla gral -->
              <table style="border-collapse: collapse;">
                  @foreach($rel->mesas as $lista_mesas)
                    <td>
                      <!-- tabla izquierda de MESAS -->
                      <table style="border-collapse: collapse;">
                        <tbody>
                          <tr>
                            <th class="tablaInicio" style="background-color: white; border-color: gray;" colspan="3">MESAS</th>
                          </tr>
                          <tr>
                            <th class=" tablaInicio" style="background-color: #dddddd; border-color: gray;">JUEGO-NRO</th>
                            <th class=" tablaInicio" style="background-color: #dddddd; border-color: gray;">HORA APERTURA</th>
                            <th class=" tablaInicio" style="background-color: #dddddd; border-color: gray;">HORA CIERRE</th>
                          </tr>
                          @foreach($lista_mesas as $mesa)
                          <tr>
                            <td class=" tablaInicio" style="background-color: #dddddd; border-color: gray;">{{$mesa['codigo_mesa']}}</td>
                            <td class=" tablaInicio" style="background-color: white; border-color: gray;"></td>
                            <td class=" tablaInicio" style="background-color: white; border-color: gray; "></td>
                          </tr>
                          @endforeach
                        </tbody>
                      </table>
                    </td>
                    @endforeach
              </table>

              <br>
              <table>
                <tr>
                  <th class="tablaInicio" style="padding-top: 50px; border-right: 0px; border-color: gray;"></th>
                  <th class="tablaInicio" style="border-left: 0px; border-color: gray;"></th>
                  <th class="tablaInicio" style="border-right: 0px; border-color: gray;"></th>
                  <th class="tablaInicio" style="border-left: 0px; border-color: gray;"></th>
                </tr>
                <tr>
                  <td class="tablaInicio" style="background-color: #dddddd; border-color: gray;"><center>Personal del Concesionario en Sala de Juegos</center></td>
                  <td class="tablaInicio" style="background-color: #dddddd; border-color: gray;"><center>Fiscalizador en Sala de Juegos</center></td>
                  <td class="tablaInicio" style="background-color: #dddddd; border-color: gray;"><center>Personal del Concesionario en Sala de Juegos</center></td>
                  <td class="tablaInicio" style="background-color: #dddddd; border-color: gray;"><center>Fiscalizador en Sala de Juegos</center></td>
                </tr>
              </table>
              <!-- segunda hoja -->
              <div style="page-break-after:always;"></div>
              <div class="encabezadoImg">
                    <img src="public/img/logos/banner_loteria_landscape2_f.png" width="900">
                    <h2><span>RMES02 | Control de apertura y cierre de MESA DE PAÑO.</span></h2>
              </div>
              <div class="camposTab titulo" style="right:-15px;">FECHA PLANILLA</div>
              <div class="camposInfo" style="right:0px;"></span><?php $hoy = date('j-m-y / h:i');
                    print_r($hoy); ?></div>

                    <!-- tabla derecha RULETA -->
                    <table >
                      <tbody>
                      <tr>
                        <th class="tablaInicio" style="background-color: white; border-color: gray;float: right;"colspan="4">MESAS DE RULETA</th>
                      </tr>
                      <tr>
                        <th class=" tablaInicio" style="background-color: #dddddd; border-color: gray;float: right;">NRO MESA</th>
                        <th class=" tablaInicio" style="background-color: #dddddd; border-color: gray;float: right;">JUEGO</th>
                        <th class=" tablaInicio" style="background-color: #dddddd; border-color: gray;float: right;">TIPO</th>
                        <th class=" tablaInicio" style="background-color: #dddddd; border-color: gray;float: right;">FISCALIZÓ</th>
                      </tr>
                      @foreach($rel->sorteadas->ruletasDados as $ruleta)
                        <tr>
                          <td class=" tablaInicio" style="background-color: #dddddd; border-color: gray;">{{$ruleta['nro_mesa']}}</td>
                          <td class=" tablaInicio" style="background-color: #dddddd; border-color: gray;">{{$ruleta['nombre_juego']}}</td>
                          <td class=" tablaInicio" style="background-color: #dddddd; border-color: gray;">{{$ruleta['descripcion']}}</td>
                          <td class=" tablaInicio" style=" border-color: gray;"></td>
                        </tr>
                      @endforeach
                      </tbody>
                    </table>
                    <br>
                    <br>
                    <!-- tabla derecha CARTAS Y DADOS -->
                    <table>
                      <tbody>
                      <tr>
                        <th class="tablaInicio" style="background-color: white; border-color: gray;float: right;"colspan="4">MESAS DE CARTAS/DADOS</th>
                      </tr>
                      <tr>
                        <th class=" tablaInicio" style="background-color: #dddddd; border-color: gray;float: right;">N° MESA</th>
                        <th class=" tablaInicio" style="background-color: #dddddd; border-color: gray;float: right;">JUEGO</th>
                        <th class=" tablaInicio" style="background-color: #dddddd; border-color: gray;float: right;">TIPO</th>
                        <th class=" tablaInicio" style="background-color: #dddddd; border-color: gray;float: right;">FISCALIZÓ</th>
                      </tr>
                      @foreach($rel->sorteadas->cartas as $carta)
                        <tr>
                          <td class=" tablaInicio" style="background-color: #dddddd; border-color: gray;">{{$carta['nro_mesa']}}</td>
                          <td class=" tablaInicio" style="background-color: #dddddd; border-color: gray;">{{$carta['nombre_juego']}}</td>
                          <td class=" tablaInicio" style="background-color: #dddddd; border-color: gray;">{{$carta['descripcion']}}</td>
                          <td class=" tablaInicio" style=" border-color: gray;"></td>
                       </tr>
                      @endforeach
                      </tbody>
                    </table>


              @foreach($rel->paginas as $p)
              <div style="page-break-after:always;"></div>
              <div class="encabezadoImg">
                    <img src="public/img/logos/banner_loteria_landscape2_f.png" width="900">
                    <h2><span>RMES02 | Control de apertura y cierre de MESA DE PAÑO.</span></h2>
              </div>
              <div class="camposTab titulo" style="right:-15px;">FECHA PLANILLA</div>
              <div class="camposInfo" style="right:0px;"></span><?php $hoy = date('j-m-y / h:i');
                    print_r($hoy); ?></div>

              <table style="padding-left:50px;">
                <tbody>
                  <tr>
                    <th class="tablaInicio" style="width:104px; text-align:center; background-color: #dddddd; border-color: gray;">FECHA</th>
                    <th class="tablaInicio" style="width:297px; text-align:center; background-color: #dddddd; border-color: gray;">JUEGO</th>
                    <th class="tablaInicio" style="width:100px; text-align:center; background-color: #dddddd; border-color: gray;">N° MESA</th>
                    <th class="tablaInicio" style="width:40px; text-align:center; background-color: #dddddd; border-color: gray;">ARS</th>
                    <th class="tablaInicio" style="width:40px; text-align:center; background-color: #dddddd; border-color: gray;">USD</th>
                  </tr>
                  <tr>
                    <td class="tablaInicio" style="width:104px; text-align:center; padding-top: 10px; background-color:white; border-color: gray;">
                      <?php $hoy = date('j-m-y'); print_r($hoy); ?>
                    </td>
                    <td class="tablaInicio" style="width:297px; background-color:white; border-color: gray;"></td>
                    <td class="tablaInicio" style="width:100px; background-color:white; border-color: gray;"></td>
                    <td class="tablaInicio" style="width:40px; background-color:white; border-color: gray;"></td>
                    <td class="tablaInicio" style="width:40px; background-color:white; border-color: gray;"></td>
                  </tr>
                </tbody>
              </table>

              <table style="padding-left:50px;">
                <tbody>
                  <tr>
                    <th class="tablaInicio" style="width:100px; text-align:center;background-color: #dddddd; border-color: gray;">VALOR FICHA</th>
                    <th class="tablaInicio" style="width:480px; text-align:center; background-color: #dddddd; border-color: gray;">CANTIDAD</th>
                  </tr>
                  @foreach($rel->fichas as $ficha)
                  <tr>
                    <td class="tablaInicio" style="width:100px; text-align:center; background-color: #dddddd; border-color: gray;">{{$ficha->valor_ficha}}</td>
                    <td class="tablaInicio" style="width:480px; background-color:white; border-color: gray;"></td>
                  </tr>
                  @endforeach
                </tbody>
              </table>
              <br>
              <br>
              <div class="tablaInicio" style="text-align: center;">.........................................</div>
              <div class="tablaInicio" style="text-align: center;">Firma y aclaración</div>
              @if($rel->cant_fichas > 15)
                <div style="page-break-after:always;"></div>
                <div class="encabezadoImg">
                      <img src="public/img/logos/banner_loteria_landscape2_f.png" width="900">
                      <h2><span>RMES02 | Control de apertura y cierre de MESA DE PAÑO.</span></h2>
                </div>
                <div class="camposTab titulo" style="right:-15px;">FECHA PLANILLA</div>
                <div class="camposInfo" style="right:0px;"></span><?php $hoy = date('j-m-y / h:i');
                      print_r($hoy); ?></div>
              @endif
              <table style="padding-left:50px;">
                <tbody>
                  <tr>
                    <th class="tablaInicio" style="width:104px; text-align:center; background-color: #dddddd; border-color: gray;">FECHA</th>
                    <th class="tablaInicio" style="width:297px; text-align:center; background-color: #dddddd; border-color: gray;">JUEGO</th>
                    <th class="tablaInicio" style="width:100px; text-align:center; background-color: #dddddd; border-color: gray;">N° MESA</th>
                    <th class="tablaInicio" style="width:40px; text-align:center; background-color: #dddddd; border-color: gray;">ARS</th>
                    <th class="tablaInicio" style="width:40px; text-align:center; background-color: #dddddd; border-color: gray;">USD</th>
                  </tr>
                  <tr>
                    <td class="tablaInicio" style="width:104px; text-align:center; padding-top: 10px; background-color:white; border-color: gray;">
                      <?php $hoy = date('j-m-y'); print_r($hoy); ?>
                    </td>
                    <td class="tablaInicio" style="width:297px; background-color:white; border-color: gray;"></td>
                    <td class="tablaInicio" style="width:100px; background-color:white; border-color: gray;"></td>
                    <td class="tablaInicio" style="width:40px; background-color:white; border-color: gray;"></td>
                    <td class="tablaInicio" style="width:40px; background-color:white; border-color: gray;"></td>
                  </tr>
                </tbody>
              </table>

              <table style="padding-left:50px;">
                <tbody>
                  <tr>
                    <th class="tablaInicio" style="width:100px; text-align:center;background-color: #dddddd; border-color: gray;">VALOR FICHA</th>
                    <th class="tablaInicio" style="width:480px; text-align:center; background-color: #dddddd; border-color: gray;">CANTIDAD</th>
                  </tr>
                  @foreach($rel->fichas as $ficha)
                  <tr>
                    <td class="tablaInicio" style="width:100px; text-align:center; background-color: #dddddd; border-color: gray;">{{$ficha->valor_ficha}}</td>
                    <td class="tablaInicio" style="width:480px; background-color:white; border-color: gray;"></td>
                  </tr>
                  @endforeach
                </tbody>
              </table>
              <br>
              <div class="tablaInicio" style="text-align: center;">.........................................</div>
              <div class="tablaInicio" style="text-align: center;">Firma y aclaración</div>
              @endforeach
      </body>
</html>
