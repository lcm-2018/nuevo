<?php

namespace Src\Nomina\Empleados\Php\Clases;

use Config\Clases\Conexion;
use Config\Clases\Logs;
use Config\Clases\Sesion;

use PDO;
use PDOException;
use Src\Common\Php\Clases\Combos;

/**
 * Clase para gestionar las embargos de los empleados.
 *
 * Esta clase permite realizar operaciones CRUD sobre las embargos de los empleados,
 * incluyendo la obtención de registros, adición, edición y eliminación de registros.
 */
class Embargos
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
                $where .= " AND (`nom_tipo_embargo`.`tipo` LIKE '%{$array['value']}%' 
                            OR `tb_terceros`.`nom_tercero` LIKE '%{$array['value']}%'
                            OR `tb_terceros`.`nit_tercero` LIKE '%{$array['value']}%'
                            OR `nom_embargos`.`fec_inicio` LIKE '%{$array['value']}%'
                            OR `nom_embargos`.`fec_fin` LIKE '%{$array['value']}%')";
            }

            if (isset($array['id']) && $array['id'] > 0) {
                $where .= " AND `nom_embargos`.`id_empleado` = {$array['id']}";
            }
        }

        $sql = "SELECT
                    `nom_embargos`.`id_embargo`
                    , `nom_embargos`.`id_juzgado`
                    , `nom_embargos`.`id_empleado`
                    , `nom_embargos`.`tipo_embargo`
                    , `nom_tipo_embargo`.`tipo`
                    , `tb_terceros`.`nom_tercero`
                    , `tb_terceros`.`nit_tercero`
                    , `nom_embargos`.`valor_total`
                    , `nom_embargos`.`dcto_max`
                    , `nom_embargos`.`valor_mes`
                    , `nom_embargos`.`porcentaje`
                    , `nom_embargos`.`fec_inicio`
                    , `nom_embargos`.`fec_fin`
                    , `nom_embargos`.`estado`
                    ,  IFNULL(`pagos`.`valor`, 0) AS `pagado`
                FROM
                    `nom_embargos`
                    INNER JOIN `nom_terceros` 
                        ON (`nom_embargos`.`id_juzgado` = `nom_terceros`.`id_tn`)
                    INNER JOIN `nom_tipo_embargo` 
                        ON (`nom_embargos`.`tipo_embargo` = `nom_tipo_embargo`.`id_tipo_emb`)
                    LEFT JOIN `tb_terceros` 
                        ON (`nom_terceros`.`id_tercero_api` = `tb_terceros`.`id_tercero_api`)
                    LEFT JOIN 
                        (SELECT
                            `id_embargo`
                            , SUM(`val_mes_embargo`) AS `valor`
                        FROM
                            `nom_liq_embargo`
                        GROUP BY `id_embargo`) AS `pagos` 
                        ON (`pagos`.`id_embargo` = `nom_embargos`.`id_embargo`)
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
                $where .= " AND (`nom_tipo_embargo`.`tipo` LIKE '%{$array['value']}%' 
                            OR `tb_terceros`.`nom_tercero` LIKE '%{$array['value']}%'
                            OR `tb_terceros`.`nit_tercero` LIKE '%{$array['value']}%'
                            OR `nom_embargos`.`fec_inicio` LIKE '%{$array['value']}%'
                            OR `nom_embargos`.`fec_fin` LIKE '%{$array['value']}%')";
            }

            if (isset($array['id']) && $array['id'] > 0) {
                $where .= " AND `nom_embargos`.`id_empleado` = {$array['id']}";
            }
        }

        $sql = "SELECT
                    COUNT(*) AS `total`
                FROM
                    `nom_embargos`
                    INNER JOIN `nom_terceros` 
                        ON (`nom_embargos`.`id_juzgado` = `nom_terceros`.`id_tn`)
                    LEFT JOIN `tb_terceros` 
                        ON (`nom_terceros`.`id_tercero_api` = `tb_terceros`.`id_tercero_api`)
                    INNER JOIN `nom_tipo_embargo` 
                        ON (`nom_embargos`.`tipo_embargo` = `nom_tipo_embargo`.`id_tipo_emb`)
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
                $where .= " AND `nom_embargos`.`id_empleado` = {$array['id']}";
            }
        }

        $sql = "SELECT
                    COUNT(*) AS `total`
                FROM
                    `nom_embargos`
                    INNER JOIN `nom_terceros` 
                        ON (`nom_embargos`.`id_juzgado` = `nom_terceros`.`id_tn`)
                    LEFT JOIN `tb_terceros` 
                        ON (`nom_terceros`.`id_tercero_api` = `tb_terceros`.`id_tercero_api`)
                    INNER JOIN `nom_tipo_embargo` 
                        ON (`nom_embargos`.`tipo_embargo` = `nom_tipo_embargo`.`id_tipo_emb`)
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
                    `id_embargo`, `id_juzgado`, `tipo_embargo`, `valor_total`, `dcto_max`, `valor_mes`, `porcentaje`, `fec_inicio`, `fec_fin`
                FROM `nom_embargos`
                WHERE `id_embargo` = ?";
        $stmt = $this->conexion->prepare($sql);
        $stmt->bindParam(1, $id, PDO::PARAM_INT);
        $stmt->execute();
        $registro = $stmt->fetch(PDO::FETCH_ASSOC);
        $stmt->closeCursor();
        unset($stmt);
        if (empty($registro)) {
            $registro = [
                'id_embargo' => 0,
                'id_juzgado' => 0,
                'tipo_embargo' => 0,
                'valor_total' => 0,
                'dcto_max' => 0,
                'valor_mes' => 0,
                'porcentaje' => 0,
                'fec_inicio' => date('Y-m-d'),
                'fec_fin' => '',
            ];
        }
        return $registro;
    }

    public function getRegistroPorEmpleado($inicia)
    {
        $sql = "SELECT
                    `id_embargo`, `id_empleado`, `tipo_embargo`, `valor_mes`, `porcentaje`, `fec_inicio`, `fec_fin`
                FROM `nom_embargos`
                WHERE `estado` = 1 
                    AND `fec_inicio` <= ? 
                    AND (`fec_fin` >= ? OR `fec_fin` IS NULL)";
        $stmt = $this->conexion->prepare($sql);
        $stmt->bindParam(1, $inicia, PDO::PARAM_STR);
        $stmt->bindParam(2, $inicia, PDO::PARAM_STR);
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
        $juzgados = Empleados::getTerceroNomina('juz', $registro['id_juzgado']);
        $tipo = self::getTiposEmbargos($registro['tipo_embargo']);
        $html =
            <<<HTML
                <div class="shadow text-center rounded">
                    <div class="rounded-top py-2" style="background-color: #16a085 !important;">
                        <h5 style="color: white;" class="mb-0">GESTIÓN DE EMBARGOS</h5>
                    </div>
                    <div class="p-3">
                        <form id="formEmbargo">
                            <input type="hidden" id="id" name="id" value="{$id}">
                            <div class="row mb-2">
                                <div class="col-md-6">
                                    <label for="slcJuzgado" class="small text-muted">Juzgado</label>
                                    <select class="form-select form-select-sm bg-input" id="slcJuzgado" name="slcJuzgado">
                                        {$juzgados}
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label for="slcTpEmbargo" class="small text-muted">Tipo de Embargo</label>
                                    <select class="form-select form-select-sm bg-input" id="slcTpEmbargo" name="slcTpEmbargo" onchange="LiberaTotal(value); CalcDctoMax()">
                                        {$tipo}
                                    </select>
                                </div>
                            </div>
                            <div class="row mb-2">
                                <div class="col-md-6">
                                    <label for="numTotLib" class="small text-muted">Total</label>
                                    <input type="number" class="form-control form-control-sm bg-input text-end" id="numTotLib" name="numTotLib" value="{$registro['valor_total']}" min="0" onblur="CalcDctoMax()">
                                </div>
                                <div class="col-md-6">
                                    <label for="numDctoMax" class="small text-muted">Dcto. Máximo</label>
                                    <input type="number" class="form-control form-control-sm bg-input text-end" id="numDctoMax" name="numDctoMax" value="{$registro['dcto_max']}" min="0" readonly disabled>
                                </div>
                            </div>
                            <div class="row mb-2">
                                <div class="col-md-6">
                                    <label for="numValMes" class="small text-muted">Valor Mes</label>
                                    <input type="number" class="form-control form-control-sm bg-input text-end" id="numValMes" name="numValMes" value="{$registro['valor_mes']}" min="0" onblur="CalcValorPorcentaje()">
                                </div>
                                <div class="col-md-6">
                                    <label for="numPorcentaje" class="small text-muted">Porcentaje</label>
                                    <input type="number" class="form-control form-control-sm bg-input text-end" id="numPorcentaje" name="numPorcentaje" value="{$registro['porcentaje']}" min="0" onblur="CalcValorMes()">
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
                        <button type="button" class="btn btn-primary btn-sm" id="btnGuardarEmbargo">Guardar</button>
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
            $sql = "DELETE FROM `nom_embargos` WHERE `id_embargo` = ?";
            $consulta  = "DELETE FROM `nom_embargos` WHERE `id_embargo` = $id";
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
            $sql = "INSERT INTO `nom_embargos`
                        (`id_juzgado`,`id_empleado`,`tipo_embargo`,`valor_total`,`dcto_max`,`valor_mes`,`porcentaje`,`fec_inicio`,`fec_fin`,`estado`,`fec_reg`)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $stmt = $this->conexion->prepare($sql);
            $stmt->bindValue(1, $array['slcJuzgado'], PDO::PARAM_INT);
            $stmt->bindValue(2, $array['id_empleado'], PDO::PARAM_INT);
            $stmt->bindValue(3, $array['slcTpEmbargo'], PDO::PARAM_INT);
            $stmt->bindValue(4, $array['numTotLib'], PDO::PARAM_STR);
            $stmt->bindValue(5, $array['numDctoMax'], PDO::PARAM_STR);
            $stmt->bindValue(6, $array['numValMes'], PDO::PARAM_STR);
            $stmt->bindValue(7, $array['numPorcentaje'], PDO::PARAM_STR);
            $stmt->bindValue(8, $array['datFecInicia'], PDO::PARAM_STR);
            $stmt->bindValue(9, $array['datFecFin'], PDO::PARAM_STR);
            $stmt->bindValue(10, 1, PDO::PARAM_INT);
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

    public function getRegistroLiq($array)
    {
        try {
            $sql = "SELECT
                        `nle`.`id_embargo` AS `id`
                    FROM
                        `nom_liq_embargo` AS `nle`
                        INNER JOIN `nom_embargos` AS `ne` 
                            ON (`nle`.`id_embargo` = `ne`.`id_embargo`)
                    WHERE (`ne`.`id_empleado` = ? AND `nle`.`id_nomina` = ? AND `nle`.`estado` = 1)";
            $stmt = $this->conexion->prepare($sql);
            $stmt->bindValue(1, $array['id_empleado'], PDO::PARAM_INT);
            $stmt->bindValue(2, $array['id_nomina'], PDO::PARAM_INT);
            $stmt->execute();
            $data = $stmt->fetch(PDO::FETCH_ASSOC);
            $stmt->closeCursor();
            unset($stmt);
            return !empty($data) ? $data['id'] : 0;
        } catch (PDOException $e) {
            return 'Error SQL: ' . $e->getMessage();
        }
    }

    public function addRegistroLiq($array)
    {
        try {
            $sql = "INSERT INTO `nom_liq_embargo`
                        (`id_embargo`,`val_mes_embargo`,`fec_reg`,`id_user_reg`,`id_nomina`)
                    VALUES (?, ?, ?, ?, ?)";
            $stmt = $this->conexion->prepare($sql);
            $stmt->bindValue(1, $array['id_embargo'], PDO::PARAM_INT);
            $stmt->bindValue(2, $array['val_mes'], PDO::PARAM_STR);
            $stmt->bindValue(3, Sesion::Hoy(), PDO::PARAM_STR);
            $stmt->bindValue(4, Sesion::IdUser(), PDO::PARAM_INT);
            $stmt->bindValue(5, $array['id_nomina'], PDO::PARAM_INT);
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
    public function editRegistroLiq($array)
    {
        try {
            $sql = "UPDATE `nom_liq_embargo`
                        SET `val_mes_embargo` = ?
                    WHERE `id_liq_embargo` = ?";
            $stmt = $this->conexion->prepare($sql);
            $stmt->bindValue(1, $array['valor_embargo'], PDO::PARAM_STR);
            $stmt->bindValue(2, $array['id'], PDO::PARAM_INT);

            if ($stmt->execute() && $stmt->rowCount() > 0) {
                $consulta = "UPDATE `nom_liq_embargo` SET `fec_act` = ? WHERE `id_liq_embargo` = ?";
                $stmt2 = $this->conexion->prepare($consulta);
                $stmt2->bindValue(1, Sesion::Hoy(), PDO::PARAM_STR);
                $stmt2->bindValue(2, $array['id'], PDO::PARAM_INT);
                $stmt2->execute();
                return 'si';
            } else {
                return 'no';
            }
        } catch (PDOException $e) {
            return 'Error SQL: ' . $e->getMessage();
        }
    }
    public function editRegistro($array)
    {
        try {
            $sql = "UPDATE `nom_embargos`
                        SET `id_juzgado` = ?, `tipo_embargo` = ?, `valor_total` = ?, `dcto_max` = ?, `valor_mes` = ?, `porcentaje` = ?, `fec_inicio` = ?, `fec_fin` = ?
                    WHERE `id_embargo` = ?";
            $stmt = $this->conexion->prepare($sql);
            $stmt->bindValue(1, $array['slcJuzgado'], PDO::PARAM_INT);
            $stmt->bindValue(2, $array['slcTpEmbargo'], PDO::PARAM_INT);
            $stmt->bindValue(3, $array['numTotLib'], PDO::PARAM_STR);
            $stmt->bindValue(4, $array['numDctoMax'], PDO::PARAM_STR);
            $stmt->bindValue(5, $array['numValMes'], PDO::PARAM_STR);
            $stmt->bindValue(6, $array['numPorcentaje'], PDO::PARAM_STR);
            $stmt->bindValue(7, $array['datFecInicia'], PDO::PARAM_STR);
            $stmt->bindValue(8, $array['datFecFin'], PDO::PARAM_STR);
            $stmt->bindValue(9, $array['id'], PDO::PARAM_INT);

            if ($stmt->execute() && $stmt->rowCount() > 0) {
                $consulta = "UPDATE `nom_embargos` SET `fec_act` = ? WHERE `id_embargo` = ?";
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
            $sql = "UPDATE `nom_embargos`
                        SET `estado` = ?
                    WHERE `id_embargo` = ?";
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
        return 'Falta programar la anulación de registro de seguridad social.';
    }

    public static function getTiposEmbargos($id)
    {
        $sql = "SELECT `id_tipo_emb`, `tipo` FROM `nom_tipo_embargo` ORDER BY `tipo` ASC";
        $combos = new Combos();
        return $combos->setConsulta($sql, $id);
    }

    /**
     * Obtiene los embargos de una nómina agrupados por juzgado
     *
     * @param int $id_nomina ID de la nómina
     * @return array Datos de embargos agrupados por juzgado
     */
    public function getEmbargosPorNomina($id_nomina)
    {
        try {
            $sql = "SELECT
                        IFNULL(`tb_terceros`.`nit_tercero`, '') AS `nit_entidad`,
                        IFNULL(`tb_terceros`.`nom_tercero`, '') AS `nom_entidad`,
                        `nom_tipo_embargo`.`tipo` AS `tipo_embargo`,
                        `nom_empleado`.`no_documento`,
                        CONCAT_WS(' ', `nom_empleado`.`nombre1`, `nom_empleado`.`nombre2`, `nom_empleado`.`apellido1`, `nom_empleado`.`apellido2`) AS `nombre_empleado`,
                        `nom_liq_embargo`.`val_mes_embargo` AS `valor`
                    FROM
                        `nom_liq_embargo`
                        INNER JOIN `nom_embargos` 
                            ON (`nom_liq_embargo`.`id_embargo` = `nom_embargos`.`id_embargo`)
                        INNER JOIN `nom_terceros` 
                            ON (`nom_embargos`.`id_juzgado` = `nom_terceros`.`id_tn`)
                        LEFT JOIN `tb_terceros` 
                            ON (`nom_terceros`.`id_tercero_api` = `tb_terceros`.`id_tercero_api`)
                        INNER JOIN `nom_tipo_embargo` 
                            ON (`nom_embargos`.`tipo_embargo` = `nom_tipo_embargo`.`id_tipo_emb`)
                        INNER JOIN `nom_empleado` 
                            ON (`nom_embargos`.`id_empleado` = `nom_empleado`.`id_empleado`)
                    WHERE 
                        `nom_liq_embargo`.`id_nomina` = ?
                        AND `nom_liq_embargo`.`estado` = 1
                        AND `nom_liq_embargo`.`val_mes_embargo` > 0
                    ORDER BY 
                        `nom_entidad` ASC, 
                        `nombre_empleado` ASC";

            $stmt = $this->conexion->prepare($sql);
            $stmt->bindValue(1, $id_nomina, PDO::PARAM_INT);
            $stmt->execute();
            $datos = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $stmt->closeCursor();
            unset($stmt);

            // Agrupar por entidad (juzgado)
            $agrupado = [];
            foreach ($datos as $dato) {
                $nit = $dato['nit_entidad'];
                if (!isset($agrupado[$nit])) {
                    $agrupado[$nit] = [
                        'nit_entidad' => $dato['nit_entidad'],
                        'nom_entidad' => $dato['nom_entidad'],
                        'empleados' => [],
                        'total' => 0
                    ];
                }
                $agrupado[$nit]['empleados'][] = [
                    'no_documento' => $dato['no_documento'],
                    'nombre_empleado' => $dato['nombre_empleado'],
                    'tipo_embargo' => $dato['tipo_embargo'],
                    'valor' => $dato['valor']
                ];
                $agrupado[$nit]['total'] += $dato['valor'];
            }

            return array_values($agrupado);
        } catch (PDOException $e) {
            return [];
        }
    }
}
