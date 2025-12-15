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

include '../financiero/consultas.php';

$content =
    <<<HTML
    <div class="card w-100">
        <div class="card-header bg-sofia text-white">
            <button class="btn btn-sm me-1 p-0" title="Regresar" onclick="window.history.back();"><i class="fas fa-arrow-left fa-lg"></i></button>
            <b>LISTADO DE INFORMES PRESUPUESTALES</b>
        </div>
        <div class="card-body p-0 bg-wiev">
            <ul class="nav nav-tabs" id="myTab">
                <li class="nav-item">
                    <a class="nav-link dropdown-toggle" data-bs-toggle="dropdown" href="#" role="button" aria-expanded="false">Internos</a>
                    <div class="dropdown-menu">
                        <a class="dropdown-item" href="javascript:void(0);" onclick="cargarReportePresupuesto(1);">Ejecución presupuestal de ingresos</a>
                        <a class="dropdown-item" href="javascript:void(0);" onclick="cargarReportePresupuesto(2);">Ejecución presupuestal de gastos</a>
                        <a class="dropdown-item" href="javascript:void(0);" onclick="cargarReportePresupuesto(3);">Libros presupuestales</a>
                        <a class="dropdown-item" href="javascript:void(0);" id="sl_libros_aux_pto">Libros auxiliares de presupuesto</a>
                        <a class="dropdown-item" href="javascript:void(0);" onclick="abrirLink(2);">Modificaciones presupuestales ingresos</a>
                        <a class="dropdown-item" href="javascript:void(0);" onclick="abrirLink(2);">Modificaciones presupuestales gastos</a>
                    </div>
                </li>
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" data-bs-toggle="dropdown" href="#" role="button" aria-expanded="false">Entidades de control</a>
                    <div class="dropdown-menu">
                        <a class="dropdown-item" href="javascript:void(0);" onclick="abrirLink(1);">Contraloría SIA</a>
                        <a class="dropdown-item" href="javascript:void(0);" onclick="cargarReportePresupuesto(6);">Contraloría General - Cuipo</a>
                        <a class="dropdown-item" href="javascript:void(0);" onclick="cargarReportePresupuesto(8);">Contraloría General - Ejecución</a>
                        <div class="dropdown-divider"></div>
                        <a class="dropdown-item" href="javascript:void(0);" onclick="abrirLink(13);">Sia Observa</a>
                    </div>
                </li>
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" data-bs-toggle="dropdown" href="#" role="button" aria-expanded="false">SIHO</a>
                    <div class="dropdown-menu">
                        <a class="dropdown-item" href="javascript:void(0);" onclick="abrirLink(1);">Homologación de ingresos</a>
                        <a class="dropdown-item" href="javascript:void(0);" onclick="abrirLink(2);">Homologación de gastos</a>
                        <a class="dropdown-item" href="javascript:void(0);" onclick="cargarReportePresupuesto(5);">Reporte 2193 de ingresos</a>
                        <a class="dropdown-item" href="javascript:void(0);" onclick="cargarReportePresupuesto(4);">Reporte 2193 de gastos</a>
                    </div>
                </li>
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" data-bs-toggle="dropdown" href="#" role="button" aria-expanded="false">Otros</a>
                    <div class="dropdown-menu">
                        <a class="dropdown-item" href="javascript:void(0);" onclick="cargarReportePresupuesto(7);">Reporte de anulaciones</a>
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
$plantilla->addScriptFile("{$host}/src/presupuesto/js/libros_aux_pto/common.js?v=" . date("YmdHis"));
$plantilla->addScriptFile("{$host}/src/presupuesto/js/libros_aux_pto/libros_aux_pto.js?v=" . date("YmdHis"));
$plantilla->addScriptFile("{$host}/src/tesoreria/js/funciontesoreria.js?v=" . date("YmdHis"));
$plantilla->addScriptFile("{$host}/src/presupuesto/js/funcionpresupuesto.js?v=" . date("YmdHis"));

$modal = $plantilla->getModal('divModalForms', 'divTamModalForms', 'divForms');
$plantilla->addModal($modal);
$modal = $plantilla->getModal('divModalReg', 'divTamModalReg', 'divFormsReg');
$plantilla->addModal($modal);
$modal = $plantilla->getModal('divModalImp', 'divTamModalImp', 'divImp');
$plantilla->addModal($modal);
echo $plantilla->render();
