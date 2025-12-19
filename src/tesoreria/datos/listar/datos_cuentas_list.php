<?php
session_start();
if (!isset($_SESSION['user'])) {
    header("Location: ../../../index.php");
    exit();
}
include '../../../../config/autoloader.php';


use Src\Common\Php\Clases\Permisos;

$id_rol = $_SESSION['rol'];
$id_user = $_SESSION['id_user'];

$permisos = new Permisos();
$opciones = $permisos->PermisoOpciones($id_user);
// Div de acciones de la lista
$vigencia = $_SESSION['vigencia'];
try {
    $cmd = \Config\Clases\Conexion::getConexion();
    $sql = "SELECT
                `tb_bancos`.`nom_banco`
                , `tb_bancos`.`cod_sia`
                , `tes_tipo_cuenta`.`tipo_cuenta`
                , `tes_cuentas`.`nombre`
                , `tes_cuentas`.`numero`
                , `tes_cuentas`.`id_cuenta`
                , `ctb_pgcp`.`cuenta` AS `cta_contable`
                , `tes_cuentas`.`estado`
                , `tes_cuentas`.`id_tes_cuenta`
                , `fin_cod_fuente`.`nombre` AS `fuente`
                , `fin_cod_fuente`.`codigo` AS `codigo_fte`
            FROM
                `tes_cuentas`
                INNER JOIN `tb_bancos` 
                    ON (`tes_cuentas`.`id_banco` = `tb_bancos`.`id_banco`)
                LEFT JOIN `tes_tipo_cuenta` 
                    ON (`tes_cuentas`.`id_tipo_cuenta` = `tes_tipo_cuenta`.`id_tipo_cuenta`)
                LEFT JOIN `ctb_pgcp` 
                    ON (`tes_cuentas`.`id_cuenta` = `ctb_pgcp`.`id_pgcp`)
                LEFT JOIN `fin_cod_fuente` 
                    ON (`tes_cuentas`.`id_fte` = `fin_cod_fuente`.`id`)";
    $rs = $cmd->query($sql);
    $lista = $rs->fetchAll();
    $rs->closeCursor();
    unset($rs);
} catch (PDOException $e) {
    echo $e->getCode() == 2002 ? 'Sin Conexión a Mysql (Error: 2002)' : 'Error: ' . $e->getCode();
}
if (!empty($lista)) {
    foreach ($lista as $lp) {
        $editar = $borrar = $acciones = $cerrar = null;
        $id_ctb = $lp['id_tes_cuenta'];
        if ($lp['estado'] == 1) {
            $estado = '<span class="badge bg-success">Activa</span>';
            if ($permisos->PermisosUsuario($opciones, 5607, 3) || $id_rol == 1) {
                $editar = '<a id ="editar_' . $id_ctb . '" value="' . $id_ctb . '" onclick="editarDatosCuenta(' . $id_ctb . ')" class="btn btn-outline-primary btn-xs rounded-circle me-1 shadow"  title="Editar_' . $id_ctb . '"><span class="fas fa-pencil-alt"></span></a>';
                //si es lider de proceso puede abrir o cerrar documentos
            }
            if ($permisos->PermisosUsuario($opciones, 5607, 4) || $id_rol == 1) {
                $borrar = '<a value="' . $id_ctb . '" onclick="eliminarCuentaBancaria(' . $id_ctb . ')" class="btn btn-outline-danger btn-xs rounded-circle me-1 shadow "  title="Eliminar"><span class="fas fa-trash-alt"></span></a>';
                $cerrar = '<li><a value="' . $id_ctb . '" class="dropdown-item sombra carga" onclick="cerrarCuentaBco(' . $id_ctb . ')" href="javascript:void(0);">Desactivar cuenta</a></li>';
            }
        } else {
            $estado = '<span class="badge bg-secondary">Inactiva</span>';
            if ($permisos->PermisosUsuario($opciones, 5607, 4) || $id_rol == 1) {
                $cerrar = '<li><a value="' . $id_ctb . '" class="dropdown-item sombra carga" onclick="abrirCuentaBco(' . $id_ctb . ')" href="javascript:void(0);">Activar cuenta</a></li>';
            }
        }

        $acciones = <<<HTML
            <div class="dropdown d-inline-block">
                <button class="btn btn-outline-secondary btn-xs rounded-circle me-1 shadow" type="button" data-bs-toggle="dropdown" data-bs-boundary="window" data-bs-popper-config='{"strategy":"fixed"}' aria-expanded="false">
                    <i class="fas fa-ellipsis-v fa-lg"></i>
                </button>
                <ul class="dropdown-menu dropdown-menu-end">
                    $cerrar
                </ul>
            </div>
        HTML;

        $data[] = [

            'banco' => $lp['nom_banco'],
            'tipo' => $lp['tipo_cuenta'],
            'nombre' => $lp['nombre'],
            'numero' => $lp['numero'],
            'sia' => $lp['cod_sia'],
            'fuente' => $lp['fuente'] != '' ? $lp['fuente'] . ' (' . $lp['codigo_fte'] . ')' : '',
            'cuenta' => $lp['cta_contable'],
            'estado' => '<div class="text-center">' . $estado . '</div>',
            'botones' => '<div class="text-center" style="position:relative">' . $editar . $borrar  . $acciones . '</div>',
        ];
    }
} else {
    $data = [];
}
$cmd = null;
$datos = ['data' => $data];


echo json_encode($datos);
