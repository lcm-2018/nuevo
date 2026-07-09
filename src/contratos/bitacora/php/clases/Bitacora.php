<?php
namespace Src\Contratos\Bitacora\Php\Clases;

use Config\Clases\Conexion;
use Config\Clases\Logs;
use Config\Clases\Sesion;
use PDO;
use Exception;

class Bitacora
{
    private $conexion;

    public function __construct()
    {
        $this->conexion = Conexion::getConexion();
    }

    public function getBitacora($start, $length, $search, $col, $dir)
    {
        $col_names = [
            1 => 'id_bitacora',
            2 => 'fec_evento',
            3 => 'usuario',
            4 => 'tipo_evento',
            5 => 'descripcion'
        ];
        $orderBy = $col_names[$col] ?? 'id_bitacora';
        $orderDir = strtoupper($dir) === 'ASC' ? 'ASC' : 'DESC';

        $sql = "SELECT b.id_bitacora, b.fec_evento, b.tipo_evento, b.descripcion,
                       u.nombre as usuario,
                       c.codigo_contrato, p.codigo_proceso
                FROM ctt_bitacora b
                LEFT JOIN seg_usuarios u ON b.id_user_reg = u.id_usuario
                LEFT JOIN ctt_contratos_new c ON b.id_contrato = c.id_contrato
                LEFT JOIN ctt_procesos_new p ON b.id_proceso = p.id_proceso
                WHERE (u.nombre LIKE :search 
                     OR b.tipo_evento LIKE :search
                     OR b.descripcion LIKE :search
                     OR c.codigo_contrato LIKE :search
                     OR p.codigo_proceso LIKE :search)
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
                FROM ctt_bitacora b
                LEFT JOIN seg_usuarios u ON b.id_user_reg = u.id_usuario
                LEFT JOIN ctt_contratos_new c ON b.id_contrato = c.id_contrato
                LEFT JOIN ctt_procesos_new p ON b.id_proceso = p.id_proceso
                WHERE (u.nombre LIKE :search 
                     OR b.tipo_evento LIKE :search
                     OR b.descripcion LIKE :search
                     OR c.codigo_contrato LIKE :search
                     OR p.codigo_proceso LIKE :search)";
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
        $sql = "SELECT COUNT(*) FROM ctt_bitacora";
        try {
            return $this->conexion->query($sql)->fetchColumn();
        } catch (Exception $e) {
            return 0;
        }
    }

    public function getFormulario($id)
    {
        $reg = [
            'id_bitacora' => 0,
            'id_proceso' => 0,
            'id_contrato' => 0,
            'tipo_evento' => '',
            'descripcion' => ''
        ];

        if ($id > 0) {
            $sql = "SELECT * FROM ctt_bitacora WHERE id_bitacora = ?";
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

        $html = <<<HTML
        <form id="formGestBitacora" class="p-3">
            <input type="hidden" name="id" value="{$reg['id_bitacora']}">
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="small mb-1">Proceso Relacionado</label>
                    <select class="form-select form-select-sm" name="id_proceso" id="id_proceso">
                        {$optProcesos}
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="small mb-1">Contrato Relacionado</label>
                    <select class="form-select form-select-sm" name="id_contrato" id="id_contrato">
                        {$optContratos}
                    </select>
                </div>
                <div class="col-md-12">
                    <label class="small mb-1">Tipo de Evento</label>
                    <input type="text" class="form-control form-control-sm" name="tipo_evento" id="tipo_evento" value="{$reg['tipo_evento']}" required>
                </div>
                <div class="col-12">
                    <label class="small mb-1">Descripción del Evento</label>
                    <textarea class="form-control form-control-sm" name="descripcion" id="descripcion" rows="3" required>{$reg['descripcion']}</textarea>
                </div>
            </div>
            <div class="text-center mt-3">
                <button type="button" class="btn btn-primary btn-sm px-4 shadow" id="btnGuardaBitacora">Guardar</button>
            </div>
        </form>
        HTML;

        return $html;
    }

    public function addBitacora($d)
    {
        try {
            $user = Sesion::IdUser();
            $hoy = date('Y-m-d H:i:s');

            $sql = "INSERT INTO ctt_bitacora (id_proceso, id_contrato, tipo_evento, descripcion, id_user_reg, fec_evento) 
                    VALUES (?, ?, ?, ?, ?, ?)";
            $stmt = $this->conexion->prepare($sql);
            $stmt->execute([
                $d['id_proceso'] ?: null,
                $d['id_contrato'] ?: null,
                $d['tipo_evento'],
                $d['descripcion'],
                $user,
                $hoy
            ]);

            $id_insert = $this->conexion->lastInsertId();
            Logs::guardaLog('ctt_bitacora', 1, $id_insert, "Bitácora creada");
            return 'si';
        } catch (Exception $e) {
            return $e->getMessage();
        }
    }

    public function editBitacora($d)
    {
        try {
            $sql = "UPDATE ctt_bitacora 
                    SET id_proceso = ?, id_contrato = ?, tipo_evento = ?, descripcion = ?
                    WHERE id_bitacora = ?";
            $stmt = $this->conexion->prepare($sql);
            $stmt->execute([
                $d['id_proceso'] ?: null,
                $d['id_contrato'] ?: null,
                $d['tipo_evento'],
                $d['descripcion'],
                $d['id']
            ]);

            Logs::guardaLog('ctt_bitacora', 3, $d['id'], "Editada bitácora {$d['id']}");
            return 'si';
        } catch (Exception $e) {
            return $e->getMessage();
        }
    }

    public function delBitacora($id)
    {
        try {
            $sql = "DELETE FROM ctt_bitacora WHERE id_bitacora = ?";
            $stmt = $this->conexion->prepare($sql);
            $stmt->execute([$id]);

            Logs::guardaLog('ctt_bitacora', 4, $id, "Eliminada bitácora {$id}");
            return 'si';
        } catch (Exception $e) {
            return $e->getMessage();
        }
    }
}
