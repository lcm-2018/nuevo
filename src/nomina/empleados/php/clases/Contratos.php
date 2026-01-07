<?php

namespace Src\Nomina\Empleados\Php\Clases;

use Config\Clases\Conexion;
use Config\Clases\Logs;
use Config\Clases\Sesion;

use Src\Common\Php\Clases\Valores;

use PDO;
use PDOException;
use Src\Nomina\Configuracion\Php\Clases\Cargos;

class Contratos
{
    private $conexion;

    public function __construct()
    {
        $this->conexion = Conexion::getConexion(); // Método estático que retorna el objeto PDO
    }

    /**
     * Obtiene los datos de los contratos para la DataTabl.
     *
     * @param int $start Índice de inicio para la paginación
     * @param int $length Número de registros a mostrar
     * @param string $buscar Valor de búsqueda
     * @param int $col Índice de la columna para ordenar
     * @param string $dir Dirección de ordenamiento (ascendente o descendente) 
     * @return array|[] Retorna un array con los datos
     */
    public function getContratosEmpleado($start, $length, $buscar, $col, $dir, $id_empleado)
    {
        $limit = "";
        if ($length != -1) {
            $limit = "LIMIT $start, $length";
        }

        $where = '';
        if ($buscar != '') {
            $buscar = trim($buscar);
            $where .= " AND (`nom_contratos_empleados`.`fec_inicio` LIKE '%$buscar%' OR `nom_contratos_empleados`.`fec_fin` LIKE '%$buscar%' OR `nom_salarios_basico`.`salario_basico` LIKE '%$buscar%')";
        }

        $sql = "SELECT
                    `nom_contratos_empleados`.`fec_inicio`
                    , `nom_contratos_empleados`.`fec_fin`
                    , `nom_contratos_empleados`.`vigencia`
                    , `nom_contratos_empleados`.`estado`
                    , `nom_salarios_basico`.`id_salario`
                    , `nom_salarios_basico`.`salario_basico`
                    , `nom_contratos_empleados`.`id_contrato_emp`
                FROM
                    `nom_salarios_basico`
                    INNER JOIN `nom_contratos_empleados` 
                        ON (`nom_salarios_basico`.`id_contrato` = `nom_contratos_empleados`.`id_contrato_emp`)
                WHERE (`nom_contratos_empleados`.`id_empleado` = $id_empleado $where)
                ORDER BY $col $dir $limit";
        $stmt = $this->conexion->prepare($sql);
        $stmt->execute();
        $datos = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $stmt->closeCursor();
        unset($stmt);
        return $datos ?: [];
    }

    public function getContratoEmpleados($ids)
    {
        $ids = implode(',', $ids);
        $sql = "SELECT
                    `nom_contratos_empleados`.`fec_inicio`
                    , `nom_contratos_empleados`.`fec_fin`
                    , `nom_contratos_empleados`.`vigencia`
                    , `nom_contratos_empleados`.`estado`
                    , `nom_salarios_basico`.`id_salario`
                    , `nom_salarios_basico`.`salario_basico`
                    , `nom_contratos_empleados`.`id_contrato_emp`
                FROM
                    `nom_salarios_basico`
                    INNER JOIN `nom_contratos_empleados` 
                        ON (`nom_salarios_basico`.`id_contrato` = `nom_contratos_empleados`.`id_contrato_emp`)
                WHERE (`nom_contratos_empleados`.`id_empleado` IN (?))";
        $stmt = $this->conexion->prepare($sql);
        $stmt->bindParam(1, $ids, PDO::PARAM_STR);
        $stmt->execute();
        $datos = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $stmt->closeCursor();
        unset($stmt);
        return $datos ?: [];
    }
    /**
     * Obtiene el total de registros filtrados.
     * 
     * @param string $val_busca Valor de búsqueda
     * @return int Total de registros filtrados
     */
    public function getRegistrosFilter($buscar, $id_empleado)
    {
        $where = '';
        if ($buscar != '') {
            $buscar = trim($buscar);
            $where .= " AND (`nom_contratos_empleados`.`fec_inicio` LIKE '%$buscar%' OR `nom_contratos_empleados`.`fec_fin` LIKE '%$buscar%' OR `nom_salarios_basico`.`salario_basico` LIKE '%$buscar%')";
        }

        $sql = "SELECT 
                    COUNT(*) AS `total`
                FROM
                    `nom_salarios_basico`
                    INNER JOIN `nom_contratos_empleados` 
                        ON (`nom_salarios_basico`.`id_contrato` = `nom_contratos_empleados`.`id_contrato_emp`)
                WHERE (`nom_contratos_empleados`.`id_empleado` = $id_empleado $where)";
        $stmt = $this->conexion->prepare($sql);
        $stmt->execute();
        $data = $stmt->fetch(PDO::FETCH_ASSOC)['total'] ?: 0;
        $stmt->closeCursor();
        unset($stmt);
        return $data;
    }

    /**
     * Obtiene el total de registros.
     * @return int Total de registros
     */

    public function getRegistrosTotal($id_empleado)
    {
        $sql = "SELECT 
                    COUNT(*) AS `total`
                FROM
                    `nom_salarios_basico`
                    INNER JOIN `nom_contratos_empleados` 
                        ON (`nom_salarios_basico`.`id_contrato` = `nom_contratos_empleados`.`id_contrato_emp`)
                WHERE (`nom_contratos_empleados`.`id_empleado` = $id_empleado)";
        $stmt = $this->conexion->prepare($sql);
        $stmt->execute();
        $data = $stmt->fetch(PDO::FETCH_ASSOC)['total'] ?: 0;
        $stmt->closeCursor();
        unset($stmt);
        return $data;
    }

    public function getRegistro($id)
    {
        $sql = "SELECT
                    `nom_contratos_empleados`.`fec_inicio`
                    , `nom_contratos_empleados`.`fec_fin`
                    , `nom_contratos_empleados`.`vigencia`
                    , `nom_contratos_empleados`.`estado`
                    , `nom_salarios_basico`.`id_salario`
                    , `nom_salarios_basico`.`salario_basico`
                    , `nom_contratos_empleados`.`id_contrato_emp`
                    , `nom_contratos_empleados`.`id_cargo`
                FROM
                    `nom_salarios_basico`
                    INNER JOIN `nom_contratos_empleados` 
                        ON (`nom_salarios_basico`.`id_contrato` = `nom_contratos_empleados`.`id_contrato_emp`)
                WHERE (`nom_salarios_basico`.`id_salario` = ?)";
        $stmt = $this->conexion->prepare($sql);
        $stmt->bindParam(1, $id, PDO::PARAM_INT);
        $stmt->execute();
        $contrato = $stmt->fetch(PDO::FETCH_ASSOC);
        $stmt->closeCursor();
        unset($stmt);
        if (empty($contrato)) {
            $contrato = [
                'fec_inicio' => date('Y-m-d'),
                'fec_fin' => '',
                'vigencia' => '',
                'estado' => 0,
                'id_salario' => 0,
                'salario_basico' => 0,
                'id_contrato_emp' => 0
            ];
        }
        return $contrato;
    }


    /**
     * Obtiene el formulario para agregar o editar un contrato.
     *
     * @param int $id ID del contrato (0 para nuevo)
     * @return string HTML del formulario
     */
    public function getFormulario($id)
    {
        $contrato   =   $this->getRegistro($id);
        $salario    =   str_replace('.', ',', $contrato['salario_basico']);
        $op_cargo   =   Cargos::_getCargos($contrato['id_cargo'] ?? 0);
        $html =
            <<<HTML
                <div class="shadow text-center rounded">
                    <div class="rounded-top py-2" style="background-color: #16a085 !important;">
                        <h5 style="color: white;" class="mb-0">GESTIÓN CONTRATOS EMPLEADOS</h5>
                    </div>
                    <div class="p-3">
                        <form id="formContratoEmpleado">
                            <input type="hidden" id="id" name="id" value="{$id}">
                            <input type="hidden" id="id_contrato_emp" name="id_contrato_emp" value="{$contrato['id_contrato_emp']}">
                            <div class="row mb-2">
                                <div class="col-md-6">
                                    <label for="datFecInicia" class="small text-muted">Inicia</label>
                                    <input type="date" class="form-control form-control-sm bg-input" id="datFecInicia" name="datFecInicia" value="{$contrato['fec_inicio']}">
                                </div>
                                <div class="col-md-6">
                                    <label for="datFecFin" class="small text-muted">Fin</label>
                                    <input type="date" class="form-control form-control-sm bg-input" id="datFecFin" name="datFecFin" value="{$contrato['fec_fin']}">
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6">
                                    <label for="txtSalarioBasico" class="small text-muted">Salario Básico</label>
                                    <input type="text" class="form-control form-control-sm bg-input text-end" id="txtSalarioBasico" name="txtSalarioBasico" value="{$contrato['salario_basico']}" placeholder="Salario Básico" oninput="NumberMiles(this)">
                                </div>
                                <div class="col-md-6">
                                    <label for="slcCargo" class="small text-muted">Cargo</label>
                                    <select class="form-select form-select-sm bg-input" id="slcCargo" name="slcCargo">
                                        $op_cargo
                                    </select>
                                </div>
                            </div>
                        </form>
                    </div>
                    <div class="text-center pb-3">
                        <button type="button" class="btn btn-primary btn-sm" id="btnGuardaContratoEmpleado">Guardar</button>
                        <a type="button" class="btn btn-secondary  btn-sm" data-bs-dismiss="modal">Cancelar</a>
                    </div>
                </div>
            HTML;
        return $html;
    }

    /**
     * Elimina un contrato.
     *
     * @param int $id ID del contrato a eliminar
     * @return string Mensaje de éxito o error
     */

    public function delContrato($id)
    {
        try {
            $sql = "DELETE FROM `nom_contratos_empleados` WHERE `id_contrato_emp` = ?";
            $consulta  = "DELETE FROM `nom_contratos_empleados` WHERE `id_contrato_emp` = $id";
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

    /**
     * Agrega un nuevo contrato.
     *
     * @param array $array Datos del contrato a agregar
     * @return string Mensaje de éxito o error
     */
    public function addContrato($array)
    {
        try {
            $sql = "INSERT INTO `nom_contratos_empleados`
                        (`id_empleado`,`fec_inicio`,`fec_fin`,`vigencia`,`id_cargo`,`estado`,`id_user_reg`,`fec_reg`)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
            $stmt = $this->conexion->prepare($sql);
            $stmt->bindValue(1, $array['id_empleado'], PDO::PARAM_INT);
            $stmt->bindValue(2, $array['datFecInicia'], PDO::PARAM_STR);
            $stmt->bindValue(3, $array['datFecFin'] != '' ? $array['datFecFin'] : NULL, PDO::PARAM_STR);
            $stmt->bindValue(4, Sesion::Vigencia(), PDO::PARAM_INT);
            $stmt->bindValue(5, $array['slcCargo'], PDO::PARAM_INT);
            $stmt->bindValue(6, 1, PDO::PARAM_INT);
            $stmt->bindValue(7, Sesion::IdUser(), PDO::PARAM_INT);
            $stmt->bindValue(8, Sesion::Hoy(), PDO::PARAM_STR);
            $stmt->execute();
            $id = $this->conexion->lastInsertId();
            if ($id > 0) {
                $sql = "INSERT INTO `nom_salarios_basico`
                            (`id_empleado`,`id_contrato`,`vigencia`,`salario_basico`,`fec_reg`)
                        VALUES (?, ?, ?, ?, ?)";
                $stmt = $this->conexion->prepare($sql);
                $stmt->bindValue(1, $array['id_empleado'], PDO::PARAM_INT);
                $stmt->bindValue(2, $id, PDO::PARAM_INT);
                $stmt->bindValue(3, Sesion::Vigencia(), PDO::PARAM_INT);
                $stmt->bindValue(4, Valores::WordToNumber($array['txtSalarioBasico']), PDO::PARAM_STR);
                $stmt->bindValue(5, Sesion::Hoy(), PDO::PARAM_STR);
                $stmt->execute();
                return $this->conexion->lastInsertId() > 0 ? 'si' : 'No se insertó el registro';
            } else {
                return 'No se insertó el registro';
            }
        } catch (PDOException $e) {
            return 'Error SQL: ' . $e->getMessage();
        }
    }
    /**
     * Actualiza los datos de un contrato.
     *
     * @param array $array Datos del contrato a actualizar
     * @return string Mensaje de éxito o error
     */
    public function editContrato($array)
    {
        $cambio = 0;
        try {
            $sql = "UPDATE `nom_contratos_empleados`
                        SET `fec_inicio` = ?, `fec_fin` = ?, `id_cargo` = ?
                    WHERE (`id_contrato_emp` = ?)";
            $stmt = $this->conexion->prepare($sql);
            $stmt->bindValue(1, $array['datFecInicia'], PDO::PARAM_STR);
            $stmt->bindValue(2, $array['datFecFin'] != '' ? $array['datFecFin'] : NULL, PDO::PARAM_STR);
            $stmt->bindValue(3, $array['slcCargo'], PDO::PARAM_INT);
            $stmt->bindValue(4, $array['id_contrato_emp'], PDO::PARAM_INT);
            if (!($stmt->execute())) {
                return 'Errado: ' . $stmt->errorInfo()[2];
            } else {
                if ($stmt->rowCount() > 0) {
                    $consulta = "UPDATE `nom_contratos_empleados` SET `id_user_act` = ?, `fec_act` = ? WHERE (`id_contrato_emp` = ?)";
                    $stmt2 = $this->conexion->prepare($consulta);
                    $stmt2->bindValue(1, Sesion::IdUser(), PDO::PARAM_INT);
                    $stmt2->bindValue(2, Sesion::Hoy(), PDO::PARAM_STR);
                    $stmt2->bindValue(3, $array['id_contrato_emp'], PDO::PARAM_INT);
                    $stmt2->execute();
                    $cambio++;
                }
            }
            $sql = "UPDATE `nom_salarios_basico`
                        SET `salario_basico` = ?
                    WHERE (`id_salario` = ?)";
            $stmt = $this->conexion->prepare($sql);
            $stmt->bindValue(1, Valores::WordToNumber($array['txtSalarioBasico']), PDO::PARAM_STR);
            $stmt->bindValue(2, $array['id'], PDO::PARAM_INT);
            if (!($stmt->execute())) {
                return 'Errado: ' . $stmt->errorInfo()[2];
            } else {
                if ($stmt->rowCount() > 0) {
                    $consulta = "UPDATE `nom_salarios_basico` SET `fec_act` = ? WHERE (`id_salario` = ?)";
                    $stmt2 = $this->conexion->prepare($consulta);
                    $stmt2->bindValue(1, Sesion::Hoy(), PDO::PARAM_STR);
                    $stmt2->bindValue(2, $array['id'], PDO::PARAM_INT);
                    $stmt2->execute();
                    $cambio++;
                }
            }
            if ($cambio > 0) {
                return 'si';
            } else {
                return 'No se actualizó el registro.';
            }
        } catch (PDOException $e) {
            return 'Error SQL: ' . $e->getMessage();
        }
    }

    public function annulContrato($array)
    {
        return 'Falta programar la anulación de contrato.';
    }
}
