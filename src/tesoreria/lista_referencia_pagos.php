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

$vigencia = $_SESSION['vigencia'];
$inicio = $vigencia . '-01-01';
$fin = $vigencia . '-12-31';

$cmd = \Config\Clases\Conexion::getConexion();

// Consultar el valor a de los descuentos realizados a la cuenta de ctb_causa_retencion
try {
    $sql = "SELECT
                `tes_referencia`.`id_referencia`
                , `tes_referencia`.`numero`
                ,  DATE_FORMAT(`tes_referencia`.`fecha`, '%Y-%m-%d') AS `fecha`
                , `tes_referencia`.`estado`
                , SUM(`t1`.`valor`) AS `valor`
                , `tes_cuentas`.`nombre` AS `banco`
            FROM
                `tes_referencia`
                LEFT JOIN `ctb_doc` 
                    ON (`ctb_doc`.`id_ref` = `tes_referencia`.`id_referencia` AND `ctb_doc`.`estado` = 2)
                LEFT JOIN 
                    (SELECT `id_ctb_doc`, SUM(`credito`) AS `valor`
                    FROM `ctb_libaux`
                    GROUP BY `id_ctb_doc`)AS`t1` 
                    ON (`t1`.`id_ctb_doc` = `ctb_doc`.`id_ctb_doc`)
                LEFT JOIN `tes_cuentas` 
                    ON (`tes_cuentas`.`id_tes_cuenta` = `tes_referencia`.`id_tes_cuenta`)
            WHERE (DATE_FORMAT(`tes_referencia`.`fecha`, '%Y-%m-%d') BETWEEN '$inicio' AND '$fin')
            GROUP BY `tes_referencia`.`id_referencia`";
    $rs = $cmd->query($sql);
    $referencias = $rs->fetchAll();
    $rs->closeCursor();
    unset($rs);
} catch (PDOException $e) {
    echo $e->getCode() == 2002 ? 'Sin Conexión a Mysql (Error: 2002)' : 'Error: ' . $e->getCode();
}
?>
<style>
    /* Sobrescribir nowrap solo para la columna Banco */
    table.nowrap td.wrap-column {
        white-space: normal !important;
        word-wrap: break-word;
        max-width: 150px;
    }
</style>
<script>
    $('#tableReferenciasPagos').DataTable({
        dom: setdom,
        language: dataTable_es,
        responsive: false,
        autoWidth: false,
        columnDefs: [{
                targets: 2, // Columna Banco - permite wrap
                className: 'text-start wrap-column'
            },
            {
                targets: [1, 3, 4, 5, 6], // Otras columnas centradas
                className: 'text-center'
            }
        ],
        buttons: $('#peReg').val() == 1 ? [{
            text: '<span class="fa-solid fa-plus "></span>',
            className: 'btn btn-success btn-sm shadow',
            action: function(e, dt, node, config) {
                GetFormRefPago(0);
            },
        }, ] : [],
        "order": [
            [0, "desc"]
        ]
    });
    // Removed wrap - already has table-responsive
    // remover de tableReferenciasPagos_filter el elemento label
    $('#tableReferenciasPagos_filter #verAnulados').remove();
    $('#tableReferenciasPagos_filter label label').remove();

    function editarReferenciaPago(id) {
        GetFormRefPago(id);
    }
    GetFormRefPago = function(id) {
        mostrarOverlay();
        $.post("datos/registrar/form_referencia_pago.php", {
            id: id
        }, function(he) {
            $("#divTamModalAux").removeClass("modal-lg");
            $("#divTamModalAux").removeClass("modal-xl");
            $("#divTamModalAux").addClass("modal-sm");
            $("#divModalAux").modal("show");
            $("#divFormsAux").html(he);
        }).always(function() {
            ocultarOverlay();
        });
    }
</script>
<div class="px-0 text-start">

    <div class="shadow">
        <div class="card-header py-2 text-center" style="background-color: #16a085 !important;">
            <h5 class="mb-0" style="color: white;">LISTA DE REFERENCIAS DE PAGO </h5>
        </div>
        <div class="px-3 mt-2">
            <div class="table-responsive">
                <table id="tableReferenciasPagos" class="table table-striped table-bordered table-sm table-hover shadow w-100 align-middle nowrap">
                    <thead>
                        <tr>
                            <th class="bg-sofia">#</th>
                            <th class="bg-sofia">Num.</th>
                            <th class="bg-sofia">Banco</th>
                            <th class="bg-sofia">Fecha</th>
                            <th class="bg-sofia">Estado</th>
                            <th class="bg-sofia">Valor</th>
                            <th class="bg-sofia text-center">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        if (!empty($referencias)) {
                            foreach ($referencias as $ce) {
                                $id = $ce['id_referencia'];
                                if (($permisos->PermisosUsuario($opciones, 5601, 3) || $permisos->PermisosUsuario($opciones, 5602, 3) || $permisos->PermisosUsuario($opciones, 5603, 3) || $permisos->PermisosUsuario($opciones, 5604, 3) || $id_rol == 1)) {
                                    $editar = '<a value="' . $id . '" onclick="editarReferenciaPago(' . $id . ')" class="btn btn-outline-primary btn-xs rounded-circle me-1 shadow editar" title="Editar"><span class="fas fa-pencil-alt"></span></a>';
                                }
                                if (($permisos->PermisosUsuario($opciones, 5601, 4) || $permisos->PermisosUsuario($opciones, 5602, 4) || $permisos->PermisosUsuario($opciones, 5603, 4) || $permisos->PermisosUsuario($opciones, 5604, 4) || $id_rol == 1)) {
                                    $eliminar = '<a value="' . $id . '" onclick="eliminarReferenciaPago(' . $id . ')" class="btn btn-outline-danger btn-xs rounded-circle me-1 shadow editar" title="Eliminar"><span class="fas fa-trash-alt"></span></a>';
                                }
                                $imprimir = '<a value="' . $id . '" onclick="imprimirReferenciaPago(' . $id . ')" class="btn btn-outline-success btn-xs rounded-circle me-1 shadow " title="Relación de pagos"><span class="fas fa-file-excel"></span></a>';

                                if ($ce['estado'] == '1') {
                                    $estado = '<a href="javascript:void(0)" onclick="CambiaEstadoReferencia(' . $id . ',0)" class="estado" title="Activo"><span class="fas fa-toggle-on fa-lg text-success" aria-hidden="true"></span></button>';
                                } else {
                                    $estado = '<a href="javascript:void(0)" onclick="CambiaEstadoReferencia(' . $id . ',1)" class="estado" title="Inactivo"><span class="fas fa-toggle-off fa-lg text-secondary" aria-hidden="true"></span></button>';
                                }
                                if ($ce['estado'] == '0') {
                                    $editar =  $eliminar = '';
                                }
                        ?>
                                <tr id="<?= $id; ?>">
                                    <td><?= $id; ?></td>
                                    <td><?= $ce['numero']; ?></td>
                                    <td><?= $ce['banco']; ?></td>
                                    <td><?= $ce['fecha']; ?></td>
                                    <td> <?= $estado; ?></td>
                                    <td> <?= number_format($ce['valor'], 2, '.', ','); ?></td>
                                    <td> <?= $editar . $imprimir .  $eliminar; ?></td>

                                </tr>
                        <?php
                            }
                        }
                        ?>
                    </tbody>
                </table>
            </div>
            <div class="text-end py-3">
                <a type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cerrar</a>
            </div>
        </div>
    </div>
</div>