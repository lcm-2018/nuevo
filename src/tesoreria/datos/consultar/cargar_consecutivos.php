<?php
session_start();
if (!isset($_SESSION['user'])) {
    header("Location: ../../../index.php");
    exit();
}
include '../../../../config/autoloader.php';
$tipo = (int)$_POST['id'];
$id_vigencia = (int)$_SESSION['id_vigencia'];

try {
    $cmd = \Config\Clases\Conexion::getConexion();
    $sql = "SELECT 
                `d1`.`id_manu` + 1 AS `consecutivo`
            FROM `ctb_doc` `d1`
                LEFT JOIN `ctb_doc` `d2`
                    ON `d2`.`id_manu` = `d1`.`id_manu` + 1
                    AND `d2`.`id_vigencia` = `d1`.`id_vigencia`
                    AND `d2`.`id_tipo_doc` = `d1`.`id_tipo_doc`
            WHERE `d1`.`id_vigencia` = :vigencia AND `d1`.`id_tipo_doc` = :tipo 
                AND `d1`.`id_manu` < (SELECT MAX(`id_manu`) FROM `ctb_doc` WHERE `id_vigencia` = :vigencia AND `id_tipo_doc` = :tipo)
            AND `d2`.`id_manu` IS NULL
            ORDER BY `consecutivo`
            LIMIT 100";

    $stmt = $cmd->prepare($sql);
    $stmt->execute([
        ':vigencia' => $id_vigencia,
        ':tipo'     => $tipo
    ]);

    $consecutivos = $stmt->fetchAll();

    /* Si no existen documentos aún */
    if (empty($consecutivos)) {
        $consecutivos = [];
    }

    $cmd = null;
} catch (PDOException $e) {
    echo $e->getCode() == 2002 ? 'Sin Conexión a Mysql (Error: 2002)' : 'Error: ' . $e->getCode();
}
?>
<div class="px-0">
    <div class="shadow">
        <div class="card-header text-center py-2" style="background-color: #16a085 !important;">
            <h5 class="mb-0 text-white">
                CONSECUTIVOS DISPONIBLES
            </h5>
        </div>
        <div class="p-3">
            <?php if (!empty($consecutivos)): ?>
                <div class="row g-2">
                    <?php foreach ($consecutivos as $c): ?>
                        <div class="col-auto">
                            <span class="badge bg-success fs-6 px-3 py-2"><?= $c['consecutivo']; ?></span>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="alert alert-warning">
                    <i class="fas fa-exclamation-triangle"></i>
                    No hay consecutivos disponibles
                </div>
            <?php endif; ?>
        </div>
    </div>
    <div class="text-center">
        <a type="button" class="btn btn-secondary btn-sm mt-3" data-bs-dismiss="modal">Cerrar</a>
    </div>
</div>