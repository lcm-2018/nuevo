<?php

namespace Src\Nomina\Liquidado\Php\Clases;

use Config\Clases\Conexion;
use Config\Clases\Sesion;
use PDO;
use PDOException;

/**
 * Clase para gestionar de nomina de los empleados Liquidado.
 *
 * Esta clase permite realizar operaciones CRUD,
 * incluyendo la obtención de registros, adición, edición y eliminación.
 */
class Reportes
{
    private $conexion;

    public function __construct($conexion = null)
    {
        $this->conexion = $conexion ?: Conexion::getConexion(); // Método estático que retorna el objeto PDO
    }

    public function addRegistro($a)
    {
        try {
            $sql = "INSERT INTO `nom_cdp_empleados` 
                    (`rubro`, `valor`, `id_nomina`, `tipo`) 
                VALUES (?, ?, ?, ?)";

            $stmt = $this->conexion->prepare($sql);
            $stmt->bindValue(1, $a['rubro'], PDO::PARAM_INT);
            $stmt->bindValue(2, $a['valor'], PDO::PARAM_STR);
            $stmt->bindValue(3, $a['id_nomina'], PDO::PARAM_INT);
            $stmt->bindValue(4, $a['tipo'], PDO::PARAM_STR);

            if (!$stmt->execute()) {
                $error = $stmt->errorInfo();
                return "Error SQL: " . $error[2];
            }

            return 'si';
        } catch (PDOException $e) {
            return 'Error SQL: ' . $e->getMessage();
        }
    }
    public function getRegistros($a)
    {
        try {
            $sql = "SELECT 
                        `padre`.`cod_pptal` AS `raiz`
                        , `padre`.`nom_rubro` AS `nombre`
                        , SUM(`nomina`.`valor`) AS `valor_total`
                    FROM 
                        `pto_cargue` AS `padre`
                        INNER JOIN 
                            `pto_presupuestos` AS `pto` ON (`padre`.`id_pto` = `pto`.`id_pto`)
                        INNER JOIN 
                            `pto_cargue` AS `hijo` ON (`hijo`.`cod_pptal` LIKE CONCAT(`padre`.`cod_pptal`, '%'))
                        INNER JOIN 
                            `nom_cdp_empleados` AS `nomina` ON (`hijo`.`id_cargue` = `nomina`.`rubro`)
                    WHERE 
                        (`pto`.`id_tipo` = 2  AND `pto`.`id_vigencia` = ? AND `nomina`.`id_nomina` = ? AND `nomina`.`tipo` = ?)
                    GROUP BY `padre`.`cod_pptal`, `padre`.`nom_rubro`
                    ORDER BY `padre`.`cod_pptal` ASC";

            $stmt = $this->conexion->prepare($sql);

            $stmt->bindValue(1, Sesion::IdVigencia(), PDO::PARAM_INT);
            $stmt->bindValue(2, $a['id_nomina'], PDO::PARAM_INT);
            $stmt->bindValue(3, $a['tipo'], PDO::PARAM_STR);

            $stmt->execute();
            $resultados = $stmt->fetchAll();

            $data = [];
            foreach ($resultados as $fila) {
                $data[$fila['raiz']] = [
                    'valor' => $fila['valor_total'],
                    'nombre' => $fila['nombre']
                ];
            }
            return $data;
        } catch (PDOException $e) {
            return 'Error SQL: ' . $e->getMessage();
        }
    }

    /**
     * Obtiene el total de registros.
     * @return int Total de registros
     */

    public function delRegsitros($id, $tipo = '')
    {
        $where = $tipo == '' ? '' : "AND `tipo` = '{$tipo}'";

        try {
            $sql = "DELETE FROM `nom_cdp_empleados` WHERE `id_nomina` = ? {$where}";
            $stmt = $this->conexion->prepare($sql);
            $stmt->bindValue(1, $id, PDO::PARAM_INT);
            $stmt->execute();
            $stmt->closeCursor();
            unset($stmt);
            return 'si';
        } catch (PDOException $e) {
            return 'Error SQL: ' . $e->getMessage();
        }
    }
}
