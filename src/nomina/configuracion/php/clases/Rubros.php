<?php

namespace Src\Nomina\Configuracion\Php\Clases;

use Config\Clases\Conexion;
use Config\Clases\Sesion;
use Config\Clases\Logs;

use PDO;
use PDOException;

class Rubros
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
    public function getRubros($start, $length, $val_busca, $col, $dir)
    {
        $id_vigencia = Sesion::IdVigencia();
        $where = '';
        if ($val_busca != '') {
            $val_busca = trim($val_busca);
            $where = "AND (`nom_tipo_rubro`.`nombre` LIKE '%$val_busca%' OR `pto_admin`.`cod_pptal` LIKE '%$val_busca%' OR `pto_admin`.`nom_rubro` LIKE '%$val_busca%' OR `pto_operativo`.`cod_pptal` LIKE '%$val_busca%' OR `pto_operativo`.`nom_rubro` LIKE '%$val_busca%')";
        }

        $limit = "";
        if ($length != -1) {
            $limit = "LIMIT $start, $length";
        }

        $sql = "SELECT
                    `nom_rel_rubro`.`id_relacion`
                    , `nom_rel_rubro`.`id_tipo`
                    , `nom_tipo_rubro`.`nombre`
                    , `nom_rel_rubro`.`r_admin`
                    , `pto_admin`.`cod_pptal` AS `cod_admin`
                    , `pto_admin`.`nom_rubro` AS `nom_admin`
                    , `nom_rel_rubro`.`r_operativo`
                    , `pto_operativo`.`cod_pptal` AS `cod_opera`
                    , `pto_operativo`.`nom_rubro` AS `nom_opera`
                    , `nom_rel_rubro`.`id_vigencia`
                FROM
                    `nom_rel_rubro`
                    INNER JOIN `pto_cargue` AS `pto_operativo` 
                        ON (`nom_rel_rubro`.`r_operativo` = `pto_operativo`.`id_cargue`)
                    INNER JOIN `nom_tipo_rubro` 
                        ON (`nom_rel_rubro`.`id_tipo` = `nom_tipo_rubro`.`id_rubro`)
                    INNER JOIN `pto_cargue` AS `pto_admin`
                        ON (`nom_rel_rubro`.`r_admin` = `pto_admin`.`id_cargue`)
                WHERE (`nom_rel_rubro`.`id_vigencia` = $id_vigencia $where)
                ORDER BY $col $dir $limit";
        $stmt = $this->conexion->prepare($sql);
        $stmt->execute();
        $datos = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        $stmt->closeCursor();
        unset($stmt);
        return $datos;
    }

    public function getRubros2()
    {

        $sql = "SELECT
                `nom_tipo_rubro`.`id_rubro`
                , `nom_rel_rubro`.`id_tipo`
                , `nom_tipo_rubro`.`nombre`
                , `nom_rel_rubro`.`r_admin`
                , `nom_rel_rubro`.`r_operativo`
                , `nom_rel_rubro`.`id_vigencia`
            FROM
                `nom_rel_rubro`
                INNER JOIN `nom_tipo_rubro` 
                    ON (`nom_rel_rubro`.`id_tipo` = `nom_tipo_rubro`.`id_rubro`)
            WHERE (`nom_rel_rubro`.`id_vigencia` = ?)";
        $stmt = $this->conexion->prepare($sql);
        $stmt->bindValue(1, Sesion::IdVigencia(), PDO::PARAM_INT);
        $stmt->execute();
        $datos = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
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
    public function getRegistrosFilter($val_busca)
    {
        $id_vigencia = Sesion::IdVigencia();
        $where = '';
        if ($val_busca != '') {
            $val_busca = trim($val_busca);
            $where = "AND (`nom_tipo_rubro`.`nombre` LIKE '%$val_busca%' OR `pto_admin`.`cod_pptal` LIKE '%$val_busca%' OR `pto_admin`.`nom_rubro` LIKE '%$val_busca%' OR `pto_operativo`.`cod_pptal` LIKE '%$val_busca%' OR `pto_operativo`.`nom_rubro` LIKE '%$val_busca%')";
        }

        $sql = "SELECT
                    COUNT(*) AS `total`
                FROM
                    `nom_rel_rubro`
                    INNER JOIN `pto_cargue` AS `pto_operativo` 
                        ON (`nom_rel_rubro`.`r_operativo` = `pto_operativo`.`id_cargue`)
                    INNER JOIN `nom_tipo_rubro` 
                        ON (`nom_rel_rubro`.`id_tipo` = `nom_tipo_rubro`.`id_rubro`)
                    INNER JOIN `pto_cargue` AS `pto_admin`
                        ON (`nom_rel_rubro`.`r_admin` = `pto_admin`.`id_cargue`)
                WHERE (`nom_rel_rubro`.`id_vigencia` = $id_vigencia $where)";
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

    public function getRegistrosTotal()
    {
        $sql = "SELECT
                    COUNT(*) AS `total`
                FROM
                    `nom_rel_rubro`
                    INNER JOIN `pto_cargue` AS `pto_operativo` 
                        ON (`nom_rel_rubro`.`r_operativo` = `pto_operativo`.`id_cargue`)
                    INNER JOIN `nom_tipo_rubro` 
                        ON (`nom_rel_rubro`.`id_tipo` = `nom_tipo_rubro`.`id_rubro`)
                    INNER JOIN `pto_cargue` AS `pto_admin`
                        ON (`nom_rel_rubro`.`r_admin` = `pto_admin`.`id_cargue`)";
        $stmt = $this->conexion->prepare($sql);
        $stmt->execute();
        $data = $stmt->fetch(PDO::FETCH_ASSOC)['total'] ?: 0;
        $stmt->closeCursor();
        unset($stmt);
        return $data;
    }

    public function getFormulario($id)
    {
        $fila = $this->getRegistro($id);
        $opciones = $this->getTipoRubro(Sesion::Caracter());
        $lst = "";
        foreach ($opciones as $o) {
            $slc = ($fila['id_tipo'] == $o['id_rubro']) ? 'selected' : '';
            $lst .= "<option value='{$o['id_rubro']}' {$slc}>{$o['nombre']}</option>";
        }
        $html =
            <<<HTML
                <div>
                    <div class="shadow text-center rounded">
                        <div class="rounded-top py-2" style="background-color: #16a085 !important;">
                            <h5 style="color: white;" class="mb-0">GUARDAR RUBRO PRESUPUESTAL</h5>
                        </div>
                        <div class="p-3">
                            <form id="formGestRubroNom">
                                <input type="hidden" id="id" name="id" value="{$fila['id_relacion']}">
                                <div class="row mb-2">
                                    <div class="col-md-12">
                                        <label for="slcTipo" class="small">TIPO</label>
                                        <select name="slcTipo" id="slcTipo" class="form-control form-control-sm bg-input">
                                            <option value="0">--Seleccione--</option>
                                            {$lst}
                                        </select>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-6">
                                        <label for="txtRubroAdmin" class="small">RUBRO ADMINISTRATIVO</label>
                                        <input type="text" id="txtRubroAdmin" class="form-control form-control-sm bg-input buscaRubro"
                                            data-target="#idRubroAdmin" data-tipo-target="#tp_dato_radm"
                                            value="{$fila['nom_admin']}">
                                        <input type="hidden" id="idRubroAdmin" name="idRubroAdmin" value="{$fila['r_admin']}">
                                        <input type="hidden" id="tp_dato_radm" value="{$fila['tp_a']}">
                                    </div>
                                    <div class="col-md-6">
                                        <label for="txtRubroOpera" class="small">RUBRO OPERATIVO</label>
                                        <input type="text" id="txtRubroOpera" class="form-control form-control-sm bg-input buscaRubro"
                                            data-target="#idRubroOpera" data-tipo-target="#tp_dato_rope"
                                            value="{$fila['nom_operativo']}">
                                        <input type="hidden" id="idRubroOpera" name="idRubroOpera" value="{$fila['r_operativo']}">
                                        <input type="hidden" id="tp_dato_rope" value="{$fila['tp_o']}">
                                    </div>
                                </div>
                            </form>
                        </div>
                        <div class="text-end pb-3">
                            <button type="button" class="btn btn-primary btn-sm" id="btnGuardaRubroPtoNom">Guardar</button>
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
                `nom_rel_rubro`.`id_relacion`
                , `nom_rel_rubro`.`id_tipo`
                , `nom_rel_rubro`.`r_admin`
                , CONCAT_WS(' - ', `pto_admin`.`cod_pptal`
                , `pto_admin`.`nom_rubro`) AS `nom_admin`
                , `pto_admin`.`tipo_dato` AS `tp_a`
                , `nom_rel_rubro`.`r_operativo`
                , CONCAT_WS(' - ',`pto_operativo`.`cod_pptal`
                , `pto_operativo`.`nom_rubro`) AS `nom_operativo`
                , `pto_operativo`.`tipo_dato` AS `tp_o`
            FROM
                `nom_rel_rubro`
                INNER JOIN `pto_cargue` AS `pto_operativo` 
                    ON (`nom_rel_rubro`.`r_operativo` = `pto_operativo`.`id_cargue`)
                INNER JOIN `pto_cargue` AS `pto_admin`
                    ON (`nom_rel_rubro`.`r_admin` = `pto_admin`.`id_cargue`)
            WHERE (`nom_rel_rubro`.`id_relacion` = ?)";
        $stmt = $this->conexion->prepare($sql);
        $stmt->bindParam(1, $id, PDO::PARAM_INT);
        $stmt->execute();
        $data = $stmt->fetch(PDO::FETCH_ASSOC);
        $stmt->closeCursor();
        unset($stmt);
        if (empty($data)) {
            $data =
                [
                    'id_relacion' => 0,
                    'id_tipo' => 0,
                    'r_admin' => 0,
                    'nom_admin' => '',
                    'tp_a' => 0,
                    'r_operativo' => 0,
                    'nom_operativo' => '',
                    'tp_o' => 0,
                ];
        }
        return $data;
    }
    private function getValidaInsert($id_tipo)
    {
        try {
            $sql = "SELECT `id_relacion` FROM `nom_rel_rubro` WHERE `id_tipo` = ? AND `id_vigencia` = ?";
            $stmt = $this->conexion->prepare($sql);
            $stmt->bindValue(1, $id_tipo, PDO::PARAM_INT);
            $stmt->bindValue(2, Sesion::IdVigencia(), PDO::PARAM_INT);
            $stmt->execute();
            $data = $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
            $stmt->closeCursor();
            unset($stmt);
            return $data;
        } catch (PDOException $e) {
            return 'Error SQL: ' . $e->getMessage();
        }
    }
    public function getTipoRubro($caracter)
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
        $registros = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $stmt->closeCursor();
        unset($stmt);
        return $registros ?: [];
    }

    public function delRubroPto($id)
    {
        try {
            $sql = "DELETE FROM `nom_rel_rubro` WHERE `id_relacion` = ?";
            $consulta  = "DELETE FROM `nom_rel_rubro` WHERE `id_relacion` = $id";
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
    public function addRubroPto($array)
    {
        $valida = $this->getValidaInsert($array['slcTipo']);
        if (!empty($valida)) {
            return 'El tipo de rubro ya existe en la vigencia actual.';
        }
        try {
            $sql = "INSERT `nom_rel_rubro`
                        (`id_tipo`,`r_admin`,`r_operativo`,`id_vigencia`,`fec_reg`,`id_user_reg`)
                    VALUES (?, ?, ?, ?, ?, ?)";
            $stmt = $this->conexion->prepare($sql);
            $stmt->bindValue(1, $array['slcTipo'], PDO::PARAM_INT);
            $stmt->bindValue(2, $array['idRubroAdmin'], PDO::PARAM_INT);
            $stmt->bindValue(3, $array['idRubroOpera'], PDO::PARAM_INT);
            $stmt->bindValue(4, Sesion::IdVigencia(), PDO::PARAM_INT);
            $stmt->bindValue(5, Sesion::Hoy(), PDO::PARAM_STR);
            $stmt->bindValue(6, Sesion::IdUser(), PDO::PARAM_INT);
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

    public function editRubroPto($array)
    {
        try {
            $sql = "UPDATE `nom_rel_rubro` 
                        SET `id_tipo` = ?, `r_admin` = ?, `r_operativo` = ?
                    WHERE `id_relacion` = ?";
            $stmt = $this->conexion->prepare($sql);
            $stmt->bindValue(1, $array['slcTipo'], PDO::PARAM_INT);
            $stmt->bindValue(2, $array['idRubroAdmin'], PDO::PARAM_INT);
            $stmt->bindValue(3, $array['idRubroOpera'], PDO::PARAM_INT);
            $stmt->bindValue(4, $array['id'], PDO::PARAM_INT);

            if (!($stmt->execute())) {
                return 'Errado: ' . $stmt->errorInfo()[2];
            } else {
                if ($stmt->rowCount() > 0) {
                    $consulta = "UPDATE `nom_rel_rubro` SET `id_user_act` =  ? , `fec_act` = ? WHERE `id_relacion` = ?";
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
