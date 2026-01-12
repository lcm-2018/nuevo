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
$peReg = $permisos->PermisosUsuario($opciones, 5509, 2) || $id_rol == 1 ? 1 : 0;

$content = <<<HTML
<div class="card w-100">
    <div class="card-header bg-sofia text-white">
        <a class="btn btn-sm me-1 p-0" title="Regresar" href="{$host}/src/inicio.php"><i class="fas fa-arrow-left fa-lg"></i></a>
        <b>SUBGRUPOS ARTÍCULOS</b>
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

        <!-- Tabla de subgrupos -->
        <div class="table-responsive shadow">
            <table id="tb_subgrupos" class="table table-striped table-bordered table-sm table-hover align-middle w-100" style="font-size:80%">
                <thead class="text-center">
                    <tr>
                        <th rowspan="2" class="bg-sofia align-middle">ID</th>
                        <th rowspan="2" class="bg-sofia align-middle">CÓDIGO</th>
                        <th rowspan="2" class="bg-sofia align-middle">NOMBRE</th>
                        <th colspan="4" class="bg-sofia">CUENTAS CONTABLES VIGENTES</th>
                        <th rowspan="2" class="bg-sofia align-middle">GRUPO</th>
                        <th rowspan="2" class="bg-sofia align-middle">ACTIVO FIJO<br>MENOR CUANTÍA</th>
                        <th rowspan="2" class="bg-sofia align-middle">USO<br>ASISTENCIAL</th>
                        <th rowspan="2" class="bg-sofia align-middle">LOTE X DEFECTO</th>
                        <th rowspan="2" class="bg-sofia align-middle">ESTADO</th>
                        <th rowspan="2" class="bg-sofia align-middle">ACCIONES</th>
                    </tr>
                    <tr>
                        <th class="bg-sofia">INVENTARIO</th>
                        <th class="bg-sofia">ACTIVO FIJO</th>
                        <th class="bg-sofia">DEPRECIACIÓN ACF</th>
                        <th class="bg-sofia">GASTO DEPREC. ACF</th>
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
$plantilla->addScriptFile("{$host}/src/contabilidad/js/subgrupos/subgrupos.js?v=" . date("YmdHis"));
$modal = $plantilla->getModal('divModalForms', 'divTamModalForms', 'divForms');
$plantilla->addModal($modal);
$modal = $plantilla->getModal('divModalReg', 'divTamModalReg', 'divFormsReg');
$plantilla->addModal($modal);
$modal = $plantilla->getModal('divModalImp', 'divTamModalImp', 'divImp');
$plantilla->addModal($modal);
$modal = $plantilla->getModal('divModalBus', 'divTamModalBus', 'divFormsBus');
$plantilla->addModal($modal);
echo $plantilla->render();
