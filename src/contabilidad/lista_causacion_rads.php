<?php
session_start();
if (!isset($_SESSION['user'])) {
    header('Location: ../../index.php');
    exit();
}
include '../../config/autoloader.php';
$id_rol = $_SESSION['rol'];
$id_user = $_SESSION['id_user'];

use Config\Clases\Plantilla;
use Src\Common\Php\Clases\Permisos;

$permisos = new Permisos();
$opciones = $permisos->PermisoOpciones($id_user);
$id_vigencia = $_SESSION['id_vigencia'];
unset($_SESSION['id_doc']);
// Consulta tipo de presupuesto
function pesos($valor)
{
    return number_format($valor, 2, ',', '.');
}
$id_r = isset($_POST['dato']) ? $_POST['dato'] : null;
$idPto = 0;
try {
    $cmd = \Config\Clases\Conexion::getConexion();
    $sql = "SELECT `id_pto` FROM `pto_presupuestos` WHERE (`id_tipo` = 1 AND `id_vigencia` = $id_vigencia)";
    $rs = $cmd->query($sql);
    $listappto = $rs ? $rs->fetch() : null;
    $idPto = !empty($listappto['id_pto']) ? intval($listappto['id_pto']) : 0;
    if ($rs) {
        $rs->closeCursor();
        unset($rs);
    }
} catch (PDOException $e) {
    echo $e->getCode() == 2002 ? 'Sin Conexión a Mysql (Error: 2002)' : 'Error: ' . $e->getCode();
}

$listado = [];
if ($idPto > 0) {
    try {
        $sql = "SELECT
                    `pto_rad`.`id_pto_rad`
                    , `pto_rad`.`id_manu`
                    , `pto_rad`.`id_tercero_api`
                    , `pto_rad`.`fecha`
                    , `pto_rad`.`objeto`
                    , `pto_rad`.`num_factura`
                    , `tb_terceros`.`nom_tercero`
                    , `tb_terceros`.`nit_tercero`
                    , IFNULL(SUM(`pto_rad_detalle`.`valor` - IFNULL(`pto_rad_detalle`.`valor_liberado`, 0)), 0) AS `valor_radicado`
                FROM
                    `pto_rad`
                    LEFT JOIN `pto_rad_detalle` 
                        ON (`pto_rad`.`id_pto_rad` = `pto_rad_detalle`.`id_pto_rad`)
                    LEFT JOIN `tb_terceros` 
                        ON (`pto_rad`.`id_tercero_api` = `tb_terceros`.`id_tercero_api`)
                WHERE (`pto_rad`.`estado` = 2 AND `pto_rad`.`id_pto` = $idPto AND `pto_rad`.`tipo_movimiento` IS NULL)
                GROUP BY `pto_rad`.`id_pto_rad`
                ORDER BY `pto_rad`.`id_manu` DESC";
        $rs = $cmd->query($sql);
        $listado = $rs->fetchAll(PDO::FETCH_ASSOC);
        $rs->closeCursor();
        unset($rs);
    } catch (PDOException $e) {
        echo $e->getCode() == 2002 ? 'Sin Conexión a Mysql (Error: 2002)' : 'Error: ' . $e->getCode();
    }
}

$causadosLookup = [];
if (!empty($listado)) {
    $idsRadMap = [];
    foreach ($listado as $ce) {
        $idsRadMap[intval($ce['id_pto_rad'])] = true;
    }

    try {
        // 1. Consultar únicamente ctb_doc (muy liviana, sin tocar ctb_libaux)
        $sqlDocs = "SELECT `id_ctb_doc`, `id_rad`
                    FROM `ctb_doc`
                    WHERE `estado` > 0 
                      AND `id_vigencia` = $id_vigencia 
                      AND `id_rad` IS NOT NULL";
        $rs = $cmd->query($sqlDocs);
        $docsCausados = $rs->fetchAll(PDO::FETCH_ASSOC);
        $rs->closeCursor();
        unset($rs);

        $mapCtbRad = [];
        $idsCtbCausados = [];
        foreach ($docsCausados as $dc) {
            $idRadDoc = intval($dc['id_rad']);
            if (isset($idsRadMap[$idRadDoc])) {
                $idCtb = intval($dc['id_ctb_doc']);
                $mapCtbRad[$idCtb] = $idRadDoc;
                $idsCtbCausados[] = $idCtb;
            }
        }

        // 2. Si hay documentos causados para estos radicados, consultar ctb_libaux por su índice id_ctb_doc
        if (!empty($idsCtbCausados)) {
            $idsCtbIn = implode(',', $idsCtbCausados);
            $sqlLib = "SELECT `id_ctb_doc`, SUM(`debito`) AS `valor`
                       FROM `ctb_libaux`
                       WHERE `id_ctb_doc` IN ($idsCtbIn)
                       GROUP BY `id_ctb_doc`";
            $rs = $cmd->query($sqlLib);
            $libauxRows = $rs->fetchAll(PDO::FETCH_ASSOC);
            $rs->closeCursor();
            unset($rs);

            foreach ($libauxRows as $lr) {
                $idRad = $mapCtbRad[intval($lr['id_ctb_doc'])];
                if (!isset($causadosLookup[$idRad])) {
                    $causadosLookup[$idRad] = 0;
                }
                $causadosLookup[$idRad] += floatval($lr['valor']);
            }
        }
    } catch (PDOException $e) {
        echo $e->getCode() == 2002 ? 'Sin Conexión a Mysql (Error: 2002)' : 'Error: ' . $e->getCode();
    }
}

?>
<script>
    $('#tableContrtacionRp').DataTable({
        dom: "<'row'<'col-md-2'l><'col-md-10'f>>" +
            "<'row'<'col-sm-12'tr>>" +
            "<'row'<'col-sm-12 col-md-5'i><'col-sm-12 col-md-7'p>>",
        language: dataTable_es,
        "order": [
            [1, "desc"]
        ],
        columnDefs: [{
            class: 'text-wrap',
            targets: [4]
        }],
    });
    $('#tableContrtacionRp').wrap('<div class="overflow" />');
</script>
<div class="px-0">
    <div class="shadow">
        <div class="card-header py-2 text-center" style="background-color: #16a085 !important;">
            <h5 class="mb-0" style="color: white;">LISTA DE RECONOCIMIENTOS PRESUPUESTALES</h5>
        </div>
        <div class="pb-3"></div>
        <div class="px-3">
            <table id="tableContrtacionRp" class="table table-striped table-bordered nowrap table-sm table-hover shadow" style="width: 100%;">
                <thead>
                    <tr>
                        <th class="bg-sofia">Num</th>
                        <?= $_SESSION['pto'] == '1' ? '<th class="bg-sofia">RAD</th><th class="bg-sofia">Contrato</th>' : ''; ?>
                        <th class="bg-sofia">Fecha</th>
                        <th class="bg-sofia">Terceros</th>
                        <?= $_SESSION['pto'] == '1' ? '<th class="bg-sofia">Valor</th>' : ''; ?>
                        <th class="bg-sofia">Acciones</th>

                    </tr>
                </thead>
                <tbody>
                    <?php
                    $acciones = null;
                    if (!empty($listado)) {
                        foreach ($listado as $ce) {
                            $detalles = $editar = null;
                            $id_ter = $ce['id_tercero_api'];
                            $id_rad = intval($ce['id_pto_rad']);
                            $sum_rad = floatval($ce['valor_radicado']);
                            $sum_cau = isset($causadosLookup[$id_rad]) ? $causadosLookup[$id_rad] : 0;
                            $saldoo = $sum_rad - $sum_cau;
                            $saldo_rad = pesos($saldoo);

                            $numeroc = $ce['num_factura'];
                            if ($permisos->PermisosUsuario($opciones, 5501, 3) || $id_rol == 1) {
                                $editar = '<a value="' . $id_rad . '" onclick="cargarListaDetalleCtbInvoice(' . $id_rad . ', 0)" class="btn btn-outline-success btn-xs rounded-circle me-1 shadow editar" title="Causar"><span class="fas fa-plus-square "></span></a>';
                            }
                            $fecha = date('Y-m-d', strtotime($ce['fecha']));
                            if ($saldoo > 0) {
                    ?>
                                <tr>
                                    <td class="text-center"><input type="checkbox" value="" id="defaultCheck1"></td>
                                    <td class="text-start"><?php echo $ce['id_manu']; ?></td>
                                    <td class="text-start"><?php echo $numeroc; ?></td>
                                    <td class="text-start"><?php echo $fecha; ?></td>
                                    <td class="text-start"><?php echo $ce['nom_tercero']; ?></td>
                                    <td class="text-end"> <?php echo $saldo_rad; ?></td>
                                    <td class="text-center"> <?php echo $editar . $acciones; ?></td>
                                </tr>
                    <?php
                            }
                        }
                    } else {
                        echo '<tr><td colspan="7" class="text-center">No hay registros</td></tr>';
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
<?php
