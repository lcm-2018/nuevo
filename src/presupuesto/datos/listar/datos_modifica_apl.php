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
$tipo_doc = $_POST['id_pto_mod'];
$tipo_mod = $_POST['tipo_mod'];
try {
    $cmd = \Config\Clases\Conexion::getConexion();
    $sql = "SELECT
     `pto_documento_detalles`.`id_detalle` as `id_pto_mvto`
    ,`pto_documento_detalles`.`id_documento` as `id_pto_doc`
    , `pto_documento_detalles`.`rubro` as rubro
    , `pto_documento_detalles`.`mov` as mov
    , `pto_cargue`.`nom_rubro` as nom_rubro
    , `pto_documento_detalles`.`valor` as valor
    , `pto_cargue`.`id_pto_presupuestos` as id_pto
    FROM
    `pto_documento_detalles`
    INNER JOIN `pto_cargue` 
        ON (`pto_documento_detalles`.`rubro` = `pto_cargue`.`cod_pptal`)
    WHERE (`pto_documento_detalles`.`id_documento` =$tipo_doc);";
    // Si documento es igual a TRA modificamos la consulta
    $rs = $cmd->query($sql);
    $listappto = $rs->fetchAll(PDO::FETCH_ASSOC);
    $rs->closeCursor();
    unset($rs);
    $cmd = null;
} catch (PDOException $e) {
    echo $e->getCode() == 2002 ? 'Sin Conexión a Mysql (Error: 2002)' : 'Error: ' . $e->getCode();
}
if (!empty($listappto)) {

    foreach ($listappto as $lp) {
        $id_pto = $lp['id_pto_doc'];
        $id_pto_mvto = $lp['id_pto_mvto'];
        // Consultar la suma de los desaplazamientos generados
        $cmd = \Config\Clases\Conexion::getConexion();

        $sql = "SELECT SUM(`valor`) as suma FROM `pto_documento_detalles` WHERE id_auto_dep = $id_pto AND tipo_mov ='DES' AND rubro ='$lp[rubro]';";
        $rs = $cmd->query($sql);
        $suma_des = $rs->fetch();
        $cmd = null;
        if ($lp['id_pto'] == '1') {
            $valor1 = number_format($lp['valor'], 2, '.', ',');
            $valor2 = 0;
        } else {
            $valor1 = number_format($suma_des['suma'], 2, '.', ',');
            $valor2 = number_format($lp['valor'], 2, '.', ',');
            if ($tipo_mod == 'TRA') {
                if ($lp['mov'] == 0) {
                    $valor1 = number_format($lp['valor'], 2, '.', ',');
                    $valor2 = 0;
                } else {
                    $valor2 = number_format($lp['valor'], 2, '.', ',');
                    $valor1 = 0;
                }
            }
        }

        if ((intval($permisos['editar'])) === 1) {
            $editar = '<a value="' . $id_pto . '" onclick="editarAplazamiento(' . $id_pto_mvto . ')" class="btn btn-outline-primary btn-xs rounded-circle me-1 shadow editar" title="Editar apl"><span class="fas fa-pencil-alt "></span></a>';
            $detalles = '<a value="' . $id_pto . '" class="btn btn-outline-warning btn-xs rounded-circle me-1 shadow detalles" title="Detalles"><span class="fas fa-eye "></span></a>';
            $acciones = '<button  class="btn btn-outline-pry btn-sm" type="button" id="dropdownMenuButton" data-bs-toggle="dropdown" aria-haspopup="false" aria-expanded="false">
            ...
            </button>
            <div class="dropdown-menu" aria-labelledby="dropdownMenuButton">
            <a value="' . $id_pto . '" class="dropdown-item sombra carga" href="#">Cargar2 presupuesto</a>
            <a value="' . $id_pto . '" class="dropdown-item sombra modifica" href="#">Modificaciones</a>
            <a value="' . $id_pto . '" class="dropdown-item sombra ejecuta" href="#">Ejecución</a>
            </div>';
        } else {
            $editar = null;
            $detalles = null;
        }
        if ((intval($permisos['borrar'])) === 1) {
            $borrar = '<a value="' . $id_pto . '" class="btn btn-outline-danger btn-xs rounded-circle me-1 shadow borrar" title="Eliminar"><span class="fas fa-trash-alt "></span></a>';
        } else {
            $borrar = null;
        }
        $data[] = [
            'rubro' => $lp['rubro'] . ' - ' . $lp['nom_rubro'],
            'valor' => '<div class="text-end">' . $valor1  . '</div>',
            'valor2' => '<div class="text-end">' . $valor2 . '</div>',
            'botones' => '<div class="text-center" style="position:relative">' . $editar . $borrar . $detalles . $acciones . '</div>',

        ];
    }
} else {
    $data = [];
}

$datos = ['data' => $data];


echo json_encode($datos);
