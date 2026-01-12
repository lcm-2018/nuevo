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
$peReg =  $permisos->PermisosUsuario($opciones, 5402, 0) || $id_rol == 1 ? 1 : 0;
$host = Plantilla::getHost();

$content =
    <<<HTML
    <div class="card w-100">
        <div class="card-header bg-sofia text-white">
            <a class="btn btn-xs me-1 p-0" title="Regresar" href="{$host}/src/inicio.php"><i class="fas fa-arrow-left fa-lg"></i></a>
            <b>LISTADO DE PRESUPUESTOS</b>
        </div>
        <div id="accordionCtt" class="card-body p-2 bg-wiev">
            <input type="hidden" id="peReg" value="{$peReg}">
            <table id="tablePresupuesto" class="table table-striped table-bordered table-sm nowrap table-hover shadow w-100">
                <thead>
                    <tr>
                        <th class="bg-sofia">ID</th>
                        <th class="bg-sofia">Presupuesto</th>
                        <th class="bg-sofia">Tipo</th>
                        <th class="bg-sofia">Vigencia</th>
                        <th class="bg-sofia">Acciones</th>
                    </tr>
                </thead>
                <tbody id="modificarPresupuesto">
                </tbody>
            </table>
        </div>
    </div>
    HTML;

$plantilla = new Plantilla($content, 2);
$plantilla->addScriptFile("{$host}/src/presupuesto/js/funcionpresupuesto.js?v=" . date("YmdHis"));
$modal = $plantilla->getModal('divModalForms', 'divTamModalForms', 'divForms');
$plantilla->addModal($modal);
echo $plantilla->render();
