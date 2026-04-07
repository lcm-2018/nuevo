<?php
session_start();
if (!isset($_SESSION['user'])) {
    echo json_encode(['status' => 'error', 'msg' => 'Sesión no iniciada']);
    exit();
}
include '../../../config/autoloader.php';

$response = ['status' => 'error', 'msg' => ''];

// ── Parámetros ────────────────────────────────────────────────────────────────
// id_ctb_doc → documento CTCB destino
// id_arqueo  → documento arqueo (id_tipo_doc=9) a asignar/desasignar
$id_ctb_doc = isset($_POST['id_ctb_doc']) ? (int)$_POST['id_ctb_doc'] : 0;
$id_arqueo  = isset($_POST['id_arqueo'])  ? (int)$_POST['id_arqueo']  : 0;
$accion     = isset($_POST['accion'])     ? trim($_POST['accion'])     : 'asignar';

if ($id_ctb_doc <= 0 || $id_arqueo <= 0) {
    $response['msg'] = 'Parámetros inválidos.';
    echo json_encode($response);
    exit();
}

$iduser = $_SESSION['id_user'];
$date   = new DateTime('now', new DateTimeZone('America/Bogota'));
$fecha2 = $date->format('Y-m-d H:i:s');

try {
    $cmd = \Config\Clases\Conexion::getConexion();
    $cmd->beginTransaction();

    // ── Obtener cuentas CTCB ──────────────────────────────────────────────────
    $stmt = $cmd->prepare("SELECT
            `ctb_referencia`.`id_cuenta`,
            `ctb_referencia`.`id_cta_credito`
        FROM `ctb_referencia`
        INNER JOIN `ctb_fuente`
            ON (`ctb_referencia`.`id_ctb_fuente` = `ctb_fuente`.`id_doc_fuente`)
        WHERE `ctb_fuente`.`cod` = 'CTCB'
          AND `ctb_referencia`.`id_cuenta`      IS NOT NULL
          AND `ctb_referencia`.`id_cta_credito` IS NOT NULL
        LIMIT 1");
    $stmt->execute();
    $cuentas = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$cuentas) {
        throw new Exception('No hay cuentas contables configuradas para CTCB en ctb_referencia.');
    }
    $id_cta_debito  = (int) $cuentas['id_cuenta'];
    $id_cta_credito = (int) $cuentas['id_cta_credito'];

    // ── Obtener valor y tercero del arqueo ────────────────────────────────────
    $stmt = $cmd->prepare("SELECT
            SUM(`ctb_libaux`.`debito`) AS `valor`,
            `ctb_doc`.`id_tercero`
        FROM `ctb_libaux`
        INNER JOIN `ctb_doc` ON (`ctb_libaux`.`id_ctb_doc` = `ctb_doc`.`id_ctb_doc`)
        WHERE `ctb_libaux`.`id_ctb_doc` = :id_arqueo
        GROUP BY `ctb_doc`.`id_ctb_doc`
        LIMIT 1");
    $stmt->bindParam(':id_arqueo', $id_arqueo, PDO::PARAM_INT);
    $stmt->execute();
    $datosArqueo = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$datosArqueo || $datosArqueo['valor'] <= 0) {
        throw new Exception('El documento arqueo no tiene movimientos o su valor es cero.');
    }
    $valor      = (float) $datosArqueo['valor'];
    $id_tercero = (int)   $datosArqueo['id_tercero'];

    // =========================================================================
    // ASIGNAR
    // =========================================================================
    if ($accion === 'asignar') {

        // Verificar que el arqueo no esté ya vinculado
        $stmt = $cmd->prepare("SELECT `id_ctb_doc_tipo3` FROM `ctb_doc` WHERE `id_ctb_doc` = :id_arqueo LIMIT 1");
        $stmt->bindParam(':id_arqueo', $id_arqueo, PDO::PARAM_INT);
        $stmt->execute();
        $docArq = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$docArq) {
            throw new Exception('Documento arqueo no encontrado.');
        }
        if (!is_null($docArq['id_ctb_doc_tipo3'])) {
            throw new Exception('El documento arqueo ya está asignado a otro documento de consignación.');
        }

        // ── DÉBITO: una sola fila acumulada ──────────────────────────────────
        $stmt = $cmd->prepare("SELECT `id_ctb_libaux` FROM `ctb_libaux`
            WHERE `id_ctb_doc` = :id_ctb_doc
              AND `id_cuenta`   = :id_cta_debito
              AND `debito`      > 0
            LIMIT 1");
        $stmt->bindParam(':id_ctb_doc',   $id_ctb_doc,   PDO::PARAM_INT);
        $stmt->bindParam(':id_cta_debito', $id_cta_debito, PDO::PARAM_INT);
        $stmt->execute();
        $filaDebito = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($filaDebito) {
            // Ya existe → sumar el nuevo valor
            $stmt = $cmd->prepare("UPDATE `ctb_libaux`
                SET `debito` = `debito` + :valor,
                    `id_user_reg` = :iduser,
                    `fecha_reg`   = :fecha
                WHERE `id_ctb_doc` = :id_ctb_doc
                  AND `id_cuenta`  = :id_cta_debito
                  AND `debito`     > 0");
            $stmt->bindParam(':valor',       $valor,        PDO::PARAM_STR);
            $stmt->bindParam(':iduser',      $iduser,       PDO::PARAM_INT);
            $stmt->bindParam(':fecha',       $fecha2);
            $stmt->bindParam(':id_ctb_doc',  $id_ctb_doc,   PDO::PARAM_INT);
            $stmt->bindParam(':id_cta_debito', $id_cta_debito, PDO::PARAM_INT);
            $stmt->execute();
        } else {
            // Primera vez → INSERT
            $stmt = $cmd->prepare("INSERT INTO `ctb_libaux`
                (`id_ctb_doc`, `id_tercero_api`, `id_cuenta`, `debito`, `credito`, `id_user_reg`, `fecha_reg`)
                VALUES (:id_ctb_doc, :id_tercero, :id_cuenta, :debito, 0, :iduser, :fecha)");
            $stmt->bindParam(':id_ctb_doc', $id_ctb_doc,   PDO::PARAM_INT);
            $stmt->bindParam(':id_tercero', $id_tercero,   PDO::PARAM_INT);
            $stmt->bindParam(':id_cuenta',  $id_cta_debito, PDO::PARAM_INT);
            $stmt->bindParam(':debito',     $valor);
            $stmt->bindParam(':iduser',     $iduser,       PDO::PARAM_INT);
            $stmt->bindParam(':fecha',      $fecha2);
            $stmt->execute();
        }

        // ── CRÉDITO: una fila nueva por cada arqueo ───────────────────────────
        $stmt = $cmd->prepare("INSERT INTO `ctb_libaux`
            (`id_ctb_doc`, `id_tercero_api`, `id_cuenta`, `debito`, `credito`, `id_user_reg`, `fecha_reg`)
            VALUES (:id_ctb_doc, :id_tercero, :id_cuenta, 0, :credito, :iduser, :fecha)");
        $stmt->bindParam(':id_ctb_doc', $id_ctb_doc,    PDO::PARAM_INT);
        $stmt->bindParam(':id_tercero', $id_tercero,    PDO::PARAM_INT);
        $stmt->bindParam(':id_cuenta',  $id_cta_credito, PDO::PARAM_INT);
        $stmt->bindParam(':credito',    $valor);
        $stmt->bindParam(':iduser',     $iduser,        PDO::PARAM_INT);
        $stmt->bindParam(':fecha',      $fecha2);
        $stmt->execute();

        // ── Vincular el arqueo al CTCB ─────────────────────────────────────────
        $stmt = $cmd->prepare("UPDATE `ctb_doc` SET `id_ctb_doc_tipo3` = :id_ctb_doc WHERE `id_ctb_doc` = :id_arqueo");
        $stmt->bindParam(':id_ctb_doc', $id_ctb_doc, PDO::PARAM_INT);
        $stmt->bindParam(':id_arqueo',  $id_arqueo,  PDO::PARAM_INT);
        $stmt->execute();

        $cmd->commit();
        $response['status'] = 'ok';
        $response['msg']    = 'Consignación asignada correctamente.';
        $response['valor']  = number_format($valor, 2, ',', '.');

    // =========================================================================
    // DESASIGNAR
    // =========================================================================
    } elseif ($accion === 'desasignar') {

        // ── DÉBITO: restar el valor del arqueo quitado ────────────────────────
        $stmt = $cmd->prepare("UPDATE `ctb_libaux`
            SET `debito` = `debito` - :valor,
                `id_user_reg` = :iduser,
                `fecha_reg`   = :fecha
            WHERE `id_ctb_doc` = :id_ctb_doc
              AND `id_cuenta`  = :id_cta_debito
              AND `debito`     > 0");
        $stmt->bindParam(':valor',        $valor,        PDO::PARAM_STR);
        $stmt->bindParam(':iduser',       $iduser,       PDO::PARAM_INT);
        $stmt->bindParam(':fecha',        $fecha2);
        $stmt->bindParam(':id_ctb_doc',   $id_ctb_doc,   PDO::PARAM_INT);
        $stmt->bindParam(':id_cta_debito', $id_cta_debito, PDO::PARAM_INT);
        $stmt->execute();

        // ── CRÉDITO: eliminar la fila del arqueo (por cuenta + tercero + valor) ──
        $stmt = $cmd->prepare("DELETE FROM `ctb_libaux`
            WHERE `id_ctb_doc`    = :id_ctb_doc
              AND `id_cuenta`     = :id_cta_credito
              AND `id_tercero_api`= :id_tercero
              AND `credito`       = :credito
            LIMIT 1");
        $stmt->bindParam(':id_ctb_doc',    $id_ctb_doc,    PDO::PARAM_INT);
        $stmt->bindParam(':id_cta_credito', $id_cta_credito, PDO::PARAM_INT);
        $stmt->bindParam(':id_tercero',    $id_tercero,    PDO::PARAM_INT);
        $stmt->bindParam(':credito',       $valor);
        $stmt->execute();

        // ── Liberar el arqueo ─────────────────────────────────────────────────
        $stmt = $cmd->prepare("UPDATE `ctb_doc` SET `id_ctb_doc_tipo3` = NULL WHERE `id_ctb_doc` = :id_arqueo");
        $stmt->bindParam(':id_arqueo', $id_arqueo, PDO::PARAM_INT);
        $stmt->execute();

        $cmd->commit();
        $response['status'] = 'ok';
        $response['msg']    = 'Documento desasignado correctamente.';

    } else {
        throw new Exception('Acción no reconocida.');
    }

} catch (Exception $e) {
    if (isset($cmd) && $cmd->inTransaction()) {
        $cmd->rollBack();
    }
    $response['msg'] = $e->getMessage();
}

echo json_encode($response);
