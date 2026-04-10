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

$obj['tipo_analitica'] = 1;
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
                    <div class="col-md-12">
                        <label for="txt_titulo_consulta" class="small">Título</label>
                        <input type="text" class="form-control form-control-sm bg-input" id="txt_titulo_consulta" name="txt_titulo_consulta" value="<?php echo $obj['titulo_consulta'] ?>">
                    </div>
                    <div class="col-md-12">
                        <label for="txt_detalle_consulta" class="small">Descripción</label>
                        <textarea class="form-control form-control-sm bg-input" id="txt_detalle_consulta" name="txt_detalle_consulta" rows="3"><?php echo $obj['detalle_consulta'] ?></textarea>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-9">
                        <div class="col-md-12">
                            <label for="txt_consulta_sql" class="small">Consulta SQL</label>
                            <textarea class="form-control form-control-sm bg-input" id="txt_consulta_sql" name="txt_consulta_sql" rows="15"><?php echo $obj['consulta_sql'] ?></textarea>
                        </div>
                        <div class="col-md-12">
                            <label for="txt_consulta_sql_group" class="small">Consulta SQL GROUP</label>
                            <textarea class="form-control form-control-sm bg-input" id="txt_consulta_sql_group" name="txt_consulta_sql_group" rows="5"><?php echo $obj['consulta_sql_group'] ?></textarea>
                        </div>
                    </div>
                    <div class="col-md-3 py-3">
                        <div class="accordion" id="accordionSimple">
                            <!-- Panel 1 -->
                            <div class="accordion-item">
                                <h2 class="accordion-header" id="headingOne">
                                    <button class="accordion-button collapsed" type="button"
                                        data-bs-toggle="collapse"
                                        data-bs-target="#panel1"
                                        aria-expanded="false"
                                        aria-controls="panel1">
                                        DISEÑO
                                    </button>
                                </h2>

                                <div id="panel1" class="accordion-collapse collapse"
                                    data-bs-parent="#accordionSimple">
                                    <div class="accordion-body">
                                        <div class="col-md-12">
                                            <label for="sl_tipo_analitica" class="small">Tipo Analítica</label>
                                            <select class="form-select form-select-sm bg-input" id="sl_tipo_analitica" name="sl_tipo_analitica">
                                                <?= tipo_analitica('', $obj['tipo_analitica']) ?>
                                            </select>
                                        </div>
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

                            <!-- Panel 2 -->
                            <div class="accordion-item">
                                <h2 class="accordion-header" id="headingTwo">
                                    <button class="accordion-button collapsed" type="button"
                                        data-bs-toggle="collapse"
                                        data-bs-target="#panel2"
                                        aria-expanded="false"
                                        aria-controls="panel2">
                                        Panel 2
                                    </button>
                                </h2>

                                <div id="panel2" class="accordion-collapse collapse"
                                    data-bs-parent="#accordionSimple">
                                    <div class="accordion-body">
                                        Contenido del Panel 2
                                    </div>
                                </div>
                            </div>

                            <!-- Panel 3 -->
                            <div class="accordion-item">
                                <h2 class="accordion-header" id="headingThree">
                                    <button class="accordion-button collapsed" type="button"
                                        data-bs-toggle="collapse"
                                        data-bs-target="#panel3"
                                        aria-expanded="false"
                                        aria-controls="panel3">
                                        Panel 3
                                    </button>
                                </h2>

                                <div id="panel3" class="accordion-collapse collapse"
                                    data-bs-parent="#accordionSimple">
                                    <div class="accordion-body">
                                        Contenido del Panel 3
                                    </div>
                                </div>
                            </div>

                            <!-- Panel 4 -->
                            <div class="accordion-item">
                                <h2 class="accordion-header" id="headingFour">
                                    <button class="accordion-button collapsed" type="button"
                                        data-bs-toggle="collapse"
                                        data-bs-target="#panel4"
                                        aria-expanded="false"
                                        aria-controls="panel4">
                                        Panel 4
                                    </button>
                                </h2>

                                <div id="panel4" class="accordion-collapse collapse"
                                    data-bs-parent="#accordionSimple">
                                    <div class="accordion-body">
                                        Contenido del Panel 4
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-12">
                            <label for="sl_estado" class="small">Estado</label>
                            <select class="form-select form-select-sm bg-input" id="sl_estado" name="sl_estado">
                                <?= estados_registros('', $obj['estado']) ?>
                            </select>
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