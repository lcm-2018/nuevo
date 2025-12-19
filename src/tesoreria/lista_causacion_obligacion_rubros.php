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

$id_cop = $_POST['id_cop'] ?? '';
$id_pag_doc = $_POST['id_doc'] ?? '';
// Consulta tipo de presupuesto
try {
    $cmd = \Config\Clases\Conexion::getConexion();
    $sql = "SELECT
                pto_cop_detalle.id_tercero_api
                , pto_cop_detalle.id_pto_crp_det
                , pto_cop_detalle.id_pto_cop_det
                , pto_cargue.nom_rubro                    
                , pto_cargue.cod_pptal AS rubro
                , pto_cargue.nom_rubro
                , IFNULL(crp.valor,0) - IFNULL(crp.valor_liberado,0) AS valor
                , IFNULL(pto_cop_detalle.valor,0) - IFNULL(pto_cop_detalle.valor_liberado,0) AS val_cop
                , SUM(IFNULL(pag.valor,0) - IFNULL(pag.valor_liberado,0)) AS val_pag
            FROM
                pto_cop_detalle
                INNER JOIN
                    (SELECT 
                        id_pto_crp_det
                        , id_pto_cdp_det
                        , valor
                        , valor_liberado
                    FROM
                        pto_crp_detalle
                        INNER JOIN pto_crp ON (pto_crp_detalle.id_pto_crp = pto_crp.id_pto_crp)
                    WHERE pto_crp.estado = 2) AS crp
                    ON (pto_cop_detalle.id_pto_crp_det = crp.id_pto_crp_det)
                INNER JOIN 
                    (SELECT 
                        id_pto_cdp_det
                        , id_rubro
                    FROM
                        pto_cdp_detalle
                        INNER JOIN pto_cdp ON (pto_cdp_detalle.id_pto_cdp = pto_cdp.id_pto_cdp)
                    WHERE pto_cdp.estado = 2) AS cdp
                    ON (crp.id_pto_cdp_det = cdp.id_pto_cdp_det)
                INNER JOIN pto_cargue ON (cdp.id_rubro = pto_cargue.id_cargue)
                LEFT JOIN
                    (SELECT
                        id_pto_cop_det
                        , valor
                        , valor_liberado
                    FROM
                        pto_pag_detalle
                        INNER JOIN ctb_doc ON (pto_pag_detalle.id_ctb_doc = ctb_doc.id_ctb_doc)
                    WHERE ctb_doc.estado > 0) AS pag
                    ON (pag.id_pto_cop_det = pto_cop_detalle.id_pto_cop_det)
            WHERE pto_cop_detalle.id_ctb_doc = $id_cop
            GROUP BY pto_cop_detalle.id_pto_cop_det";
    $rs = $cmd->query($sql);
    $rubros = $rs->fetchAll();
    $rs->closeCursor();
    unset($rs);
    $tercero = !empty($rubros) ? $rubros[0]['id_tercero_api'] : 0;
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
            <h5 class="mb-0" style="color: white;">LISTA DE REGISTROS PRESUPUESTALES PARA PAGO</h5>
        </div>
        <div class="pb-3"></div>
        <input type="hidden" name="id_pto_rp" id="id_pto_rp" value="<?php echo $id_cop; ?>">
        <form id="rubrosPagar">
            <input type="hidden" name="id_pag_doc" value="<?php echo $id_pag_doc; ?>">
            <input type="hidden" name="id_tercero" value="<?php echo $tercero; ?>">
            <div class="px-3">
                <table id="tableContrtacionRpRubros" class="table table-striped table-bordered table-sm table-hover shadow" style="width: 100%;">
                    <thead>
                        <tr>
                            <th style="width: 45%;">Rubro</th>
                            <th style="width: 15%;">Valor Rp</th>
                            <th style="width: 15%;">Valor Causado</th>
                            <th style="width: 15%;">Valor Cxp</th>
                            <!--<th style="width: 15%;">Acciones</th>-->
                        </tr>
                    </thead>
                    <tbody>

                        <?php
                        foreach ($rubros as $ce) {
                            $editar = $detalles = null;
                            $id_doc = 0;
                            $valor = 0;
                            $id_det_cop = $ce['id_pto_cop_det'];
                            $pagado = $ce['val_pag'] > 0 ? $ce['val_pag'] : 0;
                            $obligado = $ce['val_cop'];
                            $valor =  $obligado - $pagado;
                            $valor_mil = number_format($valor, 2, '.', ',');
                            if ($permisos->PermisosUsuario($opciones, 5601, 3) || $id_rol == 1) {
                                $editar = '<a value="' . $id_doc . '"  class="btn btn-outline-success btn-xs rounded-circle me-1 shadow editar" title="Causar"><span class="fas fa-print"></span></a>';
                                $acciones = '<button  class="btn btn-outline-pry btn-sm" type="button" id="dropdownMenuButton" data-bs-toggle="dropdown" aria-haspopup="false" aria-expanded="false">
                            ...
                            </button>
                            <div class="dropdown-menu" aria-labelledby="dropdownMenuButton">
                            <a value="' . $id_doc . '" class="dropdown-item sombra carga" href="#">Historial</a>
                            </div>';
                            }
                            if ($permisos->PermisosUsuario($opciones, 5601, 4) || $id_rol == 1) {
                                $borrar = '<a value="' . $id_doc . '" onclick="eliminarImputacionPag(' . $id_doc . ')" class="btn btn-outline-danger btn-xs rounded-circle me-1 shadow "  title="Eliminar"><span class="fas fa-trash-alt"></span></a>';
                            }
                            $valor_obl = number_format($obligado, 2, '.', ',');
                        ?>
                            <tr>
                                <td class="text-start"><?php echo $ce['rubro'] . ' - ' . $ce['nom_rubro']; ?></td>
                                <td class="text-end"><?php echo '$ ' . number_format($ce['valor'], 2, '.', ','); ?></td>
                                <td class="text-end"><?php echo '$ ' . number_format($ce['val_cop'], 2, '.', ','); ?></td>
                                <td class="text-end">
                                    <input type="text" name="detalle[<?php echo $id_det_cop; ?>]" id="detalle_<?php echo $id_det_cop; ?>" class="form-control form-control-sm bg-input detalle-pag" value="<?php echo $valor_mil; ?>" style="text-align: right;" required onkeyup="NumberMiles(this)" max="<?php echo $valor; ?>">
                                </td>
                                <!--<td class="text-center"> <?php //echo $editar  .  $acciones; 
                                                                ?></td>-->
                            </tr>
                        <?php
                        }
                        ?>

                    </tbody>
                </table>
                <div class="text-end py-3">
                    <button type="button" class="btn btn-success btn-sm" onclick="rubrosaPagar(this);"> Guardar</button>
                    <a type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancelar</a>
                </div>
            </div>
        </form>
    </div>


</div>

<?php
$cmd = null;
