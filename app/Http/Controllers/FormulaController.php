<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Formula;
use App\Maquina;
use Validator;


class FormulaController extends Controller
{
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

  public function buscarTodo(){
    $usuario = UsuarioController::getInstancia()->buscarUsuario(session('id_usuario'));
    UsuarioController::getInstancia()->agregarSeccionReciente('Formulas' , 'formulas');
    return view('seccionFormulas',['casinos' => $usuario['usuario']->casinos]);
  }

  private static $max_conts = 8;

  public function obtenerFormula($id){
    $formula = Formula::find($id);
    $ret = [];

    for($i = 1;$i<=self::$max_conts;$i++){
      $cont = $formula->{'cont'.$i};
      $op   = null;
      if($i < self::$max_conts){//Operadores va de 1 a $max_conts no inclusivo
        $op = $formula->{'operador'.$i};
      }
      $ret[] = ['contador' => $cont,'operador' => $op];
    }

    return ['formula' => $ret];
  }

  public function guardarFormula(Request $request){
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

    $formula = new Formula;
    $this->setearTerminos($formula,$request->formula);
    $formula->save();
    return ['formula' => $formula];
  }

  public function asociarMaquinas(Request $request){
    Validator::make($request->all(), [
      'id_formula' => 'required|exists:formula,id_formula',
      'maquinas.*' => 'required|exists:maquina,id_maquina',
    ], array(), self::$atributos)->after(function ($validator){})->validate();

    foreach ($request->maquinas as $id_maquina) {
      $maquina = Maquina::find($id_maquina);
      $maquina->id_formula = $request->id_formula;
      $maquina->save();
    }

    return ['codigo' => 200];
  }

  public function modificarFormula(Request $request){
    $formula=Formula::find($request->id_formula);

    Validator::make($request->all(), [
      'formula.*.contador' => 'required',
      'formula.*.operador' => 'nullable|in:+,-',
      'id_formula' => 'required|exists:formula,id_formula',
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

    $formula= Formula::find($request->id_formula);
    $nulos = [];
    for($i=0;$i<self::$max_conts;$i++) $nulos[] = ['contador' => null,'operador' => null];
    $this->setearTerminos($formula,$nulos);
    $this->setearTerminos($formula,$request->formula);    
    $formula->save();
    return ['formula' => $formula];
  }

  private function setearTerminos(&$formula,$terminos){
    $i=1;
    foreach ($terminos as $termino) {
      if($i > self::$max_conts) break;

      $formula->{'cont'.$i} = $termino['contador'];

      if($i < self::$max_conts){//Operadores va de 1 a $max_conts no inclusivo
        $formula->{'operador'.$i} = $termino['operador'];
      }
      
      $i++;
    }
  }

  public function eliminarFormula($id){
    $formula=Formula::findorfail($id);
    $formula->delete();
    return ['formula' => $formula];
  }

  public function buscarFormula(Request $request){
    $usuario = UsuarioController::getInstancia()->buscarUsuario(session('id_usuario'));
    foreach($usuario['usuario']->casinos as $casino){
      $casinos[]=$casino->id_casino;
    }
    $reglas=array();

    if(!empty($request->tabla)){
      $terminos = preg_split('/([-+])/', $request->tabla, -1, PREG_SPLIT_DELIM_CAPTURE);
      $c = 1;
      $o = 1;
      for ($i=0; $i <count($terminos) ; $i++) {
        if(($i % 2) == 0){
          $reglas[]=['cont'.$c,'like','%'.$terminos[$i].'%'];
          $c++;
        }
        else{
          $reglas[]=['operador'.$o,'like','%'.$terminos[$i].'%'];
          $o++;
        }
      }
    }

    $sort_by = $request->sort_by;
    $resultados=DB::table('formula')
    ->select('formula.*')
    ->leftJoin('maquina' , 'maquina.id_formula' , '=' , 'formula.id_formula')
    ->where($reglas)
    ->where(function ($q) use ($casinos){
      return $q->whereIn('maquina.id_casino',$casinos)->orWhereNull('maquina.id_casino');
    })
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
}
