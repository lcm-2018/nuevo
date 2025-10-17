<?php
session_start();
if (!isset($_SESSION['user'])) {
    header('Location: ../../../../../index.php');
    exit();
}
require_once '../../../../../config/autoloader.php';

$opcion = isset($_POST['opcion']) ? $_POST['opcion'] : exit('Acción no permitida ');
$id_contrato = $_POST['id'];
//API URL
$cmd = \Config\Clases\Conexion::getConexion();
try {
    $sql = "SELECT
                `id_novedad`,`descripcion`
            FROM `ctt_tipo_novedad`
            ORDER BY `descripcion` ASC";
    $rs = $cmd->query($sql);
    $tipo = $rs->fetchAll(PDO::FETCH_ASSOC);
$rs->closeCursor();
unset($rs);
    $cmd = null;
} catch (PDOException $e) {
    echo $e->getCode() == 2002 ? 'Sin Conexión a Mysql (Error: 2002)' : 'Error: ' . $e->getMessage();
}
switch ($opcion) {
    case 1:
?>
        <div class="px-0">
            <div class="shadow">
                <div class="card-header text-center py-2" style="background-color: #16a085 !important;">
                    <h5 style="color: white;">REGISTRAR ADICIÓN O PRORROGA DE CONTRATO</h5>
                </div>
                <form id="formAddNovContrato">
                    <input type="hidden" name="id_compra" value="<?php echo $id_contrato ?>">
                    <div class="row px-4 pt-2">
                        <div class="col-md-12 mb-3">
                            <label for="slcTipoNovedad" class="small">TIPO DE NOVEDAD</label>
                            <select id="slcTipoNovedad" name="slcTipoNovedad" class="form-select form-select-sm bg-input" aria-label="Default select example">
                                <option value="0">--Seleccionar--</option>
                                <?php
                                foreach ($tipo as $tn) {
                                    if (in_array($tn['id_novedad'], [1, 2, 3])) {
                                        echo '<option value="' . $tn['id_novedad'] . '">' . $tn['descripcion'] . '</option>';
                                    }
                                }
                                ?>
                            </select>
                        </div>
                    </div>
                    <div class="row px-4" id="divAdicion" style="display: none;">
                        <div class="col-md-6 mb-3">
                            <label for="numValAdicion" class="small">VALOR</label>
                            <input type="number" name="numValAdicion" id="numValAdicion" class="form-control form-control-sm bg-input">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="datFecAdicion" class="small">FECHA DE ADICIÓN</label>
                            <input type="date" name="datFecAdicion" id="datFecAdicion" class="form-control form-control-sm bg-input">
                        </div>
                    </div>
                    <div class="row px-4" id="divProrroga" style="display: none;">
                        <div class="col-md-6 mb-3">
                            <label for="datFecIniProrroga" class="small">FECHA INICIAL</label>
                            <input type="date" name="datFecIniProrroga" id="datFecIniProrroga" class="form-control form-control-sm bg-input">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="datFecFinProrroga" class="small">FECHA FINAL</label>
                            <input type="date" name="datFecFinProrroga" id="datFecFinProrroga" class="form-control form-control-sm bg-input">
                        </div>
                    </div>
                    <div class="row px-4" id="divObservaNov" style="display: none;">
                        <div class="col-md-12 mb-3">
                            <label for="txtAObservaNov" class="small">OBSERVACIONES</label>
                            <textarea class="form-control bg-input" id="txtAObservaNov" name="txtAObservaNov" rows="3"></textarea>
                        </div>
                    </div>
                    <div class="text-center pb-3">
                        <button class="btn btn-primary btn-sm" id="btnNovContrato" value="<?php echo $opcion ?>">Registrar</button>
                        <a type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal"> Cancelar</a>
                    </div>
                </form>
            </div>
        </div>
    <?php
        break;
    case 2:
    ?>
        <div class="px-0">
            <div class="shadow">
                <div class="card-header text-center py-2" style="background-color: #16a085 !important;">
                    <h5 style="color: white;">REGISTRAR CESIÓN DE CONTRATO</h5>
                </div>
                <form id="formAddNovContrato">
                    <input type="hidden" name="id_compra" value="<?php echo $id_contrato ?>">
                    <input type="hidden" name="slcTipoNovedad" value="4">
                    <div class="row px-4 pt-2">
                        <div class="col-md-4 mb-3">
                            <label for="datFecCesion" class="small">FECHA CESIÓN</label>
                            <input type="date" name="datFecCesion" id="datFecCesion" class="form-control form-control-sm bg-input">
                        </div>
                        <div class="col-md-8 mb-3">
                            <label for="buscaTercero" class="small">TERCERO CESIONARIO</label> <br>
                            <input type="text" id="buscaTercero" name="buscaTercero" class="form-control form-control-sm bg-input awesomplete">
                            <input type="hidden" id="id_tercero" name="id_tercero" value="0">
                        </div>
                    </div>
                    <div class="row px-4">
                        <div class="col-md-12 mb-3">
                            <label for="txtAObservaNov" class="small">OBSERVACIONES</label>
                            <textarea class="form-control bg-input" id="txtAObservaNov" name="txtAObservaNov" rows="3"></textarea>
                        </div>
                    </div>
                    <div class="text-center pb-3">
                        <button class="btn btn-primary btn-sm" id="btnNovContrato" value="<?php echo $opcion ?>">Registrar</button>
                        <a type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal"> Cancelar</a>
                    </div>
                </form>
            </div>
        </div>
    <?php
        break;
    case 3:
    ?>
        <div class="px-0">
            <div class="shadow">
                <div class="card-header text-center py-2" style="background-color: #16a085 !important;">
                    <h5 style="color: white;">REGISTRAR SUSPENCIÓN DE CONTRATO</h5>
                </div>
                <form id="formAddNovContrato">
                    <input type="hidden" name="id_compra" value="<?php echo $id_contrato ?>">
                    <input type="hidden" name="slcTipoNovedad" value="5">
                    <div class="row px-4 pt-2">
                        <div class="col-md-6 mb-3">
                            <label for="datFecIniSuspencion" class="small">FECHA INICIAL</label>
                            <input type="date" name="datFecIniSuspencion" id="datFecIniSuspencion" class="form-control form-control-sm bg-input">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="datFecFinSuspencion" class="small">FECHA FINAL</label>
                            <input type="date" name="datFecFinSuspencion" id="datFecFinSuspencion" class="form-control form-control-sm bg-input">
                        </div>
                    </div>
                    <div class="row px-4">
                        <div class="col-md-12 mb-3">
                            <label for="txtAObservaNov" class="small">OBSERVACIONES</label>
                            <textarea class="form-control bg-input" id="txtAObservaNov" name="txtAObservaNov" rows="3"></textarea>
                        </div>
                    </div>
                    <div class="text-center pb-3">
                        <button class="btn btn-primary btn-sm" id="btnNovContrato" value="<?php echo $opcion ?>">Registrar</button>
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
                        `id_suspension`,`id_adq`,`id_tipo_nov`,`fec_inicia`,`fec_fin`,`observacion` 
                    FROM
                        `ctt_novedad_suspension`
                    WHERE `id_suspension` IN (SELECT MAX(`id_suspension`) FROM `ctt_novedad_suspension` WHERE (`id_adq` = $id_contrato))";
            //echo $sql;
            $rs = $cmd->query($sql);
            $suspensiones = $rs->fetch();
            $cmd = null;
        } catch (PDOException $e) {
            echo $e->getCode() == 2002 ? 'Sin Conexión a Mysql (Error: 2002)' : 'Error: ' . $e->getMessage();
        }
    ?>
        <div class="px-0">
            <div class="shadow">
                <div class="card-header text-center py-2" style="background-color: #16a085 !important;">
                    <h5 style="color: white;">REGISTRAR REINICIO DE CONTRATO</h5>
                </div>
                <br>
                <div class="px-4">
                    <?php
                    if (empty($suspensiones)) {
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
                                    <th colspan="3">ÚLTIMA SUSPENSIÓN</th>
                                </tr>
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
                <form id="formAddNovContrato">
                    <input type="hidden" name="id_compra" value="<?php echo $id_contrato ?>">
                    <input type="hidden" id="fecIniSus" value="<?php echo $suspensiones['fec_inicia'] ?>">
                    <input type="hidden" id="fecFinSus" value="<?php echo $suspensiones['fec_fin'] ?>">
                    <input type="hidden" id="id_suspension" name="id_suspension" value="<?php echo $suspensiones['id_suspension'] ?>">
                    <input type="hidden" name="slcTipoNovedad" value="6">
                    <div class="row px-4 pt-2">
                        <div class="col-md-12 mb-3">
                            <label for="datFecReinicio" class="small">FECHA APROBADA REINICIO</label>
                            <input type="date" name="datFecReinicio" id="datFecReinicio" class="form-control form-control-sm bg-input">
                        </div>
                    </div>
                    <div class="row px-4">
                        <div class="col-md-12 mb-3">
                            <label for="txtAObservaNov" class="small">OBSERVACIONES</label>
                            <textarea class="form-control bg-input" id="txtAObservaNov" name="txtAObservaNov" rows="3"></textarea>
                        </div>
                    </div>
                    <div class="text-center pb-3">
                        <button class="btn btn-primary btn-sm" id="btnNovContrato" value="<?php echo $opcion ?>">Registrar</button>
                        <a type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal"> Cancelar</a>
                    </div>
                </form>
            <?php } ?>
            </div>
        </div>
    <?php
        break;
    case 5:
        try {
            $cmd = \Config\Clases\Conexion::getConexion();
            $sql = "SELECT `id_tipo_term`, `descripcion` FROM `ctt_tipo_terminacion` ORDER BY `descripcion` ASC";
            $rs = $cmd->query($sql);
            $tip_terminacion = $rs->fetchAll(PDO::FETCH_ASSOC);
            $cmd = null;
        } catch (PDOException $e) {
            echo $e->getCode() == 2002 ? 'Sin Conexión a Mysql (Error: 2002)' : 'Error: ' . $e->getMessage();
        }
    ?>
        <div class="px-0">
            <div class="shadow">
                <div class="card-header text-center py-2" style="background-color: #16a085 !important;">
                    <h5 style="color: white;">REGISTRAR TERMINACIÓN DE CONTRATO</h5>
                </div>
                <form id="formAddNovContrato">
                    <input type="hidden" name="id_compra" value="<?php echo $id_contrato ?>">
                    <input type="hidden" name="slcTipoNovedad" value="7">
                    <div class="row px-4 pt-2">
                        <div class="col-md-12 mb-3">
                            <label for="slcTipTerminacion" class="small">TIPO DE TERMINACIÓN DE CONTRATO</label>
                            <select id="slcTipTerminacion" name="slcTipTerminacion" class="form-select form-select-sm bg-input" aria-label="Default select example">
                                <option value="0">--Seleccionar--</option>
                                <?php
                                foreach ($tip_terminacion as $tt) {
                                    echo '<option value="' . $tt['id_tipo_term'] . '">' . $tt['descripcion'] . '</option>';
                                }
                                ?>
                            </select>
                        </div>
                    </div>
                    <div class="row px-4">
                        <div class="col-md-12 mb-3">
                            <label for="txtAObservaNov" class="small">OBSERVACIONES</label>
                            <textarea class="form-control bg-input" id="txtAObservaNov" name="txtAObservaNov" rows="3"></textarea>
                        </div>
                    </div>
                    <div class="text-center pb-3">
                        <button class="btn btn-primary btn-sm" id="btnNovContrato" value="<?php echo $opcion ?>">Registrar</button>
                        <a type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal"> Cancelar</a>
                    </div>
                </form>
            </div>
        </div>
    <?php
        break;
    case 6:
    ?>
        <div class="px-0">
            <div class="shadow">
                <div class="card-header text-center py-2" style="background-color: #16a085 !important;">
                    <h5 style="color: white;">REGISTRAR LIQUIDACIÓN DE CONTRATO</h5>
                </div>
                <form id="formAddNovContrato">
                    <input type="hidden" name="id_compra" value="<?php echo $id_contrato ?>">
                    <input type="hidden" name="slcTipoNovedad" value="8">
                    <div class="row px-4 pt-2">
                        <div class="col-md-4 mb-3">
                            <label for="datFecLiq" class="small">FECHA LIQUIDACIÓN</label>
                            <input type="date" name="datFecLiq" id="datFecLiq" class="form-control form-control-sm bg-input">
                        </div>
                        <div class="col-md-8 mb-3">
                            <label for="slcTipLiquidacion" class="small">TIPO DE LIQUIDACIÓN DE CONTRATO</label>
                            <select id="slcTipLiquidacion" name="slcTipLiquidacion" class="form-select form-select-sm bg-input" aria-label="Default select example">
                                <option value="0">--Seleccionar--</option>
                                <option value="1">UNILATERAL</option>
                                <option value="2">MUTUO ACUERDO</option>
                            </select>
                        </div>
                    </div>
                    <div class="row px-4">
                        <div class="col-md-6 mb-3">
                            <label for="numValFavorCtrate" class="small">VALOR A FAVOR CONTRATANTE</label>
                            <input type="number" name="numValFavorCtrate" id="numValFavorCtrate" class="form-control form-control-sm bg-input">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="numValFavorCtrista" class="small">VALOR A FAVOR CONTRATISTA</label>
                            <input type="number" name="numValFavorCtrista" id="numValFavorCtrista" class="form-control form-control-sm bg-input">
                        </div>
                    </div>
                    <div class="row px-4">
                        <div class="col-md-12 mb-3">
                            <label for="txtAObservaNov" class="small">OBSERVACIONES</label>
                            <textarea class="form-control bg-input" id="txtAObservaNov" name="txtAObservaNov" rows="3"></textarea>
                        </div>
                    </div>
                    <div class="text-center pb-3">
                        <button class="btn btn-primary btn-sm" id="btnNovContrato" value="<?php echo $opcion ?>">Registrar</button>
                        <a type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal"> Cancelar</a>
                    </div>
                </form>
            </div>
        </div>
<?php
        break;
}
?>