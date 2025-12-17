<?php
session_start();
if (!isset($_SESSION['user'])) {
    header('Location: ../index.php');
    exit();
}
include '../../../config/autoloader.php';
$id_rol = $_SESSION['rol'];
$id_user = $_SESSION['id_user'];

use Config\Clases\Plantilla;
use Src\Common\Php\Clases\Permisos;

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
?>

<div class="row mb-2 justify-content-center">
    <div class="col-sm-8 ">
        <div class="card">
            <h5 class="card-header small">Informe de otros descuentos aplicados</h5>
            <div class="card-body">
                <form>
                    <div class="row mb-2">
                        <div class="col-2"></div>
                        <div class="col-3 small">Descuento:</div>
                        <div class="col-3">
                            <select name="tipo_sede" id="tipo_sede" class="form-control form-control-sm bg-input">
                                <option value="0">-- Selecionar --</option>
                                <?php
                                foreach ($otras as $sed) {
                                    echo '<option value="' . $sed['id_retencion'] . '">' . $sed['nombre_retencion'] .  '</option>';
                                }
                                ?>
                            </select>
                        </div>
                    </div>
                    <div class="row mb-2">
                        <div class="col-2"></div>
                        <div class="col-3 small">Fecha de inicial:</div>
                        <div class="col-3"><input type="date" name="fecha_ini" id="fecha_ini" class="form-control form-control-sm bg-input" min="<?php echo $fecha_min; ?>" max="<?php echo $fecha_max; ?>" value="<?php echo $fecha_min; ?>"></div>
                    </div>

                    <div class="row mb-2">
                        <div class="col-2"></div>
                        <div class="col-3 small">Fecha de corte:</div>
                        <div class="col-3"><input type="date" name="fecha_fin" id="fecha_fin" class="form-control form-control-sm bg-input" min="<?php echo $fecha_min; ?>" max="<?php echo $fecha_max; ?>" value="<?php echo $fecha_actual; ?>"></div>
                    </div>

                    <div class="px-50">&nbsp; </div>
                    <div class="row mb-2">
                        <div class="col-12">
                            <div class="text-center pt-3">
                                <button value="6" type="button" class="btn btn-primary btn-sm" onclick="generarInformeCtb(this)"> Resumen</button>
                                <button value="7" type="button" class="btn btn-warning btn-sm" onclick="generarInformeCtb(this)"> Detallado</button>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <div id="areaImprimir" class="table-responsive px-2" style="font-size: 100%;"></div>
</div>