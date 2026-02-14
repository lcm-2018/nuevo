<?php
session_start();
if (!isset($_SESSION['user'])) {
    header("Location: ../../../../index.php");
    exit();
}

include_once '../../../../config/autoloader.php';

$vigencia           =   $_SESSION['vigencia'];
$start              =   isset($_POST['start']) ? intval($_POST['start']) : 0;
$length             =   isset($_POST['length']) ? intval($_POST['length']) : 10;
$col                =   $_POST['order'][0]['column'] + 1;
$dir                =   $_POST['order'][0]['dir'];
$array['search']    =   $_POST['search']['value'] ?? '';
$array['id_nomina'] =   isset($_POST['id_nomina']) ? intval($_POST['id_nomina']) : exit('Acceso no autorizado');

$id_rol = $_SESSION['rol'];
$id_user = $_SESSION['id_user'];

use Config\Clases\Conexion;
use Src\Nomina\Liquidado\Php\Clases\Detalles;
use Src\Common\Php\Clases\Permisos;
use Src\Common\Php\Clases\Valores;

$sql        = new Detalles();
$permisos   = new Permisos();

$opciones           =   $permisos->PermisoOpciones($id_user);
$obj                =   $sql->getRegistrosDT($start, $length, $array, $col, $dir);
$totalRecordsFilter =   $sql->getRegistrosFilter($array);
$totalRecords       =   $sql->getRegistrosTotal($array);

$cmd = Conexion::getConexion();
$fecha = isset($obj[0]['mes']) ? $_SESSION['vigencia'] . '-' . $obj[0]['mes'] . '-01' : date('Y-m-01');
$sql = "SELECT
            `nom_terceros_novedad`.`id_empleado`,
            `tb_terceros`.`nom_tercero`
        FROM
            `nom_terceros_novedad`
            INNER JOIN `nom_terceros` 
                ON (`nom_terceros_novedad`.`id_tercero` = `nom_terceros`.`id_tn`)
            LEFT JOIN `tb_terceros`
            ON (`tb_terceros`.`id_tercero_api` = `nom_terceros`.`id_tercero_api`)
        WHERE 
            `nom_terceros`.`id_tipo` = 4 AND '$fecha' >= `nom_terceros_novedad`.`fec_inicia`
            AND ('$fecha' <= `nom_terceros_novedad`.`fec_fin` OR `nom_terceros_novedad`.`fec_fin` IS NULL)";
$res = $cmd->query($sql);
$fondo = $res->fetchAll();
$fondo = array_column($fondo, 'nom_tercero', 'id_empleado');


$datos = [];
if (!empty($obj)) {
    foreach ($obj as $o) {
        $detalles = $anular = '';
        $valorLicencias = $o['valor_luto'] + $o['valor_mp'];
        $devengado = $o['valor_incap'] + $valorLicencias + $o['valor_vacacion']
            + $o['valor_laborado'] + $o['aux_tran'] + $o['aux_alim']
            + $o['horas_ext'] + $o['val_bsp'] + $o['val_prima_vac']
            + $o['g_representa'] + $o['val_bon_recrea'] + $o['valor_ps']
            + $o['valor_pv'] + $o['val_cesantias'] + $o['val_icesantias']
            + $o['val_compensa'] + $o['valor_viatico'];

        $deducciones = $o['valor_salud'] + $o['valor_pension'] + $o['val_psolidaria']
            + $o['valor_libranza'] + $o['valor_embargo'] + $o['valor_sind']
            + $o['val_retencion'] + $o['valor_dcto'];

        $patronal = $o['valor_salud'] + $o['valor_pension'] + $o['val_psolidaria']
            + $o['val_rieslab'] + $o['val_salud_empresa'] + $o['val_pension_empresa'];


        $neto = $devengado - $deducciones;

        if ($permisos->PermisosUsuario($opciones, 5101, 1) || $id_rol == 1) {
            $detalles = '<button type="button" class="btn btn-outline-warning btn-xs rounded-circle shadow me-1 detalles" title="Ver Detalles"><i class="fa fa-eye"></i></button>';
        }
        if (($permisos->PermisosUsuario($opciones, 5101, 5) || $id_rol == 1) && $o['estado_nomina'] == 1) {
            $anular .= '<button class="btn btn-outline-secondary btn-xs rounded-circle shadow me-1 anular" title="Anular Registro"><span class="fas fa-ban fa-sm"></span></button>';
        }
        $tipo_cargo = $o['tipo_cargo'] == 1 ? 'ADMINISTRATIVO' : 'OPERATIVO';

        $datos[] = [
            'id_empleado'       => $o['id_empleado'],
            'sede'              => $o['sede'],
            'nombre'            => Valores::TextFormat($o['nombre']),
            'no_documento'      => $o['no_documento'],
            'descripcion_carg'  => $o['descripcion_carg'],
            'sal_base'          => Valores::formatNumber($o['sal_base']),
            'dias_incapacidad'  => $o['dias_incapacidad'],
            'dias_licencias'    => $o['dias_licencias'],
            'dias_vacaciones'   => $o['dias_inactivo'],
            'dias_otros'        => $o['dias_otros'],
            'dias_lab'          => $o['dias_lab'],
            'valor_incap'       => Valores::formatNumber($o['valor_incap']),
            'valor_licencias'   => Valores::formatNumber($valorLicencias),
            'valor_vacacion'    => Valores::formatNumber($o['valor_vacacion']),
            'valor_otros'       => Valores::formatNumber(0.00),
            'valor_laborado'    => Valores::formatNumber($o['valor_laborado']),
            'aux_tran'          => Valores::formatNumber($o['aux_tran']),
            'aux_alim'          => Valores::formatNumber($o['aux_alim']),
            'horas_ext'         => Valores::formatNumber($o['horas_ext']),
            'val_bsp'           => Valores::formatNumber($o['val_bsp']),
            'val_prima_vac'     => Valores::formatNumber($o['val_prima_vac']),
            'g_representa'      => Valores::formatNumber($o['g_representa']),
            'val_bon_recrea'    => Valores::formatNumber($o['val_bon_recrea']),
            'valor_ps'          => Valores::formatNumber($o['valor_ps']),
            'valor_pv'          => Valores::formatNumber($o['valor_pv']),
            'val_cesantias'     => Valores::formatNumber($o['val_cesantias']),
            'val_icesantias'    => Valores::formatNumber($o['val_icesantias']),
            'val_compensa'      => Valores::formatNumber($o['val_compensa']),
            'valor_viatico'     => Valores::formatNumber($o['valor_viatico']),
            'devengado'         => Valores::formatNumber($devengado),
            'valor_salud'       => Valores::formatNumber($o['valor_salud']),
            'valor_pension'     => Valores::formatNumber($o['valor_pension']),
            'val_psolidaria'    => Valores::formatNumber($o['val_psolidaria']),
            'val_rlaboral'      => Valores::formatNumber($o['val_rieslab']),
            'valor_salud_patronal' => Valores::formatNumber($o['val_salud_empresa']),
            'valor_pension_patronal' => Valores::formatNumber($o['val_pension_empresa']),
            'valor_libranza'    => Valores::formatNumber($o['valor_libranza']),
            'valor_embargo'     => Valores::formatNumber($o['valor_embargo']),
            'valor_sind'        => Valores::formatNumber($o['valor_sind']),
            'val_retencion'     => Valores::formatNumber($o['val_retencion']),
            'valor_dcto'        => Valores::formatNumber($o['valor_dcto']),
            'deducciones'       => Valores::formatNumber($deducciones),
            'neto'              => Valores::formatNumber($neto),
            'patronal'          => $patronal,
            'accion'            => '<div class="text-center">' . ($detalles ?? '') . ($anular ?? '') . '</div>',
            'nom_eps'           => $o['nom_eps'],
            'nom_afp'           => $o['nom_afp'],
            'nom_arl'           => $o['nom_arl'],
            'nom_fc'            => isset($fondo[$o['id_empleado']]) ? $fondo[$o['id_empleado']] : '',
            'tipo_cargo'        => $tipo_cargo,
        ];
    }
}
$data = [
    'data'              => $datos,
    'recordsFiltered'   => $totalRecordsFilter,
    'recordsTotal'      => $totalRecords,
];
echo json_encode($data);
