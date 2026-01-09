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
$peReg = 0; // Sin permisos de registro en consultas
$host = Plantilla::getHost();

// Construir opciones de combos usando output buffering
include '../common/cargar_combos.php';

ob_start();
sedes_usuario($cmd, '--Sede--');
$opcionesSedeUsuario = ob_get_clean();

ob_start();
subgrupo_articulo($cmd, '--Subgrupo--');
$opcionesSubgrupo = ob_get_clean();

ob_start();
estados_sino('--Uso Asistencial--');
$opcionesAsistencial = ob_get_clean();

ob_start();
con_existencia('--Existencia--');
$opcionesExistencia = ob_get_clean();

ob_start();
lotes_vencidos('--Lotes--');
$opcionesLotesVencidos = ob_get_clean();

ob_start();
tipo_reporte_exi_lote('--TIPO DE REPORTE--');
$opcionesReporte = ob_get_clean();

$content = <<<HTML
<div class="card w-100">
    <div class="card-header bg-sofia text-white">
        <a class="btn btn-sm me-1 p-0" title="Regresar" href="{$host}/src/inicio.php" fff><i class="fas fa-arrow-left fa-lg"></i></a>
        <b>EXISTENCIA DETALLADA</b>
    </div>
    <div class="card-body p-2 bg-wiev">
        <input type="hidden" id="peReg" value="{$peReg}">
        
        <!-- Opciones de filtros -->
        <div class="row mb-2">
            <div class="col-md-2">
                <select class="filtro form-select form-select-sm bg-input" id="sl_sede_filtro">
                    {$opcionesSedeUsuario}
                </select>
            </div>
            <div class="col-md-2">
                <select class="filtro form-select form-select-sm bg-input" id="sl_bodega_filtro">
                </select>
            </div>
            <div class="col-md-3">
                <div class="row mb-2">
                    <div class="col-md-6">
                        <input type="text" class="filtro form-control form-control-sm bg-input" id="txt_codigo_filtro" placeholder="Codigo">
                    </div>
                    <div class="col-md-6">
                        <input type="text" class="filtro form-control form-control-sm bg-input" id="txt_nombre_filtro" placeholder="Nombre">
                    </div>
                </div>
            </div>
            <div class="col-md-2">
                <select class="filtro form-select form-select-sm bg-input" id="sl_subgrupo_filtro">
                    {$opcionesSubgrupo}
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
        <div class="row mb-3">
            <div class="col-md-5">
                <div class="row mb-2">
                    <div class="col-md-4">
                        <select class="filtro form-select form-select-sm bg-input" id="sl_tipoasis_filtro">
                            {$opcionesAsistencial}
                        </select>
                    </div>
                    <div class="col-md-4">
                        <select class="filtro form-select form-select-sm bg-input" id="sl_conexi_filtro">
                            {$opcionesExistencia}
                        </select>
                    </div>
                    <div class="col-md-4">
                        <select class="filtro form-select form-select-sm bg-input" id="sl_lotven_filtro">
                            {$opcionesLotesVencidos}
                        </select>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="row mb-2">
                    <div class="col-md-7">
                        <div class="form-check form-check-inline">
                            <input class="filtro form-check-input" type="checkbox" id="chk_artact_filtro" checked>
                            <label class="form-check-label small" for="chk_artact_filtro">Articulos Activos</label>
                        </div>
                    </div>
                    <div class="col-md-5">
                        <div class="form-check form-check-inline">
                            <input class="filtro form-check-input" type="checkbox" id="chk_lotact_filtro" checked>
                            <label class="form-check-label small" for="chk_lotact_filtro">Lotes Activos</label>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <select class="filtro form-select form-select-sm bg-input text-primary" id="sl_tipo_reporte">
                    {$opcionesReporte}
                </select>
            </div>
            <div class="col-md-1">
                <input type="text" class="filtro form-control form-control-sm bg-input" id="txt_diasven_filtro" style="display: none;" value="15" placeholder="Días Vence.">
            </div>
        </div>

        <!-- Tabla de datos -->
        <div class="table-responsive shadow p-2">
            <table id="tb_lotes" class="table table-striped table-bordered table-sm nowrap table-hover shadow align-middle w-100" style="font-size:80%">
                <thead class="text-center">
                    <tr>
                        <th class="bg-sofia">Id</th>
                        <th class="bg-sofia">Sede</th>
                        <th class="bg-sofia">Bodega</th>
                        <th class="bg-sofia">Código</th>
                        <th class="bg-sofia">Nombre</th>
                        <th class="bg-sofia">Subgrupo</th>
                        <th class="bg-sofia">Lote</th>
                        <th class="bg-sofia">Existencia</th>
                        <th class="bg-sofia">Vr. Promedio</th>
                        <th class="bg-sofia">Vr. Total</th>
                        <th class="bg-sofia">Fecha Vencimiento</th>
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
$plantilla->addScriptFile("{$host}/src/almacen/js/existencia_lote/existencia_lote.js?v=" . date("YmdHis"));
$plantilla->addScriptFile("{$host}/src/almacen/js/common/common.js?v=" . date("YmdHis"));
$modal = $plantilla->getModal('divModalForms', 'divTamModalForms', 'divForms');
$plantilla->addModal($modal);
$modal = $plantilla->getModal('divModalReg', 'divTamModalReg', 'divFormsReg');
$plantilla->addModal($modal);
$modal = $plantilla->getModal('divModalImp', 'divTamModalImp', 'divImp');
$plantilla->addModal($modal);
echo $plantilla->render();
