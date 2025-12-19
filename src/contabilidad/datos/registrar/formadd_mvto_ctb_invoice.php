<?php
session_start();
if (!isset($_SESSION['user'])) {
    header("Location: ../../../index.php");
    exit();
}
include '../../../../config/autoloader.php';
include '../../../financiero/consultas.php';


$cod_ctb_doc = isset($_POST['cod_doc']) ? $_POST['cod_doc'] : exit('Acceso no permitido');
$id_documento = isset($_POST['id_detalle']) ? $_POST['id_detalle'] : 0;
$id_vigencia = $_SESSION['id_vigencia'];

$cmd = \Config\Clases\Conexion::getConexion();

$fecha_cierre = fechaCierre($_SESSION['vigencia'], 55, $cmd);
try {
    $sql = "SELECT
                MAX(`ctb_doc`.`id_manu`) AS `id_manu`, `id_doc_fuente`,`ctb_fuente`.`nombre`
            FROM
                `ctb_fuente`
                LEFT JOIN `ctb_doc` 
                    ON (`ctb_doc`.`id_tipo_doc` = `ctb_fuente`.`id_doc_fuente` AND `ctb_doc`.`id_vigencia` = $id_vigencia)
            WHERE (`ctb_fuente`.`cod` = '$cod_ctb_doc')";
    $rs = $cmd->query($sql);
    $consecutivo = $rs->fetch();
    $id_manu = !empty($consecutivo) ? $consecutivo['id_manu'] + 1 : 1;
    $fuente = !empty($consecutivo) ? $consecutivo['nombre'] : '---';
    $id_doc_fuente = !empty($consecutivo) ? $consecutivo['id_doc_fuente'] : 0;
    $cmd = null;
} catch (PDOException $e) {
    echo $e->getCode() == 2002 ? 'Sin Conexión a Mysql (Error: 2002)' : 'Error: ' . $e->getCode();
}
try {
    $cmd = \Config\Clases\Conexion::getConexion();
    $sql = "SELECT
                `ctb_doc`.`id_ctb_doc`
                , `ctb_doc`.`id_manu`
                , `ctb_doc`.`id_tercero`
                , `ctb_doc`.`fecha`
                , `ctb_doc`.`detalle`
                , `ctb_doc`.`id_ref_ctb`
                , `tb_terceros`.`nom_tercero`
                , `tb_terceros`.`nit_tercero`
            FROM
                `ctb_doc`
                LEFT JOIN `tb_terceros`
                    ON (`ctb_doc`.`id_tercero` = `tb_terceros`.`id_tercero_api`)
            WHERE (`id_ctb_doc` = $id_documento)";
    $rs = $cmd->query($sql);
    $datos = $rs->fetch();
} catch (PDOException $e) {
    echo $e->getCode() == 2002 ? 'Sin Conexión a Mysql (Error: 2002)' : 'Error: ' . $e->getCode();
}

try {
    $cmd = \Config\Clases\Conexion::getConexion();
    $sql = "SELECT `id_ctb_referencia`,`nombre` FROM `ctb_referencia` WHERE `accion` = 1";
    $rs = $cmd->query($sql);
    $referencias = $rs->fetchAll();
    $rs->closeCursor();
    unset($rs);
} catch (PDOException $e) {
    echo $e->getCode() == 2002 ? 'Sin Conexión a Mysql (Error: 2002)' : 'Error: ' . $e->getCode();
}
$fecha = date("Y-m-d");
// Estabelcer fecha minima con vigencia
$fecha_min = date("Y-m-d", strtotime($_SESSION['vigencia'] . '-01-01'));
$fecha_max = date("Y-m-d", strtotime($_SESSION['vigencia'] . '-12-31'));
if (empty($datos)) {
    $datos['id_ctb_doc'] = 0;
    $datos['id_manu'] = $id_manu;
    $datos['id_tercero'] = 0;
    $datos['fecha'] = $fecha;
    $datos['detalle'] = '';
    $datos['id_ref_ctb'] = 0;
    $tercero = '';
} else {
    $tercero = !empty($datos['nom_tercero']) ? ltrim($datos['nom_tercero']) : '---';
}
$cmd = null;
?>
<div class="px-0">
    <div class="shadow">
        <div class="card-header py-2 text-center" style="background-color: #16a085 !important;">
            <h5 class="mb-0" style="color: white;">GESTIÓN DOCUMENTOS: <b><?php echo $fuente; ?></b></h5>
        </div>
        <form id="formMvtoCtbInvoice">
            <input type="hidden" name="cod_ctb_doc" value="<?php echo $cod_ctb_doc; ?>">
            <input type="hidden" name="id_doc_fuente" value="<?php echo $id_doc_fuente; ?>">
            <input type="hidden" id="fec_cierre" value="<?php echo $fecha_cierre; ?>">
            <div class="row mb-2 px-4 pt-2">
                <div class="col-md-4">
                    <label for="fecha" class="small">FECHA </label>
                    <input type="date" name="fecha" id="fecha" class="form-control form-control-sm bg-input" value="<?php echo date('Y-m-d', strtotime($datos['fecha'])); ?>" min="<?php echo $fecha_min; ?>" max="<?php echo $fecha_max; ?>">
                </div>
                <div class="col-md-4">
                    <label for="numDoc" class="small">NUMERO</label>
                    <input type="number" name="numDoc" id="numDoc" class="form-control form-control-sm bg-input" value="<?php echo $datos['id_manu'] ?>">
                </div>
                <div class="col-md-4">
                    <label for="slcReferencia" class="small">Referencia</label>
                    <select id="slcReferencia" name="slcReferencia" class="form-control form-control-sm bg-input">
                        <option value="0">Seleccione...</option>
                        <?php
                        foreach ($referencias as $rf) {
                            $slc = $datos['id_ref_ctb'] == $rf['id_ctb_referencia'] ? 'selected' : '';
                            echo "<option value='{$rf['id_ctb_referencia']}' {$slc}>{$rf['nombre']}</option>";
                        }
                        ?>
                    </select>
                </div>
            </div>
            <div class="row mb-2 px-4">
                <div class="col-md-12">
                    <label for="terceromov" class="small">TERCERO</label>
                    <input type="text" name="terceromov" id="terceromov" class="form-control form-control-sm bg-input" value="<?php echo $tercero ?>">
                    <input type="hidden" name="id_tercero" id="id_tercero" class="form-control form-control-sm bg-input" value="<?php echo $datos['id_tercero'] ?>">
                </div>

            </div>
            <div class="row mb-2 px-4">
                <div class="col-md-12">
                    <label for="objeto" class="small">OBJETO</label>
                    <textarea id="objeto" type="text" name="objeto" class="form-control form-control-sm bg-input py-0 sm" aria-label="Default select example" rows="4" required><?php echo $datos['detalle'] ?></textarea>
                </div>

            </div>
        </form>
        <div class="text-end pb-3 px-4 w-100">
            <button class="btn btn-primary btn-sm" style="width: 5rem;" id="btnGuardaMvtoCtbInvoice" text="<?php echo $id_documento ?>"><?php echo $id_documento == 0 ? 'Registrar' : 'Actualizar'; ?></button>
            <a type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancelar</a>
        </div>
    </div>
</div>