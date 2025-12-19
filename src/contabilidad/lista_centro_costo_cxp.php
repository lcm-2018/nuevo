<?php
session_start();
if (!isset($_SESSION['user'])) {
    header('Location: ../index.php');
    exit();
}
include '../../config/autoloader.php';
$id_rol = $_SESSION['rol'];
$id_user = $_SESSION['id_user'];

use Config\Clases\Plantilla;
use Src\Common\Php\Clases\Permisos;

$permisos = new Permisos();
$opciones = $permisos->PermisoOpciones($id_user);

$id_doc = isset($_POST['id']) ? $_POST['id'] : exit('Acceso no disponible');
$id_detalle = isset($_POST['id_detalle']) ? $_POST['id_detalle'] : 0;

try {
    $cmd = \Config\Clases\Conexion::getConexion();
    $sql = "SELECT
                `ctb_causa_costos`.`id`
                , `ctb_causa_costos`.`id_ctb_doc`
                , `ctb_causa_costos`.`valor`
                , `tb_sedes`.`nom_sede`
                , `tb_municipios`.`nom_municipio`
                , `far_centrocosto_area`.`nom_area` AS `descripcion`
            FROM
                `ctb_causa_costos`
                INNER JOIN `far_centrocosto_area` 
                    ON (`ctb_causa_costos`.`id_area_cc` = `far_centrocosto_area`.`id_area`)
                INNER JOIN `tb_sedes` 
                    ON (`far_centrocosto_area`.`id_sede` = `tb_sedes`.`id_sede`)
                INNER JOIN `tb_municipios` 
                    ON (`tb_sedes`.`id_municipio` = `tb_municipios`.`id_municipio`)
            WHERE (`ctb_causa_costos`.`id_ctb_doc` = $id_doc)";
    $rs = $cmd->query($sql);
    $rubros = $rs->fetchAll();
    $rs->closeCursor();
    unset($rs);
    $cmd = null;
} catch (PDOException $e) {
    echo $e->getCode() == 2002 ? 'Sin Conexión a Mysql (Error: 2002)' : 'Error: ' . $e->getCode();
}
if ($id_detalle > 0) {
    try {
        $cmd = \Config\Clases\Conexion::getConexion();
        $sql = "SELECT
                    `ctb_causa_costos`.`id`
                    , `ctb_causa_costos`.`id_area_cc`
                    , `tb_municipios`.`nom_municipio`
                    , `tb_municipios`.`id_municipio`
                    , `tb_sedes`.`id_sede`
                    , `far_centrocosto_area`.`id_centrocosto`
                    , `ctb_causa_costos`.`valor`
                FROM
                    `ctb_causa_costos`
                    INNER JOIN `far_centrocosto_area` 
                        ON (`ctb_causa_costos`.`id_area_cc` = `far_centrocosto_area`.`id_area`)
                    INNER JOIN `tb_sedes` 
                        ON (`far_centrocosto_area`.`id_sede` = `tb_sedes`.`id_sede`)
                    INNER JOIN `tb_municipios` 
                        ON (`tb_sedes`.`id_municipio` = `tb_municipios`.`id_municipio`)
                WHERE (`ctb_causa_costos`.`id` = $id_detalle)";
        $rs = $cmd->query($sql);
        $data = $rs->fetch();
        $id_municipio = $data['id_municipio'];
        $id_sede = $data['id_sede'];
        $id_cc = $data['id_area_cc'];
        $municipio = $data['nom_municipio'];
        $value_cc = $data['valor'];
        $cmd = null;
    } catch (PDOException $e) {
        echo $e->getCode() == 2002 ? 'Sin Conexión a Mysql (Error: 2002)' : 'Error: ' . $e->getCode();
    }
    try {
        $cmd = \Config\Clases\Conexion::getConexion();
        $sql = "SELECT `id_sede`, `nom_sede` as `nombre` FROM `tb_sedes` WHERE `id_municipio` = $id_municipio";
        $rs = $cmd->query($sql);
        $sedes = $rs->fetchAll();
        $rs->closeCursor();
        unset($rs);
        $cmd = null;
    } catch (PDOException $e) {
        echo $e->getCode() == 2002 ? 'Sin Conexión a Mysql (Error: 2002)' : 'Error: ' . $e->getCode();
    }
    try {
        $cmd = \Config\Clases\Conexion::getConexion();
        $sql = "SELECT
                    `id_area`
                    , `nom_area`
                FROM
                    `far_centrocosto_area`
                WHERE (`id_sede` = $id_sede)";
        $rs = $cmd->query($sql);
        $centros = $rs->fetchAll();
        $rs->closeCursor();
        unset($rs);
        $cmd = null;
    } catch (PDOException $e) {
        echo $e->getCode() == 2002 ? 'Sin Conexión a Mysql (Error: 2002)' : 'Error: ' . $e->getCode();
    }
} else {
    $id_municipio = 0;
    $municipio = '';
    $id_sede = 0;
    $id_cc = 0;
    $sedes = [];
    $centros = [];
}
try {
    $cmd = \Config\Clases\Conexion::getConexion();
    $sql = "SELECT SUM(`valor_pago`) AS `valor_pago` FROM `ctb_factura` WHERE (`id_ctb_doc` = $id_doc)";
    $rs = $cmd->query($sql);
    $valor_factura = $rs->fetch();
    $valor_max = !empty($valor_factura) ? $valor_factura['valor_pago'] : 0;
    $cmd = null;
} catch (PDOException $e) {
    echo $e->getCode() == 2002 ? 'Sin Conexión a Mysql (Error: 2002)' : 'Error: ' . $e->getCode();
}
$val_cc = 0;
foreach ($rubros as $r) {
    if ($r['id'] != $id_detalle) {
        $val_cc += $r['valor'];
    }
}
$min = 0;
$max = $valor_max - $val_cc;
$max = $max < 0 ? 0 : $max;
?>
<script>
    $('#tableCausacionCostos').DataTable({
        dom: "<'row'<'col-md-2'l><'col-md-10'f>>" +
            "<'row'<'col-sm-12'tr>>" +
            "<'row'<'col-sm-12 col-md-5'i><'col-sm-12 col-md-7'p>>",
        language: dataTable_es,
        "order": [
            [0, "desc"]
        ]
    });
    $('#tableCausacionCostos').wrap('<div class="overflow" />');
</script>
<div class="px-0">
    <div class="shadow ">
        <div class="card-header py-2 text-center" style="background-color: #16a085 !important;">
            <h5 class="mb-0" style="color: white;">LISTA DE CENTROS DE COSTO DE CUENTA POR PAGAR </h5>
        </div>
        <div class="px-4">
            <form id="formGuardaCentroCosto" class="mb-3">
                <input type="hidden" name="id_doc" id="id_doc" value="<?php echo $id_doc; ?>">
                <input type="hidden" name="id_detalle" id="id_detalle" value="<?php echo $id_detalle; ?>">
                <div class="row mb-2">
                    <div class="col-md-3">
                        <label for="municipio" class="small">MUNICIPIO</label>
                        <input type="text" name="municipio" id="municipio" class="form-control form-control-sm bg-input" value="<?= $municipio ?>" onchange="mostrarSedes();" required>
                        <input type="hidden" name="id_municipio" id="id_municipio" value="<?= $id_municipio; ?>">
                    </div>
                    <div class="col-md-3" id="divSede">
                        <label for="id_sede" class="small">SEDE</label>
                        <select type="text" name="id_sede" id="id_sede" class="form-control form-control-sm bg-input" onchange="mostrarCentroCostos(value);">
                            <option value="0">--Seleccione--</option>
                            <?php
                            foreach ($sedes as $s) {
                                $slc = $s['id_sede'] == $id_sede ? 'selected' : '';
                                echo '<option value="' . $s['id_sede'] . '" ' . $slc . '>' . $s['nombre'] . '</option>';
                            }
                            ?>
                        </select>
                    </div>
                    <div class="col-md-3" id="divCosto">
                        <label for="id_cc" class="small">CENTRO DE COSTO</label>
                        <select type="text" name="id_cc" id="id_cc" class="form-control form-control-sm bg-input">
                            <option value="0">--Seleccione--</option>
                            <?php
                            foreach ($centros as $c) {
                                $slc = $c['id_area'] == $id_cc ? 'selected' : '';
                                echo '<option value="' . $c['id_area'] . '" ' . $slc . '>' . $c['nom_area'] . '</option>';
                            }
                            ?>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label for="valor_cc" class="small">VALOR CC</label>
                        <input type="text" name="valor_cc" id="valor_cc" min="<?= $min; ?>" max="<?= $max; ?>" class="form-control form-control-sm bg-input" required style="text-align: right;" onkeyup="NumberMiles(this)" value="<?= isset($value_cc) ? $value_cc : $max; ?>">
                    </div>
                </div>
            </form>
            <table id="tableCausacionCostos" class="table table-striped table-bordered table-sm table-hover shadow" style="width: 100%;">
                <thead>
                    <tr>
                        <th class="bg-sofia" style="width: 30%;">Municipio</th>
                        <th class="bg-sofia" style="width: 35%;">Sede</th>
                        <th class="bg-sofia" style="width: 20%;">Centro de costo</th>
                        <th class="bg-sofia" style="width: 20%;">Valor</th>
                        <th class="bg-sofia" style="width: 15%;">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <div id="datostabla">
                        <?php
                        foreach ($rubros as $ce) {
                            $id_doc = $ce['id_ctb_doc'];
                            $id = $ce['id'];
                            $editar = null;
                            $detalles = null;
                            if ($permisos->PermisosUsuario($opciones, 5501, 3)  || $id_rol == 1) {
                                $editar = '<a value="' . $id_doc . '" onclick="editarCentroCosto(' . $id . ')" class="btn btn-outline-primary btn-xs rounded-circle me-1 shadow editar" title="Editar"><span class="fas fa-pencil-alt "></span></a>';
                            }
                            if ($permisos->PermisosUsuario($opciones, 5501, 4)  || $id_rol == 1) {
                                $eliminar = '<a value="' . $id_doc . '" onclick="eliminarCentroCosto(' . $id . ')" class="btn btn-outline-danger btn-xs rounded-circle me-1 shadow editar" title="Eliminar"><span class="fas fa-trash-alt "></span></a>';
                            }
                            if (true) {
                                $acciones = '<button  class="btn btn-outline-pry btn-sm" type="button" id="dropdownMenuButton" data-toggle="dropdown" aria-haspopup="false" aria-expanded="false">
                            ...
                            </button>
                            <div class="dropdown-menu" aria-labelledby="dropdownMenuButton">
                            <a value="' . $id_doc . '" class="dropdown-item sombra carga" href="#">Historial</a>
                            </div>';
                            }
                            $valor = number_format($ce['valor'], 2, '.', ',');
                            $acciones = null;
                        ?>
                            <tr id="<?php echo $id; ?>">
                                <td class="text-start"><?php echo $ce['nom_municipio']; ?></td>
                                <td class="text-start"><?php echo $ce['nom_sede']; ?></td>
                                <td class="text-start"> <?php echo $ce['descripcion'];; ?></td>
                                <td class="text-end"> <?php echo number_format($ce['valor'], 2, '.', ','); ?></td>
                                <td class="text-center"> <?php echo $editar . $eliminar .  $acciones; ?></td>

                            </tr>
                        <?php
                        }
                        ?>
                    </div>
                </tbody>
            </table>
        </div>
    </div>
    <div class="text-end pt-3">
        <button type="button" class="btn btn-primary btn-sm" onclick="guardarCostos(this)">Guardar</button>
        <a type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal"> Cerrar</a>
    </div>
</div>
<?php
