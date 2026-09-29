<?php
session_start();
set_time_limit(0);
ini_set('memory_limit', '-1');
if (!isset($_SESSION['user'])) {
    header('Location: ../../../index.php');
    exit();
}

header("Content-type: application/vnd.ms-excel charset=utf-8");
header("Content-Disposition: attachment; filename=Movimeinto_Bancos.xls");
header("Pragma: no-cache");
header("Expires: 0");

include '../../../config/autoloader.php';
$periodo = $_POST['periodo'];
$vigencia = $_SESSION['vigencia'];
session_write_close(); // Liberar la sesión para no bloquear el navegador
$vig_ant = $vigencia - 1;
$meses = '';
if ($periodo == 1) {
    $start_date = "$vigencia-01-01";
    $end_date = "$vigencia-06-30";
    $meses = 'ENERO - JUNIO';
    $mes_extracto_inicial = '12';
    $vig_extracto_inicial = $vig_ant;
    $mes_extracto_final = '06';
} else if ($periodo == 2) {
    $start_date = "$vigencia-07-01";
    $end_date = "$vigencia-12-31";
    $meses = 'JULIO - DICIEMBRE';
    $mes_extracto_inicial = '06';
    $vig_extracto_inicial = $vigencia;
    $mes_extracto_final = '12';
} else {
    $start_date = "$vigencia-01-01";
    $end_date = "$vigencia-12-31";
    $meses = 'ENERO - DICIEMBRE';
    $mes_extracto_inicial = '12';
    $vig_extracto_inicial = $vig_ant;
    $mes_extracto_final = '12';
}

$cmd = \Config\Clases\Conexion::getConexion();

    $sql_empresa = "SELECT razon_social_ips AS nombre, nit_ips AS nit, dv AS dig_ver FROM tb_datos_ips";
    $res_empresa = $cmd->query($sql_empresa);
    $empresa = $res_empresa->fetch();
    $nit_empresa = $empresa['nit'];
    $nombre_empresa = $empresa['nombre'];

try {
    $sql = "WITH mov_diarios AS (
                SELECT 
                    `cd`.`fecha`, 
                    `cl`.`id_cuenta`, 
                    SUM(IFNULL(`cl`.`debito`, 0)) AS `debito`, 
                    SUM(IFNULL(`cl`.`credito`, 0)) AS `credito`,
                    SUM(IF(`tcd`.`id_ctb_libaux` IS NULL, IFNULL(`cl`.`debito`, 0), 0)) AS `debito_nr`,
                    SUM(IF(`tcd`.`id_ctb_libaux` IS NULL, IFNULL(`cl`.`credito`, 0), 0)) AS `credito_nr`
                FROM `ctb_libaux` `cl`
                INNER JOIN `ctb_doc` `cd` ON `cl`.`id_ctb_doc` = `cd`.`id_ctb_doc`
                LEFT JOIN `tes_conciliacion_detalle` `tcd` ON `tcd`.`id_ctb_libaux` = `cl`.`id_ctb_libaux`
                WHERE `cd`.`estado` = 2
                GROUP BY `cd`.`fecha`, `cl`.`id_cuenta`
            )
            SELECT
                `ctb_pgcp`.`cuenta` AS `codigo`
                , `tb_bancos`.`nom_banco` AS `banco`
                , `tes_cuentas`.`numero`
                , `tes_cuentas`.`nombre` AS `denominacion`
                , `fin_cod_fuente`.`codigo` AS `fuente`
                , '$meses' AS `nom_mes`
                , IFNULL(`tt`.`saldo`,0) AS `saldo`
                , IFNULL(`ext_ini`.`saldo_extracto`, 0) AS `extr_inicial`
                , IFNULL(`mov`.`debito`, 0) AS `debito`
                , IFNULL(`mov`.`credito`, 0) AS `credito`
                , 0 AS `nd`
                , 0 AS `nc`
                , IFNULL(`tt`.`saldo`,0) + IFNULL(`mov`.`debito`, 0) - IFNULL(`mov`.`credito`, 0) AS `sf_libros`
                , IFNULL(`ext_fin`.`saldo_extracto`, 0) AS `sf_extracto`
                , IFNULL(`nr`.`debito`, 0) AS `sf_debito`
                , IFNULL(`nr`.`credito`, 0) AS `sf_credito`
            FROM
                `tes_cuentas`
                INNER JOIN `ctb_pgcp` ON (`tes_cuentas`.`id_cuenta` = `ctb_pgcp`.`id_pgcp`)
                INNER JOIN `tb_bancos` ON (`tes_cuentas`.`id_banco` = `tb_bancos`.`id_banco`)
                INNER JOIN `fin_cod_fuente` ON (`tes_cuentas`.`id_fte` = `fin_cod_fuente`.`id`)
                LEFT JOIN (
                    SELECT id_cuenta, SUM(debito - credito) AS saldo
                    FROM mov_diarios
                    WHERE fecha < '$start_date 00:00:00'
                    GROUP BY id_cuenta
                ) tt ON tt.id_cuenta = tes_cuentas.id_cuenta
                LEFT JOIN (
                    SELECT id_cuenta, SUM(debito) AS debito, SUM(credito) AS credito
                    FROM mov_diarios
                    WHERE fecha BETWEEN '$start_date 00:00:00' AND '$end_date 23:59:59'
                    GROUP BY id_cuenta
                ) mov ON mov.id_cuenta = tes_cuentas.id_cuenta
                LEFT JOIN `tes_conciliacion` ext_ini 
                    ON ext_ini.id_cuenta = tes_cuentas.id_tes_cuenta 
                    AND ext_ini.vigencia = '$vig_extracto_inicial' 
                    AND ext_ini.mes = '$mes_extracto_inicial'
                LEFT JOIN `tes_conciliacion` ext_fin 
                    ON ext_fin.id_cuenta = tes_cuentas.id_tes_cuenta 
                    AND ext_fin.vigencia = '$vigencia' 
                    AND ext_fin.mes = '$mes_extracto_final'
                LEFT JOIN (
                    SELECT id_cuenta, SUM(debito_nr) AS debito, SUM(credito_nr) AS credito
                    FROM mov_diarios
                    WHERE fecha <= '$end_date 23:59:59'
                    GROUP BY id_cuenta
                ) nr ON nr.id_cuenta = tes_cuentas.id_cuenta
            WHERE (`tes_cuentas`.`estado` = 1)
            ORDER BY `tes_cuentas`.`numero` ASC";
    $res = $cmd->query($sql);
    $lista = $res->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    echo $e->getCode() == 2002 ? 'Sin Conexión a Mysql (Error: 2002)' : 'Error: ' . $e->getCode();
}


echo "\xEF\xBB\xBF";
?>
<table class="table-bordered bg-light" style="width:100% !important;" border=1>
    <tr>
        <td colspan="15" style="text-align: center; font-weight: bold;">INFORME DE BANCOS</td>
    </tr>
    <tr>
        <td colspan="15" style="text-align: center; font-weight: bold;">AÑO: <?= $vigencia; ?></td>
    </tr>
    <tr>
        <td colspan="15" style="text-align: center; font-weight: bold;">PERIODO: <?= $meses ?></td>
    </tr>
    <tr>
        <th>Fila</th>
        <th>NIT</th>
        <th>Nombre de la entidad</th>
        <th>Código Contable</th>
        <th>Banco</th>
        <th>No. Cuenta</th>
        <th>Denominación</th>
        <th>Fte. Finciación</th>
        <th>Mes</th>
        <th>Saldo Inicial Libros</th>
        <th>Saldo Inicial Extracto</th>
        <th>Ingresos</th>
        <th>Egresos</th>
        <th>Notas Débito</th>
        <th>Notas Crédito</th>
        <th>Saldo Final Libros</th>
        <th>Saldo Final Extracto</th>
        <th>Saldo Conciliado</th>
    </tr>
    <tbody>
        <?php
        $fila = 1;
        foreach ($lista as $r) {
            $rubro = isset($r['rubro']) ? $r['rubro'] : (isset($r['codigo']) ? $r['codigo'] : (isset($r['cuenta']) ? $r['cuenta'] : ''));
            $rubro_limpio = preg_replace('/[^0-9]/', '', $rubro);

            $saldo_con = $r['sf_libros'] - ($r['sf_extracto'] + $r['sf_debito'] - $r['sf_credito']);
            echo "<tr>
                <td>{$fila}</td>\n                <td style='mso-number-format:\"\\@\"'>{$nit_empresa}</td>\n                <td>{$nombre_empresa}</td>\n                <td style='mso-number-format:\"\@\";'>{$rubro_limpio}</td>
                <td>{$r['banco']}</td>
                <td style='mso-number-format:\"\@\";'>{$r['numero']}</td>
                <td>{$r['denominacion']}</td>
                <td>{$r['fuente']}</td>
                <td>{$r['nom_mes']}</td>
                <td>{$r['saldo']}</td>
                <td>{$r['extr_inicial']}</td>
                <td>{$r['debito']}</td>
                <td>{$r['credito']}</td>
                <td>{$r['nd']}</td>
                <td>{$r['nc']}</td>
                <td>{$r['sf_libros']}</td>
                <td>{$r['sf_extracto']}</td>
                <td>{$saldo_con}</td>
            </tr>";
            $fila++;
        }
        ?>
    </tbody>
</table>