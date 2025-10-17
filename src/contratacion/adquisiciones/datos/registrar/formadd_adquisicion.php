<?php
session_start();
if (!isset($_SESSION['user'])) {
    header('Location: ../../../../index.php');
    exit();
}
include_once '../../../../../config/autoloader.php';

$id_adq = isset($_POST['id_adq']) ? $_POST['id_adq'] : exit('Acceso denegado');
try {
    $cmd = \Config\Clases\Conexion::getConexion();

    $sql = "SELECT * FROM `ctt_modalidad` ORDER BY `modalidad` ASC";
    $rs = $cmd->query($sql);
    $modalidad = $rs->fetchAll(PDO::FETCH_ASSOC);
$rs->closeCursor();
unset($rs);
    $cmd = null;
} catch (PDOException $e) {
    echo $e->getCode() == 2002 ? 'Sin Conexión a Mysql (Error: 2002)' : 'Error: ' . $e->getMessage();
}
try {
    $cmd = \Config\Clases\Conexion::getConexion();

    $sql = "SELECT `id_area`, `area` FROM `tb_area_c` ORDER BY `area` ASC";
    $rs = $cmd->query($sql);
    $areas = $rs->fetchAll(PDO::FETCH_ASSOC);
$rs->closeCursor();
unset($rs);
    $cmd = null;
} catch (PDOException $e) {
    echo $e->getCode() == 2002 ? 'Sin Conexión a Mysql (Error: 2002)' : 'Error: ' . $e->getMessage();
}
try {
    $cmd = \Config\Clases\Conexion::getConexion();

    $sql = "SELECT 
            `id_tipo_b_s`, `tipo_compra`, `tipo_bn_sv`
        FROM
            `tb_tipo_bien_servicio`
        INNER JOIN `tb_tipo_compra` 
            ON (`tb_tipo_bien_servicio`.`id_tipo` = `tb_tipo_compra`.`id_tipo`)
        ORDER BY `tipo_compra`, `tipo_bn_sv`";
    $rs = $cmd->query($sql);
    $tbnsv = $rs->fetchAll(PDO::FETCH_ASSOC);
$rs->closeCursor();
unset($rs);
    $cmd = null;
} catch (PDOException $e) {
    echo $e->getCode() == 2002 ? 'Sin Conexión a Mysql (Error: 2002)' : 'Error: ' . $e->getMessage();
}
$readonly = '';
$disabled = '';
if ($id_adq > 0) {
    try {
        $cmd = \Config\Clases\Conexion::getConexion();

        $sql = "SELECT
                    `ctt_adquisiciones`.`id_adquisicion`
                    , `ctt_adquisiciones`.`id_modalidad`
                    , `ctt_adquisiciones`.`id_area`
                    , `ctt_adquisiciones`.`fecha_adquisicion`
                    , `ctt_adquisiciones`.`val_contrato`
                    , CONCAT_WS(' -> ',`tb_tipo_compra`.`tipo_compra`
                    , `tb_tipo_bien_servicio`.`tipo_bn_sv`) AS `tipo_bn_sv`
                    , `ctt_adquisiciones`.`id_tipo_bn_sv`
                    , `ctt_adquisiciones`.`objeto`
                    , `ctt_adquisiciones`.`id_tercero`
                    , `tb_terceros`.`nom_tercero` AS `tercero`
                    , `tb_area_c`.`filtro_adq` AS `filtro`
                FROM
                    `ctt_adquisiciones`
                    INNER JOIN `tb_tipo_bien_servicio` 
                        ON (`ctt_adquisiciones`.`id_tipo_bn_sv` = `tb_tipo_bien_servicio`.`id_tipo_b_s`)
                    INNER JOIN `tb_tipo_compra` 
                        ON (`tb_tipo_bien_servicio`.`id_tipo` = `tb_tipo_compra`.`id_tipo`)
                    INNER JOIN `tb_area_c` 
                        ON (`ctt_adquisiciones`.`id_area` = `tb_area_c`.`id_area`)
                    LEFT JOIN `tb_terceros` 
                        ON (`ctt_adquisiciones`.`id_tercero` = `tb_terceros`.`id_tercero_api`)
                WHERE (`ctt_adquisiciones`.`id_adquisicion` = $id_adq)";
        $rs = $cmd->query($sql);
        $adquisicion = $rs->fetch(PDO::FETCH_ASSOC);
        $cmd = null;
    } catch (PDOException $e) {
        echo $e->getCode() == 2002 ? 'Sin Conexión a Mysql (Error: 2002)' : 'Error: ' . $e->getMessage();
    }
} else {
    $adquisicion = [
        'id_adquisicion' => 0,
        'id_modalidad' => 0,
        'id_area' => 0,
        'fecha_adquisicion' => date('Y-m-d'),
        'val_contrato' => '',
        'tipo_bn_sv' => '',
        'id_tipo_bn_sv' => 0,
        'objeto' => '',
        'id_tercero' => 0,
        'filtro' => 2,
        'tercero' => ''
    ];
}
if ($adquisicion['filtro'] == '1' || $adquisicion['filtro'] == '2') {
    $readonly = 'readonly';
    $disabled = ' disabled';
}
?>
<div class="px-0">
    <div class="shadow">
        <div class="card-header text-center py-2" style="background-color: #16a085 !important;">
            <h5 style="color: white;">REGISTRAR ADQUISICIÓN</h5>
        </div>
        <form id="formAddAdquisicion">
            <input type="hidden" name="id_adquisicion" value="<?php echo $adquisicion['id_adquisicion'] ?>">
            <input type="hidden" name="filtro" id="filtro" value="<?php echo $adquisicion['filtro'] ?>">
            <div class="row px-4 pt-2">
                <div class="col-md-4 mb-3">
                    <label for="datFecAdq" class="small">FECHA ADQUISICIÓN</label>
                    <input type="date" name="datFecAdq" id="datFecAdq" class="form-control form-control-sm bg-input" value="<?php echo $adquisicion['fecha_adquisicion'] ?>">
                </div>
                <input type="hidden" name="datFecVigencia" value="<?php echo $_SESSION['vigencia'] ?>">
                <div class="col-md-4 mb-3">
                    <label for="slcModalidad" class="small">MODALIDAD CONTRATACIÓN</label>
                    <select id="slcModalidad" name="slcModalidad" class="form-select form-select-sm bg-input" aria-label="Default select example">
                        <option value="0" <?php echo $adquisicion['id_modalidad'] == 0 ? 'selected' : '' ?>>-- Seleccionar --</option>
                        <?php
                        foreach ($modalidad as $mo) {
                            $slc = $adquisicion['id_modalidad'] == $mo['id_modalidad'] ? 'selected' : '';
                            echo '<option value="' . $mo['id_modalidad'] . '" ' . $slc . '>' . $mo['modalidad'] . '</option>';
                        }
                        ?>
                    </select>
                </div>
                <input type="hidden" name="numTotalContrato" id="numTotalContrato" class="form-control form-control-sm" value="0">
                <div class="col-md-4 mb-3">
                    <label for="slcAreaSolicita" class="small">ÁREA SOLICITANTE</label>
                    <select id="slcAreaSolicita" name="slcAreaSolicita" class="form-select form-select-sm bg-input" aria-label="Default select example">
                        <option value="0" <?php echo $adquisicion['id_area'] == 0 ? 'selected' : '' ?>>-- Seleccionar --</option>
                        <?php
                        foreach ($areas as $ar) {
                            $slc = $adquisicion['id_area'] == $ar['id_area'] ? 'selected' : '';
                            echo '<option value="' . $ar['id_area'] . '" ' . $slc . '>' . $ar['area'] . '</option>';
                        }
                        ?>
                    </select>
                </div>
            </div>
            <?php if (false) { ?>
                <div class="row px-4">
                    <div class="col-md-12 mb-3">
                        <label for="SeaTercer" class="small">TERCERO</label>
                        <input type="text" id="SeaTercer" class="form-control form-control-sm py-0 sm" placeholder="Buscar tercero" value="<?php echo $adquisicion['tercero'] ?>">
                        <input type="hidden" name="id_tercero" id="id_tercero" value="<?php echo $adquisicion['id_tercero'] ?>">
                    </div>
                </div>
            <?php } ?>
            <div class="row px-4">                <div class="col-md-12 mb-3">
                    <label for="txtBuscarTipoBnSv" class="small">TIPO DE BIEN / SERVICIO</label>                    <input type="text" id="txtBuscarTipoBnSv" class="form-control form-control-sm bg-input" placeholder="Buscar tipo de servicio" <?php echo $readonly . $disabled ?> value="<?php echo $adquisicion['tipo_bn_sv'] ?>">
                    <input type="hidden" name="slcTipoBnSv" id="slcTipoBnSv" value="<?php echo $adquisicion['id_tipo_bn_sv'] ?>">                </div>
            </div>
            <div class="row px-4 pt-2">
                <div class="col-md-12 mb-3">
                    <label for="txtObjeto" class="small">OBJETO</label>
                    <textarea id="txtObjeto" type="text" name="txtObjeto" class="form-control form-control-sm bg-input" aria-label="Default select example" rows="3"><?php echo $adquisicion['objeto'] ?></textarea>
                </div>
            </div>
            <div class="text-center">
                <div class="text-center pb-3">
                    <button class="btn btn-primary btn-sm" id="btnAddAdquisicion">Guardar</button>
                    <a type="button" class="btn btn-secondary  btn-sm" data-bs-dismiss="modal">Cancelar</a>
                </div>
            </div>
        </form>
    </div>
</div>