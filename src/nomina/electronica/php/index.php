<?php
session_start();
if (!isset($_SESSION['user'])) {
    header("Location: ../../../../index.php");
    exit();
}

include_once '../../../../config/autoloader.php';

use Config\Clases\Plantilla;
use Config\Clases\Sesion;
use Src\Common\Php\Clases\Permisos;

$host = Plantilla::getHost();
$id_rol = Sesion::Rol();
$id_user = Sesion::IdUser();
$opciones = (new Permisos)->PermisoOpciones($id_user);
$vigencia = Sesion::Vigencia();

// Conexión a base de datos
try {
    $cmd = \Config\Clases\Conexion::getConexion();

    $sql = "SELECT id_soporte, nom_meses.id_mes, nom_meses.codigo, nom_meses.nom_mes, 
                   nom_empleado.id_empleado, nom_empleado.no_documento, 
                   nom_empleado.apellido1, nom_empleado.apellido2, 
                   nom_empleado.nombre2, nom_empleado.nombre1, 
                   nom_soporte_ne.shash, nom_soporte_ne.referencia
            FROM nom_meses, nom_soporte_ne
            INNER JOIN nom_empleado ON (nom_soporte_ne.id_empleado = nom_empleado.id_empleado)
            WHERE nom_meses.codigo = nom_soporte_ne.mes 
            AND nom_soporte_ne.anio = :vigencia
            ORDER BY nom_meses.codigo ASC";

    $rs = $cmd->prepare($sql);
    $rs->execute([':vigencia' => $vigencia]);
    $obj = $rs->fetchAll();
} catch (PDOException $e) {
    $obj = [];
    error_log("Error en nómina electrónica: " . $e->getMessage());
}

// Generar el contenido del accordion para cada mes
$accordionContent = '';

for ($i = 1; $i <= 12; $i++) {
    $key = array_search($i, array_column($obj, 'id_mes'));
    if (false !== $key) {
        $mes = $obj[$key];
        $mesId = $mes['codigo'];
        $nomMes = ucfirst(mb_strtolower($mes['nom_mes']));

        // Generar filas de la tabla
        $tableRows = '';
        foreach ($obj as $o) {
            if ($o['id_mes'] == $i) {
                $nombreCompleto = mb_strtoupper($o['apellido1'] . ' ' . $o['apellido2'] . ' ' . $o['nombre1'] . ' ' . $o['nombre2']);
                $permisoReporte = ($id_rol == 1 || (new Permisos)->PermisosUsuario($opciones, 5112, 6));
                $btnSoporte = $permisoReporte && !empty($o['shash'])
                    ? "<a href=\"https://api.taxxa.co/nominaGet.dhtml?hash={$o['shash']}\" target=\"_blank\" class=\"btn btn-outline-danger btn-xs rounded-circle shadow\" title=\"Ver Soporte PDF\"><span class=\"fas fa-file-pdf\"></span></a>"
                    : '';

                $tableRows .= <<<ROW
                    <tr>
                        <td>{$nombreCompleto}</td>
                        <td class="text-center">{$o['no_documento']}</td>
                        <td class="text-center">{$btnSoporte}</td>
                    </tr>
ROW;
            }
        }

        $accordionContent .= <<<ACCORDION
            <div class="accordion-item">
                <h2 class="accordion-header" id="heading{$mesId}">
                    <button class="accordion-button collapsed bg-head-button" type="button" data-bs-toggle="collapse" data-bs-target="#collapse{$mesId}" aria-expanded="false" aria-controls="collapse{$mesId}">
                        <i class="fas fa-calendar-alt me-2 text-primary"></i>
                        {$nomMes}
                    </button>
                </h2>
                <div id="collapse{$mesId}" class="accordion-collapse collapse" aria-labelledby="heading{$mesId}">
                    <div class="accordion-body p-2">
                        <table id="tableNE{$mesId}" class="table table-striped table-bordered table-sm table-hover align-middle dataTableMes" style="width:100%">
                            <thead>
                                <tr>
                                    <th class="bg-sofia text-muted">NOMBRE COMPLETO</th>
                                    <th class="bg-sofia text-muted text-center">NO. DOCUMENTO</th>
                                    <th class="bg-sofia text-muted text-center">SOPORTE</th>
                                </tr>
                            </thead>
                            <tbody>
                                {$tableRows}
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
ACCORDION;
    }
}

// Mensaje cuando no hay datos
if (empty($accordionContent)) {
    $accordionContent = <<<EMPTY
        <div class="text-center py-5">
            <i class="fas fa-folder-open fa-3x text-muted mb-3"></i>
            <h5 class="text-muted">No hay soportes de nómina electrónica</h5>
            <p class="text-secondary">No se encontraron registros para la vigencia {$vigencia}</p>
        </div>
EMPTY;
}

$content = <<<HTML
<div class="card w-100">
    <div class="card-header bg-sofia text-white">
        <a class="btn btn-xs me-1 p-0" title="Regresar" href="{$host}/src/inicio.php"><i class="fas fa-arrow-left fa-lg"></i></a>
        <b>SOPORTES DE NÓMINA ELECTRÓNICA - VIGENCIA {$vigencia}</b>
    </div>
    <div class="card-body p-2 bg-wiev">
        <div class="accordion" id="accordionSoportes">
            {$accordionContent}
        </div>
    </div>
</div>
HTML;

$plantilla = new Plantilla($content, 2);

// Script para inicializar DataTables en el accordion
$plantilla->addScriptInline("
    document.addEventListener('DOMContentLoaded', function() {
        // Inicializar DataTables cuando se abre un accordion
        document.querySelectorAll('.accordion-collapse').forEach(function(collapse) {
            collapse.addEventListener('shown.bs.collapse', function() {
                const table = this.querySelector('.dataTableMes');
                if (table && !$.fn.DataTable.isDataTable(table)) {
                    $(table).DataTable({
                        language: dataTable_es,
                        pageLength: 10,
                        responsive: true
                    });
                }
            });
        });
    });
");

echo $plantilla->render();
