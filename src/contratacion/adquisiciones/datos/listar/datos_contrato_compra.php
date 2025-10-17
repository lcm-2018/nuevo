<?php
if (!isset($_SESSION['user'])) {
    header('Location: ../../../index.php');
    exit();
}

include_once '../../../config/autoloader.php';

$vigencia = $_SESSION['vigencia'];
try {
    $cmd = \Config\Clases\Conexion::getConexion();

    $sql = "SELECT
                `ctt_contratos`.`id_contrato_compra`
                , `ctt_contratos`.`id_compra`
                , `ctt_contratos`.`fec_ini`
                , `ctt_contratos`.`fec_fin`
                , `ctt_contratos`.`val_contrato`
                , `tb_forma_pago_compras`.`descripcion`
                , `ctt_contratos`.`id_supervisor`
                , `tb_terceros`.`nit_tercero`
                , `tb_terceros`.`nom_tercero`
            FROM
                `ctt_contratos`
            INNER JOIN `tb_forma_pago_compras` 
                ON (`ctt_contratos`.`id_forma_pago` = `tb_forma_pago_compras`.`id_form_pago`)
            LEFT JOIN `tb_terceros` 
                ON (`ctt_contratos`.`id_supervisor` = `tb_terceros`.`id_tercero_api`)
            WHERE `id_compra` = $id_adq";
    $rs = $cmd->query($sql);
    $contrato = $rs->fetch();
} catch (PDOException $e) {
    echo $e->getCode() == 2002 ? 'Sin Conexión a Mysql (Error: 2002)' : 'Error: ' . $e->getMessage();
}
$contrata = isset($contrato) ? $contrato['id_contrato_compra'] : 0;
try {
    $cmd = \Config\Clases\Conexion::getConexion();

    $sql = "SELECT
                `ctt_garantias_compra`.`id_contrato_compra`
                ,`ctt_garantias_compra`.`id_poliza`
                , `tb_polizas`.`descripcion`
                , `tb_polizas`.`porcentaje`
            FROM
                `ctt_garantias_compra`
            INNER JOIN `tb_polizas` 
                ON (`ctt_garantias_compra`.`id_poliza` = `tb_polizas`.`id_poliza`)
            WHERE `ctt_garantias_compra`.`id_contrato_compra` = '$contrata'";
    $rs = $cmd->query($sql);
    $garantias = $rs->fetchAll(PDO::FETCH_ASSOC);
    $rs->closeCursor();
    unset($rs);
} catch (PDOException $e) {
    echo $e->getCode() == 2002 ? 'Sin Conexión a Mysql (Error: 2002)' : 'Error: ' . $e->getMessage();
}
$fini = new DateTime($contrato['fec_ini']);
$ffin = new DateTime($contrato['fec_fin']);
$diferencia = $fini->diff($ffin);
$dias = intval($diferencia->format('%d')) + 1;
$meses = intval($diferencia->format('%m')) > 0 ? intval($diferencia->format('%m')) . ' mes(es) ' : '';


$fini = new DateTime($contrato['fec_ini']);
$ffin = new DateTime($contrato['fec_fin']);
$ffin_ajustada = clone $ffin;
$ffin_ajustada->modify('+1 day');
$diff = $fini->diff($ffin_ajustada);
$dias = $diff->d > 0 ? $diff->d . ' día(s)' : '';
$meses = $diff->m > 0 ? $diff->m . ' mes(es) ' : '';
$val_contrato = pesos($contrato['val_contrato']);
$garant = '';
foreach ($garantias as $g) {
    $garant .= '<li>' . $g['descripcion'] . ' ' . $g['porcentaje'] . '%</li>';
}
$editar = $borrar = $superv = null;
if ($adquisicion['estado'] <= 7) {
    if ($permisos->PermisosUsuario($opciones, 5302, 3) || $id_rol == 1) {
        $editar = '<a value="' . $contrata . '" class="btn btn-outline-primary btn-xs rounded-circle shadow me-1 editar" title="Editar"><span class="fas fa-pencil-alt"></span></a>';
    }
    if ($permisos->PermisosUsuario($opciones, 5302, 4) || $id_rol == 1) {
        $borrar = '<a value="' . $contrata . '" class="btn btn-outline-danger btn-xs rounded-circle shadow me-1 borrar" title="Eliminar"><span class="fas fa-trash-alt"></span></a>';
    }
} else if ($adquisicion['estado'] == 8) {
    $superv = '<a value="' . $contrata . '" class="btn btn-outline-info btn-xs rounded-circle shadow me-1 supervisor" title="Designar Supervisor"><span class="fas fa-user-secret"></span></a>';
}
$ctt_html =
    <<<HTML
<div class="overflow">
    <table class="table table-striped table-bordered table-sm nowrap table-hover shadow" style="width:100%">
        <thead>
            <tr>
                <th rowspan="2" class="bg-sofia">Fecha Inicial</th>
                <th rowspan="2" class="bg-sofia">Fecha Final</th>
                <th rowspan="2" class="bg-sofia">Duración</th>
                <th rowspan="2" class="bg-sofia">Forma de Pago</th>
                <th rowspan="2" class="bg-sofia">Valor Contrato</th>
                <th rowspan="2" class="bg-sofia">Garantías / Pólizas</th>
                <th colspan="2" class="bg-sofia">Supervisor</th>
                <th rowspan="2" class="bg-sofia">Acciones</th>
            </tr>
            <tr>
                <th class="bg-sofia">No. Documento</th>
                <th class="bg-sofia">Nombre</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td class="">{$contrato['fec_ini']}</td>
                <td class="">{$contrato['fec_fin']}</td>
                <td class="">
                    {$meses}{$dias}
                </td>
                <td class="">{$contrato['descripcion']}</td>
                <td class="text-end">{$val_contrato}</td>
                <td class="">
                    {$garant}
                </td>
                <td class="">
                    {$contrato['nit_tercero']}
                    <input type="hidden" id="id_sup_desig" value="{$contrato['id_supervisor']}">
                </td>
                <td class="">{$contrato['nom_tercero']}</td>
                <td class="" id="modificarContraCompra">
                    <div class="text-center">
                        {$editar}{$borrar}{$superv}
                    </div>
                </td>
            </tr>
        </tbody>
    </table>
</div>
HTML;
