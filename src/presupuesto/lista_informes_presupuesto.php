<?php
session_start();
if (!isset($_SESSION['user'])) {
    header('Location: ../../index.php');
    exit();
}
include '../../config/autoloader.php';

use Config\Clases\Plantilla;
use Src\Common\Php\Clases\Permisos;

$id_rol = $_SESSION['rol'];
$id_user = $_SESSION['id_user'];

$permisos = new Permisos();
$opciones = $permisos->PermisoOpciones($id_user);

$host = Plantilla::getHost();

$content = <<<HTML
<div class="card w-100">
    <div class="card-header bg-sofia text-white">
        <button class="btn btn-sm me-1 p-0" title="Regresar" onclick="window.history.back();"><i class="fas fa-arrow-left fa-lg"></i></button>
        <b>LISTADO DE INFORMES PRESUPUESTALES</b>
    </div>
    <div class="card-body p-2 bg-wiev">
        <ul class="nav nav-tabs mb-3" id="informesTab">
            <li class="nav-item dropdown">
                <a class="nav-link dropdown-toggle" data-bs-toggle="dropdown" href="#" role="button" aria-expanded="false">
                    <i class="fas fa-folder-open me-1"></i> Internos
                </a>
                <ul class="dropdown-menu">
                    <li><a class="dropdown-item" href="javascript:void(0)" onclick="cargarReportePresupuesto(1);">
                        <i class="fas fa-arrow-up me-2"></i> Ejecución presupuestal de ingresos
                    </a></li>
                    <li><a class="dropdown-item" href="javascript:void(0)" onclick="cargarReportePresupuesto(2);">
                        <i class="fas fa-arrow-down me-2"></i> Ejecución presupuestal de gastos
                    </a></li>
                    <li><a class="dropdown-item" href="javascript:void(0)" onclick="cargarReportePresupuesto(3);">
                        <i class="fas fa-book me-2"></i> Libros presupuestales
                    </a></li>
                    <li><a class="dropdown-item" href="javascript:void(0)" id="sl_libros_aux_pto">
                        <i class="fas fa-book-open me-2"></i> Libros auxiliares de presupuesto
                    </a></li>
                    <li><a class="dropdown-item" href="javascript:void(0)" onclick="abrirLink(2);">
                        <i class="fas fa-edit me-2"></i> Modificaciones presupuestales ingresos
                    </a></li>
                    <li><a class="dropdown-item" href="javascript:void(0)" onclick="abrirLink(2);">
                        <i class="fas fa-edit me-2"></i> Modificaciones presupuestales gastos
                    </a></li>
                </ul>
            </li>
            <li class="nav-item dropdown">
                <a class="nav-link dropdown-toggle" data-bs-toggle="dropdown" href="#" role="button" aria-expanded="false">
                    <i class="fas fa-landmark me-1"></i> Entidades de control
                </a>
                <ul class="dropdown-menu">
                    <li><a class="dropdown-item" href="javascript:void(0)" onclick="abrirLink(1);">
                        <i class="fas fa-file-contract me-2"></i> Contraloría SIA
                    </a></li>
                    <li><a class="dropdown-item" href="javascript:void(0)" onclick="cargarReportePresupuesto(6);">
                        <i class="fas fa-file-alt me-2"></i> Contraloría General - Cuipo
                    </a></li>
                    <li><a class="dropdown-item" href="javascript:void(0)" onclick="cargarReportePresupuesto(8);">
                        <i class="fas fa-chart-line me-2"></i> Contraloría General - Ejecución
                    </a></li>
                    <li><hr class="dropdown-divider"></li>
                    <li><a class="dropdown-item" href="javascript:void(0)" onclick="abrirLink(13);">
                        <i class="fas fa-eye me-2"></i> Sia Observa
                    </a></li>
                </ul>
            </li>
            <li class="nav-item dropdown">
                <a class="nav-link dropdown-toggle" data-bs-toggle="dropdown" href="#" role="button" aria-expanded="false">
                    <i class="fas fa-hospital me-1"></i> SIHO
                </a>
                <ul class="dropdown-menu">
                    <li><a class="dropdown-item" href="javascript:void(0)" onclick="abrirLink(1);">
                        <i class="fas fa-exchange-alt me-2"></i> Homologación de ingresos
                    </a></li>
                    <li><a class="dropdown-item" href="javascript:void(0)" onclick="abrirLink(2);">
                        <i class="fas fa-exchange-alt me-2"></i> Homologación de gastos
                    </a></li>
                    <li><a class="dropdown-item" href="javascript:void(0)" onclick="cargarReportePresupuesto(5);">
                        <i class="fas fa-file-invoice me-2"></i> Reporte 2193 de ingresos
                    </a></li>
                    <li><a class="dropdown-item" href="javascript:void(0)" onclick="cargarReportePresupuesto(4);">
                        <i class="fas fa-file-invoice me-2"></i> Reporte 2193 de gastos
                    </a></li>
                </ul>
            </li>
            <li class="nav-item dropdown">
                <a class="nav-link dropdown-toggle" data-bs-toggle="dropdown" href="#" role="button" aria-expanded="false">
                    <i class="fas fa-ellipsis-h me-1"></i> Otros
                </a>
                <ul class="dropdown-menu">
                    <li><a class="dropdown-item" href="javascript:void(0)" onclick="cargarReportePresupuesto(7);">
                        <i class="fas fa-ban me-2"></i> Reporte de anulaciones
                    </a></li>
                </ul>
            </li>
        </ul>
        
        <div class="tab-content" id="informesTabContent">
            <div class="tab-pane fade show active" id="informes" role="tabpanel">
                <div class="alert alert-info">
                    <i class="fas fa-info-circle me-2"></i>
                    Seleccione un informe del menú superior para visualizarlo
                </div>
            </div>
        </div>
        
        <div class="mt-3" id="areaReporte"></div>
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
