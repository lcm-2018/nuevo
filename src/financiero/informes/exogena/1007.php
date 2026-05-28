<?php
session_start();
if (!isset($_SESSION['user'])) {
    echo "Acceso denegado";
    exit();
}

// Configurar cabeceras para forzar la descarga del Excel por streaming
$nombre_archivo = "Formato_1007_" . date("Ymd_His") . ".xls";

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



// Definir los encabezados de las columnas (Formato 1007)
$columnas = [
    'Concepto',
    'Tipo de documento',
    'Número identificación del informado',
    'Primer apellido del informado',
    'Segundo apellido del informado',
    'Primer nombre del informado',
    'Otros nombres del informado',
    'Razón social informado',
    'País de residencia o domicilio',
    'Ingresos brutos recibidos',
    'Devoluciones, rebajas y descuentos',
];

// Las columnas de identificación se fuerzan como texto para conservar ceros a la izquierda.
// Índices: Tipo doc(1), Num id(2), Apellido1(3), Apellido2(4), Nombre1(5), OtrosNombres(6),
//          Razón social(7), País(8)
$columnas_texto = array_flip([1, 2, 3, 4, 5, 6, 7, 8]);

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
// CONSULTA SQL — Formato 1007 (Ingresos recibidos por terceros)
// -------------------------------------------------------------------------
include '../../../../config/autoloader.php';
$conexion    = \Config\Clases\Conexion::getConexion();
$vigencia    = $_SESSION['vigencia'];
$id_vigencia = $_SESSION['id_vigencia'];

try {
    $sql = "SELECT
                `cl`.`id_tercero_api`               AS `id_tercero`,
                `cce`.`cod_concepto`                AS `concepto`,
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
                END                   AS `tipo_documento`,
                `t`.`nit_tercero`                   AS `no_documento`,
                `t`.`nom_tercero`                   AS `nom_tercero`,
                SUM(CASE WHEN `cp`.`cuenta` LIKE '4395%' THEN (IFNULL(`cl`.`debito`, 0) - IFNULL(`cl`.`credito`, 0)) ELSE 0 END) AS `devoluciones`,
                SUM(CASE WHEN `cp`.`cuenta` NOT LIKE '4395%' THEN (IFNULL(`cl`.`credito`, 0) - IFNULL(`cl`.`debito`, 0)) ELSE 0 END) AS `ingresos`
            FROM `ctb_homologacion`   AS `ch`
            INNER JOIN `ctb_ctas_exogena` AS `cce`
                ON (`ch`.`id_cuenta_otros` = `cce`.`id_cuenta` AND `cce`.`id_form` = 6)
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
            WHERE `ch`.`id_vigencia` = $id_vigencia
              AND `cd`.`estado`  = 2
              AND `cl`.`id_tercero_api` > 0
              AND DATE_FORMAT(`cd`.`fecha`,'%Y') = '$vigencia'
            GROUP BY `cl`.`id_tercero_api`, `cce`.`cod_concepto`, `ch`.`id_cuenta_otros`";

    $stmt = $conexion->prepare($sql);
    $stmt->execute();

    // Iterar línea por línea sin cargar todo en memoria
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

    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {

        $ingresos     = (float) $row['ingresos'];
        $devoluciones = (float) $row['devoluciones'];

        // Omitir registros si ambos son cero
        if ($ingresos == 0 && $devoluciones == 0) continue;

        $datos_api = $terceros_api_idx[$row['id_tercero']] ?? null;

        $nombre = [
            'apellido1' => '',
            'apellido2' => '',
            'nombre1'   => '',
            'nombre2'   => ''
        ];
        $razon_social = '';

        if ($datos_api) {
            if ($datos_api['tipo_doc'] == 5) {
                $razon_social = $datos_api['razon_social'] ?? '';
            } else {
                $nombre['apellido1'] = $datos_api['apellido1'] ?? '';
                $nombre['apellido2'] = $datos_api['apellido2'] ?? '';
                $nombre['nombre1']   = $datos_api['nombre1'] ?? '';
                $nombre['nombre2']   = $datos_api['nombre2'] ?? '';
            }
        }

        $linea = [
            $row['concepto'],                           // Concepto
            $row['tipo_documento'],                     // Tipo de documento
            $row['no_documento'],                       // Número identificación del informado
            $nombre['apellido1'],                       // Primer apellido del informado
            $nombre['apellido2'],                       // Segundo apellido del informado
            $nombre['nombre1'],                         // Primer nombre del informado
            $nombre['nombre2'],                         // Otros nombres del informado
            $razon_social,                              // Razón social informado
            '169',                                      // País de residencia o domicilio (169 = Colombia)
            round($ingresos, 2),                           // Ingresos brutos recibidos
            round($devoluciones, 2),                       // Devoluciones, rebajas y descuentos
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
