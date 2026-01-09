<?php
session_start();
if (!isset($_SESSION['user'])) {
    header("Location: ../../../../index.php");
    exit();
}

include_once '../../../../config/autoloader.php';

use Config\Clases\Plantilla;

$host = Plantilla::getHost();

$content = <<<HTML

<div class="card w-100">
    <div class="card-header bg-sofia text-white">
        <a class="btn btn-xs me-1 p-0" title="Regresar" href="{$host}/src/inicio.php" fff><i class="fas fa-arrow-left fa-lg"></i></a>
        <b>LISTADO DE NÓMINAS DE EMPLEADOS</b>
    </div>
    <div class="card-body p-2 bg-wiev">
        <form id="formLiquidacion">
            <table id="tablenNominasEmpleados" class="table table-striped table-bordered table-sm table-hover align-middle shadow" style="width:100%">
                <thead>
                    <tr id="filterRow" class="bg-light">
                        <th class="text-center">
                            <button class="btn btn-sm btn-outline-secondary" type="button" onclick="LimpiarFiltro(tablenNominasEmpleados);" title="Limpiar Filtros">
                                <i class="fas fa-eraser"></i>
                            </button>
                        </th>
                        <th><input type="text" class="form-control form-control-sm bg-input" placeholder="Descripción" id="filter_descripcion"></th>
                        <th><input type="text" class="form-control form-control-sm bg-input" placeholder="Mes" id="filter_mes"></th>
                        <th><input type="text" class="form-control form-control-sm bg-input" placeholder="Tipo" id="filter_tipo"></th>
                        <th class="text-center">
                            <select class="form-select form-select-sm bg-input w-100" id="filter_estado" title="Estado de liquidación">
                                <option value="">-- SELECCIONAR --</option>
                                <option value="0">ANULADO</option>
                                <option value="2">DEFINITIVA</option>
                                <option value="1">PENDIENTE</option>
                            </select>
                        </th>
                        <th>
                            <button class="btn btn-sm btn-outline-warning" type="button" onclick="FiltraDatos(tablenNominasEmpleados);" title="Filtrar">
                                <i class="fas fa-filter"></i>
                            </button>
                        </th>
                    </tr>
                    <tr>
                        <th class="text-center bg-sofia">ID</th>
                        <th class="text-center bg-sofia">DESCRIPCIÓN</th>
                        <th class="text-center bg-sofia">MES</th>
                        <th class="text-center bg-sofia">TIPO</th>
                        <th class="text-center bg-sofia">ESTADO</th>
                        <th class="text-center bg-sofia">ACCIONES</th>
                    </tr>
                </thead>
                <tbody id="bodytablenNominasEmpleados">
                </tbody>
            </table>
        </form>  
    </div>
</div>
HTML;

$plantilla = new Plantilla($content, 2);
$plantilla->addScriptFile("{$host}/src/nomina/liquidado/js/funciones.js?v=" . date("YmdHis"));
$modal = $plantilla->getModal('modalForms', 'tamModalForms', 'bodyModal');
$plantilla->addModal($modal);
echo $plantilla->render();
