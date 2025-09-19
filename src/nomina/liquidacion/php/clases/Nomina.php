<?php

namespace Src\Nomina\Liquidacion\Php\Clases;

use Config\Clases\Conexion;
use Config\Clases\Logs;
use Config\Clases\Sesion;

use PDO;
use PDOException;
use Src\Common\Php\Clases\Combos;
use DateTime;
use Exception;

/**
 * Clase para gestionar nominas de los empleados.
 *
 * Esta clase permite realizar operaciones CRUD sobre nominas de los empleados,
 * incluyendo la obtención de registros, adición, edición y eliminación de nominas.
 */
class Nomina
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
                    , IFNULL(`tt`.`inc`,0) AS `inc`
                    , IFNULL(`tt`.`lic`,0) AS `lic`
                    , IFNULL(`tt`.`vac`,0) AS `vac`
                    , DATEDIFF('$fec_fin', '$fec_inicio') + 1 AS `dias_mes`
                    , IF(`obs`.`corte` > 0,  DATEDIFF('$fec_fin', `obs`.`corte`), 0) AS `observacion`
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
                            WHERE `estado` = 1 
                            GROUP BY `id_empleado`)
                        ) AS `ctt`
                        INNER JOIN `nom_empleado` `e` ON (`ctt`.`id_empleado` = `e`.`id_empleado`)
                    WHERE  '$fec_fin' >= `ctt`.`fec_inicio` AND '$fec_inicio' <= `ctt`.`fec_fin`
                    ORDER BY `e`.`no_documento`, `nombre`) AS `taux`
                    LEFT JOIN
                        (SELECT 
                            `id_empleado`
                            , SUM(CASE WHEN `id_tipo` = 1 THEN 1 ELSE 0 END) AS `inc`
                            , SUM(CASE WHEN `id_tipo` = 2 THEN 1 ELSE 0 END) AS `vac`
                            , SUM(CASE WHEN `id_tipo` = 3 THEN 1 ELSE 0 END) AS `lic`
                        FROM 
                            `nom_calendar_novedad`
                        WHERE 
                            `fecha` BETWEEN '$fec_inicio' AND '$fec_fin' AND `id_tipo` IN (1, 2, 3)
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
                            WHERE `estado` = 1 
                            GROUP BY `id_empleado`)
                        ) AS `ctt`
                        INNER JOIN `nom_empleado` `e` ON (`ctt`.`id_empleado` = `e`.`id_empleado`)
                    WHERE  '$fec_fin' >= `ctt`.`fec_inicio` AND '$fec_inicio' <= `ctt`.`fec_fin`
                    ORDER BY `e`.`no_documento`, `nombre`) AS `taux`
                    LEFT JOIN
                        (SELECT 
                            `id_empleado`
                            , SUM(CASE WHEN `id_tipo` = 1 THEN 1 ELSE 0 END) AS `inc`
                            , SUM(CASE WHEN `id_tipo` = 2 THEN 1 ELSE 0 END) AS `vac`
                            , SUM(CASE WHEN `id_tipo` = 3 THEN 1 ELSE 0 END) AS `lic`
                        FROM 
                            `nom_calendar_novedad`
                        WHERE 
                            `fecha` BETWEEN '$fec_inicio' AND '$fec_fin' AND `id_tipo` IN (1, 2, 3)
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
        return !empty($registro) ? $registro['total'] : 0;
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
                            WHERE `estado` = 1 
                            GROUP BY `id_empleado`)
                        ) AS `ctt`
                        INNER JOIN `nom_empleado` `e` ON (`ctt`.`id_empleado` = `e`.`id_empleado`)
                    WHERE  '$fec_fin' >= `ctt`.`fec_inicio` AND '$fec_inicio' <= `ctt`.`fec_fin`
                    ORDER BY `e`.`no_documento`, `nombre`) AS `taux`
                    LEFT JOIN
                        (SELECT 
                            `id_empleado`
                            , SUM(CASE WHEN `id_tipo` = 1 THEN 1 ELSE 0 END) AS `inc`
                            , SUM(CASE WHEN `id_tipo` = 2 THEN 1 ELSE 0 END) AS `vac`
                            , SUM(CASE WHEN `id_tipo` = 3 THEN 1 ELSE 0 END) AS `lic`
                        FROM 
                            `nom_calendar_novedad`
                        WHERE 
                            `fecha` BETWEEN '$fec_inicio' AND '$fec_fin' AND `id_tipo` IN (1, 2, 3)
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
        return !empty($registro) ? $registro['total'] : 0;
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
            $sql = "DELETE FROM `nom_nominas` WHERE `id_nomina` = ?";
            $consulta  = "DELETE FROM `nom_nominas` WHERE `id_nomina` = $id";
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
    public static function addRegistro($mes, $tipo, $incremento = NULL)
    {
        $res['status'] = 'error';
        $data = self::getTipoNomina($tipo);
        $estado = 1;
        try {
            $sql = "INSERT INTO `nom_nominas`
                        (`descripcion`,`mes`,`vigencia`,`tipo`,`estado`,`planilla`,`id_incremento`,`fec_reg`,`id_user_reg`)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $stmt = Conexion::getConexion()->prepare($sql);
            $stmt->bindValue(1, 'LIQUIDACIÓN ' . $data['descripcion'], PDO::PARAM_STR);
            $stmt->bindValue(2, $mes, PDO::PARAM_STR);
            $stmt->bindValue(3, Sesion::Vigencia(), PDO::PARAM_STR);
            $stmt->bindValue(4, $data['codigo'], PDO::PARAM_STR);
            $stmt->bindValue(5, $estado, PDO::PARAM_INT);
            $stmt->bindValue(6, $estado, PDO::PARAM_INT);
            $stmt->bindValue(7, $incremento, PDO::PARAM_INT);
            $stmt->bindValue(8, Sesion::Hoy(), PDO::PARAM_STR);
            $stmt->bindValue(9, Sesion::IdUser(), PDO::PARAM_INT);
            $stmt->execute();
            $id = Conexion::getConexion()->lastInsertId();
            if ($id > 0) {
                $res['status'] = 'si';
                $res['id'] = $id;
            } else {
                $res['msg'] = 'No se insertó el registro.';
            }
            $stmt->closeCursor();
            unset($stmt);
        } catch (PDOException $e) {
            $res['msg'] = 'Error SQL: ' . $e->getMessage();
        }
        return $res;
    }
    /**
     * Actualiza los datos de un registro.
     *
     * @param array $array Datos del registro a actualizar
     * @return string Mensaje de éxito o error
     */
    public function editRegistro($array)
    {
        return 'No se ha definido la edición de registros.';
    }

    public static function getTipoNomina($id)
    {
        $sql = "SELECT `codigo`,`descripcion` FROM `nom_tipo_liquidacion` WHERE `id_tipo` = ?";
        $stmt = Conexion::getConexion()->prepare($sql);
        $stmt->bindParam(1, $id, PDO::PARAM_INT);
        $stmt->execute();
        $data = $stmt->fetch(PDO::FETCH_ASSOC);
        return !empty($data) ? $data : ['codigo' => '', 'descripcion' => ''];
    }

    public static function getIDNomina($mes, $tipo)
    {
        try {
            $sql = "SELECT
                        MAX(`nom_nominas`.`id_nomina`) AS `id_nomina`, `nom_nominas`.`estado`
                    FROM
                        `nom_nominas`
                        INNER JOIN `nom_tipo_liquidacion` 
                            ON (`nom_nominas`.`tipo` = `nom_tipo_liquidacion`.`codigo`)
                    WHERE (`nom_tipo_liquidacion`.`id_tipo` = ? AND `nom_nominas`.`mes` = ?
                        AND `nom_nominas`.`vigencia` = ? AND `nom_nominas`.`estado` > 0)
                    GROUP BY `nom_tipo_liquidacion`.`id_tipo`, `nom_nominas`.`mes`";
            $stmt = Conexion::getConexion()->prepare($sql);
            $stmt->bindParam(1, $tipo, PDO::PARAM_INT);
            $stmt->bindParam(2, $mes, PDO::PARAM_STR);
            $stmt->bindValue(3, Sesion::Vigencia(), PDO::PARAM_STR);
            $stmt->execute();
            $data = $stmt->fetch(PDO::FETCH_ASSOC);
            return !empty($data) ? $data : ['id_nomina' => 0, 'estado' => 0];
        } catch (PDOException $e) {
            return 'Error SQL: ' . $e->getMessage();
        }
    }
    public static function getParamLiq()
    {
        $sql = "SELECT
                    `id_concepto`, `valor` 
                FROM `nom_valxvigencia` WHERE (`id_vigencia` = ?) 
                ORDER BY `id_concepto` ASC";
        $stmt = Conexion::getConexion()->prepare($sql);
        $stmt->bindValue(1, Sesion::IdVigencia(), PDO::PARAM_INT);
        $stmt->execute();
        $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $stmt->closeCursor();
        unset($stmt);
        return $result;
    }
}
