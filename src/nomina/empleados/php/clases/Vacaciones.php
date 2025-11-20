<?php

namespace Src\Nomina\Empleados\Php\Clases;

use Config\Clases\Conexion;
use Config\Clases\Logs;
use Config\Clases\Sesion;

use PDO;
use PDOException;

class Vacaciones
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
                $where .= " AND (`nom_vacaciones`.`fec_inicial` LIKE '%{$array['value']}%' 
                            OR `nom_vacaciones`.`fec_fin` LIKE '%{$array['value']}%'
                            OR `nom_vacaciones`.`dias_inactivo` LIKE '%{$array['value']}%'
                            OR `nom_vacaciones`.`dias_habiles` LIKE '%{$array['value']}%'
                            OR `nom_vacaciones`.`corte` LIKE '%{$array['value']}%'
                            OR `nom_vacaciones`.`dias_liquidar` LIKE '%{$array['value']}%')";
            }

            if (isset($array['id']) && $array['id'] > 0) {
                $where .= " AND `nom_vacaciones`.`id_empleado` = {$array['id']}";
            }
        }

        $sql = "SELECT
                    `id_vac`, `anticipo`, `fec_inicial`, `fec_fin`, `dias_inactivo`, `dias_habiles`, `corte`, `dias_liquidar`, `estado`
                FROM
                    `nom_vacaciones`
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
                $where .= " AND (`nom_vacaciones`.`fec_inicial` LIKE '%{$array['value']}%' 
                            OR `nom_vacaciones`.`fec_fin` LIKE '%{$array['value']}%'
                            OR `nom_vacaciones`.`dias_inactivo` LIKE '%{$array['value']}%'
                            OR `nom_vacaciones`.`dias_habiles` LIKE '%{$array['value']}%'
                            OR `nom_vacaciones`.`corte` LIKE '%{$array['value']}%'
                            OR `nom_vacaciones`.`dias_liquidar` LIKE '%{$array['value']}%')";
            }

            if (isset($array['id']) && $array['id'] > 0) {
                $where .= " AND `nom_vacaciones`.`id_empleado` = {$array['id']}";
            }
        }

        $sql = "SELECT
                    COUNT(*) AS `total`
                FROM
                    `nom_vacaciones`
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
                $where .= " AND `nom_vacaciones`.`id_empleado` = {$array['id']}";
            }
        }

        $sql = "SELECT
                   COUNT(*) AS `total`
                FROM
                    `nom_vacaciones`
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

    public function getRegistroLiq($a)
    {
        $sql = "SELECT
                    `nlv`.`id_liq_vac` AS `id`
                    , `nlv`.`val_liq` AS `val_vac`
                    , `nlv`.`val_prima_vac` AS `prima_vac`
                    , `nlv`.`val_bon_recrea` AS `bon_recrea`
                    , `nlv`.`tipo` AS `tipo`
                FROM
                    `nom_liq_vac` AS `nlv`
                    INNER JOIN `nom_vacaciones` AS `nv` 
                        ON (`nlv`.`id_vac` = `nv`.`id_vac`)
                WHERE (`nv`.`id_empleado` = ? AND `nlv`.`id_nomina` = ? AND `nlv`.`estado` = 1)";
        $stmt = $this->conexion->prepare($sql);
        $stmt->bindParam(1, $a['id_empleado'], PDO::PARAM_INT);
        $stmt->bindParam(2, $a['id_nomina'], PDO::PARAM_INT);
        $stmt->execute();
        $data = $stmt->fetch(PDO::FETCH_ASSOC);
        $stmt->closeCursor();
        unset($stmt);
        return !empty($data) ? $data : ['id' => 0, 'val_vac' => 0, 'prima_vac' => 0, 'bon_recrea' => 0, 'tipo' => 'S'];
    }

    public function getRegistro($id)
    {
        $sql = "SELECT
                `id_vac`, `anticipo`, `fec_inicial`, `fec_fin`, `dias_inactivo`, `dias_habiles`, `corte`, `dias_liquidar`, `estado`
            FROM
                `nom_vacaciones`
            WHERE (`id_vac` = $id)";
        $stmt = $this->conexion->prepare($sql);
        $stmt->bindParam(1, $id, PDO::PARAM_INT);
        $stmt->execute();
        $registro = $stmt->fetch(PDO::FETCH_ASSOC);
        $stmt->closeCursor();
        unset($stmt);
        if (empty($registro)) {
            $registro = [
                'id_vac' => 0,
                'anticipo' => 2, // 1: Si, 2: No
                'fec_inicial' => Sesion::_Hoy(),
                'fec_fin' => '',
                'dias_inactivo' => 0,
                'dias_habiles' => 0,
                'corte' => Sesion::_Hoy(),
                'dias_liquidar' => 0,
                'estado' => 1,
            ];
        }
        return $registro;
    }

    public function getRegistroPorEmpleado($inicia, $fin)
    {
        $sql = "SELECT
                    `nom_vacaciones`.`id_vac`
                    , `nom_vacaciones`.`id_empleado`
                    , `nom_vacaciones`.`fec_inicial`
                    , `nom_vacaciones`.`fec_inicio`
                    , `nom_vacaciones`.`fec_fin`
                    , `nom_vacaciones`.`dias_inactivo`
                    , `nom_vacaciones`.`dias_habiles`
                    , `nom_vacaciones`.`corte`
                    , `nom_vacaciones`.`dias_liquidar`
                    , IFNULL(`liquidado`.`dias_liqs`,0) AS `liq`
                    , IFNULL(`calendario`.`dias`,0) AS `dias`
                FROM `nom_vacaciones`
                    LEFT JOIN 
                        (SELECT
                            `id_vac`, SUM(`dias_liqs`) AS `dias_liqs`
                        FROM
                            `nom_liq_vac`
                        WHERE (`estado` = 1)
                        GROUP BY `id_vac`) AS `liquidado`
                        ON (`liquidado`.`id_vac` = `nom_vacaciones`.`id_vac`)
                    LEFT JOIN
                        (SELECT
                            `id_novedad`
                            , COUNT(`id_novedad`) AS `dias`
                        FROM
                            `nom_calendar_novedad`
                        WHERE (`id_tipo` = 2 AND `fecha` BETWEEN ? AND ?)
                        GROUP BY `id_novedad`, `id_empleado`) AS `calendario`
                        ON (`nom_vacaciones`.`id_vac` = `calendario`.`id_novedad`) 
                WHERE `estado` = 1";
        $stmt = $this->conexion->prepare($sql);
        $stmt->bindParam(1, $inicia, PDO::PARAM_STR);
        $stmt->bindParam(2, $fin, PDO::PARAM_STR);
        $stmt->execute();
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $stmt->closeCursor();
        unset($stmt);

        $index = [];
        foreach ($data as $row) {
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
        $ant1 = $registro['anticipo'] == 1 ? 'checked' : '';
        $ant2 = $registro['anticipo'] == 2 ? 'checked' : '';
        $html =
            <<<HTML
                <div class="shadow text-center rounded">
                    <div class="rounded-top py-2" style="background-color: #16a085 !important;">
                        <h5 style="color: white;" class="mb-0">GESTIÓN DE VACACIONES</h5>
                    </div>
                    <div class="p-3">
                        <form id="formVacaciones">
                            <input type="hidden" id="id" name="id" value="{$id}">
                            <div class="row mb-2">
                                <div class="col-md-6">
                                    <label for="datFecCorte" class="small text-muted">Fecha corte</label>
                                    <input type="date" class="form-control form-control-sm bg-input" id="datFecCorte" name="datFecCorte" value="{$registro['corte']}">
                                </div>
                                <div class="col-md-6">
                                    <label for="diasLiquidar" class="small text-muted">Dias a liquidar</label>
                                    <input type="number" class="form-control form-control-sm bg-input text-end" id="diasLiquidar" name="diasLiquidar" value="{$registro['dias_liquidar']}" min="0">
                                </div>
                            </div>
                            <div class="row mb-2">
                                <div class="col-md-6 d-flex flex-column justify-content-center">
                                    <label for="slcAnticipada2" class="small text-muted">Anticipadas</label>
                                    <div class="d-flex justify-content-center gap-2 bg-input border rounded-1 pt-1" id="slcAnticipada">
                                        <div class="form-check form-check-inline">
                                            <input class="form-check-input" type="radio" name="slcAnticipada" id="slcAnticipada1" value="1" {$ant1}>
                                            <label class="form-check-label small text-muted" for="slcAnticipada1">Si</label>
                                        </div>
                                        <div class="form-check form-check-inline me-0">
                                            <input class="form-check-input" type="radio" name="slcAnticipada" id="slcAnticipada2" value="2" {$ant2}>
                                            <label class="form-check-label small text-muted" for="slcAnticipada2">No</label>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <label for="diasInactivo" class="small text-muted">Días inactivo</label>
                                    <input type="number" class="form-control form-control-sm text-end" id="diasInactivo" name="diasInactivo" value="{$registro['dias_inactivo']}" min="0" disabled readonly>
                                </div>
                                <div class="col-md-3">
                                    <label for="diasHabiles" class="small text-muted">Días hábiles</label>
                                    <input type="number" class="form-control form-control-sm bg-input text-end" id="diasHabiles" name="diasHabiles" value="{$registro['dias_habiles']}" min="0">
                                </div>
                            </div>
                            <div class="row mb-2">
                                <div class="col-md-6">
                                    <label for="datFecInicia" class="small text-muted">Inicia</label>
                                    <input type="date" class="form-control form-control-sm bg-input" id="datFecInicia" name="datFecInicia" value="{$registro['fec_inicial']}" onchange="DiasInactivo()">
                                </div>
                                <div class="col-md-6">
                                    <label for="datFecFin" class="small text-muted">Termina</label>
                                    <input type="date" class="form-control form-control-sm bg-input" id="datFecFin" name="datFecFin" value="{$registro['fec_fin']}" onchange="DiasInactivo()">
                                </div>
                            </div>
                        </form>
                    </div>
                    <div class="text-center pb-3">
                        <button type="button" class="btn btn-primary btn-sm" id="btnGuardarVacacion">Guardar</button>
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
            $sql = "DELETE FROM `nom_vacaciones` WHERE `id_vac` = ?";
            $consulta  = "DELETE FROM `nom_vacaciones` WHERE `id_vac` = $id";
            $stmt = $this->conexion->prepare($sql);
            $stmt->bindParam(1, $id, PDO::PARAM_INT);
            $stmt->execute();
            if ($stmt->rowCount() > 0) {
                Logs::guardaLog($consulta);
                (new Novedades())->delRegistro(2, $id);
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
            // iniciar transacción
            $this->conexion->beginTransaction();
            $sql = "INSERT INTO `nom_vacaciones`
                        (`id_empleado`,`anticipo`,`fec_inicial`,`fec_fin`,`dias_inactivo`,`dias_habiles`,`corte`,`dias_liquidar`,`estado`,`fec_reg`)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $stmt = $this->conexion->prepare($sql);
            $stmt->bindValue(1, $array['id_empleado'], PDO::PARAM_INT);
            $stmt->bindValue(2, $array['slcAnticipada'], PDO::PARAM_INT);
            $stmt->bindValue(3, $array['datFecInicia'], PDO::PARAM_STR);
            $stmt->bindValue(4, $array['datFecFin'], PDO::PARAM_STR);
            $stmt->bindValue(5, $array['diasInactivo'], PDO::PARAM_INT);
            $stmt->bindValue(6, $array['diasHabiles'], PDO::PARAM_INT);
            $stmt->bindValue(7, $array['datFecCorte'], PDO::PARAM_STR);
            $stmt->bindValue(8, $array['diasLiquidar'], PDO::PARAM_INT);
            $stmt->bindValue(9, 1, PDO::PARAM_INT);
            $stmt->bindValue(10, Sesion::Hoy(), PDO::PARAM_STR);

            $stmt->execute();
            $id = $this->conexion->lastInsertId();
            if ($id > 0) {
                $array['novedad'] = $id;
                $array['tipo'] = 2;
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
                return 'No se insertó el registro';
            }
        } catch (PDOException $e) {
            $this->conexion->rollBack();
            return 'Error SQL: ' . $e->getMessage();
        }
    }

    public function addRegistroLiq($d)
    {
        try {
            $sql = "INSERT INTO `nom_liq_vac`
                        (`id_vac`,`sal_base`,`g_rep`,`aux_tra`,`aux_alim`,`bsp_ant`,`psv_ant`,`dias_liqs`,`val_liq`,`val_prima_vac`,`val_bon_recrea`,`id_user_reg`,`fec_reg`,`id_nomina`)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $stmt = $this->conexion->prepare($sql);
            $stmt->bindValue(1, $d['idvac'], PDO::PARAM_INT);
            $stmt->bindValue(2, $d['salbas'], PDO::PARAM_STR);
            $stmt->bindValue(3, $d['grepre'], PDO::PARAM_STR);
            $stmt->bindValue(4, $d['auxtra'], PDO::PARAM_STR);
            $stmt->bindValue(5, $d['auxali'], PDO::PARAM_STR);
            $stmt->bindValue(6, $d['bspant'], PDO::PARAM_STR);
            $stmt->bindValue(7, $d['psvant'], PDO::PARAM_STR);
            $stmt->bindValue(8, $d['dhabiles'], PDO::PARAM_STR);
            $stmt->bindValue(9, $d['vacacion'], PDO::PARAM_STR);
            $stmt->bindValue(10, $d['prima_vac'], PDO::PARAM_STR);
            $stmt->bindValue(11, $d['bonrecrea'], PDO::PARAM_STR);
            $stmt->bindValue(12, Sesion::IdUser(), PDO::PARAM_INT);
            $stmt->bindValue(13, Sesion::Hoy(), PDO::PARAM_STR);
            $stmt->bindValue(14, $d['id_nomina'], PDO::PARAM_INT);
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

    public function editRegistroLiq($d)
    {
        try {
            $sql = "UPDATE `nom_liq_vac`
                        SET `val_liq` = ?, `val_prima_vac` = ?, `val_bon_recrea` = ?, `tipo` = ?
                    WHERE `id_liq_vac` = ?";
            $stmt = $this->conexion->prepare($sql);
            $stmt->bindValue(1, $d['val_vac'], PDO::PARAM_STR);
            $stmt->bindValue(2, $d['prima_vac'], PDO::PARAM_STR);
            $stmt->bindValue(3, $d['bon_recrea'], PDO::PARAM_STR);
            $stmt->bindValue(4, $d['tipo'], PDO::PARAM_INT);
            $stmt->bindValue(5, $d['id'], PDO::PARAM_INT);

            if ($stmt->execute() && $stmt->rowCount() > 0) {
                $consulta = "UPDATE `nom_liq_vac` SET `fec_act` = ?, `id_user_act` = ? WHERE `id_liq_vac` = ?";
                $stmt2 = $this->conexion->prepare($consulta);
                $stmt2->bindValue(1, Sesion::Hoy(), PDO::PARAM_STR);
                $stmt2->bindValue(2, Sesion::IdUser(), PDO::PARAM_INT);
                $stmt2->bindValue(3, $d['id'], PDO::PARAM_INT);
                $stmt2->execute();
                return 'si';
            } else {
                return 'No se actualizó el registro.';
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
            $sql = "UPDATE `nom_vacaciones`
                        SET `anticipo` = ?, `fec_inicial` = ?, `fec_fin` = ?, `dias_inactivo` = ?, `dias_habiles` = ?, `corte` = ?, `dias_liquidar` = ?
                    WHERE `id_vac` = ?";
            $stmt = $this->conexion->prepare($sql);
            $stmt->bindValue(1, $array['slcAnticipada'], PDO::PARAM_INT);
            $stmt->bindValue(2, $array['datFecInicia'], PDO::PARAM_STR);
            $stmt->bindValue(3, $array['datFecFin'], PDO::PARAM_STR);
            $stmt->bindValue(4, $array['diasInactivo'], PDO::PARAM_INT);
            $stmt->bindValue(5, $array['diasHabiles'], PDO::PARAM_INT);
            $stmt->bindValue(6, $array['datFecCorte'], PDO::PARAM_STR);
            $stmt->bindValue(7, $array['diasLiquidar'], PDO::PARAM_INT);
            $stmt->bindValue(8, $array['id'], PDO::PARAM_INT);

            if ($stmt->execute() && $stmt->rowCount() > 0) {
                $consulta = "UPDATE `nom_vacaciones` SET `fec_act` = ? WHERE `id_vac` = ?";
                $stmt2 = $this->conexion->prepare($consulta);
                $stmt2->bindValue(1, Sesion::Hoy(), PDO::PARAM_STR);
                $stmt2->bindValue(2, $array['id'], PDO::PARAM_INT);
                $stmt2->execute();
                $Novedad = new Novedades($this->conexion);
                $Novedad->delRegistro(2, $array['id']);
                $array['novedad'] = $array['id'];
                $array['tipo'] = 2;
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
        return 'Falta programar la anulación de registro.';
    }
    public function upEstado($id, $estado = 2)
    {
        try {
            $sql = "UPDATE `nom_liq_vac`
                        SET `estado` = ?
                    WHERE `id_vac` = ?";
            $stmt = $this->conexion->prepare($sql);
            $stmt->bindValue(1, $estado, PDO::PARAM_INT);
            $stmt->bindValue(2, $id, PDO::PARAM_INT);
            $stmt->execute();
            if ($stmt->rowCount() > 0) {
                $consulta = "UPDATE `nom_liq_vac` SET `fec_act` = ?, `id_user_act` = ? WHERE `id_vac` = ?";
                $stmt2 = $this->conexion->prepare($consulta);
                $stmt2->bindValue(1, Sesion::Hoy(), PDO::PARAM_STR);
                $stmt2->bindValue(2, Sesion::IdUser(), PDO::PARAM_INT);
                $stmt2->bindValue(3, $id, PDO::PARAM_INT);
                $stmt2->execute();
                return 'si';
            } else {
                return 'No se actualizó el registro.';
            }
        } catch (PDOException $e) {
            return 'Error SQL: ' . $e->getMessage();
        }
    }
}
