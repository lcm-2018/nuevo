<?php

namespace Src\Nomina\Certificaciones\Php\Clases;

use Config\Clases\Conexion;
use Config\Clases\Sesion;

use PDO;
use PDOException;

class Certificados
{
    private $conexion;

    public function __construct()
    {
        $this->conexion = Conexion::getConexion();
    }

    /**
     * Obtiene los tipos de certificado disponibles.
     * @return array Lista de tipos de certificado
     */
    public function getTiposCertificado()
    {
        $sql = "SELECT `id_cert`, `descripcion`
                FROM `nom_tipo_certificado`
                ORDER BY `id_cert` ASC";
        $stmt = $this->conexion->prepare($sql);
        $stmt->execute();
        $registros = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        $stmt->closeCursor();
        unset($stmt);
        return $registros;
    }

    /**
     * Obtiene los empleados activos para selección.
     * @param string $busca Texto de búsqueda
     * @return array Lista de empleados
     */
    public function getEmpleados($busca = '')
    {
        $where = '';
        if ($busca !== '') {
            $busca = trim($busca);
            $where = "AND (e.`primer_nombre` LIKE '%$busca%'
                      OR e.`segundo_nombre` LIKE '%$busca%'
                      OR e.`primer_apellido` LIKE '%$busca%'
                      OR e.`segundo_apellido` LIKE '%$busca%'
                      OR e.`cedula` LIKE '%$busca%')";
        }

        $sql = "SELECT
                    e.`id_empleado`,
                    e.`cedula`,
                    CONCAT_WS(' ', e.`primer_nombre`, e.`segundo_nombre`, e.`primer_apellido`, e.`segundo_apellido`) AS `nombre_completo`,
                    c.`descripcion_carg` AS `cargo`,
                    ce.`fecha_inicio`,
                    ce.`salario`
                FROM `nom_empleados` e
                INNER JOIN `nom_contratos_empleados` ce
                    ON (ce.`id_contrato_emp` = (
                        SELECT MAX(`id_contrato_emp`)
                        FROM `nom_contratos_empleados`
                        WHERE `id_empleado` = e.`id_empleado` AND `estado` = 1
                    ))
                INNER JOIN `nom_cargo_empleado` c
                    ON (ce.`id_cargo` = c.`id_cargo`)
                WHERE e.`estado` = 1 $where
                ORDER BY e.`primer_apellido`, e.`primer_nombre` ASC";
        $stmt = $this->conexion->prepare($sql);
        $stmt->execute();
        $datos = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        $stmt->closeCursor();
        unset($stmt);
        return $datos;
    }

    /**
     * Obtiene los datos completos de un empleado para la certificación.
     * @param int $id_empleado ID del empleado
     * @return array Datos del empleado
     */
    public function getDatosEmpleado($id_empleado)
    {
        $sql = "SELECT
                    e.`id_empleado`,
                    e.`cedula`,
                    CONCAT_WS(' ', e.`primer_nombre`, e.`segundo_nombre`, e.`primer_apellido`, e.`segundo_apellido`) AS `nombre_completo`,
                    e.`ciudad_exp_cedula`,
                    e.`fecha_nac`,
                    c.`descripcion_carg` AS `cargo`,
                    c.`grado`,
                    ce.`fecha_inicio`,
                    ce.`salario`,
                    ce.`id_contrato_emp`,
                    DATEDIFF(CURDATE(), ce.`fecha_inicio`) AS `dias_servicio`
                FROM `nom_empleados` e
                INNER JOIN `nom_contratos_empleados` ce
                    ON (ce.`id_contrato_emp` = (
                        SELECT MAX(`id_contrato_emp`)
                        FROM `nom_contratos_empleados`
                        WHERE `id_empleado` = e.`id_empleado` AND `estado` = 1
                    ))
                INNER JOIN `nom_cargo_empleado` c
                    ON (ce.`id_cargo` = c.`id_cargo`)
                WHERE e.`id_empleado` = ?";
        $stmt = $this->conexion->prepare($sql);
        $stmt->bindParam(1, $id_empleado, PDO::PARAM_INT);
        $stmt->execute();
        $dato = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
        $stmt->closeCursor();
        unset($stmt);
        return $dato;
    }

    /**
     * Obtiene la información de la entidad empleadora (empresa/entidad).
     * @return array Datos de la entidad
     */
    public function getEntidad()
    {
        $sql = "SELECT `nombre`, `nit`, `representante_legal`, `cargo_rep`
                FROM `nom_entidad`
                LIMIT 1";
        $stmt = $this->conexion->prepare($sql);
        $stmt->execute();
        $dato = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
        $stmt->closeCursor();
        unset($stmt);
        return $dato;
    }

    /**
     * Registra la emisión de una certificación en el log (si existe tabla de control).
     * @param int    $id_empleado  ID del empleado
     * @param int    $id_cert      ID del tipo de certificado
     * @param string $dirigido_a   A quién va dirigida la certificación
     * @return string 'si' si se registró correctamente, mensaje de error en caso contrario
     */
    public function registrarCertificacion($id_empleado, $id_cert, $dirigido_a = '')
    {
        try {
            $sql = "INSERT INTO `nom_certificaciones`
                        (`id_empleado`, `id_cert`, `dirigido_a`, `id_user_reg`, `fec_reg`)
                    VALUES (?, ?, ?, ?, ?)";
            $stmt = $this->conexion->prepare($sql);
            $stmt->bindValue(1, $id_empleado, PDO::PARAM_INT);
            $stmt->bindValue(2, $id_cert, PDO::PARAM_INT);
            $stmt->bindValue(3, $dirigido_a, PDO::PARAM_STR);
            $stmt->bindValue(4, Sesion::IdUser(), PDO::PARAM_INT);
            $stmt->bindValue(5, Sesion::Hoy(), PDO::PARAM_STR);
            $stmt->execute();
            $id = $this->conexion->lastInsertId();
            return $id > 0 ? 'si' : 'No se registró la certificación.';
        } catch (PDOException $e) {
            return 'Error SQL: ' . $e->getMessage();
        }
    }

    /**
     * Genera el formulario HTML para seleccionar empleado y dirigido a.
     * @param int $id_cert ID del tipo de certificado
     * @return string HTML del formulario
     */
    public function getFormularioCert($id_cert)
    {
        $tipos = $this->getTiposCertificado();
        $nombre_cert = '';
        foreach ($tipos as $t) {
            if ($t['id_cert'] == $id_cert) {
                $nombre_cert = $t['descripcion'];
                break;
            }
        }

        $html = <<<HTML
        <div>
            <div class="shadow text-center rounded">
                <div class="rounded-top py-2" style="background-color: #16a085 !important;">
                    <h5 style="color: white;" class="mb-0">GENERAR CERTIFICACIÓN</h5>
                    <small style="color: #d5f5e3;">{$nombre_cert}</small>
                </div>
                <div class="p-3">
                    <form id="formGenerarCert">
                        <input type="hidden" id="id_cert" name="id_cert" value="{$id_cert}">
                        <div class="row mb-3">
                            <div class="col-12">
                                <label for="buscaEmpleado" class="small fw-bold text-start d-block">EMPLEADO</label>
                                <div class="input-group input-group-sm">
                                    <input type="hidden" id="id_empleado" name="id_empleado" value="0">
                                    <input type="text" id="buscaEmpleado" name="buscaEmpleado"
                                           class="form-control form-control-sm bg-input"
                                           placeholder="Buscar por nombre o cédula..."
                                           autocomplete="off">
                                    <button class="btn btn-outline-secondary btn-sm" type="button" id="btnBuscarEmp">
                                        <i class="fas fa-search"></i>
                                    </button>
                                </div>
                                <div id="listaEmpleados" class="list-group mt-1" style="max-height:180px; overflow-y:auto; display:none; text-align:left;"></div>
                                <small class="text-muted" id="infoEmpleado"></small>
                            </div>
                        </div>
                        <div class="row mb-3">
                            <div class="col-12">
                                <label for="txtDirigidoA" class="small fw-bold text-start d-block">DIRIGIDO A</label>
                                <input type="text" id="txtDirigidoA" name="txtDirigidoA"
                                       class="form-control form-control-sm bg-input"
                                       placeholder="A QUIEN CORRESPONDA"
                                       value="A QUIEN CORRESPONDA">
                            </div>
                        </div>
                    </form>
                </div>
                <div class="text-end pb-3 px-3 d-flex gap-2 justify-content-end">
                    <button type="button" class="btn btn-primary btn-sm" id="btnGenerarCert">
                        <i class="fas fa-file-alt me-1"></i>Generar
                    </button>
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancelar</button>
                </div>
            </div>
        </div>
        HTML;
        return $html;
    }

    /**
     * Obtiene los id_nomina con estado=5 cuya fecha (vigencia+mes) está dentro del rango.
     * @param string $fecha_ini  Fecha inicio (Y-m-d)
     * @param string $fecha_fin  Fecha fin    (Y-m-d)
     * @return array de id_nomina
     */
    public function getNominasPorRango(string $fecha_ini, string $fecha_fin): array
    {
        $sql = "SELECT `id_nomina`
                FROM `nom_nominas`
                WHERE `estado` = 5
                  AND `id_nomina` > 0
                  AND STR_TO_DATE(CONCAT(`vigencia`,'-',LPAD(`mes`,2,'0'),'-01'),'%Y-%m-%d')
                      BETWEEN :fecha_ini AND :fecha_fin
                ORDER BY `vigencia` ASC, `mes` ASC";
        $stmt = $this->conexion->prepare($sql);
        $stmt->bindValue(':fecha_ini', $fecha_ini, PDO::PARAM_STR);
        $stmt->bindValue(':fecha_fin', $fecha_fin, PDO::PARAM_STR);
        $stmt->execute();
        $rows = $stmt->fetchAll(PDO::FETCH_COLUMN) ?: [];
        $stmt->closeCursor();
        return $rows;  // [id_nomina1, id_nomina2, ...]
    }

    /**
     * Obtiene los empleados que tienen liquidación en las nóminas dadas.
     * @param array $ids_nomina
     * @return array
     */
    public function getEmpleadosConNomina(array $ids_nomina): array
    {
        if (empty($ids_nomina)) return [];
        $ids_in = implode(',', array_map('intval', $ids_nomina));
        $sql = "SELECT DISTINCT e.`id_empleado`, e.`no_documento`, e.`nombre1`, e.`nombre2`, e.`apellido1`, e.`apellido2`,
                       CONCAT_WS(' ', e.`apellido1`, e.`apellido2`, e.`nombre1`, e.`nombre2`) AS `nombre_completo`
                FROM `nom_liq_dlab_auxt` d
                INNER JOIN `nom_empleado` e ON e.`id_empleado` = d.`id_empleado`
                WHERE d.`id_nomina` IN ($ids_in) AND d.`estado` = 1
                ORDER BY `nombre_completo` ASC";
        $stmt = $this->conexion->prepare($sql);
        $stmt->execute();
        $datos = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        $stmt->closeCursor();
        return $datos;
    }

    /**
     * Obtiene el id_empleado (y datos básicos) a partir del id_tercero_api de tb_terceros.
     * Compara nit_tercero con no_documento del empleado.
     * @param int $id_tercero  id_tercero_api de tb_terceros
     * @return array
     */
    public function getDatosEmpleadoPorTercero(int $id_tercero): array
    {
        $sql = "SELECT
                    e.`id_empleado`,
                    e.`no_documento`,
                    e.`nombre1`,
                    e.`nombre2`,
                    e.`apellido1`,
                    e.`apellido2`,
                    CONCAT_WS(' ', e.`nombre1`, e.`nombre2`, e.`apellido1`, e.`apellido2`) AS `nombre`,
                    ce.`sal_base`   AS `salario`,
                    nc.`descripcion_carg` AS `cargo`,
                    ce2.`fec_inicio` AS `fecha_inicio`
                FROM `tb_terceros` t
                INNER JOIN `nom_empleado` e
                    ON TRIM(e.`no_documento`) = TRIM(t.`nit_tercero`)
                LEFT JOIN `nom_liq_salario` ce
                    ON ce.`id_empleado` = e.`id_empleado`
                    AND ce.`id_nomina` = (
                        SELECT MAX(`id_nomina`)
                        FROM `nom_liq_salario`
                        WHERE `id_empleado` = e.`id_empleado` AND `estado` = 1
                    )
                LEFT JOIN `nom_contratos_empleados` ce2
                    ON ce2.`id_contrato_emp` = ce.`id_contrato`
                LEFT JOIN `nom_cargo_empleado` nc
                    ON nc.`id_cargo` = ce2.`id_cargo`
                WHERE t.`id_tercero_api` = :id_tercero
                LIMIT 1";
        $stmt = $this->conexion->prepare($sql);
        $stmt->bindValue(':id_tercero', $id_tercero, PDO::PARAM_INT);
        $stmt->execute();
        $dato = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
        $stmt->closeCursor();
        return $dato;
    }

    /**
     * Sumatoria anual de un empleado sobre múltiples nóminas.
     * Retorna un array con totales para el Formulario 220.
     * @param int   $id_empleado
     * @param array $ids_nomina   arreglo de id_nomina obtenidos con getNominasPorRango()
     * @return array
     */
    public function getResumenAnual(int $id_empleado, array $ids_nomina): array
    {
        if (empty($ids_nomina)) return [];
        $ids_in = implode(',', array_map('intval', $ids_nomina));

        $sql = "SELECT
                    SUM(IFNULL(s.`val_liq`, 0))                                   AS `total_salario`,
                    SUM(IFNULL(d.`val_liq_dias`, 0))                               AS `total_laborado`,
                    SUM(IFNULL(d.`val_liq_auxt`, 0))                               AS `total_aux_transporte`,
                    SUM(IFNULL(d.`aux_alim`, 0))                                   AS `total_aux_alim`,
                    SUM(IFNULL(d.`g_representa`, 0))                               AS `total_g_representa`,
                    SUM(IFNULL(d.`horas_ext`, 0))                                  AS `total_horas_ext`,
                    SUM(IFNULL(inc.`pago_empresa`, 0))                             AS `total_incap`,
                    SUM(IFNULL(ps.`val_liq_ps`, 0))                                AS `total_prima_serv`,
                    SUM(IFNULL(pn.`val_liq_pv`, 0))                                AS `total_prima_nav`,
                    SUM(IFNULL(bsp.`val_bsp`, 0))                                  AS `total_bsp`,
                    SUM(IFNULL(ces.`val_cesantias`, 0))                            AS `total_cesantias`,
                    SUM(IFNULL(ces.`val_icesantias`, 0))                           AS `total_int_cesantias`,
                    SUM(IFNULL(vac.`val_liq`, 0))                                  AS `total_vacaciones`,
                    SUM(IFNULL(odev.`valor`, 0))                                   AS `otros_dev`,
                    SUM(IFNULL(seg.`aporte_salud_emp`, 0))                         AS `total_salud_emp`,
                    SUM(IFNULL(seg.`aporte_pension_emp`, 0))                       AS `total_pension_emp`,
                    SUM(IFNULL(seg.`aporte_solidaridad_pensional`, 0))             AS `total_solidaridad`,
                    SUM(IFNULL(rte.`val_ret`, 0))                                  AS `total_retencion`,
                    SUM(IFNULL(rte.`base`, 0))                                     AS `base_retencion`,
                    SUM(
                        IFNULL(d.`val_liq_dias`, 0) + IFNULL(d.`val_liq_auxt`, 0)
                        + IFNULL(d.`aux_alim`, 0) + IFNULL(d.`g_representa`, 0)
                        + IFNULL(d.`horas_ext`, 0)
                        + IFNULL(ps.`val_liq_ps`, 0) + IFNULL(pn.`val_liq_pv`, 0)
                        + IFNULL(bsp.`val_bsp`, 0) + IFNULL(ces.`val_cesantias`, 0)
                        + IFNULL(ces.`val_icesantias`, 0)
                        + IFNULL(inc.`pago_empresa`, 0)
                        + IFNULL(odev.`valor`, 0)
                    )                                                               AS `total_ingresos`,
                    SUM(
                        IFNULL(seg.`aporte_salud_emp`, 0)
                        + IFNULL(seg.`aporte_pension_emp`, 0)
                        + IFNULL(seg.`aporte_solidaridad_pensional`, 0)
                        + IFNULL(rte.`val_ret`, 0)
                    )                                                               AS `total_deducciones`
                FROM `nom_liq_dlab_auxt` d
                LEFT JOIN `nom_liq_salario`          s   ON (s.`id_empleado`   = d.`id_empleado` AND s.`id_nomina`   = d.`id_nomina` AND s.`estado` = 1)
                LEFT JOIN `nom_liq_bsp`              bsp ON (bsp.`id_empleado` = d.`id_empleado` AND bsp.`id_nomina` = d.`id_nomina` AND bsp.`estado` = 1)
                LEFT JOIN `nom_liq_cesantias`        ces ON (ces.`id_empleado` = d.`id_empleado` AND ces.`id_nomina` = d.`id_nomina` AND ces.`estado` = 1)
                LEFT JOIN `nom_liq_prima`            ps  ON (ps.`id_empleado`  = d.`id_empleado` AND ps.`id_nomina`  = d.`id_nomina` AND ps.`estado` = 1)
                LEFT JOIN `nom_liq_prima_nav`        pn  ON (pn.`id_empleado`  = d.`id_empleado` AND pn.`id_nomina`  = d.`id_nomina` AND pn.`estado` = 1)
                LEFT JOIN `nom_liq_segsocial_empdo`  seg ON (seg.`id_empleado` = d.`id_empleado` AND seg.`id_nomina` = d.`id_nomina` AND seg.`estado` = 1)
                LEFT JOIN `nom_retencion_fte`        rte ON (rte.`id_empleado` = d.`id_empleado` AND rte.`id_nomina` = d.`id_nomina` AND rte.`estado` = 1)
                LEFT JOIN (
                    SELECT ni.`id_empleado`, li.`id_nomina`, SUM(li.`pago_empresa`) AS `pago_empresa`
                    FROM `nom_liq_incap` li
                    INNER JOIN `nom_incapacidad` ni ON ni.`id_incapacidad` = li.`id_incapacidad`
                    WHERE li.`id_nomina` IN ($ids_in) AND li.`estado` = 1
                    GROUP BY ni.`id_empleado`, li.`id_nomina`
                ) AS inc ON (inc.`id_empleado` = d.`id_empleado` AND inc.`id_nomina` = d.`id_nomina`)
                LEFT JOIN (
                    SELECT nv.`id_empleado`, lv.`id_nomina`, SUM(lv.`val_liq`) AS `val_liq`
                    FROM `nom_liq_vac` lv
                    INNER JOIN `nom_vacaciones` nv ON nv.`id_vac` = lv.`id_vac`
                    WHERE lv.`id_nomina` IN ($ids_in) AND lv.`estado` = 1
                    GROUP BY nv.`id_empleado`, lv.`id_nomina`
                ) AS vac ON (vac.`id_empleado` = d.`id_empleado` AND vac.`id_nomina` = d.`id_nomina`)
                LEFT JOIN (
                    SELECT nod.`id_empleado`, nld.`id_nomina`, SUM(nld.`valor`) AS `valor`
                    FROM `nom_liq_devengado` AS `nld`
                    INNER JOIN `nom_otros_devengados` AS `nod` ON (`nld`.`id_devengado` = `nod`.`id_devengado`)
                    WHERE `nld`.`estado` = 1 AND `nld`.`id_nomina` IN ($ids_in)
                    GROUP BY nod.`id_empleado`, nld.`id_nomina`
                ) AS odev ON (odev.`id_empleado` = d.`id_empleado` AND odev.`id_nomina` = d.`id_nomina`)
                WHERE d.`id_empleado` = :id_empleado
                  AND d.`id_nomina` IN ($ids_in)
                  AND d.`estado` = 1";
        $stmt = $this->conexion->prepare($sql);
        $stmt->bindValue(':id_empleado', $id_empleado, PDO::PARAM_INT);
        $stmt->execute();
        $dato = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
        $stmt->closeCursor();
        return $dato;
    }
}
