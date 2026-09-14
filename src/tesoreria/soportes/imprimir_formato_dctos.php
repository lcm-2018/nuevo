<?php
session_start();
set_time_limit(3600);
if (!isset($_SESSION['user'])) {
    header("Location: ../../../index.php");
    exit();
}
$vigencia = $_SESSION['vigencia'];
$id_vigencia = $_SESSION['id_vigencia'];
$dto = $_POST['id_ctb_doc'];
$tipo_doc = 4;
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
    $sel = "SELECT * FROM `ctb_doc` WHERE `id_ctb_doc` = $dto";
    $r = $cmd->query($sel);
    $d = $r->fetch();
    $sql = "SELECT 
                `ctb_doc`.`id_ctb_doc`
                ,`ctb_doc`.`detalle`
                , `ctb_doc`.`fecha`
                , `ctb_doc`.`id_manu`
                , `ctb_doc`.`id_tercero`
                , `ctb_doc`.`fecha_reg`
                , `ctb_doc`.`id_tipo_doc` AS `tipo_doc`
                , `ctb_doc`.`estado`
                , CONCAT_WS (' ',`us1`.`nombre1`, `us1`.`nombre2`, `us1`.`apellido1`, `us1`.`apellido2`) AS `usuario`
                , CONCAT_WS (' ',`us2`.`nombre1`, `us2`.`nombre2`, `us2`.`apellido1`, `us2`.`apellido2`) AS `usuario_act`
                , `us1`.`descripcion` as `cargo`
                , `tb_terceros`.`nom_tercero`
                , `tb_terceros`.`nit_tercero`
            FROM `ctb_doc`
                INNER JOIN `seg_usuarios_sistema`  AS `us1` 
                    ON (`ctb_doc`.`id_user_reg` = `us1`.`id_usuario`)
                LEFT JOIN `seg_usuarios_sistema`  AS `us2`
                    ON (`ctb_doc`.`id_user_act` = `us2`.`id_usuario`)
                LEFT JOIN `tb_terceros` 
                    ON (`ctb_doc`.`id_tercero` = `tb_terceros`.`id_tercero_api`)
            WHERE `ctb_doc`.`id_ctb_doc` IN (SELECT
                                        `pto_cop_detalle`.`id_ctb_doc`
                                    FROM
                                        `pto_pag_detalle`
                                        INNER JOIN `pto_cop_detalle` 
                                        ON (`pto_pag_detalle`.`id_pto_cop_det` = `pto_cop_detalle`.`id_pto_cop_det`)
                                        LEFT JOIN `ctb_causa_retencion` 
                                        ON (`pto_cop_detalle`.`id_ctb_doc` = `ctb_causa_retencion`.`id_ctb_doc`)
                                    WHERE (`pto_pag_detalle`.`id_ctb_doc` = $dto))";
    $res = $cmd->query($sql);
    $documentos = $res->fetchAll();
    $res->closeCursor();
    unset($res);
} catch (PDOException $e) {
    echo $e->getCode() == 2002 ? 'Sin Conexión a Mysql (Error: 2002)' : 'Error: ' . $e->getCode();
}
?>
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
    $id_modulo = 56;
    $doc_fte = 'CEVA';
    foreach ($documentos as $doc) {
        $tipo_doc = $doc['tipo_doc'];
        try {
            $sql = "SELECT `cod`, `nombre` FROM `ctb_fuente` WHERE `id_doc_fuente` = $tipo_doc";
            $res = $cmd->query($sql);
            $dss = $res->fetch();
        } catch (PDOException $e) {
            echo $e->getCode() == 2002 ? 'Sin Conexión a Mysql (Error: 2002)' : 'Error: ' . $e->getCode();
        }
        $nombre_doc = $dss['nombre'] ?? '';

        $cdp = $doc;
        $dto = $doc['id_ctb_doc'];

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

        $enletras = numeroLetras($total);

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
        $fecha = date('Y-m-d', strtotime($d['fecha']));
        $reportes = new \Src\Common\Php\Clases\Reportes($cmd);
        $user = new \Src\Usuarios\Login\Php\Clases\Usuario();
        try {
            $empresa = $user->getEmpresa();
        } catch (PDOException $e) {
            echo $e->getCode() == 2002 ? 'Sin Conexión a Mysql (Error: 2002)' : 'Error: ' . $e->getCode();
        }
        $html = $reportes->getEncabezado($empresa);
        $config = $reportes->getConfigDoc($id_modulo, $fecha, $doc_fte);

        extract($config);
        $elabora = [
            'nom_tercero' => isset($cdp['usuario_act']) && trim($cdp['usuario_act']) != ''
                ? $cdp['usuario_act']
                : (isset($cdp['usuario']) ? $cdp['usuario'] : ''),
            'cargo' => isset($cdp['cargo']) ? $cdp['cargo'] : ''
        ];
        $tercero_nombre = isset($doc['nom_tercero']) ? $doc['nom_tercero'] : '';
        $firmas = $reportes->getFormFirmas($elabora, $id_modulo, $fecha, $doc_fte, $tercero_nombre);

        $hora = date('H:i:s', strtotime($doc['fecha_reg']));


        ?>
        <div class="px-2 " style="width:90% !important;margin: 0 auto;">

            </br>
            <table class="table-bordered bg-light" style="width:100% !important;">
                <tr>
                    <td class='text-center' style="width:18%"><label class="small"><img
                                src="../../../assets/images/logo.png" width="100"></label></td>
                    <td style="text-align:center">
                        <strong><?php echo $empresa['nombre']; ?> </strong>
                        <div>NIT <?php echo $empresa['nit'] . '-' . $empresa['dig_ver']; ?></div>
                    </td>
                </tr>
            </table>

            </br>


            <div class="row mb-2 px-2" style="text-align: center">
                <div class="col-12">
                    <div class="col lead">
                        <label><strong><?php echo $nombre_doc . ': ' . $doc['id_manu']; ?></strong></label>
                    </div>
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
                    <td class='text-start'>
                        <label><?php echo $enletras . "  $" . number_format($total, 2, ",", "."); ?></label>
                    </td>
                </tr>
            </table>
            <div class="watermark">
                <h3><?php echo $anulado ?></h3>
            </div>

            <?php if ($doc['tipo_doc'] == '3' || $doc['tipo_doc'] == '5') {
                if ($doc['tipo_doc'] != '5') {
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
                            <td style="text-align: right;border: 1px solid black ">
                                <?php echo number_format($total_rete, 2, ",", "."); ?>
                            </td>
                        </tr>

                    </table>
                    <?php
                }
                ?>
            <?php } ?>
            <?= $firmas ?>
        </div>
        <div class="page-break"></div>
        <?php
    }
    ?>
</div>
<script>
    window.onload = function () {
        window.print();
    };
</script>