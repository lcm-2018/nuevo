<?php
session_start();
if (!isset($_SESSION['user'])) {
    header('Location: ../../../../../index.php');
    exit();
}
include '../../../../../config/autoloader.php';

$id_t = isset($_POST['id_t']) ? $_POST['id_t'] : exit('Acción no permitida');

try {
    $cmd = \Config\Clases\Conexion::getConexion();
    $sql = "SELECT id_deduccion, intereses_vivienda, medicina_prepagada, polizas_salud, ahorros_afc, aportes_pension FROM tb_terceros_deducciones WHERE id_tercero_api = $id_t AND estado = 1 LIMIT 1";
    $rs = $cmd->query($sql);
    $row = $rs->fetch(PDO::FETCH_ASSOC);
    $cmd = null;
} catch (PDOException $e) {
    echo $e->getCode() == 2002 ? 'Sin Conexión a Mysql (Error: 2002)' : 'Error: ' . $e->getMessage();
}

$data = [];
if ($row) {
    // Configurar los 5 campos
    $campos = [
        ['tipo' => 'INTERESES POR CRÉDITO DE VIVIENDA', 'valor' => $row['intereses_vivienda']],
        ['tipo' => 'MEDICINA PREPAGADA', 'valor' => $row['medicina_prepagada']],
        ['tipo' => 'PÓLIZAS DE SALUD', 'valor' => $row['polizas_salud']],
        ['tipo' => 'AHORROS A CUENTAS AFC', 'valor' => $row['ahorros_afc']],
        ['tipo' => 'APORTES VOLUNTARIOS A FONDOS DE PENSIÓN', 'valor' => $row['aportes_pension']]
    ];

    foreach ($campos as $c) {
        $data[] = [
            'tipo'   => $c['tipo'],
            'valor'  => '$ ' . number_format($c['valor'], 2, ',', '.'),
        ];
    }
}

$datos = ['data' => $data];
echo json_encode($datos);
