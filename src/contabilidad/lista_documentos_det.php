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
$id_doc = isset($_POST['id_doc']) ? $_POST['id_doc'] : 0;
$id_crp = isset($_POST['id_crp']) ? $_POST['id_crp'] : 0;
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
    $sql = "SELECT
                `cod`,`nombre`,`contab`
            FROM `ctb_fuente`
            WHERE `id_doc_fuente` = $tipo_dato LIMIT 1";
    $rs = $cmd->query($sql);
    $fuente = $rs->fetch(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    echo $e->getCode() == 2002 ? 'Sin Conexión a Mysql (Error: 2002)' : 'Error: ' . $e->getCode();
}

if ($id_doc == 0) {
    $fecha_doc = date('Y-m-d');
    try {
        $sql = "SELECT
                    `pto_crp`.`id_pto_crp` AS `id_crp`
                    , `pto_crp`.`id_tercero_api` AS `id_tercero`
                    , `pto_crp`.`fecha`
                    , `pto_crp`.`fecha` AS `fecha_crp`
                    , `pto_crp`.`objeto` AS `detalle`
                    , 'CUENTAS POR PAGAR' AS `fuente`
                    , 0 AS `estado`
                    , 0 AS `val_factura`
                    , 0 AS `val_imputacion`
                    , 0 AS `val_ccosto`
                    , 0 AS `val_retencion`
                    , `tb_terceros`.`nom_tercero`
                    , `tb_terceros`.`nit_tercero`
                FROM
                    `pto_crp`
                    LEFT JOIN `tb_terceros`
                        ON (`pto_crp`.`id_tercero_api` = `tb_terceros`.`id_tercero_api`)
                WHERE (`pto_crp`.`id_pto_crp` = $id_crp) LIMIT 1";
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

$fecha = date('Y-m-d', strtotime($datosDoc['fecha']));

// Consulto tercero registrado en contratación
$tercero = '---';
if (isset($datosDoc['nom_tercero'])) {
    $tercero = ltrim($datosDoc['nom_tercero']);
}

// Validar permisos de registro
$peReg = $permisos->PermisosUsuario($opciones, 5501, 2) || $id_rol == 1 ? 1 : 0;

// Construir secciones según el tipo de documento
$seccionBotones = '';
if ($tipo_dato == '3') {
    // Calcular valores antes del heredoc
    $valFactura = pesos($datosDoc['val_factura']);
    $valImputacion = pesos($datosDoc['val_imputacion']);
    $valCentroCosto = pesos($datosDoc['val_ccosto']);
    $valDescuentos = pesos($datosDoc['val_retencion']);
    $estadoDisabledBtn = $datosDoc['estado'] == '1' ? '' : 'disabled';

    $btnFacturacion =
        <<<HTML
        <div class="row mt-3 mb-1">
            <div class="col-md-2 pe-0">
                <button class="btn btn-outline-success btn-sm w-100 text-start" type="button" onclick="FacturarCtasPorPagar('{$id_doc}')" {$estadoDisabledBtn}> <i class="fas fa-file-invoice-dollar me-2"></i> Facturación
                </button>
            </div>
            <div class="col-md-4">
                <input type="text" class="form-control form-control-sm" readonly id="valFactura" value="{$valFactura}">
            </div>
        </div>
        HTML;

    $btnImputacion = '';
    if ($_SESSION['caracter'] == '2') {
        $btnImputacion =
            <<<HTML
            <div class="row mb-1">
                <div class="col-md-2 pe-0">
                    <button class="btn btn-outline-primary btn-sm w-100 text-start" type="button" onclick="ImputacionCtasPorPagar('{$id_doc}')" {$estadoDisabledBtn}> <i class="fas fa-file-signature me-2"></i> Imputación
                    </button>
                </div>
                <div class="col-md-4">
                    <div class="input-group input-group-sm">
                        <input type="text" class="form-control form-control-sm" readonly id="valImputacion" value="{$valImputacion}">
                        <button class="btn btn-outline-primary btn-sm" type="button" onclick="CausaAuCentroCostos(this)" title="Asignar Imputacion y Centros de costo automaticamente" {$estadoDisabledBtn}> <i class="fas fa-eject"></i>
                        </button>
                    </div>
                </div>
            </div>
            HTML;
    }

    $btnCentroCosto =
        <<<HTML
        <div class="row mb-1">
            <div class="col-md-2 pe-0">
                <button class="btn btn-outline-warning btn-sm w-100 text-start" type="button" onclick="CentroCostoCtasPorPagar('{$id_doc}')" {$estadoDisabledBtn}> <i class="fas fa-kaaba me-2"></i> Centro Costo
                </button>
            </div>
            <div class="col-md-4">
                <input type="text" class="form-control form-control-sm" readonly id="valCentroCosto" value="{$valCentroCosto}">
            </div>
        </div>
        HTML;

    $btnDescuentos =
        <<<HTML
        <div class="row mb-1">
            <div class="col-md-2 pe-0">
                <button class="btn btn-outline-info btn-sm w-100 text-start" type="button" onclick="DesctosCtasPorPagar('{$id_doc}')" {$estadoDisabledBtn}> <i class="fas fa-donate me-2"></i> Descuentos
                </button>
            </div>
            <div class="col-md-4">
                <input type="text" class="form-control form-control-sm" readonly id="valDescuentos" value="{$valDescuentos}">
            </div>
        </div>
        HTML;

    $seccionBotones = $btnFacturacion . $btnImputacion . $btnCentroCosto . $btnDescuentos;
}

$seccionTraslado = '';
if ($fuente['contab'] == 3) {
    $fechaHoy = date('Y-m-d');
    $seccionTraslado = <<<HTML
    <div class="row mb-2 mb-1 text-start">
        <div class="col-md-2">
            <span class="small">PERIODO TRASLADO:</span>
        </div>
        <div class="col-md-6">
            <div class="row mb-2">
                <div class="col-md-3">
                    <span class="small text-muted">FECHA INICIO:</span>
                </div>
                <div class="col-md-3">
                    <input type="date" name="fecIniTraslado" id="fecIniTraslado" class="form-control form-control-sm bg-input" value="{$fechaHoy}" min="{$vigencia}-01-01" max="{$vigencia}-12-31" required>
                </div>
                <div class="col-md-3">
                    <span class="small text-muted">FECHA FIN:</span>
                </div>
                <div class="col-md-3">
                    <input type="date" name="fecFinTraslado" id="fecFinTraslado" class="form-control form-control-sm bg-input" value="{$fechaHoy}" min="{$vigencia}-01-01" max="{$vigencia}-12-31" required>
                </div>
            </div>
        </div>
    </div>
HTML;
}

// Botón generar movimiento
$btnGenerarMovimiento = '';
$funcion = $fuente['contab'] == 3 ? 'generaMovimientoTrasCosto' : 'generaMovimientoCxp';
if ($tipo_dato == '3' || $fuente['contab'] == 3) {
    $estadoDisabled = $datosDoc['estado'] == '1' ? '' : 'disabled';
    $btnGenerarMovimiento = <<<HTML
    <button type="button" class="btn btn-primary btn-sm" onclick="{$funcion}(this);" {$estadoDisabled}>Generar movimiento</button>
HTML;
}

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
            <input type="hidden" name="tipoDato" id="tipoDato" value="0">
        </td>
        <td>
            <input type="text" name="bTercero" id="bTercero" class="form-control form-control-sm bg-input bTercero" required value="{$trc}">
            <input type="hidden" name="idTercero" id="idTercero" value="{$idter}">
        </td>
        <td>
            <input type="text" name="valorDebito" id="valorDebito" class="form-control form-control-sm bg-input text-end" value="0" required onkeyup="NumberMiles(this)" onchange="llenarCero(id)">
        </td>
        <td>
            <input type="text" name="valorCredito" id="valorCredito" class="form-control form-control-sm bg-input text-end" value="0" required onkeyup="NumberMiles(this)" onchange="llenarCero(id)">
        </td>
        <td class="text-center">
            <button text="0" class="btn btn-primary btn-sm" onclick="GestMvtoDetalle(this)">Agregar</button>
        </td>
    </tr>
HTML;
}

$estadoGuardar = $datosDoc['estado'] == '2' ? 'disabled' : '';

// Calcular valores para formulario
$idCrpValue = $datosDoc['id_crp'] > 0 ? $datosDoc['id_crp'] : 0;
$minFecha = date('Y-m-d', strtotime($datosDoc['fecha_crp']));
$maxFecha = $vigencia . '-12-31';

$content = <<<HTML
<div class="card w-100">
    <div class="card-header bg-sofia text-white">
        <button class="btn btn-sm me-1 p-0" title="Regresar" onclick="terminarDetalle('{$tipo_dato}')"><i class="fas fa-arrow-left fa-lg"></i></button>
        <b>DETALLE DEL MOVIMIENTO CONTABLE - {$datosDoc['fuente']}</b>
    </div>
    <div class="card-body p-2 bg-wiev">
        <input type="hidden" id="peReg" value="{$peReg}">
        <input type="hidden" id="fec_cierre" name="fec_cierre" value="{$fecha_cierre}">
        <input type="hidden" id="id_ctb_doc" name="id_ctb_doc" value="{$id_doc}">
        
        <form id="formGetMvtoCtb">
            <div class="row mb-2">
                <div class="col-md-2">
                    <span class="small">NUMERO ACTO:</span>
                </div>
                <div class="col-md-10">
                    <input type="number" name="numDoc" id="numDoc" class="form-control form-control-sm bg-input" value="{$id_manu}" required>
                    <input type="hidden" id="tipodato" name="tipodato" value="{$tipo_dato}">
                    <input type="hidden" id="id_crpp" name="id_crpp" value="{$idCrpValue}">
                    <input type="hidden" id="fuente" name="fuente" value="{$fuente['contab']}">
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
        
        {$seccionBotones}
        {$seccionTraslado}
        
        <div class="text-center py-2 mt-3">
            {$btnGenerarMovimiento}
            <button type="button" class="btn btn-warning btn-sm" onclick="" {$estadoGuardar} id="GuardaDocCtb" text="{$id_doc}">Guardar</button>
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
            <button type="button" onclick="terminarDetalle('{$tipo_dato}')" class="btn btn-danger btn-sm" style="width: 7rem;">
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
