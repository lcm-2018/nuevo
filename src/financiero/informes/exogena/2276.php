<?php
session_start();
if (!isset($_SESSION['user'])) {
    echo "Acceso denegado";
    exit();
}

// Configurar cabeceras para forzar la descarga del Excel por streaming
$nombre_archivo = "Formato_2276_" . date("Ymd_His") . ".xls";

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

// Definir los encabezados de las columnas (Formato 2276)
$columnas = [
    'Entidad Informante',
    'Tipo de documento del beneficiario',
    'Número de Identificación del beneficiario',
    'Primer Apellido del beneficiario',
    'Segundo Apellido del beneficiario',
    'Primer Nombre del beneficiario',
    'Otros Nombres del beneficiario',
    'Dirección del beneficiario',
    'Departamento del beneficiario',
    'Municipio del beneficiario',
    'País del beneficiario',
    'Pagos por Salarios',
    'Pagos por emolumentos eclesiásticos',
    'Pagos realizados con bonos electrónicos o de papel de servicio, cheques, tarjetas, vales, etc.',
    'Valor del exceso de los pagos por alimentación mayores a 41 UVT, art. 387-1 E.T.',
    'Pagos por honorarios',
    'Pagos por servicios',
    'Pagos por comisiones',
    'Pagos por prestaciones sociales',
    'Pagos por viáticos',
    'Pagos por gastos de representación',
    'Pagos por compensaciones trabajo asociado cooperativo',
    'Valor apoyos económicos no reembolsables o condonados, entregados por el Estado o financiados con recursos públicos, para financiar programas educativos.',
    'Otros pagos',
    'Cesantías e intereses de cesantías efectivamente pagadas al empleado',
    'Cesantías consignadas al fondo de cesantías',
    'Auxilio de cesantías reconocido a trabajadores del régimen tradicional del Código Sustantivo del Trabajo, Capítulo VII, Título VIII Parte Primera',
    'Pensiones de Jubilación, vejez o invalidez',
    'Total ingresos brutos por rentas de trabajo y pensión',
    'Aportes obligatorios por salud a cargo del trabajador',
    'Aportes obligatorios a fondos de pensiones y solidaridad pensional a cargo del trabajador',
    'Aportes voluntarios al régimen de ahorro individual con solidaridad - RAIS',
    'Aportes voluntarios a fondos de pensiones voluntarias',
    'Aportes a cuentas AFC',
    'Aportes a cuentas AVC',
    'Valor de las retenciones en la fuente por pagos de rentas de trabajo o pensiones',
    'Impuesto sobre las ventas - IVA, mayor valor del costo o gasto',
    'Retención en la fuente a título de impuesto sobre las ventas - IVA.',
    'Pagos por alimentación hasta 41 UVT',
    'Valor ingreso laboral promedio de los últimos seis meses.',
    'Tipo de documento del dependiente económico',
    'Número de Identificación del dependiente económico',
    'Identificación del fideicomiso',
    'Tipo documento participante en contrato de colaboración',
    'Identificación participante en contrato colaboración'
];

// Las columnas de identificación se fuerzan como texto para conservar ceros a la izquierda.
$columnas_texto = array_flip([0, 1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 40, 41, 42, 43, 44]);

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
    // 1. Aquí colocas tu consulta SQL PIVOTEADA
    $sql = "WITH rubros_cesantias AS (
                SELECT r_admin AS id_rubro
                FROM nom_rel_rubro
                WHERE id_vigencia = $id_vigencia AND id_tipo = 18 AND r_admin > 0

                UNION

                SELECT r_operativo AS id_rubro
                FROM nom_rel_rubro
                WHERE id_vigencia = $id_vigencia AND id_tipo = 18 AND r_operativo > 0
            ),
            docs_14 AS (
                -- Primero obtenemos los comprobantes (Causaciones o Pagos) que tocaron la cuenta de Cesantías Consignadas (Concepto 14)
                SELECT DISTINCT cd.id_ctb_doc, cd.fecha
                FROM ctb_homologacion AS ch
                INNER JOIN ctb_ctas_exogena AS cce 
                    ON cce.id_cuenta = ch.id_cuenta_otros AND cce.id_form = 15 AND cce.cod_concepto = '14'
                INNER JOIN ctb_libaux AS cl 
                    ON cl.id_cuenta = ch.id_cuenta
                INNER JOIN ctb_doc AS cd 
                    ON cl.id_ctb_doc = cd.id_ctb_doc
                WHERE ch.id_vigencia = $id_vigencia
                  AND cl.debito > 0 AND cd.estado = 2
                  AND DATE_FORMAT(cd.fecha,'%Y') = '$vigencia'
            ),
            datos_base AS (
                -- Todos los conceptos excepto el 14 (Cesantías Consignadas)
                SELECT 
                    cl.id_tercero_api,
                    cce.cod_concepto,
                    cl.debito,
                    cl.credito,
                    cd.fecha
                FROM ctb_homologacion AS ch
                INNER JOIN ctb_ctas_exogena AS cce 
                    ON cce.id_cuenta = ch.id_cuenta_otros AND cce.id_form = 15
                INNER JOIN ctb_libaux AS cl 
                    ON cl.id_cuenta = ch.id_cuenta
                INNER JOIN ctb_doc AS cd 
                    ON cl.id_ctb_doc = cd.id_ctb_doc
                WHERE ch.id_vigencia = $id_vigencia
                  AND (cl.debito > 0 OR cl.credito > 0) AND cd.estado = 2 AND cl.id_tercero_api > 0
                  AND cce.cod_concepto IN ('1','2','3','4','5','6','7','8','9','10','11','12','13','15','16','17','18','19')
                  AND DATE_FORMAT(cd.fecha,'%Y') = '$vigencia'
                
                UNION ALL
                
                -- Concepto 14: Extraído desde pto_cop_detalle (si el asiento fue una Causación)
                SELECT 
                    pcd.id_tercero_api,
                    '14' AS cod_concepto,
                    pcd.valor AS debito,
                    0 AS credito,
                    docs.fecha
                FROM pto_cop_detalle pcd
                INNER JOIN docs_14 docs ON pcd.id_ctb_doc = docs.id_ctb_doc
                INNER JOIN pto_crp_detalle pcrpd ON pcd.id_pto_crp_det = pcrpd.id_pto_crp_det
                INNER JOIN pto_cdp_detalle pcdpd ON pcrpd.id_pto_cdp_det = pcdpd.id_pto_cdp_det
                INNER JOIN rubros_cesantias rc ON pcdpd.id_rubro = rc.id_rubro
                WHERE pcd.id_tercero_api > 0 AND pcd.valor > 0
                
                UNION ALL
                
                -- Concepto 14: Extraído desde pto_pag_detalle (si el asiento fue un Pago / Egreso)
                SELECT 
                    ppd.id_tercero_api,
                    '14' AS cod_concepto,
                    ppd.valor AS debito,
                    0 AS credito,
                    docs.fecha
                FROM pto_pag_detalle ppd
                INNER JOIN docs_14 docs ON ppd.id_ctb_doc = docs.id_ctb_doc
                INNER JOIN pto_cop_detalle pcd ON ppd.id_pto_cop_det = pcd.id_pto_cop_det
                INNER JOIN pto_crp_detalle pcrpd ON pcd.id_pto_crp_det = pcrpd.id_pto_crp_det
                INNER JOIN pto_cdp_detalle pcdpd ON pcrpd.id_pto_cdp_det = pcdpd.id_pto_cdp_det
                INNER JOIN rubros_cesantias rc ON pcdpd.id_rubro = rc.id_rubro
                WHERE ppd.id_tercero_api > 0 AND ppd.valor > 0
            ),
            ultimo_pago AS (
                -- 1. Buscamos cuál fue el último pago de nómina de cada empleado
                SELECT 
                    id_tercero_api,
                    MAX(fecha) AS max_fecha,
                    DATE_SUB(MAX(fecha), INTERVAL 6 MONTH) AS fecha_inicio_6m
                FROM datos_base
                WHERE cod_concepto IN ('1','2','3','4','5','6','7','8','9','10','11','12','13','14','15','16')
                GROUP BY id_tercero_api
            ),
            promedio_6m AS (
                -- 2. Sumamos los últimos 6 meses de cada empleado y lo dividimos 
                -- entre la cantidad real de meses que trabajaron en ese lapso.
                SELECT 
                    db.id_tercero_api,
                    SUM(db.debito) / COUNT(DISTINCT DATE_FORMAT(db.fecha, '%Y-%m')) AS valor_promedio
                FROM datos_base db
                INNER JOIN ultimo_pago up 
                    ON db.id_tercero_api = up.id_tercero_api
                WHERE db.cod_concepto IN ('1','2','3','4','5','6','7','8','9','10','11','12','13','14','15','16')
                  -- Filtramos desde el mes de su último pago hacia 6 meses atrás
                  AND db.fecha > up.fecha_inicio_6m AND db.fecha <= up.max_fecha
                GROUP BY db.id_tercero_api
            ),
            dependientes AS (
                SELECT 
                    tdep.id_tercero_api,
                    GROUP_CONCAT(tdoc.codigo_ne SEPARATOR ',') AS tipos_doc_dependientes,
                    GROUP_CONCAT(tdep.no_documento SEPARATOR ',') AS nums_doc_dependientes
                FROM tb_terceros_dependientes tdep
                INNER JOIN tb_tipos_documento tdoc 
                    ON tdep.id_tipo_doc = tdoc.id_tipodoc
                WHERE tdep.estado = 1
                GROUP BY tdep.id_tercero_api
            ),
            pivote AS (
                SELECT
                    id_tercero_api,
                    
                    -- Aquí pivoteamos según el cod_concepto 
                    SUM(CASE WHEN cod_concepto = '1' THEN debito ELSE 0 END) AS pagos_salarios,
                    SUM(CASE WHEN cod_concepto = '2' THEN debito ELSE 0 END) AS pagos_emolumentos,
                    SUM(CASE WHEN cod_concepto = '3' THEN debito ELSE 0 END) AS pagos_bonos,
                    SUM(CASE WHEN cod_concepto = '4' THEN debito ELSE 0 END) AS exceso_pagos_alim,
                    SUM(CASE WHEN cod_concepto = '5' THEN debito ELSE 0 END) AS pagos_honorarios,
                    SUM(CASE WHEN cod_concepto = '6' THEN debito ELSE 0 END) AS pagos_servicios,
                    SUM(CASE WHEN cod_concepto = '7' THEN debito ELSE 0 END) AS pagos_comisiones,
                    SUM(CASE WHEN cod_concepto = '8' THEN debito ELSE 0 END) AS pagos_prestaciones,
                    SUM(CASE WHEN cod_concepto = '9' THEN debito ELSE 0 END) AS pagos_viaticos,
                    SUM(CASE WHEN cod_concepto = '10' THEN debito ELSE 0 END) AS gastos_representacion,
                    SUM(CASE WHEN cod_concepto = '11' THEN debito ELSE 0 END) AS compensaciones_coop,
                    SUM(CASE WHEN cod_concepto = '12' THEN debito ELSE 0 END) AS otros_pagos,
                    SUM(CASE WHEN cod_concepto = '13' THEN debito ELSE 0 END) AS cesantias_pagadas,
                    SUM(CASE WHEN cod_concepto = '14' THEN debito ELSE 0 END) AS cesantias_consignadas,
                    SUM(CASE WHEN cod_concepto = '15' THEN debito ELSE 0 END) AS auxilio_cesantias,
                    SUM(CASE WHEN cod_concepto = '16' THEN debito ELSE 0 END) AS pensiones,
                    GREATEST(
                        SUM(CASE WHEN cod_concepto = '17' THEN debito ELSE 0 END),
                        SUM(CASE WHEN cod_concepto = '17' THEN credito ELSE 0 END)
                    ) AS aportes_salud,
                    GREATEST(
                        SUM(CASE WHEN cod_concepto = '18' THEN debito ELSE 0 END),
                        SUM(CASE WHEN cod_concepto = '18' THEN credito ELSE 0 END)
                    ) AS aportes_pensiones,
                    SUM(CASE WHEN cod_concepto = '19' THEN credito ELSE 0 END) AS retenciones
                    
                FROM datos_base
                GROUP BY id_tercero_api
            )
            SELECT 
                p.id_tercero_api,
                p.pagos_salarios,
                p.pagos_emolumentos,
                p.pagos_bonos,
                p.exceso_pagos_alim,
                p.pagos_honorarios,
                p.pagos_servicios,
                p.pagos_comisiones,
                p.pagos_prestaciones,
                p.pagos_viaticos,
                p.gastos_representacion,
                p.compensaciones_coop,
                p.otros_pagos,
                p.cesantias_pagadas,
                p.cesantias_consignadas,
                p.auxilio_cesantias,
                p.pensiones,
                p.aportes_salud,
                p.aportes_pensiones,
                p.retenciones,
                td.codigo_ne,
                ne.no_documento,
                ne.nombre1,
                ne.nombre2,
                ne.apellido1,
                ne.apellido2,
                t.dir_tercero,
                m.codigo_municipio,
                d.codigo_departamento,
                pr.valor_promedio,
                dep.tipos_doc_dependientes,
                dep.nums_doc_dependientes
            FROM pivote p
            INNER JOIN tb_terceros AS t 
                ON p.id_tercero_api = t.id_tercero_api
            INNER JOIN nom_empleado AS ne 
                ON t.nit_tercero = ne.no_documento
            INNER JOIN tb_tipos_documento AS td 
                ON td.id_tipodoc = ne.tipo_doc
            LEFT JOIN tb_municipios AS m 
                ON t.id_municipio = m.id_municipio
            LEFT JOIN tb_departamentos AS d 
                ON m.id_departamento = d.id_departamento
            LEFT JOIN promedio_6m AS pr 
                ON p.id_tercero_api = pr.id_tercero_api
            LEFT JOIN dependientes AS dep 
                ON p.id_tercero_api = dep.id_tercero_api";
    $stmt = $conexion->prepare($sql);
    $stmt->execute();

    // 2. Iterar línea por línea sin cargar todo en memoria
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {

        // 3. Mapear los datos de tu fila a un arreglo (debe coincidir con el orden de $columnas)
        $linea = [
            '1', // Entidad Informante
            $row['codigo_ne'], // Tipo doc (ej: 13, 31, etc.)
            $row['no_documento'], // Num doc
            $row['apellido1'], // Primer Apellido
            $row['apellido2'], // Segundo Apellido
            $row['nombre1'], // Primer Nombre
            $row['nombre2'], // Otros Nombres
            $row['dir_tercero'], // Dirección (Asumiendo que el campo se llama dir_tercero)
            $row['codigo_departamento'], // Departamento
            $row['codigo_departamento'] . $row['codigo_municipio'], // Municipio
            '169', // País (169 es Colombia)
            $row['pagos_salarios'], // (1) Salarios
            $row['pagos_emolumentos'], // (2) Emolumentos
            $row['pagos_bonos'], // (3) Bonos
            $row['exceso_pagos_alim'], // (4) Exceso Alim.
            $row['pagos_honorarios'], // (5) Honorarios
            $row['pagos_servicios'], // (6) Servicios
            $row['pagos_comisiones'], // (7) Comisiones
            $row['pagos_prestaciones'], // (8) Prestaciones
            $row['pagos_viaticos'], // (9) Viáticos
            $row['gastos_representacion'], // (10) Gastos Rep.
            $row['compensaciones_coop'], // (11) Compensaciones Coop.
            0, // Valor apoyos económicos no reembolsables (Revisa qué cod_concepto es este)
            $row['otros_pagos'], // (12) Otros pagos
            $row['cesantias_pagadas'], // (13) Cesantías pagadas
            $row['cesantias_consignadas'], // (14) Cesantías consignadas
            $row['auxilio_cesantias'], // (15) Auxilio cesantías
            $row['pensiones'], // (16) Pensiones
            $row['pagos_salarios'] + $row['pagos_emolumentos'] + $row['pagos_bonos'] + $row['exceso_pagos_alim'] + $row['pagos_honorarios'] + $row['pagos_servicios'] + $row['pagos_comisiones'] + $row['pagos_prestaciones'] + $row['pagos_viaticos'] + $row['gastos_representacion'] + $row['compensaciones_coop'] + $row['otros_pagos'] + $row['cesantias_pagadas'] + $row['cesantias_consignadas'] + $row['auxilio_cesantias'] + $row['pensiones'], // (1- 16) Total
            $row['aportes_salud'], // (17) Aportes salud
            $row['aportes_pensiones'], // (18) Aportes pensiones
            0,
            0,
            0,
            0,
            $row['retenciones'], // (19) Retenciones
            0,
            0,
            0,
            round($row['valor_promedio'] ?? 0, 2), // Promedio de los últimos 6 meses laborados
            $row['tipos_doc_dependientes'] ?? '', // Tipo documento dependiente (separados por coma)
            $row['nums_doc_dependientes'] ?? '', // Numero de documento dependiente (separados por coma)
            '', // Identificación del fideicomiso
            '', // Tipo documento participante en contrato de colaboración
            '', // Identificación participante en contrato colaboración
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
