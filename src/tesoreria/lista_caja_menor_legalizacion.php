<?php
session_start();
if (!isset($_SESSION['user'])) {
    header('Location: ../index.php');
    exit();
}
include '../../config/autoloader.php';


use Src\Common\Php\Clases\Permisos;

$id_rol = $_SESSION['rol'];
$id_user = $_SESSION['id_user'];

$permisos = new Permisos();
$opciones = $permisos->PermisoOpciones($id_user);
include '../financiero/consultas.php';

$id_doc = $_POST['id_doc'] ?? '';
$id_cop = $_POST['id_cop'] ?? '';
$valor_pago = $_POST['valor'] ?? 0;
$fecha_doc = $_POST['fecha'] ?? '';
$valor_descuento = 0;
// Consulta tipo de presupuesto
$cmd = \Config\Clases\Conexion::getConexion();
// Control de fechas
//$fecha_doc = date('Y-m-d');
$fecha_cierre = fechaCierre($_SESSION['vigencia'], 56, $cmd);
$fecha = fechaSesion($_SESSION['vigencia'], $_SESSION['id_user'], $cmd);
$fecha_max = date("Y-m-d", strtotime($_SESSION['vigencia'] . '-12-31'));

try {
    $sql = "SELECT
                `tf`.`id_tercero_api`
                , `ter`.`nom_tercero`
                , `ter`.`nit_tercero`
            FROM
                `tes_facturador` AS `tf`
                LEFT JOIN `tb_terceros` AS `ter`
                    ON (`tf`.`id_tercero_api` = `ter`.`id_tercero_api`)
            WHERE (`tf`.`estado` = 1)";
    $rs = $cmd->query($sql);
    $facturador = $rs->fetchAll();
    $rs->closeCursor();
    unset($rs);
} catch (PDOException $e) {
    echo $e->getCode() == 2002 ? 'Sin Conexión a Mysql (Error: 2002)' : 'Error: ' . $e->getCode();
}
// consultar los conceptos asociados al recuado del arqueo
try {
    $sql = "SELECT `id_concepto_arq`,`concepto` FROM `tes_concepto_arqueo` WHERE `estado` = 1";
    $rs = $cmd->query($sql);
    $conceptos = $rs->fetchAll();
    $rs->closeCursor();
    unset($rs);
} catch (PDOException $e) {
    echo $e->getCode() == 2002 ? 'Sin Conexión a Mysql (Error: 2002)' : 'Error: ' . $e->getCode();
}
// Consultar los arqueos registrados en seg_tes_arqueo_caja
try {
    $sql = "SELECT
                `tes_causa_arqueo`.`id_ctb_doc`
                , `tes_causa_arqueo`.`id_causa_arqueo`
                , `tes_causa_arqueo`.`fecha`
                , `tes_causa_arqueo`.`id_tercero`
                , `tes_causa_arqueo`.`valor_arq`
                , `tes_causa_arqueo`.`valor_fac`
                , `tes_causa_arqueo`.`observaciones`
            FROM
                `tes_facturador`
                INNER JOIN `tes_causa_arqueo` 
                    ON (`tes_facturador`.`id_tercero_api` = `tes_causa_arqueo`.`id_tercero`)
            WHERE (`tes_causa_arqueo`.`id_ctb_doc` = $id_doc)";
    $rs = $cmd->query($sql);
    $arqueos = $rs->fetchAll();
    $rs->closeCursor();
    unset($rs);
} catch (PDOException $e) {
    echo $e->getCode() == 2002 ? 'Sin Conexión a Mysql (Error: 2002)' : 'Error: ' . $e->getCode();
}

$valor_pagar = 0;

?>
<script>
    $('#tableLegalizacionCaja').DataTable({
        dom: "<'row'<'col-md-2'l><'col-md-10'f>>" +
            "<'row'<'col-sm-12'tr>>" +
            "<'row'<'col-sm-12 col-md-5'i><'col-sm-12 col-md-7'p>>",
        language: dataTable_es,
        "order": [
            [0, "desc"]
        ]
    });
    $('#tableLegalizacionCaja').wrap('<div class="overflow" />');
</script>
<div class="px-0">

    <div class="shadow">
        <div class="card-header text-center py-2" style="background-color: #16a085 !important;">
            <h5 class="mb-0" style="color: white;">LISTA DE GASTOS PARA LEGALIZACION DE CAJA MENOR</h5>
        </div>
        <div class="px-3 pt-2">
            <form id="formAddFacturador">
                <div class="row mb-2">
                    <div class="col-md-8">
                        <label for="id_facturador" class="small">TIPO DE GASTO</label>
                        <div id="divBanco">
                            <select name="id_facturador" id="id_facturador" class="form-control form-control-sm bg-input" required onchange="calcularCopagos2(this)">
                                <option value="0">--Seleccione--</option>
                                <?php foreach ($facturador as $fact) {
                                    $nombre = $fact['nom_tercero'] ?? '---';
                                    $cc = $fact['nit_tercero'] ?? '---';
                                    echo '<option value="' . $fact['id_tercero_api'] . '">' . $nombre . ' -> ' . $cc . '</option>';
                                }
                                ?>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <label for="valor_arq" class="small">VALOR</label>
                        <input type="text" name="valor_arq" id="valor_arq" class="form-control form-control-sm bg-input" value="<?php echo $valor_pagar; ?>" required style="text-align: right;" onkeyup="NumberMiles(this)" ondblclick="copiarValor()" onchange="validarDiferencia()">
                        <button type="submit" class="btn btn-primary btn-sm" id="registrarMvtoDetalle">+</button>
                    </div>
                </div>
            </form>
            <br>
            <table id="tableLegalizacionCaja" class="table table-striped table-bordered table-sm table-hover shadow" style="width: 100%;">
                <thead>
                    <tr>
                        <th class="w-60">Tipo de gasto</th>
                        <th class="w-20">Valor</th>
                        <th class="w-20">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <div id="datostabla">
                        <?php
                        foreach ($arqueos as $ce) {
                            //$id_doc = $ce['id_ctb_doc'];
                            $fecha = date("Y-m-d", strtotime($ce['fecha']));
                            $id = $ce['id_causa_arqueo'];
                            if ($fecha > $fecha_cierre && ($permisos->PermisosUsuario($opciones, 5601, 3) || $id_rol == 1)) {
                                $borrar = '<a value="' . $id_doc . '" onclick="eliminarRecaduoArqeuo(' . $id . ')" class="btn btn-outline-danger btn-xs rounded-circle me-1 shadow editar" title="Causar"><span class="fas fa-trash-alt"></span></a>';
                                $acciones = '<button  class="btn btn-outline-pry btn-sm" type="button" id="dropdownMenuButton" data-bs-toggle="dropdown" aria-haspopup="false" aria-expanded="false">
                            ...
                            </button>
                            <div class="dropdown-menu" aria-labelledby="dropdownMenuButton">
                            <a value="' . $id_doc . '" class="dropdown-item sombra carga" href="#">Historial</a>
                            </div>';
                            } else {
                                $editar = null;
                                $detalles = null;
                            }
                        ?>
                            <tr id="<?php echo $id; ?>">
                                <td><?php echo $fecha; ?></td>
                                <td class="text-start"><?php echo $ce['facturador']; ?></td>
                                <td> <?php echo $ce['id_tercero']; ?></td>
                                <td> <?php echo number_format($ce['valor_fac'], 2, '.', ','); ?></td>
                                <td> <?php echo number_format($ce['valor_arq'], 2, '.', ','); ?></td>
                                <td> <?php echo $borrar .  $acciones; ?></td>

                            </tr>
                        <?php
                        }
                        ?>
                    </div>
                </tbody>
            </table>
            <div class="text-end py-3">
                <a type="button" class="btn btn-success btn-sm" onclick="GuardarLegCajaMenor()">Cerrar</a>
                <a type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cerrar</a>
            </div>
        </div>
    </div>