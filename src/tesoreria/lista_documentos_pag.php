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

// Consulta tipo de presupuesto
$id_doc_pag = isset($_POST['id_doc']) ? $_POST['id_doc'] : exit('Acceso no disponible');
$id_arq = isset($_POST['id_arq']) ? $_POST['id_arq'] : 0;
$id_cop = isset($_POST['id_cop']) ? $_POST['id_cop'] : 0;
$tipo_dato = isset($_POST['tipo_dato']) ? $_POST['tipo_dato'] : 0;
$tipo_mov = isset($_POST['tipo_movi']) ? $_POST['tipo_movi'] : 0;
$tipo_var = isset($_POST['tipo_var']) ? $_POST['tipo_var'] : 0;
$id_doc_rad = isset($_POST['id_doc_rad']) ? $_POST['id_doc_rad'] : 0;
$id_vigencia = $_SESSION['id_vigencia'];

$cmd = \Config\Clases\Conexion::getConexion();

$fecha_cierre = fechaCierre($_SESSION['vigencia'], 56, $cmd);

if ($id_doc_pag == 0) {
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
    try {
        if ($id_cop > 0) {
            $sql = "SELECT 
                    `ctb_doc`.`id_tercero`
                    , `ctb_doc`.`fecha`
                    , `ctb_doc`.`detalle`
                    , `ctb_doc`.`id_ref`
                    , `tb_terceros`.`nom_tercero`
                    ,  $id_manu AS `id_manu`
                    , `ctb_fuente`.`nombre` AS `fuente`
                    , 0 AS `val_pagado`
                    , 1 AS `estado`
                    , 0 AS `id_ref_ctb`

                FROM `ctb_doc`
                    INNER JOIN `ctb_fuente`
		                ON (`ctb_doc`.`id_tipo_doc` = `ctb_fuente`.`id_doc_fuente`)
                    LEFT JOIN `tb_terceros`
                        ON(`ctb_doc`.`id_tercero` = `tb_terceros`.`id_tercero_api`)
                WHERE (`ctb_doc`.`id_ctb_doc` =  $id_cop) LIMIT 1";
        } else if ($id_doc_rad > 0) {
            $sql = "SELECT 
                    `ctb_doc`.`id_tercero`
                    , `ctb_doc`.`fecha`
                    , `ctb_doc`.`detalle`
                    , `ctb_doc`.`id_ref`
                    , `tb_terceros`.`nom_tercero`
                    ,  $id_manu AS `id_manu`
                    , `ctb_fuente`.`nombre` AS `fuente`
                    , 0 AS `val_pagado`
                    , 1 AS `estado`
                    , `ctb_doc`.`id_ref_ctb`

                FROM `ctb_doc`
                    INNER JOIN `ctb_fuente`
                        ON (`ctb_doc`.`id_tipo_doc` = `ctb_fuente`.`id_doc_fuente`)
                    LEFT JOIN `tb_terceros`
                        ON(`ctb_doc`.`id_tercero` = `tb_terceros`.`id_tercero_api`)
                WHERE (`ctb_doc`.`id_ctb_doc` =  $id_doc_rad) LIMIT 1";
        }
        $rs = $cmd->query($sql);
        $datosDoc = $rs->fetch();
        $tercero = $datosDoc['nom_tercero'];
    } catch (PDOException $e) {
        echo $e->getCode() == 2002 ? 'Sin Conexión a Mysql (Error: 2002)' : 'Error: ' . $e->getCode();
    }
} else {
    $datosDoc = GetValoresCeva($id_doc_pag, $cmd);
    $id_manu = $datosDoc['id_manu'];
    $id_cop = $datosDoc['id_doc_cop'] > 0 ? $datosDoc['id_doc_cop'] : 0;
    $id_ref = $datosDoc['id_ref'];
    if ($id_doc_rad == 0) {
        $iddd = $datosDoc['id_ctb_doc_tipo3'] == '' ? 0 : $datosDoc['id_ctb_doc_tipo3'];
        $sqls = "SELECT
                    `ctb_fuente`.`cod`
                FROM
                    `ctb_doc`
                    INNER JOIN `ctb_fuente` 
                        ON (`ctb_doc`.`id_tipo_doc` = `ctb_fuente`.`id_doc_fuente`)
                WHERE (`ctb_doc`.`id_ctb_doc` = $iddd)";
        $rs = $cmd->query($sqls);
        $rdss = $rs->fetch();
        $id_doc_rad = !empty($rdss) && $rdss['cod'] == 'FELE' ? $datosDoc['id_ctb_doc_tipo3'] : 0;
    }
    if ($id_doc_rad > 0) {
        $sql = "SELECT
                SUM(IFNULL(`pto_rec_detalle`.`valor`,0) - IFNULL(`pto_rec_detalle`.`valor_liberado`,0)) AS `valor`
            FROM
                `pto_rec_detalle`
                INNER JOIN `pto_rec` 
                    ON (`pto_rec_detalle`.`id_pto_rac` = `pto_rec`.`id_pto_rec`)
            WHERE (`pto_rec`.`estado` > 0 AND `pto_rec`.`id_ctb_doc` = $id_doc_pag)";
        $rs = $cmd->query($sql);
        $valor = $rs->fetch();
        $datosDoc['val_pagado'] = !empty($valor) ? $valor['valor'] : 0;
    }
    // Consulta terceros directamente
    try {
        $sql = "SELECT `nom_tercero` FROM `tb_terceros` WHERE `id_tercero_api` = {$datosDoc['id_tercero']}";
        $rs = $cmd->query($sql);
        $dat_ter = $rs->fetch();
        $tercero = !empty($dat_ter) ? $dat_ter['nom_tercero'] : '---';
    } catch (PDOException $e) {
        $tercero = '---';
    }
}
$datosDoc['id_ref_ctb'] = $datosDoc['id_ref_ctb'] == '' ? 0 : $datosDoc['id_ref_ctb'];
try {
    $sql = "SELECT
                `id_ctb_doc`
                , SUM(IFNULL(`debito`,0)) AS `debito`
                , SUM(IFNULL(`credito`,0)) AS `credito`
            FROM
                `ctb_libaux`
            WHERE (`id_ctb_doc` = $id_doc_pag)";
    $rs = $cmd->query($sql);
    $totales = $rs->fetch();
} catch (PDOException $e) {
    echo $e->getCode() == 2002 ? 'Sin Conexión a Mysql (Error: 2002)' : 'Error: ' . $e->getCode();
}
try {
    $sql = "SELECT `numero` FROM `tes_referencia`  WHERE `estado` = 1";
    $rs = $cmd->query($sql);
    $pagos_ref = $rs->fetch();
    if ($rs->rowCount() > 0) {
        $ref = $pagos_ref['numero'];
        $chek = 'checked';
    } else {
        $ref = 0;
        $chek = '';
    }
} catch (PDOException $e) {
    echo $e->getCode() == 2002 ? 'Sin Conexión a Mysql (Error: 2002)' : 'Error: ' . $e->getCode();
}
$valor_teso = 0;
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
if ($tipo_dato == '9') {
    if ($tipo_dato == '9') {
        $id_arq = $id_doc_pag;
    }
    try {
        $sql = "SELECT
                    `tes_causa_arqueo`.`id_causa_arqueo`
                    ,`ctb_doc`.`id_ctb_doc`
                    ,`ctb_doc`.`id_manu`
                    , `ctb_doc`.`fecha`
                    , `ctb_doc`.`id_tercero`
                    , `ctb_doc`.`detalle`
                    , SUM(`tes_causa_arqueo`.`valor_arq`) as valor
                FROM
                    `tes_causa_arqueo`
                    INNER JOIN `ctb_doc` 
                        ON (`tes_causa_arqueo`.`id_ctb_doc` = `ctb_doc`.`id_ctb_doc`)
                WHERE `tes_causa_arqueo`.`id_ctb_doc` = $id_arq";
        $rs = $cmd->query($sql);
        $arqueo = $rs->fetch();
        $fecha_arq = $arqueo['fecha'];
        $valor_teso = $arqueo['valor'];
        $valor_pago = $valor_teso;
    } catch (PDOException $e) {
        echo $e->getCode() == 2002 ? 'Sin Conexión a Mysql (Error: 2002)' : 'Error: ' . $e->getCode();
    }
}
try {
    if ($id_doc_rad == 0) {
        $sql = "SELECT
                    `ctb_referencia`.`id_ctb_referencia`
                    , `ctb_referencia`.`nombre`
                FROM
                    `ctb_referencia`
                    INNER JOIN `ctb_fuente` 
                        ON (`ctb_referencia`.`id_ctb_fuente` = `ctb_fuente`.`id_doc_fuente`)
                WHERE (`ctb_fuente`.`id_doc_fuente` = $tipo_dato)";
    } else {
        $sql = "SELECT `id_ctb_referencia`,`nombre` FROM `ctb_referencia` WHERE `id_ctb_referencia` = {$datosDoc['id_ref_ctb']}";
    }
    $rs = $cmd->query($sql);
    $referencia = $rs->fetchAll();
    $rs->closeCursor();
    unset($rs);
} catch (PDOException $e) {
    echo $e->getCode() == 2002 ? 'Sin Conexión a Mysql (Error: 2002)' : 'Error: ' . $e->getCode();
}

try {
    $sql = "SELECT
                ctb_referencia.accion_pto
            FROM
                ctb_referencia
            WHERE ctb_referencia.id_ctb_referencia =" . $datosDoc['id_ref_ctb']
        . " AND id_ctb_fuente = $tipo_dato";
    $rs = $cmd->query($sql);
    $obj_referencia = $rs->fetch();
    if (empty($obj_referencia)) {
        $obj_referencia['accion_pto'] = 0;
    }
} catch (PDOException $e) {
    echo $e->getCode() == 2002 ? 'Sin Conexión a Mysql (Error: 2002)' : 'Error: ' . $e->getCode();
}

// Construir opciones del select de referencia
$optionsReferencia = '';
foreach ($referencia as $rf) {
    $selected = ($datosDoc['id_ref_ctb'] == $rf['id_ctb_referencia']) ? 'selected' : '';
    $optionsReferencia .= '<option value="' . $rf['id_ctb_referencia'] . '" ' . $selected . '>' . $rf['nombre'] . '</option>';
}

// Permisos
$peReg = ($permisos->PermisosUsuario($opciones, 5601, 2) || $id_rol == 1) ? '1' : '0';

// Variables para el formulario
$fecha = date('Y-m-d', strtotime($datosDoc['fecha']));
$fuente = $datosDoc['fuente'] ?? 'DOCUMENTO';
$estado = $datosDoc['estado'] ?? 1;
$id_tercero = $datosDoc['id_tercero'] ?? 0;
$id_ref = $datosDoc['id_ref'] ?? '';
$detalle = $datosDoc['detalle'] ?? '';
$val_pagado = $datosDoc['val_pagado'] ?? 0;
$accion_pto = $obj_referencia['accion_pto'] ?? 0;

// Construir secciones condicionales
$seccionArqueoCaja = '';
if ($tipo_dato == '9') {
    $btnArqueo = ($estado == 1 || $tipo_dato == '9') ? '<button class="btn btn-outline-success" onclick="CargaArqueoCajaTes(' . $id_doc_pag . ',0)"><span class="fas fa-cash-register fa-lg"></span></button>' : '';
    $seccionArqueoCaja = <<<HTML
    <div class="row mb-1">
        <div class="col-2">
            <label for="arqueo_caja" class="small">ARQUEO DE CAJA:</label>
        </div>
        <div class="col-4">
            <div class="input-group input-group-sm">
                <input type="text" name="arqueo_caja" id="arqueo_caja" value="{$valor_teso}" class="form-control form-control-sm bg-input" style="text-align: right;" required readonly>
                {$btnArqueo}
            </div>
        </div>
    </div>
HTML;
}

$seccionCajaMenor = '';
if ($tipo_dato == '13' || $tipo_dato == '14' || $tipo_dato == '15') {
    $btnCajaMenor = ($estado == 1) ? '<button class="btn btn-outline-success" onclick="cargaLegalizacionCajaMenor(\'' . $id_cop . '\')"><span class="fas fa-cash-register fa-lg"></span></button>' : '';
    $seccionCajaMenor = <<<HTML
    <div class="row mb-1">
        <div class="col-2">
            <label for="arqueo_caja" class="small">CAJA MENOR:</label>
        </div>
        <div class="col-4">
            <div class="input-group input-group-sm">
                <input type="text" name="arqueo_caja" id="arqueo_caja" value="{$valor_pago}" class="form-control form-control-sm bg-input" style="text-align: right;" required readonly>
                {$btnCajaMenor}
            </div>
        </div>
    </div>
HTML;
}

$seccionPresupuesto = '';
if (($tipo_dato == '6' || $tipo_dato == '16' || $tipo_dato == '7' || $tipo_dato == '12') && $id_doc_rad == 0) {
    $btnPresupuesto = ($estado == 1) ? '<button class="btn btn-outline-success btn-sm" id="btn_cargar_presupuesto"><span class="fas fa-plus fa-lg"></span></button>' : '';
    $seccionPresupuesto = <<<HTML
    <div class="row mb-1">
        <div class="col-2">
            <label for="arqueo_caja" class="small">PRESUPUESTO:</label>
        </div>
        <div class="col-4">
            <div class="input-group input-group-sm">
                <input type="text" name="arqueo_caja" id="arqueo_caja" value="{$valor_pago}" class="form-control form-control-sm bg-input" style="text-align: right;" required readonly>
                {$btnPresupuesto}
            </div>
        </div>
    </div>
HTML;
}

$seccionImputacion = '';
if ($id_cop > 0 && $_SESSION['pto'] == '1') {
    $btnImputacion = ($estado == 1 && $id_doc_pag > 0) ? '<button class="btn btn-outline-success" onclick="cargaListaCausaciones(this)"><span class="fas fa-plus fa-lg"></span></button>' : '';
    $seccionImputacion = <<<HTML
    <div class="row mb-1">
        <div class="col-2">
            <label for="valor" class="small">IMPUTACION:</label>
        </div>
        <div class="col-4">
            <div class="input-group input-group-sm">
                <input type="text" name="valor" id="valor" value="{$val_pagado}" class="form-control bg-input" style="text-align: right;" readonly>
                {$btnImputacion}
            </div>
        </div>
    </div>
HTML;
}

$campo_req = '';
if ($tipo_dato == '6' || $tipo_dato == '16' || $tipo_dato == '7' || $tipo_dato == '12') {
    $campo_req = 'readonly';
}

$btnFormaPago = ($estado == 1 && $id_doc_pag > 0) ? '<button class="btn btn-outline-primary" onclick="cargaFormaPago(' . $id_cop . ',0,this)"><span class="fas fa-wallet fa-lg"></span></button>' : '';

$seccionFormaPago = '';
if (true) {
    if ($id_doc_rad > 0) {
        $forma = 'FORMA DE RECAUDO :';
    } else {
        $forma = 'FORMA DE PAGO :';
    }
    $seccionFormaPago = <<<HTML
    <div class="row mb-1">
        <div class="col-2">
            <label class="small">{$forma}</label>
        </div>
        <div class="col-4">
            <div class="input-group input-group-sm">
                <input type="text" name="forma_pago" id="forma_pago" value="{$valor_pago}" class="form-control bg-input" style="text-align: right;" {$campo_req}>
                {$btnFormaPago}
            </div>
        </div>
    </div>
HTML;
}
$btnGuardar = $estado == 1 ? '<button type="button" class="btn btn-warning btn-sm" id="GuardaDocMvtoPag" text="' . $id_doc_pag . '">Guardar</button>' : '';
if ($estado == 1 && $id_doc_pag > 0) {
    $btnGenMov =
        <<<HTML
            <button type="button" class="btn btn-primary btn-sm" onclick="generaMovimientoPag('{$id_doc_pag}')">Generar movimiento</button>
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
            <input type="text" name="bTercero" id="bTercero" class="form-control form-control-sm bg-input bTercero" required>
            <input type="hidden" name="idTercero" id="idTercero" value="0">
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
        <b>REGISTRO DE MOVIMIENTOS CONTABLES</b>
    </div>
    <div class="card-body p-2 bg-wiev">
        <input type="hidden" id="tipo_var" value="{$tipo_var}">
        <input type="hidden" id="peReg" value="{$peReg}">
        <input type="hidden" id="valor_teso" value="{$valor_teso}">
        
        <form id="formGetMvtoTes">
            <input type="hidden" id="fec_cierre" value="{$fecha_cierre}">
            
            <div class="row mb-2">
                <div class="col-md-2">
                    <label class="small fw-bold">NUMERO ACTO:</label>
                </div>
                <div class="col-md-10">
                    <input type="number" name="numDoc" id="numDoc" class="form-control form-control-sm bg-input" value="{$id_manu}">
                    <input type="hidden" id="tipodato" name="tipodato" value="{$tipo_dato}">
                    <input type="hidden" id="id_cop_pag" name="id_cop_pag" value="{$id_cop}">
                    <input type="hidden" id="id_arqueo" name="id_arqueo" value="{$id_arq}">
                    <input type="hidden" id="id_doc_rad" name="id_doc_rad" value="{$id_doc_rad}">
                    <input type="hidden" id="hd_accion_pto" name="hd_accion_pto" value="{$accion_pto}">
                </div>
            </div>
            
            <div class="row mb-2">
                <div class="col-md-2">
                    <label class="small fw-bold">FECHA:</label>
                </div>
                <div class="col-md-10">
                    <input type="date" name="fecha" id="fecha" class="form-control form-control-sm bg-input" value="{$fecha}">
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
                    <label class="small fw-bold">CONCEPTO:</label>
                </div>
                <div class="col-md-10">
                    <select name="ref_mov" id="ref_mov" class="form-select form-select-sm bg-input" readonly>
                        <option value="0">---</option>
                        {$optionsReferencia}
                    </select>
                </div>
            </div>
            
            <div class="row mb-2">
                <div class="col-md-2">
                    <label class="small fw-bold">REFERENCIA:</label>
                </div>
                <div class="col-md-10">
                    <input type="text" name="referencia" id="referencia" value="{$id_ref}" class="form-control form-control-sm bg-input" readonly>
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
            
            {$seccionArqueoCaja}
            {$seccionCajaMenor}
            {$seccionPresupuesto}
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
