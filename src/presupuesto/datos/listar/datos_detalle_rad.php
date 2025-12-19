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
// Div de acciones de la lista
$id_rad = $_POST['id_rad'];
$id_vigencia = $_SESSION['id_vigencia'];
$vigencia = $_SESSION['vigencia'];
$id_pto = $_POST['id_pto'];
try {
    $cmd = \Config\Clases\Conexion::getConexion();
    $sql = "SELECT
                `pto_rad_detalle`.`id_pto_rad_det` AS `id_detalle`
                , `pto_rad_detalle`.`id_pto_rad` AS `id_pto_doc`
                , `pto_rad_detalle`.`valor` AS `valor_deb`
                , `pto_rad_detalle`.`valor_liberado` AS `valor_cred`
                , `pto_cargue`.`id_cargue` AS `id_pto`
                , `pto_cargue`.`cod_pptal` AS `rubro`
                , `pto_cargue`.`nom_rubro` AS `nom_rubro`
            FROM
                `pto_rad_detalle`
                INNER JOIN `pto_cargue` 
                    ON (`pto_rad_detalle`.`id_rubro` = `pto_cargue`.`id_cargue`)
            WHERE (`pto_rad_detalle`.`id_pto_rad` = $id_rad)";
    // Si documento es igual a TRA modificamos la consulta
    $rs = $cmd->query($sql);
    $listappto = $rs->fetchAll(PDO::FETCH_ASSOC);
    $rs->closeCursor();
    unset($rs);
    $cmd = null;
} catch (PDOException $e) {
    echo $e->getCode() == 2002 ? 'Sin Conexión a Mysql (Error: 2002)' : 'Error: ' . $e->getCode();
}
try {
    $cmd = \Config\Clases\Conexion::getConexion();
    $sql = "SELECT `estado` FROM `pto_rad` WHERE (`id_pto_rad` = $id_rad)";
    // Si documento es igual a TRA modificamos la consulta
    $rs = $cmd->query($sql);
    $estado = $rs->fetch(PDO::FETCH_ASSOC);
    $estado = !empty($estado) ? $estado['estado'] : 1;
    $cmd = null;
} catch (PDOException $e) {
    echo $e->getCode() == 2002 ? 'Sin Conexión a Mysql (Error: 2002)' : 'Error: ' . $e->getCode();
}
$id_rb = 0;
$valor = 0;
$nom_rubro = '';
$tp_dt = 0;
$suma = 0;
$resta = 0;
if (!empty($listappto)) {
    foreach ($listappto as $lp) {
        $editar =  $borrar = $detalles = $acciones = null;
        $id_detalle = $lp['id_detalle'];
        $id_pto = $lp['id_pto_doc'];
        $debito = number_format($lp['valor_deb'], 2, ',', '.');
        $suma += $lp['valor_deb'];
        if ($estado < 2) {
            if ($permisos->PermisosUsuario($opciones, 5401, 3) || $id_rol == 1) {
                $editar = '<a value="' . $id_detalle . '" class="btn btn-outline-primary btn-xs rounded-circle me-1 shadow editar" title="Editar detalle"><span class="fas fa-pencil-alt "></span></a>';
            }
            if ($permisos->PermisosUsuario($opciones, 5401, 4) || $id_rol == 1) {
                $borrar = '<a value="' .  $id_detalle . '" class="btn btn-outline-danger btn-xs rounded-circle me-1 shadow borrar" title="Eliminar"><span class="fas fa-trash-alt "></span></a>';
            }
        }
        $data[] = [
            'id' => $id_detalle,
            'rubro' => $lp['rubro'] . ' - ' . $lp['nom_rubro'],
            'valor' => '<div class="text-end">' . $debito . '</div>',
            'botones' => '<div class="text-center" style="position:relative">' . $editar . $detalles . $acciones . $borrar . '</div>',

        ];
    }
}
$suma = number_format($suma, 2, ',', '.');
if ($estado == '1') {
    $rubro = ' <input type="text" id="rubroCod" class="form-control form-control-sm bg-input" value="' . $nom_rubro . '">
            <input type="hidden" name="id_rubroCod" id="id_rubroCod" class="form-control form-control-sm bg-input" value="' . $id_rb . '">
            <input type="hidden" id="tipoRubro" name="tipoRubro" value="' . $tp_dt . '">';
    $debito = '<input type="text" name="valorDeb" id="valorDeb" class="form-control form-control-sm  bg-input" size="6" value="' . $valor . '" style="text-align: right;" onkeyup="NumberMiles(this)">';
    $botones = '<input type="hidden" name="id_pto_mod" id="id_pto_mod" value="' . $id_rad . '">
            <a class="btn btn-outline-warning btn-xs rounded-circle me-1 shadow" title="Ver historial del rubro" onclick="verHistorial(this)"><span class="far fa-list-alt "></span></a>
            <button text="0" class="btn btn-primary btn-sm" onclick="RegDetalleRads(this)">Agregar</button>';
    $data[] = [
        'id' => '2',
        'rubro' => $rubro,
        'valor' => '<div class="text-end">' . $debito . '</div>',
        'botones' => '<div class="text-center">' . $botones . '</div>',
    ];
}
$data[] = [
    'id' => '1',
    'rubro' => '<div class="text-center"><b>TOTAL</b></div>',
    'valor' => '<div class="text-end">' . $suma . '</div>',
    'botones' => '<div class="text-center"></div>',

];
$datos = ['data' => $data];

echo json_encode($datos);
