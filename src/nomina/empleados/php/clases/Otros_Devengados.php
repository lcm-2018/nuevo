<?php

namespace Src\Nomina\Empleados\Php\Clases;

use Config\Clases\Conexion;
use Config\Clases\Logs;
use Config\Clases\Sesion;

use PDO;
use PDOException;
use Src\Common\Php\Clases\Combos;

/**
 * Clase para gestionar otros devengados de los empleados.
 *
 * Esta clase permite realizar operaciones CRUD sobre otros devengados de los empleados,
 * incluyendo la obtención de registros, adición, edición y eliminación de otros devengados.
 */
class Otros_Devengados
{
    private $conexion;

    public function __construct($conexion = null)
    {
        $this->conexion = $conexion ?: Conexion::getConexion();
    }

    /**
     * Obtiene los datos para la DataTable.
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
                $where .= " AND (`nom_tipo_devengado`.`descripcion` LIKE '%{$array['value']}%'
                            OR `nom_otros_devengados`.`concepto` LIKE '%{$array['value']}%'
                            OR `nom_otros_devengados`.`fec_inicia` LIKE '%{$array['value']}%'
                            OR `nom_otros_devengados`.`fec_fin` LIKE '%{$array['value']}%'
                            OR `nom_otros_devengados`.`valor` LIKE '%{$array['value']}%')";
            }

            if (isset($array['id']) && $array['id'] > 0) {
                $where .= " AND `nom_otros_devengados`.`id_empleado` = {$array['id']}";
            }
        }

        $sql = "SELECT
                    `nom_otros_devengados`.`id_devengado`
                    , `nom_otros_devengados`.`id_empleado`
                    , `nom_otros_devengados`.`id_tipo`
                    , `nom_tipo_devengado`.`descripcion`
                    , `nom_otros_devengados`.`fec_inicia`
                    , `nom_otros_devengados`.`fec_fin`
                    , `nom_otros_devengados`.`concepto`
                    , `nom_otros_devengados`.`valor`
                    , `nom_otros_devengados`.`estado`
                    , IFNULL(`aportado`.`valor`, 0) AS `aportado`
                FROM
                    `nom_otros_devengados`
                    INNER JOIN `nom_tipo_devengado`
                        ON (`nom_otros_devengados`.`id_tipo` = `nom_tipo_devengado`.`id_tipo`)
                    LEFT JOIN
                        (SELECT
                            `id_devengado`, SUM(`valor`) AS `valor`
                        FROM
                            `nom_liq_devengado`
                        GROUP BY `id_devengado`) AS `aportado`
                        ON (`nom_otros_devengados`.`id_devengado` = `aportado`.`id_devengado`)
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
     */
    public function getRegistrosFilter($array)
    {
        $where = '';
        if (!empty($array)) {
            if (isset($array['value']) && $array['value'] != '') {
                $where .= " AND (`nom_tipo_devengado`.`descripcion` LIKE '%{$array['value']}%'
                            OR `nom_otros_devengados`.`concepto` LIKE '%{$array['value']}%'
                            OR `nom_otros_devengados`.`fec_inicia` LIKE '%{$array['value']}%'
                            OR `nom_otros_devengados`.`fec_fin` LIKE '%{$array['value']}%'
                            OR `nom_otros_devengados`.`valor` LIKE '%{$array['value']}%')";
            }

            if (isset($array['id']) && $array['id'] > 0) {
                $where .= " AND `nom_otros_devengados`.`id_empleado` = {$array['id']}";
            }
        }

        $sql = "SELECT
                    COUNT(*) AS `total`
                FROM
                    `nom_otros_devengados`
                    INNER JOIN `nom_tipo_devengado`
                        ON (`nom_otros_devengados`.`id_tipo` = `nom_tipo_devengado`.`id_tipo`)
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
     */
    public function getRegistrosTotal($array)
    {
        $where = '';
        if (!empty($array)) {
            if (isset($array['id']) && $array['id'] > 0) {
                $where .= " AND `nom_otros_devengados`.`id_empleado` = {$array['id']}";
            }
        }

        $sql = "SELECT
                    COUNT(*) AS `total`
                FROM
                    `nom_otros_devengados`
                    INNER JOIN `nom_tipo_devengado`
                        ON (`nom_otros_devengados`.`id_tipo` = `nom_tipo_devengado`.`id_tipo`)
                WHERE (1 = 1 $where)";
        $stmt = $this->conexion->prepare($sql);
        $stmt->execute();
        $data = $stmt->fetch(PDO::FETCH_ASSOC)['total'] ?: 0;
        $stmt->closeCursor();
        unset($stmt);
        return $data;
    }

    /**
     * Obtiene un registro por ID, incluyendo los rubros del tipo asociado.
     */
    public function getRegistro($id)
    {
        $sql = "SELECT
                    `nod`.`id_devengado`
                    , `nod`.`id_empleado`
                    , `nod`.`id_tipo`
                    , `nod`.`fec_inicia`
                    , `nod`.`fec_fin`
                    , `nod`.`concepto`
                    , `nod`.`valor`
                    , `nod`.`estado`
                    , `ntd`.`r_admin`
                    , `ntd`.`r_oper`
                    , IFNULL(CONCAT_WS(' - ', `pc_a`.`cod_pptal`, `pc_a`.`nom_rubro`), '') AS `nom_admin`
                    , IFNULL(CONCAT_WS(' - ', `pc_o`.`cod_pptal`, `pc_o`.`nom_rubro`), '') AS `nom_oper`
                FROM `nom_otros_devengados` AS `nod`
                INNER JOIN `nom_tipo_devengado` AS `ntd`
                    ON (`nod`.`id_tipo` = `ntd`.`id_tipo`)
                LEFT JOIN `pto_cargue` AS `pc_a`
                    ON (`ntd`.`r_admin` = `pc_a`.`id_cargue`)
                LEFT JOIN `pto_cargue` AS `pc_o`
                    ON (`ntd`.`r_oper`  = `pc_o`.`id_cargue`)
                WHERE `nod`.`id_devengado` = ?";
        $stmt = $this->conexion->prepare($sql);
        $stmt->bindParam(1, $id, PDO::PARAM_INT);
        $stmt->execute();
        $registro = $stmt->fetch(PDO::FETCH_ASSOC);
        $stmt->closeCursor();
        unset($stmt);

        if (empty($registro)) {
            return [
                'id_devengado' => 0,
                'id_empleado'  => 0,
                'id_tipo'      => 0,
                'fec_inicia'   => Sesion::_Hoy(),
                'fec_fin'      => '',
                'concepto'     => '',
                'valor'        => 0,
                'estado'       => 1,
                'r_admin'      => 0,
                'r_oper'       => 0,
                'nom_admin'    => '',
                'nom_oper'     => '',
            ];
        }
        return $registro;
    }

    /**
     * Retorna los rubros (admin y operativo) de un tipo de devengado.
     * Se usa vía AJAX cuando el usuario cambia el tipo en el formulario.
     *
     * @param int $id_tipo
     * @return array
     */
    public function getTipoConRubros($id_tipo)
    {
        $sql = "SELECT
                    `ntd`.`id_tipo`
                    , `ntd`.`r_admin`
                    , `ntd`.`r_oper`
                    , IFNULL(CONCAT_WS(' - ', `pc_a`.`cod_pptal`, `pc_a`.`nom_rubro`), '') AS `nom_admin`
                    , IFNULL(CONCAT_WS(' - ', `pc_o`.`cod_pptal`, `pc_o`.`nom_rubro`), '') AS `nom_oper`
                FROM `nom_tipo_devengado` AS `ntd`
                LEFT JOIN `pto_cargue` AS `pc_a`
                    ON (`ntd`.`r_admin` = `pc_a`.`id_cargue`)
                LEFT JOIN `pto_cargue` AS `pc_o`
                    ON (`ntd`.`r_oper`  = `pc_o`.`id_cargue`)
                WHERE `ntd`.`id_tipo` = ?";
        $stmt = $this->conexion->prepare($sql);
        $stmt->bindParam(1, $id_tipo, PDO::PARAM_INT);
        $stmt->execute();
        $data = $stmt->fetch(PDO::FETCH_ASSOC);
        $stmt->closeCursor();
        unset($stmt);
        return $data ?: ['r_admin' => 0, 'r_oper' => 0, 'nom_admin' => '', 'nom_oper' => ''];
    }

    /**
     * Obtiene el formulario HTML para agregar/editar un devengado.
     */
    public function getFormulario($id)
    {
        $registro = $this->getRegistro($id);
        $tipo = $this->getTiposDevengados($registro['id_tipo']);

        $html =
            <<<HTML
                <div class="shadow text-center rounded">
                    <div class="rounded-top py-2" style="background-color: #16a085 !important;">
                        <h5 style="color: white;" class="mb-0">GESTIÓN DE OTROS DEVENGADOS</h5>
                    </div>
                    <div class="p-3">
                        <form id="formOtroDevengado">
                            <input type="hidden" id="id" name="id" value="{$id}">
                            <div class="row mb-2">
                                <div class="col-md-6">
                                    <label for="slcTipoDev" class="small text-muted">TIPO</label>
                                    <select class="form-select form-select-sm bg-input" id="slcTipoDev" name="slcTipoDev">
                                        {$tipo}
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label for="numValor" class="small text-muted">VALOR</label>
                                    <input type="number" class="form-control form-control-sm bg-input text-end" id="numValor" name="numValor" value="{$registro['valor']}">
                                </div>
                            </div>
                            <div class="row mb-2">
                                <div class="col-md-6">
                                    <label for="datFecInicia" class="small text-muted">INICIA</label>
                                    <input type="date" class="form-control form-control-sm bg-input" id="datFecInicia" name="datFecInicia" value="{$registro['fec_inicia']}">
                                </div>
                                <div class="col-md-6">
                                    <label for="datFecFin" class="small text-muted">TERMINA</label>
                                    <input type="date" class="form-control form-control-sm bg-input" id="datFecFin" name="datFecFin" value="{$registro['fec_fin']}">
                                </div>
                            </div>
                            <div class="row mb-2">
                                <div class="col-md-6">
                                    <label for="txtRubroAdmin" class="small text-muted">RUBRO ADMINISTRATIVO</label>
                                    <input type="text" id="txtRubroAdmin" class="form-control form-control-sm bg-light"
                                        readonly
                                        placeholder="Se carga al seleccionar el tipo"
                                        value="{$registro['nom_admin']}">
                                    <input type="hidden" id="idRubroAdminDev" value="{$registro['r_admin']}">
                                </div>
                                <div class="col-md-6">
                                    <label for="txtRubroOper" class="small text-muted">RUBRO OPERATIVO</label>
                                    <input type="text" id="txtRubroOper" class="form-control form-control-sm bg-light"
                                        readonly
                                        placeholder="Se carga al seleccionar el tipo"
                                        value="{$registro['nom_oper']}">
                                    <input type="hidden" id="idRubroOperDev" value="{$registro['r_oper']}">
                                </div>
                            </div>
                            <div class="row mb-2">
                                <div class="col-md-12">
                                    <label for="txtDescribeDev" class="small text-muted">DESCRIPCIÓN</label>
                                    <textarea class="form-control form-control-sm bg-input" id="txtDescribeDev" name="txtDescribeDev">{$registro['concepto']}</textarea>
                                </div>
                            </div>
                        </form>
                    </div>
                    <div class="text-center pb-3">
                        <button type="button" class="btn btn-primary btn-sm" id="btnGuardarOtroDevengado">Guardar</button>
                        <a type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancelar</a>
                    </div>
                </div>
            HTML;
        return $html;
    }

    /**
     * Elimina un registro.
     */
    public function delRegistro($id)
    {
        try {
            $sql      = "DELETE FROM `nom_otros_devengados` WHERE `id_devengado` = ?";
            $consulta = "DELETE FROM `nom_otros_devengados` WHERE `id_devengado` = $id";
            $stmt     = $this->conexion->prepare($sql);
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
     */
    public function addRegistro($array)
    {
        try {
            $sql = "INSERT INTO `nom_otros_devengados`
                        (`id_empleado`,`id_tipo`,`fec_inicia`,`fec_fin`,`concepto`,`valor`,`estado`,`id_user_reg`,`fec_reg`)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $stmt = $this->conexion->prepare($sql);
            $stmt->bindValue(1, $array['id_empleado'],                                  PDO::PARAM_INT);
            $stmt->bindValue(2, $array['slcTipoDev'],                                   PDO::PARAM_INT);
            $stmt->bindValue(3, $array['datFecInicia'],                                 PDO::PARAM_STR);
            $stmt->bindValue(4, $array['datFecFin'] == '' ? null : $array['datFecFin'], PDO::PARAM_STR);
            $stmt->bindValue(5, $array['txtDescribeDev'],                               PDO::PARAM_STR);
            $stmt->bindValue(6, $array['numValor'],                                     PDO::PARAM_STR);
            $stmt->bindValue(7, 1,                                                      PDO::PARAM_INT);
            $stmt->bindValue(8, Sesion::IdUser(),                                       PDO::PARAM_INT);
            $stmt->bindValue(9, Sesion::Hoy(),                                          PDO::PARAM_STR);
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
     */
    public function editRegistro($array)
    {
        try {
            $sql = "UPDATE `nom_otros_devengados`
                        SET `id_tipo` = ?, `fec_inicia` = ?, `fec_fin` = ?, `concepto` = ?, `valor` = ?
                    WHERE `id_devengado` = ?";
            $stmt = $this->conexion->prepare($sql);
            $stmt->bindValue(1, $array['slcTipoDev'],                                   PDO::PARAM_INT);
            $stmt->bindValue(2, $array['datFecInicia'],                                 PDO::PARAM_STR);
            $stmt->bindValue(3, $array['datFecFin'] == '' ? null : $array['datFecFin'], PDO::PARAM_STR);
            $stmt->bindValue(4, $array['txtDescribeDev'],                               PDO::PARAM_STR);
            $stmt->bindValue(5, $array['numValor'],                                     PDO::PARAM_STR);
            $stmt->bindValue(6, $array['id'],                                           PDO::PARAM_INT);

            if ($stmt->execute() && $stmt->rowCount() > 0) {
                $consulta = "UPDATE `nom_otros_devengados` SET `fec_act` = ?, `id_user_act` = ? WHERE `id_devengado` = ?";
                $stmt2    = $this->conexion->prepare($consulta);
                $stmt2->bindValue(1, Sesion::Hoy(),    PDO::PARAM_STR);
                $stmt2->bindValue(2, Sesion::IdUser(), PDO::PARAM_INT);
                $stmt2->bindValue(3, $array['id'],     PDO::PARAM_INT);
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
     * Cambia el estado (activo/inactivo) de un registro.
     */
    public function annulRegistro($array)
    {
        try {
            $sql  = "UPDATE `nom_otros_devengados` SET `estado` = ? WHERE `id_devengado` = ?";
            $stmt = $this->conexion->prepare($sql);
            $stmt->bindValue(1, $array['estado'], PDO::PARAM_INT);
            $stmt->bindValue(2, $array['id'],     PDO::PARAM_INT);
            if ($stmt->execute() && $stmt->rowCount() > 0) {
                return 'si';
            } else {
                return 'No se hizo el cambio de estado.' . $stmt->errorInfo()[2];
            }
        } catch (PDOException $e) {
            return 'Error SQL: ' . $e->getMessage();
        }
    }

    /**
     * Obtiene los tipos de devengados como opciones HTML.
     */
    public function getTiposDevengados($id)
    {
        $sql = "SELECT `id_tipo`, `descripcion` FROM `nom_tipo_devengado` ORDER BY `descripcion`";
        return (new Combos)->setConsulta($sql, $id);
    }

}

