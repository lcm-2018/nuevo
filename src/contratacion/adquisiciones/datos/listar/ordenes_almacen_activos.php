<?php
session_start();
if (!isset($_SESSION['user'])) {
    header('Location: ../../../../index.php');
    exit();
}
include '../../../../conexion.php';
$tipo = isset($_POST['tipo']) ? $_POST['tipo'] : exit('Acción no permitida');
$tp_orden = $tipo == '1' ? 'ALMACÉN' : 'ACTIVOS FIJOS';
try {
    $cmd = \Config\Clases\Conexion::getConexion();
    
    $sql = "SELECT
                `id_pedido`, `num_pedido`, `fec_pedido`, `detalle`
            FROM
                `far_alm_pedido`
            LEFT JOIN `ctt_adquisiciones`
                ON(`ctt_adquisiciones`.`id_orden` = `far_alm_pedido`.`id_pedido`)
            WHERE (`tipo` = $tipo AND `far_alm_pedido`.`estado`  = 2 AND `ctt_adquisiciones`.`id_orden` IS NULL)";
    $rs = $cmd->query($sql);
    $tbnsv = $rs->fetchAll();
    $cmd = null;
} catch (PDOException $e) {
    echo $e->getCode() == 2002 ? 'Sin Conexión a Mysql (Error: 2002)' : 'Error: ' . $e->getMessage();
}
?>
<div class="px-0">
    <div class="shadow">
         <div class="card-header text-center py-2" style="background-color: #16a085 !important;">
            <h5 style="color: white;">LISTA DE ORDENES DE <?php echo $tp_orden; ?></h5>
        </div>
        <div class="card-body">
            <table class="table table-sm table-bordered table-hover table-striped">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>N° ORDEN</th>
                        <th>FECHA</th>
                        <th>DETALLE</th>
                        <th>ACCIONES</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $i = 1;
                    foreach ($tbnsv as $row) {
                        $id = $row['id_pedido'];
                    ?>
                        <tr>
                            <td><?php echo $id; ?></td>
                            <td><?php echo  $row['num_pedido']; ?></td>
                            <td><?php echo $fec_pedido = $row['fec_pedido']; ?></td>
                            <td class="text-left"><?php echo $row['detalle']; ?></td>
                            <td>
                                <button class="btn btn-outline-success btn-sm btn-circle shadow-gb" title="Asociar Orden a Adquisición" onclick="AsociarOrden(<?php echo $id; ?>)"><span class="fas fa-layer-group fa-lg"></span></button>
                            </td>
                        </tr>
                    <?php
                    }
                    ?>
                </tbody>
            </table>
            <div class="text-right">
                <a type="button" class="btn btn-secondary  btn-sm" data-bs-dismiss="modal">Cerrar</a>
            </div>
        </div>
    </div>
</div>