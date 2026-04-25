<?php
session_start();
ini_set('max_execution_time', 5600);

include '../../../config/autoloader.php';
include '../../financiero/consultas.php';

$vigencia = $_SESSION['vigencia'];
$fecha_inicial = $_POST['fecha_inicial'] ?? $_POST['fecha_ini'] ?? '';
$fecha_corte = $_POST['fecha_final'] ?? $_POST['fecha_fin'] ?? '';

// Configurar encabezados para forzar la descarga de un archivo CSV
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="movimientos_integracion_' . date('YmdHis') . '.csv"');
header('Pragma: no-cache');
header('Expires: 0');

// Abrir la salida estándar de PHP como un stream
$output = fopen('php://output', 'w');

// Escribir el BOM para que Excel reconozca correctamente el UTF-8
fprintf($output, chr(0xEF) . chr(0xBB) . chr(0xBF));

// Escribir los encabezados de las columnas del CSV
fputcsv($output, ['Sede', 'Código', 'Nombre', 'No. Documento', 'Valor'], ',');

// Liberar buffer para que empiece a descargar de inmediato los encabezados
ob_flush();
flush();

$cmd = \Config\Clases\Conexion::getConexion();

// Obtener sedes activas
$datos_sedes = obtenerSedesActivas($cmd);
$sedes = $datos_sedes['sedes'];

$total = 0;

// Iterar sobre todas las sedes activas
foreach ($sedes as $sede) {
    if ($sede['es_principal'] == 1) {
        $cmd_sede = $cmd;
    } else {
        $cmd_sede = conectarSede($sede['bd_sede']);
        if ($cmd_sede === null) {
            error_log("No se pudo conectar a la sede {$sede['nom_sede']}");
            continue;
        }
    }

    try {
        $sql = "WITH pagos_excluidos AS (
          SELECT DISTINCT d.id_pago
          FROM ctb_doc d
          WHERE d.subtipo_movimiento IN (112, 204)
        )
        SELECT
          x.cod,
          x.nombre,
          x.id_manu,
          SUM(x.valor) AS valor
        FROM (
          SELECT
            f.cod,
            f.nombre,
            d.id_manu,
            SUM(l.credito) AS valor
          FROM ctb_libaux l
          JOIN ctb_doc    d ON l.id_ctb_doc = d.id_ctb_doc
          JOIN ctb_fuente f ON d.id_tipo_doc = f.id_doc_fuente
          JOIN ctb_pgcp   p ON l.id_cuenta  = p.id_pgcp
          WHERE f.cod IN ('FCFG')
            AND d.fecha BETWEEN '$fecha_inicial' AND '$fecha_corte'
            AND (p.cuenta LIKE '4312%' OR p.cuenta LIKE '2910%')
          GROUP BY f.cod, f.nombre, d.id_manu
          UNION ALL
          SELECT
            f.cod,
            f.nombre,
            d.id_manu,
            SUM(l.debito) AS valor
          FROM ctb_libaux l
          JOIN ctb_doc    d ON l.id_ctb_doc = d.id_ctb_doc
          JOIN ctb_fuente f ON d.id_tipo_doc = f.id_doc_fuente
          JOIN ctb_pgcp   p ON l.id_cuenta  = p.id_pgcp
          WHERE f.cod IN ('FCFP','FCFA','FCDD','FCGI','FCGD','RECC')
            AND d.fecha BETWEEN '$fecha_inicial' AND '$fecha_corte'
            AND (p.cuenta LIKE '4312%' OR p.cuenta LIKE '2910%' OR p.cuenta LIKE '5890%')
          GROUP BY f.cod, f.nombre, d.id_manu
          UNION ALL
          SELECT
            f.cod,
            f.nombre,
            d.id_manu,
            SUM(l.debito) AS valor
          FROM ctb_libaux l
          JOIN ctb_doc    d ON l.id_ctb_doc = d.id_ctb_doc
          JOIN ctb_fuente f ON d.id_tipo_doc = f.id_doc_fuente
          JOIN ctb_pgcp   p ON l.id_cuenta  = p.id_pgcp
          WHERE f.cod IN ('FCFP')
            AND d.fecha BETWEEN '$fecha_inicial' AND '$fecha_corte'
            AND p.cuenta LIKE '1319%'
          GROUP BY f.cod, f.nombre, d.id_manu
          UNION ALL
          SELECT
            f.cod,
            f.nombre,
            d.id_manu,
            SUM(l.debito) AS valor
          FROM ctb_doc d
          JOIN ctb_libaux l ON l.id_ctb_doc = d.id_ctb_doc
          JOIN ctb_fuente f ON d.id_tipo_doc = f.id_doc_fuente
          JOIN ctb_pgcp   p ON l.id_cuenta  = p.id_pgcp
          WHERE (d.subtipo_movimiento IN (111,203,406) OR d.subtipo_movimiento IS NULL)
            AND f.cod IN ('RECC')
            AND d.fecha BETWEEN '$fecha_inicial' AND '$fecha_corte'
            AND NOT EXISTS (
              SELECT 1
              FROM pagos_excluidos pe
              WHERE pe.id_pago = d.id_pago
            )
          GROUP BY f.cod, f.nombre, d.id_manu
        ) AS x
        GROUP BY x.cod, x.nombre, x.id_manu
        ORDER BY x.id_manu";

        $res = $cmd_sede->query($sql);

        // Optimización: en lugar de guardar todos los registros de la sede en memoria,
        // los vamos escribiendo en el CSV línea a línea a medida que la BD los devuelve.
        while ($registro = $res->fetch(PDO::FETCH_ASSOC)) {
            $valor_formato = number_format($registro['valor'], 2, '.', '');
            fputcsv($output, [
                mb_convert_encoding($sede['nom_sede'], 'UTF-8', 'auto'),
                $registro['cod'],
                mb_convert_encoding($registro['nombre'], 'UTF-8', 'auto'),
                $registro['id_manu'],
                $valor_formato
            ], ',');

            $total += $registro['valor'];
        }
        $res->closeCursor();

        // Liberar el buffer al finalizar cada sede para que la descarga sea progresiva
        ob_flush();
        flush();
    } catch (Exception $e) {
        error_log("Error consultando movimientos en sede {$sede['nom_sede']}: " . $e->getMessage());
    }

    if ($sede['es_principal'] != 1) {
        $cmd_sede = null;
    }
}

// Escribir fila de totales al final
fputcsv($output, ['', '', '', 'TOTAL', number_format($total, 2, '.', '')], ',');

// Cerrar el stream
fclose($output);
exit();
