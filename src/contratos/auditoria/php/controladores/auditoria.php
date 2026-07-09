<?php
session_start();
if (!isset($_SESSION['user'])) {
    header("Location: ../../../../../index.php");
    exit();
}

$action = isset($_POST['action']) ? $_POST['action'] : exit('Acción no definida.');
$id     = $_POST['id'] ?? 0;

include_once '../../../../../config/autoloader.php';
use Config\Clases\Conexion;
use PDO;

$res   = ['status' => 'error', 'msg' => ''];

switch ($action) {
    case 'ver':
        if ($id > 0) {
            $conexion = Conexion::getConexion();
            $sql = "SELECT estado_anterior, estado_nuevo FROM ctt_auditoria_new WHERE id_auditoria = ?";
            $stmt = $conexion->prepare($sql);
            $stmt->execute([$id]);
            if ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $antes = $row['estado_anterior'] ? json_encode(json_decode($row['estado_anterior']), JSON_PRETTY_PRINT) : 'Sin datos';
                $despues = $row['estado_nuevo'] ? json_encode(json_decode($row['estado_nuevo']), JSON_PRETTY_PRINT) : 'Sin datos';
                
                $html = <<<HTML
                <div class="row">
                    <div class="col-md-6">
                        <h6>Estado Anterior</h6>
                        <pre class="bg-light p-2 border rounded" style="max-height:300px; overflow-y:auto; font-size:12px;">{$antes}</pre>
                    </div>
                    <div class="col-md-6">
                        <h6>Estado Nuevo</h6>
                        <pre class="bg-light p-2 border rounded" style="max-height:300px; overflow-y:auto; font-size:12px;">{$despues}</pre>
                    </div>
                </div>
                HTML;
                
                $res['status'] = 'ok';
                $res['msg'] = $html;
            } else {
                $res['msg'] = 'Registro no encontrado';
            }
        }
        break;
    default:
        $res['msg'] = 'Acción no válida.';
        break;
}

echo json_encode($res);
