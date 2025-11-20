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
class Primas
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

    public function getRegistroLiq1($a)
    {
        $sql = "SELECT `id_liq_prima` FROM `nom_liq_prima` WHERE `id_empleado` = ? AND `id_nomina` = ? AND `estado` = 1";
        $stmt = $this->conexion->prepare($sql);
        $stmt->bindParam(1, $a['id_empleado'], PDO::PARAM_INT);
        $stmt->bindParam(2, $a['id_nomina'], PDO::PARAM_INT);
        $stmt->execute();
        $data = $stmt->fetch(PDO::FETCH_ASSOC);
        $stmt->closeCursor();
        unset($stmt);
        return !empty($data) ? $data['id_liq_prima'] : 0;
    }

    public function getRegistroLiq2($a)
    {
        $sql = "SELECT `id_liq_privac` FROM `nom_liq_prima_nav` WHERE `id_empleado` = ? AND `id_nomina` = ? AND `estado` = 1";
        $stmt = $this->conexion->prepare($sql);
        $stmt->bindParam(1, $a['id_empleado'], PDO::PARAM_INT);
        $stmt->bindParam(2, $a['id_nomina'], PDO::PARAM_INT);
        $stmt->execute();
        $data = $stmt->fetch(PDO::FETCH_ASSOC);
        $stmt->closeCursor();
        unset($stmt);
        return !empty($data) ? $data['id_liq_privac'] : 0;
    }

    public function addRegistroLiq1($array)
    {
        try {
            $sql = "INSERT INTO `nom_liq_prima`
                        (`id_empleado`,`cant_dias`,`val_liq_ps`,`val_liq_pns`,`periodo`,`corte`,`id_user_reg`,`fec_reg`,`id_nomina`,`tipo`)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $stmt = $this->conexion->prepare($sql);
            $stmt->bindValue(1, $array['id_empleado'], PDO::PARAM_INT);
            $stmt->bindValue(2, $array['cant_dias'], PDO::PARAM_INT);
            $stmt->bindValue(3, $array['val_liq_ps'], PDO::PARAM_STR);
            $stmt->bindValue(4, $array['val_liq_pns'], PDO::PARAM_STR);
            $stmt->bindValue(5, $array['periodo'], PDO::PARAM_STR);
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
    public function addRegistroLiq2($array)
    {
        try {
            $sql = "INSERT INTO `nom_liq_prima_nav`
                    (`id_empleado`,`cant_dias`,`val_liq_pv`,`val_liq_pnv`,`periodo`,`corte`,`id_user_reg`,`fec_reg`,`id_nomina`,`tipo`)
                VALUES(?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $stmt = $this->conexion->prepare($sql);
            $stmt->bindValue(1, $array['id_empleado'], PDO::PARAM_INT);
            $stmt->bindValue(2, $array['cant_dias'], PDO::PARAM_INT);
            $stmt->bindValue(3, $array['val_liq_pv'], PDO::PARAM_STR);
            $stmt->bindValue(4, $array['val_liq_pnv'], PDO::PARAM_STR);
            $stmt->bindValue(5, $array['periodo'], PDO::PARAM_STR);
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
                return 'no';
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
    public function editRegistroLiq1($array)
    {
        try {
            $sql = "UPDATE `nom_liq_prima`
                        SET `cant_dias` = ?, `val_liq_ps` = ?,`corte` = ?
                    WHERE `id_liq_prima` = ?";
            $stmt = $this->conexion->prepare($sql);
            $stmt->bindValue(1, $array['cant_dias'], PDO::PARAM_INT);
            $stmt->bindValue(2, $array['val_liq_ps'], PDO::PARAM_STR);
            $stmt->bindValue(3, $array['corte'], PDO::PARAM_STR);
            $stmt->bindValue(4, $array['id'], PDO::PARAM_INT);

            if ($stmt->execute() && $stmt->rowCount() > 0) {
                $consulta = "UPDATE `nom_liq_prima` SET `fec_act` = ?, `id_user_act` = ? WHERE `id_liq_prima` = ?";
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

    public function editRegistroLiq2($array)
    {
        try {
            $sql = "UPDATE `nom_liq_prima_nav`
                        SET `cant_dias` = ?, `val_liq_pv` = ?, `corte` = ?
                    WHERE `id_liq_privac` = ?";
            $stmt = $this->conexion->prepare($sql);
            $stmt->bindValue(1, $array['cant_dias'], PDO::PARAM_INT);
            $stmt->bindValue(2, $array['val_liq_pv'], PDO::PARAM_STR);
            $stmt->bindValue(3, $array['corte'], PDO::PARAM_STR);
            $stmt->bindValue(4, $array['id'], PDO::PARAM_INT);

            if ($stmt->execute() && $stmt->rowCount() > 0) {
                $consulta = "UPDATE `nom_liq_prima_nav` SET `fec_act` = ?, `id_user_act` = ? WHERE `id_liq_privac` = ?";
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
