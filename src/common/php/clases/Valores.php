<?php

namespace Src\Common\Php\Clases;

use NumberFormatter;

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

    public static function NombreMes($mes)
    {
        $meses = [
            1 => 'Enero',
            2 => 'Febrero',
            3 => 'Marzo',
            4 => 'Abril',
            5 => 'Mayo',
            6 => 'Junio',
            7 => 'Julio',
            8 => 'Agosto',
            9 => 'Septiembre',
            10 => 'Octubre',
            11 => 'Noviembre',
            12 => 'Diciembre'
        ];

        return $meses[intval($mes)] ?? '';
    }

    /**
     * Calcula el plazo entre dos fechas y lo retorna en formato legible.
     * Si los días son >= 29, se redondea a un mes adicional.
     * 
     * @param string $fecha_inicio Fecha de inicio en formato 'Y-m-d'
     * @param string $fecha_fin Fecha de fin en formato 'Y-m-d'
     * @return string El plazo en formato "X MESES Y Y DÍAS" o cadena vacía si las fechas son iguales
     */
    public static function calcularPlazo($fecha_inicio, $fecha_fin)
    {
        if (empty($fecha_inicio) || empty($fecha_fin)) {
            return '';
        }

        $start = new \DateTime($fecha_inicio);
        $end = new \DateTime($fecha_fin);
        $plazo = $start->diff($end);

        $p_mes = (int)$plazo->format('%m');
        $p_dia = (int)$plazo->format('%d');

        // Si los días son >= 29, se redondea a un mes adicional
        if ($p_dia >= 29) {
            $p_mes++;
            $p_dia = 0;
        }

        $letras = new NumberFormatter("es", NumberFormatter::SPELLOUT);

        // Formatear meses
        $texto_meses = '';
        if ($p_mes < 1) {
            $texto_meses = '';
        } else if ($p_mes == 1) {
            $texto_meses = 'UN (01) MES';
        } else {
            $texto_meses = mb_strtoupper($letras->format($p_mes)) . ' (' . str_pad($p_mes, 2, '0', STR_PAD_LEFT) . ') MESES';
        }

        // Formatear días
        $y = ' Y ';
        $texto_dias = '';
        if ($p_dia < 1) {
            $y = '';
            $texto_dias = '';
        } else if ($p_dia == 1) {
            $texto_dias = 'UN DÍA';
        } else {
            $texto_dias = mb_strtoupper($letras->format($p_dia)) . ' (' . str_pad($p_dia, 2, '0', STR_PAD_LEFT) . ') DÍAS';
        }

        // Retornar el plazo formateado
        return $texto_meses == '' ? $texto_dias : $texto_meses . $y . $texto_dias;
    }

    /**
     * Convierte una fecha en formato Y-m-d a texto legible en español.
     * Ejemplos:
     * - mayusculas: "PRIMERO (01) DE ENERO DE 2024"
     * - minusculas: "primero (01) de enero de 2024"
     * 
     * @param string $fecha Fecha en formato 'Y-m-d'
     * @param bool $mayusculas Si es true retorna en MAYÚSCULAS, si es false en minúsculas
     * @return string La fecha convertida a letras
     */
    public static function fechaEnLetras($fecha, $mayusculas = false)
    {
        if (empty($fecha)) {
            return '';
        }

        $partes = explode('-', $fecha);
        $anio = $partes[0];
        $mes = intval($partes[1]);
        $dia = $partes[2];

        $letras = new NumberFormatter("es", NumberFormatter::SPELLOUT);

        // Convertir el día a letras (caso especial para el día 01)
        $dia_texto = $dia == '01' ? 'PRIMERO' : mb_strtoupper($letras->format(intval($dia)));

        // Obtener el nombre del mes usando el método existente
        $nombre_mes = mb_strtolower(self::NombreMes($mes));

        // Construir la fecha completa
        $fecha_letras = $dia_texto . ' (' . $dia . ') DE ' . mb_strtoupper($nombre_mes) . ' DE ' . $anio;

        // Retornar en mayúsculas o minúsculas según el parámetro
        return $mayusculas ? $fecha_letras : mb_strtolower($fecha_letras);
    }

    /**
     * Convierte un string delimitado en un array de objetos con una clave específica.
     * Útil para convertir datos almacenados como: "valor1||valor2||valor3"
     * 
     * Ejemplo:
     * stringToArrayObjects("item1||item2||item3", "nombre", "||")
     * Retorna: [['nombre' => 'item1'], ['nombre' => 'item2'], ['nombre' => 'item3']]
     * 
     * @param string $string El string delimitado a convertir
     * @param string $keyName El nombre de la clave para cada objeto en el array
     * @param string $delimiter El delimitador usado (por defecto '||')
     * @return array Array de objetos asociativos
     */
    public static function stringToArrayObjects($string, $keyName, $delimiter = '||')
    {
        if (empty($string)) {
            return [];
        }

        $items = explode($delimiter, $string);
        $result = [];

        foreach ($items as $item) {
            // Limpiar espacios
            $item = trim($item);
            // Solo agregar si no está vacío
            if ($item !== '') {
                $result[] = [$keyName => $item];
            }
        }

        return $result;
    }

    public static function LetrasCOP($numero)
    {
        $numero = number_format((float)$numero, 2, '.', '');
        list($entero, $decimal) = explode('.', $numero);

        $letras = self::numeroALetras((int)$entero);

        // Limpiar espacios
        $letras = trim($letras);

        // Agregar "de" después de "millón" o "millones" si termina exactamente con esa palabra
        // Solo para millones exactos (no para "millón quinientos mil")
        // mb_substr maneja correctamente la tilde en "millón"
        if (mb_substr($letras, -6) === 'millón' || mb_substr($letras, -8) === 'millones') {
            $letras .= ' de';
        }

        // Peso o pesos
        $letras .= ((int)$entero == 1) ? ' peso' : ' pesos';

        // Centavos (solo si existen y son diferentes de 00)
        if ((int)$decimal > 0) {
            $letras .= ' con ' . self::numeroALetras((int)$decimal);
            $letras .= ((int)$decimal == 1) ? ' centavo' : ' centavos';
        }

        return trim($letras) . ' m/cte';
    }

    private static function numeroALetras($numero)
    {
        $formatter = new NumberFormatter("es", NumberFormatter::SPELLOUT);
        $texto = $formatter->format($numero);

        // Ajustes de idioma para Colombia
        $reemplazos = [
            'uno' => 'un',
            'veintiuno' => 'veintiún',
            'veintiún ' => 'veintiún ',
            'un mil' => 'mil',
        ];

        foreach ($reemplazos as $buscar => $reemplazar) {
            $texto = str_replace($buscar, $reemplazar, $texto);
        }

        // Si el texto es exactamente "millón", agregar "un" al inicio
        if (trim($texto) === 'millón' || trim($texto) === 'millon') {
            $texto = 'un ' . trim($texto);
        }

        return $texto;
    }
}
