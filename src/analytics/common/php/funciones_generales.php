<?php
$ruta_firmas = "/cronhis/img/firmas/";
$ruta_firmas = "/proyecto/hc/img/firmas/";

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
    $mysqli = @new mysqli($host, $user, $password, $database, $port);
   if ($mysqli->connect_errno) {
        $error = $mysqli->connect_error;
        return [false, $error];
    }
    $mysqli->close();
    return [true, 'Conexión a la base de datos exitosa.'];
}
