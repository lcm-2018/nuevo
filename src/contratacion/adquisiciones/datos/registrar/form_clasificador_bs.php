<?php
session_start();
if (!isset($_SESSION['user'])) {
    header('Location: ../../../../../index.php');
    exit();
}
include_once '../../../../../config/autoloader.php';

$cmd = \Config\Clases\Conexion::getConexion();
$id_clas = isset($_POST['id']) ? $_POST['id'] : 0;
$id_adq = isset($_POST['id_adq']) ? $_POST['id_adq'] : exit('Acción no permitida');

$titulo = "REGISTRAR CLASIFICADOR";
$boton = "Registrar";
$codigo_unspsc = "";
$descripcion_unspsc = "";
$id_unspsc = 0;

// Si id_clas > 0, es una actualización
if ($id_clas > 0) {
    $titulo = "ACTUALIZAR CLASIFICADOR";
    $boton = "Actualizar";

    try {
        $sql = "SELECT 
                    `ctt_clasificador_bs`.`id_clas`,
                    `ctt_clasificador_bs`.`id_unspsc`,
                    `tb_codificacion_unspsc`.`codigo`,
                    `tb_codificacion_unspsc`.`descripcion`
                FROM 
                    `ctt_clasificador_bs`
                INNER JOIN `tb_codificacion_unspsc` 
                    ON (`ctt_clasificador_bs`.`id_unspsc` = `tb_codificacion_unspsc`.`id_codificacion`)
                WHERE `ctt_clasificador_bs`.`id_clas` = ?";

        $stmt = $cmd->prepare($sql);
        $stmt->bindParam(1, $id_clas, PDO::PARAM_INT);
        $stmt->execute();
        $clasificador = $stmt->fetch();
        $stmt->closeCursor();

        if ($clasificador) {
            $id_unspsc = $clasificador['id_unspsc'];
            $codigo_unspsc = $clasificador['codigo'];
            $descripcion_unspsc = $clasificador['descripcion'];
        }
    } catch (PDOException $e) {
        echo $e->getCode() == 2002 ? 'Sin Conexión a Mysql (Error: 2002)' : 'Error: ' . $e->getMessage();
    }
}
$cmd = null;
?>
<div class="px-0">
    <div class="shadow">
        <div class="card-header text-center py-2" style="background-color: #16a085 !important;">
            <h5 style="color: white;"><?= $titulo ?></h5>
        </div>
        <form id="formClasificadorBS">
            <input type="hidden" name="id_clas" id="id_clas" value="<?= $id_clas ?>">
            <input type="hidden" name="id_adq" value="<?= $id_adq ?>">
            <input type="hidden" id="id_unspsc" name="id_unspsc" value="<?= $id_unspsc ?>">

            <div class="row px-4 pt-3">
                <div class="col-md-12 mb-3">
                    <label for="buscaUnspsc" class="small">CÓDIGO UNSPSC</label>
                    <input type="text"
                        id="buscaUnspsc"
                        name="buscaUnspsc"
                        class="form-control form-control-sm bg-input awesomplete"
                        value="<?= $codigo_unspsc ?> - <?= $descripcion_unspsc ?>"
                        placeholder="Buscar código UNSPSC...">
                </div>
            </div>

            <div class="text-center pb-3">
                <button type="button" class="btn btn-primary btn-sm" id="btnSaveClasificador"><?= $boton ?></button>
                <a type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancelar</a>
            </div>
        </form>
    </div>
</div>