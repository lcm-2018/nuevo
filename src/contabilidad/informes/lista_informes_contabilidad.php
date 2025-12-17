
<?php
session_start();
if (!isset($_SESSION['user'])) {
    header('Location: ../../../index.php');
    exit();
}
include '../../../config/autoloader.php';

use Config\Clases\Plantilla;
use Src\Common\Php\Clases\Permisos;

$id_rol = $_SESSION['rol'];
$id_user = $_SESSION['id_user'];

$permisos = new Permisos();
$opciones = $permisos->PermisoOpciones($id_user);
$peReg = $permisos->PermisosUsuario($opciones, 5501, 2) || $id_rol == 1 ? 1 : 0;
$host = Plantilla::getHost();

include '../../financiero/consultas.php';

$content = <<<HTML
<div class="card w-100">
    <div class="card-header bg-sofia text-white">
        <button class="btn btn-sm me-1 p-0" title="Regresar" onclick="window.history.back();"><i class="fas fa-arrow-left fa-lg"></i></button>
        <b>LISTADO DE INFORMES CONTABILIDAD</b>
    </div>
    <div class="card-body p-0 bg-wiev">
        <ul class="nav nav-tabs small" id="myTab">
            <li class="nav-item">
                <a class="nav-link dropdown-toggle" data-bs-toggle="dropdown" href="#" role="button" aria-expanded="false">Internos</a>
                <div class="dropdown-menu">
                    <a class="dropdown-item" href="javascript:void(0);" id="sl_libros_aux_bancos">Libros auxiliares</a>
                    <a class="dropdown-item" href="javascript:void(0);" onclick="cargarReporteContable(12);">Balance de prueba</a>
                    <a class="dropdown-item" href="javascript:void(0);" onclick="cargarReportePresupuesto(3);">Mayor y balance</a>
                    <a class="dropdown-item" href="javascript:void(0);" onclick="abrirLink(2);">Estado financieros</a>
                </div>
            </li>
            <li class="nav-item">
                <a class="nav-link dropdown-toggle" data-bs-toggle="dropdown" href="#" role="button" aria-expanded="false">Impuestos y descuentos</a>
                <div class="dropdown-menu">
                    <a class="dropdown-item" href="javascript:void(0);" onclick="cargarReporteContable(21);">Municipales</a>
                    <a class="dropdown-item" href="javascript:void(0);" onclick="cargarReporteContable(22);">DIAN</a>
                    <a class="dropdown-item" href="javascript:void(0);" onclick="cargarReporteContable(24);">Estampillas</a>
                    <a class="dropdown-item" href="javascript:void(0);" onclick="cargarReporteContable(23);">Otros descuentos</a>
                </div>
            </li>
            <li class="nav-item dropdown">
                <a class="nav-link dropdown-toggle" data-bs-toggle="dropdown" href="#" role="button" aria-expanded="false">Entidades de control</a>
                <div class="dropdown-menu">
                    <a class="dropdown-item" href="javascript:void(0);" onclick="cargarReporteContable(1);">Contaduría CGN</a>
                </div>
            </li>
            <li class="nav-item dropdown">
                <a class="nav-link dropdown-toggle" data-bs-toggle="dropdown" href="#" role="button" aria-expanded="false">Certificados</a>
                <div class="dropdown-menu">
                    <a class="dropdown-item" href="javascript:void(0);" onclick="cargarReporteContable(25);">Certificado de ingresos y retenciones</a>
                </div>
            </li>
        </ul>
        
        <div class="tab-content p-2" id="myTabContent">
            <div class="tab-pane active" id="internos" role="tabpanel" aria-labelledby="internos-tab">
            </div>
            <div class="tab-pane" id="profile" role="tabpanel" aria-labelledby="profile-tab">
            </div>
            <div class="tab-pane" id="messages" role="tabpanel" aria-labelledby="messages-tab">
            </div>
            <div class="tab-pane" id="settings" role="tabpanel" aria-labelledby="settings-tab">
            </div>
        </div>
    </div>
    
    <div class="card-body p-2" id="areaReporte">
    </div>
</div>
HTML;

$plantilla = new Plantilla($content, 2);
$plantilla->addCssFile("{$host}/assets/css/jquery-ui.css?v=" . date("YmdHis"));
$plantilla->addScriptFile("{$host}/assets/js/jquery-ui.js?v=" . date("YmdHis"));
$plantilla->addScriptFile("{$host}/src/tesoreria/js/funciontesoreria.js?v=" . date("YmdHis"));
$plantilla->addScriptFile("{$host}/src/contabilidad/js/funcioncontabilidad.js?v=" . date("YmdHis"));

$modal = $plantilla->getModal('divModalForms', 'divTamModalForms', 'divForms');
$plantilla->addModal($modal);
$modal = $plantilla->getModal('divModalReg', 'divTamModalReg', 'divFormsReg');
$plantilla->addModal($modal);
$modal = $plantilla->getModal('divModalImp', 'divTamModalImp', 'divImp');
$plantilla->addModal($modal);
echo $plantilla->render();
