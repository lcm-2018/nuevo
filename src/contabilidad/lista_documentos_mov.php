
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
$peReg = $permisos->PermisosUsuario($opciones, 5501, 2) || $id_rol == 1 ? 1 : 0;
$host = Plantilla::getHost();

$tipo_doc = isset($_POST['id_doc']) ? $_POST['id_doc'] : 0;

// Consulta tipo de documento
try {
    $cmd = \Config\Clases\Conexion::getConexion();
    $sql = "SELECT `id_doc_fuente`, `nombre` FROM `ctb_fuente` WHERE `contab` = 1 OR `contab` = 3 ORDER BY `nombre`";
    $rs = $cmd->query($sql);
    $docsFuente = $rs->fetchAll();
    $rs->closeCursor();
    unset($rs);
} catch (PDOException $e) {
    echo $e->getCode() == 2002 ? 'Sin Conexión a Mysql (Error: 2002)' : 'Error: ' . $e->getCode();
}

// Consulta nóminas pendientes de contabilizar
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
            WHERE (`nom_nominas`.`estado` = 3) AND `nom_nomina_pto_ctb_tes`.`tipo` <> 'PL'
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
            WHERE (`nom_nominas`.`planilla` = 3 AND `nom_nomina_pto_ctb_tes`.`tipo` = 'PL')";
    $rs = $cmd->query($sql);
    $nominas = $rs->fetchAll();
    $rs->closeCursor();
    unset($rs);
} catch (PDOException $e) {
    echo $e->getCode() == 2002 ? 'Sin Conexión a Mysql (Error: 2002)' : 'Error: ' . $e->getCode();
}

$rp = [];
foreach ($nominas as $nm) {
    if ($nm['crp'] != '') {
        $rp[] = $nm['crp'];
    }
}
$rp = implode(',', $rp);
$total = 0;

if (!empty($nominas) && !empty($rp)) {
    try {
        $sql = "SELECT 
                    `pto_crp`.`id_pto_crp`
                    , `t1`.`valor`
                    , `pto_crp`.`id_manu`
                    , `pto_crp`.`fecha`
                    , `pto_crp`.`objeto`
                FROM 
                    (SELECT
                        `id_pto_crp`
                        , SUM(`valor`) AS `valor`
                    FROM
                        `pto_crp_detalle`
                    WHERE `id_pto_crp` IN ($rp) GROUP BY `id_pto_crp`) AS `t1`
                INNER JOIN `pto_crp`
                    ON(`pto_crp`.`id_pto_crp` = `t1`.`id_pto_crp`)";
        $rs = $cmd->query($sql);
        $valores = $rs->fetchAll();
        $rs->closeCursor();
        unset($rs);

        foreach ($valores as $vl) {
            $key = array_search($vl['id_pto_crp'], array_column($nominas, 'crp'));
            if ($key !== false && $nominas[$key]['estado'] == 3) {
                $total++;
            }
        }
    } catch (PDOException $e) {
        echo $e->getCode() == 2002 ? 'Sin Conexión a Mysql (Error: 2002)' : 'Error: ' . $e->getCode();
    }
}

// Construir opciones del select de documentos
$optionsDocFuente = '<option value="">-- Seleccionar --</option>';
foreach ($docsFuente as $mov) {
    $selected = $mov['id_doc_fuente'] == $tipo_doc ? 'selected' : '';
    $optionsDocFuente .= '<option value="' . $mov['id_doc_fuente'] . '" ' . $selected . '>' . $mov['nombre'] . '</option>';
}

// Botones adicionales según tipo de documento
$botonesAdicionales = '';
if ($tipo_doc == '3' && $_SESSION['caracter'] == '2') {
    $botonesAdicionales .= <<<HTML
    <button type="button" class="btn btn-primary btn-sm me-1" onclick="CargaObligaCrp(2)">
        Ver Listado
    </button>
HTML;
}

if ($tipo_doc == '5') {
    $botonesAdicionales .= <<<HTML
    <input type="hidden" id="total" value="{$total}">
    <button type="button" class="btn btn-outline-success btn-sm me-1" onclick="CargaObligaCrp(3)">
        Nómina <span class="badge bg-light text-dark" id="totalCausa">{$total}</span>
    </button>
HTML;
}

// Determinar si mostrar tabla
$tablaHTML = '';
if ($tipo_doc > 0) {
    $tablaHTML = <<<HTML
    <div class="table-responsive shadow p-2">
        <table id="tableMvtoContable" class="table table-striped table-bordered table-sm table-hover shadow w-100">
            <thead class="text-center">
                <tr>
                    <th class="bg-sofia">Numero</th>
                    <th class="bg-sofia">Rp</th>
                    <th class="bg-sofia">Fecha</th>
                    <th class="bg-sofia">Tercero</th>
                    <th class="bg-sofia">Valor</th>
                    <th class="bg-sofia">Acciones</th>
                </tr>
            </thead>
            <tbody id="modificarMvtoContable">
            </tbody>
        </table>
    </div>
HTML;
}

// Validar permisos de registro
$peRegValue = ($peReg && !($tipo_doc == '5' || $tipo_doc == '3')) || ($_SESSION['caracter'] == '1' && $tipo_doc == '3') ? 1 : 0;

$content = <<<HTML
<div class="card w-100">
    <div class="card-header bg-sofia text-white">
        <a class="btn btn-sm me-1 p-0" title="Regresar" href="{$host}/src/inicio.php"><i class="fas fa-arrow-left fa-lg"></i></a>
        <b>REGISTRO DE MOVIMIENTOS CONTABLES</b>
    </div>
    <div class="card-body p-2 bg-wiev">
        <input type="hidden" id="peReg" value="{$peRegValue}">
        <input type="hidden" id="id_ctb_doc" value="{$tipo_doc}">
        
        <div class="d-flex justify-content-center align-items-center gap-2 flex-wrap">
            <form action="{$_SERVER['PHP_SELF']}" method="POST">
                <select class="form-select form-select-sm bg-input" id="slcDocFuente" name="id_doc" onchange="cambiaListadoContable(this.value)">
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
                <div class="input-group">
                    <div class="input-group-text">
                        <input class="form-check-input mt-0" type="checkbox" value="" title="Marcar para filtrar por valor exacto" id="txt_bandera_filtro">
                    </div>
                    <input type="text" class="filtro form-control form-control-sm bg-input" id="txt_idmanu_filtro" placeholder="Id. Manu">
                </div>
            </div>
            <div class="col-md-1">
                <input type="text" class="filtro form-control form-control-sm bg-input bg-input" id="txt_rp_filtro" placeholder="RP">
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
                <label class="form-check-label small">
                    <input class="form-check-input" type="checkbox" id="verAnulados"> Anulados
                </label>
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
$plantilla->addScriptFile("{$host}/src/contabilidad/js/funcioncontabilidad.js?v=" . date("YmdHis"));
$modal = $plantilla->getModal('divModalForms', 'divTamModalForms', 'divForms');
$plantilla->addModal($modal);
$modal = $plantilla->getModal('divModalReg', 'divTamModalReg', 'divFormsReg');
$plantilla->addModal($modal);
$modal = $plantilla->getModal('divModalImp', 'divTamModalImp', 'divImp');
$plantilla->addModal($modal);
echo $plantilla->render();
