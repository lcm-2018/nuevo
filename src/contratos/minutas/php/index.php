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
        <b>GESTIÓN DE MINUTAS DE CONTRATOS</b>
    </div>
    <div class="card-body p-2 bg-wiev">
        <table id="tableMinutas" class="table table-striped table-bordered table-sm table-hover align-middle shadow" style="width:100%">
            <thead>
                <tr id="filterRow" class="bg-light">
                    <th class="text-center">
                        <button class="btn btn-sm btn-outline-secondary" type="button" onclick="LimpiarFiltro(tableMinutas);" title="Limpiar Filtros">
                            <i class="fas fa-eraser"></i>
                        </button>
                    </th>
                    <th></th>
                    <th></th>
                    <th><input type="text" class="form-control form-control-sm" placeholder="Contrato" id="filter_Contrato"></th>
                    <th><input type="text" class="form-control form-control-sm" placeholder="Usuario" id="filter_Usuario"></th>
                    <th>
                        <button class="btn btn-sm btn-outline-warning" type="button" onclick="FiltraDatos(tableMinutas);" title="Filtrar">
                            <i class="fas fa-filter"></i>
                        </button>
                    </th>
                </tr>
                <tr>
                    <th class="bg-sofia text-muted">ID</th>
                    <th class="bg-sofia text-muted">VERSIÓN</th>
                    <th class="bg-sofia text-muted">FECHA REGISTRO</th>
                    <th class="bg-sofia text-muted">CÓDIGO CONTRATO</th>
                    <th class="bg-sofia text-muted">USUARIO</th>
                    <th class="bg-sofia text-muted">ACCIONES</th>
                </tr>
            </thead>
            <tbody id="modificaMinutas">
            </tbody>
        </table>
    </div>
</div>
HTML;

$plantilla = new Plantilla($content, 2);
$plantilla->addScriptFile("{$host}/src/contratos/minutas/js/funciones.js?v=" . date("YmdHis"));
$modal = $plantilla->getModal('modalFormsMin', 'tamModalFormsMin', 'bodyModalMin');
$plantilla->addModal($modal);
echo $plantilla->render();
