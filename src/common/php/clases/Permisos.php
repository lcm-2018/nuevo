<?php

namespace Src\Common\Php\Clases;

use Config\Clases\Conexion;
use PDO;

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
}
