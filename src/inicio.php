<?php
session_start();
if (!isset($_SESSION['user'])) {
    header("Location: ../index.php");
    exit();
}


include_once '../config/autoloader.php';

use Config\Clases\Plantilla;

$host = Plantilla::getHost();

$content = <<<HTML
<div class="card w-100">
    <div class="card-header bg-sofia text-white">
        PÁGINA DE INICIO
    </div>
    <div class="card-body p-2">
        
    </div>
</div>
HTML;
$plantilla = new Plantilla($content, 2);
echo $plantilla->render();
