<?php
session_start();
if (!isset($_SESSION['user'])) {
    header('Location: ../../../../index.php');
    exit();
}
include '../../../../config/autoloader.php';

use Config\Clases\Plantilla;
use Src\Common\Php\Clases\Permisos;

$id_rol = $_SESSION['rol'];
$id_user = $_SESSION['id_user'];

$permisos = new Permisos();
$opciones = $permisos->PermisoOpciones($id_user);
$peReg = $permisos->PermisosUsuario($opciones, 3002, 2) || $id_rol == 1 ? 1 : 0;
$host = Plantilla::getHost();

// Lista de registros en combos
include '../../common/php/cargar_combos.php';
$comboEstados = estados_registros('--Estado--');

$content = <<<HTML
<div class="card w-100">
    <div class="card-header bg-sofia text-white">
        <a class="btn btn-sm me-1 p-0" title="Regresar" href="{$host}/src/inicio.php"><i class="fas fa-arrow-left fa-lg"></i></a>
        <b>ENTIDADES - BASES DE DATOS</b>
    </div>
    <div class="card-body p-2 bg-wiev">
        <input type="hidden" id="peReg" value="{$peReg}">
        <!-- Filtros reducidos -->
        <div class="row mb-3">
            <div class="col-md-3 col-lg-2">
                <input type="text" class="filtro form-control form-control-sm bg-input" id="txt_nombre_filtro" placeholder="Nombre">
            </div>
            <div class="col-md-2 col-lg-2">
                <select class="form-select form-select-sm bg-input" id="sl_estado_filtro">{$comboEstados}</select>
            </div>
            <div class="col-md-2">
                <button type="button" id="btn_buscar_filtro" class="btn btn-outline-success btn-sm" title="Filtrar"><span class="fas fa-search"></span></button>
                <button type="button" id="btn_imprime_filtro" class="btn btn-outline-success btn-sm" title="Imprimir"><span class="fas fa-print"></span></button>
            </div>
        </div>

        <div class="table-responsive shadow p-2">
            <table id="tb_bdatos" class="table table-striped table-bordered table-sm nowrap table-hover shadow w-100" style="font-size:80%">
                <thead class="text-center">
                    <tr>
                        <th class="bg-sofia">Id</th>
                        <th class="bg-sofia">Nombre</th>
                        <th class="bg-sofia">Descripción</th>
                        <th class="bg-sofia">IP Servidor</th>
                        <th class="bg-sofia">Nombre BD</th>
                        <th class="bg-sofia">Puerto BD</th>
                        <th class="bg-sofia">Estado</th>
                        <th class="bg-sofia">Acciones</th>
                    </tr>
                </thead>
            </table>
        </div>
    </div>
</div>
HTML;

$plantilla = new Plantilla($content, 2);
$plantilla->addCssFile("{$host}/assets/css/jquery-ui.css?v=" . date("YmdHis"));
$plantilla->addScriptFile("{$host}/assets/js/jquery-ui.js?v=" . date("YmdHis"));
$plantilla->addScriptFile("{$host}/src/analytics/conf_bdatos/js/bdatos.js?v=" . date("YmdHis"));
$plantilla->addScriptFile("{$host}/src/analytics/common/js/common.js?v=" . date("YmdHis"));
$modal = $plantilla->getModal('divModalForms', 'divTamModalForms', 'divForms');
$plantilla->addModal($modal);
$modal = $plantilla->getModal('divModalReg', 'divTamModalReg', 'divFormsReg');
$plantilla->addModal($modal);
$modal = $plantilla->getModal('divModalImp', 'divTamModalImp', 'divImp');
$plantilla->addModal($modal);
echo $plantilla->render();
