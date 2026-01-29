<?php

namespace Src\Nomina\Liquidado\Php\Clases;

use Config\Clases\Conexion;
use Config\Clases\Sesion;
use PDO;
use PDOException;
use Src\Common\Php\Clases\Combos;

/**
 * Clase para gestionar de nomina de los empleados Liquidado.
 *
 * Esta clase permite realizar operaciones CRUD,
 * incluyendo la obtención de registros, adición, edición y eliminación.
 */
class Reportes
{
    private $conexion;

    public function __construct($conexion = null)
    {
        $this->conexion = $conexion ?: Conexion::getConexion(); // Método estático que retorna el objeto PDO
    }

    public function addRegistro($a)
    {
        try {
            $sql = "INSERT INTO `nom_cdp_empleados` 
                    (`rubro`, `valor`, `id_nomina`, `tipo`) 
                VALUES (?, ?, ?, ?)";

            $stmt = $this->conexion->prepare($sql);
            $stmt->bindValue(1, $a['rubro'], PDO::PARAM_INT);
            $stmt->bindValue(2, $a['valor'], PDO::PARAM_STR);
            $stmt->bindValue(3, $a['id_nomina'], PDO::PARAM_INT);
            $stmt->bindValue(4, $a['tipo'], PDO::PARAM_STR);

            if (!$stmt->execute()) {
                $error = $stmt->errorInfo();
                return "Error SQL: " . $error[2];
            }

            return 'si';
        } catch (PDOException $e) {
            return 'Error SQL: ' . $e->getMessage();
        }
    }
    public function getRegistros($a)
    {
        try {
            $sql = "SELECT 
                        `padre`.`cod_pptal` AS `raiz`
                        , `padre`.`nom_rubro` AS `nombre`
                        , SUM(`nomina`.`valor`) AS `valor_total`
                    FROM 
                        `pto_cargue` AS `padre`
                        INNER JOIN 
                            `pto_presupuestos` AS `pto` ON (`padre`.`id_pto` = `pto`.`id_pto`)
                        INNER JOIN 
                            `pto_cargue` AS `hijo` ON (`hijo`.`cod_pptal` LIKE CONCAT(`padre`.`cod_pptal`, '%'))
                        INNER JOIN 
                            `nom_cdp_empleados` AS `nomina` ON (`hijo`.`id_cargue` = `nomina`.`rubro`)
                    WHERE 
                        (`pto`.`id_tipo` = 2  AND `pto`.`id_vigencia` = ? AND `nomina`.`id_nomina` = ? AND `nomina`.`tipo` = ?)
                    GROUP BY `padre`.`cod_pptal`, `padre`.`nom_rubro`
                    ORDER BY `padre`.`cod_pptal` ASC";

            $stmt = $this->conexion->prepare($sql);

            $stmt->bindValue(1, Sesion::IdVigencia(), PDO::PARAM_INT);
            $stmt->bindValue(2, $a['id_nomina'], PDO::PARAM_INT);
            $stmt->bindValue(3, $a['tipo'], PDO::PARAM_STR);

            $stmt->execute();
            $resultados = $stmt->fetchAll();

            $data = [];
            foreach ($resultados as $fila) {
                $data[$fila['raiz']] = [
                    'valor' => $fila['valor_total'],
                    'nombre' => $fila['nombre']
                ];
            }
            return $data;
        } catch (PDOException $e) {
            return 'Error SQL: ' . $e->getMessage();
        }
    }

    /**
     * Obtiene el total de registros.
     * @return int Total de registros
     */

    public function delRegsitros($id, $tipo = '')
    {
        $where = $tipo == '' ? '' : "AND `tipo` = '{$tipo}'";

        try {
            $sql = "DELETE FROM `nom_cdp_empleados` WHERE `id_nomina` = ? {$where}";
            $stmt = $this->conexion->prepare($sql);
            $stmt->bindValue(1, $id, PDO::PARAM_INT);
            $stmt->execute();
            $stmt->closeCursor();
            unset($stmt);
            return 'si';
        } catch (PDOException $e) {
            return 'Error SQL: ' . $e->getMessage();
        }
    }

    public function getFormulario($id_nomina)
    {
        $conceptos = Combos::getConceptosNom(0);
        $buscar = '<option value="17" >EMBARGO</option>';
        $reemplazar = '<option value="90" >CONSOLIDADO</option>' . $buscar;
        $conceptos = str_replace($buscar, $reemplazar, $conceptos);
        $html =
            <<<HTML
                <div>
                    <div class="shadow text-center rounded">
                        <div class="rounded-top py-2" style="background-color: #16a085 !important;">
                            <h5 style="color: white;" class="mb-0"><b>REPORTES DE NOMINA LIQUIDADA No. {$id_nomina}</b></h5>
                        </div>
                        <div class="p-3">
                            <input type="hidden" id="id_nomina" name="id_nomina" value="{$id_nomina}">
                            <table class="table table-sm table-striped table-bordered table-hover align-middle">
                                <thead>
                                    <tr>
                                        <th class= "bg-sofia">ID</th>
                                        <th class= "bg-sofia">REPORTE</th>
                                        <th class= "bg-sofia">OPCIONES</th>
                                        <th class= "bg-sofia">ACCIÓN</th>
                                    </tr>
                                </thead>
                                <tbody id="tableReportesNomina">
                                    <tr>
                                        <td>1</td>
                                        <td class="text-start">LIBRANZAS</td>
                                        <td></td>
                                        <td class="text-center">
                                            <button data-id="1" class="btn btn-outline-success btn-xs rounded-circle shadow me-1 reportes" title="Excel" text="E"><span class="fas fa-file-excel fa-sm"></span></button>
                                            <button data-id="1" class="btn btn-outline-info btn-xs rounded-circle shadow me-1 reportes" title="Imprimir" text="P"><span class="fas fa-print fa-sm"></span></button>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>2</td>
                                        <td class="text-start">EMBARGOS</td>
                                        <td></td>
                                        <td class="text-center">
                                            <button data-id="2" class="btn btn-outline-success btn-xs rounded-circle shadow me-1 reportes" title="Excel" text="E"><span class="fas fa-file-excel fa-sm"></span></button>
                                            <button data-id="2" class="btn btn-outline-info btn-xs rounded-circle shadow me-1 reportes" title="Imprimir" text="P"><span class="fas fa-print fa-sm"></span></button>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>3</td>
                                        <td class="text-start">SINDICATOS</td>
                                        <td></td>
                                        <td class="text-center">
                                            <button data-id="3" class="btn btn-outline-success btn-xs rounded-circle shadow me-1 reportes" title="Excel" text="E"><span class="fas fa-file-excel fa-sm"></span></button>
                                            <button data-id="3" class="btn btn-outline-info btn-xs rounded-circle shadow me-1 reportes" title="Imprimir" text="P"><span class="fas fa-print fa-sm"></span></button>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>4</td>
                                        <td class="text-start">CONCEPTOS LIQUIDADOS</td>
                                        <td class="p-0">
                                            <select id="concepto" class="form-select form-select-sm border-0 rounded-0">
                                                {$conceptos}
                                            </select>
                                        </td>
                                        <td class="text-center">
                                            <button data-id="4" class="btn btn-outline-success btn-xs rounded-circle shadow me-1 reportes" title="Excel" text="E"><span class="fas fa-file-excel fa-sm"></span></button>
                                            <button data-id="4" class="btn btn-outline-info btn-xs rounded-circle shadow me-1 reportes" title="Imprimir" text="P"><span class="fas fa-print fa-sm"></span></button>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>   
                        </div>
                        <div class="text-end pb-3 px-3">
                            <a type="button" class="btn btn-secondary  btn-sm" data-bs-dismiss="modal">Cancelar</a>
                        </div>
                    </div>
                </div>
            HTML;
        return $html;
    }
}
