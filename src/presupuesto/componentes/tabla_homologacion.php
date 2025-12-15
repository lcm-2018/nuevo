<?php
// Componente de tabla de homologación
// Este archivo es incluido desde lista_homologacion_pto.php
// Variables disponibles: $nomPresupuestos, $ingreso, $gasto, $rubros, $homologacion, $situacion
?>
<table id="tableHomologaPto" class="table table-striped table-bordered table-sm align-middle nowrap shadow w-100" style="font-size:12px;">
    <thead style="position: sticky !important; top: 0 !important; z-index: 999 !important;">
        <tr class="text-center">
            <?php
            if ($nomPresupuestos['id_tipo'] == 1) {
            ?>
                <th class="bg-sofia">Código</th>
                <th class="bg-sofia">Nombre</th>
                <th class="bg-sofia">
                    <div class="d-flex justify-content-center px-4">
                        <input type="checkbox" id="desmarcar" title="Desmarcar Todos">
                        <input type="hidden" value="<?php echo $ingreso ?>" name="ingreso">
                    </div>
                </th>
                <th class="bg-sofia">Código CGR</th>
                <th class="bg-sofia">Vigencia</th>
                <th class="bg-sofia">CPC</th>
                <th class="bg-sofia">Fuente</th>
                <th class="bg-sofia">Terceros</th>
                <th class="bg-sofia">Política<br>Pública</th>
                <th class="bg-sofia">SIHO</th>
                <th class="bg-sofia">SIA</th>
                <th class="bg-sofia">Situación<br>Fondos</th>
            <?php
            } else if ($nomPresupuestos['id_tipo'] == 2) {
            ?>
                <th class="bg-sofia">Código</th>
                <th class="bg-sofia">Nombre</th>
                <th class="bg-sofia">
                    <div class="d-flex justify-content-center px-4">
                        <input type="checkbox" id="desmarcar" title="Desmarcar Todos">
                        <input type="hidden" value="<?php echo $gasto ?>" name="gasto">
                    </div>
                </th>
                <th class="bg-sofia">Codigo CGR</th>
                <th class="bg-sofia">Vigencia</th>
                <th class="bg-sofia">Sección<br>Presupuesto</th>
                <th class="bg-sofia">Sector</th>
                <th class="bg-sofia">CPC</th>
                <th class="bg-sofia">Fuente</th>
                <th class="bg-sofia">Terceros</th>
                <th class="bg-sofia">Política<br>Pública</th>
                <th class="bg-sofia">SIHO</th>
                <th class="bg-sofia">SIA</th>
                <th class="bg-sofia">Clase<br>SIA</th>
                <th class="bg-sofia">Situación<br>Fondos</th>
                <th class="bg-sofia" title="Mantenimiento hospitalario">MH</th>
            <?php
            }
            ?>
        </tr>
    </thead>
    <tbody id="modificaHomologaPto">
        <?php
        foreach ($rubros as $rb) {
            $tp_cta = $rb['tipo_dato'] == 0 ? 'M' : 'D';
            echo "<tr>";
            echo "<td>" . $rb['cod_pptal'] . "</td>";
            if ($nomPresupuestos['id_tipo'] == 1) {
                $colspan = $tp_cta == 'D' ? 1 : 11;
                echo "<td colspan='" . $colspan . "'>" . $rb['nom_rubro'] . "</td>";
                if ($tp_cta == 'D') {
                    $key = array_search($rb['id_cargue'], array_column($homologacion, 'id_cargue'));
                    echo "<td class='text-center'>
                            <div class='d-flex justify-content-center'>
                                <input type='checkbox' name='pto1[]' class='dupLine' value='" . $rb['id_cargue'] . "' title='Copiar datos de otra linea'>
                                <input type='hidden' value='" . ($key !== false ? $homologacion[$key]['id_homologacion'] : 0) . "' name='idHomol[" . $rb['id_cargue'] . "]'>
                            </div>
                        </td>";
                    echo "<td class='p-0'>
                            <input tipo='1' type='text' class='form-control form-control-sm border-0 py-0 px-1 homologaPTO' name='uno[" . $rb['id_cargue'] . "]' value='" . ($key !== false ? $homologacion[$key]['codigo_cgr'] . ' -> ' . $homologacion[$key]['nombre_cgr'] : '') . "'>
                            <input type='hidden' class='validaPto srow' name='codCgr[" . $rb['id_cargue'] . "]' value='" . ($key !== false ? $homologacion[$key]['id_cgr'] : 0) . "'>
                        </td>";
                    $val_vig = $key !== false ? $homologacion[$key]['id_vigencia'] : 0;
                    echo "<td class='p-0'>
                        <select class='form-select form-select-sm border-0 py-0 px-1 validaPto homologaPTO'  name='vigencia[" . $rb['id_cargue'] . "]'>
                            <option value='0' " . ($val_vig == 0 ? 'selected' : '') . ">--Seleccionar--</option>
                            <option value='1' " . ($val_vig == 1 ? 'selected' : '') . ">ACTUAL</option>
                            <option value='2' " . ($val_vig == 2 ? 'selected' : '') . ">ANTERIOR</option>
                        </select>
                    </td>";
                    echo "<td class='p-0'>
                            <input tipo='5' type='text' class='form-control form-control-sm border-0 py-0 px-1 homologaPTO' name='cinco[" . $rb['id_cargue'] . "]' value='" . ($key !== false ? $homologacion[$key]['codigo_cpc'] . ' -> ' . $homologacion[$key]['nombre_cpc'] : '') . "'>
                            <input type='hidden' class='validaPto' name='cpc[" . $rb['id_cargue'] . "]'  value='" . ($key !== false ? $homologacion[$key]['id_cpc'] : 0) . "'>
                        </td>";
                    echo "<td class='p-0'>
                            <input tipo='6' type='text' class='form-control form-control-sm border-0 py-0 px-1 homologaPTO' name='seis[" . $rb['id_cargue'] . "]' value='" . ($key !== false ? $homologacion[$key]['codigo_fte'] . ' -> ' . $homologacion[$key]['nombre_fte'] : '') . "'>
                            <input type='hidden' class='validaPto' name='fuente[" . $rb['id_cargue'] . "]'  value='" . ($key !== false ? $homologacion[$key]['id_fuente'] : 0) . "'>
                        </td>";
                    echo "<td class='p-0'>
                            <input tipo='7' type='text' class='form-control form-control-sm border-0 py-0 px-1 homologaPTO' name='siete[" . $rb['id_cargue'] . "]' value='" . ($key !== false ? $homologacion[$key]['codigo_ter'] . ' -> ' . $homologacion[$key]['nombre_ter'] : '') . "'>
                            <input type='hidden' class='validaPto' name='tercero[" . $rb['id_cargue'] . "]'  value='" . ($key !== false ? $homologacion[$key]['id_tercero'] : 0) . "'>
                        </td>";
                    echo "<td class='p-0'>
                            <input tipo='8' type='text' class='form-control form-control-sm border-0 py-0 px-1 homologaPTO' name='ocho[" . $rb['id_cargue'] . "]' value='" . ($key !== false ? $homologacion[$key]['codigo_pol'] . ' -> ' . $homologacion[$key]['nombre_pol'] : '') . "'>
                            <input type='hidden' class='validaPto' name='polPub[" . $rb['id_cargue'] . "]'  value='" . ($key !== false ? $homologacion[$key]['id_politica'] : 0) . "'>
                        </td>";
                    echo "<td class='p-0'>
                        <input tipo='9' type='text' class='form-control form-control-sm border-0 py-0 px-1 homologaPTO' name='nueve[" . $rb['id_cargue'] . "]' value='" . ($key !== false ? $homologacion[$key]['codigo_siho'] . ' -> ' . $homologacion[$key]['nombre_siho'] : '') . "'>
                        <input type='hidden' class='validaPto' name='siho[" . $rb['id_cargue'] . "]'  value='" . ($key !== false ? $homologacion[$key]['id_siho'] : 0) . "'>
                    </td>";
                    echo "<td class='p-0'>
                        <input tipo='10' type='text' class='form-control form-control-sm border-0 py-0 px-1 homologaPTO' name='diez[" . $rb['id_cargue'] . "]' value='" . ($key !== false ? $homologacion[$key]['codigo_sia'] . ' -> ' . $homologacion[$key]['nombre_sia'] : '') . "'>
                        <input type='hidden' class='validaPto' name='sia[" . $rb['id_cargue'] . "]'  value='" . ($key !== false ? $homologacion[$key]['id_sia'] : 0) . "'>
                    </td>";
                    echo "<td class='p-0'>
                            <select class='form-select form-select-sm border-0 py-0 px-1 homologaPTO validaPto'  name='situacion[" . $rb['id_cargue'] . "]'>
                                <option value='0'>--Seleccionar--</option>";

                    foreach ($situacion as $s) {
                        $val_sit = $key !== false ? $homologacion[$key]['id_situacion'] : 0;
                        $slc = $val_sit == $s['id_situacion'] ? 'selected' : '';
                        echo '<option value="' . $s['id_situacion'] . '" ' . $slc . '>' . $s['concepto'] . '</option>';
                    }
                    echo    "</select>
                        </td>";
                }
            } else if ($nomPresupuestos['id_tipo'] == 2) {
                $colspan = $tp_cta == 'D' ? 1 : 14;
                echo "<td colspan='" . $colspan . "'>" . $rb['nom_rubro'] . "</td>";
                if ($tp_cta == 'D') {
                    $key = false;
                    $key = array_search($rb['id_cargue'], array_column($homologacion, 'id_cargue'));
                    echo "<td class='text-center'>
                            <div class='d-flex justify-content-center'>
                                <input type='checkbox' name='pto2[]' class='dupLine' value='" . $rb['id_cargue'] . "' title='Copiar datos de otra linea'>
                                <input type='hidden' value='" . ($key !== false ? $homologacion[$key]['id_homologacion'] : 0) . "' name='idHomol[" . $rb['id_cargue'] . "]'>
                            </div>
                        </td>";
                    echo "<td class='p-0'>
                            <input tipo='1' type='text' class='form-control form-control-sm border-0 py-0 px-1 homologaPTO' name='uno[" . $rb['id_cargue'] . "]' value='" . ($key !== false ? $homologacion[$key]['codigo_cgr'] . ' -> ' . $homologacion[$key]['nombre_cgr'] : '') . "'>
                            <input type='hidden' class='validaPto srow' name='codCgr[" . $rb['id_cargue'] . "]' value='" . ($key !== false ? $homologacion[$key]['id_cgr'] : 0) . "'>
                        </td>";
                    echo "<td class='p-0'>
                            <input tipo='2' type='text' class='form-control form-control-sm border-0 py-0 px-1 homologaPTO' name='dos[" . $rb['id_cargue'] . "]' value='" . ($key !== false ? $homologacion[$key]['codigo_vig'] . ' -> ' . $homologacion[$key]['nombre_vig'] : '') . "'>
                            <input type='hidden' class='validaPto' name='vigencia[" . $rb['id_cargue'] . "]' value='" . ($key !== false ? $homologacion[$key]['id_vigencia'] : 0) . "'>
                        </td>";
                    echo "<td class='p-0'>
                            <input tipo='3' type='text' class='form-control form-control-sm border-0 py-0 px-1 homologaPTO' name='tres[" . $rb['id_cargue'] . "]' value='" . ($key !== false ? $homologacion[$key]['codigo_secc'] . ' -> ' . $homologacion[$key]['nombre_secc'] : '') . "'>
                            <input type='hidden' class='validaPto' name='seccion[" . $rb['id_cargue'] . "]' value='" . ($key !== false ? $homologacion[$key]['id_seccion'] : 0) . "'>
                        </td>";
                    echo "<td class='p-0'>
                            <input tipo='4' type='text' class='form-control form-control-sm border-0 py-0 px-1 homologaPTO' name='cuatro[" . $rb['id_cargue'] . "]' value='" . ($key !== false ? $homologacion[$key]['codigo_sect'] . ' -> ' . $homologacion[$key]['nombre_sect'] : '') . "'>
                            <input type='hidden' class='validaPto' name='sector[" . $rb['id_cargue'] . "]' value='" . ($key !== false ? $homologacion[$key]['id_sector'] : 0) . "'>
                        </td>";
                    echo "<td class='p-0'>
                            <input tipo='5' type='text' class='form-control form-control-sm border-0 py-0 px-1 homologaPTO' name='cinco[" . $rb['id_cargue'] . "]' value='" . ($key !== false ? $homologacion[$key]['codigo_cpc'] . ' -> ' . $homologacion[$key]['nombre_cpc'] : '') . "'>
                            <input type='hidden' class='validaPto' name='cpc[" . $rb['id_cargue'] . "]'  value='" . ($key !== false ? $homologacion[$key]['id_cpc'] : 0) . "'>
                        </td>";
                    echo "<td class='p-0'>
                            <input tipo='6' type='text' class='form-control form-control-sm border-0 py-0 px-1 homologaPTO' name='seis[" . $rb['id_cargue'] . "]' value='" . ($key !== false ? $homologacion[$key]['codigo_fte'] . ' -> ' . $homologacion[$key]['nombre_fte'] : '') . "'>
                            <input type='hidden' class='validaPto' name='fuente[" . $rb['id_cargue'] . "]'  value='" . ($key !== false ? $homologacion[$key]['id_fuente'] : 0) . "'>
                        </td>";
                    echo "<td class='p-0'>
                            <input tipo='7' type='text' class='form-control form-control-sm border-0 py-0 px-1 homologaPTO' name='siete[" . $rb['id_cargue'] . "]' value='" . ($key !== false ? $homologacion[$key]['codigo_ter'] . ' -> ' . $homologacion[$key]['nombre_ter'] : '') . "'>
                            <input type='hidden' class='validaPto' name='tercero[" . $rb['id_cargue'] . "]'  value='" . ($key !== false ? $homologacion[$key]['id_tercero'] : 0) . "'>
                        </td>";
                    echo "<td class='p-0'>
                            <input tipo='8' type='text' class='form-control form-control-sm border-0 py-0 px-1 homologaPTO' name='ocho[" . $rb['id_cargue'] . "]' value='" . ($key !== false ? $homologacion[$key]['codigo_pol'] . ' -> ' . $homologacion[$key]['nombre_pol'] : '') . "'>
                            <input type='hidden' class='validaPto' name='polPub[" . $rb['id_cargue'] . "]'  value='" . ($key !== false ? $homologacion[$key]['id_politica'] : 0) . "'>
                        </td>";
                    echo "<td class='p-0'>
                        <input tipo='9' type='text' class='form-control form-control-sm border-0 py-0 px-1 homologaPTO' name='nueve[" . $rb['id_cargue'] . "]' value='" . ($key !== false ? $homologacion[$key]['codigo_siho'] . ' -> ' . $homologacion[$key]['nombre_siho'] : '') . "'>
                        <input type='hidden' class='validaPto' name='siho[" . $rb['id_cargue'] . "]'  value='" . ($key !== false ? $homologacion[$key]['id_siho'] : 0) . "'>
                    </td>";
                    echo "<td class='p-0'>
                        <input tipo='10' type='text' class='form-control form-control-sm border-0 py-0 px-1 homologaPTO' name='diez[" . $rb['id_cargue'] . "]' value='" . ($key !== false ? $homologacion[$key]['codigo_sia'] . ' -> ' . $homologacion[$key]['nombre_sia'] : '') . "'>
                        <input type='hidden' class='validaPto' name='sia[" . $rb['id_cargue'] . "]'  value='" . ($key !== false ? $homologacion[$key]['id_sia'] : 0) . "'>
                    </td>";
                    echo "<td class='p-0'>
                        <input tipo='11' type='text' class='form-control form-control-sm border-0 py-0 px-1 homologaPTO' name='once[" . $rb['id_cargue'] . "]' value='" . ($key !== false ? $homologacion[$key]['codigo_csia'] . ' -> ' . $homologacion[$key]['nombre_csia'] : '') . "'>
                        <input type='hidden' class='validaPto' name='csia[" . $rb['id_cargue'] . "]'  value='" . ($key !== false ? $homologacion[$key]['id_csia'] : 0) . "'>
                    </td>";
                    echo "<td class='p-0'>
                            <select class='form-select form-select-sm border-0 py-0 px-1 homologaPTO validaPto'  name='situacion[" . $rb['id_cargue'] . "]'>
                                <option value='0'>--Seleccionar--</option>";

                    foreach ($situacion as $s) {
                        $val_sit = $key !== false ? $homologacion[$key]['id_situacion'] : 0;
                        $slc = $val_sit == $s['id_situacion'] ? 'selected' : '';
                        echo '<option value="' . $s['id_situacion'] . '" ' . $slc . '>' . $s['concepto'] . '</option>';
                    }
                    $cero = 'checked';
                    $uno = $key !== false && ($homologacion[$key]['id_mh'] == '1') ? 'checked' : '';
                    if ($uno == 'checked') {
                        $cero = '';
                    }
                    echo    '</select>
                        </td>
                        <td class="p-0">
                            <div class="mb-0">
                                <div class="form-control form-control-sm d-inline-flex align-items-center border-0">
                                    <div class="form-check form-check-inline m-0 me-1">
                                        <input type="radio" id="si_' . $rb['id_cargue'] . '" name="mmto_h[' . $rb['id_cargue'] . ']" class="form-check-input" ' . $uno . ' value="1">
                                        <label class="form-check-label" for="si_' . $rb['id_cargue'] . '">Sí</label>
                                    </div>
                                    <div class="form-check form-check-inline m-0">
                                        <input type="radio" id="no_' . $rb['id_cargue'] . '" name="mmto_h[' . $rb['id_cargue'] . ']" class="form-check-input" ' . $cero . ' value="0">
                                        <label class="form-check-label" for="no_' . $rb['id_cargue'] . '">No</label>
                                    </div>
                                </div>
                            </div>
                        </td>';
                } else {
                    echo "<td colspan='13'></td>";
                }
            }
            echo "</tr>";
        }
        ?>
    </tbody>
</table>