<?php

namespace Src\Nomina\Empleados\Php\Clases;

use Config\Clases\Conexion;
use Config\Clases\Logs;
use Config\Clases\Sesion;

use PDO;
use PDOException;
use Src\Nomina\Liquidacion\Php\Clases\Nomina;

/**
 * Clase para gestionar las indenminzaciones por vacaciones de los empleados.
 *
 * Esta clase permite realizar operaciones CRUD sobre las indenminzaciones por vacaciones de los empleados,
 * incluyendo la obtención de registros, adición, edición y eliminación de indeminaciones.
 */
class Bsp
{
    private $conexion;

    public function __construct($conexion = null)
    {
        $this->conexion = $conexion ?: Conexion::getConexion(); // Método estático que retorna el objeto PDO
    }

    /**
     * Obtiene los datos para la DataTable.
     *
     * @param int $start Índice de inicio para la paginación
     * @param int $length Número de registros a mostrar
     * @param string $array  filtros de búsqueda
     * @param int $col Índice de la columna para ordenar
     * @param string $dir Dirección de ordenamiento (ascendente o descendente) 
     * @return array|[] Retorna un array con los datos 
     */
    public function getRegistrosDT($start, $length, $array, $col, $dir)
    {
        $limit = "";
        if ($length != -1) {
            $limit = "LIMIT $start, $length";
        }

        $where = '';
        if (!empty($array)) {
            if (isset($array['value']) && $array['value'] != '') {
                $where .= " AND (`fec_corte` LIKE '%{$array['value']}%' 
                            OR `val_bsp` LIKE '%{$array['value']}%')";
            }

            if (isset($array['id']) && $array['id'] > 0) {
                $where .= " AND `id_empleado` = {$array['id']}";
            }
        }

        $sql = "SELECT
                    `id_bonificaciones`,`val_bsp`,`fec_corte`,`tipo`,`estado`
                FROM `nom_liq_bsp`
                WHERE (1 = 1 $where)
                ORDER BY $col $dir $limit";
        $stmt = $this->conexion->prepare($sql);
        $stmt->execute();
        $datos = $stmt->fetchAll(PDO::FETCH_ASSOC);
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
    public function getRegistrosFilter($array)
    {
        $where = '';
        if (!empty($array)) {
            if (isset($array['value']) && $array['value'] != '') {
                $where .= " AND (`fec_corte` LIKE '%{$array['value']}%' 
                            OR `val_bsp` LIKE '%{$array['value']}%')";
            }

            if (isset($array['id']) && $array['id'] > 0) {
                $where .= " AND `id_empleado` = {$array['id']}";
            }
        }

        $sql = "SELECT
                    COUNT(*) AS `total`
                FROM `nom_liq_bsp`
                WHERE (1 = 1 $where)";
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

    public function getRegistrosTotal($array)
    {
        $where = '';
        if (!empty($array)) {
            if (isset($array['id']) && $array['id'] > 0) {
                $where .= " AND `id_empleado` = {$array['id']}";
            }
        }

        $sql = "SELECT
                   COUNT(*) AS `total`
                FROM
                    `nom_liq_bsp`
                WHERE (1 = 1 $where)";
        $stmt = $this->conexion->prepare($sql);
        $stmt->execute();
        $data = $stmt->fetch(PDO::FETCH_ASSOC)['total'] ?: 0;
        $stmt->closeCursor();
        unset($stmt);
        return $data;
    }

    /**
     * Obtiene un registro por ID.
     *
     * @param int $id ID del registro
     * @return array  datos del registro
     */

    public function getRegistro($id)
    {
        $sql = "SELECT
                    `id_bonificaciones`,`val_bsp`,`fec_corte`,`tipo`,`estado`
                FROM `nom_liq_bsp`
                WHERE `id_bonificaciones` = ?";
        $stmt = $this->conexion->prepare($sql);
        $stmt->bindParam(1, $id, PDO::PARAM_INT);
        $stmt->execute();
        $registro = $stmt->fetch(PDO::FETCH_ASSOC);
        if (empty($registro)) {
            $registro = [
                'id_bonificaciones' => 0,
                'val_bsp' => 0,
                'fec_corte' => Sesion::_Hoy(),
                'tipo' => 0,
                'estado' => 1,
            ];
        }
        $stmt->closeCursor();
        unset($stmt);
        return $registro;
    }

    public function getRegistroPorEmpleado()
    {
        $sql = "SELECT
                    `id_empleado`,`val_bsp`
                FROM `nom_liq_bsp`
                WHERE `estado` = 1 AND `tipo` = 'M'";
        $stmt = $this->conexion->prepare($sql);
        $stmt->execute();
        $registro = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $stmt->closeCursor();
        unset($stmt);
        return !empty($registro) ? array_column($registro, 'val_bsp', 'id_empleado') : [];
    }
    /**
     * Obtiene el formulario para agregar o editar un registro.
     *
     * @param int $id ID del registro (0 para nuevo)
     * @return string HTML del formulario
     */
    public function getFormulario($data)
    {
        $registro = $this->getRegistro($data['id']);
        $E = new Empleados();
        $salario = $E->getSalario($E->getContratoActivo($data['id_empleado']));
        $vals = Nomina::getParamLiq();
        $vals = array_column($vals, 'valor', 'id_concepto');
        $gasrep = $E->getEmpleados($data['id_empleado'])  == 0 ? 0 : $vals[8];
        $bsp = (($salario + $gasrep) <= $vals[7] ? ($salario + $gasrep) * 0.5 : ($salario + $gasrep) * 0.35);
        $bsp  = $data['id'] == 0 ? $bsp : $registro['val_bsp'];
        $html =
            <<<HTML
                <div class="shadow text-center rounded">
                    <div class="rounded-top py-2" style="background-color: #16a085 !important;">
                        <h5 style="color: white;" class="mb-0">GESTIÓN DE BONIFICACIÓN DE SERVICIOS</h5>
                    </div>
                    <div class="p-3">
                        <form id="formBsp">
                            <input type="hidden" id="id" name="id" value="{$data['id']}">
                            <div class="row mb-2">
                                <div class="col-md-12">
                                    <label for="numValor" class="small text-muted">Valor</label>
                                    <input type="number" class="form-control form-control-sm bg-input text-end" id="numValor" name="numValor" value="{$bsp}" min="0">
                                </div>
                            </div>
                            <div class="row mb-2">
                                <div class="col-md-12">
                                    <label for="datFecCorte" class="small text-muted">Corte</label>
                                    <input type="date" class="form-control form-control-sm bg-input" id="datFecCorte" name="datFecCorte" value="{$registro['fec_corte']}">
                                </div>
                            </div>
                        </form>
                    </div>
                    <div class="text-center pb-3">
                        <button type="button" class="btn btn-primary btn-sm" id="btnGuardarBsp">Guardar</button>
                        <a type="button" class="btn btn-secondary  btn-sm" data-bs-dismiss="modal">Cancelar</a>
                    </div>
                </div>
            HTML;
        return $html;
    }

    /**
     * Elimina un registro.
     *
     * @param int $id ID del registro a eliminar
     * @return string Mensaje de éxito o error
     */

    public function delRegistro($id)
    {
        try {
            $sql = "DELETE FROM `nom_liq_bsp` WHERE `id_bonificaciones` = ?";
            $consulta  = "DELETE FROM `nom_liq_bsp` WHERE `id_bonificaciones` = $id";
            $stmt = $this->conexion->prepare($sql);
            $stmt->bindParam(1, $id, PDO::PARAM_INT);
            $stmt->execute();
            if ($stmt->rowCount() > 0) {
                Logs::guardaLog($consulta);
                (new Novedades())->delRegistro(6, $id);
                return 'si';
            } else {
                return 'No se eliminó el registro.';
            }
        } catch (PDOException $e) {
            return 'Error SQL: ' . $e->getMessage();
        }
    }

    /**
     * Agrega un nuevo registro.
     *
     * @param array $array Datos del registro a agregar
     * @return string Mensaje de éxito o error
     */
    public function addRegistro($array)
    {
        try {
            $sql = "INSERT INTO `nom_liq_bsp`
                        (`id_empleado`,`val_bsp`,`fec_corte`,`tipo`,`id_user_reg`,`fec_reg`,`id_nomina`)
                    VALUES (?, ?, ?, ?, ?, ?, ?)";
            $stmt = $this->conexion->prepare($sql);
            $stmt->bindValue(1, $array['id_empleado'], PDO::PARAM_INT);
            $stmt->bindValue(2, $array['valor'], PDO::PARAM_STR);
            $stmt->bindValue(3, $array['corte'], PDO::PARAM_STR);
            $stmt->bindValue(4, $array['tipo'] ?? 'S', PDO::PARAM_STR);
            $stmt->bindValue(5, Sesion::IdUser(), PDO::PARAM_INT);
            $stmt->bindValue(6, Sesion::Hoy(), PDO::PARAM_STR);
            $stmt->bindValue(7, $array['id_nomina'] ?? NULL, PDO::PARAM_INT);
            $stmt->execute();
            $id = $this->conexion->lastInsertId();
            $stmt->closeCursor();
            unset($stmt);
            if ($id > 0) {
                return 'si';
            } else {
                return 'No se insertó el registro';
            }
        } catch (PDOException $e) {
            return 'Error SQL: ' . $e->getMessage();
        }
    }

    /**
     * Actualiza los datos de un registro.
     *
     * @param array $array Datos del registro a actualizar
     * @return string Mensaje de éxito o error
     */
    public function editRegistro($array)
    {
        try {
            $sql = "UPDATE `nom_liq_bsp`
                        SET `val_bsp` = ?, `fec_corte` = ?
                    WHERE `id_bonificaciones` = ?";
            $stmt = $this->conexion->prepare($sql);
            $stmt->bindValue(1, $array['numValor'], PDO::PARAM_STR);
            $stmt->bindValue(2, $array['datFecCorte'], PDO::PARAM_STR);
            $stmt->bindValue(3, $array['id'], PDO::PARAM_INT);
            if ($stmt->execute() && $stmt->rowCount() > 0) {
                $stmt->closeCursor();
                $consulta = "UPDATE `nom_liq_bsp` SET `fec_act` = ?, `id_user_act` = ? WHERE `id_bonificaciones` = ?";
                $stmt2 = $this->conexion->prepare($consulta);
                $stmt2->bindValue(1, Sesion::Hoy(), PDO::PARAM_STR);
                $stmt2->bindValue(2, Sesion::IdUser(), PDO::PARAM_INT);
                $stmt2->bindValue(3, $array['id'], PDO::PARAM_INT);
                $stmt2->execute();
                $stmt2->closeCursor();
                unset($stmt2);
                return 'si';
            } else {
                return 'No se actualizó el registro.';
            }
        } catch (PDOException $e) {
            return 'Error SQL: ' . $e->getMessage();
        }
    }

    public function annulRegistro($array)
    {
        return 'Falta programar la anulación de registro de seguridad social.';
    }
}
