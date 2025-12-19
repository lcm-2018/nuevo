<?php
session_start();
if (!isset($_SESSION['user'])) {
    header('Location: ../index.php');
    exit();
}
include '../../../config/autoloader.php';


use Src\Common\Php\Clases\Permisos;

$id_rol = $_SESSION['rol'];
$id_user = $_SESSION['id_user'];

$permisos = new Permisos();
$opciones = $permisos->PermisoOpciones($id_user);
include '../../financiero/consultas.php';
?>
<!DOCTYPE html>
<html lang="es">

<?php
$vigencia = $_SESSION['vigencia'];
// concateno la fecha con el año vigencia
$fecha_max = date("Y-m-d", strtotime($_SESSION['vigencia'] . '-12-31'));
$fecha_min = date("Y-m-d", strtotime($_SESSION['vigencia'] . '-01-01'));
$fecha = new DateTime('now', new DateTimeZone('America/Bogota'));
$fecha_actual = $fecha->format('Y-m-d');
// obtengo la lista de municipio asociados a las sedes de la empresa
$cmd = \Config\Clases\Conexion::getConexion();
$sql = "SELECT
        `ctb_retenciones`.`id_retencion`
        ,`ctb_retenciones`.`nombre_retencion`
        FROM
        `ctb_retenciones`
        INNER JOIN `ctb_retencion_tipo` 
            ON (`ctb_retenciones`.`id_retencion_tipo` = `ctb_retencion_tipo`.`id_retencion_tipo`)
        WHERE (`ctb_retencion_tipo`.`id_retencion_tipo` =6);";
$rs = $cmd->query($sql);
$otras = $rs->fetchAll();
$rs->closeCursor();
unset($rs);
?>

<div class="row justify-content-center">
    <div class="col-sm-10 ">
        <div class="card">
            <h5 class="card-header small">Informe libro auxiliar </h5>
            <div class="card-body">
                <form>
                    <div class="row">
                        <div class="col-2"></div>
                        <div class="col-3 small">CUENTA INICIAL:</div>
                        <div class="col-6">
                            <input type="text" name="codigocta_ini" id="codigocta_ini" class="form-control form-control-sm bg-input" value="" required>
                            <input type="hidden" name="id_codigoctaini" id="id_codigoctaini" class="form-control form-control-sm bg-input" value="">
                            <input type="hidden" name="tipo_sede" id="tipo_sede" class="form-control form-control-sm bg-input" value="1">
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-2"></div>
                        <div class="col-3 small">CUENTA FINAL:</div>
                        <div class="col-6">
                            <input type="text" name="codigocta_fin" id="codigocta_fin" class="form-control form-control-sm bg-input" value="" required>
                            <input type="hidden" name="id_codigoctafin" id="id_codigoctafin" class="form-control form-control-sm bg-input" value="">

                        </div>
                    </div>
                    <div class="row">
                        <div class="col-2"></div>
                        <div class="col-3 small">Fecha de inicial:</div>
                        <div class="col-3"><input type="date" name="fecha_ini" id="fecha_ini" class="form-control form-control-sm bg-input" min="<?php echo $fecha_min; ?>" max="<?php echo $fecha_max; ?>" value="<?php echo $fecha_min; ?>"></div>
                    </div>

                    <div class="row">
                        <div class="col-2"></div>
                        <div class="col-3 small">Fecha de corte:</div>
                        <div class="col-3"><input type="date" name="fecha_fin" id="fecha_fin" class="form-control form-control-sm bg-input" min="<?php echo $fecha_min; ?>" max="<?php echo $fecha_max; ?>" value="<?php echo $fecha_actual; ?>"></div>
                    </div>

                    <div class="px-50">&nbsp; </div>
                    <div class="row">
                        <div class="col-12">
                            <div class="text-center pt-3">
                                <a type="button" class="btn btn-primary btn-sm" onclick="generarInformeCtb(9);"> Libro auxiliar</a>
                            </div>
                        </div>
                    </div>
            </div>
            </form>
        </div>
    </div>
</div>
</div>