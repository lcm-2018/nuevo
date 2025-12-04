<?php

namespace Src\Usuarios\General\Php\Clases;

use Config\Clases\Conexion;
use Config\Clases\Logs;
use Config\Clases\Sesion;
use PDO;
use PDOException;
use Src\Common\Php\Clases\Permisos;

class Cierre
{
    private $conexion;

    public function __construct()
    {
        $this->conexion = Conexion::getConexion(); // Método estático que retorna el objeto PDO
    }

    public function getFormularioCierreModulos()
    {
        $html =
            <<<HTML
                <div>
                    <div class="shadow text-center rounded">
                        <div class="rounded-top py-2" style="background-color: #16a085 !important;">
                            <h5 style="color: white;" class="mb-0">ACTUALIZAR PERMISOS DE OPCIONES</h5>
                        </div>
                        <div class="p-3">
                            <table id="tableMesesCierre" class="table table-bordered table-sm table-hover table-striped w-100 shadow">
                                <thead>
                                    <tr>
                                        <th class="bg-sofia">Módulo</th>
                                        <th class="bg-sofia" title="Enero">Ene.</th>
                                        <th class="bg-sofia" title="Febrero">Feb.</th>
                                        <th class="bg-sofia" title="Marzo">Mar.</th>
                                        <th class="bg-sofia" title="Abril">Abr.</th>
                                        <th class="bg-sofia" title="Mayo">May.</th>
                                        <th class="bg-sofia" title="Junio">Jun.</th>
                                        <th class="bg-sofia" title="Julio">Jul.</th>
                                        <th class="bg-sofia" title="Agosto">Ago.</th>
                                        <th class="bg-sofia" title="Septiembre">Sep.</th>
                                        <th class="bg-sofia" title="Octubre">Oct.</th>
                                        <th class="bg-sofia" title="Noviembre">Nov.</th>
                                        <th class="bg-sofia" title="Diciembre">Dic.</th>
                                    </tr>
                                </thead>
                                <tbody></tbody>
                            </table>
                            <div class="text-end py-3">
                                <button type="button" class="btn btn-secondary  btn-sm" data-bs-dismiss="modal">Cancelar</button>
                            </div>
                        </div>
                    </div>
                </div>
            HTML;
        return $html;
    }

    public function getMeseJSON()
    {
        $obj    = (new Permisos())->getMesesCierre();
        $data = [];
        foreach ($obj as $o) {
            // $o comes from Permisos::getMesesCierre which returns associative array
            // keys: id_modulo, nom_modulo, ene, feb, mar, ...

            $row = [
                'id' => $o['id_modulo'],
                'modulo' => $o['nom_modulo'] // Changed from 'opcion' to 'modulo' to match JS columns
            ];

            // Map months to columns
            $months = [
                'ene' => 'enero',
                'feb' => 'febrero',
                'mar' => 'marzo',
                'abr' => 'abril',
                'may' => 'mayo',
                'jun' => 'junio',
                'jul' => 'julio',
                'ago' => 'agosto',
                'sep' => 'septiembre',
                'oct' => 'octubre',
                'nov' => 'noviembre',
                'dic' => 'diciembre'
            ];

            $i = 1;
            foreach ($months as $dbKey => $colName) {
                $isClosed = $o[$dbKey] == 1;
                $val = $isClosed ? 0 : 1;
                $icon = $isClosed
                    ? '<i class="fas fa-toggle-on fa-lg text-success"></i>'
                    : '<i class="fas fa-toggle-off fa-lg text-secondary"></i>';
                $row[$colName] = '<a href="javascript:void(0)" data-id="' . $o['id_modulo'] . '|' . $i . '|' . $val . '" class="estado">' . $icon . '</a>';
                $i++;
            }
            $data[] = $row;
        }
        return $data;
    }

    public function setCierrePeriodo($id_modulo, $mes_idx, $nuevo_estado)
    {
        // $mes_idx is 1 to 12
        $mes = str_pad($mes_idx, 2, '0', STR_PAD_LEFT);
        $vigencia = Sesion::Vigencia();

        try {
            if ($nuevo_estado == 1) {
                // Close period: Insert if not exists
                $sql = "INSERT IGNORE INTO `tb_fin_periodos` (`id_modulo`, `mes`, `vigencia`,`fecha_cierre`,`id_user_reg`, `fec_reg`) VALUES (?, ?, ?, ?, ?, ?)";
                $stmt = $this->conexion->prepare($sql);
                $stmt->bindValue(1, $id_modulo, PDO::PARAM_INT);
                $stmt->bindValue(2, $mes, PDO::PARAM_STR);
                $stmt->bindValue(3, $vigencia, PDO::PARAM_STR);
                $stmt->bindValue(4, Sesion::Hoy(), PDO::PARAM_STR);
                $stmt->bindValue(5, Sesion::IdUser(), PDO::PARAM_INT);
                $stmt->bindValue(6, Sesion::Hoy(), PDO::PARAM_STR);
                $stmt->execute();
            } else {
                // Open period: Delete
                $sql = "DELETE FROM `tb_fin_periodos` WHERE `id_modulo` = ? AND `mes` = ? AND `vigencia` = ?";
                $stmt = $this->conexion->prepare($sql);
                $stmt->bindValue(1, $id_modulo, PDO::PARAM_INT);
                $stmt->bindValue(2, $mes, PDO::PARAM_STR);
                $stmt->bindValue(3, $vigencia, PDO::PARAM_STR);
                if ($stmt->execute() && $stmt->rowCount() > 0) {
                    $consulta = "DELETE FROM `tb_fin_periodos` WHERE `id_modulo` = $id_modulo AND `mes` = $mes AND `vigencia` = $vigencia";
                    Logs::guardaLog($consulta);
                }
            }
            return 'si';
        } catch (PDOException $e) {
            return 'Error SQL: ' . $e->getMessage();
        }
    }
    public function getFormularioFechaSesion()
    {
        $html =
            <<<HTML
                <div class="modal-header" style="background-color: #16a085 !important;">
                    <h5 class="modal-title text-white">FECHA DE SESIÓN</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="formFechaSesion">
                        <div class="mb-3">
                            <label for="fecha" class="form-label small">Seleccione Fecha</label>
                            <input type="date" class="form-control form-control-sm bg-input" id="fecha" name="fecha" required>
                        </div>
                        <div class="text-end">
                            <button type="submit" class="btn btn-primary btn-sm">Guardar</button>
                            <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancelar</button>
                        </div>
                    </form>
                </div>
            HTML;
        return $html;
    }

    public function setFechaSesion($fecha)
    {
        $vigencia = Sesion::Vigencia();
        $id_usuario = Sesion::IdUser();

        try {
            $sql = "INSERT INTO `tb_fin_fecha` (`vigencia`, `id_usuario`, `fecha`) VALUES (?, ?, ?)";
            $stmt = $this->conexion->prepare($sql);
            $stmt->bindValue(1, $vigencia, PDO::PARAM_STR);
            $stmt->bindValue(2, $id_usuario, PDO::PARAM_INT);
            $stmt->bindValue(3, $fecha, PDO::PARAM_STR);
            $stmt->execute();
            return 'si';
        } catch (PDOException $e) {
            return 'Error SQL: ' . $e->getMessage();
        }
    }
    public function getFormularioVigencia()
    {
        try {
            $sql = "SELECT MAX(`anio`) as max_anio FROM `tb_vigencias`";
            $stmt = $this->conexion->prepare($sql);
            $stmt->execute();
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            $next_anio = ($row['max_anio'] ?? date('Y')) + 1;
        } catch (PDOException $e) {
            $next_anio = date('Y') + 1;
        }

        $html =
            <<<HTML
                <div class="modal-header" style="background-color: #16a085 !important;">
                    <h5 class="modal-title text-white">NUEVA VIGENCIA</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="formVigencia">
                        <div class="mb-3">
                            <label for="anio" class="form-label small">Año Vigencia</label>
                            <input type="number" class="form-control form-control-sm bg-input" id="anio" name="anio" value="{$next_anio}" min="{$next_anio}" required readonly>
                        </div>
                        <div class="text-end">
                            <button type="submit" class="btn btn-primary btn-sm">Guardar</button>
                            <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancelar</button>
                        </div>
                    </form>
                </div>
            HTML;
        return $html;
    }

    public function setVigencia($anio)
    {
        try {
            // Validar que sea mayor a la última vigencia
            $sqlCheck = "SELECT MAX(`anio`) as max_anio FROM `tb_vigencias`";
            $stmtCheck = $this->conexion->prepare($sqlCheck);
            $stmtCheck->execute();
            $row = $stmtCheck->fetch(PDO::FETCH_ASSOC);
            $max_anio = $row['max_anio'] ?? 0;

            if ($anio <= $max_anio) {
                return 'La vigencia debe ser mayor a la actual (' . $max_anio . ')';
            }

            $ven_fecha = $anio . '-12-31';
            $estado = 1;
            $id_empresa = 1; // Default value as per assumption
            $registros = 0;

            $sql = "INSERT INTO `tb_vigencias` (`anio`, `registros`, `ven_fecha`, `estado`, `id_empresa`) VALUES (?, ?, ?, ?, ?)";
            $stmt = $this->conexion->prepare($sql);
            $stmt->bindValue(1, $anio, PDO::PARAM_STR);
            $stmt->bindValue(2, $registros, PDO::PARAM_INT);
            $stmt->bindValue(3, $ven_fecha, PDO::PARAM_STR);
            $stmt->bindValue(4, $estado, PDO::PARAM_INT);
            $stmt->bindValue(5, $id_empresa, PDO::PARAM_INT);
            $stmt->execute();
            return 'si';
        } catch (PDOException $e) {
            return 'Error SQL: ' . $e->getMessage();
        }
    }
}
