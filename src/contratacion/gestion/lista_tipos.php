<?php
session_start();
if (!isset($_SESSION['user'])) {
    header("Location: ../../../index.php");
    exit();
}

include_once '../../../config/autoloader.php';

use Config\Clases\Plantilla;
use Src\Common\Php\Clases\Permisos;

$host = Plantilla::getHost();
$numeral = 1;
$permisos = new Permisos();

$id_rol = $_SESSION['rol'];
$id_user = $_SESSION['id_user'];
$opciones = $permisos->PermisoOpciones($id_user);
$peReg =  $permisos->PermisosUsuario($opciones, 5301, 0) || $id_rol == 1 ? 1 : 0;
$configuracion = '';
if ($id_rol == 1) {
    $configuracion =
        <<<HTML
            <div class="accordion-item">
                <h2 class="accordion-header">
                    <button class="accordion-button collapsed bg-head-button border" type="button" data-bs-toggle="collapse" data-bs-target="#collapsemodConfig" aria-expanded="false" aria-controls="collapsemodConfig">
                        <span class="text-primary"><i class="fas fa-file-contract me-2 fa-lg"></i>VIÑETA. Configuración.</span>
                    </button>
                </h2>
                <div id="collapsemodConfig" class="accordion-collapse collapse" data-bs-parent="#accConfig">
                    <div class="text-center">
                        <div class="row justify-content-center py-3">
                            <div class="col-md-4">
                                <input type="text" class="form-control form-control-sm bg-input" id="BuscaUsuario" placeholder="Nombre del usuario">
                                <input type="hidden" id="id_user" name="id_user" value="0">
                            </div>
                        </div>
                    </div>
                     <div class="accordion p-3 pt-0" id="accConfig">
                        <div class="accordion-item">
                            <h2 class="accordion-header">
                                <button class="accordion-button collapsed bg-head-button border" type="button" data-bs-toggle="collapse" data-bs-target="#collapsemodArea" aria-expanded="false" aria-controls="collapsemodArea">
                                    <span class="text-primary"><i class="fas fa-file-contract me-2 fa-lg"></i>EACII. Area contratación por usuario.</span>
                                </button>
                            </h2>
                            <div id="collapsemodArea" class="accordion-collapse collapse" data-bs-parent="#accConfig">
                                <div class="accordion-body bg-wiev">
                                    <table id="tableAreaUser" class="table table-striped table-bordered table-sm nowrap table-hover shadow" style="width:100%">
                                        <thead>
                                            <tr>
                                                <th class="bg-sofia">ID</th>
                                                <th class="bg-sofia">Area</th>
                                                <th class="bg-sofia">Estado</th>
                                                <th class="bg-sofia">Acción</th>
                                            </tr>
                                        </thead>
                                        <tbody id="modificarAreasUser">
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                        <div class="accordion-item">
                            <h2 class="accordion-header">
                                <button class="accordion-button collapsed bg-head-button border" type="button" data-bs-toggle="collapse" data-bs-target="#collapsemodUser" aria-expanded="false" aria-controls="collapsemodUser">
                                    <span class="text-success"><i class="fas fa-file-contract me-2 fa-lg"></i>EACII. Relación de Usuarios.</span>
                                </button>
                            </h2>
                            <div id="collapsemodUser" class="accordion-collapse collapse" data-bs-parent="#accConfig">
                                <div class="accordion-body bg-wiev">
                                    <table id="tableRelacion" class="table table-striped table-bordered table-sm nowrap table-hover shadow" style="width:100%">
                                        <thead>
                                            <tr>
                                                <th class="bg-sofia">ID</th>
                                                <th class="bg-sofia">Documento</th>
                                                <th class="bg-sofia">Nombre</th>
                                                <th class="bg-sofia">Acción</th>
                                            </tr>
                                        </thead>
                                        <tbody id="modificarRelaciones">
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                     </div>
                </div>
            </div>
        HTML;
}

$content = <<<HTML

<div class="card w-100">
    <div class="card-header bg-sofia text-white">
        <button class="btn btn-xs me-1 p-0" title="Regresar" onclick="window.history.back();"><i class="fas fa-arrow-left fa-lg"></i></button>
        <b>CONFIGURACIÓN DE CONTRATACIÓN</b>
    </div>
    <div class="card-body p-2 bg-wiev">
        <input type="hidden" id="peReg" value="{$peReg}">
        <div class="accordion" id="accContrata">
            {$configuracion}
            <div class="accordion-item">
                <h2 class="accordion-header">
                    <button class="accordion-button collapsed bg-head-button border" type="button" data-bs-toggle="collapse" data-bs-target="#collapsemodContrata" aria-expanded="false" aria-controls="collapsemodContrata">
                        <span class="text-success"><i class="fas fa-file-contract me-2 fa-lg"></i>VIÑETA. Modalidad de contratación.</span>
                    </button>
                </h2>
                <div id="collapsemodContrata" class="accordion-collapse collapse" data-bs-parent="#accContrata">
                    <div class="accordion-body bg-wiev">
                        <table id="tableModalidad" class="table table-striped table-bordered table-sm nowrap table-hover shadow" style="width:100%">
                            <thead>
                                <tr>
                                    <th class="bg-sofia">Modalidad</th>
                                    <th class="bg-sofia">Acción</th>
                                </tr>
                            </thead>
                            <tbody id="modificarModalidades">
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <!-- parte-->
            <div class="accordion-item">
                <h2 class="accordion-header">
                    <button class="accordion-button collapsed bg-head-button border" type="button" data-bs-toggle="collapse" data-bs-target="#collapseTipoSerBien" aria-expanded="false" aria-controls="collapseTipoSerBien">
                        <span class="text-info"><i class="fas fa-mail-bulk fa-lg me-2"></i>VIÑETA. Tipo de bien o servicio</span>
                    </button>
                </h2>
                <div id="collapseTipoSerBien" class="accordion-collapse collapse" data-bs-parent="#accContrata">
                    <div class="accordion-body bg-wiev">
                        <table id="tableTipoBnSv" class="table table-striped table-bordered table-sm nowrap table-hover shadow" style="width:100%">
                            <thead>
                                <tr>
                                    <th class="bg-sofia">Tipo de compra</th>
                                    <th class="bg-sofia">Tipo de Bien y/o servicio</th>
                                    <th class="bg-sofia">Acción</th>
                                </tr>
                            </thead>
                            <tbody id="modificarTipoBnSvs">
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <!--parte-->
            <div class="accordion-item">
                <h2 class="accordion-header">
                    <button class="accordion-button collapsed bg-head-button border" type="button" data-bs-toggle="collapse" data-bs-target="#collapeseBnSv" aria-expanded="false" aria-controls="collapeseBnSv">
                        <span class="text-muted"><i class="fas fa-cart-arrow-down fa-lg me-2"></i>VIÑETA. Bienes y servicios</span>
                    </button>
                </h2>
                <div id="collapeseBnSv" class="accordion-collapse collapse" data-bs-parent="#accContrata">
                    <div class="accordion-body bg-wiev">
                        <table id="tableBnSv" class="table table-striped table-bordered table-sm nowrap table-hover shadow" style="width:100%">
                            <thead>
                                <tr>
                                    <th class="bg-sofia">Tipo de compra</th>
                                    <th class="bg-sofia">Tipo de Bien y/o servicio</th>
                                    <th class="bg-sofia">Bien y/o servicio</th>
                                </tr>
                            </thead>
                            <tbody id="modificarBnSvs">
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <!--parte-->
            <div class="accordion-item">
                <h2 class="accordion-header">
                    <button class="accordion-button collapsed bg-head-button border" type="button" data-bs-toggle="collapse" data-bs-target="#collapeseFCtt" aria-expanded="false" aria-controls="collapeseFCtt">
                        <span class="text-warning"><i class="fas fa-file-word fa-lg me-2"></i>VIÑETA. Formatos de contratación</span>
                    </button>
                </h2>
                <div id="collapeseFCtt" class="accordion-collapse collapse" data-bs-parent="#accContrata">
                    <div class="accordion-body bg-wiev">
                        <div class="text-end">
                            <button type="button" class="btn btn-outline-info mb-1" id="btnDownloadVarsCtt" title="Descargar variables de contratación">
                                <i class="fas fa-download me-2"></i>Variables
                            </button>
                        </div>
                        <table id="tableFormCtt" class="table table-striped table-bordered table-sm nowrap table-hover shadow" style="width:100%">
                            <thead>
                                <tr>
                                    <th class="bg-sofia">ID</th>
                                    <th class="bg-sofia">Tipo de Formato</th>
                                    <th class="bg-sofia">Tipo de Bien/Servicio</th>
                                    <th class="bg-sofia">Acción</th>
                                </tr>
                            </thead>
                            <tbody id="modificaFormCtt">
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <!--parte-->
            <div class="accordion-item">
                <h2 class="accordion-header">
                    <button class="accordion-button collapsed bg-head-button border" type="button" data-bs-toggle="collapse" data-bs-target="#collapeseMsOp" aria-expanded="false" aria-controls="collapeseMsOp">
                        <span class="text-secondary"><i class="fas fa-bars fa-lg me-2"></i>VIÑETA. Más opciones</span>
                    </button>
                </h2>
                <div id="collapeseMsOp" class="accordion-collapse collapse" data-bs-parent="#accContrata">
                    <div class="accordion-body bg-wiev">
                        <div class="row py-2 g-2">
                            <div class="col-auto">
                                <div class="input-group">
                                    <span class="input-group-text">
                                        <i class="fas fa-file-excel me-2 text-success"></i>
                                        Homologación de servicios
                                    </span>
                                    <button type="button" class="btn btn-outline-primary" id="btnExcelHomolgBnSv">
                                        <span class="fas fa-download"></span>
                                    </button>
                                    <button type="button" class="btn btn-outline-warning subirHomologacion" text="1">
                                        <span class="fas fa-upload"></span>
                                    </button>
                                </div>
                            </div>
                            <div class="col-auto">
                                <div class="input-group">
                                    <span class="input-group-text">
                                        <i class="fas fa-file-excel me-2 text-success"></i>
                                        Homologación escala de honorarios
                                    </span>
                                    <button type="button" class="btn btn-outline-primary" id="btnExcelHomolgEscHonor">
                                        <span class="fas fa-download"></span>
                                    </button>
                                    <button type="button" class="btn btn-outline-warning subirHomologacion" text="2">
                                        <span class="fas fa-upload"></span>
                                    </button>
                                </div>
                            </div>
                        </div>

                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
HTML;

$lines = explode("\n", $content);
$result = [];
$mainCounter = 0;
$subCounter = 0;

foreach ($lines as $line) {
    if (strpos($line, 'VIÑETA') !== false) {
        $mainCounter++;
        $subCounter = 0;
        $line = trim(str_replace('VIÑETA', $mainCounter, $line));
    } else if (strpos($line, 'EACII') !== false) {
        $subCounter++;
        $line = trim(str_replace('EACII', $mainCounter . '.' . $subCounter, $line));
    } else {
        $line = trim($line);
    }
    $result[] = $line;
}

$content = implode("\n", $result);

$plantilla = new Plantilla($content, 2);
$plantilla->addScriptFile("{$host}/src/contratacion/gestion/js/funciones_contrataciones.js?v=" . date("YmdHis"));
$modal = $plantilla->getModal('divModalForms', 'divTamModalForms', 'divForms');
$plantilla->addModal($modal);
echo $plantilla->render();
