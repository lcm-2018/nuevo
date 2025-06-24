<?php

namespace Src\Nomina\Horas_extra\Php\Clases;

use Config\Clases\Conexion;
use Config\Clases\Logs;
use Config\Clases\Sesion;

use PDO;
use PDOException;
use Src\Common\Php\Clases\Combos;
use DateTime;
use Exception;
use Src\Nomina\Empleados\Php\Clases\Empleados;

/**
 * Clase para gestionar horas extras de los empleados.
 *
 * Esta clase permite realizar operaciones CRUD sobre horas extras de los empleados,
 * incluyendo la obtención de registros, adición, edición y eliminación de horas extras.
 */
class Horas_Extra
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
                $where .= " AND `tt`.`no_documento` LIKE '%{$array['filter_nodoc']}%'";
            }
            if (isset($array['filter_nombre']) && $array['filter_nombre'] != '') {
                $where .= " AND `tt`.`nombre` LIKE '%{$array['filter_nombre']}%'";
            }
        }
        $mes = $array['filter_mes'];
        $tipo = $array['filter_tipo'];
        $fec_inicio = Sesion::Vigencia() . '-' . $mes . '-01';
        $fec_fin = date('Y-m-t', strtotime($fec_inicio));

        $sql = "SELECT
                    *
                FROM
                    (SELECT 
                        `e`.`id_empleado`
                        , `e`.`no_documento`
                        , CONCAT_WS(' ', `e`.`nombre1`, `e`.`nombre2`, `e`.`apellido1`, `e`.`apellido2`) AS `nombre`
                        , IFNULL(SUM(CASE WHEN `he`.`id_he` = 1 THEN `he`.`cantidad` ELSE 0 END), 0) AS `do`
                        , IFNULL(SUM(CASE WHEN `he`.`id_he` = 2 THEN `he`.`cantidad` ELSE 0 END), 0) AS `no`
                        , IFNULL(SUM(CASE WHEN `he`.`id_he` = 3 THEN `he`.`cantidad` ELSE 0 END), 0) AS `rno`
                        , IFNULL(SUM(CASE WHEN `he`.`id_he` = 4 THEN `he`.`cantidad` ELSE 0 END), 0) AS `dd`
                        , IFNULL(SUM(CASE WHEN `he`.`id_he` = 5 THEN `he`.`cantidad` ELSE 0 END), 0) AS `rdd`
                        , IFNULL(SUM(CASE WHEN `he`.`id_he` = 6 THEN `he`.`cantidad` ELSE 0 END), 0) AS `ndf`
                        , IFNULL(SUM(CASE WHEN `he`.`id_he` = 7 THEN `he`.`cantidad` ELSE 0 END), 0) AS `rndf`
                        , IFNULL(SUM(`he`.`cantidad`), 0) AS `total`
                    FROM 
                        `nom_empleado` AS `e`
                        LEFT JOIN
                            (SELECT 
                                `id_empleado`, `id_he`, SUM(`cantidad_he`) AS `cantidad`
                            FROM `nom_horas_ex_trab`
                            WHERE `fec_inicio` BETWEEN '$fec_inicio' AND '$fec_fin' AND `tipo` = $tipo
                            GROUP BY `id_empleado`, `id_he`) `he` 
                            ON `he`.`id_empleado` = `e`.`id_empleado`
                    GROUP BY `e`.`id_empleado` HAVING `total` > 0) AS `tt`
                WHERE (1 = 1 $where)
                ORDER BY $col $dir $limit";
        $stmt = $this->conexion->prepare($sql);
        $stmt->execute();
        $datos = $stmt->fetchAll(PDO::FETCH_ASSOC);
        if ($mes == '0') {
            $datos = [];
        }
        return !empty($datos) ? $datos : [];
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
            if (isset($array['filter_nodoc']) && $array['filter_nodoc'] != '') {
                $where .= " AND `tt`.`no_documento` LIKE '%{$array['filter_nodoc']}%'";
            }
            if (isset($array['filter_nombre']) && $array['filter_nombre'] != '') {
                $where .= " AND `tt`.`nombre` LIKE '%{$array['filter_nombre']}%'";
            }
        }
        $mes = $array['filter_mes'];
        $tipo = $array['filter_tipo'];
        $fec_inicio = Sesion::Vigencia() . '-' . $mes . '-01';
        $fec_fin = date('Y-m-t', strtotime($fec_inicio));

        $sql = "SELECT
                    COUNT(*) AS `total`
                FROM
                    (SELECT 
                        `e`.`id_empleado`
                        , `e`.`no_documento`
                        , CONCAT_WS(' ', `e`.`nombre1`, `e`.`nombre2`, `e`.`apellido1`, `e`.`apellido2`) AS `nombre`
                        , IFNULL(SUM(CASE WHEN `he`.`id_he` = 1 THEN `he`.`cantidad` ELSE 0 END), 0) AS `do`
                        , IFNULL(SUM(CASE WHEN `he`.`id_he` = 2 THEN `he`.`cantidad` ELSE 0 END), 0) AS `no`
                        , IFNULL(SUM(CASE WHEN `he`.`id_he` = 3 THEN `he`.`cantidad` ELSE 0 END), 0) AS `rno`
                        , IFNULL(SUM(CASE WHEN `he`.`id_he` = 4 THEN `he`.`cantidad` ELSE 0 END), 0) AS `dd`
                        , IFNULL(SUM(CASE WHEN `he`.`id_he` = 5 THEN `he`.`cantidad` ELSE 0 END), 0) AS `rdd`
                        , IFNULL(SUM(CASE WHEN `he`.`id_he` = 6 THEN `he`.`cantidad` ELSE 0 END), 0) AS `ndf`
                        , IFNULL(SUM(CASE WHEN `he`.`id_he` = 7 THEN `he`.`cantidad` ELSE 0 END), 0) AS `rndf`
                        , IFNULL(SUM(`he`.`cantidad`), 0) AS `total`
                    FROM 
                        `nom_empleado` AS `e`
                        LEFT JOIN
                            (SELECT 
                                `id_empleado`, `id_he`, SUM(`cantidad_he`) AS `cantidad`
                            FROM `nom_horas_ex_trab`
                            WHERE `fec_inicio` BETWEEN '$fec_inicio' AND '$fec_fin' AND `tipo` = $tipo
                            GROUP BY `id_empleado`, `id_he`) `he` 
                            ON `he`.`id_empleado` = `e`.`id_empleado`
                    GROUP BY `e`.`id_empleado` HAVING `total` > 0) AS `tt`
                WHERE (1 = 1 $where)";
        $stmt = $this->conexion->prepare($sql);
        $stmt->execute();
        if ($mes == '0') {
            return 0;
        }
        $registro = $stmt->fetch(PDO::FETCH_ASSOC);
        return !empty($registro) ? $registro['total'] : 0;
    }

    /**
     * Obtiene el total de registros.
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
                    (SELECT 
                        `e`.`id_empleado`
                        , `e`.`no_documento`
                        , CONCAT_WS(' ', `e`.`nombre1`, `e`.`nombre2`, `e`.`apellido1`, `e`.`apellido2`) AS `nombre`
                        , IFNULL(SUM(CASE WHEN `he`.`id_he` = 1 THEN `he`.`cantidad` ELSE 0 END), 0) AS `do`
                        , IFNULL(SUM(CASE WHEN `he`.`id_he` = 2 THEN `he`.`cantidad` ELSE 0 END), 0) AS `no`
                        , IFNULL(SUM(CASE WHEN `he`.`id_he` = 3 THEN `he`.`cantidad` ELSE 0 END), 0) AS `rno`
                        , IFNULL(SUM(CASE WHEN `he`.`id_he` = 4 THEN `he`.`cantidad` ELSE 0 END), 0) AS `dd`
                        , IFNULL(SUM(CASE WHEN `he`.`id_he` = 5 THEN `he`.`cantidad` ELSE 0 END), 0) AS `rdd`
                        , IFNULL(SUM(CASE WHEN `he`.`id_he` = 6 THEN `he`.`cantidad` ELSE 0 END), 0) AS `ndf`
                        , IFNULL(SUM(CASE WHEN `he`.`id_he` = 7 THEN `he`.`cantidad` ELSE 0 END), 0) AS `rndf`
                        , IFNULL(SUM(`he`.`cantidad`), 0) AS `total`
                    FROM 
                        `nom_empleado` AS `e`
                        LEFT JOIN
                            (SELECT 
                                `id_empleado`, `id_he`, SUM(`cantidad_he`) AS `cantidad`
                            FROM `nom_horas_ex_trab`
                            WHERE `fec_inicio` BETWEEN '$fec_inicio' AND '$fec_fin' AND `tipo` = $tipo
                            GROUP BY `id_empleado`, `id_he`) `he` 
                            ON `he`.`id_empleado` = `e`.`id_empleado`
                    GROUP BY `e`.`id_empleado` HAVING `total` > 0) AS `tt`
                WHERE (1 = 1)";
        $stmt = $this->conexion->prepare($sql);
        $stmt->execute();
        if ($mes == '0') {
            return 0;
        }
        $registro = $stmt->fetch(PDO::FETCH_ASSOC);
        return !empty($registro) ? $registro['total'] : 0;
    }

    /**
     * Obtiene el formulario para agregar o editar un registro.
     *
     * @param int $id ID del registro (0 para nuevo)
     * @return string HTML del formulario
     */
    public function getFormulario($id)
    {
        $tipo = self::getTipoHoraExtra(0);
        $html =
            <<<HTML
                <div class="shadow text-center rounded">
                    <div class="rounded-top py-2" style="background-color: #16a085 !important;">
                        <h5 style="color: white;" class="mb-0">GESTIÓN DE HORAS EXTRAS</h5>
                    </div>
                    <div class="p-3">
                        <form id="formHorasExtra">
                            <input type="hidden" id="id" name="id" value="0">
                            <div class="row mb-2">
                                <div class="col-md-12">
                                    <label for="txtBuscaEmpleado" class="small text-muted">Empleado</label>
                                    <input type="text" class="form-control form-control-sm bg-input" id="txtBuscaEmpleado">
                                    <input type="hidden" id="id_empleado" name="id_empleado" value="0">
                                </div>
                            </div>
                            <div class="row mb-2">
                                <div class="col-md-6">
                                    <label for="datFecInicia" class="small text-muted">Fecha Inicio</label>
                                    <input type="datetime-local" class="form-control form-control-sm bg-input text-end" id="datFecInicia" name="datFecInicia">
                                </div>
                                <div class="col-md-6">
                                    <label for="datFecFin" class="small text-muted">Fecha Fin</label>
                                    <input type="datetime-local" class="form-control form-control-sm bg-input text-end" id="datFecFin" name="datFecFin">
                                </div>
                            </div>
                            <div class="row mb-2">
                                <div class="col-md-3">
                                    <label for="numCantidad" class="small text-muted">Cantidad</label>
                                    <input type="number" class="form-control form-control-sm bg-input text-end" id="numCantidad" name="numCantidad">
                                </div>
                                <div class="col-md-5">
                                    <label for="slcTipoHora" class="small text-muted">Tipo de Hora</label>
                                    <select id="slcTipoHora" name="slcTipoHora" class="form-select form-select-sm bg-input">
                                        {$tipo}
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <label for="slcTipo" class="small text-muted">Liquidación</label>
                                    <select id="slcTipo" name="slcTipo" class="form-select form-select-sm bg-input">
                                        <option value="1" selected>MENSUAL</option>
                                        <option value="2">PRESTACIONES SOCIALES</option>
                                    </select>
                                </div>
                            </div>
                            <div class="row mb-2">
                                <div class="col-md-12">
                                    <label for="fileHE" class="form-label">Adjuntar archivo</label>
                                    <input class="form-control form-control-sm bg-input" id="fileHE" name="fileHE" type="file">
                                </div>
                            </div>
                        </form>
                    </div>
                    <div class="text-center pb-3">
                        <button type="button" class="btn btn-primary btn-sm" id="btnGuardaHorasExtra">Guardar</button>
                        <button type="button" class="btn btn-outline-success btn-sm" id="btnFormCsvHorasExtra" title="Descargar formato CSV para cargue masivo"><i class="fas fa-file-csv fa-lg"></i></button>
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
            $sql = "DELETE FROM `nom_horas_ex_trab` WHERE `id_intv` = ?";
            $consulta  = "DELETE FROM `nom_horas_ex_trab` WHERE `id_intv` = $id";
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

    public function addMasivo($files, $post)
    {
        if (!isset($files['fileHE']) || $files['fileHE']['error'] !== UPLOAD_ERR_OK) {
            return 'Error al subir el archivo CSV.';
        }
        $csvFile = $files['fileHE']['tmp_name'];
        $handle = fopen($csvFile, 'r');
        if (!$handle) {
            return 'No se pudo abrir el archivo.';
        }
        $empleados = (new Empleados())->getEmpleados();
        $this->conexion->beginTransaction();
        try {
            $rowIndex = 0;
            while (($row = fgetcsv($handle, 1000, ";")) !== false) {
                // Omitir encabezado
                if ($rowIndex++ === 0) continue;

                // Validar cantidad de columnas
                if (count($row) !== 10) {
                    throw new Exception("Fila $rowIndex: número de columnas incorrecto.");
                }
                // en un array colocar todos los datos del csv de la fila 
                $cedula = trim($row[0]);
                $key = array_search($cedula, array_column($empleados, 'no_documento'));
                if ($key === false) {
                    throw new Exception("Fila $rowIndex: cédula no encontrada.");
                } else {
                    $array = [
                        'id_empleado' => $empleados[$key]['id_empleado'],
                        'datFecInicia' => $this->normalizarFecha($row[1]) . 'T07:00',
                        'datFecFin' => $this->normalizarFecha($row[2]) . 'T23:59',
                        'slcTipoLiq' => $post['slcTipo'],
                    ];
                    for ($i = 3; $i <= 9; $i++) {
                        if ($row[$i] > 0) {
                            $array['slcTipoHora'] = $i - 2;
                            $array['numCantidad'] = intval($row[$i]);

                            // Agregar registro
                            $result = self::addRegistro($array);
                            if ($result !== 'si') {
                                throw new Exception("Fila $rowIndex: error al agregar registro - " . $result);
                            }
                        } else {
                            continue;
                        }
                    }
                }
            }

            fclose($handle);
            $this->conexion->commit();

            return 'si';
        } catch (Exception $e) {
            $this->conexion->rollBack();
            fclose($handle);
            return 'Error al procesar el archivo: ' . $e->getMessage();
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
        $inicia = new DateTime($array['datFecInicia']);
        $fin = new DateTime($array['datFecFin']);
        try {
            $sql = "INSERT INTO `nom_horas_ex_trab`
                        (`id_empleado`,`id_he`,`fec_inicio`,`fec_fin`,`hora_inicio`,`hora_fin`,`cantidad_he`,`tipo`,`fec_reg`,`estado`)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 1)";
            $stmt = $this->conexion->prepare($sql);
            $stmt->bindValue(1, $array['id_empleado'], PDO::PARAM_INT);
            $stmt->bindValue(2, $array['slcTipoHora'], PDO::PARAM_INT);
            $stmt->bindValue(3, $inicia->format('Y-m-d'), PDO::PARAM_STR);
            $stmt->bindValue(4, $fin->format('Y-m-d'), PDO::PARAM_STR);
            $stmt->bindValue(5, $inicia->format('H:i:s'), PDO::PARAM_STR);
            $stmt->bindValue(6, $fin->format('H:i:s'), PDO::PARAM_STR);
            $stmt->bindValue(7, $array['numCantidad'], PDO::PARAM_INT);
            $stmt->bindValue(8, $array['slcTipoLiq'], PDO::PARAM_STR);
            $stmt->bindValue(9, Sesion::Hoy(), PDO::PARAM_STR);
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
        $data = self::getIdHoraExtra($array);
        $id = $data['id_he_trab'];
        $estado = $data['estado'];
        if ($estado == 0) {
            return 'no';
        }
        try {
            if ($id > 0) {

                $sql = "UPDATE `nom_horas_ex_trab`
                        SET `cantidad_he` = ?
                    WHERE `id_he_trab` = ?";
                $stmt = $this->conexion->prepare($sql);
                $stmt->bindValue(1, $array['valor'], PDO::PARAM_INT);
                $stmt->bindValue(2, $id, PDO::PARAM_INT);

                if ($stmt->execute() && $stmt->rowCount() > 0) {
                    $consulta = "UPDATE `nom_horas_ex_trab` 
                                SET `fec_actu` = ? 
                            WHERE `id_he_trab` = ?";
                    $stmt2 = $this->conexion->prepare($consulta);
                    $stmt2->bindValue(1, Sesion::Hoy(), PDO::PARAM_STR);
                    $stmt2->bindValue(2, $id, PDO::PARAM_INT);
                    $stmt2->execute();
                    return 'si';
                } else {
                    return 'No se actualizó el registro.';
                }
            } else {
                $datos = base64_decode($array['id']);
                $datos = explode('|', $datos);
                $id_empleado = $datos[0];
                $tipo_hora = $datos[1];
                $mes = $array['mes'];
                $fec_inicio = Sesion::Vigencia() . '-' . $mes . '-01';

                $data = [
                    'id_empleado' => $id_empleado,
                    'datFecInicia' => $fec_inicio . 'T07:00',
                    'datFecFin' => date('Y-m-t', strtotime($fec_inicio)) . 'T23:59',
                    'slcTipoHora' => $tipo_hora,
                    'numCantidad' => $array['valor'],
                    'slcTipoLiq' => $array['tipo'],
                ];
                return self::addRegistro($data);
            }
        } catch (PDOException $e) {
            return 'Error SQL: ' . $e->getMessage();
        }
    }

    public function getIdHoraExtra($array)
    {
        $datos = base64_decode($array['id']);
        $datos = explode('|', $datos);
        $id_empleado = $datos[0];
        $tipo_hora = $datos[1];
        $mes = $array['mes'];
        $fec_inicio = Sesion::Vigencia() . '-' . $mes . '-01';
        $fec_fin = date('Y-m-t', strtotime($fec_inicio));

        $sql = "SELECT `id_he_trab`, `estado`
                    FROM `nom_horas_ex_trab` 
                WHERE `id_empleado` = ? AND `id_he` = ? AND `fec_inicio` BETWEEN ? AND ?";
        $stmt = $this->conexion->prepare($sql);
        $stmt->bindValue(1, $id_empleado, PDO::PARAM_INT);
        $stmt->bindValue(2, $tipo_hora, PDO::PARAM_INT);
        $stmt->bindValue(3, $fec_inicio, PDO::PARAM_STR);
        $stmt->bindValue(4, $fec_fin, PDO::PARAM_STR);
        $stmt->execute();
        $registro = $stmt->fetch(PDO::FETCH_ASSOC);
        return !empty($registro) ? $registro : ['id_he_trab' => 0, 'estado' => 1];
    }

    public static function getTipoHoraExtra($id)
    {
        $sql = "SELECT `id_he`,`desc_he` FROM `nom_tipo_horaex` ORDER BY `desc_he`";
        $combos = new Combos();
        return $combos->setConsulta($sql, $id);
    }

    private function normalizarFecha($fecha)
    {
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha)) {
            return $fecha;
        }

        if (preg_match('/^\d{2}\/\d{2}\/\d{4}$/', $fecha)) {
            $fechaObj = DateTime::createFromFormat('d/m/Y', $fecha);
            if ($fechaObj && $fechaObj->format('d/m/Y') === $fecha) {
                return $fechaObj->format('Y-m-d');
            }
        }

        throw new Exception("Formato de fecha inválido: $fecha");
    }
}
