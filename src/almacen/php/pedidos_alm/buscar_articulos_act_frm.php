<?php
session_start();
if (!isset($_SESSION['user'])) {
    header("Location: ../../../index.php");
    exit();
}
include '../../../../config/autoloader.php';
include '../common/cargar_combos.php';

$cmd = \Config\Clases\Conexion::getConexion();

$id_subgrupo = isset($_POST['id_subgrupo']) ? $_POST['id_subgrupo'] : 0;

?>
<div class="px-0">
    <div class="shadow">
        <div class="card-header py-2 text-center" style="background-color: #16a085 !important;">
            <h7 style="color: white;">BUSCAR ARTICULOS</h7>
        </div>
        <div class="p-2">

            <!--Formulario de busqueda de articulos-->
            <form id="frm_buscar_articulos">
                <div class="row mb-2">
                    <div class="col-md-2">
                        <select class="form-select form-select-sm bg-input" id="sl_subgrupo_art_fil">
                            <?php subgrupo_articulo($cmd, '--Subgrupo--', $id_subgrupo) ?>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <input type="text" class="filtro_art form-control form-control-sm bg-input" id="txt_codigo_art_fil" placeholder="Codigo">
                    </div>
                    <div class="col-md-2">
                        <input type="text" class="filtro_art form-control form-control-sm bg-input" id="txt_nombre_art_fil" placeholder="Nombre">
                    </div>
                    <div class="col-md-2">
                        <div class="form-control form-control-sm bg-input">
                            <input class="filtro_art form-check-input" type="checkbox" id="chk_conexistencia_lot_fil" checked>
                            <label class="filtro_art form-check-label small" for="chk_conexistencia_lot_fil">Con Existencias</label>
                        </div>
                    </div>
                    <div class="col-md-1">
                        <a type="button" id="btn_buscar_articulo_fil" class="btn btn-outline-success btn-sm" title="Filtrar">
                            <span class="fas fa-search fa-lg" aria-hidden="true"></span>
                        </a>
                    </div>
                </div>
            </form>
            <div style="height:400px" class="overflow-auto">
                <table id="tb_articulos_activos" class="table table-striped table-bordered table-sm nowrap table-hover shadow" style="width:100%; font-size:80%">
                    <thead>
                        <tr class="text-center">
                            <th>Id</th>
                            <th>Código</th>
                            <th>Artículo</th>
                            <th>Existencia</th>
                            <th>Vr. Promedio</th>
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
            $('#tb_articulos_activos').DataTable({
                language: dataTable_es,
                processing: true,
                serverSide: true,
                searching: false,
                autoWidth: false,
                ajax: {
                    url: 'buscar_articulos_act_lista.php',
                    type: 'POST',
                    dataType: 'json',
                    data: function(data) {
                        data.id_subgrupo = $('#sl_subgrupo_art_fil').val();
                        data.codigo = $('#txt_codigo_art_fil').val();
                        data.nombre = $('#txt_nombre_art_fil').val();
                        data.con_existencia = $('#chk_conexistencia_lot_fil').is(':checked') ? 1 : 0;
                    }
                },
                columns: [{
                        'data': 'id_med'
                    }, //Index=0
                    {
                        'data': 'cod_medicamento'
                    },
                    {
                        'data': 'nom_medicamento'
                    },
                    {
                        'data': 'existencia'
                    },
                    {
                        'data': 'val_promedio'
                    },
                ],
                columnDefs: [{
                        class: 'text-wrap',
                        targets: [2]
                    },
                    {
                        width: '5%',
                        targets: [0, 1, 3, 4]
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
            $('#tb_articulos_activos').wrap('<div class="overflow"/>');
        });
    })(jQuery);

    //Buascar registros de articulos de Articulos
    $('#btn_buscar_articulo_fil').on("click", function() {
        $('#tb_articulos_activos').DataTable().ajax.reload(null, false);
    });

    $('.filtro_art').keypress(function(e) {
        if (e.keyCode == 13) {
            $('#tb_articulos_activos').DataTable().ajax.reload(null, false);
        }
    });

    $('.filtro_art').mouseup(function(e) {
        $('#tb_articulos_activos').DataTable().ajax.reload(null, false);
    });

    $('#sl_subgrupo_art_fil').on("change", function() {
        sessionStorage.setItem("id_subgrupo", $(this).val());
    });
</script>