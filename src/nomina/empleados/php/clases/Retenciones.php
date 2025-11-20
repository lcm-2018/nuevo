<?php

namespace Src\Nomina\Empleados\Php\Clases;

use Config\Clases\Conexion;
use Config\Clases\Sesion;

use PDO;
use PDOException;

/**
 * Clase para gestionar las primas de los empleados.
 *
 * Esta clase permite realizar operaciones CRUD sobre las primas de los empleados,
 * incluyendo la obtención de registros, adición, edición y eliminación de primas.
 */
class Retenciones
{
    private $conexion;

    public function __construct($conexion = null)
    {
        $this->conexion = $conexion ?: Conexion::getConexion(); // Método estático que retorna el objeto PDO
    }

    /**
     * Obtiene un registro por ID.
     *
     * @param int $id ID del registro 
     * @return array  datos del registro
     */

    public function getRegistro($id)
    {
        return 'No se ha implementado aún';
    }

    public function getRegistroLiq($array)
    {
        try {
            $sql = "SELECT `id_rte_fte` AS `id`
                    FROM `nom_retencion_fte`
                    WHERE `id_empleado` = ?  AND `id_nomina` = ? AND `estado` = 1";
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

    /**
     * Elimina un registro.
     *
     * @param int $id ID del registro a eliminar
     * @return string Mensaje de éxito o error
     */

    public function delRegistro($id)
    {
        return 'No se ha implementado eliminar aún';
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
    }

    public function addRegistroLiq1($array)
    {
        return 'No se ha implementado aún';
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
            $sql = "UPDATE `nom_retencion_fte`
                        SET `base` =  ?,`val_ret` =  ?
                    WHERE `id_rte_fte` = ?";
            $stmt = $this->conexion->prepare($sql);
            $stmt->bindValue(1, $array['base_retencion'], PDO::PARAM_STR);
            $stmt->bindValue(2, $array['valor_retencion'], PDO::PARAM_STR);
            $stmt->bindValue(3, $array['id'], PDO::PARAM_INT);

            if ($stmt->execute() && $stmt->rowCount() > 0) {
                $consulta = "UPDATE `nom_retencion_fte` SET `fec_act` = ?, `id_user_act` = ? WHERE `id_rte_fte` = ?";
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
}
