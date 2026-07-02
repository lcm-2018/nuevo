<?php
session_start();
if (!isset($_SESSION['user'])) {
    header("Location: ../../../index.php");
    exit();
}
include '../../../../config/autoloader.php';
include '../../common/php/cargar_combos.php';

$id = isset($_POST['id_consulta']) ? (int)$_POST['id_consulta'] : -1;

?>
<div class="px-0">
    <div class="shadow">
        <div class="card-header py-2 text-center" style="background-color: #16a085 !important;">
            <h5 class="text-white mb-0">USUARIOS AUTORIZADOS</h5>
        </div>
        <div class="p-3">
            <form id="frm_reg_consulta_user">   
                <input type="hidden" id="id_consulta_us" name="id_consulta_us" value="<?php echo $id ?>">
                <div class="row">
                    <label for="txt_unimed_art" class="small">Usuario de Sistemas</label>
                </div>    
                <div class="row">
                    <div class="col-md-9">                        
                        <input type="text" class="form-control form-control-sm bg-input" id="txt_usuario_sistema" name="txt_usuario_sistema">
                        <input type="hidden" id="id_txt_usuario_sistema" name="id_txt_usuario_sistema">
                    </div>
                    <div class="col-md-1">                        
                        <button type="button" id="btn_add_usuario" class="btn btn-outline-success btn-sm" title="Agregar Usuario">
                            <span class="fas fa-user-plus" aria-hidden="true"></span>                                       
                        </button>
                    </div>    
                </div>    
            </form>    
            <div class="table-responsive shadow p-2">             
                <table id="tb_consulta_user" class="table table-striped table-bordered table-sm nowrap table-hover shadow w-100" style="font-size:80%">
                    <thead>
                        <tr class="text-center centro-vertical">
                            <th class="bg-sofia">Id</th>
                            <th class="bg-sofia">Identificación</th>
                            <th class="bg-sofia">Usuario</th>
                            <th class="bg-sofia">Cargo</th>
                            <th class="bg-sofia">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="text-start centro-vertical"></tbody>
                </table>
            </div>            
        </div>
    </div>
    <div class="text-center pt-3">        
        <a type="button" class="btn btn-secondary  btn-sm" data-bs-dismiss="modal">Cancelar</a>
    </div>
</div>
        