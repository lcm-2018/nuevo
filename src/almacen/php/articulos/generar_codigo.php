<?php
session_start();
if (!isset($_SESSION['user'])) {
    header("Location: ../../../index.php");
    exit();
}
include '../../../../config/autoloader.php';

$id_articulo = isset($_POST['id_articulo']) ? $_POST['id_articulo'] : '';
$cod_subgrupo = isset($_POST['cod_subgrupo']) ? $_POST['cod_subgrupo'] : '';

try {
    $cmd = \Config\Clases\Conexion::getConexion();

    $stmt = $cmd->prepare("SELECT cod_medicamento FROM far_medicamentos WHERE id_med = :id_articulo");
    $stmt->bindValue(':id_articulo', $id_articulo, PDO::PARAM_INT);
    $stmt->execute();
    $cod_articulo = (string) $stmt->fetchColumn();
    $stmt->closeCursor();

    $pos = strpos($cod_articulo, '-');
    $subgrupo = $pos !== false ? substr($cod_articulo, 0, $pos) : '';

    if ($subgrupo !== $cod_subgrupo) {
        $contador = 1;
        $stmt = $cmd->prepare("SELECT 1 FROM far_medicamentos WHERE cod_medicamento = :cod_articulo AND id_med != :id_articulo LIMIT 1");    
        do {
            $cod_articulo = $cod_subgrupo . '-' . $contador;
            $stmt->bindValue(':cod_articulo', $cod_articulo, PDO::PARAM_STR);
            $stmt->bindValue(':id_articulo', $id_articulo, PDO::PARAM_INT);
            $stmt->execute();
            $existe_codigo = $stmt->fetchColumn() !== false;
            $stmt->closeCursor();

            if ($existe_codigo) {
                $contador++;
            }
        } while ($existe_codigo);
    }
    $cmd = null;

} catch (PDOException $e) {
    echo $e->getCode() == 2002 ? 'Sin Conexión a Mysql (Error: 2002)' : 'Error: ' . $e->getMessage();
}

$data = [
    "mensaje" => "ok",
    "codigo" => $cod_articulo
];

echo json_encode($data);
