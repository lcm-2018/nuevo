<?php
session_start();
if (!isset($_SESSION['user'])) {
    header('Location: ../../index.php');
    exit();
}
include '../../config/autoloader.php';

use Config\Clases\Plantilla;
use Src\Common\Php\Clases\Permisos;

$id_rol = $_SESSION['rol'];
$id_user = $_SESSION['id_user'];

$permisos = new Permisos();
$opciones = $permisos->PermisoOpciones($id_user);

$host = Plantilla::getHost();

// Consulta tipo de presupuesto
$id_doc_pag = isset($_POST['id_doc']) ? $_POST['id_doc'] : exit('Acceso no disponible');
$id_cop = isset($_POST['id_cop']) ? $_POST['id_cop'] : 0;
$tipo_dato = isset($_POST['tipo_dato']) ? $_POST['tipo_dato'] : 0;
$tipo_mov = isset($_POST['tipo_movi']) ? $_POST['tipo_movi'] : 0;
$tipo_var = isset($_POST['tipo_var']) ? $_POST['tipo_var'] : 0;
$id_arq = isset($_POST['id_arq']) ? $_POST['id_arq'] : 0;
$id_vigencia = $_SESSION['id_vigencia'];

try {
    $cmd = \Config\Clases\Conexion::getConexion();
    $sql = "SELECT
                `ctb_doc`.`id_ctb_doc`
                , `ctb_doc`.`id_manu`
                , `ctb_fuente`.`nombre`
                , `ctb_doc`.`fecha`
                , `ctb_doc`.`detalle`
                , `ctb_doc`.`id_tercero`
                , `ctb_doc`.`estado`
                , `tes_caja_const`.`nombre_caja`
                , `tes_caja_const`.`fecha_ini`
                , `tes_caja_const`.`id_caja_const`
            FROM
                `ctb_doc`
                INNER JOIN `ctb_fuente` 
                    ON (`ctb_doc`.`id_tipo_doc` = `ctb_fuente`.`id_doc_fuente`)
                INNER JOIN `tes_caja_doc` 
                    ON (`tes_caja_doc`.`id_ctb_doc` = `ctb_doc`.`id_ctb_doc`)
                INNER JOIN `tes_caja_const` 
                    ON (`tes_caja_doc`.`id_caja` = `tes_caja_const`.`id_caja_const`)
            LEFT JOIN `tb_terceros` 
                ON (`ctb_doc`.`id_tercero` = `tb_terceros`.`id_tercero_api`)    
            WHERE (`ctb_doc`.`id_ctb_doc` = :id_doc_pag)";
    $rs = $cmd->prepare($sql);
    $rs->execute([':id_doc_pag' => $id_doc_pag]);
    $datosDoc = $rs->fetch();
    $rs->closeCursor();
    unset($rs);
} catch (PDOException $e) {
    echo $e->getCode() == 2002 ? 'Sin Conexión a Mysql (Error: 2002)' : 'Error: ' . $e->getCode();
}

$id_manu = $datosDoc['id_manu'];
if (!empty($datosDoc)) {
    $id_t = ['0' => $datosDoc['id_tercero']];
    $ids = implode(',', $id_t);
    // Consulta de tercero
    $sqlTer = "SELECT `nom_tercero` FROM `tb_terceros` WHERE `id_tercero_api` = :id_tercero";
    $rsTer = $cmd->prepare($sqlTer);
    $rsTer->execute([':id_tercero' => $datosDoc['id_tercero']]);
    $dat_ter = $rsTer->fetch();
    $tercero = ltrim($dat_ter['nom_tercero'] ?? '---');
    $rsTer->closeCursor();
    unset($rsTer);
} else {
    $tercero = '---';
}

try {
    $sql = "SELECT
                `id_ctb_doc`
                , SUM(`valor`) AS `valor`
            FROM
                `tes_detalle_pago`
            WHERE (`id_ctb_doc` = :id_doc_pag)";
    $rs = $cmd->prepare($sql);
    $rs->execute([':id_doc_pag' => $id_doc_pag]);
    $values = $rs->fetch();
    $valor_pago = !empty($values) ? $values['valor'] : 0;
    $rs->closeCursor();
    unset($rs);
} catch (PDOException $e) {
    echo $e->getCode() == 2002 ? 'Sin Conexión a Mysql (Error: 2002)' : 'Error: ' . $e->getCode();
}

try {
    $query = "SELECT SUM(`valor`) AS `val_imputacion` FROM `tes_caja_mvto` WHERE `id_ctb_doc` = :id_doc_pag";
    $rs = $cmd->prepare($query);
    $rs->execute([':id_doc_pag' => $id_doc_pag]);
    $val_imp = $rs->fetch(PDO::FETCH_ASSOC)['val_imputacion'];
    $rs->closeCursor();
    unset($rs);
} catch (PDOException $e) {
    echo $e->getCode() == 2002 ? 'Sin Conexión a Mysql (Error: 2002)' : 'Error: ' . $e->getCode();
}

// Verificar permisos de registro
$peReg = 0;
if ($permisos->PermisosUsuario($opciones, 5601, 2) || $id_rol == 1) {
    $peReg = 1;
}

// Formatear valores para mostrar
$fechaFormateada = date('Y-m-d', strtotime($datosDoc['fecha']));
$nombreCaja = $datosDoc['nombre_caja'] . ' -> ' . $datosDoc['fecha_ini'];
$idCajaConst = $datosDoc['id_caja_const'];
$estadoDoc = $datosDoc['estado'];
$detalleDoc = $datosDoc['detalle'];
$idTercero = $datosDoc['id_tercero'];

// Botones de imputación según estado
$btnImputacion = '';
if ($estadoDoc == 1) {
    $btnImputacion = <<<HTML
    <a class="btn btn-outline-success btn-sm" onclick="ImputacionCtasCajas({$idCajaConst})"><span class="fas fa-plus fa-lg"></span></a>
HTML;
}

// Botón de forma de pago según estado
$btnFormaPago = '';
if ($estadoDoc == 1) {
    $btnFormaPago = <<<HTML
    <button class="btn btn-outline-primary btn-sm" onclick="cargaFormaPago({$id_cop},0,this)"><span class="fas fa-wallet fa-lg"></span></button>
HTML;
}

// Botón generar movimiento según estado
$btnGenerarMov = '';
if ($estadoDoc == 1) {
    $btnGenerarMov = <<<HTML
    <div class="row">
        <div class="col-2">
            <div><label for="fecha" class="small"></label></div>
        </div>
        <div class="col-2">
            <div class="text-align: center">
                <button type="button" class="btn btn-primary btn-sm" onclick="generaMovimientoCaja('{$id_doc_pag}')">Generar movimiento</button>
            </div>
        </div>
    </div>
HTML;
}

// Fila de agregar según estado
$filaAgregar = '';
if ($estadoDoc == '1') {
    $filaAgregar = <<<HTML
    <tr>
        <td>
            <input type="text" name="codigoCta" id="codigoCta" class="form-control form-control-sm bg-input" value="" required>
            <input type="hidden" name="id_codigoCta" id="id_codigoCta" class="form-control form-control-sm" value="0">
            <input type="hidden" name="tipoDato" id="tipoDato" value="0">
        </td>
        <td><input type="text" name="bTercero" id="bTercero" class="form-control form-control-sm bg-input" required>
            <input type="hidden" name="idTercero" id="idTercero" value="0">
        </td>
        <td>
            <input type="text" name="valorDebito" id="valorDebito" class="form-control form-control-sm bg-input text-end" value="0" required onkeyup="valorMiles(id)" onchange="llenarCero(id)">
        </td>
        <td>
            <input type="text" name="valorCredito" id="valorCredito" class="form-control form-control-sm bg-input text-end" value="0" required onkeyup="valorMiles(id)" onchange="llenarCero(id)">
        </td>
        <td class="text-center">
            <button text="0" class="btn btn-primary btn-sm" onclick="GestMvtoDetallePag(this)">Agregar</button>
        </td>
    </tr>
HTML;
}

$content = <<<HTML
<div class="card w-100">
    <div class="card-header bg-sofia text-white">
        <a class="btn btn-sm me-1 p-0" title="Regresar" href="javascript:void(0);" onclick="terminarDetalleTes({$tipo_dato},{$tipo_var})"><i class="fas fa-arrow-left fa-lg"></i></a>
        <b>DETALLE DEL COMPROBANTE DE CAJA MENOR</b>
    </div>
    <div class="card-body p-2 bg-wiev">
        <input type="hidden" id="peReg" value="{$peReg}">
        
        <form id="formAddDetallePag">
            <div class="row mb-2">
                <div class="col-md-2">
                    <label class="small fw-bold">NUMERO ACTO:</label>
                </div>
                <div class="col-md-10">
                    <input type="number" name="numDoc" id="numDoc" class="form-control form-control-sm bg-input" value="{$id_manu}" required readonly>
                    <input type="hidden" id="tipodato" name="tipodato" value="{$tipo_dato}">
                    <input type="hidden" id="id_cop_pag" name="id_cop_pag" value="{$id_cop}">
                    <input type="hidden" id="id_arqueo" name="id_arqueo" value="{$id_arq}">
                </div>
            </div>
            <div class="row mb-2">
                <div class="col-md-2">
                    <label class="small fw-bold">FECHA:</label>
                </div>
                <div class="col-md-10">
                    <input type="date" name="fecha" id="fecha" class="form-control form-control-sm bg-input" value="{$fechaFormateada}" readonly>
                </div>
            </div>
            <div class="row mb-2">
                <div class="col-md-2">
                    <label class="small fw-bold">TERCERO:</label>
                </div>
                <div class="col-md-10">
                    <input type="text" name="tercero" id="tercero" class="form-control form-control-sm bg-input" value="{$tercero}" required readonly>
                    <input type="hidden" name="id_tercero" id="id_tercero" value="{$idTercero}">
                </div>
            </div>
            <div class="row mb-2">
                <div class="col-md-2">
                    <label class="small fw-bold">CAJA:</label>
                </div>
                <div class="col-md-10">
                    <input type="text" name="referencia" id="referencia" value="{$nombreCaja}" class="form-control form-control-sm bg-input" readonly>
                </div>
            </div>
            <div class="row mb-3">
                <div class="col-md-2">
                    <label class="small fw-bold">OBJETO:</label>
                </div>
                <div class="col-md-10">
                    <textarea id="objeto" type="text" name="objeto" class="form-control form-control-sm bg-input py-0" rows="3" required readonly>{$detalleDoc}</textarea>
                </div>
            </div>
            <div class="row mb-2">
                <div class="col-md-2">
                    <label class="small fw-bold">IMPUTACION:</label>
                </div>
                <div class="col-md-4">
                    <div class="input-group input-group-sm">
                        <input type="text" name="valor" id="valor" value="{$val_imp}" class="form-control bg-input text-end" required readonly>
                        {$btnImputacion}
                    </div>
                </div>
            </div>
            <div class="row mb-2">
                <div class="col-md-2">
                    <label class="small fw-bold">FORMA DE PAGO:</label>
                </div>
                <div class="col-md-4">
                    <div class="input-group input-group-sm">
                        <input type="text" name="forma_pago" id="forma_pago" value="{$valor_pago}" class="form-control bg-input text-end" readonly>
                        {$btnFormaPago}
                    </div>
                </div>
            </div>
            {$btnGenerarMov}
            
            <input type="hidden" id="id_ctb_doc" name="id_ctb_doc" value="{$id_doc_pag}">
            
            <div class="table-responsive shadow p-2 mt-3">
                <table id="tableMvtoContableDetallePag" class="table table-striped table-bordered table-sm table-hover shadow w-100">
                    <thead class="text-center">
                        <tr>
                            <th class="bg-sofia" style="width: 35%;">Cuenta</th>
                            <th class="bg-sofia" style="width: 35%;">Tercero</th>
                            <th class="bg-sofia" style="width: 10%;">Debito</th>
                            <th class="bg-sofia" style="width: 10%;">Credito</th>
                            <th class="bg-sofia" style="width: 10%;">Acciones</th>
                        </tr>
                    </thead>
                    <tbody id="modificartableMvtoContableDetallePag">
                    </tbody>
                    {$filaAgregar}
                </table>
            </div>
        </form>
        
        <div class="text-center pt-4">
            <button type="button" class="btn btn-primary btn-sm" onclick="imprimirFormatoTes({$id_doc_pag});" style="width: 5rem;">
                <span class="fas fa-print"></span>
            </button>
            <a onclick="terminarDetalleTes({$tipo_dato},{$tipo_var})" class="btn btn-danger btn-sm" style="width: 7rem;" href="#">Terminar</a>
        </div>
    </div>
</div>

<script>
    window.onload = function() {
        buscarConsecutivoTeso('{$tipo_dato}');
    }
</script>
HTML;

$plantilla = new Plantilla($content, 2);
$plantilla->addCssFile("{$host}/assets/css/jquery-ui.css?v=" . date("YmdHis"));
$plantilla->addScriptFile("{$host}/assets/js/jquery-ui.js?v=" . date("YmdHis"));
$plantilla->addScriptFile("{$host}/src/tesoreria/js/funciontesoreria.js?v=" . date("YmdHis"));
$modal = $plantilla->getModal('divModalForms', 'divTamModalForms', 'divForms');
$plantilla->addModal($modal);
$modal = $plantilla->getModal('divModalReg', 'divTamModalReg', 'divFormsReg');
$plantilla->addModal($modal);
$modal = $plantilla->getModal('divModalImp', 'divTamModalImp', 'divImp');
$plantilla->addModal($modal);
$modal = $plantilla->getModal('divModalAux', 'divTamModalAux', 'divFormsAux');
$plantilla->addModal($modal);
echo $plantilla->render();
