<?php
session_start();
if (!isset($_SESSION['user'])) {
    header('Location: ../../../../index.php');
    exit();
}
include '../../../../config/autoloader.php';

use Src\Common\Php\Clases\Permisos;

$permisos = new Permisos();

$id_rol = $_SESSION['rol'];
$id_user = $_SESSION['id_user'];
$opciones = $permisos->PermisoOpciones($id_user);

try {
    $cmd = \Config\Clases\Conexion::getConexion();

    $sql = "SELECT * FROM tb_tipo_tercero";
    $rs = $cmd->query($sql);
    $tipoTercero = $rs->fetchAll();
    $rs->closeCursor();
    unset($rs);
    $cmd = null;
} catch (PDOException $e) {
    echo $e->getCode() == 2002 ? 'Sin Conexión a Mysql (Error: 2002)' : 'Error: ' . $e->getMessage();
}
try {
    $cmd = \Config\Clases\Conexion::getConexion();

    $sql = "SELECT * FROM tb_tipos_documento";
    $rs = $cmd->query($sql);
    $tipodoc = $rs->fetchAll();
    $rs->closeCursor();
    unset($rs);
    $cmd = null;
} catch (PDOException $e) {
    echo $e->getCode() == 2002 ? 'Sin Conexión a Mysql (Error: 2002)' : 'Error: ' . $e->getMessage();
}
try {
    $cmd = \Config\Clases\Conexion::getConexion();

    $sql = "SELECT * FROM tb_paises ORDER BY nom_pais";
    $rs = $cmd->query($sql);
    $pais = $rs->fetchAll();
    $rs->closeCursor();
    unset($rs);
    $sql = "SELECT * FROM tb_departamentos ORDER BY nom_departamento";
    $rs = $cmd->query($sql);
    $dpto = $rs->fetchAll();
    $rs->closeCursor();
    unset($rs);
    $cmd = null;
} catch (PDOException $e) {
    echo $e->getCode() == 2002 ? 'Sin Conexión a Mysql (Error: 2002)' : 'Error: ' . $e->getMessage();
}

try {
    $cmd = \Config\Clases\Conexion::getConexion();

    $sql = "SELECT * FROM nom_riesgos_laboral ORDER BY clase";
    $rs = $cmd->query($sql);
    $riesgos = $rs->fetchAll();
    $rs->closeCursor();
    unset($rs);
    $cmd = null;
} catch (PDOException $e) {
    echo $e->getCode() == 2002 ? 'Sin Conexión a Mysql (Error: 2002)' : 'Error: ' . $e->getMessage();
}
?>
<div class="container-fluid p-2">
    <div class="card mb-4">
        <div class="card-header text-white text-center" id="divTituloPag" style="background-color: #16a085 !important;">
            <h5 class="mb-0">REGISTRAR TERCERO</h5>
        </div>
        <div class="card-body" id="divCuerpoPag">
            <nav>
                <div class="nav nav-tabs" id="nav-tab" role="tablist">
                    <a class="nav-item nav-link active small" id="nav_regTercro-tab" data-bs-toggle="tab" href="#nav_regTercro" role="tab" aria-controls="nav_regTercro" aria-selected="true">Nuevo Tercero</a>
                    <a class="nav-item nav-link small" id="nav-agregTipoTercer-tab" data-bs-toggle="tab" href="#nav-agregTipoTercer" role="tab" aria-controls="nav-agregTipoTercer" aria-selected="false">Agregar Tipo de Tercero</a>
                </div>
            </nav>
            <div class="tab-content" id="nav-tabContent">
                <div class="tab-pane fade show active" id="nav_regTercro" role="tabpanel" aria-labelledby="nav_regTercro-tab">
                    <div class="card-header p-2 text-white" style="background-color: #16a085 !important;" id="divDivisor">
                        <div class="text-center">DATOS DE TERCERO</div>
                    </div>
                    <div class="shadow">
                        <form id="formNuevoTercero">
                            <div class="row px-4 pt-2 mb-3">
                                <div class="col-md-2">
                                    <label for="slcTipoTercero" class="small">Tipo de tercero</label>
                                    <select id="slcTipoTercero" name="slcTipoTercero" class="form-select form-select-sm bg-input" aria-label="Default select example">
                                        <option selected value="0">--Selecionar tipo--</option>
                                        <?php
                                        foreach ($tipoTercero as $tT) {
                                            echo '<option value="' . $tT['id_tipo'] . '">' . $tT['descripcion'] . '</option>';
                                        }
                                        ?>
                                    </select>
                                </div>
                                <div class="col-md-2">
                                    <label for="datFecInicio" class="small">Fecha de inicio</label>
                                    <input type="date" class="form-control form-control-sm bg-input" id="datFecInicio" name="datFecInicio">
                                </div>
                                <div class="col-md-2">
                                    <label for="slcGenero" class="small">Género</label>
                                    <select id="slcGenero" name="slcGenero" class="form-select form-select-sm bg-input" aria-label="Default select example">
                                        <option value="0">--Selecionar--</option>
                                        <option value="M">MASCULINO</option>
                                        <option value="F">FEMENINO</option>
                                        <option value="NA">NO APLICA</option>
                                    </select>
                                </div>
                                <div class="col-md-2">
                                    <label for="datFecNacimiento" class="small">Fecha de Nacimiento</label>
                                    <input type="date" class="form-control form-control-sm bg-input" id="datFecNacimiento" name="datFecNacimiento">
                                </div>
                                <div class="col-md-2">
                                    <label for="slcTipoDocEmp" class="small">Tipo de documento</label>
                                    <select id="slcTipoDocEmp" name="slcTipoDocEmp" class="form-select form-select-sm bg-input" aria-label="Default select example">
                                        <option selected value="0">--Selecionar tipo--</option>
                                        <?php
                                        foreach ($tipodoc as $td) {
                                            echo '<option value="' . $td['id_tipodoc'] . '">' . mb_strtoupper($td['descripcion']) . '</option>';
                                        }
                                        ?>
                                    </select>
                                </div>
                                <div class="col-md-2">
                                    <label for="txtCCempleado" class="small">Identificación</label>
                                    <input type="number" class="form-control form-control-sm bg-input" id="txtCCempleado" name="txtCCempleado" min="1" placeholder="C.C., NIT, etc.">
                                </div>
                            </div>
                            <div class="row px-4 mb-3">
                                <div class="col-md-2">
                                    <label for="txtNomb1Emp" class="small">Primer nombre</label>
                                    <input type="text" class="form-control form-control-sm bg-input" id="txtNomb1Emp" name="txtNomb1Emp" placeholder="Nombre">
                                </div>
                                <div class="col-md-2">
                                    <label for="txtNomb2Emp" class="small">Segundo nombre</label>
                                    <input type="text" class="form-control form-control-sm bg-input" id="txtNomb2Emp" name="txtNomb2Emp" placeholder="Nombre">
                                </div>
                                <div class="col-md-2">
                                    <label for="txtApe1Emp" class="small">Primer apellido</label>
                                    <input type="text" class="form-control form-control-sm bg-input" id="txtApe1Emp" name="txtApe1Emp" placeholder="Apellido">
                                </div>
                                <div class="col-md-2">
                                    <label for="txtApe2Emp" class="small">Segundo apellido</label>
                                    <input type="text" class="form-control form-control-sm bg-input" id="txtApe2Emp" name="txtApe2Emp" placeholder="Apellido">
                                </div>
                                <div class="col-md-4">
                                    <label for="txtRazonSocial" class="small">Razón Social</label>
                                    <input type="text" class="form-control form-control-sm bg-input" id="txtRazonSocial" name="txtRazonSocial" placeholder="Nombre empresa">
                                </div>
                            </div>
                            <div class="row px-4 mb-3">
                                <div class="col-md-3">
                                    <label for="slcPaisEmp" class="small">País</label>
                                    <select id="slcPaisEmp" name="slcPaisEmp" class="form-select form-select-sm bg-input" aria-label="Default select example">
                                        <option selected value="0">--Selecionar--</option>
                                        <?php
                                        foreach ($pais as $p) {
                                            echo '<option value="' . $p['id_pais'] . '">' . mb_strtoupper($p['nom_pais']) . '</option>';
                                        }
                                        ?>
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <label for="slcDptoEmp" class="small">Departamento</label>
                                    <select id="slcDptoEmp" name="slcDptoEmp" class="form-select form-select-sm bg-input" aria-label="Default select example" onchange="CargaCombos('slcMunicipioEmp','mun',this.value)">
                                        <option selected value="0">--Selecionar--</option>
                                        <?php
                                        foreach ($dpto as $d) {
                                            echo '<option value="' . $d['id_departamento'] . '">' . $d['nom_departamento'] . '</option>';
                                        }
                                        ?>
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <label for="slcMunicipioEmp" class="small">Municipio</label>
                                    <select id="slcMunicipioEmp" name="slcMunicipioEmp" class="form-select form-select-sm bg-input" aria-label="Default select example" placeholder="elegir mes">
                                        <option selected value="0">Debe elegir departamento</option>
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <label for="txtDireccion" class="small">Dirección</label>
                                    <input type="text" class="form-control form-control-sm bg-input" id="txtDireccion" name="txtDireccion" placeholder="Residencial">
                                </div>
                            </div>
                            <div class="row px-4 mb-3">
                                <div class="col-md-3">
                                    <label for="mailEmp" class="small">Correo</label>
                                    <input type="email" class="form-control form-control-sm bg-input" id="mailEmp" name="mailEmp" placeholder="Correo electrónico">
                                </div>
                                <div class="col-md-2">
                                    <label for="txtTelEmp" class="small">Contacto</label>
                                    <input type="text" class="form-control form-control-sm bg-input" id="txtTelEmp" name="txtTelEmp" placeholder="Teléfono/celular">
                                </div>
                                <div class="col-md-2 text-center mt-1">
                                    <label class="small d-block" for="rdo_esasist_si">Es asistencial</label>
                                    <div class="form-control-sm border rounded px-2 py-1">
                                        <div class="form-check form-check-inline mb-0">
                                            <input class="form-check-input" type="radio" name="rdo_esasist" id="rdo_esasist_si" value="1">
                                            <label class="form-check-label small" for="rdo_esasist_si">SI</label>
                                        </div>
                                        <div class="form-check form-check-inline mb-0">
                                            <input class="form-check-input" type="radio" name="rdo_esasist" id="rdo_esasist_no" value="0" checked>
                                            <label class="form-check-label small" for="rdo_esasist_no">NO</label>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-2 text-center mt-1">
                                    <label class="small d-block" for="rdo_planilla_si">Planilla</label>
                                    <div class="form-control-sm border rounded px-2 py-1">
                                        <div class="form-check form-check-inline mb-0">
                                            <input class="form-check-input" type="radio" name="rdo_planilla" id="rdo_planilla_si" value="1">
                                            <label class="form-check-label small" for="rdo_planilla_si">SI</label>
                                        </div>
                                        <div class="form-check form-check-inline mb-0">
                                            <input class="form-check-input" type="radio" name="rdo_planilla" id="rdo_planilla_no" value="0" checked>
                                            <label class="form-check-label small" for="rdo_planilla_no">NO</label>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <label for="txtTelEmp" class="small">Riesgo Laboral</label>
                                    <select id="slcRiesgoLab" name="slcRiesgoLab" class="form-select form-select-sm bg-input" aria-label="Default select example">
                                        <option selected value="0">--Selecionar--</option>
                                        <?php
                                        foreach ($riesgos as $r) {
                                            echo '<option value="' . $r['id_rlab'] . '">' . $r['clase'] . ' - ' . $r['riesgo'] . '</option>';
                                        }
                                        ?>
                                    </select>
                                </div>
                            </div>
                        </form>
                        <div class="text-end p-3">
                            <button class="btn btn-primary btn-sm" id="btnNewTercero">Registrar</button>
                            <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal"> Cancelar</button>
                        </div>
                    </div>
                </div>
                <div class="tab-pane fade" id="nav-agregTipoTercer" role="tabpanel" aria-labelledby="nav-agregTipoTercer-tab">
                    <div class="shadow">
                        <form id="formAddTipoTercero">
                            <div class="row px-4 pt-2 mb-3">
                                <div class="col-md-9">
                                    <label for="buscaTercero" class="small">Buscar Tercero</label>
                                    <input type="text" class="form-control form-control-sm bg-input" id="buscaTercero">
                                    <input type="hidden" id="id_tercero" name="id_tercero" value="0">
                                </div>
                                <div class="col-md-3">
                                    <label for="slcTipoTerce" class="small">Tipo de tercero</label>
                                    <select id="slcTipoTerce" name="slcTipoTerce" class="form-select form-select-sm bg-input" aria-label="Default select example">
                                        <option selected value="0">--Selecionar tipo--</option>
                                        <?php
                                        foreach ($tipoTercero as $tT) {
                                            echo '<option value="' . $tT['id_tipo'] . '">' . $tT['descripcion'] . '</option>';
                                        }
                                        ?>
                                    </select>
                                </div>
                            </div>
                        </form>
                        <br>
                    </div>
                    <div class="text-end p-2">
                        <button class="btn btn-primary btn-sm" id="btnNewTipoTercero">Agregar</button>
                        <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal"> Cancelar</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>