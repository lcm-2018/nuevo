<?php

namespace Src\Common\Php\Clases;


class Valores
{
    public function Pesos($valor)
    {
        return '$ ' . number_format($valor, 2, ',', '.');
    }

    /**
     * Convierte un valor en texto a un número.
     * @param string $valor El valor en texto a convertir.
     * @return float El valor convertido a número.
     */
    public static function WordToNumber($valor)
    {
        $number = str_replace(',', '', $valor);
        return floatval($number);
    }

    /**
     * Redondea un número al múltiplo más cercano especificado.
     * @param float $numero El número a redondear.
     * @param float $multiplo El múltiplo al cual redondear. Por defecto es 1. 1: Unidades, 10: Decenas, 100: Centenas, etc.
     * @return float El número redondeado al múltiplo más cercano.
     */
    public static function Redondear($numero, $multiplo = 1)
    {
        return ceil($numero / $multiplo) * $multiplo;
    }

    //metodo para formatear un numero a 2 decimales solo con . decimal 
    public static function formatNumber($number)
    {
        return number_format((float)$number, 2, '.', '');
    }

    public static function TextFormat($string)
    {
        return trim(preg_replace(['/[.,-]/', '/\r|\n/', '/\s+/'], ['', ' ', ' '], $string));
    }
}
