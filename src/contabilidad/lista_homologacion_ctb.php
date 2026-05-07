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
$peReg = $permisos->PermisosUsuario($opciones, 5401, 2) || $id_rol == 1 ? 1 : 0;
$host = Plantilla::getHost();

$vigencia = $_SESSION['id_vigencia'];

// Consulta Plan Único de Cuentas (PUC)
try {
    $cmd = \Config\Clases\Conexion::getConexion();
    $sql = "SELECT 
                p.`id_pgcp`, 
                p.`cuenta`, 
                p.`nombre`, 
                p.`estado`, 
                p.`tipo_dato`,
                h.`id_cuenta_otros`,
                h.`id_cuenta_1009`,
                c.`id_form`
            FROM `ctb_pgcp` AS p
            LEFT JOIN `ctb_homologacion` AS h ON p.`id_pgcp` = h.`id_cuenta` AND h.`id_vigencia` = $vigencia
            LEFT JOIN `ctb_ctas_exogena` AS c ON h.`id_cuenta_otros` = c.`id_cuenta`
            ORDER BY p.`cuenta` ASC";
    $rs = $cmd->query($sql);
    $plan_cuentas = $rs->fetchAll(PDO::FETCH_ASSOC);
    $rs->closeCursor();
    unset($rs);
    $cmd = null;
} catch (PDOException $e) {
    echo $e->getCode() == 2002 ? 'Sin Conexión a Mysql (Error: 2002)' : 'Error: ' . $e->getCode();
}

// 1. Consulta para el primer Select (Formularios Exógena)
try {
    $cmd = \Config\Clases\Conexion::getConexion();
    $sql = "SELECT `id_form` AS id, CONCAT(`codigo`, ' - ', `descripcion`) AS nombre FROM `ctb_form_exogena`";
    $rs = $cmd->query($sql);
    $formularios = $rs ? $rs->fetchAll(PDO::FETCH_ASSOC) : [];
    if ($rs) $rs->closeCursor();
    unset($rs);
    $cmd = null;
} catch (PDOException $e) {
    $formularios = [];
}

// 2. Consulta para el segundo Select (Cuentas dependientes del Formulario)
try {
    $cmd = \Config\Clases\Conexion::getConexion();
    $sql = "SELECT `id_cuenta` AS id, `id_form`, CONCAT(`cod_concepto`, ' - ', `concepto`) AS nombre FROM `ctb_ctas_exogena`";
    $rs = $cmd->query($sql);
    $cuentas_form = $rs ? $rs->fetchAll(PDO::FETCH_ASSOC) : [];
    if ($rs) $rs->closeCursor();
    unset($rs);
    $cmd = null;
} catch (PDOException $e) {
    $cuentas_form = [];
}

// 3. Consulta para el tercer Select (Cuentas específicas del Formulario 1009)
try {
    $cmd = \Config\Clases\Conexion::getConexion();
    $sql = "SELECT CE.`id_cuenta` AS id, CONCAT(CE.`cod_concepto`, ' - ', CE.`concepto`) AS nombre 
            FROM `ctb_ctas_exogena` AS CE
            INNER JOIN `ctb_form_exogena` AS FE ON CE.`id_form` = FE.`id_form`
            WHERE FE.`codigo` = '1009'";
    $rs = $cmd->query($sql);
    $cuentas_1009 = $rs ? $rs->fetchAll(PDO::FETCH_ASSOC) : [];
    if ($rs) $rs->closeCursor();
    unset($rs);
    $cmd = null;
} catch (PDOException $e) {
    $cuentas_1009 = [];
}

ob_start();
?>
<table id="tableHomologaCtb" class="table table-striped table-bordered table-sm align-middle nowrap shadow w-100" style="font-size:12px;">
    <thead style="position: sticky !important; top: 0 !important; z-index: 999 !important;">
        <tr class="text-center">
            <th class="bg-sofia">Cuenta</th>
            <th class="bg-sofia">Nombre</th>
            <th class="bg-sofia">
                <div class="d-flex justify-content-center px-4">
                    <input type="checkbox" id="desmarcarCtb" title="Desmarcar Todos">
                </div>
            </th>
            <th class="bg-sofia">Formulario Exógena</th>
            <th class="bg-sofia">Concepto (Depende de Form.)</th>
            <th class="bg-sofia">Concepto Form. 1009</th>
        </tr>
    </thead>
    <tbody id="modificaHomologaCtb">
        <?php foreach ($plan_cuentas as $cuenta) {
            $tp_cta = isset($cuenta['tipo_dato']) ? $cuenta['tipo_dato'] : 'D';
            $colspan = $tp_cta == 'D' ? 1 : 5;
            $nom_cta = $tp_cta == 'M' ? "<b>" . $cuenta['nombre'] . "</b>" : $cuenta['nombre'];
            $cod_cta = $tp_cta == 'M' ? "<b>" . $cuenta['cuenta'] . "</b>" : $cuenta['cuenta'];
        ?>
            <tr>
                <td><?= $cod_cta ?></td>
                <td <?= $tp_cta == 'M' ? "colspan='$colspan'" : "" ?>><?= $nom_cta ?></td>
                <?php if ($tp_cta == 'D') { ?>
                    <td class="text-center">
                        <div class="d-flex justify-content-center">
                            <input type="checkbox" name="dupLineCtb[]" class="dupLineCtb" value="<?= $cuenta['id_pgcp'] ?>" title="Copiar datos de otra linea">
                        </div>
                    </td>
                    <td class="p-0">
                        <select class="form-select form-select-sm border-0 py-0 px-1 selFormulario" name="formulario[<?= $cuenta['id_pgcp'] ?>]" data-id="<?= $cuenta['id_pgcp'] ?>">
                            <option value="0">--Seleccionar--</option>
                            <?php foreach ($formularios as $f) {
                                $selForm = (isset($cuenta['id_form']) && $cuenta['id_form'] == $f['id']) ? 'selected' : '';
                            ?>
                                <option value="<?= $f['id'] ?>" <?= $selForm ?>><?= $f['nombre'] ?></option>
                            <?php } ?>
                        </select>
                    </td>
                    <td class="p-0">
                        <select class="form-select form-select-sm border-0 py-0 px-1 selCuentasForm" name="id_cuenta_otros[<?= $cuenta['id_pgcp'] ?>]" id="cuentas_form_<?= $cuenta['id_pgcp'] ?>" style="width: 100%; min-width: 250px;">
                            <option value="0" class="default-opt">--Seleccionar--</option>
                            <?php foreach ($cuentas_form as $c) {
                                $selOtros = (isset($cuenta['id_cuenta_otros']) && $cuenta['id_cuenta_otros'] == $c['id']) ? 'selected' : '';
                                $display = (isset($cuenta['id_form']) && $cuenta['id_form'] == $c['id_form']) ? '' : 'display:none;';
                            ?>
                                <option value="<?= $c['id'] ?>" data-form="<?= $c['id_form'] ?>" style="<?= $display ?>" <?= $selOtros ?>><?= $c['nombre'] ?></option>
                            <?php } ?>
                        </select>
                    </td>
                    <td class="p-0">
                        <select class="form-select form-select-sm border-0 py-0 px-1 selCuentas1009" name="id_cuenta_1009[<?= $cuenta['id_pgcp'] ?>]" style="width: 100%; min-width: 250px;">
                            <option value="0">--Seleccionar--</option>
                            <?php foreach ($cuentas_1009 as $c1009) {
                                $sel1009 = (isset($cuenta['id_cuenta_1009']) && $cuenta['id_cuenta_1009'] == $c1009['id']) ? 'selected' : '';
                            ?>
                                <option value="<?= $c1009['id'] ?>" <?= $sel1009 ?>><?= $c1009['nombre'] ?></option>
                            <?php } ?>
                        </select>
                    </td>
                <?php } ?>
            </tr>
        <?php } ?>
    </tbody>
</table>

<!-- Script para la lógica solicitada -->
<script>
    document.addEventListener('DOMContentLoaded', function() {

        // 1. Lógica del select dependiente (Filtro por formulario)
        const container = document.getElementById('modificaHomologaCtb');

        if (container) {
            container.addEventListener('change', function(e) {
                if (e.target.classList.contains('selFormulario')) {
                    const idFormulario = e.target.value;
                    const rowId = e.target.getAttribute('data-id');
                    const selCuentasForm = document.getElementById('cuentas_form_' + rowId);

                    // Resetear el dependiente
                    selCuentasForm.value = "0";

                    // Ocultar / Mostrar opciones basadas en id_form
                    Array.from(selCuentasForm.options).forEach(opt => {
                        if (opt.classList.contains('default-opt')) return; // mantener --Seleccionar--
                        if (idFormulario != "0" && opt.getAttribute('data-form') == idFormulario) {
                            opt.style.display = '';
                        } else {
                            opt.style.display = 'none';
                        }
                    });
                }
            });
        }

        // 2. Lógica para desmarcar y duplicar linea
        if (typeof jQuery !== 'undefined') {
            $('#desmarcarCtb').on('click', function() {
                var elemento = $(this);
                $('.dupLineCtb').each(function() {
                    if ($(this).is(':checked')) {
                        $(this).prop("checked", false);
                    }
                });
                elemento.prop("checked", false);
            });

            $('#modificaHomologaCtb').on('click', '.dupLineCtb', function() {
                var elemento = $(this);
                var id = $(this).val();
                var valForm = '0';
                var valCtaForm = '0';
                var valCta1009 = '0';

                if (elemento.is(':checked')) {
                    $('#desmarcarCtb').prop("checked", true);

                    $('.dupLineCtb').each(function() {
                        var id_ctb = $(this).val();
                        if ($(this).is(':checked')) {
                            valForm = $('select[name="formulario[' + id_ctb + ']"]').val();
                            valCtaForm = $('select[name="id_cuenta_otros[' + id_ctb + ']"]').val();
                            valCta1009 = $('select[name="id_cuenta_1009[' + id_ctb + ']"]').val();
                            return false; // Salir del loop al encontrar el primer checked
                        }
                    });

                    var selectFormDest = $('select[name="formulario[' + id + ']"]');
                    var selectCtaFormDest = $('select[name="id_cuenta_otros[' + id + ']"]');
                    var selectCta1009Dest = $('select[name="id_cuenta_1009[' + id + ']"]');

                    selectFormDest.val(valForm);

                    // Disparar change para filtrar el select dependiente antes de asignarle el valor
                    const event = new Event('change', {
                        bubbles: true
                    });
                    selectFormDest[0].dispatchEvent(event);

                    // Setear valores de los otros selects
                    selectCtaFormDest.val(valCtaForm);
                    selectCta1009Dest.val(valCta1009);
                }
            });

            // Evento para el botón Modificar (Guardar datos)
            $('#setHomologacionCtb').on('click', function() {
                var btn = $(this);
                var form_data = $('#formDataHomolCtb').serialize();

                btn.prop('disabled', true).text('Guardando...');
                if (typeof mostrarOverlay === 'function') mostrarOverlay();

                $.ajax({
                    type: 'POST',
                    url: 'datos/actualizar/update_homologacion_ctb.php',
                    data: form_data,
                    success: function(r) {
                        if (r.trim() === 'ok') {
                            if (typeof mje === 'function') {
                                mje('Homologación guardada correctamente');
                            } else {
                                alert('Homologación guardada correctamente');
                            }
                            setTimeout(function() {
                                location.reload();
                            }, 1000);
                        } else {
                            if (typeof mjeError === 'function') {
                                mjeError('Error al guardar', r);
                            } else {
                                alert('Error: ' + r);
                            }
                            btn.prop('disabled', false).text('Modificar');
                        }
                    },
                    error: function() {
                        if (typeof mjeError === 'function') {
                            mjeError('Error', 'Fallo la petición AJAX al servidor');
                        } else {
                            alert('Fallo la petición AJAX');
                        }
                        btn.prop('disabled', false).text('Modificar');
                    }
                }).always(function() {
                    if (typeof ocultarOverlay === 'function') ocultarOverlay();
                });
            });
        }
    });
</script>
<?php
$tabla_html = ob_get_clean();

$content =
    <<<HTML
    <div class="card w-100">
        <div class="card-header bg-sofia text-white">
            <button class="btn btn-sm me-1 p-0" title="Regresar" onclick="window.history.back();"><i class="fas fa-arrow-left fa-lg"></i></button>
            <b>HOMOLOGACIÓN CONTABILIDAD EXÓGENA (PUC)</b>
        </div>
        <div class="card-body p-2 bg-wiev">
            <input type="hidden" id="peReg" value="{$peReg}">
            
            <div class="table-responsive shadow p-2" style="max-height: 75vh; overflow-y: auto;">
                <form id="formDataHomolCtb">
                    $tabla_html
                </form>
            </div>
            
            <div class="text-center pt-4">
                <button type="button" class="btn btn-secondary btn-sm" style="width: 7rem;" onclick="window.history.back();">Regresar</button>
                <button type="button" class="btn btn-success btn-sm" style="width: 7rem;" id="setHomologacionCtb">Modificar</button>
            </div>
        </div>
    </div>
    HTML;

$plantilla = new Plantilla($content, 2);
$plantilla->addCssFile("{$host}/assets/css/jquery-ui.css?v=" . date("YmdHis"));
$plantilla->addScriptFile("{$host}/assets/js/jquery-ui.js?v=" . date("YmdHis"));
$plantilla->addScriptFile("{$host}/src/contabilidad/js/funcioncontabilidad.js?v=" . date("YmdHis"));
echo $plantilla->render();
