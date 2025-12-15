<?php

use Config\Clases\Plantilla;

session_start();
if (!isset($_SESSION['user'])) {
    header("Location: ../../../index.php");
    exit();
}
include_once '../../../config/autoloader.php';

$cmd = \Config\Clases\Conexion::getConexion();

$id_tercero = isset($_POST['idt']) ? $_POST['idt'] : -1;
$otro_form = isset($_POST['otro_form']) ? $_POST['otro_form'] : 0;
$idcdp = isset($_POST['idcdp']) ? $_POST['idcdp'] : 0;

// se vuelve a consultar los datos del tercero con el id que viene del boton
//------------------------------------
$sql = "SELECT tb_terceros.id_tercero_api,tb_terceros.nom_tercero
        FROM tb_terceros 
        WHERE id_tercero_api= $id_tercero LIMIT 1";
$rs = $cmd->query($sql);
$obj = $rs->fetch();
if (empty($obj)) {
    $obj = ['id_tercero_api' => '', 'nom_tercero' => ''];
}
//---------------------------------------------------
?>
<div class="px-0">
    <div class="shadow">
        <div class="card-header p-2 text-center" style="background-color: #16a085 !important;">
            <h5 class="mb-0" style="color: white;">HISTORIAL MOVIMIENTOS POR TERCERO</h5>
        </div>
        <div class="px-2">
            <form id="frm_historialtercero">
                <input type="hidden" id="id_tercero" name="id_tercero" value="<?php echo $id_tercero ?>">
                <input type="hidden" id="id_cdp" name="id_cdp" value="<?php echo $idcdp ?>">
                <div class="row mb-3">
                    <div class="col-md-4">
                        <label for="txt_tercero_filtro" class="small">Tercero</label>
                        <input type="text" class="filtro form-control form-control-sm bg-input" id="txt_tercero_filtro" name="txt_tercero_filtro" readonly="true" value="<?php echo $obj['nom_tercero'] ?>">
                        <input type="hidden" id="id_txt_tercero" name="id_txt_tercero" class="form-control form-control-sm bg-input" value="<?php echo $id_tercero ?>">
                    </div>
                    <div class="col-md-3">
                        <label for="txt_nrodisponibilidad_filtro" class="small">Nro Disponibilidad</label>
                        <input type="text" class="filtro form-control form-control-sm bg-input" id="txt_nrodisponibilidad_filtro" placeholder="Nro disponibilidad">
                    </div>
                    <div class="col-md-2">
                        <label for="txt_fecini_filtro" class="small">Fecha inicial</label>
                        <input type="date" class="form-control form-control-sm bg-input" id="txt_fecini_filtro" name="txt_fecini_filtro" placeholder="Fecha Inicial" value="<?php echo $_SESSION['vigencia'] ?>-01-01">
                    </div>
                    <div class="col-md-2">
                        <label for="txt_fecfin_filtro" class="small">Fecha final</label>
                        <input type="date" class="form-control form-control-sm bg-input" id="txt_fecfin_filtro" name="txt_fecfin_filtro" placeholder="Fecha final" value="<?php echo $_SESSION['vigencia'] ?>-12-31">
                    </div>
                    <div class="col-md-1">
                        <label for="btn_buscar_filtro" class="small">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</label>
                        <a type="button" id="btn_buscar_filtro" class="btn btn-outline-success btn-sm" title="Filtrar">
                            <span class="fas fa-search fa-lg" aria-hidden="true"></span>
                        </a>
                    </div>
                </div>
                <div class=" w-100 text-start">
                    <table id="tb_cdps" class="table table-striped table-bordered table-sm nowrap table-hover align-middle shadow w-100" style="width:100%; font-size:80%">
                        <thead>
                            <tr class="text-center align-middle">
                                <th class="bg-sofia">Id CDP</th>
                                <th class="bg-sofia">Documento</th>
                                <th class="bg-sofia">Fecha</th>
                                <th class="bg-sofia" style="min-width: 70%;">Objeto</th>
                                <th class="bg-sofia">Valor CDP</th>
                                <th class="bg-sofia">Saldo</th>
                                <th class="bg-sofia">Acciones</th>
                            </tr>
                        </thead>
                        <tbody class="text-start align-middle" id="body_tb_cdps"></tbody>
                    </table>
                </div>
            </form>

            <!--Tabs-->
            <div class="p-3">
                <nav>
                    <div class="nav nav-tabs small" id="nav-tab" role="tablist">
                        <a class="nav-item nav-link active small" id="nav_lista_contratacion-tab" data-bs-toggle="tab" href="#nav_lista_contratacion" role="tab" aria-controls="nav_lista_contratacion" aria-selected="true">CONTRATACIÓN</a>
                        <a class="nav-item nav-link small" id="nav_lista_regpresupuestal-tab" data-bs-toggle="tab" href="#nav_lista_regpresupuestal" role="tab" aria-controls="nav_lista_regpresupuestal" aria-selected="false">REGISTRO PRESUPUESTAL</a>
                        <a class="nav-item nav-link small" id="nav_lista_obligaciones-tab" data-bs-toggle="tab" href="#nav_lista_obligaciones" role="tab" aria-controls="nav_lista_obligaciones" aria-selected="false">OBLIGACIONES</a>
                        <a class="nav-item nav-link small" id="nav_lista_pagos-tab" data-bs-toggle="tab" href="#nav_lista_pagos" role="tab" aria-controls="nav_lista_pagos" aria-selected="false">PAGOS</a>
                    </div>
                </nav>

                <div class="tab-content pt-2 w-100 text-start" id="nav-tabContent">
                    <!--Lista de contratacion-->
                    <div class="tab-pane fade show active" id="nav_lista_contratacion" role="tabpanel" aria-labelledby="nav_lista_contratacion-tab">
                        <table id="tb_contratos" class="table table-striped table-bordered table-sm nowrap table-hover shadow" style="width:100%; font-size:80%">
                            <thead>
                                <tr class="text-center align-middle">
                                    <th class="bg-sofia">No Contrato</th>
                                    <th class="bg-sofia">Fecha Ini</th>
                                    <th class="bg-sofia">Fecha fin</th>
                                    <th class="bg-sofia">Valor contrato</th>
                                    <th class="bg-sofia">Adiciones</th>
                                    <th class="bg-sofia">Reducciones</th>
                                    <th class="bg-sofia">Estado</th>
                                </tr>
                            </thead>
                            <tbody class="text-start align-middle"></tbody>
                        </table>
                    </div>

                    <!--Lista de reg presupuestal-->
                    <div class="tab-pane fade" id="nav_lista_regpresupuestal" role="tabpanel" aria-labelledby="nav_lista_regpresupuestal-tab">
                        <table id="tb_reg_presupuestal" class="table table-striped table-bordered table-sm nowrap table-hover shadow" style="width:100%; font-size:80%">
                            <thead>
                                <tr class="text-center align-middle">
                                    <th class="bg-sofia">ID CRP</th>
                                    <th class="bg-sofia">No Registro</th>
                                    <th class="bg-sofia">Fecha</th>
                                    <th class="bg-sofia">Tipo</th>
                                    <th class="bg-sofia">No Contrato</th>
                                    <th class="bg-sofia">Valor registro</th>
                                    <th class="bg-sofia">Saldo</th>
                                    <th class="bg-sofia">Estado</th>
                                    <th class="bg-sofia">Acciones</th>
                                </tr>
                            </thead>
                            <tbody class="text-start align-middle" id="body_tb_reg_presupuestal"></tbody>
                        </table>
                    </div>

                    <!--Lista de obligaciones-->
                    <div class="tab-pane fade" id="nav_lista_obligaciones" role="tabpanel" aria-labelledby="nav_lista_obligaciones-tab">
                        <table id="tb_obligaciones" class="table table-striped table-bordered table-sm nowrap table-hover shadow" style="width:100%; font-size:80%">
                            <thead>
                                <tr class="text-center align-middle">
                                    <th class="bg-sofia">No causacion</th>
                                    <th class="bg-sofia">Fecha</th>
                                    <th class="bg-sofia">Soporte</th>
                                    <th class="bg-sofia">Valor causado</th>
                                    <th class="bg-sofia">Descuentos</th>
                                    <th class="bg-sofia">Neto</th>
                                    <th class="bg-sofia">Estado</th>
                                </tr>
                            </thead>
                            <tbody class="text-start align-middle"></tbody>
                        </table>
                    </div>

                    <!--Lista de pagos-->
                    <div class="tab-pane fade" id="nav_lista_pagos" role="tabpanel" aria-labelledby="nav_lista_pagos-tab">
                        <table id="tb_pagos" class="table table-striped table-bordered table-sm nowrap table-hover shadow" style="width:100%; font-size:80%">
                            <thead>
                                <tr class="text-center align-middle">
                                    <th class="bg-sofia">Consecutivo</th>
                                    <th class="bg-sofia">Fecha</th>
                                    <th class="bg-sofia" style="min-width: 70%;">Detalle</th>
                                    <th class="bg-sofia">Valor pagado</th>
                                </tr>
                            </thead>
                            <tbody class="text-start align-middle"></tbody>
                        </table>
                    </div>
                </div>
            </div>

        </div>
    </div>
    <div class="text-center pt-3">
        <button type="button" class="btn btn-primary btn-sm" id="btn_imprimir">Imprimir</button>
        <a type="button" class="btn btn-secondary  btn-sm" data-bs-dismiss="modal">Cancelar</a>
    </div>
</div>

<?php
$host = Plantilla::getHost();
if ($otro_form == 0) {
    echo '<script type="text/javascript" src= "' . $host . '/src/terceros/js/historialtercero/historialtercero.js?v=' . date('YmdHis') . '"></script>';
    echo '<script type="text/javascript" src="' . $host . '/src/terceros/js/historialtercero/historialtercero_reg.js?v=' . date('YmdHis') . '"></script>';
}

//----1 lo llamo desde presupuesto de gastos
if ($otro_form == 1) {
    echo '<script type="text/javascript" src= "' . $host . '/src/terceros/js/historialtercero/historialtercero.js?v=' . date('YmdHis') . '"></script>';
    echo '<script type="text/javascript" src="' . $host . '/src/terceros/js/historialtercero/historialtercero_reg.js?v=' . date('YmdHis') . '"></script>';
}
