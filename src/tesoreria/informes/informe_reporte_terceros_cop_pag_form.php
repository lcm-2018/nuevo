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
    <div class="col-sm-12 ">
        <div class="card">
            <h5 class="card-header small">Informe auxiliar por tercero pagos y causaciones</h5>
            <div class="card-body">
                <form>
                    <div class="row mb-1">
                        <div class="col-2"></div>
                        <div class="col-3 small">Tercero:</div>
                        <div class="col-3">
                            <input type="text" name="terceromov" id="terceromov" class="form-control form-control-sm bg-input" value="" required>
                            <input type="hidden" name="id_tercero" id="id_tercero" value="">

                        </div>
                    </div>
                    <div class="row mb-1">
                        <div class="col-2"></div>
                        <div class="col-3 small">Fecha de inicial:</div>
                        <div class="col-3"><input type="date" name="fecha_ini" id="fecha_ini" class="form-control form-control-sm bg-input" min="<?php echo $fecha_min; ?>" max="<?php echo $fecha_max; ?>" value="<?php echo $fecha_min; ?>"></div>
                    </div>

                    <div class="row mb-1">
                        <div class="col-2"></div>
                        <div class="col-3 small">Fecha de corte:</div>
                        <div class="col-3"><input type="date" name="fecha_fin" id="fecha_fin" class="form-control form-control-sm bg-input" min="<?php echo $fecha_min; ?>" max="<?php echo $fecha_max; ?>" value="<?php echo $fecha_actual; ?>"></div>
                    </div>

                    <div class="px-10">&nbsp; </div>
                    <div class="row">
                        <div class="col-12">
                            <div class="text-center">
                                <a type="button" class="btn btn-primary btn-sm" onclick="generarReporteTerceros(3);"> Libro auxiliar</a>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>