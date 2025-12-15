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
$peReg = $permisos->PermisosUsuario($opciones, 5401, 2) || $id_rol == 1 ? 1 : 0;
$host = Plantilla::getHost();

// Consulta tipo de presupuesto
$id_pto_presupuestos = $_POST['id_pto'];
$vigencia = $_SESSION['vigencia'];

try {
    $cmd = \Config\Clases\Conexion::getConexion();
    $sql = "SELECT `id_cargue`, `id_pto`, `cod_pptal`, `nom_rubro`, `tipo_dato` 
            FROM `pto_cargue` 
            WHERE `id_pto` = $id_pto_presupuestos";
    $rs = $cmd->query($sql);
    $rubros = $rs->fetchAll(PDO::FETCH_ASSOC);
    $rs->closeCursor();
    unset($rs);
    $cmd = null;
} catch (PDOException $e) {
    echo $e->getCode() == 2002 ? 'Sin Conexión a Mysql (Error: 2002)' : 'Error: ' . $e->getCode();
}

try {
    $cmd = \Config\Clases\Conexion::getConexion();
    $sql = "SELECT
                `id_situacion`,
                `concepto`
            FROM `pto_situacion`";
    $rs = $cmd->query($sql);
    $situacion = $rs->fetchAll(PDO::FETCH_ASSOC);
    $rs->closeCursor();
    unset($rs);
    $cmd = null;
} catch (PDOException $e) {
    echo $e->getCode() == 2002 ? 'Sin Conexión a Mysql (Error: 2002)' : 'Error: ' . $e->getCode();
}

try {
    $cmd = \Config\Clases\Conexion::getConexion();
    $sql = "SELECT `nombre`, `id_tipo`
            FROM `pto_presupuestos` 
            WHERE `id_pto`= $id_pto_presupuestos";
    $rs = $cmd->query($sql);
    $nomPresupuestos = $rs->fetch(PDO::FETCH_ASSOC);
    $rs->closeCursor();
    unset($rs);
    $cmd = null;
} catch (PDOException $e) {
    echo $e->getCode() == 2002 ? 'Sin Conexión a Mysql (Error: 2002)' : 'Error: ' . $e->getCode();
}

try {
    $cmd = \Config\Clases\Conexion::getConexion();
    if ($nomPresupuestos['id_tipo'] == 1) {
        $tabla = '`pto_homologa_ingresos`';
        $campos = '';
        $condicion = '';
    } else if ($nomPresupuestos['id_tipo'] == 2) {
        $tabla = '`pto_homologa_gastos`';
        $campos = ' , `pto_vigencias`.`id_vigencia` AS `codigo_vig`
                    , `pto_vigencias`.`vigencia` AS `nombre_vig`
                    , `pto_homologa_gastos`.`id_seccion`
                    , `pto_seccion`.`id_seccion` AS `codigo_secc`
                    , `pto_seccion`.`seccion` AS `nombre_secc`
                    , `pto_homologa_gastos`.`id_sector`
                    , `pto_sector`.`id_sector` AS `codigo_sect`
                    , `pto_sector`.`sector` AS `nombre_sect`
                    , `pto_homologa_gastos`.`id_csia`
                    , `pto_clase_sia`.`codigo` AS `codigo_csia`
                    , `pto_clase_sia`.`clase_sia` AS `nombre_csia`
                    , `pto_homologa_gastos`.`id_mh`';
        $condicion = 'INNER JOIN `pto_vigencias` 
                        ON (`pto_homologa_gastos`.`id_vigencia` = `pto_vigencias`.`id_vigencia`)
                    INNER JOIN `pto_seccion` 
                        ON (`pto_homologa_gastos`.`id_seccion` = `pto_seccion`.`id_seccion`)
                    INNER JOIN `pto_sector` 
                        ON (`pto_homologa_gastos`.`id_sector` = `pto_sector`.`id_sector`)
                    INNER JOIN `pto_clase_sia` 
                        ON (`pto_homologa_gastos`.`id_csia` = `pto_clase_sia`.`id_csia`)';
    }
    $sql = "SELECT
                $tabla.`id_homologacion`
                , $tabla.`id_cargue`
                , $tabla.`id_cgr`
                , `pto_codigo_cgr`.`codigo` AS `codigo_cgr`
                , `pto_codigo_cgr`.`nombre` AS `nombre_cgr`
                , $tabla.`id_cpc`
                , `pto_cpc`.`codigo` AS `codigo_cpc`
                , `pto_cpc`.`division` AS `nombre_cpc`
                , $tabla.`id_fuente`
                , `pto_fuente`.`codigo` AS `codigo_fte`
                , `pto_fuente`.`fuente` AS `nombre_fte`
                , $tabla.`id_tercero`
                , `pto_terceros`.`codigo` AS `codigo_ter`
                , `pto_terceros`.`entidad` AS `nombre_ter`
                , $tabla.`id_politica`
                , `pto_politica`.`codigo` AS `codigo_pol`
                , `pto_politica`.`politica` AS `nombre_pol`
                , $tabla.`id_siho`
                , `pto_siho`.`codigo` AS `codigo_siho`
                , `pto_siho`.`nombre` AS `nombre_siho`
                , $tabla.`id_sia`
                , `pto_sia`.`codigo` AS `codigo_sia`
                , `pto_sia`.`nombre` AS `nombre_sia`
                , $tabla.`id_situacion`
                , `pto_situacion`.`concepto`
                , $tabla.`id_vigencia`
                $campos
            FROM
                $tabla
                INNER JOIN `pto_codigo_cgr` 
                    ON ($tabla.`id_cgr` = `pto_codigo_cgr`.`id_cod`)
                INNER JOIN `pto_cpc` 
                    ON ($tabla.`id_cpc` = `pto_cpc`.`id_cpc`)
                INNER JOIN `pto_fuente` 
                    ON ($tabla.`id_fuente` = `pto_fuente`.`id_fuente`)
                INNER JOIN `pto_politica` 
                    ON ($tabla.`id_politica` = `pto_politica`.`id_politica`)
                INNER JOIN `pto_terceros` 
                    ON ($tabla.`id_tercero` = `pto_terceros`.`id_tercero`)
                INNER JOIN `pto_siho` 
                    ON ($tabla.`id_siho` = `pto_siho`.`id_siho`)
                INNER JOIN `pto_sia` 
                    ON ($tabla.`id_sia` = `pto_sia`.`id_sia`)
                INNER JOIN `pto_situacion` 
                    ON ($tabla.`id_situacion` = `pto_situacion`.`id_situacion`)
                $condicion
                INNER JOIN `pto_cargue` 
                    ON ($tabla.`id_cargue` = `pto_cargue`.`id_cargue`)
            WHERE (`pto_cargue`.`id_pto` = $id_pto_presupuestos)";
    $rs = $cmd->query($sql);
    $homologacion = $rs->fetchAll(PDO::FETCH_ASSOC);
    $rs->closeCursor();
    unset($rs);
    $cmd = null;
} catch (PDOException $e) {
    echo $e->getCode() == 2002 ? 'Sin Conexión a Mysql (Error: 2002)' : 'Error: ' . $e->getCode();
}

$ingreso = empty($homologacion) ? 0 : 1;
$gasto = empty($homologacion) ? 0 : 1;

// Generar tabla dinámica
ob_start();
include 'componentes/tabla_homologacion.php'; // Separar la lógica de la tabla
$tabla_html = ob_get_clean();

$content =
    <<<HTML
    <div class="card w-100">
        <div class="card-header bg-sofia text-white">
            <button class="btn btn-sm me-1 p-0" title="Regresar" onclick="window.location.href='lista_presupuestos.php';"><i class="fas fa-arrow-left fa-lg"></i></button>
            <b>HOMOLOGACIONES A {$nomPresupuestos['nombre']}</b>
        </div>
        <div class="card-body p-2 bg-wiev">
            <input type="hidden" id="peReg" value="{$peReg}">
            
            <div class="table-responsive shadow p-2">
                <form id="formDataHomolPto">
                    <input type="hidden" id="id_pto_tipo" name="id_pto_tipo" value="{$nomPresupuestos['id_tipo']}">
                    $tabla_html
                </form>
            </div>
            
            <div class="text-center pt-4">
                <a type="button" class="btn btn-secondary btn-sm" style="width: 7rem;" href="lista_presupuestos.php">Regresar</a>
                <button type="button" class="btn btn-success btn-sm" style="width: 7rem;" id="setHomologacionPto">Modificar</button>
            </div>
        </div>
    </div>
    HTML;

$plantilla = new Plantilla($content, 2);
$plantilla->addCssFile("{$host}/assets/css/jquery-ui.css?v=" . date("YmdHis"));
$plantilla->addScriptFile("{$host}/assets/js/jquery-ui.js?v=" . date("YmdHis"));
$plantilla->addScriptFile("{$host}/src/presupuesto/js/libros_aux_pto/common.js?v=" . date("YmdHis"));
$plantilla->addScriptFile("{$host}/src/presupuesto/js/funcionpresupuesto.js?v=" . date("YmdHis"));
$modal = $plantilla->getModal('divModalForms', 'divTamModalForms', 'divForms');
$plantilla->addModal($modal);
$modal = $plantilla->getModal('divModalReg', 'divTamModalReg', 'divFormsReg');
$plantilla->addModal($modal);
$modal = $plantilla->getModal('divModalImp', 'divTamModalImp', 'divImp');
$plantilla->addModal($modal);
echo $plantilla->render();
