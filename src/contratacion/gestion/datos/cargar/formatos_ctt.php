<?php
session_start();
if (!isset($_SESSION['user'])) {
    header('Location: ../../../../index.php');
    exit();
}

include_once '../../../../../config/autoloader.php';
$cmd = \Config\Clases\Conexion::getConexion();
try {

    $sql = "SELECT
                `id_fdoc`,`descripcion`
            FROM `ctt_formatos_doc`";
    $rs = $cmd->query($sql);
    $formatos = $rs->fetchAll(PDO::FETCH_ASSOC);
    $rs->closeCursor();
    unset($rs);
} catch (PDOException $e) {
    echo $e->getCode() == 2002 ? 'Sin Conexión a Mysql (Error: 2002)' : 'Error: ' . $e->getMessage();
}
try {

    $sql = "SELECT
                `tb_tipo_bien_servicio`.`id_tipo_b_s`
                , `tb_tipo_bien_servicio`.`tipo_bn_sv`
                , `tb_tipo_compra`.`tipo_compra`
            FROM `tb_tipo_bien_servicio`
                INNER JOIN `tb_tipo_compra` 
                    ON (`tb_tipo_bien_servicio`.`id_tipo` = `tb_tipo_compra`.`id_tipo`)
            ORDER BY `tb_tipo_bien_servicio`.`tipo_bn_sv` ASC";
    $rs = $cmd->query($sql);
    $tipos = $rs->fetchAll(PDO::FETCH_ASSOC);
    $rs->closeCursor();
    unset($rs);
} catch (PDOException $e) {
    echo $e->getCode() == 2002 ? 'Sin Conexión a Mysql (Error: 2002)' : 'Error: ' . $e->getMessage();
}
?>
<div class="px-0">
    <div class="shadow">
        <div class="card-header text-center py-2" style="background-color: #16a085 !important;">
            <h5 style="color: white;">CARGAR FORMATOS DE CONTRATACIÓN</h5>
        </div>
        <form id="formContratacion" enctype="multipart/form-data">
            <div class="row px-4 pt-2">
                <div class="col-md-12 mb-3">
                    <label for="slcTipoFormato" class="small">Tipo de formato</label>
                    <select class="form-control form-control-sm bg-input" name="slcTipoFormato" id="slcTipoFormato">
                        <option value="0">--Seleccione--</option>
                        <?php
                        foreach ($formatos as $formato) {
                            echo "<option value='$formato[id_fdoc]'>$formato[descripcion]</option>";
                        }
                        ?>
                    </select>
                </div>
            </div>
            <div class="row px-4 pt-2">
                <div class="col-md-12 mb-3">
                    <label for="slcTipoBnSv" class="small">Tipo de bien o servicio</label>
                    <select class="form-control form-control-sm bg-input" name="slcTipoBnSv" id="slcTipoBnSv">
                        <option value="0">--Seleccione--</option>
                        <?php
                        foreach ($tipos as $tipo) {
                            echo "<option value='$tipo[id_tipo_b_s]'>$tipo[tipo_compra] -> $tipo[tipo_bn_sv]</option>";
                        }
                        ?>
                    </select>
                </div>
            </div>
            <div class="row px-4">
                <div class="col-md-12 mb-3">
                    <label for="fileContratacion" class="small">Formato</label>
                    <input type="hidden" name="MAX_FILE_SIZE" value="30000" />
                    <input type="file" class="form-control-file border bg-input" name="fileContratacion" id="fileContratacion">
                </div>
            </div>
            <div class="text-center">
                <button class="btn btn-primary btn-sm" id="btnGuardaFormatoCtt">Guardar</button>
                <a type="button" class="btn btn-secondary  btn-sm" data-bs-dismiss="modal"> Cancelar</a>
            </div>
            <br>
        </form>
    </div>
</div>