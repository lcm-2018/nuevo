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
        <div class="card-header py-2 mb-2 text-center" style="background-color: #16a085 !important;">
            <h5 class="mb-0" style="color: white;">LIBROS AUXILIARES DE TESORERIA</h5>
        </div>
        <div class="px-2">
            <form id="frm_supersalud">
                <div class=" row">
                    <div class="col-md-4">
                        <label for="sl_tipo_libro" class="small">Tipo de libro</label>
                        <select class="filtro form-control form-control-sm bg-input" id="sl_tipo_libro" name="sl_tipo_libro">
                            <option value="1">Cuentas por pagar FT004</option>
                            <!--<option value="2">Relación de comprobantes de egreso generados</option>-->
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label for="txt_fecini" class="small">Fecha inicial</label>
                        <input type="date" class="form-control form-control-sm bg-input" id="txt_fecini" name="txt_fecini" placeholder="Fecha Inicial" value="<?php echo $_SESSION['vigencia'] ?>-01-01">
                    </div>
                    <div class="col-md-3">
                        <label for="txt_fecfin" class="small">Fecha final</label>
                        <input type="date" class="form-control form-control-sm bg-input" id="txt_fecfin" name="txt_fecfin" placeholder="Fecha final" value="<?php echo date("Y-m-d") ?>">
                    </div>
                    <div class="col-md-1">
                        <label for="btn_buscar" class="small">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</label>
                        <a type="button" id="btn_buscar" class="btn btn-outline-success btn-sm" title="Buscar">
                            <span class="fas fa-search fa-lg" aria-hidden="true"></span>
                        </a>
                    </div>
                    <div class="col-md-1">
                        <label for="btn_cancelar" class="small">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</label>
                        <a type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">
                            <span class="fas fa-window-close fa-lg" aria-hidden="true"></span>
                        </a>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<script type="text/javascript" src="../js/supersalud/supersalud.js?v=<?php echo date('YmdHis') ?>"></script>
<script type="text/javascript" src="../js/supersalud/common.js?v=<?php echo date('YmdHis') ?>"></script>