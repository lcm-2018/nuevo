<?php
session_start();
if (!isset($_SESSION['user'])) {
    header('Location: ../../../../../index.php');
    exit();
}
include_once '../../../../../config/autoloader.php';

$id_adq = $_POST['id_adq'];
$tipo_servicio = $_POST['tipo_servicio'];
try {
    $cmd = \Config\Clases\Conexion::getConexion();
    $sql = "SELECT * 
            FROM 
                (SELECT
                    `ctt_bien_servicio`.`id_b_s`
                    , `ctt_bien_servicio`.`bien_servicio`
                    , `tb_tipo_bien_servicio`.`tipo_bn_sv`
                    , `tb_tipo_compra`.`tipo_compra`
                FROM
                    `ctt_bien_servicio`
                    INNER JOIN `tb_tipo_bien_servicio` 
                        ON (`ctt_bien_servicio`.`id_tipo_bn_sv` = `tb_tipo_bien_servicio`.`id_tipo_b_s`)
                    INNER JOIN `tb_tipo_compra` 
                        ON (`tb_tipo_bien_servicio`.`id_tipo` = `tb_tipo_compra`.`id_tipo`)
                WHERE (`ctt_bien_servicio`.`id_tipo_bn_sv` = ?)) AS `t1`
                LEFT JOIN 
                    (SELECT
                        `ctt_orden_compra`.`id_adq`
                        , `ctt_orden_compra_detalle`.`id_servicio`
                    FROM
                        `ctt_orden_compra_detalle`
                    INNER JOIN `ctt_orden_compra` 
                        ON (`ctt_orden_compra_detalle`.`id_oc` = `ctt_orden_compra`.`id_oc`)
                    WHERE (`ctt_orden_compra`.`id_adq` = ?)) AS `t2`
                    ON(`t1`.`id_b_s` = `t2`.`id_servicio`)
            WHERE `t2`.`id_adq` IS NULL";
    $stmt = $cmd->prepare($sql);
    $stmt->bindParam(1, $tipo_servicio, PDO::PARAM_INT);
    $stmt->bindParam(2, $id_adq, PDO::PARAM_INT);
    $stmt->execute();
    $bnsv = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $stmt->closeCursor();
    unset($stmt);
} catch (PDOException $e) {
    echo $e->getCode() == 2002 ? 'Sin Conexión a Mysql (Error: 2002)' : 'Error: ' . $e->getMessage();
}
?>
<script>
    $('#tableAdqBnSv').DataTable({
        paginate: false,
    });
    $('#tableAdqBnSv').wrap('<div class="overflow" />');
</script>
<div class="px-0">
    <div class="shadow">
        <div class="card-header text-center py-2" style="background-color: #16a085 !important;">
            <h5 style="color: white;">GESTIÓN DE SERVICIOS DE ORDEN DE COMPRA</h5>
        </div>
        <form id="formDetallesAdq">
            <input type="hidden" name="idAdq" value="<?= $id_adq ?>">
            <div class="px-3 py-2">
                <table id="tableAdqBnSv" class="table table-striped table-bordered table-sm nowrap table-hover shadow align-middle" style="width:100%">
                    <thead>
                        <tr>
                            <th><input type="checkbox" id="selectAll" title="Marcar todos"></th>
                            <th class="bg-sofia">Pago</th>
                            <th class="bg-sofia">Bien o Servicio</th>
                            <th class="bg-sofia">Cantidad</th>
                            <th class="bg-sofia">Valor Unitario</th>
                            <th class="bg-sofia">Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        foreach ($bnsv as $bs) {
                        ?>
                            <tr>
                                <td>
                                    <div class="text-center listado">
                                        <input type="checkbox" name="check[<?= $bs['id_b_s'] ?>]" class="aprobado" text="<?= $bs['id_b_s'] ?>">
                                    </div>
                                </td>
                                <?php if (true) { ?>
                                    <td>
                                        <select class="form-select form-select-sm bg-input" id="tipo_<?= $bs['id_b_s'] ?>" name="tipo_pago[<?= $bs['id_b_s'] ?>]">
                                            <option value="H">Horas</option>
                                            <option value="M">Mensual</option>
                                        </select>
                                    </td>
                                <?php } ?>
                                <td class="text-start text-wrap"><i><?= $bs['bien_servicio'] ?></i></td>
                                <td><input type="number" name="bnsv[<?= $bs['id_b_s'] ?>]" class="form-control form-control-sm cantidad bg-input" value="0" text="<?= $bs['id_b_s'] ?>"></td>
                                <td><input type="number" name="val_bnsv[<?= $bs['id_b_s'] ?>]" class="form-control form-control-sm val_bnsv bg-input" value="0" text="<?= $bs['id_b_s'] ?>"></td>
                                <td class="text-end total">0.00</td>
                            </tr>
                        <?php
                        }
                        ?>
                    </tbody>
                </table>
            </div>
            <div class=" text-end pb-3 px-3">
                <button class="btn btn-sm btn-success" id="btnGuardarOrden">Guardar</button>
                <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Cerrar</button>
            </div>
        </form>
    </div>
</div>