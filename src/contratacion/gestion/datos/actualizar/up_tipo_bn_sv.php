<?php
$sessionStarted = false;
if (session_status() === PHP_SESSION_NONE) {
    session_start();
    $sessionStarted = true;
}
if (!isset($_SESSION['user'])) {
    header('Location: ../../../../index.php');
    exit();
}
include_once '../../../../../config/autoloader.php';

$idtbs = isset($_POST['idtbs']) ? $_POST['idtbs'] : exit('Acción no permitida');

$id_user = isset($_SESSION['id_user']) ? $_SESSION['id_user'] : null;
$cmd = \Config\Clases\Conexion::getConexion();
try {

    $sql = "SELECT * FROM `tb_tipo_bien_servicio` WHERE `id_tipo_b_s` = ?";
    $stmt = $cmd->prepare($sql);
    $stmt->bindParam(1, $idtbs, PDO::PARAM_INT);
    $stmt->execute();
    $tbnsv = $stmt->fetch();
} catch (PDOException $e) {
    echo $e->getCode() == 2002 ? 'Sin Conexión a Mysql (Error: 2002)' : 'Error: ' . $e->getMessage();
}

if (!empty($tbnsv)) {
    try {
        $cmd = \Config\Clases\Conexion::getConexion();

        $sql = "SELECT `id_tipo`, `tipo_compra` FROM `tb_tipo_compra` ORDER BY `tipo_compra`";
        $rs = $cmd->query($sql);
        $tipo = $rs->fetchAll(Pdo::FETCH_ASSOC);
        $rs->closeCursor();
        unset($rs);
        $cmd = null;
    } catch (PDOException $e) {
        echo $e->getCode() == 2002 ? 'Sin Conexión a Mysql (Error: 2002)' : 'Error: ' . $e->getMessage();
    }
?>
    <div class="px-0">
        <div class="shadow">
            <div class="card-header text-center py-2" style="background-color: #16a085 !important;">
                <h5 style="color: white;">ACTUALIZAR DATOS DE TIPO DE BIEN O SERVICIO</h5>
            </div>
            <form id="formActualizaBnSv">
                <input type="number" id="idTipoBnSv" name="idTipoBnSv" value="<?php echo $tbnsv['id_tipo_b_s'] ?>" hidden>
                <div class="row px-4 pt-2">
                    <div class="col-md-4 mb-3">
                        <label for="slcTipoContrato" class="small">TIPO DE BIEN O SERVICIO</label>
                        <select id="slcTipoContrato" name="slcTipoContrato" class="form-control form-control-sm py-0 sm bg-input" aria-label="Default select example">
                            <?php
                            foreach ($tipo as $tp) {
                                if ($tp['id_tipo'] !== $tbnsv['id_tipo']) {
                                    echo '<option value="' . $tp['id_tipo'] . '">' . $tp['tipo_compra'] . '</option>';
                                } else {
                                    echo '<option selected value="' . $tp['id_tipo'] . '">' . $tp['tipo_compra'] . '</option>';
                                }
                            }
                            ?>
                        </select>
                    </div>
                    <div class="col-md-8 mb-3">
                        <label for="txtTipoBnSv" class="small">NOMBRE TIPO DE BIEN O SERVICIO</label>
                        <input id="txtTipoBnSv" type="text" name="txtTipoBnSv" class="form-control form-control-sm py-0 sm bg-input" aria-label="Default select example" value="<?= $tbnsv['tipo_bn_sv'] ?>">
                    </div>
                </div>
                <div class="row px-4 pt-2">
                    <div class="col-md-12 mb-3">
                        <label for="txtObjPre" class="small">OBJETO PREDEFINIDO</label>
                        <textarea id="txtObjPre" type="text" name="txtObjPre" class="form-control form-control-sm py-0 sm bg-input" aria-label="Default select example" rows="3" placeholder="Objeto predefinido del contrato"><?= $tbnsv['objeto_definido'] ?></textarea>
                    </div>
                </div>
                <div class="text-center py-3">
                    <button class="btn btn-primary btn-sm" id="btnUpTipoBnSv">Actualizar</button>
                    <a type="button" class="btn btn-secondary  btn-sm" data-bs-dismiss="modal"> Cancelar</a>
                </div>
            </form>
        </div>
    </div>
<?php
} else {
    echo 'Error al intentar obtener datos';
} ?>