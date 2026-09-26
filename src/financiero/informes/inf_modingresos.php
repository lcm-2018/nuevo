<?php
session_start();
set_time_limit(0);
ini_set('memory_limit', '-1');
if (!isset($_SESSION['user'])) {
    header('Location: ../../../index.php');
    exit();
}

header("Content-type: application/vnd.ms-excel charset=utf-8");
header("Content-Disposition: attachment; filename=Modificacion_Ingresos.xls");
header("Pragma: no-cache");
header("Expires: 0");

include '../../../config/autoloader.php';
$periodo = $_POST['periodo'];
$vigencia = $_SESSION['vigencia'];
$id_vigencia = $_SESSION['id_vigencia'];
$meses = '';
if ($periodo == 1) {
    $rango = "'$vigencia-01-01' AND '$vigencia-06-30'";
    $meses = 'JUNIO';
} else if ($periodo == 2) {
    $rango = "'$vigencia-07-01' AND '$vigencia-12-31'";
    $meses = 'DICIEMBRE';
} else {
    $rango = "'$vigencia-01-01' AND '$vigencia-12-31'";
    $meses = 'ANUAL';
}

$cmd = \Config\Clases\Conexion::getConexion();

    $sql_empresa = "SELECT razon_social_ips AS nombre, nit_ips AS nit, dv AS dig_ver FROM tb_datos_ips";
    $res_empresa = $cmd->query($sql_empresa);
    $empresa = $res_empresa->fetch();
    $nit_empresa = $empresa['nit'];
    $nombre_empresa = $empresa['nombre'];

try {
    $sql = "SELECT
                `pto_mod`.`id_pto_mod`
                , `pto_actos_admin`.`nombre`
                , CONCAT(IFNULL(`pto_sia`.`codigo`,''),`pto_cargue`.`cod_pptal`) AS `cod_pptal`
                , `pto_mod`.`numero_acto`
                , DATE_FORMAT(`pto_mod`.`fecha`,'%Y-%m-%d') AS `fecha`
                , `detalles`.`debito`
                , `detalles`.`credito`
            FROM
                (SELECT
                    `id_pto_mod`
                    , `id_cargue`
                    , SUM(`valor_deb`) AS `debito`
                    , SUM(`valor_cred`) AS `credito`
                FROM
                    `pto_mod_detalle`
                GROUP BY `id_pto_mod`, `id_cargue`) AS `detalles`
                INNER JOIN `pto_mod` 
                    ON (`detalles`.`id_pto_mod` = `pto_mod`.`id_pto_mod`)
                INNER JOIN `pto_actos_admin` 
                    ON (`pto_mod`.`id_tipo_acto` = `pto_actos_admin`.`id_acto`)
                INNER JOIN `pto_cargue` 
                    ON (`detalles`.`id_cargue` = `pto_cargue`.`id_cargue`)
                INNER JOIN `pto_presupuestos` 
                    ON (`pto_cargue`.`id_pto` = `pto_presupuestos`.`id_pto`)
                INNER JOIN `pto_homologa_ingresos` 
                    ON (`pto_homologa_ingresos`.`id_cargue` = `pto_cargue`.`id_cargue`)
                INNER JOIN `pto_sia` 
                    ON (`pto_homologa_ingresos`.`id_sia` = `pto_sia`.`id_sia`)
            WHERE (`pto_mod`.`estado` = 2 AND `pto_presupuestos`.`id_tipo` = 1 AND DATE_FORMAT(`pto_mod`.`fecha`,'%Y-%m-%d') BETWEEN $rango)
            ORDER BY  DATE_FORMAT(`pto_mod`.`fecha`,'%Y-%m-%d')";
    $res = $cmd->query($sql);
    $lista = $res->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    echo $e->getCode() == 2002 ? 'Sin Conexión a Mysql (Error: 2002)' : 'Error: ' . $e->getCode();
}


echo "\xEF\xBB\xBF";
?>
<table class="table-bordered bg-light" style="width:100% !important;" border=1>
    <tr>
        <td colspan="6" style="text-align: center; font-weight: bold;">MODIFICACIONES AL PRESUPUESTO INGRESOS</td>
    </tr>
    <tr>
        <td colspan="6" style="text-align: center; font-weight: bold;">AÑO: <?= $vigencia; ?></td>
    </tr>
    <tr>
        <td colspan="6" style="text-align: center; font-weight: bold;">PERIODO: <?= $meses ?></td>
    </tr>
    <tr>
        <th>Fila</th>
        <th>NIT</th>
        <th>Nombre de la entidad</th>
        <th>Tipo</th>
        <th>Código Rubro Presupuestal</th>
        <th>Acto Administrativo</th>
        <th>Fecha</th>
        <th>Adiciones</th>
        <th>Reducciones</th>
    </tr>
    <tbody>
        <?php
$fila = 1;
        foreach ($lista as $r) {
            $rubro = isset($r['rubro']) ? $r['rubro'] : (isset($r['codigo']) ? $r['codigo'] : (isset($r['cuenta']) ? $r['cuenta'] : ''));
            $rubro_limpio = preg_replace('/[^0-9]/', '', $rubro);

    echo "<tr>
                <td>{$fila}</td>\n                <td style='mso-number-format:\"\\@\"'>{$nit_empresa}</td>\n                <td>{$nombre_empresa}</td>\n                <td>{$r['nombre']}</td>
                <td style='mso-number-format:\"\\@\"'>{$r['cod_pptal']}</td>
                <td>{$r['numero_acto']}</td>
                <td>{$r['fecha']}</td>
                <td>{$r['debito']}</td>
                <td>{$r['credito']}</td>
            </tr>";
            $fila++;
}
?>
    </tbody>
</table>