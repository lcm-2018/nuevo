<?php
session_start();

if (!isset($_SESSION['user'])) {
    header("Location: ../../../../index.php");
    exit();
}
$id_adqi = isset($_POST['id_adq']) ? $_POST['id_adq'] : exit('Acción no permitida');
$form = $_POST['form'];
$id_user = $_SESSION['id_user'];
$vigencia = $_SESSION['vigencia'];

include_once '../../../../config/autoloader.php';
require_once '../../../../vendor/autoload.php';

use Src\Common\Php\Clases\Permisos;
use Config\Clases\Conexion;

$permisos = new Permisos();
$opciones = $permisos->PermisoOpciones($id_user);
$cmd = Conexion::getConexion();

$docx = $form . '.docx';
include 'variables.php';

$plantilla = new \PhpOffice\PhpWord\TemplateProcessor($docx);
$marcadores = $plantilla->getVariables();
foreach ($variables as $v) {
    $var_ = str_replace(['${', '}'], '', $v['variable']);
    $tip_ = $v['tipo'];
    if (in_array($var_, $marcadores)) {
        if ($tip_ == '1') {
            $plantilla->setValue($var_, $$var_);
        } else if ($tip_ == '2') {
            $plantilla->cloneRowAndSetValues($var_, $$var_);
        } else if ($tip_ == '3') {
            // Obtener la ruta absoluta del sistema de archivos
            $docRoot = $_SERVER['DOCUMENT_ROOT'];

            // Verificar si existe una variable con el nombre del marcador
            $imgPath = isset($$var_) ? $$var_ : '/nuevo/assets/images/firmas/frm_' . $var_ . '.png';

            // Construir la ruta absoluta completa
            $fullPath = $docRoot . $imgPath;

            // Verificar si el archivo existe, si no usar la imagen vacía
            if (!file_exists($fullPath)) {
                $fullPath = $docRoot . '/nuevo/assets/images/vacio.png';
            }

            // Insertar la imagen en el documento
            $plantilla->setImageValue($var_, [
                'path' => $fullPath,
                'width' => 200,
                'height' => 75,
                'ratio' => false
            ]);
        }
    }
}
$name = 'formato_doc' . date('YmdHis') . '.docx';
$plantilla->saveAs($name);
header("Content-Disposition: attachment; Filename=" . $name);
echo file_get_contents($name);
unlink($name);
