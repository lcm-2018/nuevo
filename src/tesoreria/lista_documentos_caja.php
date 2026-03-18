<?php
session_start();

if (!isset($_SESSION['user'])) {
    header('Location: ../index.php');
    exit();
}
include '../../config/autoloader.php';

use Config\Clases\Plantilla;
use Src\Common\Php\Clases\Permisos;

$id_rol = $_SESSION['rol'];
$id_user = $_SESSION['id_user'];

$permisos = new Permisos();
$opciones = $permisos->PermisoOpciones($id_user);
include '../financiero/consultas.php';

$host = Plantilla::getHost();

// Parámetros de entrada
$id_doc_pag = isset($_POST['id_doc'])    ? $_POST['id_doc']    : exit('Acceso no disponible');
$id_cop     = isset($_POST['id_cop'])    ? $_POST['id_cop']    : 0;
$tipo_dato  = isset($_POST['tipo_dato']) ? $_POST['tipo_dato'] : 0;
$tipo_mov   = isset($_POST['tipo_movi']) ? $_POST['tipo_movi'] : 0;
$tipo_var   = isset($_POST['tipo_var'])  ? $_POST['tipo_var']  : 0;
$id_arq     = isset($_POST['id_arq'])    ? $_POST['id_arq']    : 0;
$id_vigencia = $_SESSION['id_vigencia'];

$cmd = \Config\Clases\Conexion::getConexion();

$fecha_cierre = fechaCierre($_SESSION['vigencia'], 56, $cmd);

// Consulta datos del documento de caja menor
try {
    $sql = "SELECT
                `ctb_doc`.`id_ctb_doc`
                , `ctb_doc`.`id_manu`
                , `ctb_fuente`.`nombre` AS `fuente`
                , `ctb_doc`.`fecha`
                , `ctb_doc`.`detalle`
                , `ctb_doc`.`id_tercero`
                , `ctb_doc`.`estado`
                , `tes_caja_const`.`nombre_caja`
                , `tes_caja_const`.`fecha_ini`
                , `tes_caja_const`.`id_caja_const`
            FROM
                `ctb_doc`
                INNER JOIN `ctb_fuente`
                    ON (`ctb_doc`.`id_tipo_doc` = `ctb_fuente`.`id_doc_fuente`)
                LEFT JOIN `tes_caja_doc`
                    ON (`tes_caja_doc`.`id_ctb_doc` = `ctb_doc`.`id_ctb_doc`)
                LEFT JOIN `tes_caja_const`
                    ON (`tes_caja_doc`.`id_caja` = `tes_caja_const`.`id_caja_const`)
            WHERE (`ctb_doc`.`id_ctb_doc` = $id_doc_pag)";
    $rs = $cmd->query($sql);
    $datosDoc = $rs->fetch();
} catch (PDOException $e) {
    echo $e->getCode() == 2002 ? 'Sin Conexión a Mysql (Error: 2002)' : 'Error: ' . $e->getCode();
}

$id_manu = $datosDoc['id_manu'] ?? 0;

// Consulta nombre del tercero
try {
    $sql = "SELECT `nom_tercero` FROM `tb_terceros` WHERE `id_tercero_api` = {$datosDoc['id_tercero']}";
    $rs  = $cmd->query($sql);
    $dat_ter = $rs->fetch();
    $tercero = !empty($dat_ter) ? $dat_ter['nom_tercero'] : '---';
} catch (PDOException $e) {
    $tercero = '---';
}

// Totales contables
try {
    $sql = "SELECT
                `id_ctb_doc`
                , SUM(IFNULL(`debito`,0))  AS `debito`
                , SUM(IFNULL(`credito`,0)) AS `credito`
            FROM
                `ctb_libaux`
            WHERE (`id_ctb_doc` = $id_doc_pag)";
    $rs = $cmd->query($sql);
    $totales = $rs->fetch();
} catch (PDOException $e) {
    echo $e->getCode() == 2002 ? 'Sin Conexión a Mysql (Error: 2002)' : 'Error: ' . $e->getCode();
}

// Forma de pago (tes_detalle_pago)
$valor_pago = 0;
try {
    $sql = "SELECT
                `id_ctb_doc`
                , SUM(`valor`) AS `valor`
            FROM
                `tes_detalle_pago`
            WHERE (`id_ctb_doc` = $id_doc_pag)";
    $rs = $cmd->query($sql);
    $values = $rs->fetch();
    $valor_pago = !empty($values) ? $values['valor'] : 0;
} catch (PDOException $e) {
    echo $e->getCode() == 2002 ? 'Sin Conexión a Mysql (Error: 2002)' : 'Error: ' . $e->getCode();
}

// Imputación de cuentas de caja menor (tes_caja_mvto)
$val_imp = 0;
try {
    $query = "SELECT SUM(`valor`) AS `val_imputacion` FROM `tes_caja_mvto` WHERE `id_ctb_doc` = ?";
    $query = $cmd->prepare($query);
    $query->bindParam(1, $id_doc_pag, PDO::PARAM_INT);
    $query->execute();
    $val_imp = $query->fetch(PDO::FETCH_ASSOC)['val_imputacion'] ?? 0;
} catch (PDOException $e) {
    echo $e->getCode() == 2002 ? 'Sin Conexión a Mysql (Error: 2002)' : 'Error: ' . $e->getCode();
}

// Permisos
$peReg = ($permisos->PermisosUsuario($opciones, 5601, 2) || $id_rol == 1) ? '1' : '0';

// Variables para el formulario
$fecha      = !empty($datosDoc['fecha'])    ? date('Y-m-d', strtotime($datosDoc['fecha'])) : date('Y-m-d');
$fuente     = $datosDoc['fuente']           ?? 'DOCUMENTO';
$estado     = $datosDoc['estado']           ?? 1;
$id_tercero = $datosDoc['id_tercero']       ?? 0;
$detalle    = $datosDoc['detalle']          ?? '';
$id_caja_const = $datosDoc['id_caja_const'] ?? 0;
$nombre_caja   = $datosDoc['nombre_caja']   ?? '';
$fecha_ini_c   = $datosDoc['fecha_ini']     ?? '';

// Sección Imputación de Caja Menor
$btnImputacion = ($estado == 1) ? '<button class="btn btn-outline-success" onclick="ImputacionCtasCajas(' . $id_caja_const . ')"><span class="fas fa-plus fa-lg"></span></button>' : '';
$seccionImputacion = <<<HTML
    <div class="row mb-1">
        <div class="col-md-2">
            <label for="valor" class="small fw-bold">IMPUTACION:</label>
        </div>
        <div class="col-4">
            <div class="input-group input-group-sm">
                <input type="text" name="valor" id="valor" value="{$val_imp}" class="form-control bg-input" style="text-align: right;" required readonly>
                {$btnImputacion}
            </div>
        </div>
    </div>
HTML;

// Sección Forma de Pago
$btnFormaPago = ($estado == 1 && $id_doc_pag > 0)
    ? '<button class="btn btn-outline-primary" onclick="cargaFormaPago(' . $id_cop . ',0,this)"><span class="fas fa-wallet fa-lg"></span></button>'
    : '';
$seccionFormaPago = <<<HTML
    <div class="row mb-1">
        <div class="col-md-2">
            <label class="small fw-bold">FORMA DE PAGO :</label>
        </div>
        <div class="col-4">
            <div class="input-group input-group-sm">
                <input type="text" name="forma_pago" id="forma_pago" value="{$valor_pago}" class="form-control bg-input" style="text-align: right;" readonly>
                {$btnFormaPago}
            </div>
        </div>
    </div>
HTML;

// Botón Guardar y Generar Movimiento
$btnGuardar = $estado == 1 ? '<button type="button" class="btn btn-warning btn-sm" id="GuardaDocMvtoPag" text="' . $id_doc_pag . '">Guardar</button>' : '';
if ($estado == 1 && $id_doc_pag > 0) {
    $btnGenMov = <<<HTML
        <button type="button" class="btn btn-primary btn-sm" onclick="generaMovimientoCaja('{$id_doc_pag}')">Generar movimiento</button>
        HTML;
} else {
    $btnGenMov = '';
}

$seccionGenerarMov = <<<HTML
    <div class="text-center pt-2">
        {$btnGenMov}
        {$btnGuardar}
    </div>
HTML;

// Fila de entrada para agregar movimiento contable
$filaEntrada = '';
if ($estado == '1' && $id_doc_pag > 0) {
    $filaEntrada = <<<HTML
    <tr>
        <td>
            <input type="text" name="codigoCta" id="codigoCta" class="form-control form-control-sm bg-input" value="" required>
            <input type="hidden" name="id_codigoCta" id="id_codigoCta" class="form-control form-control-sm bg-input" value="0">
            <input type="hidden" name="tipoDato" id="tipoDato" value="0">
        </td>
        <td>
            <input type="text" name="bTercero" id="bTercero" class="form-control form-control-sm bg-input bTercero" value="{$tercero}" required>
            <input type="hidden" name="idTercero" id="idTercero" value="{$id_tercero}">
        </td>
        <td>
            <input type="text" name="valorDebito" id="valorDebito" class="form-control form-control-sm bg-input text-end" value="0" required onkeyup="NumberMiles(this)" onchange="llenarCero(id)">
        </td>
        <td>
            <input type="text" name="valorCredito" id="valorCredito" class="form-control form-control-sm bg-input text-end" value="0" required onkeyup="NumberMiles(this)" onchange="llenarCero(id)">
        </td>
        <td class="text-center">
            <button text="0" class="btn btn-primary btn-sm" onclick="GestMvtoDetallePag(this)">Agregar</button>
        </td>
    </tr>
HTML;
}

$content = <<<HTML
<div class="card w-100">
    <div class="card-header bg-sofia text-white">
        <a class="btn btn-sm me-1 p-0" title="Regresar" onclick="terminarDetalleTes({$tipo_dato},{$tipo_var})"><i class="fas fa-arrow-left fa-lg"></i></a>
        <b>DETALLE DEL COMPROBANTE DE CAJA MENOR</b>
    </div>
    <div class="card-body p-2 bg-wiev">
        <input type="hidden" id="tipo_var" value="{$tipo_var}">
        <input type="hidden" id="peReg" value="{$peReg}">

        <form id="formGetMvtoTes">
            <input type="hidden" id="fec_cierre" value="{$fecha_cierre}">

            <div class="row mb-2">
                <div class="col-md-2">
                    <label class="small fw-bold">NUMERO ACTO:</label>
                </div>
                <div class="col-md-10">
                    <input type="number" name="numDoc" id="numDoc" class="form-control form-control-sm bg-input" value="{$id_manu}" readonly>
                    <input type="hidden" id="tipodato" name="tipodato" value="{$tipo_dato}">
                    <input type="hidden" id="id_cop_pag" name="id_cop_pag" value="{$id_cop}">
                    <input type="hidden" id="id_arqueo" name="id_arqueo" value="{$id_arq}">
                </div>
            </div>

            <div class="row mb-2">
                <div class="col-md-2">
                    <label class="small fw-bold">FECHA:</label>
                </div>
                <div class="col-md-10">
                    <input type="date" name="fecha" id="fecha" class="form-control form-control-sm bg-input" value="{$fecha}" readonly>
                </div>
            </div>

            <div class="row mb-2">
                <div class="col-md-2">
                    <label class="small fw-bold">TERCERO:</label>
                </div>
                <div class="col-md-10">
                    <input type="text" name="tercero" id="tercero" class="form-control form-control-sm bg-secondary-subtle" value="{$tercero}" required readonly>
                    <input type="hidden" name="id_tercero" id="id_tercero" value="{$id_tercero}">
                </div>
            </div>

            <div class="row mb-2">
                <div class="col-md-2">
                    <label class="small fw-bold">CAJA:</label>
                </div>
                <div class="col-md-10">
                    <input type="text" name="referencia" id="referencia" value="{$nombre_caja} -> {$fecha_ini_c}" class="form-control form-control-sm bg-input" readonly>
                </div>
            </div>

            <div class="row mb-3">
                <div class="col-md-2">
                    <label class="small fw-bold">OBJETO:</label>
                </div>
                <div class="col-md-10">
                    <textarea id="objeto" name="objeto" class="form-control form-control-sm bg-input" rows="3">{$detalle}</textarea>
                </div>
            </div>

            {$seccionImputacion}
            {$seccionFormaPago}
            {$seccionGenerarMov}

            <input type="hidden" id="id_ctb_doc" name="id_ctb_doc" value="{$id_doc_pag}">

            <div class="table-responsive mt-4">
                <table id="tableMvtoContableDetallePag" class="table table-striped table-bordered table-sm table-hover shadow" style="width:100%">
                    <thead>
                        <tr>
                            <th class="bg-sofia">Cuenta</th>
                            <th class="bg-sofia">Tercero</th>
                            <th class="bg-sofia">Debito</th>
                            <th class="bg-sofia">Credito</th>
                            <th class="bg-sofia">Acciones</th>
                        </tr>
                    </thead>
                    <tbody id="modificartableMvtoContableDetallePag">
                    </tbody>
                    {$filaEntrada}
                </table>
            </div>
        </form>

        <div class="text-center pt-4">
            <a type="button" class="btn btn-primary btn-sm" onclick="imprimirFormatoTes({$id_doc_pag});" style="width: 5rem;">
                <span class="fas fa-print"></span>
            </a>
            <a onclick="terminarDetalleTes({$tipo_dato},{$tipo_var})" class="btn btn-danger btn-sm" style="width: 7rem;" href="#">
                Terminar
            </a>
        </div>
    </div>
    <script>
        window.onload = function() {
            buscarConsecutivoTeso('{$tipo_dato}');
        }
    </script>
</div>
HTML;

$plantilla = new Plantilla($content, 2);
$plantilla->addCssFile("{$host}/assets/css/jquery-ui.css?v=" . date("YmdHis"));
$plantilla->addScriptFile("{$host}/assets/js/jquery-ui.js?v=" . date("YmdHis"));
$plantilla->addScriptFile("{$host}/src/tesoreria/js/funciontesoreria.js?v=" . date("YmdHis"));
$modal = $plantilla->getModal('divModalForms', 'divTamModalForms', 'divForms');
$plantilla->addModal($modal);
$modal = $plantilla->getModal('divModalReg', 'divTamModalReg', 'divFormsReg');
$plantilla->addModal($modal);
$modal = $plantilla->getModal('divModalAux', 'divTamModalAux', 'divFormsAux');
$plantilla->addModal($modal);

echo $plantilla->render();
