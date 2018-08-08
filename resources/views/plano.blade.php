@extends('includes.nuevaBarraNavegacion')


@section('contenidoVista')

<header>
  <img class="iconoSeccion" src="/img/logos/layout_blue.png" alt="">
  <h2>PLANOS ZOOM</h2>
</header>

<div class="row">
  <div class="col-md-12">
    <div class="panel panel-default">
      <div class="panel-heading">
          <h4>FABRICJS</h4>
      </div>
      <div class="panel-heading">
            <center>
                <canvas id="c" width="1500" height="700" style="border: 5px solid #ccc;"></canvas>
            </center>
      </div>
    </div>
  </div>
</div>

@endsection

@section('scripts')
<!-- JavaScript personalizado -->
<script src="/js/fabricjs.js" charset="utf-8"></script>

<script type="text/javascript">
    // Se crea el canvas referenciando la etiqueta HTML
      // preserveObjectStacking:true -> sirve para poder cambar z-index de los objetos
    var canvas = new fabric.Canvas('c', {preserveObjectStacking: true, hoverCursor: 'pointer'});

    canvas.setBackgroundImage('/img/planos layout/planta_baja_rosario.png', canvas.renderAll.bind(canvas));

    var zona1 = new fabric.Path('M 20 20 L 300 300 L 170 100 z');

    var zona2 = new fabric.Path('M 20 20 L 300 300 L 40 70 z');

    zona1.set({ id: 1, perPixelTargetFind: true, targetFindTolerance: 4, fill: 'red', left: 320, top: 120 });
    zona2.set({ id: 2, perPixelTargetFind: true, targetFindTolerance: 4, fill: 'blue', left: 520, top: 220 });


    canvas.add(zona1);
    canvas.add(zona2);

    canvas.on('object:selected', function(e){
      var objeto = e.target;

        console.log('Objeto seleccionado: ', objeto.id);
        console.log(objeto.getLeft());
        console.log(objeto.getWidth());

        var centroX = (objeto.getLeft() + objeto.getWidth()) / 2;
        var centroY = (objeto.getTop() + objeto.getHeight()) / 2

        console.log('Centro: ' + centroX + ' ' + centroY);


        var zoomCenter = new fabric.Point(centroX, centroY);
        canvas.zoomToPoint({x:centroX,y:centroY}, 3);

        canvas.renderAll();
    });





</script>
@endsection
