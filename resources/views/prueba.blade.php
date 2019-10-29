  <?php
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

use App\Maquina;
use App\Rol;
use App\Sector;
use App\GliSoft;
use App\Isla;
use App\LayoutParcial;
use App\Relevamiento;
use App\EstadoMaquina;
use App\Http\Controllers\MTMController;
use App\Http\Controllers\UsuarioController;
use App\Http\Controllers\CasinoController;
use App\Http\Controllers\JuegoController;
use App\Http\Controllers\IslaController;

$string = 'aristocrat modelo bla bla';
$pos = explode('aristocrat' , $string);
print_r($pos[1]);
echo '<br>';

?>
@extends('includes.dashboard')
@section('headerLogo')
<span class="etiquetaLogoMaquinas">@svg('maquinas','iconoMaquinas')</span>
@endsection

@section('estilos')
  <link rel="stylesheet" href="/css/paginacion.css">
  <link rel="stylesheet" href="/css/lista-datos.css">

@endsection

@section('contenidoVista')
<style media="screen">

.my-group .form-control{
    width:25%;
}
.my-group{
  margin-bottom: 15px !important;
}

</style>
<div class="row"><!-- Cuerpo-->
  <div class="container">
    <div class="col-lg-6">
      <div class="input-group my-group">
        <select id="lunch" class="selectpicker form-control"  title="Please select a lunch ...">
          <option>Hot Dog, Fries and a Soda</option>
          <option>Burger, Shake and a Smile</option>
          <option>Sugar, Spice and all things nice</option>
          <option>Baby Back Ribs</option>
        </select>

        <input type="text" class="form-control" name="snpid" placeholder="Primero">
        <input type="text" class="form-control" name="snpid" placeholder="Segundo">
        <input type="text" class="form-control" name="snpid" placeholder="Tercero">
      </div>
      <!-- /input-group -->
    </div>
    <!-- /.col-lg-6 -->
  </div>
</div>


@endsection
@section('scripts')
<!-- JavaScript -->
<script src="/js/prueba.js" charset="utf-8"></script>

@endsection
