<?php

namespace Src\Nomina\Configuracion\Php\Clases;

use Config\Clases\Conexion;
use Config\Clases\Sesion;
use Config\Clases\Logs;
use Src\Nomina\Configuracion\Php\Clases\Parametros;
use Src\Nomina\Empleados\Php\Clases\Empleados;

use PDO;
use PDOException;

class Incrementos
{
    private $conexion;

    public function __construct()
    {
        $this->conexion = Conexion::getConexion(); // Método estático que retorna el objeto PDO
    }

    /**
     * Obtiene los datos de los terceros de nómina.
     *
     * @param int $start Índice de inicio para la paginación
     * @param int $length Número de registros a mostrar
     * @param string $val_busca Valor de búsqueda
     * @param int $col Índice de la columna para ordenar
     * @param string $dir Dirección de ordenamiento (ascendente o descendente) 
     * @return array|[] Retorna un array con los datos
     */
    public function getIncrementos($start, $length, $val_busca, $col, $dir)
    {
        $vigencia = Sesion::Vigencia();
        $where = '';
        if ($val_busca != '') {
            $val_busca = trim($val_busca);
            $where = "AND (`nom_incremento_salario`.`porcentaje` LIKE '%$val_busca%' OR `nom_incremento_salario`.`fecha` LIKE '%$val_busca%')";
        }

        $limit = "";
        if ($length != -1) {
            $limit = "LIMIT $start, $length";
        }

        $sql = "SELECT
                    `id_inc`,`porcentaje`,`vigencia`,`fecha`,`estado`
                FROM `nom_incremento_salario`
                WHERE (`vigencia` = '$vigencia' $where)
                ORDER BY $col $dir $limit";
        $stmt = $this->conexion->prepare($sql);
        $stmt->execute();
        $datos = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        $stmt->closeCursor();
        unset($stmt);
        return $datos;
    }

    /**
     * Obtiene el total de registros filtrados.
     * 
     * @param string $val_busca Valor de búsqueda
     * @return int Total de registros filtrados
     */
    public function getRegistrosFilter($val_busca)
    {
        $vigencia = Sesion::Vigencia();
        $where = '';
        if ($val_busca != '') {
            $val_busca = trim($val_busca);
            $where = "AND (`nom_incremento_salario`.`porcentaje` LIKE '%$val_busca%' OR `nom_incremento_salario`.`fecha` LIKE '%$val_busca%')";
        }

        $sql = "SELECT
                   COUNT(*) AS `total`
                FROM `nom_incremento_salario`
                WHERE (`vigencia` = '$vigencia' $where)";
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
                FROM `nom_incremento_salario`";
        $stmt = $this->conexion->prepare($sql);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC)['total'] ?: 0;
    }

    public function getFormulario($id)
    {
        $fila = $this->getRegistro($id);
        $html =
            <<<HTML
                <div>
                    <div class="shadow text-center rounded">
                        <div class="rounded-top py-2" style="background-color: #16a085 !important;">
                            <h5 style="color: white;" class="mb-0">GUARDAR INCREMENTO SALARIAL</h5>
                        </div>
                        <div class="p-3">
                            <form id="formGestIncSalarial">
                                <input type="hidden" id="id" name="id" value="{$fila['id_inc']}">
                                <div class="row mb-2">
                                    <div class="col-md-6">
                                        <label for="numPorcIncSal" class="small">PORCENTAJE</label>
                                        <input type="number" id="numPorcIncSal" name="numPorcIncSal" class="form-control form-control-sm bg-input text-end" value="{$fila['porcentaje']}">
                                    </div>
                                    <div class="col-md-6">
                                        <label for="datFechaInSal" class="small">FECHA</label>
                                        <input type="date" id="datFechaInSal" name="datFechaInSal" class="form-control form-control-sm bg-input text-end" value="{$fila['fecha']}">
                                    </div>
                                </div>
                            </form>
                        </div>
                        <div class="text-right pb-3">
                            <button type="button" class="btn btn-primary btn-sm" id="btnGuardaIncSalarial">Guardar</button>
                            <a type="button" class="btn btn-secondary  btn-sm" data-bs-dismiss="modal">Cancelar</a>
                        </div>
                    </div>
                </div>
            HTML;
        return $html;
    }

    public function getRegistro($id)
    {
        $sql = "SELECT `id_inc`,`porcentaje`,`vigencia`,`fecha`,`estado` FROM `nom_incremento_salario` WHERE `id_inc` = ?";
        $stmt = $this->conexion->prepare($sql);
        $stmt->bindParam(1, $id, PDO::PARAM_INT);
        $stmt->execute();
        $data = $stmt->fetch(PDO::FETCH_ASSOC);
        if (empty($data)) {
            $data =
                [
                    'id_inc' => 0,
                    'porcentaje' => 0,
                    'vigencia' => Sesion::Vigencia(),
                    'fecha' => Sesion::_Hoy(),
                    'estado' => 1,
                ];
        }
        return $data;
    }
    public function delIncSalarial($id)
    {
        try {
            $sql = "DELETE FROM `nom_incremento_salario` WHERE `id_inc` = ?";
            $consulta  = "DELETE FROM `nom_incremento_salario` WHERE `id_inc` = $id";
            $stmt = $this->conexion->prepare($sql);
            $stmt->bindParam(1, $id, PDO::PARAM_INT);
            $stmt->execute();
            if ($stmt->rowCount() > 0) {
                Logs::guardaLog($consulta);
                return 'si';
            } else {
                return 'No se eliminó el registro';
            }
        } catch (PDOException $e) {
            return 'Error SQL: ' . $e->getMessage();
        }
    }
    public function addIncSalarial($array)
    {
        try {
            $sql = "INSERT INTO `nom_incremento_salario`
                        (`porcentaje`,`vigencia`,`fecha`,`estado`,`fec_reg`,`id_user_reg`)
                    VALUES (?, ?, ?, ?, ?, ?)";
            $stmt = $this->conexion->prepare($sql);
            $stmt->bindValue(1, $array['numPorcIncSal'], PDO::PARAM_INT);
            $stmt->bindValue(2, Sesion::Vigencia(), PDO::PARAM_STR);
            $stmt->bindValue(3, $array['datFechaInSal'], PDO::PARAM_STR);
            $stmt->bindValue(4, 1, PDO::PARAM_INT);
            $stmt->bindValue(5, Sesion::_Hoy(), PDO::PARAM_STR);
            $stmt->bindValue(6, Sesion::IdUser(), PDO::PARAM_INT);
            $stmt->execute();
            $id = $this->conexion->lastInsertId();
            if ($id > 0) {
                $empledos = new Empleados();
                $lista = $empledos->getEmpleados();
                foreach ($lista as $e) {
                    $contrato = $empledos->getContratoActivo($e['id_empleado']);
                    $salario = $empledos->getSalario($contrato);
                    if ($salario == 0 || $salario === Parametros::Smmlv() || $e['estado'] == 0) {
                        $new_salario = 0;
                        continue;
                    } else {
                        $new_salario = $salario + ($salario * $array['numPorcIncSal'] / 100);
                    }
                    $data = [
                        'id_empleado' => $e['id_empleado'],
                        'id_contrato' => $contrato,
                        'vigencia' => Sesion::Vigencia(),
                        'salario_basico' => $new_salario,
                        'id_inc' => $id,

                    ];
                    $empledos->addSalario($data);
                }
                return 'si';
            } else {
                return 'No se insertó el registro';
            }
        } catch (PDOException $e) {
            return 'Error SQL: ' . $e->getMessage();
        }
    }

    public function editIncSalarial($array)
    {
        try {
            $sql = "UPDATE `nom_incremento_salario` 
                        SET `porcentaje` = ?, `fecha` = ? 
                    WHERE `id_inc` = ?";
            $stmt = $this->conexion->prepare($sql);
            $stmt->bindValue(1, $array['numPorcIncSal'], PDO::PARAM_INT);
            $stmt->bindValue(2, $array['datFechaInSal'], PDO::PARAM_STR);
            $stmt->bindValue(3, $array['id'], PDO::PARAM_INT);
            if (!($stmt->execute())) {
                return 'Errado: ' . $stmt->errorInfo()[2];
            } else {
                if ($stmt->rowCount() > 0) {
                    $consulta = "UPDATE `nom_incremento_salario` SET `id_user_act` =  ? , `fec_act` = ? WHERE `id_inc` = ?";
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
