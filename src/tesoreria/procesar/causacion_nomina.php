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
$crp = $data[3];
$id_ctb_doc = $data[4]; // Documento contable de causación (CNOM)
$fecha = $data[5];

$Detalles = new Detalles();
$Terceros = new Terceros();
$Nomina = new Nomina();

// Obtener todos los datos de liquidación de la nómina de forma unificada
$datos = $Detalles->getRegistrosDT(1, -1, ['id_nomina' => $id_nomina], 1, 'ASC');
$nomina = $Nomina->getRegistro($id_nomina);
$terceros = $Terceros->getTerceros();
$terceros = array_column($terceros, 'id', 'cedula');

$tipo_nomina = $nomina['tipo'];

try {
    $cmd = \Config\Clases\Conexion::getConexion();
    // Cargar cuentas de causación que son pasivos
    $sql = "SELECT
                `nom_causacion`.`id_causacion`, `nom_causacion`.`centro_costo`, `nom_causacion`.`id_tipo`,
                `nom_tipo_rubro`.`nombre`, `nom_causacion`.`cuenta`, `nom_causacion`.`detalle`,
                `tb_centrocostos`.`es_pasivo`
            FROM `nom_causacion`
            INNER JOIN `nom_tipo_rubro` ON (`nom_causacion`.`id_tipo` = `nom_tipo_rubro`.`id_rubro`)
            INNER JOIN `tb_centrocostos` ON (`nom_causacion`.`centro_costo` = `tb_centrocostos`.`id_centro`)
            WHERE (`tb_centrocostos`.`es_pasivo` = 1)";
    $rs = $cmd->query($sql);
    $cPasivo = $rs->fetchAll();

    // Cargar cuenta de banco para pago de nómina
    $sql = "SELECT `id_cuenta` AS `cta_contable` FROM `tes_cuentas` WHERE (`est_nomina` = 1)";
    $rs = $cmd->query($sql);
    $banco = $rs->fetch(PDO::FETCH_ASSOC);

    // Obtener los detalles de la causación para el enlace presupuestal
    $ids_detalle = [];
    if ($_SESSION['pto'] == '1') {
        $sql = "SELECT
                `pto_cop_detalle`.`id_pto_cop_det`, `pto_cdp_detalle`.`id_rubro`, `pto_cop_detalle`.`id_tercero_api`
            FROM `pto_cop_detalle`
            INNER JOIN `pto_crp_detalle` ON (`pto_cop_detalle`.`id_pto_crp_det` = `pto_crp_detalle`.`id_pto_crp_det`)
            INNER JOIN `pto_cdp_detalle` ON (`pto_crp_detalle`.`id_pto_cdp_det` = `pto_cdp_detalle`.`id_pto_cdp_det`)
            WHERE (`pto_cop_detalle`.`id_ctb_doc` = ?)";
        $stmt = $cmd->prepare($sql);
        $stmt->bindParam(1, $id_ctb_doc, PDO::PARAM_INT);
        $stmt->execute();
        $ids_detalle = $stmt->fetchAll();
    }
    // Relación de rubros
    $sql = "SELECT `id_tipo`, `r_admin`, `r_operativo` FROM `nom_rel_rubro` WHERE `id_vigencia` = ?";
    $stmt = $cmd->prepare($sql);
    $stmt->bindParam(1, $id_vigencia, PDO::PARAM_INT);
    $stmt->execute();
    $rubros = $stmt->fetchAll();

    if ($tipo_nomina == 'CE' || $tipo_nomina == 'IC') {
        $sql = "SELECT SUM(`val_cesantias`) AS `val_cesantias`, SUM(`val_icesantias`) AS `val_icesantias`, `nom_fondo_censan`.`id_tercero_api`
                FROM `nom_liq_cesantias`
                INNER JOIN `nom_novedades_fc` ON (`nom_liq_cesantias`.`id_empleado` = `nom_novedades_fc`.`id_empleado`)
                INNER JOIN `nom_fondo_censan` ON (`nom_novedades_fc`.`id_fc` = `nom_fondo_censan`.`id_fc`)
                WHERE (`nom_liq_cesantias`.`id_nomina` =  ?) GROUP BY `nom_fondo_censan`.`id_tercero_api`";
        $stmt = $cmd->prepare($sql);
        $stmt->bindParam(1, $id_nomina, PDO::PARAM_INT);
        $stmt->execute();
        $cesantias2 = $stmt->fetchAll();
    }
    $cmd = null;
} catch (PDOException $e) {
    echo $e->getCode() == 2002 ? 'Sin Conexión a Mysql (Error: 2002)' : 'Error: ' . $e->getMessage();
    exit();
}

$date = new DateTime('now', new DateTimeZone('America/Bogota'));
$fecha2 = $date->format('Y-m-d H:i:s');
$iduser = $_SESSION['id_user'];

$objeto = $nomina['descripcion'];

$tipo_doc_ceva = '4'; // CEVA - Comprobante de Egreso
try {
    $cmd = \Config\Clases\Conexion::getConexion();
    $cmd->beginTransaction();

    $sql = "SELECT MAX(`id_manu`) AS `id_manu` FROM `ctb_doc` WHERE `id_vigencia` = ? AND `id_tipo_doc` = ?";
    $stmt = $cmd->prepare($sql);
    $stmt->bindParam(1, $id_vigencia, PDO::PARAM_INT);
    $stmt->bindParam(2, $tipo_doc_ceva, PDO::PARAM_INT);
    $stmt->execute();
    $consecutivo = $stmt->fetch();
    $id_manu = !empty($consecutivo) ? $consecutivo['id_manu'] + 1 : 1;

    $sql = "SELECT `id_tercero_api` FROM `tb_terceros` WHERE `nit_tercero` = ?";
    $stmt = $cmd->prepare($sql);
    $stmt->bindParam(1, $_SESSION['nit_emp'], PDO::PARAM_STR);
    $stmt->execute();
    $tercero_emp = $stmt->fetch();
    $id_ter_emp_api = !empty($tercero_emp) ? $tercero_emp['id_tercero_api'] : 0;
    $id_ter_doc = count($datos) == 1 ? $terceros[$datos[0]['no_documento']] : $id_ter_emp_api;

    // Insertar el documento de Egreso (CEVA)
    $estado_doc = 2;
    $sql = "INSERT INTO `ctb_doc` (`id_vigencia`, `id_tipo_doc`, `id_manu`,`id_tercero`, `fecha`, `detalle`, `id_user_reg`, `fecha_reg`, `estado`)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
    $stmt = $cmd->prepare($sql);
    $stmt->execute([$id_vigencia, $tipo_doc_ceva, $id_manu, $id_ter_doc, $fecha, $objeto, $iduser, $fecha2, $estado_doc]);
    $id_ctb_doc_ceva = $cmd->lastInsertId();
    if (!($id_ctb_doc_ceva > 0)) {
        throw new Exception("No se pudo insertar el documento CEVA.");
    }

    // Preparar inserciones en libaux
    $sql_libaux = "INSERT INTO `ctb_libaux` (`id_ctb_doc`,`id_tercero_api`,`id_cuenta`,`debito`,`credito`,`id_user_reg`,`fecha_reg`) 
                   VALUES (?, ?, ?, ?, ?, ?, ?)";
    $stmt_libaux = $cmd->prepare($sql_libaux);

    $con_ces = 0;
    // Iterar sobre cada empleado liquidado
    foreach ($datos as $d) {
        $id_empleado = $d['id_empleado'];
        $id_ter_api = $terceros[$d['no_documento']];
        $total_pago_empleado = 0;
        $restar = 0;

        if (($tipo_nomina == 'CE' || $tipo_nomina == 'IC')) {
            if ($con_ces == 0) {
                $cPasivo_ces = array_values(array_filter($cPasivo, function ($p) {
                    return in_array($p['id_tipo'], [18, 19]);
                }));
                foreach ($cesantias2 as $ces) {
                    $id_ter_ces = $ces['id_tercero_api'];
                    // Debito Cesantías
                    $key_ces = array_search(18, array_column($cPasivo_ces, 'id_tipo'));
                    if ($key_ces !== false) {
                        $cuenta = $cPasivo_ces[$key_ces]['cuenta'];
                        $valor = $ces['val_cesantias'];
                        if ($valor > 0) {
                            $stmt_libaux->execute([$id_ctb_doc_ceva, $id_ter_ces, $cuenta, $valor, 0, $iduser, $fecha2]);
                            $total_pago_empleado += $valor;
                        }
                    }
                    // Debito Intereses Cesantías
                    $key_ices = array_search(19, array_column($cPasivo_ces, 'id_tipo'));
                    if ($key_ices !== false) {
                        $cuenta = $cPasivo_ces[$key_ices]['cuenta'];
                        $valor = $ces['val_icesantias'];
                        if ($valor > 0) {
                            $stmt_libaux->execute([$id_ctb_doc_ceva, $id_ter_ces, $cuenta, $valor, 0, $iduser, $fecha2]);
                            $total_pago_empleado += $valor;
                        }
                    }
                }
            }
            $con_ces = 1;
        } else {
            // Lógica para nómina normal y otras
            foreach ($cPasivo as $cp) {
                $valor = 0;
                $credito = 0;
                $tipo = $cp['id_tipo'];
                $cuenta = $cp['cuenta'];

                switch ($tipo) {
                    case 1: // Neto a pagar
                        $sgs = $d['valor_salud'] + $d['valor_pension'] + $d['val_psolidaria'];
                        $deducciones = $sgs + $d['valor_sind'] + $d['valor_libranza'] + $d['valor_embargo'] + $d['val_retencion'] + $d['valor_dcto'];
                        $devengos = $d['valor_laborado'] + $d['horas_ext'] + $d['g_representa'] + $d['aux_tran'] + $d['aux_alim'] + $d['val_compensa'];
                        $valor = $devengos - $deducciones;
                        if ($valor < 0) {
                            $restar = abs($valor);
                            $valor = 0;
                        }
                        break;
                    case 4:
                        $valor = $d['val_bon_recrea'];
                        break;
                    case 5:
                        $valor = $d['val_bsp'];
                        break;
                    case 8:
                        $valor = ($d['valor_incap'] - $d['pago_empresa']) - $restar;
                        if ($valor < 0) {
                            $restar = abs($valor);
                            $valor = 0;
                        } else {
                            $restar = 0;
                        }
                        break;
                    case 9:
                        $valor = $d['val_indemniza'];
                        break;
                    case 17:
                        $valor = $d['valor_vacacion'] - $restar;
                        if ($valor < 0) {
                            $restar = abs($valor);
                            $valor = 0;
                        } else {
                            $restar = 0;
                        }
                        break;
                    case 18:
                        $valor = $d['val_cesantias'];
                        break;
                    case 19:
                        $valor = $d['val_icesantias'];
                        break;
                    case 20:
                        $valor = $d['val_prima_vac'];
                        if ($valor < 0) {
                            $restar = abs($valor);
                            $valor = 0;
                        } else {
                            $restar = 0;
                        }
                        break;
                    case 21:
                        $valor = $d['valor_pv'];
                        break;
                    case 22:
                        $valor = $d['valor_ps'];
                        break;
                    case 24:
                        $valor = $d['valor_pension'] + $d['val_psolidaria'];
                        break;
                    case 25:
                        $valor = $d['valor_salud'];
                        break;
                    case 26:
                        $valor = $d['valor_sind'];
                        break;
                    case 28:
                        $valor = $d['valor_libranza'];
                        break;
                    case 29:
                        $valor = $d['valor_embargo'];
                        break;
                    case 30:
                        $valor = $d['val_retencion'];
                        break;
                    case 32:
                        $valor = $d['pago_empresa'] - $restar;
                        if ($valor < 0) {
                            $restar = abs($valor);
                            $valor = 0;
                        } else {
                            $restar = 0;
                        }
                        break;
                    case 33: // Otros descuentos
                        // Esta lógica se maneja por separado si es necesario
                        break;
                }
                if ($valor > 0) {
                    $stmt_libaux->execute([$id_ctb_doc_ceva, $id_ter_api, $cuenta, $valor, 0, $iduser, $fecha2]);
                    $total_pago_empleado += $valor;
                }
            }
        }
        // Crédito a la cuenta del banco por el total pagado al empleado
        if ($total_pago_empleado > 0) {
            $stmt_libaux->execute([$id_ctb_doc_ceva, $id_ter_api, $banco['cta_contable'], 0, $total_pago_empleado, $iduser, $fecha2]);
        }
    }

    // Actualizar estado de la nómina
    $estado_nomina = 5; // Pagada
    $sql = "UPDATE `nom_nominas` SET `estado` = ? WHERE `id_nomina` = ?";
    $stmt = $cmd->prepare($sql);
    $stmt->execute([$estado_nomina, $id_nomina]);

    // Registrar el documento de egreso en la tabla de trazabilidad
    $query = "UPDATE `nom_nomina_pto_ctb_tes` SET `ceva` = ? WHERE `id_nomina` = ? AND `crp` = ?";
    $query = $cmd->prepare($query);
    $query->execute([$id_ctb_doc_ceva, $id_nomina, $crp]);

    $cmd->commit();
    echo 'ok';
} catch (Exception $e) {
    if ($cmd->inTransaction()) {
        $cmd->rollBack();
    }
    echo 'Error: ' . $e->getMessage();
}
