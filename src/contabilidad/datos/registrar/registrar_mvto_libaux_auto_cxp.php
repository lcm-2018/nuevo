<?php
session_start();
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
if (!isset($_SESSION['user'])) {
    header('Location: ../index.php');
    exit();
}
$_post = json_decode(file_get_contents('php://input'), true);
include_once '../../../../config/autoloader.php';
$id_rol = $_SESSION['rol'];
$id_user = $_SESSION['id_user'];

use Config\Clases\Plantilla;
use Src\Common\Php\Clases\Permisos;

$permisos = new Permisos();
$opciones = $permisos->PermisoOpciones($id_user);
include_once '../../../financiero/consultas.php';

$id_doc = $_post['id_doc'];
$id_crp = $_post['id_crp'];
$iduser = $_SESSION['id_user'];
$date = new DateTime('now', new DateTimeZone('America/Bogota'));
$fecha2 = $date->format('Y-m-d H:i:s');

$cmd = \Config\Clases\Conexion::getConexion();
$response['status'] = 'error';
$response['msg'] = '<br>Ningún registro afectado';
$acumulador = 0; // Inicializar fuera del try

$datosDoc = GetValoresCxP($id_doc, $cmd);

try {
    $cmd->beginTransaction();

    // Eliminar registros existentes
    $stmt = $cmd->prepare("DELETE FROM `ctb_libaux` WHERE `id_ctb_doc` = ?");
    $stmt->execute([$id_doc]);

    $id_tercero = $datosDoc['id_tercero'];
    $id_tercero_ant = $id_tercero;

    // Obtener id_cdp desde el CRP
    $stmtCdp = $cmd->prepare("SELECT `id_cdp` FROM `pto_crp` WHERE `id_pto_crp` = ?");
    $stmtCdp->execute([$id_crp]);
    $crpData = $stmtCdp->fetch(PDO::FETCH_ASSOC);
    $id_cdp = $crpData ? $crpData['id_cdp'] : 0;

    // OPTIMIZACIÓN: Buscar cuentas SOLO de documentos relacionados al mismo CDP
    // En lugar de recorrer toda la tabla ctb_libaux
    $queryCuentas = "SELECT 
            la.`id_cuenta`,
            ctt.`id_tipo_bn_sv`,
            CASE WHEN la.`debito` > 0 THEN 'debito' ELSE 'credito' END AS tipo
        FROM `ctb_libaux` la
        INNER JOIN `ctb_doc` cd ON la.`id_ctb_doc` = cd.`id_ctb_doc`
        INNER JOIN `pto_crp` crp ON cd.`id_crp` = crp.`id_pto_crp`
        LEFT JOIN `ctt_adquisiciones` ctt ON ctt.`id_cdp` = crp.`id_cdp`
        WHERE crp.`id_cdp` = ?
        AND (la.`debito` > 0 OR la.`credito` > 0)
        AND la.`id_cuenta` IS NOT NULL
        GROUP BY ctt.`id_tipo_bn_sv`, tipo, la.`id_cuenta`
        ORDER BY la.`id_ctb_libaux` DESC
        LIMIT 100";

    $stmtCuentas = $cmd->prepare($queryCuentas);
    $stmtCuentas->execute([$id_cdp]);
    $todasCuentas = $stmtCuentas->fetchAll(PDO::FETCH_ASSOC);
    $stmtCuentas->closeCursor();

    // Separar en arrays de débito y crédito
    $ctas_debito = [];
    $ctas_credito = [];
    foreach ($todasCuentas as $cuenta) {
        if ($cuenta['tipo'] === 'debito') {
            $ctas_debito[] = $cuenta;
        } else {
            $ctas_credito[] = $cuenta;
        }
    }
    unset($todasCuentas);

    // Consulta de costos
    $stmtCostos = $cmd->prepare("SELECT
            ctt.`id_tipo_bn_sv`,
            cc.`valor`
        FROM `ctb_doc` cd
        INNER JOIN `pto_crp` crp ON cd.`id_crp` = crp.`id_pto_crp`
        LEFT JOIN `ctt_adquisiciones` ctt ON crp.`id_cdp` = ctt.`id_cdp`
        INNER JOIN `ctb_causa_costos` cc ON cc.`id_ctb_doc` = cd.`id_ctb_doc`
        WHERE cd.`id_ctb_doc` = ?");
    $stmtCostos->execute([$id_doc]);
    $datoscostos = $stmtCostos->fetchAll(PDO::FETCH_ASSOC);
    $stmtCostos->closeCursor();

    // Consulta de retenciones
    $stmtRetencion = $cmd->prepare("SELECT
            cr.`valor_retencion`,
            cr.`id_terceroapi`,
            rr.`id_retencion`,
            rt.`id_cuenta`
        FROM `ctb_causa_retencion` cr
        INNER JOIN `ctb_retencion_rango` rr ON cr.`id_rango` = rr.`id_rango`
        LEFT JOIN `ctb_retenciones` rt ON rr.`id_retencion` = rt.`id_retencion`
        WHERE cr.`id_ctb_doc` = ?");
    $stmtRetencion->execute([$id_doc]);
    $datosretencion = $stmtRetencion->fetchAll(PDO::FETCH_ASSOC);
    $stmtRetencion->closeCursor();

    // Consulta de ingresos (farmacia)
    $stmtIngresos = $cmd->prepare("SELECT
            SUM(vl.`cantidad` * vl.`valor_sin_iva`) AS `base`,
            SUM(vl.`cantidad` * vl.`valor_sin_iva` * vl.`iva` / 100) AS `iva`,
            vl.`id_cuenta`
        FROM (
            SELECT
                foid.`cantidad`,
                foid.`valor_sin_iva`,
                foid.`iva`,
                foid.`id_ingreso`,
                taux.`id_cuenta`
            FROM `far_orden_ingreso_detalle` foid
            INNER JOIN `far_orden_ingreso` foi ON foid.`id_ingreso` = foi.`id_ingreso`
            INNER JOIN `far_medicamento_lote` fml ON foid.`id_lote` = fml.`id_lote`
            INNER JOIN `far_medicamentos` fm ON fml.`id_med` = fm.`id_med`
            LEFT JOIN `far_subgrupos` fs ON fm.`id_subgrupo` = fs.`id_subgrupo`
            LEFT JOIN (
                SELECT fsc1.`id_subgrupo`, fsc1.`id_cuenta`
                FROM `far_subgrupos_cta` fsc1
                INNER JOIN (
                    SELECT `id_subgrupo`, MAX(`id_subgrupo_cta`) AS max_id
                    FROM `far_subgrupos_cta`
                    WHERE `fecha_vigencia` <= CURDATE()
                    GROUP BY `id_subgrupo`
                ) fsc2 ON fsc1.`id_subgrupo_cta` = fsc2.max_id
            ) AS taux ON taux.`id_subgrupo` = fs.`id_subgrupo`
            WHERE foi.`id_ctb_doc` = ?
        ) AS vl
        GROUP BY vl.`id_cuenta`");
    $stmtIngresos->execute([$id_doc]);
    $ingresos = $stmtIngresos->fetchAll(PDO::FETCH_ASSOC);
    $stmtIngresos->closeCursor();

    // Preparar datos para inserción
    $registrosInsertar = [];
    $total_debito = 0;
    $total_credito = 0;

    if (empty($ingresos)) {
        // Crear índice para búsqueda rápida
        $indexDebito = [];
        foreach ($ctas_debito as $val) {
            $indexDebito[$val['id_tipo_bn_sv']] = $val['id_cuenta'];
        }

        foreach ($datoscostos as $dc) {
            $id_tipo_bn_sv = $dc['id_tipo_bn_sv'];
            $id_cuenta = isset($indexDebito[$id_tipo_bn_sv]) ? $indexDebito[$id_tipo_bn_sv] : null;
            $debito = $dc['valor'];
            $total_debito += $debito;
            $registrosInsertar[] = [$id_doc, $id_tercero, $id_cuenta, $debito, 0, $iduser, $fecha2, 0];
        }
    } else {
        foreach ($ingresos as $ingreso) {
            $id_cuenta = $ingreso['id_cuenta'];
            $debito = $ingreso['base'] + $ingreso['iva'];
            $total_debito += $debito;
            $registrosInsertar[] = [$id_doc, $id_tercero, $id_cuenta, $debito, 0, $iduser, $fecha2, 0];
        }
    }

    // Agregar retenciones
    foreach ($datosretencion as $dr) {
        $id_cuenta = $dr['id_cuenta'];
        $credito = $dr['valor_retencion'];
        $total_credito += $credito;
        $registrosInsertar[] = [$id_doc, $id_tercero, $id_cuenta, 0, $credito, $iduser, $fecha2, 0];
    }

    // Agregar registro final de crédito
    if (empty($ingresos)) {
        $indexCredito = [];
        foreach ($ctas_credito as $val) {
            $indexCredito[$val['id_tipo_bn_sv']] = $val['id_cuenta'];
        }

        foreach ($datoscostos as $dc) {
            $id_tipo_bn_sv = $dc['id_tipo_bn_sv'];
            $id_cuenta = isset($indexCredito[$id_tipo_bn_sv]) ? $indexCredito[$id_tipo_bn_sv] : null;
            $credito = $total_debito - $total_credito;
            $registrosInsertar[] = [$id_doc, $id_tercero, $id_cuenta, 0, $credito, $iduser, $fecha2, 1];
            break;
        }
    } else {
        $credito = $total_debito - $total_credito;
        $registrosInsertar[] = [$id_doc, $id_tercero, null, 0, $credito, $iduser, $fecha2, 1];
    }

    // Inserción batch
    if (!empty($registrosInsertar)) {
        $placeholders = [];
        $valores = [];
        foreach ($registrosInsertar as $registro) {
            $placeholders[] = "(?, ?, ?, ?, ?, ?, ?, ?)";
            foreach ($registro as $val) {
                $valores[] = $val;
            }
        }

        $sqlBatch = "INSERT INTO `ctb_libaux` 
            (`id_ctb_doc`, `id_tercero_api`, `id_cuenta`, `debito`, `credito`, `id_user_reg`, `fecha_reg`, `ref`) 
            VALUES " . implode(", ", $placeholders);

        $stmtBatch = $cmd->prepare($sqlBatch);
        if ($stmtBatch->execute($valores)) {
            $acumulador = count($registrosInsertar);
        } else {
            $response['msg'] = $stmtBatch->errorInfo()[2];
        }
    }

    $cmd->commit();
} catch (PDOException $e) {
    if ($cmd->inTransaction()) {
        $cmd->rollBack();
    }
    $response['msg'] = $e->getMessage();
}

if ($acumulador > 0) {
    $response['status'] = 'ok';
}
echo json_encode($response);
exit();
