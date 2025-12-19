<?php
session_start();
if (!isset($_SESSION['user'])) {
    header('Location: ../../index.php');
    exit();
}
include '../../../config/autoloader.php';
$cmd = \Config\Clases\Conexion::getConexion();
try {
    $sql = "SELECT `id`,`formato`,`nombre` FROM `fin_informes`";
    $res = $cmd->query($sql);
    $formatos = $res->fetchAll();
} catch (PDOException $e) {
    echo $e->getCode() == 2002 ? 'Sin Conexión a Mysql (Error: 2002)' : 'Error: ' . $e->getCode();
}

$opcion = $_POST['periodo'];
$tabla = '';
$body = '';
foreach ($formatos as $formato) {
    $body .= "<tr>
            <td>{$formato['id']}</td>
            <td>{$formato['formato']}</td>
            <td>{$formato['nombre']}</td>
            <td class='text-center'>
                <button class='btn btn-outline-success btn-xs rounded-circle me-1 shadow' onclick='LoadInforme({$formato['id']})' title='{$formato['nombre']}'><i class='fas fa-file-excel'></i></button>
            </td>
        </tr>";
}
$tabla = '<table class="table table-sm table-striped table-bordered table-hover w-100" id="tablaInformesFinancieros">
            <thead>
                <tr class="text-center">
                    <th class="bg-sofia">#</th>
                    <th class="bg-sofia">Formato</th>
                    <th class="bg-sofia">Nombre</th>
                    <th class="bg-sofia">Acción</th>
                </tr>
            </thead>
            <tbody>' . $body . '
            </tbody>
        </table>';
echo $tabla;
