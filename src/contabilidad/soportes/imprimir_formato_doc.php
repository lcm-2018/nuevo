<?php
session_start();
set_time_limit(3600);
if (!isset($_SESSION['user'])) {
    header("Location: ../../../index.php");
    exit();
}
$vigencia = $_SESSION['vigencia'];
$id_vigencia = $_SESSION['id_vigencia'];
$dto = $_POST['id'];
$dto = implode(',', $_POST['id']);
$tipo_doc = $_POST['tipo'];
$prefijo = '';
function pesos($valor)
{
    return '$' . number_format($valor, 2);
}
include '../../../config/autoloader.php';
$id_rol = $_SESSION['rol'];
$id_user = $_SESSION['id_user'];

use Config\Clases\Plantilla;
use Src\Common\Php\Clases\Permisos;

$permisos = new Permisos();
$opciones = $permisos->PermisoOpciones($id_user);
$cmd = \Config\Clases\Conexion::getConexion();

include '../../financiero/consultas.php';

$id_t = [];
try {
    $sql = "SELECT 
                `ctb_doc`.`id_ctb_doc`
                ,`ctb_doc`.`detalle`
                , `ctb_doc`.`fecha`
                , `ctb_doc`.`id_manu`
                , `ctb_doc`.`id_tercero`
                , `ctb_doc`.`fecha_reg`
                , `ctb_doc`.`id_tipo_doc` AS `tipo_doc`
                , `ctb_doc`.`estado`
                , CONCAT_WS (' ',`us1`.`nombre1`, `us1`.`nombre2`, `us1`.`apellido1`, `us1`.`apellido2`) AS `usuario_reg`
                , CONCAT_WS (' ',`us2`.`nombre1`, `us2`.`nombre2`, `us2`.`apellido1`, `us2`.`apellido2`) AS `usuario_act`
                , `tb_terceros`.`nom_tercero`
                , `tb_terceros`.`nit_tercero`
            FROM `ctb_doc`
                INNER JOIN `seg_usuarios_sistema`  AS `us1` 
                    ON (`ctb_doc`.`id_user_reg` = `us1`.`id_usuario`)
                LEFT JOIN `seg_usuarios_sistema`  AS `us2`
                    ON (`ctb_doc`.`id_user_act` = `us2`.`id_usuario`)
                LEFT JOIN `tb_terceros` 
                    ON (`ctb_doc`.`id_tercero` = `tb_terceros`.`id_tercero_api`)
            WHERE `ctb_doc`.`id_ctb_doc` IN ($dto)";
    $res = $cmd->query($sql);
    $documentos = $res->fetchAll();
    $res->closeCursor();
    unset($res);
} catch (PDOException $e) {
    echo $e->getCode() == 2002 ? 'Sin Conexión a Mysql (Error: 2002)' : 'Error: ' . $e->getCode();
}
?>
<div class="text-end py-3">
    <?php if ($permisos->PermisosUsuario($opciones, 5501, 6)  || $id_rol == 1) { ?>
        <a type="button" class="btn btn-primary btn-sm" onclick="imprSelecDoc('areaImprimir','<?php echo implode('|', $_POST['id']); ?>');"> Imprimir</a>
    <?php } ?>
    <a type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal"> Cerrar</a>
</div>
<div class="contenedor bg-light" id="areaImprimir">
    <style>
        /* Estilos para la pantalla */
        .watermark {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%) rotate(-45deg);
            /* Se añade rotación */
            font-size: 100px;
            color: rgba(255, 0, 0, 0.2);
            /* Cambia la opacidad para que sea tenue */
            z-index: 1000;
            pointer-events: none;
            /* Para que no interfiera con el contenido */
            white-space: nowrap;
            /* Evita que el texto se divida en varias líneas */
        }

        /* Estilos específicos para la impresión */
        @media print {

            body {
                position: relative;
            }

            .watermark {
                position: fixed;
                /* Cambiar a 'fixed' para impresión */
                top: 50%;
                left: 50%;
                transform: translate(-50%, -50%) rotate(-45deg);
                font-size: 100px;
                color: rgba(255, 0, 0, 0.2);
                /* Asegura que el color y opacidad se mantengan */
                z-index: -1;
                /* Colocar detrás del contenido impreso */
            }

            .page-break {
                page-break-after: always;
            }
        }
    </style>
    <?php
    try {
        $sql = "SELECT `cod`, `nombre` FROM `ctb_fuente` WHERE `id_doc_fuente` = $tipo_doc";
        $res = $cmd->query($sql);
        $dss = $res->fetch();
    } catch (PDOException $e) {
        echo $e->getCode() == 2002 ? 'Sin Conexión a Mysql (Error: 2002)' : 'Error: ' . $e->getCode();
    }
    $doc_fte = $dss['cod'];
    $id_modulo = 55;
    $nombre_doc = $dss['nombre'];
    foreach ($documentos as $doc) {
        $dto = $doc['id_ctb_doc'];
        try {
            $sql = "SELECT
                `ctb_causa_costos`.`valor`
                , `tb_centrocostos`.`nom_centro` AS `nom_area`
                , `tb_municipios`.`nom_municipio`
            FROM
                `ctb_causa_costos`
                INNER JOIN `far_centrocosto_area` 
                    ON (`ctb_causa_costos`.`id_area_cc` = `far_centrocosto_area`.`id_area`)
                INNER JOIN `tb_centrocostos`
		            ON (`tb_centrocostos`.`id_centro` = `far_centrocosto_area`.`id_centrocosto`)
                INNER JOIN `tb_sedes` 
                    ON (`far_centrocosto_area`.`id_sede` = `tb_sedes`.`id_sede`)
                INNER JOIN `tb_municipios` 
                    ON (`tb_sedes`.`id_municipio` = `tb_municipios`.`id_municipio`)
            WHERE (`ctb_causa_costos`.`id_ctb_doc` = $dto)";
            $res = $cmd->query($sql);
            $costos = $res->fetchAll();
            $res->closeCursor();
            unset($res);
        } catch (PDOException $e) {
            echo $e->getCode() == 2002 ? 'Sin Conexión a Mysql (Error: 2002)' : 'Error: ' . $e->getCode();
        }
        $anulado = $doc['estado'] == '0' ? 'ANULADO' : '';
        $id_t[] = $doc['id_tercero'] > 0 ? $doc['id_tercero'] : 0;
        $num_doc = '';
        // Valor total del cdp
        try {
            $sql = "SELECT SUM(`debito`) as `valor` FROM `ctb_libaux` WHERE `id_ctb_doc` = $dto";
            $res = $cmd->query($sql);
            $datos = $res->fetch();
            $total = $datos['valor'];
        } catch (PDOException $e) {
            echo $e->getCode() == 2002 ? 'Sin Conexión a Mysql (Error: 2002)' : 'Error: ' . $e->getCode();
        }
        try {
            $sql = "SELECT SUM(`debito`) as `valor` FROM `ctb_libaux` WHERE `id_ctb_doc` = $dto";
            $res = $cmd->query($sql);
            $datos = $res->fetch();
            $total = $datos['valor'];
        } catch (PDOException $e) {
            echo $e->getCode() == 2002 ? 'Sin Conexión a Mysql (Error: 2002)' : 'Error: ' . $e->getCode();
        }
        $enletras = numeroLetras($total);
        try {
            $sql = "SELECT
                `pto_crp`.`id_manu`
                , `pto_cargue`.`cod_pptal` AS `rubro`
                , `pto_cargue`.`nom_rubro`
                , `pto_cop_detalle`.`id_tercero_api`
                , `pto_cop_detalle`.`valor` -`pto_cop_detalle`.`valor_liberado` AS `valor`
                , 'COP' AS `tipo_mov`
                , `tb_terceros`.`nom_tercero`
                , `tb_terceros`.`nit_tercero`
            FROM
                `pto_cop_detalle`
                INNER JOIN `ctb_doc` 
                    ON (`pto_cop_detalle`.`id_ctb_doc` = `ctb_doc`.`id_ctb_doc`)
                INNER JOIN `pto_crp_detalle` 
                    ON (`pto_cop_detalle`.`id_pto_crp_det` = `pto_crp_detalle`.`id_pto_crp_det`)
                INNER JOIN `pto_cdp_detalle` 
                    ON (`pto_crp_detalle`.`id_pto_cdp_det` = `pto_cdp_detalle`.`id_pto_cdp_det`)
                INNER JOIN `pto_crp`
                    ON (`pto_crp_detalle`.`id_pto_crp` = `pto_crp`.`id_pto_crp`)
                INNER JOIN `pto_cargue` 
                    ON (`pto_cdp_detalle`.`id_rubro` = `pto_cargue`.`id_cargue`)
                INNER JOIN `ctb_fuente` 
                    ON (`ctb_doc`.`id_tipo_doc` = `ctb_fuente`.`id_doc_fuente`)
                LEFT JOIN `tb_terceros` 
                    ON (`pto_cop_detalle`.`id_tercero_api` = `tb_terceros`.`id_tercero_api`)
            WHERE (`ctb_doc`.`id_ctb_doc` = $dto)";
            $res = $cmd->query($sql);
            $rubros = $res->fetchAll();
            $res->closeCursor();
            unset($res);
        } catch (PDOException $e) {
            echo $e->getCode() == 2002 ? 'Sin Conexión a Mysql (Error: 2002)' : 'Error: ' . $e->getCode();
        }
        // Datos de la factura 
        try {
            $sql = "SELECT
                `ctb_doc`.`id_ctb_doc`
                , `ctb_tipo_doc`.`tipo` AS `tipo_doc`
                , `ctb_fuente`.`nombre` AS `tipo`
                , `ctb_factura`.`num_doc`
                , `ctb_factura`.`fecha_fact`
                , `ctb_factura`.`fecha_ven`
                , `ctb_factura`.`valor_pago`
                , `ctb_factura`.`valor_iva`
                , `ctb_factura`.`valor_base`
            FROM
                `ctb_factura`
                INNER JOIN `ctb_doc` 
                    ON (`ctb_factura`.`id_ctb_doc` = `ctb_doc`.`id_ctb_doc`)
                INNER JOIN `ctb_fuente` 
                    ON (`ctb_doc`.`id_tipo_doc` = `ctb_fuente`.`id_doc_fuente`)
                INNER JOIN `ctb_tipo_doc` 
                    ON (`ctb_factura`.`id_tipo_doc` = `ctb_tipo_doc`.`id_ctb_tipodoc`)
            WHERE (`ctb_doc`.`id_ctb_doc` = $dto)";
            $res = $cmd->query($sql);
            $facturas = $res->fetchAll();
            $res->closeCursor();
            unset($res);
        } catch (PDOException $e) {
            echo $e->getCode() == 2002 ? 'Sin Conexión a Mysql (Error: 2002)' : 'Error: ' . $e->getCode();
        }
        // Movimiento contable
        try {
            $sql = "SELECT
                        `ctb_libaux`.`id_tercero_api` AS `id_tercero`
                        , `ctb_pgcp`.`cuenta`
                        , `ctb_pgcp`.`nombre`
                        , `ctb_libaux`.`debito`
                        , `ctb_libaux`.`credito`
                        , `ctb_fuente`.`nombre` AS `fuente`
                        , `tb_terceros`.`nit_tercero`
                        , `tb_terceros`.`nom_tercero`
                    FROM
                        `ctb_libaux`
                        INNER JOIN `ctb_doc` 
                            ON (`ctb_libaux`.`id_ctb_doc` = `ctb_doc`.`id_ctb_doc`)
                        INNER JOIN `ctb_pgcp` 
                            ON (`ctb_libaux`.`id_cuenta` = `ctb_pgcp`.`id_pgcp`)
                        INNER JOIN `ctb_fuente` 
                            ON (`ctb_doc`.`id_tipo_doc` = `ctb_fuente`.`id_doc_fuente`)
                        LEFT JOIN `tb_terceros` 
                            ON (`ctb_libaux`.`id_tercero_api` = `tb_terceros`.`id_tercero_api`)
                    WHERE (`ctb_doc`.`id_ctb_doc` = $dto)
                    ORDER BY `ctb_pgcp`.`cuenta`,`ctb_pgcp`.`nombre` DESC";
            $res = $cmd->query($sql);
            $movimiento = $res->fetchAll();
            $res->closeCursor();
            unset($res);
        } catch (PDOException $e) {
            echo $e->getCode() == 2002 ? 'Sin Conexión a Mysql (Error: 2002)' : 'Error: ' . $e->getCode();
        }
        // consulta para motrar cuadro de retenciones
        try {
            $sql = "SELECT
                `ctb_causa_retencion`.`id_ctb_doc`
                , `ctb_causa_retencion`.`id_causa_retencion`
                , `ctb_retencion_tipo`.`tipo`
                , `ctb_retenciones`.`nombre_retencion`
                , `ctb_causa_retencion`.`valor_base`
                , `ctb_causa_retencion`.`tarifa`
                , `ctb_causa_retencion`.`valor_retencion`
                , `ctb_causa_retencion`.`id_terceroapi`
                , `tb_terceros`.`nom_tercero`
                , `tb_terceros`.`nit_tercero`
            FROM
                `ctb_retenciones`
                LEFT JOIN `ctb_retencion_tipo` 
                    ON (`ctb_retenciones`.`id_retencion_tipo` = `ctb_retencion_tipo`.`id_retencion_tipo`)
                LEFT JOIN `ctb_retencion_rango` 
                    ON (`ctb_retencion_rango`.`id_retencion` = `ctb_retenciones`.`id_retencion`)
                LEFT JOIN `ctb_causa_retencion` 
                    ON (`ctb_causa_retencion`.`id_rango` = `ctb_retencion_rango`.`id_rango`)
                LEFT JOIN `tb_terceros` 
                    ON (`ctb_causa_retencion`.`id_terceroapi` = `tb_terceros`.`id_tercero_api`)
            WHERE (`ctb_causa_retencion`.`id_ctb_doc` = $dto)";
            $rs = $cmd->query($sql);
            $retenciones = $rs->fetchAll();
            $rs->closeCursor();
            unset($rs);
        } catch (PDOException $e) {
            echo $e->getCode() == 2002 ? 'Sin Conexión a Mysql (Error: 2002)' : 'Error: ' . $e->getCode();
        }
        // consulto el nombre de la empresa de la tabla tb_datos_ips
        try {
            $sql = "SELECT 
                `tb_datos_ips`.`razon_social_ips` AS `nombre`, `tb_datos_ips`.`nit_ips` AS `nit`, `tb_datos_ips`.`dv` AS `dig_ver`, `tb_municipios`.`nom_municipio`
            FROM `tb_datos_ips`
                INNER JOIN `tb_municipios`
                    ON (`tb_datos_ips`.`idmcpio` = `tb_municipios`.`id_municipio`)";
            $res = $cmd->query($sql);
            $empresa = $res->fetch();
        } catch (PDOException $e) {
            echo $e->getCode() == 2002 ? 'Sin Conexión a Mysql (Error: 2002)' : 'Error: ' . $e->getCode();
        }
        // consulto el tipo de control del documento
        $fecha = date('Y-m-d', strtotime($doc['fecha']));
        include_once '../../financiero/encabezado_imp.php';

        $hora = date('H:i:s', strtotime($doc['fecha_reg']));

        try {
            $sql = "SELECT 
                `tb_datos_ips`.`razon_social_ips` AS `nombre`, `tb_datos_ips`.`nit_ips` AS `nit`, `tb_datos_ips`.`dv` AS `dig_ver`, `tb_municipios`.`nom_municipio`
            FROM `tb_datos_ips`
                INNER JOIN `tb_municipios`
                    ON (`tb_datos_ips`.`idmcpio` = `tb_municipios`.`id_municipio`)";
            $res = $cmd->query($sql);
            $empresa = $res->fetch();
        } catch (PDOException $e) {
            echo $e->getCode() == 2002 ? 'Sin Conexión a Mysql (Error: 2002)' : 'Error: ' . $e->getCode();
        }

        $meses = [
            '01' => 'enero',
            '02' => 'febrero',
            '03' => 'marzo',
            '04' => 'abril',
            '05' => 'mayo',
            '06' => 'junio',
            '07' => 'julio',
            '08' => 'agosto',
            '09' => 'septiembre',
            '10' => 'octubre',
            '11' => 'noviembre',
            '12' => 'diciembre'
        ];
    ?>
        <div class="px-2 " style="width:90% !important;margin: 0 auto;">

            </br>
            <table class="table-bordered bg-light" style="width:100% !important;">
                <tr>
                    <td class='text-center' style="width:18%"><label class="small"><img src="../../assets/images/logo.png" width="100"></label></td>
                    <td style="text-align:center">
                        <strong><?php echo $empresa['nombre']; ?> </strong>
                        <div>NIT <?php echo $empresa['nit'] . '-' . $empresa['dig_ver']; ?></div>
                    </td>
                </tr>
            </table>

            </br>


            <div class="row mb-2 px-2" style="text-align: center">
                <div class="col-12">
                    <div class="col lead"><label><strong><?php echo $nombre_doc . ': ' . $doc['id_manu']; ?></strong></label></div>
                </div>
            </div>

            <div class="row mb-2">
                <div class="col-12">
                    <div style="text-align: left">
                        <div><strong>Datos generales: </strong></div>
                    </div>
                </div>
            </div>
            <table class="table-bordered bg-light" style="width:100% !important;">
                <tr>
                    <td class='text-start' style="width:18%">FECHA:</td>
                    <td class='text-start'><?php echo $fecha . ' ' . $hora; ?></td>
                </tr>
                <tr>
                    <td class='text-start' style="width:18%">TERCERO:</td>
                    <td class='text-start'>
                        <?php
                        echo $doc['nom_tercero'];
                        ?>
                    </td>
                </tr>
                <tr>
                    <td class='text-start' style="width:18%">CC/NIT:</td>
                    <td class='text-start'><?php echo $doc['nit_tercero'] ?></td>
                </tr>
                <tr>
                    <td class='text-start'>OBJETO:</td>
                    <td class='text-start'><?php echo mb_strtoupper($doc['detalle']); ?></td>
                </tr>
                <tr>
                    <td class='text-start'>VALOR:</td>
                    <td class='text-start'><label><?php echo $enletras . "  $" . number_format($total, 2, ",", "."); ?></label></td>
                </tr>
            </table>
            <div class="watermark">
                <h3><?php echo $anulado ?></h3>
            </div>

            <?php if ($doc['tipo_doc'] == '3' || $doc['tipo_doc'] == '5') {
                if ($_SESSION['pto'] == '1') {
            ?>
                    </br>
                    <div class="row mb-2">
                        <div class="col-12">
                            <div style="text-align: left">
                                <div><strong>Imputación presupuestal: </strong></div>
                            </div>
                        </div>
                    </div>
                    <table class="table-bordered" style="width:100% !important; border-collapse: collapse; " cellspacing="2">
                        <tr>
                            <?php
                            if ($doc['tipo_doc'] == '5') {
                            ?>
                                <td style="text-align: left;border: 1px solid black ">Número Rp </td>
                                <td style="text-align: left;border: 1px solid black ">Cc/nit</td>
                                <td style="border: 1px solid black ">Código</td>
                                <td style="border: 1px solid black ">Nombre</td>
                                <td style="border: 1px solid black;text-align:center">Valor</td>
                            <?php
                            } else {
                            ?>
                                <td style="text-align: left;border: 1px solid black ">Número Rp</td>
                                <td style="border: 1px solid black ">Código</td>
                                <td style="border: 1px solid black ">Nombre</td>
                                <td style="border: 1px solid black;text-align:center">Valor</td>
                            <?php
                            }
                            ?>
                        </tr>
                        <?php
                        $total_pto = 0;
                        if ($doc['tipo_doc'] == '5') {
                            foreach ($rubros as $rp) {
                                if ($rp['tipo_mov'] == 'COP') {
                                    echo "<tr>
                                    <td class='text-start' style='border: 1px solid black '>" . $rp['id_manu'] . "</td>
                                    <td class='text-start' style='border: 1px solid black '>" . $rp['nit_tercero'] . "</td>
                                    <td class='text-start' style='border: 1px solid black '>" . $rp['rubro'] . "</td>
                                    <td class='text-start' style='border: 1px solid black '>" . $rp['nom_rubro'] . "</td>
                                    <td class='text-end' style='border: 1px solid black; text-align: right'>" . number_format($rp['valor'], 2, ",", ".")  . "</td>
                                </tr>";
                                    $total_pto += $rp['valor'];
                                }
                            }
                        } else {
                            foreach ($rubros as $rp) {
                                if ($rp['tipo_mov'] == 'COP') {
                                    echo "<tr>
                                    <td class='text-start' style='border: 1px solid black '>" . $rp['id_manu'] . "</td>
                                    <td class='text-start' style='border: 1px solid black '>" . $rp['rubro'] . "</td>
                                    <td class='text-start' style='border: 1px solid black '>" . $rp['nom_rubro'] . "</td>
                                    <td class='text-end' style='border: 1px solid black; text-align: right'>" . number_format($rp['valor'], 2, ",", ".")  . "</td>
                                </tr>";
                                    $total_pto += $rp['valor'];
                                }
                            }
                        }
                        ?>
                        <?php
                        if ($doc['tipo_doc'] == '5') {
                        ?>
                            <tr>
                                <td colspan="4" style="text-align:left;border: 1px solid black ">Total</td>
                                <td style="text-align: right;border: 1px solid black "><?php echo number_format($total_pto, 2, ",", "."); ?></td>
                            </tr>
                        <?php
                        } else {
                        ?>
                            <tr>
                                <td colspan="3" style="text-align:left;border: 1px solid black ">Total</td>
                                <td style="text-align: right;border: 1px solid black "><?php echo number_format($total_pto, 2, ",", "."); ?></td>
                            </tr>
                        <?php
                        }
                        ?>
                    </table>
                    </br>
                    <?php
                }
                if ($doc['tipo_doc'] != '5') {
                    if (!empty($facturas)) {
                    ?>
                        <br>
                        <div class="row mb-2">
                            <div class="col-12">
                                <div style="text-align: left">
                                    <div><strong>Datos de la factura: </strong></div>
                                </div>
                            </div>
                        </div>
                        <table class="table-bordered bg-light" style="width:100% !important;">
                            <tr style="text-align: center">
                                <td>Documento</td>
                                <td>Valor Total</td>
                                <td>Valor IVA</td>
                                <td>Base</td>
                            </tr>
                            <?php
                            $t_pago = 0;
                            $t_iva = 0;
                            $t_base = 0;
                            $cr = 0;
                            foreach ($facturas as $factura) {
                                if ($empresa['nit'] == 844001355 && $factura['tipo_doc'] == 3) {
                                    $prefijo = 'RSC-';
                                } else {
                                    $prefijo = '';
                                }
                            ?>

                                <tr>
                                    <td style="text-align: left"><?php echo $factura['tipo_doc'] . ' ' . $prefijo . $factura['num_doc']; ?></td>
                                    <td style="text-align: right"><?php echo number_format($factura['valor_pago'], 2, ',', '.'); ?></td>
                                    <td style="text-align: right"><?php echo  number_format($factura['valor_iva'], 2, ',', '.');; ?></td>
                                    <td style="text-align: right"><?php echo number_format($factura['valor_base'], 2, ',', '.'); ?></td>
                                </tr>
                        <?php
                                $t_pago += $factura['valor_pago'];
                                $t_iva += $factura['valor_iva'];
                                $t_base += $factura['valor_base'];
                                $cr++;
                            }
                            if ($cr > 1) {
                                echo "<tr style='text-align: right'>
                                    <td style='text-align: center'>TOTAL</td>
                                    <td style='text-align: right'>" . number_format($t_pago, 2, ',', '.') . "</td>
                                    <td style='text-align: right'>" . number_format($t_iva, 2, ',', '.') . "</td>
                                    <td style='text-align: right'>" . number_format($t_base, 2, ',', '.') . "</td>
                                </tr>";
                            }
                            echo "</table>";
                        }
                        ?>
                        </br>
                        <div class="row mb-2">
                            <div class="col-12">
                                <div style="text-align: left">
                                    <div><strong>Retenciones y descuentos: </strong></div>
                                </div>
                            </div>
                        </div>
                        <table class="table-bordered bg-light" style="width:100% !important;border-collapse: collapse;">
                            <tr>
                                <td style="text-align: left;border: 1px solid black">Entidad</td>
                                <td style='border: 1px solid black'>Descuento</td>
                                <td style='border: 1px solid black'>Valor base</td>
                                <td style='border: 1px solid black'>Valor rete</td>
                            </tr>
                            <?php
                            $total_rete = 0;
                            foreach ($retenciones as $re) {
                                echo "<tr>
                <td style='text-align: left;border: 1px solid black'>" . $re['nom_tercero'] . "</td>
                <td style='text-align: left;border: 1px solid black'>" . $re['nombre_retencion'] . "</td>
                <td style='text-align: right;border: 1px solid black'>" . number_format($re['valor_base'], 2, ',', '.') . "</td>
                <td style='text-align: right;border: 1px solid black'>" . number_format($re['valor_retencion'], 2, ',', '.') . "</td>
                </tr>";
                                $total_rete += $re['valor_retencion'];
                            }
                            ?>
                            <tr>
                                <td colspan="3" style="text-align:left;border: 1px solid black ">Total</td>
                                <td style="text-align: right;border: 1px solid black "><?php echo number_format($total_rete, 2, ",", "."); ?></td>
                            </tr>

                        </table>
                    <?php
                }
                    ?>
                <?php }
            if ($ver_costos && $cod_doc !== 'FELE') {
                ?>

                    </br>
                    <div class="row mb-2">
                        <div class="col-12">
                            <div style="text-align: left">
                                <div><strong>Distribución de costos: </strong></div>
                            </div>
                        </div>
                    </div>
                    <table class="table-bordered bg-light" style="width:100% !important; border-collapse: collapse;">
                        <tr>
                            <td style="text-align: left;border: 1px solid black">Municipio</td>
                            <td style='border: 1px solid black'>Centro Costo</td>
                            <td style='border: 1px solid black'>Valor</td>
                        </tr>
                        <?php
                        $tot_costos = 0;
                        foreach ($costos as $ct) {
                            echo "<tr style='border: 1px solid black'>
                            <td class='text-start' style='border: 1px solid black'>" . $ct['nom_municipio'] . "</td>
                            <td class='text-start' style='border: 1px solid black'>" . $ct['nom_area'] .  "</td>
                            <td class='text-end' style='border: 1px solid black;text-align: right'>" . number_format($ct['valor'], 2, ",", ".")  . "</td>
                        </tr>";
                            $tot_costos += $ct['valor'];
                        }
                        ?>
                        <tr>
                            <td style="text-align: left;border: 1px solid black" colspan="2">Total</td>
                            <td class='text-end' style='border: 1px solid black;text-align: right'><?php echo number_format($tot_costos, 2, ",", "."); ?> </td>
                        </tr>
                    </table>
                <?php } ?>
                </br>
                <div class="row mb-2">
                    <div class="col-12">
                        <div style="text-align: left">
                            <div><strong>Movimiento contable: </strong></div>
                        </div>
                    </div>
                </div>
                <table class="table-bordered bg-light" style="width:100% !important; border-collapse: collapse;">
                    <?php
                    if ($doc['tipo_doc'] == '5') {
                    ?>
                        <tr>
                            <td style="text-align: left;border: 1px solid black">Cuenta</td>
                            <td style='border: 1px solid black'>Nombre</td>
                            <td style='border: 1px solid black'>Terceros</td>
                            <td style='border: 1px solid black'>Nombre</td>
                            <td style='border: 1px solid black'>Débito</td>
                            <td style='border: 1px solid black'>Crédito</td>
                        </tr>
                        <?php
                        $tot_deb = 0;
                        $tot_cre = 0;
                        foreach ($movimiento as $mv) {
                            echo "<tr style='border: 1px solid black'>
                <td class='text-start' style='border: 1px solid black'>" . $mv['cuenta'] . "</td>
                <td class='text-start' style='border: 1px solid black'>" . $mv['nombre'] .  "</td>
                <td class='text-start' style='border: 1px solid black'>" . $mv['nit_tercero'] . "</td>
                <td class='text-start' style='border: 1px solid black'>" . $mv['nom_tercero'] . "</td>
                <td class='text-end' style='border: 1px solid black;text-align: right'>" . number_format($mv['debito'], 2, ",", ".")  . "</td>
                <td class='text-end' style='border: 1px solid black;text-align: right'>" . number_format($mv['credito'], 2, ",", ".")  . "</td>
                </tr>";
                            $tot_deb += $mv['debito'];
                            $tot_cre += $mv['credito'];
                        }
                        ?>
                        <tr>
                            <td style="text-align: left;border: 1px solid black" colspan="4">Sumas iguales</td>
                            <td class='text-end' style='border: 1px solid black;text-align: right'><?php echo number_format($tot_deb, 2, ",", "."); ?></td>
                            <td class='text-end' style='border: 1px solid black;text-align: right'><?php echo number_format($tot_cre, 2, ",", "."); ?> </td>
                        </tr>
                    <?php
                    } else {
                    ?>
                        <tr>
                            <td style="text-align: left;border: 1px solid black">Cuenta</td>
                            <td style='border: 1px solid black'>Nombre</td>
                            <td style='border: 1px solid black'>Débito</td>
                            <td style='border: 1px solid black'>Crédito</td>
                        </tr>
                        <?php

                        $tot_deb = 0;
                        $tot_cre = 0;
                        foreach ($movimiento as $mv) {
                            // Consulta terceros en la api ********************************************* API


                            echo "<tr style='border: 1px solid black'>
            <td class='text-start' style='border: 1px solid black'>" . $mv['cuenta'] . "</td>
            <td class='text-start' style='border: 1px solid black'>" . $mv['nombre'] .  "</td>
            <td class='text-end' style='border: 1px solid black;text-align: right'>" . number_format($mv['debito'], 2, ",", ".")  . "</td>
            <td class='text-end' style='border: 1px solid black;text-align: right'>" . number_format($mv['credito'], 2, ",", ".")  . "</td>
            </tr>";
                            $tot_deb += $mv['debito'];
                            $tot_cre += $mv['credito'];
                        }
                        ?>
                        <tr>
                            <td style="text-align: left;border: 1px solid black" colspan="2">Sumas iguales</td>
                            <td class='text-end' style='border: 1px solid black;text-align: right'><?php echo number_format($tot_deb, 2, ",", "."); ?></td>
                            <td class='text-end' style='border: 1px solid black;text-align: right'><?php echo number_format($tot_cre, 2, ",", "."); ?> </td>
                        </tr>
                    <?php
                    }
                    ?>
                </table>
                </br>
                <?= $firmas ?>
        </div>
        <div class="page-break"></div>
    <?php
    }
    ?>
</div>