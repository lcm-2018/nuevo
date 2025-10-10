<?php
session_start();
if (!isset($_SESSION['user'])) {
    header("Location: ../../../index.php");
    exit();
}
include_once '../../../../../config/autoloader.php';
$id_rol = isset($_SESSION['rol']) ? $_SESSION['rol'] : null;
$id_user = isset($_SESSION['id_user']) ? $_SESSION['id_user'] : null;
$permisos = new \Src\Common\Php\Clases\Permisos();
$opciones = $permisos->PermisoOpciones($id_user);
$cmd = \Config\Clases\Conexion::getConexion();
$id_adq = isset($_POST['up_adq_compra']) ? $_POST['up_adq_compra'] : exit('Acción no permitida');
try {
    $cmd = \Config\Clases\Conexion::getConexion();
    
    $sql = "SELECT * FROM ctt_adquisiciones WHERE id_adquisicion = '$id_adq'";
    $rs = $cmd->query($sql);
    $adq_compra = $rs->fetch();
    $cmd = null;
} catch (PDOException $e) {
    echo $e->getCode() == 2002 ? 'Sin Conexión a Mysql (Error: 2002)' : 'Error: ' . $e->getMessage();
}
try {
    $cmd = \Config\Clases\Conexion::getConexion();
    
    $sql = "SELECT * FROM ctt_adquisicion_detalles WHERE id_adquisicion = '$id_adq'";
    $rs = $cmd->query($sql);
    $listBnSv = $rs->fetchAll();
    $cmd = null;
} catch (PDOException $e) {
    echo $e->getCode() == 2002 ? 'Sin Conexión a Mysql (Error: 2002)' : 'Error: ' . $e->getMessage();
}
$idtbnsv = $adq_compra['id_tipo_bn_sv'];
try {
    $cmd = \Config\Clases\Conexion::getConexion();
    
    $sql = "SELECT id_b_s, tipo_compra, tipo_contrato, tipo_bn_sv, bien_servicio
            FROM
                tb_tipo_contratacion
            INNER JOIN tb_tipo_compra 
                ON (tb_tipo_contratacion.id_tipo_compra = tb_tipo_compra.id_tipo)
            INNER JOIN tb_tipo_bien_servicio 
                ON (tb_tipo_bien_servicio.id_tipo = tb_tipo_contratacion.id_tipo)
            INNER JOIN ctt_bien_servicio 
                ON (ctt_bien_servicio.id_tipo_bn_sv = tb_tipo_bien_servicio.id_tipo_b_s)
            WHERE id_tipo_b_s = '$idtbnsv'
            ORDER BY tipo_compra,tipo_contrato, tipo_bn_sv, bien_servicio";
    $rs = $cmd->query($sql);
    $bnsv = $rs->fetchAll();
    $cmd = null;
} catch (PDOException $e) {
    echo $e->getCode() == 2002 ? 'Sin Conexión a Mysql (Error: 2002)' : 'Error: ' . $e->getMessage();
}
if (!empty($adq_compra)) {
    if ($adq_compra['estado'] <= 2) {
        try {
            $cmd = \Config\Clases\Conexion::getConexion();
            
            $sql = "SELECT * FROM ctt_modalidad ORDER BY modalidad ASC";
            $rs = $cmd->query($sql);
            $modalidad = $rs->fetchAll();
            $cmd = null;
        } catch (PDOException $e) {
            echo $e->getCode() == 2002 ? 'Sin Conexión a Mysql (Error: 2002)' : 'Error: ' . $e->getMessage();
        }
        try {
            $cmd = \Config\Clases\Conexion::getConexion();
            
            $sql = "SELECT 
                    id_tipo_b_s, tipo_compra, tipo_contrato, tipo_bn_sv
                FROM
                    tb_tipo_bien_servicio
                INNER JOIN tb_tipo_contratacion 
                    ON (tb_tipo_bien_servicio.id_tipo = tb_tipo_contratacion.id_tipo)
                INNER JOIN tb_tipo_compra 
                    ON (tb_tipo_contratacion.id_tipo_compra = tb_tipo_compra.id_tipo)
                ORDER BY tipo_compra, tipo_contrato, tipo_bn_sv";
            $rs = $cmd->query($sql);
            $tbnsv = $rs->fetchAll();
            $cmd = null;
        } catch (PDOException $e) {
            echo $e->getCode() == 2002 ? 'Sin Conexión a Mysql (Error: 2002)' : 'Error: ' . $e->getMessage();
        }
?>
        <!DOCTYPE html>
        <html lang="es">
        <?php include '../../../head.php' ?>

        <body class="sb-nav-fixed <?php if ($_SESSION['navarlat'] == '1') {
                                        echo 'sb-sidenav-toggled';
                                    } ?>">
            <?php include '../../../navsuperior.php' ?>
            <div id="layoutSidenav">
                <?php include '../../../navlateral.php' ?>
                <div id="layoutSidenav_content">
                    <main>
                        <div class="container-fluid p-2">
                            <div class="card mb-4">
                                <div class="card-header" id="divTituloPag">
                                    <div class="row">
                                        <div class="col-md-11">
                                            <i class="fas fa-copy fa-lg" style="color:#1D80F7"></i>
                                            ACTUALIZAR DETALLES DE ORDEN DE COMPRA
                                        </div>
                                    </div>
                                </div>
                                <div class="card-body" id="divCuerpoPag">
                                    <div class="accordion" id="accordion">
                                        <div class="accordion-item">
                                            <h2 class="accordion-header" id="headingOne">
                                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#datosperson" aria-expanded="false" aria-controls="datosperson">
                                                    <span class="fas fa-book fa-lg me-2" style="color: #3498DB;"></span>
                                                    1. DETALLES DE CONTRATACIÓN
                                                </button>
                                            </h2>
                                            <div id="datosperson" class="accordion-collapse collapse" aria-labelledby="headingOne" data-bs-parent="#accordion">
                                                <div class="accordion-body">
                                                    <form id="formuPAdqCompra">
                                                        <input type="hidden" name="idAdqCompra" value="<?php echo $id_adq ?>">
                                                        <div class="row">
                                                            <div class="col-md-4 mb-3">
                                                                <label for="datUpFecAdqCompra" class="small">FECHA ORDEN</label> 
                                                                <input type="date" name="datUpFecAdqCompra" id="datUpFecAdqCompra" class="form-control form-control-sm bg-input" value="<?php echo $adq_compra['fecha_adquisicion'] ?>">
                                                            </div>
                                                            <div class="col-md-4 mb-3">
                                                                <label for="slcModalidad" class="small">MODALIDAD CONTRATACIÓN</label>
                                                                <select id="slcModalidad" name="slcModalidad" class="form-control form-control-sm py-0 sm bg-input" aria-label="Default select example">
                                                                    <?php
                                                                    foreach ($modalidad as $mo) {
                                                                        if ($mo['id_modalidad'] !== $adq_compra['id_modalidad']) {
                                                                            echo '<option value="' . $mo['id_modalidad'] . '">' . $mo['modalidad'] . '</option>';
                                                                        } else {
                                                                            echo '<option selected value="' . $mo['id_modalidad'] . '">' . $mo['modalidad'] . '</option>';
                                                                        }
                                                                    }
                                                                    ?>
                                                                </select>
                                                            </div>
                                                            <div class="col-md-4 mb-3">
                                                                <label for="numTotalContrato" class="small">Valor total contrato</label> 
                                                                <input type="number" name="numTotalContrato" id="numTotalContrato" class="form-control form-control-sm bg-input" value="<?php echo $adq_compra['val_contrato'] ?>">
                                                            </div>
                                                        </div>
                                                        <div class="row">
                                                            <div class="col-md-12 mb-3">
                                                                <input type="hidden" name="tpBnSv" value="<?php echo $adq_compra['id_tipo_bn_sv'] ?>">
                                                                <label for="slcTipoBnSv" class="small">TIPO DE BIEN O SERVICIO</label> 
                                                                <select id="slcTipoBnSv" name="slcTipoBnSv" class="form-control form-control-sm py-0 sm bg-input" aria-label="Default select example">
                                                                    <?php
                                                                    foreach ($tbnsv as $tbs) {
                                                                        if ($tbs['id_tipo_b_s'] !== $adq_compra['id_tipo_bn_sv']) {
                                                                            echo '<option value="' . $tbs['id_tipo_b_s'] . '">' . $tbs['tipo_compra'] . ' || ' . $tbs['tipo_contrato'] . ' || ' . $tbs['tipo_bn_sv'] . '</option>';
                                                                        } else {
                                                                            echo '<option selected value="' . $tbs['id_tipo_b_s'] . '">' . $tbs['tipo_compra'] . ' || ' . $tbs['tipo_contrato'] . ' || ' . $tbs['tipo_bn_sv'] . '</option>';
                                                                        }
                                                                    }
                                                                    ?>
                                                                </select>
                                                            </div>
                                                        </div>
                                                        <div class="row pt-2">
                                                            <div class="col-md-12 mb-3">
                                                                <label for="txtObjeto" class="small">OBJETO</label>
                                                                <textarea id="txtObjeto" type="text" name="txtObjeto" class="form-control form-control-sm py-0 sm bg-input" aria-label="Default select example" rows="3" placeholder="Objeto del contrato"><?php echo $adq_compra['objeto'] ?></textarea>
                                                            </div>
                                                        </div>
                                                    </form>
                                                    <div class="text-center pt-2">
                                                        <?php if (($permisos->PermisosUsuario($opciones, 5302, 3) || $id_rol == 1)) { ?>
                                                            <button class="btn btn-primary btn-sm" id="btnUpDataAdqCompra">Actualizar</button>
                                                        <?php } ?>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="accordion-item">
                                            <h2 class="accordion-header" id="headingBnSv">
                                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseBnSv" aria-expanded="false" aria-controls="collapseBnSv">
                                                    <span class="fas fa-shopping-bag fa-lg me-2" style="color: #EC7063;"></span>
                                                    2. BIENES O SERVICIOS
                                                </button>
                                            </h2>
                                            <div id="collapseBnSv" class="accordion-collapse collapse" aria-labelledby="headingBnSv" data-bs-parent="#accordion">
                                                <div class="accordion-body">
                                                    <div id="divEstadoBnSv">
                                                        <?php
                                                        if (!empty($listBnSv)) {
                                                        ?>
                                                            <form id="formDetallesAdq">
                                                                <input type="hidden" name="idAdq" value="<?php echo $id_adq ?>">
                                                                <table id="tableUpAdqBnSv" class="table table-striped table-bordered table-sm nowrap table-hover shadow align-middle" style="width:100%">
                                                                    <thead>
                                                                        <tr>
                                                                            <th id="orderCheck" class="bg-sofia">Seleccionar</th>
                                                                            <th class="bg-sofia">Bien o Servicio</th>
                                                                            <th class="bg-sofia">Cantidad</th>
                                                                            <th class="bg-sofia">Valor Unitario</th>
                                                                        </tr>
                                                                    </thead>
                                                                    <tbody>
                                                                        <?php
                                                                        foreach ($bnsv as $bs) {
                                                                            $id_bs = $bs['id_b_s'];
                                                                            $key = array_search($id_bs, array_column($listBnSv, 'id_bn_sv'));
                                                                            if (false !== $key) {
                                                                                $check = 'checked';
                                                                                $cant = $listBnSv[$key]['cantidad'];
                                                                                $val_c = $listBnSv[$key]['val_estimado_unid'];
                                                                            } else {
                                                                                $check = $cant = $val_c = null;
                                                                            }
                                                                        ?>
                                                                            <tr>
                                                                                <td>
                                                                                    <div class="text-center casilla">
                                                                                        <input type="checkbox" name="check[]" value="<?php echo $bs['id_b_s'] ?>" <?php echo $check ?>>
                                                                                    </div>
                                                                                </td>
                                                                                <td class="text-left"><i><?php echo $bs['bien_servicio'] ?></i></td>
                                                                                <td><input type="number" name="bnsv_<?php echo $bs['id_b_s'] ?>" id="bnsv_<?php echo $bs['id_b_s'] ?>" class="form-control altura bg-input" value="<?php echo $cant ?>"></td>
                                                                                <td><input type="number" name="val_bnsv_<?php echo $bs['id_b_s'] ?>" id="val_bnsv_<?php echo $bs['id_b_s'] ?>" class="form-control altura bg-input" value="<?php echo $val_c ?>"></td>
                                                                            </tr>
                                                                        <?php
                                                                        }
                                                                        ?>
                                                                    </tbody>
                                                                    <tfoot>
                                                                        <tr>
                                                                            <th class="bg-sofia">Seleccionar</th>
                                                                            <th class="bg-sofia">Bien o Servicio</th>
                                                                            <th class="bg-sofia">Cantidad</th>
                                                                            <th class="bg-sofia">Valor Unitario</th>
                                                                        </tr>
                                                                    </tfoot>
                                                                </table>
                                                            </form>
                                                            <div class="text-center pt-2">
                                                                <button class="btn btn-primary btn-sm" id="btnUpDetalAdqCompra">Actualizar</button>
                                                            </div>
                                                        <?php
                                                        } else {
                                                            echo '<div class="p-3 mb-2 bg-warning text-white">AUN NO SE HA AGREGADO NINGÚN BIEN O SERVICIO</div>';
                                                        ?>
                                                            <form id="formDetallesAdq">
                                                                <input type="hidden" name="idAdq" value="<?php echo $id_adq ?>">
                                                                <table id="tableUpAdqBnSv" class="table table-striped table-bordered table-sm nowrap table-hover shadow align-middle" style="width:100%">
                                                                    <thead>
                                                                        <tr>
                                                                            <th class="bg-sofia">Seleccionar</th>
                                                                            <th class="bg-sofia">Bien o Servicio</th>
                                                                            <th class="bg-sofia">Cantidad</th>
                                                                            <th class="bg-sofia">Valor estimado Und.</th>
                                                                        </tr>
                                                                    </thead>
                                                                    <tbody>
                                                                        <?php
                                                                        foreach ($bnsv as $bs) {
                                                                        ?>
                                                                            <tr>
                                                                                <td>
                                                                                    <div class="text-center">
                                                                                        <input type="checkbox" name="check[]" value="<?php echo $bs['id_b_s'] ?>">
                                                                                    </div>
                                                                                </td>
                                                                                <td class="text-left"><i><?php echo $bs['bien_servicio'] ?></i></td>
                                                                                <td><input type="number" name="bnsv_<?php echo $bs['id_b_s'] ?>" id="bnsv_<?php echo $bs['id_b_s'] ?>" class="form-control altura"></td>
                                                                                <td><input type="number" name="val_bnsv_<?php echo $bs['id_b_s'] ?>" id="val_bnsv_<?php echo $bs['id_b_s'] ?>" class="form-control altura"></td>
                                                                            </tr>
                                                                        <?php
                                                                        }
                                                                        ?>
                                                                    </tbody>
                                                                    <tfoot>
                                                                        <tr>
                                                                            <th class="bg-sofia">Seleccionar</th>
                                                                            <th class="bg-sofia">Bien o Servicio</th>
                                                                            <th class="bg-sofia">Cantidad</th>
                                                                            <th class="bg-sofia">Valor estimado Und.</th>
                                                                        </tr>
                                                                    </tfoot>
                                                                </table>
                                                            </form>
                                                            <div class="text-center pt-2">
                                                                <?php if (($permisos->PermisosUsuario($opciones, 5302, 3) || $id_rol == 1)) { ?>
                                                                    <button class="btn btn-primary btn-sm" id="btnUpDetalAdqCompra">Actualizar</button>
                                                                <?php } ?>
                                                            </div>
                                                        <?php
                                                        }
                                                        ?>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="text-center pt-3">
                                        <a type="button" class="btn btn-secondary  btn-sm" href="../lista_adquisiciones.php">Regresar</a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </main>
                    <?php include '../../../footer.php' ?>
                </div>
                <?php include '../../../modales.php' ?>
            </div>
            <?php include '../../../scripts.php' ?>
        </body>

        </html>
<?php
    } else {
        echo 'No se puede editar esta compra';
    }
} else {
    echo 'Error al intentar obtener datos';
} ?>