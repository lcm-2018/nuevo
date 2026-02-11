<?php
session_start();
if (!isset($_SESSION['user'])) {
    header("Location: ../../../../index.php");
    exit();
}

include_once '../../../../config/autoloader.php';

$vigencia =                 $_SESSION['vigencia'];
$start =                    isset($_POST['start']) ? intval($_POST['start']) : 0;
$length =                   isset($_POST['length']) ? intval($_POST['length']) : 10;
$col =                      $_POST['order'][0]['column'] + 1;
$dir =                      $_POST['order'][0]['dir'];
$_POST['search']['id'] =    $_POST['id_empleado'];
$busca =                    $_POST['search'];

$id_rol = $_SESSION['rol'];
$id_user = $_SESSION['id_user'];


use Src\Nomina\Empleados\Php\Clases\Viaticos;
use Src\Common\Php\Clases\Permisos;

$sql        = new Viaticos();
$permisos   = new Permisos();

$opciones =             $permisos->PermisoOpciones($id_user);
$obj =                  $sql->getRegistrosDT($start, $length, $busca, $col, $dir);
$totalRecordsFilter =   $sql->getRegistrosFilter($busca);
$totalRecords =         $sql->getRegistrosTotal($busca);

$datos = [];
if (!empty($obj)) {
    foreach ($obj as $o) {
        $id = $o['id_viatico'];
        $actualizar = $eliminar = $detalles = $imprimir = '';
        // Solo mostrar editar/eliminar si no tiene novedades registradas
        if ($o['estado'] === null) {
            if ($permisos->PermisosUsuario($opciones, 5101, 3) || $id_rol == 1) {
                $actualizar = '<button data-id="' . $id . '" class="btn btn-outline-primary btn-xs rounded-circle shadow me-1 actualizar" title="Actualizar"><span class="fas fa-pencil-alt fa-sm"></span></button>';
            }
            if ($permisos->PermisosUsuario($opciones, 5101, 4) || $id_rol == 1) {
                $eliminar = '<button data-id="' . $id . '" class="btn btn-outline-danger btn-xs rounded-circle shadow me-1 eliminar" title="Eliminar"><span class="fas fa-trash-alt fa-sm"></span></button>';
            }
        }
        if ($o['tipo'] == 1) {
            $detalles = '<button data-id="' . $id . '" class="btn btn-outline-warning btn-xs rounded-circle shadow me-1 detalles" title="Detalles novedades"><span class="fas fa-eye fa-sm"></span></button>';
        }
        if ($permisos->PermisosUsuario($opciones, 5101, 6) || $id_rol == 1) {
            $imprimir = '<button onclick="imprimirReporteViatico(' . $id . ');" class="btn btn-outline-success btn-xs rounded-circle shadow me-1 imprimir" title="Imprimir"><span class="fas fa-print fa-sm"></span></button>';
        }
        // Tipo con texto
        $tipoTexto = ($o['tipo'] == 1) ? 'Anticipo' : 'Legalización';

        // Badge de estado con colores (viene del LEFT JOIN a novedades)
        $estadoBadge = '';
        if ($o['estado'] !== null) {
            switch ((int)$o['estado']) {
                case 1:
                    $estadoBadge = '<span class="badge bg-info">Anticipo</span>';
                    break;
                case 2:
                    $estadoBadge = '<span class="badge bg-success">Aprobado</span>';
                    break;
                case 3:
                    $estadoBadge = '<span class="badge bg-primary">Legalizado</span>';
                    break;
                case 4:
                    $estadoBadge = '<span class="badge bg-danger">Rechazado</span>';
                    break;
                case 5:
                    $estadoBadge = '<span class="badge bg-secondary">Caducado</span>';
                    break;
            }
        }

        $datos[] = [
            'id'            => $id,
            'fecha'         => $o['fec_inicia'],
            'no_resolucion' => $o['no_resolucion'],
            'tipo'          => $tipoTexto,
            'destino'       => $o['destino'],
            'objetivo'      => $o['objetivo'],
            'monto'         => '$ ' . number_format($o['val_total'], 2, ',', '.'),
            'estado'        => '<div class="text-center">' . $estadoBadge . '</div>',
            'acciones'      => '<div class="text-center">' . $actualizar . $eliminar . $detalles . $imprimir . '</div>',
        ];
    }
}
$data = [
    'data'              => $datos,
    'recordsFiltered'   => $totalRecordsFilter,
    'recordsTotal'      => $totalRecords,
];
echo json_encode($data);
