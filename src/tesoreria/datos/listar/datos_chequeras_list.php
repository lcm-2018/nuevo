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
                `fin_chequeras`.`fecha`
                , `fin_chequeras`.`id_chequera`
                , `tes_cuentas`.`nombre`
                , `fin_chequeras`.`numero` 
                , `fin_chequeras`.`inicial` AS `en_uso`
                , `fin_chequeras`.`maximo` AS `final`
                , `fin_chequeras`.`contador`
                , `tb_bancos`.`nom_banco`
                , `tb_bancos`.`id_banco`
            FROM
                `fin_chequeras`
                INNER JOIN `tes_cuentas` 
                    ON (`fin_chequeras`.`id_cuenta` = `tes_cuentas`.`id_tes_cuenta`)
                INNER JOIN `tb_bancos` 
                    ON (`tes_cuentas`.`id_banco` = `tb_bancos`.`id_banco`)";
    $rs = $cmd->query($sql);
    $lista = $rs->fetchAll();
    $rs->closeCursor();
    unset($rs);
} catch (PDOException $e) {
    echo $e->getCode() == 2002 ? 'Sin Conexión a Mysql (Error: 2002)' : 'Error: ' . $e->getCode();
}
if (!empty($lista)) {
    foreach ($lista as $lp) {
        $editar = $detalles = $acciones = $borrar = null;
        $fecha = date('Y-m-d', strtotime($lp['fecha']));

        if ($permisos->PermisosUsuario($opciones, 5608, 3) || $id_rol == 1) {
            $id_ctb = $lp['id_chequera'];
            $editar = '<a id ="editar_' . $id_ctb . '" value="' . $id_ctb . '" onclick="editarDatosChequera(' . $id_ctb . ')" class="btn btn-outline-primary btn-xs rounded-circle me-1 shadow"  title="Editar_' . $id_ctb . '"><span class="fas fa-pencil-alt"></span></a>';
            $detalles = '<a value="' . $id_ctb . '" class="btn btn-outline-warning btn-xs rounded-circle me-1 shadow detalles" title="Detalles"><span class="fas fa-eye"></span></a>';
            //si es lider de proceso puede abrir o cerrar documentos

        }
        if ($permisos->PermisosUsuario($opciones, 5608, 3) || $id_rol == 1) {
            $borrar = '<a value="' . $id_ctb . '" onclick="eliminarChequera(' . $id_ctb . ')" class="btn btn-outline-danger btn-xs rounded-circle me-1 shadow "  title="Eliminar"><span class="fas fa-trash-alt"></span></a>';
            $acciones = '<button  class="btn btn-outline-pry btn-sm" type="button" id="dropdownMenuButton" data-bs-toggle="dropdown" aria-haspopup="false" aria-expanded="false">
            ...
            </button>
            <div class="dropdown-menu" aria-labelledby="dropdownMenuButton">
            <a value="' . $id_ctb . '" class="dropdown-item sombra" href="#">Duplicar</a>
            <a value="' . $id_ctb . '" class="dropdown-item sombra" href="#">Parametrizar</a>
            </div>';
        }
        $contador = $lp['contador'] == '' ? $lp['en_uso'] : $lp['contador'];
        $acciones = null;
        $data[] = [

            'fecha' => $fecha,
            'banco' => $lp['nom_banco'],
            'cuenta' => $lp['nombre'],
            'numero' => $lp['numero'],
            'inicial' => $lp['en_uso'] . ' - ' . $lp['final'],
            'en_uso' => $contador,
            'botones' => '<div class="text-center" style="position:relative">' . $editar . $borrar  . $acciones . '</div>',
        ];
    }
} else {
    $data = [];
}
$cmd = null;
$datos = ['data' => $data];


echo json_encode($datos);
