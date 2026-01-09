<?php
session_start();
if (!isset($_SESSION['user'])) {
    header("Location: ../../../../../index.php");
    exit();
}

include_once '../../../../config/autoloader.php';

use Config\Clases\Plantilla;
use Src\Common\Php\Clases\Permisos;


$host = Plantilla::getHost();
$numeral = 1;
$permisos = new Permisos();

$id_rol = $_SESSION['rol'];
$id_user = $_SESSION['id_user'];
$opciones = $permisos->PermisoOpciones($id_user);

$content = <<<HTML

<div class="card w-100">
    <div class="card-header bg-sofia text-white">
        <a class="btn btn-xs me-1 p-0" title="Regresar" href="{$host}/src/inicio.php" fff><i class="fas fa-arrow-left"></i></a>
        <b>Reportes Personalizados</b>
    </div>
    <div class="card-body p-2 bg-wiev" id="divCuerpoPag">
        <table style="width:100% !important">
            <tr>
                <td style="width:50% !important" class="align-top">
                    <div class="row g-2">
                        <input type="hidden" id="txt_id_opcion" value="{$_POST['id_opcion']}">
                        <div class="col-md-6">
                            <input type="text" class="filtro form-control form-control-sm" id="txt_nombre_filtro" placeholder="Nombre">
                        </div>
                        <div class="col-md-6">
                            <a type="button" id="btn_buscar_filtro" class="btn btn-outline-success btn-sm" title="Filtrar">
                                <span class="fas fa-search fa-lg" aria-hidden="true"></span>
                            </a>
                        </div>
                        <div class="col-md-12 mt-2">
                            <table id="tb_consultas" class="table table-striped table-bordered table-sm nowrap table-hover shadow align-middle" style="width:100%; font-size:80%">
                                <thead>
                                    <tr class="text-center">
                                        <th class="bg-sofia">Id</th>
                                        <th class="bg-sofia">Nombre</th>
                                    </tr>
                                </thead>
                            </table>
                        </div>
                    </div>
                </td>
                <td style="width:50% !important">
                    <div class="row g-2">
                        <input type="hidden" class="form-control form-control-sm" id="txt_id_consulta" name="txt_id_consulta" readonly="readonly">
                        <div class="col-md-12">
                            <label for="txt_nom_consulta" class="small">Detalles del Reporte</label>
                            <input type="text" class="form-control form-control-sm" id="txt_nom_consulta" name="txt_nom_consulta" readonly="readonly">
                        </div>
                        <div class="col-md-12">
                            <textarea class="form-control form-control-sm" id="txt_des_consulta" name="txt_des_consulta" rows="3" readonly="readonly"></textarea>
                        </div>
                        <div class="col-md-6">
                            <form id="frm_parametros"></form>
                        </div>
                        <div class="col-md-12">
                            <a type="button" id="btn_buscar_consulta" class="btn btn-outline-success btn-sm">
                                <span class="d-flex align-items-center">
                                    <i class="fas fa-search fa-lg me-1" aria-hidden="true"></i>
                                    <span class="small">Consultar</span>
                                </span>
                            </a>
                            <a type="button" id="btn_imprimir_consulta" class="btn btn-outline-success btn-sm">
                                <span class="d-flex align-items-center">
                                    <i class="fas fa-print fa-lg me-1" aria-hidden="true"></i>
                                    <span class="small">Imprimir</span>
                                </span>
                            </a>
                            <a type="button" id="btn_exportar_consulta" class="btn btn-outline-success btn-sm">
                                <span class="d-flex align-items-center">
                                    <i class="fas fa-file-excel fa-lg me-1" aria-hidden="true"></i>
                                    <span class="small">Exportar</span>
                                </span>
                            </a>
                            <span id="lbl_archivo"></span>
                        </div>
                        <div class="col-md-4">
                            <label for="txt_limite" class="small">Límite Registros a Visualizar</label>
                            <input type="number" class="form-control form-control-sm" id="txt_limite" name="txt_limite" value="100">
                        </div>
                        <div class="col-md-8">
                            <span class="small">
                                Esto solo aplica en el caso de visualizar los datos en pantalla.
                                Utilice la opción Exportar para envía el total de los datos a un archivo.
                                En consultas grandes y/o pesadas es recomendable limitar el máximo de registros
                                a visualizar.
                            </span>
                        </div>
                    </div>
                </td>
            </tr>
        </table>
        <div id="dv_resultado"></div>
    </div>
</div>
HTML;

$plantilla = new Plantilla($content, 2);
$plantilla->addScriptFile("{$host}/src/inf_generales/js/inf_personalizados/inf_personalizados.js?v=" . date("YmdHis"));
$modal = $plantilla->getModal('divModalImp', 'divTamModalImp', 'divImp');
$plantilla->addModal($modal);
echo $plantilla->render();
/*
<!--Cuerpo Principal del formulario -->
<div class="card-body" id="divCuerpoPag">
    
</div>
*/