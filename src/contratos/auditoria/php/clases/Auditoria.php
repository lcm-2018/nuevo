<?php
namespace Src\Contratos\Auditoria\Php\Clases;

use Config\Clases\Conexion;
use Config\Clases\Logs;
use Config\Clases\Sesion;
use PDO;
use Exception;

class Auditoria
{
    private $conexion;

    public function __construct()
    {
        $this->conexion = Conexion::getConexion();
    }

    public function getAuditoria($start, $length, $search, $col, $dir)
    {
        $col_names = [
            1 => 'id_auditoria',
            2 => 'fec_reg',
            3 => 'usuario',
            4 => 'modulo',
            5 => 'accion',
            6 => 'id_reg'
        ];
        $orderBy = $col_names[$col] ?? 'id_auditoria';
        $orderDir = strtoupper($dir) === 'ASC' ? 'ASC' : 'DESC';

        $sql = "SELECT a.id_auditoria, a.fec_reg, a.modulo, a.accion, a.id_reg, a.ip,
                       u.nombre as usuario
                FROM ctt_auditoria_new a
                LEFT JOIN seg_usuarios u ON a.id_user_reg = u.id_usuario
                WHERE (u.nombre LIKE :search 
                     OR a.modulo LIKE :search
                     OR a.accion LIKE :search)
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
                FROM ctt_auditoria_new a
                LEFT JOIN seg_usuarios u ON a.id_user_reg = u.id_usuario
                WHERE (u.nombre LIKE :search 
                     OR a.modulo LIKE :search
                     OR a.accion LIKE :search)";
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
        $sql = "SELECT COUNT(*) FROM ctt_auditoria_new";
        try {
            return $this->conexion->query($sql)->fetchColumn();
        } catch (Exception $e) {
            return 0;
        }
    }

    public static function registrar($modulo, $accion, $id_reg, $antes = null, $despues = null)
    {
        $conexion = Conexion::getConexion();
        try {
            $user = Sesion::IdUser();
            $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
            $hoy = date('Y-m-d H:i:s');

            $antes_json = $antes ? json_encode($antes) : null;
            $despues_json = $despues ? json_encode($despues) : null;

            $sql = "INSERT INTO ctt_auditoria_new (id_user_reg, modulo, accion, id_reg, estado_anterior, estado_nuevo, ip, fec_reg) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
            $stmt = $conexion->prepare($sql);
            $stmt->execute([
                $user,
                $modulo,
                $accion,
                $id_reg,
                $antes_json,
                $despues_json,
                $ip,
                $hoy
            ]);
            return true;
        } catch (Exception $e) {
            return false;
        }
    }
}
