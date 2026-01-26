<?php

use Src\Common\Php\Clases\Terceros;
use Src\Nomina\Liquidacion\Php\Clases\Nomina;
use Src\Nomina\Liquidado\Php\Clases\Detalles;

session_start();
if (!isset($_SESSION['user'])) {
    header('Location: ../../../index.php');
    exit();
}
include '../../../config/autoloader.php';
include '../../financiero/consultas.php';
$vigencia = $_SESSION['vigencia'];
$id_vigencia = $_SESSION['id_vigencia'];
$data = explode(',', file_get_contents("php://input"));
$id_nomina = $data[0];
$crp = $data[1];
$tipo_nomina = $data[2];

$Detalles = new Detalles();
$Terceros = new Terceros();
$Nomina = new Nomina();

$datos = $Detalles->getRegistrosDT(1, -1, ['id_nomina' => $id_nomina], 1, 'ASC');
$nomina = $Nomina->getRegistro($id_nomina);
$terceros = $Terceros->getTerceros();
$terceros = array_column($terceros, 'id', 'cedula');

$tipo_nomina = $nomina['tipo'];
$descripcion = $nomina['descripcion'];
$mes = $nomina['mes'] == '' ? '00' : $nomina['mes'];
try {
    $cmd = \Config\Clases\Conexion::getConexion();
    $sql = "SELECT
                `nom_tipo_rubro`.`id_rubro`
                , `nom_rel_rubro`.`id_tipo`
                , `nom_tipo_rubro`.`nombre`
                , `nom_rel_rubro`.`r_admin`
                , `nom_rel_rubro`.`r_operativo`
            FROM
                `nom_rel_rubro`
                INNER JOIN `nom_tipo_rubro` 
                    ON (`nom_rel_rubro`.`id_tipo` = `nom_tipo_rubro`.`id_rubro`)
            WHERE (`nom_rel_rubro`.`id_vigencia` = $id_vigencia)";
    $rs = $cmd->query($sql);
    $rubros = $rs->fetchAll();
    $rs->closeCursor();
    unset($rs);
    $cmd = null;
} catch (PDOException $e) {
    echo $e->getCode() == 2002 ? 'Sin Conexión a Mysql (Error: 2002)' : 'Error: ' . $e->getMessage();
}
try {
    $cmd = \Config\Clases\Conexion::getConexion();
    $sql = "SELECT
                `nom_causacion`.`id_causacion`
                , `nom_causacion`.`centro_costo`
                , `nom_causacion`.`id_tipo`
                , `nom_tipo_rubro`.`nombre`
                , `nom_causacion`.`cuenta`
                , `nom_causacion`.`detalle`
                , `tb_centrocostos`.`es_pasivo`
                FROM
                    `nom_causacion`
                INNER JOIN `nom_tipo_rubro` 
                    ON (`nom_causacion`.`id_tipo` = `nom_tipo_rubro`.`id_rubro`)
                INNER JOIN `tb_centrocostos`
                    ON (`nom_causacion`.`centro_costo` = `tb_centrocostos`.`id_centro`)";
    $rs = $cmd->query($sql);
    $cuentas_causacion = $rs->fetchAll();
    $rs->closeCursor();
    unset($rs);
    $cmd = null;
} catch (PDOException $e) {
    echo $e->getCode() == 2002 ? 'Sin Conexión a Mysql (Error: 2002)' : 'Error: ' . $e->getMessage();
}
try {
    $cmd = \Config\Clases\Conexion::getConexion();
    $sql = "SELECT 
                `ctb_retencion_rango`.`id_rango`
            FROM 
                `nom_causacion` 
                INNER JOIN `tb_centrocostos` 
                    ON (`nom_causacion`.`centro_costo` = `tb_centrocostos`.`id_centro`)
                INNER JOIN `ctb_retenciones` 
                    ON (`ctb_retenciones`.`id_cuenta` = `nom_causacion`.`cuenta`)
                INNER JOIN `ctb_retencion_rango` 
                    ON (`ctb_retencion_rango`.`id_retencion` = `ctb_retenciones`.`id_retencion`)
            WHERE (`tb_centrocostos`.`es_pasivo` = 1 AND `nom_causacion`.`id_tipo` = 30 AND `ctb_retencion_rango`.`id_vigencia` = $id_vigencia)";
    $rs = $cmd->query($sql);
    $rango = $rs->fetch(PDO::FETCH_ASSOC);
    $rango = !empty($rango) ? $rango['id_rango'] : exit('No se encontró el rango de retención  en la fuente para la causación de nómina');
    $cmd = null;
} catch (PDOException $e) {
    echo $e->getCode() == 2002 ? 'Sin Conexión a Mysql (Error: 2002)' : 'Error: ' . $e->getMessage();
}

try {
    $cmd = \Config\Clases\Conexion::getConexion();
    $sql = "SELECT
                    SUM(`nom_liq_cesantias`.`val_cesantias`) AS `val_cesantias`
                    , SUM(`nom_liq_cesantias`.`val_icesantias`) AS `val_icesantias`
                    , `nom_fondo_censan`.`id_tercero_api`
                FROM
                    `nom_liq_cesantias`
                    INNER JOIN `nom_novedades_fc` 
                        ON (`nom_liq_cesantias`.`id_empleado` = `nom_novedades_fc`.`id_empleado`)
                    INNER JOIN `nom_fondo_censan` 
                        ON (`nom_novedades_fc`.`id_fc` = `nom_fondo_censan`.`id_fc`)
                WHERE (`nom_liq_cesantias`.`id_nomina` =  $id_nomina AND `nom_liq_cesantias`.`estado` = 1)
                GROUP BY `nom_fondo_censan`.`id_tercero_api`";
    $rs = $cmd->query($sql);
    $cesantias2 = $rs->fetchAll();
    $rs->closeCursor();
    unset($rs);
    $cmd = null;
} catch (PDOException $e) {
    echo $e->getCode() == 2002 ? 'Sin Conexión a Mysql (Error: 2002)' : 'Error: ' . $e->getMessage();
}

$meses = array(
    '00' => '',
    '01' => 'Enero',
    '02' => 'Febrero',
    '03' => 'Marzo',
    '04' => 'Abril',
    '05' => 'Mayo',
    '06' => 'Junio',
    '07' => 'Julio',
    '08' => 'Agosto',
    '09' => 'Septiembre',
    '10' => 'Octubre',
    '11' => 'Noviembre',
    '12' => 'Diciembre'
);
$date = new DateTime('now', new DateTimeZone('America/Bogota'));
$fecha = $data[3];
$objeto = $nomina['descripcion'];
$iduser = $_SESSION['id_user'];
$fecha2 = $date->format('Y-m-d H:i:s');

//CNOM = 5
$cnom = 5;
try {
    $cmd = \Config\Clases\Conexion::getConexion();
    $sql = "SELECT MAX(`id_manu`) AS `id_manu` FROM `ctb_doc` WHERE `id_vigencia` = $id_vigencia AND `id_tipo_doc` = $cnom";
    $rs = $cmd->query($sql);
    $consecutivo = $rs->fetch();
    $id_manu = !empty($consecutivo) ? $consecutivo['id_manu'] + 1 : 1;
    $cmd = null;
} catch (PDOException $e) {
    echo $e->getCode() == 2002 ? 'Sin Conexión a Mysql (Error: 2002)' : 'Error: ' . $e->getMessage();
}

try {
    $cmd = \Config\Clases\Conexion::getConexion();
    $sql = "SELECT `id_tercero_api` FROM `tb_terceros` WHERE `nit_tercero` = " . $_SESSION['nit_emp'];
    $rs = $cmd->query($sql);
    $tercero = $rs->fetch();
    $id_ter_api = !empty($tercero) ? $tercero['id_tercero_api'] : NULL;
    $cmd = null;
} catch (PDOException $e) {
    echo $e->getCode() == 2002 ? 'Sin Conexión a Mysql (Error: 2002)' : 'Error: ' . $e->getCode();
}
try {
    $cmd = \Config\Clases\Conexion::getConexion();
    $sql = "SELECT
                `pto_crp_detalle`.`id_pto_crp_det`
                , `pto_cdp_detalle`.`id_rubro`
                , `pto_crp_detalle`.`id_tercero_api`
            FROM
                `pto_crp_detalle`
                INNER JOIN `pto_cdp_detalle` 
                    ON (`pto_crp_detalle`.`id_pto_cdp_det` = `pto_cdp_detalle`.`id_pto_cdp_det`)
            WHERE (`pto_crp_detalle`.`id_pto_crp` = $crp)";
    $rs = $cmd->query($sql);
    $ids_detalle = $rs->fetchAll();
    $rs->closeCursor();
    unset($rs);
    $cmd = null;
} catch (PDOException $e) {
    echo $e->getCode() == 2002 ? 'Sin Conexión a Mysql (Error: 2002)' : 'Error: ' . $e->getCode();
}
try {
    $cmd = \Config\Clases\Conexion::getConexion();
    $sql = "SELECT
                `nom_liq_descuento`.`valor`
                , `nom_tipo_descuentos`.`id_cuenta`
                , `nom_otros_descuentos`.`id_empleado`
            FROM
                `nom_liq_descuento`
                INNER JOIN `nom_otros_descuentos` 
                    ON (`nom_liq_descuento`.`id_dcto` = `nom_otros_descuentos`.`id_dcto`)
                INNER JOIN `nom_tipo_descuentos` 
                    ON (`nom_otros_descuentos`.`id_tipo_dcto` = `nom_tipo_descuentos`.`id_tipo`)
            WHERE (`nom_liq_descuento`.`id_nomina` = $id_nomina AND `nom_liq_descuento`.`estado` = 1)";
    $rs = $cmd->query($sql);
    $descuentos = $rs->fetchAll();
    $rs->closeCursor();
    unset($rs);
    $dctoGp = [];
    foreach ($descuentos as $d) {
        $dctoGp[$d['id_empleado']][] = $d;
    }
    $cmd = null;
} catch (PDOException $e) {
    echo $e->getCode() == 2002 ? 'Sin Conexión a Mysql (Error: 2002)' : 'Error: ' . $e->getCode();
}

try {
    $estado = 2;
    $cmd = \Config\Clases\Conexion::getConexion();
    $cmd->beginTransaction();
    $query = "INSERT INTO `ctb_doc` 
                (`id_vigencia`, `id_tipo_doc`, `id_manu`,`id_tercero`, `fecha`, `detalle`, `id_user_reg`, `fecha_reg`, `estado`)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
    $query = $cmd->prepare($query);
    $query->bindParam(1, $id_vigencia, PDO::PARAM_INT);
    $query->bindParam(2, $cnom, PDO::PARAM_STR);
    $query->bindParam(3, $id_manu, PDO::PARAM_INT);
    $query->bindParam(4, $id_ter_api, PDO::PARAM_INT);
    $query->bindParam(5, $fecha, PDO::PARAM_STR);
    $query->bindParam(6, $objeto, PDO::PARAM_STR);
    $query->bindParam(7, $iduser, PDO::PARAM_INT);
    $query->bindParam(8, $fecha2);
    $query->bindParam(9, $estado, PDO::PARAM_INT);
    $query->execute();
    $id_doc_nom = $cmd->lastInsertId();
    if (!($cmd->lastInsertId() > 0)) {
        throw new Exception($query->errorInfo()[2]);
    }
    $con_ces = 0;
    $sql0 = "INSERT INTO `pto_cop_detalle`
                    (`id_ctb_doc`, `id_pto_crp_det`,`id_tercero_api`,`valor`,`valor_liberado`,`id_user_reg`,`fecha_reg`) 
                VALUES (?, ?, ?, ?, ?, ?, ?)";
    $sql0 = $cmd->prepare($sql0);
    $sql0->bindParam(1, $id_doc_nom, PDO::PARAM_INT);
    $sql0->bindParam(2, $id_det, PDO::PARAM_INT);
    $sql0->bindParam(3, $id_ter_api, PDO::PARAM_INT);
    $sql0->bindParam(4, $valor, PDO::PARAM_STR);
    $sql0->bindParam(5, $liberado, PDO::PARAM_STR);
    $sql0->bindParam(6, $iduser, PDO::PARAM_INT);
    $sql0->bindParam(7, $fecha2, PDO::PARAM_STR);

    $sql1 = "INSERT INTO `ctb_libaux` (`id_ctb_doc`,`id_tercero_api`,`id_cuenta`,`debito`,`credito`,`id_user_reg`,`fecha_reg`) 
                VALUES (?, ?, ?, ?, ?, ?, ?)";
    $sql1 = $cmd->prepare($sql1);
    $sql1->bindParam(1, $id_doc_nom, PDO::PARAM_INT);
    $sql1->bindParam(2, $id_ter_api, PDO::PARAM_INT);
    $sql1->bindParam(3, $cuenta, PDO::PARAM_STR);
    $sql1->bindParam(4, $valor, PDO::PARAM_STR);
    $sql1->bindParam(5, $credito, PDO::PARAM_STR);
    $sql1->bindParam(6, $iduser, PDO::PARAM_INT);
    $sql1->bindParam(7, $fecha2);

    $sql2 = "INSERT INTO `ctb_causa_retencion`
                    (`id_ctb_doc`,`id_rango`,`valor_base`,`tarifa`,`valor_retencion`,`id_terceroapi`,`id_user_reg`,`fecha_reg`)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
    $sql2 = $cmd->prepare($sql2);
    $sql2->bindParam(1, $id_doc_nom, PDO::PARAM_INT);
    $sql2->bindParam(2, $rango, PDO::PARAM_INT);
    $sql2->bindParam(3, $base, PDO::PARAM_STR);
    $sql2->bindValue(4, 0, PDO::PARAM_STR);
    $sql2->bindParam(5, $valor_retencion, PDO::PARAM_STR);
    $sql2->bindParam(6, $id_ter_api, PDO::PARAM_INT);
    $sql2->bindParam(7, $iduser, PDO::PARAM_INT);
    $sql2->bindParam(8, $fecha2);

    foreach ($datos as $dd) {
        // Extraer datos del empleado desde $dd
        $id_empleado = $dd['id_empleado'];
        $tipo_cargo = $dd['tipo_cargo'];
        $id_ter_api = $terceros[$dd['no_documento']] ?? NULL;
        $ccosto = $dd['id_ccosto'] ?? 21;

        $restar = 0;
        $rest = 0;
        $liberado = 0;


        foreach ($rubros as $rb) {
            $tipo = $rb['id_tipo'];
            if ($tipo_cargo == '1') {
                $rubro = $rb['r_admin'];
            } else {
                $rubro = $rb['r_operativo'];
            }
            $valor = 0;
            $id_det = NULL;

            // Buscar el id_det correspondiente
            foreach ($ids_detalle as $detalle) {
                if ($detalle['id_rubro'] == $rubro && $detalle['id_tercero_api'] == $id_ter_api) {
                    $id_det = $detalle['id_pto_crp_det'];
                    break;
                }
            }

            // Calcular el valor según el tipo de rubro
            switch ($tipo) {
                case 1:
                    $valor = $dd['valor_laborado'];
                    break;
                case 2:
                    $valor = $dd['horas_ext'];
                    break;
                case 3:
                    $valor = $dd['g_representa'];
                    break;
                case 4:
                    $valor = $dd['val_bon_recrea'];
                    break;
                case 5:
                    $valor = $dd['val_bsp'];
                    break;
                case 6:
                    $valor = $dd['aux_tran'];
                    break;
                case 7:
                    $valor = $dd['aux_alim'];
                    break;
                case 9:
                    $valor = $dd['val_indemniza'];
                    break;
                case 17:
                    $valor = $dd['valor_vacacion'];
                    break;
                case 18:
                    $valor = $dd['val_cesantias'];
                    break;
                case 19:
                    $valor = $dd['val_icesantias'];
                    break;
                case 20:
                    $valor = $dd['val_prima_vac'];
                    break;
                case 21:
                    $valor = $dd['valor_pv'];
                    break;
                case 22:
                    $valor = $dd['valor_ps'];
                    break;
                case 32:
                    $valor = $dd['pago_empresa'];
                    break;
                default:
                    $valor = 0;
                    break;
            }

            // Insertar solo si hay valor y rubro válido
            if ($valor > 0 && $rubro != '' && $id_det !== NULL) {
                $sql0->execute();
                if (!($cmd->lastInsertId() > 0)) {
                    throw new Exception($sql0->errorInfo()[2]);
                }
            }
        }

        $filtro = [];
        $filtro = array_filter($cuentas_causacion, function ($cuentas_causacion) use ($ccosto) {
            return $cuentas_causacion["centro_costo"] == $ccosto;
        });
        foreach ($filtro as $ca) {
            $valor = 0;
            $credito = 0;
            $tipo = $ca['id_tipo'];
            $cuenta = $ca['cuenta'];
            switch ($tipo) {
                case 1:
                    $valor = $dd['valor_laborado'];
                    break;
                case 2:
                    $valor = $dd['horas_ext'];
                    break;
                case 3:
                    $valor = $dd['g_representa'];
                    break;
                case 4:
                    $valor = $dd['val_bon_recrea'];
                    break;
                case 5:
                    $valor = $dd['val_bsp'];
                    break;
                case 6:
                    $valor = $dd['aux_tran'];
                    break;
                case 7:
                    $valor = $dd['aux_alim'];
                    break;
                case 8:
                    $valor = $dd['valor_incap'] - $dd['pago_empresa'];
                    break;
                case 9:
                    $valor = $dd['val_indemniza'];
                    break;
                case 17:
                    $valor = $dd['valor_vacacion'];
                    break;
                case 18:
                    $valor = $dd['val_cesantias'];
                    break;
                case 19:
                    $valor = $dd['val_icesantias'];
                    break;
                case 20:
                    $valor = $dd['val_prima_vac'];
                    break;
                case 21:
                    $valor = $dd['valor_pv'];
                    break;
                case 22:
                    $valor = $dd['valor_ps'];
                    break;
                case 32:
                    $valor = $dd['pago_empresa'];
                    break;
                default:
                    $valor = 0;
                    break;
            }
            if ($valor > 0 && $cuenta != '') {
                $sql1->execute();
                if (!($cmd->lastInsertId() > 0)) {
                    throw new Exception($sql1->errorInfo()[2]);
                }
            }
        }

        // Filtrar cuentas pasivas (cuentas cuyo centro de costo tiene es_pasivo = 1)
        $cPasivo = [];
        $cPasivo = array_filter($cuentas_causacion, function ($cuentas_causacion) {
            return $cuentas_causacion["es_pasivo"] == 1;
        });
        if (($tipo_nomina == 'CE' || $tipo_nomina == 'IC')) {
            if ($con_ces == 0) {
                $cPasivo = array_values($cPasivo);
                foreach ($cesantias2 as $ces) {
                    $valor = 0;
                    $credito = 0;
                    $key = array_search(18, array_column($cPasivo, 'id_tipo'));
                    if ($key !== false) {
                        $cuenta = $cPasivo[$key]['cuenta'];
                        $credito = $ces['val_cesantias'];
                        $id_ter_api = $ces['id_tercero_api'];
                        if ($credito > 0 && $cuenta != '') {
                            $sql1->execute();
                            if (!($cmd->lastInsertId() > 0)) {
                                throw new Exception($sql1->errorInfo()[2]);
                            }
                        }
                    }
                    $key = array_search(19, array_column($cPasivo, 'id_tipo'));
                    if ($key !== false) {
                        $cuenta = $cPasivo[$key]['cuenta'];
                        $credito = $ces['val_icesantias'];
                        $id_ter_api = $ces['id_tercero_api'];
                        if ($credito > 0 && $cuenta != '') {
                            $sql1->execute();
                            if (!($cmd->lastInsertId() > 0)) {
                                throw new Exception($sql1->errorInfo()[2]);
                            }
                        }
                    }
                }
            }
            $con_ces = 1;
        } else {
            /*De aqui en adelante*/
            $dcto = isset($dctoGp[$dd['id_empleado']]) ? $dctoGp[$dd['id_empleado']] : [];
            foreach ($cPasivo as $cp) {
                $valor = 0;
                $credito = 0;
                $tipo = $cp['id_tipo'];
                $cuenta = $cp['cuenta'];
                switch ($tipo) {
                    case 1:
                        $valSind = $dd['valor_sind'];
                        $valLib = $dd['valor_libranza'];
                        $valEmb = $dd['valor_embargo'];
                        $valRteFte = $dd['val_retencion'];

                        if ($valRteFte > 0) {
                            $base = $dd['base_retencion'];
                            $valor_retencion = $dd['val_retencion'];
                            $sql2->execute();
                            if (!($cmd->lastInsertId() > 0)) {
                                throw new Exception($sql2->errorInfo()[2]);
                            }
                        }

                        $val_dcto = $dd['valor_dcto'];

                        $sgs = $dd['valor_salud'] + $dd['valor_pension'] + $dd['val_psolidaria'];
                        $credito = $dd['valor_laborado'] + $dd['horas_ext'] + $dd['g_representa'] + $dd['aux_tran'] + $dd['aux_alim'] - ($sgs + $valSind + $valLib + $valEmb + $valRteFte + $val_dcto);
                        if ($credito < 0) {
                            $restar = $credito * -1;
                            $credito = 0;
                        } else {
                            $restar = 0;
                        }
                        break;
                    case 4:
                        $credito = $dd['val_bsp'];
                        break;
                    case 5:
                        $credito = $dd['val_bon_recrea'];
                        break;
                    case 8:
                        $credito = $dd['valor_incap'] - $dd['pago_empresa'];
                        $credito -= $restar;
                        if ($credito < 0) {
                            $restar = $credito * -1;
                            $credito = 0;
                        } else {
                            $restar = 0;
                        }
                        break;
                    case 9:
                        $credito = $dd['val_indemniza'];
                        break;
                    case 17:
                        $credito = $dd['valor_vacacion'] - $restar;
                        if ($credito < 0) {
                            $restar = $credito * -1;
                            $credito = 0;
                        } else {
                            $restar = 0;
                        }
                        break;
                    case 18:
                        $credito = $dd['val_cesantias'];
                        break;
                    case 19:
                        $credito = $dd['val_icesantias'];
                        break;
                    case 20:
                        $credito = $dd['val_prima_vac'];
                        if ($credito < 0) {
                            $restar = $credito * -1;
                            $credito = 0;
                        } else {
                            $restar = 0;
                        }
                        break;
                    case 21:
                        $credito = $dd['valor_pv'];
                        break;
                    case 22:
                        $credito = $dd['valor_ps'];
                        break;
                    case 24:
                        $sgs = $dd['valor_pension'] + $dd['val_psolidaria'];
                        $credito = $sgs;
                        break;
                    case 25:
                        $credito = $dd['valor_salud'];
                        break;
                    case 26:
                        $credito = $dd['valor_sind'];
                        break;
                    case 28:
                        $credito = $dd['valor_libranza'];
                        break;
                    case 29:
                        $credito = $dd['valor_embargo'];
                        break;
                    case 30:
                        $credito = $dd['val_retencion'];
                        break;
                    case 32:
                        $credito = $dd['pago_empresa'];
                        $credito -= $restar;
                        if ($credito < 0) {
                            $restar = $credito * -1;
                            $credito = 0;
                        } else {
                            $restar = 0;
                        }
                        break;
                    case 33:
                        if (!empty($dcto)) {
                            foreach ($dcto as $dc) {
                                $credito = $dc['valor'];
                                $cuenta = $dc['id_cuenta'];
                                if ($credito > 0 && $cuenta != '') {
                                    $sql1->execute();
                                    if (!($cmd->lastInsertId() > 0)) {
                                        throw new Exception($sql1->errorInfo()[2]);
                                    }
                                }
                            }
                        }
                        $credito = 0;
                        break;
                    default:
                        $credito = 0;
                        break;
                }
                if ($credito > 0 && $cuenta != '') {
                    $sql1->execute();
                    if (!($cmd->lastInsertId() > 0)) {
                        throw new Exception($sql1->errorInfo()[2]);
                    }
                }
            }
        }
    }

    $estado = 4;
    $sql = "UPDATE `nom_nominas` SET `estado` = ? WHERE `id_nomina` = ?";
    $sql = $cmd->prepare($sql);
    $sql->bindParam(1, $estado, PDO::PARAM_INT);
    $sql->bindParam(2, $id_nomina, PDO::PARAM_INT);
    $sql->execute();
    if (!($sql->rowCount() > 0)) {
        throw new Exception($sql->errorInfo()[2]);
    }

    $query = "UPDATE `nom_nomina_pto_ctb_tes` SET `cnom` = ? WHERE `id_nomina` = ? AND `tipo` = ? AND `crp`  = ?";
    $query = $cmd->prepare($query);
    $query->bindParam(1, $id_doc_nom, PDO::PARAM_INT);
    $query->bindParam(2, $id_nomina, PDO::PARAM_INT);
    $query->bindParam(3, $tipo_nomina, PDO::PARAM_STR);
    $query->bindParam(4, $crp, PDO::PARAM_INT);
    $query->execute();
    if (!($query->rowCount() > 0)) {
        throw new Exception($query->errorInfo()[2]);
    }
    $cmd->commit();
    echo 'ok';
} catch (PDOException $e) {
    $cmd->rollBack();
    throw new Exception('Error: ' . $e->getMessage());
}
exit;
