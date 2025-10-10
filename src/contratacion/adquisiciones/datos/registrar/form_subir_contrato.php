<?php
session_start();
if (!isset($_SESSION['user'])) {
    header('Location: ../../../../index.php');
    exit();
}
include '../../../../conexion.php';
$id_c = isset($_POST['id']) ? $_POST['id'] : exit('Accion no permitida');
try {
    $cmd = \Config\Clases\Conexion::getConexion();
    
    $sql = "SELECT
                `ctt_contratos`.`id_contrato_compra`
                , `ctt_contratos`.`id_compra`
                , `ctt_contratos`.`val_contrato`
                , `tb_terceros`.`no_doc`
            FROM
                `ctt_contratos`
            INNER JOIN `ctt_adquisiciones` 
                ON (`ctt_contratos`.`id_compra` = `ctt_adquisiciones`.`id_adquisicion`)
            INNER JOIN `tb_terceros` 
                ON (`ctt_adquisiciones`.`id_tercero` = `tb_terceros`.`id_tercero_api`)
            WHERE `ctt_contratos`.`id_compra`= '$id_c' LIMIT 1";
    $rs = $cmd->query($sql);
    $ids = $rs->fetch();
    $cmd = null;
} catch (PDOException $e) {
    echo $e->getCode() == 2002 ? 'Sin Conexión a Mysql (Error: 2002)' : 'Error: ' . $e->getMessage();
}
?>
<div class="px-0">
    <div class="shadow">
         <div class="card-header text-center py-2" style="background-color: #16a085 !important;">
            <h5 style="color: white;">ENVIAR CONTRATO</h5>
        </div>
        <form id="formEnviarContrato">
            <input type="hidden" id="id_contrato_s" value="<?php echo $ids['id_contrato_compra'] ?>">
            <input type="hidden" id="id_compra_s" value="<?php echo $ids['id_compra'] ?>">
            <input type="hidden" id="nit_empresa_s" value="<?php echo $_SESSION['nit_emp'] ?>">
            <input type="hidden" id="doc_tercero_s" value="<?php echo $ids['no_doc'] ?>">
            <input type="hidden" id="val_contrato_s" value="<?php echo $ids['val_contrato'] ?>">
            <div class="form-row px-4 pt-2">
                <div class="form-group col-md-12">
                    <label for="fileContrato" class="small">SELECIONAR UN ARCHIVO</label>
                    <input type="file" class="form-control-file border" name="fileContrato" id="fileContrato">
                </div>
            </div>
            <div class="form-row px-4 pt-2">
                <div class="text-center pb-3">
                    <button class="btn btn-primary btn-sm" id="btnSubirContrato">Enviar</button>
                    <a type="button" class="btn btn-secondary  btn-sm" data-bs-dismiss="modal"> Cancelar</a>
                </div>
            </div>
        </form>
    </div>
</div>