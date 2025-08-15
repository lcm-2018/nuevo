<?php

namespace Src\Nomina\Configuracion\Php\Clases;

use Config\Clases\Conexion;
use Config\Clases\Logs;
use Config\Clases\Sesion;

use Src\Common\Php\Clases\Combos;

use PDO;
use PDOException;
use Exception;

class Cargos
{
    private $conexion;

    public function __construct()
    {
        $this->conexion = Conexion::getConexion(); // Método estático que retorna el objeto PDO
    }

    public static function _getCargos($id)
    {
        $combos = new Combos();
        $sql = "SELECT `id_cargo`,CONCAT(`descripcion_carg`,' - ',`grado`) FROM `nom_cargo_empleado`
                ORDER BY `descripcion_carg`,`grado` ASC";
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
    public function getCargos($start, $length, $val_busca, $col, $dir)
    {
        $where = '';
        if ($val_busca != '') {
            $val_busca = trim($val_busca);
            $where = "AND `nom_cargo_empleado`.`codigo` LIKE '%$val_busca%' OR `nom_cargo_empleado`.`descripcion_carg` LIKE '%$val_busca%' OR `nom_cargo_empleado`.`grado` LIKE '%$val_busca%' OR `nom_cargo_empleado`.`perfil_siho` LIKE '%$val_busca%' OR `nom_cargo_nombramiento`.`tipo` LIKE '%$val_busca%'";
        }

        $limit = "";
        if ($length != -1) {
            $limit = "LIMIT $start, $length";
        }

        $sql = "SELECT
                    `nom_cargo_empleado`.`id_cargo`
                    , `nom_cargo_empleado`.`codigo` AS `id_codigo`
                    , `nom_cargo_empleado`.`descripcion_carg`
                    , `nom_cargo_empleado`.`grado`
                    , `nom_cargo_empleado`.`perfil_siho`
                    , `nom_cargo_nombramiento`.`tipo`
                    , `nom_cargo_codigo`.`codigo`
                    , `nom_cargo_empleado`.`id_nombramiento`
                FROM
                    `nom_cargo_empleado`
                    LEFT JOIN `nom_cargo_codigo` 
                        ON (`nom_cargo_empleado`.`codigo` = `nom_cargo_codigo`.`id_cod`)
                    LEFT JOIN `nom_cargo_nombramiento` 
                        ON (`nom_cargo_empleado`.`id_nombramiento` = `nom_cargo_nombramiento`.`id`)
                WHERE (`nom_cargo_empleado`.`id_cargo` > 0 $where)
                ORDER BY $col $dir $limit";
        $stmt = $this->conexion->prepare($sql);
        $stmt->execute();
        $datos = $stmt->fetchAll(PDO::FETCH_ASSOC);
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
            $where = "AND `nom_cargo_empleado`.`codigo` LIKE '%$val_busca%' OR `nom_cargo_empleado`.`descripcion_carg` LIKE '%$val_busca%' OR `nom_cargo_empleado`.`grado` LIKE '%$val_busca%' OR `nom_cargo_empleado`.`perfil_siho` LIKE '%$val_busca%' OR `nom_cargo_nombramiento`.`tipo` LIKE '%$val_busca%'";
        }

        $sql = "SELECT
                    COUNT(*) AS `total`
                FROM
                    `nom_cargo_empleado`
                    LEFT JOIN `nom_cargo_codigo` 
                        ON (`nom_cargo_empleado`.`codigo` = `nom_cargo_codigo`.`id_cod`)
                    LEFT JOIN `nom_cargo_nombramiento` 
                        ON (`nom_cargo_empleado`.`id_nombramiento` = `nom_cargo_nombramiento`.`id`)
                WHERE (`nom_cargo_empleado`.`id_cargo` > 0 $where)";
        $stmt = $this->conexion->prepare($sql);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC)['total'] ?: 0;
    }

    /**
     * Obtiene el total de registros.
     * @return int Total de registros
     */

    public function getRegistrosTotal()
    {
        $sql = "SELECT
                    COUNT(*) AS `total`
                FROM
                    `nom_cargo_empleado`
                    LEFT JOIN `nom_cargo_codigo` 
                        ON (`nom_cargo_empleado`.`codigo` = `nom_cargo_codigo`.`id_cod`)
                    LEFT JOIN `nom_cargo_nombramiento` 
                        ON (`nom_cargo_empleado`.`id_nombramiento` = `nom_cargo_nombramiento`.`id`)";
        $stmt = $this->conexion->prepare($sql);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC)['total'] ?: 0;
    }

    public function getRegistro($id)
    {
        $sql = "SELECT
                `id_cargo`, `codigo`, `descripcion_carg`, `grado`, `perfil_siho`, `id_nombramiento`
            FROM
                `nom_cargo_empleado`
            WHERE (`id_cargo` = $id)";
        $stmt = $this->conexion->prepare($sql);
        $stmt->bindParam(1, $id, PDO::PARAM_INT);
        $stmt->execute();
        $cargo = $stmt->fetch(PDO::FETCH_ASSOC);
        if (empty($cargo)) {
            $cargo = [
                'id_cargo' => 0,
                'codigo' => 0,
                'descripcion_carg' => '',
                'grado' => '',
                'perfil_siho' => '',
                'id_nombramiento' => 0
            ];
        }
        return $cargo;
    }

    public function getCodigos()
    {
        $sql = "SELECT
                    `id_cod`, `denominacion`, `nivel`
                FROM
                    `nom_cargo_codigo` ORDER BY `nivel`,`denominacion` ASC";
        $stmt = $this->conexion->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public function getNombramientos($id)
    {
        $sql = "SELECT
                    `id`, `tipo`
                FROM
                    `nom_cargo_nombramiento`; ORDER BY `tipo` ASC";
        $stmt = $this->conexion->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public function getFormulario($id)
    {
        $fila = $this->getRegistro($id);
        $codigos = $this->getCodigos();
        $nombramientos = $this->getNombramientos($id);
        $caracter = Sesion::Caracter();

        if ($caracter == '2') {
            $tam = '4';
            $opt_cods = '';
            foreach ($codigos as $c) {
                $slc = $fila['codigo'] == $c['id_cod'] ? 'selected' : '';
                $opt_cods .= '<option value="' . $c['id_cod'] . '" ' . $slc . '>' . mb_strtoupper($c['nivel'] . ' -> ' . $c['denominacion']) . '</option>';
            }
            $opt_noms = '';
            foreach ($nombramientos as $n) {
                $slc = $fila['id_nombramiento'] == $n['id'] ? 'selected' : '';
                $opt_noms .= '<option value="' . $n['id'] . '" ' . $slc . '>' . mb_strtoupper($n['tipo']) . '</option>';
            }

            $linea1 =
                <<<HTML
                <div class="col-md-4">
                    <label for="slcCodigo" class="small">CÓDIGO</label>
                    <select name="slcCodigo" id="slcCodigo" class="form-control form-control-sm bg-input">
                        <option value="0">--Seleccione--</option>
                        {$opt_cods}
                    </select>
                </div>
                <div class="col-md-4">
                    <label for="numGrado" class="small">GRADO</label>
                    <input type="number" id="numGrado" name="numGrado" class="form-control form-control-sm bg-input" value="{$fila['grado']}">
                </div>
            HTML;
            $linea2 =
                <<<HTML
                <div class="row">
                    <div class="col-md-6">
                        <label for="slcNombramiento" class="small">NOMBRAMIENTO</label>
                        <select name="slcNombramiento" id="slcNombramiento" class="form-control form-control-sm bg-input">
                            <option value="0">--Seleccione--</option>
                            {$opt_noms}
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label for="txtPerfilSiho" class="small">PERFÍL SIHO</label>
                        <input type="text" id="txtPerfilSiho" name="txtPerfilSiho" class="form-control form-control-sm bg-input" value="{$fila['perfil_siho']}">
                    </div>
                </div>
            HTML;
        } else {
            $tam = '12';
            $linea1 = '';
            $linea2 = '';
        }
        $html =
            <<<HTML
                <div>
                    <div class="shadow text-center rounded">
                        <div class="rounded-top py-2" style="background-color: #16a085 !important;">
                            <h5 style="color: white;" class="mb-0">CARGO DE NÓMINA</h5>
                        </div>
                        <div class="p-3">
                            <form id="formGestCargoNom">
                                <input type="hidden" id="id" name="id" value="{$fila['id_cargo']}">
                                <div class="row mb-2">
                                    <div class="col-md-{$tam}">
                                        <label for="txtNomCargo" class="small">NOMBRE</label>
                                        <input type="text" id="txtNomCargo" name="txtNomCargo" class="form-control form-control-sm bg-input" value="{$fila['descripcion_carg']}">
                                    </div>
                                    {$linea1}
                                </div>
                                {$linea2}
                            </form>
                        </div>
                        <div class="text-right pb-3">
                            <button type="button" class="btn btn-primary btn-sm" id="btnGuardaCargo">Guardar</button>
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
