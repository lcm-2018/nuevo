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
$peReg =  $permisos->PermisosUsuario($opciones, 5202, 2) || $id_rol == 1 ? 1 : 0;

$estados = Combos::getEstadoAdq();
$modalidades = Combos::getModalidad();

$datos = (new Terceros())->getTercero($_POST['id_ter']);

$content = <<<HTML

<div class="card w-100">
    <div class="card-header bg-sofia text-white">
        <a class="btn btn-xs me-1 p-0" title="Regresar" href="listterceros.php"><i class="fas fa-arrow-left fa-lg"></i></a>
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
                    <div class="accordion-body bg-body-tertiary">
                        <div class="card border-0 shadow-lg rounded-4 overflow-hidden">
                            <div class="card-body p-0">
                                <!-- Header con avatar e info principal -->
                                <div class="bg-success bg-gradient text-white p-2">
                                    <div class="row align-items-center">
                                        <div class="col-auto">
                                            <div class="rounded-circle bg-white text-success d-flex align-items-center justify-content-center shadow" style="width: 80px; height: 80px; font-size: 2rem; font-weight: 700;">
                                                {$datos['nom_tercero'][0]}
                                            </div>
                                        </div>
                                        <div class="col">
                                            <h4 class="mb-1 fw-bold">{$datos['nom_tercero']}</h4>
                                            <div class="d-flex flex-wrap gap-2 align-items-center">
                                                <span class="badge bg-light text-success rounded-pill px-3 py-2">
                                                    <i class="fas fa-id-card me-1"></i>{$datos['nit_tercero']}
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <!-- Info detallada -->
                                <div class="p-4">
                                    <div class="row g-4">
                                        <!-- Ubicación -->
                                        <div class="col-md-6">
                                            <div class="card h-100 border-0 bg-light rounded-3">
                                                <div class="card-body">
                                                    <h6 class="text-uppercase text-muted small mb-3">
                                                        <i class="fas fa-map-marker-alt text-danger me-2"></i>Ubicación
                                                    </h6>
                                                    <div class="row g-3">
                                                        <div class="col-6">
                                                            <span class="text-muted small d-block">Departamento</span>
                                                            <span class="fw-semibold">{$datos['nom_departamento']}</span>
                                                        </div>
                                                        <div class="col-6">
                                                            <span class="text-muted small d-block">Municipio</span>
                                                            <span class="fw-semibold">{$datos['nom_municipio']}</span>
                                                        </div>
                                                        <div class="col-12">
                                                            <span class="text-muted small d-block">Dirección</span>
                                                            <span class="fw-semibold">{$datos['dir_tercero']}</span>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <!-- Contacto -->
                                        <div class="col-md-6">
                                            <div class="card h-100 border-0 bg-light rounded-3">
                                                <div class="card-body">
                                                    <h6 class="text-uppercase text-muted small mb-3">
                                                        <i class="fas fa-address-book text-info me-2"></i>Contacto
                                                    </h6>
                                                    <div class="row g-3">
                                                        <div class="col-12">
                                                            <div class="d-flex align-items-center">
                                                                <div class="rounded-circle bg-success bg-opacity-10 text-success d-flex align-items-center justify-content-center me-3" style="width: 40px; height: 40px;">
                                                                    <i class="fas fa-envelope"></i>
                                                                </div>
                                                                <div>
                                                                    <span class="text-muted small d-block">Correo electrónico</span>
                                                                    <span class="fw-semibold">-</span>
                                                                </div>
                                                            </div>
                                                        </div>
                                                        <div class="col-12">
                                                            <div class="d-flex align-items-center">
                                                                <div class="rounded-circle bg-primary bg-opacity-10 text-primary d-flex align-items-center justify-content-center me-3" style="width: 40px; height: 40px;">
                                                                    <i class="fas fa-phone"></i>
                                                                </div>
                                                                <div>
                                                                    <span class="text-muted small d-block">Teléfono</span>
                                                                    <span class="fw-semibold">{$datos['tel_tercero']}</span>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
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
