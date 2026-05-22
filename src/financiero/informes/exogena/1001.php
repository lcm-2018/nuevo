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

/**
 * Divide nom_tercero en sus partes para personas naturales (tipo CC).
 * Orden esperado en la cadena: nombre1 [nombre2] apellido1 [apellido2]
 * Retorna siempre las 4 claves: apellido1, apellido2, nombre1, nombre2.
 */
function parsearNombreNatural(string $cadena): array
{
    $partes = preg_split('/\s+/', trim($cadena), -1, PREG_SPLIT_NO_EMPTY);
    $n = count($partes);

    switch ($n) {
        case 1: // Sólo un token → se trata como apellido
            return ['apellido1' => $partes[0], 'apellido2' => '', 'nombre1' => '', 'nombre2' => ''];

        case 2: // nombre1 apellido1
            return ['apellido1' => $partes[1], 'apellido2' => '', 'nombre1' => $partes[0], 'nombre2' => ''];

        case 3: // nombre1 apellido1 apellido2
            return ['apellido1' => $partes[1], 'apellido2' => $partes[2], 'nombre1' => $partes[0], 'nombre2' => ''];

        case 4: // nombre1 nombre2 apellido1 apellido2 (caso ideal)
            return ['apellido1' => $partes[2], 'apellido2' => $partes[3], 'nombre1' => $partes[0], 'nombre2' => $partes[1]];

        default: // 5+ partes: primer y últimas dos son nombre1 / apellido1 apellido2; el resto va en nombre2
            return [
                'apellido1' => $partes[$n - 2],
                'apellido2' => $partes[$n - 1],
                'nombre1'   => $partes[0],
                'nombre2'   => implode(' ', array_slice($partes, 1, $n - 3)),
            ];
    }
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
$conexion    = \Config\Clases\Conexion::getConexion();
$vigencia    = $_SESSION['vigencia'];
$id_vigencia = $_SESSION['id_vigencia'];

try {
    $sql = "WITH movimientos AS (
                -- Movimientos de la homologación (Formato 1001 principal)
                SELECT
                    `cce`.`cod_concepto`                                        AS `concepto`,
                    `cl`.`id_tercero_api`                                       AS `id_tercero`,
                    SUM(IFNULL(`cl`.`debito`, 0))                               AS `pago_deducible`,
                    SUM(IFNULL(`cl`.`debito`, 0))                               AS `pago_no_deducible`,
                    SUM(CASE
                        WHEN `cp`.`cuenta` LIKE '2436%'
                         AND `cp`.`cuenta` NOT IN ('243625','243627')
                        THEN IFNULL(`cl`.`credito`, 0) ELSE 0
                    END)                                                        AS `retencion_renta`,
                    SUM(CASE
                        WHEN `cp`.`cuenta` = '243625'
                        THEN IFNULL(`cl`.`credito`, 0) ELSE 0
                    END)                                                        AS `retencion_iva_res`
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

    // Iterar línea por línea sin cargar todo en memoria
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {

        $pago_deducible     = 0; // Se mantiene 0 como en la versión original
        $pago_no_deducible  = (float) $row['pago_no_deducible'];
        $iva_deducible      = 0;    // TODO: ajustar cuando se disponga de la cuenta correspondiente
        $iva_no_deducible   = 0;    // TODO: ajustar cuando se disponga de la cuenta correspondiente
        $retencion_renta    = (float) $row['retencion_renta'];    // cuentas 2436 excl. 243625/243627
        $retencion_asumida  = 0;    // TODO: ajustar cuando se disponga de la cuenta correspondiente
        $retencion_iva_res  = (float) $row['retencion_iva_res'];  // cuenta 243625
        $retencion_iva_nres = 0;    // TODO: ajustar cuando se disponga de la cuenta correspondiente

        // NIT → nom_tercero va en Razón social.
        // CC  → nom_tercero se parsea en apellidos/nombres.
        $es_cc  = ($row['tipo_documento'] === '13'); // 13 = Cédula de ciudadanía (persona natural)
        $nombre = $es_cc ? parsearNombreNatural($row['nom_tercero']) : [];

        $linea = [
            $row['concepto'],                                       //  0  Concepto
            $row['tipo_documento'],                                 //  1  Tipo de documento
            $row['no_documento'],                                   //  2  Número identificación
            $es_cc ? ($nombre['apellido1'] ?? '') : '',             //  3  Primer apellido
            $es_cc ? ($nombre['apellido2'] ?? '') : '',             //  4  Segundo apellido
            $es_cc ? ($nombre['nombre1']   ?? '') : '',             //  5  Primer nombre
            $es_cc ? ($nombre['nombre2']   ?? '') : '',             //  6  Otros nombres
            !$es_cc ? $row['nom_tercero']  : '',                    //  7  Razón social
            $row['dir_tercero']             ?? '',                  //  8  Dirección
            $row['codigo_departamento']     ?? '',                  //  9  Código dpto
            $row['codigo_municipio'],                              // 10  Código mcp
            '169',                                                  // 11  País (169 = Colombia)
            round($pago_deducible,     2),                          // 12  Pago o abono deducible
            round($pago_no_deducible,  2),                          // 13  Pago o abono NO deducible
            round($iva_deducible,      2),                          // 14  IVA deducible
            round($iva_no_deducible,   2),                          // 15  IVA no deducible
            round($retencion_renta,    2),                          // 16  Ret. fuente practicada Renta (2436 excl. 243625/243627)
            round($retencion_asumida,  2),                          // 17  Ret. fuente asumida Renta
            round($retencion_iva_res,  2),                          // 18  Ret. fuente IVA responsables (243625)
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
