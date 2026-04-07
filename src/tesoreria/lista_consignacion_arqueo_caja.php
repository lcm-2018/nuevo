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

// ─── Parámetros de filtro ────────────────────────────────────────────────────
$fechaIni  = isset($_POST['fecha_ini'])  && $_POST['fecha_ini']  !== '' ? $_POST['fecha_ini']  : null;
$fechaFin  = isset($_POST['fecha_fin'])  && $_POST['fecha_fin']  !== '' ? $_POST['fecha_fin']  : null;
$idTercero = isset($_POST['id_tercero']) && $_POST['id_tercero'] !== '' ? (int)$_POST['id_tercero'] : null;

// ─── Consulta con filtros opcionales ─────────────────────────────────────────
$where  = "WHERE (`ctb_doc`.`id_tipo_doc` = 9 AND `ctb_doc`.`id_ctb_doc_tipo3` IS NULL)";
$params = [];

if ($fechaIni !== null) {
    $where .= " AND DATE(`ctb_doc`.`fecha`) >= :fecha_ini";
    $params[':fecha_ini'] = $fechaIni;
}
if ($fechaFin !== null) {
    $where .= " AND DATE(`ctb_doc`.`fecha`) <= :fecha_fin";
    $params[':fecha_fin'] = $fechaFin;
}
if ($idTercero !== null) {
    $where .= " AND `tb_terceros`.`id_tercero_api` = :id_tercero";
    $params[':id_tercero'] = $idTercero;
}

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
            {$where}
            GROUP BY `ctb_doc`.`id_ctb_doc`
            ORDER BY `ctb_doc`.`fecha` DESC";

    $rs = $cmd->prepare($sql);
    $rs->execute($params);
    $listado = $rs->fetchAll(PDO::FETCH_ASSOC);
    $rs->closeCursor();
    unset($rs);
} catch (PDOException $e) {
    echo $e->getCode() == 2002 ? 'Sin Conexión a Mysql (Error: 2002)' : 'Error: ' . $e->getCode();
    $listado = [];
}
?>
<script>
    // ── DataTable ────────────────────────────────────────────────────────────
    $('#tableArqueoCaja').DataTable({
        language: dataTable_es,
        order: [
            [1, 'desc']
        ],
        pageLength: 10
    });
    $('#tableArqueoCaja').wrap('<div class="overflow" />');

    // ── Awesomplete para el tercero (se inicializa aquí porque no hay modal shown) ──
    (function initAwesomplete() {
        const inputTercero = document.getElementById('buscaTerceroFiltro');
        if (inputTercero) {
            inicializarAwesomplete(
                inputTercero,
                ValueInput('host') + '/src/common/php/controladores/consultaTercero.php',
                '#id_tercero_filtro',
                true // incluir cédula/nit en la etiqueta
            );
        }
    })();

    // ── Buscar: recarga #divForms con los filtros vía $.post ─────────────────
    function buscarArqueoCaja() {
        const fechaIni = document.getElementById('fecha_ini').value;
        const fechaFin = document.getElementById('fecha_fin').value;
        const idTercero = document.getElementById('id_tercero_filtro').value;
        const nomTercero = document.getElementById('buscaTerceroFiltro').value;

        mostrarOverlay();
        $.post(
            'lista_consignacion_arqueo_caja.php', {
                fecha_ini: fechaIni,
                fecha_fin: fechaFin,
                id_tercero: idTercero
            },
            function(he) {
                $('#divForms').html(he);
                // Restaurar los valores en los filtros tras recargar
                setTimeout(function() {
                    if (fechaIni) document.getElementById('fecha_ini').value = fechaIni;
                    if (fechaFin) document.getElementById('fecha_fin').value = fechaFin;
                    if (idTercero) document.getElementById('id_tercero_filtro').value = idTercero;
                    if (nomTercero) document.getElementById('buscaTerceroFiltro').value = nomTercero;
                }, 50);
            }
        ).always(function() {
            ocultarOverlay();
        });
    }

    // ── Limpiar filtros ──────────────────────────────────────────────────────
    function limpiarFiltroArqueo() {
        mostrarOverlay();
        $.post('lista_consignacion_arqueo_caja.php', {}, function(he) {
            $('#divForms').html(he);
        }).always(function() {
            ocultarOverlay();
        });
    }

    // ── Limpiar solo el campo de tercero ─────────────────────────────────────
    function limpiarTercero() {
        document.getElementById('buscaTerceroFiltro').value = '';
        document.getElementById('id_tercero_filtro').value = '';
    }

    // ── Evento: asignar / desasignar arqueo al documento CTCB ────────────────
    // El id_ctb_doc del documento destino vive en la página padre
    $(document).on('change', '.chk-doc', function() {
        const chk       = this;
        const idArqueo  = chk.value;
        // Buscar el id_ctb_doc en la página padre (lista_documentos_pag.php)
        const idCtbDoc  = document.getElementById('id_ctb_doc')
                          ? document.getElementById('id_ctb_doc').value
                          : 0;

        if (!idCtbDoc || idCtbDoc <= 0) {
            mjeError('Error', 'No se encontró el documento de consignación (id_ctb_doc).');
            chk.checked = !chk.checked; // revertir
            return;
        }

        const accion = chk.checked ? 'asignar' : 'desasignar';

        Swal.fire({
            title: chk.checked ? '¿Asignar este arqueo?' : '¿Desasignar este arqueo?',
            text: chk.checked
                ? 'Se vinculará el arqueo al documento de consignación y se generarán los movimientos contables.'
                : 'Se eliminará el vínculo y los movimientos contables generados.',
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#16a085',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Sí, continuar',
            cancelButtonText: 'Cancelar'
        }).then(function(result) {
            if (!result.isConfirmed) {
                chk.checked = !chk.checked; // revertir si cancela
                return;
            }

            mostrarOverlay();
            $.post(
                ValueInput('host') + '/src/tesoreria/procesar/asignar_arqueo_consignacion.php',
                {
                    id_ctb_doc: idCtbDoc,
                    id_arqueo:  idArqueo,
                    accion:     accion
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
                        // Recargar el listado de pendientes
                        setTimeout(function() {
                            buscarArqueoCaja();
                        }, 1200);
                    } else {
                        mjeError('Error', res.msg);
                        chk.checked = !chk.checked; // revertir si falla
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
            <h5 class="mb-0" style="color: white;">LISTA DE ARQUEOS DE CAJA PENDIENTE CONSIGNACIÓN</h5>
        </div>

        <!-- ═══ FILTROS ═══════════════════════════════════════════════════════ -->
        <div class="px-3 pt-3 pb-2">
            <div class="row g-2 align-items-end">

                <!-- Fecha inicial -->
                <div class="col-md-3 col-sm-6">
                    <label for="fecha_ini" class="form-label small fw-semibold mb-1">Fecha inicial</label>
                    <input type="date"
                        id="fecha_ini"
                        class="form-control form-control-sm bg-input"
                        value="<?= htmlspecialchars($fechaIni ?? '') ?>">
                </div>

                <!-- Fecha final -->
                <div class="col-md-3 col-sm-6">
                    <label for="fecha_fin" class="form-label small fw-semibold mb-1">Fecha final</label>
                    <input type="date"
                        id="fecha_fin"
                        class="form-control form-control-sm bg-input"
                        value="<?= htmlspecialchars($fechaFin ?? '') ?>">
                </div>

                <!-- Tercero (Awesomplete) -->
                <div class="col-md-4 col-sm-9">
                    <label for="buscaTerceroFiltro" class="form-label small fw-semibold mb-1">Tercero (nombre o NIT)</label>
                    <div style="position: relative;">
                        <input type="text"
                            id="buscaTerceroFiltro"
                            class="form-control form-control-sm bg-input awesomplete"
                            placeholder="Escriba para buscar…"
                            autocomplete="off"
                            style="padding-right: 2rem;">
                        <input type="hidden" id="id_tercero_filtro" value="">
                        <button type="button"
                            onclick="limpiarTercero()"
                            title="Limpiar tercero"
                            style="position:absolute; right:4px; top:50%; transform:translateY(-50%); background:none; border:none; padding:0 4px; cursor:pointer; color:#6c757d; z-index:10; line-height:1;">
                            <i class="fas fa-times fa-xs"></i>
                        </button>
                    </div>
                </div>

                <!-- Botones -->
                <div class="col-md-2 col-sm-3 d-flex gap-2">
                    <button type="button"
                        class="btn btn-primary btn-sm w-50"
                        onclick="buscarArqueoCaja()"
                        title="Buscar">
                        <i class="fas fa-search"></i>
                    </button>
                    <button type="button"
                        class="btn btn-secondary btn-sm w-50"
                        onclick="limpiarFiltroArqueo()"
                        title="Limpiar filtros">
                        <i class="fas fa-eraser"></i>
                    </button>
                </div>

            </div>
        </div>
        <!-- ══════════════════════════════════════════════════════════════════ -->

        <div class="px-3 pb-3">
            <table id="tableArqueoCaja"
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
                                <input type="checkbox" class="chk-doc ms-1" value="<?= $id_doc ?>" title="Seleccionar">
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