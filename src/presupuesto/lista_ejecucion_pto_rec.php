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

// Consulta nombre de presupuesto
$id_pto_presupuestos = isset($_POST['id_pto']) ? intval($_POST['id_pto']) : 0;
if ($id_pto_presupuestos <= 0) {
    header('Location: lista_presupuestos.php');
    exit();
}
$vigencia = $_SESSION['vigencia'];
$nomPresupuestos = ['nombre' => ''];
try {
    $cmd = \Config\Clases\Conexion::getConexion();
    $sql = "SELECT `nombre` FROM `pto_presupuestos` WHERE `id_pto` = :id_pto";
    $stmt = $cmd->prepare($sql);
    $stmt->bindValue(':id_pto', $id_pto_presupuestos, PDO::PARAM_INT);
    $stmt->execute();
    $nomPresupuestos = $stmt->fetch(PDO::FETCH_ASSOC) ?: ['nombre' => ''];
    $stmt->closeCursor();
    unset($stmt);
    $cmd = null;
} catch (PDOException $e) {
    echo $e->getCode() == 2002 ? 'Sin Conexión a Mysql (Error: 2002)' : 'Error: ' . $e->getCode();
}

$content =
    <<<HTML
    <div class="card w-100">
        <div class="card-header bg-sofia text-white">
            <button class="btn btn-sm me-1 p-0" title="Regresar" onclick="window.location.href='lista_presupuestos.php';"><i class="fas fa-arrow-left fa-lg"></i></button>
            <b>EJECUCION {$nomPresupuestos['nombre']} - RECAUDO PRESUPUESTAL</b>
        </div>
        <div class="card-body p-2 bg-wiev">
            <input type="hidden" id="peReg" value="{$peReg}">
            <input type="hidden" id="id_pto_ppto" value="{$id_pto_presupuestos}">
            <input type="hidden" id="id_pto_presupuestos" value="1">
            
            <div class="row mb-3">
                <div class="mb-3 col-md-1">
                    <div class="input-group">
                        <div class="input-group-text">
                            <input class="form-check-input mt-0" type="checkbox" value="" title="Marcar para filtrar por valor exacto" id="txt_bandera_filtro">
                        </div>
                        <input type="text" class="filtro form-control form-control-sm bg-input" id="txt_idmanu_filtro" placeholder="Id. Manu">
                    </div>
                </div>
                <div class="mb-3 col-md-3">
                    <div class="row">
                        <div class="mb-3 col-md-6">
                            <input type="date" class="form-control form-control-sm bg-input" id="txt_fecini_filtro" name="txt_fecini_filtro" placeholder="Fecha Inicial">
                        </div>
                        <div class="mb-3 col-md-6">
                            <input type="date" class="form-control form-control-sm bg-input" id="txt_fecfin_filtro" name="txt_fecfin_filtro" placeholder="Fecha Final">
                        </div>
                    </div>
                </div>
                <div class="mb-3 col-md-5">
                    <input type="text" class="filtro form-control form-control-sm bg-input" id="txt_objeto_filtro" placeholder="Objeto">
                </div>
                <div class="mb-3 col-md-1">
                    <select class="form-select form-select-sm bg-input" id="sl_estado_filtro">
                        <option value="0">--Estado--</option>
                        <option value="1">Abierto</option>
                        <option value="2">Cerrado</option>
                        <option value="3">Anulado</option>
                    </select>
                </div>
                <div class="mb-3 col-md-1">
                    <a type="button" id="btn_buscar_filtro" class="btn btn-outline-success btn-sm" title="Filtrar">
                        <span class="fas fa-search fa-lg" aria-hidden="true"></span>
                    </a>
                </div>
            </div>

            <div class="table-responsive shadow p-2">
                <table id="tablePptoRec" class="table table-striped table-bordered table-sm table-hover shadow w-100 align-middle">
                    <thead>
                        <tr class="text-center">
                            <th class="bg-sofia" style="width: 8%;">Numero</th>
                            <th class="bg-sofia" style="width: 10%;">Factura</th>
                            <th class="bg-sofia" style="width: 10%;">Fecha</th>
                            <th class="bg-sofia" style="width: 30%;">Tercero</th>
                            <th class="bg-sofia" style="width: 30%;">Objeto</th>
                            <th class="bg-sofia" style="width: 12%;">Valor</th>
                            <th class="bg-sofia" style="min-width: 150px;">Acciones</th>
                        </tr>
                    </thead>
                    <tbody id="modificarPptoRec">
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
