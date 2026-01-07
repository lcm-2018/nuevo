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
$peReg =  $permisos->PermisosUsuario($opciones, 5202, 0) || $id_rol == 1 ? 1 : 0;

$estados = Combos::getEstadoAdq();
$modalidades = Combos::getModalidad();

$dash =
    <<<HTML
            <div class="col-md-1">
                <a type="button" id="btn_iniciar_dashboard" class="btn btn-outline-success btn-sm" title="Dashboard">
                    <span class="fas fa-chart-line fa-lg" aria-hidden="true"></span>
                </a>
            </div>
            <div class="col-md-1">
                <a type="button" id="btn_dashboard" class="btn btn-outline-primary btn-sm" title="Dashboard">
                    <span class="fas fa-chart-line fa-lg" aria-hidden="true"></span>
                </a>
            </div>
            <div class="col-md-1">
                <a type="button" id="btn_detener_dashboard" class="btn btn-outline-danger btn-sm" title="Dashboard">
                    <span class="fas fa-chart-line fa-lg" aria-hidden="true"></span>
                </a>
            </div>
    HTML;
// cuando se requiera el los botones para dashboard eliminar  la variable $dash vacia
$dash = '';

$content = <<<HTML

<div class="card w-100">
    <div class="card-header bg-sofia text-white">
        <button class="btn btn-xs me-1 p-0" title="Regresar" onclick="window.history.back();"><i class="fas fa-arrow-left"></i></button>
        <b>LISTA DE TERCEROS</b>
    </div>
    <div class="card-body p-2 bg-wiev">
        <input type="hidden" id="peReg" value="{$peReg}">
        <div class="text-end">
            <button class="btn btn-outline-warning btn-sm" id="btnActualizaRepositorio" title="Actualizar repositorio de terceros">
                <span class="me-2"></span><i class="fas fa-user-edit fa-lg"></i>
            </button>
            <button class="btn btn-outline-success btn-sm" id="btnReporteTerceros" title="Descargar Informe de Terceros">
                <i class="fas fa-file-excel fa-lg"></i>
            </button>
        </div>
        <div class="row pb-2">
            <div class="col-md-2">
                <input type="text" class="filtro form-control form-control-sm" id="txt_ccnit_filtro" placeholder="Doc / Nit">
            </div>
            <div class="col-md-3">
                <input type="text" class="filtro form-control form-control-sm" id="txt_tercero_filtro" placeholder="Tercero">
            </div>
            <div class="col-md-1">
                <a type="button" id="btn_buscar_filtro" class="btn btn-outline-success btn-sm" title="Filtrar">
                    <span class="fas fa-search fa-lg" aria-hidden="true"></span>
                </a>
            </div>
            {$dash}
        </div>
        <table id="tableTerceros" class="table table-striped table-bordered table-sm nowrap table-hover shadow w-100 align-middle">
            <thead>
                <tr>
                    <th class="bg-sofia">No. Doc.</th>
                    <th class="bg-sofia">Nombre / Razón Social</th>
                    <th class="bg-sofia">Tipo</th>
                    <th class="bg-sofia">Ciudad</th>
                    <th class="bg-sofia">Dirección</th>
                    <th class="bg-sofia">Teléfono</th>
                    <th class="bg-sofia">Correo</th>
                    <th class="bg-sofia">Estado</th>
                    <th class="bg-sofia">Acción</th>
                </tr>
            </thead>
            <tbody id="modificarTerceros">
            </tbody>
        </table>
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
