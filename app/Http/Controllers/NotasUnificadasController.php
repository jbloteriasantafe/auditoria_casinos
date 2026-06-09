<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\NotaIngreso;
use App\Models\Expediente;
use App\Models\Movimiento;
use App\Models\NotaTieneActivo;
use App\Models\Disposicion;
use App\Models\NotaTipoEvento;
use App\Models\NotaCategoria;
use App\Models\NotaEstado;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use App\Http\Controllers\Controller;
use App\Http\Controllers\UsuarioController;

use App\Isla; // Importar modelo Isla (Legacy)

class NotasUnificadasController extends Controller
{
    // URL base de la API del sistema online
    // PRUEBA: la API accesible/funcional desde este entorno es 10.1.121.24:8003 (responde 200).
    // 10.1.121.30:8003 devuelve 422 "Token o IP invalida" desde acá (no whitelisteado) -> por eso fallaban los juegos online.
    // EN PRODUCCIÓN esta URL debe apuntar a la API JOL de producción.
    const API_ONLINE_URL = 'http://10.1.121.24:8003/api/auditoria';
    const API_ONLINE_TOKEN = 'TokenParaJuego';
    // Otros entornos:
    //const API_ONLINE_URL = 'http://10.1.121.30:8003/api/auditoria';
    //const API_ONLINE_URL = 'http://10.1.121.24:8004/api/auditoria';

    /**
     * Obtener plataformas y juegos desde la API online (cacheado 1 hora)
     */
    private static $datosOnline = null;

    private static function obtenerDatosOnline()
    {
        if (self::$datosOnline !== null) {
            return self::$datosOnline;
        }

        try {
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, self::API_ONLINE_URL . '/plataformasYJuegos');
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_PROXY, null);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'API-Token: ' . self::API_ONLINE_TOKEN
            ]);
            $response = curl_exec($ch);
            $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            if ($code != 200 || $response === false) {
                \Log::error("API Online error: HTTP $code");
                self::$datosOnline = [];
                return [];
            }
            self::$datosOnline = json_decode($response) ?: [];
            return self::$datosOnline;
        } catch (\Exception $e) {
            \Log::error("API Online error: " . $e->getMessage());
            self::$datosOnline = [];
            return [];
        }
    }

    private static function obtenerPlataformasOnline()
    {
        return self::obtenerDatosOnline();
    }

    /**
     * Intentar formatear fecha, devuelve '' si no es válida
     */
    private static function safeDateFormat($value, $format = 'd/m/Y')
    {
        if (!$value)
            return '';
        try {
            return \Carbon\Carbon::parse($value)->format($format);
        } catch (\Exception $e) {
            return '';
        }
    }

    /**
     * Resolver nombre de casino o plataforma
     */
    public static function resolverNombreCasino($id_casino, $id_plataforma = null)
    {
        if ($id_plataforma) {
            $plataformas = self::obtenerPlataformasOnline();
            foreach ($plataformas as $p) {
                if ($p->id_plataforma == $id_plataforma) {
                    return str_replace('.bet.ar', '', $p->nombre) . ' (' . $p->codigo . ')';
                }
            }
            return 'Plataforma #' . $id_plataforma;
        }

        if ($id_casino) {
            $casino = \App\Casino::find($id_casino);
            return $casino ? $casino->nombre : 'Casino #' . $id_casino;
        }

        return '---';
    }

    /**
     * Registrar un movimiento en el expediente de la nota dada.
     * Helper de dominio: evita repetir el patrón Movimiento::create + lookup de usuario.
     * Public porque también lo usa NotasPdfAnotacionesController para trazar las anotaciones PDF.
     * Si la nota no tiene expediente o no hay sesión, no registra y devuelve false.
     */
    public static function registrarMovimiento($nota, $accion, $comentario)
    {
        if (!$nota) return false;
        $exp = $nota->expedientes()->first();
        if (!$exp) return false;

        $idUsuario = session('id_usuario');
        $usuario = $idUsuario ? \App\Usuario::find($idUsuario) : null;
        $nombre = $usuario ? $usuario->nombre : 'Usuario';

        Movimiento::create([
            'id_expediente_nota' => $exp->id,
            'id_usuario' => $idUsuario ?? 1,
            'fecha_movimiento' => \Carbon\Carbon::now(),
            'accion' => $accion,
            'comentario' => $nombre . ' ' . $comentario,
        ]);
        return true;
    }

    /**
     * Registrar un movimiento en TODAS las notas de un grupo (MKT + FISC).
     * Útil para operaciones a nivel grupo (vínculo padre/hijo, nota de aprobación):
     * el evento queda trazado en cada nota hija del grupo afectado.
     */
    public static function registrarMovimientoEnGrupo($grupo, $accion, $comentario)
    {
        if (!$grupo) return false;
        $grupo->load('notas');
        foreach ($grupo->notas as $nota) {
            self::registrarMovimiento($nota, $accion, $comentario);
        }
        return true;
    }

    /**
     * Estado binario de una MTM derivado de id_estado_maquina.
     * Activa = Ingreso (1) o Reingreso (2). Inactiva = Egreso{Definitivo,Temporal,por Intervención},
     * Inhabilitada, Eventualidad Observada. Si la máquina no tiene estado cargado -> '—'.
     */
    private static function estadoMtmBinario($idEstadoMaquina)
    {
        if ($idEstadoMaquina === null || $idEstadoMaquina === '') {
            return '—';
        }
        return in_array((int) $idEstadoMaquina, [1, 2], true) ? 'Activa' : 'Inactiva';
    }

    /**
     * Crea el transporte SMTP con credenciales propias (no usa .env)
     */
    private static function crearMailer()
    {
        $transport = new \Swift_SmtpTransport('correo.santafe.gov.ar', 587, 'tls');
        $transport->setUsername('no-reply-loteria');
        $transport->setPassword('C0ntr0l_L0t3r14');
        $transport->setTimeout(5);
        return new \Swift_Mailer($transport);
    }

    /**
     * Wrapper que llama a notificarCambioEstado recibiendo id en vez de objeto
     */
    private static function notificarCambioEstadoDiferido($idEstadoOrigen, $idEstadoDestino, $descEstadoOrigen, $descEstadoDestino, $notaId, $nombreUsuario)
    {
        $nota = NotaIngreso::find($notaId);
        if ($nota) {
            self::notificarCambioEstado($idEstadoOrigen, $idEstadoDestino, $descEstadoOrigen, $descEstadoDestino, $nota, $nombreUsuario);
        }
    }

    /**
     * Genera el HTML del mail de cambio de estado
     */
    private static function htmlCambioEstado($usuario, $nroNota, $tipoNota, $estadoAnterior, $estadoNuevo, $casino, $titulo = '')
    {
        $colorEstado = function ($estado) {
            $lower = mb_strtolower($estado);
            if (strpos($lower, 'aprobado') !== false)
                return ['bg' => '#27ae60', 'fg' => '#fff'];
            if (strpos($lower, 'observacion') !== false)
                return ['bg' => '#e74c3c', 'fg' => '#fff'];
            if (strpos($lower, 'vencido') !== false)
                return ['bg' => '#95a5a6', 'fg' => '#fff'];
            if ($estado === 'CON INFORME')
                return ['bg' => '#f0ad4e', 'fg' => '#fff'];
            if ($estado === 'CON INFORME NEGATIVO')
                return ['bg' => '#f0ad4e', 'fg' => '#000'];
            return ['bg' => '#3498db', 'fg' => '#fff'];
        };

        $colorNuevo = $colorEstado($estadoNuevo);
        $esCreacion = ($estadoAnterior === 'AL CREAR');
        $subtitulo = $esCreacion ? 'Nueva nota ingresada' : 'Notificaci&oacute;n de cambio de estado';
        $mensaje = $esCreacion
            ? 'El usuario <strong>' . e($usuario) . '</strong> ha subido una nueva nota al sistema.'
            : 'El usuario <strong>' . e($usuario) . '</strong> ha modificado el estado de una nota.';

        return '
        <div style="font-family: Arial, Helvetica, sans-serif; max-width: 600px; margin: 0 auto;">
            <!-- Header -->
            <div style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); padding: 25px 30px; border-radius: 8px 8px 0 0;">
                <h1 style="color: #ffffff; margin: 0; font-size: 20px; font-weight: 600;">
                    Sistema de Notas Unificadas
                </h1>
                <p style="color: #e0d4f7; margin: 5px 0 0; font-size: 13px;">' . $subtitulo . '</p>
            </div>

            <!-- Body -->
            <div style="background: #ffffff; padding: 30px; border-left: 1px solid #e0e0e0; border-right: 1px solid #e0e0e0;">
                <p style="color: #333; font-size: 15px; margin: 0 0 20px; line-height: 1.5;">
                    ' . $mensaje . '
                </p>

                <!-- Detalle -->
                <table style="width: 100%; border-collapse: collapse; margin-bottom: 20px;">
                    <tr>
                        <td style="padding: 10px 14px; background: #f8f9fa; border: 1px solid #e9ecef; color: #666; font-size: 13px; width: 140px;">Nota N&deg;</td>
                        <td style="padding: 10px 14px; background: #ffffff; border: 1px solid #e9ecef; font-size: 14px; font-weight: 600; color: #2c3e50;">' . e($nroNota) . '</td>
                    </tr>
                    <tr>
                        <td style="padding: 10px 14px; background: #f8f9fa; border: 1px solid #e9ecef; color: #666; font-size: 13px;">Tipo</td>
                        <td style="padding: 10px 14px; background: #ffffff; border: 1px solid #e9ecef; font-size: 14px; color: #2c3e50;">' . e($tipoNota) . '</td>
                    </tr>' .
            ($titulo ? '
                    <tr>
                        <td style="padding: 10px 14px; background: #f8f9fa; border: 1px solid #e9ecef; color: #666; font-size: 13px;">T&iacute;tulo</td>
                        <td style="padding: 10px 14px; background: #ffffff; border: 1px solid #e9ecef; font-size: 14px; color: #2c3e50;">' . e($titulo) . '</td>
                    </tr>' : '') . '
                    <tr>
                        <td style="padding: 10px 14px; background: #f8f9fa; border: 1px solid #e9ecef; color: #666; font-size: 13px;">Casino / Plataforma</td>
                        <td style="padding: 10px 14px; background: #ffffff; border: 1px solid #e9ecef; font-size: 14px; color: #2c3e50;">' . e($casino) . '</td>
                    </tr>
                </table>

                <!-- Estado -->
                <div style="text-align: center; padding: 15px 0;">' .
            ($esCreacion
                ? '<span style="display: inline-block; background: ' . $colorNuevo['bg'] . '; color: ' . $colorNuevo['fg'] . '; padding: 8px 18px; border-radius: 20px; font-size: 13px; font-weight: 600;">' . e($estadoNuevo) . '</span>'
                : '<span style="display: inline-block; background: #ecf0f1; color: #7f8c8d; padding: 8px 18px; border-radius: 20px; font-size: 13px; font-weight: 600;">' . e($estadoAnterior) . '</span>
                    <span style="display: inline-block; padding: 0 12px; color: #bbb; font-size: 20px;">&rarr;</span>
                    <span style="display: inline-block; background: ' . $colorNuevo['bg'] . '; color: ' . $colorNuevo['fg'] . '; padding: 8px 18px; border-radius: 20px; font-size: 13px; font-weight: 600;">' . e($estadoNuevo) . '</span>'
            ) . '
                </div>
            </div>

            <!-- Footer -->
            <div style="background: #f8f9fa; padding: 15px 30px; border-radius: 0 0 8px 8px; border: 1px solid #e0e0e0; border-top: none;">
                <p style="color: #999; font-size: 11px; margin: 0; text-align: center;">
                    Este es un mensaje autom&aacute;tico del sistema de auditor&iacute;a. No responda a este correo.
                </p>
            </div>
        </div>';
    }



    /**
     * Envía mails de notificación según transiciones y destinatarios configurados.
     * $idEstadoOrigen: id del estado anterior (0 = al crear)
     * $idEstadoDestino: id del estado nuevo (o null, se busca por descripción)
     * $descEstadoOrigen: descripción del estado anterior (para el HTML)
     * $descEstadoDestino: descripción del estado nuevo
     * $nota: instancia de NotaIngreso
     * $nombreUsuario: nombre del usuario que hizo el cambio
     */
    private static function notificarCambioEstado($idEstadoOrigen, $idEstadoDestino, $descEstadoOrigen, $descEstadoDestino, $nota, $nombreUsuario)
    {
        try {
            \Log::info("notificarCambioEstado: origen=$idEstadoOrigen, destino=$idEstadoDestino, nota=" . $nota->id);

            // Buscar categorías que tengan esta transición configurada
            // Filtrar por id_tipo_evento: 0 = todos, o debe coincidir con el tipo_evento de la nota
            $idTipoEventoNota = (int) $nota->id_tipo_evento;
            $categorias = DB::table('nota_mail_transiciones')
                ->where('id_estado_origen', $idEstadoOrigen)
                ->where('id_estado_destino', $idEstadoDestino)
                ->where('activo', 1)
                ->where(function ($q) use ($idTipoEventoNota) {
                    $q->where('id_tipo_evento', 0)
                        ->orWhere('id_tipo_evento', $idTipoEventoNota);
                })
                ->pluck('categoria')
                ->toArray();

            \Log::info("notificarCambioEstado: categorias=" . json_encode($categorias) . " idTipoEvento=$idTipoEventoNota");
            if (empty($categorias))
                return;

            // Expandir categorías agrupadas
            $catsBuscar = [];
            foreach ($categorias as $cat) {
                if ($cat === 'auditoria') {
                    $catsBuscar[] = 'auditoria';
                    $catsBuscar[] = 'despacho';
                } elseif ($cat === 'casino') {
                    $catsBuscar[] = 'casino';
                    $catsBuscar[] = 'plataforma';
                } else {
                    $catsBuscar[] = $cat;
                }
            }

            // Armar datos de la nota
            $grupo = $nota->grupo;
            $notaCasinoId = $grupo ? $grupo->id_casino : $nota->id_casino;
            $notaPlataformaId = $grupo ? $grupo->id_plataforma : $nota->id_plataforma;

            // Buscar destinatarios, filtrando por casino/plataforma para la categoría "casino"
            $destinatarios = DB::table('nota_mail_destinatarios')
                ->where('activo', 1)
                ->whereIn('categoria', $catsBuscar)
                ->where(function ($q) use ($notaCasinoId, $notaPlataformaId) {
                    $q->where(function ($q2) {
                        // Categorías que no son casino/plataforma: siempre incluir
                        $q2->whereNotIn('categoria', ['casino', 'plataforma']);
                    })->orWhere(function ($q2) use ($notaCasinoId, $notaPlataformaId) {
                        // Categoría casino/plataforma: solo si coincide el casino o es "Todos" (null/null)
                        $q2->whereIn('categoria', ['casino', 'plataforma'])
                            ->where(function ($q3) use ($notaCasinoId, $notaPlataformaId) {
                            $q3->where(function ($q4) {
                                // "Todos": sin casino ni plataforma asignados
                                $q4->whereNull('id_casino')->whereNull('id_plataforma');
                            });
                            if ($notaCasinoId) {
                                $q3->orWhere('id_casino', $notaCasinoId);
                            }
                            if ($notaPlataformaId) {
                                $q3->orWhere('id_plataforma', $notaPlataformaId);
                            }
                        });
                    });
                })
                ->get();

            if ($destinatarios->isEmpty())
                return;

            $tipoAbrev = strtoupper($nota->tipo_rama ?: 'FISC');
            $nroNota = ($grupo ? $grupo->nro_nota : $nota->nro_nota) . '-' . ($grupo ? $grupo->anio : date('Y'));
            $tipoNota = ($tipoAbrev === 'MKT') ? 'Marketing' : 'Fiscalización';
            $casino = self::resolverNombreCasino($notaCasinoId, $notaPlataformaId);

            $titulo = $grupo ? $grupo->titulo : '';
            $html = self::htmlCambioEstado($nombreUsuario, $nroNota, $tipoNota, $descEstadoOrigen, $descEstadoDestino, $casino, $titulo);

            $emailsRaw = $destinatarios->pluck('email')->unique()->toArray();
            $emails = [];
            $emailsDescartados = [];
            foreach ($emailsRaw as $em) {
                $em = trim((string) $em);
                if (filter_var($em, FILTER_VALIDATE_EMAIL)) {
                    $emails[] = $em;
                } else {
                    $emailsDescartados[] = $em;
                }
            }
            if (!empty($emailsDescartados)) {
                \Log::warning("notificarCambioEstado: emails inválidos descartados: " . implode(', ', $emailsDescartados));
            }
            if (empty($emails)) {
                \Log::warning("notificarCambioEstado: no hay emails válidos para enviar, abortando.");
                return;
            }

            $esCreacion = ($descEstadoOrigen === 'AL CREAR');
            $subject = $esCreacion ? 'Nueva nota - ' . $nroNota : 'Cambio de estado - Nota ' . $nroNota;

            $message = (new \Swift_Message($subject))
                ->setFrom(['no-reply-loteria@santafe.gov.ar' => 'Control Sistemas Loteria'])
                ->setTo($emails)
                ->setBody($html, 'text/html');

            self::crearMailer()->send($message);

            \Log::info("MAIL ENVIADO: $nroNota [$descEstadoOrigen -> $descEstadoDestino] a " . implode(', ', $emails));
        } catch (\Exception $e) {
            \Log::error("ERROR ENVIO MAIL: " . $e->getMessage());
        }
    }

    /**
     * Display a listing of the resource.
     * Bandeja de entrada según ROL (FISC / MKT / CONCESIONARIO)
     */
    public function index(Request $request)
    {
        // LEGACY AUTHENTICATION COMPATIBILITY
        $id_usuario = session('id_usuario');
        if (!$id_usuario)
            return redirect('login');

        $usuario_data = UsuarioController::getInstancia()->buscarUsuario($id_usuario);
        $usuario = $usuario_data['usuario'];

        // $usuario = Auth::user(); // DEPRECATED for this system
        if (!$usuario) {
            return redirect('login');
        }

        // --- PERMISOS DE CASINO POR USUARIO ---
        // Superusuarios/controladores ven todo; otros solo sus casinos asignados o plataforma por rol
        // Permisos: null = sin filtro (super/admin/control)
        $casinosPermitidos = null;
        $plataformasPermitidas = null;

        // Roles que ven TODO sin filtro de casino: superusuario, auditor, despacho
        // Administradores, casinos, plataformas y funcionarios: solo ven sus casinos asignados
        $sinFiltroCasino = $usuario->es_superusuario || $usuario->es_auditor || $usuario->es_despacho;

        if (!$sinFiltroCasino) {
            // Buscar todos los roles CARGA_NOTAS_* del usuario
            $rolesNotas = DB::table('usuario_tiene_rol')
                ->join('rol', 'rol.id_rol', '=', 'usuario_tiene_rol.id_rol')
                ->where('usuario_tiene_rol.id_usuario', $id_usuario)
                ->where('rol.descripcion', 'LIKE', 'CARGA\_NOTAS\_%')
                ->pluck('rol.descripcion')
                ->toArray();

            // CARGA_NOTAS_UNIFICADAS = acceso a casinos físicos asignados
            $tieneCasinosFisicos = in_array('CARGA_NOTAS_UNIFICADAS', $rolesNotas);

            // Otros roles CARGA_NOTAS_* = acceso a plataformas (BPLAY, CCO, etc.)
            $rolesPlataforma = array_filter($rolesNotas, function ($r) {
                return $r !== 'CARGA_NOTAS_UNIFICADAS';
            });

            $casinosPermitidos = [];
            if ($tieneCasinosFisicos) {
                $casinosPermitidos = DB::table('usuario_tiene_casino')
                    ->where('id_usuario', $id_usuario)
                    ->pluck('id_casino')
                    ->toArray();
            }

            $plataformasPermitidas = [];
            if (!empty($rolesPlataforma)) {
                $plataformasOnlineData = self::obtenerPlataformasOnline();
                foreach ($rolesPlataforma as $rolDesc) {
                    $codigo = str_replace('CARGA_NOTAS_', '', $rolDesc);
                    foreach ($plataformasOnlineData as $p) {
                        if ($p->codigo === $codigo) {
                            $plataformasPermitidas[] = $p->id_plataforma;
                            break;
                        }
                    }
                }
            }

            // Fallback para administradores/control sin rol CARGA_NOTAS_*:
            // usar sus casinos asignados en usuario_tiene_casino.
            if (empty($rolesNotas) && ($usuario->es_administrador || $usuario->es_control)) {
                $casinosPermitidos = DB::table('usuario_tiene_casino')
                    ->where('id_usuario', $id_usuario)
                    ->pluck('id_casino')
                    ->toArray();
            }
        }

        // Detectar roles de funcionario (tienen prioridad, incluso sobre superusuario)
        $esFuncionario1 = $usuario->tieneRol('FUNCIONARIO_1');
        $esFuncionario2 = $usuario->tieneRol('FUNCIONARIO_2');
        $esFuncionario = $esFuncionario1 || $esFuncionario2;
        $esAdministradorRol = $usuario->es_administrador && !$esFuncionario;

        // rolVista determina el filtro por defecto
        // funcionario1 = solo MKT, funcionario2 = Contratos + CON INFORME NEGATIVO, administrador = FISC + compartir_admin MKT, all = todo
        if ($esFuncionario1) {
            $rolVista = 'funcionario1';
        } elseif ($esFuncionario2) {
            $rolVista = 'funcionario2';
        } elseif ($esAdministradorRol) {
            $rolVista = 'administrador';
        } else {
            $rolVista = 'all';
        }

        // "Ver todo" desactiva filtros de rol
        $verTodo = $request->has('ver_todo') && $request->ver_todo == '1';

        // Mostrar botón "Ver todo" solo a administradores y funcionarios
        $muestraVerTodo = $esFuncionario || $esAdministradorRol;

        // Obtener Grupos de Trámite (con notas hijas)
        $gruposQuery = \App\Models\GrupoTramite::with(['notas', 'notas.expedientes', 'casino']);

        // Filtrar por casinos/plataformas permitidos del usuario
        if ($casinosPermitidos !== null || $plataformasPermitidas !== null) {
            $hayCasinos = !empty($casinosPermitidos);
            $hayPlataformas = !empty($plataformasPermitidas);
            if (!$hayCasinos && !$hayPlataformas) {
                $gruposQuery->whereRaw('0 = 1');
            } else {
                $gruposQuery->where(function ($q) use ($casinosPermitidos, $plataformasPermitidas, $hayCasinos, $hayPlataformas) {
                    if ($hayCasinos)
                        $q->orWhereIn('id_casino', $casinosPermitidos);
                    if ($hayPlataformas)
                        $q->orWhereIn('id_plataforma', $plataformasPermitidas);
                });
            }
        }

        // Filtros por rol (se omiten si "ver_todo" está activo)
        if (!$verTodo) {
            if ($rolVista === 'funcionario1') {
                // Funcionario 1: solo grupos con notas MKT
                $gruposQuery->whereHas('notas', function ($q) {
                    $q->where('tipo_rama', 'MKT');
                });
            } elseif ($rolVista === 'funcionario2') {
                // Funcionario 2: notas con tipo_evento o categoria "Contratos" O estado "CON INFORME NEGATIVO"
                $gruposQuery->where(function ($q) {
                    $q->whereHas('notas', function ($q2) {
                        $q2->where('id_tipo_evento', 6)  // Contratos (tipo_evento MKT)
                            ->orWhere('id_categoria', 3);  // Contratos (categoria MKT)
                    })->orWhereHas('notas.expedientes', function ($q2) {
                        $q2->where('estado_actual', 'CON INFORME NEGATIVO');
                    });
                });
            } elseif ($rolVista === 'administrador') {
                // Administrador: notas FISC + MKT con compartir_administrador
                $gruposQuery->whereHas('notas', function ($q) {
                    $q->where('tipo_rama', 'FISC')
                        ->orWhere(function ($q2) {
                            $q2->where('tipo_rama', 'MKT')
                                ->where('compartir_administrador', 1);
                        });
                });
            }
            // rolVista === 'all': sin filtro de rol
        }

        // Search - buscar en datos del grupo y en las notas hijas
        if ($request->has('q') && !empty($request->q)) {
            $q = $request->q;
            // El borrador es privado para roles privilegiados; solo ellos lo buscan/ven.
            $verBorrador = $usuario->es_superusuario || $usuario->es_administrador || $usuario->es_auditor || $usuario->es_despacho;
            $gruposQuery->where(function ($sub) use ($q, $verBorrador) {
                $sub->where('nro_nota', 'LIKE', "%$q%")
                    ->orWhere('titulo', 'LIKE', "%$q%")
                    ->orWhereIn('id', function ($subq) use ($q) {
                        $subq->select('id_grupo')
                            ->from('grupo_notas_aprobacion')
                            ->where('numero_documento', 'LIKE', "%$q%");
                    });
                // También busca en el "borrador" (anotación) de cada nota hija
                if ($verBorrador) {
                    $sub->orWhereHas('notas', function ($qn) use ($q) {
                        $qn->where('borrador', 'LIKE', "%$q%");
                    });
                }
            });
        }

        // Filters — soportan selección múltiple (arrays) o valor único
        $idCasinoFiltro = array_values(array_filter((array) $request->input('id_casino', []), 'strlen'));
        $idPlataformaFiltro = array_values(array_filter((array) $request->input('id_plataforma', []), 'strlen'));
        if (!empty($idCasinoFiltro) || !empty($idPlataformaFiltro)) {
            $gruposQuery->where(function ($q) use ($idCasinoFiltro, $idPlataformaFiltro) {
                if (!empty($idCasinoFiltro))
                    $q->orWhereIn('id_casino', $idCasinoFiltro);
                if (!empty($idPlataformaFiltro))
                    $q->orWhereIn('id_plataforma', $idPlataformaFiltro);
            });
        }
        // Rama (MKT / FISC) — múltiple
        $ramaFiltro = array_values(array_filter((array) $request->input('rama', []), 'strlen'));
        if (!empty($ramaFiltro)) {
            $gruposQuery->whereHas('notas', function ($q) use ($ramaFiltro) {
                $q->whereIn('tipo_rama', $ramaFiltro);
            });
        }
        // Estado del expediente — múltiple
        $estadoFiltro = array_values(array_filter((array) $request->input('estado', []), 'strlen'));
        if (!empty($estadoFiltro)) {
            $gruposQuery->whereHas('notas.expedientes', function ($q) use ($estadoFiltro) {
                $q->whereIn('estado_actual', $estadoFiltro);
            });
        }
        // Rango de fechas (fecha de carga)
        if ($request->has('fecha_desde') && !empty($request->fecha_desde)) {
            $gruposQuery->whereDate('created_at', '>=', $request->fecha_desde);
        }
        if ($request->has('fecha_hasta') && !empty($request->fecha_hasta)) {
            $gruposQuery->whereDate('created_at', '<=', $request->fecha_hasta);
        }

        // Quick Filters
        if ($request->has('quick_filter')) {
            if ($request->quick_filter === 'hoy') {
                // Grupos que tienen alguna nota creada hoy
                $gruposQuery->whereHas('notas', function ($q) {
                    $q->whereDate('created_at', \Carbon\Carbon::today());
                });
            }
            if ($request->quick_filter === 'proximos') {
                // Grupos con alguna nota (MKT o FISC) con fecha_pretendida_aprobacion >= hoy
                $hoy = \Carbon\Carbon::today()->toDateString();
                $gruposQuery->whereHas('notas', function ($q) use ($hoy) {
                    $q->whereDate('fecha_pretendida_aprobacion', '>=', $hoy);
                });
                $gruposQuery->orderByRaw('(
                    SELECT MIN(ni.fecha_pretendida_aprobacion)
                    FROM notas_ingreso ni
                    WHERE ni.id_grupo = grupos_tramites.id
                      AND ni.fecha_pretendida_aprobacion >= ?
                ) ASC', [$hoy]);
            }
            if ($request->quick_filter === 'por_vencer') {
                // Grupos con fecha estimada de aprobación (MKT) O fecha propuesta de realización (FISC)
                // entre hoy y hoy+7 días (cualquier estado).
                $hoy = \Carbon\Carbon::today()->toDateString();
                $en7dias = \Carbon\Carbon::today()->addDays(7)->toDateString();
                $gruposQuery->whereHas('notas', function ($q) use ($hoy, $en7dias) {
                    $q->where(function ($w) use ($hoy, $en7dias) {
                        $w->whereDate('fecha_pretendida_aprobacion', '>=', $hoy)
                            ->whereDate('fecha_pretendida_aprobacion', '<=', $en7dias);
                    })->orWhere(function ($w) use ($hoy, $en7dias) {
                        $w->whereDate('fecha_propuesta_realizacion', '>=', $hoy)
                            ->whereDate('fecha_propuesta_realizacion', '<=', $en7dias);
                    });
                });
                $gruposQuery->orderByRaw('LEAST(
                    COALESCE((SELECT MIN(ni.fecha_pretendida_aprobacion) FROM notas_ingreso ni
                        WHERE ni.id_grupo = grupos_tramites.id AND ni.fecha_pretendida_aprobacion >= ? AND ni.fecha_pretendida_aprobacion <= ?), \'9999-12-31\'),
                    COALESCE((SELECT MIN(ni.fecha_propuesta_realizacion) FROM notas_ingreso ni
                        WHERE ni.id_grupo = grupos_tramites.id AND ni.fecha_propuesta_realizacion >= ? AND ni.fecha_propuesta_realizacion <= ?), \'9999-12-31\')
                ) ASC', [$hoy, $en7dias, $hoy, $en7dias]);
            }
        }

        // Sorting (no aplicar si ya se ordenó por quick_filter con orden propio)
        if (!$request->has('quick_filter') || !in_array($request->quick_filter, ['proximos', 'por_vencer'])) {
            $sort = $request->get('sort_by', 'id');
            $order = $request->get('order', 'desc');
            if (!in_array($order, ['asc', 'desc']))
                $order = 'desc';

            // Columnas que están en notas_ingreso, no en grupos_tramites
            if ($sort === 'fecha_pretendida_aprobacion' || $sort === 'fecha_propuesta_realizacion') {
                $col = $sort; // ambos son literales whitelisteados (columnas de notas_ingreso)
                $gruposQuery->orderByRaw('(
                    SELECT MIN(ni.' . $col . ')
                    FROM notas_ingreso ni
                    WHERE ni.id_grupo = grupos_tramites.id
                ) ' . $order);
            } else {
                // Validar columnas permitidas de grupos_tramites
                $columnasPermitidas = ['id', 'created_at', 'nro_nota', 'id_casino', 'anio', 'titulo', 'tipo_solicitud'];
                if (!in_array($sort, $columnasPermitidas))
                    $sort = 'id';
                $gruposQuery->orderBy($sort, $order);
            }
        }

        $pageSize = $request->page_size ?: 10;
        $grupos = $gruposQuery->paginate($pageSize);

        // Preserve params
        $grupos->appends($request->all());

        // Cargar notas de aprobación para los grupos de esta página
        $grupoIds = $grupos->pluck('id')->toArray();
        $aprobacionesPorGrupo = [];
        if (!empty($grupoIds)) {
            $aprobs = DB::table('grupo_notas_aprobacion')
                ->whereIn('id_grupo', $grupoIds)
                ->orderBy('created_at', 'desc')
                ->get();
            foreach ($aprobs as $a) {
                $aprobacionesPorGrupo[$a->id_grupo][] = $a;
            }
        }

        // Notas sueltas sin grupo (legacy) - limitadas para no traer todo
        $notasSueltasQuery = NotaIngreso::with(['casino', 'expedientes'])
            ->whereNull('id_grupo');
        if ($casinosPermitidos !== null || $plataformasPermitidas !== null) {
            $hayCasinos = !empty($casinosPermitidos);
            $hayPlataformas = !empty($plataformasPermitidas);
            if (!$hayCasinos && !$hayPlataformas) {
                $notasSueltasQuery->whereRaw('0 = 1');
            } else {
                $notasSueltasQuery->where(function ($q) use ($casinosPermitidos, $plataformasPermitidas, $hayCasinos, $hayPlataformas) {
                    if ($hayCasinos)
                        $q->orWhereIn('id_casino', $casinosPermitidos);
                    if ($hayPlataformas)
                        $q->orWhereIn('id_plataforma', $plataformasPermitidas);
                });
            }
        }
        $notasSueltas = $notasSueltasQuery->orderBy('id', 'desc')->limit(50)->get();


        // Funcionario NO puede eliminar; los demás roles admin sí
        $puedeEliminar = !$esFuncionario && ($usuario->es_superusuario || $usuario->es_administrador || $usuario->es_auditor || $usuario->es_despacho || $usuario->es_control);

        // Los roles administrador NO pueden eliminar notas (sí adjuntos / comentarios)
        $puedeEliminarNotas = $puedeEliminar && !$usuario->es_administrador;

        // Comentarios: visibles para todos MENOS casinos/plataformas (regular sin rol admin)
        $puedeVerComentarios = true; // Los comentarios de las notas son visibles para todos (pedido del usuario)

        // Editar el "borrador" (anotaciones rápidas inline por nota hija) lo pueden hacer
        // solo superusuario / administrador / auditor / despacho. Control queda excluido por pedido.
        $puedeEditarBorrador = $usuario->es_superusuario || $usuario->es_administrador || $usuario->es_auditor || $usuario->es_despacho;

        // Nivel de permisos para cambio de estado: funcionario tiene prioridad sobre admin
        $esAdmin = $usuario->es_superusuario || $usuario->es_administrador || $usuario->es_auditor || $usuario->es_despacho || $usuario->es_control;

        // ¿Tiene algún rol CARGA_NOTAS_*? (casino físico o plataforma online)
        $tieneRolCargaNotas = DB::table('usuario_tiene_rol')
            ->join('rol', 'rol.id_rol', '=', 'usuario_tiene_rol.id_rol')
            ->where('usuario_tiene_rol.id_usuario', $id_usuario)
            ->where('rol.descripcion', 'LIKE', 'CARGA\_NOTAS\_%')
            ->exists();
        // Concesionario/casino: carga notas pero NO es staff interno ni funcionario.
        // Habilitado a editar/borrar SUS notas mientras estén en estado CARGA INICIAL.
        $esConcesionario = $tieneRolCargaNotas && !$esAdmin && !$esFuncionario;

        // Administrador "puro": en la tabla ve "Fecha propuesta de realización" (FISC)
        // en lugar de "Fecha Est. Aprob." (MKT).
        $esAdministrador = $usuario->es_administrador;

        if ($esFuncionario1) {
            $nivelEstado = 'funcionario1';
        } elseif ($esFuncionario2) {
            $nivelEstado = 'funcionario2';
        } elseif ($esAdmin) {
            $nivelEstado = 'admin';
        } else {
            $nivelEstado = 'regular';
        }

        if ($request->ajax()) {
            if ($request->get('view_mode') === 'kanban') {
                return response()->json([
                    'html' => view('Unified.kanban_notas', compact('grupos', 'notasSueltas', 'puedeEliminar'))->render(),
                    'total' => $grupos->total(),
                ]);
            }
            return response()->json([
                'html' => view('Unified.tabla_notas', compact('grupos', 'notasSueltas', 'puedeEliminar', 'puedeEliminarNotas', 'esFuncionario', 'esFuncionario1', 'esFuncionario2', 'rolVista', 'verTodo', 'aprobacionesPorGrupo', 'puedeEditarBorrador', 'esConcesionario', 'esAdministrador'))->render(),
                'total' => $grupos->total(),
            ]);
        }

        // Casinos físicos
        $casinos = \App\Casino::all()->map(function ($c) {
            $c->es_plataforma = false;
            return $c;
        });

        // Plataformas online (desde API)
        $plataformasOnline = self::obtenerPlataformasOnline();
        $casinos_online = collect($plataformasOnline)->map(function ($p) {
            $c = new \stdClass();
            $c->id_casino = null;
            $c->id_plataforma = $p->id_plataforma;
            $c->nombre = str_replace('.bet.ar', '', $p->nombre) . ' (' . $p->codigo . ')';
            $c->codigo = $p->codigo;
            $c->es_plataforma = true;
            return $c;
        });
        $casinos = $casinos->concat($casinos_online);

        // Filtrar dropdown según permisos del usuario
        if ($casinosPermitidos !== null || $plataformasPermitidas !== null) {
            $casinos = $casinos->filter(function ($c) use ($casinosPermitidos, $plataformasPermitidas) {
                if (!$c->es_plataforma && $casinosPermitidos !== null) {
                    return in_array($c->id_casino, $casinosPermitidos);
                }
                if ($c->es_plataforma && $plataformasPermitidas !== null) {
                    return in_array($c->id_plataforma, $plataformasPermitidas);
                }
                return false;
            })->values();
        }

        // Tipos de evento y categorías desde BD
        $categorias = NotaCategoria::activasPorRama();
        $tipos_evento = NotaTipoEvento::activosPorRama();

        $estados = NotaEstado::activos();

        // Tipos de evento separados para el modal de mails
        $tiposEventoMkt = DB::table('nota_tipos_evento')->where('activo', 1)->where('tipo_tarea', 'MKT')->orderBy('descripcion')->get();
        $tiposEventoFisc = DB::table('nota_tipos_evento')->where('activo', 1)->where('tipo_tarea', 'FISC')->orderBy('descripcion')->get();

        $totalGrupos = $grupos->total();

        // Retornar vista principal (Bandejas)
        $esAdminMails = $esAdmin;

        // Puede gestionar mails: admin o cualquier rol CARGA_NOTAS_* (calculado arriba)
        $puedeGestionarMails = $esAdmin || $tieneRolCargaNotas;

        // Puede exportar: admin o funcionario
        $puedeExportar = $esAdmin || $esFuncionario;

        return view('Unified.index', compact('grupos', 'notasSueltas', 'casinos', 'categorias', 'tipos_evento', 'estados', 'puedeEliminar', 'puedeEliminarNotas', 'nivelEstado', 'esFuncionario', 'esFuncionario1', 'esFuncionario2', 'rolVista', 'muestraVerTodo', 'verTodo', 'totalGrupos', 'tiposEventoMkt', 'tiposEventoFisc', 'aprobacionesPorGrupo', 'puedeVerComentarios', 'esAdminMails', 'puedeGestionarMails', 'puedeExportar', 'puedeEditarBorrador', 'esConcesionario', 'esAdministrador'));
    }

    /**
     * Exportar listado en PDF o Excel
     */
    public function exportar(Request $request)
    {
        // Reusar la misma lógica de filtros del index
        $request->merge(['export' => '1']);
        // Forzar sin paginación: traemos todos
        $id_usuario = session('id_usuario');
        $usuario = UsuarioController::getInstancia()->buscarUsuario($id_usuario)['usuario'];

        // Exportar solo para admin / superusuario / auditor / funcionario
        $puedeExportar = $usuario->es_superusuario || $usuario->es_administrador
            || $usuario->es_auditor || $usuario->es_despacho || $usuario->es_control
            || $usuario->tieneRol('FUNCIONARIO_1') || $usuario->tieneRol('FUNCIONARIO_2');
        if (!$puedeExportar) {
            return response()->json(['success' => false, 'msg' => 'No tiene permisos para exportar'], 403);
        }

        $sinFiltroCasino = $usuario->es_superusuario || $usuario->es_auditor || $usuario->es_despacho;
        $casinosPermitidos = null;
        $plataformasPermitidas = null;

        if (!$sinFiltroCasino) {
            $rolesNotas = DB::table('usuario_tiene_rol')
                ->join('rol', 'rol.id_rol', '=', 'usuario_tiene_rol.id_rol')
                ->where('usuario_tiene_rol.id_usuario', $id_usuario)
                ->where(function ($q) {
                    $q->where('rol.descripcion', 'CARGA_NOTAS')
                        ->orWhere('rol.descripcion', 'LIKE', 'CARGA\_NOTAS\_%');
                })
                ->pluck('rol.descripcion')->toArray();

            $tieneCasinosFisicos = in_array('CARGA_NOTAS', $rolesNotas);
            $rolesPlataforma = array_filter($rolesNotas, function ($r) {
                return $r !== 'CARGA_NOTAS';
            });

            $casinosPermitidos = [];
            if ($tieneCasinosFisicos) {
                $casinosPermitidos = DB::table('usuario_tiene_casino')->where('id_usuario', $id_usuario)->pluck('id_casino')->toArray();
            }
            $plataformasPermitidas = [];
            if (!empty($rolesPlataforma)) {
                $plataformasOnlineData = self::obtenerPlataformasOnline();
                foreach ($rolesPlataforma as $rolDesc) {
                    $codigo = str_replace('CARGA_NOTAS_', '', $rolDesc);
                    foreach ($plataformasOnlineData as $p) {
                        if ($p->codigo === $codigo) {
                            $plataformasPermitidas[] = $p->id_plataforma;
                            break;
                        }
                    }
                }
            }
            if (!$tieneCasinosFisicos && empty($rolesPlataforma)) {
                $casinosPermitidos = DB::table('usuario_tiene_casino')->where('id_usuario', $id_usuario)->pluck('id_casino')->toArray();
            }
        }

        $esFuncionario1 = $usuario->tieneRol('FUNCIONARIO_1');
        $esFuncionario2 = $usuario->tieneRol('FUNCIONARIO_2');
        $esFuncionario = $esFuncionario1 || $esFuncionario2;
        $verTodo = $request->has('ver_todo') && $request->ver_todo == '1';

        if ($esFuncionario1)
            $rolVista = 'funcionario1';
        elseif ($esFuncionario2)
            $rolVista = 'funcionario2';
        elseif ($usuario->es_administrador && !$esFuncionario)
            $rolVista = 'administrador';
        else
            $rolVista = 'all';

        $gruposQuery = \App\Models\GrupoTramite::with(['notas', 'notas.expedientes', 'notas.expedientes.movimientos.usuario', 'casino']);

        if ($casinosPermitidos !== null || $plataformasPermitidas !== null) {
            $gruposQuery->where(function ($q) use ($casinosPermitidos, $plataformasPermitidas) {
                if (!empty($casinosPermitidos))
                    $q->orWhereIn('id_casino', $casinosPermitidos);
                if (!empty($plataformasPermitidas))
                    $q->orWhereIn('id_plataforma', $plataformasPermitidas);
            });
        }

        // Filtros de rol
        if (!$verTodo) {
            if ($rolVista === 'funcionario1') {
                $gruposQuery->whereHas('notas', function ($q) {
                    $q->where('tipo_rama', 'MKT');
                });
            } elseif ($rolVista === 'funcionario2') {
                $gruposQuery->where(function ($q) {
                    $q->whereHas('notas', function ($q2) {
                        $q2->where('id_tipo_evento', 6)->orWhere('id_categoria', 3);
                    })->orWhereHas('notas.expedientes', function ($q2) {
                        $q2->where('estado_actual', 'CON INFORME NEGATIVO');
                    });
                });
            } elseif ($rolVista === 'administrador') {
                $gruposQuery->whereHas('notas', function ($q) {
                    $q->where('tipo_rama', 'FISC')
                        ->orWhere(function ($q2) {
                            $q2->where('tipo_rama', 'MKT')->where('compartir_administrador', 1);
                        });
                });
            }
        }

        // Filtros de búsqueda
        if ($request->has('q') && !empty($request->q)) {
            $q = $request->q;
            $verBorrador = $usuario->es_superusuario || $usuario->es_administrador || $usuario->es_auditor || $usuario->es_despacho;
            $gruposQuery->where(function ($sub) use ($q, $verBorrador) {
                $sub->where('nro_nota', 'LIKE', "%$q%")
                    ->orWhere('titulo', 'LIKE', "%$q%")
                    ->orWhereIn('id', function ($subq) use ($q) {
                        $subq->select('id_grupo')->from('grupo_notas_aprobacion')->where('numero_documento', 'LIKE', "%$q%");
                    });
                if ($verBorrador) {
                    $sub->orWhereHas('notas', function ($qn) use ($q) {
                        $qn->where('borrador', 'LIKE', "%$q%");
                    });
                }
            });
        }
        // Filtros — soportan selección múltiple (arrays) o valor único
        $idCasinoFiltro = array_values(array_filter((array) $request->input('id_casino', []), 'strlen'));
        $idPlataformaFiltro = array_values(array_filter((array) $request->input('id_plataforma', []), 'strlen'));
        if (!empty($idCasinoFiltro) || !empty($idPlataformaFiltro)) {
            $gruposQuery->where(function ($q) use ($idCasinoFiltro, $idPlataformaFiltro) {
                if (!empty($idCasinoFiltro))
                    $q->orWhereIn('id_casino', $idCasinoFiltro);
                if (!empty($idPlataformaFiltro))
                    $q->orWhereIn('id_plataforma', $idPlataformaFiltro);
            });
        }
        $ramaFiltro = array_values(array_filter((array) $request->input('rama', []), 'strlen'));
        if (!empty($ramaFiltro)) {
            $gruposQuery->whereHas('notas', function ($q) use ($ramaFiltro) {
                $q->whereIn('tipo_rama', $ramaFiltro);
            });
        }
        $estadoFiltro = array_values(array_filter((array) $request->input('estado', []), 'strlen'));
        if (!empty($estadoFiltro)) {
            $gruposQuery->whereHas('notas.expedientes', function ($q) use ($estadoFiltro) {
                $q->whereIn('estado_actual', $estadoFiltro);
            });
        }
        if ($request->has('fecha_desde') && $request->fecha_desde) {
            $gruposQuery->whereDate('created_at', '>=', $request->fecha_desde);
        }
        if ($request->has('fecha_hasta') && $request->fecha_hasta) {
            $gruposQuery->whereDate('created_at', '<=', $request->fecha_hasta);
        }

        // Quick filters
        if ($request->has('quick_filter') && $request->quick_filter) {
            if ($request->quick_filter === 'hoy') {
                $gruposQuery->whereHas('notas', function ($q) {
                    $q->whereDate('created_at', \Carbon\Carbon::today());
                });
            }
            if ($request->quick_filter === 'proximos') {
                $hoy = \Carbon\Carbon::today()->toDateString();
                $gruposQuery->whereHas('notas', function ($q) use ($hoy) {
                    $q->whereDate('fecha_pretendida_aprobacion', '>=', $hoy);
                });
            }
            if ($request->quick_filter === 'por_vencer') {
                $hoy = \Carbon\Carbon::today()->toDateString();
                $en7dias = \Carbon\Carbon::today()->addDays(7)->toDateString();
                $gruposQuery->whereHas('notas', function ($q) use ($hoy, $en7dias) {
                    $q->where(function ($w) use ($hoy, $en7dias) {
                        $w->whereDate('fecha_pretendida_aprobacion', '>=', $hoy)->whereDate('fecha_pretendida_aprobacion', '<=', $en7dias);
                    })->orWhere(function ($w) use ($hoy, $en7dias) {
                        $w->whereDate('fecha_propuesta_realizacion', '>=', $hoy)->whereDate('fecha_propuesta_realizacion', '<=', $en7dias);
                    });
                });
            }
        }

        $sort = $request->get('sort_by', 'id');
        $order = $request->get('order', 'desc');
        $gruposQuery->orderBy($sort, $order);

        $grupos = $gruposQuery->get();

        // Cargar aprobaciones
        $grupoIds = $grupos->pluck('id')->toArray();
        $aprobaciones = [];
        if (!empty($grupoIds)) {
            $aprobs = DB::table('grupo_notas_aprobacion')->whereIn('id_grupo', $grupoIds)->get();
            foreach ($aprobs as $a) {
                $aprobaciones[$a->id_grupo][] = $a;
            }
        }

        // Determinar si puede ver comentarios
        $puedeVerComentarios = true; // Los comentarios de las notas son visibles para todos (pedido del usuario)

        // Construir filas — una fila por cada nota hija (MKT y FISC son filas separadas)
        $rows = [];
        $meses = [
            '01' => 'Enero',
            '02' => 'Febrero',
            '03' => 'Marzo',
            '04' => 'Abril',
            '05' => 'Mayo',
            '06' => 'Junio',
            '07' => 'Julio',
            '08' => 'Agosto',
            '09' => 'Septiembre',
            '10' => 'Octubre',
            '11' => 'Noviembre',
            '12' => 'Diciembre'
        ];

        foreach ($grupos as $grupo) {
            if ($grupo->notas->isEmpty())
                continue;

            $casinoNombre = $grupo->casino ? $grupo->casino->nombre : self::resolverNombreCasino($grupo->id_casino, $grupo->id_plataforma);

            // Notas de aprobación del grupo (filtradas por rama)
            $aprobsGrupo = isset($aprobaciones[$grupo->id]) ? $aprobaciones[$grupo->id] : [];

            foreach ($grupo->notas as $nota) {
                // Filtrar FISC para funcionario1 sin ver_todo
                if ($rolVista === 'funcionario1' && !$verTodo && $nota->tipo_rama === 'FISC')
                    continue;

                // Estado
                $estado = '';
                if ($nota->expedientes->count() > 0) {
                    $estado = $nota->expedientes->first()->estado_actual;
                }

                // Adjuntos propios de esta nota
                $adjSolicitud = $nota->path_solicitud ? basename($nota->path_solicitud) : '';
                $adjDiseno = $nota->path_diseno ? basename($nota->path_diseno) : '';
                $adjBases = $nota->path_bases ? basename($nota->path_bases) : '';
                $adjInforme = $nota->path_informe ? basename($nota->path_informe) : '';
                $adjVarios = $nota->path_varios ? basename($nota->path_varios) : '';

                // Tipo evento y categoría
                $tipoEventoNombre = $nota->id_tipo_evento ? NotaTipoEvento::nombrePorId($nota->id_tipo_evento) : '';
                $categoriaNombre = $nota->id_categoria ? NotaCategoria::nombrePorId($nota->id_categoria) : '';

                // Fecha referencia y mes
                $fechaRef = $nota->fecha_referencia;
                $mesRef = '';
                $anioRef = '';
                $fechaRefValida = false;
                if ($fechaRef) {
                    try {
                        $dr = \Carbon\Carbon::parse($fechaRef);
                        $mesRef = $meses[$dr->format('m')] ?? '';
                        $anioRef = $dr->format('Y');
                        $fechaRefValida = true;
                    } catch (\Exception $e) {
                    }
                }

                // Comentarios de esta nota (solo si puede verlos)
                $comentariosStr = '';
                if ($puedeVerComentarios) {
                    $comentarios = [];
                    foreach ($nota->expedientes as $exp) {
                        foreach ($exp->movimientos as $mov) {
                            if ($mov->accion === 'COMENTARIO' && !$mov->deleted_at) {
                                $nombreUsuario = $mov->usuario ? $mov->usuario->nombre : 'Sistema';
                                $fecha = $mov->fecha_movimiento ? (is_string($mov->fecha_movimiento) ? $mov->fecha_movimiento : $mov->fecha_movimiento->format('d/m/Y H:i')) : '';
                                $comentarios[] = $nombreUsuario . ' (' . $fecha . '): ' . $mov->comentario;
                            }
                        }
                    }
                    $comentariosStr = implode("\n", $comentarios);
                }

                // Notas de aprobación de esta rama
                $aprobStr = '';
                $aprobParts = [];
                foreach ($aprobsGrupo as $ap) {
                    if ($ap->tipo_rama === $nota->tipo_rama && $ap->numero_documento) {
                        $pref = $ap->tipo_documento === 'DISPOSICION' ? 'D' : 'N';
                        $aprobParts[] = $pref . ' ' . $ap->numero_documento . '-' . $ap->anio_documento;
                    }
                }
                $aprobStr = implode(', ', $aprobParts);

                // Fecha modificación
                $fechaModif = $nota->updated_at ? $nota->updated_at->format('d/m/Y H:i') : '';

                $rows[] = [
                    'nro_nota' => $grupo->nro_nota . '-' . $grupo->anio,
                    'tema' => $grupo->titulo,
                    'tipo_evento' => $tipoEventoNombre,
                    'origen' => $casinoNombre,
                    'fecha_recepcion' => self::safeDateFormat($nota->fecha_ingreso ?: $grupo->created_at),
                    'categoria' => $nota->tipo_rama === 'FISC' ? ($nota->tipo_solicitud ?: '') : $categoriaNombre,
                    'adj_solicitud' => $adjSolicitud,
                    'adj_diseno' => $nota->tipo_rama === 'MKT' ? $adjDiseno : $adjVarios,
                    'adj_bases' => $adjBases,
                    'adj_informe' => $adjInforme,
                    'fecha_inicio' => self::safeDateFormat($nota->fecha_inicio_evento),
                    'fecha_fin' => self::safeDateFormat($nota->fecha_fin_evento),
                    'fecha_referencia' => $fechaRefValida ? $dr->format('d/m/Y') : ($fechaRef ?: ''),
                    'mes_referencia' => $mesRef,
                    'anio' => $anioRef ?: $grupo->anio,
                    'estado' => $estado,
                    'comentarios' => nl2br(e($comentariosStr)),
                    'comentarios_raw' => $comentariosStr,
                    'notas_aprobacion' => $aprobStr,
                    'notas_relacionadas' => '',
                    'fecha_modif' => $fechaModif,
                    // Extras para display
                    'tipo_rama' => $nota->tipo_rama,
                ];
            }
        }

        $formato = $request->get('formato', 'pdf');

        if ($formato === 'excel') {
            return $this->exportarExcel($rows);
        }

        // PDF
        $pdf = \Dompdf\Dompdf::class;
        $dompdf = new $pdf();
        $html = view('Unified.export_pdf', compact('rows'))->render();
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A3', 'landscape');
        $dompdf->render();
        return $dompdf->stream('notas_unificadas_' . date('Y-m-d') . '.pdf');
    }

    private function exportarExcel($rows)
    {
        $headers = [
            'Nro de Nota',
            'Rama',
            'Tema del Evento',
            'Tipo Evento',
            'Origen',
            'Fecha Recepción',
            'Categoría',
            'Adj. Solicitud',
            'Adj. Diseño/Varios',
            'Adj. Bases y Cond.',
            'Adj. Inf. Técnico',
            'Fecha Inicio Evento',
            'Fecha Finalización',
            'Fecha Referencia',
            'Año',
            'Estado',
            'Comentarios',
            'Notas Aprobación',
            'Fecha Modif.'
        ];

        $data = [];
        foreach ($rows as $row) {
            $data[] = [
                $row['nro_nota'],
                $row['tipo_rama'],
                $row['tema'],
                $row['tipo_evento'],
                $row['origen'],
                $row['fecha_recepcion'],
                $row['categoria'],
                $row['adj_solicitud'],
                $row['adj_diseno'],
                $row['adj_bases'],
                $row['adj_informe'],
                $row['fecha_inicio'],
                $row['fecha_fin'],
                $row['fecha_referencia'],
                $row['anio'],
                $row['estado'],
                $row['comentarios_raw'],
                $row['notas_aprobacion'],
                $row['fecha_modif'],
            ];
        }

        $filename = 'notas_unificadas_' . date('Y-m-d');

        return \Excel::create($filename, function ($excel) use ($headers, $data) {
            $excel->sheet('Notas', function ($sheet) use ($headers, $data) {
                $sheet->row(1, $headers);
                $sheet->row(1, function ($row) {
                    $row->setBackground('#2c3e50');
                    $row->setFontColor('#ffffff');
                    $row->setFontWeight('bold');
                    $row->setFontSize(10);
                });
                $rowNum = 2;
                foreach ($data as $d) {
                    $sheet->row($rowNum, $d);
                    $rowNum++;
                }
                $sheet->setAutoSize(true);
                $sheet->freezeFirstRow();
            });
        })->download('xlsx');
    }

    /**
     * Store a newly created resource in storage.
     * Carga inicial del concesionario
     */
    public function store(Request $request)
    {
        \Log::info("STORE: Request Data = " . json_encode($request->all()));

        // 0. Pre-Process: If FISCALIZACION, set tipo_solicitud = EVENTO
        if ($request->tipo_tarea === 'FISCALIZACION') {
            $request->merge(['tipo_solicitud' => 'EVENTO']);
            // Merge FISC specific inputs to generic names for validation
            $request->merge([
                'id_tipo_evento' => $request->id_tipo_evento_fisc,
                'id_categoria' => null,
            ]);
        } elseif ($request->tipo_tarea === 'MARKETING') {
            // Merge MKT specific inputs to generic names for validation
            $request->merge([
                'id_tipo_evento' => $request->id_tipo_evento_mkt,
                'id_categoria' => $request->id_categoria_mkt,
            ]);
        }

        // 1. Validar request
        $rules = [
            'nro_nota' => 'required',
            'anio' => 'required|integer',
            'titulo' => 'required|string',
            'tipo_solicitud' => 'required|in:EVENTO,PUBLICIDAD',
        ];

        // Debe tener casino O plataforma (no ambos, no ninguno)
        if (!$request->id_casino && !$request->id_plataforma) {
            return response()->json([
                'success' => false,
                'msg' => 'Debe seleccionar un Casino o Plataforma'
            ], 422);
        }

        if ($request->tipo_solicitud == 'EVENTO') {
            $rules['fecha_inicio_evento'] = 'required|date';
            // Fecha fin obligatoria solo para MKT, opcional para FISC
            if ($request->tipo_tarea === 'FISCALIZACION') {
                $rules['fecha_fin_evento'] = 'nullable|date';
            } else {
                $rules['fecha_fin_evento'] = 'required|date';
            }
        }

        // Custom messages for better UX
        $messages = [
            'nro_nota.required' => 'El Número de Nota es requerido',
            'anio.required' => 'El Año es requerido',
            'anio.integer' => 'El Año debe ser un número entero',
            'titulo.required' => 'El Título es requerido',
            'tipo_solicitud.required' => 'Debe seleccionar un Tipo de Solicitud (EVENTO o PUBLICIDAD)',
            'tipo_solicitud.in' => 'El Tipo de Solicitud debe ser EVENTO o PUBLICIDAD',
            'fecha_inicio_evento.required' => 'La Fecha de Inicio es requerida para eventos',
            'fecha_fin_evento.required' => 'La Fecha de Fin es requerida para eventos',
        ];

        // Use manual validation to capture and return errors
        $validator = \Validator::make($request->all(), $rules, $messages);
        if ($validator->fails()) {
            \Log::warning("STORE VALIDATION FAILED: " . json_encode($validator->errors()->toArray()));
            return response()->json([
                'success' => false,
                'msg' => 'Error de validación: ' . implode(', ', $validator->errors()->all())
            ], 422);
        }

        // (Validación extra de EVENTO eliminada — ya no existe esa opción)

        // 0.B Buscar o Crear Grupo de Trámite
        $grupo = null;
        if ($request->has('id_grupo_existente') && $request->id_grupo_existente) {
            $grupo = \App\Models\GrupoTramite::find($request->id_grupo_existente);
        }

        if (!$grupo) {
            $q = \App\Models\GrupoTramite::where('nro_nota', $request->nro_nota)
                ->where('anio', $request->anio);
            if ($request->id_plataforma) {
                $q->where('id_plataforma', $request->id_plataforma);
            } else {
                $q->where('id_casino', $request->id_casino);
            }
            $grupo = $q->first();
        }

        // Determinar qué ramas crear basándose en tipo_tarea
        $ramasACrear = [];
        if ($request->tipo_tarea === 'MARKETING') {
            $ramasACrear = ['MKT'];
        } elseif ($request->tipo_tarea === 'FISCALIZACION') {
            $ramasACrear = ['FISC'];
        }

        // Si el grupo ya existe, verificar qué ramas faltan
        if ($grupo) {
            $ramasExistentes = $grupo->notas->pluck('tipo_rama')->toArray();
            $ramasNuevas = array_diff($ramasACrear, $ramasExistentes);

            if (empty($ramasNuevas)) {
                return response()->json([
                    'success' => false,
                    'msg' => 'Ya existen notas para las ramas solicitadas en este trámite (Nota ' . $request->nro_nota . '-' . $request->anio . ').'
                ], 422);
            }

            $ramasACrear = $ramasNuevas; // Solo crear las que faltan
            \Log::info("GRUPO EXISTENTE: Anidando ramas " . implode(',', $ramasACrear) . " bajo grupo #" . $grupo->id);
        }

        DB::beginTransaction();

        try {
            // Si no existe el grupo, crearlo
            if (!$grupo) {
                $grupo = new \App\Models\GrupoTramite();
                $grupo->nro_nota = $request->nro_nota;
                $grupo->anio = $request->anio;
                $grupo->id_casino = $request->id_plataforma ? null : $request->id_casino;
                $grupo->id_plataforma = $request->id_plataforma ?: null;
                $grupo->titulo = $request->titulo;
                $grupo->tipo_solicitud = $request->tipo_solicitud;
                $grupo->fecha_inicio_evento = $request->fecha_inicio_evento;
                $grupo->fecha_fin_evento = $request->fecha_fin_evento;
                $grupo->id_tipo_evento = $request->id_tipo_evento;
                $grupo->id_categoria = $request->id_categoria;
                if ($request->id_grupo_padre) {
                    $grupo->id_grupo_padre = $request->id_grupo_padre;
                }
                $grupo->save();

                \Log::info("GRUPO NUEVO CREADO: ID " . $grupo->id);
            }

            // Helper Closure to Create Note (child of grupo)
            $createNota = function ($tipo_solicitud, $tipo_rama) use ($request, $grupo) {
                $nota = new NotaIngreso();
                $nota->id_grupo = $grupo->id;  // FK al grupo padre
                $nota->nro_nota = $request->nro_nota; // Mismo número, no prefijos
                $nota->anio = $request->anio;
                $nota->fecha_ingreso = \Carbon\Carbon::now();
                $nota->id_casino = $request->id_plataforma ? null : $request->id_casino;
                $nota->id_plataforma = $request->id_plataforma ?: null;
                $nota->titulo = $request->titulo;
                $nota->tipo_solicitud = $tipo_solicitud;
                $nota->tipo_rama = $tipo_rama;

                // Clasificación
                $nota->id_tipo_evento = $request->id_tipo_evento;
                $nota->id_categoria = $request->id_categoria;

                // Fechas
                $nota->fecha_inicio_evento = $request->fecha_inicio_evento;
                $nota->fecha_fin_evento = $request->fecha_fin_evento;
                $nota->fecha_referencia = $request->fecha_referencia;
                if ($tipo_rama === 'MKT' && $request->fecha_pretendida_aprobacion) {
                    $nota->fecha_pretendida_aprobacion = $request->fecha_pretendida_aprobacion;
                }
                // Fecha propuesta de realización: equivalente FISC de la fecha estimada de aprobación
                if ($tipo_rama === 'FISC' && $request->fecha_propuesta_realizacion) {
                    $nota->fecha_propuesta_realizacion = $request->fecha_propuesta_realizacion;
                }
                if ($tipo_rama === 'MKT') {
                    $nota->compartir_administrador = $request->compartir_administrador ? 1 : 0;
                    $nota->involucra_juegos = $request->involucra_juegos ? 1 : 0;
                }

                $nota->save();

                // Asociar Activos
                if ($request->has('activos')) {
                    $this->procesarActivos($nota, $request->activos);
                }

                // Crear Expediente
                $exp = new \App\Models\Expediente();
                $exp->id_nota_ingreso = $nota->id;
                $exp->tipo_rama = $tipo_rama;
                $exp->estado_actual = NotaEstado::CARGA_INICIAL;
                $exp->save();

                // Movimiento Inicial
                $usuarioInicio = \App\Usuario::find(session('id_usuario'));
                $nombreInicio = $usuarioInicio ? $usuarioInicio->nombre : 'Usuario';
                $mov = new \App\Models\Movimiento();
                $mov->id_expediente_nota = $exp->id;
                $mov->id_usuario = session('id_usuario') ?? 1;
                $mov->fecha_movimiento = \Carbon\Carbon::now();
                $mov->accion = 'INICIO';
                $mov->comentario = $nombreInicio . ' realizó la carga inicial del trámite';
                $mov->save();

                return $nota;
            };

            $ids_notas = [];
            $main_nota = null;

            // Crear las notas según las ramas determinadas
            foreach ($ramasACrear as $rama) {
                $tipoSol = $request->tipo_solicitud ?: 'PUBLICIDAD';

                $nota = $createNota($tipoSol, $rama);
                $ids_notas[strtolower($rama)] = $nota->id;

                if (!$main_nota)
                    $main_nota = $nota;
            }

            DB::commit();

            // Notificar "AL CREAR" (id_estado_origen = 0)
            $idDestino = DB::table('nota_estados')->where('descripcion', NotaEstado::CARGA_INICIAL)->where('activo', 1)->value('id') ?: 0;
            $usuarioCreador = \App\Usuario::find(session('id_usuario'));
            $nombreCreador = $usuarioCreador ? $usuarioCreador->nombre : 'Usuario';
            \Log::info("MAIL AL CREAR: idDestino=$idDestino, ramas=" . implode(',', $ramasACrear) . ", ids=" . json_encode($ids_notas));
            foreach ($ramasACrear as $rama) {
                $notaCreada = NotaIngreso::find($ids_notas[strtolower($rama)]);
                \Log::info("MAIL AL CREAR NOTA: rama=$rama, notaId=" . ($notaCreada ? $notaCreada->id : 'NULL') . ", grupoId=" . ($notaCreada && $notaCreada->grupo ? $notaCreada->grupo->id : 'NULL'));
                if ($notaCreada) {
                    self::notificarCambioEstado(0, $idDestino, 'AL CREAR', NotaEstado::CARGA_INICIAL, $notaCreada, $nombreCreador);
                }
            }

            return response()->json([
                'success' => true,
                'id_grupo' => $grupo->id,
                'ids_notas' => $ids_notas,
                'nro_nota' => $grupo->nro_nota,
                'anio' => $grupo->anio,
                'titulo' => $grupo->titulo,
                'tipo_solicitud' => $request->tipo_solicitud,
                'ramas_creadas' => $ramasACrear
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error("STORE ERROR: " . $e->getMessage() . " | Trace: " . $e->getTraceAsString());
            return response()->json(['success' => false, 'mensaje' => $e->getMessage()], 500);
        }
    }

    /**
     * Visualizar el detalle de una nota con su chat
     */


    /**
     * Lógica "Explosiva" para activos
     * Si es ISLA -> Busca todas las MTMs y las guarda una por una.
     * Si es OTRO -> Lo guarda directo.
     */
    private function procesarActivos(NotaIngreso $nota, array $activos)
    {
        foreach ($activos as $activo) {
            $tipo = strtoupper($activo['tipo']);
            $id = $activo['id'];

            if ($tipo === 'ISLA') {
                // "Explotar" la isla
                $isla = Isla::with('maquinas')->find($id);
                if ($isla) {
                    foreach ($isla->maquinas as $mtm) {
                        NotaTieneActivo::create([
                            'id_nota_ingreso' => $nota->id,
                            'tipo_activo' => 'MTM', // Guardamos el átomo (Máquina)
                            'id_activo' => $mtm->id_maquina
                        ]);
                    }
                }
            } else {
                // Guardado directo (Juegos, Mesas, o MTMs individuales)
                NotaTieneActivo::create([
                    'id_nota_ingreso' => $nota->id,
                    'tipo_activo' => $tipo,
                    'id_activo' => $id
                ]);
            }
        }
    }
    /**
     * Buscar Activos (AJAX)
     */
    /**
     * Buscar Activos (AJAX)
     */
    public function buscarActivos(Request $request)
    {
        $busqueda = $request->q;
        $id_casino = $request->id_casino;
        $tipo = $request->tipo;
        $resultados = [];

        if ($tipo == 'ISLA') {
            $resultados = \App\Isla::where('id_casino', $id_casino)
                ->where('nro_isla', 'like', $busqueda . '%')
                ->with('sector')
                ->withCount('maquinas')
                ->take(20)->get()->map(function ($i) {
                    $texto = 'Isla ' . $i->nro_isla . ' (Sector ' . ($i->sector->descripcion ?? 'N/A') . ')';

                    // Structured Data
                    $data = [
                        'Nro Isla' => $i->nro_isla,
                        'Sector' => $i->sector ? $i->sector->descripcion : '-',
                        'Cant. Maquinas' => $i->maquinas_count
                    ];

                    $info = 'Cant. Maquinas: ' . $i->maquinas_count;
                    return ['id' => $i->id_isla, 'text' => $texto, 'info' => $info, 'data' => $data];
                });
        } elseif ($tipo == 'MTM') {
            $resultados = \App\Maquina::where('id_casino', $id_casino)
                ->where('nro_admin', 'like', $busqueda . '%')
                ->with(['isla.sector', 'juego_activo', 'unidad_medida', 'tipoMaquina'])
                ->take(20)->get()->map(function ($m) {
                    $texto = 'MTM ' . $m->nro_admin . ' - ' . $m->marca;

                    // Structured Data for Dynamic Columns
                    $data = [
                        'Nro Admin' => $m->nro_admin,
                        'Marca' => $m->marca,
                        'Modelo' => $m->modelo,
                        'Isla' => $m->isla ? $m->isla->nro_isla : '-',
                        'Juego' => $m->juego_activo ? $m->juego_activo->nombre_juego : '-',
                        '% Dev' => $m->obtenerPorcentajeDevolucion() ?? '-',
                        'Estado' => self::estadoMtmBinario($m->id_estado_maquina),
                    ];

                    // Keep 'info' str for search list preview, but send data for table
                    $info_str = "Isla: {$data['Isla']} | Juego: {$data['Juego']} | %Dev: {$data['% Dev']} | {$data['Estado']}";

                    return ['id' => $m->id_maquina, 'text' => $texto, 'info' => $info_str, 'data' => $data];
                });
        } elseif ($tipo == 'MESA') {
            $resultados = \App\Mesas\Mesa::where('id_casino', $id_casino)
                ->where('nro_mesa', 'like', $busqueda . '%')
                ->with(['juego', 'sector', 'moneda'])
                ->take(20)->get()->map(function ($m) {
                    $texto = 'Mesa ' . $m->nro_mesa . ' - ' . ($m->juego->nombre_juego ?? 'Sin Juego');

                    $data = [
                        'Nro Mesa' => $m->nro_mesa,
                        'Juego' => $m->juego->nombre_juego ?? '-',
                        'Sector' => $m->sector ? $m->sector->descripcion : '-',
                        'Moneda' => $m->moneda ? $m->moneda->descripcion : '-',
                        // mesa_de_panio no tiene flag de activo; las soft-deleted no aparecen en la query.
                        'Estado' => 'Activa',
                    ];

                    $info_str = "Juego: {$data['Juego']} | Sec: {$data['Sector']} | {$data['Estado']}";
                    return ['id' => $m->id_mesa_de_panio, 'text' => $texto, 'info' => $info_str, 'data' => $data];
                });
        } elseif ($tipo == 'JUEGO_ONLINE') {
            // Buscar juegos desde cache (datos de API online)
            $id_plataforma = $request->id_plataforma;
            $datos = self::obtenerDatosOnline();
            $juegos = [];
            foreach ($datos as $plat) {
                if ($plat->id_plataforma == $id_plataforma && isset($plat->juegos)) {
                    $juegos = $plat->juegos;
                    break;
                }
            }
            // Filtrar por búsqueda localmente
            $busquedaLower = mb_strtolower($busqueda);
            $filtrados = array_filter($juegos, function ($j) use ($busquedaLower) {
                return mb_strpos(mb_strtolower($j->nombre_juego), $busquedaLower) !== false
                    || mb_strpos(mb_strtolower($j->cod_juego ?? ''), $busquedaLower) !== false;
            });
            $resultados = array_map(function ($j) {
                // El endpoint /plataformasYJuegos filtra ya por estado_juego='Activo' y
                // descarta deleted_at, así que todos los juegos que llegan son activos.
                $data = [
                    'Cod Juego' => $j->cod_juego ?? '-',
                    'Juego' => $j->nombre_juego,
                    'Categoria' => $j->categoria ?? '-',
                    '% Dev' => $j->porcentaje_devolucion ?? '-',
                    'Plataforma' => ($j->escritorio ? 'PC ' : '') . ($j->movil ? 'Movil' : ''),
                    'Estado' => 'Activa',
                ];
                $info_str = "Cat: {$data['Categoria']} | %Dev: {$data['% Dev']} | Activa";
                return ['id' => $j->id_juego, 'text' => $j->nombre_juego, 'info' => $info_str, 'data' => $data];
            }, array_slice(array_values($filtrados), 0, 20));
        }

        return response()->json($resultados);
    }

    /**
     * Resolver una lista de IDs/códigos tipeados o pegados a su ID canónico (carga masiva o alta directa).
     * Acepta tanto el id como el código de cada activo; nunca guarda un valor sin resolver.
     * Devuelve { resueltos:[{valor,id,nombre}], no_encontrados:[valor], ambiguos:[{valor,opciones:[{id,nombre,cod}]}] }
     */
    public function resolverActivos(Request $request)
    {
        $tipo = strtoupper((string) $request->tipo);
        $idCasino = $request->id_casino;
        $idPlataforma = $request->id_plataforma;
        $valores = $request->valores ?: [];
        if (!is_array($valores))
            $valores = preg_split('/[\r\n,;]+/', (string) $valores); // por si llega como texto pegado

        // normalizar: trim, sin vacíos, únicos
        $valores = array_values(array_unique(array_filter(array_map(function ($v) {
            return trim((string) $v);
        }, $valores), 'strlen')));

        $resueltos = [];
        $noEncontrados = [];
        $ambiguos = [];

        $esNum = function ($v) { return preg_match('/^\d+$/', (string) $v) === 1; };

        if ($tipo === 'JUEGO_ONLINE') {
            // Juegos de la plataforma de la nota (acota colisiones id/código a esa plataforma)
            $juegos = [];
            foreach (self::obtenerDatosOnline() as $plat) {
                if ($plat->id_plataforma == $idPlataforma && isset($plat->juegos)) {
                    $juegos = $plat->juegos;
                    break;
                }
            }
            foreach ($valores as $v) {
                $matches = [];
                foreach ($juegos as $j) {
                    if (($esNum($v) && (int) $j->id_juego === (int) $v) || (string) ($j->cod_juego ?? '') === (string) $v) {
                        $matches[$j->id_juego] = ['id' => $j->id_juego, 'nombre' => $j->nombre_juego, 'cod' => $j->cod_juego ?? null];
                    }
                }
                $this->clasificarResolucion($v, array_values($matches), $resueltos, $noEncontrados, $ambiguos);
            }
        } elseif ($tipo === 'MTM') {
            foreach ($valores as $v) {
                $rows = \App\Maquina::where('id_casino', $idCasino)
                    ->where(function ($w) use ($v, $esNum) {
                        $w->where('nro_admin', $v);
                        if ($esNum($v)) $w->orWhere('id_maquina', (int) $v);
                    })->get();
                $matches = $rows->keyBy('id_maquina')->map(function ($m) {
                    return ['id' => $m->id_maquina, 'nombre' => 'MTM ' . $m->nro_admin . ' - ' . $m->marca, 'cod' => $m->nro_admin];
                })->values()->all();
                $this->clasificarResolucion($v, $matches, $resueltos, $noEncontrados, $ambiguos);
            }
        } elseif ($tipo === 'MESA') {
            foreach ($valores as $v) {
                $rows = \App\Mesas\Mesa::where('id_casino', $idCasino)
                    ->where(function ($w) use ($v, $esNum) {
                        $w->where('nro_mesa', $v);
                        if ($esNum($v)) $w->orWhere('id_mesa_de_panio', (int) $v);
                    })->with('juego')->get();
                $matches = $rows->keyBy('id_mesa_de_panio')->map(function ($m) {
                    return ['id' => $m->id_mesa_de_panio, 'nombre' => 'Mesa ' . $m->nro_mesa, 'cod' => $m->nro_mesa];
                })->values()->all();
                $this->clasificarResolucion($v, $matches, $resueltos, $noEncontrados, $ambiguos);
            }
        } elseif ($tipo === 'ISLA') {
            foreach ($valores as $v) {
                $rows = \App\Isla::where('id_casino', $idCasino)
                    ->where(function ($w) use ($v, $esNum) {
                        $w->where('nro_isla', $v);
                        if ($esNum($v)) $w->orWhere('id_isla', (int) $v);
                    })->get();
                $matches = $rows->keyBy('id_isla')->map(function ($i) {
                    return ['id' => $i->id_isla, 'nombre' => 'Isla ' . $i->nro_isla, 'cod' => $i->nro_isla];
                })->values()->all();
                $this->clasificarResolucion($v, $matches, $resueltos, $noEncontrados, $ambiguos);
            }
        }

        return response()->json([
            'success' => true,
            'resueltos' => $resueltos,
            'no_encontrados' => $noEncontrados,
            'ambiguos' => $ambiguos,
        ]);
    }

    /** Clasifica las coincidencias de un valor en resuelto / no_encontrado / ambiguo. */
    private function clasificarResolucion($valor, array $matches, array &$resueltos, array &$noEncontrados, array &$ambiguos)
    {
        if (count($matches) === 0) {
            $noEncontrados[] = $valor;
        } elseif (count($matches) === 1) {
            $resueltos[] = ['valor' => $valor, 'id' => $matches[0]['id'], 'nombre' => $matches[0]['nombre']];
        } else {
            $ambiguos[] = ['valor' => $valor, 'opciones' => $matches];
        }
    }

    public function obtenerActivosIsla($id_isla)
    {
        $mtms = \App\Maquina::where('id_isla', $id_isla)
            ->with(['isla.sector', 'juego_activo', 'unidad_medida', 'tipoMaquina'])
            ->get()->map(function ($m) {
                $texto = 'MTM ' . $m->nro_admin . ' - ' . $m->marca;

                // Format MATCHES MTM Search logic exactly
                $data = [
                    'Nro Admin' => $m->nro_admin,
                    'Marca' => $m->marca,
                    'Modelo' => $m->modelo,
                    'Isla' => $m->isla ? $m->isla->nro_isla : '-',
                    'Juego' => $m->juego_activo ? $m->juego_activo->nombre_juego : '-',
                    '% Dev' => $m->obtenerPorcentajeDevolucion() ?? '-',
                    'Estado' => self::estadoMtmBinario($m->id_estado_maquina),
                ];

                $info_str = "Isla: {$data['Isla']} | Juego: {$data['Juego']} | %Dev: {$data['% Dev']} | {$data['Estado']}";

                return ['id' => $m->id_maquina, 'text' => $texto, 'info' => $info_str, 'data' => $data, 'tipo' => 'MTM'];
            });

        return response()->json($mtms);
    }
    /**
     * Wizard Step 2: Vista de Adjuntos
     */
    public function vistaAdjuntar($id)
    {
        $nota = NotaIngreso::findOrFail($id);
        return view('Unified.wizard_step_2', compact('nota'));
    }

    /**
     * Wizard Step 2: Guardar Adjuntos
     * 
     * ESTRUCTURA DE ADJUNTOS:
     * ========================
     * COMÚN (ambas ramas):
     *   - path_solicitud: Solicitud Concesionario
     *   - path_informe: Informe Técnico (instancia posterior)
     * 
     * MKT (Marketing):
     *   - path_diseno: Diseño/Arte
     *   - path_bases: Bases y Condiciones
     * 
     * FISC (Fiscalización):
     *   - path_varios: Archivos Varios (.zip con todo)
     */
    public function guardarAdjuntos(Request $request)
    {
        \Log::info("UPLOAD: METHOD ENTRY - RAW INPUTS: " . json_encode($request->only(['id_nota_mkt', 'id_nota_fisc'])));
        $disk = 'public';

        // IDs recibidos del frontend
        $id_nota_fisc = $request->id_nota_fisc;
        $id_nota_mkt = $request->id_nota_mkt;

        \Log::info("WIZARD UPLOAD START: MKT=" . ($id_nota_mkt ?? 'null') . ", FISC=" . ($id_nota_fisc ?? 'null'));

        // Helper function to store file with original name + timestamp
        $storeFile = function ($file, $folder) use ($disk) {
            $originalName = $file->getClientOriginalName();
            $extension = $file->getClientOriginalExtension();
            $baseName = pathinfo($originalName, PATHINFO_FILENAME);
            $uniqueName = time() . '_' . preg_replace('/[^a-zA-Z0-9_-]/', '_', $baseName) . '.' . $extension;
            return $file->storeAs($folder, $uniqueName, $disk);
        };

        try {
            // ===================================================
            // ! GUARDAR ADJUNTOS MKT (Marketing)
            // ===================================================
            // Procesa un input (posiblemente con varios archivos) creando UN DOCUMENTO por archivo.
            $procesar = function ($nota, $inputName, $tipo) use ($request) {
                if (!$request->hasFile($inputName)) return;
                $files = $request->file($inputName);
                if (!is_array($files)) $files = [$files]; // soporta input simple o múltiple
                foreach ($files as $f) {
                    if ($f && $f->isValid()) {
                        $this->crearDocumentoConArchivo($nota, $tipo, $f);
                    }
                }
            };

            if ($id_nota_mkt && is_numeric($id_nota_mkt)) {
                $notaMkt = NotaIngreso::find($id_nota_mkt);
                if ($notaMkt) {
                    $procesar($notaMkt, 'adjuntoSolicitud', 'solicitud');
                    $procesar($notaMkt, 'adjuntoDisenio', 'diseno');
                    $procesar($notaMkt, 'adjuntoBases', 'bases');
                    $procesar($notaMkt, 'adjuntoInformeMkt', 'informe');
                    $procesar($notaMkt, 'adjuntoAnexosMkt', 'anexo');
                    $notaMkt->save();
                    \Log::info("MKT Files Saved for Note " . $notaMkt->id);
                }
            }

            // ===================================================
            // ! GUARDAR ADJUNTOS FISC (Fiscalización)
            // ===================================================
            if ($id_nota_fisc && is_numeric($id_nota_fisc)) {
                $notaFisc = NotaIngreso::find($id_nota_fisc);
                if ($notaFisc) {
                    $procesar($notaFisc, 'adjuntoSolicitudFisc', 'solicitud');
                    $procesar($notaFisc, 'adjuntoVarios', 'varios');
                    $procesar($notaFisc, 'adjuntoInformeFisc', 'informe');
                    $procesar($notaFisc, 'adjuntoAnexosFisc', 'anexo');
                    $notaFisc->save();
                    \Log::info("FISC Files Saved for Note " . $notaFisc->id);
                }
            }

            return response()->json(['success' => true]);

        } catch (\Throwable $e) {
            \Log::error("Error guardarAdjuntos Trace: " . $e->getTraceAsString());
            return response()->json(['success' => false, 'msg' => "Server Error: " . $e->getMessage()], 500);
        }
    }

    /**
     * Agregar adjuntos a una nota existente (por turnos)
     * Permite que MKT o FISC suban sus archivos en diferentes momentos
     */
    public function agregarAdjuntos(Request $request, $id)
    {
        $disk = 'public';
        $nota = NotaIngreso::findOrFail($id);
        $userId = session('id_usuario') ?? Auth::id() ?? 1;
        $usuarioAdj = \App\Usuario::find($userId);
        $nombreAdj = $usuarioAdj ? $usuarioAdj->nombre : 'Usuario';

        // Helper para guardar archivo
        $storeFile = function ($file, $folder) use ($disk) {
            $originalName = $file->getClientOriginalName();
            $extension = $file->getClientOriginalExtension();
            $baseName = pathinfo($originalName, PATHINFO_FILENAME);
            $uniqueName = time() . '_' . preg_replace('/[^a-zA-Z0-9_-]/', '_', $baseName) . '.' . $extension;
            return $file->storeAs($folder, $uniqueName, $disk);
        };

        // Helper para registrar movimiento
        $logMovimiento = function ($nota, $campo, $nombreArchivo, $accion = 'ADJUNTO_AGREGADO') use ($userId, $nombreAdj) {
            // Obtener o crear expediente para la nota
            $expediente = $nota->expedientes->first();
            if (!$expediente) {
                $expediente = Expediente::create([
                    'id_nota_ingreso' => $nota->id,
                    'estado_actual' => 'EN_PROCESO'
                ]);
            }

            $camposLegibles = [
                'path_solicitud' => 'Solicitud Concesionario',
                'path_diseno' => 'Diseño',
                'path_bases' => 'Bases y Condiciones',
                'path_informe' => 'Informe Técnico',
                'path_varios' => 'Archivos Varios'
            ];

            $mov = new Movimiento;
            $mov->id_expediente_nota = $expediente->id;
            $mov->id_usuario = $userId;
            $mov->fecha_movimiento = \Carbon\Carbon::now();
            $mov->accion = $accion;
            $mov->comentario = $nombreAdj . ' ' . ($accion === 'ADJUNTO_REEMPLAZADO' ? 'reemplazó' : 'agregó')
                . ' ' . ($camposLegibles[$campo] ?? $campo)
                . ': "' . $nombreArchivo . '"';
            $mov->save();
        };

        try {
            // Cada input (posiblemente con varios archivos) crea UN DOCUMENTO por archivo.
            $mapa = [
                'adjuntoSolicitud' => 'solicitud',
                'adjuntoDisenio'   => 'diseno',
                'adjuntoBases'     => 'bases',
                'adjuntoInforme'   => 'informe',
                'adjuntoVarios'    => 'varios',
                'adjuntoAnexos'    => 'anexo',
            ];

            $archivosSubidos = [];
            foreach ($mapa as $inputName => $tipoArchivo) {
                if (!$request->hasFile($inputName)) continue;
                $files = $request->file($inputName);
                if (!is_array($files)) $files = [$files]; // soporta input simple o múltiple
                foreach ($files as $file) {
                    if (!$file || !$file->isValid()) continue;
                    $doc = $this->crearDocumentoConArchivo($nota, $tipoArchivo, $file);
                    $this->logMovimientoAdjunto($nota, 'ADJUNTO_AGREGADO', $tipoArchivo, $doc->nombre, $file->getClientOriginalName());
                    $archivosSubidos[] = $file->getClientOriginalName();
                }
            }

            $nota->save();

            return response()->json([
                'success' => true,
                'msg' => count($archivosSubidos) . ' archivo(s) subido(s) correctamente',
                'archivos' => $archivosSubidos
            ]);

        } catch (\Throwable $e) {
            \Log::error("Error agregarAdjuntos: " . $e->getMessage());
            return response()->json(['success' => false, 'msg' => $e->getMessage()], 500);
        }
    }

    /**
     * Obtener historial de adjuntos/movimientos de una nota
     */
    public function getHistorialAdjuntos($id)
    {
        try {
            $nota = NotaIngreso::with('expedientes.movimientos.usuario')->findOrFail($id);

            $historial = [];
            foreach ($nota->expedientes as $exp) {
                if (!$exp->movimientos)
                    continue;

                foreach ($exp->movimientos as $mov) {
                    // Filtrar solo movimientos de adjuntos
                    $comentario = $mov->comentario ?? '';
                    $accion = $mov->accion ?? '';

                    if (strpos($accion, 'ADJUNTO') !== false || strpos($comentario, 'Agregó') !== false || strpos($comentario, 'Reemplazó') !== false) {
                        $fecha = $mov->fecha_movimiento;
                        if ($fecha && !is_string($fecha)) {
                            $fechaStr = $fecha->format('d/m/Y H:i');
                        } else {
                            $fechaStr = $fecha ?? date('d/m/Y H:i');
                        }

                        $historial[] = [
                            'fecha' => $fechaStr,
                            'usuario' => $mov->usuario->nombre ?? 'Usuario',
                            'accion' => $accion,
                            'detalle' => $comentario
                        ];
                    }
                }
            }

            // Ordenar por fecha desc
            usort($historial, function ($a, $b) {
                return strtotime($b['fecha']) - strtotime($a['fecha']);
            });

            // Include current attachment status
            $adjuntos = [
                'solicitud' => $nota->path_solicitud ? ['existe' => true, 'nombre' => basename($nota->path_solicitud)] : ['existe' => false],
                'diseno' => $nota->path_diseno ? ['existe' => true, 'nombre' => basename($nota->path_diseno)] : ['existe' => false],
                'bases' => $nota->path_bases ? ['existe' => true, 'nombre' => basename($nota->path_bases)] : ['existe' => false],
                'informe' => $nota->path_informe ? ['existe' => true, 'nombre' => basename($nota->path_informe)] : ['existe' => false],
                'varios' => $nota->path_varios ? ['existe' => true, 'nombre' => basename($nota->path_varios)] : ['existe' => false],
            ];

            return response()->json(['success' => true, 'historial' => $historial, 'adjuntos' => $adjuntos, 'nota_id' => $id]);
        } catch (\Throwable $e) {
            \Log::error("Error getHistorialAdjuntos ID={$id}: " . $e->getMessage() . " | File: " . $e->getFile() . " | Line: " . $e->getLine());

            // Return empty object (not array) so JS can check properties
            $emptyAdjuntos = [
                'solicitud' => ['existe' => false],
                'diseno' => ['existe' => false],
                'bases' => ['existe' => false],
                'informe' => ['existe' => false],
                'varios' => ['existe' => false],
            ];
            return response()->json(['success' => true, 'historial' => [], 'adjuntos' => $emptyAdjuntos, 'error' => $e->getMessage()]);
        }
    }

    /**
     * Inline Update (Quick Edit)
     */
    public function quickUpdate(Request $request)
    {
        try {
            $nota = NotaIngreso::findOrFail($request->id);
            $field = $request->field;
            $value = $request->value;

            if ($field === 'estado') {
                $exp = $nota->expedientes->first();
                if (!$exp)
                    return response()->json(['success' => false, 'msg' => 'Sin expediente'], 400);

                $estadoActual = $exp->estado_actual;
                $id_usuario = session('id_usuario');
                $usuario_data = UsuarioController::getInstancia()->buscarUsuario($id_usuario);
                $usuario = $usuario_data['usuario'];

                $esFuncionario1 = $usuario->tieneRol('FUNCIONARIO_1');
                $esFuncionario2 = $usuario->tieneRol('FUNCIONARIO_2');
                $esFuncionario = $esFuncionario1 || $esFuncionario2;
                $esAdmin = !$esFuncionario && ($usuario->es_superusuario || $usuario->es_administrador || $usuario->es_auditor || $usuario->es_despacho || $usuario->es_control);

                if ($esFuncionario1)
                    $nivel = 'funcionario1';
                elseif ($esFuncionario2)
                    $nivel = 'funcionario2';
                elseif ($esAdmin)
                    $nivel = 'admin';
                else
                    $nivel = 'regular';

                if (!NotaEstado::transicionPermitida($estadoActual, $value, $nivel)) {
                    return response()->json(['success' => false, 'msg' => 'No tiene permisos para esta transición de estado'], 403);
                }

                $exp->estado_actual = $value;
                $exp->save();

                $mov = new Movimiento;
                $mov->id_expediente_nota = $exp->id;
                $mov->id_usuario = $id_usuario;
                $mov->fecha_movimiento = \Carbon\Carbon::now();
                $mov->accion = 'MODIFICACION';
                $mov->comentario = $usuario->nombre . ' cambió estado a: ' . $value;
                $mov->save();

                // Notificar por mail
                $idOrigen = DB::table('nota_estados')->where('descripcion', $estadoActual)->where('activo', 1)->value('id') ?: 0;
                $idDestino = DB::table('nota_estados')->where('descripcion', $value)->where('activo', 1)->value('id') ?: 0;
                self::notificarCambioEstadoDiferido($idOrigen, $idDestino, $estadoActual, $value, $nota->id, $usuario->nombre);
            }

            return response()->json(['success' => true]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'msg' => $e->getMessage()], 500);
        }
    }

    /**
     * Upload Single File (Drag & Drop)
     */
    public function uploadArchivo(Request $request)
    {
        try {
            $nota = NotaIngreso::findOrFail($request->id_nota);
            $tipo = $request->tipo; // pautas, diseno, bases

            if (!$request->hasFile('file')) {
                return response()->json(['success' => false, 'msg' => 'No file provided'], 400);
            }

            $disk = 'public';
            $path = null;
            $nombreOriginal = $request->file('file')->getClientOriginalName();

            if ($tipo == 'pautas') {
                $path = $request->file('file')->store('pautas', $disk);
                $nota->path_pautas = $path;
            } elseif ($tipo == 'diseno') {
                $path = $request->file('file')->store('disenos', $disk);
                $nota->path_diseno = $path;
            } elseif ($tipo == 'bases') {
                $path = $request->file('file')->store('bases', $disk);
                $nota->path_bases = $path;
            } else {
                return response()->json(['success' => false, 'msg' => 'Invalid file type'], 400);
            }

            $nota->save();

            self::registrarMovimiento(
                $nota,
                'ADJUNTO_AGREGADO',
                'subió archivo "' . $nombreOriginal . '" (' . $tipo . ')'
            );

            return response()->json(['success' => true]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'msg' => $e->getMessage()], 500);
        }
    }

    /**
     * Get Last Movements for Tooltip
     */
    public function getMovimientos($id)
    {
        $nota = NotaIngreso::with([
            'expedientes.movimientos' => function ($q) {
                $q->orderBy('id', 'desc')->take(3);
            }
        ])->find($id);

        if (!$nota)
            return response()->json([]);

        $movs = [];
        if ($nota->expedientes->count() > 0) {
            foreach ($nota->expedientes->first()->movimientos as $m) {
                $movs[] = [
                    'fecha' => \Carbon\Carbon::parse($m->fecha_movimiento)->format('d/m H:i'),
                    'estado' => $m->accion // Or $m->estado_actual ??
                ];
            }
        }
        return response()->json($movs);
    }


    // ! DESCARGAR
    public function descargarArchivo($id, $tipo)
    {
        $nota = NotaIngreso::findOrFail($id);
        $path = null;

        switch ($tipo) {
            case 'pautas':
                $path = $nota->path_pautas;
                break;
            case 'diseno':
                $path = $nota->path_diseno;
                break;
            case 'bases':
                $path = $nota->path_bases;
                break;
        }

        if (!$path || !Storage::disk('public')->exists($path)) {
            return redirect()->back()->with('error', 'Archivo no encontrado');
        }

        // Get the full path and original filename
        $fullPath = Storage::disk('public')->getDriver()->getAdapter()->applyPathPrefix($path);
        $filename = basename($path); // Extract original filename with extension

        // Use response()->download() with the correct filename
        return response()->download($fullPath, $filename);
    }

    // ! VISUALIZAR (para mostrar PDFs en el navegador)
    public function visualizarArchivo($id, $tipo)
    {
        $nota = NotaIngreso::findOrFail($id);
        $path = null;

        switch ($tipo) {
            case 'pautas':
            case 'solicitud':
                $path = $nota->path_solicitud;
                break;
            case 'diseno':
                $path = $nota->path_diseno;
                break;
            case 'bases':
                $path = $nota->path_bases;
                break;
            case 'varios':
                $path = $nota->path_varios;
                break;
            case 'informe':
                $path = $nota->path_informe;
                break;
        }

        if (!$path || !Storage::disk('public')->exists($path)) {
            abort(404, 'Archivo no encontrado');
        }

        $fullPath = Storage::disk('public')->path($path);
        $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));

        $mimeType = mime_content_type($fullPath);

        // Debug
        // \Log::info("Visualizar archivo: Path=$path, Ext=$extension, Mime=$mimeType");

        // Si es PDF (por mime o por extension), mostrar inline
        if ($mimeType === 'application/pdf' || $extension === 'pdf') {
            return response()->file($fullPath, [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'inline; filename="' . basename($path) . '"'
            ]);
        }

        // Si es imagen, mostrar inline
        if (strpos($mimeType, 'image/') === 0) {
            return response()->file($fullPath, [
                'Content-Type' => $mimeType,
                'Content-Disposition' => 'inline; filename="' . basename($path) . '"'
            ]);
        }

        // Otros archivos: descargar
        return response()->download($fullPath, basename($path));
    }


    public function getCalendarEvents(Request $request)
    {
        // Fetch notes with dates to show in calendar
        $notas = DB::table('nota')
            ->join('expediente', 'nota.id_expediente', '=', 'expediente.id_expediente')
            ->select('nota.id_nota', 'nota.fecha', 'nota.identificacion', 'expediente.concepto', 'expediente.nro_exp_org', 'expediente.nro_exp_interno', 'expediente.nro_exp_control')
            ->limit(100) // Optimize as needed
            ->get();

        $events = [];
        foreach ($notas as $n) {
            $titulo = "Nota " . $n->identificacion;
            // Add some info
            if ($n->nro_exp_org) {
                $titulo .= " | Exp: " . $n->nro_exp_org . "-" . $n->nro_exp_interno;
            }

            $events[] = [
                'title' => $titulo,
                'start' => $n->fecha,
                'url' => 'javascript:verNota(' . $n->id_nota . ')',
                'color' => '#1976D2'
            ];
        }

        return response()->json($events);
    }

    // ! COMENTARIOS ("POST-ITS")
    public function addComment(Request $request)
    {
        $request->validate([
            'id_nota' => 'required|integer',
            'comentario' => 'required|string|max:500'
        ]);

        $usuario = UsuarioController::getInstancia()->buscarUsuario(session('id_usuario'))['usuario'];

        // Solo admin/funcionarios/auditores/despacho/superusuario pueden comentar
        $puedeComentarios = $usuario->es_superusuario || $usuario->es_administrador || $usuario->es_auditor || $usuario->es_despacho || $usuario->es_control || $usuario->tieneRol('FUNCIONARIO_1') || $usuario->tieneRol('FUNCIONARIO_2');
        if (!$puedeComentarios) {
            return response()->json(['status' => 'error', 'msg' => 'No tiene permisos para agregar comentarios'], 403);
        }

        $comentario = new \App\NotaComentario;
        $comentario->id_nota = $request->id_nota;
        $comentario->id_usuario = $usuario->id_usuario;
        $comentario->comentario = $request->comentario;
        $comentario->save();

        return response()->json([
            'status' => 'success',
            'comentario' => $comentario->load('usuario')
        ]);
    }

    public function getComments($id)
    {
        // Solo admin/funcionarios/auditores/despacho/superusuario pueden ver comentarios
        $usuario = UsuarioController::getInstancia()->buscarUsuario(session('id_usuario'))['usuario'];
        $puedeComentarios = $usuario->es_superusuario || $usuario->es_administrador || $usuario->es_auditor || $usuario->es_despacho || $usuario->es_control || $usuario->tieneRol('FUNCIONARIO_1') || $usuario->tieneRol('FUNCIONARIO_2');
        if (!$puedeComentarios) {
            return response()->json([]);
        }

        $comentarios = \App\NotaComentario::where('id_nota', $id)
            ->with('usuario')
            ->orderBy('created_at', 'desc')
            ->get();
        return response()->json($comentarios);
    }




    public function show($id)
    {
        $nota = NotaIngreso::with(['casino', 'expedientes'])->findOrFail($id);

        if (request()->ajax()) {
            return view('Unified.detalle_nota_drawer', compact('nota'));
        }

        return view('Unified.detalle_nota_drawer', compact('nota'));
    }
    /**
     * Verifica si el usuario actual puede eliminar notas/adjuntos
     */
    private function puedeEliminar()
    {
        $id_usuario = session('id_usuario');
        if (!$id_usuario)
            return false;
        $usuario_data = UsuarioController::getInstancia()->buscarUsuario($id_usuario);
        $u = $usuario_data['usuario'];
        return $u->es_superusuario || $u->es_administrador || $u->es_auditor || $u->es_despacho || $u->es_control;
    }

    /**
     * Permiso para eliminar NOTAS (destroy individual / eliminación masiva).
     * Igual base que puedeEliminar() pero los roles administrador quedan excluidos.
     */
    private function puedeEliminarNotas()
    {
        $id_usuario = session('id_usuario');
        if (!$id_usuario)
            return false;
        $usuario_data = UsuarioController::getInstancia()->buscarUsuario($id_usuario);
        $u = $usuario_data['usuario'];
        return ($u->es_superusuario || $u->es_auditor || $u->es_despacho || $u->es_control) && !$u->es_administrador;
    }

    /**
     * Devuelve el modelo del usuario logueado (o null).
     */
    private function usuarioActual()
    {
        $id = session('id_usuario');
        if (!$id)
            return null;
        $data = UsuarioController::getInstancia()->buscarUsuario($id);
        return isset($data['usuario']) ? $data['usuario'] : null;
    }

    /**
     * ¿El usuario es un concesionario/casino/plataforma? = tiene algún rol CARGA_NOTAS_*
     * y NO es staff interno (super/admin/auditor/despacho/control) ni funcionario.
     */
    private function esConcesionarioActual($u = null)
    {
        $u = $u ?: $this->usuarioActual();
        if (!$u)
            return false;
        $esStaff = $u->es_superusuario || $u->es_administrador || $u->es_auditor || $u->es_despacho || $u->es_control;
        $esFunc = $u->tieneRol('FUNCIONARIO_1') || $u->tieneRol('FUNCIONARIO_2');
        if ($esStaff || $esFunc)
            return false;
        return DB::table('usuario_tiene_rol')
            ->join('rol', 'rol.id_rol', '=', 'usuario_tiene_rol.id_rol')
            ->where('usuario_tiene_rol.id_usuario', $u->id_usuario)
            ->where('rol.descripcion', 'LIKE', 'CARGA\_NOTAS\_%')
            ->exists();
    }

    /**
     * Casinos y plataformas a las que el usuario tiene acceso (su "scope").
     * Devuelve [arrayCasinoIds, arrayPlataformaIds]. Misma lógica que index().
     */
    private function casinosPlataformasPermitidos($u)
    {
        $casinos = [];
        $plataformas = [];
        if (!$u)
            return [$casinos, $plataformas];

        $roles = DB::table('usuario_tiene_rol')
            ->join('rol', 'rol.id_rol', '=', 'usuario_tiene_rol.id_rol')
            ->where('usuario_tiene_rol.id_usuario', $u->id_usuario)
            ->where('rol.descripcion', 'LIKE', 'CARGA\_NOTAS\_%')
            ->pluck('rol.descripcion')
            ->toArray();

        if (in_array('CARGA_NOTAS_UNIFICADAS', $roles)) {
            $casinos = DB::table('usuario_tiene_casino')
                ->where('id_usuario', $u->id_usuario)
                ->pluck('id_casino')
                ->toArray();
        }

        $rolesPlataforma = array_filter($roles, function ($r) {
            return $r !== 'CARGA_NOTAS_UNIFICADAS';
        });
        if (!empty($rolesPlataforma)) {
            $online = self::obtenerPlataformasOnline();
            foreach ($rolesPlataforma as $rolDesc) {
                $codigo = str_replace('CARGA_NOTAS_', '', $rolDesc);
                foreach ($online as $p) {
                    if ($p->codigo === $codigo) {
                        $plataformas[] = $p->id_plataforma;
                        break;
                    }
                }
            }
        }

        // Fallback admin/control sin rol CARGA_NOTAS_*: sus casinos asignados.
        if (empty($roles) && ($u->es_administrador || $u->es_control)) {
            $casinos = DB::table('usuario_tiene_casino')
                ->where('id_usuario', $u->id_usuario)
                ->pluck('id_casino')
                ->toArray();
        }

        return [$casinos, $plataformas];
    }

    /**
     * Un concesionario puede editar/borrar SOLO sus propias notas que estén
     * en estado CARGA INICIAL (recién cargadas, antes de que control las tome).
     */
    private function concesionarioPuedeGestionarNota($nota)
    {
        $u = $this->usuarioActual();
        if (!$u || !$nota)
            return false;
        if (!$this->esConcesionarioActual($u))
            return false;

        // Solo estado CARGA INICIAL
        $exp = $nota->expedientes()->first();
        if (!$exp || $exp->estado_actual !== NotaEstado::CARGA_INICIAL)
            return false;

        // Solo notas dentro de su scope de casinos/plataformas
        list($casinos, $plataformas) = $this->casinosPlataformasPermitidos($u);
        $enScope = (is_array($casinos) && in_array($nota->id_casino, $casinos))
            || ($nota->id_plataforma && is_array($plataformas) && in_array($nota->id_plataforma, $plataformas));
        return (bool) $enScope;
    }

    /**
     * Un concesionario puede borrar el GRUPO (nota padre) solo si TODAS sus notas
     * (la única que haya, o ambas) están en estado CARGA INICIAL y el grupo es de su scope.
     */
    private function concesionarioPuedeGestionarGrupo($grupo)
    {
        $u = $this->usuarioActual();
        if (!$u || !$grupo)
            return false;
        if (!$this->esConcesionarioActual($u))
            return false;

        $grupo->load('notas.expedientes');
        if ($grupo->notas->isEmpty())
            return false;

        // TODAS las notas del grupo deben estar en CARGA INICIAL
        foreach ($grupo->notas as $nota) {
            $exp = $nota->expedientes->first();
            if (!$exp || $exp->estado_actual !== NotaEstado::CARGA_INICIAL)
                return false;
        }

        // El grupo debe estar dentro de su scope de casinos/plataformas
        list($casinos, $plataformas) = $this->casinosPlataformasPermitidos($u);
        $enScope = (is_array($casinos) && in_array($grupo->id_casino, $casinos))
            || ($grupo->id_plataforma && is_array($plataformas) && in_array($grupo->id_plataforma, $plataformas));
        return (bool) $enScope;
    }

    public function destroy($id)
    {
        $nota = NotaIngreso::find($id);
        if (!$nota) {
            return response()->json(['success' => false, 'msg' => 'Nota no encontrada'], 404);
        }
        // Staff con permiso de borrado, O concesionario sobre su propia nota en CARGA INICIAL.
        if (!$this->puedeEliminarNotas() && !$this->concesionarioPuedeGestionarNota($nota)) {
            return response()->json(['success' => false, 'msg' => 'No tiene permisos para eliminar esta nota'], 403);
        }
        try {
            DB::transaction(function () use ($nota) {
                $grupo = $nota->grupo;
                $nota->delete();
                // Si era la única nota del grupo, eliminar también el padre
                // (no dejar un grupo/nota padre huérfano y vacío).
                if ($grupo && $grupo->notas()->count() === 0) {
                    $grupo->delete();
                }
            });
            return response()->json(['success' => true]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'msg' => $e->getMessage()], 500);
        }
    }

    /**
     * Eliminar un Grupo de Trámite y todas sus notas hijas
     */
    public function destroyGrupo($id)
    {
        $grupo = \App\Models\GrupoTramite::find($id);
        if (!$grupo) {
            return response()->json(['success' => false, 'msg' => 'Grupo no encontrado'], 404);
        }
        // Staff con permiso, O concesionario si TODAS las notas del grupo están en CARGA INICIAL.
        if (!$this->puedeEliminar() && !$this->concesionarioPuedeGestionarGrupo($grupo)) {
            return response()->json(['success' => false, 'msg' => 'No tiene permisos para eliminar este trámite'], 403);
        }
        try {
            DB::transaction(function () use ($grupo) {
                // Eliminar todas las notas hijas primero
                foreach ($grupo->notas as $nota) {
                    // Eliminar expedientes y movimientos asociados
                    foreach ($nota->expedientes as $exp) {
                        \App\Models\Movimiento::where('id_expediente_nota', $exp->id)->delete();
                        $exp->delete();
                    }
                    // Eliminar activos asociados
                    NotaTieneActivo::where('id_nota_ingreso', $nota->id)->delete();
                    $nota->delete();
                }

                // Eliminar el grupo
                $grupo->delete();
            });

            return response()->json(['success' => true]);
        } catch (\Exception $e) {
            \Log::error("Error al eliminar grupo: " . $e->getMessage());
            return response()->json(['success' => false, 'msg' => $e->getMessage()], 500);
        }
    }

    // =========================================================================
    // ! MODAL DE DETALLE - Ver/Editar Trámite
    // =========================================================================

    /**
     * Obtener detalle completo de un Grupo de Trámite
     */
    public function getDetalleGrupo($id)
    {
        try {
            $grupo = \App\Models\GrupoTramite::with(['notas.expedientes.movimientos.usuario', 'notas.activos'])
                ->findOrFail($id);

            $notaMkt = null;
            $notaFisc = null;

            foreach ($grupo->notas as $nota) {
                $notaData = $this->formatNotaDetalle($nota);
                if ($nota->tipo_rama === 'MKT') {
                    $notaMkt = $notaData;
                } else {
                    $notaFisc = $notaData;
                }
            }

            // Notas de aprobación del grupo
            $notasAprobacion = DB::table('grupo_notas_aprobacion')
                ->where('id_grupo', $grupo->id)
                ->orderBy('created_at', 'desc')
                ->get()
                ->map(function ($na) {
                    return [
                        'id' => $na->id,
                        'tipo_rama' => $na->tipo_rama,
                        'tipo_documento' => isset($na->tipo_documento) ? $na->tipo_documento : '',
                        'numero_documento' => isset($na->numero_documento) ? $na->numero_documento : '',
                        'anio_documento' => isset($na->anio_documento) ? $na->anio_documento : '',
                        'nombre_original' => $na->nombre_original,
                        'created_at' => $na->created_at ? \Carbon\Carbon::parse($na->created_at)->format('d/m/Y H:i') : null,
                        'url' => '/notas-unificadas/nota-aprobacion/visualizar/' . $na->id,
                    ];
                });

            // Datos de relación padre/hijos
            $grupoPadre = null;
            if ($grupo->id_grupo_padre) {
                $gp = \App\Models\GrupoTramite::with('casino')->find($grupo->id_grupo_padre);
                if ($gp) {
                    $grupoPadre = [
                        'id' => $gp->id,
                        'nro_nota' => $gp->nro_nota,
                        'anio' => $gp->anio,
                        'titulo' => $gp->titulo,
                        'casino' => $gp->casino ? $gp->casino->nombre : self::resolverNombreCasino($gp->id_casino, $gp->id_plataforma),
                    ];
                }
            }

            $gruposHijos = \App\Models\GrupoTramite::with('casino')
                ->where('id_grupo_padre', $grupo->id)
                ->get()
                ->map(function ($gh) {
                    return [
                        'id' => $gh->id,
                        'nro_nota' => $gh->nro_nota,
                        'anio' => $gh->anio,
                        'titulo' => $gh->titulo,
                        'casino' => $gh->casino ? $gh->casino->nombre : self::resolverNombreCasino($gh->id_casino, $gh->id_plataforma),
                    ];
                });

            return response()->json([
                'success' => true,
                'grupo' => [
                    'id' => $grupo->id,
                    'nro_nota' => $grupo->nro_nota,
                    'anio' => $grupo->anio,
                    'id_casino' => $grupo->id_casino,
                    'id_plataforma' => $grupo->id_plataforma,
                    'tipo_solicitud' => $grupo->tipo_solicitud,
                    'fecha_inicio_evento' => $grupo->fecha_inicio_evento,
                    'fecha_fin_evento' => $grupo->fecha_fin_evento,
                    'titulo' => $grupo->titulo,
                    'casino' => $grupo->casino ? $grupo->casino->nombre : self::resolverNombreCasino($grupo->id_casino, $grupo->id_plataforma),
                    'created_at' => $grupo->created_at ? $grupo->created_at->format('d/m/Y H:i') : null,
                ],
                'grupo_padre' => $grupoPadre,
                'grupos_hijos' => $gruposHijos,
                'mkt' => $notaMkt,
                'fisc' => $notaFisc,
                'notas_aprobacion' => $notasAprobacion,
            ]);
        } catch (\Throwable $e) {
            \Log::error("getDetalleGrupo error: " . $e->getMessage() . " | " . $e->getFile() . ":" . $e->getLine());
            return response()->json(['success' => false, 'msg' => $e->getMessage()], 500);
        }
    }

    /**
     * Obtener detalle de una Nota individual
     */
    public function getDetalleNota($id)
    {
        try {
            $nota = NotaIngreso::with(['expedientes.movimientos.usuario', 'activos', 'grupo'])
                ->findOrFail($id);

            return response()->json([
                'success' => true,
                'nota' => $this->formatNotaDetalle($nota),
                'grupo' => $nota->grupo ? [
                    'id' => $nota->grupo->id,
                    'tipo_tarea' => $nota->grupo->tipo_tarea,
                    'titulo' => $nota->grupo->titulo,
                ] : null
            ]);
        } catch (\Throwable $e) {
            \Log::error("getDetalleNota error: " . $e->getMessage());
            return response()->json(['success' => false, 'msg' => $e->getMessage()], 500);
        }
    }

    /**
     * Formatear nota para el modal de detalle
     */
    private function formatNotaDetalle($nota)
    {
        // Obtener estado del último movimiento
        $estado = 'INGRESADO';
        $movimientos = [];

        // Determinar si el usuario puede ver comentarios
        $usuarioActual = UsuarioController::getInstancia()->buscarUsuario(session('id_usuario'))['usuario'];
        $puedeVerComentarios = true; // Los comentarios de las notas son visibles para todos (pedido del usuario)

        if ($nota->expedientes && $nota->expedientes->count() > 0) {
            $exp = $nota->expedientes->first();
            $estado = $exp->estado_actual ?: $estado;
            if ($exp->movimientos && $exp->movimientos->count() > 0) {

                foreach ($exp->movimientos->sortByDesc('id') as $mov) {
                    // Ocultar comentarios para casinos/plataformas
                    if (!$puedeVerComentarios && $mov->accion === 'COMENTARIO')
                        continue;

                    $usuario = $mov->usuario;
                    $movimientos[] = [
                        'id' => $mov->id,
                        'id_usuario' => $mov->id_usuario,
                        'fecha' => $mov->fecha_movimiento ? (is_string($mov->fecha_movimiento) ? $mov->fecha_movimiento : $mov->fecha_movimiento->format('d/m/Y H:i')) : null,
                        'accion' => $mov->accion,
                        'comentario' => $mov->comentario,
                        'usuario' => $usuario->nombre ?? 'Sistema',
                        'user_imagen' => $usuario ? $usuario->imagen : null,
                    ];
                }
            }
        }

        // Activos asociados (enriquecer con datos reales)
        $activos = $this->enriquecerActivos($nota->activos);

        // Adjuntos (estructura vieja: 1 por tipo desde path_*) — se mantiene para compatibilidad
        $adjuntos = [
            'solicitud' => $nota->path_solicitud ? ['existe' => true, 'nombre' => basename($nota->path_solicitud), 'path' => $nota->path_solicitud] : ['existe' => false],
            'diseno' => $nota->path_diseno ? ['existe' => true, 'nombre' => basename($nota->path_diseno), 'path' => $nota->path_diseno] : ['existe' => false],
            'bases' => $nota->path_bases ? ['existe' => true, 'nombre' => basename($nota->path_bases), 'path' => $nota->path_bases] : ['existe' => false],
            'informe' => $nota->path_informe ? ['existe' => true, 'nombre' => basename($nota->path_informe), 'path' => $nota->path_informe] : ['existe' => false],
            'varios' => $nota->path_varios ? ['existe' => true, 'nombre' => basename($nota->path_varios), 'path' => $nota->path_varios] : ['existe' => false],
        ];

        // Estructura nueva anidada: tipo -> [documentos -> versiones]
        $documentos = $this->documentosDeNota($nota->id);

        // Resolver nombre de casino/plataforma
        $casinoNombre = self::resolverNombreCasino($nota->id_casino, $nota->id_plataforma);

        // Resolver tipo_evento y categoría por ID desde BD
        $tipoEventoNombre = $nota->id_tipo_evento ? NotaTipoEvento::nombrePorId($nota->id_tipo_evento) : null;
        $categoriaNombre = $nota->id_categoria ? NotaCategoria::nombrePorId($nota->id_categoria) : null;

        return [
            'id' => $nota->id,
            'nro_nota' => $nota->nro_nota ?? 'N/A',
            'anio' => $nota->anio,
            'tipo_rama' => $nota->tipo_rama,
            'tipo_solicitud' => $nota->tipo_solicitud,
            'descripcion' => $nota->titulo ?? 'Sin descripción',
            'casino' => $casinoNombre,
            'id_casino' => $nota->id_casino,
            'id_plataforma' => $nota->id_plataforma,
            'estado' => $estado,
            'tipo_evento' => $tipoEventoNombre,
            'id_tipo_evento' => $nota->id_tipo_evento,
            'categoria' => $categoriaNombre,
            'id_categoria' => $nota->id_categoria,
            'fecha_inicio' => $nota->fecha_inicio_evento,
            'fecha_fin' => $nota->fecha_fin_evento,
            'fecha_referencia' => $nota->fecha_referencia,
            'fecha_pretendida_aprobacion' => $nota->fecha_pretendida_aprobacion,
            'fecha_propuesta_realizacion' => $nota->fecha_propuesta_realizacion,
            'compartir_administrador' => (int) $nota->compartir_administrador,
            'involucra_juegos' => (int) $nota->involucra_juegos,
            'created_at' => $nota->created_at ? $nota->created_at->format('d/m/Y H:i') : null,
            'adjuntos' => $adjuntos,
            'documentos' => $documentos,
            'activos' => $activos,
            'movimientos' => $movimientos,
        ];
    }

    /**
     * Devuelve los adjuntos de una nota en el modelo nuevo Tipo -> Documentos -> Versiones.
     * Estructura: [ tipo => [ { id, nombre, orden, cant_versiones, ultima{...}, versiones[...] }, ... ], ... ]
     */
    private function documentosDeNota($idNota)
    {
        $docs = \App\Models\NotaArchivoDocumento::where('id_nota_ingreso', $idNota)
            ->orderBy('tipo_archivo')->orderBy('orden')->orderBy('id')
            ->get();

        $out = [];
        foreach ($docs as $doc) {
            $versiones = \App\Models\NotaArchivoVersion::where('id_documento', $doc->id)
                ->orderBy('version', 'desc')->get();
            $ultima = $versiones->first();

            $mapVer = function ($v) {
                return [
                    'version_id' => $v->id,
                    'version' => $v->version,
                    'nombre_original' => $v->nombre_original ?: basename($v->path_archivo),
                    'path' => $v->path_archivo,
                    'created_at' => $v->created_at ? $v->created_at->format('d/m/Y H:i') : null,
                ];
            };

            $out[$doc->tipo_archivo][] = [
                'id' => $doc->id,
                'nombre' => $doc->nombre ?: 'Documento',
                'orden' => $doc->orden,
                'cant_versiones' => $versiones->count(),
                'ultima' => $ultima ? $mapVer($ultima) : null,
                'versiones' => $versiones->map($mapVer)->values(),
            ];
        }

        return $out;
    }

    /**
     * Actualizar campos de una Nota
     *
     * Los campos nro_nota, anio y titulo viven duplicados en `notas_ingreso` y
     * `grupos_tramites` (el wizard los crea sincronizados). Si alguno de esos
     * cambia, propagamos al grupo padre y a todas las notas hermanas para
     * mantener el invariante. Validamos previamente que el nuevo nro_nota+anio
     * no choque con otro grupo del mismo casino/plataforma.
     */
    public function updateNota(Request $request, $id)
    {
        try {
            $nota = NotaIngreso::with('grupo')->findOrFail($id);

            // Autorización: staff interno (nivel admin) puede editar siempre;
            // un concesionario solo puede editar SUS notas en estado CARGA INICIAL.
            $u = $this->usuarioActual();
            $esAdmin = $u && ($u->es_superusuario || $u->es_administrador || $u->es_auditor || $u->es_despacho || $u->es_control);
            if (!$esAdmin && !$this->concesionarioPuedeGestionarNota($nota)) {
                return response()->json(['success' => false, 'msg' => 'No tiene permisos para editar esta nota'], 403);
            }

            // Campos editables (mappeo de frontend a DB)
            $campoMapping = [
                'nro_nota_ing' => 'nro_nota',
                'anio' => 'anio',
                'descripcion' => 'titulo',
                'fecha_inicio' => 'fecha_inicio_evento',
                'fecha_fin' => 'fecha_fin_evento',
                'id_tipo_evento' => 'id_tipo_evento',
                'id_categoria' => 'id_categoria',
                'fecha_pretendida_aprobacion' => 'fecha_pretendida_aprobacion',
                'fecha_propuesta_realizacion' => 'fecha_propuesta_realizacion',
                'compartir_administrador' => 'compartir_administrador',
                'fecha_referencia' => 'fecha_referencia'
            ];

            // Validación defensiva: nro_nota, anio y titulo son NOT NULL en BD.
            // Si vienen vacíos rechazamos antes de tocar nada.
            if ($request->has('nro_nota_ing') && trim((string) $request->nro_nota_ing) === '') {
                return response()->json(['success' => false, 'msg' => 'El Nº de nota no puede quedar vacío'], 422);
            }
            if ($request->has('anio') && (int) $request->anio <= 0) {
                return response()->json(['success' => false, 'msg' => 'El año no es válido'], 422);
            }
            if ($request->has('descripcion') && trim((string) $request->descripcion) === '') {
                return response()->json(['success' => false, 'msg' => 'El título/descripción no puede quedar vacío'], 422);
            }

            // Detectar cambios en campos compartidos con grupos_tramites
            $grupo = $nota->grupo;
            $nuevosCompartidos = [];

            if ($grupo) {
                if ($request->has('nro_nota_ing')) {
                    $nuevoNro = trim((string) $request->nro_nota_ing);
                    if ($nuevoNro !== (string) $grupo->nro_nota) {
                        $nuevosCompartidos['nro_nota'] = $nuevoNro;
                    }
                }
                if ($request->has('anio')) {
                    $nuevoAnio = (int) $request->anio;
                    if ($nuevoAnio !== (int) $grupo->anio) {
                        $nuevosCompartidos['anio'] = $nuevoAnio;
                    }
                }
                if ($request->has('descripcion')) {
                    $nuevoTit = trim((string) $request->descripcion);
                    if ($nuevoTit !== (string) $grupo->titulo) {
                        $nuevosCompartidos['titulo'] = $nuevoTit;
                    }
                }

                // Validar choque con otro grupo si cambia nro_nota o anio
                if (isset($nuevosCompartidos['nro_nota']) || isset($nuevosCompartidos['anio'])) {
                    $nroFinal = $nuevosCompartidos['nro_nota'] ?? $grupo->nro_nota;
                    $anioFinal = $nuevosCompartidos['anio'] ?? $grupo->anio;

                    $q = \App\Models\GrupoTramite::where('nro_nota', $nroFinal)
                        ->where('anio', $anioFinal)
                        ->where('id', '!=', $grupo->id);
                    if ($grupo->id_plataforma) {
                        $q->where('id_plataforma', $grupo->id_plataforma);
                    } else {
                        $q->where('id_casino', $grupo->id_casino);
                    }
                    if ($q->exists()) {
                        return response()->json([
                            'success' => false,
                            'msg' => 'Ya existe otro trámite con Nº ' . $nroFinal . '-' . $anioFinal . ' para el mismo casino/plataforma. Verifique el número o el año.'
                        ], 422);
                    }
                }
            }

            DB::beginTransaction();

            foreach ($campoMapping as $frontendCampo => $dbCampo) {
                if ($request->has($frontendCampo)) {
                    $nota->$dbCampo = $request->$frontendCampo;
                }
            }

            $nota->save();

            // Propagar nro_nota / anio / titulo al grupo y a las notas hermanas
            if ($grupo && !empty($nuevosCompartidos)) {
                foreach ($nuevosCompartidos as $col => $val) {
                    $grupo->$col = $val;
                }
                $grupo->save();

                NotaIngreso::where('id_grupo', $grupo->id)->update($nuevosCompartidos);
            }

            DB::commit();

            // Registrar movimiento de edición
            $exp = $nota->expedientes->first();
            $id_usuario = session('id_usuario');
            $usuarioEdit = \App\Usuario::find($id_usuario);
            $nombreEdit = $usuarioEdit ? $usuarioEdit->nombre : 'Usuario';

            if ($exp) {
                Movimiento::create([
                    'id_expediente_nota' => $exp->id,
                    'id_usuario' => $id_usuario ?? 1,
                    'fecha_movimiento' => \Carbon\Carbon::now(),
                    'accion' => 'EDITADO',
                    'comentario' => $nombreEdit . ' editó la nota'
                ]);
            }

            // Cambio de estado
            if ($request->has('estado') && $request->estado && $exp) {
                $nuevoEstado = $request->estado;
                $estadoActual = $exp->estado_actual;

                if ($nuevoEstado !== $estadoActual) {
                    $esFuncionario1 = $usuarioEdit->tieneRol('FUNCIONARIO_1');
                    $esFuncionario2 = $usuarioEdit->tieneRol('FUNCIONARIO_2');
                    $esFuncionario = $esFuncionario1 || $esFuncionario2;
                    $esAdmin = !$esFuncionario && ($usuarioEdit->es_superusuario || $usuarioEdit->es_administrador || $usuarioEdit->es_auditor || $usuarioEdit->es_despacho || $usuarioEdit->es_control);

                    if ($esFuncionario1)
                        $nivel = 'funcionario1';
                    elseif ($esFuncionario2)
                        $nivel = 'funcionario2';
                    elseif ($esAdmin)
                        $nivel = 'admin';
                    else
                        $nivel = 'regular';

                    if (!NotaEstado::transicionPermitida($estadoActual, $nuevoEstado, $nivel)) {
                        return response()->json(['success' => false, 'msg' => 'No tiene permisos para esta transición de estado'], 403);
                    }

                    $exp->estado_actual = $nuevoEstado;
                    $exp->save();

                    Movimiento::create([
                        'id_expediente_nota' => $exp->id,
                        'id_usuario' => $id_usuario,
                        'fecha_movimiento' => \Carbon\Carbon::now(),
                        'accion' => 'MODIFICACION',
                        'comentario' => $nombreEdit . ' cambió estado a: ' . $nuevoEstado
                    ]);

                    // Notificar por mail
                    $idOrigen = DB::table('nota_estados')->where('descripcion', $estadoActual)->where('activo', 1)->value('id') ?: 0;
                    $idDestino = DB::table('nota_estados')->where('descripcion', $nuevoEstado)->where('activo', 1)->value('id') ?: 0;
                    self::notificarCambioEstadoDiferido($idOrigen, $idDestino, $estadoActual, $nuevoEstado, $nota->id, $nombreEdit);
                }
            }

            return response()->json([
                'success' => true,
                'msg' => 'Nota actualizada',
                'grupo_actualizado' => !empty($nuevosCompartidos),
            ]);
        } catch (\Throwable $e) {
            // Si la transacción quedó abierta (excepción después de beginTransaction
            // y antes de commit), rollback. Si nunca se abrió o ya hubo commit, no-op.
            try {
                if (DB::transactionLevel() > 0) DB::rollBack();
            } catch (\Throwable $ignored) {}

            \Log::error("updateNota error: " . $e->getMessage());
            return response()->json(['success' => false, 'msg' => $e->getMessage()], 500);
        }
    }

    /**
     * Guardar/limpiar el "borrador" (anotación rápida inline) de una nota hija.
     * Permitido solo para superusuario/administrador/auditor/despacho.
     */
    public function updateBorrador(Request $request, $id)
    {
        try {
            $usuario_data = UsuarioController::getInstancia()->buscarUsuario(session('id_usuario'));
            $usuario = $usuario_data['usuario'] ?? null;
            if (!$usuario || !($usuario->es_superusuario || $usuario->es_administrador || $usuario->es_auditor || $usuario->es_despacho)) {
                return response()->json(['success' => false, 'msg' => 'Sin permiso para editar borrador'], 403);
            }

            $nota = NotaIngreso::findOrFail($id);
            $anterior = (string) ($nota->borrador ?? '');
            $valor = $request->has('borrador') ? trim((string) $request->borrador) : '';
            // Cap a 500 caracteres (definición de columna).
            if (mb_strlen($valor) > 500) {
                $valor = mb_substr($valor, 0, 500);
            }
            $nota->borrador = $valor === '' ? null : $valor;
            $nota->save();

            // Registrar en historial solo si cambió el contenido.
            if ($valor !== $anterior) {
                if ($anterior === '' && $valor !== '') {
                    $comentario = 'agregó borrador: "' . $valor . '"';
                } elseif ($valor === '') {
                    $comentario = 'borró el borrador';
                } else {
                    $comentario = 'modificó el borrador: "' . $valor . '"';
                }
                self::registrarMovimiento($nota, 'BORRADOR', $comentario);
            }

            return response()->json([
                'success' => true,
                'borrador' => $nota->borrador,
            ]);
        } catch (\Throwable $e) {
            \Log::error("updateBorrador error: " . $e->getMessage());
            return response()->json(['success' => false, 'msg' => $e->getMessage()], 500);
        }
    }

    /**
     * Agregar activos a una Nota existente
     */
    public function addActivos(Request $request, $id)
    {
        try {
            $nota = NotaIngreso::findOrFail($id);
            $activos = $request->activos ?: [];
            $cantPrevia = $nota->activos()->count();
            $this->procesarActivos($nota, $activos);

            // Registrar en historial cuánto y de qué tipo se agregó.
            // (procesarActivos "explota" islas en MTMs; tomamos el delta real post-insert.)
            $cantPosterior = $nota->activos()->count();
            $delta = $cantPosterior - $cantPrevia;
            if ($delta > 0) {
                $tipos = collect($activos)->pluck('tipo')->map(function ($t) { return strtoupper($t); })->unique()->values()->all();
                $tiposTxt = implode(', ', $tipos);
                self::registrarMovimiento(
                    $nota,
                    'ACTIVO_AGREGADO',
                    'agregó ' . $delta . ' activo(s)' . ($tiposTxt !== '' ? ' (' . $tiposTxt . ')' : '')
                );
            }

            // Recargar y devolver la lista actualizada (misma lógica que getDetalleNota)
            $nota->load('activos');
            $lista = $this->enriquecerActivos($nota->activos);
            return response()->json(['success' => true, 'activos' => $lista]);
        } catch (\Throwable $e) {
            \Log::error("addActivos error: " . $e->getMessage());
            return response()->json(['success' => false, 'msg' => $e->getMessage()], 500);
        }
    }

    /**
     * Enriquecer colección de activos con datos del activo subyacente (MTM/Mesa/Bingo/Juego Online).
     * Devuelve para cada activo: nombre, id_display, estado (Activa/Inactiva/—) y porcentaje_devolucion
     * para que el modal de detalle muestre una tabla uniforme. Conserva los campos legacy
     * (nro_admin, marca, nro_isla) por compat con renders previos.
     */
    private function enriquecerActivos($activos)
    {
        $lista = [];
        foreach ($activos as $activo) {
            $tipo = $activo->tipo_activo ?? 'ISLA';
            $info = [
                'id' => $activo->id,
                'id_activo' => $activo->id_activo ?? 'N/A',
                'tipo_activo' => $tipo,
                'nombre' => '—',
                'id_display' => $activo->id_activo ?? '—',
                'estado' => '—',
                'porcentaje_devolucion' => '—',
            ];

            if ($tipo === 'MTM' && $activo->id_activo) {
                $maq = \App\Maquina::with('juego_activo')->find($activo->id_activo);
                if ($maq) {
                    $info['nro_admin'] = $maq->nro_admin;
                    $info['marca'] = $maq->marca ?? '';
                    $isla = $maq->id_isla ? \App\Isla::find($maq->id_isla) : null;
                    $info['nro_isla'] = $isla ? $isla->nro_isla : null;

                    $info['nombre'] = $maq->juego_activo ? $maq->juego_activo->nombre_juego : ($maq->marca ?: 'MTM');
                    $info['id_display'] = $maq->nro_admin ?: $maq->id_maquina;
                    $info['estado'] = self::estadoMtmBinario($maq->id_estado_maquina);
                    $pdev = $maq->obtenerPorcentajeDevolucion();
                    $info['porcentaje_devolucion'] = $pdev !== null ? $pdev : '—';
                }
            } elseif ($tipo === 'MESA' && $activo->id_activo) {
                $mesa = \App\Mesas\Mesa::with('juego')->find($activo->id_activo);
                if ($mesa) {
                    $info['nombre'] = $mesa->juego ? $mesa->juego->nombre_juego : ($mesa->nombre ?: 'Mesa');
                    $info['id_display'] = $mesa->nro_mesa ?: $mesa->id_mesa_de_panio;
                    // mesa_de_panio no tiene flag activo/inactivo; las soft-deleted no aparecen aquí.
                    $info['estado'] = 'Activa';
                }
            } elseif ($tipo === 'BINGO') {
                $info['nombre'] = 'Bingo (general)';
            } elseif ($tipo === 'JUEGO_ONLINE' && $activo->id_activo) {
                // Buscar el juego en el cache de la API (id_activo == id_juego en la API).
                // El endpoint solo devuelve juegos activos (server filtra estado_juego='Activo'
                // y deleted_at IS NULL). Si esta nota referencia un id que ya no aparece,
                // asumimos que el juego fue dado de baja en la plataforma -> Inactiva.
                // Matchea por id_juego primero y, como red de seguridad, por cod_juego.
                // (Notas viejas guardaron el cod_juego como id_activo -> así se rescatan.)
                $datos = self::obtenerDatosOnline();
                $encontrado = false;
                $valor = (string) $activo->id_activo;
                foreach ($datos as $plat) {
                    if (!isset($plat->juegos))
                        continue;
                    foreach ($plat->juegos as $j) {
                        if ((int) $j->id_juego === (int) $activo->id_activo || (string) ($j->cod_juego ?? '') === $valor) {
                            $info['nombre'] = $j->nombre_juego;
                            $info['id_display'] = $j->cod_juego ?? $activo->id_activo;
                            $info['estado'] = 'Activa';
                            $info['porcentaje_devolucion'] = $j->porcentaje_devolucion ?? '—';
                            $encontrado = true;
                            break 2;
                        }
                    }
                }
                if (!$encontrado) {
                    $info['nombre'] = 'Juego #' . $activo->id_activo . ' (baja en plataforma)';
                    $info['estado'] = 'Inactiva';
                }
            }

            $lista[] = $info;
        }
        return $lista;
    }

    /**
     * Exportar a CSV/Excel los activos (máquinas/juegos) asociados a una nota.
     * Columnas: Tipo, Nombre, ID/Nro Admin, Estado, % Devolución, Isla, Casino/Plataforma, Nro de Nota.
     */
    public function exportarActivos(Request $request, $id)
    {
        $formato = strtolower($request->input('formato', 'xlsx'));
        if (!in_array($formato, ['csv', 'xlsx'])) {
            $formato = 'xlsx';
        }

        $nota = NotaIngreso::findOrFail($id);
        $activos = $this->enriquecerActivos($nota->activos);

        $casinoNombre = self::resolverNombreCasino($nota->id_casino, $nota->id_plataforma);
        $nroNota = ($nota->nro_nota ?: '') . ($nota->anio ? '/' . $nota->anio : '');

        $tipoNombre = [
            'MTM' => 'Máquina (MTM)',
            'MESA' => 'Mesa de Paño',
            'BINGO' => 'Bingo',
            'JUEGO_ONLINE' => 'Juego Online',
            'ISLA' => 'Isla',
        ];

        $headers = ['Tipo', 'Nombre', 'ID / Nro Admin', 'Estado', '% Devolución', 'Isla', 'Casino / Plataforma', 'Nro de Nota'];

        $data = [];
        foreach ($activos as $a) {
            $tipo = isset($a['tipo_activo']) ? $a['tipo_activo'] : '';
            $pdev = (isset($a['porcentaje_devolucion']) && $a['porcentaje_devolucion'] !== '—' && $a['porcentaje_devolucion'] !== '' && $a['porcentaje_devolucion'] !== null)
                ? $a['porcentaje_devolucion'] : '';
            $isla = (isset($a['nro_isla']) && $a['nro_isla']) ? ('N° ' . $a['nro_isla']) : '';
            $estado = (isset($a['estado']) && $a['estado'] !== '—') ? $a['estado'] : '';
            $data[] = [
                isset($tipoNombre[$tipo]) ? $tipoNombre[$tipo] : $tipo,
                isset($a['nombre']) ? $a['nombre'] : '',
                isset($a['id_display']) ? $a['id_display'] : (isset($a['id_activo']) ? $a['id_activo'] : ''),
                $estado,
                $pdev,
                $isla,
                $casinoNombre,
                $nroNota,
            ];
        }

        $filename = 'activos_nota_' . ($nota->nro_nota ?: $nota->id) . '_' . date('Y-m-d');

        return \Excel::create($filename, function ($excel) use ($headers, $data) {
            $excel->sheet('Activos', function ($sheet) use ($headers, $data) {
                $sheet->row(1, $headers);
                $sheet->row(1, function ($row) {
                    $row->setBackground('#2c3e50');
                    $row->setFontColor('#ffffff');
                    $row->setFontWeight('bold');
                    $row->setFontSize(10);
                });
                $rowNum = 2;
                foreach ($data as $d) {
                    $sheet->row($rowNum, $d);
                    $rowNum++;
                }
                $sheet->setAutoSize(true);
                $sheet->freezeFirstRow();
            });
        })->download($formato);
    }

    /**
     * Eliminar un activo de una Nota
     */
    public function removeActivo($id)
    {
        try {
            $activo = NotaTieneActivo::findOrFail($id);
            $notaId = $activo->id_nota_ingreso;
            $tipo = $activo->tipo_activo ?? 'activo';
            $idDelActivo = $activo->id_activo ?? '-';
            $activo->delete();

            // Registrar en historial qué se quitó.
            $notaParaHistorial = NotaIngreso::find($notaId);
            self::registrarMovimiento(
                $notaParaHistorial,
                'ACTIVO_ELIMINADO',
                'quitó activo (' . $tipo . ' #' . $idDelActivo . ')'
            );

            $restantes = NotaTieneActivo::where('id_nota_ingreso', $notaId)->get();
            $activos = $this->enriquecerActivos($restantes);

            return response()->json(['success' => true, 'activos' => $activos]);
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'msg' => $e->getMessage()], 500);
        }
    }

    /**
     * Agregar comentario a una Nota
     */
    public function addComentario(Request $request, $id)
    {
        try {
            $nota = NotaIngreso::with('expedientes')->findOrFail($id);
            $exp = $nota->expedientes->first();

            if (!$exp) {
                return response()->json(['success' => false, 'msg' => 'No se encontró expediente'], 400);
            }

            $mov = Movimiento::create([
                'id_expediente_nota' => $exp->id,
                'id_usuario' => session('id_usuario') ?? 1,
                'fecha_movimiento' => \Carbon\Carbon::now(),
                'accion' => 'COMENTARIO',
                'comentario' => $request->comentario
            ]);

            // Get user name
            $usuario = \App\Usuario::find(session('id_usuario'));

            return response()->json([
                'success' => true,
                'movimiento' => [
                    'id' => $mov->id,
                    'id_usuario' => $mov->id_usuario,
                    'fecha' => \Carbon\Carbon::now()->format('d/m/Y H:i'),
                    'accion' => 'COMENTARIO',
                    'comentario' => $request->comentario,
                    'usuario' => $usuario->nombre ?? 'Usuario',
                    'user_imagen' => $usuario ? $usuario->imagen : null,
                ]
            ]);
        } catch (\Throwable $e) {
            \Log::error("addComentario error: " . $e->getMessage());
            return response()->json(['success' => false, 'msg' => $e->getMessage()], 500);
        }
    }

    /**
     * Eliminar un comentario (movimiento)
     */
    public function deleteComentario($id)
    {
        try {
            $mov = Movimiento::where('accion', 'COMENTARIO')
                ->findOrFail($id);

            $userId = session('id_usuario');
            $esPropio = ($mov->id_usuario == $userId);

            if (!$esPropio && !$this->puedeEliminar()) {
                return response()->json(['success' => false, 'msg' => 'No tiene permisos para eliminar este comentario'], 403);
            }

            $mov->delete();
            return response()->json(['success' => true]);
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'msg' => $e->getMessage()], 500);
        }
    }

    /**
     * Eliminar un adjunto específico
     */
    public function deleteAdjunto($id, $campo)
    {
        if (!$this->puedeEliminar()) {
            return response()->json(['success' => false, 'msg' => 'No tiene permisos para eliminar'], 403);
        }
        try {
            $nota = NotaIngreso::findOrFail($id);

            $camposPermitidos = ['path_solicitud', 'path_diseno', 'path_bases', 'path_informe', 'path_varios'];
            if (!in_array($campo, $camposPermitidos)) {
                return response()->json(['success' => false, 'msg' => 'Campo inválido'], 400);
            }

            $campoTipoMap = [
                'path_solicitud' => 'solicitud',
                'path_diseno' => 'diseno',
                'path_bases' => 'bases',
                'path_informe' => 'informe',
                'path_varios' => 'varios',
            ];
            $tipo = $campoTipoMap[$campo];

            // Eliminar todas las versiones históricas del disco y BD
            $versiones = \App\Models\NotaArchivoVersion::where('id_nota_ingreso', $nota->id)
                ->where('tipo_archivo', $tipo)
                ->get();
            foreach ($versiones as $v) {
                Storage::disk('public')->delete($v->path_archivo);
            }
            \App\Models\NotaArchivoVersion::where('id_nota_ingreso', $nota->id)
                ->where('tipo_archivo', $tipo)
                ->delete();

            // Eliminar archivo del campo principal y limpiar BD
            $pathActual = $nota->$campo;
            if ($pathActual) {
                Storage::disk('public')->delete($pathActual);
                $nota->$campo = null;
                $nota->save();

                $exp = $nota->expedientes->first();
                if ($exp) {
                    $usuarioDel = \App\Usuario::find(session('id_usuario'));
                    $nombreDel = $usuarioDel ? $usuarioDel->nombre : 'Usuario';
                    Movimiento::create([
                        'id_expediente_nota' => $exp->id,
                        'id_usuario' => session('id_usuario') ?? 1,
                        'fecha_movimiento' => \Carbon\Carbon::now(),
                        'accion' => 'ADJUNTO_ELIMINADO',
                        'comentario' => $nombreDel . " eliminó adjunto: " . basename($pathActual)
                    ]);
                }
            }

            return response()->json(['success' => true, 'msg' => 'Adjunto eliminado']);
        } catch (\Throwable $e) {
            \Log::error("deleteAdjunto error: " . $e->getMessage());
            return response()->json(['success' => false, 'msg' => $e->getMessage()], 500);
        }
    }

    // =========================================================================
    // ! ADJUNTOS — modelo Tipo -> Documento -> Versión (varios docs por tipo + Anexos)
    // =========================================================================

    /** Carpeta de storage por tipo de adjunto. */
    private function folderPorTipo($tipo)
    {
        $map = [
            'solicitud' => 'solicitudes', 'diseno' => 'disenos', 'bases' => 'bases',
            'informe' => 'informes', 'varios' => 'archivos_varios', 'anexo' => 'anexos',
        ];
        return isset($map[$tipo]) ? $map[$tipo] : 'archivos_varios';
    }

    /** Columna path_* "actual" por tipo (los Anexos no tienen columna -> null). */
    private function campoPorTipo($tipo)
    {
        $map = [
            'solicitud' => 'path_solicitud', 'diseno' => 'path_diseno', 'bases' => 'path_bases',
            'informe' => 'path_informe', 'varios' => 'path_varios',
        ];
        return isset($map[$tipo]) ? $map[$tipo] : null;
    }

    /** Guarda el archivo físico con nombre único y devuelve el path relativo. */
    private function guardarArchivoFisico($file, $folder)
    {
        $originalName = $file->getClientOriginalName();
        $extension = $file->getClientOriginalExtension();
        $baseName = pathinfo($originalName, PATHINFO_FILENAME);
        $uniqueName = time() . '_' . preg_replace('/[^a-zA-Z0-9_-]/', '_', $baseName) . '.' . $extension;
        return $file->storeAs($folder, $uniqueName, 'public');
    }

    /** Registra un movimiento de adjunto en el expediente de la nota. */
    private function logMovimientoAdjunto($nota, $accion, $tipo, $nombreDoc, $nombreArchivo)
    {
        $exp = $nota->expedientes()->first();
        if (!$exp) return;
        $userId = session('id_usuario') ?? 1;
        $u = \App\Usuario::find($userId);
        $nombreUsr = $u ? $u->nombre : 'Usuario';
        $tipoLabel = [
            'solicitud' => 'Solicitud', 'diseno' => 'Diseño', 'bases' => 'Bases',
            'informe' => 'Informe', 'varios' => 'Archivos Varios', 'anexo' => 'Anexo',
        ];
        $verbo = $accion === 'ADJUNTO_REEMPLAZADO' ? 'subió nueva versión de'
            : ($accion === 'ADJUNTO_ELIMINADO' ? 'eliminó' : 'agregó');
        Movimiento::create([
            'id_expediente_nota' => $exp->id,
            'id_usuario' => $userId,
            'fecha_movimiento' => \Carbon\Carbon::now(),
            'accion' => $accion,
            'comentario' => $nombreUsr . ' ' . $verbo . ' ' . ($tipoLabel[$tipo] ?? $tipo)
                . ' "' . $nombreDoc . '"' . ($nombreArchivo ? ': ' . $nombreArchivo : ''),
        ]);
    }

    /**
     * Crea un NUEVO documento de un tipo con su primer archivo (versión 1).
     * Setea path_* en la nota (el caller hace save()). Devuelve el documento.
     * Lo usan subirDocumento (detalle), el wizard y el modal "Agregar Adjuntos".
     */
    private function crearDocumentoConArchivo($nota, $tipo, $file, $nombre = null)
    {
        $userId = session('id_usuario') ?? 1;
        $path = $this->guardarArchivoFisico($file, $this->folderPorTipo($tipo));
        $nombreDoc = ($nombre !== null && trim((string) $nombre) !== '') ? trim((string) $nombre) : $file->getClientOriginalName();

        $doc = \App\Models\NotaArchivoDocumento::create([
            'id_nota_ingreso' => $nota->id,
            'tipo_archivo' => $tipo,
            'nombre' => $nombreDoc,
            'orden' => \App\Models\NotaArchivoDocumento::siguienteOrden($nota->id, $tipo),
            'created_at' => \Carbon\Carbon::now(),
            'created_by' => $userId,
        ]);

        \App\Models\NotaArchivoVersion::create([
            'id_nota_ingreso' => $nota->id,
            'id_documento' => $doc->id,
            'tipo_archivo' => $tipo,
            'version' => 1,
            'path_archivo' => $path,
            'nombre_original' => $file->getClientOriginalName(),
            'created_at' => \Carbon\Carbon::now(),
            'created_by' => $userId,
        ]);

        $campo = $this->campoPorTipo($tipo);
        if ($campo) {
            $nota->$campo = $path; // el caller hace save()
        }

        return $doc;
    }

    /** Crea un NUEVO documento de un tipo con su primer archivo (versión 1). */
    public function subirDocumento(Request $request, $idNota, $tipo)
    {
        try {
            $nota = NotaIngreso::findOrFail($idNota);
            $tiposValidos = ['solicitud', 'diseno', 'bases', 'informe', 'varios', 'anexo'];
            if (!in_array($tipo, $tiposValidos)) {
                return response()->json(['success' => false, 'msg' => 'Tipo inválido'], 400);
            }
            if (!$request->hasFile('archivo') || !$request->file('archivo')->isValid()) {
                return response()->json(['success' => false, 'msg' => 'Archivo no válido'], 422);
            }

            $file = $request->file('archivo');
            $doc = $this->crearDocumentoConArchivo($nota, $tipo, $file, $request->input('nombre'));
            $nota->save();
            $this->logMovimientoAdjunto($nota, 'ADJUNTO_AGREGADO', $tipo, $doc->nombre, $file->getClientOriginalName());

            return response()->json(['success' => true, 'documentos' => $this->documentosDeNota($idNota)]);
        } catch (\Throwable $e) {
            \Log::error("subirDocumento error: " . $e->getMessage());
            return response()->json(['success' => false, 'msg' => $e->getMessage()], 500);
        }
    }

    /** Sube una NUEVA versión a un documento existente. */
    public function subirVersionDocumento(Request $request, $idDoc)
    {
        try {
            $doc = \App\Models\NotaArchivoDocumento::findOrFail($idDoc);
            $nota = NotaIngreso::findOrFail($doc->id_nota_ingreso);
            if (!$request->hasFile('archivo') || !$request->file('archivo')->isValid()) {
                return response()->json(['success' => false, 'msg' => 'Archivo no válido'], 422);
            }

            $userId = session('id_usuario') ?? 1;
            $file = $request->file('archivo');
            $path = $this->guardarArchivoFisico($file, $this->folderPorTipo($doc->tipo_archivo));

            $version = \App\Models\NotaArchivoVersion::getNextVersion($nota->id, $doc->tipo_archivo, $doc->id);
            \App\Models\NotaArchivoVersion::create([
                'id_nota_ingreso' => $nota->id,
                'id_documento' => $doc->id,
                'tipo_archivo' => $doc->tipo_archivo,
                'version' => $version,
                'path_archivo' => $path,
                'nombre_original' => $file->getClientOriginalName(),
                'created_at' => \Carbon\Carbon::now(),
                'created_by' => $userId,
            ]);

            $campo = $this->campoPorTipo($doc->tipo_archivo);
            if ($campo) {
                $nota->$campo = $path;
                $nota->save();
            }

            $this->logMovimientoAdjunto($nota, 'ADJUNTO_REEMPLAZADO', $doc->tipo_archivo, $doc->nombre, $file->getClientOriginalName());

            return response()->json(['success' => true, 'documentos' => $this->documentosDeNota($nota->id)]);
        } catch (\Throwable $e) {
            \Log::error("subirVersionDocumento error: " . $e->getMessage());
            return response()->json(['success' => false, 'msg' => $e->getMessage()], 500);
        }
    }

    /** Versiones de un documento (para el desplegable de versiones). */
    public function getVersionesDocumento($idDoc)
    {
        $versiones = \App\Models\NotaArchivoVersion::getVersionsByDocumento($idDoc);
        return response()->json([
            'success' => true,
            'versiones' => $versiones->map(function ($v) {
                return [
                    'version_id' => $v->id,
                    'version' => $v->version,
                    'nombre_original' => $v->nombre_original ?: basename($v->path_archivo),
                    'path' => $v->path_archivo,
                    'created_at' => $v->created_at ? \Carbon\Carbon::parse($v->created_at)->format('d/m/Y H:i') : null,
                ];
            })->values(),
        ]);
    }

    /** Elimina un documento completo: sus versiones, archivos físicos y el registro. */
    public function eliminarDocumento($idDoc)
    {
        if (!$this->puedeEliminar()) {
            return response()->json(['success' => false, 'msg' => 'No tiene permisos para eliminar'], 403);
        }
        try {
            $doc = \App\Models\NotaArchivoDocumento::findOrFail($idDoc);
            $nota = NotaIngreso::find($doc->id_nota_ingreso);
            $tipo = $doc->tipo_archivo;

            $versiones = \App\Models\NotaArchivoVersion::where('id_documento', $idDoc)->get();
            foreach ($versiones as $v) {
                Storage::disk('public')->delete($v->path_archivo);
            }
            \App\Models\NotaArchivoVersion::where('id_documento', $idDoc)->delete();
            $nombreDoc = $doc->nombre;
            $doc->delete();

            // Recalcular path_* del tipo: apuntar al último archivo que quede (o null).
            if ($nota) {
                $campo = $this->campoPorTipo($tipo);
                if ($campo) {
                    $ultima = \App\Models\NotaArchivoVersion::where('id_nota_ingreso', $nota->id)
                        ->where('tipo_archivo', $tipo)
                        ->orderBy('version', 'desc')->orderBy('id', 'desc')->first();
                    $nota->$campo = $ultima ? $ultima->path_archivo : null;
                    $nota->save();
                }
                $this->logMovimientoAdjunto($nota, 'ADJUNTO_ELIMINADO', $tipo, $nombreDoc, null);
            }

            return response()->json(['success' => true, 'documentos' => $nota ? $this->documentosDeNota($nota->id) : []]);
        } catch (\Throwable $e) {
            \Log::error("eliminarDocumento error: " . $e->getMessage());
            return response()->json(['success' => false, 'msg' => $e->getMessage()], 500);
        }
    }

    public function eliminarMasivo(Request $request)
    {
        if (!$this->puedeEliminarNotas()) {
            return response()->json(['success' => false, 'msg' => 'No tiene permisos para eliminar'], 403);
        }
        try {
            $ids = $request->ids;
            if (!is_array($ids) || count($ids) == 0)
                return response()->json(['success' => false, 'msg' => 'No IDs provided'], 400);

            DB::transaction(function () use ($ids) {
                NotaIngreso::whereIn('id', $ids)->delete();
            });

            return response()->json(['success' => true]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'msg' => $e->getMessage()], 500);
        }
    }


    // ! COLLABORATIVE FLOW
    public function flujoColaborativo(Request $request)
    {
        try {
            $id_nota = $request->id_nota;
            $accion = $request->accion;

            if (!$id_nota)
                return response()->json(['success' => false, 'msg' => 'ID missing'], 400);

            DB::transaction(function () use ($id_nota, $accion) {
                // We update the state of the note's first expediente
                $nota = NotaIngreso::findOrFail($id_nota);

                // Assuming we use Expediente status for tracking workflow
                $exp = $nota->expedientes()->first(); // Or orderBy created_at dest

                if ($exp) {
                    if ($accion == 'SOLICITAR_MKT') {
                        $exp->estado_actual = NotaEstado::PENDIENTE_ADJUNTOS;
                        $exp->save();

                        // Create movement log
                        $mov = new \App\Models\Movimiento();
                        $mov->id_expediente_nota = $exp->id;
                        $mov->id_usuario = session('id_usuario') ?? 1;
                        $mov->fecha_movimiento = \Carbon\Carbon::now();
                        $mov->accion = 'SOLICITUD';
                        $usuarioSol = \App\Usuario::find(session('id_usuario'));
                        $mov->comentario = ($usuarioSol ? $usuarioSol->nombre : 'Usuario') . ' solicitó carga de adjuntos a Marketing';
                        $mov->save();
                    }
                }
            });

            return response()->json(['success' => true]);

        } catch (\Exception $e) {
            return response()->json(['success' => false, 'msg' => $e->getMessage()], 500);
        }
    }

    /**
     * Obtener versiones de un archivo para comparación
     */
    public function getVersionesArchivo($id, $tipo)
    {
        try {
            $versiones = \App\Models\NotaArchivoVersion::getVersions($id, $tipo);

            return response()->json([
                'success' => true,
                'versiones' => $versiones->map(function ($v) {
                    return [
                        'id' => $v->id,
                        'version' => $v->version,
                        'nombre_original' => $v->nombre_original,
                        'created_at' => $v->created_at->format('d/m/Y H:i'),
                        'path' => $v->path_archivo
                    ];
                })
            ]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'msg' => $e->getMessage()], 500);
        }
    }

    /**
     * Visualizar una versión específica de un archivo
     */
    public function visualizarVersion($idVersion)
    {
        try {
            $version = \App\Models\NotaArchivoVersion::findOrFail($idVersion);
            $path = $version->path_archivo;

            if (!Storage::disk('public')->exists($path)) {
                abort(404, 'Archivo no encontrado');
            }

            $fullPath = Storage::disk('public')->path($path);
            $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));

            if ($extension === 'pdf') {
                return response()->file($fullPath, [
                    'Content-Type' => 'application/pdf',
                    'Content-Disposition' => 'inline; filename="' . basename($path) . '"'
                ]);
            }

            return response()->file($fullPath);

        } catch (\Exception $e) {
            abort(404, 'Versión no encontrada');
        }
    }
    /**
     * Obtener historial de versiones de un archivo (AJAX)
     */
    public function getHistorialVersionesAjax($id, $tipo)
    {
        try {
            $versiones = \App\Models\NotaArchivoVersion::where('id_nota_ingreso', $id)
                ->where('tipo_archivo', $tipo)
                ->orderBy('version', 'desc')
                ->get();

            return response()->json([
                'success' => true,
                'versiones' => $versiones->map(function ($v) {
                    return [
                        'id' => $v->id,
                        'version' => $v->version,
                        'nombre_original' => $v->nombre_original,
                        'created_at' => $v->created_at->format('d/m/Y H:i'),
                        'path' => $v->path_archivo
                    ];
                })
            ]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'msg' => $e->getMessage()], 500);
        }
    }

    // ==================== NOTAS DE APROBACIÓN ====================

    /**
     * Subir nota(s) de aprobación para un grupo
     */
    public function subirNotaAprobacion(Request $request)
    {
        try {
            $idGrupo = $request->input('id_grupo');
            $tipoRama = $request->input('tipo_rama'); // MKT o FISC
            $tipoDocumento = $request->input('tipo_documento', ''); // NOTA o DISPOSICION
            $numeroDocumento = $request->input('numero_documento', '');
            $anioDocumento = $request->input('anio_documento', date('Y'));
            $disk = 'public';
            $userId = session('id_usuario') ?? Auth::id() ?? 1;

            if (!$idGrupo || !$tipoRama) {
                return response()->json(['success' => false, 'msg' => 'Faltan datos requeridos'], 400);
            }
            if (!$tipoDocumento || !$numeroDocumento) {
                return response()->json(['success' => false, 'msg' => 'El tipo y número de documento son obligatorios'], 400);
            }

            // Normalizar número a 4 dígitos con ceros (ej: "4" -> "0004")
            $numeroDocumento = $this->normalizarNumeroAprobacion($numeroDocumento);
            if (!$numeroDocumento) {
                return response()->json(['success' => false, 'msg' => 'El número de documento debe ser numérico'], 400);
            }

            // Validar unicidad numérica por (tipo_documento, año), ignorando rama/casino: "4" y "0004" cuentan como el mismo
            $existe = DB::table('grupo_notas_aprobacion')
                ->where('tipo_documento', $tipoDocumento)
                ->where('anio_documento', $anioDocumento)
                ->whereRaw('CAST(numero_documento AS UNSIGNED) = ?', [(int) $numeroDocumento])
                ->exists();
            if ($existe) {
                $label = $tipoDocumento === 'DISPOSICION' ? 'Disposición' : 'Nota';
                return response()->json(['success' => false, 'msg' => "Ya existe una {$label} N° {$numeroDocumento}-{$anioDocumento}. El número debe ser único."], 400);
            }

            $grupo = \App\Models\GrupoTramite::findOrFail($idGrupo);
            $subidos = [];

            $archivos = $request->file('archivos_aprobacion');
            if (!$archivos || !is_array($archivos)) {
                $archivos = $request->file('archivos_aprobacion') ? [$request->file('archivos_aprobacion')] : [];
            }

            foreach ($archivos as $file) {
                if (!$file || !$file->isValid())
                    continue;

                $originalName = $file->getClientOriginalName();
                $extension = $file->getClientOriginalExtension();
                $baseName = pathinfo($originalName, PATHINFO_FILENAME);
                $uniqueName = time() . '_' . preg_replace('/[^a-zA-Z0-9_-]/', '_', $baseName) . '.' . $extension;
                $path = $file->storeAs('notas_aprobacion', $uniqueName, $disk);

                DB::table('grupo_notas_aprobacion')->insert([
                    'id_grupo' => $idGrupo,
                    'tipo_rama' => $tipoRama,
                    'tipo_documento' => $tipoDocumento,
                    'numero_documento' => $numeroDocumento,
                    'anio_documento' => $anioDocumento,
                    'path_archivo' => $path,
                    'nombre_original' => $originalName,
                    'created_by' => $userId,
                    'created_at' => \Carbon\Carbon::now(),
                    'updated_at' => \Carbon\Carbon::now(),
                ]);

                $subidos[] = $originalName;
            }

            if (count($subidos) > 0) {
                $label = $tipoDocumento === 'DISPOSICION' ? 'Disposición' : 'Nota';
                self::registrarMovimientoEnGrupo(
                    $grupo,
                    'NOTA_APROBACION_AGREGADA',
                    'subió ' . $label . ' de aprobación N° ' . $numeroDocumento . '-' . $anioDocumento . ' (' . $tipoRama . ')'
                );
            }

            return response()->json([
                'success' => true,
                'msg' => count($subidos) . ' nota(s) de aprobación subida(s)',
                'archivos' => $subidos,
            ]);
        } catch (\Throwable $e) {
            \Log::error("Error subirNotaAprobacion: " . $e->getMessage());
            return response()->json(['success' => false, 'msg' => $e->getMessage()], 500);
        }
    }

    /**
     * Visualizar (inline) una nota de aprobación
     */
    public function visualizarNotaAprobacion($id)
    {
        $registro = DB::table('grupo_notas_aprobacion')->where('id', $id)->first();
        if (!$registro)
            abort(404);

        $disk = Storage::disk('public');
        if (!$disk->exists($registro->path_archivo))
            abort(404);

        $fullPath = $disk->path($registro->path_archivo);
        $mime = $disk->mimeType($registro->path_archivo);

        return response()->file($fullPath, [
            'Content-Type' => $mime,
            'Content-Disposition' => 'inline; filename="' . $registro->nombre_original . '"',
        ]);
    }

    /**
     * Descargar una nota de aprobación
     */
    public function descargarNotaAprobacion($id)
    {
        $registro = DB::table('grupo_notas_aprobacion')->where('id', $id)->first();
        if (!$registro)
            abort(404);

        $disk = Storage::disk('public');
        if (!$disk->exists($registro->path_archivo))
            abort(404);

        return response()->download($disk->path($registro->path_archivo), $registro->nombre_original);
    }

    /**
     * Eliminar una nota de aprobación
     */
    public function eliminarNotaAprobacion($id)
    {
        if (!$this->puedeEliminar()) {
            return response()->json(['success' => false, 'msg' => 'No tiene permisos para eliminar'], 403);
        }
        try {
            $registro = DB::table('grupo_notas_aprobacion')->where('id', $id)->first();
            if (!$registro) {
                return response()->json(['success' => false, 'msg' => 'No encontrado'], 404);
            }

            // Eliminar archivo físico
            $disk = Storage::disk('public');
            if ($disk->exists($registro->path_archivo)) {
                $disk->delete($registro->path_archivo);
            }

            DB::table('grupo_notas_aprobacion')->where('id', $id)->delete();

            // Trazar en todas las notas del grupo afectado.
            $grupo = \App\Models\GrupoTramite::find($registro->id_grupo);
            if ($grupo) {
                $label = (isset($registro->tipo_documento) && $registro->tipo_documento === 'DISPOSICION') ? 'Disposición' : 'Nota';
                self::registrarMovimientoEnGrupo(
                    $grupo,
                    'NOTA_APROBACION_ELIMINADA',
                    'eliminó ' . $label . ' de aprobación N° ' . ($registro->numero_documento ?? '?') . '-' . ($registro->anio_documento ?? '?') . ' (' . ($registro->tipo_rama ?? '?') . ')'
                );
            }

            return response()->json(['success' => true]);
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'msg' => $e->getMessage()], 500);
        }
    }

    /**
     * Normaliza el número de documento de una nota de aprobación a 4 dígitos con ceros (ej: "4" -> "0004").
     * Devuelve null si no contiene dígitos o equivale a 0.
     */
    private function normalizarNumeroAprobacion($numero)
    {
        $digitos = preg_replace('/\D/', '', (string) $numero);
        if ($digitos === '' || (int) $digitos === 0) {
            return null;
        }
        // Quitar ceros a la izquierda y rellenar a un mínimo de 4 dígitos
        return str_pad(ltrim($digitos, '0'), 4, '0', STR_PAD_LEFT);
    }

    /**
     * Editar los datos (rama, tipo, número, año) de una nota de aprobación existente.
     * Revalida la unicidad del número por (tipo_documento, año) excluyendo el propio registro.
     */
    public function updateNotaAprobacion(Request $request, $id)
    {
        try {
            $registro = DB::table('grupo_notas_aprobacion')->where('id', $id)->first();
            if (!$registro) {
                return response()->json(['success' => false, 'msg' => 'No encontrado'], 404);
            }

            $tipoRama = $request->input('tipo_rama', $registro->tipo_rama);
            $tipoDocumento = $request->input('tipo_documento', $registro->tipo_documento);
            $numeroDocumento = $request->input('numero_documento', $registro->numero_documento);
            $anioDocumento = $request->input('anio_documento', $registro->anio_documento);

            if (!$tipoRama || !$tipoDocumento || !$numeroDocumento || !$anioDocumento) {
                return response()->json(['success' => false, 'msg' => 'Faltan datos requeridos'], 400);
            }

            // Normalizar número a 4 dígitos con ceros (ej: "4" -> "0004")
            $numeroDocumento = $this->normalizarNumeroAprobacion($numeroDocumento);
            if (!$numeroDocumento) {
                return response()->json(['success' => false, 'msg' => 'El número de documento debe ser numérico'], 400);
            }

            // Unicidad numérica por (tipo_documento, año), excluyendo este mismo registro
            $existe = DB::table('grupo_notas_aprobacion')
                ->where('id', '<>', $id)
                ->where('tipo_documento', $tipoDocumento)
                ->where('anio_documento', $anioDocumento)
                ->whereRaw('CAST(numero_documento AS UNSIGNED) = ?', [(int) $numeroDocumento])
                ->exists();
            if ($existe) {
                $label = $tipoDocumento === 'DISPOSICION' ? 'Disposición' : 'Nota';
                return response()->json(['success' => false, 'msg' => "Ya existe una {$label} N° {$numeroDocumento}-{$anioDocumento}. El número debe ser único."], 400);
            }

            DB::table('grupo_notas_aprobacion')->where('id', $id)->update([
                'tipo_rama' => $tipoRama,
                'tipo_documento' => $tipoDocumento,
                'numero_documento' => $numeroDocumento,
                'anio_documento' => $anioDocumento,
                'updated_at' => \Carbon\Carbon::now(),
            ]);

            // Trazar el cambio en el grupo (solo si efectivamente cambió la identidad del documento)
            $grupo = \App\Models\GrupoTramite::find($registro->id_grupo);
            if ($grupo) {
                $labelAnt = ($registro->tipo_documento === 'DISPOSICION') ? 'Disposición' : 'Nota';
                $labelNue = ($tipoDocumento === 'DISPOSICION') ? 'Disposición' : 'Nota';
                $antes = $labelAnt . ' N° ' . ($registro->numero_documento ?: '?') . '-' . ($registro->anio_documento ?: '?') . ' (' . $registro->tipo_rama . ')';
                $despues = $labelNue . ' N° ' . $numeroDocumento . '-' . $anioDocumento . ' (' . $tipoRama . ')';
                if ($antes !== $despues) {
                    self::registrarMovimientoEnGrupo(
                        $grupo,
                        'NOTA_APROBACION_EDITADA',
                        'editó nota de aprobación: ' . $antes . ' → ' . $despues
                    );
                }
            }

            return response()->json([
                'success' => true,
                'msg' => 'Nota de aprobación actualizada',
                'numero_documento' => $numeroDocumento,
            ]);
        } catch (\Throwable $e) {
            \Log::error("Error updateNotaAprobacion: " . $e->getMessage());
            return response()->json(['success' => false, 'msg' => $e->getMessage()], 500);
        }
    }

    /**
     * Devuelve el próximo número correlativo (4 dígitos) para una nota de aprobación.
     * El correlativo es por (tipo_documento, año), ignorando rama y casino.
     */
    public function proximoNumeroAprobacion(Request $request)
    {
        $tipoDocumento = $request->input('tipo_documento');
        $anioDocumento = $request->input('anio_documento', date('Y'));

        if (!$tipoDocumento) {
            return response()->json(['success' => false, 'msg' => 'Falta el tipo de documento'], 400);
        }

        $maximo = DB::table('grupo_notas_aprobacion')
            ->where('tipo_documento', $tipoDocumento)
            ->where('anio_documento', $anioDocumento)
            ->max(DB::raw('CAST(numero_documento AS UNSIGNED)'));

        $proximo = (int) $maximo + 1;

        return response()->json([
            'success' => true,
            'numero' => str_pad($proximo, 4, '0', STR_PAD_LEFT),
            'numero_int' => $proximo,
        ]);
    }

    /**
     * Verificar si existe un grupo con los datos ingresados en el wizard.
     * Responde: { existe, grupo: {id,nro_nota,anio,titulo,casino,ramas}, rama_ya_existe }
     */
    public function verificarGrupoExistente(Request $request)
    {
        $nroNota = $request->nro_nota;
        $anio = $request->anio;
        $idCasino = $request->id_casino;
        $idPlataforma = $request->id_plataforma;
        $tipoTarea = $request->tipo_tarea;

        if (!$nroNota || !$anio || (!$idCasino && !$idPlataforma)) {
            return response()->json(['existe' => false]);
        }

        $q = \App\Models\GrupoTramite::with('casino')
            ->where('nro_nota', $nroNota)
            ->where('anio', $anio);
        if ($idPlataforma) {
            $q->where('id_plataforma', $idPlataforma);
        } else {
            $q->where('id_casino', $idCasino);
        }
        $grupo = $q->first();

        if (!$grupo) {
            return response()->json(['existe' => false]);
        }

        $ramas = $grupo->notas()->pluck('tipo_rama')->toArray();
        $ramaNueva = ($tipoTarea === 'MARKETING') ? 'MKT' : (($tipoTarea === 'FISCALIZACION') ? 'FISC' : null);

        return response()->json([
            'existe' => true,
            'rama_ya_existe' => $ramaNueva ? in_array($ramaNueva, $ramas) : false,
            'grupo' => [
                'id' => $grupo->id,
                'nro_nota' => $grupo->nro_nota,
                'anio' => $grupo->anio,
                'titulo' => $grupo->titulo,
                'casino' => $grupo->casino ? $grupo->casino->nombre : self::resolverNombreCasino($grupo->id_casino, $grupo->id_plataforma),
                'ramas' => $ramas,
            ],
        ]);
    }

    /**
     * Buscar grupos para vincular como nota padre
     */
    public function buscarGrupos(Request $request)
    {
        $q = $request->q;
        if (!$q || strlen($q) < 2) {
            return response()->json([]);
        }

        $resultados = \App\Models\GrupoTramite::with('casino')
            ->where(function ($query) use ($q) {
                $query->where('nro_nota', 'like', '%' . $q . '%')
                    ->orWhere('titulo', 'like', '%' . $q . '%');
            })
            ->orderBy('anio', 'desc')
            ->orderBy('nro_nota', 'desc')
            ->take(15)
            ->get()
            ->map(function ($g) {
                $ramas = $g->notas()->pluck('tipo_rama')->toArray();
                return [
                    'id' => $g->id,
                    'nro_nota' => $g->nro_nota,
                    'anio' => $g->anio,
                    'titulo' => $g->titulo,
                    'casino' => $g->casino ? $g->casino->nombre : self::resolverNombreCasino($g->id_casino, $g->id_plataforma),
                    'ramas' => $ramas,
                ];
            });

        return response()->json($resultados);
    }

    /**
     * Asignar relación padre a un grupo
     */
    public function asignarGrupoPadre(Request $request)
    {
        try {
            $grupo = \App\Models\GrupoTramite::findOrFail($request->id_grupo);
            $padreId = $request->id_grupo_padre;

            // Validar que no se relacione consigo mismo
            if ($padreId == $grupo->id) {
                return response()->json(['success' => false, 'msg' => 'No se puede relacionar un grupo consigo mismo.'], 422);
            }

            // Validar que no genere circularidad
            if ($padreId) {
                $actual = \App\Models\GrupoTramite::find($padreId);
                while ($actual && $actual->id_grupo_padre) {
                    if ($actual->id_grupo_padre == $grupo->id) {
                        return response()->json(['success' => false, 'msg' => 'Relación circular detectada.'], 422);
                    }
                    $actual = \App\Models\GrupoTramite::find($actual->id_grupo_padre);
                }
            }

            $grupo->id_grupo_padre = $padreId;
            $grupo->save();

            // Trazar en ambos extremos: las notas del hijo (este grupo) y las del padre.
            $padre = $padreId ? \App\Models\GrupoTramite::find($padreId) : null;
            if ($padre) {
                self::registrarMovimientoEnGrupo(
                    $grupo,
                    'GRUPO_PADRE_ASIGNADO',
                    'vinculó este trámite como hijo de N° ' . $padre->nro_nota . '-' . $padre->anio
                );
                self::registrarMovimientoEnGrupo(
                    $padre,
                    'GRUPO_PADRE_ASIGNADO',
                    'vinculó el trámite N° ' . $grupo->nro_nota . '-' . $grupo->anio . ' como hijo'
                );
            }

            return response()->json(['success' => true]);
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'msg' => $e->getMessage()], 500);
        }
    }

    /**
     * Quitar relación padre de un grupo
     */
    public function quitarGrupoPadre(Request $request)
    {
        try {
            $grupo = \App\Models\GrupoTramite::findOrFail($request->id_grupo);
            $exPadreId = $grupo->id_grupo_padre;
            $grupo->id_grupo_padre = null;
            $grupo->save();

            // Trazar en ambos extremos si había un padre.
            $exPadre = $exPadreId ? \App\Models\GrupoTramite::find($exPadreId) : null;
            if ($exPadre) {
                self::registrarMovimientoEnGrupo(
                    $grupo,
                    'GRUPO_PADRE_QUITADO',
                    'quitó el vínculo como hijo de N° ' . $exPadre->nro_nota . '-' . $exPadre->anio
                );
                self::registrarMovimientoEnGrupo(
                    $exPadre,
                    'GRUPO_PADRE_QUITADO',
                    'se quitó el vínculo con el trámite hijo N° ' . $grupo->nro_nota . '-' . $grupo->anio
                );
            }

            return response()->json(['success' => true]);
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'msg' => $e->getMessage()], 500);
        }
    }

    // =============================================
    // GESTIÓN DE MAILS — ABM Destinatarios y Transiciones
    // =============================================

    /**
     * Alcance del usuario actual sobre destinatarios de mail.
     * Retorna ['sinFiltro' => bool, 'casinos' => int[], 'plataformas' => int[], 'esAdmin' => bool]
     * sinFiltro=true => ve/edita todo (super, auditor, despacho, administrador, control)
     */
    private function obtenerAlcanceMails($usuario, $id_usuario)
    {
        $esAdmin = $usuario->es_superusuario || $usuario->es_administrador
            || $usuario->es_auditor || $usuario->es_despacho || $usuario->es_control;
        $sinFiltro = $esAdmin;

        if ($sinFiltro) {
            return ['sinFiltro' => true, 'casinos' => [], 'plataformas' => [], 'esAdmin' => true];
        }

        $rolesNotas = DB::table('usuario_tiene_rol')
            ->join('rol', 'rol.id_rol', '=', 'usuario_tiene_rol.id_rol')
            ->where('usuario_tiene_rol.id_usuario', $id_usuario)
            ->where('rol.descripcion', 'LIKE', 'CARGA\_NOTAS\_%')
            ->pluck('rol.descripcion')
            ->toArray();

        $casinos = [];
        if (in_array('CARGA_NOTAS_UNIFICADAS', $rolesNotas)) {
            $casinos = DB::table('usuario_tiene_casino')
                ->where('id_usuario', $id_usuario)
                ->pluck('id_casino')
                ->map(function ($v) {
                    return (int) $v;
                })
                ->toArray();
        }

        $rolesPlataforma = array_filter($rolesNotas, function ($r) {
            return $r !== 'CARGA_NOTAS_UNIFICADAS';
        });
        $plataformas = [];
        if (!empty($rolesPlataforma)) {
            $plataformasOnlineData = self::obtenerPlataformasOnline();
            foreach ($rolesPlataforma as $rolDesc) {
                $codigo = str_replace('CARGA_NOTAS_', '', $rolDesc);
                foreach ($plataformasOnlineData as $p) {
                    if ($p->codigo === $codigo) {
                        $plataformas[] = (int) $p->id_plataforma;
                        break;
                    }
                }
            }
        }

        return ['sinFiltro' => false, 'casinos' => $casinos, 'plataformas' => $plataformas, 'esAdmin' => false];
    }

    /**
     * Chequea si el usuario puede operar sobre un destinatario (por id_casino/id_plataforma del registro).
     */
    private function destinatarioEnAlcance($idCasino, $idPlataforma, array $alcance)
    {
        if ($alcance['sinFiltro'])
            return true;
        if ($idCasino !== null && in_array((int) $idCasino, $alcance['casinos'], true))
            return true;
        if ($idPlataforma !== null && in_array((int) $idPlataforma, $alcance['plataformas'], true))
            return true;
        return false;
    }

    public function getMailDestinatarios()
    {
        $id_usuario = session('id_usuario');
        $usuario = UsuarioController::getInstancia()->buscarUsuario($id_usuario)['usuario'];
        $alcance = $this->obtenerAlcanceMails($usuario, $id_usuario);

        $query = DB::table('nota_mail_destinatarios')->where('activo', 1);

        if (!$alcance['sinFiltro']) {
            $hayC = !empty($alcance['casinos']);
            $hayP = !empty($alcance['plataformas']);
            if (!$hayC && !$hayP) {
                $query->whereRaw('0 = 1');
            } else {
                $query->where(function ($q) use ($alcance, $hayC, $hayP) {
                    if ($hayC)
                        $q->orWhereIn('id_casino', $alcance['casinos']);
                    if ($hayP)
                        $q->orWhereIn('id_plataforma', $alcance['plataformas']);
                });
            }
        }

        $destinatarios = $query->orderBy('categoria')->orderBy('nombre')->get();

        // Resolver nombre de casino/plataforma
        foreach ($destinatarios as $d) {
            $d->nombre_casino = null;
            if ($d->id_casino) {
                $casino = \App\Casino::find($d->id_casino);
                $d->nombre_casino = $casino ? $casino->nombre : 'Casino #' . $d->id_casino;
            } elseif ($d->id_plataforma) {
                $plat = DB::table('plataforma')->where('id_plataforma', $d->id_plataforma)->first();
                $d->nombre_casino = $plat ? $plat->nombre : 'Plataforma #' . $d->id_plataforma;
            }
        }

        return response()->json($destinatarios);
    }

    public function storeMailDestinatario(Request $request)
    {
        $id_usuario = session('id_usuario');
        $usuario = UsuarioController::getInstancia()->buscarUsuario($id_usuario)['usuario'];
        $alcance = $this->obtenerAlcanceMails($usuario, $id_usuario);

        $idCasino = null;
        $idPlataforma = null;
        if ($request->id_casino_plat) {
            $val = $request->id_casino_plat;
            if (strpos($val, 'p_') === 0) {
                $idPlataforma = (int) str_replace('p_', '', $val);
            } elseif (strpos($val, 'c_') === 0) {
                $idCasino = (int) str_replace('c_', '', $val);
            }
        }

        if (!$this->destinatarioEnAlcance($idCasino, $idPlataforma, $alcance)) {
            return response()->json(['success' => false, 'msg' => 'No tiene permisos sobre ese casino/plataforma'], 403);
        }

        $id = DB::table('nota_mail_destinatarios')->insertGetId([
            'email' => $request->email,
            'nombre' => $request->nombre,
            'categoria' => $request->categoria,
            'id_casino' => $idCasino,
            'id_plataforma' => $idPlataforma,
            'activo' => 1,
            'created_at' => \Carbon\Carbon::now(),
            'updated_at' => \Carbon\Carbon::now(),
        ]);
        return response()->json(['success' => true, 'id' => $id]);
    }

    public function updateMailDestinatario(Request $request, $id)
    {
        $id_usuario = session('id_usuario');
        $usuario = UsuarioController::getInstancia()->buscarUsuario($id_usuario)['usuario'];
        $alcance = $this->obtenerAlcanceMails($usuario, $id_usuario);

        $actual = DB::table('nota_mail_destinatarios')->where('id', $id)->first();
        if (!$actual) {
            return response()->json(['success' => false, 'msg' => 'Destinatario no encontrado'], 404);
        }
        if (!$this->destinatarioEnAlcance($actual->id_casino, $actual->id_plataforma, $alcance)) {
            return response()->json(['success' => false, 'msg' => 'No tiene permisos sobre este destinatario'], 403);
        }

        $idCasino = null;
        $idPlataforma = null;
        if ($request->id_casino_plat) {
            $val = $request->id_casino_plat;
            if (strpos($val, 'p_') === 0) {
                $idPlataforma = (int) str_replace('p_', '', $val);
            } elseif (strpos($val, 'c_') === 0) {
                $idCasino = (int) str_replace('c_', '', $val);
            }
        }

        if (!$this->destinatarioEnAlcance($idCasino, $idPlataforma, $alcance)) {
            return response()->json(['success' => false, 'msg' => 'No tiene permisos sobre ese casino/plataforma'], 403);
        }

        DB::table('nota_mail_destinatarios')->where('id', $id)->update([
            'email' => $request->email,
            'nombre' => $request->nombre,
            'categoria' => $request->categoria,
            'id_casino' => $idCasino,
            'id_plataforma' => $idPlataforma,
            'updated_at' => \Carbon\Carbon::now(),
        ]);
        return response()->json(['success' => true]);
    }

    public function deleteMailDestinatario($id)
    {
        $id_usuario = session('id_usuario');
        $usuario = UsuarioController::getInstancia()->buscarUsuario($id_usuario)['usuario'];
        $alcance = $this->obtenerAlcanceMails($usuario, $id_usuario);

        $actual = DB::table('nota_mail_destinatarios')->where('id', $id)->first();
        if (!$actual) {
            return response()->json(['success' => false, 'msg' => 'Destinatario no encontrado'], 404);
        }
        if (!$this->destinatarioEnAlcance($actual->id_casino, $actual->id_plataforma, $alcance)) {
            return response()->json(['success' => false, 'msg' => 'No tiene permisos sobre este destinatario'], 403);
        }

        DB::table('nota_mail_destinatarios')->where('id', $id)->update([
            'activo' => 0,
            'updated_at' => \Carbon\Carbon::now(),
        ]);
        return response()->json(['success' => true]);
    }

    public function getMailTransiciones(Request $request)
    {
        $query = DB::table('nota_mail_transiciones as t')
            ->leftJoin('nota_estados as eo', 'eo.id', '=', 't.id_estado_origen')
            ->join('nota_estados as ed', 'ed.id', '=', 't.id_estado_destino')
            ->leftJoin('nota_tipos_evento as te', 'te.id', '=', 't.id_tipo_evento')
            ->where('t.activo', 1)
            ->select(
                't.id',
                't.id_estado_origen',
                't.id_estado_destino',
                't.categoria',
                't.id_tipo_evento',
                DB::raw("IFNULL(eo.descripcion, 'AL CREAR') as estado_origen"),
                'ed.descripcion as estado_destino',
                'te.descripcion as tipo_evento_nombre',
                DB::raw("IFNULL(eo.orden, 0) as orden_origen")
            )
            ->orderBy('orden_origen');

        if ($request->categoria) {
            $query->where('t.categoria', $request->categoria);
        }

        return response()->json($query->get());
    }

    public function storeMailTransicion(Request $request)
    {
        $id_usuario = session('id_usuario');
        $usuario = UsuarioController::getInstancia()->buscarUsuario($id_usuario)['usuario'];
        $alcance = $this->obtenerAlcanceMails($usuario, $id_usuario);
        if (!$alcance['esAdmin']) {
            return response()->json(['success' => false, 'msg' => 'No tiene permisos para editar transiciones'], 403);
        }

        $idTipoEvento = (int) ($request->id_tipo_evento ?: 0);

        // Evitar duplicados
        $existe = DB::table('nota_mail_transiciones')
            ->where('id_estado_origen', $request->id_estado_origen)
            ->where('id_estado_destino', $request->id_estado_destino)
            ->where('categoria', $request->categoria)
            ->where('id_tipo_evento', $idTipoEvento)
            ->where('activo', 1)
            ->exists();
        if ($existe) {
            return response()->json(['success' => false, 'msg' => 'Esa transición ya existe para esta categoría y tipo'], 422);
        }

        $id = DB::table('nota_mail_transiciones')->insertGetId([
            'id_estado_origen' => $request->id_estado_origen,
            'id_estado_destino' => $request->id_estado_destino,
            'categoria' => $request->categoria,
            'id_tipo_evento' => $idTipoEvento,
            'activo' => 1,
            'created_at' => \Carbon\Carbon::now(),
            'updated_at' => \Carbon\Carbon::now(),
        ]);
        return response()->json(['success' => true, 'id' => $id]);
    }

    public function deleteMailTransicion($id)
    {
        $id_usuario = session('id_usuario');
        $usuario = UsuarioController::getInstancia()->buscarUsuario($id_usuario)['usuario'];
        $alcance = $this->obtenerAlcanceMails($usuario, $id_usuario);
        if (!$alcance['esAdmin']) {
            return response()->json(['success' => false, 'msg' => 'No tiene permisos para editar transiciones'], 403);
        }

        DB::table('nota_mail_transiciones')->where('id', $id)->update([
            'activo' => 0,
            'updated_at' => \Carbon\Carbon::now(),
        ]);
        return response()->json(['success' => true]);
    }
}
