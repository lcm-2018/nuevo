<?php
namespace Src\Contratos\Procesos\Php\Clases;

use Config\Clases\Conexion;
use Config\Clases\Logs;
use Config\Clases\Sesion;
use PDO;
use Exception;

class Procesos
{
    private $conexion;

    public function __construct()
    {
        $this->conexion = Conexion::getConexion();
    }

    public function getProcesos($start, $length, $search, $col, $dir)
    {
        $col_names = [
            1 => 'id_proceso',
            2 => 'codigo_proceso',
            3 => 'objeto',
            4 => 'tipo',
            5 => 'modalidad',
            6 => 'estado'
        ];
        $orderBy = $col_names[$col] ?? 'id_proceso';
        $orderDir = strtoupper($dir) === 'ASC' ? 'ASC' : 'DESC';

        $sql = "SELECT p.id_proceso, p.codigo_proceso, p.objeto, 
                       t.nombre as tipo, m.modalidad as modalidad,
                       e.nombre as estado, e.color_badge
                FROM ctt_procesos_new p
                INNER JOIN ctt_tipo_proceso t ON p.id_tipo_proceso = t.id_tipo_proceso
                INNER JOIN ctt_modalidad m ON p.id_modalidad = m.id_modalidad
                INNER JOIN ctt_estado_proceso e ON p.id_estado = e.id_estado
                WHERE p.id_vigencia = " . Sesion::Vigencia() . "
                AND (p.codigo_proceso LIKE :search 
                     OR p.objeto LIKE :search
                     OR t.nombre LIKE :search
                     OR m.modalidad LIKE :search)
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
                FROM ctt_procesos_new p
                INNER JOIN ctt_tipo_proceso t ON p.id_tipo_proceso = t.id_tipo_proceso
                INNER JOIN ctt_modalidad m ON p.id_modalidad = m.id_modalidad
                WHERE p.id_vigencia = " . Sesion::Vigencia() . "
                AND (p.codigo_proceso LIKE :search 
                     OR p.objeto LIKE :search
                     OR t.nombre LIKE :search
                     OR m.modalidad LIKE :search)";
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
        $sql = "SELECT COUNT(*) FROM ctt_procesos_new WHERE id_vigencia = " . Sesion::Vigencia();
        try {
            return $this->conexion->query($sql)->fetchColumn();
        } catch (Exception $e) {
            return 0;
        }
    }

    public function getFormulario($id)
    {
        $reg = [
            'id_proceso' => 0,
            'objeto' => '',
            'id_tipo_proceso' => 0,
            'id_modalidad' => 0,
            'id_area' => 0,
            'observaciones' => ''
        ];

        if ($id > 0) {
            $sql = "SELECT * FROM ctt_procesos_new WHERE id_proceso = ?";
            $stmt = $this->conexion->prepare($sql);
            $stmt->execute([$id]);
            if ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $reg = $row;
            }
        }

        // Combos
        $optTipo = '<option value="">--Seleccionar--</option>';
        $stmtT = $this->conexion->query("SELECT id_tipo_proceso, nombre FROM ctt_tipo_proceso WHERE activo = 1 ORDER BY nombre");
        while ($t = $stmtT->fetch(PDO::FETCH_ASSOC)) {
            $sel = ($reg['id_tipo_proceso'] == $t['id_tipo_proceso']) ? 'selected' : '';
            $optTipo .= "<option value=\"{$t['id_tipo_proceso']}\" $sel>{$t['nombre']}</option>";
        }

        $optModalidad = '<option value="">--Seleccionar--</option>';
        $stmtM = $this->conexion->query("SELECT id_modalidad, modalidad FROM ctt_modalidad ORDER BY modalidad");
        while ($m = $stmtM->fetch(PDO::FETCH_ASSOC)) {
            $sel = ($reg['id_modalidad'] == $m['id_modalidad']) ? 'selected' : '';
            $optModalidad .= "<option value=\"{$m['id_modalidad']}\" $sel>{$m['modalidad']}</option>";
        }

        $html = <<<HTML
        <form id="formGestProceso" class="p-3">
            <input type="hidden" name="id" value="{$reg['id_proceso']}">
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="small mb-1">Tipo de Proceso</label>
                    <select class="form-select form-select-sm" name="id_tipo_proceso" id="id_tipo_proceso" required>
                        {$optTipo}
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="small mb-1">Modalidad</label>
                    <select class="form-select form-select-sm" name="id_modalidad" id="id_modalidad" required>
                        {$optModalidad}
                    </select>
                </div>
                <div class="col-12">
                    <label class="small mb-1">Objeto del Proceso</label>
                    <textarea class="form-control form-control-sm" name="objeto" id="objeto" rows="3" required>{$reg['objeto']}</textarea>
                </div>
                <div class="col-12">
                    <label class="small mb-1">Observaciones</label>
                    <textarea class="form-control form-control-sm" name="observaciones" id="observaciones" rows="2">{$reg['observaciones']}</textarea>
                </div>
            </div>
            <div class="text-center mt-3">
                <button type="button" class="btn btn-primary btn-sm px-4 shadow" id="btnGuardaProceso">Guardar</button>
            </div>
        </form>
        HTML;

        return $html;
    }

    private function generarCodigoProceso($vigencia)
    {
        $anio = date('Y'); // o consultar tabla nom_vigencia según $vigencia

        $sql = "SELECT COUNT(*) as total FROM ctt_procesos_new WHERE id_vigencia = ?";
        $stmt = $this->conexion->prepare($sql);
        $stmt->execute([$vigencia]);
        $res = $stmt->fetch(PDO::FETCH_ASSOC);

        $consecutivo = str_pad(($res['total'] + 1), 3, '0', STR_PAD_LEFT);
        return "CTT-{$anio}-{$consecutivo}";
    }

    public function addProceso($d)
    {
        try {
            $vigencia = Sesion::Vigencia();
            $codigo = $this->generarCodigoProceso($vigencia);
            $id_estado_inicial = 1; // BORRADOR
            $user = Sesion::IdUser();
            $hoy = date('Y-m-d');

            $sql = "INSERT INTO ctt_procesos_new (codigo_proceso, objeto, id_tipo_proceso, id_modalidad, id_area, id_vigencia, id_estado, observaciones, id_user_reg, fec_reg) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $stmt = $this->conexion->prepare($sql);
            $stmt->execute([
                $codigo,
                $d['objeto'],
                $d['id_tipo_proceso'],
                $d['id_modalidad'],
                $d['id_area'] ?? 0,
                $vigencia,
                $id_estado_inicial,
                $d['observaciones'],
                $user,
                $hoy
            ]);

            $id_insert = $this->conexion->lastInsertId();
            Logs::guardaLog('ctt_procesos_new', 1, $id_insert, $codigo);
            return 'si';
        } catch (Exception $e) {
            return $e->getMessage();
        }
    }

    public function editProceso($d)
    {
        try {
            $sql = "UPDATE ctt_procesos_new 
                    SET objeto = ?, id_tipo_proceso = ?, id_modalidad = ?, observaciones = ?, id_user_act = ?, fec_act = ?
                    WHERE id_proceso = ?";
            $stmt = $this->conexion->prepare($sql);
            $stmt->execute([
                $d['objeto'],
                $d['id_tipo_proceso'],
                $d['id_modalidad'],
                $d['observaciones'],
                Sesion::Id_User(),
                date('Y-m-d'),
                $d['id']
            ]);

            Logs::guardaLog('ctt_procesos_new', 3, $d['id'], "Editado proceso {$d['id']}");
            return 'si';
        } catch (Exception $e) {
            return $e->getMessage();
        }
    }

    public function delProceso($id)
    {
        try {
            $sql = "DELETE FROM ctt_procesos_new WHERE id_proceso = ?";
            $stmt = $this->conexion->prepare($sql);
            $stmt->execute([$id]);

            Logs::guardaLog('ctt_procesos_new', 4, $id, "Eliminado proceso {$id}");
            return 'si';
        } catch (Exception $e) {
            return $e->getMessage();
        }
    }
}
