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
            `tb_municipios`.`nom_municipio`
            ,`tb_sedes`.`id_tercero_api` AS `id_tercero`
        FROM
            `tb_sedes`
        INNER JOIN `tb_municipios` 
        ON (`tb_sedes`.`id_municipio` = `tb_municipios`.`id_municipio`)
        GROUP BY `tb_sedes`.`id_tercero_api`";
$rs = $cmd->query($sql);
$sedes = $rs->fetchAll();
$rs->closeCursor();
unset($rs);
?>

<div class="row mb-2 justify-content-center">
    <div class="col-sm-8 ">
        <div class="card">
            <h5 class="card-header small">Informe de impuestos municipales aplicados</h5>
            <div class="card-body">
                <form>
                    <div class="row mb-2">
                        <div class="col-2"></div>
                        <div class="col-3 small">Municipio:</div>
                        <div class="col-3">
                            <select name="tipo_sede" id="tipo_sede" class="form-control form-control-sm bg-input">
                                <option value="0">-- Selecionar --</option>
                                <?php
                                foreach ($sedes as $sed) {
                                    echo '<option value="' . $sed['id_tercero'] . '">' . $sed['nom_municipio'] .  '</option>';
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
                                <button value="1" type="button" class="btn btn-primary btn-sm" onclick="generarInformeCtb(this);"> Resumen</button>
                                <button value="2" type="button" class="btn btn-warning btn-sm" onclick="generarInformeCtb(this);"> Detallado</button>
                                <button value="3" type="button" class="btn btn-secondary btn-sm" onclick="generarInformeCtb(this);"> Exogena</button>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <div class="col-md-9">
        <div class="row mb-2">
            <br>
            <div id="areaImprimir" class="table-responsive px-2" style="font-size: 100%;"></div>
        </div>
    </div>
</div>