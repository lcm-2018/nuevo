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
                , `tb_terceros`.`nom_tercero`
                , `tb_terceros`.`nit_tercero`
                , `ctb_doc`.`estado`
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
    $rs->closeCursor();
    unset($rs);
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
$estado = !empty($estado) ? $estado['estado'] : 0;
$data = [];
$totDebito = 0;
$totCredito = 0;
if (!empty($listappto)) {
    $id_t = [];
    foreach ($listappto as $lp) {
        $id_t[] = $lp['id_tercero_api'];
    }
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
        $tercero = $lp['nom_tercero'] != '' ? $lp['nom_tercero'] : '---';
        $borrar = $editar = $detalles = $registrar = null;
        if ($estado == 1) {
            $detalles = '<a value="' . $id_ctb . '" class="btn btn-outline-warning btn-xs rounded-circle me-1 shadow detalles" title="Detalles"><span class="fas fa-eye"></span></a>';
            if ($permisos->PermisosUsuario($opciones, 5603, 2) || $permisos->PermisosUsuario($opciones, 5501, 2) || $id_rol == 1) {
                $registrar = '<a value="' . $id_ctb . '" onclick="CargarFormularioCrpp(' . $id_ctb . ')" class="text-blue " role="button" title="Detalles"><span>Registrar</span></a>';
            }
            if ($permisos->PermisosUsuario($opciones, 5603, 2) || $permisos->PermisosUsuario($opciones, 5501, 3) || $id_rol == 1) {
                $editar = '<a text="' . $id . '" class="btn btn-outline-primary btn-xs rounded-circle me-1 shadow modificar" title="Editar"><span class="fas fa-pencil-alt"></span></a>';
            }
            if ($permisos->PermisosUsuario($opciones, 5603, 2) || $permisos->PermisosUsuario($opciones, 5501, 4) || $id_rol == 1) {
                $borrar = '<a value="' . $id . '" onclick="eliminarRegistroDetalletesPag(' . $id . ')"class="btn btn-outline-danger btn-xs rounded-circle me-1 shadow" title="Borrar"><span class="fas fa-trash-alt"></span></a>';
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
$valor = $totDebito - $totCredito;
$msg = $valor == 0 ? '<span class="badge bg-success">Correcto</span>' : '<span class="badge bg-danger">Incorrecto</span>';
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
    'tfoot' => $tfoot,
    'estado' => $estado
];

echo json_encode($datos);
