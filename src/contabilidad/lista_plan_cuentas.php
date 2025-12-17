<?php
session_start();
if (!isset($_SESSION['user'])) {
    header('Location: ../../index.php');
    exit();
}
include '../../config/autoloader.php';

use Config\Clases\Plantilla;
use Src\Common\Php\Clases\Permisos;

$id_rol = $_SESSION['rol'];
$id_user = $_SESSION['id_user'];

$permisos = new Permisos();
$opciones = $permisos->PermisoOpciones($id_user);
$peReg = $permisos->PermisosUsuario($opciones, 5504, 2) || $id_rol == 1 ? 1 : 0;
$host = Plantilla::getHost();

// Verificar si hay registros en ctb_libaux
try {
    $cmd = \Config\Clases\Conexion::getConexion();
    $sql = "SELECT COUNT(*) AS `cantiad` FROM `ctb_libaux`";
    $rs = $cmd->query($sql);
    $registros = $rs->fetch();
    $registros = $registros['cantiad'];
    $cmd = null;
} catch (PDOException $e) {
    echo $e->getCode() == 2002 ? 'Sin Conexión a Mysql (Error: 2002)' : 'Error: ' . $e->getCode();
}

// Botones adicionales según cantidad de registros
$botonesAdicionales = '';
if ($registros == 0 && $peReg) {
    $botonesAdicionales = <<<HTML
    <button class="btn btn-outline-success btn-sm" id="cargaExcelPuc" title="Cargar plan de cuentas con archivo Excel">
        <i class="far fa-file-excel fa-lg"></i>
    </button>
    <button class="btn btn-outline-primary btn-sm" id="formatoExcelPuc" title="Descargar formato cargue de plan de cuentas">
        <i class="fas fa-download fa-lg"></i>
    </button>
HTML;
}

$content = <<<HTML
<div class="card w-100">
    <div class="card-header bg-sofia text-white">
        <button class="btn btn-sm me-1 p-0" title="Regresar" onclick="window.history.back();"><i class="fas fa-arrow-left fa-lg"></i></button>
        <b>LISTA DE CUENTAS CONTABLES</b>
    </div>
    <div class="card-body p-2 bg-wiev">
        <input type="hidden" id="peReg" value="{$peReg}">
        
        <div class="d-flex justify-content-end align-items-center gap-2 mb-2">
            {$botonesAdicionales}
        </div>

        <div class="table-responsive shadow p-2">
            <table id="tablePlanCuentas" class="table table-striped table-bordered table-sm table-hover shadow w-100">
                <thead class="text-center">
                    <tr>
                        <th class="bg-sofia">Fecha</th>
                        <th class="bg-sofia">Cuenta</th>
                        <th class="bg-sofia">Nombre</th>
                        <th class="bg-sofia">Tipo</th>
                        <th class="bg-sofia">Nivel</th>
                        <th class="bg-sofia" title="Desagregación de terceros">Des.</th>
                        <th class="bg-sofia">Estado</th>
                        <th class="bg-sofia">Acciones</th>
                    </tr>
                </thead>
                <tbody id="modificartablePlanCuentas">
                </tbody>
            </table>
        </div>
    </div>
</div>
HTML;

$plantilla = new Plantilla($content, 2);
$plantilla->addCssFile("{$host}/assets/css/jquery-ui.css?v=" . date("YmdHis"));
$plantilla->addScriptFile("{$host}/assets/js/jquery-ui.js?v=" . date("YmdHis"));
$plantilla->addScriptFile("{$host}/src/contabilidad/js/funcioncontabilidad.js?v=" . date("YmdHis"));
$modal = $plantilla->getModal('divModalForms', 'divTamModalForms', 'divForms');
$plantilla->addModal($modal);
echo $plantilla->render();
