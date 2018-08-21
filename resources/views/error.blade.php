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

tr:nth-child(even) {
  background-color: #dddddd;
}

p {
      border-top: 1px solid #000;
}
</style>

<head>
  <meta charset="utf-8">
  <title></title>

  <!-- <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/> -->

  <meta charset="utf-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <meta name="viewport" content="width=device-width, initial-scale=1">

  <!-- <link href="css/bootstrap.min.css" rel="stylesheet"> -->

  <link href="css/estiloPlanillaPortrait.css" rel="stylesheet">
</head>
<body>
  <div class="col-xl-12" style="text-align:center">

    <h5> ERROR! </h5>
    <h5>{{$aa->detalles}}</h5>
  <h5>  $aa= new \stdClass();
    $aa->detalles = 'No hay contadores importados.';
    $view = View::make('error', compact('aa'));
    $dompdf = new Dompdf();
    $dompdf->set_paper('A4','landscape');
    $dompdf->loadHtml($view->render());
    $dompdf->render();
    $font = $dompdf->getFontMetrics()->get_font("helvetica","regular");
    $dompdf->getCanvas()->page_text(750,565,"Página {PAGE_NUM} de {PAGE_COUNT}",$font,10,array(0,0,0));
    return $dompdf;
    </h5>
  </div>


</body>
</html>
