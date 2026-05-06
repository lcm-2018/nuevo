<?php
session_start();
if (!isset($_SESSION['user'])) {
    header('Location: ../../../../../index.php');
    exit();
}
include '../../../../../config/autoloader.php';

use Src\Common\Php\Clases\Combos;

$id_dep = isset($_POST['id']) ? $_POST['id'] : 0;
$idt = isset($_POST['idt']) ? $_POST['idt'] : 0;

$titulo = "REGISTRAR DEPENDIENTE";
$tipo_doc = 0;
$num_doc = '';
$nombre = '';
$tipo_dependiente = 0;

if ($id_dep > 0) {
    try {
        $cmd = \Config\Clases\Conexion::getConexion();
        $sql = "SELECT * FROM `tb_terceros_dependientes` WHERE `id_dependiente` = ?";
        $stmt = $cmd->prepare($sql);
        $stmt->execute([$id_dep]);
        $dependiente = $stmt->fetch(PDO::FETCH_ASSOC);
        $cmd = null;
        
        if ($dependiente) {
            $titulo = "ACTUALIZAR DEPENDIENTE";
            $idt = $dependiente['id_tercero_api'];
            $tipo_doc = $dependiente['id_tipo_doc'];
            $num_doc = $dependiente['no_documento'];
            $nombre = $dependiente['nombre_completo'];
            $tipo_dependiente = $dependiente['id_tipo_dependiente'];
        } else {
            echo "No se encontró el registro.";
            exit();
        }
    } catch (PDOException $e) {
        echo $e->getCode() == 2002 ? 'Sin Conexión a Mysql (Error: 2002)' : 'Error: ' . $e->getMessage();
        exit();
    }
}
?>
<div class="px-0">
    <div class="shadow">
        <div class="card-header text-center p-2" style="background-color: #16a085 !important;">
            <h5 class="mb-0" style="color: white;"><?php echo $titulo; ?></h5>
        </div>
        <form id="formAddDependiente">
            <input type="hidden" id="idDependiente" name="idDependiente" value="<?php echo $id_dep; ?>">
            <input type="hidden" id="idTercero" name="idTercero" value="<?php echo $idt; ?>">
            <div class="row px-4 pt-2 mb-3">
                <div class="col-md-3">
                    <label for="slcTipoDocs" class="small">Tipo Doc.</label>
                    <select id="slcTipoDocs" name="slcTipoDocs" class="form-select form-select-sm bg-input">
                        <?php echo Combos::getTiposDocumento($tipo_doc); ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label for="txtNumDoc" class="small">Número Doc.</label>
                    <input type="text" class="form-control form-control-sm bg-input" id="txtNumDoc" name="txtNumDoc" placeholder="Número" value="<?php echo htmlspecialchars($num_doc); ?>">
                </div>
                <div class="col-md-6">
                    <label for="txtNombreCompleto" class="small">Nombre Completo</label>
                    <input type="text" class="form-control form-control-sm bg-input" id="txtNombreCompleto" name="txtNombreCompleto" placeholder="Nombres y Apellidos" value="<?php echo htmlspecialchars($nombre); ?>">
                </div>
            </div>
            <div class="row px-4 mb-3">
                <div class="col-md-12">
                    <label for="slcCalidadDependiente" class="small">Calidad / Parentesco</label>
                    <select class="form-select form-select-sm bg-input" id="slcCalidadDependiente" name="slcCalidadDependiente">
                        <?php echo Combos::getTiposDependientes($tipo_dependiente); ?>
                    </select>
                </div>
            </div>
        </form>
        <div class="text-end p-3">
            <button type="button" class="btn btn-primary btn-sm" id="btnGuardaDependiente">Guardar</button>
            <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal"> Cancelar</button>
        </div>
    </div>
</div>