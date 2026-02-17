<?php
session_start();
ini_set("memory_limit", "-1");
if (!isset($_SESSION['user'])) {
    header('Location: ../../index.php');
    exit();
}

include '../../../../config/autoloader.php';
include '../../../financiero/consultas.php';
include 'funciones_generales.php';

$cmd = \Config\Clases\Conexion::getConexion();

$id_cuenta_ini = isset($_POST['id_cuenta_ini']) ? $_POST['id_cuenta_ini'] : 0;
$id_cuenta_fin = isset($_POST['id_cuenta_fin']) ? $_POST['id_cuenta_fin'] : 0;
$fec_ini = isset($_POST['fec_ini']) && strlen($_POST['fec_ini'] > 0) ? "'" . $_POST['fec_ini'] . "'" : '2020-01-01';
$fec_fin = isset($_POST['fec_fin']) && strlen($_POST['fec_fin']) > 0 ? "'" . $_POST['fec_fin'] . "'" : '2050-12-31';
$id_tipo_doc = isset($_POST['id_tipo_doc']) ? $_POST['id_tipo_doc'] : 0;
$id_tercero = isset($_POST['id_tercero']) ? $_POST['id_tercero'] : 0;

$and_where = '';
if ($id_tercero > 0) {
    $and_where .= " AND ctb_libaux.id_tercero_api = $id_tercero";
}
if ($id_tipo_doc > 0) {
    $and_where .= " AND ctb_doc.id_tipo_doc = $id_tipo_doc";
}

// Obtener sedes activas
$datos_sedes = obtenerSedesActivas($cmd);
$sedes = $datos_sedes['sedes'];
$sede_principal = $datos_sedes['sede_principal'];

try {
    $cmd = \Config\Clases\Conexion::getConexion();
    $sql = "SELECT 
                 ctb_pgcp.id_pgcp
                ,ctb_pgcp.cuenta
                ,ctb_pgcp.nombre
                ,ctb_pgcp.tipo_dato 
            FROM ctb_pgcp 
            WHERE ctb_pgcp.estado = 1
            AND ctb_pgcp.id_pgcp BETWEEN '$id_cuenta_ini' AND '$id_cuenta_fin'";
    $rs = $cmd->query($sql);
    $obj_cuentas = $rs->fetchAll();
    $rs->closeCursor();
    unset($rs);

    $sql = 'SELECT razon_social_ips,nit_ips FROM tb_datos_ips LIMIT 1';
    $rs = $cmd->query($sql);
    $obj_ent = $rs->fetch();
    $razhd = $obj_ent['razon_social_ips'];
    $nithd = $obj_ent['nit_ips'];
    $cmd = null;

?>

    <div class="text-end py-3">
        <a type="button" id="btnExcelEntrada" class="btn btn-outline-success btn-sm" value="01" title="Exportar a Excel">
            <span class="fas fa-file-excel fa-lg" aria-hidden="true"></span>
        </a>
        <a type="button" class="btn btn-primary btn-sm" id="btnImprimir">Imprimir</a>
        <a type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal"> Cerrar</a>
    </div>
    <div class="content bg-light" id="areaImprimirrr">
        <style>
            @media print {
                body {
                    font-family: Arial, sans-serif;
                }
            }

            .resaltar:nth-child(even) {
                background-color: #F8F9F9;
            }

            .resaltar:nth-child(odd) {
                background-color: #ffffff;
            }
        </style>
    </div>
    <?php
    $reg = 0;
    $cmd = \Config\Clases\Conexion::getConexion();

    foreach ($obj_cuentas as $obj_c) {
        // Array para consolidar movimientos de todas las sedes
        $obj_informe = [];
        $saldos_iniciales = ['debito' => 0, 'credito' => 0, 'filas' => 0];

        // Iterar sobre todas las sedes activas
        foreach ($sedes as $sede) {
            // Usar conexión actual para la sede principal, conectar para las demás
            if ($sede['es_principal'] == 1) {
                $cmd_sede = $cmd;
            } else {
                $cmd_sede = conectarSede($sede['bd_sede']);
                if ($cmd_sede === null) {
                    error_log("No se pudo conectar a la sede {$sede['nom_sede']} para el libro de bancos");
                    continue;
                }
            }

            try {
                //-----libros auxiliares de bancos -----------------------
                $sql = "SELECT
                            DATE_FORMAT(ctb_doc.fecha, '%Y-%m-%d') AS fecha,
                            ctb_pgcp.cuenta,
                            ctb_libaux.id_tercero_api,
                            IFNULL(ctb_libaux.debito,0) AS debito,
                            IFNULL(ctb_libaux.credito,0) AS credito,
                            ctb_doc.id_tipo_doc,
                            ctb_fuente.cod AS cod_tipo_doc,
                            ctb_fuente.nombre AS nom_tipo_doc,
                            ctb_doc.id_manu,
                            CONCAT(IFNULL(facturas.num_factura,''),' - ',ctb_doc.detalle) AS detalle,
                            tes_forma_pago.forma_pago,
                            tb_terceros.nom_tercero,
                            tb_terceros.nit_tercero
                        FROM 
                            ctb_libaux 
                        INNER JOIN ctb_doc ON (ctb_libaux.id_ctb_doc = ctb_doc.id_ctb_doc)
                        INNER JOIN ctb_pgcp ON (ctb_libaux.id_cuenta = ctb_pgcp.id_pgcp)
                        INNER JOIN ctb_fuente ON (ctb_doc.id_tipo_doc = ctb_fuente.id_doc_fuente)
                        LEFT JOIN tes_detalle_pago ON (tes_detalle_pago.id_ctb_doc = ctb_doc.id_ctb_doc)
                        LEFT JOIN tes_forma_pago ON (tes_detalle_pago.id_forma_pago = tes_forma_pago.id_forma_pago)
                        LEFT JOIN tb_terceros ON (tb_terceros.id_tercero_api = ctb_libaux.id_tercero_api)
                        LEFT JOIN
                            (SELECT 
                                doc.id_ctb_doc,
                                doc.id_manu,
                                doc.tipo_movimiento AS tipo,
                                CASE doc.tipo_movimiento
                                    WHEN 1 THEN CONCAT(ff.prefijo, IFNULL(ff.num_efactura, ff.num_factura))
                                    WHEN 2 THEN CONCAT(fo.prefijo, IFNULL(fo.num_efactura, fo.num_factura))
                                    WHEN 3 THEN CONCAT(fv.prefijo, IFNULL(fv.num_efactura, fv.num_factura))
                                    WHEN 4 THEN CONCAT(fc.prefijo, fc.num_factura)
                                END AS num_factura
                            FROM ctb_doc doc
                            LEFT JOIN fac_facturacion ff ON doc.tipo_movimiento = 1 AND doc.id_manu = ff.id_factura
                            LEFT JOIN fac_otros fo       ON doc.tipo_movimiento = 2 AND doc.id_manu = fo.id_factura
                            LEFT JOIN far_ventas fv      ON doc.tipo_movimiento = 3 AND doc.id_manu = fv.id_venta
                            LEFT JOIN fac_cartera fc     ON doc.tipo_movimiento = 4 AND doc.id_manu = fc.id_facturac
                            WHERE (doc.tipo_movimiento = 1 AND ff.id_factura IS NOT NULL)
                                OR (doc.tipo_movimiento = 2 AND fo.id_factura IS NOT NULL)
                                OR (doc.tipo_movimiento = 3 AND fv.id_venta IS NOT NULL)
                                OR (doc.tipo_movimiento = 4 AND fc.id_facturac IS NOT NULL)) AS facturas
                                ON (facturas.id_manu = ctb_doc.id_manu AND facturas.tipo = ctb_doc.tipo_movimiento
                                AND facturas.id_ctb_doc = ctb_doc.id_ctb_doc)
                        WHERE DATE_FORMAT(ctb_doc.fecha, '%Y-%m-%d') BETWEEN $fec_ini AND $fec_fin AND ctb_doc.estado = 2 
                            AND ctb_pgcp.id_pgcp IN ('" . $obj_c['id_pgcp'] . "','" . $obj_c['id_pgcp'] . "')
                            $and_where
                        ORDER BY DATE_FORMAT(ctb_doc.fecha, '%Y-%m-%d') ASC, ctb_libaux.debito DESC, ctb_libaux.credito DESC
                        LIMIT 500";
                $rs = $cmd_sede->query($sql);
                $movimientos_sede = $rs->fetchAll();
                $rs->closeCursor();

                // Agregar nombre de sede a cada movimiento
                foreach ($movimientos_sede as &$mov) {
                    $mov['nom_sede'] = $sede['nom_sede'];
                }
                unset($mov);

                // Agregar movimientos de esta sede al array consolidado
                $obj_informe = array_merge($obj_informe, $movimientos_sede);

                //-----consulta para debito y credito para saldo inicial-----------------------
                $sql = "SELECT
                            COUNT(*) AS filas
                            ,ctb_libaux.id_cuenta
                            ,ctb_pgcp.cuenta
                            ,ctb_pgcp.nombre
                            , SUM(IFNULL(ctb_libaux.debito,0)) AS debito 
                            , SUM(IFNULL(ctb_libaux.credito,0)) AS credito 
                        FROM
                            ctb_libaux
                            INNER JOIN ctb_doc ON (ctb_libaux.id_ctb_doc = ctb_doc.id_ctb_doc)
                            INNER JOIN ctb_pgcp ON (ctb_libaux.id_cuenta = ctb_pgcp.id_pgcp)
                        WHERE ctb_doc.fecha < $fec_ini  
                        AND ctb_libaux.id_cuenta IN ('" . $obj_c['id_pgcp'] . "','" . $obj_c['id_pgcp'] . "') 
                        AND ctb_doc.estado=2 limit 1";

                $rs = $cmd_sede->query($sql);
                $saldos_sede = $rs->fetchAll();
                $rs->closeCursor();

                // Acumular saldos iniciales de todas las sedes
                if (!empty($saldos_sede) && $saldos_sede[0]['filas'] > 0) {
                    $saldos_iniciales['debito'] += $saldos_sede[0]['debito'];
                    $saldos_iniciales['credito'] += $saldos_sede[0]['credito'];
                    $saldos_iniciales['filas'] += $saldos_sede[0]['filas'];
                    // Guardar cuenta para referencia
                    if (!isset($saldos_iniciales['cuenta'])) {
                        $saldos_iniciales['cuenta'] = $saldos_sede[0]['cuenta'];
                    }
                }
            } catch (PDOException $e) {
                error_log("Error consultando movimientos bancarios en sede {$sede['nom_sede']}: " . $e->getMessage());
            }

            // Cerrar la conexión si no es la principal
            if ($sede['es_principal'] != 1) {
                $cmd_sede = null;
            }
        }

        // Ordenar movimientos consolidados por fecha
        usort($obj_informe, function ($a, $b) {
            $comp = strcmp($a['fecha'], $b['fecha']);
            if ($comp === 0) {
                // Si las fechas son iguales, ordenar por débito desc, luego crédito desc
                if ($a['debito'] == $b['debito']) {
                    return $b['credito'] <=> $a['credito'];
                }
                return $b['debito'] <=> $a['debito'];
            }
            return $comp;
        });
        // Si no hay movimientos en esta cuenta, continuar con la siguiente
        if (empty($obj_informe)) {
            continue;
        }

        $primer_caracter_cuenta = '';
        $saldo_inicial = 0;
        $total_deb = 0;
        $total_cre = 0;

        if ($saldos_iniciales['filas'] > 0) {
            $primer_caracter_cuenta = substr($saldos_iniciales['cuenta'], 0, 1);
            if ($primer_caracter_cuenta == 1 || $primer_caracter_cuenta == 5 || $primer_caracter_cuenta == 6 || $primer_caracter_cuenta == 7) {
                $saldo_inicial = $saldos_iniciales['debito'] - $saldos_iniciales['credito'];
            } else {
                $saldo_inicial = $saldos_iniciales['credito'] - $saldos_iniciales['debito'];
            }
        } else {
            $saldo_inicial = 0;
            $total_deb = 0;
            $total_cre = 0;
        }
    ?>
        <div class="content bg-light" id="areaImprimir">
            <table style="width:100% !important; font-size:70%;">
                <?php
                if ($reg == 0) {
                    $reg++;
                ?>
                    <tr style="text-align: left;">
                        <th colspan="2">ENTIDAD</th>
                        <td colspan="10"><?= $razhd; ?></td>
                    </tr>
                    <tr style="text-align: left;">
                        <th colspan="2">NIT</th>
                        <td colspan="10"><?= "'" . $nithd . "'"; ?></td>
                    </tr>
                    <tr style="text-align: left;">
                        <th colspan="2">REPORTE</th>
                        <td colspan="10">LIBROS AUXILIARES</td>
                    </tr>
                    <tr style="text-align: left;">
                        <th colspan="2">FECHA INICIAL</th>
                        <td colspan="10"><?= $fec_ini; ?></td>
                    </tr>
                    <tr style="text-align: left;">
                        <th colspan="2">FECHA FINAL</th>
                        <td colspan="10"><?= $fec_fin; ?></td>
                    </tr>
                <?php
                }
                $reg++;
                ?>
                <tr style="text-align: left;">
                    <th colspan="2" style="font-weight: bold;">CUENTA</th>
                    <td colspan="10" style="font-weight: bold;"><?= strval($obj_c['cuenta'] . ' - ' . $obj_c['nombre']); ?></td>
                </tr>
                <tr style="background-color:#CED3D3; color:#000000; text-align:center;">
                    <th style="border: 1px solid #A9A9A9;">Sede</th>
                    <th style="border: 1px solid #A9A9A9;">Fecha</th>
                    <th style="border: 1px solid #A9A9A9;">Tipo<br>Documento</th>
                    <th style="border: 1px solid #A9A9A9;">Documento</th>
                    <th style="border: 1px solid #A9A9A9;">Referencia</th>
                    <th colspan="2" style="border: 1px solid #A9A9A9;">Tercero</th>
                    <th style="border: 1px solid #A9A9A9;">CC/nit</th>
                    <th colspan="2" style="border: 1px solid #A9A9A9;">Detalle</th>
                    <th style="border: 1px solid #A9A9A9;">Debito</th>
                    <th style="border: 1px solid #A9A9A9;">Credito</th>
                    <th style="border: 1px solid #A9A9A9;">Saldo</th>
                </tr>
                <tbody>
                    <?php
                    $tabla = '';
                    echo "<tr>
                            <td style='text-align: center; border: 1px solid #A9A9A9;' colspan='12'>Saldo inicial: </td>
                            <td style='text-align: right; border: 1px solid #A9A9A9;'>" . number_format($saldo_inicial, 2, ".", ",") . "</td>
                        </tr>";
                    foreach ($obj_informe as $obj) {

                        $primer_caracter = substr($obj['cuenta'], 0, 1);
                        if ($primer_caracter == 1 || $primer_caracter == 5 || $primer_caracter == 6 || $primer_caracter == 7) {
                            $saldo_inicial = $saldo_inicial + $obj['debito'] - $obj['credito'];
                        } else {
                            $saldo_inicial = $saldo_inicial + $obj['credito'] - $obj['debito'];
                        }

                        //-------------------------------
                        $tabla .=  '<tr class="resaltar"> 
                                <td style="border: 1px solid #A9A9A9;">' . $obj['nom_sede'] . '</td>
                                <td style="border: 1px solid #A9A9A9;">' . $obj['fecha'] . '</td>
                                <td style="border: 1px solid #A9A9A9;">' . $obj['cod_tipo_doc'] . '</td>
                                <td style="border: 1px solid #A9A9A9;">' . $obj['id_manu'] . '</td>
                                <td style="border: 1px solid #A9A9A9;">' . mb_strtoupper($obj['forma_pago']) . '</td>
                                <td colspan="2" style="border: 1px solid #A9A9A9;">' . mb_strtoupper($obj['nom_tercero']) . '</td>
                                <td style="border: 1px solid #A9A9A9;">' . $obj['nit_tercero'] . '</td>
                                <td colspan="2" style="border: 1px solid #A9A9A9;">' . mb_strtoupper($obj['detalle']) . '</td>
                                <td style="border: 1px solid #A9A9A9;">' . $obj['debito'] . '</td>
                                <td style="border: 1px solid #A9A9A9;">' . $obj['credito'] . '</td>
                                <td style="border: 1px solid #A9A9A9;">' . $saldo_inicial . '</td></tr>';

                        $total_deb += $obj['debito'];
                        $total_cre += $obj['credito'];
                    }
                    echo $tabla;

                    echo "<tr>
                        <td style='text-align: center; border: 1px solid #A9A9A9;' colspan='10'> Total</td>
                        <td style='text-align: center; border: 1px solid #A9A9A9;'>Debito: " . number_format($total_deb, 2, ".", ",") . "</td>
                        <td style='text-align: center; border: 1px solid #A9A9A9;'>Credito: " . number_format($total_cre, 2, ".", ",") . "</td>
                        <td style='text-align: center; border: 1px solid #A9A9A9;'>Saldo: " . number_format($saldo_inicial, 2, ".", ",") . "</td>
                        </tr>
                        <tr><td colspan='13'>&nbsp;</td></tr>";
                    ?>
                </tbody>
            </table>
        </div>
<?php
    }
} catch (PDOException $e) {
    echo $e->getCode() == 2002 ? 'Sin Conexión a Mysql (Error: 2002)' : 'Error: ' . $e->getMessage();
}
