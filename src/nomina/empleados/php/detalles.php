<?php
session_start();
if (!isset($_SESSION['user'])) {
    header("Location: ../../../../index.php");
    exit();
}

$id_empleado = isset($_POST['id_empleado']) ? $_POST['id_empleado'] : exit('Access denied');

include_once '../../../../config/autoloader.php';

use Config\Clases\Plantilla;
use Src\Common\Php\Clases\Combos;
use Src\Nomina\Empleados\Php\Clases\Empleados;
use Src\Common\Php\Clases\Permisos;

$id_user = $_SESSION['id_user'];
$id_rol = $_SESSION['rol'];

$empleados = new Empleados();
$permisos = new Permisos();
$obj = $empleados->getEmpleadosDT(1, -1, ['filter_id' => $id_empleado, 'filter_Status' => '2'], 1, 'ASC')[0];
$opciones = $permisos->PermisoOpciones($id_user);
$registrar = ($permisos->PermisosUsuario($opciones, 5101, 2) || $id_rol == 1) ? 1 : 0;
$categoria = Combos::getCategoriaTercero();

$host = Plantilla::getHost();

$content = <<<HTML
<div class="card w-100">
    <div class="card-header bg-sofia text-white">
        <b>DETALLES DE EMPLEADOS</b>
    </div>
    <div class="card-body p-2 bg-wiev">
        <div class="accordion" id="accDetallesEmpleado">
            <input type="hidden" id="id_empleado" value="{$obj['id_empleado']}">
            <input type="hidden" id="peReg" value="{$registrar}">
            <div class="accordion-item">
                <h2 class="accordion-header sombra">
                    <button class="accordion-button bg-success-subtle" type="button" data-bs-toggle="collapse" data-bs-target="#divParamsLiq" aria-expanded="true" aria-controls="divParamsLiq">
                        <span class="text-primary"><i class="fas fa-user-tie me-2 fa-lg"></i>VIÑETA. Empleado.</span>
                    </button>
                </h2>
                <div id="divParamsLiq" class="accordion-collapse collapse show">
                    <div class="accordion-body bg-wiev">
                        <div class=" px-3 shadow rounded">
                            <div class="card-body">
                                <div class="row mb-0 border border-bottom-0 rounded-top">
                                    <div class="col-md-3 border-end">
                                        <span class="text-muted small">IDENTIFICACIÓN</span><br>
                                        <span class="fw-bold">{$obj['no_documento']}</span>
                                    </div>
                                    <div class="col-md-5 border-end">
                                        <span class="text-muted small">NOMBRE COMPLETO</span><br>
                                        <span class="fw-bold">{$obj['nombre']}</span>
                                    </div>
                                    <div class="col-md-2 border-end">
                                        <span class="text-muted small">DEPARTAMENTO</span><br>
                                        <span class="fw-bold">{$obj['nom_departamento']}</span>
                                    </div>
                                    <div class="col-md-2">
                                        <span class="text-muted small">MUNICIPIO</span><br>
                                        <span class="fw-bold">{$obj['nom_municipio']}</span>
                                    </div>
                                </div>
                                <div class="row mb-0 border rounded-bottom">
                                    <div class="col-md-3 border-end">
                                        <span class="text-muted small">DIRECCIÓN</span><br>
                                        <span class="fw-bold">{$obj['direccion']}</span>
                                    </div>
                                    <div class="col-md-4 border-end">
                                        <span class="text-muted small">CORREO</span><br>
                                        <span class="fw-bold">{$obj['correo']}</span>
                                    </div>
                                    <div class="col-md-2 border-end">
                                        <span class="text-muted small">CONTACTO</span><br>
                                        <span class="fw-bold">{$obj['telefono']}</span>
                                    </div>
                                    <div class="col-md-3">
                                        <span class="text-muted small">CARGO ACTUAL</span><br>
                                        <span class="fw-bold">{$obj['descripcion_carg']}</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="accordion-item">
                <h2 class="accordion-header sombra">
                    <button class="accordion-button collapsed bg-success-subtle" type="button" data-bs-toggle="collapse" data-bs-target="#divContratos" aria-expanded="false" aria-controls="divContratos">
                        <span class="text-success"><i class="fas fa-file-signature me-2 fa-lg"></i>VIÑETA. Contratos.</span>
                    </button>
                </h2>
                <div id="divContratos" class="accordion-collapse collapse">
                    <div class="accordion-body bg-wiev">
                        <table id="tableContratosEmpleado" class="table table-striped table-bordered table-sm table-hover align-middle shadow" style="width:100%">
                            <thead>
                                <tr>
                                    <th class="bg-sofia text-muted">#</th>
                                    <th class="bg-sofia text-muted">INICIA</th>
                                    <th class="bg-sofia text-muted">TERMINA</th>
                                    <th class="bg-sofia text-muted">SALARIO</th>
                                    <th class="bg-sofia text-muted">ESTADO</th>
                                    <th class="bg-sofia text-muted">ACCIONES</th>
                                </tr>
                            </thead>
                            <tbody id="modificaContratosEmpleado">
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
             <div class="accordion-item">
                <h2 class="accordion-header sombra">
                    <button class="accordion-button collapsed bg-success-subtle" type="button" data-bs-toggle="collapse" data-bs-target="#divSegSocial" aria-expanded="false" aria-controls="divSegSocial">
                        <span class="text-info"><i class="fas fa-hospital-user me-2 fa-lg"></i>VIÑETA. Seguridad Social.</span>
                    </button>
                </h2>
                <div id="divSegSocial" class="accordion-collapse collapse">
                    <div class="accordion-body bg-wiev">
                        <table id="tableSegSocial" class="table table-striped table-bordered table-sm table-hover align-middle shadow" style="width:100%">
                            <thead>
                                <tr id="filterRow" class="bg-light">
                                    <th class="text-center">
                                        <button class="btn btn-sm btn-outline-secondary" type="button" onclick="LimpiarFiltro(tableSegSocial);" title="Limpiar Filtros">
                                            <i class="fas fa-eraser"></i>
                                        </button>
                                    </th>
                                    <th>
                                        <select id="filter_tipo" class="form-select form-select-sm">
                                            {$categoria}
                                        </select>
                                    </th>
                                    <th><input type="text" class="form-control form-control-sm" placeholder="Nombre" id="filter_nombre"></th>
                                    <th><input type="text" class="form-control form-control-sm" placeholder="NIT" id="filter_nit"></th>
                                    <th><input type="text" class="form-control form-control-sm" placeholder="Fecha de afiliación" id="filter_afiliacion"></th>
                                    <th><input type="text" class="form-control form-control-sm" placeholder="Fecha de retiro" id="filter_retiro"></th>
                                    <th><input type="text" class="form-control form-control-sm" placeholder="Riesgo Laboral" id="filter_riesgo"></th>
                                    <th>
                                        <button class="btn btn-sm btn-outline-warning" type="button" onclick="FiltraDatos(tableSegSocial);" title="Filtrar">
                                            <i class="fas fa-filter"></i>
                                        </button>
                                    </th>

                                </tr>
                                <tr>
                                    <th class="text-center bg-sofia">#</th>
                                    <th class="text-center bg-sofia">TIPO</th>
                                    <th class="text-center bg-sofia">NOMBRE</th>
                                    <th class="text-center bg-sofia">NIT</th>
                                    <th class="text-center bg-sofia">AFILIACIÓN</th>
                                    <th class="text-center bg-sofia">RETIRO</th>
                                    <th class="text-center bg-sofia">RIESGO</th>
                                    <th class="text-center bg-sofia">ACCIONES</th>
                                </tr>
                            </thead>
                            <tbody id="modificaSegSocial">
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <div class="accordion-item">
                <h2 class="accordion-header sombra">
                    <button class="accordion-button collapsed bg-success-subtle" type="button" data-bs-toggle="collapse" data-bs-target="#divDevengados" aria-expanded="false" aria-controls="divDevengados">
                        <span class="text-muted"><i class="fas fa-user-plus me-2 fa-lg"></i>VIÑETA. Devengados.</span>
                    </button>
                </h2>
                <div id="divDevengados" class="accordion-collapse collapse">
                    <div class="accordion-body bg-wiev">
                        <div class="accordion">
                            <div class="accordion-item">
                                <h2 class="accordion-header sombra">
                                    <button class="accordion-button collapsed bg-success-subtle" type="button" data-bs-toggle="collapse" data-bs-target="#divIncapacidades" aria-expanded="false" aria-controls="divIncapacidades">
                                        <span class="text-primary"></i>EACII. Incapacidades.</span>
                                    </button>
                                </h2>
                                <div id="divIncapacidades" class="accordion-collapse collapse">
                                    <div class="accordion-body bg-wiev">
                                        <table id="tableIncapacidades" class="table table-striped table-bordered table-sm table-hover align-middle shadow" style="width:100%">
                                            <thead>
                                                <tr>
                                                    <th class="text-center bg-sofia">#</th>
                                                    <th class="text-center bg-sofia">TIPO</th>
                                                    <th class="text-center bg-sofia">INICIA</th>
                                                    <th class="text-center bg-sofia">TERMINA</th>
                                                    <th class="text-center bg-sofia">DÍAS</th>
                                                    <th class="text-center bg-sofia">CATEGORÍA</th>
                                                    <th class="text-center bg-sofia">ACCIONES</th>
                                                </tr>
                                            </thead>
                                            <tbody id="modificaIncapacidades">
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                            <div class="accordion-item">
                                <h2 class="accordion-header sombra">
                                    <button class="accordion-button collapsed bg-success-subtle" type="button" data-bs-toggle="collapse" data-bs-target="#divVacaciones" aria-expanded="false" aria-controls="divVacaciones">
                                        <span class="text-success"></i>EACII. Vacaciones.</span>
                                    </button>
                                </h2>
                                <div id="divVacaciones" class="accordion-collapse collapse">
                                    <div class="accordion-body bg-wiev">
                                        <table id="tableVacaciones" class="table table-striped table-bordered table-sm table-hover align-middle shadow" style="width:100%">
                                            <thead>
                                                <tr>
                                                    <th class="text-center bg-sofia">#</th>
                                                    <th class="text-center bg-sofia">ANTICIPADAS</th>
                                                    <th class="text-center bg-sofia">INICIA</th>
                                                    <th class="text-center bg-sofia">TERMINA</th>
                                                    <th class="text-center bg-sofia">INACTIVO</th>
                                                    <th class="text-center bg-sofia">HÁBILES</th>
                                                    <th class="text-center bg-sofia">CORTE</th>
                                                    <th class="text-center bg-sofia">LIQUIDAR</th>
                                                    <th class="text-center bg-sofia">ACCIONES</th>
                                                </tr>
                                            </thead>
                                            <tbody id="modificaVacaciones">
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                            <div class="accordion-item">
                                <h2 class="accordion-header sombra">
                                    <button class="accordion-button collapsed bg-success-subtle" type="button" data-bs-toggle="collapse" data-bs-target="#divLicenciaMoP" aria-expanded="false" aria-controls="divLicenciaMoP">
                                        <span class="text-info"></i>EACII. Licencia Manterna o Paterna.</span>
                                    </button>
                                </h2>
                                <div id="divLicenciaMoP" class="accordion-collapse collapse">
                                    <div class="accordion-body bg-wiev">
                                        <table id="tableLicenciaMoP" class="table table-striped table-bordered table-sm table-hover align-middle shadow" style="width:100%">
                                            <thead>
                                                <tr>
                                                    <th class="text-center bg-sofia">#</th>
                                                    <th class="text-center bg-sofia">INICIA</th>
                                                    <th class="text-center bg-sofia">TERMINA</th>
                                                    <th class="text-center bg-sofia">INACTIVO</th>
                                                    <th class="text-center bg-sofia">HÁBILES</th>
                                                    <th class="text-center bg-sofia">ACCIONES</th>
                                                </tr>
                                            </thead>
                                            <tbody id="modificaLicenciaMoP">
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                            <div class="accordion-item">
                                <h2 class="accordion-header sombra">
                                    <button class="accordion-button collapsed bg-success-subtle" type="button" data-bs-toggle="collapse" data-bs-target="#divLicenciaLuto" aria-expanded="false" aria-controls="divLicenciaLuto">
                                        <span class="text-secondary"></i>EACII. Licencia Por Luto.</span>
                                    </button>
                                </h2>
                                <div id="divLicenciaLuto" class="accordion-collapse collapse">
                                    <div class="accordion-body bg-wiev">
                                        <table id="tableLicenciaLuto" class="table table-striped table-bordered table-sm table-hover align-middle shadow" style="width:100%">
                                            <thead>
                                                <tr>
                                                    <th class="text-center bg-sofia">#</th>
                                                    <th class="text-center bg-sofia">INICIA</th>
                                                    <th class="text-center bg-sofia">TERMINA</th>
                                                    <th class="text-center bg-sofia">INACTIVO</th>
                                                    <th class="text-center bg-sofia">HÁBILES</th>
                                                    <th class="text-center bg-sofia">ACCIONES</th>
                                                </tr>
                                            </thead>
                                            <tbody id="modificaLicenciaLuto">
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                            <div class="accordion-item">
                                <h2 class="accordion-header sombra">
                                    <button class="accordion-button collapsed bg-success-subtle" type="button" data-bs-toggle="collapse" data-bs-target="#divLicenciaNoRem" aria-expanded="false" aria-controls="divLicenciaNoRem">
                                        <span class="text-warning"></i>EACII. Licencia No Remunerada.</span>
                                    </button>
                                </h2>
                                <div id="divLicenciaNoRem" class="accordion-collapse collapse">
                                    <div class="accordion-body bg-wiev">
                                        <table id="tableLicenciaNoRem" class="table table-striped table-bordered table-sm table-hover align-middle shadow" style="width:100%">
                                            <thead>
                                                <tr>
                                                    <th class="text-center bg-sofia">#</th>
                                                    <th class="text-center bg-sofia">INICIA</th>
                                                    <th class="text-center bg-sofia">TERMINA</th>
                                                    <th class="text-center bg-sofia">INACTIVO</th>
                                                    <th class="text-center bg-sofia">HÁBILES</th>
                                                    <th class="text-center bg-sofia">ACCIONES</th>
                                                </tr>
                                            </thead>
                                            <tbody id="modificaLicenciaNoRem">
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                            <div class="accordion-item">
                                <h2 class="accordion-header sombra">
                                    <button class="accordion-button collapsed bg-success-subtle" type="button" data-bs-toggle="collapse" data-bs-target="#divIndemnizaVacacion" aria-expanded="false" aria-controls="divIndemnizaVacacion">
                                        <span class="text-muted"></i>EACII. Indemnización Por Vacacion.</span>
                                    </button>
                                </h2>
                                <div id="divIndemnizaVacacion" class="accordion-collapse collapse">
                                    <div class="accordion-body bg-wiev">
                                        <table id="tableIndemnizaVacacion" class="table table-striped table-bordered table-sm table-hover align-middle shadow" style="width:100%">
                                            <thead>
                                                <tr>
                                                    <th class="text-center bg-sofia">#</th>
                                                    <th class="text-center bg-sofia">INICIA</th>
                                                    <th class="text-center bg-sofia">TERMINA</th>
                                                    <th class="text-center bg-sofia">DÍAS</th>
                                                    <th class="text-center bg-sofia">ACCIONES</th>
                                                </tr>
                                            </thead>
                                            <tbody id="modificaIndemnizaVacacion">
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="accordion-item">
                <h2 class="accordion-header sombra">
                    <button class="accordion-button collapsed bg-success-subtle" type="button" data-bs-toggle="collapse" data-bs-target="#divDeducidos" aria-expanded="false" aria-controls="divDeducidos">
                        <span class="text-warning"><i class="fas fa-user-minus me-2 fa-lg"></i>VIÑETA. Deducidos.</span>
                    </button>
                </h2>
                <div id="divDeducidos" class="accordion-collapse collapse">
                    <div class="accordion-body bg-wiev">
                        <div class="accordion">
                            <div class="accordion-item">
                                <h2 class="accordion-header sombra">
                                    <button class="accordion-button collapsed bg-success-subtle" type="button" data-bs-toggle="collapse" data-bs-target="#divLibranzas" aria-expanded="false" aria-controls="divLibranzas">
                                        <span class="text-primary"></i>EACII. Libranzas.</span>
                                    </button>
                                </h2>
                                <div id="divLibranzas" class="accordion-collapse collapse">
                                    <div class="accordion-body bg-wiev">
                                        <table id="tableLibranzas" class="table table-striped table-bordered table-sm table-hover align-middle shadow" style="width:100%">
                                            <thead>
                                                <tr>
                                                    <th class="text-center bg-sofia">#</th>
                                                    <th class="text-center bg-sofia">ENTIDAD</th>
                                                    <th class="text-center bg-sofia">TOTAL</th>
                                                    <th class="text-center bg-sofia">VAL. MES</th>
                                                    <th class="text-center bg-sofia">PAGADO</th>
                                                    <th class="text-center bg-sofia">CUOTAS</th>
                                                    <th class="text-center bg-sofia">INICIA</th>
                                                    <th class="text-center bg-sofia">TERMINA</th>
                                                    <th class="text-center bg-sofia">ESTADO</th>
                                                    <th class="text-center bg-sofia">ACCIONES</th>
                                                </tr>
                                            </thead>
                                            <tbody id="modificaLibranzas">
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                            <div class="accordion-item">
                                <h2 class="accordion-header sombra">
                                    <button class="accordion-button collapsed bg-success-subtle" type="button" data-bs-toggle="collapse" data-bs-target="#divEmbargos" aria-expanded="false" aria-controls="divEmbargos">
                                        <span class="text-success"></i>EACII. Embargos.</span>
                                    </button>
                                </h2>
                                <div id="divEmbargos" class="accordion-collapse collapse">
                                    <div class="accordion-body bg-wiev">
                                        <table id="tableEmbargos" class="table table-striped table-bordered table-sm table-hover align-middle shadow" style="width:100%">
                                            <thead>
                                                <tr>
                                                    <th class="text-center bg-sofia">#</th>
                                                    <th class="text-center bg-sofia">TIPO</th>
                                                    <th class="text-center bg-sofia">INICIA</th>
                                                    <th class="text-center bg-sofia">DÍAS</th>
                                                    <th class="text-center bg-sofia">ACCIONES</th>
                                                </tr>
                                            </thead>
                                            <tbody id="modificaEmbargos">
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                            <div class="accordion-item">
                                <h2 class="accordion-header sombra">
                                    <button class="accordion-button collapsed bg-success-subtle" type="button" data-bs-toggle="collapse" data-bs-target="#divSindicatos" aria-expanded="false" aria-controls="divSindicatos">
                                        <span class="text-info"></i>EACII. Sindicatos.</span>
                                    </button>
                                </h2>
                                <div id="divSindicatos" class="accordion-collapse collapse">
                                    <div class="accordion-body bg-wiev">
                                        <table id="tableSindicatos" class="table table-striped table-bordered table-sm table-hover align-middle shadow" style="width:100%">
                                            <thead>
                                                <tr>
                                                    <th class="text-center bg-sofia">#</th>
                                                    <th class="text-center bg-sofia">TIPO</th>
                                                    <th class="text-center bg-sofia">INICIA</th>
                                                    <th class="text-center bg-sofia">DÍAS</th>
                                                    <th class="text-center bg-sofia">ACCIONES</th>
                                                </tr>
                                            </thead>
                                            <tbody id="modificaSindicatos">
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                            <div class="accordion-item">
                                <h2 class="accordion-header sombra">
                                    <button class="accordion-button collapsed bg-success-subtle" type="button" data-bs-toggle="collapse" data-bs-target="#divOtroDescuento" aria-expanded="false" aria-controls="divOtroDescuento">
                                        <span class="text-secondary"></i>EACII. Otros Descuentos.</span>
                                    </button>
                                </h2>
                                <div id="divOtroDescuento" class="accordion-collapse collapse">
                                    <div class="accordion-body bg-wiev">
                                        <table id="tableOtroDescuento" class="table table-striped table-bordered table-sm table-hover align-middle shadow" style="width:100%">
                                            <thead>
                                                <tr>
                                                    <th class="text-center bg-sofia">#</th>
                                                    <th class="text-center bg-sofia">TIPO</th>
                                                    <th class="text-center bg-sofia">INICIA</th>
                                                    <th class="text-center bg-sofia">DÍAS</th>
                                                    <th class="text-center bg-sofia">ACCIONES</th>
                                                </tr>
                                            </thead>
                                            <tbody id="modificaOtroDescuento">
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
HTML;

$lines = explode("\n", $content);
$result = [];
$mainCounter = 0;
$subCounter = 0;

foreach ($lines as $line) {
    if (strpos($line, 'VIÑETA') !== false) {
        $mainCounter++;
        $subCounter = 0;
        $line = trim(str_replace('VIÑETA', $mainCounter, $line));
    } else if (strpos($line, 'EACII') !== false) {
        $subCounter++;
        $line = trim(str_replace('EACII', $mainCounter . '.' . $subCounter, $line));
    } else {
        $line = trim($line);
    }
    $result[] = $line;
}

$content = implode("\n", $result);

$plantilla = new Plantilla($content, 2);
$plantilla->addScriptFile("{$host}/src/nomina/empleados/js/detalles.js?v=" . date("YmdHis"));
$modal = $plantilla->getModal('modalForms', 'tamModalForms', 'bodyModal');
$plantilla->addModal($modal);
echo $plantilla->render();
