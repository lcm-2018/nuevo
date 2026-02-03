<?php

namespace Src\Nomina\Empleados\Php\Clases;

use Config\Clases\Conexion;
use Config\Clases\Logs;
use Config\Clases\Sesion;
use Exception;
use PDO;
use PDOException;
use Src\Nomina\Liquidacion\Php\Clases\Liquidacion;
use Src\Nomina\Liquidacion\Php\Clases\Nomina;
use Src\Usuarios\Login\Php\Clases\Usuario;

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
                $where .= " AND (`nv`.`fec_inicial` LIKE '%{$array['value']}%' 
                            OR `nv`.`fec_fin` LIKE '%{$array['value']}%'
                            OR `nv`.`dias_inactivo` LIKE '%{$array['value']}%'
                            OR `nv`.`dias_habiles` LIKE '%{$array['value']}%'
                            OR `nv`.`corte` LIKE '%{$array['value']}%'
                            OR `nv`.`dias_liquidar` LIKE '%{$array['value']}%')";
            }

            if (isset($array['id']) && $array['id'] > 0) {
                $where .= " AND `nv`.`id_empleado` = {$array['id']}";
            }
        }

        $sql = "SELECT
                    `nv`.`id_vac`, `nv`.`anticipo`, `nv`.`fec_inicial`, `nv`.`fec_fin`, `nv`.`dias_inactivo`, `nv`.`dias_habiles`, `nv`.`corte`, `nv`.`dias_liquidar`, `nv`.`estado`, IFNULL(`liquidado`.`dias_liqs`,0) AS `liq`, `nv`.`id_empleado`
                FROM
                    `nom_vacaciones` AS `nv`
                    LEFT JOIN 
                        (SELECT
                            `nlv`.`id_vac`
                            , SUM(`nlv`.`dias_liqs`) AS `dias_liqs`
                        FROM
                            `nom_liq_vac` AS `nlv`
                            INNER JOIN `nom_nominas` 
                                ON (`nlv`.`id_nomina` = `nom_nominas`.`id_nomina`)
                        WHERE (`nlv`.`estado` = 1 AND `nom_nominas`.`estado` > 0)
                        GROUP BY `nlv`.`id_vac`) AS `liquidado`
                        ON (`liquidado`.`id_vac` = `nv`.`id_vac`)
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

    public function getLiquidados()
    {
        $sql = "SELECT
                    `nlv`.`id_vac`
                    , SUM(`nlv`.`dias_liqs`) AS `dias_liqs`
                FROM
                    `nom_liq_vac` AS `nlv`
                    INNER JOIN `nom_nominas` 
                        ON (`nlv`.`id_nomina` = `nom_nominas`.`id_nomina`)
                WHERE (`nlv`.`estado` = 1 AND `nom_nominas`.`estado` > 0)
                GROUP BY `nlv`.`id_vac`";
        $stmt = $this->conexion->prepare($sql);
        $stmt->execute();
        $registro = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $stmt->closeCursor();
        unset($stmt);
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
                            `nlv`.`id_vac`
                            , SUM(`nlv`.`dias_liqs`) AS `dias_liqs`
                        FROM
                            `nom_liq_vac` AS `nlv`
                            INNER JOIN `nom_nominas` 
                                ON (`nlv`.`id_nomina` = `nom_nominas`.`id_nomina`)
                        WHERE (`nlv`.`estado` = 1 AND `nom_nominas`.`estado` > 0)
                        GROUP BY `nlv`.`id_vac`) AS `liquidado`
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
                WHERE `estado` = 1 AND IFNULL(`liquidado`.`dias_liqs`,0) = 0";
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
     * Obtiene las vacaciones por empleado que ya han sido pagadas.
     * 
     * Verifica que la nómina esté en estado >= 2 y que la vacación tenga 
     * una fecha de inicio dentro del mes que se está liquidando.
     *
     * @param string $inicia Fecha de inicio del período (YYYY-MM-DD)
     * @param string $fin Fecha de fin del período (YYYY-MM-DD)
     * @return array Datos de vacaciones pagadas indexados por id_empleado
     */
    public function getRegistroPago($inicia, $fin)
    {
        $sql = "SELECT
                    `nv`.`id_vac`
                    , `nv`.`id_empleado`
                    , `nv`.`fec_inicial`
                    , `nv`.`fec_inicio`
                    , `nv`.`fec_fin`
                    , `nv`.`dias_inactivo`
                    , `nv`.`dias_habiles`
                    , `nv`.`corte`
                    , `nv`.`dias_liquidar`
                    , IFNULL(`liquidado`.`dias_liqs`, 0) AS `liq`
                    , IFNULL(`liquidado`.`val_liq`, 0) AS `val_vac`
                    , IFNULL(`liquidado`.`val_prima_vac`, 0) AS `prima_vac`
                    , IFNULL(`liquidado`.`val_bon_recrea`, 0) AS `bon_recrea`
                    , `liquidado`.`id_nomina`
                FROM `nom_vacaciones` AS `nv`
                    INNER JOIN 
                        (SELECT
                            `nlv`.`id_vac`
                            , SUM(`nlv`.`dias_liqs`) AS `dias_liqs`
                            , SUM(`nlv`.`val_liq`) AS `val_liq`
                            , SUM(`nlv`.`val_prima_vac`) AS `val_prima_vac`
                            , SUM(`nlv`.`val_bon_recrea`) AS `val_bon_recrea`
                            , `nlv`.`id_nomina`
                        FROM
                            `nom_liq_vac` AS `nlv`
                            INNER JOIN `nom_nominas` 
                                ON (`nlv`.`id_nomina` = `nom_nominas`.`id_nomina`)
                        WHERE (`nlv`.`estado` = 1 AND `nom_nominas`.`estado` >= 2)
                        GROUP BY `nlv`.`id_vac`, `nlv`.`id_nomina`) AS `liquidado`
                        ON (`liquidado`.`id_vac` = `nv`.`id_vac`)
                WHERE `nv`.`estado` = 1 
                    AND `nv`.`fec_inicial` BETWEEN ? AND ?";

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
    public function addRegistroNoVc($array, $opcion = 0)
    {
        $ids =          $array['chk_liquidacion'];
        $contratos =    $array['id_contrato'];
        $mpago =        $array['metodo'];
        $tipo =         $array['tipo'];
        $mes =          $array['mes'];
        $incremento =   isset($array['incremento']) ? $array['incremento'] : NULL;
        $nomina =       Nomina::getIDNomina($mes, $tipo);

        if (($nomina['id_nomina'] > 0 && $nomina['estado'] >= 2) || $nomina['id_nomina'] == 0) {
            $res = Nomina::addRegistro($mes, $tipo, $incremento);
            if ($res['status'] == 'si') {
                $id_nomina = $res['id'];
            } else {
                return $res['msg'];
            }
        } else {
            $id_nomina = $nomina['id_nomina'];
        }

        $data = Nomina::getParamLiq();
        if (empty($data)) {
            return 'No se han configurado los parámetros de liquidación.';
        }

        $parametro = array_column($data, 'valor', 'id_concepto');

        if (empty($parametro[1]) || empty($parametro[6])) {
            return 'No se han Configurado los parámetros de liquidación.';
        }

        $inicia = Sesion::Vigencia() . '-' . $mes . '-01';
        $fin = date('Y-m-t', strtotime($inicia));

        $Empleado =     new Empleados();
        $empleados =    array_column($Empleado->getEmpleados(), null, 'id_empleado');
        $salarios =     $Empleado->getSalarioMasivo($mes);
        $salarios =     array_column($salarios, 'basico', 'id_empleado');
        $terceros_ss =  $Empleado->getRegistro();
        $empresa =      (new Usuario())->getEmpresa();
        //Devengados
        $vacaciones =       (new Vacaciones())->getRegistroPorEmpleado($inicia, $fin);

        $cortes =       array_column((new Liquidacion)->getCortes($ids, $fin), null, 'id_empleado');
        $liquidados =   (new Liquidacion)->getEmpleadosLiq($id_nomina, $ids);
        $liquidados =   array_column($liquidados, 'id_sal_liq', 'id_empleado');
        $error = '';

        if ($opcion == 0) {
            $param['smmlv'] =           $parametro[1];
            $param['uvt'] =             $parametro[6];
            $param['base_bsp'] =        $parametro[7];
            $param['grep'] =            $parametro[8];
            $param['base_alim'] =       $parametro[9];
            $param['min_vital'] =       $parametro[10] ?? 0;
            $param['id_nomina'] =       $id_nomina;
        }

        $inserts = 0;
        foreach ($ids as $id_empleado) {
            if (!(isset($liquidados[$id_empleado]) && isset($salarios[$id_empleado]))) {
                try {
                    $filtro = [];
                    $filtro = array_filter($terceros_ss, function ($terceros_ss) use ($id_empleado) {
                        return $terceros_ss["id_empleado"] == $id_empleado;
                    });

                    $novedad = array_column($filtro, 'id_tercero', 'id_tipo');
                    if (!(isset($novedad[1]) && isset($novedad[2]) && isset($novedad[3]) && isset($novedad[4]))) {
                        throw new Exception("No tiene registrado novedades de seguridad social");
                    }

                    $cortes_empleado =  $cortes[$id_empleado] ?? [];
                    if (!$this->conexion->inTransaction()) {
                        $this->conexion->beginTransaction();
                    }

                    if ($opcion == 0) {
                        $param['id_empleado'] =     $id_empleado;
                        $param['salario'] =         $salarios[$id_empleado];
                        $param['tiene_grep'] =      $cortes_empleado['tiene_grep'] ?? 0;
                        $param['bsp_ant'] =         $cortes_empleado['val_bsp'] ?? 0;
                        $param['pri_ser_ant'] =     $cortes_empleado['val_liq_ps'] ?? 0;
                        $param['pri_vac_ant'] =     $cortes_empleado['val_liq_pv'] ?? 0;
                        $param['pri_nav_ant'] =     $cortes_empleado['val_liq'] ?? 0;
                        $param['prom_horas'] =      $cortes_empleado['prom'] ?? 0;
                    } else if ($opcion == 1) {
                        $param = (new Valores_Liquidacion($this->conexion))->getRegistro($id_nomina, $id_empleado);
                    }

                    $param['aux_trans'] =   $salarios[$id_empleado] <= $param['smmlv'] * 2 ? $parametro[2] : 0;
                    $param['aux_alim'] =    $salarios[$id_empleado] <= $param['base_alim'] ? $parametro[3] : 0;
                    $tipo_emp =             $empleados[$id_empleado]['tipo_empleado'];
                    $subtipo_emp =          $empleados[$id_empleado]['subtipo_empleado'];

                    if ($tipo_emp == 12 || $tipo_emp == 8) {
                        $param['aux_trans'] =   0;
                        $param['aux_alim'] =    0;
                    }

                    if ($opcion == 0) {
                        $res = (new Valores_Liquidacion($this->conexion))->addRegistro($param);
                        if ($res != 'si') {
                            throw new Exception("Valores de liquidación: $res");
                        }
                    }
                    //liquidar vacaciones
                    $valTotVac =        0;
                    $valTotPrimVac =    0;
                    $valBonRec =        0;

                    $filtro = $vacaciones[$id_empleado][0] ?? [];
                    if (!empty($filtro)) {
                        $Vcc = new Vacaciones($this->conexion);
                        $rt = $Vcc->getRegistroLiq(['id_empleado' => $id_empleado, 'id_nomina' => $id_nomina]);
                        if (!empty($rt) && $rt['tipo'] == 'M') {
                            $valTotVac =        $rt['val_vac'];
                            $valTotPrimVac =    $rt['prima_vac'];
                            $valBonRec =        $rt['bon_recrea'];
                        } else {
                            $response =         (new Liquidacion($this->conexion))->LiquidaVacaciones($filtro, $param);
                            $valTotVac =        $response['valor'];
                            $valTotPrimVac =    $response['prima'];
                            $valBonRec =        $response['bono'];
                            if (!$response['insert']) {
                                throw new Exception("Vacaciones: {$response['msg']}");
                            }
                        }
                    }

                    //Seguridad social
                    $ibc = $valTotVac;
                    $dias = $vacaciones[$id_empleado][0]['dias_inactivo'] ?? 22;
                    $response = (new Liquidacion($this->conexion))->LiquidaSeguridadSocial($param, $novedad, $ibc, $tipo_emp, $subtipo_emp, $dias);
                    $valTotSegSoc = $response['valor'];
                    if (!$response['insert']) {
                        throw new Exception("Seguridad social: {$response['msg']}");
                    }

                    $response = (new Liquidacion($this->conexion))->LiquidaParafiscales($param, $ibc, $empresa['exonera_aportes'], $tipo_emp);
                    if (!$response['insert']) {
                        throw new Exception("Parafiscales: {$response['msg']}");
                    }

                    $baseDctos = $valTotVac + $valTotPrimVac + $valBonRec - ($valTotSegSoc ?? 0);

                    $neto = $baseDctos;
                    $data = [
                        'id_empleado'   =>  $id_empleado,
                        'id_nomina'     =>  $id_nomina,
                        'metodo_pago'   =>  $mpago[$id_empleado],
                        'val_liq'       =>  $neto,
                        'forma_pago'    =>  1,
                        'sal_base'      =>  $salarios[$id_empleado],
                        'id_contrato'   =>  $contratos[$id_empleado],
                    ];
                    $response = (new Liquidacion($this->conexion))->LiquidaSalarioNeto($data);
                    if (!$response['insert']) {
                        throw new Exception("Salario neto: {$response['msg']}");
                    }
                    if ($opcion == 0) {
                        $this->conexion->commit();
                    }
                    $inserts++;
                    unset($filtro, $response);
                    gc_collect_cycles();
                } catch (Exception $e) {
                    if ($this->conexion->inTransaction()) {
                        $this->conexion->rollBack();
                    }
                    $error .= "<p>ID: $id_empleado ({$empleados[$id_empleado]['no_documento']}), {$e->getMessage()}</p>";
                    continue;
                }
            }
        }
        if ($error != '') {
            return $error;
        } else if ($inserts == 0) {
            return 'No se liquidó ningún empleado.';
        } else {
            return 'si';
        }
    }
    /**
     * Agrega un nuevo registro.
     *
     * @param array $array Datos del registro a agregar
     * @return string Mensaje de éxito o error
     */
    public function addRegistro($array, $op = true)
    {
        try {
            // iniciar transacción
            //verificar si no existe una transacion
            if (!($this->conexion->inTransaction())) {
                $this->conexion->beginTransaction();
            }
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
                $resultado = $Novedad->addRegistro($array, $op);
                if ($resultado === 'si') {
                    $this->conexion->commit();
                    return $op ? 'si' : $id;
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
            //verificar si no existe una transacion
            if (!($this->conexion->inTransaction())) {
                $this->conexion->beginTransaction();
            }
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
