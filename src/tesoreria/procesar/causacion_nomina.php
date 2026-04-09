<?php

use Config\Clases\Sesion;
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

function indexarRubrosNomina($rubros)
{
    $rubrosPorTipo = [];
    $rubrosPorTipoCcosto = [];

    foreach ($rubros as $rb) {
        $tipo = (int) $rb['id_tipo'];
        $rubrosPorTipo[$tipo] = [
            'r_admin' => (int) $rb['r_admin'],
            'r_operativo' => (int) $rb['r_operativo'],
        ];

        if (!empty($rb['id_ccosto'])) {
            $rubrosPorTipoCcosto[$tipo][(int) $rb['id_ccosto']] = (int) $rb['r_admin'];
        }
    }

    return [$rubrosPorTipo, $rubrosPorTipoCcosto];
}

function indexarDetallesCopNomina($detalles)
{
    $indexados = [];

    foreach ($detalles as $detalle) {
        $indexados[(int) $detalle['id_rubro']][(int) $detalle['id_tercero_api']] = (int) $detalle['id_pto_cop_det'];
    }

    return $indexados;
}

function calcularValorRubroNomina($detalleEmpleado, $fields)
{
    if (is_array($fields)) {
        $valor = 0;
        foreach ($fields as $field) {
            if (!empty($detalleEmpleado[$field])) {
                $valor += $detalleEmpleado[$field];
            }
        }
        return $valor;
    }

    return !empty($detalleEmpleado[$fields]) ? $detalleEmpleado[$fields] : 0;
}

function resolverRubroNomina($detalleEmpleado, $tipo, $rubrosPorTipo, $rubrosPorTipoCcosto, $esPtoCaracter)
{
    if ($esPtoCaracter) {
        $ccostos = array_filter(array_map('trim', explode(',', (string) ($detalleEmpleado['id_ccosto'] ?? ''))));
        if (count($ccostos) !== 1) {
            throw new Exception(
                'El empleado con documento ' . $detalleEmpleado['no_documento'] .
                    ' tiene un centro de costo no valido para el pago: ' . ($detalleEmpleado['id_ccosto'] ?? 'sin definir')
            );
        }

        $idCcosto = (int) $ccostos[0];
        if (empty($rubrosPorTipoCcosto[$tipo][$idCcosto])) {
            throw new Exception(
                'No existe relacion de rubro para el tipo ' . $tipo .
                    ' y centro de costo ' . $idCcosto .
                    ' del empleado ' . $detalleEmpleado['no_documento']
            );
        }

        return (int) $rubrosPorTipoCcosto[$tipo][$idCcosto];
    }

    if (empty($rubrosPorTipo[$tipo])) {
        throw new Exception('No existe relacion de rubro para el tipo ' . $tipo . '.');
    }

    $rubro = $detalleEmpleado['tipo_cargo'] == '1'
        ? (int) $rubrosPorTipo[$tipo]['r_admin']
        : (int) $rubrosPorTipo[$tipo]['r_operativo'];

    if (!($rubro > 0)) {
        throw new Exception(
            'No existe rubro presupuestal configurado para el tipo ' . $tipo .
                ' y tipo de cargo del empleado ' . $detalleEmpleado['no_documento']
        );
    }

    return $rubro;
}

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
    $sql = "SELECT `id_tipo`, `r_admin`, `r_operativo`, `id_ccosto` FROM `nom_rel_rubro` WHERE `id_vigencia` = ?";
    $stmt = $cmd->prepare($sql);
    $stmt->bindParam(1, $id_vigencia, PDO::PARAM_INT);
    $stmt->execute();
    $rubros = $stmt->fetchAll();

    if ($tipo_nomina == 'CE') {
        $sql = "SELECT SUM(`val_cesantias`) AS `val_cesantias`, SUM(`val_icesantias`) AS `val_icesantias`, `nom_fondo_censan`.`id_tercero_api`
                FROM `nom_liq_cesantias`
                INNER JOIN (
                    SELECT `nf1`.`id_empleado`, `nf1`.`id_fc`
                    FROM `nom_novedades_fc` `nf1`
                    WHERE `nf1`.`id_novfc` = (
                        SELECT MAX(`nf2`.`id_novfc`)
                        FROM `nom_novedades_fc` `nf2`
                        WHERE `nf2`.`id_empleado` = `nf1`.`id_empleado`
                    )
                ) AS `ultimo_fondo` ON (`nom_liq_cesantias`.`id_empleado` = `ultimo_fondo`.`id_empleado`)
                INNER JOIN `nom_fondo_censan` ON (`ultimo_fondo`.`id_fc` = `nom_fondo_censan`.`id_fc`)
                WHERE (`nom_liq_cesantias`.`id_nomina` = ? AND `nom_liq_cesantias`.`estado` = 1) 
                GROUP BY `nom_fondo_censan`.`id_tercero_api`";
        $stmt = $cmd->prepare($sql);
        $stmt->bindParam(1, $id_nomina, PDO::PARAM_INT);
        $stmt->execute();
        $cesantias2 = $stmt->fetchAll();
    } elseif ($tipo_nomina == 'IC') {
        $sql = "SELECT SUM(`val_cesantias`) AS `val_cesantias`, SUM(`val_icesantias`) AS `val_icesantias`, `tb_terceros`.`id_tercero_api`
                FROM `nom_liq_cesantias`
                INNER JOIN `nom_empleado` ON (`nom_liq_cesantias`.`id_empleado` = `nom_empleado`.`id_empleado`)
                INNER JOIN `tb_terceros` ON (`nom_empleado`.`no_documento` = `tb_terceros`.`nit_tercero`)
                WHERE (`nom_liq_cesantias`.`id_nomina` = ? AND `nom_liq_cesantias`.`estado` = 1) 
                GROUP BY `tb_terceros`.`id_tercero_api`";
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
[$rubrosPorTipo, $rubrosPorTipoCcosto] = indexarRubrosNomina($rubros);
$idsDetalleIndexados = indexarDetallesCopNomina($ids_detalle);
$esPtoCaracter = (Sesion::Caracter() == 1 && Sesion::Pto() == 1);

$date = new DateTime('now', new DateTimeZone('America/Bogota'));
$fecha2 = $date->format('Y-m-d H:i:s');
$iduser = $_SESSION['id_user'];

$objeto = $nomina['descripcion'];

$tipo_doc_ceva = '4'; // CEVA - Comprobante de Egreso
try {
    $cmd = \Config\Clases\Conexion::getConexion();
    $cmd->beginTransaction();

    // Bloquea reprocesos concurrentes o dobles ejecuciones de la misma nomina.
    $sql = "SELECT `estado` FROM `nom_nominas` WHERE `id_nomina` = ? FOR UPDATE";
    $stmt = $cmd->prepare($sql);
    $stmt->bindParam(1, $id_nomina, PDO::PARAM_INT);
    $stmt->execute();
    $nominaEstado = $stmt->fetch(PDO::FETCH_ASSOC);
    if (empty($nominaEstado)) {
        throw new Exception('No se encontro la nomina a procesar.');
    }

    if ((int) $nominaEstado['estado'] === 5) {
        throw new Exception('La nomina ya fue pagada y tiene comprobante de egreso.');
    }

    $sql = "SELECT
                `nom_nomina_pto_ctb_tes`.`ceva`,
                `ctb_doc`.`id_manu`
            FROM `nom_nomina_pto_ctb_tes`
            INNER JOIN `ctb_doc`
                ON (`nom_nomina_pto_ctb_tes`.`ceva` = `ctb_doc`.`id_ctb_doc`)
            WHERE (`nom_nomina_pto_ctb_tes`.`id_nomina` = ? AND `nom_nomina_pto_ctb_tes`.`tipo` <> 'PL' AND `nom_nomina_pto_ctb_tes`.`ceva` IS NOT NULL)
            LIMIT 1
            FOR UPDATE";
    $stmt = $cmd->prepare($sql);
    $stmt->bindParam(1, $id_nomina, PDO::PARAM_INT);
    $stmt->execute();
    $cevaExistente = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!empty($cevaExistente['ceva'])) {
        $numCeva = $cevaExistente['id_manu'] ?? $cevaExistente['ceva'];
        throw new Exception('La nomina ya tiene un comprobante de egreso registrado (' . $numCeva . ').');
    }

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
    if (empty($tercero_emp) || (int) $id_ter_emp_api <= 0) {
        throw new Exception(
            'No se encontro un tercero valido para la empresa. Verifique el NIT ' .
                ($_SESSION['nit_emp'] ?? 'sin definir') .
                ' en la tabla de terceros.'
        );
    }

    $id_ter_doc = count($datos) == 1 ? $terceros[$datos[0]['no_documento']] : $id_ter_emp_api;
    if ((int) $id_ter_doc <= 0) {
        throw new Exception('No se pudo determinar un tercero valido para el documento de egreso.');
    }

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
        $tipo_field_map = [
            1  => ['valor_laborado', 'val_compensa'],
            2  => 'horas_ext',
            3  => 'g_representa',
            4  => 'val_bon_recrea',
            5  => 'val_bsp',
            6  => 'aux_tran',
            7  => 'aux_alim',
            8  => 'valor_incap_presupuesto',
            9  => 'val_indemniza',
            10 => 'valor_luto',
            17 => 'valor_vacacion',
            18 => 'val_cesantias',
            19 => 'val_icesantias',
            20 => 'val_prima_vac',
            21 => 'valor_pv',
            22 => 'valor_ps',
            23 => 'valor_viatico',
            32 => 'pago_empresa'
        ];
    // Preparar INSERT para pto_pag_detalle si el módulo presupuestal está activo
    $stmt_pto_pag = null;
    if ($_SESSION['pto'] == '1') {
        $sql_pto_pag = "INSERT INTO `pto_pag_detalle` (`id_ctb_doc`,`id_pto_cop_det`,`valor`,`valor_liberado`,`id_tercero_api`)
                        VALUES (?, ?, ?, ?, ?)";
        $stmt_pto_pag = $cmd->prepare($sql_pto_pag);
    }

    // Iterar sobre cada empleado liquidado
    foreach ($datos as $d) {
        $id_empleado = $d['id_empleado'];
        $id_ter_api = $terceros[$d['no_documento']];
        $total_pago_empleado = 0;
        $restar = 0;

        // Insertar registros de pago presupuestal si el módulo está activo
        if ($_SESSION['pto'] == '1' && $stmt_pto_pag) {
            $liberado = 0;
            foreach ($tipo_field_map as $tipo => $fields) {
                if ($tipo == 8) {
                    $valor_pto = $d['valor_incap'] - $d['pago_empresa'];
                } else {
                    $valor_pto = calcularValorRubroNomina($d, $fields);
                }
                $rubro = 0;
                $id_det = null;

                if ($valor_pto > 0) {
                    // Solo resolver el rubro si este tipo está configurado en nom_rel_rubro.
                    // Si no está (ej: tipo 8 - incapacidades pagadas por EPS), se omite
                    // la ejecución presupuestal sin error.
                    if (!empty($rubrosPorTipo[$tipo])) {
                        $rubro = resolverRubroNomina($d, $tipo, $rubrosPorTipo, $rubrosPorTipoCcosto, $esPtoCaracter);
                        $id_det = $idsDetalleIndexados[$rubro][(int) $id_ter_api] ?? null;
                    }
                }

                // Determinar el valor según el tipo de rubro
                switch ($tipo) {
                    case 1: // Salario básico
                        $valor_pto = $d['valor_laborado'] + $d['val_compensa'];
                        // Si el empleado tiene días de incapacidad y el COP no tiene
                        // rubro de sueldos para él, significa que el presupuesto se
                        // ejecuta por incapacidades (tipo 8/32). Omitir tipo 1.
                        if ((int)$d['dias_incapacidad'] > 0 && $id_det === null) {
                            $valor_pto = 0;
                            $rubro = 0;
                        }
                        break;
                    case 2: // Horas extras
                        $valor_pto = $d['horas_ext'];
                        break;
                    case 3: // Gastos de representación
                        $valor_pto = $d['g_representa'];
                        break;
                    case 4: // Bonificación recreación
                        $valor_pto = $d['val_bon_recrea'];
                        break;
                    case 5: // BSP
                        $valor_pto = $d['val_bsp'];
                        break;
                    case 6: // Auxilio de transporte
                        $valor_pto = $d['aux_tran'];
                        break;
                    case 7: // Auxilio de alimentación
                        $valor_pto = $d['aux_alim'];
                        break;
                    case 8: // Incapacidades (pago EPS/ARL)
                        $valor_pto = $d['valor_incap'] - $d['pago_empresa'];
                        break;
                    case 9: // Indemnización
                        $valor_pto = $d['val_indemniza'];
                        break;
                    case 10: // Indemnización
                        $valor_pto = $d['valor_luto'];
                        break;
                    case 17: // Vacaciones
                        $valor_pto = $d['valor_vacacion'];
                        break;
                    case 18: // Cesantías
                        $valor_pto = $d['val_cesantias'];
                        break;
                    case 19: // Intereses cesantías
                        $valor_pto = $d['val_icesantias'];
                        break;
                    case 20: // Prima de vacaciones
                        $valor_pto = $d['val_prima_vac'];
                        break;
                    case 21: // Prima de navidad
                        $valor_pto = $d['valor_pv'];
                        break;
                    case 22: // Prima de servicios
                        $valor_pto = $d['valor_ps'];
                        break;
                    case 23: // Viáticos
                        $valor_pto = $d['valor_viatico'];
                        break;
                    case 32: // Pago empresa (incapacidades)
                        $valor_pto = $d['pago_empresa'];
                        break;
                    default:
                        $valor_pto = 0;
                        break;
                }

                // Solo lanzar error si el tipo tiene rubro configurado en nom_rel_rubro
                // pero ese rubro no existe en el COP para este tercero.
                // Si $rubro === 0, significa que el tipo no tiene rubro presupuestal
                // (ej: incapacidades pagadas por EPS) y se omite sin error.
                if ($valor_pto > 0 && $rubro > 0 && $id_det === null) {
                    throw new Exception(
                        'No existe detalle COP para el rubro ' . $rubro .
                            ' y tercero ' . $id_ter_api .
                            ' del empleado ' . $d['no_documento']
                    );
                }


                if ($valor_pto > 0 && $rubro > 0 && $id_det !== null) {
                    $stmt_pto_pag->execute([$id_ctb_doc_ceva, $id_det, $valor_pto, $liberado, $id_ter_api]);
                }
            }
        }


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
                    case 10:
                        $valor = $d['valor_luto'];
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
                    case 23:
                        $valor = $d['valor_viatico'];
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
