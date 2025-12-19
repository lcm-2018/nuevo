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
function pesos($valor)
{
    return number_format($valor, 0, ',', '.');
}
// Div de acciones de la lista
$id_cdp = $_POST['id_cdp'];
$id_crp = $_POST['id_crp'];
$where = '';
$cmd = \Config\Clases\Conexion::getConexion();

if ($id_crp > '0') {
    $where = "AND `pto_crp_detalle`.`id_pto_crp` = $id_crp";
    try {
        $sql = "SELECT `id_cdp` FROM `pto_crp` WHERE (`id_pto_crp` = $id_crp)";
        $rs = $cmd->query($sql);
        $id_cdp = $rs->fetch();
        $id_cdp = !empty($id_cdp) ? $id_cdp['id_cdp'] : 0;
    } catch (PDOException $e) {
        echo $e->getCode() == 2002 ? 'Sin Conexión a Mysql (Error: 2002)' : 'Error: ' . $e->getCode();
    }
}

try {
    $sql = "SELECT
                `pto_cdp_detalle`.`id_pto_cdp_det`
                , `pto_cdp_detalle`.`id_rubro`
                , (SUM(IFNULL(`pto_cdp_detalle`.`valor`,0)) - SUM(IFNULL(`pto_cdp_detalle`.`valor_liberado`,0))) AS `val_cdp`
                , `pto_cargue`.`cod_pptal`
                , `pto_cargue`.`nom_rubro`
                , IFNULL(`t1`.`val_crp`,0) AS `val_crp`
            FROM
                `pto_cdp_detalle`
                INNER JOIN `pto_cargue` 
                    ON (`pto_cdp_detalle`.`id_rubro` = `pto_cargue`.`id_cargue`)
                LEFT JOIN
                    (SELECT
                        `pto_crp_detalle`.`id_pto_cdp_det`
                        , (SUM(IFNULL(`pto_crp_detalle`.`valor`,0))- SUM(IFNULL(`pto_crp_detalle`.`valor_liberado`,0))) AS `val_crp`
                    FROM
                        `pto_crp_detalle`
                    INNER JOIN `pto_cdp_detalle` 
                        ON (`pto_crp_detalle`.`id_pto_cdp_det` = `pto_cdp_detalle`.`id_pto_cdp_det`)
                    INNER JOIN `pto_crp` 
                        ON (`pto_crp_detalle`.`id_pto_crp` = `pto_crp`.`id_pto_crp`)
                    WHERE (`pto_cdp_detalle`.`id_pto_cdp` = $id_cdp AND `pto_crp`.`estado` > 0 $where)
                    GROUP BY `pto_crp_detalle`.`id_pto_cdp_det`) AS `t1`
                    ON (`t1`.`id_pto_cdp_det` = `pto_cdp_detalle`.`id_pto_cdp_det`)
            WHERE (`pto_cdp_detalle`.`id_pto_cdp` = $id_cdp)
            GROUP BY `pto_cdp_detalle`.`id_pto_cdp_det`";
    $rs = $cmd->query($sql);
    $detalles = $rs->fetchAll(PDO::FETCH_ASSOC);
    $rs->closeCursor();
    unset($rs);
} catch (PDOException $e) {
    echo $e->getCode() == 2002 ? 'Sin Conexión a Mysql (Error: 2002)' : 'Error: ' . $e->getCode();
}
try {
    $cmd = \Config\Clases\Conexion::getConexion();
    $sql = "SELECT `estado` FROM `pto_crp` WHERE (`id_pto_crp` = $id_crp)";
    $rs = $cmd->query($sql);
    $estado = $rs->fetch();
    $estado = !empty($estado) ? $estado['estado'] : 1;
} catch (PDOException $e) {
    echo $e->getCode() == 2002 ? 'Sin Conexión a Mysql (Error: 2002)' : 'Error: ' . $e->getCode();
}
$data = [];
if (!empty($detalles)) {
    foreach ($detalles as $dt) {
        $editar = $detalles = $borrar = null;
        $id = $dt['id_pto_cdp_det'];
        $valor  = $dt['val_cdp'] - $dt['val_crp'];
        if ($valor != 0) {
            if ($id_crp == 0) {
                $valor_input = '<input class="form-control form-control-sm valor-detalle bg-input" type="text" style="text-align:right;border: 0;" name="detalle[' . $id . ']"  id="lp' . $id . '" value="' . $valor . '" min="0" max="' . $valor .  '" onkeyup="NumberMiles(this)">';
            } else {
                $max = $dt['val_crp'] + $valor;
                $valor_input = '<input class="form-control form-control-sm valor-detalle bg-input" type="text" style="text-align:right;border: 0;" name="detalle[' . $id . ']"  id="lp' . $id . '" value="' . $dt['val_crp'] . '" min="0" max="' . $max .  '" onkeyup="NumberMiles(this)">';
            }
        } else {
            $valor_input = pesos($dt['val_crp']);
        }

        // Valor con separador de mailes

        if ($permisos->PermisosUsuario($opciones, 5401, 3) || $id_rol == 1) {
            $editar = '<a onclick=Editar("' . $id . '") class="btn btn-outline-primary btn-xs rounded-circle me-1 shadow" title="Editar"><span class="fas fa-pencil-alt "></span></a>';
        }
        if ($permisos->PermisosUsuario($opciones, 5401, 4) || $id_rol == 1) {
            //$borrar = '<a onclick=Eliminar("' . $id . '") class="btn btn-outline-danger btn-xs rounded-circle me-1 shadow" title="Eliminar"><span class="fas fa-trash-alt "></span></a>';
        }
        if ($valor > 0 || $estado > 0) {
            $data[] = [

                'rubro' => $dt['cod_pptal'] . ' - ' . $dt['nom_rubro'],
                'valor' => '<div class="text-end">' . $valor_input . '</div>',
                'botones' => '<div class="text-center" style="position:relative">' . $editar . $borrar .  '</div>',

            ];
        }
    }
}
$cmd = null;
$datos = ['data' => $data];


echo json_encode($datos);
