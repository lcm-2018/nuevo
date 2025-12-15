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
$peReg = $permisos->PermisosUsuario($opciones, 5401, 2) || $id_rol == 1 ? 1 : 0;
$host = Plantilla::getHost();

// Consulta tipo de presupuesto
$id_pto_presupuestos = $_POST['id_pto'];
try {
    $cmd = \Config\Clases\Conexion::getConexion();
    $sql = "SELECT `id_tipo`, `nombre` FROM `pto_presupuestos` WHERE `id_pto` = $id_pto_presupuestos";
    $rs = $cmd->query($sql);
    $presupuesto = $rs->fetch();
    $id_tipo = $presupuesto['id_tipo'];
    $rs->closeCursor();
    unset($rs);
    $cmd = null;
} catch (PDOException $e) {
    echo $e->getCode() == 2002 ? 'Sin Conexión a Mysql (Error: 2002)' : 'Error: ' . $e->getCode();
}

$buscar = $id_tipo == 1 ? 1 : 2;
$tipo_dato = isset($_POST['tipo_mod']) ? $_POST['tipo_mod'] : "0";

try {
    $cmd = \Config\Clases\Conexion::getConexion();
    $sql = "SELECT `id_tmvto`,`codigo`,`nombre` FROM `pto_tipo_mvto` WHERE (`filtro` = $buscar OR `filtro` = 0) ORDER BY `nombre`";
    $rs = $cmd->query($sql);
    $tipoMod = $rs->fetchAll(PDO::FETCH_ASSOC);
    $rs->closeCursor();
    unset($rs);
    $cmd = null;
} catch (PDOException $e) {
    echo $e->getCode() == 2002 ? 'Sin Conexión a Mysql (Error: 2002)' : 'Error: ' . $e->getCode();
}

// Generar opciones del select
$opciones_select = '<option value="0">-- Seleccionar --</option>';
foreach ($tipoMod as $mov) {
    $selected = $mov['id_tmvto'] == $tipo_dato ? 'selected' : '';
    $opciones_select .= '<option value="' . $mov['id_tmvto'] . '" ' . $selected . '>' . $mov['nombre'] . '</option>';
}

// Generar tabla si hay tipo seleccionado
$tabla_html = '';
if ($tipo_dato != 0) {
    $tabla_html = <<<HTML
        <div class="table-responsive shadow p-2">
            <table id="tableModPresupuesto" class="table table-striped table-bordered table-sm nowrap table-hover shadow w-100">
                <thead>
                    <tr class="text-center">
                        <th class="bg-sofia">Número</th>
                        <th class="bg-sofia">Fecha</th>
                        <th class="bg-sofia">Documento</th>
                        <th class="bg-sofia">Acto admin</th>
                        <th class="bg-sofia">Valor</th>
                        <th class="bg-sofia">Acciones</th>
                    </tr>
                </thead>
                <tbody id="modificarModPresupuesto">
                </tbody>
            </table>
        </div>
HTML;
}

$content =
    <<<HTML
    <div class="card w-100">
        <div class="card-header bg-sofia text-white">
            <button class="btn btn-sm me-1 p-0" title="Regresar" onclick="window.location.href='lista_presupuestos.php';"><i class="fas fa-arrow-left fa-lg"></i></button>
            <b>MODIFICACIONES A {$presupuesto['nombre']}</b>
        </div>
        <div class="card-body p-2 bg-wiev">
            <input type="hidden" id="peReg" value="{$peReg}">
            <input type="hidden" id="id_pto_ppto" value="{$id_pto_presupuestos}">
            <input type="hidden" id="id_mov" value="{$tipo_dato}">
            
            <div class="row mb-3">
                <div class="col-md-12">
                    <div class="d-flex justify-content-center">
                        <form action="{$_SERVER['PHP_SELF']}" method="POST" class="w-25">
                            <select class="form-select form-select-sm bg-input" id="id_pto_doc" name="id_pto_doc" onchange="cambiaListadoModifica(value)">
                                {$opciones_select}
                            </select>
                        </form>
                    </div>
                </div>
            </div>
            
            {$tabla_html}
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
