<?php

namespace Src\Analytics\Conf_Consultas\Php\Clases;

use Config\Clases\Conexion;
use PDO;

class ConsultasModel
{
    private $conexion;

    public function __construct()
    {
        $this->conexion = Conexion::getConexion();
    }

    private function buildWhere(array $filters): string
    {
        $where = "WHERE dash_consultas.id_consulta<>0";
        if (!empty($filters['titulo'])) {
            $where .= " AND dash_consultas.titulo_consulta LIKE '%" . $filters['titulo'] . "%'";
        }
        if (isset($filters['estado']) && strlen((string)$filters['estado'])) {
            $where .= " AND dash_consultas.estado = " . (int)$filters['estado'];
        }
        return $where;
    }

    public function countAll(): int
    {
        $sql = "SELECT COUNT(*) AS total FROM dash_consultas WHERE id_consulta<>0";
        $stmt = $this->conexion->prepare($sql);
        $stmt->execute();
        $r = $stmt->fetch(PDO::FETCH_ASSOC);
        return (int)($r['total'] ?? 0);
    }

    public function countFiltered(array $filters): int
    {
        $bw = $this->buildWhere($filters);
        $sql = "SELECT COUNT(*) AS total FROM dash_consultas " . $bw;
        $stmt = $this->conexion->prepare($sql);        
        $stmt->execute();
        $r = $stmt->fetch(PDO::FETCH_ASSOC);
        return (int)($r['total'] ?? 0);
    }

    public function fetchList(array $filters = [], int $start = 0, int $length = 10, string $orderBy = 'id_consulta', string $dir = 'DESC')
    {
        $bw = $this->buildWhere($filters);
        
        $limitSql = '';
        if ($length != -1) {
            $limitSql = " LIMIT :start, :length";
        }

        $sql = "SELECT dash_consultas.id_consulta,
                    dash_consultas.titulo_consulta,                    
                    IF(dash_consultas.tipo_bdatos=1,'BD PRIMARIA','BDs DISTRIBUIDAS') AS tipo_bdatos,
                    IF(dash_consultas.tipo_informe=1,'UN INFORME CONSOLIDADO','INFORMES SEPARADOS POR BD') AS tipo_informe,
                    IF(dash_consultas.tipo_consulta=1,'SERVIDOR LOCAL','SERVIDORES REMOTOS') AS tipo_consulta,
                    IF(dash_consultas.tipo_acceso=1,'PUBLICO','USUARIOS AUTORIZADOS') AS tipo_acceso,                    
                    IF(dash_consultas.estado=1,'ACTIVO','INACTIVO') AS estado,
                    dash_consultas.fec_crea,dash_consultas.id_usr_crea
                FROM dash_consultas " . $bw . " ORDER BY $orderBy $dir" . $limitSql;
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
        $sql = "SELECT * FROM dash_consultas WHERE id_consulta = :id_consulta LIMIT 1";
        $stmt = $this->conexion->prepare($sql);
        $stmt->bindParam(':id_consulta', $id, PDO::PARAM_INT);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($row === false) {
            $columnsStmt = $this->conexion->query("DESCRIBE dash_consultas");
            $columns = $columnsStmt->fetchAll(PDO::FETCH_COLUMN);
            $row = array_fill_keys($columns, '');
        }
        return $row;            
    }

    public function insert(array $d)
    {
        $sql = "INSERT INTO dash_consultas(
                    titulo_consulta,detalle_consulta,tipo_bdatos,consulta_sql,consulta_sql_group,
                    tipo_informe,tipo_consulta,tipo_acceso,estado,fec_crea,id_usr_crea
                ) VALUES(
                    :titulo_consulta,:detalle_consulta,:tipo_bdatos,:consulta_sql,:consulta_sql_group,
                    :tipo_informe,:tipo_consulta,:tipo_acceso,:estado,:fec_crea,:id_usr_crea
                )";
        $stmt = $this->conexion->prepare($sql);
        $stmt->bindParam(':titulo_consulta', $d['titulo_consulta']);
        $stmt->bindParam(':detalle_consulta', $d['detalle_consulta']);
        $stmt->bindParam(':tipo_bdatos', $d['tipo_bdatos']);
        $stmt->bindParam(':consulta_sql', $d['consulta_sql']);
        $stmt->bindParam(':consulta_sql_group', $d['consulta_sql_group']);
        $stmt->bindParam(':tipo_informe', $d['tipo_informe']);
        $stmt->bindParam(':tipo_consulta', $d['tipo_consulta']);
        $stmt->bindParam(':tipo_acceso', $d['tipo_acceso']);
        $stmt->bindParam(':estado', $d['estado']);
        $stmt->bindParam(':fec_crea', $d['fec_crea']);
        $stmt->bindParam(':id_usr_crea', $d['id_usr_crea']);
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
        $sql = "UPDATE dash_consultas 
                SET titulo_consulta=:titulo_consulta,detalle_consulta=:detalle_consulta,tipo_bdatos=:tipo_bdatos,
                    consulta_sql=:consulta_sql,consulta_sql_group=:consulta_sql_group,tipo_informe=:tipo_informe,
                    tipo_consulta=:tipo_consulta,tipo_acceso=:tipo_acceso,estado=:estado
                WHERE id_consulta=:id_consulta";
        $stmt = $this->conexion->prepare($sql);
        $stmt->bindParam(':titulo_consulta', $d['titulo_consulta']);
        $stmt->bindParam(':detalle_consulta', $d['detalle_consulta']);
        $stmt->bindParam(':tipo_bdatos', $d['tipo_bdatos']);
        $stmt->bindParam(':consulta_sql', $d['consulta_sql']);
        $stmt->bindParam(':consulta_sql_group', $d['consulta_sql_group']);
        $stmt->bindParam(':tipo_informe', $d['tipo_informe']);
        $stmt->bindParam(':tipo_consulta', $d['tipo_consulta']);
        $stmt->bindParam(':tipo_acceso', $d['tipo_acceso']);
        $stmt->bindParam(':estado', $d['estado']);
        $stmt->bindParam(':id_consulta', $id, PDO::PARAM_INT);
        return $stmt->execute();
    }

    public function delete(int $id)
    {
        $sql = "DELETE FROM dash_consultas WHERE id_consulta = :id_consulta";
        $stmt = $this->conexion->prepare($sql);
        $stmt->bindParam(':id_consulta', $id, PDO::PARAM_INT);
        return $stmt->execute();
    }

    public function add_bd(int $id_consulta, int $id_bdatos)
    {
        $sql = "INSERT INTO dash_consulta_bd(id_consulta, id_bdatos) VALUES(:id_consulta, :id_bdatos)";
        $stmt = $this->conexion->prepare($sql);
        $stmt->bindParam(':id_consulta', $id_consulta, PDO::PARAM_INT);
        $stmt->bindParam(':id_bdatos', $id_bdatos, PDO::PARAM_INT);
        return $stmt->execute();
    }
    public function delete_bd(int $id_consulta, int $id_bdatos)
    {
        $sql = "DELETE FROM dash_consulta_bd WHERE id_consulta = :id_consulta AND id_bdatos = :id_bdatos";
        $stmt = $this->conexion->prepare($sql);
        $stmt->bindParam(':id_consulta', $id_consulta, PDO::PARAM_INT);
        $stmt->bindParam(':id_bdatos', $id_bdatos, PDO::PARAM_INT);
        return $stmt->execute();
    }

    public function search_user(int $id_consulta, int $id_usuario)
    {
        $sql = "SELECT COUNT(*) AS count FROM dash_consulta_usr WHERE id_consulta = :id_consulta AND id_usuario = :id_usuario";
        $stmt = $this->conexion->prepare($sql);
        $stmt->bindParam(':id_consulta', $id_consulta, PDO::PARAM_INT);
        $stmt->bindParam(':id_usuario', $id_usuario, PDO::PARAM_INT);
        $stmt->execute();
        $count = (int) $stmt->fetchColumn();
        return ($count > 0) ? 1 : 0;         
    }
    public function add_user(int $id_consulta, int $id_usuario) 
    {
        $sql = "INSERT INTO dash_consulta_usr(id_consulta, id_usuario) VALUES(:id_consulta, :id_usuario)";
        $stmt = $this->conexion->prepare($sql);
        $stmt->bindParam(':id_consulta', $id_consulta, PDO::PARAM_INT);
        $stmt->bindParam(':id_usuario', $id_usuario, PDO::PARAM_INT);
        return $stmt->execute();
    }
    public function delete_user(int $id_consulta, int $id_usuario)
    {
        $sql = "DELETE FROM dash_consulta_usr WHERE id_consulta = :id_consulta AND id_usuario = :id_usuario";
        $stmt = $this->conexion->prepare($sql);
        $stmt->bindParam(':id_consulta', $id_consulta, PDO::PARAM_INT);
        $stmt->bindParam(':id_usuario', $id_usuario, PDO::PARAM_INT);
        return $stmt->execute();
    }

    public function getParametroById(int $id_parametro)
    {
        $sql = "SELECT * FROM dash_consulta_param WHERE id_parametro = :id_parametro LIMIT 1";
        $stmt = $this->conexion->prepare($sql);
        $stmt->bindParam(':id_parametro', $id_parametro, PDO::PARAM_INT);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($row === false) {
            $columnsStmt = $this->conexion->query("DESCRIBE dash_consulta_param");
            $columns = $columnsStmt->fetchAll(PDO::FETCH_COLUMN);
            $row = array_fill_keys($columns, '');
        }
        return $row;            
    }
    public function insert_parametro(array $d)
    {
        $sql = "INSERT INTO dash_consulta_param(
                    id_consulta,parametro,etiqueta,descripcion,tipo,detalles
                ) VALUES(
                    :id_consulta,:parametro,:etiqueta,:descripcion,:tipo,:detalles
                )";
        $stmt = $this->conexion->prepare($sql);
        $stmt->bindParam(':id_consulta', $d['id_consulta']);
        $stmt->bindParam(':parametro', $d['parametro']);
        $stmt->bindParam(':etiqueta', $d['etiqueta']);
        $stmt->bindParam(':descripcion', $d['descripcion']);
        $stmt->bindParam(':tipo', $d['tipo']);
        $stmt->bindParam(':detalles', $d['detalles']);
        $ok = $stmt->execute();
        if ($ok) {
            $rs = $this->conexion->query('SELECT LAST_INSERT_ID() AS id');
            $obj = $rs->fetch(PDO::FETCH_ASSOC);
            return $obj['id'] ?? null;
        }
        return false;
    }

    public function update_parametro(int $id_parametro, array $d)
    {
        $sql = "UPDATE dash_consulta_param 
                SET parametro=:parametro,etiqueta=:etiqueta,
                    descripcion=:descripcion,tipo=:tipo,detalles=:detalles
                WHERE id_parametro=:id_parametro";
        $stmt = $this->conexion->prepare($sql);
        $stmt->bindParam(':parametro', $d['parametro']);
        $stmt->bindParam(':etiqueta', $d['etiqueta']);
        $stmt->bindParam(':descripcion', $d['descripcion']);
        $stmt->bindParam(':tipo', $d['tipo']);
        $stmt->bindParam(':detalles', $d['detalles']);
        $stmt->bindParam(':id_parametro', $id_parametro, PDO::PARAM_INT);
        return $stmt->execute();
    }  
    
    public function delete_parametro(int $id_parametro)
    {
        $sql = "DELETE FROM dash_consulta_param WHERE id_parametro = :id_parametro";
        $stmt = $this->conexion->prepare($sql);
        $stmt->bindParam(':id_parametro', $id_parametro, PDO::PARAM_INT);
        return $stmt->execute();
    }
}
