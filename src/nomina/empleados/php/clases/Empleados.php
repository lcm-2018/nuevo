<?php

namespace Src\Nomina\Empleados\Php\Clases;

use Config\Clases\Conexion;
use Config\Clases\Logs;
use Config\Clases\Sesion;
use Src\Common\Php\Clases\Combos;
use Src\Nomina\Configuracion\Php\Clases\Cargos;
use Src\Terceros\Php\Clases\Terceros;

use PDO;
use PDOException;
use Exception;

class Empleados
{
    private $conexion;

    public function __construct()
    {
        $this->conexion = Conexion::getConexion(); // Método estático que retorna el objeto PDO
    }

    public  static function getTiposEmpleado($id)
    {
        $sql = "SELECT `id_tip_empl`, `descripcion`
                FROM `nom_tipo_empleado`
                ORDER BY
                    CASE WHEN `descripcion` = 'Dependiente' THEN 0 ELSE 1 END,
                    `descripcion` ASC";
        $Combos = new Combos();
        return $Combos->setConsulta($sql, $id);
    }

    public  static function getEmpleadoNull()
    {
        return [
            'id_empleado' => 0,
            'no_documento' => '',
            'nombre1' => '',
            'nombre2' => '',
            'apellido1' => '',
            'apellido2' => '',
            'correo' => '',
            'telefono' => '',
            'direccion' => '',
            'sede_emp' => 0,
            'tipo_empleado' => 0,
            'subtipo_empleado' => 0,
            'tipo_contrato' => 0,
            'tipo_doc' => 0,
            'pais_exp' => 0,
            'dpto_exp' => 0,
            'city_exp' => 0,
            'fec_exp' => date('Y-m-d'),
            'pais_nac' => 0,
            'dpto_nac' => 0,
            'city_nac' => 0,
            'fec_nac' => date('Y-m-d'),
            'pais' => 0,
            'departamento' => 0,
            'municipio' => 0,
            'cargo' => 0,
            'alto_riesgo_pension' => 0,
            'genero' => 'F',
            'salario_integral' => 0,
            'id_banco' => 0,
            'tipo_cta' => 1,
            'cuenta_bancaria' => '',
            'dependientes' => 0,
            'bsp' => 1,
        ];
    }

    public  static function getSubTiposEmpleado($id)
    {
        $sql = "SELECT `id_sub_emp`,`descripcion`
                FROM `nom_subtipo_empl`
                ORDER BY
                    CASE WHEN `descripcion` = 'No Aplica' THEN 0 ELSE 1 END,
                    `descripcion` ASC";
        $Combos = new Combos();
        return $Combos->setConsulta($sql, $id);
    }

    public  static function getTipoContrato($id)
    {
        $sql = "SELECT `id_tip_contrato`,`descripcion` FROM `nom_tipo_contrato`
                ORDER BY `descripcion` ASC";
        $Combos = new Combos();
        return $Combos->setConsulta($sql, $id);
    }

    public  static function getTerceroNomina($cod, $id, $tipo = 0)
    {
        if ($tipo > 0) {
            $where = " AND `nom_categoria_tercero`.`id_cat` = $tipo";
        } else {
            $where = " AND `nom_categoria_tercero`.`codigo` = '$cod'";
        }
        $sql = "SELECT
                    `nom_terceros`.`id_tn`
                    , `tb_terceros`.`nom_tercero`
                FROM
                    `nom_terceros`
                    INNER JOIN `nom_categoria_tercero` 
                        ON (`nom_terceros`.`id_tipo` = `nom_categoria_tercero`.`id_cat`)
                    INNER JOIN `tb_terceros` 
                        ON (`nom_terceros`.`id_tercero_api` = `tb_terceros`.`id_tercero_api`)
                WHERE (`tb_terceros`.`estado` = 1 $where)";
        $Combos = new Combos();
        return $Combos->setConsulta($sql, $id);
    }

    public  static function getRiesgoLaboral($id)
    {
        $sql = "SELECT `id_rlab`, CONCAT(`clase`,' - ',`riesgo`) AS `clase` FROM `nom_riesgos_laboral`
                ORDER BY `clase` ASC";
        $Combos = new Combos();
        return $Combos->setConsulta($sql, $id);
    }

    /**
     * Obtiene los datos de los empleados.
     *
     * @return array|[] Retorna un array con los datos
     */
    public function getEmpleados($id = null)
    {
        $where = '';
        if ($id > 0) {
            $where = "WHERE `id_empleado` = $id";
        }
        try {
            $sql = "SELECT
                        *
                    FROM
                        `nom_empleado`
                    $where";
            $stmt = $this->conexion->prepare($sql);
            $stmt->execute();
            if ($id > 0) {
                $result = $stmt->fetch(PDO::FETCH_ASSOC);
                $stmt->closeCursor();
                unset($stmt);
                return $result;
            } else {
                $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
                $stmt->closeCursor();
                unset($stmt);
                return $result;
            }
        } catch (Exception $e) {
            return 'Error SQL: ' . $e->getMessage();
        }
    }
    /**
     * Obtiene los datos de los empleados para la DataTabl.
     *
     * @param int $start Índice de inicio para la paginación
     * @param int $length Número de registros a mostrar
     * @param string $array conjunto de filtros
     * @param int $col Índice de la columna para ordenar
     * @param string $dir Dirección de ordenamiento (ascendente o descendente) 
     * @return array|[] Retorna un array con los datos
     */
    public function getEmpleadosDT($start, $length, $array, $col, $dir)
    {
        $limit = "";
        if ($length != -1) {
            $limit = "LIMIT $start, $length";
        }

        $where = '';
        if (!empty($array)) {
            if (isset($array['filter_Status'])) {
                if ($array['filter_Status'] == '2') {
                    $where .= ">= 0";
                } else {
                    $where .= "= {$array['filter_Status']}";
                }
            }

            if (isset($array['filter_Nodoc']) && $array['filter_Nodoc'] != '') {
                $where .= " AND `no_documento` LIKE '%{$array['filter_Nodoc']}%'";
            }

            if (isset($array['filter_Nombre']) && $array['filter_Nombre'] != '') {
                $where .= " AND `nombre` LIKE '%{$array['filter_Nombre']}%'";
            }

            if (isset($array['filter_Correo']) && $array['filter_Correo'] != '') {
                $where .= " AND `correo` LIKE '%{$array['filter_Correo']}%'";
            }

            if (isset($array['filter_Tel']) && $array['filter_Tel'] != '') {
                $where .= " AND `telefono` LIKE '%{$array['filter_Tel']}%'";
            }

            if (isset($array['filter_id']) && $array['filter_id'] != '') {
                $where .= " AND `id_empleado` = {$array['filter_id']}";
            }
        }

        $sql = "SELECT * FROM 	
                    (SELECT
                        `id_empleado`
                        , `no_documento`
                        , CONCAT_WS (' ',`nombre2`,`nombre1`,`apellido1`,`apellido2`) AS `nombre`
                        , `correo`,`telefono`,`direccion`,`estado`
                        , `nom_municipio`
                        , `nom_departamento` 
                    FROM `nom_empleado`
                        INNER JOIN `tb_municipios` ON (`nom_empleado`.`municipio` = `tb_municipios`.`id_municipio`)
                        INNER JOIN `tb_departamentos` ON (`nom_empleado`.`departamento` = `tb_departamentos`.`id_departamento`)
                    ) AS `t1`
                WHERE (`t1`.`estado` $where)
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
    public function getRegistrosFilter($array)
    {
        $where = '';
        if (!empty($array)) {
            if ($array['filter_Status'] == '2') {
                $where .= ">= 0";
            } else {
                $where .= "= {$array['filter_Status']}";
            }
            if ($array['filter_Nodoc'] != '') {
                $where .= " AND `no_documento` LIKE '%{$array['filter_Nodoc']}%'";
            }

            if ($array['filter_Nombre'] != '') {
                $where .= " AND `nombre` LIKE '%{$array['filter_Nombre']}%'";
            }

            if ($array['filter_Correo'] != '') {
                $where .= " AND `nombre` LIKE '%{$array['filter_Correo']}%'";
            }

            if ($array['filter_Tel'] != '') {
                $where .= " AND `nombre` LIKE '%{$array['filter_Tel']}%'";
            }
        }

        $sql = "SELECT 
                    COUNT(*) AS `total`
                FROM 	
                    (SELECT
                        `id_empleado`
                        , `no_documento`
                        , CONCAT_WS (' ',`nombre2`,`nombre1`,`apellido1`,`apellido2`) AS `nombre`
                        , `correo`,`telefono`,`direccion`,`estado`
                    FROM `nom_empleado`) AS `t1`
                WHERE (`t1`.`estado` $where)";
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
                    (SELECT
                        `id_empleado`
                        , `no_documento`
                        , CONCAT_WS (' ',`nombre2`,`nombre1`,`apellido1`,`apellido2`) AS `nombre`
                        , `correo`,`telefono`,`direccion`,`estado`
                    FROM `nom_empleado`) AS `t1`";
        $stmt = $this->conexion->prepare($sql);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC)['total'] ?: 0;
    }

    public function getRegistro()
    {
        $sql = "SELECT
                    IF(`nom_terceros`.`id_tipo` = 3, CONCAT_WS('|',`nom_terceros_novedad`.`id_tercero`,`nom_riesgos_laboral`.`cotizacion`),`nom_terceros_novedad`.`id_tercero`) AS `id_tercero`
                    , `nom_terceros_novedad`.`id_empleado`
                    , `nom_terceros`.`id_tipo`
                FROM
                    `nom_terceros_novedad`
                    INNER JOIN `nom_terceros` 
                        ON (`nom_terceros_novedad`.`id_tercero` = `nom_terceros`.`id_tn`)
                    LEFT JOIN `nom_riesgos_laboral`
                    ON (`nom_terceros_novedad`.`id_riesgo` = `nom_riesgos_laboral`.`id_rlab`)
                WHERE `nom_terceros_novedad`.`id_novedad` IN
                    (SELECT
                        MAX(`nom_terceros_novedad`.`id_novedad`) AS `novedad`
                    FROM
                        `nom_terceros_novedad`
                        INNER JOIN `nom_terceros` 
                        ON (`nom_terceros_novedad`.`id_tercero` = `nom_terceros`.`id_tn`)
                    GROUP BY `nom_terceros_novedad`.`id_tercero`, `nom_terceros_novedad`.`id_empleado`, `nom_terceros`.`id_tipo`)";
        $stmt = $this->conexion->prepare($sql);
        $stmt->execute();
        $novedades = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $stmt->closeCursor();
        unset($stmt);
        return !empty($novedades) ? $novedades : [];
    }


    /**
     * Obtiene el formulario para agregar o editar un empleado.
     *
     * @param int $id ID del empleado (0 para nuevo)
     * @return string HTML del formulario
     */
    public function getFormulario($id)
    {
        $formEmpleado = $this->getFormularioEmpleado($id);
        if ($id == 0) {
            $formSalud = $this->getFormularioSalud();
            $formPension = $this->getFormularioPension();
            $formRiesgos = $this->getFormularioRiesgos();
            $formCesantias = $this->getFormularioCesantias();
            $cuerpo =
                <<<HTML
            <div class="p-3">
                <ul class="nav nav-tabs" id="btnFormEmpleado" role="tablist">
                    <li class="nav-item bg-sofia" role="presentation">
                        <button data-id="emp" class="nav-link active" data-bs-toggle="tab" data-bs-target="#formEmpleado" type="button" role="tab" aria-selected="true">EMPLEADO</button>
                    </li>
                    <li class="nav-item bg-sofia" role="presentation">
                        <button data-id="eps" class="nav-link" data-bs-toggle="tab" data-bs-target="#formSalud" type="button" role="tab" aria-selected="false">SALUD</button>
                    </li>
                    <li class="nav-item bg-sofia" role="presentation">
                        <button data-id="afp" class="nav-link" data-bs-toggle="tab" data-bs-target="#formPension" type="button" role="tab" aria-selected="false">PENSION</button>
                    </li>
                    <li class="nav-item bg-sofia" role="presentation">
                        <button data-id="arl" class="nav-link" data-bs-toggle="tab" data-bs-target="#formRiesgos" type="button" role="tab" aria-selected="false">RIESGOS</button>
                    </li>
                    <li class="nav-item bg-sofia" role="presentation">
                        <button data-id="ces" class="nav-link" data-bs-toggle="tab" data-bs-target="#formCesantias" type="button" role="tab" aria-selected="false">CESANTÍAS</button>
                    </li>
                </ul>
                <div class="tab-content pt-2" id="myTabContent">
                    <div class="tab-pane fade show active" id="formEmpleado" role="tabpanel">
                        {$formEmpleado}
                    </div>
                    <div class="tab-pane fade" id="formSalud" role="tabpanel">
                        {$formSalud}
                    </div>
                    <div class="tab-pane fade" id="formPension" role="tabpanel">
                        {$formPension}
                    </div>
                    <div class="tab-pane fade" id="formRiesgos" role="tabpanel">
                        {$formRiesgos}
                    </div>
                    <div class="tab-pane fade" id="formCesantias" role="tabpanel">
                        {$formCesantias}
                    </div>
                </div>
            </div>
            HTML;
        } else {
            $cuerpo = '<div class="p-3">' . $formEmpleado . '</div>';
        }
        $html =
            <<<HTML
                <div class="shadow text-center rounded">
                    <div class="rounded-top py-2" style="background-color: #16a085 !important;">
                        <h5 style="color: white;" class="mb-0">GESTIÓN EMPLEADO DE NÓMINA</h5>
                    </div>
                    <div>
                        {$cuerpo}
                    </div>
                    <div class="text-center pb-3">
                        <button type="button" class="btn btn-primary btn-sm" id="btnGuardaEmpleado">Guardar</button>
                        <a type="button" class="btn btn-secondary  btn-sm" data-bs-dismiss="modal">Cancelar</a>
                    </div>
                </div>
            HTML;
        return $html;
    }
    /**
     * Obtiene el formulario para agregar o editar un empleado.
     *
     * @param int $id ID del empleado (0 para nuevo)
     * @return string HTML del formulario
     */
    public function getFormularioEmpleado($id)
    {
        if ($id > 0) {
            $res =   $this->getEmpleados($id);
        } else {
            $res = self::getEmpleadoNull();
        }
        $op_sedes               =   Combos::getSedes($res['sede_emp'] ?? 0);
        $op_tipo_empleado       =   $this->getTiposEmpleado($res['tipo_empleado'] ?? 0);
        $op_subtipo_empleado    =   $this->getSubTiposEmpleado($res['subtipo_empleado'] ?? 0);
        $op_tipo_contrato       =   $this->getTipoContrato($res['tipo_contrato'] ?? 0);
        $op_tipo_documento      =   Combos::getTiposDocumento($res['tipo_doc'] ?? 0);
        $op_paises_exp          =   Combos::getPaises($res['pais_exp'] ?? 0);
        $op_depto_exp           =   Combos::getDepartamentos($res['dpto_exp'] ?? 0);
        $op_municipio_exp       =   Combos::getMunicipios($res['dpto_exp'] ?? 0, $res['city_exp'] ?? 0);
        $op_paises_nac          =   Combos::getPaises($res['pais_nac'] ?? 0);
        $op_depto_nac           =   Combos::getDepartamentos($res['dpto_nac'] ?? 0);
        $op_municipio_nac       =   Combos::getMunicipios($res['dpto_nac'] ?? 0, $res['city_nac'] ?? 0);
        $op_paises_res          =   Combos::getPaises($res['pais'] ?? 0);
        $op_depto_res           =    Combos::getDepartamentos($res['departamento'] ?? 0);
        $op_municipio_res       =   Combos::getMunicipios($res['departamento'] ?? 0, $res['municipio'] ?? 0);
        $op_bancos              =   Combos::getBancos($res['id_banco'] ?? 0);
        $riesgo_si              =   ($res['alto_riesgo_pension'] == 1) ? 'checked' : '';
        $riesgo_no              =   ($res['alto_riesgo_pension'] == 0) ? 'checked' : '';
        $genero_m               =   ($res['genero'] == 'M') ? 'checked' : '';
        $genero_f               =   ($res['genero'] == 'F') ? 'checked' : '';
        $salario_integral_si    =   ($res['salario_integral'] == 1) ? 'checked' : '';
        $salario_integral_no    =   ($res['salario_integral'] == 0) ? 'checked' : '';
        $tipo_cuenta_ahorro     =   ($res['tipo_cta'] == 1) ? 'checked' : '';
        $tipo_cuenta_corriente  =   ($res['tipo_cta'] == 2) ? 'checked' : '';
        $dependientes           =   ($res['dependientes'] == 1) ? 'checked' : '';
        $bsp                    =   ($res['bsp'] == 1) ? 'checked' : '';
        $row_ccosto             =   '';
        if ($id == 0) {
            $op_ccosto              =   Combos::getCentrosCosto(0);
            $row_ccosto =
                <<<HTML
                    <div class="col-md-2">
                        <label for="slcCCostoEmp" class="small text-muted">Centro Costo</label>
                        <select id="slcCCostoEmp" name="slcCCostoEmp" class="form-select form-select-sm bg-input" aria-label="Default select example">
                            {$op_ccosto}
                        </select>
                    </div>
                HTML;
        }
        $html =
            <<<HTML
                <form id="formGestEmpleado">
                    <input type="hidden" id="id" name="id" value="{$id}">
                    <div class="row pb-2">
                        <div class="col-md-2">
                            <label for="slcSedeEmp" class="small text-muted">Sede</label>
                            <select id="slcSedeEmp" name="slcSedeEmp" class="form-select form-select-sm bg-input" aria-label="Default select example">
                                {$op_sedes}
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label for="slcTipoEmp" class="small text-muted">Tipo de empleado</label>
                            <select id="slcTipoEmp" name="slcTipoEmp" class="form-select form-select-sm bg-input" aria-label="Default select example">
                                {$op_tipo_empleado}
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label for="slcSubTipoEmp" class="small text-muted">Subtipo de empleado</label>
                            <select id="slcSubTipoEmp" name="slcSubTipoEmp" class="form-select form-select-sm bg-input" aria-label="Default select example">
                                {$op_subtipo_empleado}
                            </select>
                        </div>
                        <div class="col-md-2 d-flex flex-column justify-content-center">
                            <label for="radioNo" class="small text-muted text-center">Alto riesgo</label>
                            <div class="d-flex justify-content-center gap-2 bg-input border rounded-1 py-1">
                                <div class="form-check form-check-inline">
                                    <input class="form-check-input" type="radio" name="slcAltoRiesgo" id="radioSi" value="1" {$riesgo_si}>
                                    <label class="form-check-label small text-muted" for="radioSi">Sí</label>
                                </div>
                                <div class="form-check form-check-inline me-0">
                                    <input class="form-check-input" type="radio" name="slcAltoRiesgo" id="radioNo" value="0" {$riesgo_no}>
                                    <label class="form-check-label small text-muted" for="radioNo">No</label>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <label for="slcTipoContratoEmp" class="small text-muted">Tipo de contrato</label>
                            <select id="slcTipoContratoEmp" name="slcTipoContratoEmp" class="form-select form-select-sm bg-input" aria-label="Default select example">
                                {$op_tipo_contrato}
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label for="slcTipoDocEmp" class="small text-muted">Tipo de documento</label>
                            <select id="slcTipoDocEmp" name="slcTipoDocEmp" class="form-select form-select-sm bg-input" aria-label="Default select example">
                                {$op_tipo_documento}
                            </select>
                        </div>
                    </div>
                    <div class="row pb-2">
                        <div class="col-md-2 d-flex flex-column justify-content-center">
                            <label for="slcGeneroF" class="small text-muted">Género</label>
                            <div class="d-flex justify-content-center gap-2 bg-input border rounded-1 py-1" id="slcGenero">
                                <div class="form-check form-check-inline">
                                    <input class="form-check-input" type="radio" name="slcGenero" id="slcGeneroM" value="M" title="Masculino" {$genero_m}>
                                    <label class="form-check-label small text-muted" for="slcGeneroM">M</label>
                                </div>
                                <div class="form-check form-check-inline me-0">
                                    <input class="form-check-input" type="radio" name="slcGenero" id="slcGeneroF" value="F" title="Femenino" {$genero_f}>
                                    <label class="form-check-label small text-muted" for="slcGeneroF">F</label>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <label for="txtCCempleado" class="small text-muted">Número de documento</label>
                            <input type="text" class="form-control form-control-sm bg-input" id="txtCCempleado" name="txtCCempleado" placeholder="Identificación" value="{$res['no_documento']}">
                        </div>
                        <div class="col-md-2">
                            <label for="slcPaisExp" class="small text-muted">País Expide Doc.</label>
                            <select id="slcPaisExp" name="slcPaisExp" class="form-select form-select-sm bg-input" aria-label="Default select example">
                                {$op_paises_exp}
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label for="slcDptoExp" class="small text-muted">Departamento Expide Doc.</label>
                            <select id="slcDptoExp" name="slcDptoExp" class="form-select form-select-sm bg-input" aria-label="Default select example" onchange="CargaCombos('slcMunicipioExp','mun',value)">
                                {$op_depto_exp}
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label for="slcMunicipioExp" class="small text-muted">Municipio Expide Doc.</label>
                            <select id="slcMunicipioExp" name="slcMunicipioExp" class="form-select form-select-sm bg-input" aria-label="Default select example" placeholder="elegir mes">
                                <option value="0">-- Seleccionar --</option>
                                {$op_municipio_exp}
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label for="datFecExp" class="small text-muted">Fecha Expide Doc.</label>
                            <input type="date" class="form-control form-control-sm bg-input" id="datFecExp" name="datFecExp" value="{$res['fec_exp']}">
                        </div>
                    </div>
                    <div class="row pb-2">
                        <div class="col-md-2">
                            <label for="slcPaisNac" class="small text-muted">País Nacimiento</label>
                            <select id="slcPaisNac" name="slcPaisNac" class="form-select form-select-sm bg-input" aria-label="Default select example">
                                {$op_paises_nac}
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label for="slcDptoNac" class="small text-muted">Departamento Nacimiento</label>
                            <select id="slcDptoNac" name="slcDptoNac" class="form-select form-select-sm bg-input" aria-label="Default select example" onchange="CargaCombos('slcMunicipioNac','mun',value)">
                                {$op_depto_nac}
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label for="slcMunicipioNac" class="small text-muted">Municipio Nacimiento</label>
                            <select id="slcMunicipioNac" name="slcMunicipioNac" class="form-select form-select-sm bg-input" aria-label="Default select example" placeholder="elegir mes">
                                <option value="0">-- Seleccionar --</option>    
                            {$op_municipio_nac}
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label for="datFecNac" class="small text-muted">Fecha Nacimiento</label>
                            <input type="date" class="form-control form-control-sm bg-input" id="datFecNac" name="datFecNac" value="{$res['fec_nac']}">
                        </div>
                        <div class="col-md-2">
                            <label for="txtNomb1Emp" class="small text-muted">Primer nombre</label>
                            <input type="text" class="form-control form-control-sm bg-input" id="txtNomb1Emp" name="txtNomb1Emp" placeholder="Nombre" value="{$res['nombre1']}">
                        </div>
                        <div class="col-md-2">
                            <label for="txtNomb2Emp" class="small text-muted">Segundo nombre</label>
                            <input type="text" class="form-control form-control-sm bg-input" id="txtNomb2Emp" name="txtNomb2Emp" placeholder="Nombre" value="{$res['nombre2']}">
                        </div>
                    </div>
                    <div class="row pb-2">
                        <div class="col-md-2">
                            <label for="txtApe1Emp" class="small text-muted">Primer apellido</label>
                            <input type="text" class="form-control form-control-sm bg-input" id="txtApe1Emp" name="txtApe1Emp" placeholder="Apellido" value="{$res['apellido1']}">
                        </div>
                        <div class="col-md-2">
                            <label for="txtApe2Emp" class="small text-muted">Segundo apellido</label>
                            <input type="text" class="form-control form-control-sm bg-input" id="txtApe2Emp" name="txtApe2Emp" placeholder="Apellido" value="{$res['apellido2']}">
                        </div>
                        <div class="col-md-2 d-flex flex-column justify-content-center">
                            <label for="slcSalIntegral0" class="small text-muted">Salario integral</label>
                            <div class="d-flex justify-content-center gap-2 bg-input border rounded-1 py-1" id="slcSalIntegral">
                                <div class="form-check form-check-inline">
                                    <input class="form-check-input" type="radio" name="slcSalIntegral" id="slcSalIntegral1" value="1" {$salario_integral_si}>
                                    <label class="form-check-label small text-muted" for="slcSalIntegral1">SI</label>
                                </div>
                                <div class="form-check form-check-inline me-0">
                                    <input class="form-check-input" type="radio" name="slcSalIntegral" id="slcSalIntegral0" value="0" {$salario_integral_no}>
                                    <label class="form-check-label small text-muted" for="slcSalIntegral0">NO</label>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <label for="slcPaisEmp" class="small text-muted">País Reside</label>
                            <select id="slcPaisEmp" name="slcPaisEmp" class="form-select form-select-sm bg-input" aria-label="Default select example">
                                {$op_paises_res}
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label for="slcDptoEmp" class="small text-muted">Departamento Reside</label>
                            <select id="slcDptoEmp" name="slcDptoEmp" class="form-select form-select-sm bg-input" aria-label="Default select example" onchange="CargaCombos('slcMunicipioEmp','mun',value)">
                                {$op_depto_res}
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label for="slcMunicipioEmp" class="small text-muted">Municipio Reside</label>
                            <select id="slcMunicipioEmp" name="slcMunicipioEmp" class="form-select form-select-sm bg-input" aria-label="Default select example" placeholder="elegir mes">
                                <option value="0">-- Seleccionar --</option>
                                {$op_municipio_res}
                            </select>
                        </div>
                    </div>
                    <div class="row pb-2">
                        <div class="col-md-2">
                            <label for="txtDireccion" class="small text-muted">Dirección Reside</label>
                            <input type="text" class="form-control form-control-sm bg-input" id="txtDireccion" name="txtDireccion" placeholder="Residencial" value="{$res['direccion']}">
                        </div>
                        <div class="col-md-2">
                            <label for="txtTelEmp" class="small text-muted">Contacto</label>
                            <input type="text" class="form-control form-control-sm bg-input" id="txtTelEmp" name="txtTelEmp" placeholder="Teléfono/celular" value="{$res['telefono']}">
                        </div>
                        <div class="col-md-4">
                            <label for="mailEmp" class="small text-muted">Correo</label>
                            <input type="email" class="form-control form-control-sm bg-input" id="mailEmp" name="mailEmp" placeholder="Correo electrónico" value="{$res['correo']}">
                        </div>
                        <div class="col-md-2">
                            <div>
                                <label for="checkDependientes" class="small text-muted">Tiene</label>
                                <div class="d-flex justify-content-center gap-2 bg-input border rounded-1 py-1">
                                    <div class="form-check">
                                        <input type="checkbox" class="form-check-input" id="checkDependientes" name="checkDependientes" {$dependientes}>
                                        <label class="form-check-label" for="checkDependientes">Dependientes</label>
                                    </div>
                                    <div class="form-check">
                                        <input type="checkbox" class="form-check-input" id="checkBsp" name="checkBsp" {$bsp}>
                                        <label class="form-check-label" for="checkBsp">BSP</label>
                                    </div>
                                </div>
                            </div>
                        </div>
                    {$row_ccosto}
                    </div>
                    <div class="row pb-2">
                        <div class="col-md-2">
                            <label for="slcBancoEmp" class="small text-muted">Banco</label>
                            <select id="slcBancoEmp" name="slcBancoEmp" class="form-select form-select-sm bg-input" aria-label="Default select example">
                                {$op_bancos}
                            </select>
                        </div>
                        <div class="col-md-2 d-flex flex-column justify-content-center">
                            <label for="selTipoCta2" class="small text-muted">Tipo de cuenta</label>
                            <div class="d-flex justify-content-center gap-2 bg-input border rounded-1 py-1" id="selTipoCta">
                                <div class="form-check form-check-inline">
                                    <input class="form-check-input" type="radio" name="selTipoCta" id="selTipoCta1" value="1" {$tipo_cuenta_ahorro}>
                                    <label class="form-check-label small text-muted" for="selTipoCta1">Ahorros</label>
                                </div>
                                <div class="form-check form-check-inline me-0">
                                    <input class="form-check-input" type="radio" name="selTipoCta" id="selTipoCta2" value="2" {$tipo_cuenta_corriente}>
                                    <label class="form-check-label small text-muted" for="selTipoCta2">Corriente</label>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <label for="txtCuentaBanc" class="small text-muted">Número de cuenta</label>
                            <input type="text" class="form-control form-control-sm bg-input" id="txtCuentaBanc" name="txtCuentaBanc" placeholder="Sin espacios" value="{$res['cuenta_bancaria']}">
                        </div>
                    </div>
                </form>

            HTML;
        return $html;
    }

    public function getFormularioSalud()
    {
        $op_eps = $this->getTerceroNomina('eps', 0);
        $hoy = Sesion::_Hoy();
        $fin = date('Y') . '-12-31';
        $html =
            <<<HTML
                <form id="formGestSaludEmpleado">
                    <div class="row pb-2">
                        <div class="col-md-4">
                            <label for="slcEps" class="small text-muted">EPS</label>
                            <select id="slcEps" name="slcEps" class="form-select form-select-sm bg-input" aria-label="Default select example">
                                {$op_eps}
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label for="datFecAfilEps" class="small text-muted">Afilición</label>
                            <div class="form-group">
                                <input type="date" class="form-control form-control-sm bg-input" id="datFecAfilEps" name="datFecAfilEps" value="{$hoy}">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <label for="datFecRetEps" class="small text-muted">Retiro</label>
                            <div class="form-group">
                                <input type="date" class="form-control form-control-sm bg-input" id="datFecRetEps" name="datFecRetEps" value="{$fin}">
                            </div>
                        </div>
                    </div>
                </form>
            HTML;
        return $html;
    }

    public function getFormularioPension()
    {
        $op_afp = $this->getTerceroNomina('afp', 0);
        $hoy = Sesion::_Hoy();
        $fin = date('Y') . '-12-31';
        $html =
            <<<HTML
                <form id="formGestPensionEmpleado">
                    <div class="row pb-2">
                        <div class="col-md-4">
                            <label for="slcAfp" class="small text-muted">Fondo Pensión</label>
                            <select id="slcAfp" name="slcAfp" class="form-select form-select-sm bg-input" aria-label="Default select example">
                                {$op_afp} 
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label for="datFecAfilAfp" class="small text-muted">Afilición</label>
                            <div class="form-group">
                                <input type="date" class="form-control form-control-sm bg-input" id="datFecAfilAfp" name="datFecAfilAfp" value="{$hoy}">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <label for="datFecRetAfp" class="small text-muted">Retiro</label>
                            <div class="form-group">
                                <input type="date" class="form-control form-control-sm bg-input" id="datFecRetAfp" name="datFecRetAfp" value="{$fin}">
                            </div>
                        </div>
                    </div>
                </form>
            HTML;
        return $html;
    }

    public function getFormularioRiesgos()
    {
        $op_arl = $this->getTerceroNomina('arl', 0);
        $op_nivel = $this->getRiesgoLaboral(0);
        $hoy = Sesion::_Hoy();
        $fin = date('Y') . '-12-31';
        $html =
            <<<HTML
                <form id="formGestRiesgoEmpleado">
                    <div class="row pb-2">
                        <div class="col-md-4">
                            <label for="slcArl" class="small text-muted">ARL</label>
                            <select id="slcArl" id="slcArl" name="slcArl" class="form-select form-select-sm bg-input" aria-label="Default select example">
                                {$op_arl}
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label for="datFecAfilArl" class="small text-muted">Afilición</label>
                            <div class="form-group">
                                <input type="date" class="form-control form-control-sm bg-input" id="datFecAfilArl" name="datFecAfilArl" value="{$hoy}">
                            </div>
                        </div>
                        <div class="col-md-2">
                            <label for="datFecRetArl" class="small text-muted">Retiro</label>
                            <div class="form-group">
                                <input type="date" class="form-control form-control-sm bg-input" id="datFecRetArl" name="datFecRetArl" value="{$fin}">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <label for="slcRiesLab" class="small text-muted">Riesgo laboral</label>
                            <select id="slcRiesLab" name="slcRiesLab" class="form-select form-select-sm bg-input" aria-label="Default select example">
                                {$op_nivel}
                            </select>
                        </div>
                    </div>
                </form>
            HTML;
        return $html;
    }

    public function getFormularioCesantias()
    {
        $op_fc = $this->getTerceroNomina('ces', 0);
        $hoy = Sesion::_Hoy();
        $fin = date('Y') . '-12-31';
        $html =
            <<<HTML
                <form id="formGestCesantiaEmpleado">
                    <div class="row pb-2">
                        <div class="col-md-4">
                            <label for="slcFc" class="small text-muted">Fondo cesantias</label>
                            <select id="slcFc" name="slcFc" class="form-select form-select-sm bg-input" aria-label="Default select example">
                                {$op_fc}
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label for="datFecAfilFc" class="small text-muted">Afilición</label>
                            <input type="date" class="form-control form-control-sm bg-input" id="datFecAfilFc" name="datFecAfilFc" value="{$hoy}">
                        </div>
                        <div class="col-md-4">
                            <label for="datFecRetFc" class="small text-muted">Retiro</label>
                            <input type="date" class="form-control form-control-sm bg-input" id="datFecRetFc" name="datFecRetFc" value="{$fin}">
                        </div>
                    </div> 
                </form>
            HTML;
        return $html;
    }

    /**
     * Elimina un empleado.
     *
     * @param int $id ID del empleado a eliminar
     * @return string Mensaje de éxito o error
     */

    public function delEmpleado($id)
    {
        return 'Falta programar la eliminación de empleados.';
        try {
            $sql = "DELETE FROM `nom_cargo_empleado` WHERE `id_cargo` = ?";
            $consulta  = "DELETE FROM `nom_cargo_empleado` WHERE `id_cargo` = $id";
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
     * Agrega un nuevo empleado.
     *
     * @param array $array Datos del empleado a agregar
     * @return string Mensaje de éxito o error
     */
    public function addEmpleadoFull($array)
    {
        try {
            $this->conexion->beginTransaction();
            $this->addEmpleado($array);
            $id = $this->conexion->lastInsertId();
            $data = ['id_empleado' => $id, 'slcCCostoEmp' => $array['slcCCostoEmp']];
            $this->addCentroCosto($data);
            $data = [1 => $id, 2 => $array['slcEps'], 3 => $array['datFecAfilEps'], 4 => $array['datFecRetEps'], 5 => NULL];
            $this->addNovedad($data);
            $data = [1 => $id, 2 => $array['slcAfp'], 3 => $array['datFecAfilAfp'], 4 => $array['datFecRetAfp'], 5 => NULL];
            $this->addNovedad($data);
            $data = [1 => $id, 2 => $array['slcArl'], 3 => $array['datFecAfilArl'], 4 => $array['datFecRetArl'], 5 => $array['slcRiesLab']];
            $this->addNovedad($data);
            $data = [1 => $id, 2 => $array['slcFc'], 3 => $array['datFecAfilFc'], 4 => $array['datFecRetFc'], 5 => NULL];
            $this->addNovedad($data);

            $Terceros = new Terceros($this->conexion);
            $Tercero = $Terceros->getRegistroApiCedula($array['txtCCempleado']);
            if (!empty($Tercero)) {
                $id_tercero_api = $Tercero['id_tercero'];
            } else {
                $id_tercero_api = $Terceros->addTerceroApi($array);
            }
            $array['id_tercero_api'] = $id_tercero_api;
            $Terceros->addTercero($array);
            $Terceros->addTipoRelacion($array);
            $this->conexion->commit();
            return 'si';
        } catch (PDOException | Exception $e) {
            if ($this->conexion->inTransaction()) {
                $this->conexion->rollBack();
            }
            return 'Error SQL: ' . $e->getMessage();
        }
    }
    /**
     * Actualiza los datos de un empleado.
     *
     * @param array $array Datos del empleado a actualizar
     * @return string Mensaje de éxito o error
     */

    public function addEmpleado($array)
    {
        $sql = "INSERT INTO `nom_empleado`
                        (`sede_emp`,`tipo_empleado`,`subtipo_empleado`,`alto_riesgo_pension`,`tipo_contrato`,`tipo_doc`,
                        `no_documento`,`pais_exp`,`dpto_exp`,`city_exp`,`fec_exp`,`pais_nac`,`dpto_nac`,`city_nac`,`fec_nac`,`genero`,
                        `apellido1`,`apellido2`,`nombre1`,`nombre2`,`salario_integral`,`correo`,`telefono`,
                        `pais`,`departamento`,`municipio`,`direccion`,`id_banco`,`tipo_cta`,`cuenta_bancaria`,
                        `estado`,`dependientes`,`fec_reg`,`bsp`)
                    VALUES (? , ?, ?, ?, ? , ?, ?, ?, ? , ?, ?, ?, ? , ?, ?, ? , ?, ? , ?, ? , ?, ? , ?, ? , ?, ? , ?, ? , ?, ? , ?, ?, ?, ?)";
        $stmt = $this->conexion->prepare($sql);
        $stmt->bindValue(1, $array['slcSedeEmp'], PDO::PARAM_INT);
        $stmt->bindValue(2, $array['slcTipoEmp'], PDO::PARAM_INT);
        $stmt->bindValue(3, $array['slcSubTipoEmp'], PDO::PARAM_INT);
        $stmt->bindValue(4, $array['slcAltoRiesgo'], PDO::PARAM_INT);
        $stmt->bindValue(5, $array['slcTipoContratoEmp'], PDO::PARAM_INT);
        $stmt->bindValue(6, $array['slcTipoDocEmp'], PDO::PARAM_INT);
        $stmt->bindValue(7, $array['txtCCempleado'], PDO::PARAM_STR);
        $stmt->bindValue(8, $array['slcPaisExp'], PDO::PARAM_INT);
        $stmt->bindValue(9, $array['slcDptoExp'], PDO::PARAM_INT);
        $stmt->bindValue(10, $array['slcMunicipioExp'], PDO::PARAM_INT);
        $stmt->bindValue(11, $array['datFecExp'], PDO::PARAM_STR);
        $stmt->bindValue(12, $array['slcPaisNac'], PDO::PARAM_INT);
        $stmt->bindValue(13, $array['slcDptoNac'], PDO::PARAM_INT);
        $stmt->bindValue(14, $array['slcMunicipioNac'], PDO::PARAM_INT);
        $stmt->bindValue(15, $array['datFecNac'], PDO::PARAM_STR);
        $stmt->bindValue(16, $array['slcGenero'], PDO::PARAM_STR);
        $stmt->bindValue(17, $array['txtApe1Emp'], PDO::PARAM_STR);
        $stmt->bindValue(18, $array['txtApe2Emp'], PDO::PARAM_STR);
        $stmt->bindValue(19, $array['txtNomb1Emp'], PDO::PARAM_STR);
        $stmt->bindValue(20, $array['txtNomb2Emp'], PDO::PARAM_STR);
        $stmt->bindValue(21, $array['slcSalIntegral'], PDO::PARAM_INT);
        $stmt->bindValue(22, $array['mailEmp'], PDO::PARAM_STR);
        $stmt->bindValue(23, $array['txtTelEmp'], PDO::PARAM_STR);
        $stmt->bindValue(24, $array['slcPaisEmp'], PDO::PARAM_INT);
        $stmt->bindValue(25, $array['slcDptoEmp'], PDO::PARAM_INT);
        $stmt->bindValue(26, $array['slcMunicipioEmp'], PDO::PARAM_INT);
        $stmt->bindValue(27, $array['txtDireccion'], PDO::PARAM_STR);
        $stmt->bindValue(28, $array['slcBancoEmp'], PDO::PARAM_INT);
        $stmt->bindValue(29, $array['selTipoCta'], PDO::PARAM_INT);
        $stmt->bindValue(30, $array['txtCuentaBanc'], PDO::PARAM_STR);
        $stmt->bindValue(31, 1, PDO::PARAM_INT);
        $stmt->bindValue(32, isset($array['checkDependientes']) ? 1 : 0, PDO::PARAM_INT);
        $stmt->bindValue(33, Sesion::Hoy(), PDO::PARAM_STR);
        $stmt->bindValue(34, isset($array['checkBsp']) ? 1 : 0, PDO::PARAM_INT);
        $stmt->execute();
    }

    public function editEmpleado($array)
    {
        try {
            $sql = "UPDATE `nom_empleado`
                    SET `sede_emp` = ?, `tipo_empleado` = ?, `subtipo_empleado` = ?, `alto_riesgo_pension` = ?, `tipo_contrato` = ?
                        , `tipo_doc` = ?,`no_documento` = ?, `pais_exp` = ?, `dpto_exp` = ?, `city_exp` = ?, `fec_exp` = ?
                        , `pais_nac` = ?, `dpto_nac` = ?, `city_nac` = ?, `fec_nac` = ?, `genero` = ?, `apellido1` = ?
                        , `apellido2` = ?, `nombre1` = ?, `nombre2` = ?, `salario_integral` = ?, `correo` = ?, `telefono` = ?
                        , `pais` = ?, `departamento` = ?, `municipio` = ?, `direccion` = ?
                        , `id_banco` = ?, `tipo_cta` = ?, `cuenta_bancaria` = ?, `dependientes` = ? , `bsp`= ?
                    WHERE (`id_empleado` = ?)";
            $stmt = $this->conexion->prepare($sql);
            $stmt->bindValue(1, $array['slcSedeEmp'], PDO::PARAM_INT);
            $stmt->bindValue(2, $array['slcTipoEmp'], PDO::PARAM_INT);
            $stmt->bindValue(3, $array['slcSubTipoEmp'], PDO::PARAM_INT);
            $stmt->bindValue(4, $array['slcAltoRiesgo'], PDO::PARAM_INT);
            $stmt->bindValue(5, $array['slcTipoContratoEmp'], PDO::PARAM_INT);
            $stmt->bindValue(6, $array['slcTipoDocEmp'], PDO::PARAM_INT);
            $stmt->bindValue(7, $array['txtCCempleado'], PDO::PARAM_STR);
            $stmt->bindValue(8, $array['slcPaisExp'], PDO::PARAM_INT);
            $stmt->bindValue(9, $array['slcDptoExp'], PDO::PARAM_INT);
            $stmt->bindValue(10, $array['slcMunicipioExp'], PDO::PARAM_INT);
            $stmt->bindValue(11, $array['datFecExp'], PDO::PARAM_STR);
            $stmt->bindValue(12, $array['slcPaisNac'], PDO::PARAM_INT);
            $stmt->bindValue(13, $array['slcDptoNac'], PDO::PARAM_INT);
            $stmt->bindValue(14, $array['slcMunicipioNac'], PDO::PARAM_INT);
            $stmt->bindValue(15, $array['datFecNac'], PDO::PARAM_STR);
            $stmt->bindValue(16, $array['slcGenero'], PDO::PARAM_STR);
            $stmt->bindValue(17, $array['txtApe1Emp'], PDO::PARAM_STR);
            $stmt->bindValue(18, $array['txtApe2Emp'], PDO::PARAM_STR);
            $stmt->bindValue(19, $array['txtNomb1Emp'], PDO::PARAM_STR);
            $stmt->bindValue(20, $array['txtNomb2Emp'], PDO::PARAM_STR);
            $stmt->bindValue(21, $array['slcSalIntegral'], PDO::PARAM_INT);
            $stmt->bindValue(22, $array['mailEmp'], PDO::PARAM_STR);
            $stmt->bindValue(23, $array['txtTelEmp'], PDO::PARAM_STR);
            $stmt->bindValue(24, $array['slcPaisEmp'], PDO::PARAM_INT);
            $stmt->bindValue(25, $array['slcDptoEmp'], PDO::PARAM_INT);
            $stmt->bindValue(26, $array['slcMunicipioEmp'], PDO::PARAM_INT);
            $stmt->bindValue(27, $array['txtDireccion'], PDO::PARAM_STR);
            $stmt->bindValue(28, $array['slcBancoEmp'], PDO::PARAM_INT);
            $stmt->bindValue(29, $array['selTipoCta'], PDO::PARAM_INT);
            $stmt->bindValue(30, $array['txtCuentaBanc'], PDO::PARAM_STR);
            $stmt->bindValue(31, isset($array['checkDependientes']) ? 1 : 0, PDO::PARAM_INT);
            $stmt->bindValue(32, isset($array['checkBsp']) ? 1 : 0, PDO::PARAM_INT);
            $stmt->bindValue(33, $array['id'], PDO::PARAM_INT);
            if (!($stmt->execute())) {
                return 'Errado: ' . $stmt->errorInfo()[2];
            } else {
                if ($stmt->rowCount() > 0) {
                    $consulta = "UPDATE `nom_empleado` SET `fec_actu` = ? WHERE (`id_empleado` = ?)";
                    $stmt2 = $this->conexion->prepare($consulta);
                    $stmt2->bindValue(1, Sesion::Hoy(), PDO::PARAM_STR);
                    $stmt2->bindValue(2, $array['id'], PDO::PARAM_INT);
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

    public function annulEmpleado($array)
    {
        try {
            $sql = "UPDATE `nom_empleado` 
                        SET `estado` = ?, `fec_actu` = ?
                    WHERE (`id_empleado` = ?)";
            $stmt = $this->conexion->prepare($sql);
            $stmt->bindValue(1, $array['estado'], PDO::PARAM_INT);
            $stmt->bindValue(2, Sesion::Hoy(), PDO::PARAM_STR);
            $stmt->bindValue(3, $array['id'], PDO::PARAM_INT);
            $stmt->execute();
            return 'si';
        } catch (PDOException $e) {
            return 'Error SQL: ' . $e->getMessage();
        }
    }
    public function addContrato($array)
    {
        try {
            $sql = "INSERT INTO `nom_cargo_empleado`
                        (`codigo`,`descripcion_carg`,`grado`,`perfil_siho`,`id_nombramiento`,`id_user_reg`,`fec_reg`)
                    VALUES (?, ?, ?, ?, ?, ?, ?)";
            $stmt = $this->conexion->prepare($sql);
            $stmt->bindValue(1, $array['slcCodigo'] ?? NULL, PDO::PARAM_INT);
            $stmt->bindValue(2, $array['txtNomCargo'], PDO::PARAM_STR);
            $stmt->bindValue(3, $array['numGrado'] ?? NULL, PDO::PARAM_INT);
            $stmt->bindValue(4, $array['txtPerfilSiho'] ?? NULL, PDO::PARAM_STR);
            $stmt->bindValue(5, $array['slcNombramiento'] ?? NULL, PDO::PARAM_INT);
            $stmt->bindValue(6, Sesion::IdUser(), PDO::PARAM_INT);
            $stmt->bindValue(7, Sesion::Hoy(), PDO::PARAM_STR);
            $stmt->execute();
            $id = $this->conexion->lastInsertId();
            return $id > 0 ? 'si' : 'No se insertó';
        } catch (PDOException $e) {
            return 'Error SQL: ' . $e->getMessage();
        }
    }
    public function getContratoActivo($id)
    {
        try {
            $sql = "SELECT MAX(`id_contrato_emp`) AS `id` FROM `nom_contratos_empleados` 
                    WHERE `estado` = 1 AND `id_empleado` = ?";
            $stmt = $this->conexion->prepare($sql);
            $stmt->bindValue(1, $id, PDO::PARAM_INT);
            $stmt->execute();
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            return $row && isset($row['id']) ? $row['id'] : 0;
        } catch (Exception $e) {
            return 'Error SQL: ' . $e->getMessage();
        }
    }

    public function getSalario($id)
    {
        try {
            $sql = "SELECT `salario_basico` FROM `nom_salarios_basico`
                    WHERE `id_salario` = (SELECT MAX(`id_salario`) FROM `nom_salarios_basico` WHERE `id_contrato` = ?)";
            $stmt = $this->conexion->prepare($sql);
            $stmt->bindValue(1, $id, PDO::PARAM_INT);
            $stmt->execute();
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            return $row && isset($row['salario_basico']) ? $row['salario_basico'] : 0;
        } catch (Exception $e) {
            return 'Error SQL: ' . $e->getMessage();
        }
    }

    public function getSalarioMasivo($mes)
    {
        $inicia = Sesion::Vigencia() . '-' . $mes . '-01';
        $fin = date('Y-m-t', strtotime($inicia));
        try {
            $sql = "SELECT 
                        `ctt`.`id_empleado`
                        , IFNULL(`salario`.`salario_basico`,0) AS `basico`
                    FROM
                        (SELECT
                            `id_empleado`
                            , `fec_inicio`
                            , IFNULL(`fec_fin`, '2999-12-31') AS `fec_fin`
                            , `id_contrato_emp`
                        FROM
                            `nom_contratos_empleados`
                        WHERE `id_contrato_emp` IN 
                            (SELECT MAX(`id_contrato_emp`) 
                            FROM `nom_contratos_empleados` 
                            WHERE `estado` = 1 GROUP BY `id_empleado`)) AS `ctt`
                        INNER JOIN 
                            (SELECT
                                `id_contrato`, `salario_basico`
                            FROM
                                `nom_salarios_basico`
                            WHERE `id_salario` IN 
                                (SELECT MAX(`id_salario`) 
                                FROM `nom_salarios_basico`
                                GROUP BY `id_contrato`)) AS `salario`
                            ON (`ctt`.`id_contrato_emp` = `salario`.`id_contrato`)
                    WHERE  `ctt`.`fec_inicio` <= ? AND `ctt`.`fec_fin` >= ?";
            $stmt = $this->conexion->prepare($sql);
            $stmt->bindValue(1, $fin, PDO::PARAM_STR);
            $stmt->bindValue(2, $inicia, PDO::PARAM_STR);
            $stmt->execute();
            $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $stmt->closeCursor();
            unset($stmt);
            return $result;
        } catch (Exception $e) {
            return 'Error SQL: ' . $e->getMessage();
        }
    }

    public function addSalario($array)
    {
        try {
            $sql = "INSERT INTO `nom_salarios_basico`
                        (`id_empleado`,`id_contrato`,`vigencia`,`salario_basico`,`fec_reg`,`id_inc`)
                    VALUES (?, ?, ?, ?, ?, ?)";
            $stmt = $this->conexion->prepare($sql);
            $stmt->bindParam(1, $array['id_empleado'], PDO::PARAM_INT);
            $stmt->bindValue(2, $array['id_contrato'], PDO::PARAM_INT);
            $stmt->bindValue(3, $array['vigencia'], PDO::PARAM_INT);
            $stmt->bindValue(4, $array['salario_basico'], PDO::PARAM_STR);
            $stmt->bindValue(5, Sesion::Hoy(), PDO::PARAM_STR);
            $stmt->bindValue(6, $array['id_inc'], PDO::PARAM_INT);
            $stmt->execute();
            $id = $this->conexion->lastInsertId();
            return $id > 0 ? 'si' : 'No se insertó';
        } catch (PDOException $e) {
            return 'Error SQL: ' . $e->getMessage();
        }
    }
    public function editCargo($array)
    {
        try {
            $sql = "UPDATE `nom_cargo_empleado` 
                        SET `codigo` = ?, `descripcion_carg` = ?, `grado` = ?, `perfil_siho` = ?, `id_nombramiento` = ?
                    WHERE (`id_cargo` = ?)";
            $stmt = $this->conexion->prepare($sql);
            $stmt->bindValue(1, $array['slcCodigo'] ?? NULL, PDO::PARAM_INT);
            $stmt->bindValue(2, $array['txtNomCargo'], PDO::PARAM_STR);
            $stmt->bindValue(3, $array['numGrado'] ?? NULL, PDO::PARAM_INT);
            $stmt->bindValue(4, $array['txtPerfilSiho'] ?? NULL, PDO::PARAM_STR);
            $stmt->bindValue(5, $array['slcNombramiento'] ?? NULL, PDO::PARAM_INT);
            $stmt->bindValue(6, $array['id'], PDO::PARAM_INT);
            if (!($stmt->execute())) {
                return 'Errado: ' . $stmt->errorInfo()[2];
            } else {
                if ($stmt->rowCount() > 0) {
                    $consulta = "UPDATE `nom_cargo_empleado` SET `id_user_act` = ?, `fec_act` = ? WHERE (`id_cargo` = ?)";
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
    /**
     * Agrega un nuevo centro de costo al empleado.
     *
     * @param array $array Datos del centro de costo a agregar
     * @return string Mensaje de éxito o error
     */
    public function addCentroCosto($array)
    {
        $sql = "INSERT INTO `nom_ccosto_empleado`
                    (`id_empleado`,`id_ccosto`,`id_user_reg`,`fec_reg`)
                VALUES (?, ?, ?, ?)";
        $stmt = $this->conexion->prepare($sql);
        $stmt->bindValue(1, $array['id_empleado'], PDO::PARAM_INT);
        $stmt->bindValue(2, $array['slcCCostoEmp'], PDO::PARAM_INT);
        $stmt->bindValue(3, Sesion::IdUser(), PDO::PARAM_INT);
        $stmt->bindValue(4, Sesion::Hoy(), PDO::PARAM_STR);
        $stmt->execute();
    }

    public function addNovedad($array)
    {
        $sql = "INSERT INTO `nom_terceros_novedad`
                        (`id_empleado`,`id_tercero`,`fec_inicia`,`fec_fin`,`id_riesgo`,`id_user_reg`,`fec_reg`)
                    VALUES (?, ?, ?, ?, ?, ?, ?)";
        $stmt = $this->conexion->prepare($sql);
        $stmt->bindValue(1, $array[1], PDO::PARAM_INT);
        $stmt->bindValue(2, $array[2], PDO::PARAM_INT);
        $stmt->bindValue(3, $array[3], PDO::PARAM_STR);
        $stmt->bindValue(4, $array[4], PDO::PARAM_STR);
        $stmt->bindValue(5, $array[5] ?? NULL, PDO::PARAM_INT);
        $stmt->bindValue(6, Sesion::IdUser(), PDO::PARAM_INT);
        $stmt->bindValue(7, Sesion::Hoy(), PDO::PARAM_STR);
        $stmt->execute();
    }

    public static function getSalarioBasico($id)
    {
        $Instance = new self();
        return $Instance->getSalario($Instance->getContratoActivo($id));
    }
}
