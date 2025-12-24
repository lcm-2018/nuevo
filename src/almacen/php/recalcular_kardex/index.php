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
$peReg = $permisos->PermisosUsuario($opciones, 5012, 2) || $id_rol == 1 ? 1 : 0;
$host = Plantilla::getHost();

// Construir opciones de combos usando output buffering
include '../common/cargar_combos.php';
include '../common/funciones_generales.php';

ob_start();
sedes_usuario($cmd, '--Sede--');
$opcionesSedeUsuario = ob_get_clean();

$fecha = add_fecha('', 2, -6);

$content = <<<HTML
<div class="card w-100">
    <div class="card-header bg-sofia text-white">
        <button class="btn btn-sm me-1 p-0" title="Regresar" onclick="window.history.back();"><i class="fas fa-arrow-left fa-lg"></i></button>
        <b>RECALCULAR KARDEX DE LOTES DE ARTICULOS</b>
    </div>
    <div class="card-body p-2 bg-wiev">
        <input type="hidden" id="peReg" value="{$peReg}">
        
        <!-- Opciones de filtros -->
        <div class="row mb-2">
            <div class="col-md-5">
                <div class="row mb-2">
                    <div class="col-md-6">
                        <select class="filtro form-select form-select-sm bg-input" id="sl_sede_filtro">
                            {$opcionesSedeUsuario}
                        </select>
                    </div>
                    <div class="col-md-5">
                        <select class="filtro form-select form-select-sm bg-input" id="sl_bodega_filtro">
                        </select>
                    </div>
                </div>
                <div class="row mb-2">
                    <div class="col-md-3">
                        <div class="form-check form-check-inline">
                            <input class="form-check-input chk_aplica" type="radio" name="rdo_opcion" id="rdo_opcion1" value="O" checked="checked">
                            <label class="form-check-label small" for="rdo_opcion1">Datos Articulo</label>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <input type="text" class="filtro form-control form-control-sm bg-input" id="txt_codigo_filtro" placeholder="Codigo">
                    </div>
                    <div class="col-md-4">
                        <input type="text" class="filtro form-control form-control-sm bg-input" id="txt_nombre_filtro" placeholder="Nombre">
                    </div>
                </div>
                <div class="row mb-2">
                    <div class="col-md-7">
                        <label class="form-control-sm">Fecha Inicial de proceso Recalcular Kardex</label>
                    </div>
                    <div class="col-md-4">
                        <input type="date" class="filtro form-control form-control-sm bg-input" id="txt_fecha_filtro" name="txt_fecha_filtro" placeholder="Fecha Inicial" value="{$fecha}">
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="row mb-2">
                    <div class="col-md-6">
                        <div class="form-check form-check-inline">
                            <input class="form-check-input chk_aplica" type="radio" name="rdo_opcion" id="rdo_opcion2" value="I">
                            <label class="form-check-label small" for="rdo_opcion2">Id. Orden Ingreso</label>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <input type="text" class="filtro form-control form-control-sm bg-input" id="txt_id_ing_filtro" placeholder="Id. Ingreso" disabled="disabled">
                    </div>
                </div>
                <div class="row mb-2">
                    <div class="col-md-6">
                        <div class="form-check form-check-inline">
                            <input class="form-check-input chk_aplica" type="radio" name="rdo_opcion" id="rdo_opcion3" value="E">
                            <label class="form-check-label small" for="rdo_opcion3">Id. Orden Egreso</label>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <input type="text" class="filtro form-control form-control-sm bg-input" id="txt_id_egr_filtro" placeholder="Id. Egreso" disabled="disabled">
                    </div>
                </div>
                <div class="row mb-2">
                    <div class="col-md-6">
                        <div class="form-check form-check-inline">
                            <input class="form-check-input chk_aplica" type="radio" name="rdo_opcion" id="rdo_opcion4" value="T">
                            <label class="form-check-label small" for="rdo_opcion4">Id. Traslado</label>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <input type="text" class="filtro form-control form-control-sm bg-input" id="txt_id_tra_filtro" placeholder="Id. Traslado" disabled="disabled">
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="row mb-2">
                    <div class="col-md-6">
                        <div class="form-check form-check-inline">
                            <input class="form-check-input chk_aplica" type="radio" name="rdo_opcion" id="rdo_opcion5" value="ER">
                            <label class="form-check-label small" for="rdo_opcion5">Id. Traslado SPSR Egreso</label>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <input type="text" class="filtro form-control form-control-sm bg-input" id="txt_id_egr_r_filtro" placeholder="Id. Traslado" disabled="disabled">
                    </div>
                </div>
            </div>
            <div class="col-md-1">
                <div class="row mb-2">
                    <button type="button" id="btn_buscar_filtro" class="btn btn-outline-success btn-sm" title="Filtrar">
                        <span class="fas fa-search fa-lg" aria-hidden="true"></span>
                    </button>
                </div>
                <div class="row mb-2">&nbsp;</div>
                <div class="row mb-2">
                    <button type="button" id="btn_recalcular_filtro" class="btn btn-outline-success btn-sm" title="Recalcular">
                        <span class="fas fa-cog fa-lg" aria-hidden="true"></span>
                        <label class="form-check-label small">Recalcular Lotes</label>
                    </button>
                </div>
            </div>
        </div>

        <!-- Tabla de datos -->
        <div class="table-responsive shadow p-2">
            <form id="frm_lotes">
                <table id="tb_lotes" class="table table-striped table-bordered table-sm nowrap table-hover shadow align-middle w-100" style="font-size:80%">
                    <thead class="text-center">
                        <tr>
                            <th rowspan="2" class="bg-sofia">
                                <label for="chk_sel_filtro">Sel.</label>
                                <input type="checkbox" id="chk_sel_filtro">
                            </th>
                            <th colspan="3" class="bg-sofia">Articulo</th>
                            <th colspan="5" class="bg-sofia">Lote</th>
                            <th colspan="3" class="bg-sofia">Existencia Total</th>
                        </tr>
                        <tr>
                            <th class="bg-sofia">Id.</th>
                            <th class="bg-sofia">Código</th>
                            <th class="bg-sofia">Descripción</th>
                            <th class="bg-sofia">Sede</th>
                            <th class="bg-sofia">Bodega</th>
                            <th class="bg-sofia">Id.</th>
                            <th class="bg-sofia">Lote</th>
                            <th class="bg-sofia">Existencia</th>
                            <th class="bg-sofia">Código Articulo</th>
                            <th class="bg-sofia">Existencia</th>
                            <th class="bg-sofia">Vr. Promedio</th>
                        </tr>
                    </thead>
                </table>
            </form>
        </div>
    </div>
</div>
HTML;

$plantilla = new Plantilla($content, 2);
$plantilla->addCssFile("{$host}/assets/css/jquery-ui.css?v=" . date("YmdHis"));
$plantilla->addScriptFile("{$host}/assets/js/jquery-ui.js?v=" . date("YmdHis"));
$plantilla->addScriptFile("{$host}/src/almacen/js/recalcular_kardex/recalcular_kardex.js?v=" . date("YmdHis"));
$plantilla->addScriptFile("{$host}/src/almacen/js/common/common.js?v=" . date("YmdHis"));
$modal = $plantilla->getModal('divModalForms', 'divTamModalForms', 'divForms');
$plantilla->addModal($modal);
$modal = $plantilla->getModal('divModalReg', 'divTamModalReg', 'divFormsReg');
$plantilla->addModal($modal);
$modal = $plantilla->getModal('divModalImp', 'divTamModalImp', 'divImp');
$plantilla->addModal($modal);
echo $plantilla->render();
