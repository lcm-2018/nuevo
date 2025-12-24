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

$id = isset($_POST['id']) ? $_POST['id'] : -1;
$sql = "SELECT acf_orden_ingreso_detalle.id_articulo,
            far_medicamentos.cod_medicamento,
            far_medicamentos.nom_medicamento,
            acf_orden_ingreso_detalle.cantidad,
            acf_orden_ingreso_detalle.valor,
            acf_orden_ingreso_detalle.observacion
        FROM acf_orden_ingreso_detalle 
        INNER JOIN far_medicamentos ON (far_medicamentos.id_med = acf_orden_ingreso_detalle.id_articulo)
        WHERE acf_orden_ingreso_detalle.id_ing_detalle=" . $id . " LIMIT 1";
$rs = $cmd->query($sql);
$obj = $rs->fetch();

?>

<div class="px-0">
    <div class="shadow">
        <div class="card-header py-2 text-center bg-sofia">
            <h5 class="text-white mb-0">REGISTRAR DATOS BÁSICOS DE ACTIVOS FIJOS</h5>
        </div>
        <div class="p-2">
            <!--Formulario de registro de Ordenes de Ingreso-->
            <form id="acf_reg_orden_ingreso">
                <input type="hidden" id="id_ing_detalle" name="id_ing_detalle" value="<?php echo $id ?>">
                <input type="hidden" id="id_articulo" name="id_articulo" value="<?php echo $obj['id_articulo'] ?>">
                <div class="row mb-2">
                    <div class="col-md-2">
                        <label for="txt_cod_med" class="small">Codigo</label>
                        <input type="text" class="form-control form-control-sm bg-input" id="txt_cod_med" class="small" value="<?php echo $obj['cod_medicamento'] ?>" readonly="readonly">
                    </div>
                    <div class="col-md-4">
                        <label for="txt_desc_med" class="small">Descripción</label>
                        <input type="text" class="form-control form-control-sm bg-input" id="txt_desc_med" class="small" value="<?php echo $obj['nom_medicamento'] ?>" readonly="readonly">
                    </div>
                    <div class="col-md-1">
                        <label for="txt_cdd_med" class="small">Cantidad</label>
                        <input type="text" class="form-control form-control-sm bg-input" id="txt_cdd_med" class="small" value="<?php echo $obj['cantidad'] ?>" readonly="readonly">
                    </div>
                    <div class="col-md-2">
                        <label for="txt_vrunit_med" class="small">Vr. Unitario</label>
                        <input type="text" class="form-control form-control-sm bg-input" id="txt_vrunit_med" class="small" value="<?php echo $obj['valor'] ?>" readonly="readonly">
                    </div>
                    <div class="col-md-3">
                        <label for="txt_observacion" class="small">Observaciones</label>
                        <input type="text" class="form-control form-control-sm bg-input" id="txt_observacion" class="small" value="<?php echo $obj['observacion'] ?>" readonly="readonly">
                    </div>
                </div>
            </form>
            <table id="tb_lista_activos_fijos" class="table table-striped table-bordered table-sm nowrap table-hover shadow w-100" style="font-size:80%">
                <thead>
                    <tr class="text-center">
                        <th class="bg-sofia">Id</th>
                        <th class="bg-sofia">Placa</th>
                        <th class="bg-sofia">No. Serial</th>
                        <th class="bg-sofia">Marca</th>
                        <th class="bg-sofia">Valor</th>
                        <th class="bg-sofia">Estado</th>
                        <th class="bg-sofia">Acciones</th>
                    </tr>
                </thead>
                <tbody class="text-start"></tbody>
            </table>
        </div>
    </div>
    <div class="text-end pt-3 right">
        <a type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Salir</a>
    </div>
</div>

<script type="text/javascript" src="../../js/ingresos/activofijo_reg.js?v=<?php echo date('YmdHis') ?>"></script>