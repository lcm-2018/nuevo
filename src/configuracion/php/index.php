<?php
session_start();
if (!isset($_SESSION['user'])) {
    header("Location: ../../../../index.php");
    exit();
}

include_once '../../../config/autoloader.php';

use Config\Clases\Plantilla;
use Config\Clases\Sesion;

$host = Plantilla::getHost();

$content = <<<HTML

<div class="card w-100">
    <div class="card-header bg-sofia text-white">
        <b>CONFIGURACIÓN DE CONSULTAS</b>
    </div>
    <div class="card-body p-2 bg-wiev">
        <div class="accordion" id="accConfiguracion">
            <div class="accordion-item">
                <h2 class="accordion-header">
                    <button class="accordion-button sombra bg-head-button border" type="button" data-bs-toggle="collapse" data-bs-target="#divConfiguracion" aria-expanded="true" aria-controls="divParamsLiq">
                        <span class="text-primary"><i class="far fa-list-alt me-2 fa-lg"></i>Prsonalizadas</span>
                    </button>
                </h2>
                <div id="divConfiguracion" class="accordion-collapse collapse">
                    <div class="accordion-body bg-wiev">
                        <table id="tableConfiguracion" class="table table-striped table-bordered table-sm table-hover align-middle shadow" style="width:100%">
                            <thead>
                                <tr>
                                    <th class="text-center">ID</th>
                                    <th class="text-center">NOMBRE</th>
                                    <th class="text-center">DESCRIPCION</th>
                                    <th class="text-center">ACCIONES</th>
                                </tr>
                            </thead>
                            <tbody id="modificaConfiguracion">
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>        
    </div>
</div>
HTML;

$plantilla = new Plantilla($content, 2);
$plantilla->addScriptFile("{$host}/src/configuracion/js/configuracion.js?v=" . date("YmdHis"));
$modal = $plantilla->getModal('modalForms', 'tamModalForms', 'bodyModal');
$plantilla->addModal($modal);
echo $plantilla->render();
