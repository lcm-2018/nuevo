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
        <b>GESTIÓN DE PROCESOS DE CONTRATACIÓN PÚBLICA</b>
    </div>
    <div class="card-body p-2 bg-wiev">
        <table id="tableProcesos" class="table table-striped table-bordered table-sm table-hover align-middle shadow" style="width:100%">
            <thead>
                <tr id="filterRow" class="bg-light">
                    <th class="text-center">
                        <button class="btn btn-sm btn-outline-secondary" type="button" onclick="LimpiarFiltro(tableProcesos);" title="Limpiar Filtros">
                            <i class="fas fa-eraser"></i>
                        </button>
                    </th>
                    <th><input type="text" class="form-control form-control-sm" placeholder="Código" id="filter_Codigo"></th>
                    <th><input type="text" class="form-control form-control-sm" placeholder="Objeto" id="filter_Objeto"></th>
                    <th><input type="text" class="form-control form-control-sm" placeholder="Tipo Proceso" id="filter_Tipo"></th>
                    <th><input type="text" class="form-control form-control-sm" placeholder="Modalidad" id="filter_Modalidad"></th>
                    <th><input type="text" class="form-control form-control-sm" placeholder="Estado" id="filter_Estado"></th>
                    <th>
                        <button class="btn btn-sm btn-outline-warning" type="button" onclick="FiltraDatos(tableProcesos);" title="Filtrar">
                            <i class="fas fa-filter"></i>
                        </button>
                    </th>
                </tr>
                <tr>
                    <th class="bg-sofia text-muted">ID</th>
                    <th class="bg-sofia text-muted">CÓDIGO</th>
                    <th class="bg-sofia text-muted">OBJETO</th>
                    <th class="bg-sofia text-muted">TIPO PROCESO</th>
                    <th class="bg-sofia text-muted">MODALIDAD</th>
                    <th class="bg-sofia text-muted">ESTADO</th>
                    <th class="bg-sofia text-muted">ACCIONES</th>
                </tr>
            </thead>
            <tbody id="modificaProcesos">
            </tbody>
        </table>
    </div>
</div>
HTML;

$plantilla = new Plantilla($content, 2);
$plantilla->addScriptFile("{$host}/src/contratos/procesos/js/funciones.js?v=" . date("YmdHis"));
$modal = $plantilla->getModal('modalFormsProc', 'tamModalFormsProc', 'bodyModalProc');
$plantilla->addModal($modal);
echo $plantilla->render();
