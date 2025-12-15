<?php
session_start();
if (!isset($_SESSION['user'])) {
    header('Location: ../../index.php');
    exit();
}
include '../../config/autoloader.php';
$id_rol = $_SESSION['rol'];
$id_user = $_SESSION['id_user'];

use Config\Clases\Plantilla;
use Src\Common\Php\Clases\Permisos;

$permisos = new Permisos();
$opciones = $permisos->PermisoOpciones($id_user);
$peReg = $permisos->PermisosUsuario($opciones, 5401, 2) || $id_rol == 1 ? 1 : 0;
$host = Plantilla::getHost();

// Consulta tipo de presupuesto
$id_pto_presupuestos = $_POST['id_pto'];
try {
    $cmd = \Config\Clases\Conexion::getConexion();
    $sql = "SELECT
                `pto_presupuestos`.`id_pto`
                , `pto_presupuestos`.`descripcion`
                , `pto_presupuestos`.`nombre`
                , `pto_presupuestos`.`estado`
                , `pto_tipo`.`nombre` AS `tipo`
            FROM
                `pto_presupuestos`
                INNER JOIN `pto_tipo` 
                    ON (`pto_presupuestos`.`id_tipo` = `pto_tipo`.`id_tipo`)
            WHERE (`pto_presupuestos`.`id_pto` = $id_pto_presupuestos)";
    $rs = $cmd->query($sql);
    $nomPresupuestos = $rs->fetch();
    $rs->closeCursor();
    unset($rs);
    $cmd = null;
} catch (PDOException $e) {
    echo $e->getCode() == 2002 ? 'Sin Conexión a Mysql (Error: 2002)' : 'Error: ' . $e->getCode();
}

// Generar botones de acción según estado
$botonesAccion = '';
if ($nomPresupuestos['estado'] == 1) {
    if ($peReg == 1) {
        $botonesAccion = <<<HTML
            <button class="btn btn-outline-success btn-sm" id="cargaExcelPto" title="Cargar presupuesto con archivo Excel">
                <i class="far fa-file-excel fa-lg"></i>
            </button>
            <button class="btn btn-outline-primary btn-sm" id="formatoExcelPto" title="Descargar formato cargue de presupuesto">
                <i class="fas fa-download fa-lg"></i>
            </button>
            <button class="btn btn-success btn-sm" id="cerrarPresupuestos">
                CERRAR {$nomPresupuestos['tipo']}
            </button>
HTML;
    }
} else {
    $botonesAccion = <<<HTML
        <button class="btn btn-secondary btn-sm" disabled>
            CERRADO
        </button>
HTML;
}

$content =
    <<<HTML
    <div class="card w-100">
        <div class="card-header bg-sofia text-white">
            <button class="btn btn-sm me-1 p-0" title="Regresar" onclick="window.location.href='lista_presupuestos.php';"><i class="fas fa-arrow-left fa-lg"></i></button>
            <b>LISTADO DE {$nomPresupuestos['nombre']}</b>
        </div>
        <div class="card-body p-2 bg-wiev">
            <input type="hidden" id="peReg" value="{$peReg}">
            <input type="hidden" id="id_pto_ppto" value="{$id_pto_presupuestos}">
            <input type="hidden" id="estadoPresupuesto" value="{$nomPresupuestos['estado']}">
            <input type="hidden" id="idPtoEstado" value="{$id_pto_presupuestos}">
            
            <div class="text-end mb-3">
                {$botonesAccion}
            </div>
            
            <div class="table-responsive shadow p-2">
                <table id="tableCargaPresupuesto" class="table table-striped table-bordered table-sm nowrap table-hover shadow w-100">
                    <thead>
                        <tr class="text-center">
                            <th class="bg-sofia">Rubro</th>
                            <th class="bg-sofia">Detalle</th>
                            <th class="bg-sofia">Tipo</th>
                            <th class="bg-sofia">Valor</th>
                            <th class="bg-sofia">Acciones</th>
                        </tr>
                    </thead>
                    <tbody id="modificarCargaPresupuesto">
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    HTML;

$plantilla = new Plantilla($content, 2);
$plantilla->addCssFile("{$host}/assets/css/jquery-ui.css?v=" . date("YmdHis"));
$plantilla->addScriptFile("{$host}/assets/js/jquery-ui.js?v=" . date("YmdHis"));
$plantilla->addScriptFile("{$host}/src/presupuesto/js/libros_aux_pto/common.js?v=" . date("YmdHis"));
$plantilla->addScriptFile("{$host}/src/presupuesto/js/funcionpresupuesto.js?v=" . date("YmdHis"));
$modal = $plantilla->getModal('divModalForms', 'divTamModalForms', 'divForms');
$plantilla->addModal($modal);
$modal = $plantilla->getModal('divModalReg', 'divTamModalReg', 'divFormsReg');
$plantilla->addModal($modal);
$modal = $plantilla->getModal('divModalImp', 'divTamModalImp', 'divImp');
$plantilla->addModal($modal);
echo $plantilla->render();
