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
$peReg =  $permisos->PermisosUsuario($opciones, 5401, 0) || $id_rol == 1 ? 1 : 0;
$host = Plantilla::getHost();
include '../financiero/consultas.php';

$automatico = '';
$id_ppto = $_POST['id_ejec'];
$valoradq = '';

$id_adq = isset($_POST['id_adq']) ? $_POST['id_adq'] : 0;
$id_otro = isset($_POST['id_otro']) ? $_POST['id_otro'] : 0;
$id_cdp = isset($_POST['id_cdp']) ? $_POST['id_cdp'] : 0;

try {
    $cmd = \Config\Clases\Conexion::getConexion();
    $sql = "SELECT
                `id_pto_cdp`,`fecha`, `id_manu`,`objeto`,`num_solicitud` 
            FROM `pto_cdp` 
            WHERE `id_pto_cdp` = $id_cdp";
    $rs = $cmd->query($sql);
    $datosCdp = $rs->fetch();
} catch (PDOException $e) {
    echo $e->getCode() == 2002 ? 'Sin Conexión a Mysql (Error: 2002)' : 'Error: ' . $e->getCode();
}
if ($id_cdp == 0) {
    $valida = true;
} else {
    $valida = false;
}
if (empty($datosCdp)) {
    try {
        $cmd = \Config\Clases\Conexion::getConexion();

        $sql = "SELECT
                    MAX(`id_manu`) AS `id_manu` 
                FROM
                    `pto_cdp`
                WHERE (`id_pto` = $id_ppto)";
        $rs = $cmd->query($sql);
        $consecutivo = $rs->fetch();
        $id_manu = !empty($consecutivo) ? $consecutivo['id_manu'] + 1 : 1;
        $cmd = null;
    } catch (PDOException $e) {
        echo $e->getCode() == 2002 ? 'Sin Conexión a Mysql (Error: 2002)' : 'Error: ' . $e->getCode();
    }
    $datosCdp['id_pto_cdp'] = 0;
    $datosCdp['fecha'] = '';
    $datosCdp['id_manu'] = $id_manu;
    $datosCdp['objeto'] = '';
    $datosCdp['num_solicitud'] = '';
}
$automatico = 'readonly';
try {
    $cmd = \Config\Clases\Conexion::getConexion();
    $sql = "SELECT (SUM(`valor`) - SUM(`valor_liberado`)) as `valorCdp` FROM `pto_cdp_detalle` WHERE `id_pto_cdp` = $id_cdp";
    $rs = $cmd->query($sql);
    $totalCdp = $rs->fetch();
    // total con puntos de mailes number_format()
    $valor = !empty($totalCdp['valorCdp']) ? $totalCdp['valorCdp'] : 0;
    $total = number_format($valor, 2, '.', ',');
} catch (PDOException $e) {
    echo $e->getCode() == 2002 ? 'Sin Conexión a Mysql (Error: 2002)' : 'Error: ' . $e->getCode();
}
// Buscar si el usuario tiene registrado fecha de sesion
try {
    $sql = "SELECT `fecha` FROM `tb_fin_fecha` WHERE `id_usuario` = '$_SESSION[id_user]' AND vigencia = '$_SESSION[vigencia]'";
    $res = $cmd->query($sql);
    $fechases = $res->fetch();
} catch (PDOException $e) {
    echo $e->getCode() == 2002 ? 'Sin Conexión a Mysql (Error: 2002)' : 'Error: ' . $e->getCode();
}

if ($id_cdp == 0) {
    $fecha = date('Y-m-d');
} else {
    $fecha = date('Y-m-d', strtotime($datosCdp['fecha']));
}
// si el proceso llega de otro si consulto el id de la adquisición
if ($id_otro != 0) {
    $sql = "SELECT
                    `ctt_adquisiciones`.`id_adquisicion` AS `id_adq`
                    , `ctt_novedad_adicion_prorroga`.`val_adicion` AS `val_adicion`
                FROM
                    `ctt_contratos`
                    INNER JOIN `ctt_adquisiciones` 
                        ON (`ctt_contratos`.`id_compra` = `ctt_adquisiciones`.`id_adquisicion`)
                    INNER JOIN `ctt_novedad_adicion_prorroga` 
                        ON (`ctt_novedad_adicion_prorroga`.`id_adq` = `ctt_contratos`.`id_contrato_compra`)
                WHERE (`ctt_novedad_adicion_prorroga`.`id_nov_con` = $id_otro)";
    $res = $cmd->query($sql);
    $datosOtro = $res->fetch();
    $id_adq = $datosOtro['id_adq'];
    $valorotro = $datosOtro['val_adicion'];
}
// Si el proceso viene de adquisiciones llama el objeto y valida fecha
$objeto = $datosCdp['objeto'];
if ($id_adq > 0) {
    // consulto datos de ctt_adquisiciones donde id_adq sea igual a id_adquisiciones
    $sql = "SELECT `objeto`,`fecha_adquisicion`,`val_contrato` FROM `ctt_adquisiciones` WHERE `id_adquisicion` = $id_adq";
    $res = $cmd->query($sql);
    $datosAdq = $res->fetch();
    $objeto = $datosAdq['objeto'];
    $valoradq = $datosAdq['val_contrato'];
}
if ($id_otro > 0) {
    $objeto = "OTRO SI " . $objeto;
    $valoradq = $valorotro;
}
// Consulta funcion fechaCierre del modulo 54
$fecha_cierre = fechaCierre($_SESSION['vigencia'], 54, $cmd);
$cmd = null;
$fecha_max = date("Y-m-d", strtotime($_SESSION['vigencia'] . '-12-31'));

$imprimir =  $permisos->PermisosUsuario($opciones, 5401, 6) || $id_rol == 1 ? '<a type="button" class="btn btn-primary btn-sm" onclick="imprimirFormatoCdp(' . $id_cdp . ')" style="width: 5rem;"> <span class="fas fa-print "></span></a>' : '';
$valida = $id_cdp == 0 ? '<input type="hidden" id="valida" value="0">' : '';
$content =
    <<<HTML
    <div class="card w-100">
        <div class="card-header bg-sofia text-white">
            <button class="btn btn-sm me-1 p-0" title="Regresar" onclick="window.history.back();"><i class="fas fa-arrow-left fa-lg"></i></button>
            <b>DETALLE CERTIFICADO DE DISPONIBILIDAD PRESUPUESTAL</b>
        </div>
        <div id="accordionCtt" class="card-body p-2 bg-wiev">
            <input type="hidden" id="peReg" value="{$peReg}">
            <div id="divFormDoc">
                <form id="formAddEjecutaPresupuesto">
                    <input type="hidden" id="id_pto_presupuestos" name="id_pto_presupuestos" value="{$id_ppto}">
                    <input type="hidden" id="id_adq" name="id_adq" value="{$id_adq}">
                    <input type="hidden" id="id_otro" name="id_otro" value="{$id_otro}">
                    <input type="hidden" id="id_pto_docini" value="{$datosCdp['id_manu']}">
                    <div class="right-block">
                        <div class="row pb-1">
                            <div class="col-2">
                                <div class="col"><label for="fecha" class="small">NUMERO CDP:</label></div>
                            </div>
                            <div class="col-10">
                                <input type="number" name="numCdp" id="numCdp" class="form-control form-control-sm bg-input" value="{$datosCdp['id_manu']}" onchange="buscarCdp(value,'CDP')" {$automatico} readonly>
                            </div>
                        </div>
                        <div class="row pb-1">
                            <div class="col-2">
                                <div class="col"><label for="fecha" class="small">FECHA:</label></div>
                            </div>
                            <div class="col-10">
                                <input type="date" name="fecha" id="fecha" class="form-control form-control-sm bg-input" min="{$fecha_cierre}" max="{$fecha_max}" value="{$fecha}" onchange="buscarConsecutivo('CDP');" readonly>
                            </div>
                        </div>
                        <div class="row pb-1">
                            <div class="col-2">
                                <div class="col"><label for="fecha" class="small">OBJETO:</label></div>
                            </div>
                            <div class="col-10"> <textarea id="objeto" type="text" name="objeto" class="form-control form-control-sm py-0 sm" aria-label="Default select example" rows="3" required="required" readonly>{$objeto}</textarea></div>
                        </div>
                        <div class="row pb-1">
                            <div class="col-2">
                                <div class="col"><label for="solicitud" class="small">No SOLICITUD:</label></div>
                            </div>
                            <div class="col-10">
                                <input type="text" name="solicitud" id="solicitud" class="form-control form-control-sm bg-input" value="{$datosCdp['num_solicitud']}">
                            </div>
                        </div>

                    </div>
                </form>
            </div>
            <form id="formAddModDetalleCDP">
                {$valida}
                <input type="hidden" id="id_cdp" name="id_cdp" value="{$id_cdp}">
                <input type="hidden" id="id_pto_cdp" name="id_pto_cdp" value="{$id_ppto}">
                <input type="hidden" id="id_pto_movto" name="id_pto_movto" value="{$id_ppto}">
                <table id="tableEjecCdp" class="table table-striped table-bordered table-sm table-hover shadow w-100">
                    <thead>
                        <tr>
                            <th class="bg-sofia" style="width: 8%;">ID</th>
                            <th class="bg-sofia" style="width: 60%;">Codigo</th>
                            <th class="bg-sofia" style="width: 20%;" class="text-center">Valor</th>
                            <th class="bg-sofia" style="width: 12%;">Acciones</th>
                        </tr>
                    </thead>
                    <tbody id="modificarEjecCdp">
                    </tbody>
                </table>
            </form>
            <div class="text-center pt-4">
                {$imprimir}
                <a type="button" id="volverListaCdps" class="btn btn-danger btn-sm" style="width: 5rem;" href="#"> VOLVER</a>
            </div>
        </div>
    </div>
    HTML;

$plantilla = new Plantilla($content, 2);
$plantilla->addCssFile("{$host}/assets/css/jquery-ui.css?v=" . date("YmdHis"));
$plantilla->addScriptFile("{$host}/assets/js/jquery-ui.js?v=" . date("YmdHis"));
$plantilla->addScriptFile("{$host}/src/presupuesto/js/funcionpresupuesto.js?v=" . date("YmdHis"));
$modal = $plantilla->getModal('divModalForms', 'divTamModalForms', 'divForms');
$plantilla->addModal($modal);
echo $plantilla->render();
