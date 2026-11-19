<?php
session_start();
if (!isset($_SESSION['user'])) {
    header('Location: ../../index.php');
    exit();
}

include '../../../../config/autoloader.php';
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
                ctb_doc.detalle,
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
            WHERE ctb_doc.fecha BETWEEN $fec_ini AND $fec_fin AND ctb_doc.estado = 2 
                AND ctb_pgcp.id_pgcp IN ('$id_cuenta_ini','$id_cuenta_fin')
                $and_where
            ORDER BY ctb_pgcp.fecha,ctb_pgcp.cuenta ASC";

    $rs = $cmd->query($sql);
    $obj_informe = $rs->fetchAll();
    $rs->closeCursor();
    unset($rs);
} catch (PDOException $e) {
    echo $e->getCode() == 2002 ? 'Sin Conexión a Mysql (Error: 2002)' : 'Error: ' . $e->getCode();
}

try {
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
            AND ctb_libaux.id_cuenta IN ('$id_cuenta_ini','$id_cuenta_ini') 
            AND ctb_doc.estado=2 limit 1";

    $rs = $cmd->query($sql);
    $obj_saldos = $rs->fetchAll();
    $rs->closeCursor();
    unset($rs);
} catch (PDOException $e) {
    echo $e->getCode() == 2002 ? 'Sin Conexión a Mysql (Error: 2002)' : 'Error: ' . $e->getCode();
}

$primer_caracter_cuenta = '';
$saldo_inicial = 0;
$total_deb = 0;
$total_cre = 0;

if ($obj_saldos[0]['filas'] > 0) {
    $primer_caracter_cuenta = substr($obj_saldos[0]['cuenta'], 0, 1);
    if ($primer_caracter_cuenta == 1 || $primer_caracter_cuenta == 5 || $primer_caracter_cuenta == 6 || $primer_caracter_cuenta == 7) {
        $saldo_inicial = $obj_saldos[0]['debito'] - $obj_saldos[0]['credito'];
    } else {
        $saldo_inicial = $obj_saldos[0]['credito'] - $obj_saldos[0]['debito'];
    }
} else {
    $saldo_inicial = 0;
    $total_deb = 0;
    $total_cre = 0;
}
?>

<div class="text-end py-3">
    <a type="button" id="btnExcelEntrada" class="btn btn-outline-success btn-sm" value="01" title="Exportar a Excel">
        <span class="fas fa-file-excel fa-lg" aria-hidden="true"></span>
    </a>
    <a type="button" class="btn btn-primary btn-sm" id="btnImprimir">Imprimir</a>
    <a type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal"> Cerrar</a>
</div>
<div class="content bg-light" id="areaImprimir">
    <style>
        .resaltar:nth-child(even) {
            background-color: #F8F9F9;
        }

        .resaltar:nth-child(odd) {
            background-color: #ffffff;
        }
    </style>
    <?php include('reporte_header.php'); ?>
    <table style="width:100%; margin: 6px 0;">
        <tr style="text-align:center">
            <th style="font-size:12px; font-weight:bold; border:none; text-align:center;">LIBROS AUXILIARES DE BANCOS
            </th>
        </tr>
        <?php if ($obj_saldos[0]['filas'] > 0) { ?>
            <tr style="text-align:center">
                <th style="font-size:11px; font-weight:bold; border:none; text-align:center;">
                    <b><?= $obj_saldos[0]['cuenta'] . ' - ' . $obj_saldos[0]['nombre']; ?></b></th>
            </tr>
        <?php } ?>
    </table>

    <table style="width:100% !important; border:#A9A9A9 1px solid; border-collapse: collapse;">
        <thead style="font-size:70%; border:#A9A9A9 1px solid;">
            <tr style="background-color:#CED3D3; color:#000000; text-align:center; border:#A9A9A9 1px solid;">
                <th style="border:#A9A9A9 1px solid;">Fecha</th>
                <th style="border:#A9A9A9 1px solid;">Tipo<br>Documento</th>
                <th style="border:#A9A9A9 1px solid;">Documento</th>
                <th style="border:#A9A9A9 1px solid;">Referencia</th>
                <th style="border:#A9A9A9 1px solid;" colspan="2">Tercero</th>
                <th style="border:#A9A9A9 1px solid;">CC/nit</th>
                <th style="border:#A9A9A9 1px solid;" colspan="2">Detalle</th>
                <th style="border:#A9A9A9 1px solid;">Debito</th>
                <th style="border:#A9A9A9 1px solid;">Credito</th>
                <th style="border:#A9A9A9 1px solid;">Saldo</th>
            </tr>
        </thead>
        <tbody style="font-size: 70%;">
            <?php
            $tabla = '';
            echo "<tr>
            <td class='text-end' colspan='10' style='border:#A9A9A9 1px solid; text-align:right; font-weight:bold;'>Saldo inicial: </td>
            <td class='text-end val-saldo' data-valor='" . $saldo_inicial . "' style='border:#A9A9A9 1px solid; text-align:right; font-weight:bold;'>" . number_format($saldo_inicial, 2, ",", ".") . "</td>
            </tr>";
            foreach ($obj_informe as $obj) {

                $primer_caracter = substr($obj['cuenta'], 0, 1);
                if ($primer_caracter == 1 || $primer_caracter == 5 || $primer_caracter == 6 || $primer_caracter == 7) {
                    $saldo_inicial = $saldo_inicial + $obj['debito'] - $obj['credito'];
                } else {
                    $saldo_inicial = $saldo_inicial + $obj['credito'] - $obj['debito'];
                }

                //-------------------------------
                $tabla .= '<tr class="resaltar"> 
                        <td style="border:#A9A9A9 1px solid; text-align:center;">' . $obj['fecha'] . '</td>
                        <td style="border:#A9A9A9 1px solid; text-align:center;">' . $obj['cod_tipo_doc'] . '</td>
                        <td style="border:#A9A9A9 1px solid; text-align:center;">' . $obj['id_manu'] . '</td>
                        <td style="border:#A9A9A9 1px solid; text-align:center;">' . mb_strtoupper($obj['forma_pago'] ?? '') . '</td>
                        <td style="border:#A9A9A9 1px solid; text-align:left;" colspan="2">' . mb_strtoupper($obj['nom_tercero']) . '</td>
                        <td style="border:#A9A9A9 1px solid; text-align:center;">' . $obj['nit_tercero'] . '</td>
                        <td style="border:#A9A9A9 1px solid; text-align:left;" colspan="2">' . mb_strtoupper($obj['detalle']) . '</td>
                        <td class="text-end val-debito" data-valor="' . $obj['debito'] . '" style="border:#A9A9A9 1px solid; text-align:right;">' . $obj['debito'] . '</td>
                        <td class="text-end val-credito" data-valor="' . $obj['credito'] . '" style="border:#A9A9A9 1px solid; text-align:right;">' . $obj['credito'] . '</td>
                        <td class="text-end val-saldo" data-valor="' . $saldo_inicial . '" style="border:#A9A9A9 1px solid; text-align:right;">' . $saldo_inicial . '</td></tr>';

                $total_deb += $obj['debito'];
                $total_cre += $obj['credito'];
            }
            echo $tabla;

            echo "<tr>
                <td class='text-end' colspan='8' style='border:#A9A9A9 1px solid; text-align:right; font-weight:bold;'> Total</td>
                <td class='text-end val-debito' data-valor='" . $total_deb . "' style='border:#A9A9A9 1px solid; text-align:right; font-weight:bold;'>" . number_format($total_deb, 2, ",", ".") . "</td>
                <td class='text-end val-credito' data-valor='" . $total_cre . "' style='border:#A9A9A9 1px solid; text-align:right; font-weight:bold;'>" . number_format($total_cre, 2, ",", ".") . "</td>
                <td class='text-end val-saldo' data-valor='" . $saldo_inicial . "' style='border:#A9A9A9 1px solid; text-align:right; font-weight:bold;'>" . number_format($saldo_inicial, 2, ",", ".") . "</td>
                </tr>";
            ?>
        </tbody>
    </table>
</div>

<script>
    $('#divModalImp').off('click', '#btnImprimir').on('click', '#btnImprimir', function (e) {
        e.preventDefault();
        e.stopImmediatePropagation();

        let $clone = $('#areaImprimir').clone();

        // Formatear debito, credito y saldo con separador de miles y centavos para impresión / PDF
        $clone.find('.val-debito, .val-credito, .val-saldo').each(function () {
            let raw = $(this).attr('data-valor');
            if (raw === undefined || raw === null || raw === '') {
                raw = $(this).text().trim();
            }
            let val = parseFloat(raw);
            if (!isNaN(val)) {
                $(this).text(val.toLocaleString('es-CO', { minimumFractionDigits: 2, maximumFractionDigits: 2 }));
            }
        });

        let ventimp = window.open(' ', 'popimpr');
        ventimp.document.write('<!DOCTYPE html><html><head><title>Libros Auxiliares de Bancos</title>');
        ventimp.document.write('<style>');
        ventimp.document.write(`
            @page { size: landscape; margin: 8mm; }
            body { font-family: Arial, Helvetica, sans-serif; font-size: 10px; margin: 0; padding: 5px; color: #000; background: #fff; }
            table { width: 100% !important; border-collapse: collapse; margin-bottom: 5px; }
            th, td { border: 1px solid #A9A9A9; padding: 3px 4px; font-size: 10px; }
            thead th { background-color: #CED3D3 !important; font-weight: bold; text-align: center; }
            .resaltar:nth-child(even) { background-color: #F8F9F9 !important; }
            .resaltar:nth-child(odd) { background-color: #ffffff !important; }
            .text-end { text-align: right !important; }
            .text-center { text-align: center !important; }
            .text-start { text-align: left !important; }
            * { -webkit-print-color-adjust: exact !important; print-color-adjust: exact !important; }
        `);
        ventimp.document.write('</style></head><body>');
        ventimp.document.write('<div>' + $clone.html() + '</div>');
        ventimp.document.write('</body></html>');
        ventimp.document.close();
        ventimp.focus();
        setTimeout(function () {
            ventimp.print();
            ventimp.close();
        }, 350);
    });
</script>