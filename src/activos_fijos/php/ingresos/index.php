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

$cmd = \Config\Clases\Conexion::getConexion();

$permisos = new Permisos();
$opciones = $permisos->PermisoOpciones($id_user);
$peReg = $permisos->PermisosUsuario($opciones, 5703, 2) || $id_rol == 1 ? 1 : 0;
$host = Plantilla::getHost();

// Construir opciones de combos
include '../common/cargar_combos.php';
ob_start();
terceros($cmd, '--Tercero--');
$opcionesTerceros = ob_get_clean();
ob_start();
tipo_ingreso($cmd, '--Tipo Ingreso--');
$opcionesTipoIngreso = ob_get_clean();
ob_start();
estados_movimientos('--Estado--');
$opcionesEstado = ob_get_clean();

$content = <<<HTML
<div class="card w-100">
    <div class="card-header bg-sofia text-white">
        <a class="btn btn-sm me-1 p-0" title="Regresar" href="{$host}/src/inicio.php" fff><i class="fas fa-arrow-left fa-lg"></i></a>
        <b>ORDENES DE INGRESO DE ACTIVOS FIJOS</b>
    </div>
    <div class="card-body p-2 bg-wiev">
        <input type="hidden" id="peReg" value="{$peReg}">
        
        <!-- Opciones de filtros -->
        <div class="row mb-3">
            <div class="col-md-1">
                <input type="text" class="filtro form-control form-control-sm bg-input" id="txt_iding_filtro" placeholder="Id. Ingreso">
            </div>
            <div class="col-md-1">
                <input type="text" class="filtro form-control form-control-sm bg-input" id="txt_numing_filtro" placeholder="No. Ingreso">
            </div>
            <div class="col-md-3">
                <div class="row">
                    <div class="col-md-6">
                        <input type="date" class="form-control form-control-sm bg-input" id="txt_fecini_filtro" name="txt_fecini_filtro" placeholder="Fecha Inicial">
                    </div>
                    <div class="col-md-6">
                        <input type="date" class="form-control form-control-sm bg-input" id="txt_fecfin_filtro" name="txt_fecfin_filtro" placeholder="Fecha Final">
                    </div>
                </div>
            </div>
            <div class="col-md-2">
                <select class="form-select form-select-sm bg-input" id="sl_tercero_filtro">
                    {$opcionesTerceros}
                </select>
            </div>
            <div class="col-md-2">
                <select class="form-select form-select-sm bg-input" id="sl_tiping_filtro">
                    {$opcionesTipoIngreso}
                </select>
            </div>
            <div class="col-md-1">
                <select class="form-select form-select-sm bg-input" id="sl_estado_filtro">
                    {$opcionesEstado}
                </select>
            </div>
            <div class="col-md-1">
                <button type="button" id="btn_buscar_filtro" class="btn btn-outline-success btn-sm" title="Filtrar">
                    <span class="fas fa-search fa-lg" aria-hidden="true"></span>
                </button>
                <button type="button" id="btn_imprime_filtro" class="btn btn-outline-success btn-sm" title="Imprimir">
                    <span class="fas fa-print" aria-hidden="true"></span>                                       
                </button>
            </div>                                
        </div>

        <!-- Tabla de datos -->
        <div class="table-responsive shadow p-2">
            <table id="tb_ingresos" class="table table-striped table-bordered table-sm nowrap table-hover shadow w-100" style="font-size:80%">
                <thead class="text-center">
                    <tr>
                        <th class="bg-sofia">Id</th>
                        <th class="bg-sofia">No. Ingreso</th>
                        <th class="bg-sofia">Fecha Ingreso</th>
                        <th class="bg-sofia">Hora Ingreso</th>
                        <th class="bg-sofia">No. Fact./Acta/Rem.</th>
                        <th class="bg-sofia">Fecha Fact./Acta/Rem.</th>
                        <th class="bg-sofia">Detalle</th>
                        <th class="bg-sofia">Tercero</th>
                        <th class="bg-sofia">Tipo Ingreso</th>
                        <th class="bg-sofia">Sede</th>
                        <th class="bg-sofia">Vr. Total</th>
                        <th class="bg-sofia">Estado</th>
                        <th class="bg-sofia">Estado</th>
                        <th class="bg-sofia">Acciones</th>
                    </tr>
                </thead>
            </table>
        </div>
        
        <!-- Leyenda de estados -->
        <div class="mt-3">
            <table class="table table-bordered table-sm col-md-3 w-25" style="font-size:85%">
                <tr class="text-center">
                    <td style="background-color:yellow"><b>Pendiente</b></td>
                    <td><b>Cerrado</b></td>
                    <td style="background-color:gray; color:white"><b>Anulado</b></td>
                </tr>
            </table>
        </div>
    </div>
</div>
HTML;

$plantilla = new Plantilla($content, 2);
$plantilla->addCssFile("{$host}/assets/css/jquery-ui.css?v=" . date("YmdHis"));
$plantilla->addScriptFile("{$host}/assets/js/jquery-ui.js?v=" . date("YmdHis"));
$plantilla->addScriptFile("{$host}/src/activos_fijos/js/ingresos/ingresos.js?v=" . date("YmdHis"));
$plantilla->addScriptFile("{$host}/src/activos_fijos/js/common/common.js?v=" . date("YmdHis"));
$modal = $plantilla->getModal('divModalForms', 'divTamModalForms', 'divForms');
$plantilla->addModal($modal);
$modal = $plantilla->getModal('divModalReg', 'divTamModalReg', 'divFormsReg');
$plantilla->addModal($modal);
$modal = $plantilla->getModal('divModalImp', 'divTamModalImp', 'divImp');
$plantilla->addModal($modal);
$modal = $plantilla->getModal('divModalBus', 'divTamModalBus', 'divFormsBus');
$plantilla->addModal($modal);
echo $plantilla->render();
