<?php
session_start();
if (!isset($_SESSION['user'])) {
    echo "Acceso denegado";
    exit();
}

// Configurar cabeceras para forzar la descarga del Excel por streaming
$nombre_archivo = "Formato_1012_" . date("Ymd_His") . ".xls";

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
        case 1:
            return ['apellido1' => $partes[0], 'apellido2' => '', 'nombre1' => '', 'nombre2' => ''];

        case 2:
            return ['apellido1' => $partes[1], 'apellido2' => '', 'nombre1' => $partes[0], 'nombre2' => ''];

        case 3:
            return ['apellido1' => $partes[1], 'apellido2' => $partes[2], 'nombre1' => $partes[0], 'nombre2' => ''];

        case 4:
            return ['apellido1' => $partes[2], 'apellido2' => $partes[3], 'nombre1' => $partes[0], 'nombre2' => $partes[1]];

        default:
            return [
                'apellido1' => $partes[$n - 2],
                'apellido2' => $partes[$n - 1],
                'nombre1' => $partes[0],
                'nombre2' => implode(' ', array_slice($partes, 1, $n - 3)),
            ];
    }
}

// Definir los encabezados de las columnas (Formato 1012)
$columnas = [
    'Concepto',
    'Tipo de documento',
    'Número identificación',
    'DV',
    'País de Residencia o domicilio',
    'Primer apellido del informado',
    'Segundo apellido del informado',
    'Primer nombre del informado',
    'Otros nombres del informado',
    'Razón social del informado',
    'Valor al 31 de diciembre',
];

// Las columnas de identificación se fuerzan como texto para conservar ceros a la izquierda.
$columnas_texto = array_flip([1, 2, 3, 4, 5, 6, 7, 8, 9]);

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

imprimirFilaExcel($columnas, [], true);
liberarSalida();

include '../../../../config/autoloader.php';
$conexion = \Config\Clases\Conexion::getConexion();
$vigencia = $_SESSION['vigencia'];
$id_vigencia = $_SESSION['id_vigencia'];

try {
    $sql = "WITH	
                `hom` AS 
                (SELECT
                    `cce`.`id_form`
                    , `cce`.`cod_concepto`
                    , `ch`.`id_cuenta`
                    , SUM(IFNULL(`cl`.`debito`,0)) AS `debito`
                    , SUM(IFNULL(`cl`.`credito`,0)) AS `credito`
                FROM
                    `ctb_libaux` AS `cl`
                    INNER JOIN `ctb_pgcp` AS `cp` 
                    ON (`cl`.`id_cuenta` = `cp`.`id_pgcp`)
                    INNER JOIN `ctb_doc` AS `cd`
                    ON (`cl`.`id_ctb_doc` = `cd`.`id_ctb_doc`)
                    INNER JOIN `ctb_homologacion` AS `ch` 
                    ON (`ch`.`id_cuenta` = `cp`.`id_pgcp`)
                    INNER JOIN `ctb_ctas_exogena` AS `cce`
                    ON (`ch`.`id_cuenta_otros` = `cce`.`id_cuenta`)
                WHERE (`cce`.`id_form` =11
                    AND DATE_FORMAT(`cd`.`fecha`,'%Y') <= '$vigencia'
                    AND `cd`.`estado` = 2
                    AND `ch`.`id_vigencia` = $id_vigencia)
                GROUP BY `cce`.`cod_concepto`, `ch`.`id_cuenta`)
            SELECT 
                `hom`.`cod_concepto` AS `concepto`
                , 31 AS `tipo_documento`
                , SUM(`hom`.`debito`) AS `debito`
                , SUM(`hom`.`credito`) AS `credito`
                , `tb`.`nit_banco` AS `no_documento`
                , `tb`.`dig_ver` AS `dv`
                , `tb`.`nom_banco` AS `nom_tercero`
            FROM `hom`
            INNER JOIN `tes_cuentas` `tc`
                ON (`tc`.`id_cuenta` = `hom`.`id_cuenta`)
            INNER JOIN `tb_bancos` `tb`
                ON (`tb`.`id_banco` = `tc`.`id_banco`)
            GROUP BY `tc`.`id_banco`,`hom`.`cod_concepto`";

    $stmt = $conexion->prepare($sql);
    $stmt->execute();

    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $es_cc = ($row['tipo_documento'] === '13');
        $valor = $row['debito'] - $row['credito'];

        if ($valor <= 0) {
            continue;
        }

        $nombre = $es_cc ? parsearNombreNatural($row['nom_tercero']) : [];

        $linea = [
            $row['concepto'],
            $row['tipo_documento'],
            $row['no_documento'],
            $row['dv'],
            '169',
            $es_cc ? ($nombre['apellido1'] ?? '') : '',
            $es_cc ? ($nombre['apellido2'] ?? '') : '',
            $es_cc ? ($nombre['nombre1'] ?? '') : '',
            $es_cc ? ($nombre['nombre2'] ?? '') : '',
            !$es_cc ? $row['nom_tercero'] : '',
            round($valor, 2),
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
