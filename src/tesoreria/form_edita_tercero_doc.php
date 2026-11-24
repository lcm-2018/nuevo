<?php
session_start();
if (!isset($_SESSION['user'])) {
    header("Location: ../../index.php");
    exit();
}
include '../../config/autoloader.php';

$id_ctb_doc = isset($_POST['id_ctb_doc']) ? intval($_POST['id_ctb_doc']) : (isset($_POST['id']) ? intval($_POST['id']) : exit('Acceso no permitido'));

$cmd = \Config\Clases\Conexion::getConexion();

try {
    $sql = "SELECT
                `ctb_doc`.`id_ctb_doc`
                , `ctb_doc`.`id_manu`
                , `ctb_doc`.`id_tercero`
                , `ctb_doc`.`fecha`
                , `ctb_doc`.`detalle`
                , `ctb_doc`.`motivo_edita`
                , `ctb_fuente`.`nombre` AS `fuente`
                , `tb_terceros`.`nom_tercero`
                , `tb_terceros`.`nit_tercero`
            FROM
                `ctb_doc`
                INNER JOIN `ctb_fuente` 
                    ON (`ctb_doc`.`id_tipo_doc` = `ctb_fuente`.`id_doc_fuente`)
                LEFT JOIN `tb_terceros`
                    ON (`tb_terceros`.`id_tercero_api` = `ctb_doc`.`id_tercero`)
            WHERE (`ctb_doc`.`id_ctb_doc` = $id_ctb_doc)";
    $rs = $cmd->query($sql);
    $datos = $rs ? $rs->fetch(PDO::FETCH_ASSOC) : null;
    $cmd = null;
} catch (PDOException $e) {
    echo $e->getCode() == 2002 ? 'Sin Conexión a Mysql (Error: 2002)' : 'Error: ' . $e->getCode();
    exit();
}

if (empty($datos)) {
    echo '<div class="alert alert-danger m-3">Documento no encontrado</div>';
    exit();
}

$tercero = ltrim($datos['nom_tercero'] ?? '');
if (!empty($datos['nit_tercero'])) {
    $tercero_display = $tercero . ' -> ' . $datos['nit_tercero'];
} else {
    $tercero_display = $tercero;
}
$id_tercero_ant = $datos['id_tercero'] ?? 0;
$fuente = $datos['fuente'] ?? 'DOCUMENTO';
?>
<div class="px-0">
    <div class="shadow pb-2">
        <div class="card-header text-center py-2" style="background-color: #16a085 !important;">
            <h5 class="mb-0" style="color: white;"><i class="fas fa-user-edit me-1"></i> MODIFICAR TERCERO: <b><?php echo $fuente . ' N° ' . $datos['id_manu']; ?></b></h5>
        </div>
        <div class="px-4">
            <form id="formEditaTerceroDoc">
                <input type="hidden" name="id_ctb_doc" id="id_ctb_doc" value="<?php echo $id_ctb_doc; ?>">
                <input type="hidden" name="id_tercero_ant" id="id_tercero_ant" value="<?php echo $id_tercero_ant; ?>">

                <div class="row mb-2 pt-3">
                    <div class="col-md-6">
                        <label for="fecha" class="small">FECHA</label>
                        <input type="date" name="fecha" id="fecha" class="form-control form-control-sm bg-secondary-subtle"
                            value="<?php echo date('Y-m-d', strtotime($datos['fecha'])); ?>" readonly>
                    </div>
                    <div class="col-md-6">
                        <label for="numDoc" class="small">NÚMERO</label>
                        <input type="number" name="numDoc" id="numDoc" class="form-control form-control-sm bg-secondary-subtle"
                            value="<?php echo $datos['id_manu']; ?>" readonly>
                    </div>
                </div>

                <div class="row mb-2">
                    <div class="col-md-12">
                        <label for="terceromov" class="small">TERCERO <span class="text-danger">*</span></label>
                        <input type="text" name="terceromov" id="terceromov"
                            class="form-control form-control-sm bg-input" value="<?php echo htmlspecialchars($tercero_display); ?>" placeholder="Buscar tercero por nombre o identificación..." required>
                        <input type="hidden" name="id_tercero" id="id_tercero"
                            value="<?php echo $datos['id_tercero']; ?>">
                    </div>
                </div>

                <div class="row mb-2">
                    <div class="col-md-12">
                        <label for="motivo_edita" class="small">MOTIVO DE MODIFICACIÓN <span class="text-danger">*</span></label>
                        <textarea id="motivo_edita" name="motivo_edita"
                            class="form-control form-control-sm bg-input py-1 sm"
                            rows="4" required placeholder="Ingrese el motivo por el cual se modifica el tercero del documento..."><?php echo htmlspecialchars($datos['motivo_edita'] ?? ''); ?></textarea>
                    </div>
                </div>
            </form>
        </div>
    </div>
    <div class="text-end px-4 pt-3 w-100">
        <button type="button" class="btn btn-primary btn-sm" onclick="guardarUpTerceroDoc()">Actualizar</button>
        <a type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancelar</a>
    </div>
</div>
