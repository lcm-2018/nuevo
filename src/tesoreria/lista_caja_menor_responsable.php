<?php
session_start();
if (!isset($_SESSION['user'])) {
    header('Location: ../index.php');
    exit();
}
include '../../config/autoloader.php';
include '../financiero/consultas.php';

$id_caja = isset($_POST['id_caja'])  ? $_POST['id_caja'] : exit('Acceso no permitido');
$id_detalle = isset($_POST['id_detalle']) ? $_POST['id_detalle'] : 0;


$cmd = \Config\Clases\Conexion::getConexion();


$fecha_cierre = fechaCierre($_SESSION['vigencia'], 56, $cmd);
$fecha = fechaSesion($_SESSION['vigencia'], $_SESSION['id_user'], $cmd);
$fecha_max = date("Y-m-d", strtotime($_SESSION['vigencia'] . '-12-31'));

try {
    $sql = "SELECT
                    `tr`.`id_caja_respon`
                    , `tr`.`id_caja_const`
                    , `tr`.`id_terceros_api`
                    , `tr`.`fecha_ini`
                    , `tr`.`fecha_fin`
                    , `tr`.`estado`
                    , `ter`.`nom_tercero`
                    , `ter`.`nit_tercero`
                FROM `tes_caja_respon` AS `tr`
                LEFT JOIN `tb_terceros` AS `ter`
                    ON (`tr`.`id_terceros_api` = `ter`.`id_tercero_api`)
                WHERE `tr`.`id_caja_const` = $id_caja";
    $rs = $cmd->query($sql);
    $responsables = $rs->fetchAll();
    $rs->closeCursor();
    unset($rs);
} catch (PDOException $e) {
    echo $e->getCode() == 2002 ? 'Sin Conexión a Mysql (Error: 2002)' : 'Error: ' . $e->getCode();
}
$reg = 0;

//detalle 
try {
    $sql = "SELECT
                    `tr`.`id_caja_respon`
                    , `tr`.`id_caja_const`
                    , `tr`.`id_terceros_api`
                    , `tr`.`fecha_ini`
                    , `tr`.`fecha_fin`
                    , `tr`.`estado`
                    , `ter`.`nom_tercero`
                    , `ter`.`nit_tercero`
                FROM `tes_caja_respon` AS `tr`
                LEFT JOIN `tb_terceros` AS `ter`
                    ON (`tr`.`id_terceros_api` = `ter`.`id_tercero_api`)
                WHERE `tr`.`id_caja_respon` = $id_detalle";
    $rs = $cmd->query($sql);
    $detalle = $rs->fetch(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    echo $e->getCode() == 2002 ? 'Sin Conexión a Mysql (Error: 2002)' : 'Error: ' . $e->getCode();
}
if (empty($detalle)) {
    $detalle = [
        'id_caja_respon' => 0,
        'id_caja_const' => $id_caja,
        'id_terceros_api' => 0,
        'fecha_ini' => $fecha,
        'fecha_fin' => $fecha,
        'estado' => 1,
        'nom_tercero' => '---',
        'nit_tercero' => '---'
    ];
}
?>
<script>
    $('#tableResponsableCaja').DataTable({
        dom: "<'row'<'col-md-2'l><'col-md-10'f>>" +
            "<'row'<'col-sm-12'tr>>" +
            "<'row'<'col-sm-12 col-md-5'i><'col-sm-12 col-md-7'p>>",
        language: dataTable_es,
        "order": [
            [0, "desc"]
        ]
    });
    $('#tableResponsableCaja').wrap('<div class="overflow" />');
</script>
<div class="px-0">

    <div class="shadow">
        <div class="card-header text-center py-2" style="background-color: #16a085 !important;">
            <h5 class="mb-0" style="color: white;">RESPONSABLE MANEJO DE CAJA MENOR </h5>
        </div>
        <div class="p-3">
            <form id="formAddResponsableCaja">
                <input type="hidden" id="id_caja" name="id_caja" value="<?php echo $id_caja; ?>">
                <input type="hidden" id="id_detalle" name="id_detalle" value="<?php echo $id_detalle; ?>">
                <input type="hidden" id="reg" value="<?php echo $reg; ?>">
                <div class="row mb-2">
                    <div class="col-md-6">
                        <label for="tercerocrp" class="small">RESPONSABLE</label>
                        <input type="text" id="tercerocrp" class="form-control form-control-sm bg-input" value="<?php echo $nombre ?>" required>
                        <input type="hidden" name="id_tercero" id="id_tercero" value="<?php echo $detalle['id_terceros_api']; ?>">
                    </div>
                    <div class="col-md-3">
                        <label for="fecha_ini" class="small">FECHA INICIAL</label>
                        <input type="date" name="fecha_ini" id="fecha_ini" class="form-control form-control-sm bg-input" min="<?php echo $fecha_cierre; ?>" max="<?php echo $fecha_max; ?>" value="<?php echo $detalle['fecha_ini']; ?>">
                    </div>
                    <div class="col-md-3">
                        <label for="fecha_fin" class="small">FECHA FINAL</label>
                        <input type="date" name="fecha_fin" id="fecha_fin" class="form-control form-control-sm bg-input" min="<?php echo $fecha_cierre; ?>" max="<?php echo $fecha_max; ?>" value="<?php echo  $detalle['fecha_fin']; ?>">
                    </div>
                </div>
            </form>
            <table id="tableResponsableCaja" class="table table-striped table-bordered table-sm table-hover shadow" style="width: 100%">
                <thead>
                    <tr>
                        <th>Responsable</th>
                        <th>Fecha inicial</th>
                        <th>Fecha final</th>
                        <th>Estado</th>
                        <th>Acciones </th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    foreach ($responsables as $r) {
                        $tercero = $r['nom_tercero'] ?? '---';
                        $editar = '<a class="btn btn-outline-primary btn-xs rounded-circle me-1 shadow"  onclick="EditResponsableCaja(' . $r['id_caja_respon'] . ')"><span class="fas fa-pencil-alt"></span></a>';
                        $estado =  $r['estado'];
                        if ($estado == 1) {
                            $title = 'Activo';
                            $icono = 'on';
                            $color = '#37E146';
                        } else {
                            $title = 'Inactivo';
                            $icono = 'off';
                            $color = 'gray';
                        }
                        $boton = '<a class="btn btn-sm rounded-circle estado" title="' . $title . '" onclick="ModEstadoResposableCaja(' . $r['id_caja_respon'] . ',' . $estado . ')"><span class="fas fa-toggle-' . $icono . ' fa-2x" style="color:' . $color . ';"></span></a>';
                        echo '<tr>';
                        echo '<td class="text-start">' . $tercero . '</td>';
                        echo '<td>' . $r['fecha_ini'] . '</td>';
                        echo '<td>' . $r['fecha_fin'] . '</td>';
                        echo '<td>' . $boton . '</td>';
                        echo '<td>' . $editar . '</td>';
                        echo '</tr>';
                    }
                    ?>
                </tbody>
            </table>
            <div class="text-end pt-3">
                <a type="button" class="btn btn-success btn-sm" onclick="GuardaRespCaja()">Guardar</a>
                <a type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cerrar</a>
            </div>

        </div>


    </div>