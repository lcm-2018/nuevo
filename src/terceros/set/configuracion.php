<?php
session_start();
if (!isset($_SESSION['user'])) {
    header("Location: ../../../index.php");
    exit();
}

include_once '../../../config/autoloader.php';

use Config\Clases\Plantilla;
use Src\Common\Php\Clases\Combos;
use Src\Common\Php\Clases\Permisos;


$host = Plantilla::getHost();
$numeral = 1;
$permisos = new Permisos();

$id_rol = $_SESSION['rol'];
$id_user = $_SESSION['id_user'];
$opciones = $permisos->PermisoOpciones($id_user);
$peReg =  $permisos->PermisosUsuario($opciones, 5302, 0) || $id_rol == 1 ? 1 : 0;

$estados = Combos::getEstadoAdq();
$modalidades = Combos::getModalidad();

$content = <<<HTML

<div class="card w-100">
    <div class="card-header bg-sofia text-white">
        <button class="btn btn-xs me-1 p-0" title="Regresar" onclick="window.history.back();"><i class="fas fa-arrow-left"></i></button>
        <b>CONFIGURACIONES PARA TERCEROS</b>
    </div>
    <div class="card-body p-2 bg-wiev">
        <input type="hidden" id="peReg" value="{$peReg}">
        <div class="accordion" id="accTerceros">
            <div class="accordion-item">
                <h2 class="accordion-header">
                    <button class="accordion-button collapsed bg-head-button border" type="button" data-bs-toggle="collapse" data-bs-target="#collapsemodContrata" aria-expanded="false" aria-controls="collapsemodContrata">
                        <span class="text-primary"><i class="fas fa-clipboard-list me-2"></i>VIÑETA. Responsabilidades Económicas.</span>
                    </button>
                </h2>
                <div id="collapsemodContrata" class="accordion-collapse collapse" data-bs-parent="#accTerceros">
                    <div class="accordion-body bg-wiev">
                        <table id="tableResponsabilidades" class="table table-striped table-bordered table-sm nowrap table-hover shadow" style="width:100%">
                            <thead class="text-center">
                                <tr>
                                    <th class="bg-sofia">ID</th>
                                    <th class="bg-sofia">CÓDIGO</th>
                                    <th class="bg-sofia">DESCRIPCIÓN</th>
                                    <th class="bg-sofia">ACCIONES</th>
                                </tr>
                            </thead>
                            <tbody id="modificaRespEcon">
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <div class="accordion-item">
                <h2 class="accordion-header">
                    <button class="accordion-button collapsed bg-head-button border" type="button" data-bs-toggle="collapse" data-bs-target="#collapsemodOrden" aria-expanded="false" aria-controls="collapsemodOrden">
                        <span class="text-success"><i class="fas fa-id-badge me-2"></i>VIÑETA. Perfiles de Terceros.</span>
                    </button>
                </h2>
                <div id="collapsemodOrden" class="accordion-collapse collapse" data-bs-parent="#accTerceros">
                    <div class="accordion-body bg-wiev">
                        <table id="tablePerfilTercero" class="table table-striped table-bordered table-sm nowrap table-hover shadow" style="width:100%">
                            <thead class="text-center">
                                <tr>
                                    <th class="bg-sofia">ID</th>
                                    <th class="bg-sofia">DESCRIPCIÓN</th>
                                    <th class="bg-sofia">ACCIONES</th>
                                </tr>
                            </thead>
                            <tbody id="modificaPerfilTercero">
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
$plantilla->addScriptFile("{$host}/src/terceros/gestion/js/funcionesterceros.js?v=" . date("YmdHis"));
$modal = $plantilla->getModal('divModalForms', 'divTamModalForms', 'divForms');
$plantilla->addModal($modal);
echo $plantilla->render();
