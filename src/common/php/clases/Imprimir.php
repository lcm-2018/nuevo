<?php

namespace Src\Common\Php\Clases;

use Config\Clases\Plantilla;
use Src\Usuarios\Login\Php\Clases\Usuario;

class Imprimir
{

    private $titulo;
    private $hoja;
    private $cssExtra;

    private $pieDePagina = "";
    private $encabezado = "";
    private $contenido = "";
    private $firmas = "";
    private $empresa = "";
    private $host = "";

    public function __construct($titulo = "Documento", $hoja = "letter")
    {
        $this->titulo = $titulo;
        $this->hoja = $hoja;
        $this->cssBase();
        $usuario = new Usuario();
        $this->empresa = $usuario->getEmpresa();
        $this->host = Plantilla::getHost();
    }

    private function cssBase()
    {
        // CSS base genérico
        // Se usa una tabla para la maquetación principal para que thead y tfoot se repitan en cada página.
        $this->cssExtra = "
            <style>
            @page {
                size: {$this->hoja};
                margin: 20mm 15mm 10mm 20mm; /* Aumentamos margen superior e inferior para el encabezado/pie */

                /* Contenido para el pie de página */
                @bottom-right {
                    content: 'Pág. ' counter(page) ' de ' counter(pages);
                    font-family: Arial, sans-serif;
                    font-size: 10px;
                }
            }

            body {
                color: #000;
                font-size: 12px;
                max-width: 816px;
                font-family: Arial, sans-serif;
                margin: 0;
            }

            @media print {
                .no-print { display: none; }
                body { margin: 0; }

                /* Estilos para que el encabezado y pie se repitan */
                thead { display: table-header-group; }
                tfoot { display: table-footer-group; }
            }

            /* Tabla principal de maquetación */
            .layout-table {
                width: 100%;
                border-collapse: collapse;
            }

            .layout-table thead, .layout-table tfoot {
                /* Evita que el contenido del cuerpo se solape */
                visibility: hidden;
            }

            .layout-table thead td, .layout-table tfoot td {
                visibility: visible;
            }

            /* Contenedor del encabezado */
            .header-container {
                width: 100%;
                border-bottom: 2px solid #5555551d;
                padding-bottom: 5px;
                margin-bottom: 5px; /* Espacio entre encabezado y contenido */
                display: table;
            }

            .header-container .logo {
                display: table-cell;
                width: 100px;
                vertical-align: middle;
            }

            .header-container .logo img {
                width: 100px;
            }

            .header-container .title {
                display: table-cell;
                width: auto;
                text-align: center;
                vertical-align: middle;
            }

            .header-container h1 {
                margin: 0;
                font-size: 16px;
                text-transform: uppercase;
            }

            /* Contenido principal */
            .contenido {
                line-height: 1.5;
                text-align: justify;
            }
            </style>
        ";
    }

    public function addEncabezado($documento = "")
    {
        $logoPath = (isset($_SERVER['HTTPS']) ? 'https://' : 'http://') . $_SERVER['HTTP_HOST']  . $this->host . '/assets/images/logo.png';
        $documento = mb_strtoupper($documento);
        $this->encabezado =
            <<<HTML
            <div class='header-container'>
                <div class='logo'>
                    <img src='{$logoPath}' alt='Logo'>
                </div>
                <div class='title'>
                    <h1><strong>{$this->empresa['nombre']}</strong></h1>
                    <div>NIT {$this->empresa['nit']}-{$this->empresa['dv']}</div>
                    <br>
                    <div style='text-transform: uppercase; padding-bottom: 10px;'>{$documento}</div>
                </div>
            </div>
            HTML;
    }

    public function addContenido($html)
    {
        $this->contenido .= $html;
    }

    public function addFirmas($firmas = '')
    {
        $html = "<div>{$firmas}</div>";
        $this->firmas = $html;
    }

    public function render($pdf = false)
    {
        $script = "";
        if (!$pdf) {
            $script = "<script>window.print();</script>";
        }
        $faviconPath = (isset($_SERVER['HTTPS']) ? 'https://' : 'http://') . $_SERVER['HTTP_HOST']  . $this->host . '/assets/images/favicon.png';
        $html =
            <<<HTML
                <!DOCTYPE html>
                    <html lang='es'>
                        <head>
                            <meta charset='UTF-8'>
                            <title>{$this->titulo}</title>
                            <link rel='shortcut icon' type='image/png' href='{$faviconPath}' />
                            {$this->cssExtra}
                        </head>
                        <body>
                            <table class='layout-table'>
                                <thead>
                                    <tr>
                                        <td>{$this->encabezado}</td>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td>
                                            <div class='contenido'>
                                                {$this->contenido}
                                                {$this->firmas}
                                            </div>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                            {$script}
                        </body>
                    </html>
            HTML;
        if ($pdf) {
            return $html;
        } else {
            echo $html;
        }
    }

    public function getPDF($html)
    {
        $tmpHtmlFile = tempnam(sys_get_temp_dir(), 'cdp_html_') . '.html';
        $tmpPdfFile = tempnam(sys_get_temp_dir(), 'cdp_pdf_') . '.pdf';

        // Guardar el HTML en un archivo temporal para que Node lo lea
        file_put_contents($tmpHtmlFile, $html);

        // Construir la ruta al script de Node.js de forma más directa
        $nodeScript = $_SERVER['DOCUMENT_ROOT'] . Plantilla::getHost() . '/src/common/js/pdf.js';

        if (!file_exists($nodeScript)) {
            echo "No se encontró el script Node.js para generar PDF: {$nodeScript}";
            if (file_exists($tmpHtmlFile)) unlink($tmpHtmlFile);
            if (file_exists($tmpPdfFile)) unlink($tmpPdfFile);
            exit;
        }

        $command = 'node ' . escapeshellarg($nodeScript) . ' ' . escapeshellarg($tmpHtmlFile) . ' ' . escapeshellarg($tmpPdfFile) . ' 2>&1';

        $output = [];
        $returnVar = 0;
        exec($command, $output, $returnVar);

        // Verificar y Servir el PDF
        if ($returnVar === 0 && file_exists($tmpPdfFile) && filesize($tmpPdfFile) > 0) {
            header('Content-Type: application/pdf');
            header('Content-Disposition: inline; filename="documento_cdp.pdf"');
            header('Content-Length: ' . filesize($tmpPdfFile));

            readfile($tmpPdfFile);

            // Limpieza de archivos temporales
            if (file_exists($tmpHtmlFile)) unlink($tmpHtmlFile);
            if (file_exists($tmpPdfFile)) unlink($tmpPdfFile);
        } else {
            echo "Hubo un error generando el PDF.<br>";
            echo "<pre>" . print_r($output, true) . "</pre>";
            if (file_exists($tmpHtmlFile)) unlink($tmpHtmlFile);
            if (file_exists($tmpPdfFile)) unlink($tmpPdfFile);
        }
    }
}
