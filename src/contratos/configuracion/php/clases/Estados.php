<?php

namespace Src\Contratos\Configuracion\Php\Clases;

use Config\Clases\Conexion;
use Config\Clases\Logs;
use Config\Clases\Sesion;
use PDO;
use PDOException;

/**
 * Clase Estados
 * Gestiona el catálogo de estados del ciclo de vida del contrato.
 * Tabla: ctt_estado_proceso
 */
class Estados
{
    private $conexion;

    public function __construct()
    {
        $this->conexion = Conexion::getConexion();
    }

    public function getEstados($start, $length, $val_busca, $col, $dir)
    {
        $where = '';
        if ($val_busca != '') {
            $val_busca = trim($val_busca);
            $where = "AND (`nombre` LIKE '%$val_busca%')";
        }
        $limit = '';
        if ($length != -1) {
            $limit = "LIMIT $start, $length";
        }
        $sql = "SELECT `id_estado`, `nombre`, `color_badge`, `permite_edicion`, `orden`
                FROM `ctt_estado_proceso`
                WHERE (`id_estado` > 0 $where)
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
            $where = "AND (`nombre` LIKE '%$val_busca%')";
        }
        $sql = "SELECT COUNT(*) AS `total` FROM `ctt_estado_proceso` WHERE (`id_estado` > 0 $where)";
        $stmt = $this->conexion->prepare($sql);
        $stmt->execute();
        $data = $stmt->fetch(PDO::FETCH_ASSOC)['total'] ?: 0;
        $stmt->closeCursor();
        unset($stmt);
        return $data;
    }

    public function getRegistrosTotal()
    {
        $sql = "SELECT COUNT(*) AS `total` FROM `ctt_estado_proceso`";
        $stmt = $this->conexion->prepare($sql);
        $stmt->execute();
        $data = $stmt->fetch(PDO::FETCH_ASSOC)['total'] ?: 0;
        $stmt->closeCursor();
        unset($stmt);
        return $data;
    }

    public function getRegistro($id)
    {
        $sql = "SELECT `id_estado`, `nombre`, `color_badge`, `permite_edicion`, `orden`
                FROM `ctt_estado_proceso` WHERE `id_estado` = ?";
        $stmt = $this->conexion->prepare($sql);
        $stmt->bindParam(1, $id, PDO::PARAM_INT);
        $stmt->execute();
        $data = $stmt->fetch(PDO::FETCH_ASSOC);
        $stmt->closeCursor();
        unset($stmt);
        if (empty($data)) {
            $data = [
                'id_estado'       => 0,
                'nombre'          => '',
                'color_badge'     => 'secondary',
                'permite_edicion' => 1,
                'orden'           => 1,
            ];
        }
        return $data;
    }

    public function getFormulario($id)
    {
        $fila   = $this->getRegistro($id);
        $chkEdicion = $fila['permite_edicion'] == 1 ? 'checked' : '';

        // Opciones de color badge
        $colores = ['secondary', 'primary', 'success', 'warning', 'danger', 'info', 'dark'];
        $optColores = '';
        foreach ($colores as $c) {
            $sel = $fila['color_badge'] == $c ? 'selected' : '';
            $optColores .= "<option value=\"$c\" $sel>" . strtoupper($c) . "</option>";
        }

        $html = <<<HTML
            <div>
                <div class="shadow text-center rounded">
                    <div class="rounded-top py-2" style="background-color: #16a085 !important;">
                        <h5 style="color: white;" class="mb-0">ESTADO DEL CONTRATO</h5>
                    </div>
                    <div class="p-3">
                        <form id="formGestEstado">
                            <input type="hidden" id="id" name="id" value="{$fila['id_estado']}">
                            <div class="row mb-2">
                                <div class="col-md-8">
                                    <label for="txtNombreEstado" class="small">NOMBRE DEL ESTADO</label>
                                    <input type="text" id="txtNombreEstado" name="txtNombreEstado"
                                        class="form-control form-control-sm bg-input text-uppercase"
                                        value="{$fila['nombre']}" maxlength="80">
                                </div>
                                <div class="col-md-4">
                                    <label for="numOrden" class="small">ORDEN</label>
                                    <input type="number" id="numOrden" name="numOrden" min="1"
                                        class="form-control form-control-sm bg-input"
                                        value="{$fila['orden']}">
                                </div>
                            </div>
                            <div class="row mb-2">
                                <div class="col-md-7">
                                    <label for="slcColorBadge" class="small">COLOR BADGE</label>
                                    <select id="slcColorBadge" name="slcColorBadge"
                                        class="form-control form-control-sm bg-input">
                                        {$optColores}
                                    </select>
                                </div>
                                <div class="col-md-5 d-flex align-items-end">
                                    <div class="form-check form-switch ms-2">
                                        <input class="form-check-input" type="checkbox" id="chkPermiteEdicion"
                                            name="chkPermiteEdicion" value="1" {$chkEdicion}>
                                        <label class="form-check-label small" for="chkPermiteEdicion">Permite edición</label>
                                    </div>
                                </div>
                            </div>
                        </form>
                    </div>
                    <div class="text-end pb-3 px-3">
                        <button type="button" class="btn btn-primary btn-sm" id="btnGuardaEstado">Guardar</button>
                        <a type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancelar</a>
                    </div>
                </div>
            </div>
        HTML;
        return $html;
    }

    public function addEstado($array)
    {
        try {
            $permiteEdicion = isset($array['chkPermiteEdicion']) ? 1 : 0;
            $sql = "INSERT INTO `ctt_estado_proceso`
                        (`nombre`, `color_badge`, `permite_edicion`, `orden`)
                    VALUES (?, ?, ?, ?)";
            $stmt = $this->conexion->prepare($sql);
            $nombre = mb_strtoupper(trim($array['txtNombreEstado']));
            $stmt->bindValue(1, $nombre, PDO::PARAM_STR);
            $stmt->bindValue(2, $array['slcColorBadge'], PDO::PARAM_STR);
            $stmt->bindValue(3, $permiteEdicion, PDO::PARAM_INT);
            $stmt->bindValue(4, $array['numOrden'] ?? 1, PDO::PARAM_INT);
            $stmt->execute();
            $id = $this->conexion->lastInsertId();
            if ($id > 0) {
                Logs::guardaLog("INSERT INTO `ctt_estado_proceso` (`nombre`, `color_badge`, `permite_edicion`, `orden`) VALUES ('$nombre', '{$array['slcColorBadge']}', $permiteEdicion, {$array['numOrden']})");
                return 'si';
            }
            return 'No se insertó el registro';
        } catch (PDOException $e) {
            return 'Error SQL: ' . $e->getMessage();
        }
    }

    public function editEstado($array)
    {
        try {
            $permiteEdicion = isset($array['chkPermiteEdicion']) ? 1 : 0;
            $nombre = mb_strtoupper(trim($array['txtNombreEstado']));
            $sql = "UPDATE `ctt_estado_proceso`
                        SET `nombre` = ?, `color_badge` = ?, `permite_edicion` = ?, `orden` = ?
                    WHERE `id_estado` = ?";
            $stmt = $this->conexion->prepare($sql);
            $stmt->bindValue(1, $nombre, PDO::PARAM_STR);
            $stmt->bindValue(2, $array['slcColorBadge'], PDO::PARAM_STR);
            $stmt->bindValue(3, $permiteEdicion, PDO::PARAM_INT);
            $stmt->bindValue(4, $array['numOrden'] ?? 1, PDO::PARAM_INT);
            $stmt->bindValue(5, $array['id'], PDO::PARAM_INT);
            if (!$stmt->execute()) {
                return 'Error: ' . $stmt->errorInfo()[2];
            }
            if ($stmt->rowCount() > 0) {
                Logs::guardaLog("UPDATE `ctt_estado_proceso` SET `nombre` = '$nombre', `color_badge` = '{$array['slcColorBadge']}', `permite_edicion` = $permiteEdicion WHERE `id_estado` = {$array['id']}");
                return 'si';
            }
            return 'No se realizó ningún cambio.';
        } catch (PDOException $e) {
            return 'Error SQL: ' . $e->getMessage();
        }
    }

    public function delEstado($id)
    {
        try {
            $sqlCheck = "SELECT COUNT(*) AS total FROM `ctt_contratos_new` WHERE `id_estado` = ?";
            $stmtCheck = $this->conexion->prepare($sqlCheck);
            $stmtCheck->bindParam(1, $id, PDO::PARAM_INT);
            $stmtCheck->execute();
            if ($stmtCheck->fetch(PDO::FETCH_ASSOC)['total'] > 0) {
                return 'No se puede eliminar: el estado está en uso por contratos existentes.';
            }
            $sql      = "DELETE FROM `ctt_estado_proceso` WHERE `id_estado` = ?";
            $consulta = "DELETE FROM `ctt_estado_proceso` WHERE `id_estado` = $id";
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
        $sql = "SELECT `id_estado`, `nombre`, `color_badge`, `permite_edicion`
                FROM `ctt_estado_proceso` ORDER BY `orden` ASC";
        $stmt = $instance->conexion->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }
}
