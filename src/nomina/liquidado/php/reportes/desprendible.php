<?php

use Src\Common\Php\Clases\Imprimir;
use Src\Common\Php\Clases\Reportes as CReportes;
use Src\Common\Php\Clases\Valores;
use Src\Nomina\Liquidacion\Php\Clases\Nomina;
use Src\Nomina\Liquidado\Php\Clases\Detalles;
use Src\Usuarios\Login\Php\Clases\Usuario;

session_start();
if (!isset($_SESSION['user'])) {
    header("Location: ../../../../../index.php");
    exit();
}

include_once '../../../../../config/autoloader.php';
$datos = isset($_POST['id']) ? explode('|', base64_decode($_POST['id'])) : exit('Acceso denegado');
$id_empleado = $datos[0];
$id_nomina   = $datos[1];

$documento  = " Desprendible de Nomina";
$usuario    = new Usuario();
$empresa    = $usuario->getEmpresa();
$nomina     = Nomina::getRegistro($id_nomina);
$mes        = mb_strtoupper(Valores::NombreMes($nomina['mes']));
$otro       = "NÓMINA No. {$id_nomina} - MES: {$mes} - VIGENCIA: {$nomina['vigencia']}";

$liquidado  = (new Detalles())->getRegistrosDT(1, -1, ['id_empleado' => $id_empleado, 'id_nomina' => $id_nomina], 1, 'ASC');
$filtrados = array_filter($liquidado, function ($valor) {
    if (!is_numeric($valor)) {
        return true;
    }
    return $valor > 0;
});
$body = '';
foreach ($filtrados as $key => $value) {
    $body .= "<tr><td>{$key}</td><td>{$value}</td></tr>";
}
$html =
    <<<HTML
    <table border='1' cellpadding='3' cellspacing='1' style='width: 100%; border-collapse: collapse; border: none !important;'>
        <tbody>
            {$body}
        </tbody>
    </table>
    HTML;

$firmas = (new CReportes())->getFormFirmas(['nom_tercero' => $nomina['elabora'], 'cargo' => $nomina['cargo']], 51, $nomina['vigencia'] . '-' . $nomina['mes'] . '-01', '');

$Imprimir = new Imprimir($documento, "letter");
$Imprimir->addEncabezado($documento, $otro);
$Imprimir->addContenido($html);
$Imprimir->addFirmas($firmas);
$pdf = isset($_POST['pdf']) ? filter_var($_POST['pdf'], FILTER_VALIDATE_BOOLEAN) : false;
$resul = $Imprimir->render($pdf);


if ($pdf) {
    $Imprimir->getPDF($resul);
    exit();
}
