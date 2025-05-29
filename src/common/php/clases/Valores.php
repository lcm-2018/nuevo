<?php

namespace Src\Common\Php\Clases;


class Valores
{
    public function Pesos($valor)
    {
        return '$ ' . number_format($valor, 2, ',', '.');
    }

    public static function WordToNumber($valor)
    {
        $number = str_replace(',', '', $valor);
        return floatval($number);
    }
}
