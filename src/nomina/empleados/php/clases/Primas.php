<?php

namespace Src\Nomina\Empleados\Php\Clases;

use Config\Clases\Conexion;
use Config\Clases\Sesion;
use Exception;
use PDO;
use PDOException;
use Src\Nomina\Liquidacion\Php\Clases\Liquidacion;
use Src\Nomina\Liquidacion\Php\Clases\Nomina;

/**
 * Clase para gestionar las primas de los empleados.
 *
 * Esta clase permite realizar operaciones CRUD sobre las primas de los empleados,
 * incluyendo la obtención de registros, adición, edición y eliminación de primas.
 */
class Primas
{
    private $conexion;

    public function __construct($conexion = null)
    {
        $this->conexion = $conexion ?: Conexion::getConexion(); // Método estático que retorna el objeto PDO
    }
    /**
     * Agrega un nuevo registro.
     *
     * @param array $array Datos del registro a agregar
     * @return string Mensaje de éxito o error
     */
    public function addRegistroPsPn($array, $opcion = 0)
    {
        $ids =          $array['chk_liquidacion'];
        $contratos =    $array['id_contrato'];
        $mpago =        $array['metodo'];
        $tipo =         $array['tipo'];
        $mes =          $array['mes'];
        $incremento =   isset($array['incremento']) ? $array['incremento'] : NULL;
        $nomina =       Nomina::getIDNomina($mes, $tipo);

        // Verificar si necesitamos crear la nómina de cesantías
        $crearNominaCes = ($nomina['id_nomina'] > 0 && $nomina['estado'] >= 2) || $nomina['id_nomina'] == 0;

        if ($crearNominaCes) {
            $res = Nomina::addRegistro($mes, $tipo, $incremento);
            if ($res['status'] == 'si') {
                $id_nomina = $res['id'];
            } else {
                return $res['msg'];
            }
        } else {
            $id_nomina = $nomina['id_nomina'];
        }

        if ($opcion == 0) {
            $data = Nomina::getParamLiq();
            if (empty($data)) {
                return 'No se han configurado los parámetros de liquidación.';
            }

            $parametro = array_column($data, 'valor', 'id_concepto');

            if (empty($parametro[1]) || empty($parametro[6])) {
                return 'No se han Configurado los parámetros de liquidación.';
            }
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

        $cortes =       array_column(((new Liquidacion())->getCortes($ids, $fin)), null, 'id_empleado');
        $liquidados =   (new Liquidacion())->getEmpleadosLiq($id_nomina, $ids);
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
            $param['tipo'] =            $tipo;
        }

        $inserts = 0;
        foreach ($ids as $id_empleado) {
            if (!isset($liquidados[$id_empleado]) && isset($salarios[$id_empleado])) {
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

                    //Prima de Servicios
                    if ($tipo == 6) {
                        $dias = (new Cesantias($this->conexion))->calcularDias($cortes_empleado['corte_prim_sv'], $fin, $id_empleado);
                        $dias = $dias > 360 ? 360 : $dias;
                        $response = (new Liquidacion($this->conexion))->LiquidaPrimaServicios($param, $cortes_empleado, $dias, 1);
                        if (!$response['insert']) {
                            throw new Exception("Prima de Servicios: {$response['msg']}");
                        }
                    } else if ($tipo == 7) { //Prima de Navidad
                        $dias = (new Cesantias($this->conexion))->calcularDias($cortes_empleado['corte_prim_nav'], $fin, $id_empleado);
                        $dias = $dias > 360 ? 360 : $dias;
                        $response = (new Liquidacion($this->conexion))->LiquidaPrimaNavidad($param, $cortes_empleado, $dias, 1);
                        if (!$response['insert']) {
                            throw new Exception("Prima de Navidad: {$response['msg']}");
                        }
                    } else {
                        throw new Exception("Tipo de nomina no valido");
                    }

                    $neto = 0;
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
     * Obtiene un registro por ID.
     *
     * @param int $id ID del registro 
     * @return array  datos del registro
     */

    public function getRegistroLiq1($a)
    {
        $sql = "SELECT `id_liq_prima` FROM `nom_liq_prima` WHERE `id_empleado` = ? AND `id_nomina` = ? AND `estado` = 1";
        $stmt = $this->conexion->prepare($sql);
        $stmt->bindParam(1, $a['id_empleado'], PDO::PARAM_INT);
        $stmt->bindParam(2, $a['id_nomina'], PDO::PARAM_INT);
        $stmt->execute();
        $data = $stmt->fetch(PDO::FETCH_ASSOC);
        $stmt->closeCursor();
        unset($stmt);
        return !empty($data) ? $data['id_liq_prima'] : 0;
    }

    public function getRegistroLiq2($a)
    {
        $sql = "SELECT `id_liq_privac` FROM `nom_liq_prima_nav` WHERE `id_empleado` = ? AND `id_nomina` = ? AND `estado` = 1";
        $stmt = $this->conexion->prepare($sql);
        $stmt->bindParam(1, $a['id_empleado'], PDO::PARAM_INT);
        $stmt->bindParam(2, $a['id_nomina'], PDO::PARAM_INT);
        $stmt->execute();
        $data = $stmt->fetch(PDO::FETCH_ASSOC);
        $stmt->closeCursor();
        unset($stmt);
        return !empty($data) ? $data['id_liq_privac'] : 0;
    }

    public function addRegistroLiq1($array)
    {
        try {
            $sql = "INSERT INTO `nom_liq_prima`
                        (`id_empleado`,`cant_dias`,`val_liq_ps`,`val_liq_pns`,`periodo`,`corte`,`id_user_reg`,`fec_reg`,`id_nomina`,`tipo`)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $stmt = $this->conexion->prepare($sql);
            $stmt->bindValue(1, $array['id_empleado'], PDO::PARAM_INT);
            $stmt->bindValue(2, $array['cant_dias'], PDO::PARAM_INT);
            $stmt->bindValue(3, $array['val_liq_ps'], PDO::PARAM_STR);
            $stmt->bindValue(4, $array['val_liq_pns'], PDO::PARAM_STR);
            $stmt->bindValue(5, $array['periodo'], PDO::PARAM_STR);
            $stmt->bindValue(6, $array['corte'], PDO::PARAM_STR);
            $stmt->bindValue(7, Sesion::IdUser(), PDO::PARAM_INT);
            $stmt->bindValue(8, Sesion::Hoy(), PDO::PARAM_STR);
            $stmt->bindValue(9, $array['id_nomina'], PDO::PARAM_INT);
            $stmt->bindValue(10, $array['tipo'] ?? 'S', PDO::PARAM_STR);
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
            $sql = "INSERT INTO `nom_liq_prima_nav`
                    (`id_empleado`,`cant_dias`,`val_liq_pv`,`val_liq_pnv`,`periodo`,`corte`,`id_user_reg`,`fec_reg`,`id_nomina`,`tipo`)
                VALUES(?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $stmt = $this->conexion->prepare($sql);
            $stmt->bindValue(1, $array['id_empleado'], PDO::PARAM_INT);
            $stmt->bindValue(2, $array['cant_dias'], PDO::PARAM_INT);
            $stmt->bindValue(3, $array['val_liq_pv'], PDO::PARAM_STR);
            $stmt->bindValue(4, $array['val_liq_pnv'], PDO::PARAM_STR);
            $stmt->bindValue(5, $array['periodo'], PDO::PARAM_STR);
            $stmt->bindValue(6, $array['corte'], PDO::PARAM_STR);
            $stmt->bindValue(7, Sesion::IdUser(), PDO::PARAM_INT);
            $stmt->bindValue(8, Sesion::Hoy(), PDO::PARAM_STR);
            $stmt->bindValue(9, $array['id_nomina'], PDO::PARAM_INT);
            $stmt->bindValue(10, $array['tipo'] ?? 'S', PDO::PARAM_STR);
            $stmt->execute();
            $id = $this->conexion->lastInsertId();
            if ($id > 0) {
                return 'si';
            } else {
                return 'no';
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
    public function editRegistroLiq1($array)
    {
        try {
            $sql = "UPDATE `nom_liq_prima`
                        SET `cant_dias` = ?, `val_liq_ps` = ?,`corte` = ?
                    WHERE `id_liq_prima` = ?";
            $stmt = $this->conexion->prepare($sql);
            $stmt->bindValue(1, $array['cant_dias'], PDO::PARAM_INT);
            $stmt->bindValue(2, $array['val_liq_ps'], PDO::PARAM_STR);
            $stmt->bindValue(3, $array['corte'], PDO::PARAM_STR);
            $stmt->bindValue(4, $array['id'], PDO::PARAM_INT);

            if ($stmt->execute() && $stmt->rowCount() > 0) {
                $consulta = "UPDATE `nom_liq_prima` SET `fec_act` = ?, `id_user_act` = ? WHERE `id_liq_prima` = ?";
                $stmt2 = $this->conexion->prepare($consulta);
                $stmt2->bindValue(1, Sesion::Hoy(), PDO::PARAM_STR);
                $stmt2->bindValue(2, Sesion::IdUser(), PDO::PARAM_INT);
                $stmt2->bindValue(3, $array['id'], PDO::PARAM_INT);
                $stmt2->execute();
                return 'si';
            } else {
                return 'no';
            }
        } catch (PDOException $e) {
            return 'Error SQL: ' . $e->getMessage();
        }
    }

    public function editRegistroLiq2($array)
    {
        try {
            $sql = "UPDATE `nom_liq_prima_nav`
                        SET `cant_dias` = ?, `val_liq_pv` = ?, `corte` = ?
                    WHERE `id_liq_privac` = ?";
            $stmt = $this->conexion->prepare($sql);
            $stmt->bindValue(1, $array['cant_dias'], PDO::PARAM_INT);
            $stmt->bindValue(2, $array['val_liq_pv'], PDO::PARAM_STR);
            $stmt->bindValue(3, $array['corte'], PDO::PARAM_STR);
            $stmt->bindValue(4, $array['id'], PDO::PARAM_INT);

            if ($stmt->execute() && $stmt->rowCount() > 0) {
                $consulta = "UPDATE `nom_liq_prima_nav` SET `fec_act` = ?, `id_user_act` = ? WHERE `id_liq_privac` = ?";
                $stmt2 = $this->conexion->prepare($consulta);
                $stmt2->bindValue(1, Sesion::Hoy(), PDO::PARAM_STR);
                $stmt2->bindValue(2, Sesion::IdUser(), PDO::PARAM_INT);
                $stmt2->bindValue(3, $array['id'], PDO::PARAM_INT);
                $stmt2->execute();
                return 'si';
            } else {
                return 'no';
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
