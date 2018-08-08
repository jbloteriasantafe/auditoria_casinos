<!DOCTYPE html>
<html lang="es">
<head>
	<meta charset="UTF-8">
	<title>Document</title>

	<link href="css/bootstrap.min.css" rel="stylesheet">

	<style>
		.sector {
			text-align: center;
			display: inline-block;
			border: 5px solid #333;
			width: 100%;
			min-height: 500px;
		}

		.isla {
			border: 5px solid #444;
			min-height: 250px;
			margin: 10px;
		}

		.maquina {
			margin: 5px 0px;
		}

		.pintado {
			text-align: center;
			font-size: 20px;
			border: 5px solid #555;
			border-radius: 50%;
			background-color: yellow;
		}
	</style>
</head>
<body>

	<div class="container">
		<div class="row">
			<div class="col-md-6">
					<div class="sector">
						<h3>SECTOR OESTE</h3>
						<div class="isla row" draggable="true">
							<h4>ISLA 1</h4>
							<div class="maquina col-md-3" draggable="true"><div class="pintado">1</div></div>
							<div class="maquina col-md-3" draggable="true"><div class="pintado">2</div></div>
							<div class="maquina col-md-3" draggable="true"><div class="pintado">3</div></div>
							<div class="maquina col-md-3" draggable="true"><div class="pintado">4</div></div>
							<div class="maquina col-md-3" draggable="true"><div class="pintado">5</div></div>
							<div class="maquina col-md-3" draggable="true"><div class="pintado">6</div></div>
							<div class="maquina col-md-3" draggable="true"><div class="pintado">7</div></div>
							<div class="maquina col-md-3" draggable="true"><div class="pintado">8</div></div>
							<div class="maquina col-md-3" draggable="true"><div class="pintado">9</div></div>
							<div class="maquina col-md-3" draggable="true"><div class="pintado">10</div></div>
							<div class="maquina col-md-3" draggable="true"><div class="pintado">11</div></div>
							<div class="maquina col-md-3" draggable="true"><div class="pintado">13</div></div>
							<div class="maquina col-md-3" draggable="true"><div class="pintado">14</div></div>
							<div class="maquina col-md-3" draggable="true"><div class="pintado">15</div></div>
							<div class="maquina col-md-3" draggable="true"><div class="pintado">16</div></div>
							<div class="maquina col-md-3" draggable="true"><div class="pintado">17</div></div>
							<div class="maquina col-md-3" draggable="true"><div class="pintado">18</div></div>
							<div class="maquina col-md-3" draggable="true"><div class="pintado">19</div></div>
							<div class="maquina col-md-3" draggable="true"><div class="pintado">20</div></div>
						</div>
						<div class="isla row" draggable="true">
							<h4>ISLA 2</h4>

						</div>
					</div>
			</div>
			<div class="col-md-6">
					<div class="sector">
						<h3>SECTOR ESTE</h3>
					</div>
			</div>
		</div>
	</div>



	<script src="js/jquery.js"></script>
	<!-- Bootstrap Core JavaScript -->
	<script src="js/bootstrap.min.js"></script>

	<script type="text/javascript">
		var isla;
		var maquina

		$('.isla').on('dragstart', function(e){
			e.originalEvent.dataTransfer.setData('text/plain', 'anything');
			isla = $(this);
		});

		$('.isla').on('dragend', function(e){

		});

		$('.sector').on('dragover',function(e){
			e.preventDefault();
    		e.stopPropagation();
		});

		$('.sector').on('dragenter',function(e){
			e.preventDefault();
    		e.stopPropagation();
		});

		$('.sector').on('drop',function(e){
			e.preventDefault();
    		e.stopPropagation();

    		if(isla != null && isla.parent()[0] != $(this)[0]){
    			$(this).append(isla);
    		}

    		isla = null;
		});



		$('.maquina').on('dragstart', function(e){
			$(this).children('.pintado').css('background','blue');
			e.originalEvent.dataTransfer.setData('text/plain', 'anything');
			maquina = $(this);
		});

		$('.maquina').on('dragend', function(e){
			$(this).children('.pintado').css('background','yellow');
		});

		$('.isla').on('dragover', function(e){
			e.preventDefault();
    		e.stopPropagation();
		});
		$('.isla').on('dropenter', function(e){
			e.preventDefault();
    		e.stopPropagation();
		});

		$('.isla').on('drop', function(e){
			e.preventDefault();
    		e.stopPropagation();

			if (maquina != null && maquina.parent()[0] != $(this)[0]){
				$(this).append(maquina);
			}

			maquina = null;

		});

	</script>
</body>
</html>
