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
            <h5 class="text-white mb-0">BASES DE DATOS A CONSULTAR</h5>            
        </div>
        <input type="hidden" id="id_consulta_bd" name="id_consulta_bd" value="<?php echo $id ?>">
        <div class="p-3">            
            <div class="table-responsive shadow p-2">             
                <table id="tb_consulta_bdatos" class="table table-striped table-bordered table-sm nowrap table-hover shadow w-100" style="font-size:80%">
                    <thead>
                        <tr class="text-center centro-vertical">
                            <th class="bg-sofia">Id</th>
                            <th class="bg-sofia">Entidad</th>
                            <th class="bg-sofia">Base Datos</th>                            
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
        