<?php

use Src\Common\Php\Clases\Imprimir;
use Src\Common\Php\Clases\Reportes as CReportes;
use Src\Common\Php\Clases\Valores;
use Src\Nomina\Liquidacion\Php\Clases\Nomina;
use Src\Nomina\Liquidado\Php\Clases\Detalles;
use Src\Usuarios\Login\Php\Clases\Usuario;

session_start();
if (!isset($_SESSION['user'])) {
    header("Location: ../../../../../index.php");
    exit();
}

include_once '../../../../../config/autoloader.php';
$datos = isset($_POST['id']) ? explode('|', base64_decode($_POST['id'])) : exit('Acceso denegado');
$id_empleado = isset($datos[0]) ? (int) $datos[0] : 0;
$id_nomina = isset($datos[1]) ? (int) $datos[1] : 0;

// id_sede: 0 = todos, >0 = sede específica (solo aplica cuando id_empleado == 0)
$id_sede = isset($_POST['id_sede']) ? (int) $_POST['id_sede'] : null;

$documento = "Desprendible de Nómina";
$usuario = new Usuario();
$empresa = $usuario->getEmpresa();
$nomina = Nomina::getRegistro($id_nomina);
$mes = mb_strtoupper(Valores::NombreMes($nomina['mes']));
$otro = "NÓMINA No. {$id_nomina} - MES: {$mes} - VIGENCIA: {$nomina['vigencia']}";

$detalles = new Detalles();
$pendientesAgrupados = $detalles->getDescuentosPendientesAgrupadosPorNomina($id_nomina);
$esRetroactivo = !empty($pendientesAgrupados)
    || (isset($nomina['tipo']) && in_array(strtoupper(trim($nomina['tipo'])), ['RA', '5']))
    || (!empty($nomina['id_incremento']) && intval($nomina['id_incremento']) > 0);

if ($id_empleado > 0) {
    // ── Modo individual: un solo empleado ──
    $empleado = $detalles->getRegistrosDT(1, -1, ['id_empleado' => $id_empleado, 'id_nomina' => $id_nomina], 1, 'ASC');
    $empleados = [$empleado];
} elseif ($id_sede !== null) {
    // ── Modo masivo por sede (viene desde el botón PDF del formulario de reportes) ──
    $filtros = ['id_nomina' => $id_nomina];
    if ($id_sede > 0) {
        $filtros['id_sede'] = $id_sede;
        $documento = "Desprendibles de Nómina - Sede";
    } else {
        $documento = "Desprendibles de Nómina - Todos";
    }
    $empleados = $detalles->getRegistrosDT(1, -1, $filtros, 1, 'ASC');
} else {
    // ── Modo masivo: todos los empleados de la nómina (compatibilidad anterior) ──
    $empleados = $detalles->getRegistrosDT(1, -1, ['id_nomina' => $id_nomina], 1, 'ASC');
    $documento = "Desprendibles de Nómina - Masivo";
}

if (empty($empleados)) {
    exit('No se encontraron empleados para generar el desprendible.');
}

/**
 * Función para generar el HTML de un desprendible individual
 */
function generarDesprendible($d)
{
    global $detalles, $id_nomina, $esRetroactivo, $pendientesAgrupados;

    // Obtener detalles discriminados
    $detalle_horas = $detalles->getDetalleHorasExtras($d['id_empleado'], $id_nomina);
    $detalle_libranzas = $detalles->getDetalleLibranzas($d['id_empleado'], $id_nomina);
    $detalle_embargos = $detalles->getDetalleEmbargos($d['id_empleado'], $id_nomina);
    $detalle_sindicatos = $detalles->getDetalleSindicatos($d['id_empleado'], $id_nomina);
    $detalle_otros_dctos = $detalles->getDetalleOtrosDescuentos($d['id_empleado'], $id_nomina);
    $detalle_viaticos = $detalles->getDetalleViaticos($d['id_empleado'], $id_nomina);
    $detalle_otros_dev = $detalles->getDetalleOtrosDevengados($d['id_empleado'], $id_nomina);

    // Obtener descuentos pendientes para nómina retroactiva (de mapa agrupado o consulta directa)
    $idEmp = isset($d['id_empleado']) ? (int) $d['id_empleado'] : 0;
    $itemsPendientes = [];
    if ($idEmp > 0 && isset($pendientesAgrupados[$idEmp])) {
        $itemsPendientes = $pendientesAgrupados[$idEmp];
    } elseif ($idEmp > 0) {
        $itemsPendientes = $detalles->getDescuentosPendientesEmpleado($idEmp, $id_nomina);
    }

    $retencion_pendiente = 0;
    $pendientesPorConcepto = [];
    $pendientesMostrados = [];
    foreach ($itemsPendientes as $ip) {
        $idC = intval($ip['id_concepto']);
        $pendientesPorConcepto[$idC] = $ip;
        if ($idC === 19) {
            $retencion_pendiente += floatval($ip['valor_pendiente']);
        }
    }

    // Calcular valores adicionales
    $valor_licencias = ($d['valor_luto'] ?? 0) + ($d['valor_mp'] ?? 0);

    $lbl_incap = (($d['dias_incapacidad'] ?? 0) > 0) ? 'Incapacidades (' . $d['dias_incapacidad'] . ' días)' : 'Incapacidades';
    $lbl_lic = (($d['dias_licencias'] ?? 0) > 0) ? 'Licencias (' . $d['dias_licencias'] . ' días)' : 'Licencias';
    $lbl_vac = (($d['dias_vacaciones'] ?? 0) > 0) ? 'Vacaciones (' . $d['dias_vacaciones'] . ' días)' : 'Vacaciones';

    // Definir conceptos de devengados
    $devengados = [
        'Salario Laborado' => $d['valor_laborado'] ?? 0,
        'Compensatorio' => $d['val_compensa'] ?? 0,
        $lbl_incap => $d['valor_incap'] ?? 0,
        $lbl_lic => $valor_licencias,
        $lbl_vac => $d['valor_vacacion'] ?? 0,
        'Prima de Vacaciones' => $d['val_prima_vac'] ?? 0,
        'Bonificación Recreación' => $d['val_bon_recrea'] ?? 0,
        'Auxilio de Transporte' => $d['aux_tran'] ?? 0,
        'Auxilio de Alimentación' => $d['aux_alim'] ?? 0,
        'Bonificación Servicios Prestados' => $d['val_bsp'] ?? 0,
        'Gastos de Representación' => $d['g_representa'] ?? 0,
        'Prima de Servicios' => $d['valor_ps'] ?? 0,
        'Prima de Navidad' => $d['valor_pv'] ?? 0,
        'Cesantías' => $d['val_cesantias'] ?? 0,
        'Intereses Cesantías' => $d['val_icesantias'] ?? 0,
        'Indemnización' => $d['val_indemniza'] ?? 0,
    ];

    // Horas extras: usar detalle discriminado si existe, o el valor acumulado de la nómina
    $total_horas = !empty($detalle_horas) ? array_sum(array_column($detalle_horas, 'valor')) : floatval($d['horas_ext'] ?? 0);
    if ($total_horas > 0) {
        $devengados['Horas Extras'] = $total_horas;
    }

    // Viáticos: usar detalle discriminado si existe, o el valor acumulado de la nómina
    $total_viaticos = !empty($detalle_viaticos) ? array_sum(array_column($detalle_viaticos, 'valor')) : floatval($d['valor_viatico'] ?? 0);
    if ($total_viaticos > 0) {
        $devengados['Viáticos'] = $total_viaticos;
    }

    // Otros devengados: usar detalle discriminado si existe, o el valor acumulado de la nómina
    $total_otros_dev = !empty($detalle_otros_dev) ? array_sum(array_column($detalle_otros_dev, 'valor')) : floatval($d['valor_otros'] ?? 0);
    if ($total_otros_dev > 0) {
        $devengados['Otros Devengados'] = $total_otros_dev;
    }

    // Definir conceptos de deducciones
    $deducciones = [
        'Aporte Salud (4%)' => $d['valor_salud'] ?? 0,
        'Aporte Pensión (4%)' => $d['valor_pension'] ?? 0,
        'Pensión Solidaria' => $d['val_psolidaria'] ?? 0,
        'Retención en la Fuente' => $d['val_retencion'] ?? 0,
    ];

    // Deducciones discriminadas con fallback a valores acumulados de la nómina
    $total_libranzas = !empty($detalle_libranzas) ? array_sum(array_column($detalle_libranzas, 'valor')) : floatval($d['valor_libranza'] ?? 0);
    if ($total_libranzas > 0) {
        $deducciones['Libranzas'] = $total_libranzas;
    }

    $total_embargos = !empty($detalle_embargos) ? array_sum(array_column($detalle_embargos, 'valor')) : floatval($d['valor_embargo'] ?? 0);
    if ($total_embargos > 0) {
        $deducciones['Embargos'] = $total_embargos;
    }

    $total_sindicatos = !empty($detalle_sindicatos) ? array_sum(array_column($detalle_sindicatos, 'valor')) : floatval($d['valor_sind'] ?? 0);
    if ($total_sindicatos > 0) {
        $deducciones['Sindicato'] = $total_sindicatos;
    }

    $total_otros = !empty($detalle_otros_dctos) ? array_sum(array_column($detalle_otros_dctos, 'valor')) : floatval($d['valor_dcto'] ?? 0);
    if ($total_otros > 0) {
        $deducciones['Otros Descuentos'] = $total_otros;
    }

    // Filtrar solo los que tienen valor > 0
    $devengados = array_filter($devengados, function ($v) {
        return $v > 0;
    });
    $deducciones = array_filter($deducciones, function ($v) {
        return $v > 0;
    });

    // Calcular totales
    $total_devengado = array_sum($devengados);
    $total_deducciones = array_sum($deducciones);
    $neto_pagar = max(0, $total_devengado - $total_deducciones);

    // Formatear valores para el HTML
    $sal_base_fmt = number_format($d['sal_base'] ?? 0, 0, ',', '.');
    $total_devengado_fmt = number_format($total_devengado, 0, ',', '.');
    $total_deducciones_fmt = number_format($total_deducciones, 0, ',', '.');
    $neto_pagar_fmt = number_format($neto_pagar, 0, ',', '.');

    // Generar filas de devengados
    $filas_devengados = '';
    foreach ($devengados as $concepto => $valor) {
        $valor_fmt = number_format($valor, 0, ',', '.');
        $filas_devengados .= "<tr>
            <td style='padding: 4px 8px;'>{$concepto}</td>
            <td style='text-align: right; padding: 4px 8px;'>{$valor_fmt}</td>
        </tr>";

        // Si es Horas Extras y tiene detalle, mostrar el desglose
        if ($concepto === 'Horas Extras' && !empty($detalle_horas)) {
            foreach ($detalle_horas as $hora) {
                $valor_hora_fmt = number_format($hora['valor'], 0, ',', '.');
                $filas_devengados .= "<tr style='font-size: 9px;'>
                    <td style='padding: 2px 8px 2px 20px; color: #666;'>• {$hora['tipo']} ({$hora['cantidad']} hrs)</td>
                    <td style='text-align: right; padding: 2px 8px; color: #666;'>{$valor_hora_fmt}</td>
                </tr>";
            }
        }

        // Si es Viáticos y tiene detalle, mostrar el desglose
        if ($concepto === 'Viáticos' && !empty($detalle_viaticos)) {
            foreach ($detalle_viaticos as $viatico) {
                $valor_v_fmt = number_format($viatico['valor'], 0, ',', '.');
                $filas_devengados .= "<tr style='font-size: 9px;'>
                    <td style='padding: 2px 8px 2px 20px; color: #666;'>- Res: {$viatico['resolucion']} - Dest: {$viatico['destino']}</td>
                    <td style='text-align: right; padding: 2px 8px; color: #666;'>{$valor_v_fmt}</td>
                </tr>";
            }
        }

        if ($concepto === 'Otros Devengados' && !empty($detalle_otros_dev)) {
            foreach ($detalle_otros_dev as $otroDev) {
                $valor_od_fmt = number_format($otroDev['valor'], 0, ',', '.');
                $filas_devengados .= "<tr style='font-size: 9px;'>
                    <td style='padding: 2px 8px 2px 20px; color: #666;'>- {$otroDev['tipo']}: {$otroDev['concepto']}</td>
                    <td style='text-align: right; padding: 2px 8px; color: #666;'>{$valor_od_fmt}</td>
                </tr>";
            }
        }
    }

    // Generar filas de deducciones
    $filas_deducciones = '';
    foreach ($deducciones as $concepto => $valor) {
        $valor_fmt = number_format($valor, 0, ',', '.');
        $filas_deducciones .= "<tr>
            <td style='padding: 4px 8px;'>{$concepto}</td>
            <td style='text-align: right; padding: 4px 8px;'>{$valor_fmt}</td>
        </tr>";

        // Si es Libranzas y tiene detalle, mostrar el desglose
        if ($concepto === 'Libranzas' && !empty($detalle_libranzas)) {
            foreach ($detalle_libranzas as $libranza) {
                $valor_lib_fmt = number_format($libranza['valor'], 0, ',', '.');
                $filas_deducciones .= "<tr style='font-size: 9px;'>
                    <td style='padding: 2px 8px 2px 20px; color: #666;'>• {$libranza['entidad']}</td>
                    <td style='text-align: right; padding: 2px 8px; color: #666;'>{$valor_lib_fmt}</td>
                </tr>";
            }
        }

        // Si es Embargos y tiene detalle, mostrar el desglose
        if ($concepto === 'Embargos' && !empty($detalle_embargos)) {
            foreach ($detalle_embargos as $embargo) {
                $valor_emb_fmt = number_format($embargo['valor'], 0, ',', '.');
                $filas_deducciones .= "<tr style='font-size: 9px;'>
                    <td style='padding: 2px 8px 2px 20px; color: #666;'>• {$embargo['descripcion']}</td>
                    <td style='text-align: right; padding: 2px 8px; color: #666;'>{$valor_emb_fmt}</td>
                </tr>";
            }
        }

        // Si es Sindicato y tiene detalle, mostrar el desglose
        if ($concepto === 'Sindicato' && !empty($detalle_sindicatos)) {
            foreach ($detalle_sindicatos as $sindicato) {
                $valor_sind_fmt = number_format($sindicato['valor'], 0, ',', '.');
                $filas_deducciones .= "<tr style='font-size: 9px;'>
                    <td style='padding: 2px 8px 2px 20px; color: #666;'>• {$sindicato['sindicato']}</td>
                    <td style='text-align: right; padding: 2px 8px; color: #666;'>{$valor_sind_fmt}</td>
                </tr>";
            }
        }

        // Si es Retención en la Fuente y tiene valor pendiente, mostrar el detalle
        if ($concepto === 'Retención en la Fuente' && $retencion_pendiente > 0) {
            $pendientesMostrados[19] = true;
            $ret_pend_fmt = number_format($retencion_pendiente, 0, ',', '.');
            $filas_deducciones .= "<tr style='font-size: 9px;'>
                <td style='padding: 2px 8px 2px 20px; color: #856404; font-weight: bold;'>• Saldo pendiente por retener:</td>
                <td style='text-align: right; padding: 2px 8px; color: #721c24; font-weight: bold;'>$ {$ret_pend_fmt}</td>
            </tr>";
        }

        // Si es Otros Descuentos y tiene detalle, mostrar el desglose
        if ($concepto === 'Otros Descuentos' && !empty($detalle_otros_dctos)) {
            foreach ($detalle_otros_dctos as $otro) {
                $valor_otro_fmt = number_format($otro['valor'], 0, ',', '.');
                $descripcion = $otro['tipo'];
                if (!empty($otro['concepto'])) {
                    $descripcion .= ': ' . $otro['concepto'];
                }
                $filas_deducciones .= "<tr style='font-size: 9px;'>
                    <td style='padding: 2px 8px 2px 20px; color: #666;'>• {$descripcion}</td>
                    <td style='text-align: right; padding: 2px 8px; color: #666;'>{$valor_otro_fmt}</td>
                </tr>";

                // Verificar si este descuento tiene saldo pendiente asociado en retroactivo
                $concUpper = mb_strtoupper($otro['concepto'] ?? '');
                $saldo_pend_otro = 0;
                if (isset($pendientesPorConcepto[3]) && strpos($concUpper, 'ALIMENTAC') !== false) {
                    $saldo_pend_otro = floatval($pendientesPorConcepto[3]['valor_pendiente']);
                    $pendientesMostrados[3] = true;
                } elseif (isset($pendientesPorConcepto[2]) && strpos($concUpper, 'TRANS') !== false) {
                    $saldo_pend_otro = floatval($pendientesPorConcepto[2]['valor_pendiente']);
                    $pendientesMostrados[2] = true;
                } elseif (isset($pendientesPorConcepto[5]) && strpos($concUpper, 'BSP') !== false) {
                    $saldo_pend_otro = floatval($pendientesPorConcepto[5]['valor_pendiente']);
                    $pendientesMostrados[5] = true;
                }

                if ($saldo_pend_otro > 0) {
                    $saldo_pend_fmt = number_format($saldo_pend_otro, 0, ',', '.');
                    $filas_deducciones .= "<tr style='font-size: 9px;'>
                        <td style='padding: 2px 8px 2px 25px; color: #856404; font-weight: bold;'>• Saldo pendiente por descontar:</td>
                        <td style='text-align: right; padding: 2px 8px; color: #721c24; font-weight: bold;'>$ {$saldo_pend_fmt}</td>
                    </tr>";
                }
            }
        }
    }

    // Si tiene otros reintegros pendientes pero valor descontado fue 0 (no apareció en deducciones)
    foreach ($itemsPendientes as $ip) {
        $idC = intval($ip['id_concepto']);
        if ($idC !== 19 && empty($pendientesMostrados[$idC]) && floatval($ip['valor_pendiente']) > 0) {
            $nom_concepto_pend = Detalles::getNombreConceptoPendiente($idC, $ip['nom_concepto_base']);
            $saldo_pend_fmt = number_format($ip['valor_pendiente'], 0, ',', '.');
            if (!isset($deducciones['Otros Descuentos'])) {
                $filas_deducciones .= "<tr>
                    <td style='padding: 4px 8px;'>Otros Descuentos</td>
                    <td style='text-align: right; padding: 4px 8px;'>0</td>
                </tr>";
                $deducciones['Otros Descuentos'] = 0;
            }
            $filas_deducciones .= "<tr style='font-size: 9px;'>
                <td style='padding: 2px 8px 2px 20px; color: #666;'>• {$nom_concepto_pend}</td>
                <td style='text-align: right; padding: 2px 8px; color: #666;'>0</td>
            </tr>
            <tr style='font-size: 9px;'>
                <td style='padding: 2px 8px 2px 25px; color: #856404; font-weight: bold;'>• Saldo pendiente por descontar:</td>
                <td style='text-align: right; padding: 2px 8px; color: #721c24; font-weight: bold;'>$ {$saldo_pend_fmt}</td>
            </tr>";
        }
    }

    // Si tiene retención en la fuente pendiente pero valor descontado fue 0 (no apareció en deducciones)
    if ($retencion_pendiente > 0 && empty($pendientesMostrados[19])) {
        $ret_pend_fmt = number_format($retencion_pendiente, 0, ',', '.');
        $filas_deducciones .= "<tr>
            <td style='padding: 4px 8px;'>Retención en la Fuente</td>
            <td style='text-align: right; padding: 4px 8px;'>$ 0</td>
        </tr>
        <tr style='font-size: 9px;'>
            <td style='padding: 2px 8px 2px 20px; color: #856404; font-weight: bold;'>• Saldo pendiente por retener:</td>
            <td style='text-align: right; padding: 2px 8px; color: #721c24; font-weight: bold;'>$ {$ret_pend_fmt}</td>
        </tr>";
    }

    // Tabla de descuentos pendientes para nóminas retroactivas
    $tabla_pendientes = '';
    if (!empty($itemsPendientes)) {
        $filas_tabla_pendientes = '';
        $tot_ini = 0;
        $tot_desc = 0;
        $tot_pend = 0;
        foreach ($itemsPendientes as $item) {
            $nom_concepto_pend = Detalles::getNombreConceptoPendiente($item['id_concepto'], $item['nom_concepto_base']);
            $v_ini = floatval($item['valor_total']);
            $v_pend = floatval($item['valor_pendiente']);
            $v_desc = floatval($item['valor_descontado'] ?? ($v_ini - $v_pend));
            $tot_ini += $v_ini;
            $tot_desc += $v_desc;
            $tot_pend += $v_pend;
            $v_ini_fmt = number_format($v_ini, 0, ',', '.');
            $v_desc_fmt = number_format($v_desc, 0, ',', '.');
            $v_pend_fmt = number_format($v_pend, 0, ',', '.');

            $filas_tabla_pendientes .= "<tr>
                <td style='padding: 4px 8px;'>{$nom_concepto_pend}</td>
                <td style='text-align: right; padding: 4px 8px;'>$ {$v_ini_fmt}</td>
                <td style='text-align: right; padding: 4px 8px;'>$ {$v_desc_fmt}</td>
                <td style='text-align: right; padding: 4px 8px; font-weight: bold; color: #721c24;'>$ {$v_pend_fmt}</td>
            </tr>";
        }
        $tot_ini_fmt = number_format($tot_ini, 0, ',', '.');
        $tot_desc_fmt = number_format($tot_desc, 0, ',', '.');
        $tot_pend_fmt = number_format($tot_pend, 0, ',', '.');

        $tabla_pendientes = <<<PENDIENTES_HTML
<!-- Descuentos y Retenciones Pendientes (Retroactivo) -->
<table style='width: 100%; border-collapse: collapse; margin-top: 15px;' border='1'>
    <thead>
        <tr style='background-color: #fff3cd;'>
            <th colspan='4' style='text-align: center; padding: 6px; font-size: 11px; color: #856404;'>
                DESCUENTOS Y RETENCIONES PENDIENTES POR APLICAR (RETROACTIVO)
            </th>
        </tr>
        <tr style='background-color: #fcf8e3; font-size: 9px; font-weight: bold;'>
            <td style='padding: 4px 8px; width: 40%;'>CONCEPTO</td>
            <td style='padding: 4px 8px; text-align: right; width: 20%;'>VALOR TOTAL</td>
            <td style='padding: 4px 8px; text-align: right; width: 20%;'>VALOR DESCONTADO</td>
            <td style='padding: 4px 8px; text-align: right; width: 20%; color: #856404;'>VALOR PENDIENTE</td>
        </tr>
    </thead>
    <tbody>
        {$filas_tabla_pendientes}
        <tr style='background-color: #fff3cd; font-weight: bold; font-size: 10px;'>
            <td style='padding: 5px 8px;'>TOTAL PENDIENTE POR DESCONTAR</td>
            <td style='text-align: right; padding: 5px 8px;'>$ {$tot_ini_fmt}</td>
            <td style='text-align: right; padding: 5px 8px;'>$ {$tot_desc_fmt}</td>
            <td style='text-align: right; padding: 5px 8px; color: #721c24;'>$ {$tot_pend_fmt}</td>
        </tr>
    </tbody>
</table>
<p style='font-size: 8px; color: #666; margin-top: 3px; margin-bottom: 5px; text-align: justify;'>
    * Los valores relacionados corresponden a conceptos de descuento o retención que no pudieron ser aplicados en su totalidad en esta liquidación de retroactivo debido a que superaban el total devengado, garantizando que el neto a pagar no resultara negativo.
</p>
PENDIENTES_HTML;
    }


    $html = <<<HTML
<!-- Información del Empleado -->
<table style='width: 100%; border-collapse: collapse; margin-bottom: 15px;' border='1'>
    <tr style='background-color: #f5f5f5;'>
        <th colspan='4' style='text-align: center; padding: 8px; font-size: 12px;'>INFORMACIÓN DEL EMPLEADO</th>
    </tr>
    <tr>
        <td style='padding: 6px; width: 15%; font-weight: bold;'>Nombre:</td>
        <td style='padding: 6px; width: 35%;'>{$d['nombre']}</td>
        <td style='padding: 6px; width: 15%; font-weight: bold;'>Documento:</td>
        <td style='padding: 6px; width: 35%;'>{$d['no_documento']}</td>
    </tr>
    <tr>
        <td style='padding: 6px; font-weight: bold;'>Cargo:</td>
        <td style='padding: 6px;'>{$d['descripcion_carg']}</td>
        <td style='padding: 6px; font-weight: bold;'>Sede:</td>
        <td style='padding: 6px;'>{$d['sede']}</td>
    </tr>
    <tr>
        <td style='padding: 6px; font-weight: bold;'>Salario Base:</td>
        <td style='padding: 6px;'>$ {$sal_base_fmt}</td>
        <td style='padding: 6px; font-weight: bold;'>Días Laborados:</td>
        <td style='padding: 6px;'>{$d['dias_lab']}</td>
    </tr>
</table>

<!-- Devengados y Deducciones -->
<table style='width: 100%; border-collapse: collapse;' border='1'>
    <tr>
        <!-- Columna Devengados -->
        <td style='width: 50%; vertical-align: top; padding: 0;'>
            <table style='width: 100%; border-collapse: collapse;'>
                <tr style='background-color: #d4edda;'>
                    <th colspan='2' style='text-align: center; padding: 8px; font-size: 11px;'>DEVENGADOS</th>
                </tr>
                {$filas_devengados}
                <tr style='background-color: #d4edda; font-weight: bold;'>
                    <td style='padding: 6px 8px;'>TOTAL DEVENGADO</td>
                    <td style='text-align: right; padding: 6px 8px;'>$ {$total_devengado_fmt}</td>
                </tr>
            </table>
        </td>
        <!-- Columna Deducciones -->
        <td style='width: 50%; vertical-align: top; padding: 0;'>
            <table style='width: 100%; border-collapse: collapse;'>
                <tr style='background-color: #f8d7da;'>
                    <th colspan='2' style='text-align: center; padding: 8px; font-size: 11px;'>DEDUCCIONES</th>
                </tr>
                {$filas_deducciones}
                <tr style='background-color: #f8d7da; font-weight: bold;'>
                    <td style='padding: 6px 8px;'>TOTAL DEDUCCIONES</td>
                    <td style='text-align: right; padding: 6px 8px;'>$ {$total_deducciones_fmt}</td>
                </tr>
            </table>
        </td>
    </tr>
</table>

<!-- Neto a Pagar -->
<table style='width: 100%; border-collapse: collapse; margin-top: 15px;' border='1'>
    <tr style='background-color: #cce5ff;'>
        <td style='padding: 10px; font-size: 14px; font-weight: bold; text-align: center; width: 70%;'>NETO A PAGAR</td>
        <td style='padding: 10px; font-size: 14px; font-weight: bold; text-align: right; width: 30%;'>$ {$neto_pagar_fmt}</td>
    </tr>
</table>

{$tabla_pendientes}

<p style='font-size: 10px; text-align: center; margin-top: 15px;'>
    Este desprendible es un documento informativo. Para cualquier aclaración, dirigirse al área de Recursos Humanos.
</p>
HTML;

    return $html;
}

// Generar el contenido HTML
$html = '';
$totalEmpleados = is_countable($empleados) ? count($empleados) : 0;
$contador = 0;

foreach ($empleados as $empleado) {
    $contador++;
    $html .= generarDesprendible($empleado);

    // Añadir salto de página entre empleados (excepto el último)
    if ($contador < $totalEmpleados) {
        $html .= "<div style='page-break-after: always;'></div>";
    }
}

$firmas = (new CReportes())->getFormFirmas(['nom_tercero' => $nomina['elabora'], 'cargo' => $nomina['cargo']], 51, $nomina['fecha'], 'DSNN');

$Imprimir = new Imprimir($documento, "letter");
$Imprimir->addEncabezado($documento, $otro, 'DSNN', 51);
$Imprimir->addContenido($html);
$Imprimir->addFirmas($firmas);
$pdf = isset($_POST['pdf']) ? filter_var($_POST['pdf'], FILTER_VALIDATE_BOOLEAN) : false;
$resul = $Imprimir->render($pdf);

if ($pdf) {
    $Imprimir->getPDF($resul);
    exit();
}
