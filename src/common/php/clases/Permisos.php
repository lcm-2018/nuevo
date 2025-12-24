<?php

namespace Src\Common\Php\Clases;

use Config\Clases\Conexion;
use Config\Clases\Logs;
use Config\Clases\Sesion;
use PDO;
use PDOException;

class Permisos
{
    private $conexion;

    public function __construct()
    {
        $this->conexion = Conexion::getConexion(); // Método estático que retorna el objeto PDO
    }

    /**
     * Obtiene los módulos a  los que el usuario tiene permiso.
     * @param int $iduser ID del usuario
     * @return array Retorna un array con los módulos que tiene permiso
     */

    public function PermisoModulos($iduser)
    {
        $sql = "SELECT `id_per_mod`, `id_usuario`, `id_modulo` FROM `seg_permisos_modulos` WHERE `id_usuario` = ?";
        $stmt = $this->conexion->prepare($sql);
        $stmt->bindParam(1, $iduser, PDO::PARAM_INT);
        $stmt->execute();
        $modulos = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $stmt->closeCursor();
        unset($stmt);
        return $modulos;
    }

    /**
     * Obtiene las opciones a las que el usuario tiene permiso.
     * @param int $id_user ID del usuario
     * @return array Retorna un array con las opciones que tiene permiso
     */
    public function PermisoOpciones($id_user)
    {
        $sql = "SELECT
                    `id_opcion`, `per_consultar`, `per_adicionar`, `per_modificar`, `per_eliminar`, `per_anular`, `per_imprimir`
                FROM
                    `seg_rol_usuario`
                WHERE (`id_usuario` = ?)";
        $stmt = $this->conexion->prepare($sql);
        $stmt->bindParam(1, $id_user, PDO::PARAM_INT);
        $stmt->execute();
        $opciones = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $stmt->closeCursor();
        unset($stmt);
        return $opciones;
    }

    /**
     * Verifica si el usuario tiene permiso para una opción específica.
     * @param array $array Array de opciones y permisos
     * @param int $opcion ID de la opción a verificar
     * @param int $tipo Tipo de permiso (0: sin permiso, 1: consultar, 2: adicionar, 3: modificar, 4: eliminar, 5: anular, 6: imprimir)
     * @return bool Retorna true si tiene permiso, false en caso contrario
     */
    public function PermisosUsuario($array, $opcion, $tipo)
    {
        $comp = false;

        $key = array_search($opcion, array_column($array, 'id_opcion'));

        if ($key !== false) {
            if ($tipo == 0) {
                $comp = true;
            } else {
                $permiso = 'per_' . $this->obtenerNombrePermiso($tipo);
                $comp = $array[$key][$permiso] == 1;
            }
        }

        return $comp;
    }

    private function obtenerNombrePermiso($tipo)
    {
        $permisos = [
            1 => 'consultar',
            2 => 'adicionar',
            3 => 'modificar',
            4 => 'eliminar',
            5 => 'anular',
            6 => 'imprimir',
        ];

        return $permisos[$tipo] ?? '';
    }

    public function getPermisosModulos($id_user)
    {
        $sql = "SELECT
                    `sm`.`id_modulo`
                    , `sm`.`nom_modulo`
                    , IFNULL(`spm`.`id_per_mod`,0) AS `estado` 
                FROM
                    `seg_modulos` AS `sm`
                    LEFT JOIN  `seg_permisos_modulos` AS `spm`
                    ON (`spm`.`id_modulo` = `sm`.`id_modulo` AND `spm`.`id_usuario` = ?)";
        $stmt = $this->conexion->prepare($sql);
        $stmt->bindParam(1, $id_user, PDO::PARAM_INT);
        $stmt->execute();
        $modulos = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $stmt->closeCursor();
        unset($stmt);
        return $modulos;
    }
    public function getPermisosOpciones($id_user, $id_modulo)
    {
        $sql = "SELECT
                    `so`.`id_opcion`
                    , `so`.`nom_opcion`
                    , `sru`.`per_consultar`
                    , `sru`.`per_adicionar`
                    , `sru`.`per_modificar`
                    , `sru`.`per_eliminar`
                    , `sru`.`per_anular`
                    , `sru`.`per_imprimir`
                FROM
                    `seg_opciones` AS `so`
                    LEFT JOIN `seg_rol_usuario` AS `sru` 
                        ON (`sru`.`id_opcion` = `so`.`id_opcion` AND `sru`.`id_usuario`  = ?)
                WHERE (`so`.`id_modulo` = ?)";
        $stmt = $this->conexion->prepare($sql);
        $stmt->bindParam(1, $id_user, PDO::PARAM_INT);
        $stmt->bindParam(2, $id_modulo, PDO::PARAM_INT);
        $stmt->execute();
        $opciones = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $stmt->closeCursor();
        unset($stmt);
        return $opciones;
    }

    public function addRegistro($d)
    {
        try {
            $sql = "INSERT INTO `seg_rol_usuario`
                        (`id_usuario`,`id_opcion`,`per_consultar`,`per_adicionar`,`per_modificar`,`per_eliminar`,`per_anular`,`per_imprimir`)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
            $stmt = $this->conexion->prepare($sql);
            $stmt->bindValue(1, $d['id_usuario'], PDO::PARAM_INT);
            $stmt->bindValue(2, $d['id_opcion'], PDO::PARAM_INT);
            $stmt->bindValue(3, $d['per_consultar'], PDO::PARAM_INT);
            $stmt->bindValue(4, $d['per_adicionar'], PDO::PARAM_INT);
            $stmt->bindValue(5, $d['per_modificar'], PDO::PARAM_INT);
            $stmt->bindValue(6, $d['per_eliminar'], PDO::PARAM_INT);
            $stmt->bindValue(7, $d['per_anular'], PDO::PARAM_INT);
            $stmt->bindValue(8, $d['per_imprimir'], PDO::PARAM_INT);
            $stmt->execute();
            if ($stmt->rowCount() > 0) {
                return 'si';
            } else {
                return 'Sin insertar';
            }
        } catch (PDOException $e) {
            return 'Error SQL: ' . $e->getMessage();
        }
    }

    public function delRegistro($d)
    {
        try {
            $sql = "DELETE FROM `seg_rol_usuario` WHERE `id_usuario` = ?";
            $stmt = $this->conexion->prepare($sql);
            $stmt->bindValue(1, $d, PDO::PARAM_INT);
            $stmt->execute();
            if ($stmt->rowCount() > 0) {
                $consulta = "DELETE FROM `seg_rol_usuario` WHERE `id_usuario` = {$d}";
                Logs::guardaLog($consulta);
                return 'si';
            } else {
                return 'Sin insertar';
            }
        } catch (PDOException $e) {
            return 'Error SQL: ' . $e->getMessage();
        }
    }
    public function getPermisosRoles($id_rol)
    {
        $sql = "SELECT
                    `so`.`id_opcion`
                    , `so`.`nom_opcion`
                    , `srp`.`per_consultar`
                    , `srp`.`per_adicionar`
                    , `srp`.`per_modificar`
                    , `srp`.`per_eliminar`
                    , `srp`.`per_anular`
                    , `srp`.`per_imprimir`
                FROM
                    `seg_opciones` AS `so`
                    LEFT JOIN `seg_rol_permisos` AS `srp` 
                        ON (`srp`.`id_opcion` = `so`.`id_opcion` AND `srp`.`id_rol` = ?)";
        $stmt = $this->conexion->prepare($sql);
        $stmt->bindParam(1, $id_rol, PDO::PARAM_INT);
        $stmt->execute();
        $opciones = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $stmt->closeCursor();
        unset($stmt);
        return $opciones;
    }

    public function getMesesCierre()
    {
        $sql = "SELECT 
                    `seg_modulos`.`id_modulo`,
                    `seg_modulos`.`nom_modulo`,
                    MAX(CASE WHEN `tb_fin_periodos`.`mes` = '01' THEN 1 ELSE 0 END) AS `ene`,
                    MAX(CASE WHEN `tb_fin_periodos`.`mes` = '02' THEN 1 ELSE 0 END) AS `feb`,
                    MAX(CASE WHEN `tb_fin_periodos`.`mes` = '03' THEN 1 ELSE 0 END) AS `mar`,
                    MAX(CASE WHEN `tb_fin_periodos`.`mes` = '04' THEN 1 ELSE 0 END) AS `abr`,
                    MAX(CASE WHEN `tb_fin_periodos`.`mes` = '05' THEN 1 ELSE 0 END) AS `may`,
                    MAX(CASE WHEN `tb_fin_periodos`.`mes` = '06' THEN 1 ELSE 0 END) AS `jun`,
                    MAX(CASE WHEN `tb_fin_periodos`.`mes` = '07' THEN 1 ELSE 0 END) AS `jul`,
                    MAX(CASE WHEN `tb_fin_periodos`.`mes` = '08' THEN 1 ELSE 0 END) AS `ago`,
                    MAX(CASE WHEN `tb_fin_periodos`.`mes` = '09' THEN 1 ELSE 0 END) AS `sep`,
                    MAX(CASE WHEN `tb_fin_periodos`.`mes` = '10' THEN 1 ELSE 0 END) AS `oct`,
                    MAX(CASE WHEN `tb_fin_periodos`.`mes` = '11' THEN 1 ELSE 0 END) AS `nov`,
                    MAX(CASE WHEN `tb_fin_periodos`.`mes` = '12' THEN 1 ELSE 0 END) AS `dic`

                FROM `seg_modulos`
                LEFT JOIN `tb_fin_periodos`
                    ON (`seg_modulos`.`id_modulo` = `tb_fin_periodos`.`id_modulo`
                        AND `tb_fin_periodos`.`vigencia` = ?)

                WHERE `seg_modulos`.`id_modulo` >= 50

                GROUP BY `seg_modulos`.`id_modulo`, `seg_modulos`.`nom_modulo`
                ORDER BY `seg_modulos`.`id_modulo`";
        $stmt = $this->conexion->prepare($sql);
        $stmt->bindValue(1, Sesion::Vigencia(), PDO::PARAM_INT);
        $stmt->execute();
        $opciones = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $stmt->closeCursor();
        unset($stmt);
        return $opciones;
    }
}
