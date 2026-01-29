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
$documento  = "Solicitud de CDP";
$nomina     = Nomina::getRegistro($id_nomina);
$mes        = mb_strtoupper(Valores::NombreMes($nomina['mes']));

$conexion   = Conexion::getConexion();
$datos      = (new Detalles())->getRegistrosDT(1, -1, ['id_nomina' => $id_nomina], 1, 'ASC');

$usuario    = new Usuario();
$empresa    = $usuario->getEmpresa();
$rubros     = (new Rubros)->getRubros2();

try {
    $conexion->beginTransaction();
    $Reportes = new Reportes($conexion);
    $del = $Reportes->delRegsitros($id_nomina, 'M');
    if ($del != 'si') {
        throw new Exception($del);
    }

    $count = 0;
    $data = ['id_nomina' => $id_nomina, 'tipo' => 'M'];

    $tipo_field_map = [
        1  => ['valor_laborado', 'val_compensa'],
        2  => 'horas_ext',
        3  => 'g_representa',
        4  => 'val_bon_recrea',
        5  => 'val_bsp',
        6  => 'aux_tran',
        7  => 'aux_alim',
        9  => 'val_indemniza',
        10 => 'valor_luto',
        17 => 'valor_vacacion',
        18 => 'val_cesantias',
        19 => 'val_icesantias',
        20 => 'val_prima_vac',
        21 => 'valor_pv',
        22 => 'valor_ps',
        32 => 'pago_empresa'
    ];
    foreach ($datos as $d) {

        foreach ($rubros as $rb) {
            $tipo = $rb['id_tipo'];
            $rubro = $d['tipo_cargo'] == '1' ? $rb['r_admin'] : $rb['r_operativo'];

            $valorCdp = 0;

            if (isset($tipo_field_map[$tipo])) {
                $fields = $tipo_field_map[$tipo];
                if (is_array($fields)) {
                    foreach ($fields as $f) {
                        if (!empty($d[$f])) {
                            $valorCdp += $d[$f];
                        }
                    }
                } else {
                    $valorCdp = !empty($d[$fields]) ? $d[$fields] : 0;
                }
            }

            if ($valorCdp > 0) {
                $data['rubro'] = $rubro;
                $data['valor'] = $valorCdp;
                $res = $Reportes->addRegistro($data);
                if ($res != 'si') {
                    throw new Exception($res);
                }
            }
        }
        $count++;
    }
    $conexion->commit();
} catch (Exception $e) {
    $conexion->rollBack();
    exit($e->getMessage());
}
$obj = $Reportes->getRegistros(['id_nomina' => $id_nomina, 'tipo' => 'M']);
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
            <th colspan='2' style='text-align: justify; border: none !important;'>{$nomina['descripcion']}</th>
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
