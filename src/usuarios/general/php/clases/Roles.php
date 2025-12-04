<?php

namespace Src\Usuarios\General\Php\Clases;

use Config\Clases\Conexion;
use Config\Clases\Logs;
use Config\Clases\Sesion;
use PDO;
use PDOException;
use Src\Common\Php\Clases\Combos;
use Src\Common\Php\Clases\Permisos;

class Roles
{
    private $conexion;

    public function __construct()
    {
        $this->conexion = Conexion::getConexion(); // Método estático que retorna el objeto PDO
    }
    /**
     * Obtiene los datos de los usuarios.
     *
     * @param int $start Índice de inicio para la paginación
     * @param int $length Número de registros a mostrar
     * @param string $val_busca Valor de búsqueda
     * @param int $col Índice de la columna para ordenar
     * @param string $dir Dirección de ordenamiento (ascendente o descendente) 
     * @return array|[] Retorna un array con los datos de los parametros o vacio si no existe
     */
    public function getRegistrosDT($start, $length, $val_busca, $col, $dir)
    {
        $where = '';
        if ($val_busca != '') {
            $val_busca = trim($val_busca);
            $where = "AND (`nom_rol` LIKE '%$val_busca%')";
        }

        $limit = "";
        if ($length != -1) {
            $limit = "LIMIT $start, $length";
        }
        $sql = "SELECT `id_rol`, `nom_rol` FROM `seg_rol` WHERE (1 = 1 $where) ORDER BY $col $dir $limit";
        $stmt = $this->conexion->prepare($sql);
        $stmt->execute();
        $datos = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $stmt->closeCursor();
        unset($stmt);
        return $datos ?: null;
    }

    /**
     * Obtiene el total de registros filtrados.
     * 
     * @param string $val_busca Valor de búsqueda
     * @return int Total de registros filtrados
     */
    public function getRegistrosFilter($val_busca)
    {
        $where = '';
        if ($val_busca != '') {
            $val_busca = trim($val_busca);
            $where = "AND (`nom_rol` LIKE '%$val_busca%')";
        }
        $sql = "SELECT COUNT(`id_rol`) AS `total` FROM `seg_rol` WHERE (1 = 1 $where)";
        $stmt = $this->conexion->prepare($sql);
        $stmt->execute();
        $data = $stmt->fetch(PDO::FETCH_ASSOC)['total'] ?: 0;
        $stmt->closeCursor();
        unset($stmt);
        return $data;
    }

    /**
     * Obtiene el total de registros.
     * @return int Total de registros
     */

    public function getRegistrosTotal()
    {
        $sql = "SELECT COUNT(`id_rol`) AS `total` FROM `seg_rol`";
        $stmt = $this->conexion->prepare($sql);
        $stmt->execute();
        $data = $stmt->fetch(PDO::FETCH_ASSOC)['total'] ?: 0;
        $stmt->closeCursor();
        unset($stmt);
        return $data;
    }

    public function getRegistro($id)
    {
        $sql = "SELECT `id_rol`, `nom_rol` FROM `seg_rol` WHERE `id_rol` = ? AND `id_rol` > 0";
        $stmt = $this->conexion->prepare($sql);
        $stmt->bindParam(1, $id, PDO::PARAM_INT);
        $stmt->execute();

        $datos = $stmt->fetch(PDO::FETCH_ASSOC);
        $stmt->closeCursor();
        unset($stmt);
        if (empty($datos)) {
            $datos = [
                'id_rol'    =>  0,
                'nom_rol'   => ''
            ];
        }
        return $datos;
    }

    public function getFormulario($id)
    {
        $obj = $this->getRegistro($id);
        $html =
            <<<HTML
                <div class="shadow text-center rounded">
                    <div class="rounded-top py-2" style="background-color: #16a085 !important;">
                        <h5 style="color: white;" class="mb-0">GESTIÓN DE ROLES DE USUARIOS</h5>
                    </div>
                    <div class="p-3">
                        <form id="formRolUsuario">
                            <input type="number" name="id_rol" value="{$id}" class="bg-input" hidden>
                            <div class="row g-3">
                                <div class="col-md-12">
                                    <label for="txtNombreRol" class="form-label small">Nombre</label>
                                    <input type="text" class="form-control form-control-sm bg-input" id="txtNombreRol" name="txtNombreRol" placeholder="Nombre" value="{$obj['nom_rol']}">
                                </div>
                            </div>
                        </form>
                        <div class="text-end pt-3">
                            <button type="button" class="btn btn-primary btn-sm" id="btnGuardaRol">Guardar</button>
                            <button type="button" class="btn btn-secondary  btn-sm" data-bs-dismiss="modal">Cancelar</button>
                        </div>
                    </div>
                </div>
            HTML;
        return $html;
    }
    public function getFormularioPermisos($id)
    {
        $html =
            <<<HTML
                <div>
                    <div class="shadow text-center rounded">
                        <div class="rounded-top py-2" style="background-color: #16a085 !important;">
                            <h5 style="color: white;" class="mb-0">ACTUALIZAR PERMISOS DE OPCIONES</h5>
                        </div>
                        <div class="p-3">
                            <input type="hidden" id="id_rol" value="{$id}">
                            <table id="tableOpciones" class="table table-bordered table-sm table-hover table-striped w-100 shadow">
                                <thead>
                                    <tr>
                                        <th class="bg-sofia">ID</th>
                                        <th class="bg-sofia">Opción</th>
                                        <th class="bg-sofia">Consultar</th>
                                        <th class="bg-sofia">Adicionar</th>
                                        <th class="bg-sofia">Modificar</th>
                                        <th class="bg-sofia">Eliminar</th>
                                        <th class="bg-sofia">Anular</th>
                                        <th class="bg-sofia">Imprimir</th>
                                    </tr>
                                </thead>
                                <tbody></tbody>
                            </table>
                            <div class="text-end py-3">
                                <button type="button" class="btn btn-secondary  btn-sm" data-bs-dismiss="modal">Cancelar</button>
                            </div>
                        </div>
                    </div>
                </div>
            HTML;
        return $html;
    }

    public function getPermisosRolesJSON($id_rol)
    {
        $obj    = (new Permisos())->getPermisosRoles($id_rol);
        $data = [];
        foreach ($obj as $o) {
            $o = array_values($o);
            $row = [
                'id' => $o[0],
                'opcion' => $o[1]
            ];
            // Columns 2 to 7
            $cols = ['consultar', 'adicionar', 'modificar', 'eliminar', 'anular', 'imprimir'];
            foreach ($cols as $idx => $colName) {
                $dbIdx = $idx + 2;
                $val = $o[$dbIdx] == 1 ? 0 : 1;
                $estado = $o[$dbIdx] == 1 ? '<i class="fas fa-toggle-on fa-lg text-success"></i>' : '<i class="fas fa-toggle-off fa-lg text-secondary"></i>';
                $row[$colName] = '<a href="javascript:void(0)" data-id="' . $o[0] . '|' . $dbIdx . '|' . $val . '" class="estado">' . $estado . '</a>';
            }
            $data[] = $row;
        }
        return $data;
    }

    public function setPermisoRol($id_rol, $id_opcion, $col_idx, $nuevo_estado)
    {
        $cols = [
            2 => 'per_consultar',
            3 => 'per_adicionar',
            4 => 'per_modificar',
            5 => 'per_eliminar',
            6 => 'per_anular',
            7 => 'per_imprimir'
        ];

        if (!isset($cols[$col_idx])) return 'Índice de columna inválido';
        $col_name = $cols[$col_idx];

        try {
            // Check if exists
            $sql = "SELECT COUNT(*) as `count` FROM `seg_rol_permisos` WHERE `id_rol` = ? AND `id_opcion` = ?";
            $stmt = $this->conexion->prepare($sql);
            $stmt->execute([$id_rol, $id_opcion]);
            $exists = $stmt->fetch(PDO::FETCH_ASSOC)['count'] > 0;

            if ($exists) {
                $sql = "UPDATE `seg_rol_permisos` SET `$col_name` = ? WHERE `id_rol` = ? AND `id_opcion` = ?";
                $stmt = $this->conexion->prepare($sql);
                $stmt->execute([$nuevo_estado, $id_rol, $id_opcion]);
            } else {
                $sql = "INSERT INTO `seg_rol_permisos` (`id_rol`, `id_opcion`, `$col_name`) VALUES (?, ?, ?)";
                $stmt = $this->conexion->prepare($sql);
                $stmt->execute([$id_rol, $id_opcion, $nuevo_estado]);
            }
            return 'si';
        } catch (PDOException $e) {
            return 'Error SQL: ' . $e->getMessage();
        }
    }

    public function editRegistro($d)
    {
        try {
            $sql = "UPDATE `seg_rol` SET `nom_rol` = ? WHERE `id_rol` = ?";
            $stmt = $this->conexion->prepare($sql);
            $stmt->bindValue(1, $d['txtNombreRol'], PDO::PARAM_STR);
            $stmt->bindValue(2, $d['id_rol'], PDO::PARAM_INT);

            if ($stmt->execute() && $stmt->rowCount() > 0) {
                return 'si';
            } else {
                return 'No se pudo actualizar el registro.';
            }
        } catch (PDOException $e) {
            return 'Error SQL: ' . $e->getMessage();
        }
    }

    public function addRegistro($a)
    {
        try {
            $sql = "INSERT INTO `seg_rol` (`nom_rol`) VALUES (?)";
            $stmt = $this->conexion->prepare($sql);
            $stmt->bindValue(1, $a['txtNombreRol'], PDO::PARAM_STR);
            $stmt->execute();
            if ($this->conexion->lastInsertId() > 0) {
                return 'si';
            } else {
                return 'No se agregó el registro.';
            }
        } catch (PDOException $e) {
            return 'Error SQL: ' . $e->getMessage();
        }
    }

    public function setEstado($id, $estado)
    {
        try {
            return ' verificar';
            $sql = "UPDATE `seg_rol` SET `estado` = ? WHERE `id_rol` = ?";
            $stmt = $this->conexion->prepare($sql);
            $stmt->bindValue(1, $estado, PDO::PARAM_INT);
            $stmt->bindValue(2, $id, PDO::PARAM_INT);
            if ($stmt->execute() && $stmt->rowCount() > 0) {
                $consulta = "UPDATE `seg_rol` 
                                SET `fec_inactivacion` = ?
                             WHERE `id_rol` = ?";
                $stmt2 = $this->conexion->prepare($consulta);
                $stmt2->bindValue(1, Sesion::Hoy(), PDO::PARAM_STR);
                $stmt2->bindValue(2, $id, PDO::PARAM_INT);
                $stmt2->execute();
                return 'si';
            } else {
                return 'No se actualizó el estado.';
            }
        } catch (PDOException $e) {
            return 'Error SQL: ' . $e->getMessage();
        }
    }

    public function delRegistro($id)
    {
        try {
            $sql = "DELETE FROM `seg_rol` WHERE `id_rol` = ?";
            $stmt = $this->conexion->prepare($sql);
            $stmt->bindValue(1, $id, PDO::PARAM_INT);
            if ($stmt->execute() && $stmt->rowCount() > 0) {
                $consulta = "DELETE FROM `seg_rol` WHERE `id_rol` = $id";
                Logs::guardaLog($consulta);
                return 'si';
            } else {
                return 'No se eliminó el registro.';
            }
        } catch (PDOException $e) {
            return 'Error SQL: ' . $e->getMessage();
        }
    }

    public function delRegistroPermiso($a)
    {
        return 'verificar';
        if (!($this->conexion->inTransaction())) {
            $this->conexion->beginTransaction();
        }
        try {
            $sql = "DELETE FROM `seg_permisos_rol` WHERE `id_rol` = ? AND `id_modulo` = ?";
            $stmt = $this->conexion->prepare($sql);
            $stmt->bindValue(1, $a['id_user'], PDO::PARAM_INT);
            $stmt->bindValue(2, $a['id'], PDO::PARAM_INT);
            if ($stmt->execute() && $stmt->rowCount() > 0) {
                $consulta = "DELETE FROM `seg_permisos_rol` WHERE `id_rol` = {$a['id_user']} AND `id_modulo` = {$a['id']}";
                $sql = "DELETE FROM `seg_rol_permisos` WHERE `id_rol` = ? AND `id_opcion` LIKE ?";
                $stmt2 = $this->conexion->prepare($sql);
                $stmt2->bindValue(1, $a['id_user'], PDO::PARAM_INT);
                $like = $a['id'] . '%';
                $stmt2->bindValue(2, $like, PDO::PARAM_STR);
                if ($stmt2->execute()) {
                    $this->conexion->commit();
                    Logs::guardaLog($consulta);
                    $consulta = "DELETE FROM `seg_rol_permisos` WHERE `id_rol` = {$a['id_user']} AND `id_opcion` LIKE {$a['id']}%";
                    Logs::guardaLog($consulta);
                    return 'si';
                } else {
                    $this->conexion->rollBack();
                    return 'No se eliminó el registro.';
                }
            } else {
                $this->conexion->rollBack();
                return 'No se eliminó el registro.';
            }
        } catch (PDOException $e) {
            $this->conexion->rollBack();
            return 'Error SQL: ' . $e->getMessage();
        }
    }
}
