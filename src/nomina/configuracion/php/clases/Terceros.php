<?php

namespace Src\Nomina\Configuracion\Php\Clases;

use Config\Clases\Conexion;
use Config\Clases\Sesion;
use PDO;
use PDOException;

class Terceros
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
    public function getTerceros($tipo, $start, $length, $val_busca, $col, $dir)
    {
        $where = '';
        if ($val_busca != '') {
            $val_busca = trim($val_busca);
            $where = "AND (`tb_terceros`.`nom_tercero` LIKE '%$val_busca%' OR `tb_terceros`.`nit_tercero` LIKE '%$val_busca%')";
        }

        $limit = "";
        if ($length != -1) {
            $limit = "LIMIT $start, $length";
        }

        $sql = "SELECT
                    `nom_terceros`.`id_tn`
                    , `nom_terceros`.`id_tercero_api`
                    , `tb_terceros`.`nom_tercero`
                    , `tb_terceros`.`nit_tercero`
                    , `tb_terceros`.`dir_tercero`
                    , `tb_terceros`.`tel_tercero`
                FROM
                    `nom_terceros`
                    INNER JOIN `nom_categoria_tercero` 
                        ON (`nom_terceros`.`id_tipo` = `nom_categoria_tercero`.`id_cat`)
                    INNER JOIN `tb_terceros` 
                        ON (`nom_terceros`.`id_tercero_api` = `tb_terceros`.`id_tercero_api`)
                WHERE (`nom_categoria_tercero`.`codigo` = '$tipo' $where)
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
    public function getRegistrosFilter($tipo, $val_busca)
    {
        $where = '';
        if ($val_busca != '') {
            $val_busca = trim($val_busca);
            $where = "AND (`tb_terceros`.`nom_tercero` LIKE '%$val_busca%' OR `tb_terceros`.`nit_tercero` LIKE '%$val_busca%')";
        }

        $sql = "SELECT
                    COUNT(*) AS `total`
                FROM
                    `nom_terceros`
                    INNER JOIN `nom_categoria_tercero` 
                        ON (`nom_terceros`.`id_tipo` = `nom_categoria_tercero`.`id_cat`)
                    INNER JOIN `tb_terceros` 
                        ON (`nom_terceros`.`id_tercero_api` = `tb_terceros`.`id_tercero_api`)
                WHERE (`nom_categoria_tercero`.`codigo` = '$tipo' $where)";
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
                    `nom_terceros`
                    INNER JOIN `nom_categoria_tercero` 
                        ON (`nom_terceros`.`id_tipo` = `nom_categoria_tercero`.`id_cat`)
                    INNER JOIN `tb_terceros` 
                        ON (`nom_terceros`.`id_tercero_api` = `tb_terceros`.`id_tercero_api`)";
        $stmt = $this->conexion->prepare($sql);
        $stmt->execute();
        $data = $stmt->fetch(PDO::FETCH_ASSOC)['total'] ?: 0;
        $stmt->closeCursor();
        unset($stmt);
        return $data;
    }

    public function getFormulario()
    {
        $html =
            <<<HTML
                <div>
                    <div class="shadow text-center rounded">
                        <div class="rounded-top py-2" style="background-color: #16a085 !important;">
                            <h5 style="color: white;" class="mb-0">GUARDAR TERCERO DE NÓMINA</h5>
                        </div>
                        <div class="p-3">
                            <form id="formGestTerceroNom">
                                <input type="hidden" id="id" name="id" value="0">
                                <div class="row mb-2">
                                    <div class="col-md-12">
                                        <label for="buscaTercero" class="small">NOMBRE</label> <br>
                                        <input type="text" id="buscaTercero" name="buscaTercero" class="form-control form-control-sm bg-input awesomplete">
                                        <input type="hidden" id="id_tercero" name="id_tercero" value="0">
                                    </div>
                                </div>
                            </form>
                        </div>
                        <div class="text-end pb-3">
                            <button type="button" class="btn btn-primary btn-sm" id="btnGuardaTercero">Guardar</button>
                            <a type="button" class="btn btn-secondary  btn-sm" data-bs-dismiss="modal">Cancelar</a>
                        </div>
                    </div>
                </div>
            HTML;
        return $html;
    }

    public function addTerceroNomina($array)
    {
        $id_tipo = $this->getIdTipoTercero($array['tipo']);
        if ($id_tipo == 0) {
            return 'Problema al obtener el tipo de tercero';
        }
        try {
            $sql = "INSERT INTO `nom_terceros`
                        (`id_tercero_api`,`id_tipo`,`id_user_reg`,`fec_reg`)
                    VALUES (?, ?, ?, ?)";
            $stmt = $this->conexion->prepare($sql);
            $stmt->bindValue(1, $array['id_tercero'] ?? NULL, PDO::PARAM_INT);
            $stmt->bindValue(2, $id_tipo, PDO::PARAM_INT);
            $stmt->bindValue(3, Sesion::IdUser(), PDO::PARAM_INT);
            $stmt->bindValue(4, Sesion::Hoy(), PDO::PARAM_STR);
            $stmt->execute();
            $id = $this->conexion->lastInsertId();
            return $id > 0 ? 'si' : 'No se insertó';
        } catch (PDOException $e) {
            return 'Error SQL: ' . $e->getMessage();
        }
    }

    private function getIdTipoTercero($cod)
    {
        $sql = "SELECT `id_cat` FROM `nom_categoria_tercero` WHERE `codigo` = '{$cod}'";
        $stmt = $this->conexion->prepare($sql);
        $stmt->execute();
        $data = $stmt->fetch(PDO::FETCH_ASSOC)['id_cat'] ?? 0;
        $stmt->closeCursor();
        unset($stmt);
        return $data;
    }
}
