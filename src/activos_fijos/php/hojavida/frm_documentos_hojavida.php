<?php
session_start();
if (!isset($_SESSION['user'])) {
    header("Location: ../../../index.php");
    exit();
}
include '../../../../config/autoloader.php';
include '../common/cargar_combos.php';
include '../common/funciones_generales.php';

$cmd = \Config\Clases\Conexion::getConexion();

$id_hv = isset($_POST['id_hv']) ? $_POST['id_hv'] : -1;

$sql = "SELECT HV.placa,
            HV.num_serial,
            HV.id_articulo,
            FM.nom_medicamento nom_articulo
        FROM acf_hojavida HV
        INNER JOIN far_medicamentos FM ON (FM.id_med = HV.id_articulo)
        WHERE HV.id_activo_fijo=" . $id_hv . " LIMIT 1";
$rs = $cmd->query($sql);
$obj = $rs->fetch();

?>

<div class="px-0">
    <div class="shadow">
        <div class="card-header py-2 text-center bg-sofia">
            <h5 class="text-white mb-0">DOCUMENTOS DE ACTIVO FIJO</h5>
        </div>
        <div class="p-3">
            <form id="frm_reg_componentes">
                <input type="hidden" id="id_hv" name="id_hv" value="<?php echo $id_hv ?>">
                <div class="row mb-2">
                    <div class="col-md-3">
                        <label class="small">Placa</label>
                        <input type="text" class="form-control form-control-sm bg-input" id="placa_componente" value="<?php echo $obj['placa'] ?>" readonly="readonly">
                    </div>
                    <div class="col-md-7">
                        <label class="small">Articulo</label>
                        <input type="text" class="form-control form-control-sm bg-input" id="nom_articulo_componente" value="<?php echo $obj['nom_articulo'] ?> " readonly="readonly">
                    </div>
                    <div class="col-md-2">
                        <label class="small">No. Serial</label>
                        <input type="text" class="form-control form-control-sm bg-input" id="serial_componente" value="<?php echo $obj['num_serial'] ?>" readonly="readonly">
                    </div>
                </div>
            </form>
            <!--Formulario de registro de Ordenes de Ingreso-->
            <table id="tb_documentos_hojavida" class="table table-striped table-bordered table-sm nowrap table-hover shadow align-middle w-100" style="font-size:80%">
                <thead>
                    <tr class="text-center">
                        <th class="bg-sofia">Id</th>
                        <th class="bg-sofia">Tipo</th>
                        <th class="bg-sofia">Descripción</th>
                        <th class="bg-sofia">Archivo Documento</th>
                        <th class="bg-sofia">Acciones</th>
                    </tr>
                </thead>
                <tbody class="text-start"></tbody>
            </table>
        </div>
    </div>
    <div class="text-center pt-3">
        <button type="button" class="btn btn-primary btn-sm" id="btn_imprimir_documentos">Imprimir</button>
        <a type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Salir</a>
    </div>
</div>

<script type="text/javascript" src="../../js/hojavida/hojavida_documento_reg.js?v=<?php echo date('YmdHis') ?>"></script>