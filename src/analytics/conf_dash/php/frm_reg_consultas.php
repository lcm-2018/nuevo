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
$obj = ($id !== -1) ? $model->getById($id) : $model->getById(0);

$obj['tipo_bdatos'] = 1;
$obj['tipo_informe'] = 1;
$obj['tipo_consulta'] = 1;
$obj['tipo_acceso'] = 1;
$obj['estado'] = 1;

?>
<div class="px-0">
    <div class="shadow">
        <div class="card-header py-2 text-center" style="background-color: #16a085 !important;">
            <h5 class="text-white mb-0">REGISTRAR CONSULTA ANALÍTICA</h5>
        </div>
        <div class="p-3">
            <form id="frm_reg_consultas">
                <input type="hidden" id="id_consulta" name="id_consulta" value="<?php echo $id ?>">
                <div class="row">
                    <div class="col-md-10">
                        <label for="txt_titulo_consulta" class="small">Título</label>
                        <input type="text" class="form-control form-control-sm bg-input" id="txt_titulo_consulta" name="txt_titulo_consulta" value="<?php echo $obj['titulo_consulta'] ?>">
                    </div>
                    <div class="col-md-2">
                        <label for="sl_estado" class="small">Estado</label>
                        <select class="form-select form-select-sm bg-input" id="sl_estado" name="sl_estado">
                            <?= estados_registros('', $obj['estado']) ?>
                        </select>
                    </div>
                    <div class="col-md-12">
                        <label for="txt_detalle_consulta" class="small">Descripción</label>
                        <textarea class="form-control form-control-sm bg-input" id="txt_detalle_consulta" name="txt_detalle_consulta" rows="1"><?php echo $obj['detalle_consulta'] ?></textarea>
                    </div>
                </div>

                <div class="p-3">
                    <nav>
                        <div class="nav nav-tabs small" id="nav-tab" role="tablist">
                            <button class="nav-link active small" id="nav_consulta-tab" data-bs-toggle="tab" data-bs-target="#nav_consulta" type="button" role="tab" aria-controls="nav_consulta" aria-selected="true">Consulta</button>
                            <button class="nav-link small" id="nav_parametros-tab" data-bs-toggle="tab" data-bs-target="#nav_parametros" type="button" role="tab" aria-controls="nav_parametros" aria-selected="false">Parámetros</button>
                        </div>
                    </nav>

                    <div class="tab-content pt-2" id="nav-tabContent">
                        <!--Consulta-->
                        <div class="tab-pane fade show active" id="nav_consulta" role="tabpanel" aria-labelledby="nav_consulta-tab">
                            <div class="col-md-12">
                                <label for="txt_consulta_sql" class="small">Consulta SQL</label>
                                <textarea class="form-control form-control-sm bg-input" id="txt_consulta_sql" name="txt_consulta_sql" rows="15"><?php echo $obj['consulta_sql'] ?></textarea>
                            </div>
                            <div class="col-md-12">
                                <label for="txt_consulta_sql_group" class="small">Consulta SQL GROUP</label>
                                <textarea class="form-control form-control-sm bg-input" id="txt_consulta_sql_group" name="txt_consulta_sql_group" rows="5"><?php echo $obj['consulta_sql_group'] ?></textarea>
                            </div>
                        </div>

                        <!--Parámetros-->
                        <div class="tab-pane fade" id="nav_parametros" role="tabpanel" aria-labelledby="nav_parametros-tab">
                            <div class="col-md-12">
                                <label for="sl_tipo_bdatos" class="small">Tipo Base Datos</label>
                                <select class="form-select form-select-sm bg-input" id="sl_tipo_bdatos" name="sl_tipo_bdatos">
                                    <?= tipo_bdatos('', $obj['tipo_bdatos']) ?>
                                </select>
                            </div>
                            <div class="col-md-12">
                                <label for="sl_tipo_informe" class="small">Tipo Informe</label>
                                <select class="form-select form-select-sm bg-input" id="sl_tipo_informe" name="sl_tipo_informe">
                                    <?= tipo_informe('', $obj['tipo_informe']) ?>
                                </select>
                            </div>
                            <div class="col-md-12">
                                <label for="sl_tipo_consulta" class="small">Tipo Consulta</label>
                                <select class="form-select form-select-sm bg-input" id="sl_tipo_consulta" name="sl_tipo_consulta">
                                    <?= tipo_consulta('', $obj['tipo_consulta']) ?>
                                </select>
                            </div>
                            <div class="col-md-12">
                                <label for="sl_tipo_acceso" class="small">Tipo Acceso</label>
                                <select class="form-select form-select-sm bg-input" id="sl_tipo_acceso" name="sl_tipo_acceso">
                                    <?= tipo_acceso('', $obj['tipo_acceso']) ?>
                                </select>
                            </div>
                        </div>
                    </div>        
                </div>
            </form>
        </div>
    </div>
    <div class="text-center pt-3">
        <button type="button" class="btn btn-primary btn-sm" id="btn_guardar">Guardar</button>
        <a type="button" class="btn btn-secondary  btn-sm" data-bs-dismiss="modal">Cancelar</a>
    </div>
</div>