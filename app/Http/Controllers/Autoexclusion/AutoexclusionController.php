<?php

namespace App\Http\Controllers\Autoexclusion;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Http\Controllers\UsuarioController;
use Validator;

use App\Autoexclusion\Autoexcluido;
use App\Autoexclusion\ContactoAE;
use App\Autoexclusion\EstadoAE;
use App\Autoexclusion\Encuesta;

use Illuminate\Support\Facades\DB;

class AutoexclusionController extends Controller
{
    private static $atributos = [
    ];

    public function index(){
      $juegos =  DB::table('ae_juego_preferido')->get();
      $ocupaciones =  DB::table('ae_ocupacion')->get();
      $frecuencias = DB::table('ae_frecuencia_asistencia')->get();
      $casinos = DB::table('casino')->get();

      return view('Autoexclusion.index', ['juegos' => $juegos, 'ocupaciones' => $ocupaciones, 'casinos' => $casinos, 'frecuencias' => $frecuencias]);
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

      //creo un nuevo Autoexcluido y cargo sus datos personales
      $ae = $this->cargarDatos($request['datos_personales']);

      $id_autoexcluido = $ae->id_autoexcluido;
      $id_usuario = UsuarioController::getInstancia()->buscarUsuario(session('id_usuario'))['usuario']->id_usuario;

      //cargo los datos de contacto
      $this->cargarContacto($request['datos_contacto'], $id_autoexcluido);

      //cargo los datos de estado/fecha
      $this->cargarEstado($request['ae_estado'], $id_usuario, $id_autoexcluido);

      // cargo los datos de la encuesta
      $this->cargarEncuesta($request['ae_encuesta'], $id_autoexcluido);

      return $ae->id_autoexcluido;
    }

    //Función para obtener los datos a partir de un DNI
    public function existeAE($dni){
      $autoexcluido = Autoexcluido::where('nro_dni','=',$dni);

      return $autoexcluido;
    }

    //Función para cargar los datos del ae
    protected function cargarDatos($datos_personales){
      $ae = new Autoexcluido;
      $ae->nro_dni = $datos_personales['nro_dni'];
      $ae->apellido = $datos_personales['apellido'];
      $ae->nombres = $datos_personales['nombres'];
      $ae->fecha_nacimiento = $datos_personales['fecha_nacimiento'];
      $ae->id_sexo = $datos_personales['id_sexo'];
      $ae->domicilio = $datos_personales['domicilio'];
      $ae->nro_domicilio = $datos_personales['nro_domicilio'];
      $ae->nombre_localidad = $datos_personales['nombre_localidad'];
      $ae->nombre_provincia = $datos_personales['nombre_provincia'];
      $ae->telefono = $datos_personales['telefono'];
      $ae->correo = $datos_personales['correo'];
      $ae->id_ocupacion = $datos_personales['id_ocupacion'];
      $ae->id_capacitacion = $datos_personales['id_capacitacion'];
      $ae->id_estado_civil = $datos_personales['id_estado_civil'];

      //guardo los datos
      $ae->save();

      return $ae;
    }

    //Función para cargar los datos de contacto
    protected function cargarContacto($datos, $id_autoexcluido){
      //creo un nuevo contacto de ae con los datos;
      $c =  new ContactoAE;
      $c->nombre_apellido = $datos['nombre_apellido'];
      $c->domicilio = $datos['domicilio_vinculo'];
      $c->nombre_localidad = $datos['nombre_localidad_vinculo'];
      $c->nombre_provincia = $datos['nombre_provincia_vinculo'];
      $c->telefono = $datos['telefono_vinculo'];
      $c->vinculo = $datos['vinculo'];
      $c->id_autoexcluido = $id_autoexcluido;

      $c->save();
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
      $e = new EstadoAE;
      $e->id_casino = $datos['id_casino'];
      $e->id_nombre_estado = $datos['id_nombre_estado'];
      $e->fecha_ae = $datos['fecha_autoexlusion'];
      $e->fecha_vencimiento = $fecha_vencimiento;
      $e->fecha_renovacion = $fecha_renovacion;
      $e->fecha_cierre_ae = $fecha_cierre_definitivo;
      $e->id_usuario = $id_usuario;
      $e->id_autoexcluido = $id_autoexcluido;

      $e->save();
    }

    //Función para cargar la encuesta
    protected function cargarEncuesta($datos, $id_autoexcluido){
      //creo una nueva encuesta con los datos
      $e = new Encuesta;
      $e->id_juego_preferido = $datos['juego_preferido'];
      $e->id_frecuencia_asistencia = $datos['id_frecuencia_asistencia'];
      $e->veces = $datos['veces'];
      $e->tiempo_jugado = $datos['tiempo_jugado'];
      $e->club_jugadores = $datos['socio_club_jugadores'];
      $e->juego_responsable = $datos['juego_responsable'];
      $e->recibir_informacion = $datos['recibir_informacion'];
      $e->autocontrol_juego = $datos['autocontrol_juego'];
      $e->medio_recibir_informacion = $datos['medio_recepcion'];
      $e->como_asiste = $datos['como_asiste'];
      $e->observacion = $datos['observaciones'];
      $e->id_autoexcluido = $id_autoexcluido;
      $e->save();
    }
}
