<?php

$user = new \Src\Usuarios\Login\Php\Clases\Usuario();
$reportes = new \Src\Common\Php\Clases\Reportes($cmd);

try {
    $empresa = $user->getEmpresa();
} catch (PDOException $e) {
    echo $e->getCode() == 2002 ? 'Sin Conexión a Mysql (Error: 2002)' : 'Error: ' . $e->getCode();
}
try {
    $responsables = $reportes->getFirmas($id_modulo, $fecha, $doc_fte);

    // Obtener primer elemento para campos generales del maestro/fuente
    $primer_resp = !empty($responsables) ? reset($responsables) : [];

    $nom_respon = isset($responsables[4]) ? $responsables[4]['nom_tercero'] : '';
    $cargo_respon = isset($responsables[4]) ? $responsables[4]['cargo'] : '';
    $gen_respon = isset($responsables[4]) ? $responsables[4]['genero'] : '';
    $control = isset($primer_resp['control_doc']) ? $primer_resp['control_doc'] : '';
    $control = !empty($control) && intval($control) !== 0;

    $ver_costos = isset($primer_resp['costos']) ? ($primer_resp['costos'] != 1) : true;
    $ver_acumula = isset($primer_resp['acumula']) ? ($primer_resp['acumula'] == 1) : false;
    $ver_tercero = isset($primer_resp['ver_tercero']) ? $primer_resp['ver_tercero'] : 0;
} catch (PDOException $e) {
    echo $e->getCode() == 2002 ? 'Sin Conexión a Mysql (Error: 2002)' : 'Error: ' . $e->getCode();
}
$where = '';
if ($ver_acumula) {
    $where = "AND `pto_cargue`.`tipo_dato` = 1";
}
$html = <<<HTML
<style>
    /* Estilos para la pantalla */
    .watermark {
        position: absolute;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%) rotate(-45deg);
        /* Se añade rotación */
        font-size: 100px;
        color: rgba(255, 0, 0, 0.2);
        /* Cambia la opacidad para que sea tenue */
        z-index: 1000;
        pointer-events: none;
        /* Para que no interfiera con el contenido */
        white-space: nowrap;
        /* Evita que el texto se divida en varias líneas */
    }

    /*
    estilos para todas las tablas a imprimir, collapse y borde de color gris que tengan la clase bordes
    */

    .bordeado {
        border-collapse: collapse;
    }

    .bordeado th,
    .bordeado td {
        border: 1px solid #ccc;
        padding-left: 5px;
        padding-right: 5px;
    }

    /* Estilos específicos para la impresión */
    @media print {

        body {
            position: relative;
            margin: 0;
            padding: 0;
        }

        .watermark {
            position: fixed;
            /* Cambiar a 'fixed' para impresión */
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%) rotate(-45deg);
            font-size: 100px;
            color: rgba(255, 0, 0, 0.2);
            /* Asegura que el color y opacidad se mantengan */
            z-index: -1;
            /* Colocar detrás del contenido impreso */
        }

        table {
            width: 100%;
            border-collapse: collapse;
            page-break-inside: auto;
        }

        thead {
            display: table-header-group;
        }

        tfoot {
            display: table-footer-group;
        }

        tbody {
            display: table-row-group;
        }

        tfoot tr {
            page-break-inside: avoid;
            padding-bottom: 50px;
            width: 100%;
            text-align: center;
        }

        tr {
            page-break-inside: avoid;
        }
    }
</style>
<table style="width:100% !important;" border="0">
    <tr>
        <td style="width:25%">
            <label>
                <img src="../../assets/images/logo.png" width="150">
            </label>
        </td>
        <td style="text-align:center">
            <strong>{$empresa['nombre']}</strong>
            <div>NIT {$empresa['nit']} - {$empresa['dig_ver']}</div>
        </td>
    </tr>
</table>
HTML;


$headtb = $nom_respon != '' ?
    '<tr style="text-align:left">
    <td style="width:33%">
        <strong>Elaboró:</strong>
    </td>
    <td style="width:33%">
        <strong>Revisó:</strong>
    </td>
    <td style="width:34%">
        <strong>Aprobó:</strong>
    </td>
</tr>' : '';
$bordes = $nom_respon != '' ? 'table-bordered' : '';
$lineas = '<div>_______________________</div>';

// Variables para la primera columna (Elaboró)
$elaboro_nombre = isset($cdp['usuario_act']) && trim($cdp['usuario_act']) != '' ? $cdp['usuario_act'] : (isset($cdp['usuario']) ? $cdp['usuario'] : '');
$elaboro_cargo = isset($cdp['cargo']) ? $cdp['cargo'] : '';
$prepara_nombre = isset($responsables[1]) ? $responsables[1]['nom_tercero'] : '';
$prepara_cargo = isset($responsables[1]) ? $responsables[1]['cargo'] : '';
$elaboro_cargo = !empty($prepara_cargo) ? $prepara_cargo : $elaboro_cargo;
$elaboro_nombre = !empty($prepara_nombre) ? $prepara_nombre : $elaboro_nombre;

// Variables para la segunda columna (Revisó - tipo_control 2)
$reviso_nombre = isset($responsables[2]) ? $responsables[2]['nom_tercero'] : '';
$reviso_cargo = isset($responsables[2]) ? $responsables[2]['cargo'] : '';

// Variables para la tercera columna (Aprobó - tipo_control 3)
$aprobo_nombre = isset($responsables[3]) ? $responsables[3]['nom_tercero'] : '';
$aprobo_cargo = isset($responsables[3]) ? $responsables[3]['cargo'] : '';

// Determinar qué firmas tienen datos
$firmas_activas = [];
if (!empty($elaboro_nombre)) {
    $firmas_activas[] = [
        'titulo' => 'Elaboró:',
        'nombre' => $elaboro_nombre,
        'cargo' => $elaboro_cargo
    ];
}
if (!empty($reviso_nombre)) {
    $firmas_activas[] = [
        'titulo' => 'Revisó:',
        'nombre' => $reviso_nombre,
        'cargo' => $reviso_cargo
    ];
}
if (!empty($aprobo_nombre)) {
    $firmas_activas[] = [
        'titulo' => 'Aprobó:',
        'nombre' => $aprobo_nombre,
        'cargo' => $aprobo_cargo
    ];
}

$table = '';
if ($control && count($firmas_activas) > 0) {
    // Calcular el ancho de cada columna
    $num_firmas = count($firmas_activas);
    $ancho_columna = floor(100 / $num_firmas);

    // Construir encabezados solo si hay un responsable principal (nom_respon no vacío)
    $headtb_dinamico = '';
    if ($nom_respon != '') {
        $headtb_dinamico = '<tr style="text-align:left">';
        foreach ($firmas_activas as $firma) {
            $headtb_dinamico .= "<td style=\"width:{$ancho_columna}%\"><strong>{$firma['titulo']}</strong></td>";
        }
        $headtb_dinamico .= '</tr>';
    }

    // Construir celdas solo para firmas activas
    $celdas_dinamicas = '<tr style="text-align:center">';
    foreach ($firmas_activas as $firma) {
        $celdas_dinamicas .= "
                <td>
                    <br><br>
                    {$lineas}
                    <div>{$firma['nombre']}</div>
                    <div>{$firma['cargo']}</div>
                </td>";
    }
    $celdas_dinamicas .= '</tr>';

    $table = <<<HTML
        <table class="{$bordes} bg-light" style="width:100% !important;font-size: 10px;">
            {$headtb_dinamico}
            {$celdas_dinamicas}
        </table>
HTML;
}
$terRecibeCell = '';
// Mostrar tercero solo cuando ver_tercero = 1
if ($ver_tercero == 1) {
    $terRecibeCell = "
        <td style=\"width:50%; text-align:center; vertical-align:top;\">
            <div>___________________________________</div>
            <div>$tercero</div>
            <div>RECIBE CC/NIT:</div>
        </td>";
    $responsableWidth = "50%";
} else {
    $responsableWidth = "100%";
}

$firmas =
    <<<HTML
        <div style="text-align: center; padding-top: 60px; font-size: 13px;">
            <table style="width:100%; border-collapse: collapse;">
                <tr>
                    <td style="width:{$responsableWidth}; text-align:center; vertical-align:top;">
                        <div>___________________________________</div>
                        <div>{$nom_respon}</div>
                        <div>{$cargo_respon}</div>
                    </td>
                    {$terRecibeCell}
                </tr>
            </table>
        </div>
        <div style="text-align: center; padding-top: 25px;">
            {$table}
        </div>
    HTML;
