<?php

namespace Src\Nomina\Empleados\Php\Clases;

use Config\Clases\Conexion;
use Config\Clases\Logs;
use Config\Clases\Sesion;

use PDO;
use PDOException;
use Exception;
use Src\Nomina\Horas_extra\Php\Clases\Horas_Extra;
use Src\Nomina\Liquidacion\Php\Clases\Liquidacion;
use Src\Nomina\Liquidacion\Php\Clases\Nomina;
use Src\Nomina\Liquidacion\Php\Clases\Otros;
use Src\Usuarios\Login\Php\Clases\Usuario;

/**
 * Clase para gestionar las primas de los empleados.
 *
 * Esta clase permite realizar operaciones CRUD sobre las primas de los empleados,
 * incluyendo la obtención de registros, adición, edición y eliminación de primas.
 */
class Prestaciones_Sociales
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
            if (isset($array['filter_nodoc']) && $array['filter_nodoc'] != '') {
                $where .= " AND `e`.`no_documento` LIKE '%{$array['filter_nodoc']}%'";
            }
            if (isset($array['filter_nombre']) && $array['filter_nombre'] != '') {
                $where .= " AND CONCAT_WS(' ', `e`.`nombre1`, `e`.`nombre2`, `e`.`apellido1`, `e`.`apellido2`) LIKE '%{$array['filter_nombre']}%'";
            }
        }
        $mes = $array['filter_mes'];
        $tipo = $array['filter_tipo'];
        $fec_inicio = Sesion::Vigencia() . '-' . $mes . '-01';
        $fec_fin = date('Y-m-t', strtotime($fec_inicio));

        $sql = "SELECT
                    `e`.`id_empleado`
                    ,`e`.`no_documento`
                    , CONCAT_WS (' ', `e`.`nombre1`
                    , `e`.`nombre2`
                    , `e`.`apellido1`
                    , `e`.`apellido2`) AS `nombre`
                    , `nce`.`id_contrato_emp` AS `id_contrato`
                FROM
                    `nom_contratos_empleados` AS `nce`
                    INNER JOIN `nom_empleado` AS `e` 
                        ON (`nce`.`id_empleado` = `e`.`id_empleado`)
                WHERE (`nce`.`fec_fin` <= '$fec_fin'
                    AND `nce`.`estado`  = 1 $where)
                ORDER BY $col $dir $limit";
        $stmt = $this->conexion->prepare($sql);
        $stmt->execute();
        $datos = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $stmt->closeCursor();
        unset($stmt);
        if ($mes == '0') {
            $datos = [];
        }
        return !empty($datos) ? $datos : [];
    }

    /**
     * Obtiene el total de registros filtrados.
     * 
     * @param array $array filtros de búsqueda
     * @return int Total de registros filtrados
     */
    public function getRegistrosFilter($array)
    {
        $where = '';
        if (!empty($array)) {
            if (isset($array['filter_nodoc']) && $array['filter_nodoc'] != '') {
                $where .= " AND `e`.`no_documento` LIKE '%{$array['filter_nodoc']}%'";
            }
            if (isset($array['filter_nombre']) && $array['filter_nombre'] != '') {
                $where .= " AND CONCAT_WS(' ', `e`.`nombre1`, `e`.`nombre2`, `e`.`apellido1`, `e`.`apellido2`) LIKE '%{$array['filter_nombre']}%'";
            }
        }
        $mes = $array['filter_mes'];
        $tipo = $array['filter_tipo'];
        $fec_inicio = Sesion::Vigencia() . '-' . $mes . '-01';
        $fec_fin = date('Y-m-t', strtotime($fec_inicio));

        $sql = "SELECT
                    COUNT(*) AS `total`
                FROM
                    `nom_contratos_empleados` AS `nce`
                    INNER JOIN `nom_empleado` AS `e` 
                        ON (`nce`.`id_empleado` = `e`.`id_empleado`)
                WHERE (`nce`.`fec_fin` <= '$fec_fin'
                    AND `nce`.`estado`  = 1 $where)";
        $stmt = $this->conexion->prepare($sql);
        $stmt->execute();
        if ($mes == '0') {
            return 0;
        }
        $registro = $stmt->fetch(PDO::FETCH_ASSOC);
        $stmt->closeCursor();
        unset($stmt);
        return !empty($registro) ? $registro['total'] : 0;
    }

    /**
     * Obtiene el total de registros.
     * @param array $array filtros de búsqueda
     * @return int Total de registros
     */
    public function getRegistrosTotal($array)
    {
        $mes = $array['filter_mes'];
        $tipo = $array['filter_tipo'];
        $fec_inicio = Sesion::Vigencia() . '-' . $mes . '-01';
        $fec_fin = date('Y-m-t', strtotime($fec_inicio));

        $sql = "SELECT
                    COUNT(*) AS `total`
                FROM
                    `nom_contratos_empleados` AS `nce`
                    INNER JOIN `nom_empleado` AS `e` 
                        ON (`nce`.`id_empleado` = `e`.`id_empleado`)
                WHERE (`nce`.`fec_fin` <= '$fec_fin'
                    AND `nce`.`estado`  = 1)";
        $stmt = $this->conexion->prepare($sql);
        $stmt->execute();
        if ($mes == '0') {
            return 0;
        }
        $registro = $stmt->fetch(PDO::FETCH_ASSOC);
        $stmt->closeCursor();
        unset($stmt);
        return !empty($registro) ? $registro['total'] : 0;
    }

    /**
     * Obtiene un registro por ID.
     *
     * @param int $id ID del registro 
     * @return array  datos del registro
     */

    public function getRegistro($id)
    {
        return 'No se ha implementado aún';
        $sql = "SELECT
                    `id_indemniza`,`fec_inica`,`fec_fin`,`cant_dias`,`estado`
                FROM `nom_indemniza_vac`
                WHERE `id_indemniza` = ?";
        $stmt = $this->conexion->prepare($sql);
        $stmt->bindParam(1, $id, PDO::PARAM_INT);
        $stmt->execute();
        $registro = $stmt->fetch(PDO::FETCH_ASSOC);
        $stmt->closeCursor();
        unset($stmt);
        if (empty($registro)) {
            $registro = [
                'id_indemniza' => 0,
                'fec_inica' => Sesion::_Hoy(),
                'fec_fin' => '',
                'cant_dias' => 0,
                'estado' => 0,
            ];
        }
        return $registro;
    }

    /**
     * Elimina un registro.
     *
     * @param int $id ID del registro a eliminar
     * @return string Mensaje de éxito o error
     */

    public function delRegistro($id)
    {
        return 'No se ha implementado eliminar aún';
        try {
            $sql = "DELETE FROM `nom_indemniza_vac` WHERE `id_indemniza` = ?";
            $consulta  = "DELETE FROM `nom_indemniza_vac` WHERE `id_indemniza` = $id";
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
    public function addRegistro($array, $opcion = 0)
    {
        $ids =          $array['chk_liquidacion'];
        $contratos =    $array['id_contrato'];
        $laborado =     $array['lab'];
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
        $horas =            (new Horas_Extra())->getHorasPorMes($inicia, $fin);
        $incapacidades =    (new Incapacidades())->getRegistroPorEmpleado($inicia, $fin);
        $vacaciones =       (new Vacaciones())->getRegistroPorEmpleado($inicia, $fin);
        $licenciasMP =      (new Licencias_MoP())->getRegistroPorEmpleado($inicia, $fin);
        $licenciaNR =       (new Licencias_Norem())->getRegistroPorEmpleado($inicia, $fin);
        $licenciaLuto =     (new Licencias_Luto())->getRegistroPorEmpleado($inicia, $fin);
        $indemVacaciones =  (new Indemniza_Vacacion())->getRegistroPorEmpleado($inicia, $fin);
        $bonificaciones =   (new Bsp())->getRegistroPorEmpleado();

        //Deducidos
        $libranzas =    (new Libranzas())->getLibranzasPorEmpleado($inicia);
        $embargos =     (new Embargos())->getRegistroPorEmpleado($inicia);
        $sindicatos =   (new Sindicatos())->getRegistroPorEmpleado($inicia);
        $otrosDctos =   (new Otros_Descuentos())->getRegistroPorEmpleado($inicia, $fin);

        //otros 
        $cortes =       array_column(((new Liquidacion($this->conexion))->getCortes($ids, $fin)), null, 'id_empleado');
        $iVivienda =    (new Ivivienda())->getIviviendaEmpleados($ids);
        $iVivienda =    array_column($iVivienda, 'valor', 'id_empleado');
        $liquidados =   ((new Liquidacion($this->conexion))->getEmpleadosLiq($id_nomina, $ids));
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
        $uSalario = $this->getUltimoSalarioLiquidado();
        $uSalario = array_column($uSalario, 'id_nomina', 'id_empleado');
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

                    $param = (new Valores_Liquidacion($this->conexion))->getRegistro($uSalario[$id_empleado], $id_empleado);
                    //aqui voy
                    $param['aux_trans'] =   $salarios[$id_empleado] <= $param['smmlv'] * 2 ? $parametro[2] : 0;
                    $param['aux_alim'] =    $salarios[$id_empleado] <= $param['base_alim'] ? $parametro[3] : 0;
                    $tipo_emp =             $empleados[$id_empleado]['tipo_empleado'];
                    $subtipo_emp =          $empleados[$id_empleado]['subtipo_empleado'];

                    if ($tipo_emp == 12 || $tipo_emp == 8) {
                        $param['aux_trans'] =   0;
                        $param['aux_alim'] =    0;
                    }

                    //liquidar Horas extras
                    $valTotalHe = 0;
                    $filtro = $horas[$id_empleado] ?? [];
                    if (!empty($filtro)) {
                        $response = (new Liquidacion($this->conexion))->LiquidaHorasExtra($filtro, $param);
                        $valTotalHe = $response['valor'];
                        if (!$response['insert']) {
                            throw new Exception("Horas extras: {$response['msg']}");
                        }
                    }

                    //liquidar incapacidades
                    $valTotIncap = 0;
                    $filtro = $incapacidades[$id_empleado] ?? [];
                    if (!empty($filtro)) {
                        $response = (new Liquidacion($this->conexion))->LiquidaIncapacidad($filtro, $param, $novedad);
                        $valTotIncap = $response['valor'];
                        if (!$response['insert']) {
                            throw new Exception("Incapacidades: {$response['msg']}");
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

                    //liquidar licencias mop
                    $valTotLicMP = 0;
                    $filtro = $licenciasMP[$id_empleado][0] ?? [];
                    if (!empty($filtro)) {
                        $Lic = new Licencias_MoP($this->conexion);
                        $rt = $Lic->getRegistroLiq(['id_empleado' => $id_empleado, 'id_nomina' => $id_nomina]);
                        if (!empty($rt) && $rt['tipo'] == 'M') {
                            $valTotLicMP = $rt['valor'];
                        } else {
                            $filtro['id_eps'] = $novedad[1];
                            $filtro['mes'] = $mes;
                            $response = (new Liquidacion($this->conexion))->LiquidaLicenciaMOP($filtro, $param);
                            $valTotLicMP = $response['valor'];

                            if (!$response['insert']) {
                                throw new Exception("Licencias MoP: {$response['msg']}");
                            }
                        }
                    }

                    //liquidar licencias no remuneradas
                    $filtro = $licenciaNR[$id_empleado] ?? [];
                    if (!empty($filtro)) {
                        $response = (new Liquidacion($this->conexion))->LiquidaLicenciaNoRem($filtro, $param, $mes);
                        if (!$response['insert']) {
                            throw new Exception("Licencias no remuneradas: {$response['msg']}");
                        }
                    }

                    //liquidar licencia por luto
                    $valTotLicLuto = 0;
                    $filtro = $licenciaLuto[$id_empleado] ?? [];
                    if (!empty($filtro)) {
                        $response = (new Liquidacion($this->conexion))->LiquidaLicenciaLuto($filtro, $param);
                        $valTotLicLuto = $response['valor'];
                        if (!$response['insert']) {
                            throw new Exception("Licencias por luto: {$response['msg']}");
                        }
                    }

                    //liquidar indemnización por vacaciones
                    $filtro = $indemVacaciones[$id_empleado][0] ?? [];
                    $valTotIndemVac = 0;
                    if (!empty($filtro)) {
                        $response = (new Liquidacion($this->conexion))->LiquidaIndemnizaVacaciones($filtro, $param);
                        $valTotIndemVac = $response['valor'];
                        if (!$response['insert']) {
                            throw new Exception("Indemnización por vacaciones: {$response['msg']}");
                        }
                    }

                    //liquidar BSP
                    // verificar que tenga  1 entonces se liquida bps
                    $valTotalBSP = 0;
                    if ($empleados[$id_empleado]['bsp'] == 1) {
                        if (isset($bonificaciones[$id_empleado])) {
                            $dBsp           = $bonificaciones[$id_empleado];
                            $valTotalBSP    = $dBsp['val_bsp'];
                            $data = [
                                'numValor'      => $dBsp['val_bsp'],
                                'datFecCorte'   => $dBsp['fec_corte'],
                                'tipo'          => 'P',
                                'id'            => $dBsp['id_bonificaciones'],
                            ];
                            (new Bsp($this->conexion))->editRegistro($data);
                        } else {
                            $fecha_corte = $cortes_empleado['val_bsp']  == '' ? $cortes_empleado['inicia_ctt'] : $cortes_empleado['corte_bsp'];
                            //verificar si hay 360 día para la bonificiacion sacandolo los dias entre fecha_corte y fecha_fin
                            $tiene_bsp = (strtotime($fin) - strtotime($fecha_corte)) / (60 * 60 * 24) >= 360;
                            if ($tiene_bsp) {
                                $param['corte'] = $fecha_corte;
                                $response = (new Liquidacion($this->conexion))->LiquidaBSP($param);
                                $valTotalBSP = $response['valor'];
                                if (!$response['insert']) {
                                    throw new Exception("BSP: {$response['msg']}");
                                }
                            }
                        }
                    }

                    //laborado 
                    $valTotalLab = $laborado[$id_empleado] * ($param['salario'] / 30);
                    $valAuxTrans = $laborado[$id_empleado] * ($param['aux_trans'] / 30);
                    $valAuxAlim = $laborado[$id_empleado] * ($param['aux_alim'] / 30);
                    $grepre = $empleados[$id_empleado]['representacion'] == 1 ? $parametro[8] : 0;

                    $Otros = new Otros();
                    $labd = $Otros->getRegistroLiq(['id_empleado' => $id_empleado, 'id_nomina' => $id_nomina]);
                    if (!empty($labd) && $labd['tipo'] == 'M') {
                        $valTotalLab    = $labd['val_laborado'];
                        $valAuxTrans    = $labd['val_auxtrans'];
                        $valAuxAlim     = $labd['auxalim'];
                        $grepre         = $labd['grepre'];
                    } else {
                        $data = [
                            'id_empleado'       =>  $id_empleado,
                            'dias_laborados'    =>  $laborado[$id_empleado],
                            'val_laborado'      =>  $valTotalLab,
                            'val_aux_trans'     =>  $valAuxTrans,
                            'val_aux_alim'      =>  $valAuxAlim,
                            'val_grep'          =>  $grepre,
                            'val_horas_ex'      =>  $valTotalHe,
                            'id_nomina'         =>  $id_nomina,
                        ];
                        $response = (new Liquidacion($this->conexion))->LiquidaLaborado($data);
                        if (!$response['insert']) {
                            throw new Exception("Laborado: {$response['msg']}");
                        }
                    }
                    //Seguridad social
                    if ($empleados[$id_empleado]['salario_integral'] == 1) {
                        $ibc = $valTotalLab * 0.7;
                    } else {
                        $ibc = $valTotalLab + $valTotalHe + $valTotIncap + $valTotalBSP + $grepre + $valTotLicLuto + $valTotLicMP + $valTotVac;
                    }

                    $response = (new Liquidacion($this->conexion))->LiquidaSeguridadSocial($param, $novedad, $ibc, $tipo_emp, $subtipo_emp, $laborado[$id_empleado]);
                    $valTotSegSoc = $response['valor'];
                    if (!$response['insert']) {
                        throw new Exception("Seguridad social: {$response['msg']}");
                    }

                    //Parafiscales
                    $ibc = $ibc - $valTotIncap;
                    $response = (new Liquidacion($this->conexion))->LiquidaParafiscales($param, $ibc, $empresa['exonera_aportes'], $tipo_emp);
                    if (!$response['insert']) {
                        throw new Exception("Parafiscales: {$response['msg']}");
                    }

                    //Apropiaciones: Vacaciones, Prima de Vacaciones, bonificacion de recreacion, Prima de Servicios, Prima de navidad, Cesantias, Int. Cesantias.
                    //Reserva vacaciones

                    $filtro = [
                        'id_vac' => 0,
                        'dias_habiles'  => 15,
                        'dias_inactivo' => 22,
                        'dias_liquidar' => $laborado[$id_empleado],
                        'corte' => '',
                        'id_nomina' => 0,
                    ];
                    $response       =   (new Liquidacion($this->conexion))->LiquidaVacaciones($filtro, $param, 0);
                    $valMesVac      =   $response['valor'];
                    $valMesPrimVac  =   $response['prima'];
                    $valMesBonRec   =   $response['bono'];
                    if (!$response['insert']) {
                        throw new Exception("Vacaciones Mes: {$response['msg']}");
                    }
                    //Reserva Prima de Servicios

                    $response = (new Liquidacion($this->conexion))->LiquidaPrimaServicios($param, $cortes_empleado, $laborado[$id_empleado], 0);
                    $valMesPriSer = $response['valor'];
                    if (!$response['insert']) {
                        throw new Exception("Prima de Servicios Mes: {$response['msg']}");
                    }

                    //Reserva Prima de Navidad
                    $response = (new Liquidacion($this->conexion))->LiquidaPrimaNavidad($param, $cortes_empleado, $laborado[$id_empleado], 0);
                    $valMesPriNav = $response['valor'];
                    if (!$response['insert']) {
                        throw new Exception("Prima de Navidad Mes: {$response['msg']}");
                    }

                    //Reserva Cesantias
                    $response = (new Liquidacion($this->conexion))->LiquidaCesantias($param, $cortes_empleado, $laborado[$id_empleado], 0);
                    $valMesCes = $response['valor'];
                    $valMesIntCes = $response['interes'];
                    if (!$response['insert']) {
                        throw new Exception("Cesantias Mes: {$response['msg']}");
                    }
                    $data = [
                        'id_empleado'           =>  $id_empleado,
                        'id_nomina'             =>  $id_nomina,
                        'val_vacacion'          =>  $valMesVac,
                        'val_cesantia'          =>  $valMesCes,
                        'val_interes_cesantia'  =>  $valMesIntCes,
                        'val_prima'             =>  $valMesPriSer,
                        'val_prima_vac'         =>  $valMesPrimVac,
                        'val_prima_nav'         =>  $valMesPriNav,
                        'val_bonifica_recrea'   =>  $valMesBonRec,
                    ];
                    $response = (new Prestaciones_Sociales($this->conexion))->addRegistroLiq($data);
                    if ($response != 'si') {
                        throw new Exception("Prestaciones sociales: $response");
                    }

                    $baseDctos = $valTotalLab + $valAuxTrans + $valAuxAlim + $valTotalHe + $valTotIncap + $valTotVac + $valTotLicMP + $valTotLicLuto + $valTotalBSP + $valTotPrimVac + $valBonRec + $grepre + $valTotIndemVac - ($valTotSegSoc ?? 0);

                    //Deducciones

                    //embargos
                    $filtro = $embargos[$id_empleado] ?? [];
                    if (!empty($filtro)) {
                        $response = (new Liquidacion($this->conexion))->LiquidaEmbargos($filtro, $param, $baseDctos);
                        $baseDctos  = $baseDctos - $response['valor'];
                        if (!$response['insert']) {
                            throw new Exception("Embargos: {$response['msg']}");
                        }
                    }

                    //sindicatos
                    $filtro = $sindicatos[$id_empleado][0] ?? [];
                    if (!empty($filtro)) {
                        $response = (new Liquidacion($this->conexion))->LiquidaSindicato($filtro, $param, $baseDctos);
                        $baseDctos  = $baseDctos - $response['valor'];
                        if (!$response['insert']) {
                            throw new Exception("Sindicatos: {$response['msg']}");
                        }
                    }
                    //libranzas
                    $filtro = $libranzas[$id_empleado] ?? [];
                    if (!empty($filtro)) {
                        $response = (new Liquidacion($this->conexion))->LiquidaLibranzas($filtro, $param, $baseDctos);
                        $baseDctos  = $baseDctos - $response['valor'];
                        if (!$response['insert']) {
                            throw new Exception("Libranzas: {$response['msg']}");
                        }
                    }

                    //otros descuentos
                    $filtro = $otrosDctos[$id_empleado] ?? [];
                    if (!empty($filtro)) {
                        $response = (new Liquidacion($this->conexion))->LiquidaOtrosDctos($filtro, $param, $baseDctos);
                        $baseDctos  = $baseDctos - $response['valor'];;
                        if (!$response['insert']) {
                            throw new Exception("Otros descuentos: {$response['msg']}");
                        }
                    }

                    $baseDep = $valTotalLab + $valTotalBSP + $valTotalHe + $valTotVac + $valTotPrimVac + $valBonRec + $grepre;
                    $pagoxdependiente = $empleados[$id_empleado]['dependientes'] == 0 ? 0 : $baseDep * 0.1;
                    $valIntViv = $iVivienda[$id_empleado] ?? 0;
                    $valrf = $baseDep + $valTotIndemVac + $valTotLicLuto - ($valTotSegSoc ?? 0) - $pagoxdependiente - $valIntViv;
                    $valdpurado =  $valrf * 0.75;
                    $uvt = $param['uvt'];
                    $ingLabUvt = $empleados[$id_empleado]['salario_integral'] == 1 ? $valTotalLab * 0.75 / $uvt :  $valdpurado / $uvt;

                    $totValRetFte = 0;
                    $data = [
                        'id_empleado'   =>  $id_empleado,
                        'id_nomina'     =>  $id_nomina,
                        'base'          =>  $valdpurado,
                        'ing_uvt'       =>  $ingLabUvt,
                        'uvt'           =>  $uvt,
                    ];
                    $response = (new Liquidacion($this->conexion))->LiquidaRetencionFuente($data);
                    $totValRetFte = $response['valor'];
                    if (!$response['insert']) {
                        throw new Exception("Retención en la fuente: {$response['msg']}");
                    }

                    $neto = $baseDctos - $totValRetFte;
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
            return 'Liquidación realizada con éxito.';
        }
    }

    /**
     * Obtiene las fechas de retiro de los contratos de empleados
     */
    private function getFechasRetiro($ids, $fin)
    {
        if (empty($ids)) {
            return [];
        }
        $idsStr = implode(',', $ids);
        $mes_inicio = date('Y-m-01', strtotime($fin));

        try {
            $sql = "SELECT 
                        `id_empleado`, `fec_fin`, `fec_inicio`
                    FROM `nom_contratos_empleados`
                    WHERE `id_contrato_emp` IN (
                        SELECT MAX(`id_contrato_emp`) 
                        FROM `nom_contratos_empleados` 
                        WHERE `id_empleado` IN ($idsStr) AND `estado` = 1 
                        AND `fec_fin` BETWEEN '$mes_inicio' AND '$fin'
                        GROUP BY `id_empleado`
                    )";
            $stmt = $this->conexion->prepare($sql);
            $stmt->execute();
            $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
            return array_column($result, null, 'id_empleado');
        } catch (PDOException $e) {
            return [];
        }
    }

    /**
     * Calcula los días para cada concepto de prestaciones sociales
     * usando año de 360 días
     */
    private function calcularDiasPrestaciones($param, $cortes)
    {
        $fec_retiro = $param['fec_retiro'];
        $fec_inicio = $param['fec_inicio_ctt'];

        // Días para BSP
        $corte_bsp = $cortes['corte_bsp'] ?? $fec_inicio;
        $dias_bsp = $this->calcularDias360($corte_bsp, $fec_retiro);

        // Días para Cesantías
        $corte_ces = $cortes['corte_ces'] ?? $fec_inicio;
        $dias_ces = $this->calcularDias360($corte_ces, $fec_retiro);

        // Días para Prima de Servicios
        $corte_prima_sv = $cortes['corte_prim_sv'] ?? $fec_inicio;
        $dias_prima_serv = $this->calcularDias360($corte_prima_sv, $fec_retiro);

        // Días para Vacaciones
        $corte_vac = $cortes['corte_vac'] ?? $fec_inicio;
        $dias_vac = $this->calcularDias360($corte_vac, $fec_retiro);

        // Días para Prima de Navidad
        $corte_prima_nav = $cortes['corte_prim_nav'] ?? $fec_inicio;
        $dias_prima_nav = $this->calcularDias360($corte_prima_nav, $fec_retiro);

        return [
            'bsp'           => $dias_bsp,
            'cesantias'     => $dias_ces,
            'prima_serv'    => $dias_prima_serv,
            'vacaciones'    => $dias_vac,
            'prima_nav'     => $dias_prima_nav,
        ];
    }

    /**
     * Calcula días entre dos fechas usando año de 360 días
     */
    private function calcularDias360($fechaInicio, $fechaFin)
    {
        $fechaI = strtotime($fechaInicio);
        $fechaF = strtotime($fechaFin);
        $dias360 = 0;

        if (!($fechaI > $fechaF)) {
            while ($fechaI < $fechaF) {
                $dias360 += 30;
                $fechaI = strtotime('+1 month', $fechaI);
            }
            $dias360 += ($fechaF - $fechaI) / (60 * 60 * 24);
            $dias360 = $dias360 + 1;
        }

        return max(0, $dias360);
    }

    /**
     * Registra empleado como retirado
     */
    private function registrarEmpleadoRetirado($id_empleado, $id_nomina, $fec_retiro)
    {
        try {
            $sql = "INSERT INTO `nom_empleados_retirados`(`id_empleado`, `fec_liq`, `id_user_reg`, `fec_reg`, `id_nomina`)
                    VALUES (?, ?, ?, ?, ?)";
            $stmt = $this->conexion->prepare($sql);
            $stmt->bindValue(1, $id_empleado, PDO::PARAM_INT);
            $stmt->bindValue(2, $fec_retiro, PDO::PARAM_STR);
            $stmt->bindValue(3, Sesion::IdUser(), PDO::PARAM_INT);
            $stmt->bindValue(4, Sesion::Hoy(), PDO::PARAM_STR);
            $stmt->bindValue(5, $id_nomina, PDO::PARAM_INT);
            $stmt->execute();
        } catch (PDOException $e) {
            // No lanzar excepción, es opcional
        }
    }
    /**
     * Obtiene empleados liquidados
     */
    private function getUltimoSalarioLiquidado()
    {
        try {
            $sql = "SELECT
                        `id_empleado`,`id_nomina`
                    FROM `nom_liq_salario`
                    WHERE `id_sal_liq` IN
                        (SELECT
                            MAX(`nls`.`id_sal_liq`)
                        FROM
                            `nom_liq_salario` AS `nls`
                            INNER JOIN `nom_nominas` AS `nn` 
                            ON (`nls`.`id_nomina` = `nn`.`id_nomina`)
                        WHERE (`nn`.`tipo` = 'N' AND `nn`.`estado` = 5 AND `nls`.`estado` = 1)
                        GROUP BY `nls`.`id_contrato`)";
            $stmt = $this->conexion->prepare($sql);
            $stmt->execute();
            $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
            return $result;
        } catch (PDOException $e) {
            return [];
        }
    }

    public function addRegistroLiq($array)
    {
        try {
            $sql = "INSERT INTO `nom_liq_prestaciones_sociales`
                        (`id_empleado`,`val_vacacion`,`val_cesantia`,`val_interes_cesantia`,`val_prima`,`val_prima_vac`,`val_prima_nav`,`val_bonifica_recrea`,`id_user_reg`,`fec_reg`,`id_nomina`)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $stmt = $this->conexion->prepare($sql);
            $stmt->bindValue(1, $array['id_empleado'], PDO::PARAM_INT);
            $stmt->bindValue(2, $array['val_vacacion'], PDO::PARAM_STR);
            $stmt->bindValue(3, $array['val_cesantia'], PDO::PARAM_STR);
            $stmt->bindValue(4, $array['val_interes_cesantia'], PDO::PARAM_STR);
            $stmt->bindValue(5, $array['val_prima'], PDO::PARAM_STR);
            $stmt->bindValue(6, $array['val_prima_vac'], PDO::PARAM_STR);
            $stmt->bindValue(7, $array['val_prima_nav'], PDO::PARAM_STR);
            $stmt->bindValue(8, $array['val_bonifica_recrea'], PDO::PARAM_STR);
            $stmt->bindValue(9, Sesion::IdUser(), PDO::PARAM_INT);
            $stmt->bindValue(10, Sesion::Hoy(), PDO::PARAM_STR);
            $stmt->bindValue(11, $array['id_nomina'], PDO::PARAM_INT);
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
        return 'No se ha implementado aún';
        try {
            $this->conexion->beginTransaction();
            $sql = "UPDATE `nom_indemniza_vac`
                        SET `fec_inica` = ?, `fec_fin` = ?, `cant_dias` = ?
                    WHERE `id_indemniza` = ?";
            $stmt = $this->conexion->prepare($sql);
            $stmt->bindValue(1, $array['datFecInicia'], PDO::PARAM_STR);
            $stmt->bindValue(2, $array['datFecFin'], PDO::PARAM_STR);
            $stmt->bindValue(3, $array['diasInactivo'], PDO::PARAM_INT);
            $stmt->bindValue(4, $array['id'], PDO::PARAM_INT);

            if ($stmt->execute() && $stmt->rowCount() > 0) {
                $consulta = "UPDATE `nom_indemniza_vac` SET `fec_act` = ?, `id_user_act` = ? WHERE `id_indemniza` = ?";
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
            $this->conexion->rollBack();
            return 'Error SQL: ' . $e->getMessage();
        }
    }

    public function annulRegistro($array)
    {
        return 'Falta programar la anulación de registro de seguridad social.';
    }
}
