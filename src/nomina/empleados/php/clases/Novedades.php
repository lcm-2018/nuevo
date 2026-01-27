<?php

namespace Src\Nomina\Empleados\Php\Clases;

use Config\Clases\Conexion;
use Config\Clases\Sesion;

use PDO;
use PDOException;
use DateTime;

class Novedades
{
    private $conexion;

    public function __construct($conexion = null)
    {
        $this->conexion = $conexion ?: Conexion::getConexion(); // Método estático que retorna el objeto PDO
    }

    /**
     * Elimina un registro.
     *
     * @param int $tipo ID  del tipo de novedad
     * @param int $novedad ID de la novedad a eliminar
     * @return string Mensaje de éxito o error
     */

    public function delRegistro($tipo, $novedad)
    {
        try {
            $sql = "DELETE FROM `nom_calendar_novedad` WHERE `id_tipo` = ? AND `id_novedad` = ?";
            $stmt = $this->conexion->prepare($sql);
            $stmt->bindParam(1, $tipo, PDO::PARAM_INT);
            $stmt->bindParam(2, $novedad, PDO::PARAM_INT);
            $stmt->execute();
            if ($stmt->rowCount() > 0) {
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
    public function addRegistro($array, $op = true)
    {
        try {
            $fecha_inicio = new DateTime($array['datFecInicia']);
            $fecha_fin = new DateTime($array['datFecFin']);

            $sql = "INSERT INTO `nom_calendar_novedad`
                    (`id_empleado`, `id_tipo`, `id_novedad`, `fecha`, `id_user_reg`, `fec_reg`)
                VALUES (?, ?, ?, ?, ?, ?)";
            $stmt = $this->conexion->prepare($sql);
            $stmt->bindValue(1, $array['id_empleado'], PDO::PARAM_INT);
            $stmt->bindValue(2, $array['tipo'], PDO::PARAM_INT);
            $stmt->bindValue(3, $array['novedad'], PDO::PARAM_INT);
            $stmt->bindValue(5, Sesion::IdUser(), PDO::PARAM_INT);
            $stmt->bindValue(6, Sesion::Hoy(), PDO::PARAM_STR);
            while ($fecha_inicio <= $fecha_fin) {
                // Verificar si ya existe un cruce de fechas
                if ($op) {
                    if (($this->getCruceFechas($array['id_empleado'], $fecha_inicio->format('Y-m-d'))) == 1 && $array['tipo'] != 6) {
                        return 'Existe cruce de fecha: ' . $fecha_inicio->format('Y-m-d');
                    }
                }
                $stmt->bindValue(4, $fecha_inicio->format('Y-m-d'), PDO::PARAM_STR);
                $stmt->execute();
                $fecha_inicio->modify('+1 day');
            }

            return 'si';
        } catch (PDOException $e) {
            return 'Error SQL: ' . $e->getMessage();
        }
    }

    private function getCruceFechas($id_empleado, $fecha)
    {
        try {
            $sql = "SELECT * FROM `nom_calendar_novedad`
                    WHERE `id_empleado` = ? AND `fecha` = ?";
            $stmt = $this->conexion->prepare($sql);
            $stmt->bindParam(1, $id_empleado, PDO::PARAM_INT);
            $stmt->bindParam(2, $fecha, PDO::PARAM_STR);
            $stmt->execute();
            $data = $stmt->fetch(PDO::FETCH_ASSOC);
            $stmt->closeCursor();
            unset($stmt);
            if (!empty($data)) {
                return 1;
            } else {
                return 0;
            }
        } catch (PDOException $e) {
            return 'Error SQL: ' . $e->getMessage();
        }
    }
}
