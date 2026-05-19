<?php
session_start();
if (!isset($_SESSION['user'])) {
    echo "Acceso denegado";
    exit();
}

// Configurar cabeceras para forzar la descarga del Excel por streaming
$nombre_archivo = "Formato_1008_" . date("Ymd_His") . ".xls";

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

// Definir los encabezados de las columnas (Formato 1008)
$columnas = [
    'Concepto',
    'Tipo de documento',
    'Número identificación deudor',
    'DV',
    'Primer apellido deudor',
    'Segundo apellido deudor',
    'Primer nombre deudor',
    'Otros nombres deudor',
    'Razón social deudor',
    'Dirección',
    'Código dpto',
    'Código mcp',
    'País de Residencia o domicilio',
    'Saldo cuentas por cobrar al 31-12',
];

// Las columnas de identificación se fuerzan como texto para conservar ceros a la izquierda.
// Índices: Tipo doc(1), Num id(2), DV(3), Apellido1(4), Apellido2(5), Nombre1(6), OtrosNombres(7),
//          Razón social(8), Dirección(9), CodDpto(10), CodMcp(11), País(12)
$columnas_texto = array_flip([1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12]);

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
// ESPACIO PARA TU CONSULTA SQL (Lógica de Streaming)
// -------------------------------------------------------------------------
include '../../../../config/autoloader.php';
$conexion = \Config\Clases\Conexion::getConexion();
$vigencia = $_SESSION['vigencia'];
$id_vigencia = $_SESSION['id_vigencia'];

try {
    // 1. Consulta SQL Formato 1008 — Cuentas por Cobrar
    $sql = "SELECT
                `cce`.`cod_concepto`                                          AS `concepto`,
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
                `t`.`nit_tercero`                                             AS `no_documento`,
                calcularDV(`t`.`nit_tercero`)                                 AS `dv`,
                `t`.`nom_tercero`                                             AS `nom_tercero`,
                `t`.`dir_tercero`                                             AS `direccion`,
                `d`.`codigo_departamento`                                     AS `codigo_dpto`,
                CONCAT(`d`.`codigo_departamento`, `m`.`codigo_municipio`)         AS `codigo_mcp`,
                SUM(IFNULL(`cl`.`debito`,  0))                               AS `debito`,
                SUM(IFNULL(`cl`.`credito`, 0))                               AS `credito`
            FROM `ctb_homologacion`   AS `ch`
            INNER JOIN `ctb_ctas_exogena` AS `cce`
                ON (`ch`.`id_cuenta_otros` = `cce`.`id_cuenta` AND `cce`.`id_form` = 7)
            INNER JOIN `ctb_pgcp`     AS `cp`
                ON (`ch`.`id_cuenta` = `cp`.`id_pgcp`)
            INNER JOIN `ctb_libaux`   AS `cl`
                ON (`cl`.`id_cuenta` = `cp`.`id_pgcp`)
            INNER JOIN `ctb_doc`      AS `cd`
                ON (`cl`.`id_ctb_doc` = `cd`.`id_ctb_doc`)
            INNER JOIN `tb_terceros`  AS `t`
                ON (`cl`.`id_tercero_api` = `t`.`id_tercero_api`)
            LEFT JOIN `tb_tipos_documento` AS `ttd`
                ON (`t`.`tipo_doc` = `ttd`.`id_tipodoc`)
            LEFT JOIN `tb_municipios`  AS `m`
                ON (`t`.`id_municipio` = `m`.`id_municipio`)
            LEFT JOIN `tb_departamentos` AS `d`
                ON (`m`.`id_departamento` = `d`.`id_departamento`)
            WHERE `ch`.`id_vigencia` = $id_vigencia
              AND `cd`.`estado`  = 2
              AND `cl`.`id_tercero_api` > 0
              AND DATE_FORMAT(`cd`.`fecha`,'%Y') <= '$vigencia'
            GROUP BY `cl`.`id_tercero_api`, `cce`.`cod_concepto`, `ch`.`id_cuenta_otros`";

    $stmt = $conexion->prepare($sql);
    $stmt->execute();

    // 2. Iterar línea por línea sin cargar todo en memoria
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {

        // 3. Mapear los datos de tu fila a un arreglo (debe coincidir con el orden de $columnas)
        // NIT → persona jurídica: nom_tercero va en Razón social.
        // CC  → persona natural:  nom_tercero se parsea en apellidos/nombres.
        // DV se escribe siempre (calcularDV lo resuelve en el SQL para todos).
        $es_cc  = ($row['tipo_documento'] === '13'); // 13 = Cédula de ciudadanía (persona natural)
        $saldo  = $row['debito'] - $row['credito'];

        // Omitir registros con saldo cero
        if ($saldo == 0) continue;

        $nombre = $es_cc ? parsearNombreNatural($row['nom_tercero']) : [];

        $linea = [
            $row['concepto'],                                   // Concepto
            $row['tipo_documento'],                             // Tipo de documento
            $row['no_documento'],                               // Número identificación deudor
            $row['dv'],                                         // DV (para todos)
            $es_cc ? ($nombre['apellido1'] ?? '') : '',         // Primer apellido deudor
            $es_cc ? ($nombre['apellido2'] ?? '') : '',         // Segundo apellido deudor
            $es_cc ? ($nombre['nombre1']   ?? '') : '',         // Primer nombre deudor
            $es_cc ? ($nombre['nombre2']   ?? '') : '',         // Otros nombres deudor
            !$es_cc ? $row['nom_tercero']  : '',                // Razón social deudor
            $row['direccion'],                                  // Dirección
            $row['codigo_dpto'] ?? '',                          // Código dpto
            $row['codigo_mcp']  ?? '',                          // Código mcp
            '169',                                              // País (169 = Colombia)
            round($saldo, 2),                                   // Saldo cuentas por cobrar al 31-12
        ];

        // 4. Escribir la línea en el Excel
        imprimirFilaExcel($linea, $columnas_texto);

        // 5. MÁGIA DEL STREAMING: Vaciar los buffers
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
