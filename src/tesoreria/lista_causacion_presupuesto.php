<?php
session_start();
if (!isset($_SESSION['user'])) {
    header('Location: ../../index.php');
    exit();
}
include '../../config/autoloader.php';

use Src\Common\Php\Clases\Permisos;

$id_rol = $_SESSION['rol'];
$id_user = $_SESSION['id_user'];

$permisos = new Permisos();
$opciones = $permisos->PermisoOpciones($id_user);

$id_ctb_doc = $_POST['id_doc'] ?? '';
$vigencia = $_SESSION['vigencia'];

try {
    $cmd = \Config\Clases\Conexion::getConexion();
    $sql = "SELECT
                `pto_documento_detalles`.`id_detalle`
                , CONCAT(`pto_documento_detalles`.`rubro`,' ', `pto_cargue`.`nom_rubro`) AS rubros
                , `pto_documento_detalles`.`valor`
                , `pto_documento_detalles`.`id_documento`
            FROM
                `pto_cargue`
                INNER JOIN `pto_documento_detalles` 
                    ON (`pto_cargue`.`cod_pptal` = `pto_documento_detalles`.`rubro`)
            WHERE (`pto_cargue`.`vigencia` = :vigencia
            AND `pto_documento_detalles`.`id_ctb_doc` = :id_ctb_doc)";
    $rs = $cmd->prepare($sql);
    $rs->execute([':vigencia' => $vigencia, ':id_ctb_doc' => $id_ctb_doc]);
    $rubros = $rs->fetchAll();
    $rs->closeCursor();
    unset($rs);
} catch (PDOException $e) {
    echo $e->getCode() == 2002 ? 'Sin Conexión a Mysql (Error: 2002)' : 'Error: ' . $e->getCode();
}

// Consulto el id_pto_doc
$id_pto_doc = '';
try {
    $sql = "SELECT
               `pto_documento_detalles`.`id_documento`
            FROM
                `pto_documento_detalles` 
            WHERE `pto_documento_detalles`.`id_ctb_doc` = :id_ctb_doc LIMIT 1";
    $rs = $cmd->prepare($sql);
    $rs->execute([':id_ctb_doc' => $id_ctb_doc]);
    $datos = $rs->fetch();
    $id_pto_doc = $datos['id_documento'] ?? '';
    $rs->closeCursor();
    unset($rs);
} catch (PDOException $e) {
    echo $e->getCode() == 2002 ? 'Sin Conexión a Mysql (Error: 2002)' : 'Error: ' . $e->getCode();
}

// Verificar permisos de edición
$peEditar = 0;
if ($permisos->PermisosUsuario($opciones, 5601, 2) || $id_rol == 1) {
    $peEditar = 1;
}

// Construir filas de la tabla
$tablaFilas = '';
if (!empty($rubros)) {
    foreach ($rubros as $ce) {
        $id = $ce['id_detalle'];
        $rubroNombre = htmlspecialchars($ce['rubros']);
        $valorFormateado = number_format($ce['valor'], 2, '.', ',');

        $editar = '';
        $acciones = '';
        if ($peEditar === 1) {
            $editar = <<<HTML
            <a value="{$id}" onclick="eliminaRubroIng({$id})" class="btn btn-outline-danger btn-sm btn-circle shadow-gb editar" title="Eliminar">
                <span class="fas fa-trash-alt fa-lg"></span>
            </a>
HTML;
            $acciones = <<<HTML
            <div class="dropdown d-inline-block">
                <button class="btn btn-outline-secondary btn-sm" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                    <i class="fas fa-ellipsis-v"></i>
                </button>
                <ul class="dropdown-menu">
                    <li><a value="{$id}" class="dropdown-item sombra carga" href="#">Historial</a></li>
                </ul>
            </div>
HTML;
        }

        $tablaFilas .= <<<HTML
        <tr id="{$id}">
            <td class="text-start">{$rubroNombre}</td>
            <td class="text-end">{$valorFormateado}</td>
            <td class="text-center">{$editar} {$acciones}</td>
        </tr>
HTML;
    }
}

$content = <<<HTML
<script>
    $('#tableCausacionIng').DataTable({
        language: setIdioma,
        "order": [
            [0, "desc"]
        ]
    });
    $('#tableCausacionIng').wrap('<div class="overflow" />');
</script>

<div class="px-0">
    <div class="shadow">
        <div class="card-header bg-sofia text-white">
            <h5 class="mb-0"><i class="fas fa-file-invoice-dollar me-2"></i>LISTA DE AFECTACION PRESUPUESTAL DE INGRESOS</h5>
        </div>
        <div class="pb-3"></div>
        <div class="px-4">
            <form id="formAddFormaIng">
                <div class="row mb-2">
                    <div class="col-md-8">
                        <label class="small fw-bold">RUBRO:</label>
                    </div>
                    <div class="col-md-2">
                        <label class="small fw-bold">DOCUMENTO:</label>
                    </div>
                    <div class="col-md-2">
                        <label class="small fw-bold">VALOR:</label>
                    </div>
                </div>
                <div class="row mb-3">
                    <div class="col-md-8">
                        <input type="text" name="rubroIng" id="rubroIng" class="form-control form-control-sm bg-input" value="">
                        <input type="hidden" name="id_rubroIng" id="id_rubroIng" value="">
                        <input type="hidden" name="tipoRubro" id="tipoRubro" value="">
                        <input type="hidden" name="id_pto_doc" id="id_pto_doc" value="{$id_pto_doc}">
                    </div>
                    <div class="col-md-2">
                        <input type="text" name="documento" id="documento" class="form-control form-control-sm bg-input" value="" required>
                    </div>
                    <div class="col-md-2">
                        <div class="input-group input-group-sm">
                            <input type="text" name="valor_ing" id="valor_ing" class="form-control bg-input text-end" value="" required onkeyup="valorMiles(id)" ondblclick="valorMovTeroreria('');">
                            <button type="button" class="btn btn-primary btn-sm" onclick="registrarPresupuestoIng()">
                                <i class="fas fa-plus"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </form>
            
            <div class="table-responsive">
                <table id="tableCausacionIng" class="table table-striped table-bordered table-sm table-hover shadow w-100">
                    <thead class="text-center">
                        <tr>
                            <th class="bg-sofia" style="width: 70%;">Rubro</th>
                            <th class="bg-sofia" style="width: 20%;">Valor</th>
                            <th class="bg-sofia" style="width: 10%;">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        {$tablaFilas}
                    </tbody>
                </table>
            </div>
            
            <div class="text-end pt-3">
                <button type="button" class="btn btn-danger btn-sm" data-bs-dismiss="modal">Cerrar</button>
            </div>
        </div>
    </div>
</div>
HTML;

echo $content;
