@component('Components/FiltroTabla')
  @slot('titulo')
  <div>AGRUPAMIENTOS</div>
  @endslot
  
  @slot('target_buscar')
  /canon/agrupamientos/buscar
  @endslot
  
  @slot('filtros')
  @endslot
  
  @slot('cabecera')
  <tr>
    <th>CLAVE</th>
    <th>NIVEL</th>
    <th>VALOR</th>
    <th>CASINO</th>
    <th>DEPENDENCIA</th>
  </tr>
  @endslot
  
  @slot('molde')
  <tr data-table-id="id_canon_subcanon_a_grupo">
    <td>CLAVE</td>
    <td>NIVEL</td>
    <td>VALOR</td>
    <td>CASINO</td>
    <td>DEPENDENCIA</td>
  </tr>
  @endslot
@endcomponent
