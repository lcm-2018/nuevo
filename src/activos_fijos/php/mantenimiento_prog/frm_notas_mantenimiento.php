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

$id_md = isset($_POST['id_md']) ? $_POST['id_md'] : -1;

$sql = "SELECT HV.placa,HV.num_serial,FM.nom_medicamento AS nom_articulo,HV.des_activo
        FROM acf_mantenimiento_detalle AS MD
        INNER JOIN acf_hojavida AS HV ON (HV.id_activo_fijo=MD.id_activo_fijo)
        INNER JOIN far_medicamentos FM ON (FM.id_med=HV.id_articulo)
        WHERE MD.id_mant_detalle=" . $id_md . " LIMIT 1";
$rs = $cmd->query($sql);
$obj = $rs->fetch();

?>

<div class="px-0">
    <div class="shadow">
        <div class="card-header py-2 text-center bg-sofia">
            <h5 class="text-white mb-0">NOTAS DE MANTENIMIENTO</h5>
        </div>
        <div class="p-3">
            <form id="frm_reg_notas">
                <input type="hidden" id="id_mant_detalle" name="id_mant_detalle" value="<?php echo $id_md ?>">
                <div class="row mb-2">
                    <div class="col-md-2">
                        <label class="small">Placa</label>
                        <input type="text" class="form-control form-control-sm bg-input" id="txt_placa_nt" value="<?php echo $obj['placa'] ?>" readonly="readonly">
                    </div>
                    <div class="col-md-3">
                        <label class="small">Articulo</label>
                        <input type="text" class="form-control form-control-sm bg-input" id="txt_nom_articulo_nt" value="<?php echo $obj['nom_articulo'] ?> " readonly="readonly">
                    </div>
                    <div class="col-md-5">
                        <label class="small">Nombre del Activo Fijo</label>
                        <input type="text" class="form-control form-control-sm bg-input" id="txt_nom_activo_nt" value="<?php echo $obj['des_activo'] ?> " readonly="readonly">
                    </div>
                    <div class="col-md-2">
                        <label class="small">No. Serial</label>
                        <input type="text" class="form-control form-control-sm bg-input" id="txt_serial_nt" value="<?php echo $obj['num_serial'] ?>" readonly="readonly">
                    </div>
                </div>
            </form>
            <!--Formulario de registro de Ordenes de Ingreso-->
            <table id="tb_notas_mantenimiento" class="table table-striped table-bordered table-sm nowrap table-hover shadow align-middle w-100" style="font-size:80%">
                <thead>
                    <tr class="text-center">
                        <th class="bg-sofia">Id</th>
                        <th class="bg-sofia">Fecha</th>
                        <th class="bg-sofia">Hora</th>
                        <th class="bg-sofia">Observacion</th>
                        <th class="bg-sofia">Archivo Documento</th>
                        <th class="bg-sofia">Acciones</th>
                    </tr>
                </thead>
                <tbody class="text-start"></tbody>
            </table>
        </div>
    </div>
    <div class="text-center pt-3">
        <button type="button" class="btn btn-primary btn-sm" id="btn_imprimir">Imprimir</button>
        <a type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Salir</a>
    </div>
</div>

<script type="text/javascript" src="../../js/mantenimiento_prog/mantenimiento_notas_reg.js?v=<?php echo date('YmdHis') ?>"></script>