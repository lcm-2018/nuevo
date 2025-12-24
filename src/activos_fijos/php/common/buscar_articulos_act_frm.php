<?php
session_start();
if (!isset($_SESSION['user'])) {
    echo '<script>window.location.replace("../../../index.php");</script>';
    exit();
}
include '../../../../config/autoloader.php';

$proceso = isset($_POST['proceso']) && $_POST['proceso'] ? $_POST['proceso'] : '';

$cmd = \Config\Clases\Conexion::getConexion();

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
                    <input type="hidden" id="proceso_fil" value="<?php echo $proceso ?>">
                    <div class="col-md-3">
                        <input type="text" class="filtro_art form-control form-control-sm bg-input" id="txt_codigo_art_fil" placeholder="Codigo">
                    </div>
                    <div class="col-md-3">
                        <input type="text" class="filtro_art form-control form-control-sm bg-input" id="txt_nombre_art_fil" placeholder="Nombre">
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
                            <th>Vr. Última Compra</th>
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
                    url: '../common/buscar_articulos_act_lista.php',
                    type: 'POST',
                    dataType: 'json',
                    data: function(data) {
                        data.proceso = $('#proceso_fil').val();
                        data.codigo = $('#txt_codigo_art_fil').val();
                        data.nombre = $('#txt_nombre_art_fil').val();
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
                        'data': 'valor'
                    }
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
                    [0, "desc"]
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
</script>