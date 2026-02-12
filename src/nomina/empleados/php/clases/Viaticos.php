<?php

namespace Src\Nomina\Empleados\Php\Clases;

use Config\Clases\Conexion;
use Config\Clases\Logs;

use PDO;
use PDOException;

/**
 * Clase para gestionar el registro y control de viáticos de los empleados.
 *
 * Esta clase permite realizar operaciones CRUD sobre viáticos de los empleados,
 * incluyendo la obtención de registros, adición, edición y eliminación de viáticos.
 */
class Viaticos
{
    private $conexion;

    public function __construct($conexion = null)
    {
        $this->conexion = $conexion ?: Conexion::getConexion();
    }

    /**
     * Obtiene los datos para la DataTable.
     *
     * @param int $start Índice de inicio para la paginación
     * @param int $length Número de registros a mostrar
     * @param array $array filtros de búsqueda
     * @param int $col Índice de la columna para ordenar
     * @param string $dir Dirección de ordenamiento
     * @return array Retorna un array con los datos
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
                $where .= " AND (`nom_viaticos`.`no_resolucion` LIKE '%{$array['value']}%'
                            OR `nom_viaticos`.`destino` LIKE '%{$array['value']}%'
                            OR `nom_viaticos`.`objetivo` LIKE '%{$array['value']}%')";
            }

            if (isset($array['id']) && $array['id'] > 0) {
                $where .= " AND `nom_viaticos`.`id_empleado` = {$array['id']}";
            }
        }

        $sql = "SELECT
                    `nom_viaticos`.`id_viatico`
                    , `nom_viaticos`.`no_resolucion`
                    , `nom_viaticos`.`tipo`
                    , `nom_viaticos`.`destino`
                    , `nom_viaticos`.`objetivo`
                    , `nom_viaticos`.`val_total`
                    , `ult_nov`.`tipo_registro` AS `estado`
                    , DATE_FORMAT(`nom_viaticos`.`fec_inicia`, '%Y-%m-%d') AS `fec_inicia`
                    , `nom_viaticos`.`id_empleado`
                FROM
                    `nom_viaticos`
                LEFT JOIN (
                    SELECT `n1`.`id_viatico`, `n1`.`tipo_registro`
                    FROM `nom_viaticos_novedades` `n1`
                    WHERE `n1`.`id_novedad` = (
                        SELECT MAX(`n2`.`id_novedad`)
                        FROM `nom_viaticos_novedades` `n2`
                        WHERE `n2`.`id_viatico` = `n1`.`id_viatico`
                    )
                ) `ult_nov` ON `ult_nov`.`id_viatico` = `nom_viaticos`.`id_viatico`
                WHERE (1 = 1 $where)
                ORDER BY $col $dir $limit";
        $stmt = $this->conexion->prepare($sql);
        $stmt->execute();
        $datos = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $stmt->closeCursor();
        unset($stmt);
        return $datos ?: [];
    }

    /**
     * Obtiene el total de registros filtrados.
     *
     * @param array $array Filtros de búsqueda
     * @return int Total de registros filtrados
     */
    public function getRegistrosFilter($array)
    {
        $where = '';
        if (!empty($array)) {
            if (isset($array['value']) && $array['value'] != '') {
                $where .= " AND (`nom_viaticos`.`no_resolucion` LIKE '%{$array['value']}%'
                            OR `nom_viaticos`.`destino` LIKE '%{$array['value']}%'
                            OR `nom_viaticos`.`objetivo` LIKE '%{$array['value']}%')";
            }

            if (isset($array['id']) && $array['id'] > 0) {
                $where .= " AND `nom_viaticos`.`id_empleado` = {$array['id']}";
            }
        }

        $sql = "SELECT
                    COUNT(*) AS `total`
                FROM
                    `nom_viaticos`
                WHERE (1 = 1 $where)";
        $stmt = $this->conexion->prepare($sql);
        $stmt->execute();
        $data = $stmt->fetch(PDO::FETCH_ASSOC)['total'] ?: 0;
        $stmt->closeCursor();
        unset($stmt);
        return $data;
    }

    /**
     * Obtiene el total de registros.
     *
     * @param array $array Filtros
     * @return int Total de registros
     */
    public function getRegistrosTotal($array)
    {
        $where = '';
        if (!empty($array)) {
            if (isset($array['id']) && $array['id'] > 0) {
                $where .= " AND `nom_viaticos`.`id_empleado` = {$array['id']}";
            }
        }

        $sql = "SELECT
                    COUNT(*) AS `total`
                FROM
                    `nom_viaticos`
                WHERE (1 = 1 $where)";
        $stmt = $this->conexion->prepare($sql);
        $stmt->execute();
        $data = $stmt->fetch(PDO::FETCH_ASSOC)['total'] ?: 0;
        $stmt->closeCursor();
        unset($stmt);
        return $data;
    }

    /**
     * Obtiene un registro por ID.
     *
     * @param int $id ID del registro
     * @return array datos del registro
     */
    public function getRegistro($id)
    {
        $sql = "SELECT
                    `id_viatico`, `id_empleado`, `no_resolucion`, `tipo`, `destino`, `objetivo`, `val_total`,
                    DATE_FORMAT(`fec_inicia`, '%Y-%m-%d') AS `fec_inicia`
                FROM `nom_viaticos`
                WHERE `id_viatico` = ?";
        $stmt = $this->conexion->prepare($sql);
        $stmt->bindParam(1, $id, PDO::PARAM_INT);
        $stmt->execute();
        $registro = $stmt->fetch(PDO::FETCH_ASSOC);
        $stmt->closeCursor();
        unset($stmt);
        if (empty($registro)) {
            $registro = [
                'id_viatico'    => 0,
                'no_resolucion' => '',
                'tipo'          => '',
                'destino'       => '',
                'objetivo'      => '',
                'val_total'     => 0,
                'estado'        => '',
                'fec_inicia'    => ''
            ];
        }
        return $registro;
    }

    /**
     * Genera las opciones del select para el campo Tipo.
     *
     * @param string $selected Valor seleccionado
     * @return string HTML con las opciones
     */
    private function getComboTipo($selected = '')
    {
        $tipos = [1 => 'Anticipo', 2 => 'Legalización'];
        $html = '<option value="">-- Seleccionar --</option>';
        foreach ($tipos as $valor => $tipo) {
            $sel = ($selected == $valor) ? 'selected' : '';
            $html .= "<option value=\"{$valor}\" {$sel}>{$tipo}</option>";
        }
        return $html;
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
        $comboTipo = $this->getComboTipo($registro['tipo']);
        $valTotal = number_format($registro['val_total'], 0, '', '');
        $html =
            <<<HTML
                <div class="shadow text-center rounded">
                    <div class="rounded-top py-2" style="background-color: #16a085 !important;">
                        <h5 style="color: white;" class="mb-0">REGISTRO DE VIÁTICOS</h5>
                    </div>
                    <div class="p-3">
                        <form id="formViaticos">
                            <input type="hidden" id="id" name="id" value="{$id}">
                            <div class="row mb-2">
                                <div class="col-md-4">
                                    <label for="datFecInicia" class="small text-muted">Fecha</label>
                                    <input type="date" class="form-control form-control-sm bg-input" id="datFecInicia" name="datFecInicia" value="{$registro['fec_inicia']}">
                                </div>
                                <div class="col-md-4">
                                    <label for="txtNoResolucion" class="small text-muted">Nº Resolución</label>
                                    <input type="text" class="form-control form-control-sm bg-input" id="txtNoResolucion" name="txtNoResolucion" value="{$registro['no_resolucion']}" placeholder="0">
                                </div>
                                <div class="col-md-4">
                                    <label for="slcTipo" class="small text-muted">Tipo</label>
                                    <select class="form-select form-select-sm bg-input" id="slcTipo" name="slcTipo">
                                        {$comboTipo}
                                    </select>
                                </div>
                            </div>
                            <div class="row mb-2">
                                <div class="col-md-8">
                                    <label for="txtDestino" class="small text-muted">Destino</label>
                                    <input type="text" class="form-control form-control-sm bg-input" id="txtDestino" name="txtDestino" value="{$registro['destino']}" placeholder="Ciudad o lugar de destino">
                                </div>
                                <div class="col-md-4">
                                    <label for="numValTotal" class="small text-muted">Monto</label>
                                    <div class="input-group input-group-sm">
                                        <span class="input-group-text">$</span>
                                        <input type="number" class="form-control form-control-sm bg-input" id="numValTotal" name="numValTotal" value="{$valTotal}" placeholder="0" min="0">
                                    </div>
                                </div>
                            </div>
                            <div class="row mb-2">
                                <div class="col-md-12">
                                    <label for="txtObjetivo" class="small text-muted">Motivo</label>
                                    <textarea class="form-control form-control-sm bg-input" id="txtObjetivo" name="txtObjetivo" rows="3" placeholder="Descripción detallada del viaje...">{$registro['objetivo']}</textarea>
                                </div>
                            </div>
                        </form>
                    </div>
                    <div class="text-center pb-3">
                        <button type="button" class="btn btn-primary btn-sm" id="btnGuardarViatico">Guardar</button>
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
            $sql = "DELETE FROM `nom_viaticos` WHERE `id_viatico` = ?";
            $consulta  = "DELETE FROM `nom_viaticos` WHERE `id_viatico` = $id";
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
            $sql = "INSERT INTO `nom_viaticos`
                        (`id_empleado`, `no_resolucion`, `fec_inicia`, `val_total`, `objetivo`, `destino`, `tipo`)
                    VALUES (?, ?, ?, ?, ?, ?, ?)";
            $stmt = $this->conexion->prepare($sql);
            $stmt->bindValue(1, $array['id_empleado'], PDO::PARAM_INT);
            $stmt->bindValue(2, $array['txtNoResolucion'], PDO::PARAM_STR);
            $stmt->bindValue(3, $array['datFecInicia'], PDO::PARAM_STR);
            $stmt->bindValue(4, $array['numValTotal'], PDO::PARAM_STR);
            $stmt->bindValue(5, $array['txtObjetivo'], PDO::PARAM_STR);
            $stmt->bindValue(6, $array['txtDestino'], PDO::PARAM_STR);
            $stmt->bindValue(7, $array['slcTipo'], PDO::PARAM_STR);
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
            $sql = "UPDATE `nom_viaticos`
                        SET `no_resolucion` = ?,
                            `fec_inicia` = ?,
                            `val_total` = ?,
                            `objetivo` = ?,
                            `destino` = ?,
                            `tipo` = ?
                    WHERE `id_viatico` = ?";
            $stmt = $this->conexion->prepare($sql);
            $stmt->bindValue(1, $array['txtNoResolucion'], PDO::PARAM_STR);
            $stmt->bindValue(2, $array['datFecInicia'], PDO::PARAM_STR);
            $stmt->bindValue(3, $array['numValTotal'], PDO::PARAM_STR);
            $stmt->bindValue(4, $array['txtObjetivo'], PDO::PARAM_STR);
            $stmt->bindValue(5, $array['txtDestino'], PDO::PARAM_STR);
            $stmt->bindValue(6, $array['slcTipo'], PDO::PARAM_STR);
            $stmt->bindValue(7, $array['id'], PDO::PARAM_INT);

            if ($stmt->execute() && $stmt->rowCount() > 0) {
                return 'si';
            } else {
                return 'No se actualizó el registro.';
            }
        } catch (PDOException $e) {
            return 'Error SQL: ' . $e->getMessage();
        }
    }
    /**
     * Obtiene los viáticos tipo 2 (legalización) que estén dentro del rango de fechas.
     *
     * @param string $fec_inicio Fecha inicio del mes
     * @param string $fec_fin Fecha fin del mes
     * @return array Lista de viáticos
     */
    public function getViaticosNomina($fec_inicio, $fec_fin)
    {
        $sql = "SELECT
                    `id_viatico`, `id_empleado`, `val_total`
                FROM `nom_viaticos`
                WHERE `tipo` = 2 
                AND `fec_inicia` BETWEEN ? AND ?";
        $stmt = $this->conexion->prepare($sql);
        $stmt->bindParam(1, $fec_inicio, PDO::PARAM_STR);
        $stmt->bindParam(2, $fec_fin, PDO::PARAM_STR);
        $stmt->execute();
        $datos = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $stmt->closeCursor();
        unset($stmt);
        return $datos ?: [];
    }
}
