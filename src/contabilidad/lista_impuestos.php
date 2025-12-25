<?php
session_start();
if (!isset($_SESSION['user'])) {
    header('Location: ../index.php');
    exit();
}

include '../../config/autoloader.php';

use Config\Clases\Plantilla;
use Src\Common\Php\Clases\Permisos;

$id_rol = $_SESSION['rol'];
$id_user = $_SESSION['id_user'];

$permisos = new Permisos();
$opciones = $permisos->PermisoOpciones($id_user);

$host = Plantilla::getHost();
$numeral = 1;

// Validar permisos de registro
$peReg = $permisos->PermisosUsuario($opciones, 5506, 2) || $id_rol == 1 ? 1 : 0;

$content = <<<HTML
<div class="card w-100">
    <div class="card-header bg-sofia text-white">
        <i class="fas fa-file-invoice fa-lg me-2"></i>
        <b>LISTA DE IMPUESTOS</b>
    </div>
    <div class="card-body p-2 bg-wiev">
        <input type="hidden" id="peReg" value="{$peReg}">
        
        <div class="accordion" id="accImpuestos">
            <div class="accordion-item">
                <h2 class="accordion-header">
                    <button class="accordion-button sombra bg-head-button border" type="button" data-bs-toggle="collapse" data-bs-target="#divTipoRte" aria-expanded="true" aria-controls="divTipoRte">
                        <span class="text-primary"><i class="fas fa-hand-holding-usd me-2 fa-lg"></i>VIÑETA. Tipo de Retención.</span>
                    </button>
                </h2>
                <div id="divTipoRte" class="accordion-collapse collapse">
                    <div class="accordion-body bg-wiev">
                        <table id="tableTipoRetencion" class="table table-striped table-bordered table-sm table-hover align-middle shadow" style="width:100%">
                            <thead class="text-center">
                                <tr>
                                    <th class="bg-sofia">ID</th>
                                    <th class="bg-sofia">TIPO RETENCIÓN</th>
                                    <th class="bg-sofia">RESPONSABLE</th>
                                    <th class="bg-sofia">ESTADO</th>
                                    <th class="bg-sofia">ACCIONES</th>
                                </tr>
                            </thead>
                            <tbody id="modificarTipoRetencion"></tbody>
                        </table>
                    </div>
                </div>
            </div>
            
            <div class="accordion-item">
                <h2 class="accordion-header">
                    <button class="accordion-button sombra collapsed bg-head-button border" type="button" data-bs-toggle="collapse" data-bs-target="#divRetenciones" aria-expanded="false" aria-controls="divRetenciones">
                        <span class="text-success"><i class="fas fa-money-bill-wave me-2 fa-lg"></i>VIÑETA. Retenciones.</span>
                    </button>
                </h2>
                <div id="divRetenciones" class="accordion-collapse collapse">
                    <div class="accordion-body bg-wiev">
                        <table id="tableRetenciones" class="table table-striped table-bordered table-sm table-hover align-middle shadow" style="width:100%">
                            <thead class="text-center">
                                <tr>
                                    <th class="bg-sofia">ID</th>
                                    <th class="bg-sofia">TIPO RETENCIÓN</th>
                                    <th class="bg-sofia">RETENCIÓN</th>
                                    <th class="bg-sofia">CUENTA</th>
                                    <th class="bg-sofia">ESTADO</th>
                                    <th class="bg-sofia">ACCIONES</th>
                                </tr>
                            </thead>
                            <tbody id="modificarRetencioness"></tbody>
                        </table>
                    </div>
                </div>
            </div>
            
            <div class="accordion-item">
                <h2 class="accordion-header">
                    <button class="accordion-button sombra collapsed bg-head-button border" type="button" data-bs-toggle="collapse" data-bs-target="#divRangoRet" aria-expanded="false" aria-controls="divRangoRet">
                        <span class="text-info"><i class="fas fa-stream me-2 fa-lg"></i>VIÑETA. Rango Retenciones.</span>
                    </button>
                </h2>
                <div id="divRangoRet" class="accordion-collapse collapse">
                    <div class="accordion-body bg-wiev">
                        <table id="tableRangoRet" class="table table-striped table-bordered table-sm table-hover align-middle shadow" style="width:100%">
                            <thead class="text-center">
                                <tr>
                                    <th class="bg-sofia">ID</th>
                                    <th class="bg-sofia">TIPO RETENCIÓN</th>
                                    <th class="bg-sofia">RETENCIÓN</th>
                                    <th class="bg-sofia">BASE</th>
                                    <th class="bg-sofia">TOPE</th>
                                    <th class="bg-sofia">TARIFA</th>
                                    <th class="bg-sofia">ESTADO</th>
                                    <th class="bg-sofia">ACCIONES</th>
                                </tr>
                            </thead>
                            <tbody id="modificarRangoRet"></tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
HTML;

// Reemplazar las VIÑETAS con numeración automática
$content = preg_replace_callback('/VIÑETA/', function () use (&$numeral) {
    return $numeral++;
}, $content);

$plantilla = new Plantilla($content, 2);
$plantilla->addCssFile("{$host}/assets/css/jquery-ui.css?v=" . date("YmdHis"));
$plantilla->addScriptFile("{$host}/assets/js/jquery-ui.js?v=" . date("YmdHis"));
$plantilla->addScriptFile("{$host}/src/contabilidad/js/funciones_retencion.js?v=" . date("YmdHis"));
$modal = $plantilla->getModal('divModalForms', 'divTamModalForms', 'divForms');
$plantilla->addModal($modal);
$modal = $plantilla->getModal('divModalReg', 'divTamModalReg', 'divFormsReg');
$plantilla->addModal($modal);
echo $plantilla->render();
