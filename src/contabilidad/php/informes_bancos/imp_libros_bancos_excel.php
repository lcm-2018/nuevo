<?php
session_start();
ini_set("memory_limit", "-1");
ini_set("max_execution_time", "0");
if (!isset($_SESSION['user'])) {
    header('Location: ../../index.php');
    exit();
}

include '../../../../config/autoloader.php';
include '../../../financiero/consultas.php';
include 'funciones_generales.php';

$cmd = \Config\Clases\Conexion::getConexion();

$id_cuenta_ini = isset($_POST['id_cuenta_ini']) ? (int) $_POST['id_cuenta_ini'] : 0;
$id_cuenta_fin = isset($_POST['id_cuenta_fin']) ? (int) $_POST['id_cuenta_fin'] : 0;
$fec_ini = isset($_POST['fec_ini']) && strlen($_POST['fec_ini']) > 0 ? $_POST['fec_ini'] : '2020-01-01';
$fec_fin = isset($_POST['fec_fin']) && strlen($_POST['fec_fin']) > 0 ? $_POST['fec_fin'] : '2050-12-31';
$id_tipo_doc = isset($_POST['id_tipo_doc']) ? (int) $_POST['id_tipo_doc'] : 0;
$id_tercero = isset($_POST['id_tercero']) ? (int) $_POST['id_tercero'] : 0;

$and_where = '';
if ($id_tercero > 0) {
    $and_where .= " AND ctb_libaux.id_tercero_api = $id_tercero";
}
if ($id_tipo_doc > 0) {
    $and_where .= " AND ctb_doc.id_tipo_doc = $id_tipo_doc";
}

// Obtener sedes activas
$datos_sedes = obtenerSedesActivas($cmd);
$sedes = $datos_sedes['sedes'];
$sede_principal = $datos_sedes['sede_principal'];

// Optimo para cualquier hospital: Obtiene el nombre de la base de datos a la que se conectó la Clase Conexion
$bd_principal = $cmd->query("SELECT DATABASE()")->fetchColumn();

/**
 * Limpia saltos de línea y caracteres de control para evitar filas partidas en Excel,
 * y escribe la fila en CSV compatible con PHP 8.4+.
 */
function escribirFilaCsv($output, array $campos): void
{
    foreach ($campos as &$valor) {
        if (is_string($valor)) {
            $valor = trim(str_replace(["\r\n", "\r", "\n", "\t", "\v", "\f", "\0"], ' ', $valor));
        }
    }
    unset($valor);
    if (PHP_VERSION_ID >= 70400) {
        fputcsv($output, $campos, ',', '"', '');
    } else {
        fputcsv($output, $campos, ',', '"');
    }
}

/**
 * Resuelve la cuenta de detalle destino (coincidencia exacta o por prefijo).
 */
function resolverCuentaDestino(string $cuenta, array $cuentasIdx, array $prefijosDesc): ?string
{
    if (isset($cuentasIdx[$cuenta])) {
        return $cuenta;
    }
    foreach ($prefijosDesc as $prefijo) {
        $prefijo = (string) $prefijo;
        if ($prefijo === '' || strncmp($cuenta, $prefijo, strlen($prefijo)) === 0) {
            return $prefijo;
        }
    }
    return null;
}

try {
    $cmd = \Config\Clases\Conexion::getConexion();
    // Obtener el código (campo cuenta) de las cuentas límite inicial y final
    $sql_limites = "SELECT id_pgcp, cuenta FROM ctb_pgcp WHERE id_pgcp IN (:id_ini, :id_fin) AND estado = 1";
    $rs_lim = $cmd->prepare($sql_limites);
    $rs_lim->bindValue(':id_ini', $id_cuenta_ini, PDO::PARAM_INT);
    $rs_lim->bindValue(':id_fin', $id_cuenta_fin, PDO::PARAM_INT);
    $rs_lim->execute();
    $cuentas_limite = $rs_lim->fetchAll(PDO::FETCH_KEY_PAIR); // [id_pgcp => cuenta]
    $rs_lim->closeCursor();

    $cod_cuenta_ini = isset($cuentas_limite[$id_cuenta_ini]) ? $cuentas_limite[$id_cuenta_ini] : '';
    $cod_cuenta_fin = isset($cuentas_limite[$id_cuenta_fin]) ? $cuentas_limite[$id_cuenta_fin] : '';

    // Si no se encontraron los límites, usar rango abierto
    $where_rango = '';
    $params_rango = [];
    if ($cod_cuenta_ini !== '' && $cod_cuenta_fin !== '') {
        if (strcmp($cod_cuenta_ini, $cod_cuenta_fin) <= 0) {
            $cuenta_desde = $cod_cuenta_ini;
            $cuenta_hasta = $cod_cuenta_fin;
        } else {
            $cuenta_desde = $cod_cuenta_fin;
            $cuenta_hasta = $cod_cuenta_ini;
        }
        $where_rango = "AND ctb_pgcp.cuenta >= :cuenta_desde 
            AND ctb_pgcp.cuenta <= :cuenta_hasta";
        $params_rango[':cuenta_desde'] = $cuenta_desde;
        $params_rango[':cuenta_hasta'] = $cuenta_hasta;
    }
    $sql = "SELECT 
                 ctb_pgcp.id_pgcp
                ,ctb_pgcp.cuenta
                ,ctb_pgcp.nombre
                ,ctb_pgcp.tipo_dato 
            FROM ctb_pgcp 
            WHERE ctb_pgcp.estado = 1 AND ctb_pgcp.tipo_dato = 'D'
            $where_rango
            ORDER BY ctb_pgcp.cuenta ASC";
    $rs = $cmd->prepare($sql);
    foreach ($params_rango as $param => $value) {
        $rs->bindValue($param, $value, PDO::PARAM_STR);
    }
    $rs->execute();
    $obj_cuentas = $rs->fetchAll();
    $rs->closeCursor();
    unset($rs);

    $sql = 'SELECT razon_social_ips,nit_ips FROM tb_datos_ips LIMIT 1';
    $rs = $cmd->query($sql);
    $obj_ent = $rs ? $rs->fetch() : null;
    $razhd = $obj_ent['razon_social_ips'] ?? '';
    $nithd = $obj_ent['nit_ips'] ?? '';
    $cmd = null;

    $reg = 0;

    $filename = "reporte_" . date("Y-m-d_H-i-s") . ".csv";

    // Encabezados HTTP para forzar la descarga
    header("Content-Type: text/csv; charset=UTF-8");
    header("Content-Disposition: attachment; filename=\"$filename\"");
    header("Pragma: no-cache");
    header("Expires: 0");

    // Abrir salida directa y escribir BOM UTF-8 para que Excel interprete tildes y ñ correctamente
    $output = fopen("php://output", "w");
    fwrite($output, "\xEF\xBB\xBF");

    // Escribir encabezados generales del reporte SIEMPRE (para evitar que descargue un archivo vacío si no hay datos)
    escribirFilaCsv($output, ["ENTIDAD", $razhd]);
    escribirFilaCsv($output, ["NIT", $nithd]);
    escribirFilaCsv($output, ["REPORTE", "LIBROS AUXILIARES"]);
    escribirFilaCsv($output, ["FECHA INICIAL", $fec_ini]);
    escribirFilaCsv($output, ["FECHA FINAL", $fec_fin]);

    $movimientosPorCuenta = [];
    $saldosPorCuenta = [];

    if (!empty($obj_cuentas)) {
        $cuentasIdx = [];
        foreach ($obj_cuentas as $obj_c) {
            $cuentasIdx[(string) $obj_c['cuenta']] = true;
        }
        $prefijosDesc = array_keys($cuentasIdx);
        usort($prefijosDesc, function ($a, $b) {
            return strlen((string) $b) <=> strlen((string) $a);
        });

        $where_rango_sede = '';
        $params_rango_sede = [];
        if ($cod_cuenta_ini !== '' && $cod_cuenta_fin !== '') {
            $where_rango_sede = " AND (ctb_pgcp.cuenta >= :cuenta_desde AND (ctb_pgcp.cuenta <= :cuenta_hasta OR ctb_pgcp.cuenta LIKE :cuenta_hasta_like))";
            $params_rango_sede[':cuenta_desde'] = $cuenta_desde;
            $params_rango_sede[':cuenta_hasta'] = $cuenta_hasta;
            $params_rango_sede[':cuenta_hasta_like'] = $cuenta_hasta . '%';
        }

        $sqlMovimientos = "SELECT
                    DATE_FORMAT(ctb_doc.fecha, '%Y-%m-%d') AS fecha,
                    ctb_pgcp.cuenta,
                    ctb_libaux.id_tercero_api,
                    IFNULL(ctb_libaux.debito, 0) AS debito,
                    IFNULL(ctb_libaux.credito, 0) AS credito,
                    ctb_doc.id_tipo_doc,
                    ctb_fuente.cod AS cod_tipo_doc,
                    ctb_fuente.nombre AS nom_tipo_doc,
                    ctb_doc.id_manu,
                    CONCAT(
                        IFNULL(
                            CASE ctb_doc.tipo_movimiento
                                WHEN 1 THEN CONCAT(ff.prefijo, IFNULL(ff.num_efactura, ff.num_factura))
                                WHEN 2 THEN CONCAT(fo.prefijo, IFNULL(fo.num_efactura, fo.num_factura))
                                WHEN 3 THEN CONCAT(fv.prefijo, IFNULL(fv.num_efactura, fv.num_factura))
                                WHEN 4 THEN CONCAT(fc.prefijo, fc.num_factura)
                                ELSE NULL
                            END,
                            ''
                        ),
                        ' - ',
                        ctb_doc.detalle
                    ) AS detalle,
                    tes_forma_pago.forma_pago,
                    tb_terceros.nom_tercero,
                    tb_terceros.nit_tercero
                FROM ctb_libaux
                INNER JOIN ctb_doc ON (ctb_libaux.id_ctb_doc = ctb_doc.id_ctb_doc)
                INNER JOIN ctb_pgcp ON (ctb_libaux.id_cuenta = ctb_pgcp.id_pgcp)
                INNER JOIN ctb_fuente ON (ctb_doc.id_tipo_doc = ctb_fuente.id_doc_fuente)
                LEFT JOIN tes_detalle_pago ON (tes_detalle_pago.id_ctb_doc = ctb_doc.id_ctb_doc)
                LEFT JOIN tes_forma_pago ON (tes_detalle_pago.id_forma_pago = tes_forma_pago.id_forma_pago)
                LEFT JOIN tb_terceros ON (tb_terceros.id_tercero_api = ctb_libaux.id_tercero_api)
                LEFT JOIN fac_facturacion ff ON (ctb_doc.tipo_movimiento = 1 AND ctb_doc.id_manu = ff.id_factura)
                LEFT JOIN fac_otros fo       ON (ctb_doc.tipo_movimiento = 2 AND ctb_doc.id_manu = fo.id_factura)
                LEFT JOIN far_ventas fv      ON (ctb_doc.tipo_movimiento = 3 AND ctb_doc.id_manu = fv.id_venta)
                LEFT JOIN fac_cartera fc     ON (ctb_doc.tipo_movimiento = 4 AND ctb_doc.id_manu = fc.id_facturac)
                WHERE ctb_doc.fecha BETWEEN :fec_ini AND :fec_fin
                  AND ctb_doc.estado = 2
                  $where_rango_sede
                  $and_where";

        $sqlSaldos = "SELECT
                    COUNT(*) AS filas,
                    ctb_pgcp.cuenta,
                    SUM(IFNULL(ctb_libaux.debito, 0)) AS debito,
                    SUM(IFNULL(ctb_libaux.credito, 0)) AS credito
                FROM ctb_libaux
                INNER JOIN ctb_doc ON (ctb_libaux.id_ctb_doc = ctb_doc.id_ctb_doc)
                INNER JOIN ctb_pgcp ON (ctb_libaux.id_cuenta = ctb_pgcp.id_pgcp)
                WHERE ctb_doc.fecha < :fec_ini
                  AND ctb_doc.estado = 2
                  $where_rango_sede
                GROUP BY ctb_pgcp.cuenta";

        $cmd = \Config\Clases\Conexion::getConexion();
        // Consultar UNA sola vez por sede en lugar de N cuentas × M sedes
        foreach ($sedes as $sede) {
            if ($sede['es_principal'] == 1) {
                $cmd_sede = $cmd;
                if (!empty($bd_principal)) {
                    $cmd_sede->exec("USE `{$bd_principal}`");
                }
            } else {
                if (empty($sede['bd_sede']) || $sede['bd_sede'] == $bd_principal) {
                    continue;
                }
                $cmd_sede = conectarSede($sede['bd_sede']);
                if ($cmd_sede === null) {
                    error_log("No se pudo conectar a la sede {$sede['nom_sede']} para el libro de bancos Excel");
                    continue;
                }
            }

            try {
                $rs = $cmd_sede->prepare($sqlMovimientos);
                $rs->bindValue(':fec_ini', $fec_ini, PDO::PARAM_STR);
                $rs->bindValue(':fec_fin', $fec_fin, PDO::PARAM_STR);
                foreach ($params_rango_sede as $param => $value) {
                    $rs->bindValue($param, $value, PDO::PARAM_STR);
                }
                $rs->execute();
                while ($mov = $rs->fetch(PDO::FETCH_ASSOC)) {
                    $destino = resolverCuentaDestino((string) $mov['cuenta'], $cuentasIdx, $prefijosDesc);
                    if ($destino !== null) {
                        $mov['nom_sede'] = $sede['nom_sede'];
                        $movimientosPorCuenta[$destino][] = $mov;
                    }
                }
                $rs->closeCursor();
                unset($rs);

                $rsSaldos = $cmd_sede->prepare($sqlSaldos);
                $rsSaldos->bindValue(':fec_ini', $fec_ini, PDO::PARAM_STR);
                foreach ($params_rango_sede as $param => $value) {
                    $rsSaldos->bindValue($param, $value, PDO::PARAM_STR);
                }
                $rsSaldos->execute();
                while ($sal = $rsSaldos->fetch(PDO::FETCH_ASSOC)) {
                    $destino = resolverCuentaDestino((string) $sal['cuenta'], $cuentasIdx, $prefijosDesc);
                    if ($destino !== null && $sal['filas'] > 0) {
                        if (!isset($saldosPorCuenta[$destino])) {
                            $saldosPorCuenta[$destino] = [
                                'debito' => 0,
                                'credito' => 0,
                                'filas' => 0,
                                'cuenta' => $sal['cuenta'],
                            ];
                        }
                        $saldosPorCuenta[$destino]['debito'] += $sal['debito'];
                        $saldosPorCuenta[$destino]['credito'] += $sal['credito'];
                        $saldosPorCuenta[$destino]['filas'] += $sal['filas'];
                    }
                }
                $rsSaldos->closeCursor();
                unset($rsSaldos);
            } catch (PDOException $e) {
                error_log("Error consultando movimientos bancarios Excel en sede {$sede['nom_sede']}: " . $e->getMessage());
            }

            if ($sede['es_principal'] != 1) {
                $cmd_sede = null;
            }
        }
        $cmd = null;
    }

    foreach ($obj_cuentas as $obj_c) {
        $cuentaCod = (string) $obj_c['cuenta'];
        $obj_informe = $movimientosPorCuenta[$cuentaCod] ?? [];
        $saldos_iniciales = $saldosPorCuenta[$cuentaCod] ?? ['debito' => 0, 'credito' => 0, 'filas' => 0];
        unset($movimientosPorCuenta[$cuentaCod], $saldosPorCuenta[$cuentaCod]);

        // Ordenar movimientos consolidados por fecha
        usort($obj_informe, function ($a, $b) {
            $comp = strcmp($a['fecha'], $b['fecha']);
            if ($comp === 0) {
                // Si las fechas son iguales, ordenar por débito desc, luego crédito desc
                if ($a['debito'] == $b['debito']) {
                    return $b['credito'] <=> $a['credito'];
                }
                return $b['debito'] <=> $a['debito'];
            }
            return $comp;
        });


        // Si no hay movimientos en esta cuenta Y TAMPOCO tiene saldo inicial, omitimos la cuenta
        if (empty($obj_informe) && $saldos_iniciales['filas'] == 0) {
            continue;
        }

        $primer_caracter_cuenta = '';
        $saldo_inicial = 0;
        $total_deb = 0;
        $total_cre = 0;

        if ($saldos_iniciales['filas'] > 0) {
            $primer_caracter_cuenta = substr($saldos_iniciales['cuenta'], 0, 1);
            $segundo_caracter_cuenta = substr($saldos_iniciales['cuenta'], 0, 2);
            if ($primer_caracter_cuenta == 1 || $primer_caracter_cuenta == 5 || $primer_caracter_cuenta == 6 || $primer_caracter_cuenta == 7 || $segundo_caracter_cuenta == 81 || $segundo_caracter_cuenta == 83 || $segundo_caracter_cuenta == 99) {
                $saldo_inicial = $saldos_iniciales['debito'] - $saldos_iniciales['credito'];
            } else {
                $saldo_inicial = $saldos_iniciales['credito'] - $saldos_iniciales['debito'];
            }
        } else {
            $saldo_inicial = 0;
            $total_deb = 0;
            $total_cre = 0;
        }

        // (Los encabezados generales fueron movidos fuera de este ciclo)
        $reg++;
        escribirFilaCsv($output, ["CUENTA", strval($obj_c['cuenta'] . ' - ' . $obj_c['nombre'])]);
        escribirFilaCsv($output, []);
        $headers = ["Sede", "Fecha", "Tipo Documento", "Documento", "Referencia", "Tercero", "CC/nit", "Detalle", "Debito", "Credito", "Saldo"];
        escribirFilaCsv($output, $headers);

        $saldo_cuenta = $saldo_inicial;
        escribirFilaCsv($output, ["", "", "", "", "", "", "", "Saldo inicial:", "", "", number_format($saldo_cuenta, 2, ".", "")]);

        foreach ($obj_informe as $obj) {
            $primer_caracter = substr($obj['cuenta'], 0, 1);
            $segundo_caracter = substr($obj['cuenta'], 0, 2);
            if ($primer_caracter == 1 || $primer_caracter == 5 || $primer_caracter == 6 || $primer_caracter == 7 || $segundo_caracter == 81 || $segundo_caracter == 83 || $segundo_caracter == 99) {
                $saldo_cuenta = $saldo_cuenta + $obj['debito'] - $obj['credito'];
            } else {
                $saldo_cuenta = $saldo_cuenta + $obj['credito'] - $obj['debito'];
            }

            $row = [
                $obj['nom_sede'] ?? '',
                $obj['fecha'] ?? '',
                $obj['cod_tipo_doc'] ?? '',
                $obj['id_manu'] ?? '',
                mb_strtoupper($obj['forma_pago'] ?? ''),
                mb_strtoupper($obj['nom_tercero'] ?? ''),
                $obj['nit_tercero'] ?? '',
                mb_strtoupper($obj['detalle'] ?? ''),
                number_format($obj['debito'], 2, ".", ""),
                number_format($obj['credito'], 2, ".", ""),
                number_format($saldo_cuenta, 2, ".", "")
            ];
            escribirFilaCsv($output, $row);

            $total_deb += $obj['debito'];
            $total_cre += $obj['credito'];
        }

        escribirFilaCsv($output, [
            "",
            "",
            "",
            "",
            "",
            "",
            "",
            "Totales",
            number_format($total_deb, 2, ".", ""),
            number_format($total_cre, 2, ".", ""),
            number_format($saldo_cuenta, 2, ".", "")
        ]);
        escribirFilaCsv($output, []);
    }

    fclose($output);
    exit;
} catch (PDOException $e) {
    echo $e->getCode() == 2002 ? 'Sin Conexión a Mysql (Error: 2002)' : 'Error: ' . $e->getMessage();
}
