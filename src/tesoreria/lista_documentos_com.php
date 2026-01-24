
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

$host = Plantilla::getHost();

$tipo_doc = isset($_POST['id_tipo_doc']) ? $_POST['id_tipo_doc'] : '0';
$tipo = isset($_POST['var']) ? $_POST['var'] : '';
unset($_SESSION['id_doc']);

// Consulta tipo de documento
try {
    $cmd = \Config\Clases\Conexion::getConexion();
    $sql = "SELECT `id_doc_fuente`, `cod`, `nombre` FROM `ctb_fuente` WHERE `tesor` = :tipo";
    $rs = $cmd->prepare($sql);
    $rs->execute([':tipo' => $tipo]);
    $docsFuente = $rs->fetchAll();
    $rs->closeCursor();
    unset($rs);
} catch (PDOException $e) {
    echo $e->getCode() == 2002 ? 'Sin Conexión a Mysql (Error: 2002)' : 'Error: ' . $e->getCode();
}

// Consulta nóminas pendientes de tesorería
try {
    $sql = "SELECT
                `nom_nomina_pto_ctb_tes`.`id`
                , `nom_nomina_pto_ctb_tes`.`id_nomina`
                , `nom_nomina_pto_ctb_tes`.`tipo`
                , `nom_nomina_pto_ctb_tes`.`cdp`
                , `nom_nomina_pto_ctb_tes`.`crp`
                , `nom_nominas`.`descripcion`
                , `nom_nominas`.`mes`
                , `nom_nominas`.`vigencia`
                , `nom_nominas`.`estado`
            FROM
                `nom_nomina_pto_ctb_tes`
                INNER JOIN `nom_nominas` 
                    ON (`nom_nomina_pto_ctb_tes`.`id_nomina` = `nom_nominas`.`id_nomina`)
            WHERE (`nom_nominas`.`estado` = 4) AND `nom_nomina_pto_ctb_tes`.`tipo` <> 'PL'
            UNION 
            SELECT
                `nom_nomina_pto_ctb_tes`.`id`
                , `nom_nomina_pto_ctb_tes`.`id_nomina`
                , `nom_nomina_pto_ctb_tes`.`tipo`
                , `nom_nomina_pto_ctb_tes`.`cdp`
                , `nom_nomina_pto_ctb_tes`.`crp`
                , `nom_nominas`.`descripcion`
                , `nom_nominas`.`mes`
                , `nom_nominas`.`vigencia`
                , `nom_nominas`.`planilla` AS `estado`
            FROM
                `nom_nomina_pto_ctb_tes`
                INNER JOIN `nom_nominas` 
                    ON (`nom_nomina_pto_ctb_tes`.`id_nomina` = `nom_nominas`.`id_nomina`)
            WHERE (`nom_nominas`.`planilla` = 4 AND `nom_nomina_pto_ctb_tes`.`tipo` = 'PL')";
    $rs = $cmd->query($sql);
    $nominas = $rs->fetchAll();
    $rs->closeCursor();
    unset($rs);
    $total = count($nominas);
} catch (PDOException $e) {
    echo $e->getCode() == 2002 ? 'Sin Conexión a Mysql (Error: 2002)' : 'Error: ' . $e->getCode();
}

// Construir opciones del select de documentos
$optionsDocFuente = '<option value="0">-- Seleccionar --</option>';
foreach ($docsFuente as $df) {
    $selected = $df['id_doc_fuente'] == $tipo_doc ? 'selected' : '';
    $optionsDocFuente .= '<option value="' . $df['id_doc_fuente'] . '" ' . $selected . '>' . $df['nombre'] . '</option>';
}

// Verificar permisos de registro
$peReg = 0;
if (($permisos->PermisosUsuario($opciones, 5601, 2) && $tipo == 1) ||
    ($permisos->PermisosUsuario($opciones, 5602, 2) && $tipo == 2) ||
    ($permisos->PermisosUsuario($opciones, 5603, 2) && $tipo == 3) ||
    ($permisos->PermisosUsuario($opciones, 5604, 2) && $tipo == 4) ||
    $id_rol == 1
) {
    $peReg = 1;
}

// Botones adicionales según tipo de documento
$botonesAdicionales = '';
if ($tipo_doc == '4') {
    $botonesAdicionales .= <<<HTML
    <button type="button" class="btn btn-primary btn-sm me-1" onclick="CargaObligaPago(this)">
        Ver Listado
    </button>
    <button type="button" class="btn btn-outline-secondary btn-sm me-1" onclick="cargaListaReferenciaPago(2)">
        Referencias
    </button>
    <input type="hidden" id="total" value="{$total}">
    <button type="button" class="btn btn-outline-success btn-sm me-1" onclick="CegresoNomina(this)">
        Nómina <span class="badge bg-light text-dark" id="totalCausa">{$total}</span>
    </button>
HTML;
}

if ($tipo_doc == '11') {
    $botonesAdicionales .= <<<HTML
    <button type="button" class="btn btn-secondary btn-sm me-1" onclick="CargaArqueoCaja(2)">
        Ver Listado
    </button>
HTML;
}

if ($tipo_doc == '6') {
    $botonesAdicionales .= <<<HTML
    <button type="button" class="btn btn-primary btn-sm me-1" onclick="CargaListaRads()">
        Ver Listado
    </button>
HTML;
}

// Botón de consecutivos
$botonConsecutivos = '';
if ($tipo_doc > '0') {
    $botonConsecutivos = <<<HTML
    <a onclick="cargarConsecutivos({$tipo_doc})" href="javascript:void(0);" title="Consultar Consecutivos">
        <span class="fas fa-info-circle text-info"></span>
    </a>
HTML;
}

// Determinar si mostrar tabla
$tablaHTML = '';
if ($tipo_doc != '0') {
    if ($tipo_doc != '13') {
        $tablaHTML = <<<HTML
    <div class="table-responsive shadow p-2">
        <table id="tableMvtoTesoreriaPagos" class="table table-striped table-bordered table-sm table-hover shadow w-100">
            <thead class="text-center">
                <tr>
                    <th class="bg-sofia">Numero</th>
                    <th class="bg-sofia">Causación</th>
                    <th class="bg-sofia">Fecha</th>
                    <th class="bg-sofia">CC/Nit</th>
                    <th class="bg-sofia">Tercero</th>
                    <th class="bg-sofia">Referencia</th>
                    <th class="bg-sofia">Valor</th>
                    <th class="bg-sofia">Acciones</th>
                </tr>
            </thead>
            <tbody id="modificartableMvtoTesoreriaPagos">
            </tbody>
        </table>
    </div>
HTML;
    } else {
        $tablaHTML = <<<HTML
    <div class="table-responsive shadow p-2">
        <table id="tableMvtoTesoreriaPagos" class="table table-striped table-bordered table-sm table-hover shadow w-100">
            <thead class="text-center">
                <tr>
                    <th class="bg-sofia">Acto</th>
                    <th class="bg-sofia">Num. Acto</th>
                    <th class="bg-sofia">Nombre Caja</th>
                    <th class="bg-sofia">Inicia</th>
                    <th class="bg-sofia">Acto</th>
                    <th class="bg-sofia">Total</th>
                    <th class="bg-sofia">Minimo.</th>
                    <th class="bg-sofia">Póliza</th>
                    <th class="bg-sofia">%</th>
                    <th class="bg-sofia">Estado</th>
                    <th class="bg-sofia">Acciones</th>
                </tr>
            </thead>
            <tbody id="modificartableMvtoTesoreriaPagos">
            </tbody>
        </table>
    </div>
HTML;
    }
}

$content = <<<HTML
<div class="card w-100">
    <div class="card-header bg-sofia text-white">
        <a class="btn btn-sm me-1 p-0" title="Regresar" href="{$host}/src/inicio.php"><i class="fas fa-arrow-left fa-lg"></i></a>
        <b>REGISTRO DE MOVIMIENTOS DE TESORERIA</b>
    </div>
    <div class="card-body p-2 bg-wiev">
        <input type="hidden" id="peReg" value="{$peReg}">
        <input type="hidden" id="id_ctb_tipo" value="{$tipo_doc}">
        <input type="hidden" name="var_tip" id="var_tip" value="{$tipo}">
        
        <div class="d-flex justify-content-center align-items-center gap-2 flex-wrap">
            <form action="{$_SERVER['PHP_SELF']}" method="POST">
                <select class="form-select form-select-sm bg-input" id="slcDocFuente" name="id_tipo_doc" onchange="cambiaListadoTesoreria(this.value,'{$tipo}')">
                    {$optionsDocFuente}
                </select>
                <input type="hidden" name="var" value="{$tipo}">
            </form>
            <div class="d-flex gap-1">
                {$botonesAdicionales}
                <button type="button" class="btn btn-success btn-sm" title="Imprimir por Lotes" id="btnImpLotesTes">
                    <i class="fas fa-print fa-lg"></i>
                </button>
                {$botonConsecutivos}
            </div>
        </div>

        <!-- Opciones de filtros -->
        <div class="row mb-2 py-3">
            <div class="col-md-1">
                <div class="input-group">
                    <div class="input-group-text">
                        <input class="form-check-input mt-0" type="checkbox" value="" title="Marcar para filtrar por valor exacto" id="txt_bandera_filtro">
                    </div>
                    <input type="text" class="filtro form-control form-control-sm bg-input" id="txt_idmanu_filtro" placeholder="Id. Manu">
                </div>
            </div>
            <div class="col-md-3">
                <div class="row mb-2">
                    <div class="col-md-6">
                        <input type="date" class="form-control form-control-sm bg-input bg-input" id="txt_fecini_filtro" name="txt_fecini_filtro" placeholder="Fecha Inicial">
                    </div>
                    <div class="col-md-6">
                        <input type="date" class="form-control form-control-sm bg-input bg-input" id="txt_fecfin_filtro" name="txt_fecfin_filtro" placeholder="Fecha Final">
                    </div>
                </div>
            </div>
            <div class="col-md-2">
                <input type="text" class="filtro form-control form-control-sm bg-input bg-input" id="txt_ccnit_filtro" placeholder="CC / Nit">
            </div>
            <div class="col-md-3">
                <input type="text" class="filtro form-control form-control-sm bg-input bg-input" id="txt_tercero_filtro" placeholder="Tercero">
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
                <button type="button" id="btn_buscar_filtro" class="btn btn-outline-success btn-sm w-100" title="Filtrar">
                    <span class="fas fa-search fa-lg" aria-hidden="true"></span>
                </button>
            </div>
        </div>

        {$tablaHTML}
    </div>
</div>
HTML;

$plantilla = new Plantilla($content, 2);
$plantilla->addCssFile("{$host}/assets/css/jquery-ui.css?v=" . date("YmdHis"));
$plantilla->addScriptFile("{$host}/assets/js/jquery-ui.js?v=" . date("YmdHis"));
$plantilla->addScriptFile("{$host}/src/tesoreria/js/informes/common.js?v=" . date("YmdHis"));
$plantilla->addScriptFile("{$host}/src/tesoreria/js/funciontesoreria.js?v=" . date("YmdHis"));
$modal = $plantilla->getModal('divModalForms', 'divTamModalForms', 'divForms');
$plantilla->addModal($modal);
$modal = $plantilla->getModal('divModalReg', 'divTamModalReg', 'divFormsReg');
$plantilla->addModal($modal);
$modal = $plantilla->getModal('divModalImp', 'divTamModalImp', 'divImp');
$plantilla->addModal($modal);
$modal = $plantilla->getModal('divModalAux', 'divTamModalAux', 'divFormsAux');
$plantilla->addModal($modal);
echo $plantilla->render();
