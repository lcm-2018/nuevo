<?php
session_start();
if (!isset($_SESSION['user'])) {
    header("Location: ../../../index.php");
    exit();
}
include '../../../../config/autoloader.php';
$id_rol = $_SESSION['rol'];
$id_user = $_SESSION['id_user'];

use Config\Clases\Plantilla;
use Src\Common\Php\Clases\Permisos;

$permisos = new Permisos();
$opciones = $permisos->PermisoOpciones($id_user);

// Div de acciones de la lista
$id_ctb_doc = $_POST['id_doc'];

try {
    $cmd = \Config\Clases\Conexion::getConexion();
    $sql = "SELECT
                `ctb_libaux`.`id_ctb_libaux`
                , `ctb_libaux`.`id_ctb_doc`
                , `ctb_libaux`.`id_cuenta`
                , `ctb_pgcp`.`cuenta`
                , `ctb_pgcp`.`nombre`
                , `ctb_libaux`.`debito`
                , `ctb_libaux`.`credito`
                , `ctb_libaux`.`id_tercero_api`
                , `ctb_doc`.`estado`
                , `tb_terceros`.`nom_tercero`
                , `tb_terceros`.`nit_tercero`
            FROM
                `ctb_libaux`
                LEFT JOIN `ctb_pgcp` 
                    ON (`ctb_libaux`.`id_cuenta` = `ctb_pgcp`.`id_pgcp`)
                INNER JOIN `ctb_doc` 
                    ON (`ctb_libaux`.`id_ctb_doc` = `ctb_doc`.`id_ctb_doc`)
                LEFT JOIN `tb_terceros`
                    ON (`ctb_libaux`.`id_tercero_api` = `tb_terceros`.`id_tercero_api`)
            WHERE (`ctb_libaux`.`id_ctb_doc` = $id_ctb_doc)";
    $rs = $cmd->query($sql);
    $listappto = $rs->fetchAll();
} catch (PDOException $e) {
    echo $e->getCode() == 2002 ? 'Sin Conexión a Mysql (Error: 2002)' : 'Error: ' . $e->getCode();
}
try {
    $cmd = \Config\Clases\Conexion::getConexion();
    $sql = "SELECT
                `estado`
            FROM `ctb_doc`
            WHERE (`id_ctb_doc` = $id_ctb_doc)";
    $rs = $cmd->query($sql);
    $estado = $rs->fetch();
} catch (PDOException $e) {
    echo $e->getCode() == 2002 ? 'Sin Conexión a Mysql (Error: 2002)' : 'Error: ' . $e->getCode();
}
$estado = !empty($estado) ? $estado['estado'] : 1;
$data = [];
$totDebito = 0;
$totCredito = 0;
if (!empty($listappto)) {
    foreach ($listappto as $lp) {
        $id = $lp['id_ctb_libaux'];
        $id_ctb = $lp['id_ctb_doc'];
        $cuenta = $lp['cuenta'] . ' - ' . $lp['nombre'];
        $deb = $lp['debito'];
        $cred = $lp['credito'];
        $totDebito += $deb;
        $totCredito += $cred;
        $valorDebito =  number_format($deb, 2, '.', ',');
        $valorCredito =  number_format($cred, 2, '.', ',');
        $tercero = !empty($lp['nom_tercero']) ? $lp['nom_tercero'] : '';
        $borrar = $editar = $detalles = $registrar = null;
        if ($estado == 1) {
            $detalles = '<a value="' . $id_ctb . '" class="btn btn-outline-warning btn-xs rounded-circle me-1 shadow detalles" title="Detalles"><span class="fas fa-eye "></span></a>';
            if ($permisos->PermisosUsuario($opciones, 5501, 2) || $id_rol == 1) {
                $registrar = '<a value="' . $id_ctb . '" onclick="CargarFormularioCrpp(' . $id_ctb . ')" class="text-blue " role="button" title="Detalles"><span>Registrar</span></a>';
            }
            if ($permisos->PermisosUsuario($opciones, 5501, 3) || $id_rol == 1) {
                $editar = '<a text="' . $id . '" class="btn btn-outline-primary btn-xs rounded-circle me-1 shadow editar" title="Editar"><span class="fas fa-pencil-alt "></span></a>';
                /*
            $acciones = '<button  class="btn btn-outline-pry btn-sm" type="button" id="dropdownMenuButton" data-toggle="dropdown" aria-haspopup="false" aria-expanded="false">
            ...
            </button>
            <div class="dropdown-menu" aria-labelledby="dropdownMenuButton">
            <a value="' . $id_ctb . '" class="dropdown-item sombra carga" href="#">Cargar presupuesto</a>
            <a value="' . $id_ctb . '" class="dropdown-item sombra" href="#">Another action</a>
            <a value="' . $id_ctb . '" class="dropdown-item sombra" href="#">Something else here</a>
            </div>';*/
            }
            if ($permisos->PermisosUsuario($opciones, 5501, 4) || $id_rol == 1) {
                $borrar = '<a value="' . $id . '" onclick="eliminarRegistroDetalle(' . $id . ')"class="btn btn-outline-danger btn-xs rounded-circle me-1 shadow" title="Borrar"><span class="fas fa-trash-alt "></span></a>';
            }
        }
        $data[] = [
            'cuenta' => $cuenta,
            'tercero' => ltrim($tercero),
            'debito' => '<div class="text-end">' . $valorDebito . '</div>',
            'credito' => '<div class="text-end">' . $valorCredito . '</div>',
            'botones' => '<div class="text-center" style="position:relative">' . $editar . $borrar . '</div>',
        ];
    }
}
$debe = number_format($totDebito, 2, '.', ',');
$haber = number_format($totCredito, 2, '.', ',');
$valor = number_format($totDebito, 2, '.', '') - number_format($totCredito, 2, '.', '');
$msg = $valor == 0 ? '<span class="badge rounded-pill text-bg-success">Correcto</span>' : '<span class="badge rounded-pill text-bg-danger">Incorrecto</span>';
$tfoot = [
    'cuenta' => '1',
    'tercero' => '<div class="text-center"><b>TOTAL</b> (Sumas iguales)</div>',
    'debito' => '<div class="text-end">' . $debe . '</div>',
    'credito' => '<div class="text-end">' . $haber . '</div>',
    'botones' => '<div class="text-center" style="position:relative">' . $msg . '<input type="hidden" id="total" value="' . $valor . '"></div>',
];
$cmd = null;
$datos = [
    'data' => $data,
    'tfoot' => $tfoot
];

echo json_encode($datos);
