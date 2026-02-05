<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Usuario;

use App\DenunciasAlea_estado;
use App\DenunciasAlea_plataforma;
use App\DenunciasAlea_paginas;
use App\DenunciasAlea_denunciadoEn;

use View;

use Illuminate\Support\Str;
use Illuminate\Support\Arr;
use Illuminate\Http\UploadedFile;
use Maatwebsite\Excel\Facades\Excel;
use Barryvdh\DomPDF\Facade\Pdf;
use Dompdf\Dompdf;
use Dompdf\Options;
use PHPExcel_Style_Alignment;
use PHPExcel_Style_Border;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Storage;


class denunciasAleaController extends Controller
{
    public function index(){

      $usuario = UsuarioController::getInstancia()->buscarUsuario(session('id_usuario'))['usuario'];


      $esControlador = 0;
      $usuario = Usuario::find(session('id_usuario'));
      foreach ($usuario->roles as $rol) {
          if ( $rol->descripcion == "SUPERUSUARIO") {
              $esControlador = 1;
          }
      }
      UsuarioController::getInstancia()->agregarSeccionReciente('Denuncias Alea', 'denunciasAlea');

      $plataformas = $this->getPlataformas();
      $estados = $this->getEstados();
      $lugares = $this->getLugares();

      return view('denunciasAlea', [
        'es_superusuario' => $esControlador,
        'plataformas' => $plataformas,
        'lugares' => $lugares,
        'estados' => $estados,
      ]);
    }

public function getPlataformas(){
  $plataformas = DenunciasAlea_plataforma::get();
  return $plataformas;
}
public function getEstados(){
  $estados = DenunciasAlea_estado::get();
  $estados->shift();
  return $estados;
}
public function getLugares(){
  $lugares = denunciasAlea_denunciadoEn::get();
  return $lugares;
}

public function guardarPagina(Request $request)
    {
        DB::beginTransaction();
        try {
            // --- Normalizaciones ---
            $link    = trim((string)$request->input('linkPaginadenunciasAlea_paginas', ''));
            if ($link !== '' && !preg_match('~^https?://~i', $link)) {
    $link = 'https://' . $link;
}
            $hostSld = $link ? $this->siteName($link) : '';

            // checkbox: hidden 0 + checkbox 1 → tomamos el valor final
            $denunciada = $request->input('denuncia_alea', 0);

            // Plataforma (1=Facebook, 2=Instagram) si no vino
            $plataformaId = $request->input('plataformadenunciasAlea_paginas');
            if ($plataformaId === null || $plataformaId === '') {
                if ($hostSld === 'facebook' || strpos($link, 'facebook.') !== false) {
                    $plataformaId = 1;
                } elseif ($hostSld === 'instagram' || strpos($link, 'instagram.') !== false) {
                    $plataformaId = 2;
                } else {
                    $plataformaId = null; // queda NULL si no se reconoce
                }
            }

            // Usuario de página si no vino
            $usuarioPag = $request->input('usuariodenunciasAlea_paginas');
            if ($usuarioPag === null || $usuarioPag === '') {
                if ($hostSld === 'instagram') {
                    $usuarioPag = ltrim($this->lastSegment($link), '@');
                } elseif ($hostSld === 'facebook') {
                    $idq = $this->queryParam($link, 'id'); // profile.php?id=123
                    $usuarioPag = $idq ? $idq : $this->lastSegment($link);
                } else {
                    $usuarioPag = $this->lastSegment($link);
                }
            }

            // --- Persistencia ---
            $pagina = new DenunciasAlea_paginas();
            $pagina->fecha           = $request->input('fecha_denunciasAlea_paginasPres');
            $pagina->denunciada      = $denunciada;
            $pagina->denunciado_en   = $request->input('lugardenunciasAlea_paginas') ?: null; // FK: que sea NULL, no 0
            $pagina->cant_denuncias  = (int)$request->input('CantdenunciasAlea_paginas', 0);
            $pagina->link_pagina     = $link;
            $pagina->estado_denuncia = $request->input('estadodenunciasAlea_paginas') ?: null;
            $pagina->user_pag        = $usuarioPag;
            $pagina->plataforma      = $plataformaId;
            $pagina->usuario         = UsuarioController::getInstancia()->quienSoy()['usuario']['id_usuario'];

            $pagina->save();

            DB::commit();
            return response()->json(['success' => true, 'id' => $pagina->getKey()]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    /* ======================= UPDATE ======================= */
    public function actualizarPagina(Request $request, $id)
    {
        DB::beginTransaction();
        try {
            $pagina = DenunciasAlea_paginas::findOrFail($id);

            // --- Normalizaciones ---
            $link    = trim((string)$request->input('linkPaginadenunciasAlea_paginas', ''));
            if ($link !== '' && !preg_match('~^https?://~i', $link)) {
    $link = 'https://' . $link;
}
            $hostSld = $link ? $this->siteName($link) : '';

            $denunciada = $request->input('denuncia_alea', 0);

            $plataformaId = $request->input('plataformadenunciasAlea_paginas');
            if ($plataformaId === null || $plataformaId === '') {
                if ($hostSld === 'facebook' || strpos($link, 'facebook.') !== false) {
                    $plataformaId = 1;
                } elseif ($hostSld === 'instagram' || strpos($link, 'instagram.') !== false) {
                    $plataformaId = 2;
                } else {
                    $plataformaId = null;
                }
            }

            $usuarioPag = $request->input('usuariodenunciasAlea_paginas');
            if ($usuarioPag === null || $usuarioPag === '') {
                if ($hostSld === 'instagram') {
                    $usuarioPag = ltrim($this->lastSegment($link), '@');
                } elseif ($hostSld === 'facebook') {
                    $idq = $this->queryParam($link, 'id');
                    $usuarioPag = $idq ? $idq : $this->lastSegment($link);
                } else {
                    $usuarioPag = $this->lastSegment($link);
                }
            }

            // --- Persistencia ---
            $pagina->fecha           = $request->input('fecha_denunciasAlea_paginasPres');
            $pagina->denunciada      = (int)$denunciada;
            $pagina->denunciado_en   = $request->input('lugardenunciasAlea_paginas') ?: null;
            $pagina->cant_denuncias  = (int)$request->input('CantdenunciasAlea_paginas', 0);
            $pagina->link_pagina     = $link;
            $pagina->estado_denuncia = $request->input('estadodenunciasAlea_paginas') ?: null;
            $pagina->user_pag        = $usuarioPag;
            $pagina->plataforma      = $plataformaId;
            $pagina->usuario         = UsuarioController::getInstancia()->quienSoy()['usuario']['id_usuario'];

            $pagina->save();

            DB::commit();
            return response()->json(['success' => true]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    /* ======================= HELPERS (separados) ======================= */
    private function siteName($url)
    {
        if (!preg_match('~^[a-z][a-z0-9+.-]*://~i', $url)) { $url = 'http://' . $url; }
        $host = strtolower(parse_url($url, PHP_URL_HOST));
        if (!$host) return '';
        $host = preg_replace('/^www\d*\./', '', $host);

        $parts = explode('.', $host);
        if (count($parts) < 2) return $host;

        $tld = array_pop($parts);
        $sld = array_pop($parts);

        $multi = array(
            'com.ar','net.ar','org.ar','gov.ar','edu.ar',
            'co.uk','org.uk','ac.uk',
            'com.br','com.mx','com.au','co.jp'
        );

        $maybe = $sld . '.' . $tld;
        if (count($parts) >= 1 && in_array($maybe, $multi, true)) {
            $tmp = array_pop($parts);
            if ($tmp) $sld = $tmp;
        }
        return $sld;
    }

    private function lastSegment($url)
    {
        if (!preg_match('~^[a-z][a-z0-9+.-]*://~i', $url)) { $url = 'http://' . $url; }
        $path = parse_url($url, PHP_URL_PATH);
        if (!$path || $path === '/') return '';
        $path = rtrim($path, '/');
        return basename($path);
    }

    private function queryParam($url, $key)
    {
        if (!preg_match('~^[a-z][a-z0-9+.-]*://~i', $url)) { $url = 'http://' . $url; }
        $q = parse_url($url, PHP_URL_QUERY);
        if (!$q) return null;
        parse_str($q, $arr);
        return isset($arr[$key]) ? (string)$arr[$key] : null;
    }

public function llenarPagina($id)
    {
        $r = DenunciasAlea_paginas::findOrFail($id);

        // Devolvemos claves con los MISMO names del form (para setear directo)
        return response()->json([
            'id_denunciasAlea_paginas'          => (int)$r->id_denunciasAlea_paginas,
            'fecha'                              => (string)$r->fecha,                       // para tu convertirMesAno
            'fecha_pres'                         => (string)$r->fecha,                       // tu form usa *_Pres
            'usuariodenunciasAlea_paginas'       => (string)$r->user_pag,
            'plataformadenunciasAlea_paginas'    => $r->plataforma,                          // ID (1/2 o null)
            'linkPaginadenunciasAlea_paginas'    => (string)$r->link_pagina,
            'denuncia_alea'                      => (int)$r->denunciada,                     // 0/1
            'CantdenunciasAlea_paginas'          => (int)$r->cant_denuncias,
            'estadodenunciasAlea_paginas'        => $r->estado_denuncia,                     // ID si es FK
            'lugardenunciasAlea_paginas'         => $r->denunciado_en,                       // ID si es FK
        ]);
    }

    public function estadosJson(){
      $rows = \App\DenunciasAlea_estado::get(['id_denunciasAlea_estado as id','estado as nombre']);
      return response()->json($rows);
    }

    public function setEstado(Request $req, $id)
{
    try {
        $raw = $req->input('estado_id', null);
        $val = ($raw === '' || $raw === null) ? null : (int)$raw;

        if (!is_null($val)) {
            $exists = \App\DenunciasAlea_estado::where('id_denunciasAlea_estado', $val)->exists();
            if (!$exists) {
                return response()->json(['ok'=>false,'error'=>'Estado inválido'], 422);
            }
        }

        $row = \App\DenunciasAlea_paginas::findOrFail($id);
        $row->estado_denuncia = $val;
        $row->save();

        $nombre = $val ? \App\DenunciasAlea_estado::find($val)->estado : '';

        return response()->json([
            'ok'            => true,
            'id'            => $row->getKey(),
            'estado_id'     => $val,
            'estado_nombre' => $nombre,
        ]);
    } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
        return response()->json(['ok'=>false,'error'=>'Registro inexistente'], 404);
    } catch (\Throwable $e) {
        return response()->json(['ok'=>false,'error'=>$e->getMessage()], 500);
    }
}


    public function ultimasDenuncias(Request $request)
    {
        $page    = max(1, (int)$request->query('page', 1));
        $perPage = max(1, (int)$request->query('page_size', 20));

        $query = DenunciasAlea_paginas::with(['plat','estado','lugar']);

        // Rango por mes (ya lo usabas)
        if ($desde = $request->query('fecha_desde')) $query->where('fecha', '>=', $desde);
        if ($hasta = $request->query('fecha_hasta')) $query->where('fecha', '<=', $hasta);

        // === Filtros por campo ===
        if ($v = trim((string)$request->query('user_pag', ''))) {
            $query->where('user_pag', 'like', "%{$v}%");
        }
        if ($v = $request->query('plataforma')) {
            $query->where('plataforma', (int)$v);
        }
        if ($v = trim((string)$request->query('link_pagina', ''))) {
            $query->where('link_pagina', 'like', "%{$v}%");
        }
        $v = $request->query('denunciada', null);
        if ($v !== null && in_array($v, ['0','1'], true)) {
            $query->where('denunciada', (int)$v);
        }

        if ($v = $request->query('cant_min')) {
            $query->where('cant_denuncias', '>=', (int)$v);
        }
        if ($v = $request->query('cant_max')) {
            $query->where('cant_denuncias', '<=', (int)$v);
        }
        if ($v = $request->query('estado_id')) {
            $query->where('estado_denuncia', (int)$v);
        }
        if ($v = $request->query('lugar_id')) {
            $query->where('denunciado_en', (int)$v);
        }

        // === Ordenamiento (whitelist) ===
        $sortable = [
            'fecha'          => 'denunciasAlea_paginas.fecha',
            'user_pag'       => 'denunciasAlea_paginas.user_pag',
            'plataforma'     => 'denunciasAlea_paginas.plataforma',      // por ID (simple). Si querés por nombre, ver nota abajo
            'link_pagina'    => 'denunciasAlea_paginas.link_pagina',
            'denunciada'     => 'denunciasAlea_paginas.denunciada',
            'cant_denuncias' => 'denunciasAlea_paginas.cant_denuncias',
            'estado_denuncia'=> 'denunciasAlea_paginas.estado_denuncia', // por ID
            'denunciado_en'  => 'denunciasAlea_paginas.denunciado_en',   // por ID
        ];
        $by  = $request->query('sort_by', 'fecha');
        $dir = strtolower($request->query('sort_dir', 'desc')) === 'asc' ? 'asc' : 'desc';

        if (isset($sortable[$by])) {
            $query->orderBy($sortable[$by], $dir);
        } else {
            $query->orderBy('fecha', 'desc');
        }

        $total = $query->count();
        $registros = $query->skip(($page - 1) * $perPage)->take($perPage)->get();

        $datos = $registros->map(function($r){
    return [
        'id_denunciasAlea_paginas' => $r->id_denunciasAlea_paginas,
        'fecha'          => (string)$r->fecha,
        'user_pag'       => (string)$r->user_pag,
        'plataforma'     => $r->plat ? $r->plat->plataforma : '-',
        'link_pagina'    => (string)$r->link_pagina,
        'denunciada'     => (int)$r->denunciada,
        'cant_denuncias' => $r->cant_denuncias,

        // 👇 NUEVO: ID y nombre
        'estado_id'      => is_null($r->estado_denuncia) ? null : (int)$r->estado_denuncia,
        'estado_denuncia'=> $r->estado ? $r->estado->estado : '',

        'denunciado_en'  => $r->lugar ? $r->lugar->lugar : '',
    ];
});


        return response()->json([
            'registros'  => $datos,
            'pagination' => [
                'current_page' => $page,
                'per_page'     => $perPage,
                'total'        => $total,
            ],
        ]);
    }




public function eliminarDenuncia($id){
  $denuncia = DenunciasAlea_paginas::findOrFail($id);
  if(is_null($denuncia)) return 0;
  DenunciasAlea_paginas::destroy($id);
  return 1;
}

public function exportSeleccion(Request $request)
{
    $format = strtolower($request->input('format', 'xlsx'));

    // ids: "1,2,3" o ["1","2","3"]
    $raw = $request->input('ids', '');
    $ids = is_array($raw)
        ? array_values(array_filter(array_map('intval', $raw)))
        : array_values(array_filter(array_map('intval',
            preg_split('/[,\s]+/', (string)$raw, -1, PREG_SPLIT_NO_EMPTY)
        )));
    if (!$ids) return response('Sin IDs seleccionados.', 422);

    $rows = DenunciasAlea_paginas::with(['plat','estado','lugar'])
        ->whereIn('id_denunciasAlea_paginas', $ids)
        ->orderByRaw('FIELD(id_denunciasAlea_paginas,'.implode(',', $ids).')')
        ->get();

        if ($format === 'pdf') {
        // Aplano los registros a escalares (sin objetos ni relaciones en el HTML)
        $trs = '';
        foreach ($rows as $r) {
            $fecha          = (string) $r->fecha;
            $user_pag       = (string) ($r->user_pag ?: '-');
            $plataforma     = (string) ($r->plat ? $r->plat->plataforma : '-');
            $link_pagina    = (string) ($r->link_pagina ?: '-');
            $denunciada     = $r->denunciada ? 'Sí' : 'No';
            $cant_denuncias = (string) ($r->cant_denuncias !== null ? $r->cant_denuncias : '-');
            $estado         = (string) ($r->estado ? $r->estado->estado : '-');
            $lugar          = (string) ($r->lugar ? $r->lugar->lugar : '-');

            // Escapamos por seguridad
            $td = function($v){ return htmlspecialchars($v, ENT_QUOTES, 'UTF-8'); };

            $trs .= '<tr>'
                  . '<td class="nowrap">'.$td($fecha).'</td>'
                  . '<td>'.$td($user_pag).'</td>'
                  . '<td>'.$td($plataforma).'</td>'
                  . '<td>'.$td($link_pagina).'</td>'
                  . '<td class="center">'.$td($denunciada).'</td>'
                  . '<td class="center">'.$td($cant_denuncias).'</td>'
                  . '<td>'.$td($estado).'</td>'
                  . '<td>'.$td($lugar).'</td>'
                  . '</tr>';
        }

        // HTML plano y compatible con Dompdf (sin @extends, sin JS, sin assets externos)
        $html = '<!doctype html>
    <html lang="es">
    <head>
      <meta charset="utf-8">
      <title>Denuncias seleccionadas</title>
      <style>
        @page { margin: 16mm 14mm; }
        body { font-family: DejaVu Sans, Arial, sans-serif; font-size: 11px; color:#222; }
        h1 { font-size: 16px; margin: 0 0 10px; }
        table { width:100%; border-collapse: collapse; table-layout: fixed; }
        thead { display: table-header-group; }  /* asegura encabezado en cada página */
        tfoot { display: table-row-group; }
        tbody { display: table-row-group; }
        th, td { border:1px solid #999; padding:6px 4px; vertical-align: top; word-wrap: break-word; overflow-wrap: break-word; }
        thead th { background:#f0f0f0; font-weight:700; }
        .nowrap { white-space: nowrap; }
        .center { text-align: center; }
      </style>
    </head>
    <body>
      <h1>Denuncias seleccionadas</h1>
      <table>
        <thead>
          <tr>
            <th style="width:11%" class="nowrap">Fecha</th>
            <th style="width:16%">Usuario de página</th>
            <th style="width:14%">Red/Plataforma</th>
            <th style="width:25%">Link de la página</th>
            <th style="width:9%"  class="center">Denuncia Alea</th>
            <th style="width:9%"  class="center">Cant. Denuncias</th>
            <th style="width:8%">Estado</th>
            <th style="width:8%">Denunciado en</th>
          </tr>
        </thead>
        <tbody>'.($trs ?: '<tr><td colspan="8" class="center">Sin registros.</td></tr>').'</tbody>
      </table>
    </body>
    </html>';

        // Dompdf “puro” (como tu PDF($id))
        $opt = new \Dompdf\Options();
        $opt->set('isHtml5ParserEnabled', true);
        $opt->set('isRemoteEnabled', true);

        $dompdf = new Dompdf($opt);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->loadHtml($html, 'UTF-8');
        $dompdf->render();

        return response($dompdf->output(), 200, [
            'Content-Type'        => 'application/pdf',
            'Content-Disposition' => 'inline; filename="denuncias_seleccion.pdf"',
        ]);
    }


    // --- XLSX (tu estilo legacy) ---
    return \Excel::create('denuncias_seleccion', function($excel) use ($rows) {
        $excel->sheet('Denuncias', function($sheet) use ($rows) {
            $sheet->row(1, [
                'Fecha','Usuario de página','Red/Plataforma','Link de la página',
                'Denuncia Alea','Cant. Denuncias','Estado','Denunciado en'
            ]);
            $i = 2;
            foreach ($rows as $r) {
                $sheet->row($i++, [
                    (string) $r->fecha,
                    (string) ($r->user_pag ?: '-'),
                    (string) ($r->plat ? $r->plat->plataforma : '-'),
                    (string) ($r->link_pagina ?: '-'),
                    $r->denunciada ? 'Sí' : 'No',
                    (string) ($r->cant_denuncias !== null ? $r->cant_denuncias : '-'),
                    (string) ($r->estado ? $r->estado->estado : '-'),
                    (string) ($r->lugar ? $r->lugar->lugar : '-'),
                ]);
            }
        });
    })->export('xlsx');
}

// dentro de denunciasAleaController


private function httpGetSmart($url)
{
    $ua     = 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome Safari';
    $accept = "text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8";
    $lang   = "es-AR,es;q=0.9,en;q=0.8";
    $maxRedirects = 5;

    // 1) STREAM con manejo manual de 3xx (tu código actual) ...
    //  (lo dejás como está)

    // 2) FALLBACK CURL con manejo manual de 3xx (NUEVO)
    if (function_exists('curl_init')) {
        $seen = 0; $last = $url; $respArr = null;

        while ($seen++ < $maxRedirects) {
            $respArr = $this->curlFetch($last, $ua, $accept, $lang);

            if (!$respArr['ok']) {
                return $respArr; // error de red
            }

            $code = (int)$respArr['status'];
            if (in_array($code, [301,302,303,307,308], true)) {
                $loc = $this->getHeader($respArr['headers'], 'Location');
                if (!$loc) break;
                $last = $this->resolveLocation($last, $loc);
                continue;
            }
            // 200/4xx/5xx -> devolver
            return $respArr;
        }
        return $respArr ?: ['ok'=>false,'error'=>'curl: sin respuesta','status'=>0,'final_url'=>$url];
    }

    // 3) Sin stream válido y sin cURL
    return ['ok'=>false, 'error'=>'sin transporte disponible', 'status'=>0, 'final_url'=>$url];
}

private function curlFetch($url, $ua, $accept, $lang)
{
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL            => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => false,     // seguimos manualmente
        CURLOPT_CONNECTTIMEOUT => 10,
        CURLOPT_TIMEOUT        => 15,
        CURLOPT_USERAGENT      => $ua,
        CURLOPT_HTTPHEADER     => ["Accept: {$accept}", "Accept-Language: {$lang}"],
        CURLOPT_SSL_VERIFYPEER => true,
        CURLOPT_SSL_VERIFYHOST => 2,
        //CURLOPT_IPRESOLVE      => CURL_IPRESOLVE_V4,
        CURLOPT_ENCODING       => '',
        CURLOPT_HEADER         => true,
    ]);

    $proxyHost = env('HTTP_PROXY_HOST');
    $proxyPort = env('HTTP_PROXY_PORT');

    if ($proxyHost && $proxyPort) {
        $options[CURLOPT_PROXY] = $proxyHost;
        $options[CURLOPT_PROXYPORT] = $proxyPort;

        $proxyUser = env('HTTP_PROXY_USER');
        $proxyPass = env('HTTP_PROXY_PASS');

        if ($proxyUser && $proxyPass) {
            $options[CURLOPT_PROXYUSERPWD] = "$proxyUser:$proxyPass";
        }
    }
    // ------------------------------------------

    curl_setopt_array($ch, $options);

    $raw  = curl_exec($ch);
    $errc = curl_errno($ch);
    $errt = curl_error($ch);
    $code = (int) curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
    $eff  = curl_getinfo($ch, CURLINFO_EFFECTIVE_URL);
    $hsz  = (int) curl_getinfo($ch, CURLINFO_HEADER_SIZE);
    curl_close($ch);

    if ($raw === false) {
        return ['ok'=>false,'error'=>"curl[{$errc}]: {$errt}",'status'=>0,'final_url'=>$url];
    }

    $rawHeaders = trim(substr($raw, 0, $hsz));
    $body       = substr($raw, $hsz);
    $hdrs = array_filter(array_map('trim', preg_split('/\r\n|\n|\r/', $rawHeaders)));

    return [
        'ok'        => true,
        'status'    => $code,
        'final_url' => $eff ?: $url,
        'headers'   => $hdrs,
        'body'      => substr((string)$body, 0, 200000),
    ];
}

private function httpGetNoCurl($url)
{
    return $this->httpGetSmart($url);
}
private function parseHttpCode($headers) {
    foreach ($headers as $h) {
        if (preg_match('#^HTTP/\d+\.\d+\s+(\d{3})#i', $h, $m)) return (int)$m[1];
    }
    return 0;
}

// Compatibilidad 5.6/7.0
private function getHeader($headers, $name) {
    $target = strtolower((string)$name);

    // Caso 1: array asociativo estilo ['Content-Type' => 'text/html']
    if (is_array($headers)) {
        foreach ($headers as $k => $v) {
            if (is_string($k) && strtolower($k) === $target) {
                // Guzzle puede devolver array de valores
                return is_array($v) ? implode(', ', $v) : $v;
            }
        }
        // Caso 2: lista de líneas "Header: valor"
        foreach ($headers as $line) {
            if (!is_string($line)) continue;
            $parts = explode(':', $line, 2);
            if (count($parts) === 2 && strtolower(trim($parts[0])) === $target) {
                return trim($parts[1]);
            }
        }
    }

    // Caso 3: string con todos los headers
    if (is_string($headers)) {
        foreach (preg_split('/\r\n|\n|\r/', $headers) as $line) {
            $parts = explode(':', $line, 2);
            if (count($parts) === 2 && strtolower(trim($parts[0])) === $target) {
                return trim($parts[1]);
            }
        }
    }

    return null; // sin tipo de retorno para compatibilidad
}
private function resolveLocation($base, $loc) {
    if (parse_url($loc, PHP_URL_SCHEME)) return $loc;
    $bp = parse_url($base);
    $scheme = isset($bp['scheme']) ? $bp['scheme'] : 'https';
    $host   = isset($bp['host'])   ? $bp['host']   : '';
    $port   = isset($bp['port'])   ? ':' . $bp['port'] : '';
    if (strpos($loc, '/') === 0) return $scheme.'://'.$host.$port.$loc;
    $path = isset($bp['path']) ? preg_replace('#/[^/]*$#', '/', $bp['path']) : '/';
    return $scheme.'://'.$host.$port.$path.$loc;
}
private function platformFromHost($url){
    $h = strtolower(parse_url($url, PHP_URL_HOST) ?: '');
    if (strpos($h,'instagram.')!==false) return 'instagram';
    if (strpos($h,'facebook.')!==false || strpos($h,'fb.')!==false) return 'facebook';
    return null;
}
private function decideAvailability($platform, $code, $body){
  $body = (string)$body; // por las dudas
  $b = function ($t) use ($body) {
      return stripos($body, $t) !== false;
  };
    if ($code===404 || $code===410) return ['available','http not found'];
    if ($code===429 || $code===403) return ['unknown','rate-limited/forbidden'];

    if ($platform==='instagram'){
        if ($b("page isn't available") || $b('The link you followed may be broken')
            || $b('Esta página no está disponible') || $b('el enlace que has seguido puede estar roto')) {
            return ['available','instagram not-available'];
        }
        if ($b('property="og:type" content="profile"') || $b('og:type" content="profile')) {
            return ['taken','og:type=profile'];
        }
        return $code>=400 ? ['unknown',"http {$code}"] : ['unknown','ambiguous 200'];
    }
    if ($platform==='facebook'){
    // No disponible (mensajes típicos de FB)
    if ($b("content isn't available right now") || $b('Este contenido no está disponible')
        || $b('may have been removed') || $b('Es posible que el enlace al que has accedido no funcione')) {
        return ['available','facebook not-available'];
    }

    // Perfiles/páginas activos: varios indicadores
    if ($b('property="og:type" content="profile"') || $b('property="og:type" content="business')
        || $b('al:ios:url" content="fb://profile') || $b('"entity_id"') || $b('"profile_id"')
        || $b('"pageID"') || $b('data-profileid') || $b('application/ld+json')) {
        return ['taken','fb profile/page markers'];
    }

    // Por defecto
    return $code>=400 ? ['unknown',"http {$code}"] : ['unknown','ambiguous 200'];
}

}





//Observaciones

public function totalesMensuales(Request $r)
{
$page     = max(1, (int) $r->get('page', 1));
$pageSize = max(1, min(100, (int) $r->get('page_size', 10)));

$desdeYM  = trim((string)$r->get('desde', '')); // yyyy-mm
$hastaYM  = trim((string)$r->get('hasta', ''));

$userPag    = trim((string)$r->get('user_pag', ''));
$plataforma = trim((string)$r->get('plataforma', ''));
$link       = trim((string)$r->get('link_pagina', ''));
$denunciada = $r->has('denunciada') && $r->get('denunciada') !== '' ? (int)$r->get('denunciada') : null;
$cantMin    = $r->get('cant_min', null);
$cantMax    = $r->get('cant_max', null);
$estadoId   = trim((string)$r->get('estado_id', ''));
$lugarId    = trim((string)$r->get('lugar_id', ''));

$sortBy  = in_array($r->get('sort_by'), ['anio','mes','total_identificadas','realizadas','no_realizadas','activas','bajas','den_sf','den_ros'])
            ? $r->get('sort_by') : 'anio';
$sortDir = strtolower($r->get('sort_dir')) === 'asc' ? 'asc' : 'desc';

$start = $desdeYM ? $desdeYM.'-01' : null;
$end   = $hastaYM ? date('Y-m-t', strtotime($hastaYM.'-01')) : null;

$q = DB::table('denunciasAlea_paginas as p')
    ->leftJoin('denunciasAlea_estado as e', 'e.id_denunciasAlea_estado', '=', 'p.estado_denuncia')
    ->leftJoin('denunciasAlea_denunciadoEn as l', 'l.id_denunciasAlea_denunciadoEn', '=', 'p.denunciado_en')
    ->selectRaw("
        YEAR(p.fecha)  as anio,
        MONTH(p.fecha) as mes,

        COUNT(DISTINCT p.user_pag) as total_identificadas,
        SUM(CASE WHEN p.denunciada = 1 THEN 1 ELSE 0 END) as realizadas,
        SUM(CASE WHEN p.denunciada = 0 THEN 1 ELSE 0 END) as no_realizadas,

        -- Estados
        SUM(CASE
            WHEN p.estado_denuncia IN (0, 1, 3, 4) THEN 1
            ELSE 0
        END) as activas,
        SUM(CASE
            WHEN p.estado_denuncia = 2 THEN 1
            ELSE 0
        END) as bajas,

        -- Lugares
        SUM(CASE
            WHEN p.denunciado_en = 1 THEN 1
            ELSE 0
        END) as den_sf,
        SUM(CASE
            WHEN p.denunciado_en = 2 THEN 1
            ELSE 0
        END) as den_ros
    ");

if ($start) $q->where('p.fecha', '>=', $start);
if ($end)   $q->where('p.fecha', '<=', $end);

if ($userPag !== '')      $q->where('p.user_pag', 'like', '%'.$userPag.'%');
if ($plataforma !== '')   $q->where('p.plataforma', $plataforma);
if ($link !== '')         $q->where('p.link_pagina', 'like', '%'.$link.'%');
if ($denunciada !== null) $q->where('p.denunciada', $denunciada);
if ($cantMin !== null && $cantMin !== '') $q->where('p.cant_denuncias', '>=', (int)$cantMin);
if ($cantMax !== null && $cantMax !== '') $q->where('p.cant_denuncias', '<=', (int)$cantMax);
if ($estadoId !== '')     $q->where('p.estado_denuncia', $estadoId);
if ($lugarId !== '')      $q->where('p.denunciado_en',   $lugarId);

$q->groupBy(DB::raw('YEAR(p.fecha), MONTH(p.fecha)'))
  ->orderBy($sortBy, $sortDir)->orderBy('mes', $sortDir);

$all   = $q->get();
$total = $all->count();
$items = $all->slice(($page - 1) * $pageSize, $pageSize)->values();

return response()->json([
    'registros' => $items,
    'pagination' => [
        'current_page' => $page,
        'per_page'     => $pageSize,
        'total'        => $total,
    ]
]);
}
public function exportTotales(Request $r)
{
    // 1) Parseo de la selección: puede venir como CSV o array
    $selRaw = $r->input('seleccion', []);
    if (is_string($selRaw)) {
        $selRaw = preg_split('/[,\s]+/', $selRaw, -1, PREG_SPLIT_NO_EMPTY);
    }

    $pares  = []; // [[anio, mes], ...]
    $tokens = []; // ["YYYY-MM", ...]
    foreach ((array)$selRaw as $k) {
        $k = trim((string)$k);
        if (preg_match('/^(\d{4})-(\d{1,2})$/', $k, $m)) {
            $yy = (int)$m[1];
            $mm = (int)$m[2];
            if ($mm >= 1 && $mm <= 12) {
                $pares[]  = [$yy, $mm];
                $tokens[] = sprintf('%04d-%02d', $yy, $mm);
            }
        }
    }

    if ($r->has('seleccion') && empty($pares)) {
        return response('Seleccioná al menos un mes válido (YYYY-MM).', 422);
    }

    // 2) Query de agregados mensuales SOLO para los meses seleccionados
    $q = \DB::table('denunciasAlea_paginas as p')
        ->leftJoin('denunciasAlea_estado as e', 'e.id_denunciasAlea_estado', '=', 'p.estado_denuncia')
        ->leftJoin('denunciasAlea_denunciadoEn as l', 'l.id_denunciasAlea_denunciadoEn', '=', 'p.denunciado_en')
        ->selectRaw("
            YEAR(p.fecha)  as anio,
            MONTH(p.fecha) as mes,
            COUNT(DISTINCT p.user_pag)                                 as total_identificadas,
            SUM(CASE WHEN p.denunciada = 1 THEN 1 ELSE 0 END)          as realizadas,
            SUM(CASE WHEN p.denunciada = 0 THEN 1 ELSE 0 END)          as no_realizadas,
            SUM(CASE WHEN e.estado LIKE '%Act%' THEN 1 ELSE 0 END)     as activas,
            SUM(CASE WHEN (e.estado LIKE '%Baj%' OR e.estado LIKE '%Elimin%') THEN 1 ELSE 0 END) as bajas,
            SUM(CASE WHEN l.lugar LIKE '%Santa%Fe%' THEN 1 ELSE 0 END) as den_sf,
            SUM(CASE WHEN l.lugar LIKE '%Rosario%'  THEN 1 ELSE 0 END) as den_ros
        ");

    // where ( (Y=aaaa AND M=mm) OR (Y=.... AND M=...) ... )
    $q->where(function($w) use ($pares) {
        foreach ($pares as $pm) {
            $yy = $pm[0]; $mm = $pm[1];
            $w->orWhere(function($x) use ($yy, $mm) {
                $x->whereYear('p.fecha', $yy)->whereMonth('p.fecha', $mm);
            });
        }
    });

    $q->groupBy(\DB::raw('YEAR(p.fecha), MONTH(p.fecha)'));

    // Traigo y reordeno en PHP según el orden enviado por el front
    $rows = $q->get();
    $pos  = array_flip($tokens); // "YYYY-MM" => orden
    $rows = $rows->sortBy(function($x) use ($pos){
        $key = sprintf('%04d-%02d', (int)$x->anio, (int)$x->mes);
        return isset($pos[$key]) ? $pos[$key] : PHP_INT_MAX;
    })->values();

    // 3) Salida: PDF o XLSX
    $format = strtolower((string)$r->input('format', 'xlsx'));

    if ($format === 'pdf') {
        $trs = '';
        foreach ($rows as $x) {
            $anio = (int)$x->anio;
            $mes  = str_pad((string)$x->mes, 2, '0', STR_PAD_LEFT);
            $trs .= '<tr>'
                  . '<td>'.htmlspecialchars((string)$anio, ENT_QUOTES, 'UTF-8').'</td>'
                  . '<td>'.htmlspecialchars($mes, ENT_QUOTES, 'UTF-8').'</td>'
                  . '<td class="center">'.htmlspecialchars((string)$x->total_identificadas, ENT_QUOTES, 'UTF-8').'</td>'
                  . '<td class="center">'.htmlspecialchars((string)$x->realizadas, ENT_QUOTES, 'UTF-8').'</td>'
                  . '<td class="center">'.htmlspecialchars((string)$x->no_realizadas, ENT_QUOTES, 'UTF-8').'</td>'
                  . '<td class="center">'.htmlspecialchars((string)$x->activas, ENT_QUOTES, 'UTF-8').'</td>'
                  . '<td class="center">'.htmlspecialchars((string)$x->bajas, ENT_QUOTES, 'UTF-8').'</td>'
                  . '<td class="center">'.htmlspecialchars((string)$x->den_sf, ENT_QUOTES, 'UTF-8').'</td>'
                  . '<td class="center">'.htmlspecialchars((string)$x->den_ros, ENT_QUOTES, 'UTF-8').'</td>'
                  . '</tr>';
        }
        if ($trs === '') {
            $trs = '<tr><td colspan="9" class="center">Sin datos.</td></tr>';
        }

        $html = '<!doctype html><html lang="es"><head><meta charset="utf-8">
        <title>Totales Mensuales</title>
        <style>
          @page { margin: 16mm 14mm; }
          body { font-family: DejaVu Sans, Arial, sans-serif; font-size: 11px; color:#222; }
          h1 { font-size: 16px; margin: 0 0 10px; }
          table { width:100%; border-collapse: collapse; table-layout: fixed; }
          thead { display: table-header-group; }
          th, td { border:1px solid #999; padding:6px 4px; vertical-align: top; }
          thead th { background:#f0f0f0; font-weight:700; }
          .center { text-align:center; }
        </style></head><body>
        <h1>Totales Mensuales</h1>
        <table>
          <thead>
            <tr>
              <th style="width:8%">Año</th>
              <th style="width:8%">Mes</th>
              <th style="width:14%">Cuentas Identificadas</th>
              <th style="width:12%">Denuncias Alea</th>
              <th style="width:12%">No Denunciadas</th>
              <th style="width:12%">Activas</th>
              <th style="width:12%">Bajas</th>
              <th style="width:11%">Den. Santa Fe</th>
              <th style="width:11%">Den. Rosario</th>
            </tr>
          </thead>
          <tbody>'.$trs.'</tbody>
        </table></body></html>';

        $opt = new \Dompdf\Options();
        $opt->set('isHtml5ParserEnabled', true);
        $opt->set('isRemoteEnabled', true);

        $dompdf = new \Dompdf\Dompdf($opt);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->loadHtml($html, 'UTF-8');
        $dompdf->render();

        return response($dompdf->output(), 200, [
            'Content-Type'        => 'application/pdf',
            'Content-Disposition' => 'inline; filename="denuncias_totales.pdf"',
        ]);
    }

    // XLSX (Maatwebsite v2)
    return \Excel::create('denuncias_totales', function($excel) use ($rows) {
        $excel->sheet('Totales', function($sheet) use ($rows) {
            $sheet->row(1, [
                'Año','Mes','Cuentas Identificadas','Denuncias Alea',
                'No Denunciadas','Activas','Bajas','Denuncias Santa Fe','Denuncias Rosario'
            ]);
            $i = 2;
            foreach ($rows as $x) {
                $sheet->row($i++, [
                    (int)$x->anio,
                    str_pad((string)$x->mes, 2, '0', STR_PAD_LEFT),
                    (int)$x->total_identificadas,
                    (int)$x->realizadas,
                    (int)$x->no_realizadas,
                    (int)$x->activas,
                    (int)$x->bajas,
                    (int)$x->den_sf,
                    (int)$x->den_ros,
                ]);
            }
        });
    })->export('xlsx');
}



public function setDenuncia(Request $req, $id)
{
    try {
        // 1) Tomar y normalizar el parámetro sin usar validate()
        $raw = $req->input('denuncia_alea', null);
        if ($raw === null) {
            return response()->json([
                'ok'    => false,
                'error' => 'Falta el parámetro denuncia_alea'
            ], 422);
        }

        // Aceptamos varias formas "truthy/falsy"
        $truthy = ['1', 1, true, 'true', 'on', 'yes', 'si', 'sí', 'SI', 'SÍ'];
        $falsy  = ['0', 0, false, 'false', 'off', 'no', 'NO'];

        if (in_array($raw, $truthy, true)) {
            $val = 1;
        } elseif (in_array($raw, $falsy, true)) {
            $val = 0;
        } else {
            // último intento: numérico 0/1
            if (is_numeric($raw) && ((int)$raw === 0 || (int)$raw === 1)) {
                $val = (int)$raw;
            } else {
                return response()->json([
                    'ok'    => false,
                    'error' => 'denuncia_alea debe ser 0/1 o booleano'
                ], 422);
            }
        }

        // 2) Buscar registro (usando el modelo que usás en el resto del controller)
        $row = \App\DenunciasAlea_paginas::findOrFail($id);

        // 3) Guardar (tu esquema usa "denunciada")
        $row->denunciada = $val;
        $row->save();

        // 4) Respuesta OK (devolvemos la PK real)
        return response()->json([
            'ok'            => true,
            'id'            => $row->getKey(), // o $row->id_denunciasAlea_paginas
            'denuncia_alea' => $val,
        ]);

    } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
        return response()->json(['ok' => false, 'error' => 'Registro inexistente'], 404);
    } catch (\Throwable $e) {
        return response()->json(['ok' => false, 'error' => $e->getMessage()], 500);
    }
}





    // --- CSV Import & Bulk Update Logic ---

    public function importarCsvPaginasActivas(Request $request) {
        if (!$request->hasFile('archivo')) {
            return response()->json(['error' => 'No se ha subido ningún archivo.'], 400);
        }

        $file = $request->file('archivo');
        $path = $file->getRealPath();
        $rawLines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        if (!$rawLines) {
           return response()->json(['registros' => []]);
        }
        
        $data = array_map('str_getcsv', $rawLines);
        
        if (count($data) > 0) {
            array_shift($data);
        }

        // CSV viene cronológico (Viejo -> Nuevo) => invertir
        $data = array_reverse($data);

        // --- Optimización: Cargar existentes para cruzar ---
        // Traemos ID, URL, Usuario y Estado (con relación)
        $existing = \App\DenunciasAlea_paginas::with('estado')->get(); 
        
        // Armamos mapas para búsqueda rápida (URL y Usuario)
        // Nota: Las URLs pueden variar en protocolo/www, así que usamos versiones "limpias" para las keys
        $mapLinks = [];
        $mapUsers = [];
        
        $clean = function($s) {
            // Quitar protocolo y www. para normalizar búsqueda
            return preg_replace('#^https?://(www\.)?#i', '', trim($s));
        };

        foreach($existing as $ex){
            if($ex->link_pagina) {
                $k = $clean($ex->link_pagina);
                if($k) $mapLinks[$k] = $ex;
            }
            if($ex->user_pag) {
                $mapUsers[trim($ex->user_pag)] = $ex;
            }
        }

        $parsedData = [];
        $idCounter = 1;

        foreach ($data as $row) {
             if (count($row) < 6) continue;

             $fecha      = $row[0];
             $usuario    = trim((string)$row[1]);
             $url        = trim((string)$row[2]);
             $estadoCsv  = $row[3];
             $detalle    = $row[4];
             $plataforma = $row[5];

             // --- Búsqueda de coincidencia ---
             $match = null;
             
             // 1. Por URL
             if ($url !== '') {
                 $k = $clean($url);
                 if ($k && isset($mapLinks[$k])) {
                     $match = $mapLinks[$k];
                 }
             }
             // 2. Por Usuario (fallback si no machó por URL)
             if (!$match && $usuario !== '') {
                 if (isset($mapUsers[$usuario])) {
                     $match = $mapUsers[$usuario];
                 }
             }

             // Datos del existente
             $esNuevo       = $match ? false : true;
             $idExistente   = $match ? $match->id_denunciasAlea_paginas : null;
             $estadoDbId    = $match ? $match->estado_denuncia : null;
             $estadoDbNombre= ($match && $match->estado) ? $match->estado->estado : '';

            $parsedData[] = [
                'id_temp'    => $idCounter++,
                'fecha'      => $fecha,
                'usuario'    => $usuario,
                'url'        => $url,
                'estado'     => $estadoCsv,
                'detalle'    => $detalle,
                'plataforma' => $plataforma,
                
                // Info extra para UI
                'es_nuevo'         => $esNuevo,
                'id_existente'     => $idExistente,
                'estado_db_id'     => $estadoDbId,
                'estado_db_nombre' => $estadoDbNombre,
            ];
        }

        return response()->json(['registros' => $parsedData]);
    }

    public function darBajaPaginasInactivas(Request $request) {
        $items = $request->input('items', []);

        if (empty($items)) {
            return response()->json(['success' => false, 'message' => 'No se seleccionaron ítems.']);
        }

        $count = 0;
        
        // Buscamos el ID de estado "Baja" o "Inactivo".
        // Primero intentamos buscar 'Baja'.
        $estadoBaja = DenunciasAlea_estado::where('estado', 'LIKE', '%Baja%')->first();
        // Si no, buscamos 'Inactivo' o similar si existiera, pero 'Baja' es lo solicitado.
        
        if (!$estadoBaja) {
             return response()->json(['success' => false, 'message' => 'No se encontró el estado "Baja" en la base de datos para asignar. Asegúrese de que existe.'], 500);
        }
        $idBaja = $estadoBaja->id_denunciasAlea_estado;

        foreach ($items as $item) {
             $url = isset($item['url']) ? trim((string)$item['url']) : '';
             $user = isset($item['usuario']) ? trim((string)$item['usuario']) : '';
             
             if ($url === '' && $user === '') continue;

             $q = DenunciasAlea_paginas::query();
             
             // Prioridad URL
             if ($url !== '') {
                // Quitamos protocolo para hacer un LIKE más amplio
                $cleanUrl = preg_replace('#^https?://#', '', $url);
                // Si la URL es muy corta, cuidado con falsos positivos
                if (strlen($cleanUrl) > 3) {
                     $q->where('link_pagina', 'LIKE', '%'.$cleanUrl.'%');
                } else {
                     // Fallback exacto si es muy corto
                     $q->where('link_pagina', $url);
                }
             } elseif ($user !== '') {
                $q->where('user_pag', $user);
             }
             
             // Actualizamos todos los que coincidan
             $affected = $q->update(['estado_denuncia' => $idBaja]);
             if ($affected > 0) $count++;
        }

        return response()->json(['success' => true, 'updated_count' => $count]);
    }

    public function exportarListadoTemporal(Request $request) {
        $format = strtolower($request->input('format', 'xlsx'));
        $data   = $request->input('data', []);

        if (empty($data)) return response('Sin datos para exportar.', 422);

        // Preparamos fecha actual para la columna "Fecha"
        $fechaHoy = date('Y-m-d');

        if ($format === 'pdf') {
            $trs = '';
            foreach ($data as $item) {
                // Normalizar entrada
                $item = (array)$item;
                
                $u = isset($item['usuario']) ? $item['usuario'] : '';
                $p = isset($item['plataforma']) ? $item['plataforma'] : '';
                $l = isset($item['url']) ? $item['url'] : '';
                $e = isset($item['estado']) ? $item['estado'] : '';
                
                // Defaults para columnas que no existen en el CSV importado
                $fecha          = isset($item['fecha']) ? $item['fecha'] : $fechaHoy;
                $user_pag       = $u ?: '-';
                $plataforma     = $p ?: '-';
                $link_pagina    = $l ?: '-';
                $denunciada     = 'No'; // Default: no alea
                $cant_denuncias = '-';  // Desconocido
                $estado         = $e ?: '-';
                $lugar          = '-';  // Desconocido
                
                $td = function($v){ return htmlspecialchars($v, ENT_QUOTES, 'UTF-8'); };

                $trs .= '<tr>'
                      . '<td class="nowrap">'.$td($fecha).'</td>'
                      . '<td>'.$td($user_pag).'</td>'
                      . '<td>'.$td($plataforma).'</td>'
                      . '<td>'.$td($link_pagina).'</td>'
                      . '<td class="center">'.$td($denunciada).'</td>'
                      . '<td class="center">'.$td($cant_denuncias).'</td>'
                      . '<td>'.$td($estado).'</td>'
                      . '<td>'.$td($lugar).'</td>'
                      . '</tr>';
            }

            // HTML: Copia exacta del estilo de exportSeleccion
            $html = '<!doctype html>
            <html lang="es">
            <head>
              <meta charset="utf-8">
              <title>Listado Importado</title>
              <style>
                @page { margin: 16mm 14mm; }
                body { font-family: DejaVu Sans, Arial, sans-serif; font-size: 11px; color:#222; }
                h1 { font-size: 16px; margin: 0 0 10px; }
                table { width:100%; border-collapse: collapse; table-layout: fixed; }
                thead { display: table-header-group; }
                tfoot { display: table-row-group; }
                tbody { display: table-row-group; }
                th, td { border:1px solid #999; padding:6px 4px; vertical-align: top; word-wrap: break-word; overflow-wrap: break-word; }
                thead th { background:#f0f0f0; font-weight:700; }
                .nowrap { white-space: nowrap; }
                .center { text-align: center; }
              </style>
            </head>
            <body>
              <h1>Listado de Páginas (Importado)</h1>
              <table>
                <thead>
                  <tr>
                    <th style="width:11%" class="nowrap">Fecha</th>
                    <th style="width:16%">Usuario de página</th>
                    <th style="width:14%">Red/Plataforma</th>
                    <th style="width:25%">Link de la página</th>
                    <th style="width:9%"  class="center">Denuncia Alea</th>
                    <th style="width:9%"  class="center">Cant. Denuncias</th>
                    <th style="width:8%">Estado</th>
                    <th style="width:8%">Denunciado en</th>
                  </tr>
                </thead>
                <tbody>'.$trs.'</tbody>
              </table>
            </body>
            </html>';

            $dompdf = new \Dompdf\Dompdf();
            // Mismas opciones que exportSeleccion (aunque no sean cruciales para HTML simple, consistencia)
            $opt = $dompdf->getOptions();
            $opt->set('isHtml5ParserEnabled', true);
            $opt->set('isRemoteEnabled', true);
            $dompdf->setOptions($opt);
            
            $dompdf->setPaper('A4', 'portrait');
            $dompdf->loadHtml($html, 'UTF-8');
            $dompdf->render();

            return response($dompdf->output(), 200, [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'inline; filename="listado_importado.pdf"',
            ]);
        }

        // Excel: Mismas columnas que exportSeleccion
        return \Excel::create('listado_importado', function($excel) use ($data, $fechaHoy) {
            $excel->sheet('Datos', function($sheet) use ($data, $fechaHoy) {
                $sheet->row(1, [
                    'Fecha','Usuario de página','Red/Plataforma','Link de la página',
                    'Denuncia Alea','Cant. Denuncias','Estado','Denunciado en'
                ]);
                $i = 2;
                foreach ($data as $item) {
                     $item = (array)$item;
                     $u = isset($item['usuario']) ? $item['usuario'] : '-';
                     $p = isset($item['plataforma']) ? $item['plataforma'] : '-';
                     $l = isset($item['url']) ? $item['url'] : '-';
                     $e = isset($item['estado']) ? $item['estado'] : '-';
                     $f = isset($item['fecha']) ? $item['fecha'] : $fechaHoy;

                    $sheet->row($i++, [
                        $f,        // Fecha
                        $u,        // Usuario
                        $p,        // Plataforma
                        $l,        // Link
                        'No',      // Denuncia Alea
                        '-',       // Cant
                        $e,        // Estado
                        '-'        // Lugar
                    ]);
                }
            });
        })->export('xlsx');
    }


    public function modificarCantidad(Request $request, $id) {
        $validator = \Validator::make($request->all(), [
            'cantidad' => 'required|integer|min:0'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first('cantidad')
            ]);
        }

        $row = \App\DenunciasAlea_paginas::find($id);
        if (!$row) {
            return response()->json(['success' => false, 'message' => 'Registro no encontrado.']);
        }

        $row->cant_denuncias = $request->input('cantidad');
        $row->save();

        return response()->json(['success' => true]);
    }
}
