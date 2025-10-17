<?php
session_start();
if (!isset($_SESSION['user'])) {
    header('Location: ../../../../index.php');
    exit();
}
include_once '../../../../../config/autoloader.php';

$id_aquisicion = isset($_POST['id']) ? $_POST['id'] : exit('Acción no permitida ');
$iduser = $_SESSION['id_user'];
$id_rol = $_SESSION['rol'];
$permisos = new \Src\Common\Php\Clases\Permisos();
$opciones = $permisos->PermisoOpciones($iduser);

$error = "Debe diligenciar este campo";
try {
    $cmd = \Config\Clases\Conexion::getConexion();

    $sql = "SELECT
                `id_adquisicion`, `id_modalidad`, `id_area`, `fecha_adquisicion`, `val_contrato`, `id_tipo_bn_sv`, `vigencia`, `obligaciones`, `objeto`, `id_tercero`, `estado`
            FROM
                `ctt_adquisiciones`
            WHERE (`id_adquisicion` = $id_aquisicion)";
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
                , `ctt_adquisiciones`.`id_modalidad`
                , `ctt_adquisiciones`.`id_area`
                , `ctt_adquisiciones`.`fecha_adquisicion`
                , `ctt_adquisiciones`.`val_contrato`
                , `ctt_adquisiciones`.`id_tipo_bn_sv`
                , `ctt_adquisiciones`.`vigencia`
                , `ctt_adquisiciones`.`obligaciones`
                , `ctt_adquisiciones`.`objeto`
                , `ctt_adquisiciones`.`id_tercero`
                , `ctt_adquisiciones`.`estado`
                , `ctt_orden_compra_detalle`.`id_servicio` AS `id_bn_sv`
                , `ctt_orden_compra_detalle`.`cantidad`
                , `ctt_orden_compra_detalle`.`val_unid` AS `val_estimado_unid`
            FROM
                `ctt_orden_compra_detalle`
            INNER JOIN `ctt_orden_compra` 
                ON (`ctt_orden_compra_detalle`.`id_oc` = `ctt_orden_compra`.`id_oc`)
            INNER JOIN `ctt_adquisiciones`
                ON (`ctt_orden_compra`.`id_adq` = `ctt_adquisiciones`.`id_adquisicion`)
            WHERE (`ctt_orden_compra`.`id_adq` = $id_aquisicion)";
    $detalles = $rs->fetchAll(PDO::FETCH_ASSOC);
    $rs->closeCursor();
    unset($rs);
    $cmd = null;
} catch (PDOException $e) {
    echo $e->getCode() == 2002 ? 'Sin Conexión a Mysql (Error: 2002)' : 'Error: ' . $e->getMessage();
}
try {
    $cmd = \Config\Clases\Conexion::getConexion();

    $sql = "SELECT 
                `id_adquisicion`,`id_area_cc` AS `id_centro_costo`,`horas_mes`, `id_sede`
            FROM `ctt_destino_contrato` 
            INNER JOIN `far_centrocosto_area` 
                ON (`ctt_destino_contrato`.`id_area_cc` = `far_centrocosto_area`.`id_area`)
            WHERE `id_adquisicion` = $id_aquisicion";
    $rs = $cmd->query($sql);
    $destino = $rs->fetchAll(PDO::FETCH_ASSOC);
    $rs->closeCursor();
    unset($rs);
    $cmd = null;
} catch (PDOException $e) {
    echo $e->getCode() == 2002 ? 'Sin Conexión a Mysql (Error: 2002)' : 'Error: ' . $e->getMessage();
}
try {
    $cmd = \Config\Clases\Conexion::getConexion();

    $sql = "SELECT
                `id_est_prev`, `id_compra`, `fec_ini_ejec`, `fec_fin_ejec`, `val_contrata`, `id_forma_pago`, `id_supervisor`, `necesidad`, `act_especificas`, `prod_entrega`, `obligaciones`, `forma_pago`, `num_ds`, `requisitos`, `garantia`, `describe_valor`
            FROM `ctt_estudios_previos`
            WHERE `id_compra` = $id_aquisicion";
    $rs = $cmd->query($sql);
    $estudios = $rs->fetch(PDO::FETCH_ASSOC);
    $cmd = null;
} catch (PDOException $e) {
    echo $e->getCode() == 2002 ? 'Sin Conexión a Mysql (Error: 2002)' : 'Error: ' . $e->getMessage();
}
try {
    $cmd = \Config\Clases\Conexion::getConexion();

    $sql = "SELECT * FROM `ctt_modalidad` ORDER BY `modalidad` ASC";
    $rs = $cmd->query($sql);
    $modalidad = $rs->fetchAll(PDO::FETCH_ASSOC);
    $rs->closeCursor();
    unset($rs);
    $cmd = null;
} catch (PDOException $e) {
    echo $e->getCode() == 2002 ? 'Sin Conexión a Mysql (Error: 2002)' : 'Error: ' . $e->getMessage();
}
try {
    $cmd = \Config\Clases\Conexion::getConexion();

    $sql = "SELECT `id_area`, `area` FROM `tb_area_c` ORDER BY `area` ASC";
    $rs = $cmd->query($sql);
    $areas = $rs->fetchAll(PDO::FETCH_ASSOC);
    $rs->closeCursor();
    unset($rs);
    $cmd = null;
} catch (PDOException $e) {
    echo $e->getCode() == 2002 ? 'Sin Conexión a Mysql (Error: 2002)' : 'Error: ' . $e->getMessage();
}
try {
    $cmd = \Config\Clases\Conexion::getConexion();

    $sql = "SELECT 
                `id_tipo_b_s`, `tipo_compra`, `tipo_bn_sv`
            FROM
                `tb_tipo_bien_servicio`
            INNER JOIN `tb_tipo_compra` 
                ON (`tb_tipo_bien_servicio`.`id_tipo` = `tb_tipo_compra`.`id_tipo`)
            ORDER BY `tipo_compra`, `tipo_bn_sv`";
    $rs = $cmd->query($sql);
    $tbnsv = $rs->fetchAll(PDO::FETCH_ASSOC);
    $rs->closeCursor();
    unset($rs);
    $cmd = null;
} catch (PDOException $e) {
    echo $e->getCode() == 2002 ? 'Sin Conexión a Mysql (Error: 2002)' : 'Error: ' . $e->getMessage();
}
try {
    $cmd = \Config\Clases\Conexion::getConexion();

    $sql = "SELECT `id_sede`,`nom_sede` AS `nombre` FROM `tb_sedes`";
    $rs = $cmd->query($sql);
    $sedes = $rs->fetchAll(PDO::FETCH_ASSOC);
    $rs->closeCursor();
    unset($rs);
    $cmd = null;
} catch (PDOException $e) {
    echo $e->getCode() == 2002 ? 'Sin Conexión a Mysql (Error: 2002)' : 'Error: ' . $e->getMessage();
}
try {
    $cmd = \Config\Clases\Conexion::getConexion();

    $sql = "SELECT
                `id_form_pago`, `descripcion`
            FROM
                `tb_forma_pago_compras` ORDER BY `descripcion` ASC ";
    $rs = $cmd->query($sql);
    $forma_pago = $rs->fetchAll(PDO::FETCH_ASSOC);
    $rs->closeCursor();
    unset($rs);
    $cmd = null;
} catch (PDOException $e) {
    echo $e->getCode() == 2002 ? 'Sin Conexión a Mysql (Error: 2002)' : 'Error: ' . $e->getMessage();
}
try {
    $cmd = \Config\Clases\Conexion::getConexion();

    $sql = "SELECT
                `tb_terceros`.`id_tercero`
                , `tb_rel_tercero`.`id_tercero_api`
                , `tb_terceros`.`nom_tercero`
                , `tb_terceros`.`nit_tercero`
            FROM
                `tb_rel_tercero`
                INNER JOIN `tb_terceros` 
                    ON (`tb_rel_tercero`.`id_tercero_api` = `tb_terceros`.`id_tercero_api`)
            WHERE `tb_terceros`.`estado` = 1 AND `tb_rel_tercero`.`id_tipo_tercero` = 3";
    $rs = $cmd->query($sql);
    $supervisor = $rs->fetchAll(PDO::FETCH_ASSOC);
    $rs->closeCursor();
    unset($rs);
    $cmd = null;
} catch (PDOException $e) {
    echo $e->getCode() == 2002 ? 'Sin Conexión a Mysql (Error: 2002)' : 'Error: ' . $e->getMessage();
}
try {
    $cmd = \Config\Clases\Conexion::getConexion();

    $sql = "SELECT
            `id_poliza`
            , `descripcion`
            , `porcentaje`
        FROM
            `tb_polizas` ORDER BY `descripcion` ASC";
    $rs = $cmd->query($sql);
    $polizas = $rs->fetchAll(PDO::FETCH_ASSOC);
    $rs->closeCursor();
    unset($rs);
    $cmd = null;
} catch (PDOException $e) {
    echo $e->getCode() == 2002 ? 'Sin Conexión a Mysql (Error: 2002)' : 'Error: ' . $e->getMessage();
}
$est_prev = $estudios['id_est_prev'];
try {
    $cmd = \Config\Clases\Conexion::getConexion();

    $sql = "SELECT
                `id_garantia`, `id_est_prev`, `id_poliza`
            FROM
                `seg_garantias_compra`
            WHERE `id_est_prev`  = $est_prev";
    $rs = $cmd->query($sql);
    $garantias = $rs->fetchAll(PDO::FETCH_ASSOC);
    $rs->closeCursor();
    unset($rs);
    $cmd = null;
} catch (PDOException $e) {
    echo $e->getCode() == 2002 ? 'Sin Conexión a Mysql (Error: 2002)' : 'Error: ' . $e->getMessage();
}
?>
<div class="px-0">
    <div class="shadow">
        <div class="card-header text-center py-2" style="background-color: #16a085 !important;">
            <h5 style="color: white;">DUPLICAR ADQUISICIÓN</h5>
        </div>
        <form id="formDuplicaAdq">
            <input type="hidden" name="id_compra" value="<?php echo $id_aquisicion ?>">
            <div class="row px-4 pt-2">
                <div class="col-md-3 mb-3">
                    <label for="datFecAdq" class="small">FECHA ADQUISICIÓN</label>
                    <input type="date" name="datFecAdq" id="datFecAdq" class="form-control form-control-sm bg-input" value="<?php echo $adquisicion['fecha_adquisicion'] ?>">
                </div>
                <input type="hidden" name="datFecVigencia" value="<?php echo $_SESSION['vigencia'] ?>">
                <div class="col-md-3 mb-3">
                    <label for="slcModalidad" class="small">MODALIDAD CONTRATACIÓN</label>
                    <select id="slcModalidad" name="slcModalidad" class="form-select form-select-sm bg-input" aria-label="Default select example">
                        <?php
                        foreach ($modalidad as $mo) {
                            $slc = $mo['id_modalidad'] == $adquisicion['id_modalidad'] ? 'selected' : '';
                            echo '<option ' . $slc . ' value="' . $mo['id_modalidad'] . '">' . $mo['modalidad'] . '</option>';
                        }
                        ?>
                    </select>
                </div>
                <div class="form-group col-md-3">
                    <label for="numTotalContrato" class="small">VALOR ESTIMADO</label>
                    <input type="number" name="numTotalContrato" id="numTotalContrato" class="form-control form-control-sm bg-input" value="<?php echo $adquisicion['val_contrato'] ?>">
                </div>
                <div class="col-md-3 mb-3">
                    <label for="slcAreaSolicita" class="small">ÁREA SOLICITANTE</label>
                    <select id="slcAreaSolicita" name="slcAreaSolicita" class="form-select form-select-sm bg-input" aria-label="Default select example">
                        <?php
                        foreach ($areas as $ar) {
                            $slc = $ar['id_area'] == $adquisicion['id_area'] ? 'selected' : '';
                            echo '<option value="' . $ar['id_area'] . '" ' . $slc . '>' . $ar['area'] . '</option>';
                        }
                        ?>
                    </select>
                </div>
            </div>
            <div class="row px-4">
                <div class="col-md-12 mb-3">
                    <label for="slcTipoBnSv" class="small">TIPO DE BIEN O SERVICIO</label>
                    <select id="slcTipoBnSv" name="slcTipoBnSv" class="form-select form-select-sm bg-input" aria-label="Default select example">
                        <?php
                        foreach ($tbnsv as $tbs) {
                            $slc = $tbs['id_tipo_b_s'] == $adquisicion['id_tipo_bn_sv'] ? 'selected' : '';
                            echo '<option value="' . $tbs['id_tipo_b_s'] . '" ' . $slc . '>' . $tbs['tipo_compra'] . ' || ' . $tbs['tipo_bn_sv'] . '</option>';
                        }
                        ?>
                    </select>
                </div>
            </div>
            <div class="row px-4 pt-2">
                <div class="col-md-12 mb-3">
                    <textarea id="txtObjeto" type="text" name="txtObjeto" class="form-control form-control-sm bg-input" aria-label="Default select example" rows="3"><?php echo $adquisicion['objeto'] ?></textarea>
                </div>
            </div>
            <div id="contenedor" class="px-4">
                <?php
                $num = 1;
                foreach ($destino as $des) {
                    $id_cc = $des['id_centro_costo'];
                    $id_sede = $des['id_sede'];
                    try {
                        $cmd = \Config\Clases\Conexion::getConexion();

                        $sql = "SELECT
                                    `far_centrocosto_area`.`id_area` AS `id_sede`
                                    , `tb_centrocostos`.`nom_centro` AS `descripcion`
                                FROM
                                    `far_centrocosto_area`
                                    INNER JOIN `tb_sedes` 
                                        ON (`far_centrocosto_area`.`id_sede` = `tb_sedes`.`id_sede`)
                                    INNER JOIN `tb_centrocostos` 
                                        ON (`far_centrocosto_area`.`id_centrocosto` = `tb_centrocostos`.`id_centro`)
                                WHERE `far_centrocosto_area`.`id_sede` = $id_sede ORDER BY `descripcion` ASC";
                        $rs = $cmd->query($sql);
                        $centros = $rs->fetchAll(PDO::FETCH_ASSOC);
$rs->closeCursor();
unset($rs);
                        $cmd = null;
                    } catch (PDOException $e) {
                        echo $e->getCode() == 2002 ? 'Sin Conexión a Mysql (Error: 2002)' : 'Error: ' . $e->getMessage();
                    }
                    if ($num == 1) {
                ?>
                        <div class="row pt-2">
                            <div class="col-md-4 mb-2">
                                <label class="small">SEDE</label>
                                <select name="slcSedeAC[]" class="form-select form-select-sm slcSedeAC bg-input">
                                    <?php
                                    foreach ($sedes as $s) {
                                        $slc = $s['id_sede'] == $id_sede ? 'selected' : '';
                                        echo '<option value="' . $s['id_sede'] . '" ' . $slc . '>' . $s['nombre'] . '</option>';
                                    }
                                    ?>
                                </select>
                            </div>
                            <div class="col-md-4 mb-2">
                                <label class="small">CENTRO DE COSTO</label>
                                <select name="slcCentroCosto[]" class="form-select form-select-sm slcCentroCosto bg-input">
                                    <?php
                                    foreach ($centros as $c) {
                                        $slc = $c['id_sede'] == $des['id_centro_costo'] ? 'selected' : '';
                                        echo '<option value="' . $c['id_centro_costo'] . '" ' . $slc . '>' . $c['descripcion'] . '</option>';
                                    }
                                    ?>
                                </select>
                            </div>
                            <div class="col-md-4 mb-2">
                                <label for="numHorasMes" class="small">Horas asignadas / mes</label>
                                <div class="input-group input-group-sm">
                                    <input type="number" name="numHorasMes[]" class="form-control bg-input" value="<?php echo $des['horas_mes'] ?>">

                                    <button class="btn btn-outline-success" type="button" id="addRowSedes"><i class="fas fa-plus"></i></button>

                                </div>
                            </div>
                        </div>
                    <?php
                    } else {
                    ?>
                        <div class="row pt-2">
                            <div class="col-md-4 mb-2">
                                <select name="slcSedeAC[]" class="form-select form-select-sm slcSedeAC bg-input">
                                    <?php
                                    foreach ($sedes as $s) {
                                        $slc = $s['id_sede'] == $id_sede ? 'selected' : '';
                                        echo '<option value="' . $s['id_sede'] . '" ' . $slc . '>' . $s['nombre'] . '</option>';
                                    }
                                    ?>
                                </select>
                            </div>
                            <div class="col-md-4 mb-2">
                                <select name="slcCentroCosto[]" class="form-select form-select-sm slcCentroCosto bg-input">
                                    <?php
                                    foreach ($centros as $c) {
                                        $slc = $c['id_sede'] == $des['id_centro_costo'] ? 'selected' : '';
                                        echo '<option value="' . $c['id_centro_costo'] . '" ' . $slc . '>' . $c['descripcion'] . '</option>';
                                    }
                                    ?>
                                </select>
                            </div>
                            <div class="col-md-4 mb-2">
                                <div class="input-group input-group-sm">
                                    <input type="number" name="numHorasMes[]" class="form-control bg-input" value="<?php echo $des['horas_mes'] ?>">

                                    <button class="btn btn-outline-danger delRowSedes" type="button"><i class="fas fa-minus"></i></button>

                                </div>
                            </div>
                        </div>
                <?php
                    }
                    $num++;
                }
                ?>
            </div>
            <?php if (true) { ?>
                <div class="row px-4 pt-2">
                    <div class="col-md-12 mb-3">
                        <label for="buscaTercero" class="small">TERCERO</label>
                        <input type="text" id="buscaTercero" class="form-control form-control-sm bg-input">
                        <input type="hidden" id="id_tercero" name="id_tercero" value="0">
                    </div>
                </div>
            <?php } ?>
            <div class="row px-4 pt-2">
                <div class="col-md-4 mb-3">
                    <label for="datFecIniEjec" class="small">FECHA INICIAL</label>
                    <input type="date" name="datFecIniEjec" id="datFecIniEjec" class="form-control form-control-sm bg-input" value="<?php echo $estudios['fec_ini_ejec'] ?>">
                </div>
                <div class="col-md-4 mb-3">
                    <label for="datFecFinEjec" class="small">FECHA FINAL</label>
                    <input type="date" name="datFecFinEjec" id="datFecFinEjec" class="form-control form-control-sm bg-input" value="<?php echo $estudios['fec_fin_ejec'] ?>">
                </div>
                <div class="col-md-4 mb-3">
                    <label for="numValContrata" class="small">Valor total contrata</label>
                    <input type="number" name="numValContrata" id="numValContrata" class="form-control form-control-sm bg-input" value="<?php echo $estudios['val_contrata'] ?>">
                </div>
            </div>
            <div class="row px-4">
                <div class="col-md-4 mb-3">
                    <label for="slcFormPago" class="small">FORMA DE PAGO</label>
                    <select id="slcFormPago" name="slcFormPago" class="form-select form-select-sm bg-input" aria-label="Default select example">
                        <?php
                        foreach ($forma_pago as $fp) {
                            $slc = $fp['id_form_pago'] == $estudios['id_forma_pago'] ? 'selected' : '';
                            echo '<option value="' . $fp['id_form_pago'] . '" ' . $slc . '>' . $fp['descripcion'] . '</option>';
                        }
                        ?>
                    </select>
                </div>
                <div class="col-md-4 mb-3">
                    <label for="slcSupervisor" class="small">SUPERVISOR</label>
                    <select id="slcSupervisor" name="slcSupervisor" class="form-select form-select-sm bg-input" aria-label="Default select example">
                        <option value="A">PENDIENTE</option>
                        <?php
                        foreach ($supervisor as $s) {
                            $slc = $s['id_tercero_api'] == $estudios['id_supervisor'] ? 'selected' : '';
                            echo '<option value="' . $s['id_tercero_api'] . '">' . $s['nom_tercero'] . '</option>';
                        }
                        ?>
                    </select>
                </div>
                <div class="col-md-4 mb-3">
                    <label for="numDS" class="small">Número DC</label>
                    <input type="number" name="numDS" id="numDS" class="form-control form-control-sm bg-input" value="<?php echo $estudios['num_ds'] ?>">
                </div>
            </div>
            <div class="row px-4 pt-2">
                <?php
                if (count($polizas) > 0) {
                ?>
                    <span class="small">PÓLIZAS</span>
                <?php
                }
                $cant = 1;
                foreach ($polizas as $pz) {
                    $chequeado = '';
                    $idp = $pz['id_poliza'];
                    $key = array_search($idp, array_column($garantias, 'id_poliza'));
                    if (false !== $key) {
                        $chequeado = 'checked';
                    }
                ?>
                    <div class="col-md-4 mb-3">
                        <div class="input-group input-group-sm">
                            <div class="input-group-text">
                                <input class="form-check-input mt-0" type="checkbox" aria-label="Checkbox for following text input" id="check_<?php echo $cant;
                                                                                                                                                $cant++ ?>" name="check[]" value="<?php echo $pz['id_poliza'] ?>" <?php echo $chequeado ?>>
                            </div>
                            <div class="form-control form-control-sm" aria-label="Text input with checkbox" style="font-size: 55%;"><?php echo $pz['descripcion'] . ' ' . $pz['porcentaje'] . '%' ?> </div>
                        </div>
                    </div>
                <?php
                }
                ?>
            </div>
            <div class="px-4">
                <nav>
                    <div class="nav nav-tabs" id="nav-tab" role="tablist">
                        <button class="nav-link active small" id="nav_necesidad-tab" data-bs-toggle="tab" data-bs-target="#nav_necesidad" type="button" role="tab" aria-controls="nav_necesidad" aria-selected="true" title="Descripción de la necesidad">Necesidad</button>
                        <button class="nav-link small" id="nav-actividad-tab" data-bs-toggle="tab" data-bs-target="#nav-actividad" type="button" role="tab" aria-controls="nav-actividad" aria-selected="false">Actividades</button>
                        <button class="nav-link small" id="nav-producto-tab" data-bs-toggle="tab" data-bs-target="#nav-producto" type="button" role="tab" aria-controls="nav-producto" aria-selected="false" title="Productos a entregar">Productos</button>
                        <button class="nav-link small" id="nav-obligacion-tab" data-bs-toggle="tab" data-bs-target="#nav-obligacion" type="button" role="tab" aria-controls="nav-obligacion" aria-selected="false" title="Obligaciones del contratista">Obligaciones</button>
                        <button class="nav-link small" id="nav-valor-tab" data-bs-toggle="tab" data-bs-target="#nav-valor" type="button" role="tab" aria-controls="nav-valor" aria-selected="false" title="Descripción del valor">Valor</button>
                        <button class="nav-link small" id="nav-pago-tab" data-bs-toggle="tab" data-bs-target="#nav-pago" type="button" role="tab" aria-controls="nav-pago" aria-selected="false" title="Forma de Pago">Pago</button>
                        <button class="nav-link small" id="nav-requisito-tab" data-bs-toggle="tab" data-bs-target="#nav-requisito" type="button" role="tab" aria-controls="nav-requisito" aria-selected="false" title="Requisitos mínimos habilitanes">Requisitos</button>
                        <button class="nav-link small" id="nav-garantia-tab" data-bs-toggle="tab" data-bs-target="#nav-garantia" type="button" role="tab" aria-controls="nav-garantia" aria-selected="false" title="Garantías de Contratación">Garantías</button>
                    </div>
                </nav>
                <div class="tab-content" id="nav-tabContent">
                    <div class="tab-pane fade show active" id="nav_necesidad" role="tabpanel" aria-labelledby="nav_regTercro-tab">
                        <textarea name="txtDescNec" id="txtDescNec" cols="30" rows="14" class="form-control form-control-sm bg-input"><?= str_replace('<br />', '', nl2br(str_replace('||', "\n", $estudios['necesidad']))) ?></textarea>
                    </div>
                    <div class="tab-pane fade" id="nav-actividad" role="tabpanel" aria-labelledby="nav-actividad-tab">
                        <textarea name="txtActEspecificas" id="txtActEspecificas" cols="30" rows="14" class="form-control form-control-sm bg-input"><?= str_replace('<br />', '', nl2br(str_replace('||', "\n", $estudios['act_especificas']))) ?></textarea>
                    </div>
                    <div class="tab-pane fade" id="nav-producto" role="tabpanel" aria-labelledby="nav-producto-tab">
                        <textarea name="txtProdEntrega" id="txtProdEntrega" cols="30" rows="14" class="form-control form-control-sm bg-input"><?= str_replace('<br />', '', nl2br(str_replace('||', "\n", $estudios['prod_entrega']))) ?></textarea>
                    </div>
                    <div class="tab-pane fade" id="nav-obligacion" role="tabpanel" aria-labelledby="nav-obligacion-tab">
                        <textarea name="txtObligContratista" id="txtObligContratista" cols="30" rows="14" class="form-control form-control-sm bg-input"><?= str_replace('<br />', '', nl2br(str_replace('||', "\n", $estudios['obligaciones']))) ?></textarea>
                    </div>
                    <div class="tab-pane fade" id="nav-valor" role="tabpanel" aria-labelledby="nav-valor-tab">
                        <textarea name="txtDescValor" id="txtDescValor" cols="30" rows="14" class="form-control form-control-sm bg-input"><?= str_replace('<br />', '', nl2br(str_replace('||', "\n", $estudios['describe_valor']))) ?></textarea>
                    </div>
                    <div class="tab-pane fade" id="nav-pago" role="tabpanel" aria-labelledby="nav-pago-tab">
                        <textarea name="txtFormPago" id="txtFormPago" cols="30" rows="14" class="form-control form-control-sm bg-input"><?= str_replace('<br />', '', nl2br(str_replace('||', "\n", $estudios['forma_pago']))) ?></textarea>
                    </div>
                    <div class="tab-pane fade" id="nav-requisito" role="tabpanel" aria-labelledby="nav-requisito-tab">
                        <textarea name="txtReqMinHab" id="txtReqMinHab" cols="30" rows="14" class="form-control form-control-sm bg-input"><?= str_replace('<br />', '', nl2br(str_replace('||', "\n", $estudios['requisitos']))) ?></textarea>
                    </div>
                    <div class="tab-pane fade" id="nav-garantia" role="tabpanel" aria-labelledby="nav-garantia-tab">
                        <textarea name="txtGarantias" id="txtGarantias" cols="30" rows="14" class="form-control form-control-sm bg-input"><?= str_replace('<br />', '', nl2br(str_replace('||', "\n", $estudios['garantia']))) ?></textarea>
                    </div>
                </div>
            </div>
        </form>
        <div class="text-center">
            <div class="text-center py-3">
                <button class="btn btn-primary btn-sm" id="btnDuplicaAdq">Duplicar</button>
                <a type="button" class="btn btn-secondary  btn-sm" data-bs-dismiss="modal">Cancelar</a>
            </div>
        </div>

    </div>
</div>