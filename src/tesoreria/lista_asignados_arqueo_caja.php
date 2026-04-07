<?php
session_start();
if (!isset($_SESSION['user'])) {
    header('Location: ../index.php');
    exit();
}
include '../../config/autoloader.php';

use Src\Common\Php\Clases\Permisos;

$id_rol  = $_SESSION['rol'];
$id_user = $_SESSION['id_user'];

$permisos = new Permisos();
$opciones  = $permisos->PermisoOpciones($id_user);

try {
    $cmd = \Config\Clases\Conexion::getConexion();
} catch (PDOException $e) {
    echo $e->getCode() == 2002 ? 'Sin Conexión a Mysql (Error: 2002)' : 'Error: ' . $e->getCode();
}

// id_ctb_doc = documento CTCB padre (se recibe por POST desde la página que llama)
$id_ctb_doc = isset($_POST['id_ctb_doc']) && $_POST['id_ctb_doc'] !== '' ? (int)$_POST['id_ctb_doc'] : 0;

if ($id_ctb_doc <= 0) {
    echo '<div class="alert alert-danger m-3">Documento no especificado.</div>';
    exit();
}

// ─── Consulta: arqueos asignados a este documento CTCB ──────────────────────
try {
    $sql = "SELECT
                `ctb_doc`.`id_ctb_doc`,
                DATE_FORMAT(`ctb_doc`.`fecha`, '%Y-%m-%d') AS `fecha`,
                SUM(`ctb_libaux`.`debito`)                  AS `valor`,
                `ctb_doc`.`id_manu`,
                `tb_terceros`.`nom_tercero`,
                `tb_terceros`.`nit_tercero`
            FROM
                `ctb_libaux`
                INNER JOIN `ctb_doc`
                    ON (`ctb_libaux`.`id_ctb_doc` = `ctb_doc`.`id_ctb_doc`)
                INNER JOIN `tb_terceros`
                    ON (`ctb_libaux`.`id_tercero_api` = `tb_terceros`.`id_tercero_api`)
            WHERE `ctb_doc`.`id_tipo_doc` = 9
              AND `ctb_doc`.`id_ctb_doc_tipo3` = :id_ctb_doc
            GROUP BY `ctb_doc`.`id_ctb_doc`
            ORDER BY `ctb_doc`.`fecha` DESC";

    $rs = $cmd->prepare($sql);
    $rs->bindParam(':id_ctb_doc', $id_ctb_doc, PDO::PARAM_INT);
    $rs->execute();
    $listado = $rs->fetchAll(PDO::FETCH_ASSOC);
    $rs->closeCursor();
    unset($rs);
} catch (PDOException $e) {
    echo $e->getCode() == 2002 ? 'Sin Conexión a Mysql (Error: 2002)' : 'Error: ' . $e->getCode();
    $listado = [];
}
?>
<script>
    // ── Input oculto con el id del documento CTCB para usarlo en el evento ──
    const _idCtbDocAsig = <?= $id_ctb_doc ?>;

    // ── DataTable ────────────────────────────────────────────────────────────
    $('#tableArqueoAsignados').DataTable({
        language: dataTable_es,
        order: [
            [1, 'desc']
        ],
        pageLength: 10
    });
    $('#tableArqueoAsignados').wrap('<div class="overflow" />');

    // ── Recargar este listado (llamado tras desasignar) ───────────────────────
    function recargarAsignados() {
        mostrarOverlay();
        $.post(
            'lista_asignados_arqueo_caja.php', {
                id_ctb_doc: _idCtbDocAsig
            },
            function(he) {
                $('#divForms').html(he);
            }
        ).always(function() {
            ocultarOverlay();
        });
    }

    // ── Evento: desasignar al desmarcar el checkbox ──────────────────────────
    $(document).on('change', '.chk-asig', function() {
        const chk = this;
        const idArqueo = chk.value;

        // Solo se puede desmarcar (están marcados por defecto)
        if (chk.checked) return;

        Swal.fire({
            title: '¿Desasignar este arqueo?',
            text: 'Se eliminará el vínculo y los movimientos contables generados en el documento de consignación.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#e74c3c',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Sí, desasignar',
            cancelButtonText: 'Cancelar'
        }).then(function(result) {
            if (!result.isConfirmed) {
                chk.checked = true; // revertir si cancela
                return;
            }

            mostrarOverlay();
            $.post(
                ValueInput('host') + '/src/tesoreria/procesar/asignar_arqueo_consignacion.php', {
                    id_ctb_doc: _idCtbDocAsig,
                    id_arqueo: idArqueo,
                    accion: 'desasignar'
                },
                function(res) {
                    if (res.status === 'ok') {
                        mje(res.msg);
                        // Refrescar movimientos contables en la página padre (con tfoot)
                        if ($.fn.DataTable.isDataTable('#tableMvtoContableDetallePag')) {
                            $('#tableMvtoContableDetallePag').DataTable().ajax.reload(function(json) {
                                var tfootData = json.tfoot;
                                var tfootHtml = '<tfoot><tr>';
                                $.each(tfootData, function(index, value) {
                                    tfootHtml += '<th>' + value + '</th>';
                                });
                                tfootHtml += '</tr></tfoot>';
                                $('#tableMvtoContableDetallePag').find('tfoot').remove();
                                $('#tableMvtoContableDetallePag').append(tfootHtml);
                            });
                        }
                        // Recargar el listado de asignados
                        setTimeout(function() {
                            recargarAsignados();
                        }, 1200);
                    } else {
                        mjeError('Error', res.msg);
                        chk.checked = true; // revertir si falla
                    }
                },
                'json'
            ).always(function() {
                ocultarOverlay();
            });
        });
    });
</script>

<div class="px-0">
    <div class="shadow">
        <div class="card-header text-center py-2" style="background-color: #16a085 !important;">
            <h5 class="mb-0" style="color: white;">ARQUEOS ASIGNADOS A LA CONSIGNACIÓN</h5>
        </div>

        <div class="px-3 pb-3 pt-2">
            <table id="tableArqueoAsignados"
                class="table table-striped table-bordered nowrap table-sm table-hover shadow"
                style="width: 100%;">
                <thead>
                    <tr>
                        <th class="bg-sofia">ID</th>
                        <th class="bg-sofia">Fecha</th>
                        <th class="bg-sofia">Tercero</th>
                        <th class="bg-sofia">DOC</th>
                        <th class="bg-sofia text-end">Valor</th>
                        <th class="bg-sofia text-center">Acción</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($listado as $ce): ?>
                        <?php
                        $id_doc  = $ce['id_ctb_doc'];
                        $fecha   = $ce['fecha'] ?? '---';
                        $tercero = $ce['nom_tercero'] ?? '---';
                        $ccnit   = $ce['nit_tercero'] ?? '---';
                        ?>
                        <tr>
                            <td class="text-start"><?= htmlspecialchars($id_doc) ?></td>
                            <td class="text-start"><?= htmlspecialchars($fecha) ?></td>
                            <td class="text-start">
                                <?= htmlspecialchars($tercero) ?>
                                <small class="text-muted">(<?= htmlspecialchars($ccnit) ?>)</small>
                            </td>
                            <td class="text-start"><?= htmlspecialchars($ce['id_manu']) ?></td>
                            <td class="text-end"><?= number_format($ce['valor'], 2, ',', '.') ?></td>
                            <td class="text-center">
                                <!-- marcado por defecto → desmarcar = desasignar -->
                                <input type="checkbox"
                                    class="chk-asig"
                                    value="<?= $id_doc ?>"
                                    checked
                                    title="Desmarcar para desasignar">
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div class="text-end pt-3">
        <a type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cerrar</a>
    </div>
</div>