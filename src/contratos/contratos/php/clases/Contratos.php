<?php
namespace Src\Contratos\Contratos\Php\Clases;

use Config\Clases\Conexion;
use Config\Clases\Logs;
use Config\Clases\Sesion;
use PDO;
use Exception;

class Contratos
{
    private $conexion;

    public function __construct()
    {
        $this->conexion = Conexion::getConexion();
    }

    public function getContratos($start, $length, $search, $col, $dir)
    {
        $col_names = [
            1 => 'id_contrato',
            2 => 'codigo_contrato',
            3 => 'codigo_proceso',
            4 => 'contratista',
            5 => 'fec_inicio',
            6 => 'fec_fin',
            7 => 'valor_total',
            8 => 'estado'
        ];
        $orderBy = $col_names[$col] ?? 'id_contrato';
        $orderDir = strtoupper($dir) === 'ASC' ? 'ASC' : 'DESC';

        $sql = "SELECT c.id_contrato, c.codigo_contrato, p.codigo_proceso,
                       t.nom_tercero as contratista, t.nit_tercero,
                       c.fec_inicio, c.fec_fin, c.valor_total,
                       e.nombre as estado, e.color_badge
                FROM ctt_contratos_new c
                INNER JOIN ctt_procesos_new p ON c.id_proceso = p.id_proceso
                INNER JOIN tb_terceros t ON c.id_tercero = t.id_tercero
                INNER JOIN ctt_estado_proceso e ON c.id_estado = e.id_estado
                WHERE c.id_vigencia = " . Sesion::Vigencia() . "
                AND (c.codigo_contrato LIKE :search 
                     OR p.codigo_proceso LIKE :search
                     OR t.nom_tercero LIKE :search
                     OR t.nit_tercero LIKE :search)
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
                FROM ctt_contratos_new c
                INNER JOIN ctt_procesos_new p ON c.id_proceso = p.id_proceso
                INNER JOIN tb_terceros t ON c.id_tercero = t.id_tercero
                WHERE c.id_vigencia = " . Sesion::Vigencia() . "
                AND (c.codigo_contrato LIKE :search 
                     OR p.codigo_proceso LIKE :search
                     OR t.nom_tercero LIKE :search
                     OR t.nit_tercero LIKE :search)";
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
        $sql = "SELECT COUNT(*) FROM ctt_contratos_new WHERE id_vigencia = " . Sesion::Vigencia();
        try {
            return $this->conexion->query($sql)->fetchColumn();
        } catch (Exception $e) {
            return 0;
        }
    }

    public function getFormulario($id)
    {
        $reg = [
            'id_contrato' => 0,
            'id_proceso' => 0,
            'objeto_contrato' => '',
            'id_tercero' => 0,
            'fec_inicio' => '',
            'fec_fin' => '',
            'valor_total' => '',
            'observaciones' => ''
        ];

        if ($id > 0) {
            $sql = "SELECT * FROM ctt_contratos_new WHERE id_contrato = ?";
            $stmt = $this->conexion->prepare($sql);
            $stmt->execute([$id]);
            if ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $reg = $row;
            }
        }

        // Combo Procesos (Solo aprobados o vigentes)
        $optProcesos = '<option value="">--Seleccionar Proceso--</option>';
        $stmtP = $this->conexion->query("SELECT id_proceso, codigo_proceso, objeto FROM ctt_procesos_new WHERE id_estado >= 1 ORDER BY codigo_proceso DESC");
        while ($p = $stmtP->fetch(PDO::FETCH_ASSOC)) {
            $sel = ($reg['id_proceso'] == $p['id_proceso']) ? 'selected' : '';
            $optProcesos .= "<option value=\"{$p['id_proceso']}\" $sel>{$p['codigo_proceso']} - " . substr($p['objeto'], 0, 40) . "...</option>";
        }

        // Combo Terceros
        $optTerceros = '<option value="">--Seleccionar Contratista--</option>';
        $stmtT = $this->conexion->query("SELECT id_tercero, nit_tercero, nom_tercero FROM tb_terceros WHERE estado = 1 ORDER BY nom_tercero");
        while ($t = $stmtT->fetch(PDO::FETCH_ASSOC)) {
            $sel = ($reg['id_tercero'] == $t['id_tercero']) ? 'selected' : '';
            $optTerceros .= "<option value=\"{$t['id_tercero']}\" $sel>{$t['nit_tercero']} - {$t['nom_tercero']}</option>";
        }

        $html = <<<HTML
        <form id="formGestContrato" class="p-3">
            <input type="hidden" name="id" value="{$reg['id_contrato']}">
            <div class="row g-3">
                <div class="col-md-12">
                    <label class="small mb-1">Proceso Relacionado</label>
                    <select class="form-select form-select-sm" name="id_proceso" id="id_proceso" required>
                        {$optProcesos}
                    </select>
                </div>
                <div class="col-md-12">
                    <label class="small mb-1">Contratista</label>
                    <select class="form-select form-select-sm" name="id_tercero" id="id_tercero" required>
                        {$optTerceros}
                    </select>
                </div>
                <div class="col-md-12">
                    <label class="small mb-1">Objeto del Contrato</label>
                    <textarea class="form-control form-control-sm" name="objeto_contrato" id="objeto_contrato" rows="3" required>{$reg['objeto_contrato']}</textarea>
                </div>
                <div class="col-md-4">
                    <label class="small mb-1">Fecha de Inicio</label>
                    <input type="date" class="form-control form-control-sm" name="fec_inicio" id="fec_inicio" value="{$reg['fec_inicio']}" required>
                </div>
                <div class="col-md-4">
                    <label class="small mb-1">Fecha de Fin</label>
                    <input type="date" class="form-control form-control-sm" name="fec_fin" id="fec_fin" value="{$reg['fec_fin']}" required>
                </div>
                <div class="col-md-4">
                    <label class="small mb-1">Valor Total</label>
                    <input type="number" step="0.01" class="form-control form-control-sm" name="valor_total" id="valor_total" value="{$reg['valor_total']}" required>
                </div>
                <div class="col-12">
                    <label class="small mb-1">Observaciones</label>
                    <textarea class="form-control form-control-sm" name="observaciones" id="observaciones" rows="2">{$reg['observaciones']}</textarea>
                </div>
            </div>
            <div class="text-center mt-3">
                <button type="button" class="btn btn-primary btn-sm px-4 shadow" id="btnGuardaContrato">Guardar</button>
            </div>
        </form>
        HTML;

        return $html;
    }

    private function generarCodigoContrato($vigencia)
    {
        $anio = date('Y');

        $sql = "SELECT COUNT(*) as total FROM ctt_contratos_new WHERE id_vigencia = ?";
        $stmt = $this->conexion->prepare($sql);
        $stmt->execute([$vigencia]);
        $res = $stmt->fetch(PDO::FETCH_ASSOC);

        $consecutivo = str_pad(($res['total'] + 1), 3, '0', STR_PAD_LEFT);
        return "CTT-CON-{$anio}-{$consecutivo}";
    }

    public function addContrato($d)
    {
        try {
            $vigencia = Sesion::Vigencia();
            $codigo = $this->generarCodigoContrato($vigencia);
            $id_estado_inicial = 1; // BORRADOR
            $user = Sesion::IdUser();
            $hoy = date('Y-m-d');

            $sql = "INSERT INTO ctt_contratos_new (id_proceso, codigo_contrato, objeto_contrato, id_tercero, fec_inicio, fec_fin, valor_total, id_vigencia, id_estado, observaciones, id_user_reg, fec_reg) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $stmt = $this->conexion->prepare($sql);
            $stmt->execute([
                $d['id_proceso'],
                $codigo,
                $d['objeto_contrato'],
                $d['id_tercero'],
                $d['fec_inicio'],
                $d['fec_fin'],
                $d['valor_total'],
                $vigencia,
                $id_estado_inicial,
                $d['observaciones'],
                $user,
                $hoy
            ]);

            $id_insert = $this->conexion->lastInsertId();
            Logs::guardaLog('ctt_contratos_new', 1, $id_insert, $codigo);
            return 'si';
        } catch (Exception $e) {
            return $e->getMessage();
        }
    }

    public function editContrato($d)
    {
        try {
            $sql = "UPDATE ctt_contratos_new 
                    SET id_proceso = ?, objeto_contrato = ?, id_tercero = ?, fec_inicio = ?, fec_fin = ?, valor_total = ?, observaciones = ?, id_user_act = ?, fec_act = ?
                    WHERE id_contrato = ?";
            $stmt = $this->conexion->prepare($sql);
            $stmt->execute([
                $d['id_proceso'],
                $d['objeto_contrato'],
                $d['id_tercero'],
                $d['fec_inicio'],
                $d['fec_fin'],
                $d['valor_total'],
                $d['observaciones'],
                Sesion::Id_User(),
                date('Y-m-d'),
                $d['id']
            ]);

            Logs::guardaLog('ctt_contratos_new', 3, $d['id'], "Editado contrato {$d['id']}");
            return 'si';
        } catch (Exception $e) {
            return $e->getMessage();
        }
    }

    public function delContrato($id)
    {
        try {
            $sql = "DELETE FROM ctt_contratos_new WHERE id_contrato = ?";
            $stmt = $this->conexion->prepare($sql);
            $stmt->execute([$id]);

            Logs::guardaLog('ctt_contratos_new', 4, $id, "Eliminado contrato {$id}");
            return 'si';
        } catch (Exception $e) {
            return $e->getMessage();
        }
    }
}
