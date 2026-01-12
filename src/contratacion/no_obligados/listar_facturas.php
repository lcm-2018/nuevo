<?php
session_start();
if (!isset($_SESSION['user'])) {
    header("Location: ../../../index.php");
    exit();
}

include_once '../../../config/autoloader.php';

use Config\Clases\Plantilla;
use Src\Common\Php\Clases\Permisos;


$host = Plantilla::getHost();
$numeral = 1;
$permisos = new Permisos();

$id_rol = $_SESSION['rol'];
$id_user = $_SESSION['id_user'];
$opciones = $permisos->PermisoOpciones($id_user);
$peReg =  $permisos->PermisosUsuario($opciones, 5302, 0) || $id_rol == 1 ? 1 : 0;


$content = <<<HTML

<div class="card w-100">
    <div class="card-header bg-sofia text-white">
        <a class="btn btn-xs me-1 p-0" title="Regresar" href="{$host}/src/inicio.php"><i class="fas fa-arrow-left fa-lg"></i></a>
        <b>LISTA DE FACTURAS DE ADQUISICIONES CON NO OBLIGADOS.</b>
    </div>
    <div class="card-body p-2 bg-wiev">
        <input type="hidden" id="peReg" value="{$peReg}">
        <table id="tableFacurasNoObligados" class="table table-striped table-bordered table-sm nowrap table-hover shadow align-middle w-100">
            <thead>
                <tr class="text-center">
                    <th class="bg-sofia">ID</th>
                    <th class="bg-sofia">Tipo</th>
                    <th class="bg-sofia">Estado</th>
                    <th class="bg-sofia">Fecha</th>
                    <th class="bg-sofia">Vence</th>
                    <th class="bg-sofia">Método<br>Pago</th>
                    <th class="bg-sofia">Forma Pago</th>
                    <th class="bg-sofia">Tipo</th>
                    <th class="bg-sofia">No. Doc.</th>
                    <th class="bg-sofia">Nombre y/o Razón social</th>
                    <th class="bg-sofia" style="min-width: 300px;">Detalles</th>
                    <th class="bg-sofia">Acción</th>
                </tr>
            </thead>
            <tbody id="modificarFacturaNoObligados">
            </tbody>
        </table>
    </div>
</div>
HTML;
$content = preg_replace_callback('/VIÑETA/', function () use (&$numeral) {
    return $numeral++;
}, $content);

$plantilla = new Plantilla($content, 2);
$plantilla->addCssFile("{$host}/assets/css/jquery-ui.css?v=" . date("YmdHis"));
$plantilla->addScriptFile("{$host}/assets/js/jquery-ui.js?v=" . date("YmdHis"));
$plantilla->addScriptFile("{$host}/src/contratacion/no_obligados/js/funciones_no_obligados.js?v=" . date("YmdHis"));
$modal = $plantilla->getModal('divModalForms', 'divTamModalForms', 'divForms');
$plantilla->addModal($modal);
echo $plantilla->render();
