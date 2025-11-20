<?php

namespace Src\Nomina\Empleados\Php\Clases;

use Config\Clases\Conexion;
use Config\Clases\Logs;
use Config\Clases\Sesion;
use Src\Nomina\Empleados\Php\Clases\Novedades;

use PDO;
use PDOException;

class Incapacidades
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
                $where .= " AND (`nom_tipo_incapacidad`.`tipo` LIKE '%{$array['value']}%' 
                            OR `nom_incapacidad`.`fec_inicio` LIKE '%{$array['value']}%'
                            OR `nom_incapacidad`.`fec_fin` LIKE '%{$array['value']}%'
                            OR `nom_incapacidad`.`can_dias` LIKE '%{$array['value']}%')";
            }

            if (isset($array['id']) && $array['id'] > 0) {
                $where .= " AND `nom_incapacidad`.`id_empleado` = {$array['id']}";
            }
        }

        $sql = "SELECT
                    `nom_incapacidad`.`id_incapacidad`
                    , `nom_incapacidad`.`id_empleado`
                    , `nom_tipo_incapacidad`.`tipo`
                    , `nom_incapacidad`.`fec_inicio`
                    , `nom_incapacidad`.`fec_fin`
                    , `nom_incapacidad`.`can_dias`
                    , IF(`nom_incapacidad`.`categoria` = 1, 'INICIAL','PRORROGA') AS `categoria`
                    , IFNULL(`liq`.`id_incapacidad`, 0) AS `liq`
                FROM
                    `nom_incapacidad`
                    INNER JOIN `nom_tipo_incapacidad` 
                        ON (`nom_incapacidad`.`id_tipo` = `nom_tipo_incapacidad`.`id_tipo`)
                    LEFT JOIN
                        (SELECT
                            `li`.`id_incapacidad`
                        FROM
                            `nom_liq_incap` AS `li`
                            INNER JOIN `nom_nominas` AS `n` 
                                ON (`li`.`id_nomina` = `n`.`id_nomina`)
                        WHERE (`n`.`estado` > 0)
                        GROUP BY `li`.`id_incapacidad`) AS `liq`
                        ON (`nom_incapacidad`.`id_incapacidad` = `liq`.`id_incapacidad`)
                WHERE (1 = 1 $where)
                ORDER BY $col $dir $limit";
        $stmt = $this->conexion->prepare($sql);
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
    public function getRegistrosFilter($array)
    {
        $where = '';
        if (!empty($array)) {
            if (isset($array['value']) && $array['value'] != '') {
                $where .= " AND (`nom_tipo_incapacidad`.`tipo` LIKE '%{$array['value']}%' 
                            OR `nom_incapacidad`.`fec_inicio` LIKE '%{$array['value']}%'
                            OR `nom_incapacidad`.`fec_fin` LIKE '%{$array['value']}%'
                            OR `nom_incapacidad`.`can_dias` LIKE '%{$array['value']}%')";
            }

            if (isset($array['id']) && $array['id'] > 0) {
                $where .= " AND `nom_incapacidad`.`id_empleado` = {$array['id']}";
            }
        }

        $sql = "SELECT
                   COUNT(*) AS `total`
                FROM
                    `nom_incapacidad`
                    INNER JOIN `nom_tipo_incapacidad` 
                        ON (`nom_incapacidad`.`id_tipo` = `nom_tipo_incapacidad`.`id_tipo`)
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
                $where .= " AND `nom_incapacidad`.`id_empleado` = {$array['id']}";
            }
        }

        $sql = "SELECT
                   COUNT(*) AS `total`
                FROM
                    `nom_incapacidad`
                    INNER JOIN `nom_tipo_incapacidad` 
                        ON (`nom_incapacidad`.`id_tipo` = `nom_tipo_incapacidad`.`id_tipo`)
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
                    `id_incapacidad`, `id_tipo`, `fec_inicio`, `fec_fin`, `can_dias`, `categoria`
                FROM
                    `nom_incapacidad`
                WHERE (`id_incapacidad` = ?)";
        $stmt = $this->conexion->prepare($sql);
        $stmt->bindParam(1, $id, PDO::PARAM_INT);
        $stmt->execute();
        $registro = $stmt->fetch(PDO::FETCH_ASSOC);
        $stmt->closeCursor();
        unset($stmt);
        if (empty($registro)) {
            $registro = [
                'id_incapacidad' => 0,
                'id_tipo' => 1, // Por defecto tipo inicial
                'fec_inicio' => Sesion::_Hoy(),
                'fec_fin' => '',
                'can_dias' => 0,
                'categoria' => 1,
            ];
        }
        return $registro;
    }

    public function getRegistroPorEmpleado($inicia, $fin)
    {
        $sql = "SELECT
                    `nom_incapacidad`.`id_incapacidad`
                    , `nom_incapacidad`.`id_tipo`
                    , `nom_incapacidad`.`id_empleado`
                    , `nom_incapacidad`.`can_dias`
                    , `nom_incapacidad`.`categoria`
                    , IFNULL(`liquidado`.`dias_liq`,0) AS `liq`
                    , IFNULL(`calendario`.`dias`,0) AS `dias`
                FROM
                    `nom_incapacidad`
                    LEFT JOIN 
                        (SELECT
                            `id_incapacidad`, SUM(`dias_liq`) AS `dias_liq`
                        FROM
                            `nom_liq_incap`
                        WHERE (`estado` = 1)
                        GROUP BY `id_incapacidad`) AS `liquidado`
                        ON (`liquidado`.`id_incapacidad` = `nom_incapacidad`.`id_incapacidad`)
                    LEFT JOIN
                        (SELECT
                            `id_novedad`
                            , COUNT(`id_novedad`) AS `dias`
                        FROM
                            `nom_calendar_novedad`
                        WHERE (`id_tipo` = 1 AND `fecha` BETWEEN ? AND ?)
                        GROUP BY `id_novedad`, `id_empleado`) AS `calendario`
                        ON (`nom_incapacidad`.`id_incapacidad` = `calendario`.`id_novedad`)
                WHERE `calendario`.`dias` > 0";
        $stmt = $this->conexion->prepare($sql);
        $stmt->bindParam(1, $inicia, PDO::PARAM_STR);
        $stmt->bindParam(2, $fin, PDO::PARAM_STR);
        $stmt->execute();
        $registro = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $stmt->closeCursor();
        unset($stmt);

        $index = [];
        foreach ($registro as $row) {
            $index[$row['id_empleado']][] = $row;
        }

        return $index;
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
        $cat0 = $registro['categoria'] == 2 ? 'checked' : '';
        $cat1 = $registro['categoria'] == 1 ? 'checked' : '';
        $tip0 = $registro['id_tipo'] == 3 ? 'checked' : '';
        $tip1 = $registro['id_tipo'] == 1 ? 'checked' : '';
        $html =
            <<<HTML
                <div class="shadow text-center rounded">
                    <div class="rounded-top py-2" style="background-color: #16a085 !important;">
                        <h5 style="color: white;" class="mb-0">GESTIÓN DE INCAPACIDAD</h5>
                    </div>
                    <div class="p-3">
                        <form id="formIncapacidad">
                            <input type="hidden" id="id" name="id" value="{$id}">
                            <div class="row mb-2">
                                <div class="col-md-6 d-flex flex-column justify-content-center">
                                    <label for="slcCategoria0" class="small text-muted">Categoría</label>
                                    <div class="d-flex justify-content-center gap-2 bg-input border rounded-1 pt-1" id="slcCategoria">
                                        <div class="form-check form-check-inline">
                                            <input class="form-check-input" type="radio" name="slcCategoria" id="slcCategoria1" value="1" {$cat1}>
                                            <label class="form-check-label small text-muted" for="slcCategoria1">Inicial</label>
                                        </div>
                                        <div class="form-check form-check-inline me-0">
                                            <input class="form-check-input" type="radio" name="slcCategoria" id="slcCategoria0" value="2" {$cat0}>
                                            <label class="form-check-label small text-muted" for="slcCategoria0">Prorroga</label>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6 d-flex flex-column justify-content-center">
                                    <label for="slcTipo0" class="small text-muted">Tipo</label>
                                    <div class="d-flex justify-content-center gap-2 bg-input border rounded-1 pt-1" id="slcTipo">
                                        <div class="form-check form-check-inline">
                                            <input class="form-check-input" type="radio" name="slcTipo" id="slcTipo1" value="1" {$tip1}>
                                            <label class="form-check-label small text-muted" for="slcTipo1">Común</label>
                                        </div>
                                        <div class="form-check form-check-inline me-0">
                                            <input class="form-check-input" type="radio" name="slcTipo" id="slcTipo0" value="3" {$tip0}>
                                            <label class="form-check-label small text-muted" for="slcTipo0">Laboral</label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="row mb-2">
                                <div class="col-md-5">
                                    <label for="datFecInicia" class="small text-muted">Inicia</label>
                                    <input type="date" class="form-control form-control-sm bg-input" id="datFecInicia" name="datFecInicia" value="{$registro['fec_inicio']}" onchange="DiasIncapacidad()">
                                </div>
                                <div class="col-md-5">
                                    <label for="datFecFin" class="small text-muted">Termina</label>
                                    <input type="date" class="form-control form-control-sm bg-input" id="datFecFin" name="datFecFin" value="{$registro['fec_fin']}" onchange="DiasIncapacidad()">
                                </div>
                                <div class="col-md-2">
                                    <label for="canDias" class="small text-muted">Días</label>
                                    <input type="number" class="form-control form-control-sm bg-input" id="canDias" name="canDias" value="{$registro['can_dias']}" min="0" readonly disabled>
                                </div>
                            </div>
                        </form>
                    </div>
                    <div class="text-center pb-3">
                        <button type="button" class="btn btn-primary btn-sm" id="btnGuardarIncapacidad">Guardar</button>
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
            $sql = "DELETE FROM `nom_incapacidad` WHERE `id_incapacidad` = ?";
            $consulta  = "DELETE FROM `nom_incapacidad` WHERE `id_incapacidad` = $id";
            $stmt = $this->conexion->prepare($sql);
            $stmt->bindParam(1, $id, PDO::PARAM_INT);
            $stmt->execute();
            if ($stmt->rowCount() > 0) {
                Logs::guardaLog($consulta);
                (new Novedades())->delRegistro(1, $id); // Elimina la novedad asociada a la incapacidad
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
            $this->conexion->beginTransaction();

            $sql = "INSERT INTO `nom_incapacidad`
                    (`id_empleado`,`id_tipo`,`fec_inicio`,`fec_fin`,`can_dias`,`categoria`,`fec_reg`)
                VALUES (?, ?, ?, ?, ?, ?, ?)";
            $stmt = $this->conexion->prepare($sql);
            $stmt->bindValue(1, $array['id_empleado'], PDO::PARAM_INT);
            $stmt->bindValue(2, $array['slcTipo'], PDO::PARAM_INT);
            $stmt->bindValue(3, $array['datFecInicia'], PDO::PARAM_STR);
            $stmt->bindValue(4, $array['datFecFin'], PDO::PARAM_STR);
            $stmt->bindValue(5, $array['canDias'], PDO::PARAM_INT);
            $stmt->bindValue(6, $array['slcCategoria'], PDO::PARAM_INT);
            $stmt->bindValue(7, Sesion::Hoy(), PDO::PARAM_STR);
            $stmt->execute();

            $id = $this->conexion->lastInsertId();

            if ($id > 0) {
                $array['novedad'] = $id;
                $array['tipo'] = 1;
                $Novedad = new Novedades($this->conexion);
                $resultado = $Novedad->addRegistro($array);

                if ($resultado === 'si') {
                    $this->conexion->commit();
                    return 'si';
                } else {
                    $this->conexion->rollBack();
                    return $resultado;
                }
            } else {
                $this->conexion->rollBack();
                return 'No se insertó el registro.';
            }
        } catch (PDOException $e) {
            $this->conexion->rollBack();
            return 'Error SQL: ' . $e->getMessage();
        }
    }

    public function addRegistroLiq($array)
    {
        try {
            $sql = "INSERT INTO `nom_liq_incap` 
                        (`id_incapacidad`, `id_eps`, `id_arl`, `dias_liq`, `pago_empresa`, `pago_eps`, `pago_arl`, `id_user_reg`,`fec_reg`, `id_nomina`) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $stmt = $this->conexion->prepare($sql);
            $stmt->bindValue(1, $array['id'], PDO::PARAM_INT);
            $stmt->bindValue(2, $array['id_eps'], PDO::PARAM_INT);
            $stmt->bindValue(3, $array['id_arl'], PDO::PARAM_INT);
            $stmt->bindValue(4, $array['dias'], PDO::PARAM_INT);
            $stmt->bindValue(5, $array['p_empresa'], PDO::PARAM_STR);
            $stmt->bindValue(6, $array['p_eps'], PDO::PARAM_STR);
            $stmt->bindValue(7, $array['p_arl'], PDO::PARAM_STR);
            $stmt->bindValue(8, Sesion::IdUser(), PDO::PARAM_INT);
            $stmt->bindValue(9, Sesion::Hoy(), PDO::PARAM_STR);
            $stmt->bindValue(10, $array['id_nomina'], PDO::PARAM_INT);
            $stmt->execute();

            $id = $this->conexion->lastInsertId();
            if ($id > 0) {
                return 'si';
            } else {
                return 'No se insertó el registro.';
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
            $this->conexion->beginTransaction();
            $sql = "UPDATE `nom_incapacidad`
                    SET `id_tipo` = ?, `fec_inicio` = ?, `fec_fin` = ?, `can_dias` = ?, `categoria` = ?
                WHERE `id_incapacidad` = ?";
            $stmt = $this->conexion->prepare($sql);
            $stmt->bindValue(1, $array['slcTipo'], PDO::PARAM_INT);
            $stmt->bindValue(2, $array['datFecInicia'], PDO::PARAM_STR);
            $stmt->bindValue(3, $array['datFecFin'], PDO::PARAM_STR);
            $stmt->bindValue(4, $array['canDias'], PDO::PARAM_INT);
            $stmt->bindValue(5, $array['slcCategoria'], PDO::PARAM_INT);
            $stmt->bindValue(6, $array['id'], PDO::PARAM_INT);
            if ($stmt->execute() && $stmt->rowCount() > 0) {
                $consulta = "UPDATE `nom_incapacidad` SET `fec_act` = ? WHERE `id_incapacidad` = ?";
                $stmt2 = $this->conexion->prepare($consulta);
                $stmt2->bindValue(1, Sesion::Hoy(), PDO::PARAM_STR);
                $stmt2->bindValue(2, $array['id'], PDO::PARAM_INT);
                $stmt2->execute();
                $Novedad = new Novedades($this->conexion);
                $Novedad->delRegistro(1, $array['id']);
                $array['novedad'] = $array['id'];
                $array['tipo'] = 1; // Tipo de novedad para incapacidades
                $resultado = $Novedad->addRegistro($array);
                if ($resultado === 'si') {
                    $this->conexion->commit();
                    return 'si';
                } else {
                    $this->conexion->rollBack();
                    return $resultado;
                }
            } else {
                $this->conexion->rollBack();
                return 'No se actualizó el registro.';
            }
        } catch (PDOException $e) {
            $this->conexion->rollBack();
            return 'Error SQL: ' . $e->getMessage();
        }
    }

    public function annulRegistro($array)
    {
        return 'Falta programar la anulación de registro de seguridad social.';
    }
}
