<?php
session_start();
if (!isset($_SESSION['user'])) {
    header("Location: ../../../index.php");
    exit();
}
include '../../../../config/autoloader.php';

$cmd = \Config\Clases\Conexion::getConexion();
$fecha_sis = date('Y-m-d');
?>

<div class="px-0">
    <div class="shadow">
        <div class="card-header py-2 text-center bg-sofia">
            <h5 class="text-white mb-0">INGRESOS DE ALMACEN POR FIANZA</h5>
        </div>
        <div class="p-2">

            <!--Formulario de busqueda de articulos-->
            <form id="frm_buscar_ingresos">
                <div class="row mb-2">
                    <div class="col-md-2">
                        <input type="text" class="filtro_ing form-control form-control-sm bg-input" id="txt_num_ing_fil" placeholder="No. Ingreso">
                    </div>
                    <div class="col-md-2">
                        <input type="date" class="filtro_ing form-control form-control-sm bg-input" id="txt_fecini_fil" name="txt_fecini_fil" placeholder="Fecha Inicial" value="<?php echo $fecha_sis ?>">
                    </div>
                    <div class="col-md-2">
                        <input type="date" class="filtro_ing form-control form-control-sm bg-input" id="txt_fecfin_fil" name="txt_fecfin_fil" placeholder="Fecha Final" value="<?php echo $fecha_sis ?>">
                    </div>
                    <div class="col-md-2">
                        <button type="button" id="btn_buscar_ing_fil" class="btn btn-outline-success btn-sm" title="Filtrar">
                            <span class="fas fa-search fa-lg" aria-hidden="true"></span>
                        </button>
                    </div>
                </div>
            </form>
            <div style="height:400px" class="overflow-auto">
                <table id="tb_ingresos_fz" class="table table-striped table-bordered table-sm nowrap table-hover shadow align-middle w-100" style="font-size:80%">
                    <thead>
                        <tr class="text-center">
                            <th class="bg-sofia">Id</th>
                            <th class="bg-sofia">No. Ingreso</th>
                            <th class="bg-sofia">Fecha Ingreso</th>
                            <th class="bg-sofia">Detalle</th>
                            <th class="bg-sofia">Id.Proveedor</th>
                            <th class="bg-sofia">Proveedor</th>
                            <th class="bg-sofia">Id.Sede</th>
                            <th class="bg-sofia">Sede</th>
                            <th class="bg-sofia">Id.Bodega</th>
                            <th class="bg-sofia">Bodega</th>
                            <th class="bg-sofia">Ver</th>
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
            $('#tb_ingresos_fz').DataTable({
                language: dataTable_es,
                processing: true,
                serverSide: true,
                searching: false,
                autoWidth: false,
                ajax: {
                    url: 'buscar_ingresos_lista.php',
                    type: 'POST',
                    dataType: 'json',
                    data: function(data) {
                        data.num_ingido = $('#txt_num_ing_fil').val();
                        data.fec_ini = $('#txt_fecini_fil').val();
                        data.fec_fin = $('#txt_fecfin_fil').val()
                    }
                },
                columns: [{
                        'data': 'id_ingreso'
                    }, //Index=0
                    {
                        'data': 'num_ingreso'
                    },
                    {
                        'data': 'fec_ingreso'
                    },
                    {
                        'data': 'detalle'
                    },
                    {
                        'data': 'id_provedor'
                    },
                    {
                        'data': 'nom_tercero'
                    },
                    {
                        'data': 'id_sede'
                    },
                    {
                        'data': 'nom_sede'
                    },
                    {
                        'data': 'id_bodega'
                    },
                    {
                        'data': 'nom_bodega'
                    },
                    {
                        'data': 'botones'
                    }
                ],
                columnDefs: [{
                        class: 'text-wrap',
                        targets: [3, 5]
                    },
                    {
                        width: '5%',
                        targets: [0, 1, 2, 7, 9]
                    },
                    {
                        visible: false,
                        targets: [4, 6, 8]
                    },
                    {
                        orderable: false,
                        targets: 10
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
            $('#tb_ingidos_tra').wrap('<div class="overflow"/>');
        });
    })(jQuery);

    //Buascar registros de articulos 
    $('#btn_buscar_ing_fil').on("click", function() {
        $('#tb_ingresos_fz').DataTable().ajax.reload(null, false);
    });

    $('.filtro_ing').keypress(function(e) {
        if (e.keyCode == 13) {
            $('#tb_ingresos_fz').DataTable().ajax.reload(null, false);
        }
    });

    $('.filtro_ing').click(function(e) {
        $('#tb_ingresos_fz').DataTable().ajax.reload(null, false);
    });
</script>