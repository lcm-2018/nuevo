<?php

namespace Src\Nomina\Empleados\Php\Clases;

use Config\Clases\Conexion;
use Config\Clases\Logs;

use PDO;
use PDOException;

/**
 * Clase para gestionar las novedades de viáticos de los empleados.
 */
class ViaticoNovedades
{
    private $conexion;

    private $tiposRegistro = [
        1 => 'Anticipo',
        2 => 'Aprobado',
        3 => 'Legalizado',
        4 => 'Rechazado',
        5 => 'Caducado'
    ];

    public function __construct($conexion = null)
    {
        $this->conexion = $conexion ?: Conexion::getConexion();
    }

    /**
     * Obtiene los datos para la DataTable.
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
                $where .= " AND (`nom_viaticos_novedades`.`observacion` LIKE '%{$array['value']}%')";
            }

            if (isset($array['id_viatico']) && $array['id_viatico'] > 0) {
                $where .= " AND `nom_viaticos_novedades`.`id_viatico` = {$array['id_viatico']}";
            }
        }

        $sql = "SELECT
                    `nom_viaticos_novedades`.`id_novedad`
                    , `nom_viaticos_novedades`.`id_viatico`
                    , DATE_FORMAT(`nom_viaticos_novedades`.`fecha`, '%Y-%m-%d') AS `fecha`
                    , `nom_viaticos_novedades`.`tipo_registro`
                    , `nom_viaticos_novedades`.`observacion`
                FROM
                    `nom_viaticos_novedades`
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
     */
    public function getRegistrosFilter($array)
    {
        $where = '';
        if (!empty($array)) {
            if (isset($array['value']) && $array['value'] != '') {
                $where .= " AND (`nom_viaticos_novedades`.`observacion` LIKE '%{$array['value']}%')";
            }

            if (isset($array['id_viatico']) && $array['id_viatico'] > 0) {
                $where .= " AND `nom_viaticos_novedades`.`id_viatico` = {$array['id_viatico']}";
            }
        }

        $sql = "SELECT COUNT(*) AS `total` FROM `nom_viaticos_novedades` WHERE (1 = 1 $where)";
        $stmt = $this->conexion->prepare($sql);
        $stmt->execute();
        $data = $stmt->fetch(PDO::FETCH_ASSOC)['total'] ?: 0;
        $stmt->closeCursor();
        unset($stmt);
        return $data;
    }

    /**
     * Obtiene el total de registros.
     */
    public function getRegistrosTotal($array)
    {
        $where = '';
        if (!empty($array)) {
            if (isset($array['id_viatico']) && $array['id_viatico'] > 0) {
                $where .= " AND `nom_viaticos_novedades`.`id_viatico` = {$array['id_viatico']}";
            }
        }

        $sql = "SELECT COUNT(*) AS `total` FROM `nom_viaticos_novedades` WHERE (1 = 1 $where)";
        $stmt = $this->conexion->prepare($sql);
        $stmt->execute();
        $data = $stmt->fetch(PDO::FETCH_ASSOC)['total'] ?: 0;
        $stmt->closeCursor();
        unset($stmt);
        return $data;
    }

    /**
     * Obtiene un registro por ID.
     */
    public function getRegistro($id)
    {
        $sql = "SELECT `id_novedad`, `id_viatico`, DATE_FORMAT(`fecha`, '%Y-%m-%d') AS `fecha`, `tipo_registro`, `observacion`
                FROM `nom_viaticos_novedades`
                WHERE `id_novedad` = ?";
        $stmt = $this->conexion->prepare($sql);
        $stmt->bindParam(1, $id, PDO::PARAM_INT);
        $stmt->execute();
        $registro = $stmt->fetch(PDO::FETCH_ASSOC);
        $stmt->closeCursor();
        unset($stmt);
        if (empty($registro)) {
            $registro = [
                'id_novedad'    => 0,
                'id_viatico'    => 0,
                'fecha'         => '',
                'tipo_registro' => '',
                'observacion'   => ''
            ];
        }
        return $registro;
    }

    /**
     * Obtiene el ID de la última novedad de un viático.
     */
    public function getUltimaNovedadId($id_viatico)
    {
        $sql = "SELECT MAX(`id_novedad`) AS `max_id` FROM `nom_viaticos_novedades` WHERE `id_viatico` = ?";
        $stmt = $this->conexion->prepare($sql);
        $stmt->bindParam(1, $id_viatico, PDO::PARAM_INT);
        $stmt->execute();
        $data = $stmt->fetch(PDO::FETCH_ASSOC)['max_id'] ?: 0;
        $stmt->closeCursor();
        unset($stmt);
        return $data;
    }

    /**
     * Genera las opciones del select para Tipo de Registro.
     */
    private function getComboTipoRegistro($selected = '')
    {
        $html = '<option value="">-- Seleccionar tipo de novedad --</option>';
        foreach ($this->tiposRegistro as $valor => $tipo) {
            $sel = ($selected == $valor) ? 'selected' : '';
            $html .= "<option value=\"{$valor}\" {$sel}>{$tipo}</option>";
        }
        return $html;
    }

    /**
     * Obtiene el formulario completo (form + tabla de novedades).
     *
     * @param int $id_viatico ID del viático
     * @return string HTML del formulario
     */
    public function getFormulario($id_viatico)
    {
        $comboTipoRegistro = $this->getComboTipoRegistro();
        $html =
            <<<HTML
                <div class="shadow text-center rounded">
                    <div class="rounded-top py-2" style="background-color: #16a085 !important;">
                        <h5 style="color: white;" class="mb-0">REGISTRO DE NOVEDADES DE VIÁTICOS</h5>
                    </div>
                    <div class="p-3">
                        <form id="formViaticoNovedad">
                            <input type="hidden" id="idNovedad" name="id" value="0">
                            <input type="hidden" id="idViaticoNov" name="id_viatico" value="{$id_viatico}">
                            <div class="row mb-2">
                                <div class="col-md-6 text-start">
                                    <label for="datFechaNov" class="small text-muted">FECHA</label>
                                    <input type="date" class="form-control form-control-sm bg-input" id="datFechaNov" name="datFechaNov" value="">
                                    <small class="text-muted">FORMATO: YYYY-MM-DD</small>
                                </div>
                                <div class="col-md-6 text-start">
                                    <label for="slcTipoRegistro" class="small text-muted">TIPO DE REGISTRO</label>
                                    <select class="form-select form-select-sm bg-input" id="slcTipoRegistro" name="slcTipoRegistro">
                                        {$comboTipoRegistro}
                                    </select>
                                </div>
                            </div>
                            <div class="row mb-2">
                                <div class="col-md-12 text-start">
                                    <label for="txtObservacion" class="small text-muted">OBSERVACIÓN</label>
                                    <textarea class="form-control form-control-sm bg-input" id="txtObservacion" name="txtObservacion" rows="3" placeholder="Ingrese el detalle de la novedad..."></textarea>
                                </div>
                            </div>
                        </form>
                    </div>
                    <div class="text-center pb-3">
                        <button type="button" class="btn btn-success btn-sm" id="btnGuardarNovedad">
                            <i class="fas fa-save me-1"></i>Guardar Novedad
                        </button>
                        <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">
                            <i class="fas fa-times me-1"></i>Cancelar
                        </button>
                    </div>
                    <hr>
                    <div class="p-3">
                        <table id="tableViaticoNovedades" class="table table-striped table-bordered table-sm table-hover align-middle shadow" style="width:100%">
                            <thead>
                                <tr>
                                    <th class="text-center bg-sofia">ID</th>
                                    <th class="text-center bg-sofia">FECHA</th>
                                    <th class="text-center bg-sofia">TIPO DE REGISTRO</th>
                                    <th class="text-center bg-sofia">OBSERVACIÓN</th>
                                    <th class="text-center bg-sofia">ACCIONES</th>
                                </tr>
                            </thead>
                            <tbody></tbody>
                        </table>
                    </div>
                </div>
            HTML;
        return $html;
    }

    /**
     * Verifica si ya existe una novedad con el mismo tipo_registro para un viático.
     *
     * @param int $id_viatico ID del viático
     * @param int $tipo_registro Tipo de registro a verificar
     * @param int $excluir_id ID de novedad a excluir (para edición)
     * @return bool true si ya existe
     */
    private function existeTipoRegistro($id_viatico, $tipo_registro, $excluir_id = 0)
    {
        $sql = "SELECT COUNT(*) AS `total` FROM `nom_viaticos_novedades`
                WHERE `id_viatico` = ? AND `tipo_registro` = ? AND `id_novedad` != ?";
        $stmt = $this->conexion->prepare($sql);
        $stmt->bindValue(1, $id_viatico, PDO::PARAM_INT);
        $stmt->bindValue(2, $tipo_registro, PDO::PARAM_INT);
        $stmt->bindValue(3, $excluir_id, PDO::PARAM_INT);
        $stmt->execute();
        $total = $stmt->fetch(PDO::FETCH_ASSOC)['total'] ?: 0;
        $stmt->closeCursor();
        unset($stmt);
        return $total > 0;
    }

    /**
     * Elimina un registro.
     */
    public function delRegistro($id, $id_viatico)
    {
        try {
            $sql = "DELETE FROM `nom_viaticos_novedades` WHERE `id_novedad` = ?";
            $consulta = "DELETE FROM `nom_viaticos_novedades` WHERE `id_novedad` = $id";
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
     */
    public function addRegistro($array)
    {
        // Validar que no exista el mismo tipo de registro
        if ($this->existeTipoRegistro($array['id_viatico'], $array['slcTipoRegistro'])) {
            return 'Ya existe una novedad con este tipo de registro para este viático.';
        }

        try {
            $sql = "INSERT INTO `nom_viaticos_novedades`
                        (`id_viatico`, `fecha`, `tipo_registro`, `observacion`)
                    VALUES (?, ?, ?, ?)";
            $stmt = $this->conexion->prepare($sql);
            $stmt->bindValue(1, $array['id_viatico'], PDO::PARAM_INT);
            $stmt->bindValue(2, $array['datFechaNov'], PDO::PARAM_STR);
            $stmt->bindValue(3, $array['slcTipoRegistro'], PDO::PARAM_INT);
            $stmt->bindValue(4, $array['txtObservacion'], PDO::PARAM_STR);
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
     */
    public function editRegistro($array)
    {
        // Validar que no exista el mismo tipo de registro (excluyendo el registro actual)
        $reg = $this->getRegistro($array['id']);
        if ($this->existeTipoRegistro($reg['id_viatico'], $array['slcTipoRegistro'], $array['id'])) {
            return 'Ya existe una novedad con este tipo de registro para este viático.';
        }

        try {
            $sql = "UPDATE `nom_viaticos_novedades`
                        SET `fecha` = ?,
                            `tipo_registro` = ?,
                            `observacion` = ?
                    WHERE `id_novedad` = ?";
            $stmt = $this->conexion->prepare($sql);
            $stmt->bindValue(1, $array['datFechaNov'], PDO::PARAM_STR);
            $stmt->bindValue(2, $array['slcTipoRegistro'], PDO::PARAM_INT);
            $stmt->bindValue(3, $array['txtObservacion'], PDO::PARAM_STR);
            $stmt->bindValue(4, $array['id'], PDO::PARAM_INT);

            if ($stmt->execute() && $stmt->rowCount() > 0) {
                return 'si';
            } else {
                return 'No se actualizó el registro.';
            }
        } catch (PDOException $e) {
            return 'Error SQL: ' . $e->getMessage();
        }
    }
}
