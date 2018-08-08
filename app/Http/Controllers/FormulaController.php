<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Formula;
use App\Maquina;
use Validator;


class FormulaController extends Controller
{

  protected $arreglo=array('cont1','cont2','cont3','cont4','cont5','cont6','cont7','cont8','operador1','operador2','operador3','operador4','operador5','operador6','operador7');


  private static $atributos=[
    'id_casino' => 'Id Casino',
    'formula.*.contador' => 'Contador',
    'formula.*.operador' => 'Operador',

    ];

  private static $instance;

  public static function getInstancia(){
    if (!isset(self::$instance)) {
      self::$instance = new FormulaController();
    }
    return self::$instance;
  }

  public function buscarTodo(){//obsoleto. usamos buscar
    $usuario = UsuarioController::getInstancia()->buscarUsuario(session('id_usuario'));
    foreach($usuario['usuario']->casinos as $casino){
      $casinos[]=$casino->id_casino;
    }

    $formulas=Formula::join('maquina', 'maquina.id_formula' , '=' , 'formula.id_formula')
                      ->whereIn('maquina.id_casino',$casinos)
                      ->get();

   UsuarioController::getInstancia()->agregarSeccionReciente('Formulas' , 'formulas');

    return view('seccionFormulas' , ['formulas' => $formulas]);
  }

  public function obtenerFormula($id){
    $formula=Formula::find($id);
    $arreglo = array();
    $arreglo[0]=['contador' => $formula->cont1, 'operador' => $formula->operador1];
    $arreglo[1]=['contador' => $formula->cont2, 'operador' => $formula->operador2];
    $arreglo[2]=['contador' => $formula->cont3, 'operador' => $formula->operador3];
    $arreglo[3]=['contador' => $formula->cont4, 'operador' => $formula->operador4];
    $arreglo[4]=['contador' => $formula->cont5, 'operador' => $formula->operador5];
    $arreglo[5]=['contador' => $formula->cont6, 'operador' => $formula->operador6];
    $arreglo[6]=['contador' => $formula->cont7, 'operador' => $formula->operador7];
    $arreglo[7]=['contador' => $formula->cont8];

    return ['formula' => $arreglo];
  }

  public function guardarFormula(Request $request){
    //por ahora los contadores solo se pueden llamar cont1,cont2...

    Validator::make($request->all(), [
      'formula.*.contador' => 'required',
      'formula.*.operador' => 'nullable|in:+,-'
    ], array(), self::$atributos)->after(function ($validator){

        $i=1;
        $cantTerminos= count($validator->getData()['formula']);
        foreach($validator->getData()['formula'] as $termino){
          if($cantTerminos!=$i && !(in_array($termino['operador'] , array("+", "-")))){
             $validator->errors()->add('formula', 'Formato Incorrecto de formula');
          }
        $i++;
        }
        if(count($validator->getData()['formula']) > 8){
               $validator->errors()->add('formula', 'Cantidad no permitida de términos');
        }

    })->validate();
    $formula= new Formula;
    $i=1;
      foreach ($request->formula as $termino) {
        switch ($i) {
            case 1: $formula->cont1=$termino['contador'];
                    $formula->operador1=$termino['operador']  ;break;
            case 2: $formula->cont2=$termino['contador'];
                    $formula->operador2=$termino['operador']  ;break;
            case 3: $formula->cont3=$termino['contador'];
                    $formula->operador3=$termino['operador']  ;break;
            case 4: $formula->cont4=$termino['contador'];
                    $formula->operador4=$termino['operador']  ;break;
            case 5: $formula->cont5=$termino['contador'];
                    $formula->operador5=$termino['operador']  ;break;
            case 6:$formula->cont6=$termino['contador'];
                    $formula->operador6=$termino['operador']  ;break;
            case 7:$formula->cont7=$termino['contador']      ;
                    $formula->operador7=$termino['operador']  ;break;
            case 8:$formula->cont8=$termino['contador']      ;break;
        }
        $i++;
      }
    $formula->save();
    // $retorno= eval('$i;'); esto es para ejecutar strings como codigo php
    return ['formula' => $formula];
  }

  public function asociarMaquinas(Request $request){
    Validator::make($request->all(), [
      'id_formula' => 'required|exists:formula,id_formula',
      'maquinas.*' => 'required|exists:maquina,id_maquina',
    ], array(), self::$atributos)->after(function ($validator){
        //$validator->getData()['descripcion'] get campo de validador

    })->validate();

    foreach ($request->maquinas as $id_maquina) {
      $maquina = Maquina::find($id_maquina);
      $maquina->id_formula = $request->id_formula;
      $maquina->save();
    }

    return ['codigo' => 200];
  }

  public function guardarFormulaConcatenada($formula){
    $unaFormula=$this->separarFormula($formula);
    $unaFormula->save();
    return $unaFormula;
  }

  public function modificarFormula(Request $request){
    $formula=Formula::find($request->id_formula);

    Validator::make($request->all(), [
      'formula.*.contador' => 'required',
      'formula.*.operador' => 'nullable|in:+,-',
      'id_formula' => 'required|exists:formula,id_formula',
    ], array(), self::$atributos)->after(function ($validator){
        //$validator->getData()['descripcion'] get campo de validador
        $i=1;
        $cantTerminos= count($validator->getData()['formula']);
        foreach($validator->getData()['formula'] as $termino){
          if($cantTerminos!=$i && !(in_array($termino['operador'] , array("+", "-")))){
             $validator->errors()->add('formula', 'Formato Incorrecto de formula');
          }
        $i++;
        }
        if(count($validator->getData()['formula']) > 8){
               $validator->errors()->add('formula', 'Cantidad no permitida de términos');
        }

    })->validate();


    $formula= Formula::find($request->id_formula);

    foreach($this->arreglo as $campo)
    {
        $formula->{$campo} = null;
    }

    $i=1;
    foreach ($request->formula as $termino) {
      switch ($i) {
          case 1: $formula->cont1=$termino['contador'];
                  $formula->operador1=$termino['operador']  ;break;
          case 2: $formula->cont2=$termino['contador'];
                  $formula->operador2=$termino['operador']  ;break;
          case 3: $formula->cont3=$termino['contador'];
                  $formula->operador3=$termino['operador']  ;break;
          case 4: $formula->cont4=$termino['contador'];
                  $formula->operador4=$termino['operador']  ;break;
          case 5: $formula->cont5=$termino['contador'];
                  $formula->operador5=$termino['operador']  ;break;
          case 6:$formula->cont6=$termino['contador'];
                  $formula->operador6=$termino['operador']  ;break;
          case 7:$formula->cont7=$termino['contador'];
                  $formula->operador7=$termino['operador']  ;break;
          case 8:$formula->cont8=$termino['contador']      ;break;
      }
      $i++;
    }
    $formula->save();
    // $retorno= eval('$i;'); esto es para ejecutar strings como codigo php
    return ['formula' => $formula];

  }

  public function eliminarFormula($id){
    $formula=Formula::findorfail($id);
    // if($formula->maquinas == 0  ){
    //   $MTMController=MTMController::getInstancia();
    //   $MTMController->desasociarFormula($formula->id_formula);
    // }
    $formula->delete();
    return ['formula' => $formula];
  }

  public function buscarFormula(Request $request){
    $usuario = UsuarioController::getInstancia()->buscarUsuario(session('id_usuario'));
    foreach($usuario['usuario']->casinos as $casino){
      $casinos[]=$casino->id_casino;
    }
    $reglas=array();
    if(!empty($request->buscadorNombre))
      $reglas[]=['nombre', 'like', '%'.$request->usuario.'%'];
    if(!empty($request->tabla)){
      $auxiliar=$this->separarFormula($request->tabla);
      for ($i=0; $i <count($auxiliar['attributes']) ; $i++) {
        if ($i % 2 == 0){
            $reglas[]=['cont' . (1 + $i) , 'like', '%'.$auxiliar['attributes']['cont' . ($i + 1)].'%'];
        }else{
            $reglas[]=['operador' . (1 + $i) , 'like', '%'.$auxiliar['attributes']['operador' . (1 + $i)].'%'];
        }
      }
    }

    $sort_by = $request->sort_by;
    $resultados=DB::table('formula')
                    ->select('formula.*')
                    ->join('maquina' , 'maquina.id_formula' , '=' , 'formula.id_formula')
                    ->where($reglas)->whereIn('maquina.id_casino',$casinos)
                    ->groupBy('formula.id_formula')
                    ->when($sort_by,function($query) use ($sort_by){
                      return $query->orderBy($sort_by['columna'],$sort_by['orden']);
                    })
                    ->paginate($request->page_size);

    return $resultados;
  }

  public function buscarPorCampos($input){
    $inputFormula = str_replace(' ', '', $input);
    $formulas=Formula::all();
    $resultados=array();
    foreach ($formulas as $unaFormula) {

        $formulaDB=  $unaFormula->cont1 . $unaFormula->operador1 .
                     $unaFormula->cont2 . $unaFormula->operador2 .
                     $unaFormula->cont3 . $unaFormula->operador3 .
                     $unaFormula->cont4 . $unaFormula->operador4 .
                     $unaFormula->cont5 . $unaFormula->operador5 .
                     $unaFormula->cont6 . $unaFormula->operador6 .
                     $unaFormula->cont7 . $unaFormula->operador7 .
                     $unaFormula->cont8;

      if(strpos(strtoupper($formulaDB),strtoupper($inputFormula))!==false){
        $auxiliar =  new \stdClass();
        $auxiliar->id_formula = $unaFormula->id_formula;
        $auxiliar->formula = $formulaDB;
        $resultados[] = $auxiliar;
      }
    }


    return['formulas' => $resultados];
  }

  public function separarFormula($formula){
    $auxiliar=preg_split('/([-+])/', $formula, -1, PREG_SPLIT_DELIM_CAPTURE);
    $unaFormula=new Formula;
    for ($i=0; $i <count($auxiliar) ; $i++) {
      switch ($i) {
        case 0:$unaFormula->cont1=$auxiliar[$i];break;
        case 1:$unaFormula->operador1=$auxiliar[$i];break;
        case 2:$unaFormula->cont2=$auxiliar[$i];break;
        case 3:$unaFormula->operador2=$auxiliar[$i];break;
        case 4:$unaFormula->cont3=$auxiliar[$i];break;
        case 5:$unaFormula->operador3=$auxiliar[$i];break;
        case 6:$unaFormula->cont4=$auxiliar[$i];break;
        case 7:$unaFormula->operador4=$auxiliar[$i];break;
        case 8:$unaFormula->cont5=$auxiliar[$i];break;
        case 9:$unaFormula->operador5=$auxiliar[$i];break;
        case 10:$unaFormula->cont6=$auxiliar[$i];break;
        case 11:$unaFormula->operador6=$auxiliar[$i];break;
        case 12:$unaFormula->cont7=$auxiliar[$i];break;
        case 11:$unaFormula->operador7=$auxiliar[$i];break;
        case 12:$unaFormula->cont8=$auxiliar[$i];break;
        default:break;
      }
    }
    return $unaFormula;
  }
}
