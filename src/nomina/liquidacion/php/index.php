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
$liquidacion = Combos::getTipoLiquidacion();

$content = <<<HTML

<div class="card w-100">
    <div class="card-header bg-sofia text-white">
        <a class="btn btn-xs me-1 p-0" title="Regresar" href="{$host}/src/inicio.php" fff><i class="fas fa-arrow-left fa-lg"></i></a>
        <b>LIQUIDACIÓN MENSUAL DE EMPLEADOS</b>
    </div>
    <div class="card-body p-2 bg-wiev">
        <form id="formLiquidacion">
            <table id="tableLiqMesEmpleados" class="table table-striped table-bordered table-sm table-hover align-middle shadow" style="width:100%">
                <thead>
                    <tr id="filterRow" class="bg-light">
                        <th class="text-center">
                            <button class="btn btn-sm btn-outline-secondary" type="button" onclick="LimpiarFiltro(tableLiqMesEmpleados);" title="Limpiar Filtros">
                                <i class="fas fa-eraser"></i>
                            </button>
                        </th>
                        <th><input type="text" class="form-control form-control-sm bg-input" placeholder="No. Documento" id="filter_nodoc"></th>
                        <th><input type="text" class="form-control form-control-sm bg-input" placeholder="Nombre" id="filter_nombre"></th>
                        <th><input type="text" class="form-control form-control-sm bg-input" placeholder="Observación" id="filter_observacion"></th>
                        <th colspan="2" class="text-center">
                            <select class="form-select form-select-sm bg-input w-100" id="filter_tipo" title="Tipo de liquidación">
                                {$liquidacion}
                            </select>
                        </th>
                        <th colspan="3" class="text-center">
                            <select class="form-select form-select-sm bg-input w-100" id="filter_mes" title="Mes de liquidación">
                                {$meses}
                            </select>
                        </th>
                        <th>
                            <button class="btn btn-sm btn-outline-warning" type="button" onclick="FiltraDatos(tableLiqMesEmpleados);" title="Filtrar">
                                <i class="fas fa-filter"></i>
                            </button>
                        </th>
                    </tr>
                    <tr>
                        <th rowspan="2" class="text-center bg-sofia"><input type="checkbox" onclick="SelectAll(this)" checked></th>
                        <th rowspan="2" class="text-center bg-sofia">No. DOC.</th>
                        <th rowspan="2" class="text-center bg-sofia">NOMBRE</th>
                        <th rowspan="2" class="text-center bg-sofia">OBSERVACIÓN</th>
                        <th colspan="5" class="text-center bg-sofia">DIAS</th>
                        <th rowspan="2" style="max-width: 110px;" class="text-center bg-sofia">MÉTODO<br>DE PAGO</th>
                    </tr>
                    <tr>
                        <th class="text-center bg-sofia" title="Dias laborados en el mes">Labor</th>
                        <th class="text-center bg-sofia" title="Total días incapacidad">Incap.</th>
                        <th class="text-center bg-sofia" title="Total días de licencia">Lic.</th>
                        <th class="text-center bg-sofia" title="Total días de vacaciones">Vac.</th>
                        <th class="text-center bg-sofia" title="Otros">Otros</th>
                    </tr>
                </thead>
                <tbody id="bodyTableLiqMesEmpleados">
                </tbody>
            </table>
        </form>  
    </div>
</div>
HTML;

$plantilla = new Plantilla($content, 2);
$plantilla->addScriptFile("{$host}/src/nomina/liquidacion/js/funciones.js?v=" . date("YmdHis"));
$modal = $plantilla->getModal('modalForms', 'tamModalForms', 'bodyModal');
$plantilla->addModal($modal);
echo $plantilla->render();
