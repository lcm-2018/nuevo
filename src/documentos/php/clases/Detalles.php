<?php

namespace Src\Documentos\Php\Clases;

use Config\Clases\Conexion;
use Config\Clases\Logs;
use Config\Clases\Sesion;

use PDO;
use PDOException;
use Src\Common\Php\Clases\Combos;
use Src\Common\Php\Clases\Permisos;

/**
 * Clase para gestionar horas extras de los empleados.
 *
 * Esta clase permite realizar operaciones CRUD sobre horas extras de los empleados,
 * incluyendo la obtención de registros, adición, edición y eliminación.
 */
class Detalles
{
    private $conexion;

    public function __construct($conexion = null)
    {
        $this->conexion = $conexion ?: Conexion::getConexion(); // Método estático que retorna el objeto PDO
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
                    `frd`.`id_tercero`
                    , `tt`.`nom_tercero`
                    , `frd`.`id_maestro_doc`
                    , `frd`.`cargo`
                    , `frd`.`tipo_control`
                    , `frd`.`fecha_ini`
                    , `frd`.`fecha_fin`
                FROM
                    `fin_respon_doc` AS `frd`
                    LEFT JOIN `tb_terceros` AS `tt` 
                        ON (`frd`.`id_tercero` = `tt`.`id_tercero_api`)
                WHERE (`frd`.`id_respon_doc` = $id)";
        $stmt = $this->conexion->prepare($sql);
        $stmt->bindParam(1, $id, PDO::PARAM_INT);
        $stmt->execute();
        $registro = $stmt->fetch(PDO::FETCH_ASSOC);
        $stmt->closeCursor();
        unset($stmt);
        if (empty($registro)) {
            $registro = [
                'id_tercero'    => 0,
                'nom_tercero'   => '',
                'id_maestro_doc' => $id,
                'cargo'         => '',
                'tipo_control'  => 0,
                'fecha_ini'     => date('Y-m-d'),
                'fecha_fin'     => date('Y-m-d')
            ];
        }
        return $registro;
    }

    public function getRegistroDT($id)
    {
        $sql = "SELECT
                    `frd`.`id_respon_doc`
                    , `tt`.`nom_tercero`
                    , `frd`.`cargo`
                    , `ftc`.`descripcion`
                    , `frd`.`fecha_ini`
                    , `frd`.`fecha_fin`
                    , `frd`.`estado`
                FROM
                    `fin_respon_doc` AS `frd`
                    INNER JOIN `fin_tipo_control` AS `ftc`
                        ON (`frd`.`tipo_control` = `ftc`.`id_tipo`)
                    LEFT JOIN `tb_terceros` AS `tt` 
                        ON (`frd`.`id_tercero` = `tt`.`id_tercero_api`)
                WHERE (`frd`.`id_maestro_doc` = ?)";
        $stmt = $this->conexion->prepare($sql);
        $stmt->bindParam(1, $id, PDO::PARAM_INT);
        $stmt->execute();
        $registro = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $stmt->closeCursor();
        unset($stmt);
        return $registro;
    }

    /**
     * Obtiene el formulario para agregar o editar un registro.
     *
     * @param int $id ID del registro (0 para nuevo)
     * @return string HTML del formulario
     */

    public function getFormulario($id, $idD = 0)
    {
        $detalle = $this->getRegistro($idD);
        $tipo = Combos::getTipoControl($detalle['tipo_control']);
        $datos = $this->getRegistroDT($id);
        $permisos   = new Permisos();

        $opciones =             $permisos->PermisoOpciones(Sesion::IdUser());
        $tabla = '';
        foreach ($datos as $d) {
            $idDetalle = $d['id_respon_doc'];
            $editar = $borrar = $href = '';
            $estado = $d['estado'] == 1 ? '<span class="badge bg-success">ACTIVO</span>' : '<span class="badge bg-secondary">INACTIVO</span>';

            if ($permisos->PermisosUsuario($opciones, 6001, 3) || Sesion::Rol() == 1) {
                $editar = '<button onclick="EditarDetalle(' . $idDetalle . ')" class="btn btn-outline-primary btn-xs rounded-circle shadow me-1" title="Editar"><span class="fas fa-pencil-alt fa-sm"></span></button>';
                $href = '<a href="javascript:void(0)" class="estado" onclick="EditarEstado(\'' . $idDetalle . '|' . ($d['estado'] == 1 ? 0 : 1) . '\')">' . $estado . '</a>';
            }

            if ($permisos->PermisosUsuario($opciones, 6001, 4) || Sesion::Rol() == 1) {
                $borrar = '<button onclick="EliminarDetalle(' . $idDetalle . ')" class="btn btn-outline-danger btn-xs rounded-circle shadow me-1 borrar" title="Borrar"><span class="fas fa-trash-alt fa-sm"></span></button>';
            }

            if ($d['estado'] == 0) {
                $editar =  $borrar = '';
            }
            $tabla .=
                <<<HTML
                    <tr>
                        <td class="text-center">{$idDetalle}</td>
                        <td class="text-start">{$d['nom_tercero']}</td>
                        <td class="text-start">{$d['cargo']}</td>
                        <td>{$d['descripcion']}</td>
                        <td>{$d['fecha_ini']}</td>
                        <td>{$d['fecha_fin']}</td>
                        <td class="text-center">{$href}</td>
                        <td class="text-center">
                            {$editar}{$borrar}
                        </td>
                    </tr>
                HTML;
        }
        $html =
            <<<HTML
                <div class="shadow text-center rounded">
                    <div class="rounded-top py-2" style="background-color: #16a085 !important;">
                        <h5 style="color: white;" class="mb-0">GESTIÓN DETALLES DOCUMENTO</h5>
                    </div>
                    <div class="p-3">
                        <form id="formDetallesDoc">
                            <input type="hidden" id="id" name="id" value="{$id}">
                            <input type="hidden" id="detalle" name="detalle" value="{$idD}">
                            <div class="row mb-3">
                                <div class="col-md-2">
                                    <label for="slcTipoControl" class="small text-muted">Tipo Control</label>
                                    <select id="slcTipoControl" name="slcTipoControl" class="form-select form-select-sm bg-input">
                                        {$tipo}
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <label for="buscaTercero" class="small text-muted">Responsable</label>
                                    <input id="buscaTercero" name="buscaTercero" class="form-control form-control-sm bg-input" type="text" value="{$detalle['nom_tercero']}" autocomplete="off">
                                    <input type="hidden" id="id_tercero" name="id_tercero" value="{$detalle['id_tercero']}">
                                </div>
                                <div class="col-md-2">
                                    <label for="txtCargo" class="small text-muted">Cargo</label>
                                    <input type="text" id="txtCargo" name="txtCargo" class="form-control form-control-sm bg-input" value="{$detalle['cargo']}">
                                </div>
                                <div class="col-md-2">
                                    <label for="datFechaIni" class="small text-muted">Fecha Inicio</label>
                                    <input type="date" id="datFechaIni" name="datFechaIni" class="form-control form-control-sm bg-input" value="{$detalle['fecha_ini']}">
                                </div>
                                <div class="col-md-2">
                                    <label for="datFechaFin" class="small text-muted">Fecha Fin</label>
                                    <input type="date" id="datFechaFin" name="datFechaFin" class="form-control form-control-sm bg-input" value="{$detalle['fecha_fin']}">
                                </div>
                            </div>
                        </form>
                        <div class="text-center pb-3">
                            <button type="button" class="btn btn-primary btn-sm" id="btnGuardaDetallesDoc">Guardar</button>
                            <a type="button" class="btn btn-secondary  btn-sm" data-bs-dismiss="modal">Cancelar</a>
                        </div>
                        <table id="tableDetallesDoc" class="table table-striped table-bordered table-sm table-hover align-middle shadow" style="width:100%">
                            <thead>
                                <tr class="bg-light">
                                    <th class="text-center bg-sofia">ID</th>
                                    <th class="text-center bg-sofia">NOMBRE</th>
                                    <th class="text-center bg-sofia">CARGO</th>
                                    <th class="text-center bg-sofia">CONTROL</th>
                                    <th class="text-center bg-sofia">FECHA INICIO</th>
                                    <th class="text-center bg-sofia">FECHA FIN</th>
                                    <th class="text-center bg-sofia">ESTADO</th>
                                    <th class="text-center bg-sofia">ACCION</th>
                                </tr>
                            </thead>
                            <tbody>
                            {$tabla}
                            </tbody>
                        </table>
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
            $sql = "DELETE FROM `fin_respon_doc` WHERE `id_respon_doc` = ?";
            $consulta  = "DELETE FROM `fin_respon_doc` WHERE `id_respon_doc` = $id";
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

    /**
     * Agrega un nuevo registro.
     *
     * @param array $array Datos del registro a agregar
     * @return string Mensaje de éxito o error
     */
    public function addRegistro($array)
    {
        $array['id_tercero'] = $array['id_tercero'] > 0 ? $array['id_tercero'] : NULL;
        try {
            $sql = "INSERT INTO `fin_respon_doc`
                        (`id_maestro_doc`,`id_tercero`,`cargo`,`tipo_control`,`fecha_ini`,`fecha_fin`,`id_user_reg`,`fec_reg`)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
            $stmt = $this->conexion->prepare($sql);
            $stmt->bindValue(1, $array['id'], PDO::PARAM_INT);
            $stmt->bindValue(2, $array['id_tercero'], PDO::PARAM_INT);
            $stmt->bindValue(3, $array['txtCargo'], PDO::PARAM_STR);
            $stmt->bindValue(4, $array['slcTipoControl'], PDO::PARAM_INT);
            $stmt->bindValue(5, $array['datFechaIni'], PDO::PARAM_STR);
            $stmt->bindValue(6, $array['datFechaFin'], PDO::PARAM_STR);
            $stmt->bindValue(7, Sesion::IdUser(), PDO::PARAM_INT);
            $stmt->bindValue(8, Sesion::Hoy(), PDO::PARAM_STR);
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

    public function setEstado($id, $estado)
    {
        try {
            $sql = "UPDATE `fin_respon_doc` SET `estado` = ? WHERE `id_respon_doc` = ?";
            $stmt = $this->conexion->prepare($sql);
            $stmt->bindValue(1, $estado, PDO::PARAM_INT);
            $stmt->bindValue(2, $id, PDO::PARAM_INT);
            if ($stmt->execute() && $stmt->rowCount() > 0) {
                $consulta = "UPDATE `fin_respon_doc` 
                                SET `id_user_act` = ?, `fec_act` = ?
                             WHERE `id_respon_doc` = ?";
                $stmt2 = $this->conexion->prepare($consulta);
                $stmt2->bindValue(1, Sesion::IdUser(), PDO::PARAM_INT);
                $stmt2->bindValue(2, Sesion::Hoy(), PDO::PARAM_STR);
                $stmt2->bindValue(3, $id, PDO::PARAM_INT);
                $stmt2->execute();
                return 'si';
            } else {
                return 'No se actualizó el estado.';
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
        $array['id_tercero'] = $array['id_tercero'] > 0 ? $array['id_tercero'] : NULL;
        try {
            $sql = "UPDATE `fin_respon_doc`
                        SET `id_tercero` = ?, `cargo` = ?, `tipo_control` = ?, `fecha_ini` = ?, `fecha_fin` = ?
                        WHERE `id_respon_doc` = ? ";
            $stmt = $this->conexion->prepare($sql);
            $stmt->bindValue(1, $array['id_tercero'], PDO::PARAM_INT);
            $stmt->bindValue(2, $array['txtCargo'], PDO::PARAM_STR);
            $stmt->bindValue(3, $array['slcTipoControl'], PDO::PARAM_INT);
            $stmt->bindValue(4, $array['datFechaIni'], PDO::PARAM_STR);
            $stmt->bindValue(5, $array['datFechaFin'], PDO::PARAM_STR);
            $stmt->bindValue(6, $array['detalle'], PDO::PARAM_INT);

            if ($stmt->execute() && $stmt->rowCount() > 0) {
                $consulta = "UPDATE `fin_respon_doc` 
                                SET `id_user_act` = ?, `fec_act` = ?
                             WHERE `id_respon_doc` = ?";
                $stmt2 = $this->conexion->prepare($consulta);
                $stmt2->bindValue(1, Sesion::IdUser(), PDO::PARAM_INT);
                $stmt2->bindValue(2, Sesion::Hoy(), PDO::PARAM_STR);
                $stmt2->bindValue(3, $array['detalle'], PDO::PARAM_INT);
                $stmt2->execute();
                return 'si';
            } else {
                return 'No se actualizó el registro';
            }
        } catch (PDOException $e) {
            return 'Error SQL: ' . $e->getMessage();
        }
    }
}
