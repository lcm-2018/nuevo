<?php

namespace Src\Nomina\Empleados\Php\Clases;

use Config\Clases\Conexion;
use Config\Clases\Logs;
use Config\Clases\Sesion;

use PDO;
use PDOException;
use Src\Common\Php\Clases\Combos;

/**
 * Clase para gestionar centros de costo de los empleados.
 *
 * Esta clase permite realizar operaciones CRUD sobre centros de costo de los empleados,
 * incluyendo la obtención de registros, adición, edición y eliminación de centros de costo.
 */
class Ivivienda
{
    private $conexion;

    public function __construct($conexion = null)
    {
        $this->conexion = $conexion ?: Conexion::getConexion(); // Método estático que retorna el objeto PDO
    }

    /**
     * Obtiene los datos para la DataTable.
     *
     * @param int $start Índice de inicio para la paginación
     * @param int $length Número de registros a mostrar
     * @param string $array  filtros de búsqueda
     * @param int $col Índice de la columna para ordenar
     * @param string $dir Dirección de ordenamiento (ascendente o descendente) 
     * @return array|[] Retorna un array con los datos 
     */
    public function getRegistrosDT($start, $length, $array, $col, $dir)
    {
        $limit = "";
        if ($length != -1) {
            $limit = "LIMIT $start, $length";
        }

        $where = '';
        if (!empty($array)) {
            if (isset($array['value']) && $array['value'] != '') {
                $where .= " AND (`valor` LIKE '%{$array['value']}%'
                            OR DATE_FORMAT(`fec_reg`, '%Y-%m-%d') LIKE '%{$array['value']}%')";
            }

            if (isset($array['id']) && $array['id'] > 0) {
                $where .= " AND `id_empleado` = {$array['id']}";
            }
        }

        $sql = "SELECT
                    `id_intv`, `id_empleado`, `valor`, DATE_FORMAT(`fec_reg`, '%Y-%m-%d') AS `fecha`
                FROM
                    `nom_intereses_vivienda`
                WHERE (1 = 1 $where)
                ORDER BY $col $dir $limit";
        $stmt = $this->conexion->prepare($sql);
        $stmt->execute();
        $datos = $stmt->fetchAll(PDO::FETCH_ASSOC);
        return $datos ?: null;
    }
    /**
     * Obtiene el total de registros filtrados.
     * 
     * @param string $val_busca Valor de búsqueda
     * @return int Total de registros filtrados
     */
    public function getRegistrosFilter($array)
    {
        $where = '';
        if (!empty($array)) {
            if (isset($array['value']) && $array['value'] != '') {
                $where .= " AND (`valor` LIKE '%{$array['value']}%'
                            OR DATE_FORMAT(`fec_reg`, '%Y-%m-%d') LIKE '%{$array['value']}%')";
            }

            if (isset($array['id']) && $array['id'] > 0) {
                $where .= " AND `id_empleado` = {$array['id']}";
            }
        }

        $sql = "SELECT
                    COUNT(*) AS `total`
                FROM
                    `nom_intereses_vivienda`
                WHERE (1 = 1 $where)";
        $stmt = $this->conexion->prepare($sql);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC)['total'] ?: 0;
    }

    /**
     * Obtiene el total de registros.
     * @return int Total de registros
     */

    public function getRegistrosTotal($array)
    {
        $where = '';
        if (!empty($array)) {
            if (isset($array['id']) && $array['id'] > 0) {
                $where .= " AND `id_empleado` = {$array['id']}";
            }
        }

        $sql = "SELECT
                    COUNT(*) AS `total`
                FROM
                    `nom_intereses_vivienda`
                WHERE (1 = 1 $where)";
        $stmt = $this->conexion->prepare($sql);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC)['total'] ?: 0;
    }

    /**
     * Obtiene un registro por ID.
     *
     * @param int $id ID del registro
     * @return array  datos del registro
     */

    public function getRegistro($id)
    {
        $sql = "SELECT
                        `id_intv`, `valor`
                FROM `nom_intereses_vivienda`
                WHERE `id_intv` = ?";
        $stmt = $this->conexion->prepare($sql);
        $stmt->bindParam(1, $id, PDO::PARAM_INT);
        $stmt->execute();
        $registro = $stmt->fetch(PDO::FETCH_ASSOC);
        if (empty($registro)) {
            $registro = [
                'id_intv' => 0,
                'valor' => 0,
            ];
        }
        return $registro;
    }


    /**
     * Obtiene el formulario para agregar o editar un registro.
     *
     * @param int $id ID del registro (0 para nuevo)
     * @return string HTML del formulario
     */
    public function getFormulario($id)
    {
        $registro = $this->getRegistro($id);
        $html =
            <<<HTML
                <div class="shadow text-center rounded">
                    <div class="rounded-top py-2" style="background-color: #16a085 !important;">
                        <h5 style="color: white;" class="mb-0">GESTIÓN DE INTERÉS DE VIVIENDA</h5>
                    </div>
                    <div class="p-3">
                        <form id="formIvivienda">
                            <input type="hidden" id="id" name="id" value="{$id}">
                            <div class="row mb-2">
                                <div class="col-md-12">
                                    <label for="numValor" class="small text-muted">Valor</label>
                                    <input type="number" class="form-control form-control-sm bg-input text-end" id="numValor" name="numValor" value="{$registro['valor']}">
                                </div>
                            </div>
                        </form>
                    </div>
                    <div class="text-center pb-3">
                        <button type="button" class="btn btn-primary btn-sm" id="btnGuardarIvivienda">Guardar</button>
                        <a type="button" class="btn btn-secondary  btn-sm" data-bs-dismiss="modal">Cancelar</a>
                    </div>
                </div>
            HTML;
        return $html;
    }

    /**
     * Elimina un registro.
     *
     * @param int $id ID del registro a eliminar
     * @return string Mensaje de éxito o error
     */

    public function delRegistro($id)
    {
        try {
            $sql = "DELETE FROM `nom_intereses_vivienda` WHERE `id_intv` = ?";
            $consulta  = "DELETE FROM `nom_intereses_vivienda` WHERE `id_intv` = $id";
            $stmt = $this->conexion->prepare($sql);
            $stmt->bindParam(1, $id, PDO::PARAM_INT);
            $stmt->execute();
            if ($stmt->rowCount() > 0) {
                Logs::guardaLog($consulta);
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
        try {
            $sql = "INSERT INTO `nom_intereses_vivienda`
                        (`id_empleado`,`valor`,`id_user_reg`,`fec_reg`)
                    VALUES (?, ?, ?, ?)";
            $stmt = $this->conexion->prepare($sql);
            $stmt->bindValue(1, $array['id_empleado'], PDO::PARAM_INT);
            $stmt->bindValue(2, $array['numValor'], PDO::PARAM_INT);
            $stmt->bindValue(3, Sesion::IdUser(), PDO::PARAM_INT);
            $stmt->bindValue(4, Sesion::Hoy(), PDO::PARAM_STR);
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
    public function editRegistro($array)
    {
        try {
            $sql = "UPDATE `nom_intereses_vivienda`
                        SET `valor` = ?
                    WHERE `id_intv` = ?";
            $stmt = $this->conexion->prepare($sql);
            $stmt->bindValue(1, $array['numValor'], PDO::PARAM_INT);
            $stmt->bindValue(2, $array['id'], PDO::PARAM_INT);

            if ($stmt->execute() && $stmt->rowCount() > 0) {
                $consulta = "UPDATE `nom_intereses_vivienda` SET `fec_act` = ?, `id_user_act` = ? WHERE `id_intv` = ?";
                $stmt2 = $this->conexion->prepare($consulta);
                $stmt2->bindValue(1, Sesion::Hoy(), PDO::PARAM_STR);
                $stmt2->bindValue(2, Sesion::IdUser(), PDO::PARAM_INT);
                $stmt2->bindValue(3, $array['id'], PDO::PARAM_INT);
                $stmt2->execute();
                return 'si';
            } else {
                return 'No se actualizó el registro.';
            }
        } catch (PDOException $e) {
            return 'Error SQL: ' . $e->getMessage();
        }
    }

    public function getIviviendaEmpleados($ids)
    {
        if (empty($ids)) {
            return [];
        } else {
            $ids = implode(',', $ids);
        }
        $sql = "SELECT 
                `id_empleado`,`valor`
            FROM `nom_intereses_vivienda` 
            WHERE `id_intv` IN 
                (SELECT MAX(`id_intv`) 
                FROM `nom_intereses_vivienda` 
                GROUP BY `id_empleado`)
                AND `id_empleado` IN ($ids)";
        $stmt = $this->conexion->prepare($sql);
        $stmt->execute();
        $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $stmt->closeCursor();
        unset($stmt);
        return $result;
    }
}
