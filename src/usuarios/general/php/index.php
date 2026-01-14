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
            <a class="btn btn-xs me-1 p-0" title="Regresar" href="{$host}/src/inicio.php" fff><i class="fas fa-arrow-left fa-lg"></i></a>
            <b>GESTIÓN DE USUARIOS DEL SISTEMA</b>
        </div>
        <div class="card-body p-2 bg-wiev">
            <table id="tableUsersSystem" class="table table-striped table-bordered table-sm nowrap table-hover shadow" style="width:100%">
                <thead>
                    <tr class="text-center">
                        <th class="bg-sofia">#</th>
                        <th class="bg-sofia">No. DOC.</th>
                        <th class="bg-sofia">NOMBRE</th>
                        <th class="bg-sofia">USUARIO</th>
                        <th class="bg-sofia">ROL</th>
                        <th class="bg-sofia">ESTADO</th>
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
$plantilla->addScriptFile("{$host}/src/usuarios/general/js/funciones.js?v=" . date("YmdHis"));
$modal = $plantilla->getModal('modalForms', 'tamModalForms', 'bodyModal');
$plantilla->addModal($modal);
$modal = $plantilla->getModal('modalModulos', 'tamModalModulos', 'bodyModulos');
$plantilla->addModal($modal);
echo $plantilla->render();
