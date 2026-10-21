<?php
session_start();
if (!isset($_SESSION['user'])) {
    header('Location: ../../../../../index.php');
    exit();
}
include '../../../../../config/autoloader.php';
$idTercero = isset($_POST['idt']) ? $_POST['idt'] : exit('Acción no permitida');
//API URL
$api = \Config\Clases\Conexion::Api();
$url = $api . 'terceros/datos/res/lista/datos_up/' . $idTercero;
$ch = curl_init($url);
//curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type:application/json'));
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
$result = curl_exec($ch);
curl_close($ch);

$tercero = json_decode($result, true);

if (isset($tercero['id_tercero'])) {
    $id_dpto = $tercero['departamento'];
    $id_api = $tercero['id_tercero'];
    try {
        $cmd = \Config\Clases\Conexion::getConexion();

        $sql = "SELECT * FROM `tb_municipios` WHERE `id_departamento` = $id_dpto ORDER BY `nom_municipio`";
        $rs = $cmd->query($sql);
        $municipios = $rs->fetchAll(PDO::FETCH_ASSOC);
        $rs->closeCursor();
        unset($rs);
        $cmd = null;
    } catch (PDOException $e) {
        echo $e->getCode() == 2002 ? 'Sin Conexión a Mysql (Error: 2002)' : 'Error: ' . $e->getMessage();
    }
    try {
        $cmd = \Config\Clases\Conexion::getConexion();

        $sql = "SELECT * FROM `tb_tipo_tercero`";
        $rs = $cmd->query($sql);
        $tipoTercero = $rs->fetchAll(PDO::FETCH_ASSOC);
        $rs->closeCursor();
        unset($rs);
        $cmd = null;
    } catch (PDOException $e) {
        echo $e->getCode() == 2002 ? 'Sin Conexión a Mysql (Error: 2002)' : 'Error: ' . $e->getMessage();
    }
    try {
        $cmd = \Config\Clases\Conexion::getConexion();

        $sql = "SELECT * FROM `tb_tipos_documento`";
        $rs = $cmd->query($sql);
        $tipodoc = $rs->fetchAll(PDO::FETCH_ASSOC);
        $rs->closeCursor();
        unset($rs);
        $cmd = null;
    } catch (PDOException $e) {
        echo $e->getCode() == 2002 ? 'Sin Conexión a Mysql (Error: 2002)' : 'Error: ' . $e->getMessage();
    }
    try {
        $cmd = \Config\Clases\Conexion::getConexion();

        $sql = "SELECT * FROM `tb_paises`";
        $rs = $cmd->query($sql);
        $pais = $rs->fetchAll(PDO::FETCH_ASSOC);
        $rs->closeCursor();
        unset($rs);
        $sql = "SELECT * FROM `tb_departamentos` ORDER BY `nom_departamento`";
        $rs = $cmd->query($sql);
        $dpto = $rs->fetchAll(PDO::FETCH_ASSOC);
        $rs->closeCursor();
        unset($rs);
        $cmd = null;
    } catch (PDOException $e) {
        echo $e->getCode() == 2002 ? 'Sin Conexión a Mysql (Error: 2002)' : 'Error: ' . $e->getMessage();
    }
    try {
        $cmd = \Config\Clases\Conexion::getConexion();

        $sql = "SELECT
                    `tb_rel_tercero`.`id_tipo_tercero`
                    , `tb_terceros`.`fec_inicio`
                FROM
                    `tb_terceros`
                    INNER JOIN `tb_rel_tercero` 
                        ON (`tb_terceros`.`id_tercero_api` = `tb_rel_tercero`.`id_tercero_api`)
                WHERE (`tb_terceros`.`id_tercero_api` = $id_api)";
        $rs = $cmd->query($sql);
        $terEmpresa = $rs->fetch();
        $cmd = null;
    } catch (PDOException $e) {
        echo $e->getCode() == 2002 ? 'Sin Conexión a Mysql (Error: 2002)' : 'Error: ' . $e->getMessage();
    }
    //-------------------------------------------
    try {
        $cmd = \Config\Clases\Conexion::getConexion();

        $sql = "SELECT tb_terceros.es_clinico, planilla, id_riesgo
                FROM tb_terceros
                WHERE tb_terceros.id_tercero_api = $id_api";
        $rs = $cmd->query($sql);
        $terclinico = $rs->fetch();
        $cmd = null;
    } catch (PDOException $e) {
        echo $e->getCode() == 2002 ? 'Sin Conexión a Mysql (Error: 2002)' : 'Error: ' . $e->getMessage();
    }

    try {
        $cmd = \Config\Clases\Conexion::getConexion();

        $sql = "SELECT * FROM nom_riesgos_laboral ORDER BY clase";
        $rs = $cmd->query($sql);
        $riesgos = $rs->fetchAll(PDO::FETCH_ASSOC);
        $rs->closeCursor();
        unset($rs);
        $cmd = null;
    } catch (PDOException $e) {
        echo $e->getCode() == 2002 ? 'Sin Conexión a Mysql (Error: 2002)' : 'Error: ' . $e->getMessage();
    }
?>
    <div class="px-0">
        <div class="shadow text-center">
            <div class="card-header p-2 text-center" style="background-color: #16a085 !important;">
                <h5 class="mb-0" style="color: white;">ACTUALIZAR DATOS DE TERCERO</h5>
            </div>
            <form id="formActualizaTercero">
                <input type="number" id="idTercero" name="idTercero" value="<?php echo $id_api ?>" hidden>
                <div class="row px-4 pt-2 mb-3">
                    <div class="col-md-2">
                        <label for="slcTipoTercero" class="small">Tipo de tercero</label>
                        <select id="slcTipoTercero" name="slcTipoTercero" class="form-select form-select-sm bg-input" aria-label="Default select example">
                            <?php
                            foreach ($tipoTercero as $tT) {
                                $slc = $tT['id_tipo'] === $terEmpresa['id_tipo_tercero'] ? 'selected' : '';
                                echo '<option value="' . $tT['id_tipo'] . '" ' . $slc . '>' . mb_strtoupper($tT['descripcion']) . '</option>';
                            }
                            ?>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label for="datFecInicio" class="small">Fecha de inicio</label>
                        <input type="date" class="form-control form-control-sm bg-input" id="datFecInicio" name="datFecInicio" value="<?php echo $terEmpresa['fec_inicio'] ?>">
                    </div>
                    <div class="col-md-2">
                        <label for="slcGenero" class="small">Género</label>
                        <select id="slcGenero" name="slcGenero" class="form-select form-select-sm bg-input" aria-label="Default select example">
                            <?php $g = $tercero['genero'] ?>
                            <option <?php echo $g == 'M' ? 'selected' : '' ?> value="M">MASCULINO</option>
                            <option <?php echo $g == 'F' ? 'selected' : '' ?> value="F">FEMENINO</option>
                            <option <?php echo $g == 'NA' ? 'selected' : '' ?> value="NA">NO APLICA</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label for="datFecNacimiento" class="small">Fecha de Nacimiento</label>
                        <input type="date" class="form-control form-control-sm bg-input" id="datFecNacimiento" name="datFecNacimiento" value="<?php echo $tercero['fec_nacimiento'] ?>">
                    </div>
                    <div class="col-md-2">
                        <label for="slcTipoDocEmp" class="small">Tipo de documento</label>
                        <select id="slcTipoDocEmp" name="slcTipoDocEmp" class="form-select form-select-sm bg-input" aria-label="Default select example">
                            <?php
                            foreach ($tipodoc as $td) {
                                if ($td['id_tipodoc'] !== $tercero['tipo_doc']) {
                                    echo '<option value="' . $td['id_tipodoc'] . '">' . mb_strtoupper($td['descripcion']) . '</option>';
                                } else {
                                    echo '<option selected value="' . $td['id_tipodoc'] . '">' . mb_strtoupper($td['descripcion']) . '</option>';
                                }
                            }
                            ?>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="small">Identificación</label>
                        <div class="form-control form-control-sm bg-light"><?php echo $tercero['cc_nit'] ?></div>
                        <input type="hidden" name="txtCCempleado" id="txtCCempleado" value="<?php echo $tercero['cc_nit'] ?>">
                    </div>
                </div>
                <div class="row px-4">
                    <div class="form-group col-md-2">
                        <label for="txtNomb1Emp" class="small">Primer nombre</label>
                        <input type="text" class="form-control form-control-sm bg-input" id="txtNomb1Emp" name="txtNomb1Emp" placeholder="Nombre" value="<?php echo $tercero['nombre1'] ?>">
                    </div>
                    <div class="col-md-2">
                        <label for="txtNomb2Emp" class="small">Segundo nombre</label>
                        <input type="text" class="form-control form-control-sm bg-input" id="txtNomb2Emp" name="txtNomb2Emp" placeholder="Nombre" value="<?php echo $tercero['nombre2'] ?>">
                    </div>
                    <div class="col-md-2">
                        <label for="txtApe1Emp" class="small">Primer apellido</label>
                        <input type="text" class="form-control form-control-sm bg-input" id="txtApe1Emp" name="txtApe1Emp" placeholder="Apellido" value="<?php echo $tercero['apellido1'] ?>">
                    </div>
                    <div class="col-md-2">
                        <label for="txtApe2Emp" class="small">Segundo apellido</label>
                        <input type="text" class="form-control form-control-sm bg-input" id="txtApe2Emp" name="txtApe2Emp" placeholder="Apellido" value="<?php echo $tercero['apellido2'] ?>">
                    </div>
                    <div class="col-md-4">
                        <label for="txtRazonSocial" class="small">Razón Social</label>
                        <input type="text" class="form-control form-control-sm bg-input" id="txtRazonSocial" name="txtRazonSocial" placeholder="Nombre empresa" value="<?php echo $tercero['razon_social'] ?>">
                    </div>
                </div>
                <div class="row px-4 mb-3">
                    <div class="col-md-3">
                        <label for="slcPaisEmp" class="small">País</label>
                        <select id="slcPaisEmp" name="slcPaisEmp" class="form-select form-select-sm bg-input" aria-label="Default select example">
                            <?php
                            foreach ($pais as $p) {
                                if ($p['id_pais'] !== $tercero['pais']) {
                                    echo '<option value="' . $p['id_pais'] . '">' . mb_strtoupper($p['nom_pais']) . '</option>';
                                } else {
                                    echo '<option selected value="' . $p['id_pais'] . '">' . mb_strtoupper($p['nom_pais']) . '</option>';
                                }
                            }
                            ?>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label for="slcDptoEmp" class="small">Departamento</label>
                        <select id="slcDptoEmp" name="slcDptoEmp" class="form-select form-select-sm bg-input" aria-label="Default select example" onchange="CargaCombos('slcMunicipioEmp','mun',this.value)">
                            <?php
                            foreach ($dpto as $d) {
                                if ($d['id_departamento'] !== $tercero['departamento']) {
                                    echo '<option value="' . $d['id_departamento'] . '">' . $d['nom_departamento'] . '</option>';
                                } else {
                                    echo '<option selected value="' . $d['id_departamento'] . '">' . $d['nom_departamento'] . '</option>';
                                }
                            }
                            ?>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label for="slcMunicipioEmp" class="small">Municipio</label>
                        <select id="slcMunicipioEmp" name="slcMunicipioEmp" class="form-select form-select-sm bg-input" aria-label="Default select example">
                            <?php
                            foreach ($municipios as $m) {
                                if ($tercero['municipio'] !== $m['id_municipio']) {
                                    echo '<option value="' . $m['id_municipio'] . '">' . $m['nom_municipio'] . '</option>';
                                } else {
                                    echo '<option selected value="' . $m['id_municipio'] . '">' . $m['nom_municipio'] . '</option>';
                                }
                            }
                            ?>
                        </select>
                    </div>
                    <div class="col-md-3 d-flex align-items-end">
                        <div class="w-100">
                            <label for="txtDireccionPreview" class="small">Dirección</label>
                            <div class="input-group input-group-sm">
                                <input type="text" class="form-control form-control-sm bg-light"
                                    id="txtDireccionPreview" placeholder="Use el generador →" readonly
                                    value="<?php echo $tercero['direccion'] ?>">
                                <input type="hidden" id="txtDireccion" name="txtDireccion"
                                    value="<?php echo $tercero['direccion'] ?>">
                                <button class="btn btn-sm text-white" type="button"
                                    id="btnAbrirGeneradorDir" style="background-color: #16a085;"
                                    title="Abrir generador de dirección DIAN">
                                    <i class="fa-solid fa-location-dot"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="row px-4 mb-3">
                    <div class="col-md-3">
                        <label for="mailEmp" class="small">Correo</label>
                        <input type="email" class="form-control form-control-sm bg-input" id="mailEmp" name="mailEmp" placeholder="Correo electrónico" value="<?php echo $tercero['correo'] ?>">
                    </div>
                    <div class="col-md-2">
                        <label for="txtTelEmp" class="small">Contacto</label>
                        <input type="text" class="form-control form-control-sm bg-input" id="txtTelEmp" name="txtTelEmp" placeholder="Teléfono/celular" value="<?php echo $tercero['telefono'] ?>">
                    </div>
                    <div class="col-md-2 text-center mt-1">
                        <label class="small d-block" for="rdo_esasist_si">Es asistencial</label>
                        <div class="form-control-sm border rounded px-2 py-1">
                            <div class="form-check form-check-inline mb-0">
                                <input class="form-check-input" type="radio" name="rdo_esasist" id="rdo_esasist_si" value="1" <?= $terclinico['es_clinico'] == 1 ? 'checked' : '' ?>>
                                <label class="form-check-label small" for="rdo_esasist_si">SI</label>
                            </div>
                            <div class="form-check form-check-inline mb-0">
                                <input class="form-check-input" type="radio" name="rdo_esasist" id="rdo_esasist_no" value="0" <?= $terclinico['es_clinico'] == 0 ? 'checked' : '' ?>>
                                <label class="form-check-label small" for="rdo_esasist_no">NO</label>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-2 text-center mt-1">
                        <label class="small d-block" for="rdo_planilla_si">Planilla</label>
                        <div class="form-control-sm border rounded px-2 py-1">
                            <div class="form-check form-check-inline mb-0">
                                <input class="form-check-input" type="radio" name="rdo_planilla" id="rdo_planilla_si" value="1" <?= $terclinico['planilla'] == 1 ? 'checked' : '' ?>>
                                <label class="form-check-label small" for="rdo_planilla_si">SI</label>
                            </div>
                            <div class="form-check form-check-inline mb-0">
                                <input class="form-check-input" type="radio" name="rdo_planilla" id="rdo_planilla_no" value="0" <?= $terclinico['planilla'] == 0 ? 'checked' : '' ?>>
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
                                $slc = $r['id_rlab'] !== $terclinico['id_riesgo'] ? '' : 'selected';
                                echo '<option value="' . $r['id_rlab'] . '" ' . $slc . '>' . $r['clase'] . ' - ' . $r['riesgo'] . '</option>';
                            }
                            ?>
                        </select>
                    </div>
                </div>
            </form>
            <div class="text-end p-3">
                <button class="btn btn-primary btn-sm" id="btnUpTercero">Actualizar</button>
                <a type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal"> Cancelar</a>
            </div>
        </div>
    </div>
<?php
} else {
    echo 'Error al intentar obtener datos';
}
?>

<!-- Modal Generador de Direcciones DIAN (Actualización) -->
<div class="modal fade" id="modalGeneradorDireccion" tabindex="-1" aria-labelledby="lblGeneradorDir"
    aria-hidden="true" data-bs-backdrop="false" data-bs-keyboard="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content shadow-lg border-0">
            <div class="modal-header py-2 text-white" style="background-color: #16a085;">
                <h6 class="modal-title mb-0" id="lblGeneradorDir">
                    <i class="fa-solid fa-location-dot me-2"></i>Generador de Dirección — Formato DIAN
                </h6>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                    aria-label="Cerrar"></button>
            </div>
            <div class="modal-body p-3">
                <!-- Vista previa de la dirección -->
                <div class="alert py-2 mb-3 border"
                    style="background-color: #e8f8f5; border-color: #16a085 !important;">
                    <div class="d-flex align-items-center">
                        <i class="fa-solid fa-eye me-2" style="color: #16a085;"></i>
                        <small class="text-muted me-2">Vista previa:</small>
                        <strong id="txtVistaPrevia" class="flex-grow-1"
                            style="color: #16a085; font-size: 0.95rem; letter-spacing: 0.5px;">
                            —
                        </strong>
                    </div>
                </div>

                <!-- Vía Principal -->
                <div class="card mb-3 border-0 shadow-sm">
                    <div class="card-header py-1 px-3 small fw-bold text-white" style="background-color: #1abc9c;">
                        <i class="fa-solid fa-road me-1"></i> Vía Principal
                    </div>
                    <div class="card-body py-2 px-3">
                        <div class="row g-2">
                            <div class="col-md-3">
                                <label class="form-label small mb-0">Tipo de vía <span
                                        class="text-danger">*</span></label>
                                <select id="slcTipoVia" class="form-select form-select-sm bg-input">
                                    <option value="">--Seleccione--</option>
                                    <option value="AC">Avenida Calle (AC)</option>
                                    <option value="AK">Avenida Carrera (AK)</option>
                                    <option value="AV">Avenida (AV)</option>
                                    <option value="CL">Calle (CL)</option>
                                    <option value="CR">Carrera (CR)</option>
                                    <option value="DG">Diagonal (DG)</option>
                                    <option value="TV">Transversal (TV)</option>
                                    <option value="CQ">Circular (CQ)</option>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label small mb-0">Número <span
                                        class="text-danger">*</span></label>
                                <input type="text" id="txtNumVia" class="form-control form-control-sm bg-input"
                                    placeholder="Ej: 45" maxlength="5">
                            </div>
                            <div class="col-md-2">
                                <label class="form-label small mb-0">Letra</label>
                                <select id="slcLetraVia" class="form-select form-select-sm bg-input">
                                    <option value="">--</option>
                                    <option value="A">A</option>
                                    <option value="B">B</option>
                                    <option value="C">C</option>
                                    <option value="D">D</option>
                                    <option value="E">E</option>
                                    <option value="F">F</option>
                                    <option value="G">G</option>
                                    <option value="H">H</option>
                                    <option value="I">I</option>
                                    <option value="J">J</option>
                                    <option value="K">K</option>
                                    <option value="L">L</option>
                                    <option value="M">M</option>
                                    <option value="N">N</option>
                                    <option value="P">P</option>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label small mb-0">BIS</label>
                                <select id="slcBisVia" class="form-select form-select-sm bg-input">
                                    <option value="">No</option>
                                    <option value="BIS">BIS</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label small mb-0">Cuadrante</label>
                                <select id="slcCuadranteVia" class="form-select form-select-sm bg-input">
                                    <option value="">--</option>
                                    <option value="SUR">SUR</option>
                                    <option value="NORTE">NORTE</option>
                                    <option value="ESTE">ESTE</option>
                                    <option value="OESTE">OESTE</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Vía Secundaria / Placa -->
                <div class="card mb-3 border-0 shadow-sm">
                    <div class="card-header py-1 px-3 small fw-bold text-white" style="background-color: #1abc9c;">
                        <i class="fa-solid fa-hashtag me-1"></i> Número de cruce y Placa
                    </div>
                    <div class="card-body py-2 px-3">
                        <div class="row g-2">
                            <div class="col-md-2">
                                <label class="form-label small mb-0">Número <span
                                        class="text-danger">*</span></label>
                                <input type="text" id="txtNumCruce" class="form-control form-control-sm bg-input"
                                    placeholder="Ej: 20" maxlength="5">
                            </div>
                            <div class="col-md-2">
                                <label class="form-label small mb-0">Letra</label>
                                <select id="slcLetraCruce" class="form-select form-select-sm bg-input">
                                    <option value="">--</option>
                                    <option value="A">A</option>
                                    <option value="B">B</option>
                                    <option value="C">C</option>
                                    <option value="D">D</option>
                                    <option value="E">E</option>
                                    <option value="F">F</option>
                                    <option value="G">G</option>
                                    <option value="H">H</option>
                                    <option value="I">I</option>
                                    <option value="J">J</option>
                                    <option value="K">K</option>
                                    <option value="L">L</option>
                                    <option value="M">M</option>
                                    <option value="N">N</option>
                                    <option value="P">P</option>
                                </select>
                            </div>
                            <div class="col-md-1 d-flex align-items-end justify-content-center pb-1">
                                <span class="fw-bold text-muted" style="font-size: 1.2rem;">-</span>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label small mb-0">Placa <span
                                        class="text-danger">*</span></label>
                                <input type="text" id="txtPlaca" class="form-control form-control-sm bg-input"
                                    placeholder="Ej: 15" maxlength="5">
                            </div>
                            <div class="col-md-2">
                                <label class="form-label small mb-0">Cuadrante</label>
                                <select id="slcCuadrantePlaca" class="form-select form-select-sm bg-input">
                                    <option value="">--</option>
                                    <option value="SUR">SUR</option>
                                    <option value="NORTE">NORTE</option>
                                    <option value="ESTE">ESTE</option>
                                    <option value="OESTE">OESTE</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Complementos -->
                <div class="card mb-2 border-0 shadow-sm">
                    <div class="card-header py-1 px-3 small fw-bold text-white d-flex align-items-center justify-content-between"
                        style="background-color: #1abc9c;">
                        <span><i class="fa-solid fa-building me-1"></i> Complementos (opcional)</span>
                        <button type="button" class="btn btn-sm py-0 px-2 text-white border-white"
                            id="btnAgregarComplemento2" style="font-size: 0.75rem;"
                            title="Agregar segundo complemento">
                            <i class="fa-solid fa-plus me-1"></i>Agregar
                        </button>
                    </div>
                    <div class="card-body py-2 px-3">
                        <!-- Complemento 1 -->
                        <div class="row g-2 mb-2" id="divComplemento1">
                            <div class="col-md-4">
                                <label class="form-label small mb-0">Tipo</label>
                                <select id="slcTipoCompl1" class="form-select form-select-sm bg-input">
                                    <option value="">--Seleccione--</option>
                                    <option value="AP">Apartamento (AP)</option>
                                    <option value="BG">Bodega (BG)</option>
                                    <option value="BL">Bloque (BL)</option>
                                    <option value="CA">Casa (CA)</option>
                                    <option value="CN">Consultorio (CN)</option>
                                    <option value="ED">Edificio (ED)</option>
                                    <option value="EN">Entrada (EN)</option>
                                    <option value="ET">Etapa (ET)</option>
                                    <option value="GJ">Garaje (GJ)</option>
                                    <option value="IN">Interior (IN)</option>
                                    <option value="LC">Local (LC)</option>
                                    <option value="LT">Lote (LT)</option>
                                    <option value="MZ">Manzana (MZ)</option>
                                    <option value="OF">Oficina (OF)</option>
                                    <option value="P">Piso (P)</option>
                                    <option value="PH">Penthouse (PH)</option>
                                    <option value="SC">Sector (SC)</option>
                                    <option value="SM">Semisótano (SM)</option>
                                    <option value="SS">Sótano (SS)</option>
                                    <option value="TO">Torre (TO)</option>
                                    <option value="UR">Urbanización (UR)</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label small mb-0">Valor / Número</label>
                                <input type="text" id="txtValCompl1" class="form-control form-control-sm bg-input"
                                    placeholder="Ej: 301" maxlength="20">
                            </div>
                        </div>
                        <!-- Complemento 2 (oculto por defecto) -->
                        <div class="row g-2" id="divComplemento2" style="display: none;">
                            <div class="col-md-4">
                                <label class="form-label small mb-0">Tipo</label>
                                <select id="slcTipoCompl2" class="form-select form-select-sm bg-input">
                                    <option value="">--Seleccione--</option>
                                    <option value="AP">Apartamento (AP)</option>
                                    <option value="BG">Bodega (BG)</option>
                                    <option value="BL">Bloque (BL)</option>
                                    <option value="CA">Casa (CA)</option>
                                    <option value="CN">Consultorio (CN)</option>
                                    <option value="ED">Edificio (ED)</option>
                                    <option value="EN">Entrada (EN)</option>
                                    <option value="ET">Etapa (ET)</option>
                                    <option value="GJ">Garaje (GJ)</option>
                                    <option value="IN">Interior (IN)</option>
                                    <option value="LC">Local (LC)</option>
                                    <option value="LT">Lote (LT)</option>
                                    <option value="MZ">Manzana (MZ)</option>
                                    <option value="OF">Oficina (OF)</option>
                                    <option value="P">Piso (P)</option>
                                    <option value="PH">Penthouse (PH)</option>
                                    <option value="SC">Sector (SC)</option>
                                    <option value="SM">Semisótano (SM)</option>
                                    <option value="SS">Sótano (SS)</option>
                                    <option value="TO">Torre (TO)</option>
                                    <option value="UR">Urbanización (UR)</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label small mb-0">Valor / Número</label>
                                <input type="text" id="txtValCompl2" class="form-control form-control-sm bg-input"
                                    placeholder="Ej: A" maxlength="20">
                            </div>
                            <div class="col-md-2 d-flex align-items-end">
                                <button type="button" class="btn btn-outline-danger btn-sm"
                                    id="btnQuitarComplemento2" title="Quitar complemento">
                                    <i class="fa-solid fa-times"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Referencia adicional (opcional) -->
                <div class="mb-0">
                    <label class="form-label small mb-0">Información adicional (opcional)</label>
                    <input type="text" id="txtInfoAdicionalDir" class="form-control form-control-sm bg-input"
                        placeholder="Ej: Barrio, conjunto, nombre del edificio, cerca a..." maxlength="80">
                </div>
            </div>
            <div class="modal-footer py-2 d-flex justify-content-between align-items-center"
                style="background-color: #f8f9fa;">
                <small class="text-muted"><i class="fa-solid fa-circle-info me-1"></i>Los campos con <span
                        class="text-danger">*</span> son obligatorios</small>
                <div>
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-sm text-white" id="btnAplicarDireccion"
                        style="background-color: #16a085;">
                        <i class="fa-solid fa-check me-1"></i>Aplicar Dirección
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    (function () {
        // Eliminar cualquier modal previo que haya quedado en el body (evita conflictos de IDs)
        $('body > #modalGeneradorDireccion').remove();

        // Función para construir la dirección según nomenclatura DIAN
        function construirDireccion() {
            var partes = [];

            var tipoVia = $('#slcTipoVia').val();
            var numVia = $.trim($('#txtNumVia').val());
            var letraVia = $('#slcLetraVia').val();
            var bisVia = $('#slcBisVia').val();
            var cuadranteVia = $('#slcCuadranteVia').val();
            var numCruce = $.trim($('#txtNumCruce').val());
            var letraCruce = $('#slcLetraCruce').val();
            var placa = $.trim($('#txtPlaca').val());
            var cuadrantePlaca = $('#slcCuadrantePlaca').val();

            // Vía principal
            if (tipoVia) partes.push(tipoVia);
            if (numVia) partes.push(numVia);
            if (letraVia) partes.push(letraVia);
            if (bisVia) partes.push(bisVia);
            if (cuadranteVia) partes.push(cuadranteVia);

            // Separador entre vía principal y cruce
            if (numCruce) partes.push(numCruce);
            if (letraCruce) partes.push(letraCruce);

            // Placa
            if (placa) partes.push('-');
            if (placa) partes.push(placa);
            if (cuadrantePlaca) partes.push(cuadrantePlaca);

            // Complemento 1
            var tipoC1 = $('#slcTipoCompl1').val();
            var valC1 = $.trim($('#txtValCompl1').val());
            if (tipoC1 && valC1) {
                partes.push(tipoC1);
                partes.push(valC1);
            }

            // Complemento 2
            if ($('#divComplemento2').is(':visible')) {
                var tipoC2 = $('#slcTipoCompl2').val();
                var valC2 = $.trim($('#txtValCompl2').val());
                if (tipoC2 && valC2) {
                    partes.push(tipoC2);
                    partes.push(valC2);
                }
            }

            var direccion = partes.join(' ').toUpperCase();
            $('#txtVistaPrevia').text(direccion || '—');
            return direccion;
        }

        // Limpiar handlers previos para evitar duplicados al recargar el form
        $(document).off('click.generadorDir');
        $(document).off('show.bs.modal.generadorDir');
        $(document).off('hidden.bs.modal.generadorDir');
        $(document).off('input.generadorDir change.generadorDir');
        $(document).off('click.generadorDirCompl');
        $(document).off('keypress.generadorDir');

        // Actualizar vista previa en cada cambio
        $(document).on('input.generadorDir change.generadorDir',
            '#slcTipoVia, #txtNumVia, #slcLetraVia, #slcBisVia, #slcCuadranteVia, ' +
            '#txtNumCruce, #slcLetraCruce, #txtPlaca, #slcCuadrantePlaca, ' +
            '#slcTipoCompl1, #txtValCompl1, #slcTipoCompl2, #txtValCompl2',
            function () {
                construirDireccion();
            }
        );

        // Abrir modal del generador
        $(document).on('click.generadorDir', '#btnAbrirGeneradorDir', function () {
            var $modal = $('#modalGeneradorDireccion');

            // Si el modal no está ya en el body (primer uso), moverlo
            if (!$modal.parent().is('body')) {
                $modal.appendTo('body');
            }

            // Obtener instancia previa o crear una nueva
            var modalDir = bootstrap.Modal.getInstance($modal[0]);
            if (!modalDir) {
                modalDir = new bootstrap.Modal($modal[0], {
                    backdrop: false,
                    keyboard: true
                });
            }

            // Siempre limpiar campos para nueva dirección
            $('#slcTipoVia, #slcLetraVia, #slcBisVia, #slcCuadranteVia').val('');
            $('#slcLetraCruce, #slcCuadrantePlaca').val('');
            $('#txtNumVia, #txtNumCruce, #txtPlaca').val('');
            $('#slcTipoCompl1, #slcTipoCompl2').val('');
            $('#txtValCompl1, #txtValCompl2, #txtInfoAdicionalDir').val('');
            $('#divComplemento2').hide();
            $('#txtVistaPrevia').text('—');

            modalDir.show();
        });

        // Ajuste de z-index y backdrop manual para modales apilados
        $(document).on('show.bs.modal.generadorDir', '#modalGeneradorDireccion', function () {
            // Crear backdrop manual entre los dos modales
            $('#backdropGeneradorDir').remove(); // evitar duplicados
            $('<div id="backdropGeneradorDir" style="position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,0.5);z-index:1055;"></div>')
                .appendTo('body');
            $(this).css('z-index', 1060);
            // Desactivar Escape y focus trap en el modal padre mientras este modal está abierto
            var elPadre = document.getElementById('divModalForms');
            if (elPadre) {
                var modalPadre = bootstrap.Modal.getInstance(elPadre);
                if (modalPadre) {
                    modalPadre._config.keyboard = false;
                    if (modalPadre._focustrap) {
                        modalPadre._focustrap.deactivate();
                    }
                }
            }
        });

        $(document).on('hidden.bs.modal.generadorDir', '#modalGeneradorDireccion', function () {
            // Remover backdrop manual
            $('#backdropGeneradorDir').remove();

            var elPadre = document.getElementById('divModalForms');
            if (elPadre) {
                var modalPadre = bootstrap.Modal.getInstance(elPadre);
                if (modalPadre) {
                    // Restaurar Escape y focus trap en el modal padre
                    modalPadre._config.keyboard = true;
                    if (modalPadre._focustrap) {
                        modalPadre._focustrap.activate();
                    }
                }
                
                // Asegurar que el modal padre siga teniendo backdrop visible
                // Usamos setTimeout para esperar que terminen las transiciones de Bootstrap
                setTimeout(function() {
                    var $backdrop = $('.modal-backdrop');
                    if ($backdrop.length === 0) {
                        $('<div class="modal-backdrop fade show"></div>').appendTo('body');
                    } else {
                        $backdrop.addClass('show').css('display', 'block');
                    }
                    $('body').addClass('modal-open');
                }, 150);
            }

            // Restaurar estado del body para que el modal padre siga funcionando
            $('body').addClass('modal-open');

            // Restaurar el padding-right del body que Bootstrap agrega por la scrollbar
            var scrollbarWidth = window.innerWidth - document.documentElement.clientWidth;
            if (scrollbarWidth > 0) {
                document.body.style.paddingRight = scrollbarWidth + 'px';
            }
        });

        // Mostrar/ocultar complemento 2
        $(document).on('click.generadorDirCompl', '#btnAgregarComplemento2', function () {
            $('#divComplemento2').slideDown(200);
        });
        $(document).on('click.generadorDirCompl', '#btnQuitarComplemento2', function () {
            $('#slcTipoCompl2').val('');
            $('#txtValCompl2').val('');
            $('#divComplemento2').slideUp(200);
            construirDireccion();
        });

        // Aplicar dirección al formulario
        $(document).on('click.generadorDir', '#btnAplicarDireccion', function () {
            // Validar campos obligatorios
            var valid = true;
            $('#modalGeneradorDireccion .is-invalid').removeClass('is-invalid');

            if (!$('#slcTipoVia').val()) {
                $('#slcTipoVia').addClass('is-invalid');
                valid = false;
            }
            if (!$.trim($('#txtNumVia').val())) {
                $('#txtNumVia').addClass('is-invalid');
                valid = false;
            }
            if (!$.trim($('#txtNumCruce').val())) {
                $('#txtNumCruce').addClass('is-invalid');
                valid = false;
            }
            if (!$.trim($('#txtPlaca').val())) {
                $('#txtPlaca').addClass('is-invalid');
                valid = false;
            }

            if (!valid) {
                if (typeof mjeError === 'function') {
                    mjeError('Error', 'Complete los campos obligatorios marcados con *');
                } else {
                    alert('Complete los campos obligatorios marcados con *');
                }
                return;
            }

            var direccionFinal = construirDireccion();
            var infoAdicional = $.trim($('#txtInfoAdicionalDir').val());
            if (infoAdicional) {
                direccionFinal += ' ' + infoAdicional.toUpperCase();
            }

            $('#txtDireccion').val(direccionFinal);
            $('#txtDireccionPreview').val(direccionFinal);

            // Cerrar el modal de dirección
            var instanciaDir = bootstrap.Modal.getInstance($('#modalGeneradorDireccion')[0]);
            if (instanciaDir) {
                instanciaDir.hide();
            }
        });

        // Permitir solo números en campos numéricos del generador
        $(document).on('keypress.generadorDir', '#txtNumVia, #txtNumCruce, #txtPlaca', function (e) {
            if (e.which < 48 || e.which > 57) {
                e.preventDefault();
            }
        });
    })();
</script>