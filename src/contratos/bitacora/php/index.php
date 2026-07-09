<?php
session_start();
if (!isset($_SESSION['user'])) {
    header("Location: ../../../../index.php");
    exit();
}

include_once '../../../../config/autoloader.php';

use Config\Clases\Plantilla;
use Config\Clases\Sesion;

$host = Plantilla::getHost();

$content = <<<HTML
<div class="card w-100">
    <div class="card-header bg-sofia text-white">
        <a class="btn btn-xs me-1 p-0" title="Regresar" href="{$host}/src/inicio.php"><i class="fas fa-arrow-left fa-lg"></i></a>
        <b>BITÁCORA DE CONTRATACIÓN PÚBLICA</b>
    </div>
    <div class="card-body p-2 bg-wiev">
        <table id="tableBitacora" class="table table-striped table-bordered table-sm table-hover align-middle shadow" style="width:100%">
            <thead>
                <tr id="filterRow" class="bg-light">
                    <th class="text-center">
                        <button class="btn btn-sm btn-outline-secondary" type="button" onclick="LimpiarFiltro(tableBitacora);" title="Limpiar Filtros">
                            <i class="fas fa-eraser"></i>
                        </button>
                    </th>
                    <th></th>
                    <th><input type="text" class="form-control form-control-sm" placeholder="Usuario" id="filter_Usuario"></th>
                    <th><input type="text" class="form-control form-control-sm" placeholder="Tipo" id="filter_Tipo"></th>
                    <th><input type="text" class="form-control form-control-sm" placeholder="Descripción" id="filter_Desc"></th>
                    <th><input type="text" class="form-control form-control-sm" placeholder="Relación" id="filter_Rel"></th>
                    <th>
                        <button class="btn btn-sm btn-outline-warning" type="button" onclick="FiltraDatos(tableBitacora);" title="Filtrar">
                            <i class="fas fa-filter"></i>
                        </button>
                    </th>
                </tr>
                <tr>
                    <th class="bg-sofia text-muted">ID</th>
                    <th class="bg-sofia text-muted">FECHA EVENTO</th>
                    <th class="bg-sofia text-muted">USUARIO</th>
                    <th class="bg-sofia text-muted">TIPO EVENTO</th>
                    <th class="bg-sofia text-muted">DESCRIPCIÓN</th>
                    <th class="bg-sofia text-muted">RELACIONADO</th>
                    <th class="bg-sofia text-muted">ACCIONES</th>
                </tr>
            </thead>
            <tbody id="modificaBitacora">
            </tbody>
        </table>
    </div>
</div>
HTML;

$plantilla = new Plantilla($content, 2);
$plantilla->addScriptFile("{$host}/src/contratos/bitacora/js/funciones.js?v=" . date("YmdHis"));
$modal = $plantilla->getModal('modalFormsBit', 'tamModalFormsBit', 'bodyModalBit');
$plantilla->addModal($modal);
echo $plantilla->render();
