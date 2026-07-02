<?php
session_start();
if (!isset($_SESSION['user'])) {
    echo "Acceso denegado";
    exit();
}

// Configurar cabeceras para forzar la descarga del Excel por streaming
$nombre_archivo = "Formato_1001_" . date("Ymd_His") . ".xls";

header('Content-Type: application/vnd.ms-excel; charset=UTF-8');
header('Content-Disposition: attachment; filename="' . $nombre_archivo . '"');
header('Pragma: no-cache');
header('Expires: 0');

// Escribir el BOM de UTF-8 para que Excel reconozca las tildes y ñ correctamente al abrirlo
echo "\xEF\xBB\xBF";

function imprimirFilaExcel($valores, $columnas_texto = [], $es_encabezado = false)
{
    $etiqueta = $es_encabezado ? 'th' : 'td';
    $estilo_base = $es_encabezado
        ? 'background-color:#d9e2f3;font-weight:bold;text-align:center;'
        : '';

    echo '<tr>';
    foreach ($valores as $indice => $valor) {
        $es_texto = isset($columnas_texto[$indice]);
        $estilo = $estilo_base . ($es_texto ? 'mso-number-format:"\@";' : '');
        echo '<' . $etiqueta . ' style=\'' . $estilo . '\'>';
        echo htmlspecialchars((string) ($valor ?? ''), ENT_QUOTES, 'UTF-8');
        echo '</' . $etiqueta . '>';
    }
    echo '</tr>';
}

function liberarSalida()
{
    if (ob_get_level() > 0) {
        ob_flush();
    }
    flush();
}



// Definir los encabezados de las columnas (Formato 1001)
$columnas = [
    'Concepto',                                                                         //  0
    'Tipo de documento',                                                                //  1
    'Número identificación',                                                            //  2
    'Primer apellido del informado',                                                    //  3
    'Segundo apellido del informado',                                                   //  4
    'Primer nombre del informado',                                                      //  5
    'Otros nombres del informado',                                                      //  6
    'Razón social informado',                                                           //  7
    'Dirección',                                                                        //  8
    'Código dpto',                                                                      //  9
    'Código mcp',                                                                       // 10
    'País de Residencia o domicilio',                                                   // 11
    'Pago o abono en cuenta deducible',                                                 // 12
    'Pago o abono en cuenta NO deducible',                                              // 13
    'IVA mayor valor del costo o gasto, deducible',                                     // 14
    'IVA mayor valor del costo o gasto no deducible',                                   // 15
    'Retención en la fuente practicada Renta',                                          // 16
    'Retención en la fuente asumida Renta',                                             // 17
    'Retención en la fuente practicada IVA a responsables del IVA',                     // 18
    'Retención en la fuente practicada IVA a no residentes o no domiciliados',          // 19
];

// Columnas forzadas como texto para conservar ceros a la izquierda / formato alfanumérico
// Índices: Tipo doc(1), Num id(2), Apellido1(3), Apellido2(4), Nombre1(5), OtrosNombres(6),
//          Razón social(7), Dirección(8), Cod dpto(9), Cod mcp(10), País(11)
$columnas_texto = array_flip([1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11]);

echo '<html xmlns:x="urn:schemas-microsoft-com:office:excel">';
echo '<head>';
echo '<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">';
echo '<style>
        table { border-collapse: collapse; }
        td, th { border: 1px solid #999; font-family: Arial, sans-serif; font-size: 11px; padding: 3px; }
      </style>';
echo '</head>';
echo '<body>';
echo '<table>';

// Imprimir los encabezados en el Excel
imprimirFilaExcel($columnas, [], true);

// Liberar buffer para que empiece a descargar de inmediato los encabezados
liberarSalida();

// -------------------------------------------------------------------------
// CONSULTA SQL — Formato 1001 (Pagos o abonos en cuenta y retenciones)
// -------------------------------------------------------------------------
include '../../../../config/autoloader.php';
$conexion = \Config\Clases\Conexion::getConexion();
$vigencia = $_SESSION['vigencia'];
$id_vigencia = $_SESSION['id_vigencia'];

try {
    $sql = "WITH movimientos AS (
                -- Movimientos de la homologación (Formato 1001 principal)
                -- Las cuentas 2436x aparecen como líneas de retención en los mismos documentos,
                -- no como cuentas homologadas, por eso se unen por id_ctb_doc (LEFT JOIN adicional).
                -- 1. Movimientos principales (Pagos)
                SELECT
                    `cce`.`cod_concepto`                                        AS `concepto`,
                    `cl`.`id_tercero_api`                                       AS `id_tercero`,
                    SUM(IFNULL(`cl`.`debito`, 0))                               AS `pago_deducible`,
                    SUM(IFNULL(`cl`.`debito`, 0))                               AS `pago_no_deducible`,
                    0                                                           AS `retencion_renta`,
                    0                                                           AS `retencion_iva_res`
                FROM `ctb_homologacion`   AS `ch`
                INNER JOIN `ctb_ctas_exogena` AS `cce`
                    ON (`ch`.`id_cuenta_otros` = `cce`.`id_cuenta` AND `cce`.`id_form` = 1)
                INNER JOIN `ctb_pgcp`     AS `cp`
                    ON (`ch`.`id_cuenta` = `cp`.`id_pgcp`)
                INNER JOIN `ctb_libaux`   AS `cl`
                    ON (`cl`.`id_cuenta` = `cp`.`id_pgcp`)
                INNER JOIN `ctb_doc`      AS `cd`
                    ON (`cl`.`id_ctb_doc` = `cd`.`id_ctb_doc`)
                WHERE `ch`.`id_vigencia` = $id_vigencia
                  AND `cd`.`estado`     = 2
                  AND `cd`.`id_tipo_doc` = 3
                  AND `cl`.`id_tercero_api` > 0
                  AND DATE_FORMAT(`cd`.`fecha`,'%Y') = '$vigencia'
                GROUP BY `cl`.`id_tercero_api`, `cce`.`cod_concepto`

                UNION ALL

                -- 1.B Retenciones de los documentos tipo 3, cruzadas con un concepto base
                SELECT 
                    `b`.`concepto`,
                    `r`.`id_tercero_api` AS `id_tercero`,
                    0 AS `pago_deducible`,
                    0 AS `pago_no_deducible`,
                    SUM(`r`.`ret_renta`) AS `retencion_renta`,
                    SUM(`r`.`ret_iva`) AS `retencion_iva_res`
                FROM (
                    SELECT 
                        `cl_ret`.`id_ctb_doc`,
                        `cl_ret`.`id_tercero_api`,
                        SUM(CASE WHEN `cp_ret`.`cuenta` LIKE '2436%' AND `cp_ret`.`cuenta` NOT IN ('243625','243627') THEN IFNULL(`cl_ret`.`credito`, 0) ELSE 0 END) AS `ret_renta`,
                        SUM(CASE WHEN `cp_ret`.`cuenta` = '243625' THEN IFNULL(`cl_ret`.`credito`, 0) ELSE 0 END) AS `ret_iva`
                    FROM `ctb_libaux` AS `cl_ret`
                    INNER JOIN `ctb_pgcp` AS `cp_ret` ON `cl_ret`.`id_cuenta` = `cp_ret`.`id_pgcp`
                    INNER JOIN `ctb_doc` AS `cd` ON `cl_ret`.`id_ctb_doc` = `cd`.`id_ctb_doc`
                    WHERE `cp_ret`.`cuenta` LIKE '2436%' AND `cd`.`estado` = 2 AND `cd`.`id_tipo_doc` = 3 AND DATE_FORMAT(`cd`.`fecha`,'%Y') = '$vigencia'
                    GROUP BY `cl_ret`.`id_ctb_doc`, `cl_ret`.`id_tercero_api`
                ) AS `r`
                INNER JOIN (
                    -- Obtenemos 1 solo concepto de gasto por documento/tercero para anclar la retención
                    SELECT `cl`.`id_ctb_doc`, `cl`.`id_tercero_api`, MAX(`cce`.`cod_concepto`) AS `concepto`
                    FROM `ctb_homologacion` `ch`
                    INNER JOIN `ctb_ctas_exogena` `cce` ON `ch`.`id_cuenta_otros` = `cce`.`id_cuenta` AND `cce`.`id_form` = 1
                    INNER JOIN `ctb_pgcp` `cp` ON `ch`.`id_cuenta` = `cp`.`id_pgcp`
                    INNER JOIN `ctb_libaux` `cl` ON `cl`.`id_cuenta` = `cp`.`id_pgcp`
                    WHERE `ch`.`id_vigencia` = $id_vigencia
                    GROUP BY `cl`.`id_ctb_doc`, `cl`.`id_tercero_api`
                ) AS `b` ON `r`.`id_ctb_doc` = `b`.`id_ctb_doc` AND `r`.`id_tercero_api` = `b`.`id_tercero_api`
                GROUP BY `r`.`id_tercero_api`, `b`.`concepto`
            
                UNION ALL
            
                -- Movimientos extra (Concepto 5012)
                SELECT
                    '5012'                                                      AS `concepto`,
                    `ctb_libaux`.`id_tercero_api`                               AS `id_tercero`,
                    0                                                           AS `pago_deducible`,
                    SUM(`ctb_libaux`.`debito`)                                  AS `pago_no_deducible`,
                    0                                                           AS `retencion_renta`,
                    0                                                           AS `retencion_iva_res`
                FROM `ctb_libaux`
                INNER JOIN `ctb_doc` 
                    ON (`ctb_libaux`.`id_ctb_doc` = `ctb_doc`.`id_ctb_doc`)
                INNER JOIN `ctb_pgcp` 
                    ON (`ctb_libaux`.`id_cuenta` = `ctb_pgcp`.`id_pgcp`)
                WHERE `ctb_doc`.`id_tipo_doc` = 4
                  AND `ctb_doc`.`estado` = 2
                  AND DATE_FORMAT(`ctb_doc`.`fecha`, '%Y') ='$vigencia'
                  AND `ctb_pgcp`.`cuenta` LIKE '242401%'
                  AND `ctb_libaux`.`id_tercero_api` > 0
                GROUP BY `ctb_libaux`.`id_tercero_api`
            
                UNION ALL
            
                -- Movimientos extra (Concepto 5011)
                SELECT
                    '5011'                                                      AS `concepto`,
                    `ctb_libaux`.`id_tercero_api`                               AS `id_tercero`,
                    0                                                           AS `pago_deducible`,
                    SUM(`ctb_libaux`.`debito`)                                  AS `pago_no_deducible`,
                    0                                                           AS `retencion_renta`,
                    0                                                           AS `retencion_iva_res`
                FROM `ctb_libaux`
                INNER JOIN `ctb_doc` 
                    ON (`ctb_libaux`.`id_ctb_doc` = `ctb_doc`.`id_ctb_doc`)
                INNER JOIN `ctb_pgcp` 
                    ON (`ctb_libaux`.`id_cuenta` = `ctb_pgcp`.`id_pgcp`)
                WHERE `ctb_doc`.`id_tipo_doc` = 4
                  AND `ctb_doc`.`estado` = 2
                  AND DATE_FORMAT(`ctb_doc`.`fecha`, '%Y') ='$vigencia'
                  AND `ctb_pgcp`.`cuenta` LIKE '242402%'
                  AND `ctb_libaux`.`id_tercero_api` > 0
                GROUP BY `ctb_libaux`.`id_tercero_api`
            
                UNION ALL
            
                -- Conceptos 5 y 6 (honorarios y servicios) adaptados de 2276.php con id_ps = 0
                SELECT
                    IFNULL(`cce1`.`cod_concepto`, `cce15`.`cod_concepto`)       AS `concepto`,
                    `cl`.`id_tercero_api`                                       AS `id_tercero`,
                    0                                                           AS `pago_deducible`,
                    SUM(IFNULL(`cl`.`debito`, 0))                               AS `pago_no_deducible`,
                    0                                                           AS `retencion_renta`,
                    0                                                           AS `retencion_iva_res`
                FROM `ctb_homologacion` AS `ch`
                INNER JOIN `ctb_ctas_exogena` AS `cce15` 
                    ON `cce15`.`id_cuenta` = `ch`.`id_cuenta_otros` AND `cce15`.`id_form` = 15
                LEFT JOIN `ctb_ctas_exogena` AS `cce1` 
                    ON `cce1`.`id_cuenta` = `ch`.`id_cuenta_otros` AND `cce1`.`id_form` = 1
                INNER JOIN `ctb_libaux` AS `cl` 
                    ON `cl`.`id_cuenta` = `ch`.`id_cuenta`
                INNER JOIN `ctb_doc` AS `cd` 
                    ON `cl`.`id_ctb_doc` = `cd`.`id_ctb_doc`
                WHERE `ch`.`id_vigencia` = $id_vigencia
                  AND `cl`.`debito` > 0 AND `cd`.`estado` = 2 AND `cl`.`id_tercero_api` > 0
                  AND `cce15`.`cod_concepto` IN ('5','6')
                  AND DATE_FORMAT(`cd`.`fecha`,'%Y') = '$vigencia'
                  AND EXISTS (
                      SELECT 1
                      FROM `pto_cop_detalle` AS `pcopc`
                      INNER JOIN `pto_crp_detalle` AS `pcrpc`
                          ON `pcrpc`.`id_pto_crp_det` = `pcopc`.`id_pto_crp_det`
                      INNER JOIN `pto_cdp_detalle` AS `pcdpc`
                          ON `pcdpc`.`id_pto_cdp_det` = `pcrpc`.`id_pto_cdp_det`
                      INNER JOIN `pto_cargue` AS `pcarg`
                          ON `pcarg`.`id_cargue` = `pcdpc`.`id_rubro`
                      INNER JOIN `pto_homologa_gastos` AS `phg`
                          ON `phg`.`id_cargue` = `pcarg`.`id_cargue` AND `phg`.`id_ps` = 0
                      WHERE `pcopc`.`id_ctb_doc` = `cd`.`id_ctb_doc` AND `pcopc`.`valor` > 0
                  )
                GROUP BY `cl`.`id_tercero_api`, IFNULL(`cce1`.`cod_concepto`, `cce15`.`cod_concepto`)
            )
            SELECT
                `m`.`concepto`,
                `m`.`id_tercero`,
                CASE `ttd`.`codigo_ne`
                    WHEN 'CC'  THEN '13'
                    WHEN 'TI'  THEN '12'
                    WHEN 'CE'  THEN '22'
                    WHEN 'NIT' THEN '31'
                    WHEN 'PAS' THEN '41'
                    WHEN 'FI'  THEN '43'
                    WHEN 'PEP' THEN '47'
                    WHEN 'VIS' THEN '48'
                    ELSE `ttd`.`codigo_ne`
                END                                                             AS `tipo_documento`,
                `t`.`nit_tercero`                                           AS `no_documento`,
                `t`.`nom_tercero`                                           AS `nom_tercero`,
                `t`.`dir_tercero`                                           AS `dir_tercero`,
                `d`.`codigo_departamento`                                   AS `codigo_departamento`,
                `mun`.`codigo_municipio`                                    AS `codigo_municipio`,
                SUM(`m`.`pago_deducible`)                                   AS `pago_deducible`,
                SUM(`m`.`pago_no_deducible`)                                AS `pago_no_deducible`,
                SUM(`m`.`retencion_renta`)                                  AS `retencion_renta`,
                SUM(`m`.`retencion_iva_res`)                                AS `retencion_iva_res`
            FROM `movimientos` AS `m`
            INNER JOIN `tb_terceros`  AS `t`
                ON (`m`.`id_tercero` = `t`.`id_tercero_api`)
            LEFT JOIN `tb_tipos_documento` AS `ttd`
                ON (`t`.`tipo_doc` = `ttd`.`id_tipodoc`)
            LEFT JOIN `tb_municipios` AS `mun`
                ON (`t`.`id_municipio` = `mun`.`id_municipio`)
            LEFT JOIN `tb_departamentos` AS `d`
                ON (`mun`.`id_departamento` = `d`.`id_departamento`)
            GROUP BY `m`.`id_tercero`, `m`.`concepto`
            HAVING `pago_deducible` != 0
                OR `pago_no_deducible` != 0
                OR `retencion_renta` != 0
                OR `retencion_iva_res` != 0";

    $stmt = $conexion->prepare($sql);
    $stmt->execute();

    $id_terceros = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $id_terceros[] = $row['id_tercero'];
    }
    $id_terceros = array_unique($id_terceros);

    $payload = json_encode(array_values($id_terceros));
    $api = \Config\Clases\Conexion::Api();
    $url = $api . 'terceros/datos/res/lista/terceros';
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
    curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type:application/json'));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    $result = curl_exec($ch);
    curl_close($ch);

    $terceros = json_decode($result, true);
    $terceros = is_array($terceros) ? $terceros : [];

    $terceros_api_idx = [];
    foreach ($terceros as $t) {
        $terceros_api_idx[$t['id_tercero']] = $t;
    }

    $stmt->execute();

    // Iterar línea por línea sin cargar todo en memoria
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {

        $pago_deducible = 0; // Se mantiene 0 como en la versión original
        $pago_no_deducible = (float) $row['pago_no_deducible'];
        $iva_deducible = 0;    // TODO: ajustar cuando se disponga de la cuenta correspondiente
        $iva_no_deducible = 0;    // TODO: ajustar cuando se disponga de la cuenta correspondiente
        $retencion_renta = (float) $row['retencion_renta'];    // cuentas 2436 excl. 243625/243627
        $retencion_asumida = 0;    // TODO: ajustar cuando se disponga de la cuenta correspondiente
        $retencion_iva_res = (float) $row['retencion_iva_res'];  // cuenta 243625
        $retencion_iva_nres = 0;    // TODO: ajustar cuando se disponga de la cuenta correspondiente

        $datos_api = $terceros_api_idx[$row['id_tercero']] ?? null;

        $nombre = [
            'apellido1' => '',
            'apellido2' => '',
            'nombre1' => '',
            'nombre2' => ''
        ];
        $razon_social = '';

        if ($datos_api) {
            if ($datos_api['tipo_doc'] == 5) {
                $razon_social = $datos_api['razon_social'] ?? '';
            } else {
                $nombre['apellido1'] = $datos_api['apellido1'] ?? '';
                $nombre['apellido2'] = $datos_api['apellido2'] ?? '';
                $nombre['nombre1'] = $datos_api['nombre1'] ?? '';
                $nombre['nombre2'] = $datos_api['nombre2'] ?? '';
            }
        }

        $linea = [
            $row['concepto'],                                       //  0  Concepto
            $row['tipo_documento'],                                 //  1  Tipo de documento
            $row['no_documento'],                                   //  2  Número identificación
            $nombre['apellido1'],                                   //  3  Primer apellido
            $nombre['apellido2'],                                   //  4  Segundo apellido
            $nombre['nombre1'],                                     //  5  Primer nombre
            $nombre['nombre2'],                                     //  6  Otros nombres
            $razon_social,                                          //  7  Razón social
            $row['dir_tercero'] ?? '',                  //  8  Dirección
            $row['codigo_departamento'] ?? '',                  //  9  Código dpto
            $row['codigo_municipio'],                              // 10  Código mcp
            '169',                                                  // 11  País (169 = Colombia)
            round($pago_deducible, 2),                          // 12  Pago o abono deducible
            round($pago_no_deducible, 2),                          // 13  Pago o abono NO deducible
            round($iva_deducible, 2),                          // 14  IVA deducible
            round($iva_no_deducible, 2),                          // 15  IVA no deducible
            round($retencion_renta, 2),                          // 16  Ret. fuente practicada Renta (2436 excl. 243625/243627)
            round($retencion_asumida, 2),                          // 17  Ret. fuente asumida Renta
            round($retencion_iva_res, 2),                          // 18  Ret. fuente IVA responsables (243625)
            round($retencion_iva_nres, 2),                          // 19  Ret. fuente IVA no residentes
        ];

        imprimirFilaExcel($linea, $columnas_texto);
        liberarSalida();
    }
} catch (PDOException $e) {
    echo '<tr><td colspan="' . count($columnas) . '">';
    echo htmlspecialchars('Error en la consulta: ' . $e->getMessage(), ENT_QUOTES, 'UTF-8');
    echo '</td></tr>';
}

echo '</table>';
echo '</body></html>';
exit();
