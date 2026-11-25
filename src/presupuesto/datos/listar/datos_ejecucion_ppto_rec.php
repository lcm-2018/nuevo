<?php
session_start();
if (!isset($_SESSION['user'])) {
    header("Location: ../../../index.php");
    exit();
}
include '../../../../config/autoloader.php';
$id_rol = $_SESSION['rol'];
$id_user = $_SESSION['id_user'];

use Src\Common\Php\Clases\Permisos;

$permisos = new Permisos();
$opciones = $permisos->PermisoOpciones($id_user);
include '../../../financiero/consultas.php';
$cmd = \Config\Clases\Conexion::getConexion();

// Consulta fecha de cierre del módulo presupuesto (54)
$fecha_cierre = fechaCierre($_SESSION['vigencia'], 54, $cmd);

$id_pto_presupuestos = isset($_POST['id_ejec']) ? intval($_POST['id_ejec']) : 0;
$start = isset($_POST['start']) ? max(0, intval($_POST['start'])) : 0;
$length = isset($_POST['length']) ? max(1, intval($_POST['length'])) : 25;
$search_value = trim((string)($_POST['search'] ?? ''));

$where = " WHERE `pto_rec`.`id_pto` = :id_pto AND `pto_rec`.`estado` >= 0";
$params = [':id_pto' => $id_pto_presupuestos];

if ($search_value !== '') {
    $where .= " AND (`pto_rec`.`id_manu` LIKE :search OR `pto_rec`.`objeto` LIKE :search OR `pto_rec`.`fecha` LIKE :search OR `pto_rec`.`num_factura` LIKE :search)";
    $params[':search'] = "%{$search_value}%";
}

if (!empty($_POST['id_manu'])) {
    $id_manu = trim((string)$_POST['id_manu']);
    if (!empty($_POST['option']) && intval($_POST['option']) === 1) {
        $where .= " AND `pto_rec`.`id_manu` = :id_manu";
        $params[':id_manu'] = $id_manu;
    } else {
        $where .= " AND `pto_rec`.`id_manu` LIKE :id_manu";
        $params[':id_manu'] = "%{$id_manu}%";
    }
}

if (!empty($_POST['fec_ini']) && !empty($_POST['fec_fin'])) {
    $where .= " AND DATE(`pto_rec`.`fecha`) BETWEEN :fec_ini AND :fec_fin";
    $params[':fec_ini'] = $_POST['fec_ini'];
    $params[':fec_fin'] = $_POST['fec_fin'];
} elseif (!empty($_POST['fec_ini'])) {
    $where .= " AND DATE(`pto_rec`.`fecha`) >= :fec_ini";
    $params[':fec_ini'] = $_POST['fec_ini'];
} elseif (!empty($_POST['fec_fin'])) {
    $where .= " AND DATE(`pto_rec`.`fecha`) <= :fec_fin";
    $params[':fec_fin'] = $_POST['fec_fin'];
}

if (!empty($_POST['objeto'])) {
    $where .= " AND (`pto_rec`.`objeto` LIKE :objeto OR `tb_terceros`.`nom_tercero` LIKE :objeto)";
    $params[':objeto'] = '%' . trim((string)$_POST['objeto']) . '%';
}

if (isset($_POST['estado']) && strlen((string)$_POST['estado']) > 0) {
    $estado = intval($_POST['estado']);
    if ($estado === -1) {
        $where .= " AND `pto_rec`.`estado` >= -1";
    } else {
        $where .= " AND `pto_rec`.`estado` = :estado";
        $params[':estado'] = $estado;
    }
}

$listappto = [];
$totalRecordsFiltered = 0;
$totalRecords = 0;

try {
    $sql = "SELECT
                `pto_rec`.`id_pto_rec`
                , `pto_rec`.`id_manu`
                , `pto_rec`.`fecha`
                , `pto_rec`.`objeto`
                , `pto_rec`.`estado`
                , `pto_rec`.`num_factura`
                , `tb_terceros`.`nom_tercero` AS `tercero`
            FROM `pto_rec`
            LEFT JOIN `tb_terceros`
                ON (`pto_rec`.`id_tercero_api` = `tb_terceros`.`id_tercero_api`)
            {$where}
            ORDER BY `pto_rec`.`id_manu` DESC
            LIMIT :start, :length";
    $stmt = $cmd->prepare($sql);
    foreach ($params as $key => $val) {
        $stmt->bindValue($key, $val, is_int($val) ? PDO::PARAM_INT : PDO::PARAM_STR);
    }
    $stmt->bindValue(':start', $start, PDO::PARAM_INT);
    $stmt->bindValue(':length', $length, PDO::PARAM_INT);
    $stmt->execute();
    $listappto = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $stmt->closeCursor();
    unset($stmt);

    // Consultar valores de pto_rec_detalle únicamente para los registros de la página actual
    $valoresRec = [];
    if (!empty($listappto)) {
        $ids = array_map(static fn($r) => intval($r['id_pto_rec']), $listappto);
        $placeholders = implode(',', $ids);
        $sqlVal = "SELECT
                        `id_pto_rac` AS `id_pto_rec`
                        , SUM(IFNULL(`valor`, 0)) AS `valor`
                        , SUM(IFNULL(`valor_liberado`, 0)) AS `liberado`
                    FROM `pto_rec_detalle`
                    WHERE `id_pto_rac` IN ({$placeholders})
                    GROUP BY `id_pto_rac`";
        $rsVal = $cmd->query($sqlVal);
        if ($rsVal) {
            foreach ($rsVal->fetchAll(PDO::FETCH_ASSOC) as $v) {
                $valoresRec[intval($v['id_pto_rec'])] = [
                    'valor' => (float)$v['valor'],
                    'liberado' => (float)$v['liberado'],
                ];
            }
            $rsVal->closeCursor();
        }
    }

    $sqlCountFiltered = "SELECT COUNT(*) AS `total`
                         FROM `pto_rec`
                         LEFT JOIN `tb_terceros`
                             ON (`pto_rec`.`id_tercero_api` = `tb_terceros`.`id_tercero_api`)
                         {$where}";
    $stmtCF = $cmd->prepare($sqlCountFiltered);
    foreach ($params as $key => $val) {
        $stmtCF->bindValue($key, $val, is_int($val) ? PDO::PARAM_INT : PDO::PARAM_STR);
    }
    $stmtCF->execute();
    $totalRecordsFiltered = intval($stmtCF->fetchColumn() ?: 0);
    $stmtCF->closeCursor();
    unset($stmtCF);

    $sqlCountTotal = "SELECT COUNT(*) AS `total` FROM `pto_rec` WHERE `id_pto` = :id_pto";
    $stmtCT = $cmd->prepare($sqlCountTotal);
    $stmtCT->bindValue(':id_pto', $id_pto_presupuestos, PDO::PARAM_INT);
    $stmtCT->execute();
    $totalRecords = intval($stmtCT->fetchColumn() ?: 0);
    $stmtCT->closeCursor();
    unset($stmtCT);
} catch (PDOException $e) {
    echo json_encode([
        'data' => [],
        'recordsFiltered' => 0,
        'recordsTotal' => 0,
        'error' => $e->getCode() == 2002 ? 'Sin Conexión a Mysql (Error: 2002)' : 'Error: ' . $e->getCode(),
    ]);
    exit();
}

$data = [];
$puedeVerDetalle = $permisos->PermisosUsuario($opciones, 5401, 0) || $id_rol == 1;
$puedeImprimir = $permisos->PermisosUsuario($opciones, 5401, 6) || $id_rol == 1;
$esAdmin = ($id_rol == 1);

if (!empty($listappto)) {
    foreach ($listappto as $lp) {
        $anular = $dato = $borrar = $imprimir = $detalles = null;
        $id_rec = intval($lp['id_pto_rec']);
        $valBruto = $valoresRec[$id_rec]['valor'] ?? 0.0;
        $valLib = $valoresRec[$id_rec]['liberado'] ?? 0.0;
        $val_rec = $valBruto - $valLib;
        $valor_formateado = number_format($val_rec, 2, ',', '.');
        $fecha = date('Y-m-d', strtotime($lp['fecha']));
        $info = base64_encode((string)$id_rec);

        if ($puedeVerDetalle) {
            $detalles = '<a value="' . $id_rec . '" class="btn btn-outline-warning btn-xs rounded-circle me-1 shadow detalles" title="Detalles"><span class="fas fa-eye "></span></a>';
        }
        if ($puedeImprimir) {
            $imprimir = '<a value="' . $id_rec . '" onclick="imprimirFormatoRec(' . $id_rec . ')" class="btn btn-outline-success btn-xs rounded-circle me-1 shadow" title="Imprimir"><span class="fas fa-print "></span></a>';
        }
        if ($esAdmin && $fecha > $fecha_cierre) {
            $anular = '<button text="' . $info . '" class="btn btn-outline-danger btn-xs rounded-circle me-1 shadow" title="Anular" onclick="anulacionPtoRec(this);"><span class="fas fa-ban "></span></button>';
            $borrar = '<a value="' . $id_rec . '" onclick="eliminarRec(' . $id_rec . ')" class="btn btn-outline-danger btn-xs rounded-circle me-1 shadow" title="Eliminar"><span class="fas fa-trash-alt "></span></a>';
        }

        if (intval($lp['estado']) === 0) {
            $borrar = null;
            $detalles = null;
            $anular = null;
            $dato = '<span class="badge rounded-pill text-bg-pill badge-secondary">Anulado</span>';
        }
        if ($val_rec < 0) {
            $imprimir = null;
            $anular = null;
            $borrar = null;
            $dato = null;
        }

        $data[] = [
            'numero' => $lp['id_manu'],
            'factura' => $lp['num_factura'],
            'fecha' => $fecha,
            'tercero' => $lp['tercero'],
            'objeto' => $lp['objeto'],
            'valor' => '<div class="text-end">' . $valor_formateado . '</div>',
            'botones' => '<div class="text-center">' . $detalles . $imprimir . $anular . $borrar . $dato . '</div>',
        ];
    }
}

$cmd = null;
$datos = [
    'data' => $data,
    'recordsFiltered' => $totalRecordsFiltered,
    'recordsTotal' => $totalRecords,
];

echo json_encode($datos);
