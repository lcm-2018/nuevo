<?php
session_start();
if (!isset($_SESSION['user'])) {
    echo '<script>window.location.replace("../../../index.php");</script>';
    exit();
}
include '../../../../config/autoloader.php';

$proceso = isset($_POST['proceso']) && $_POST['proceso'] ? $_POST['proceso'] : '';
$id_area = isset($_POST['id_area']) && $_POST['id_area'] ? $_POST['id_area'] : '-1';

$cmd = \Config\Clases\Conexion::getConexion();

?>

<div class="px-0">
    <div class="shadow">
        <div class="card-header py-2 text-center" style="background-color: #16a085 !important;">
            <h7 style="color: white;">BUSCAR ACTIVOS FIJOS</h7>
        </div>
        <div class="p-2">

            <!--Formulario de busqueda de activos fijos-->
            <form id="frm_buscar_activos_fijos">
                <div class="row mb-2">
                    <input type="hidden" id="proceso_fil" value="<?php echo $proceso ?>">
                    <input type="hidden" id="id_area_fil" value="<?php echo $id_area ?>">
                    <div class="col-md-2">
                        <input type="text" class="filtro_acf form-control form-control-sm bg-input" id="txt_placa_acf_fil" placeholder="Placa">
                    </div>
                    <div class="col-md-2">
                        <input type="text" class="filtro_acf form-control form-control-sm bg-input" id="txt_codigo_art_fil" placeholder="Codigo">
                    </div>
                    <div class="col-md-2">
                        <input type="text" class="filtro_acf form-control form-control-sm bg-input" id="txt_nombre_art_fil" placeholder="Nombre">
                    </div>
                    <div class="col-md-1">
                        <a type="button" id="btn_buscar_activofijo_fil" class="btn btn-outline-success btn-sm" title="Filtrar">
                            <span class="fas fa-search fa-lg" aria-hidden="true"></span>
                        </a>
                    </div>
                </div>
            </form>
            <div style="height:400px" class="overflow-auto">
                <table id="tb_activos_fijos" class="table table-striped table-bordered table-sm nowrap table-hover shadow" style="width:100%; font-size:80%">
                    <thead>
                        <tr class="text-center">
                            <th>Id</th>
                            <th>Placa</th>
                            <th>Código</th>
                            <th>Artículo</th>
                            <th>Activo Fijo</th>
                            <th>No. Serial</th>
                            <th>Marca</th>
                            <th>Sede</th>
                            <th>Area</th>
                            <th>Responsable</th>
                            <th>Estado General</th>
                            <th>Estado</th>
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
            $('#tb_activos_fijos').DataTable({
                language: dataTable_es,
                processing: true,
                serverSide: true,
                searching: false,
                autoWidth: false,
                ajax: {
                    url: '../common/buscar_activo_fijo_lista.php',
                    type: 'POST',
                    dataType: 'json',
                    data: function(data) {
                        data.proceso = $('#proceso_fil').val();
                        data.id_area = $('#id_area_fil').val();
                        data.placa = $('#txt_placa_acf_fil').val();
                        data.codigo = $('#txt_codigo_art_fil').val();
                        data.nombre = $('#txt_nombre_art_fil').val();
                    }
                },
                columns: [{
                        'data': 'id_activo_fijo'
                    }, //Index=0
                    {
                        'data': 'placa'
                    },
                    {
                        'data': 'cod_articulo'
                    },
                    {
                        'data': 'nom_articulo'
                    },
                    {
                        'data': 'des_activo'
                    },
                    {
                        'data': 'num_serial'
                    },
                    {
                        'data': 'nom_marca'
                    },
                    {
                        'data': 'nom_sede'
                    },
                    {
                        'data': 'nom_area'
                    },
                    {
                        'data': 'nom_responsable'
                    },
                    {
                        'data': 'nom_estado_general'
                    },
                    {
                        'data': 'nom_estado'
                    }
                ],
                columnDefs: [{
                    class: 'text-wrap',
                    targets: '_all'
                }],
                order: [
                    [0, "desc"]
                ],
                lengthMenu: [
                    [10, 25, 50, -1],
                    [10, 25, 50, 'TODO'],
                ]
            });
            $('#tb_activos_fijos').wrap('<div class="overflow"/>');
        });
    })(jQuery);

    //Buascar registros de articulos de Articulos
    $('#btn_buscar_activofijo_fil').on("click", function() {
        $('#tb_activos_fijos').DataTable().ajax.reload(null, false);
    });

    $('.filtro_acf').keypress(function(e) {
        if (e.keyCode == 13) {
            $('#tb_activos_fijos').DataTable().ajax.reload(null, false);
        }
    });

    $('.filtro_acf').mouseup(function(e) {
        $('#tb_activos_fijos').DataTable().ajax.reload(null, false);
    });
</script>