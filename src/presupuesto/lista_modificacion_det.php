<?php
session_start();
header("Pragma: no-cache");
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
$peReg = $permisos->PermisosUsuario($opciones, 5401, 2) || $id_rol == 1 ? 1 : 0;
$host = Plantilla::getHost();

$id_pto_mod = $_POST['id_mod'];
$id_vigencia = $_SESSION['id_vigencia'];

// Consulto los datos generales del nuevo registro presupuestal
try {
    $cmd = \Config\Clases\Conexion::getConexion();
    $sql = "SELECT 
                `pto_mod`.`id_pto_mod`
                , `pto_mod`.`fecha`
                , `pto_mod`.`id_manu`
                , `pto_mod`.`objeto`
                , `pto_mod`.`id_tipo_mod`
                , `pto_mod`.`id_pto`
                , `pto_tipo_mvto`. `codigo`
                , `pto_tipo_mvto`.`id_tmvto`
            FROM `pto_mod`
                INNER JOIN `pto_tipo_mvto` 
                    ON (`pto_mod`.`id_tipo_mod` = `pto_tipo_mvto`.`id_tmvto`)
            WHERE `pto_mod`.`id_pto_mod` = $id_pto_mod";
    $rs = $cmd->query($sql);
    $datos = $rs->fetch();
    $rs->closeCursor();
    unset($rs);
    $cmd = null;
} catch (PDOException $e) {
    echo $e->getCode() == 2002 ? 'Sin Conexión a Mysql (Error: 2002)' : 'Error: ' . $e->getCode();
}

try {
    $cmd = \Config\Clases\Conexion::getConexion();
    $sql = "SELECT
                `pto_mod`.`id_pto_mod`
                , `pto_mod_detalle`.`id_pto_mod`
                , SUM(`pto_mod_detalle`.`valor_deb`) AS `debito`
                , SUM(`pto_mod_detalle`.`valor_cred`) AS `credito`
            FROM
                `pto_mod`
                LEFT JOIN `pto_mod_detalle` 
                    ON (`pto_mod`.`id_pto_mod` = `pto_mod_detalle`.`id_pto_mod`)
            WHERE (`pto_mod`.`id_pto_mod` = $id_pto_mod AND `pto_mod`.`estado` >= 1)
            GROUP BY `pto_mod_detalle`.`id_pto_mod`";
    $rs = $cmd->query($sql);
    $valores = $rs->fetch();
    $rs->closeCursor();
    unset($rs);
    $cmd = null;
} catch (PDOException $e) {
    echo $e->getCode() == 2002 ? 'Sin Conexión a Mysql (Error: 2002)' : 'Error: ' . $e->getCode();
}

try {
    $cmd = \Config\Clases\Conexion::getConexion();
    $sql = "SELECT
                `id_pto`, `id_tipo`
            FROM
                `pto_presupuestos`
            WHERE (`id_vigencia` = $id_vigencia)";
    $rs = $cmd->query($sql);
    $presupuestos = $rs->fetchAll(PDO::FETCH_ASSOC);
    $rs->closeCursor();
    unset($rs);
    $key = array_search(1, array_column($presupuestos, 'id_tipo'));
    $ingreso = $presupuestos[$key]['id_pto'];
    $key = array_search(2, array_column($presupuestos, 'id_tipo'));
    $gasto = $presupuestos[$key]['id_pto'];
    $cmd = null;
} catch (PDOException $e) {
    echo $e->getCode() == 2002 ? 'Sin Conexión a Mysql (Error: 2002)' : 'Error: ' . $e->getCode();
}

$dif = !empty($valores) ? ($valores['debito'] - $valores['credito']) : 0;
$dif = abs($dif);
$fecha = date('Y-m-d', strtotime($datos['fecha']));

switch ($datos['codigo']) {
    case 'ADI':
        $campo1 = 'Ingreso';
        $campo2 = 'Gasto';
        break;
    case 'RED':
        $campo1 = 'Crédito';
        $campo2 = 'Contracrédito';
        break;
    default:
        $campo1 = 'Débito';
        $campo2 = 'Crédito';
        break;
}

// Generar botones de opción si es ADI o RED
$botones_pto = '';
if ($datos['codigo'] == 'ADI' || $datos['codigo'] == 'RED') {
    $checked_ingreso = isset($_POST['id_pto']) && $_POST['id_pto'] == $ingreso ? 'checked' : '';
    $checked_gasto = isset($_POST['id_pto']) && $_POST['id_pto'] == $gasto ? 'checked' : '';

    $botones_pto = <<<HTML
        <div class="row pb-2">
            <div class="col-md-2"></div>
            <div class="col-md-10">
                <div class="btn-group btn-group-toggle" data-bs-toggle="buttons">
                    <label class="btn btn-outline-info active">
                        <input type="radio" class="btnOptionPto" name="tipoPto" id="ptoIngresos" value="{$ingreso}" {$checked_ingreso}> Ingresos
                    </label>
                    <label class="btn btn-outline-info">
                        <input type="radio" class="btnOptionPto" name="tipoPto" value="{$gasto}" id="ptoGastos" {$checked_gasto}> Gasto
                    </label>
                </div>
            </div>
        </div>
HTML;
}

$id_pto_post = isset($_POST['id_pto']) ? $_POST['id_pto'] : '';

$content =
    <<<HTML
    <div class="card w-100">
        <div class="card-header bg-sofia text-white">
            <button class="btn btn-sm me-1 p-0" title="Regresar" onclick="terminarDetalleMod('{$datos['id_tipo_mod']}');"><i class="fas fa-arrow-left fa-lg"></i></button>
            <b>DETALLE DOCUMENTO DE MODIFICACION PRESUPUESTAL</b>
        </div>
        <div class="card-body p-2 bg-wiev">
            <input type="hidden" id="peReg" value="{$peReg}">
            <input type="hidden" id="id_pto_movto" name="id_pto_movto" value="{$id_pto_post}">
            <input type="hidden" id="id_pto_mod" name="id_pto_mod" value="{$id_pto_mod}">
            
            <div class="right-block mb-3">
                <div class="row pb-2">
                    <div class="col-md-2">
                        <label for="numero" class="form-label small">NUMERO:</label>
                    </div>
                    <div class="col-md-10">
                        <strong>{$datos['id_manu']}</strong>
                    </div>
                </div>
                <div class="row pb-2">
                    <div class="col-md-2">
                        <label for="fecha" class="form-label small">FECHA:</label>
                    </div>
                    <div class="col-md-10">
                        <strong>{$fecha}</strong>
                    </div>
                </div>
                <div class="row pb-2">
                    <div class="col-md-2">
                        <label for="objeto" class="form-label small">OBJETO:</label>
                    </div>
                    <div class="col-md-10">
                        {$datos['objeto']}
                    </div>
                </div>
                {$botones_pto}
            </div>
            
            <form id="formAddModDetalle">
                <div class="table-responsive shadow p-2">
                    <table id="tableModDetalle" class="table table-striped table-bordered table-sm table-hover shadow w-100">
                        <thead>
                            <tr class="text-center">
                                <th class="bg-sofia" style="width: 8%;">ID</th>
                                <th class="bg-sofia" style="width: 50%;">Codigo</th>
                                <th class="bg-sofia" style="width: 15%;">{$campo1}</th>
                                <th class="bg-sofia" style="width: 15%;">{$campo2}</th>
                                <th class="bg-sofia" style="width: 12%;">Acciones</th>
                            </tr>
                        </thead>
                        <tbody id="modificarModDetalle">
                        </tbody>
                    </table>
                </div>
            </form>
            
            <div class="text-center pt-4">
                <a onclick="terminarDetalleMod('{$datos['id_tipo_mod']}')" class="btn btn-danger btn-sm" style="width: 7rem;" href="#">TERMINAR</a>
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
