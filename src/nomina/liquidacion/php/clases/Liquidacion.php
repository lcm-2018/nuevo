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
use Src\Nomina\Empleados\Php\Clases\Prestaciones_Sociales;
use Src\Nomina\Empleados\Php\Clases\Primas;
use Src\Nomina\Empleados\Php\Clases\Seguridad_Social;
use Src\Nomina\Empleados\Php\Clases\Sindicatos;
use Src\Nomina\Empleados\Php\Clases\Vacaciones;
use Src\Nomina\Empleados\Php\Clases\Valores_Liquidacion;
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
                            , SUM(CASE WHEN `id_tipo` = 6 THEN 1 ELSE 0 END) AS `ivac`
                            , GROUP_CONCAT(DISTINCT CASE WHEN `id_tipo` = 2 THEN `id_novedad` END) AS `ids_vac`
                        FROM 
                            `nom_calendar_novedad`
                        WHERE 
                            `fecha` BETWEEN '$fec_inicio' AND '$fec_fin' AND `id_tipo` IN (1, 2, 3, 4, 5, 6)
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
        $stmt->closeCursor();
        unset($stmt);
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
        $stmt->closeCursor();
        unset($stmt);
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
    public function addRegistro($array, $opcion = 0)
    {
        $ids =          $array['chk_liquidacion'];
        $contratos =    $array['id_contrato'];
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

        $parametro = array_column($data, 'valor', 'id_concepto');

        if (empty($parametro[1]) || empty($parametro[6])) {
            return 'No se han Configurado los parámetros de liquidación.';
        }

        $inicia = Sesion::Vigencia() . '-' . $mes . '-01';
        $fin = date('Y-m-t', strtotime($inicia));

        $Empleado =     new Empleados();
        $empleados =    array_column($Empleado->getEmpleados(), null, 'id_empleado');
        $salarios =     $Empleado->getSalarioMasivo($mes);
        $salarios =     array_column($salarios, 'basico', 'id_empleado');
        $terceros_ss =  $Empleado->getRegistro();
        $empresa =      (new Usuario())->getEmpresa();
        //Devengados
        $horas =            (new Horas_Extra())->getHorasPorMes($inicia, $fin);
        $incapacidades =    (new Incapacidades())->getRegistroPorEmpleado($inicia, $fin);
        $vacaciones =       (new Vacaciones())->getRegistroPorEmpleado($inicia, $fin);
        $vacPagadas =       (new Vacaciones())->getRegistroPago($inicia, $fin);
        $licenciasMP =      (new Licencias_MoP())->getRegistroPorEmpleado($inicia, $fin);
        $licenciaNR =       (new Licencias_Norem())->getRegistroPorEmpleado($inicia, $fin);
        $licenciaLuto =     (new Licencias_Luto())->getRegistroPorEmpleado($inicia, $fin);
        $indemVacaciones =  (new Indemniza_Vacacion())->getRegistroPorEmpleado($inicia, $fin);
        $bonificaciones =   (new Bsp())->getRegistroPorEmpleado();

        //Deducidos
        $libranzas =    (new Libranzas())->getLibranzasPorEmpleado($inicia);
        $embargos =     (new Embargos())->getRegistroPorEmpleado($inicia);
        $sindicatos =   (new Sindicatos())->getRegistroPorEmpleado($inicia);
        $otrosDctos =   (new Otros_Descuentos())->getRegistroPorEmpleado($inicia, $fin);

        //otros 
        $cortes =       array_column((self::getCortes($ids, $fin)), null, 'id_empleado');
        $iVivienda =    (new Ivivienda())->getIviviendaEmpleados($ids);
        $iVivienda =    array_column($iVivienda, 'valor', 'id_empleado');
        $liquidados =   (self::getEmpleadosLiq($id_nomina, $ids));
        $liquidados =   array_column($liquidados, 'id_sal_liq', 'id_empleado');
        $error = '';

        if ($opcion == 0) {
            $param['smmlv'] =           $parametro[1];
            $param['uvt'] =             $parametro[6];
            $param['base_bsp'] =        $parametro[7];
            $param['grep'] =            $parametro[8];
            $param['base_alim'] =       $parametro[9];
            $param['min_vital'] =       $parametro[10] ?? 0;
            $param['id_nomina'] =       $id_nomina;
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

                    $cortes_empleado =  $cortes[$id_empleado] ?? [];
                    if (!$this->conexion->inTransaction()) {
                        $this->conexion->beginTransaction();
                    }

                    if ($opcion == 0) {
                        $param['id_empleado'] =     $id_empleado;
                        $param['salario'] =         $salarios[$id_empleado];
                        $param['tiene_grep'] =      $cortes_empleado['tiene_grep'] ?? 0;
                        $param['bsp_ant'] =         $cortes_empleado['val_bsp'] ?? 0;
                        $param['pri_ser_ant'] =     $cortes_empleado['val_liq_ps'] ?? 0;
                        $param['pri_vac_ant'] =     $cortes_empleado['val_liq_pv'] ?? 0;
                        $param['pri_nav_ant'] =     $cortes_empleado['val_liq'] ?? 0;
                        $param['prom_horas'] =      $cortes_empleado['prom'] ?? 0;
                    } else if ($opcion == 1) {
                        $param = (new Valores_Liquidacion($this->conexion))->getRegistro($id_nomina, $id_empleado);
                    }

                    $param['aux_trans'] =   $salarios[$id_empleado] <= $param['smmlv'] * 2 ? $parametro[2] : 0;
                    $param['aux_alim'] =    $salarios[$id_empleado] <= $param['base_alim'] ? $parametro[3] : 0;
                    $tipo_emp =             $empleados[$id_empleado]['tipo_empleado'];
                    $subtipo_emp =          $empleados[$id_empleado]['subtipo_empleado'];

                    if ($tipo_emp == 12 || $tipo_emp == 8) {
                        $param['aux_trans'] =   0;
                        $param['aux_alim'] =    0;
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
                    $valTotVac =        0;
                    $valTotPrimVac =    0;
                    $valBonRec =        0;

                    $filtro = $vacaciones[$id_empleado][0] ?? [];
                    if (!empty($filtro)) {
                        $Vcc = new Vacaciones($this->conexion);
                        $rt = $Vcc->getRegistroLiq(['id_empleado' => $id_empleado, 'id_nomina' => $id_nomina]);
                        if (!empty($rt) && $rt['tipo'] == 'M') {
                            $valTotVac =        $rt['val_vac'];
                            $valTotPrimVac =    $rt['prima_vac'];
                            $valBonRec =        $rt['bon_recrea'];
                        } else {
                            $response =         $this->LiquidaVacaciones($filtro, $param);
                            $valTotVac =        $response['valor'];
                            $valTotPrimVac =    $response['prima'];
                            $valBonRec =        $response['bono'];
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
                            $dBsp           = $bonificaciones[$id_empleado];
                            $valTotalBSP    = $dBsp['val_bsp'];
                            $data = [
                                'numValor'      => $dBsp['val_bsp'],
                                'datFecCorte'   => $dBsp['fec_corte'],
                                'tipo'          => 'P',
                                'id'            => $dBsp['id_bonificaciones'],
                            ];
                            (new Bsp($this->conexion))->editRegistro($data);
                        } else {
                            $fecha_corte = $cortes_empleado['val_bsp']  == '' ? $cortes_empleado['inicia_ctt'] : $cortes_empleado['corte_bsp'];
                            //verificar si hay 360 día para la bonificiacion sacandolo los dias entre fecha_corte y fecha_fin
                            $tiene_bsp = (strtotime($fin) - strtotime($fecha_corte)) / (60 * 60 * 24) >= 360;
                            if ($tiene_bsp) {
                                $param['corte'] = $fecha_corte;
                                $response = $this->LiquidaBSP($param);
                                $valTotalBSP = $response['valor'];
                                if (!$response['insert']) {
                                    throw new Exception("BSP: {$response['msg']}");
                                }
                            }
                        }
                    }

                    //laborado 
                    $valTotalLab = $laborado[$id_empleado] * ($param['salario'] / 30);
                    $valAuxTrans = $laborado[$id_empleado] * ($param['aux_trans'] / 30);
                    $valAuxAlim = $laborado[$id_empleado] * ($param['aux_alim'] / 30);
                    $grepre = $empleados[$id_empleado]['representacion'] == 1 ? $parametro[8] : 0;

                    $Otros = new Otros();
                    $labd = $Otros->getRegistroLiq(['id_empleado' => $id_empleado, 'id_nomina' => $id_nomina]);
                    if (!empty($labd) && $labd['tipo'] == 'M') {
                        $valTotalLab    = $labd['val_laborado'];
                        $valAuxTrans    = $labd['val_auxtrans'];
                        $valAuxAlim     = $labd['auxalim'];
                        $grepre         = $labd['grepre'];
                    } else {
                        $data = [
                            'id_empleado'       =>  $id_empleado,
                            'dias_laborados'    =>  $laborado[$id_empleado],
                            'val_laborado'      =>  $valTotalLab,
                            'val_aux_trans'     =>  $valAuxTrans,
                            'val_aux_alim'      =>  $valAuxAlim,
                            'val_grep'          =>  $grepre,
                            'val_horas_ex'      =>  $valTotalHe,
                            'id_nomina'         =>  $id_nomina,
                        ];
                        $response = $this->LiquidaLaborado($data);
                        if (!$response['insert']) {
                            throw new Exception("Laborado: {$response['msg']}");
                        }
                    }
                    //Seguridad social
                    if ($empleados[$id_empleado]['salario_integral'] == 1) {
                        $ibc = $valTotalLab * 0.7;
                    } else {
                        $ibc = $valTotalLab + $valTotalHe + $valTotIncap + $valTotalBSP + $grepre + $valTotLicLuto + $valTotLicMP + $valTotVacIbc;
                    }

                    $response = $this->LiquidaSeguridadSocial($param, $novedad, $ibc, $tipo_emp, $subtipo_emp, $laborado[$id_empleado]);
                    $valTotSegSoc = $response['valor'];
                    if (!$response['insert']) {
                        throw new Exception("Seguridad social: {$response['msg']}");
                    }

                    //Parafiscales
                    $ibc = $ibc - $valTotIncap;
                    $response = $this->LiquidaParafiscales($param, $ibc, $empresa['exonera_aportes'], $tipo_emp);
                    if (!$response['insert']) {
                        throw new Exception("Parafiscales: {$response['msg']}");
                    }

                    //Apropiaciones: Vacaciones, Prima de Vacaciones, bonificacion de recreacion, Prima de Servicios, Prima de navidad, Cesantias, Int. Cesantias.
                    //Reserva vacaciones

                    $filtro = [
                        'id_vac' => 0,
                        'dias_habiles'  => 15,
                        'dias_inactivo' => 22,
                        'dias_liquidar' => $laborado[$id_empleado],
                        'corte' => '',
                        'id_nomina' => 0,
                    ];
                    $response       =   $this->LiquidaVacaciones($filtro, $param, 0);
                    $valMesVac      =   $response['valor'];
                    $valMesPrimVac  =   $response['prima'];
                    $valMesBonRec   =   $response['bono'];
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
                        'id_empleado'           =>  $id_empleado,
                        'id_nomina'             =>  $id_nomina,
                        'val_vacacion'          =>  $valMesVac,
                        'val_cesantia'          =>  $valMesCes,
                        'val_interes_cesantia'  =>  $valMesIntCes,
                        'val_prima'             =>  $valMesPriSer,
                        'val_prima_vac'         =>  $valMesPrimVac,
                        'val_prima_nav'         =>  $valMesPriNav,
                        'val_bonifica_recrea'   =>  $valMesBonRec,
                    ];
                    $response = (new Prestaciones_Sociales($this->conexion))->addRegistroLiq($data);
                    if ($response != 'si') {
                        throw new Exception("Prestaciones sociales: $response");
                    }

                    $baseDctos = $valTotalLab + $valAuxTrans + $valAuxAlim + $valTotalHe + $valTotIncap + $valTotVac + $valTotLicMP + $valTotLicLuto + $valTotalBSP + $valTotPrimVac + $valBonRec + $grepre + $valTotIndemVac - ($valTotSegSoc ?? 0);

                    //Deducciones

                    //embargos
                    $filtro = $embargos[$id_empleado] ?? [];
                    if (!empty($filtro)) {
                        $response = $this->LiquidaEmbargos($filtro, $param, $baseDctos);
                        $baseDctos  = $baseDctos - $response['valor'];
                        if (!$response['insert']) {
                            throw new Exception("Embargos: {$response['msg']}");
                        }
                    }

                    //sindicatos
                    $filtro = $sindicatos[$id_empleado][0] ?? [];
                    if (!empty($filtro)) {
                        $response = $this->LiquidaSindicato($filtro, $param, $baseDctos);
                        $baseDctos  = $baseDctos - $response['valor'];
                        if (!$response['insert']) {
                            throw new Exception("Sindicatos: {$response['msg']}");
                        }
                    }
                    //libranzas
                    $filtro = $libranzas[$id_empleado] ?? [];
                    if (!empty($filtro)) {
                        $response = $this->LiquidaLibranzas($filtro, $param, $baseDctos);
                        $baseDctos  = $baseDctos - $response['valor'];
                        if (!$response['insert']) {
                            throw new Exception("Libranzas: {$response['msg']}");
                        }
                    }

                    //otros descuentos
                    $filtro = $otrosDctos[$id_empleado] ?? [];
                    if (!empty($filtro)) {
                        $response = $this->LiquidaOtrosDctos($filtro, $param, $baseDctos);
                        $baseDctos  = $baseDctos - $response['valor'];;
                        if (!$response['insert']) {
                            throw new Exception("Otros descuentos: {$response['msg']}");
                        }
                    }

                    $baseDep = $valTotalLab + $valTotalBSP + $valTotalHe + $valTotVac + $valTotPrimVac + $valBonRec + $grepre;
                    $pagoxdependiente = $empleados[$id_empleado]['dependientes'] == 0 ? 0 : $baseDep * 0.1;
                    $valIntViv = $iVivienda[$id_empleado] ?? 0;
                    $valrf = $baseDep + $valTotIndemVac + $valTotLicLuto - ($valTotSegSoc ?? 0) - $pagoxdependiente - $valIntViv;
                    $valdpurado =  $valrf * 0.75;
                    $uvt = $param['uvt'];
                    $ingLabUvt = $empleados[$id_empleado]['salario_integral'] == 1 ? $valTotalLab * 0.75 / $uvt :  $valdpurado / $uvt;

                    $totValRetFte = 0;
                    $data = [
                        'id_empleado'   =>  $id_empleado,
                        'id_nomina'     =>  $id_nomina,
                        'base'          =>  $valdpurado,
                        'ing_uvt'       =>  $ingLabUvt,
                        'uvt'           =>  $uvt,
                    ];
                    $response = $this->LiquidaRetencionFuente($data);
                    $totValRetFte = $response['valor'];
                    if (!$response['insert']) {
                        throw new Exception("Retención en la fuente: {$response['msg']}");
                    }

                    $neto = $baseDctos - $totValRetFte;
                    $data = [
                        'id_empleado'   =>  $id_empleado,
                        'id_nomina'     =>  $id_nomina,
                        'metodo_pago'   =>  $mpago[$id_empleado],
                        'val_liq'       =>  $neto,
                        'forma_pago'    =>  1,
                        'sal_base'      =>  $salarios[$id_empleado],
                        'id_contrato'   =>  $contratos[$id_empleado],
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
            $sql = "SELECT `id_empleado`,`id_sal_liq`, `id_contrato`
                    FROM `nom_liq_salario`
                    WHERE (`id_nomina` = ? AND `nom_liq_salario`.`estado` = 1 AND `id_empleado` IN ($ids))";
            $stmt = Conexion::getConexion()->prepare($sql);
            $stmt->bindParam(1, $id_nomina, PDO::PARAM_INT);
            $stmt->execute();
            $res  = $stmt->fetchAll(PDO::FETCH_ASSOC);
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
                                WHERE `nn`.`vigencia` <= :vigencia AND `nn`.`tipo` = 'N' AND  `nlb`.`estado` = 1
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
                                WHERE `sub_nn`.`vigencia` <= :vigencia AND `sub_nn`.`tipo` = 'RA' AND  `sub_nlb`.`estado` = 1
                                AND `sub_nn`.`id_nomina` IN (SELECT `id_nomina` FROM `nominas_contrato_activo`)
                                GROUP BY `sub_nlb`.`id_empleado`)),
                        `t1` AS
                            (SELECT
                                `n`.`id_empleado`,
                                CASE
                                    WHEN `n`.`fec_corte` > IFNULL(`r`.`fec_final`, '1900-01-01') THEN `n`.`val_bsp`
                                    ELSE IFNULL(`n`.`val_bsp`, 0) + IFNULL(`r`.`val_bsp_ra`, 0)
                                END AS `val_bsp`,
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
                                WHERE `nn`.`tipo` = 'PV' AND `nn`.`vigencia` <= :vigencia AND `nlp`.`estado` = 1
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
                                WHERE `sub_nn`.`tipo` = 'RA' AND `sub_nn`.`vigencia` <= :vigencia AND `sub_nlp`.`estado` = 1
                                AND `sub_nn`.`id_nomina` IN (SELECT `id_nomina` FROM `nominas_contrato_activo`)
                                GROUP BY `sub_nlp`.`id_empleado`)),
                        `t3` AS
                            (SELECT
                                `pv`.`id_empleado`,
                                CASE
                                    WHEN `pv`.`corte_pv` > IFNULL(`ra`.`corte_ra`, '1900-01-01') THEN IFNULL(`pv`.`val_liq_pv`, 0)
                                    ELSE IFNULL(`pv`.`val_liq_pv`, 0) + IFNULL(`ra`.`val_liq_ra`, 0)
                                END AS `val_liq_ps`,
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
                                WHERE `nn`.`tipo` = 'PN' AND `nn`.`vigencia` <= :vigencia AND `nlpn`.`estado` = 1
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
                                WHERE `sub_nn`.`vigencia` <= :vigencia AND `sub_nlv`.`estado` = 1
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
                                CASE
                                    WHEN `n`.`corte` > IFNULL(`r`.`fec_final`, '1900-01-01') THEN IFNULL(`n`.`val_prima_vac`, 0)
                                    ELSE IFNULL(`n`.`val_prima_vac`, 0) + IFNULL(`r`.`val_prima_vac_racv`, 0)
                                END AS `val_prima_vac`,
                                CASE
                                    WHEN `n`.`corte` > IFNULL(`r`.`fec_final`, '1900-01-01') THEN IFNULL(`n`.`val_liq`, 0)
                                    ELSE IFNULL(`n`.`val_liq`, 0) + IFNULL(`r`.`val_liq_racv`, 0)
                                END AS `val_liq`,
                                CASE
                                    WHEN `n`.`corte` > IFNULL(`r`.`fec_final`, '1900-01-01') THEN IFNULL(`n`.`val_bon_recrea`, 0)
                                    ELSE IFNULL(`n`.`val_bon_recrea`, 0) + IFNULL(`r`.`val_bon_recrea_racv`, 0)
                                END AS `val_bon_recrea`,
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
                                INNER JOIN `t2` ON `h`.`id_empleado` = `t2`.`id_empleado`
                            WHERE `l`.`estado` = 1 AND `n`.`estado` = 5 AND `h`.`fec_inicio` BETWEEN `t2`.`corte_ces` AND '$ffin'
                            AND `n`.`id_nomina` AND `l`.`estado` = 1
                            IN (SELECT `id_nomina` FROM `nominas_contrato_activo`)
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
            $res  = $stmt->fetchAll(PDO::FETCH_ASSOC);
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
            $idHe =     $f['id_he_trab'];
            $valhe =    $valHora * $f['factor'] * $f['cantidad_he'];
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
            $idIncap =     $f['id_incapacidad'];
            $idTipo =      $f['id_tipo'];
            $categoria =   $f['categoria'];
            $liquidado =   $f['liq'];
            $dias =        $f['dias'];
            $pEmpresa = $pEps = $pArl = 0;
            if ($idTipo == 2 || $idTipo == 3) {
                $pArl = $valDia * $dias;
            } else if ($idTipo == 1) {
                if ($categoria == 2) {
                    $pEps = ($valDia * (2 / 3)) * $dias;
                } else if ($categoria == 1) {
                    $diasEmpresaRestantes =     max(0, 2 - $liquidado); // Cuántos días le faltan a la empresa pagar
                    $diasEmpresaMes =           min($dias, $diasEmpresaRestantes); // Solo los que caben en este mes
                    $diasEpsMes =               $dias - $diasEmpresaMes; // El resto es EPS
                    $pEmpresa =                 $valDia * $diasEmpresaMes;
                    $pEps =                     ($valDia * (2 / 3)) * $diasEpsMes;
                }
            }
            // se debe sacar los id_arl e id_eps
            $data = [
                'id' =>         $idIncap,
                'valor' =>      '',
                'id_nomina' =>  $param['id_nomina'],
                'p_empresa' =>  $pEmpresa,
                'p_eps' =>      $pEps,
                'p_arl' =>      $pArl,
                'dias' =>       $dias,
                'id_arl' =>     $novedad[1],
                'id_eps' =>     explode('|', $novedad[3])[0]
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

        $salbas =   floatval($param['salario']);
        $grepre =   ($cortes['representacion'] ?? 0) == 1 ? floatval($param['grep']) : 0;
        $auxtra =   floatval($param['aux_trans']);
        $auxali =   floatval($param['aux_alim']);
        $bspant =   floatval($param['bsp_ant'] ?? 0);
        $psvant =   floatval($param['pri_ser_ant'] ?? 0);
        $base =     $salbas + $grepre + $auxtra + $auxali + $bspant / 12 + $psvant / 12;
        $idvac =    $filtro['id_vac'];
        $dhabiles = $filtro['dias_habiles'] ?? 15;
        $dinactiv = $filtro['dias_inactivo'] ?? 22;
        $dliq =     $filtro['dias_liquidar'];
        $corte =    $filtro['corte'];
        $id_nomina = $param['id_nomina'];

        $prima_vac_dia = ($base * $dhabiles) / (30 * 360);
        $prima_vac = $prima_vac_dia * $dliq;

        $vac_dia = ($base * $dinactiv) / (30 * 360);
        $vacacion = $vac_dia * $dliq;

        $bonrecrea = (($salbas / 30) * (2 * $dliq / 360));
        if ($opcion == 1) {
            $data = compact('idvac', 'corte', 'vacacion', 'prima_vac', 'bonrecrea', 'id_nomina', 'salbas', 'grepre', 'auxtra', 'auxali', 'bspant', 'psvant', 'dhabiles');
            $res = (new Vacaciones($this->conexion))->addRegistroLiq($data);

            if ($res != 'si') {
                $response['insert'] = false;
                $response['msg'] = "<p>$res</p>";
                return $response;
            }
        }

        $response['valor']  =   $vacacion;
        $response['prima']  =   $prima_vac;
        $response['bono']   =   $bonrecrea;

        return $response;
    }

    public function LiquidaPrimaServicios($param, $cortes, $dliq, $opcion = 1)
    {
        $response = [
            'msg' => '',
            'insert' => true,
            'valor' => 0,
        ];

        $salbas         =   $param['salario'];
        $id_empleado    =   $param['id_empleado'];
        $cant_dias      =   $dliq;
        $val_liq_pns    =   0;
        $periodo        =   1;
        $grepre         =   ($cortes['representacion'] ?? 0) == 1 ? $param['grep'] : 0;
        $auxtra         =   $param['aux_trans'];
        $auxali         =   $param['aux_alim'];
        $bspant         =   floatval($param['val_bsp'] ?? 0);
        $base           =   $salbas + $grepre + $auxtra + $auxali + $bspant / 12;
        $corte          =   $param['corte_prim_sv'] ?? NULL;
        $id_nomina      =   $param['id_nomina'];

        $prima_dia      =   $base  / 720;
        $val_liq_ps          =   $prima_dia * $dliq;

        if ($opcion == 1) {
            $data = compact('id_empleado', 'cant_dias', 'val_liq_ps', 'val_liq_pns', 'periodo', 'corte', 'id_nomina');
            $res = (new Primas($this->conexion))->addRegistroLiq1($data);

            if ($res != 'si') {
                $response['insert'] = false;
                $response['msg'] = "<p>$res</p>";
                return $response;
            }
        }

        $response['valor']  =   $val_liq_ps;

        return $response;
    }

    public function LiquidaPrimaNavidad($param, $cortes, $dliq, $opcion = 1)
    {
        $response = [
            'msg' => '',
            'insert' => true,
            'valor' => 0,
        ];

        $salbas         =   $param['salario'];
        $id_empleado    =   $param['id_empleado'];
        $cant_dias      =   $dliq;
        $val_liq_pnv    =   0;
        $periodo        =   2;
        $grepre         =   ($cortes['representacion'] ?? 0) == 1 ? $param['grep'] : 0;
        $auxtra         =   $param['aux_trans'];
        $auxali         =   $param['aux_alim'];
        $bspant         =   floatval($param['val_bsp'] ?? 0);
        $prima_ant      =   floatval($param['val_liq_ps'] ?? 0);
        $vac_ant        =   floatval($param['val_prima_vac'] ?? 0);
        $base           =   $salbas + $grepre + $auxtra + $auxali + ($bspant / 12) + ($prima_ant / 12) + ($vac_ant / 12);
        $corte          =   $param['corte_psv'] ?? NULL;
        $id_nomina      =   $param['id_nomina'];

        $prima_dia      =   $base  / 360;
        $val_liq_pv     =   $prima_dia * $dliq;

        if ($opcion == 1) {
            $data = compact('id_empleado', 'cant_dias', 'val_liq_pv', 'val_liq_pnv', 'periodo', 'corte', 'id_nomina');
            $res = (new Primas($this->conexion))->addRegistroLiq2($data);

            if ($res != 'si') {
                $response['insert'] = false;
                $response['msg'] = "<p>$res</p>";
                return $response;
            }
        }

        $response['valor']  =   $val_liq_pv;

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

        $salbas         =   $param['salario'];
        $id_empleado    =   $param['id_empleado'];
        $cant_dias      =   $dliq;
        $grepre         =   ($cortes['representacion'] ?? 0) == 1 ? $param['grep'] : 0;
        $auxtra         =   $param['aux_trans'];
        $auxali         =   $param['aux_alim'];
        $bspant         =   floatval($param['bsp_ant'] ?? 0);
        $prima_ant      =   floatval($param['pri_ser_ant'] ?? 0);
        $vac_ant        =   floatval($param['pri_vac_ant'] ?? 0);
        $prima_nav_ant  =   floatval($param['pri_nav_ant'] ?? 0);
        $promHoEx       =   floatval($param['prom_horas'] ?? 0);
        $base           =   $salbas + $grepre + $auxtra + $auxali + ($bspant / 12) + ($prima_ant / 12) + ($vac_ant / 12) + ($prima_nav_ant / 12) + $promHoEx;
        $corte          =   $param['corte_psv'] ?? NULL;
        $id_nomina      =   $param['id_nomina'];

        $cesantia_dia   =   $base  / 320;
        $val_cesantias  =   $cesantia_dia * $dliq;
        $val_icesantias =   $val_cesantias * 0.12;

        if ($opcion == 1) {
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

        $response['valor']      =   $val_cesantias;
        $response['interes']    =   $val_icesantias;

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
        $dias = $filtro['mes'] == '02' && $filtro['dias'] >= 28 ? 30 : $filtro['dias'];
        $valdialc = ($tipo == '1' && $filtro['dias_cot'] < 270) ? ($filtro['dias_cot'] * $param['salario']) / (30 * 270) : $param['salario'] / 30;
        $valor = $valdialc * $dias;
        $data = [
            'id_licmp' =>   $filtro['id_licmp'],
            'id_eps' =>     $filtro['id_eps'],
            'dias_liqs' =>  $dias,
            'val_liq' =>    $valor,
            'val_dialc' =>  $valdialc,
            'id_nomina' =>  $param['id_nomina']
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
                'id_licnr' =>   $f['id_licnr'],
                'dias_licnr' => $dias,
                'id_nomina' =>  $param['id_nomina']
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
            $valor = $valor_dia * $f['dias'];
            $data = [
                'id_licluto' =>   $f['id_licluto'],
                'dias' => $f['dias'],
                'valor' => $valor,
                'id_nomina' =>  $param['id_nomina']
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
        $valor = $valor_dia * $filtro['dias'];
        $data = [
            'id_indemniza'  => $filtro['id_indemniza'],
            'dias'          => $filtro['dias'],
            'valor'         => $valor,
            'id_nomina'     => $param['id_nomina']
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
        $dias = floatval($param['dias_bsp'] ?? 360);
        $salario = floatval($param['salario'] ?? 0);
        $base_bsp = floatval($param['base_bsp'] ?? 0);
        $val_grep = $param['tiene_grep'] == 1 ? floatval($param['greps'] ?? 0) : 0;
        $bsp = (($salario + $val_grep) <= $base_bsp ? ($salario + $val_grep) * 0.5 : ($salario + $val_grep) * 0.35);
        $bsp = $bsp * $dias / 360;
        $data = [
            'id_empleado' =>   $param['id_empleado'],
            'corte' =>         $param['corte'],
            'valor' =>         $bsp,
            'id_nomina' =>     $param['id_nomina']
        ];

        $res = (new Bsp($this->conexion))->addRegistro($data);
        if ($res != 'si') {
            $response['insert'] =   false;
            $response['msg'] =      "<p>$res</p>";
        } else {
            $response['valor']      = $bsp;
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
        $saludTotal      = Valores::Redondear($ibc * 0.125, 100);
        $pensionTotal    = Valores::Redondear($ibc * 0.16, 100);
        $saludEmpleado   = Valores::Redondear($ibc * 0.04, 1);
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
            'id_empleado'                   => $param['id_empleado'],
            'id_eps'                        => $novedad[1],
            'id_arl'                        => $idArl,
            'id_afp'                        => $novedad[2],
            'aporte_salud_emp'              => $saludEmpleado,
            'aporte_pension_emp'            => $pensionEmpleado,
            'aporte_solidaridad_pensional'  => $pSol + $pSub,
            'porcentaje_ps'                 => $porPS,
            'aporte_salud_empresa'          => $saludTotal - $saludEmpleado,
            'aporte_pension_empresa'        => $pensionTotal - $pensionEmpleado,
            'aporte_rieslab'                => $riesgos,
            'id_nomina'                     => $param['id_nomina'],
        ];

        // Insertar y generar respuesta
        $res = (new Seguridad_Social($this->conexion))->addRegistroLiq($data);

        if ($res != 'si') {
            $response['insert'] =   false;
            $response['msg'] =      "<p>$res</p>";
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
            $sena   = $exonerado ? 0 : $ibc * 0.02;
            $icbf   = $exonerado ? 0 : $ibc * 0.03;
            $comfam = $ibc * 0.04;
        }

        $data = [
            'id_empleado' => $param['id_empleado'],
            'val_sena'    => Valores::Redondear($sena, 100),
            'val_icbf'    => Valores::Redondear($icbf, 100),
            'val_comfam'  => Valores::Redondear($comfam, 100),
            'id_nomina'   => $param['id_nomina'],
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
            if ($base  > $f['val_mes'] && $base > $minVital) {
                $data = [
                    'id_libranza'   =>   $f['id_libranza'],
                    'val_mes'       =>   $f['val_mes'],
                    'id_nomina'     =>   $param['id_nomina']
                ];

                $res = (new Libranzas($this->conexion))->addRegistroLiq($data);
                if ($res != 'si') {
                    $response['insert'] = false;
                    $response['msg'] = "<p>$res</p>";
                    break;
                } else {
                    $response['valor'] += $f['val_mes'];
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
                $data = [
                    'id_embargo'   =>   $f['id_embargo'],
                    'val_mes'      =>   $f['valor_mes'],
                    'id_nomina'    =>   $param['id_nomina']
                ];

                $res = (new Embargos($this->conexion))->addRegistroLiq($data);
                if ($res != 'si') {
                    $response['insert'] = false;
                    $response['msg'] = "<p>$res</p>";
                    break;
                } else {
                    // Restar de la base solo después de validar y registrar exitosamente
                    $base -= $f['valor_mes'];
                    $response['valor'] += $f['valor_mes'];
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
            'id_sindicato'  =>  $filtro['id_cuota_sindical'],
            'id_nomina'     =>  $param['id_nomina']
        ];

        $smmlv = $param['smmlv'];
        $minVital = $param['min_vital'] > 0 ? $param['min_vital'] : $smmlv;

        $sindicalizacion = !empty((new Sindicatos($this->conexion))->getRegistroLiq($filtro['id_sindicato'])) ? 0 : $filtro['val_sidicalizacion'];
        $val = Valores::Redondear((($filtro['porcentaje_cuota'] / 100) * $param['salario']), 1);
        $dcto = $val + $sindicalizacion;
        $data['valor_fijo']    =  $dcto;

        if ($base  > $dcto && $base > $minVital) {
            $res = (new Sindicatos($this->conexion))->addRegistroLiq($data);
            if ($res != 'si') {
                $response['insert'] =   false;
                $response['msg']    =   "<p>$res</p>";
            } else {
                $response['valor']  =   $dcto;
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
            if ($base  > $f['valor'] && $base > $minVital) {
                $data = [
                    'id_dcto'   =>   $f['id_dcto'],
                    'valor'     =>   $f['valor'],
                    'id_nomina' =>   $param['id_nomina']
                ];

                $res = (new Otros_Descuentos($this->conexion))->addRegistroLiq($data);
                if ($res != 'si') {
                    $response['insert'] = false;
                    $response['msg'] = "<p>$res</p>";
                    break;
                } else {
                    $response['valor'] += $f['valor'];
                }
            } else {
                continue;
            }
        }
        return $response;
    }

    public function LiquidaRetencionFuente($array)
    {
        $response = [
            'msg'       =>    '',
            'insert'    =>    false,
            'valor'     =>    0
        ];
        $ingLabUvt  =    $array['ing_uvt'];
        $uvt        =    $array['uvt'];
        $retencion  =    0;

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
            $retencion = ($uvt * $uvtx * 0.35) +  (162 * $uvt);
        } else if ($ingLabUvt >= 945 && $ingLabUvt < 2300) {
            $uvtx = $ingLabUvt - 945;
            $retencion = ($uvt * $uvtx * 0.37) + (268 * $uvt);
        } else if ($ingLabUvt >= 2300) {
            $uvtx = $ingLabUvt - 2300;
            $retencion = ($uvt * $uvtx * 0.39) + (770 * $uvt);
        }
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
            'msg'       =>    '',
            'insert'    =>    false,
            'valor'     =>    0
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
            'msg'       =>    '',
            'insert'    =>    false,
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
            'msg'       =>    '',
            'delete'    =>    false,
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
