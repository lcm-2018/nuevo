<?php

namespace Src\Analytics\Conf_Bdatos\Php\Clases;

use Config\Clases\Conexion;
use PDO;

class BdatosModel
{
    private $conexion;

    public function __construct()
    {
        $this->conexion = Conexion::getConexion();
    }

    private function buildWhere(array $filters): string
    {
        $where = "WHERE dash_bdatos.id_entidad<>0";
        if (!empty($filters['nombre'])) {
            $where .= " AND dash_bdatos.nombre_entidad LIKE '%" . $filters['nombre'] . "%'";
        }
        if (isset($filters['estado']) && strlen((string)$filters['estado'])) {
            $where .= " AND dash_bdatos.estado = " . (int)$filters['estado'];
        }
        return $where;
    }

    public function countAll(): int
    {
        $sql = "SELECT COUNT(*) AS total FROM dash_bdatos WHERE id_entidad<>0";
        $stmt = $this->conexion->prepare($sql);
        $stmt->execute();
        $r = $stmt->fetch(PDO::FETCH_ASSOC);
        return (int)($r['total'] ?? 0);
    }

    public function countFiltered(array $filters): int
    {
        $bw = $this->buildWhere($filters);
        $sql = "SELECT COUNT(*) AS total FROM dash_bdatos " . $bw;
        $stmt = $this->conexion->prepare($sql);        
        $stmt->execute();
        $r = $stmt->fetch(PDO::FETCH_ASSOC);
        return (int)($r['total'] ?? 0);
    }

    public function fetchList(array $filters = [], int $start = 0, int $length = 10, string $orderBy = 'id_entidad', string $dir = 'DESC')
    {
        $bw = $this->buildWhere($filters);
        
        $limitSql = '';
        if ($length != -1) {
            $limitSql = " LIMIT :start, :length";
        }

        $sql = "SELECT dash_bdatos.id_entidad,dash_bdatos.nombre_entidad,dash_bdatos.descri_entidad,
                    dash_bdatos.ip_servidor,dash_bdatos.nombre_bd,dash_bdatos.puerto_bd,
                    IF(dash_bdatos.estado=1,'ACTIVO','INACTIVO') AS estado
                FROM dash_bdatos " . $bw . " ORDER BY $orderBy $dir" . $limitSql;
        $stmt = $this->conexion->prepare($sql);
        if ($length != -1) {
            $stmt->bindValue(':start', (int)$start, PDO::PARAM_INT);
            $stmt->bindValue(':length', (int)$length, PDO::PARAM_INT);
        }
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getById(int $id)
    {
        $sql = "SELECT * FROM dash_bdatos WHERE id_entidad = :id_entidad LIMIT 1";
        $stmt = $this->conexion->prepare($sql);
        $stmt->bindParam(':id_entidad', $id, PDO::PARAM_INT);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($row === false) {
            $columnsStmt = $this->conexion->query("DESCRIBE dash_bdatos");
            $columns = $columnsStmt->fetchAll(PDO::FETCH_COLUMN);
            $row = array_fill_keys($columns, '');
        }
        return $row;            
    }

    public function insert(array $d)
    {
        $sql = "INSERT INTO dash_bdatos(nombre_entidad,descri_entidad,ip_servidor,nombre_bd,usuario_bd,password_bd,puerto_bd,estado,id_usr_crea,fec_crea)
                VALUES(:nombre_entidad,:descri_entidad,:ip_servidor,:nombre_bd,:usuario_bd,:password_bd,:puerto_bd,:estado,:id_usr_crea,:fec_crea)";
        $stmt = $this->conexion->prepare($sql);
        $stmt->bindParam(':nombre_entidad', $d['nombre_entidad']);
        $stmt->bindParam(':descri_entidad', $d['descri_entidad']);
        $stmt->bindParam(':ip_servidor', $d['ip_servidor']);
        $stmt->bindParam(':nombre_bd', $d['nombre_bd']);
        $stmt->bindParam(':usuario_bd', $d['usuario_bd']);
        $stmt->bindParam(':password_bd', $d['password_bd']);
        $stmt->bindParam(':puerto_bd', $d['puerto_bd']);
        $stmt->bindParam(':estado', $d['estado']);
        $stmt->bindParam(':id_usr_crea', $d['id_usr_crea']);
        $stmt->bindParam(':fec_crea', $d['fec_crea']);
        $ok = $stmt->execute();
        if ($ok) {
            $rs = $this->conexion->query('SELECT LAST_INSERT_ID() AS id');
            $obj = $rs->fetch(PDO::FETCH_ASSOC);
            return $obj['id'] ?? null;
        }
        return false;
    }

    public function update(int $id, array $d)
    {
        $sql = "UPDATE dash_bdatos 
                SET nombre_entidad=:nombre_entidad,descri_entidad=:descri_entidad,ip_servidor=:ip_servidor,
                    nombre_bd=:nombre_bd,usuario_bd=:usuario_bd,password_bd=:password_bd,puerto_bd=:puerto_bd,estado=:estado
                WHERE id_entidad=:id_entidad";
        $stmt = $this->conexion->prepare($sql);
        $stmt->bindParam(':nombre_entidad', $d['nombre_entidad']);
        $stmt->bindParam(':descri_entidad', $d['descri_entidad']);
        $stmt->bindParam(':ip_servidor', $d['ip_servidor']);
        $stmt->bindParam(':nombre_bd', $d['nombre_bd']);
        $stmt->bindParam(':usuario_bd', $d['usuario_bd']);
        $stmt->bindParam(':password_bd', $d['password_bd']);
        $stmt->bindParam(':puerto_bd', $d['puerto_bd']);
        $stmt->bindParam(':estado', $d['estado']);
        $stmt->bindParam(':id_entidad', $id, PDO::PARAM_INT);
        return $stmt->execute();
    }

    public function delete(int $id)
    {
        $sql = "DELETE FROM dash_bdatos WHERE id_entidad = :id_entidad";
        $stmt = $this->conexion->prepare($sql);
        $stmt->bindParam(':id_entidad', $id, PDO::PARAM_INT);
        return $stmt->execute();
    }    
}
