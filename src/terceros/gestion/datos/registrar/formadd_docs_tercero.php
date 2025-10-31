<?php
session_start();
if (!isset($_SESSION['user'])) {
    header('Location: ../../../../../index.php');
    exit();
}
include('../../../../../config/autoloader.php');
$idT = $_POST['idt'];

try {
    $cmd = \Config\Clases\Conexion::getConexion();

    $sql = "SELECT
                `id_soporte`, `descripcion`
            FROM
                `ctt_soportes_contrato`
            ORDER BY `descripcion` ASC";
    $rs = $cmd->query($sql);
    $tipo_docs = $rs->fetchAll(PDO::FETCH_ASSOC);
    $cmd = null;
} catch (PDOException $e) {
    echo $e->getCode() == 2002 ? 'Sin Conexión a Mysql (Error: 2002)' : 'Error: ' . $e->getMessage();
}

try {
    $cmd = \Config\Clases\Conexion::getConexion();

    $sql = "SELECT
                `id_banco`, `nom_banco`
            FROM
                `tb_bancos`
            ORDER BY `nom_banco` ASC";
    $rs = $cmd->query($sql);
    $bancos = $rs->fetchAll(PDO::FETCH_ASSOC);
    $cmd = null;
} catch (PDOException $e) {
    echo $e->getCode() == 2002 ? 'Sin Conexión a Mysql (Error: 2002)' : 'Error: ' . $e->getMessage();
}
try {
    $cmd = \Config\Clases\Conexion::getConexion();

    $sql = "SELECT
                `id_perfil`,`descripcion`
            FROM `ctt_perfil_tercero`
            ORDER BY `descripcion` ASC";
    $rs = $cmd->query($sql);
    $perfiles = $rs->fetchAll(PDO::FETCH_ASSOC);
    $cmd = null;
} catch (PDOException $e) {
    echo $e->getCode() == 2002 ? 'Sin Conexión a Mysql (Error: 2002)' : 'Error: ' . $e->getMessage();
}

?>
<div class="px-0">
    <div class="shadow">
        <div class="card-header text-center p-2" style="background-color: #16a085 !important;">
            <h5 class="mb-0" style="color: white;">CARGAR DOCUMENTOS</h5>
        </div>
        <form id="formAddDocsTercero" enctype="multipart/form-data">
            <input type="hidden" id="idTercero" name="idTercero" value="<?php echo $idT ?>">
            <div class="row px-4 pt-2 mb-3">
                <div class="col-md-4">
                    <label for="slcTipoDocs" class="small">Tipo</label>
                    <select id="slcTipoDocs" name="slcTipoDocs" class="form-select form-select-sm bg-input" aria-label="Default select example">
                        <option value="0">-- Seleccionar --</option>
                        <?php
                        foreach ($tipo_docs as $td) {
                            echo '<option value="' . $td['id_soporte'] . '">' . $td['descripcion'] . '</option>';
                        }
                        ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <label for="datFecInicio" class="small">Fecha Inicio</label>
                    <input type="date" class="form-control form-control-sm bg-input" id="datFecInicio" name="datFecInicio">
                </div>
                <div class="col-md-4">
                    <label for="datFecVigencia" class="small">Fecha Vigente</label>
                    <input type="date" class="form-control form-control-sm bg-input" id="datFecVigencia" name="datFecVigencia">
                </div>
            </div>
            <div id="rowCertfBanc" class="mb-3" style="display: none;">
                <div class="row px-4 ">
                    <div class="col-md-4">
                        <label for="slcBanco" class="small">Banco</label>
                        <select class="form-select form-select-sm bg-input" id="slcBanco" name="slcBanco">
                            <option value="0">-- Seleccionar --</option>
                            <?php
                            foreach ($bancos as $b) {
                                echo '<option value="' . $b['id_banco'] . '">' . $b['nom_banco'] . '</option>';
                            }
                            ?>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label for="slcTipoCta" class="small">Tipo Cuenta</label>
                        <select class="form-select form-select-sm bg-input" id="slcTipoCta" name="slcTipoCta">
                            <option value="0">-- Seleccionar --</option>
                            <option value="Ahorros">Ahorros</option>
                            <option value="Corriente">Corriente</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label for="numCuenta" class="small">Número de cuenta</label>
                        <input type="text" class="form-control form-control-sm bg-input" id="numCuenta" name="numCuenta">
                    </div>
                </div>
            </div>
            <div id="rowCcontrato" class="mb-3" style="display: none;">
                <div class="row px-4">
                    <div class="col-md-6">
                        <label for="slcPerfil" class="small">Perfil</label>
                        <select class="form-select form-select-sm bg-input" id="slcPerfil" name="slcPerfil">
                            <option value="0">-- Seleccionar --</option>
                            <?php
                            foreach ($perfiles as $p) {
                                echo '<option value="' . $p['id_perfil'] . '">' . $p['descripcion'] . '</option>';
                            }
                            ?>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label for="txtCargo" class="small">Cargo</label>
                        <input type="text" class="form-control form-control-sm bg-input" id="txtCargo" name="txtCargo">
                    </div>
                </div>
            </div>
            <div class="row px-4 mb-3">
                <div class="col-md-12">
                    <label for="fileDoc" class="small">Documento</label>
                    <input type="hidden" name="MAX_FILE_SIZE" value="30000" />
                    <input type="file" class="form-control bg-input" name="fileDoc" id="fileDoc">
                </div>
            </div>
        </form>
        <div class="text-end p-3">
            <button class="btn btn-primary btn-sm" id="btnGuardaDocTercero">Guardar</button>
            <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal"> Cancelar</button>
        </div>
    </div>
</div>