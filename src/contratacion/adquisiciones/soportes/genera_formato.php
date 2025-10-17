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
        }
    }
}

$plantilla->saveAs('formato_doc.docx');
header("Content-Disposition: attachment; Filename=formato_doc.docx");
echo file_get_contents('formato_doc.docx');
unlink('formato_doc.docx');
