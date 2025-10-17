<?php
session_start();
if (!isset($_SESSION['user'])) {
    header('Location: ../../../../../index.php');
    exit();
}

require_once '../../../../../config/autoloader.php';

$data = isset($_POST['datos']) ? explode('|', $_POST['datos']) : exit('Acción no permitida ');
$id_novedad = $data[0];
$opcion = $data[1];
try {
    $cmd = \Config\Clases\Conexion::getConexion();

    $sql = "SELECT `id_novedad` , `descripcion` FROM `ctt_tipo_novedad`";
    $rs = $cmd->query($sql);
    $tip_novedad = $rs->fetchAll(PDO::FETCH_ASSOC);
$rs->closeCursor();
unset($rs);
    $cmd = null;
} catch (PDOException $e) {
    echo $e->getCode() == 2002 ? 'Sin Conexión a Mysql (Error: 2002)' : 'Error: ' . $e->getMessage();
}
if (empty($tip_novedad)) {
    echo 'Error al intentar obetener tipos de novedad';
    exit();
}
$cmd = \Config\Clases\Conexion::getConexion();

switch ($opcion) {
    case 1:
    case 2:
    case 3:
        try {
            $cmd = \Config\Clases\Conexion::getConexion();
            $sql = "SELECT
                        `id_nov_con`, `val_adicion`, `fec_adcion`, `fec_ini_prorroga`, `fec_fin_prorroga`, `observacion`, `id_cdp`
                    FROM
                        `ctt_novedad_adicion_prorroga`
                    WHERE `id_nov_con` = ?";
            $rs = $cmd->prepare($sql);
            $rs->bindParam(1, $id_novedad, PDO::PARAM_INT);
            $rs->execute();
            $detalles_novedad = $rs->fetch();
            $cmd = null;
        } catch (PDOException $e) {
            echo $e->getCode() == 2002 ? 'Sin Conexión a Mysql (Error: 2002)' : 'Error: ' . $e->getMessage();
        }
?>
        <div class="px-0">
            <div class="shadow">
                <div class="card-header text-center py-2" style="background-color: #16a085 !important;">
                    <h5 style="color: white;">ACTUALIZAR/MODIFICAR ADICIÓN Y/O PRORROGA DE CONTRATO</h5>
                </div>
                <form id="formUpNovContrato">
                    <input type="hidden" name="id_novendad" value="<?php echo $id_novedad ?>">
                    <div class="row px-4 pt-2">
                        <div class="col-md-12 mb-3">
                            <label for="slcTipoNovedad" class="small">TIPO DE NOVEDAD</label>
                            <select id="slcTipoNovedad" name="slcTipoNovedad" class="form-select form-select-sm bg-input" aria-label="Default select example">
                                <?php
                                $ver1 = $ver2 = 'none';
                                foreach ($tip_novedad as $tn) {
                                    $slc = $opcion == $tn['id_novedad'] ? 'selected' : '';
                                    echo '<option ' . $slc . ' value="' . $tn['id_novedad'] . '">' . $tn['descripcion'] . '</option>';
                                }
                                if ($opcion == 3) {
                                    $ver1 = $ver2 = true;
                                } else if ($opcion == 2) {
                                    $ver2 = true;
                                } else {
                                    $ver1 = true;
                                }
                                ?>
                            </select>
                        </div>
                    </div>
                    <div class="row px-4" id="divAdicion" style="display: <?php echo $ver1 ?>;">
                        <div class="col-md-6 mb-3">
                            <label for="numValAdicion" class="small">VALOR</label>
                            <input type="number" name="numValAdicion" id="numValAdicion" class="form-control form-control-sm bg-input" value="<?php echo $detalles_novedad['val_adicion'] ?>">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="datFecAdicion" class="small">FECHA DE ADICIÓN</label>
                            <input type="date" name="datFecAdicion" id="datFecAdicion" class="form-control form-control-sm bg-input" value="<?php echo $detalles_novedad['fec_adcion'] ?>">
                        </div>
                    </div>
                    <?php if (false) { ?>
                        <div class="row px-4" id="divCDPadicion" style="display: <?php echo $ver1 ?>;">
                            <div class="col-md-12 mb-3">
                                <label for="slcCDP" class="small">CDP</label>
                                <select id="slcCDP" name="slcCDP" class="form-select form-select-sm bg-input" aria-label="Default select example">
                                    <option value="1">CDP-001</option>
                                </select>
                            </div>
                        </div>
                    <?php } ?>
                    <div class="row px-4" id="divProrroga" style="display: <?php echo $ver2 ?>;">
                        <div class="col-md-6 mb-3">
                            <label for="datFecIniProrroga" class="small">FECHA INICIAL</label>
                            <input type="date" name="datFecIniProrroga" id="datFecIniProrroga" class="form-control form-control-sm bg-input" value="<?php echo $detalles_novedad['fec_ini_prorroga'] ?>">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="datFecFinProrroga" class="small">FECHA FINAL</label>
                            <input type="date" name="datFecFinProrroga" id="datFecFinProrroga" class="form-control form-control-sm bg-input" value="<?php echo $detalles_novedad['fec_fin_prorroga'] ?>">
                        </div>
                    </div>
                    <div class="row px-4" id="divObservaNov">
                        <div class="col-md-12 mb-3">
                            <label for="txtAObservaNov" class="small">OBSERVACIONES</label>
                            <textarea class="form-control bg-input" id="txtAObservaNov" name="txtAObservaNov" rows="3"><?php echo $detalles_novedad['observacion'] ?></textarea>
                        </div>
                    </div>
                    <div class="text-center pb-3">
                        <button class="btn btn-primary btn-sm" id="btnUpNovContrato">Actualizar</button>
                        <a type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal"> Cancelar</a>
                    </div>
                </form>
            </div>
        </div>
    <?php
        break;
    case 4:
        try {
            $cmd = \Config\Clases\Conexion::getConexion();
            $sql = "SELECT
                        `cnc`.`id_cesion`, `id_adq`
                        , `cnc`.`id_tipo_nov`
                        , `cnc`.`id_tercero`
                        , `cnc`.`fec_cesion`
                        , `cnc`.`observacion`
                        , `tb_terceros`.`nom_tercero`
                        , `tb_terceros`.`nit_tercero`
                    FROM
                        `ctt_novedad_cesion` `cnc`
                        INNER JOIN `tb_terceros`
                            ON (`cnc`.`id_tercero` = `tb_terceros`.`id_tercero_api`)
                    WHERE (`id_cesion` = ?)";
            $rs = $cmd->prepare($sql);
            $rs->bindParam(1, $id_novedad, PDO::PARAM_INT);
            $rs->execute();
            $detalles_novedad = $rs->fetch();
        } catch (PDOException $e) {
            echo $e->getCode() == 2002 ? 'Sin Conexión a Mysql (Error: 2002)' : 'Error: ' . $e->getMessage();
        }
        $cmd = null;
    ?>
        <div class="px-0">
            <div class="shadow">
                <div class="card-header text-center py-2" style="background-color: #16a085 !important;">
                    <h5 style="color: white;">ACTUALIZAR O MODIFICAR CESIÓN DE CONTRATO</h5>
                </div>
                <form id="formUpNovContrato">
                    <input type="hidden" name="id_novendad" value="<?php echo $id_novedad ?>">
                    <input type="hidden" name="id_contrato" value="<?php echo $detalles_novedad['id_adq'] ?>">
                    <input type="hidden" name="slcTipoNovedad" id="slcTipoNovedad" value="4">
                    <div class="row px-4 pt-2">
                        <div class="col-md-4 mb-3">
                            <label for="datFecCesion" class="small">FECHA CESIÓN</label>
                            <input type="date" name="datFecCesion" id="datFecCesion" class="form-control form-control-sm bg-input" value="<?php echo $detalles_novedad['fec_cesion'] ?>">
                        </div>
                        <div class="col-md-8 mb-3">
                            <label for="buscaTercero" class="small">TERCERO CESIONARIO</label> <br>
                            <input type="text" id="buscaTercero" name="buscaTercero" class="form-control form-control-sm bg-input awesomplete" value="<?php echo $detalles_novedad['nom_tercero'] . ' -> ' . $detalles_novedad['nit_tercero'] ?>">
                            <input type="hidden" id="id_tercero" name="id_tercero" value="<?php echo $detalles_novedad['id_tercero'] ?>">
                        </div>
                    </div>
                    <div class="row px-4">
                        <div class="col-md-12 mb-3">
                            <label for="txtAObservaNov" class="small">OBSERVACIONES</label>
                            <textarea class="form-control bg-input" id="txtAObservaNov" name="txtAObservaNov" rows="3"><?php echo $detalles_novedad['observacion'] ?></textarea>
                        </div>
                    </div>
                    <div class="text-center pb-3">
                        <button class="btn btn-primary btn-sm" id="btnUpNovContrato">Actualizar</button>
                        <a type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal"> Cancelar</a>
                    </div>
                </form>
            </div>
        </div>
    <?php
        break;
    case 5:
        try {
            $cmd = \Config\Clases\Conexion::getConexion();
            $sql = "SELECT
                        `id_suspension`, `id_adq`, `id_tipo_nov`, `fec_inicia`, `fec_fin`, `observacion`
                    FROM
                        `ctt_novedad_suspension`
                    WHERE (`id_suspension` = ?)";
            $rs = $cmd->prepare($sql);
            $rs->bindParam(1, $id_novedad, PDO::PARAM_INT);
            $rs->execute();
            $detalles_novedad = $rs->fetch();
            $cmd = null;
        } catch (PDOException $e) {
            echo $e->getCode() == 2002 ? 'Sin Conexión a Mysql (Error: 2002)' : 'Error: ' . $e->getMessage();
        }
    ?>
        <div class="px-0">
            <div class="shadow">
                <div class="card-header text-center py-2" style="background-color: #16a085 !important;">
                    <h5 style="color: white;">ACTUALIZAR O MODIFICAR SUSPENCIÓN DE CONTRATO</h5>
                </div>
                <form id="formUpNovContrato">
                    <input type="hidden" name="id_novendad" value="<?php echo $id_novedad ?>">
                    <input type="hidden" name="id_contrato" value="<?php echo $detalles_novedad['id_adq'] ?>">
                    <input type="hidden" name="slcTipoNovedad" id="slcTipoNovedad" value="5">
                    <div class="row px-4 pt-2">
                        <div class="col-md-6 mb-3">
                            <label for="datFecIniSuspencion" class="small">FECHA INICIAL</label>
                            <input type="date" name="datFecIniSuspencion" id="datFecIniSuspencion" class="form-control form-control-sm bg-input" value="<?php echo $detalles_novedad['fec_inicia'] ?>">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="datFecFinSuspencion" class="small">FECHA FINAL</label>
                            <input type="date" name="datFecFinSuspencion" id="datFecFinSuspencion" class="form-control form-control-sm bg-input" value="<?php echo $detalles_novedad['fec_fin'] ?>">
                        </div>
                    </div>
                    <div class="row px-4">
                        <div class="col-md-12 mb-3">
                            <label for="txtAObservaNov" class="small">OBSERVACIONES</label>
                            <textarea class="form-control bg-input" id="txtAObservaNov" name="txtAObservaNov" rows="3"><?php echo $detalles_novedad['observacion'] ?></textarea>
                        </div>
                    </div>
                    <div class="text-center pb-3">
                        <button class="btn btn-primary btn-sm" id="btnUpNovContrato">Actualizar</button>
                        <a type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal"> Cancelar</a>
                    </div>
                </form>
            </div>
        </div>
    <?php
        break;
    case 6:
        try {
            $cmd = \Config\Clases\Conexion::getConexion();
            $sql = "SELECT
                        `ctt_novedad_reinicio`.`id_reinicio`
                        , `ctt_novedad_reinicio`.`id_tipo_nov`
                        , `ctt_novedad_reinicio`.`id_suspension`
                        , `ctt_novedad_reinicio`.`fec_reinicia`
                        , `ctt_novedad_reinicio`.`observacion`
                        , `ctt_novedad_suspension`.`id_adq`
                    FROM
                        `ctt_novedad_reinicio`
                        INNER JOIN `ctt_novedad_suspension` 
                            ON (`ctt_novedad_reinicio`.`id_suspension` = `ctt_novedad_suspension`.`id_suspension`)
                    WHERE (`ctt_novedad_reinicio`.`id_reinicio` = ?)";
            $rs = $cmd->prepare($sql);
            $rs->bindParam(1, $id_novedad, PDO::PARAM_INT);
            $rs->execute();
            $detalles_novedad = $rs->fetch();
            $cmd = null;
        } catch (PDOException $e) {
            echo $e->getCode() == 2002 ? 'Sin Conexión a Mysql (Error: 2002)' : 'Error: ' . $e->getMessage();
        }
        $id_contrato = $detalles_novedad['id_adq'];
        try {
            $cmd = \Config\Clases\Conexion::getConexion();
            $sql = "SELECT 
                        `id_suspension`,`id_adq`,`id_tipo_nov`,`fec_inicia`,`fec_fin`,`observacion` 
                    FROM
                        `ctt_novedad_suspension`
                    WHERE `id_suspension` IN (SELECT MAX(`id_suspension`) FROM `ctt_novedad_suspension` WHERE (`id_adq` = ?))";
            $rs = $cmd->prepare($sql);
            $rs->bindParam(1, $id_contrato, PDO::PARAM_INT);
            $rs->execute();
            $suspensiones = $rs->fetch();
            $cmd = null;
        } catch (PDOException $e) {
            echo $e->getCode() == 2002 ? 'Sin Conexión a Mysql (Error: 2002)' : 'Error: ' . $e->getMessage();
        }
    ?>
        <div class="px-0">
            <div class="shadow">
                <div class="card-header text-center py-2" style="background-color: #16a085 !important;">
                    <h5 style="color: white;">ACTUALIZAR O MODIFICAR REINICIO DE CONTRATO</h5>
                </div>
                <br>
                <div class="px-4">
                    <?php
                    if ($suspensiones['id_suspension'] == '') {
                    ?>
                        <div class="alert alert-danger" role="alert">
                            PRIMERO DEBE REGISTAR UNA SUSPENCIÓN DE CONTRATO!
                        </div>
                        <div class="text-center pb-3">
                            <a type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal"> Cancelar</a>
                        </div>
                    <?php
                    } else {
                    ?>
                        <table class="table table-striped table-bordered table-sm nowrap table-hover shadow" style="width:100%">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Fecha Inicial</th>
                                    <th>Fecha Final</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td><?php echo $suspensiones['id_suspension'] ?></td>
                                    <td><?php echo $suspensiones['fec_inicia'] ?></td>
                                    <td><?php echo $suspensiones['fec_fin'] ?></td>
                                </tr>
                            </tbody>
                        </table>
                </div>
                <form id="formUpNovContrato">
                    <input type="hidden" name="id_novendad" value="<?php echo $id_novedad ?>">
                    <input type="hidden" id="fecIniSus" value="<?php echo $suspensiones['fec_inicia'] ?>">
                    <input type="hidden" id="fecFinSus" value="<?php echo $suspensiones['fec_fin'] ?>">
                    <input type="hidden" id="id_suspension" name="id_suspension" value="<?php echo $suspensiones['id_suspension'] ?>">
                    <input type="hidden" name="slcTipoNovedad" id="slcTipoNovedad" value="6">
                    <div class="row px-4 pt-2">
                        <div class="col-md-12 mb-3">
                            <label for="datFecReinicio" class="small">FECHA APROBADA REINICIO</label>
                            <input type="date" name="datFecReinicio" id="datFecReinicio" class="form-control form-control-sm bg-input" value="<?php echo $detalles_novedad['fec_reinicia'] ?>">
                        </div>
                    </div>
                    <div class="row px-4">
                        <div class="col-md-12 mb-3">
                            <label for="txtAObservaNov" class="small">OBSERVACIONES</label>
                            <textarea class="form-control bg-input" id="txtAObservaNov" name="txtAObservaNov" rows="3"><?php echo $detalles_novedad['observacion'] ?></textarea>
                        </div>
                    </div>
                    <div class="text-center pb-3">
                        <button class="btn btn-primary btn-sm" id="btnUpNovContrato">Actualizar</button>
                        <a type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal"> Cancelar</a>
                    </div>
                </form>
            <?php } ?>
            </div>
        </div>
    <?php
        break;
    case 7:
        try {
            $cmd = \Config\Clases\Conexion::getConexion();
            $sql = "SELECT `id_tipo_term`, `descripcion` FROM `ctt_tipo_terminacion` ORDER BY `descripcion` ASC";
            $rs = $cmd->query($sql);
            $tip_terminacion = $rs->fetchAll(PDO::FETCH_ASSOC);
            $sql = "SELECT
                        `id_terminacion`, `id_tipo_nov`, `id_t_terminacion`, `id_adq`, `observacion`
                    FROM
                        `ctt_novedad_terminacion`
                    WHERE (`id_terminacion` = ?)";
            $rs = $cmd->prepare($sql);
            $rs->bindParam(1, $id_novedad, PDO::PARAM_INT);
            $rs->execute();
            $detalles_novedad = $rs->fetch();
            $cmd = null;
        } catch (PDOException $e) {
            echo $e->getCode() == 2002 ? 'Sin Conexión a Mysql (Error: 2002)' : 'Error: ' . $e->getMessage();
        }
    ?>
        <div class="px-0">
            <div class="shadow">
                <div class="card-header text-center py-2" style="background-color: #16a085 !important;">
                    <h5 style="color: white;">ACTUALIZAR O MODIFICAR TERMINACIÓN DE CONTRATO</h5>
                </div>
                <form id="formUpNovContrato">
                    <input type="hidden" name="id_novendad" value="<?php echo $id_novedad ?>">
                    <input type="hidden" name="slcTipoNovedad" id="slcTipoNovedad" value="7">
                    <div class="row px-4 pt-2">
                        <div class="col-md-12 mb-3">
                            <label for="slcTipTerminacion" class="small">TIPO DE TERMINACIÓN DE CONTRATO</label>
                            <select id="slcTipTerminacion" name="slcTipTerminacion" class="form-select form-select-sm bg-input" aria-label="Default select example">
                                <?php
                                foreach ($tip_terminacion as $tt) {
                                    $slc = $tt['id_tipo_term'] == $detalles_novedad['id_t_terminacion'] ? 'selected' : '';
                                    echo '<option ' . $slc . ' value="' . $tt['id_tipo_term'] . '">' . $tt['descripcion'] . '</option>';
                                }
                                ?>
                            </select>
                        </div>
                    </div>
                    <div class="row px-4">
                        <div class="col-md-12 mb-3">
                            <label for="txtAObservaNov" class="small">OBSERVACIONES</label>
                            <textarea class="form-control bg-input" id="txtAObservaNov" name="txtAObservaNov" rows="3"><?php echo $detalles_novedad['observacion'] ?></textarea>
                        </div>
                    </div>
                    <div class="text-center pb-3">
                        <button class="btn btn-primary btn-sm" id="btnUpNovContrato">Actualizar</button>
                        <a type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal"> Cancelar</a>
                    </div>
                </form>
            </div>
        </div>
    <?php
        break;
    case 8:
        try {
            $cmd = \Config\Clases\Conexion::getConexion();
            $sql = "SELECT
                        `id_liquidacion`, `id_tipo_nov`, `id_t_liq`, `id_adq`, `fec_liq`, `val_cte`, `val_cta`, `observacion`
                    FROM
                        `ctt_novedad_liquidacion`
                    WHERE (`id_liquidacion` = ?)";
            $rs = $cmd->prepare($sql);
            $rs->bindParam(1, $id_novedad, PDO::PARAM_INT);
            $rs->execute();
            $detalles_novedad = $rs->fetch();
            $cmd = null;
        } catch (PDOException $e) {
            echo $e->getCode() == 2002 ? 'Sin Conexión a Mysql (Error: 2002)' : 'Error: ' . $e->getMessage();
        }
    ?>
        <div class="px-0">
            <div class="shadow">
                <div class="card-header text-center py-2" style="background-color: #16a085 !important;">
                    <h5 style="color: white;">ACTUALIZAR O MODIFICAR LIQUIDACIÓN DE CONTRATO</h5>
                </div>
                <form id="formUpNovContrato">
                    <input type="hidden" name="id_novendad" value="<?php echo $id_novedad ?>">
                    <input type="hidden" name="slcTipoNovedad" id="slcTipoNovedad" value="8">
                    <div class="row px-4 pt-2">
                        <div class="col-md-4 mb-3">
                            <label for="datFecLiq" class="small">FECHA LIQUIDACIÓN</label>
                            <input type="date" name="datFecLiq" id="datFecLiq" class="form-control form-control-sm bg-input" value="<?php echo $detalles_novedad['fec_liq'] ?>">
                        </div>
                        <div class="col-md-8 mb-3">
                            <label for="slcTipLiquidacion" class="small">TIPO DE LIQUIDACIÓN DE CONTRATO</label>
                            <select id="slcTipLiquidacion" name="slcTipLiquidacion" class="form-select form-select-sm bg-input" aria-label="Default select example">
                                <?php
                                $slc1 = $detalles_novedad['id_t_liq'] == 1 ? 'selected' : '';
                                $slc2 = $detalles_novedad['id_t_liq'] == 2 ? 'selected' : '';
                                ?>
                                <option <?php echo $slc1 ?> value="1">UNILATERAL</option>
                                <option <?php echo $slc2 ?> value="2">MUTUO ACUERDO</option>
                            </select>
                        </div>
                    </div>
                    <div class="row px-4">
                        <div class="col-md-6 mb-3">
                            <label for="numValFavorCtrate" class="small">VALOR A FAVOR CONTRATANTE</label>
                            <input type="number" name="numValFavorCtrate" id="numValFavorCtrate" class="form-control form-control-sm bg-input" value="<?php echo $detalles_novedad['val_cte'] ?>">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="numValFavorCtrista" class="small">VALOR A FAVOR CONTRATISTA</label>
                            <input type="number" name="numValFavorCtrista" id="numValFavorCtrista" class="form-control form-control-sm bg-input" value="<?php echo $detalles_novedad['val_cta'] ?>">
                        </div>
                    </div>
                    <div class="row px-4">
                        <div class="col-md-12 mb-3">
                            <label for="txtAObservaNov" class="small">OBSERVACIONES</label>
                            <textarea class="form-control bg-input" id="txtAObservaNov" name="txtAObservaNov" rows="3"><?php echo $detalles_novedad['observacion'] ?></textarea>
                        </div>
                    </div>
                    <div class="text-center pb-3">
                        <button class="btn btn-primary btn-sm" id="btnUpNovContrato">Actualizar</button>
                        <a type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal"> Cancelar</a>
                    </div>
                </form>
            </div>
        </div>
<?php
        break;
}
?>