<?php

namespace Src\Contratos\Configuracion\Php\Clases;

use Config\Clases\Conexion;
use Config\Clases\Logs;
use Config\Clases\Sesion;
use PDO;
use PDOException;

/**
 * Clase Modalidades
 * Gestiona el catálogo de modalidades de contratación.
 * Tabla: ctt_modalidad (existente, se reutiliza)
 */
class Modalidades
{
    private $conexion;

    public function __construct()
    {
        $this->conexion = Conexion::getConexion();
    }

    /**
     * Obtiene la lista paginada de modalidades para DataTables.
     */
    public function getModalidades($start, $length, $val_busca, $col, $dir)
    {
        $where = '';
        if ($val_busca != '') {
            $val_busca = trim($val_busca);
            $where = "AND (`ctt_modalidad`.`modalidad` LIKE '%$val_busca%')";
        }
        $limit = '';
        if ($length != -1) {
            $limit = "LIMIT $start, $length";
        }
        $sql = "SELECT `id_modalidad`, `modalidad`
                FROM `ctt_modalidad`
                WHERE (`id_modalidad` > 0 $where)
                ORDER BY $col $dir $limit";
        $stmt = $this->conexion->prepare($sql);
        $stmt->execute();
        $datos = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        $stmt->closeCursor();
        unset($stmt);
        return $datos;
    }

    public function getRegistrosFilter($val_busca)
    {
        $where = '';
        if ($val_busca != '') {
            $val_busca = trim($val_busca);
            $where = "AND (`modalidad` LIKE '%$val_busca%')";
        }
        $sql = "SELECT COUNT(*) AS `total` FROM `ctt_modalidad` WHERE (`id_modalidad` > 0 $where)";
        $stmt = $this->conexion->prepare($sql);
        $stmt->execute();
        $data = $stmt->fetch(PDO::FETCH_ASSOC)['total'] ?: 0;
        $stmt->closeCursor();
        unset($stmt);
        return $data;
    }

    public function getRegistrosTotal()
    {
        $sql = "SELECT COUNT(*) AS `total` FROM `ctt_modalidad`";
        $stmt = $this->conexion->prepare($sql);
        $stmt->execute();
        $data = $stmt->fetch(PDO::FETCH_ASSOC)['total'] ?: 0;
        $stmt->closeCursor();
        unset($stmt);
        return $data;
    }

    public function getRegistro($id)
    {
        $sql = "SELECT `id_modalidad`, `modalidad` FROM `ctt_modalidad` WHERE `id_modalidad` = ?";
        $stmt = $this->conexion->prepare($sql);
        $stmt->bindParam(1, $id, PDO::PARAM_INT);
        $stmt->execute();
        $data = $stmt->fetch(PDO::FETCH_ASSOC);
        $stmt->closeCursor();
        unset($stmt);
        if (empty($data)) {
            $data = ['id_modalidad' => 0, 'modalidad' => ''];
        }
        return $data;
    }

    public function getFormulario($id)
    {
        $fila = $this->getRegistro($id);
        $html = <<<HTML
            <div>
                <div class="shadow text-center rounded">
                    <div class="rounded-top py-2" style="background-color: #16a085 !important;">
                        <h5 style="color: white;" class="mb-0">MODALIDAD DE CONTRATACIÓN</h5>
                    </div>
                    <div class="p-3">
                        <form id="formGestModalidad">
                            <input type="hidden" id="id" name="id" value="{$fila['id_modalidad']}">
                            <div class="row mb-2">
                                <div class="col-md-12">
                                    <label for="txtNombreModalidad" class="small">NOMBRE DE LA MODALIDAD</label>
                                    <input type="text" id="txtNombreModalidad" name="txtNombreModalidad"
                                        class="form-control form-control-sm bg-input text-uppercase"
                                        value="{$fila['modalidad']}" maxlength="150"
                                        placeholder="Ej: Licitación Pública, Contratación Directa...">
                                </div>
                            </div>
                        </form>
                    </div>
                    <div class="text-end pb-3 px-3">
                        <button type="button" class="btn btn-primary btn-sm" id="btnGuardaModalidad">Guardar</button>
                        <a type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancelar</a>
                    </div>
                </div>
            </div>
        HTML;
        return $html;
    }

    public function addModalidad($array)
    {
        try {
            // Verificar duplicado
            $sqlCheck = "SELECT `id_modalidad` FROM `ctt_modalidad` WHERE `modalidad` = ?";
            $stmtCheck = $this->conexion->prepare($sqlCheck);
            $nombre = mb_strtoupper(trim($array['txtNombreModalidad']));
            $stmtCheck->bindParam(1, $nombre, PDO::PARAM_STR);
            $stmtCheck->execute();
            if ($stmtCheck->fetch()) {
                return 'Ya existe una modalidad con ese nombre.';
            }
            $sql = "INSERT INTO `ctt_modalidad` (`modalidad`) VALUES (?)";
            $stmt = $this->conexion->prepare($sql);
            $stmt->bindValue(1, $nombre, PDO::PARAM_STR);
            $stmt->execute();
            $id = $this->conexion->lastInsertId();
            if ($id > 0) {
                Logs::guardaLog("INSERT INTO `ctt_modalidad` (`modalidad`) VALUES ('$nombre')");
                return 'si';
            }
            return 'No se insertó el registro';
        } catch (PDOException $e) {
            return 'Error SQL: ' . $e->getMessage();
        }
    }

    public function editModalidad($array)
    {
        try {
            $nombre = mb_strtoupper(trim($array['txtNombreModalidad']));
            $sql = "UPDATE `ctt_modalidad` SET `modalidad` = ? WHERE `id_modalidad` = ?";
            $stmt = $this->conexion->prepare($sql);
            $stmt->bindValue(1, $nombre, PDO::PARAM_STR);
            $stmt->bindValue(2, $array['id'], PDO::PARAM_INT);
            if (!$stmt->execute()) {
                return 'Error: ' . $stmt->errorInfo()[2];
            }
            if ($stmt->rowCount() > 0) {
                Logs::guardaLog("UPDATE `ctt_modalidad` SET `modalidad` = '$nombre' WHERE `id_modalidad` = {$array['id']}");
                return 'si';
            }
            return 'No se realizó ningún cambio.';
        } catch (PDOException $e) {
            return 'Error SQL: ' . $e->getMessage();
        }
    }

    public function delModalidad($id)
    {
        try {
            $sqlCheck = "SELECT COUNT(*) AS total FROM `ctt_procesos_new` WHERE `id_modalidad` = ?";
            $stmtCheck = $this->conexion->prepare($sqlCheck);
            $stmtCheck->bindParam(1, $id, PDO::PARAM_INT);
            $stmtCheck->execute();
            if ($stmtCheck->fetch(PDO::FETCH_ASSOC)['total'] > 0) {
                return 'No se puede eliminar: la modalidad está asociada a uno o más procesos.';
            }
            $sql      = "DELETE FROM `ctt_modalidad` WHERE `id_modalidad` = ?";
            $consulta = "DELETE FROM `ctt_modalidad` WHERE `id_modalidad` = $id";
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

    public static function getListado()
    {
        $instance = new self();
        $sql = "SELECT `id_modalidad`, `modalidad` FROM `ctt_modalidad` ORDER BY `modalidad` ASC";
        $stmt = $instance->conexion->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }
}
