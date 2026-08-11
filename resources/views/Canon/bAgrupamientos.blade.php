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
    <th>GRUPOS OPERADORES</th>
    <th>ACCIÓN</th>
  </tr>
  @endslot
  
  @slot('molde')
  <tr data-table-id="clave">
    <td class="clave">CLAVE</td>
    <td class="grupos_operadores">GRUPOS OPERADORES</td>
    <td>
      @if($permisos('canon_agrupamiento_ver'))
      <button class="btn" type="button" data-js-click-ver-agrupamiento="/canon/agrupamientos/obtener" title="VER"><i class="fa fa-fw fa-search-plus"></i></button>
      @endif
      @if($permisos('canon_agrupamiento_cargar'))
      <button class="btn" type="button" data-js-click-editar-agrupamiento="/canon/agrupamientos/obtener" title="EDITAR"><i class="fas fa-fw fa-pencil-alt"></i></button>
      @endif
    </td>
  </tr>
  @endslot
@endcomponent
