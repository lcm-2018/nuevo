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

try {
    $cmd = \Config\Clases\Conexion::getConexion();
} catch (PDOException $e) {
    echo $e->getCode() == 2002 ? 'Sin Conexión a Mysql (Error: 2002)' : 'Error: ' . $e->getCode();
}

try {
    $sql = "SELECT
                `tes_causa_arqueo`.`id_causa_arqueo`
                ,`ctb_doc`.`id_ctb_doc`
                ,`ctb_doc`.`id_manu`
                , `ctb_doc`.`fecha`
                , `ctb_doc`.`id_tercero`
                , `ctb_doc`.`detalle`
                , SUM(`tes_causa_arqueo`.`valor_arq`) as valor
                , `tb_terceros`.`nom_tercero`
                , `tb_terceros`.`nit_tercero`
            FROM
                `tes_causa_arqueo`
                INNER JOIN `ctb_doc` 
                    ON (`tes_causa_arqueo`.`id_ctb_doc` = `ctb_doc`.`id_ctb_doc`)
                LEFT JOIN `tb_terceros`
                    ON (`ctb_doc`.`id_tercero` = `tb_terceros`.`id_tercero_api`)
            WHERE (`tes_causa_arqueo`.`estado` =0) 
            GROUP BY `ctb_doc`.`id_manu`;";
    $rs = $cmd->query($sql);
    $listado = $rs->fetchAll();
    $rs->closeCursor();
    unset($rs);
} catch (PDOException $e) {
    echo $e->getCode() == 2002 ? 'Sin Conexión a Mysql (Error: 2002)' : 'Error: ' . $e->getCode();
}
?>
<script>
    $('#tableObligacionesPago').DataTable({
        language: dataTable_es,
        "order": [
            [0, "desc"]
        ]
    });
    $('#tableObligacionesPago').wrap('<div class="overflow" />');
</script>
<div class="px-0">
    <div class="shadow">
        <div class="card-header text-center py-2" style="background-color: #16a085 !important;">
            <h5 class="mb-0" style="color: white;">LISTA DE ARQUEOS DE CAJA PENDIENTE CONSIGNACION</h5>
        </div>
        <div class="pb-3"></div>
        <div class="px-3">
            <table id="tableObligacionesPago" class="table table-striped table-bordered nowrap table-sm table-hover shadow" style="width: 100%;">
                <thead>
                    <tr>
                        <th class="bg-sofia">Num </th>
                        <th class="bg-sofia">Fecha</th>
                        <th class="bg-sofia">Tercero</th>
                        <th class="bg-sofia">Doc</th>
                        <th class="bg-sofia">Valor</th>
                        <th class="bg-sofia">Acciones</th>

                    </tr>
                </thead>
                <tbody>
                    <?php
                    foreach ($listado as $ce) {
                        $id_doc = $ce['id_ctb_doc'];
                        $fecha = date('Y-m-d', strtotime($ce['fecha']));
                        // Consulta terceros en la api
                        $tercero = $ce['nom_tercero'] ?? '---';
                        $ccnit = $ce['nit_tercero'] ?? '---';
                        // fin api terceros

                        if (true) {
                            $editar = '<a value="' . $id_doc . '" onclick="cargarListaArqueoConsignacion(' . $id_doc . ')" class="btn btn-outline-success btn-xs rounded-circle me-1 shadow editar" title="Causar"><span class="fas fa-plus-square"></span></a>';
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
                        $acciones = null;
                    ?>
                        <tr>
                            <td class="text-start"><?= $ce['id_manu']  ?></td>
                            <td class="text-start"><?= $fecha;  ?></td>
                            <td class="text-start"><?= $tercero;   ?></td>
                            <td class="text-start"><?= $ccnit; ?></td>
                            <td class="text-end"><?= number_format($ce['valor'], 2, ',', '.') ?></td>
                            <td class=" text-center"> <?= $editar .  $acciones; ?></td>
                        </tr>
                    <?php
                    }
                    ?>
                </tbody>
            </table>
        </div>
    </div>
    <div class="text-end pt-3">
        <a type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal"> Cerrar</a>
    </div>
</div>