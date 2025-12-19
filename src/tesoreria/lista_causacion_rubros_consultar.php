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

$id_ctb_doc = isset($_POST['id_doc']) ? $_POST['id_doc'] : exit('Acceso no autorizado');
// Consulta tipo de presupuesto
try {
    $cmd = \Config\Clases\Conexion::getConexion();
    $sql = "SELECT
                `pto_documento_detalles`.`rubro`
                , `pto_cargue`.`nom_rubro`
                , `pto_documento_detalles`.`valor`
                , `pto_documento_detalles`.`id_detalle`
            FROM
                `ctb_doc`
                INNER JOIN `pto_documento_detalles` 
                    ON (`ctb_doc`.`id_ctb_doc` = `pto_documento_detalles`.`id_ctb_doc`)
                INNER JOIN `pto_cargue` 
                    ON (`pto_documento_detalles`.`rubro` = `pto_cargue`.`cod_pptal`)
            WHERE (`pto_cargue`.`vigencia` = {$_SESSION['vigencia']} AND `ctb_doc`.`id_ctb_doc` = $id_ctb_doc);";
    $rs = $cmd->query($sql);
    $rubros = $rs->fetchAll();
    $rs->closeCursor();
    unset($rs);
} catch (PDOException $e) {
    echo $e->getCode() == 2002 ? 'Sin Conexión a Mysql (Error: 2002)' : 'Error: ' . $e->getCode();
}
?>
<script>
    $('#tableContrtacionRp').DataTable({
        dom: "<'row'<'col-md-2'l><'col-md-10'f>>" +
            "<'row'<'col-sm-12'tr>>" +
            "<'row'<'col-sm-12 col-md-5'i><'col-sm-12 col-md-7'p>>",
        language: dataTable_es,
        "order": [
            [0, "desc"]
        ]
    });
    $('#tableContrtacionRpRubros').wrap('<div class="overflow" />');
</script>
<div class="px-0">

    <div class="shadow">
        <div class="card-header text-center py-2" style="background-color: #16a085 !important;">
            <h5 class="mb-0" style="color: white;">LISTA DE REGISTROS PRESUPUESTALES PARA PAGO <?php echo '' ?></h5>
        </div>
        <div class="pb-3"></div>
        <form id="rubrosPagar">
            <div class="px-3">
                <table id="tableContrtacionRpRubros" class="table table-striped table-bordered table-sm table-hover shadow" style="width: 100%;">
                    <thead>
                        <tr>
                            <th style="width: 45%;">Rubro</th>
                            <th style="width: 20%;">Valor Rp</th>
                            <th style="width: 15%;">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>

                        <?php
                        foreach ($rubros as $ce) {
                            $id_pto_mvto = $ce['id_pto_mvto'];
                            if ((intval($permisos['editar'])) === 1) {
                                $editar = '<a value="' . $id_ctb_doc . '"  class="btn btn-outline-success btn-xs rounded-circle me-1 shadow editar" title="Causar"><span class="fas fa-print"></span></a>';
                                $acciones = '<button  class="btn btn-outline-pry btn-sm" type="button" id="dropdownMenuButton" data-bs-toggle="dropdown" aria-haspopup="false" aria-expanded="false">
                            ...
                            </button>
                            <div class="dropdown-menu" aria-labelledby="dropdownMenuButton">
                            <a value="' . $id_ctb_doc . '" class="dropdown-item sombra carga" href="#">Historial</a>
                            </div>';
                                $borrar = '<a value="' . $id_pto_mvto  . '" onclick="eliminarImputacionPag(' . $id_pto_mvto  . ')" class="btn btn-outline-danger btn-xs rounded-circle me-1 shadow "  title="Eliminar"><span class="fas fa-trash-alt"></span></a>';
                            } else {
                                $editar = null;
                                $detalles = null;
                            }
                        ?>
                            <tr>
                                <td class="text-start"><?php echo $ce['rubro'] . ' - ' . $ce['nom_rubro']; ?></td>
                                <td class="text-end"><?php echo number_format($ce['valor'], 2, '.', ','); ?></td>
                                <td class="text-center"> <?php echo  $borrar .  $acciones; ?></td>
                            </tr>
                        <?php
                        }
                        ?>

                    </tbody>
                </table>
            </div>
            <div class="text-end pt-3">
                <!--a type="button" class="btn btn-primary btn-sm" onclick="rubrosaPagar(<?php echo $id_ctb_doc; ?>);"> Aceptar</a-->
                <a type="button" class="btn btn-danger btn-sm" data-bs-dismiss="modal">Cancelar</a>


            </div>
        </form>
    </div>


</div>
<?php
$cmd = null;
