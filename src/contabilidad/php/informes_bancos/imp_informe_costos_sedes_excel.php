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

$fec_ini = isset($_POST['fec_ini']) && strlen($_POST['fec_ini']) > 0 ? $_POST['fec_ini'] : '2020-01-01';
$fec_fin = isset($_POST['fec_fin']) && strlen($_POST['fec_fin']) > 0 ? $_POST['fec_fin'] : '2050-12-31';
$id_tipo_doc = isset($_POST['id_tipo_doc']) ? $_POST['id_tipo_doc'] : 0;
$id_tercero = isset($_POST['id_tercero']) ? $_POST['id_tercero'] : 0;

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

try {
    $cmd = \Config\Clases\Conexion::getConexion();

    // Solo consultar cuentas que inicien por 43
    $sql = "SELECT 
                 ctb_pgcp.id_pgcp
                ,ctb_pgcp.cuenta
                ,ctb_pgcp.nombre
            FROM ctb_pgcp 
            WHERE ctb_pgcp.estado = 1 AND ctb_pgcp.tipo_dato = 'D'
            AND ctb_pgcp.cuenta LIKE '43%'
            ORDER BY ctb_pgcp.cuenta ASC";
    $rs = $cmd->query($sql);
    $obj_cuentas = $rs->fetchAll(PDO::FETCH_ASSOC);
    $rs->closeCursor();

    $sql = 'SELECT razon_social_ips,nit_ips FROM tb_datos_ips LIMIT 1';
    $rs = $cmd->query($sql);
    $obj_ent = $rs->fetch();
    $razhd = $obj_ent['razon_social_ips'];
    $nithd = $obj_ent['nit_ips'];

    // Obtener nombres de municipios de las sedes
    $sql_muni = "SELECT tb_sedes.id_sede, tb_municipios.nom_municipio 
                 FROM tb_sedes 
                 LEFT JOIN tb_municipios ON (tb_sedes.id_municipio = tb_municipios.id_municipio)";
    $rs_muni = $cmd->query($sql_muni);
    $municipios_sedes = $rs_muni->fetchAll(PDO::FETCH_KEY_PAIR);
    $rs_muni->closeCursor();

    // Filtrar sedes que realmente se van a procesar (que tengan bd_sede propia o sean la principal)
    $sedes_procesadas = [];
    foreach ($sedes as $sede) {
        if ($sede['es_principal'] == 1 || (!empty($sede['bd_sede']) && $sede['bd_sede'] != $bd_principal)) {
            $sedes_procesadas[] = $sede;
        }
    }

    // Inicializar estructura de datos
    $data = [];
    foreach ($obj_cuentas as $c) {
        $data[$c['cuenta']] = [
            'nombre' => $c['nombre'],
            'sedes' => [],
            'total' => 0
        ];
        foreach ($sedes_procesadas as $sede) {
            $data[$c['cuenta']]['sedes'][$sede['id_sede']] = 0;
        }
    }

    foreach ($sedes_procesadas as $sede) {
        if ($sede['es_principal'] == 1) {
            $cmd_sede = $cmd;
            if (!empty($bd_principal)) {
                $cmd_sede->exec("USE `{$bd_principal}`");
            }
        } else {
            $cmd_sede = conectarSede($sede['bd_sede']);
            if ($cmd_sede === null) {
                continue;
            }
        }

        try {
            // Consulta para obtener los movimientos del periodo
            // Cuentas de ingresos (4) son de naturaleza crédito, el valor se calcula Crédito - Débito
            $sql_mov = "SELECT
                        ctb_pgcp.cuenta,
                        SUM(IFNULL(ctb_libaux.credito,0)) - SUM(IFNULL(ctb_libaux.debito,0)) AS valor_periodo
                    FROM
                        ctb_libaux
                        INNER JOIN ctb_doc ON (ctb_libaux.id_ctb_doc = ctb_doc.id_ctb_doc)
                        INNER JOIN ctb_pgcp ON (ctb_libaux.id_cuenta = ctb_pgcp.id_pgcp)
                    WHERE ctb_doc.fecha BETWEEN :fec_ini AND :fec_fin
                    AND ctb_pgcp.cuenta LIKE '43%'
                    AND ctb_doc.estado = 2
                    $and_where
                    GROUP BY ctb_pgcp.cuenta";

            $rs = $cmd_sede->prepare($sql_mov);
            $rs->bindValue(':fec_ini', $fec_ini, PDO::PARAM_STR);
            $rs->bindValue(':fec_fin', $fec_fin, PDO::PARAM_STR);
            $rs->execute();
            $movimientos = $rs->fetchAll(PDO::FETCH_ASSOC);
            $rs->closeCursor();

            // Consulta para saldos iniciales
            $sql_ini = "SELECT
                        ctb_pgcp.cuenta,
                        SUM(IFNULL(ctb_libaux.credito,0)) - SUM(IFNULL(ctb_libaux.debito,0)) AS valor_inicial
                    FROM
                        ctb_libaux
                        INNER JOIN ctb_doc ON (ctb_libaux.id_ctb_doc = ctb_doc.id_ctb_doc)
                        INNER JOIN ctb_pgcp ON (ctb_libaux.id_cuenta = ctb_pgcp.id_pgcp)
                    WHERE ctb_doc.fecha < :fec_ini
                    AND ctb_pgcp.cuenta LIKE '43%'
                    AND ctb_doc.estado = 2
                    $and_where
                    GROUP BY ctb_pgcp.cuenta";

            $rs_ini = $cmd_sede->prepare($sql_ini);
            $rs_ini->bindValue(':fec_ini', $fec_ini, PDO::PARAM_STR);
            $rs_ini->execute();
            $iniciales = $rs_ini->fetchAll(PDO::FETCH_ASSOC);
            $rs_ini->closeCursor();

            // Combinar saldos
            $saldos_sede = [];
            foreach ($iniciales as $ini) {
                $saldos_sede[$ini['cuenta']] = (float) $ini['valor_inicial'];
            }
            foreach ($movimientos as $mov) {
                if (!isset($saldos_sede[$mov['cuenta']])) {
                    $saldos_sede[$mov['cuenta']] = 0;
                }
                $saldos_sede[$mov['cuenta']] += (float) $mov['valor_periodo'];
            }

            foreach ($saldos_sede as $cuenta => $valor) {
                if (isset($data[$cuenta])) {
                    $data[$cuenta]['sedes'][$sede['id_sede']] += $valor;
                    $data[$cuenta]['total'] += $valor;
                }
            }

        } catch (PDOException $e) {
            error_log("Error consultando en sede {$sede['nom_sede']}: " . $e->getMessage());
        }

        // Si no es la principal, anulamos la conexión temporal
        if ($sede['es_principal'] != 1) {
            $cmd_sede = null;
        }
    }

    $cmd = null;

    $filename = "reporte_cuentas_43_" . date("Y-m-d_H-i-s") . ".csv";

    header("Content-Type: text/csv; charset=UTF-8");
    header("Content-Disposition: attachment; filename=\"$filename\"");
    header("Pragma: no-cache");
    header("Expires: 0");

    $output = fopen("php://output", "w");

    fputs($output, $bom = (chr(0xEF) . chr(0xBB) . chr(0xBF)));

    fputcsv($output, ["ENTIDAD", $razhd]);
    fputcsv($output, ["NIT", $nithd]);
    fputcsv($output, ["REPORTE", "CONSOLIDADO CUENTAS 43 POR MUNICIPIO (SEDE)"]);
    fputcsv($output, ["FECHA INICIAL", $fec_ini]);
    fputcsv($output, ["FECHA FINAL", $fec_fin]);
    fputcsv($output, []);

    // Construir encabezados
    $headers = ["CUENTA", "NOMBRE CUENTA"];
    foreach ($sedes_procesadas as $sede) {
        $nom_muni = isset($municipios_sedes[$sede['id_sede']]) && !empty($municipios_sedes[$sede['id_sede']]) ? $municipios_sedes[$sede['id_sede']] : $sede['nom_sede'];
        $headers[] = mb_strtoupper($nom_muni);
    }
    $headers[] = "TOTAL";
    fputcsv($output, $headers);

    // Fila adicional de ingresos por unidad funcional
    fputcsv($output, ["INGRESOS POR UNIDAD FUNCIONAL"]);

    // Variables para el total general
    $totales_sedes = [];
    foreach ($sedes_procesadas as $sede) {
        $totales_sedes[$sede['id_sede']] = 0;
    }
    $gran_total = 0;

    // Escribir datos
    foreach ($data as $cuenta => $info) {
        $tiene_valores = false;
        foreach ($info['sedes'] as $val) {
            if (abs($val) > 0.001) {
                $tiene_valores = true;
                break;
            }
        }
        // Saltar cuenta si está en 0 para todas las sedes
        if (!$tiene_valores)
            continue;

        $row = [
            $cuenta,
            mb_strtoupper($info['nombre'])
        ];

        foreach ($sedes_procesadas as $sede) {
            $val = $info['sedes'][$sede['id_sede']];
            $row[] = number_format($val, 2, ".", ",");
            $totales_sedes[$sede['id_sede']] += $val;
        }
        $row[] = number_format($info['total'], 2, ".", ",");
        $gran_total += $info['total'];

        fputcsv($output, $row);
    }

    // Fila adicional de INGRESOS FACTURADOS
    $row_totales = ["", "INGRESOS FACTURADOS"];
    foreach ($sedes_procesadas as $sede) {
        $row_totales[] = number_format($totales_sedes[$sede['id_sede']], 2, ".", ",");
    }
    $row_totales[] = number_format($gran_total, 2, ".", ",");
    fputcsv($output, $row_totales);

    fclose($output);
    exit;

} catch (PDOException $e) {
    echo $e->getCode() == 2002 ? 'Sin Conexión a Mysql (Error: 2002)' : 'Error: ' . $e->getMessage();
}
