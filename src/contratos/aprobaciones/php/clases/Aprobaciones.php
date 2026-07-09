<?php
namespace Src\Contratos\Aprobaciones\Php\Clases;

use Config\Clases\Conexion;
use Config\Clases\Logs;
use Config\Clases\Sesion;
use PDO;
use Exception;

class Aprobaciones
{
    private $conexion;

    public function __construct()
    {
        $this->conexion = Conexion::getConexion();
    }

    public function getAprobaciones($start, $length, $search, $col, $dir)
    {
        $col_names = [
            1 => 'id_aprobacion',
            2 => 'fec_aprobacion',
            3 => 'aprobador',
            4 => 'rol_aprobador',
            5 => 'estado',
            6 => 'proceso',
            7 => 'contrato'
        ];
        $orderBy = $col_names[$col] ?? 'id_aprobacion';
        $orderDir = strtoupper($dir) === 'ASC' ? 'ASC' : 'DESC';

        $sql = "SELECT a.id_aprobacion, a.fec_aprobacion, a.rol_aprobador, a.estado, a.observaciones,
                       u.nombre as aprobador,
                       p.codigo_proceso, c.codigo_contrato
                FROM ctt_aprobaciones a
                LEFT JOIN seg_usuarios u ON a.id_user_aprobador = u.id_usuario
                LEFT JOIN ctt_procesos_new p ON a.id_proceso = p.id_proceso
                LEFT JOIN ctt_contratos_new c ON a.id_contrato = c.id_contrato
                WHERE (u.nombre LIKE :search 
                     OR p.codigo_proceso LIKE :search
                     OR c.codigo_contrato LIKE :search)
                ORDER BY $orderBy $orderDir
                LIMIT :start, :length";

        try {
            $stmt = $this->conexion->prepare($sql);
            $searchParam = "%$search%";
            $stmt->bindParam(':search', $searchParam, PDO::PARAM_STR);
            $stmt->bindParam(':start', $start, PDO::PARAM_INT);
            $stmt->bindParam(':length', $length, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            return [];
        }
    }

    public function getRegistrosFilter($search)
    {
        $sql = "SELECT COUNT(*)
                FROM ctt_aprobaciones a
                LEFT JOIN seg_usuarios u ON a.id_user_aprobador = u.id_usuario
                LEFT JOIN ctt_procesos_new p ON a.id_proceso = p.id_proceso
                LEFT JOIN ctt_contratos_new c ON a.id_contrato = c.id_contrato
                WHERE (u.nombre LIKE :search 
                     OR p.codigo_proceso LIKE :search
                     OR c.codigo_contrato LIKE :search)";
        try {
            $stmt = $this->conexion->prepare($sql);
            $searchParam = "%$search%";
            $stmt->bindParam(':search', $searchParam, PDO::PARAM_STR);
            $stmt->execute();
            return $stmt->fetchColumn();
        } catch (Exception $e) {
            return 0;
        }
    }

    public function getRegistrosTotal()
    {
        $sql = "SELECT COUNT(*) FROM ctt_aprobaciones";
        try {
            return $this->conexion->query($sql)->fetchColumn();
        } catch (Exception $e) {
            return 0;
        }
    }

    public function getFormulario($id)
    {
        $reg = [
            'id_aprobacion' => 0,
            'id_proceso' => 0,
            'id_contrato' => 0,
            'rol_aprobador' => '',
            'estado' => 1,
            'observaciones' => ''
        ];

        if ($id > 0) {
            $sql = "SELECT * FROM ctt_aprobaciones WHERE id_aprobacion = ?";
            $stmt = $this->conexion->prepare($sql);
            $stmt->execute([$id]);
            if ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $reg = $row;
            }
        }

        // Combo Procesos
        $optProcesos = '<option value="">--Ninguno--</option>';
        $stmtP = $this->conexion->query("SELECT id_proceso, codigo_proceso FROM ctt_procesos_new ORDER BY codigo_proceso DESC");
        while ($p = $stmtP->fetch(PDO::FETCH_ASSOC)) {
            $sel = ($reg['id_proceso'] == $p['id_proceso']) ? 'selected' : '';
            $optProcesos .= "<option value=\"{$p['id_proceso']}\" $sel>{$p['codigo_proceso']}</option>";
        }

        // Combo Contratos
        $optContratos = '<option value="">--Ninguno--</option>';
        $stmtC = $this->conexion->query("SELECT id_contrato, codigo_contrato FROM ctt_contratos_new ORDER BY codigo_contrato DESC");
        while ($c = $stmtC->fetch(PDO::FETCH_ASSOC)) {
            $sel = ($reg['id_contrato'] == $c['id_contrato']) ? 'selected' : '';
            $optContratos .= "<option value=\"{$c['id_contrato']}\" $sel>{$c['codigo_contrato']}</option>";
        }

        $selApr = $reg['estado'] == 1 ? 'selected' : '';
        $selRech = $reg['estado'] == 0 ? 'selected' : '';

        $html = <<<HTML
        <form id="formGestAprobacion" class="p-3">
            <input type="hidden" name="id" value="{$reg['id_aprobacion']}">
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="small mb-1">Proceso (Opcional)</label>
                    <select class="form-select form-select-sm" name="id_proceso" id="id_proceso">
                        {$optProcesos}
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="small mb-1">Contrato (Opcional)</label>
                    <select class="form-select form-select-sm" name="id_contrato" id="id_contrato">
                        {$optContratos}
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="small mb-1">Rol Aprobador</label>
                    <input type="text" class="form-control form-control-sm" name="rol_aprobador" id="rol_aprobador" value="{$reg['rol_aprobador']}" required>
                </div>
                <div class="col-md-6">
                    <label class="small mb-1">Decisión</label>
                    <select class="form-select form-select-sm" name="estado" id="estado" required>
                        <option value="1" {$selApr}>Aprobar</option>
                        <option value="0" {$selRech}>Rechazar</option>
                    </select>
                </div>
                <div class="col-12">
                    <label class="small mb-1">Observaciones / Justificación</label>
                    <textarea class="form-control form-control-sm" name="observaciones" id="observaciones" rows="3" required>{$reg['observaciones']}</textarea>
                </div>
            </div>
            <div class="text-center mt-3">
                <button type="button" class="btn btn-primary btn-sm px-4 shadow" id="btnGuardaAprobacion">Guardar Decisión</button>
            </div>
        </form>
        HTML;

        return $html;
    }

    public function addAprobacion($d)
    {
        try {
            $user = Sesion::IdUser();
            $hoy = date('Y-m-d H:i:s');

            $sql = "INSERT INTO ctt_aprobaciones (id_proceso, id_contrato, id_user_aprobador, rol_aprobador, estado, observaciones, fec_aprobacion) 
                    VALUES (?, ?, ?, ?, ?, ?, ?)";
            $stmt = $this->conexion->prepare($sql);
            $stmt->execute([
                $d['id_proceso'] ?: null,
                $d['id_contrato'] ?: null,
                $user,
                $d['rol_aprobador'],
                $d['estado'],
                $d['observaciones'],
                $hoy
            ]);

            $id_insert = $this->conexion->lastInsertId();
            Logs::guardaLog('ctt_aprobaciones', 1, $id_insert, "Aprobación/Rechazo registrada");
            return 'si';
        } catch (Exception $e) {
            return $e->getMessage();
        }
    }

    public function editAprobacion($d)
    {
        try {
            $sql = "UPDATE ctt_aprobaciones 
                    SET id_proceso = ?, id_contrato = ?, rol_aprobador = ?, estado = ?, observaciones = ?
                    WHERE id_aprobacion = ?";
            $stmt = $this->conexion->prepare($sql);
            $stmt->execute([
                $d['id_proceso'] ?: null,
                $d['id_contrato'] ?: null,
                $d['rol_aprobador'],
                $d['estado'],
                $d['observaciones'],
                $d['id']
            ]);

            Logs::guardaLog('ctt_aprobaciones', 3, $d['id'], "Editada aprobación {$d['id']}");
            return 'si';
        } catch (Exception $e) {
            return $e->getMessage();
        }
    }

    public function delAprobacion($id)
    {
        try {
            $sql = "DELETE FROM ctt_aprobaciones WHERE id_aprobacion = ?";
            $stmt = $this->conexion->prepare($sql);
            $stmt->execute([$id]);

            Logs::guardaLog('ctt_aprobaciones', 4, $id, "Eliminada aprobación {$id}");
            return 'si';
        } catch (Exception $e) {
            return $e->getMessage();
        }
    }
}
