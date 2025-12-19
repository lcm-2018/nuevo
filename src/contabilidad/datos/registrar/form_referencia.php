<?php
session_start();
if (!isset($_SESSION['user'])) {
    header("Location: ../../../index.php");
    exit();
}
include '../../../../config/autoloader.php';
$id_rol = $_SESSION['rol'];
$id_user = $_SESSION['id_user'];

use Config\Clases\Plantilla;
use Src\Common\Php\Clases\Permisos;

$permisos = new Permisos();
$opciones = $permisos->PermisoOpciones($id_user);

$id_doc = isset($_POST['id_doc']) ? $_POST['id_doc'] : exit('Acceso no permitido');

try {
    $cmd = \Config\Clases\Conexion::getConexion();
    $sql = "SELECT
                `ctb_referencia`.`id_ctb_referencia`
                , `ctb_referencia`.`id_cuenta`
                , `ctb_referencia`.`nombre`
                , `ctb_referencia`.`accion`
                , `ctb_referencia`.`estado`
                , IF(`pgcp`.`nombre` IS NULL, `ctb_pgcp`.`cuenta`,CONCAT(`ctb_pgcp`.`cuenta`,' - ', `pgcp`.`cuenta`)) AS `nom_cuenta`
            FROM `ctb_referencia`
                INNER JOIN `ctb_pgcp` 
                    ON (`ctb_referencia`.`id_cuenta` = `ctb_pgcp`.`id_pgcp`)
                LEFT JOIN `ctb_pgcp` AS `pgcp` 
                    ON (`ctb_referencia`.`id_cta_credito` = `pgcp`.`id_pgcp`)
            WHERE `ctb_referencia`.`id_ctb_fuente` = $id_doc";
    $rs = $cmd->query($sql);
    $referencias = $rs->fetchAll();
    $rs->closeCursor();
    unset($rs);
    $cmd = null;
} catch (PDOException $e) {
    echo $e->getCode() == 2002 ? 'Sin Conexión a Mysql (Error: 2002)' : 'Error: ' . $e->getCode();
}

?>
<script>
    function FormDocReferencia(id_doc_ref, id_ctb_ref) {
        $.post("datos/registrar/form_doc_referencia.php", {
            id_doc_ref: id_doc_ref,
            id_ctb_ref: id_ctb_ref
        }, function(he) {
            $("#divTamModalReg").removeClass("modal-xl");
            $("#divTamModalReg").removeClass("modal-sm");
            $("#divTamModalReg").removeClass("modal-lg");

            // Configurar el modal para que se muestre correctamente sobre el primero
            var $modalReg = $("#divModalReg");

            // Manejar el evento shown para ajustar z-index
            $modalReg.off('shown.bs.modal').on('shown.bs.modal', function() {
                // Ajustar z-index del modal y backdrop
                var zIndex = 1055;
                $(this).css('z-index', zIndex);
                $('.modal-backdrop').not('.modal-stack').css('z-index', zIndex - 5).addClass('modal-stack');

                // Configurar autocomplete con z-index alto
                configureNestedAutocomplete();
            });

            $modalReg.modal("show");
            $("#divFormsReg").html(he);
        });
    }

    // Función para configurar autocomplete en modales anidados
    function configureNestedAutocomplete() {
        // Configurar autocomplete para codigoCta1 y codigoCta2
        $('#codigoCta1, #codigoCta2').each(function() {
            var $input = $(this);
            var inputId = $input.attr('id');
            var num = inputId === 'codigoCta1' ? 1 : 2;

            $input.autocomplete({
                source: function(request, response) {
                    $.ajax({
                        url: "datos/consultar/consultaPgcp.php",
                        type: "post",
                        dataType: "json",
                        data: {
                            search: request.term,
                        },
                        success: function(data) {
                            response(data);
                        },
                    });
                },
                select: function(event, ui) {
                    $("#codigoCta" + num).val(ui.item.label);
                    $("#id_codigoCta" + num).val(ui.item.id);
                    $("#tipoDato" + num).val(ui.item.tipo_dato);
                    return false;
                },
                appendTo: "#divModalReg", // Anexar al modal para mejor control
                position: {
                    my: "left top",
                    at: "left bottom",
                    collision: "flip"
                }
            }).autocomplete("widget").css({
                "z-index": 9999,
                "max-height": "300px",
                "overflow-y": "auto"
            });
        });

        // Configurar autocomplete para rubroCod si existe
        if ($('#rubroCod').length) {
            $('#rubroCod').autocomplete({
                source: function(request, response) {
                    $.ajax({
                        url: "../presupuesto/datos/consultar/buscar_rubros.php",
                        type: "post",
                        dataType: "json",
                        data: {
                            term: request.term,
                            tipo: $('#tipoRubro').val() || '1'
                        },
                        success: function(data) {
                            response(data);
                        },
                    });
                },
                select: function(event, ui) {
                    $("#rubroCod").val(ui.item.label);
                    $("#id_rubroCod").val(ui.item.id);
                    $("#tipoRubro").val(ui.item.tipo);
                    return false;
                },
                appendTo: "#divModalReg",
                position: {
                    my: "left top",
                    at: "left bottom",
                    collision: "flip"
                },
                minLength: 2
            }).autocomplete("widget").css({
                "z-index": 9999,
                "max-height": "300px",
                "overflow-y": "auto"
            });
        }
    }

    $('#tableDocRefs').DataTable({
        language: dataTable_es,
        dom: setdom,
        buttons: [{
            text: '<span class="fa-solid fa-plus "></span>',
            className: 'btn btn-success btn-sm shadow',
            action: function(e, dt, node, config) {
                FormDocReferencia($('#id_doc_ft').val(), 0);
            },
        }, ],
        "pageLength": 10,
        "order": [
            [0, "desc"]
        ]
    });
    $('#tableDocRefs').wrap('<div class="overflow" />');

    function EditarDocReferencia(id_ctb_ref) {
        FormDocReferencia($('#id_doc_ft').val(), id_ctb_ref);
    }
</script>
<div class="px-0">
    <div class="shadow">
        <div class="card-header py-2 text-center" style="background-color: #16a085 !important;">
            <h5 class="mb-0" style="color: white;">DOCUMENTOS DE REFERENCIA</b></h5>
        </div>
        <input type="hidden" name="id_doc_ft" id="id_doc_ft" value="<?php echo $id_doc; ?>">
        <div class="px-3 py-3 text-start">
            <table id="tableDocRefs" class="table table-striped table-bordered table-sm nowrap table-hover shadow" style="width:100%">
                <thead>
                    <tr>
                        <th class="text-center bg-sofia">Cuenta</th>
                        <th class="text-center bg-sofia">Nombre</th>
                        <th class="text-center bg-sofia">Acción</th>
                        <th class="text-center bg-sofia">Estado</th>
                        <th class="text-center bg-sofia">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    if (!empty($referencias)) {
                        foreach ($referencias as $ref) {
                            $cerrar = $editar = $borrar = null;
                            $id_ctb = $ref['id_ctb_referencia'];
                            if ($ref['estado'] == 1) {
                                $estado = '<span class="badge rounded-pill text-bg-success">Activa</span>';
                            } else {
                                $estado = '<span class="badge rounded-pill text-bg-secondary">Inactiva</span>';
                            }
                            if ($ref['accion'] == '1') {
                                $accion = '<span class="badge rounded-pill text-bg-primary">Ingreso</span>';
                            } else {
                                $accion = '<span class="badge rounded-pill text-bg-warning">Gasto</span>';
                            }
                            if ($ref['estado'] == 1) {
                                $cerrar = '<button value="' . $id_ctb . '" class="btn btn-outline-warning btn-xs rounded-circle me-1 shadow" onclick="cerrarReferencia(' . $id_ctb . ')" title="Desactivar Referencia"><span class="fas fa-unlock "></span></button>';
                            } else {
                                $cerrar = '<button value="' . $id_ctb . '" class="btn btn-outline-secondary btn-xs rounded-circle me-1 shadow" onclick="abrirReferencia(' . $id_ctb . ')" title="Activar Referencia"><span class="fas fa-lock "></span></button>';
                            }
                            if (($permisos->PermisosUsuario($opciones, 5505, 3) || $id_rol == 1) && ($ref['estado'] == 1)) {
                                $editar = '<button onclick="EditarDocReferencia(' . $id_ctb . ')" class="btn btn-outline-primary btn-xs rounded-circle me-1 shadow"  title="Editar Referencia"><span class="fas fa-pencil-alt "></span></button>';
                            }
                            if ($permisos->PermisosUsuario($opciones, 5505, 4) || $id_rol == 1 && ($ref['estado'] == 1)) {
                                $borrar = '<button value="' . $id_ctb . '" onclick="eliminarReferencia(' . $id_ctb . ')" class="btn btn-outline-danger btn-xs rounded-circle me-1 shadow "  title="Eliminar"><span class="fas fa-trash-alt "></span></button>';
                            }
                            echo '<tr>
                                <td class="text-end">' . $ref['nom_cuenta'] . '</td>
                                <td>' . $ref['nombre'] . '</td>
                                <td class="text-center">' . $accion . '</td>
                                <td class="text-center">' . $estado . '</td>
                                <td class="text-center">' . $editar . $cerrar . $borrar . '</td>
                            </tr>';
                        }
                    }
                    ?>
                </tbody>
            </table>
        </div>
        <div class="text-end pb-3 px-4 w-100">
            <a type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancelar</a>
        </div>
    </div>
</div>