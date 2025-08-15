<?php

namespace Src\Nomina\Liquidacion\Php\Clases;

use Config\Clases\Conexion;
use Config\Clases\Logs;
use Config\Clases\Sesion;

use PDO;
use PDOException;
use Src\Nomina\Empleados\Php\Clases\Contratos;
use Src\Nomina\Empleados\Php\Clases\Embargos;
use Src\Nomina\Empleados\Php\Clases\Empleados;
use Src\Nomina\Empleados\Php\Clases\Incapacidades;
use Src\Nomina\Empleados\Php\Clases\Indemniza_Vacacion;
use Src\Nomina\Empleados\Php\Clases\Ivivienda;
use Src\Nomina\Empleados\Php\Clases\Libranzas;
use Src\Nomina\Empleados\Php\Clases\Licencias_Luto;
use Src\Nomina\Empleados\Php\Clases\Licencias_MoP;
use Src\Nomina\Empleados\Php\Clases\Licencias_Norem;
use Src\Nomina\Empleados\Php\Clases\Otros_Descuentos;
use Src\Nomina\Empleados\Php\Clases\Sindicatos;
use Src\Nomina\Empleados\Php\Clases\Vacaciones;
use Src\Nomina\Horas_extra\Php\Clases\Horas_Extra;
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
                            , SUM(CASE WHEN `id_tipo` IN (3, 4, 5) THEN 1 ELSE 0 END) AS `lic`
                        FROM 
                            `nom_calendar_novedad`
                        WHERE 
                            `fecha` BETWEEN '$fec_inicio' AND '$fec_fin' AND `id_tipo` IN (1, 2, 3, 4, 5)
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
                            , SUM(CASE WHEN `id_tipo` IN (3, 4, 5) THEN 1 ELSE 0 END) AS `lic`
                        FROM 
                            `nom_calendar_novedad`
                        WHERE 
                            `fecha` BETWEEN '$fec_inicio' AND '$fec_fin' AND `id_tipo` IN (1, 2, 3, 4, 5)
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
                            , SUM(CASE WHEN `id_tipo` IN (3, 4, 5) THEN 1 ELSE 0 END) AS `lic`
                        FROM 
                            `nom_calendar_novedad`
                        WHERE 
                            `fecha` BETWEEN '$fec_inicio' AND '$fec_fin' AND `id_tipo` IN (1, 2, 3, 4, 5)
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
            $sql = "DELETE FROM `nom_horas_ex_trab` WHERE `id_intv` = ?";
            $consulta  = "DELETE FROM `nom_horas_ex_trab` WHERE `id_intv` = $id";
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
    public function addRegistro($array)
    {
        $ids =          $array['chk_liquidacion'];
        $laborado =     $array['lab'];
        $mpago =        $array['metodo'];
        $tipo =         $array['tipo'];
        $mes =          $array['mes'];
        $incremento =   isset($array['incremento']) ? $array['incremento'] : NULL;
        $nomina =       Nomina::getIDNomina($mes, $tipo);

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

        $param = array_column($data, 'valor', 'id_concepto');

        if (empty($param[1]) || empty($param[6])) {
            return 'No se han Configurado los parámetros de liquidación.';
        }

        $inicia = Sesion::Vigencia() . '-' . $mes . '-01';
        $fin = date('Y-m-t', strtotime($inicia));

        $Empleado =     new Empleados();
        $empleados =    $Empleado->getEmpleados();
        $salarios =     $Empleado->getSalarioMasivo($mes);
        $salarios =     array_column($salarios, 'basico', 'id_empleado');
        $terceros_ss =  $Empleado->getRegistro();
        $empresa =      (new Usuario())->getEmpresa();
        //Devengados
        $horas =            (new Horas_Extra())->getHorasPorMes($inicia, $fin);
        $incapacidades =    (new Incapacidades())->getRegistroPorEmpleado($inicia, $fin);
        $vacaciones =       (new Vacaciones())->getRegistroPorEmpleado($inicia, $fin);
        $licenciasMP =      (new Licencias_MoP())->getRegistroPorEmpleado($inicia, $fin);
        $licenciaNR =       (new Licencias_Norem())->getRegistroPorEmpleado($inicia, $fin);
        $licenciaLuto =     (new Licencias_Luto())->getRegistroPorEmpleado($inicia, $fin);
        $indemVacaciones =  (new Indemniza_Vacacion())->getRegistroPorEmpleado($inicia, $fin);

        //Deducidos
        $libranzas =    (new Libranzas())->getLibranzasPorEmpleado();
        $embargos =     (new Embargos())->getRegistroPorEmpleado();
        $sindicatos =   (new Sindicatos())->getRegistroPorEmpleado();
        $otrosDctos =   (new Otros_Descuentos())->getRegistroPorEmpleado();

        //otros 
        $cortes =       (self::getCortes($ids));
        $iVivienda =    (new Ivivienda())->getIviviendaEmpleados($ids);
        $liquidados =   (self::getEmpleadosLiq($id_nomina, $ids));
        $liquidados =   array_column($liquidados, 'id_sal_liq', 'id_empleado');
        $error = '';

        foreach ($ids as $id_empleado) {
            if (!(isset($liquidados[$id_empleado]) && isset($salarios[$id_empleado]))) {
                $filtro = [];
                $filtro = array_filter($terceros_ss, function ($terceros_ss) use ($id_empleado) {
                    return $terceros_ss["id_empleado"] == $id_empleado;
                });

                $novedad = array_column($filtro, 'id_tercero', 'id_tipo');
                if (!(isset($novedad[1]) && isset($novedad[2]) && isset($novedad[3]) && isset($novedad[4]))) {
                    $error .= "<p>ID: $id_empleado, no tiene registrado seguridad social</p>";
                    continue;
                }

                //$this->conexion->beginTransaction();

                $insert = true;

                //liquidar Horas extras
                $filtro = [];
                $filtro = array_filter($horas, function ($horas) use ($id_empleado) {
                    return $horas["id_empleado"] == $id_empleado;
                });
                $valHora = $salarios[$id_empleado] / 230;
                $valTotalHe = 0;
                if (!empty($filtro)) {
                    $response = $this->LiquidaHorasExtra($filtro, $id_nomina, $valHora);
                    $insert = $response['insert'];
                    $valTotalHe = $response['valor'];
                    if (!$insert) {
                        $error .= "<p>ID: $id_empleado, Error al liquidar horas extras: {$response['msg']}</p>";
                    }
                }

                if (!$insert) {
                    $this->conexion->rollBack();
                    continue;
                }

                //liquidar incapacidades
                $valorDia = $salarios[$id_empleado] / 30;
                $valTotIncap = 0;
                $filtro = [];
                $filtro = array_filter($incapacidades, function ($incapacidades) use ($id_empleado) {
                    return $incapacidades["id_empleado"] == $id_empleado;
                });
                if (!empty($filtro)) {
                }
            }
        }
        if ($error != '') {
            return $error;
        } else {
            return 'Todos los empleados tienen las novedades registradas correctamente.';
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
                    $consulta = "UPDATE `nom_horas_ex_trab` 
                                SET `fec_actu` = ? 
                            WHERE `id_he_trab` = ?";
                    $stmt2 = $this->conexion->prepare($consulta);
                    $stmt2->bindValue(1, Sesion::Hoy(), PDO::PARAM_STR);
                    $stmt2->bindValue(2, $id, PDO::PARAM_INT);
                    $stmt2->execute();
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

    public function getIdHoraExtra($array) {}

    public static function getEmpleadosLiq($id_nomina, $ids)
    {
        if (empty($ids)) {
            return [];
        } else {
            $ids = implode(',', $ids);
        }
        try {
            $sql = "SELECT `id_empleado`,`id_sal_liq`
                    FROM `nom_liq_salario`
                    WHERE (`id_nomina` = ? AND `id_empleado` IN ($ids))";
            $stmt = Conexion::getConexion()->prepare($sql);
            $stmt->bindParam(1, $id_nomina, PDO::PARAM_INT);
            $stmt->execute();
            $res  = $stmt->fetchAll(PDO::FETCH_ASSOC);
            return !empty($res) ? $res : [];
        } catch (PDOException $e) {
            return 'Error SQL: ' . $e->getMessage();
        }
    }
    public static function getCortes($empleados)
    {
        if (empty($empleados)) {
            return [];
        } else {
            $empleados = implode(',', $empleados);
        }
        try {
            $sql = "SELECT 
                        `nom_empleado`.`id_empleado`
                        , `nom_empleado`.`representacion`
                        , `t1`.`val_bsp`
                        , `t1`.`mes`
                        , `t1`.`anio`
                        , `t2`.`corte_ces`
                        , `t3`.`val_liq_ps`
                        , `t3`.`corte_prim_sv`
                        , `t4`.`val_liq_pv`
                        , `t4`.`corte_prim_nav`
                        , `t5`.`corte` AS `corte_vac`
                        , `t5`.`val_liq`
                        , `t5`.`val_prima_vac`
                        , `t5`.`val_bon_recrea`
                    FROM
                        `nom_empleado`
                        LEFT JOIN  
                            (SELECT
                                `tbsp`.`id_empleado`
                                , CASE
                                    WHEN `tbsp`.`fec_comp1` > IFNULL(`tbspra`.`fec_comp2`,'1900-01-01') THEN `tbsp`.`val_bsp`
                                    ELSE IFNULL(`tbsp`.`val_bsp`,0) + IFNULL(`tbspra`.`val_bsp_ra`,0)
                                END AS `val_bsp`
                                , RIGHT(`tbsp`.`fec_comp1`, 2) AS `mes` 
                                , LEFT(`tbsp`.`fec_comp1`, 4) AS `anio`
                            FROM	
                                (SELECT
                                    `id_empleado`
                                    , `val_bsp`
                                    , CONCAT(`anio`,`mes`) AS `fec_comp1` 
                                FROM `nom_liq_bsp`
                                WHERE `id_bonificaciones` IN
                                    (SELECT
                                        MAX(`nom_liq_bsp`.`id_bonificaciones`)
                                    FROM
                                        `nom_liq_bsp`
                                        INNER JOIN `nom_nominas` 
                                        ON (`nom_liq_bsp`.`id_nomina` = `nom_nominas`.`id_nomina`)
                                    WHERE (`nom_nominas`.`vigencia` <= :vigencia AND `nom_nominas`.`estado` = 5 AND `nom_nominas`.`tipo` = 'N')
                                    GROUP BY `nom_liq_bsp`.`id_empleado`)) AS `tbsp`
                                LEFT JOIN
                                    (SELECT
                                        `nom_liq_bsp`.`id_empleado`
                                        , `nom_liq_bsp`.`val_bsp` AS `val_bsp_ra`
                                        , DATE_FORMAT(`nom_retroactivos`.`fec_final`, '%Y%m') AS `fec_comp2`
                                    FROM `nom_liq_bsp`
                                        INNER JOIN `nom_nominas`
                                            ON(`nom_liq_bsp`.`id_nomina` = `nom_nominas`.`id_nomina`)
                                        LEFT JOIN `nom_retroactivos`
                                            ON(`nom_retroactivos`.`id_incremento` = `nom_nominas`.`id_incremento`)
                                    WHERE `nom_liq_bsp`.`id_bonificaciones` IN
                                        (SELECT
                                            MAX(`nom_liq_bsp`.`id_bonificaciones`)
                                        FROM
                                            `nom_liq_bsp`
                                            INNER JOIN `nom_nominas` 
                                            ON (`nom_liq_bsp`.`id_nomina` = `nom_nominas`.`id_nomina`)
                                        WHERE (`nom_nominas`.`vigencia` <= :vigencia AND `nom_nominas`.`estado` = 5 AND `nom_nominas`.`tipo` = 'RA')
                                        GROUP BY `nom_liq_bsp`.`id_empleado`)) `tbspra`
                                    ON(`tbsp`.`id_empleado` = `tbspra`.`id_empleado`)) AS `t1`
                            ON (`t1`.`id_empleado` = `nom_empleado`.`id_empleado`)
                        LEFT JOIN 
                            (SELECT 
                                `id_empleado`,`corte` AS `corte_ces`
                            FROM `nom_liq_cesantias`
                            WHERE `id_liq_cesan`  IN 
                                (SELECT 
                                    MAX(`id_liq_cesan`) 
                                FROM `nom_liq_cesantias`
                                    INNER JOIN `nom_nominas`
                                        ON (`nom_liq_cesantias`.`id_nomina` = `nom_nominas`.`id_nomina`)
                                WHERE `nom_nominas`.`tipo` = 'CE' AND `nom_nominas`.`vigencia` <= :vigencia AND `nom_nominas`.`estado` = 5
                                GROUP BY `id_empleado`)) AS `t2`
                                ON (`nom_empleado`.`id_empleado` = `t2`.`id_empleado`)
                        LEFT JOIN
                            (SELECT 
                                `tpv`.`id_empleado`
                                , CASE 
                                    WHEN `tpv`.`corte_pv` > IFNULL(`tra`.`corte_ra`,'1900-01-01') THEN IFNULL(`tpv`.`val_liq_pv`,0)
                                    ELSE IFNULL(`tpv`.`val_liq_pv`,0) + IFNULL(`tra`.`val_liq_ra`,0)
                                END AS `val_liq_ps`
                                , `tpv`.`corte_pv` AS `corte_prim_sv`
                            FROM
                                (SELECT   
                                    `id_empleado`
                                    , `val_liq_ps` AS `val_liq_pv`
                                    , `corte` AS `corte_pv`
                                FROM `nom_liq_prima` 
                                WHERE `id_liq_prima` IN 
                                    (SELECT
                                        MAX(`id_liq_prima`) AS `id_lp`
                                    FROM
                                        `nom_liq_prima`
                                        INNER JOIN `nom_nominas`
                                            ON (`nom_liq_prima`.`id_nomina` = `nom_nominas`.`id_nomina`)
                                    WHERE `nom_nominas`.`tipo` = 'PV' AND `nom_nominas`.`vigencia` <= :vigencia AND `nom_nominas`.`estado` = 5
                                    GROUP BY `id_empleado`)) AS `tpv`
                                LEFT JOIN 
                                    (SELECT   
                                        `id_empleado`
                                        , `val_liq_ps` AS `val_liq_ra`
                                        , `nom_retroactivos`.`fec_final` AS `corte_ra`
                                    FROM `nom_liq_prima` 
                                        INNER JOIN `nom_nominas`
                                            ON(`nom_liq_prima`.`id_nomina` = `nom_nominas`.`id_nomina`)
                                        LEFT JOIN `nom_retroactivos`
                                            ON(`nom_retroactivos`.`id_incremento` = `nom_nominas`.`id_incremento`)
                                    WHERE `id_liq_prima` IN 
                                            (SELECT
                                                MAX(`id_liq_prima`) AS `id_lp`
                                            FROM
                                                `nom_liq_prima`
                                                INNER JOIN `nom_nominas`
                                                    ON (`nom_liq_prima`.`id_nomina` = `nom_nominas`.`id_nomina`)
                                            WHERE `nom_nominas`.`tipo` = 'RA' AND `nom_nominas`.`vigencia` <= :vigencia AND `nom_nominas`.`estado` = 5
                                            GROUP BY `id_empleado`)) AS `tra`
                                            ON (`tpv`.`id_empleado` = `tra`.`id_empleado`)) AS `t3`
                                ON (`nom_empleado`.`id_empleado` = `t3`.`id_empleado`)
                        LEFT JOIN 
                            (SELECT 
                                `id_empleado`,`val_liq_pv`,`corte` AS `corte_prim_nav`
                            FROM `nom_liq_prima_nav`
                            WHERE `id_liq_privac` IN 
                                    (SELECT 
                                        MAX(`id_liq_privac`) 
                                    FROM `nom_liq_prima_nav` 
                                        INNER JOIN `nom_nominas` 
                                            ON (`nom_liq_prima_nav`.`id_nomina` = `nom_nominas`.`id_nomina`)
                                    WHERE `nom_nominas`.`tipo` = 'PN' AND `nom_nominas`.`vigencia` <= :vigencia AND `nom_nominas`.`estado` = 5
                                    GROUP BY `id_empleado`)) AS `t4`
                                ON (`nom_empleado`.`id_empleado` = `t4`.`id_empleado`)
                        LEFT JOIN 
                            (SELECT 
                                `tvc`.`id_empleado`
                                , CASE
                                    WHEN `tvc`.`corte` > IFNULL(`travc`.`fec_final`,'1900-01-01') THEN IFNULL(`tvc`.`val_prima_vac`,0)
                                                    ELSE IFNULL(`tvc`.`val_prima_vac`,0) + IFNULL(`travc`.`val_prima_vac_racv`,0)
                                                END AS `val_prima_vac`
                                    , CASE
                                    WHEN `tvc`.`corte` > IFNULL(`travc`.`fec_final`,'1900-01-01') THEN IFNULL(`tvc`.`val_liq`,0)
                                                    ELSE IFNULL(`tvc`.`val_liq`,0) + IFNULL(`travc`.`val_liq_racv`,0)
                                                END AS `val_liq`
                                    , CASE
                                    WHEN `tvc`.`corte` > IFNULL(`travc`.`fec_final`,'1900-01-01') THEN IFNULL(`tvc`.`val_bon_recrea`,0)
                                                    ELSE IFNULL(`tvc`.`val_bon_recrea`,0) + IFNULL(`travc`.`val_bon_recrea_racv`,0)
                                                END AS `val_bon_recrea`
                                    , `tvc`.`corte`
                            FROM 
                                (SELECT
                                    `nom_vacaciones`.`id_empleado`
                                    , `nom_liq_vac`.`val_prima_vac`
                                    , `nom_liq_vac`.`val_liq`
                                    , `nom_liq_vac`.`val_bon_recrea`
                                    , `nom_vacaciones`.`corte` 
                                FROM
                                    `nom_liq_vac`
                                    INNER JOIN `nom_nominas` 
                                        ON (`nom_liq_vac`.`id_nomina` = `nom_nominas`.`id_nomina`)
                                    INNER JOIN `nom_vacaciones` 
                                        ON (`nom_liq_vac`.`id_vac` = `nom_vacaciones`.`id_vac`)
                                WHERE `nom_liq_vac`.`id_liq_vac` IN
                                        (SELECT
                                            MAX(`nom_liq_vac`.`id_liq_vac`) 
                                        FROM
                                            `nom_liq_vac`
                                            INNER JOIN `nom_nominas` 
                                            ON (`nom_liq_vac`.`id_nomina` = `nom_nominas`.`id_nomina`)
                                            INNER JOIN `nom_vacaciones` 
                                            ON (`nom_liq_vac`.`id_vac` = `nom_vacaciones`.`id_vac`)
                                        WHERE `nom_nominas`.`vigencia` <= :vigencia AND (`nom_nominas`.`tipo` = 'VC' OR `nom_nominas`.`tipo` = 'N') AND `nom_nominas`.`estado` = 5
                                        GROUP BY `nom_vacaciones`.`id_empleado`)) AS `tvc` 
                                LEFT JOIN
                                    (SELECT
                                        `nom_vacaciones`.`id_empleado`
                                        , `nom_liq_vac`.`val_prima_vac` AS `val_prima_vac_racv`
                                        , `nom_liq_vac`.`val_liq` AS `val_liq_racv`
                                        , `nom_liq_vac`.`val_bon_recrea` AS `val_bon_recrea_racv`
                                        , `nom_retroactivos`.`fec_final`
                                    FROM
                                        `nom_liq_vac`
                                        INNER JOIN `nom_nominas` 
                                            ON (`nom_liq_vac`.`id_nomina` = `nom_nominas`.`id_nomina`)
                                        INNER JOIN `nom_vacaciones` 
                                            ON (`nom_liq_vac`.`id_vac` = `nom_vacaciones`.`id_vac`)
                                        LEFT JOIN `nom_retroactivos`
                                            ON(`nom_retroactivos`.`id_incremento` = `nom_nominas`.`id_incremento`)
                                    WHERE `nom_liq_vac`.`id_liq_vac` IN
                                        (SELECT
                                            MAX(`nom_liq_vac`.`id_liq_vac`) 
                                        FROM
                                            `nom_liq_vac`
                                            INNER JOIN `nom_nominas` 
                                            ON (`nom_liq_vac`.`id_nomina` = `nom_nominas`.`id_nomina`)
                                            INNER JOIN `nom_vacaciones` 
                                            ON (`nom_liq_vac`.`id_vac` = `nom_vacaciones`.`id_vac`)
                                        WHERE `nom_nominas`.`vigencia` <= :vigencia AND `nom_nominas`.`tipo` = 'RA' AND `nom_nominas`.`estado` = 5
                                        GROUP BY `nom_vacaciones`.`id_empleado`)) AS `travc`
                                    ON(`travc`.`id_empleado` = `tvc`.`id_empleado`)) AS `t5`
                                ON (`nom_empleado`.`id_empleado` = `t5`.`id_empleado`)
                    WHERE `nom_empleado`.`id_empleado` IN ($empleados)";
            $stmt = Conexion::getConexion()->prepare($sql);
            $stmt->bindValue(':vigencia', Sesion::Vigencia());
            $stmt->execute();
            $res  = $stmt->fetchAll(PDO::FETCH_ASSOC);
            return !empty($res) ? $res : [];
        } catch (PDOException $e) {
            return 'Error SQL: ' . $e->getMessage();
        }
    }

    public function LiquidaHorasExtra($filtro, $id_nomina, $valHora)
    {
        $response = [
            'msg' => '',
            'insert' => true,
            'valor' => 0
        ];
        foreach ($filtro as $f) {
            $idHe =     $f['id_he_trab'];
            $valhe =    $valHora * $f['factor'] * $f['cantidad_he'];
            $data = [
                'id' => $idHe,
                'valor' => $valhe,
                'id_nomina' => $id_nomina
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

    public function LiquidaIncapacidad($filtro, $id_nomina, $valDia)
    {
        $response = [
            'msg' => '',
            'insert' => true,
            'valor' => 0
        ];
        foreach ($filtro as $f) {
            $idIncap =     $f['id_incapacidad'];
            $idTipo =      $f['id_tipo'];
            $categoria =   $f['categoria'];
            $liquidado =   $f['liq'];
            $dias =        $f['dias'];

            $data = [
                'id' => $idIncap,
                'valor' => '',
                'id_nomina' => $id_nomina
            ];
            $res = (new Horas_Extra($this->conexion))->addRegistroLiq($data);
            if ($res != 'si') {
                $response['insert'] = false;
                $response['msg'] = "<p>$res</p>";
                break;
            } else {
                $response['valor'] += '';
            }
        }
        return $response;
    }
}
