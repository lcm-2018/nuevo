<?php

namespace Src\Contratos\Configuracion\Php\Clases;

use Config\Clases\Conexion;
use Config\Clases\Logs;
use Config\Clases\Sesion;
use PDO;
use PDOException;

/**
 * Clase TiposProceso
 * Gestiona el catálogo de tipos de proceso de contratación.
 * Tabla: ctt_tipo_proceso
 */
class TiposProceso
{
    private $conexion;

    public function __construct()
    {
        $this->conexion = Conexion::getConexion();
    }

    /**
     * Obtiene la lista paginada de tipos de proceso para DataTables.
     */
    public function getTipos($start, $length, $val_busca, $col, $dir)
    {
        $where = '';
        if ($val_busca != '') {
            $val_busca = trim($val_busca);
            $where = "AND (`ctt_tipo_proceso`.`nombre` LIKE '%$val_busca%' OR `ctt_tipo_proceso`.`descripcion` LIKE '%$val_busca%')";
        }
        $limit = '';
        if ($length != -1) {
            $limit = "LIMIT $start, $length";
        }
        $sql = "SELECT
                    `id_tipo_proceso`,
                    `nombre`,
                    `descripcion`,
                    `activo`,
                    `fec_reg`
                FROM `ctt_tipo_proceso`
                WHERE (`id_tipo_proceso` > 0 $where)
                ORDER BY $col $dir $limit";
        $stmt = $this->conexion->prepare($sql);
        $stmt->execute();
        $datos = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        $stmt->closeCursor();
        unset($stmt);
        return $datos;
    }

    /**
     * Total de registros filtrados para DataTables.
     */
    public function getRegistrosFilter($val_busca)
    {
        $where = '';
        if ($val_busca != '') {
            $val_busca = trim($val_busca);
            $where = "AND (`nombre` LIKE '%$val_busca%' OR `descripcion` LIKE '%$val_busca%')";
        }
        $sql = "SELECT COUNT(*) AS `total` FROM `ctt_tipo_proceso` WHERE (`id_tipo_proceso` > 0 $where)";
        $stmt = $this->conexion->prepare($sql);
        $stmt->execute();
        $data = $stmt->fetch(PDO::FETCH_ASSOC)['total'] ?: 0;
        $stmt->closeCursor();
        unset($stmt);
        return $data;
    }

    /**
     * Total general de registros para DataTables.
     */
    public function getRegistrosTotal()
    {
        $sql = "SELECT COUNT(*) AS `total` FROM `ctt_tipo_proceso`";
        $stmt = $this->conexion->prepare($sql);
        $stmt->execute();
        $data = $stmt->fetch(PDO::FETCH_ASSOC)['total'] ?: 0;
        $stmt->closeCursor();
        unset($stmt);
        return $data;
    }

    /**
     * Obtiene un registro por ID. Retorna array vacío si es nuevo (id=0).
     */
    public function getRegistro($id)
    {
        $sql = "SELECT `id_tipo_proceso`, `nombre`, `descripcion`, `activo`
                FROM `ctt_tipo_proceso` WHERE `id_tipo_proceso` = ?";
        $stmt = $this->conexion->prepare($sql);
        $stmt->bindParam(1, $id, PDO::PARAM_INT);
        $stmt->execute();
        $data = $stmt->fetch(PDO::FETCH_ASSOC);
        $stmt->closeCursor();
        unset($stmt);
        if (empty($data)) {
            $data = [
                'id_tipo_proceso' => 0,
                'nombre'          => '',
                'descripcion'     => '',
                'activo'          => 1,
            ];
        }
        return $data;
    }

    /**
     * Genera el HTML del formulario para el modal.
     */
    public function getFormulario($id)
    {
        $fila  = $this->getRegistro($id);
        $chkActivo   = $fila['activo'] == 1 ? 'checked' : '';
        $html = <<<HTML
            <div>
                <div class="shadow text-center rounded">
                    <div class="rounded-top py-2" style="background-color: #16a085 !important;">
                        <h5 style="color: white;" class="mb-0">TIPO DE PROCESO</h5>
                    </div>
                    <div class="p-3">
                        <form id="formGestTipoProceso">
                            <input type="hidden" id="id" name="id" value="{$fila['id_tipo_proceso']}">
                            <div class="row mb-2">
                                <div class="col-md-12">
                                    <label for="txtNombreTipo" class="small">NOMBRE</label>
                                    <input type="text" id="txtNombreTipo" name="txtNombreTipo"
                                        class="form-control form-control-sm bg-input text-uppercase"
                                        value="{$fila['nombre']}" maxlength="150">
                                </div>
                            </div>
                            <div class="row mb-2">
                                <div class="col-md-12">
                                    <label for="txtDescTipo" class="small">DESCRIPCIÓN</label>
                                    <textarea id="txtDescTipo" name="txtDescTipo" rows="2"
                                        class="form-control form-control-sm bg-input">{$fila['descripcion']}</textarea>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-12 text-start">
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" id="chkActivo"
                                            name="chkActivo" value="1" {$chkActivo}>
                                        <label class="form-check-label small" for="chkActivo">Activo</label>
                                    </div>
                                </div>
                            </div>
                        </form>
                    </div>
                    <div class="text-end pb-3 px-3">
                        <button type="button" class="btn btn-primary btn-sm" id="btnGuardaTipoProceso">Guardar</button>
                        <a type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancelar</a>
                    </div>
                </div>
            </div>
        HTML;
        return $html;
    }

    /**
     * Inserta un nuevo tipo de proceso.
     */
    public function addTipo($array)
    {
        try {
            $sql = "INSERT INTO `ctt_tipo_proceso`
                        (`nombre`, `descripcion`, `activo`, `id_user_reg`, `fec_reg`)
                    VALUES (?, ?, ?, ?, ?)";
            $stmt = $this->conexion->prepare($sql);
            $activo = isset($array['chkActivo']) ? 1 : 0;
            $stmt->bindValue(1, mb_strtoupper(trim($array['txtNombreTipo'])), PDO::PARAM_STR);
            $stmt->bindValue(2, $array['txtDescTipo'] ?? '', PDO::PARAM_STR);
            $stmt->bindValue(3, $activo, PDO::PARAM_INT);
            $stmt->bindValue(4, Sesion::IdUser(), PDO::PARAM_INT);
            $stmt->bindValue(5, Sesion::Hoy(), PDO::PARAM_STR);
            $stmt->execute();
            $id = $this->conexion->lastInsertId();
            if ($id > 0) {
                Logs::guardaLog("INSERT INTO `ctt_tipo_proceso` (`nombre`, `descripcion`, `activo`, `id_user_reg`, `fec_reg`) VALUES ('{$array['txtNombreTipo']}', '{$array['txtDescTipo']}', $activo, " . Sesion::IdUser() . ", '" . Sesion::Hoy() . "')");
                return 'si';
            }
            return 'No se insertó el registro';
        } catch (PDOException $e) {
            return 'Error SQL: ' . $e->getMessage();
        }
    }

    /**
     * Edita un tipo de proceso existente.
     */
    public function editTipo($array)
    {
        try {
            $activo = isset($array['chkActivo']) ? 1 : 0;
            $sql = "UPDATE `ctt_tipo_proceso`
                        SET `nombre` = ?, `descripcion` = ?, `activo` = ?,
                            `id_user_act` = ?, `fec_act` = ?
                    WHERE `id_tipo_proceso` = ?";
            $stmt = $this->conexion->prepare($sql);
            $stmt->bindValue(1, mb_strtoupper(trim($array['txtNombreTipo'])), PDO::PARAM_STR);
            $stmt->bindValue(2, $array['txtDescTipo'] ?? '', PDO::PARAM_STR);
            $stmt->bindValue(3, $activo, PDO::PARAM_INT);
            $stmt->bindValue(4, Sesion::IdUser(), PDO::PARAM_INT);
            $stmt->bindValue(5, Sesion::Hoy(), PDO::PARAM_STR);
            $stmt->bindValue(6, $array['id'], PDO::PARAM_INT);
            if (!$stmt->execute()) {
                return 'Error: ' . $stmt->errorInfo()[2];
            }
            if ($stmt->rowCount() > 0) {
                Logs::guardaLog("UPDATE `ctt_tipo_proceso` SET `nombre` = '{$array['txtNombreTipo']}', `activo` = $activo WHERE `id_tipo_proceso` = {$array['id']}");
                return 'si';
            }
            return 'No se realizó ningún cambio.';
        } catch (PDOException $e) {
            return 'Error SQL: ' . $e->getMessage();
        }
    }

    /**
     * Elimina un tipo de proceso (solo si no está en uso).
     */
    public function delTipo($id)
    {
        try {
            // Verificar que no esté en uso
            $sqlCheck = "SELECT COUNT(*) AS total FROM `ctt_procesos_new` WHERE `id_tipo_proceso` = ?";
            $stmtCheck = $this->conexion->prepare($sqlCheck);
            $stmtCheck->bindParam(1, $id, PDO::PARAM_INT);
            $stmtCheck->execute();
            $enUso = $stmtCheck->fetch(PDO::FETCH_ASSOC)['total'];
            $stmtCheck->closeCursor();
            if ($enUso > 0) {
                return 'No se puede eliminar: el tipo está asociado a uno o más procesos.';
            }
            $sql     = "DELETE FROM `ctt_tipo_proceso` WHERE `id_tipo_proceso` = ?";
            $consulta = "DELETE FROM `ctt_tipo_proceso` WHERE `id_tipo_proceso` = $id";
            $stmt = $this->conexion->prepare($sql);
            $stmt->bindParam(1, $id, PDO::PARAM_INT);
            $stmt->execute();
            if ($stmt->rowCount() > 0) {
                Logs::guardaLog($consulta);
                return 'si';
            }
            return 'No se eliminó el registro.';
        } catch (PDOException $e) {
            return 'Error SQL: ' . $e->getMessage();
        }
    }

    /**
     * Retorna lista simple para combos/selects.
     */
    public static function getListado()
    {
        $instance = new self();
        $sql = "SELECT `id_tipo_proceso`, `nombre` FROM `ctt_tipo_proceso` WHERE `activo` = 1 ORDER BY `nombre` ASC";
        $stmt = $instance->conexion->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }
}
