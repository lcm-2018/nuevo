<?php
session_start();
if (!isset($_SESSION['user'])) {
    header('Location: ../../../../../index.php');
    exit();
}
include_once '../../../../../config/autoloader.php';
$id_rol = isset($_SESSION['rol']) ? $_SESSION['rol'] : null;
$id_user = isset($_SESSION['id_user']) ? $_SESSION['id_user'] : null;
$permisos = new \Src\Common\Php\Clases\Permisos();
$opciones = $permisos->PermisoOpciones($id_user);

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
                    , `tb_tipo_contratacion`.`tipo_contrato`
                    , `tb_tipo_compra`.`tipo_compra`
                FROM
                    `ctt_bien_servicio`
                    INNER JOIN `tb_tipo_bien_servicio` 
                        ON (`ctt_bien_servicio`.`id_tipo_bn_sv` = `tb_tipo_bien_servicio`.`id_tipo_b_s`)
                    INNER JOIN `tb_tipo_contratacion` 
                        ON (`tb_tipo_bien_servicio`.`id_tipo` = `tb_tipo_contratacion`.`id_tipo`)
                    INNER JOIN `tb_tipo_compra` 
                        ON (`tb_tipo_contratacion`.`id_tipo_compra` = `tb_tipo_compra`.`id_tipo`)
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
    $bnsv = $stmt->fetchAll();
} catch (PDOException $e) {
    echo $e->getCode() == 2002 ? 'Sin Conexión a Mysql (Error: 2002)' : 'Error: ' . $e->getMessage();
}
?>
<script>
    //dataTable Adquisicion de bienes o servicios
    $('#tableAdqBnSv').DataTable({
        /*dom: "<'row'<'reg-orden col-md-6'B><'col-md-6'f>>" +
            "<'row'<'col-sm-12'tr>>" +
            "<'row'<'col-sm-12 col-md-5'i><'col-sm-12 col-md-7'p>>",
        buttons: [{
            text: 'CREAR ORDEN',
            action: function() {
                let b = 1;
                $('input[type=checkbox]:checked').each(function() {
                    let idcheck = $(this).val();
                    let idCant = 'bnsv_' + idcheck;
                    let idVAl = 'val_bnsv_' + idcheck;
                    if ($('#' + idCant).val() === '' || parseInt($('#' + idCant).val()) <= 0) {
                        showError(idCant);
                        bordeError(idCant);
                        b = 0
                        return false;;
                    }
                    if ($('#' + idVAl).val() === '' || parseInt($('#' + idVAl).val()) <= 0) {
                        showError(idVAl);
                        bordeError(idVAl);
                        b = 0
                        return false;;
                    }

                });
                if (b === 1) {
                    let datos = $('#formDetallesAdq').serialize();
                    $.ajax({
                        type: 'POST',
                        url: 'registrar/new_adquisicion_bn_sv.php',
                        data: datos,
                        success: function(r) {
                            if (r === 0) {
                                $('#divModalError').modal('show');
                                $('#divMsgError').html("No se agregó ningún bien o servicio");
                            } else if (r > 0) {
                                let id = 'tableAdquisiciones';
                                reloadtable(id);
                                $('#divModalForms').modal('hide');
                                $('#divModalDone').modal('show');
                                $('#divEstadoBnSv').html('<div class="p-3 mb-2 bg-success text-white">ORDEN AGREGADA CORRECTAMENTE</div>');
                                $('#divMsgDone').html('Se agregaron' + r + 'bien(es) o servicio(s) a la compra actual');
                            } else {
                                $('#divModalError').modal('show');
                                $('#divMsgError').html(r);
                            }
                        }
                    });
                    return false;
                }
            }
        }],*/
        //language: dataTable_es,
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
            <input type="hidden" name="idAdq" value="<?php echo $id_adq ?>">
            <div class="px-3 py-2">
                <table id="tableAdqBnSv" class="table table-striped table-bordered table-sm nowrap table-hover shadow align-middle" style="width:100%">
                    <thead>
                        <tr>
                            <th><input type="checkbox" id="selectAll" title="Marcar todos"></th>
                            <th class="bg-sofia">Pago</th>
                            <th class="bg-sofia">Bien o Servicio</th>
                            <th class="bg-sofia">Cantidad</th>
                            <th class="bg-sofia">Valor Unitario</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        foreach ($bnsv as $bs) {
                        ?>
                            <tr>
                                <td>
                                    <div class="text-center listado">
                                        <input type="checkbox" name="check[<?php echo $bs['id_b_s'] ?>]" class="aprobado">
                                    </div>
                                </td>
                                <?php if (true) { ?>
                                    <td>
                                        <select class="form-control form-control-sm altura py-0 bg-input" id="tipo_<?php echo $bs['id_b_s'] ?>" name="tipo_pago[<?php echo $bs['id_b_s'] ?>]">
                                            <option value="H">Horas</option>
                                            <option value="M">Mensual</option>
                                        </select>
                                    </td>
                                <?php } ?>
                                <td class="text-left text-wrap"><i><?php echo $bs['bien_servicio'] ?></i></td>
                                <td><input type="number" name="bnsv[<?php echo $bs['id_b_s'] ?>]" class="form-control altura cantidad bg-input" value="0"></td>
                                <td><input type="number" name="val_bnsv[<?php echo $bs['id_b_s'] ?>]" class="form-control altura val_bnsv bg-input" value="0"></td>
                            </tr>
                        <?php
                        }
                        ?>
                    </tbody>
                </table>
            </div>
            <div class="text-right pb-3 px-3">
                <button class="btn btn-sm btn-success" id="btnGuardarOrden">Guardar</button>
                <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Cerrar</button>
            </div>
        </form>
    </div>
</div>