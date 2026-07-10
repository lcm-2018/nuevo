<?php
$ruta_firmas = "/cronhis/img/firmas/";
#$ruta_firmas = "/proyecto/hc/img/firmas/";

//FUNCION QUE RETORNAR FECHA Y HORA DEL SERVIDOR
function fecha_hora_servidor(){
    $res = array();
    date_default_timezone_set('America/Bogota');
    $res['hora'] = date('h:iA');
    $res['hora24h'] = date('H:i');
    $res['fecha'] = date('Y-m-d');    
    return $res;
}

//FUNCION PARA DAR FORMATO A LOS VALORES NUMERICOS
function formato_valor($valor){
    return '$' . number_format($valor, 2, ",", ".");    
}

//FUNCION PARA DAR FORMATO A LOS VALORES CON DECIMALES
function formato_decimal($num) {
    $num = rtrim(rtrim($num, '0'), '.');  
    return $num;
}

//BITACORA DE MENSAJES A UN ARCHIVO DE ACCIONES REALIZADAS
function bitacora($accion, $opcion, $detalle, $id_usuario, $login) {
    $fecha = '[' . date('Y-m-d h:i:s A') . ']';
    $usuario = $id_usuario . '-' . $login;
    $ip=$_SERVER['REMOTE_ADDR'];    
    $archivo = $_SESSION['ruta_logs'] . date('Ym') . '.log';
    $log= "$fecha Usuario: $usuario, IP: $ip, Accion: $accion, Opcion: $opcion, Registro: $detalle\r\n";
    file_put_contents("$archivo", $log, FILE_APPEND | LOCK_EX);
}

//FUNCIONES DE CONEXION A SEDE REMOTA
function isHostReachable($host): bool {
    // Si el SO empieza por "WIN" → Windows, si no → asumimos Linux/Unix
    $cmd = (stripos(PHP_OS, 'WIN') === 0) ? "ping -n 1 " : "ping -c 1 ";
    $cmd .= escapeshellarg($host);
    exec($cmd, $output, $status);
    return $status === 0;
}

function isMySQLPortOpen(string $host, int $port, int $timeout = 2): bool {
    $errno  = 0;
    $errstr = '';
    $fp = @fsockopen($host, $port, $errno, $errstr, $timeout);
    if ($fp) {
        fclose($fp);
        return true;
    }
    return false;
}

function canConnectToDatabase(string $host, int $port, string $user, string $password, string $database): array {
    $dsn = "mysql:host={$host};port={$port};dbname={$database};charset=utf8mb4";
    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_TIMEOUT => 2,
    ];
    try {
        $pdo = new PDO($dsn, $user, $password, $options);
        $pdo = null;
        return [true, 'Conexión a la base de datos exitosa.'];
    } catch (\PDOException $e) {
        return [false, $e->getMessage()];
    }
}

// FUNCIONES GENERALES
function convertir_sql(string $cad_sql){
    // Añade el prefijo [@bd]. delante de los identificadores de tabla
    if (!is_string($cad_sql) || $cad_sql === '') return $cad_sql;

    $res = $cad_sql;

    // Procesar cláusulas FROM: tomar desde FROM hasta WHERE/GROUP/ORDER/HAVING/LIMIT/UNION/JOIN o fin
    $res = preg_replace_callback('/\bFROM\b\s*(.+?)(?=\bWHERE\b|\bGROUP\b|\bORDER\b|\bHAVING\b|\bLIMIT\b|\bUNION\b|\bJOIN\b|$)/is', function($m){
        $chunk = $m[1];
        $prefix = 'FROM ';
        $trim = ltrim($chunk);
        
        // Si es una subconsulta (FROM (SELECT ...)) no tocar
        if (isset($trim[0]) && $trim[0] === '(') return $prefix . $chunk;

        // Separar por comas para manejar múltiples tablas
        $parts = preg_split('/\s*,\s*/', $chunk);
        $newParts = array_map(function($p){
            // Si el fragmento ya contiene [@bd], no modificar
            if (strpos($p, '.') !== false || stripos($p, '[@bd]') !== false) return $p;
            // Buscar el identificador de tabla al inicio del fragmento
            if (preg_match('/^\s*((?:`[^`]+`)|(?:"[^"]+")|(?:\[[^\]]+\])|(?:[A-Za-z0-9_\.]+))/i', $p, $mm)){
                $ident = $mm[1];
                // Si ya contiene [@bd] o un punto, no modificar
                if (stripos($ident, '[@bd]') !== false || strpos($ident, '.') !== false) return $p;
                // Insertar [@bd]. antes del identificador
                $newIdent = '[@bd].' . $ident;
                // Reemplazar solo la primera aparición del identificador
                $p = preg_replace('/' . preg_quote($ident, '/') . '/', $newIdent, $p, 1);
            }
            return $p;
        }, $parts);

        return $prefix . implode(', ', $newParts);
    }, $res);

    // Procesar JOINs individuales (JOIN <tabla> ...)
    $res = preg_replace_callback('/\b((?:INNER|LEFT|RIGHT|FULL|CROSS)\s+)?JOIN\s+((?:`[^`]+`)|(?:"[^"]+")|(?:\[[^\]]+\])|(?:[A-Za-z0-9_\.]+))/i', function($m){
        $pre = isset($m[1]) && $m[1] !== '' ? $m[1] . 'JOIN' : 'JOIN';
        $ident = $m[2];
        $trim = ltrim($ident);
        if (isset($trim[0]) && $trim[0] === '(') return $m[0]; // JOIN (subquery) -> skip
        // Si ya tiene prefijo [@bd] o incluye esquema (punto), no prefijar
        if (stripos($ident, '[@bd]') !== false || strpos($m[0], '.') !== false) return $m[0];
        $newIdent = '[@bd].' . $ident;
        return $pre . ' ' . $newIdent;
    }, $res);

    return $res;
}

