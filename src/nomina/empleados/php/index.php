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
        <button class="btn btn-xs me-1 p-0" title="Regresar" onclick="window.history.back();"><i class="fas fa-arrow-left fa-lg"></i></button>
        <b>GESTIÓN DE EMPLEADOS</b>
    </div>
    <div class="card-body p-2 bg-wiev">
        <table id="tableEmpleados" class="table table-striped table-bordered table-sm table-hover align-middle shadow" style="width:100%">
            <thead>
                <tr id="filterRow" class="bg-light">
                    <th class="text-center">
                        <button class="btn btn-sm btn-outline-secondary" type="button" onclick="LimpiarFiltro(tableEmpleados);" title="Limpiar Filtros">
                            <i class="fas fa-eraser"></i>
                        </button>
                    </th>
                    <th><input type="text" class="form-control form-control-sm" placeholder="No. Documento" id="filter_Nodoc"></th>
                    <th><input type="text" class="form-control form-control-sm" placeholder="Nombre" id="filter_Nombre"></th>
                    <th><input type="text" class="form-control form-control-sm" placeholder="Correo" id="filter_Correo"></th>
                    <th><input type="text" class="form-control form-control-sm" placeholder="Teléfono" id="filter_Tel"></th>
                    <th class="text-center">
                        <select id="filter_Status" class="form-select form-select-sm">
                            <option value="1" class="text-muted">--Seleccionar--</option>
                            <option value="0">Inactivos</option>
                            <option value="2">Todos</option>
                        </select>
                    </th>
                    <th>
                        <button class="btn btn-sm btn-outline-warning" type="button" onclick="FiltraDatos(tableEmpleados);" title="Filtrar">
                            <i class="fas fa-filter"></i>
                        </button>
                    </th>
                </tr>
                <tr>
                    <th class="bg-sofia text-muted">ID</th>
                    <th class="bg-sofia text-muted">No. DOC</th>
                    <th class="bg-sofia text-muted">NOMBRE</th>
                    <th class="bg-sofia text-muted">CORREO</th>
                    <th class="bg-sofia text-muted">TELEFONO</th>
                    <th class="bg-sofia text-muted">ESTADO</th>
                    <th class="bg-sofia text-muted">ACCIONES</th>
                </tr>
            </thead>
            <tbody id="modificaEmpleados">
            </tbody>
        </table>
    </div>
</div>
HTML;

$plantilla = new Plantilla($content, 2);
$plantilla->addScriptFile("{$host}/src/nomina/empleados/js/funciones.js?v=" . date("YmdHis"));
$modal = $plantilla->getModal('modalForms', 'tamModalForms', 'bodyModal');
$plantilla->addModal($modal);
echo $plantilla->render();
