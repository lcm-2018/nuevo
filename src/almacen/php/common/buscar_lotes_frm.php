<?php
session_start();
if (!isset($_SESSION['user'])) {
    header("Location: ../../../index.php");
    exit();
}
include '../../../../config/autoloader.php';
include '../common/cargar_combos.php';

$cmd = \Config\Clases\Conexion::getConexion();

$proceso = isset($_POST['proceso']) && $_POST['proceso'] ? $_POST['proceso'] : '';
$id_subgrupo = isset($_POST['id_subgrupo']) ? $_POST['id_subgrupo'] : 0;
$id_sede = isset($_POST['id_sede']) ? $_POST['id_sede'] : -1;
$id_bodega = isset($_POST['id_bodega']) && $_POST['id_bodega'] ? $_POST['id_bodega'] : -1;
$tipo = isset($_POST['tipo']) ? $_POST['tipo']  : '';
$checked = $tipo == 'I' ? '' : 'checked';

$sql = "SELECT nombre FROM far_bodegas WHERE id_bodega=$id_bodega";
$rs = $cmd->query($sql);
$obj = $rs->fetch();
$nom_bodega = isset($obj['nombre']) ? $obj['nombre'] : '';
?>

<div class="px-0">
    <div class="shadow">
        <div class="card-header py-2 text-center bg-sofia">
            <h5 class="text-white mb-0">BUSCAR ARTICULOS - LOTES</h5>
        </div>
        <div class="p-2">

            <!--Formulario de busqueda de lotes-->
            <form id="frm_buscar_lotes">
                <input type="hidden" id="proceso_fil" value="<?php echo $proceso ?>">
                <input type="hidden" id="id_sede_fil" value="<?php echo $id_sede ?>">
                <input type="hidden" id="id_bodega_fil" value="<?php echo $id_bodega ?>">
                <div class="row mb-2">
                    <div class="col-md-3">
                        <input type="text" class="form-control form-control-sm bg-input" class="small" value="<?php echo $nom_bodega ?>" readonly="readonly">
                    </div>
                    <div class="col-md-2">
                        <select class="form-select form-select-sm bg-input" id="sl_subgrupo_art_fil">
                            <?php subgrupo_articulo($cmd, '--Subgrupo--', $id_subgrupo) ?>
                        </select>
                    </div>
                    <div class="col-md-7">
                        <div class="row mb-2">
                            <div class="col-md-2">
                                <input type="text" class="filtro_lot form-control form-control-sm bg-input" id="txt_codigo_art_fil" placeholder="Codigo">
                            </div>
                            <div class="col-md-3">
                                <input type="text" class="filtro_lot form-control form-control-sm bg-input" id="txt_nombre_art_fil" placeholder="Nombre">
                            </div>
                            <div class="col-md-3">
                                <div class="form-control form-control-sm bg-input">
                                    <input class="filtro_lot form-check-input" type="checkbox" id="chk_novencido_lot_fil" checked>
                                    <label class="filtro_lot form-check-label small" for="chk_novencido_lot_fil">NO Vencidos</label>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-control form-control-sm bg-input">
                                    <input class="filtro_lot form-check-input" type="checkbox" id="chk_conexistencia_lot_fil" <?php echo $checked ?>>
                                    <label class="filtro_lot form-check-label small" for="chk_conexistencia_lot_fil">Con Existencias</label>
                                </div>
                            </div>
                            <div class="col-md-1">
                                <button type="button" id="btn_buscar_lot_fil" class="btn btn-outline-success btn-sm" title="Filtrar">
                                    <span class="fas fa-search fa-lg" aria-hidden="true"></span>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </form>
            <div style="height:400px" class="overflow-auto">
                <table id="tb_lotes_articulos" class="table table-striped table-bordered table-sm nowrap table-hover shadow align-middle w-100" style="font-size:80%">
                    <thead>
                        <tr class="text-center">
                            <th class="bg-sofia">Id</th>
                            <th class="bg-sofia">Id.Med</th>
                            <th class="bg-sofia">Código</th>
                            <th class="bg-sofia">Artículo</th>
                            <th class="bg-sofia">Lote</th>
                            <th class="bg-sofia">Presentación del Lote</th>
                            <th class="bg-sofia">Unidades en UMPL</th>
                            <th class="bg-sofia">Existencia</th>
                            <th class="bg-sofia">Vr. Promedio</th>
                            <th class="bg-sofia">Fecha Vencimiento</th>
                        </tr>
                    </thead>
                    <tbody class="text-start"></tbody>
                </table>
            </div>
        </div>
    </div>
    <div class="text-end pt-3 rigth">
        <a type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Salir</a>
    </div>
</div>

<script>
    (function($) {
        $(document).ready(function() {
            $('#tb_lotes_articulos').DataTable({
                language: dataTable_es,
                processing: true,
                serverSide: true,
                searching: false,
                autoWidth: false,
                ajax: {
                    url: '../common/buscar_lotes_lista.php',
                    type: 'POST',
                    dataType: 'json',
                    data: function(data) {
                        data.proceso = $('#proceso_fil').val();
                        data.id_sede = $('#id_sede_fil').val();
                        data.id_bodega = $('#id_bodega_fil').val();
                        data.id_subgrupo = $('#sl_subgrupo_art_fil').val();
                        data.codigo = $('#txt_codigo_art_fil').val();
                        data.nombre = $('#txt_nombre_art_fil').val();
                        data.no_vencidos = $('#chk_novencido_lot_fil').is(':checked') ? 1 : 0;
                        data.con_existencia = $('#chk_conexistencia_lot_fil').is(':checked') ? 1 : 0;
                    }
                },
                columns: [{
                        'data': 'id_lote'
                    }, //Index=0
                    {
                        'data': 'id_med'
                    },
                    {
                        'data': 'cod_medicamento'
                    },
                    {
                        'data': 'nom_medicamento'
                    },
                    {
                        'data': 'lote'
                    },
                    {
                        'data': 'nom_presentacion'
                    },
                    {
                        'data': 'existencia_umpl'
                    },
                    {
                        'data': 'existencia'
                    },
                    {
                        'data': 'val_promedio'
                    },
                    {
                        'data': 'fec_vencimiento'
                    },
                ],
                columnDefs: [{
                        class: 'text-wrap',
                        targets: [3, 5]
                    },
                    {
                        width: '5%',
                        targets: [0, 1, 2, 4, 6, 7, 8, 9]
                    }
                ],
                order: [
                    [2, "asc"]
                ],
                lengthMenu: [
                    [10, 25, 50, -1],
                    [10, 25, 50, 'TODO'],
                ]
            });
            $('#tb_lotes_articulos').wrap('<div class="overflow"/>');
        });
    })(jQuery);

    //Buascar registros de Lotes de Articulos
    $('#btn_buscar_lot_fil').on("click", function() {
        $('#tb_lotes_articulos').DataTable().ajax.reload(null, false);
    });

    $('.filtro_lot').keypress(function(e) {
        if (e.keyCode == 13) {
            $('#tb_lotes_articulos').DataTable().ajax.reload(null, false);
        }
    });

    $('.filtro_lot').click(function(e) {
        $('#tb_lotes_articulos').DataTable().ajax.reload(null, false);
    });

    $('#sl_subgrupo_art_fil').on("change", function() {
        sessionStorage.setItem("id_subgrupo", $(this).val());
    });
</script>