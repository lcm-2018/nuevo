<?php
session_start();

/* Activar si desea verificar Errores desde el Servidor
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
*/

if (!isset($_SESSION['user'])) {
    header("Location: ../../../index.php");
    exit();
}

include '../../../../config/autoloader.php';

use Config\Clases\Plantilla;
use Src\Common\Php\Clases\Permisos;

$id_rol = $_SESSION['rol'];
$id_user = $_SESSION['id_user'];

$permisos = new Permisos();
$opciones = $permisos->PermisoOpciones($id_user);

$host = Plantilla::getHost();

// Validar permisos de registro
$peReg = $permisos->PermisosUsuario($opciones, 5512, 2) || $id_rol == 1 ? 1 : 0;

$content = <<<HTML
<div class="card w-100">
    <div class="card-header bg-sofia text-white">
        <a class="btn btn-sm me-1 p-0" title="Regresar" href="{$host}/src/inicio.php"><i class="fas fa-arrow-left fa-lg"></i></a>
        <b>TIPOS DE ÓRDENES DE INGRESO</b>
    </div>
    <div class="card-body p-2 bg-wiev">
        <input type="hidden" id="peReg" value="{$peReg}">
        
        <!-- Opciones de filtros -->
        <div class="row mb-3">
            <div class="col-md-2">
                <input type="text" class="filtro form-control form-control-sm bg-input" id="txt_nombre_filtro" placeholder="Nombre">
            </div>
            <div class="col-md-auto">
                <button type="button" id="btn_buscar_filtro" class="btn btn-outline-success btn-sm" title="Filtrar">
                    <i class="fas fa-search fa-lg"></i>
                </button>
                <button type="button" id="btn_imprime_filtro" class="btn btn-outline-success btn-sm" title="Imprimir">
                    <i class="fas fa-print fa-lg"></i>
                </button>
            </div>
        </div>

        <!-- Tabla de tipos de orden de ingreso -->
        <div class="table-responsive shadow">
            <table id="tb_tipos_orden_ingreso" class="table table-striped table-bordered table-sm table-hover align-middle w-100" style="font-size:80%">
                <thead class="text-center">
                    <tr>
                        <th rowspan="2" class="bg-sofia align-middle">ID</th>
                        <th rowspan="2" class="bg-sofia align-middle">NOMBRE</th>
                        <th rowspan="2" class="bg-sofia align-middle">INT/EXT</th>
                        <th rowspan="2" class="bg-sofia align-middle">CON ORDEN COMPRA</th>
                        <th rowspan="2" class="bg-sofia align-middle">ES FIANZA</th>
                        <th colspan="3" class="bg-sofia">MÓDULOS</th>
                        <th rowspan="2" class="bg-sofia align-middle">ACCIONES</th>
                    </tr>
                    <tr>
                        <th class="bg-sofia">ALMACÉN</th>
                        <th class="bg-sofia">FARMACIA</th>
                        <th class="bg-sofia">ACTIVOS FIJOS</th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>
    </div>
</div>
HTML;

$plantilla = new Plantilla($content, 2);
$plantilla->addCssFile("{$host}/assets/css/jquery-ui.css?v=" . date("YmdHis"));
$plantilla->addScriptFile("{$host}/assets/js/jquery-ui.js?v=" . date("YmdHis"));
$plantilla->addScriptFile("{$host}/src/contabilidad/js/informes_bancos/common.js?v=" . date("YmdHis"));
$plantilla->addScriptFile("{$host}/src/contabilidad/js/tipos_orden_ingreso/tipos_orden_ingreso.js?v=" . date("YmdHis"));
$modal = $plantilla->getModal('divModalForms', 'divTamModalForms', 'divForms');
$plantilla->addModal($modal);
$modal = $plantilla->getModal('divModalReg', 'divTamModalReg', 'divFormsReg');
$plantilla->addModal($modal);
$modal = $plantilla->getModal('divModalImp', 'divTamModalImp', 'divImp');
$plantilla->addModal($modal);
echo $plantilla->render();
