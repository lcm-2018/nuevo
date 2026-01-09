<?php
session_start();
if (!isset($_SESSION['user'])) {
    header("Location: ../../../index.php");
    exit();
}
if ($_SESSION['rol'] != 1) {
    header("Location: ../../../index.php");
    exit();
}
include_once '../../../config/autoloader.php';


use Config\Clases\Plantilla;
use Src\Common\Php\Clases\Combos;

$host = Plantilla::getHost();
$modulos = Combos::getModulos();
$numeral = 1;

$content = <<<HTML
<div class="card w-100">
    <div class="card-header bg-sofia text-white">
        <a class="btn btn-xs me-1 p-0" title="Regresar" href="{$host}/src/inicio.php" fff><i class="fas fa-arrow-left fa-lg"></i></a>
        <b>CONFIGURACIÓN DE DOCUMENTOS</b>
    </div>
    <div class="card-body p-2 bg-wiev">
        <table id="tableGeDocs" class="table table-striped table-bordered table-sm table-hover align-middle shadow w-100">
            <thead>
                <tr id="filterRow" class="bg-light">
                    <th class="text-center">
                        <button class="btn btn-sm btn-outline-secondary" type="button" onclick="LimpiarFiltro(tableGeDocs);" title="Limpiar Filtros">
                            <i class="fas fa-eraser"></i>
                        </button>
                    </th>
                    <th class="text-center">
                        <select id="filter_modulo" class="form-select form-select-sm bg-input">
                            {$modulos}
                        </select>
                    </th>
                    <th><input type="text" class="form-control form-control-sm bg-input" placeholder="Nombre Documento" id="filter_doc"></th>
                    <th><input type="text" class="form-control form-control-sm bg-input" placeholder="Version Doc." id="filter_version"></th>
                    <th><input type="date" class="form-control form-control-sm bg-input" id="filter_fecha"></th>
                    <th class="text-center">
                        <select id="filter_control" class="form-select form-select-sm bg-input">
                            <option value="">-- Seleccione --</option>
                            <option value="1">SI</option>
                            <option value="0">NO</option>
                        </select>
                    </th>
                    <th class="text-center">
                        <select id="filter_estado" class="form-select form-select-sm bg-input">
                            <option value="">-- Seleccione --</option>
                            <option value="1">ACTIVO</option>
                            <option value="0">INACTIVO</option>
                        </select>
                    </th>
                    <th>
                        <button class="btn btn-sm btn-outline-warning" type="button" onclick="FiltraDatos(tableGeDocs);" title="Filtrar">
                            <i class="fas fa-filter"></i>
                        </button>
                    </th>
                </tr>
                <tr>
                    <th class="text-center bg-sofia">ID</th>
                    <th class="text-center bg-sofia">MÓDULO</th>
                    <th class="text-center bg-sofia">DOCUMENTO</th>
                    <th class="text-center bg-sofia">VERSION</th>
                    <th class="text-center bg-sofia">FECHA</th>
                    <th class="text-center bg-sofia">CONTROL</th>
                    <th class="text-center bg-sofia">ESTADO</th>
                    <th class="text-center bg-sofia">ACCION</th>
                </tr>
            </thead>
        </table>  
    </div>
</div>
HTML;

$content = preg_replace_callback('/VIÑETA/', function () use (&$numeral) {
    return $numeral++;
}, $content);

$plantilla = new Plantilla($content, 2);
$plantilla->addScriptFile("{$host}/src/documentos/js/funciones.js?v=" . date("YmdHis"));
$plantilla->addScriptFile("{$host}/src/documentos/js/detalles.js?v=" . date("YmdHis"));
$modal = $plantilla->getModal('modalForms', 'tamModalForms', 'bodyModal');
$plantilla->addModal($modal);
echo $plantilla->render();
