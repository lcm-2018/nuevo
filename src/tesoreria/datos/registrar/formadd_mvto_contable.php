<?php
session_start();
if (!isset($_SESSION['user'])) {
    header("Location: ../../../index.php");
    exit();
}
include '../../../../config/autoloader.php';
$error = "Debe diligenciar este campo";
$fecha = date("Y-m-d");
// Estabelcer fecha minima con vigencia
$fecha_min = date("Y-m-d", strtotime($_SESSION['vigencia'] . '-01-01'));
$fecha_max = date("Y-m-d", strtotime($_SESSION['vigencia'] . '-12-31'));

?>
<div class="px-0">
    <div class="shadow">
        <div class="card-header text-center py-2" style="background-color: #16a085 !important;">
            <h5 class="mb-0" style="color: white;">CREAR NUEVO DOCUMENTO</h5>
        </div>
        <form id="formAddMvtoCtb">
            <div class="row mb-2 px-4 pt-2">
                <div class="col-md-6">
                    <label for="fecha" class="small">FECHA </label>
                    <input type="date" name="fecha" id="fecha" class="form-control form-control-sm bg-input" value="<?php echo $fecha; ?>" min="<?php echo $fecha_min; ?>" max="<?php echo $fecha_max; ?>">

                </div>
                <input type="hidden" name="datFecVigencia" value="<?php echo $_SESSION['vigencia'] ?>">
                <div class="col-md-6">
                    <label for="numDoc" class="small">NUMERO </label>
                    <input type="number" name="numDoc" id="numDoc" class="form-control form-control-sm bg-input" required value="">
                </div>

            </div>
            <div class="row mb-2 px-4  ">
                <div class="col-md-12">
                    <label for="Tercero" class="small">TERCERO</label>
                    <input type="text" name="terceromov" id="terceromov" class="form-control form-control-sm bg-input" value="">
                    <input type="hidden" name="id_tercero" id="id_tercero" class="form-control form-control-sm bg-input" value="">
                </div>

            </div>
            <div class="row mb-2 px-4  ">
                <div class="col-md-12">
                    <label for="Objeto" class="small">OBJETO CRP</label>
                    <textarea id="objeto" type="text" name="objeto" class="form-control form-control-sm bg-input py-0 sm" aria-label="Default select example" rows="4" required></textarea>
                </div>

            </div>
            <div class="row mb-2 px-2 ">
                <div class="text-center pb-3 w-100">
                    <button type="submit" class="btn btn-primary btn-sm" style="width: 5rem;" id="registrarMvtoCtb">Aceptar</button>
                    <a type="button" class="btn btn-danger btn-sm" data-bs-dismiss="modal">Cancelar</a>
                </div>
        </form>
    </div>
</div>