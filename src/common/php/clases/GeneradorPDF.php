<?php

namespace Src\Common\Php\Clases;

require_once dirname(__DIR__, 4) . '/vendor/autoload.php';

use Dompdf\Dompdf;
use Dompdf\Options;
use Src\Usuarios\Login\Php\Clases\Usuario;
use Config\Clases\Plantilla;

/**
 * Clase GeneradorPDF - Generación eficiente de PDFs usando DOMPDF
 * 
 * Esta clase proporciona métodos para generar PDFs de manera rápida y eficiente,
 * ideal para envíos masivos donde el rendimiento es crítico.
 * 
 * @package Src\Common\Php\Clases
 */
class GeneradorPDF
{
    private $dompdf;
    private $options;
    private $empresa;
    private $host;

    /**
     * Constructor de la clase GeneradorPDF
     * 
     * @param string $tamanoPapel Tamaño del papel (letter, A4, legal, etc.)
     * @param string $orientacion Orientación del papel (portrait, landscape)
     */
    public function __construct($tamanoPapel = 'letter', $orientacion = 'portrait')
    {
        // Configurar opciones de DOMPDF
        $this->options = new Options();
        $this->options->set('isHtml5ParserEnabled', true);
        $this->options->set('isRemoteEnabled', true);
        $this->options->set('defaultFont', 'Arial');
        $this->options->set('isPhpEnabled', true);
        $this->options->set('chroot', dirname(__DIR__, 4));

        // Crear instancia de DOMPDF
        $this->dompdf = new Dompdf($this->options);
        $this->dompdf->setPaper($tamanoPapel, $orientacion);

        // Obtener datos de la empresa
        $usuario = new Usuario();
        $this->empresa = $usuario->getEmpresa();
        $this->host = Plantilla::getHost();
    }

    /**
     * Genera un PDF a partir de contenido HTML
     * 
     * @param string $html Contenido HTML a convertir
     * @return string Contenido binario del PDF
     */
    public function generarDesdeHTML($html)
    {
        $this->dompdf->loadHtml($html);
        $this->dompdf->render();
        return $this->dompdf->output();
    }

    /**
     * Guarda el PDF en un archivo
     * 
     * @param string $html Contenido HTML a convertir
     * @param string $rutaArchivo Ruta donde guardar el PDF
     * @return bool True si se guardó correctamente
     */
    public function guardarPDF($html, $rutaArchivo)
    {
        $pdfContent = $this->generarDesdeHTML($html);
        return file_put_contents($rutaArchivo, $pdfContent) !== false;
    }

    /**
     * Genera el HTML completo de un documento con encabezado y estilos
     * 
     * @param string $titulo Título del documento
     * @param string $subtitulo Subtítulo o información adicional
     * @param string $contenido Contenido HTML del cuerpo
     * @param string $firmas HTML de las firmas (opcional)
     * @return string HTML completo listo para convertir a PDF
     */
    public function generarDocumentoHTML($titulo, $subtitulo, $contenido, $firmas = '')
    {
        $nombreEmpresa = $this->empresa['nombre'] ?? 'Empresa';
        $nitEmpresa = ($this->empresa['nit'] ?? '') . '-' . ($this->empresa['dv'] ?? '');

        // Convertir logo a base64 para que DOMPDF lo renderice correctamente
        $logoPath = $_SERVER['DOCUMENT_ROOT'] . $this->host . '/assets/images/logo.png';
        $logoBase64 = '';
        if (file_exists($logoPath)) {
            $logoData = file_get_contents($logoPath);
            $logoBase64 = 'data:image/png;base64,' . base64_encode($logoData);
        }

        return <<<HTML
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>{$titulo}</title>
    <style>
        @page {
            margin: 15mm 15mm 15mm 15mm;
        }
        body {
            font-family: Arial, Helvetica, sans-serif;
            font-size: 11px;
            color: #333;
            margin: 0;
            padding: 0;
            line-height: 1.4;
        }
        .header-container {
            background-color: #16a085;
            padding: 20px 25px;
            margin-bottom: 20px;
            border-radius: 8px;
        }
        .header-table {
            width: 100%;
        }
        .header-logo {
            width: 70px;
            vertical-align: middle;
        }
        .header-logo img {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            background: white;
            padding: 5px;
        }
        .header-title {
            text-align: center;
            vertical-align: middle;
            color: #ffffff;
        }
        .header-title h1 {
            margin: 0;
            font-size: 16px;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        .header-title .nit {
            font-size: 11px;
            margin-top: 5px;
            opacity: 0.9;
        }
        .document-title {
            text-align: center;
            margin: 20px 0;
            padding: 15px;
            background-color: #f8f9fa;
            border-radius: 6px;
        }
        .document-title h2 {
            margin: 0;
            font-size: 14px;
            color: #16a085;
            text-transform: uppercase;
        }
        .document-title .subtitle {
            font-size: 11px;
            color: #666;
            margin-top: 8px;
        }
        .content {
            margin-bottom: 20px;
        }
        table.data-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 10px;
        }
        table.data-table th,
        table.data-table td {
            border: 1px solid #ddd;
            padding: 6px 10px;
            text-align: left;
        }
        table.data-table th {
            background-color: #f5f5f5;
            font-weight: bold;
        }
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .bg-success { background-color: #d4edda; }
        .bg-danger { background-color: #f8d7da; }
        .bg-info { background-color: #cce5ff; }
        .bg-primary { background-color: #16a085; color: #fff; }
        .fw-bold { font-weight: bold; }
        .footer {
            margin-top: 30px;
            padding: 15px;
            font-size: 9px;
            text-align: center;
            color: #888;
            border-top: 1px solid #eee;
        }
        .page-break {
            page-break-after: always;
        }
    </style>
</head>
<body>
    <div class="header-container">
        <table class="header-table">
            <tr>
                <td class="header-logo">
                    <img src="{$logoBase64}" alt="Logo">
                </td>
                <td class="header-title">
                    <h1><strong>{$nombreEmpresa}</strong></h1>
                    <div class="nit">NIT {$nitEmpresa}</div>
                </td>
            </tr>
        </table>
    </div>
    
    <div class="document-title">
        <h2>{$titulo}</h2>
        <div class="subtitle">{$subtitulo}</div>
    </div>
    
    <div class="content">
        {$contenido}
    </div>
    
    {$firmas}
    
    <div class="footer">
        <p>Este es un documento generado automáticamente.</p>
        <p>© {$nombreEmpresa} - Todos los derechos reservados</p>
    </div>
</body>
</html>
HTML;
    }

    /**
     * Genera el HTML de un desprendible de nómina
     * 
     * @param array $d Datos del empleado y su liquidación
     * @param object $detalles Instancia de Detalles para obtener discriminados (opcional)
     * @param int $id_nomina ID de la nómina (opcional)
     * @return string HTML del desprendible
     */
    public function generarDesprendibleHTML($d, $detalles = null, $id_nomina = null)
    {
        // Obtener detalles discriminados si se proporcionan los parámetros
        $detalle_horas = [];
        $detalle_libranzas = [];
        $detalle_embargos = [];
        $detalle_sindicatos = [];
        $detalle_otros_dctos = [];
        $detalle_viaticos = [];

        if ($detalles !== null && $id_nomina !== null) {
            $detalle_horas = $detalles->getDetalleHorasExtras($d['id_empleado'], $id_nomina);
            $detalle_libranzas = $detalles->getDetalleLibranzas($d['id_empleado'], $id_nomina);
            $detalle_embargos = $detalles->getDetalleEmbargos($d['id_empleado'], $id_nomina);
            $detalle_sindicatos = $detalles->getDetalleSindicatos($d['id_empleado'], $id_nomina);
            $detalle_otros_dctos = $detalles->getDetalleOtrosDescuentos($d['id_empleado'], $id_nomina);
            $detalle_viaticos = $detalles->getDetalleViaticos($d['id_empleado'], $id_nomina);
        }

        $valor_licencias = ($d['valor_luto'] ?? 0) + ($d['valor_mp'] ?? 0);

        $devengados = [
            'Salario Laborado' => $d['valor_laborado'] ?? 0,
            'Compensatorio' => $d['val_compensa'] ?? 0,
            'Incapacidades (' . ($d['dias_incapacidad'] ?? 0) . ' días)' => $d['valor_incap'] ?? 0,
            'Licencias (' . ($d['dias_licencias'] ?? 0) . ' días)' => $valor_licencias,
            'Vacaciones (' . ($d['dias_vacaciones'] ?? 0) . ' días)' => $d['valor_vacacion'] ?? 0,
            'Prima de Vacaciones' => $d['val_prima_vac'] ?? 0,
            'Bonificación Recreación' => $d['val_bon_recrea'] ?? 0,
            'Auxilio de Transporte' => $d['aux_tran'] ?? 0,
            'Auxilio de Alimentación' => $d['aux_alim'] ?? 0,
            'Bonificación Servicios Prestados' => $d['val_bsp'] ?? 0,
            'Gastos de Representación' => $d['g_representa'] ?? 0,
            'Prima de Servicios' => $d['valor_ps'] ?? 0,
            'Prima de Navidad' => $d['valor_pv'] ?? 0,
            'Cesantías' => $d['val_cesantias'] ?? 0,
            'Intereses Cesantías' => $d['val_icesantias'] ?? 0,
        ];

        // Agregar horas extras con su detalle si existe
        if (!empty($detalle_horas)) {
            $total_horas = array_sum(array_column($detalle_horas, 'valor'));
            $devengados['Horas Extras'] = $total_horas;
        } else {
            $devengados['Horas Extras'] = $d['horas_ext'] ?? 0;
        }

        if (!empty($detalle_viaticos)) {
            $total_viaticos = array_sum(array_column($detalle_viaticos, 'valor'));
            $devengados['Viáticos'] = $total_viaticos;
        } else {
            $devengados['Viáticos'] = $d['valor_viatico'] ?? 0;
        }

        $deducciones = [
            'Aporte Salud (4%)' => $d['valor_salud'] ?? 0,
            'Aporte Pensión (4%)' => $d['valor_pension'] ?? 0,
            'Pensión Solidaria' => $d['val_psolidaria'] ?? 0,
            'Retención en la Fuente' => $d['val_retencion'] ?? 0,
        ];

        // Agregar conceptos discriminados solo si tienen valor
        if (!empty($detalle_libranzas)) {
            $total_libranzas = array_sum(array_column($detalle_libranzas, 'valor'));
            $deducciones['Libranzas'] = $total_libranzas;
        } else {
            $deducciones['Libranzas'] = $d['valor_libranza'] ?? 0;
        }

        if (!empty($detalle_embargos)) {
            $total_embargos = array_sum(array_column($detalle_embargos, 'valor'));
            $deducciones['Embargos'] = $total_embargos;
        } else {
            $deducciones['Embargos'] = $d['valor_embargo'] ?? 0;
        }

        if (!empty($detalle_sindicatos)) {
            $total_sindicatos = array_sum(array_column($detalle_sindicatos, 'valor'));
            $deducciones['Sindicato'] = $total_sindicatos;
        } else {
            $deducciones['Sindicato'] = $d['valor_sind'] ?? 0;
        }

        if (!empty($detalle_otros_dctos)) {
            $total_otros = array_sum(array_column($detalle_otros_dctos, 'valor'));
            $deducciones['Otros Descuentos'] = $total_otros;
        } else {
            $deducciones['Otros Descuentos'] = $d['valor_dcto'] ?? 0;
        }

        // Filtrar solo los que tienen valor > 0
        $devengados = array_filter($devengados, function ($v) {
            return $v > 0;
        });
        $deducciones = array_filter($deducciones, function ($v) {
            return $v > 0;
        });

        $total_devengado = array_sum($devengados);
        $total_deducciones = array_sum($deducciones);
        $neto_pagar = $total_devengado - $total_deducciones;

        $sal_base_fmt = number_format($d['sal_base'] ?? 0, 0, ',', '.');
        $total_devengado_fmt = number_format($total_devengado, 0, ',', '.');
        $total_deducciones_fmt = number_format($total_deducciones, 0, ',', '.');
        $neto_pagar_fmt = number_format($neto_pagar, 0, ',', '.');

        $filas_devengados = '';
        foreach ($devengados as $concepto => $valor) {
            $valor_fmt = number_format($valor, 0, ',', '.');
            $filas_devengados .= "<tr><td style='padding: 4px 8px;'>{$concepto}</td><td style='text-align: right; padding: 4px 8px;'>{$valor_fmt}</td></tr>";

            // Si es Horas Extras y tiene detalle, mostrar el desglose
            if ($concepto === 'Horas Extras' && !empty($detalle_horas)) {
                foreach ($detalle_horas as $hora) {
                    $valor_hora_fmt = number_format($hora['valor'], 0, ',', '.');
                    $filas_devengados .= "<tr style='font-size: 9px;'><td style='padding: 2px 8px 2px 20px; color: #666;'>• {$hora['tipo']} ({$hora['cantidad']} hrs)</td><td style='text-align: right; padding: 2px 8px; color: #666;'>{$valor_hora_fmt}</td></tr>";
                }
            }

            // Si es Viáticos y tiene detalle, mostrar el desglose
            if ($concepto === 'Viáticos' && !empty($detalle_viaticos)) {
                foreach ($detalle_viaticos as $viatico) {
                    $valor_v_fmt = number_format($viatico['valor'], 0, ',', '.');
                    $filas_devengados .= "<tr style='font-size: 9px;'><td style='padding: 2px 8px 2px 20px; color: #666;'>• Res: {$viatico['resolucion']} - Dest: {$viatico['destino']}</td><td style='text-align: right; padding: 2px 8px; color: #666;'>{$valor_v_fmt}</td></tr>";
                }
            }
        }

        $filas_deducciones = '';
        foreach ($deducciones as $concepto => $valor) {
            $valor_fmt = number_format($valor, 0, ',', '.');
            $filas_deducciones .= "<tr><td style='padding: 4px 8px;'>{$concepto}</td><td style='text-align: right; padding: 4px 8px;'>{$valor_fmt}</td></tr>";

            // Discriminar libranzas
            if ($concepto === 'Libranzas' && !empty($detalle_libranzas)) {
                foreach ($detalle_libranzas as $libranza) {
                    $valor_lib_fmt = number_format($libranza['valor'], 0, ',', '.');
                    $filas_deducciones .= "<tr style='font-size: 9px;'><td style='padding: 2px 8px 2px 20px; color: #666;'>• {$libranza['entidad']}</td><td style='text-align: right; padding: 2px 8px; color: #666;'>{$valor_lib_fmt}</td></tr>";
                }
            }

            // Discriminar embargos
            if ($concepto === 'Embargos' && !empty($detalle_embargos)) {
                foreach ($detalle_embargos as $embargo) {
                    $valor_emb_fmt = number_format($embargo['valor'], 0, ',', '.');
                    $filas_deducciones .= "<tr style='font-size: 9px;'><td style='padding: 2px 8px 2px 20px; color: #666;'>• {$embargo['descripcion']}</td><td style='text-align: right; padding: 2px 8px; color: #666;'>{$valor_emb_fmt}</td></tr>";
                }
            }

            // Discriminar sindicatos
            if ($concepto === 'Sindicato' && !empty($detalle_sindicatos)) {
                foreach ($detalle_sindicatos as $sindicato) {
                    $valor_sind_fmt = number_format($sindicato['valor'], 0, ',', '.');
                    $filas_deducciones .= "<tr style='font-size: 9px;'><td style='padding: 2px 8px 2px 20px; color: #666;'>• {$sindicato['sindicato']}</td><td style='text-align: right; padding: 2px 8px; color: #666;'>{$valor_sind_fmt}</td></tr>";
                }
            }

            // Discriminar otros descuentos
            if ($concepto === 'Otros Descuentos' && !empty($detalle_otros_dctos)) {
                foreach ($detalle_otros_dctos as $otro) {
                    $valor_otro_fmt = number_format($otro['valor'], 0, ',', '.');
                    $descripcion = $otro['tipo'];
                    if (!empty($otro['concepto'])) {
                        $descripcion .= ': ' . $otro['concepto'];
                    }
                    $filas_deducciones .= "<tr style='font-size: 9px;'><td style='padding: 2px 8px 2px 20px; color: #666;'>• {$descripcion}</td><td style='text-align: right; padding: 2px 8px; color: #666;'>{$valor_otro_fmt}</td></tr>";
                }
            }
        }

        return <<<HTML
<!-- Información del Empleado -->
<table class="data-table">
    <tr style="background-color: #f5f5f5;">
        <th colspan="4" style="text-align: center;">INFORMACIÓN DEL EMPLEADO</th>
    </tr>
    <tr>
        <td style="width: 15%; font-weight: bold;">Nombre:</td>
        <td style="width: 35%;">{$d['nombre']}</td>
        <td style="width: 15%; font-weight: bold;">Documento:</td>
        <td style="width: 35%;">{$d['no_documento']}</td>
    </tr>
    <tr>
        <td style="font-weight: bold;">Cargo:</td>
        <td>{$d['descripcion_carg']}</td>
        <td style="font-weight: bold;">Sede:</td>
        <td>{$d['sede']}</td>
    </tr>
    <tr>
        <td style="font-weight: bold;">Salario Base:</td>
        <td>$ {$sal_base_fmt}</td>
        <td style="font-weight: bold;">Días Laborados:</td>
        <td>{$d['dias_lab']}</td>
    </tr>
</table>

<!-- Devengados y Deducciones -->
<table style="width: 100%; border-collapse: collapse;">
    <tr>
        <td style="width: 50%; vertical-align: top; padding: 0;">
            <table class="data-table">
                <tr class="bg-success">
                    <th colspan="2" style="text-align: center;">DEVENGADOS</th>
                </tr>
                {$filas_devengados}
                <tr class="bg-success fw-bold">
                    <td style="padding: 6px 8px;">TOTAL DEVENGADO</td>
                    <td style="text-align: right; padding: 6px 8px;">$ {$total_devengado_fmt}</td>
                </tr>
            </table>
        </td>
        <td style="width: 50%; vertical-align: top; padding: 0;">
            <table class="data-table">
                <tr class="bg-danger">
                    <th colspan="2" style="text-align: center;">DEDUCCIONES</th>
                </tr>
                {$filas_deducciones}
                <tr class="bg-danger fw-bold">
                    <td style="padding: 6px 8px;">TOTAL DEDUCCIONES</td>
                    <td style="text-align: right; padding: 6px 8px;">$ {$total_deducciones_fmt}</td>
                </tr>
            </table>
        </td>
    </tr>
</table>

<!-- Neto a Pagar -->
<table class="data-table" style="margin-top: 15px;">
    <tr class="bg-info">
        <td style="padding: 10px; font-size: 13px; font-weight: bold; text-align: center; width: 70%;">NETO A PAGAR</td>
        <td style="padding: 10px; font-size: 13px; font-weight: bold; text-align: right; width: 30%;">$ {$neto_pagar_fmt}</td>
    </tr>
</table>

<p style="font-size: 9px; text-align: center; margin-top: 15px; color: #666;">
    Este desprendible es un documento informativo. Para cualquier aclaración, dirigirse al área de Recursos Humanos.
</p>
HTML;
    }

    /**
     * Genera PDF de desprendible de nómina completo
     * 
     * @param array $datosEmpleado Datos del empleado
     * @param string $titulo Título del documento
     * @param string $subtitulo Subtítulo
     * @param string $firmas HTML de firmas (opcional)
     * @param object $detalles Instancia de Detalles para obtener discriminados (opcional)
     * @param int $id_nomina ID de la nómina (opcional)
     * @return string Contenido binario del PDF
     */
    public function generarDesprendiblePDF($datosEmpleado, $titulo, $subtitulo, $firmas = '', $detalles = null, $id_nomina = null)
    {
        $contenido = $this->generarDesprendibleHTML($datosEmpleado, $detalles, $id_nomina);
        $html = $this->generarDocumentoHTML($titulo, $subtitulo, $contenido, $firmas);
        return $this->generarDesdeHTML($html);
    }

    /**
     * Genera PDFs masivos de desprendibles (optimizado para envío masivo)
     * 
     * @param array $empleados Array de datos de empleados
     * @param string $titulo Título del documento
     * @param string $subtitulo Subtítulo
     * @param callable $callback Función callback para cada PDF generado: function($pdfContent, $empleado)
     * @return array Estadísticas del proceso
     */
    public function generarDesprendiblesMasivo($empleados, $titulo, $subtitulo, $callback = null)
    {
        $stats = ['total' => count($empleados), 'exitosos' => 0, 'fallidos' => 0, 'errores' => []];

        foreach ($empleados as $empleado) {
            try {
                // Generar nuevo DOMPDF para cada documento (evita acumulación de memoria)
                $this->dompdf = new Dompdf($this->options);
                $this->dompdf->setPaper('letter', 'portrait');

                $pdfContent = $this->generarDesprendiblePDF($empleado, $titulo, $subtitulo);

                if ($callback && is_callable($callback)) {
                    $callback($pdfContent, $empleado);
                }

                $stats['exitosos']++;
            } catch (\Exception $e) {
                $stats['fallidos']++;
                $stats['errores'][] = [
                    'empleado' => $empleado['nombre'] ?? 'Desconocido',
                    'error' => $e->getMessage()
                ];
            }
        }

        return $stats;
    }
}
