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
$peReg =  $permisos->PermisosUsuario($opciones, 5401, 0) || $id_rol == 1 ? 1 : 0;
$host = Plantilla::getHost();

$id_pto_presupuestos = $_POST['id_pto'];
$vigencia = $_SESSION['vigencia'];
try {
    $cmd = \Config\Clases\Conexion::getConexion();
    $sql = "SELECT `nombre` FROM `pto_presupuestos` WHERE `id_pto` = $id_pto_presupuestos";
    $rs = $cmd->query($sql);
    $nomPresupuestos = $rs->fetch();
} catch (PDOException $e) {
    echo $e->getCode() == 2002 ? 'Sin Conexión a Mysql (Error: 2002)' : 'Error: ' . $e->getCode();
}
// consulto el numero de registros en ctt_adquisiciones con estado 5 e id_cdp =0
try {
    $cmd = \Config\Clases\Conexion::getConexion();
    $sql = "SELECT `id_adquisicion` FROM `ctt_adquisiciones` WHERE `estado` = 6 AND (`id_cdp` = 1 OR `id_cdp` IS NULL) AND `vigencia` = $vigencia";
    $rs = $cmd->query($sql);
    // buscar num rows de la consulta
    $numadq = $rs->rowCount();
} catch (PDOException $e) {
    echo $e->getCode() == 2002 ? 'Sin Conexión a Mysql (Error: 2002)' : 'Error: ' . $e->getCode();
}
try {
    // consulta la cantidad de registros que tiene la tabla  ctt_novedad_adicion_prorroga donde cdp es null
    $sql = "SELECT COUNT(*) FROM `ctt_novedad_adicion_prorroga` WHERE (`id_cdp` IS NULL);";
    $rs = $cmd->query($sql);
    $total = $rs->fetchColumn();
} catch (PDOException $e) {
    echo $e->getCode() == 2002 ? 'Sin Conexión a Mysql (Error: 2002)' : 'Error: ' . $e->getCode();
}
try {
    $cmd = \Config\Clases\Conexion::getConexion();
    $sql = "SELECT '0' AS`patronal`, `id_nomina`, `estado`, `descripcion`, `mes`, `vigencia`, `tipo` FROM `nom_nominas` WHERE `estado` = 2
            UNION
            SELECT	
                    `t1`.`seg_patronal` + `t2`.`parafiscales` AS `patronal`
                    , `t1`.`id_nomina`
                    , `nom_nominas`.`planilla` AS estado
                    , `nom_nominas`.`descripcion`
                    , `nom_nominas`.`mes`
                    , `nom_nominas`.`vigencia`
                    , 'P' AS `tipo`
            FROM
                    (SELECT
                        SUM(`aporte_salud_empresa`) + SUM(`aporte_pension_empresa`) + SUM(`aporte_rieslab`) AS `seg_patronal`
                        , `nn`.`vigencia`
                        , `nn`.`id_nomina`
                    FROM
                        `nom_liq_segsocial_empdo` AS `nlse`
                    INNER JOIN `nom_nominas` AS `nn` 
                        ON (`nlse`.`id_nomina` = `nn`.`id_nomina`)
                    WHERE `nn`.`vigencia` = '$vigencia' AND `nlse`.`estado` = 1
                    GROUP BY `nn`.`id_nomina`) AS`t1`
                    LEFT JOIN 
                    (SELECT
                        SUM(`val_sena`) + SUM(`val_icbf`) + SUM(`val_comfam`) AS `parafiscales`
                        , `nn`.`vigencia`
                        , `nn`.`id_nomina` 
                    FROM
                        `nom_liq_parafiscales` AS `nlp`
                    INNER JOIN `nom_nominas` AS `nn` 
                        ON (`nlp`.`id_nomina` = `nn`.`id_nomina`)
                    WHERE `nn`.`vigencia` = '$vigencia' AND `nlp`.`estado` = 1
                    GROUP BY `nn`.`id_nomina`) AS `t2`
                    ON (`t1`.`id_nomina` = `t2`.`id_nomina`)
            INNER JOIN `nom_nominas` 
                    ON (`t1`.`id_nomina` = `nom_nominas`.`id_nomina`)
            WHERE `nom_nominas`.`planilla` = 2";
    $rs = $cmd->query($sql);
    $nominas = $rs->fetchAll(PDO::FETCH_ASSOC);
    $rs->closeCursor();
    unset($rs);
    $cant_nominas = count($nominas);
    $cmd = null;
} catch (PDOException $e) {
    echo $e->getCode() == 2002 ? 'Sin Conexión a Mysql (Error: 2002)' : 'Error: ' . $e->getCode();
}

$content =
    <<<HTML
    <div class="card w-100">
        <div class="card-header bg-sofia text-white">
            <a class="btn btn-xs me-1 p-0" title="Regresar" href="lista_presupuestos.php"><i class="fas fa-arrow-left fa-lg"></i></a>
            <b>EJECUCION {$nomPresupuestos['nombre']}</b>
        </div>
        <div id="accordionCtt" class="card-body p-2 bg-wiev">
            <input type="hidden" id="peReg" value="{$peReg}">
            <input type="hidden" id="id_pto_ppto" value="{$id_pto_presupuestos}">
            <div class="d-flex justify-content-center align-items-center gap-2 flex-wrap">
                <form action="{$_SERVER['PHP_SELF']}" method="POST">
                    <select class="form-select form-select-sm bg-input" id="slcMesHe" name="slcMesHe" onchange="cambiaListado(value)">
                        <option selected value='1'>CDP - CERTIFICADO DE DISPONIBILIDAD PRESUPUESTAL</option>
                        <option value='2'>CRP - CERTIFICADO DE REGISTRO PRESUPUESTAL</option>
                    </select>
                </form>
                <button type="button" class="btn btn-sm btn-outline-primary" id="botonContrata">
                    Contratación <span class="badge bg-light text-dark">{$numadq}</span>
                </button>
                <button type="button" class="btn btn-sm btn-outline-warning" id="botonOtrosi">
                    Adición <span class="badge bg-light text-dark">{$total}</span>
                </button>
                <button type="button" class="btn btn-sm btn-outline-success" id="btnPtoNomina">
                    <input type="hidden" id="cantidad" value="{$cant_nominas}">
                    Nomina <span class="badge bg-light text-dark" id="nCant"> {$cant_nominas}</span>
                </button>
            </div>
            <div class="row">
                <div class="mb-3 col-md-5">
                    <label for="txt_tercero_filtro" class="small">Historial Terceros</label>
                    <div class="input-group input-group-sm">
                        <input type="text" class="filtro form-control bg-input" id="txt_tercero_filtro" name="txt_tercero_filtro" placeholder="Tercero">
                        <input type="hidden" id="id_txt_tercero" name="id_txt_tercero" class="form-control form-control-sm" value="0">
                        <button type="button" id="btn_historialtercero" class="btn btn-outline-success" title="Historial tercero">
                            <span class="fas fa-history fa-lg" aria-hidden="true"></span>
                        </button>
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="mb-3 col-md-1">
                    <div class="input-group">
                        <div class="input-group-text">
                            <input class="form-check-input mt-0" type="checkbox" value="" title="Marcar para filtrar por valor exacto" id="txt_bandera_filtro">
                        </div>
                        <input type="text" class="filtro form-control form-control-sm bg-input" id="txt_idmanu_filtro" placeholder="Id. Manu">
                    </div>
                </div>
                <div class="mb-3 col-md-3">
                    <div class="row">
                        <div class="mb-3 col-md-6">
                            <input type="date" class="form-control form-control-sm bg-input" id="txt_fecini_filtro" name="txt_fecini_filtro" placeholder="Fecha Inicial">
                        </div>
                        <div class="mb-3 col-md-6">
                            <input type="date" class="form-control form-control-sm bg-input" id="txt_fecfin_filtro" name="txt_fecfin_filtro" placeholder="Fecha Final">
                        </div>
                    </div>
                </div>
                <div class="mb-3 col-md-5">
                    <input type="text" class="filtro form-control form-control-sm bg-input" id="txt_objeto_filtro" placeholder="Objeto">
                </div>
                <div class="mb-3 col-md-1">
                    <select class="form-select form-select-sm bg-input" id="sl_estado_filtro">
                        <option value="0">--Estado--</option>
                        <option value="1">Abierto</option>
                        <option value="2">Cerrado</option>
                        <option value="3">Anulado</option>
                    </select>
                </div>
                <div class="mb-3 col-md-1">
                    <a type="button" id="btn_buscar_filtro" class="btn btn-outline-success btn-sm" title="Filtrar">
                        <span class="fas fa-search fa-lg" aria-hidden="true"></span>
                    </a>
                </div>
            </div>
            <div class="table-responsive shadow p-2">
                <table id="tableEjecPresupuesto" class="table table-striped table-bordered table-sm table-hover shadow w-100">
                    <thead>
                        <tr class="text-center">
                            <th class="bg-sofia">Numero</th>
                            <th class="bg-sofia">Fecha</th>
                            <th class="bg-sofia">Objeto</th>
                            <th class="bg-sofia">Valor CDP</th>
                            <th class="bg-sofia">X Registrar</th>
                            <th class="bg-sofia">Registro</th>
                            <th class="bg-sofia">Acciones</th>
                        </tr>
                    </thead>
                    <tbody id="modificarEjecPresupuesto">
                    </tbody>
                </table>
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
