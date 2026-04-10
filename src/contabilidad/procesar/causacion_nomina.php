<?php

use Config\Clases\Sesion;
use Src\Common\Php\Clases\Terceros;
use Src\Nomina\Configuracion\Php\Clases\Rubros;
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

function indexarDetallesCrpNomina($detalles)
{
    $indexados = [];

    foreach ($detalles as $detalle) {
        $indexados[(int) $detalle['id_rubro']][(int) $detalle['id_tercero_api']] = (int) $detalle['id_pto_crp_det'];
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

function indexarOtrosDevengadosNomina($otrosDevengados)
{
    $indexados = [];

    foreach ($otrosDevengados as $otroDevengado) {
        $idEmpleado = isset($otroDevengado['id_empleado']) ? (int) $otroDevengado['id_empleado'] : 0;
        if (!($idEmpleado > 0)) {
            continue;
        }

        $indexados[$idEmpleado][] = [
            'documento' => $otroDevengado['documento'] ?? '',
            'rubro' => isset($otroDevengado['rubro']) ? (int) $otroDevengado['rubro'] : 0,
            'cuenta' => isset($otroDevengado['cuenta']) ? (int) $otroDevengado['cuenta'] : 0,
            'valor' => isset($otroDevengado['valor']) ? (float) $otroDevengado['valor'] : 0,
        ];
    }

    return $indexados;
}

function resolverRubroNomina($detalleEmpleado, $tipo, $rubrosPorTipo, $rubrosPorTipoCcosto, $esPtoCaracter)
{
    if ($esPtoCaracter) {
        $ccostos = array_filter(array_map('trim', explode(',', (string) ($detalleEmpleado['id_ccosto'] ?? ''))));
        if (count($ccostos) !== 1) {
            throw new Exception(
                'El empleado con documento ' . $detalleEmpleado['no_documento'] .
                    ' tiene un centro de costo no válido para la causación: ' . ($detalleEmpleado['id_ccosto'] ?? 'sin definir')
            );
        }

        $idCcosto = (int) $ccostos[0];
        if (empty($rubrosPorTipoCcosto[$tipo][$idCcosto])) {
            throw new Exception(
                'No existe relación de rubro para el tipo ' . $tipo .
                    ' y centro de costo ' . $idCcosto .
                    ' del empleado ' . $detalleEmpleado['no_documento']
            );
        }

        return (int) $rubrosPorTipoCcosto[$tipo][$idCcosto];
    }

    if (empty($rubrosPorTipo[$tipo])) {
        throw new Exception('No existe relación de rubro para el tipo ' . $tipo . '.');
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
$crp = $data[1];
$tipo_nomina = $data[2];

$Detalles = new Detalles();
$Terceros = new Terceros();
$Nomina = new Nomina();
$Rubros = new Rubros();

$datos = $Detalles->getRegistrosDT(1, -1, ['id_nomina' => $id_nomina], 1, 'ASC');
$nomina = $Nomina->getRegistro($id_nomina);
$terceros = $Terceros->getTerceros();
$terceros = array_column($terceros, 'id', 'cedula');
$otrosDevengadosPorEmpleado = indexarOtrosDevengadosNomina($Rubros->getDetalleCausacionOtrosDevengados($id_nomina));

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
                , `nom_rel_rubro`.`id_ccosto`
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
[$rubrosPorTipo, $rubrosPorTipoCcosto] = indexarRubrosNomina($rubros);
$esPtoCaracter = (Sesion::Caracter() == 1 && Sesion::Pto() == 1);
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
    if ($tipo_nomina == 'CE') {
        $sql = "SELECT
                        SUM(`nom_liq_cesantias`.`val_cesantias`) AS `val_cesantias`
                        , SUM(`nom_liq_cesantias`.`val_icesantias`) AS `val_icesantias`
                        , `nom_terceros`.`id_tercero_api`
                    FROM
                        `nom_liq_cesantias`
                        INNER JOIN (
                            SELECT `tn1`.`id_empleado`, `tn1`.`id_tercero`
                            FROM `nom_terceros_novedad` `tn1`
                            INNER JOIN `nom_terceros` `nt1` ON (`tn1`.`id_tercero` = `nt1`.`id_tn`)
                            WHERE `nt1`.`id_tipo` = 4
                            AND `tn1`.`id_novedad` = (
                                SELECT MAX(`tn2`.`id_novedad`)
                                FROM `nom_terceros_novedad` `tn2`
                                INNER JOIN `nom_terceros` `nt2` ON (`tn2`.`id_tercero` = `nt2`.`id_tn`)
                                WHERE `tn2`.`id_empleado` = `tn1`.`id_empleado`
                                AND `nt2`.`id_tipo` = 4
                            )
                        ) AS `ultimo_fondo` ON (`nom_liq_cesantias`.`id_empleado` = `ultimo_fondo`.`id_empleado`)
                        INNER JOIN `nom_terceros` 
                            ON (`ultimo_fondo`.`id_tercero` = `nom_terceros`.`id_tn`)
                    WHERE (`nom_liq_cesantias`.`id_nomina` = $id_nomina 
                        AND `nom_liq_cesantias`.`estado` = 1)
                    GROUP BY `nom_terceros`.`id_tercero_api`";
    } else {
        $sql = "SELECT
                        SUM(`nom_liq_cesantias`.`val_cesantias`) AS `val_cesantias`
                        , SUM(`nom_liq_cesantias`.`val_icesantias`) AS `val_icesantias`
                        , `tb_terceros`.`id_tercero_api`
                    FROM
                        `nom_liq_cesantias`
                        INNER JOIN `nom_empleado` 
                            ON (`nom_liq_cesantias`.`id_empleado` = `nom_empleado`.`id_empleado`)
                        INNER JOIN `tb_terceros` 
                            ON (`nom_empleado`.`no_documento` = `tb_terceros`.`nit_tercero`)
                    WHERE (`nom_liq_cesantias`.`id_nomina` = $id_nomina 
                        AND `nom_liq_cesantias`.`estado` = 1)
                    GROUP BY `tb_terceros`.`id_tercero_api`";
    }
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
$idsDetalleIndexados = indexarDetallesCrpNomina($ids_detalle);
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
        23 => 'valor_viatico',
        32 => 'pago_empresa'
    ];

    foreach ($datos as $dd) {
        // Extraer datos del empleado desde $dd
        $id_empleado = $dd['id_empleado'];
        $id_ter_api = $terceros[$dd['no_documento']] ?? NULL;
        $ccosto = $dd['id_ccosto'] ?? 21;
        $otrosDevengadosEmpleado = $otrosDevengadosPorEmpleado[$id_empleado] ?? [];

        $restar = 0;
        $rest = 0;
        $liberado = 0;


        foreach ($tipo_field_map as $tipo => $fields) {
            $valor = calcularValorRubroNomina($dd, $fields);
            $rubro = 0;
            $id_det = NULL;

            if ($valor > 0) {
                $rubro = resolverRubroNomina($dd, $tipo, $rubrosPorTipo, $rubrosPorTipoCcosto, $esPtoCaracter);
                $id_det = $idsDetalleIndexados[$rubro][(int) $id_ter_api] ?? NULL;
            }

            // Calcular el valor según el tipo de rubro



            // Insertar solo si hay valor y rubro válido
            if ($valor > 0 && $id_det === NULL) {
                throw new Exception(
                    'No existe detalle CRP para el rubro ' . $rubro .
                        ' y tercero ' . $id_ter_api .
                        ' del empleado ' . $dd['no_documento']
                );
            }

            if ($valor > 0 && $rubro > 0 && $id_det !== NULL) {
                $sql0->execute();
                if (!($cmd->lastInsertId() > 0)) {
                    throw new Exception($sql0->errorInfo()[2]);
                }
            }
        }

        // Manejar múltiples centros de costo separados por comas
        if (!empty($otrosDevengadosEmpleado)) {
            foreach ($otrosDevengadosEmpleado as $otroDevengado) {
                $valor = $otroDevengado['valor'];
                $rubro = $otroDevengado['rubro'];
                $id_det = $idsDetalleIndexados[$rubro][(int) $id_ter_api] ?? NULL;

                if ($valor > 0 && $rubro <= 0) {
                    throw new Exception(
                        'No existe rubro presupuestal configurado en el tipo de otros devengados para el empleado ' .
                            ($dd['no_documento'] ?? $otroDevengado['documento'] ?? 'sin documento')
                    );
                }

                if ($valor > 0 && $id_det === NULL) {
                    throw new Exception(
                        'No existe detalle CRP para el rubro ' . $rubro .
                            ' y tercero ' . $id_ter_api .
                            ' del empleado ' . ($dd['no_documento'] ?? $otroDevengado['documento'] ?? 'sin documento')
                    );
                }

                if ($valor > 0) {
                    $sql0->execute();
                    if (!($cmd->lastInsertId() > 0)) {
                        throw new Exception($sql0->errorInfo()[2]);
                    }
                }
            }
        }

        $ccosto = strval($ccosto); // Asegurar que sea string
        $ccostos_array = strpos($ccosto, ',') !== false ? explode(',', $ccosto) : [$ccosto];
        $num_ccostos = count($ccostos_array);

        foreach ($ccostos_array as $ccosto_individual) {
            $ccosto_individual = trim($ccosto_individual);
            $filtro = [];
            $filtro = array_filter($cuentas_causacion, function ($cuentas_causacion) use ($ccosto_individual) {
                return $cuentas_causacion["centro_costo"] == $ccosto_individual;
            });
            foreach ($filtro as $ca) {
                $valor = 0;
                $credito = 0;
                $tipo = $ca['id_tipo'];
                $cuenta = $ca['cuenta'];
                if ((int) $tipo === 34) {
                    if (!empty($otrosDevengadosEmpleado)) {
                        foreach ($otrosDevengadosEmpleado as $otroDevengado) {
                            $valor = $otroDevengado['valor'] / $num_ccostos;
                            $cuenta = $otroDevengado['cuenta'];
                            if ($valor > 0 && !($cuenta > 0)) {
                                throw new Exception(
                                    'No existe cuenta contable configurada en el tipo de otros devengados para el empleado ' .
                                        ($dd['no_documento'] ?? $otroDevengado['documento'] ?? 'sin documento')
                                );
                            }
                            if ($valor > 0 && $cuenta != '') {
                                $sql1->execute();
                                if (!($cmd->lastInsertId() > 0)) {
                                    throw new Exception($sql1->errorInfo()[2]);
                                }
                            }
                        }
                    }
                    continue;
                }
                switch ($tipo) {
                    case 1:
                        $valor = ($dd['valor_laborado'] + $dd['val_compensa']) / $num_ccostos;
                        break;
                    case 2:
                        $valor = $dd['horas_ext'] / $num_ccostos;
                        break;
                    case 3:
                        $valor = $dd['g_representa'] / $num_ccostos;
                        break;
                    case 4:
                        $valor = $dd['val_bon_recrea'] / $num_ccostos;
                        break;
                    case 5:
                        $valor = $dd['val_bsp'] / $num_ccostos;
                        break;
                    case 6:
                        $valor = $dd['aux_tran'] / $num_ccostos;
                        break;
                    case 7:
                        $valor = $dd['aux_alim'] / $num_ccostos;
                        break;
                    case 8:
                        $valor = ($dd['valor_incap'] - $dd['pago_empresa']) / $num_ccostos;
                        break;
                    case 9:
                        $valor = $dd['val_indemniza'] / $num_ccostos;
                        break;
                    case 10:
                        $valor = $dd['valor_luto'] / $num_ccostos;
                        break;
                    case 17:
                        $valor = $dd['valor_vacacion'] / $num_ccostos;
                        break;
                    case 18:
                        $valor = $dd['val_cesantias'] / $num_ccostos;
                        break;
                    case 19:
                        $valor = $dd['val_icesantias'] / $num_ccostos;
                        break;
                    case 20:
                        $valor = $dd['val_prima_vac'] / $num_ccostos;
                        break;
                    case 21:
                        $valor = $dd['valor_pv'] / $num_ccostos;
                        break;
                    case 22:
                        $valor = $dd['valor_ps'] / $num_ccostos;
                        break;
                    case 23:
                        $valor = $dd['valor_viatico'] / $num_ccostos;
                        break;
                    case 32:
                        $valor = $dd['pago_empresa'] / $num_ccostos;
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
                        $credito = $dd['valor_laborado'] + $dd['val_compensa'] + $dd['horas_ext'] + $dd['aux_tran'] + $dd['aux_alim'] - ($sgs + $valSind + $valLib + $valEmb + $valRteFte + $val_dcto);
                        if ($credito < 0) {
                            $restar = $credito * -1;
                            $credito = 0;
                        } else {
                            $restar = 0;
                        }
                        break;
                    case 3:
                        $credito = $dd['g_representa'];
                        break;
                    case 4:
                        $credito = $dd['val_bon_recrea'];
                        break;
                    case 5:
                        $credito = $dd['val_bsp'];
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
                    case 10:
                        $credito = $dd['valor_luto'] - $restar;
                        if ($credito < 0) {
                            $restar = $credito * -1;
                            $credito = 0;
                        } else {
                            $restar = 0;
                        }
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
                    case 23:
                        $credito = $dd['valor_viatico'];
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
                    case 34:
                        $credito = $dd['valor_otros'];
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
} catch (Exception $e) {
    if ($cmd instanceof PDO && $cmd->inTransaction()) {
        $cmd->rollBack();
    }
    throw new Exception('Error: ' . $e->getMessage());
}
exit;
