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
use Src\Common\Php\Clases\Terceros;

$host = Plantilla::getHost();
$numeral = 1;
$permisos = new Permisos();

$id_rol = $_SESSION['rol'];
$id_user = $_SESSION['id_user'];
$opciones = $permisos->PermisoOpciones($id_user);
$peReg =  $permisos->PermisosUsuario($opciones, 5302, 0) || $id_rol == 1 ? 1 : 0;

$estados = Combos::getEstadoAdq();
$modalidades = Combos::getModalidad();

$datos = (new Terceros())->getTercero($_POST['id_ter']);

$content = <<<HTML

<div class="card w-100">
    <div class="card-header bg-sofia text-white">
        <button class="btn btn-xs me-1 p-0" title="Regresar" onclick="window.history.back();"><i class="fas fa-arrow-left"></i></button>
        <b>DETALLES DE TERCEROS</b>
    </div>
    <div class="card-body p-2 bg-wiev">
        <input type="hidden" id="peReg" value="{$peReg}">
        <input type="hidden" id="id_tercero" value="{$_POST['id_ter']}">
        <div class="accordion" id="accDetalles">
            <div class="accordion-item">
                <h2 class="accordion-header">
                    <button class="accordion-button sombra collapsed bg-head-button border" type="button" data-bs-toggle="collapse" data-bs-target="#collapsemodData" aria-expanded="false" aria-controls="collapsemodData">
                        <span class="text-primary"><i class="far fa-address-book me-2"></i>VIÑETA. Datos Personales.</span>
                    </button>
                </h2>
                <div id="collapsemodData" class="accordion-collapse show" data-bs-parent="#accDetalles">
                    <div class="accordion-body bg-wiev">
                        <div class=" px-3 shadow rounded">
                            <div class="card-body">
                                <div class="row mb-0 border border-bottom-0 rounded-top">
                                    <div class="col-md-3 border-end">
                                        <span class="text-muted small">IDENTIFICACIÓN</span><br>
                                        <span class="fw-bold">{$datos['nit_tercero']}</span>
                                    </div>
                                    <div class="col-md-5 border-end">
                                        <span class="text-muted small">NOMBRE COMPLETO</span><br>
                                        <span class="fw-bold">{$datos['nom_tercero']}</span>
                                    </div>
                                    <div class="col-md-2 border-end">
                                        <span class="text-muted small">DEPARTAMENTO</span><br>
                                        <span class="fw-bold">{$datos['nom_departamento']}</span>
                                    </div>
                                    <div class="col-md-2">
                                        <span class="text-muted small">MUNICIPIO</span><br>
                                        <span class="fw-bold">{$datos['nom_municipio']}</span>
                                    </div>
                                </div>
                                <div class="row mb-0 border rounded-bottom">
                                    <div class="col-md-4 border-end">
                                        <span class="text-muted small">DIRECCIÓN</span><br>
                                        <span class="fw-bold">{$datos['dir_tercero']}</span>
                                    </div>
                                    <div class="col-md-4 border-end">
                                        <span class="text-muted small">CORREO</span><br>
                                        <span class="fw-bold"></span>
                                    </div>
                                    <div class="col-md-4 border-end">
                                        <span class="text-muted small">CONTACTO</span><br>
                                        <span class="fw-bold">{$datos['tel_tercero']}</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="accordion-item">
                <h2 class="accordion-header">
                    <button class="accordion-button sombra collapsed bg-head-button border" type="button" data-bs-toggle="collapse" data-bs-target="#collapsemodContrata" aria-expanded="false" aria-controls="collapsemodContrata">
                        <span class="text-success"><i class="fas fa-clipboard-list me-2"></i>VIÑETA. Responsabilidades Económicas.</span>
                    </button>
                </h2>
                <div id="collapsemodContrata" class="accordion-collapse collapse" data-bs-parent="#accDetalles">
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
                    <button class="accordion-button sombra collapsed bg-head-button border" type="button" data-bs-toggle="collapse" data-bs-target="#collapsemodActividad" aria-expanded="false" aria-controls="collapsemodActividad">
                        <span class="text-info"><i class="fas fa-donate me-2"></i>VIÑETA. Actividades Económicas.</span>
                    </button>
                </h2>
                <div id="collapsemodActividad" class="accordion-collapse collapse" data-bs-parent="#accDetalles">
                    <div class="accordion-body bg-wiev">
                        <table id="tableActvEcon" class="table table-striped table-bordered table-sm nowrap table-hover shadow" style="width:100%">
                            <thead class="text-center">
                                <tr>
                                    <th class="bg-sofia" >Código CIIU</th>
                                    <th class="bg-sofia" >Descripción</th>
                                    <th class="bg-sofia" >Fecha Inicio</th>
                                    <th class="bg-sofia" >Acciones</th>
                                </tr>
                            </thead>
                            <tbody id="modificarActvEcons">
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <div class="accordion-item">
                <h2 class="accordion-header">
                    <button class="accordion-button sombra collapsed bg-head-button border" type="button" data-bs-toggle="collapse" data-bs-target="#collapsemodDocs" aria-expanded="false" aria-controls="collapsemodDocs">
                        <span class="text-muted"><i class="fas fa-copy me-2"></i>VIÑETA. Documentos.</span>
                    </button>
                </h2>
                <div id="collapsemodDocs" class="accordion-collapse collapse" data-bs-parent="#accDetalles">
                    <div class="accordion-body bg-wiev">
                        <table id="tableDocumento" class="table table-striped table-bordered table-sm nowrap table-hover shadow" style="width:100%">
                            <thead class="text-center">
                                <tr>
                                    <th class="bg-sofia">Tipo Doc.</th>
                                    <th class="bg-sofia">Fecha Inicia</th>
                                    <th class="bg-sofia">Vigencia</th>
                                    <th class="bg-sofia">Estado</th>
                                    <th class="bg-sofia">Documento</th>
                                </tr>
                            </thead>
                            <tbody id="modificarDocs">
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
$plantilla->addCssFile("{$host}/assets/css/jquery-ui.css?v=" . date("YmdHis"));
$plantilla->addScriptFile("{$host}/assets/js/jquery-ui.js?v=" . date("YmdHis"));
$plantilla->addScriptFile("{$host}/src/usuarios/login/js/sha.js?v=" . date("YmdHis"));
$plantilla->addScriptFile("{$host}/src/terceros/gestion/js/funcionesterceros.js?v=" . date("YmdHis"));
$plantilla->addScriptFile("{$host}/src/terceros/js/historialtercero/historialtercero_reg.js?v=" . date("YmdHis"));
$plantilla->addScriptFile("{$host}/src/terceros/js/historialtercero/historialtercero.js?v=" . date("YmdHis"));
$modal = $plantilla->getModal('divModalForms', 'divTamModalForms', 'divForms');
$plantilla->addModal($modal);
$modal = $plantilla->getModal('divModalReg', 'divTamModalReg', 'divFormsReg');
$plantilla->addModal($modal);
echo $plantilla->render();
