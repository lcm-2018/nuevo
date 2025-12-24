<?php
session_start();
if (!isset($_SESSION['user'])) {
    header("Location: ../index.php");
    exit();
}

include_once '../config/autoloader.php';

use Config\Clases\Plantilla;
use Config\Clases\Sesion;

$host = Plantilla::getHost();
$nombre_usuario = Sesion::User();
$vigencia = Sesion::Vigencia();
$hoy = new DateTime('now', new DateTimeZone('America/Bogota'));
$fecha_actual = $hoy->format('d/m/Y');
$hora_actual = $hoy->format('h:i A');

$content = <<<HTML
<div class="container-fluid p-4">
    <!-- Welcome Banner -->
    <div class="dashboard-welcome">
        <div class="position-relative">
            <h1 class="welcome-title">¡Bienvenido, {$nombre_usuario}!</h1>
            <p class="welcome-subtitle mb-4">
                <i class="far fa-calendar-alt me-2"></i>{$fecha_actual} 
                <i class="far fa-clock ms-3 me-2"></i>{$hora_actual}
                <i class="fas fa-calendar-check ms-3 me-2"></i>Vigencia {$vigencia}
            </p>
            <p class="mb-0 fs-5">Sistema: CRONHIS Financiero.</p>
        </div>
    </div>

    <!-- Info Cards Row -->
    <div class="row g-4 mb-5">
        <div class="col-md-4">
            <div class="info-card">
                <div class="info-label">
                    <i class="fas fa-calendar-alt me-2"></i>Vigencia Actual
                </div>
                <div class="info-value text-primary">{$vigencia}</div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="info-card">
                <div class="info-label">
                    <i class="fas fa-clock me-2"></i>Última Conexión
                </div>
                <div class="info-value text-success">Hoy, {$hora_actual}</div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="info-card">
                <div class="info-label">
                    <i class="fas fa-shield-alt me-2"></i>Estado del Sistema
                </div>
                <div class="info-value text-info">
                    <i class="fas fa-check-circle me-2"></i>Activo
                </div>
            </div>
        </div>
    </div>
</div>
HTML;

$plantilla = new Plantilla($content, 2);
echo $plantilla->render();
