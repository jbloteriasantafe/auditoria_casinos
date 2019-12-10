<?php

namespace App\Http\Controllers\Autoexcluido;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Http\Controllers\UsuarioController;
use Validator;

use App\Autoexcluido\Autoexcluido;
use App\Autoexcluido\ContactoAE;
use App\Autoexcluido\EstadoAE;
use App\Autoexcluido\Encuesta;

use Illuminate\Support\Facades\DB;

class AutoexcluidoController extends Controller
{
    private static $atributos = [
    ];

    public function index(){
      $juegos =  DB::table('ae_juego_preferido')->get();
      $ocupaciones =  DB::table('ae_ocupacion')->get();
      $frecuencias = DB::table('ae_frecuencia_asistencia')->get();
      $casinos = DB::table('casino')->get();

      return view('Autoexcluidos.index', ['juegos' => $juegos, 'ocupaciones' => $ocupaciones, 'casinos' => $casinos, 'frecuencias' => $frecuencias]);
    }

    //Función para agregar un nuevo autoexluido completo.
    public function agregarAE(Request $request){
      //Validación de los datos
      Validator::make($request->datos_personales,$request->ae_estado, [
            'datos_personales.*.nro_dni' => 'required|numeric',
            'datos_personales.*.apellido' => 'required|string',
            'datos_personales.*.nombres' => 'required|string',
            'datos_personales.*.fecha_nacimiento' => 'required',
            'datos_personales.*.id_sexo' => 'required|numeric',
            'datos_personales.*.domicilio' => 'required|string',
            'datos_personales.*.nro_domicilio' => 'required|numeric',
            'datos_personales.*.nombre_localidad' => 'required|string',
            'datos_personales.*.nombre_provincia' => 'required|string',
            'datos_personales.*.telefono' => 'required|string',
            'datos_personales.*.correo' => 'required|string',
            'datos_personales.*.id_ocupacion' => 'required|numeric',
            'datos_personales.*.id_capacitacion' => 'required|numeric',
            'datos_personales.*.id_estado_civil' => 'required|numeric',
            'ae_estado.*.id_nombre_estado' => 'required',
            'ae_estado.*.id_casino' => 'required',
            'ae_estado.*.fecha_autoexlusion' => 'required',
            'ae_estado.*.fecha_vencimiento_periodo' => 'required',
            'ae_estado.*.fecha_renovacion' => 'required',
            'ae_estado.*.fecha_cierre_definitivo' => 'required',
        ], array(), self::$atributos)->after(function($validator){
          //valido que no exista importacion para el mismo día en el casino
          // $regla_carga = array();
          // $regla_carga [] =['bingo_sesion.id_casino', '=', $validator->getData()['casino']];
          // $regla_carga [] =['bingo_sesion.fecha_inicio', '=', $validator->getData()['fecha_inicio']];
          // $carga = SesionBingo::where($regla_carga)->count();
          // if($carga > 0){
          //   $validator->errors()->add('sesion_cargada','La sesion para esta fecha se encuentra cargarda.');
          // }
        })->validate();

      //creo un nuevo Autoexcluido y cargo sus datos
      $datos_personales = $request->datos_personales;
      $datos_ae = $this->cargarDatos($datos_personales);

      //id autoexluido
      $id_autoexcluido = $datos_ae->id_autoexcluido;
      //busco la id del usuario que agrega ae
      $id_usuario = UsuarioController::getInstancia()->buscarUsuario(session('id_usuario'))['usuario']->id_usuario;

      //cargo los datos de contacto
      $datos_contacto = $request->datos_contacto;
      $this->cargarContacto($datos_contacto, $id_autoexcluido);

      //cargo los datos de estado/fecha
      $datos_estado= $request->ae_estado;
      $this->cargarEstado($datos_estado, $id_usuario, $id_autoexcluido);

      // cargo la encuesta
      $datos_encuesta= $request->ae_encuesta;
      $this->cargarEncuesta($datos_encuesta, $id_autoexcluido);

      return $datos_ae->id_autoexcluido;
    }

    //Función para obtener los datos a partir de un DNI
    public function existeAE($dni){
      $autoexcluido = Autoexcluido::where('nro_dni','=',$dni);

        return $autoexcluido;
    }

    //Función para cargar los datos del ae
    protected function cargarDatos($datos_personales){
      $datos_ae = new Autoexcluido;
      $datos_ae->nro_dni = $datos_personales['nro_dni'];
      $datos_ae->apellido = $datos_personales['apellido'];
      $datos_ae->nombres = $datos_personales['nombres'];
      $datos_ae->fecha_nacimiento = $datos_personales['fecha_nacimiento'];
      $datos_ae->id_sexo = $datos_personales['id_sexo'];
      $datos_ae->domicilio = $datos_personales['domicilio'];
      $datos_ae->nro_domicilio = $datos_personales['nro_domicilio'];
      $datos_ae->nombre_localidad = $datos_personales['nombre_localidad'];
      $datos_ae->nombre_provincia = $datos_personales['nombre_provincia'];
      $datos_ae->telefono = $datos_personales['telefono'];
      $datos_ae->correo = $datos_personales['correo'];
      $datos_ae->id_ocupacion = $datos_personales['id_ocupacion'];
      $datos_ae->id_capacitacion = $datos_personales['id_capacitacion'];
      $datos_ae->id_estado_civil = $datos_personales['id_estado_civil'];
      //guardo los datos
      $datos_ae->save();

      return $datos_ae;
    }
    //Función para cargar los datos de contacto
    protected function cargarContacto($datos, $id_autoexcluido){
      //creo un nuevo contacto de ae con los datos;
      $contacto =  new ContactoAE;
      $contacto->nombre_apellido = $datos['nombre_apellido'];
      $contacto->domicilio = $datos['domicilio_vinculo'];
      $contacto->nombre_localidad = $datos['nombre_localidad_vinculo'];
      $contacto->nombre_provincia = $datos['nombre_provincia_vinculo'];
      $contacto->telefono = $datos['telefono_vinculo'];
      $contacto->vinculo = $datos['vinculo'];
      $contacto->id_autoexcluido = $id_autoexcluido;

      $contacto->save();
    }

    //Función para cargar los datos de estado / fecha
    protected function cargarEstado($datos, $id_usuario, $id_autoexcluido){
      //modifico las fechas para guardarlas en formato aaaa-mm-dd
      $fecha = explode('-', $datos['fecha_vencimiento_periodo']);
      $fecha_vencimiento = $fecha[2] . '-' . $fecha[1] . '-' . $fecha[0];

      $fecha = explode('-', $datos['fecha_renovacion']);
      $fecha_renovacion = $fecha[2] . '-' . $fecha[1] . '-' . $fecha[0];

      $fecha = explode('-', $datos['fecha_cierre_definitivo']);
      $fecha_cierre_definitivo = $fecha[2] . '-' . $fecha[1] . '-' . $fecha[0];


      //creo un nuevo etado con los datos
      $estado = new EstadoAE;
      $estado->id_casino = $datos['id_casino'];
      $estado->id_nombre_estado = $datos['id_nombre_estado'];
      $estado->fecha_ae = $datos['fecha_autoexlusion'];
      $estado->fecha_vencimiento = $fecha_vencimiento;
      $estado->fecha_renovacion = $fecha_renovacion;
      $estado->fecha_cierre_ae = $fecha_cierre_definitivo;
      $estado->id_usuario = $id_usuario;
      $estado->id_autoexcluido = $id_autoexcluido;

      $estado->save();
    }

    //Función para cargar la encuesta
    protected function cargarEncuesta($datos, $id_autoexcluido){
      //creo un nuevo etado con los datos
      $encuesta = new Encuesta;
      $encuesta->id_juego_preferido = $datos['juego_preferido'];
      $encuesta->id_frecuencia_asistencia = $datos['id_frecuencia_asistencia'];
      $encuesta->veces = $datos['veces'];
      $encuesta->tiempo_jugado = $datos['tiempo_jugado'];
      $encuesta->club_jugadores = $datos['socio_club_jugadores'];
      $encuesta->juego_responsable = $datos['juego_responsable'];
      $encuesta->recibir_informacion = $datos['recibir_informacion'];
      $encuesta->autocontrol_juego = $datos['autocontrol_juego'];
      $encuesta->medio_recibir_informacion = $datos['medio_recepcion'];
      $encuesta->como_asiste = $datos['como_asiste'];
      $encuesta->observacion = $datos['observaciones'];
      $encuesta->id_autoexcluido = $id_autoexcluido;
      $encuesta->save();
    }
}
