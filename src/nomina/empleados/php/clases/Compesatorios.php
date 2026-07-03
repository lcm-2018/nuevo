<?php

namespace Src\Nomina\Empleados\Php\Clases;

use Config\Clases\Conexion;
use Config\Clases\Logs;

use PDO;
use PDOException;

/**
 * Clase para gestionar compensatorios de los empleados.
 *
 * Esta clase permite realizar operaciones de adición y edición de compensatorios.
 */
class Compesatorios
{
    private $conexion;

    public function __construct($conexion = null)
    {
        $this->conexion = $conexion ?: Conexion::getConexion(); // Método estático que retorna el objeto PDO
    }

    /**
     * Agrega un nuevo registro.
     *
     * @param array $array Datos del registro a agregar
     * @return string Mensaje de éxito o error
     */
    public function addRegistro($array)
    {
        try {
            $sql = "INSERT INTO `nom_liq_compesatorio`
                        (`id_empleado`, `val_compensa`, `dias`, `id_nomina`)
                    VALUES (?, ?, ?, ?)";
            $stmt = $this->conexion->prepare($sql);
            $stmt->bindValue(1, isset($array['id_empleado']) ? $array['id_empleado'] : null, PDO::PARAM_INT);
            $stmt->bindValue(2, isset($array['val_compensa']) ? $array['val_compensa'] : null, PDO::PARAM_STR);
            $stmt->bindValue(3, isset($array['dias']) ? $array['dias'] : null, PDO::PARAM_INT);
            $stmt->bindValue(4, isset($array['id_nomina']) ? $array['id_nomina'] : null, PDO::PARAM_INT);
            $stmt->execute();
            $id = $this->conexion->lastInsertId();
            if ($id > 0) {
                $idEmpleado = isset($array['id_empleado']) ? $array['id_empleado'] : 'NULL';
                $val = isset($array['val_compensa']) ? $array['val_compensa'] : 'NULL';
                $dias = isset($array['dias']) ? $array['dias'] : 'NULL';
                $idNomina = isset($array['id_nomina']) ? $array['id_nomina'] : 'NULL';
                Logs::guardaLog("INSERT INTO `nom_liq_compesatorio` (`id_empleado`, `val_compensa`, `dias`, `id_nomina`) VALUES ($idEmpleado, $val, $dias, $idNomina)");
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
    public function editRegistro($array)
    {
        try {
            $sql = "UPDATE `nom_liq_compesatorio`
                        SET `val_compensa` = ?,
                            `dias` = ?,
                            `id_nomina` = ?
                    WHERE `id_compensa` = ?";
            $stmt = $this->conexion->prepare($sql);
            $stmt->bindValue(1, isset($array['val_compensa']) ? $array['val_compensa'] : null, PDO::PARAM_STR);
            $stmt->bindValue(2, isset($array['dias']) ? $array['dias'] : null, PDO::PARAM_INT);
            $stmt->bindValue(3, isset($array['id_nomina']) ? $array['id_nomina'] : null, PDO::PARAM_INT);
            $stmt->bindValue(4, $array['id'], PDO::PARAM_INT);

            if ($stmt->execute()) {
                if ($stmt->rowCount() > 0) {
                    $val = isset($array['val_compensa']) ? $array['val_compensa'] : 'NULL';
                    $dias = isset($array['dias']) ? $array['dias'] : 'NULL';
                    $idNomina = isset($array['id_nomina']) ? $array['id_nomina'] : 'NULL';
                    Logs::guardaLog("UPDATE `nom_liq_compesatorio` SET `val_compensa` = $val, `dias` = $dias, `id_nomina` = $idNomina WHERE `id_compensa` = {$array['id']}");
                }
                return 'si';
            } else {
                return 'No se actualizó el registro.';
            }
        } catch (PDOException $e) {
            return 'Error SQL: ' . $e->getMessage();
        }
    }
}
