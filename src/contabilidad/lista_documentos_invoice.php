
<?php
session_start();
if (!isset($_SESSION['user'])) {
    header('Location: ../../index.php');
    exit();
}
include '../../config/autoloader.php';

use Config\Clases\Plantilla;
use Src\Common\Php\Clases\Permisos;

$id_rol = $_SESSION['rol'];
$id_user = $_SESSION['id_user'];

$permisos = new Permisos();
$opciones = $permisos->PermisoOpciones($id_user);
$peReg = $permisos->PermisosUsuario($opciones, 5511, 2) || $id_rol == 1 ? 1 : 0;
$host = Plantilla::getHost();

$tipo_doc = isset($_POST['cod_doc']) ? $_POST['cod_doc'] : '';

// Consulta tipo de documento
try {
    $cmd = \Config\Clases\Conexion::getConexion();
    $sql = "SELECT `cod`, `nombre` FROM `ctb_fuente` WHERE `contab` = 2 ORDER BY `nombre`";
    $rs = $cmd->query($sql);
    $docsFuente = $rs->fetchAll();
} catch (PDOException $e) {
    echo $e->getCode() == 2002 ? 'Sin Conexión a Mysql (Error: 2002)' : 'Error: ' . $e->getCode();
}

// Obtener id_doc_fuente
$id_tipo_doc = 0;
if ($tipo_doc != '') {
    try {
        $sql = "SELECT `id_doc_fuente` FROM `ctb_fuente` WHERE `cod` = '$tipo_doc'";
        $rs = $cmd->query($sql);
        $tipo_doc_fuente = $rs->fetch();
        $id_tipo_doc = !empty($tipo_doc_fuente) ? $tipo_doc_fuente['id_doc_fuente'] : 0;
    } catch (PDOException $e) {
        echo $e->getCode() == 2002 ? 'Sin Conexión a Mysql (Error: 2002)' : 'Error: ' . $e->getCode();
    }
}

// Construir opciones del select de documentos
$optionsDocFuente = '<option value="">-- Seleccionar --</option>';
foreach ($docsFuente as $mov) {
    $selected = $mov['cod'] == $tipo_doc ? 'selected' : '';
    $optionsDocFuente .= '<option value="' . $mov['cod'] . '" ' . $selected . '>' . $mov['nombre'] . '</option>';
}

// Botones adicionales según tipo de documento
$botonesAdicionales = '';
if ($tipo_doc == 'FELE' && $_SESSION['pto'] == '1') {
    $botonesAdicionales .= <<<HTML
    <button type="button" class="btn btn-primary btn-sm me-1" onclick="CargaObligaRad(2)">
        Ver Listado <span class="badge bg-light text-dark"></span>
    </button>
HTML;
}

// Determinar si mostrar tabla
$tablaHTML = '';
if ($tipo_doc != '') {
    $tablaHTML = <<<HTML
    <div class="table-responsive shadow p-2">
        <table id="tableMvtCtbInvoice" class="table table-striped table-bordered table-sm table-hover shadow w-100">
            <thead class="text-center">
                <tr>
                    <th class="bg-sofia">Numero</th>
                    <th class="bg-sofia">RAD</th>
                    <th class="bg-sofia">Fecha</th>
                    <th class="bg-sofia">Tercero</th>
                    <th class="bg-sofia">Valor</th>
                    <th class="bg-sofia">Acciones</th>
                </tr>
            </thead>
            <tbody id="modificarMvtCtbInvoice">
            </tbody>
        </table>
    </div>
HTML;
}

$content = <<<HTML
<div class="card w-100">
    <div class="card-header bg-sofia text-white">
        <button class="btn btn-sm me-1 p-0" title="Regresar" onclick="window.history.back();"><i class="fas fa-arrow-left fa-lg"></i></button>
        <b>REGISTRO DE MOVIMIENTOS CONTABLES FACTURACIÓN</b>
    </div>
    <div class="card-body p-2 bg-wiev">
        <input type="hidden" id="peReg" value="{$peReg}">
        <input type="hidden" id="id_ctb_doc" value="{$id_tipo_doc}">
        
        <div class="d-flex justify-content-center align-items-center gap-2 flex-wrap">
            <form action="{$_SERVER['PHP_SELF']}" method="POST">
                <select class="form-select form-select-sm bg-input" id="cod_ctb_doc" name="cod_doc" onchange="cambiaListadoCtbInvoice(this.value)">
                    {$optionsDocFuente}
                </select>
            </form>
            <div class="d-flex gap-1">
                {$botonesAdicionales}
                <button type="button" class="btn btn-success btn-sm" title="Imprimir por Lotes" id="btnImpLotes">
                    <i class="fas fa-print fa-lg"></i>
                </button>
            </div>
        </div>

        <!-- Opciones de filtros -->
        <div class="row mb-2 py-3">
            <div class="col-md-1">
                <input type="text" class="filtro form-control form-control-sm bg-input" id="txt_idmanu_filtro" placeholder="Id. Manu">
            </div>
            <div class="col-md-1">
                <input type="text" class="filtro form-control form-control-sm bg-input" id="txt_rad_filtro" placeholder="RAD">
            </div>
            <div class="col-md-3">
                <div class="row mb-2">
                    <div class="col-md-6">
                        <input type="date" class="form-control form-control-sm bg-input" id="txt_fecini_filtro" name="txt_fecini_filtro" placeholder="Fecha Inicial">
                    </div>
                    <div class="col-md-6">
                        <input type="date" class="form-control form-control-sm bg-input" id="txt_fecfin_filtro" name="txt_fecfin_filtro" placeholder="Fecha Final">
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <input type="text" class="filtro form-control form-control-sm bg-input" id="txt_tercero_filtro" placeholder="Tercero">
            </div>
            <div class="col-md-2">
                <select class="form-select form-select-sm bg-input" id="sl_estado_filtro">
                    <option value="0">--Estado--</option>
                    <option value="1">Abierto</option>
                    <option value="2">Cerrado</option>
                    <option value="3">Anulado</option>
                </select>
            </div>
            <div class="col-md-1">
                <label class="form-check-label small">
                    <input class="form-check-input" type="checkbox" id="verAnulados"> Anulados
                </label>
            </div>
            <div class="col-md-1">
                <button type="button" id="btn_buscar_filtro_Invoice" class="btn btn-outline-success btn-sm w-100" title="Filtrar">
                    <span class="fas fa-search fa-lg" aria-hidden="true"></span>
                </button>
            </div>
        </div>

        {$tablaHTML}
    </div>
</div>
HTML;

$plantilla = new Plantilla($content, 2);
$plantilla->addScriptFile("{$host}/src/contabilidad/js/funcioncontabilidad.js?v=" . date("YmdHis"));
$modal = $plantilla->getModal('divModalForms', 'divTamModalForms', 'divForms');
$plantilla->addModal($modal);
$modal = $plantilla->getModal('divModalReg', 'divTamModalReg', 'divFormsReg');
$plantilla->addModal($modal);
$modal = $plantilla->getModal('divModalImp', 'divTamModalImp', 'divImp');
$plantilla->addModal($modal);
echo $plantilla->render();
