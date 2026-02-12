<?php

namespace Src\Nomina\Empleados\Php\Clases;

use Config\Clases\Conexion;
use Config\Clases\Logs;
use Config\Clases\Sesion;
use PDO;
use PDOException;

/**
 * Clase para gestionar la liquidación de viáticos en nómina.
 * Se encarga de guardar y gestionar la tabla nom_liq_viaticos.
 */
class ViaticosLiq
{
    private $conexion;

    public function __construct($conexion = null)
    {
        $this->conexion = $conexion ?: Conexion::getConexion();
    }

    /**
     * Agrega un nuevo registro de liquidación de viático.
     *
     * @param array $array Datos del registro a agregar: 'id_viatico', 'valor', 'id_nomina'
     * @return string 'si' en caso de éxito, mensaje de error en caso contrario
     */
    public function addRegistro($array)
    {
        try {
            $sql = "INSERT INTO `nom_liq_viaticos`
	                    (`id_viatico`,`valor`,`id_nomina`,`id_user_reg`,`fec_reg`)
                    VALUES (?, ?, ?, ?, ?)";
            $stmt = $this->conexion->prepare($sql);
            $stmt->bindValue(1, $array['id_viatico'], PDO::PARAM_INT);
            $stmt->bindValue(2, $array['valor'], PDO::PARAM_STR);
            $stmt->bindValue(3, $array['id_nomina'], PDO::PARAM_INT);
            $stmt->bindValue(4, Sesion::IdUser(), PDO::PARAM_INT);
            $stmt->bindValue(5, Sesion::Hoy(), PDO::PARAM_STR);
            $stmt->execute();

            if ($this->conexion->lastInsertId() > 0) {
                return 'si';
            } else {
                return 'No se insertó el registro de viático liquidado';
            }
        } catch (PDOException $e) {
            return 'Error SQL en ViaticosLiq: ' . $e->getMessage();
        }
    }

    /**
     * Elimina registros de liquidación de viáticos para una nómina específica.
     * Útil si se necesita limpiar antes de reliquidar masivamente.
     */
    public function delRegistrosPorNomina($id_nomina)
    {
        try {
            $sql = "DELETE FROM `nom_liq_viaticos` WHERE `id_nomina` = ?";
            $stmt = $this->conexion->prepare($sql);
            $stmt->bindValue(1, $id_nomina, PDO::PARAM_INT);
            $stmt->execute();
            return 'si';
        } catch (PDOException $e) {
            return 'Error SQL en ViaticosLiq: ' . $e->getMessage();
        }
    }
}
