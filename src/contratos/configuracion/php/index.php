<?php
session_start();
if (!isset($_SESSION['user'])) {
    header("Location: ../../../../index.php");
    exit();
}

include_once '../../../../config/autoloader.php';

use Config\Clases\Plantilla;
use Config\Clases\Sesion;

$host    = Plantilla::getHost();
$numeral = 1;

$content = <<<HTML
<div class="card w-100">
    <div class="card-header bg-sofia text-white">
        <a class="btn btn-xs me-1 p-0" title="Regresar" href="{$host}/src/inicio.php">
            <i class="fas fa-arrow-left fa-lg"></i>
        </a>
        <b>CONFIGURACIÓN — CONTRATACIÓN PÚBLICA</b>
    </div>
    <div class="card-body p-2 bg-wiev">
        <div class="accordion" id="accConfigCtt">

            <!-- 1. Tipos de Proceso -->
            <div class="accordion-item">
                <h2 class="accordion-header">
                    <button class="accordion-button sombra bg-head-button border" type="button"
                        data-bs-toggle="collapse" data-bs-target="#divTiposProceso"
                        aria-expanded="true" aria-controls="divTiposProceso">
                        <span class="text-primary">
                            <i class="fas fa-tags me-2 fa-lg"></i>VIÑETA. Tipos de Proceso.
                        </span>
                    </button>
                </h2>
                <div id="divTiposProceso" class="accordion-collapse collapse show">
                    <div class="accordion-body bg-wiev">
                        <table id="tableTiposProc" class="table table-striped table-bordered table-sm table-hover align-middle shadow" style="width:100%">
                            <thead class="text-center">
                                <tr>
                                    <th class="bg-sofia">ID</th>
                                    <th class="bg-sofia">NOMBRE</th>
                                    <th class="bg-sofia">DESCRIPCIÓN</th>
                                    <th class="bg-sofia">ESTADO</th>
                                    <th class="bg-sofia">ACCIONES</th>
                                </tr>
                            </thead>
                            <tbody id="modificaTiposProc"></tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- 2. Modalidades -->
            <div class="accordion-item">
                <h2 class="accordion-header">
                    <button class="accordion-button sombra collapsed bg-head-button border" type="button"
                        data-bs-toggle="collapse" data-bs-target="#divModalidades"
                        aria-expanded="false" aria-controls="divModalidades">
                        <span class="text-success">
                            <i class="fas fa-file-signature me-2 fa-lg"></i>VIÑETA. Modalidades de Contratación.
                        </span>
                    </button>
                </h2>
                <div id="divModalidades" class="accordion-collapse collapse">
                    <div class="accordion-body bg-wiev">
                        <table id="tableModalidadesCtt" class="table table-striped table-bordered table-sm table-hover align-middle shadow" style="width:100%">
                            <thead class="text-center">
                                <tr>
                                    <th class="bg-sofia">ID</th>
                                    <th class="bg-sofia">MODALIDAD</th>
                                    <th class="bg-sofia">ACCIONES</th>
                                </tr>
                            </thead>
                            <tbody id="modificaModalidadesCtt"></tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- 3. Estados -->
            <div class="accordion-item">
                <h2 class="accordion-header">
                    <button class="accordion-button sombra collapsed bg-head-button border" type="button"
                        data-bs-toggle="collapse" data-bs-target="#divEstadosCtt"
                        aria-expanded="false" aria-controls="divEstadosCtt">
                        <span class="text-warning">
                            <i class="fas fa-traffic-light me-2 fa-lg"></i>VIÑETA. Estados del Contrato.
                        </span>
                    </button>
                </h2>
                <div id="divEstadosCtt" class="accordion-collapse collapse">
                    <div class="accordion-body bg-wiev">
                        <table id="tableEstadosCtt" class="table table-striped table-bordered table-sm table-hover align-middle shadow" style="width:100%">
                            <thead class="text-center">
                                <tr>
                                    <th class="bg-sofia">ORDEN</th>
                                    <th class="bg-sofia">NOMBRE / BADGE</th>
                                    <th class="bg-sofia">PERMITE EDICIÓN</th>
                                    <th class="bg-sofia">ACCIONES</th>
                                </tr>
                            </thead>
                            <tbody id="modificaEstadosCtt"></tbody>
                        </table>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>
HTML;

// Reemplazar VIÑETA por numeral automático
$content = preg_replace_callback('/VIÑETA/', function () use (&$numeral) {
    return $numeral++;
}, $content);

$plantilla = new Plantilla($content, 2);
$plantilla->addScriptFile("{$host}/src/contratos/configuracion/js/funciones.js?v=" . date("YmdHis"));
$modal = $plantilla->getModal('modalFormsCtt', 'tamModalFormsCtt', 'bodyModalCtt');
$plantilla->addModal($modal);
echo $plantilla->render();
