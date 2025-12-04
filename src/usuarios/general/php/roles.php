<?php
session_start();

include_once '../../../../config/autoloader.php';

use Config\Clases\Sesion;
use Config\Clases\Plantilla;

if (!isset($_SESSION['user']) || Sesion::Rol() != 1) {
    header("Location: ../../../../index.php");
    exit();
}
$host = Plantilla::getHost();

$content = <<<HTML
    <div class="card w-100">
        <div class="card-header bg-sofia text-white">
            <button class="btn btn-xs me-1 p-0" title="Regresar" onclick="window.history.back();"><i class="fas fa-arrow-left fa-lg"></i></button>
            <b>GESTIÓN DE ROLES DE USUARIOS</b>
        </div>
        <div class="card-body p-2 bg-wiev">
            <table id="tableRoles" class="table table-striped table-bordered table-sm nowrap table-hover shadow tableCotRecibidas" style="width:100%">
                <thead>
                    <tr class="text-center">
                        <th class="bg-sofia">#</th>
                        <th class="bg-sofia">NOMBRE ROL</th>
                        <th class="bg-sofia">ACCIONES</th>
                    </tr>
                </thead>
                <tbody>
                </tbody>
            </table>
        </div>
    </div>
HTML;

$plantilla = new Plantilla($content, 2);
$plantilla->addScriptFile("{$host}/src/usuarios/general/js/roles.js?v=" . date("YmdHis"));
$modal = $plantilla->getModal('modalForms', 'tamModalForms', 'bodyModal');
$plantilla->addModal($modal);
echo $plantilla->render();
