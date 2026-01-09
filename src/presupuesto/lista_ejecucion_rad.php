<?php
session_start();
if (!isset($_SESSION['user'])) {
    header('Location: ../../index.php');
    exit();
}
include '../../config/autoloader.php';
$id_rol = $_SESSION['rol'];
$id_user = $_SESSION['id_user'];

use Config\Clases\Plantilla;
use Src\Common\Php\Clases\Permisos;

$permisos = new Permisos();
$opciones = $permisos->PermisoOpciones($id_user);
$host = Plantilla::getHost();
include '../financiero/consultas.php';

// Tabla que genera el reporte datos_detalle_rad.php
// Consulta tipo de presupuesto en la base de datos
$id_rad = isset($_POST['id_rad']) ? $_POST['id_rad'] : exit('Acceso no permitido');
$id_ppto = $_POST['id_ejec'];
$automatico = '';

try {
    $cmd = \Config\Clases\Conexion::getConexion();
    $sql = "SELECT
               `pto_rad`. `id_pto_rad`
               , `pto_rad`.`id_manu`
               , `pto_rad`.`fecha`
               , `pto_rad`.`objeto`
               , `pto_rad`.`num_factura`
               , CONCAT(`tb_terceros`.`nom_tercero`, ' -> ', `tb_terceros`.`nit_tercero`) AS `tercero`
               , `pto_rad`.`id_tercero_api`
            FROM
                `pto_rad`
            LEFT JOIN `tb_terceros` 
                ON (`pto_rad`.`id_tercero_api` = `tb_terceros`.`id_tercero_api`)
            WHERE `id_pto_rad` = $id_rad";
    $rs = $cmd->query($sql);
    $datosRad = $rs->fetch();
    $rs->closeCursor();
    unset($rs);
} catch (PDOException $e) {
    echo $e->getCode() == 2002 ? 'Sin Conexión a Mysql (Error: 2002)' : 'Error: ' . $e->getCode();
}

$automatico = 'readonly';
try {
    $cmd = \Config\Clases\Conexion::getConexion();
    $sql = "SELECT (SUM(`valor`) - SUM(`valor_liberado`)) as `valorCdp` FROM `pto_rad_detalle` WHERE `id_pto_rad` = $id_rad";
    $rs = $cmd->query($sql);
    $totalCdp = $rs->fetch();
    // total con puntos de mailes number_format()
    $valor = !empty($totalCdp['valorCdp']) ? $totalCdp['valorCdp'] : 0;
    $total = number_format($valor, 2, '.', ',');
    $rs->closeCursor();
    unset($rs);
} catch (PDOException $e) {
    echo $e->getCode() == 2002 ? 'Sin Conexión a Mysql (Error: 2002)' : 'Error: ' . $e->getCode();
}

// Buscar si el usuario tiene registrado fecha de sesion
try {
    $sql = "SELECT `fecha` FROM `tb_fin_fecha` WHERE `id_usuario` = {$_SESSION['id_user']} AND `vigencia` = '{$_SESSION['vigencia']}'";
    $res = $cmd->query($sql);
    $fechases = $res->fetch();
    $res->closeCursor();
    unset($res);
} catch (PDOException $e) {
    echo $e->getCode() == 2002 ? 'Sin Conexión a Mysql (Error: 2002)' : 'Error: ' . $e->getCode();
}

$fecha = date('Y-m-d', strtotime($datosRad['fecha']));

// Consulta funcion fechaCierre del modulo 4
$fecha_cierre = fechaCierre($_SESSION['vigencia'], 54, $cmd);
$fecha_max = date("Y-m-d", strtotime($_SESSION['vigencia'] . '-12-31'));
$cmd = null;

$content =
    <<<HTML
    <div class="card w-100">
        <div class="card-header bg-sofia text-white">
            <a class="btn btn-sm me-1 p-0" title="Regresar" href="lista_presupuestos.php"><i class="fas fa-arrow-left fa-lg"></i></a>
            <b>DETALLE RECONOCIMIENTO PRESUPUESTAL</b>
        </div>
        <div class="card-body p-2 bg-wiev">
            <div id="divFormDoc">
                <form id="formAddEjecutaPresupuesto">
                    <input type="hidden" id="id_pto_presupuestos" name="id_pto_presupuestos" value="{$id_ppto}">
                    <input type="hidden" id="id_rads" name="id_rads" value="{$id_rad}">
                    <input type="hidden" id="id_pto_docini" value="{$datosRad['id_manu']}">
                    <div class="right-block">
                        <div class="row pb-2">
                            <div class="col-md-2">
                                <label for="numCdp" class="form-label small">NUMERO CDP:</label>
                            </div>
                            <div class="col-md-10">
                                <input type="number" name="numCdp" id="numCdp" class="form-control form-control-sm bg-input" value="{$datosRad['id_manu']}" onchange="buscarCdp(value,'CDP')" {$automatico} readonly>
                            </div>
                        </div>
                        <div class="row pb-2">
                            <div class="col-md-2">
                                <label for="fecha" class="form-label small">FECHA:</label>
                            </div>
                            <div class="col-md-10">
                                <input type="date" name="fecha" id="fecha" class="form-control form-control-sm bg-input" min="{$fecha_cierre}" max="{$fecha_max}" value="{$fecha}" onchange="buscarConsecutivo('CDP');" readonly>
                            </div>
                        </div>
                        <div class="row pb-2">
                            <div class="col-md-2">
                                <label for="tercero" class="form-label small">TERCERO:</label>
                            </div>
                            <div class="col-md-10">
                                <input type="text" name="tercero" id="tercero" class="form-control form-control-sm bg-input" value="{$datosRad['tercero']}" readonly>
                                <input type="hidden" id="id_tercero" name="id_tercero" value="{$datosRad['id_tercero_api']}">
                            </div>
                        </div>
                        <div class="row pb-2">
                            <div class="col-md-2">
                                <label for="objeto" class="form-label small">OBJETO:</label>
                            </div>
                            <div class="col-md-10">
                                <textarea id="objeto" name="objeto" class="form-control form-control-sm py-0" rows="3" required readonly>{$datosRad['objeto']}</textarea>
                            </div>
                        </div>
                        <div class="row pb-2">
                            <div class="col-md-2">
                                <label for="solicitud" class="form-label small">No Factura:</label>
                            </div>
                            <div class="col-md-10">
                                <input type="text" name="solicitud" id="solicitud" class="form-control form-control-sm bg-input" value="{$datosRad['num_factura']}" readonly>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
            <form id="formAddModDetalleRad">
                <input type="hidden" id="id_rad" name="id_rad" value="{$id_rad}">
                <input type="hidden" id="id_pto_rad" name="id_pto_rad" value="{$id_ppto}">
                <input type="hidden" id="id_pto_movto" name="id_pto_movto" value="{$id_ppto}">
                <div class="table-responsive shadow p-2">
                    <table id="tableEjecRad" class="table table-striped table-bordered table-sm table-hover shadow w-100">
                        <thead>
                            <tr class="text-center">
                                <th class="bg-sofia" style="width: 8%;">ID</th>
                                <th class="bg-sofia" style="width: 60%;">Codigo</th>
                                <th class="bg-sofia" style="width: 20%;">Valor</th>
                                <th class="bg-sofia" style="width: 12%;">Acciones</th>
                            </tr>
                        </thead>
                        <tbody id="modificarEjeRad">
                        </tbody>
                    </table>
                </div>
            </form>
            <div class="text-center pt-4">
HTML;

if ($permisos->PermisosUsuario($opciones, 5401, 6) || $id_rol == 1) {
    $content .= <<<HTML
                <a type="button" class="btn btn-primary btn-sm" onclick="imprimirFormatoRad({$id_rad});" style="width: 5rem;">
                    <span class="fas fa-print"></span>
                </a>
HTML;
}

$content .= <<<HTML
                <a type="button" id="volverListaRads" class="btn btn-danger btn-sm" style="width: 5rem;" href="#">VOLVER</a>
            </div>
        </div>
    </div>
    HTML;

$plantilla = new Plantilla($content, 2);
$plantilla->addCssFile("{$host}/assets/css/jquery-ui.css?v=" . date("YmdHis"));
$plantilla->addScriptFile("{$host}/assets/js/jquery-ui.js?v=" . date("YmdHis"));
$plantilla->addScriptFile("{$host}/src/presupuesto/js/libros_aux_pto/common.js?v=" . date("YmdHis"));
$plantilla->addScriptFile("{$host}/src/presupuesto/js/funcionpresupuesto.js?v=" . date("YmdHis"));
$modal = $plantilla->getModal('divModalForms', 'divTamModalForms', 'divForms');
$plantilla->addModal($modal);
$modal = $plantilla->getModal('divModalReg', 'divTamModalReg', 'divFormsReg');
$plantilla->addModal($modal);
$modal = $plantilla->getModal('divModalImp', 'divTamModalImp', 'divImp');
$plantilla->addModal($modal);
echo $plantilla->render();
