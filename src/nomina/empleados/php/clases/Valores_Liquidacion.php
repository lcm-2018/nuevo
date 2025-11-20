<?php

namespace Src\Nomina\Empleados\Php\Clases;

use Config\Clases\Conexion;
use Config\Clases\Logs;
use Config\Clases\Sesion;

use Src\Common\Php\Clases\Valores;

use PDO;
use PDOException;

class Valores_Liquidacion
{
    private $conexion;

    public function __construct()
    {
        $this->conexion = Conexion::getConexion(); // Método estático que retorna el objeto PDO
    }

    /**
     * Obtiene los datos de los valores usados como base para liquidar para la DataTabl.
     *
     * @param int $start Índice de inicio para la paginación
     * @param int $length Número de registros a mostrar
     * @param string $buscar Valor de búsqueda
     * @param int $col Índice de la columna para ordenar
     * @param string $dir Dirección de ordenamiento (ascendente o descendente) 
     * @return array|[] Retorna un array con los datos
     */
    public function getContratosEmpleado($start, $length, $buscar, $col, $dir, $id_empleado)
    {
        $limit = "";
        if ($length != -1) {
            $limit = "LIMIT $start, $length";
        }

        $where = '';
        if ($buscar != '') {
            $buscar = trim($buscar);
            $where .= " AND (`nom_contratos_empleados`.`fec_inicio` LIKE '%$buscar%' OR `nom_contratos_empleados`.`fec_fin` LIKE '%$buscar%' OR `nom_salarios_basico`.`salario_basico` LIKE '%$buscar%')";
        }

        $sql = "SELECT
                WHERE (`nom_contratos_empleados`.`id_empleado` = $id_empleado $where)
                ORDER BY $col $dir $limit";
        $stmt = $this->conexion->prepare($sql);
        $stmt->execute();
        $datos = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $stmt->closeCursor();
        unset($stmt);
        return $datos ?: [];
    }

    public function getContratoEmpleados($ids)
    {
        $ids = implode(',', $ids);
        $sql = "";
        $stmt = $this->conexion->prepare($sql);
        $stmt->bindParam(1, $ids, PDO::PARAM_STR);
        $stmt->execute();
        $datos = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $stmt->closeCursor();
        unset($stmt);
        return $datos ?: [];
    }
    /**
     * Obtiene el total de registros filtrados.
     * 
     * @param string $val_busca Valor de búsqueda
     * @return int Total de registros filtrados
     */
    public function getRegistrosFilter($buscar, $id_empleado)
    {
        $where = '';
        if ($buscar != '') {
            $buscar = trim($buscar);
            $where .= " AND (`nom_contratos_empleados`.`fec_inicio` LIKE '%$buscar%' OR `nom_contratos_empleados`.`fec_fin` LIKE '%$buscar%' OR `nom_salarios_basico`.`salario_basico` LIKE '%$buscar%')";
        }

        $sql = "SELECT 
                    COUNT(*) AS `total`
                FROM
                    `nom_salarios_basico`
                    INNER JOIN `nom_contratos_empleados` 
                        ON (`nom_salarios_basico`.`id_contrato` = `nom_contratos_empleados`.`id_contrato_emp`)
                WHERE (`nom_contratos_empleados`.`id_empleado` = $id_empleado $where)";
        $stmt = $this->conexion->prepare($sql);
        $stmt->execute();
        $data = $stmt->fetch(PDO::FETCH_ASSOC)['total'] ?: 0;
        $stmt->closeCursor();
        unset($stmt);
        return $data;
    }

    /**
     * Obtiene el total de registros.
     * @return int Total de registros
     */

    public function getRegistrosTotal($id_empleado)
    {
        $sql = "SELECT 
                    COUNT(*) AS `total`
                FROM
                    `nom_salarios_basico`
                    INNER JOIN `nom_contratos_empleados` 
                        ON (`nom_salarios_basico`.`id_contrato` = `nom_contratos_empleados`.`id_contrato_emp`)
                WHERE (`nom_contratos_empleados`.`id_empleado` = $id_empleado)";
        $stmt = $this->conexion->prepare($sql);
        $stmt->execute();
        $data = $stmt->fetch(PDO::FETCH_ASSOC)['total'] ?: 0;
        $stmt->closeCursor();
        unset($stmt);
        return $data;
    }

    public function getRegistro($id_nomina, $id_empleado)
    {
        $sql = "SELECT
                    `id_empleado`,`smmlv`,`aux_trans`,`aux_alim`,`uvt`,`base_bsp`,`base_alim`,`min_vital`,`salario`,`tiene_grep`,`grep`,`prom_horas`,`bsp_ant`,`pri_ser_ant`,`pri_vac_ant`,`pri_nav_ant`,`id_nomina`
                FROM `nom_valores_liquidacion` WHERE `id_nomina` = ? AND `id_empleado` = ?";
        $stmt = $this->conexion->prepare($sql);
        $stmt->bindParam(1, $id_nomina, PDO::PARAM_INT);
        $stmt->bindParam(2, $id_empleado, PDO::PARAM_INT);
        $stmt->execute();
        $valores = $stmt->fetch(PDO::FETCH_ASSOC);
        $stmt->closeCursor();
        unset($stmt);
        return !empty($valores) ? $valores : [];
    }


    /**
     * Obtiene el formulario para agregar o editar un contrato.
     *
     * @param int $id ID del contrato (0 para nuevo)
     * @return string HTML del formulario
     */
    public function getFormulario($id)
    {
        return 'Falta programar el formulario.';
    }

    /**
     * Elimina un contrato.
     *
     * @param int $id ID del contrato a eliminar
     * @return string Mensaje de éxito o error
     */

    public function delRegistro($id)
    {
        try {
            $sql = "DELETE FROM `nom_contratos_empleados` WHERE `id_contrato_emp` = ?";
            $consulta  = "DELETE FROM `nom_contratos_empleados` WHERE `id_contrato_emp` = $id";
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
     * Agrega un nuevo contrato.
     *
     * @param array $array Datos del contrato a agregar
     * @return string Mensaje de éxito o error
     */
    public function addRegistro($l)
    {
        try {
            $sql = "INSERT INTO `nom_valores_liquidacion`
                        (`id_empleado`,`smmlv`,`aux_trans`,`aux_alim`,`uvt`,`base_bsp`,`base_alim`,`min_vital`,`salario`,`tiene_grep`,`prom_horas`,`bsp_ant`,`pri_ser_ant`,`pri_vac_ant`,`pri_nav_ant`,`id_user_reg`,`fec_reg`,`id_nomina`,`grep`)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $stmt = $this->conexion->prepare($sql);
            $stmt->bindValue(1, $l['id_empleado'], PDO::PARAM_INT);
            $stmt->bindValue(2, $l['smmlv'], PDO::PARAM_INT);
            $stmt->bindValue(3, $l['aux_trans'], PDO::PARAM_STR);
            $stmt->bindValue(4, $l['aux_alim'], PDO::PARAM_STR);
            $stmt->bindValue(5, $l['uvt'], PDO::PARAM_STR);
            $stmt->bindValue(6, $l['base_bsp'], PDO::PARAM_STR);
            $stmt->bindValue(7, $l['base_alim'], PDO::PARAM_STR);
            $stmt->bindValue(8, $l['min_vital'], PDO::PARAM_STR);
            $stmt->bindValue(9, $l['salario'], PDO::PARAM_STR);
            $stmt->bindValue(10, $l['tiene_grep'], PDO::PARAM_INT);
            $stmt->bindValue(11, $l['prom_horas'], PDO::PARAM_STR);
            $stmt->bindValue(12, $l['bsp_ant'], PDO::PARAM_STR);
            $stmt->bindValue(13, $l['pri_ser_ant'], PDO::PARAM_STR);
            $stmt->bindValue(14, $l['pri_vac_ant'], PDO::PARAM_STR);
            $stmt->bindValue(15, $l['pri_nav_ant'], PDO::PARAM_STR);
            $stmt->bindValue(16, Sesion::IdUser(), PDO::PARAM_INT);
            $stmt->bindValue(17, Sesion::Hoy(), PDO::PARAM_STR);
            $stmt->bindValue(18, $l['id_nomina'], PDO::PARAM_INT);
            $stmt->bindValue(19, $l['grep'], PDO::PARAM_STR);
            $stmt->execute();
            $id = $this->conexion->lastInsertId();
            if ($id > 0) {
                $stmt->closeCursor();
                unset($stmt);
                return  'si';
            } else {
                return 'No se insertó el registro';
            }
        } catch (PDOException $e) {
            return 'Error SQL: ' . $e->getMessage();
        }
    }

    public function getRegistroLiq($array)
    {
        try {
            $sql = "SELECT
                        `id_valor` AS `id`
                    FROM
                        `nom_valores_liquidacion`
                    WHERE (`id_empleado` = ? AND `id_nomina` = ?)";
            $stmt = $this->conexion->prepare($sql);
            $stmt->bindValue(1, $array['id_empleado'], PDO::PARAM_INT);
            $stmt->bindValue(2, $array['id_nomina'], PDO::PARAM_INT);
            $stmt->execute();
            $data = $stmt->fetch(PDO::FETCH_ASSOC);
            $stmt->closeCursor();
            unset($stmt);
            return !empty($data) ? $data['id'] : 0;
        } catch (PDOException $e) {
            return 'Error SQL: ' . $e->getMessage();
        }
    }

    public function editRegistroLiq($l)
    {
        try {
            $sql = "UPDATE `nom_valores_liquidacion`
                        SET `smmlv` = ?, `aux_trans` = ?, `aux_alim` = ?, `uvt` = ?, `base_bsp` = ?, `base_alim` = ?, `min_vital` = ?, `salario` = ?, `tiene_grep` = ?, `prom_horas` = ?, `bsp_ant` = ?, `pri_ser_ant` = ?, `pri_vac_ant` = ?, `pri_nav_ant` = ?, `grep` = ?
                    WHERE (`id_valor` = ?)";
            $stmt = $this->conexion->prepare($sql);
            $stmt->bindValue(1, $l['smmlv'], PDO::PARAM_STR);
            $stmt->bindValue(2, $l['aux_trans'], PDO::PARAM_STR);
            $stmt->bindValue(3, $l['aux_alim'], PDO::PARAM_STR);
            $stmt->bindValue(4, $l['uvt'], PDO::PARAM_STR);
            $stmt->bindValue(5, $l['base_bsp'], PDO::PARAM_STR);
            $stmt->bindValue(6, $l['base_alim'], PDO::PARAM_STR);
            $stmt->bindValue(7, $l['min_vital'] ?? 0, PDO::PARAM_STR);
            $stmt->bindValue(8, $l['salario'], PDO::PARAM_STR);
            $stmt->bindValue(9, $l['tiene_grep'] ?? 0, PDO::PARAM_INT);
            $stmt->bindValue(10, $l['prom_horas'], PDO::PARAM_STR);
            $stmt->bindValue(11, $l['bsp_ant'], PDO::PARAM_STR);
            $stmt->bindValue(12, $l['pri_ser_ant'], PDO::PARAM_STR);
            $stmt->bindValue(13, $l['pri_vac_ant'], PDO::PARAM_STR);
            $stmt->bindValue(14, $l['pri_nav_ant'], PDO::PARAM_STR);
            $stmt->bindValue(15, $l['grep'], PDO::PARAM_STR);
            $stmt->bindValue(16, $l['id'], PDO::PARAM_INT);
            if ($stmt->execute() && $stmt->rowCount() > 0) {
                $consulta = "UPDATE `nom_valores_liquidacion` SET `fec_act` = ?, `id_user_act` = ? WHERE `id_valor` = ?";
                $stmt2 = $this->conexion->prepare($consulta);
                $stmt2->bindValue(1, Sesion::Hoy(), PDO::PARAM_STR);
                $stmt2->bindValue(2, Sesion::IdUser(), PDO::PARAM_INT);
                $stmt2->bindValue(3, $l['id'], PDO::PARAM_INT);
                $stmt2->execute();
                return 'si';
            } else {
                return 'no';
            }
        } catch (PDOException $e) {
            return 'Error SQL: ' . $e->getMessage();
        }
    }
    /**
     * Actualiza los datos de un contrato.
     *
     * @param array $array Datos del contrato a actualizar
     * @return string Mensaje de éxito o error
     */
    public function editRegistro($array)
    {
        $cambio = 0;
        try {
            $sql = "UPDATE `nom_contratos_empleados`
                        SET `fec_inicio` = ?, `fec_fin` = ?
                    WHERE (`id_contrato_emp` = ?)";
            $stmt = $this->conexion->prepare($sql);
            $stmt->bindValue(1, $array['datFecInicia'], PDO::PARAM_STR);
            $stmt->bindValue(2, $array['datFecFin'] != '' ? $array['datFecFin'] : NULL, PDO::PARAM_STR);
            $stmt->bindValue(3, $array['id_contrato_emp'], PDO::PARAM_INT);
            if (!($stmt->execute())) {
                return 'Errado: ' . $stmt->errorInfo()[2];
            } else {
                if ($stmt->rowCount() > 0) {
                    $consulta = "UPDATE `nom_contratos_empleados` SET `id_user_act` = ?, `fec_act` = ? WHERE (`id_contrato_emp` = ?)";
                    $stmt2 = $this->conexion->prepare($consulta);
                    $stmt2->bindValue(1, Sesion::IdUser(), PDO::PARAM_INT);
                    $stmt2->bindValue(2, Sesion::Hoy(), PDO::PARAM_STR);
                    $stmt2->bindValue(3, $array['id_contrato_emp'], PDO::PARAM_INT);
                    $stmt2->execute();
                    $cambio++;
                }
            }
            $sql = "UPDATE `nom_salarios_basico`
                        SET `salario_basico` = ?
                    WHERE (`id_salario` = ?)";
            $stmt = $this->conexion->prepare($sql);
            $stmt->bindValue(1, Valores::WordToNumber($array['txtSalarioBasico']), PDO::PARAM_STR);
            $stmt->bindValue(2, $array['id'], PDO::PARAM_INT);
            if (!($stmt->execute())) {
                return 'Errado: ' . $stmt->errorInfo()[2];
            } else {
                if ($stmt->rowCount() > 0) {
                    $consulta = "UPDATE `nom_salarios_basico` SET `fec_act` = ? WHERE (`id_salario` = ?)";
                    $stmt2 = $this->conexion->prepare($consulta);
                    $stmt2->bindValue(1, Sesion::Hoy(), PDO::PARAM_STR);
                    $stmt2->bindValue(2, $array['id'], PDO::PARAM_INT);
                    $stmt2->execute();
                    $cambio++;
                }
            }
            if ($cambio > 0) {
                return 'si';
            } else {
                return 'No se actualizó el registro.';
            }
        } catch (PDOException $e) {
            return 'Error SQL: ' . $e->getMessage();
        }
    }

    public function annulRegistro($array)
    {
        return 'Falta programar la anulación de contrato.';
    }
}
