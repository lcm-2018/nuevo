<?php

namespace Src\Nomina\Empleados\Php\Clases;

use Config\Clases\Conexion;
use Config\Clases\Logs;
use Config\Clases\Sesion;

use PDO;
use PDOException;

/**
 * Clase para gestionar los sindicatos de los empleados.
 *
 * Esta clase permite realizar operaciones CRUD sobre los sindicatos de los empleados,
 * incluyendo la obtención de registros, adición, edición y eliminación de sindicatos.
 */
class Sindicatos
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
                            OR `nom_cuota_sindical`.`fec_inicio` LIKE '%{$array['value']}%'
                            OR `nom_cuota_sindical`.`fec_fin` LIKE '%{$array['value']}%')";
            }

            if (isset($array['id']) && $array['id'] > 0) {
                $where .= " AND `nom_cuota_sindical`.`id_empleado` = {$array['id']}";
            }
        }

        $sql = "SELECT
                    `nom_cuota_sindical`.`id_cuota_sindical`
                    , `nom_cuota_sindical`.`id_sindicato`
                    , `tb_terceros`.`nom_tercero`
                    , `tb_terceros`.`nit_tercero`
                    , `nom_cuota_sindical`.`id_empleado`
                    , `nom_cuota_sindical`.`porcentaje_cuota`
                    , `nom_cuota_sindical`.`val_fijo`
                    , `nom_cuota_sindical`.`fec_inicio`
                    , `nom_cuota_sindical`.`fec_fin`
                    , `nom_cuota_sindical`.`val_sidicalizacion`
                    , `nom_cuota_sindical`.`estado`
                    , IFNULL(`aportados`.`valor`, 0) AS `aportado`
                FROM
                    `nom_cuota_sindical`
                    INNER JOIN `nom_terceros` 
                        ON (`nom_cuota_sindical`.`id_sindicato` = `nom_terceros`.`id_tn`)
                    LEFT JOIN `tb_terceros` 
                        ON (`nom_terceros`.`id_tercero_api` = `tb_terceros`.`id_tercero_api`)
                    LEFT JOIN 
                        (SELECT
                            `id_cuota_sindical`, SUM(`val_aporte`) AS `valor`
                        FROM
                            `nom_liq_sindicato_aportes`
                        GROUP BY `id_cuota_sindical`) AS `aportados`
                        ON (`nom_cuota_sindical`.`id_cuota_sindical` = `aportados`.`id_cuota_sindical`)
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
                            OR `nom_cuota_sindical`.`fec_inicio` LIKE '%{$array['value']}%'
                            OR `nom_cuota_sindical`.`fec_fin` LIKE '%{$array['value']}%')";
            }

            if (isset($array['id']) && $array['id'] > 0) {
                $where .= " AND `nom_cuota_sindical`.`id_empleado` = {$array['id']}";
            }
        }

        $sql = "SELECT
                    COUNT(*) AS `total`
                FROM
                    `nom_cuota_sindical`
                    INNER JOIN `nom_terceros` 
                        ON (`nom_cuota_sindical`.`id_sindicato` = `nom_terceros`.`id_tn`)
                    LEFT JOIN `tb_terceros` 
                        ON (`nom_terceros`.`id_tercero_api` = `tb_terceros`.`id_tercero_api`)
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
        if (!empty($array)) {
            if (isset($array['id']) && $array['id'] > 0) {
                $where .= " AND `nom_cuota_sindical`.`id_empleado` = {$array['id']}";
            }
        }

        $sql = "SELECT
                    COUNT(*) AS `total`
                FROM
                    `nom_cuota_sindical`
                    INNER JOIN `nom_terceros` 
                        ON (`nom_cuota_sindical`.`id_sindicato` = `nom_terceros`.`id_tn`)
                    LEFT JOIN `tb_terceros` 
                        ON (`nom_terceros`.`id_tercero_api` = `tb_terceros`.`id_tercero_api`)
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
                    `id_cuota_sindical`,`id_sindicato`,`porcentaje_cuota`,`val_fijo`,`fec_inicio`,`fec_fin`,`val_sidicalizacion`
                FROM `nom_cuota_sindical`
                WHERE `id_cuota_sindical` = ?";
        $stmt = $this->conexion->prepare($sql);
        $stmt->bindParam(1, $id, PDO::PARAM_INT);
        $stmt->execute();
        $registro = $stmt->fetch(PDO::FETCH_ASSOC);
        if (empty($registro)) {
            $registro = [
                'id_cuota_sindical' => 0,
                'id_sindicato' => 0,
                'porcentaje_cuota' => 0,
                'val_fijo' => 0,
                'fec_inicio' => Sesion::_Hoy(),
                'fec_fin' => '',
                'val_sidicalizacion' => 0,
            ];
        }
        return $registro;
    }

    public function getRegistroPorEmpleado()
    {
        $sql = "SELECT
                    `id_cuota_sindical`,`id_sindicato`,`porcentaje_cuota`,`val_fijo`,`estado`,  `fec_fin`, `val_sidicalizacion`
                FROM `nom_cuota_sindical`
                WHERE `estado` = 1";
        $stmt = $this->conexion->prepare($sql);
        $stmt->execute();
        $registro = $stmt->fetchAll(PDO::FETCH_ASSOC);
        return $registro;
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
        $sindicatos = Empleados::getTerceroNomina('juz', $registro['id_sindicato']);
        $html =
            <<<HTML
                <div class="shadow text-center rounded">
                    <div class="rounded-top py-2" style="background-color: #16a085 !important;">
                        <h5 style="color: white;" class="mb-0">GESTIÓN DE APORTES SINDICALES</h5>
                    </div>
                    <div class="p-3">
                        <form id="formSindicatos">
                            <input type="hidden" id="id" name="id" value="{$id}">
                            <div class="row mb-2">
                                <div class="col-md-12">
                                    <label for="slcSindicato" class="small text-muted">Sindicato</label>
                                    <select class="form-select form-select-sm bg-input" id="slcSindicato" name="slcSindicato">
                                        {$sindicatos}
                                    </select>
                                </div>
                            </div>
                            <div class="row mb-2">
                                <div class="col-md-6">
                                    <label for="numValSind" class="small text-muted">Val. Sindicalización</label>
                                    <input type="number" class="form-control form-control-sm bg-input text-end" id="numValSind" name="numValSind" value="{$registro['val_sidicalizacion']}" min="0">
                                </div>
                                <div class="col-md-6">
                                    <label for="numPorcentaje" class="small text-muted">Porcentaje</label>
                                    <input type="number" class="form-control form-control-sm bg-input text-end" id="numPorcentaje" name="numPorcentaje" value="{$registro['porcentaje_cuota']}" min="0">
                                </div>
                            </div>
                            <div class="row mb-2">
                                <div class="col-md-6">
                                    <label for="datFecInicia" class="small text-muted">Inicia</label>
                                    <input type="date" class="form-control form-control-sm bg-input" id="datFecInicia" name="datFecInicia" value="{$registro['fec_inicio']}">
                                </div>
                                <div class="col-md-6">
                                    <label for="datFecFin" class="small text-muted">Termina</label>
                                    <input type="date" class="form-control form-control-sm bg-input" id="datFecFin" name="datFecFin" value="{$registro['fec_fin']}">
                                </div>
                            </div>
                        </form>
                    </div>
                    <div class="text-center pb-3">
                        <button type="button" class="btn btn-primary btn-sm" id="btnGuardarSindicato">Guardar</button>
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
            $sql = "DELETE FROM `nom_cuota_sindical` WHERE `id_cuota_sindical` = ?";
            $consulta  = "DELETE FROM `nom_cuota_sindical` WHERE `id_cuota_sindical` = $id";
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
        $valor = Empleados::getSalarioBasico($_POST['id_empleado']) * ($_POST['numPorcentaje'] / 100);
        try {
            $sql = "INSERT INTO `nom_cuota_sindical`
                        (`id_sindicato`,`id_empleado`,`porcentaje_cuota`,`val_fijo`,`fec_inicio`,`fec_fin`,`val_sidicalizacion`,`estado`,`fec_reg`)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $stmt = $this->conexion->prepare($sql);
            $stmt->bindValue(1, $array['slcSindicato'], PDO::PARAM_INT);
            $stmt->bindValue(2, $array['id_empleado'], PDO::PARAM_INT);
            $stmt->bindValue(3, $array['numPorcentaje'], PDO::PARAM_STR);
            $stmt->bindValue(4, $valor, PDO::PARAM_STR);
            $stmt->bindValue(5, $array['datFecInicia'], PDO::PARAM_STR);
            $stmt->bindValue(6, $array['datFecFin'] == '' ? NULL : $array['datFecFin'], PDO::PARAM_STR);
            $stmt->bindValue(7, $array['numValSind'], PDO::PARAM_STR);
            $stmt->bindValue(8, 1, PDO::PARAM_INT);
            $stmt->bindValue(9, Sesion::Hoy(), PDO::PARAM_STR);
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
        $valor = Empleados::getSalarioBasico($_POST['id_empleado']) * ($_POST['numPorcentaje'] / 100);
        try {
            $sql = "UPDATE `nom_cuota_sindical`
                        SET `id_sindicato` = ?, `porcentaje_cuota` = ?, `val_fijo` = ?, `fec_inicio` = ?, `fec_fin` = ?, `val_sidicalizacion` = ?
                    WHERE `id_cuota_sindical` = ?";
            $stmt = $this->conexion->prepare($sql);
            $stmt->bindValue(1, $array['slcSindicato'], PDO::PARAM_INT);
            $stmt->bindValue(2, $array['numPorcentaje'], PDO::PARAM_STR);
            $stmt->bindValue(3, $valor, PDO::PARAM_STR);
            $stmt->bindValue(4, $array['datFecInicia'], PDO::PARAM_STR);
            $stmt->bindValue(5, $array['datFecFin'] == '' ? NULL : $array['datFecFin'], PDO::PARAM_STR);
            $stmt->bindValue(6, $array['numValSind'], PDO::PARAM_STR);
            $stmt->bindValue(7, $array['id'], PDO::PARAM_INT);

            if ($stmt->execute() && $stmt->rowCount() > 0) {
                $consulta = "UPDATE `nom_cuota_sindical` SET `fec_act` = ? WHERE `id_cuota_sindical` = ?";
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
            $sql = "UPDATE `nom_cuota_sindical`
                        SET `estado` = ?
                    WHERE `id_cuota_sindical` = ?";
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
