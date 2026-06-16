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
use Src\Nomina\Empleados\Php\Clases\Prestaciones_Sociales;
use Src\Nomina\Liquidacion\Php\Clases\Anulacion;
use Src\Nomina\Liquidacion\Php\Clases\Liquidacion;
use Src\Nomina\Liquidacion\Php\Clases\Nomina;
use Src\Nomina\Liquidacion\Php\Clases\Otros;
use Src\Nomina\Liquidado\Php\Clases\Detalles;

$Detalles = new Detalles();

$res['status'] = 'error';
$res['msg'] = '';
switch ($action) {
    case 'form':
        $res['status'] = 'ok';
        $res['msg'] = $Detalles->getFormulario($id, $id_nomina, $item);
        break;
    case 'form2':
        $res['status'] = 'ok';
        $res['msg'] = $Detalles->getFormulario2($id);
        break;
    case 'add':

        break;
    case 'edit':
        $option = $_POST['option'];
        $suma = 0;
        $valida = false;
        $id_empleado = $_POST['id_empleado'];
        $conexion = Conexion::getConexion();
        $conexion->beginTransaction();
        switch ($option) {
            case 1:
                $Otros = new Otros($conexion);
                $datos = $Otros->getRegistroLiq($_POST);
                $id = $datos['id'];
                if ($id > 0 && ($datos['dias'] != $_POST['dias_lab'] || $datos['val_laborado'] != $_POST['valor_laborado'] || $datos['val_auxtrans'] != $_POST['aux_tran'] || $datos['auxalim'] != $_POST['alimentacion'] || $datos['grepre'] != $_POST['g_representa'])) {
                    $data = [
                        'id' => $id,
                        'dias' => $_POST['dias_lab'],
                        'laborado' => $_POST['valor_laborado'],
                        'auxtrans' => $_POST['aux_tran'],
                        'auxalim' => $_POST['alimentacion'],
                        'grepre' => $_POST['g_representa'],
                        'tipo' => 'M'
                    ];
                    $rspt = $Otros->editRegistroLabliq($data);
                    if ($rspt == 'si') {
                        $suma++;
                    } else if ($rspt != 'no') {
                        $conexion->rollBack();
                        exit(json_encode($rspt));
                    }
                }
                $Licencia = new Licencias_MoP($conexion);
                $datos = $Licencia->getRegistroLiq($_POST);
                $id = $datos['id'];
                if ($id > 0 && ($datos['valor'] != $_POST['valor_mp'])) {
                    $data = [
                        'id' => $id,
                        'valor' => $_POST['valor_mp'],
                        'tipo' => 'M'
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
                    $Anular = new Anulacion($conexion);
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
                            'chk_liquidacion' => [0 => $id_empleado],
                            'id_contrato' => [$id_empleado => $_POST['id_contrato']],
                            'lab' => [$id_empleado => $_POST['dias_lab']],
                            'metodo' => [$id_empleado => $_POST['metodo_pago']],
                            'tipo' => 2,
                            'mes' => $_POST['mes'],
                        ];
                        $rstd = $Liquidacion->addRegistro($array, 1);
                        if ($rstd == 'si') {
                            $suma++;
                        } else {
                            $conexion->rollBack();
                            exit(json_encode($rstd));
                        }
                    }
                }
                break;
            case 2:
                $nominaActual = (new Nomina())->getRegistro($_POST['id_nomina']);
                $codigoNomina = $nominaActual['tipo'] ?? '';
                $liquidaTodos = in_array($codigoNomina, ['N', 'PS', 'RA', 'IN']);
                $liquidaPrimaServicio = $liquidaTodos || $codigoNomina == 'PV';
                $liquidaPrimaNavidad = $liquidaTodos || $codigoNomina == 'PN';
                $liquidaCesantias = $liquidaTodos || in_array($codigoNomina, ['CE', 'IC']);
                $liquidaBsp = $liquidaTodos;
                $liquidaVacaciones = $liquidaTodos || $codigoNomina == 'VC';

                $_POST['tiene_grep'] = (isset($_POST['grep']) && floatval($_POST['grep']) > 0) ? 1 : 0;

                $id = (new Valores_Liquidacion($conexion))->getRegistroLiq($_POST);
                $val_liq = (new Valores_Liquidacion($conexion))->editRegistroLiq($_POST);
                if ($val_liq == 'si') {
                    $suma++;
                    $valida = true; // Activar re-liquidación
                    //echo 'Actualizado valores de liquidación.';
                } else if ($val_liq != 'no') {
                    $conexion->rollBack();
                    exit(json_encode($val_liq));
                }
                //primas
                $Primas = new Primas($conexion);
                if ($liquidaPrimaServicio && $_POST['valor_ps'] >= 0) {
                    $dataPrima = $Primas->getRegistroLiq1($_POST);
                    $id = $dataPrima['id'];
                    $val = floatval($dataPrima['valor']);
                    $post = isset($_POST['valor_ps']) ? floatval($_POST['valor_ps']) : $val;
                    if ($id == 0 && $post > 0) {
                        $data = [
                            'id_empleado' => $id_empleado,
                            'cant_dias' => $_POST['dias_ps'],
                            'val_liq_ps' => $_POST['valor_ps'],
                            'val_liq_pns' => 0,
                            'periodo' => 1,
                            'corte' => $_POST['corte_ps'],
                            'id_nomina' => $_POST['id_nomina'],
                            'tipo' => 'M',
                            'id' => $id,
                        ];
                        $Servicio = $Primas->addRegistroLiq1($data);
                    } else if ($id > 0 && $val != $post) {
                        $data = [
                            'id_empleado' => $id_empleado,
                            'cant_dias' => $_POST['dias_ps'],
                            'val_liq_ps' => $_POST['valor_ps'],
                            'val_liq_pns' => 0,
                            'periodo' => 1,
                            'corte' => $_POST['corte_ps'],
                            'id_nomina' => $_POST['id_nomina'],
                            'tipo' => 'M',
                            'id' => $id,
                        ];
                        $Servicio = $Primas->editRegistroLiq1($data);
                    } else {
                        $Servicio = 'si';
                    }
                    if ($Servicio == 'si') {
                        $suma++;
                        $valida = true; // Activar re-liquidación
                        //echo 'Actualizado prima de servicio.';
                    } else if ($Servicio != 'no') {
                        $conexion->rollBack();
                        exit(json_encode($Servicio));
                    }
                }
                if ($liquidaPrimaNavidad && $_POST['valor_pv'] >= 0) {
                    $dataNavidad = $Primas->getRegistroLiq2($_POST);
                    $id = $dataNavidad['id'];
                    $val = floatval($dataNavidad['valor']);
                    $post = isset($_POST['valor_pv']) ? floatval($_POST['valor_pv']) : $val;
                    if ($id == 0 && $post > 0) {
                        $data = [
                            'id_empleado' => $id_empleado,
                            'cant_dias' => $_POST['dias_pn'],
                            'val_liq_pv' => $_POST['valor_pv'],
                            'val_liq_pnv' => 0,
                            'periodo' => 2,
                            'corte' => $_POST['corte_pn'],
                            'id_nomina' => $_POST['id_nomina'],
                            'id' => $id,
                            'tipo' => 'M',
                        ];
                        $Navidad = $Primas->addRegistroLiq2($data);
                    } else if ($id > 0 && $val != $_POST['valor_pv']) {
                        $data = [
                            'id_empleado' => $id_empleado,
                            'cant_dias' => $_POST['dias_pn'],
                            'val_liq_pv' => $_POST['valor_pv'],
                            'val_liq_pnv' => 0,
                            'periodo' => 2,
                            'corte' => $_POST['corte_pn'],
                            'id_nomina' => $_POST['id_nomina'],
                            'id' => $id,
                            'tipo' => 'M',
                        ];
                        $Navidad = $Primas->editRegistroLiq2($data);
                    } else {
                        $Navidad = 'si';
                    }
                    if ($Navidad == 'si') {
                        $suma++;
                        $valida = true; // Activar re-liquidación
                        //echo 'Actualizado prima de navidad.';
                    } else if ($Navidad != 'no') {
                        $conexion->rollBack();
                        exit(json_encode($Navidad));
                    }
                }
                //Cesantías
                if ($liquidaCesantias && $_POST['val_cesantias'] >= 0) {
                    $Cesantias = new Cesantias($conexion);
                    $dataCesantias = $Cesantias->getRegistroLiq($_POST);
                    $id = $dataCesantias['id'];
                    $val1 = floatval($dataCesantias['val_cesantias']);
                    $val2 = floatval($dataCesantias['val_icesantias']);
                    $post1 = isset($_POST['val_cesantias']) ? floatval($_POST['val_cesantias']) : $val1;
                    $post2 = isset($_POST['val_icesantias']) ? floatval($_POST['val_icesantias']) : $val2;

                    file_put_contents('C:\Users\Edwin\.gemini\antigravity-ide\brain\3abcf960-731e-4a45-9259-0c7e7fd0cbb7\scratch\debug.log', "CE: id=$id, val1=$val1, post1=$post1\n", FILE_APPEND);

                    if ($id == 0 && $post1 > 0) {
                        $data = [
                            'id_empleado' => $id_empleado,
                            'cant_dias' => $_POST['dias_ces'],
                            'val_cesantias' => $post1,
                            'val_icesantias' => $post2,
                            'corte' => $_POST['corte_ces'],
                            'id_nomina' => $_POST['id_nomina'],
                            'tipo' => 'M',
                            'id' => $id
                        ];
                        $ces = $Cesantias->addRegistroLiq($data);
                    } else if ($id > 0 && ($val1 != $post1 || $val2 != $post2)) {
                        $data = [
                            'id_empleado' => $id_empleado,
                            'cant_dias' => $_POST['dias_ces'],
                            'val_cesantias' => $post1,
                            'val_icesantias' => $post2,
                            'corte' => $_POST['corte_ces'],
                            'id_nomina' => $_POST['id_nomina'],
                            'tipo' => 'M',
                            'id' => $id
                        ];
                        $ces = $Cesantias->editRegistroLiq($data);
                    } else {
                        $ces = 'si';
                    }
                    if ($ces == 'si') {
                        $suma++;
                        $valida = true; // Activar re-liquidación
                        //echo 'Actualizado cesantías.';
                    } else if ($ces != 'no') {
                        $conexion->rollBack();
                        exit(json_encode($ces));
                    }
                }
                //BSP
                if ($liquidaBsp) {
                    $BSP = new Bsp($conexion);
                    $data = $BSP->getRegistroLiq($_POST);
                    $id = $data['id'];
                    $val = $data['valor'];
                    if ($id > 0 && $val != $_POST['valor_bsp']) {
                        $data = [
                            'numValor' => $_POST['valor_bsp'],
                            'datFecCorte' => $_POST['corte_bsp'],
                            'tipo' => 'M',
                            'id' => $id
                        ];
                        $bsp = $BSP->editRegistro($data);
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
                            'valor' => $_POST['valor_bsp'],
                            'corte' => $_POST['corte_bsp'],
                            'id_nomina' => $_POST['id_nomina'],
                            'tipo' => 'M',
                        ];
                        $bsp = $BSP->addRegistro($data);
                        if ($bsp == 'si') {
                            $suma++;
                            //echo 'Agregada bonificación de servicios.';
                            $valida = true;
                        } else {
                            $conexion->rollBack();
                            exit(json_encode($bsp));
                        }
                    }
                }
                //Vacaciones
                if ($liquidaVacaciones) {
                    $Vacaciones = new Vacaciones($conexion);
                    $data = $Vacaciones->getRegistroLiq($_POST);
                    $id = $data['id'];
                    $valVac         = floatval($data['val_vac']);
                    $valPrima       = floatval($data['prima_vac']);
                    $valBonif       = floatval($data['bon_recrea']);
                    
                    $postValVac     = isset($_POST['valor_vacacion']) ? floatval($_POST['valor_vacacion']) : $valVac;
                    $postValPrima   = isset($_POST['val_prima_vac']) ? floatval($_POST['val_prima_vac']) : $valPrima;
                    $postValBonif   = isset($_POST['val_bon_recrea']) ? floatval($_POST['val_bon_recrea']) : $valBonif;

                    if ($id > 0 && ($valVac != $postValVac || $valPrima != $postValPrima || $valBonif != $postValBonif)) {
                        $data = [
                            'val_vac'      => $postValVac,
                            'prima_vac'    => $postValPrima,
                            'bon_recrea'   => $postValBonif,
                            'tipo'         => 'M',
                            'id'           => $id
                        ];
                        $vac = $Vacaciones->editRegistroLiq($data);
                        if ($vac == 'si') {
                            $suma++;
                            //echo 'Actualizado vacaciones.';
                            $valida = true;
                        } else if ($vac != 'no') {
                            $conexion->rollBack();
                            exit(json_encode($vac));
                        }
                    }
                }
                //Anular
                if ($valida) {
                    $Anular = new Anulacion($conexion);
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
                        $stmtTipo = $conexion->prepare("SELECT `id_tipo` FROM `nom_tipo_liquidacion` WHERE `codigo` = ? LIMIT 1");
                        $stmtTipo->execute([$codigoNomina]);
                        $id_tipo_nomina = $stmtTipo->fetchColumn() ?: 2;

                        $array = [
                            'chk_liquidacion' => [0 => $id_empleado],
                            'id_contrato' => [$id_empleado => $_POST['id_contrato']],
                            'lab' => [$id_empleado => $_POST['dias_lab']],
                            'metodo' => [$id_empleado => $_POST['metodo_pago']],
                            'tipo' => $id_tipo_nomina,
                            'mes' => $_POST['mes'],
                        ];

                        switch ($codigoNomina) {
                            case 'PS':
                                $Clase = new Prestaciones_Sociales($conexion);
                                $rstd = $Clase->addRegistro($array, 1);
                                break;
                            case 'PV':
                            case 'PN':
                                $Clase = new Primas($conexion);
                                $rstd = $Clase->addRegistroPsPn($array, 1);
                                break;
                            case 'CE':
                            case 'IC':
                                $Clase = new Cesantias($conexion);
                                $rstd = $Clase->addRegistroN($array, 1);
                                break;
                            case 'VC':
                                $Clase = new Vacaciones($conexion);
                                $rstd = $Clase->addRegistroNoVc($array, 1);
                                break;
                            default:
                                $array['tipo'] = 2; // Mantener el comportamiento original para los demas
                                $Liquidacion = new Liquidacion($conexion);
                                $rstd = $Liquidacion->addRegistro($array, 1);
                                break;
                        }

                        if ($rstd == 'si') {
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
                $id = $Seguridad_Social->getRegistroLiq($_POST);
                $_POST['id'] = $id;
                $seso = $Seguridad_Social->editRegistroLiq($_POST);
                if ($seso == 'si') {
                    $suma++;
                } else if ($seso != 'no') {
                    $conexion->rollBack();
                    exit(json_encode($seso));
                }

                $id = $Seguridad_Social->getRegistroLiq2($_POST);
                $_POST['id'] = $id;
                $pfis = $Seguridad_Social->editRegistroLiq2($_POST);
                if ($pfis == 'si') {
                    $suma++;
                } else if ($pfis != 'no') {
                    $conexion->rollBack();
                    exit(json_encode($pfis));
                }
                break;
            case 4:
                // Libranzas
                if (isset($_POST['libranza']) && is_array($_POST['libranza'])) {
                    foreach ($_POST['libranza'] as $id => $val) {
                        $stmt = $conexion->prepare("UPDATE `nom_liq_libranza` SET `val_mes_lib` = ? WHERE `id_lid_lib` = ?");
                        if ($stmt->execute([$val, $id]) && $stmt->rowCount() > 0) $suma++;
                    }
                }
                // Embargos
                if (isset($_POST['embargo']) && is_array($_POST['embargo'])) {
                    foreach ($_POST['embargo'] as $id => $val) {
                        $stmt = $conexion->prepare("UPDATE `nom_liq_embargo` SET `val_mes_embargo` = ? WHERE `id_liq_embargo` = ?");
                        if ($stmt->execute([$val, $id]) && $stmt->rowCount() > 0) $suma++;
                    }
                }
                // Sindicatos
                if (isset($_POST['sindicato']) && is_array($_POST['sindicato'])) {
                    foreach ($_POST['sindicato'] as $id => $val) {
                        $stmt = $conexion->prepare("UPDATE `nom_liq_sindicato_aportes` SET `val_aporte` = ? WHERE `id_aporte` = ?");
                        if ($stmt->execute([$val, $id]) && $stmt->rowCount() > 0) $suma++;
                    }
                }
                // Otros Descuentos
                if (isset($_POST['otro_dcto']) && is_array($_POST['otro_dcto'])) {
                    foreach ($_POST['otro_dcto'] as $id => $val) {
                        $stmt = $conexion->prepare("UPDATE `nom_liq_descuento` SET `valor` = ? WHERE `id_liq` = ?");
                        if ($stmt->execute([$val, $id]) && $stmt->rowCount() > 0) $suma++;
                    }
                }

                // Retenciones
                $id = (new Retenciones($conexion))->getRegistroLiq($_POST);
                $_POST['id'] = $id;
                $fte = (new Retenciones($conexion))->editRegistroLiq($_POST);
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
            if ($conexion->inTransaction()) {
                $conexion->commit();
            }
        } else {
            if ($conexion->inTransaction()) {
                $conexion->rollBack();
            }
            $res['msg'] = 'No se actualizaron los registros.';
        }
        break;
    case 'del':
        $rs = (new Nomina())->delRegistro($id);
        if ($rs == 'si') {
            $res['status'] = 'ok';
        } else {
            $res['msg'] = $rs;
        }
        break;
    case 'list':
        $data = (new Empleados)->getEmpleados();
        $resultado = [];
        if (!empty($data)) {
            foreach ($data as $row) {
                $nombre = trim($row['nombre1'] . ' ' . $row['nombre2'] . ' ' . $row['apellido1'] . ' ' . $row['apellido2'] . ' - ' . $row['no_documento']);
                $resultado[] = [
                    'label' => $nombre,
                    'id' => $row['id_empleado']
                ];
            }
        }
        $res = $resultado;
        break;
    case 'annul':
        $Anular = new Anulacion();
        $resul = $Anular->anulaRegistros($id, $id_nomina, 2);
        if ($resul == 'si') {
            $res['status'] = 'ok';
        } else {
            $res['msg'] = $resul;
        }
        break;

    case 'estado':
        $estado = $_POST['estado'];
        $Nomina = new Nomina();
        $resul = $Nomina->cambiaEstado($id, $estado);
        if ($resul == 'si') {
            // Si es anulación (estado=0), guardar datos de anulación en nom_nominas
            if ($estado == '0') {
                try {
                    $fec_anull = $_POST['fec_anull'] ?? date('Y-m-d');
                    $concepto_anul = $_POST['concepto_anul'] ?? '';
                    $id_user_anul = $_SESSION['id_user'] ?? 0;
                    $cnx = Conexion::getConexion();
                    $sqlAnul = "UPDATE `nom_nominas`
                                SET `id_user_anul`  = :id_user_anul,
                                    `fec_anull`     = :fec_anull,
                                    `concepto_anul` = :concepto_anul
                                WHERE `id_nomina` = :id_nomina";
                    $stmtAnul = $cnx->prepare($sqlAnul);
                    $stmtAnul->bindValue(':id_user_anul', $id_user_anul, \PDO::PARAM_INT);
                    $stmtAnul->bindValue(':fec_anull', $fec_anull, \PDO::PARAM_STR);
                    $stmtAnul->bindValue(':concepto_anul', $concepto_anul, \PDO::PARAM_STR);
                    $stmtAnul->bindValue(':id_nomina', $id, \PDO::PARAM_INT);
                    $stmtAnul->execute();
                } catch (\PDOException $e) {
                    // No bloquear si falla el guardado de datos adicionales
                }
            }
            $res['status'] = 'ok';
        } else {
            $res['msg'] = $resul;
        }
        break;
    default:
        break;
}

echo json_encode($res);
