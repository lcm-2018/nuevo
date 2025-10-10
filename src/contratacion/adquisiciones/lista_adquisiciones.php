<?php
session_start();
if (!isset($_SESSION['user'])) {
    header("Location: ../../../index.php");
    exit();
}

include_once '../../../config/autoloader.php';

use Config\Clases\Plantilla;
use Src\Common\Php\Clases\Combos;
use Src\Common\Php\Clases\Permisos;


$host = Plantilla::getHost();
$numeral = 1;
$permisos = new Permisos();

$id_rol = $_SESSION['rol'];
$id_user = $_SESSION['id_user'];
$opciones = $permisos->PermisoOpciones($id_user);
$peReg =  $permisos->PermisosUsuario($opciones, 5302, 0) || $id_rol == 1 ? 1 : 0;

$estados = Combos::getEstadoAdq();
$modalidades = Combos::getModalidad();

$content = <<<HTML

<div class="card w-100">
    <div class="card-header bg-sofia text-white">
        <button class="btn btn-xs me-1 p-0" title="Regresar" onclick="window.history.back();"><i class="fas fa-arrow-left fa-lg"></i></button>
        <b>LISTADO DE ADQUISICIONES / COMPRAS</b>
    </div>
    <div class="card-body p-2 bg-wiev">
        <input type="hidden" id="peReg" value="{$peReg}">
        <table id="tableAdquisiciones" class="table table-striped table-bordered table-sm nowrap table-hover shadow align-middle w-100">
            <thead>
                <tr id="filterRow" class="bg-light">
                    <th class="text-center">
                        <button class="btn btn-sm btn-outline-secondary" type="button" onclick="LimpiarFiltro(tableAdquisiciones);" title="Limpiar Filtros">
                            <i class="fas fa-eraser"></i>
                        </button>
                    </th>
                    <th class="text-center">
                        <select id="filter_Modalidad" class="form-select form-select-sm">
                            {$modalidades}
                        </select>
                    </th>
                    <th><input type="text" class="form-control form-control-sm" placeholder="Valor" id="filter_Valor"></th>
                    <th><input type="date" class="form-control form-control-sm" placeholder="Fecha" id="filter_Fecha"></th>
                    <th><input type="text" class="form-control form-control-sm" placeholder="Objeto" id="filter_Objeto"></th>
                    <th><input type="text" class="form-control form-control-sm" placeholder="Tercero" id="filter_Tercero"></th>
                    <th class="text-center">
                        <select id="filter_Status" class="form-select form-select-sm">
                            {$estados}
                        </select>
                    </th>
                    <th>
                        <button class="btn btn-sm btn-outline-warning" type="button" onclick="FiltraDatos(tableAdquisiciones);" title="Filtrar">
                            <i class="fas fa-filter"></i>
                        </button>
                    </th>
                </tr>
                <tr class="text-center">
                    <th class="bg-sofia">ID</th>
                    <th class="bg-sofia">Modalidad</th>
                    <th class="bg-sofia">Valor</th>
                    <th class="bg-sofia">Fecha</th>
                    <th class="bg-sofia">Objeto</th>
                    <th class="bg-sofia">Tercero</th>
                    <th class="bg-sofia">Estado</th>
                    <th class="bg-sofia">Acción</th>
                </tr>
            </thead>
            <tbody id="modificarAdquisiciones">
            </tbody>
        </table>
    </div>
</div>
HTML;
$content = preg_replace_callback('/VIÑETA/', function () use (&$numeral) {
    return $numeral++;
}, $content);

$plantilla = new Plantilla($content, 2);
$plantilla->addScriptFile("{$host}/src/contratacion/adquisiciones/js/funciones_adquisiciones.js?v=" . date("YmdHis"));
$modal = $plantilla->getModal('divModalForms', 'divTamModalForms', 'divForms');
$plantilla->addModal($modal);
echo $plantilla->render();
