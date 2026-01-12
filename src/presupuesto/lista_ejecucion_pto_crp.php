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
$peReg =  $permisos->PermisosUsuario($opciones, 5401, 0) || $id_rol == 1 ? 1 : 0;
$host = Plantilla::getHost();

$id_pto_presupuestos = $_POST['id_pto'];
try {
    $cmd = \Config\Clases\Conexion::getConexion();
    $sql = "SELECT `nombre` FROM `pto_presupuestos` WHERE `id_pto` = $id_pto_presupuestos";
    $rs = $cmd->query($sql);
    $nomPresupuestos = $rs->fetch();
} catch (PDOException $e) {
    echo $e->getCode() == 2002 ? 'Sin Conexión a Mysql (Error: 2002)' : 'Error: ' . $e->getCode();
}

$content =
    <<<HTML
    <div class="card w-100">
        <div class="card-header bg-sofia text-white">
            <a class="btn btn-sm me-1 p-0" title="Regresar" href="lista_presupuestos.php"><i class="fas fa-arrow-left fa-lg"></i></a>
            <b>EJECUCION {$nomPresupuestos['nombre']} - REGISTROS PRESUPUESTALES</b>
        </div>
        <div id="accordionCtt" class="card-body p-2 bg-wiev">
            <input type="hidden" id="id_pto_ppto" value="{$id_pto_presupuestos}">
            <div class="d-flex justify-content-center align-items-center gap-2 flex-wrap mb-3">
                <form action="{$_SERVER['PHP_SELF']}" method="POST">
                    <select class="form-select form-select-sm bg-input" id="slcMesHe" name="slcMesHe" onchange="cambiaListado(value)">
                        <option value='1'>CDP - CERTIFICADO DE DISPONIBILIDAD PRESUPUESTAL</option>
                        <option selected value='2'>CRP - CERTIFICADO DE REGISTRO PRESUPUESTAL</option>
                    </select>
                </form>
            </div>
            <div class="row">
                <div class="mb-3 col-md-1">
                    <input type="text" class="filtrocrp form-control form-control-sm bg-input" id="txt_idmanu_filtrocrp" placeholder="Id. Manu CRP">
                </div>
                <div class="mb-3 col-md-1">
                    <input type="text" class="filtrocrp form-control form-control-sm bg-input" id="txt_idmanucdp_filtrocrp" placeholder="Id. Manu CDP">
                </div>
                <div class="mb-3 col-md-3">
                    <div class="row">
                        <div class="mb-3 col-md-6">
                            <input type="date" class="form-control form-control-sm bg-input" id="txt_fecini_filtrocrp" name="txt_fecini_filtro" placeholder="Fecha Inicial">
                        </div>
                        <div class="mb-3 col-md-6">
                            <input type="date" class="form-control form-control-sm bg-input" id="txt_fecfin_filtrocrp" name="txt_fecfin_filtro" placeholder="Fecha Final">
                        </div>
                    </div>
                </div>
                <div class="mb-3 col-md-1">
                    <input type="text" class="filtrocrp form-control form-control-sm bg-input" id="txt_contrato_filtrocrp" placeholder="Contrato">
                </div>
                <div class="mb-3 col-md-1">
                    <input type="text" class="filtrocrp form-control form-control-sm bg-input" id="txt_ccnit_filtrocrp" placeholder="CC / Nit">
                </div>
                <div class="mb-3 col-md-3">
                    <input type="text" class="filtrocrp form-control form-control-sm bg-input" id="txt_tercero_filtrocrp" placeholder="Tercero">
                </div>
                <div class="mb-3 col-md-1">
                    <select class="form-select form-select-sm bg-input" id="sl_estado_filtrocrp">
                        <option value="0">--Estado--</option>
                        <option value="1">Abierto</option>
                        <option value="2">Cerrado</option>
                        <option value="3">Anulado</option>
                    </select>
                </div>
                <div class="mb-3 col-md-1">
                    <a type="button" id="btn_buscar_filtrocrp" class="btn btn-outline-success btn-sm" title="Filtrar">
                        <span class="fas fa-search fa-lg" aria-hidden="true"></span>
                    </a>
                </div>
            </div>
            <div class="table-responsive">
                <table id="tableEjecPresupuestoCrp" class="table table-striped table-bordered table-sm table-hover shadow w-100">
                    <thead>
                        <tr class="text-center">
                            <th class="bg-sofia">Numero</th>
                            <th class="bg-sofia">Cdp</th>
                            <th class="bg-sofia">Fecha</th>
                            <th class="bg-sofia">Contrato</th>
                            <th class="bg-sofia">CC/Nit</th>
                            <th class="bg-sofia">Tercero</th>
                            <th class="bg-sofia">Valor</th>
                            <th class="bg-sofia">Acciones</th>
                        </tr>
                    </thead>
                    <tbody id="modificarEjecPresupuestoCrp">
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
$plantilla->addModal($modal);
$modal = $plantilla->getModal('divModalImp', 'divTamModalImp', 'divImp');
$plantilla->addModal($modal);
echo $plantilla->render();
