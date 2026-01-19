<?php

namespace Src\Nomina\Empleados\Php\Clases;

use Config\Clases\Conexion;
use Config\Clases\Logs;
use Config\Clases\Sesion;
use Exception;
use PDO;
use PDOException;
use Src\Nomina\Liquidacion\Php\Clases\Liquidacion;
use Src\Nomina\Liquidacion\Php\Clases\Nomina;
use Src\Usuarios\Login\Php\Clases\Usuario;

/**
 * Clase para gestionar las primas de los empleados.
 *
 * Esta clase permite realizar operaciones CRUD sobre las primas de los empleados,
 * incluyendo la obtención de registros, adición, edición y eliminación de primas.
 */
class Cesantias
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
                $where .= " AND `e`.`no_documento` LIKE '%{$array['filter_nodoc']}%'";
            }
            if (isset($array['filter_nombre']) && $array['filter_nombre'] != '') {
                $where .= " AND `e`.`nombre` LIKE '%{$array['filter_nombre']}%'";
            }
        }
        $mes = $array['filter_mes'];

        $sql = "SELECT 
                    `ctt`.`id_empleado`
                    , `e`.`no_documento`
                    , CONCAT_WS(' ', `e`.`nombre1`, `e`.`nombre2`, `e`.`apellido1`, `e`.`apellido2`) AS `nombre`
                    , `ctt`.`id_contrato_emp` AS `id_contrato`
                    , 0 AS `inc`
                    , 0 AS `lic`
                    , 0 AS `vac`
                    , 0 AS `ivac`
                    , 0 AS `dias_mes`
                    , '' AS `observacion`
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

        $sql = "SELECT 
                    COUNT(*) AS `total`
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

        $sql = "SELECT 
                    COUNT(*) AS `total`
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
     * Agrega un nuevo registro.
     *
     * @param array $array Datos del registro a agregar
     * @return string Mensaje de éxito o error
     */
    public function addRegistroN($array, $opcion = 0)
    {
        $ids =          $array['chk_liquidacion'];
        $contratos =    $array['id_contrato'];
        $mpago =        $array['metodo'];
        $tipo =         $array['tipo'];
        $mes =          $array['mes'];
        $incremento =   isset($array['incremento']) ? $array['incremento'] : NULL;
        $nomina =       Nomina::getIDNomina($mes, $tipo);

        // Verificar si necesitamos crear la nómina de cesantías
        $crearNominaCes = ($nomina['id_nomina'] > 0 && $nomina['estado'] >= 2) || $nomina['id_nomina'] == 0;

        if ($crearNominaCes) {
            $res = Nomina::addRegistro($mes, $tipo, $incremento);
            if ($res['status'] == 'si') {
                $id_nomina = $res['id'];
            } else {
                return $res['msg'];
            }
        } else {
            $id_nomina = $nomina['id_nomina'];
        }

        if ($opcion == 0) {
            $data = Nomina::getParamLiq();
            if (empty($data)) {
                return 'No se han configurado los parámetros de liquidación.';
            }

            $parametro = array_column($data, 'valor', 'id_concepto');

            if (empty($parametro[1]) || empty($parametro[6])) {
                return 'No se han Configurado los parámetros de liquidación.';
            }
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

        $cortes =       array_column(((new Liquidacion())->getCortes($ids, $fin)), null, 'id_empleado');
        $liquidados =   (new Liquidacion())->getEmpleadosLiq($id_nomina, $ids);
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
            $param['tipo'] =            $tipo;
        }

        $inserts = 0;
        foreach ($ids as $id_empleado) {
            if (!isset($liquidados[$id_empleado]) && isset($salarios[$id_empleado])) {
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

                    //Cesantias
                    $dias = $this->calcularDias($cortes_empleado['corte_ces'], $fin, $id_empleado);
                    $response = (new Liquidacion($this->conexion))->LiquidaCesantias($param, $cortes_empleado, $dias, 1);
                    if (!$response['insert']) {
                        throw new Exception("Cesantias Mes: {$response['msg']}");
                    }

                    $neto = 0;
                    $data = [
                        'id_empleado'   =>  $id_empleado,
                        'id_nomina'     =>  $id_nomina,
                        'metodo_pago'   =>  $mpago[$id_empleado],
                        'val_liq'       =>  $neto,
                        'forma_pago'    =>  1,
                        'sal_base'      =>  $salarios[$id_empleado],
                        'id_contrato'   =>  $contratos[$id_empleado],
                    ];
                    $response = (new Liquidacion($this->conexion))->LiquidaSalarioNeto($data);
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
            return 'Liquidación realizada con éxito.';
        }
    }

    /**
     * Obtiene un registro por ID.
     *
     * @param int $id ID del registro 
     * @return array  datos del registro
     */

    public function getRegistroLiq($a)
    {
        $sql = "SELECT
                    `id_liq_cesan`
                FROM `nom_liq_cesantias`
                WHERE `id_empleado` = ? AND `id_nomina` = ? AND `estado` = 1";
        $stmt = $this->conexion->prepare($sql);
        $stmt->bindParam(1, $a['id_empleado'], PDO::PARAM_INT);
        $stmt->bindParam(2, $a['id_nomina'], PDO::PARAM_INT);
        $stmt->execute();
        $data = $stmt->fetch(PDO::FETCH_ASSOC);
        $stmt->closeCursor();
        unset($stmt);
        return !empty($data) ? $data['id_liq_cesan'] : 0;
    }


    /**
     * Elimina un registro.
     *
     * @param int $id ID del registro a eliminar
     * @return string Mensaje de éxito o error
     */

    public function delRegistro($id)
    {
        return 'No se ha implementado eliminar aún';
        try {
            $sql = "DELETE FROM `nom_indemniza_vac` WHERE `id_indemniza` = ?";
            $consulta  = "DELETE FROM `nom_indemniza_vac` WHERE `id_indemniza` = $id";
            $stmt = $this->conexion->prepare($sql);
            $stmt->bindParam(1, $id, PDO::PARAM_INT);
            $stmt->execute();
            if ($stmt->rowCount() > 0) {
                Logs::guardaLog($consulta);
                (new Novedades())->delRegistro(6, $id);
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
        return 'No se ha implementado aún';
        try {
            $this->conexion->beginTransaction();
            $sql = "INSERT INTO `nom_indemniza_vac`
                        (`id_empleado`,`fec_inica`,`fec_fin`,`cant_dias`,`estado`,`fec_reg`,`id_user_reg`)
                    VALUES (?, ?, ?, ?, ?, ?, ?)";
            $stmt = $this->conexion->prepare($sql);
            $stmt->bindValue(1, $array['id_empleado'], PDO::PARAM_INT);
            $stmt->bindValue(2, $array['datFecInicia'], PDO::PARAM_STR);
            $stmt->bindValue(3, $array['datFecFin'], PDO::PARAM_STR);
            $stmt->bindValue(4, $array['diasInactivo'], PDO::PARAM_INT);
            $stmt->bindValue(5, 1, PDO::PARAM_INT);
            $stmt->bindValue(6, Sesion::Hoy(), PDO::PARAM_STR);
            $stmt->bindValue(7, Sesion::IdUser(), PDO::PARAM_INT);
            $stmt->execute();
            $id = $this->conexion->lastInsertId();
            if ($id > 0) {
                return 'si';
            } else {
                $this->conexion->rollBack();
                return 'No se insertó el registro';
            }
        } catch (PDOException $e) {
            return 'Error SQL: ' . $e->getMessage();
        }
    }

    public function addRegistroLiq($array)
    {
        try {
            $sql = "INSERT INTO `nom_liq_cesantias`
                        (`id_empleado`,`cant_dias`,`val_cesantias`,`val_icesantias`,`porcentaje_interes`,`corte`,`id_user_reg`,`fec_reg`,`id_nomina`,`tipo`)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $stmt = $this->conexion->prepare($sql);
            $stmt->bindValue(1, $array['id_empleado'], PDO::PARAM_INT);
            $stmt->bindValue(2, $array['cant_dias'], PDO::PARAM_INT);
            $stmt->bindValue(3, $array['val_cesantias'], PDO::PARAM_STR);
            $stmt->bindValue(4, $array['val_icesantias'], PDO::PARAM_STR);
            $stmt->bindValue(5, 12, PDO::PARAM_INT);
            $stmt->bindValue(6, $array['corte'], PDO::PARAM_STR);
            $stmt->bindValue(7, Sesion::IdUser(), PDO::PARAM_INT);
            $stmt->bindValue(8, Sesion::Hoy(), PDO::PARAM_STR);
            $stmt->bindValue(9, $array['id_nomina'], PDO::PARAM_INT);
            $stmt->bindValue(10, $array['tipo'] ?? 'S', PDO::PARAM_STR);
            $stmt->execute();
            $id = $this->conexion->lastInsertId();
            if ($id > 0) {
                return 'si';
            } else {
                return 'No se insertó el registro';
            }
        } catch (PDOException $e) {
            return 'Error SQL: ' . $e->getMessage();
        }
    }

    /**
     * Actualiza los datos de un registro.
     *
     * @param array $array Datos del registro a actualizar
     * @return string Mensaje de éxito o error
     */
    public function editRegistroLiq($array)
    {
        try {
            $sql = "UPDATE `nom_liq_cesantias`
                        SET `cant_dias` = ?, `val_cesantias` = ?,`val_icesantias` = ?, `corte` = ?
                    WHERE `id_liq_cesan` = ?";
            $stmt = $this->conexion->prepare($sql);
            $stmt->bindValue(1, $array['cant_dias'], PDO::PARAM_INT);
            $stmt->bindValue(2, $array['val_cesantias'], PDO::PARAM_STR);
            $stmt->bindValue(3, $array['val_icesantias'], PDO::PARAM_STR);
            $stmt->bindValue(4, $array['corte'], PDO::PARAM_STR);
            $stmt->bindValue(5, $array['id'], PDO::PARAM_INT);
            if ($stmt->execute() && $stmt->rowCount() > 0) {
                $consulta = "UPDATE `nom_liq_cesantias` SET `fec_act` = ?, `id_user_act` = ? WHERE `id_liq_cesan` = ?";
                $stmt2 = $this->conexion->prepare($consulta);
                $stmt2->bindValue(1, Sesion::Hoy(), PDO::PARAM_STR);
                $stmt2->bindValue(2, Sesion::IdUser(), PDO::PARAM_INT);
                $stmt2->bindValue(3, $array['id'], PDO::PARAM_INT);
                $stmt2->execute();
                return 'si';
            } else {
                return 'no';
            }
        } catch (PDOException $e) {
            return 'Error SQL: ' . $e->getMessage();
        }
    }

    public function annulRegistro($array)
    {
        return 'Falta programar la anulación de registro de seguridad social.';
    }

    /**
     * Calcula los días entre dos fechas usando el método de 360 días.
     * Resta los días de licencias no remuneradas del período.
     *
     * @param string $fI Fecha de inicio (formato Y-m-d)
     * @param string $fF Fecha de fin (formato Y-m-d)
     * @param int $id ID del empleado
     * @return int Número de días calculados
     */
    public function calcularDias($fI, $fF, $id)
    {
        $fechaInicial = strtotime($fI);
        $fechaFinal = strtotime($fF);
        $dias360 = 0;
        if (!($fechaInicial > $fechaFinal)) {
            while ($fechaInicial < $fechaFinal) {
                $dias360 += 30; // Agregar 30 días por cada mes
                $fechaInicial = strtotime('+1 month', $fechaInicial);
            }

            // Agregar los días restantes después del último mes completo
            $dias360 += ($fechaFinal - $fechaInicial) / (60 * 60 * 24);
            $dias360 = $dias360 + 1;
        }
        try {
            $sql = "SELECT
                        SUM(`dias_inactivo`) AS `dias`
                    FROM
                        `nom_licenciasnr`
                    WHERE ((`fec_inicio` BETWEEN ? AND ?) OR (`fec_fin` BETWEEN ? AND ?)) AND `id_empleado` = ?";
            $stmt = $this->conexion->prepare($sql);
            $stmt->bindParam(1, $fI, PDO::PARAM_STR);
            $stmt->bindParam(2, $fF, PDO::PARAM_STR);
            $stmt->bindParam(3, $fI, PDO::PARAM_STR);
            $stmt->bindParam(4, $fF, PDO::PARAM_STR);
            $stmt->bindParam(5, $id, PDO::PARAM_INT);
            $stmt->execute();
            $dias = $stmt->fetch(PDO::FETCH_ASSOC);
            $dlcnr = !empty($dias) ? $dias['dias'] : 0;
            $stmt->closeCursor();
            unset($stmt);
        } catch (PDOException $e) {
            $dlcnr = 0;
        }
        $dias360 = $dias360 > $dlcnr ? $dias360 - $dlcnr : 0;
        return $dias360;
    }
}
