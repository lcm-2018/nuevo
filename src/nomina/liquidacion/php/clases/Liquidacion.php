<?php

namespace Src\Nomina\Liquidacion\Php\Clases;

use Config\Clases\Conexion;
use Config\Clases\Logs;
use Config\Clases\Sesion;
use Exception;
use PDO;
use PDOException;
use Src\Common\Php\Clases\Valores;
use Src\Nomina\Empleados\Php\Clases\Bsp;
use Src\Nomina\Empleados\Php\Clases\Cesantias;
use Src\Nomina\Empleados\Php\Clases\Compesatorios;
use Src\Nomina\Empleados\Php\Clases\Embargos;
use Src\Nomina\Empleados\Php\Clases\Empleados;
use Src\Nomina\Empleados\Php\Clases\Incapacidades;
use Src\Nomina\Empleados\Php\Clases\Indemniza_Vacacion;
use Src\Nomina\Empleados\Php\Clases\Ivivienda;
use Src\Nomina\Empleados\Php\Clases\Libranzas;
use Src\Nomina\Empleados\Php\Clases\Licencias_Luto;
use Src\Nomina\Empleados\Php\Clases\Licencias_MoP;
use Src\Nomina\Empleados\Php\Clases\Licencias_Norem;
use Src\Nomina\Empleados\Php\Clases\Otros_Devengados;
use Src\Nomina\Empleados\Php\Clases\Otros_Descuentos;
use Src\Nomina\Empleados\Php\Clases\Prestaciones_Sociales;
use Src\Nomina\Empleados\Php\Clases\Primas;
use Src\Nomina\Empleados\Php\Clases\Seguridad_Social;
use Src\Nomina\Empleados\Php\Clases\Sindicatos;
use Src\Nomina\Empleados\Php\Clases\Vacaciones;
use Src\Nomina\Empleados\Php\Clases\Valores_Liquidacion;
use Src\Nomina\Horas_extra\Php\Clases\Horas_Extra;
use Src\Nomina\Empleados\Php\Clases\Viaticos;
use Src\Nomina\Empleados\Php\Clases\ViaticosLiq;
use Src\Usuarios\Login\Php\Clases\Usuario;

/**
 * Clase para gestionar liquidacion de nomina de los empleados.
 *
 * Esta clase permite realizar operaciones CRUD sobre liquidacion de nomina de los empleados,
 * incluyendo la obtención de registros, adición, edición y eliminación de liquidacion de nomina.
 */
class Liquidacion
{
    private $conexion;

    public function __construct($conexion = null)
    {
        $this->conexion = $conexion ?: Conexion::getConexion(); // Método estático que retorna el objeto PDO
    }

    /**
     * Obtiene los datos para la DataTable.
     *
     * @param int $start Índice de inicio para la paginación
     * @param int $length Número de registros a mostrar
     * @param string $array  filtros de búsqueda
     * @param int $col Índice de la columna para ordenar
     * @param string $dir Dirección de ordenamiento (ascendente o descendente) 
     * @return array|[] Retorna un array con los datos 
     */
    public function getRegistrosDT($start, $length, $array, $col, $dir)
    {
        $limit = "";
        if ($length != -1) {
            $limit = "LIMIT $start, $length";
        }

        $where = '';
        if (!empty($array)) {
            if (isset($array['filter_nodoc']) && $array['filter_nodoc'] != '') {
                $where .= " AND `taux`.`no_documento` LIKE '%{$array['filter_nodoc']}%'";
            }
            if (isset($array['filter_nombre']) && $array['filter_nombre'] != '') {
                $where .= " AND `taux`.`nombre` LIKE '%{$array['filter_nombre']}%'";
            }
        }
        $mes = $array['filter_mes'];
        $tipo = $array['filter_tipo'];
        $fec_inicio = Sesion::Vigencia() . '-' . $mes . '-01';
        $fec_fin = date('Y-m-t', strtotime($fec_inicio));
        $sql = "SELECT 
                    `taux`.`id_empleado`
                    , `taux`.`no_documento`
                    , `taux`.`nombre`
                    , `taux`.`id_contrato`
                    , IFNULL(`tt`.`inc`,0) AS `inc`
                    , IFNULL(`tt`.`lic`,0) AS `lic`
                    , IFNULL(`tt`.`vac`,0) AS `vac`
                    , IFNULL(`tt`.`ivac`,0) AS `ivac`
                    , `tt`.`ids_vac`
                    , IF(DATE_FORMAT(LEAST('$fec_fin', `taux`.`fec_fin`), '%Y-%m-%d') = DATE_FORMAT(LAST_DAY(LEAST('$fec_fin', `taux`.`fec_fin`)), '%Y-%m-%d'), 30, LEAST(30, DAY(LEAST('$fec_fin', `taux`.`fec_fin`))))
                        - IF(DATE_FORMAT(GREATEST('$fec_inicio', `taux`.`fec_inicio`), '%d') = '01', 1, LEAST(30, DAY(GREATEST('$fec_inicio', `taux`.`fec_inicio`)))) + 1 AS `dias_mes`
                    , IF(`obs`.`corte` > 0,  DATEDIFF('$fec_fin', `obs`.`corte`), 0) AS `observacion`
                FROM
                    (SELECT 
                        `ctt`.`id_empleado`
                        , `e`.`no_documento`
                        , CONCAT_WS(' ', `e`.`nombre1`, `e`.`nombre2`, `e`.`apellido1`, `e`.`apellido2`) AS `nombre`
                        , `ctt`.`fec_fin`
                        , `ctt`.`fec_inicio`
                        , `ctt`.`id_contrato_emp` AS `id_contrato`
                    FROM
                        (SELECT
                            `id_empleado`
                            , `fec_inicio`
                            , IFNULL(`fec_fin`, '2999-12-31') AS `fec_fin`
                            , `id_contrato_emp`
                        FROM
                            `nom_contratos_empleados`
                        WHERE `id_contrato_emp` IN (
                            SELECT MAX(`id_contrato_emp`) 
                            FROM `nom_contratos_empleados` 
                            GROUP BY `id_empleado`)
                        ) AS `ctt`
                        INNER JOIN `nom_empleado` `e` ON (`ctt`.`id_empleado` = `e`.`id_empleado`)
                    WHERE  '$fec_fin' >= `ctt`.`fec_inicio` AND '$fec_inicio' <= `ctt`.`fec_fin` AND `e`.`estado` = 1
                    ORDER BY `e`.`no_documento`, `nombre`) AS `taux`
                    LEFT JOIN
                        (SELECT 
                            `id_empleado`
                            , SUM(CASE WHEN `id_tipo` = 1 THEN 1 ELSE 0 END) AS `inc`
                            , SUM(CASE WHEN `id_tipo` = 2 THEN 1 ELSE 0 END) AS `vac`
                            , SUM(CASE WHEN `id_tipo` IN (3, 4, 5) THEN 1 ELSE 0 END) AS `lic`
                            , SUM(CASE WHEN `id_tipo` = 6 THEN 1 ELSE 0 END) AS `ivac`
                            , GROUP_CONCAT(DISTINCT CASE WHEN `id_tipo` = 2 THEN `id_novedad` END) AS `ids_vac`
                        FROM 
                            `nom_calendar_novedad`
                        WHERE 
                            `fecha` BETWEEN '$fec_inicio' AND '$fec_fin'
                            AND DAY(`fecha`) <= 30
                            AND `id_tipo` IN (1, 2, 3, 4, 5, 6)
                        GROUP BY `id_empleado`) AS `tt`
                        ON (`taux`.`id_empleado` = `tt`.`id_empleado`) 
                    LEFT JOIN
                        (SELECT
                            `id_empleado`,IFNULL(`corte`,0) AS `corte`
                        FROM `nom_vacaciones`
                        WHERE `id_vac` IN
                            (SELECT
                                MAX(`nom_vacaciones`.`id_vac`) AS `id`
                            FROM
                                `nom_liq_vac`
                                INNER JOIN `nom_vacaciones` 
                                ON (`nom_liq_vac`.`id_vac` = `nom_vacaciones`.`id_vac`)
                                INNER JOIN `nom_nominas` 
                                ON (`nom_liq_vac`.`id_nomina` = `nom_nominas`.`id_nomina`)
                            WHERE (`nom_nominas`.`estado` = 5)
                            GROUP BY `nom_vacaciones`.`id_empleado`)) AS `obs`
                        ON (`taux`.`id_empleado` = `obs`.`id_empleado`)
                WHERE (1 = 1 $where)
                ORDER BY $col $dir $limit";
        $stmt = $this->conexion->prepare($sql);
        $stmt->execute();
        $datos = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $stmt->closeCursor();
        unset($stmt);
        if ($mes == '0') {
            $datos = [];
        }
        return !empty($datos) ? $datos : [];
    }
    /**
     * Obtiene el total de registros filtrados.
     * 
     * @param string $val_busca Valor de búsqueda
     * @return int Total de registros filtrados
     */
    public function getRegistrosFilter($array)
    {
        $where = '';
        if (!empty($array)) {
            if (isset($array['filter_nodoc']) && $array['filter_nodoc'] != '') {
                $where .= " AND `taux`.`no_documento` LIKE '%{$array['filter_nodoc']}%'";
            }
            if (isset($array['filter_nombre']) && $array['filter_nombre'] != '') {
                $where .= " AND `taux`.`nombre` LIKE '%{$array['filter_nombre']}%'";
            }
        }
        $mes = $array['filter_mes'];
        $tipo = $array['filter_tipo'];
        $fec_inicio = Sesion::Vigencia() . '-' . $mes . '-01';
        $fec_fin = date('Y-m-t', strtotime($fec_inicio));

        $sql = "SELECT 
                    COUNT(*) AS `total`
                FROM
                    (SELECT 
                        `ctt`.`id_empleado`,
                        `e`.`no_documento`,
                        CONCAT_WS(' ', `e`.`nombre1`, `e`.`nombre2`, `e`.`apellido1`, `e`.`apellido2`) AS `nombre`
                    FROM
                        (SELECT
                            `id_empleado`,
                            `fec_inicio`,
                            IFNULL(`fec_fin`, '2999-12-31') AS `fec_fin`
                        FROM
                            `nom_contratos_empleados`
                        WHERE `id_contrato_emp` IN (
                            SELECT MAX(`id_contrato_emp`) 
                            FROM `nom_contratos_empleados` 
                            GROUP BY `id_empleado`)
                        ) AS `ctt`
                        INNER JOIN `nom_empleado` `e` ON (`ctt`.`id_empleado` = `e`.`id_empleado`)
                    WHERE  '$fec_fin' >= `ctt`.`fec_inicio` AND '$fec_inicio' <= `ctt`.`fec_fin` AND `e`.`estado` = 1
                    ORDER BY `e`.`no_documento`, `nombre`) AS `taux`
                    LEFT JOIN
                        (SELECT 
                            `id_empleado`
                            , SUM(CASE WHEN `id_tipo` = 1 THEN 1 ELSE 0 END) AS `inc`
                            , SUM(CASE WHEN `id_tipo` = 2 THEN 1 ELSE 0 END) AS `vac`
                            , SUM(CASE WHEN `id_tipo` IN (3, 4, 5) THEN 1 ELSE 0 END) AS `lic`
                        FROM 
                            `nom_calendar_novedad`
                        WHERE 
                            `fecha` BETWEEN '$fec_inicio' AND '$fec_fin'
                            AND DAY(`fecha`) <= 30
                            AND `id_tipo` IN (1, 2, 3, 4, 5, 6)
                        GROUP BY `id_empleado`) AS `tt`
                        ON (`taux`.`id_empleado` = `tt`.`id_empleado`) 
                    LEFT JOIN
                        (SELECT
                            `id_empleado`,IFNULL(`corte`,0) AS `corte`
                        FROM `nom_vacaciones`
                        WHERE `id_vac` IN
                            (SELECT
                                MAX(`nom_vacaciones`.`id_vac`) AS `id`
                            FROM
                                `nom_liq_vac`
                                INNER JOIN `nom_vacaciones` 
                                ON (`nom_liq_vac`.`id_vac` = `nom_vacaciones`.`id_vac`)
                                INNER JOIN `nom_nominas` 
                                ON (`nom_liq_vac`.`id_nomina` = `nom_nominas`.`id_nomina`)
                            WHERE (`nom_nominas`.`estado` = 5)
                            GROUP BY `nom_vacaciones`.`id_empleado`)) AS `obs`
                        ON (`taux`.`id_empleado` = `obs`.`id_empleado`)
                WHERE (1 = 1 $where)";
        $stmt = $this->conexion->prepare($sql);
        $stmt->execute();
        if ($mes == '0') {
            return 0;
        }
        $registro = $stmt->fetch(PDO::FETCH_ASSOC);
        $stmt->closeCursor();
        unset($stmt);
        return !empty($registro) ? ($registro['total'] ?? 0) : 0;
    }

    /**
     * Obtiene el total de registros.
     * @return int Total de registros
     */

    public function getRegistrosTotal($array)
    {
        $mes = $array['filter_mes'];
        $tipo = $array['filter_tipo'];
        $fec_inicio = Sesion::Vigencia() . '-' . $mes . '-01';
        $fec_fin = date('Y-m-t', strtotime($fec_inicio));

        $sql = "SELECT 
                    COUNT(*) AS `total`
                FROM
                    (SELECT 
                        `ctt`.`id_empleado`,
                        `e`.`no_documento`,
                        CONCAT_WS(' ', `e`.`nombre1`, `e`.`nombre2`, `e`.`apellido1`, `e`.`apellido2`) AS `nombre`
                    FROM
                        (SELECT
                            `id_empleado`,
                            `fec_inicio`,
                            IFNULL(`fec_fin`, '2999-12-31') AS `fec_fin`
                        FROM
                            `nom_contratos_empleados`
                        WHERE `id_contrato_emp` IN (
                            SELECT MAX(`id_contrato_emp`) 
                            FROM `nom_contratos_empleados` 
                            GROUP BY `id_empleado`)
                        ) AS `ctt`
                        INNER JOIN `nom_empleado` `e` ON (`ctt`.`id_empleado` = `e`.`id_empleado`)
                    WHERE  '$fec_fin' >= `ctt`.`fec_inicio` AND '$fec_inicio' <= `ctt`.`fec_fin` AND `e`.`estado` = 1
                    ORDER BY `e`.`no_documento`, `nombre`) AS `taux`
                    LEFT JOIN
                        (SELECT 
                            `id_empleado`
                            , SUM(CASE WHEN `id_tipo` = 1 THEN 1 ELSE 0 END) AS `inc`
                            , SUM(CASE WHEN `id_tipo` = 2 THEN 1 ELSE 0 END) AS `vac`
                            , SUM(CASE WHEN `id_tipo` IN (3, 4, 5) THEN 1 ELSE 0 END) AS `lic`
                        FROM 
                            `nom_calendar_novedad`
                        WHERE 
                            `fecha` BETWEEN '$fec_inicio' AND '$fec_fin'
                            AND DAY(`fecha`) <= 30
                            AND `id_tipo` IN (1, 2, 3, 4, 5, 6)
                        GROUP BY `id_empleado`) AS `tt`
                        ON (`taux`.`id_empleado` = `tt`.`id_empleado`) 
                    LEFT JOIN
                        (SELECT
                            `id_empleado`,IFNULL(`corte`,0) AS `corte`
                        FROM `nom_vacaciones`
                        WHERE `id_vac` IN
                            (SELECT
                                MAX(`nom_vacaciones`.`id_vac`) AS `id`
                            FROM
                                `nom_liq_vac`
                                INNER JOIN `nom_vacaciones` 
                                ON (`nom_liq_vac`.`id_vac` = `nom_vacaciones`.`id_vac`)
                                INNER JOIN `nom_nominas` 
                                ON (`nom_liq_vac`.`id_nomina` = `nom_nominas`.`id_nomina`)
                            WHERE (`nom_nominas`.`estado` = 5)
                            GROUP BY `nom_vacaciones`.`id_empleado`)) AS `obs`
                        ON (`taux`.`id_empleado` = `obs`.`id_empleado`)
                WHERE (1 = 1)";
        $stmt = $this->conexion->prepare($sql);
        $stmt->execute();
        if ($mes == '0') {
            return 0;
        }
        $registro = $stmt->fetch(PDO::FETCH_ASSOC);
        $stmt->closeCursor();
        unset($stmt);
        return !empty($registro) ? ($registro['total'] ?? 0) : 0;
    }

    public function getRetroactivosActivos()
    {
        try {
            $sql = "SELECT
                        `nr`.`id_retroactivo`,
                        `nr`.`fec_inicio`,
                        `nr`.`fec_final`,
                        `nr`.`meses`,
                        `nr`.`id_incremento`,
                        `nis`.`porcentaje`
                    FROM `nom_retroactivos` `nr`
                    INNER JOIN `nom_incremento_salario` `nis`
                        ON (`nr`.`id_incremento` = `nis`.`id_inc`)
                    WHERE `nr`.`estado` = 1 AND `nis`.`estado` = 1 AND `nis`.`vigencia` = ?
                    ORDER BY `nr`.`vigencia` DESC, `nr`.`fec_inicio` DESC";
            $stmt = $this->conexion->prepare($sql);
            $stmt->bindValue(1, Sesion::Vigencia(), PDO::PARAM_INT);
            $stmt->execute();
            $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $stmt->closeCursor();
            unset($stmt);
            if (empty($data)) {
                return [];
            }
            foreach ($data as &$row) {
                $row['detalle'] = $row['fec_inicio'] . ' a ' . $row['fec_final'] . ' | ' . $row['porcentaje'] . '% | ' . $row['meses'] . ' mes(es)';
            }
            return $data;
        } catch (PDOException $e) {
            return [];
        }
    }

    public function getRetroactivoById($id_retroactivo)
    {
        try {
            $sql = "SELECT
                        `nr`.`id_retroactivo`,
                        `nr`.`fec_inicio`,
                        `nr`.`fec_final`,
                        `nr`.`meses`,
                        `nr`.`id_incremento`,
                        `nr`.`vigencia`,
                        `nis`.`porcentaje`
                    FROM `nom_retroactivos` `nr`
                    INNER JOIN `nom_incremento_salario` `nis`
                        ON (`nr`.`id_incremento` = `nis`.`id_inc`)
                    WHERE `nr`.`id_retroactivo` = ?
                        AND `nr`.`estado` = 1
                    LIMIT 1";
            $stmt = $this->conexion->prepare($sql);
            $stmt->bindValue(1, $id_retroactivo, PDO::PARAM_INT);
            $stmt->execute();
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            $stmt->closeCursor();
            unset($stmt);
            return !empty($row) ? $row : [];
        } catch (PDOException $e) {
            return [];
        }
    }

    private function getSqlRetroListado($array, $countOnly = false)
    {
        $retroactivo = intval($array['filter_retroactivo'] ?? 0);
        if ($retroactivo <= 0) {
            return '';
        }
        $retro = $this->getRetroactivoById($retroactivo);
        if (empty($retro)) {
            return '';
        }

        $where = '';
        if (!empty($array['filter_nodoc'])) {
            $where .= " AND `base`.`no_documento` LIKE '%{$array['filter_nodoc']}%'";
        }
        if (!empty($array['filter_nombre'])) {
            $where .= " AND `base`.`nombre` LIKE '%{$array['filter_nombre']}%'";
        }

        $parametros = array_column(Nomina::getParamLiq(), 'valor', 'id_concepto');
        $smmlv = intval($parametros[1] ?? 0);
        $sqlBase = "FROM (
                        SELECT
                            `e`.`id_empleado`,
                            `e`.`no_documento`,
                            CONCAT_WS(' ', `e`.`nombre1`, `e`.`nombre2`, `e`.`apellido1`, `e`.`apellido2`) AS `nombre`,
                            MAX(`ls`.`id_contrato`) AS `id_contrato`,
                            SUM(IFNULL(`d`.`dias_liq`, 0) + IFNULL(`comp`.`dias`, 0)) AS `laborado`,
                            SUM(IFNULL(`inc`.`dias`, 0)) AS `inc`,
                            SUM(IFNULL(`licl`.`dias`, 0) + IFNULL(`licm`.`dias`, 0) + IFNULL(`licn`.`dias`, 0)) AS `lic`,
                            SUM(IFNULL(`vac`.`dias`, 0)) AS `vac`,
                            COUNT(DISTINCT `n`.`mes`) AS `meses`,
                            CONCAT('{$retro['fec_inicio']}', ' a ', '{$retro['fec_final']}') AS `rango`
                        FROM `nom_nominas` `n`
                        INNER JOIN `nom_liq_salario` `ls`
                            ON (`ls`.`id_nomina` = `n`.`id_nomina` AND `ls`.`estado` = 1)
                        INNER JOIN `nom_empleado` `e`
                            ON (`e`.`id_empleado` = `ls`.`id_empleado`)
                        LEFT JOIN (
                            SELECT `sb1`.`id_empleado`, `sb1`.`salario_basico`
                            FROM `nom_salarios_basico` `sb1`
                            INNER JOIN (
                                SELECT MAX(`id_salario`) AS `id_salario`
                                FROM `nom_salarios_basico`
                                WHERE `vigencia` = '{$retro['vigencia']}'
                                    AND `id_inc` = {$retro['id_incremento']}
                                GROUP BY `id_empleado`
                            ) `sb2` ON (`sb1`.`id_salario` = `sb2`.`id_salario`)
                        ) `sal`
                            ON (`sal`.`id_empleado` = `ls`.`id_empleado`)
                        LEFT JOIN (
                            SELECT `sb3`.`id_empleado`, `sb3`.`salario_basico`
                            FROM `nom_salarios_basico` `sb3`
                            INNER JOIN (
                                SELECT MAX(`id_salario`) AS `id_salario`
                                FROM `nom_salarios_basico`
                                GROUP BY `id_empleado`
                            ) `sb4` ON (`sb3`.`id_salario` = `sb4`.`id_salario`)
                        ) `sal_prev`
                            ON (`sal_prev`.`id_empleado` = `ls`.`id_empleado`)
                        LEFT JOIN `nom_liq_dlab_auxt` `d`
                            ON (`d`.`id_nomina` = `n`.`id_nomina` AND `d`.`id_empleado` = `ls`.`id_empleado` AND `d`.`estado` = 1)
                        LEFT JOIN (
                            SELECT `id_nomina`, `id_empleado`, SUM(`dias`) AS `dias`
                            FROM `nom_liq_compesatorio`
                            WHERE `estado` = 1
                            GROUP BY `id_nomina`, `id_empleado`
                        ) `comp`
                            ON (`comp`.`id_nomina` = `n`.`id_nomina` AND `comp`.`id_empleado` = `ls`.`id_empleado`)
                        LEFT JOIN (
                            SELECT `li`.`id_nomina`, `i`.`id_empleado`, SUM(`li`.`dias_liq`) AS `dias`
                            FROM `nom_liq_incap` `li`
                            INNER JOIN `nom_incapacidad` `i`
                                ON (`i`.`id_incapacidad` = `li`.`id_incapacidad`)
                            WHERE `li`.`estado` = 1
                            GROUP BY `li`.`id_nomina`, `i`.`id_empleado`
                        ) `inc`
                            ON (`inc`.`id_nomina` = `n`.`id_nomina` AND `inc`.`id_empleado` = `ls`.`id_empleado`)
                        LEFT JOIN (
                            SELECT `ll`.`id_nomina`, `l`.`id_empleado`, SUM(`ll`.`dias_licluto`) AS `dias`
                            FROM `nom_liq_licluto` `ll`
                            INNER JOIN `nom_licencia_luto` `l`
                                ON (`l`.`id_licluto` = `ll`.`id_licluto`)
                            WHERE `ll`.`estado` = 1
                            GROUP BY `ll`.`id_nomina`, `l`.`id_empleado`
                        ) `licl`
                            ON (`licl`.`id_nomina` = `n`.`id_nomina` AND `licl`.`id_empleado` = `ls`.`id_empleado`)
                        LEFT JOIN (
                            SELECT `lm`.`id_nomina`, `m`.`id_empleado`, SUM(`lm`.`dias_liqs`) AS `dias`
                            FROM `nom_liq_licmp` `lm`
                            INNER JOIN `nom_licenciasmp` `m`
                                ON (`m`.`id_licmp` = `lm`.`id_licmp`)
                            WHERE `lm`.`estado` = 1
                            GROUP BY `lm`.`id_nomina`, `m`.`id_empleado`
                        ) `licm`
                            ON (`licm`.`id_nomina` = `n`.`id_nomina` AND `licm`.`id_empleado` = `ls`.`id_empleado`)
                        LEFT JOIN (
                            SELECT `ln`.`id_nomina`, `nrl`.`id_empleado`, SUM(`ln`.`dias_licnr`) AS `dias`
                            FROM `nom_liq_licnr` `ln`
                            INNER JOIN `nom_licenciasnr` `nrl`
                                ON (`nrl`.`id_licnr` = `ln`.`id_licnr`)
                            WHERE `ln`.`estado` = 1
                            GROUP BY `ln`.`id_nomina`, `nrl`.`id_empleado`
                        ) `licn`
                            ON (`licn`.`id_nomina` = `n`.`id_nomina` AND `licn`.`id_empleado` = `ls`.`id_empleado`)
                        LEFT JOIN (
                            SELECT `lv`.`id_nomina`, `nv`.`id_empleado`, SUM(`nv`.`dias_inactivo`) AS `dias`
                            FROM `nom_liq_vac` `lv`
                            INNER JOIN `nom_vacaciones` `nv`
                                ON (`nv`.`id_vac` = `lv`.`id_vac`)
                            WHERE `lv`.`estado` = 1
                            GROUP BY `lv`.`id_nomina`, `nv`.`id_empleado`
                        ) `vac`
                            ON (`vac`.`id_nomina` = `n`.`id_nomina` AND `vac`.`id_empleado` = `ls`.`id_empleado`)
                        WHERE `n`.`tipo` <> 'RA'
                            AND `n`.`estado` = 5
                            AND (
                                `n`.`fecha` BETWEEN '{$retro['fec_inicio']}' AND '{$retro['fec_final']}'
                                OR (`n`.`tipo` = 'PS' AND `n`.`vigencia` = '{$retro['vigencia']}' AND `n`.`fecha` <= DATE_ADD('{$retro['fec_final']}', INTERVAL 1 MONTH))
                            )
                            AND COALESCE(`sal`.`salario_basico`, `sal_prev`.`salario_basico`, `ls`.`sal_base`, 0) <> {$smmlv}
                            AND COALESCE(`sal`.`salario_basico`, `sal_prev`.`salario_basico`, `ls`.`sal_base`, 0) > 0
                        GROUP BY `e`.`id_empleado`, `e`.`no_documento`, `nombre`
                    ) `base`
                    WHERE 1 = 1 {$where}";

        if ($countOnly) {
            return "SELECT COUNT(*) AS `total` {$sqlBase}";
        }
        return "SELECT `base`.* {$sqlBase}";
    }

    public function getRegistrosRetroDT($start, $length, $array, $col, $dir)
    {
        $sql = $this->getSqlRetroListado($array, false);
        if ($sql === '') {
            return [];
        }
        $cols = [
            1 => '`base`.`id_empleado`',
            2 => '`base`.`no_documento`',
            3 => '`base`.`nombre`',
            4 => '`base`.`rango`',
            5 => '`base`.`laborado`',
            6 => '`base`.`inc`',
            7 => '`base`.`lic`',
            8 => '`base`.`vac`',
            9 => '`base`.`meses`',
            10 => '`base`.`id_contrato`',
        ];
        $order = $cols[$col] ?? '`base`.`no_documento`';
        $limit = $length != -1 ? " LIMIT {$start}, {$length}" : '';
        $stmt = $this->conexion->prepare($sql . " ORDER BY {$order} {$dir}{$limit}");
        $stmt->execute();
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $stmt->closeCursor();
        unset($stmt);
        return !empty($data) ? $data : [];
    }

    public function getRegistrosRetroFilter($array)
    {
        $sql = $this->getSqlRetroListado($array, true);
        if ($sql === '') {
            return 0;
        }
        $stmt = $this->conexion->prepare($sql);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        $stmt->closeCursor();
        unset($stmt);
        return intval($row['total'] ?? 0);
    }

    public function getRegistrosRetroTotal($array)
    {
        $filtros = $array;
        $filtros['filter_nodoc'] = '';
        $filtros['filter_nombre'] = '';
        return $this->getRegistrosRetroFilter($filtros);
    }

    private function getSalarioRetroactivoEmpleado($id_empleado, $retro)
    {
        // 1. Intentar obtener el salario directo registrado con el id_incremento
        $sql = "SELECT `sb1`.`salario_basico`
                FROM `nom_salarios_basico` `sb1`
                INNER JOIN (
                    SELECT MAX(`id_salario`) AS `id_salario`
                    FROM `nom_salarios_basico`
                    WHERE `vigencia` = ?
                        AND `id_inc` = ?
                        AND `id_empleado` = ?
                ) `sb2` ON (`sb1`.`id_salario` = `sb2`.`id_salario`)
                LIMIT 1";
        $stmt = $this->conexion->prepare($sql);
        $stmt->bindValue(1, $retro['vigencia'], PDO::PARAM_STR);
        $stmt->bindValue(2, $retro['id_incremento'], PDO::PARAM_INT);
        $stmt->bindValue(3, $id_empleado, PDO::PARAM_INT);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        $stmt->closeCursor();
        unset($stmt);
        $salario = floatval($row['salario_basico'] ?? 0);
        if ($salario > 0) {
            return $salario;
        }

        // 2. Fallback para empleados liquidados con Prestaciones Sociales o retirados (sin id_inc):
        // Buscar el último salario básico registrado para el empleado
        $sqlUltSal = "SELECT `salario_basico`, `id_contrato`
                      FROM `nom_salarios_basico`
                      WHERE `id_empleado` = ?
                      ORDER BY `id_salario` DESC
                      LIMIT 1";
        $stmtUlt = $this->conexion->prepare($sqlUltSal);
        $stmtUlt->bindValue(1, $id_empleado, PDO::PARAM_INT);
        $stmtUlt->execute();
        $rowUlt = $stmtUlt->fetch(PDO::FETCH_ASSOC);
        $stmtUlt->closeCursor();
        unset($stmtUlt);

        $salBaseAnt = floatval($rowUlt['salario_basico'] ?? 0);
        $idContrato = intval($rowUlt['id_contrato'] ?? 0);

        // Si no está en nom_salarios_basico, buscarlo en la última liquidación de salario
        if ($salBaseAnt <= 0) {
            $sqlLiq = "SELECT `sal_base`, `id_contrato`
                       FROM `nom_liq_salario`
                       WHERE `id_empleado` = ? AND `estado` = 1 AND `sal_base` > 0
                       ORDER BY `id_sal_liq` DESC
                       LIMIT 1";
            $stmtLiq = $this->conexion->prepare($sqlLiq);
            $stmtLiq->bindValue(1, $id_empleado, PDO::PARAM_INT);
            $stmtLiq->execute();
            $rowLiq = $stmtLiq->fetch(PDO::FETCH_ASSOC);
            $stmtLiq->closeCursor();
            unset($stmtLiq);
            $salBaseAnt = floatval($rowLiq['sal_base'] ?? 0);
            if ($idContrato <= 0) {
                $idContrato = intval($rowLiq['id_contrato'] ?? 0);
            }
        }

        if ($salBaseAnt > 0) {
            $porcentaje = floatval($retro['porcentaje'] ?? 0);
            $salarioNuevo = Valores::Redondear($salBaseAnt * (1 + ($porcentaje / 100)));
            if ($salarioNuevo > 0 && !empty($retro['id_incremento'])) {
                try {
                    $sqlInsSal = "INSERT INTO `nom_salarios_basico`
                                  (`id_empleado`, `id_contrato`, `vigencia`, `salario_basico`, `id_inc`, `fec_reg`)
                                  VALUES (?, ?, ?, ?, ?, ?)";
                    $stmtInsSal = $this->conexion->prepare($sqlInsSal);
                    $stmtInsSal->execute([
                        $id_empleado,
                        $idContrato,
                        $retro['vigencia'],
                        $salarioNuevo,
                        $retro['id_incremento'],
                        Sesion::Hoy()
                    ]);
                    $stmtInsSal->closeCursor();
                    unset($stmtInsSal);
                } catch (\Exception $e) {
                    // Si ya existe o falla por constraint, no interrumpir
                }
            }
            return $salarioNuevo;
        }

        return 0;
    }

    private function getRetroMensualData($id_empleado, $retro)
    {
        $sql = "SELECT
                    `n`.`id_nomina`,
                    `n`.`mes`,
                    `n`.`vigencia`,
                    `n`.`tipo` AS `tipo_nomina`,
                    `ls`.`id_contrato`,
                    `ls`.`metodo_pago`,
                    IFNULL(`ls`.`sal_base`, 0) AS `sal_base_ant`,
                    IFNULL(`ls`.`val_liq`, 0) AS `val_neto_pagado`,
                    IFNULL(`d`.`dias_liq`, 0) AS `dias_laborados`,
                    IFNULL(`d`.`val_liq_dias`, 0) AS `val_laborado`,
                    IFNULL(`comp`.`val_compensa`, 0) AS `val_compensa_pagada`,
                    IFNULL(`comp`.`dias`, 0) AS `dias_compensa_pagada`,
                    IFNULL(`d`.`horas_ext`, 0) AS `horas_ext`,
                    IFNULL(`d`.`val_liq_auxt`, 0) AS `val_aux_trans`,
                    IFNULL(`d`.`aux_alim`, 0) AS `val_aux_alim`,
                    IFNULL(`d`.`g_representa`, 0) AS `g_representa`,
                    IFNULL(`bsp`.`val_bsp`, 0) AS `val_bsp_pagada`,
                    IFNULL(`ss`.`aporte_salud_emp`, 0) AS `aporte_salud_emp`,
                    IFNULL(`ss`.`aporte_pension_emp`, 0) AS `aporte_pension_emp`,
                    IFNULL(`ss`.`aporte_solidaridad_pensional`, 0) AS `aporte_solidaridad_pensional`,
                    IFNULL(`ss`.`aporte_salud_empresa`, 0) AS `aporte_salud_empresa`,
                    IFNULL(`ss`.`aporte_pension_empresa`, 0) AS `aporte_pension_empresa`,
                    IFNULL(`ss`.`aporte_rieslab`, 0) AS `aporte_rieslab`,
                    IFNULL(`pf`.`val_sena`, 0) AS `val_sena`,
                    IFNULL(`pf`.`val_icbf`, 0) AS `val_icbf`,
                    IFNULL(`pf`.`val_comfam`, 0) AS `val_comfam`,
                    IFNULL(`ps`.`val_vacacion`, 0) AS `val_vacacion`,
                    IFNULL(`ps`.`val_cesantia`, 0) AS `val_cesantia`,
                    IFNULL(`ps`.`val_interes_cesantia`, 0) AS `val_interes_cesantia`,
                    IFNULL(`ps`.`val_prima`, 0) AS `val_prima`,
                    IFNULL(`ps`.`val_prima_vac`, 0) AS `val_prima_vac`,
                    IFNULL(`ps`.`val_prima_nav`, 0) AS `val_prima_nav`,
                    IFNULL(`ps`.`val_bonifica_recrea`, 0) AS `val_bonifica_recrea`,
                    IFNULL(`rf`.`base`, 0) AS `base_ret`,
                    IFNULL(`rf`.`val_ret`, 0) AS `val_ret`,
                    IFNULL(`vac`.`val_liq`, 0) AS `val_vac_pagada`,
                    IFNULL(`vac`.`val_prima_vac`, 0) AS `val_prima_vac_pagada`,
                    IFNULL(`vac`.`val_bon_recrea`, 0) AS `val_bon_recrea_pagada`,
                    `vac`.`id_vac` AS `id_vac_pagada`,
                    IFNULL(`vac`.`dias_liqs`, 0) AS `dias_vac_pagada`,
                    IFNULL(`pri_serv`.`val_liq_ps`, 0) AS `val_prima_servicio_pagada`,
                    IFNULL(`pri_serv`.`cant_dias`, '') AS `dias_pri_serv`,
                    `pri_serv`.`corte` AS `corte_pri_serv`,
                    `pri_serv`.`periodo` AS `periodo_pri_serv`,
                    IFNULL(`pri_nav`.`val_liq_pv`, 0) AS `val_prima_navidad_pagada`,
                    IFNULL(`pri_nav`.`cant_dias`, '') AS `dias_pri_nav`,
                    `pri_nav`.`corte` AS `corte_pri_nav`,
                    `pri_nav`.`periodo` AS `periodo_pri_nav`,
                    IFNULL(`ces_dir`.`val_cesantias`, 0) AS `val_cesantias_pagada`,
                    IFNULL(`ces_dir`.`val_icesantias`, 0) AS `val_icesantias_pagada`,
                    IFNULL(`ces_dir`.`cant_dias`, '') AS `dias_cesantias`,
                    `ces_dir`.`corte` AS `corte_cesantias`,
                    IFNULL(`inc_dir`.`val_incap`, 0) AS `val_incap_pagada`,
                    `inc_dir`.`id_incapacidad` AS `id_incap_pagada`,
                    IFNULL(`inc_dir`.`dias_liq`, 0) AS `dias_incap_pagada`,
                    IFNULL(`lic_mp`.`val_licmp`, 0) AS `val_licmp_pagada`,
                    `lic_mp`.`id_licmp` AS `id_licmp_pagada`,
                    IFNULL(`lic_mp`.`dias_liqs`, 0) AS `dias_licmp_pagada`,
                    IFNULL(`lic_luto`.`val_licluto`, 0) AS `val_licluto_pagada`,
                    `lic_luto`.`id_licluto` AS `id_licluto_pagada`,
                    IFNULL(`lic_luto`.`dias_licluto`, 0) AS `dias_licluto_pagada`,
                    IFNULL(`indem_dir`.`val_indemniza`, 0) AS `val_indemniza_pagada`,
                    `indem_dir`.`id_indemnizacion` AS `id_indem_pagada`,
                    IFNULL(`indem_dir`.`dias_liq`, 0) AS `dias_indem_pagada`,
                    IFNULL(`odev_dir`.`val_odev`, 0) AS `val_odev_pagada`,
                    `odev_dir`.`id_devengado` AS `id_odev_pagada`
                FROM `nom_nominas` `n`
                INNER JOIN `nom_liq_salario` `ls`
                    ON (`ls`.`id_nomina` = `n`.`id_nomina` AND `ls`.`estado` = 1)
                LEFT JOIN `nom_liq_dlab_auxt` `d`
                    ON (`d`.`id_nomina` = `n`.`id_nomina` AND `d`.`id_empleado` = `ls`.`id_empleado` AND `d`.`estado` = 1)
                LEFT JOIN (
                    SELECT `id_nomina`, `id_empleado`,
                           SUM(`val_compensa`) AS `val_compensa`,
                           SUM(`dias`) AS `dias`
                    FROM `nom_liq_compesatorio`
                    WHERE `estado` = 1
                    GROUP BY `id_nomina`, `id_empleado`
                ) `comp` ON (`comp`.`id_nomina` = `n`.`id_nomina` AND `comp`.`id_empleado` = `ls`.`id_empleado`)
                LEFT JOIN `nom_liq_bsp` `bsp`
                    ON (`bsp`.`id_nomina` = `n`.`id_nomina` AND `bsp`.`id_empleado` = `ls`.`id_empleado` AND `bsp`.`estado` = 1)
                LEFT JOIN `nom_liq_segsocial_empdo` `ss`
                    ON (`ss`.`id_nomina` = `n`.`id_nomina` AND `ss`.`id_empleado` = `ls`.`id_empleado` AND `ss`.`estado` = 1)
                LEFT JOIN `nom_liq_parafiscales` `pf`
                    ON (`pf`.`id_nomina` = `n`.`id_nomina` AND `pf`.`id_empleado` = `ls`.`id_empleado` AND `pf`.`estado` = 1)
                LEFT JOIN `nom_liq_prestaciones_sociales` `ps`
                    ON (`ps`.`id_nomina` = `n`.`id_nomina` AND `ps`.`id_empleado` = `ls`.`id_empleado` AND `ps`.`estado` = 1)
                LEFT JOIN `nom_retencion_fte` `rf`
                    ON (`rf`.`id_nomina` = `n`.`id_nomina` AND `rf`.`id_empleado` = `ls`.`id_empleado` AND `rf`.`estado` = 1)
                LEFT JOIN (
                    SELECT `nlv`.`id_nomina`, `nv`.`id_empleado`,
                           MAX(`nlv`.`id_vac`) AS `id_vac`,
                           SUM(`nlv`.`dias_liqs`) AS `dias_liqs`,
                           SUM(`nlv`.`val_liq`) AS `val_liq`,
                           SUM(`nlv`.`val_prima_vac`) AS `val_prima_vac`,
                           SUM(`nlv`.`val_bon_recrea`) AS `val_bon_recrea`
                    FROM `nom_liq_vac` `nlv`
                    INNER JOIN `nom_vacaciones` `nv` ON (`nlv`.`id_vac` = `nv`.`id_vac`)
                    WHERE `nlv`.`estado` = 1
                    GROUP BY `nlv`.`id_nomina`, `nv`.`id_empleado`
                ) `vac` ON (`vac`.`id_nomina` = `n`.`id_nomina` AND `vac`.`id_empleado` = `ls`.`id_empleado`)
                LEFT JOIN (
                    SELECT `id_nomina`, `id_empleado`,
                           SUM(`val_liq_ps`) AS `val_liq_ps`,
                           MAX(`cant_dias`) AS `cant_dias`,
                           MAX(`corte`) AS `corte`,
                           MAX(`periodo`) AS `periodo`
                    FROM `nom_liq_prima`
                    WHERE `estado` = 1
                    GROUP BY `id_nomina`, `id_empleado`
                ) `pri_serv` ON (`pri_serv`.`id_nomina` = `n`.`id_nomina` AND `pri_serv`.`id_empleado` = `ls`.`id_empleado`)
                LEFT JOIN (
                    SELECT `id_nomina`, `id_empleado`,
                           SUM(`val_liq_pv`) AS `val_liq_pv`,
                           MAX(`cant_dias`) AS `cant_dias`,
                           MAX(`corte`) AS `corte`,
                           MAX(`periodo`) AS `periodo`
                    FROM `nom_liq_prima_nav`
                    WHERE `estado` = 1
                    GROUP BY `id_nomina`, `id_empleado`
                ) `pri_nav` ON (`pri_nav`.`id_nomina` = `n`.`id_nomina` AND `pri_nav`.`id_empleado` = `ls`.`id_empleado`)
                LEFT JOIN (
                    SELECT `id_nomina`, `id_empleado`,
                           SUM(`val_cesantias`) AS `val_cesantias`,
                           SUM(`val_icesantias`) AS `val_icesantias`,
                           MAX(`cant_dias`) AS `cant_dias`,
                           MAX(`corte`) AS `corte`
                    FROM `nom_liq_cesantias`
                    WHERE `estado` = 1
                    GROUP BY `id_nomina`, `id_empleado`
                ) `ces_dir` ON (`ces_dir`.`id_nomina` = `n`.`id_nomina` AND `ces_dir`.`id_empleado` = `ls`.`id_empleado`)
                LEFT JOIN (
                    SELECT `nli`.`id_nomina`, `ni`.`id_empleado`,
                           MAX(`nli`.`id_incapacidad`) AS `id_incapacidad`,
                           SUM(`nli`.`dias_liq`) AS `dias_liq`,
                           SUM(`nli`.`pago_empresa`) AS `val_incap`
                    FROM `nom_liq_incap` `nli`
                    INNER JOIN `nom_incapacidad` `ni` ON (`nli`.`id_incapacidad` = `ni`.`id_incapacidad`)
                    WHERE `nli`.`estado` = 1
                    GROUP BY `nli`.`id_nomina`, `ni`.`id_empleado`
                ) `inc_dir` ON (`inc_dir`.`id_nomina` = `n`.`id_nomina` AND `inc_dir`.`id_empleado` = `ls`.`id_empleado`)
                LEFT JOIN (
                    SELECT `nlmp`.`id_nomina`, `lmp`.`id_empleado`,
                           MAX(`nlmp`.`id_licmp`) AS `id_licmp`,
                           SUM(`nlmp`.`dias_liqs`) AS `dias_liqs`,
                           SUM(`nlmp`.`val_liq`) AS `val_licmp`
                    FROM `nom_liq_licmp` `nlmp`
                    INNER JOIN `nom_licenciasmp` `lmp` ON (`nlmp`.`id_licmp` = `lmp`.`id_licmp`)
                    WHERE `nlmp`.`estado` = 1
                    GROUP BY `nlmp`.`id_nomina`, `lmp`.`id_empleado`
                ) `lic_mp` ON (`lic_mp`.`id_nomina` = `n`.`id_nomina` AND `lic_mp`.`id_empleado` = `ls`.`id_empleado`)
                LEFT JOIN (
                    SELECT `nll`.`id_nomina`, `ll`.`id_empleado`,
                           MAX(`nll`.`id_licluto`) AS `id_licluto`,
                           SUM(`nll`.`dias_licluto`) AS `dias_licluto`,
                           SUM(`nll`.`val_liq`) AS `val_licluto`
                    FROM `nom_liq_licluto` `nll`
                    INNER JOIN `nom_licencia_luto` `ll` ON (`nll`.`id_licluto` = `ll`.`id_licluto`)
                    WHERE `nll`.`estado` = 1
                    GROUP BY `nll`.`id_nomina`, `ll`.`id_empleado`
                ) `lic_luto` ON (`lic_luto`.`id_nomina` = `n`.`id_nomina` AND `lic_luto`.`id_empleado` = `ls`.`id_empleado`)
                LEFT JOIN (
                    SELECT `nliv`.`id_nomina`, `niv`.`id_empleado`,
                           MAX(`nliv`.`id_indemnizacion`) AS `id_indemnizacion`,
                           SUM(`nliv`.`dias_liq`) AS `dias_liq`,
                           SUM(`nliv`.`val_liq`) AS `val_indemniza`
                    FROM `nom_liq_indemniza_vac` `nliv`
                    INNER JOIN `nom_indemniza_vac` `niv` ON (`nliv`.`id_indemnizacion` = `niv`.`id_indemniza`)
                    WHERE `nliv`.`estado` = 1
                    GROUP BY `nliv`.`id_nomina`, `niv`.`id_empleado`
                ) `indem_dir` ON (`indem_dir`.`id_nomina` = `n`.`id_nomina` AND `indem_dir`.`id_empleado` = `ls`.`id_empleado`)
                LEFT JOIN (
                    SELECT `nld`.`id_nomina`, `nod`.`id_empleado`,
                           MAX(`nld`.`id_devengado`) AS `id_devengado`,
                           SUM(`nld`.`valor`) AS `val_odev`
                    FROM `nom_liq_devengado` `nld`
                    INNER JOIN `nom_otros_devengados` `nod` ON (`nld`.`id_devengado` = `nod`.`id_devengado`)
                    WHERE `nld`.`estado` = 1
                    GROUP BY `nld`.`id_nomina`, `nod`.`id_empleado`
                ) `odev_dir` ON (`odev_dir`.`id_nomina` = `n`.`id_nomina` AND `odev_dir`.`id_empleado` = `ls`.`id_empleado`)
                WHERE `n`.`tipo` <> 'RA'
                    AND `n`.`estado` = 5
                    AND `ls`.`id_empleado` = ?
                    AND (
                        `n`.`fecha` BETWEEN ? AND ?
                        OR (`n`.`tipo` = 'PS' AND `n`.`vigencia` = ? AND `n`.`fecha` <= DATE_ADD(?, INTERVAL 1 MONTH))
                    )
                ORDER BY `n`.`fecha`, `n`.`id_nomina`";
        $stmt = $this->conexion->prepare($sql);
        $stmt->bindValue(1, $id_empleado, PDO::PARAM_INT);
        $stmt->bindValue(2, $retro['fec_inicio'], PDO::PARAM_STR);
        $stmt->bindValue(3, $retro['fec_final'], PDO::PARAM_STR);
        $stmt->bindValue(4, $retro['vigencia'], PDO::PARAM_STR);
        $stmt->bindValue(5, $retro['fec_final'], PDO::PARAM_STR);
        $stmt->execute();
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $stmt->closeCursor();
        unset($stmt);
        return !empty($rows) ? $rows : [];
    }

    private function getUltimaSeguridadSocialEmpleado($id_empleado)
    {
        try {
            $sql = "SELECT 
                        `nlse`.`id_eps`,
                        `nlse`.`id_arl`,
                        `nlse`.`id_afp`,
                        COALESCE(`rl`.`cotizacion`, 0.00522) AS `cotizacion_arl`
                    FROM `nom_liq_segsocial_empdo` `nlse`
                    INNER JOIN `nom_nominas` `n` ON (`n`.`id_nomina` = `nlse`.`id_nomina`)
                    LEFT JOIN (
                        SELECT `id_empleado`, `id_tercero`, `id_riesgo`
                        FROM `nom_terceros_novedad`
                        WHERE `id_novedad` IN (
                            SELECT MAX(`id_novedad`) FROM `nom_terceros_novedad` GROUP BY `id_empleado`, `id_tercero`
                        )
                    ) `tn` ON (`tn`.`id_empleado` = `nlse`.`id_empleado` AND `tn`.`id_tercero` = `nlse`.`id_arl`)
                    LEFT JOIN `nom_riesgos_laboral` `rl` ON (`rl`.`id_rlab` = `tn`.`id_riesgo`)
                    WHERE `nlse`.`id_empleado` = ?
                        AND `nlse`.`estado` = 1
                        AND `n`.`estado` = 5
                        AND `n`.`tipo` <> 'RA'
                    ORDER BY `n`.`fecha` DESC, `nlse`.`id_liq_empdo` DESC
                    LIMIT 1";
            $stmt = $this->conexion->prepare($sql);
            $stmt->execute([$id_empleado]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            $stmt->closeCursor();
            unset($stmt);
            return !empty($row) ? $row : null;
        } catch (PDOException $e) {
            return null;
        }
    }

    private function calcularSeguridadSocialRetro($ibc, $smmlv, $tipo, $subtipo, $diaslab, $porcentajeArl)
    {
        if ($ibc <= 0 || $diaslab <= 0) {
            return [
                'aporte_salud_emp' => 0,
                'aporte_pension_emp' => 0,
                'aporte_solidaridad_pensional' => 0,
                'aporte_salud_empresa' => 0,
                'aporte_pension_empresa' => 0,
                'aporte_rieslab' => 0,
            ];
        }

        $diaslab = min(30, max(1, intval($diaslab)));
        $ibcMinimo = ($smmlv / 30) * $diaslab;
        $ibcMaximo = ($smmlv * 25 / 30) * $diaslab;

        $ibc = Valores::Redondear($ibc, 1);
        $ibc = max($ibcMinimo, min($ibc, $ibcMaximo));

        $saludTotal = Valores::Redondear($ibc * 0.125, 100);
        $pensionTotal = Valores::Redondear($ibc * 0.16, 100);
        $saludEmpleado = Valores::Redondear($ibc * 0.04, 1);
        $pensionEmpleado = $saludEmpleado;
        $solidaridad = 0;

        // Escala completa progresiva de Fondo de Solidaridad Pensional (Ley 100/93 y Ley 797/03)
        if ($ibc >= ($smmlv * 4) && $ibc < ($smmlv * 16)) {
            $solidaridad = Valores::Redondear($ibc * 0.01, 100);
        } else if ($ibc >= ($smmlv * 16) && $ibc < ($smmlv * 17)) {
            $solidaridad = Valores::Redondear($ibc * 0.012, 100);
        } else if ($ibc >= ($smmlv * 17) && $ibc < ($smmlv * 18)) {
            $solidaridad = Valores::Redondear($ibc * 0.014, 100);
        } else if ($ibc >= ($smmlv * 18) && $ibc < ($smmlv * 19)) {
            $solidaridad = Valores::Redondear($ibc * 0.016, 100);
        } else if ($ibc >= ($smmlv * 19) && $ibc <= ($smmlv * 20)) {
            $solidaridad = Valores::Redondear($ibc * 0.018, 100);
        } else if ($ibc > ($smmlv * 20)) {
            $solidaridad = Valores::Redondear($ibc * 0.02, 100);
        }

        if ($tipo == 12) {
            $saludTotal = 0;
            $pensionTotal = 0;
            $saludEmpleado = 0;
            $pensionEmpleado = 0;
            $solidaridad = 0;
        } else if ($tipo == 8) {
            $saludEmpleado = 0;
            $pensionEmpleado = 0;
            $solidaridad = 0;
            $saludTotal = (($ibc / 30) * $diaslab) * 0.125;
            $pensionTotal = 0;
        }

        if ($subtipo == 2) {
            $pensionEmpleado = 0;
            $pensionTotal = 0;
            $solidaridad = 0;
        }

        return [
            'aporte_salud_emp' => $saludEmpleado,
            'aporte_pension_emp' => $pensionEmpleado,
            'aporte_solidaridad_pensional' => $solidaridad,
            'aporte_salud_empresa' => Valores::Redondear($saludTotal - $saludEmpleado, 100),
            'aporte_pension_empresa' => Valores::Redondear($pensionTotal - $pensionEmpleado, 100),
            'aporte_rieslab' => Valores::Redondear($ibc * $porcentajeArl, 100),
        ];
    }

    private function calcularRetencionBase($base, $uvt)
    {
        $ingLabUvt = $uvt > 0 ? ($base / $uvt) : 0;
        $retencion = 0;
        if ($ingLabUvt >= 95 && $ingLabUvt < 150) {
            $retencion = $uvt * ($ingLabUvt - 95) * 0.19;
        } else if ($ingLabUvt >= 150 && $ingLabUvt < 360) {
            $retencion = ($uvt * ($ingLabUvt - 150) * 0.28) + (10 * $uvt);
        } else if ($ingLabUvt >= 360 && $ingLabUvt < 640) {
            $retencion = ($uvt * ($ingLabUvt - 360) * 0.33) + (69 * $uvt);
        } else if ($ingLabUvt >= 640 && $ingLabUvt < 945) {
            $retencion = ($uvt * ($ingLabUvt - 640) * 0.35) + (162 * $uvt);
        } else if ($ingLabUvt >= 945 && $ingLabUvt < 2300) {
            $retencion = ($uvt * ($ingLabUvt - 945) * 0.37) + (268 * $uvt);
        } else if ($ingLabUvt >= 2300) {
            $retencion = ($uvt * ($ingLabUvt - 2300) * 0.39) + (770 * $uvt);
        }
        return Valores::Redondear($retencion);
    }

    private function insertRetencionFuenteRetro($id_empleado, $id_nomina, $base, $retencion)
    {
        if ($retencion == 0 && $base == 0) {
            return 'si';
        }
        $sql = "INSERT INTO `nom_retencion_fte`
                    (`id_empleado`,`base`,`val_ret`,`id_user_reg`,`fec_reg`,`id_nomina`)
                VALUES (?, ?, ?, ?, ?, ?)";
        $stmt = $this->conexion->prepare($sql);
        $stmt->bindValue(1, $id_empleado, PDO::PARAM_INT);
        $stmt->bindValue(2, $base, PDO::PARAM_STR);
        $stmt->bindValue(3, $retencion, PDO::PARAM_STR);
        $stmt->bindValue(4, Sesion::IdUser(), PDO::PARAM_INT);
        $stmt->bindValue(5, Sesion::Hoy(), PDO::PARAM_STR);
        $stmt->bindValue(6, $id_nomina, PDO::PARAM_INT);
        $stmt->execute();
        $id = $this->conexion->lastInsertId();
        if ($id > 0) {
            $hoy = Sesion::Hoy();
            $idUser = Sesion::IdUser();
            Logs::guardaLog("INSERT INTO `nom_retencion_fte` (`id_empleado`,`base`,`val_ret`,`id_user_reg`,`fec_reg`,`id_nomina`) VALUES ($id_empleado, $base, $retencion, $idUser, '$hoy', $id_nomina)");
        }
        return $id > 0 ? 'si' : 'No se insertó la retención en la fuente.';
    }

    private function getIdTipoDescuentoReintegro()
    {
        try {
            $sql = "SELECT `id_tipo` FROM `nom_tipo_descuentos` 
                    WHERE `descripcion` LIKE '%REINTEGRO%' 
                       OR `descripcion` LIKE '%RETROACTIVO%' 
                       OR `descripcion` LIKE '%AJUSTE%' 
                    ORDER BY `id_tipo` ASC LIMIT 1";
            $stmt = $this->conexion->query($sql);
            $row = $stmt ? $stmt->fetch(PDO::FETCH_ASSOC) : null;
            if (!empty($row['id_tipo'])) {
                return intval($row['id_tipo']);
            }
            $sqlFallback = "SELECT `id_tipo` FROM `nom_tipo_descuentos` WHERE `id_tipo` = 3 LIMIT 1";
            $stmt2 = $this->conexion->query($sqlFallback);
            $row2 = $stmt2 ? $stmt2->fetch(PDO::FETCH_ASSOC) : null;
            return !empty($row2['id_tipo']) ? intval($row2['id_tipo']) : 3;
        } catch (PDOException $e) {
            return 3;
        }
    }

    private function registrarDescuentoReintegro($id_empleado, $id_nomina, $concepto, $valor, $fecInicio)
    {
        if ($valor <= 0) {
            return ['status' => true, 'id_dcto' => 0];
        }
        $idTipoDcto = $this->getIdTipoDescuentoReintegro();
        $hoy = Sesion::Hoy();
        $idUser = Sesion::IdUser();

        $sqlDcto = "INSERT INTO `nom_otros_descuentos`
                        (`id_empleado`, `id_tipo_dcto`, `fecha`, `fecha_fin`, `concepto`, `valor`, `estado`, `id_user_reg`, `fec_reg`)
                    VALUES (?, ?, ?, NULL, ?, ?, 1, ?, ?)";
        $stmtDcto = $this->conexion->prepare($sqlDcto);
        $stmtDcto->bindValue(1, $id_empleado, PDO::PARAM_INT);
        $stmtDcto->bindValue(2, $idTipoDcto, PDO::PARAM_INT);
        $stmtDcto->bindValue(3, $fecInicio, PDO::PARAM_STR);
        $stmtDcto->bindValue(4, $concepto, PDO::PARAM_STR);
        $stmtDcto->bindValue(5, $valor, PDO::PARAM_STR);
        $stmtDcto->bindValue(6, $idUser, PDO::PARAM_INT);
        $stmtDcto->bindValue(7, $hoy, PDO::PARAM_STR);
        $stmtDcto->execute();
        $idDcto = $this->conexion->lastInsertId();

        if ($idDcto <= 0) {
            return ['status' => false, 'msg' => 'No se pudo registrar en nom_otros_descuentos'];
        }
        Logs::guardaLog("INSERT INTO `nom_otros_descuentos` (`id_empleado`,`id_tipo_dcto`,`fecha`,`fecha_fin`,`concepto`,`valor`,`estado`,`id_user_reg`,`fec_reg`) VALUES ($id_empleado, $idTipoDcto, '$fecInicio', NULL, '$concepto', $valor, 1, $idUser, '$hoy')");

        $sqlLiq = "INSERT INTO `nom_liq_descuento`
                       (`id_dcto`, `valor`, `id_nomina`, `id_user_reg`, `fec_reg`)
                   VALUES (?, ?, ?, ?, ?)";
        $stmtLiq = $this->conexion->prepare($sqlLiq);
        $stmtLiq->bindValue(1, $idDcto, PDO::PARAM_INT);
        $stmtLiq->bindValue(2, $valor, PDO::PARAM_STR);
        $stmtLiq->bindValue(3, $id_nomina, PDO::PARAM_INT);
        $stmtLiq->bindValue(4, $idUser, PDO::PARAM_INT);
        $stmtLiq->bindValue(5, $hoy, PDO::PARAM_STR);
        $stmtLiq->execute();
        $idLiq = $this->conexion->lastInsertId();

        if ($idLiq <= 0) {
            return ['status' => false, 'msg' => 'No se pudo registrar en nom_liq_descuento'];
        }
        Logs::guardaLog("INSERT INTO `nom_liq_descuento` (`id_dcto`,`valor`,`id_nomina`,`id_user_reg`,`fec_reg`) VALUES ($idDcto, $valor, $id_nomina, $idUser, '$hoy')");

        return ['status' => true, 'id_dcto' => $idDcto];
    }

    public function addRegistroRetroactivo($array)
    {
        $ids = $array['chk_liquidacion'] ?? [];
        $tipo = intval($array['tipo'] ?? 0);
        $retroactivoId = intval($array['retroactivo'] ?? 0);
        $mpago = $array['metodo'] ?? [];
        $contratos = $array['id_contrato'] ?? [];

        if ($tipo !== 5 || empty($ids) || $retroactivoId <= 0) {
            return 'Debe seleccionar un retroactivo y al menos un empleado.';
        }

        $retro = $this->getRetroactivoById($retroactivoId);
        if (empty($retro)) {
            return 'No se encontró el retroactivo seleccionado.';
        }

        $mesNomina = !empty($array['mes']) && $array['mes'] !== '0'
            ? $array['mes']
            : date('m', strtotime($retro['fec_final'] . ' +1 month'));

        $nomina = Nomina::getIDNomina($mesNomina, 5, $retro['id_incremento']);
        if (($nomina['id_nomina'] > 0 && $nomina['estado'] >= 2) || $nomina['id_nomina'] == 0) {
            $res = Nomina::addRegistro($mesNomina, 5, $retro['id_incremento']);
            if ($res['status'] == 'si') {
                $id_nomina = $res['id'];
            } else {
                return $res['msg'];
            }
        } else {
            $id_nomina = $nomina['id_nomina'];
        }
        (new Nomina())->editRegistro([
            'id_nomina' => $id_nomina,
            'descripcion' => 'LIQUIDACIÓN RETROACTIVA DE NOMINAS DE ' . $retro['fec_inicio'] . ' A ' . $retro['fec_final'],
        ]);

        $parametros = array_column(Nomina::getParamLiq(), 'valor', 'id_concepto');
        if (empty($parametros[1]) || empty($parametros[6])) {
            return 'No se han configurado los parámetros de liquidación.';
        }

        $smmlv = floatval($parametros[1] ?? 0);
        $uvt = floatval($parametros[6] ?? 0);
        $baseBspLimite = floatval($parametros[7] ?? 0);
        $grepParam = floatval($parametros[8] ?? 0);
        $baseAlimLimite = floatval($parametros[9] ?? 0);

        $empleados = array_column((new Empleados())->getEmpleados(), null, 'id_empleado');
        $terceros_ss = (new Empleados())->getRegistro();
        $empresa = (new Usuario())->getEmpresa();
        $factor = floatval($retro['porcentaje']) / 100;
        $inserts = 0;
        $error = '';

        foreach ($ids as $id_empleado) {
            try {
                if (!isset($empleados[$id_empleado])) {
                    throw new Exception('Empleado no encontrado.');
                }

                $mensual = $this->getRetroMensualData($id_empleado, $retro);
                if (empty($mensual)) {
                    continue;
                }

                $salarioNuevo = $this->getSalarioRetroactivoEmpleado($id_empleado, $retro);
                if ($salarioNuevo <= 0) {
                    throw new Exception('No se encontró el salario actualizado para el incremento seleccionado.');
                }

                // Obtener EPS, ARL, AFP de la última liquidación
                $ultimaSS = $this->getUltimaSeguridadSocialEmpleado($id_empleado);
                if ($ultimaSS && !empty($ultimaSS['id_eps']) && !empty($ultimaSS['id_afp']) && !empty($ultimaSS['id_arl'])) {
                    $idEps = intval($ultimaSS['id_eps']);
                    $idAfp = intval($ultimaSS['id_afp']);
                    $idArl = intval($ultimaSS['id_arl']);
                    $porcentajeArl = floatval($ultimaSS['cotizacion_arl']);
                } else {
                    $filtro = array_filter($terceros_ss, function ($tercero) use ($id_empleado) {
                        return $tercero['id_empleado'] == $id_empleado;
                    });
                    $novedad = array_column($filtro, 'id_tercero', 'id_tipo');
                    if (!(isset($novedad[1]) && isset($novedad[2]) && isset($novedad[3]))) {
                        throw new Exception('No tiene registrados terceros de seguridad social.');
                    }
                    $idEps = intval($novedad[1]);
                    $idAfp = intval($novedad[2]);
                    [$idArl, $porcentajeArl] = explode('|', $novedad[3]);
                    $idArl = intval($idArl);
                    $porcentajeArl = floatval($porcentajeArl);
                }

                if (!$this->conexion->inTransaction()) {
                    $this->conexion->beginTransaction();
                }

                // Limpiar registros previos de este empleado en esta nómina para evitar duplicidades
                $tablasLimpieza = [
                    'nom_retroctivo_detalles',
                    'nom_liq_salario',
                    'nom_liq_dlab_auxt',
                    'nom_liq_segsocial_empdo',
                    'nom_liq_parafiscales',
                    'nom_liq_prestaciones_sociales',
                    'nom_retencion_fte',
                    'nom_valores_liquidacion',
                    'nom_liq_prima',
                    'nom_liq_prima_nav',
                    'nom_liq_cesantias',
                    'nom_liq_compesatorio',
                ];
                foreach ($tablasLimpieza as $tbl) {
                    $stmtClean = $this->conexion->prepare("DELETE FROM `{$tbl}` WHERE `id_nomina` = ? AND `id_empleado` = ?");
                    $stmtClean->execute([$id_nomina, $id_empleado]);
                }
                $stmtCleanBsp = $this->conexion->prepare("DELETE FROM `nom_liq_bsp` WHERE `id_nomina` = ? AND `id_empleado` = ? AND `tipo` = 'S'");
                $stmtCleanBsp->execute([$id_nomina, $id_empleado]);

                $delQueriesJoin = [
                    "DELETE `nlv` FROM `nom_liq_vac` `nlv` INNER JOIN `nom_vacaciones` `nv` ON (`nlv`.`id_vac` = `nv`.`id_vac`) WHERE `nlv`.`id_nomina` = ? AND `nv`.`id_empleado` = ?",
                    "DELETE `nli` FROM `nom_liq_incap` `nli` INNER JOIN `nom_incapacidad` `ni` ON (`nli`.`id_incapacidad` = `ni`.`id_incapacidad`) WHERE `nli`.`id_nomina` = ? AND `ni`.`id_empleado` = ?",
                    "DELETE `nll` FROM `nom_liq_licluto` `nll` INNER JOIN `nom_licencia_luto` `ll` ON (`nll`.`id_licluto` = `ll`.`id_licluto`) WHERE `nll`.`id_nomina` = ? AND `ll`.`id_empleado` = ?",
                    "DELETE `nlmp` FROM `nom_liq_licmp` `nlmp` INNER JOIN `nom_licenciasmp` `lmp` ON (`nlmp`.`id_licmp` = `lmp`.`id_licmp`) WHERE `nlmp`.`id_nomina` = ? AND `lmp`.`id_empleado` = ?",
                    "DELETE `nliv` FROM `nom_liq_indemniza_vac` `nliv` INNER JOIN `nom_indemniza_vac` `niv` ON (`nliv`.`id_indemnizacion` = `niv`.`id_indemniza`) WHERE `nliv`.`id_nomina` = ? AND `niv`.`id_empleado` = ?",
                    "DELETE `nld` FROM `nom_liq_devengado` `nld` INNER JOIN `nom_otros_devengados` `nod` ON (`nld`.`id_devengado` = `nod`.`id_devengado`) WHERE `nld`.`id_nomina` = ? AND `nod`.`id_empleado` = ?",
                ];
                foreach ($delQueriesJoin as $delQ) {
                    $stmtCleanJoin = $this->conexion->prepare($delQ);
                    $stmtCleanJoin->execute([$id_nomina, $id_empleado]);
                }

                $sqlDelLiqDcto = "DELETE `ld` FROM `nom_liq_descuento` `ld` 
                                  INNER JOIN `nom_otros_descuentos` `od` ON (`ld`.`id_dcto` = `od`.`id_dcto`) 
                                  WHERE `ld`.`id_nomina` = ? AND `od`.`id_empleado` = ? AND `od`.`concepto` LIKE '%RETROACTIVO%'";
                $stmtCleanLiqDcto = $this->conexion->prepare($sqlDelLiqDcto);
                $stmtCleanLiqDcto->execute([$id_nomina, $id_empleado]);

                $sqlDelOtroDcto = "DELETE FROM `nom_otros_descuentos` 
                                   WHERE `id_empleado` = ? AND `concepto` LIKE '%RETROACTIVO%' 
                                   AND `id_dcto` NOT IN (SELECT `id_dcto` FROM `nom_liq_descuento`)";
                $stmtCleanOtroDcto = $this->conexion->prepare($sqlDelOtroDcto);
                $stmtCleanOtroDcto->execute([$id_empleado]);

                $sqlInsRetroDet = "INSERT INTO `nom_retroctivo_detalles`
                    (`id_nomina`, `id_empleado`, `anio`, `mes`, `id_concepto`, `valor_inicial`, `valor_con_incremento`, `bandera`, `id_user_reg`, `fec_reg`)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
                $stmtInsRetroDet = $this->conexion->prepare($sqlInsRetroDet);
                $idUserLog = Sesion::IdUser();
                $hoyLog = Sesion::Hoy();

                $grepEmpleado = (isset($empleados[$id_empleado]['representacion']) && intval($empleados[$id_empleado]['representacion']) === 1) ? $grepParam : 0;
                $reintegroAuxTrans = 0;
                $reintegroAuxAlim = 0;
                $reintegroBsp = 0;
                $mayorValorBsp = 0;

                $totales = [
                    'dias' => 0,
                    'laborado' => 0,
                    'compensa' => 0,
                    'dias_compensa' => 0,
                    'horas_ext' => 0,
                    'grep' => 0,
                    'vac' => 0,
                    'prima_vac' => 0,
                    'bon_recrea' => 0,
                    'prima_serv' => 0,
                    'prima_nav' => 0,
                    'cesantias' => 0,
                    'icesantias' => 0,
                    'incap' => 0,
                    'lic' => 0,
                    'indem' => 0,
                    'odev' => 0,
                    'salud_emp' => 0,
                    'pension_emp' => 0,
                    'solidaridad' => 0,
                    'salud_empresa' => 0,
                    'pension_empresa' => 0,
                    'riesgos' => 0,
                    'sena' => 0,
                    'icbf' => 0,
                    'comfam' => 0,
                    'ret_base' => 0,
                    'retencion' => 0,
                    'prest_vac' => 0,
                    'prest_ces' => 0,
                    'prest_ices' => 0,
                    'prest_prima' => 0,
                    'prest_prima_vac' => 0,
                    'prest_prima_nav' => 0,
                    'prest_bon_recrea' => 0,
                    'neto' => 0,
                ];

                $itemsVac = [];
                $itemPriServ = null;
                $itemPriNav = null;
                $itemCes = null;
                $itemsIncap = [];
                $itemsIncapDias = [];
                $itemsLicLuto = [];
                $itemsLicLutoDias = [];
                $itemsLicMp = [];
                $itemsLicMpDias = [];
                $itemsIndem = [];
                $itemsIndemDias = [];
                $itemsOdev = [];

                foreach ($mensual as $row) {
                    $salBaseAnt = floatval($row['sal_base_ant'] ?? 0);
                    $oldSalary = floatval($row['dias_laborados']) > 0
                        ? Valores::Redondear((floatval($row['val_laborado']) * 30) / max(1, floatval($row['dias_laborados'])))
                        : ($salBaseAnt > 0 ? $salBaseAnt : Valores::Redondear($salarioNuevo / (1 + $factor)));
                    $factorFila = $oldSalary > 0 ? ($salarioNuevo / $oldSalary) : (1 + $factor);

                    $registraDetalle = function ($idConcepto, $valIni, $valInc, $bandera = 0) use ($stmtInsRetroDet, $id_nomina, $id_empleado, $row, $idUserLog, $hoyLog) {
                        $valIni = Valores::Redondear(floatval($valIni), 2);
                        $valInc = Valores::Redondear(floatval($valInc), 2);
                        if (abs($valIni) > 0.001 || abs($valInc) > 0.001) {
                            $stmtInsRetroDet->execute([
                                $id_nomina,
                                $id_empleado,
                                $row['vigencia'],
                                str_pad($row['mes'], 2, '0', STR_PAD_LEFT),
                                $idConcepto,
                                $valIni,
                                $valInc,
                                $bandera,
                                $idUserLog,
                                $hoyLog,
                            ]);
                        }
                    };

                    // 1. Sueldo básico (id_concepto 1)
                    $valLabIni = floatval($row['val_laborado']);
                    $diffLaborado = Valores::Redondear($valLabIni * ($factorFila - 1));
                    $valLabInc = $valLabIni + $diffLaborado;
                    $registraDetalle(1, $valLabIni, $valLabInc);

                    // Compensatorios (liquidados en nóminas tipo PS)
                    $valCompPag = floatval($row['val_compensa_pagada'] ?? 0);
                    $diffComp = 0;
                    if ($valCompPag > 0) {
                        $diffComp = Valores::Redondear($valCompPag * ($factorFila - 1));
                        $valCompInc = $valCompPag + $diffComp;
                        $registraDetalle(1, $valCompPag, $valCompInc);
                        $totales['compensa'] += $diffComp;
                        $totales['dias_compensa'] += intval($row['dias_compensa_pagada'] ?? 0);
                    }

                    // 4. Horas extras (id_concepto 4)
                    $valHorasIni = floatval($row['horas_ext']);
                    $diffHoras = Valores::Redondear($valHorasIni * ($factorFila - 1));
                    $valHorasInc = $valHorasIni + $diffHoras;
                    $registraDetalle(4, $valHorasIni, $valHorasInc);

                    // 2. Auxilio de transporte (id_concepto 2)
                    $auxTransPagado = floatval($row['val_aux_trans'] ?? 0);
                    $reintegroAuxTransMes = 0;
                    $auxTransNuevo = $auxTransPagado;
                    if ($auxTransPagado > 0) {
                        if ($salarioNuevo > ($smmlv * 2)) {
                            $auxTransNuevo = 0.00;
                            $reintegroAuxTransMes = $auxTransPagado;
                            $reintegroAuxTrans += $reintegroAuxTransMes;
                        }
                        $registraDetalle(2, $auxTransPagado, $auxTransNuevo);
                    }

                    // 3. Auxilio de alimentación (id_concepto 3)
                    $auxAlimPagado = floatval($row['val_aux_alim'] ?? 0);
                    $reintegroAuxAlimMes = 0;
                    $auxAlimNuevo = $auxAlimPagado;
                    if ($auxAlimPagado > 0) {
                        if ($baseAlimLimite > 0 && $salarioNuevo > $baseAlimLimite) {
                            $auxAlimNuevo = 0.00;
                            $reintegroAuxAlimMes = $auxAlimPagado;
                            $reintegroAuxAlim += $reintegroAuxAlimMes;
                        }
                        $registraDetalle(3, $auxAlimPagado, $auxAlimNuevo);
                    }

                    // 5. BSP (id_concepto 5)
                    $bspPagada = floatval($row['val_bsp_pagada'] ?? 0);
                    $reintegroBspMes = 0;
                    $mayorValorBspMes = 0;
                    $bspNueva = $bspPagada;
                    if ($bspPagada > 0) {
                        $baseBspAnt = $oldSalary + $grepEmpleado;
                        $baseBspNueva = $salarioNuevo + $grepEmpleado;

                        if ($baseBspLimite > 0 && $baseBspAnt <= $baseBspLimite && $baseBspNueva > $baseBspLimite) {
                            $bspNueva = Valores::Redondear($baseBspNueva * 0.38);
                            $diffBsp = $bspNueva - $bspPagada;
                            if ($diffBsp < 0) {
                                $reintegroBspMes = abs($diffBsp);
                                $reintegroBsp += $reintegroBspMes;
                            } else {
                                $mayorValorBspMes = $diffBsp;
                                $mayorValorBsp += $mayorValorBspMes;
                            }
                        } else {
                            $diffBsp = Valores::Redondear($bspPagada * ($factorFila - 1));
                            $bspNueva = $bspPagada + $diffBsp;
                            if ($diffBsp > 0) {
                                $mayorValorBspMes = $diffBsp;
                                $mayorValorBsp += $mayorValorBspMes;
                            }
                        }
                        $registraDetalle(5, $bspPagada, $bspNueva);
                    }

                    // 11. Gastos de representación (id_concepto 11)
                    $valGrepIni = floatval($row['g_representa'] ?? 0);
                    $diffGrep = 0;
                    if ($valGrepIni > 0) {
                        $diffGrep = Valores::Redondear($valGrepIni * ($factorFila - 1));
                        $registraDetalle(11, $valGrepIni, $valGrepIni + $diffGrep);
                        $totales['grep'] += $diffGrep;
                    }

                    // 6, 7, 8. Vacaciones pagadas directas (id_concepto 6, 7, 8)
                    $valVacPag = floatval($row['val_vac_pagada'] ?? 0);
                    $diffVacPag = 0;
                    if ($valVacPag > 0) {
                        $diffVacPag = Valores::Redondear($valVacPag * ($factorFila - 1));
                        $registraDetalle(6, $valVacPag, $valVacPag + $diffVacPag);
                        $totales['vac'] += $diffVacPag;
                    }
                    $valPriVacPag = floatval($row['val_prima_vac_pagada'] ?? 0);
                    $diffPriVacPag = 0;
                    if ($valPriVacPag > 0) {
                        $diffPriVacPag = Valores::Redondear($valPriVacPag * ($factorFila - 1));
                        $registraDetalle(7, $valPriVacPag, $valPriVacPag + $diffPriVacPag);
                        $totales['prima_vac'] += $diffPriVacPag;
                    }
                    $valBonRecPag = floatval($row['val_bon_recrea_pagada'] ?? 0);
                    $diffBonRecPag = 0;
                    if ($valBonRecPag > 0) {
                        $diffBonRecPag = Valores::Redondear($valBonRecPag * ($factorFila - 1));
                        $registraDetalle(8, $valBonRecPag, $valBonRecPag + $diffBonRecPag);
                        $totales['bon_recrea'] += $diffBonRecPag;
                    }

                    $idVac = intval($row['id_vac_pagada'] ?? 0);
                    if ($idVac > 0 && ($diffVacPag > 0 || $diffPriVacPag > 0 || $diffBonRecPag > 0)) {
                        if (!isset($itemsVac[$idVac])) {
                            $itemsVac[$idVac] = [
                                'id_vac' => $idVac,
                                'dias_liqs' => intval($row['dias_vac_pagada'] ?? 0),
                                'val_liq' => 0,
                                'val_prima_vac' => 0,
                                'val_bon_recrea' => 0,
                            ];
                        }
                        $itemsVac[$idVac]['val_liq'] += $diffVacPag;
                        $itemsVac[$idVac]['val_prima_vac'] += $diffPriVacPag;
                        $itemsVac[$idVac]['val_bon_recrea'] += $diffBonRecPag;
                    }

                    // 9. Incapacidad pagada directa (id_concepto 9)
                    $valIncapPag = floatval($row['val_incap_pagada'] ?? 0);
                    $diffIncap = 0;
                    if ($valIncapPag > 0) {
                        $diffIncap = Valores::Redondear($valIncapPag * ($factorFila - 1));
                        $registraDetalle(9, $valIncapPag, $valIncapPag + $diffIncap);
                        $totales['incap'] += $diffIncap;
                        $idIncap = intval($row['id_incap_pagada'] ?? 0);
                        if ($idIncap > 0 && $diffIncap > 0) {
                            $itemsIncap[$idIncap] = ($itemsIncap[$idIncap] ?? 0) + $diffIncap;
                            $itemsIncapDias[$idIncap] = intval($row['dias_incap_pagada'] ?? 0);
                        }
                    }

                    // 10. Licencias remuneradas directas (id_concepto 10)
                    $valLicLuto = floatval($row['val_licluto_pagada'] ?? 0);
                    $diffLicLuto = 0;
                    if ($valLicLuto > 0) {
                        $diffLicLuto = Valores::Redondear($valLicLuto * ($factorFila - 1));
                        $idLicLuto = intval($row['id_licluto_pagada'] ?? 0);
                        if ($idLicLuto > 0 && $diffLicLuto > 0) {
                            $itemsLicLuto[$idLicLuto] = ($itemsLicLuto[$idLicLuto] ?? 0) + $diffLicLuto;
                            $itemsLicLutoDias[$idLicLuto] = intval($row['dias_licluto_pagada'] ?? 0);
                        }
                    }

                    $valLicMp = floatval($row['val_licmp_pagada'] ?? 0);
                    $diffLicMp = 0;
                    if ($valLicMp > 0) {
                        $diffLicMp = Valores::Redondear($valLicMp * ($factorFila - 1));
                        $idLicMp = intval($row['id_licmp_pagada'] ?? 0);
                        if ($idLicMp > 0 && $diffLicMp > 0) {
                            $itemsLicMp[$idLicMp] = ($itemsLicMp[$idLicMp] ?? 0) + $diffLicMp;
                            $itemsLicMpDias[$idLicMp] = intval($row['dias_licmp_pagada'] ?? 0);
                        }
                    }

                    $valLicPag = $valLicLuto + $valLicMp;
                    $diffLic = $diffLicLuto + $diffLicMp;
                    if ($valLicPag > 0) {
                        $registraDetalle(10, $valLicPag, $valLicPag + $diffLic);
                        $totales['lic'] += $diffLic;
                    }

                    // 12. Indemnización vacaciones directa (id_concepto 12)
                    $valIndemPag = floatval($row['val_indemniza_pagada'] ?? 0);
                    $diffIndem = 0;
                    if ($valIndemPag > 0) {
                        $diffIndem = Valores::Redondear($valIndemPag * ($factorFila - 1));
                        $registraDetalle(12, $valIndemPag, $valIndemPag + $diffIndem);
                        $totales['indem'] += $diffIndem;
                        $idIndem = intval($row['id_indem_pagada'] ?? 0);
                        if ($idIndem > 0 && $diffIndem > 0) {
                            $itemsIndem[$idIndem] = ($itemsIndem[$idIndem] ?? 0) + $diffIndem;
                            $itemsIndemDias[$idIndem] = intval($row['dias_indem_pagada'] ?? 0);
                        }
                    }

                    // 22, 23. Cesantías directas (id_concepto 22, 23)
                    $valCesPag = floatval($row['val_cesantias_pagada'] ?? 0);
                    $diffCesPag = 0;
                    if ($valCesPag > 0) {
                        $diffCesPag = Valores::Redondear($valCesPag * ($factorFila - 1));
                        $registraDetalle(22, $valCesPag, $valCesPag + $diffCesPag);
                        $totales['cesantias'] += $diffCesPag;
                    }
                    $valIcesPag = floatval($row['val_icesantias_pagada'] ?? 0);
                    $diffIcesPag = 0;
                    if ($valIcesPag > 0) {
                        $diffIcesPag = Valores::Redondear($valIcesPag * ($factorFila - 1));
                        $registraDetalle(23, $valIcesPag, $valIcesPag + $diffIcesPag);
                        $totales['icesantias'] += $diffIcesPag;
                    }
                    if ($diffCesPag > 0 || $diffIcesPag > 0) {
                        if ($itemCes === null) {
                            $itemCes = [
                                'cant_dias' => $row['dias_cesantias'] ?? '',
                                'corte' => !empty($row['corte_cesantias']) ? $row['corte_cesantias'] : $retro['fec_final'],
                            ];
                        }
                    }

                    // 24, 25. Primas directas (id_concepto 24, 25)
                    $valPriServPag = floatval($row['val_prima_servicio_pagada'] ?? 0);
                    $diffPriServPag = 0;
                    if ($valPriServPag > 0) {
                        $diffPriServPag = Valores::Redondear($valPriServPag * ($factorFila - 1));
                        $registraDetalle(24, $valPriServPag, $valPriServPag + $diffPriServPag);
                        $totales['prima_serv'] += $diffPriServPag;
                        if ($diffPriServPag > 0 && $itemPriServ === null) {
                            $itemPriServ = [
                                'cant_dias' => $row['dias_pri_serv'] ?? '',
                                'corte' => !empty($row['corte_pri_serv']) ? $row['corte_pri_serv'] : $retro['fec_final'],
                                'periodo' => !empty($row['periodo_pri_serv']) ? $row['periodo_pri_serv'] : '1',
                            ];
                        }
                    }
                    $valPriNavPag = floatval($row['val_prima_navidad_pagada'] ?? 0);
                    $diffPriNavPag = 0;
                    if ($valPriNavPag > 0) {
                        $diffPriNavPag = Valores::Redondear($valPriNavPag * ($factorFila - 1));
                        $registraDetalle(25, $valPriNavPag, $valPriNavPag + $diffPriNavPag);
                        $totales['prima_nav'] += $diffPriNavPag;
                        if ($diffPriNavPag > 0 && $itemPriNav === null) {
                            $itemPriNav = [
                                'cant_dias' => $row['dias_pri_nav'] ?? '',
                                'corte' => !empty($row['corte_pri_nav']) ? $row['corte_pri_nav'] : $retro['fec_final'],
                                'periodo' => !empty($row['periodo_pri_nav']) ? $row['periodo_pri_nav'] : '1',
                            ];
                        }
                    }

                    // 29. Otros devengados directos (id_concepto 29)
                    $valOdevPag = floatval($row['val_odev_pagada'] ?? 0);
                    $diffOdev = 0;
                    if ($valOdevPag > 0) {
                        $diffOdev = Valores::Redondear($valOdevPag * ($factorFila - 1));
                        $registraDetalle(29, $valOdevPag, $valOdevPag + $diffOdev);
                        $totales['odev'] += $diffOdev;
                        $idOdev = intval($row['id_odev_pagada'] ?? 0);
                        if ($idOdev > 0 && $diffOdev > 0) {
                            $itemsOdev[$idOdev] = ($itemsOdev[$idOdev] ?? 0) + $diffOdev;
                        }
                    }

                    // Seguridad Social (13, 14, 15, 21, 26, 27)
                    $aporteBase = max(floatval($row['aporte_salud_emp']), floatval($row['aporte_pension_emp']));
                    $valBaseLab = floatval($row['val_laborado']) + floatval($row['horas_ext']);
                    $diasFila = intval($row['dias_laborados']);

                    if (($aporteBase <= 0 && $valBaseLab <= 0) || $diasFila <= 0) {
                        $ssNueva = [
                            'aporte_salud_emp' => 0,
                            'aporte_pension_emp' => 0,
                            'aporte_solidaridad_pensional' => 0,
                            'aporte_salud_empresa' => 0,
                            'aporte_pension_empresa' => 0,
                            'aporte_rieslab' => 0,
                        ];
                    } else {
                        $ibcAnterior = $aporteBase > 0 ? Valores::Redondear($aporteBase / 0.04, 1) : $valBaseLab;
                        $ibcNuevo = $ibcAnterior * $factorFila;

                        $ssNueva = $this->calcularSeguridadSocialRetro(
                            $ibcNuevo,
                            $smmlv,
                            intval($empleados[$id_empleado]['tipo_empleado']),
                            intval($empleados[$id_empleado]['subtipo_empleado']),
                            $diasFila,
                            floatval($porcentajeArl)
                        );
                    }

                    $registraDetalle(13, $row['aporte_salud_emp'], $ssNueva['aporte_salud_emp']);
                    $registraDetalle(14, $row['aporte_pension_emp'], $ssNueva['aporte_pension_emp']);
                    $registraDetalle(15, $row['aporte_solidaridad_pensional'], $ssNueva['aporte_solidaridad_pensional']);
                    $registraDetalle(21, $row['aporte_rieslab'], $ssNueva['aporte_rieslab']);
                    $registraDetalle(26, $row['aporte_salud_empresa'], $ssNueva['aporte_salud_empresa']);
                    $registraDetalle(27, $row['aporte_pension_empresa'], $ssNueva['aporte_pension_empresa']);

                    // 28. Parafiscales (id_concepto 28)
                    $parafAnt = floatval($row['val_sena']) + floatval($row['val_icbf']) + floatval($row['val_comfam']);
                    if ($parafAnt > 0) {
                        $parafNue = Valores::Redondear($parafAnt * $factorFila, 100);
                        $registraDetalle(28, $parafAnt, $parafNue);
                    }

                    // 19. Retención en la fuente (id_concepto 19)
                    $baseRetAnterior = floatval($row['base_ret'] ?? 0);
                    $valRetAnterior = floatval($row['val_ret'] ?? 0);

                    if ($baseRetAnterior > 0) {
                        $baseRetNueva = Valores::Redondear($baseRetAnterior * $factorFila);
                    } else if ($valBaseLab > 0 && $diasFila > 0) {
                        // Si antes no retenía y no tenía base registrada, estimar base depurada del mes
                        $ingresoMesNuevo = (floatval($row['val_laborado']) + $diffLaborado) + ($valCompPag + $diffComp) + (floatval($row['horas_ext']) + $diffHoras);
                        $incrMesNuevo = $ssNueva['aporte_salud_emp'] + $ssNueva['aporte_pension_emp'] + $ssNueva['aporte_solidaridad_pensional'];
                        $baseDepurada = max(0, $ingresoMesNuevo - $incrMesNuevo) * 0.75;
                        $baseRetNueva = Valores::Redondear($baseDepurada);
                    } else {
                        $baseRetNueva = 0;
                    }

                    $retNueva = $baseRetNueva > 0 ? $this->calcularRetencionBase($baseRetNueva, $uvt) : 0;
                    $diffRet = max(0, $retNueva - $valRetAnterior);
                    $diffBaseRet = max(0, $baseRetNueva - $baseRetAnterior);
                    $registraDetalle(19, $valRetAnterior, $retNueva);

                    // 30. Reintegros / otros descuentos (id_concepto 30)
                    $reintegroMes = $reintegroAuxTransMes + $reintegroAuxAlimMes + $reintegroBspMes;
                    if ($reintegroMes > 0) {
                        $registraDetalle(30, 0.00, $reintegroMes);
                    }

                    // 20. Neto del mes (id_concepto 20)
                    $diffSalud = max(0, $ssNueva['aporte_salud_emp'] - floatval($row['aporte_salud_emp']));
                    $diffPension = max(0, $ssNueva['aporte_pension_emp'] - floatval($row['aporte_pension_emp']));
                    $diffSolidaridad = max(0, $ssNueva['aporte_solidaridad_pensional'] - floatval($row['aporte_solidaridad_pensional']));

                    $devengadoAdicMes = $diffLaborado + $diffComp + $diffHoras + $mayorValorBspMes + $diffVacPag + $diffPriVacPag + $diffBonRecPag + $diffPriServPag + $diffPriNavPag + $diffCesPag + $diffIcesPag + $diffIncap + $diffLic + $diffGrep + $diffIndem + $diffOdev;
                    $deduccionAdicMes = $diffSalud + $diffPension + $diffSolidaridad + $diffRet + $reintegroMes;
                    $diffNetoMes = $devengadoAdicMes - $deduccionAdicMes;
                    $netoAnt = floatval($row['val_neto_pagado']);
                    $netoNue = max(0, $netoAnt + $diffNetoMes);
                    $registraDetalle(20, $netoAnt, $netoNue);

                    // Acumulaciones globales para nom_liq_*
                    $totales['dias'] += intval($row['dias_laborados']);
                    $totales['laborado'] += $diffLaborado;
                    $totales['horas_ext'] += $diffHoras;
                    $totales['salud_emp'] += $diffSalud;
                    $totales['pension_emp'] += $diffPension;
                    $totales['solidaridad'] += $diffSolidaridad;
                    $totales['salud_empresa'] += max(0, $ssNueva['aporte_salud_empresa'] - floatval($row['aporte_salud_empresa']));
                    $totales['pension_empresa'] += max(0, $ssNueva['aporte_pension_empresa'] - floatval($row['aporte_pension_empresa']));
                    $totales['riesgos'] += max(0, $ssNueva['aporte_rieslab'] - floatval($row['aporte_rieslab']));
                    $totales['sena'] += Valores::Redondear(floatval($row['val_sena']) * ($factorFila - 1), 100);
                    $totales['icbf'] += Valores::Redondear(floatval($row['val_icbf']) * ($factorFila - 1), 100);
                    $totales['comfam'] += Valores::Redondear(floatval($row['val_comfam']) * ($factorFila - 1), 100);
                    $totales['prest_vac'] += Valores::Redondear(floatval($row['val_vacacion']) * ($factorFila - 1));
                    $totales['prest_ces'] += Valores::Redondear(floatval($row['val_cesantia']) * ($factorFila - 1)) + $diffCesPag;
                    $totales['prest_ices'] += Valores::Redondear(floatval($row['val_interes_cesantia']) * ($factorFila - 1)) + $diffIcesPag;
                    $totales['prest_prima'] += Valores::Redondear(floatval($row['val_prima']) * ($factorFila - 1)) + $diffPriServPag;
                    $totales['prest_prima_vac'] += Valores::Redondear(floatval($row['val_prima_vac']) * ($factorFila - 1)) + $diffPriVacPag;
                    $totales['prest_prima_nav'] += Valores::Redondear(floatval($row['val_prima_nav']) * ($factorFila - 1)) + $diffPriNavPag;
                    $totales['prest_bon_recrea'] += Valores::Redondear(floatval($row['val_bonifica_recrea']) * ($factorFila - 1)) + $diffBonRecPag;
                    $totales['ret_base'] += $diffBaseRet;
                    $totales['retencion'] += $diffRet;
                }

                if ($mayorValorBsp > 0) {
                    $dataBsp = [
                        'id_empleado' => $id_empleado,
                        'valor' => $mayorValorBsp,
                        'corte' => $retro['fec_final'],
                        'tipo' => 'S',
                        'id_nomina' => $id_nomina,
                    ];
                    $resBsp = (new Bsp($this->conexion))->addRegistro($dataBsp);
                    if ($resBsp != 'si') {
                        throw new Exception('Mayor valor BSP: ' . $resBsp);
                    }
                }

                // Control de retención en la fuente y reintegros para no generar netos negativos
                // Solo se descuenta hasta que el neto sea 0. Los excedentes no descontados se registran en nom_retroctivo_detalles con bandera = 1
                $totalDevengadoLiq = $totales['laborado'] + $totales['grep'] + $totales['horas_ext'] + $mayorValorBsp
                    + $totales['compensa']
                    + $totales['vac'] + $totales['prima_vac'] + $totales['bon_recrea']
                    + $totales['prima_serv'] + $totales['prima_nav']
                    + $totales['cesantias'] + $totales['icesantias']
                    + $totales['incap'] + $totales['lic'] + $totales['indem'] + $totales['odev'];

                $anioRetro = date('Y', strtotime($retro['fec_final']));
                $mesRetro = date('m', strtotime($retro['fec_final']));

                // Deducciones de seguridad social obligatorias del empleado (salud, pensión, fondo de solidaridad)
                $totalDeduccionesSS = $totales['salud_emp'] + $totales['pension_emp'] + $totales['solidaridad'];

                // Control de retención en la fuente (id_concepto 19):
                // Para evitar generar netos negativos, solo se descuenta retención hasta donde alcance el saldo
                // disponible tras seguridad social. El excedente no descontado se deja como pendiente en nom_retroctivo_detalles con bandera = 1.
                $saldoDisponibleRetencion = max(0, $totalDevengadoLiq - $totalDeduccionesSS);
                $valTotalRetencion = floatval($totales['retencion']);
                $valRetADescontar = min($valTotalRetencion, $saldoDisponibleRetencion);
                $valRetPendiente = $valTotalRetencion - $valRetADescontar;

                if ($valRetPendiente > 0) {
                    $stmtInsRetroDet->execute([
                        $id_nomina,
                        $id_empleado,
                        $anioRetro,
                        $mesRetro,
                        19, // id_concepto 19 = RETENCIÓN EN LA FUENTE
                        $valTotalRetencion,
                        $valRetPendiente,
                        1, // bandera 1 = retención pendiente
                        $idUserLog,
                        $hoyLog,
                    ]);
                }

                // Ajustar base y valor de retención a lo que efectivamente se descuenta en esta nómina
                if ($valTotalRetencion > 0 && $valRetADescontar > 0) {
                    $totales['ret_base'] = Valores::Redondear($totales['ret_base'] * ($valRetADescontar / $valTotalRetencion));
                } else {
                    $totales['ret_base'] = 0;
                }
                $totales['retencion'] = $valRetADescontar;

                $totalDeduccionesLey = $totalDeduccionesSS + $totales['retencion'];
                $saldoDisponibleDcto = max(0, $totalDevengadoLiq - $totalDeduccionesLey);
                $totalDescuentosAplicados = 0;

                $reintegros = [];
                if ($reintegroAuxTrans > 0) {
                    $reintegros[] = [
                        'id_concepto' => 2,
                        'concepto' => 'REINTEGRO AUXILIO TRANSPORTE - LIQ. RETROACTIVO ' . $retro['fec_inicio'] . ' A ' . $retro['fec_final'],
                        'valor' => $reintegroAuxTrans,
                    ];
                }
                if ($reintegroAuxAlim > 0) {
                    $reintegros[] = [
                        'id_concepto' => 3,
                        'concepto' => 'REINTEGRO AUXILIO ALIMENTACION - LIQ. RETROACTIVO ' . $retro['fec_inicio'] . ' A ' . $retro['fec_final'],
                        'valor' => $reintegroAuxAlim,
                    ];
                }
                if ($reintegroBsp > 0) {
                    $reintegros[] = [
                        'id_concepto' => 5,
                        'concepto' => 'REINTEGRO EXCESO BSP (53% A 38%) - LIQ. RETROACTIVO ' . $retro['fec_inicio'] . ' A ' . $retro['fec_final'],
                        'valor' => $reintegroBsp,
                    ];
                }

                foreach ($reintegros as $r) {
                    $valTotalReintegro = floatval($r['valor']);
                    $valADescontar = min($valTotalReintegro, $saldoDisponibleDcto);
                    $valPendiente = $valTotalReintegro - $valADescontar;

                    if ($valADescontar > 0) {
                        $resDcto = $this->registrarDescuentoReintegro(
                            $id_empleado,
                            $id_nomina,
                            $r['concepto'],
                            $valADescontar,
                            $retro['fec_inicio']
                        );
                        if (!$resDcto['status']) {
                            throw new Exception('Descuento reintegro: ' . $resDcto['msg']);
                        }
                        $totalDescuentosAplicados += $valADescontar;
                        $saldoDisponibleDcto -= $valADescontar;
                    }

                    if ($valPendiente > 0) {
                        $stmtInsRetroDet->execute([
                            $id_nomina,
                            $id_empleado,
                            $anioRetro,
                            $mesRetro,
                            $r['id_concepto'],
                            $valTotalReintegro,
                            $valPendiente,
                            1, // bandera 1 = descuento pendiente
                            $idUserLog,
                            $hoyLog,
                        ]);
                    }
                }

                // Deducir del valor neto solo lo efectivamente descontado
                $totales['neto'] = max(0, $totalDevengadoLiq - ($totalDeduccionesLey + $totalDescuentosAplicados));

                $resLab = $this->LiquidaLaborado([
                    'id_empleado' => $id_empleado,
                    'dias_laborados' => max(0, intval($totales['dias'])),
                    'val_laborado' => $totales['laborado'],
                    'val_aux_trans' => 0,
                    'val_aux_alim' => 0,
                    'val_grep' => $totales['grep'],
                    'val_horas_ex' => $totales['horas_ext'],
                    'id_nomina' => $id_nomina,
                ]);
                if (!$resLab['insert']) {
                    throw new Exception('Laborado: ' . $resLab['msg']);
                }

                if ($totales['compensa'] > 0) {
                    $resComp = (new Compesatorios($this->conexion))->addRegistro([
                        'id_empleado' => $id_empleado,
                        'val_compensa' => $totales['compensa'],
                        'dias' => $totales['dias_compensa'],
                        'id_nomina' => $id_nomina,
                    ]);
                    if ($resComp != 'si') {
                        throw new Exception('Compensatorio: ' . $resComp);
                    }
                }

                // Inserción de devengados de prestaciones y novedades para que aparezcan en Detalles
                if (!empty($itemsVac)) {
                    $stmtInsVac = $this->conexion->prepare("INSERT INTO `nom_liq_vac`
                        (`id_vac`, `dias_liqs`, `val_liq`, `val_prima_vac`, `val_bon_recrea`, `fec_reg`, `id_nomina`, `estado`, `tipo`, `id_user_reg`)
                        VALUES (?, ?, ?, ?, ?, ?, ?, 1, 'S', ?)");
                    foreach ($itemsVac as $item) {
                        $stmtInsVac->execute([
                            $item['id_vac'],
                            $item['dias_liqs'],
                            $item['val_liq'],
                            $item['val_prima_vac'],
                            $item['val_bon_recrea'],
                            $hoyLog,
                            $id_nomina,
                            $idUserLog,
                        ]);
                    }
                }

                if ($totales['prima_serv'] > 0) {
                    $stmtInsPri = $this->conexion->prepare("INSERT INTO `nom_liq_prima`
                        (`id_empleado`, `cant_dias`, `val_liq_ps`, `periodo`, `corte`, `anio`, `fec_reg`, `id_nomina`, `estado`, `tipo`, `id_user_reg`)
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, 1, 'S', ?)");
                    $stmtInsPri->execute([
                        $id_empleado,
                        $itemPriServ['cant_dias'] ?? '',
                        $totales['prima_serv'],
                        $itemPriServ['periodo'] ?? '1',
                        $itemPriServ['corte'] ?? $retro['fec_final'],
                        $anioRetro,
                        $hoyLog,
                        $id_nomina,
                        $idUserLog,
                    ]);
                }

                if ($totales['prima_nav'] > 0) {
                    $stmtInsPriNav = $this->conexion->prepare("INSERT INTO `nom_liq_prima_nav`
                        (`id_empleado`, `cant_dias`, `val_liq_pv`, `periodo`, `corte`, `anio`, `fec_reg`, `id_nomina`, `estado`, `tipo`, `id_user_reg`)
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, 1, 'S', ?)");
                    $stmtInsPriNav->execute([
                        $id_empleado,
                        $itemPriNav['cant_dias'] ?? '',
                        $totales['prima_nav'],
                        $itemPriNav['periodo'] ?? '1',
                        $itemPriNav['corte'] ?? $retro['fec_final'],
                        $anioRetro,
                        $hoyLog,
                        $id_nomina,
                        $idUserLog,
                    ]);
                }

                if ($totales['cesantias'] > 0 || $totales['icesantias'] > 0) {
                    $stmtInsCes = $this->conexion->prepare("INSERT INTO `nom_liq_cesantias`
                        (`id_empleado`, `cant_dias`, `val_cesantias`, `val_icesantias`, `porcentaje_interes`, `corte`, `fec_reg`, `id_nomina`, `estado`, `tipo`, `id_user_reg`)
                        VALUES (?, ?, ?, ?, 12.00, ?, ?, ?, 1, 'S', ?)");
                    $stmtInsCes->execute([
                        $id_empleado,
                        $itemCes['cant_dias'] ?? '',
                        $totales['cesantias'],
                        $totales['icesantias'],
                        $itemCes['corte'] ?? $retro['fec_final'],
                        $hoyLog,
                        $id_nomina,
                        $idUserLog,
                    ]);
                }

                if (!empty($itemsIncap)) {
                    $stmtInsIncap = $this->conexion->prepare("INSERT INTO `nom_liq_incap`
                        (`id_incapacidad`, `dias_liq`, `pago_empresa`, `pago_eps`, `pago_arl`, `fec_reg`, `id_nomina`, `estado`, `id_user_reg`)
                        VALUES (?, ?, ?, 0, 0, ?, ?, 1, ?)");
                    foreach ($itemsIncap as $idIncap => $valDiff) {
                        $stmtInsIncap->execute([
                            $idIncap,
                            $itemsIncapDias[$idIncap] ?? 0,
                            $valDiff,
                            $hoyLog,
                            $id_nomina,
                            $idUserLog,
                        ]);
                    }
                }

                if (!empty($itemsLicLuto)) {
                    $stmtInsLicLuto = $this->conexion->prepare("INSERT INTO `nom_liq_licluto`
                        (`id_licluto`, `dias_licluto`, `val_liq`, `fec_reg`, `id_nomina`, `estado`, `id_user_reg`)
                        VALUES (?, ?, ?, ?, ?, 1, ?)");
                    foreach ($itemsLicLuto as $idLicLuto => $valDiff) {
                        $stmtInsLicLuto->execute([
                            $idLicLuto,
                            $itemsLicLutoDias[$idLicLuto] ?? 0,
                            $valDiff,
                            $hoyLog,
                            $id_nomina,
                            $idUserLog,
                        ]);
                    }
                }

                if (!empty($itemsLicMp)) {
                    $stmtInsLicMp = $this->conexion->prepare("INSERT INTO `nom_liq_licmp`
                        (`id_licmp`, `dias_liqs`, `val_liq`, `val_dialc`, `fec_reg`, `id_nomina`, `estado`, `tipo`, `id_user_reg`)
                        VALUES (?, ?, ?, 0, ?, ?, 1, 'S', ?)");
                    foreach ($itemsLicMp as $idLicMp => $valDiff) {
                        $stmtInsLicMp->execute([
                            $idLicMp,
                            $itemsLicMpDias[$idLicMp] ?? 0,
                            $valDiff,
                            $hoyLog,
                            $id_nomina,
                            $idUserLog,
                        ]);
                    }
                }

                if (!empty($itemsIndem)) {
                    $stmtInsIndem = $this->conexion->prepare("INSERT INTO `nom_liq_indemniza_vac`
                        (`id_indemnizacion`, `dias_liq`, `val_liq`, `fec_reg`, `id_nomina`, `estado`, `id_user_reg`)
                        VALUES (?, ?, ?, ?, ?, 1, ?)");
                    foreach ($itemsIndem as $idIndem => $valDiff) {
                        $stmtInsIndem->execute([
                            $idIndem,
                            $itemsIndemDias[$idIndem] ?? 0,
                            $valDiff,
                            $hoyLog,
                            $id_nomina,
                            $idUserLog,
                        ]);
                    }
                }

                if (!empty($itemsOdev)) {
                    $stmtInsOdev = $this->conexion->prepare("INSERT INTO `nom_liq_devengado`
                        (`id_devengado`, `valor`, `fec_reg`, `id_nomina`, `estado`, `id_user_reg`)
                        VALUES (?, ?, ?, ?, 1, ?)");
                    foreach ($itemsOdev as $idOdev => $valDiff) {
                        $stmtInsOdev->execute([
                            $idOdev,
                            $valDiff,
                            $hoyLog,
                            $id_nomina,
                            $idUserLog,
                        ]);
                    }
                }

                $resSs = (new Seguridad_Social($this->conexion))->addRegistroLiq([
                    'id_empleado' => $id_empleado,
                    'id_eps' => $idEps,
                    'id_arl' => $idArl,
                    'id_afp' => $idAfp,
                    'aporte_salud_emp' => $totales['salud_emp'],
                    'aporte_pension_emp' => $totales['pension_emp'],
                    'aporte_solidaridad_pensional' => $totales['solidaridad'],
                    'porcentaje_ps' => $totales['solidaridad'] > 0 ? 1 : 0,
                    'aporte_salud_empresa' => $totales['salud_empresa'],
                    'aporte_pension_empresa' => $totales['pension_empresa'],
                    'aporte_rieslab' => $totales['riesgos'],
                    'id_nomina' => $id_nomina,
                ]);
                if ($resSs != 'si') {
                    throw new Exception('Seguridad social: ' . $resSs);
                }

                $resPf = (new Seguridad_Social($this->conexion))->addRegistroLiq2([
                    'id_empleado' => $id_empleado,
                    'val_sena' => $totales['sena'],
                    'val_icbf' => $totales['icbf'],
                    'val_comfam' => $totales['comfam'],
                    'id_nomina' => $id_nomina,
                ]);
                if ($resPf != 'si') {
                    throw new Exception('Parafiscales: ' . $resPf);
                }

                $resPrest = (new Prestaciones_Sociales($this->conexion))->addRegistroLiq([
                    'id_empleado' => $id_empleado,
                    'id_nomina' => $id_nomina,
                    'val_vacacion' => $totales['prest_vac'],
                    'val_cesantia' => $totales['prest_ces'],
                    'val_interes_cesantia' => $totales['prest_ices'],
                    'val_prima' => $totales['prest_prima'],
                    'val_prima_vac' => $totales['prest_prima_vac'],
                    'val_prima_nav' => $totales['prest_prima_nav'],
                    'val_bonifica_recrea' => $totales['prest_bon_recrea'],
                ]);
                if ($resPrest != 'si') {
                    throw new Exception('Prestaciones sociales: ' . $resPrest);
                }

                $resRet = $this->insertRetencionFuenteRetro($id_empleado, $id_nomina, $totales['ret_base'], $totales['retencion']);
                if ($resRet != 'si') {
                    throw new Exception('Retención en la fuente: ' . $resRet);
                }

                $resSal = $this->LiquidaSalarioNeto([
                    'id_empleado' => $id_empleado,
                    'sal_base' => $salarioNuevo,
                    'id_contrato' => intval($contratos[$id_empleado] ?? ($mensual[0]['id_contrato'] ?? 0)),
                    'forma_pago' => 1,
                    'metodo_pago' => intval($mpago[$id_empleado] ?? ($mensual[0]['metodo_pago'] ?? 47)),
                    'val_liq' => $totales['neto'],
                    'id_nomina' => $id_nomina,
                ]);
                if (!$resSal['insert']) {
                    throw new Exception('Salario neto: ' . $resSal['msg']);
                }

                // Registrar valores de liquidación para el retroactivo
                $vlAnt = (new Valores_Liquidacion($this->conexion))->getUltimoRegistro($id_empleado);
                $paramValLiq = [
                    'id_empleado' => $id_empleado,
                    'smmlv' => $smmlv,
                    'aux_trans' => $salarioNuevo <= ($smmlv * 2) ? floatval($parametros[2] ?? 0) : 0,
                    'aux_alim' => ($baseAlimLimite > 0 && $salarioNuevo <= $baseAlimLimite) ? floatval($parametros[3] ?? 0) : 0,
                    'uvt' => $uvt,
                    'base_bsp' => $baseBspLimite,
                    'base_alim' => $baseAlimLimite,
                    'min_vital' => floatval($parametros[10] ?? 0),
                    'salario' => $salarioNuevo,
                    'tiene_grep' => $grepEmpleado > 0 ? 1 : 0,
                    'grep' => $grepEmpleado,
                    'prom_horas' => floatval($vlAnt['prom_horas'] ?? 0),
                    'bsp_ant' => floatval($vlAnt['bsp_ant'] ?? 0),
                    'pri_ser_ant' => floatval($vlAnt['pri_ser_ant'] ?? 0),
                    'pri_vac_ant' => floatval($vlAnt['pri_vac_ant'] ?? 0),
                    'pri_nav_ant' => floatval($vlAnt['pri_nav_ant'] ?? 0),
                    'id_nomina' => $id_nomina,
                ];
                (new Valores_Liquidacion($this->conexion))->addRegistro($paramValLiq);

                $this->conexion->commit();
                $inserts++;
            } catch (Exception $e) {
                if ($this->conexion->inTransaction()) {
                    $this->conexion->rollBack();
                }
                $error .= "<p>ID: {$id_empleado}, {$e->getMessage()}</p>";
            }
        }

        if ($error != '') {
            return $error;
        }
        if ($inserts === 0) {
            return 'No se liquidó ningÃºn empleado.';
        }
        return 'si';
    }
    /**
     * Elimina un registro.
     *
     * @param int $id ID del registro a eliminar
     * @return string Mensaje de éxito o error
     */

    public function delRegistro($id)
    {
        try {
            $sql = "DELETE FROM `nom_horas_ex_trab` WHERE `id_intv` = ?";
            $consulta = "DELETE FROM `nom_horas_ex_trab` WHERE `id_intv` = $id";
            $stmt = $this->conexion->prepare($sql);
            $stmt->bindParam(1, $id, PDO::PARAM_INT);
            $stmt->execute();
            if ($stmt->rowCount() > 0) {
                Logs::guardaLog($consulta);
                return 'si';
            } else {
                return 'No se eliminó el registro.';
            }
        } catch (PDOException $e) {
            return 'Error SQL: ' . $e->getMessage();
        }
    }

    /**
     * Agrega un nuevo registro.
     *
     * @param array $array Datos del registro a agregar
     * @return string Mensaje de éxito o error
     */
    public function addRegistro($array, $opcion = 0)
    {
        $ids = $array['chk_liquidacion'];
        $contratos = $array['id_contrato'];
        $laborado = $array['lab'];
        $mpago = $array['metodo'];
        $tipo = $array['tipo'];
        $mes = $array['mes'];
        $incremento = isset($array['incremento']) ? $array['incremento'] : NULL;
        $nomina = Nomina::getIDNomina($mes, $tipo);

        if (($nomina['id_nomina'] > 0 && $nomina['estado'] >= 2) || $nomina['id_nomina'] == 0) {
            $res = Nomina::addRegistro($mes, $tipo, $incremento);
            if ($res['status'] == 'si') {
                $id_nomina = $res['id'];
            } else {
                return $res['msg'];
            }
        } else {
            $id_nomina = $nomina['id_nomina'];
        }

        $data = Nomina::getParamLiq();
        if (empty($data)) {
            return 'No se han configurado los parámetros de liquidación.';
        }

        $parametro = array_column($data, 'valor', 'id_concepto');

        if (empty($parametro[1]) || empty($parametro[6])) {
            return 'No se han Configurado los parámetros de liquidación.';
        }

        $inicia = Sesion::Vigencia() . '-' . $mes . '-01';
        $fin = date('Y-m-t', strtotime($inicia));

        $Empleado = new Empleados();
        $empleados = array_column($Empleado->getEmpleados(), null, 'id_empleado');
        $salarios = $Empleado->getSalarioMasivo($mes);
        $salarios = array_column($salarios, 'basico', 'id_empleado');
        $terceros_ss = $Empleado->getRegistro();
        $empresa = (new Usuario())->getEmpresa();
        //Devengados
        $horas = (new Horas_Extra())->getHorasPorMes($inicia, $fin);
        $incapacidades = (new Incapacidades())->getRegistroPorEmpleado($inicia, $fin);
        $vacaciones = (new Vacaciones())->getRegistroPorEmpleado($inicia, $fin);
        $vacPagadas = (new Vacaciones())->getRegistroPago($inicia, $fin);
        $licenciasMP = (new Licencias_MoP())->getRegistroPorEmpleado($inicia, $fin);
        $licenciaNR = (new Licencias_Norem())->getRegistroPorEmpleado($inicia, $fin);
        $licenciaLuto = (new Licencias_Luto())->getRegistroPorEmpleado($inicia, $fin);
        $indemVacaciones = (new Indemniza_Vacacion())->getRegistroPorEmpleado($inicia, $fin);
        $indemVacaciones = (new Indemniza_Vacacion())->getRegistroPorEmpleado($inicia, $fin);
        $bonificaciones = (new Bsp())->getRegistroPorEmpleado();
        $bspPagadas = (new Bsp())->getRegistroPago(Sesion::Vigencia(), $mes);
        $viaticosNomina = (new Viaticos())->getViaticosNomina($inicia, $fin); // Obtener viáticos del mes
        $otrosDevengados = (new Otros_Devengados())->getRegistroPorEmpleado($inicia, $fin);

        //Deducidos
        $libranzas = (new Libranzas())->getLibranzasPorEmpleado($inicia);
        $embargos = (new Embargos())->getRegistroPorEmpleado($inicia);
        $sindicatos = (new Sindicatos())->getRegistroPorEmpleado($inicia);
        $otrosDctos = (new Otros_Descuentos())->getRegistroPorEmpleado($inicia, $fin);

        //otros 
        $cortes = array_column((self::getCortes($ids, $fin)), null, 'id_empleado');
        $iVivienda = (new Ivivienda())->getIviviendaEmpleados($ids);
        $iVivienda = array_column($iVivienda, 'valor', 'id_empleado');
        $liquidados = (self::getEmpleadosLiq($id_nomina, $ids));
        $liquidados = array_column($liquidados, 'id_sal_liq', 'id_empleado');
        $error = '';

        // Consultar configuración bsp_ibc
        $ownerConfig = Valores::getOwnerConfig();
        $incluirBspEnIbc = (isset($ownerConfig['bsp_ibc']) && $ownerConfig['bsp_ibc'] == '1');

        if ($opcion == 0) {
            $param['smmlv'] = $parametro[1];
            $param['uvt'] = $parametro[6];
            $param['base_bsp'] = $parametro[7];
            $param['grep'] = $parametro[8] ?? 0;
            $param['base_alim'] = $parametro[9] ?? 0;
            $param['min_vital'] = $parametro[10] ?? 0;
            $param['id_nomina'] = $id_nomina;
        }

        $inserts = 0;
        foreach ($ids as $id_empleado) {
            if (!(isset($liquidados[$id_empleado]) && isset($salarios[$id_empleado]))) {
                try {
                    $filtro = [];
                    $filtro = array_filter($terceros_ss, function ($terceros_ss) use ($id_empleado) {
                        return $terceros_ss["id_empleado"] == $id_empleado;
                    });

                    $novedad = array_column($filtro, 'id_tercero', 'id_tipo');
                    if (!(isset($novedad[1]) && isset($novedad[2]) && isset($novedad[3]) && isset($novedad[4]))) {
                        throw new Exception("No tiene registrado novedades de seguridad social");
                    }

                    $cortes_empleado = $cortes[$id_empleado] ?? [];
                    if (!$this->conexion->inTransaction()) {
                        $this->conexion->beginTransaction();
                    }

                    if ($opcion == 0) {
                        $param['id_empleado'] = $id_empleado;
                        $param['salario'] = $salarios[$id_empleado];
                        $param['tiene_grep'] = $cortes_empleado['tiene_grep'] ?? 0;
                        $param['bsp_ant'] = $cortes_empleado['val_bsp'] ?? 0;
                        $param['pri_ser_ant'] = $cortes_empleado['val_liq_ps'] ?? 0;
                        $param['pri_vac_ant'] = $cortes_empleado['val_liq_pv'] ?? 0;
                        $param['pri_nav_ant'] = $cortes_empleado['val_prima_vac'] ?? 0;
                        $param['prom_horas'] = $cortes_empleado['prom'] ?? 0;
                    } else if ($opcion == 1) {
                        $param = (new Valores_Liquidacion($this->conexion))->getRegistro($id_nomina, $id_empleado);
                        // Para nómina tipo N, getRegistro puede retornar salario=0 si no existe el registro previo.
                        // Se toma el salario real del empleado en todo caso.
                        $param['salario'] = $salarios[$id_empleado];
                        $param['id_empleado'] = $id_empleado;
                    }

                    $param['aux_trans'] = $salarios[$id_empleado] <= $param['smmlv'] * 2 ? $parametro[2] : 0;
                    $param['aux_alim'] = $salarios[$id_empleado] <= $param['base_alim'] ? $parametro[3] : 0;
                    $tipo_emp = $empleados[$id_empleado]['tipo_empleado'];
                    $subtipo_emp = $empleados[$id_empleado]['subtipo_empleado'];

                    if ($tipo_emp == 12 || $tipo_emp == 8) {
                        $param['aux_trans'] = 0;
                        $param['aux_alim'] = 0;
                    }
                    if ($opcion == 0) {
                        $res = (new Valores_Liquidacion($this->conexion))->addRegistro($param);
                        if ($res != 'si') {
                            throw new Exception("Valores de liquidación: $res");
                        }
                    }

                    //liquidar Horas extras
                    $valTotalHe = 0;
                    $filtro = $horas[$id_empleado] ?? [];
                    if (!empty($filtro)) {
                        $response = $this->LiquidaHorasExtra($filtro, $param);
                        $valTotalHe = $response['valor'];
                        if (!$response['insert']) {
                            throw new Exception("Horas extras: {$response['msg']}");
                        }
                    }

                    //liquidar incapacidades
                    $valTotIncap = 0;
                    $filtro = $incapacidades[$id_empleado] ?? [];
                    if (!empty($filtro)) {
                        $response = $this->LiquidaIncapacidad($filtro, $param, $novedad);
                        $valTotIncap = $response['valor'];
                        if (!$response['insert']) {
                            throw new Exception("Incapacidades: {$response['msg']}");
                        }
                    }

                    //liquidar vacaciones
                    $valTotVac = 0;
                    $valTotPrimVac = 0;
                    $valBonRec = 0;

                    $filtro = $vacaciones[$id_empleado][0] ?? [];
                    if (!empty($filtro)) {
                        $Vcc = new Vacaciones($this->conexion);
                        $rt = $Vcc->getRegistroLiq(['id_empleado' => $id_empleado, 'id_nomina' => $id_nomina]);
                        if (!empty($rt) && $rt['tipo'] == 'M') {
                            $valTotVac = $rt['val_vac'];
                            $valTotPrimVac = $rt['prima_vac'];
                            $valBonRec = $rt['bon_recrea'];
                        } else {
                            $response = $this->LiquidaVacaciones($filtro, $param);
                            $valTotVac = $response['valor'];
                            $valTotPrimVac = $response['prima'];
                            $valBonRec = $response['bono'];
                            if (!$response['insert']) {
                                throw new Exception("Vacaciones: {$response['msg']}");
                            }
                        }
                    }

                    $valTotVacIbc = $valTotVac;
                    $valTotPrimVacIbc = $valTotPrimVac;
                    $valBonRecIbc = $valBonRec;

                    //verificar si $valTotVac es igual a 0
                    if ($valTotVac == 0) {
                        if (isset($vacPagadas[$id_empleado])) {
                            foreach ($vacPagadas[$id_empleado] as $vp) {
                                $valTotVacIbc += $vp['val_vac'];
                                $valTotPrimVacIbc += $vp['prima_vac'];
                                $valBonRecIbc += $vp['bon_recrea'];
                            }
                        }
                    }
                    //liquidar licencias mop
                    $valTotLicMP = 0;
                    $filtro = $licenciasMP[$id_empleado][0] ?? [];
                    if (!empty($filtro)) {
                        $Lic = new Licencias_MoP($this->conexion);
                        $rt = $Lic->getRegistroLiq(['id_empleado' => $id_empleado, 'id_nomina' => $id_nomina]);
                        if (!empty($rt) && $rt['tipo'] == 'M') {
                            $valTotLicMP = $rt['valor'];
                        } else {
                            $filtro['id_eps'] = $novedad[1];
                            $filtro['mes'] = $mes;
                            $response = $this->LiquidaLicenciaMOP($filtro, $param);
                            $valTotLicMP = $response['valor'];

                            if (!$response['insert']) {
                                throw new Exception("Licencias MoP: {$response['msg']}");
                            }
                        }
                    }

                    //liquidar licencias no remuneradas
                    $filtro = $licenciaNR[$id_empleado] ?? [];
                    if (!empty($filtro)) {
                        $response = $this->LiquidaLicenciaNoRem($filtro, $param, $mes);
                        if (!$response['insert']) {
                            throw new Exception("Licencias no remuneradas: {$response['msg']}");
                        }
                    }

                    //liquidar licencia por luto
                    $valTotLicLuto = 0;
                    $filtro = $licenciaLuto[$id_empleado] ?? [];
                    if (!empty($filtro)) {
                        $response = $this->LiquidaLicenciaLuto($filtro, $param);
                        $valTotLicLuto = $response['valor'];
                        if (!$response['insert']) {
                            throw new Exception("Licencias por luto: {$response['msg']}");
                        }
                    }

                    //liquidar indemnización por vacaciones
                    $filtro = $indemVacaciones[$id_empleado][0] ?? [];
                    $valTotIndemVac = 0;
                    if (!empty($filtro)) {
                        $response = $this->LiquidaIndemnizaVacaciones($filtro, $param);
                        $valTotIndemVac = $response['valor'];
                        if (!$response['insert']) {
                            throw new Exception("Indemnización por vacaciones: {$response['msg']}");
                        }
                    }

                    //liquidar BSP
                    // verificar que tenga  1 entonces se liquida bps
                    $valTotalBSP = 0;
                    if ($empleados[$id_empleado]['bsp'] == 1) {
                        if (isset($bonificaciones[$id_empleado])) {
                            $dBsp = $bonificaciones[$id_empleado];
                            $valTotalBSP = $dBsp['val_bsp'];
                            $data = [
                                'numValor' => $dBsp['val_bsp'],
                                'datFecCorte' => $dBsp['fec_corte'],
                                'tipo' => 'P',
                                'id' => $dBsp['id_bonificaciones'],
                            ];
                            (new Bsp($this->conexion))->editRegistro($data);
                        } else {
                            $fecha_corte = $cortes_empleado['val_bsp'] == '' ? $cortes_empleado['inicia_ctt'] : $cortes_empleado['corte_bsp'];
                            //verificar si hay 360 día para la bonificiacion sacandolo los dias entre fecha_corte y fecha_fin
                            $tiene_bsp = (new Cesantias())->calcularDias($fecha_corte, $fin, $id_empleado) >= 360;
                            if ($tiene_bsp) {
                                $param['corte'] = date('Y-m-d', strtotime($fecha_corte . ' +1 year'));
                                $response = $this->LiquidaBSP($param);
                                $valTotalBSP = $response['valor'];
                                if (!$response['insert']) {
                                    throw new Exception("BSP: {$response['msg']}");
                                }
                            }
                        }
                    }
                    $bspToIBC = $bspPagadas[$id_empleado] ?? 0;
                    $bspToSSParafiscales = $incluirBspEnIbc ? $bspToIBC : 0;

                    // Liquidar Viáticos
                    // Verificar si tiene viáticos en el mes
                    $valTotalViaticos = 0;
                    if (isset($viaticosNomina[$id_empleado])) {
                        $filtro = $viaticosNomina[$id_empleado];
                        foreach ($filtro as $viatico) {
                            $valTotalViaticos += $viatico['val_total'];
                            // Registrar en nom_liq_viaticos
                            $dataLiqViatico = [
                                'id_viatico' => $viatico['id_viatico'],
                                'valor' => $viatico['val_total'],
                                'id_nomina' => $id_nomina
                            ];
                            $resLiqV = (new ViaticosLiq($this->conexion))->addRegistro($dataLiqViatico);
                            if ($resLiqV != 'si') {
                                throw new Exception("Error al liquidar viático ID {$viatico['id_viatico']}: $resLiqV");
                            }
                        }
                    }

                    // Liquidar otros devengados
                    $valTotalOtrosDev = 0;
                    $valTotalOtrosDevSal = 0;
                    $valTotalOtrosDevSS = 0;
                    $valTotalOtrosDevParaf = 0;
                    $filtro = $otrosDevengados[$id_empleado] ?? [];
                    if (!empty($filtro)) {
                        $response = $this->LiquidaOtrosDevengados($filtro, $param);
                        $valTotalOtrosDev = $response['valor'];
                        $valTotalOtrosDevSal = $response['valor_salarial'];
                        $valTotalOtrosDevSS = $response['valor_ibc_seg_social'];
                        $valTotalOtrosDevParaf = $response['valor_ibc_parafiscales'];
                        if (!$response['insert']) {
                            throw new Exception("Otros devengados: {$response['msg']}");
                        }
                    }

                    //laborado 
                    $valTotalLab = Valores::Redondear($laborado[$id_empleado] * ($param['salario'] / 30));
                    $valAuxTrans = Valores::Redondear($laborado[$id_empleado] * ($param['aux_trans'] / 30));
                    $valAuxAlim = Valores::Redondear($laborado[$id_empleado] * ($param['aux_alim'] / 30));
                    $grepre = $empleados[$id_empleado]['representacion'] == 1 ? $parametro[8] : 0;

                    $Otros = new Otros();
                    $labd = $Otros->getRegistroLiq(['id_empleado' => $id_empleado, 'id_nomina' => $id_nomina]);
                    if (!empty($labd) && $labd['tipo'] == 'M') {
                        $valTotalLab = $labd['val_laborado'];
                        $valAuxTrans = $labd['val_auxtrans'];
                        $valAuxAlim = $labd['auxalim'];
                        $grepre = $labd['grepre'];
                    } else {
                        $data = [
                            'id_empleado' => $id_empleado,
                            'dias_laborados' => $laborado[$id_empleado],
                            'val_laborado' => $valTotalLab,
                            'val_aux_trans' => $valAuxTrans,
                            'val_aux_alim' => $valAuxAlim,
                            'val_grep' => $grepre,
                            'val_horas_ex' => $valTotalHe,
                            'id_nomina' => $id_nomina,
                        ];
                        $response = $this->LiquidaLaborado($data);
                        if (!$response['insert']) {
                            throw new Exception("Laborado: {$response['msg']}");
                        }
                    }
                    //Seguridad social
                    if ($empleados[$id_empleado]['salario_integral'] == 1) {
                        $ibc = ($valTotalLab * 0.7) + $valTotalOtrosDevSS;
                        $ibcParafiscales = ($valTotalLab * 0.7) + $valTotalOtrosDevParaf;
                    } else {
                        $ibc = $valTotalLab + $valTotalHe + $valTotIncap + $valTotalBSP + $bspToSSParafiscales + $grepre + $valTotLicLuto + $valTotLicMP + $valTotVacIbc + $valTotalOtrosDevSS;
                        $ibcParafiscales = $valTotalLab + $valTotalHe + $valTotalBSP + $bspToSSParafiscales + $grepre + $valTotLicLuto + $valTotLicMP + $valTotVacIbc + $valTotalOtrosDevParaf;
                    }

                    $response = $this->LiquidaSeguridadSocial($param, $novedad, $ibc, $tipo_emp, $subtipo_emp, $laborado[$id_empleado]);
                    $valTotSegSoc = $response['valor'];
                    if (!$response['insert']) {
                        throw new Exception("Seguridad social: {$response['msg']}");
                    }

                    //Parafiscales
                    $response = $this->LiquidaParafiscales($param, $ibcParafiscales, $empresa['exonera_aportes'], $tipo_emp);
                    if (!$response['insert']) {
                        throw new Exception("Parafiscales: {$response['msg']}");
                    }

                    //Apropiaciones: Vacaciones, Prima de Vacaciones, bonificacion de recreacion, Prima de Servicios, Prima de navidad, Cesantias, Int. Cesantias.
                    //Reserva vacaciones

                    $filtro = [
                        'id_vac' => 0,
                        'dias_habiles' => 15,
                        'dias_inactivo' => 22,
                        'dias_liquidar' => $laborado[$id_empleado],
                        'corte' => '',
                        'id_nomina' => 0,
                    ];
                    $response = $this->LiquidaVacaciones($filtro, $param, 0);
                    $valMesVac = $response['valor'];
                    $valMesPrimVac = $response['prima'];
                    $valMesBonRec = $response['bono'];
                    if (!$response['insert']) {
                        throw new Exception("Vacaciones Mes: {$response['msg']}");
                    }
                    //Reserva Prima de Servicios

                    $response = $this->LiquidaPrimaServicios($param, $cortes_empleado, $laborado[$id_empleado], 0);
                    $valMesPriSer = $response['valor'];
                    if (!$response['insert']) {
                        throw new Exception("Prima de Servicios Mes: {$response['msg']}");
                    }

                    //Reserva Prima de Navidad
                    $response = $this->LiquidaPrimaNavidad($param, $cortes_empleado, $laborado[$id_empleado], 0);
                    $valMesPriNav = $response['valor'];
                    if (!$response['insert']) {
                        throw new Exception("Prima de Navidad Mes: {$response['msg']}");
                    }

                    //Reserva Cesantias
                    $response = $this->LiquidaCesantias($param, $cortes_empleado, $laborado[$id_empleado], 0);
                    $valMesCes = $response['valor'];
                    $valMesIntCes = $response['interes'];
                    if (!$response['insert']) {
                        throw new Exception("Cesantias Mes: {$response['msg']}");
                    }
                    $data = [
                        'id_empleado' => $id_empleado,
                        'id_nomina' => $id_nomina,
                        'val_vacacion' => $valMesVac,
                        'val_cesantia' => $valMesCes,
                        'val_interes_cesantia' => $valMesIntCes,
                        'val_prima' => $valMesPriSer,
                        'val_prima_vac' => $valMesPrimVac,
                        'val_prima_nav' => $valMesPriNav,
                        'val_bonifica_recrea' => $valMesBonRec,
                    ];
                    $response = (new Prestaciones_Sociales($this->conexion))->addRegistroLiq($data);
                    if ($response != 'si') {
                        throw new Exception("Prestaciones sociales: $response");
                    }

                    $baseDctos = $valTotalLab + $valAuxTrans + $valAuxAlim + $valTotalHe + $valTotIncap + $valTotVac + $valTotLicMP + $valTotLicLuto + $valTotalBSP + $valTotPrimVac + $valBonRec + $grepre + $valTotIndemVac + $valTotalViaticos + $valTotalOtrosDevSal - ($valTotSegSoc ?? 0);

                    //Deducciones

                    //embargos
                    $filtro = $embargos[$id_empleado] ?? [];
                    if (!empty($filtro)) {
                        $response = $this->LiquidaEmbargos($filtro, $param, $baseDctos);
                        $baseDctos = $baseDctos - $response['valor'];
                        if (!$response['insert']) {
                            throw new Exception("Embargos: {$response['msg']}");
                        }
                    }

                    //sindicatos
                    $filtro = $sindicatos[$id_empleado][0] ?? [];
                    if (!empty($filtro)) {
                        $response = $this->LiquidaSindicato($filtro, $param, $baseDctos);
                        $baseDctos = $baseDctos - $response['valor'];
                        if (!$response['insert']) {
                            throw new Exception("Sindicatos: {$response['msg']}");
                        }
                    }
                    //libranzas
                    $filtro = $libranzas[$id_empleado] ?? [];
                    if (!empty($filtro)) {
                        $response = $this->LiquidaLibranzas($filtro, $param, $baseDctos);
                        $baseDctos = $baseDctos - $response['valor'];
                        if (!$response['insert']) {
                            throw new Exception("Libranzas: {$response['msg']}");
                        }
                    }

                    //otros descuentos
                    $filtro = $otrosDctos[$id_empleado] ?? [];
                    if (!empty($filtro)) {
                        $response = $this->LiquidaOtrosDctos($filtro, $param, $baseDctos);
                        $baseDctos = $baseDctos - $response['valor'];
                        ;
                        if (!$response['insert']) {
                            throw new Exception("Otros descuentos: {$response['msg']}");
                        }
                    }

                    $baseDep = $valTotalLab + $valTotalBSP + $bspToIBC + $valTotalHe + $valTotVac + $valTotPrimVac + $valBonRec + $grepre + $valTotalOtrosDevSal;
                    $pagoxdependiente = $empleados[$id_empleado]['dependientes'] == 0 ? 0 : $baseDep * 0.1;
                    $valIntViv = $iVivienda[$id_empleado] ?? 0;
                    $valrf = $baseDep + $valTotIndemVac + $valTotLicLuto - ($valTotSegSoc ?? 0) - $pagoxdependiente - $valIntViv;
                    $valdpurado = $valrf * 0.75;
                    $uvt = $param['uvt'];
                    $ingLabUvt = $empleados[$id_empleado]['salario_integral'] == 1 ? $valTotalLab * 0.75 / $uvt : $valdpurado / $uvt;

                    $totValRetFte = 0;
                    $data = [
                        'id_empleado' => $id_empleado,
                        'id_nomina' => $id_nomina,
                        'base' => $valdpurado,
                        'ing_uvt' => $ingLabUvt,
                        'uvt' => $uvt,
                    ];
                    $response = $this->LiquidaRetencionFuente($data);
                    $totValRetFte = $response['valor'];
                    if (!$response['insert']) {
                        throw new Exception("Retención en la fuente: {$response['msg']}");
                    }

                    $neto = $baseDctos - $totValRetFte + $valTotalViaticos + ($valTotalOtrosDev - $valTotalOtrosDevSal);
                    $data = [
                        'id_empleado' => $id_empleado,
                        'id_nomina' => $id_nomina,
                        'metodo_pago' => $mpago[$id_empleado],
                        'val_liq' => $neto,
                        'forma_pago' => 1,
                        'sal_base' => $salarios[$id_empleado],
                        'id_contrato' => $contratos[$id_empleado],
                    ];
                    $response = $this->LiquidaSalarioNeto($data);
                    if (!$response['insert']) {
                        throw new Exception("Salario neto: {$response['msg']}");
                    }
                    if ($opcion == 0) {
                        $this->conexion->commit();
                    }
                    $inserts++;
                    unset($filtro, $response);
                    gc_collect_cycles();
                } catch (Exception $e) {
                    if ($this->conexion->inTransaction()) {
                        $this->conexion->rollBack();
                    }
                    $error .= "<p>ID: $id_empleado ({$empleados[$id_empleado]['no_documento']}), {$e->getMessage()}</p>";
                    continue;
                }
            }
        }
        if ($error != '') {
            return $error;
        } else if ($inserts == 0) {
            return 'No se liquidó ningún empleado.';
        } else {
            return 'si';
        }
    }
    /**
     * Actualiza los datos de un registro.
     *
     * @param array $array Datos del registro a actualizar
     * @return string Mensaje de éxito o error
     */
    public function editRegistro($array)
    {
        $data = self::getIdHoraExtra($array);
        $id = $data['id_he_trab'];
        $estado = $data['estado'];
        if ($estado == 0) {
            return 'no';
        }
        try {
            if ($id > 0) {

                $sql = "UPDATE `nom_horas_ex_trab`
                        SET `cantidad_he` = ?
                    WHERE `id_he_trab` = ?";
                $stmt = $this->conexion->prepare($sql);
                $stmt->bindValue(1, $array['valor'], PDO::PARAM_INT);
                $stmt->bindValue(2, $id, PDO::PARAM_INT);

                if ($stmt->execute() && $stmt->rowCount() > 0) {
                    Logs::guardaLog("UPDATE `nom_horas_ex_trab` SET `cantidad_he` = {$array['valor']} WHERE `id_he_trab` = $id");
                    $consulta = "UPDATE `nom_horas_ex_trab` 
                                SET `fec_actu` = ? 
                            WHERE `id_he_trab` = ?";
                    $stmt2 = $this->conexion->prepare($consulta);
                    $hoy = Sesion::Hoy();
                    $stmt2->bindValue(1, $hoy, PDO::PARAM_STR);
                    $stmt2->bindValue(2, $id, PDO::PARAM_INT);
                    $stmt2->execute();
                    Logs::guardaLog("UPDATE `nom_horas_ex_trab` SET `fec_actu` = '$hoy' WHERE `id_he_trab` = $id");
                    return 'si';
                } else {
                    return 'No se actualizó el registro.';
                }
            } else {
                $datos = base64_decode($array['id']);
                $datos = explode('|', $datos);
                $id_empleado = $datos[0];
                $tipo_hora = $datos[1];
                $mes = $array['mes'];
                $fec_inicio = Sesion::Vigencia() . '-' . $mes . '-01';

                $data = [
                    'id_empleado' => $id_empleado,
                    'datFecInicia' => $fec_inicio . 'T07:00',
                    'datFecFin' => date('Y-m-t', strtotime($fec_inicio)) . 'T23:59',
                    'slcTipoHora' => $tipo_hora,
                    'numCantidad' => $array['valor'],
                    'slcTipoLiq' => $array['tipo'],
                ];
                return self::addRegistro($data);
            }
        } catch (PDOException $e) {
            return 'Error SQL: ' . $e->getMessage();
        }
    }

    public function getIdHoraExtra($array)
    {
    }

    public static function getEmpleadosLiq($id_nomina, $ids)
    {
        if (empty($ids)) {
            return [];
        } else {
            $ids = implode(',', $ids);
        }
        try {
            $sql = "SELECT `id_empleado`,`id_sal_liq`, `id_contrato`
                    FROM `nom_liq_salario`
                    WHERE (`id_nomina` = ? AND `nom_liq_salario`.`estado` = 1 AND `id_empleado` IN ($ids))";
            $stmt = Conexion::getConexion()->prepare($sql);
            $stmt->bindParam(1, $id_nomina, PDO::PARAM_INT);
            $stmt->execute();
            $res = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $stmt->closeCursor();
            unset($stmt);
            return !empty($res) ? $res : [];
        } catch (PDOException $e) {
            return 'Error SQL: ' . $e->getMessage();
        }
    }
    public static function getCortes($empleados, $ffin)
    {
        if (empty($empleados)) {
            return [];
        } else {
            $empleados = implode(',', $empleados);
        }
        try {
            $sql = "WITH
                        `ctt` AS
                            (SELECT
                                `id_empleado`, MAX(`id_contrato_emp`) AS `contrato`
                            FROM
                                `nom_contratos_empleados`
                            WHERE (`estado` = 1)
                            GROUP BY `id_empleado`),
                        `nominas_contrato_activo` AS
                            (SELECT DISTINCT
                                `nls`.`id_nomina`
                            FROM `nom_liq_salario` `nls`
                                INNER JOIN `ctt` ON `nls`.`id_empleado` = `ctt`.`id_empleado` AND `nls`.`id_contrato` = `ctt`.`contrato`
                                INNER JOIN `nom_nominas` `nn` ON `nls`.`id_nomina` = `nn`.`id_nomina`
                            WHERE `nn`.`estado` = 5 AND `nls`.`id_contrato` IS NOT NULL
                            ),
                        `bsp_n` AS
                            (SELECT
                                `id_empleado`, `val_bsp`, `fec_corte`
                            FROM `nom_liq_bsp`
                            WHERE `id_bonificaciones` IN
                                (SELECT
                                    MAX(`nlb`.`id_bonificaciones`)
                                FROM `nom_liq_bsp` `nlb`
                                    INNER JOIN `nom_nominas` `nn` ON `nlb`.`id_nomina` = `nn`.`id_nomina`
                                WHERE `nn`.`vigencia` <= :vigencia AND (`nn`.`tipo` = 'N' OR `nn`.`tipo` = 'BS') AND  `nlb`.`estado` = 1 AND `nlb`.`val_bsp` > 0
                                AND `nn`.`id_nomina` IN (SELECT `id_nomina` FROM `nominas_contrato_activo`)
                                GROUP BY `nlb`.`id_empleado`)),
                        `bsp_ra` AS
                            (SELECT
                                `nlb`.`id_empleado`, `nlb`.`val_bsp` AS `val_bsp_ra`,`nr`.`fec_final`
                            FROM `nom_liq_bsp` `nlb`
                                INNER JOIN `nom_nominas` `nn` ON `nlb`.`id_nomina` = `nn`.`id_nomina`
                                LEFT JOIN `nom_retroactivos` `nr` ON `nr`.`id_incremento` = `nn`.`id_incremento`
                            WHERE `nlb`.`id_bonificaciones` IN
                                (SELECT
                                    MAX(`sub_nlb`.`id_bonificaciones`)
                                FROM `nom_liq_bsp` `sub_nlb`
                                    INNER JOIN `nom_nominas` `sub_nn` ON `sub_nlb`.`id_nomina` = `sub_nn`.`id_nomina`
                                WHERE `sub_nn`.`vigencia` <= :vigencia AND `sub_nn`.`tipo` = 'RA' AND  `sub_nlb`.`estado` = 1 AND `sub_nlb`.`val_bsp` > 0
                                AND `sub_nn`.`id_nomina` IN (SELECT `id_nomina` FROM `nominas_contrato_activo`)
                                GROUP BY `sub_nlb`.`id_empleado`)),
                        `t1` AS
                            (SELECT
                                `n`.`id_empleado`,
                                IFNULL(`n`.`val_bsp`, 0) + IFNULL(`r`.`val_bsp_ra`, 0) AS `val_bsp`,
                                `n`.`fec_corte`
                            FROM `bsp_n` `n`
                            LEFT JOIN `bsp_ra` `r` ON (`n`.`id_empleado` = `r`.`id_empleado`)),
                        `t2` AS
                            (SELECT
                                `id_empleado`, `corte` AS `corte_ces`
                            FROM `nom_liq_cesantias`
                            WHERE `id_liq_cesan` IN
                                (SELECT MAX(`nlc`.`id_liq_cesan`)
                                FROM `nom_liq_cesantias` `nlc`
                                    INNER JOIN `nom_nominas` `nn` ON `nlc`.`id_nomina` = `nn`.`id_nomina`
                                WHERE `nn`.`tipo` = 'CE' AND `nn`.`vigencia` <= :vigencia AND `nlc`.`estado` = 1
                                AND `nn`.`id_nomina` IN (SELECT `id_nomina` FROM `nominas_contrato_activo`)
                                GROUP BY `nlc`.`id_empleado`)),
                        `prima_pv` AS
                            (SELECT
                                `id_empleado`, `val_liq_ps` AS `val_liq_pv`, `corte` AS `corte_pv`
                            FROM `nom_liq_prima`
                            WHERE `id_liq_prima` IN
                                (SELECT MAX(`nlp`.`id_liq_prima`)
                                FROM `nom_liq_prima` `nlp`
                                    INNER JOIN `nom_nominas` `nn` ON `nlp`.`id_nomina` = `nn`.`id_nomina`
                                WHERE `nn`.`tipo` = 'PV' AND `nn`.`vigencia` <= :vigencia AND `nlp`.`estado` = 1 AND `nlp`.`val_liq_ps` > 0
                                AND `nn`.`id_nomina` IN (SELECT `id_nomina` FROM `nominas_contrato_activo`)
                                GROUP BY `nlp`.`id_empleado`)),
                        `prima_ra` AS
                            (SELECT
                                `nlp`.`id_empleado`,
                                `nlp`.`val_liq_ps` AS `val_liq_ra`,
                                `nr`.`fec_final` AS `corte_ra`
                            FROM `nom_liq_prima` `nlp`
                                INNER JOIN `nom_nominas` `nn` ON `nlp`.`id_nomina` = `nn`.`id_nomina`
                                LEFT JOIN `nom_retroactivos` `nr` ON `nr`.`id_incremento` = `nn`.`id_incremento`
                            WHERE `nlp`.`id_liq_prima` IN
                                (SELECT MAX(`sub_nlp`.`id_liq_prima`)
                                FROM `nom_liq_prima` `sub_nlp`
                                    INNER JOIN `nom_nominas` `sub_nn` ON `sub_nlp`.`id_nomina` = `sub_nn`.`id_nomina`
                                WHERE `sub_nn`.`tipo` = 'RA' AND `sub_nn`.`vigencia` <= :vigencia AND `sub_nlp`.`estado` = 1 AND `sub_nlp`.`val_liq_ps` > 0
                                AND `sub_nn`.`id_nomina` IN (SELECT `id_nomina` FROM `nominas_contrato_activo`)
                                GROUP BY `sub_nlp`.`id_empleado`)),
                        `t3` AS
                            (SELECT
                                `pv`.`id_empleado`,
                                IFNULL(`pv`.`val_liq_pv`, 0) + IFNULL(`ra`.`val_liq_ra`, 0) AS `val_liq_ps`,
                                `pv`.`corte_pv` AS `corte_prim_sv`
                            FROM `prima_pv` `pv`
                            LEFT JOIN `prima_ra` `ra` ON (`pv`.`id_empleado` = `ra`.`id_empleado`)),
                        `t4` AS
                            (SELECT
                                `id_empleado`, `val_liq_pv`, `corte` AS `corte_prim_nav`
                            FROM `nom_liq_prima_nav`
                            WHERE `id_liq_privac` IN (
                                SELECT MAX(`nlpn`.`id_liq_privac`)
                                FROM `nom_liq_prima_nav` `nlpn`
                                    INNER JOIN `nom_nominas` `nn` ON `nlpn`.`id_nomina` = `nn`.`id_nomina`
                                WHERE `nn`.`tipo` = 'PN' AND `nn`.`vigencia` <= :vigencia AND `nlpn`.`estado` = 1 AND `nlpn`.`val_liq_pv` > 0
                                AND `nn`.`id_nomina` IN (SELECT `id_nomina` FROM `nominas_contrato_activo`)
                                GROUP BY `nlpn`.`id_empleado`)),
                        `vac_n` AS
                            (SELECT
                                `nv`.`id_empleado`, `nlv`.`val_prima_vac`, `nlv`.`val_liq`, `nlv`.`val_bon_recrea`,`nv`.`corte`
                            FROM `nom_liq_vac` `nlv`
                                INNER JOIN `nom_nominas` `nn` ON `nlv`.`id_nomina` = `nn`.`id_nomina`
                                INNER JOIN `nom_vacaciones` `nv` ON `nlv`.`id_vac` = `nv`.`id_vac`
                            WHERE `nlv`.`id_liq_vac` IN
                                (SELECT MAX(`sub_nlv`.`id_liq_vac`)
                                FROM `nom_liq_vac` `sub_nlv`
                                    INNER JOIN `nom_nominas` `sub_nn` ON `sub_nlv`.`id_nomina` = `sub_nn`.`id_nomina`
                                    INNER JOIN `nom_vacaciones` `sub_nv` ON `sub_nlv`.`id_vac` = `sub_nv`.`id_vac`
                                WHERE `sub_nn`.`vigencia` <= :vigencia AND `sub_nlv`.`estado` = 1 AND `sub_nlv`.`val_liq` > 0
                                AND (`sub_nn`.`tipo` = 'VC' OR `sub_nn`.`tipo` = 'N')
                                AND `sub_nn`.`id_nomina` IN (SELECT `id_nomina` FROM `nominas_contrato_activo`)
                                GROUP BY `sub_nv`.`id_empleado`)),
                        `vac_ra` AS
                            (SELECT
                                `nv`.`id_empleado`,
                                `nlv`.`val_prima_vac` AS `val_prima_vac_racv`,
                                `nlv`.`val_liq` AS `val_liq_racv`,
                                `nlv`.`val_bon_recrea` AS `val_bon_recrea_racv`,
                                `nr`.`fec_final`
                            FROM `nom_liq_vac` `nlv`
                                INNER JOIN `nom_nominas` `nn` ON `nlv`.`id_nomina` = `nn`.`id_nomina`
                                INNER JOIN `nom_vacaciones` `nv` ON `nlv`.`id_vac` = `nv`.`id_vac`
                                LEFT JOIN `nom_retroactivos` `nr` ON `nr`.`id_incremento` = `nn`.`id_incremento`
                            WHERE `nlv`.`id_liq_vac` IN
                                (SELECT MAX(`sub_nlv`.`id_liq_vac`)
                                FROM `nom_liq_vac` `sub_nlv`
                                    INNER JOIN `nom_nominas` `sub_nn` ON `sub_nlv`.`id_nomina` = `sub_nn`.`id_nomina`
                                    INNER JOIN `nom_vacaciones` `sub_nv` ON `sub_nlv`.`id_vac` = `sub_nv`.`id_vac`
                                WHERE `sub_nn`.`vigencia` <= :vigencia AND `sub_nn`.`tipo` = 'RA' AND `sub_nlv`.`estado` = 1
                                AND `sub_nn`.`id_nomina` IN (SELECT `id_nomina` FROM `nominas_contrato_activo`)
                                GROUP BY `sub_nv`.`id_empleado`)),
                        `t5` AS
                            (SELECT
                                `n`.`id_empleado`,
                                IFNULL(`n`.`val_prima_vac`, 0) + IFNULL(`r`.`val_prima_vac_racv`, 0) AS `val_prima_vac`,
                                IFNULL(`n`.`val_liq`, 0) + IFNULL(`r`.`val_liq_racv`, 0) AS `val_liq`,
                                IFNULL(`n`.`val_bon_recrea`, 0) + IFNULL(`r`.`val_bon_recrea_racv`, 0) AS `val_bon_recrea`,
                                `n`.`corte`
                            FROM `vac_n` `n`
                            LEFT JOIN `vac_ra` `r` ON `n`.`id_empleado` = `r`.`id_empleado`),
                        `t6` AS
                            (SELECT
                                `h`.`id_empleado`,
                                SUM(`l`.`val_liq`) / COUNT(DISTINCT `l`.`id_nomina`) AS `prom`
                            FROM `nom_liq_horex` `l`
                                INNER JOIN `nom_horas_ex_trab` `h` ON `l`.`id_he_lab` = `h`.`id_he_trab`
                                INNER JOIN `nom_nominas` `n` ON `l`.`id_nomina` = `n`.`id_nomina`
                                INNER JOIN `ctt` ON `h`.`id_empleado` = `ctt`.`id_empleado`
                                INNER JOIN `nom_contratos_empleados` `ct` ON `ct`.`id_contrato_emp` = `ctt`.`contrato`
                                LEFT JOIN `t2` ON `h`.`id_empleado` = `t2`.`id_empleado`
                            WHERE `l`.`estado` = 1 AND `n`.`estado` = 5 
                            AND `h`.`fec_inicio` BETWEEN IFNULL(`t2`.`corte_ces`, `ct`.`fec_inicio`) AND '$ffin'
                            AND `n`.`id_nomina` IN (SELECT `id_nomina` FROM `nominas_contrato_activo`)
                            GROUP BY `h`.`id_empleado`)
                    SELECT
                        `e`.`id_empleado`,
                        `ctt`.`contrato`,
	                    `ct`.`fec_inicio` AS `inicia_ctt`,
                        `ct`.`fec_fin` AS `fin_ctt`,
                        `e`.`representacion`,
                        `t1`.`val_bsp`,
                        `t1`.`fec_corte` AS `corte_bsp`,
                        `t2`.`corte_ces`,
                        `t3`.`val_liq_ps`,
                        `t3`.`corte_prim_sv`,
                        `t4`.`val_liq_pv`,
                        `t4`.`corte_prim_nav`,
                        `t5`.`corte` AS `corte_vac`,
                        `t5`.`val_liq`,
                        `t5`.`val_prima_vac`,
                        `t5`.`val_bon_recrea`,
                        `t6`.`prom`
                    FROM `nom_empleado` `e`
                        LEFT JOIN `t1` ON `t1`.`id_empleado` = `e`.`id_empleado`
                        LEFT JOIN `t2` ON `t2`.`id_empleado` = `e`.`id_empleado`
                        LEFT JOIN `t3` ON `t3`.`id_empleado` = `e`.`id_empleado`
                        LEFT JOIN `t4` ON `t4`.`id_empleado` = `e`.`id_empleado`
                        LEFT JOIN `t5` ON `t5`.`id_empleado` = `e`.`id_empleado`
                        LEFT JOIN `t6` ON `t6`.`id_empleado` = `e`.`id_empleado`
                        LEFT JOIN `ctt` ON `ctt`.`id_empleado` = `e`.`id_empleado`
	                    LEFT JOIN `nom_contratos_empleados` `ct` ON `ct`.`id_contrato_emp` = `ctt`.`contrato`
                    WHERE `e`.`id_empleado` IN ($empleados)";
            $stmt = Conexion::getConexion()->prepare($sql);
            $stmt->bindValue(':vigencia', Sesion::Vigencia());
            $stmt->execute();
            $res = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $stmt->closeCursor();
            unset($stmt);
            return !empty($res) ? $res : [];
        } catch (PDOException $e) {
            return 'Error SQL: ' . $e->getMessage();
        }
    }


    public function LiquidaHorasExtra($filtro, $param)
    {
        $response = [
            'msg' => '',
            'insert' => true,
            'valor' => 0
        ];

        $config = Valores::getOwnerConfig();
        $valHora = $param['salario'] / floatval($config['horas_mes'] ?? 230);

        foreach ($filtro as $f) {
            $idHe = $f['id_he_trab'];
            $valhe = Valores::Redondear($valHora * $f['factor'] * $f['cantidad_he']);
            $data = [
                'id' => $idHe,
                'valor' => $valhe,
                'id_nomina' => $param['id_nomina']
            ];
            $res = (new Horas_Extra($this->conexion))->addRegistroLiq($data);
            if ($res != 'si') {
                $response['insert'] = false;
                $response['msg'] = "<p>$res</p>";
                break;
            } else {
                $response['valor'] += $valhe;
            }
        }
        return $response;
    }

    public function LiquidaIncapacidad($filtro, $param, $novedad)
    {
        $response = [
            'msg' => '',
            'insert' => true,
            'valor' => 0
        ];

        $valDia = $param['salario'] / 30;

        foreach ($filtro as $f) {
            $idIncap = $f['id_incapacidad'];
            $idTipo = $f['id_tipo'];
            $categoria = $f['categoria'];
            $liquidado = $f['liq'];
            $dias = $f['dias'];
            $pEmpresa = $pEps = $pArl = 0;
            if ($idTipo == 2 || $idTipo == 3) {
                $pArl = Valores::Redondear($valDia * $dias);
            } else if ($idTipo == 1) {
                if ($categoria == 2) {
                    $pEps = Valores::Redondear(($valDia * (2 / 3)) * $dias);
                } else if ($categoria == 1) {
                    $diasEmpresaRestantes = max(0, 2 - $liquidado); // Cuántos días le faltan a la empresa pagar
                    $diasEmpresaMes = min($dias, $diasEmpresaRestantes); // Solo los que caben en este mes
                    $diasEpsMes = $dias - $diasEmpresaMes; // El resto es EPS
                    $pEmpresa = Valores::Redondear($valDia * $diasEmpresaMes);
                    $pEps = Valores::Redondear(($valDia * (2 / 3)) * $diasEpsMes);
                }
            }
            // se debe sacar los id_arl e id_eps
            $data = [
                'id' => $idIncap,
                'valor' => '',
                'id_nomina' => $param['id_nomina'],
                'p_empresa' => $pEmpresa,
                'p_eps' => $pEps,
                'p_arl' => $pArl,
                'dias' => $dias,
                'id_arl' => $novedad[1],
                'id_eps' => explode('|', $novedad[3])[0]
            ];

            $res = (new Incapacidades($this->conexion))->addRegistroLiq($data);
            if ($res != 'si') {
                $response['insert'] = false;
                $response['msg'] = "<p>$res</p>";
                break;
            } else {
                $response['valor'] += ($pEmpresa + $pEps + $pArl);
            }
        }
        return $response;
    }
    public function LiquidaVacaciones($filtro, $param, $opcion = 1)
    {
        $response = [
            'msg' => '',
            'insert' => true,
            'valor' => 0,
            'prima' => 0,
            'bono' => 0
        ];
        $config = Valores::getOwnerConfig();
        $salbas = floatval($param['salario']);
        $grepre = ($param['tiene_grep'] == 1) ? floatval($param['grep']) : 0;
        $auxtra = floatval($param['aux_trans']);
        $auxali = floatval($param['aux_alim']);
        $bspant = floatval($param['bsp_ant'] ?? 0);
        $psvant = floatval($param['pri_ser_ant'] ?? 0);
        $base = $salbas + $grepre + $auxtra + $auxali + $bspant / 12 + $psvant / 12;
        $idvac = $filtro['id_vac'];
        $dhabiles = $filtro['dias_habiles'] ?? 15;
        $dinactiv = $filtro['dias_inactivo'] ?? 22;
        $dliq = $filtro['dias_liquidar'];
        $corte = $filtro['corte'];
        $id_nomina = $param['id_nomina'];
        $dhabiles = $dhabiles > 15 ? 15 : $dhabiles;
        $dinactiv = $dinactiv > 22 ? 22 : $dinactiv;

        $prima_vac_dia = ($base * $dhabiles) / (30 * 360);
        $prima_vac = Valores::Redondear($prima_vac_dia * $dliq);

        $vac_dia = ($base * $dinactiv) / (30 * 360);
        $vacacion = Valores::Redondear($vac_dia * $dliq);
        $dias_rec = isset($config['dias_recreacion']) ? $config['dias_recreacion'] : 2;
        $bonrecrea = Valores::Redondear(($salbas / 30) * ($dias_rec * $dliq / 360));

        // Para caracter 1, la prima de vacaciones y la bonificación de recreación no aplican
        if (Sesion::Caracter() == 1) {
            $prima_vac = 0;
            $bonrecrea = 0;
        }
        if ($opcion == 1) {
            // Si ya existe un registro editado manualmente (tipo='M'), respetarlo sin recalcular
            $stmtChk = $this->conexion->prepare(
                "SELECT `id_liq_vac`, `val_liq`, `val_prima_vac`, `val_bon_recrea`, `tipo`
                 FROM `nom_liq_vac`
                 WHERE `id_vac` = ? AND `id_nomina` = ? AND `estado` = 1
                 ORDER BY `id_liq_vac` DESC LIMIT 1"
            );
            $stmtChk->execute([$idvac, $id_nomina]);
            $rtChk = $stmtChk->fetch(PDO::FETCH_ASSOC);
            $stmtChk->closeCursor();

            if ($rtChk && isset($rtChk['tipo']) && $rtChk['tipo'] == 'M') {
                // Valor modificado manualmente — no tocar
                $response['valor'] = floatval($rtChk['val_liq'] ?? 0);
                $response['prima'] = floatval($rtChk['val_prima_vac'] ?? 0);
                $response['bono'] = floatval($rtChk['val_bon_recrea'] ?? 0);
                return $response;
            }

            if ($rtChk) {
                // Existe registro tipo='S': actualizarlo en lugar de anular+insertar
                $stmtUpd = $this->conexion->prepare(
                    "UPDATE `nom_liq_vac`
                     SET `val_liq` = ?, `val_prima_vac` = ?, `val_bon_recrea` = ?,
                         `sal_base` = ?, `g_rep` = ?, `aux_tra` = ?, `aux_alim` = ?,
                         `bsp_ant` = ?, `psv_ant` = ?, `dias_liqs` = ?
                     WHERE `id_liq_vac` = ?"
                );
                $stmtUpd->execute([
                    $vacacion,
                    $prima_vac,
                    $bonrecrea,
                    $salbas,
                    $grepre,
                    $auxtra,
                    $auxali,
                    $bspant,
                    $psvant,
                    $dhabiles,
                    $rtChk['id_liq_vac']
                ]);
                $stmtUpd->closeCursor();
                if ($stmtUpd->rowCount() > 0) {
                    Logs::guardaLog("UPDATE `nom_liq_vac` SET `val_liq` = $vacacion, `val_prima_vac` = $prima_vac, `val_bon_recrea` = $bonrecrea, `sal_base` = $salbas, `g_rep` = $grepre, `aux_tra` = $auxtra, `aux_alim` = $auxali, `bsp_ant` = $bspant, `psv_ant` = $psvant, `dias_liqs` = $dhabiles WHERE `id_liq_vac` = {$rtChk['id_liq_vac']}");
                }
            } else {
                // No existe registro previo: insertar uno nuevo
                $data = compact('idvac', 'corte', 'vacacion', 'prima_vac', 'bonrecrea', 'id_nomina', 'salbas', 'grepre', 'auxtra', 'auxali', 'bspant', 'psvant', 'dhabiles');
                $res = (new Vacaciones($this->conexion))->addRegistroLiq($data);
                if ($res != 'si') {
                    $response['insert'] = false;
                    $response['msg'] = "<p>$res</p>";
                    return $response;
                }
            }
        }

        $response['valor'] = $vacacion;
        $response['prima'] = $prima_vac;
        $response['bono'] = $bonrecrea;

        return $response;
    }

    public function LiquidaPrimaServicios($param, $cortes, $dliq, $opcion = 1)
    {
        $response = [
            'msg' => '',
            'insert' => true,
            'valor' => 0,
        ];

        $salbas = $param['salario'];
        $id_empleado = $param['id_empleado'];
        $cant_dias = $dliq;
        $val_liq_pns = 0;
        $periodo = 1;
        $grepre = ($param['tiene_grep'] == 1) ? floatval($param['grep']) : 0;
        $auxtra = $param['aux_trans'];
        $auxali = $param['aux_alim'];
        $bspant = floatval($param['bsp_ant'] ?? 0);
        $base = $salbas + $grepre + $auxtra + $auxali + $bspant / 12;
        $corte = $param['corte_prim_sv'] ?? NULL;
        $id_nomina = $param['id_nomina'];

        $prima_dia = $base / ($_SESSION['caracter'] == 1 ? 360 : 720);
        $val_liq_ps = Valores::Redondear($prima_dia * $dliq);

        if ($opcion == 1) {
            $stmt = $this->conexion->prepare("SELECT `tipo`, `val_liq_ps` FROM `nom_liq_prima` WHERE `id_empleado` = ? AND `id_nomina` = ? AND `estado` = 1 ORDER BY `id_liq_prima` DESC LIMIT 1");
            $stmt->execute([$id_empleado, $id_nomina]);
            $rt = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($rt && isset($rt['tipo']) && $rt['tipo'] == 'M') {
                $response['valor'] = $rt['val_liq_ps'] ?? 0;
                $response['insert'] = true;
                return $response;
            }

            $data = compact('id_empleado', 'cant_dias', 'val_liq_ps', 'val_liq_pns', 'periodo', 'corte', 'id_nomina');
            $res = (new Primas($this->conexion))->addRegistroLiq1($data);

            if ($res != 'si') {
                $response['insert'] = false;
                $response['msg'] = "<p>$res</p>";
                return $response;
            }
        }

        $response['valor'] = $val_liq_ps;

        return $response;
    }

    public function LiquidaPrimaNavidad($param, $cortes, $dliq, $opcion = 1)
    {
        $response = [
            'msg' => '',
            'insert' => true,
            'valor' => 0,
        ];

        $salbas = $param['salario'];
        $id_empleado = $param['id_empleado'];
        $cant_dias = $dliq;
        $val_liq_pnv = 0;
        $periodo = 2;
        $grepre = ($param['tiene_grep'] == 1) ? floatval($param['grep']) : 0;
        $auxtra = $param['aux_trans'];
        $auxali = $param['aux_alim'];
        $bspant = floatval($param['bsp_ant'] ?? 0);
        $prima_ant = floatval($param['pri_ser_ant'] ?? 0);
        $vac_ant = floatval($param['pri_vac_ant'] ?? 0);
        $base = $salbas + $grepre + $auxtra + $auxali + ($bspant / 12) + ($prima_ant / 12) + ($vac_ant / 12);
        $corte = $param['corte_psv'] ?? NULL;
        $id_nomina = $param['id_nomina'];

        $prima_dia = $base / 360;
        $val_liq_pv = Valores::Redondear($prima_dia * $dliq);

        if ($opcion == 1) {
            $stmt = $this->conexion->prepare("SELECT `tipo`, `val_liq_pv` FROM `nom_liq_prima_nav` WHERE `id_empleado` = ? AND `id_nomina` = ? AND `estado` = 1 ORDER BY `id_liq_privac` DESC LIMIT 1");
            $stmt->execute([$id_empleado, $id_nomina]);
            $rt = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($rt && isset($rt['tipo']) && $rt['tipo'] == 'M') {
                $response['valor'] = $rt['val_liq_pv'] ?? 0;
                $response['insert'] = true;
                return $response;
            }

            $data = compact('id_empleado', 'cant_dias', 'val_liq_pv', 'val_liq_pnv', 'periodo', 'corte', 'id_nomina');
            $res = (new Primas($this->conexion))->addRegistroLiq2($data);

            if ($res != 'si') {
                $response['insert'] = false;
                $response['msg'] = "<p>$res</p>";
                return $response;
            }
        }

        $response['valor'] = $val_liq_pv;

        return $response;
    }
    /**
     * Liquida cesantías y interés de cesantías
     * $param: array con los parámetros
     * $cortes: array con los cortes
     * $dliq: días laborados
     * $opcion: 1 inserta, 0 no inserta y obtiene el valor
     */
    public function LiquidaCesantias($param, $cortes, $dliq, $opcion = 1)
    {
        $response = [
            'msg' => '',
            'insert' => true,
            'valor' => 0,
        ];

        $salbas = $param['salario'];
        $id_empleado = $param['id_empleado'];
        $dliq = $dliq > 0 ? $dliq : 0;
        $cant_dias = $dliq;
        $grepre = $param['tiene_grep'] == 1 ? $param['grep'] : 0;
        $auxtra = $param['aux_trans'];
        $auxali = $param['aux_alim'];
        $bspant = floatval($param['bsp_ant'] ?? 0);
        $prima_ant = floatval($param['pri_ser_ant'] ?? 0);
        $vac_ant = floatval($param['pri_vac_ant'] ?? 0);
        $prima_nav_ant = floatval($param['pri_nav_ant'] ?? 0);
        $promHoEx = floatval($param['prom_horas'] ?? 0);
        $base = $salbas + $grepre + $auxtra + $auxali + ($bspant / 12) + ($prima_ant / 12) + ($vac_ant / 12) + ($prima_nav_ant / 12) + $promHoEx;
        $corte = $param['corte_psv'] ?? NULL;
        $id_nomina = $param['id_nomina'];

        $cesantia_dia = $base / 360;
        $val_cesantias = Valores::Redondear($cesantia_dia * $dliq);
        $val_icesantias = Valores::Redondear($val_cesantias * 0.12);

        if ($opcion == 1) {
            $stmt = $this->conexion->prepare("SELECT `tipo`, `val_cesantias`, `val_icesantias` FROM `nom_liq_cesantias` WHERE `id_empleado` = ? AND `id_nomina` = ? AND `estado` = 1 ORDER BY `id_liq_cesan` DESC LIMIT 1");
            $stmt->execute([$id_empleado, $id_nomina]);
            $rt = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($rt && isset($rt['tipo']) && $rt['tipo'] == 'M') {
                $response['valor'] = $rt['val_cesantias'] ?? 0;
                $response['interes'] = $rt['val_icesantias'] ?? 0;
                $response['insert'] = true;
                return $response;
            }

            if (isset($param['tipo']) && $param['tipo'] == 8) {
                $val_icesantias = 0;
            } elseif (isset($param['tipo']) && $param['tipo'] == 9) {
                $val_cesantias = 0;
            }
            $data = compact('id_empleado', 'cant_dias', 'val_cesantias', 'val_icesantias', 'corte', 'id_nomina');
            $res = (new Cesantias($this->conexion))->addRegistroLiq($data);

            if ($res != 'si') {
                $response['insert'] = false;
                $response['msg'] = "<p>$res</p>";
                return $response;
            }
        }

        $response['valor'] = $val_cesantias;
        $response['interes'] = $val_icesantias;

        return $response;
    }

    public function LiquidaLicenciaMOP($filtro, $param)
    {
        $response = [
            'msg' => '',
            'insert' => true,
            'valor' => 0
        ];
        $tipo = $filtro['tipo'];
        $diasRaw = (int) $filtro['dias'];
        $dias = $filtro['mes'] == '02' && $diasRaw >= 28 ? 30 : min($diasRaw, 30);
        $valdialc = ($tipo == '1' && $filtro['dias_cot'] < 270) ? ($filtro['dias_cot'] * $param['salario']) / (30 * 270) : $param['salario'] / 30;
        $valor = Valores::Redondear($valdialc * $dias);
        $data = [
            'id_licmp' => $filtro['id_licmp'],
            'id_eps' => $filtro['id_eps'],
            'dias_liqs' => $dias,
            'val_liq' => $valor,
            'val_dialc' => $valdialc,
            'id_nomina' => $param['id_nomina']
        ];
        $res = (new Licencias_MoP($this->conexion))->addRegistroLiq($data);
        if ($res != 'si') {
            $response['insert'] = false;
            $response['msg'] = "<p>$res</p>";
        } else {
            $response['valor'] = $valor;
        }
        return $response;
    }

    public function LiquidaLicenciaNoRem($filtro, $param, $mes)
    {
        $response = [
            'msg' => '',
            'insert' => true,
        ];
        foreach ($filtro as $f) {
            $dias = $mes == '02' && $f['dias'] >= 28 ? 30 : $f['dias'];
            $data = [
                'id_licnr' => $f['id_licnr'],
                'dias_licnr' => $dias,
                'id_nomina' => $param['id_nomina']
            ];

            $res = (new Licencias_Norem($this->conexion))->addRegistroLiq($data);
            if ($res != 'si') {
                $response['insert'] = false;
                $response['msg'] = "<p>$res</p>";
                break;
            }
        }
        return $response;
    }

    public function LiquidaLicenciaLuto($filtro, $param)
    {
        $response = [
            'msg' => '',
            'insert' => true,
            'valor' => 0
        ];
        $valor_dia = $param['salario'] / 30;
        foreach ($filtro as $f) {
            $valor = Valores::Redondear($valor_dia * $f['dias']);
            $data = [
                'id_licluto' => $f['id_licluto'],
                'dias' => $f['dias'],
                'valor' => $valor,
                'id_nomina' => $param['id_nomina']
            ];

            $res = (new Licencias_Luto($this->conexion))->addRegistroLiq($data);
            if ($res != 'si') {
                $response['insert'] = false;
                $response['msg'] = "<p>$res</p>";
                break;
            } else {
                $response['valor'] += $valor;
            }
        }
        return $response;
    }

    public function LiquidaIndemnizaVacaciones($filtro, $param)
    {
        $response = [
            'msg' => '',
            'insert' => true,
            'valor' => 0
        ];
        $valor_dia = $param['salario'] / 30;
        $valor = Valores::Redondear($valor_dia * $filtro['dias']);
        $data = [
            'id_indemniza' => $filtro['id_indemniza'],
            'dias' => $filtro['dias'],
            'valor' => $valor,
            'id_nomina' => $param['id_nomina']
        ];

        $res = (new Indemniza_Vacacion($this->conexion))->addRegistroLiq($data);
        if ($res != 'si') {
            $response['insert'] = false;
            $response['msg'] = "<p>$res</p>";
        } else {
            $response['valor'] = $valor;
        }
        return $response;
    }

    public function LiquidaBSP($param)
    {
        $response = [
            'msg' => '',
            'insert' => true,
            'valor' => 0
        ];

        $id_empleado = $param['id_empleado'];
        $id_nomina = $param['id_nomina'];

        // Si ya existe un registro tipo='M' (editado manualmente), respetarlo y no duplicar
        $stmt = $this->conexion->prepare(
            "SELECT `val_bsp` FROM `nom_liq_bsp`
             WHERE `id_empleado` = ? AND `id_nomina` = ? AND `estado` = 1 AND `tipo` = 'M'
             ORDER BY `id_bonificaciones` DESC LIMIT 1"
        );
        $stmt->execute([$id_empleado, $id_nomina]);
        $rt = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($rt && isset($rt['val_bsp'])) {
            $response['valor'] = floatval($rt['val_bsp']);
            $response['insert'] = true;
            return $response;
        }

        $dias = floatval($param['dias_bsp'] ?? 360);
        $salario = floatval($param['salario'] ?? 0);
        $base_bsp = floatval($param['base_bsp'] ?? 0);
        $val_grep = $param['tiene_grep'] == 1 ? floatval($param['greps'] ?? 0) : 0;
        $bsp = (($salario + $val_grep) <= $base_bsp ? ($salario + $val_grep) * 0.53 : ($salario + $val_grep) * 0.38);
        $bsp = Valores::Redondear($bsp * $dias / 360);
        $data = [
            'id_empleado' => $id_empleado,
            'corte' => $param['corte'],
            'valor' => $bsp,
            'id_nomina' => $id_nomina
        ];

        $res = (new Bsp($this->conexion))->addRegistro($data);
        if ($res != 'si') {
            $response['insert'] = false;
            $response['msg'] = "<p>$res</p>";
        } else {
            $response['valor'] = $bsp;
        }
        return $response;
    }
    /**
     * Liquida la seguridad social de un empleado.
     * @param array $param Parámetros necesarios para la liquidación
     * @param array $novedad Novedades del empleado
     * @param float $ibc Ingreso Base de Cotización
     * @param string $tipo Tipo de empleado
     * @param string $subtipo Subtipo de empleado
     * @return array Resultado de la liquidación con mensaje, estado de inserción y valor
     */
    public function LiquidaSeguridadSocial($param, $novedad, $ibc, $tipo, $subtipo, $diaslab)
    {
        $response = [
            'msg' => '',
            'insert' => true,
            'valor' => 0
        ];

        $ibc = Valores::Redondear($ibc, 1);
        $smmlv = $param['smmlv'];

        // Ajustar IBC entre mínimo y máximo permitido
        $ibc = max($smmlv, min($ibc, $smmlv * 25));

        // Cálculos base
        $saludTotal = Valores::Redondear($ibc * 0.125, 100);
        $pensionTotal = Valores::Redondear($ibc * 0.16, 100);
        $saludEmpleado = Valores::Redondear($ibc * 0.04, 1);
        $pensionEmpleado = $saludEmpleado;
        $pSol = $pSub = $porPS = 0;

        // Aportes de solidaridad si aplica
        if ($ibc >= $smmlv * 4 && $ibc < $smmlv * 16) {
            $pSol = $pSub = Valores::Redondear($ibc * 0.005, 100);
            $porPS = 1;
        }

        // Ajustes según tipo
        if ($tipo == 12) {
            $saludEmpleado = $pensionEmpleado = $pSol = $pSub = $porPS = 0;
            $saludTotal = $pensionTotal = 0;
        } else if ($tipo == 8) {
            $saludEmpleado = $pensionEmpleado = $pSol = $pSub = $porPS = 0;
            $saludTotal = (($param['salario'] / 30) * $diaslab) * 0.125;
            $pensionTotal = 0;
        }

        // Ajustes según subtipo
        if ($subtipo == 2) {
            $pensionEmpleado = $pSol = $pSub = $porPS = $pensionTotal = 0;
        }

        // Cálculo ARL
        [$idArl, $porcentajeArl] = explode('|', $novedad[3]);
        $riesgos = Valores::Redondear($ibc * $porcentajeArl, 100);

        // Datos a guardar
        $data = [
            'id_empleado' => $param['id_empleado'],
            'id_eps' => $novedad[1],
            'id_arl' => $idArl,
            'id_afp' => $novedad[2],
            'aporte_salud_emp' => $saludEmpleado,
            'aporte_pension_emp' => $pensionEmpleado,
            'aporte_solidaridad_pensional' => $pSol + $pSub,
            'porcentaje_ps' => $porPS,
            'aporte_salud_empresa' => $saludTotal - $saludEmpleado,
            'aporte_pension_empresa' => $pensionTotal - $pensionEmpleado,
            'aporte_rieslab' => $riesgos,
            'id_nomina' => $param['id_nomina'],
        ];

        // Insertar y generar respuesta
        $res = (new Seguridad_Social($this->conexion))->addRegistroLiq($data);

        if ($res != 'si') {
            $response['insert'] = false;
            $response['msg'] = "<p>$res</p>";
        } else {
            $response['valor'] = $saludEmpleado + $pensionEmpleado + $pSol + $pSub;
        }
        return $response;
    }

    /**
     * Liquida los aportes parafiscales de un empleado.
     * @param array $param Parámetros necesarios para la liquidación
     * @param float $ibc Ingreso Base de Cotización
     * @param bool $exonerado Indica si el empleado está exonerado de aportes
     * @param int $tipo_emp Tipo de empleado
     * @return array Resultado de la liquidación con mensaje, estado de inserción y valor
     */
    public function LiquidaParafiscales($param, $ibc, $exonerado, $tipo_emp)
    {
        $response = [
            'msg' => '',
            'insert' => true,
            'valor' => 0
        ];
        $ibc = Valores::Redondear($ibc, 1);
        // Si el tipo de empleado es 12 o 8, no aplica ningún aporte
        if (in_array($tipo_emp, [12, 8])) {
            $sena = $icbf = $comfam = 0;
        } else {
            $sena = $exonerado ? 0 : $ibc * 0.02;
            $icbf = $exonerado ? 0 : $ibc * 0.03;
            $comfam = $ibc * 0.04;
        }

        $data = [
            'id_empleado' => $param['id_empleado'],
            'val_sena' => Valores::Redondear($sena, 100),
            'val_icbf' => Valores::Redondear($icbf, 100),
            'val_comfam' => Valores::Redondear($comfam, 100),
            'id_nomina' => $param['id_nomina'],
        ];

        $res = (new Seguridad_Social($this->conexion))->addRegistroLiq2($data);

        if ($res !== 'si') {
            $response['insert'] = false;
            $response['msg'] = "<p>$res</p>";
        } else {
            $response['valor'] = $sena + $icbf + $comfam;
        }

        return $response;
    }

    public function LiquidaLibranzas($filtro, $param, $base)
    {
        $response = [
            'msg' => '',
            'insert' => true,
            'valor' => 0,
        ];
        $smmlv = $param['smmlv'];
        $minVital = $param['min_vital'] > 0 ? $param['min_vital'] : $smmlv;

        foreach ($filtro as $f) {
            $base -= $f['val_mes'];
            if ($base > $f['val_mes'] && $base > $minVital) {
                $valLib = Valores::Redondear($f['val_mes']);
                $data = [
                    'id_libranza' => $f['id_libranza'],
                    'val_mes' => $valLib,
                    'id_nomina' => $param['id_nomina']
                ];

                $res = (new Libranzas($this->conexion))->addRegistroLiq($data);
                if ($res != 'si') {
                    $response['insert'] = false;
                    $response['msg'] = "<p>$res</p>";
                    break;
                } else {
                    $response['valor'] += $valLib;
                }
            } else {
                continue;
            }
        }
        return $response;
    }

    public function LiquidaEmbargos($filtro, $param, $base)
    {
        $response = [
            'msg' => '',
            'insert' => true,
            'valor' => 0,
        ];

        $smmlv = $param['smmlv'];
        $minVital = $param['min_vital'] > 0 ? $param['min_vital'] : $smmlv;

        foreach ($filtro as $f) {
            // Validar primero si hay suficiente base para descontar
            if ($base > $f['valor_mes'] && $base > $minVital) {
                $valEmb = Valores::Redondear($f['valor_mes']);
                $data = [
                    'id_embargo' => $f['id_embargo'],
                    'val_mes' => $valEmb,
                    'id_nomina' => $param['id_nomina']
                ];

                $res = (new Embargos($this->conexion))->addRegistroLiq($data);
                if ($res != 'si') {
                    $response['insert'] = false;
                    $response['msg'] = "<p>$res</p>";
                    break;
                } else {
                    // Restar de la base solo después de validar y registrar exitosamente
                    $base -= $valEmb;
                    $response['valor'] += $valEmb;
                }
            } else {
                // No hay suficiente base para este embargo, continuar con el siguiente
                continue;
            }
        }
        return $response;
    }

    public function LiquidaSindicato($filtro, $param, $base)
    {
        $response = [
            'msg' => '',
            'insert' => true,
            'valor' => 0,
        ];
        $data = [
            'id_sindicato' => $filtro['id_cuota_sindical'],
            'id_nomina' => $param['id_nomina']
        ];

        $config = Valores::getOwnerConfig();
        $smmlv = $param['smmlv'];
        $minVital = $param['min_vital'] > 0 ? $param['min_vital'] : $smmlv;

        $sindicalizacion = !empty((new Sindicatos($this->conexion))->getRegistroLiq($filtro['id_sindicato'])) ? 0 : $filtro['val_sidicalizacion'];
        $val = isset($config['sindicato']) && $config['sindicato'] == 1 ? $filtro['val_fijo'] : ($filtro['porcentaje_cuota'] / 100) * $param['salario'];
        $dcto = Valores::Redondear($val + $sindicalizacion);
        $data['valor_fijo'] = $dcto;

        if ($base > $dcto && $base > $minVital) {
            $res = (new Sindicatos($this->conexion))->addRegistroLiq($data);
            if ($res != 'si') {
                $response['insert'] = false;
                $response['msg'] = "<p>$res</p>";
            } else {
                $response['valor'] = $dcto;
            }
        }
        return $response;
    }

    public function LiquidaOtrosDctos($filtro, $param, $base)
    {
        $response = [
            'msg' => '',
            'insert' => true,
            'valor' => 0,
        ];

        $smmlv = $param['smmlv'];
        $minVital = $param['min_vital'] > 0 ? $param['min_vital'] : $smmlv;

        foreach ($filtro as $f) {
            $base -= $f['valor'];
            if ($base > $f['valor'] && $base > $minVital) {
                $valDcto = Valores::Redondear($f['valor']);
                $data = [
                    'id_dcto' => $f['id_dcto'],
                    'valor' => $valDcto,
                    'id_nomina' => $param['id_nomina']
                ];

                $res = (new Otros_Descuentos($this->conexion))->addRegistroLiq($data);
                if ($res != 'si') {
                    $response['insert'] = false;
                    $response['msg'] = "<p>$res</p>";
                    break;
                } else {
                    $response['valor'] += $valDcto;
                }
            } else {
                continue;
            }
        }
        return $response;
    }

    public function LiquidaOtrosDevengados($filtro, $param)
    {
        $response = [
            'msg' => '',
            'insert' => true,
            'valor' => 0,
            'valor_salarial' => 0,
            'valor_ibc_seg_social' => 0,
            'valor_ibc_parafiscales' => 0,
        ];

        foreach ($filtro as $f) {
            $valorDevengado = Valores::Redondear($f['valor']);
            $data = [
                'id_devengado' => $f['id_devengado'],
                'valor' => $valorDevengado,
                'id_nomina' => $param['id_nomina']
            ];

            $res = (new Otros_Devengados($this->conexion))->addRegistroLiq($data);
            if ($res != 'si') {
                $response['insert'] = false;
                $response['msg'] = "<p>$res</p>";
                break;
            }

            $response['valor'] += $valorDevengado;
            if ((int) $f['es_salarial'] === 1) {
                $response['valor_salarial'] += $valorDevengado;
            }
            if ((int) $f['seg_social'] === 1) {
                $response['valor_ibc_seg_social'] += $valorDevengado;
            }
            if ((int) $f['parafiscales'] === 1) {
                $response['valor_ibc_parafiscales'] += $valorDevengado;
            }
        }

        return $response;
    }

    public function LiquidaRetencionFuente($array)
    {
        $response = [
            'msg' => '',
            'insert' => false,
            'valor' => 0
        ];
        $ingLabUvt = $array['ing_uvt'];
        $uvt = $array['uvt'];
        $retencion = 0;

        if ($ingLabUvt >= 95 && $ingLabUvt < 150) {
            $uvtx = $ingLabUvt - 95;
            $retencion = $uvt * $uvtx * 0.19;
        } else if ($ingLabUvt >= 150 && $ingLabUvt < 360) {
            $uvtx = $ingLabUvt - 150;
            $retencion = ($uvt * $uvtx * 0.28) + (10 * $uvt);
        } else if ($ingLabUvt >= 360 && $ingLabUvt < 640) {
            $uvtx = $ingLabUvt - 360;
            $retencion = ($uvt * $uvtx * 0.33) + (69 * $uvt);
        } else if ($ingLabUvt >= 640 && $ingLabUvt < 945) {
            $uvtx = $ingLabUvt - 640;
            $retencion = ($uvt * $uvtx * 0.35) + (162 * $uvt);
        } else if ($ingLabUvt >= 945 && $ingLabUvt < 2300) {
            $uvtx = $ingLabUvt - 945;
            $retencion = ($uvt * $uvtx * 0.37) + (268 * $uvt);
        } else if ($ingLabUvt >= 2300) {
            $uvtx = $ingLabUvt - 2300;
            $retencion = ($uvt * $uvtx * 0.39) + (770 * $uvt);
        }
        $retencion = Valores::Redondear($retencion);
        try {
            $sql = "INSERT INTO `nom_retencion_fte`
                        (`id_empleado`,`base`,`val_ret`,`id_user_reg`,`fec_reg`,`id_nomina`)
                    VALUES (?, ?, ?, ?, ?, ?)";
            $stmt = $this->conexion->prepare($sql);
            $stmt->bindValue(1, $array['id_empleado'], PDO::PARAM_INT);
            $stmt->bindValue(2, $array['base'], PDO::PARAM_INT);
            $stmt->bindValue(3, $retencion, PDO::PARAM_INT);
            $stmt->bindValue(4, Sesion::IdUser(), PDO::PARAM_INT);
            $stmt->bindValue(5, Sesion::Hoy(), PDO::PARAM_STR);
            $stmt->bindValue(6, $array['id_nomina'], PDO::PARAM_INT);
            $stmt->execute();
            $id = $this->conexion->lastInsertId();
            if ($id > 0) {
                $hoy = Sesion::Hoy();
                $idUser = Sesion::IdUser();
                Logs::guardaLog("INSERT INTO `nom_retencion_fte` (`id_empleado`,`base`,`val_ret`,`id_user_reg`,`fec_reg`,`id_nomina`) VALUES ({$array['id_empleado']}, {$array['base']}, $retencion, $idUser, '$hoy', {$array['id_nomina']})");
                $response['insert'] = true;
                $response['msg'] = 'si';
                $response['valor'] = $retencion;
            } else {
                $response['msg'] = 'No se insertó el registro';
            }
        } catch (PDOException $e) {
            $response['msg'] = 'Error SQL: ' . $e->getMessage();
        }
        return $response;
    }

    public function LiquidaSalarioNeto($array)
    {
        $response = [
            'msg' => '',
            'insert' => false,
            'valor' => 0
        ];

        try {
            $sql = "INSERT INTO `nom_liq_salario`
                        (`id_empleado`,`sal_base`, `id_contrato`,`forma_pago`,`metodo_pago`,`val_liq`,`fec_reg`,`id_user_reg`,`id_nomina`)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $stmt = $this->conexion->prepare($sql);
            $stmt->bindValue(1, $array['id_empleado'], PDO::PARAM_INT);
            $stmt->bindValue(2, $array['sal_base'], PDO::PARAM_STR);
            $stmt->bindValue(3, $array['id_contrato'], PDO::PARAM_INT);
            $stmt->bindValue(4, $array['forma_pago'], PDO::PARAM_INT);
            $stmt->bindValue(5, $array['metodo_pago'], PDO::PARAM_INT);
            $stmt->bindValue(6, $array['val_liq'], PDO::PARAM_STR);
            $stmt->bindValue(7, Sesion::Hoy(), PDO::PARAM_STR);
            $stmt->bindValue(8, Sesion::IdUser(), PDO::PARAM_INT);
            $stmt->bindValue(9, $array['id_nomina'], PDO::PARAM_INT);
            $stmt->execute();
            $id = $this->conexion->lastInsertId();
            if ($id > 0) {
                $hoy = Sesion::Hoy();
                $idUser = Sesion::IdUser();
                Logs::guardaLog("INSERT INTO `nom_liq_salario` (`id_empleado`,`sal_base`, `id_contrato`,`forma_pago`,`metodo_pago`,`val_liq`,`fec_reg`,`id_user_reg`,`id_nomina`) VALUES ({$array['id_empleado']}, {$array['sal_base']}, {$array['id_contrato']}, {$array['forma_pago']}, {$array['metodo_pago']}, {$array['val_liq']}, '$hoy', $idUser, {$array['id_nomina']})");
                $response['insert'] = true;
                $response['msg'] = 'si';
                $response['valor'] = $array['val_liq'];
            } else {
                $response['msg'] = 'No se insertó el registro';
            }
        } catch (PDOException $e) {
            $response['msg'] = 'Error SQL: ' . $e->getMessage();
        }
        return $response;
    }

    public function LiquidaLaborado($array)
    {
        $response = [
            'msg' => '',
            'insert' => false,
        ];

        try {
            $sql = "INSERT INTO `nom_liq_dlab_auxt`
                        (`id_empleado`,`dias_liq`,`val_liq_dias`,`val_liq_auxt`,`aux_alim`,`g_representa`,`horas_ext`,`id_user_reg`,`fec_reg`,`id_nomina`)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $stmt = $this->conexion->prepare($sql);
            $stmt->bindValue(1, $array['id_empleado'], PDO::PARAM_INT);
            $stmt->bindValue(2, $array['dias_laborados'], PDO::PARAM_INT);
            $stmt->bindValue(3, $array['val_laborado'], PDO::PARAM_STR);
            $stmt->bindValue(4, $array['val_aux_trans'], PDO::PARAM_STR);
            $stmt->bindValue(5, $array['val_aux_alim'], PDO::PARAM_STR);
            $stmt->bindValue(6, $array['val_grep'], PDO::PARAM_STR);
            $stmt->bindValue(7, $array['val_horas_ex'], PDO::PARAM_STR);
            $stmt->bindValue(8, Sesion::IdUser(), PDO::PARAM_INT);
            $stmt->bindValue(9, Sesion::Hoy(), PDO::PARAM_STR);
            $stmt->bindValue(10, $array['id_nomina'], PDO::PARAM_INT);
            $stmt->execute();
            $id = $this->conexion->lastInsertId();
            if ($id > 0) {
                $hoy = Sesion::Hoy();
                $idUser = Sesion::IdUser();
                Logs::guardaLog("INSERT INTO `nom_liq_dlab_auxt` (`id_empleado`,`dias_liq`,`val_liq_dias`,`val_liq_auxt`,`aux_alim`,`g_representa`,`horas_ext`,`id_user_reg`,`fec_reg`,`id_nomina`) VALUES ({$array['id_empleado']}, {$array['dias_laborados']}, {$array['val_laborado']}, {$array['val_aux_trans']}, {$array['val_aux_alim']}, {$array['val_grep']}, {$array['val_horas_ex']}, $idUser, '$hoy', {$array['id_nomina']})");
                $response['insert'] = true;
                $response['msg'] = 'Correcto';
            } else {
                $response['msg'] = 'No se insertó el registro';
            }
        } catch (PDOException $e) {
            $response['msg'] = 'Error SQL: ' . $e->getMessage();
        }
        return $response;
    }
    function anulaLiquidacionNomina($id_nomina, $id_empleado = 0)
    {
        $response = [
            'msg' => '',
            'delete' => false,
        ];

        try {
            $sql = "CALL sp_anu_nominaliq(?)";
            $stmt = $this->conexion->prepare($sql);
            $stmt->bindValue(1, $id_nomina, PDO::PARAM_INT);
            $stmt->execute();
            $response['delete'] = true;
            $response['msg'] = 'si';
        } catch (PDOException $e) {
            $response['msg'] = 'Error SQL: ' . $e->getMessage();
        }
        return $response;
    }
}