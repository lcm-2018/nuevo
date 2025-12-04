<?php

namespace Src\Usuarios\General\Php\Clases;

use Config\Clases\Conexion;
use Config\Clases\Logs;
use Config\Clases\Sesion;
use PDO;
use PDOException;
use Src\Common\Php\Clases\Combos;
use Src\Common\Php\Clases\Permisos;

class Users
{
    private $conexion;

    public function __construct()
    {
        $this->conexion = Conexion::getConexion(); // Método estático que retorna el objeto PDO
    }
    /**
     * Obtiene los datos de los usuarios.
     *
     * @param int $start Índice de inicio para la paginación
     * @param int $length Número de registros a mostrar
     * @param string $val_busca Valor de búsqueda
     * @param int $col Índice de la columna para ordenar
     * @param string $dir Dirección de ordenamiento (ascendente o descendente) 
     * @return array|[] Retorna un array con los datos de los parametros o vacio si no existe
     */
    public function getRegistrosDT($start, $length, $val_busca, $col, $dir)
    {
        $where = '';
        if ($val_busca != '') {
            $val_busca = trim($val_busca);
            $where = "AND (`sus`.`num_documento` LIKE '%$val_busca%' OR `sus`.`login` LIKE '%$val_busca%' OR CONCAT_WS(' ',`sus`.`nombre1`, `sus`.`nombre2` , `sus`.`apellido1` , `sus`.`apellido2`) LIKE '%$val_busca%')";
        }

        $limit = "";
        if ($length != -1) {
            $limit = "LIMIT $start, $length";
        }
        $sql = "SELECT
                    `sus`.`id_usuario`
                    , `sus`.`num_documento`
                    , CONCAT_WS(' ',`sus`.`nombre1`, `sus`.`nombre2` , `sus`.`apellido1` , `sus`.`apellido2`) AS `nombre`
                    , `sus`.`login`
                    , `sr`.`nom_rol`
                    , `sus`.`estado`
                FROM
                    `seg_usuarios_sistema` AS `sus`
                    INNER JOIN `seg_rol` AS `sr` 
                        ON (`sus`.`id_rol` = `sr`.`id_rol`)
                WHERE (1 = 1 $where) 
            ORDER BY $col $dir $limit";
        $stmt = $this->conexion->prepare($sql);
        $stmt->execute();
        $datos = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $stmt->closeCursor();
        unset($stmt);
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
            $where = "AND (`sus`.`num_documento` LIKE '%$val_busca%' OR `sus`.`login` LIKE '%$val_busca%' OR CONCAT_WS(' ',`sus`.`nombre1`, `sus`.`nombre2` , `sus`.`apellido1` , `sus`.`apellido2`) LIKE '%$val_busca%')";
        }
        $sql = "SELECT 
                    COUNT(`sus`.`id_usuario`) AS `total`
                FROM
                    `seg_usuarios_sistema` AS `sus`
                    INNER JOIN `seg_rol` AS `sr` 
                        ON (`sus`.`id_rol` = `sr`.`id_rol`)
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

    public function getRegistrosTotal()
    {
        $sql = "SELECT
                    COUNT(*) AS `total`
                FROM
                    `seg_usuarios_sistema` AS `sus`
                    INNER JOIN `seg_rol` AS `sr` 
                        ON (`sus`.`id_rol` = `sr`.`id_rol`)";
        $stmt = $this->conexion->prepare($sql);
        $stmt->execute();
        $data = $stmt->fetch(PDO::FETCH_ASSOC)['total'] ?: 0;
        $stmt->closeCursor();
        unset($stmt);
        return $data;
    }

    public function getUsers($busca)
    {
        $sql = "SELECT 
                    `id_usuario`, CONCAT_WS(' ',`nombre1`, `nombre2`, `apellido1`, `apellido2`) AS `nombre`, `num_documento`
                FROM `seg_usuarios_sistema`  
                WHERE (`nombre1` LIKE '%{$busca}%' OR `nombre2` LIKE '%{$busca}%' OR `apellido1` LIKE '%{$busca}%' OR `apellido2` LIKE '%{$busca}%' OR `num_documento` LIKE '%{$busca}%') AND `estado` = '1'";
        $stmt = $this->conexion->prepare($sql);
        $stmt->bindParam(':busca', $busca, PDO::PARAM_STR);
        $stmt->execute();

        $datos = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $stmt->closeCursor();
        unset($stmt);

        return $datos ?: [];
    }

    public function getUserId($id)
    {
        if ($id == 0) {
            $id = -1;
        }
        $sql = "SELECT 
                    `id_usuario`, `login` ,`clave` , CONCAT(`nombre1`, ' ', `apellido1`) as `nombre` ,`id_rol` , `estado`,`nombre1`,`nombre2`,`apellido1`,`apellido2`, `id_tipo_doc`,`num_documento`,`email`,`telefono`, `direccion`, `descripcion` AS `cargo`, `id_centrocosto`
                FROM `seg_usuarios_sistema`  
                WHERE `id_usuario` = ?";
        $stmt = $this->conexion->prepare($sql);
        $stmt->bindParam(1, $id, PDO::PARAM_INT);
        $stmt->execute();

        $datos = $stmt->fetch(PDO::FETCH_ASSOC);
        $stmt->closeCursor();
        unset($stmt);
        if (empty($datos)) {
            $datos = [
                'id_usuario'    =>  -1,
                'login'         => '',
                'clave'         => '',
                'nombre'        => '',
                'id_rol'        => 0,
                'estado'        => 1,
                'nombre1'       => '',
                'nombre2'       => '',
                'apellido1'     => '',
                'apellido2'     => '',
                'id_tipo_doc'   => 0,
                'num_documento' => '',
                'email'         => '',
                'telefono'      => '',
                'direccion'     => '',
                'cargo'         => '',
                'id_centrocosto' => 0
            ];
        }
        return $datos;
    }

    public function getFormUsuario($id_user)
    {
        $obj    = $this->getUserId($id_user);
        $tpDocs = Combos::getTiposDocumento($obj['id_tipo_doc']);
        $rol    = Combos::getRolUser($obj['id_rol']);
        $ccosto = Combos::getCentrosCosto($obj['id_centrocosto']);
        $html =
            <<<HTML
                <div class="shadow text-center rounded">
                    <div class="rounded-top py-2" style="background-color: #16a085 !important;">
                        <h5 style="color: white;" class="mb-0">GESTIÓN DE USUARIO</h5>
                    </div>
                    <div class="p-3">
                        <form id="formUserSistema">
                            <div class="row g-3">
                                <div class="col-md-2">
                                    <label for="sl_tipoDocumento" class="form-label small">Tipo documento</label>
                                    <select class="form-select form-select-sm bg-input" id="sl_tipoDocumento" name="sl_tipoDocumento">
                                        {$tpDocs}
                                    </select>
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label small" for="txtCCuser">No. Documento</label>
                                    <input type="number" class="form-control form-control-sm bg-input" id="txtCCuser" name="txtCCuser" placeholder="Identificación" value="{$obj['num_documento']}">
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label small" for="txtlogin">Login</label>
                                    <input type="text" class="form-control form-control-sm bg-input" id="txtlogin" name="txtlogin" placeholder="Usuario" value="{$obj['login']}">
                                </div>
                                <div class="col-md-2 d-flex flex-column justify-content-center">
                                    <label for="radioNo" class="small text-muted text-center mb-2">Sexo</label>
                                    <div class="d-flex justify-content-center gap-2 bg-input border rounded-1 pt-1">
                                        <div class="form-check form-check-inline">
                                            <input class="form-check-input" type="radio" name="slcSexo" id="radioM" value="M">
                                            <label class="form-check-label small text-muted" for="radioM">M</label>
                                        </div>
                                        <div class="form-check form-check-inline me-0">
                                            <input class="form-check-input" type="radio" name="slcSexo" id="radioF" value="F">
                                            <label class="form-check-label small text-muted" for="radioF">F</label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="row g-3 mt-1">
                                <div class="col-md-3">
                                    <label class="form-label small" for="txtNomb1user">Primer nombre</label>
                                    <input type="text" class="form-control form-control-sm bg-input" id="txtNomb1user" name="txtNomb1user" placeholder="Nombre" value="{$obj['nombre1']}">
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label small" for="txtNomb2user">Segundo nombre</label>
                                    <input type="text" class="form-control form-control-sm bg-input" id="txtNomb2user" name="txtNomb2user" placeholder="Nombre" value="{$obj['nombre2']}">
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label small" for="txtApe1user">Primer apellido</label>
                                    <input type="text" class="form-control form-control-sm bg-input" id="txtApe1user" name="txtApe1user" placeholder="Apellido" value="{$obj['apellido1']}">
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label small" for="txtApe2user">Segundo apellido</label>
                                    <input type="text" class="form-control form-control-sm bg-input" id="txtApe2user" name="txtApe2user" placeholder="Apellido" value="{$obj['apellido2']}">
                                </div>
                            </div>
                            <div class="row g-3 mt-1">
                                <div class="col-md-3">
                                    <label class="form-label small" for="txt_direccion">Dirección</label>
                                    <input type="text" class="form-control form-control-sm bg-input" id="txt_direccion" name="txt_direccion" placeholder="Direccion" value="{$obj['direccion']}">
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label small" for="txt_telefono">Teléfono</label>
                                    <input type="text" class="form-control form-control-sm bg-input" id="txt_telefono" name="txt_telefono" placeholder="Teléfono" value="{$obj['telefono']}">
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label small" for="mailuser">Correo eléctronico</label>
                                    <input type="email" class="form-control form-control-sm bg-input" id="mailuser" name="mailuser" placeholder="usuario@correo.com" value="{$obj['email']}">
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label small" for="slcRolUser">Rol</label>
                                    <select class="form-select form-select-sm bg-input" id="slcRolUser" name="slcRolUser">
                                        {$rol}
                                    </select>
                                </div>
                            </div>
                            <input type="number" name="numEstUser" value="1" class="bg-input" hidden>
                            <div class="row g-3 mt-1">
                                <div class="col-md-3">
                                    <label class="form-label small" for="txt_cargo">Cargo</label>
                                    <input type="text" class="form-control form-control-sm bg-input" id="txt_cargo" name="txt_cargo" placeholder="Cargo">
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label small" for="sl_centroCosto">Centro de costo - Dependencia</label>
                                    <select class="form-select form-select-sm bg-input" id="sl_centroCosto" name="sl_centroCosto">
                                        {$ccosto}
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label small" for="sl_areaCentroCosto">Area</label>
                                    <select class="form-select form-select-sm bg-input" id="sl_areaCentroCosto" name="sl_areaCentroCosto"></select>
                                </div>
                            </div>
                            <div class="row mt-3">
                                <label style="width:50%; font-size:80%">Sedes</label>
                                <label style="width:50%; font-size:80%">Bodegas</label>
                            </div>
                            <div class="row">
                                <!--Lista de sedes-->
                                <div class="col-md-6">
                                    <table id="tb_sedes" class="align-middle table table-striped table-bordered table-sm nowrap table-hover shadow" style="width:100%; font-size:80%;">
                                        <thead>
                                            <tr class="text-center">
                                                <th class="bg-sofia">
                                                    <input type="checkbox" id="chk_sel_filtro_sedes" class="bg-input" title="Marcar/Desmarcar todas las sedes">
                                                </th>
                                                <th class="bg-sofia">Id.</th>
                                                <th class="bg-sofia">Sede</th>
                                                <th class="bg-sofia">Dirección</th>
                                                <th class="bg-sofia">Teléfono</th>
                                            </tr>
                                        </thead>
                                    </table>
                                </div>
                                <div class="col-md-6">
                                    <table id="tb_bodegas" class="table table-striped table-bordered table-sm nowrap table-hover shadow align-middle" style="width:100%; font-size:80%">
                                        <thead>
                                            <tr class="text-center">
                                                <th class="bg-sofia">
                                                    <input type="checkbox" id="chk_sel_filtro_bodegas" class="bg-input" title="Marcar/Desmarcar todas las bodegas">
                                                </th>
                                                <th class="bg-sofia">Id.</th>
                                                <th class="bg-sofia">Bodega</th>
                                                <th class="bg-sofia">Tipo</th>
                                                <th class="bg-sofia">Estado</th>
                                            </tr>
                                        </thead>
                                    </table>
                                </div>
                            </div>
                        </form>
                        <div class="text-end pt-2">
                            <button type="button" class="btn btn-primary btn-sm" id="btnGuardaUser">Guardar</button>
                            <button type="button" class="btn btn-secondary  btn-sm" data-bs-dismiss="modal">Cancelar</button>
                        </div>
                    </div>
                </div>
            HTML;
        return $html;
    }

    public function editClave($d)
    {
        try {

            $sql = "UPDATE `seg_usuarios_sistema` SET `clave` = ? WHERE `id_usuario` = ?";
            $stmt = $this->conexion->prepare($sql);
            $stmt->bindValue(1, $d['nuevaClave'], PDO::PARAM_STR);
            $stmt->bindValue(2, $d['id_user'], PDO::PARAM_INT);

            if ($stmt->execute() && $stmt->rowCount() > 0) {
                $consulta  = "UPDATE `seg_usuarios_sistema` SET `fec_cambioclave` = ? WHERE `id_usuario` = ?";
                $stmt2 = $this->conexion->prepare($consulta);
                $stmt2->bindValue(1, Sesion::Hoy(), PDO::PARAM_STR);
                $stmt2->bindValue(2, $d['id_user'], PDO::PARAM_INT);
                $stmt2->execute();
                return 'si';
            } else {
                return 'No se pudo actualizar la clave.';
            }
            return 'si';
        } catch (PDOException $e) {
            return 'Error SQL: ' . $e->getMessage();
        }
    }

    public function getFormCambiaClave($id_user)
    {
        $obj = $this->getUserId($id_user);
        $html =
            <<<HTML
                <div>
                    <div class="shadow text-center rounded">
                        <div class="rounded-top py-2" style="background-color: #16a085 !important;">
                            <h5 style="color: white;" class="mb-0">CAMBIO DE CLAVE</h5>
                        </div>
                        <div class="p-3">
                            <form id='formCambiaClave'>
                                <div class='form-group mb-2'>
                                    <label for='claveActual' class="small">Clave Actual</label>
                                    <input type='password' class='form-control form-control-sm bg-input' id='claveActual' required>
                                    <input type='hidden' id='id_user' name='id_user' value='{$id_user}'>
                                    <input type='hidden' id='passAnt' value='{$obj["clave"]}'>
                                </div>
                                <div class='form-group mb-2'>
                                    <label for='nuevaClave' class="small">Nueva Clave</label>
                                    <input type='password' class='form-control form-control-sm bg-input' id='nuevaClave' name='nuevaClave' required>
                                </div>
                                <div class='form-group mb-2'>
                                    <label for='confirmarClave' class="small">Confirmar Nueva Clave</label>
                                    <input type='password' class='form-control form-control-sm bg-input' id='confirmarClave' required>
                                </div>
                            </form>
                            <div class="text-end py-3">
                                <button type="button" class="btn btn-primary btn-sm" id="btnGuardaCambiaClave">Guardar</button>
                                <button type="button" class="btn btn-secondary  btn-sm" data-bs-dismiss="modal">Cancelar</button>
                            </div>
                        </div>
                    </div>
                </div>
            HTML;
        return $html;
    }

    public function getPermisosModulos($id)
    {
        $user   = $this->getUserId($id);
        $head =
            <<<HTML
                <tr>
                    <th class="bg-sofia">ID</th>
                    <th class="bg-sofia">MÓDULO</th>
                    <th class="bg-sofia">ESTADO</th>
                    <th class="bg-sofia">ACCION</th>
                </tr>
            HTML;
        $html =
            <<<HTML
                <div>
                    <div class="shadow text-center rounded">
                        <div class="rounded-top py-2" style="background-color: #16a085 !important;">
                            <h5 style="color: white;" class="mb-0">PERMISO DE MODULOS: <b>{$user['nombre']}</b></h5>
                        </div>
                        <div class="p-3">
                            <input type="hidden" id="id_user" value="{$id}">
                            <div class="row">
                                <div class="col-md-6">
                                    <table id="tableModulosAsistencial" class="table table-bordered table-sm table-hover table-striped w-100 shadow">
                                        <thead>
                                            <tr>
                                                <th colspan="4" class="bg-sofia">ASISTENCIAL</th>
                                            </tr>
                                            {$head}
                                        </thead>
                                        <tbody></tbody>
                                    </table>
                                </div>
                                <div class="col-md-6">
                                    <table id="tableModulosFinanciero" class="table table-bordered table-sm table-hover table-striped w-100 shadow">
                                        <thead>
                                            <tr>
                                                <th colspan="4" class="bg-sofia">FINANCIERO</th>
                                            </tr>
                                            {$head}
                                        </thead>
                                        <tbody></tbody>
                                    </table>
                                </div>
                            </div>
                            <div class="text-end py-3">
                                <button type="button" class="btn btn-secondary  btn-sm" data-bs-dismiss="modal">Cancelar</button>
                            </div>
                        </div>
                    </div>
                </div>
            HTML;
        return $html;
    }

    public function getPermisosModulosJSON($id)
    {
        $obj    = (new Permisos())->getPermisosModulos($id);
        $adm    = [];
        $fin    = [];

        foreach ($obj as $o) {
            $isAdm = ($o['id_modulo'] > 0 && $o['id_modulo'] < 50);
            $estado = $o['estado'] > 0 ? '<i class="fas fa-toggle-on fa-lg text-success"></i>' : '<i class="fas fa-toggle-off fa-lg text-secondary"></i>';
            $val = $o['estado'] > 0 ? 0 : 1;
            $boton =  $o['estado'] > 0 ? '<button data-id="' . $o['id_modulo'] . '" class="btn btn-outline-info btn-xs rounded-circle shadow me-1 opciones" title="Ver permisos de opciones de módulo"><span class="fas fa-cogs"></span></button>' : '';

            $fila = [
                'id' => $o['id_modulo'],
                'modulo' => $o['nom_modulo'],
                'estado' => '<a href="javascript:void(0)" data-id="' . $o['id_modulo'] . '|' . $val . '" class="estado">' . $estado . '</a>',
                'accion' => $boton
            ];

            if ($isAdm) {
                $adm[] = $fila;
            } else {
                $fin[] = $fila;
            }
        }
        return ['asistencial' => $adm, 'financiero' => $fin];
    }
    public function getPermisosOpciones($a)
    {
        $html =
            <<<HTML
                <div>
                    <div class="shadow text-center rounded">
                        <div class="rounded-top py-2" style="background-color: #16a085 !important;">
                            <h5 style="color: white;" class="mb-0">ACTUALIZAR PERMISOS DE OPCIONES</h5>
                        </div>
                        <div class="p-3">
                            <input type="hidden" id="id_modulo_opciones" value="{$a['id']}">
                            <input type="hidden" id="id_user_opciones" value="{$a['id_user']}">
                            <table id="tableOpciones" class="table table-bordered table-sm table-hover table-striped w-100 shadow">
                                <thead>
                                    <tr>
                                        <th class="bg-sofia">ID</th>
                                        <th class="bg-sofia">Opción</th>
                                        <th class="bg-sofia">Consultar</th>
                                        <th class="bg-sofia">Adicionar</th>
                                        <th class="bg-sofia">Modificar</th>
                                        <th class="bg-sofia">Eliminar</th>
                                        <th class="bg-sofia">Anular</th>
                                        <th class="bg-sofia">Imprimir</th>
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

    public function getPermisosOpcionesJSON($a)
    {
        $obj    = (new Permisos())->getPermisosOpciones($a['id_user'], $a['id']);
        $data = [];
        foreach ($obj as $o) {
            $o = array_values($o);
            $row = [
                'id' => $o[0],
                'opcion' => $o[1]
            ];
            // Columns 2 to 7
            $cols = ['consultar', 'adicionar', 'modificar', 'eliminar', 'anular', 'imprimir'];
            foreach ($cols as $idx => $colName) {
                $dbIdx = $idx + 2;
                $val = $o[$dbIdx] == 1 ? 0 : 1;
                $estado = $o[$dbIdx] == 1 ? '<i class="fas fa-toggle-on fa-lg text-success"></i>' : '<i class="fas fa-toggle-off fa-lg text-secondary"></i>';
                $row[$colName] = '<a href="javascript:void(0)" data-id="' . $o[0] . '|' . $dbIdx . '|' . $val . '" class="estado">' . $estado . '</a>';
            }
            $data[] = $row;
        }
        return $data;
    }

    public function setPermisoOpcion($id_user, $id_opcion, $col_idx, $nuevo_estado)
    {
        $cols = [
            2 => 'per_consultar',
            3 => 'per_adicionar',
            4 => 'per_modificar',
            5 => 'per_eliminar',
            6 => 'per_anular',
            7 => 'per_imprimir'
        ];

        if (!isset($cols[$col_idx])) return 'Índice de columna inválido';
        $col_name = $cols[$col_idx];

        try {
            // Check if exists
            $sql = "SELECT COUNT(*) as `count` FROM `seg_rol_usuario` WHERE `id_usuario` = ? AND `id_opcion` = ?";
            $stmt = $this->conexion->prepare($sql);
            $stmt->execute([$id_user, $id_opcion]);
            $exists = $stmt->fetch(PDO::FETCH_ASSOC)['count'] > 0;

            if ($exists) {
                $sql = "UPDATE `seg_rol_usuario` SET `$col_name` = ? WHERE `id_usuario` = ? AND `id_opcion` = ?";
                $stmt = $this->conexion->prepare($sql);
                $stmt->execute([$nuevo_estado, $id_user, $id_opcion]);
            } else {
                $sql = "INSERT INTO `seg_rol_usuario` (`id_usuario`, `id_opcion`, `$col_name`) VALUES (?, ?, ?)";
                $stmt = $this->conexion->prepare($sql);
                $stmt->execute([$id_user, $id_opcion, $nuevo_estado]);
            }
            return 'si';
        } catch (PDOException $e) {
            return 'Error SQL: ' . $e->getMessage();
        }
    }

    public function addRegistroModulo($a)
    {
        try {
            $sql = "INSERT INTO `seg_permisos_modulos` (`id_usuario`, `id_modulo`) VALUES (?, ?);";
            $stmt = $this->conexion->prepare($sql);
            $stmt->bindValue(1, $a['id_user'], PDO::PARAM_INT);
            $stmt->bindValue(2, $a['id'], PDO::PARAM_INT);
            $stmt->execute();
            if ($this->conexion->lastInsertId() > 0) {
                return 'si';
            } else {
                return 'No se agregó el registro.';
            }
        } catch (PDOException $e) {
            return 'Error SQL: ' . $e->getMessage();
        }
    }

    public function setEstado($id, $estado)
    {
        try {
            $sql = "UPDATE `seg_usuarios_sistema` SET `estado` = ? WHERE `id_usuario` = ?";
            $stmt = $this->conexion->prepare($sql);
            $stmt->bindValue(1, $estado, PDO::PARAM_INT);
            $stmt->bindValue(2, $id, PDO::PARAM_INT);
            if ($stmt->execute() && $stmt->rowCount() > 0) {
                $consulta = "UPDATE `seg_usuarios_sistema` 
                                SET `fec_inactivacion` = ?
                             WHERE `id_usuario` = ?";
                $stmt2 = $this->conexion->prepare($consulta);
                $stmt2->bindValue(1, Sesion::Hoy(), PDO::PARAM_STR);
                $stmt2->bindValue(2, $id, PDO::PARAM_INT);
                $stmt2->execute();
                return 'si';
            } else {
                return 'No se actualizó el estado.';
            }
        } catch (PDOException $e) {
            return 'Error SQL: ' . $e->getMessage();
        }
    }

    public function delRegistro($id)
    {
        try {
            $sql = "DELETE FROM `seg_usuarios_sistema` WHERE `id_usuario` = ?";
            $stmt = $this->conexion->prepare($sql);
            $stmt->bindValue(1, $id, PDO::PARAM_INT);
            if ($stmt->execute() && $stmt->rowCount() > 0) {
                $consulta = "DELETE FROM `seg_usuarios_sistema` WHERE `id_usuario` = $id";
                Logs::guardaLog($consulta);
                return 'si';
            } else {
                return 'No se eliminó el registro.';
            }
        } catch (PDOException $e) {
            return 'Error SQL: ' . $e->getMessage();
        }
    }

    public function delRegistroModulo($a)
    {
        //verificar si no existen transacciones antes de iniciar transaccion
        if (!($this->conexion->inTransaction())) {
            $this->conexion->beginTransaction();
        }
        try {
            $sql = "DELETE FROM `seg_permisos_modulos` WHERE `id_usuario` = ? AND `id_modulo` = ?";
            $stmt = $this->conexion->prepare($sql);
            $stmt->bindValue(1, $a['id_user'], PDO::PARAM_INT);
            $stmt->bindValue(2, $a['id'], PDO::PARAM_INT);
            if ($stmt->execute() && $stmt->rowCount() > 0) {
                $consulta = "DELETE FROM `seg_permisos_modulos` WHERE `id_usuario` = {$a['id_user']} AND `id_modulo` = {$a['id']}";
                $sql = "DELETE FROM `seg_rol_usuario` WHERE `id_usuario` = ? AND `id_opcion` LIKE ?";
                $stmt2 = $this->conexion->prepare($sql);
                $stmt2->bindValue(1, $a['id_user'], PDO::PARAM_INT);
                $like = $a['id'] . '%';
                $stmt2->bindValue(2, $like, PDO::PARAM_STR);
                if ($stmt2->execute()) {
                    $this->conexion->commit();
                    Logs::guardaLog($consulta);
                    $consulta = "DELETE FROM `seg_rol_usuario` WHERE `id_usuario` = {$a['id_user']} AND `id_opcion` LIKE {$a['id']}%";
                    Logs::guardaLog($consulta);
                    return 'si';
                } else {
                    $this->conexion->rollBack();
                    return 'No se eliminó el registro.';
                }
            } else {
                $this->conexion->rollBack();
                return 'No se eliminó el registro.';
            }
        } catch (PDOException $e) {
            $this->conexion->rollBack();
            return 'Error SQL: ' . $e->getMessage();
        }
    }
}
