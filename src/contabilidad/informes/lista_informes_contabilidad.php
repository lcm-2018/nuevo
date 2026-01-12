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

$host = Plantilla::getHost();

$content = <<<HTML
<div class="card w-100">
    <div class="card-header bg-sofia text-white">
        <a class="btn btn-sm me-1 p-0" title="Regresar" href="{$host}/src/inicio.php"><i class="fas fa-arrow-left fa-lg"></i></a>
        <b>LISTADO DE INFORMES CONTABILIDAD</b>
    </div>
    <div class="card-body p-2 bg-wiev">
        <ul class="nav nav-tabs mb-3" id="informesTab">
            <li class="nav-item dropdown">
                <a class="nav-link dropdown-toggle" data-bs-toggle="dropdown" href="#" role="button" aria-expanded="false">
                    <i class="fas fa-folder-open me-1"></i> Internos
                </a>
                <ul class="dropdown-menu">
                    <li><a class="dropdown-item" href="javascript:void(0)" id="sl_libros_aux_bancos_ctb">
                        <i class="fas fa-book me-2"></i> Libros auxiliares
                    </a></li>
                    <li><a class="dropdown-item" href="javascript:void(0)" onclick="cargarReporteContable(12);">
                        <i class="fas fa-balance-scale me-2"></i> Balance de prueba
                    </a></li>
                    <li><a class="dropdown-item" href="javascript:void(0)" onclick="cargarReportePresupuesto(3);">
                        <i class="fas fa-calculator me-2"></i> Mayor y balance
                    </a></li>
                    <li><a class="dropdown-item" href="javascript:void(0)" onclick="abrirLink(2);">
                        <i class="fas fa-chart-pie me-2"></i> Estado financieros
                    </a></li>
                </ul>
            </li>
            <li class="nav-item dropdown">
                <a class="nav-link dropdown-toggle" data-bs-toggle="dropdown" href="#" role="button" aria-expanded="false">
                    <i class="fas fa-percentage me-1"></i> Impuestos y descuentos
                </a>
                <ul class="dropdown-menu">
                    <li><a class="dropdown-item" href="javascript:void(0)" onclick="cargarReporteContable(21);">
                        <i class="fas fa-city me-2"></i> Municipales
                    </a></li>
                    <li><a class="dropdown-item" href="javascript:void(0)" onclick="cargarReporteContable(22);">
                        <i class="fas fa-landmark me-2"></i> DIAN
                    </a></li>
                    <li><a class="dropdown-item" href="javascript:void(0)" onclick="cargarReporteContable(24);">
                        <i class="fas fa-stamp me-2"></i> Estampillas
                    </a></li>
                    <li><a class="dropdown-item" href="javascript:void(0)" onclick="cargarReporteContable(23);">
                        <i class="fas fa-minus-circle me-2"></i> Otros descuentos
                    </a></li>
                </ul>
            </li>
            <li class="nav-item dropdown">
                <a class="nav-link dropdown-toggle" data-bs-toggle="dropdown" href="#" role="button" aria-expanded="false">
                    <i class="fas fa-university me-1"></i> Entidades de control
                </a>
                <ul class="dropdown-menu">
                    <li><a class="dropdown-item" href="javascript:void(0)" onclick="cargarReporteContable(1);">
                        <i class="fas fa-file-contract me-2"></i> Contaduría CGN
                    </a></li>
                </ul>
            </li>
            <li class="nav-item dropdown">
                <a class="nav-link dropdown-toggle" data-bs-toggle="dropdown" href="#" role="button" aria-expanded="false">
                    <i class="fas fa-certificate me-1"></i> Certificados
                </a>
                <ul class="dropdown-menu">
                    <li><a class="dropdown-item" href="javascript:void(0)" onclick="cargarReporteContable(25);">
                        <i class="fas fa-file-signature me-2"></i> Certificado de ingresos y retenciones
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
$plantilla->addScriptFile("{$host}/src/tesoreria/js/funciontesoreria.js?v=" . date("YmdHis"));
$plantilla->addScriptFile("{$host}/src/contabilidad/js/funcioncontabilidad.js?v=" . date("YmdHis"));
$modal = $plantilla->getModal('divModalForms', 'divTamModalForms', 'divForms');
$plantilla->addModal($modal);
$modal = $plantilla->getModal('divModalReg', 'divTamModalReg', 'divFormsReg');
$plantilla->addModal($modal);
$modal = $plantilla->getModal('divModalImp', 'divTamModalImp', 'divImp');
$plantilla->addModal($modal);

echo $plantilla->render();
