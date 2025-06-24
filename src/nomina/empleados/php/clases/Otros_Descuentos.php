<?php

namespace Src\Nomina\Empleados\Php\Clases;

use Config\Clases\Conexion;
use Config\Clases\Logs;
use Config\Clases\Sesion;

use PDO;
use PDOException;
use Src\Common\Php\Clases\Combos;

/**
 * Clase para gestionar otros descuentos de los empleados.
 *
 * Esta clase permite realizar operaciones CRUD sobre otros descuentos de los empleados,
 * incluyendo la obtención de registros, adición, edición y eliminación de otros descuentos.
 */
class Otros_Descuentos
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
                $where .= " AND (`nom_tipo_descuentos`.`descripcion` LIKE '%{$array['value']}%'
                            OR `nom_otros_descuentos`.`concepto` LIKE '%{$array['value']}%'
                            OR `nom_otros_descuentos`.`fecha` LIKE '%{$array['value']}%'
                            OR `nom_otros_descuentos`.`fecha_fin` LIKE '%{$array['value']}%'
                            OR `nom_otros_descuentos`.`valor` LIKE '%{$array['value']}%')";
            }

            if (isset($array['id']) && $array['id'] > 0) {
                $where .= " AND `nom_otros_descuentos`.`id_empleado` = {$array['id']}";
            }
        }

        $sql = "SELECT
                    `nom_otros_descuentos`.`id_dcto`
                    , `nom_otros_descuentos`.`id_empleado`
                    , `nom_otros_descuentos`.`id_tipo_dcto`
                    , `nom_tipo_descuentos`.`descripcion`
                    , `nom_otros_descuentos`.`fecha`
                    , `nom_otros_descuentos`.`fecha_fin`
                    , `nom_otros_descuentos`.`concepto`
                    , `nom_otros_descuentos`.`valor`
                    , `nom_otros_descuentos`.`estado`
                    , IFNULL(`aportado`.`valor`, 0) AS `aportado`
                FROM
                    `nom_otros_descuentos`
                    INNER JOIN `nom_tipo_descuentos` 
                        ON (`nom_otros_descuentos`.`id_tipo_dcto` = `nom_tipo_descuentos`.`id_tipo`)
                    LEFT JOIN 
                        (SELECT
                            `id_dcto`, SUM(`valor`) AS `valor`
                        FROM
                            `nom_liq_descuento`
                        GROUP BY `id_dcto`) AS `aportado`
                        ON (`nom_otros_descuentos`.`id_dcto` = `aportado`.`id_dcto`)
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
                $where .= " AND (`nom_tipo_descuentos`.`descripcion` LIKE '%{$array['value']}%'
                            OR `nom_otros_descuentos`.`concepto` LIKE '%{$array['value']}%'
                            OR `nom_otros_descuentos`.`fecha` LIKE '%{$array['value']}%'
                            OR `nom_otros_descuentos`.`fecha_fin` LIKE '%{$array['value']}%'
                            OR `nom_otros_descuentos`.`valor` LIKE '%{$array['value']}%')";
            }

            if (isset($array['id']) && $array['id'] > 0) {
                $where .= " AND `nom_otros_descuentos`.`id_empleado` = {$array['id']}";
            }
        }

        $sql = "SELECT
                    COUNT(*) AS `total`
                FROM
                    `nom_otros_descuentos`
                    INNER JOIN `nom_tipo_descuentos` 
                        ON (`nom_otros_descuentos`.`id_tipo_dcto` = `nom_tipo_descuentos`.`id_tipo`)
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
                $where .= " AND `nom_otros_descuentos`.`id_empleado` = {$array['id']}";
            }
        }

        $sql = "SELECT
                    COUNT(*) AS `total`
                FROM
                    `nom_otros_descuentos`
                    INNER JOIN `nom_tipo_descuentos` 
                        ON (`nom_otros_descuentos`.`id_tipo_dcto` = `nom_tipo_descuentos`.`id_tipo`)
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
                    `id_dcto`,`id_empleado`,`id_tipo_dcto`,`fecha`,`fecha_fin`,`concepto`,`valor`,`estado`
                FROM `nom_otros_descuentos`
                WHERE `id_dcto` = ?";
        $stmt = $this->conexion->prepare($sql);
        $stmt->bindParam(1, $id, PDO::PARAM_INT);
        $stmt->execute();
        $registro = $stmt->fetch(PDO::FETCH_ASSOC);
        if (empty($registro)) {
            $registro = [
                'id_dcto' => 0,
                'id_empleado' => 0,
                'id_tipo_dcto' => 0,
                'fecha' => Sesion::_Hoy(),
                'fecha_fin' => '',
                'concepto' => '',
                'valor' => 0,
                'estado' => 1
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
        $tipo = $this->getTiposDescuentos($registro['id_tipo_dcto']);
        $html =
            <<<HTML
                <div class="shadow text-center rounded">
                    <div class="rounded-top py-2" style="background-color: #16a085 !important;">
                        <h5 style="color: white;" class="mb-0">GESTIÓN DE OTROS DESCUENTOS</h5>
                    </div>
                    <div class="p-3">
                        <form id="formOtroDescuento">
                            <input type="hidden" id="id" name="id" value="{$id}">
                            <div class="row mb-2">
                                <div class="col-md-6">
                                    <label for="slcTipoDcto" class="small text-muted">TIPO</label>
                                    <select class="form-select form-select-sm bg-input" id="slcTipoDcto" name="slcTipoDcto">
                                        {$tipo}
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label for="numValor" class="small text-muted">Valor</label>
                                    <input type="number" class="form-control form-control-sm bg-input text-end" id="numValor" name="numValor" value="{$registro['valor']}">
                                </div>
                            </div>
                            <div class="row mb-2">
                                <div class="col-md-6">
                                    <label for="datFecInicia" class="small text-muted">Inicia</label>
                                    <input type="date" class="form-control form-control-sm bg-input" id="datFecInicia" name="datFecInicia" value="{$registro['fecha']}">
                                </div>
                                <div class="col-md-6">
                                    <label for="datFecFin" class="small text-muted">Termina</label>
                                    <input type="date" class="form-control form-control-sm bg-input" id="datFecFin" name="datFecFin" value="{$registro['fecha_fin']}">
                                </div>
                            </div>
                            <div class="row mb-2">
                                <div class="col-md-12">
                                    <label for="txtDescribe" class="small text-muted">Descripción</label>
                                    <textarea class="form-control form-control-sm bg-input" id="txtDescribe" name="txtDescribe">{$registro['concepto']}</textarea>
                                </div>
                            </div>
                        </form>
                    </div>
                    <div class="text-center pb-3">
                        <button type="button" class="btn btn-primary btn-sm" id="btnGuardarOtroDescuento">Guardar</button>
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
            $sql = "DELETE FROM `nom_otros_descuentos` WHERE `id_dcto` = ?";
            $consulta  = "DELETE FROM `nom_otros_descuentos` WHERE `id_dcto` = $id";
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
            $sql = "INSERT INTO `nom_otros_descuentos`
                        (`id_empleado`,`id_tipo_dcto`,`fecha`,`fecha_fin`,`concepto`,`valor`,`estado`,`id_user_reg`,`fec_reg`)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $stmt = $this->conexion->prepare($sql);
            $stmt->bindValue(1, $array['id_empleado'], PDO::PARAM_INT);
            $stmt->bindValue(2, $array['slcTipoDcto'], PDO::PARAM_INT);
            $stmt->bindValue(3, $array['datFecInicia'], PDO::PARAM_STR);
            $stmt->bindValue(4, $array['datFecFin'] == '' ? NULL : $array['datFecFin'], PDO::PARAM_STR);
            $stmt->bindValue(5, $array['txtDescribe'], PDO::PARAM_STR);
            $stmt->bindValue(6, $array['numValor'], PDO::PARAM_STR);
            $stmt->bindValue(7, 1, PDO::PARAM_INT);
            $stmt->bindValue(8, Sesion::IdUser(), PDO::PARAM_INT);
            $stmt->bindValue(9, Sesion::Hoy(), PDO::PARAM_STR);
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
            $sql = "UPDATE `nom_otros_descuentos`
                        SET `id_tipo_dcto` = ?, `fecha` = ?, `fecha_fin` = ?, `concepto` = ?, `valor` = ?
                    WHERE `id_dcto` = ?";
            $stmt = $this->conexion->prepare($sql);
            $stmt->bindValue(1, $array['slcTipoDcto'], PDO::PARAM_INT);
            $stmt->bindValue(2, $array['datFecInicia'], PDO::PARAM_STR);
            $stmt->bindValue(3, $array['datFecFin'] == '' ? NULL : $array['datFecFin'], PDO::PARAM_STR);
            $stmt->bindValue(4, $array['txtDescribe'], PDO::PARAM_STR);
            $stmt->bindValue(5, $array['numValor'], PDO::PARAM_STR);
            $stmt->bindValue(6, $array['id'], PDO::PARAM_INT);

            if ($stmt->execute() && $stmt->rowCount() > 0) {
                $consulta = "UPDATE `nom_otros_descuentos` SET `fec_act` = ?, `id_user_act` = ? WHERE `id_dcto` = ?";
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

    public function annulRegistro($array)
    {
        try {
            $sql = "UPDATE `nom_otros_descuentos`
                        SET `estado` = ?
                    WHERE `id_dcto` = ?";
            $stmt = $this->conexion->prepare($sql);
            $stmt->bindValue(1, $array['estado'], PDO::PARAM_INT);
            $stmt->bindValue(2, $array['id'], PDO::PARAM_INT);
            if ($stmt->execute() && $stmt->rowCount() > 0) {
                return 'si';
            } else {
                return 'No se hizo el cambio de estado.' . $stmt->errorInfo()[2];
            }
        } catch (PDOException $e) {
            return 'Error SQL: ' . $e->getMessage();
        }
    }
    /**
     * Obtiene los tipos de descuentos.
     *
     * @return string HTML con las opciones de tipo de descuento
     */
    public function getTiposDescuentos($id)
    {
        $sql = "SELECT
                    `id_tipo`, `descripcion`
                FROM
                    `nom_tipo_descuentos` 
                    ORDER BY `descripcion`";
        return (new Combos)->setConsulta($sql, $id);
    }
}
