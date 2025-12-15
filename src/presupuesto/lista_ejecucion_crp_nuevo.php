<?php
session_start();
if (!isset($_SESSION['user'])) {
    header('Location: ../../index.php');
    exit();
}
include '../../config/autoloader.php';
$id_rol = $_SESSION['rol'];
$id_user = $_SESSION['id_user'];

use Config\Clases\Plantilla;
use Src\Common\Php\Clases\Permisos;

$permisos = new Permisos();
$opciones = $permisos->PermisoOpciones($id_user);
$peReg =  $permisos->PermisosUsuario($opciones, 5401, 2) || $id_rol == 1 ? 1 : 0;
$host = Plantilla::getHost();

include '../financiero/consultas.php';

// Consulta tipo de presupuesto
$id_crp = isset($_POST['id_crp']) ? $_POST['id_crp'] : 0;
$id_cdp = isset($_POST['id_cdp']) ? $_POST['id_cdp'] : 0;
$id_pto = $_POST['id_pto'];
$vigencia = $_SESSION['vigencia'];
$automatico = '';

// Consulto los datos generales del nuevo registro presupuesal
try {
    $cmd = \Config\Clases\Conexion::getConexion();
    $sql = "SELECT
                MAX(`id_manu`) AS `id_manu` 
            FROM
                `pto_crp`
            WHERE (`id_pto` = $id_pto)";
    $rs = $cmd->query($sql);
    $consecutivo = $rs->fetch();
    $id_manu = !empty($consecutivo) ? $consecutivo['id_manu'] + 1 : 1;
} catch (PDOException $e) {
    echo $e->getCode() == 2002 ? 'Sin Conexión a Mysql (Error: 2002)' : 'Error: ' . $e->getCode();
}
try {
    $cmd = \Config\Clases\Conexion::getConexion();
    $sql = "SELECT
            `objeto`,`fecha`
            FROM `pto_cdp`
            WHERE `id_pto_cdp` = $id_cdp";
    $rs = $cmd->query($sql);
    $objeto_ = $rs->fetch();
    $objeto = !empty($objeto_) ? $objeto_['objeto'] : '';
    $fecha_cdp = !empty($objeto_) ? $objeto_['fecha'] : date('Y-m-d');
} catch (PDOException $e) {
    echo $e->getCode() == 2002 ? 'Sin Conexión a Mysql (Error: 2002)' : 'Error: ' . $e->getCode();
}
try {
    $cmd = \Config\Clases\Conexion::getConexion();
    $sql = "SELECT
                `ctt_adquisiciones`.`id_cdp`
                , `ctt_adquisiciones`.`id_tercero`
                , `tb_terceros`.`nit_tercero`
                , `tb_terceros`.`nom_tercero`
                , `ctt_contratos`.`num_contrato`
            FROM
                `ctt_adquisiciones`
                INNER JOIN `tb_terceros` 
                    ON (`ctt_adquisiciones`.`id_tercero` = `tb_terceros`.`id_tercero_api`)
                LEFT JOIN `ctt_contratos` 
                    ON (`ctt_adquisiciones`.`id_adquisicion` = `ctt_contratos`.`id_compra`)
            WHERE (`ctt_adquisiciones`.`id_cdp` = $id_cdp)
            UNION ALL
            SELECT
                `ctt_novedad_adicion_prorroga`.`id_cdp`
                , `ctt_adquisiciones`.`id_tercero`
                , `tb_terceros`.`nit_tercero`
                , `tb_terceros`.`nom_tercero`
                , `ctt_contratos`.`num_contrato`
            FROM
                `ctt_contratos`
                INNER JOIN `ctt_novedad_adicion_prorroga` 
                    ON (`ctt_contratos`.`id_contrato_compra` = `ctt_novedad_adicion_prorroga`.`id_adq`)
                INNER JOIN `ctt_adquisiciones` 
                    ON (`ctt_contratos`.`id_compra` = `ctt_adquisiciones`.`id_adquisicion`)
                INNER JOIN `tb_terceros` 
                    ON (`ctt_adquisiciones`.`id_tercero` = `tb_terceros`.`id_tercero_api`)
            WHERE (`ctt_novedad_adicion_prorroga`.`id_cdp` = $id_cdp)";
    $rs = $cmd->query($sql);
    $ctt = $rs->fetch();
    $id_ter = !empty($ctt) ? $ctt['id_tercero'] : 0;
    $num_contrato = !empty($ctt) ? $ctt['num_contrato'] : '';
} catch (PDOException $e) {
    echo $e->getCode() == 2002 ? 'Sin Conexión a Mysql (Error: 2002)' : 'Error: ' . $e->getCode();
}
try {
    $cmd = \Config\Clases\Conexion::getConexion();
    $sql = "SELECT 
                `pto_crp`.`id_pto`
                , `pto_crp`.`estado`
                , DATE_FORMAT(`pto_crp`.`fecha`,'%Y-%m-%d') AS `fecha`
                , `pto_crp`.`id_manu`
                , `pto_crp`.`objeto`
                , `pto_crp`.`id_tercero_api`
                , `pto_crp`.`num_contrato`
                , `pto_crp`.`tesoreria` 
                , `tb_terceros`.`nom_tercero`
                , `tb_terceros`.`nit_tercero`
            FROM `pto_crp` 
                INNER JOIN `tb_terceros`
                    ON (`pto_crp`.`id_tercero_api` = `tb_terceros`.`id_tercero_api`)
            WHERE `id_pto_crp` = $id_crp";
    $rs = $cmd->query($sql);
    $datosCRP = $rs->fetch();
    if (empty($datosCRP)) {
        $datosCRP = [
            'tesoreria' => 0,
            'estado' => 1,
            'id_pto' => '',
            'fecha' => date('Y-m-d'),
            'id_manu' => $id_manu,
            'objeto' => $objeto,
            'num_contrato' => $num_contrato,
            'id_tercero_api' => 0,
            'nom_tercero' => '---',
            'nit_tercero' => '---'
        ];
    } else {
        $automatico = 'readonly';
    }
} catch (PDOException $e) {
    echo $e->getCode() == 2002 ? 'Sin Conexión a Mysql (Error: 2002)' : 'Error: ' . $e->getCode();
}
$datosCRP['checked'] = $datosCRP['tesoreria'] == 1 ? 'checked' : '';
$fec_cierre_periodo = fechaCierre($_SESSION['vigencia'], 54, $cmd);

$cmd = null;
if ($id_ter == 0) {
    if ($datosCRP['id_tercero_api'] == 0) {
        $tercero = '---';
        $ccnit = '---';
    } else {
        $tercero = $datosCRP['nom_tercero'];
        $ccnit = $datosCRP['nit_tercero'];
    }
} else {
    $tercero = $ctt['nom_tercero'];
    $ccnit = $ctt['nit_tercero'];
    $datosCRP['id_tercero_api'] = $id_ter;
}
$fecha_cierre =  date("Y-m-d", strtotime($fecha_cdp));
$fecha_max = date("Y-m-d", strtotime($vigencia . '-12-31'));

// Preparar botón de acción
$botonAccion = '';
if ($permisos->PermisosUsuario($opciones, 5401, 2) || $id_rol == 1) {
    $opcion = $id_crp == 0 ? 'Registrar' : 'Actualizar';
    $text = $id_crp == 0 ? 1 : 2;
    if ($datosCRP['estado'] == 1) {
        $botonAccion = '<button class="btn btn-info btn-sm" id="registrarMovDetalle" text="' . $text . '">' . $opcion . '</button>';
    }
}

$content =
    <<<HTML
    <div class="card w-100">
        <div class="card-header bg-sofia text-white">
            <button class="btn btn-sm me-1 p-0" title="Regresar" onclick="cambiaListado(2)"><i class="fas fa-arrow-left fa-lg"></i></button>
            <b>DETALLE CERTIFICADO DE REGISTRO PRESUPUESTAL {$datosCRP['estado']}</b>
        </div>
        <div class="card-body p-3 bg-wiev">
            <form id="formGestionaCrp">
                <input type="hidden" name="fec_cierre" id="fec_cierre" value="{$fec_cierre_periodo}">
                <input type="hidden" id="id_pto_ppto" name="id_pto_presupuestos" value="{$id_pto}">
                <input type="hidden" name="id_cdp" id="id_cdp" value="{$id_cdp}">
                <input type="hidden" name="id_crp" id="id_crp" value="{$id_crp}">
                <input type="hidden" name="id_tercero" id="id_tercero" value="{$datosCRP['id_tercero_api']}">
                <input type="hidden" name="id_pto_save" id="id_pto_save" value="">
                
                <div class="row mb-3">
                    <div class="col-md-6">
                        <label for="numCdp" class="small fw-bold">NUMERO CRP:</label>
                        <input type="number" name="numCdp" id="numCdp" class="form-control form-control-sm" value="{$datosCRP['id_manu']}" {$automatico}>
                    </div>
                    <div class="col-md-6">
                        <label for="fecha" class="small fw-bold">FECHA:</label>
                        <input type="date" name="fecha" id="fecha" class="form-control form-control-sm" min="{$fecha_cierre}" max="{$fecha_max}" value="{$datosCRP['fecha']}" {$automatico}>
                    </div>
                </div>
                
                <div class="row mb-3">
                    <div class="col-md-12">
                        <label for="tercero" class="small fw-bold">TERCERO:</label>
                        <input type="text" id="tercero" class="form-control form-control-sm" value="{$tercero}" required {$automatico}>
                    </div>
                </div>
                
                <div class="row mb-3">
                    <div class="col-md-12">
                        <label for="objeto" class="small fw-bold">OBJETO:</label>
                        <textarea id="objeto" type="text" name="objeto" class="form-control form-control-sm" rows="3" required {$automatico}>{$datosCRP['objeto']}</textarea>
                    </div>
                </div>
                
                <div class="row mb-3">
                    <div class="col-md-6">
                        <label for="contrato" class="small fw-bold">NO CONTRATO:</label>
                        <input type="text" name="contrato" id="contrato" class="form-control form-control-sm" value="{$datosCRP['num_contrato']}" {$automatico}>
                    </div>
                    <div class="col-md-6">
                        <label for="chDestTes" class="small fw-bold" title="Marcar para enviar directamente a tesorería">TESORERÍA:</label>
                        <div class="form-check">
                            <input type="checkbox" name="chDestTes" id="chDestTes" class="form-check-input" title="Marcar para enviar directamente a tesorería" {$datosCRP['checked']} {$automatico}>
                            <label class="form-check-label small" for="chDestTes">Enviar a tesorería</label>
                        </div>
                    </div>
                </div>
                
                <div class="table-responsive mt-4">
                    <table id="tableEjecCrpNuevo" class="table table-striped table-bordered table-sm table-hover shadow w-100">
                        <thead>
                            <tr class="text-center">
                                <th class="bg-sofia">Codigo</th>
                                <th class="bg-sofia">Valor</th>
                                <th class="bg-sofia">Acciones</th>
                            </tr>
                        </thead>
                        <tbody id="modificarEjecCrpNuevo">
                        </tbody>
                    </table>
                </div>
            </form>
            
            <div class="text-center mt-4">
                {$botonAccion}
                <button type="button" class="btn btn-primary btn-sm" onclick="imprimirFormatoCrp({$id_crp})">
                    <span class="fas fa-print"></span> Imprimir
                </button>
                <button type="button" class="btn btn-danger btn-sm" onclick="cambiaListado(2)">
                    <span class="fas fa-arrow-left"></span> Volver
                </button>
            </div>
        </div>
    </div>
    HTML;

// Preparar el estado del checkbox
$plantilla = new Plantilla($content, 2);
$plantilla->addCssFile("{$host}/assets/css/jquery-ui.css?v=" . date("YmdHis"));
$plantilla->addScriptFile("{$host}/assets/js/jquery-ui.js?v=" . date("YmdHis"));
$plantilla->addScriptFile("{$host}/src/presupuesto/js/libros_aux_pto/common.js?v=" . date("YmdHis"));
$plantilla->addScriptFile("{$host}/src/presupuesto/js/funcionpresupuesto.js?v=" . date("YmdHis"));
$modal = $plantilla->getModal('divModalForms', 'divTamModalForms', 'divForms');
$plantilla->addModal($modal);
echo $plantilla->render();
