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
                `ctb_doc`.`id_ctb_doc`
                , `ctb_doc`.`id_manu`
                , `ctb_doc`.`fecha`
                , `ctb_doc`.`detalle`
                , `ctb_doc`.`id_tercero`
                , `ctb_doc`.`estado`
                , `tb_terceros`.`nom_tercero`
                , `tb_terceros`.`nit_tercero`
            FROM 
                `ctb_doc`
                LEFT JOIN `tb_terceros`
                    ON (`ctb_doc`.`id_tercero` = `tb_terceros`.`id_tercero_api`)
            WHERE `tipo_doc`='$id_ctb_doc' AND `vigencia` = $_SESSION[vigencia]";
    $rs = $cmd->query($sql);
    $listappto = $rs->fetchAll();
    $rs->closeCursor();
    unset($rs);
} catch (PDOException $e) {
    echo $e->getCode() == 2002 ? 'Sin Conexión a Mysql (Error: 2002)' : 'Error: ' . $e->getCode();
}
if (!empty($listappto)) {
    foreach ($listappto as $lp) {

        $id_ctb = $lp['id_ctb_doc'];
        $estado = $lp['estado'];
        $tercero = !empty($lp['nom_tercero']) ? $lp['nom_tercero'] : '';

        // consultar la suma de debito y credito en la tabla ctb_libaux para el documento
        $sql = "SELECT sum(debito) as debito, sum(credito) as credito FROM ctb_libaux WHERE id_ctb_doc=$id_ctb GROUP BY id_ctb_doc";
        $rs3 = $cmd->query($sql);
        $suma = $rs3->fetch();
        $dif = $suma['debito'] - $suma['credito'];
        if ($dif != 0) {
            $valor_total = 'Error';
        } else {
            $valor_total = number_format($suma['credito'], 2, ',', '.');
        }
        // Consulto el numero de registro presupuestal asociado al documento
        $sql = "SELECT
        `pto_documento`.`id_manu`
        , `pto_documento_detalles`.`id_ctb_doc`
        FROM
        `pto_documento_detalles`
        INNER JOIN `pto_documento` 
            ON (`pto_documento_detalles`.`id_documento` = `pto_documento`.`id_doc`)
        WHERE (`pto_documento_detalles`.`id_ctb_doc` =$id_ctb )
        GROUP BY `pto_documento_detalles`.`id_ctb_doc`;";
        $rs4 = $cmd->query($sql);
        $docment = $rs4->fetch();
        $id_manu_rp = $docment['id_manu'];

        $fecha = date('Y-m-d', strtotime($lp['fecha']));
        // Sumar el valor del crp de la tabla id_pto_mtvo asociado al CDP

        if ((intval($permisos['editar'])) === 1) {
            $editar = '<a id ="editar_' . $id_ctb . '" value="' . $id_ctb . '" onclick="cargarListaDetalle(' . $id_ctb . ')" class="btn btn-outline-primary btn-xs rounded-circle me-1 shadow"  title="Editar_' . $id_ctb . '"><span class="fas fa-pencil-alt "></span></a>';
            $detalles = '<a value="' . $id_ctb . '" class="btn btn-outline-warning btn-xs rounded-circle me-1 shadow detalles" title="Detalles"><span class="fas fa-eye "></span></a>';
            $imprimir = '<a value="' . $id_ctb . '" onclick="imprimirFormatoDoc(' . $lp['id_ctb_doc'] . ')" class="btn btn-outline-success btn-xs rounded-circle me-1 shadow " title="Detalles"><span class="fas fa-print "></span></a>';
            // Acciones teniendo en cuenta el tipo de rol
            //si es lider de proceso puede abrir o cerrar documentos
            $acciones = null;
            if ($rol['id_rol'] == 3 || $rol['id_rol'] == 1) {
                if ($estado == 0) {
                    $cerrar = '<a value="' . $id_ctb . '" class="dropdown-item sombra carga" onclick="cerrarDocumentoCtb(' . $id_ctb . ')" href="#">Cerrar documento</a>';
                } else {
                    $cerrar = '<a value="' . $id_ctb . '" class="dropdown-item sombra carga" onclick="abrirDocumentoCtb(' . $id_ctb . ')" href="#">Abrir documento</a>';
                }
            } else {
                $cerrar = null;
            }
        } else {
            $editar = null;
            $detalles = null;
            $acciones = null;
        }
        if ((intval($permisos['borrar'])) === 1) {
            $borrar = '<a value="' . $id_ctb . '" onclick="eliminarRegistroDoc(' . $id_ctb . ')" class="btn btn-outline-danger btn-xs rounded-circle me-1 shadow "  title="Eliminar"><span class="fas fa-trash-alt "></span></a>';
            $acciones = '<button  class="btn btn-outline-pry btn-sm" type="button" id="dropdownMenuButton" data-toggle="dropdown" aria-haspopup="false" aria-expanded="false">
            ...
            </button>
            <div class="dropdown-menu" aria-labelledby="dropdownMenuButton">
           ' . $cerrar . '
            <a value="' . $id_ctb . '" class="dropdown-item sombra" href="#">Duplicar</a>
            <a value="' . $id_ctb . '" class="dropdown-item sombra" href="#">Parametrizar</a>
            </div>';
        } else {
            $borrar = null;
        }

        if ($estado == 1) {
            $editar = null;
            $borrar = null;
        }
        $data[] = [

            'numero' => $lp['id_manu'],
            'rp' =>  $id_manu_rp,
            'fecha' => $fecha,
            'tercero' => $tercero,
            'valor' =>  '<div class="text-end">' . $valor_total . '</div>',
            'botones' => '<div class="text-center" style="position:relative">' . $editar . $borrar . $imprimir . $acciones .  '</div>',
        ];
    }
} else {
    $data = ['entro' => $sql];
}
$cmd = null;
$datos = ['data' => $data];


echo json_encode($datos);
