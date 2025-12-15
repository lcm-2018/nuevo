<?php
session_start();
if (!isset($_SESSION['user'])) {
    header('Location: ../../index.php');
    exit();
}
// Consulta tipo de presupuesto
include '../../config/autoloader.php';
$id_rol = $_SESSION['rol'];
$id_user = $_SESSION['id_user'];

use Src\Common\Php\Clases\Permisos;

$permisos = new Permisos();
$opciones = $permisos->PermisoOpciones($id_user);
$vigencia = $_SESSION['vigencia'];
try {
    $cmd = \Config\Clases\Conexion::getConexion();
    $sql = "SELECT
                `ctt_adquisiciones`.`id_adquisicion` 
                , `ctt_adquisiciones`.`fecha_adquisicion`
                , `ctt_adquisiciones`.`objeto`
                , `ctt_adquisiciones`.`val_contrato`
                , `ctt_adquisiciones`.`estado`
                , `tb_area_c`.`area`
            FROM
                `ctt_adquisiciones`
            INNER JOIN `tb_area_c` ON (`ctt_adquisiciones`.`id_area` = `tb_area_c`.`id_area`)   
            WHERE (`estado` = 6 AND (`id_cdp` = 1 OR `id_cdp` IS NULL) AND `vigencia` = $vigencia)";
    $rs = $cmd->query($sql);
    $solicitudes = $rs->fetchAll(PDO::FETCH_ASSOC);
    $rs->closeCursor();
    unset($rs);

    $cmd = null;
} catch (PDOException $e) {
    echo $e->getCode() == 2002 ? 'Sin Conexión a Mysql (Error: 2002)' : 'Error: ' . $e->getCode();
}
?>
<script>
    $('#tableContrtacionCdp').DataTable({
        dom: "<'row'<'col-md-2'l><'col-md-10'f>>" +
            "<'row'<'col-sm-12'tr>>" +
            "<'row'<'col-sm-12 col-md-5'i><'col-sm-12 col-md-7'p>>",
        language: dataTable_es,
        "order": [
            [0, "desc"]
        ],
        columnDefs: [{
            targets: [3],
            createdCell: function(td, cellData, rowData, row, col) {
                $(td).css({
                    'white-space': 'normal',
                    'word-wrap': 'break-word',
                    'word-break': 'break-word'
                });
            }
        }]
    });
    $('#tableContrtacionCdp').wrap('<div class="overflow" />');
</script>
<div class="px-0">
    <div class="shadow">
        <div class="card-header py-2 text-center" style="background-color: #16a085 !important;">
            <h5 class="mb-0" style="color: white;">LISTA DE CONTRATOS PARA CDP</h5>
        </div>
        <div class="pb-3"></div>
        <div class="px-3">
            <table id="tableContrtacionCdp" class="table table-striped table-bordered  table-sm table-hover shadow" style="width: 100%;">
                <thead>
                    <tr>
                        <th></th>
                        <th class="bg-sofia">Numero ADQ</th>
                        <th class="bg-sofia">Area</th>
                        <th class="bg-sofia">Objeto</th>
                        <th class="bg-sofia">Valor solicitado</th>
                        <th class="bg-sofia">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    foreach ($solicitudes as $ce) {
                        $id_doc = $ce['id_adquisicion'];
                        if ($permisos->PermisosUsuario($opciones, 5401, 3) || $id_rol == 1) {
                            $editar = '<a value="' . $id_doc . '" onclick="mostrarListaCdp(' . $id_doc . ')" class="btn btn-outline-primary btn-xs rounded-circle me-1 shadow editar" title="Editar"><span class="fas fa-pencil-alt "></span></a>';
                            $detalles = '<a value="' . $id_doc . '" class="btn btn-outline-warning btn-xs rounded-circle me-1 shadow detalles" title="Detalles"><span class="fas fa-eye "></span></a>';
                            $acciones = '<button  class="btn btn-outline-pry btn-sm" type="button" id="dropdownMenuButton" data-bs-toggle="dropdown" aria-haspopup="false" aria-expanded="false">
                                ...
                                </button>
                                <div class="dropdown-menu" aria-labelledby="dropdownMenuButton">
                                <a value="' . $id_doc . '" class="dropdown-item sombra carga" href="#">Cargar2 presupuesto</a>
                                <a value="' . $id_doc . '" class="dropdown-item sombra modifica" href="#">Modificaciones</a>
                                <a value="' . $id_doc . '" class="dropdown-item sombra ejecuta" href="#">Ejecución</a>
                                </div>';
                        } else {
                            $editar = null;
                            $detalles = null;
                        }
                    ?> <tr>
                            <td class="text-center"><input type="checkbox" value="" id="defaultCheck1"></td>
                            <td class="text-start"><?php echo $ce['id_adquisicion'] ?></td>
                            <td class="text-start"><?php echo $ce['area'] ?></td>
                            <td class="text-start"><?php echo $ce['objeto'] ?></td>
                            <td class="text-end">$ <?php echo number_format($ce['val_contrato'], 2, '.', ',') ?></td>
                            <td class="text-center"> <?php echo $editar ?></td>
                        </tr>
                    <?php
                    }
                    ?>
                </tbody>
            </table>
        </div>
    </div>
    <div class="text-end pt-3">
        <a type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal"> Aceptar</a>
    </div>
</div>
<?php
