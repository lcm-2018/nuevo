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

$id_doc = isset($_POST['id_doc']) ? $_POST['id_doc'] : 0;
$id_tercero = isset($_POST['id_tercero']) ? $_POST['id_tercero'] : 0;
$id_cop = isset($_POST['id_cop']) ? $_POST['id_cop'] : 0;
// Consulta tipo de presupuesto
$cmd = \Config\Clases\Conexion::getConexion();

try {
    if ($_SESSION['pto'] == '1') {
        $sql = "SELECT
                `t1`.`id_pto_cop_det`
                , SUM(`t1`.`val_cop`) AS `val_cop`
                , SUM(`t1`.`val_pag`) AS `val_pag`
                , `t1`.`id_manu`
                , `t1`.`id_ctb_doc`
                , `t1`.`fecha`
            FROM 
                (SELECT
                    `pto_cop_detalle`.`id_pto_cop_det`
                    , IFNULL(`pto_cop_detalle`.`valor`,0) - IFNULL(`pto_cop_detalle`.`valor_liberado`,0) AS `val_cop`
                    , IFNULL(`pagado`.`val_pag`,0) AS `val_pag` 
                    , `ctb_doc`.`id_manu`
                    , `ctb_doc`.`id_ctb_doc`
                    , `ctb_doc`.`fecha`
                FROM
                    `pto_cop_detalle`
                INNER JOIN `ctb_doc` 
                    ON (`pto_cop_detalle`.`id_ctb_doc` = `ctb_doc`.`id_ctb_doc`)
                LEFT JOIN 
                    (SELECT
                        `id_pto_cop_det`
                        , SUM(IFNULL(`valor`,0) - IFNULL(`valor_liberado`,0)) AS `val_pag`
                    FROM
                        `pto_pag_detalle`
                        INNER JOIN `ctb_doc` 
                            ON (`pto_pag_detalle`.`id_ctb_doc` = `ctb_doc`.`id_ctb_doc`)
                    WHERE (`id_tercero_api` = $id_tercero AND `ctb_doc`.`estado` = 2)
                    GROUP BY `id_pto_cop_det`) AS `pagado`
                    ON (`pto_cop_detalle`.`id_pto_cop_det` = `pagado`.`id_pto_cop_det`)
                WHERE `pto_cop_detalle`.`id_tercero_api` = $id_tercero AND `ctb_doc`.`estado` = 2) AS `t1`
            WHERE `val_cop` > `val_pag`
            GROUP BY `t1`.`id_ctb_doc`";
    } else {
        $sql = "SELECT 
                    'e' AS `id_pto_cop_det`
                    , `causado`.`valor` AS `val_cop`
                    , IFNULL(`pagado`.`valor`,0) AS `val_pag`
                    , `ctb_doc`.`id_manu` 
                    , `ctb_doc`.`id_ctb_doc`
                    , `ctb_doc`.`fecha`
                FROM 
                    `ctb_doc`
                    INNER JOIN
                        (SELECT
                            `ctb_libaux`.`id_ctb_doc`
                            , SUM(`ctb_libaux`.`debito`) AS `valor`
                        FROM
                            `ctb_libaux`
                            INNER JOIN `ctb_doc` 
                            ON (`ctb_libaux`.`id_ctb_doc` = `ctb_doc`.`id_ctb_doc`)
                        WHERE (`ctb_doc`.`id_ctb_doc_tipo3` IS NULL AND `ctb_doc`.`id_tipo_doc` = 3 AND `ctb_doc`.`estado` = 2)
                        GROUP BY `ctb_libaux`.`id_ctb_doc`) AS `causado`
                        ON(`causado`.`id_ctb_doc` = `ctb_doc`.`id_ctb_doc`)
                    LEFT JOIN
                        (SELECT
                            `ctb_libaux`.`id_ctb_doc`
                            , `ctb_doc`.`id_ctb_doc_tipo3`
                            , SUM(`ctb_libaux`.`debito`) AS `valor`
                        FROM
                            `ctb_libaux`
                            INNER JOIN `ctb_doc` 
                            ON (`ctb_libaux`.`id_ctb_doc` = `ctb_doc`.`id_ctb_doc`)
                        WHERE (`ctb_doc`.`id_ctb_doc_tipo3` > 0 AND `ctb_doc`.`estado` > 1)
                        GROUP BY `ctb_libaux`.`id_ctb_doc`) AS `pagado`
                        ON(`causado`.`id_ctb_doc` = `pagado`.`id_ctb_doc_tipo3`)
                WHERE `ctb_doc`.`id_tercero` = $id_tercero";
    }
    $rs = $cmd->query($sql);
    $causaciones = $rs->fetchAll();
    $rs->closeCursor();
    unset($rs);
} catch (PDOException $e) {
    echo $e->getCode() == 2002 ? 'Sin Conexión a Mysql (Error: 2002)' : 'Error: ' . $e->getCode();
}

?>
<script>
    $('#tableCausacionPagos').DataTable({
        language: dataTable_es,
        "order": [
            [0, "desc"]
        ]

    });
    $('#tableCausacionPagos').wrap('<div class="overflow" />');
</script>
<div class="px-0">

    <div class="shadow">
        <div class="card-header text-center py-2" style="background-color: #16a085 !important;">
            <h5 class="mb-0" style="color: white;">LISTA DE CAUSACIONES PARA PAGO DEL TERCERO</h5>
        </div>
        <div class="px-3 pt-2">
            <table id="tableCausacionPagos" class="table table-striped table-bordered table-sm table-hover shadow" style="width: 100%;">
                <thead>
                    <tr>
                        <th class="bg-sofia">No causación</th>
                        <th class="bg-sofia">Fecha</th>
                        <th class="bg-sofia">Valor causado</th>
                        <th class="bg-sofia">Valor Pagos</th>
                        <th class="bg-sofia">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <div id="datostabla">
                        <?php
                        foreach ($causaciones as $ce) {
                            $id = $ce['id_ctb_doc'];
                            $fecha = $ce['fecha'];

                            if ($permisos->PermisosUsuario($opciones, 5601, 3) || $id_rol == 1) {
                                $editar = '<button value="' . $id_doc . '" onclick="cargaRubrosPago(' . $id . ',this)" class="btn btn-outline-info btn-xs rounded-circle me-1 shadow" title="Causar"><span class="fas fa-chevron-circle-down"></span></a>';
                                $borrar = '<a value="' . $id_doc . '" onclick="eliminarFormaPago(' . $id_doc . ')" class="btn btn-outline-danger btn-xs rounded-circle me-1 shadow editar" title="Causar"><span class="fas fa-trash-alt"></span></a>';
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
                            $saldo = $ce['val_cop'] - $ce['val_pag'];
                            if ($saldo == 0) {
                                $editar = null;
                            }
                            $fecha_doc = date('Y-m-d',  strtotime($fecha));
                        ?>
                            <tr id="<?php echo $id; ?>">
                                <td class="text-start"><?php echo $ce['id_manu']; ?></td>
                                <td class="text-start"><?php echo $fecha_doc;  ?></td>
                                <td class="text-end">$ <?php echo number_format($ce['val_cop'], 2, '.', ','); ?></td>
                                <td class="text-end">$ <?php echo number_format($ce['val_pag'], 2, '.', ','); ?></td>
                                <td class="text-center"> <?php echo $editar .  $acciones; ?></td>

                            </tr>
                        <?php
                        }
                        ?>
                    </div>
                </tbody>
            </table>
            <div class="text-end py-3">
                <a type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cerrar</a>
            </div>

        </div>


    </div>
    <?php
