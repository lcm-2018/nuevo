<?php

namespace Src\Nomina\Empleados\Php\Clases;

use Config\Clases\Conexion;
use Config\Clases\Logs;
use Config\Clases\Sesion;
use Exception;
use PDO;
use PDOException;
use Src\Common\Php\Clases\Valores;
use Src\Nomina\Liquidacion\Php\Clases\Liquidacion;
use Src\Nomina\Liquidacion\Php\Clases\Nomina;
use Src\Usuarios\Login\Php\Clases\Usuario;

/**
 * Clase para gestionar las indenminzaciones por vacaciones de los empleados.
 *
 * Esta clase permite realizar operaciones CRUD sobre las indenminzaciones por vacaciones de los empleados,
 * incluyendo la obtención de registros, adición, edición y eliminación de indeminaciones.
 */
class Bsp
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
                $where .= " AND (`fec_corte` LIKE '%{$array['value']}%' 
                            OR `val_bsp` LIKE '%{$array['value']}%')";
            }

            if (isset($array['id']) && $array['id'] > 0) {
                $where .= " AND `id_empleado` = {$array['id']}";
            }
        }

        $sql = "SELECT
                    `id_bonificaciones`,`val_bsp`,`fec_corte`,`tipo`,`estado`
                FROM `nom_liq_bsp`
                WHERE (1 = 1 $where)
                ORDER BY $col $dir $limit";
        $stmt = $this->conexion->prepare($sql);
        $stmt->execute();
        $datos = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $stmt->closeCursor();
        unset($stmt);
        return $datos;
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
                $where .= " AND (`fec_corte` LIKE '%{$array['value']}%' 
                            OR `val_bsp` LIKE '%{$array['value']}%')";
            }

            if (isset($array['id']) && $array['id'] > 0) {
                $where .= " AND `id_empleado` = {$array['id']}";
            }
        }

        $sql = "SELECT
                    COUNT(*) AS `total`
                FROM `nom_liq_bsp`
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
                $where .= " AND `id_empleado` = {$array['id']}";
            }
        }

        $sql = "SELECT
                   COUNT(*) AS `total`
                FROM
                    `nom_liq_bsp`
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
                    `id_bonificaciones`,`val_bsp`,`fec_corte`,`tipo`,`estado`
                FROM `nom_liq_bsp`
                WHERE `id_bonificaciones` = ?";
        $stmt = $this->conexion->prepare($sql);
        $stmt->bindParam(1, $id, PDO::PARAM_INT);
        $stmt->execute();
        $registro = $stmt->fetch(PDO::FETCH_ASSOC);
        $stmt->closeCursor();
        unset($stmt);
        if (empty($registro)) {
            $registro = [
                'id_bonificaciones' => 0,
                'val_bsp' => 0,
                'fec_corte' => Sesion::_Hoy(),
                'tipo' => 0,
                'estado' => 1,
            ];
        }
        return $registro;
    }

    public function getRegistroPorEmpleado($liq = 0)
    {
        $where = '';
        if ($liq !== 0) {
            $where .= " AND `id_nomina` IS NULL";
        }
        $sql = "SELECT
                    `id_empleado`, `val_bsp`, `id_bonificaciones`, `fec_corte`
                FROM `nom_liq_bsp`
                WHERE `estado` = 1 AND `tipo` = 'M' $where";
        $stmt = $this->conexion->prepare($sql);
        $stmt->execute();
        $registro = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $stmt->closeCursor();
        unset($stmt);
        return !empty($registro) ? array_column($registro, null, 'id_empleado') : [];
    }


    public function getRegistroLiq($array)
    {
        $sql = "SELECT
                    `id_bonificaciones` AS `id`, `val_bsp` AS `valor`
                FROM
                    `nom_liq_bsp`
                WHERE (`id_empleado` = ? AND `id_nomina` = ? AND `estado` = 1)";
        $stmt = $this->conexion->prepare($sql);
        $stmt->bindParam(1, $array['id_empleado'], PDO::PARAM_INT);
        $stmt->bindParam(2, $array['id_nomina'], PDO::PARAM_INT);
        $stmt->execute();
        $data = $stmt->fetch(PDO::FETCH_ASSOC);
        $stmt->closeCursor();
        unset($stmt);
        return !empty($data) ? $data : ['id' => 0, 'valor' => 0];
    }
    /**
     * Obtiene el formulario para agregar o editar un registro.
     *
     * @param int $id ID del registro (0 para nuevo)
     * @return string HTML del formulario
     */
    public function getFormulario($data)
    {
        $registro = $this->getRegistro($data['id']);
        $E = new Empleados();
        $salario = $E->getSalario($E->getContratoActivo($data['id_empleado']));
        $vals = Nomina::getParamLiq();
        $vals = array_column($vals, 'valor', 'id_concepto');
        $gasrep = $E->getEmpleados($data['id_empleado']) == 0 ? 0 : $vals[8] ?? 0;
        $bsp = (($salario + $gasrep) <= $vals[7] ? ($salario + $gasrep) * 0.5 : ($salario + $gasrep) * 0.35);
        $bsp = $data['id'] == 0 ? $bsp : $registro['val_bsp'];
        $html =
            <<<HTML
                <div class="shadow text-center rounded">
                    <div class="rounded-top py-2" style="background-color: #16a085 !important;">
                        <h5 style="color: white;" class="mb-0">GESTIÓN DE BONIFICACIÓN DE SERVICIOS</h5>
                    </div>
                    <div class="p-3">
                        <form id="formBsp">
                            <input type="hidden" id="id" name="id" value="{$data['id']}">
                            <div class="row mb-2">
                                <div class="col-md-12">
                                    <label for="numValor" class="small text-muted">Valor</label>
                                    <input type="number" class="form-control form-control-sm bg-input text-end" id="numValor" name="numValor" value="{$bsp}" min="0">
                                </div>
                            </div>
                            <div class="row mb-2">
                                <div class="col-md-12">
                                    <label for="datFecCorte" class="small text-muted">Corte</label>
                                    <input type="date" class="form-control form-control-sm bg-input" id="datFecCorte" name="datFecCorte" value="{$registro['fec_corte']}">
                                </div>
                            </div>
                        </form>
                    </div>
                    <div class="text-center pb-3">
                        <button type="button" class="btn btn-primary btn-sm" id="btnGuardarBsp">Guardar</button>
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
            $sql = "DELETE FROM `nom_liq_bsp` WHERE `id_bonificaciones` = ?";
            $consulta = "DELETE FROM `nom_liq_bsp` WHERE `id_bonificaciones` = $id";
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
    public function addRegistro($array)
    {
        try {
            $sql = "INSERT INTO `nom_liq_bsp`
                        (`id_empleado`,`val_bsp`,`fec_corte`, `tipo`, `id_user_reg`,`fec_reg`,`id_nomina`)
                    VALUES (?, ?, ?, ?, ?, ?, ?)";
            $stmt = $this->conexion->prepare($sql);
            $stmt->bindValue(1, $array['id_empleado'], PDO::PARAM_INT);
            $stmt->bindValue(2, $array['numValor'], PDO::PARAM_STR);
            $stmt->bindValue(3, $array['datFecCorte'], PDO::PARAM_STR);
            $stmt->bindValue(4, $array['tipo'] ?? 'S', PDO::PARAM_STR);
            $stmt->bindValue(5, Sesion::IdUser(), PDO::PARAM_INT);
            $stmt->bindValue(6, Sesion::Hoy(), PDO::PARAM_STR);
            $stmt->bindValue(7, $array['id_nomina'] ?? NULL, PDO::PARAM_INT);
            $stmt->execute();
            $id = $this->conexion->lastInsertId();
            $stmt->closeCursor();
            unset($stmt);
            if ($id > 0) {
                $tipo = $array['tipo'] ?? 'S';
                $idNomina = $array['id_nomina'] ?? 'NULL';
                $idUser = Sesion::IdUser();
                $hoy = Sesion::Hoy();
                Logs::guardaLog("INSERT INTO `nom_liq_bsp` (`id_empleado`,`val_bsp`,`fec_corte`, `tipo`, `id_user_reg`,`fec_reg`,`id_nomina`) VALUES ({$array['id_empleado']}, {$array['numValor']}, '{$array['datFecCorte']}', '$tipo', $idUser, '$hoy', $idNomina)");
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
            $sql = "UPDATE `nom_liq_bsp`
                        SET `val_bsp` = ?, `fec_corte` = ?, `tipo` = ?
                    WHERE `id_bonificaciones` = ?";
            $stmt = $this->conexion->prepare($sql);
            $stmt->bindValue(1, $array['numValor'], PDO::PARAM_STR);
            $stmt->bindValue(2, $array['datFecCorte'], PDO::PARAM_STR);
            $stmt->bindValue(3, $array['tipo'], PDO::PARAM_STR);
            $stmt->bindValue(4, $array['id'], PDO::PARAM_INT);
            if ($stmt->execute() && $stmt->rowCount() > 0) {
                $stmt->closeCursor();
                Logs::guardaLog("UPDATE `nom_liq_bsp` SET `val_bsp` = {$array['numValor']}, `fec_corte` = '{$array['datFecCorte']}', `tipo` = '{$array['tipo']}' WHERE `id_bonificaciones` = {$array['id']}");
                $consulta = "UPDATE `nom_liq_bsp` SET `fec_act` = ?, `id_user_act` = ? WHERE `id_bonificaciones` = ?";
                $stmt2 = $this->conexion->prepare($consulta);
                $hoy = Sesion::Hoy();
                $idUser = Sesion::IdUser();
                $stmt2->bindValue(1, $hoy, PDO::PARAM_STR);
                $stmt2->bindValue(2, $idUser, PDO::PARAM_INT);
                $stmt2->bindValue(3, $array['id'], PDO::PARAM_INT);
                $stmt2->execute();
                $stmt2->closeCursor();
                unset($stmt2);
                Logs::guardaLog("UPDATE `nom_liq_bsp` SET `fec_act` = '$hoy', `id_user_act` = $idUser WHERE `id_bonificaciones` = {$array['id']}");
                return 'si';
            } else {
                return 'no';
            }
        } catch (PDOException $e) {
            return 'Error SQL: ' . $e->getMessage();
        }
    }

    /**
     * Liquida de forma independiente las BSP manuales (tipo='M', id_nomina IS NULL)
     * de los empleados seleccionados, sin calcular seguridad social, parafiscales
     * ni retenciones. Equivalente a Cesantias::addRegistroN para ese concepto.
     *
     * Comportamiento:
     *  - Obtiene las BSP manuales pendientes (getRegistroPorEmpleado(1)).
     *  - Por cada empleado seleccionado que tenga BSP pendiente:
     *      · Actualiza el registro BSP: tipo='P' y asigna id_nomina (ancla al periodo).
     *      · Inserta en nom_liq_salario con val_liq=0 para que aparezca en detalles.
     *      · Hace commit individual por empleado.
     *  - Si el empleado no tiene BSP manual pendiente, lo omite (continue).
     *
     * @param array $array  Datos del formulario de liquidación
     *                      (chk_liquidacion, id_contrato, metodo, tipo, mes, incremento)
     * @return string 'si' | mensaje de error
     */
    public function addRegistroN($array)
    {
        $ids       = $array['chk_liquidacion'];
        $contratos = $array['id_contrato'];
        $mpago     = $array['metodo'];
        $tipo      = $array['tipo'];
        $mes       = $array['mes'];
        $incremento = isset($array['incremento']) ? $array['incremento'] : NULL;

        // Obtener o crear la nómina tipo 'BS' del periodo
        // Se consulta directamente nom_nominas por tipo='BS', mes y vigencia
        // sin pasar por nom_tipo_liquidacion, ya que 'BS' es un tipo directo
        if (!empty($array['id_nomina_fija'])) {
            $id_nomina = $array['id_nomina_fija'];
        } else {
            $nominaBS = self::getNominaBs($mes);
            if ($nominaBS > 0) {
                $id_nomina = $nominaBS;
            } else {
                // Crear nueva nómina tipo 'BS'
                $vigencia = Sesion::Vigencia();
                $sqlIns = "INSERT INTO `nom_nominas`
                               (`descripcion`,`mes`,`vigencia`,`tipo`,`estado`,`planilla`,`fec_reg`,`id_user_reg`)
                           VALUES (?, ?, ?, 'BS', 1, 1, ?, ?)";
                $stmtIns = $this->conexion->prepare($sqlIns);
                $stmtIns->bindValue(1, 'LIQUIDACIÓN BSP', PDO::PARAM_STR);
                $stmtIns->bindValue(2, $mes, PDO::PARAM_STR);
                $stmtIns->bindValue(3, $vigencia, PDO::PARAM_STR);
                $stmtIns->bindValue(4, Sesion::Hoy(), PDO::PARAM_STR);
                $stmtIns->bindValue(5, Sesion::IdUser(), PDO::PARAM_INT);
                $stmtIns->execute();
                $id_nomina = $this->conexion->lastInsertId();
                $stmtIns->closeCursor();
                unset($stmtIns);
                if (!($id_nomina > 0)) {
                    return 'No se pudo crear la nómina de BSP.';
                }
                $hoy = Sesion::Hoy();
                $idUser = Sesion::IdUser();
                Logs::guardaLog("INSERT INTO `nom_nominas` (`descripcion`,`mes`,`vigencia`,`tipo`,`estado`,`planilla`,`fec_reg`,`id_user_reg`) VALUES ('LIQUIDACIÓN BSP', '$mes', '$vigencia', 'BS', 1, 1, '$hoy', $idUser)");
                // Actualizar descripción con el formato estándar (igual que Nomina::addRegistro)
                $mesNombre = mb_strtoupper(Valores::nombreMes($mes));
                $empresa = (new Usuario())->getEmpresa();
                $descripcion = 'NOMINA N° ' . $id_nomina . ', ' . $mesNombre . ' VIGENCIA ' . $vigencia . ', ADMINISTRATIVO-ASISTENCIAL, EMPLEADOS ADSCRITOS A ' . $empresa['nombre'];
                (new Nomina())->editRegistro(['id_nomina' => $id_nomina, 'descripcion' => $descripcion]);

            }
        }

        // Parámetros de liquidación (salario base para nom_liq_salario)
        $Empleado = new Empleados();
        $salarios = $Empleado->getSalarioMasivo($mes);
        $salarios = array_column($salarios, 'basico', 'id_empleado');

        // BSP manuales pendientes (tipo='M' AND id_nomina IS NULL)
        $bonificaciones = $this->getRegistroPorEmpleado(1);

        $liquidados = (new Liquidacion())->getEmpleadosLiq($id_nomina, $ids);
        $liquidados = array_column($liquidados, 'id_sal_liq', 'id_empleado');

        $error   = '';
        $inserts = 0;

        foreach ($ids as $id_empleado) {
            // Omitir si ya fue liquidado en esta nómina o no tiene salario registrado
            if (isset($liquidados[$id_empleado]) || !isset($salarios[$id_empleado])) {
                continue;
            }

            // Omitir si no tiene BSP manual pendiente
            if (!isset($bonificaciones[$id_empleado])) {
                continue;
            }

            try {
                $dBsp = $bonificaciones[$id_empleado];

                if (!$this->conexion->inTransaction()) {
                    $this->conexion->beginTransaction();
                }

                // 1. Actualizar BSP: tipo='P' + asignar id_nomina (ancla al periodo)
                $sqlUpd = "UPDATE `nom_liq_bsp`
                               SET `tipo` = 'P', `id_nomina` = ?
                           WHERE `id_bonificaciones` = ? AND `estado` = 1";
                $stmtUpd = $this->conexion->prepare($sqlUpd);
                $stmtUpd->bindValue(1, $id_nomina, PDO::PARAM_INT);
                $stmtUpd->bindValue(2, $dBsp['id_bonificaciones'], PDO::PARAM_INT);
                $stmtUpd->execute();
                if ($stmtUpd->rowCount() === 0) {
                    throw new Exception("No se pudo marcar la BSP como procesada (id: {$dBsp['id_bonificaciones']})");
                }
                $stmtUpd->closeCursor();
                unset($stmtUpd);
                Logs::guardaLog("UPDATE `nom_liq_bsp` SET `tipo` = 'P', `id_nomina` = $id_nomina WHERE `id_bonificaciones` = {$dBsp['id_bonificaciones']}");

                // 2. Insertar en nom_liq_salario con val_liq=0
                //    Requerido para que el empleado aparezca en la vista de detalles
                //    (INNER JOIN sobre nom_liq_salario en Detalles::getRegistrosDT)
                $data = [
                    'id_empleado'  => $id_empleado,
                    'id_nomina'    => $id_nomina,
                    'metodo_pago'  => $mpago[$id_empleado],
                    'val_liq'      => 0,
                    'forma_pago'   => 1,
                    'sal_base'     => $salarios[$id_empleado],
                    'id_contrato'  => $contratos[$id_empleado],
                ];
                $response = (new Liquidacion($this->conexion))->LiquidaSalarioNeto($data);
                if (!$response['insert']) {
                    throw new Exception("Salario neto: {$response['msg']}");
                }

                $this->conexion->commit();
                $inserts++;
                gc_collect_cycles();

            } catch (Exception $e) {
                if ($this->conexion->inTransaction()) {
                    $this->conexion->rollBack();
                }
                $empleados = $Empleado->getEmpleados();
                $empleados = array_column($empleados, null, 'id_empleado');
                $doc = $empleados[$id_empleado]['no_documento'] ?? $id_empleado;
                $error .= "<p>ID: $id_empleado ($doc), {$e->getMessage()}</p>";
                continue;
            }
        }

        if ($error != '') {
            return $error;
        } elseif ($inserts == 0) {
            return 'No se liquidó ningún empleado.';
        }
        return 'si';
    }

    /**
     * Devuelve el id_nomina de la nómina tipo 'BS' activa para el mes y vigencia en sesión.
     * Retorna 0 si no existe.
     * Usado por Liquidacion::addRegistro para detectar BSPs pre-liquidadas del periodo.
     *
     * @param string $mes  Mes a consultar (ej. '08')
     * @return int id_nomina o 0
     */
    public static function getNominaBs($mes): int
    {
        try {
            $sql = "SELECT `id_nomina`
                    FROM `nom_nominas`
                    WHERE `tipo` = 'BS'
                      AND `mes` = ?
                      AND `vigencia` = ?
                      AND `estado` > 0
                    ORDER BY `id_nomina` DESC
                    LIMIT 1";
            $stmt = Conexion::getConexion()->prepare($sql);
            $stmt->bindValue(1, $mes, PDO::PARAM_STR);
            $stmt->bindValue(2, Sesion::Vigencia(), PDO::PARAM_STR);
            $stmt->execute();
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            $stmt->closeCursor();
            unset($stmt);
            return !empty($row) ? (int) $row['id_nomina'] : 0;
        } catch (PDOException $e) {
            return 0;
        }
    }

    public function annulRegistro($array)
    {
        return 'Falta programar la anulación de registro de seguridad social.';
    }
}
