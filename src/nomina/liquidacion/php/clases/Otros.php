<?php

namespace Src\Nomina\Liquidacion\Php\Clases;

use Config\Clases\Conexion;
use Config\Clases\Sesion;
use PDO;
use PDOException;

class Otros
{
    private $conexion;

    public function __construct($conexion = null)
    {
        $this->conexion = $conexion ?: Conexion::getConexion(); // Método estático que retorna el objeto PDO
    }
    public function getRegistroLiq($array)
    {
        $sql = "SELECT
                    `id_liq_dlab_auxt` AS `id`,`dias_liq` AS `dias`,`val_liq_dias` AS `val_laborado`,`val_liq_auxt` AS `val_auxtrans`,`aux_alim` AS `auxalim`,`g_representa` AS `grepre`,`tipo`
                FROM
                    `nom_liq_dlab_auxt`
                WHERE (`id_empleado` = ? AND `id_nomina` = ? AND `estado` = 1)";
        $stmt = $this->conexion->prepare($sql);
        $stmt->bindParam(1, $array['id_empleado'], PDO::PARAM_INT);
        $stmt->bindParam(2, $array['id_nomina'], PDO::PARAM_INT);
        $stmt->execute();
        $data = $stmt->fetch(PDO::FETCH_ASSOC);
        $stmt->closeCursor();
        unset($stmt);
        return !empty($data) ? $data : ['id' => 0, 'dias' => 0, 'val_laborado' => 0, 'val_auxtrans' => 0, 'auxalim' => 0, 'grepre' => 0, 'tipo' => 'S'];
    }

    public function editRegistroLabliq($a)
    {
        try {
            $sql = "UPDATE `nom_liq_dlab_auxt`
                        SET `dias_liq` = ?,`val_liq_dias` = ?,`val_liq_auxt` = ?,`aux_alim` = ?,`g_representa` = ?,`tipo` = ?
                    WHERE `id_liq_dlab_auxt` = ?";
            $stmt = $this->conexion->prepare($sql);

            $stmt->bindParam(1, $a['dias'], PDO::PARAM_INT);
            $stmt->bindParam(2, $a['laborado'], PDO::PARAM_STR);
            $stmt->bindParam(3, $a['auxtrans'], PDO::PARAM_STR);
            $stmt->bindParam(4, $a['auxalim'], PDO::PARAM_STR);
            $stmt->bindParam(5, $a['grepre'], PDO::PARAM_STR);
            $stmt->bindParam(6, $a['tipo'], PDO::PARAM_STR);
            $stmt->bindParam(7, $a['id'], PDO::PARAM_INT);
            if ($stmt->execute() && $stmt->rowCount() > 0) {
                $consulta = "UPDATE `nom_liq_dlab_auxt` SET `fec_act` = ?, `id_user_act` = ? WHERE `id_liq_dlab_auxt` = ?";
                $stmt2 = $this->conexion->prepare($consulta);
                $stmt2->bindValue(1, Sesion::Hoy(), PDO::PARAM_STR);
                $stmt2->bindValue(2, Sesion::IdUser(), PDO::PARAM_INT);
                $stmt2->bindValue(3, $a['id'], PDO::PARAM_INT);
                $stmt2->execute();
                return 'si';
            } else {
                return 'no';
            }
        } catch (PDOException $e) {
            return 'Error SQL: ' . $e->getMessage();
        }
    }
}
