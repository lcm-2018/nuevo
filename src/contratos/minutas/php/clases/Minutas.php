<?php
namespace Src\Contratos\Minutas\Php\Clases;

use Config\Clases\Conexion;
use Config\Clases\Logs;
use Config\Clases\Sesion;
use PDO;
use Exception;

class Minutas
{
    private $conexion;

    public function __construct()
    {
        $this->conexion = Conexion::getConexion();
    }

    public function getMinutas($start, $length, $search, $col, $dir)
    {
        $col_names = [
            1 => 'id_minuta',
            2 => 'version',
            3 => 'fec_reg',
            4 => 'codigo_contrato'
        ];
        $orderBy = $col_names[$col] ?? 'id_minuta';
        $orderDir = strtoupper($dir) === 'ASC' ? 'ASC' : 'DESC';

        $sql = "SELECT m.id_minuta, m.version, m.fec_reg, c.codigo_contrato,
                       u.nombre as usuario
                FROM ctt_minutas m
                INNER JOIN ctt_contratos_new c ON m.id_contrato = c.id_contrato
                LEFT JOIN seg_usuarios u ON m.id_user_reg = u.id_usuario
                WHERE (c.codigo_contrato LIKE :search 
                     OR u.nombre LIKE :search)
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
                FROM ctt_minutas m
                INNER JOIN ctt_contratos_new c ON m.id_contrato = c.id_contrato
                LEFT JOIN seg_usuarios u ON m.id_user_reg = u.id_usuario
                WHERE (c.codigo_contrato LIKE :search 
                     OR u.nombre LIKE :search)";
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
        $sql = "SELECT COUNT(*) FROM ctt_minutas";
        try {
            return $this->conexion->query($sql)->fetchColumn();
        } catch (Exception $e) {
            return 0;
        }
    }

    public function getFormulario($id)
    {
        $reg = [
            'id_minuta' => 0,
            'id_contrato' => 0,
            'contenido_html' => ''
        ];

        if ($id > 0) {
            $sql = "SELECT * FROM ctt_minutas WHERE id_minuta = ?";
            $stmt = $this->conexion->prepare($sql);
            $stmt->execute([$id]);
            if ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $reg = $row;
            }
        }

        // Combo Contratos
        $optContratos = '<option value="">--Seleccionar Contrato--</option>';
        $stmtC = $this->conexion->query("SELECT id_contrato, codigo_contrato FROM ctt_contratos_new WHERE id_estado >= 1 ORDER BY codigo_contrato DESC");
        while ($c = $stmtC->fetch(PDO::FETCH_ASSOC)) {
            $sel = ($reg['id_contrato'] == $c['id_contrato']) ? 'selected' : '';
            $optContratos .= "<option value=\"{$c['id_contrato']}\" $sel>{$c['codigo_contrato']}</option>";
        }

        $html = <<<HTML
        <form id="formGestMinuta" class="p-3">
            <input type="hidden" name="id" value="{$reg['id_minuta']}">
            <div class="row g-3">
                <div class="col-md-12">
                    <label class="small mb-1">Contrato Relacionado</label>
                    <select class="form-select form-select-sm" name="id_contrato" id="id_contrato" required>
                        {$optContratos}
                    </select>
                </div>
                <div class="col-md-12">
                    <label class="small mb-1">Contenido de la Minuta (HTML)</label>
                    <textarea class="form-control form-control-sm" name="contenido_html" id="contenido_html" rows="10" required>{$reg['contenido_html']}</textarea>
                </div>
            </div>
            <div class="text-center mt-3">
                <button type="button" class="btn btn-primary btn-sm px-4 shadow" id="btnGuardaMinuta">Guardar</button>
            </div>
        </form>
        <!-- Inicializar editor rico si se desea -->
        <script>
            // if(typeof ClassicEditor !== 'undefined') {
            //     ClassicEditor.create(document.querySelector('#contenido_html'));
            // }
        </script>
        HTML;

        return $html;
    }

    public function addMinuta($d)
    {
        try {
            // Calcular nueva versión
            $sqlV = "SELECT MAX(version) as max_v FROM ctt_minutas WHERE id_contrato = ?";
            $stmtV = $this->conexion->prepare($sqlV);
            $stmtV->execute([$d['id_contrato']]);
            $res = $stmtV->fetch(PDO::FETCH_ASSOC);
            $version = ($res['max_v'] > 0) ? $res['max_v'] + 1 : 1;

            $user = Sesion::IdUser();
            $hoy = date('Y-m-d H:i:s');

            $sql = "INSERT INTO ctt_minutas (id_contrato, version, contenido_html, id_user_reg, fec_reg) 
                    VALUES (?, ?, ?, ?, ?)";
            $stmt = $this->conexion->prepare($sql);
            $stmt->execute([
                $d['id_contrato'],
                $version,
                $d['contenido_html'],
                $user,
                $hoy
            ]);

            $id_insert = $this->conexion->lastInsertId();
            Logs::guardaLog('ctt_minutas', 1, $id_insert, "Creada minuta v{$version}");
            return 'si';
        } catch (Exception $e) {
            return $e->getMessage();
        }
    }

    public function editMinuta($d)
    {
        try {
            // En vez de editar, lo mejor en minutas es versionar, pero si se permite edición de la última versión:
            $sql = "UPDATE ctt_minutas 
                    SET contenido_html = ?
                    WHERE id_minuta = ?";
            $stmt = $this->conexion->prepare($sql);
            $stmt->execute([
                $d['contenido_html'],
                $d['id']
            ]);

            Logs::guardaLog('ctt_minutas', 3, $d['id'], "Editada minuta {$d['id']}");
            return 'si';
        } catch (Exception $e) {
            return $e->getMessage();
        }
    }

    public function delMinuta($id)
    {
        try {
            $sql = "DELETE FROM ctt_minutas WHERE id_minuta = ?";
            $stmt = $this->conexion->prepare($sql);
            $stmt->execute([$id]);

            Logs::guardaLog('ctt_minutas', 4, $id, "Eliminada minuta {$id}");
            return 'si';
        } catch (Exception $e) {
            return $e->getMessage();
        }
    }
}
