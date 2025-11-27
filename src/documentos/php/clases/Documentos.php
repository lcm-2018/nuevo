<?php

namespace Src\Documentos\Php\Clases;

use Config\Clases\Conexion;
use Config\Clases\Logs;
use Config\Clases\Sesion;

use PDO;
use PDOException;
use DateTime;
use Src\Common\Php\Clases\Combos;

/**
 * Clase para gestionar horas extras de los empleados.
 *
 * Esta clase permite realizar operaciones CRUD sobre horas extras de los empleados,
 * incluyendo la obtención de registros, adición, edición y eliminación de horas extras.
 */
class Documentos
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
            if (isset($array['filter_modulo']) && $array['filter_modulo'] != '0') {
                $where .= " AND `fmd`.`id_modulo`  = {$array['filter_modulo']}";
            }
            if (isset($array['filter_doc']) && $array['filter_doc'] != '') {
                $where .= " AND `fmd`.`nombre` LIKE '%{$array['filter_doc']}%'";
            }
            if (isset($array['filter_version']) && $array['filter_version'] != '') {
                $where .= " AND `fmd`.`version_doc` LIKE '%{$array['filter_version']}%'";
            }
            if (isset($array['filter_fecha']) && $array['filter_fecha'] != '') {
                $where .= " AND `fmd`.`fecha_doc` = '{$array['filter_fecha']}'";
            }
            if (isset($array['filter_control']) && $array['filter_control'] != '') {
                $where .= " AND `fmd`.`control_doc` = '{$array['filter_control']}'";
            }
            if (isset($array['filter_estado']) && $array['filter_estado'] != '') {
                $where .= " AND `fmd`.`estado` = {$array['filter_estado']}";
            }
        }

        $sql = "SELECT
                    `fmd`.`id_maestro`
                    , `fmd`.`id_modulo`
                    , `sm`.`nom_modulo`
                    , `cf`.`nombre`
                    , `fmd`.`version_doc`
                    , DATE_FORMAT(`fmd`.`fecha_doc`, '%Y-%m-%d') AS `fecha_doc`
                    , `fmd`.`control_doc`
                    , `fmd`.`estado`
                FROM
                    `fin_maestro_doc` AS `fmd`
                    INNER JOIN `ctb_fuente` AS `cf` 
                        ON (`fmd`.`id_doc_fte` = `cf`.`id_doc_fuente`)
                    INNER JOIN `seg_modulos` AS `sm`
                        ON (`fmd`.`id_modulo` = `sm`.`id_modulo`)
                WHERE (1 = 1 $where)
                ORDER BY $col $dir $limit";
        $stmt = $this->conexion->prepare($sql);
        $stmt->execute();
        $datos = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $stmt->closeCursor();
        unset($stmt);
        return !empty($datos) ? $datos : [];
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
            if (isset($array['filter_modulo']) && $array['filter_modulo'] != '0') {
                $where .= " AND `fmd`.`id_modulo`  = {$array['filter_modulo']}";
            }
            if (isset($array['filter_doc']) && $array['filter_doc'] != '') {
                $where .= " AND `fmd`.`nombre` LIKE '%{$array['filter_doc']}%'";
            }
            if (isset($array['filter_version']) && $array['filter_version'] != '') {
                $where .= " AND `fmd`.`version_doc` LIKE '%{$array['filter_version']}%'";
            }
            if (isset($array['filter_fecha']) && $array['filter_fecha'] != '') {
                $where .= " AND `fmd`.`fecha_doc` = '{$array['filter_fecha']}'";
            }
            if (isset($array['filter_control']) && $array['filter_control'] != '') {
                $where .= " AND `fmd`.`control_doc` = '{$array['filter_control']}'";
            }
            if (isset($array['filter_estado']) && $array['filter_estado'] != '') {
                $where .= " AND `fmd`.`estado` = {$array['filter_estado']}";
            }
        }

        $sql = "SELECT
                    COUNT(*) AS `total`
                FROM
                    `fin_maestro_doc` AS `fmd`
                    INNER JOIN `ctb_fuente` AS `cf` 
                        ON (`fmd`.`id_doc_fte` = `cf`.`id_doc_fuente`)
                    INNER JOIN `seg_modulos` AS `sm`
                        ON (`fmd`.`id_modulo` = `sm`.`id_modulo`)
                WHERE (1 = 1 $where)";
        $stmt = $this->conexion->prepare($sql);
        $stmt->execute();
        $registro = $stmt->fetch(PDO::FETCH_ASSOC);
        $stmt->closeCursor();
        unset($stmt);
        return !empty($registro) ? $registro['total'] : 0;
    }

    /**
     * Obtiene el total de registros.
     * @return int Total de registros
     */

    public function getRegistrosTotal($array)
    {
        $sql = "SELECT
                    COUNT(*) AS `total`
                FROM
                    `fin_maestro_doc` AS `fmd`
                    INNER JOIN `ctb_fuente` AS `cf` 
                        ON (`fmd`.`id_doc_fte` = `cf`.`id_doc_fuente`)
                    INNER JOIN `seg_modulos` AS `sm`
                        ON (`fmd`.`id_modulo` = `sm`.`id_modulo`)
                WHERE (1 = 1)";
        $stmt = $this->conexion->prepare($sql);
        $stmt->execute();
        $registro = $stmt->fetch(PDO::FETCH_ASSOC);
        $stmt->closeCursor();
        unset($stmt);
        return !empty($registro) ? $registro['total'] : 0;
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
                    `id_modulo`,`id_doc_fte`,`version_doc`,DATE_FORMAT(`fecha_doc`, '%Y-%m-%d') AS `fecha_doc`,`estado`,`control_doc`,`acumula`,`costos`
                FROM `fin_maestro_doc`
                WHERE  `id_maestro`  = ?";
        $stmt = $this->conexion->prepare($sql);
        $stmt->bindParam(1, $id, PDO::PARAM_INT);
        $stmt->execute();
        $registro = $stmt->fetch(PDO::FETCH_ASSOC);
        $stmt->closeCursor();
        unset($stmt);
        if (empty($registro)) {
            $registro = [
                'id_maestro'   => 0,
                'id_modulo'    => 0,
                'id_doc_fte'   => 0,
                'version_doc'  => '',
                'fecha_doc'    => '',
                'estado'       => 1,
                'control_doc'  => '',
                'acumula'      => 0,
                'costos'       => 0
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
        $datos = $this->getRegistro($id);
        $modulos = Combos::getModulos($datos['id_modulo']);
        $dctoFunte = Combos::getDocumentoFuente($datos['id_doc_fte'], 0, 0);
        $ctrol1 = $datos['control_doc'] == 1 ? ' selected' : '';
        $ctrol0 = $datos['control_doc'] == 0 ? ' selected' : '';
        $html =
            <<<HTML
                <div class="shadow text-center rounded">
                    <div class="rounded-top py-2" style="background-color: #16a085 !important;">
                        <h5 style="color: white;" class="mb-0">GESTIÓN DOCUMENTAL</h5>
                    </div>
                    <div class="p-3">
                        <form id="formDctoFte">
                            <input type="hidden" id="id" name="id" value="{$id}">
                            <div class="row mb-2">
                                <div class="col-md-6">
                                    <label for="slcModulo" class="small text-muted">Módulo</label>
                                    <select id="slcModulo" name="slcModulo" class="form-select form-select-sm bg-input">
                                        {$modulos}
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label for="slcDocFte" class="small text-muted">Documento Fuente</label>
                                    <select id="slcDocFte" name="slcDocFte" class="form-select form-select-sm bg-input">
                                        {$dctoFunte}
                                    </select>
                                </div>
                            </div>
                            <div class="row mb-2">
                                <div class="col-md-4">
                                    <label for="txtVersion" class="small text-muted">Version</label>
                                    <input type="text" class="form-control form-control-sm bg-input text-end" id="txtVersion" name="txtVersion" value="{$datos['version_doc']}">
                                </div>
                                <div class="col-md-4">
                                    <label for="slcControl" class="small text-muted">Control</label>
                                    <select id="slcControl" name="slcControl" class="form-select form-select-sm bg-input">
                                        <option value="1" {$ctrol1}>SI</option>
                                        <option value="0" {$ctrol0}>NO</option>
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <label for="datFecha" class="small text-muted">Fecha Documento</label>
                                    <input type="date" class="form-control form-control-sm bg-input" id="datFecha" name="datFecha" value="{$datos['fecha_doc']}">
                                </div>
                            </div>
                            <div class="row hide d-none mb-2">
                                <div class="col-md-6">
                                    <label for="acum" class="small text-muted">Acumula por Rubros</label>
                                    <input type="number" title="Acumula por Rubros" name="acum" id="acum" class="form-control form-control-sm bg-input" value="{$datos['acumula']}">
                                </div>
                                <div class="col-md-6">
                                    <label for="costos" class="small text-muted">Visualiza costos</label>
                                    <input type="number" title="Visualiza costos" id="costos" name="costos" class=" form-control form-control-sm bg-input" value="{$datos['costos']}">
                                </div>
                            </div>
                        </form>
                    </div>
                    <div class="text-center pb-3">
                        <button type="button" class="btn btn-primary btn-sm" id="btnGuardaDctoFte">Guardar</button>
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
            $sql = "DELETE FROM `fin_maestro_doc` WHERE `id_maestro` = ?";
            $consulta  = "DELETE FROM `fin_maestro_doc` WHERE `id_maestro` = $id";
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
            $sql = "INSERT INTO `fin_maestro_doc`
                        (`id_modulo`,`id_doc_fte`,`version_doc`,`fecha_doc`,`control_doc`,`acumula`,`costos`,`id_user_reg`,`fecha_reg`)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $stmt = $this->conexion->prepare($sql);
            $stmt->bindValue(1, $array['slcModulo'], PDO::PARAM_INT);
            $stmt->bindValue(2, $array['slcDocFte'], PDO::PARAM_INT);
            $stmt->bindValue(3, $array['txtVersion'], PDO::PARAM_STR);
            $stmt->bindValue(4, $array['datFecha'], PDO::PARAM_STR);
            $stmt->bindValue(5, $array['slcControl'], PDO::PARAM_INT);
            $stmt->bindValue(6, $array['acum'], PDO::PARAM_INT);
            $stmt->bindValue(7, $array['costos'], PDO::PARAM_INT);
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

    public function setEstado($id, $estado)
    {
        try {
            $sql = "UPDATE `fin_maestro_doc` SET `estado` = ? WHERE `id_maestro` = ?";
            $stmt = $this->conexion->prepare($sql);
            $stmt->bindValue(1, $estado, PDO::PARAM_INT);
            $stmt->bindValue(2, $id, PDO::PARAM_INT);
            if ($stmt->execute() && $stmt->rowCount() > 0) {
                $consulta = "UPDATE `fin_maestro_doc` 
                                SET `id_user_act` = ?, `fecha_act` = ?
                             WHERE `id_maestro` = ?";
                $stmt2 = $this->conexion->prepare($consulta);
                $stmt2->bindValue(1, Sesion::IdUser(), PDO::PARAM_INT);
                $stmt2->bindValue(2, Sesion::Hoy(), PDO::PARAM_STR);
                $stmt2->bindValue(3, $id, PDO::PARAM_INT);
                $stmt2->execute();
                return 'si';
            } else {
                return 'No se actualizó el estado.';
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
            $sql = "UPDATE `fin_maestro_doc` 
                        SET `id_modulo` = ?, `id_doc_fte` = ?, `version_doc` = ?, `fecha_doc` = ?, `control_doc` = ?, `acumula` = ?, `costos` = ?
                    WHERE `id_maestro` = ?";
            $stmt = $this->conexion->prepare($sql);
            $stmt->bindValue(1, $array['slcModulo'], PDO::PARAM_INT);
            $stmt->bindValue(2, $array['slcDocFte'], PDO::PARAM_INT);
            $stmt->bindValue(3, $array['txtVersion'], PDO::PARAM_STR);
            $stmt->bindValue(4, $array['datFecha'], PDO::PARAM_STR);
            $stmt->bindValue(5, $array['slcControl'], PDO::PARAM_INT);
            $stmt->bindValue(6, $array['acum'], PDO::PARAM_INT);
            $stmt->bindValue(7, $array['costos'], PDO::PARAM_INT);
            $stmt->bindValue(8, $array['id'], PDO::PARAM_INT);

            if ($stmt->execute() && $stmt->rowCount() > 0) {
                $consulta = "UPDATE `fin_maestro_doc` 
                                SET `id_user_act` = ?, `fecha_act` = ?
                             WHERE `id_maestro` = ?";
                $stmt2 = $this->conexion->prepare($consulta);
                $stmt2->bindValue(1, Sesion::IdUser(), PDO::PARAM_INT);
                $stmt2->bindValue(2, Sesion::Hoy(), PDO::PARAM_STR);
                $stmt2->bindValue(3, $array['id'], PDO::PARAM_INT);
                $stmt2->execute();
                return 'si';
            } else {
                return 'No se actualizó el registro';
            }
        } catch (PDOException $e) {
            return 'Error SQL: ' . $e->getMessage();
        }
    }
}
