
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
include_once '../financiero/consultas.php';

$host = Plantilla::getHost();

// Consulta tipo de presupuesto
$id_rad = isset($_POST['id_rad']) ? $_POST['id_rad'] : 0;
$id_doc = isset($_POST['id_doc']) ? $_POST['id_doc'] : 0;
$tipo_dato = $_POST['tipo_dato'];
$id_vigencia = $_SESSION['id_vigencia'];
$vigencia = $_SESSION['vigencia'];

function pesos($valor)
{
    return '$ ' . number_format($valor, 2, '.', ',');
}

$cmd = \Config\Clases\Conexion::getConexion();
$fecha_cierre = fechaCierre($vigencia, 55, $cmd);

try {
    $sql = "SELECT `id_doc_fuente` FROM `ctb_fuente` WHERE `cod` = '$tipo_dato'";
    $rs = $cmd->query($sql);
    $fuente = $rs->fetch();
    $tipo_dato = $fuente['id_doc_fuente'];
} catch (PDOException $e) {
    echo $e->getCode() == 2002 ? 'Sin Conexión a Mysql (Error: 2002)' : 'Error: ' . $e->getCode();
}

if ($id_doc == 0) {
    $fecha_doc = date('Y-m-d');
    try {
        $sql = "SELECT
                    `pto_rad`.`id_pto_rad` AS `id_crp`
                    , `pto_rad`.`id_tercero_api` AS `id_tercero`
                    , `pto_rad`.`fecha`
                    , `pto_rad`.`fecha` AS `fecha_crp`
                    , `pto_rad`.`objeto` AS `detalle`
                    , 'FACTURA' AS `fuente`
                    , 0 AS `estado`
                    , 0 AS `val_factura`
                    , 0 AS `val_imputacion`
                    , 0 AS `val_ccosto`
                    , 0 AS `val_retencion`
                    , 0 AS `id_ref_ctb`
                    , $id_rad AS `id_rad`
                    , `tb_terceros`.`nom_tercero`
                    , `tb_terceros`.`nit_tercero`
                FROM
                    `pto_rad`
                    LEFT JOIN `tb_terceros`
                        ON (`pto_rad`.`id_tercero_api` = `tb_terceros`.`id_tercero_api`)
                WHERE (`pto_rad`.`id_pto_rad` = $id_rad) LIMIT 1";
        $rs = $cmd->query($sql);
        $datosDoc = $rs->fetch();
    } catch (PDOException $e) {
        echo $e->getCode() == 2002 ? 'Sin Conexión a Mysql (Error: 2002)' : 'Error: ' . $e->getCode();
    }
    try {
        $sql = "SELECT
                    MAX(`id_manu`) AS `id_manu` 
                FROM
                    `ctb_doc`
                WHERE (`id_vigencia` = $id_vigencia AND `id_tipo_doc` = $tipo_dato)";
        $rs = $cmd->query($sql);
        $consecutivo = $rs->fetch();
        $id_manu = !empty($consecutivo) ? $consecutivo['id_manu'] + 1 : 1;
    } catch (PDOException $e) {
        echo $e->getCode() == 2002 ? 'Sin Conexión a Mysql (Error: 2002)' : 'Error: ' . $e->getCode();
    }
} else {
    $datosDoc = GetValoresCxP($id_doc, $cmd);
    $id_manu = $datosDoc['id_manu'];
    $fecha_doc = $datosDoc['fecha'];
    $fecha_doc = date("Y-m-d", strtotime($fecha_doc));
}

try {
    $sql = "SELECT
                `id_ctb_doc`
                , SUM(IFNULL(`debito`,0)) AS `debito`
                , SUM(IFNULL(`credito`,0)) AS `credito`
            FROM
                `ctb_libaux`
            WHERE (`id_ctb_doc` = $id_doc)";
    $rs = $cmd->query($sql);
    $totales = $rs->fetch();
} catch (PDOException $e) {
    echo $e->getCode() == 2002 ? 'Sin Conexión a Mysql (Error: 2002)' : 'Error: ' . $e->getCode();
}

try {
    $sql = "SELECT `id_ctb_referencia`,`nombre` FROM `ctb_referencia` WHERE `accion` = 1";
    $rs = $cmd->query($sql);
    $referencias = $rs->fetchAll();
} catch (PDOException $e) {
    echo $e->getCode() == 2002 ? 'Sin Conexión a Mysql (Error: 2002)' : 'Error: ' . $e->getCode();
}

$fecha = date('Y-m-d', strtotime($datosDoc['fecha']));

// Consulto tercero registrado en contratación
$tercero = '---';
if (!empty($datosDoc) && !empty($datosDoc['nom_tercero'])) {
    $tercero = ltrim($datosDoc['nom_tercero']);
}

// Validar permisos de registro
$peReg = $permisos->PermisosUsuario($opciones, 5501, 2) || $id_rol == 1 ? 1 : 0;

// Construir select de referencias
$optionsReferencias = '<option value="0">Seleccione...</option>';
foreach ($referencias as $rf) {
    $selected = $datosDoc['id_ref_ctb'] == $rf['id_ctb_referencia'] ? 'selected' : '';
    $optionsReferencias .= "<option value='{$rf['id_ctb_referencia']}' {$selected}>{$rf['nombre']}</option>";
}

// Sección de botón de facturación
$estadoDisabledBtn = $datosDoc['estado'] == '1' ? '' : 'disabled';
$valFactura = pesos($datosDoc['val_factura']);

$btnFacturacion = <<<HTML
<div class="row mt-3 mb-1">
    <div class="col-md-2 pe-0">
        <button class="btn btn-outline-success btn-sm w-100 text-start" type="button" onclick="GeneraFormInvoice('{$id_doc}')" {$estadoDisabledBtn}>
            <i class="fas fa-file-invoice-dollar me-2"></i> Facturación
        </button>
    </div>
    <div class="col-md-4">
        <input type="text" class="form-control form-control-sm" readonly id="valFactura" value="{$valFactura}">
    </div>
</div>
HTML;

// Fila de agregar registro
$filaAgregar = '';
if ($datosDoc['estado'] == '1') {
    $trc = $tipo_dato == '1' ? $tercero : '';
    $idter = $tipo_dato == '1' ? $datosDoc['id_tercero'] : 0;

    $filaAgregar = <<<HTML
    <tr>
        <td>
            <input type="text" name="codigoCta" id="codigoCta" class="form-control form-control-sm bg-input" value="" required>
            <input type="hidden" name="id_codigoCta" id="id_codigoCta" class="form-control form-control-sm bg-input" value="0">
            <input type="hidden" name="tipodato" id="tipodato" value="{$tipo_dato}">
        </td>
        <td>
            <input type="text" name="bTercero" id="bTercero" class="form-control form-control-sm bg-input bTercero" required value="{$trc}">
            <input type="hidden" name="idTercero" id="idTercero" value="{$idter}">
        </td>
        <td>
            <input type="text" name="valorDebito" id="valorDebito" class="form-control form-control-sm bg-input text-end" value="0" required onkeyup="valorMiles(id)" onchange="llenarCero(id)">
        </td>
        <td>
            <input type="text" name="valorCredito" id="valorCredito" class="form-control form-control-sm bg-input text-end" value="0" required onkeyup="valorMiles(id)" onchange="llenarCero(id)">
        </td>
        <td class="text-center">
            <button text="0" class="btn btn-primary btn-sm" onclick="GestMvtoDetalle(this)">Agregar</button>
        </td>
    </tr>
HTML;
}

$estadoGenerarBtn = $datosDoc['estado'] == '1' ? '' : 'disabled';
$estadoGuardar = $datosDoc['estado'] == '2' ? 'disabled' : '';

// Calcular valores para formulario
$idRadValue = $datosDoc['id_rad'] > 0 ? $datosDoc['id_rad'] : 0;
$minFecha = date('Y-m-d', strtotime($datosDoc['fecha_crp']));
$maxFecha = $vigencia . '-12-31';

$content = <<<HTML
<div class="card w-100">
    <div class="card-header bg-sofia text-white">
        <button class="btn btn-sm me-1 p-0" title="Regresar" onclick="window.history.back();"><i class="fas fa-arrow-left fa-lg"></i></button>
        <b>DETALLE DEL MOVIMIENTO CONTABLE - {$datosDoc['fuente']}</b>
    </div>
    <div class="card-body p-2 bg-wiev">
        <input type="hidden" id="peReg" value="{$peReg}">
        <input type="hidden" id="fec_cierre" name="fec_cierre" value="{$fecha_cierre}">
        <input type="hidden" id="id_ctb_doc" name="id_ctb_doc" value="{$id_doc}">
        
        <form id="formMvtoCtbInvoice">
            <div class="row mb-2">
                <div class="col-md-2">
                    <span class="small">NUMERO ACTO:</span>
                </div>
                <div class="col-md-10">
                    <input type="number" name="numDoc" id="numDoc" class="form-control form-control-sm bg-input" value="{$id_manu}" required>
                    <input type="hidden" id="id_doc_fuente" name="id_doc_fuente" value="{$tipo_dato}">
                    <input type="hidden" id="id_rad" name="id_rad" value="{$idRadValue}">
                </div>
            </div>
            <div class="row mb-2">
                <div class="col-md-2">
                    <span class="small">FECHA:</span>
                </div>
                <div class="col-md-10">
                    <input type="date" name="fecha" id="fecha" class="form-control form-control-sm bg-input" value="{$fecha_doc}" min="{$minFecha}" max="{$maxFecha}" required>
                </div>
            </div>
            <div class="row mb-2">
                <div class="col-md-2">
                    <span class="small">REFERENCIA:</span>
                </div>
                <div class="col-md-10">
                    <select id="slcReferencia" name="slcReferencia" class="form-select form-select-sm bg-input">
                        {$optionsReferencias}
                    </select>
                </div>
            </div>
            <div class="row mb-2">
                <div class="col-md-2">
                    <span class="small">TERCERO:</span>
                </div>
                <div class="col-md-10">
                    <input type="text" name="tercero" id="tercero" class="form-control form-control-sm bg-input" value="{$tercero}" readonly>
                    <input type="hidden" name="id_tercero" id="id_tercero" value="{$datosDoc['id_tercero']}">
                </div>
            </div>
            <div class="row mb-2">
                <div class="col-md-2">
                    <span class="small">OBJETO:</span>
                </div>
                <div class="col-md-10">
                    <textarea id="objeto" type="text" name="objeto" class="form-control form-control-sm bg-input py-0 sm" rows="3" required="required">{$datosDoc['detalle']}</textarea>
                </div>
            </div>
        </form>
        
        {$btnFacturacion}
        
        <div class="text-center py-2 mt-3">
            <button type="button" class="btn btn-primary btn-sm" onclick="generaMovimientoInvoice(this);" {$estadoGenerarBtn}>Generar movimiento</button>
            <button type="button" class="btn btn-warning btn-sm" onclick="" {$estadoGuardar} id="btnGuardaMvtoCtbInvoice" text="{$id_doc}">Guardar</button>
        </div>
        
        <div class="table-responsive shadow p-2 mt-3">
            <table id="tableMvtoContableDetalle" class="table table-striped table-bordered table-sm table-hover shadow w-100">
                <thead class="text-center">
                    <tr>
                        <th class="bg-sofia">Cuenta</th>
                        <th class="bg-sofia">Tercero</th>
                        <th class="bg-sofia">Debito</th>
                        <th class="bg-sofia">Credito</th>
                        <th class="bg-sofia">Acciones</th>
                    </tr>
                </thead>
                <tbody id="modificartableMvtoContableDetalle">
                </tbody>
                {$filaAgregar}
            </table>
        </div>
        
        <div class="text-center pt-4">
            <button type="button" class="btn btn-primary btn-sm" onclick="imprimirFormatoDoc({$id_doc});" style="width: 5rem;">
                <span class="fas fa-print"></span>
            </button>
            <button type="button" onclick="terminarDetalleInvoice('FELE')" class="btn btn-danger btn-sm" style="width: 7rem;">
                Terminar
            </button>
        </div>
    </div>
</div>
HTML;

$plantilla = new Plantilla($content, 2);
$plantilla->addCssFile("{$host}/assets/css/jquery-ui.css?v=" . date("YmdHis"));
$plantilla->addScriptFile("{$host}/assets/js/jquery-ui.js?v=" . date("YmdHis"));
$plantilla->addScriptFile("{$host}/src/contabilidad/js/funcioncontabilidad.js?v=" . date("YmdHis"));
$modal = $plantilla->getModal('divModalForms', 'divTamModalForms', 'divForms');
$plantilla->addModal($modal);
$modal = $plantilla->getModal('divModalReg', 'divTamModalReg', 'divFormsReg');
$plantilla->addModal($modal);
$modal = $plantilla->getModal('divModalImp', 'divTamModalImp', 'divImp');
$plantilla->addModal($modal);
echo $plantilla->render();
