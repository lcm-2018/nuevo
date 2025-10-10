<?php
session_start();
if (!isset($_SESSION['user'])) {
    header('Location: ../../../../index.php');
    exit();
}
include_once '../../../../../config/autoloader.php';
$id_rol = isset($_SESSION['rol']) ? $_SESSION['rol'] : null;
$id_user = isset($_SESSION['id_user']) ? $_SESSION['id_user'] : null;
$permisos = new \Src\Common\Php\Clases\Permisos();
$opciones = $permisos->PermisoOpciones($id_user);
$cmd = \Config\Clases\Conexion::getConexion();
$id_cc = isset($_POST['id']) ? $_POST['id'] : exit('Acción no permitida ');
try {
    
    $sql = "SELECT
                `id_est_prev`
                , `id_compra`
                , `fec_fin_ejec`
                , `fec_ini_ejec`
                , `val_contrata`
                , `id_forma_pago`
                , `id_supervisor`
            FROM
                `ctt_estudios_previos`
            WHERE id_compra = ?";
    $stmt = $cmd->prepare($sql);
    $stmt->bindParam(1, $id_cc, PDO::PARAM_INT);
    $stmt->execute();
    $estudio_prev = $stmt->fetch();
} catch (PDOException $e) {
    echo $e->getCode() == 2002 ? 'Sin Conexión a Mysql (Error: 2002)' : 'Error: ' . $e->getMessage();
}
$est_prev = isset($estudio_prev) ? $estudio_prev['id_est_prev'] : 0;
try {
    
    $sql = "SELECT
                `id_garantia`
                , `id_est_prev`
                , `id_poliza`
            FROM
                `seg_garantias_compra`
            WHERE `id_est_prev`  = ?";
    $stmt2 = $cmd->prepare($sql);
    $stmt2->bindParam(1, $est_prev, PDO::PARAM_INT);
    $stmt2->execute();
    $garantias = $stmt2->fetchAll();
} catch (PDOException $e) {
    echo $e->getCode() == 2002 ? 'Sin Conexión a Mysql (Error: 2002)' : 'Error: ' . $e->getMessage();
}
try {
    
    $sql = "SELECT
                `id_form_pago`
                , `descripcion`
            FROM
                `tb_forma_pago_compras` ORDER BY `descripcion` ASC ";
    $rs = $cmd->query($sql);
    $forma_pago = $rs->fetchAll();
} catch (PDOException $e) {
    echo $e->getCode() == 2002 ? 'Sin Conexión a Mysql (Error: 2002)' : 'Error: ' . $e->getMessage();
}
try {
    
    $sql = "SELECT
                `tb_terceros`.`id_tercero_api`
                , `tb_terceros`.`nom_tercero`
            FROM
                `tb_rel_tercero`
                INNER JOIN `tb_terceros` 
                    ON (`tb_rel_tercero`.`id_tercero_api` = `tb_terceros`.`id_tercero_api`)
            WHERE `tb_terceros`.`estado` = 1 AND `tb_rel_tercero`.`id_tipo_tercero` = 3";
    $rs = $cmd->query($sql);
    $supervisor = $rs->fetchAll();
} catch (PDOException $e) {
    echo $e->getCode() == 2002 ? 'Sin Conexión a Mysql (Error: 2002)' : 'Error: ' . $e->getMessage();
}
try {
    
    $sql = "SELECT
            `id_poliza`
            , `descripcion`
            , `porcentaje`
        FROM
            `tb_polizas` ORDER BY `descripcion` ASC";
    $rs = $cmd->query($sql);
    $polizas = $rs->fetchAll();
} catch (PDOException $e) {
    echo $e->getCode() == 2002 ? 'Sin Conexión a Mysql (Error: 2002)' : 'Error: ' . $e->getMessage();
}
?>
<div class="px-0">
    <div class="shadow">
         <div class="card-header text-center py-2" style="background-color: #16a085 !important;">
            <h5 style="color: white;">REGISTRAR CONTRATO</h5>
        </div>
        <form id="formAddcontratoCompra">
            <input type="hidden" name="id_cc" value="<?php echo $id_cc ?>">
            <div class="row px-4 pt-2">
                <div class="col-md-4 mb-3">
                    <label for="datFecIniEjec" class="small">FECHA INICIAL CONTRATO</label>
                    <input type="date" name="datFecIniEjec" id="datFecIniEjec" class="form-control form-control-sm bg-input" value="<?php echo $estudio_prev['fec_ini_ejec'] ?>">
                </div>
                <div class="col-md-4 mb-3">
                    <label for="datFecFinEjec" class="small">FECHA FINAL CONTRATO</label>
                    <input type="date" name="datFecFinEjec" id="datFecFinEjec" class="form-control form-control-sm bg-input" value="<?php echo $estudio_prev['fec_fin_ejec'] ?>">
                </div>
                <?php
                $fini = new DateTime($estudio_prev['fec_ini_ejec'] ?? date('Y-m-d'));
                $ffin = new DateTime($estudio_prev['fec_fin_ejec'] ?? date('Y-m-d'));
                $diferencia = $fini->diff($ffin);
                $dias = $diferencia->days + 1;
                $meses = floor($dias / 30);
                $dias_res = $dias % 30;
                ?>
                <div class="col-md-4 mb-3">
                    <label for="divDuraContrato" class="small">DURACIÓN DEL CONTRATO</label>
                    <div id="divDuraContrato" class="form-control form-control-sm">
                        <?php echo $meses . $dias . ' día(s)' ?>
                    </div>
                </div>
            </div>
            <div class="row px-4">
                <div class="col-md-12 mb-3">
                    <label for="SeaTercer" class="small">TERCERO</label>
                    <input type="text" id="SeaTercer" class="form-control form-control-sm py-0 sm bg-input" placeholder="Buscar tercero" value="<?php echo '' ?>">
                    <input type="hidden" name="id_tercero" id="id_tercero" value="<?php echo 0 ?>">
                </div>
            </div>
            <div class="row px-4">
                <div class="col-md-6 mb-3">
                    <label for="txtCodIntern" class="small">número de contrato</label>
                    <input type="text" name="txtCodIntern" id="txtCodIntern" class="form-control form-control-sm bg-input">
                </div>
                <div class="col-md-6 mb-3">
                    <label for="txtCodSecop" class="small">código Secop II</label>
                    <input type="text" name="txtCodSecop" id="txtCodSecop" class="form-control form-control-sm bg-input" placeholder="CO1.PCCNTR.0000000">
                </div>
            </div>
            <div class="row px-4">
                <div class="col-md-4 mb-3">
                    <label for="numValContrata" class="small">Valor Contrato</label>
                    <input type="number" name="numValContrata" id="numValContrata" class="form-control form-control-sm bg-input" value="<?php echo $estudio_prev['val_contrata'] ?>">
                </div>
                <div class="col-md-4 mb-3">
                    <label for="slcFormPago" class="small">FORMA DE PAGO</label>
                    <select id="slcFormPago" name="slcFormPago" class="form-control form-control-sm py-0 sm bg-input" aria-label="Default select example">
                        <?php
                        foreach ($forma_pago as $fp) {
                            $selecionada = '';
                            if ($fp['id_form_pago'] == $estudio_prev['id_forma_pago']) {
                                $selecionada = 'selected';
                            }
                            echo '<option ' . $selecionada . ' value="' . $fp['id_form_pago'] . '">' . $fp['descripcion'] . '</option>';
                        }
                        ?>
                    </select>
                </div>
                <div class="col-md-4 mb-3">
                    <label for="slcSupervisor" class="small">SUPERVISOR</label>
                    <select id="slcSupervisor" name="slcSupervisor" class="form-control form-control-sm py-0 sm bg-input" aria-label="Default select example">
                        <option value="0">--Selecionar--</option>
                        <?php
                        foreach ($supervisor as $s) {
                            $selecionada = '';
                            if ($s['id_tercero_api'] == $estudio_prev['id_supervisor']) {
                                $selecionada = 'selected';
                            }
                            echo '<option ' . $selecionada . ' value="' . $s['id_tercero_api'] . '">' . $s['nom_tercero'] . '</option>';
                        }
                        ?>
                    </select>
                </div>
            </div>
            <label for="slcSupervisor" class="small">PÓLIZAS</label>
            <div class="row px-4" id="slcSupervisor">

                <?php
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
                            <div class="form-control form-control-sm text-left" aria-label="Text input with checkbox" style="font-size: 55%;"><?php echo $pz['descripcion'] . ' ' . $pz['porcentaje'] ?> </div>
                        </div>
                    </div>
                <?php
                }
                ?>
            </div>
            <div class="text-center pb-3">
                <button class="btn btn-primary btn-sm" id="btnAddContratoCompra">Registrar</button>
                <a type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancelar</a>
            </div>
        </form>
    </div>
</div>