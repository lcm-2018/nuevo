<?php
session_start();
if (!isset($_SESSION['user'])) {
    header("Location: ../../index.php");
    exit();
}

include_once '../../config/autoloader.php';

use Config\Clases\Plantilla;

$host = Plantilla::getHost();

$content = <<<HTML
<div class="card w-100">
    <div class="card-header bg-sofia text-white d-flex justify-content-between align-items-center">
        <div>
            <a class="btn btn-xs me-1 p-0 text-white" title="Regresar" href="{$host}/src/inicio.php"><i class="fas fa-arrow-left fa-lg"></i></a>
            <b>GESTIÓN DE CONTRATOS (CLM)</b>
        </div>
    </div>
    <div class="card-body p-2 bg-wiev">
        <table id="table_contratos" class="table table-striped table-bordered table-sm table-hover align-middle shadow" style="width:100%">
            <thead>
                <tr id="filterRow" class="bg-light">
                    <th class="text-center">
                        <button class="btn btn-sm btn-outline-secondary" type="button" onclick="LimpiarFiltro(table_contratos);" title="Limpiar Filtros">
                            <i class="fas fa-eraser"></i>
                        </button>
                    </th>
                    <th><input type="text" class="form-control form-control-sm" placeholder="No. Contrato" id="filter_Nodoc"></th>
                    <th><input type="text" class="form-control form-control-sm" placeholder="ID Tercero" id="filter_Tercero"></th>
                    <th class="text-center">
                        <select id="filter_Status" class="form-select form-select-sm">
                            <option value="2" class="text-muted">--SELECCIONAR--</option>
                            <option value="Borrador">Borrador</option>
                            <option value="Aprobado">Aprobado</option>
                            <option value="Firmado">Firmado</option>
                            <option value="Anulado">Anulado</option>
                        </select>
                    </th>
                    <th><input type="date" class="form-control form-control-sm" id="filter_Fecha"></th>
                    <th><input type="text" class="form-control form-control-sm" placeholder="Valor" id="filter_Valor"></th>
                    <th class="text-center">
                        <button class="btn btn-sm btn-outline-warning" type="button" onclick="FiltraDatos(table_contratos);" title="Filtrar">
                            <i class="fas fa-filter"></i>
                        </button>
                    </th>
                </tr>
                <tr>
                    <th class="bg-sofia text-muted">ID</th>
                    <th class="bg-sofia text-muted">NO. CONTRATO</th>
                    <th class="bg-sofia text-muted">ID TERCERO</th>
                    <th class="bg-sofia text-muted">ESTADO</th>
                    <th class="bg-sofia text-muted">FECHA INICIO</th>
                    <th class="bg-sofia text-muted">VALOR</th>
                    <th class="bg-sofia text-muted text-center">ACCIONES</th>
                </tr>
            </thead>
            <tbody>
                <!-- Cargado por DataTables -->
            </tbody>
        </table>
    </div>
</div>

<!-- Modal para Crear/Editar Contrato -->
<div class="modal fade" id="modalContrato" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
  <div class="modal-dialog modal-xl">
    <div class="modal-content">
      <form id="formContrato">
      <div class="modal-header bg-sofia text-white">
        <h5 class="modal-title">Redacción de Contrato</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body bg-light">
          <input type="hidden" name="id_contrato" id="id_contrato" value="">
          <div class="row mb-3">
              <div class="col-md-3">
                  <label>Número de Contrato</label>
                  <input type="text" class="form-control form-control-sm" name="numero_contrato" id="numero_contrato" required>
              </div>
              <div class="col-md-3">
                  <label>ID Tercero</label>
                  <input type="number" class="form-control form-control-sm" name="id_tercero" id="id_tercero" required>
              </div>
              <div class="col-md-3">
                  <label>Fecha Inicio</label>
                  <input type="date" class="form-control form-control-sm" name="fecha_inicio" id="fecha_inicio" required>
              </div>
              <div class="col-md-3">
                  <label>Valor ($)</label>
                  <input type="number" step="0.01" class="form-control form-control-sm" name="valor" id="valor" required>
              </div>
          </div>
          <div class="row">
              <div class="col-12">
                  <label>Cuerpo del Contrato</label>
                  <textarea id="contenido_contrato" name="contenido_contrato" class="form-control"></textarea>
              </div>
          </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
        <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Guardar Borrador</button>
      </div>
      </form>
    </div>
  </div>
</div>

<!-- Contenedor para Diff Visual (Normalmente iría en otro Modal) -->
<div id="diffContainer" class="mt-3"></div>

HTML;

$plantilla = new Plantilla($content, 2);

// Cargar librerías necesarias: TinyMCE (CDN para prueba) y Diff2Html
$plantilla->addScriptFile("https://cdnjs.cloudflare.com/ajax/libs/tinymce/6.8.3/tinymce.min.js");
$plantilla->addCssFile("https://cdn.jsdelivr.net/npm/diff2html/bundles/css/diff2html.min.css");
$plantilla->addScriptFile("https://cdn.jsdelivr.net/npm/diff2html/bundles/js/diff2html.min.js");

// Cargar nuestros scripts
$plantilla->addScriptFile("{$host}/src/contrata/js/funciones.js?v=" . date("YmdHis"));
$plantilla->addScriptFile("{$host}/src/contrata/js/detalles.js?v=" . date("YmdHis"));

echo $plantilla->render();
