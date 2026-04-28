<?php
session_start();
if (!isset($_SESSION['user'])) {
    header("Location: ../../../index.php");
    exit();
}
include '../../../../config/autoloader.php';
include '../../common/php/cargar_combos.php';

use src\analytics\Conf_Bdatos\Php\Clases\BdatosModel;

$id = isset($_POST['id']) ? (int)$_POST['id'] : -1;

$model = new BdatosModel();
$obj = ($id !== -1) ? $model->getById($id) : $model->getById(0); 

$obj['estado'] = 1;

?>
<div class="px-0">
    <div class="shadow">
        <div class="card-header py-2 text-center" style="background-color: #16a085 !important;">
            <h5 class="text-white mb-0">REGISTRAR ENTIDAD-BD</h5>
        </div>
        <div class="p-3">
            <form id="frm_reg_bdatos">
                <input type="hidden" id="id_entidad" name="id_entidad" value="<?php echo $id ?>">
                <div class="row">
                    <div class="col-md-12">
                        <label for="txt_nom_entidad" class="small">Entidad</label>
                        <input type="text" class="form-control form-control-sm bg-input" id="txt_nom_entidad" name="txt_nom_entidad" value="<?php echo $obj['nombre_entidad'] ?>" maxlength="100">
                    </div>
                    <div class="col-md-12">
                        <label for="txt_des_entidad" class="small">Descripción</label>
                        <input type="text" class="form-control form-control-sm bg-input" id="txt_des_entidad" name="txt_des_entidad" value="<?php echo $obj['descri_entidad'] ?>" maxlength="200">
                    </div>
                    <div class="col-md-3">
                        <label for="txt_ip_servidor" class="small">IP Servidor</label>
                        <input type="text" class="form-control form-control-sm bg-input" id="txt_ip_servidor" name="txt_ip_servidor" value="<?php echo $obj['ip_servidor'] ?>" maxlength="20">
                    </div>
                    <div class="col-md-3">
                        <label for="txt_nom_bd" class="small">Nombre Base Datos</label>
                        <input type="text" class="form-control form-control-sm bg-input" id="txt_nom_bd" name="txt_nom_bd" value="<?php echo $obj['nombre_bd'] ?>" maxlength="20">
                    </div>
                    <div class="col-md-3">
                        <label for="txt_usr_bd" class="small">Usuario Base Datos</label>
                        <input type="text" class="form-control form-control-sm bg-input" id="txt_usr_bd" name="txt_usr_bd" value="<?php echo $obj['usuario_bd'] ?>" maxlength="20">
                    </div>
                    <div class="col-md-3">
                        <label for="txt_pto_bd" class="small">Puerto Base Datos</label>
                        <input type="text" class="form-control form-control-sm bg-input number" id="txt_pto_bd" name="txt_pto_bd" value="<?php echo $obj['puerto_bd'] ?>" maxlength="10">
                    </div>
                    <div class="col-md-4">
                        <label for="txt_pws_bd" class="small">Password Base Datos</label>
                        <input type="text" class="form-control form-control-sm bg-input" id="txt_pws_bd" name="txt_pws_bd" value="<?php echo $obj['password_bd'] ?>" maxlength="20">
                    </div>                    
                    <div class="col-md-3">
                        <label for="sl_estado" class="small">Estado</label>
                        <select class="form-select form-select-sm bg-input" id="sl_estado" name="sl_estado">
                            <?= estados_registros('', $obj['estado']) ?>
                        </select>
                    </div>
                </div>
            </form>
        </div>
    </div>
    <div class="text-center pt-3">
        <button type="button" class="btn btn-primary btn-sm" id="btn_testear">Testear</button>
        <button type="button" class="btn btn-primary btn-sm" id="btn_guardar">Guardar</button>
        <a type="button" class="btn btn-secondary  btn-sm" data-bs-dismiss="modal">Cancelar</a>
    </div>
</div>