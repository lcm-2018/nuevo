<?php

namespace Src\Nomina\Configuracion\Php\Clases;

use Config\Clases\Conexion;
use Config\Clases\Logs;
use Config\Clases\Sesion;

use PDO;
use PDOException;

class Parametros
{
    private $conexion;

    public function __construct()
    {
        $this->conexion = Conexion::getConexion(); // Método estático que retorna el objeto PDO
    }

    /**
     * Obtiene los datos de los parametros de liquidación de Nómina.
     *
     * @param string $vigencia Vigencia actual (año)
     * @param int $start Índice de inicio para la paginación
     * @param int $length Número de registros a mostrar
     * @param string $val_busca Valor de búsqueda
     * @param int $col Índice de la columna para ordenar
     * @param string $dir Dirección de ordenamiento (ascendente o descendente) 
     * @return array|[] Retorna un array con los datos de los parametros o vacio si no existe
     */
    public function getParametros($vigencia, $start, $length, $val_busca, $col, $dir)
    {
        $where = '';
        if ($val_busca != '') {
            $val_busca = trim($val_busca);
            $where = "AND (`nom_conceptosxvigencia`.`concepto` LIKE '%$val_busca%' OR `nom_valxvigencia`.`valor` LIKE '%$val_busca%')";
        }

        $limit = "";
        if ($length != -1) {
            $limit = "LIMIT $start, $length";
        }
        $sql = "SELECT
                `nom_valxvigencia`.`id_concepto`
                , `nom_conceptosxvigencia`.`concepto`
                , `nom_valxvigencia`.`valor`
                , `tb_vigencias`.`anio`
                , `nom_valxvigencia`.`id_valxvig`
            FROM
                `nom_valxvigencia`
                INNER JOIN `nom_conceptosxvigencia` 
                    ON (`nom_valxvigencia`.`id_concepto` = `nom_conceptosxvigencia`.`id_concp`)
                INNER JOIN `tb_vigencias` 
                    ON (`nom_valxvigencia`.`id_vigencia` = `tb_vigencias`.`id_vigencia`)
            WHERE (`tb_vigencias`.`anio` = '$vigencia' $where)
            ORDER BY $col $dir $limit";
        $stmt = $this->conexion->prepare($sql);
        $stmt->execute();
        $datos = $stmt->fetchAll(PDO::FETCH_ASSOC);
        return $datos ?: null;
    }

    /**
     * Obtiene el total de registros filtrados de los parametros de liquidación de Nómina.
     * 
     * @param string $vigencia Vigencia actual (año)
     * @param string $val_busca Valor de búsqueda
     * @return int Total de registros filtrados
     */
    public function getRegistrosFilter($vigencia, $val_busca)
    {
        $where = '';
        if ($val_busca != '') {
            $val_busca = trim($val_busca);
            $where = "AND (`nom_conceptosxvigencia`.`concepto` LIKE '%$val_busca%' OR `nom_valxvigencia`.`valor` LIKE '%$val_busca%')";
        }
        $sql = "SELECT COUNT(`nom_valxvigencia`.`id_concepto`) AS `total`
                FROM `nom_valxvigencia`
                INNER JOIN `nom_conceptosxvigencia` 
                    ON (`nom_valxvigencia`.`id_concepto` = `nom_conceptosxvigencia`.`id_concp`)
                INNER JOIN `tb_vigencias` 
                    ON (`nom_valxvigencia`.`id_vigencia` = `tb_vigencias`.`id_vigencia`)
                WHERE (`tb_vigencias`.`anio` = '$vigencia' $where)";
        $stmt = $this->conexion->prepare($sql);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC)['total'] ?: 0;
    }

    /**
     * Obtiene el total de registros de los parametros de liquidación de Nómina.
     * @return int Total de registros
     */

    public function getRegistrosTotal()
    {
        $sql = "SELECT
                    COUNT(*) AS `total`
                FROM
                    `nom_valxvigencia`
                    INNER JOIN `nom_conceptosxvigencia` 
                        ON (`nom_valxvigencia`.`id_concepto` = `nom_conceptosxvigencia`.`id_concp`)
                    INNER JOIN `tb_vigencias` 
                        ON (`nom_valxvigencia`.`id_vigencia` = `tb_vigencias`.`id_vigencia`)";
        $stmt = $this->conexion->prepare($sql);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC)['total'] ?: 0;
    }

    public function getRegistro($id)
    {
        $sql = "SELECT `id_valxvig`,`id_concepto`,`valor` FROM `nom_valxvigencia` WHERE `id_valxvig` = ?";
        $stmt = $this->conexion->prepare($sql);
        $stmt->bindParam(1, $id, PDO::PARAM_INT);
        $stmt->execute();
        $data = $stmt->fetch(PDO::FETCH_ASSOC);
        if (empty($data)) {
            $data =
                [
                    'id_valxvig' => 0,
                    'id_concepto' => 0,
                    'valor' => 0
                ];
        }
        return $data;
    }

    private function getRegistrado($id, $vigencia)
    {
        $sql = "SELECT `id_valxvig` FROM `nom_valxvigencia` WHERE `id_concepto` = ? AND `id_vigencia` = ?";
        $stmt = $this->conexion->prepare($sql);
        $stmt->bindParam(1, $id, PDO::PARAM_INT);
        $stmt->bindParam(2, $vigencia, PDO::PARAM_INT);
        $stmt->execute();
        $data = $stmt->fetch(PDO::FETCH_ASSOC);
        return !empty($data) ? $data['id_valxvig'] : 0;
    }

    public function getConceptos()
    {
        $sql = "SELECT `id_concp`,`concepto` FROM `nom_conceptosxvigencia` WHERE `habilitado` = 1";
        $stmt = $this->conexion->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: null;
    }

    public function getFormulario($id)
    {
        $fila = $this->getRegistro($id);
        $conceptos = $this->getConceptos($id);
        $options = '';
        foreach ($conceptos as $cp) {
            $slc = $fila['id_concepto'] == $cp['id_concp'] ? 'selected' : '';
            $options .= "<option value='{$cp['id_concp']}' $slc>{$cp['concepto']}</option>";
        }
        $html =
            <<<HTML
                <div>
                    <div class="shadow text-center rounded">
                        <div class="rounded-top py-2" style="background-color: #16a085 !important;">
                            <h5 style="color: white;" class="mb-0">CONCEPTO DE LIQUIDACIÓN POR VIGENCIA</h5>
                        </div>
                        <div class="p-3">
                            <form id="formConcepXvig">
                                <input type="hidden" id="id" name="id" value="{$fila['id_valxvig']}">
                                <div class=" form-row">
                                    <div class="form-group col-md-12">
                                        <label for="concepto" class="small">CONCEPTO</label>
                                        <select class="form-control form-control-sm" id="concepto" name="concepto">
                                            <option value="0">--Seleccione--</option>
                                            $options
                                        </select>
                                    </div>
                                </div>
                                <div class=" form-row">
                                    <div class="form-group col-md-12">
                                        <label for="valor" class="small">VALOR</label>
                                        <input type="number" class="form-control form-control-sm text-end" id="valor" name="valor" placeholder="Valor" value="{$fila['valor']}" required>
                                    </div>
                                </div>
                            </form>
                        </div>
                        <div class="text-right pb-3">
                            <button type="button" class="btn btn-primary btn-sm" id="btnGuardaConcxVig">Guardar</button>
                            <a type="button" class="btn btn-secondary  btn-sm" data-bs-dismiss="modal">Cancelar</a>
                        </div>
                    </div>
                </div>
            HTML;
        return $html;
    }

    public function delParametro($id)
    {
        $sql = "DELETE FROM `nom_valxvigencia` WHERE `id_valxvig` = ?";
        $consulta  = "DELETE FROM `nom_valxvigencia` WHERE `id_valxvig` = $id";
        $stmt = $this->conexion->prepare($sql);
        $stmt->bindParam(1, $id, PDO::PARAM_INT);
        Logs::guardaLog($consulta);
        return $stmt->execute() ? 'si' : 'no: ' . $this->conexion->errorInfo()[2];
    }

    public function addParametro($array)
    {
        if ($this->getRegistrado($array['concepto'], $array['id_vigencia']) > 0) {
            return 'El concepto ya está registrado para esta vigencia.';
        }
        try {
            $sql = "INSERT INTO `nom_valxvigencia` (`id_concepto`, `valor`, `id_vigencia`) VALUES (?, ?, ?)";
            $stmt = $this->conexion->prepare($sql);
            $stmt->bindParam(1, $array['concepto'], PDO::PARAM_INT);
            $stmt->bindParam(2, $array['valor'], PDO::PARAM_STR);
            $stmt->bindParam(3, $array['id_vigencia'], PDO::PARAM_INT);
            $stmt->execute();
            $id = $this->conexion->lastInsertId();
            return $id > 0 ? 'si' : 'No se insertó';
        } catch (PDOException $e) {
            return 'Error SQL: ' . $e->getMessage();
        }
    }


    public function editParametro($array)
    {
        $sql = "UPDATE `nom_valxvigencia` SET `valor` = ? WHERE `id_valxvig` = ?";
        $stmt = $this->conexion->prepare($sql);
        $stmt->bindParam(1, $array['valor'], PDO::PARAM_STR);
        $stmt->bindParam(2, $array['id'], PDO::PARAM_INT);
        if (!($stmt->execute())) {
            return 'Errado: ' . $stmt->errorInfo()[2];
        } else {
            return $stmt->rowCount() > 0 ? 'si' : 'No se realizó ningún cambio.';
        }
    }

    public function getValorConcepto($id)
    {
        try {
            $sql = "SELECT `valor` FROM `nom_valxvigencia` WHERE `id_concepto` = ? AND `id_vigencia` = ?";
            $stmt = $this->conexion->prepare($sql);
            $stmt->bindParam(1, $id, PDO::PARAM_INT);
            $stmt->bindValue(2, Sesion::IdVigencia(), PDO::PARAM_INT);
            $stmt->execute();
            $valor = $stmt->fetch(PDO::FETCH_ASSOC);
            return !empty($valor) ? $valor['valor'] : 0;
        } catch (PDOException $e) {
            return 'Error SQL: ' . $e->getMessage();
        }
    }

    public static function Smmlv()
    {
        $instance = new self();
        return $instance->getValorConcepto(1);
    }
    public static function AuxTrans()
    {
        $instance = new self();
        return $instance->getValorConcepto(2);
    }
    public static function AuxAlim()
    {
        $instance = new self();
        return $instance->getValorConcepto(3);
    }
    public static function iNonce()
    {
        $instance = new self();
        return $instance->getValorConcepto(4);
    }
    public static function Consecutivo()
    {
        $instance = new self();
        return $instance->getValorConcepto(5);
    }
    public static function UVT()
    {
        $instance = new self();
        return $instance->getValorConcepto(6);
    }
    public static function BaseServicios()
    {
        $instance = new self();
        return $instance->getValorConcepto(7);
    }
    public static function Representacion()
    {
        $instance = new self();
        return $instance->getValorConcepto(8);
    }
    public static function BaseAlimentacion()
    {
        $instance = new self();
        return $instance->getValorConcepto(9);
    }
    public static function MinimoVital()
    {
        $instance = new self();
        return $instance->getValorConcepto(10);
    }
}
