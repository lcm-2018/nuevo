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
        <b>GESTIÓN DE CONTRATOS</b>
    </div>
    <div class="card-body p-2 bg-wiev">
        <table id="tableContratos" class="table table-striped table-bordered table-sm table-hover align-middle shadow" style="width:100%">
            <thead>
                <tr id="filterRow" class="bg-light">
                    <th class="text-center">
                        <button class="btn btn-sm btn-outline-secondary" type="button" onclick="LimpiarFiltro(tableContratos);" title="Limpiar Filtros">
                            <i class="fas fa-eraser"></i>
                        </button>
                    </th>
                    <th><input type="text" class="form-control form-control-sm" placeholder="Contrato" id="filter_CodigoCtt"></th>
                    <th><input type="text" class="form-control form-control-sm" placeholder="Proceso" id="filter_Proceso"></th>
                    <th><input type="text" class="form-control form-control-sm" placeholder="Contratista" id="filter_Tercero"></th>
                    <th><input type="text" class="form-control form-control-sm" placeholder="Fechas" id="filter_Fechas"></th>
                    <th><input type="text" class="form-control form-control-sm" placeholder="Valor" id="filter_Valor"></th>
                    <th><input type="text" class="form-control form-control-sm" placeholder="Estado" id="filter_Estado"></th>
                    <th>
                        <button class="btn btn-sm btn-outline-warning" type="button" onclick="FiltraDatos(tableContratos);" title="Filtrar">
                            <i class="fas fa-filter"></i>
                        </button>
                    </th>
                </tr>
                <tr>
                    <th class="bg-sofia text-muted">ID</th>
                    <th class="bg-sofia text-muted">CÓDIGO CONTRATO</th>
                    <th class="bg-sofia text-muted">PROCESO RELAC.</th>
                    <th class="bg-sofia text-muted">CONTRATISTA</th>
                    <th class="bg-sofia text-muted">FECHAS</th>
                    <th class="bg-sofia text-muted">VALOR TOTAL</th>
                    <th class="bg-sofia text-muted">ESTADO</th>
                    <th class="bg-sofia text-muted">ACCIONES</th>
                </tr>
            </thead>
            <tbody id="modificaContratos">
            </tbody>
        </table>
    </div>
</div>
HTML;

$plantilla = new Plantilla($content, 2);
$plantilla->addScriptFile("{$host}/src/contratos/contratos/js/funciones.js?v=" . date("YmdHis"));
$modal = $plantilla->getModal('modalFormsCtt2', 'tamModalFormsCtt2', 'bodyModalCtt2');
$plantilla->addModal($modal);
echo $plantilla->render();
