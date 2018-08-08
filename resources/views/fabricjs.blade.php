@extends('includes.nuevaBarraNavegacion')


@section('contenidoVista')

<header>
  <img class="iconoSeccion" src="/img/logos/layout_blue.png" alt="">
  <h2>PLANOS</h2>
</header>

<div class="row">
  <div class="col-md-12">
    <div class="panel panel-default">
      <div class="panel-heading">
          <h4>FABRICJS</h4>
      </div>
      <div class="panel-heading">
            <button id="sacarControl" type="button" name="button">SACAR CONTROL</button>
            <button id="agregarMaquina" type="button" name="button">+ MÁQUINA</button>
            <button id="agregarIsla" type="button" name="button">+ ISLA</button>
            <br>

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
    var canvas = new fabric.Canvas('c', {preserveObjectStacking: true});

    //Estructura JSON para dibujar islas y máquinas
    var listaIslas = [
        // {
        //   nro_isla: 16,
        //   top: 300,
        //   left: 150,
        //   maquinas: [
        //       {
        //         nro_admin: 1234,
        //         top: 30,
        //         left: 30,
        //       },
        //       {
        //         nro_admin: 4321,
        //         top: 5,
        //         left: 8,
        //       }
        //   ],
        // },
        // {
        //   nro_isla: 18,
        //   top: 100,
        //   left: 450,
        //   maquinas: [
        //       {
        //         nro_admin: 6543,
        //         top: 5,
        //         left: 6,
        //       },
        //       {
        //         nro_admin: 2383,
        //         top: 12,
        //         left: 30,
        //       },
        //       {
        //         nro_admin: 8761,
        //         top: 12,
        //         left: 11,
        //       }
        //   ],
        // }
    ];


    for (var i = 0; i < 10; i++) {
        var xIsla = fabric.util.getRandomInt(0, 1400);
        var yIsla = fabric.util.getRandomInt(0, 600);
        //
        var cantidadMaquinas = fabric.util.getRandomInt(1,15);

        var listaMaquinas = [];

        for (var j = 0; j < cantidadMaquinas; j++) {
            var xMaquina = fabric.util.getRandomInt(0, 50);
            var yMaquina = fabric.util.getRandomInt(0, 50);

            var nuevaMaquina =  {
                                  nro_admin: fabric.util.getRandomInt(1,6000),
                                  top: xMaquina,
                                  left: yMaquina,
                                };

            listaMaquinas.push(nuevaMaquina);
        }

        var nuevaIsla = {
                          nro_isla: i,
                          top: yIsla,
                          left: xIsla,
                          maquinas: listaMaquinas,
                        };


        listaIslas.push(nuevaIsla);
    }

    //Dibujar todas las máquinas y sus islas desde un JSON
    function dibujarLayout() {
        //Crear todas las islas
        for (var i = 0; i < listaIslas.length; i++) {
            // console.log('Isla: ', listaIslas[i].nro_isla);

            canvas.add(new fabric.Rect({
                id: listaIslas[i].nro_isla,
                type: 'isla',
                width: 120,
                height: 120,
                left: listaIslas[i].left,
                top: listaIslas[i].top,
                hasControls: true,
                fill: '#EF9A9A',
                stroke: '#E57373',
                strokeWidth: 3,
                minScaleLimit: 0.8,
                maxScaleLimit: 1.1,
            }));

            //Crear todas las máquinas
            for (var j = 0; j < listaIslas[i].maquinas.length; j++) {
                  // console.log('Máquina: ', listaIslas[i].maquinas[j]);

                  // canvas.add(new fabric.Circle({
                  //    radius: 15,
                  //    id: listaIslas[i].maquinas[j].nro_admin,
                  //    isla: listaIslas[i].nro_isla,
                  //    type: 'maquina',
                  //   //  width: 10,
                  //   //  height: 10,
                  //    fill: '#afa',
                  //    top: listaIslas[i].top + listaIslas[i].maquinas[j].top,
                  //    left: listaIslas[i].left + listaIslas[i].maquinas[j].left,
                  //    hasControls: false,
                  //    index: 6,
                  // }));
                  var texto = listaIslas[i].maquinas[j].nro_admin.toString();

                  var circle = new fabric.Circle({
                    radius: 14,
                    fill: '#90CAF9',
                    originX: 'center',
                    originY: 'center',
                    stroke: '#2196F3',
                    strokeWidth: 3,
                  });

                  var text = new fabric.Text(texto, {
                    fontSize: 10,
                    originX: 'center',
                    originY: 'center'
                  });

                  var group = new fabric.Group([ circle, text ], {
                    type: 'maquina',
                    id: listaIslas[i].maquinas[j].nro_admin,
                    isla: listaIslas[i].nro_isla,
                    top: listaIslas[i].top + listaIslas[i].maquinas[j].top,
                    left: listaIslas[i].left + listaIslas[i].maquinas[j].left,
                    hasControls: false,
                  });

                  canvas.add(group);
            }
        }
    }

    dibujarLayout();

    //Se guardan instancias de los objetos intersectados para poder mover
    var objetoIsla = null;
    var objetosMaquinas = [];

    canvas.on("object:selected", function (e) {

        if (e.target.type == 'maquina') {
            objetoIsla = null;

            //Buscar la isla de la máquina
            canvas.forEachObject(function(objeto){
                if (objeto.id == e.target.isla) objetoIsla = objeto;
            });
        }
        else if (e.target.type == 'isla') {
            objetosMaquinas = [];

            //Buscar todas las máquinas de la isla
            canvas.forEachObject(function(objeto) {
                if (objeto === e.target) return;
                if (objeto.type == 'maquina' && e.target.intersectsWithObject(objeto) && objeto.isla == e.target.id) {
                    objetosMaquinas.push(objeto);
                }
                // e.target.intersectsWithObject(objeto) && objeto.type == 'maquina' ? objetosMaquinas.push(objeto) : null;
            });

            canvas.sendToBack(e.target); //Se manda al fondo la isla seleccionada
        }
    });

    canvas.on("object:moving", function (e) {
      //Si se mueve una máquina
        if (e.target.type == 'maquina') {
            var objetoMaquina = e.target;
            //Que la máquina no se salga de la isla
            if (objetoMaquina.left < objetoIsla.left)
                          objetoMaquina.left = objetoIsla.left;

            if (objetoMaquina.top < objetoIsla.top)
                          objetoMaquina.top = objetoIsla.top;

            if (objetoMaquina.top + objetoMaquina.getHeight() > objetoIsla.top + objetoIsla.getHeight())
                          objetoMaquina.top = objetoIsla.top + objetoIsla.getHeight() - objetoMaquina.getHeight();

            if (objetoMaquina.left + objetoMaquina.getWidth() > objetoIsla.left + objetoIsla.getWidth())
                          objetoMaquina.left = objetoIsla.left + objetoIsla.getWidth() - objetoMaquina.getWidth();

            objetoMaquina.setCoords();
        }

      //Si se mueve una isla
        if (e.target.type == 'isla') {
            var x = e.e.movementX;
            var y = e.e.movementY;
            $.each(objetosMaquinas, function(i, obj) {
                obj.set('left', obj.left + x);
                obj.set('top', obj.top + y);
                obj.setCoords();
            });

        }
    });

    var maxScaleLimit = 2;

    canvas.on('object:scaling',function(e){
      //Si se escala una ISLA
        if (e.target.type == 'isla') {
            objetoIsla = e.target;

            //Que las máquinas no se salgan de la isla
            $.each(objetosMaquinas,function(i,objetoMaquina) {
                if (objetoMaquina.left < objetoIsla.left)
                              objetoMaquina.left = objetoIsla.left;

                if (objetoMaquina.top < objetoIsla.top)
                              objetoMaquina.top = objetoIsla.top;

                if (objetoMaquina.top + objetoMaquina.getHeight() > objetoIsla.top + objetoIsla.getHeight())
                              objetoMaquina.top = objetoIsla.top + objetoIsla.getHeight() - objetoMaquina.getHeight();

                if (objetoMaquina.left + objetoMaquina.getWidth() > objetoIsla.left + objetoIsla.getWidth())
                              objetoMaquina.left = objetoIsla.left + objetoIsla.getWidth() - objetoMaquina.getWidth();

                objetoMaquina.setCoords();

            });

            //Que la isla no se escale demás del límite
            if (objetoIsla.scaleX > maxScaleLimit) {
                objetoIsla.scaleX =  maxScaleLimit;
            }
            if (objetoIsla.scaleY > maxScaleLimit) {
                objetoIsla.scaleY =  maxScaleLimit;
            }

        }

        // e.target.set('strokeWidth', e.target.strokeWidth / Math.max(e.target.scaleX, e.target.scaleY));
        //No cambiar escala del borde
        // e.target.set('widthStroke', e.target.origStrokeWidth / Math.max(e.target.scaleX, e.target.scaleY));

    });

    canvas.on('object:modified',function(e){

    });






</script>
@endsection
