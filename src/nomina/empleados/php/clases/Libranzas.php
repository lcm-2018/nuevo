<?php

namespace Src\Nomina\Empleados\Php\Clases;

use Config\Clases\Conexion;
use Config\Clases\Logs;
use Config\Clases\Sesion;

use PDO;
use PDOException;
use Src\Common\Php\Clases\Combos;

/**
 * Clase para gestionar las indenminzaciones por vacaciones de los empleados.
 *
 * Esta clase permite realizar operaciones CRUD sobre las indenminzaciones por vacaciones de los empleados,
 * incluyendo la obtención de registros, adición, edición y eliminación de indeminaciones.
 */
class Libranzas
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
                $where .= " AND (`tb_terceros`.`nom_tercero` LIKE '%{$array['value']}%' 
                            OR `tb_terceros`.`nit_tercero` LIKE '%{$array['value']}%'
                            OR `nom_libranzas`.`descripcion_lib` LIKE '%{$array['value']}%'
                            OR `nom_libranzas`.`valor_total` LIKE '%{$array['value']}%'
                            OR `nom_libranzas`.`cuotas` LIKE '%{$array['value']}%'
                            OR `nom_libranzas`.`val_mes` LIKE '%{$array['value']}%'
                            OR `nom_libranzas`.`porcentaje` LIKE '%{$array['value']}%'
                            OR `nom_libranzas`.`fecha_inicio` LIKE '%{$array['value']}%'
                            OR `nom_libranzas`.`fecha_fin` LIKE '%{$array['value']}%')";
            }

            if (isset($array['id']) && $array['id'] > 0) {
                $where .= " AND `nom_libranzas`.`id_empleado` = {$array['id']}";
            }
        }

        $sql = "SELECT
                    `nom_libranzas`.`id_libranza`
                    , `nom_libranzas`.`id_banco`
                    , IFNULL(`tb_terceros`.`nom_tercero`, `tb_bancos`.`nom_banco`) AS `nom_banco`
                    , IFNULL(`tb_terceros`.`nit_tercero`, `tb_bancos`.`nit_banco`) AS `nit_banco`
                    , `nom_libranzas`.`id_empleado`
                    , `nom_libranzas`.`descripcion_lib`
                    , `nom_libranzas`.`estado`
                    , `nom_libranzas`.`valor_total`
                    , `nom_libranzas`.`cuotas`
                    , `nom_libranzas`.`val_mes`
                    , `nom_libranzas`.`porcentaje`
                    , `nom_libranzas`.`fecha_inicio`
                    , `nom_libranzas`.`fecha_fin`
                    , IFNULL(`pagado`.`valor`,0) AS `pagado`
                FROM
                    `nom_libranzas`
                    INNER JOIN `tb_bancos` 
                        ON (`nom_libranzas`.`id_banco` = `tb_bancos`.`id_banco`)
                    LEFT JOIN `tb_terceros` 
                        ON (`tb_bancos`.`id_tercero_api` = `tb_terceros`.`id_tercero_api`)
                    LEFT JOIN 
                        (SELECT
                            `id_libranza`
                            , SUM(`val_mes_lib`) AS `valor`
                        FROM
                            `nom_liq_libranza`
                        GROUP BY `id_libranza`) AS `pagado`
                        ON (`nom_libranzas`.`id_libranza` = `pagado`.`id_libranza`)
                WHERE (1 = 1 $where)
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
    public function getRegistrosFilter($array)
    {
        $where = '';
        if (!empty($array)) {
            if (isset($array['value']) && $array['value'] != '') {
                $where .= " AND (`tb_terceros`.`nom_tercero` LIKE '%{$array['value']}%' 
                            OR `tb_terceros`.`nit_tercero` LIKE '%{$array['value']}%'
                            OR `nom_libranzas`.`descripcion_lib` LIKE '%{$array['value']}%'
                            OR `nom_libranzas`.`valor_total` LIKE '%{$array['value']}%'
                            OR `nom_libranzas`.`cuotas` LIKE '%{$array['value']}%'
                            OR `nom_libranzas`.`val_mes` LIKE '%{$array['value']}%'
                            OR `nom_libranzas`.`porcentaje` LIKE '%{$array['value']}%'
                            OR `nom_libranzas`.`fecha_inicio` LIKE '%{$array['value']}%'
                            OR `nom_libranzas`.`fecha_fin` LIKE '%{$array['value']}%')";
            }

            if (isset($array['id']) && $array['id'] > 0) {
                $where .= " AND `nom_libranzas`.`id_empleado` = {$array['id']}";
            }
        }

        $sql = "SELECT
                    COUNT(*) AS `total`
                FROM
                    `nom_libranzas`
                    INNER JOIN `tb_bancos` 
                        ON (`nom_libranzas`.`id_banco` = `tb_bancos`.`id_banco`)
                    INNER JOIN `tb_terceros` 
                        ON (`tb_bancos`.`id_tercero_api` = `tb_terceros`.`id_tercero_api`)
                WHERE (1 = 1 $where)";
        $stmt = $this->conexion->prepare($sql);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC)['total'] ?: 0;
    }

    /**
     * Obtiene el total de registros.
     * @return int Total de registros
     */

    public function getRegistrosTotal($array)
    {
        $where = '';
        if (isset($array['id']) && $array['id'] > 0) {
            $where .= " AND `nom_libranzas`.`id_empleado` = {$array['id']}";
        }

        $sql = "SELECT
                    COUNT(*) AS `total`
                FROM
                    `nom_libranzas`
                    INNER JOIN `tb_bancos` 
                        ON (`nom_libranzas`.`id_banco` = `tb_bancos`.`id_banco`)
                    INNER JOIN `tb_terceros` 
                        ON (`tb_bancos`.`id_tercero_api` = `tb_terceros`.`id_tercero_api`)
                WHERE (1 = 1 $where)";
        $stmt = $this->conexion->prepare($sql);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC)['total'] ?: 0;
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
                    `id_libranza`,`id_banco`,`id_empleado`,`estado`,`descripcion_lib`,`valor_total`,`cuotas`,`val_mes`,`porcentaje`,`fecha_inicio`,`fecha_fin`
                FROM `nom_libranzas`
                WHERE `id_libranza` = ?";
        $stmt = $this->conexion->prepare($sql);
        $stmt->bindParam(1, $id, PDO::PARAM_INT);
        $stmt->execute();
        $registro = $stmt->fetch(PDO::FETCH_ASSOC);
        if (empty($registro)) {
            $registro = [
                'id_libranza' => 0,
                'id_banco' => 0,
                'id_empleado' => 0,
                'estado' => 0,
                'descripcion_lib' => '',
                'valor_total' => 0,
                'cuotas' => 0,
                'val_mes' => 0,
                'porcentaje' => 0,
                'fecha_inicio' => '',
                'fecha_fin' => '',
            ];
        }
        return $registro;
    }

    public function getLibranzasPorEmpleado()
    {
        $sql = "SELECT
                    `id_libranza`,`id_banco`,`id_empleado`,`val_mes`,`porcentaje`, `fecha_fin`
                FROM `nom_libranzas`
                WHERE `estado` = 1";
        $stmt = $this->conexion->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Obtiene el formulario para agregar o editar un registro.
     *
     * @param int $id ID del registro (0 para nuevo)
     * @return string HTML del formulario
     */
    public function getFormulario($id)
    {
        $registro = $this->getRegistro($id);
        $bancos = Combos::getBancos($registro['id_banco']);
        $html =
            <<<HTML
                <div class="shadow text-center rounded">
                    <div class="rounded-top py-2" style="background-color: #16a085 !important;">
                        <h5 style="color: white;" class="mb-0">GESTIÓN DE LIBRANZAS</h5>
                    </div>
                    <div class="p-3">
                        <form id="formLibranza">
                            <input type="hidden" id="id" name="id" value="{$id}">
                            <div class="row mb-2">
                                <div class="col-md-6">
                                    <label for="slcEntFinanciera" class="small text-muted">Entidad Financiera</label>
                                    <select class="form-select form-select-sm bg-input" id="slcEntFinanciera" name="slcEntFinanciera">
                                        {$bancos}
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <label for="numTotLib" class="small text-muted">Total</label>
                                    <input type="number" class="form-control form-control-sm bg-input text-end" id="numTotLib" name="numTotLib" value="{$registro['valor_total']}" min="0">
                                </div>
                                <div class="col-md-3">
                                    <label for="numCuotasLib" class="small text-muted"># Cuotas</label>
                                    <input type="number" class="form-control form-control-sm bg-input text-end" id="numCuotasLib" name="numCuotasLib" value="{$registro['cuotas']}" min="0">
                                </div>
                            </div>
                            <div class="row mb-2">
                                <div class="col-md-3">
                                    <label for="numValMes" class="small text-muted">Valor Mes</label>
                                    <input type="number" class="form-control form-control-sm bg-input text-end" id="numValMes" name="numValMes" value="{$registro['val_mes']}" min="0" onblur="CalcValorPorcentaje()">
                                </div>
                                <div class="col-md-3">
                                    <label for="numPorcentaje" class="small text-muted">Porcentaje</label>
                                    <input type="number" class="form-control form-control-sm bg-input text-end" id="numPorcentaje" name="numPorcentaje" value="{$registro['porcentaje']}" min="0" onblur="CalcValorMes()">
                                </div>
                                <div class="col-md-3">
                                    <label for="datFecInicia" class="small text-muted">Inicia</label>
                                    <input type="date" class="form-control form-control-sm bg-input" id="datFecInicia" name="datFecInicia" value="{$registro['fecha_inicio']}">
                                </div>
                                <div class="col-md-3">
                                    <label for="datFecFin" class="small text-muted">Termina</label>
                                    <input type="date" class="form-control form-control-sm bg-input" id="datFecFin" name="datFecFin" value="{$registro['fecha_fin']}">
                                </div>
                            </div>
                            <div class="row mb-2">
                                <div class="col-md-12">
                                    <label for="txtDescripcion" class="small text-muted">Descripción</label>
                                    <input type="text" class="form-control form-control-sm bg-input" id="txtDescripcion" name="txtDescripcion" value="{$registro['descripcion_lib']}">
                                </div>
                            </div>
                        </form>
                    </div>
                    <div class="text-center pb-3">
                        <button type="button" class="btn btn-primary btn-sm" id="btnGuardarLibranza">Guardar</button>
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
            $sql = "DELETE FROM `nom_libranzas` WHERE `id_libranza` = ?";
            $consulta  = "DELETE FROM `nom_libranzas` WHERE `id_libranza` = $id";
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
     * Agrega un nuevo registro.
     *
     * @param array $array Datos del registro a agregar
     * @return string Mensaje de éxito o error
     */
    public function addRegistro($array)
    {
        try {
            $sql = "INSERT INTO `nom_libranzas`
                        (`id_banco`,`id_empleado`,`estado`,`descripcion_lib`,`valor_total`,`cuotas`,`val_mes`,`porcentaje`,`fecha_inicio`,`fecha_fin`,`fec_reg`)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $stmt = $this->conexion->prepare($sql);
            $stmt->bindValue(1, $array['slcEntFinanciera'], PDO::PARAM_INT);
            $stmt->bindValue(2, $array['id_empleado'], PDO::PARAM_INT);
            $stmt->bindValue(3, 1, PDO::PARAM_INT);
            $stmt->bindValue(4, $array['txtDescripcion'], PDO::PARAM_STR);
            $stmt->bindValue(5, $array['numTotLib'], PDO::PARAM_STR);
            $stmt->bindValue(6, $array['numCuotasLib'], PDO::PARAM_INT);
            $stmt->bindValue(7, $array['numValMes'], PDO::PARAM_STR);
            $stmt->bindValue(8, $array['numPorcentaje'], PDO::PARAM_STR);
            $stmt->bindValue(9, $array['datFecInicia'], PDO::PARAM_STR);
            $stmt->bindValue(10, $array['datFecFin'], PDO::PARAM_STR);
            $stmt->bindValue(11, Sesion::Hoy(), PDO::PARAM_STR);
            $stmt->execute();
            $id = $this->conexion->lastInsertId();
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
            $sql = "UPDATE `nom_libranzas`
                        SET `id_banco` = ?, `descripcion_lib` = ?, `valor_total` = ?, `cuotas` = ?, `val_mes` = ?, `porcentaje` = ?, `fecha_inicio` = ?, `fecha_fin` = ?
                    WHERE `id_libranza` = ?";
            $stmt = $this->conexion->prepare($sql);
            $stmt->bindValue(1, $array['slcEntFinanciera'], PDO::PARAM_INT);
            $stmt->bindValue(2, $array['txtDescripcion'], PDO::PARAM_STR);
            $stmt->bindValue(3, $array['numTotLib'], PDO::PARAM_STR);
            $stmt->bindValue(4, $array['numCuotasLib'], PDO::PARAM_INT);
            $stmt->bindValue(5, $array['numValMes'], PDO::PARAM_STR);
            $stmt->bindValue(6, $array['numPorcentaje'], PDO::PARAM_STR);
            $stmt->bindValue(7, $array['datFecInicia'], PDO::PARAM_STR);
            $stmt->bindValue(8, $array['datFecFin'], PDO::PARAM_STR);
            $stmt->bindValue(9, $array['id'], PDO::PARAM_INT);

            if ($stmt->execute() && $stmt->rowCount() > 0) {
                $consulta = "UPDATE `nom_libranzas` SET `fec_act` = ? WHERE `id_libranza` = ?";
                $stmt2 = $this->conexion->prepare($consulta);
                $stmt2->bindValue(1, Sesion::Hoy(), PDO::PARAM_STR);
                $stmt2->bindValue(2, $array['id'], PDO::PARAM_INT);
                $stmt2->execute();
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
        try {
            $sql = "UPDATE `nom_libranzas`
                        SET `estado` = ?
                    WHERE `id_libranza` = ?";
            $stmt = $this->conexion->prepare($sql);
            $stmt->bindValue(1, $array['estado'], PDO::PARAM_INT);
            $stmt->bindValue(2, $array['id'], PDO::PARAM_INT);
            if ($stmt->execute() && $stmt->rowCount() > 0) {
                return 'si';
            } else {
                return 'No se hizo el cambio de estado.' . $stmt->errorInfo()[2];
            }
        } catch (PDOException $e) {
            return 'Error SQL: ' . $e->getMessage();
        }
    }
}
