<?php
session_start();
if (!isset($_SESSION['user'])) {
    header("Location: ../../../index.php");
    exit();
}
include '../../../../config/autoloader.php';
?>
<div class="px-0">
    <div class="shadow">
        <div class="card-header py-2 text-center" style="background-color: #16a085 !important;">
            <h5 class="mb-0" style="color: white;">CARGAR PRESUPUESTO EN EXCEL</h5>
        </div>
        <form id="formAddDocsTercero" enctype="multipart/form-data">
            <div class="row px-4 py-3">
                <div class="col-md-12">
                    <label for="fileDocHoEx" class="small">DOCUMENTO</label>
                    <input type="hidden" name="MAX_FILE_SIZE" value="30000" />
                    <input type="file" class="form-control-file border bg-input" name="file" id="file">
                </div>
            </div>
            <div class="text-center">
                <button class="btn btn-primary btn-sm" id="btnAddPtoExcel">Subir</button>
                <a type="button" class="btn btn-secondary  btn-sm" data-bs-dismiss="modal"> Cancelar</a>
            </div>
            <br>
        </form>
    </div>
</div>