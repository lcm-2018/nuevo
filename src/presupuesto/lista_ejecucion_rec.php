<?php
session_start();
if (!isset($_SESSION['user'])) {
    header('Location: ../../index.php');
    exit();
}
include '../../config/autoloader.php';
$id_rol = $_SESSION['rol'];
$id_user = $_SESSION['id_user'];

use Config\Clases\Plantilla;
use Src\Common\Php\Clases\Permisos;

$permisos = new Permisos();
$opciones = $permisos->PermisoOpciones($id_user);
$host = Plantilla::getHost();
include '../financiero/consultas.php';

$id_rec = isset($_POST['id_rec']) ? intval($_POST['id_rec']) : exit('Acceso no permitido');
$id_ppto = isset($_POST['id_ejec']) ? intval($_POST['id_ejec']) : 0;

$datosRec = [];
$fecha_cierre = '';
try {
    $cmd = \Config\Clases\Conexion::getConexion();
    $sql = "SELECT
                `pto_rec`.`id_pto_rec`
                , `pto_rec`.`id_pto`
                , `pto_rec`.`id_manu`
                , `pto_rec`.`fecha`
                , `pto_rec`.`objeto`
                , `pto_rec`.`num_factura`
                , CONCAT(`tb_terceros`.`nom_tercero`, ' -> ', `tb_terceros`.`nit_tercero`) AS `tercero`
                , `pto_rec`.`id_tercero_api`
            FROM
                `pto_rec`
            LEFT JOIN `tb_terceros`
                ON (`pto_rec`.`id_tercero_api` = `tb_terceros`.`id_tercero_api`)
            WHERE `pto_rec`.`id_pto_rec` = :id_rec";
    $stmt = $cmd->prepare($sql);
    $stmt->bindValue(':id_rec', $id_rec, PDO::PARAM_INT);
    $stmt->execute();
    $datosRec = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
    $stmt->closeCursor();
    unset($stmt);

    if ($id_ppto <= 0 && !empty($datosRec['id_pto'])) {
        $id_ppto = intval($datosRec['id_pto']);
    }

    $fecha_cierre = fechaCierre($_SESSION['vigencia'], 54, $cmd);
    $cmd = null;
} catch (PDOException $e) {
    echo $e->getCode() == 2002 ? 'Sin Conexión a Mysql (Error: 2002)' : 'Error: ' . $e->getCode();
}

$fecha = !empty($datosRec['fecha']) ? date('Y-m-d', strtotime($datosRec['fecha'])) : '';
$fecha_max = date("Y-m-d", strtotime($_SESSION['vigencia'] . '-12-31'));
$id_manu = htmlspecialchars((string)($datosRec['id_manu'] ?? ''), ENT_QUOTES, 'UTF-8');
$tercero = htmlspecialchars((string)($datosRec['tercero'] ?? ''), ENT_QUOTES, 'UTF-8');
$id_tercero = intval($datosRec['id_tercero_api'] ?? 0);
$objeto = htmlspecialchars((string)($datosRec['objeto'] ?? ''), ENT_QUOTES, 'UTF-8');
$num_factura = htmlspecialchars((string)($datosRec['num_factura'] ?? ''), ENT_QUOTES, 'UTF-8');

$content =
    <<<HTML
    <div class="card w-100">
        <div class="card-header bg-sofia text-white">
            <a class="btn btn-sm me-1 p-0 volverListaRecs" title="Regresar" href="#"><i class="fas fa-arrow-left fa-lg"></i></a>
            <b>DETALLE RECAUDO PRESUPUESTAL</b>
        </div>
        <div class="card-body p-2 bg-wiev">
            <div id="divFormDoc">
                <form id="formDetalleRecDoc">
                    <input type="hidden" id="id_pto_presupuestos" name="id_pto_presupuestos" value="{$id_ppto}">
                    <input type="hidden" id="id_recs" name="id_recs" value="{$id_rec}">
                    <input type="hidden" id="id_pto_docini" value="{$id_manu}">
                    <div class="right-block">
                        <div class="row pb-2">
                            <div class="col-md-2">
                                <label for="numRec" class="form-label small">NUMERO REC:</label>
                            </div>
                            <div class="col-md-10">
                                <input type="number" name="numRec" id="numRec" class="form-control form-control-sm bg-input" value="{$id_manu}" readonly>
                            </div>
                        </div>
                        <div class="row pb-2">
                            <div class="col-md-2">
                                <label for="fecha" class="form-label small">FECHA:</label>
                            </div>
                            <div class="col-md-10">
                                <input type="date" name="fecha" id="fecha" class="form-control form-control-sm bg-input" min="{$fecha_cierre}" max="{$fecha_max}" value="{$fecha}" readonly>
                            </div>
                        </div>
                        <div class="row pb-2">
                            <div class="col-md-2">
                                <label for="tercero" class="form-label small">TERCERO:</label>
                            </div>
                            <div class="col-md-10">
                                <input type="text" name="tercero" id="tercero" class="form-control form-control-sm bg-input" value="{$tercero}" readonly>
                                <input type="hidden" id="id_tercero" name="id_tercero" value="{$id_tercero}">
                            </div>
                        </div>
                        <div class="row pb-2">
                            <div class="col-md-2">
                                <label for="objeto" class="form-label small">OBJETO:</label>
                            </div>
                            <div class="col-md-10">
                                <textarea id="objeto" name="objeto" class="form-control form-control-sm py-0" rows="3" readonly>{$objeto}</textarea>
                            </div>
                        </div>
                        <div class="row pb-2">
                            <div class="col-md-2">
                                <label for="solicitud" class="form-label small">No Factura:</label>
                            </div>
                            <div class="col-md-10">
                                <input type="text" name="solicitud" id="solicitud" class="form-control form-control-sm bg-input" value="{$num_factura}" readonly>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
            <div class="table-responsive shadow p-2">
                <table id="tableEjecRec" class="table table-striped table-bordered table-sm table-hover shadow w-100 align-middle">
                    <thead>
                        <tr class="text-center">
                            <th class="bg-sofia" style="width: 8%;">ID</th>
                            <th class="bg-sofia" style="width: 72%;">Codigo</th>
                            <th class="bg-sofia" style="width: 20%;">Valor</th>
                        </tr>
                    </thead>
                    <tbody id="modificarEjeRec">
                    </tbody>
                </table>
            </div>
            <div class="text-center pt-4">
HTML;

if ($permisos->PermisosUsuario($opciones, 5401, 6) || $id_rol == 1) {
    $content .= <<<HTML
                <a type="button" class="btn btn-primary btn-sm" onclick="imprimirFormatoRec({$id_rec});" style="width: 5rem;">
                    <span class="fas fa-print"></span>
                </a>
HTML;
}

$content .= <<<HTML
                <a type="button" id="volverListaRecs" class="btn btn-danger btn-sm volverListaRecs" style="width: 5rem;" href="#">VOLVER</a>
            </div>
        </div>
    </div>
    HTML;

$plantilla = new Plantilla($content, 2);
$plantilla->addCssFile("{$host}/assets/css/jquery-ui.css?v=" . date("YmdHis"));
$plantilla->addScriptFile("{$host}/assets/js/jquery-ui.js?v=" . date("YmdHis"));
$plantilla->addScriptFile("{$host}/src/presupuesto/js/libros_aux_pto/common.js?v=" . date("YmdHis"));
$plantilla->addScriptFile("{$host}/src/presupuesto/js/funcionpresupuesto.js?v=" . date("YmdHis"));
$modal = $plantilla->getModal('divModalForms', 'divTamModalForms', 'divForms');
$plantilla->addModal($modal);
$modal = $plantilla->getModal('divModalReg', 'divTamModalReg', 'divFormsReg');
$plantilla->addModal($modal);
$modal = $plantilla->getModal('divModalImp', 'divTamModalImp', 'divImp');
$plantilla->addModal($modal);
echo $plantilla->render();
