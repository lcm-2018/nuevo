<?php

namespace Src\Nomina\Configuracion\Php\Clases;

use Config\Clases\Conexion;
use Config\Clases\Sesion;
use Config\Clases\Logs;

use Src\Common\Php\Clases\Combos;

use PDO;
use PDOException;

class Cuentas
{
    private $conexion;

    public function __construct()
    {
        $this->conexion = Conexion::getConexion(); // Método estático que retorna el objeto PDO
    }

    /**
     * Obtiene los datos de los terceros de nómina.
     *
     * @param int $start Índice de inicio para la paginación
     * @param int $length Número de registros a mostrar
     * @param string $val_busca Valor de búsqueda
     * @param int $col Índice de la columna para ordenar
     * @param string $dir Dirección de ordenamiento (ascendente o descendente) 
     * @return array|[] Retorna un array con los datos
     */
    public function getCuentas($start, $length, $val_busca, $col, $dir)
    {
        $where = '';
        if ($val_busca != '') {
            $val_busca = trim($val_busca);
            $where = "WHERE (`ctb_pgcp`.`cuenta` LIKE '%$val_busca%' OR `ctb_pgcp`.`nombre` LIKE '%$val_busca%' OR `tb_centrocostos`.`nom_centro` LIKE '%$val_busca%' OR `nom_tipo_rubro`.`nombre` LIKE '%$val_busca%')";
        }

        $limit = "";
        if ($length != -1) {
            $limit = "LIMIT $start, $length";
        }

        $sql = "SELECT
                    `nom_causacion`.`id_causacion`
                    , `ctb_pgcp`.`cuenta`
                    , `ctb_pgcp`.`nombre` AS `nom_cta`
                    , `tb_centrocostos`.`nom_centro` AS `centro_costo`
                    , `nom_causacion`.`id_tipo`
                    , `nom_tipo_rubro`.`nombre`
                FROM
                    `nom_causacion`
                    LEFT JOIN `nom_tipo_rubro` 
                        ON (`nom_causacion`.`id_tipo` = `nom_tipo_rubro`.`id_rubro`)
                    LEFT JOIN `ctb_pgcp` 
                        ON (`nom_causacion`.`cuenta` = `ctb_pgcp`.`id_pgcp`)
                    LEFT JOIN `tb_centrocostos` 
                        ON (`tb_centrocostos`.`id_centro` = `nom_causacion`.`centro_costo`)
                $where
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
    public function getRegistrosFilter($val_busca)
    {
        $where = '';
        if ($val_busca != '') {
            $val_busca = trim($val_busca);
            $where = "WHERE (`ctb_pgcp`.`cuenta` LIKE '%$val_busca%' OR `ctb_pgcp`.`nombre` LIKE '%$val_busca%' OR `tb_centrocostos`.`nom_centro` LIKE '%$val_busca%' OR `nom_tipo_rubro`.`nombre` LIKE '%$val_busca%')";
        }

        $sql = "SELECT
                    COUNT(*) AS `total`
                FROM
                    `nom_causacion`
                    LEFT JOIN `nom_tipo_rubro` 
                        ON (`nom_causacion`.`id_tipo` = `nom_tipo_rubro`.`id_rubro`)
                    LEFT JOIN `ctb_pgcp` 
                        ON (`nom_causacion`.`cuenta` = `ctb_pgcp`.`id_pgcp`)
                    LEFT JOIN `tb_centrocostos` 
                        ON (`tb_centrocostos`.`id_centro` = `nom_causacion`.`centro_costo`)
                $where";
        $stmt = $this->conexion->prepare($sql);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC)['total'] ?: 0;
    }

    /**
     * Obtiene el total de registros.
     * @return int Total de registros
     */

    public function getRegistrosTotal()
    {
        $sql = "SELECT
                    COUNT(*) AS `total`
                FROM
                    `nom_causacion`
                    LEFT JOIN `nom_tipo_rubro` 
                        ON (`nom_causacion`.`id_tipo` = `nom_tipo_rubro`.`id_rubro`)
                    LEFT JOIN `ctb_pgcp` 
                        ON (`nom_causacion`.`cuenta` = `ctb_pgcp`.`id_pgcp`)
                    LEFT JOIN `tb_centrocostos` 
                        ON (`tb_centrocostos`.`id_centro` = `nom_causacion`.`centro_costo`)";
        $stmt = $this->conexion->prepare($sql);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC)['total'] ?: 0;
    }

    public function getFormulario($id)
    {
        $fila = $this->getRegistro($id);
        $concepto = $this->getConcepto(Sesion::Caracter());
        $lst = "";
        foreach ($concepto as $o) {
            $slc = ($fila['id_tipo'] == $o['id_rubro']) ? 'selected' : '';
            $lst .= "<option value='{$o['id_rubro']}' {$slc}>{$o['nombre']}</option>";
        }
        $lst2 = Combos::getCentrosCosto($fila['id_cc']);
        $ver = $id == 0 ? '' : 'disabled';
        $html =
            <<<HTML
                <div>
                    <div class="shadow text-center rounded">
                        <div class="rounded-top py-2" style="background-color: #16a085 !important;">
                            <h5 style="color: white;" class="mb-0">GUARDAR CUENTAS CONTABLES</h5>
                        </div>
                        <div class="p-3">
                            <form id="formGestCtaCtbNom">
                                <input type="hidden" id="id" name="id" value="{$fila['id_causacion']}">
                                <div class="row mb-2">
                                    <div class="col-md-6">
                                        <label for="slcCcosto" class="small">CENTRO COSTO</label>
                                        <select name="slcCcosto" id="slcCcosto" class="form-control form-control-sm" {$ver}>
                                            {$lst2}
                                        </select>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="slcTipo" class="small">TIPO</label>
                                        <select name="slcTipo" id="slcTipo" class="form-control form-control-sm" {$ver}>
                                            <option value="0">--Seleccione--</option>
                                            {$lst}
                                        </select>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-12">
                                        <label for="buscaCuenta" class="small">CUENTA CONTABLE</label>
                                        <input type="text" id="buscaCuenta" class="form-control form-control-sm awesomplete"
                                            data-target="#idCtaCtb" data-tipo-target="#tipoCta"
                                            value="{$fila['nom_cta']}" placeholder="Buscar cuenta...">
                                        <input type="hidden" id="idCtaCtb" name="idCtaCtb" value="{$fila['id_cuenta']}">
                                        <input type="hidden" id="tipoCta" value="{$fila['tp']}">
                                    </div>
                                </div>
                            </form>
                        </div>
                        <div class="text-right pb-3">
                            <button type="button" class="btn btn-primary btn-sm" id="btnGuardaCtaCtbNom">Guardar</button>
                            <a type="button" class="btn btn-secondary  btn-sm" data-bs-dismiss="modal">Cancelar</a>
                        </div>
                    </div>
                </div>
            HTML;
        return $html;
    }

    public function getRegistro($id)
    {
        $sql = "SELECT
                    `nom_causacion`.`id_causacion`
                    , `ctb_pgcp`.`id_pgcp` AS `id_cuenta`
                    , CONCAT_wS(' -> ', `ctb_pgcp`.`cuenta`
                    , `ctb_pgcp`.`nombre`) AS `nom_cta`
                    , `ctb_pgcp`.`tipo_dato` AS `tp`
                    , `nom_causacion`.`centro_costo` AS `id_cc`
                    , `nom_causacion`.`centro_costo`
                    , `nom_causacion`.`id_tipo`
                    , `nom_tipo_rubro`.`nombre`
                FROM
                    `nom_causacion`
                    LEFT JOIN `nom_tipo_rubro` 
                        ON (`nom_causacion`.`id_tipo` = `nom_tipo_rubro`.`id_rubro`)
                    LEFT JOIN `ctb_pgcp` 
                        ON (`nom_causacion`.`cuenta` = `ctb_pgcp`.`id_pgcp`)
                WHERE (`nom_causacion`.`id_causacion` = ?)";
        $stmt = $this->conexion->prepare($sql);
        $stmt->bindParam(1, $id, PDO::PARAM_INT);
        $stmt->execute();
        $data = $stmt->fetch(PDO::FETCH_ASSOC);
        if (empty($data)) {
            $data =
                [
                    'id_causacion' => '0',
                    'id_cuenta' => '0',
                    'nom_cta' => '',
                    'tp' => 'M',
                    'id_cc' => '0',
                    'centro_costo' => '',
                    'id_tipo' => '0',
                    'nombre' => ''
                ];
        }
        return $data;
    }
    private function getValidaInsert($ccosto, $id_tipo)
    {
        try {
            $sql = "SELECT `id_causacion` FROM `nom_causacion` WHERE `centro_costo` = ? AND `id_tipo` = ?";
            $stmt = $this->conexion->prepare($sql);
            $stmt->bindValue(1, $ccosto, PDO::PARAM_INT);
            $stmt->bindValue(2, $id_tipo, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
        } catch (PDOException $e) {
            return 'Error SQL: ' . $e->getMessage();
        }
    }
    public function getConcepto($caracter)
    {
        if ($caracter == 2) {
            $where = 'IN (1,2)';
        } else {
            $where = 'IN (1)';
        }
        $sql = "SELECT
                    `id_rubro`,`nombre`
                FROM `nom_tipo_rubro` WHERE `tipo` $where 
                ORDER BY `nombre` ASC";
        $stmt = $this->conexion->prepare($sql);
        $stmt->bindParam(1, $caracter, PDO::PARAM_STR);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function delCuenta($id)
    {
        try {
            $sql = "DELETE FROM `nom_causacion` WHERE `id_causacion` = ?";
            $consulta  = "DELETE FROM `nom_causacion` WHERE `id_causacion` = $id";
            $stmt = $this->conexion->prepare($sql);
            $stmt->bindParam(1, $id, PDO::PARAM_INT);
            $stmt->execute();
            if ($stmt->rowCount() > 0) {
                Logs::guardaLog($consulta);
                return 'si';
            } else {
                return 'No se eliminó el registro';
            }
        } catch (PDOException $e) {
            return 'Error SQL: ' . $e->getMessage();
        }
    }
    public function addCuenta($array)
    {
        $valida = $this->getValidaInsert($array['slcCcosto'], $array['slcTipo']);
        if (!empty($valida)) {
            return 'La cuenta ya existe.';
        }
        try {
            $sql = "INSERT INTO `nom_causacion`
                        (`centro_costo`,`id_tipo`,`cuenta`,`fec_reg`,`id_user_reg`)
                    VALUES (?, ?, ?, ?, ?)";
            $stmt = $this->conexion->prepare($sql);
            $stmt->bindValue(1, $array['slcCcosto'], PDO::PARAM_INT);
            $stmt->bindValue(2, $array['slcTipo'], PDO::PARAM_INT);
            $stmt->bindValue(3, $array['idCtaCtb'], PDO::PARAM_INT);
            $stmt->bindValue(4, Sesion::Hoy(), PDO::PARAM_STR);
            $stmt->bindValue(5, Sesion::IdUser(), PDO::PARAM_INT);
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

    public function editCuenta($array)
    {
        try {
            $sql = "UPDATE `nom_causacion` 
                        SET `cuenta` = ?
                    WHERE `id_causacion` = ?";
            $stmt = $this->conexion->prepare($sql);
            $stmt->bindValue(1, $array['idCtaCtb'], PDO::PARAM_INT);
            $stmt->bindValue(2, $array['id'], PDO::PARAM_INT);

            if (!($stmt->execute())) {
                return 'Errado: ' . $stmt->errorInfo()[2];
            } else {
                if ($stmt->rowCount() > 0) {
                    $consulta = "UPDATE `nom_causacion` SET `id_user_act` =  ? , `fec_act` = ? WHERE `id_causacion` = ?";
                    $stmt2 = $this->conexion->prepare($consulta);
                    $stmt2->bindValue(1, Sesion::IdUser(), PDO::PARAM_INT);
                    $stmt2->bindValue(2, Sesion::Hoy(), PDO::PARAM_STR);
                    $stmt2->bindValue(3, $array['id'], PDO::PARAM_INT);
                    $stmt2->execute();
                    return 'si';
                } else {
                    return 'No se realizó ningún cambio.';
                }
            }
        } catch (PDOException $e) {
            return 'Error SQL: ' . $e->getMessage();
        }
    }
}
