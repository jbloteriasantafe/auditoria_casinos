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
    <th>GRUPO OPERARIO</th>
    <th>ACCIÓN</th>
  </tr>
  @endslot
  
  @slot('molde')
  <tr data-table-id="id_canon_subcanon_a_grupo">
    <td class="clave">CLAVE</td>
    <td class="grupo_operario">GRUPO OPERARIO</td>
    <td>
      <button class="btn" type="button" data-js-click-editar-agrupamiento="/canon/agrupamientos/obtener" title="EDITAR"><i class="fas fa-fw fa-pencil-alt"></i></button>
    </td>
  </tr>
  @endslot
@endcomponent
