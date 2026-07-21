<?php
session_start();
if (!isset($_SESSION['user'])) {
    header('Location: ../../index.php');
    exit();
}

include '../../../../config/autoloader.php';
include '../common/funciones_generales.php';

$cmd = \Config\Clases\Conexion::getConexion();

$id = isset($_POST['id']) ? $_POST['id'] : -1;

try {
    $sql = "SELECT far_pedido.id_pedido,far_pedido.num_pedido,far_pedido.fec_pedido,far_pedido.hor_pedido,far_pedido.detalle,far_pedido.val_total,
            ss.nom_sede AS nom_sede_solicita,bs.nombre AS nom_bodega_solicita,bs.id_bodega AS id_bodega_solicita,
            sp.nom_sede AS nom_sede_provee,bp.nombre AS nom_bodega_provee,bp.id_bodega AS id_bodega_provee,                    
            CASE far_pedido.estado WHEN 0 THEN 'ANULADO' WHEN 1 THEN 'PENDIENTE' WHEN 2 THEN 'CONFIRMADO' WHEN 3 THEN 'FINALIZADO' END AS estado,
            CASE far_pedido.estado WHEN 0 THEN far_pedido.fec_anulacion WHEN 1 THEN far_pedido.fec_creacion ELSE far_pedido.fec_cierre END AS fec_estado,
            CONCAT_WS(' ',usr.nombre1,usr.nombre2,usr.apellido1,usr.apellido2) AS usr_cierra,
            usr.descripcion AS usr_perfil,usr.nom_firma
        FROM far_pedido             
        INNER JOIN tb_sedes AS ss ON (ss.id_sede = far_pedido.id_sede_destino)
        INNER JOIN far_bodegas AS bs ON (bs.id_bodega = far_pedido.id_bodega_destino)           
        INNER JOIN tb_sedes AS sp ON (sp.id_sede = far_pedido.id_sede_origen)
        INNER JOIN far_bodegas AS bp ON (bp.id_bodega = far_pedido.id_bodega_origen)
        LEFT JOIN seg_usuarios_sistema AS usr ON (usr.id_usuario=far_pedido.id_usr_cierre)
        WHERE id_pedido=" . $id . " LIMIT 1";
    $rs = $cmd->query($sql);
    $obj_e = $rs->fetch();

     $sql = "SELECT far_medicamentos.id_med,far_medicamentos.cod_medicamento,far_medicamentos.nom_medicamento,
		    far_for_farmaceutica.descripcion AS forma_farmaceutica,
            far_pedido_detalle.cantidad,far_pedido_detalle.valor,
            (far_pedido_detalle.cantidad*far_pedido_detalle.valor) AS val_total
        FROM far_pedido_detalle
        INNER JOIN far_medicamentos ON (far_medicamentos.id_med = far_pedido_detalle.id_medicamento)
        INNER JOIN far_for_farmaceutica ON (far_for_farmaceutica.id_for = far_medicamentos.id_formafarmaceutica)
        WHERE far_pedido_detalle.id_pedido=" . $id . " ORDER BY far_medicamentos.nom_medicamento";
    $rs = $cmd->query($sql);
    $obj_ds = $rs->fetchAll();
    
    $id_sede_destino = $obj_e['id_bodega_solicita'];
    $fec_pedido = $obj_e['fec_pedido'];

    $sql = "SELECT ip_sede,bd_sede,pw_sede,us_sede,pt_http FROM tb_sedes WHERE id_sede=$id_sede_destino LIMIT 1";
    $rs = $cmd->query($sql);
    $obj_sede = $rs->fetch();

    $ip_pr = explode(':', $obj_sede['ip_sede']);
    $ip = $ip_pr[0];
    $port = $ip_pr[1];
    $database = $obj_sede['bd_sede'];
    $password = $obj_sede['pw_sede'];
    $user = $obj_sede['us_sede'];
   
    $continuar = true;
    $mensaje = '';

    if (!isMySQLPortOpen($ip, $port) && $continuar) {
        $mensaje = "Error: El servidor MySQL no responde en $ip:$port. Verifique el servicio.";
        $continuar = false;
    }
    if ($continuar) {
        list($ok, $msg) = canConnectToDatabase($ip, $port, $user, $password, $database);
        if (!$ok) {
            $mensaje = "Error: No se pudo conectar a la base de datos '$database' en $ip:$port.<br>Detalle: $msg";
            $continuar = false;
        }
    }

    if ($continuar) {
        $bd_driver = "mysql";
        $charset = "charset=utf8";
        $cmd1 = new PDO("$bd_driver:host=$ip;port=$port;dbname=$database;$charset", $user, $password);
        
        $sql = "SELECT GROUP_CONCAT(id_medicamento) AS ids_med FROM far_pedido_detalle WHERE id_pedido=" . $id . " LIMIT 1";
        $rs = $cmd->query($sql);
        $obj = $rs->fetch();
        $ids = $obj['ids_med'];

        $fecha = new DateTime($fec_pedido);
        $fecha->modify('-1 month');
        $anio = (int)$fecha->format('Y');
        $mes  = (int)$fecha->format('m');

        $sql = "SELECT med.id_origen,med.existencia,
                    IF(fac.can_fac IS NULL, 0, fac.can_fac) AS cantidad_fac,
                    IF(egr.can_egr IS NULL, 0, egr.can_egr) AS cantidad_egr
            FROM (SELECT id_origen,existencia
                FROM far_medicamentos
                WHERE far_medicamentos.id_origen IN ($ids)
                ) AS med
            LEFT JOIN (SELECT far_medicamentos.id_origen, SUM(fac_facturacion_detalle.cantidad) AS can_fac
                FROM fac_facturacion_detalle
                INNER JOIN fac_facturacion ON (fac_facturacion.id_factura = fac_facturacion_detalle.id_factura)
                INNER JOIN far_medicamento_lote ON (far_medicamento_lote.id_lote = fac_facturacion_detalle.id_medicamento)
                INNER JOIN far_medicamentos ON (far_medicamentos.id_med = far_medicamento_lote.id_med)
                WHERE fac_facturacion.estado>=2 AND far_medicamentos.id_origen IN ($ids) AND
                    YEAR(fac_facturacion.fec_cierre) = $anio AND MONTH(fac_facturacion.fec_cierre) = $mes
                GROUP BY far_medicamentos.id_origen) AS fac ON (fac.id_origen = med.id_origen)
            LEFT JOIN (SELECT far_medicamentos.id_origen, SUM(far_orden_egreso_detalle.cantidad) AS can_egr
                FROM far_orden_egreso_detalle
                INNER JOIN far_orden_egreso ON (far_orden_egreso.id_egreso = far_orden_egreso_detalle.id_egreso)
                INNER JOIN far_medicamento_lote ON (far_medicamento_lote.id_lote = far_orden_egreso_detalle.id_lote)
                INNER JOIN far_medicamentos ON (far_medicamentos.id_med = far_medicamento_lote.id_med)
                WHERE far_orden_egreso.estado=2 AND far_medicamentos.id_origen IN ($ids) AND
                    YEAR(far_orden_egreso.fec_cierre) = $anio AND MONTH(far_orden_egreso.fec_cierre) = $mes
                GROUP BY far_medicamentos.id_origen) AS egr ON (egr.id_origen = med.id_origen)";
                $rs = $cmd1->query($sql);
        $obj_dsr = $rs->fetchAll();

        // Convertir resultado a arreglo asociativo indexado por id_origen
        $dsr_map = [];
        if (is_array($obj_dsr) && count($obj_dsr) > 0) {
            foreach ($obj_dsr as $row) {                
                if (isset($row['id_origen'])) {
                    $dsr_map[$row['id_origen']] = $row;
                }
            }
        }
    }
        
} catch (PDOException $e) {
    echo $e->getCode() == 2002 ? 'Sin Conexión a Mysql (Error: 2002)' : 'Error: ' . $e->getCode();
}
?>
<div class="text-end py-3">
    <a type="button" id="btnExcelEntrada" class="btn btn-outline-success btn-sm" value="01" title="Exprotar a Excel">
        <span class="fas fa-file-excel fa-lg" aria-hidden="true"></span>
    </a>
    <a type="button" class="btn btn-primary btn-sm" id="btnImprimir">Imprimir</a>
    <a type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal"> Cerrar</a>
</div>
<div class="content bg-light" id="areaImprimir">
    <style>
        @media print {
            body {
                font-family: Arial, sans-serif;
            }
        }

        .resaltar:nth-child(even) {
            background-color: #F8F9F9;
        }

        .resaltar:nth-child(odd) {
            background-color: #ffffff;
        }
    </style>

    <?php if (!$continuar) : ?>
        <div class="alert alert-danger" role="alert">
            <?php echo $mensaje; ?>
        </div>
    <?php else : 
        
        include('../common/reporte_header.php'); ?>

        <table style="width:100%; font-size:70%">
            <tr style="text-align:center">
                <th>ORDEN DE PEDIDO DE BODEGA SPSR</th>
            </tr>
        </table>

        <table style="width:100%; font-size:60%; text-align:left; border:#A9A9A9 1px solid;">
            <tr style="background-color:#CED3D3; border:#A9A9A9 1px solid">
                <td>Id. Pedido</td>
                <td>No. Pedido</td>
                <td>Fecha Pedido</td>
                <td>Hora Pedido</td>
                <td>Estado</td>
                <td>Fecha Estado</td>
            </tr>
            <tr>
                <td><?php echo $obj_e['id_pedido']; ?></td>
                <td><?php echo $obj_e['num_pedido']; ?></td>
                <td><?php echo $obj_e['fec_pedido']; ?></td>
                <td><?php echo $obj_e['hor_pedido']; ?></td>
                <td><?php echo $obj_e['estado']; ?></td>
                <td><?php echo $obj_e['fec_estado']; ?></td>
            </tr>
            <tr style="background-color:#CED3D3; border:#A9A9A9 1px solid">
                <td colspan="3">Sede y Bodega DE donde se solicita</td>
                <td colspan="3">Sede y Bodega Principal (Proveedor)</td>
            </tr>
            <tr>
                <td colspan="2"><?php echo $obj_e['nom_sede_solicita']; ?></td>
                <td><?php echo $obj_e['nom_bodega_solicita']; ?></td>
                <td colspan="2"><?php echo $obj_e['nom_sede_provee']; ?></td>
                <td><?php echo $obj_e['nom_bodega_provee']; ?></td>
            </tr>
            <tr style="background-color:#CED3D3; border:#A9A9A9 1px solid">
                <td colspan="6">Detalle</td>
            </tr>
            <tr>
                <td colspan="6"><?php echo $obj_e['detalle']; ?></td>
            </tr>
        </table>

        <table style="width:100% !important">
            <thead style="font-size:60%">
                <tr style="background-color:#CED3D3; color:#000000; text-align:center">
                    <th rowspan="2">Item</th>
                    <th rowspan="2">Código</th>
                    <th rowspan="2">Descripción</th>
                    <th rowspan="2">Form. Farmacéutica</th>
                    <th colspan="7">CANTIDADES</th>
                    <th rowspan="2">Lote</th>
                    <th rowspan="2">Fec. Vence</th>
                    <th rowspan="2">Observaciones</th>
                </tr>
                <tr style="background-color:#CED3D3; color:#000000; text-align:center">
                    <th>Stock</th>
                    <th>Facturado</th>
                    <th>Egresado</th>
                    <th>Solicitud</th>
                    <th>Anticipado</th>
                    <th>Despachado</th>
                    <th>Pendiente</th>
                </tr>
            </thead>
            <tbody style="font-size: 60%;">
                <?php
                $tabla = '';
                $item = 1;
                foreach ($obj_ds as $obj) {
                    // valores por defecto
                    $existencia = '';
                    $facturado = '';
                    $egresado = '';

                    // Si existe en el arreglo dsr_map, rellenar facturado y egresado
                    if (isset($dsr_map) && isset($dsr_map[$obj['id_med']])) {
                        $dsr = $dsr_map[$obj['id_med']];
                        $existencia = isset($dsr['existencia']) ? $dsr['existencia'] : '';
                        $facturado = isset($dsr['cantidad_fac']) ? $dsr['cantidad_fac'] : '';
                        $egresado = isset($dsr['cantidad_egr']) ? $dsr['cantidad_egr'] : '';                        
                    }

                    $tabla .=  '<tr class="resaltar">'
                        . '<td>' . $item . '</td>'
                        . '<td>' . $obj['cod_medicamento'] . '</td>'
                        . '<td style="text-align:left">' . mb_strtoupper($obj['nom_medicamento']) . '</td>'
                        . '<td style="text-align:left">' . mb_strtoupper($obj['forma_farmaceutica']) . '</td>'
                        . '<td style="text-align:center">' . $existencia . '</td>'
                        . '<td style="text-align:center">' . $facturado . '</td>'
                        . '<td style="text-align:center">' . $egresado . '</td>'
                        . '<td style="text-align:center">' . $obj['cantidad'] . '</td>'
                        . '<td></td>'
                        . '<td></td>'
                        . '<td></td>'
                        . '<td></td>'
                        . '<td></td>'
                        . '<td></td>'
                        . '</tr>';
                    $item++;
                }
                echo $tabla;
                ?>
            </tbody>
            <tfoot style="font-size:60%">
                <tr style="background-color:#CED3D3; color:#000000">
                    <td colspan="14"></td>
                </tr>
            </tfoot>
        </table>

        <table style="width:100%; font-size:70%; text-align:center">
            <tr>
                <td style="width:50%">
                    <?php if ($obj_e['nom_firma']) : ?>
                        <img src="<?php echo $ruta_firmas . $obj_e['nom_firma'] ?>">
                    <?php endif; ?>
                </td>
                <td style="width:50%">
                </td>
            </tr>
            <tr>
                <td style="vertical-align: top">
                    <div>-------------------------------------------------</div>
                    <div><?php echo $obj_e['usr_cierra']; ?></div>
                    <div><?php echo $obj_e['usr_perfil']; ?></div>
                </td>
                <td style="vertical-align: top">
                    <div>-------------------------------------------------</div>
                    <div>Aceptado Por</div>
                </td>
            </tr>
        </table>

    <?php endif; ?>    

</div>