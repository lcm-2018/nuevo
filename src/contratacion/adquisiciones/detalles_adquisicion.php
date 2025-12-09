<?php
session_start();
if (!isset($_SESSION['user'])) {
    header('Location: ../../../index.php');
    exit();
}
include_once '../../../config/autoloader.php';

use Config\Clases\Plantilla;
use Src\Common\Php\Clases\Combos;
use Src\Common\Php\Clases\Permisos;

$host = Plantilla::getHost();
$numeral = 1;
$permisos = new Permisos();

$id_rol = $_SESSION['rol'];
$id_user = $_SESSION['id_user'];
$opciones = $permisos->PermisoOpciones($id_user);
$peReg =  $permisos->PermisosUsuario($opciones, 5302, 0) || $id_rol == 1 ? 1 : 0;

function pesos($valor)
{
    return '$' . number_format($valor, 2, ",", ".");
}

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
                `ctt_adquisiciones`
                INNER JOIN `tb_tipo_bien_servicio` 
                    ON (`tb_tipo_bien_servicio`.`id_tipo_b_s` = `ctt_adquisiciones`.`id_tipo_bn_sv`)
                INNER JOIN `tb_tipo_compra` 
                    ON (`tb_tipo_bien_servicio`.`id_tipo` = `tb_tipo_compra`.`id_tipo`)
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
    $sql = "SELECT `id_relacion`, `id_formato` FROM `ctt_formatos_doc_rel` WHERE (`id_tipo_bn_sv` = $tp_bs)";
    $rs = $cmd->query($sql);
    $formatos = $rs->fetchAll(PDO::FETCH_ASSOC);
    $rs->closeCursor();
    unset($rs);
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

    $sql = "SELECT `id`, `descripcion` FROM `ctt_estado_adq`";
    $rs = $cmd->query($sql);
    $estado_adq = $rs->fetchAll(PDO::FETCH_ASSOC);
    $rs->closeCursor();
    unset($rs);
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
    $destinos = $rs->fetchAll(PDO::FETCH_ASSOC);
    $rs->closeCursor();
    unset($rs);
    if (empty($destinos)) {
        $destinos = [
            ['id_destino' => 0, 'id_area' => 0, 'id_sede' => 0, 'id_centrocosto' => 0, 'horas_mes' => 0]
        ];
    }
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
                , `tb_tipo_bien_servicio`.`tipo_bn_sv`
                , `ctt_adquisiciones`.`id_tercero`
                , `tb_terceros`.`nit_tercero`
                , `tb_terceros`.`nom_tercero`
                , `tb_tipo_compra`.`id_tipo`
                , `tb_tipo_compra`.`tipo_compra`
                , `ctt_estado_adq`.`descripcion`
            FROM
                `ctt_adquisiciones`
            INNER JOIN `ctt_modalidad` 
                ON (`ctt_adquisiciones`.`id_modalidad` = `ctt_modalidad`.`id_modalidad`)
            INNER JOIN `tb_tipo_bien_servicio` 
                ON (`ctt_adquisiciones`.`id_tipo_bn_sv` = `tb_tipo_bien_servicio`.`id_tipo_b_s`)
            INNER JOIN `tb_tipo_compra` 
                ON (`tb_tipo_bien_servicio`.`id_tipo` = `tb_tipo_compra`.`id_tipo`)
            LEFT JOIN `tb_terceros` 
                ON (`ctt_adquisiciones`.`id_tercero` = `tb_terceros`.`id_tercero_api`)
            LEFT JOIN `ctt_estado_adq`
                ON (`ctt_adquisiciones`.`estado` = `ctt_estado_adq`.`id`)
            WHERE `id_adquisicion` = $id_adq";
    $rs = $cmd->query($sql);
    $adquisicion = $rs->fetch();
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
    $rs->closeCursor();
    unset($rs);
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
            WHERE `id_compra` = $id_adq LIMIT 1";
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
                , DATE_FORMAT(`pto_cdp`.`fecha`,'%Y-%m-%d') AS `fecha`
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
                , DATE_FORMAT(`pto_crp`.`fecha`,'%Y-%m-%d') AS `fecha`
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
                `id_contrato_compra`, `id_compra`, `fec_ini`, `fec_fin`, `val_contrato`, `id_forma_pago`, `id_supervisor`, `id_secop`, `num_contrato`
            FROM
                `ctt_contratos`
            WHERE (`id_compra` = $id_adq) LIMIT 1";
    $rs = $cmd->query($sql);
    $contrato = $rs->fetch();
    if (empty($contrato)) {
        $contrato['id_contrato_compra'] = '';
    }
    $cmd = null;
} catch (PDOException $e) {
    echo $e->getCode() == 2002 ? 'Sin Conexión a Mysql (Error: 2002)' : 'Error: ' . $e->getMessage();
}
$id_orden = $adquisicion['id_orden'] == '' ? '' : '<input type="hidden" name="id_orden" id="id_orden" value="' . $adquisicion['id_orden'] . '">';

// destinación de contrato
$guardar = $destino = '';
if ($adquisicion['id_tipo'] == 2) {
    if ($adquisicion['estado'] <= 5 && ($permisos->PermisosUsuario($opciones, 5302, 2) || $permisos->PermisosUsuario($opciones, 5302, 3) || $id_rol == 1)) {
        $guardar = '<button type="button" class="btn btn-success btn-sm" id="btnDestContra">Guardar</button>';
    }
    $uno = true;
    $read = '';
    foreach ($destinos as $d) {
        if ($uno) {
            $label1 = ['label' => '<label for="slcSedeAC" class="small">SEDE</label>', 'id' => 'id="slcSedeAC"'];
            $label2 = ['label' => '<label for="slcCentroCosto" class="small">CENTRO DE COSTO</label>', 'id' => 'id="slcCentroCosto"'];
            $label3 = ['label' => '<label for="numHorasMes" class="small">HORAS ASIGNADAS / MES</label>', 'id' => 'id="numHorasMes"'];
            $opBtn =
                <<<HTML
                <button class="btn btn-outline-success" type="button" id="addRowSedes"><span class="fas fa-plus"></span></button>
                HTML;
            $uno = false;
            $padding = '';
        } else {
            $label1 = $label2 = $label3 = ['label' => '', 'id' => ''];
            $opBtn =
                <<<HTML
                <button class="btn btn-outline-danger delRowSedes" type="button"><span class="fas fa-minus"></span></button>
                HTML;
            $padding = 'pt-2';
        }
        if ($adquisicion['estado'] > 5) {
            $opBtn = '';
            $read = 'disabled readonly';
        }
        $sedes = Combos::getSedes($d['id_sede']);
        $centros = Combos::getCentrosCostoxSede($d['id_area'], $d['id_sede']);
        $horas = $d['horas_mes'];
        $destino .=
            <<<HTML
            <div class="row text-center {$padding}">
                <div class="col-md-4">
                    {$label1['label']}
                    <select name="slcSedeAC[]" {$label1['id']} class="form-select form-select-sm bg-input slcSedeAC" {$read}>
                        {$sedes}
                    </select>
                </div>
                <div class="col-md-4">
                    {$label2['label']}
                    <select name="slcCentroCosto[]" {$label2['id']} class="form-select form-select-sm bg-input slcCentroCosto" {$read}>
                        {$centros}
                    </select>
                </div>
                <div class="col-md-4">
                    {$label3['label']}
                    <div class="input-group input-group-sm">
                        <input type="number" name="numHorasMes[]" {$label3['id']} class="form-control bg-input" placeholder="Horas asignadas" aria-label="Horas asignadas" value="{$horas}" {$read}>
                        {$opBtn}
                    </div>
                </div>
            </div>
            HTML;
    }
    $destino =
        <<<HTML
        <div class="accordion-item">
            <h2 class="accordion-header">
                <button class="accordion-button collapsed bg-head-button border" type="button" data-bs-toggle="collapse" data-bs-target="#collapsemodDestino" aria-expanded="false" aria-controls="collapsemodDestino">
                    <span class="text-info"><i class="fas fa-map-signs me-2 fa-lg"></i>VIÑETA. Destino del contrato.</span>
                </button>
            </h2>
            <div id="collapsemodDestino" class="accordion-collapse collapse" data-bs-parent="#accDestino">
                <div class="accordion-body bg-wiev">
                    <div class=" px-3 shadow rounded">
                        <form id="formDestContra">
                            <div class="card-body">
                                <div  id="contenedor">
                                {$destino}
                                </div>
                                <div class="text-center pt-3">{$guardar}</div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
        HTML;
}
$detalle_html = '';
$total_compra = 0;
$fila =  $boton_oc = $guardar_oc = $cerrar_oc = '';
//orden de compra
if (in_array($adquisicion['filtro_adq'], ['1', '2'])) {
    if (empty($adquisicion['id_orden'])) {
        $buttonText = $adquisicion['filtro_adq'] == '1' ? 'Orden Almacén' : 'Orden Activos Fijos';
        $boton_oc = '<button type="button" class="btn btn-primary btn-sm listOrdenes mr-1 mb-3 mt-3" text="' . $adquisicion['filtro_adq'] . '">' . $buttonText . '</button>';
    }
    if ($adquisicion['estado'] == 1) {
        $guardar_oc = '<button type="button" class="btn btn-success btn-sm mr-1 mb-3 mt-3" id="guardarOrden">Guardar</button>';
        $cerrar_oc = '<button type="button" class="btn btn-secondary btn-sm mr-1 mb-3 mt-3" id="cerrarOrden">Cerrar</button>';
    }
}

foreach ($detalles_orden as $dc) {
    if ($adquisicion['id_orden'] > 0 && $adquisicion['estado'] < 5) {
        $aprobado = $dc['aprobado'] > 0 ? $dc['aprobado'] : $dc['cantidad'];
        $val_unid = '<input type="number" name="val_unid[' . $dc['id_detalle'] . ']" class="form-control form-control-sm text-end" value="' . $dc['val_unid'] . '">';
        $iva = '<select name="iva[' . $dc['id_detalle'] . ']" class="form-select form-select-sm">
                    <option value="0" ' . ($dc['iva'] == 0 ? 'selected' : '') . '>0%</option>
                    <option value="5" ' . ($dc['iva'] == 5 ? 'selected' : '') . '>5%</option>
                    <option value="19" ' . ($dc['iva'] == 19 ? 'selected' : '') . '>19%</option>
                </select>';
        $cantidad = '
                <div class="input-group input-group-sm">
                    <span class="input-group-text d-flex justify-content-end" style="min-width: 70px;">' . $dc['cantidad'] . '</span>
                    <input 
                        type="number" 
                        class="form-control form-control-sm text-end" 
                        name="cantidad[' . $dc['id_detalle'] . ']" 
                        value="' . $aprobado . '" 
                        max="' . $dc['cantidad'] . '"
                    >
                </div>';
    } else {
        $val_unid = pesos($dc['val_unid']);
        $iva = $dc['iva'] . '%';
        $cantidad = isset($dc['aprobado']) ? $dc['aprobado'] : $dc['cantidad'];
    }
    $tot_l = $dc['val_unid'] * (isset($dc['aprobado']) ? $dc['aprobado'] : $dc['cantidad']);
    $tot_con_iva = $tot_l + ($tot_l * $dc['iva'] / 100);
    $total_compra += $tot_con_iva;

    $botones = '';
    $check = '';
    if ($adquisicion['id_orden'] == '') {
        if ($adquisicion['estado'] >= 1 && $adquisicion['estado'] < 6 && $adquisicion['id_orden'] == '') {
            $botones = '<button value="' . $dc['id_detalle'] . '" class="btn btn-outline-primary btn-xs rounded-circle shadow editar" title="Editar"><span class="fas fa-pencil-alt"></span></button>
            <button value="' . $dc['id_detalle'] . '" class="btn btn-outline-danger btn-xs rounded-circle shadow borrar" title="Eliminar"><span class="fas fa-trash-alt"></span></button>';
        }
    } else {
        if ($adquisicion['estado'] < 5) {
            $check = '<input type="checkbox" class="aprobado" name="aprobado[' . $dc['id_detalle'] . ']" checked>';
        }
    }
    $con_iva = pesos($tot_con_iva);
    $detalle_html .=
        <<<HTML
            <tr>
                <td>{$dc['id_detalle']}</td>
                <td>{$dc['bien_servicio']}</td>
                <td>{$cantidad}</td>
                <td class="text-end">{$val_unid}</td>
                <td> {$iva}</td>
                <td class="text-end">
                    {$con_iva}
                    <input type="hidden" name="total[]" class="sumTotal" value="{$tot_con_iva}">
                </td>
                <td class="text-center">
                    {$botones}
                    {$check}
                </td>
            </tr>
        HTML;
}
$solcan = $adquisicion['id_orden'] > 0 ? 'Solicita/Ordena' : 'Cantidad';
$action = $adquisicion['id_orden'] == '' ? 'Acciones' : '<input type="checkbox" id="selectAll" title="Desmarcar todos" checked>';
if ($adquisicion['id_orden'] > 0) {
    $ttl = pesos($total_compra);
    $detalle_html .=
        <<<HTML
        <tfoot>
            <th colspan="5" class="text-center"><b>TOTAL ORDEN</b></th>
            <th colspan="2" class="text-end"><b>{$ttl}</b></th>
        </tfoot>
        HTML;
}
//cdp
if (!empty($cdp)) {
    $valor_cdp = pesos($cdp['valor']);
    $cdp_html =
        <<<HTML
    <input type="hidden" id="num_cdp" value="{$cdp['id_manu']}">
    <table class="table table-striped table-bordered table-sm nowrap table-hover shadow tableCDP" style="width:100%">
        <thead class="text-center">
            <tr>
                <th class="bg-sofia">Número</th>
                <th class="bg-sofia">Fecha</th>
                <th class="bg-sofia">Objeto</th>
                <th class="bg-sofia">Valor</th>
            </tr>
        </thead>
        <tbody class="modificarCDP">
            <tr>
                <td>{$cdp['id_manu']}</td>
                <td>{$cdp['fecha']}</td>
                <td>{$cdp['objeto']}</td>
                <td class="text-end">{$valor_cdp}</td>
            </tr>
        </tbody>
    </table>
    HTML;
} else {
    $cdp_html =
        <<<HTML
    <div class="p-3 mb-2 bg-warning text-white">AÚN <b>NO</b> SE HA ASIGNADO UN CDP</div>
    HTML;
}
$btn_ep = '';
$tb_html = '';
if ($id_estudio == '') {
    if ($permisos->PermisosUsuario($opciones, 5302, 2) || $id_rol == 1) {
        $btn_ep =
            <<<HTML
            <button type="button" class="btn btn-success btn-sm" id='btnAddEstudioPrevio' value="{$id_adq}">INICIAR ESTUDIOS PREVIOS</button>
        HTML;
    }
} else {
    include 'datos/listar/datos_estudio_previo.php';
    if ($adquisicion['estado'] <= 7) {
        $pos1 = isset($posicion[1]) ? $posicion[1] : 0;
        $pos2 = isset($posicion[2]) ? $posicion[2] : 0;
        $btn_ep =
            <<<HTML
            <a type="button" text="{$pos1}" class="btn btn-warning btn-sm downloadFormsCtt" id="btnFormatoEstudioPrevio" style="color:white">DESCARGAR FORMATO&nbsp&nbsp;<span class="fas fa-file-download fa-lg"></span></a>
            <a type="button" class="btn btn-info btn-sm" id="x-x" style="color:white">MATRIZ DE RIESGOS&nbsp&nbsp;<span class="fas fa-download fa-lg"></span></a>
            <a type="button" text="{$pos2}" class="btn btn-primary btn-sm downloadFormsCtt" id="btnAnexos" style="color:white">ANEXOS&nbsp&nbsp;<span class="far fa-copy fa-lg"></span></a>
        HTML;
    }
}
//contratacion
$ctt_html = $btn_ctt = $btn_ctt_cerrar = $btn_cv = $btn_cs = $btn_ds = '';
if ($id_estudio == '') {
    $ctt_html =
        <<<HTML
    <div class="alert alert-warning" role="alert">
        AUN NO SE HA REGISTRADO ESTUDIOS PREVIOS
    </div>
    HTML;
} else {
    if ($adquisicion['estado'] == 6) {
        if ($permisos->PermisosUsuario($opciones, 5302, 2) || $id_rol == 1) {
            $btn_ctt =
                <<<HTML
                <button type="button" class="btn btn-success btn-sm" id='btnAddContrato' value="{$id_estudio}">INICIAR CONTRATACIÓN</button>
                HTML;
        }
    } else if ($adquisicion['estado'] >= 7) {
        if ($adquisicion['estado'] == 7) {
            $btn_ctt_cerrar =
                <<<HTML
                <div class="text-end">
                    <a type="button" class="btn btn-secondary btn-sm mb-2" id="btnCerrarContrato">Cerrar</a>
                </div>
                HTML;
        }
        include 'datos/listar/datos_contrato_compra.php';
        if ($adquisicion['estado'] == 7) {
            $btn_cv =
                <<<HTML
                <button type="button" class="btn btn-warning btn-sm" id="xx" style="color:white" disabled>DESCARGAR FORMATO&nbsp&nbsp;<span class="fas fa-file-download fa-lg"></span></button>
                HTML;
        }
    }
}
if ($adquisicion['estado'] == 9) {
    $pos3 = isset($posicion[3]) ? $posicion[3] : 0;
    $btn_ds =
        <<<HTML
            <a type="button" class="btn btn-warning btn-sm" id="btnFormatoDesigSuper" style="color:white">DESCARGAR FORMATO DESIGNACIÓN DE SUPERVISIÓN&nbsp&nbsp;<span class="fas fa-file-download fa-lg"></span></a>
            <a type="button" text="{$pos3}" class="btn btn-success btn-sm downloadFormsCtt" id="btnFormatoContrato" style="color:white">DESCARGAR FORMATO CONTRATO&nbsp&nbsp;<span class="fas fa-file-download fa-lg"></span></a>
        HTML;
}
//CRP
if (!empty($crp)) {
    $valor_cdp = pesos($crp['valor']);
    $crp_html =
        <<<HTML
    <input type="hidden" id="num_cdp" value="{$crp['id_manu']}">
    <table class="table table-striped table-bordered table-sm nowrap table-hover shadow tableCDP" style="width:100%">
        <thead class="text-center">
            <tr>
                <th class="bg-sofia">Número</th>
                <th class="bg-sofia">Fecha</th>
                <th class="bg-sofia">Objeto</th>
                <th class="bg-sofia">Valor</th>
            </tr>
        </thead>
        <tbody class="modificarCDP">
            <tr>
                <td>{$crp['id_manu']}</td>
                <td>{$crp['fecha']}</td>
                <td>{$crp['objeto']}</td>
                <td class="text-end">{$valor_cdp}</td>
            </tr>
        </tbody>
    </table>
    HTML;
} else {
    $crp_html =
        <<<HTML
    <div class="p-3 mb-2 bg-warning text-white">AÚN <b>NO</b> SE HA ASIGNADO UN REGISTRO PRESUPUESTAL (RP)</div>
    HTML;
}
//ACTA DE INICIO
if ($adquisicion['estado'] >= 9) {
    $pos4 = isset($posicion[4]) ? $posicion[4] : 0;
    $btn_acta =
        <<<HTML
    <a type="button" text="{$pos4}" class="btn btn-warning btn-sm downloadFormsCtt" id="btnFormActaInicio" style="color:white">DESCARGAR FORMATO ACTA DE INICIO&nbsp&nbsp;<span class="fas fa-file-download fa-lg"></span></a>
    HTML;
} else {
    $btn_acta =
        <<<HTML
    <div class="alert alert-warning" role="alert">
        SE DEBE ASIGNAR UN SUPERVISOR PARA GENERAR ACTA DE INICIO.
    </div>
    HTML;
}
//NOVEDADES CONTRATO
if ($contrato['id_contrato_compra'] > 0) {
    $novedades_html =
        <<<HTML
    <div class="row pb-3">
        <div class="col-md-2">
            <button value="1" type="button" class="btn btn-outline-info w-100 btn-sm novedadC">Adición o Prorroga</button>
        </div>
        <div class="col-md-2">
            <button value="2" type="button" class="btn btn-outline-info w-100 btn-sm novedadC">Cesión</button>
        </div>
        <div class="col-md-2">
            <button value="3" type="button" class="btn btn-outline-info w-100 btn-sm novedadC">Suspención</button>
        </div>
        <div class="col-md-2">
            <button value="4" type="button" class="btn btn-outline-info w-100 btn-sm novedadC">Reinicio</button>
        </div>
        <div class="col-md-2">
            <button value="5" type="button" class="btn btn-outline-info w-100 btn-sm novedadC">Terminación</button>
        </div>
        <div class="col-md-2">
            <button value="6" type="button" class="btn btn-outline-info w-100 btn-sm novedadC">Liquidación</button>
        </div>
    </div>
    <table id="tableNovedadesContrato" class="table table-striped table-bordered table-sm nowrap table-hover shadow" style="width:100%">
        <thead>
            <tr class="text-center">
                <th class="bg-sofia">Novedad</th>
                <th class="bg-sofia">Fecha</th>
                <th class="bg-sofia">Valor 1</th>
                <th class="bg-sofia">Valor 2</th>
                <th class="bg-sofia">Fecha Inicia</th>
                <th class="bg-sofia">Fecha Fin</th>
                <th class="bg-sofia">Observación</th>
                <th class="bg-sofia">Acciones</th>
            </tr>
        </thead>
        <tbody id="modificarNovContrato">
        </tbody>
    </table>
    HTML;
} else {
    $novedades_html =
        <<<HTML
    <div class="alert alert-warning" role="alert">
        AUN NO SE HA GENERADO EL CONTRATO PARA ESTA CONTRATACIÓN.
    </div>
    HTML;
}
if ($adquisicion['estado'] > 5 || $adquisicion['id_tipo'] != 2) {
    $peReg = 0;
}
$content =
    <<<HTML
    <div class="card w-100">
        <div class="card-header bg-sofia text-white">
            <button class="btn btn-xs me-1 p-0" title="Regresar" onclick="window.history.back();"><i class="fas fa-arrow-left fa-lg"></i></button>
            <b>CONFIGURACIÓN DE CONTRATACIÓN</b>
        </div>
        <div id="accordionCtt" class="card-body p-2 bg-wiev">
            <input type="hidden" id="peReg" value="{$peReg}">
            <div class="accordion" id="accContrata">
                <div class="accordion-item">
                    <h2 class="accordion-header">
                        <button class="accordion-button collapsed bg-head-button border" type="button" data-bs-toggle="collapse" data-bs-target="#collapsemodContrata" aria-expanded="false" aria-controls="collapsemodContrata">
                            <span class="text-primary"><i class="fas fa-clipboard-list me-2 fa-lg"></i>VIÑETA. Detalles de contratación.</span>
                        </button>
                    </h2>
                    <div id="collapsemodContrata" class="accordion-collapse collapse show" data-bs-parent="#accContrata">
                        <div class="accordion-body bg-wiev">
                            <div class=" px-3 shadow rounded">
                                <div class="card-body">
                                    <input type="hidden" id="tipo_contrato" value="{$adquisicion['id_tipo']}">
                                    <input type="hidden" id="tipo_servicio" value="{$adquisicion['id_tipo_bn_sv']}">
                                    <div class="row mb-0 border border-bottom-0 rounded-top">
                                        <div class="border-end col-md-4">
                                            <span class="text-muted small">MODALIDAD CONTRATACIÓN</span><br>
                                            <span class="fw-bold">{$adquisicion['modalidad']}</span>
                                        </div>
                                        <div class="border-end col-md-2">
                                            <span class="text-muted small">ADQUISICIÓN</span><br>
                                            <input type="hidden" id="id_compra" value="{$id_adq}">
                                            <input type="hidden" id="id_contrato_compra" value="{$contrato['id_contrato_compra']}">
                                            <span class="fw-bold">ADQ-{$adquisicion['id_adquisicion']}</span>
                                        </div>
                                        <div class="border-end col-md-3">
                                            <span class="text-muted small">FECHA</span><br>
                                            <span class="fw-bold">{$adquisicion['fecha_adquisicion']}</span>
                                        </div>
                                        <div class="border-end col-md-3">
                                            <span class="text-muted small">ESTADO</span><br>
                                            <span class="fw-bold">{$adquisicion['descripcion']}</span>
                                        </div>
                                    </div>
                                    <div class="row border">
                                        <div class="col-md-12">
                                            <span class="text-muted small">OBJETO</span><br>
                                            <span class="fw-bold">{$adquisicion['objeto']}</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="accordion-item">
                    <h2 class="accordion-header">
                        <button class="accordion-button collapsed bg-head-button border" type="button" data-bs-toggle="collapse" data-bs-target="#collapsemodOrden" aria-expanded="false" aria-controls="collapsemodOrden">
                            <span class="text-success"><i class="fas fa-clipboard-list me-2 fa-lg"></i>VIÑETA. Orden de compra.</span>
                        </button>
                    </h2>
                    <div id="collapsemodOrden" class="accordion-collapse collapse" data-bs-parent="#accOrden">
                        <div class="accordion-body bg-wiev">
                            <div class=" px-3 shadow rounded">
                                <form id="formOrdenCompra">
                                    {$id_orden}
                                        {$boton_oc}
                                        {$guardar_oc}
                                        {$cerrar_oc}
                                    <table class="table table-striped table-bordered table-sm nowrap table-hover shadow tableCotRecibidas" style="width:100%">
                                        <thead>
                                            <tr class="text-center">
                                                <th class="bg-sofia">TERCERO:</th>
                                                <th class="bg-sofia" colspan="4">{$adquisicion['nom_tercero']}</th>
                                                <th class="bg-sofia" colspan="2">{$adquisicion['nit_tercero']}</th>
                                            </tr>
                                            <tr class="text-center">
                                                <th class="bg-sofia">#</th>
                                                <th class="bg-sofia">Bien o Servicio</th>
                                                <th class="bg-sofia">{$solcan}</th>
                                                <th class="bg-sofia">Val. Unidad</th>
                                                <th class="bg-sofia">IVA</th>
                                                <th class="bg-sofia">Total</th>
                                                <th class="bg-sofia">{$action}</th>
                                            </tr>
                                        </thead>
                                        <tbody class="modificarCotizaciones">
                                            {$detalle_html}
                                        </tbody>
                                    </table>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
                $destino
                <div class="accordion-item">
                    <h2 class="accordion-header">
                        <button class="accordion-button collapsed bg-head-button border" type="button" data-bs-toggle="collapse" data-bs-target="#collapsemodClasificador" aria-expanded="false" aria-controls="collapsemodClasificador">
                            <span class="text-warning-emphasis"><i class="fas fa-clipboard-list me-2 fa-lg"></i>VIÑETA. Clasificador de bienes y servicios.</span>
                        </button>
                    </h2>
                    <div id="collapsemodClasificador" class="accordion-collapse collapse" data-bs-parent="#accClasificador">
                        <div class="accordion-body bg-wiev">
                            <div class=" px-3 shadow rounded">
                                <div class="card-body">
                                    <table id="tableClasificador" class="table table-striped table-bordered table-sm nowrap table-hover shadow" style="width:100%">
                                        <thead>
                                            <tr class="text-center">
                                                <th class="bg-sofia">#</th>
                                                <th class="bg-sofia">Código UNSPSC</th>
                                                <th class="bg-sofia">Descripción</th>
                                                <th class="bg-sofia">Acciones</th>
                                            </tr>
                                        </thead>
                                        <tbody id="modificarClasificador">
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="accordion-item">
                    <h2 class="accordion-header">
                        <button class="accordion-button collapsed bg-head-button border" type="button" data-bs-toggle="collapse" data-bs-target="#collapsemodCdp" aria-expanded="false" aria-controls="collapsemodCdp">
                            <span class="text-muted"><i class="fas fa-file-invoice-dollar me-2 fa-lg"></i>VIÑETA. Certificado de disponibilidad presupuestal (CDP).</span>
                        </button>
                    </h2>
                    <div id="collapsemodCdp" class="accordion-collapse collapse" data-bs-parent="#accCdp">
                        <div class="accordion-body bg-wiev">
                            <div class=" px-3 shadow rounded">
                                <div class="card-body">
                                    {$cdp_html}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="accordion-item">
                    <h2 class="accordion-header">
                        <button class="accordion-button collapsed bg-head-button border" type="button" data-bs-toggle="collapse" data-bs-target="#collapsemodEP" aria-expanded="false" aria-controls="collapsemodEP">
                            <span class="text-warning"><i class="fas fa-folder-open me-2 fa-lg"></i>VIÑETA. Estudios previos.</span>
                        </button>
                    </h2>
                    <div id="collapsemodEP" class="accordion-collapse collapse" data-bs-parent="#accEP">
                        <div class="accordion-body bg-wiev">
                            <div class=" px-3 shadow rounded">
                                <div class="card-body">
                                    {$tb_html}
                                    {$btn_ep}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="accordion-item">
                    <h2 class="accordion-header">
                        <button class="accordion-button collapsed bg-head-button border" type="button" data-bs-toggle="collapse" data-bs-target="#collapsemodContrato" aria-expanded="false" aria-controls="collapsemodContrato">
                            <span class="text-secondary"><i class="fas fa-file-contract me-2 fa-lg"></i>VIÑETA. Contratación.</span>
                        </button>
                    </h2>
                    <div id="collapsemodContrato" class="accordion-collapse collapse" data-bs-parent="#accContrato">
                        <div class="accordion-body bg-wiev">
                            <div class=" px-3 shadow rounded">
                                <div class="card-body">
                                    {$btn_ctt_cerrar}{$ctt_html}{$btn_ctt}{$btn_cv}{$btn_cs}{$btn_ds}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="accordion-item">
                    <h2 class="accordion-header">
                        <button class="accordion-button collapsed bg-head-button border" type="button" data-bs-toggle="collapse" data-bs-target="#collapsemodCrp" aria-expanded="false" aria-controls="collapsemodCrp">
                            <span class="text-primary-emphasis"><i class="fas fa-file-prescription me-2 fa-lg"></i>VIÑETA. Registro presupuestal (RP).</span>
                        </button>
                    </h2>
                    <div id="collapsemodCrp" class="accordion-collapse collapse" data-bs-parent="#accCrp">
                        <div class="accordion-body bg-wiev">
                            <div class=" px-3 shadow rounded">
                                <div class="card-body">
                                    {$crp_html}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="accordion-item">
                    <h2 class="accordion-header">
                        <button class="accordion-button collapsed bg-head-button border" type="button" data-bs-toggle="collapse" data-bs-target="#collapsemodInicio" aria-expanded="false" aria-controls="collapsemodInicio">
                            <span class="text-success-emphasis"><i class="fas fa-map-pin me-2 fa-lg"></i>VIÑETA. Acta de inicio.</span>
                        </button>
                    </h2>
                    <div id="collapsemodInicio" class="accordion-collapse collapse" data-bs-parent="#accInicio">
                        <div class="accordion-body bg-wiev">
                            <div class=" px-3 shadow rounded">
                                <div class="card-body">
                                    {$btn_acta}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="accordion-item">
                    <h2 class="accordion-header">
                        <button class="accordion-button collapsed bg-head-button border" type="button" data-bs-toggle="collapse" data-bs-target="#collapsemodNovedad" aria-expanded="false" aria-controls="collapsemodNovedad">
                            <span class="text-info-emphasis"><i class="fas fa-bullhorn me-2 fa-lg"></i>VIÑETA. Novedades.</span>
                        </button>
                    </h2>
                    <div id="collapsemodNovedad" class="accordion-collapse collapse" data-bs-parent="#accNovedad">
                        <div class="accordion-body bg-wiev">
                            <div class=" px-3 shadow rounded">
                                <div class="card-body">
                                    {$novedades_html}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    HTML;

$content = preg_replace_callback('/VIÑETA/', function () use (&$numeral) {
    return $numeral++;
}, $content);

$plantilla = new Plantilla($content, 2);
$plantilla->addCssFile("{$host}/assets/css/jquery-ui.css?v=" . date("YmdHis"));
$plantilla->addScriptFile("{$host}/assets/js/jquery-ui.js?v=" . date("YmdHis"));
$plantilla->addScriptFile("{$host}/src/contratacion/adquisiciones/js/funciones_adquisiciones.js?v=" . date("YmdHis"));
$modal = $plantilla->getModal('divModalForms', 'divTamModalForms', 'divForms');
$plantilla->addModal($modal);
echo $plantilla->render();
