<?php
session_start();
if (!isset($_SESSION['user'])) {
    header('Location: ../index.php');
    exit();
}
$vigencia = $_SESSION['vigencia'];
// concateno la fecha con el año vigencia
$fecha_max = date("Y-m-d", strtotime($_SESSION['vigencia'] . '-12-31'));
$fecha_min = date("Y-m-d", strtotime($_SESSION['vigencia'] . '-01-01'));
$fecha = new DateTime('now', new DateTimeZone('America/Bogota'));
$fecha_actual = $fecha->format('Y-m-d');
?>

<div class="row justify-content-center">
    <div class="col-md-12 ">
        <div class="card">
            <h5 class="card-header small">Relación de pagos</h5>
            <div class="card-body">
                <form>
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

                    <div class="row">
                        <div class="col-2"></div>
                        <div class="col-3 small">Tiene presupuesto:</div>
                        <div class="col-3"><input type="checkbox" name="pto" id="pto" class="form-check-input" checked></div>
                    </div>
                    <div class="row">
                        <div class="col-2"></div>
                        <div class="col-3 small">Egreso manual:</div>
                        <div class="col-3"><input type="checkbox" name="egr_man" id="egr_man" class="form-check-input"></div>
                    </div>
                    <div class="row">
                        <div class="col-2"></div>
                        <div class="col-3 small">Descuentos detallado:</div>
                        <div class="col-3"><input type="checkbox" name="desc_det" id="desc_det" class="form-check-input"></div>
                    </div>

                    <div class="px-50">&nbsp; </div>
                    <div class="row">
                        <div class="col-12">
                            <div class="text-center pt-3">
                                <a type="button" class="btn btn-primary btn-sm" onclick="generarRelacionPagos(this);">Informe</a>
                                <a type="" id="btnExcelEntrada" class="btn btn-sm btn-outline-success" value="01" title="Exprotar a Excel">
                                    <span class="fas fa-file-excel fa-lg" aria-hidden="true"></span>
                                </a>
                                <a type="button" class="btn btn-danger btn-sm" title="Imprimir" onclick="imprSelecTes('areaImprimir','<?php echo 0; ?>');"><span class="fas fa-print fa-lg" aria-hidden="true"></span></a>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
            <div id="areaImprimir" class="pt-3" style="font-size: 10px;">

            </div>
        </div>
    </div>
</div>