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

// Verificar permisos de registro
$peReg = ($permisos->PermisosUsuario($opciones, 5608, 0) || $id_rol == 1) ? '1' : '0';

$content = <<<HTML
<div class="card w-100">
    <div class="card-header bg-sofia text-white">
        <button class="btn btn-sm me-1 p-0" title="Regresar" onclick="window.history.back();"><i class="fas fa-arrow-left fa-lg"></i></button>
        <b>LISTA DE CHEQUERAS REGISTRADAS EN EL SISTEMA</b>
        <input type="hidden" id="peReg" value="{$peReg}">
    </div>
    <div class="card-body p-2 bg-wiev">
        <table id="tableFinChequeras" class="table table-striped table-bordered table-sm table-hover shadow w-100 nowrap">
            <thead>
                <tr class="text-center">
                    <th class="bg-sofia">Fecha</th>
                    <th class="bg-sofia">Banco</th>
                    <th class="bg-sofia">Cuenta</th>
                    <th class="bg-sofia">Num chequera</th>
                    <th class="bg-sofia">Inicial</th>
                    <th class="bg-sofia">En uso</th>
                    <th class="bg-sofia">Acciones</th>
                </tr>
            </thead>
            <tbody id="modificartableFinChequeras">
            </tbody>
            <tfoot>
                <tr class="text-center">
                    <th class="bg-sofia">Fecha</th>
                    <th class="bg-sofia">Banco</th>
                    <th class="bg-sofia">Cuenta</th>
                    <th class="bg-sofia">Num chequera</th>
                    <th class="bg-sofia">Inicial</th>
                    <th class="bg-sofia">En uso</th>
                    <th class="bg-sofia">Acciones</th>
                </tr>
            </tfoot>
        </table>
    </div>
</div>
HTML;

$plantilla = new Plantilla($content, 2);
$plantilla->addCssFile("{$host}/assets/css/jquery-ui.css?v=" . date("YmdHis"));
$plantilla->addScriptFile("{$host}/assets/js/jquery-ui.js?v=" . date("YmdHis"));
$plantilla->addScriptFile("{$host}/src/tesoreria/js/funciontesoreria.js?v=" . date("YmdHis"));

$modal = $plantilla->getModal('divModalForms', 'divTamModalForms', 'divForms');
$plantilla->addModal($modal);
$modal = $plantilla->getModal('divModalReg', 'divTamModalReg', 'divFormsReg');
$plantilla->addModal($modal);
$modal = $plantilla->getModal('divModalAux', 'divTamModalAux', 'divFormsAux');
$plantilla->addModal($modal);

echo $plantilla->render();
