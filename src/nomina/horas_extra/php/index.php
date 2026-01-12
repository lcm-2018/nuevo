<?php
session_start();
if (!isset($_SESSION['user'])) {
    header("Location: ../../../../index.php");
    exit();
}

include_once '../../../../config/autoloader.php';

use Config\Clases\Plantilla;
use Src\Common\Php\Clases\Combos;

$host = Plantilla::getHost();
$meses = Combos::getMeses();

$content = <<<HTML

<div class="card w-100">
    <div class="card-header bg-sofia text-white">
        <a class="btn btn-xs me-1 p-0" title="Regresar" href="{$host}/src/inicio.php" fff><i class="fas fa-arrow-left fa-lg"></i></a>
        <b>HORAS EXTRAS DE EMPLEADOS</b>
    </div>
    <div class="card-body p-2 bg-wiev">
        <table id="tableHorasExtra" class="table table-striped table-bordered table-sm table-hover align-middle shadow" style="width:100%">
            <thead>
                <tr id="filterRow" class="bg-light">
                    <th class="text-center">
                        <button class="btn btn-sm btn-outline-secondary" type="button" onclick="LimpiarFiltro(tableHorasExtra);" title="Limpiar Filtros">
                            <i class="fas fa-eraser"></i>
                        </button>
                    </th>
                    <th><input type="text" class="form-control form-control-sm" placeholder="No. Documento" id="filter_nodoc"></th>
                    <th><input type="text" class="form-control form-control-sm" placeholder="Nombre" id="filter_nombre"></th>
                    <th colspan="3" class="text-center">
                        <select id="filter_tipo" name="slcTipoLiq" class="form-select form-select-sm bg-input" onchange="cargarHorasExtra()">
                            <option value="1" selected>MENSUAL</option>
                            <option value="2">PRESTACIONES SOCIALES</option>
                        </select>
                    </th>
                    <th colspan="3" class="text-center">
                        <select id="filter_mes" class="form-select form-select-sm bg-input" onchange="cargarHorasExtra()">
                            $meses
                        </select>
                    </th>
                    <th>
                        <button class="btn btn-sm btn-outline-warning" type="button" onclick="FiltraDatos(tableHorasExtra);" title="Filtrar">
                            <i class="fas fa-filter"></i>
                        </button>
                    </th>
                </tr>
                <tr>
                    <th rowspan="2" class="text-center bg-sofia">ID</th>
                    <th rowspan="2" class="text-center bg-sofia">No. DOC.</th>
                    <th rowspan="2" class="text-center bg-sofia">NOMBRE</th>
                    <th colspan="7" class="text-center bg-sofia">HORAS EXTRA</th>
                </tr>
                <tr>
                    <th class="text-center bg-sofia" title="Diurna">DO</th>
                    <th class="text-center bg-sofia" title="Nocturna">NO</th>
                    <th class="text-center bg-sofia" title="Recargo Nocturno">RNO</th>
                    <th class="text-center bg-sofia" title="Diurna Dominical y Festivos">DD</th>
                    <th class="text-center bg-sofia" title="Recargo Diurno Dominical y Festivos">RDD</th>
                    <th class="text-center bg-sofia" title="Nocturna Dominical y Festivos">NDF</th>
                    <th class="text-center bg-sofia" title="Recargo Nocturno Dominical y Festivos">RNDF</th>
                </tr>
            </thead>
        </table>       
    </div>
</div>
HTML;

$plantilla = new Plantilla($content, 2);
$plantilla->addScriptFile("{$host}/src/nomina/horas_extra/js/funciones.js?v=" . date("YmdHis"));
$modal = $plantilla->getModal('modalForms', 'tamModalForms', 'bodyModal');
$plantilla->addModal($modal);
echo $plantilla->render();
