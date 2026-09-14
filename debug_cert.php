<?php
include 'config/autoloader.php';
$cmd = \Config\Clases\Conexion::getConexion();

$id_tercero_api = 6028;

echo "=== El formulario envía id_tercero_api = $id_tercero_api ===" . PHP_EOL;
echo "=== Buscar este tercero en tb_terceros por id_tercero_api ===" . PHP_EOL;
$sql = "SELECT id_tercero, id_tercero_api, nom_tercero, nit_tercero, estado FROM tb_terceros WHERE id_tercero_api = $id_tercero_api";
$rs = $cmd->query($sql);
$t = $rs->fetch();
if ($t) {
    echo "ENCONTRADO: id_tercero={$t['id_tercero']} | id_tercero_api={$t['id_tercero_api']} | {$t['nom_tercero']} | estado={$t['estado']}" . PHP_EOL;
} else {
    echo "NO existe ningún tercero con id_tercero_api=6028 en tb_terceros" . PHP_EOL;
}

echo PHP_EOL . "=== Buscar documentos de ese tercero en ctb_doc (todas las fechas) ===" . PHP_EOL;
$sql = "SELECT id_tipo_doc, estado, MIN(fecha) as min_fecha, MAX(fecha) as max_fecha, COUNT(*) as total
        FROM ctb_doc WHERE id_tercero = $id_tercero_api
        GROUP BY id_tipo_doc, estado ORDER BY id_tipo_doc, estado";
$rs = $cmd->query($sql);
$rows = $rs->fetchAll();
if (count($rows) > 0) {
    foreach ($rows as $r) {
        echo " - tipo_doc={$r['id_tipo_doc']} | estado={$r['estado']} | desde={$r['min_fecha']} hasta={$r['max_fecha']} | total={$r['total']}" . PHP_EOL;
    }
} else {
    echo " NO hay ningún documento para id_tercero=6028" . PHP_EOL;
}

echo PHP_EOL . "=== Verificar los docs tipo 3 para ese tercero (sin filtro de fecha ni estado) ===" . PHP_EOL;
$sql = "SELECT id_ctb_doc, fecha, estado, id_tipo_doc FROM ctb_doc WHERE id_tercero = $id_tercero_api AND id_tipo_doc = 3 LIMIT 10";
$rs = $cmd->query($sql);
$rows = $rs->fetchAll();
if (count($rows) > 0) {
    foreach ($rows as $r) {
        echo " - id_ctb_doc={$r['id_ctb_doc']} | fecha={$r['fecha']} | estado={$r['estado']}" . PHP_EOL;
    }
} else {
    echo " Ningún doc tipo 3 para este tercero." . PHP_EOL;
}

echo PHP_EOL . "=== ¿Cuántos terceros tienen id_tercero_api en rango 6000-6050? ===" . PHP_EOL;
$sql = "SELECT id_tercero, id_tercero_api, nom_tercero, nit_tercero FROM tb_terceros WHERE id_tercero_api BETWEEN 6000 AND 6050 ORDER BY id_tercero_api LIMIT 20";
$rs = $cmd->query($sql);
foreach ($rs->fetchAll() as $r) {
    echo " - id_tercero_api={$r['id_tercero_api']} | id_tercero={$r['id_tercero']} | {$r['nom_tercero']} | nit={$r['nit_tercero']}" . PHP_EOL;
}

echo PHP_EOL . "=== Verificar retenciones del 2025 en ctb_doc tipo 3 estado 2 ===" . PHP_EOL;
$sql = "SELECT id_tercero, COUNT(*) as total FROM ctb_doc 
        WHERE id_tipo_doc = 3 AND estado = 2 
        AND DATE_FORMAT(fecha, '%Y') = '2025'
        GROUP BY id_tercero ORDER BY total DESC LIMIT 10";
$rs = $cmd->query($sql);
echo "Top 10 terceros con más docs tipo 3 estado 2 en 2025:" . PHP_EOL;
foreach ($rs->fetchAll() as $r) {
    echo " - id_tercero={$r['id_tercero']} | docs={$r['total']}" . PHP_EOL;
}

echo PHP_EOL . "=== ¿Hay retenciones en ctb_causa_retencion para ese tercero? (vía id_terceroapi) ===" . PHP_EOL;
$sql = "SELECT COUNT(*) as total FROM ctb_causa_retencion WHERE id_terceroapi = $id_tercero_api";
$rs = $cmd->query($sql);
echo "Retenciones con id_terceroapi=6028: " . $rs->fetch()['total'] . PHP_EOL;

echo PHP_EOL . "=== Verificar el estado de los docs tipo 3 para terceros similares (6022, 6051) ===" . PHP_EOL;
foreach ([6022, 6051] as $t_id) {
    $sql = "SELECT id_tipo_doc, estado, MIN(fecha) as min_fecha, MAX(fecha) as max_fecha, COUNT(*) as total
            FROM ctb_doc WHERE id_tercero = $t_id
            GROUP BY id_tipo_doc, estado";
    $rs = $cmd->query($sql);
    $rows = $rs->fetchAll();
    echo "Tercero $t_id:" . PHP_EOL;
    foreach ($rows as $r) {
        echo "  tipo={$r['id_tipo_doc']} estado={$r['estado']} desde={$r['min_fecha']} hasta={$r['max_fecha']} count={$r['total']}" . PHP_EOL;
    }
}
