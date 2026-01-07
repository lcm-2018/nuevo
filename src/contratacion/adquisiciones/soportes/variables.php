<?php

use Src\Common\Php\Clases\Valores;

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
include_once '../../../../config/autoloader.php';
$cmd = \Config\Clases\Conexion::getConexion();

function pesos($valor)
{
    return '$ ' . number_format($valor, 0, ',', '.');
}

try {
    $cmd = \Config\Clases\Conexion::getConexion();

    $sql = "SELECT `nombre1`,`nombre2`,`apellido1`,`apellido2` FROM `seg_usuarios_sistema` WHERE `id_usuario` = {$id_user}";
    $rs = $cmd->query($sql);
    $usuario = $rs->fetch(PDO::FETCH_ASSOC);
    $usuario = !empty($usuario) ? trim($usuario['nombre1'] . ' ' . $usuario['nombre2'] . ' ' . $usuario['apellido1'] . ' ' . $usuario['apellido2']) : 'XXXXXX';
    $cmd = null;
} catch (PDOException $e) {
    echo $e->getCode() == 2002 ? 'Sin Conexión a Mysql (Error: 2002)' : 'Error: ' . $e->getMessage();
}

try {
    $cmd = \Config\Clases\Conexion::getConexion();

    $sql = "SELECT `variable`, `tipo`, `contexto`, `ejemplo` FROM `ctt_variables_forms`";
    $rs = $cmd->query($sql);
    $variables = $rs->fetchAll(PDO::FETCH_ASSOC);
    $cmd = null;
} catch (PDOException $e) {
    echo $e->getCode() == 2002 ? 'Sin Conexión a Mysql (Error: 2002)' : 'Error: ' . $e->getMessage();
}

try {
    $cmd = \Config\Clases\Conexion::getConexion();

    $sql = "SELECT 
                `ctt_adquisiciones`.`id_orden`,`pto_cdp`.`id_manu`, `pto_cdp`.`id_pto_cdp` AS `id_cdp`, `pto_cdp`.`fecha` AS `fecha_cdp`
            FROM `ctt_adquisiciones`
            LEFT JOIN `pto_cdp` 
                ON (`ctt_adquisiciones`.`id_cdp` = `pto_cdp`.`id_pto_cdp`)
            WHERE `id_adquisicion` = $id_adqi LIMIT 1";
    $rs = $cmd->query($sql);
    $adquisicion = $rs->fetch(PDO::FETCH_ASSOC);
    $id_orden = $adquisicion['id_orden'];
    if ($id_orden == '') {
        $sql = "SELECT
                `ctt_bien_servicio`.`bien_servicio`
                , `ctt_orden_compra_detalle`.`cantidad`
                , `ctt_orden_compra_detalle`.`val_unid` AS `val_estimado_unid`
                , `ctt_orden_compra_detalle`.`id_detalle`
                , `ctt_orden_compra_detalle`.`id_servicio` AS `id_bn_sv`
            FROM
                `ctt_orden_compra_detalle`
                INNER JOIN `ctt_orden_compra` 
                    ON (`ctt_orden_compra_detalle`.`id_oc` = `ctt_orden_compra`.`id_oc`)
                INNER JOIN `ctt_bien_servicio` 
                    ON (`ctt_orden_compra_detalle`.`id_servicio` = `ctt_bien_servicio`.`id_b_s`)
            WHERE (`ctt_orden_compra`.`id_adq` = $id_adqi)";
    } else {
        $sql = "SELECT
                `far_alm_pedido_detalle`.`id_ped_detalle` AS `id_detalle`
                , `far_medicamentos`.`nom_medicamento` AS `bien_servicio`
                , `far_alm_pedido_detalle`.`cantidad`
                , `far_alm_pedido_detalle`.`valor` AS `val_unid`
                , `far_alm_pedido_detalle`.`aprobado` AS `val_estimado_unid`
                , `far_alm_pedido_detalle`.`id_medicamento` AS `id_bn_sv`
            FROM
                `far_alm_pedido_detalle`
                INNER JOIN `far_medicamentos` 
                    ON (`far_alm_pedido_detalle`.`id_medicamento` = `far_medicamentos`.`id_med`)
            WHERE (`far_alm_pedido_detalle`.`id_pedido` = {$id_orden})";
    }
    $rs = $cmd->query($sql);
    $oferta = $rs->fetchAll(PDO::FETCH_ASSOC);
    $cmd = null;
} catch (PDOException $e) {
    echo $e->getCode() == 2002 ? 'Sin Conexión a Mysql (Error: 2002)' : 'Error: ' . $e->getMessage();
}
$cod = [];
if (!empty($oferta)) {
    foreach ($oferta as $o) {
        $cod[] = $o['id_bn_sv'];
    }
} else {
    $cod = '0';
}
$cod = implode(',', $cod);
try {
    $cmd = \Config\Clases\Conexion::getConexion();

    $sql = "SELECT
                `ctt_clasificacion_bn_sv`.`id_b_s`
                , `tb_codificacion_unspsc`.`codigo`
                , `tb_codificacion_unspsc`.`descripcion`
            FROM
                `ctt_clasificacion_bn_sv`
                LEFT JOIN  `tb_codificacion_unspsc`
                    ON (`ctt_clasificacion_bn_sv`.`cod_unspsc` = `tb_codificacion_unspsc`.`codigo`)
            WHERE `ctt_clasificacion_bn_sv`.`id_b_s` IN($cod) AND `ctt_clasificacion_bn_sv`.`vigencia` = '$vigencia'";
    $rs = $cmd->query($sql);
    $codigo_servicio = $rs->fetch(PDO::FETCH_ASSOC);
    $cmd = null;
} catch (PDOException $e) {
    echo $e->getCode() == 2002 ? 'Sin Conexión a Mysql (Error: 2002)' : 'Error: ' . $e->getMessage();
}
try {
    $cmd = \Config\Clases\Conexion::getConexion();

    $sql = "SELECT
                `ctt_adquisiciones`.`id_adquisicion`
                , `ctt_adquisiciones`.`id_tipo_bn_sv`
                , `ttbs`.`tipo_bn_sv`
                , `ctt_adquisiciones`.`id_modalidad`
                , `ctt_modalidad`.`modalidad`
                , `ctt_adquisiciones`.`objeto`
                , `tb_terceros`.`id_tercero_api`
                , `tb_terceros`.`nom_tercero`
                , `tb_terceros`.`nit_tercero`
                , `tb_area_c`.`id_area`
                , `tb_area_c`.`area`
            FROM
                `ctt_adquisiciones`
            INNER JOIN `tb_tipo_bien_servicio` AS `ttbs`
                ON (`ctt_adquisiciones`.`id_tipo_bn_sv` = `ttbs`.`id_tipo_b_s`)
            INNER JOIN `ctt_modalidad` 
                ON (`ctt_adquisiciones`.`id_modalidad` = `ctt_modalidad`.`id_modalidad`)
            INNER JOIN `tb_area_c` 
                ON (`ctt_adquisiciones`.`id_area` = `tb_area_c`.`id_area`)
            LEFT JOIN `tb_terceros`
                ON (`ctt_adquisiciones`.`id_tercero` = `tb_terceros`.`id_tercero_api`)
            WHERE `id_adquisicion` = $id_adqi LIMIT 1";
    $rs = $cmd->query($sql);
    $compra = $rs->fetch(PDO::FETCH_ASSOC);
    $cmd = null;
} catch (PDOException $e) {
    echo $e->getCode() == 2002 ? 'Sin Conexión a Mysql (Error: 2002)' : 'Error: ' . $e->getMessage();
}
$tipo_bn = $compra['id_tipo_bn_sv'];
try {
    $cmd = \Config\Clases\Conexion::getConexion();

    $sql = "SELECT
                `ctt_escala_honorarios`.`cod_pptal`  AS `id_pto_cargue`
                , `pto_cargue`.`cod_pptal`
                , `pto_cargue`.`nom_rubro`
            FROM
                `ctt_escala_honorarios`
                INNER JOIN`pto_cargue`
                ON (`ctt_escala_honorarios`.`cod_pptal` = `pto_cargue`.`id_cargue`)
            WHERE `ctt_escala_honorarios`.`id_tipo_b_s` = $tipo_bn AND `ctt_escala_honorarios`.`vigencia` = '$vigencia'";
    $rs = $cmd->query($sql);
    $cod_cargue = $rs->fetch(PDO::FETCH_ASSOC);
    $cmd = null;
} catch (PDOException $e) {
    echo $e->getCode() == 2002 ? 'Sin Conexión a Mysql (Error: 2002)' : 'Error: ' . $e->getMessage();
}
try {
    $cmd = \Config\Clases\Conexion::getConexion();

    $sql = "SELECT
                `ctt_estudios_previos`.`id_est_prev`
                , `ctt_estudios_previos`.`id_compra`
                , `ctt_estudios_previos`.`fec_ini_ejec` 
                , `ctt_estudios_previos`.`fec_fin_ejec`
                , `ctt_estudios_previos`.`val_contrata`
                , `ctt_estudios_previos`.`necesidad`
                , `ctt_estudios_previos`.`act_especificas`
                , `ctt_estudios_previos`.`prod_entrega`
                , `ctt_estudios_previos`.`obligaciones`
                , `ctt_estudios_previos`.`forma_pago`
                , `ctt_estudios_previos`.`requisitos`
                , `ctt_estudios_previos`.`garantia`
                , `ctt_estudios_previos`.`describe_valor`
                , `tb_forma_pago_compras`.`descripcion`
                , `ctt_estudios_previos`.`id_supervisor`
                , `tb_terceros`.`nom_tercero`
                , `tb_terceros`.`nit_tercero`
                , `ncee`.`descripcion_carg` AS `cargo_supervisor`
            FROM
                `ctt_estudios_previos`
            INNER JOIN `tb_forma_pago_compras` 
                ON (`ctt_estudios_previos`.`id_forma_pago` = `tb_forma_pago_compras`.`id_form_pago`)
            LEFT JOIN `tb_terceros` 
                ON (`ctt_estudios_previos`.`id_supervisor` = `tb_terceros`.`id_tercero_api`)
            LEFT JOIN `nom_empleado` AS `ne` 
                ON `tb_terceros`.`nit_tercero` = `ne`.`no_documento`
            LEFT JOIN `nom_contratos_empleados` AS `nce`
                ON `nce`.`id_empleado` = `ne`.`id_empleado`
                AND `nce`.`id_contrato_emp` = (
                    SELECT MAX(`id_contrato_emp`) 
                    FROM `nom_contratos_empleados` 
                    WHERE `id_empleado` = `ne`.`id_empleado`
                )
            LEFT JOIN `nom_cargo_empleado` AS `ncee` 
                ON `nce`.`id_cargo` = `ncee`.`id_cargo`
            WHERE `id_compra` = $id_adqi";
    $rs = $cmd->query($sql);
    $estudio_prev = $rs->fetch(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    echo $e->getCode() == 2002 ? 'Sin Conexión a Mysql (Error: 2002)' : 'Error: ' . $e->getMessage();
}
$cmd = null;
$id_ep = $estudio_prev['id_est_prev'];
try {
    $cmd = \Config\Clases\Conexion::getConexion();

    $sql = "SELECT
                `seg_garantias_compra`.`id_est_prev`
                ,`seg_garantias_compra`.`id_poliza`
                , `tb_polizas`.`descripcion`
                , `tb_polizas`.`porcentaje`
            FROM
                `seg_garantias_compra`
            INNER JOIN `tb_polizas` 
                ON (`seg_garantias_compra`.`id_poliza` = `tb_polizas`.`id_poliza`)
            WHERE `seg_garantias_compra`.`id_est_prev` = $id_ep";
    $rs = $cmd->query($sql);
    $garantias = $rs->fetchAll(PDO::FETCH_ASSOC);
    $cmd = null;
} catch (PDOException $e) {
    echo $e->getCode() == 2002 ? 'Sin Conexión a Mysql (Error: 2002)' : 'Error: ' . $e->getMessage();
}
try {
    $cmd = \Config\Clases\Conexion::getConexion();

    $sql = "SELECT
                `ctt`.`id_contrato_compra`,
                `ctt`.`id_compra`,
                `ctt`.`fec_ini`,
                `ctt`.`fec_fin`,
                `fp`.`descripcion`,
                `ctt`.`id_supervisor`,
                `ctt`.`num_contrato`,
                `ctt`.`val_contrato`,
                `ncee`.`descripcion_carg` AS `cargo_supervisor`
            FROM `ctt_contratos` AS `ctt`
            INNER JOIN `tb_forma_pago_compras` AS `fp` 
                ON `ctt`.`id_forma_pago` = `fp`.`id_form_pago`
            INNER JOIN `tb_terceros` AS `tt` 
                ON `ctt`.`id_supervisor` = `tt`.`id_tercero_api`
            INNER JOIN `nom_empleado` AS `ne` 
                ON `tt`.`nit_tercero` = `ne`.`no_documento`
            INNER JOIN `nom_contratos_empleados` AS `nce`
                ON `nce`.`id_empleado` = `ne`.`id_empleado`
                AND `nce`.`id_contrato_emp` = (
                    SELECT MAX(`id_contrato_emp`) 
                    FROM `nom_contratos_empleados` 
                    WHERE `id_empleado` = `ne`.`id_empleado`
                )
            INNER JOIN `nom_cargo_empleado` AS `ncee` 
                ON `nce`.`id_cargo` = `ncee`.`id_cargo`
            WHERE `ctt`.`id_compra` = '$id_adqi'";
    $rs = $cmd->query($sql);
    $contrato = $rs->fetch();
    $cmd = null;
} catch (PDOException $e) {
    echo $e->getCode() == 2002 ? 'Sin Conexión a Mysql (Error: 2002)' : 'Error: ' . $e->getMessage();
}
$polizas = '';
$num = 1;
foreach ($garantias as $g) {
    $polizas .=  $num . '. ' . ucfirst(strtolower($g['descripcion']) . ' por el ' . $g['porcentaje'] . '%. ');
    $num++;
}
try {
    $cmd = \Config\Clases\Conexion::getConexion();

    $sql = "SELECT
                `tb_datos_ips`.`nit_ips` AS `nit`
                , `tb_datos_ips`.`dv` AS `dig_ver`
                , `tb_datos_ips`.`razon_social_ips` AS `nombre`
                , `tb_municipios`.`nom_municipio`
            FROM
                `tb_datos_ips`
                INNER JOIN `tb_municipios` 
                    ON (`tb_datos_ips`.`idmcpio` = `tb_municipios`.`id_municipio`) LIMIT 1";
    $rs = $cmd->query($sql);
    $compania = $rs->fetch();
    $cmd = null;
} catch (PDOException $e) {
    echo $e->getCode() == 2002 ? 'Sin Conexión a Mysql (Error: 2002)' : 'Error: ' . $e->getMessage();
}

$servicio = $id_orden == '' ? $oferta[0]['bien_servicio'] : 'XXXXXXXXX';
$unspsc = $codigo_servicio['codigo'] ?? 'XXXXXXXXX';
$nombre = $codigo_servicio['descripcion'] ?? 'XXXXXXXXX';
$empresa = $compania['nombre'];
$municipio = $compania['nom_municipio'];
$n_cdp = $adquisicion['id_manu'] == '' ? 'XXXXXXXXX' : $adquisicion['id_manu'];
$meses = ['', 'enero', 'febrero', 'marzo', 'abril', 'mayo', 'junio', 'julio', 'agosto', 'septiembre', 'octubre', 'noviembre', 'diciembre'];
$fecI = explode('-', $estudio_prev['fec_ini_ejec']);
$fecF = explode('-', $estudio_prev['fec_fin_ejec']);
$fecha = mb_strtoupper($fecI[2] . ' de ' . $meses[intval($fecI[1])] . ' de ' . $fecI[0]);
$fecha_anexo = $fecI[2] . ' de ' . $meses[intval($fecI[1])] . ' de ' . $fecI[0];
$letras = new NumberFormatter("es", NumberFormatter::SPELLOUT);
// Convertir fechas a letras usando el método de la clase Valores
$fec_inicia = Valores::fechaEnLetras($estudio_prev['fec_ini_ejec'], false); // minúsculas
$fec_fin = Valores::fechaEnLetras($estudio_prev['fec_fin_ejec'], false); // minúsculas
$valor = $estudio_prev['val_contrata'];
$val_num = pesos($valor);
$objeto_ep = mb_strtoupper($compra['objeto']);
$supervisor = $estudio_prev['nom_tercero'];
$supervisor = $estudio_prev['id_supervisor'] == '' ? 'PENDIENTE' : $supervisor;
$val_ep = str_replace('-', '', mb_strtoupper($letras->format($valor, 2)));
// Calcular plazo de estudios previos usando el método de la clase Valores
$plazo_ep = Valores::calcularPlazo($estudio_prev['fec_ini_ejec'], $estudio_prev['fec_fin_ejec']);
$proyecto = mb_strtoupper($compra['area']);
// Convertir strings delimitados a arrays de objetos usando el método de la clase Valores
$necesidad      = Valores::stringToArrayObjects($estudio_prev['necesidad'], 'necesidad');
$actividad      = Valores::stringToArrayObjects($estudio_prev['act_especificas'], 'actividad');
$producto       = Valores::stringToArrayObjects($estudio_prev['prod_entrega'], 'producto');
$obligacion     = Valores::stringToArrayObjects($estudio_prev['obligaciones'], 'obligacion');
$pago           = Valores::stringToArrayObjects($estudio_prev['forma_pago'], 'pago');
$req_min        = Valores::stringToArrayObjects($estudio_prev['requisitos'], 'req_min');
$garantia       = Valores::stringToArrayObjects($estudio_prev['garantia'], 'garantia');
$describ_val    = Valores::stringToArrayObjects($estudio_prev['describe_valor'], 'describ_val');
//De oferta filtrar todos los bienes y servicios en un array
$bien_servicio = array_filter($oferta, function ($item) {
    return ucfirst(mb_strtolower($item['bien_servicio']));
});
// Calcular plazo del contrato si existen las fechas
$plazo              = '';
$fec_inicia         = '';
$fec_fin            = '';
if (isset($contrato['fec_ini']) && isset($contrato['fec_fin'])) {
    $plazo          = Valores::calcularPlazo($contrato['fec_ini'], $contrato['fec_fin']);
    $fec_inicia     = Valores::fechaEnLetras($contrato['fec_ini'], false); // minúsculas
    $fec_fin        = Valores::fechaEnLetras($contrato['fec_fin'], false); // minúsculas
}
$forma_pago         = isset($contrato['descripcion']) ? $contrato['descripcion'] : '';
$no_contrato        = isset($contrato['num_contrato']) ? $contrato['num_contrato'] : '-';
$objeto_ctt         = $compra['objeto'];
$val_ctt            = mb_strtoupper(Valores::LetrasCOP($contrato['val_contrato']));
$van_ctt            = pesos($contrato['val_contrato']);
$cod_presupuesto    = $cod_cargue['cod_pptal'] ?? 'XXX';
$rubro              = $cod_cargue['nom_rubro'] ?? 'XXX';
$tercero            = $compra['nom_tercero'] ?? 'XXX';
$cedula_tercero     = $compra['nit_tercero'] ?? 'XXX';
$cargo_sup_ep       = $estudio_prev['cargo_supervisor'] ?? 'XXX';
$cargo_supervisor   = $estudio_prev['cargo_supervisor'] ?? $contrato['cargo_supervisor'] ?? 'XXX';
$tipo_bn_sv         = ucfirst(mb_strtolower($compra['tipo_bn_sv'])) ?? 'XXX';
$firma1 = '/nuevo/assets/images/vacio.png';
