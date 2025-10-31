<?php
session_start();
if (!isset($_SESSION['user'])) {
    header('Location: ../../../../../index.php');
    exit();
}
include '../../../../../config/autoloader.php';
$idT = isset($_POST['idt']) ? $_POST['idt'] : exit('Acción no permitida');
?>
<div class="px-0">
    <div class="shadow">
        <div class="card-header text-center p-2" style="background-color: #16a085 !important;">
            <h5 style="color: white;">REGISTRAR ACTIVIDAD ECONÓMICA DE TERCERO</h5>
        </div>
        <form id="formAddActvEcon">
            <input type="number" id="idTercero" name="idTercero" value="<?php echo $idT ?>" hidden>
            <div class="row px-4 pt-2">
                <div class="form-group col-md-10">
                    <label for="buscarActvEcono" class="small">ACTIVIDAD ECONÓMICA</label>
                    <input type="text" class="form-control form-control-sm bg-input" id="buscarActvEcono">
                    <input type="hidden" id="slcActvEcon" name="slcActvEcon" value="0">
                </div>
                <div class="form-group col-md-2">
                    <label for="datFecInicio" class="small">FECHA INICIO</label>
                    <input type="date" class="form-control form-control-sm bg-input" id="datFecInicio" name="datFecInicio">
                </div>
            </div>
            <div class="text-end p-3">
                <button class="btn btn-primary btn-sm" id="btnAddActvEcon">Agregar</button>
                <a type="button" class="btn btn-secondary  btn-sm" data-bs-dismiss="modal"> Cancelar</a>
            </div>
            <br>
        </form>
    </div>
</div>