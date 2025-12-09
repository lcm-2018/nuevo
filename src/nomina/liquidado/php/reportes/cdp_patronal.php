<?php

use Config\Clases\Conexion;
use Src\Common\Php\Clases\Imprimir;
use Src\Common\Php\Clases\Reportes as CReportes;
use Src\Common\Php\Clases\Valores;
use Src\Nomina\Configuracion\Php\Clases\Rubros;
use Src\Nomina\Liquidacion\Php\Clases\Nomina;
use Src\Nomina\Liquidado\Php\Clases\Detalles;
use Src\Nomina\Liquidado\Php\Clases\Reportes;
use Src\Usuarios\Login\Php\Clases\Usuario;

session_start();
if (!isset($_SESSION['user'])) {
    header("Location: ../../../../../index.php");
    exit();
}

include_once '../../../../../config/autoloader.php';
$id_nomina  = isset($_POST['id']) ? intval($_POST['id']) : exit('Acceso Denegado');
$documento  = "Solicitud de CDP Patronal";
$nomina     = Nomina::getRegistro($id_nomina);
$mes        = mb_strtoupper(Valores::NombreMes($nomina['mes']));


$conexion   = Conexion::getConexion();
$datos      = (new Detalles())->getAporteSocial($id_nomina);

$sumas = [];

foreach ($datos as $row) {
    $sumas[$row['cargo']][$row['tipo']] = ($sumas[$row['cargo']][$row['tipo']] ?? 0) + $row['valor'];
}

$admin = $sumas['admin'] ?? [];
$oper  = $sumas['oper'] ?? [];

$usuario    = new Usuario();
$empresa    = $usuario->getEmpresa();
$rubros     = (new Rubros)->getRubros2();

try {
    $conexion->beginTransaction();
    $Reportes = new Reportes($conexion);
    $del = $Reportes->delRegsitros($id_nomina, 'PL');
    if ($del != 'si') {
        throw new Exception($del);
    }

    $count = 0;
    $data  = ['id_nomina' => $id_nomina, 'tipo' => 'PL'];

    // Mapa entre id_tipo → clave usada en admin/oper
    $map = [
        11 => 'caja',
        12 => 'eps',
        13 => 'arl',
        14 => 'afp',
        15 => 'icbf',
        16 => 'sena'
    ];

    foreach ($rubros as $rb) {

        $key = $map[$rb['id_tipo']] ?? null;

        if (!$key) {
            continue;
        }

        $valAdmin = $admin[$key] ?? 0;
        $valOper  = $oper[$key]  ?? 0;

        if ($valAdmin > 0) {
            $data['rubro'] = $rb['r_admin'];
            $data['valor'] = $valAdmin;

            if ($Reportes->addRegistro($data) !== 'si') {
                throw new Exception("Error guardando ADMIN para {$key}");
            }
        }

        if ($valOper > 0) {
            $data['rubro'] = $rb['r_operativo'];
            $data['valor'] = $valOper;

            if ($Reportes->addRegistro($data) !== 'si') {
                throw new Exception("Error guardando OPER para {$key}");
            }
        }
    }
    $conexion->commit();
} catch (Exception $e) {
    $conexion->rollBack();
    exit($e->getMessage());
}
$obj = $Reportes->getRegistros(['id_nomina' => $id_nomina, 'tipo' => 'PL']);
$body = '';
foreach ($obj as $codigo => $datos) {
    $body .= "
            <tr>
                <td style='text-align: left;'>{$codigo}</td>
                <td>{$datos['nombre']}</td>
                <td style='text-align: right;'>" . number_format($datos['valor'], 2) . "</td>
            </tr>";
}
$html =
    <<<HTML
    <table border='1' cellpadding='3' cellspacing='1' style='width: 100%; border-collapse: collapse; border: none !important;'>
        <tr>
            <th style='text-align: left; aling-middle: center; border: none !important;'>OBJETO:</th>
            <th colspan='2' style='text-align: justify; border: none !important;'>{$nomina['descripcion']} N° {$id_nomina} (PATRONAL) MES DE {$mes} VIGENCIA {$nomina['vigencia']}, ADMINISTRATIVO - ASISTENCIAL, EMPLEADOS ADSCRITOS A {$empresa['nombre']}.</th>
        </tr>
        <tr>
            <th style='text-align: center;'>CÓDIGO </th>
            <th style='text-align: center;'>NOMBRE RUBRO</th>
            <th style='text-align: center;'>VALOR</th>
        </tr>
        <tbody>
            {$body}
        </tbody>
    </table>
    HTML;

$firmas = (new CReportes())->getFormFirmas(['nom_tercero' => $nomina['elabora'], 'cargo' => $nomina['cargo']], 51, $nomina['vigencia'] . '-' . $nomina['mes'] . '-01', '');

$Imprimir = new Imprimir($documento, "letter");
$Imprimir->addEncabezado($documento);
$Imprimir->addContenido($html);
$Imprimir->addFirmas($firmas);
$pdf = isset($_POST['pdf']) ? filter_var($_POST['pdf'], FILTER_VALIDATE_BOOLEAN) : false;
$resul = $Imprimir->render($pdf);


if ($pdf) {
    $Imprimir->getPDF($resul);
    exit();
}
