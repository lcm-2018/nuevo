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
$numeral = 1;
$incremento_salarial = '';
if (Sesion::Caracter() == 2) {
    $incremento_salarial =
        <<<HTML
            <div class="accordion-item">
                <h2 class="accordion-header">
                    <button class="accordion-button sombra collapsed bg-head-button border" type="button" data-bs-toggle="collapse" data-bs-target="#divIncrementoSal" aria-expanded="false" aria-controls="divIncrementoSal">
                        <span class="text-muted"><i class="fas fa-chart-line me-2 fa-lg"></i>VIÑETA. Incremento Salarial.</span>
                    </button>
                </h2>
                <div id="divIncrementoSal" class="accordion-collapse collapse">
                    <div class="accordion-body bg-wiev">
                        <table id="tableIncSalario" class="table table-striped table-bordered table-sm table-hover align-middle shadow" style="width:100%">
                            <thead class="text-center">
                                <tr>
                                    <th class="bg-sofia">ID</th>
                                    <th class="bg-sofia">PORCENTAJE</th>
                                    <th class="bg-sofia">INICIA</th>
                                    <th class="bg-sofia">ESTADO</th>
                                    <th class="bg-sofia">ACCIONES</th>
                                </tr>
                            </thead>
                            <tbody id="modificaIncSalario"></tbody>
                        </table>
                    </div>
                </div>
            </div>
        HTML;
}
$presupuesto = '';
if (Sesion::Pto() == 1) {
    $presupuesto =
        <<<HTML
        <div class="accordion-item">
            <h2 class="accordion-header">
                <button class="accordion-button sombra collapsed bg-head-button border" type="button" data-bs-toggle="collapse" data-bs-target="#divRubroPto" aria-expanded="false" aria-controls="divRubroPto">
                    <span class="text-warning"><i class="fas fa-chart-bar me-2 fa-lg"></i>VIÑETA. Rubros Presupuestales.</span>
                </button>
            </h2>
            <div id="divRubroPto" class="accordion-collapse collapse">
                <div class="accordion-body bg-wiev">
                    <table id="tableRubroPto" class="table table-striped table-bordered table-sm table-hover align-middle shadow" style="width:100%">
                        <thead class="text-center">
                            <tr>
                                <th rowspan="2" class="bg-sofia">ID</th>
                                <th rowspan="2" class="bg-sofia">TIPO</th>
                                <th colspan="2" class="bg-sofia">RUBRO ADMINISTRATIVO</th>
                                <th colspan="2" class="bg-sofia">RUBRO OPERATIBO</th>
                                <th rowspan="2" class="bg-sofia">ACCIONES</th>
                            </tr>
                            <tr>
                                <th class="bg-sofia">CÓDIGO</th>
                                <th class="bg-sofia">NOMBRE</th>
                                <th class="bg-sofia">CÓDIGO</th>
                                <th class="bg-sofia">NOMBRE</th>
                            </tr>
                        </thead>
                        <tbody id="modificaRubroPto"></tbody>
                    </table>
                </div>
            </div>
        </div>
    HTML;
}
$content = <<<HTML
<div class="card w-100">
    <div class="card-header bg-sofia text-white">
        <a class="btn btn-xs me-1 p-0" title="Regresar" href="{$host}/src/inicio.php" fff><i class="fas fa-arrow-left fa-lg"></i></a>
        <b>CONFIGURACIÓN DE NÓMINA</b>
    </div>
    <div class="card-body p-2 bg-wiev">
        <div class="accordion" id="accNomina">
            <div class="accordion-item">
                <h2 class="accordion-header">
                    <button class="accordion-button sombra bg-head-button border" type="button" data-bs-toggle="collapse" data-bs-target="#divParamsLiq" aria-expanded="true" aria-controls="divParamsLiq">
                        <span class="text-primary"><i class="far fa-list-alt me-2 fa-lg"></i>VIÑETA. Parametros de liquidación.</span>
                    </button>
                </h2>
                <div id="divParamsLiq" class="accordion-collapse collapse">
                    <div class="accordion-body bg-wiev">
                        <table id="tableParamLiq" class="table table-striped table-bordered table-sm table-hover align-middle shadow" style="width:100%">
                            <thead class="text-center">
                                <tr>
                                    <th class="bg-sofia">ID</th>
                                    <th class="bg-sofia">CONCEPTO</th>
                                    <th class="bg-sofia">VALOR</th>
                                    <th class="bg-sofia">ACCIONES</th>
                                </tr>
                            </thead>
                            <tbody id="modificaParamLiq">
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <div class="accordion-item">
                <h2 class="accordion-header">
                    <button class="accordion-button sombra collapsed bg-head-button border" type="button" data-bs-toggle="collapse" data-bs-target="#divCargos" aria-expanded="false" aria-controls="divCargos">
                        <span class="text-success"><i class="fas fa-user-tie me-2 fa-lg"></i>VIÑETA. Cargos.</span>
                    </button>
                </h2>
                <div id="divCargos" class="accordion-collapse collapse">
                    <div class="accordion-body bg-wiev">
                        <table id="tableCargosNom" class="table table-striped table-bordered table-sm table-hover align-middle shadow" style="width:100%">
                            <thead class="text-center">
                                <tr>
                                    <th class="bg-sofia">#</th>
                                    <th class="bg-sofia">CÓDIGO</th>
                                    <th class="bg-sofia">CARGO</th>
                                    <th class="bg-sofia">GRADO</th>
                                    <th class="bg-sofia">PERFÍL SIHO</th>
                                    <th class="bg-sofia">NOMBRAMIENTO</th>
                                    <th class="bg-sofia">ACCIONES</th>
                                </tr>
                            </thead>
                            <tbody id="modificaCargoNom">
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <div class="accordion-item">
                <h2 class="accordion-header">
                    <button class="accordion-button sombra collapsed bg-head-button border" type="button" data-bs-toggle="collapse" data-bs-target="#divTerceros" aria-expanded="false" aria-controls="divTerceros">
                        <span class="text-info"><i class="fas fa-users me-2 fa-lg"></i>VIÑETA. Terceros.</span>
                    </button>
                </h2>
                <div id="divTerceros" class="accordion-collapse collapse">
                    <div class="accordion-body bg-wiev">
                    <input type="hidden" id="tipoTerceroNom" value="eps">
                        <ul class="nav nav-tabs small" id="btnsTerceros" role="tablist">
                            <li class="nav-item bg-sofia" role="presentation">
                                <button data-id="eps" class="nav-link active tipo-datas" data-filter="EPS" data-bs-toggle="tab" data-bs-target="#tab-pane" type="button" role="tab" aria-selected="true">EPS</button>
                            </li>
                            <li class="nav-item bg-sofia" role="presentation">
                                <button data-id="afp" class="nav-link tipo-datas" data-filter="AFP" data-bs-toggle="tab" data-bs-target="#tab-pane" type="button" role="tab" aria-selected="false">AFP</button>
                            </li>
                            <li class="nav-item bg-sofia" role="presentation">
                                <button data-id="arl" class="nav-link tipo-datas" data-filter="ARL" data-bs-toggle="tab" data-bs-target="#tab-pane" type="button" role="tab" aria-selected="false">ARL</button>
                            </li>
                            <li class="nav-item bg-sofia" role="presentation">
                                <button data-id="ces" class="nav-link tipo-datas" data-filter="CESANTIAS" data-bs-toggle="tab" data-bs-target="#tab-pane" type="button" role="tab" aria-selected="false">CESANTÍAS</button>
                            </li>
                            <li class="nav-item bg-sofia" role="presentation">
                                <button data-id="ban" class="nav-link tipo-datas" data-filter="BANCO" data-bs-toggle="tab" data-bs-target="#tab-pane" type="button" role="tab" aria-selected="false">BANCO</button>
                            </li>
                            <li class="nav-item bg-sofia" role="presentation">
                                <button data-id="juz" class="nav-link tipo-datas" data-filter="JUZGADO" data-bs-toggle="tab" data-bs-target="#tab-pane" type="button" role="tab" aria-selected="false">JUZGADO</button>
                            </li>
                            <li class="nav-item bg-sofia" role="presentation">
                                <button data-id="sin" class="nav-link tipo-datas" data-filter="SINDICATO" data-bs-toggle="tab" data-bs-target="#tab-pane" type="button" role="tab" aria-selected="false">SINDICATO</button>
                            </li>
                            <li class="nav-item bg-sofia" role="presentation">
                                <button data-id="par" class="nav-link tipo-datas" data-filter="PARAFISCALES" data-bs-toggle="tab" data-bs-target="#tab-pane" type="button" role="tab" aria-selected="false">PARAFISCALES</button>
                            </li>
                        </ul>
                        <div class="tab-content pt-2" id="myTabContent">
                            <div class="tab-pane fade show active" id="tab-pane" role="tabpanel">
                                <table id="tableTerceroNom" class="table table-striped table-bordered table-sm table-hover align-middle shadow" style="width:100%">
                                    <thead class="text-center">
                                        <tr>
                                            <th class="bg-sofia">ID</th>
                                            <th class="bg-sofia">NOMBRE</th>
                                            <th class="bg-sofia">NIT</th>
                                            <th class="bg-sofia">DIRECCIÓN</th>
                                            <th class="bg-sofia">TELÉFONO</th>
                                        </tr>
                                    </thead>
                                    <tbody id="modificaTerceroNom"></tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            {$incremento_salarial}
            {$presupuesto}
            <div class="accordion-item">
                <h2 class="accordion-header">
                    <button class="accordion-button sombra collapsed bg-head-button border" type="button" data-bs-toggle="collapse" data-bs-target="#divCtaCtbNom" aria-expanded="false" aria-controls="divCtaCtbNom">
                        <span class="text-secondary"><i class="fas fa-file-import me-2 fa-lg"></i>VIÑETA. Cuentas Contables.</span>
                    </button> 
                </h2>
                <div id="divCtaCtbNom" class="accordion-collapse collapse">
                    <div class="accordion-body bg-wiev">
                        <table id="tableCtaCtbNom" class="table table-striped table-bordered table-sm nowrap table-hover shadow" style="width:100%">
                            <thead class="text-center">
                                <tr>
                                    <th class="bg-sofia">ID</th>
                                    <th class="bg-sofia">CENTRO COSTO</th>
                                    <th class="bg-sofia">TIPO</th>
                                    <th class="bg-sofia">NOMBRE</th>
                                    <th class="bg-sofia">CUENTA</th>
                                    <th class="bg-sofia">NOMBRE</th>
                                    <th class="bg-sofia">ACCIONES</th>
                                </tr>
                            </thead>
                            <tbody id="modificaCtaCtbNom">
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
HTML;

$content = preg_replace_callback('/VIÑETA/', function () use (&$numeral) {
    return $numeral++;
}, $content);

$plantilla = new Plantilla($content, 2);
$plantilla->addScriptFile("{$host}/src/nomina/configuracion/js/funciones.js?v=" . date("YmdHis"));
$modal = $plantilla->getModal('modalForms', 'tamModalForms', 'bodyModal');
$plantilla->addModal($modal);
echo $plantilla->render();
