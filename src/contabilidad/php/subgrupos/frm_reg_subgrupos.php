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
$sql = "SELECT * FROM far_subgrupos WHERE id_subgrupo=" . $id . " LIMIT 1";
$rs = $cmd->query($sql);
$obj = $rs->fetch();

if (empty($obj)) {
    $n = $rs->columnCount();
    for ($i = 0; $i < $n; $i++):
        $col = $rs->getColumnMeta($i);
        $name = $col['name'];
        $obj[$name] = NULL;
    endfor;
    //Inicializa variable por defecto
    $obj['af_menor_cuantia'] = 0;
    $obj['es_clinico'] = 0;
    $obj['lote_xdef'] = 1;
    $obj['estado'] = 1;
}

?>
<div class="px-0">
    <div class="shadow">
        <div class="card-header py-2 mb-2 text-center" style="background-color: #16a085 !important;">
            <h5 class="mb-0" style="color: white;">REGISRTAR SUBGRUPO</h5>
        </div>
        <div class="px-2">
            <form id="frm_reg_subgrupos">
                <input type="hidden" id="id_subgrupo" name="id_subgrupo" value="<?php echo $id ?>">
                <div class=" row">
                    <div class="col-md-1">
                        <label for="txt_cod_subgrupo" class="small">Código</label>
                        <input type="text" class="form-control form-control-sm bg-input number" id="txt_cod_subgrupo" name="txt_cod_subgrupo" required value="<?php echo $obj['cod_subgrupo'] ?>">
                    </div>
                    <div class="col-md-5">
                        <label for="txt_nom_subgrupo" class="small">Nombre</label>
                        <input type="text" class="form-control form-control-sm bg-input" id="txt_nom_subgrupo" name="txt_nom_subgrupo" required value="<?php echo $obj['nom_subgrupo'] ?>">
                    </div>
                    <div class="col-md-2">
                        <label for="sl_grp_subgrupo" class="small">Grupo</label>
                        <select class="form-select form-select-sm bg-input" id="sl_grp_subgrupo" name="sl_grp_subgrupo" required>
                            <?php grupo_articulo($cmd, '', $obj['id_grupo']) ?>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label for="sl_actfij_mencua" class="small">Activo Fijo Menor Cuantia</label>
                        <select class="form-select form-select-sm bg-input" id="sl_actfij_mencua" name="sl_actfij_mencua">
                            <?php estados_sino('', $obj['af_menor_cuantia']) ?>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="small">Para Uso Asistencial</label>
                        <div class="form-control form-control-sm bg-input" id="rdo_escli_subgrupo">
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="radio" name="rdo_escli_subgrupo" id="rdo_escli_subgrupo_si" value="1" <?php echo $obj['es_clinico'] == 1 ? 'checked' : '' ?>>
                                <label class="form-check-label small" for="rdo_escli_subgrupo_si">SI</label>
                            </div>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="radio" name="rdo_escli_subgrupo" id="rdo_escli_subgrupo_no" value="0" <?php echo $obj['es_clinico'] == 0 ? 'checked' : '' ?>>
                                <label class="form-check-label small" for="rdo_escli_subgrupo_no">NO</label>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <label for="sl_lotexdef" class="small">Lote x Defecto</label>
                        <select class="form-select form-select-sm bg-input" id="sl_lotexdef" name="sl_lotexdef">
                            <?php estados_sino('', $obj['lote_xdef']) ?>
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

            <!--Tabs para CUENTAS-->
            <div class="p-3">
                <nav>
                    <div class="nav nav-tabs small" id="nav-tab" role="tablist">
                        <button class="nav-link active small" id="nav_lista_cta_cs-tab" data-bs-toggle="tab" data-bs-target="#nav_lista_cta_cs" type="button" role="tab" aria-controls="nav_lista_cta_cs" aria-selected="true">CUENTA CONTABLE - INVENTARIO</button>
                        <button class="nav-link small" id="nav_lista_cta_af-tab" data-bs-toggle="tab" data-bs-target="#nav_lista_cta_af" type="button" role="tab" aria-controls="nav_lista_cta_af" aria-selected="false">CUENTA CONTABLE - ACTIVO FIJO</button>
                    </div>
                </nav>

                <div class="tab-content pt-2" id="nav-tabContent">
                    <!--Cuentas de Articulos de Consumo-->
                    <div class="tab-pane fade show active" id="nav_lista_cta_cs" role="tabpanel" aria-labelledby="nav_lista_cta_cs-tab">
                        <table id="tb_cuentas_cs" class="table table-striped table-bordered table-sm nowrap table-hover shadow align-middle" style="width:100%; font-size:80%">
                            <thead class="text-center">
                                <tr>
                                    <th class="bg-sofia">ID</th>
                                    <th class="bg-sofia">CUENTA CONTABLE</th>
                                    <th class="bg-sofia">FECHA INICIO DE VIGENCIA</th>
                                    <th class="bg-sofia">CUENTA VIGENTE</th>
                                    <th class="bg-sofia">ESTADO</th>
                                    <th class="bg-sofia">ACCIONES</th>
                                </tr>
                            </thead>
                            <tbody class="text-start"></tbody>
                        </table>
                    </div>

                    <!--Cuentas de Articulos de Activos Fijos-->
                    <div class="tab-pane fade" id="nav_lista_cta_af" role="tabpanel" aria-labelledby="nav_lista_cta_af-tab">
                        <table id="tb_cuentas_af" class="table table-striped table-bordered table-sm nowrap table-hover shadow align-middle" style="width:100%; font-size:80%">
                            <thead class="text-center">
                                <tr>
                                    <th rowspan="2" class="bg-sofia align-middle">ID</th>
                                    <th colspan="3" class="bg-sofia">CUENTAS CONTABLES</th>
                                    <th rowspan="2" class="bg-sofia align-middle">FECHA INICIO DE VIGENCIA</th>
                                    <th rowspan="2" class="bg-sofia align-middle">CUENTA VIGENTE</th>
                                    <th rowspan="2" class="bg-sofia align-middle">ESTADO</th>
                                    <th rowspan="2" class="bg-sofia align-middle">ACCIONES</th>
                                </tr>
                                <tr>
                                    <th class="bg-sofia">ACTIVO</th>
                                    <th class="bg-sofia">DEPRECIACIÓN</th>
                                    <th class="bg-sofia">GASTO DEPRECIACIÓN</th>
                                </tr>
                            </thead>
                            <tbody class="text-start"></tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="text-center pt-3">
        <button type="button" class="btn btn-primary btn-sm" id="btn_guardar">Guardar</button>
        <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancelar</button>
    </div>
</div>

<script type="text/javascript" src="../../js/subgrupos/subgrupos_reg.js?v=<?php echo date('YmdHis') ?>"></script>