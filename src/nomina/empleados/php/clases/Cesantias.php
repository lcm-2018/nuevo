<?php

namespace Src\Nomina\Empleados\Php\Clases;

use Config\Clases\Conexion;
use Config\Clases\Logs;
use Config\Clases\Sesion;

use PDO;
use PDOException;

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
}
