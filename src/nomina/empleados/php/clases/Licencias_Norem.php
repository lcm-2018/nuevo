<?php

namespace Src\Nomina\Empleados\Php\Clases;

use Config\Clases\Conexion;
use Config\Clases\Logs;
use Config\Clases\Sesion;

use PDO;
use PDOException;

/**
 * Clase para gestionar las licencias de no remuneradas de los empleados.
 *
 * Esta clase permite realizar operaciones CRUD sobre las licencias de no remuneradas de los empleados,
 * incluyendo la obtención de registros, adición, edición y eliminación de licencias.
 */
class Licencias_Norem
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
                $where .= " AND (`fec_inicio` LIKE '%{$array['value']}%' 
                            OR `fec_fin` LIKE '%{$array['value']}%'
                            OR `dias_inactivo` LIKE '%{$array['value']}%'
                            OR `dias_habiles` LIKE '%{$array['value']}%')";
            }

            if (isset($array['id']) && $array['id'] > 0) {
                $where .= " AND `id_empleado` = {$array['id']}";
            }
        }

        $sql = "SELECT
                    `id_licnr`,`fec_inicio`,`fec_fin`,`dias_inactivo`,`dias_habiles`
                FROM `nom_licenciasnr`
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
                $where .= " AND (`fec_inicio` LIKE '%{$array['value']}%' 
                            OR `fec_fin` LIKE '%{$array['value']}%'
                            OR `dias_inactivo` LIKE '%{$array['value']}%'
                            OR `dias_habiles` LIKE '%{$array['value']}%')";
            }

            if (isset($array['id']) && $array['id'] > 0) {
                $where .= " AND `id_empleado` = {$array['id']}";
            }
        }

        $sql = "SELECT
                     COUNT(*) AS `total`
                FROM `nom_licenciasnr`
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
                    `nom_licenciasnr`
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
                    `id_licnr`,`fec_inicio`,`fec_fin`,`dias_inactivo`,`dias_habiles`
                FROM `nom_licenciasnr`
                WHERE `id_licnr` = ?";
        $stmt = $this->conexion->prepare($sql);
        $stmt->bindParam(1, $id, PDO::PARAM_INT);
        $stmt->execute();
        $registro = $stmt->fetch(PDO::FETCH_ASSOC);
        if (empty($registro)) {
            $registro = [
                'id_licnr' => 0,
                'fec_inicio' => Sesion::_Hoy(),
                'fec_fin' => '',
                'dias_inactivo' => 0,
                'dias_habiles' => 0,
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
                        <h5 style="color: white;" class="mb-0">GESTIÓN DE LICENCIA NO REMUNERADA</h5>
                    </div>
                    <div class="p-3">
                        <form id="formLicenciasNoRem">
                            <input type="hidden" id="id" name="id" value="{$id}">
                            <div class="row mb-2">
                                <div class="col-md-6">
                                    <label for="diasInactivo" class="small text-muted">Días inactivo</label>
                                    <input type="number" class="form-control form-control-sm text-end" id="diasInactivo" name="diasInactivo" value="{$registro['dias_inactivo']}" min="0" disabled readonly>
                                </div>
                                <div class="col-md-6">
                                    <label for="diasHabiles" class="small text-muted">Días hábiles</label>
                                    <input type="number" class="form-control form-control-sm bg-input text-end" id="diasHabiles" name="diasHabiles" value="{$registro['dias_habiles']}" min="0">
                                </div>
                            </div>
                            <div class="row mb-2">
                                <div class="col-md-6">
                                    <label for="datFecInicia" class="small text-muted">Inicia</label>
                                    <input type="date" class="form-control form-control-sm bg-input" id="datFecInicia" name="datFecInicia" value="{$registro['fec_inicio']}" onchange="DiasInactivo()">
                                </div>
                                <div class="col-md-6">
                                    <label for="datFecFin" class="small text-muted">Termina</label>
                                    <input type="date" class="form-control form-control-sm bg-input" id="datFecFin" name="datFecFin" value="{$registro['fec_fin']}" onchange="DiasInactivo()">
                                </div>
                            </div>
                        </form>
                    </div>
                    <div class="text-center pb-3">
                        <button type="button" class="btn btn-primary btn-sm" id="btnGuardarLicenciaNoRem">Guardar</button>
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
            $sql = "DELETE FROM `nom_licenciasnr` WHERE `id_licnr` = ?";
            $consulta  = "DELETE FROM `nom_licenciasnr` WHERE `id_licnr` = $id";
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
            $sql = "INSERT INTO `nom_licenciasnr`
                        (`id_empleado`,`fec_inicio`,`fec_fin`,`dias_inactivo`,`dias_habiles`,`fec_reg`,`id_user_reg`)
                    VALUES (?, ?, ?, ?, ?, ?, ?)";
            $stmt = $this->conexion->prepare($sql);
            $stmt->bindValue(1, $array['id_empleado'], PDO::PARAM_INT);
            $stmt->bindValue(2, $array['datFecInicia'], PDO::PARAM_STR);
            $stmt->bindValue(3, $array['datFecFin'], PDO::PARAM_STR);
            $stmt->bindValue(4, $array['diasInactivo'], PDO::PARAM_INT);
            $stmt->bindValue(5, $array['diasHabiles'], PDO::PARAM_INT);
            $stmt->bindValue(6, Sesion::Hoy(), PDO::PARAM_STR);
            $stmt->bindValue(7, Sesion::IdUser(), PDO::PARAM_INT);
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
            $sql = "UPDATE `nom_licenciasnr`
                        SET `fec_inicio` = ?, `fec_fin` = ?, `dias_inactivo` = ?, `dias_habiles` = ?
                    WHERE `id_licnr` = ?";
            $stmt = $this->conexion->prepare($sql);
            $stmt->bindValue(1, $array['datFecInicia'], PDO::PARAM_STR);
            $stmt->bindValue(2, $array['datFecFin'], PDO::PARAM_STR);
            $stmt->bindValue(3, $array['diasInactivo'], PDO::PARAM_INT);
            $stmt->bindValue(4, $array['diasHabiles'], PDO::PARAM_INT);
            $stmt->bindValue(5, $array['id'], PDO::PARAM_INT);

            if ($stmt->execute() && $stmt->rowCount() > 0) {
                $consulta = "UPDATE `nom_licenciasnr` SET `fec_act` = ? WHERE `id_licnr` = ?";
                $stmt2 = $this->conexion->prepare($consulta);
                $stmt2->bindValue(1, Sesion::Hoy(), PDO::PARAM_STR);
                $stmt2->bindValue(2, $array['id'], PDO::PARAM_INT);
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
        return 'Falta programar la anulación de registro de seguridad social.';
    }
}
