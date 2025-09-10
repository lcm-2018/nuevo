<?php

namespace Src\Nomina\Empleados\Php\Clases;

use Config\Clases\Conexion;
use Config\Clases\Logs;
use Config\Clases\Sesion;

use Src\Common\Php\Clases\Valores;

use PDO;
use PDOException;
use Src\Common\Php\Clases\Combos;

class Seguridad_Social
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
        $id_empleado = 0;
        $where = '';
        if (!empty($array)) {
            if (isset($array['filter_tipo']) && $array['filter_tipo'] > 0) {
                $where .= " AND `nom_terceros`.`id_tipo` LIKE '%{$array['filter_tipo']}%'";
            }

            if (isset($array['filter_nombre']) && $array['filter_nombre'] != '') {
                $where .= " AND `tb_terceros`.`nom_tercero` LIKE '%{$array['filter_nombre']}%'";
            }

            if (isset($array['filter_nit']) && $array['filter_nit'] != '') {
                $where .= " AND `tb_terceros`.`nit_tercero` LIKE '%{$array['filter_nit']}%'";
            }

            if (isset($array['filter_afiliacion']) && $array['filter_afiliacion'] != '') {
                $where .= " AND `nom_terceros_novedad`.`fec_inicia` LIKE '%{$array['filter_afiliacion']}%'";
            }

            if (isset($array['filter_retiro']) && $array['filter_retiro'] != '') {
                $where .= " AND `nom_terceros_novedad`.`fec_fin` = {$array['filter_retiro']}";
            }

            if (isset($array['filter_id']) && $array['filter_id'] != '') {
                $where .= " AND `nom_terceros_novedad`.`id_empleado` = {$array['filter_id']}";
                $id_empleado = $array['filter_id'];
            }
        }

        $sql = "SELECT
                    `nom_terceros_novedad`.`id_novedad`
                    , `nom_terceros_novedad`.`id_empleado`
                    , `nom_categoria_tercero`.`codigo`
                    , `nom_categoria_tercero`.`descripcion`
                    , `nom_terceros`.`id_tipo`
                    , `nom_terceros`.`id_tercero_api`
                    , `tb_terceros`.`nom_tercero`
                    , `tb_terceros`.`nit_tercero`
                    , `nom_terceros_novedad`.`fec_inicia`
                    , `nom_terceros_novedad`.`fec_fin`
                    , `nom_terceros_novedad`.`id_riesgo`
                    , IF(`tt`.`activo` = 1, 1, 0) AS `activo`
                    , IFNULL(`nom_riesgos_laboral`.`clase`, 'N/A') AS `riesgo`
                FROM
                    `nom_terceros_novedad`
                    INNER JOIN `nom_terceros` 
                        ON (`nom_terceros_novedad`.`id_tercero` = `nom_terceros`.`id_tn`)
                    INNER JOIN `nom_categoria_tercero` 
                        ON (`nom_terceros`.`id_tipo` = `nom_categoria_tercero`.`id_cat`)
                    INNER JOIN `tb_terceros` 
                        ON (`nom_terceros`.`id_tercero_api` = `tb_terceros`.`id_tercero_api`)
                    LEFT JOIN 
                        (SELECT
                            `ntn`.`id_novedad`,
                            `nt`.`id_tipo`,
                            1 AS `activo`
                        FROM
                            `nom_terceros_novedad` `ntn`
                            INNER JOIN `nom_terceros` `nt` ON `ntn`.`id_tercero` = `nt`.`id_tn`
                        WHERE `ntn`.`id_empleado` = 62
                            AND `ntn`.`fec_inicia` = (
                                SELECT MAX(`ntn2`.`fec_inicia`)
                                FROM `nom_terceros_novedad` `ntn2`
                                INNER JOIN `nom_terceros` `nt2` ON `ntn2`.`id_tercero` = `nt2`.`id_tn`
                                WHERE
                                    `ntn2`.`id_empleado` = $id_empleado
                                    AND `nt2`.`id_tipo` = `nt`.`id_tipo`)) AS `tt`
                        ON (`tt`.`id_novedad` = `nom_terceros_novedad`.`id_novedad`)
                    LEFT JOIN  `nom_riesgos_laboral` 
                        ON (`nom_terceros_novedad`.`id_riesgo` = `nom_riesgos_laboral`.`id_rlab`)
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
            if (isset($array['filter_tipo']) && $array['filter_tipo'] > 0) {
                $where .= " AND `nom_terceros`.`id_tipo` LIKE '%{$array['filter_tipo']}%'";
            }

            if (isset($array['filter_nombre']) && $array['filter_nombre'] != '') {
                $where .= " AND `tb_terceros`.`nom_tercero` LIKE '%{$array['filter_nombre']}%'";
            }

            if (isset($array['filter_nit']) && $array['filter_nit'] != '') {
                $where .= " AND `tb_terceros`.`nit_tercero` LIKE '%{$array['filter_nit']}%'";
            }

            if (isset($array['filter_afiliacion']) && $array['filter_afiliacion'] != '') {
                $where .= " AND `nom_terceros_novedad`.`fec_inicia` LIKE '%{$array['filter_afiliacion']}%'";
            }

            if (isset($array['filter_retiro']) && $array['filter_retiro'] != '') {
                $where .= " AND `nom_terceros_novedad`.`fec_fin` = {$array['filter_retiro']}";
            }

            if (isset($array['filter_id']) && $array['filter_id'] != '') {
                $where .= " AND `nom_terceros_novedad`.`id_empleado` = {$array['filter_id']}";
            }
        }


        $sql = "SELECT 
                    COUNT(*) AS `total`
                FROM
                    `nom_terceros_novedad`
                    INNER JOIN `nom_terceros` 
                        ON (`nom_terceros_novedad`.`id_tercero` = `nom_terceros`.`id_tn`)
                    INNER JOIN `nom_categoria_tercero` 
                        ON (`nom_terceros`.`id_tipo` = `nom_categoria_tercero`.`id_cat`)
                    INNER JOIN `tb_terceros` 
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

    public function getRegistrosTotal($id)
    {
        $sql = "SELECT 
                    COUNT(*) AS `total`
                FROM
                    `nom_terceros_novedad`
                    INNER JOIN `nom_terceros` 
                        ON (`nom_terceros_novedad`.`id_tercero` = `nom_terceros`.`id_tn`)
                    INNER JOIN `nom_categoria_tercero` 
                        ON (`nom_terceros`.`id_tipo` = `nom_categoria_tercero`.`id_cat`)
                    INNER JOIN `tb_terceros` 
                        ON (`nom_terceros`.`id_tercero_api` = `tb_terceros`.`id_tercero_api`)
                WHERE (1 = 1 AND `nom_terceros_novedad`.`id_empleado` = ?)";
        $stmt = $this->conexion->prepare($sql);
        $stmt->execute([$id]);
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
                    `nom_terceros_novedad`.`id_empleado`
                    , `nom_terceros_novedad`.`id_tercero`
                    , `nom_terceros_novedad`.`fec_inicia`
                    , `nom_terceros_novedad`.`fec_fin`
                    , `nom_terceros_novedad`.`id_riesgo`
                    , CONCAT(`tb_terceros`.`nom_tercero`, ' - ', `tb_terceros`.`nit_tercero`) AS `tercero`
                    , `nom_terceros`.`id_tipo`
                FROM `nom_terceros_novedad`
                    INNER JOIN `nom_terceros` 
                        ON (`nom_terceros_novedad`.`id_tercero` = `nom_terceros`.`id_tn`)
                    INNER JOIN `tb_terceros` 
                        ON (`nom_terceros`.`id_tercero_api` = `tb_terceros`.`id_tercero_api`)
                WHERE `id_novedad` = ?";
        $stmt = $this->conexion->prepare($sql);
        $stmt->bindParam(1, $id, PDO::PARAM_INT);
        $stmt->execute();
        $registro = $stmt->fetch(PDO::FETCH_ASSOC);
        if (empty($registro)) {
            $registro = [
                'id_empleado' => 0,
                'id_tercero' => 0,
                'fec_inicia' => Sesion::_Hoy(),
                'fec_fin' => '',
                'id_riesgo' => 0,
                'tercero' => '',
                'id_tipo' => 0
            ];
        }
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
        $tipos = Combos::getCategoriaTercero($registro['id_tipo'], 'SS');
        $terceros = $id > 0 ? Empleados::getTerceroNomina('', $registro['id_tercero'], $registro['id_tipo']) : '<option value="0">--Seleccione--</option>';
        $riesgos = Empleados::getRiesgoLaboral($registro['id_riesgo']);
        $readonly = $id > 0 ? 'readonly' : '';
        $disabled = $id > 0 ? 'disabled' : '';
        $view = $registro['id_tipo'] == '3' ? '' : 'd-none';
        $html =
            <<<HTML
                <div class="shadow text-center rounded">
                    <div class="rounded-top py-2" style="background-color: #16a085 !important;">
                        <h5 style="color: white;" class="mb-0">GESTIÓN DE SEGURIDAD SOCIAL</h5>
                    </div>
                    <div class="p-3">
                        <form id="formSegSocial">
                            <input type="hidden" id="id" name="id" value="{$id}">
                            <div class="row mb-2">
                                <div class="col-md-12">
                                    <label for="slcTipoSS" class="small text-muted">Tipo</label>
                                    <select class="form-select form-select-sm bg-input" id="slcTipoSS" name="slcTipoSS" onchange="CargaCombos('slcTercero','tern',value);InputRiesgoLaboral('divRiesgoLaboral', value);" {$readonly} {$disabled}>
                                        {$tipos}
                                    </select>
                                </div>
                            </div>
                            <div class="row mb-2 {$view}" id="divRiesgoLaboral">
                                <div class="col-md-12">
                                    <label for="slcRiesgoLaboral" class="small text-muted">Riesgo Laboral</label>
                                    <select class="form-select form-select-sm bg-input" id="slcRiesgoLaboral" name="slcRiesgoLaboral">
                                        {$riesgos}
                                    </select>
                                </div>
                            </div>
                            <div class="row mb-2">
                                <div class="col-md-12">
                                    <label for="slcTercero" class="small text-muted">Tercero</label>
                                    <select class="form-select form-select-sm bg-input" id="slcTercero" name="slcTercero">
                                        {$terceros}
                                    </select>
                                </div>
                            </div>
                            <div class="row mb-2">
                                <div class="col-md-6">
                                    <label for="datFecInicia" class="small text-muted">Inicia</label>
                                    <input type="date" class="form-control form-control-sm bg-input" id="datFecInicia" name="datFecInicia" value="{$registro['fec_inicia']}">
                                </div>
                                <div class="col-md-6">
                                    <label for="datFecFin" class="small text-muted">Fin</label>
                                    <input type="date" class="form-control form-control-sm bg-input" id="datFecFin" name="datFecFin" value="{$registro['fec_fin']}">
                                </div>
                            </div>
                        </form>
                    </div>
                    <div class="text-center pb-3">
                        <button type="button" class="btn btn-primary btn-sm" id="btnGuardarSegSocial">Guardar</button>
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
            $sql = "DELETE FROM `nom_terceros_novedad` WHERE `id_novedad` = ?";
            $consulta  = "DELETE FROM `nom_terceros_novedad` WHERE `id_novedad` = $id";
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
            $sql = "INSERT INTO `nom_terceros_novedad`
                        (`id_empleado`,`id_tercero`,`fec_inicia`,`fec_fin`,`id_riesgo`,`id_user_reg`,`fec_reg`)
                    VALUES (?, ?, ?, ?, ?, ?, ?)";
            $stmt = $this->conexion->prepare($sql);
            $stmt->bindValue(1, $array['id_empleado'], PDO::PARAM_INT);
            $stmt->bindValue(2, $array['slcTercero'], PDO::PARAM_INT);
            $stmt->bindValue(3, $array['datFecInicia'], PDO::PARAM_STR);
            $stmt->bindValue(4, $array['datFecFin'] != '' ? $array['datFecFin'] : NULL, PDO::PARAM_STR);
            $stmt->bindValue(5, $array['slcRiesgoLaboral'] == '0' ? NULL : $array['slcRiesgoLaboral'], PDO::PARAM_INT);
            $stmt->bindValue(6, Sesion::IdUser(), PDO::PARAM_INT);
            $stmt->bindValue(7, Sesion::Hoy(), PDO::PARAM_STR);
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

    public function addRegistroLiq($array)
    {
        try {
            $sql = "INSERT INTO `nom_liq_segsocial_empdo`
                    (`id_empleado`,`id_eps`,`id_arl`,`id_afp`,`aporte_salud_emp`,`aporte_pension_emp`,`aporte_solidaridad_pensional`,`porcentaje_ps`,`aporte_salud_empresa`,`aporte_pension_empresa`,`aporte_rieslab`,`id_user_reg`,`fec_reg`,`id_nomina`)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $stmt = $this->conexion->prepare($sql);
            $stmt->bindValue(1, $array['id_empleado'], PDO::PARAM_INT);
            $stmt->bindValue(2, $array['id_eps'], PDO::PARAM_INT);
            $stmt->bindValue(3, $array['id_arl'], PDO::PARAM_INT);
            $stmt->bindValue(4, $array['id_afp'], PDO::PARAM_INT);
            $stmt->bindValue(5, $array['aporte_salud_emp'], PDO::PARAM_STR);
            $stmt->bindValue(6, $array['aporte_pension_emp'], PDO::PARAM_STR);
            $stmt->bindValue(7, $array['aporte_solidaridad_pensional'], PDO::PARAM_STR);
            $stmt->bindValue(8, $array['porcentaje_ps'], PDO::PARAM_STR);
            $stmt->bindValue(9, $array['aporte_salud_empresa'], PDO::PARAM_STR);
            $stmt->bindValue(10, $array['aporte_pension_empresa'], PDO::PARAM_STR);
            $stmt->bindValue(11, $array['aporte_rieslab'], PDO::PARAM_STR);
            $stmt->bindValue(12, Sesion::IdUser(), PDO::PARAM_INT);
            $stmt->bindValue(13, Sesion::Hoy(), PDO::PARAM_STR);
            $stmt->bindValue(14, $array['id_nomina'], PDO::PARAM_INT);
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

    public function addRegistroLiq2($array)
    {
        try {
            $sql = "INSERT INTO `nom_liq_parafiscales`
                        (`id_empleado`,`val_sena`,`val_icbf`,`val_comfam`,`fec_reg`,`id_user_reg`,`id_nomina`)
                    VALUES (?, ?, ?, ?, ?, ?, ?)";
            $stmt = $this->conexion->prepare($sql);
            $stmt->bindValue(1, $array['id_empleado'], PDO::PARAM_INT);
            $stmt->bindValue(2, $array['val_sena'], PDO::PARAM_STR);
            $stmt->bindValue(3, $array['val_icbf'], PDO::PARAM_STR);
            $stmt->bindValue(4, $array['val_comfam'], PDO::PARAM_STR);
            $stmt->bindValue(5, Sesion::Hoy(), PDO::PARAM_STR);
            $stmt->bindValue(6, Sesion::IdUser(), PDO::PARAM_INT);
            $stmt->bindValue(7, $array['id_nomina'], PDO::PARAM_INT);
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
            $sql = "UPDATE `nom_terceros_novedad`
                        SET  `id_tercero` = ?, `fec_inicia` = ?, `fec_fin` = ?, `id_riesgo` = ?
                    WHERE `id_novedad` = ?";
            $stmt = $this->conexion->prepare($sql);
            $stmt->bindValue(1, $array['slcTercero'], PDO::PARAM_INT);
            $stmt->bindValue(2, $array['datFecInicia'], PDO::PARAM_STR);
            $stmt->bindValue(3, $array['datFecFin'] != '' ? $array['datFecFin'] : NULL, PDO::PARAM_STR);
            $stmt->bindValue(4, $array['slcRiesgoLaboral'] == '0' ? NULL : $array['slcRiesgoLaboral'], PDO::PARAM_INT);
            $stmt->bindValue(5, $array['id'], PDO::PARAM_INT);
            if ($stmt->execute() && $stmt->rowCount() > 0) {
                $consulta = "UPDATE `nom_terceros_novedad`
                            SET `fec_act` = ?, `id_user_act` = ?
                        WHERE `id_novedad` = ?";
                $stmt2 = $this->conexion->prepare($consulta);
                $stmt2->bindValue(1, Sesion::Hoy(), PDO::PARAM_STR);
                $stmt2->bindValue(2, Sesion::IdUser(), PDO::PARAM_INT);
                $stmt2->bindValue(3, $array['id'], PDO::PARAM_INT);
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
        return 'Falta programar la anulación de registro de seguridad social.';
    }
}
