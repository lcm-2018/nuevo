<?php

use Config\Clases\Conexion;
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

// ── Parámetros ───────────────────────────────────────────────────────────────
$id_nomina = isset($_POST['id']) ? intval($_POST['id']) : exit('ID de nómina no proporcionado');

$nomina = Nomina::getRegistro($id_nomina);
if (empty($nomina) || $nomina['id_nomina'] == 0) {
    exit('Nómina no encontrada');
}

$usuario  = new Usuario();
$empresa  = $usuario->getEmpresa();
$detalles = new Detalles();
$pdo      = Conexion::getConexion();

// ── Tipo de nómina → título dinámico ────────────────────────────────────────
$tipo_nomina_upper  = mb_strtoupper($nomina['tipo_nomina'] ?? 'NÓMINA');
$tipo_nomina_codigo = strtoupper(trim($nomina['tipo'] ?? ''));   // Ej: 'N', 'VAC', 'PS', 'CES'
$titulo_doc         = "LIQUIDACION {$tipo_nomina_upper}";

// ── Empleados liquidados en la nómina ───────────────────────────────────────
$empleados = $detalles->getRegistrosDT(1, -1, ['id_nomina' => $id_nomina], 1, 'ASC');
if (empty($empleados)) {
    exit('No se encontraron empleados liquidados en esta nómina.');
}

// ── FACTORES SALARIALES reales → nom_valores_liquidacion ────────────────────
// Se obtienen todos en un solo query y se indexan por id_empleado.
$sql_vl = "SELECT
                `id_empleado`,
                `salario`,
                `aux_trans`,
                `aux_alim`,
                `bsp_ant`,
                `pri_ser_ant`,
                `pri_vac_ant`,
                `pri_nav_ant`,
                `grep`,
                `tiene_grep`,
                `prom_horas`,
                `smmlv`,
                `base_bsp`,
                `base_alim`,
                `uvt`
            FROM `nom_valores_liquidacion`
            WHERE `id_nomina` = :id_nomina AND `estado` = 1";
$stmt_vl = $pdo->prepare($sql_vl);
$stmt_vl->bindValue(':id_nomina', $id_nomina, PDO::PARAM_INT);
$stmt_vl->execute();
$factores_por_empleado = [];
foreach ($stmt_vl->fetchAll(PDO::FETCH_ASSOC) as $row) {
    $factores_por_empleado[(int)$row['id_empleado']] = $row;
}
$stmt_vl->closeCursor();
unset($stmt_vl);

// ── Fechas de vacaciones (fec_inicio / fec_fin desde nom_vacaciones) ─────────
$sql_vac = "SELECT
                `e`.`no_documento`,
                `vac`.`fec_inicio`,
                `vac`.`fec_fin`
            FROM
                `nom_liq_vac`  AS `lv`
                INNER JOIN `nom_vacaciones` AS `vac` ON (`lv`.`id_vac` = `vac`.`id_vac`)
                INNER JOIN `nom_empleado`   AS `e`   ON (`vac`.`id_empleado` = `e`.`id_empleado`)
            WHERE `lv`.`id_nomina` = :id_nomina AND `lv`.`estado` = 1";
$stmt_vac = $pdo->prepare($sql_vac);
$stmt_vac->bindValue(':id_nomina', $id_nomina, PDO::PARAM_INT);
$stmt_vac->execute();
$vac_fechas = [];
foreach ($stmt_vac->fetchAll(PDO::FETCH_ASSOC) as $row) {
    $vac_fechas[$row['no_documento']] = $row;
}
$stmt_vac->closeCursor();
unset($stmt_vac);

// ── Datos de PRESTACIONES SOCIALES → nom_liq_prestaciones_sociales ───────────
// Solo se consulta si la nómina es de tipo PS; el array puede quedar vacío si no aplica.
$ps_por_empleado = [];
$sql_ps = "SELECT
                `id_empleado`,
                `val_vacacion`,
                `val_cesantia`,
                `val_interes_cesantia`,
                `val_prima`,
                `val_prima_vac`,
                `val_prima_nav`,
                `val_bonifica_recrea`
            FROM `nom_liq_prestaciones_sociales`
            WHERE `id_nomina` = :id_nomina AND `estado` = 1";
$stmt_ps = $pdo->prepare($sql_ps);
$stmt_ps->bindValue(':id_nomina', $id_nomina, PDO::PARAM_INT);
$stmt_ps->execute();
foreach ($stmt_ps->fetchAll(PDO::FETCH_ASSOC) as $row) {
    $ps_por_empleado[(int)$row['id_empleado']] = $row;
}
$stmt_ps->closeCursor();
unset($stmt_ps);

// Días de PS desde sus tablas fuente (BSP, vac, cesantías, primas)
// Los trae el CTE de getRegistrosDT: val_bsp, dias_ces, dias_ps, dias_pn
// No necesitamos query adicional para días.

// ── Helpers ──────────────────────────────────────────────────────────────────
function fmt($valor): string
{
    if ((float)$valor == 0) return '';
    return '$ ' . number_format((float)$valor, 2, ',', '.');
}

function fmtDias($dias): string
{
    if ((float)$dias == 0) return '';
    return number_format((float)$dias, 2, '.', '');
}

function formatFechaEsp(string $fecha): string
{
    if (empty($fecha) || $fecha === '0000-00-00' || $fecha === '2999-12-31') return '';
    $ts = strtotime($fecha);
    if ($ts === false) return '';
    $meses = [
        1 => 'enero',    2 => 'febrero', 3 => 'marzo',    4 => 'abril',
        5 => 'mayo',     6 => 'junio',   7 => 'julio',    8 => 'agosto',
        9 => 'septiembre', 10 => 'octubre', 11 => 'noviembre', 12 => 'diciembre',
    ];
    return (int)date('d', $ts) . ' de ' . $meses[(int)date('m', $ts)] . ' de ' . date('Y', $ts);
}

// ── Fecha de expedición ───────────────────────────────────────────────────────
$dia_hoy  = (int)date('d');
$mes_hoy  = (int)date('m');
$anio_hoy = date('Y');
$meses_esp = [
    1 => 'enero',    2 => 'febrero', 3 => 'marzo',    4 => 'abril',
    5 => 'mayo',     6 => 'junio',   7 => 'julio',    8 => 'agosto',
    9 => 'septiembre', 10 => 'octubre', 11 => 'noviembre', 12 => 'diciembre',
];
$municipio_empresa = $empresa['municipio'] ?? 'Yopal';
$fecha_expedicion  = "Dada en {$municipio_empresa}, a los {$dia_hoy} días del mes de {$meses_esp[$mes_hoy]} de {$anio_hoy}.";

// ── Generador del HTML de un empleado ────────────────────────────────────────
function generarLiquidacion(
    array  $d,
    array  $vac_fechas,
    array  $factores_por_empleado,
    array  $ps_por_empleado,
    string $tipo_nomina_upper,
    string $tipo_nomina_codigo
): string {

    $id_empleado = (int)($d['id_empleado'] ?? 0);
    $doc         = $d['no_documento']     ?? '';
    $nombre      = $d['nombre']           ?? '';
    $cargo       = $d['descripcion_carg'] ?? '';
    $sede        = $d['sede']             ?? '';
    $tipo_doc    = 'Cedula de Ciudadanía';

    $tipo_upper = mb_strtoupper($tipo_nomina_upper);

    // ── Fechas programadas de vacaciones ─────────────────────────────────
    $fila_programadas = '';
    if (mb_strpos($tipo_upper, 'VACACION') !== false && isset($vac_fechas[$doc])) {
        $ini = formatFechaEsp($vac_fechas[$doc]['fec_inicio'] ?? '');
        $fin = formatFechaEsp($vac_fechas[$doc]['fec_fin']    ?? '');
        if ($ini !== '' && $fin !== '') {
            $fila_programadas = "
        <tr>
            <td style='width:140px; padding:2px 0; vertical-align:top;'><strong>Programadas de:</strong></td>
            <td style='padding:2px 0;'>{$ini} a {$fin}</td>
        </tr>";
        }
    }

    // ── Valores liquidados (para sección LIQUIDACION) ─────────────────────
    $val_prima_vac  = (float)($d['val_prima_vac']  ?? 0);
    $val_bon_recrea = (float)($d['val_bon_recrea']  ?? 0);
    $val_vacacion   = (float)($d['valor_vacacion']  ?? 0);
    $val_ps         = (float)($d['valor_ps']        ?? 0);
    $dias_ps        = (float)($d['dias_ps']         ?? 0);
    $val_ces        = (float)($d['val_cesantias']   ?? 0);
    $val_ices       = (float)($d['val_icesantias']  ?? 0);
    $dias_ces       = (float)($d['dias_ces']        ?? 0);
    $val_laborado   = (float)($d['valor_laborado']  ?? 0);
    $dias_lab       = (float)($d['dias_lab']        ?? 0);
    $aux_tran_liq   = (float)($d['aux_tran']        ?? 0);
    $aux_alim_liq   = (float)($d['aux_alim']        ?? 0);

    $dias_inactivos = (float)($d['dias_inactivo']   ?? 0);
    $dias_habiles   = (float)($d['dias_vacaciones'] ?? 0);
    $total_dias_vac = $dias_inactivos + $dias_habiles;

    // ── Conceptos de LIQUIDACION según tipo de nómina ────────────────────
    $conceptos_liq = [];

    if ($tipo_nomina_codigo === 'PS') {
        // --- Nómina de PRESTACIONES SOCIALES ---
        // Los valores consolidados vienen de nom_liq_prestaciones_sociales
        $ps = $ps_por_empleado[$id_empleado] ?? [];

        $ps_bsp       = (float)($d['val_bsp']                      ?? 0);
        $ps_vac       = (float)($ps['val_vacacion']                 ?? 0);
        $ps_prim_vac  = (float)($ps['val_prima_vac']                ?? 0);
        $ps_bon_rec   = (float)($ps['val_bonifica_recrea']          ?? 0);
        $ps_ces       = (float)($ps['val_cesantia']                 ?? 0);
        $ps_ices      = (float)($ps['val_interes_cesantia']         ?? 0);
        $ps_prima     = (float)($ps['val_prima']                    ?? 0);
        $ps_prima_nav = (float)($ps['val_prima_nav']                ?? 0);

        // Días desde el CTE de getRegistrosDT
        $ps_dias_bsp = (float)($d['dias_bsp']  ?? 0);
        $ps_dias_ces = (float)($d['dias_ces']   ?? 0);
        $ps_dias_ps  = (float)($d['dias_ps']    ?? 0);
        $ps_dias_pn  = (float)($d['dias_pn']    ?? 0);

        if ($ps_bsp      > 0) $conceptos_liq[] = ['BONIFICACIÓN SERVICIOS PRESTADOS', $ps_dias_bsp, $ps_bsp,      false];
        if ($ps_vac      > 0) $conceptos_liq[] = ['VACACIONES',                       $total_dias_vac, $ps_vac,  false];
        if ($ps_prim_vac > 0) $conceptos_liq[] = ['PRIMA DE VACACIONES',              $dias_habiles, $ps_prim_vac, false];
        if ($ps_bon_rec  > 0) $conceptos_liq[] = ['BONIFICACIÓN RECREACIÓN',          3.0,           $ps_bon_rec,  false];
        if ($ps_ces      > 0) $conceptos_liq[] = ['CESANTÍAS',                        $ps_dias_ces,  $ps_ces,      false];
        if ($ps_ices     > 0) $conceptos_liq[] = ['INTERESES DE CESANTÍAS',           '',            $ps_ices,     false];
        if ($ps_prima    > 0) $conceptos_liq[] = ['PRIMA DE SERVICIOS',               $ps_dias_ps,   $ps_prima,    false];
        if ($ps_prima_nav > 0) $conceptos_liq[] = ['PRIMA DE NAVIDAD',                $ps_dias_pn,   $ps_prima_nav, true];

    } elseif (mb_strpos($tipo_upper, 'VACACION') !== false) {
        if ($val_prima_vac > 0)
            $conceptos_liq[] = ['PRIMA DE VACACIONES',    $dias_habiles,   $val_prima_vac,  false];
        if ($val_bon_recrea > 0)
            $conceptos_liq[] = ['BONIFICACION RECREACIÓN', 3.0,            $val_bon_recrea, false];
        if ($val_vacacion > 0)
            $conceptos_liq[] = ['VACACIONES',             $total_dias_vac, $val_vacacion,   true];

    } elseif (mb_strpos($tipo_upper, 'PRIMA') !== false && mb_strpos($tipo_upper, 'SERVICIO') !== false) {
        if ($val_ps > 0)
            $conceptos_liq[] = ['PRIMA DE SERVICIOS',     $dias_ps,        $val_ps,         true];

    } elseif (mb_strpos($tipo_upper, 'CESANT') !== false) {
        if ($val_ces > 0)
            $conceptos_liq[] = ['CESANTÍAS',              $dias_ces,       $val_ces,        false];
        if ($val_ices > 0)
            $conceptos_liq[] = ['INTERESES DE CESANTÍAS', '',              $val_ices,       true];

    } else {
        // Nómina regular mensual
        if ($val_laborado > 0)
            $conceptos_liq[] = ['SALARIO LABORADO',         $dias_lab,     $val_laborado,   false];
        if ($aux_tran_liq > 0)
            $conceptos_liq[] = ['SUBSIDIO DE TRANSPORTE',   '',            $aux_tran_liq,   false];
        if ($aux_alim_liq > 0)
            $conceptos_liq[] = ['SUBSIDIO DE ALIMENTACIÓN', '',            $aux_alim_liq,   true];
    }

    // Total liquidación
    $total_liq = 0.0;
    foreach ($conceptos_liq as [, , $v]) $total_liq += $v;

    // Filas LIQUIDACION
    $filas_liq = '';
    foreach ($conceptos_liq as [$nom, $dias, $val, $subray]) {
        $diasCell  = $dias !== '' ? fmtDias($dias) : '';
        $valorCell = $subray ? '<u>' . fmt($val) . '</u>' : fmt($val);
        $filas_liq .= "
                    <tr>
                        <td style='padding:2px 0; font-weight:bold;'>{$nom}</td>
                        <td style='text-align:center; padding:2px 6px; font-weight:bold; width:55px;'>{$diasCell}</td>
                        <td style='text-align:right; padding:2px 0; font-weight:bold; width:130px;'>{$valorCell}</td>
                    </tr>";
    }
    // Total con borde doble
    $filas_liq .= "
                    <tr>
                        <td>&nbsp;</td><td>&nbsp;</td>
                        <td style='text-align:right; padding:4px 0;
                                   border-top:1px solid #000;
                                   border-bottom:3px double #000;'>
                            <strong>" . fmt($total_liq) . "</strong>
                        </td>
                    </tr>";

    // ── FACTORES SALARIALES desde nom_valores_liquidacion ─────────────────
    $vl = $factores_por_empleado[$id_empleado] ?? [];

    $fs_salario      = (float)($vl['salario']      ?? 0);
    $fs_aux_trans    = (float)($vl['aux_trans']    ?? 0);
    $fs_aux_alim     = (float)($vl['aux_alim']     ?? 0);
    $fs_bsp_ant      = (float)($vl['bsp_ant']      ?? 0);   // última bonif. servicios
    $fs_pri_ser_ant  = (float)($vl['pri_ser_ant']  ?? 0);   // última prima servicios
    $fs_pri_vac_ant  = (float)($vl['pri_vac_ant']  ?? 0);   // última prima vacaciones
    $fs_pri_nav_ant  = (float)($vl['pri_nav_ant']  ?? 0);   // última prima navidad
    $fs_grep         = (float)($vl['grep']         ?? 0);
    $fs_tiene_grep   = (int)  ($vl['tiene_grep']   ?? 0);
    $fs_prom_horas   = (float)($vl['prom_horas']   ?? 0);

    // Doceavas partes (÷ 12)
    $doceava_bsp     = $fs_bsp_ant     > 0 ? round($fs_bsp_ant     / 12, 0) : 0;
    $doceava_ps      = $fs_pri_ser_ant > 0 ? round($fs_pri_ser_ant / 12, 0) : 0;

    $factores = [];
    if ($fs_salario    > 0) $factores[] = ['SUELDO BÁSICO',                        $fs_salario];
    if ($fs_aux_trans  > 0) $factores[] = ['SUBSIDIO DE TRANSPORTE',               $fs_aux_trans];
    if ($fs_aux_alim   > 0) $factores[] = ['SUBSIDIO DE ALIMENTACIÓN',             $fs_aux_alim];
    if ($doceava_bsp   > 0) $factores[] = ["DOCEAVA PARTE ULT. BONIF\nSERVICIOS", $doceava_bsp];
    if ($doceava_ps    > 0) $factores[] = ["DOCEAVA PARTE ULT. PRIMA\nSERVICIOS", $doceava_ps];
    if ($fs_tiene_grep && $fs_grep > 0) $factores[] = ['GASTOS DE REPRESENTACIÓN', $fs_grep];
    if ($fs_prom_horas > 0) $factores[] = ['PROMEDIO HORAS EXTRAS',               $fs_prom_horas];

    $total_fs = array_sum(array_column($factores, 1));

    $filas_fact = '';
    foreach ($factores as [$nom_f, $val_f]) {
        $nom_cell = nl2br(htmlspecialchars($nom_f));
        $filas_fact .= "
                    <tr>
                        <td style='padding:2px 0;'>{$nom_cell}</td>
                        <td style='text-align:right; padding:2px 0; width:130px;'>" . fmt($val_f) . "</td>
                    </tr>";
    }
    // Total factores
    $filas_fact .= "
                    <tr>
                        <td>&nbsp;</td>
                        <td style='text-align:right; padding:4px 0;
                                   border-top:1px solid #000;
                                   border-bottom:3px double #000;'>
                            <strong>" . fmt($total_fs) . "</strong>
                        </td>
                    </tr>";

    // ── HTML del bloque ───────────────────────────────────────────────────
    $html = <<<HTML

    <!-- ▬▬ DATOS DEL EMPLEADO ▬▬ -->
    <table style='width:100%; border-collapse:collapse; font-size:11px; margin-bottom:14px;'>
        <tr>
            <td style='width:140px; padding:2px 0; vertical-align:top;'><strong>Tipo Doc.:</strong></td>
            <td style='padding:2px 0;'>{$tipo_doc}</td>
        </tr>
        <tr>
            <td style='padding:2px 0; vertical-align:top;'><strong>Numero:</strong></td>
            <td style='padding:2px 0;'>{$doc}</td>
        </tr>
        <tr>
            <td style='padding:2px 0; vertical-align:top;'><strong>Nombre:</strong></td>
            <td style='padding:2px 0;'>{$nombre}</td>
        </tr>
        <tr>
            <td style='padding:2px 0; vertical-align:top;'><strong>Cargo:</strong></td>
            <td style='padding:2px 0;'>{$cargo}</td>
        </tr>
        <tr>
            <td style='padding:2px 0; vertical-align:top;'><strong>IPS asignada:</strong></td>
            <td style='padding:2px 0;'>{$sede}</td>
        </tr>
        {$fila_programadas}
    </table>

    <!-- ▬▬ LIQUIDACION + FACTORES SALARIALES ▬▬ -->
    <table style='width:100%; border-collapse:collapse; font-size:11px; margin-top:10px;'>

        <!-- Sección LIQUIDACION -->
        <tr style='vertical-align:top;'>
            <td style='width:150px; padding-top:3px;'>
                <strong>LIQUIDACION</strong>
            </td>
            <td>
                <table style='width:100%; border-collapse:collapse;'>
                    <tr>
                        <td style='padding-bottom:5px; font-size:10px; color:#444;'>La erogación se hará con cargo a;</td>
                        <td style='text-align:center; padding-bottom:5px; width:55px;'><strong>Dias</strong></td>
                        <td style='text-align:right;  padding-bottom:5px; width:130px;'><strong>Valor</strong></td>
                    </tr>
                    {$filas_liq}
                </table>
            </td>
        </tr>

        <!-- Separador -->
        <tr><td colspan='2' style='padding:6px 0;'></td></tr>

        <!-- Sección FACTORES SALARIALES -->
        <tr style='vertical-align:top;'>
            <td style='padding-top:3px;'>
                <strong>FACTORES SALARIALES</strong>
            </td>
            <td>
                <table style='width:100%; border-collapse:collapse;'>
                    {$filas_fact}
                </table>
            </td>
        </tr>

    </table>

HTML;

    return $html;
}

// ── Firmas (se generan una sola vez, se reutilizan en cada empleado) ─────────
$firmas = (new CReportes())->getFormFirmas(
    ['nom_tercero' => $nomina['elabora'], 'cargo' => $nomina['cargo']],
    51,
    $nomina['vigencia'] . '-' . $nomina['mes'] . '-01',
    'CNOM'
);

// ── HTML completo ────────────────────────────────────────────────────────────
$html       = '';
$total_emps = count($empleados);
$contador   = 0;

foreach ($empleados as $empleado) {
    $contador++;

    // Contenido del empleado
    $html .= generarLiquidacion($empleado, $vac_fechas, $factores_por_empleado, $ps_por_empleado, $tipo_nomina_upper, $tipo_nomina_codigo);

    // Fecha de expedición
    $html .= "<p style='font-size:11px; margin-top:18px;'>{$fecha_expedicion}</p>";

    // Firmas individuales por empleado
    $html .= "<div>{$firmas}</div>";

    // Salto de página entre empleados (excepto el último)
    if ($contador < $total_emps) {
        $html .= "<div style='page-break-after:always;'></div>";
    }
}

// ── Renderizar ────────────────────────────────────────────────────────────────
$Imprimir = new Imprimir($titulo_doc, "letter");
$Imprimir->addEncabezado($titulo_doc);
$Imprimir->addContenido($html);
// Sin addFirmas() global: las firmas ya están dentro del contenido de cada empleado

$pdf   = isset($_POST['pdf']) ? filter_var($_POST['pdf'], FILTER_VALIDATE_BOOLEAN) : false;
$resul = $Imprimir->render($pdf);

if ($pdf) {
    $Imprimir->getPDF($resul);
    exit();
}
