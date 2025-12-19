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
        <div class="card-header text-center py-2" style="background-color: #16a085 !important;">
            <h5 class="mb-0 text-white">HISTORICO DE PAGOS PENDIENTES A TERCEROS</h5>
        </div>
        <div class="p-2">
            <form id="frm_historicopagos">
                <div class=" row mb-2">
                    <div class="col-md-2">
                        <span class="small">Fecha</span>
                    </div>
                    <div class="col-md-4">
                        <input type="date" class="form-control form-control-sm bg-input" id="txt_fecha" name="txt_fecha" placeholder="Fecha" value="<?php echo date('Y-m-d'); ?>">
                    </div>
                    <div class="col-md-2">
                        <a type="button" id="btn_buscar" class="btn btn-outline-success btn-sm" title="Buscar">
                            <span class="fas fa-search fa-lg" aria-hidden="true"></span>
                        </a>
                    </div>
                </div>

                <div class=" w-100 text-start">
                    <table id="tb_terceros" class="table table-striped table-bordered table-sm nowrap table-hover shadow w-100" style="font-size:80%">
                        <thead>
                            <tr class="text-center bg-sofia">
                                <th class="bg-sofia" colspan="5">&nbsp;</th>
                                <th class="bg-sofia" colspan="7">Antigüedad (dias)</th>
                            </tr>
                            <tr class="text-center">
                                <!--<th>Id tercero</th>-->
                                <th class="bg-sofia">ID Manu</th>
                                <th class="bg-sofia">Documento/Nit</th>
                                <th class="bg-sofia" style="max-width: 40%;">Tercero</th>
                                <th class="bg-sofia">Fecha credito</th>
                                <th class="bg-sofia">Credito</th>
                                <th class="bg-sofia">
                                    < 30</th>
                                <th class="bg-sofia">30 a 60</th>
                                <th class="bg-sofia">60 a 90</th>
                                <th class="bg-sofia">90 a 180</th>
                                <th class="bg-sofia">180 a 360</th>
                                <th class="bg-sofia">> 360</th>
                                <th class="bg-sofia">Saldo</th>
                            </tr>
                        </thead>
                        <tbody class="text-start" id="body_tb_terceros"></tbody>
                    </table>
                </div>
            </form>
        </div>
    </div>
    <div class="text-center pt-3">
        <!--<button type="button" class="btn btn-primary btn-sm" id="btn_imprimir">Imprimir</button>-->
        <a type="button" class="btn btn-secondary  btn-sm" data-bs-dismiss="modal">Cancelar</a>
    </div>
</div>


<script type="text/javascript" src="js/historico_pagos_pendientes/historico_pagos_pendientes.js?v=<?php echo date('YmdHis') ?>"></script>