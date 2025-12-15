<?php
session_start();
if (!isset($_SESSION['user'])) {
    header('Location: ../../../index.php');
    exit();
}
include_once '../../../config/autoloader.php';
$cmd = \Config\Clases\Conexion::getConexion();

$id_cdp = isset($_POST['id_cdp']) ? $_POST['id_cdp'] : -1;
$otro_form = isset($_POST['otro_form']) ? $_POST['otro_form'] : 0;

// se vuelve a consultar los datos del cdp con el id que viene del boton
//------------------------------------
$sql = "SELECT
	        pto_cdp.id_pto_cdp
            ,pto_cdp.id_manu
            , DATE_FORMAT(pto_cdp.fecha, '%Y-%m-%d') AS fecha                         
        FROM
            pto_cdp                          
        WHERE pto_cdp.id_pto_cdp = $id_cdp LIMIT 1";
$rs = $cmd->query($sql);
$obj = $rs->fetch();
//----------------------------------------------------- hago la consulta aqui para que los saldos sean cajas de texto
$sql = "WITH
            cdp_por_rubro AS (
            SELECT
                p.id_pto_cdp,
                p.id_rubro,
                SUM(p.valor) AS valorcdp,
                SUM(IFNULL(p.valor_liberado,0)) AS cdpliberado
            FROM pto_cdp_detalle p
            WHERE p.id_pto_cdp = $id_cdp
            GROUP BY p.id_pto_cdp, p.id_rubro
            ),
            crp_por_cdp_det AS (
            SELECT
                pcd.id_pto_cdp_det,
                SUM(pcd.valor) AS valorcrp,
                SUM(IFNULL(pcd.valor_liberado,0)) AS crpliberado
            FROM pto_crp_detalle pcd
            JOIN pto_crp pc ON pcd.id_pto_crp = pc.id_pto_crp
            WHERE pc.estado = 2
            GROUP BY pcd.id_pto_cdp_det
            ),
            crp_por_rubro AS (
            SELECT
                pcd.id_rubro,
                SUM(IFNULL(crp.valorcrp,0))     AS valorcrp,
                SUM(IFNULL(crp.crpliberado,0))  AS crpliberado
            FROM (
                SELECT id_pto_cdp_det, id_rubro
                FROM pto_cdp_detalle
                WHERE id_pto_cdp = $id_cdp
            ) pcd
            LEFT JOIN crp_por_cdp_det crp ON crp.id_pto_cdp_det = pcd.id_pto_cdp_det
            GROUP BY pcd.id_rubro
            )
            SELECT
            c.id_pto_cdp,
            c.id_rubro,
            pc.cod_pptal,
            c.valorcdp,
            c.cdpliberado,
            IFNULL(r.valorcrp,0)    AS valorcrp,
            IFNULL(r.crpliberado,0) AS crpliberado,
            ((c.valorcdp - c.cdpliberado) - (IFNULL(r.valorcrp,0) - IFNULL(r.crpliberado,0))) AS saldo_final,
            GREATEST(0, ((c.valorcdp - c.cdpliberado) - (IFNULL(r.valorcrp,0) - IFNULL(r.crpliberado,0)))) AS puede_liberar
            FROM cdp_por_rubro c
            LEFT JOIN crp_por_rubro r ON r.id_rubro = c.id_rubro
            LEFT JOIN pto_cargue pc ON pc.id_cargue = c.id_rubro
            ORDER BY c.id_rubro";

$rs = $cmd->query($sql);
$obj_saldos = $rs->fetchAll(PDO::FETCH_ASSOC);
$rs->closeCursor();
unset($rs);

//---------------------------------------------------
?>
<div class="px-0">
    <div class="shadow">
        <div class="card-header p-2 text-center" style="background-color: #16a085 !important;">
            <h5 class="mb-0" style="color: white;">LIBERACION DE SALDOS</h5>
        </div>
        <div class="p-3">
            <form id="frm_liberarsaldos">
                <input type="hidden" id="id_cdp" name="id_cdp" value="<?= $id_cdp ?>">
                <div class="row mb-1">
                    <div class="col-md-3">
                        <label for="txt_num_cdp" class="small">NUMERO CDP</label>
                    </div>
                    <div class="col-md-9">
                        <input type="text" class="filtro form-control form-control-sm bg-secondary-subtle" id="txt_num_cdp" name="txt_num_cdp" readonly="true" value="<?= $obj['id_manu'] ?>">
                    </div>
                </div>
                <div class="row mb-1">
                    <div class="col-md-3">
                        <label for="txt_fec_cdp" class="small">FECHA CDP</label>
                    </div>
                    <div class="col-md-9">
                        <input type="text" class="filtro form-control form-control-sm bg-secondary-subtle" id="txt_fec_cdp" name="txt_fec_cdp" readonly="true" value="<?= $obj['fecha'] ?>">
                    </div>
                </div>
                <div class="row mb-1">
                    <div class="col-md-3">
                        <label for="txt_fec_lib" class="small">FECHA LIBERACION</label>
                    </div>
                    <div class="col-md-9">
                        <input type="date" class="form-control form-control-sm bg-input" id="txt_fec_lib" name="txt_fec_lib" placeholder="Fecha liberacion" value="<?= date('Y-m-d') ?>">
                    </div>
                </div>
                <div class="row mb-1">
                    <div class="col-md-3">
                        <label for="txt_concepto_lib" class="small">CONCEPTO LIBERACION</label>
                    </div>
                    <div class="col-md-9">
                        <input type="text" class="form-control form-control-sm bg-input" id="txt_concepto_lib" name="txt_concepto_lib" placeholder="Concepto liberacion">
                    </div>
                </div>

                <div class=" w-100 text-left">
                    <table id="tb_saldos" class="table table-striped table-bordered table-sm nowrap table-hover shadow w-100" style="width:100%; font-size:80%">
                        <thead>
                            <tr class="text-center centro-vertical">
                                <th class="bg-sofia">Id Rubro</th>
                                <th class="bg-sofia" style="min-width: 50%;">Codigo</th>
                                <th class="bg-sofia">Valor</th>
                                <th class="bg-sofia">Valor a liberar</th>
                            </tr>
                        </thead>
                        <tbody class="text-left centro-vertical" id="body_tb_saldos"></tbody>
                        <?php
                        foreach ($obj_saldos as $dll) {
                        ?>
                            <tr>
                                <td class="border" colspan="1">
                                    <input type="text" name="txt_id_rubro[]" class="form-control form-control-sm bg-plain bg-secondary-subtle" value="<?= $dll['id_rubro'] ?>" readonly="true">
                                </td>
                                <td class="border" colspan="1">
                                    <input type="text" name="txt_codigo[]" class="form-control form-control-sm  bg-plain bg-secondary-subtle" value="<?= $dll['cod_pptal'] ?>" readonly="true">
                                </td>
                                <td class="border" colspan="1">
                                    <input type="text" name="txt_valor[]" class="form-control form-control-sm bg-plain bg-secondary-subtle" value="<?= $dll['saldo_final'] ?>" readonly="true">
                                </td>
                                <td class="border" colspan="1">
                                    <input type="text" name="txt_valor_liberar[]" class="form-control form-control-sm valfno bg-plain bg-input" value="<?= $dll['saldo_final'] ?>">
                                </td>
                            </tr>
                        <?php
                        }
                        ?>
                    </table>
                </div>
            </form>
        </div>
    </div>
    <div class="text-end py-3">
        <a type="button" class="btn btn-primary btn-sm" onclick="RegLiberacionCdp()">Liberar</a>
        <a type="button" class="btn btn-secondary  btn-sm" data-bs-dismiss="modal">Cancelar</a>
    </div>
</div>

<script>
    /*
    (function($) {
        $(document).ready(function() {
            $('#tb_saldos').DataTable({
                language: dataTable_es,
                processing: true,
                serverSide: true,
                searching: false,
                autoWidth: false,
                ajax: {
                    url: ValueInput('host') + '/terceros/php/historialtercero/listar_saldos.php',
                    type: 'POST',
                    dataType: 'json',
                    data: function(data) {
                        data.id_cdp = $('#id_cdp').val();
                    }
                },
                columns: [{
                        'data': 'id_rubro'
                    }, //Index=0
                    {
                        'data': 'cod_pptal'
                    },
                    {
                        'data': 'saldo_final'
                    },
                ],
                columnDefs: [{
                        class: 'text-wrap',
                        targets: []
                    } //,
                    //{ width: '5%', targets: [0,1,3,4] }
                ],
                order: [
                    [2, "asc"]
                ],
                lengthMenu: [
                    [10, 25, 50, -1],
                    [10, 25, 50, 'TODO'],
                ]
            });
            $('#tb_saldos').wrap('<div class="overflow"/>');
        });
    })(jQuery);

    //Buascar registros de articulos de Articulos
    /*
    $('#btn_buscar_articulo_fil').on("click", function() {
        reloadtable('tb_articulos_activos');
    });

    $('.filtro_art').keypress(function(e) {
        if (e.keyCode == 13) {
            reloadtable('tb_articulos_activos');
        }
    });

    $('.filtro_art').mouseup(function(e) {
        reloadtable('tb_articulos_activos');
    });

    $('#sl_subgrupo_art_fil').on("change", function() {
        sessionStorage.setItem("id_subgrupo", $(this).val());
    });*/
</script>