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

$idbs = isset($_POST['idbs']) ? $_POST['idbs'] : exit('Acción no permitida');

include_once '../../../../../config/autoloader.php';
$cmd = \Config\Clases\Conexion::getConexion();

try {

    $sql = "SELECT * FROM `ctt_bien_servicio` WHERE `id_b_s` = ?";
    $stmt = $cmd->prepare($sql);
    $stmt->bindParam(1, $idbs, PDO::PARAM_INT);
    $stmt->execute();
    $bnsv = $stmt->fetch();
} catch (PDOException $e) {
    echo $e->getCode() == 2002 ? 'Sin Conexión a Mysql (Error: 2002)' : 'Error: ' . $e->getMessage();
}

if (!empty($bnsv)) {
    try {

        $sql = "SELECT 
                    `id_tipo_b_s`, `tipo_compra`, `tipo_bn_sv`
                FROM
                    `tb_tipo_bien_servicio`
                INNER JOIN `tb_tipo_compra` 
                    ON (`tb_tipo_bien_servicio`.`id_tipo` = `tb_tipo_compra`.`id_tipo`)
                ORDER BY `tipo_compra`, `tipo_bn_sv`";
        $rs = $cmd->query($sql);
        $tbnsv = $rs->fetchAll();
    } catch (PDOException $e) {
        echo $e->getCode() == 2002 ? 'Sin Conexión a Mysql (Error: 2002)' : 'Error: ' . $e->getMessage();
    }
    $error = "Debe diligenciar este campo";
?>
    <div class="px-0">
        <div class="shadow">
            <div class="card-header text-center py-2" style="background-color: #16a085 !important;">
                <h5 style="color: white;">ACTUALIZAR DATOS DE BIEN O SERVICIO</h5>
            </div>
            <form id="formActualizaBnSv">
                <input type="number" id="idBnSv" name="idBnSv" value="<?php echo $bnsv['id_b_s'] ?>" hidden>
                <div class="row px-4 pt-2">
                    <div class="col-md-4 mb-3">
                        <label for="slcTipoBnSv" class="small">TIPO DE BIEN O SERVICIO</label>
                        <select id="slcTipoBnSv" name="slcTipoBnSv" class="form-control form-control-sm py-0 sm bg-input" aria-label="Default select example">
                            <?php
                            foreach ($tbnsv as $tbs) {
                                if ($tbs['id_tipo_b_s'] !== $bnsv['id_tipo_bn_sv']) {
                                    echo '<option value="' . $tbs['id_tipo_b_s'] . '">' . $tbs['tipo_compra'] . ' || ' . $tbs['tipo_contrato'] . ' || ' . $tbs['tipo_bn_sv'] . '</option>';
                                } else {
                                    echo '<option selected value="' . $tbs['id_tipo_b_s'] . '">' . $tbs['tipo_compra'] . ' || ' . $tbs['tipo_contrato'] . ' || ' . $tbs['tipo_bn_sv'] . '</option>';
                                }
                            }
                            ?>
                        </select>
                    </div>
                    <div class="col-md-8 mb-3">
                        <label for="txtBnSv" class="small">NOMBRE DE BIEN O SERVICIO</label>
                        <input id="txtBnSv" type="text" name="txtBnSv" class="form-control form-control-sm py-0 sm bg-input" aria-label="Default select example" value="<?php echo $bnsv['bien_servicio'] ?>">
                    </div>
                </div>
                <div class="text-center pb-3">
                    <button class="btn btn-primary btn-sm" id="btnUpBnSv">Actualizar</button>
                    <a type="button" class="btn btn-secondary  btn-sm" data-bs-dismiss="modal"> Cancelar</a>
                </div>
            </form>
        </div>
    </div>
<?php
} else {
    echo 'Error al intentar obtener datos';
} ?>