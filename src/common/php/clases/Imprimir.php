<?php

namespace Src\Common\Php\Clases;

use Config\Clases\Plantilla;
use Src\Usuarios\Login\Php\Clases\Usuario;


class Imprimir
{
    public function __construct() {}

    public static function getEncabezado()
    {
        $usuario = new Usuario();
        $empresa = $usuario->getEmpresa();
        $host = Plantilla::getHost();
        $html =
            <<<HTML
            <table class="table-bordered bg-light" style="width:100% !important;">
                <tr>
                    <td class='text-center' style="width:18%">
                        <label class="small"><img src="{$host}/images/logos/logo.png" width="100"></label>
                    </td>
                    <td style="text-align:center">
                        <strong>{$empresa['nombre']}</strong>
                        <div>NIT {$empresa['nit']}-{$empresa['dv']}</div>
                    </td>
                </tr>
            </table>
            HTML;
        return $html;
    }
}
