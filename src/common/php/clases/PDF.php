<?php

namespace Src\Common\Php\Clases;

require '../../../../../vendor/autoload.php';


use Dompdf\Dompdf;
use Dompdf\Options;

class PDF
{
    private $dompdf;

    public function __construct()
    {
        // Configurar opciones (importante para imágenes externas como firmas)
        $options = new Options();
        $options->set('isRemoteEnabled', true);
        $options->set('isHtml5ParserEnabled', true);

        $this->dompdf = new Dompdf($options);
        $this->dompdf->setPaper('letter', 'portrait'); // Tamaño y orientación
    }

    public function loadHtml($html)
    {
        $this->dompdf->loadHtml($html);
    }

    public function render()
    {
        $this->dompdf->render();
    }

    public function stream($filename)
    {
        $this->dompdf->stream($filename, ["Attachment" => false]);
    }

    public function output()
    {
        return $this->dompdf->output();
    }
}
