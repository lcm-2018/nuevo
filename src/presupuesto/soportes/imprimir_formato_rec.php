<?php
session_start();
if (!isset($_SESSION['user'])) {
    header("Location: ../../../index.php");
    exit();
}
header("Content-type: text/html; charset=utf-8");

include '../../../config/autoloader.php';
include '../../financiero/consultas.php';

use Src\Common\Php\Clases\Permisos;

$id_rol = $_SESSION['rol'];
$id_user = $_SESSION['id_user'];
$permisos = new Permisos();
$opciones = $permisos->PermisoOpciones($id_user);

if (!($permisos->PermisosUsuario($opciones, 5401, 6) || $id_rol == 1)) {
    echo '<div class="alert alert-danger m-3 p-3">No tiene permisos para imprimir este documento.</div>';
    exit();
}

$vigencia = $_SESSION['vigencia'];
$id_vigencia = $_SESSION['id_vigencia'];
$dto = intval($_POST['id'] ?? $_GET['id'] ?? 0);
if ($dto <= 0) {
    echo '<div class="alert alert-warning m-3 p-3">No se especificó un identificador de recaudo válido.</div>';
    exit();
}

function pesos($valor)
{
    return '$' . number_format($valor, 2);
}

$cmd = \Config\Clases\Conexion::getConexion();
$rec = null;
$rubros = [];
$total = 0.0;

try {
    $sql = "SELECT
                `pto_rec`.`objeto`
                , `pto_rec`.`id_manu`
                , `pto_rec`.`fecha`
                , `pto_rec`.`num_factura`
                , `pto_rec`.`fecha_reg` AS `fec_reg`
                , `pto_rec`.`estado`
                , CONCAT_WS(' ', `seg_usuarios_sistema`.`nombre1`
                , `seg_usuarios_sistema`.`nombre2`
                , `seg_usuarios_sistema`.`apellido1`
                , `seg_usuarios_sistema`.`apellido2`) AS `usuario`
                , CONCAT_WS(' ', `seg_usuarios_sistema_1`.`nombre1`
                , `seg_usuarios_sistema_1`.`nombre2`
                , `seg_usuarios_sistema_1`.`apellido1`
                , `seg_usuarios_sistema_1`.`apellido2`) AS `usuario_act`
                , `seg_usuarios_sistema`.`descripcion` AS `cargo`
                , `tb_terceros`.`nom_tercero` AS `tercero`
                , `tb_terceros`.`nit_tercero` AS `nit_tercero`
            FROM
                `pto_rec`
                LEFT JOIN `seg_usuarios_sistema`
                    ON (`pto_rec`.`id_user_reg` = `seg_usuarios_sistema`.`id_usuario`)
                LEFT JOIN `seg_usuarios_sistema` AS `seg_usuarios_sistema_1`
                    ON (`pto_rec`.`id_user_act` = `seg_usuarios_sistema_1`.`id_usuario`)
                LEFT JOIN `tb_terceros`
                    ON (`pto_rec`.`id_tercero_api` = `tb_terceros`.`id_tercero_api`)
            WHERE (`pto_rec`.`id_pto_rec` = :id_rec)";
    $stmt = $cmd->prepare($sql);
    $stmt->bindValue(':id_rec', $dto, PDO::PARAM_INT);
    $stmt->execute();
    $rec = $stmt->fetch(PDO::FETCH_ASSOC);
    $stmt->closeCursor();
    unset($stmt);
} catch (PDOException $e) {
    echo $e->getCode() == 2002 ? 'Sin Conexión a Mysql (Error: 2002)' : 'Error: ' . $e->getCode();
}

if (empty($rec)) {
    echo '<div class="alert alert-warning m-3 p-3">No se encontró el recaudo presupuestal especificado.</div>';
    exit();
}

try {
    $sql = "SELECT
                `id_pto_rac`, SUM(IFNULL(`valor`, 0)) AS `debito`, SUM(IFNULL(`valor_liberado`, 0)) AS `credito`
            FROM
                `pto_rec_detalle`
            WHERE (`id_pto_rac` = :id_rec)";
    $stmt = $cmd->prepare($sql);
    $stmt->bindValue(':id_rec', $dto, PDO::PARAM_INT);
    $stmt->execute();
    $datos = $stmt->fetch(PDO::FETCH_ASSOC);
    $total = (float)($datos['debito'] ?? 0) - (float)($datos['credito'] ?? 0);
    $stmt->closeCursor();
    unset($stmt);
} catch (PDOException $e) {
    echo $e->getCode() == 2002 ? 'Sin Conexión a Mysql (Error: 2002)' : 'Error: ' . $e->getCode();
}

$anulado = $rec['estado'] == '0' ? 'ANULADO' : '';

try {
    $sql = "SELECT
                `pto_rec_detalle`.`id_pto_rac`
                , IFNULL(`pto_rec_detalle`.`valor`, 0) AS `valor`
                , IFNULL(`pto_rec_detalle`.`valor_liberado`, 0) AS `valor_liberado`
                , `pto_cargue`.`cod_pptal`
                , `pto_cargue`.`nom_rubro`
                , `pto_cargue`.`tipo_dato`
                , `pto_tipo`.`nombre`
            FROM
                `pto_rec_detalle`
                LEFT JOIN `pto_rad_detalle`
                    ON (`pto_rec_detalle`.`id_pto_rad_detalle` = `pto_rad_detalle`.`id_pto_rad_det`)
                INNER JOIN `pto_cargue`
                    ON (`pto_cargue`.`id_cargue` = COALESCE(`pto_rec_detalle`.`id_rubro`, `pto_rad_detalle`.`id_rubro`))
                INNER JOIN `pto_presupuestos`
                    ON (`pto_cargue`.`id_pto` = `pto_presupuestos`.`id_pto`)
                INNER JOIN `pto_tipo`
                    ON (`pto_presupuestos`.`id_tipo` = `pto_tipo`.`id_tipo`)
            WHERE (`pto_rec_detalle`.`id_pto_rac` = :id_rec)";
    $stmt = $cmd->prepare($sql);
    $stmt->bindValue(':id_rec', $dto, PDO::PARAM_INT);
    $stmt->execute();
    $rubros = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $stmt->closeCursor();
    unset($stmt);
} catch (PDOException $e) {
    echo $e->getCode() == 2002 ? 'Sin Conexión a Mysql (Error: 2002)' : 'Error: ' . $e->getCode();
}

$fecha = date('Y-m-d', strtotime($rec['fecha']));
$id_modulo = 54;
$doc_fte = 'REC';
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
$elabora = [
    'nom_tercero' => isset($rec['usuario_act']) && trim($rec['usuario_act']) != ''
        ? $rec['usuario_act']
        : (isset($rec['usuario']) ? $rec['usuario'] : ''),
    'cargo' => isset($rec['cargo']) ? $rec['cargo'] : ''
];
$tercero_nombre = isset($rec['tercero']) ? $rec['tercero'] : '';
$firmas = $reportes->getFormFirmas($elabora, $id_modulo, $fecha, $doc_fte, $tercero_nombre);

$enletras = numeroLetras($total);
$nit_raw = $rec['nit_tercero'] ?? '';
$nit_formateado = is_numeric($nit_raw) ? number_format((float)$nit_raw, 0, ",", ".") : htmlspecialchars((string)$nit_raw, ENT_QUOTES, 'UTF-8');
?>
<div class="text-end py-3">
    <a type="button" class="btn btn-primary btn-sm" onclick="imprSelecRec('areaImprimir');"> Imprimir</a>
    <a type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal"> Cerrar</a>
</div>
<div class="contenedor bg-light" id="areaImprimir">
    <div class="p-2 " style="width:90% !important;margin: 0 auto;">
        <table style="width: 100%;">
            <thead class="bg-light text-dark">
                <tr>
                    <td>
                        <?php echo $html; ?>
                    </td>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>
                        <div>
                            <p style="text-align: center;"><b>RECAUDO PRESUPUESTAL No: <?php echo htmlspecialchars((string)$rec['id_manu'], ENT_QUOTES, 'UTF-8'); ?></b></p>
                        </div>
                        <div style="text-align: justify;">
                            <div>
                                <div class="watermark">
                                    <h3><?php echo $anulado; ?></h3>
                                </div>
                                <table style="width:100% !important; text-align: justify;" class="bordeado">
                                    <tr>
                                        <td style="width:22%">FECHA:</td>
                                        <td><?php echo $fecha; ?></td>
                                    </tr>
                                    <tr>
                                        <td>TERCERO:</td>
                                        <td><?= htmlspecialchars((string)($rec['tercero'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                                    </tr>
                                    <tr>
                                        <td>CC/NIT:</td>
                                        <td><?= $nit_formateado ?></td>
                                    </tr>
                                    <tr>
                                        <td>OBJETO:</td>
                                        <td><?php echo htmlspecialchars((string)($rec['objeto'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></td>
                                    </tr>
                                    <tr>
                                        <td>VALOR:</td>
                                        <td><label><?php echo $enletras . "  $" . number_format($total, 2, ",", "."); ?></label></td>
                                    </tr>
                                    <tr>
                                        <td>No. FACTURA:</td>
                                        <td><label><?php echo htmlspecialchars((string)($rec['num_factura'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></label></td>
                                    </tr>
                                </table>
                            </div>
                            <div style="padding-top: 14px;">
                                <table style="width:100% !important; text-align:center;" class="bordeado">
                                    <tr>
                                        <td>Código</td>
                                        <td>Nombre</td>
                                        <td>Valor</td>
                                    </tr>
                                    <?php
                                    $totalRubros = 0.0;
                                    foreach ($rubros as $rb) {
                                        $key = htmlspecialchars((string)$rb['cod_pptal'], ENT_QUOTES, 'UTF-8');
                                        $rubro = htmlspecialchars((string)$rb['nom_rubro'], ENT_QUOTES, 'UTF-8');
                                        $val = (float)$rb['valor'] - (float)$rb['valor_liberado'];
                                        $totalRubros += $val;
                                        echo "<tr>
                                            <td style='text-align:left'>" . $key . "</td>
                                            <td style='text-align:left'>" . $rubro . "</td>
                                            <td style='text-align:right'>" . number_format($val, 2, ",", ".") . "</td>
                                        </tr>";
                                    }
                                    ?>
                                    <tr>
                                        <td colspan="2" style="text-align:right"><strong>TOTAL</strong></td>
                                        <td style="text-align:right"><strong><?php echo number_format($totalRubros, 2, ",", "."); ?></strong></td>
                                    </tr>
                                </table>
                            </div>
                            <?= $firmas ?>
                        </div>
                    </td>
                </tr>
            </tbody>
            <tfoot></tfoot>
        </table>
    </div>
</div>
