<?php

namespace App\Http\Controllers\Mesas\Canon;

use Auth;
use Session;
use Illuminate\Http\Request;
use Response;
use App\Http\Controllers\Controller;
use Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Hash;

use App\Usuario;
use App\Casino;
use Carbon\Carbon;
use App\SecRecientes;
use App\Http\Controllers\RolesPermissions\RoleFinderController;

use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

use App\Mesas\Mesa;
use App\Mesas\Moneda;
use App\Mesas\ImagenesBunker;
use App\Mesas\DetalleImgBunker;
use App\Mesas\Canon;
use App\Http\Controllers\UsuarioController;

class IndexController extends Controller{
  public function __construct(){
    $this->middleware(['tiene_permiso:m_ver_seccion_canon']);
  }

  public function index(){
    $user =UsuarioController::getInstancia()->buscarUsuario(session('id_usuario'))['usuario'];
    return view ('Canon.canon',['casinos'=>$user->casinos]);
  }
}
