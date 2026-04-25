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
$hoy = date("Y-m-d");
?>

<div class="row mb-2 justify-content-center">
    <div class="col-sm-12 ">
        <div class="card">
            <h5 class="card-header small">Informe de movimientos Integración</h5>
            <div class="card-body">
                <form>
                    <div class="row mb-2 mb-1">
                        <div class="col-3"></div>
                        <div class="col-md-2"><span class="small">Fecha de inicial:</span></div>
                        <div class="col-md-4"><input type="date" name="fecha_ini" id="fecha_ini" class="form-control form-control-sm bg-input" min="<?php echo $fecha_min; ?>" max="<?php echo $fecha_max; ?>" value="<?php echo $fecha_min; ?>"></div>
                    </div>
                    <div class="row mb-2 mb-1">
                        <div class="col-3"></div>
                        <div class="col-md-2 small">Fecha de corte:</div>
                        <div class="col-md-4"><input type="date" name="fecha_fin" id="fecha_fin" class="form-control form-control-sm bg-input" min="<?php echo $fecha_min; ?>" max="<?php echo $fecha_max; ?>" value="<?php echo $hoy; ?>"></div>
                    </div>
                    <div class="row mb-2">
                        <div class="col-12">
                            <div class="text-center pt-3">
                                <button class="btn btn-primary" onclick="generarInformeCtb(this)" value="14"><span></span>Generar</button>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <div id="areaImprimir">
    </div>
</div>