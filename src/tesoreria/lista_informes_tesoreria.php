<?php
session_start();
if (!isset($_SESSION['user'])) {
    header('Location: ../index.php');
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
        <b>LISTADO DE INFORMES TESORERÍA</b>
    </div>
    <div class="card-body p-2 bg-wiev">
        <ul class="nav nav-tabs mb-3" id="informesTab" role="tablist">
            <li class="nav-item dropdown">
                <a class="nav-link dropdown-toggle" data-bs-toggle="dropdown" href="#" role="button" aria-expanded="false">
                    <i class="fas fa-folder-open me-1"></i> Internos
                </a>
                <ul class="dropdown-menu">
                    <li><a class="dropdown-item" href="javascript:void(0)" id="sl_libros_aux_tesoreria">
                        <i class="fas fa-book me-2"></i> Libros auxiliares de tesorería
                    </a></li>
                    <li><a class="dropdown-item" href="javascript:void(0)" id="sl_libros_aux_bancos">
                        <i class="fas fa-university me-2"></i> Libros auxiliares de bancos
                    </a></li>
                    <li><a class="dropdown-item" href="javascript:void(0)" id="sl_historico_pagos_pendientes">
                        <i class="fas fa-history me-2"></i> Historial de pagos pendientes a terceros
                    </a></li>
                    <li><hr class="dropdown-divider"></li>
                    <li><a class="dropdown-item" href="javascript:void(0)" onclick="cargarReporteTesoreria(4);">
                        <i class="fas fa-users me-2"></i> Consolidado por terceros
                    </a></li>
                    <li><a class="dropdown-item" href="javascript:void(0)" onclick="cargarReporteTesoreria(3);">
                        <i class="fas fa-file-invoice me-2"></i> Reporte por tercero pagos y causaciones pendientes
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
                </ul>
            </li>
        </ul>
        
        <div class="tab-content" id="informesTabContent">
            <div class="tab-pane fade show active" id="internos" role="tabpanel">
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
$plantilla->addScriptFile("{$host}/src/tesoreria/js/informes/informes.js?v=" . date("YmdHis"));
$plantilla->addScriptFile("{$host}/src/tesoreria/js/informes_bancos/informes_bancos.js?v=" . date("YmdHis"));
$plantilla->addScriptFile("{$host}/src/tesoreria/js/funciontesoreria.js?v=" . date("YmdHis"));
$modal = $plantilla->getModal('divModalForms', 'divTamModalForms', 'divForms');
$plantilla->addModal($modal);
$modal = $plantilla->getModal('divModalImp', 'divTamModalImp', 'divImp');
$plantilla->addModal($modal);
$modal = $plantilla->getModal('divModalAux', 'divTamModalAux', 'divFormsAux');
$plantilla->addModal($modal);

echo $plantilla->render();
