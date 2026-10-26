<?php
session_start();
if (!isset($_SESSION['user'])) {
    header("Location: ../../../index.php");
    exit();
}
include '../../../../config/autoloader.php';
include_once '../../../financiero/consultas.php';
$id_rol = $_SESSION['rol'];
$id_user = $_SESSION['id_user'];

use Config\Clases\Plantilla;
use Src\Common\Php\Clases\Permisos;

$permisos = new Permisos();
$opciones = $permisos->PermisoOpciones($id_user);
// Div de acciones de la lista
ini_set('memory_limit', '-1');
$cod_ctb_doc = $_POST['cod_doc'];
$anulados = $_POST['anulados'];
$id_vigencia = $_SESSION['id_vigencia'];
$start = isset($_POST['start']) ? intval($_POST['start']) : 0;
$length = isset($_POST['length']) ? intval($_POST['length']) : 10;
$limit = "";
if ($length != -1) {
    $limit = "LIMIT $start, $length";
}
$col = $_POST['order'][0]['column'] + 1;
$dir = $_POST['order'][0]['dir'];

//----------- filtros--------------------------

$andwhere = " ";

if (isset($_POST['id_manu']) && $_POST['id_manu']) {
    $andwhere .= $_POST['option'] == 0 ? " AND ctb_doc.id_manu LIKE '%" . $_POST['id_manu'] . "%'" : " AND ctb_doc.id_manu = '" . $_POST['id_manu'] . "'";
}
if (isset($_POST['fec_ini']) && $_POST['fec_ini'] && isset($_POST['fec_fin']) && $_POST['fec_fin']) {
    $andwhere .= " AND ctb_doc.fecha BETWEEN '" . $_POST['fec_ini'] . "' AND '" . $_POST['fec_fin'] . "'";
}
if (isset($_POST['rad']) && $_POST['rad']) {
    $andwhere .= " AND pto_rad.id_manu LIKE '%" . $_POST['rp'] . "%'";
}
if (isset($_POST['tercero']) && $_POST['tercero']) {
    $andwhere .= " AND tb_terceros.nom_tercero LIKE '%" . $_POST['tercero'] . "%'";
}
if (isset($_POST['estado']) && strlen($_POST['estado'])) {
    if ($_POST['estado'] == "-1") {
        $andwhere .= " AND ctb_doc.estado >= " . $_POST['estado'];
    } else {
        $andwhere .= " AND ctb_doc.estado = " . $_POST['estado'];
    }
}

// Definir la parte FROM/WHERE compartida para evitar repetir los JOINs
$fromJoins = "FROM
                `pto_rad`
                INNER JOIN `ctb_doc` 
                    ON (`ctb_doc`.`id_rad` = `pto_rad`.`id_pto_rad`)
                INNER JOIN `ctb_fuente` 
                    ON (`ctb_doc`.`id_tipo_doc` = `ctb_fuente`.`id_doc_fuente`)
                LEFT JOIN `tb_terceros` 
                    ON (`ctb_doc`.`id_tercero` = `tb_terceros`.`id_tercero_api`)
            WHERE (`ctb_fuente`.`cod` = '$cod_ctb_doc' AND `ctb_doc`.`id_vigencia` = $id_vigencia) $andwhere";

$whereBase = "FROM
                `ctb_doc`
                INNER JOIN `ctb_fuente` 
                    ON (`ctb_doc`.`id_tipo_doc` = `ctb_fuente`.`id_doc_fuente`)
                INNER JOIN `pto_rad` 
                    ON (`ctb_doc`.`id_rad` = `pto_rad`.`id_pto_rad`)
            WHERE (`ctb_fuente`.`cod` = '$cod_ctb_doc' AND `ctb_doc`.`id_vigencia` = $id_vigencia)";

$listappto = [];
$totalRecords = 0;
$totalRecordsFilter = 0;

try {
    $cmd = \Config\Clases\Conexion::getConexion();
    
    // 1. Consulta principal paginada
    $sql = "SELECT
                `pto_rad`.`id_manu` AS `id_rad`
                , `ctb_doc`.`id_ctb_doc`
                , `ctb_doc`.`id_manu`
                , `ctb_doc`.`fecha`
                , `ctb_doc`.`detalle`
                , `ctb_doc`.`id_tercero`
                , `tb_terceros`.`nom_tercero`
                , `tb_terceros`.`nit_tercero`
                , `ctb_doc`.`estado`
                , '' AS `pag`
            $fromJoins
            GROUP BY `pto_rad`.`id_pto_rad`, `ctb_doc`.`id_ctb_doc`
            ORDER BY $col $dir $limit";
    $rs = $cmd->query($sql);
    $listappto = $rs ? $rs->fetchAll() : [];
    $rs->closeCursor();
    unset($rs);

    // 2. Conteo total sin filtros adicionales (recordsTotal)
    $sql = "SELECT COUNT(DISTINCT `ctb_doc`.`id_ctb_doc`) AS `total` $whereBase";
    $rs = $cmd->query($sql);
    $total = $rs ? $rs->fetch() : null;
    $totalRecords = $total ? $total['total'] : 0;
    $rs->closeCursor();
    unset($rs);

    // 3. Conteo con filtros aplicados (recordsFiltered)
    if (trim($andwhere) == '') {
        $totalRecordsFilter = $totalRecords;
    } else {
        $sql = "SELECT COUNT(DISTINCT `ctb_doc`.`id_ctb_doc`) AS `total` $fromJoins";
        $rs = $cmd->query($sql);
        $total = $rs ? $rs->fetch() : null;
        $totalRecordsFilter = $total ? $total['total'] : 0;
        $rs->closeCursor();
        unset($rs);
    }
} catch (PDOException $e) {
    echo $e->getCode() == 2002 ? 'Sin Conexión a Mysql (Error: 2002)' : 'Error: ' . $e->getCode();
}

// consultar la fecha de cierre del periodo del módulo de presupuesto 
$fecha_cierre = function_exists('fechaCierre') ? fechaCierre($_SESSION['vigencia'], 55, $cmd) : date('Y-m-d');

// Extraer los IDs de los documentos de la página actual para filtrar consultas secundarias
$ids_pagina = [];
if (!empty($listappto)) {
    foreach ($listappto as $lp) {
        $ids_pagina[] = intval($lp['id_ctb_doc']);
    }
}

$diferenciasLookup = [];
$equivalenteLookup = [];

if (!empty($ids_pagina)) {
    $idsIn = implode(',', $ids_pagina);

    // 4. Consulto la diferencia débito/crédito SOLO para los documentos de la página actual
    try {
        $sql = "SELECT
                    `id_ctb_doc`
                    , SUM(`debito`) AS `debito`
                    , SUM(`credito`) AS `credito`
                    , SUM(`debito` - `credito`) AS `diferencia`
                FROM
                    `ctb_libaux`
                WHERE `id_ctb_doc` IN ($idsIn)
                GROUP BY `id_ctb_doc`";
        $rs = $cmd->query($sql);
        $diferencias = $rs->fetchAll();
        $rs->closeCursor();
        unset($rs);
        // Lookup O(1)
        $diferenciasLookup = array_column($diferencias, null, 'id_ctb_doc');
        unset($diferencias);
    } catch (PDOException $e) {
        echo $e->getCode() == 2002 ? 'Sin Conexión a Mysql (Error: 2002)' : 'Error: ' . $e->getCode();
    }

    // 5. Consultar soportes electrónicos SOLO para los documentos de la página actual
    try {
        $sql = "SELECT
                    `id_soporte`, `id_factura_no`, `shash`, `referencia`, `fecha`
                FROM
                    `seg_soporte_fno`
                WHERE `id_factura_no` IN ($idsIn)";
        $rs = $cmd->query($sql);
        $equivalente = $rs->fetchAll();
        $rs->closeCursor();
        unset($rs);
        // Lookup O(1)
        $equivalenteLookup = array_column($equivalente, null, 'id_factura_no');
        unset($equivalente);
    } catch (PDOException $e) {
        echo $e->getCode() == 2002 ? 'Sin Conexión a Mysql (Error: 2002)' : 'Error: ' . $e->getCode();
    }
}

$data = [];
if (!empty($listappto)) {
    foreach ($listappto as $lp) {
        $valor_debito = 0;
        $id_ctb = $lp['id_ctb_doc'];
        $estado = $lp['estado'];
        $tercero = $lp['nom_tercero'] != '' ? $lp['nom_tercero'] : '---';

        // Acceso O(1) al lookup de diferencias
        if (isset($diferenciasLookup[$id_ctb])) {
            $valor_debito = $diferenciasLookup[$id_ctb]['debito'];
            $dif = $diferenciasLookup[$id_ctb]['diferencia'];
        } else {
            $valor_debito = 0;
            $dif = 0;
        }

        if ($dif != 0) {
            $valor_total = 'Error';
        } else {
            $valor_total = number_format($valor_debito, 2, ',', '.');
        }

        // Consulto el numero de registro presupuestal asociado al documento
        $id_manu_rp = $lp['id_rad'] != '' ? $lp['id_rad'] : '-';

        $fecha = date('Y-m-d', strtotime($lp['fecha']));

        $base = base64_encode('0|' . $cod_ctb_doc . '|' .  $id_ctb);
        $editar = $detalles = $borrar = $imprimir = $enviar = $acciones = $dato = $anular = $cerrar = null;

        if ($permisos->PermisosUsuario($opciones, 5501, 1) || $id_rol == 1) {
            $detalles = '<a text ="' . $base . '" onclick="cargarListaDetalleCtbInvoice2(this)" class="btn btn-outline-warning btn-xs rounded-circle me-1 shadow"  title="Detalles"><span class="fas fa-eye "></span></a>';
        }
        if ($permisos->PermisosUsuario($opciones, 5501, 3) || $id_rol == 1) {
            //$editar = '<a class="btn btn-outline-primary btn-xs rounded-circle me-1 shadow editar" title="Editar" text="' . $id_ctb . '"><span class="fas  fa-pencil-alt "></span></a>';
        }
        if ($permisos->PermisosUsuario($opciones, 5501, 4) || $id_rol == 1) {
            $borrar = '<a value="' . $id_ctb . '" onclick="eliminarRegistroDoc(' . $id_ctb . ')" class="btn btn-outline-danger btn-xs rounded-circle me-1 shadow "  title="Eliminar"><span class="fas fa-trash-alt "></span></a>';
        }
        if ($permisos->PermisosUsuario($opciones, 5501, 5) || $id_rol == 1) {
            if ($fecha < $fecha_cierre) {
                $anular = null;
                $cerrar = null;
            } else if ($lp['pag'] != '') {
                $anular = null;
                $cerrar = null;
            } else {
                if ($estado == 1) {
                    $cerrar = '<a value="' . $id_ctb . '" class="btn btn-outline-info btn-xs rounded-circle me-1 shadow" onclick="CierraDocCtb(' . $id_ctb . ')" title="Cerrar"><span class="fas fa-lock "></span></a>';
                } else {
                    $cerrar = '<a value="' . $id_ctb . '" class="btn btn-outline-secondary btn-xs rounded-circle me-1 shadow" onclick="abrirDocumentoCtb(' . $id_ctb . ')" title="Abrir"><span class="fas fa-unlock "></span></a>';
                }
                $anular = '<a value="' . $id_ctb . '" class="btn btn-outline-danger btn-xs rounded-circle me-1 shadow" onclick="anularDocumentoCont(' . $id_ctb . ');" title="Anular"><span class="fas fa-ban "></span></a>';
            }
        }
        if ($permisos->PermisosUsuario($opciones, 5501, 6) || $id_rol == 1) {
            $imprimir = '<a value="' . $id_ctb . '" onclick="imprimirFormatoDoc(' . $lp['id_ctb_doc'] . ')" class="btn btn-outline-success btn-xs rounded-circle me-1 shadow " title="Detalles"><span class="fas fa-print "></span></a>';
        }

        if ($estado == 2 || $estado == 0) {
            $editar = null;
            $borrar = null;
        }

        $enviar = null;
        $disabled = $estado == 2 ? 'xd' : 'disabled';
        $eq = isset($equivalenteLookup[$id_ctb]) ? $equivalenteLookup[$id_ctb] : null;
        $hash = ($eq && !empty($eq['shash'])) ? $eq['shash'] : '';
        if ($eq && $hash != '') {
            $enviar = '<a onclick="VerSoporteElectronico(' . $eq['id_soporte'] . ')" class="btn btn-outline-danger btn-xs rounded-circle me-1 shadow" title="VER DOCUMENTO"><span class="far fa-file-pdf "></span></a>';
        } else {
            //EnviaDocumentoSoporte(this,1), this es el boton que se envia y 1 para decir que es factura, sino es doc soporte
            $enviar = '<button value="' . $id_ctb . '" onclick="EnviaDocumentoSoporte(this,1)" class="btn btn-outline-info btn-xs rounded-circle me-1 shadow" title="REPORTAR FACTURA" ' . $disabled . '><span class="fas fa-paper-plane "></span></button>';
        }

        if ($estado == 0) {
            $anular = null;
            $enviar = null;
            $cerrar = null;
            $detalles = null;
            $dato = '<span class="badge rounded-pill text-bg-secondary">Anulado</span>';
        }

        $data[] = [

            'numero' => $lp['id_manu'],
            'rp' => $id_manu_rp,
            'fecha' => $fecha,
            'tercero' => $tercero,
            'valor' => '<div class="text-end">' . $valor_total . '</div>',
            'botones' => '<div class="text-center" style="position:relative">' . $editar . $detalles . $borrar . $imprimir . $enviar . $cerrar . $anular . $dato . '</div>',
        ];
    }
}
$cmd = null;
$datos = [
    "data" => $data,
    "recordsTotal" => $totalRecords,
    "recordsFiltered" => $totalRecordsFilter

];


echo json_encode($datos);
