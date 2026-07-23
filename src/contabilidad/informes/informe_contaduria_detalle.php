<?php
session_start();
// set_time_limit(0);
// incrementar el tiempo de ejecucion del script
ini_set('max_execution_time', 5600);

include '../../../config/autoloader.php';
include '../../financiero/consultas.php';
// Consexion a cronhis asistencial
$vigencia = $_SESSION['vigencia'];
// estraigo las variables que llegan por post en json
$fecha_inicial = $_POST['fecha_inicial'];
$fecha_corte = $_POST['fecha_final'];
$inicio = $_SESSION['vigencia'] . '-01-01';
$parametro = 2;
// contar los caracteres de $cuenta_ini
$cmd = \Config\Clases\Conexion::getConexion();

// Obtener sedes activas usando la función centralizada
$datos_sedes = obtenerSedesActivas($cmd);
$sedes = $datos_sedes['sedes'];
$sede_principal = $datos_sedes['sede_principal'];

// Array para consolidar datos de todas las sedes
$datos = [];

// Iterar sobre todas las sedes activas
foreach ($sedes as $sede) {
    // Usar conexión actual para la sede principal, conectar para las demás
    if ($sede['es_principal'] == 1) {
        $cmd_sede = $cmd;
    } else {
        $cmd_sede = conectarSede($sede['bd_sede']);
        if ($cmd_sede === null) {
            error_log("No se pudo conectar a la sede {$sede['nom_sede']} para el informe de contaduría detalle");
            continue; // Saltar esta sede si falla la conexión
        }
    }

    try {
        $sql = "SELECT
                    `taux`.`cuenta`
                    , `taux`.`tipo`
                    , SUM(`taux`.`debitoi`) AS `debitoi`
                    , SUM(`taux`.`creditoi`) AS `creditoi`
                    , SUM(`taux`.`debito`) AS `debito`
                    , SUM(`taux`.`credito`) AS `credito`
                FROM
                    (SELECT 
                        SUBSTRING(`ctb_pgcp`.`cuenta`, 1, 6) AS `cuenta`
                        , `ctb_pgcp`.`tipo_dato` AS `tipo`
                        , SUM(`t1`.`debitoi`) AS `debitoi`
                        , SUM(`t1`.`creditoi`) AS `creditoi`
                        , SUM(`t1`.`debito`) AS `debito`
                        , SUM(`t1`.`credito`) AS `credito`
                    FROM
                        (SELECT
                            `ctb_libaux`.`id_cuenta`
                            , SUM(`ctb_libaux`.`debito`) AS `debitoi`
                            , SUM(`ctb_libaux`.`credito`) AS `creditoi`
                            , 0 AS `debito`
                            , 0 AS `credito`
                        FROM
                            `ctb_libaux`
                            INNER JOIN `ctb_doc`
                                ON `ctb_libaux`.`id_ctb_doc` = `ctb_doc`.`id_ctb_doc`
                            INNER JOIN `ctb_pgcp`
                                ON `ctb_libaux`.`id_cuenta` = `ctb_pgcp`.`id_pgcp`
                        WHERE `ctb_doc`.`estado` = 2
                            AND ((SUBSTRING(`ctb_pgcp`.`cuenta`, 1, 1) IN ('1', '2', '3', '8', '9') AND DATE_FORMAT(`ctb_doc`.`fecha`, '%Y-%m-%d') < '$fecha_inicial')
                                OR
                            (SUBSTRING(`ctb_pgcp`.`cuenta`, 1, 1) IN ('4', '5', '6', '7') AND DATE_FORMAT(`ctb_doc`.`fecha`, '%Y-%m-%d') < '$fecha_inicial' AND DATE_FORMAT(`ctb_doc`.`fecha`, '%Y-%m-%d') >= '$inicio'))
                        GROUP BY `ctb_libaux`.`id_cuenta`
                        UNION ALL 
                        SELECT
                            `ctb_libaux`.`id_cuenta`
                            , 0 AS `debitoi`
                            , 0 AS `creditoi`
                            , SUM(`ctb_libaux`.`debito`) AS `debito`
                            , SUM(`ctb_libaux`.`credito`) AS `credito`
                        FROM
                            `ctb_libaux`
                            INNER JOIN `ctb_doc` 
                                ON (`ctb_libaux`.`id_ctb_doc` = `ctb_doc`.`id_ctb_doc`)
                            INNER JOIN `ctb_pgcp` 
                                ON (`ctb_libaux`.`id_cuenta` = `ctb_pgcp`.`id_pgcp`)
                        WHERE (DATE_FORMAT(`ctb_doc`.`fecha`, '%Y-%m-%d') BETWEEN '$fecha_inicial' AND '$fecha_corte' AND `ctb_doc`.`estado` = 2)
                        GROUP BY `ctb_libaux`.`id_cuenta`) AS `t1`
                        INNER JOIN `ctb_pgcp`
                            ON `t1`.`id_cuenta` = `ctb_pgcp`.`id_pgcp`
                    GROUP BY `t1`.`id_cuenta`) AS `taux`
                GROUP BY `taux`.`cuenta`
                ORDER BY `taux`.`cuenta`";
        $res = $cmd_sede->query($sql);
        $datos_sede = $res->fetchAll(PDO::FETCH_ASSOC);
        $res->closeCursor();

        // Agregar nombre de sede a cada registro para referencia
        foreach ($datos_sede as &$fila) {
            $fila['nom_sede'] = $sede['nom_sede'];
        }
        unset($fila); // Romper la referencia

        // Consolidar con los resultados de otras sedes
        $datos = array_merge($datos, $datos_sede);
    } catch (PDOException $e) {
        error_log("Error consultando contaduría detalle en sede {$sede['nom_sede']}: " . $e->getMessage());
    }

    // Cerrar la conexión si no es la principal
    if ($sede['es_principal'] != 1) {
        $cmd_sede = null;
    }
}

// Agrupar por cuenta sumando los valores de todas las sedes
$datos_agrupados = [];
foreach ($datos as $fila) {
    $cuenta = $fila['cuenta'];
    if (!isset($datos_agrupados[$cuenta])) {
        $datos_agrupados[$cuenta] = [
            'cuenta'   => $fila['cuenta'],
            'tipo'     => $fila['tipo'],
            'debitoi'  => 0,
            'creditoi' => 0,
            'debito'   => 0,
            'credito'  => 0,
        ];
    }
    $datos_agrupados[$cuenta]['debitoi']  += $fila['debitoi'];
    $datos_agrupados[$cuenta]['creditoi'] += $fila['creditoi'];
    $datos_agrupados[$cuenta]['debito']   += $fila['debito'];
    $datos_agrupados[$cuenta]['credito']  += $fila['credito'];
}
ksort($datos_agrupados);
$datos = array_values($datos_agrupados);

$acum = [];

$nom_informe = "CONTADURÍA CGN";
include_once '../../financiero/encabezado_empresa.php';

?>
<table style="width:100% !important; border-collapse: collapse;" border="1">
    <thead>
        <tr>
            <td>FECHA INICIO</td>
            <td style='text-align: left;'><?php echo $fecha_inicial; ?></td>
            <td>FECHA FIN</td>
            <td style='text-align: left;'><?php echo $fecha_corte; ?></td>
        </tr>
    </thead>
</table>
<table style="width:100% !important; border-collapse: collapse;" border="1">
    <thead>
        <tr class="centrar">
            <td>Cuenta</td>
            <td>Inicial</td>
            <td>Debito</td>
            <td>Credito</td>
            <td>Saldo Final</td>
        </tr>
    </thead>
    <tbody>
        <?php
        if (!empty($datos)) {
            $separarCadenaResultados = [];
            foreach ($datos as $tp) {
                $cuenta = $tp['cuenta'];
                $nat = substr($cuenta, 0, 2);
                $naturaleza = ($nat[0] == '1' || $nat[0] == '5' || $nat[0] == '6' || $nat[0] == '7' || $nat == '81' || $nat == '83' || $nat == '99') ? "D" : "C";

                if (!isset($separarCadenaResultados[$cuenta])) {
                    $separarCadenaResultados[$cuenta] = SepararCadena($cuenta, $parametro);
                }

                $debitoi = $tp['debitoi'];
                $creditoi = $tp['creditoi'];
                $debito = $tp['debito'];
                $credito = $tp['credito'];

                $saldo_ini = ($naturaleza == "D") ? $debitoi - $creditoi : $creditoi - $debitoi;
                $saldo = $saldo_ini + (($naturaleza == "D") ? $debito - $credito : $credito - $debito);

                echo "<tr>
                        <td class='text'>" . $separarCadenaResultados[$cuenta] . "</td>
                        <td class='text-end'>" . Decimales($saldo_ini) . "</td>
                        <td class='text-end'>" . Decimales($debito) . "</td>
                        <td class='text-end'>" . Decimales($credito) . "</td>
                        <td class='text-end'>" . Decimales($saldo) . "</td>
                    </tr>";
            }
        } else {
            echo "<tr><td colspan='5'>No hay datos para mostrar</td></tr>";
        }
        ?>
    </tbody>
</table>
<?php
function SepararCadena($cadena, $tam)
{
    if (strlen($cadena) <= 1) {
        return $cadena;
    }
    $resultado = substr($cadena, 0, 1) . '.' . substr($cadena, 1, 1) . '.' . substr($cadena, 2, 2) . '.' . substr($cadena, 4, 2);
    $resto = substr($cadena, 6);
    for ($i = 0; $i < strlen($resto); $i += $tam) {
        $resultado .= '.' . substr($resto, $i, $tam);
    }
    return $resultado;
}

function Decimales($numero)
{
    return number_format($numero, 2, '.', '');
}
