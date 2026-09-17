<?php
session_start();
date_default_timezone_set('America/Bogota');
if (!isset($_SESSION['user'])) {
    header("Location: ../../../index.php");
    exit();
}
$vigencia = $_SESSION['vigencia'];
$id = $_POST['id'];
$mes = $_POST['mes'];
$vigencia = $_SESSION['vigencia'];

function pesos($valor)
{
    return '$' . number_format($valor, 2);
}
include '../../../config/autoloader.php';


use Src\Common\Php\Clases\Permisos;

$id_rol = $_SESSION['rol'];
$id_user = $_SESSION['id_user'];

$permisos = new Permisos();
$opciones = $permisos->PermisoOpciones($id_user);
include '../../financiero/consultas.php';

$cmd = \Config\Clases\Conexion::getConexion();

$cmd = \Config\Clases\Conexion::getConexion();
try {
    $sql = "SELECT `fin_mes`, `nom_mes` FROM `nom_meses` WHERE (`codigo` = '$mes')";
    $rs = $cmd->query($sql);
    $dia = $rs->fetch(PDO::FETCH_ASSOC);
    $fin_mes = !(empty($dia)) ? date('Y-m-t', strtotime($vigencia . '-' . $mes . '-01')) : 0;
    $nom_mes = !(empty($dia)) ? mb_strtoupper($dia['nom_mes']) : '';
} catch (PDOException $e) {
    echo $e->getCode() == 2002 ? 'Sin Conexión a Mysql (Error: 2002)' : 'Error: ' . $e->getCode();
}
try {
    $sql = "SELECT
                `ctb_doc`.`fecha`
                , `ctb_fuente`.`cod`
                , `ctb_doc`.`id_manu`
                , `ctb_libaux`.`id_tercero_api`
                , `ctb_libaux`.`debito`
                , `ctb_libaux`.`credito`
                , `ctb_libaux`.`id_ctb_libaux`
                , `tb_terceros`.`nom_tercero`
                , `tb_terceros`.`nit_tercero`
            FROM
                `ctb_libaux`
                INNER JOIN `tes_cuentas` 
                    ON (`tes_cuentas`.`id_cuenta` = `ctb_libaux`.`id_cuenta`)
                INNER JOIN `ctb_doc` 
                    ON (`ctb_libaux`.`id_ctb_doc` = `ctb_doc`.`id_ctb_doc`)
                INNER JOIN `ctb_fuente` 
                    ON (`ctb_doc`.`id_tipo_doc` = `ctb_fuente`.`id_doc_fuente`)
                LEFT JOIN `tb_terceros` 
                    ON (`ctb_libaux`.`id_tercero_api` = `tb_terceros`.`id_tercero_api`)
                LEFT JOIN `tes_conciliacion_detalle`
                    ON (`tes_conciliacion_detalle`.`id_ctb_libaux` = `ctb_libaux`.`id_ctb_libaux` AND `tes_conciliacion_detalle`.`fecha_marca` <= '$fin_mes')
            WHERE `tes_cuentas`.`id_tes_cuenta` = $id AND `ctb_doc`.`estado` = 2 AND `ctb_doc`.`fecha` <= '$fin_mes'
                    AND `tes_conciliacion_detalle`.`id_ctb_libaux` IS NULL";
    $rs = $cmd->query($sql);
    $lista = $rs->fetchAll();
    $rs->closeCursor();
    unset($rs);
    $tot_deb = 0;
    $tot_cre = 0;
    foreach ($lista as $lp) {
        $tot_deb += $lp['debito'];
        $tot_cre += $lp['credito'];
    }
} catch (PDOException $e) {
    echo $e->getCode() == 2002 ? 'Sin Conexión a Mysql (Error: 2002)' : 'Error: ' . $e->getCode();
}
try {
    $sql = "SELECT
                tc.`id_conciliacion`
                , tc.`saldo_extracto`
                , tc.`estado`
                , IFNULL((SELECT SUM(l.debito) 
                   FROM tes_conciliacion_detalle tcd 
                   INNER JOIN ctb_libaux l ON tcd.id_ctb_libaux = l.id_ctb_libaux 
                   WHERE tcd.id_concilia = tc.id_conciliacion), 0) AS debito
                , IFNULL((SELECT SUM(l.credito) 
                   FROM tes_conciliacion_detalle tcd 
                   INNER JOIN ctb_libaux l ON tcd.id_ctb_libaux = l.id_ctb_libaux 
                   WHERE tcd.id_concilia = tc.id_conciliacion), 0) AS credito
            FROM
                `tes_conciliacion` tc
            WHERE tc.`id_cuenta` = $id AND tc.`vigencia` = '$vigencia' AND tc.`mes` = '$mes'";
    $rs = $cmd->query($sql);
    $data = $rs->fetch(PDO::FETCH_ASSOC);
    if (!empty($data)) {
        $id_conciliacion = $data['id_conciliacion'];
        $saldo = $data['saldo_extracto'];
        $estado = $data['estado'];
        $debito = $data['debito'];
        $credito = $data['credito'];
    } else {
        $id_conciliacion = 0;
        $saldo = 0;
        $estado = 0;
        $debito = 0;
        $credito = 0;
    }
} catch (PDOException $e) {
    echo $e->getCode() == 2002 ? 'Sin Conexión a Mysql (Error: 2002)' : 'Error: ' . $e->getCode();
}
try {
    $sql = "SELECT
                    `tes_cuentas`.`id_tes_cuenta`
                    , `tb_bancos`.`nom_banco`
                    , `tes_tipo_cuenta`.`tipo_cuenta`
                    , `tes_cuentas`.`numero`
                    , `tes_cuentas`.`nombre` AS `descripcion`
                    , `ctb_pgcp`.`cuenta` AS `cta_contable`
                    , IFNULL(`t1`.`debito`, 0) AS `debito`
                    , IFNULL(`t1`.`credito`, 0) AS `credito`
                FROM
                    `tes_cuentas`
                    INNER JOIN `ctb_pgcp` 
                        ON (`tes_cuentas`.`id_cuenta` = `ctb_pgcp`.`id_pgcp`)
                    INNER JOIN `tb_bancos` 
                        ON (`tes_cuentas`.`id_banco` = `tb_bancos`.`id_banco`)
                    INNER JOIN `tes_tipo_cuenta` 
                        ON (`tes_cuentas`.`id_tipo_cuenta` = `tes_tipo_cuenta`.`id_tipo_cuenta`)
                    LEFT JOIN 
                        (SELECT
                            l.`id_cuenta`
                            , SUM(l.`debito`) AS `debito` 
                            , SUM(l.`credito`) AS `credito`
                        FROM
                            `ctb_libaux` l
                            INNER JOIN `ctb_doc` d ON (l.`id_ctb_doc` = d.`id_ctb_doc`)
                            INNER JOIN `tes_cuentas` tc ON (tc.`id_cuenta` = l.`id_cuenta`)
                        WHERE tc.`id_tes_cuenta` = $id AND d.`estado` = 2 AND d.`fecha` <= '$fin_mes' 
                        GROUP BY l.`id_cuenta`) AS `t1`  
                        ON (`t1`.`id_cuenta` = `ctb_pgcp`.`id_pgcp`)
                WHERE `tes_cuentas`.`id_tes_cuenta` = $id";
    $rs = $cmd->query($sql);
    $detalles = $rs->fetch();
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
    $ips = $res->fetch();
} catch (PDOException $e) {
    echo $e->getCode() == 2002 ? 'Sin Conexión a Mysql (Error: 2002)' : 'Error: ' . $e->getCode();
}
// si tipo de documento es CICP es un recibo de caja

$fecha = date('Y-m-d', strtotime($fin_mes));
$id_modulo = 56;
$doc_fte = 'CONC';
// fechas para factua
// Consulto responsable del documento
$user = new \Src\Usuarios\Login\Php\Clases\Usuario();
$reportes = new \Src\Common\Php\Clases\Reportes($cmd);
try {
    $empresa = $user->getEmpresa();
} catch (PDOException $e) {
    echo $e->getCode() == 2002 ? 'Sin Conexión a Mysql (Error: 2002)' : 'Error: ' . $e->getCode();
}
$html = $reportes->getEncabezado($empresa);
$config = $reportes->getConfigDoc($id_modulo, $fecha, $doc_fte);
extract($config);
$elabora = [];
$tercero_nombre = '';
$firmas = $reportes->getFormFirmas($elabora, $id_modulo, $fecha, $doc_fte, $tercero_nombre);
$anulado = '';

?>
<div class="text-end py-3">
    <?php if ($permisos->PermisosUsuario($opciones, 5601, 6) || $id_rol == 1) { ?>
        <a type="button" class="btn btn-primary btn-sm" onclick="imprSelecTes('areaImprimir','0');"> Imprimir</a>
    <?php } ?>
    <a type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal"> Cerrar</a>
</div>
<div class="contenedor bg-light" id="areaImprimir">
    <style>
        /* CSS para replicar la clase .row */
        .row-custom {
            display: flex;
            flex-wrap: wrap;
            margin-right: -15px;
            margin-left: -15px;
        }

        .row-custom>div {
            padding-right: 15px;
            padding-left: 15px;
        }

        /* Opcional: columnas */
        .col-6-custom {
            flex: 0 0 50%;
            /* Toma el 50% del ancho */
            max-width: 50%;
            /* Limita el ancho máximo al 50% */
        }

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
        }
    </style>
    <div class="px-2 " style="width:90% !important;margin: 0 auto;">

        </br>
        <table class="table-bordered bg-light" style="width:100% !important;">
            <tr>
                <td class='text-center' style="width:18%"><label class="small"><img src="../../assets/images/logo.png"
                            width="100"></label></td>
                <td style="text-align:center">
                    <strong><?php echo $ips['nombre']; ?> </strong>
                    <div>NIT <?php echo $ips['nit'] . '-' . $ips['dig_ver']; ?></div>
                </td>
            </tr>
        </table>

        </br>


        <div class="row px-2" style="text-align: center">
            <div class="col-12">
                <div class="col lead"><label><strong>CONCILIACIÓN BANCARIA</strong></label></div>
            </div>
        </div>
        <div class="watermark">
            <h3><?php echo $anulado ?></h3>
        </div>
        <table class="table-bordered bg-light" style="width:100% !important;">
            <tr>
                <td class='text-start' style="width:25%">CÓDIGO CUENTA:</td>
                <td class='text-start'><?= $detalles['cta_contable'] ?></td>
            </tr>
            <tr>
                <td class='text-start'>NOMBRE CUENTA:</td>
                <td class='text-start'><?= $detalles['descripcion']; ?></td>
            </tr>
            <tr>
                <td class='text-start'>MES CONCILIADO:</td>
                <td class='text-start'><?= $nom_mes; ?></td>
            </tr>
            <tr>
                <td class='text-start'>AÑO:</td>
                <td class='text-start'><?php echo $vigencia; ?></td>
            </tr>
        </table>
        <br>
        <table style="width:100% !important; border-collapse: collapse;" border="1">
            <tr>
                <td style="text-align: left; width: 50%;">SALDO EN LIBROS (contable)</td>
                <td style="text-align: right; width: 25%"><?= pesos($detalles['debito'] - $detalles['credito']); ?></td>
                <td style="text-align: left; width: 25%"></td>
            </tr>
            <tr>
                <td style="text-align: left;">Total Débitos Pendientes (++)</td>
                <td style="text-align: left;"></td>
                <td style="text-align: right;"><?= pesos($tot_deb); ?></td>
            </tr>
            <tr>
                <td style="text-align: left;">Total Créditos Pendientes (-)</td>
                <td style="text-align: left;"></td>
                <td style="text-align: right;"><?= pesos($tot_cre); ?></td>
            </tr>
            <tr>
                <td style="text-align: left;">SALDO EN LIBROS EXTRACTO</td>
                <td style="text-align: left;"></td>
                <td style="text-align: right;"><?= pesos($saldo); ?></td>
            </tr>
            <tr>
                <td style="text-align: left;">SUMAS IGUALES</td>
                <td style="text-align: right;"><?= pesos($detalles['debito'] - $detalles['credito']); ?></td>
                <td style="text-align: right;"><?= pesos($saldo + $tot_deb - $tot_cre); ?></td>
            </tr>
        </table>
        </br>
        <table style="width:100% !important; border-collapse: collapse; font-size: 12px;" border="1">
            <tr>
                <th>Fecha</th>
                <th>Comprobante</th>
                <th>Tercero</th>
                <th>Documento</th>
                <th>Débito</th>
                <th>Crédito</th>
            </tr>
            <?php
            $tdebito = 0;
            $tcredito = 0;
            foreach ($lista as $l) {
                $tdebito += $l['debito'];
                $tcredito += $l['credito'];

                ?>
                <tr style="text-align: left;">
                    <td><?= date('Y-m-d', strtotime($l['fecha'])); ?></td>
                    <td><?= $l['cod'] . $l['id_manu']; ?></td>
                    <td><?= $l['nom_tercero'] . ' - ' . $l['nit_tercero']; ?></td>
                    <td><?= '' ?></td>
                    <td style="text-align: right;"><?= pesos($l['debito']); ?></td>
                    <td style="text-align: right;"><?= pesos($l['credito']); ?></td>
                </tr>
                <?php
            }
            ?>
            <tr>
                <th colspan="4">TOTAL</th>
                <th style="text-align: right;"><?= pesos($tdebito); ?></th>
                <th style="text-align: right;"><?= pesos($tcredito); ?></th>
            </tr>
        </table>
        <br><br>
        <?= $firmas ?>
        </br> </br>
    </div>

</div>