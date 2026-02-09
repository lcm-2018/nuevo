<?php
session_start();
if (!isset($_SESSION['user'])) {
    header("Location: ../../../index.php");
    exit();
}
include '../../../../config/autoloader.php';
include_once '../../../financiero/consultas.php';
function pesos($valor)
{
    return '$ ' . number_format($valor, 2, '.', ',');
}
$id_cta_factura = $_POST['id_cta_factura'];
$id_ctb_doc = $_POST['id_doc'];
$id_tipo_doc = $_POST['tipoDoc'];
$fecha_fact = $_POST['fechaDoc'];
$fecha_ven = $_POST['fechaVen'];
$num_doc = $_POST['numFac'];
$valor_pago = str_replace(",", "", $_POST['valor_pagar']);
$valor_iva = str_replace(",", "", $_POST['valor_iva']);
$valor_base = str_replace(",", "", $_POST['valor_base']);
$detalle = mb_strtoupper($_POST['detalle']);
$iduser = $_SESSION['id_user'];
$date = new DateTime('now', new DateTimeZone('America/Bogota'));
$cmd = \Config\Clases\Conexion::getConexion();
$response['status'] = 'error';

if ($id_cta_factura == 0) {
    $maxReintentos = 5;
    $intentos = 0;
    $insertado = false;

    while (!$insertado && $intentos < $maxReintentos) {
        $intentos++;
        try {
            // Iniciar transacción para garantizar consistencia
            $cmd->beginTransaction();

            // Obtener el prefijo si el tipo de documento es 3 (documento equivalente)
            $pref = '';
            $siguienteResol = 0;
            if ($id_tipo_doc == '3') {
                $sqlResol = "SELECT `consecutivo`, `prefijo` FROM `nom_resoluciones` 
                            WHERE `id_resol` = (SELECT MAX(`id_resol`) FROM `nom_resoluciones` WHERE `tipo` = 2)";
                $rsResol = $cmd->query($sqlResol);
                $prefijo = $rsResol->fetch();
                if (!empty($prefijo)) {
                    $siguienteResol = $prefijo['consecutivo'];
                    $pref = $prefijo['prefijo'];
                }
            }

            // Obtener el último consecutivo DENTRO de la transacción con bloqueo
            // Usamos FOR UPDATE para bloquear los registros mientras obtenemos el máximo
            $sqlMax = "SELECT MAX(CAST(REPLACE(`num_doc`, ?, '') AS UNSIGNED)) AS `max_num` 
                       FROM `ctb_factura` 
                       WHERE `id_tipo_doc` = ? 
                       FOR UPDATE";
            $stmtMax = $cmd->prepare($sqlMax);
            $stmtMax->bindParam(1, $pref, PDO::PARAM_STR);
            $stmtMax->bindParam(2, $id_tipo_doc, PDO::PARAM_INT);
            $stmtMax->execute();
            $datosMax = $stmtMax->fetch();

            // Calcular el nuevo consecutivo
            $maxActual = !empty($datosMax['max_num']) ? intval($datosMax['max_num']) : 0;
            $nuevoConsecutivo = $maxActual + 1;

            // Si hay resolución, tomar el mayor entre el consecutivo de resolución y el calculado
            if ($siguienteResol >= $nuevoConsecutivo) {
                $nuevoConsecutivo = $siguienteResol;
            }

            // Generar el número de documento final con prefijo
            $num_doc_final = $pref . $nuevoConsecutivo;

            // Verificar que no exista este consecutivo (doble validación)
            $sqlVerif = "SELECT COUNT(*) as existe FROM `ctb_factura` 
                         WHERE `id_tipo_doc` = ? AND `num_doc` = ?";
            $stmtVerif = $cmd->prepare($sqlVerif);
            $stmtVerif->bindParam(1, $id_tipo_doc, PDO::PARAM_INT);
            $stmtVerif->bindParam(2, $num_doc_final, PDO::PARAM_STR);
            $stmtVerif->execute();
            $existe = $stmtVerif->fetch();

            if ($existe['existe'] > 0) {
                // El consecutivo ya existe, hacer rollback y reintentar
                $cmd->rollBack();
                continue;
            }

            // Insertar el registro con el consecutivo calculado
            $sql = "INSERT INTO `ctb_factura`
                    (`id_ctb_doc`,`id_tipo_doc`,`num_doc`,`fecha_fact`,`fecha_ven`,`valor_pago`,`valor_iva`,`valor_base`,`detalle`,`id_user_reg`,`fec_rec`)
                VALUES (? , ? , ? , ? , ? , ? , ? , ? , ? , ? , ?)";
            $sql = $cmd->prepare($sql);
            $sql->bindParam(1, $id_ctb_doc, PDO::PARAM_INT);
            $sql->bindParam(2, $id_tipo_doc, PDO::PARAM_INT);
            $sql->bindParam(3, $num_doc_final, PDO::PARAM_STR);
            $sql->bindParam(4, $fecha_fact, PDO::PARAM_STR);
            $sql->bindParam(5, $fecha_ven, PDO::PARAM_STR);
            $sql->bindParam(6, $valor_pago, PDO::PARAM_STR);
            $sql->bindParam(7, $valor_iva, PDO::PARAM_STR);
            $sql->bindParam(8, $valor_base, PDO::PARAM_STR);
            $sql->bindParam(9, $detalle, PDO::PARAM_STR);
            $sql->bindParam(10, $iduser, PDO::PARAM_INT);
            $sql->bindValue(11, $date->format('Y-m-d H:i:s'));
            $sql->execute();

            $lastInsertId = $cmd->lastInsertId();
            if ($lastInsertId > 0) {
                // Si el tipo de documento es 3, insertar también en seg_soporte_fno
                if ($id_tipo_doc == '3' && !empty($pref)) {
                    // Generar la referencia con guion: prefijo-consecutivo (ej: rscc-001)
                    $referencia = $pref . '-' . $nuevoConsecutivo;

                    $sqlSoporte = "INSERT INTO `seg_soporte_fno` (`id_factura_no`, `referencia`, `tipo`) VALUES (?, ?, 0)";
                    $stmtSoporte = $cmd->prepare($sqlSoporte);
                    $stmtSoporte->bindParam(1, $lastInsertId, PDO::PARAM_INT);
                    $stmtSoporte->bindParam(2, $referencia, PDO::PARAM_STR);
                    $stmtSoporte->execute();

                    // Incrementar el consecutivo en nom_resoluciones para que el siguiente proceso tome el siguiente número
                    $sqlUpdateResol = "UPDATE `nom_resoluciones` SET `consecutivo` = ? 
                                       WHERE `id_resol` = (SELECT * FROM (SELECT MAX(`id_resol`) FROM `nom_resoluciones` WHERE `tipo` = 2) as tmp)";
                    $stmtUpdateResol = $cmd->prepare($sqlUpdateResol);
                    $nuevoConsecutivoResol = $nuevoConsecutivo + 1;
                    $stmtUpdateResol->bindParam(1, $nuevoConsecutivoResol, PDO::PARAM_INT);
                    $stmtUpdateResol->execute();
                }

                $cmd->commit();
                $insertado = true;
                $response['status'] = 'ok';
                $response['msg'] = 'Proceso realizado correctamente';
                $response['consecutivo'] = $num_doc_final;
            } else {
                $cmd->rollBack();
                $response['msg'] = 'Error: ' . $sql->errorInfo()[2];
            }
        } catch (PDOException $e) {
            if ($cmd->inTransaction()) {
                $cmd->rollBack();
            }
            // Si es un error de duplicado (1062), reintentar
            if ($e->getCode() == 23000 || $e->errorInfo[1] == 1062) {
                continue;
            }
            $response['msg'] = $e->getCode() == 2002 ? 'Sin Conexión a Mysql (Error: 2002)' : 'Error: ' . $e->getCode() . ' - ' . $e->getMessage();
        }
    }

    if (!$insertado && $intentos >= $maxReintentos) {
        $response['msg'] = 'No se pudo generar un consecutivo único después de ' . $maxReintentos . ' intentos. Por favor intente nuevamente.';
    }
} else {
    try {
        $num_doc = $_POST['numFac'];
        $sql = "UPDATE `ctb_factura`
                    SET `id_tipo_doc` = ?, `num_doc` = ?, `fecha_fact` = ?, `fecha_ven` = ?, `valor_pago` = ?, `valor_iva` = ?, `valor_base` = ?, `detalle` = ?
                WHERE `id_cta_factura` = ?";
        $sql = $cmd->prepare($sql);
        $sql->bindParam(1, $id_tipo_doc, PDO::PARAM_INT);
        $sql->bindParam(2, $num_doc, PDO::PARAM_STR);
        $sql->bindParam(3, $fecha_fact, PDO::PARAM_STR);
        $sql->bindParam(4, $fecha_ven, PDO::PARAM_STR);
        $sql->bindParam(5, $valor_pago, PDO::PARAM_STR);
        $sql->bindParam(6, $valor_iva, PDO::PARAM_STR);
        $sql->bindParam(7, $valor_base, PDO::PARAM_STR);
        $sql->bindParam(8, $detalle, PDO::PARAM_STR);
        $sql->bindParam(9, $id_cta_factura, PDO::PARAM_INT);
        if (!($sql->execute())) {
            $response['msg'] = $sql->errorInfo()[2];
        } else {
            if ($sql->rowCount() > 0) {
                $sql = $sql = "UPDATE `ctb_factura` SET `id_user_act` = ?,`fec_act` = ? WHERE `id_cta_factura` = ?";
                $sql = $cmd->prepare($sql);
                $sql->bindParam(1, $iduser, PDO::PARAM_INT);
                $sql->bindValue(2, $date->format('Y-m-d H:i:s'));
                $sql->bindParam(3, $id_cta_factura, PDO::PARAM_INT);
                $sql->execute();

                // Si el tipo de documento es 3, actualizar también en seg_soporte_fno
                if ($id_tipo_doc == '3') {
                    // Obtener el prefijo de la resolución
                    $sqlResol = "SELECT `prefijo` FROM `nom_resoluciones` 
                                WHERE `id_resol` = (SELECT MAX(`id_resol`) FROM `nom_resoluciones` WHERE `tipo` = 2)";
                    $rsResol = $cmd->query($sqlResol);
                    $prefijo = $rsResol->fetch();
                    $pref = !empty($prefijo['prefijo']) ? $prefijo['prefijo'] : '';

                    // Extraer el número del num_doc (quitar el prefijo si existe)
                    $numeroSolo = str_replace($pref, '', $num_doc);
                    // Generar la referencia con guion: prefijo-consecutivo
                    $referencia = $pref . '-' . $numeroSolo;

                    // Verificar si ya existe registro en seg_soporte_fno con tipo = 0
                    $sqlVerifSoporte = "SELECT COUNT(*) as existe FROM `seg_soporte_fno` WHERE `id_factura_no` = ? AND `tipo` = 0";
                    $stmtVerifSoporte = $cmd->prepare($sqlVerifSoporte);
                    $stmtVerifSoporte->bindParam(1, $id_cta_factura, PDO::PARAM_INT);
                    $stmtVerifSoporte->execute();
                    $existeSoporte = $stmtVerifSoporte->fetch();

                    if ($existeSoporte['existe'] > 0) {
                        // Actualizar registro existente con tipo = 0
                        $sqlSoporte = "UPDATE `seg_soporte_fno` SET `referencia` = ? WHERE `id_factura_no` = ? AND `tipo` = 0";
                        $stmtSoporte = $cmd->prepare($sqlSoporte);
                        $stmtSoporte->bindParam(1, $referencia, PDO::PARAM_STR);
                        $stmtSoporte->bindParam(2, $id_cta_factura, PDO::PARAM_INT);
                        $stmtSoporte->execute();
                    } else {
                        // Insertar nuevo registro con tipo = 0 (valor por defecto)
                        $sqlSoporte = "INSERT INTO `seg_soporte_fno` (`id_factura_no`, `referencia`, `tipo`) VALUES (?, ?, 0)";
                        $stmtSoporte = $cmd->prepare($sqlSoporte);
                        $stmtSoporte->bindParam(1, $id_cta_factura, PDO::PARAM_INT);
                        $stmtSoporte->bindParam(2, $referencia, PDO::PARAM_STR);
                        $stmtSoporte->execute();
                    }
                }

                $response['status'] = 'ok';
                $response['msg'] = 'Proceso realizado con correctamente';
            } else {
                $response['msg'] = 'No se realizó ningún cambio';
            }
        }
    } catch (PDOException $e) {
        $response['msg'] = $e->getCode() == 2002 ? 'Sin Conexión a Mysql (Error: 2002)' : 'Error: ' . $e->getCode();
    }
}
$acumulado = GetValoresCxP($id_ctb_doc, $cmd);
$acumulado = $acumulado['val_factura'];
$response['acumulado'] = pesos($acumulado);
echo json_encode($response);
