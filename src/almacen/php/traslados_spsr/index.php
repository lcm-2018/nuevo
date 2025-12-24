<?php
session_start();
if (!isset($_SESSION['user'])) {
    header('Location: ../../../index.php');
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
$peReg = $permisos->PermisosUsuario($opciones, 5019, 2) || $id_rol == 1 ? 1 : 0;
$host = Plantilla::getHost();

ob_start();
include '../common/cargar_combos.php';
$opcionesSedeUsuario = sedes_usuario($cmd, '--Sede Origen--');
$opcionesSedeDestino = sedes($cmd, '--Sede Destino--');
$opcionesEstado = estados_movimientos('--Estado--');
ob_end_clean();

$content = <<<HTML
<div class="card w-100">
    <div class="card-header bg-sofia text-white">
        <button class="btn btn-sm me-1 p-0" title="Regresar" onclick="window.history.back();"><i class="fas fa-arrow-left fa-lg"></i></button>
        <b>TRASLADOS SPSR</b>
    </div>
    <div class="card-body p-2 bg-wiev">
        <input type="hidden" id="peReg" value="{$peReg}">
        
        <div class="row mb-3">
            <div class="col-md-2">
                <select class="form-select form-select-sm bg-input" id="sl_sedori_filtro">{$opcionesSedeUsuario}</select>
            </div>
            <div class="col-md-2">
                <select class="form-select form-select-sm bg-input" id="sl_bodori_filtro"></select>
            </div>
            <div class="col-md-1">
                <input type="text" class="filtro form-control form-control-sm bg-input bg-input" id="txt_idtra_filtro" placeholder="Id. Traslado">
            </div>
            <div class="col-md-1">
                <input type="text" class="filtro form-control form-control-sm bg-input bg-input" id="txt_numtra_filtro" placeholder="No. Traslado">
            </div>
            <div class="col-md-2">
                <input type="date" class="form-control form-control-sm bg-input bg-input" id="txt_fecini_filtro" placeholder="Fecha Inicial">
            </div>
            <div class="col-md-2">
                <input type="date" class="form-control form-control-sm bg-input bg-input" id="txt_fecfin_filtro" placeholder="Fecha Final">
            </div>
            <div class="col-md-1">
                <select class="form-select form-select-sm bg-input" id="sl_estado_filtro">{$opcionesEstado}</select>
            </div>
            <div class="col-md-1">
                <button type="button" id="btn_buscar_filtro" class="btn btn-outline-success btn-sm" title="Filtrar">
                    <span class="fas fa-search "></span>
                </button>
                <button type="button" id="btn_imprime_filtro" class="btn btn-outline-success btn-sm" title="Imprimir">
                    <span class="fas fa-print"></span>
                </button>
            </div>
        </div>

        <div class="table-responsive shadow p-2">
            <table id="tb_traslados" class="table table-striped table-bordered table-sm nowrap table-hover shadow w-100" style="font-size:80%">
                <thead class="text-center">
                    <tr>
                        <th rowspan="2" class="bg-sofia">Id</th>
                        <th rowspan="2" class="bg-sofia">No. Traslado</th>
                        <th rowspan="2" class="bg-sofia">Fecha Traslado</th>
                        <th rowspan="2" class="bg-sofia">Hora Traslado</th>
                        <th rowspan="2" class="bg-sofia">Detalle</th>
                        <th colspan="2" class="bg-sofia">Unidad Origen</th>
                        <th colspan="2" class="bg-sofia">Unidad Destino</th>
                        <th rowspan="2" class="bg-sofia">Id.Estado</th>
                        <th rowspan="2" class="bg-sofia">Estado</th>
                        <th rowspan="2" class="bg-sofia">Acciones</th>
                    </tr>
                    <tr>
                        <th class="bg-sofia">Sede</th>
                        <th class="bg-sofia">Bodega</th>
                        <th class="bg-sofia">Sede</th>
                        <th class="bg-sofia">Bodega</th>
                    </tr>
                </thead>
            </table>
        </div>
        
        <table class="table-bordered table-sm col-md-2 mt-2">
            <tr>
                <td style="background-color:yellow">Pendiente</td>
                <td>Cerrado</td>
                <td style="background-color:gray">Anulado</td>
            </tr>
        </table>
    </div>
</div>
HTML;

$plantilla = new Plantilla($content, 2);
$plantilla->addCssFile("{$host}/assets/css/jquery-ui.css?v=" . date("YmdHis"));
$plantilla->addScriptFile("{$host}/assets/js/jquery-ui.js?v=" . date("YmdHis"));
$plantilla->addScriptFile("{$host}/src/almacen/js/traslados_spsr/traslados_spsr.js?v=" . date("YmdHis"));
$modal = $plantilla->getModal('divModalForms', 'divTamModalForms', 'divForms');
$plantilla->addModal($modal);
$modal = $plantilla->getModal('divModalReg', 'divTamModalReg', 'divFormsReg');
$plantilla->addModal($modal);
$modal = $plantilla->getModal('divModalImp', 'divTamModalImp', 'divImp');
$plantilla->addModal($modal);
echo $plantilla->render();
