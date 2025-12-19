<?php
session_start();
if (!isset($_SESSION['user'])) {
    header('Location: ../index.php');
    exit();
}
include '../../config/autoloader.php';

use Config\Clases\Plantilla;
use Src\Common\Php\Clases\Combos;
use Src\Common\Php\Clases\Permisos;

$id_rol = $_SESSION['rol'];
$id_user = $_SESSION['id_user'];

$permisos = new Permisos();
$opciones = $permisos->PermisoOpciones($id_user);

$host = Plantilla::getHost();
$meses = Combos::getMeses();

$content = <<<HTML
<div class="card w-100">
    <div class="card-header bg-sofia text-white">
        <button class="btn btn-sm me-1 p-0" title="Regresar" onclick="window.history.back();"><i class="fas fa-arrow-left fa-lg"></i></button>
        <b>CONCILIACIÓN BANCARIA</b>
    </div>
    <div class="card-body p-2 bg-wiev">
        <div class="justify-content-center d-flex">
            <div class="input-group input-group-sm border border-warning w-25">
                <span class="input-group-text bg-warning text-white">MES</span>
                <select class="form-select form-select-sm" id="slcMesConcBanc" onchange="recargarConciliacion()">
                    {$meses}
                </select>
            </div>
        </div>
        <table id="tableConcBancaria" class="table table-striped table-bordered table-sm nowrap table-hover shadow w-100">
            <thead>
                <tr class="text-center">
                    <th class="bg-sofia">Banco</th>
                    <th class="bg-sofia">Tipo<br>Cuenta</th>
                    <th class="bg-sofia">Descripción</th>
                    <th class="bg-sofia">No. Cta.</th>
                    <th class="bg-sofia">Saldo</th>
                    <th class="bg-sofia">Conciliar</th>
                    <th class="bg-sofia">Acciones</th>
                </tr>
            </thead>
            <tbody id="modificarTableConcBancaria">
            </tbody>
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
