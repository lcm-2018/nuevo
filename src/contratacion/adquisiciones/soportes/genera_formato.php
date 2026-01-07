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
use Config\Clases\Plantilla;

$permisos = new Permisos();
$opciones = $permisos->PermisoOpciones($id_user);
$cmd = Conexion::getConexion();

$docx = $form . '.docx';
include 'variables.php';

// Función helper para validar si un valor está vacío
function esVacioParaFormato($valor): bool
{
    if (is_array($valor)) {
        return empty($valor);
    }
    if (is_string($valor)) {
        return trim($valor) === '';
    }
    return false;
}

// Obtener valor de reemplazo: 'xxxx' si está vacío, o el valor original
function obtenerValorFormato($valor, $tipo = null)
{
    if (esVacioParaFormato($valor)) {
        return 'xxxx';
    }
    return $valor;
}

$plantilla = new \PhpOffice\PhpWord\TemplateProcessor($docx);
$marcadores = $plantilla->getVariables();

// Agrupar marcadores por base (sin el sufijo #N)
$marcadoresAgrupados = [];
foreach ($marcadores as $marcador) {
    $base = preg_replace('/#\d+$/', '', $marcador);
    if (!isset($marcadoresAgrupados[$base])) {
        $marcadoresAgrupados[$base] = [];
    }
    $marcadoresAgrupados[$base][] = $marcador;
}

// ESTRATEGIA NUEVA: Procesar PRIMERO los marcadores agrupados
foreach ($marcadoresAgrupados as $varBase => $ocurrencias) {
    // Si existe la variable PHP correspondiente
    if (isset($$varBase)) {
        $valor = $$varBase;

        // Determinar el tipo de dato
        if (is_array($valor)) {
            // Es un array - verificar si se usa en tabla o como texto
            $esTabla = false;

            // Buscar en las variables de BD si está marcado como tipo 2 (tabla)
            foreach ($variables as $v) {
                $var_ = str_replace(['${', '}'], '', $v['variable']);
                if ($var_ === $varBase && $v['tipo'] == '2') {
                    $esTabla = true;
                    break;
                }
            }

            if ($esTabla) {
                // Es una tabla - clonar filas (solo una vez con el marcador base)
                if (in_array($varBase, $ocurrencias)) {
                    // Validar si el array de tabla está vacío
                    if (esVacioParaFormato($valor)) {
                        // Array vacío - reemplazar con xxxx
                        foreach ($ocurrencias as $marcador) {
                            $plantilla->setValue($marcador, 'xxxx');
                        }
                    } else {
                        $plantilla->cloneRowAndSetValues($varBase, $valor);
                    }
                }
            } else {
                // NO es tabla - convertir array a texto
                // Extraer valores del array y unirlos
                $textoArray = '';

                if (!empty($valor)) {
                    $items = [];
                    foreach ($valor as $item) {
                        // Si el array tiene estructura ['clave' => 'valor'], extraer el primer valor
                        if (is_array($item)) {
                            $items[] = reset($item); // Obtiene el primer valor del array asociativo
                        } else {
                            $items[] = $item;
                        }
                    }

                    // Unir items con saltos de línea numerados
                    foreach ($items as $index => $texto) {
                        $numero = $index + 1;
                        $textoArray .= $numero . '. ' . $texto . "\n";
                    }
                } else {
                    // Array vacío - poner mensaje
                    $textoArray = 'N/A';
                }

                // Reemplazar en TODAS las ocurrencias como texto
                foreach ($ocurrencias as $marcador) {
                    $plantilla->setValue($marcador, trim($textoArray));
                }
            }
        } else if (is_string($valor) && strpos($valor, '/') !== false && file_exists($_SERVER['DOCUMENT_ROOT'] . $valor)) {
            // Es una ruta de imagen
            $fullPath = $_SERVER['DOCUMENT_ROOT'] . $valor;
            if (!file_exists($fullPath)) {
                $fullPath = $_SERVER['DOCUMENT_ROOT'] . Plantilla::getHost() . '/assets/images/vacio.png';
            }

            // Insertar imagen en TODAS las ocurrencias
            foreach ($ocurrencias as $marcador) {
                $plantilla->setImageValue($marcador, [
                    'path' => $fullPath,
                    'width' => 200,
                    'height' => 75,
                    'ratio' => false
                ]);
            }
        } else {
            // Es texto simple - REEMPLAZAR EN TODAS LAS OCURRENCIAS
            // Aplicar validación: si está vacío, usar 'xxxx'
            $valorFinal = obtenerValorFormato($valor);
            foreach ($ocurrencias as $marcador) {
                $plantilla->setValue($marcador, $valorFinal);
            }
        }
    }
}

// LUEGO procesar variables de la BD que no hayan sido procesadas aún
foreach ($variables as $v) {
    $var_ = str_replace(['${', '}'], '', $v['variable']);
    $tip_ = $v['tipo'];

    // Si esta variable NO fue procesada en el loop anterior
    if (!isset($$var_)) {
        continue;
    }

    // Buscar si hay marcadores para esta variable que no fueron procesados
    foreach ($marcadores as $marcador) {
        $marcadorBase = preg_replace('/#\d+$/', '', $marcador);

        if ($marcadorBase === $var_ && !isset($marcadoresAgrupados[$var_])) {
            if ($tip_ == '1') {
                // Validar si el valor está vacío para tipo 1 (texto)
                $valorTipo1 = obtenerValorFormato($$var_);
                $plantilla->setValue($marcador, $valorTipo1);
            } else if ($tip_ == '2') {
                if ($marcador === $var_) {
                    // Validar si el array está vacío para tipo 2 (tabla)
                    if (esVacioParaFormato($$var_)) {
                        $plantilla->setValue($marcador, 'xxxx');
                    } else {
                        $plantilla->cloneRowAndSetValues($var_, $$var_);
                    }
                }
            } else if ($tip_ == '3') {
                $docRoot = $_SERVER['DOCUMENT_ROOT'];
                $imgPath = isset($$var_) ? $$var_ : Plantilla::getHost() . '/assets/images/firmas/frm_' . $var_ . '.png';
                $fullPath = $docRoot . $imgPath;

                if (!file_exists($fullPath)) {
                    $fullPath = $docRoot . Plantilla::getHost() . '/assets/images/vacio.png';
                }

                $plantilla->setImageValue($marcador, [
                    'path' => $fullPath,
                    'width' => 200,
                    'height' => 75,
                    'ratio' => false
                ]);
            }
        }
    }
}

$name = 'formato_doc' . date('YmdHis') . '.docx';
$plantilla->saveAs($name);
header("Content-Disposition: attachment; Filename=" . $name);
echo file_get_contents($name);
unlink($name);
