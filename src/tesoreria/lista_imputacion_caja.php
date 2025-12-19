<?php
session_start();
if (!isset($_SESSION['user'])) {
    header('Location: ../index.php');
    exit();
}
include '../../config/autoloader.php';


use Src\Common\Php\Clases\Permisos;

$id_rol = $_SESSION['rol'];
$id_user = $_SESSION['id_user'];

$permisos = new Permisos();
$opciones = $permisos->PermisoOpciones($id_user);

$id_doc = isset($_POST['id']) ? $_POST['id'] : exit('Acceso no disponible');

try {
    $cmd = \Config\Clases\Conexion::getConexion();
    $sql = "SELECT
                `tes_caja_rubros`.`id_caja_rubros`
                , `tes_caja_rubros`.`valor`
                , `pto_cargue`.`cod_pptal`
                , `pto_cargue`.`nom_rubro`
                , IFNULL(`gastado`.`valor`,0) AS `valor_pag`
            FROM
                `tes_caja_rubros`
                INNER JOIN `pto_cargue` 
                    ON (`tes_caja_rubros`.`id_rubro_gasto` = `pto_cargue`.`id_cargue`)
                LEFT JOIN 
                    (SELECT 
                        `id_caja_rubros`, SUM(`valor`) AS `valor`
                    FROM `tes_caja_mvto`
                    GROUP BY `id_caja_rubros`) AS `gastado`
                    ON (`tes_caja_rubros`.`id_caja_rubros` = `gastado`.`id_caja_rubros`)
            WHERE (`tes_caja_rubros`.`id_caja_const` = $id_doc)";
    $rs = $cmd->query($sql);
    $listado = $rs->fetchAll();
    $rs->closeCursor();
    unset($rs);
} catch (PDOException $e) {
    echo $e->getCode() == 2002 ? 'Sin Conexión a Mysql (Error: 2002)' : 'Error: ' . $e->getCode();
}
?>
<div class="px-0">
    <div class="shadow">
        <div class="card-header text-center py-2" style="background-color: #16a085 !important;">
            <h5 class="mb-0" style="color: white;">LISTA DE IMPUTACIONES CAJA MENOR </h5>
        </div>
        <div class="p-3">
            <form id="formImputacion">
                <input type="hidden" name="id_doc" value="<?php echo $id_doc ?>">
                <?php
                $band = true;
                foreach ($listado as $l) {
                    $max = $l['valor'] - $l['valor_pag'];
                    $value = $max > 0 ? $max : 0;
                ?>
                    <div class="row mb-2">
                        <div class="col-md-3">
                            <?php if ($band) { ?>
                                <span class="small">Código presupuestal</span>
                            <?php } ?>
                            <div class="form-control form-control-sm bg-input text-start bg-light"><?php echo $l['cod_pptal'] ?></div>
                        </div>
                        <div class="col-md-5">
                            <?php if ($band) { ?>
                                <span class="small">Rubro</span>
                            <?php } ?>
                            <div class="form-control form-control-sm bg-input text-start bg-light"><?php echo $l['nom_rubro'] ?></div>
                        </div>
                        <div class="col-md-2">
                            <?php if ($band) { ?>
                                <span for="valor" class="small">Valor RP</span>
                            <?php } ?>
                            <div class="form-control form-control-sm bg-input text-start bg-light"><?php echo number_format($max, 2) ?></div>
                        </div>
                        <div class="col-md-2">
                            <?php if ($band) { ?>
                                <span for="valor" class="small">Valor CxP</span>
                            <?php } ?>
                            <input type="text" name="valor[<?php echo $l['id_caja_rubros'] ?>]" id="valor" onkeyup="NumberMiles(this)" class="form-control form-control-sm bg-input text-end ValImputacion" min="0" max="<?php echo $max ?>" value="<?php echo number_format($value, 2) ?>">
                        </div>
                    </div>
                <?php
                    $band = false;
                }
                ?>
            </form>
        </div>
    </div>
    <div class="text-end pt-3">
        <a type="button" class="btn btn-primary btn-sm" onclick="DetalleImputacionCajaMenor()">Guardar</a>
        <a type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal"> Aceptar</a>
    </div>
</div>
<?php
