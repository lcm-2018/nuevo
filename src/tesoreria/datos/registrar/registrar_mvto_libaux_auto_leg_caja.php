<?php
use Src\Common\Php\Clases\Valores;

session_start();
if (!isset($_SESSION['user'])) {
    header("Location: ../../../index.php");
    exit();
}
// Recibir variables por POST
include '../../../../config/autoloader.php';
$_post = json_decode(file_get_contents('php://input'), true);

$id_doc         = $_post['id'];
$id_crp         = $_post['id_crp'];
$id_cop         = $_post['id_cop'];
$tipo           = $_post['tipo'];
$fecIni         = $_post['fecIniTraslado'];
$fecFin         = $_post['fecFinTraslado'];
$id_caja_const  = $_post['caja_menor'];
$iduser         = $_SESSION['id_user'];

$date   = new DateTime('now', new DateTimeZone('America/Bogota'));
$fecha2 = $date->format('Y-m-d H:i:s');

$response['status'] = 'error';
$registros = 0;

// -------------------------------------------------------
// 1. Obtener debitos: rubros de caja agrupados por cuenta
//    contable y tercero dentro del periodo solicitado
// -------------------------------------------------------
$cuentasDebito = [];
try {
    $cmd = \Config\Clases\Conexion::getConexion();
    $sql = "SELECT
                tes_caja_rubros.id_caja_const,
                tes_caja_rubros.id_cta_contable,
                tb_terceros.id_tercero_api,
                SUM(tes_caja_mvto.valor) AS valor
            FROM tes_caja_mvto
            INNER JOIN ctb_doc
                ON (tes_caja_mvto.id_ctb_doc = ctb_doc.id_ctb_doc)
            INNER JOIN tes_caja_rubros
                ON (tes_caja_rubros.id_caja_rubros = tes_caja_mvto.id_caja_rubros)
            INNER JOIN pto_cargue
                ON (tes_caja_rubros.id_rubro_gasto = pto_cargue.id_cargue)
            INNER JOIN tb_terceros
                ON (ctb_doc.id_tercero = tb_terceros.id_tercero_api)
            WHERE ctb_doc.fecha BETWEEN ? AND ?
              AND tes_caja_rubros.id_caja_const = ?
            GROUP BY
                tes_caja_rubros.id_caja_const,
                tes_caja_rubros.id_cta_contable,
                tb_terceros.id_tercero_api";
    $rs = $cmd->prepare($sql);
    $rs->bindParam(1, $fecIni);
    $rs->bindParam(2, $fecFin);
    $rs->bindParam(3, $id_caja_const, PDO::PARAM_INT);
    $rs->execute();
    $cuentasDebito = $rs->fetchAll(PDO::FETCH_ASSOC);
    $rs->closeCursor();
    unset($rs);
    $cmd = null;
} catch (PDOException $e) {
    $response['msg'] = $e->getCode() == 2002
        ? 'Sin Conexion a Mysql (Error: 2002)'
        : 'Error al obtener debitos: ' . $e->getCode();
    echo json_encode($response);
    exit();
}

// -------------------------------------------------------
// 2. Obtener tercero del documento contable
// -------------------------------------------------------
$id_tercero = null;
try {
    $cmd = \Config\Clases\Conexion::getConexion();
    $sql = "SELECT id_tercero FROM ctb_doc WHERE id_ctb_doc = ?";
    $rs  = $cmd->prepare($sql);
    $rs->bindParam(1, $id_doc, PDO::PARAM_INT);
    $rs->execute();
    $row = $rs->fetch(PDO::FETCH_ASSOC);
    $id_tercero = $row['id_tercero'] ?? null;
    $cmd = null;
} catch (PDOException $e) {
    $response['msg'] = $e->getCode() == 2002
        ? 'Sin Conexion a Mysql (Error: 2002)'
        : 'Error al obtener tercero: ' . $e->getCode();
    echo json_encode($response);
    exit();
}

// -------------------------------------------------------
// 3. Obtener cuenta de credito desde configuracion del owner
//    Parametro: cta_caja en tb_owner_config
// -------------------------------------------------------
$id_cta_credito = null;
try {
    $config = Valores::getOwnerConfig();
    $id_cta_credito = $config['cta_caja'] ?? null;
} catch (PDOException $e) {
    $response['msg'] = $e->getCode() == 2002
        ? 'Sin Conexion a Mysql (Error: 2002)'
        : 'Error al obtener cuenta credito: ' . $e->getCode();
    echo json_encode($response);
    exit();
}

// -------------------------------------------------------
// 4. Limpiar movimientos previos del documento
// -------------------------------------------------------
try {
    $cmd   = \Config\Clases\Conexion::getConexion();
    $query = "DELETE FROM `ctb_libaux` WHERE `id_ctb_doc` = ?";
    $query = $cmd->prepare($query);
    $query->bindParam(1, $id_doc, PDO::PARAM_INT);
    $query->execute();
    $cmd = null;
} catch (PDOException $e) {
    $response['msg'] = $e->getCode() == 2002
        ? 'Sin Conexion a Mysql (Error: 2002)'
        : 'Error al limpiar libaux: ' . $e->getCode();
    echo json_encode($response);
    exit();
}

// -------------------------------------------------------
// 5. Insertar movimientos debito y credito en ctb_libaux
// -------------------------------------------------------
try {
    $cmd = \Config\Clases\Conexion::getConexion();
    $sql = "INSERT INTO `ctb_libaux`
                (`id_ctb_doc`,`id_tercero_api`,`id_cuenta`,`debito`,`credito`,`id_user_reg`,`fecha_reg`)
            VALUES (?, ?, ?, ?, ?, ?, ?)";
    $sql = $cmd->prepare($sql);
    $sql->bindParam(1, $id_doc,         PDO::PARAM_INT);
    $sql->bindParam(2, $id_tercero_ins, PDO::PARAM_INT);
    $sql->bindParam(3, $id_cuenta,      PDO::PARAM_INT);
    $sql->bindParam(4, $debito,         PDO::PARAM_STR);
    $sql->bindParam(5, $credito,        PDO::PARAM_STR);
    $sql->bindParam(6, $iduser,         PDO::PARAM_INT);
    $sql->bindParam(7, $fecha2);

    $totalDebito = 0;

    // 5a. Insertar cada linea de debito (por cuenta contable y tercero)
    foreach ($cuentasDebito as $c) {
        $id_tercero_ins = $c['id_tercero_api'];
        $id_cuenta      = $c['id_cta_contable'];
        $debito         = $c['valor'];
        $credito        = 0;
        $sql->execute();
        if ($sql->rowCount() > 0) {
            $registros++;
            $totalDebito += $debito;
        } else {
            $response['msg'] = $sql->errorInfo()[2];
        }
    }

    // 5b. Insertar linea de credito (TODO: confirmar cuenta de credito)
    if ($id_cta_credito && $totalDebito > 0) {
        $id_tercero_ins = $id_tercero;
        $id_cuenta      = $id_cta_credito;
        $debito         = 0;
        $credito        = $totalDebito;
        $sql->execute();
        if ($sql->rowCount() > 0) {
            $registros++;
        } else {
            $response['msg'] = $sql->errorInfo()[2];
        }
    }
} catch (PDOException $e) {
    $response['msg'] = $e->getCode() == 2002
        ? 'Sin Conexion a Mysql (Error: 2002)'
        : 'Error al insertar libaux: ' . $e->getCode();
}

if ($registros > 0) {
    $response['status'] = 'ok';
    $response['msg']    = 'Se han registrado los movimientos contables';
}
echo json_encode($response);


