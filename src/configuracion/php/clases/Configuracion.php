<?php

namespace Src\Configuracion\Php\Clases;

use Config\Clases\Conexion;
use Config\Clases\Logs;
use Config\Clases\Sesion;

use Src\Common\Php\Clases\Combos;

use PDO;
use PDOException;
use Exception;

class Configuracion
{
    private $conexion;

    public function __construct()
    {
        $this->conexion = Conexion::getConexion(); // Método estático que retorna el objeto PDO
    }

    public static function _getConfiguracion($id)
    {
        $combos = new Combos();
        $sql = "SELECT `id_consulta`,`nom_consulta` FROM `tb_consultas_sql`
                ORDER BY `id_consulta` ASC";
        return $combos->setConsulta($sql, $id);
    }

    /**
     * Obtiene los datos de los cargos de los empleados.
     *
     * @param int $start Índice de inicio para la paginación
     * @param int $length Número de registros a mostrar
     * @param string $val_busca Valor de búsqueda
     * @param int $col Índice de la columna para ordenar
     * @param string $dir Dirección de ordenamiento (ascendente o descendente) 
     * @return array|[] Retorna un array con los datos
     */
    public function getConfiguracion($start, $length, $val_busca, $col, $dir)
    {
        $where = '';
        if ($val_busca != '') {
            $val_busca = trim($val_busca);
            $where = "AND `tb_consultas_sql`.`nom_consulta` LIKE '%$val_busca%'";
        }

        $limit = "";
        if ($length != -1) {
            $limit = "LIMIT $start, $length";
        }

        $sql = "SELECT
                    `tb_consultas_sql`.`id_consulta`
                    , `tb_consultas_sql`.`nom_consulta`
                    , `tb_consultas_sql`.`des_consulta`                   
                FROM
                    `tb_consultas_sql`                    
                WHERE (`tb_consultas_sql`.`id_consulta` > 0 $where)
                ORDER BY $col $dir $limit";
        $stmt = $this->conexion->prepare($sql);
        $stmt->execute();
        $datos = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $stmt->closeCursor();
        unset($stmt);
        return $datos ?: null;
    }

    /**
     * Obtiene el total de registros filtrados.
     * 
     * @param string $val_busca Valor de búsqueda
     * @return int Total de registros filtrados
     */
    public function getRegistrosFilter($val_busca)
    {
        $where = '';
        if ($val_busca != '') {
            $val_busca = trim($val_busca);
            $where = "AND `tb_consultas_sql`.`nom_consulta` LIKE '%$val_busca%'";
        }

        $sql = "SELECT
                    COUNT(*) as total                 
                FROM
                    `tb_consultas_sql`                    
                WHERE (`tb_consultas_sql`.`id_consulta` > 0 $where)";
        $stmt = $this->conexion->prepare($sql);
        $stmt->execute();
        $id = $stmt->fetch(PDO::FETCH_ASSOC)['total'] ?: 0;
        $stmt->closeCursor();
        unset($stmt);
        return $id;
    }

    /**
     * Obtiene el total de registros.
     * @return int Total de registros
     */

    public function getRegistrosTotal()
    {
        $sql = "SELECT
                    COUNT(*) as total                 
                FROM
                    `tb_consultas_sql`                    
                WHERE (`tb_consultas_sql`.`id_consulta` > 0)";
        $stmt = $this->conexion->prepare($sql);
        $stmt->execute();
        $id = $stmt->fetch(PDO::FETCH_ASSOC)['total'] ?: 0;
        $stmt->closeCursor();
        unset($stmt);
        return $id;
    }

    public function getRegistro($id)
    {
        $sql = "SELECT
                    `tb_consultas_sql`.`id_consulta`
                    , `tb_consultas_sql`.`nom_consulta`
                    , `tb_consultas_sql`.`des_consulta`                   
                FROM
                    `tb_consultas_sql`                    
                WHERE (`tb_consultas_sql`.`id_consulta` = $id)";
        $stmt = $this->conexion->prepare($sql);
        $stmt->bindParam(1, $id, PDO::PARAM_INT);
        $stmt->execute();
        $cargo = $stmt->fetch(PDO::FETCH_ASSOC);
        $stmt->closeCursor();
        unset($stmt);
        if (empty($cargo)) {
            $cargo = [
                'id_consulta' => 0,
                'nom_consulta' => '',
                'des_consulta' => '',
            ];
        }
        return $cargo;
    }

    public function getCodigos()
    {
        $sql = "SELECT
                    `id_consulta`
                FROM
                    `tb_consultas_sql` ORDER BY `id_consulta` ASC";
        $stmt = $this->conexion->prepare($sql);
        $stmt->execute();
        $registros =  $stmt->fetchAll(PDO::FETCH_ASSOC);
        $stmt->closeCursor();
        unset($stmt);
        return $registros;
    }

    public function getFormulario($id)
    {
        $fila = $this->getRegistro($id);
        $html =
            <<<HTML
                <div>
                    <div class="shadow text-center rounded">
                        <div class="rounded-top py-2" style="background-color: #16a085 !important;">
                            <h5 style="color: white;" class="mb-0">CONSULTA SQL</h5>
                        </div>
                        <div class="p-3">
                            <form id="formGestCargoNom">
                                <input type="hidden" id="id" name="id" value="{$fila['id_consulta']}">
                                <div class="row mb-2">
                                    <div class="col-md-6">
                                        <label for="txt_nom_consulta" class="small">NOMBRE</label>
                                        <input type="text" id="txt_nom_consulta" name="txt_nom_consulta" class="form-control form-control-sm bg-input" value="{$fila['nom_consulta']}">
                                    </div>
                                    <div class="col-md-6">
                                        <label for="txt_des_consulta" class="small">DESCRIPCION</label>
                                        <input type="text" id="txt_des_consulta" name="txt_des_consulta" class="form-control form-control-sm bg-input" value="{$fila['des_consulta']}">
                                    </div>
                                </div>
                            </form>
                        </div>
                        <div class="text-end pb-3">
                            <button type="button" class="btn btn-primary btn-sm" id="btnGuardaConfiguracion">Guardar</button>
                            <a type="button" class="btn btn-secondary  btn-sm" data-bs-dismiss="modal">Cancelar</a>
                        </div>
                    </div>
                </div>
            HTML;
        return $html;
    }

    public function delCargo($id)
    {
        try {
            $sql = "DELETE FROM `nom_cargo_empleado` WHERE `id_cargo` = ?";
            $consulta  = "DELETE FROM `nom_cargo_empleado` WHERE `id_cargo` = $id";
            $stmt = $this->conexion->prepare($sql);
            $stmt->bindParam(1, $id, PDO::PARAM_INT);
            $stmt->execute();
            if ($stmt->rowCount() > 0) {
                Logs::guardaLog($consulta);
                return 'si';
            } else {
                return 'No se eliminó el registro.';
            }
        } catch (PDOException $e) {
            return 'Error SQL: ' . $e->getMessage();
        }
    }

    public function addCargo($array)
    {
        try {
            $sql = "INSERT INTO `nom_cargo_empleado`
                        (`codigo`,`descripcion_carg`,`grado`,`perfil_siho`,`id_nombramiento`,`id_user_reg`,`fec_reg`)
                    VALUES (?, ?, ?, ?, ?, ?, ?)";
            $stmt = $this->conexion->prepare($sql);
            $stmt->bindValue(1, $array['slcCodigo'] ?? NULL, PDO::PARAM_INT);
            $stmt->bindValue(2, $array['txtNomCargo'], PDO::PARAM_STR);
            $stmt->bindValue(3, $array['numGrado'] ?? NULL, PDO::PARAM_INT);
            $stmt->bindValue(4, $array['txtPerfilSiho'] ?? NULL, PDO::PARAM_STR);
            $stmt->bindValue(5, $array['slcNombramiento'] ?? NULL, PDO::PARAM_INT);
            $stmt->bindValue(6, Sesion::IdUser(), PDO::PARAM_INT);
            $stmt->bindValue(7, Sesion::Hoy(), PDO::PARAM_STR);
            $stmt->execute();
            $id = $this->conexion->lastInsertId();
            return $id > 0 ? 'si' : 'No se insertó';
        } catch (PDOException $e) {
            return 'Error SQL: ' . $e->getMessage();
        }
    }

    public function editCargo($array)
    {
        try {
            $sql = "UPDATE `nom_cargo_empleado` 
                        SET `codigo` = ?, `descripcion_carg` = ?, `grado` = ?, `perfil_siho` = ?, `id_nombramiento` = ?
                    WHERE (`id_cargo` = ?)";
            $stmt = $this->conexion->prepare($sql);
            $stmt->bindValue(1, $array['slcCodigo'] ?? NULL, PDO::PARAM_INT);
            $stmt->bindValue(2, $array['txtNomCargo'], PDO::PARAM_STR);
            $stmt->bindValue(3, $array['numGrado'] ?? NULL, PDO::PARAM_INT);
            $stmt->bindValue(4, $array['txtPerfilSiho'] ?? NULL, PDO::PARAM_STR);
            $stmt->bindValue(5, $array['slcNombramiento'] ?? NULL, PDO::PARAM_INT);
            $stmt->bindValue(6, $array['id'], PDO::PARAM_INT);
            if (!($stmt->execute())) {
                return 'Errado: ' . $stmt->errorInfo()[2];
            } else {
                if ($stmt->rowCount() > 0) {
                    $consulta = "UPDATE `nom_cargo_empleado` SET `id_user_act` = ?, `fec_act` = ? WHERE (`id_cargo` = ?)";
                    $stmt2 = $this->conexion->prepare($consulta);
                    $stmt2->bindValue(1, Sesion::IdUser(), PDO::PARAM_INT);
                    $stmt2->bindValue(2, Sesion::Hoy(), PDO::PARAM_STR);
                    $stmt2->bindValue(3, $array['id'], PDO::PARAM_INT);
                    $stmt2->execute();
                    return 'si';
                } else {
                    return 'No se realizó ningún cambio.';
                }
            }
        } catch (PDOException $e) {
            return 'Error SQL: ' . $e->getMessage();
        }
    }
}
