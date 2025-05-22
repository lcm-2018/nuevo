<?php

namespace Src\Common\Php\Clases;


class Valores
{
    public function Pesos($valor)
    {
        return '$ ' . number_format($valor, 2, ',', '.');
    }
}
