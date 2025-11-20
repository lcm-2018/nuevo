<?php
session_start();
if (!isset($_SESSION['user'])) {
    header("Location: ../../../../../index.php");
    exit();
}

$action = isset($_POST['action']) ? $_POST['action'] : exit('Acceso no permitido');
$id = $_POST['id'] ?? null;
$id_nomina = $_POST['id_nomina'] ?? null;
$item = $_POST['item'] ?? 1;

include_once '../../../../../config/autoloader.php';

use Config\Clases\Conexion;
use Src\Nomina\Empleados\Php\Clases\Bsp;
use Src\Nomina\Empleados\Php\Clases\Cesantias;
use Src\Nomina\Empleados\Php\Clases\Empleados;
use Src\Nomina\Empleados\Php\Clases\Licencias_MoP;
use Src\Nomina\Empleados\Php\Clases\Primas;
use Src\Nomina\Empleados\Php\Clases\Retenciones;
use Src\Nomina\Empleados\Php\Clases\Seguridad_Social;
use Src\Nomina\Empleados\Php\Clases\Vacaciones;
use Src\Nomina\Empleados\Php\Clases\Valores_Liquidacion;
use Src\Nomina\Liquidacion\Php\Clases\Anulacion;
use Src\Nomina\Liquidacion\Php\Clases\Liquidacion;
use Src\Nomina\Liquidacion\Php\Clases\Otros;
use Src\Nomina\Liquidado\Php\Clases\Detalles;

$Detalles = new Detalles();

$res['status'] = ' error';
$res['msg'] = '';
switch ($action) {
    case 'form':
        $res['status'] = 'ok';
        $res['msg'] = $Detalles->getFormulario($id, $id_nomina, $item);
        break;
    case 'add':

        break;
    case 'edit':
        $option = $_POST['option'];
        $suma = 0;
        $valida         = false;
        $id_empleado    = $_POST['id_empleado'];
        $conexion = Conexion::getConexion();
        $conexion->beginTransaction();
        switch ($option) {
            case 1:
                $Otros          = new Otros($conexion);
                $datos          = $Otros->getRegistroLiq($_POST);
                $id             = $datos['id'];
                if ($id > 0 && ($datos['dias'] != $_POST['dias_lab'] || $datos['val_laborado'] != $_POST['valor_laborado'] || $datos['val_auxtrans'] != $_POST['aux_tran'] || $datos['auxalim'] != $_POST['alimentacion'] || $datos['grepre'] != $_POST['g_representa'])) {
                    $data = [
                        'id'         => $id,
                        'dias'       => $_POST['dias_lab'],
                        'laborado'   => $_POST['valor_laborado'],
                        'auxtrans'   => $_POST['aux_tran'],
                        'auxalim'    => $_POST['alimentacion'],
                        'grepre'     => $_POST['g_representa'],
                        'tipo'       => 'M'
                    ];
                    $rspt = $Otros->editRegistroLabliq($data);
                    if ($rspt == 'si') {
                        $suma++;
                    } else if ($rspt != 'no') {
                        $conexion->rollBack();
                        exit(json_encode($rspt));
                    }
                }
                $Licencia      = new Licencias_MoP($conexion);
                $datos         = $Licencia->getRegistroLiq($_POST);
                $id            = $datos['id'];
                if ($id > 0 && ($datos['valor'] != $_POST['valor_mp'])) {
                    $data = [
                        'id'         => $id,
                        'valor'      => $_POST['valor_mp'],
                        'tipo'       => 'M'
                    ];
                    $lcmp = $Licencia->editRegistroLiq($data);
                    if ($lcmp == 'si') {
                        $suma++;
                    } else if ($lcmp != 'no') {
                        $conexion->rollBack();
                        exit(json_encode($lcmp));
                    }
                }
                if ($suma > 0) {
                    $Anular  = new Anulacion($conexion);
                    $resul = $Anular->anulaRegistros($id_empleado, $_POST['id_nomina']);
                    if ($resul == 'si') {
                        $suma++;
                        //echo 'Anulado liquidación.';
                    } else if ($resul != 'no') {
                        if ($conexion->inTransaction()) {
                            $conexion->rollBack();
                        }
                        exit(json_encode($resul));
                    }
                    if ($suma > 0) {
                        $Liquidacion = new Liquidacion($conexion);
                        $array = [
                            'chk_liquidacion' => [0  => $id_empleado],
                            'id_contrato'     => [$id_empleado => $_POST['id_contrato']],
                            'lab'             => [$id_empleado => $_POST['dias_lab']],
                            'metodo'          => [$id_empleado => $_POST['metodo_pago']],
                            'tipo'            => 2,
                            'mes'             => $_POST['mes'],
                        ];
                        $rstd = $Liquidacion->addRegistro($array, 1);
                        if ($rstd == 'Liquidación realizada con éxito.') {
                            $suma++;
                        } else {
                            $conexion->rollBack();
                            exit(json_encode($rstd));
                        }
                    }
                }
                break;
            case 2:
                $id             = (new Valores_Liquidacion($conexion))->getRegistroLiq($_POST);
                $_POST['id']    = $id;
                $val_liq        = (new Valores_Liquidacion($conexion))->editRegistroLiq($_POST);
                if ($val_liq == 'si') {
                    $suma++;
                    //echo 'Actualizado valores de liquidación.';
                } else if ($val_liq != 'no') {
                    $conexion->rollBack();
                    exit(json_encode($val_liq));
                }
                //primas
                $Primas         = new Primas($conexion);
                if ($_POST['valor_ps'] >= 0) {
                    $id             = $Primas->getRegistroLiq1($_POST);
                    $data           = [
                        'id_empleado'   => $id_empleado,
                        'cant_dias'     => $_POST['dias_ps'],
                        'val_liq_ps'    => $_POST['valor_ps'],
                        'val_liq_pns'   => 0,
                        'periodo'       => 1,
                        'corte'         => $_POST['corte_ps'],
                        'id_nomina'     => $_POST['id_nomina'],
                        'tipo'          => 'P',
                        'id'            => $id,
                    ];
                    if ($id == 0 && $_POST['valor_ps'] > 0) {
                        $Servicio       = $Primas->addRegistroLiq1($data);
                    } else {
                        $Servicio       = $Primas->editRegistroLiq1($data);
                    }
                    if ($Servicio == 'si') {
                        $suma++;
                        //echo 'Actualizado prima de servicio.';
                    } else if ($Servicio != 'no') {
                        $conexion->rollBack();
                        exit(json_encode($Servicio));
                    }
                }
                if ($_POST['valor_pv'] >= 0) {
                    $id             = $Primas->getRegistroLiq2($_POST);
                    $data           = [
                        'id_empleado'   => $id_empleado,
                        'cant_dias'     => $_POST['dias_pn'],
                        'val_liq_pv'    => $_POST['valor_pv'],
                        'val_liq_pnv'   => 0,
                        'periodo'       => 2,
                        'corte'         => $_POST['corte_pn'],
                        'id_nomina'     => $_POST['id_nomina'],
                        'id'            => $id,
                        'tipo'          => 'P',
                    ];
                    if ($id == 0 && $_POST['valor_pv'] > 0) {
                        $Navidad       = $Primas->addRegistroLiq2($data);
                    } else {
                        $Navidad       = $Primas->editRegistroLiq2($data);
                    }
                    if ($Navidad == 'si') {
                        $suma++;
                        //echo 'Actualizado prima de navidad.';
                    } else if ($Navidad != 'no') {
                        $conexion->rollBack();
                        exit(json_encode($Navidad));
                    }
                }
                //Cesantías
                if ($_POST['val_cesantias'] >= 0) {
                    $Cesantias      = new Cesantias($conexion);
                    $id             = $Cesantias->getRegistroLiq($_POST);
                    $data = [
                        'id_empleado'    => $id_empleado,
                        'cant_dias'      => $_POST['dias_ces'],
                        'val_cesantias'  => $_POST['val_cesantias'],
                        'val_icesantias' => $_POST['val_icesantias'],
                        'corte'          => $_POST['corte_ces'],
                        'id_nomina'      => $_POST['id_nomina'],
                        'tipo'           => 'M',
                        'id'             => $id
                    ];
                    if ($id == 0 && $_POST['val_cesantias'] > 0) {
                        $ces          = $Cesantias->addRegistroLiq($data);
                    } else {
                        $ces          = $Cesantias->editRegistroLiq($data);
                    }
                    if ($ces == 'si') {
                        $suma++;
                        //echo 'Actualizado cesantías.';
                    } else if ($ces != 'no') {
                        $conexion->rollBack();
                        exit(json_encode($ces));
                    }
                }
                //BSP
                $BSP            = new Bsp($conexion);
                $data           = $BSP->getRegistroLiq($_POST);
                $id             = $data['id'];
                $val            = $data['valor'];
                if ($id > 0 && $val != $_POST['valor_bsp']) {
                    $data = [
                        'numValor'      => $_POST['valor_bsp'],
                        'datFecCorte'   => $_POST['corte_bsp'],
                        'tipo'          => 'M',
                        'id'            => $id
                    ];
                    $bsp            = $BSP->editRegistro($data);
                    if ($bsp == 'si') {
                        $suma++;
                        //echo 'Actualizado bonificación de servicios.';
                        $valida = true;
                    } else if ($bsp != 'no') {
                        $conexion->rollBack();
                        exit(json_encode($bsp));
                    }
                } else if ($_POST['valor_bsp'] > 0 && $id == 0) {
                    $data = [
                        'id_empleado' => $id_empleado,
                        'valor'       => $_POST['valor_bsp'],
                        'corte'       => $_POST['corte_bsp'],
                        'id_nomina'   => $_POST['id_nomina'],
                        'tipo'        => 'M',
                    ];
                    $bsp    = $BSP->addRegistro($data);
                    if ($bsp == 'si') {
                        $suma++;
                        //echo 'Agregada bonificación de servicios.';
                        $valida = true;
                    } else {
                        $conexion->rollBack();
                        exit(json_encode($bsp));
                    }
                }
                //Vacaciones
                $Vacaciones     = new Vacaciones($conexion);
                $data           = $Vacaciones->getRegistroLiq($_POST);
                $id             = $data['id'];
                $valVac         = $data['val_vac'];
                $valPrima       = $data['prima_vac'];
                $valBonif       = $data['bon_recrea'];
                if ($id > 0 && ($valVac != $_POST['valor_vacacion'] || $valPrima != $_POST['val_prima_vac'] || $valBonif != $_POST['val_bon_recrea'])) {
                    $data = [
                        'val_vac'      => $_POST['valor_vacacion'],
                        'prima_vac'    => $_POST['val_prima_vac'],
                        'bon_recrea'   => $_POST['val_bon_recrea'],
                        'tipo'         => 'M',
                        'id'           => $id
                    ];
                    $vac            = $Vacaciones->editRegistroLiq($data);
                    if ($vac == 'si') {
                        $suma++;
                        //echo 'Actualizado vacaciones.';
                        $valida = true;
                    } else if ($vac != 'no') {
                        $conexion->rollBack();
                        exit(json_encode($vac));
                    }
                }
                //Anular
                if ($valida) {
                    $Anular  = new Anulacion($conexion);
                    $resul = $Anular->anulaRegistros($id_empleado, $_POST['id_nomina']);
                    if ($resul == 'si') {
                        $suma++;
                        //echo 'Anulado liquidación.';
                    } else if ($resul != 'no') {
                        if ($conexion->inTransaction()) {
                            $conexion->rollBack();
                        }
                        exit(json_encode($resul));
                    }
                    if ($suma > 0) {
                        $Liquidacion = new Liquidacion($conexion);
                        $array = [
                            'chk_liquidacion' => [0  => $id_empleado],
                            'id_contrato'     => [$id_empleado => $_POST['id_contrato']],
                            'lab'             => [$id_empleado => $_POST['dias_lab']],
                            'metodo'          => [$id_empleado => $_POST['metodo_pago']],
                            'tipo'            => 2,
                            'mes'             => $_POST['mes'],
                        ];
                        $rstd = $Liquidacion->addRegistro($array, 1);
                        if ($rstd == 'Liquidación realizada con éxito.') {
                            $suma++;
                            //echo 'Recalculada liquidación.';
                        } else {
                            $conexion->rollBack();
                            exit(json_encode($rstd));
                        }
                    }
                }
                break;
            case 3:
                $Seguridad_Social = new Seguridad_Social($conexion);
                $id             = $Seguridad_Social->getRegistroLiq($_POST);
                $_POST['id']    = $id;
                $seso            = $Seguridad_Social->editRegistroLiq($_POST);
                if ($seso == 'si') {
                    $suma++;
                } else if ($seso != 'no') {
                    $conexion->rollBack();
                    exit(json_encode($seso));
                }

                $id             = $Seguridad_Social->getRegistroLiq2($_POST);
                $_POST['id']    = $id;
                $pfis            = $Seguridad_Social->editRegistroLiq2($_POST);
                if ($pfis == 'si') {
                    $suma++;
                } else if ($pfis != 'no') {
                    $conexion->rollBack();
                    exit(json_encode($pfis));
                }
                break;
            case 4:
                $id             = (new Retenciones($conexion))->getRegistroLiq($_POST);
                $_POST['id']    = $id;
                $fte            = (new Retenciones($conexion))->editRegistroLiq($_POST);
                if ($fte == 'si') {
                    $suma++;
                } else if ($fte != 'no') {
                    $conexion->rollBack();
                    exit(json_encode($fte));
                }
                break;
            default:
                $res['msg'] = 'Tipo no válido.';
                break;
        }
        if ($suma > 0) {
            $res['status'] = 'ok';
            $conexion->commit();
        } else {
            $conexion->rollBack();
            $res['msg'] = 'No se actualizaron los registros.';
        }
        break;
    case 'del':
        break;
    case 'list':
        $data = (new Empleados)->getEmpleados();
        $resultado = [];
        if (!empty($data)) {
            foreach ($data as $row) {
                $nombre = trim($row['nombre1'] . ' ' . $row['nombre2'] . ' ' . $row['apellido1'] . ' ' . $row['apellido2'] . ' - ' . $row['no_documento']);
                $resultado[] = [
                    'label'  => $nombre,
                    'id'     => $row['id_empleado']
                ];
            }
        }
        $res = $resultado;
        break;
    default:
        break;
}

echo json_encode($res);
