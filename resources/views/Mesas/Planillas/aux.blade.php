<!-- tabla derecha RULETA -->
<table >
  <tbody>
  <tr>
    <th class="tablaInicio" style="background-color: white; border-color: gray;float: right;"colspan="4">MESAS DE RULETA</th>
  </tr>
  <tr>
    <th class=" tablaInicio" style="background-color: #dddddd; border-color: gray;float: right;">N° MESA</th>
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
