<?php
session_start();
if (!isset($_SESSION['user'])) {
    header('Location: ../../../index.php');
    exit();
}
include_once '../../../config/autoloader.php';

use Config\Clases\Conexion;
use Config\Clases\Plantilla;
use Src\Common\Php\Clases\Permisos;

$host = Plantilla::getHost();
$numeral = 1;
$permisos = new Permisos();

$id_rol = $_SESSION['rol'];
$id_user = $_SESSION['id_user'];
$opciones = $permisos->PermisoOpciones($id_user);
$peReg =  $permisos->PermisosUsuario($opciones, 5302, 0) || $id_rol == 1 ? 1 : 0;

$id_adq = isset($_POST['detalles']) ? $_POST['detalles'] : exit('Acción no permitida');

try {
    $cmd = \Config\Clases\Conexion::getConexion();

    $sql = "SELECT
                `ctt_adquisiciones`.`id_adquisicion`
                , `tb_tipo_compra`.`id_tipo`
                , `tb_tipo_compra`.`tipo_compra`
                , `tb_tipo_bien_servicio`.`id_tipo`
                , `ctt_adquisiciones`.`id_tipo_bn_sv`
            FROM
                `tb_tipo_contratacion`
                INNER JOIN `tb_tipo_compra` 
                    ON (`tb_tipo_contratacion`.`id_tipo_compra` = `tb_tipo_compra`.`id_tipo`)
                INNER JOIN `tb_tipo_bien_servicio` 
                    ON (`tb_tipo_bien_servicio`.`id_tipo` = `tb_tipo_contratacion`.`id_tipo`)
                INNER JOIN `ctt_adquisiciones` 
                    ON (`ctt_adquisiciones`.`id_tipo_bn_sv` = `tb_tipo_bien_servicio`.`id_tipo_b_s`)
            WHERE `ctt_adquisiciones`.`id_adquisicion` = $id_adq";
    $rs = $cmd->query($sql);
    $tipo_adq = $rs->fetch();
    $cmd = null;
} catch (PDOException $e) {
    echo $e->getCode() == 2002 ? 'Sin Conexión a Mysql (Error: 2002)' : 'Error: ' . $e->getMessage();
}
$tp_bs = $tipo_adq['id_tipo_bn_sv'];
try {
    $cmd = \Config\Clases\Conexion::getConexion();

    $sql = "SELECT
                `id_relacion`, `id_formato`
            FROM
                `ctt_formatos_doc_rel`
            WHERE (`id_tipo_bn_sv` = $tp_bs)";
    $rs = $cmd->query($sql);
    $formatos = $rs->fetchAll();
    $posicion = [];
    foreach ($formatos as $f) {
        $posicion[$f['id_formato']] = $f['id_relacion'];
    }
    $cmd = null;
} catch (PDOException $e) {
    echo $e->getCode() == 2002 ? 'Sin Conexión a Mysql (Error: 2002)' : 'Error: ' . $e->getMessage();
}
try {
    $cmd = \Config\Clases\Conexion::getConexion();

    $sql = "SELECT `id_sede`, `nom_sede` AS `nombre` FROM `tb_sedes`";
    $rs = $cmd->query($sql);
    $sedes = $rs->fetchAll();
    $cmd = null;
} catch (PDOException $e) {
    echo $e->getCode() == 2002 ? 'Sin Conexión a Mysql (Error: 2002)' : 'Error: ' . $e->getMessage();
}
try {
    $cmd = \Config\Clases\Conexion::getConexion();

    $sql = "SELECT
                `far_centrocosto_area`.`id_area`,`far_centrocosto_area`.`id_centrocosto`, `tb_centrocostos`.`nom_centro`
            FROM
                `far_centrocosto_area`
                INNER JOIN `tb_centrocostos` 
                    ON (`far_centrocosto_area`.`id_centrocosto` = `tb_centrocostos`.`id_centro`) 
            ORDER BY `tb_centrocostos`.`nom_centro` ASC";
    $rs = $cmd->query($sql);
    $centros_costo = $rs->fetchAll();
    $cmd = null;
} catch (PDOException $e) {
    echo $e->getCode() == 2002 ? 'Sin Conexión a Mysql (Error: 2002)' : 'Error: ' . $e->getMessage();
}
try {
    $cmd = \Config\Clases\Conexion::getConexion();

    $sql = "SELECT `id`, `descripcion` FROM `ctt_estado_adq`";
    $rs = $cmd->query($sql);
    $estado_adq = $rs->fetchAll();
    $cmd = null;
} catch (PDOException $e) {
    echo $e->getCode() == 2002 ? 'Sin Conexión a Mysql (Error: 2002)' : 'Error: ' . $e->getMessage();
}
try {
    $cmd = \Config\Clases\Conexion::getConexion();

    $sql = "SELECT
                `ctt_destino_contrato`.`id_destino`
                , `far_centrocosto_area`.`id_area`
                , `far_centrocosto_area`.`id_sede`
                , `far_centrocosto_area`.`id_centrocosto`
                , `ctt_destino_contrato`.`horas_mes`
            FROM
                `ctt_destino_contrato`
            INNER JOIN `far_centrocosto_area` 
                ON (`ctt_destino_contrato`.`id_area_cc` = `far_centrocosto_area`.`id_area`)
            WHERE `ctt_destino_contrato`.`id_adquisicion` = $id_adq ORDER BY `ctt_destino_contrato`.`id_destino` ASC";
    $rs = $cmd->query($sql);
    $destinos = $rs->fetchAll();
    $cmd = null;
} catch (PDOException $e) {
    echo $e->getCode() == 2002 ? 'Sin Conexión a Mysql (Error: 2002)' : 'Error: ' . $e->getMessage();
}
try {
    $cmd = \Config\Clases\Conexion::getConexion();

    $sql = "SELECT 
                `ctt_adquisiciones`.`id_tipo_bn_sv`
                , `ctt_modalidad`.`modalidad`
                , `ctt_adquisiciones`.`id_adquisicion`
                , `ctt_adquisiciones`.`fecha_adquisicion`
                , `ctt_adquisiciones`.`estado`
                , `ctt_adquisiciones`.`objeto`
                , `ctt_adquisiciones`.`id_cont_api`
                , `ctt_adquisiciones`.`id_supervision`
                , `ctt_adquisiciones`.`id_orden`
                , `tb_tipo_bien_servicio`.`filtro_adq`
                , `ctt_adquisiciones`.`id_tercero`
                , `tb_terceros`.`nit_tercero`
                , `tb_terceros`.`nom_tercero`
            FROM
                `ctt_adquisiciones`
            INNER JOIN `ctt_modalidad` 
                ON (`ctt_adquisiciones`.`id_modalidad` = `ctt_modalidad`.`id_modalidad`)
            INNER JOIN `tb_tipo_bien_servicio` 
                ON (`ctt_adquisiciones`.`id_tipo_bn_sv` = `tb_tipo_bien_servicio`.`id_tipo_b_s`)
            LEFT JOIN `tb_terceros` 
                ON (`ctt_adquisiciones`.`id_tercero` = `tb_terceros`.`id_tercero_api`)
            WHERE `id_adquisicion` = $id_adq";
    $rs = $cmd->query($sql);
    $adquisicion = $rs->fetch();
    $cmd = null;
} catch (PDOException $e) {
    echo $e->getCode() == 2002 ? 'Sin Conexión a Mysql (Error: 2002)' : 'Error: ' . $e->getMessage();
}
try {
    $cmd = \Config\Clases\Conexion::getConexion();

    $sql = "SELECT
                `ctt_adquisiciones`.`id_adquisicion`
                , `tb_tipo_contratacion`.`id_tipo_compra`
            FROM
                `ctt_adquisiciones`
                INNER JOIN `tb_tipo_bien_servicio` 
                    ON (`ctt_adquisiciones`.`id_tipo_bn_sv` = `tb_tipo_bien_servicio`.`id_tipo_b_s`)
                INNER JOIN `tb_tipo_contratacion` 
                    ON (`tb_tipo_bien_servicio`.`id_tipo` = `tb_tipo_contratacion`.`id_tipo`)
            WHERE  `ctt_adquisiciones`.`id_adquisicion` = $id_adq";
    $rs = $cmd->query($sql);
    $tipo_compra = $rs->fetch();
    $cmd = null;
} catch (PDOException $e) {
    echo $e->getCode() == 2002 ? 'Sin Conexión a Mysql (Error: 2002)' : 'Error: ' . $e->getMessage();
}
try {
    $cmd = \Config\Clases\Conexion::getConexion();


    if ($adquisicion['id_orden'] == '') {
        $sql = "SELECT
                    `ctt_bien_servicio`.`bien_servicio`
                    , `ctt_orden_compra_detalle`.`cantidad`
                    , `ctt_orden_compra_detalle`.`val_unid`
                    , `ctt_orden_compra_detalle`.`id_detalle`
                    , '0' AS `iva`
                FROM
                    `ctt_orden_compra_detalle`
                    INNER JOIN `ctt_orden_compra` 
                        ON (`ctt_orden_compra_detalle`.`id_oc` = `ctt_orden_compra`.`id_oc`)
                    INNER JOIN `ctt_bien_servicio` 
                        ON (`ctt_orden_compra_detalle`.`id_servicio` = `ctt_bien_servicio`.`id_b_s`)
                WHERE (`ctt_orden_compra`.`id_adq` = $id_adq)";
    } else {
        $sql = "SELECT
                    `far_alm_pedido_detalle`.`id_ped_detalle` AS `id_detalle`
                    , `far_medicamentos`.`nom_medicamento` AS `bien_servicio`
                    , `far_alm_pedido_detalle`.`cantidad`
                    , `far_alm_pedido_detalle`.`valor` AS `val_unid`
                    , `far_alm_pedido_detalle`.`iva`
                    , `far_alm_pedido_detalle`.`aprobado`
                FROM
                    `far_alm_pedido_detalle`
                    INNER JOIN `far_medicamentos` 
                        ON (`far_alm_pedido_detalle`.`id_medicamento` = `far_medicamentos`.`id_med`)
                WHERE (`far_alm_pedido_detalle`.`id_pedido` = {$adquisicion['id_orden']})";
    }
    $rs = $cmd->query($sql);
    $detalles_orden = $rs->fetchAll(PDO::FETCH_ASSOC);
    $cmd = null;
} catch (PDOException $e) {
    echo $e->getCode() == 2002 ? 'Sin Conexión a Mysql (Error: 2002)' : 'Error: ' . $e->getMessage();
}

try {
    $cmd = \Config\Clases\Conexion::getConexion();

    $sql = "SELECT
                `id_est_prev` , `id_compra`, `fec_ini_ejec`, `fec_fin_ejec`, `id_forma_pago`, `id_supervisor`, `id_user_reg`
            FROM
                `ctt_estudios_previos`
            WHERE `id_compra` = '$id_adq' LIMIT 1";
    $rs = $cmd->query($sql);
    $estudios = $rs->fetch();

    $id_estudio = !empty($estudios['id_est_prev']) ? $estudios['id_est_prev'] : '';
    $cmd = null;
} catch (PDOException $e) {
    echo $e->getCode() == 2002 ? 'Sin Conexión a Mysql (Error: 2002)' : 'Error: ' . $e->getMessage();
}
try {
    $cmd = \Config\Clases\Conexion::getConexion();

    $sql = "SELECT
                `ctt_adquisiciones`.`id_adquisicion`
                , `pto_cdp`.`id_manu`
                , `pto_cdp`.`objeto`
                , `pto_cdp`.`fecha`
                , `pto_cdp_detalle`.`valor`
            FROM
                `ctt_adquisiciones`
                INNER JOIN `pto_cdp` 
                    ON (`ctt_adquisiciones`.`id_cdp` = `pto_cdp`.`id_pto_cdp`)
                INNER JOIN `pto_cdp_detalle` 
                    ON (`pto_cdp_detalle`.`id_pto_cdp` = `pto_cdp`.`id_pto_cdp`)
            WHERE (`ctt_adquisiciones`.`id_adquisicion` = $id_adq)
            LIMIT 1";
    $rs = $cmd->query($sql);
    $cdp = $rs->fetch();
    $cmd = null;
} catch (PDOException $e) {
    echo $e->getCode() == 2002 ? 'Sin Conexión a Mysql (Error: 2002)' : 'Error: ' . $e->getMessage();
}
try {
    $cmd = \Config\Clases\Conexion::getConexion();

    $sql = "SELECT
                `pto_crp`.`id_manu`
                , `pto_crp`.`fecha`
                , `pto_crp_detalle`.`valor`
                , `ctt_adquisiciones`.`id_adquisicion`
                , `pto_crp`.`objeto`
            FROM
                `pto_crp`
                INNER JOIN `pto_cdp` 
                    ON (`pto_crp`.`id_cdp` = `pto_cdp`.`id_pto_cdp`)
                INNER JOIN `pto_crp_detalle` 
                    ON (`pto_crp_detalle`.`id_pto_crp` = `pto_crp`.`id_pto_crp`)
                INNER JOIN `ctt_adquisiciones` 
                    ON (`ctt_adquisiciones`.`id_cdp` = `pto_cdp`.`id_pto_cdp`)
            WHERE (`ctt_adquisiciones`.`id_adquisicion` = $id_adq)
            LIMIT 1";
    $rs = $cmd->query($sql);
    $crp = $rs->fetch();
    $cmd = null;
} catch (PDOException $e) {
    echo $e->getCode() == 2002 ? 'Sin Conexión a Mysql (Error: 2002)' : 'Error: ' . $e->getMessage();
}
try {
    $cmd = \Config\Clases\Conexion::getConexion();

    $sql = "SELECT
                `id_contrato_compra`
                , `id_compra`
                , `fec_ini`
                , `fec_fin`
                , `val_contrato`
                , `id_forma_pago`
                , `id_supervisor`
                , `id_secop`
                , `num_contrato`
            FROM
                `ctt_contratos`
            WHERE (`id_compra` = $id_adq) LIMIT 1";
    $rs = $cmd->query($sql);
    $contrato = $rs->fetch();
    $cmd = null;
} catch (PDOException $e) {
    echo $e->getCode() == 2002 ? 'Sin Conexión a Mysql (Error: 2002)' : 'Error: ' . $e->getMessage();
}
function pesos($valor)
{
    if ($valor >= 0) {
        return '$' . number_format($valor, 2, ",", ".");
    } else {
        return '-$' . number_format($valor * (-1), 2);
    }
}
if (!empty($adquisicion)) {
    $idtbnsv = $adquisicion['id_tipo_bn_sv'];
    try {
        $cmd = \Config\Clases\Conexion::getConexion();

        $sql = "SELECT 
                    `id_b_s`, `tipo_compra`,`tb_tipo_contratacion`.`id_tipo`, `tipo_contrato`, `tipo_bn_sv`, `bien_servicio`
                FROM
                    `tb_tipo_contratacion`
                INNER JOIN `tb_tipo_compra` 
                    ON (`tb_tipo_contratacion`.`id_tipo_compra` = `tb_tipo_compra`.`id_tipo`)
                INNER JOIN `tb_tipo_bien_servicio` 
                    ON (`tb_tipo_bien_servicio`.`id_tipo` = `tb_tipo_contratacion`.`id_tipo`)
                INNER JOIN `ctt_bien_servicio` 
                    ON (`ctt_bien_servicio`.`id_tipo_bn_sv` = `tb_tipo_bien_servicio`.`id_tipo_b_s`)
                WHERE `id_tipo_b_s` = $idtbnsv
                ORDER BY `tipo_compra`,`tipo_contrato`, `tipo_bn_sv`, `bien_servicio`";
        $rs = $cmd->query($sql);
        $bnsv = $rs->fetchAll();
        $cmd = null;
    } catch (PDOException $e) {
        echo $e->getCode() == 2002 ? 'Sin Conexión a Mysql (Error: 2002)' : 'Error: ' . $e->getMessage();
    }
    $j = 0;

    $boton = $guardar = $cerrar = '';
    if (($permisos->PermisosUsuario($opciones, 5302, 1) || $id_rol == 1) && $adquisicion['estado'] < 6) {

        if ($adquisicion['filtro_adq'] == '0') {
            if ($adquisicion['estado'] == 1) {
                $cerrar = '<button type="button" class="btn btn-secondary btn-sm mr-1" id="cerrarOrdenServicio">Cerrar</button>';
            }
        } elseif (in_array($adquisicion['filtro_adq'], ['1', '2'])) {
            if (empty($adquisicion['id_orden'])) {
                $buttonText = $adquisicion['filtro_adq'] == '1' ? 'Orden Almacén' : 'Orden Activos Fijos';
                $boton = '<button type="button" class="btn btn-primary btn-sm listOrdenes mr-1" text="' . $adquisicion['filtro_adq'] . '">' . $buttonText . '</button>';
            }
            if ($adquisicion['estado'] == 1) {
                $guardar = '<button type="button" class="btn btn-success btn-sm mr-1" id="guardarOrden">Guardar</button>';
                $cerrar = '<button type="button" class="btn btn-secondary btn-sm mr-1" id="cerrarOrden">Cerrar</button>';
            }
        }
    }
    $estado_desc = '';
    $key_estado = array_search($adquisicion['estado'], array_column($estado_adq, 'id'));
    if ($key_estado !== false) {
        $estado_desc = $estado_adq[$key_estado]['descripcion'];
    }

    $tipo_contrato = '0';
    foreach ($bnsv as $bs) {
        if ($bs['id_tipo'] == '1') {
            $tipo_contrato = '1';
        }
    }

    $url_api = Conexion::Api();
    //API URL
    $url = $url_api . 'terceros/datos/res/listar/novedades_contrato/' . $adquisicion['id_cont_api'];
    $ch = curl_init($url);
    //curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type:application/json'));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    $result = curl_exec($ch);
    curl_close($ch);
    $nvdds = json_decode($result, true);
    $t_novdad = [];
    if (isset($nvdds)) {
        while (current($nvdds)) {
            $t_novdad[] =  key($nvdds);
            next($nvdds);
        }
    }
    $keyliq = array_search('liquidacion', $t_novdad);
    $keyter = array_search('terminacion', $t_novdad);
    $inactivo = '';
    $activar = 'novedadC';
    if (false !== $keyliq || false !== $keyter) {
        $inactivo = 'disabled';
        $activar = '';
    }

    $id_contrato_compra_val = isset($contrato['id_contrato_compra']) ? $contrato['id_contrato_compra'] : '';

    $content = <<<HTML

        <div class="card w-100">
            <div class="card-header bg-sofia text-white">
                <button class="btn btn-xs me-1 p-0" title="Regresar" onclick="window.history.back();"><i class="fas fa-arrow-left fa-lg"></i></button>
                <b>DETALLES DE ADQUISICIÓN</b>
            </div>
            <div class="card-body p-2 bg-wiev">
                <input type="hidden" id="peReg" value="{$peReg}">
                <div class="container-fluid p-2">
                    <div id="accordion">
                        <div class="card">
                            <div class="card-header card-header-detalles py-0 headings" id="headingOne">
                                <h5 class="mb-0">
                                    <a class="btn btn-link-acordeon sombra collapsed" data-bs-toggle="collapse" data-bs-target="#datosperson" aria-expanded="true" aria-controls="collapseOne">
                                        <div class="form-row">
                                            <div class="div-icono">
                                                <span class="fas fa-clipboard-list fa-lg" style="color: #3498DB;"></span>
                                            </div>
                                            <div>
                                                VIÑETA. DETALLES DE CONTRATACIÓN
                                            </div>
                                        </div>
                                    </a>
                                </h5>
                            </div>
                            <div id="datosperson" class="collapse show" aria-labelledby="headingOne" data-bs-parent="#accordion">
                                <div class="card-body">
                                    <div class="shadow detalles-empleado">
                                        <div class="row">
                                            <div class="div-mostrar bor-top-left col-md-4">
                                                <span class="lbl-mostrar pb-2">MODALIDAD CONTRATACIÓN</span>
                                                <div class="div-cont pb-2">{$adquisicion['modalidad']}</div>
                                            </div>
                                            <div class="div-mostrar col-md-2">
                                                <span class="lbl-mostrar pb-2">ADQUISICIÓN</span>
                                                <input type="hidden" id="id_compra" value="{$id_adq}">
                                                <input type="hidden" id="id_contrato_compra" value="{$id_contrato_compra_val}">
                                                <div class="div-cont pb-2">ADQ-{$adquisicion['id_adquisicion']}</div>
                                            </div>
                                            <div class="div-mostrar col-md-3">
                                                <span class="lbl-mostrar pb-2">FECHA</span>
                                                <div class="div-cont pb-2">{$adquisicion['fecha_adquisicion']}</div>
                                            </div>
                                            <div class="div-mostrar bor-top-right col-md-3">
                                                <span class="lbl-mostrar pb-2">ESTADO</span>
                                                <div class="div-cont pb-2">{$estado_desc}</div>
                                            </div>
                                        </div>
                                        <div class="row">
                                            <div class="div-mostrar bor-down-right bor-down-left col-md-12">
                                                <span class="lbl-mostrar pb-2">OBJETO</span>
                                                <div class="div-cont text-left pb-2">{$adquisicion['objeto']}</div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <input type="hidden" id="tipo_contrato" value="{$tipo_contrato}">
                        <input type="hidden" id="tipo_servicio" value="{$idtbnsv}">
HTML;
    if ($tipo_contrato == '1' && $adquisicion['estado'] >= 1) {
        $content .= <<<HTML
        <div class="card">
            <div class="card-header card-header-detalles py-0 headings" id="headingDestContrato">
                <h5 class="mb-0">
                    <a class="btn btn-link-acordeon sombra collapsed" data-bs-toggle="collapse" data-bs-target="#collapseDestContrato" aria-expanded="true" aria-controls="collapseDestContrato">
                        <div class="form-row">
                            <div class="div-icono">
                                <span class="fas fa-people-arrows fa-lg" style="color: #1ABC9C;"></span>
                            </div>
                            <div>
                                VIÑETA. DESTINACIÓN DEL CONTRATO
                            </div>
                        </div>
                    </a>
                </h5>
            </div>
            <div id="collapseDestContrato" class="collapse" aria-labelledby="headingDestContrato" data-bs-parent="#accordion">
HTML;
        $accion = empty($destinos) ? 'Guardar' : 'Actualizar';
        $value = empty($destinos) ? '0' : '1';
        $content .= <<<HTML
                <div class="card-body">
                    <form id="formDestContra">
                        <fieldset class="border p-2 bg-light">
                            <div id="contenedor">
HTML;
        $disabled = $adquisicion['estado'] <= 5 ? '' : 'disabled';
        if ($value == '0') {
            $content .= <<<HTML
                                <div class="row px-4 pt-2">
                                    <div class="col-md-4 mb-2">
                                        <label class="small">SEDE</label>
                                        <select name="slcSedeAC[]" class="form-control form-control-sm slcSedeAC bg-input" {$disabled}>
                                            <option value="0">--Seleccione--</option>
HTML;
            foreach ($sedes as $s) {
                $content .= '<option value="' . $s['id_sede'] . '">' . $s['nombre'] . '</option>';
            }
            $content .= <<<HTML
                                        </select>
                                    </div>
                                    <div class="col-md-4 mb-2">
                                        <label class="small">CENTRO DE COSTO</label>
                                        <select name="slcCentroCosto[]" class="form-control form-control-sm slcCentroCosto" {$disabled}>
                                            <option value="0">--Seleccionar Sede--</option>
                                        </select>
                                    </div>
                                    <div class="col-md-4 mb-2">
                                        <label class="small">Horas asignadas / mes</label>
                                        <div class="input-group input-group-sm">
                                            <input type="number" name="numHorasMes[]" class="form-control bg-input" {$disabled}>
HTML;
            if ($disabled == '') {
                $content .= '<button class="btn btn-outline-success" type="button" id="addRowSedes"><i class="fas fa-plus"></i></button>';
            }
            $content .= <<<HTML
                                        </div>
                                    </div>
                                </div>
HTML;
        } else {
            $control = 0;
            foreach ($destinos as $d) {
                $content .= '<div class="row px-4 pt-2">';
                $content .= '<div class="col-md-4 mb-2">';
                $content .= $control == 0 ? '<label class="small">SEDE</label>' : '';
                $content .= '<select name="slcSedeAC[]" class="form-control form-control-sm slcSedeAC bg-input" ' . $disabled . '>';
                foreach ($sedes as $s) {
                    $selected = ($s['id_sede'] == $d['id_sede']) ? 'selected' : '';
                    $content .= '<option value="' . $s['id_sede'] . '" ' . $selected . '>' . $s['nombre'] . '</option>';
                }
                $content .= '</select></div>';
                $content .= '<div class="col-md-4 mb-2">';
                $content .= $control == 0 ? '<label class="small">CENTRO DE COSTO</label>' : '';
                $content .= '<select name="slcCentroCosto[]" class="form-control form-control-sm slcCentroCosto bg-input" ' . $disabled . '>';
                foreach ($centros_costo as $cc) {
                    if ($cc['id_area'] == $d['id_area']) {
                        $selected = ($cc['id_area'] == $d['id_area']) ? 'selected' : '';
                        $content .= '<option value="' . $cc['id_area'] . '" ' . $selected . '>' . $cc['nom_centro'] . '</option>';
                    }
                }
                $content .= '</select></div>';
                $content .= '<div class="col-md-4 mb-2">';
                $content .= $control == 0 ? '<label for="numHorasMes" class="small">Horas asignadas / mes</label>' : '';
                $content .= '<div class="input-group input-group-sm">';
                $content .= '<input type="number" name="numHorasMes[]" class="form-control bg-input" value="' . $d['horas_mes'] . '" ' . $disabled . '>';
                if ($adquisicion['estado'] <= 5) {
                    if ($control == 0) {
                        $content .= '<button class="btn btn-outline-success" type="button" id="addRowSedes"><i class="fas fa-plus"></i></button>';
                    } else {
                        $content .= '<button class="btn btn-outline-danger delRowSedes" type="button"><i class="fas fa-minus"></i></button>';
                    }
                }
                $content .= '</div></div></div>';
                $control++;
            }
        }
        $content .= <<<HTML
                            </div>
                        </fieldset>
                    </form>
HTML;
        if ($adquisicion['estado'] <= 5) {
            $content .= '<div class="text-center pt-3">';
            if ($permisos->PermisosUsuario($opciones, 5302, 2) || $permisos->PermisosUsuario($opciones, 5302, 3) || $id_rol == 1) {
                $content .= '<button type="button" class="btn btn-success btn-sm" id="btnDestContra" value="' . $value . '">' . $accion . '</button>';
            }
            $content .= '</div>';
        }
        $content .= '</div></div></div>';
    }

    $content .= <<<HTML
                    </div>
                </div>
            </div>
        </div>
HTML;
    $content = preg_replace_callback('/VIÑETA/', function () use (&$numeral) {
        return $numeral++;
    }, $content);

    $plantilla = new Plantilla($content, 2);
    $plantilla->addScriptFile("{$host}/src/contratacion/adquisiciones/js/funciones_adquisiciones.js?v=" . date("YmdHis"));
    $modal = $plantilla->getModal('divModalForms', 'divTamModalForms', 'divForms');
    $plantilla->addModal($modal);
    echo $plantilla->render();
} else {
    echo 'Error al intentar obtener datos de la adquisición.';
}
