<?php
session_start();
if (!isset($_SESSION['user'])) {
    header("Location: ../../../index.php");
    exit();
}
include '../../../../config/autoloader.php';
include '../common/cargar_combos.php';

$cmd = \Config\Clases\Conexion::getConexion();

$id = isset($_POST['id']) ? $_POST['id'] : -1;
$sql = "SELECT far_medicamentos.*,
            IF(id_uni=0,unidad,CONCAT(unidad,' (',descripcion,')')) AS unidad_medida
        FROM far_medicamentos 
        LEFT JOIN far_med_unidad ON (far_med_unidad.id_uni=far_medicamentos.id_unidadmedida_2)
        WHERE id_med=" . $id . " LIMIT 1";
$rs = $cmd->query($sql);
$obj = $rs->fetch();

if (empty($obj)) {
    $n = $rs->columnCount();
    for ($i = 0; $i < $n; $i++) :
        $col = $rs->getColumnMeta($i);
        $name = $col['name'];
        $obj[$name] = NULL;
    endfor;
    //Inicializa variable por defecto
    $obj['es_clinico'] = 0;
    $obj['tipo_riesgo'] = 0;
    $obj['estado'] = 1;
}
$imprimir = $id != -1 ? '' : 'disabled="disabled"';

?>

<div class="px-0">
    <div class="shadow">
        <div class="card-header py-2 text-center bg-sofia">
            <h5 class="text-white mb-0">REGISTRAR ARTICULO</h5>
        </div>
        <div class="p-2">

            <!--Formulario de registro de Articulos-->
            <form id="frm_reg_articulos">
                <input type="hidden" id="id_articulo" name="id_articulo" value="<?php echo $id ?>">
                <div class=" row">
                    <div class="col-md-2">
                        <label for="txt_cod_art" class="small">Código</label>
                        <input type="text" class="form-control form-control-sm bg-input valcode" id="txt_cod_art" name="txt_cod_art" required value="<?php echo $obj['cod_medicamento'] ?>">
                    </div>
                    <div class="col-md-6">
                        <label for="txt_nom_art" class="small">Nombre</label>
                        <input type="text" class="form-control form-control-sm bg-input" id="txt_nom_art" name="txt_nom_art" required value="<?php echo $obj['nom_medicamento'] ?>">
                    </div>
                    <div class="col-md-2">
                        <label for="sl_subgrp_art" class="small">Subgrupo</label>
                        <select class="form-select form-select-sm bg-input" id="sl_subgrp_art" name="sl_subgrp_art" required>
                            <?php subgrupo_articulo($cmd, '', $obj['id_subgrupo']) ?>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label for="txt_vidautil_art" class="small">Vida Últil</label>
                        <input type="text" class="form-control form-control-sm bg-input numberint" id="txt_vidautil_art" name="txt_vidautil_art" value="<?php echo $obj['vida_util'] ?>">
                    </div>
                    <div class="col-md-2">
                        <label for="txt_topmin_art" class="small">Tope Mínimo</label>
                        <input type="text" class="form-control form-control-sm bg-input numberint" id="txt_topmin_art" name="txt_topmin_art" value="<?php echo $obj['top_min'] ?>">
                    </div>
                    <div class="col-md-2">
                        <label for="txt_topmax_art" class="small">Tope Máximo</label>
                        <input type="text" class="form-control form-control-sm bg-input numberint" id="txt_topmax_art" name="txt_topmax_art" value="<?php echo $obj['top_max'] ?>">
                    </div>
                    <div class="col-md-4">
                        <label for="txt_unimed_art" class="small">Unidad Medida</label>
                        <input type="text" class="form-control form-control-sm bg-input" id="txt_unimed_art" required value="<?php echo $obj['unidad_medida'] ?>">
                        <input type="hidden" id="id_txt_unimed_art" name="id_txt_unimed_art" value="<?php echo $obj['id_unidadmedida_2'] ?>">
                    </div>
                    <div class="col-md-2">
                        <label for="sl_riesgo_art" class="small">Clasificación de Riesgo</label>
                        <select class="form-select form-select-sm bg-input" id="sl_riesgo_art" name="sl_riesgo_art" required>
                            <?php clasificacion_riesgo('', $obj['tipo_riesgo']) ?>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="small">Para Uso Asistencial</label>
                        <div class="form-control form-control-sm bg-input" id="rdo_escli_art">
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="radio" name="rdo_escli_art" id="rdo_escli_art_si" value="1" <?php echo $obj['es_clinico'] == 1 ? 'checked' : '' ?>>
                                <label class="form-check-label small" for="rdo_escli_art_si">SI</label>
                            </div>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="radio" name="rdo_escli_art" id="rdo_escli_art_no" value="0" <?php echo $obj['es_clinico'] == 0 ? 'checked' : '' ?>>
                                <label class="form-check-label small" for="rdo_escli_art_no">NO</label>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <label for="sl_medins_art" class="small">Med/Ins Uso Clínico</label>
                        <select class="form-select form-select-sm bg-input" id="sl_medins_art" name="sl_medins_art">
                            <?php tipo_Medicamento_insumo($cmd, $obj['id_tip_medicamento']) ?>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label for="sl_estado" class="small">Estado</label>
                        <select class="form-select form-select-sm bg-input" id="sl_estado" name="sl_estado">
                            <?php estados_registros('', $obj['estado']) ?>
                        </select>
                    </div>
                </div>
            </form>

            <!--Tabs para CUMS y Lotes-->
            <div class="p-3">
                <nav>
                    <div class="nav nav-tabs small" id="nav-tab" role="tablist">
                        <button class="nav-link active small" id="nav_lista_cums-tab" data-bs-toggle="tab" data-bs-target="#nav_lista_cums" type="button" role="tab" aria-controls="nav_lista_cums" aria-selected="true">CUMS/Expedientes</button>
                        <button class="nav-link small" id="nav_lista_lotes-tab" data-bs-toggle="tab" data-bs-target="#nav_lista_lotes" type="button" role="tab" aria-controls="nav_lista_lotes" aria-selected="false">LOTES</button>
                    </div>
                </nav>

                <div class="tab-content pt-2" id="nav-tabContent">
                    <!--Lista de CUMS-->
                    <div class="tab-pane fade show active" id="nav_lista_cums" role="tabpanel" aria-labelledby="nav_lista_cums-tab">
                        <table id="tb_articulos_cums" class="table table-striped table-bordered table-sm nowrap table-hover shadow align-middle w-100" style="font-size:80%">
                            <thead>
                                <tr class="text-center">
                                    <th class="bg-sofia">Id</th>
                                    <th class="bg-sofia">CUM/</br>Expediente</th>
                                    <th class="bg-sofia">IUM</th>
                                    <th class="bg-sofia">Laboratorio</th>
                                    <th class="bg-sofia">Registro Invima</th>
                                    <th class="bg-sofia">Fec. Vence</br> Invima</th>
                                    <th class="bg-sofia">Estado Invima</th>
                                    <th class="bg-sofia">Presentación Comercial</th>
                                    <th class="bg-sofia">Estado</th>
                                    <th class="bg-sofia">Acciones</th>
                                </tr>
                            </thead>
                            <tbody class="text-start"></tbody>
                        </table>
                    </div>

                    <!--Lista de LOTES-->
                    <div class="tab-pane fade" id="nav_lista_lotes" role="tabpanel" aria-labelledby="nav_lista_lotes-tab">
                        <table id="tb_articulos_lotes" class="table table-striped table-bordered table-sm nowrap table-hover shadow align-middle w-100" style="font-size:80%">
                            <thead>
                                <tr class="text-center">
                                    <th class="bg-sofia">Id</th>
                                    <th class="bg-sofia">Lote</th>
                                    <th class="bg-sofia">Principal</th>
                                    <th class="bg-sofia">Fecha<br>Vencimiento</th>
                                    <th class="bg-sofia">Reg. Invima</th>
                                    <th class="bg-sofia">Marca</th>
                                    <th class="bg-sofia">Presentación del Lote</th>
                                    <th class="bg-sofia">Unidades<br>en UMPL</th>
                                    <th class="bg-sofia">Existencia</th>
                                    <th class="bg-sofia">CUM</th>
                                    <th class="bg-sofia">Bodega</th>
                                    <th class="bg-sofia">Estado</th>
                                    <th class="bg-sofia">Acciones</th>
                                </tr>
                            </thead>
                            <tbody class="text-start"></tbody>
                        </table>
                        <label class="block text-start filtro_lotes"><input type="checkbox" id="chk_lotes_activos" checked="checked"/>&nbsp;Lotes Activos </label>
                        &nbsp;&nbsp;&nbsp;&nbsp;
                        <label class="block text-start filtro_lotes"><input type="checkbox" id="chk_lotes_con_exi" />&nbsp;Lotes con Existencia </label>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="text-center pt-3">
        <button type="button" class="btn btn-primary btn-sm" id="btn_guardar">Guardar</button>
        <button type="button" class="btn btn-primary btn-sm" id="btn_imprimir" <?php echo $imprimir ?>>Imprimir</button>
        <a type="button" class="btn btn-secondary  btn-sm" data-bs-dismiss="modal">Cancelar</a>
    </div>
</div>

<script type="text/javascript" src="../../js/articulos/articulos_reg.js?v=<?php echo date('YmdHis') ?>"></script>