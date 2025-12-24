<?php
session_start();

/* Activar si desea verificar Errores desde el Servidor
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
*/

if (!isset($_SESSION['user'])) {
    header("Location: ../../../index.php");
    exit();
}

include '../../../../config/autoloader.php';


use Src\Common\Php\Clases\Permisos;

$id_rol = $_SESSION['rol'];
$id_user = $_SESSION['id_user'];

$permisos = new Permisos();
$opciones = $permisos->PermisoOpciones($id_user);
include '../common/cargar_combos.php';
include '../common/funciones_generales.php';

$cmd = \Config\Clases\Conexion::getConexion();

?>

<!DOCTYPE html>
<html lang="es">
<?php include '../../../head.php' ?>

<body class="sb-nav-fixed <?php if ($_SESSION['navarlat'] == '1') {
                                echo 'sb-sidenav-toggled';
                            } ?>">
    <?php include '../../../navsuperior.php' ?>
    <div id="layoutSidenav">
        <?php include '../../../navlateral.php' ?>
        <div id="layoutSidenav_content">
            <main>
                <div class="container-fluid p-2">
                    <div class="card mb-4">
                        <div class="card-header" id="divTituloPag">
                            <div class="row mb-2">
                                <div class="col-md-11">
                                    <i class="fas fa-list-ul fa-lg" style="color:#1D80F7"></i>
                                    TRASLADOS DE ACTIVOS FIJOS
                                </div>
                            </div>
                        </div>

                        <!--Cuerpo Principal del formulario -->
                        <div class="card-body" id="divCuerpoPag">

                            <!--Opciones de filtros -->
                            <div class="row mb-2">
                                <div class="col-md-2">
                                    <select class="form-select form-select-sm bg-input" id="sl_sedori_filtro">
                                        <?php sedes($cmd, '--Sede Origen--') ?>
                                    </select>
                                </div>
                                <div class="col-md-2">
                                    <select class="form-select form-select-sm bg-input" id="sl_areori_filtro">
                                    </select>
                                </div>
                                <div class="col-md-2">
                                    <select class="form-select form-select-sm bg-input" id="sl_resori_filtro">
                                        <?php usuarios($cmd, '--Responsable Origen--') ?>
                                    </select>
                                </div>
                                <div class="col-md-1">
                                    <input type="text" class="filtro form-control form-control-sm bg-input" id="txt_id_traslado_filtro" placeholder="Id. traslado">
                                </div>
                                <div class="col-md-3">
                                    <div class="row mb-2">
                                        <div class="col-md-6">
                                            <input type="date" class="form-control form-control-sm bg-input" id="txt_fecini_filtro" name="txt_fecini_filtro" placeholder="Fecha Inicial">
                                        </div>
                                        <div class="col-md-6">
                                            <input type="date" class="form-control form-control-sm bg-input" id="txt_fecfin_filtro" name="txt_fecfin_filtro" placeholder="Fecha Final">
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-2">
                                    <select class="form-select form-select-sm bg-input" id="sl_seddes_filtro">
                                        <?php sedes($cmd, '--Sede Destino--') ?>
                                    </select>
                                </div>
                                <div class="col-md-2">
                                    <select class="form-select form-select-sm bg-input" id="sl_aredes_filtro">
                                    </select>
                                </div>
                                <div class="col-md-2">
                                    <select class="form-select form-select-sm bg-input" id="sl_resdes_filtro">
                                        <?php usuarios($cmd, '--Responsable destino--') ?>
                                    </select>
                                </div>
                                <div class="col-md-1">
                                    <select class="form-select form-select-sm bg-input" id="sl_estado_filtro">
                                        <?php estados_movimientos('--Estado--') ?>
                                    </select>
                                </div>
                                <div class="col-md-1">
                                    <a type="button" id="btn_buscar_filtro" class="btn btn-outline-success btn-sm" title="Filtrar">
                                        <span class="fas fa-search fa-lg" aria-hidden="true"></span>
                                    </a>
                                    <a type="button" id="btn_imprime_filtro" class="btn btn-outline-success btn-sm" title="Imprimir">
                                        <span class="fas fa-print" aria-hidden="true"></span>
                                    </a>
                                </div>
                            </div>

                            <!--Lista de registros en la tabla-->
                            <?php
                            if ($permisos->PermisosUsuario($opciones, 5708, 2) || $id_rol == 1) {
                                echo '<input type="hidden" id="peReg" value="1">';
                            } else {
                                echo '<input type="hidden" id="peReg" value="0">';
                            }
                            ?>
                            <table id="tb_traslados" class="table table-striped table-bordered table-sm nowrap table-hover shadow" style="width:100%; font-size:80%">
                                <thead>
                                    <tr class="text-center">
                                        <th rowspan="2">Id</th>
                                        <th rowspan="2">Fecha traslado</th>
                                        <th rowspan="2">Hora traslado</th>
                                        <th rowspan="2">Observaciones</th>
                                        <th colspan="3">Unidad Origen</th>
                                        <th colspan="3">Unidad Destino</th>
                                        <th rowspan="2">Id.Estado</th>
                                        <th rowspan="2">Estado</th>
                                        <th rowspan="2">Acciones</th>
                                    </tr>
                                    <tr class="text-center">
                                        <th>Sede</th>
                                        <th>Area</th>
                                        <th>Responsable</th>
                                        <th>Sede</th>
                                        <th>Area</th>
                                        <th>Responsable</th>
                                    </tr>
                                </thead>
                            </table>
                            <table class="table-bordered table-sm col-md-2">
                                <tr>
                                    <td style="background-color:yellow">Pendiente</td>
                                    <td>Cerrado</td>
                                    <td style="background-color:gray">Anulado</td>
                                </tr>
                            </table>
                        </div>
                    </div>
                </div>
            </main>
            <?php include '../../../footer.php' ?>
        </div>
        <?php include '../../../modales.php' ?>
    </div>
    <?php include '../../../scripts.php' ?>
    <script type="text/javascript" src="../../js/traslados/traslados.js?v=<?php echo date('YmdHis') ?>"></script>
</body>

</html>