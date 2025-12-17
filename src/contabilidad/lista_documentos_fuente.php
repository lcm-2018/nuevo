<?php
session_start();
if (!isset($_SESSION['user'])) {
    header('Location: ../index.php');
    exit();
}
include '../../config/autoloader.php';

use Config\Clases\Plantilla;
use Src\Common\Php\Clases\Permisos;

$id_rol = $_SESSION['rol'];
$id_user = $_SESSION['id_user'];

$permisos = new Permisos();
$opciones = $permisos->PermisoOpciones($id_user);

$host = Plantilla::getHost();

// Validar permisos de registro
$peReg = $permisos->PermisosUsuario($opciones, 5505, 2) || $id_rol == 1 ? 1 : 0;

$content = <<<HTML
<div class="card w-100">
    <div class="card-header bg-sofia text-white">
        <i class="fas fa-file-alt fa-lg me-2"></i>
        <b>LISTA DE DOCUMENTOS FUENTE</b>
    </div>
    <div class="card-body p-2 bg-wiev">
        <input type="hidden" id="peReg" value="{$peReg}">
        
        <div class="table-responsive shadow p-2">
            <table id="tableDocumentosFuente" class="table table-striped table-bordered table-sm table-hover w-100">
                <thead class="text-center">
                    <tr>
                        <th class="bg-sofia" style="width: 12%;">Código</th>
                        <th class="bg-sofia" style="width: 34%;">Nombre</th>
                        <th class="bg-sofia" style="width: 13%;">Contabilidad</th>
                        <th class="bg-sofia" style="width: 12%;">Tesorería</th>
                        <th class="bg-sofia" style="width: 12%;">Cuentas por pagar</th>
                        <th class="bg-sofia" style="width: 7%;">Estado</th>
                        <th class="bg-sofia" style="width: 10%;">Acciones</th>
                    </tr>
                </thead>
                <tbody id="modificartableDocumentosFuente">
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
$modal = $plantilla->getModal('divModalReg', 'divTamModalReg', 'divFormsReg');
$plantilla->addModal($modal);
echo $plantilla->render();
