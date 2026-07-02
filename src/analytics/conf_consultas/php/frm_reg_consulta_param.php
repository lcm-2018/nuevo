<?php
session_start();
if (!isset($_SESSION['user'])) {
    header("Location: ../../../index.php");
    exit();
}
include '../../../../config/autoloader.php';
include '../../common/php/cargar_combos.php';

use Src\Analytics\Conf_Consultas\Php\Clases\ConsultasModel;

$id = isset($_POST['id']) ? (int)$_POST['id'] : -1;

$model = new ConsultasModel();
$obj = ($id !== -1) ? $model->getParametroById($id) : $model->getParametroById(0);

if ($id == -1) {
    $obj['tipo'] = 1;    
}

?>
<div class="px-0">
    <div class="shadow">
        <div class="card-header py-2 text-center" style="background-color: #16a085 !important;">
            <h7 style="color: white;">REGISTRAR PARÁMETRO</h7>
        </div>

        <div class="p-2">
            <form id="frm_reg_parametro">
                <input type="text" id="id_parametro" name="id_parametro" value="<?php echo $id ?>">
                <div class="row">
                    <div class="col-md-2">
                        <label for="txt_parametro" class="small">Parámetro</label>
                        <input type="text" class="form-control form-control-sm bg-input valcode" id="txt_parametro" name="txt_parametro" value="<?php echo $obj['parametro'] ?>">
                    </div>
                    <div class="col-md-10">
                        <label for="txt_etiqueta" class="small">Etiqueta</label>
                        <input type="text" class="form-control form-control-sm bg-input" id="txt_etiqueta" name="txt_etiqueta" value="<?php echo $obj['etiqueta'] ?>">
                    </div>
                    <div class="col-md-12">
                        <label for="txt_des_parametro" class="small">Descripción</label>
                        <input type="text" class="form-control form-control-sm bg-input" id="txt_des_parametro" name="txt_des_parametro" value="<?php echo $obj['descripcion'] ?>">
                    </div>
                    <div class="col-md-5">
                        <label for="sl_tip_parametro" class="small">Tipo</label>
                        <select class="form-select form-select-sm bg-input" id="sl_tip_parametro" name="sl_tip_parametro">
                            <?= tipo_parametro('', $obj['tipo']) ?>
                        </select>
                    </div>  
                    <div class="col-md-12">
                        <label for="txt_det_parametro" class="small">Detalles</label>
                        <input type="text" class="form-control form-control-sm bg-input" id="txt_det_parametro" name="txt_det_parametro" value="<?php echo $obj['detalles'] ?>">
                    </div>  
                </div>                    
            </form>
        </div>
    </div>

    <div class="text-center pt-3">
        <button type="button" class="btn btn-primary btn-sm" id="btn_guardar_parametro">Guardar</button>
        <a type="button" class="btn btn-secondary  btn-sm" data-bs-dismiss="modal">Cancelar</a>
    </div>
</div>