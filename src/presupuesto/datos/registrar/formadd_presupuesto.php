<?php
session_start();
if (!isset($_SESSION['user'])) {
    header("Location: ../../../index.php");
    exit();
}
include '../../../../config/autoloader.php';
$error = "Debe diligenciar este campo";
try {
    $cmd = \Config\Clases\Conexion::getConexion();
    $sql = "SELECT * FROM `pto_tipo` ORDER BY `nombre` ASC";
    $rs = $cmd->query($sql);
    $modalidad = $rs->fetchAll(PDO::FETCH_ASSOC);
    $rs->closeCursor();
    unset($rs);
    $cmd = null;
} catch (PDOException $e) {
    echo $e->getCode() == 2002 ? 'Sin Conexión a Mysql (Error: 2002)' : 'Error: ' . $e->getCode();
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
    echo $e->getCode() == 2002 ? 'Sin Conexión a Mysql (Error: 2002)' : 'Error: ' . $e->getCode();
}
?>
<div class="px-0">
    <div class="shadow">
        <div class="card-header py-2 text-center" style="background-color: #16a085 !important;">
            <h5 class="mb-0" style="color: white;">REGISTRAR PRESUPUESTO</h5>
        </div>
        <form id="formAddPresupuesto">
            <div class="row px-4 pt-2">
                <div class="form-group col-md-8">
                    <label for="nomPto" class="small">Nombre del presupuesto</label>
                    <input type="text" name="nomPto" id="nomPto" class="form-control form-control-sm bg-input">
                </div>
                <input type="hidden" name="datFecVigencia" value="<?php echo $_SESSION['vigencia'] ?>">
                <div class="form-group col-md-4">
                    <label for="tipoPto" class="small">Tipo de presupuesto</label>
                    <select id="tipoPto" name="tipoPto" class="form-select form-select-sm bg-input  sm" aria-label="Default select example">
                        <option value="0">-- Seleccionar --</option>
                        <?php
                        foreach ($modalidad as $mo) {
                            echo '<option value="' . $mo['id_tipo'] . '">' . $mo['nombre'] . '</option>';
                        }
                        ?>
                    </select>
                </div>
            </div>
            <div class="row px-4 pt-2">
                <div class="form-group col-md-12">
                    <label for="txtObjeto" class="small">Descripción</label>
                    <textarea id="txtObjeto" type="text" name="txtObjeto" class="form-control form-control-sm py-0 sm bg-input" aria-label="Default select example" rows="3"></textarea>
                </div>
            </div>
        </form>
        <div class="text-end py-3 px-4">
            <button class="btn btn-success btn-sm" id="btnAddPresupuesto">Registrar</button>
            <a type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal"> Cancelar</a>
        </div>
    </div>
</div>