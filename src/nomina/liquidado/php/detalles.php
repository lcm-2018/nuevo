<?php
session_start();
if (!isset($_SESSION['user'])) {
    header("Location: ../../../../index.php");
    exit();
}

include_once '../../../../config/autoloader.php';

use Config\Clases\Plantilla;
use Src\Nomina\Liquidacion\Php\Clases\Nomina;

$host = Plantilla::getHost();
$id_nomina = isset($_POST['id_nomina']) ? $_POST['id_nomina'] : exit('Acceso no autorizado');
$nomina = (new Nomina())->getRegistro($id_nomina);

if (isset($nomina['estado']) && $nomina['estado'] == 1) {
    $definitiva = '<button type="button" id="btnCerrarNomina" class="btn btn-outline-warning btn-sm mb-2"><i class="fas fa-check-circle fa-lg me-1"></i> Definitiva</button>';
} else if (isset($nomina['estado']) && $nomina['estado'] > 1) {
    $definitiva = '<div class="badge bg-success py-2 mb-2"></i> NÓMINA DEFINITIVA</div>';
} else {
    $definitiva = '';
}



$content = <<<HTML

<div class="card w-100">
    <div class="card-header bg-sofia text-white">
        <a class="btn btn-xs me-1 p-0" title="Regresar" href="index.php"><i class="fas fa-arrow-left fa-lg"></i></a>
        <b>DETALLES DE NÓMINA No. {$id_nomina}</b>
    </div>
    <div class="card-body p-2 bg-wiev">
        <form id="formDetallesNomina">
            <input type="hidden" id="id_nomina" value="{$id_nomina}">
            <div class="text-end">
                {$definitiva}
            </div>
            <table id="tableDetallesNomina" class="table table-striped table-bordered table-sm table-hover align-middle shadow nowrap w-100">
                <thead>
                    <tr>
                        <th rowspan="2" class="text-center bg-sofia">ID</th>
                        <th rowspan="2" class="text-center bg-sofia">NOMBRE</th>
                        <th rowspan="2" class="text-center bg-sofia">No. DOC.</th>
                        <th rowspan="2" class="text-center bg-sofia">SEDE</th>
                        <th rowspan="2" class="text-center bg-sofia">CARGO</th>
                        <th rowspan="2" class="text-center bg-sofia" title="Salario Base">BASE</th>
                        <th colspan="5" class="text-center bg-sofia">DIAS</th>
                        <th colspan="5" class="text-center bg-sofia">VALOR</th>
                        <th rowspan="2" class="text-center bg-sofia" title="Auxilio Transporte">AUX. T.</th>
                        <th rowspan="2" class="text-center bg-sofia" title="Auxilio Alimentación">AUX. A.</th>
                        <th rowspan="2" class="text-center bg-sofia" title="Horas Extras">EXTRAS</th>
                        <th rowspan="2" class="text-center bg-sofia" title="Bonificación por Servicios Prestados">BSP</th>
                        <th rowspan="2" class="text-center bg-sofia" title="Prima de Vacaciones">P. VAC.</th>
                        <th rowspan="2" class="text-center bg-sofia" title="Gastos Representación">G. REP.</th>
                        <th rowspan="2" class="text-center bg-sofia" title="Bonificación por Recreación">B. REC.</th>
                        <th rowspan="2" class="text-center bg-sofia" title="Prima de Servicios">P. SERV.</th>
                        <th rowspan="2" class="text-center bg-sofia" title="Prima de Navidad">P. NAV.</th>
                        <th rowspan="2" class="text-center bg-sofia" title="Cesantías">CES.</th>
                        <th rowspan="2" class="text-center bg-sofia" title="Intereses Cesantías">I. CES.</th>
                        <th rowspan="2" class="text-center bg-sofia" title="Compensatorio">COMP.</th>
                        <th rowspan="2" class="text-center bg-sofia" title="Total Devengado">T. DEV.</th>
                        <th colspan="11" class="text-center bg-sofia">DEDUCCIONES</th>
                        <th rowspan="2" class="text-center bg-sofia" title="Total Deducciones">T. DED.</th> 
                        <th rowspan="2" class="text-center bg-sofia" title="Neto a Pagar">NETO</th>
                        <th rowspan="2" class="text-center bg-sofia">Patronal</th>
                        <th rowspan="2" class="text-center bg-sofia">ACCIONES</th>
                    </tr>
                    <tr>
                        <th class="text-center bg-sofia border" title="Incapacidades">INC.</th>
                        <th class="text-center bg-sofia border" title="Licencias">LIC.</th>
                        <th class="text-center bg-sofia border" title="Vacaciones">VAC.</th>
                        <th class="text-center bg-sofia border" title="Otros">OTRO.</th>
                        <th class="text-center bg-sofia border" title="Días Laborados">LAB.</th>
                        <th class="text-center bg-sofia border" title="Incapacidades">INC.</th>
                        <th class="text-center bg-sofia border" title="Licencias">LIC.</th>
                        <th class="text-center bg-sofia border" title="Vacaciones">VAC.</th>
                        <th class="text-center bg-sofia border" title="Otros">OTRO.</th>
                        <th class="text-center bg-sofia border" title="Días Laborados">LAB.</th>
                        <th class="text-center bg-sofia border" title="Salud">SALUD</th>
                        <th class="text-center bg-sofia border" title="Pensión">PENS.</th>
                        <th class="text-center bg-sofia border" title="Pensión solidaria">P. SOLID.</th>
                        <th class="text-center bg-sofia border" title="Riesgo Laboral">RLAB.</th>
                        <th class="text-center bg-sofia border" title="Salud Patronal">SALUD</th>
                        <th class="text-center bg-sofia border" title="Pensión Patronal">PENS.</th>
                        <th class="text-center bg-sofia border" title="Libranzas">LIB.</th>
                        <th class="text-center bg-sofia border" title="Embargos">EMB.</th>
                        <th class="text-center bg-sofia border" title="Sindicatos">SIND.</th>
                        <th class="text-center bg-sofia border" title="Retención en la Fuente">R. FTE.</th>
                        <th class="text-center bg-sofia border" title="Otros Descuentos">DCTO.</th>
                    </tr>
                </thead>
                <tbody id="bodytableDetallesNomina">
                </tbody>
            </table>
        </form>  
    </div>
</div>
HTML;

$plantilla = new Plantilla($content, 2);
$plantilla->addCssFile("{$host}/assets/css/fixedColumns.bootstrap5.css?v=" . date("YmdHis"));
$plantilla->addScriptFile("{$host}/assets/js/jszip.min.js?v=" . date("YmdHis"));
$plantilla->addScriptFile("{$host}/assets/js/dataTables.fixedColumns.min.js?v=" . date("YmdHis"));
$plantilla->addScriptFile("{$host}/assets/js/dataTables.buttons.js?v=" . date("YmdHis"));
$plantilla->addScriptFile("{$host}/assets/js/fixedColumns.bootstrap5.js?v=" . date("YmdHis"));
$plantilla->addScriptFile("{$host}/assets/js/button.html5.min.js?v=" . date("YmdHis"));
$plantilla->addScriptFile("{$host}/src/nomina/liquidado/js/detalles.js?v=" . date("YmdHis"));
$modal = $plantilla->getModal('modalForms', 'tamModalForms', 'bodyModal');
$plantilla->addModal($modal);
echo $plantilla->render();
