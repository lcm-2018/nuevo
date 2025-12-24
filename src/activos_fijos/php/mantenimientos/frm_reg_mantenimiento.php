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
$sql = "SELECT M.*,      
            TE.id_tercero,TE.nom_tercero,      
            CASE M.estado WHEN 1 THEN 'PENDIENTE' WHEN 2 THEN 'APROBADO' WHEN 3 THEN 'EN EJECUCION' WHEN 4 THEN 'CERRADO' WHEN 0 THEN 'ANULADO' END AS nom_estado     
        FROM acf_mantenimiento M
        INNER JOIN tb_terceros AS TE ON (TE.id_tercero=M.id_tercero)
        WHERE M.id_mantenimiento=" . $id . " LIMIT 1";
$rs = $cmd->query($sql);
$obj = $rs->fetch();

if ($obj === false) {
    $obj = array(); // Inicializa $obj como un array vacío
}

if (empty($obj)) {
    $n = $rs->columnCount();
    for ($i = 0; $i < $n; $i++) :
        $col = $rs->getColumnMeta($i);
        $name = $col['name'];
        $obj[$name] = NULL;
    endfor;
    //Inicializa variable por defecto
    $obj['id_tercero'] = 0;
    $obj['nom_tercero'] = 'NINGUNO';
    $obj['estado'] = 1;
    $obj['nom_estado'] = 'PENDIENTE';

    $fecha = fecha_hora_servidor();
    $obj['fec_mantenimiento'] = $fecha['fecha'];
    $obj['hor_mantenimiento'] = $fecha['hora'];
}
$guardar = in_array($obj['estado'], [1]) ? '' : 'disabled="disabled"';
$aprobar = in_array($obj['estado'], [1]) && $id != -1 ? '' : 'disabled="disabled"';
$ejecutar = in_array($obj['estado'], [2]) ? '' : 'disabled="disabled"';
$cerrar = in_array($obj['estado'], [3]) ? '' : 'disabled="disabled"';
$anular = in_array($obj['estado'], [2]) ? '' : 'disabled="disabled"';
$imprimir = $id != -1 ? '' : 'disabled="disabled"';
?>

<div class="px-0">
    <div class="shadow">
        <div class="card-header py-2 text-center bg-sofia">
            <h5 class="text-white mb-0">REGISTRAR ORDEN DE MANTENIMIENTO</h5>
        </div>
        <div class="p-3">
            <form id="frm_reg_mantenimiento">
                <input type="hidden" id="id_mantenimiento" name="id_mantenimiento" value="<?php echo $id ?>">
                <div class="row mb-2">
                    <div class="col-md-1">
                        <label for="txt_id_mant" class="small">Id.</label>
                        <input type="text" class="form-control form-control-sm bg-input" id="txt_id_mant" name="txt_id_mant" class="small" value="<?php echo ($id == -1 ? '' : $id) ?>" readonly="readonly">
                    </div>
                    <div class="col-md-2">
                        <label for="txt_fec_mant" class="small">Fecha</label>
                        <input type="text" class="form-control form-control-sm bg-input" id="txt_fec_mant" name="txt_fec_mant" class="small" value="<?php echo $obj['fec_mantenimiento'] ?>" readonly="readonly">
                    </div>
                    <div class="col-md-2">
                        <label for="txt_hor_mant" class="small">Hora</label>
                        <input type="text" class="form-control form-control-sm bg-input" id="txt_hor_mant" name="txt_hor_mant" class="small" value="<?php echo $obj['hor_mantenimiento'] ?>" readonly="readonly">
                    </div>
                    <div class="col-md-2">
                        <label for="txt_est_mant" class="small">Estado</label>
                        <input type="text" class="form-control form-control-sm bg-input" id="txt_est_mant" name="txt_est_mant" class="small" value="<?php echo $obj['nom_estado'] ?>" readonly="readonly">
                    </div>
                </div>
                <div class="row mb-2">
                    <div class="col-md-2">
                        <label for="sl_tip_mant" class="small" required>Tipo Mantenimiento</label>
                        <select class="form-select form-select-sm bg-input" id="sl_tip_mant" name="sl_tip_mant">
                            <?php tipos_mantenimiento('', $obj['tipo_mantenimiento']) ?>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label for="sl_responsable" class="small">Reponsable</label>
                        <select class="form-select form-select-sm bg-input" id="sl_responsable" name="sl_responsable">
                            <?php usuarios($cmd, '', $obj['id_responsable']) ?>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label for="txt_tercero" class="small">Tercero</label>
                        <input type="text" class="form-control form-control-sm bg-input" id="txt_tercero" value="<?php echo $obj['nom_tercero'] ?>">
                        <input type="hidden" id="id_txt_tercero" name="id_txt_tercero" value="<?php echo $obj['id_tercero'] ?>">
                    </div>
                    <div class="col-md-2">
                        <label for="txt_fec_ini_mant" class="small">Inicio Mantenimiento</label>
                        <input type="date" class="form-control form-control-sm bg-input" id="txt_fec_ini_mant" name="txt_fec_ini_mant" class="small" value="<?php echo $obj['fec_ini_mantenimiento'] ?>">
                    </div>
                    <div class="col-md-2">
                        <label for="txt_fec_fin_mant" class="small">Fin Mantenimiento</label>
                        <input type="date" class="form-control form-control-sm bg-input" id="txt_fec_fin_mant" name="txt_fec_fin_mant" class="small" value="<?php echo $obj['fec_fin_mantenimiento'] ?>">
                    </div>
                    <div class="col-md-12">
                        <label for="txt_observaciones_mant" class="small">Observaciones</label>
                        <textarea class="form-control" id="txt_observaciones_mant" name="txt_observaciones_mant" rows="2"><?php echo $obj['observaciones'] ?></textarea>
                    </div>
                </div>
            </form>
            <table id="tb_mantenimientos_detalles" class="table table-striped table-bordered table-sm nowrap table-hover shadow align-middle w-100" style="font-size:80%">
                <thead>
                    <tr class="text-center">
                        <th class="bg-sofia">Id</th>
                        <th class="bg-sofia">Placa</th>
                        <th class="bg-sofia">Articulo</th>
                        <th class="bg-sofia">Activo Fijo</th>
                        <th class="bg-sofia">Estado General</th>
                        <th class="bg-sofia">Area</th>
                        <th class="bg-sofia">Observación</th>
                        <th class="bg-sofia">Acciones</th>
                        <th class="bg-sofia">Estado</th>
                    </tr>
                </thead>
                <tbody class="text-start"></tbody>
            </table>
        </div>
    </div>
    <div class="text-center pt-3">
        <button type="button" class="btn btn-primary btn-sm" id="btn_guardar" <?php echo $guardar ?>>Guardar</button>
        <button type="button" class="btn btn-primary btn-sm" id="btn_aprobar" <?php echo $aprobar ?>>Aprobar</button>
        <button type="button" class="btn btn-primary btn-sm" id="btn_ejecutar" <?php echo $ejecutar ?>>En Ejecución</button>
        <button type="button" class="btn btn-primary btn-sm" id="btn_cerrar" <?php echo $cerrar ?>>Cerrar</button>
        <button type="button" class="btn btn-primary btn-sm" id="btn_anular" <?php echo $anular ?>>Anular</button>
        <button type="button" class="btn btn-primary btn-sm" id="btn_imprimir" <?php echo $imprimir ?>>Imprimir</button>
        <a type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancelar</a>
    </div>
</div>

<script type="text/javascript" src="../../js/mantenimientos/mantenimientos_reg.js?v=<?php echo date('YmdHis') ?>"></script>