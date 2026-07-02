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
$permisos = new Permisos();

$id_rol = $_SESSION['rol'];
$id_user = $_SESSION['id_user'];
$opciones = $permisos->PermisoOpciones($id_user);

$content = <<<HTML

<div class="card w-100">
    <div class="card-header bg-sofia text-white">
        <a class="btn btn-xs me-1 p-0" title="Regresar" href="{$host}/src/inicio.php" fff><i class="fas fa-arrow-left"></i></a>
        <b>CONSULTAS ANALÍTICAS</b>
    </div>
    <div class="card-body p-2 bg-wiev" id="divCuerpoPag">
        <div class="row">
            <div class="col-md-6">
                <div class="row">
                    <div class="col-md-2">
                        <input type="text" class="filtro form-control form-control-sm" id="txt_id_filtro" placeholder="Id.">
                    </div>    
                    <div class="col-md-5">    
                        <input type="text" class="filtro form-control form-control-sm" id="txt_titulo_filtro" placeholder="Título de la Consulta">                        
                    </div>
                    <div class="col-md-2">
                        <a type="button" id="btn_buscar_filtro" class="btn btn-outline-success btn-sm" title="Filtrar">
                            <span class="fas fa-search fa-lg" aria-hidden="true"></span>
                        </a>
                    </div>    
                </div>        
                <div class="row mt-2">
                    <table id="tb_consultas" class="table table-striped table-bordered table-sm nowrap table-hover shadow align-middle" style="width:100%; font-size:80%">
                        <thead>
                            <tr class="text-center">
                                <th class="bg-sofia">Id</th>
                                <th class="bg-sofia">Título</th>
                                <th class="bg-sofia">Acciones</th>
                            </tr>
                        </thead>
                    </table>
                </div>
            </div>
            <div class="col-md-6">                                
                <input type="text" class="form-control form-control-sm" id="txt_id_consulta">
                <div class="col-md-12 mb-2">
                    <div id="txt_detalle_consulta" class="form-control form-control-sm" style="height:300px; overflow:auto;"></div>
                </div>
                <div class="col-md-12 mb-2">
                    <form id="frm_parametros"></form>                
                </div>    
                <div class="col-md-12">
                    <a type="button" id="btn_ejecutar_consulta" class="btn btn-warning btn-sm">
                        <span class="d-flex align-items-center">
                            <i class="fas fa-images fa-lg me-1" aria-hidden="true"></i>
                            <span class="small">Ejecutar Consulta Analítica</span>
                        </span>
                    </a>                    
                </div>
            </div>
        </div>  
        <div class="row"> 
            <div id="dv_resultado"></div>         
        </div>    
    </div>
</div>
HTML;

$plantilla = new Plantilla($content, 2);
$plantilla->addCssFile("{$host}/assets/css/jquery-ui.css?v=" . date("YmdHis"));
$plantilla->addScriptFile("{$host}/assets/js/jquery-ui.js?v=" . date("YmdHis"));
$plantilla->addScriptFile("{$host}/src/analytics/visu_consultas/js/v_consultas.js?v=" . date("YmdHis"));
$plantilla->addScriptFile("{$host}/src/analytics/common/js/common.js?v=" . date("YmdHis"));
$modal = $plantilla->getModal('divModalForms', 'divTamModalForms', 'divForms');
$plantilla->addModal($modal);
$modal = $plantilla->getModal('divModalReg', 'divTamModalReg', 'divFormsReg');
$plantilla->addModal($modal);
$modal = $plantilla->getModal('divModalImp', 'divTamModalImp', 'divImp');
$plantilla->addModal($modal);
echo $plantilla->render();

/*
<!--Cuerpo Principal del formulario -->
<div class="card-body" id="divCuerpoPag">
    
</div>
*/